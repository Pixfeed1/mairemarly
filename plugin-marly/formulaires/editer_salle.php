<?php
/**
 * Créer et modifier une salle, depuis l'espace privé.
 * ---------------------------------------------------------------------------
 * Une salle porte ses propres règles — tarifs, caution, délais — parce
 * qu'elles ne sont pas les mêmes d'une salle à l'autre. Les écrire dans le
 * code aurait voulu dire m'appeler pour changer un tarif.
 */

if (!defined('_ECRIRE_INC_VERSION')) {
	return;
}

function marly_champs_salle() {
	return array(
		'titre', 'descriptif', 'capacite',
		'tarif_commune', 'tarif_hors_commune', 'caution',
		'delai_min', 'delai_max', 'statut',
	);
}

function formulaires_editer_salle_charger_dist($id_salle = 'new') {
	if (!autoriser('modifier', 'salle')) {
		return false;
	}

	$valeurs = array(
		'id_salle'           => $id_salle,
		'titre'              => '',
		'descriptif'         => '',
		'capacite'           => '',
		'tarif_commune'      => '',
		'tarif_hors_commune' => '',
		'caution'            => '',
		'delai_min'          => 3,
		'delai_max'          => 365,
		'statut'             => 'prepa',
	);

	if ($id_salle !== 'new' and intval($id_salle)) {
		$salle = sql_fetsel('*', 'spip_salles', 'id_salle = ' . intval($id_salle));
		if ($salle) {
			foreach (marly_champs_salle() as $champ) {
				$valeurs[$champ] = $salle[$champ];
			}
		}
	}

	return $valeurs;
}

function formulaires_editer_salle_verifier_dist($id_salle = 'new') {
	$erreurs = array();

	if (!trim((string) _request('titre'))) {
		$erreurs['titre'] = _T('marly:erreur_obligatoire');
	}

	foreach (array('delai_min', 'delai_max') as $champ) {
		$v = _request($champ);
		if ($v !== '' && (!ctype_digit((string) $v) || intval($v) < 0)) {
			$erreurs[$champ] = _T('marly:erreur_nombre');
		}
	}

	if (!isset($erreurs['delai_min']) && !isset($erreurs['delai_max'])
		&& intval(_request('delai_min')) > intval(_request('delai_max'))) {
		$erreurs['delai_max'] = _T('marly:erreur_delais_croises');
	}

	return $erreurs;
}

function formulaires_editer_salle_traiter_dist($id_salle = 'new') {
	$champs = array();
	foreach (marly_champs_salle() as $champ) {
		$champs[$champ] = trim((string) _request($champ));
	}
	$champs['capacite']  = intval($champs['capacite']);
	$champs['delai_min'] = intval($champs['delai_min']);
	$champs['delai_max'] = intval($champs['delai_max']);
	if (!in_array($champs['statut'], array('prepa', 'publie'), true)) {
		$champs['statut'] = 'prepa';
	}

	if ($id_salle === 'new' or !intval($id_salle)) {
		$id_salle = sql_insertq('spip_salles', $champs);
		if (!$id_salle) {
			return array('message_erreur' => _T('marly:erreur_enregistrement'));
		}
	} else {
		sql_updateq('spip_salles', $champs, 'id_salle = ' . intval($id_salle));
	}

	return array(
		'message_ok'    => _T('marly:ressource_enregistree'),
		'id_salle'      => $id_salle,
		'redirect'      => generer_url_ecrire('ressources'),
	);
}
