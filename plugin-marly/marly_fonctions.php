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
