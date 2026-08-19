<?php
/**
 * Branchements du plugin sur SPIP.
 */

if (!defined('_ECRIRE_INC_VERSION')) {
	return;
}

/** Ajoute notre feuille de style aux ecrans de l'espace prive. */
function marly_header_prive($flux) {
	$css = find_in_path('prive/themes/spip/css/marly.css');
	if ($css) {
		$flux .= "\n<link rel='stylesheet' href='" . direction_css($css) . "' type='text/css' />";
	}
	return $flux;
}
