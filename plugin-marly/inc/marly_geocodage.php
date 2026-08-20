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
 * Rend array('latitude' => …, 'longitude' => …, 'precis' => true|false),
 * ou un tableau vide si rien n'a été trouvé.
 *
 * La recherche se fait en trois passes de plus en plus larges, parce qu'une
 * adresse écrite par un humain n'est presque jamais celle qu'OpenStreetMap
 * connaît. Le cas qui a servi de test : « Église Saint-Remi, Marly-Gomont »
 * ne rend RIEN, alors que « eglise Marly-Gomont » rend le bâtiment exact.
 * OpenStreetMap connaît l'église, pas son vocable. Une secrétaire écrira
 * toujours le nom d'usage, et refuser cette écriture serait lui demander de
 * deviner un vocabulaire qu'elle n'a pas à connaître.
 *
 *   1. l'adresse telle qu'elle est écrite ;
 *   2. la même sans les virgules — Nominatim les lit comme une hiérarchie
 *      stricte, et un intitulé qu'il ignore fait échouer le tout ;
 *   3. la commune seule, qui situe au moins le village.
 *
 * La troisième passe rend un point APPROCHÉ, d'où l'indicateur « precis » :
 * l'écran d'enregistrement le dit à la mairie plutôt que de laisser croire
 * que le marqueur est sur le bâtiment.
 */
function marly_geocoder($adresse) {
	$adresse = trim(preg_replace('/\s+/', ' ', str_replace("\n", ', ', (string) $adresse)));
	if ($adresse === '') {
		return array();
	}

	include_spip('inc/config');
	$ville = lire_config('marly/ville', '');
	$cp    = lire_config('marly/code_postal', '');

	/* Sans commune, tout se degrade en silence : les adresses partent sans
	   leur ville, et la passe de repli n'existe pas. Le dire ici evite de
	   chercher la panne dans le geocodage alors qu'elle est dans un champ
	   vide de l'ecran de configuration. */
	if (!$ville) {
		spip_log('marly : commune absente de la configuration, le geocodage '
			. 'travaille sans elle et sans repli — voir Configuration',
			'marly.' . _LOG_INFO_IMPORTANTE);
	}

	/* On n'ajoute la commune que si l'adresse ne la contient pas déjà : la
	   répéter fait chuter la pertinence du résultat. */
	$complete = $adresse;
	if ($ville and stripos($adresse, $ville) === false) {
		$complete .= ', ' . trim($cp . ' ' . $ville);
	}

	/* Chaque passe dit elle-meme si son resultat vaut pour l'adresse
	   demandee ou seulement pour la commune. Le deduire du rang serait faux
	   le jour ou la commune n'est pas renseignee dans les reglages. */
	$passes = array(array($complete, true));
	$sans_virgule = trim(preg_replace('/\s+/', ' ', str_replace(',', ' ', $complete)));
	if ($sans_virgule !== $complete) {
		$passes[] = array($sans_virgule, true);
	}
	if ($ville) {
		$passes[] = array(trim($cp . ' ' . $ville), false);
	}

	foreach ($passes as $rang => $passe) {
		list($requete, $precis) = $passe;
		/* Nominatim demande une requête par seconde au plus. On n'attend
		   qu'entre deux essais réels : le cas courant, celui qui réussit du
		   premier coup, n'attend pas du tout. */
		if ($rang) {
			usleep(1100000);
		}
		$point = marly_interroger_nominatim($requete);
		if ($point) {
			spip_log("marly : « $requete » -> {$point['latitude']}, {$point['longitude']}"
				. ($precis ? '' : ' (point approche : commune seule)'),
				'marly.' . _LOG_INFO_IMPORTANTE);
			$point['precis'] = $precis;
			return $point;
		}
	}

	spip_log("marly : aucune des " . count($passes) . " recherches n'a localise « $adresse »",
		'marly.' . _LOG_INFO_IMPORTANTE);
	return array();
}

/**
 * Une interrogation de Nominatim. Rend les coordonnées, ou un tableau vide.
 */
