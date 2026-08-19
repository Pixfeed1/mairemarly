<?php
/**
 * Qui a le droit de faire quoi.
 * ---------------------------------------------------------------------------
 * Volontairement strict : accepter une réservation engage la commune, et
 * seul un administrateur doit pouvoir le faire. Un rédacteur qui publie des
 * articles n'a pas à attribuer la salle des fêtes.
 */

if (!defined('_ECRIRE_INC_VERSION')) {
	return;
}

function marly_autoriser() {}

function autoriser_reservation_modifier_dist($faire, $type, $id, $qui, $opt) {
	return $qui['statut'] === '0minirezo' && !$qui['restreint'];
}

function autoriser_reservation_voir_dist($faire, $type, $id, $qui, $opt) {
	return in_array($qui['statut'], array('0minirezo', '1comite'), true);
}

function autoriser_reservations_menu_dist($faire, $type, $id, $qui, $opt) {
	return autoriser('voir', 'reservation', 0, $qui);
}

function autoriser_salle_modifier_dist($faire, $type, $id, $qui, $opt) {
	return $qui['statut'] === '0minirezo' && !$qui['restreint'];
}
