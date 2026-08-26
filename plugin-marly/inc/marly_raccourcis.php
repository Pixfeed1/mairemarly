<?php

/**
 * Les six raccourcis de la page d'accueil.
 * ---------------------------------------------------------------------------
 * La destination est écrite « type:valeur ». Une colonne par type de cible
 * aurait laissé cinq colonnes vides sur six à chaque ligne, et il aurait
 * fallu en ajouter une chaque fois qu'une nouvelle sorte de destination
 * apparaît.
 */

if (!defined('_ECRIRE_INC_VERSION')) {
	return;
}

/**
 * Les destinations proposées, prêtes pour un menu déroulant.
 *
 * Tout est offert au choix : les démarches publiées, les rubriques du site,
 * les pages du thème. La secrétaire ne tape jamais d'adresse — sauf pour un
 * site extérieur, qui est le seul cas où personne ne peut deviner à sa place.
 */
function marly_cibles_raccourcis() {
	$cibles = array('' => _T('marly:choisir_destination'));

	include_spip('inc/marly_outils');
	foreach (marly_table_prete('spip_demarches')
	         ? sql_allfetsel('id_demarche, titre', 'spip_demarches',
	                         "statut = 'publie'", '', 'famille, rang, titre')
	         : array() as $d) {
		$cibles['demarche:' . $d['id_demarche']] = _T('marly:cible_demarche') . ' — ' . $d['titre'];
	}

	/* LES SOUS-RUBRIQUES AUSSI, et pas seulement celles de premier niveau.
	   La premiere version ne proposait que les racines : le jour ou Urbanisme
	   et Travaux et projets sont devenus des sous-rubriques de Ma mairie, ils
	   ont disparu de la liste, et il ne restait qu'a taper leur adresse a la
	   main — exactement ce qu'un selecteur est cense eviter.

	   Elles sont presentees sous leur mere, pour qu'on lise l'arborescence
	   dans un menu qui, lui, est plat. */
	$racines = sql_allfetsel('id_rubrique, titre', 'spip_rubriques',
		"statut = 'publie' AND id_parent = 0", '', '0+titre, titre');
	foreach ($racines as $r) {
		$cibles['rubrique:' . $r['id_rubrique']] = _T('marly:cible_rubrique') . ' — ' . $r['titre'];
		foreach (sql_allfetsel('id_rubrique, titre', 'spip_rubriques',
			"statut = 'publie' AND id_parent = " . intval($r['id_rubrique']),
			'', '0+titre, titre') as $f) {
			$cibles['rubrique:' . $f['id_rubrique']] =
				_T('marly:cible_rubrique') . ' — ' . $r['titre'] . ' — ' . $f['titre'];
		}
	}

	/* Les pages du thème, nommées en clair. Elles ne sont pas devinables
	   depuis la base : c'est la seule liste que le code doit connaître. */
	$pages = array(
		'demarches'    => 'marly:toutes_les_demarches',
		'commerces'    => 'marly:titre_commerces',
		'associations' => 'marly:titre_vie_associative',
		'conseil'      => 'marly:titre_conseil',
		'lieux'        => 'marly:titre_ou_nous_trouver',
		'reservation'  => 'marly:reserver',
		'newsletter'   => 'marly:newsletter',
		'plan'         => 'marly:plan_du_site',
	);
	foreach ($pages as $page => $intitule) {
		$cibles['page:' . $page] = _T('marly:cible_page') . ' — ' . _T($intitule);
	}

	$cibles['url:'] = _T('marly:cible_url');

	return $cibles;
}

/**
 * L'adresse d'un raccourci.
 *
 * Rend une chaîne vide si la cible n'existe plus — une rubrique supprimée,
 * une démarche dépubliée. Le gabarit n'affiche alors pas le rond : mieux vaut
 * cinq raccourcis que six dont un mène à une page vide.
 */
function marly_url_raccourci($cible) {
	$cible = trim((string) $cible);
	if ($cible === '' or strpos($cible, ':') === false) {
		return '';
	}
	list($type, $valeur) = explode(':', $cible, 2);

	switch ($type) {
		case 'demarche':
			if (!sql_countsel('spip_demarches',
			    'id_demarche = ' . intval($valeur) . " AND statut = 'publie'")) {
				return '';
			}
			return generer_url_public('demarche', 'id_demarche=' . intval($valeur));

		case 'rubrique':
			if (!sql_countsel('spip_rubriques',
			    'id_rubrique = ' . intval($valeur) . " AND statut = 'publie'")) {
				return '';
			}
			return generer_url_entite(intval($valeur), 'rubrique');

		case 'page':
			return generer_url_public(preg_replace('/[^a-z0-9_-]/', '', $valeur));

		case 'url':
			return preg_match(',^https?://,i', $valeur) ? $valeur : '';
	}

	return '';
}
