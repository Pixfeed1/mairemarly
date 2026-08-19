<?php
/**
 * Ce que l'espace privé affiche du plugin dans la liste des plugins.
 * ---------------------------------------------------------------------------
 * Le nom, le slogan et la description ne vivent PAS dans paquet.xml : ce
 * fichier est leur place. Le XML ne décrit que la mécanique — version,
 * compatibilité, entrées de menu.
 */
if (!defined('_ECRIRE_INC_VERSION')) {
	return;
}

$GLOBALS[$GLOBALS['idx_lang']] = array(

	'marly_nom'         => 'Réglages de Marly-Gomont',
	'marly_slogan'      => 'Coordonnées, horaires et réseaux sociaux de la commune',
	'marly_description' => 'Ajoute un écran unique dans Configuration pour saisir les
		coordonnées de la mairie, ses horaires et ses réseaux sociaux. Rien d’autre :
		ni table, ni champ, ni traitement. Il existe pour que déclarer un compte
		Facebook ne demande pas de créer un article.',
);
