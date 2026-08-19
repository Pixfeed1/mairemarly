<?php
/**
 * Accepter, refuser ou annuler une réservation depuis l'espace privé.
 * ---------------------------------------------------------------------------
 * Passe par une action SPIP, donc par un jeton CGI : sans lui, une simple
 * adresse suffirait à accepter une réservation, et il suffirait qu'un agent
 * connecté clique un lien reçu par courriel.
 */

if (!defined('_ECRIRE_INC_VERSION')) {
	return;
}

function action_traiter_reservation_dist($arg = null) {

	if (is_null($arg)) {
		$securiser = charger_fonction('securiser_action', 'inc');
		$arg = $securiser();
	}

	list($id_reservation, $quoi) = array_pad(explode('-', $arg, 2), 2, '');
	$id_reservation = intval($id_reservation);

	if (!$id_reservation || !autoriser('modifier', 'reservation', $id_reservation)) {
		return;
	}

	include_spip('inc/marly_reservations');
	$reponse = trim((string) _request('reponse'));

	if ($quoi === 'acceptee') {
		$erreur = marly_accepter($id_reservation, $reponse);
	} else {
		$erreur = marly_changer_statut($id_reservation, $quoi, $reponse);
	}

	if ($erreur) {
		/* Le message doit remonter à l'agent : un refus silencieux lui
		   ferait croire que la salle est attribuée. */
		set_request('message_erreur', $erreur);
		spip_log("marly : refus de traitement $id_reservation ($quoi) — $erreur", 'marly');
	}
}
