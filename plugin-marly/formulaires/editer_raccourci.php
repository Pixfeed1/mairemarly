<?php
/**
 * Un raccourci de la page d'accueil.
 */

if (!defined('_ECRIRE_INC_VERSION')) {
	return;
}

function formulaires_editer_raccourci_charger_dist($id_raccourci = 'new') {
	if (!autoriser('modifier', 'salle')) {
		return false;
	}

	include_spip('inc/marly_demarches');
	include_spip('inc/marly_raccourcis');

	$valeurs = array(
		'id_raccourci' => $id_raccourci,
		'titre'   => '',
		'icone'   => 'ri-arrow-right-line',
		'cible'   => '',
		'url'     => '',
		'rang'    => 1,
		'statut'  => 'publie',
		'_icones' => marly_icones_demarches(),
		'_cibles' => marly_cibles_raccourcis(),
	);

	/* La liste des places s'ajuste a ce qui existe.
	   ------------------------------------------------------------------------
	   Proposer six places quand il n'y a qu'un bouton est une question posee
	   pour rien, et six reponses dont cinq sont fausses. On n'offre donc que
	   les places reellement occupables : autant qu'il y a de raccourcis, plus
	   une quand on en cree un.

	   Et pour le tout premier, on ne demande rien du tout : il est premier,
	   il n'y a pas a en discuter. */
	$combien = sql_countsel('spip_raccourcis');
	$creation = ($id_raccourci === 'new' or !intval($id_raccourci));
	$places = $creation ? $combien + 1 : $combien;

	$valeurs['_positions'] = array();
	if ($places > 1) {
		for ($i = 1; $i <= min(6, $places); $i++) {
			$rang = ($i === 1) ? '1re' : $i . 'e';
			if ($i === 1) {
				$rang .= ', ' . _T('marly:position_gauche');
			} elseif ($i === min(6, $places)) {
				$rang .= ', ' . _T('marly:position_droite');
			}
			$valeurs['_positions'][$i] = $rang;
		}
	}
	if ($creation) {
		$valeurs['rang'] = min(6, $combien + 1);
	}

	if ($id_raccourci !== 'new' and intval($id_raccourci)) {
		$r = sql_fetsel('*', 'spip_raccourcis', 'id_raccourci = ' . intval($id_raccourci));
		if ($r) {
			foreach (array('titre', 'icone', 'cible', 'rang', 'statut') as $c) {
				$valeurs[$c] = $r[$c] ?? $valeurs[$c] ?? '';
			}
			/* Une adresse extérieure est rangée dans « cible » sous la forme
			   url:https://… On la ressort dans son propre champ, sans quoi la
			   secrétaire relirait « url:https://… » dans un menu déroulant. */
			if (strpos($r['cible'], 'url:') === 0) {
				$valeurs['url'] = substr($r['cible'], 4);
				$valeurs['cible'] = 'url:';
			}
		}
	}

	return $valeurs;
}

function formulaires_editer_raccourci_verifier_dist($id_raccourci = 'new') {
	$erreurs = array();

	if (!trim((string) _request('titre'))) {
		$erreurs['titre'] = _T('marly:erreur_obligatoire');
	}
	if (!trim((string) _request('cible'))) {
		$erreurs['cible'] = _T('marly:erreur_obligatoire');
	}

	/* Le champ d'adresse n'est obligatoire QUE si l'on a choisi « une autre
	   adresse ». Le rendre obligatoire en toutes circonstances obligerait à
	   inventer une adresse pour un raccourci vers une rubrique. */
	if (_request('cible') === 'url:') {
		$url = trim((string) _request('url'));
		if (!preg_match(',^https?://.+,i', $url)) {
			$erreurs['url'] = _T('marly:erreur_adresse');
		}
	}

	return $erreurs;
}

function formulaires_editer_raccourci_traiter_dist($id_raccourci = 'new') {
	if (!autoriser('modifier', 'salle')) {
		return array('message_erreur' => _T('avis_operation_impossible'));
	}

	$cible = trim((string) _request('cible'));
	if ($cible === 'url:') {
		$cible = 'url:' . trim((string) _request('url'));
	}

	$champs = array(
		'titre'  => trim((string) _request('titre')),
		'icone'  => trim((string) _request('icone')),
		'cible'  => $cible,
		'rang'   => min(6, max(1, intval(_request('rang')))),
		'statut' => in_array(_request('statut'), array('publie', 'prepa'), true)
		            ? _request('statut') : 'publie',
	);

	if ($id_raccourci === 'new' or !intval($id_raccourci)) {
		$id_raccourci = sql_insertq('spip_raccourcis', $champs);
		if (!$id_raccourci) {
			return array('message_erreur' => _T('marly:erreur_enregistrement'));
		}
	} else {
		sql_updateq('spip_raccourcis', $champs, 'id_raccourci = ' . intval($id_raccourci));
	}

	return array('message_ok' => _T('marly:raccourci_enregistre'),
	             'redirect' => generer_url_ecrire('raccourcis'));
}
