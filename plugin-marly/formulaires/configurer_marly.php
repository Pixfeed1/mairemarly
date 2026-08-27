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

/**
 * Les images déposées depuis cet écran.
 * ---------------------------------------------------------------------------
 * Deux, et elles ne servent pas au même endroit :
 *   banniere — la grande photographie du haut de la page d'accueil ;
 *   bandeau  — la bande étroite en tête des pages de section, celles qui
 *              n'existent pas dans la base et n'ont donc pas de rubrique à
 *              qui emprunter un logo.
 *
 * Une rubrique, elle, garde son propre logo : c'est déjà le cas et c'est plus
 * juste, chaque rubrique méritant sa propre image.
 */
function marly_champs_image() {
	return array('banniere', 'bandeau');
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
		/* Le crédit de la photographie de bannière. Il s'affiche en petit dans
		   son coin. Une photographie a un auteur, et la mairie doit pouvoir le
		   citer sans appeler personne. */
		'banniere_credit', 'bandeau_credit',
	);
}

function marly_champs() {
	return array_merge(marly_champs_texte(), marly_champs_url());
}

/**
 * Le fichier déjà en place pour ce champ, ou ''.
 * ---------------------------------------------------------------------------
 * On relit le DISQUE, pas seulement la configuration : si le fichier a été
 * effacé à la main, la configuration mentirait et la page publique appellerait
 * une image absente. Ce qui n'existe pas ne s'affiche pas.
 */
function marly_image_fichier($champ) {
	$nom = lire_config('marly/' . $champ, '');
	if ($nom === '' or !@is_file(_DIR_IMG . $nom)) {
		return '';
	}
	return $nom;
}

