<?php
/**
 * Les filtres que les squelettes peuvent appeler.
 * ---------------------------------------------------------------------------
 * SPIP charge ce fichier automatiquement pour tout squelette du site.
 */

if (!defined('_ECRIRE_INC_VERSION')) {
	return;
}

include_spip('inc/marly_reservations');

/** Le compte des places restantes, exposé aux gabarits. */
function filtre_marly_places_restantes_dist($id_manifestation) {
	$restantes = marly_places_restantes($id_manifestation);
	return ($restantes === PHP_INT_MAX) ? '' : $restantes;
}

/**
 * Le calendrier d'un mois, pour filtrer les événements.
 * ---------------------------------------------------------------------------
 * Rend la grille complète — six semaines de sept jours — parce qu'un
 * calendrier qui change de hauteur d'un mois à l'autre fait sauter la page
 * sous le curseur quand on navigue.
 *
 * Chaque jour porte :
 *   jour   le numéro affiché
 *   date   AAAA-MM-JJ, pour le lien de filtre
 *   hors   vrai si le jour appartient au mois voisin
 *   actif  vrai si au moins un événement a lieu ce jour-là
 *
 * @param string $mois  AAAA-MM
 */
function filtre_marly_jours_mois_dist($mois) {
	if (!preg_match(',^(\d{4})-(\d{2})$,', (string) $mois, $r)) {
		$mois = date('Y-m');
		preg_match(',^(\d{4})-(\d{2})$,', $mois, $r);
	}
	$annee = intval($r[1]);
	$m     = intval($r[2]);

	$premier = mktime(0, 0, 0, $m, 1, $annee);
	$nb      = intval(date('t', $premier));

	/* Lundi comme premier jour : N est le 1 = lundi … 7 = dimanche. */
	$decalage = intval(date('N', $premier)) - 1;
	$debut    = mktime(0, 0, 0, $m, 1 - $decalage, $annee);

	/* Les jours du mois qui portent un événement publié. Une seule requête,
	   pas une par case : quarante-deux requêtes pour afficher un mois
	   seraient quarante et une de trop. */
	$occupes = array();
	$lignes = sql_allfetsel(
		'DISTINCT DATE(date_debut) AS jour',
		'spip_manifestations',
		array(
			"statut = 'publie'",
			'date_debut >= ' . sql_quote(date('Y-m-d 00:00:00', $debut)),
			'date_debut < ' . sql_quote(date('Y-m-d 00:00:00', $debut + 42 * 86400)),
		)
	);
	foreach ($lignes as $ligne) {
		$occupes[$ligne['jour']] = true;
	}

	$grille = array();
	for ($i = 0; $i < 42; $i++) {
		$t    = $debut + $i * 86400;
		$date = date('Y-m-d', $t);
		$grille[] = array(
			'jour'  => intval(date('j', $t)),
			'date'  => $date,
			'hors'  => (intval(date('n', $t)) !== $m) ? 'oui' : '',
			'actif' => isset($occupes[$date]) ? 'oui' : '',
			'aujourdhui' => ($date === date('Y-m-d')) ? 'oui' : '',
		);
	}

	return $grille;
}

/**
 * Un numéro de téléphone pour un lien tel:.
 * ---------------------------------------------------------------------------
 * Les squelettes écrivaient |replace{'[^0-9+]',''}. L'expression est juste,
 * mais elle contient des CROCHETS, et le crochet ouvre un bloc optionnel en
 * langage SPIP : la ligne entière cessait d'être interprétée et le visiteur
 * lisait href="tel:[(03 23 60 21 85|replace{'[^0-9+]',''}|attribut_html)]"
 * dans la page. Mesuré le 26 août 2026 sur la page Contact.
 *
 * Le nettoyage se fait donc ici, où les crochets n'ont pas de sens
 * particulier. Le plus (+) initial est gardé : un numéro international sans
 * lui n'appelle personne.
 */
function filtre_marly_tel_lien_dist($tel) {
	$tel = (string) $tel;
	$plus = (strpos(trim($tel), '+') === 0) ? '+' : '';
	return $plus . preg_replace('/[^0-9]/', '', $tel);
}

