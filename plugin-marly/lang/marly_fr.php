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
	'horaires_aide'    => 'Une ligne par jour ou par plage. Exemple : Lundi et jeudi, 14 h – 17 h',

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
	'titre_reservations'   => 'Réservations de salles',
	'en_attente'           => 'Demandes en attente',
	'acceptees'            => 'Réservations acceptées à venir',
	'aucune_demande'       => 'Aucune demande en attente.',
	'aucune_acceptee'      => 'Aucune réservation acceptée à venir.',

	'col_creneau'          => 'Créneau',
	'col_salle'            => 'Salle',
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
	'courriel_sujet_demande'   => '@site@ — votre demande de réservation',
	'courriel_corps_demande'   => 'Bonjour @nom@,

Nous avons bien reçu votre demande de réservation :

  Salle   : @salle@
  Du      : @date_debut@
  Au      : @date_fin@

Cette demande n’est pas encore une réservation. La mairie l’examinera et
vous répondra par courriel.

@site@',

	'courriel_sujet_mairie'    => '@site@ — nouvelle demande de réservation',

	'courriel_sujet_acceptee'  => '@site@ — votre réservation est confirmée',
	'courriel_corps_acceptee'  => 'Bonjour @nom@,

Votre réservation est confirmée :

  Salle   : @salle@
  Du      : @date_debut@
  Au      : @date_fin@

@reponse@

@site@',

	'courriel_sujet_refusee'   => '@site@ — votre demande n’a pas pu être retenue',
	'courriel_corps_refusee'   => 'Bonjour @nom@,

Votre demande de réservation pour @salle@, le @date_debut@, n’a pas pu être
retenue.

@reponse@

N’hésitez pas à contacter la mairie pour envisager une autre date.

@site@',

	'courriel_sujet_annulee'   => '@site@ — votre réservation a été annulée',
	'courriel_corps_annulee'   => 'Bonjour @nom@,

Votre réservation de @salle@ le @date_debut@ a été annulée.

@reponse@

@site@',

	// --- Salles ---------------------------------------------------------------
	'titre_salles'         => 'Salles à louer',
	'creer_salle'          => 'Créer une salle',
	'modifier_salle'       => 'Modifier la salle',
	'aucune_salle'         => 'Aucune salle n’est enregistrée. Créez-en une pour ouvrir les réservations.',
	'salle_enregistree'    => 'La salle a été enregistrée.',
	'col_delais'           => 'Délais',
	'col_etat'             => 'État',
	'jours'                => 'jours',

	'champ_titre'              => 'Nom de la salle',
	'champ_capacite'           => 'Capacité (personnes assises)',
	'capacite_aide'            => 'Le nombre que la commission de sécurité autorise, pas le nombre de chaises.',
	'champ_tarif_commune'      => 'Tarif habitants de la commune',
	'tarif_aide'               => 'Texte libre : « 80 € la journée », « gratuit pour les associations ».',
	'champ_tarif_hors_commune' => 'Tarif hors commune',
	'champ_caution'            => 'Caution',
	'champ_delai_min'          => 'Délai minimum',
	'delai_min_aide'           => 'En jours. Une demande faite plus tard est refusée automatiquement.',
	'champ_delai_max'          => 'Délai maximum',
	'delai_max_aide'           => 'En jours. Évite les réservations posées deux ans à l’avance.',
	'champ_descriptif'         => 'Description',
	'descriptif_aide'          => 'Ce qui est fourni : tables, chaises, cuisine, vaisselle, sonorisation…',
	'champ_statut'             => 'Ouverte à la réservation',
	'statut_prepa'             => 'Non — la salle n’apparaît pas sur le site',
	'statut_publie'            => 'Oui — le formulaire est ouvert',
	'statut_aide'              => 'Une salle non ouverte reste enregistrée mais invisible : utile hors saison, ou pendant des travaux.',

	'erreur_nombre'            => 'Indiquez un nombre entier de jours.',
	'erreur_delais_croises'    => 'Le délai maximum doit être supérieur au délai minimum.',

	// --- Manifestations --------------------------------------------------------
	'titre_manifestations'  => 'Manifestations et inscriptions',
	'creer_manifestation'   => 'Créer une manifestation',
	'modifier_manifestation' => 'Modifier la manifestation',
	'manifestation_enregistree' => 'La manifestation a été enregistrée.',
	'aucune_manifestation'  => 'Aucune manifestation. Créez-en une pour ouvrir les inscriptions.',
	'les_inscrits'          => 'Les inscrits',
	'inscrits'              => 'inscrits',
	'inscrite'              => 'Inscrit',
	'en_attente_courte'     => 'En attente',
	'sans_limite'           => 'sans limite',
	'col_manifestation'     => 'Manifestation',
	'col_quand'             => 'Quand',
	'col_places'            => 'Places',
	'col_inscriptions'      => 'Inscriptions',

	'legende_evenement'     => 'La manifestation',
	'legende_places'        => 'Places et tarif',
	'legende_fenetre'       => 'Fenêtre d’inscription',

	'champ_lieu'                => 'Lieu',
	'lieu_aide'                 => 'Exemple : salle des fêtes, place de la mairie, départ devant l’école.',
	'champ_date_debut'          => 'Date et heure',
	'champ_date_fin'            => 'Fin',
	'date_fin_aide'             => 'Laissez vide pour une manifestation ponctuelle.',
	'champ_places'              => 'Nombre de places',
	'places_aide'               => '0 pour ne pas limiter — une kermesse en plein air n’a pas de jauge.',
	'champ_places_par_personne' => 'Places maximum par inscription',
	'places_par_personne_aide'  => 'Évite qu’une personne réserve la moitié de la salle.',
	'champ_validation'          => 'Validation',
	'validation_auto'           => 'Automatique — l’inscription est confirmée aussitôt',
	'validation_mairie'         => 'Par la mairie — vous validez chaque inscription',
	'validation_aide'           => 'Automatique pour un repas ou une sortie ; par la mairie quand il faut arbitrer, comme les emplacements de brocante.',
	'champ_ouverture'           => 'Ouverture des inscriptions',
	'ouverture_aide'            => 'Laissez vide pour ouvrir tout de suite.',
	'champ_cloture'             => 'Clôture des inscriptions',
	'cloture_aide'              => 'Exemple : huit jours avant, le temps de commander les couverts.',

	'champ_places_demandees'    => 'Nombre de places',
	'precision'                 => 'Précision',
	'precision_aide'            => 'Régime alimentaire, personne à mobilité réduite, mètres d’emplacement souhaités…',
	'je_minscris'               => 'Je m’inscris',
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
	'erreur_inscriptions_passe' => 'Cette manifestation a déjà eu lieu.',
	'erreur_cloture_avant'      => 'La clôture doit venir après l’ouverture.',
	'erreur_cloture_apres_evenement' => 'La clôture doit venir avant la manifestation.',

	'courriel_sujet_inscrit'    => '@site@ — votre inscription est confirmée',
	'courriel_corps_inscrit'    => 'Bonjour @nom@,

Votre inscription est confirmée :

  Manifestation : @salle@
  Date          : @date_debut@
  Places        : @places@

@site@',

	'enregistrer'      => 'Enregistrer',
	'reglages_ok'      => 'Les réglages ont été enregistrés.',
	'erreur_adresse'   => 'L’adresse doit commencer par https:// — copiez-la depuis la barre du navigateur.',
	'erreur_courriel'  => 'Cette adresse électronique ne semble pas valide.',
);
