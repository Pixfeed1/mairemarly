<?php
/**
 * Surcharge de trois libelles de SPIP.
 * ---------------------------------------------------------------------------
 * CE FICHIER NE REMPLACE RIEN, IL AJOUTE PAR-DESSUS. Mecanique verifiee dans
 * ecrire/inc/traduire.php le 26 aout 2026 :
 *
 *   charger_langue()      prend le PREMIER fichier trouve comme base, puis
 *                         passe les suivants a surcharger_langue()
 *   surcharger_langue()   fusionne par array_merge : le dernier gagne
 *   find_langs_in_path()  se termine par array_reverse($liste)
 *
 * creer_chemin() donne le chemin du plus local vers le noyau ; renverse, le
 * fichier du noyau passe premier et devient la base, le notre passe dernier
 * et surcharge. Les 2000 autres chaines de SPIP sont intactes.
 *
 * ON N'EN MET QUE TROIS, et seulement celles qu'une secretaire de mairie lit
 * a l'ecran. Chaque ligne ajoutee ici est une divergence a maintenir a
 * chaque montee de version de SPIP.
 */
if (!defined('_ECRIRE_INC_VERSION')) {
	return;
}

$GLOBALS[$GLOBALS['idx_lang']] = array(

	// « Login » est du jargon. Le mot est en tete du champ le plus vu du
	// site prive. Les deux-points de SPIP sautent : nos autres formulaires
	// n'en portent pas, et l'etoile d'obligation suit deja le libelle.
	'login_login2' => 'Identifiant ou adresse électronique',

	// « email » se dit « adresse électronique » dans la langue de
	// l'administration, et c'est celle que parlent nos pages.
	'entree_adresse_email' => 'Votre adresse électronique',

	// Le bouton du mot de passe oublie s'appelait « OK ». Il ne disait ni ce
	// qu'il fait ni ce qui va suivre. « Valider » plutot que « Envoyer le
	// lien » : la meme chaine sert peut-etre aux deux etapes du formulaire,
	// et l'etape du nouveau mot de passe n'envoie aucun lien. Verite dans
	// les deux cas plutot que precision dans un seul.
	'pass_ok' => 'Valider',

);