/**
 * Un mois AAAA-MM sûr, quoi qu'on lui donne.
 * ---------------------------------------------------------------------------
 * Le squelette écrivait le mois courant lui-même, par #VAL|date_format{Y-m}.
 * #VAL sans argument vaut la chaîne vide, et PHP 8 refuse une chaîne là où
 * date_format() attend un objet : la page de réservation levait une erreur à
 * chaque affichage et ne rendait plus sa bannière. Personne ne l'avait vue —
 * c'est l'audit RGAA du 26 août 2026 qui a signalé la page comme dépourvue de
 * titre de premier niveau, et c'était le symptôme, pas la maladie.
 *
 * Le mois vient de la chaîne de requête, donc de n'importe qui. Un ?mois=pizza
 * ne casse rien aujourd'hui, mais laissait le nom du mois vide en haut du
 * calendrier. Une seule porte d'entrée vaut mieux que trois replis identiques
 * dans trois filtres.
 */
function filtre_marly_mois_valide_dist($mois) {
	return preg_match(',^\d{4}-(0[1-9]|1[0-2])$,', (string) $mois)
		? (string) $mois
		: date('Y-m');
}

/** Décale un mois AAAA-MM de n mois. */
function filtre_marly_mois_decale_dist($mois, $n) {
	if (!preg_match(',^(\d{4})-(\d{2})$,', (string) $mois, $r)) {
		$mois = date('Y-m');
		preg_match(',^(\d{4})-(\d{2})$,', $mois, $r);
	}
	return date('Y-m', mktime(0, 0, 0, intval($r[2]) + intval($n), 1, intval($r[1])));
}

/** « Août 2026 » à partir de « 2026-08 ». */
function filtre_marly_nom_mois_dist($mois) {
	if (!preg_match(',^(\d{4})-(\d{2})$,', (string) $mois, $r)) {
		return '';
	}
	include_spip('inc/filtres_dates');
	return affdate(date('Y-m-d', mktime(0, 0, 0, intval($r[2]), 1, intval($r[1]))), 'nom_mois')
		. ' ' . $r[1];
}

/**
 * La rubrique où vivent les pages légales, ou 0.
 * ---------------------------------------------------------------------------
 * POURQUOI CETTE FONCTION EXISTE. La bande d'actualités doit écarter les
 * quatre pages légales — mentions, confidentialité, crédits, accessibilité —
 * qui ne sont pas des nouvelles et qui, créées le même jour, seraient sinon
 * les quatre articles les plus récents du site.
 *
 * Deux écritures ont été essayées dans le squelette, et les deux ont échoué
 * en production :
 *
 *   {type_mot!=Emplacements}  — le groupe n'existait pas, le critère
 *                               n'écartait rien ;
 *   {titre_mot!=…} ×7         — mesuré le 26 août 2026 : la bande affichait
 *                               « Aucune actualité publiée » avec 82 articles
 *                               en base. Une négation sur table JOINTE écarte
 *                               aussi les articles qui ne portent AUCUN
 *                               mot-clé, c'est-à-dire ici la quasi-totalité.
 *
 * On revient donc à une comparaison de colonne simple, {id_rubrique!=…}, le
 * seul mécanisme dont ce site ait la preuve qu'il fonctionne. Il faut pour
 * cela connaître la rubrique, et c'est ce que cette fonction donne.
 *
 * ELLE CHERCHE PAR LE CONTENU, PAS PAR LE NOM. Le titre « Informations
 * légales » n'est qu'un repli : on part du mot-clé « Mentions légales », on
 * remonte à l'article qui le porte, et on lit SA rubrique. La mairie peut
 * donc renommer ou déplacer la rubrique sans que l'accueil se remette à
 * afficher ses mentions légales en une.
 *
 * Rend 0 si rien n'est trouvé — et {id_rubrique!=0} n'écarte alors personne,
 * ce qui se voit tout de suite sur la page plutôt que de masquer des articles
 * en silence.
 */
function filtre_marly_rubrique_legale_dist($rien = '') {
	$id_mot = sql_getfetsel('id_mot', 'spip_mots', 'titre = ' . sql_quote('Mentions légales'));
	if ($id_mot) {
		$id_article = sql_getfetsel('id_objet', 'spip_mots_liens',
			'id_mot = ' . intval($id_mot) . ' AND objet = ' . sql_quote('article'));
		if ($id_article) {
			$id_rubrique = sql_getfetsel('id_rubrique', 'spip_articles',
				'id_article = ' . intval($id_article));
			if ($id_rubrique) {
				return (int) $id_rubrique;
			}
		}
	}
	return (int) sql_getfetsel('id_rubrique', 'spip_rubriques',
		'titre = ' . sql_quote('Informations légales'));
}

