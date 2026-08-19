<?php
/**
 * Libellés de l'écran de réglages.
 * ---------------------------------------------------------------------------
 * Ils sont écrits pour la secrétaire de mairie, pas pour un informaticien :
 * chaque champ dit ce qu'on y met, et l'explication donne un exemple réel
 * plutôt qu'une description abstraite.
 */
if (!defined('_ECRIRE_INC_VERSION')) {
	return;
}

$GLOBALS[$GLOBALS['idx_lang']] = array(

	'titre_reglages'   => 'Réglages de la commune',
	'intro_reglages'   => 'Ces informations s’affichent dans l’en-tête et le pied de page du site. Laissez vide ce que la commune n’a pas : rien ne s’affichera.',

	'legende_contact'  => 'Coordonnées de la mairie',
	'legende_reseaux'  => 'Réseaux sociaux',
	'legende_appli'    => 'Application d’alerte',

	'telephone'        => 'Téléphone',
	'telephone_aide'   => 'Exemple : 03 23 60 22 69',
	'courriel'         => 'Adresse électronique',
	'adresse'          => 'Adresse postale',
	'adresse_aide'     => 'Numéro et rue, sans le code postal ni la ville',
	'code_postal'      => 'Code postal',
	'ville'            => 'Commune',
	'horaires'         => 'Horaires d’ouverture',
	'horaires_aide'    => 'Une ligne par jour ou par plage. Exemple : Lundi et jeudi, 14 h – 17 h',

	'facebook'         => 'Facebook',
	'instagram'        => 'Instagram',
	'youtube'          => 'YouTube',
	'x'                => 'X (ex-Twitter)',
	'linkedin'         => 'LinkedIn',
	'tiktok'           => 'TikTok',
	'whatsapp'         => 'WhatsApp',
	'mastodon'         => 'Mastodon',
	'bluesky'          => 'Bluesky',
	'reseaux_aide'     => 'Collez l’adresse complète de la page de la commune, en commençant par https://',

	'appli_nom'        => 'Nom de l’application',
	'appli_nom_aide'   => 'Exemple : PanneauPocket, IntraMuros',
	'appli_url'        => 'Adresse de l’application',

	'enregistrer'      => 'Enregistrer',
	'reglages_ok'      => 'Les réglages ont été enregistrés.',
	'erreur_adresse'   => 'L’adresse doit commencer par https:// — copiez-la depuis la barre du navigateur.',
	'erreur_courriel'  => 'Cette adresse électronique ne semble pas valide.',
);
