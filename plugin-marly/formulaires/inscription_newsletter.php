<?php
/**
 * Inscription à la lettre d'information.
 * ---------------------------------------------------------------------------
 * Deux exigences non négociables, et elles ne viennent pas du confort :
 *
 *   LE CONSENTEMENT doit être un acte positif. Une case pré-cochée n'est pas
 *   un consentement — le RGPD le dit, et la CNIL sanctionne. La case part
 *   donc décochée, et le formulaire est refusé tant qu'elle ne l'est pas.
 *
 *   LA DOUBLE CONFIRMATION protège les habitants les uns des autres. Sans
 *   elle, n'importe qui inscrit l'adresse d'un voisin, et c'est la commune
 *   qui envoie des courriels non sollicités en son nom.
 */

if (!defined('_ECRIRE_INC_VERSION')) {
	return;
}

include_spip('inc/marly_antispam');

function formulaires_inscription_newsletter_charger_dist() {
	return array_merge(marly_antispam_charger(), array(
		'courriel'  => '',
		'nom'       => '',
		'consent'   => '',
	));
}

function formulaires_inscription_newsletter_verifier_dist() {
	$erreurs = array();

	if ($message = marly_antispam_verifier()) {
		return array('message_erreur' => $message);
	}

	$courriel = trim((string) _request('courriel'));
	if (!$courriel) {
		$erreurs['courriel'] = _T('marly:erreur_obligatoire');
	} elseif (!filter_var($courriel, FILTER_VALIDATE_EMAIL)) {
		$erreurs['courriel'] = _T('marly:erreur_courriel');
	}

	if (!_request('consent')) {
		$erreurs['consent'] = _T('marly:erreur_consentement');
	}

	return $erreurs;
}

function formulaires_inscription_newsletter_traiter_dist() {
	$courriel = trim((string) _request('courriel'));
	$nom      = trim((string) _request('nom'));
	$jeton    = md5(uniqid((string) mt_rand(), true));

	$existant = sql_fetsel('id_abonne, statut', 'spip_abonnes',
		'courriel = ' . sql_quote($courriel));

	if ($existant && $existant['statut'] === 'confirme') {
		/* On ne le dit pas autrement : révéler qu'une adresse est déjà
		   inscrite renseignerait n'importe qui sur les habitants abonnés.
		   Le message est le même que pour une inscription neuve. */
		return array('message_ok' => _T('marly:newsletter_verifiez'), 'editable' => false);
	}

	if ($existant) {
		sql_updateq('spip_abonnes', array(
			'nom'    => $nom,
			'statut' => 'attente',
			'jeton'  => $jeton,
			'date'   => date('Y-m-d H:i:s'),
		), 'id_abonne = ' . intval($existant['id_abonne']));
	} else {
		sql_insertq('spip_abonnes', array(
			'courriel' => $courriel,
			'nom'      => $nom,
			'statut'   => 'attente',
			'jeton'    => $jeton,
			'date'     => date('Y-m-d H:i:s'),
		));
	}

	marly_envoyer_confirmation($courriel, $nom, $jeton);

	return array('message_ok' => _T('marly:newsletter_verifiez'), 'editable' => false);
}

/** Le courriel qui porte le lien de confirmation. */
function marly_envoyer_confirmation($courriel, $nom, $jeton) {
	$lien = url_absolue(generer_url_public('confirmation', 'jeton=' . $jeton));

	$contexte = array(
		'nom'  => $nom ?: $courriel,
		'lien' => $lien,
		'site' => $GLOBALS['meta']['nom_site'] ?? '',
	);

	$envoyer = charger_fonction('envoyer_mail', 'inc', true);
	if (!$envoyer) {
		spip_log("marly : envoyer_mail indisponible, confirmation perdue pour $courriel", 'marly' . _LOG_ERREUR);
		return;
	}
	$envoyer($courriel, _T('marly:courriel_sujet_confirmation', $contexte),
	                    _T('marly:courriel_corps_confirmation', $contexte));
}