/**
 * La rubrique des comptes rendus de conseil, ou 0.
 * ---------------------------------------------------------------------------
 * MESURE DU 26 AOÛT 2026, sur la base réelle : 30 des 82 articles publiés sont
 * des comptes rendus de conseil, et ce sont les plus récents. Déployée sans
 * ce filtre, la page d'accueil affichait six fois « Réunion de conseil du … »
 * — la mise en avant et les cinq brèves — pendant que la bande des documents,
 * juste en dessous, listait les MÊMES séances en PDF. L'accueil disait deux
 * fois de suite la même chose.
 *
 * Ces articles ne sont pas perdus pour autant : ils ont leur bande de
 * documents, leur page « Le conseil municipal », et la rubrique elle-même.
 * Les écarter des actualités laisse 52 articles, dont 19 d'événements et 11
 * d'informations pratiques — ce qu'un habitant vient chercher.
 *
 * La rubrique est trouvée par son titre, comme le fait déjà la page du
 * conseil municipal ({titre==[Cc]omptes rendus}). Si la mairie la renomme,
 * les deux cessent de la reconnaître en même temps, ce qui est au moins
 * cohérent — et ici l'effet est bénin : les comptes rendus réapparaissent
 * dans les actualités, personne ne perd rien.
 */
function filtre_marly_rubrique_comptes_rendus_dist($rien = '') {
	return (int) sql_getfetsel('id_rubrique', 'spip_rubriques',
		'titre LIKE ' . sql_quote('Comptes rendus%'));
}

/**
 * L'adresse publique de la photographie de bannière, ou ''.
 * ---------------------------------------------------------------------------
 * Le fichier est déposé depuis Configuration ▸ Réglages de la commune et vit
 * dans IMG/. Trois raisons de passer par un filtre plutôt que de composer
 * l'adresse dans le squelette :
 *
 *   — _DIR_IMG est un réglage de SPIP, pas une constante universelle. L'écrire
 *     « IMG/ » en dur marcherait ici et casserait ailleurs ;
 *   — l'adresse doit être ABSOLUE. Une adresse relative se casse dès que la
 *     page a des segments, ce qui est le cas de toutes nos URL d'articles ;
 *   — on relit le DISQUE. Si le fichier a été effacé à la main, la
 *     configuration mentirait et la page appellerait une image absente. Ce qui
 *     n'existe pas ne s'affiche pas, et le thème reprend la place.
 */
function filtre_marly_url_banniere_dist($rien = '') {
	return marly_url_image_deposee('banniere');
}

/**
 * L'adresse publique de la photographie des pages de section, ou ''.
 * ---------------------------------------------------------------------------
 * La bande étroite en tête des pages qui n'existent pas dans la base — les
 * démarches, l'annuaire des commerces, les réservations, le contact — et qui
 * n'ont donc aucune rubrique à qui emprunter un logo. Une rubrique, elle,
 * garde le sien : c'est plus juste, chacune mérite son image.
 */
function filtre_marly_url_bandeau_dist($rien = '') {
	return marly_url_image_deposee('bandeau');
}

/**
 * L'adresse publique de la photographie de la vie associative, ou ''.
 * ---------------------------------------------------------------------------
 * Le thème en fournit une par défaut. Celle-ci, si elle existe, la remplace :
 * une photographie du forum des associations ou de la fête du village dit
 * toujours mieux la vie associative de la commune qu'une image générique.
 */
/**
 * L'identifiant de la rubrique de la vie associative, ou 0.
 * ---------------------------------------------------------------------------
 * Le site a DEUX pages qui parlent des associations, et c'est une vraie
 * particularité de sa structure : la RUBRIQUE, où sont les articles écrits par
 * les associations, et l'ANNUAIRE, qui liste les associations elles-mêmes avec
 * leurs contacts. Le menu principal, qui boucle sur les rubriques racines,
 * mène à la première. L'annuaire n'est atteint que depuis une fiche, la
 * recherche et le plan.
 *
 * L'illustration doit donc couvrir les deux, sans quoi le visiteur qui arrive
 * par le menu ne la voit jamais — c'est ce qui s'est passé.
 *
 * ANCRÉ SUR LE TITRE, comme les comptes rendus. Si la mairie renomme la
 * rubrique, la reconnaissance cesse et la rubrique retombe sur son propre
 * logo : c'est un repli honnête, et pas une page cassée.
 */
function filtre_marly_rubrique_associations_dist($rien = '') {
	return (int) sql_getfetsel('id_rubrique', 'spip_rubriques',
		'titre LIKE ' . sql_quote('Vie associative%'));
}

function filtre_marly_url_associations_dist($rien = '') {
	return marly_url_image_deposee('associations');
}

