<?php
/**
 * Un commerce ou un service du village.
 * ---------------------------------------------------------------------------
 * Décalqué du formulaire des associations, moins ce qu'un commerce n'a pas :
 * ni rubrique où publier, ni compte rédacteur, ni préinscription. Un
 * commerçant ne rédige pas sur le site de la mairie ; il veut que son numéro
 * et ses horaires soient justes.
 */

if (!defined('_ECRIRE_INC_VERSION')) {
	return;
}

function marly_champs_commerce() {
	return array('nom', 'categorie', 'activite', 'responsable', 'telephone',
	             'courriel', 'site', 'lieu', 'latitude', 'longitude',
	             'horaires', 'rang', 'statut');
}

function formulaires_editer_commerce_charger_dist($id_commerce = 'new') {
	if (!autoriser('modifier', 'salle')) {
		return false;
	}

	include_spip('inc/marly_commerces');

	$valeurs = array(
		'id_commerce' => $id_commerce,
		'categorie'   => 'commerce',
		'rang'        => 100,
		'statut'      => 'publie',
		'_categories' => marly_categories_traduites(),
	);
	foreach (array('nom', 'activite', 'responsable', 'telephone', 'courriel',
	               'site', 'lieu', 'latitude', 'longitude', 'horaires') as $c) {
		$valeurs[$c] = '';
	}

	if ($id_commerce !== 'new' and intval($id_commerce)) {
		$a = sql_fetsel('*', 'spip_commerces', 'id_commerce = ' . intval($id_commerce));
		if ($a) {
			/* Une colonne peut manquer : les fichiers sont deployes avant que
			   la mise a jour de la base n'ait tourne, et entre les deux la
			   table a une colonne de retard. Le formulaire garde alors sa
			   valeur par defaut au lieu d'afficher un avertissement PHP. */
			foreach (marly_champs_commerce() as $c) {
				$valeurs[$c] = $a[$c] ?? $valeurs[$c] ?? '';
			}
		}
	}

	/* L'adresse du service qui cherche les coordonnees pour nous. Signee par
	   SPIP : elle ne vaut que pour la session ouverte, et l'action verifie
	   l'autorisation une seconde fois de son cote. */
	include_spip('inc/actions');
	$valeurs['_url_chercher'] = str_replace('&amp;', '&',
		generer_action_auteur('marly_chercher_adresse', ''));

	return $valeurs;
}

function formulaires_editer_commerce_verifier_dist($id_commerce = 'new') {
	include_spip('inc/marly_commerces');
	$erreurs = array();

	if (!trim((string) _request('nom'))) {
		$erreurs['nom'] = _T('marly:erreur_obligatoire');
	}
	if (!trim((string) _request('activite'))) {
		$erreurs['activite'] = _T('marly:erreur_obligatoire');
	}
	if (!array_key_exists(_request('categorie'), marly_categories_commerces())) {
		$erreurs['categorie'] = _T('marly:erreur_obligatoire');
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

function formulaires_editer_commerce_traiter_dist($id_commerce = 'new') {
	if (!autoriser('modifier', 'salle')) {
		return array('message_erreur' => _T('avis_operation_impossible'));
	}

	$champs = array();
	foreach (marly_champs_commerce() as $c) {
		$champs[$c] = trim((string) _request($c));
	}
	$champs['rang'] = intval($champs['rang']) ?: 100;
	if (!in_array($champs['statut'], array('publie', 'prepa'), true)) {
		$champs['statut'] = 'publie';
	}

	/* L'adresse suffit : les coordonnees s'en deduisent a l'enregistrement.
	   C'est le serveur qui interroge OpenStreetMap, pas le visiteur — aucune
	   donnee d'habitant ne part.

	   On ne cherche que si l'adresse a CHANGE, ou s'il n'y a pas encore de
	   coordonnees : sans cela, corriger un horaire relancerait une requete
	   pour une adresse identique. */
	$ancien = ($id_commerce !== 'new' and intval($id_commerce))
		? sql_getfetsel('lieu', 'spip_commerces', 'id_commerce = ' . intval($id_commerce))
		: null;

	/* Un point choisi dans la liste des propositions ne se rediscute pas :
	   la mairie a vu l'adresse et a clique dessus. Chercher a nouveau
	   ecraserait son choix par une devinette. */
	$choisi = (_request('point_choisi') === '1'
		and $champs['latitude'] !== '' and $champs['longitude'] !== '');

	$cherchees = false;
	$precis = $choisi;
	if (!$choisi and $champs['lieu'] !== '' and ($champs['latitude'] === '' or $champs['lieu'] !== $ancien)) {
		include_spip('inc/marly_geocodage');
		$cherchees = true;
		$point = marly_geocoder($champs['lieu']);
		$champs['latitude']  = $point['latitude'] ?? '';
		$champs['longitude'] = $point['longitude'] ?? '';
		$precis = !empty($point['precis']);
	}
	if ($champs['lieu'] === '') {
		$champs['latitude'] = $champs['longitude'] = '';
	}

	if ($id_commerce === 'new' or !intval($id_commerce)) {
		$id_commerce = sql_insertq('spip_commerces', $champs);
		if (!$id_commerce) {
			return array('message_erreur' => _T('marly:erreur_enregistrement'));
		}
	} else {
		sql_updateq('spip_commerces', $champs, 'id_commerce = ' . intval($id_commerce));
	}

	/* On dit ce qui s'est passe. Un enregistrement muet laissait croire que
	   l'adresse avait ete localisee : la secretaire voyait << Enregistre >>,
	   aucune carte n'apparaissait sur le site, et rien n'expliquait l'ecart. */
	if ($champs['latitude'] !== '' and $precis) {
		$message = _T('marly:commerce_enregistre_localise');
	} elseif ($champs['latitude'] !== '') {
		$message = _T('marly:commerce_enregistre_approche');
	} elseif ($cherchees) {
		$message = _T('marly:commerce_enregistre_non_localise');
	} else {
		$message = _T('marly:commerce_enregistre');
	}

	include_spip('inc/marly_outils');
	marly_invalider_cache();

	return array('message_ok' => $message,
	             'redirect' => generer_url_ecrire('commerce', 'id_commerce=' . $id_commerce));
}
