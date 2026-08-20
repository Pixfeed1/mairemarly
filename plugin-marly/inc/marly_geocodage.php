<?php
/**
 * Trouver les coordonnées d'une adresse.
 * ---------------------------------------------------------------------------
 * Interroge Nominatim, le service de recherche d'OpenStreetMap. C'est LE
 * SERVEUR qui l'interroge, à l'enregistrement d'un lieu, et non le visiteur :
 * aucune donnée d'habitant ne part, contrairement à une carte affichée dans
 * la page.
 *
 * Nominatim est gratuit et sans clé, mais il pose des conditions d'usage :
 * une requête par seconde au plus, et un en-tête qui identifie clairement
 * l'application. On les respecte — une commune qui saisit cinq bâtiments en
 * fait cinq requêtes dans sa vie, on est très loin des limites.
 *
 * L'échec est sans conséquence : le lieu est enregistré sans coordonnées,
 * il figure dans la liste de la page « Où nous trouver » mais pas sur la
 * carte, et la mairie peut toujours les saisir à la main.
 */

if (!defined('_ECRIRE_INC_VERSION')) {
	return;
}

/**
 * Rend array('latitude' => …, 'longitude' => …), ou un tableau vide.
 *
 * L'adresse est complétée par la commune et le code postal pris dans les
 * réglages : « salle des fêtes » seul ne veut rien dire pour un service qui
 * couvre la planète, alors que « salle des fêtes, 02120 Marly-Gomont » le
 * situe.
 */
function marly_geocoder($adresse) {
	$adresse = trim(preg_replace('/\s+/', ' ', str_replace("\n", ', ', (string) $adresse)));
	if ($adresse === '') {
		return array();
	}

	include_spip('inc/config');
	$ville = lire_config('marly/ville', '');
	$cp    = lire_config('marly/code_postal', '');

	/* On n'ajoute la commune que si l'adresse ne la contient pas déjà : la
	   répéter fait chuter la pertinence du résultat. */
	$requete = $adresse;
	if ($ville and stripos($adresse, $ville) === false) {
		$requete .= ', ' . trim($cp . ' ' . $ville);
	}

	$url = 'https://nominatim.openstreetmap.org/search?format=jsonv2&limit=1&countrycodes=fr&q='
		. rawurlencode($requete);

	include_spip('inc/distant');
	if (!function_exists('recuperer_url')) {
		spip_log('marly : recuperer_url indisponible, geocodage impossible', 'marly');
		return array();
	}

	/* L'en-tête identifie le site et donne une adresse de contact : c'est ce
	   que demandent les conditions d'usage de Nominatim, et c'est ce qui
	   permet qu'on nous prévienne plutôt qu'on nous bloque. */
	$contact = lire_config('marly/courriel', $GLOBALS['meta']['email_webmaster'] ?? '');
	/* Les en-tetes se passent en TABLEAU, une ligne par entree : SPIP boucle
	   dessus. En chaine, il tombe sur un foreach() qui recoit du texte, et
	   l'avertissement PHP sort au milieu de la page. */
	$reponse = recuperer_url($url, array(
		'taille_max' => 40000,
		'transcoder' => false,
		'headers'    => array(
			'User-Agent: SitePublicMarlyGomont/1.0 (' . $contact . ')',
			'Accept-Language: fr',
		),
	));

	$json = json_decode($reponse['page'] ?? '', true);
	if (!is_array($json) or !isset($json[0]['lat'], $json[0]['lon'])) {
		spip_log("marly : adresse non localisee — $requete", 'marly');
		return array();
	}

	spip_log("marly : $requete -> {$json[0]['lat']}, {$json[0]['lon']}", 'marly');

	return array(
		'latitude'  => substr((string) $json[0]['lat'], 0, 12),
		'longitude' => substr((string) $json[0]['lon'], 0, 12),
	);
}
