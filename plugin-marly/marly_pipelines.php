<?php
/**
 * Branchements du plugin sur SPIP.
 */

if (!defined('_ECRIRE_INC_VERSION')) {
	return;
}

/**
 * Ajoute notre feuille de style aux écrans de l'espace privé.
 *
 * On charge inc/filtres explicitement : ce pipeline s'exécute tôt, et rien
 * ne garantit que direction_css() soit déjà défini. Un appel à une fonction
 * absente ici casserait tout l'espace privé, pas seulement le style.
 */
function marly_header_prive($flux) {
	$css = find_in_path('prive/themes/spip/css/marly.css');
	if (!$css) {
		spip_log('marly : prive/themes/spip/css/marly.css introuvable', 'marly' . _LOG_ERREUR);
		return $flux;
	}

	include_spip('inc/filtres');
	$href = function_exists('direction_css') ? direction_css($css) : $css;

	/* Un paramètre de version force le navigateur à recharger la feuille
	   après chaque modification. Sans lui, on corrige un style et on croit
	   que ça n'a rien changé. */
	$href .= (strpos($href, '?') === false ? '?' : '&') . 'v=' . filemtime($css);

	return $flux . "\n<link rel=\"stylesheet\" href=\"" . $href . "\" type=\"text/css\" />";
}
