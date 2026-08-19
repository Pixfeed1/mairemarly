<?php
/**
 * Installation et désinstallation.
 * ---------------------------------------------------------------------------
 * SPIP appelle marly_upgrade() à l'activation et à chaque montée de version.
 * maj_plugin() compare la version enregistrée à la version cible et n'exécute
 * que les étapes manquantes : une installation neuve les joue toutes, une
 * mise à jour ne rejoue rien.
 */

if (!defined('_ECRIRE_INC_VERSION')) {
	return;
}

function marly_upgrade($nom_meta_base_version, $version_cible) {
	$maj = array();

	/* 1.0.0 — le plugin ne faisait que des réglages : rien en base. */
	$maj['1.0.0'] = array();

	/* 2.0.0 — les deux tables de la réservation. creer_base() les crée à
	   partir des déclarations de base/marly.php : aucun SQL écrit ici, donc
	   aucune divergence possible entre la déclaration et la table réelle. */
	$maj['2.0.0'] = array(
		array('maj_tables', array('spip_salles', 'spip_reservations')),
	);

	/* 3.0.0 — les manifestations, et deux colonnes de plus sur les
	   réservations. maj_tables ajoute les colonnes manquantes sans toucher
	   aux données : les réservations de salles déjà saisies survivent. */
	$maj['3.0.0'] = array(
		array('maj_tables', array('spip_manifestations', 'spip_reservations')),
	);

	/* 3.1.0 — les tables passent en objets editoriaux declares (l'API de
	   SPIP 4). Aucune colonne ne change : c'est la declaration qui evolue,
	   pour ouvrir les logos et la recherche. maj_tables est rejoue par
	   securite, il ne fera rien si tout est deja en place. */
	$maj['3.1.0'] = array(
		array('maj_tables', array('spip_manifestations', 'spip_salles', 'spip_reservations')),
	);

	/* 3.2.0 — une colonne video sur les evenements. */
	$maj['3.2.0'] = array(
		array('maj_tables', array('spip_manifestations')),
	);

	include_spip('base/upgrade');
	maj_plugin($nom_meta_base_version, $version_cible, $maj);
}

function marly_vider_tables($nom_meta_base_version) {
	/* On supprime les tables : ce sont les nôtres, personne d'autre ne les
	   lit. Les réglages, eux, restent — désactiver le plugin ne doit pas
	   faire perdre le numéro de téléphone de la mairie. Ils seront effacés
	   avec la meta ci-dessous seulement si l'on désinstalle vraiment. */
	sql_drop_table('spip_manifestations');
	sql_drop_table('spip_reservations');
	sql_drop_table('spip_salles');

	effacer_meta($nom_meta_base_version);
}
