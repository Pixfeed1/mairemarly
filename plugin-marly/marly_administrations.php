<?php
/**
 * Installation et désinstallation.
 * ---------------------------------------------------------------------------
 * SPIP appelle marly_upgrade() à l'activation et à chaque montée de version.
 * maj_plugin() compare la version enregistrée à la version cible et n'exécute
 * que les étapes manquantes : une installation neuve les joue toutes, une
 * mise à jour ne rejoue rien.
 */

if (!defined('_ECRIRE_INC_VERSION')) {
	return;
}

function marly_upgrade($nom_meta_base_version, $version_cible) {
	$maj = array();

	/* 1.0.0 — le plugin ne faisait que des réglages : rien en base. */
	$maj['1.0.0'] = array();

	/* 2.0.0 — les deux tables de la réservation. creer_base() les crée à
	   partir des déclarations de base/marly.php : aucun SQL écrit ici, donc
	   aucune divergence possible entre la déclaration et la table réelle. */
	$maj['2.0.0'] = array(
		array('maj_tables', array('spip_salles', 'spip_reservations')),
	);

	/* 3.0.0 — les manifestations, et deux colonnes de plus sur les
	   réservations. maj_tables ajoute les colonnes manquantes sans toucher
	   aux données : les réservations de salles déjà saisies survivent. */
	$maj['3.0.0'] = array(
		array('maj_tables', array('spip_manifestations', 'spip_reservations')),
	);

	/* 3.1.0 — les tables passent en objets editoriaux declares (l'API de
	   SPIP 4). Aucune colonne ne change : c'est la declaration qui evolue,
	   pour ouvrir les logos et la recherche. maj_tables est rejoue par
	   securite, il ne fera rien si tout est deja en place. */
	$maj['3.1.0'] = array(
		array('maj_tables', array('spip_manifestations', 'spip_salles', 'spip_reservations')),
	);

	/* 3.2.0 — une colonne video sur les evenements. */
	$maj['3.2.0'] = array(
		array('maj_tables', array('spip_manifestations')),
	);

	/* 3.4.0 — la table des abonnes a la lettre d'information. */
	$maj['3.4.0'] = array(
		array('maj_tables', array('spip_abonnes')),
	);

	/* 3.5.0 — prenom, code postal et commune sur les abonnes. */
	$maj['3.5.0'] = array(
		array('maj_tables', array('spip_abonnes')),
	);

	/* 3.6.0 — les lettres envoyees. */
	$maj['3.6.0'] = array(
		array('maj_tables', array('spip_lettres')),
	);

	/* 3.9.0 — un lien video et la reprise des dernieres actualites sur les
	   lettres. Les lettres deja ecrites gardent leur contenu : maj_tables
	   ajoute les colonnes, il ne reecrit rien. */
	$maj['3.9.0'] = array(
		array('maj_tables', array('spip_lettres')),
	);

	/* 3.11.0 — les demarches, et le socle national pose une seule fois.
	   marly_poser_socle() ne fait rien si la table contient deja quelque
	   chose : une fiche que la mairie a supprimee ne doit pas revenir a la
	   mise a jour suivante. */
	$maj['3.11.0'] = array(
		array('maj_tables', array('spip_demarches')),
		array('marly_poser_socle'),
	);

	/* 3.12.0 — les elus, et six colonnes de plus sur les demarches. */
	$maj['3.12.0'] = array(
		array('maj_tables', array('spip_elus', 'spip_demarches')),
	);

	/* 3.13.0 — les raccourcis de la page d'accueil. */
	$maj['3.13.0'] = array(
		array('maj_tables', array('spip_raccourcis')),
	);

	/* 3.14.0 — l'annuaire des associations, et la fiche << creer une
	   association >> qui manquait au socle. */
	$maj['3.14.0'] = array(
		array('maj_tables', array('spip_associations')),
		array('marly_ajouter_demarche_association'),
	);

	/* 3.16.0 — la rubrique ou une association publie ses articles. */
	$maj['3.16.0'] = array(
		array('maj_tables', array('spip_associations')),
	);

	/* 3.18.0 — les lieux de la commune, et le lieu d'une association. */
	$maj['3.18.0'] = array(
		array('maj_tables', array('spip_lieux', 'spip_associations')),
	);

	/* 3.20.0 — les coordonnees d'une association, tirees de son adresse. */
	$maj['3.20.0'] = array(
		array('maj_tables', array('spip_associations')),
	);

	/* 3.21.0 — les associations saisies avant que la creation automatique
	   n'existe recoivent leur rubrique. Une seule fois. */
	$maj['3.21.0'] = array(
		array('marly_rubriques_associations_manquantes'),
	);

	/* 3.36.0 — quatre demarches de plus au socle (livret de famille,
	   changement de prenom, concession au cimetiere, buvette), et le
	   parcours en etapes de la carte d'identite. */
	$maj['3.36.0'] = array(
		array('marly_completer_socle_2026'),
	);

	/* 3.36.2 — chaque site nomme dans une fiche devient cliquable la ou il
	   est nomme. Rattrape une base passee par 3.36.0, dont le texte en
	   etapes ne portait pas encore les liens. */
	$maj['3.36.2'] = array(
		array('marly_lier_sites_nommes'),
	);

	/* 3.36.3 — une passe d'ecriture : les textes les plus abrupts gagnent
	   une transition. Seules les fiches restees au texte connu bougent. */
	$maj['3.36.3'] = array(
		array('marly_adoucir_textes'),
	);

	/* 3.36.4 — la procuration entre au socle, et les liens qui manquaient
	   encore (teleservice des actes, verification d'inscription electorale,
	   cadastre, France Identite) se posent sur les fiches non retouchees. */
	$maj['3.36.4'] = array(
		array('marly_completer_liens_2026'),
	);

	include_spip('base/upgrade');
	maj_plugin($nom_meta_base_version, $version_cible, $maj);
}

