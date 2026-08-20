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
		/* La vie religieuse a son propre groupe, et n'est pas rangee dans
		   << autres >>. Ce n'est pas une faveur : c'est une rubrique que les gens
		   cherchent nommement — horaires des offices, contact du presbytere — et
		   la noyer parmi les autres reviendrait a la cacher. Une commune peut
		   informer sur les cultes presents sur son territoire ; ce qu'elle ne
		   peut pas, c'est en promouvoir un. D'ou une fiche strictement
		   factuelle, au meme format que toutes les autres. */
		'culte'      => 'marly:theme_culte',
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
