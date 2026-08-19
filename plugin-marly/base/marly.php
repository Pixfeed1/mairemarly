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

function marly_declarer_tables_objets_sql($tables) {

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
		'titre'      => 'titre AS titre, "" AS lang',
		'principale' => 'oui',
		'type'       => 'salle',
		'editable'   => 'oui',
		'champs_editables'  => array('titre', 'descriptif', 'capacite', 'tarif_commune',
		                             'tarif_hors_commune', 'caution', 'delai_min', 'delai_max'),
		'rechercher_champs' => array('titre' => 8, 'descriptif' => 3),
		'statut' => array(
			array(
				'champ'     => 'statut',
				'publie'    => 'publie',
				'previsu'   => 'publie,prepa',
				'exception' => 'statut',
			),
		),
	);


	/*
	 * MANIFESTATIONS — tout ce à quoi on s'inscrit : repas des aînés, sortie
	 * en car, emplacement de brocante, atelier, séance de cinéma itinérant.
	 *
	 * La différence avec une salle n'est pas de nature mais de comptage.
	 * Une salle, c'est « ce créneau est-il libre ? » — une exclusivité.
	 * Une manifestation, c'est « combien reste-t-il de places ? » — un stock.
	 * Deux questions différentes, donc deux tables.
	 *
	 * Le nom : PAS spip_evenements. Le plugin Agenda de SPIP utilise déjà ce
	 * nom, et le jour où la mairie l'installerait pour son calendrier, les
	 * deux se marcheraient dessus.
	 */
	$tables['spip_manifestations'] = array(
		'field' => array(
			'id_manifestation' => 'bigint(21) NOT NULL',
			'titre'            => 'text NOT NULL DEFAULT ""',
			'descriptif'       => 'text NOT NULL DEFAULT ""',
			'lieu'             => 'varchar(255) NOT NULL DEFAULT ""',
			'date_debut'       => "datetime NOT NULL DEFAULT '0000-00-00 00:00:00'",
			'date_fin'         => "datetime NOT NULL DEFAULT '0000-00-00 00:00:00'",

			/* Le stock. 0 signifie « sans limite » : une kermesse en plein air
			   n'a pas de jauge, et forcer un nombre obligerait à en inventer un. */
			'places'           => 'int(11) NOT NULL DEFAULT 0',
			'places_par_personne' => 'int(11) NOT NULL DEFAULT 1',

			'tarif'            => 'varchar(60) NOT NULL DEFAULT ""',

			/* auto   : l'inscription est confirmée tout de suite. C'est ce
			            qu'attend quelqu'un qui s'inscrit au repas des aînés.
			   mairie : la mairie arbitre, comme pour une salle. */
			'validation'       => 'varchar(10) NOT NULL DEFAULT "auto"',

			/* La fenêtre d'inscription, distincte de la date de l'événement :
			   on ouvre les inscriptions au repas six semaines avant, et on les
			   ferme huit jours avant pour commander les couverts. */
			'ouverture'        => "datetime NOT NULL DEFAULT '0000-00-00 00:00:00'",
			'cloture'          => "datetime NOT NULL DEFAULT '0000-00-00 00:00:00'",

			'statut'           => 'varchar(20) NOT NULL DEFAULT "prepa"',
			'maj'              => 'TIMESTAMP',
		),
		'key' => array(
			'PRIMARY KEY'   => 'id_manifestation',
			'KEY statut'    => 'statut',
			'KEY date_debut' => 'date_debut',
		),
		'titre'      => 'titre AS titre, "" AS lang',
		'date'       => 'date_debut',
		'principale' => 'oui',
		'type'       => 'manifestation',
		'editable'   => 'oui',

		/* C'est cette declaration qui ouvre les LOGOS : SPIP autorise une
		   image sur tout objet editorial declare ici. Sans elle, la table
		   existe et les boucles marchent, mais il n'y a nulle part ou
		   deposer la photo. */
		'champs_editables'  => array('titre', 'descriptif', 'lieu', 'date_debut', 'date_fin',
		                             'places', 'places_par_personne', 'tarif', 'validation',
		                             'ouverture', 'cloture'),
		'rechercher_champs' => array('titre' => 8, 'descriptif' => 3, 'lieu' => 2),
		'statut' => array(
			array(
				'champ'     => 'statut',
				'publie'    => 'publie',
				'previsu'   => 'publie,prepa',
				'exception' => 'statut',
			),
		),
		'texte_modifier'    => 'marly:modifier_manifestation',
		'texte_creer'       => 'marly:creer_manifestation',
		'info_aucun_objet'  => 'marly:aucune_manifestation',
	);

	$tables['spip_reservations'] = array(
		'field' => array(
			'id_reservation'   => 'bigint(21) NOT NULL',
			/* Une réservation porte SOIT sur une salle, SOIT sur une
			   manifestation. L'autre colonne reste à zéro. Une seule table
			   pour les deux : même cycle de statuts, même écran de gestion,
			   mêmes courriels — les séparer aurait tout dupliqué. */
			'id_salle'         => 'bigint(21) NOT NULL DEFAULT 0',
			'id_manifestation' => 'bigint(21) NOT NULL DEFAULT 0',
			'places'           => 'int(11) NOT NULL DEFAULT 1',
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
			'KEY id_manifestation' => 'id_manifestation',
			'KEY statut'       => 'statut',
			/* L'index qui porte la recherche de conflit : on interroge
			   toujours par salle et par date. */
			'KEY creneau'      => 'id_salle,date_debut,date_fin',
			'KEY jeton'        => 'jeton',
		),
		'titre'      => 'nom AS titre, "" AS lang',
		'date'       => 'date',
		'principale' => 'oui',
		'type'       => 'reservation',
		'editable'   => 'non',
	);

	return $tables;
}

function marly_declarer_tables_interfaces($interfaces) {
	$interfaces['table_des_tables']['salles']       = 'salles';
	$interfaces['table_des_tables']['reservations'] = 'reservations';
	$interfaces['table_des_tables']['manifestations'] = 'manifestations';
	return $interfaces;
}
