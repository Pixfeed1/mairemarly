<?php
/**
 * Une association de la commune.
 */

if (!defined('_ECRIRE_INC_VERSION')) {
	return;
}

function marly_champs_association() {
	return array('nom', 'theme', 'activite', 'president', 'telephone',
	             'courriel', 'site', 'lieu', 'latitude', 'longitude',
	             'horaires', 'rang', 'statut', 'id_rubrique');
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
	);

	/* Les lieux deja saisis servent de SUGGESTIONS au champ libre, rien de
	   plus. Un menu ferme aurait oblige a creer le batiment dans un autre
	   ecran avant de pouvoir dire ou l'association se reunit : deux ecrans et
	   une notion de plus, pour une commune qui compte cinq batiments. */
	include_spip('inc/marly_outils');
	$valeurs['_lieux'] = array();
	if (marly_table_prete('spip_lieux')) {
		foreach (sql_allfetsel('nom', 'spip_lieux', "statut = 'publie'", '', 'rang, nom') as $l) {
			$valeurs['_lieux'][] = $l['nom'];
		}
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
	foreach (array('nom', 'activite', 'president', 'telephone', 'courriel', 'site', 'lieu',
	               'latitude', 'longitude', 'horaires') as $c) {
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

	/* L'adresse du service qui cherche pour nous. Signee par SPIP : elle ne
	   vaut que pour la session ouverte, et l'action verifie l'autorisation
	   une seconde fois de son cote. */
	include_spip('inc/actions');
	/* generer_action_auteur ecrit les separateurs en &amp;, forme prevue
	   pour un attribut HTML. Dans du JavaScript, elle donne des parametres
	   nommes amp;arg : SPIP ne reconnait plus l'action et repond une page
	   entiere au lieu du JSON attendu. */
	$valeurs['_url_chercher'] = str_replace('&amp;', '&',
		generer_action_auteur('marly_chercher_adresse', ''));

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
	if (!in_array($champs['statut'], array('publie', 'prepa'), true)) {
		$champs['statut'] = 'publie';
	}

	/* L'adresse suffit : les coordonnees s'en deduisent a l'enregistrement.
	   C'est le serveur qui interroge OpenStreetMap, pas le visiteur — aucune
	   donnee d'habitant ne part.

	   On ne cherche que si le lieu a CHANGE, ou s'il n'y a pas encore de
	   coordonnees : sans cela, chaque modification de l'horaire relancerait
	   une requete pour une adresse identique. */
	$ancien = ($id_association !== 'new' and intval($id_association))
		? sql_getfetsel('lieu', 'spip_associations', 'id_association = ' . intval($id_association))
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

	/* On dit ce qui s'est passe. Un enregistrement muet laissait croire que
	   l'adresse avait ete localisee : la secretaire voyait << Enregistre >>,
	   aucune carte n'apparaissait sur le site, et rien n'expliquait l'ecart.
	   C'est le meme compte rendu que sur les lieux. */
	if ($champs['latitude'] !== '' and $precis) {
		$message = _T('marly:association_enregistree_localisee');
	} elseif ($champs['latitude'] !== '') {
		$message = _T('marly:association_enregistree_approchee');
	} elseif ($cherchees) {
		$message = _T('marly:association_enregistree_non_localisee');
	} else {
		$message = _T('marly:association_enregistree');
	}

	return array('message_ok' => $message,
	             'redirect' => generer_url_ecrire('associations'));
}
