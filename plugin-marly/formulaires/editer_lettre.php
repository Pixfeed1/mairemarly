<?php
/**
 * Rédiger une lettre d'information.
 */

if (!defined('_ECRIRE_INC_VERSION')) {
	return;
}

function formulaires_editer_lettre_charger_dist($id_lettre = 'new') {
	if (!autoriser('modifier', 'salle')) {
		return false;
	}

	$valeurs = array(
		'id_lettre' => $id_lettre,
		'titre'     => '',
		'chapo'     => '',
		'texte'     => '',
	);

	if ($id_lettre !== 'new' and intval($id_lettre)) {
		$lettre = sql_fetsel('*', 'spip_lettres', 'id_lettre = ' . intval($id_lettre));
		if ($lettre) {
			/* Une lettre partie ne se modifie plus. La rouvrir laisserait
			   croire qu'on peut corriger ce que les gens ont déjà reçu. */
			if ($lettre['statut'] !== 'redaction') {
				return false;
			}
			foreach (array('titre', 'chapo', 'texte') as $champ) {
				$valeurs[$champ] = $lettre[$champ];
			}
		}
	}

	return $valeurs;
}

function formulaires_editer_lettre_verifier_dist($id_lettre = 'new') {
	$erreurs = array();

	if (!trim((string) _request('titre'))) {
		$erreurs['titre'] = _T('marly:erreur_obligatoire');
	}
	if (!trim((string) _request('texte'))) {
		$erreurs['texte'] = _T('marly:erreur_obligatoire');
	}

	return $erreurs;
}

function formulaires_editer_lettre_traiter_dist($id_lettre = 'new') {
	$champs = array(
		'titre' => trim((string) _request('titre')),
		'chapo' => trim((string) _request('chapo')),
		'texte' => trim((string) _request('texte')),
	);

	if ($id_lettre === 'new' or !intval($id_lettre)) {
		$champs['date'] = date('Y-m-d H:i:s');
		$id_lettre = sql_insertq('spip_lettres', $champs);
		if (!$id_lettre) {
			return array('message_erreur' => _T('marly:erreur_enregistrement'));
		}
	} else {
		sql_updateq('spip_lettres', $champs, 'id_lettre = ' . intval($id_lettre));
	}

	return array(
		'message_ok' => _T('marly:lettre_enregistree'),
		'redirect'   => generer_url_ecrire('lettre', 'id_lettre=' . $id_lettre),
	);
}
