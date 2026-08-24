<?php
/**
 * Les deux tables de la réservation de salles.
 * ---------------------------------------------------------------------------
 * SALLES — ce que la commune loue. Chaque salle porte ses propres règles :
 * on ne réserve pas la salle des fêtes et la salle du conseil dans les mêmes
 * délais, ni aux mêmes tarifs.
 *
 * RESERVATIONS — une demande, puis son sort. Le statut est le cœur de tout :
 *
 *     demande  → acceptee  : le créneau est pris, plus personne ne peut l'avoir
 *              → refusee   : le créneau redevient libre
 *              → annulee   : après acceptation, la mairie ou le demandeur renonce
 *
 * Seul « acceptee » bloque un créneau. Deux demandes peuvent porter sur la
 * même date — c'est normal, et c'est à la mairie de trancher. Un logiciel qui
 * interdirait la seconde demande cacherait à la mairie qu'il y a conflit.
 */

if (!defined('_ECRIRE_INC_VERSION')) {
	return;
}

function marly_declarer_tables_objets_sql($tables) {

	$tables['spip_salles'] = array(
		'field' => array(
			'id_salle'            => 'bigint(21) NOT NULL',
			'titre'               => 'text NOT NULL DEFAULT ""',
			'descriptif'          => 'text NOT NULL DEFAULT ""',
			'capacite'            => 'int(11) NOT NULL DEFAULT 0',
			'tarif_commune'       => 'varchar(60) NOT NULL DEFAULT ""',
			'tarif_hors_commune'  => 'varchar(60) NOT NULL DEFAULT ""',
			'caution'             => 'varchar(60) NOT NULL DEFAULT ""',
			/* Délais exprimés en jours. Bidart impose « pas plus de 6 mois
			   d'avance » sur une salle et « entre 1 et 3 mois » sur l'autre :
			   ces règles varient d'une salle à l'autre, elles vivent donc
			   sur la salle et non dans le code. */
			'delai_min'           => 'int(11) NOT NULL DEFAULT 3',
			'delai_max'           => 'int(11) NOT NULL DEFAULT 365',
			'statut'              => 'varchar(20) NOT NULL DEFAULT "prepa"',
			'maj'                 => 'TIMESTAMP',
		),
		'key' => array(
			'PRIMARY KEY'  => 'id_salle',
			'KEY statut'   => 'statut',
		),
		'titre'      => 'titre AS titre, "" AS lang',
		'principale' => 'oui',
		'type'       => 'salle',

		/* Comment cet objet s'appelle. Sans ces deux lignes, SPIP cherche une
		   chaine titre_salle qui n'existe pas, et affiche le nom de la cle
		   dans sa barre d'administration : << titre salle >>. */
		'texte_objet'  => 'marly:objet_salle',
		'texte_objets' => 'marly:objets_salles',
		'editable'   => 'oui',
		'champs_editables'  => array('titre', 'descriptif', 'capacite', 'tarif_commune',
		                             'tarif_hors_commune', 'caution', 'delai_min', 'delai_max'),
		'rechercher_champs' => array('titre' => 8, 'descriptif' => 3),
		'statut' => array(
			array(
				'champ'     => 'statut',
				'publie'    => 'publie',
				'previsu'   => 'publie,prepa',
				'exception' => 'statut',
			),
		),
	);


	/*
	 * MANIFESTATIONS — tout ce à quoi on s'inscrit : repas des aînés, sortie
	 * en car, emplacement de brocante, atelier, séance de cinéma itinérant.
	 *
	 * La différence avec une salle n'est pas de nature mais de comptage.
	 * Une salle, c'est « ce créneau est-il libre ? » — une exclusivité.
	 * Une manifestation, c'est « combien reste-t-il de places ? » — un stock.
	 * Deux questions différentes, donc deux tables.
	 *
	 * Le nom : PAS spip_evenements. Le plugin Agenda de SPIP utilise déjà ce
	 * nom, et le jour où la mairie l'installerait pour son calendrier, les
	 * deux se marcheraient dessus.
	 */

	/*
	 * ABONNÉS à la lettre d'information.
	 *
	 * DOUBLE CONFIRMATION obligatoire, et ce n'est pas un raffinement : sans
	 * elle, n'importe qui inscrit l'adresse d'un voisin, et la commune se
	 * retrouve à envoyer des courriels non sollicités en son nom. Une adresse
	 * ne devient active qu'après un clic dans un courriel reçu à cette
	 * adresse-là — c'est la seule preuve qu'elle appartient bien au
	 * demandeur.
	 *
	 * Les adresses en attente depuis plus de sept jours sont effacees : une
	 * adresse jamais confirmee est une donnee qu'on n'a pas le droit de
	 * garder.
	 */

	/*
	 * LETTRES envoyées.
	 *
	 * Le CURSEUR est ce qui rend l'envoi reprenable. On n'envoie pas deux
	 * cents courriels dans une requête HTTP : le serveur coupe au bout de
	 * trente secondes, et l'expéditeur ne sait pas où il s'est arrêté — la
	 * moitié des abonnés reçoit deux fois, l'autre rien. On envoie par lots,
	 * et le curseur retient le dernier abonné servi.
	 */
	$tables['spip_lettres'] = array(
		'field' => array(
			'id_lettre'    => 'bigint(21) NOT NULL',
			'titre'        => 'text NOT NULL DEFAULT ""',
			'chapo'        => 'text NOT NULL DEFAULT ""',
			'texte'        => 'longtext NOT NULL DEFAULT ""',

			/* Un lien vers une video. Elle ne se LIT pas dans un courriel :
			   toutes les messageries ou presque suppriment la balise video.
			   On envoie donc un appel a la regarder, qui ouvre la video sur
			   le site — c'est ce que font tous les envois en nombre. */
			'video'        => 'varchar(255) NOT NULL DEFAULT ""',

			/* Reprendre en fin de lettre les derniers articles publies. Une
			   case a cocher plutot qu'un choix d'articles : le secretariat
			   n'a pas a composer une selection en plus d'ecrire la lettre,
			   et les trois derniers articles sont, par construction, ceux
			   qu'on vient de publier. */
			'actus'        => 'tinyint(1) NOT NULL DEFAULT 1',

			/* redaction -> envoi -> envoyee, ou arretee */
			'statut'       => 'varchar(20) NOT NULL DEFAULT "redaction"',

			'curseur'      => 'bigint(21) NOT NULL DEFAULT 0',
			'nb_envoyes'   => 'int(11) NOT NULL DEFAULT 0',
			'nb_erreurs'   => 'int(11) NOT NULL DEFAULT 0',

			'date'         => "datetime NOT NULL DEFAULT '0000-00-00 00:00:00'",
			'date_envoi'   => "datetime NOT NULL DEFAULT '0000-00-00 00:00:00'",
			'maj'          => 'TIMESTAMP',
		),
		'key' => array(
			'PRIMARY KEY' => 'id_lettre',
			'KEY statut'  => 'statut',
		),
		'titre'      => 'titre AS titre, "" AS lang',
		'date'       => 'date',
		'principale' => 'oui',
		'type'       => 'lettre',

		/* Comment cet objet s'appelle. Sans ces deux lignes, SPIP cherche une
		   chaine titre_lettre qui n'existe pas, et affiche le nom de la cle
		   dans sa barre d'administration : << titre lettre >>. */
		'texte_objet'  => 'marly:objet_lettre',
		'texte_objets' => 'marly:objets_lettres',

		/* PAS editable au sens de SPIP. Une lettre n'a ni logo, ni page
		   publique, ni fiche a consulter : la declarer editable faisait
		   apparaitre une colonne << Logo lettre >> et un lien << Voir en
		   ligne >> qui ne menaient nulle part. Notre formulaire est un CVT
		   maison, il n'a pas besoin de cette machinerie. */
		'editable'   => 'non',
	);

	/* LIEUX — les batiments de la commune.
	   ------------------------------------------------------------------------
	   Cinq ou six adresses qui ne bougeront jamais : la mairie, la salle des
	   fetes, l'ecole, l'eglise, le terrain. Elles etaient jusqu'ici recopiees
	   en toutes lettres dans chaque fiche d'association et chaque evenement.

	   Les coordonnees sont relevees une fois, a la main, et rangees ici. Ce
	   n'est pas de la paresse : interroger un service de geocodage a chaque
	   enregistrement, pour cinq points qui ne bougeront jamais, serait une
	   dependance exterieure pour rien — et une panne de plus le jour ou ce
	   service change ses conditions. */
	$tables['spip_lieux'] = array(
		'field' => array(
			'id_lieu'     => 'bigint(21) NOT NULL',
			'nom'         => 'varchar(180) NOT NULL DEFAULT ""',
			'type'        => 'varchar(30) NOT NULL DEFAULT "autre"',
			'adresse'     => 'text NOT NULL DEFAULT ""',
			/* En degres decimaux, tels que openstreetmap.org les donne. */
			'latitude'    => 'varchar(24) NOT NULL DEFAULT ""',
			'longitude'   => 'varchar(24) NOT NULL DEFAULT ""',
			'horaires'    => 'text NOT NULL DEFAULT ""',
			'descriptif'  => 'text NOT NULL DEFAULT ""',
			'rang'        => 'int(11) NOT NULL DEFAULT 100',
			'statut'      => 'varchar(20) NOT NULL DEFAULT "publie"',
			'maj'         => 'TIMESTAMP',
		),
		'key' => array(
			'PRIMARY KEY' => 'id_lieu',
			'KEY statut'  => 'statut',
		),
		'titre'      => 'nom AS titre, "" AS lang',
		'principale' => 'oui',
		'type'       => 'lieu',

		/* Comment cet objet s'appelle. Sans ces deux lignes, SPIP cherche une
		   chaine titre_lieu qui n'existe pas, et affiche le nom de la cle
		   dans sa barre d'administration : << titre lieu >>. */
		'texte_objet'  => 'marly:objet_lieu',
		'texte_objets' => 'marly:objets_lieux',
		'editable'   => 'oui',
		'champs_editables'  => array('nom', 'type', 'adresse', 'latitude', 'longitude',
		                             'horaires', 'descriptif', 'rang'),
		'rechercher_champs' => array('nom' => 8, 'adresse' => 4, 'descriptif' => 2),
		'statut' => array(
			array(
				'champ'     => 'statut',
				'publie'    => 'publie',
				'previsu'   => 'publie,prepa',
				'exception' => 'statut',
			),
		),
		'texte_modifier'    => 'marly:modifier_lieu',
		'texte_creer'       => 'marly:creer_lieu',
		'info_aucun_objet'  => 'marly:aucun_lieu',
	);

	/* ASSOCIATIONS — l'annuaire de la vie associative.
	   ------------------------------------------------------------------------
	   Des champs separes, comme pour les demarches et les elus. Ce qu'on vient
	   chercher dans un annuaire d'associations n'est pas une presentation :
	   c'est QUI appeler. Un texte libre laisse ce renseignement se perdre au
	   milieu d'un paragraphe, ou manquer tout a fait.

	   Le theme n'est pas une etiquette libre : dix associations saisies par
	   trois personnes donneraient << sport >>, << Sports >> et << sportif >>,
	   et le regroupement ne marcherait plus. */
	$tables['spip_associations'] = array(
		'field' => array(
			'id_association' => 'bigint(21) NOT NULL',
			'nom'         => 'varchar(180) NOT NULL DEFAULT ""',
			'theme'       => 'varchar(40) NOT NULL DEFAULT "autre"',
			'activite'    => 'text NOT NULL DEFAULT ""',
			'president'   => 'varchar(180) NOT NULL DEFAULT ""',
			'telephone'   => 'varchar(60) NOT NULL DEFAULT ""',
			'courriel'    => 'varchar(255) NOT NULL DEFAULT ""',
			'site'        => 'varchar(255) NOT NULL DEFAULT ""',
			'id_lieu'     => 'bigint(21) NOT NULL DEFAULT 0',

			/* Ou se deroulent les activites, ecrit en clair, et les
			   coordonnees qui en sont tirees automatiquement a
			   l'enregistrement. La mairie ne saisit qu'une chose. */
			'lieu'        => 'varchar(255) NOT NULL DEFAULT ""',
			'latitude'    => 'varchar(24) NOT NULL DEFAULT ""',
			'longitude'   => 'varchar(24) NOT NULL DEFAULT ""',
			'horaires'    => 'text NOT NULL DEFAULT ""',
			/* La rubrique ou l'association ecrit, si elle ecrit.
			   ------------------------------------------------------------------
			   C'est ce qui reliait deja les associations a leurs articles sur
			   l'ancien site, et c'etait la bonne idee : il manquait seulement
			   de la brancher sur la fiche. A zero, la fiche n'affiche aucune
			   actualite, et c'est tres bien : une association sur deux n'a rien
			   a publier. */
			'id_rubrique' => 'bigint(21) NOT NULL DEFAULT 0',

			'rang'        => 'int(11) NOT NULL DEFAULT 100',
			'statut'      => 'varchar(20) NOT NULL DEFAULT "publie"',
			'maj'         => 'TIMESTAMP',
		),
		'key' => array(
			'PRIMARY KEY' => 'id_association',
			'KEY statut'  => 'statut',
			'KEY theme'   => 'theme',
		),
		'titre'      => 'nom AS titre, "" AS lang',
		'principale' => 'oui',
		'type'       => 'association',

		/* Comment cet objet s'appelle. Sans ces deux lignes, SPIP cherche une
		   chaine titre_association qui n'existe pas, et affiche le nom de la cle
		   dans sa barre d'administration : << titre association >>. */
		'texte_objet'  => 'marly:objet_association',
		'texte_objets' => 'marly:objets_associations',
		'editable'   => 'oui',
		'champs_editables'  => array('nom', 'theme', 'activite', 'president', 'telephone',
		                             'courriel', 'site', 'lieu', 'id_lieu', 'horaires', 'rang', 'id_rubrique'),
		'rechercher_champs' => array('nom' => 8, 'activite' => 4, 'president' => 2),
		'statut' => array(
			array(
				'champ'     => 'statut',
				'publie'    => 'publie',
				'previsu'   => 'publie,prepa',
				'exception' => 'statut',
			),
		),
		'texte_modifier'    => 'marly:modifier_association',
		'texte_creer'       => 'marly:creer_association',
		'info_aucun_objet'  => 'marly:aucune_association',
	);

	/* COMMERCES ET SERVICES — l'annuaire des professionnels du village.
	   ------------------------------------------------------------------------
	   Une table a part, et non des lignes de plus dans les LIEUX, bien que les
	   champs se ressemblent. Deux raisons, toutes deux du cote de la mairie :
	   la page << Ou nous trouver >> liste les batiments de la commune et n'a
	   aucune raison d'y meler vingt entreprises privees ; et dans l'espace
	   prive, la secretaire qui cherche l'ecole ne doit pas la chercher parmi
	   les couvreurs. Un objet = une idee, comme les associations, qui
	   dupliquent deja adresse et coordonnees pour la meme raison.

	   Ce que la mairie saisit est le minimum : un nom, une categorie, une
	   phrase, un contact. Tout le reste est facultatif — beaucoup de ces
	   professionnels n'ont ni site, ni courriel, ni horaires fixes. */
	$tables['spip_commerces'] = array(
		'field' => array(
			'id_commerce' => 'bigint(21) NOT NULL',
			'nom'         => 'varchar(180) NOT NULL DEFAULT ""',
			'categorie'   => 'varchar(40) NOT NULL DEFAULT "commerce"',
			'activite'    => 'text NOT NULL DEFAULT ""',
			'responsable' => 'varchar(180) NOT NULL DEFAULT ""',
			'telephone'   => 'varchar(60) NOT NULL DEFAULT ""',
			'courriel'    => 'varchar(255) NOT NULL DEFAULT ""',
			'site'        => 'varchar(255) NOT NULL DEFAULT ""',

			/* L'adresse ecrite en clair, et les coordonnees qui en sont
			   tirees a l'enregistrement. La mairie ne saisit qu'une chose. */
			'lieu'        => 'varchar(255) NOT NULL DEFAULT ""',
			'latitude'    => 'varchar(24) NOT NULL DEFAULT ""',
			'longitude'   => 'varchar(24) NOT NULL DEFAULT ""',
			'horaires'    => 'text NOT NULL DEFAULT ""',

			'rang'        => 'int(11) NOT NULL DEFAULT 100',
			'statut'      => 'varchar(20) NOT NULL DEFAULT "publie"',
			'maj'         => 'TIMESTAMP',
		),
		'key' => array(
			'PRIMARY KEY'   => 'id_commerce',
			'KEY statut'    => 'statut',
			'KEY categorie' => 'categorie',
		),
		'titre'      => 'nom AS titre, "" AS lang',
		'principale' => 'oui',
		'type'       => 'commerce',
		'texte_objet'  => 'marly:objet_commerce',
		'texte_objets' => 'marly:objets_commerces',
		'editable'   => 'oui',
		'champs_editables'  => array('nom', 'categorie', 'activite', 'responsable',
		                             'telephone', 'courriel', 'site', 'lieu',
		                             'horaires', 'rang'),
		'rechercher_champs' => array('nom' => 8, 'activite' => 4, 'responsable' => 2),
		'statut' => array(
			array(
				'champ'     => 'statut',
				'publie'    => 'publie',
				'previsu'   => 'publie,prepa',
				'exception' => 'statut',
			),
		),
		'texte_modifier'    => 'marly:modifier_commerce',
		'texte_creer'       => 'marly:creer_commerce',
		'info_aucun_objet'  => 'marly:aucun_commerce',
	);

	/* RACCOURCIS — les six ronds de la page d'accueil.
	   ------------------------------------------------------------------------
	   Ils venaient de mots-cles poses sur des articles : un groupe pour les
	   raccourcis, un autre pour les icones, un chapo commencant par << = >>
	   pour la destination. Trois mecanismes a comprendre pour poser un bouton,
	   et le secretariat de mairie n'a aucune raison de les apprendre.

	   Ici, une ligne = un rond. Un intitule, une icone choisie au clic, une
	   destination prise dans une liste. La destination est ecrite sous la
	   forme << type:valeur >> — demarche:12, rubrique:3, page:reservation,
	   url:https://... — parce qu'une colonne par type de cible aurait laisse
	   cinq colonnes vides sur six a chaque ligne. */
	$tables['spip_raccourcis'] = array(
		'field' => array(
			'id_raccourci' => 'bigint(21) NOT NULL',
			'titre'        => 'varchar(120) NOT NULL DEFAULT ""',
			'icone'        => 'varchar(60) NOT NULL DEFAULT ""',
			'cible'        => 'varchar(255) NOT NULL DEFAULT ""',
			'rang'         => 'int(11) NOT NULL DEFAULT 100',
			'statut'       => 'varchar(20) NOT NULL DEFAULT "publie"',
			'maj'          => 'TIMESTAMP',
		),
		'key' => array(
			'PRIMARY KEY' => 'id_raccourci',
			'KEY statut'  => 'statut',
		),
		'titre'      => 'titre AS titre, "" AS lang',
		'principale' => 'oui',
		'type'       => 'raccourci',

		/* Comment cet objet s'appelle. Sans ces deux lignes, SPIP cherche une
		   chaine titre_raccourci qui n'existe pas, et affiche le nom de la cle
		   dans sa barre d'administration : << titre raccourci >>. */
		'texte_objet'  => 'marly:objet_raccourci',
		'texte_objets' => 'marly:objets_raccourcis',
		'editable'   => 'oui',
		'champs_editables'  => array('titre', 'icone', 'cible', 'rang'),
		'rechercher_champs' => array('titre' => 8),
		'statut' => array(
			array(
				'champ'     => 'statut',
				'publie'    => 'publie',
				'previsu'   => 'publie,prepa',
				'exception' => 'statut',
			),
		),
		'texte_modifier'    => 'marly:modifier_raccourci',
		'texte_creer'       => 'marly:creer_raccourci',
		'info_aucun_objet'  => 'marly:aucun_raccourci',
	);

	/* ELUS — qui est responsable de quoi.
	   ------------------------------------------------------------------------
	   Une table a part, et non des champs libres sur chaque fiche. L'elu
	   referent de l'etat civil est le meme sur les six fiches d'etat civil :
	   en champ libre, on le saisit six fois, on se trompe une fois sur le
	   numero, et le jour ou il change de delegation il faut retrouver les six.
	   C'est ainsi qu'un site de mairie devient faux au bout de deux ans.

	   Elle servira aussi a la page du conseil municipal, que toute commune
	   finit par vouloir. */
	$tables['spip_elus'] = array(
		'field' => array(
			'id_elu'      => 'bigint(21) NOT NULL',
			'nom'         => 'varchar(120) NOT NULL DEFAULT ""',
			'prenom'      => 'varchar(120) NOT NULL DEFAULT ""',
			'fonction'    => 'varchar(120) NOT NULL DEFAULT ""',
			'delegation'  => 'varchar(255) NOT NULL DEFAULT ""',
			'telephone'   => 'varchar(60) NOT NULL DEFAULT ""',
			'courriel'    => 'varchar(255) NOT NULL DEFAULT ""',
			'permanence'  => 'text NOT NULL DEFAULT ""',

			/* Le parcours de la personne, ecrit par la mairie. Un texte long
			   et non une ligne : c'est ce qui remplit sa fiche, et la fiche
			   est ce qu'on ouvre quand la delegation ne suffit pas. */
			'biographie'  => 'text NOT NULL DEFAULT ""',
			'rang'        => 'int(11) NOT NULL DEFAULT 100',
			'statut'      => 'varchar(20) NOT NULL DEFAULT "publie"',
			'maj'         => 'TIMESTAMP',
		),
		'key' => array(
			'PRIMARY KEY' => 'id_elu',
			'KEY statut'  => 'statut',
		),
		'titre'      => 'nom AS titre, "" AS lang',
		'principale' => 'oui',
		'type'       => 'elu',

		/* Comment cet objet s'appelle. Sans ces deux lignes, SPIP cherche une
		   chaine titre_elu qui n'existe pas, et affiche le nom de la cle
		   dans sa barre d'administration : << titre elu >>. */
		'texte_objet'  => 'marly:objet_elu',
		'texte_objets' => 'marly:objets_elus',
		'editable'   => 'oui',
		'champs_editables'  => array('nom', 'prenom', 'fonction', 'delegation',
		                             'telephone', 'courriel', 'permanence', 'biographie'),
		'rechercher_champs' => array('nom' => 8, 'prenom' => 6, 'fonction' => 4,
		                             'delegation' => 3, 'biographie' => 2),
		'statut' => array(
			array(
				'champ'     => 'statut',
				'publie'    => 'publie',
				'previsu'   => 'publie,prepa',
				'exception' => 'statut',
			),
		),
		'texte_modifier'    => 'marly:modifier_elu',
		'texte_creer'       => 'marly:creer_elu',
		'info_aucun_objet'  => 'marly:aucun_elu',
	);

	/* DEMARCHES — les fiches pratiques.
	   ------------------------------------------------------------------------
	   Une fiche n'est pas un article : elle a toujours la meme structure — qui
	   est concerne, comment faire, quelles pieces, combien, quel delai, ou
	   s'adresser. Des champs separes et non un texte libre, pour que toutes les
	   fiches se ressemblent quel que soit qui les a saisies, et pour qu'aucune
	   ne parte sans dire ou aller.

	   Le SOCLE (une quinzaine de fiches identiques dans les 34 000 communes)
	   est pose a l'installation. Il RENVOIE vers la fiche officielle au lieu de
	   recopier la reglementation : une commune qui recopie le droit diffuse une
	   information fausse deux ans plus tard, et c'est elle qui en repond. */
	$tables['spip_demarches'] = array(
		'field' => array(
			'id_demarche'  => 'bigint(21) NOT NULL',
			'titre'        => 'text NOT NULL DEFAULT ""',

			/* mairie : on vient au guichet — enligne : ca se fait depuis chez
			   soi — ailleurs : ce n'est pas la mairie qui traite, et le seul
			   service a rendre est d'eviter un deplacement pour rien. */
			'famille'      => 'varchar(20) NOT NULL DEFAULT "mairie"',

			/* Le nom technique d'une icone du sprite. Choisie dans une liste
			   fermee cote formulaire : une icone absente du sprite ne provoque
			   aucune erreur, elle s'affiche vide, en silence. */
			'icone'        => 'varchar(60) NOT NULL DEFAULT ""',

			'resume'       => 'text NOT NULL DEFAULT ""',
			'qui'          => 'text NOT NULL DEFAULT ""',
			'comment'      => 'text NOT NULL DEFAULT ""',
			'pieces'       => 'text NOT NULL DEFAULT ""',
			'cout'         => 'varchar(120) NOT NULL DEFAULT ""',
			'delai'        => 'varchar(120) NOT NULL DEFAULT ""',
			'ou'           => 'text NOT NULL DEFAULT ""',

			/* La fiche officielle sur service-public.gouv.fr. */
			'lien'         => 'varchar(255) NOT NULL DEFAULT ""',
			/* Le teleservice, quand la demarche se fait vraiment en ligne. */
			'lien_faire'   => 'varchar(255) NOT NULL DEFAULT ""',

			/* Vient du socle national. Sert a distinguer, dans l'espace prive,
			   ce que la mairie a ecrit de ce qui lui a ete fourni — et a ne
			   jamais reposer une fiche qu'elle aurait supprimee. */
			'socle'        => 'tinyint(1) NOT NULL DEFAULT 0',

			/* L'elu referent : une reference, pas une copie. */
			'id_elu'       => 'bigint(21) NOT NULL DEFAULT 0',

			/* Le contact propre a cette demarche. Vides, la fiche affiche ceux
			   de la mairie, pris dans les reglages : on ne saisit que
			   l'exception. */
			'contact_tel'      => 'varchar(60) NOT NULL DEFAULT ""',
			'contact_courriel' => 'varchar(255) NOT NULL DEFAULT ""',

			/* L'encadre permanent — << les demarches d'etat civil sont
			   gratuites, mefiez-vous des sites payants >>. */
			'a_savoir'     => 'text NOT NULL DEFAULT ""',

			/* L'avertissement temporaire, et SA DATE DE FIN. Un message qu'il
			   faut penser a retirer n'est jamais retire : celui de la ville
			   dont on s'inspire annonce un incident de mars, et il est encore
			   affiche en aout. Le notre s'efface seul. */
			'alerte'       => 'text NOT NULL DEFAULT ""',
			'alerte_fin'   => "date NOT NULL DEFAULT '0000-00-00'",

			'rang'         => 'int(11) NOT NULL DEFAULT 100',
			'statut'       => 'varchar(20) NOT NULL DEFAULT "publie"',
			'maj'          => 'TIMESTAMP',
		),
		'key' => array(
			'PRIMARY KEY'   => 'id_demarche',
			'KEY statut'    => 'statut',
			'KEY famille'   => 'famille',
		),
		'titre'      => 'titre AS titre, "" AS lang',
		'principale' => 'oui',
		'type'       => 'demarche',

		/* Comment cet objet s'appelle. Sans ces deux lignes, SPIP cherche une
		   chaine titre_demarche qui n'existe pas, et affiche le nom de la cle
		   dans sa barre d'administration : << titre demarche >>. */
		'texte_objet'  => 'marly:objet_demarche',
		'texte_objets' => 'marly:objets_demarches',
		'editable'   => 'oui',
		'champs_editables'  => array('titre', 'famille', 'icone', 'resume', 'qui', 'comment',
		                             'pieces', 'cout', 'delai', 'ou', 'lien', 'lien_faire', 'rang',
		                             'id_elu', 'contact_tel', 'contact_courriel',
		                             'a_savoir', 'alerte', 'alerte_fin'),
		'rechercher_champs' => array('titre' => 8, 'resume' => 4, 'qui' => 2, 'comment' => 2),
		'statut' => array(
			array(
				'champ'     => 'statut',
				'publie'    => 'publie',
				'previsu'   => 'publie,prepa',
				'exception' => 'statut',
			),
		),
		'texte_modifier'    => 'marly:modifier_demarche',
		'texte_creer'       => 'marly:creer_demarche',
		'info_aucun_objet'  => 'marly:aucune_demarche',
	);

	$tables['spip_abonnes'] = array(
		'field' => array(
			'id_abonne'    => 'bigint(21) NOT NULL',
			'courriel'     => 'varchar(255) NOT NULL DEFAULT ""',
			'nom'          => 'varchar(255) NOT NULL DEFAULT ""',
			'prenom'       => 'varchar(255) NOT NULL DEFAULT ""',

			/* Le code postal et la commune servent a distinguer les habitants
			   des residents secondaires et des voisins abonnes : la mairie ne
			   communique pas la meme chose aux uns et aux autres. C'est la
			   seule justification acceptable pour les demander — sans usage
			   prevu, ce serait une collecte de trop. */
			'code_postal'  => 'varchar(10) NOT NULL DEFAULT ""',
			'ville'        => 'varchar(255) NOT NULL DEFAULT ""',

			/* attente -> confirme -> desinscrit */
			'statut'       => 'varchar(20) NOT NULL DEFAULT "attente"',

			/* Le meme jeton sert a confirmer puis a se desinscrire : le lien
			   de desinscription doit figurer dans CHAQUE envoi, sans que le
			   destinataire ait a se souvenir de quoi que ce soit. */
			'jeton'        => 'varchar(32) NOT NULL DEFAULT ""',

			'date'         => "datetime NOT NULL DEFAULT '0000-00-00 00:00:00'",
			'date_confirmation' => "datetime NOT NULL DEFAULT '0000-00-00 00:00:00'",
			'maj'          => 'TIMESTAMP',
		),
		'key' => array(
			'PRIMARY KEY'      => 'id_abonne',
			'UNIQUE KEY courriel' => 'courriel',
			'KEY statut'       => 'statut',
			'KEY jeton'        => 'jeton',
		),
		'titre'      => 'courriel AS titre, "" AS lang',
		'date'       => 'date',
		'principale' => 'oui',
		'type'       => 'abonne',

		/* Comment cet objet s'appelle. Sans ces deux lignes, SPIP cherche une
		   chaine titre_abonne qui n'existe pas, et affiche le nom de la cle
		   dans sa barre d'administration : << titre abonne >>. */
		'texte_objet'  => 'marly:objet_abonne',
		'texte_objets' => 'marly:objets_abonnes',
		'editable'   => 'non',
	);

	$tables['spip_manifestations'] = array(
		'field' => array(
			'id_manifestation' => 'bigint(21) NOT NULL',
			'titre'            => 'text NOT NULL DEFAULT ""',
			'descriptif'       => 'text NOT NULL DEFAULT ""',
			'lieu'             => 'varchar(255) NOT NULL DEFAULT ""',
			'date_debut'       => "datetime NOT NULL DEFAULT '0000-00-00 00:00:00'",
			'date_fin'         => "datetime NOT NULL DEFAULT '0000-00-00 00:00:00'",

			/* Le stock. 0 signifie « sans limite » : une kermesse en plein air
			   n'a pas de jauge, et forcer un nombre obligerait à en inventer un. */
			'places'           => 'int(11) NOT NULL DEFAULT 0',
			'places_par_personne' => 'int(11) NOT NULL DEFAULT 1',

			'tarif'            => 'varchar(60) NOT NULL DEFAULT ""',

			/* L'adresse d'une video : YouTube, Vimeo, Dailymotion, PeerTube.
			   On garde l'adresse telle que la mairie l'a collee, et on la
			   traduit a l'affichage. Stocker un identifiant extrait
			   obligerait a refaire le travail le jour ou une plateforme
			   change ses adresses. */
			'video'            => 'varchar(255) NOT NULL DEFAULT ""',

			/* auto   : l'inscription est confirmée tout de suite. C'est ce
			            qu'attend quelqu'un qui s'inscrit au repas des aînés.
			   mairie : la mairie arbitre, comme pour une salle. */
			'validation'       => 'varchar(10) NOT NULL DEFAULT "auto"',

			/* La fenêtre d'inscription, distincte de la date de l'événement :
			   on ouvre les inscriptions au repas six semaines avant, et on les
			   ferme huit jours avant pour commander les couverts. */
			'ouverture'        => "datetime NOT NULL DEFAULT '0000-00-00 00:00:00'",
			'cloture'          => "datetime NOT NULL DEFAULT '0000-00-00 00:00:00'",

			'statut'           => 'varchar(20) NOT NULL DEFAULT "prepa"',
			'maj'              => 'TIMESTAMP',
		),
		'key' => array(
			'PRIMARY KEY'   => 'id_manifestation',
			'KEY statut'    => 'statut',
			'KEY date_debut' => 'date_debut',
		),
		'titre'      => 'titre AS titre, "" AS lang',
		'date'       => 'date_debut',
		'principale' => 'oui',
		'type'       => 'manifestation',

		/* Comment cet objet s'appelle. Sans ces deux lignes, SPIP cherche une
		   chaine titre_manifestation qui n'existe pas, et affiche le nom de la cle
		   dans sa barre d'administration : << titre manifestation >>. */
		'texte_objet'  => 'marly:objet_manifestation',
		'texte_objets' => 'marly:objets_manifestations',
		'editable'   => 'oui',

		/* C'est cette declaration qui ouvre les LOGOS : SPIP autorise une
		   image sur tout objet editorial declare ici. Sans elle, la table
		   existe et les boucles marchent, mais il n'y a nulle part ou
		   deposer la photo. */
		'champs_editables'  => array('titre', 'descriptif', 'lieu', 'date_debut', 'date_fin',
		                             'places', 'places_par_personne', 'tarif', 'video',
		                             'validation', 'ouverture', 'cloture'),
		'rechercher_champs' => array('titre' => 8, 'descriptif' => 3, 'lieu' => 2),
		'statut' => array(
			array(
				'champ'     => 'statut',
				'publie'    => 'publie',
				'previsu'   => 'publie,prepa',
				'exception' => 'statut',
			),
		),
		'texte_modifier'    => 'marly:modifier_manifestation',
		'texte_creer'       => 'marly:creer_manifestation',
		'info_aucun_objet'  => 'marly:aucune_manifestation',
	);

	$tables['spip_reservations'] = array(
		'field' => array(
			'id_reservation'   => 'bigint(21) NOT NULL',
			/* Une réservation porte SOIT sur une salle, SOIT sur une
			   manifestation. L'autre colonne reste à zéro. Une seule table
			   pour les deux : même cycle de statuts, même écran de gestion,
			   mêmes courriels — les séparer aurait tout dupliqué. */
			'id_salle'         => 'bigint(21) NOT NULL DEFAULT 0',
			'id_manifestation' => 'bigint(21) NOT NULL DEFAULT 0',
			'places'           => 'int(11) NOT NULL DEFAULT 1',
			'date_debut'       => "datetime NOT NULL DEFAULT '0000-00-00 00:00:00'",
			'date_fin'         => "datetime NOT NULL DEFAULT '0000-00-00 00:00:00'",
			'statut'           => 'varchar(20) NOT NULL DEFAULT "demande"',

			/* Le demandeur. On ne demande pas de compte : exiger une
			   inscription pour réserver une salle des fêtes ferait fuir la
			   moitié des habitants. */
			'nom'              => 'varchar(255) NOT NULL DEFAULT ""',
			'organisme'        => 'varchar(255) NOT NULL DEFAULT ""',
			'courriel'         => 'varchar(255) NOT NULL DEFAULT ""',
			'telephone'        => 'varchar(30) NOT NULL DEFAULT ""',
			'motif'            => 'text NOT NULL DEFAULT ""',

			/* Le traitement par la mairie. */
			'reponse'          => 'text NOT NULL DEFAULT ""',
			'id_auteur'        => 'bigint(21) NOT NULL DEFAULT 0',
			'date_traitement'  => "datetime NOT NULL DEFAULT '0000-00-00 00:00:00'",

			/* Jeton d'annulation : il permet au demandeur d'annuler depuis
			   le courriel de confirmation, sans compte ni mot de passe. */
			'jeton'            => 'varchar(32) NOT NULL DEFAULT ""',

			'date'             => "datetime NOT NULL DEFAULT '0000-00-00 00:00:00'",
			'maj'              => 'TIMESTAMP',
		),
		'key' => array(
			'PRIMARY KEY'      => 'id_reservation',
			'KEY id_salle'     => 'id_salle',
			'KEY id_manifestation' => 'id_manifestation',
			'KEY statut'       => 'statut',
			/* L'index qui porte la recherche de conflit : on interroge
			   toujours par salle et par date. */
			'KEY creneau'      => 'id_salle,date_debut,date_fin',
			'KEY jeton'        => 'jeton',
		),
		'titre'      => 'nom AS titre, "" AS lang',
		'date'       => 'date',
		'principale' => 'oui',
		'type'       => 'reservation',

		/* Comment cet objet s'appelle. Sans ces deux lignes, SPIP cherche une
		   chaine titre_reservation qui n'existe pas, et affiche le nom de la cle
		   dans sa barre d'administration : << titre reservation >>. */
		'texte_objet'  => 'marly:objet_reservation',
		'texte_objets' => 'marly:objets_reservations',
		'editable'   => 'non',
	);

	return $tables;
}

