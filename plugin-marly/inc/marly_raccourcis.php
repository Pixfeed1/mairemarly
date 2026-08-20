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

	foreach (sql_allfetsel('id_demarche, titre', 'spip_demarches',
	                       "statut = 'publie'", '', 'famille, rang, titre') as $d) {
		$cibles['demarche:' . $d['id_demarche']] = _T('marly:cible_demarche') . ' — ' . $d['titre'];
	}

	foreach (sql_allfetsel('id_rubrique, titre', 'spip_rubriques',
	                       "statut = 'publie' AND id_parent = 0", '', '0+titre, titre') as $r) {
		$cibles['rubrique:' . $r['id_rubrique']] = _T('marly:cible_rubrique') . ' — ' . $r['titre'];
	}

	/* Les pages du thème, nommées en clair. Elles ne sont pas devinables
	   depuis la base : c'est la seule liste que le code doit connaître. */
	$pages = array(
		'demarches'    => 'marly:toutes_les_demarches',
		'associations' => 'marly:titre_vie_associative',
		'reservation'  => 'marly:reserver',
		'newsletter'   => 'marly:newsletter',
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
function filtre_marly_url_raccourci_dist($cible) {
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
