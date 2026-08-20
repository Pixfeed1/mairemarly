<?php
/**
 * Supprimer une fiche d'association.
 *
 * Même sécurité que la suppression d'une démarche : le lien est signé pour
 * cet auteur et cette fiche par securiser_action(), sans quoi une adresse
 * recopiée suffirait à effacer le travail d'autrui.
 *
 * La fiche seule est supprimée. Sa rubrique et les articles qu'elle
 * contient RESTENT : ils sont l'oeuvre de l'association, et les effacer en
 * cascade détruirait des textes que personne n'a demandé à perdre. Si la
 * rubrique doit partir aussi, la mairie la supprime dans Édition,
 * Rubriques, par le circuit normal de SPIP. Le compte rédacteur, s'il
 * existe, reste également : il peut servir à une autre fiche.
 */

if (!defined('_ECRIRE_INC_VERSION')) {
	return;
}

function action_supprimer_association_dist($arg = null) {
	if (is_null($arg)) {
		$securiser = charger_fonction('securiser_action', 'inc');
		$arg = $securiser();
	}

	$id_association = intval($arg);
	if (!$id_association or !autoriser('modifier', 'salle')) {
		return;
	}

	sql_delete('spip_associations', 'id_association = ' . $id_association);
	spip_log("marly : association $id_association supprimee", 'marly.' . _LOG_INFO_IMPORTANTE);

	include_spip('inc/marly_outils');
	marly_invalider_cache();
}