/**
 * Les alias de tables pour les boucles.
 *
 * Quatre manquaient : les demarches, les elus, les raccourcis et les
 * associations, ajoutes apres coup sans que cette fonction suive. SPIP 4
 * renseigne en principe ces alias tout seul depuis declarer_tables_objets_sql,
 * mais une declaration incomplete a cote d'une declaration complete est un
 * piege pour la prochaine lecture : on croit voir la liste des tables, et il
 * en manque la moitie.
 */
function marly_declarer_tables_interfaces($interfaces) {
	$interfaces['table_des_tables']['salles']       = 'salles';
	$interfaces['table_des_tables']['reservations'] = 'reservations';
	$interfaces['table_des_tables']['manifestations'] = 'manifestations';
	$interfaces['table_des_tables']['abonnes'] = 'abonnes';
	$interfaces['table_des_tables']['lettres'] = 'lettres';
	$interfaces['table_des_tables']['demarches'] = 'demarches';
	$interfaces['table_des_tables']['elus'] = 'elus';
	$interfaces['table_des_tables']['raccourcis'] = 'raccourcis';
	$interfaces['table_des_tables']['associations'] = 'associations';
	$interfaces['table_des_tables']['lieux'] = 'lieux';
	$interfaces['table_des_tables']['commerces'] = 'commerces';
	return $interfaces;
}
