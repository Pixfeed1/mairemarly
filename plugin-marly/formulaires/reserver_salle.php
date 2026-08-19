<?php
/**
 * Le formulaire public de demande de réservation.
 * ---------------------------------------------------------------------------
 * Ce formulaire crée une DEMANDE, il ne réserve pas. La distinction est
 * volontaire et elle est dite à l'usager : la mairie garde la main sur qui
 * obtient la salle, ce qui est le fonctionnement réel d'une commune — les
 * associations sont prioritaires, certains usages sont refusés.
 *
 * Il refuse en revanche tout de suite ce qui est manifestement impossible :
 * une date déjà prise, une date passée, un délai hors des règles de la salle.
 * Laisser passer une demande vouée au refus, c'est faire attendre quelqu'un
 * pour rien.
 */

if (!defined('_ECRIRE_INC_VERSION')) {
	return;
}

include_spip('inc/marly_reservations');

function formulaires_reserver_salle_charger_dist($id_salle = 0) {
	$salle = sql_fetsel('*', 'spip_salles',
		'id_salle = ' . intval($id_salle) . " AND statut = 'publie'");
	if (!$salle) {
		return false;   /* pas de salle, pas de formulaire */
	}

	return array(
		'id_salle'   => $id_salle,
		'_salle'     => $salle,
		'date_jour'  => '',
		'heure_debut' => '09:00',
		'heure_fin'  => '23:00',
		'nom'        => '',
		'organisme'  => '',
		'courriel'   => '',
		'telephone'  => '',
		'motif'      => '',
	);
}

function formulaires_reserver_salle_verifier_dist($id_salle = 0) {
	$erreurs = array();

	foreach (array('nom', 'courriel', 'telephone', 'date_jour', 'motif') as $obligatoire) {
		if (!trim((string) _request($obligatoire))) {
			$erreurs[$obligatoire] = _T('marly:erreur_obligatoire');
		}
	}

	$courriel = trim((string) _request('courriel'));
	if ($courriel && !filter_var($courriel, FILTER_VALIDATE_EMAIL)) {
		$erreurs['courriel'] = _T('marly:erreur_courriel');
	}

	$jour  = trim((string) _request('date_jour'));
	$debut = trim((string) _request('heure_debut'));
	$fin   = trim((string) _request('heure_fin'));

	if (!$jour || isset($erreurs['date_jour'])) {
		return $erreurs;
	}
	if (!preg_match(',^\d{4}-\d{2}-\d{2}$,', $jour)) {
		$erreurs['date_jour'] = _T('marly:erreur_date');
		return $erreurs;
	}
	if (!preg_match(',^\d{2}:\d{2}$,', $debut) || !preg_match(',^\d{2}:\d{2}$,', $fin)) {
		$erreurs['heure_debut'] = _T('marly:erreur_heure');
		return $erreurs;
	}
	if ($fin <= $debut) {
		$erreurs['heure_fin'] = _T('marly:erreur_ordre_heures');
		return $erreurs;
	}

	$salle = sql_fetsel('*', 'spip_salles', 'id_salle = ' . intval($id_salle));
	if (!$salle) {
		$erreurs['message_erreur'] = _T('marly:erreur_introuvable');
		return $erreurs;
	}

	$sql_debut = "$jour $debut:00";
	$sql_fin   = "$jour $fin:00";

	if ($message = marly_verifier_delais($salle, $sql_debut)) {
		$erreurs['date_jour'] = $message;
		return $erreurs;
	}

	if ($conflits = marly_conflits($id_salle, $sql_debut, $sql_fin)) {
		$erreurs['date_jour'] = _T('marly:erreur_deja_pris');
		return $erreurs;
	}

	return $erreurs;
}

function formulaires_reserver_salle_traiter_dist($id_salle = 0) {
	$jour  = trim((string) _request('date_jour'));
	$debut = trim((string) _request('heure_debut'));
	$fin   = trim((string) _request('heure_fin'));

	$jeton = md5(uniqid((string) mt_rand(), true));

	$id = sql_insertq('spip_reservations', array(
		'id_salle'   => intval($id_salle),
		'date_debut' => "$jour $debut:00",
		'date_fin'   => "$jour $fin:00",
		'statut'     => 'demande',
		'nom'        => trim((string) _request('nom')),
		'organisme'  => trim((string) _request('organisme')),
		'courriel'   => trim((string) _request('courriel')),
		'telephone'  => trim((string) _request('telephone')),
		'motif'      => trim((string) _request('motif')),
		'jeton'      => $jeton,
		'date'       => date('Y-m-d H:i:s'),
	));

	if (!$id) {
		return array('message_erreur' => _T('marly:erreur_enregistrement'));
	}

	marly_notifier($id, 'demande');

	return array(
		'message_ok' => _T('marly:demande_enregistree'),
		'editable'   => false,   /* on ne repropose pas le formulaire rempli */
	);
}
