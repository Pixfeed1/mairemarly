<?php
/**
 * Cherche une adresse chez OpenStreetMap et rend les propositions en JSON.
 * ---------------------------------------------------------------------------
 * Appelée depuis les écrans de saisie d'un lieu ou d'une association, quand
 * la mairie clique sur « Chercher cette adresse ». Elle ne modifie rien : sa
 * seule fonction est de rendre une liste dans laquelle choisir.
 *
 * C'est LE SERVEUR qui interroge Nominatim, jamais le navigateur de la
 * mairie. Deux raisons : l'en-tête d'identification exigé par les conditions
 * d'usage ne peut être posé que là, et le poste de la secrétaire n'a pas à
 * être connu d'un service tiers.
 */

if (!defined('_ECRIRE_INC_VERSION')) {
	return;
}

function action_marly_chercher_adresse_dist() {
	/* Même autorisation que les écrans d'où part la recherche : ce qui
	   protège la saisie protège la recherche qui l'assiste. */
	if (!autoriser('modifier', 'salle')) {
		http_response_code(403);
		echo json_encode(array('erreur' => 'non autorise'));
		exit;
	}

	include_spip('inc/marly_geocodage');
	$propositions = marly_chercher_adresses(_request('q'), 5);

	header('Content-Type: application/json; charset=utf-8');
	echo json_encode(array('propositions' => $propositions));
	exit;
}
