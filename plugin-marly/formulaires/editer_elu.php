<?php
/**
 * Un élu, sa délégation, sa permanence.
 *
 * Saisi une fois, proposé ensuite sur chaque fiche démarche.
 */

if (!defined('_ECRIRE_INC_VERSION')) {
	return;
}

function marly_champs_elu() {
	return array('nom', 'prenom', 'fonction', 'delegation',
	             'telephone', 'courriel', 'permanence', 'biographie', 'rang', 'statut');
}

function formulaires_editer_elu_charger_dist($id_elu = 'new') {
	if (!autoriser('modifier', 'salle')) {
		return false;
	}

	$valeurs = array('id_elu' => $id_elu, 'rang' => 100, 'statut' => 'publie');
	foreach (array('nom', 'prenom', 'fonction', 'delegation', 'telephone', 'courriel', 'permanence', 'biographie') as $c) {
		$valeurs[$c] = '';
	}

	if ($id_elu !== 'new' and intval($id_elu)) {
		$elu = sql_fetsel('*', 'spip_elus', 'id_elu = ' . intval($id_elu));
		if ($elu) {
			foreach (marly_champs_elu() as $c) {
				$valeurs[$c] = $elu[$c] ?? $valeurs[$c] ?? '';
			}
		}
	}

	return $valeurs;
}

function formulaires_editer_elu_verifier_dist($id_elu = 'new') {
	$erreurs = array();

	if (!trim((string) _request('nom'))) {
		$erreurs['nom'] = _T('marly:erreur_obligatoire');
	}
	if (!trim((string) _request('fonction'))) {
		$erreurs['fonction'] = _T('marly:erreur_obligatoire');
	}

	$courriel = trim((string) _request('courriel'));
	if ($courriel !== '' and !filter_var($courriel, FILTER_VALIDATE_EMAIL)) {
		$erreurs['courriel'] = _T('marly:erreur_courriel');
	}

	return $erreurs;
}

function formulaires_editer_elu_traiter_dist($id_elu = 'new') {
	if (!autoriser('modifier', 'salle')) {
		return array('message_erreur' => _T('avis_operation_impossible'));
	}

	$champs = array();
	foreach (marly_champs_elu() as $c) {
		$champs[$c] = trim((string) _request($c));
	}
	$champs['rang'] = intval($champs['rang']) ?: 100;
	if (!in_array($champs['statut'], array('publie', 'prepa'), true)) {
		$champs['statut'] = 'publie';
	}

	if ($id_elu === 'new' or !intval($id_elu)) {
		$id_elu = sql_insertq('spip_elus', $champs);
		if (!$id_elu) {
			return array('message_erreur' => _T('marly:erreur_enregistrement'));
		}
	} else {
		sql_updateq('spip_elus', $champs, 'id_elu = ' . intval($id_elu));
	}

	include_spip('inc/marly_outils');
	marly_invalider_cache();

	return array('message_ok' => _T('marly:elu_enregistre'),
	             'redirect' => generer_url_ecrire('elu', 'id_elu=' . $id_elu));
}
