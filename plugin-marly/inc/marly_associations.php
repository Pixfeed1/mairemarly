<?php
/**
 * Les thèmes de la vie associative.
 * ---------------------------------------------------------------------------
 * Liste FERMÉE, et c'est le point important. Dix associations saisies par
 * trois personnes différentes donneraient « sport », « Sports » et
 * « sportif » : le regroupement par thème ne marcherait plus, et personne ne
 * comprendrait pourquoi.
 *
 * Sept entrées : assez pour qu'une association se reconnaisse, assez peu pour
 * que chaque groupe contienne plus d'une ligne. Dans une commune de 483
 * habitants, un thème par association n'est pas un classement.
 */

if (!defined('_ECRIRE_INC_VERSION')) {
	return;
}

function marly_themes_associations() {
	return array(
		'sport'      => 'marly:theme_sport',
		'culture'    => 'marly:theme_culture',
		'enfance'    => 'marly:theme_enfance',
		'solidarite' => 'marly:theme_solidarite',
		'patrimoine' => 'marly:theme_patrimoine',
		'memoire'    => 'marly:theme_memoire',
		'autre'      => 'marly:theme_autre',
	);
}

/** Les thèmes traduits, prêts pour un menu déroulant. */
function marly_themes_traduits() {
	$out = array();
	foreach (marly_themes_associations() as $cle => $intitule) {
		$out[$cle] = _T($intitule);
	}
	return $out;
}

/** L'intitulé d'un thème, pour l'affichage public. */
function filtre_marly_theme_association_dist($theme) {
	$themes = marly_themes_associations();
	return isset($themes[$theme]) ? _T($themes[$theme]) : '';
}