/**
 * Insère la fiche « Voter par procuration » si elle n'existe pas, puis pose
 * les liens manquants sur les fiches restées à la version connue du champ.
 * Une fiche retouchée par la mairie ne bouge jamais.
 */
function marly_completer_liens_2026() {
	include_spip('inc/marly_demarches');

	$anciens = array(
		'Demander un acte de naissance, de mariage ou de décès' => array(
			'comment' => 'La demande se fait sur place, par courrier ou en ligne, au choix. Précisez la date de l’événement et les noms et prénoms des parents : ce sont eux qui permettent de retrouver l’acte.',
			'lien_faire' => '',
		),
		'S’inscrire sur les listes électorales' => array(
			'comment' => 'L’inscription se fait en ligne en quelques minutes, ou au secrétariat de la mairie si vous préférez être accompagné.',
			'a_savoir' => '',
		),
		'Consulter le cadastre' => array(
			'comment' => 'Rendez-vous sur le site officiel du cadastre : cherchez la commune, puis la parcelle. La consultation et l’impression sont gratuites.',
		),
		'Carte d’identité ou passeport' => array(
			'a_savoir' => 'Un titre non retiré dans les trois mois est détruit, sans remboursement. Méfiez-vous des sites payants qui imitent les sites officiels : la pré-demande et le rendez-vous sont gratuits. Avec l’application France Identité, votre nouvelle carte peut aussi prouver votre identité en ligne.',
		),
	);

	foreach (marly_socle_demarches() as $fiche) {
		if ($fiche['titre'] === 'Voter par procuration'
		and !sql_countsel('spip_demarches', 'titre = ' . sql_quote($fiche['titre']))) {
			$fiche['statut'] = 'publie';
			sql_insertq('spip_demarches', $fiche);
			continue;
		}
		if (!isset($anciens[$fiche['titre']])) {
			continue;
		}
		foreach ($anciens[$fiche['titre']] as $champ => $ancien) {
			sql_updateq('spip_demarches', array($champ => $fiche[$champ]),
				'titre = ' . sql_quote($fiche['titre'])
				. ' AND ' . $champ . ' = ' . sql_quote($ancien));
		}
	}
	spip_log('marly : procuration inseree et liens completes', 'marly.' . _LOG_INFO_IMPORTANTE);
}

/**
 * Réapplique les textes adoucis du socle aux fiches restées à la version
 * précédente exacte du champ. Une fiche retouchée par la mairie ne bouge
 * jamais : la garde est l'égalité stricte avec l'ancien texte.
 */
