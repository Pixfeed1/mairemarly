<?php
/**
 * Écrire à la mairie.
 * ---------------------------------------------------------------------------
 * RIEN N'EST ENREGISTRÉ. Le message part par courriel au secrétariat, avec un
 * Reply-To vers l'expéditeur, et n'existe nulle part ailleurs. Le conserver
 * créerait un traitement de données à porter au registre RGPD — nom, adresse,
 * téléphone, propos parfois personnels — pour un gain nul : le message vit
 * dans la boîte de la mairie, comme une lettre dans un casier.
 *
 * CONSÉQUENCE, ET ELLE EST TRAITÉE : si l'envoi échoue, le message est perdu.
 * Les autres formulaires du site peuvent se contenter d'un journal, parce
 * qu'ils ont écrit une fiche en base avant d'écrire un courriel. Celui-ci ne
 * peut pas : un échec silencieux laisserait quelqu'un croire que la mairie a
 * reçu sa demande. Il rend donc une erreur, avec le téléphone.
 *
 * LE DESTINATAIRE VIENT TOUJOURS DES RÉGLAGES, jamais du formulaire. C'est ce
 * qui empêche qu'on s'en serve pour écrire à des tiers depuis notre serveur.
 * Sans adresse renseignée, le formulaire ne se charge pas du tout : mieux vaut
 * pas de formulaire qu'un formulaire qui n'écrit à personne.
 *
 * Antispam : les quatre barrières communes de inc/marly_antispam, et rien de
 * plus. Le comptage de débit de ce module compte des demandes en attente dans
 * une table ; ici il n'y a pas de table, donc pas de compte à faire. On
 * n'invente pas une cinquième barrière pour la symétrie.
 *
 * PAS DE FILTRE SUR LES LIENS DANS LE MESSAGE. Un robot générique n'arrive
 * jamais jusque-là — il bute sur le champ-piège ou sur le délai signé. Un
 * habitant, lui, colle légitimement l'adresse d'une annonce ou d'un article.
 * Filtrer coûterait des vrais messages pour un spam qui ne passe pas.
 */

if (!defined('_ECRIRE_INC_VERSION')) {
	return;
}

include_spip('inc/marly_antispam');

/**
 * Les objets proposés.
 *
 * Sept entrées, toutes vraies pour n'importe quelle commune : ce sont des
 * compétences communales, pas l'organigramme de Marly-Gomont, qu'on ne
 * connaît pas. Elles servent au secrétariat à trier, pas à aiguiller
 * automatiquement — rien n'est routé, tout arrive à la même adresse.
 */
function marly_contact_objets() {
	$cles = array('general', 'etat_civil', 'urbanisme', 'salles', 'voirie', 'associatif', 'autre');
	$liste = array();
	foreach ($cles as $cle) {
		$liste[$cle] = _T('marly:contact_objet_' . $cle);
	}
	return $liste;
}

function formulaires_contact_charger_dist() {
	include_spip('inc/config');
	if (!lire_config('marly/courriel', '')) {
		return false;
	}

	return array_merge(marly_antispam_charger(), array(
		'nom'       => '',
		'courriel'  => '',
		'telephone' => '',
		'objet'     => 'general',
		'message'   => '',
		'_objets'   => marly_contact_objets(),
	));
}

function formulaires_contact_verifier_dist() {
	if ($message = marly_antispam_verifier()) {
		return array('message_erreur' => $message);
	}

	$erreurs = array();

	if (trim((string) _request('nom')) === '') {
		$erreurs['nom'] = _T('marly:erreur_obligatoire');
	}

	$courriel = trim((string) _request('courriel'));
	if ($courriel === '') {
		$erreurs['courriel'] = _T('marly:erreur_obligatoire');
	} elseif (!filter_var($courriel, FILTER_VALIDATE_EMAIL)) {
		$erreurs['courriel'] = _T('marly:erreur_courriel');
	}

	$telephone = trim((string) _request('telephone'));
	if ($telephone !== '' and !preg_match(',^\+?[0-9 .()-]{6,20}$,', $telephone)) {
		$erreurs['telephone'] = _T('marly:erreur_telephone');
	}

	if (!array_key_exists((string) _request('objet'), marly_contact_objets())) {
		$erreurs['objet'] = _T('marly:erreur_obligatoire');
	}

	/* Un message de trois caractères n'est pas une demande. Le seuil est bas :
	   il attrape la validation accidentelle, pas la personne concise. */
	$texte = trim((string) _request('message'));
	if ($texte === '') {
		$erreurs['message'] = _T('marly:erreur_obligatoire');
	} elseif (mb_strlen($texte) < 10) {
		$erreurs['message'] = _T('marly:erreur_message_court');
	}

	return $erreurs;
}

function formulaires_contact_traiter_dist() {
	include_spip('inc/config');

	$destinataire = lire_config('marly/courriel', '');
	if (!$destinataire) {
		return array('message_erreur' => _T('marly:contact_echec'));
	}

	$objets    = marly_contact_objets();
	$objet     = (string) _request('objet');
	$nom       = trim((string) _request('nom'));
	$courriel  = trim((string) _request('courriel'));
	$telephone = trim((string) _request('telephone'));
	$texte     = trim((string) _request('message'));

	$corps = _T('marly:contact_champ_objet') . ' : ' . $objets[$objet] . "\n"
		. _T('marly:votre_nom') . ' : ' . $nom . "\n"
		. _T('marly:votre_courriel') . ' : ' . $courriel . "\n"
		. ($telephone !== '' ? _T('marly:champ_telephone') . ' : ' . $telephone . "\n" : '')
		. "\n" . $texte . "\n\n"
		. _T('marly:contact_consigne');

	$envoyer = charger_fonction('envoyer_mail', 'inc', true);
	if (!$envoyer) {
		spip_log('marly : envoyer_mail indisponible, message de contact PERDU',
			'marly.' . _LOG_ERREUR);
		return array('message_erreur' => marly_contact_echec());
	}

	/* Reply-To en en-tete brut : c'est la forme que le facteur de SPIP
	   accepte, la cle << repondre_a >> n'existe pas chez lui. Repondre au
	   courriel de notification repond ainsi directement a l'habitant. */
	$parti = $envoyer($destinataire,
		_T('marly:contact_objet_courriel', array('objet' => $objets[$objet])),
		array('texte' => $corps, 'headers' => array('Reply-To: ' . $courriel)));

	if (!$parti) {
		spip_log("marly : envoi du message de contact echoue ($courriel)",
			'marly.' . _LOG_ERREUR);
		return array('message_erreur' => marly_contact_echec());
	}

	spip_log("marly : message de contact transmis — $objet, de $courriel",
		'marly.' . _LOG_INFO_IMPORTANTE);

	return array('message_ok' => _T('marly:contact_recu'));
}

/**
 * Le message d'echec. Il donne le telephone, parce qu'a ce moment-la le
 * courriel est precisement ce qui ne marche pas.
 */
function marly_contact_echec() {
	include_spip('inc/config');
	$tel = lire_config('marly/telephone', '');
	return $tel
		? _T('marly:contact_echec_tel', array('tel' => $tel))
		: _T('marly:contact_echec');
}
