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

/**
 * L'état de localisation d'une adresse, pour l'afficher dans un formulaire.
 *
 * Le journal du serveur dit ce que le géocodage a trouvé. Personne en mairie
 * ne le lira jamais, et c'est normal : c'est un outil de développement. La
 * secrétaire, elle, doit voir l'état sur son écran, sans avoir à enregistrer
 * pour le découvrir.
 *
 * On ne lui annonce pas une « précision » calculée — elle n'aurait aucun
 * moyen de la contredire. On lui donne le lien vers le point trouvé : elle
 * regarde la carte et voit elle-même si le marqueur est sur le bon bâtiment.
 * C'est la seule vérification qui vaille.
 */
function marly_etat_localisation($adresse, $latitude, $longitude) {
	if (trim((string) $adresse) === '') {
		return array('etat' => '', 'lien' => '');
	}
	if (trim((string) $latitude) === '' or trim((string) $longitude) === '') {
		return array('etat' => 'absent', 'lien' => '');
	}
	include_spip('marly_fonctions');
	return array(
		'etat' => 'trouve',
		'lien' => filtre_marly_lien_carte_dist($latitude, $longitude),
	);
}
