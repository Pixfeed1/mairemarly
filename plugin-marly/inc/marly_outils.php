<?php
/**
 * Petits outils communs.
 */

if (!defined('_ECRIRE_INC_VERSION')) {
	return;
}

/**
 * La table est-elle créée ?
 * ---------------------------------------------------------------------------
 * Les fichiers sont déployés avant que la mise à jour de la base n'ait tourné.
 * Entre les deux — et cet intervalle peut durer, il faut qu'un humain charge
 * une page de l'espace privé — un formulaire qui interroge une table nouvelle
 * s'arrête sur une erreur SQL, et l'écran devient inutilisable.
 *
 * Ce n'est pas un cas rare : c'est arrivé à chaque livraison qui ajoutait une
 * table. D'où ce garde-fou, à poser devant toute lecture d'une table récente.
 * Le formulaire propose alors une liste vide, ce qui est exactement la vérité :
 * il n'y a encore rien à proposer.
 *
 * Le résultat est retenu : la question ne se pose qu'une fois par table et par
 * page.
 */
function marly_table_prete($table) {
	static $vues = array();

	if (!isset($vues[$table])) {
		$vues[$table] = function_exists('sql_showtable')
			? (bool) sql_showtable($table, true)
			: true;
	}

	return $vues[$table];
}
