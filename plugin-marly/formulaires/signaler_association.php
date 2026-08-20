<?php
/**
 * Une association demande à entrer dans l'annuaire.
 * ---------------------------------------------------------------------------
 * Le formulaire n'écrit RIEN en base : il envoie un courriel à la mairie,
 * qui crée la fiche elle-même. C'est voulu. Le maire est directeur de
 * publication : rien ne doit entrer dans l'annuaire sans passer par lui,
 * et une table de « demandes en attente » serait un deuxième endroit à
 * surveiller pour la secrétaire, qui a déjà sa boîte aux lettres.
 *
 * Antispam : les quatre barrières communes (inc/marly_antispam). Les champs
 * s'appellent « organisme » et « nom » exprès : la barrière qui refuse les
 * adresses web dans les champs d'identité porte sur ces noms-là.
 */

if (!defined('_ECRIRE_INC_VERSION')) {
	return;
}

include_spip('inc/marly_antispam');

function formulaires_signaler_association_charger_dist() {
	include_spip('inc/config');
	/* Sans destinataire, pas de formulaire : le bouton qui l'ouvre est déjà
	   conditionné au même réglage, ceci couvre l'appel direct. */
	if (!lire_config('marly/courriel', '')) {
		return false;
	}

	return array_merge(marly_antispam_charger(), array(
		'organisme' => '',
		'nom'       => '',
		'courriel'  => '',
		'telephone' => '',
		'message'   => '',
	));
}

function formulaires_signaler_association_verifier_dist() {
	if ($message = marly_antispam_verifier()) {
		return array('message_erreur' => $message);
	}

	$erreurs = array();

	if (trim((string) _request('organisme')) === '') {
		$erreurs['organisme'] = _T('marly:erreur_obligatoire');
	}
	if (trim((string) _request('nom')) === '') {
		$erreurs['nom'] = _T('marly:erreur_obligatoire');
	}

	/* Un moyen de répondre, au choix : courriel ou téléphone. Imposer le
	   courriel écarterait ceux qui n'en ont pas, et il y en a. */
	$courriel  = trim((string) _request('courriel'));
	$telephone = trim((string) _request('telephone'));
	if ($courriel === '' and $telephone === '') {
		$erreurs['courriel'] = _T('marly:erreur_un_moyen_de_reponse');
	}
	if ($courriel !== '' and !filter_var($courriel, FILTER_VALIDATE_EMAIL)) {
		$erreurs['courriel'] = _T('marly:erreur_courriel');
	}
	if ($telephone !== '' and !preg_match(',^\+?[0-9 .()-]{6,20}$,', $telephone)) {
		$erreurs['telephone'] = _T('marly:erreur_telephone');
	}

	return $erreurs;
}

function formulaires_signaler_association_traiter_dist() {
	include_spip('inc/config');
	$destinataire = lire_config('marly/courriel', '');

	$champs = array(
		_T('marly:champ_nom_association') => trim((string) _request('organisme')),
		_T('marly:votre_nom')             => trim((string) _request('nom')),
		_T('marly:votre_courriel')        => trim((string) _request('courriel')),
		_T('marly:champ_telephone')       => trim((string) _request('telephone')),
		_T('marly:votre_message')         => trim((string) _request('message')),
	);
	$corps = _T('marly:signalement_corps') . "\n\n";
	foreach ($champs as $etiquette => $valeur) {
		if ($valeur !== '') {
			$corps .= $etiquette . ' : ' . $valeur . "\n";
		}
	}

	$envoyer = charger_fonction('envoyer_mail', 'inc', true);
	if (!$envoyer or !$envoyer($destinataire,
			_T('marly:signalement_objet', array('nom' => $champs[_T('marly:champ_nom_association')])),
			$corps)) {
		spip_log('marly : signalement association non envoye a ' . $destinataire,
			'marly.' . _LOG_INFO_IMPORTANTE);
		return array('message_erreur' => _T('marly:erreur_envoi_signalement'));
	}

	spip_log('marly : signalement association envoye — ' . $champs[_T('marly:champ_nom_association')],
		'marly.' . _LOG_INFO_IMPORTANTE);

	return array('message_ok' => _T('marly:signalement_envoye'));
}
