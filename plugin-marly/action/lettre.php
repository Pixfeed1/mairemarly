<?php
/**
 * Essayer, lancer ou arrêter l'envoi d'une lettre.
 * ---------------------------------------------------------------------------
 * Passe par une action SPIP, donc par un jeton : sans lui, une simple adresse
 * suffirait à expédier une lettre à tous les abonnés.
 */

if (!defined('_ECRIRE_INC_VERSION')) {
	return;
}

function action_lettre_dist($arg = null) {

	if (is_null($arg)) {
		$securiser = charger_fonction('securiser_action', 'inc');
		$arg = $securiser();
	}

	list($id_lettre, $quoi) = array_pad(explode('-', $arg, 2), 2, '');
	$id_lettre = intval($id_lettre);

	if (!$id_lettre or !autoriser('modifier', 'salle')) {
		return;
	}

	include_spip('inc/marly_lettres');
	$lettre = sql_fetsel('*', 'spip_lettres', 'id_lettre = ' . $id_lettre);
	if (!$lettre) {
		return;
	}

	switch ($quoi) {

		/* L'essai. On s'envoie la lettre à soi-même avant de l'expédier à
		   deux cents personnes : c'est le seul moment où une coquille se
		   rattrape encore. */
		case 'essai':
			$moi = $GLOBALS['visiteur_session']['email'] ?? '';
			if (!$moi) { return; }

			$faux = array(
				'courriel' => $moi,
				'nom'      => $GLOBALS['visiteur_session']['nom'] ?? '',
				'prenom'   => '',
				'jeton'    => 'essai',
			);
			$envoyer = charger_fonction('envoyer_mail', 'inc', true);
			if ($envoyer) {
				$envoyer($moi, '[Essai] ' . $lettre['titre'],
					marly_lettre_texte($lettre, $faux), '', '',
					marly_lettre_html($lettre, $faux));
			}
			break;

		/* Le départ. On repart de zéro : curseur, compteurs. Une lettre
		   relancée après un arrêt reprendrait sinon au milieu. */
		case 'envoyer':
			if ($lettre['statut'] !== 'redaction') { return; }
			sql_updateq('spip_lettres', array(
				'statut'     => 'envoi',
				'curseur'    => 0,
				'nb_envoyes' => 0,
				'nb_erreurs' => 0,
			), 'id_lettre = ' . $id_lettre);

			/* Un premier lot tout de suite : voir le compteur bouger rassure
			   davantage qu'un message promettant que ça va partir. */
			marly_envoyer_lot($id_lettre);
			break;

		/* L'arrêt d'urgence. Les déjà-partis sont partis — on ne les
		   rattrape pas, et le dire vaut mieux que le laisser croire. */
		case 'arreter':
			if ($lettre['statut'] !== 'envoi') { return; }
			sql_updateq('spip_lettres', array('statut' => 'arretee'),
				'id_lettre = ' . $id_lettre);
			spip_log("marly : envoi de la lettre $id_lettre arrete a {$lettre['nb_envoyes']} envois", 'marly.' . _LOG_INFO_IMPORTANTE);
			break;

		/* Poursuivre un envoi arrêté, sans repartir du début. */
		case 'reprendre':
			if ($lettre['statut'] !== 'arretee') { return; }
			sql_updateq('spip_lettres', array('statut' => 'envoi'), 'id_lettre = ' . $id_lettre);
			marly_envoyer_lot($id_lettre);
			break;
	}
}
