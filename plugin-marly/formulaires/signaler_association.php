<?php
/**
 * La préinscription d'une association dans l'annuaire.
 * ---------------------------------------------------------------------------
 * Le pipeline des villes qui font ça proprement (Brest, Tours, Avignon) :
 *
 *   1. l'association remplit sa fiche ici — elle entre EN ATTENTE,
 *      invisible du public ;
 *   2. la mairie est prévenue par courriel, relit la fiche dans
 *      Édition, Associations, corrige au besoin, et la publie ;
 *   3. à la publication, la personne qui s'est déclarée gérante reçoit
 *      la confirmation, avec son accès rédacteur et le lien pour choisir
 *      son mot de passe (le circuit natif de SPIP, rien ne circule en
 *      clair).
 *
 * Rien ne paraît sans la mairie : le maire est directeur de publication.
 * La saisie, elle, est faite une seule fois, par l'association.
 *
 * Antispam : les quatre barrières communes, plus deux propres au
 * formulaire : un nom déjà présent dans l'annuaire est refusé (personne ne
 * redépose la fiche du voisin), et un même courriel ne peut pas laisser
 * plus de deux fiches en attente.
 */

if (!defined('_ECRIRE_INC_VERSION')) {
	return;
}

include_spip('inc/marly_antispam');
include_spip('inc/marly_associations');

function formulaires_signaler_association_charger_dist() {
	include_spip('inc/config');
	if (!lire_config('marly/courriel', '')) {
		return false;
	}

	return array_merge(marly_antispam_charger(), array(
		'organisme' => '',
		'theme'     => 'autre',
		'activite'  => '',
		'nom'       => '',
		'courriel'  => '',
		'telephone' => '',
		'_themes'   => marly_themes_traduits(),
	));
}

function formulaires_signaler_association_verifier_dist() {
	if ($message = marly_antispam_verifier()) {
		return array('message_erreur' => $message);
	}

	$erreurs = array();

	$organisme = trim((string) _request('organisme'));
	if ($organisme === '') {
		$erreurs['organisme'] = _T('marly:erreur_obligatoire');
	} elseif (sql_countsel('spip_associations', 'nom = ' . sql_quote($organisme))) {
		$erreurs['organisme'] = _T('marly:erreur_asso_existe');
	}

	if (!array_key_exists((string) _request('theme'), marly_themes_associations())) {
		$erreurs['theme'] = _T('marly:erreur_obligatoire');
	}
	if (trim((string) _request('activite')) === '') {
		$erreurs['activite'] = _T('marly:erreur_obligatoire');
	}
	if (trim((string) _request('nom')) === '') {
		$erreurs['nom'] = _T('marly:erreur_obligatoire');
	}

	/* Le courriel est obligatoire ici : c'est lui qui recevra la
	   confirmation et l'accès. Sans lui, le pipeline s'arrête au
	   téléphone, ce qui reste possible en appelant la mairie. */
	$courriel = trim((string) _request('courriel'));
	if ($courriel === '') {
		$erreurs['courriel'] = _T('marly:erreur_obligatoire');
	} elseif (!filter_var($courriel, FILTER_VALIDATE_EMAIL)) {
		$erreurs['courriel'] = _T('marly:erreur_courriel');
	} elseif (sql_countsel('spip_associations',
			array('courriel = ' . sql_quote($courriel), "statut = 'prepa'")) >= 2) {
		$erreurs['courriel'] = _T('marly:erreur_trop_de_demandes');
	}

	$telephone = trim((string) _request('telephone'));
	if ($telephone !== '' and !preg_match(',^\+?[0-9 .()-]{6,20}$,', $telephone)) {
		$erreurs['telephone'] = _T('marly:erreur_telephone');
	}

	return $erreurs;
}

function formulaires_signaler_association_traiter_dist() {
	include_spip('inc/config');

	$champs = array(
		'nom'       => trim((string) _request('organisme')),
		'theme'     => (string) _request('theme'),
		'activite'  => trim((string) _request('activite')),
		'president' => trim((string) _request('nom')),
		'courriel'  => trim((string) _request('courriel')),
		'telephone' => trim((string) _request('telephone')),
		'statut'    => 'prepa',
	);

	$id_association = sql_insertq('spip_associations', $champs);
	if (!$id_association) {
		return array('message_erreur' => _T('marly:erreur_enregistrement'));
	}
	spip_log("marly : preinscription deposee — {$champs['nom']} (fiche $id_association)",
		'marly.' . _LOG_INFO_IMPORTANTE);

	/* La mairie est prevenue. La reponse ira au demandeur : Reply-To. */
	$destinataire = lire_config('marly/courriel', '');
	$corps = _T('marly:preinscription_corps', array('nom' => $champs['nom'])) . "\n\n"
		. _T('marly:champ_nom_association') . ' : ' . $champs['nom'] . "\n"
		. _T('marly:asso_responsable') . ' : ' . $champs['president'] . "\n"
		. _T('marly:votre_courriel') . ' : ' . $champs['courriel'] . "\n"
		. ($champs['telephone'] !== '' ? _T('marly:champ_telephone') . ' : ' . $champs['telephone'] . "\n" : '')
		. "\n" . $champs['activite'] . "\n\n"
		. _T('marly:preinscription_consigne');

	$envoyer = charger_fonction('envoyer_mail', 'inc', true);
	if ($envoyer) {
		/* Reply-To en en-tete brut : c'est la forme que le facteur de SPIP
		   accepte, la cle << repondre_a >> n'existe pas chez lui. Repondre au
		   courriel de notification repond ainsi directement au demandeur. */
		$envoyer($destinataire,
			_T('marly:preinscription_objet', array('nom' => $champs['nom'])),
			array('texte' => $corps,
			      'headers' => array('Reply-To: ' . $champs['courriel'])));
	} else {
		spip_log('marly : envoyer_mail indisponible, la mairie n\'est pas prevenue de la preinscription',
			'marly.' . _LOG_INFO_IMPORTANTE);
	}

	return array('message_ok' => _T('marly:preinscription_recue'));
}