function formulaires_configurer_marly_charger_dist() {
	$valeurs = array();
	foreach (marly_champs() as $champ) {
		$valeurs[$champ] = lire_config('marly/' . $champ, '');
	}
	foreach (marly_champs_image() as $champ) {
		$valeurs[$champ] = marly_image_fichier($champ);
		$valeurs[$champ . '_url'] = $valeurs[$champ] ? _DIR_IMG . $valeurs[$champ] : '';
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

	/* LES IMAGES DEPOSEES. On refuse ici plutot que d'accepter et d'afficher
	   mal : une image trop petite s'agrandit et pixelise sur toute la largeur
	   de l'ecran, et c'est la premiere chose que voit un visiteur.

	   getimagesize() sert de controle de type ET de mesure : elle rend false
	   sur tout ce qui n'est pas une image, quel que soit le nom du fichier. Se
	   fier a l'extension laisserait passer un .jpg qui n'en est pas un. */
	foreach (marly_champs_image() as $champ) {
		$envoi = isset($_FILES[$champ]) ? $_FILES[$champ] : null;
		if (!$envoi or $envoi['error'] === UPLOAD_ERR_NO_FILE) {
			continue;
		}
		if ($envoi['error'] !== UPLOAD_ERR_OK) {
			/* Le cas le plus frequent : le fichier depasse la limite du
			   serveur, qui n'est pas la notre. On le dit dans les termes de
			   l'usager plutot que de rendre un code. */
			$erreurs[$champ] = _T('marly:erreur_banniere_envoi');
			continue;
		}
		$taille = @getimagesize($envoi['tmp_name']);
		if (!$taille or !in_array($taille[2], array(IMAGETYPE_JPEG, IMAGETYPE_PNG, IMAGETYPE_WEBP), true)) {
			$erreurs[$champ] = _T('marly:erreur_banniere_type');
		} elseif ($taille[0] < 1200) {
			$erreurs[$champ] = _T('marly:erreur_banniere_petite',
				array('largeur' => (int) $taille[0]));
		} elseif ($taille[0] < $taille[1]) {
			$erreurs[$champ] = _T('marly:erreur_banniere_portrait');
		} elseif ($envoi['size'] > 8 * 1024 * 1024) {
			$erreurs[$champ] = _T('marly:erreur_banniere_lourde');
		}
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

	/* La carte de la page Contact a besoin d'un point, et la secrétaire n'a
	   pas à saisir une latitude. On la calcule depuis l'adresse, avec le même
	   géocodeur que les commerces, et seulement quand l'adresse a changé :
	   interroger un service à chaque enregistrement pour un point qui ne bouge
	   jamais serait payer sans raison.

	   L'échec est silencieux À DESSEIN. Le géocodeur peut ne rien trouver, ou
	   être injoignable ; ce n'est pas une raison pour refuser d'enregistrer
	   l'adresse et le téléphone de la mairie. Sans point, la page Contact
	   n'affiche pas de cadre, et l'adresse écrite au-dessus porte
	   l'information de toute façon. */
	$adresse = trim(implode(' ', array_filter(array(
		trim((string) _request('adresse')),
		trim((string) _request('code_postal')),
		trim((string) _request('ville')),
	))));
	if ($adresse !== '' and $adresse !== lire_config('marly/adresse_situee', '')) {
		include_spip('inc/marly_geocodage');
		$point = marly_geocoder($adresse);
		ecrire_config('marly/latitude',  (string) ($point['latitude'] ?? ''));
		ecrire_config('marly/longitude', (string) ($point['longitude'] ?? ''));
		ecrire_config('marly/adresse_situee', $adresse);
	}
	if ($adresse === '') {
		ecrire_config('marly/latitude', '');
		ecrire_config('marly/longitude', '');
		ecrire_config('marly/adresse_situee', '');
	}

	foreach (marly_champs_image() as $champ) {
		marly_enregistrer_image($champ);
	}

	return array(
		'message_ok' => _T('marly:reglages_ok'),
		'editable'   => true,
	);
}

/**
 * Enregistre l'image déposée pour ce champ, ou la retire.
 * ---------------------------------------------------------------------------
 * LE FICHIER VA DANS IMG/, ET C'EST UN CHOIX. Le dossier des squelettes est
 * recopié à chaque déploiement : une image déposée là serait effacée au
 * prochain envoi de code, sans que personne comprenne pourquoi la bannière a
 * disparu. IMG/ appartient au site, pas au thème, et survit.
 *
 * LE NOM PORTE UN HORODATAGE, et ce n'est pas de la coquetterie. Avec un nom
 * fixe, le navigateur de la secrétaire garderait l'ancienne image en cache et
 * elle croirait que l'enregistrement n'a pas marché — le genre de faux
 * problème qui coûte un appel téléphonique. Un nom neuf est une adresse neuve.
 * L'ancien fichier est effacé dans la foulée, sinon IMG/ se remplirait de
 * bannières mortes.
 */
function marly_enregistrer_image($champ) {
	$ancien = lire_config('marly/' . $champ, '');

	/* La case « retirer » : on revient à la photographie du thème. */
	if (_request($champ . '_retirer')) {
		if ($ancien !== '' and @is_file(_DIR_IMG . $ancien)) {
			@unlink(_DIR_IMG . $ancien);
		}
		ecrire_config('marly/' . $champ, '');
		return;
	}

	$envoi = isset($_FILES[$champ]) ? $_FILES[$champ] : null;
	if (!$envoi or $envoi['error'] !== UPLOAD_ERR_OK) {
		return;
	}

	$taille = @getimagesize($envoi['tmp_name']);
	if (!$taille) {
		return;
	}
	$extensions = array(
		IMAGETYPE_JPEG => 'jpg',
		IMAGETYPE_PNG  => 'png',
		IMAGETYPE_WEBP => 'webp',
	);
	if (!isset($extensions[$taille[2]])) {
		return;
	}

	$nom = 'marly-' . $champ . '-' . time() . '.' . $extensions[$taille[2]];
	if (!@move_uploaded_file($envoi['tmp_name'], _DIR_IMG . $nom)) {
		return;
	}
	@chmod(_DIR_IMG . $nom, 0644);

	/* ON RELIT LE DISQUE AVANT D'ENREGISTRER LE NOM. Sans ce contrôle, un
	   déplacement qui échoue à moitié laisserait la configuration pointer vers
	   un fichier absent, et la page d'accueil appellerait une image morte. */
	if (!@is_file(_DIR_IMG . $nom)) {
		return;
	}

	if ($ancien !== '' and $ancien !== $nom and @is_file(_DIR_IMG . $ancien)) {
		@unlink(_DIR_IMG . $ancien);
	}
	ecrire_config('marly/' . $champ, $nom);
}
