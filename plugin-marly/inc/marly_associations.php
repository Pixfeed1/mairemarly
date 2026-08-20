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

/**
 * Trouve ou crée le compte rédacteur d'une association.
 *
 * Rend le login, ou '' si rien n'a pu être fait. Le compte est créé SANS
 * mot de passe : personne ne peut s'en servir tant que son titulaire ne
 * l'a pas défini lui-même par le lien « mot de passe oublié ». On n'envoie
 * donc jamais de mot de passe par courriel, et aucun mot de passe
 * provisoire ne traîne nulle part : c'est le circuit de SPIP qui fait
 * autorité, pas un des nôtres.
 *
 * Rédacteur, pas plus : il écrit, la mairie publie. Le maire est
 * directeur de publication.
 */
function marly_compte_redacteur($nom, $courriel) {
	$courriel = trim((string) $courriel);
	if (!$courriel or !filter_var($courriel, FILTER_VALIDATE_EMAIL)) {
		return '';
	}

	$existant = sql_fetsel('id_auteur, login', 'spip_auteurs', 'email = ' . sql_quote($courriel));
	if ($existant) {
		return $existant['login'] ?: $courriel;
	}

	include_spip('action/editer_objet');
	$id_auteur = objet_inserer('auteur');
	if (!$id_auteur) {
		spip_log("marly : creation du compte impossible pour $courriel",
			'marly.' . _LOG_INFO_IMPORTANTE);
		return '';
	}

	/* Directement en base, a dessein : l'API des auteurs melange droits et
	   institution, et un echec silencieux ici laisserait un compte fantome.
	   Les colonnes posees sont simples et connues. pass vide = connexion
	   impossible tant que le titulaire n'a pas defini son mot de passe. */
	sql_updateq('spip_auteurs', array(
		'nom'    => (string) $nom ?: $courriel,
		'login'  => $courriel,
		'email'  => $courriel,
		'statut' => '1comite',
		'pass'   => '',
	), 'id_auteur = ' . intval($id_auteur));

	spip_log("marly : compte redacteur $courriel cree (auteur $id_auteur)",
		'marly.' . _LOG_INFO_IMPORTANTE);

	return $courriel;
}

/**
 * Le courriel envoyé quand la mairie publie une fiche demandée.
 *
 * C'est le deuxième temps du pipeline : demande, validation, puis ce
 * message, qui confirme la parution et donne l'accès. Le lien mène au
 * circuit « mot de passe » de SPIP : la personne choisit son mot de passe
 * elle-même, rien ne circule en clair.
 */
function marly_prevenir_association_publiee($id_association) {
	$fiche = sql_fetsel('nom, president, courriel', 'spip_associations',
		'id_association = ' . intval($id_association));
	if (!$fiche or !trim((string) $fiche['courriel'])) {
		return false;
	}

	$login = marly_compte_redacteur($fiche['president'], $fiche['courriel']);

	include_spip('inc/filtres');
	$url_site = url_absolue(generer_url_public('associations', ''));
	$url_pass = url_absolue(generer_url_public('spip_pass', ''));

	$corps = _T('marly:asso_publiee_corps', array('nom' => $fiche['nom'])) . "\n\n"
		. $url_site . "\n\n";
	if ($login) {
		$corps .= _T('marly:asso_publiee_acces', array('login' => $login)) . "\n"
			. $url_pass . "\n\n"
			. _T('marly:asso_publiee_role') . "\n\n";
	}
	$corps .= _T('marly:asso_publiee_maj');

	$envoyer = charger_fonction('envoyer_mail', 'inc', true);
	if (!$envoyer or !$envoyer($fiche['courriel'],
			_T('marly:asso_publiee_objet', array('nom' => $fiche['nom'])), $corps)) {
		spip_log('marly : courriel de publication non envoye a ' . $fiche['courriel'],
			'marly.' . _LOG_INFO_IMPORTANTE);
		return false;
	}

	spip_log('marly : fiche publiee, courriel envoye a ' . $fiche['courriel'],
		'marly.' . _LOG_INFO_IMPORTANTE);
	return true;
}