/**
 * L'illustration d'un article : son logo, et a defaut sa premiere image jointe.
 * ---------------------------------------------------------------------------
 * POURQUOI EN PHP ET PAS DANS LE GABARIT. La une d'article doit CHOISIR sa
 * mise en page selon qu'il y a une image ou non — photographie a gauche et
 * titre a droite, ou titre pleine largeur. Il faut donc un booleen AVANT
 * d'ecrire quoi que ce soit, et non une valeur au moment de l'afficher.
 *
 * Ecrit dans le gabarit, ce test demanderait #LOGO_ARTICLE|sinon{#GET{illu}}
 * a l'interieur d'un #SET, donc des accolades imbriquees. Ce site en a deja
 * paye le prix deux fois. Ici il n'y a pas d'accolade du tout.
 *
 * LE REPLI SUR L'IMAGE JOINTE N'EST PAS UN LUXE. Mesure du 27 aout 2026 :
 * aucun des 82 articles n'a de logo, sept portent une image jointe. Dans SPIP
 * un document attache et un logo sont deux choses distinctes, et ce sont les
 * logos que les gabarits montrent. Une secretaire de mairie joint une photo a
 * son article ; elle ne va pas chercher l'ecran separe du logo.
 *
 * Rend le chemin du fichier, ou une chaine vide.
 */
function filtre_marly_illustration_article_dist($id_article) {
	$doc = marly_illustration_document($id_article);
	return $doc ? $doc['fichier'] : '';
}

/**
 * L'identifiant de l'illustration d'un article, ou 0.
 * ---------------------------------------------------------------------------
 * Il sert a l'ECARTER de la galerie << En images >>, ou elle ressortait en
 * vignette apres s'etre affichee en grand dans l'en-tete.
 *
 * UNE SEULE VALEUR, ET C'EST UNE CONTRAINTE DU LANGAGE. J'avais ecrit un
 * filtre qui rendait la LISTE de toutes les images deja posees — celle de
 * tete et celles que la mairie place dans le texte — pour les ecarter d'un
 * coup avec le critere !IN. Mesure du 30 aout 2026 en production : la page
 * ne signale aucune erreur et n'ecarte RIEN, pas meme l'image de tete. Le
 * critere IN attend une liste ecrite dans le gabarit, pas une chaine
 * calculee a l'execution.
 *
 * La comparaison simple, elle, fonctionne : le site l'emploie depuis des
 * semaines pour ecarter deux rubriques de la bande d'actualites. On s'en
 * tient donc a ce qui est prouve, quitte a ne traiter qu'un cas sur deux.
 */
function filtre_marly_illustration_id_dist($id_article) {
	$doc = marly_illustration_document($id_article);
	return $doc ? (int) $doc['id'] : 0;
}

/**
 * Le document qui illustre un article : logo d'abord, premiere image jointe
 * ensuite. Rend array('id', 'fichier'), ou null.
 * ---------------------------------------------------------------------------
 * << PREMIERE >> VEUT DIRE PREMIERE DE LA LISTE, dans l'ordre ou l'espace
 * prive les montre — rang_lien, puis l'identifiant. C'est le seul ordre que
 * la mairie voit et sur lequel elle peut agir.
 *
 * Le tri precedent etait titre, date. Mesure du 30 aout 2026 : les titres des
 * documents sont TOUS VIDES, le tri retombait donc sur la date, c'est-a-dire
 * l'ordre d'import. Arbitraire, et surtout invisible : rien a l'ecran ne
 * disait pourquoi telle photo montait en tete plutot qu'une autre. L'article
 * 38 en porte six, le 39 en porte quatre.
 *
 * rang_lien vaut 0 partout aujourd'hui — les documents viennent de l'import,
 * qui ne l'a pas rempli. Le tri retombe alors sur l'identifiant, c'est-a-dire
 * l'ordre d'ajout, et la regle enseignable tient quand meme : joignez d'abord
 * la photo qui doit s'afficher en grand. Si SPIP tient ce rang pour les
 * documents a venir, faire remonter la photo dans la liste marchera aussi.
 */
