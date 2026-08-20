<?php
/**
 * Branchements du plugin sur SPIP.
 * ---------------------------------------------------------------------------
 * L'habillage de l'espace privé ne passe PAS par ici. Il vit dans
 * prive/squelettes/inc/marly-styles.html, inclus en tête de chacun de nos
 * écrans.
 *
 * Deux autres voies ont été essayées avant : le pipeline header_prive, puis
 * la convention prive/style_prive_plugin_marly.html. Aucune n'est arrivée
 * jusqu'au navigateur, et surtout aucune ne le disait : elles supposent que
 * SPIP ait recensé le plugin, recompilé sa feuille de style et purgé son
 * cache. Un bloc <style> posé dans l'écran ne suppose rien — si l'écran
 * s'affiche, le style est là.
 */

if (!defined('_ECRIRE_INC_VERSION')) {
	return;
}
