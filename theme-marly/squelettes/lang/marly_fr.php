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

	'actualites'           => 'actualités',
	'agenda_de_la'         => 'L’agenda de la',
	'commune'              => 'commune',
	'les_derniers'         => 'Les derniers',
	'documents'            => 'documents',
	'voir_detail'          => 'Voir le détail',
	'aucune_actualite_publiee' => 'Aucune actualité publiée pour le moment.',

	'toutes_actualites'    => 'Toutes les actualités',
	'tout_agenda'          => 'Tout l’agenda',
	'tous_les_comptes_rendus' => 'Tous les comptes rendus',
	'toutes_associations'  => 'Toutes les associations',

	// --- Page « Toutes les actualités » -------------------------------------
	'toutes_les_actualites' => 'Toutes les actualités',
	'actualites_intro'     => 'Tout ce qui a été publié sur le site, de la plus récente à la plus ancienne : décisions du conseil, travaux, informations pratiques, vie de la commune.',
	'plan_actualites_quoi' => 'toutes les publications du site, classées par date',

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
	'confidentialite'      => 'Données personnelles',
	'accessibilite'        => 'Accessibilité',
	'credits'              => 'Crédits',
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
	'plan_mentions_quoi'   => 'éditeur, hébergeur et directeur de la publication',
	'plan_credits_quoi'    => 'polices, photographies et logiciels utilisés par le site',
	'plan_confidentialite_quoi' => 'ce que le site enregistre, qui y a accès, et vos droits',
	'plan_accessibilite_quoi'   => 'niveau de conformité et contact',
	'plan_ici_quoi'        => 'la page que vous lisez',

	// --- Urbanisme et travaux -----------------------------------------------
	'titre_urbanisme'      => 'Urbanisme et travaux',
	'urba_intro'           => 'Un abri de jardin, des fenêtres neuves, une clôture, une extension : la plupart des travaux se déclarent en mairie avant d’être commencés. Ce qu’il faut déposer dépend d’abord de la surface.',
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

	'urba_titre_ensuite'   => 'Après le dépôt',

	'urba_recepisse'       => 'Le récépissé',
	'urba_recepisse_texte' => 'Il vous est remis au dépôt du dossier. Il porte son numéro et la date à partir de laquelle le délai court : gardez-le.',
	'urba_delais'          => 'Les délais d’instruction',
	'urba_delai_dp'        => 'Déclaration préalable',
	'urba_delai_dp_duree'  => '1 mois',
	'urba_delai_maison'    => 'Permis de construire, maison individuelle',
	'urba_delai_maison_duree' => '2 mois',
	'urba_delai_autres'    => 'Permis de construire, autres cas',
	'urba_delai_autres_duree' => '3 mois',
	'urba_incomplet'       => 'Un dossier incomplet fait repartir le délai à la réception des pièces manquantes.',
	'urba_decision'        => 'La décision',
	'urba_decision_texte'  => 'Sans réponse au terme du délai, l’autorisation est acquise. Demandez-en l’attestation à la mairie : elle vous sera réclamée par votre assureur et par le notaire.',
	'urba_affichage'       => 'L’affichage sur le terrain',
	'urba_affichage_texte' => 'Dès l’accord, le panneau réglementaire est posé sur le terrain, visible depuis la rue, et reste en place pendant toute la durée du chantier. Les voisins ont deux mois pour contester à compter de cet affichage : sans panneau, ce délai ne commence jamais à courir.',

	'urba_titre_local'     => 'Ce qui vaut à Marly-Gomont',
	'urba_document'        => 'Le document d’urbanisme',
	'urba_decide'          => 'Qui délivre les autorisations',
	'urba_clotures'        => 'Les clôtures',

	'urba_lien_cadastre'   => 'Consulter le cadastre',
	'urba_lien_formulaires' => 'Les formulaires à remplir',
	'urba_lien_ecrire'     => 'Écrire à la mairie',

	// --- Pied de page --------------------------------------------------------
	'pied_sous_titre'      => 'Commune de l’Aisne · Thiérache',
	'pied_la_mairie'       => 'La mairie',
	'pied_horaires'        => 'Horaires d’ouverture',
	'pied_nous_ecrire'     => 'Nous écrire',
	'pied_rester_informe'  => 'Rester informé',
	'pied_lettre_texte'    => 'Les nouvelles de la commune dans votre boîte, une fois par mois.',
	'pied_sinscrire'       => 'S’inscrire',
	'pied_demarches_texte' => 'Carte d’identité, acte de naissance, listes électorales : ce qui se fait en mairie et ce qui se fait ailleurs.',
	'pied_voir_demarches'  => 'Voir les démarches',
	'pied_ou'              => 'ou directement sur',

	// --- Recherche ------------------------------------------------------------
	'titre_recherche'      => 'Recherche',
	'recherche_rubriques'  => 'Rubriques du site',
	'un_resultat'          => '1 résultat',
	'des_resultats'        => '@nb@ résultats',
	'recherche_rien'       => 'Aucun résultat pour',
	'recherche_essayez'    => 'Essayez un mot plus court, ou une autre orthographe. Vous pouvez aussi partir de l’une de ces pages :',

	// --- Page introuvable ----------------------------------------------------
	'titre_404'            => 'Cette page n’existe pas',
	'texte_404'            => 'L’adresse demandée ne correspond à aucune page du site. Elle a peut-être changé, ou la page a été retirée. Voici de quoi retrouver ce que vous cherchiez.',
	'404_ou_aller'         => 'Où aller',
	'404_accueil_quoi'     => 'les actualités et les prochains rendez-vous',
	'404_plan_quoi'        => 'toutes les pages du site, sur une seule liste',
	'404_sinon'            => 'Si vous ne trouvez toujours pas, écrivez à la mairie :',

	// --- Connexion -----------------------------------------------------------
	// L'espace prive est celui du secretariat et des elus, et de personne
	// d'autre : c'est l'usage des communes de cette taille, et c'est le seul
	// que le code sache tenir. Une association ou un commerce passe par le
	// signalement ou par le secretariat, sans compte.
	'se_connecter'         => 'Se connecter',
	'connexion_pour'       => 'Réservé au secrétariat de mairie et aux élus.',
	'connexion_deja'       => 'Vous êtes connecté',
	'connexion_deja_qui'   => 'Compte utilisé :',
	'connexion_espace'     => 'Espace personnel',
	'connexion_deconnecter'=> 'Se déconnecter',

	'connexion_acces'      => 'Accès à l’espace personnel',

	'connexion_q1'         => 'Faut-il créer un compte pour utiliser le site ?',
	'connexion_q1_a'       => 'Non. L’ensemble des services proposés sur le site est accessible sans création de compte.',
	'connexion_q1_b'       => 'Vous pouvez consulter les démarches, réserver une salle, vous inscrire à la lettre d’information ou transmettre les informations d’une association sans avoir à vous connecter.',

	'connexion_q2'         => 'Qui accède à l’espace personnel ?',
	'connexion_q2_a'       => 'L’espace personnel est réservé au secrétariat de mairie et aux élus habilités. Il leur permet de publier et de mettre à jour les informations utiles à la vie de la commune, notamment :',
	'connexion_q2_1'       => 'les actualités ;',
	'connexion_q2_2'       => 'les comptes rendus du conseil municipal ;',
	'connexion_q2_3'       => 'les arrêtés ;',
	'connexion_q2_4'       => 'les documents relatifs à l’urbanisme.',
	'connexion_q2_b'       => 'Aucun compte n’est attribué en dehors de la mairie.',

	'connexion_q3'         => 'Vous représentez une association ou un commerce ?',
	// Le formulaire de signalement CREE une fiche, il n'en modifie aucune :
	// il refuse meme un nom deja present. Ecrire << ou mettre a jour >> sur
	// son bouton envoyait le president d'une association existante dans un
	// mur. Pour corriger, chaque fiche porte deja son bouton de signalement.
	'connexion_q3_a'       => 'Les fiches de l’annuaire sont tenues à jour par la mairie, et aucun compte n’est nécessaire.',
	'connexion_q3_b2'      => 'Si votre association ne figure pas encore dans l’annuaire, ce formulaire permet de la proposer. Pour corriger une fiche déjà en ligne, sa page porte un bouton « signaler une correction ».',
	'connexion_q3_lien'    => 'Proposer une association absente de l’annuaire',
	'connexion_q3_b'       => 'Pour un commerce, un artisan, un service, ou pour toute autre demande, vous pouvez contacter la mairie à l’adresse suivante :',

	// --- Mot de passe oublie -------------------------------------------------
	'mot_de_passe_oublie'  => 'Mot de passe oublié',
	'pass_avant'           => 'Cette page ne concerne que les comptes de l’espace personnel, créés par la mairie. Aucun compte n’est nécessaire pour consulter le site ou effectuer une démarche.',
	'pass_titre_demande'   => 'Réinitialiser votre mot de passe',
	'pass_titre_nouveau'   => 'Choisir un nouveau mot de passe',
	'pass_retour'          => 'Retour à la connexion',

	// --- Contact -------------------------------------------------------------
	'contact'              => 'Contact',
	'plan_contact_quoi'    => 'l’adresse, le téléphone, les horaires, et un formulaire',
	'champ_adresse'        => 'Adresse',
	'contact_la_mairie'    => 'La mairie',
	'contact_ecrire'       => 'Écrire à la mairie',
	'contact_ecrire_quoi'  => 'Une question, une remarque, un signalement. Le message arrive au secrétariat, qui vous répond à l’adresse que vous indiquez.',
	'contact_mention'      => 'Les informations saisies servent uniquement à traiter votre demande. Elles sont transmises au secrétariat de mairie par courriel, ne sont pas conservées sur le site, et ne sont communiquées à personne d’autre.',
	'contact_avant'        => 'Avant d’écrire',
	'contact_avant_quoi'   => 'Beaucoup de demandes ont déjà leur page, avec la liste des pièces à fournir et les délais. C’est souvent plus rapide que d’attendre une réponse.',

	// Variante en bas de casse : la meme mention, au milieu d'une phrase.
	'site_officiel_bas'    => 'site officiel de la commune',

);