function marly_illustration_document($id_article) {
	$id_article = intval($id_article);
	if (!$id_article) {
		return null;
	}

	/* LE LOGO N'EST LU QUE SI CETTE VERSION DE SPIP SAIT LE LIRE.
	   chercher_logo() a disparu du noyau : l'appeler a fait tomber toutes les
	   pages d'article le 29 aout 2026. Le garde-fou ne masque rien — depuis
	   SPIP 4.2 un logo EST un document attache, et la requete ci-dessous le
	   trouve de toute facon. */
	include_spip('inc/logos');
	if (function_exists('chercher_logo')) {
		$logo = chercher_logo($id_article, 'id_article', 'on');
		if ($logo and !empty($logo[0])) {
			return array('id' => 0, 'fichier' => $logo[0]);
		}
	}

	/* Les liens, dans l'ordre de la liste. Deux requetes ordinaires plutot
	   qu'une jointure ecrite a la main : sql_getfetsel prefixe lui-meme les
	   noms de tables, et une chaine avec des alias part de travers. */
	$liens = sql_allfetsel('id_document', 'spip_documents_liens', array(
		'objet = ' . sql_quote('article'),
		'id_objet = ' . $id_article,
	), '', 'rang_lien, id_document');
	if (!$liens) {
		return null;
	}

	$ordre = array();
	foreach ($liens as $lien) {
		$ordre[] = intval($lien['id_document']);
	}

	/* Celles qui sont des images. On garde l'ordre des liens, pas celui que
	   la base voudrait rendre. */
	$images = array();
	foreach (sql_allfetsel('id_document, fichier', 'spip_documents', array(
		sql_in('id_document', $ordre),
		'mode = ' . sql_quote('image'),
	)) as $doc) {
		$images[intval($doc['id_document'])] = $doc['fichier'];
	}

	foreach ($ordre as $id) {
		if (isset($images[$id])) {
			return array('id' => $id, 'fichier' => $images[$id]);
		}
	}
	return null;
}

/**
 * Le titre d'une page FIXE du site, pour le <title> du navigateur.
 * ---------------------------------------------------------------------------
 * MESURE DU 30 AOUT 2026 : les 162 pages du site portaient TOUTES le meme
 * titre, << Marly-Gomont — Site officiel de la commune >>. inc/head.html le
 * construisait a partir d'une variable titre_page qu'aucun squelette n'a
 * jamais definie. Trois consequences : RGAA 8.6 — un titre identique partout
 * n'est pas pertinent ; le referencement — c'est la ligne bleue d'un resultat
 * de recherche, et le site en avait une seule ; et l'usage courant — onglets
 * et favoris indistinguables.
 *
 * POURQUOI UNE TABLE ICI ET PAS DANS LES SQUELETTES. L'en-tete HTML est rendu
 * AVANT le contenu de la page : un titre pose dans contenu/credits.html ne
 * peut pas remonter jusqu'a lui. Il faudrait donc le passer en argument
 * depuis chacun des trente squelettes de racine — trente endroits a tenir a
 * jour, et trente occasions d'en oublier un.
 *
 * LES PAGES D'OBJET NE SONT PAS ICI, et c'est voulu : un article, une fiche,
 * une rubrique portent le titre de leur objet, que inc/head.html lit dans une
 * boucle. Seules les pages dont le titre est fixe ont besoin d'une ligne.
 *
 * Rend une chaine vide pour l'accueil — qui n'a pas de titre de page, et
 * affiche a la place << Site officiel de la commune >> — et pour toute page
 * inconnue. La regle 78 du verificateur interdit qu'une page de racine soit
 * inconnue des deux mecanismes a la fois.
 */
function filtre_marly_titre_page_dist($type_page) {
	$titres = array(
		'sommaire'         => '',
		'404'              => 'marly:titre_404',
		'accessibilite'    => 'marly:titre_accessibilite',
		'actualites'       => 'marly:titre_actualites',
		'associations'     => 'marly:titre_annuaire',
		'commerces'        => 'marly:titre_commerces',
		'confidentialite'  => 'marly:titre_confidentialite',
		'conseil'          => 'marly:titre_conseil',
		'contact'          => 'marly:contact',
		'credits'          => 'marly:credits',
		'demarches'        => 'marly:toutes_les_demarches',
		'lieux'            => 'marly:titre_ou_nous_trouver',
		'login'            => 'marly:connexion',
		'mentions-legales' => 'marly:mentions_legales',
		'newsletter'       => 'marly:newsletter',
		'plan'             => 'marly:plan_du_site',
		'recherche'        => 'marly:titre_recherche',
		'reservation'      => 'marly:reserver',
		'spip_pass'        => 'marly:mot_de_passe_oublie',
	);

	$type_page = (string) $type_page;
	if (!isset($titres[$type_page]) or $titres[$type_page] === '') {
		return '';
	}
	return _T($titres[$type_page]);
}

