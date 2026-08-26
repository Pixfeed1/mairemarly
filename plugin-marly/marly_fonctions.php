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