function marly_adoucir_textes() {
	include_spip('inc/marly_demarches');

	$anciens = array(
		'Demander un acte de naissance, de mariage ou de décès' => array('comment' => 'Sur place, par courrier, ou en ligne. Précisez la date de l’événement et les noms et prénoms des parents.'),
		'Déclarer un décès' => array('comment' => 'En vous présentant à la mairie avec le certificat médical de décès.'),
		'Se marier' => array('comment' => 'Retirez le dossier au secrétariat, puis déposez-le complet. La date est fixée avec la mairie une fois le dossier reçu.'),
		'Conclure un PACS' => array('comment' => 'Prenez rendez-vous au secrétariat avec la convention et le dossier complet.'),
		'Se faire recenser à 16 ans' => array('comment' => 'À la mairie du domicile. Un parent peut faire la démarche si le jeune est mineur.'),
		'S’inscrire sur les listes électorales' => array('comment' => 'En ligne en quelques minutes, ou au secrétariat de la mairie.'),
		'Demander une attestation d’accueil' => array('comment' => 'Sur place, au secrétariat. La demande est instruite avant délivrance.'),
		'Permis de construire' => array('comment' => 'Dépôt du dossier à la mairie, sur place ou par voie électronique.'),
		'Certificat d’urbanisme' => array('comment' => 'Dépôt du formulaire à la mairie, sur place ou par voie électronique.'),
		'Carte grise (certificat d’immatriculation)' => array(
			'comment' => 'Entièrement en ligne, sur [le site de l’ANTS->https://immatriculation.ants.gouv.fr/]. Méfiez-vous des sites payants qui imitent le site officiel.',
			'ou'      => 'En ligne uniquement.',
		),
		'Permis de conduire' => array(
			'comment' => 'En ligne sur [le site de l’ANTS->https://permisdeconduire.ants.gouv.fr/].',
			'ou'      => 'En ligne uniquement.',
		),
		'Consulter le cadastre' => array('comment' => 'Sur le site officiel du cadastre, en cherchant la commune puis la parcelle.'),
		'Impôts et taxes' => array('comment' => 'Depuis votre espace particulier sur [le site des impôts->https://www.impots.gouv.fr/].'),
	);

	foreach (marly_socle_demarches() as $fiche) {
		if (!isset($anciens[$fiche['titre']])) {
			continue;
		}
		foreach ($anciens[$fiche['titre']] as $champ => $ancien) {
			sql_updateq('spip_demarches', array($champ => $fiche[$champ]),
				'titre = ' . sql_quote($fiche['titre'])
				. ' AND ' . $champ . ' = ' . sql_quote($ancien));
		}
	}
	spip_log('marly : textes adoucis sur les fiches non retouchees', 'marly.' . _LOG_INFO_IMPORTANTE);
}

/**
 * Reapplique les textes du socle qui viennent de gagner leurs liens, mais
 * UNIQUEMENT sur les fiches restees a une version connue du texte : une
 * fiche retouchee par la mairie ne bouge pas. Les versions connues sont
 * l'originale et celle de la 3.36.0.
 */
function marly_lier_sites_nommes() {
	include_spip('inc/marly_demarches');

	$anciens = array(
		'Carte d’identité ou passeport' => array(
			'comment' => array(
				'Faites d’abord la pré-demande en ligne sur le site de l’ANTS, puis prenez rendez-vous dans une mairie équipée. Vous pouvez vous rendre dans n’importe laquelle, quel que soit votre domicile.',
				"-# Faites la pré-demande en ligne sur le site de l'ANTS. Elle est gratuite : seul le timbre fiscal du passeport est payant.\n-# Prenez rendez-vous dans une mairie équipée. N'importe laquelle, quel que soit votre domicile : les plus proches sont sur la carte de l'ANTS.\n-# Déposez le dossier au rendez-vous. La personne concernée doit être présente, ses empreintes sont recueillies.\n-# Un SMS vous prévient quand le titre est prêt : retirez-le, sans rendez-vous, là où vous avez déposé le dossier.",
			),
			'pieces' => array('Photo d’identité récente, justificatif de domicile, timbre fiscal pour un passeport, et ancien titre s’il s’agit d’un renouvellement.'),
			'ou' => array('Dans une mairie équipée d’un dispositif de recueil. La carte des mairies habilitées est sur le site de l’ANTS.'),
		),
		'Carte grise (certificat d’immatriculation)' => array(
			'comment' => array('Entièrement en ligne, sur le site de l’ANTS. Méfiez-vous des sites payants qui imitent le site officiel.'),
		),
		'Permis de conduire' => array(
			'comment' => array('En ligne sur le site de l’ANTS.'),
		),
		'Impôts et taxes' => array(
			'comment' => array('Depuis votre espace particulier sur le site des impôts.'),
		),
	);

	foreach (marly_socle_demarches() as $fiche) {
		if (!isset($anciens[$fiche['titre']])) {
			continue;
		}
		foreach ($anciens[$fiche['titre']] as $champ => $versions_connues) {
			foreach ($versions_connues as $version_connue) {
				sql_updateq('spip_demarches', array($champ => $fiche[$champ]),
					'titre = ' . sql_quote($fiche['titre'])
					. ' AND ' . $champ . ' = ' . sql_quote($version_connue));
			}
		}
	}
	spip_log('marly : liens poses sur les sites nommes des fiches non retouchees',
		'marly.' . _LOG_INFO_IMPORTANTE);
}

