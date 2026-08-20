<?php
/**
 * Branchements du plugin sur SPIP.
 * ---------------------------------------------------------------------------
 * L'habillage de l'espace privé ne passe PAS par ici. Il vit dans
 * prive/style_prive_plugin_marly.html, un nom que SPIP reconnaît et compile
 * tout seul dans sa feuille de style.
 *
 * Le pipeline header_prive faisait la même chose, mais il demandait de nommer
 * correctement une fonction, de la déclarer dans paquet.xml, et de purger le
 * cache des plugins pour que SPIP la voie. Trois occasions de se tromper
 * silencieusement — et c'est ce qui s'est produit.
 */

if (!defined('_ECRIRE_INC_VERSION')) {
	return;
}
