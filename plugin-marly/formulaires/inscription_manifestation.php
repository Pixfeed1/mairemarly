<?php
/**
 * S'inscrire à une manifestation.
 * ---------------------------------------------------------------------------
 * Plus court que la réservation de salle, et c'est voulu : quelqu'un qui
 * s'inscrit au repas des aînés ne doit pas remplir un dossier. Nom, contact,
 * nombre de places. Le reste, la mairie le sait déjà ou le demandera.
 */

if (!defined('_ECRIRE_INC_VERSION')) {
	return;
}

include_spip('inc/marly_reservations');

function formulaires_inscription_manifestation_charger_dist($id_manifestation = 0) {
	$manif = sql_fetsel('*', 'spip_manifestations',
		'id_manifestation = ' . intval($id_manifestation));
	if (!$manif or marly_inscriptions_ouvertes($manif) !== 'ouvert') {
		return false;
	}

	return array(
		'id_manifestation' => $id_manifestation,
		'_manif'           => $manif,
		'_restantes'       => marly_places_restantes($id_manifestation),
		'places'           => 1,
		'nom'              => '',
		'courriel'         => '',
		'telephone'        => '',
		'motif'            => '',
	);
}

function formulaires_inscription_manifestation_verifier_dist($id_manifestation = 0) {
	$erreurs = array();

	foreach (array('nom', 'courriel') as $obligatoire) {
		if (!trim((string) _request($obligatoire))) {
			$erreurs[$obligatoire] = _T('marly:erreur_obligatoire');
		}
	}

	$courriel = trim((string) _request('courriel'));
	if ($courriel && !filter_var($courriel, FILTER_VALIDATE_EMAIL)) {
		$erreurs['courriel'] = _T('marly:erreur_courriel');
	}

	$places = _request('places');
	if (!ctype_digit((string) $places) or intval($places) < 1) {
		$erreurs['places'] = _T('marly:erreur_places');
	}

	return $erreurs;
}

function formulaires_inscription_manifestation_traiter_dist($id_manifestation = 0) {
	$resultat = marly_inscrire(
		$id_manifestation,
		intval(_request('places')),
		array(
			'nom'       => trim((string) _request('nom')),
			'organisme' => trim((string) _request('organisme')),
			'courriel'  => trim((string) _request('courriel')),
			'telephone' => trim((string) _request('telephone')),
			'motif'     => trim((string) _request('motif')),
		)
	);

	/* Le message d'erreur remonte du modèle, pas du formulaire : c'est là
	   qu'on a compté les places, et c'est là seulement qu'on sait pourquoi
	   ça n'a pas marché. */
	if (isset($resultat['erreur'])) {
		return array('message_erreur' => $resultat['erreur']);
	}

	$manif = sql_fetsel('validation', 'spip_manifestations',
		'id_manifestation = ' . intval($id_manifestation));

	return array(
		'message_ok' => ($manif['validation'] === 'auto')
			? _T('marly:inscription_confirmee')
			: _T('marly:demande_enregistree'),
		'editable'   => false,
	);
}
