<?php
/**
 * Le plan, fabriqué par le serveur.
 * ---------------------------------------------------------------------------
 * Le cadre officiel d'OpenStreetMap embarque son propre habillage : liens
 * « Signaler un problème », « Faire un don », conditions d'utilisation. Sur
 * un site de mairie, cet habillage n'a rien à faire, et il ne s'enlève pas :
 * c'est leur page, pas la nôtre.
 *
 * On assemble donc l'image nous-mêmes : le serveur télécharge les tuiles,
 * les colle, pose le marqueur, et enregistre un PNG servi comme n'importe
 * quelle image du site. Nos dimensions, notre cadre, aucun texte tiers.
 * Et rien ne part chez OpenStreetMap quand un habitant visite la page :
 * les tuiles sont demandées UNE FOIS, par le serveur, à la fabrication.
 *
 * Ce qui reste dû : le crédit « © OpenStreetMap ». C'est la condition de la
 * licence des données. Il se grave dans le coin de l'image, minuscule,
 * comme sur tout plan sérieux, Google compris. Les sites qui n'affichent
 * rien sont en infraction, pas en avance.
 *
 * L'image est refaite quand les coordonnées changent (son nom les
 * contient), et à chaque déploiement (le cache local/cache-* est purgé).
 */

if (!defined('_ECRIRE_INC_VERSION')) {
	return;
}

define('MARLY_CARTE_LARGEUR', 760);
define('MARLY_CARTE_HAUTEUR', 420);

/**
 * L'image d'un point. Rend l'adresse du PNG, ou '' si elle n'a pu se faire.
 */
function marly_carte_image_point($latitude, $longitude) {
	$lat = floatval($latitude);
	$lon = floatval($longitude);
	if (!$lat and !$lon) {
		return '';
	}
	return marly_carte_fabriquer('point', array(array($lat, $lon)), 16);
}

/**
 * L'image de la commune : tous les lieux publiés qui ont des coordonnées.
 */
function marly_carte_image_commune() {
	include_spip('base/abstract_sql');
	$points = array();
	foreach (sql_allfetsel('latitude, longitude', 'spip_lieux',
	                       "statut = 'publie' AND latitude <> '' AND longitude <> ''") as $l) {
		$points[] = array(floatval($l['latitude']), floatval($l['longitude']));
	}
	if (!$points) {
		return '';
	}
	return marly_carte_fabriquer('commune', $points, 0);
}

/**
 * Fabrique (ou retrouve) l'image d'un ensemble de points.
 *
 * $zoom = 0 : calculé pour que tous les points tiennent dans l'image.
 */
function marly_carte_fabriquer($genre, $points, $zoom) {
	if (!function_exists('imagecreatetruecolor')) {
		spip_log('marly : GD absent, pas de carte-image', 'marly.' . _LOG_INFO_IMPORTANTE);
		return '';
	}

	if (!$zoom) {
		$zoom = marly_carte_zoom_pour($points);
	}

	$dossier = _DIR_VAR . 'cache-marly-cartes/';
	$nom = $genre . '-' . md5($zoom . '|' . serialize($points)
		. '|' . MARLY_CARTE_LARGEUR . 'x' . MARLY_CARTE_HAUTEUR) . '.png';
	if (file_exists($dossier . $nom)) {
		return $dossier . $nom;
	}

	$image = marly_carte_composer($points, $zoom, 'marly_carte_tuile');
	if (!$image) {
		return '';
	}

	if (!is_dir($dossier)) {
		@mkdir($dossier, 0755, true);
	}
	imagepng($image, $dossier . $nom);
	imagedestroy($image);
	spip_log("marly : carte $nom fabriquee (" . count($points) . ' point(s), zoom ' . $zoom . ')',
		'marly.' . _LOG_INFO_IMPORTANTE);

	return file_exists($dossier . $nom) ? $dossier . $nom : '';
}

/**
 * Le zoom le plus serré qui montre tous les points, borné à [12, 16].
 * Borné en bas pour rester à l'échelle du village, en haut pour garder
 * un peu d'alentour même avec un seul point.
 */
function marly_carte_zoom_pour($points) {
	for ($z = 16; $z > 12; $z--) {
		$xs = $ys = array();
		foreach ($points as $p) {
			list($x, $y) = marly_carte_pixel($p[0], $p[1], $z);
			$xs[] = $x;
			$ys[] = $y;
		}
		/* 60 px de marge : un marqueur au ras du bord semble hors de la carte. */
		if (max($xs) - min($xs) < MARLY_CARTE_LARGEUR - 120
		and max($ys) - min($ys) < MARLY_CARTE_HAUTEUR - 120) {
			return $z;
		}
	}
	return 12;
}

/**
 * La projection : degrés vers pixels du planisphère, au zoom donné.
 */
