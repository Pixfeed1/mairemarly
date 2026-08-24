<?php
/**
 * Supprimer la fiche d'une salle.
 *
 * Même sécurité que les autres suppressions : le lien est signé pour cet
 * auteur et cette fiche par securiser_action(), sans quoi une adresse
 * recopiée suffirait à effacer le travail d'autrui.
 *
 * Les RESERVATIONS qui s'y rapportent partent avec elle. Une reservation
 * sans salle ne veut plus rien dire : elle ne s'afficherait nulle part, et
 * resterait dans la base sans que personne puisse la retrouver ni l'effacer.
 * La confirmation le dit en toutes lettres avant le clic.
 */

if (!defined('_ECRIRE_INC_VERSION')) {
	return;
}

function action_supprimer_salle_dist($arg = null) {
	if (is_null($arg)) {
		$securiser = charger_fonction('securiser_action', 'inc');
		$arg = $securiser();
	}

	$id_salle = intval($arg);
	if (!$id_salle or !autoriser('modifier', 'salle')) {
		return;
	}

	/* Les reservations de cette salle : elles n'ont plus d'objet. */
	sql_delete('spip_reservations', 'id_salle = ' . $id_salle);
	sql_delete('spip_salles', 'id_salle = ' . $id_salle);
	spip_log("marly : salle $id_salle supprime", 'marly.' . _LOG_INFO_IMPORTANTE);

	include_spip('inc/marly_outils');
	marly_invalider_cache();
}
