<?php
/**
 * Protection des formulaires publics contre les robots.
 * ---------------------------------------------------------------------------
 * PAS de reCAPTCHA. Trois raisons, dans cet ordre :
 *
 *   1. c'est un service de Google appelé sur chaque visiteur, donc un
 *      transfert de données qu'une collectivité ne peut pas justifier ;
 *   2. il bloque une partie des usagers — déficience visuelle, troubles
 *      cognitifs, connexion lente, navigateur ancien. Sur un site où
 *      l'usager est parfois âgé, c'est disqualifiant ;
 *   3. il ne sert à rien ici. Le spam qui arrive sur un site de commune de
 *      483 habitants n'est pas ciblé : ce sont des robots génériques qui
 *      remplissent tout ce qu'ils trouvent.
 *
 * Quatre barrières, aucune visible pour un usager :
 *
 *   - le jeton CSRF de SPIP, déjà posé par #ACTION_FORMULAIRE
 *   - un champ-piège qu'un humain ne voit pas et ne remplit donc jamais
 *   - un délai minimum : un robot remplit en moins d'une seconde
 *   - le refus des adresses web dans les champs d'identité
 *
 * Aucune n'arrête un humain déterminé. Ensemble elles arrêtent la totalité
 * du spam automatique, qui est le seul qu'on rencontre à cette échelle.
 */

if (!defined('_ECRIRE_INC_VERSION')) {
	return;
}

/** Le délai en dessous duquel on considère que ce n'est pas un humain. */
define('_MARLY_DELAI_MINIMUM', 4);

/** Au-delà, le formulaire est resté ouvert trop longtemps : on redemande. */
define('_MARLY_DELAI_MAXIMUM', 86400);

/**
 * Signe un horodatage avec le secret du site.
 * Sans signature, un robot changerait la valeur : le champ est dans le HTML.
 */
function marly_signer($t) {
	return $t . '-' . substr(md5($t . ($GLOBALS['meta']['secret_du_site'] ?? '')), 0, 12);
}

/** Les valeurs à injecter dans le contexte du formulaire. */
function marly_antispam_charger() {
	return array(
		'_piege'  => '',
		'_pose'   => marly_signer(time()),
	);
}

/**
 * Vérifie. Rend une chaîne d'erreur, ou une chaîne vide.
 *
 * Le message rendu à un robot n'a aucune importance ; celui rendu à un
 * humain pris à tort en a beaucoup. D'où un message qui propose toujours
 * une porte de sortie — retenter, ou téléphoner.
 */
function marly_antispam_verifier() {

	/* 1. Le champ-piège. Un humain ne le voit pas : il est sorti de l'écran
	      et retiré de l'ordre de tabulation. Un robot remplit tous les
	      champs qu'il trouve dans le HTML. */
	if (trim((string) _request('_piege')) !== '') {
		spip_log('marly : formulaire rejete, champ-piege rempli', 'marly.' . _LOG_INFO_IMPORTANTE);
		return _T('marly:erreur_robot');
	}

	/* 2. Le délai. */
	$pose = (string) _request('_pose');
	if (!preg_match(',^(\d{9,})-([0-9a-f]{12})$,', $pose, $r)) {
		return _T('marly:erreur_formulaire_expire');
	}
	if (marly_signer($r[1]) !== $pose) {
		spip_log('marly : formulaire rejete, horodatage falsifie', 'marly.' . _LOG_INFO_IMPORTANTE);
		return _T('marly:erreur_robot');
	}

	$ecoule = time() - intval($r[1]);
	if ($ecoule < _MARLY_DELAI_MINIMUM) {
		spip_log("marly : formulaire rejete, rempli en {$ecoule}s", 'marly.' . _LOG_INFO_IMPORTANTE);
		return _T('marly:erreur_trop_vite');
	}
	if ($ecoule > _MARLY_DELAI_MAXIMUM) {
		return _T('marly:erreur_formulaire_expire');
	}

	/* 3. Les adresses web dans les champs d'identité. Personne ne s'appelle
	      « https:// ». C'est la signature la plus fiable du spam de masse. */
	foreach (array('nom', 'organisme') as $champ) {
		$valeur = (string) _request($champ);
		if (preg_match(',https?://|www\.|\[url,i', $valeur)) {
			spip_log("marly : formulaire rejete, adresse web dans $champ", 'marly.' . _LOG_INFO_IMPORTANTE);
			return _T('marly:erreur_robot');
		}
	}

	/* 4. Le débit. Une même adresse électronique qui dépose cinq demandes
	      en attente n'est pas un habitant pressé. On ne compte pas par
	      adresse IP : la conserver demanderait une justification de plus au
	      registre des traitements, pour un gain nul à cette échelle. */
	$courriel = trim((string) _request('courriel'));
	if ($courriel) {
		$en_cours = sql_countsel('spip_reservations', array(
			'courriel = ' . sql_quote($courriel),
			"statut = 'demande'",
			'date > ' . sql_quote(date('Y-m-d H:i:s', time() - 86400)),
		));
		if ($en_cours >= 5) {
			return _T('marly:erreur_trop_de_demandes');
		}
	}

	return '';
}
