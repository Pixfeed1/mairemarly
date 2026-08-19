<?php
/**
 * Les deux tables de la réservation de salles.
 * ---------------------------------------------------------------------------
 * SALLES — ce que la commune loue. Chaque salle porte ses propres règles :
 * on ne réserve pas la salle des fêtes et la salle du conseil dans les mêmes
 * délais, ni aux mêmes tarifs.
 *
 * RESERVATIONS — une demande, puis son sort. Le statut est le cœur de tout :
 *
 *     demande  → acceptee  : le créneau est pris, plus personne ne peut l'avoir
 *              → refusee   : le créneau redevient libre
 *              → annulee   : après acceptation, la mairie ou le demandeur renonce
 *
 * Seul « acceptee » bloque un créneau. Deux demandes peuvent porter sur la
 * même date — c'est normal, et c'est à la mairie de trancher. Un logiciel qui
 * interdirait la seconde demande cacherait à la mairie qu'il y a conflit.
 */

if (!defined('_ECRIRE_INC_VERSION')) {
	return;
}

function marly_declarer_tables_principales($tables) {

	$tables['spip_salles'] = array(
		'field' => array(
			'id_salle'            => 'bigint(21) NOT NULL',
			'titre'               => 'text NOT NULL DEFAULT ""',
			'descriptif'          => 'text NOT NULL DEFAULT ""',
			'capacite'            => 'int(11) NOT NULL DEFAULT 0',
			'tarif_commune'       => 'varchar(60) NOT NULL DEFAULT ""',
			'tarif_hors_commune'  => 'varchar(60) NOT NULL DEFAULT ""',
			'caution'             => 'varchar(60) NOT NULL DEFAULT ""',
			/* Délais exprimés en jours. Bidart impose « pas plus de 6 mois
			   d'avance » sur une salle et « entre 1 et 3 mois » sur l'autre :
			   ces règles varient d'une salle à l'autre, elles vivent donc
			   sur la salle et non dans le code. */
			'delai_min'           => 'int(11) NOT NULL DEFAULT 3',
			'delai_max'           => 'int(11) NOT NULL DEFAULT 365',
			'statut'              => 'varchar(20) NOT NULL DEFAULT "prepa"',
			'maj'                 => 'TIMESTAMP',
		),
		'key' => array(
			'PRIMARY KEY'  => 'id_salle',
			'KEY statut'   => 'statut',
		),
		'titre'     => 'titre AS titre, "" AS lang',
		'principale' => 'oui',
	);

	$tables['spip_reservations'] = array(
		'field' => array(
			'id_reservation'   => 'bigint(21) NOT NULL',
			'id_salle'         => 'bigint(21) NOT NULL DEFAULT 0',
			'date_debut'       => "datetime NOT NULL DEFAULT '0000-00-00 00:00:00'",
			'date_fin'         => "datetime NOT NULL DEFAULT '0000-00-00 00:00:00'",
			'statut'           => 'varchar(20) NOT NULL DEFAULT "demande"',

			/* Le demandeur. On ne demande pas de compte : exiger une
			   inscription pour réserver une salle des fêtes ferait fuir la
			   moitié des habitants. */
			'nom'              => 'varchar(255) NOT NULL DEFAULT ""',
			'organisme'        => 'varchar(255) NOT NULL DEFAULT ""',
			'courriel'         => 'varchar(255) NOT NULL DEFAULT ""',
			'telephone'        => 'varchar(30) NOT NULL DEFAULT ""',
			'motif'            => 'text NOT NULL DEFAULT ""',

			/* Le traitement par la mairie. */
			'reponse'          => 'text NOT NULL DEFAULT ""',
			'id_auteur'        => 'bigint(21) NOT NULL DEFAULT 0',
			'date_traitement'  => "datetime NOT NULL DEFAULT '0000-00-00 00:00:00'",

			/* Jeton d'annulation : il permet au demandeur d'annuler depuis
			   le courriel de confirmation, sans compte ni mot de passe. */
			'jeton'            => 'varchar(32) NOT NULL DEFAULT ""',

			'date'             => "datetime NOT NULL DEFAULT '0000-00-00 00:00:00'",
			'maj'              => 'TIMESTAMP',
		),
		'key' => array(
			'PRIMARY KEY'      => 'id_reservation',
			'KEY id_salle'     => 'id_salle',
			'KEY statut'       => 'statut',
			/* L'index qui porte la recherche de conflit : on interroge
			   toujours par salle et par date. */
			'KEY creneau'      => 'id_salle,date_debut,date_fin',
			'KEY jeton'        => 'jeton',
		),
		'titre'      => 'nom AS titre, "" AS lang',
		'date'       => 'date',
		'principale' => 'oui',
	);

	return $tables;
}

function marly_declarer_tables_interfaces($interfaces) {
	$interfaces['table_des_tables']['salles']       = 'salles';
	$interfaces['table_des_tables']['reservations'] = 'reservations';
	return $interfaces;
}
