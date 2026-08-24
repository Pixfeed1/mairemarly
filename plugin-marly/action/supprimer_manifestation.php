<?php
/**
 * Supprimer la fiche d'un evenement.
 *
 * Même sécurité que les autres suppressions : le lien est signé pour cet
 * auteur et cette fiche par securiser_action(), sans quoi une adresse
 * recopiée suffirait à effacer le travail d'autrui.
 *
 * Les INSCRIPTIONS partent avec lui, pour la meme raison qu'une reservation
 * sans salle : elles ne renverraient plus a rien. La confirmation le dit en
 * toutes lettres avant le clic.
 */

if (!defined('_ECRIRE_INC_VERSION')) {
	return;
}

function action_supprimer_manifestation_dist($arg = null) {
	if (is_null($arg)) {
		$securiser = charger_fonction('securiser_action', 'inc');
		$arg = $securiser();
	}

	$id_manifestation = intval($arg);
	if (!$id_manifestation or !autoriser('modifier', 'salle')) {
		return;
	}

	/* Les inscriptions a cet evenement : elles n'ont plus d'objet. */
	sql_delete('spip_reservations', 'id_manifestation = ' . $id_manifestation);
	sql_delete('spip_manifestations', 'id_manifestation = ' . $id_manifestation);
	spip_log("marly : manifestation $id_manifestation supprime", 'marly.' . _LOG_INFO_IMPORTANTE);

	include_spip('inc/marly_outils');
	marly_invalider_cache();
}
