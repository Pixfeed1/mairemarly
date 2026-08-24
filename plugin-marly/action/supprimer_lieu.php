<?php
/**
 * Supprimer la fiche d'un lieu.
 *
 * Même sécurité que les autres suppressions : le lien est signé pour cet
 * auteur et cette fiche par securiser_action(), sans quoi une adresse
 * recopiée suffirait à effacer le travail d'autrui.
 *
 * La fiche seule part. Les associations qui déclaraient s'y réunir gardent
 * leur adresse écrite en clair : elle ne dépend pas de cette fiche, et une
 * suppression ici ne les laisse donc pas sans lieu.
 */

if (!defined('_ECRIRE_INC_VERSION')) {
	return;
}

function action_supprimer_lieu_dist($arg = null) {
	if (is_null($arg)) {
		$securiser = charger_fonction('securiser_action', 'inc');
		$arg = $securiser();
	}

	$id_lieu = intval($arg);
	if (!$id_lieu or !autoriser('modifier', 'salle')) {
		return;
	}

	sql_delete('spip_lieux', 'id_lieu = ' . $id_lieu);
	spip_log("marly : lieu $id_lieu supprime", 'marly.' . _LOG_INFO_IMPORTANTE);

	include_spip('inc/marly_outils');
	marly_invalider_cache();
}
