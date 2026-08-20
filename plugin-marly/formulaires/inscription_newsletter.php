<?php
/**
 * Inscription et désinscription à la lettre d'information.
 * ---------------------------------------------------------------------------
 * Un seul formulaire, deux modes. Le désabonnement doit être aussi facile
 * que l'abonnement — c'est une obligation, pas une courtoisie.
 *
 * Trois exigences qui ne se négocient pas :
 *
 *   LE CONSENTEMENT est un acte positif. La case part décochée : une case
 *   pré-cochée n'est pas un consentement.
 *
 *   LA DOUBLE CONFIRMATION protège les habitants les uns des autres. Sans
 *   elle, n'importe qui inscrit l'adresse d'un voisin.
 *
 *   LA DÉSINSCRIPTION passe elle aussi par un lien envoyé par courriel,
 *   pour la même raison en miroir : sans cela, n'importe qui désabonnerait
 *   son voisin, qui ne recevrait plus les alertes de la commune sans jamais
 *   comprendre pourquoi. Depuis un lien reçu dans un envoi, en revanche, un
 *   seul clic suffit — la personne a déjà prouvé qu'elle tient l'adresse.
 */

if (!defined('_ECRIRE_INC_VERSION')) {
	return;
}

include_spip('inc/marly_antispam');

function formulaires_inscription_newsletter_charger_dist($mode = 'abonnement') {
	if (!in_array($mode, array('abonnement', 'desabonnement'), true)) {
		$mode = 'abonnement';
	}

	return array_merge(marly_antispam_charger(), array(
		'_mode'        => $mode,
		'courriel'     => '',
		'courriel_bis' => '',
		'nom'          => '',
		'prenom'       => '',
		'code_postal'  => '',
		'ville'        => '',
		'consent'      => '',
	));
}

function formulaires_inscription_newsletter_verifier_dist($mode = 'abonnement') {
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

	if ($mode === 'desabonnement') {
		return $erreurs;
	}

	/* La double saisie attrape la faute de frappe. Une adresse mal tapée ne
	   recevra jamais le courriel de confirmation, et la personne croira
	   s'être abonnée : elle attendra une lettre qui n'arrivera pas. */
	$bis = trim((string) _request('courriel_bis'));
	if (!isset($erreurs['courriel']) && strcasecmp($courriel, $bis) !== 0) {
		$erreurs['courriel_bis'] = _T('marly:erreur_courriels_differents');
	}

	/* Le code postal et la commune sont FACULTATIFS. Le RGPD impose de ne
	   collecter que ce qui sert : tant que la mairie n'a qu'une lettre pour
	   tout le monde, distinguer les habitants des voisins ne sert a rien.
	   Le jour ou elle voudra cibler, on les rendra obligatoires — dans ce
	   sens-la c'est possible, l'inverse ne l'est pas : on ne peut pas
	   effacer une collecte deja faite. */
	$cp = trim((string) _request('code_postal'));
	if ($cp && !preg_match(',^\d{5}$,', $cp)) {
		$erreurs['code_postal'] = _T('marly:erreur_code_postal');
	}

	if (!_request('consent')) {
		$erreurs['consent'] = _T('marly:erreur_consentement');
	}

	return $erreurs;
}

function formulaires_inscription_newsletter_traiter_dist($mode = 'abonnement') {
	$courriel = trim((string) _request('courriel'));
	$jeton    = md5(uniqid((string) mt_rand(), true));

	$existant = sql_fetsel('id_abonne, statut, nom, prenom', 'spip_abonnes',
		'courriel = ' . sql_quote($courriel));

	/* ---- Désabonnement ---------------------------------------------------- */
	if ($mode === 'desabonnement') {
		if ($existant && $existant['statut'] !== 'desinscrit') {
			sql_updateq('spip_abonnes', array('jeton' => $jeton),
				'id_abonne = ' . intval($existant['id_abonne']));
			marly_courriel_abonne($courriel, $existant['prenom'] ?: $existant['nom'],
				$jeton, 'desinscription');
		}
		/* Même réponse dans tous les cas : dire « cette adresse n'est pas
		   inscrite » permettrait de tester qui est abonné. */
		return array('message_ok' => _T('marly:desabonnement_verifiez'), 'editable' => false);
	}

	/* ---- Abonnement ------------------------------------------------------- */
	$champs = array(
		'nom'         => trim((string) _request('nom')),
		'prenom'      => trim((string) _request('prenom')),
		'code_postal' => trim((string) _request('code_postal')),
		'ville'       => trim((string) _request('ville')),
		'statut'      => 'attente',
		'jeton'       => $jeton,
		'date'        => date('Y-m-d H:i:s'),
	);

	if ($existant && $existant['statut'] === 'confirme') {
		return array('message_ok' => _T('marly:newsletter_verifiez'), 'editable' => false);
	}

	if ($existant) {
		sql_updateq('spip_abonnes', $champs, 'id_abonne = ' . intval($existant['id_abonne']));
	} else {
		sql_insertq('spip_abonnes', array_merge($champs, array('courriel' => $courriel)));
	}

	marly_courriel_abonne($courriel, $champs['prenom'] ?: $champs['nom'], $jeton, 'confirmation');

	return array('message_ok' => _T('marly:newsletter_verifiez'), 'editable' => false);
}

/**
 * Le courriel porteur du lien, confirmation ou désinscription.
 * Un seul point d'envoi : deux copies de ce code auraient fini par diverger
 * sur le nom du site ou la forme du lien.
 */
function marly_courriel_abonne($courriel, $nom, $jeton, $quoi) {
	$faire = ($quoi === 'confirmation') ? 'confirmer' : 'desinscrire';
	$lien = url_absolue('spip.php?action=marly_abonne&faire=' . $faire . '&jeton=' . $jeton);

	$contexte = array(
		'nom'  => $nom ?: $courriel,
		'lien' => $lien,
		'site' => $GLOBALS['meta']['nom_site'] ?? '',
	);

	$envoyer = charger_fonction('envoyer_mail', 'inc', true);
	if (!$envoyer) {
		spip_log("marly : envoyer_mail indisponible, $quoi perdue pour $courriel", 'marly' . _LOG_ERREUR);
		return;
	}
	$envoyer($courriel,
		_T('marly:courriel_sujet_' . $quoi, $contexte),
		_T('marly:courriel_corps_' . $quoi, $contexte));
}
