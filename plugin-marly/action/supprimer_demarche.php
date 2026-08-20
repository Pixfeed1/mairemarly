<?php
/**
 * Supprimer une fiche démarche.
 *
 * Une suppression est définitive et se déclenche par un lien : elle passe donc
 * par securiser_action(), qui vérifie que le lien a bien été fabriqué pour cet
 * auteur et cette fiche. Sans cela, il suffirait d'écrire l'adresse à la main
 * — ou de la faire suivre — pour effacer le travail de quelqu'un d'autre.
 */

if (!defined('_ECRIRE_INC_VERSION')) {
	return;
}

function action_supprimer_demarche_dist($arg = null) {
	if (is_null($arg)) {
		$securiser = charger_fonction('securiser_action', 'inc');
		$arg = $securiser();
	}

	$id_demarche = intval($arg);
	if (!$id_demarche or !autoriser('modifier', 'salle')) {
		return;
	}

	sql_delete('spip_demarches', 'id_demarche = ' . $id_demarche);
	spip_log("marly : demarche $id_demarche supprimee", 'marly');
}
