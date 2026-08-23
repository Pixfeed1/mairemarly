<?php
/**
 * L'annuaire des commerces et services.
 * ---------------------------------------------------------------------------
 * Quatre catégories, écrites une fois ici. Un champ libre donnerait autant de
 * catégories que de saisies — trois personnes écriraient « santé », « Santé »
 * et « médical » — et le regroupement ne marcherait plus. Même raisonnement
 * que pour les thèmes d'association.
 *
 * Les intitulés sont volontairement larges : dans un village de 483 habitants,
 * une catégorie par métier donnerait vingt catégories d'une entrée chacune.
 */

if (!defined('_ECRIRE_INC_VERSION')) {
	return;
}

/** Les catégories, avec leur intitulé et leur icône. */
function marly_categories_commerces() {
	return array(
		'commerce' => array('marly:cat_commerce', 'ri-store-2-line'),
		'sante'    => array('marly:cat_sante',    'ri-heart-pulse-line'),
		'artisan'  => array('marly:cat_artisan',  'ri-tools-line'),
		'service'  => array('marly:cat_service',  'ri-government-line'),
	);
}

/** Les catégories traduites, prêtes pour un menu déroulant. */
function marly_categories_traduites() {
	$out = array();
	foreach (marly_categories_commerces() as $cle => $def) {
		$out[$cle] = _T($def[0]);
	}
	return $out;
}

/**
 * L'intitulé d'une catégorie, pour le filtre #CATEGORIE|marly_categorie_commerce.
 * Rend l'intitulé de la catégorie par défaut si la valeur stockée n'est plus
 * déclarée : une fiche ancienne ne doit pas afficher un code brut.
 */
function marly_categorie_commerce($cle) {
	$cats = marly_categories_commerces();
	$def = $cats[$cle] ?? $cats['commerce'];
	return _T($def[0]);
}

/** L'icône d'une catégorie, pour le filtre #CATEGORIE|marly_icone_commerce. */
function marly_icone_commerce($cle) {
	$cats = marly_categories_commerces();
	$def = $cats[$cle] ?? $cats['commerce'];
	return $def[1];
}