/**
 * Le titre complet d'une page, tel qu'il part dans l'onglet du navigateur.
 * ---------------------------------------------------------------------------
 * POURQUOI CE FILTRE EXISTE. Le titre etait compose de trois blocs optionnels
 * dans le gabarit, et l'accueil sortait avec DEUX espaces avant le tiret.
 * Mesure du 30 aout 2026 : 6f 6e 74 20 20 e2 80 94 — deux espaces ORDINAIRES,
 * pas une insecable. J'ai cherche un caractere exotique pendant trois tours
 * alors que c'etait la mecanique du langage.
 *
 * |non NE REND PAS UN BOOLEEN, IL REND UNE ESPACE. C'est ainsi que SPIP fait
 * marcher ses blocs optionnels : ' ' pour vrai, '' pour faux. Et dans la
 * forme entre crochets, SPIP affiche LA VALEUR puis le texte qui suit. La
 * valeur, ici, c'etait cette espace.
 *
 * Le titre se compose donc en PHP, ou une condition est une condition et
 * n'imprime rien. Trois blocs optionnels de moins dans le gabarit.
 *
 * Le nom du site vient des metas et non d'un argument : le passer depuis le
 * gabarit demanderait une balise dans les accolades d'un filtre, et ce site a
 * deja paye ce piege trois fois.
 */
function filtre_marly_titre_complet_dist($titre) {
	$nom = filtre_marly_nom_site_dist($GLOBALS['meta']['nom_site'] ?? '');
	$titre = filtre_marly_nom_site_dist($titre);

	if ($titre !== '') {
		return $titre . ' — ' . $nom;
	}

	/* L'accueil n'a pas de titre de page. La mention qui le remplace est ce
	   qui, dans une liste de resultats de recherche, distingue le vrai site de
	   l'ancien marlygomont.free.fr et des pages non officielles. */
	return $nom . ' — ' . _T('marly:site_officiel');
}

/**
 * Le nom du site, debarrasse de ses espaces de bout — insecables compris.
 * ---------------------------------------------------------------------------
 * Le nom est saisi a la main dans SPIP, et il l'a ete avec une espace de trop.
 * Le squelette passait deja par trim(), et le titre sortait pourtant avec DEUX
 * espaces avant le tiret — mesure du 30 aout 2026, visible dans chaque
 * resultat de recherche. trim() de PHP ne connait que l'espace ordinaire, la
 * tabulation et les sauts de ligne : une espace INSECABLE lui passe sous le
 * nez, et c'est ce qu'un copier-coller depose le plus souvent.
 *
 * Corriger le champ dans SPIP ne suffirait pas : il sera ressaisi un jour.
 */
function filtre_marly_nom_site_dist($nom) {
	/* On ne se contente pas de rogner les bouts : on ramene TOUTE suite
	   d'espaces a une seule, quelle que soit l'espace — ordinaire, insecable,
	   fine insecable, tabulation. Enumerer les caracteres a la main, c'est en
	   oublier un ; \pZ les prend tous. */
	$nom = preg_replace('/[\pZ\s]+/u', ' ', (string) $nom);
	return trim((string) $nom);
}

/** Le travail commun aux trois : lire la configuration, et vérifier le disque. */
function marly_url_image_deposee($champ) {
	include_spip('inc/config');
	$nom = lire_config('marly/' . $champ, '');
	if ($nom === '' or !@is_file(_DIR_IMG . $nom)) {
		return '';
	}
	include_spip('inc/filtres');
	return url_absolue(_DIR_IMG . $nom);
}

/**
 * « sept. » à partir d'une date SQL.
 * ---------------------------------------------------------------------------
 * Pour la pastille de date de l'agenda : un rond de 52 pixels ne loge pas
 * « septembre », et un rond plus grand mangerait le titre à côté.
 *
 * La table est ÉCRITE ICI plutôt que déduite de affdate() : les abréviations
 * françaises ne sont pas mécaniques — « juin » et « août » ne s'abrègent pas,
 * « juil. » perd deux lettres, « déc. » trois. Une troncature à quatre
 * caractères donnerait « juin. », « août. » et « févr » selon les cas.
 *
 * Rend une chaîne vide si la date est illisible : l'affichage est dans un
 * bloc optionnel, la pastille se contente alors du quantième.
 */
function filtre_marly_mois_abrege_dist($date) {
	$t = strtotime((string) $date);
	/* La date vide de SQL, « 0000-00-00 », se laisse lire par strtotime et
	   rend un timestamp negatif — donc vrai. Sans ce garde-fou une
	   manifestation sans date affichait « nov. » sur sa pastille. On exige
	   une annee plausible plutot qu'un simple non-zero. */
	if ($t === false || intval(date('Y', (int) $t)) < 1970) {
		return '';
	}
	$abreges = array(
		1 => 'janv.', 2 => 'févr.', 3 => 'mars',  4 => 'avr.',
		5 => 'mai',   6 => 'juin',  7 => 'juil.', 8 => 'août',
		9 => 'sept.', 10 => 'oct.', 11 => 'nov.', 12 => 'déc.',
	);
	return $abreges[intval(date('n', $t))];
}

