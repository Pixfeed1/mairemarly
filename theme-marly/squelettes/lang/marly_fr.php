<?php
/**
 * Fichier de langue du thème de Marly-Gomont.
 * ---------------------------------------------------------------------------
 * Tous les libellés d'interface vivent ici, et nulle part ailleurs. On les
 * appelle dans les squelettes par  <:marly:cle:>
 *
 * Intérêt concret : changer « Newsletter » en « Lettre d'information » se fait
 * ici, une fois, sans ouvrir un seul gabarit, et sans risquer d'en oublier un.
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
	'publie_le_min' => 'publié le',
	'par' => 'par',
	'en_images' => 'En images',
	'voir_la_photo' => 'Voir la photo en grand',
	'telecharger' => 'Télécharger',
	'actus_liees' => 'Les actualités liées',
	'toutes_actus_rubrique' => 'Toutes les actualités de la rubrique',
	'article_precedent' => 'Article précédent',
	'article_suivant' => 'Article suivant',
	'lettre_invite' => 'Recevez les actualités du village par courriel, quelques lettres par an, rien de plus.',
	'lettre_sinscrire' => 'S’inscrire à la lettre',
	'dans_cette_rubrique' => 'Dans cette rubrique',
	'dans_cet_article' => 'Dans cet article',
	'accueil'              => 'Accueil',
	'attention'            => 'Attention :',
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
	'document_joint'       => 'Document à télécharger',
	'documents_joints'     => 'Documents à télécharger',
	'dans_la_rubrique'     => 'Dans la même rubrique',
	'aucun_article'        => 'Aucun article dans cette rubrique pour le moment.',
	'aucun_resultat'       => 'Aucun résultat ne correspond à votre recherche.',
	'un_article'           => 'un article',
	'des_articles'         => '@nb@ articles',

	// --- Plan du site -------------------------------------------------------
	'plan_intro'           => 'Toutes les pages du site de Marly-Gomont, rassemblées ici.',
	'plan_rubriques'       => 'Les rubriques',
	'plan_services'        => 'Services',
	'plan_demarches_quoi'  => 'toutes les fiches, classées selon l’endroit où la démarche se fait',
	'plan_annuaire'        => 'Annuaire des associations',
	// --- Le conseil municipal -------------------------------------------------
	'ma_mairie'            => 'Ma mairie',
	'titre_conseil'        => 'Le conseil municipal',
	'conseil_intro'        => 'Les élus à qui vous avez confié la commune. Ils votent le budget, décident des travaux, gèrent l’école et les salles. Voici qui fait quoi, et comment les joindre.',
	'conseil_maire'        => 'Le maire',
	'conseil_adjoints'     => 'Les adjoints',
	'conseil_conseillers'  => 'Les conseillers',
	'conseil_vide'         => 'La liste des élus n’est pas encore en ligne.',
	'conseil_seances'      => 'Les séances sont publiques',
	'conseil_seances_texte' => 'N’importe quel habitant peut assister à une séance du conseil municipal, s’asseoir au fond de la salle et écouter. Il n’y a rien à demander.',
	'conseil_prochaine'    => 'Prochaine séance :',
	'conseil_comptes_rendus' => 'Les comptes rendus des séances',
	'conseil_ecrire'       => 'Écrire à la mairie',
	'retour_conseil'       => 'Retour au conseil municipal',
	'elu_parcours'         => 'Son parcours',
	'fiche_maj'            => 'Fiche mise à jour le',

	// Le rôle du maire : la même information pour les 34 000 communes de
	// France. Elle vient du code général des collectivités territoriales, pas
	// de Marly-Gomont, et n'a donc rien à faire dans la base — la faire saisir
	// par la mairie reviendrait à lui demander de recopier la loi, sans qu'elle
	// ait le moyen de vérifier qu'elle l'a bien recopiée.
	'role_maire'           => 'Le rôle du maire',
	'role_maire_intro'     => 'Le maire est à la fois l’élu de la commune et le représentant de l’État. Ces deux casquettes expliquent l’essentiel de ce qu’il fait.',
	'role_etat'            => 'Au nom de l’État',
	'role_etat_texte'      => 'Il est officier d’état civil : c’est lui, ou un adjoint, qui célèbre les mariages et signe les actes de naissance et de décès. Il organise les élections et tient le recensement citoyen.',
	'role_commune'         => 'Au nom de la commune',
	'role_commune_texte'   => 'Il prépare et exécute les décisions du conseil municipal : il engage les dépenses votées, signe les contrats, et représente la commune en justice comme dans les cérémonies.',
	'role_seul'            => 'Seul, parfois',
	'role_seul_texte'      => 'Dans certains domaines il décide sans le conseil : il prend les arrêtés municipaux, dirige le personnel communal et exerce les pouvoirs de police du maire, qui vont de la circulation aux animaux errants.',

	'plan_conseil_quoi'    => 'le maire, les adjoints et leurs délégations',

	'plan_commerces_quoi'  => 'les commerçants, professionnels de santé et artisans du village',
	'plan_annuaire_quoi'   => 'les associations du village, avec leurs contacts',
	'plan_reserver'        => 'Réserver une salle',
	'plan_reserver_quoi'   => 'la salle des fêtes et le matériel de la commune',
	'plan_lieux'           => 'Les lieux de la commune',
	'plan_lieux_quoi'      => 'la mairie, l’école, l’église et le stade sur la carte',
	'plan_lettre'          => 'La lettre d’information',
	'plan_lettre_quoi'     => 'recevoir les actualités de la commune par courriel',
	'plan_citoyen_quoi'    => 'suivre ses demandes et ses réservations',
	'plan_recherche_quoi'  => 'retrouver un article, une démarche, une association',
	'plan_mentions_quoi'   => 'éditeur, hébergeur et crédits',
	'plan_confidentialite_quoi' => 'ce que le site enregistre, et vos droits',
	'plan_accessibilite_quoi'   => 'niveau de conformité et contact',
	'plan_ici_quoi'        => 'la page que vous lisez',

	// --- Urbanisme et travaux -----------------------------------------------
	'titre_urbanisme'      => 'Urbanisme et travaux',
	'urba_intro'           => 'Un abri de jardin, des fenêtres neuves, une clôture, une extension : la plupart des travaux se déclarent en mairie, même dans un village. Ce qu’il faut déposer dépend d’abord de la surface.',
	'urba_titre_cas'       => 'Ce que demande votre projet',

	'urba_z1_seuil'        => 'jusqu’à 5 m²',
	'urba_z1_quoi'         => 'Rien à déposer',
	'urba_z1_temps'        => 'vous pouvez commencer',
	'urba_z1_a'            => 'Un <b>petit abri</b>, un coffre, une niche',
	'urba_z1_b'            => 'Une <b>piscine de 10 m² au plus</b>, non couverte',
	'urba_z1_c'            => 'L’entretien <b>à l’identique</b> : mêmes tuiles, même teinte',

	'urba_z2_seuil'        => 'de 5 à 20 m²',
	'urba_z2_quoi'         => 'Déclaration préalable',
	'urba_z2_temps'        => 'réponse sous 1 mois',
	'urba_z2_a'            => 'Un <b>abri</b>, un carport, un garage, une véranda',
	'urba_z2_b'            => 'Une <b>extension</b> de la maison',
	'urba_z2_c'            => 'Une <b>piscine de 10 à 100 m²</b>, non couverte',
	'urba_z2_d'            => '<b>Diviser un terrain</b> en vue de construire',

	'urba_z3_seuil'        => 'au-delà de 20 m²',
	'urba_z3_quoi'         => 'Permis de construire',
	'urba_z3_temps'        => 'réponse sous 2 à 3 mois',
	'urba_z3_a'            => 'Une <b>maison neuve</b>, quelle que soit sa surface',
	'urba_z3_b'            => 'Toute <b>construction ou extension</b> plus grande',
	'urba_z3_c'            => 'Une piscine de <b>plus de 100 m²</b>, ou couverte de plus de 1,80 m',
	'urba_z3_d'            => 'Transformer une <b>grange en logement</b> si la structure ou les façades changent',

	'urba_hors'            => '<b>Quelle que soit la surface</b>, changer les fenêtres, une porte, la toiture ou la teinte d’une façade demande une déclaration préalable : ce qui compte alors n’est pas ce que vous ajoutez, c’est l’aspect vu depuis la rue.',
	'urba_note'            => 'Les surfaces se comptent en emprise au sol ou en surface de plancher, la plus grande des deux valant seuil. Un doute sur un cas précis se règle par un appel à la mairie : c’est plus rapide que de chercher, et ça évite d’avoir à refaire un dossier. Avant d’acheter un terrain, demandez un <b>certificat d’urbanisme</b> : il dit ce qu’on peut y construire, fige les règles dix-huit mois, et il est gratuit.',

	'urba_titre_depot'     => 'Où déposer le dossier',
	'urba_en_mairie'       => 'En mairie',
	'urba_precision_mairie' => 'Deux exemplaires du dossier, plus un par service à consulter. La mairie vous remet un récépissé daté : ce papier fait courir le délai, gardez-le.',
	'urba_par_courriel'    => 'Par voie électronique',
	'urba_precision_courriel' => 'Depuis 2022, toute commune doit accepter un dossier envoyé par voie électronique. Le récépissé vous revient par le même chemin.',

	'urba_titre_ensuite'   => 'Ce qui se passe ensuite',
	'urba_etape_1'         => '<b>Le récépissé.</b> Remis au dépôt, il porte la date à partir de laquelle le délai court, et le numéro de votre dossier.',
	'urba_etape_2'         => '<b>L’instruction.</b> Un mois pour une déclaration préalable, deux mois pour un permis de maison individuelle, trois mois pour les autres. Un dossier incomplet fait repartir le délai à la réception des pièces manquantes.',
	'urba_etape_3'         => '<b>La décision.</b> Sans réponse au terme du délai, l’autorisation est acquise. Demandez-en alors l’attestation à la mairie : elle vous sera réclamée par votre assureur et par le notaire.',
	'urba_etape_4'         => '<b>L’affichage.</b> Dès l’accord, posez le panneau réglementaire sur le terrain, visible depuis la rue, et laissez-le pendant toute la durée du chantier. Les voisins ont deux mois pour contester à compter de cet affichage : sans panneau, ce délai ne commence jamais à courir.',

	'urba_titre_local'     => 'Ce qui vaut à Marly-Gomont',
	'urba_document'        => 'Le document d’urbanisme',
	'urba_decide'          => 'Qui délivre les autorisations',
	'urba_clotures'        => 'Les clôtures',

	'urba_lien_cadastre'   => 'Consulter le cadastre',
	'urba_lien_formulaires' => 'Les formulaires à remplir',
	'urba_lien_ecrire'     => 'Écrire à la mairie',

);