/**
 * Complète le socle des démarches, sans jamais faire revenir une fiche
 * que la mairie aurait supprimée : seules les QUATRE fiches nouvelles de
 * cette version sont concernées, chacune uniquement si son titre est
 * absent. Et le parcours en étapes de la carte d'identité ne remplace le
 * texte existant que s'il est resté celui d'origine : une fiche retouchée
 * par la mairie ne se fait pas écraser.
 */
function marly_completer_socle_2026() {
	include_spip('inc/marly_demarches');

	$nouvelles = array(
		'Demander un second livret de famille',
		'Changer de prénom',
		'Acheter ou renouveler une concession au cimetière',
		'Ouvrir une buvette pour une fête (débit de boissons temporaire)',
	);
	$ajoutees = 0;
	foreach (marly_socle_demarches() as $fiche) {
		if (!in_array($fiche['titre'], $nouvelles, true)) {
			continue;
		}
		if (sql_countsel('spip_demarches', 'titre = ' . sql_quote($fiche['titre']))) {
			continue;
		}
		$fiche['socle']  = 1;
		$fiche['statut'] = 'publie';
		if (sql_insertq('spip_demarches', $fiche)) {
			$ajoutees++;
		}
	}

	$ancien = 'Faites d’abord la pré-demande en ligne sur le site de l’ANTS, puis prenez rendez-vous dans une mairie équipée. Vous pouvez vous rendre dans n’importe laquelle, quel que soit votre domicile.';
	foreach (marly_socle_demarches() as $fiche) {
		if ($fiche['titre'] === 'Carte d’identité ou passeport') {
			sql_updateq('spip_demarches',
				array('comment' => $fiche['comment'], 'a_savoir' => $fiche['a_savoir']),
				'titre = ' . sql_quote($fiche['titre'])
				. ' AND comment = ' . sql_quote($ancien));
		}
	}

	spip_log("marly : socle complete, $ajoutees fiche(s) ajoutee(s)", 'marly.' . _LOG_INFO_IMPORTANTE);
}

/**
 * Pose le socle national des démarches, si et seulement si la table est vide.
 *
 * La condition compte plus que l'insertion. Sans elle, une mairie qui aurait
 * supprimé « Attestation d'accueil » parce qu'elle ne la délivre pas la
 * verrait réapparaître à chaque mise à jour du plugin, sans comprendre
 * pourquoi. Le socle est un point de départ, pas une norme imposée.
 */
function marly_poser_socle() {
	if (sql_countsel('spip_demarches')) {
		spip_log('marly : des demarches existent deja, socle non repose', 'marly.' . _LOG_INFO_IMPORTANTE);
		return;
	}

	include_spip('inc/marly_demarches');
	$posees = 0;
	foreach (marly_socle_demarches() as $fiche) {
		$fiche['socle']  = 1;
		$fiche['statut'] = 'publie';
		if (sql_insertq('spip_demarches', $fiche)) {
			$posees++;
		}
	}
	spip_log("marly : socle pose, $posees demarches", 'marly.' . _LOG_INFO_IMPORTANTE);
}

