<?php
/**
 * Libellés de l'écran de réglages.
 * ---------------------------------------------------------------------------
 * Ils sont écrits pour la secrétaire de mairie, pas pour un informaticien :
 * chaque champ dit ce qu'on y met, et l'explication donne un exemple réel
 * plutôt qu'une description abstraite.
 */
if (!defined('_ECRIRE_INC_VERSION')) {
	return;
}

$GLOBALS[$GLOBALS['idx_lang']] = array(

	'titre_reglages'   => 'Réglages de la commune',
	'intro_reglages'   => 'Ces informations s’affichent dans l’en-tête et le pied de page du site. Laissez vide ce que la commune n’a pas : rien ne s’affichera.',

	'legende_contact'  => 'Coordonnées de la mairie',
	'legende_reseaux'  => 'Réseaux sociaux',
	'legende_appli'    => 'Application d’alerte',

	'telephone'        => 'Téléphone',
	'telephone_aide'   => 'Exemple : 03 23 60 22 69',
	'courriel'         => 'Adresse électronique',
	'adresse'          => 'Adresse postale',
	'adresse_aide'     => 'Numéro et rue, sans le code postal ni la ville',
	'code_postal'      => 'Code postal',
	'ville'            => 'Commune',
	'horaires'         => 'Horaires d’ouverture',
	'horaires_aide'    => 'Une ligne par jour ou par plage. Exemple : Lundi et jeudi, 14 h à 17 h',

	'facebook'         => 'Facebook',
	'instagram'        => 'Instagram',
	'youtube'          => 'YouTube',
	'x'                => 'X (ex-Twitter)',
	'linkedin'         => 'LinkedIn',
	'tiktok'           => 'TikTok',
	'whatsapp'         => 'WhatsApp',
	'mastodon'         => 'Mastodon',
	'bluesky'          => 'Bluesky',
	'reseaux_aide'     => 'Collez l’adresse complète de la page de la commune, en commençant par https://',

	'appli_nom'        => 'Nom de l’application',
	'appli_nom_aide'   => 'Exemple : PanneauPocket, IntraMuros',
	'appli_url'        => 'Adresse de l’application',

	// --- Réservation de salles ----------------------------------------------
	'titre_reservations'   => 'Demandes de réservation',
	'en_attente'           => 'Demandes en attente',
	'acceptees'            => 'Réservations acceptées à venir',
	'aucune_demande'       => 'Aucune demande en attente.',
	'aucune_acceptee'      => 'Aucune réservation acceptée à venir.',

	'col_creneau'          => 'Créneau',
	'col_ressource'        => 'Quoi',
	'col_salle'            => 'Quoi',
	'col_demandeur'        => 'Demandeur',
	'col_motif'            => 'Motif',
	'col_action'           => 'Décision',

	'accepter'             => 'Accepter',
	'refuser'              => 'Refuser',
	'annuler'              => 'Annuler',
	'conflit_accepte'      => 'Attention, ce créneau est déjà accordé à :',

	'legende_creneau'      => 'Quand',
	'legende_demandeur'    => 'Qui',
	'date_souhaitee'       => 'Date souhaitée',
	'heure_debut'          => 'De',
	'heure_fin'            => 'À',
	'votre_nom'            => 'Nom et prénom',
	'organisme'            => 'Association ou organisme',
	'organisme_aide'       => 'Laissez vide s’il s’agit d’une demande personnelle.',
	'votre_courriel'       => 'Adresse électronique',
	'votre_telephone'      => 'Téléphone',
	'motif'                => 'Motif de la demande',
	'motif_aide'           => 'Exemple : repas de famille, assemblée générale, loto de l’association.',
	'envoyer_demande'      => 'Envoyer ma demande',

	'reserver_explication' => 'Ce formulaire enregistre une DEMANDE : il ne réserve pas la salle. La mairie vous répondra par courriel. Les associations de la commune sont prioritaires.',
	'demande_enregistree'  => 'Votre demande a bien été enregistrée. Vous recevrez une réponse par courriel.',

	// --- Erreurs -------------------------------------------------------------
	'erreur_obligatoire'   => 'Ce champ est nécessaire.',
	'erreur_date'          => 'Cette date n’est pas valide.',
	'erreur_heure'         => 'Cette heure n’est pas valide.',
	'erreur_ordre_heures'  => 'L’heure de fin doit venir après l’heure de début.',
	'erreur_date_passee'   => 'Cette date est déjà passée.',
	'erreur_delai_min'     => 'Il faut demander au moins @n@ jours à l’avance.',
	'erreur_delai_max'     => 'On ne peut pas réserver plus de @n@ jours à l’avance.',
	'erreur_deja_pris'     => 'Ce créneau est déjà accordé. Choisissez une autre date.',
	'erreur_creneau_pris'  => 'Impossible : le créneau vient d’être accordé à @nom@.',
	'erreur_introuvable'   => 'Cette réservation est introuvable.',
	'erreur_statut'        => 'Ce changement d’état n’est pas prévu.',
	'erreur_enregistrement' => 'L’enregistrement a échoué. Réessayez, ou appelez la mairie.',

	// --- Courriels -----------------------------------------------------------
	'courriel_sujet_demande'   => '@site@ : votre demande de réservation',
	'courriel_corps_demande'   => 'Bonjour @nom@,

Nous avons bien reçu votre demande de réservation :

  Salle   : @salle@
  Du      : @date_debut@
  Au      : @date_fin@

Cette demande n’est pas encore une réservation. La mairie l’examinera et
vous répondra par courriel.

@site@',

	'courriel_sujet_mairie'    => '@site@ : nouvelle demande de réservation',

	'courriel_sujet_acceptee'  => '@site@ : votre réservation est confirmée',
	'courriel_corps_acceptee'  => 'Bonjour @nom@,

Votre réservation est confirmée :

  Salle   : @salle@
  Du      : @date_debut@
  Au      : @date_fin@

@reponse@

@site@',

	'courriel_sujet_refusee'   => '@site@ : votre demande n’a pas pu être retenue',
	'courriel_corps_refusee'   => 'Bonjour @nom@,

Votre demande de réservation pour @salle@, le @date_debut@, n’a pas pu être
retenue.

@reponse@

N’hésitez pas à contacter la mairie pour envisager une autre date.

@site@',

	'courriel_sujet_annulee'   => '@site@ : votre réservation a été annulée',
	'courriel_corps_annulee'   => 'Bonjour @nom@,

Votre réservation de @salle@ le @date_debut@ a été annulée.

@reponse@

@site@',

	// --- Salles ---------------------------------------------------------------
	'titre_espace'         => 'Réservations',
	'titre_lettre_info'    => 'Lettre d’information',
	'titre_ressources'     => 'Ce qu’on peut réserver',
	'onglet_demandes'      => 'Demandes',
	'onglet_ressources'    => 'Ce qu’on peut réserver',
	'onglet_evenements'    => 'Événements',
	'onglet_lettres'       => 'Lettres',
	'onglet_abonnes'       => 'Abonnés',
	'creer_ressource'      => 'Ajouter quelque chose à réserver',
	'modifier_ressource'   => 'Modifier',
	'aucune_ressource'     => 'Rien à réserver pour l’instant. Ajoutez une salle, un terrain, du matériel, tout ce qui se prête ou se loue.',
	'ressource_enregistree' => 'Enregistré.',
	'col_delais'           => 'Délais',
	'col_etat'             => 'État',
	'jours'                => 'jours',

	'champ_titre'              => 'Nom',
	'champ_capacite'           => 'Capacité (personnes assises)',
	'capacite_aide'            => 'Pour une salle, le nombre autorisé par la commission de sécurité. Laissez vide si la question ne se pose pas : du matériel n’a pas de capacité.',
	'champ_tarif_commune'      => 'Tarif habitants de la commune',
	'tarif_aide'               => 'Texte libre : « 80 € la journée », « gratuit pour les associations ».',
	'champ_tarif_hors_commune' => 'Tarif hors commune',
	'champ_caution'            => 'Caution',
	'champ_delai_min'          => 'Délai minimum',
	'delai_min_aide'           => 'En jours. Une demande faite plus tard est refusée automatiquement.',
	'champ_delai_max'          => 'Délai maximum',
	'delai_max_aide'           => 'En jours. Évite les réservations posées deux ans à l’avance.',
	'champ_video'              => 'Vidéo',
	'video_aide'               => 'Collez l’adresse de la page de la vidéo, telle qu’elle apparaît dans votre navigateur. YouTube, Vimeo, Dailymotion et PeerTube sont reconnus.',
	'erreur_video'             => 'Adresse non reconnue. Collez l’adresse complète d’une vidéo YouTube, Vimeo, Dailymotion ou PeerTube.',
	'voir_la_video'            => 'Voir la vidéo',
	'video_avertissement'      => 'La lecture est hébergée par @plateforme@, qui déposera des traceurs sur votre appareil. Rien n’est chargé tant que vous ne cliquez pas.',
	'champ_descriptif'         => 'Description',
	'descriptif_aide'          => 'Ce qui est fourni : tables, chaises, cuisine, vaisselle, sonorisation…',
	'champ_statut'             => 'Ouverte à la réservation',
	'statut_prepa'             => 'Non, n’apparaît pas sur le site',
	'statut_publie'            => 'Oui, le formulaire est ouvert',
	'statut_aide'              => 'Ce qui n’est pas ouvert reste enregistré mais invisible : utile hors saison, ou pendant des travaux.',

	'erreur_nombre'            => 'Indiquez un nombre entier de jours.',
	'erreur_delais_croises'    => 'Le délai maximum doit être supérieur au délai minimum.',

	// --- Manifestations --------------------------------------------------------
	'titre_manifestations'  => 'Événements et inscriptions',
	'creer_manifestation'   => 'Créer un événement',
	'modifier_manifestation' => 'Modifier l’événement',
	'manifestation_enregistree' => 'L’événement a été enregistré.',
	'aucune_manifestation'  => 'Aucun événement. Créez-en un pour ouvrir les inscriptions.',
	'les_inscrits'          => 'Les inscrits',
	'inscrits'              => 'inscrits',
	'inscrite'              => 'Inscrit',
	'en_attente_courte'     => 'En attente',
	'sans_limite'           => 'sans limite',
	'col_manifestation'     => 'Événement',
	'col_quand'             => 'Quand',
	'col_places'            => 'Places',
	'col_inscriptions'      => 'Inscriptions',

	'legende_evenement'     => 'L’événement',
	'legende_places'        => 'Places et tarif',
	'legende_fenetre'       => 'Fenêtre d’inscription',

	'champ_lieu'                => 'Lieu',
	'lieu_aide'                 => 'Exemple : salle des fêtes, place de la mairie, départ devant l’école.',
	'champ_date_debut'          => 'Date et heure',
	'champ_date_fin'            => 'Fin',
	'date_fin_aide'             => 'Laissez vide pour un événement ponctuel.',
	'champ_places'              => 'Nombre de places',
	'champ_tarif'               => 'Tarif',

	// --- Comment nos objets s'appellent ---------------------------------------
	// SPIP s'en sert partout où il parle d'un objet sans savoir lequel : sa
	// barre d'administration, ses messages, ses formulaires génériques.
	'objet_salle'          => 'Ressource',
	'objets_salles'        => 'Ressources',
	'objet_reservation'    => 'Réservation',
	'objets_reservations'  => 'Réservations',
	'objet_manifestation'  => 'Événement',
	'objets_manifestations' => 'Événements',
	'objet_abonne'         => 'Abonné',
	'objets_abonnes'       => 'Abonnés',
	'objet_lettre'         => 'Lettre d’information',
	'objets_lettres'       => 'Lettres d’information',
	'objet_demarche'       => 'Démarche',
	'objets_demarches'     => 'Démarches',
	'objet_elu'            => 'Élu',
	'objets_elus'          => 'Élus',
	'objet_raccourci'      => 'Accès rapide',
	'objets_raccourcis'    => 'Accès rapides',
	'objet_association'    => 'Association',
	'objets_associations'  => 'Associations',
	'objet_lieu'           => 'Lieu',
	'objets_lieux'         => 'Lieux',

	// --- Lieux ----------------------------------------------------------------
	'titre_lieux'          => 'Lieux',
	'creer_lieu'           => 'Ajouter un lieu',
	'modifier_lieu'        => 'Modifier',
	'aucun_lieu'           => 'Aucun lieu enregistré.',
	'lieu_enregistre'      => 'Enregistré.',
	'lieu_enregistre_localise' => 'Enregistré. L’adresse a été localisée : le lieu apparaît sur la carte.',
	'point_enregistre' => 'Adresse localisée :',
	'outil_signaler' => 'Signaler une correction',
	'outil_imprimer' => 'Imprimer la page',
	'outil_partager' => 'Partager la page',
	'lien_copie' => 'Lien copié',
	'chercher_adresse' => 'Chercher cette adresse',
	'adresse_a_saisir' => 'Écrivez d’abord une adresse, puis cliquez sur Chercher.',
	'recherche_en_cours' => 'Recherche en cours…',
	'recherche_en_panne' => 'La recherche n’a pas abouti. Signalez ce message tel quel :',
	'aucune_proposition' => 'Aucune adresse trouvée. Essayez plus simple : le nom de la rue seul, par exemple « rue de l’Église ».',
	'choisir_proposition' => 'Cliquez sur la bonne adresse :',
	'point_retenu' => 'Adresse retenue :',
	'verifier_sur_la_carte' => 'vérifier sur la carte',
	'ville_aide' => 'Sert aussi à situer les adresses des lieux et des associations sur la carte. Sans elle, aucune adresse ne peut être localisée.',
	'lieu_enregistre_approche' => 'Enregistré. L’adresse exacte n’a pas été reconnue : la carte est centrée sur le village, le marqueur n’est pas sur le bâtiment. Pour un repère précis, essayez le nom de la rue, par exemple « rue de l’Église ».',
	'lieu_enregistre_non_localise' => 'Enregistré, mais l’adresse n’a pas pu être localisée : le lieu figure dans la liste, pas sur la carte. Précisez l’adresse, ou saisissez les coordonnées à la main.',
	'intro_lieux'          => 'Cet écran sert à UNE chose : la page « Où nous trouver » du site, qui affiche les bâtiments de la commune, leurs adresses, leurs horaires, et une carte. Il n’est pas nécessaire d’y saisir quoi que ce soit pour utiliser le reste du site.',
	'champ_nom_lieu'       => 'Nom du lieu',
	'champ_type_lieu'      => 'Nature',
	'type_lieu_aide'       => 'Elle détermine le symbole affiché à côté du nom.',
	'champ_adresse_lieu'   => 'Adresse',
	'champ_horaires_lieu'  => 'Horaires d’ouverture',
	'horaires_lieu_aide'   => 'Seulement si le lieu a des horaires. Une salle des fêtes n’en a pas, la mairie oui.',
	'champ_latitude'       => 'Latitude',
	'champ_longitude'      => 'Longitude',
	'coordonnees_aide'     => 'Laissez vide : elles sont cherchées automatiquement à partir de l’adresse, à l’enregistrement. Ne les remplissez que pour corriger un placement erroné, ce qui arrive pour un bâtiment sans numéro de rue.',
	'col_carte'            => 'Carte',
	'sur_la_carte'         => 'Oui',
	'sans_coordonnees'     => 'sans coordonnées',
	'erreur_coordonnee'    => 'Ce n’est pas un nombre. Attendu : 49.94123',
	'erreur_coordonnees_paire' => 'Latitude et longitude vont ensemble : indiquez les deux, ou aucune.',
	'erreur_coordonnees_hors_france' => 'Ces coordonnées tombent hors de France. Les deux nombres sont-ils intervertis ?',

	'type_mairie'          => 'Mairie',
	'type_salle'           => 'Salle',
	'type_ecole'           => 'École',
	'type_culte'           => 'Lieu de culte',
	'type_sport'           => 'Terrain, équipement sportif',
	'type_patrimoine'      => 'Patrimoine',
	'type_autre'           => 'Autre',

	'titre_ou_nous_trouver' => 'Où nous trouver',
	'lieux_intro'          => 'Les bâtiments de la commune et leurs horaires.',
	'aucun_lieu_publie'    => 'Les adresses sont en cours de saisie.',
	'voir_en_grand'        => 'Ouvrir la carte sur OpenStreetMap',

	// --- Associations ---------------------------------------------------------
	'titre_associations'   => 'Associations',
	'creer_association'    => 'Ajouter une association',
	'modifier_association' => 'Modifier',
	'aucune_association'   => 'Aucune association enregistrée.',
	'association_enregistree' => 'Enregistré.',
	'association_enregistree_localisee' => 'Enregistré. L’adresse a été localisée : la carte apparaît sur la fiche du site.',
	'association_enregistree_approchee' => 'Enregistré. L’adresse exacte n’a pas été reconnue : la carte est centrée sur le village, le marqueur n’est pas sur le bâtiment. Pour un repère précis, essayez le nom de la rue, par exemple « rue de l’Église ».',
	'association_enregistree_non_localisee' => 'Enregistré, mais l’adresse n’a pas pu être localisée : la fiche paraît sans carte. Vérifiez le nom de la rue et de la commune, ou écrivez seulement la rue et le code postal.',
	'intro_associations'   => 'L’annuaire de la vie associative. Ce que les habitants viennent y chercher n’est pas une présentation : c’est qui appeler. Le nom du responsable et son téléphone comptent plus que le reste.',
	'champ_nom_asso'       => 'Nom de l’association',
	'photo_asso'           => 'Photographie',
	'photo_asso_aide'      => 'Une photo de l’équipe, du local ou d’une manifestation. Facultative : sans elle, la fiche affiche une pastille aux couleurs de la commune, avec le symbole du thème.',
	'champ_theme'          => 'Thème',
	'theme_aide'           => 'Il sert à regrouper les associations sur la page. Choisissez le plus proche : mieux vaut « Culture et loisirs » que « Autre ».',
	'champ_activite'       => 'Activité',
	'activite_aide'        => 'Une ou deux phrases : ce qu’on y fait, pour qui. C’est ce qui s’affiche dans la liste.',
	'champ_president'      => 'Responsable',
	'president_aide'       => 'La personne à qui l’on s’adresse : présidente, président ou secrétaire. Avec son accord : ce nom sera public.',
	'champ_site'           => 'Site ou page internet',
	'champ_lieu_asso'      => 'Où se déroulent les activités',
	'lieu_asso_aide'       => 'Écrivez-le simplement : « salle des fêtes », « 12 rue du Moulin », « chez les membres ». L’adresse est localisée automatiquement à l’enregistrement, et un lien vers le plan apparaît sur la fiche. Les lieux déjà connus vous sont proposés dès les premières lettres.',
	'champ_horaires'       => 'Jours et horaires',
	'horaires_asso_aide'   => 'Par exemple : « Entraînements le mardi et le jeudi, de 18 h à 20 h, salle des fêtes ».',
	'champ_rubrique_asso'  => 'Rubrique où l’association publie',
	'rubrique_asso_aide'   => 'Sa rubrique est créée automatiquement à l’enregistrement, sous « Vie associative ». Elle reste invisible sur le site tant qu’aucun article n’y est publié. Vous pouvez en choisir une autre, ou aucune.',
	'aucune_rubrique'      => 'Aucune, cette association n’écrit pas d’articles',
	'actus_association'    => 'Les actualités de l’association',
	'col_theme'            => 'Thème',
	'col_responsable'      => 'Responsable',

	'theme_sport'          => 'Sport',
	'theme_culture'        => 'Culture et loisirs',
	'theme_enfance'        => 'Enfance et jeunesse',
	'theme_solidarite'     => 'Solidarité et entraide',
	'theme_patrimoine'     => 'Patrimoine et environnement',
	'theme_memoire'        => 'Mémoire et anciens combattants',
	'theme_culte'          => 'Vie religieuse',
	'theme_autre'          => 'Autres associations',

	'titre_vie_associative' => 'Vie associative',
	'assos_intro'          => 'Les associations de la commune, ce qu’elles proposent et à qui s’adresser. Pour ajouter la vôtre ou corriger une information, écrivez à la mairie.',
	'aucune_association_publiee' => 'L’annuaire est en cours de constitution.',
	'asso_responsable'     => 'Responsable',
	'asso_ou'              => 'Où',
	'asso_quand'           => 'Quand',
	'asso_site'            => 'Voir leur site',
	'retour_associations'  => 'Toutes les associations',
	'confirmer_suppression_association' => 'Supprimer cette fiche ? Sa rubrique et ses articles sont conservés.',
	'erreur_asso_existe' => 'Une association porte déjà ce nom dans l’annuaire. Pour corriger sa fiche, contactez la mairie.',
	'courriel_gerant_aide' => 'C’est à cette adresse que la mairie confirmera la parution et enverra l’accès pour tenir la fiche à jour.',
	'preinscription_objet' => 'Annuaire des associations : demande de @nom@',
	'preinscription_corps' => 'Une association demande à entrer dans l’annuaire du site. Sa fiche est enregistrée en attente, invisible du public.',
	'preinscription_consigne' => 'Pour la mettre en ligne : espace privé, Édition, Associations, ouvrez la fiche, relisez, choisissez « Publiée » et enregistrez. La personne recevra alors la confirmation et son accès. Pour refuser, supprimez la fiche.',
	'preinscription_recue' => 'Votre demande est transmise à la mairie. Elle relit chaque fiche avant sa mise en ligne : vous recevrez un courriel de confirmation à la parution.',
	'asso_publiee_objet' => 'Votre association est dans l’annuaire de Marly-Gomont',
	'asso_publiee_corps' => 'Bonjour. La fiche de @nom@ vient d’être publiée dans l’annuaire des associations du site de la commune :',
	'asso_publiee_acces' => 'Un accès vous a été créé pour écrire les actualités de votre association sur le site. Votre identifiant : @login@. Choisissez votre mot de passe ici :',
	'asso_publiee_role' => 'Vos articles sont relus par la mairie avant leur mise en ligne, comme pour tout ce qui paraît sur le site de la commune.',
	'asso_publiee_maj' => 'Pensez à signaler à la mairie tout changement : contact, horaires, activités. Une fiche à jour, c’est une association que l’on trouve.',
	'signalement_titre' => 'Proposer une association',
	'signalement_intro' => 'Remplissez la fiche de votre association : la mairie la relit avant sa mise en ligne, puis vous recevez un accès pour la tenir à jour et publier vos actualités.',
	'signalement_objet' => 'Annuaire des associations : @nom@',
	
	'signalement_envoyer' => 'Envoyer la demande',
	'signalement_message_aide' => 'En quelques mots : ce que fait l’association, quand et où.',
	'champ_nom_association' => 'Nom de l’association',
	'erreur_telephone' => 'Ce numéro de téléphone semble incomplet.',
	'erreur_envoi_signalement' => 'L’envoi n’a pas abouti. Réessayez, ou téléphonez à la mairie.',
	'fermer_signalement' => 'Fermer la fenêtre',
	'asso_absente' => 'Votre association ne figure pas dans l’annuaire ?',
	'asso_absente_lien' => 'Proposez-la à la mairie',
	'asso_absente_objet' => 'Notre association dans l’annuaire du site',
	'creer_la_sienne'      => 'Créer une association',

	// --- Accès rapides --------------------------------------------------------
	'titre_raccourcis'     => 'Accès rapides',
	'creer_raccourci'      => 'Ajouter un accès rapide',
	'modifier_raccourci'   => 'Modifier',
	'aucun_raccourci'      => 'Aucun accès rapide. La page d’accueil affiche alors les rubriques du site.',
	'raccourci_enregistre' => 'Enregistré.',
	'intro_raccourcis'     => 'Les six ronds de la page d’accueil, sous la bannière. Au-delà de six, les suivants ne s’affichent pas : une liste de raccourcis qui s’allonge cesse d’être un raccourci. Tant que rien n’est saisi ici, la page d’accueil propose les rubriques du site.',
	'raccourci_titre_aide' => 'Court : il s’affiche sous un rond. « Comptes rendus », « Urbanisme ». Trente caractères au plus.',
	'champ_position'       => 'Place dans la bande',
	'position_aide'        => 'De gauche à droite sur la page d’accueil. Les places proposées correspondent au nombre d’accès rapides existants.',
	'position_gauche'      => 'tout à gauche',
	'position_droite'      => 'tout à droite',
	'champ_destination'    => 'Vers quoi',
	'destination_aide'     => 'Tout ce que le site contient vous est proposé. Vous n’avez à taper une adresse que pour un site extérieur.',
	'choisir_destination'  => 'Choisissez une destination…',
	'cible_demarche'       => 'Démarche',
	'cible_rubrique'       => 'Rubrique',
	'cible_page'           => 'Page',
	'cible_url'            => 'Une autre adresse (site extérieur)',
	'champ_url_exterieure' => 'Adresse du site extérieur',
	'url_exterieure_aide'  => 'À remplir seulement si vous avez choisi « une autre adresse » ci-dessus.',
	'destination_perdue'   => 'destination supprimée, ce raccourci ne s’affiche pas',

	// --- Élus -----------------------------------------------------------------
	'titre_elus'           => 'Élus',
	'creer_elu'            => 'Ajouter un élu',
	'modifier_elu'         => 'Modifier',
	'aucun_elu'            => 'Aucun élu enregistré.',
	'elu_enregistre'       => 'Enregistré.',
	'intro_elus'           => 'Saisis une fois, les élus sont proposés ensuite sur chaque fiche démarche. Le jour où une délégation change, il n’y a qu’un seul endroit à corriger, et non six fiches à retrouver.',
	'champ_nom_elu'        => 'Nom',
	'champ_telephone'      => 'Téléphone',
	'champ_courriel'       => 'Courriel',
	'champ_prenom'         => 'Prénom',
	'champ_fonction'       => 'Fonction',
	'fonction_aide'        => 'Maire, première adjointe, conseiller municipal délégué…',
	'champ_delegation'     => 'Délégation',
	'delegation_aide'      => 'Ce dont cette personne a la charge : état civil, urbanisme, associations. C’est ce que les habitants lisent pour savoir à qui s’adresser.',
	'champ_permanence'     => 'Permanence',
	'photo_elu'            => 'Photographie',
	'photo_elu_aide'       => 'Un portrait, de préférence cadré serré et pris de face. Il s’affiche en petit, à côté du nom : une photo de groupe ou un paysage y seraient illisibles.',
	'permanence_aide'      => 'Quand et comment on peut la rencontrer. Par exemple : « Reçoit sur rendez-vous, le samedi matin ».',
	'col_delegation'       => 'Délégation',
	'aucun_elu_choisi'     => 'Aucun',

	// --- Démarches : référent, contact, encadrés ------------------------------
	'champ_elu'            => 'Élu référent',
	'elu_aide'             => 'La personne responsable de cette démarche. Elle se saisit dans « Élus », et se choisit ici.',
	'champ_contact_tel'    => 'Téléphone pour cette démarche',
	'contact_aide'         => 'Laissez vide si c’est le numéro habituel de la mairie : la fiche l’affichera de lui-même. Ne remplissez que l’exception.',
	'champ_contact_courriel' => 'Courriel pour cette démarche',
	'champ_a_savoir'       => 'À savoir',
	'a_savoir_aide'        => 'Un encadré permanent, pour ce qui protège l’habitant : « cette démarche est gratuite, méfiez-vous des sites payants ».',
	'champ_alerte'         => 'Avertissement temporaire',
	'alerte_aide'          => 'Une information passagère : guichet fermé, service indisponible. Elle s’affiche en évidence.',
	'champ_alerte_fin'     => 'Jusqu’au',
	'alerte_fin_aide'      => 'L’avertissement disparaît tout seul après cette date. Sans date, il reste affiché, et un message qu’il faut penser à retirer n’est jamais retiré.',
	'a_savoir_titre'       => 'À savoir',
	'referent_titre'       => 'Élu référent',
	'contact_titre'        => 'Contact',
	'documents_titre'      => 'Documents à télécharger',

	// --- Démarches ------------------------------------------------------------
	'champ_intitule'       => 'Intitulé',
	'champ_publication'    => 'Publication',
	'publication_oui'      => 'Publiée, visible sur le site',
	'publication_non'      => 'Brouillon, invisible sur le site',
	'modifier'             => 'Modifier',
	'supprimer'            => 'Supprimer',
	'confirmer_suppression' => 'Supprimer cette fiche ? Elle ne sera pas remise en place à la prochaine mise à jour.',
	'titre_demarches'      => 'Démarches',
	'creer_demarche'       => 'Ajouter une démarche',
	'modifier_demarche'    => 'Modifier la démarche',
	'aucune_demarche'      => 'Aucune démarche enregistrée.',
	'demarche_enregistree' => 'Enregistré.',
	'intro_demarches'      => 'Les fiches que les habitants consultent. Celles marquées « socle » ont été fournies avec le site : elles existent dans toutes les communes de France. Vous pouvez les modifier, les dépublier ou les supprimer : elles ne reviendront pas.',
	'col_famille'          => 'Où ça se passe',
	'col_socle'            => 'Origine',
	'origine_socle'        => 'Socle',
	'origine_mairie'       => 'Mairie',

	'famille_mairie'       => 'À la mairie',
	'famille_enligne'      => 'En ligne',
	'famille_ailleurs'     => 'Ailleurs qu’à la mairie',
	'famille_mairie_court'  => 'À la mairie',
	'famille_enligne_court' => 'En ligne',
	'famille_ailleurs_court' => 'Ailleurs',

	'champ_famille'        => 'Où cette démarche se fait-elle ?',
	'famille_aide'         => '« Ailleurs » sert aux démarches que la mairie ne traite pas : carte grise, impôts. Le seul service à rendre est alors d’éviter un déplacement pour rien.',
	'champ_icone'          => 'Icône',
	'icone_aide'           => 'Choisissez la plus proche. Elle sert de repère visuel dans la liste, elle ne remplace pas le titre.',
	'champ_resume'         => 'En une phrase',
	'resume_aide'          => 'Ce que la personne doit retenir si elle ne lit rien d’autre. C’est cette phrase qui s’affiche dans la liste.',
	'champ_qui'            => 'Qui est concerné',
	'champ_comment'        => 'Comment faire',
	'champ_pieces'         => 'Pièces à fournir',
	'champ_cout'           => 'Coût',
	'cout_aide'            => 'Écrivez « Gratuit » plutôt que de laisser vide : un champ vide laisse croire qu’on ne sait pas.',
	'champ_delai'          => 'Délai',
	'champ_ou'             => 'Où s’adresser',
	'ou_aide'              => 'L’information que l’État ne peut pas donner à votre place : votre guichet, vos horaires, votre téléphone.',
	'champ_lien'           => 'Fiche officielle',
	'lien_aide'            => 'L’adresse de la fiche sur service-public.gouv.fr. C’est elle qui porte la réglementation à jour : le site n’a pas à la recopier.',
	'champ_lien_faire'     => 'Faire la démarche en ligne',
	'lien_faire_aide'      => 'Le téléservice, quand il existe : ANTS, impots.gouv.fr, cadastre. Laissez vide si la démarche ne se fait pas en ligne.',
	'champ_rang'           => 'Ordre d’affichage',
	'champ_ordre_elus'     => 'Rang',
	'ordre_elus_aide'      => 'Le maire en 1, les adjoints ensuite dans l’ordre de leur élection, les conseillers après. C’est le seul endroit du site où l’ordre a un sens : ailleurs, tout se classe par ordre alphabétique.',
	'rang_aide'            => 'Sert à ranger les fiches entre elles : la plus petite s’affiche en premier. Si vous n’y touchez pas, elles se classent par ordre alphabétique.',

	'demarche_socle_note'  => 'Cette fiche fait partie du socle fourni avec le site. Vous pouvez la modifier librement : vos modifications ne seront jamais écrasées.',
	'voir_fiche_officielle' => 'Consulter la fiche officielle',
	'faire_en_ligne'       => 'Faire la démarche en ligne',
	'toutes_les_demarches' => 'Toutes les démarches',
	'demarches_intro'      => 'Ce que vous pouvez faire, et où. Les fiches renvoient vers service-public.gouv.fr pour la réglementation, toujours à jour ; la mairie précise ici ce qui la concerne : son guichet, ses horaires, ses pièces.',
	'aucune_demarche_publiee' => 'Les fiches sont en cours de préparation.',
	'demarche_qui'         => 'Qui est concerné',
	'demarche_comment'     => 'Comment faire',
	'demarche_pieces'      => 'Pièces à fournir',
	'demarche_cout'        => 'Coût',
	'demarche_delai'       => 'Délai',
	'demarche_ou'          => 'Où s’adresser',
	'retour_demarches'     => 'Toutes les démarches',

	// --- Lettre : media et renvois vers le site -------------------------------
	'champ_video_lettre'   => 'Lien vers une vidéo',
	'video_lettre_aide'    => 'YouTube, Viméo, ou une vidéo déposée sur le site. Une vidéo ne se lit pas dans un courriel : le message affichera un bouton qui l’ouvre.',
	'champ_actus'          => 'Reprendre les dernières actualités du site',
	'actus_aide'           => 'Ajoute en fin de lettre les trois derniers articles publiés, avec leur date et un lien. Rien à choisir : ce sont ceux que vous venez de publier.',
	'places_aide'               => '0 pour ne pas limiter : une kermesse en plein air n’a pas de jauge.',
	'champ_logo'                => 'Photographie',
	'logo_aide'                 => 'Elle s’ajoute après l’enregistrement, dans la colonne de droite : « Ajouter un logo ». C’est elle qui s’affiche sur la carte du site.',
	'champ_places_par_personne' => 'Places maximum par inscription',
	'places_par_personne_aide'  => 'Évite qu’une personne réserve la moitié de la salle.',
	'champ_validation'          => 'Validation',
	'validation_auto'           => 'Automatique, l’inscription est confirmée aussitôt',
	'validation_mairie'         => 'Par la mairie, vous validez chaque inscription',
	'validation_aide'           => 'Automatique pour un repas ou une sortie ; par la mairie quand il faut arbitrer, comme les emplacements de brocante.',
	'champ_ouverture'           => 'Ouverture des inscriptions',
	'ouverture_aide'            => 'Laissez vide pour ouvrir tout de suite.',
	'champ_cloture'             => 'Clôture des inscriptions',
	'cloture_aide'              => 'Exemple : huit jours avant, le temps de commander les couverts.',

	'champ_places_demandees'    => 'Nombre de places',
	'precision'                 => 'Précision',
	'precision_aide'            => 'Régime alimentaire, personne à mobilité réduite, mètres d’emplacement souhaités…',
	'je_minscris'               => 'Je m’inscris',
	'obligatoire'               => '(obligatoire)',

	'erreur_robot'              => 'Votre envoi n’a pas pu être accepté. Si vous êtes bien une personne, réessayez, ou appelez la mairie : nous prendrons votre demande par téléphone.',
	'erreur_trop_vite'          => 'Le formulaire a été envoyé trop vite. Réessayez : le bouton fonctionne à nouveau.',
	'erreur_formulaire_expire'  => 'Cette page est restée ouverte trop longtemps. Rechargez-la et recommencez, vos informations n’ont pas été perdues.',
	'erreur_trop_de_demandes'   => 'Plusieurs demandes sont déjà en attente à cette adresse. Attendez notre réponse, ou appelez la mairie.',

	'rgpd_titre'                => 'Que devient ce que vous saisissez ?',
	'rgpd_texte'                => 'Les informations recueillies sont enregistrées par la mairie
		dans le seul but de traiter votre demande. Elles sont conservées trois ans, ne sont
		transmises à aucun tiers, et ne servent à aucun envoi commercial. Vous pouvez à tout
		moment demander à les consulter, les corriger ou les faire effacer en écrivant à la
		mairie.',
	'rgpd_lien'                 => 'Politique de confidentialité du site',
	'inscription_confirmee'     => 'Votre inscription est confirmée. Vous allez recevoir un courriel.',
	'places_restantes'          => 'Places restantes :',
	'complet'                   => 'Complet',
	'inscriptions_closes'       => 'Les inscriptions sont closes.',
	'inscriptions_a_venir'      => 'Les inscriptions ouvriront le',

	'erreur_places'             => 'Indiquez un nombre de places d’au moins 1.',
	'erreur_trop_de_places'     => 'Vous ne pouvez pas prendre plus de @n@ places en une fois.',
	'erreur_reste_seulement'    => 'Il ne reste que @n@ place(s).',
	'erreur_complet'            => 'Il ne reste plus de place.',
	'erreur_inscriptions_ferme' => 'Les inscriptions ne sont pas ouvertes.',
	'erreur_inscriptions_pas_encore' => 'Les inscriptions ne sont pas encore ouvertes.',
	'erreur_inscriptions_clos'  => 'Les inscriptions sont closes.',
	'erreur_inscriptions_passe' => 'Cet événement a déjà eu lieu.',
	'erreur_cloture_avant'      => 'La clôture doit venir après l’ouverture.',
	'erreur_cloture_apres_evenement' => 'La clôture doit venir avant l’événement.',

	'courriel_sujet_inscrit'    => '@site@ : votre inscription est confirmée',
	'courriel_corps_inscrit'    => 'Bonjour @nom@,

Votre inscription est confirmée :

  Événement : @salle@
  Date          : @date_debut@
  Places        : @places@

@site@',

	// --- Lettre d'information --------------------------------------------------
	'titre_abonnes'         => 'Abonnés à la lettre d’information',
	'titre_newsletter'      => 'Lettre d’information',
	'newsletter_intro'      => 'Travaux, coupures d’eau, alertes météo, conseil municipal, manifestations : recevez l’essentiel par courriel. Quelques envois par an, jamais plus.',
	'votre_prenom'          => 'Prénom',
	'je_mabonne'            => 'Je m’abonne',
	'consentement_newsletter' => 'J’accepte que la mairie utilise mon adresse pour m’envoyer sa lettre d’information.',
	'newsletter_note'       => 'Votre adresse ne sert qu’à cela. Elle n’est transmise à personne et vous pouvez vous désinscrire en un clic, depuis n’importe quel envoi.',
	'newsletter_verifiez'   => 'Presque fini. Ouvrez votre boîte : un courriel vous attend, avec un lien à cliquer pour confirmer. Sans ce clic, vous ne recevrez rien.',
	'erreur_consentement'   => 'Cochez la case pour que la mairie puisse vous écrire.',

	'newsletter_confirme'   => 'Votre abonnement est confirmé. Merci.',
	'newsletter_desinscrit' => 'Vous êtes désinscrit. Vous ne recevrez plus rien.',
	'newsletter_jeton_inconnu' => 'Ce lien n’est plus valable. Il a peut-être déjà servi, ou il a expiré.',

	'courriel_sujet_confirmation' => '@site@ : confirmez votre abonnement',
	'courriel_corps_confirmation' => 'Bonjour @nom@,

Quelqu’un, vous sans doute, a demandé à recevoir la lettre d’information
de @site@.

Pour confirmer, cliquez sur ce lien :

@lien@

Si vous n’êtes à l’origine de rien, ignorez ce message : sans ce clic, votre
adresse ne sera pas utilisée et sera effacée sous sept jours.

@site@',

	'col_courriel'          => 'Adresse',
	'col_inscrit_le'        => 'Inscrit le',
	'abonnes_confirmes'     => 'Abonnés confirmés',
	'abonnes_attente'       => 'En attente de confirmation',
	'aucun_abonne'          => 'Aucun abonné pour le moment.',
	'champ_ville'           => 'Commune',
	'attente_explication'   => 'Ces adresses n’ont pas encore été confirmées : elles ne recevront rien. Si cette liste s’allonge sans jamais se vider, c’est que le courriel de confirmation n’arrive pas : vérifiez les réglages d’envoi du serveur.',
	'exporter_csv'          => 'Exporter la liste (CSV)',

	'votre_nom_seul'        => 'Nom',
	'confirmez_courriel'    => 'Confirmez votre adresse électronique',
	'requis_explication'    => 'Les champs marqués « obligatoire » doivent être remplis.',
	'lieu_facultatif'       => 'Facultatif : si vous l’indiquez, la mairie saura distinguer les habitants de la commune des personnes des environs.',
	'me_desabonner'         => 'Me désabonner',
	'plutot_mabonner'       => 'Plutôt m’abonner',
	'desabonnement_intro'   => 'Indiquez l’adresse à retirer de la liste. Nous vous enverrons un lien de confirmation, ainsi personne ne peut désabonner quelqu’un d’autre.',
	'desabonnement_verifiez' => 'Si cette adresse figure sur la liste, un courriel vient de lui être envoyé avec un lien de désinscription.',
	'erreur_courriels_differents' => 'Les deux adresses ne correspondent pas. Vérifiez la seconde.',
	'erreur_code_postal'    => 'Un code postal s’écrit avec cinq chiffres.',

	'mention_titre'         => 'Que devient ce que vous saisissez ?',
	'mention_texte'         => 'La commune de Marly-Gomont est responsable de ce traitement. Votre adresse
		et vos coordonnées ne servent qu’à vous envoyer la lettre d’information, sur la base de votre
		consentement. Elles ne sont transmises à personne : ni association, ni prestataire, ni autre
		administration, et elles ne servent à aucun envoi commercial. Elles sont conservées jusqu’à votre
		désinscription, puis effacées.',
	'mention_droits'        => 'Vous pouvez à tout moment accéder à vos données, les corriger, les faire
		effacer, en demander la portabilité, ou retirer votre consentement : le lien de désinscription
		figure dans chaque envoi. Écrivez à la mairie pour exercer ces droits.',
	'mention_cnil'          => 'Si une réponse ne vous satisfait pas, vous pouvez saisir la CNIL, autorité
		de contrôle française.',

	'courriel_sujet_desinscription' => '@site@ : confirmez votre désinscription',
	'courriel_corps_desinscription' => 'Bonjour @nom@,

Une désinscription de la lettre d’information de @site@ a été demandée pour
cette adresse.

Pour la confirmer, cliquez sur ce lien :

@lien@

Si vous n’avez rien demandé, ignorez ce message : sans ce clic, vous
resterez abonné.

@site@',

	// --- Lettres envoyees ------------------------------------------------------
	'titre_lettres'         => 'Lettres d’information',
	'creer_lettre'          => 'Rédiger une lettre',
	'lettre_enregistree'    => 'La lettre a été enregistrée.',
	'aucune_lettre'         => 'Aucune lettre. Rédigez-en une pour l’envoyer aux abonnés.',
	'abonnes_actifs'        => 'Abonnés confirmés, qui recevront les envois :',
	'abonnes'               => 'abonnés',
	'abonnes_recevront'     => 'personnes recevront cette lettre',
	'voir_les_abonnes'      => 'Voir la liste',

	'champ_objet'           => 'Objet du courriel',
	'objet_aide'            => 'C’est ce que les gens liront dans leur boîte avant de décider d’ouvrir. Soyez concret : « Coupure d’eau jeudi 12 » vaut mieux que « Lettre d’information n° 3 ».',
	'champ_accroche'        => 'Accroche',
	'accroche_aide'         => 'Une ou deux phrases en tête de la lettre. Facultatif.',
	'champ_texte'           => 'Texte',
	'texte_aide'            => 'Les raccourcis de SPIP fonctionnent : {{gras}}, {italique}, [lien->https://…].',

	'col_envoyes'           => 'Envoyés',
	'erreurs'               => 'en échec',
	'etat_redaction'        => 'En rédaction',
	'etat_envoi'            => 'Envoi en cours',
	'etat_envoyee'          => 'Envoyée',
	'etat_arretee'          => 'Arrêtée',

	'avant_envoi'           => 'Avant d’envoyer',
	'avant_envoi_texte'     => 'Envoyez-vous la lettre à vous-même et relisez-la dans votre boîte. C’est le dernier moment où une coquille se rattrape : une fois partie, elle est partie.',
	'envoyer_essai'         => 'M’envoyer un essai',
	'pret_a_envoyer'        => 'Cette lettre partira à',
	'envoyer_maintenant'    => 'Envoyer maintenant',
	'confirmer_envoi'       => 'La lettre va partir à tous les abonnés. On ne peut pas la rattraper. Continuer ?',

	'envois_effectues'      => 'envois effectués',
	'envoi_en_cours'        => 'L’envoi se poursuit tout seul, par petits lots. Vous pouvez fermer cette page.',
	'envoi_arrete'          => 'Envoi arrêté. Les courriels déjà partis ne peuvent pas être rattrapés.',
	'envoi_termine'         => 'Envoi terminé le',
	'arreter_envoi'         => 'Arrêter l’envoi',
	'reprendre_envoi'       => 'Reprendre l’envoi',
	'apercu_lettre'         => 'Aperçu',

	'enregistrer'      => 'Enregistrer',
	'reglages_ok'      => 'Les réglages ont été enregistrés.',
	'erreur_adresse'   => 'L’adresse doit commencer par https:// Copiez-la depuis la barre du navigateur.',
	'erreur_courriel'  => 'Cette adresse électronique ne semble pas valide.',
	'erreur_un_contact' => 'Indiquez au moins un moyen de contact : téléphone, courriel ou site. C’est la seule chose qu’on vient chercher dans un annuaire.',
);
