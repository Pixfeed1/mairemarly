<?php
/**
 * Supprimer la fiche d'un raccourci de la page d'accueil.
 *
 * Même sécurité que les autres suppressions : le lien est signé pour cet
 * auteur et cette fiche par securiser_action(), sans quoi une adresse
 * recopiée suffirait à effacer le travail d'autrui.
 *
 * Rien ne part avec lui : un raccourci n'est qu'un rond sur la page
 * d'accueil, il ne porte aucun contenu. La page en affichera simplement un
 * de moins.
 */

if (!defined('_ECRIRE_INC_VERSION')) {
	return;
}

function action_supprimer_raccourci_dist($arg = null) {
	if (is_null($arg)) {
		$securiser = charger_fonction('securiser_action', 'inc');
		$arg = $securiser();
	}

	$id_raccourci = intval($arg);
	if (!$id_raccourci or !autoriser('modifier', 'salle')) {
		return;
	}

	sql_delete('spip_raccourcis', 'id_raccourci = ' . $id_raccourci);
	spip_log("marly : raccourci $id_raccourci supprime", 'marly.' . _LOG_INFO_IMPORTANTE);

	include_spip('inc/marly_outils');
	marly_invalider_cache();
}
