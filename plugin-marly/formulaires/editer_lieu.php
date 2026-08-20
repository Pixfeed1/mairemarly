<?php
/**
 * Un lieu de la commune.
 */

if (!defined('_ECRIRE_INC_VERSION')) {
	return;
}

function marly_champs_lieu() {
	return array('nom', 'type', 'adresse', 'latitude', 'longitude',
	             'horaires', 'descriptif', 'rang', 'statut');
}

function formulaires_editer_lieu_charger_dist($id_lieu = 'new') {
	if (!autoriser('modifier', 'salle')) {
		return false;
	}

	include_spip('inc/marly_lieux');

	$valeurs = array('id_lieu' => $id_lieu, 'type' => 'salle', 'rang' => 100,
	                 'statut' => 'publie', '_types' => marly_types_traduits());
	foreach (array('nom', 'adresse', 'latitude', 'longitude', 'horaires', 'descriptif') as $c) {
		$valeurs[$c] = '';
	}

	if ($id_lieu !== 'new' and intval($id_lieu)) {
		$l = sql_fetsel('*', 'spip_lieux', 'id_lieu = ' . intval($id_lieu));
		if ($l) {
			foreach (marly_champs_lieu() as $c) {
				$valeurs[$c] = $l[$c] ?? $valeurs[$c] ?? '';
			}
		}
	}

	return $valeurs;
}

function formulaires_editer_lieu_verifier_dist($id_lieu = 'new') {
	include_spip('inc/marly_lieux');
	$erreurs = array();

	if (!trim((string) _request('nom'))) {
		$erreurs['nom'] = _T('marly:erreur_obligatoire');
	}
	if (!array_key_exists(_request('type'), marly_types_lieux())) {
		$erreurs['type'] = _T('marly:erreur_obligatoire');
	}

	/* Les coordonnées vont par deux, ou pas du tout : une latitude sans
	   longitude place le lieu au large des côtes africaines, sans que rien
	   ne s'en plaigne. */
	$lat = trim((string) _request('latitude'));
	$lon = trim((string) _request('longitude'));
	if (($lat === '') !== ($lon === '')) {
		$erreurs[$lat === '' ? 'latitude' : 'longitude'] = _T('marly:erreur_coordonnees_paire');
	}
	foreach (array('latitude' => $lat, 'longitude' => $lon) as $champ => $valeur) {
		if ($valeur !== '' and !preg_match('/^-?\d{1,3}([.,]\d+)?$/', $valeur)) {
			$erreurs[$champ] = _T('marly:erreur_coordonnee');
		}
	}
	/* La France métropolitaine tient entre 41 et 52 degrés de latitude et
	   entre -5 et 10 de longitude. Hors de là, c'est que les deux nombres ont
	   été intervertis, ce qui est l'erreur la plus courante. */
	if ($lat !== '' and $lon !== '' and empty($erreurs)) {
		$la = floatval(str_replace(',', '.', $lat));
		$lo = floatval(str_replace(',', '.', $lon));
		if ($la < 41 or $la > 52 or $lo < -5 or $lo > 10) {
			$erreurs['latitude'] = _T('marly:erreur_coordonnees_hors_france');
		}
	}

	return $erreurs;
}

function formulaires_editer_lieu_traiter_dist($id_lieu = 'new') {
	if (!autoriser('modifier', 'salle')) {
		return array('message_erreur' => _T('avis_operation_impossible'));
	}

	$champs = array();
	foreach (marly_champs_lieu() as $c) {
		$champs[$c] = trim((string) _request($c));
	}
	/* La virgule décimale est ce que tape un clavier français ; le point est
	   ce qu'attend une carte. On convertit, plutôt que de le reprocher. */
	$champs['latitude']  = str_replace(',', '.', $champs['latitude']);
	$champs['longitude'] = str_replace(',', '.', $champs['longitude']);
	$champs['rang'] = intval($champs['rang']) ?: 100;
	if (!in_array($champs['statut'], array('publie', 'prepa'), true)) {
		$champs['statut'] = 'publie';
	}

	/* Les coordonnées se cherchent toutes seules à partir de l'adresse. On ne
	   le fait QUE si elles sont vides : une correction faite à la main ne doit
	   jamais être écrasée par un service automatique. */
	$trouvees = false;
	if ($champs['latitude'] === '' and $champs['longitude'] === '' and $champs['adresse'] !== '') {
		include_spip('inc/marly_geocodage');
		$point = marly_geocoder($champs['adresse']);
		if ($point) {
			$champs['latitude']  = $point['latitude'];
			$champs['longitude'] = $point['longitude'];
			$trouvees = true;
		}
	}

	if ($id_lieu === 'new' or !intval($id_lieu)) {
		$id_lieu = sql_insertq('spip_lieux', $champs);
		if (!$id_lieu) {
			return array('message_erreur' => _T('marly:erreur_enregistrement'));
		}
	} else {
		sql_updateq('spip_lieux', $champs, 'id_lieu = ' . intval($id_lieu));
	}

	/* On dit ce qui s'est passé. Un enregistrement muet laisse croire que les
	   coordonnées ont été trouvées alors qu'elles ne l'ont peut-être pas été,
	   et le lieu manquerait sur la carte sans que personne le sache. */
	if ($trouvees) {
		$message = _T('marly:lieu_enregistre_localise');
	} elseif ($champs['latitude'] === '' and $champs['adresse'] !== '') {
		$message = _T('marly:lieu_enregistre_non_localise');
	} else {
		$message = _T('marly:lieu_enregistre');
	}

	return array('message_ok' => $message,
	             'redirect' => generer_url_ecrire('lieux'));
}
