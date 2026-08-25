<?php
/**
 * Formulaire de réglages de la commune.
 * ---------------------------------------------------------------------------
 * Trois fonctions, c'est le contrat des formulaires SPIP (CVT) :
 *   charger  — ce qu'on affiche au départ
 *   verifier — ce qu'on refuse
 *   traiter  — ce qu'on enregistre
 *
 * Les valeurs vont dans la table des méta, sous le préfixe « marly ». Les
 * squelettes les relisent par #CONFIG{marly/telephone}. Aucune n'est écrite
 * en dur nulle part.
 */

if (!defined('_ECRIRE_INC_VERSION')) {
	return;
}

include_spip('inc/config');

/**
 * Les champs qui contiennent une adresse web.
 * Séparés des autres parce qu'ils sont les seuls à être vérifiés.
 */
function marly_champs_url() {
	return array(
		'facebook', 'instagram', 'youtube', 'x', 'linkedin',
		'tiktok', 'whatsapp', 'mastodon', 'bluesky', 'appli_url',
	);
}

/** Les champs de texte libre. */
function marly_champs_texte() {
	return array(
		'telephone', 'courriel', 'adresse', 'code_postal', 'ville',
		'horaires', 'appli_nom',
		/* Les trois lignes d'urbanisme. Elles ne sont pas du décor : elles
		   disent qui délivre les permis et à partir de quelle surface il en
		   faut un. Vides, la page publique n'affiche pas le bloc — mieux vaut
		   ne rien dire que d'envoyer les gens frapper à la mauvaise porte. */
		'urbanisme_document', 'urbanisme_decide', 'urbanisme_clotures',
		/* Le niveau de conformité affiché en pied de page. Vide tant qu'aucun
		   audit n'a eu lieu : annoncer un niveau qu'on n'a pas mesuré est une
		   déclaration fausse, et c'est une déclaration légale. */
		'accessibilite_niveau',
	);
}

function marly_champs() {
	return array_merge(marly_champs_texte(), marly_champs_url());
}

function formulaires_configurer_marly_charger_dist() {
	$valeurs = array();
	foreach (marly_champs() as $champ) {
		$valeurs[$champ] = lire_config('marly/' . $champ, '');
	}
	return $valeurs;
}

function formulaires_configurer_marly_verifier_dist() {
	$erreurs = array();

	/* Une adresse sans https:// ne mène nulle part une fois cliquée. On
	   refuse plutôt que d'enregistrer un lien mort : l'erreur se verrait
	   des mois plus tard, quand quelqu'un cliquerait. */
	foreach (marly_champs_url() as $champ) {
		$valeur = trim((string) _request($champ));
		if ($valeur !== '' && !preg_match(',^https?://.+,i', $valeur)) {
			$erreurs[$champ] = _T('marly:erreur_adresse');
		}
	}

	$courriel = trim((string) _request('courriel'));
	if ($courriel !== '' && !filter_var($courriel, FILTER_VALIDATE_EMAIL)) {
		$erreurs['courriel'] = _T('marly:erreur_courriel');
	}

	return $erreurs;
}

function formulaires_configurer_marly_traiter_dist() {
	/* L'écran de réglages est protégé, mais l'action du formulaire reste une
	   URL : sans ce contrôle, un compte rédacteur pourrait réécrire les
	   coordonnées de la mairie en postant directement dessus. */
	if (!autoriser('configurer')) {
		return array('message_erreur' => _T('avis_operation_impossible'));
	}

	foreach (marly_champs() as $champ) {
		ecrire_config('marly/' . $champ, trim((string) _request($champ)));
	}

	return array(
		'message_ok' => _T('marly:reglages_ok'),
		'editable'   => true,
	);
}