/**
 * Traduit l'adresse d'une video en adresse d'incorporation.
 * ---------------------------------------------------------------------------
 * La mairie colle l'adresse qu'elle voit dans son navigateur. Lui demander
 * un « code d'intégration » reviendrait à lui demander de lire du HTML.
 *
 * Les variantes sans traceur sont préférées quand la plateforme en propose
 * une : youtube-nocookie pour YouTube, dnt=1 pour Vimeo. Elles ne dispensent
 * PAS du consentement — d'où la façade au clic — mais elles réduisent ce qui
 * part une fois la vidéo lancée.
 *
 * Rend une chaîne vide si l'adresse n'est pas reconnue : mieux vaut ne rien
 * afficher qu'un cadre vide.
 */
function filtre_marly_video_embed_dist($url) {
	$url = trim((string) $url);
	if (!$url) {
		return '';
	}

	if (preg_match(',(?:youtube\.com/watch\?(?:.*&)?v=|youtu\.be/|youtube\.com/embed/)([A-Za-z0-9_-]{6,}),i', $url, $r)) {
		return 'https://www.youtube-nocookie.com/embed/' . $r[1] . '?rel=0';
	}
	if (preg_match(',vimeo\.com/(?:video/)?(\d+),i', $url, $r)) {
		return 'https://player.vimeo.com/video/' . $r[1] . '?dnt=1';
	}
	if (preg_match(',dailymotion\.com/(?:video/|embed/video/)([A-Za-z0-9]+),i', $url, $r)) {
		return 'https://www.dailymotion.com/embed/video/' . $r[1];
	}
	/* PeerTube : instances multiples, on ne peut pas lister les domaines.
	   On reconnait la forme de l'adresse d'une video et on la convertit. */
	if (preg_match(',^(https://[^/]+)/w/([A-Za-z0-9_-]+),i', $url, $r)) {
		return $r[1] . '/videos/embed/' . $r[2];
	}
	if (preg_match(',^(https://[^/]+)/videos/watch/([A-Za-z0-9-]+),i', $url, $r)) {
		return $r[1] . '/videos/embed/' . $r[2];
	}

	return '';
}

/** Le nom de la plateforme, pour le dire à l'usager avant qu'il ne clique. */
function filtre_marly_video_plateforme_dist($url) {
	$url = strtolower(trim((string) $url));
	if (strpos($url, 'youtu') !== false)       { return 'YouTube'; }
	if (strpos($url, 'vimeo') !== false)       { return 'Vimeo'; }
	if (strpos($url, 'dailymotion') !== false) { return 'Dailymotion'; }
	return 'PeerTube';
}

/** Le nombre d'abonnes confirmes, expose aux gabarits de l'espace prive. */
function filtre_marly_nb_destinataires_dist($rien = '') {
	include_spip('inc/marly_lettres');
	return marly_nb_destinataires();
}

/**
 * L'avertissement temporaire d'une fiche, s'il est encore d'actualité.
 *
 * Rend le texte, ou rien. La date de fin est le cœur du dispositif : un
 * message qu'il faut penser à retirer n'est jamais retiré. Sur le site dont
 * nous nous inspirons, un avis d'incident daté de mars s'affichait encore en
 * août.
 *
 * Sans date de fin, l'avertissement reste — c'est un choix explicite de la
 * mairie, et le formulaire le dit.
 */
function marly_alerte_active($alerte, $fin = '') {
	$alerte = trim((string) $alerte);
	if ($alerte === '') {
		return '';
	}
	$fin = trim((string) $fin);
	if ($fin === '' or $fin === '0000-00-00') {
		return $alerte;
	}
	return ($fin >= date('Y-m-d')) ? $alerte : '';
}

/**
 * Les thèmes d'associations, pour une boucle DATA.
 *
 * SPIP ne sait pas grouper une boucle par une colonne en intercalant un
 * titre. On boucle donc sur les thèmes, et une boucle interne va chercher les
 * associations de chacun. Sept requêtes sur une table de dix lignes ne se
 * mesurent pas, et le gabarit reste lisible.
 */
function filtre_marly_themes_pour_boucle_dist($rien = '') {
	include_spip('inc/marly_associations');
	return marly_themes_traduits();
}

