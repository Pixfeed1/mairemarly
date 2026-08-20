<?php
/**
 * Fichier de langue du thème de Marly-Gomont.
 * ---------------------------------------------------------------------------
 * Tous les libellés d'interface vivent ici, et nulle part ailleurs. On les
 * appelle dans les squelettes par  <:marly:cle:>
 *
 * Intérêt concret : changer « Newsletter » en « Lettre d'information » se fait
 * ici, une fois, sans ouvrir un seul gabarit — et sans risquer d'en oublier un.
 *
 * Ce qui N'A PAS sa place ici : le contenu (articles, rubriques) qui vit en
 * base, et les paramètres du site (téléphone, adresse) qui vivent en
 * configuration.
 */

if (!defined('_ECRIRE_INC_VERSION')) {
	return;
}

$GLOBALS[$GLOBALS['idx_lang']] = array(

	// --- En-tête et navigation ---------------------------------------------
	'aller_contenu'        => 'Aller au contenu principal',
	'site_officiel'        => 'Site officiel de la commune',
	'menu_principal'       => 'Navigation principale',
	'menu'                 => 'Menu',
	'ouvrir_menu'          => 'Ouvrir le menu',
	'fermer_recherche'     => 'Fermer la recherche',
	'que'                  => 'Que',
	'cherchez_vous'        => 'cherchez-vous ?',
	'lancer_recherche'     => 'Lancer la recherche',
	'echap_ferme'          => 'Appuyez sur Échap pour fermer.',
	'newsletter'           => 'Newsletter',
	'lettre'               => 'Lettre',
	'dinformation'         => 'd’information',
	'retour_accueil'       => 'Retour à l’accueil',
	'derniere_maj'         => 'Dernière mise à jour :',
	'page_en_preparation'  => 'Cette page est en cours de rédaction. Elle engage la commune : elle sera publiée dès qu’elle aura été validée par la mairie.',
	'fermer_newsletter'    => 'Fermer l’inscription à la lettre d’information',
	'newsletter_intro'     => 'Travaux, coupures d’eau, alertes météo, conseil municipal, manifestations : recevez l’essentiel par courriel. Quelques envois par an, jamais plus.',
	'contacter_mairie'     => 'Contacter la mairie',
	'reserver'             => 'Réserver',
	'reserver_salle'       => 'Réserver une salle',
	'rechercher_evenement' => 'Rechercher',
	'filtrer_par_type'     => 'Filtrer par type',
	'tout_voir'            => 'Tout voir',
	'les_evenements'       => 'Les événements',
	'les_salles'           => 'Les salles',
	'mois_precedent'       => 'Mois précédent',
	'mois_suivant'         => 'Mois suivant',
	'filtre_du_jour'       => 'Filtré sur le',
	'retirer_le_filtre'    => 'retirer',
	'a_un_evenement'       => 'à un événement',
	'places_libres'        => 'places libres',
	'location'             => 'Location',
	'rien_a_reserver'      => 'Rien à réserver pour le moment. Revenez bientôt, ou contactez la mairie.',
	'retour_catalogue'     => '← Tout ce qu’on peut réserver',
	'tarif'                => 'Tarif',
	'voir_la_video'        => 'Voir la vidéo',
	'video_avertissement'  => 'La lecture est hébergée par @plateforme@, qui déposera des traceurs sur votre appareil. Rien n’est chargé tant que vous ne cliquez pas.',
	'cloture_le'           => 'Inscriptions jusqu’au',
	'places_restantes_sur' => 'places restantes sur',
	'une_salle'            => 'une salle',
	'sinscrire'            => 'S’inscrire',
	'a_une_sortie'         => 'à un événement',
	'places_restantes'     => 'Places restantes :',
	'sur'                  => 'sur',
	'personnes'            => 'personnes assises',
	'dates_prises'         => 'Dates déjà retenues',
	'aucune_date_prise'    => 'Aucune date n’est retenue pour le moment.',
	'aucune_salle_publique' => 'Les réservations ne sont pas ouvertes en ligne pour le moment. Contactez la mairie.',
	'champ_capacite'           => 'Capacité',
	'champ_tarif_commune'      => 'Tarif habitants de la commune',
	'champ_tarif_hors_commune' => 'Tarif hors commune',
	'champ_caution'            => 'Caution',
	'connexion'            => 'Connexion',
	'espace_citoyen'       => 'Espace citoyen',
	'fermer_menu'          => 'Fermer le menu',
	'rechercher'           => 'Rechercher',
	'rechercher_site'      => 'Rechercher sur le site',
	'rechercher_placeholder' => 'Une démarche, un compte rendu, un horaire…',
	'recherche_amorce'     => 'Qu’est-ce que vous recherchez ?',

	// --- Bande de raccourcis -----------------------------------------------
	'acces'                => 'Accès',
	'rapides'              => 'rapides',
	'choisir_demarche'     => 'Choisir une démarche',
	'valider'              => 'Valider',
	'toutes_demarches'     => 'Voir toutes les démarches',
	'comptes_rendus'       => 'Comptes rendus',
	'urbanisme'            => 'Urbanisme',
	'bulletin'             => 'La Voix du Village',

	// --- Accueil ------------------------------------------------------------
	'je'                   => 'Je',
	'souhaite'             => 'souhaite…',
	'les'                  => 'Les',
	'actus'                => 'actus',
	'evenements'           => 'événements',
	'le'                   => 'Le',
	'conseil_municipal'    => 'conseil municipal',
	'nos'                  => 'Nos',
	'associations'         => 'associations',
	'a'                    => 'À',
	'lire'                 => 'lire',

	'toutes_actualites'    => 'Toutes les actualités',
	'tout_agenda'          => 'Tout l’agenda',
	'toutes_associations'  => 'Toutes les associations',

	// --- Conseil municipal --------------------------------------------------
	'pv_intro'             => 'Tous les procès-verbaux, consultables en ligne et cherchables par mot-clé.',
	'pv_placeholder'       => 'Ex. : voirie, budget, école…',
	'pv_chercher'          => 'Chercher',

	// --- Agenda -------------------------------------------------------------
	'filtre_mois'          => 'Ce mois-ci',
	'filtre_semaine'       => 'Cette semaine',
	'filtre_weekend'       => 'Ce week-end',

	// --- Lettre d'information ----------------------------------------------
	'restez_informe'       => 'Restez informé',
	'restez_informe_texte' => 'Travaux, coupures d’eau, alertes météo, événements : recevez l’essentiel par courriel.',
	'votre_courriel'       => 'Votre adresse électronique',
	'je_minscris'          => 'Je m’inscris',

	// --- Pied de page -------------------------------------------------------
	'le_site'              => 'Le site',
	'demarches'            => 'Démarches',
	'mentions_legales'     => 'Mentions légales',
	'confidentialite'      => 'Politique de confidentialité',
	'accessibilite'        => 'Accessibilité',
	'plan_du_site'         => 'Plan du site',

	// --- Articles et rubriques ---------------------------------------------
	'publie_le'            => 'Publié le',
	'mis_a_jour_le'        => 'Mis à jour le',
	'par_auteur'           => 'par',
	'documents_joints'     => 'Documents à télécharger',
	'dans_la_rubrique'     => 'Dans la même rubrique',
	'aucun_article'        => 'Aucun article dans cette rubrique pour le moment.',
	'aucun_resultat'       => 'Aucun résultat ne correspond à votre recherche.',
);