function marly_interroger_nominatim($requete) {
	include_spip('inc/config');
	include_spip('inc/distant');
	if (!function_exists('recuperer_url')) {
		spip_log('marly : recuperer_url indisponible, geocodage impossible',
			'marly.' . _LOG_INFO_IMPORTANTE);
		return array();
	}

	$json = marly_appeler_nominatim($requete, 1);
	if (!isset($json[0]['lat'], $json[0]['lon'])) {
		return array();
	}

	return array(
		'latitude'  => substr((string) $json[0]['lat'], 0, 12),
		'longitude' => substr((string) $json[0]['lon'], 0, 12),
	);
}

/**
 * L'appel brut a Nominatim. Rend la liste decodee, ou un tableau vide.
 */
function marly_appeler_nominatim($requete, $limite) {
	include_spip('inc/config');
	include_spip('inc/distant');
	if (!function_exists('recuperer_url')) {
		spip_log('marly : recuperer_url indisponible, geocodage impossible',
			'marly.' . _LOG_INFO_IMPORTANTE);
		return array();
	}

	$url = 'https://nominatim.openstreetmap.org/search?format=jsonv2&countrycodes=fr'
		. '&limit=' . intval($limite) . '&q=' . rawurlencode($requete);

	/* L'en-tête identifie le site et donne une adresse de contact : c'est ce
	   que demandent les conditions d'usage de Nominatim, et c'est ce qui
	   permet qu'on nous prévienne plutôt qu'on nous bloque.

	   Les en-tetes se passent en TABLEAU, une ligne par entree : SPIP boucle
	   dessus. En chaine, il tombe sur un foreach() qui recoit du texte, et
	   l'avertissement PHP sort au milieu de la page. */
	$contact = lire_config('marly/courriel', $GLOBALS['meta']['email_webmaster'] ?? '');
	$reponse = recuperer_url($url, array(
		'taille_max' => 40000,
		'transcoder' => false,
		'headers'    => array(
			'User-Agent: SitePublicMarlyGomont/1.0 (' . $contact . ')',
			'Accept-Language: fr',
		),
	));

	$json = json_decode($reponse['page'] ?? '', true);

	return is_array($json) ? $json : array();
}

/**
 * Cherche une adresse et rend les propositions, pour que la mairie choisisse.
 *
 * C'est la bonne façon de faire, et elle est arrivée après deux détours.
 * Deviner l'adresse à la place de la personne qui la saisit oblige à
 * l'expliquer quand la devinette rate ; lui montrer ce qu'OpenStreetMap
 * connaît et la laisser cliquer ne demande aucune explication. Personne n'a
 * à savoir sous quel nom un bâtiment est enregistré : il suffit de le
 * reconnaître dans une liste.
 *
 * L'appel part du SERVEUR, pas du navigateur de la mairie : c'est ce qui
 * permet de tenir l'en-tête d'identification exigé par Nominatim, et de ne
 * pas exposer le poste de la secrétaire au service tiers.
 *
 * Une requête par clic sur le bouton, jamais à la frappe : les conditions
 * d'usage de Nominatim excluent explicitement la saisie assistée au
 * caractère.
 */
function marly_chercher_adresses($recherche, $limite = 5) {
	$recherche = trim(preg_replace('/\s+/', ' ', str_replace("\n", ', ', (string) $recherche)));
	if ($recherche === '') {
		return array();
	}

	include_spip('inc/config');
	$ville = lire_config('marly/ville', '');
	$cp    = lire_config('marly/code_postal', '');
	if ($ville and stripos($recherche, $ville) === false) {
		$recherche .= ', ' . trim($cp . ' ' . $ville);
	}

	$json = marly_appeler_nominatim($recherche, intval($limite) ?: 5);
	$propositions = array();
	foreach ($json as $trouve) {
		if (!isset($trouve['lat'], $trouve['lon'])) {
			continue;
		}
		$propositions[] = array(
			'intitule'  => (string) ($trouve['display_name'] ?? $recherche),
			'latitude'  => substr((string) $trouve['lat'], 0, 12),
			'longitude' => substr((string) $trouve['lon'], 0, 12),
		);
	}

	spip_log('marly : « ' . $recherche . ' » -> ' . count($propositions) . ' proposition(s)',
		'marly.' . _LOG_INFO_IMPORTANTE);

	return $propositions;
}
