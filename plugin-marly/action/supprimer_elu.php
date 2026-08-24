<?php
/**
 * Supprimer la fiche d'un élu.
 *
 * Même sécurité que les autres suppressions : le lien est signé pour cet
 * auteur et cette fiche par securiser_action(), sans quoi une adresse
 * recopiée suffirait à effacer le travail d'autrui.
 *
 * La fiche seule part. Rien d'autre n'y est attaché : un élu ne porte ni
 * rubrique, ni article, ni compte. La suppression est donc bien ce qu'elle
 * annonce, et elle est définitive.
 */

if (!defined('_ECRIRE_INC_VERSION')) {
	return;
}

function action_supprimer_elu_dist($arg = null) {
	if (is_null($arg)) {
		$securiser = charger_fonction('securiser_action', 'inc');
		$arg = $securiser();
	}

	$id_elu = intval($arg);
	if (!$id_elu or !autoriser('modifier', 'salle')) {
		return;
	}

	sql_delete('spip_elus', 'id_elu = ' . $id_elu);
	spip_log("marly : elu $id_elu supprime", 'marly.' . _LOG_INFO_IMPORTANTE);

	include_spip('inc/marly_outils');
	marly_invalider_cache();
}
