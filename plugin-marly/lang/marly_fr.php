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

	'enregistrer'      => 'Enregistrer',
	'reglages_ok'      => 'Les réglages ont été enregistrés.',
	'erreur_adresse'   => 'L’adresse doit commencer par https:// — copiez-la depuis la barre du navigateur.',
	'erreur_courriel'  => 'Cette adresse électronique ne semble pas valide.',
);