function marly_carte_pixel($lat, $lon, $zoom) {
	$n = pow(2, $zoom) * 256;
	$x = ($lon + 180) / 360 * $n;
	$r = deg2rad($lat);
	$y = (1 - log(tan($r) + 1 / cos($r)) / M_PI) / 2 * $n;
	return array($x, $y);
}

/**
 * Assemble l'image : tuiles, puis marqueurs.
 *
 * Le chargeur de tuiles est passé en argument : la fabrication se teste
 * ainsi sans réseau, avec des tuiles fabriquées sur place.
 */
function marly_carte_composer($points, $zoom, $charge_tuile) {
	/* Le centre : au milieu des points. */
	$xs = $ys = array();
	foreach ($points as $p) {
		list($x, $y) = marly_carte_pixel($p[0], $p[1], $zoom);
		$xs[] = $x;
		$ys[] = $y;
	}
	$cx = (min($xs) + max($xs)) / 2;
	$cy = (min($ys) + max($ys)) / 2;

	$gauche = (int) round($cx - MARLY_CARTE_LARGEUR / 2);
	$haut   = (int) round($cy - MARLY_CARTE_HAUTEUR / 2);

	$image = imagecreatetruecolor(MARLY_CARTE_LARGEUR, MARLY_CARTE_HAUTEUR);

	/* Les tuiles couvrant le cadre. Si une seule manque, on renonce :
	   une carte trouée ferait douter de tout le site. */
	for ($tx = intdiv($gauche, 256); $tx * 256 < $gauche + MARLY_CARTE_LARGEUR; $tx++) {
		for ($ty = intdiv($haut, 256); $ty * 256 < $haut + MARLY_CARTE_HAUTEUR; $ty++) {
			$tuile = call_user_func($charge_tuile, $zoom, $tx, $ty);
			if (!$tuile) {
				spip_log("marly : tuile $zoom/$tx/$ty introuvable, carte abandonnee",
					'marly.' . _LOG_INFO_IMPORTANTE);
				imagedestroy($image);
				return null;
			}
			imagecopy($image, $tuile, $tx * 256 - $gauche, $ty * 256 - $haut, 0, 0, 256, 256);
			imagedestroy($tuile);
		}
	}

	/* Les marqueurs, aux couleurs de la charte : goutte verte, coeur blanc. */
	$vert  = imagecolorallocate($image, 0x1E, 0x5B, 0x41);
	$blanc = imagecolorallocate($image, 0xFF, 0xFF, 0xFF);
	foreach ($points as $p) {
		list($x, $y) = marly_carte_pixel($p[0], $p[1], $zoom);
		$x = (int) round($x - $gauche);
		$y = (int) round($y - $haut);
		/* La pointe touche le lieu, la tete est au-dessus. */
		imagefilledpolygon($image,
			array($x, $y, $x - 9, $y - 16, $x + 9, $y - 16), $vert);
		imagefilledellipse($image, $x, $y - 20, 24, 24, $vert);
		imagefilledellipse($image, $x, $y - 20, 9, 9, $blanc);
	}

	/* Le credit, grave dans le coin comme sur tout plan. Fond clair sous le
	   texte pour rester lisible quelle que soit la tuile en dessous. */
	$credit = '(c) OpenStreetMap';
	$lc = strlen($credit) * imagefontwidth(1) + 8;
	$hc = imagefontheight(1) + 4;
	$fond = imagecolorallocatealpha($image, 255, 255, 255, 40);
	imagefilledrectangle($image,
		MARLY_CARTE_LARGEUR - $lc, MARLY_CARTE_HAUTEUR - $hc,
		MARLY_CARTE_LARGEUR, MARLY_CARTE_HAUTEUR, $fond);
	$gris = imagecolorallocate($image, 0x55, 0x50, 0x4A);
	imagestring($image, 1,
		MARLY_CARTE_LARGEUR - $lc + 4, MARLY_CARTE_HAUTEUR - $hc + 2,
		$credit, $gris);

	return $image;
}

/**
 * Une tuile, demandée aux serveurs d'OpenStreetMap.
 * Mêmes égards que pour Nominatim : l'en-tête dit qui nous sommes.
 */
function marly_carte_tuile($zoom, $x, $y) {
	include_spip('inc/distant');
	include_spip('inc/config');
	if (!function_exists('recuperer_url')) {
		return null;
	}
	$contact = lire_config('marly/courriel', $GLOBALS['meta']['email_webmaster'] ?? '');
	$reponse = recuperer_url("https://tile.openstreetmap.org/$zoom/$x/$y.png", array(
		'taille_max' => 300000,
		'transcoder' => false,
		'headers'    => array(
			'User-Agent: SitePublicMarlyGomont/1.0 (' . $contact . ')',
		),
	));
	if (empty($reponse['page'])) {
		return null;
	}
	$img = @imagecreatefromstring($reponse['page']);
	return $img ?: null;
}
