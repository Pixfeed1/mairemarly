<?php
/**
 * Supprimer la fiche d'un commerce.
 *
 * Même sécurité que la suppression d'une association : le lien est signé
 * pour cet auteur et cette fiche par securiser_action(), sans quoi une
 * adresse recopiée suffirait à effacer le travail d'autrui.
 *
 * Rien d'autre ne part avec elle : une fiche de commerce ne porte ni
 * rubrique, ni article, ni compte. La suppression est donc bien ce qu'elle
 * annonce, et elle est définitive.
 */

if (!defined('_ECRIRE_INC_VERSION')) {
	return;
}

function action_supprimer_commerce_dist($arg = null) {
	if (is_null($arg)) {
		$securiser = charger_fonction('securiser_action', 'inc');
		$arg = $securiser();
	}

	$id_commerce = intval($arg);
	if (!$id_commerce or !autoriser('modifier', 'salle')) {
		return;
	}

	sql_delete('spip_commerces', 'id_commerce = ' . $id_commerce);
	spip_log("marly : commerce $id_commerce supprime", 'marly.' . _LOG_INFO_IMPORTANTE);

	include_spip('inc/marly_outils');
	marly_invalider_cache();
}
