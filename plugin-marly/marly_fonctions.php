<?php
/**
 * Les filtres que les squelettes peuvent appeler.
 * ---------------------------------------------------------------------------
 * SPIP charge ce fichier automatiquement pour tout squelette du site. Il n'y
 * a qu'un filtre ici, et il expose une fonction qui existait deja : le
 * compte des places restantes. Le dupliquer dans le gabarit aurait cree une
 * deuxieme regle a maintenir.
 */

if (!defined('_ECRIRE_INC_VERSION')) {
	return;
}

include_spip('inc/marly_reservations');

function filtre_marly_places_restantes_dist($id_manifestation) {
	$restantes = marly_places_restantes($id_manifestation);
	return ($restantes === PHP_INT_MAX) ? '' : $restantes;
}
