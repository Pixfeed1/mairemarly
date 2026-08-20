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

/**
 * La rubrique d'une association, créée au besoin.
 * ---------------------------------------------------------------------------
 * Créée dès l'enregistrement de l'association, et non sur demande. Une
 * rubrique vide n'apparaît PAS sur le site : SPIP ne publie une rubrique que
 * lorsqu'elle contient au moins un article publié. Elle ne coûte donc rien
 * tant que personne n'écrit, et le jour où l'association veut publier, tout
 * est déjà en place — personne n'a à comprendre ce qu'est une rubrique.
 *
 * La rubrique d'accueil est cherchée parmi les rubriques racines, à son
 * titre. Si aucune ne convient, elle est créée : mieux vaut une rubrique
 * « Vie associative » posée d'office qu'une association rattachée n'importe
 * où.
 *
 * Rend l'identifiant, ou 0 si SPIP n'a pas pu créer la rubrique. L'échec est
 * silencieux et sans conséquence : l'association est enregistrée, il lui
 * manque seulement sa rubrique, que la mairie peut choisir à la main.
 */
function marly_rubrique_association($nom) {
	include_spip('action/editer_objet');
	if (!function_exists('objet_inserer')) {
		spip_log('marly : objet_inserer indisponible, rubrique non creee', 'marly.' . _LOG_INFO_IMPORTANTE);
		return 0;
	}

	$parent = sql_getfetsel('id_rubrique', 'spip_rubriques',
		"id_parent = 0 AND (titre LIKE " . sql_quote('%associ%')
		. " OR titre LIKE " . sql_quote('%Vie asso%') . ')');

	if (!$parent) {
		$parent = objet_inserer('rubrique', 0);
		if (!$parent) {
			return 0;
		}
		objet_modifier('rubrique', $parent, array(
			'titre'      => _T('marly:titre_vie_associative'),
			'descriptif' => _T('marly:assos_intro'),
		));
	}

	$id = objet_inserer('rubrique', $parent);
	if (!$id) {
		return 0;
	}
	objet_modifier('rubrique', $id, array('titre' => $nom));
	spip_log("marly : rubrique $id creee pour l'association " . $nom, 'marly.' . _LOG_INFO_IMPORTANTE);

	return $id;
}
