<?php
/**
 * Une association de la commune.
 */

if (!defined('_ECRIRE_INC_VERSION')) {
	return;
}

function marly_champs_association() {
	return array('nom', 'theme', 'activite', 'president', 'telephone',
	             'courriel', 'site', 'lieu', 'horaires', 'rang', 'statut');
}

function formulaires_editer_association_charger_dist($id_association = 'new') {
	if (!autoriser('modifier', 'salle')) {
		return false;
	}

	include_spip('inc/marly_associations');

	$valeurs = array(
		'id_association' => $id_association,
		'theme'   => 'culture',
		'rang'    => 100,
		'statut'  => 'publie',
		'_themes' => marly_themes_traduits(),
	);
	foreach (array('nom', 'activite', 'president', 'telephone', 'courriel', 'site', 'lieu', 'horaires') as $c) {
		$valeurs[$c] = '';
	}

	if ($id_association !== 'new' and intval($id_association)) {
		$a = sql_fetsel('*', 'spip_associations', 'id_association = ' . intval($id_association));
		if ($a) {
			foreach (marly_champs_association() as $c) {
				$valeurs[$c] = $a[$c];
			}
		}
	}

	return $valeurs;
}

function formulaires_editer_association_verifier_dist($id_association = 'new') {
	include_spip('inc/marly_associations');
	$erreurs = array();

	if (!trim((string) _request('nom'))) {
		$erreurs['nom'] = _T('marly:erreur_obligatoire');
	}
	if (!trim((string) _request('activite'))) {
		$erreurs['activite'] = _T('marly:erreur_obligatoire');
	}
	if (!array_key_exists(_request('theme'), marly_themes_associations())) {
		$erreurs['theme'] = _T('marly:erreur_obligatoire');
	}

	$courriel = trim((string) _request('courriel'));
	if ($courriel !== '' and !filter_var($courriel, FILTER_VALIDATE_EMAIL)) {
		$erreurs['courriel'] = _T('marly:erreur_courriel');
	}

	$site = trim((string) _request('site'));
	if ($site !== '' and !preg_match(',^https?://.+,i', $site)) {
		$erreurs['site'] = _T('marly:erreur_adresse');
	}

	/* Un annuaire sans moyen de contact ne sert a rien : c'est la seule chose
	   qu'on y cherche. On n'impose pas LEQUEL — telephone, courriel ou site —
	   mais on en exige un. */
	if (!trim((string) _request('telephone')) and $courriel === '' and $site === '') {
		$erreurs['telephone'] = _T('marly:erreur_un_contact');
	}

	return $erreurs;
}

function formulaires_editer_association_traiter_dist($id_association = 'new') {
	if (!autoriser('modifier', 'salle')) {
		return array('message_erreur' => _T('avis_operation_impossible'));
	}

	$champs = array();
	foreach (marly_champs_association() as $c) {
		$champs[$c] = trim((string) _request($c));
	}
	$champs['rang'] = intval($champs['rang']) ?: 100;
	if (!in_array($champs['statut'], array('publie', 'prepa'), true)) {
		$champs['statut'] = 'publie';
	}

	if ($id_association === 'new' or !intval($id_association)) {
		$id_association = sql_insertq('spip_associations', $champs);
		if (!$id_association) {
			return array('message_erreur' => _T('marly:erreur_enregistrement'));
		}
	} else {
		sql_updateq('spip_associations', $champs, 'id_association = ' . intval($id_association));
	}

	return array('message_ok' => _T('marly:association_enregistree'),
	             'redirect' => generer_url_ecrire('associations'));
}