/* ---------------------------------------------------------------------------
   Les filtres vivent TOUS ici, et nulle part ailleurs.
   ---------------------------------------------------------------------------
   SPIP ne charge que ce fichier pour compiler un squelette. Un filtre range
   dans inc/ existe pour le PHP, mais reste introuvable pour un gabarit :
   << Filtre marly_url_raccourci non defini >>. Les deux qui suivent etaient
   dans ce cas.

   Ils appellent le fichier qui porte la logique : le filtre est un guichet,
   pas un endroit ou raisonner.
   --------------------------------------------------------------------------- */

/** L'intitule d'un theme d'association, pour l'affichage. */
function filtre_marly_theme_association_dist($theme) {
	include_spip('inc/marly_associations');
	$themes = marly_themes_associations();
	return isset($themes[$theme]) ? _T($themes[$theme]) : '';
}

/**
 * L'adresse d'un raccourci de la page d'accueil.
 *
 * Rend une chaine vide si la destination n'existe plus : le gabarit n'affiche
 * alors pas le rond. Cinq raccourcis valent mieux que six dont un mene a une
 * page vide.
 */
function filtre_marly_url_raccourci_dist($cible) {
	include_spip('inc/marly_raccourcis');
	return marly_url_raccourci($cible);
}

/**
 * L'icone d'un theme d'association, pour la tuile de l'annuaire.
 *
 * Une icone par thème, et non la même partout. Répétée huit fois à
 * l'identique, une illustration cesse d'informer et devient du bruit ; en
 * changeant avec le thème, la tuile dit quelque chose avant même qu'on lise
 * le titre. Et elle ne remplace jamais une photographie : elle occupe la
 * place tant qu'il n'y en a pas.
 */
function filtre_marly_icone_theme_dist($theme) {
	$icones = array(
		'sport'      => 'ri-run-line',
		'culture'    => 'ri-palette-line',
		'enfance'    => 'ri-school-line',
		'solidarite' => 'ri-hand-heart-line',
		'patrimoine' => 'ri-ancient-gate-line',
		'memoire'    => 'ri-medal-line',
		'culte'      => 'ri-building-2-line',
		'autre'      => 'ri-team-line',
	);
	return $icones[$theme] ?? 'ri-team-line';
}

/**
 * L'adresse PUBLIQUE d'une fiche, vue depuis l'espace prive.
 *
 * Ecrite ici et non avec #URL_PAGE dans le gabarit : dans l'espace prive,
 * #URL_PAGE rend une adresse relative a /ecrire/, et le lien menait donc
 * nulle part. On passe par le generateur d'adresses publiques, puis on rend
 * l'adresse absolue — le lien s'ouvre dans un autre onglet, il doit porter
 * le nom du site.
 */
function filtre_marly_url_publique_dist($page, $cle = '', $id = 0) {
	include_spip('inc/urls');
	include_spip('inc/filtres');
	$args = ($cle !== '' and intval($id)) ? $cle . '=' . intval($id) : '';
	return url_absolue(generer_url_public($page, $args));
}

/** L'intitule d'une categorie de commerce. */
function filtre_marly_categorie_commerce_dist($categorie) {
	include_spip('inc/marly_commerces');
	return marly_categorie_commerce($categorie);
}

/** L'icone d'une categorie de commerce. */
function filtre_marly_icone_commerce_dist($categorie) {
	include_spip('inc/marly_commerces');
	return marly_icone_commerce($categorie);
}

/** L'intitule d'une nature de lieu. */
function filtre_marly_type_lieu_dist($type) {
	include_spip('inc/marly_lieux');
	$types = marly_types_lieux();
	return isset($types[$type]) ? _T($types[$type][0]) : '';
}

/** L'icone d'une nature de lieu. */
function filtre_marly_icone_lieu_dist($type) {
	include_spip('inc/marly_lieux');
	$types = marly_types_lieux();
	return isset($types[$type]) ? $types[$type][1] : 'ri-map-pin-2-line';
}

/**
 * Le lien vers un point sur OpenStreetMap.
 *
 * Il accompagne la carte au lieu de la remplacer : la facade situe le lieu,
 * le lien permet d'ouvrir le plan en grand et d'y demander un itineraire,
 * ce qu'un cadre integre ne fait pas.
 */
function filtre_marly_lien_carte_dist($latitude, $longitude = '') {
	$lat = trim((string) $latitude);
	$lon = trim((string) $longitude);
	if ($lat === '' or $lon === '') {
		return '';
	}
	return 'https://www.openstreetmap.org/?mlat=' . rawurlencode($lat)
		. '&mlon=' . rawurlencode($lon)
		. '#map=17/' . rawurlencode($lat) . '/' . rawurlencode($lon);
}
