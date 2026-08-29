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
	$id_article = intval($id_article);
	if (!$id_article) {
		return '';
	}

	/* LE LOGO N'EST LU QUE SI CETTE VERSION DE SPIP SAIT LE LIRE.
	   chercher_logo() a disparu du noyau : l'appeler a fait tomber la page
	   d'article entiere en << Erreur d'execution >> le 29 aout 2026 a 23 h 55,
	   sur toutes les pages d'article du site. Journal de SPIP :
	   << Call to undefined function chercher_logo() >>.

	   Le garde-fou ne masque rien : depuis SPIP 4.2 un logo EST un document
	   attache, et la requete ci-dessous le trouve donc de toute facon. Sur une
	   version plus ancienne, l'appel reprend son role. Dans les deux cas la
	   page s'affiche.

	   Mesure du 27 aout 2026 : aucun des 82 articles n'a de logo. */
	include_spip('inc/logos');
	if (function_exists('chercher_logo')) {
		$logo = chercher_logo($id_article, 'id_article', 'on');
		if ($logo and !empty($logo[0])) {
			return $logo[0];
		}
	}

	/* DEUX REQUETES SIMPLES PLUTOT QU'UNE JOINTURE ECRITE A LA MAIN.
	   sql_getfetsel prefixe lui-meme les noms de tables : une chaine
	   << spip_documents AS doc INNER JOIN ... >> passe par ce prefixage avec
	   ses alias, et la requete part de travers. Deux appels ordinaires
	   coutent une requete de plus et ne peuvent pas se tromper. */
	$liens = sql_allfetsel('id_document', 'spip_documents_liens', array(
		'objet = ' . sql_quote('article'),
		'id_objet = ' . $id_article,
	));
	if (!$liens) {
		return '';
	}

	$ids = array();
	foreach ($liens as $lien) {
		$ids[] = intval($lien['id_document']);
	}

	/* La premiere image jointe, dans l'ordre ou le gabarit les montre. */
	return (string) sql_getfetsel('fichier', 'spip_documents',
		array(
			sql_in('id_document', $ids),
			'mode = ' . sql_quote('image'),
		),
		'', 'titre, date', '0,1'
	);
}

/**
 * L'adresse toute prete de l'illustration d'un article, reduite pour la une.
 * ---------------------------------------------------------------------------
 * POURQUOI CE FILTRE EXISTE PLUTOT QU'UN CALCUL DANS LE GABARIT. La une doit
 * choisir sa mise en page selon qu'il y a une image, donc poser un bloc
 * optionnel. Ecrire la reduction a l'interieur de ce bloc demandait
 * [(...|image_reduire{...}|extraire_attribut{...})] DANS un [(...)] : le
 * premier crochet fermant referme le bloc exterieur, et la page entiere rend
 * << Erreur d'execution >>. Mesure du 28 aout 2026 en production.
 *
 * Ici il n'y a ni crochet ni accolade. Le gabarit n'ecrit plus qu'un GET.
 */
function filtre_marly_illustration_src_dist($id_article) {
	$fichier = filtre_marly_illustration_article_dist($id_article);
	if (!$fichier) {
		return '';
	}
	include_spip('inc/filtres');
	include_spip('inc/filtres_images');
	return extraire_attribut(image_reduire($fichier, 1400, 0), 'src');
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
