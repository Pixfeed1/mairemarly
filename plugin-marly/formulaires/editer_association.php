<?php
/**
 * Une association de la commune.
 */

if (!defined('_ECRIRE_INC_VERSION')) {
	return;
}

function marly_champs_association() {
	return array('nom', 'theme', 'activite', 'president', 'telephone',
	             'courriel', 'site', 'lieu', 'id_lieu', 'horaires', 'rang', 'statut', 'id_rubrique');
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
		'id_rubrique' => 0,
		'id_lieu' => 0,
	);

	/* Les lieux de la commune, saisis une fois dans leur propre ecran. Le
	   champ libre reste, pour l'exception : une association qui se reunit
	   chez ses membres n'a pas de batiment communal. */
	include_spip('inc/marly_lieux');
	$valeurs['_lieux'] = array(0 => _T('marly:aucun_lieu_choisi'));
	foreach (sql_allfetsel('id_lieu, nom', 'spip_lieux', "statut = 'publie'", '', 'rang, nom') as $l) {
		$valeurs['_lieux'][$l['id_lieu']] = $l['nom'];
	}

	/* Les rubriques du site, pour relier l'association a celle ou elle
	   publiera. Le chemin complet est affiche : dans un site a deux niveaux,
	   deux rubriques peuvent porter le meme titre. */
	$valeurs['_rubriques'] = array(0 => _T('marly:aucune_rubrique'));
	foreach (sql_allfetsel('id_rubrique, titre, id_parent', 'spip_rubriques',
	                       "statut = 'publie'", '', '0+titre, titre') as $r) {
		$chemin = $r['titre'];
		if ($r['id_parent']) {
			$parent = sql_getfetsel('titre', 'spip_rubriques', 'id_rubrique = ' . intval($r['id_parent']));
			if ($parent) {
				$chemin = $parent . ' / ' . $chemin;
			}
		}
		$valeurs['_rubriques'][$r['id_rubrique']] = $chemin;
	}
	foreach (array('nom', 'activite', 'president', 'telephone', 'courriel', 'site', 'lieu', 'horaires') as $c) {
		$valeurs[$c] = '';
	}

	if ($id_association !== 'new' and intval($id_association)) {
		$a = sql_fetsel('*', 'spip_associations', 'id_association = ' . intval($id_association));
		if ($a) {
			/* Une colonne peut manquer : les fichiers sont deployes avant que
			   la mise a jour de la base n'ait tourne, et entre les deux la
			   table a une colonne de retard. Le formulaire garde alors sa
			   valeur par defaut au lieu d'afficher un avertissement PHP en
			   haut de l'ecran. */
			foreach (marly_champs_association() as $c) {
				$valeurs[$c] = $a[$c] ?? $valeurs[$c] ?? '';
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
	$champs['id_rubrique'] = intval($champs['id_rubrique']);
	$champs['id_lieu'] = intval($champs['id_lieu']);
	if (!in_array($champs['statut'], array('publie', 'prepa'), true)) {
		$champs['statut'] = 'publie';
	}

	if ($id_association === 'new' or !intval($id_association)) {
		$id_association = sql_insertq('spip_associations', $champs);
		if (!$id_association) {
			return array('message_erreur' => _T('marly:erreur_enregistrement'));
		}

		/* Sa rubrique est creee dans la foulee. Elle n'apparaitra pas sur le
		   site tant qu'aucun article n'y est publie, donc elle ne coute rien ;
		   et le jour ou l'association veut ecrire, il n'y a rien a preparer. */
		if (!$champs['id_rubrique']) {
			include_spip('inc/marly_associations');
			$id_rubrique = marly_rubrique_association($champs['nom']);
			if ($id_rubrique) {
				sql_updateq('spip_associations', array('id_rubrique' => $id_rubrique),
					'id_association = ' . intval($id_association));
			}
		}
	} else {
		sql_updateq('spip_associations', $champs, 'id_association = ' . intval($id_association));
	}

	return array('message_ok' => _T('marly:association_enregistree'),
	             'redirect' => generer_url_ecrire('associations'));
}
