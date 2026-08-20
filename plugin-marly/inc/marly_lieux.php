<?php
/**
 * Les lieux de la commune.
 * ---------------------------------------------------------------------------
 * Cinq ou six bâtiments qui ne bougeront jamais. Ils étaient jusqu'ici
 * recopiés en toutes lettres dans chaque fiche d'association : « salle des
 * fêtes », « Salle des Fêtes », « salle polyvalente ». Saisis une fois, ils
 * se choisissent ensuite dans une liste.
 */

if (!defined('_ECRIRE_INC_VERSION')) {
	return;
}

/** Les natures de lieu, et l'icône qui va avec. */
function marly_types_lieux() {
	return array(
		'mairie'     => array('marly:type_mairie',     'ri-government-line'),
		'salle'      => array('marly:type_salle',      'ri-home-4-line'),
		'ecole'      => array('marly:type_ecole',      'ri-school-line'),
		'culte'      => array('marly:type_culte',      'ri-building-2-line'),
		'sport'      => array('marly:type_sport',      'ri-run-line'),
		'patrimoine' => array('marly:type_patrimoine', 'ri-ancient-gate-line'),
		'autre'      => array('marly:type_autre',      'ri-map-pin-2-line'),
	);
}

/** Les natures traduites, prêtes pour un menu déroulant. */
function marly_types_traduits() {
	$out = array();
	foreach (marly_types_lieux() as $cle => $def) {
		$out[$cle] = _T($def[0]);
	}
	return $out;
}

/**
 * Le cadrage de la carte, calculé sur les lieux qui ont des coordonnées.
 *
 * Rend un tableau vide s'il n'y en a aucun : la page n'affiche alors pas de
 * carte du tout, plutôt qu'une carte du milieu de l'océan Atlantique — ce
 * que donne une latitude et une longitude à zéro.
 */
function marly_cadre_carte() {
	$points = sql_allfetsel('latitude, longitude', 'spip_lieux',
		"statut = 'publie' AND latitude != '' AND longitude != ''");

	if (!$points) {
		return array();
	}

	$lats = array_map('floatval', array_column($points, 'latitude'));
	$lons = array_map('floatval', array_column($points, 'longitude'));

	/* Une marge autour des points extrêmes : sans elle, les bâtiments du bord
	   se retrouvent collés au cadre et l'on ne voit pas ce qu'il y a autour. */
	$marge = 0.004;

	return array(
		'ouest' => min($lons) - $marge,
		'sud'   => min($lats) - $marge,
		'est'   => max($lons) + $marge,
		'nord'  => max($lats) + $marge,
		'centre_lat' => (min($lats) + max($lats)) / 2,
		'centre_lon' => (min($lons) + max($lons)) / 2,
	);
}
