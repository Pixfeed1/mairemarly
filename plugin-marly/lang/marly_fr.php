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
	'aucune_ressource'     => 'Rien à réserver pour l’instant. Ajoutez une salle, un terrain, du matériel — tout ce qui se prête ou se loue.',
	'ressource_enregistree' => 'Enregistré.',
	'col_delais'           => 'Délais',
	'col_etat'             => 'État',
	'jours'                => 'jours',

	'champ_titre'              => 'Nom',
	'champ_capacite'           => 'Capacité (personnes assises)',
	'capacite_aide'            => 'Pour une salle, le nombre autorisé par la commission de sécurité. Laissez vide si la question ne se pose pas — du matériel n’a pas de capacité.',
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
	'statut_prepa'             => 'Non — n’apparaît pas sur le site',
	'statut_publie'            => 'Oui — le formulaire est ouvert',
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
	'places_aide'               => '0 pour ne pas limiter — une kermesse en plein air n’a pas de jauge.',
	'champ_logo'                => 'Photographie',
	'logo_aide'                 => 'Elle s’ajoute après l’enregistrement, dans la colonne de droite : « Ajouter un logo ». C’est elle qui s’affiche sur la carte du site.',
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
	'obligatoire'               => '(obligatoire)',

	'erreur_robot'              => 'Votre envoi n’a pas pu être accepté. Si vous êtes bien une personne, réessayez, ou appelez la mairie — nous prendrons votre demande par téléphone.',
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

	'courriel_sujet_inscrit'    => '@site@ — votre inscription est confirmée',
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

	'courriel_sujet_confirmation' => '@site@ — confirmez votre abonnement',
	'courriel_corps_confirmation' => 'Bonjour @nom@,

Quelqu’un — vous, sans doute — a demandé à recevoir la lettre d’information
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
	'attente_explication'   => 'Ces adresses n’ont pas encore été confirmées : elles ne recevront rien. Si cette liste s’allonge sans jamais se vider, c’est que le courriel de confirmation n’arrive pas — vérifiez les réglages d’envoi du serveur.',
	'exporter_csv'          => 'Exporter la liste (CSV)',

	'votre_nom_seul'        => 'Nom',
	'confirmez_courriel'    => 'Confirmez votre adresse électronique',
	'code_postal'           => 'Code postal',
	'ville'                 => 'Commune',
	'requis_explication'    => 'Les champs marqués « obligatoire » doivent être remplis.',
	'lieu_facultatif'       => 'Facultatif : si vous l’indiquez, la mairie saura distinguer les habitants de la commune des personnes des environs.',
	'me_desabonner'         => 'Me désabonner',
	'plutot_mabonner'       => 'Plutôt m’abonner',
	'desabonnement_intro'   => 'Indiquez l’adresse à retirer de la liste. Nous vous enverrons un lien de confirmation — ainsi personne ne peut désabonner quelqu’un d’autre.',
	'desabonnement_verifiez' => 'Si cette adresse figure sur la liste, un courriel vient de lui être envoyé avec un lien de désinscription.',
	'erreur_courriels_differents' => 'Les deux adresses ne correspondent pas. Vérifiez la seconde.',
	'erreur_code_postal'    => 'Un code postal s’écrit avec cinq chiffres.',

	'mention_titre'         => 'Que devient ce que vous saisissez ?',
	'mention_texte'         => 'La commune de Marly-Gomont est responsable de ce traitement. Votre adresse
		et vos coordonnées ne servent qu’à vous envoyer la lettre d’information, sur la base de votre
		consentement. Elles ne sont transmises à personne — ni association, ni prestataire, ni autre
		administration — et ne servent à aucun envoi commercial. Elles sont conservées jusqu’à votre
		désinscription, puis effacées.',
	'mention_droits'        => 'Vous pouvez à tout moment accéder à vos données, les corriger, les faire
		effacer, en demander la portabilité, ou retirer votre consentement — le lien de désinscription
		figure dans chaque envoi. Écrivez à la mairie pour exercer ces droits.',
	'mention_cnil'          => 'Si une réponse ne vous satisfait pas, vous pouvez saisir la CNIL, autorité
		de contrôle française.',

	'courriel_sujet_desinscription' => '@site@ — confirmez votre désinscription',
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
	'erreur_adresse'   => 'L’adresse doit commencer par https:// — copiez-la depuis la barre du navigateur.',
	'erreur_courriel'  => 'Cette adresse électronique ne semble pas valide.',
);