/**
 * Ajoute la fiche « Créer une association », oubliée dans le socle initial.
 *
 * Insérée UNE FOIS, et seulement si aucune fiche ne porte déjà ce titre. Une
 * mairie qui l'aurait supprimée entre-temps ne la verra pas revenir : c'est
 * la même règle que pour le socle, et elle vaut aussi pour les rattrapages.
 */
function marly_ajouter_demarche_association() {
	if (sql_countsel('spip_demarches', 'titre LIKE ' . sql_quote('%association%'))) {
		return;
	}

	sql_insertq('spip_demarches', array(
		'titre'   => 'Créer une association',
		'famille' => 'enligne',
		'icone'   => 'ri-team-line',
		'rang'    => 90,
		'socle'   => 1,
		'statut'  => 'publie',
		'resume'  => 'La déclaration se fait en ligne ou à la préfecture. La mairie n’intervient pas, mais elle peut vous orienter.',
		'qui'     => 'Au moins deux personnes majeures souhaitant mener un projet commun sans but lucratif.',
		'comment' => 'Rédigez les statuts, tenez l’assemblée constitutive, puis déclarez l’association en ligne. La publication au Journal officiel se demande au même moment.',
		'pieces'  => 'Statuts signés, procès-verbal de l’assemblée constitutive, et liste des dirigeants.',
		'cout'    => 'Déclaration gratuite. Publication au Journal officiel : 44 €',
		'delai'   => 'Récépissé sous 5 jours, publication sous un mois environ',
		'ou'      => 'En ligne, ou au greffe des associations de la préfecture. Le secrétariat de la mairie peut vous aider à constituer le dossier.',
		'a_savoir' => 'Une fois l’association déclarée, signalez-la à la mairie : elle figurera dans l’annuaire du site et pourra réserver la salle des fêtes.',
		'lien'      => 'https://www.service-public.gouv.fr/particuliers/vosdroits/F1120',
		'lien_faire' => 'https://www.service-public.gouv.fr/particuliers/vosdroits/R37933',
	));
	spip_log('marly : fiche << creer une association >> ajoutee', 'marly.' . _LOG_INFO_IMPORTANTE);
}

/**
 * Donne sa rubrique à chaque association qui n'en a pas.
 *
 * Rattrapage pour celles saisies avant que la création automatique n'existe.
 * Il ne tourne qu'une fois, à cette étape de mise à jour : à ce moment-là,
 * aucune mairie n'a encore pu choisir « Aucune » délibérément, puisque le
 * choix vient d'apparaître. Plus tard, ce serait défaire une décision.
 *
 * C'est aussi la première fois que marly_rubrique_association() s'exécute
 * pour de bon. Si l'appel à SPIP échoue, rien n'est perdu : les associations
 * restent sans rubrique, et la mairie peut en choisir une à la main.
 */
function marly_rubriques_associations_manquantes() {
	if (!sql_countsel('spip_associations', 'id_rubrique = 0')) {
		return;
	}

	include_spip('inc/marly_associations');
	$faites = 0;
	foreach (sql_allfetsel('id_association, nom', 'spip_associations', 'id_rubrique = 0') as $a) {
		$id_rubrique = marly_rubrique_association($a['nom']);
		if ($id_rubrique) {
			sql_updateq('spip_associations', array('id_rubrique' => $id_rubrique),
				'id_association = ' . intval($a['id_association']));
			$faites++;
		}
	}
	spip_log("marly : $faites rubrique(s) d'association creee(s) en rattrapage", 'marly.' . _LOG_INFO_IMPORTANTE);
}

function marly_vider_tables($nom_meta_base_version) {
	/* On supprime les tables : ce sont les nôtres, personne d'autre ne les
	   lit. Les réglages, eux, restent — désactiver le plugin ne doit pas
	   faire perdre le numéro de téléphone de la mairie. Ils seront effacés
	   avec la meta ci-dessous seulement si l'on désinstalle vraiment. */
	sql_drop_table('spip_lieux');
	sql_drop_table('spip_associations');
	sql_drop_table('spip_raccourcis');
	sql_drop_table('spip_elus');
	sql_drop_table('spip_demarches');
	sql_drop_table('spip_lettres');
	sql_drop_table('spip_abonnes');
	sql_drop_table('spip_manifestations');
	sql_drop_table('spip_reservations');
	sql_drop_table('spip_salles');

	effacer_meta($nom_meta_base_version);
}
