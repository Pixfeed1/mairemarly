<?php
/**
 * La logique de réservation, en un seul endroit.
 * ---------------------------------------------------------------------------
 * Le formulaire public, l'écran de gestion et l'action d'acceptation posent
 * tous la même question — « ce créneau est-il libre ? » — et elle doit
 * recevoir la même réponse partout. Trois copies de cette règle finiraient
 * par diverger.
 */

if (!defined('_ECRIRE_INC_VERSION')) {
	return;
}

/**
 * Les réservations acceptées qui chevauchent le créneau demandé.
 *
 * Deux intervalles se chevauchent si chacun commence avant que l'autre ne
 * finisse. C'est la seule formulation juste : comparer les seules dates de
 * début laisse passer une réservation qui englobe la nouvelle.
 *
 * Les bornes sont exclusives : une salle rendue à 12 h peut être reprise à
 * 12 h. L'inverse interdirait deux locations le même jour.
 *
 * @param int    $id_salle
 * @param string $debut          date SQL
 * @param string $fin            date SQL
 * @param int    $sauf           id_reservation à ignorer (la sienne)
 * @return array                 les réservations en conflit
 */
function marly_conflits($id_salle, $debut, $fin, $sauf = 0) {
	$where = array(
		'id_salle = ' . intval($id_salle),
		'statut = ' . sql_quote('acceptee'),
		'date_debut < ' . sql_quote($fin),
		'date_fin > ' . sql_quote($debut),
	);
	if ($sauf) {
		$where[] = 'id_reservation <> ' . intval($sauf);
	}

	return sql_allfetsel(
		'id_reservation, nom, organisme, date_debut, date_fin',
		'spip_reservations',
		$where,
		'', 'date_debut'
	);
}

/**
 * Les demandes en attente qui chevauchent le créneau : elles ne bloquent
 * rien, mais la mairie doit les voir avant d'accepter celle-ci.
 */
function marly_demandes_concurrentes($id_salle, $debut, $fin, $sauf = 0) {
	$where = array(
		'id_salle = ' . intval($id_salle),
		'statut = ' . sql_quote('demande'),
		'date_debut < ' . sql_quote($fin),
		'date_fin > ' . sql_quote($debut),
	);
	if ($sauf) {
		$where[] = 'id_reservation <> ' . intval($sauf);
	}

	return sql_allfetsel(
		'id_reservation, nom, organisme, date_debut, date_fin',
		'spip_reservations',
		$where,
		'', 'date_debut'
	);
}

/**
 * Vérifie qu'une date respecte les délais de la salle.
 * Rend une chaîne d'erreur, ou une chaîne vide si tout va bien.
 */
function marly_verifier_delais($salle, $debut) {
	$jours = floor((strtotime($debut) - time()) / 86400);

	if ($jours < 0) {
		return _T('marly:erreur_date_passee');
	}
	if (!empty($salle['delai_min']) && $jours < intval($salle['delai_min'])) {
		return _T('marly:erreur_delai_min', array('n' => intval($salle['delai_min'])));
	}
	if (!empty($salle['delai_max']) && $jours > intval($salle['delai_max'])) {
		return _T('marly:erreur_delai_max', array('n' => intval($salle['delai_max'])));
	}
	return '';
}

/**
 * Accepte une réservation, en refusant si le créneau a été pris entre-temps.
 *
 * C'est LE point où le verrouillage se joue. Deux agents peuvent accepter
 * deux demandes concurrentes à quelques secondes d'intervalle : la
 * transaction et la re-vérification à l'intérieur garantissent qu'une seule
 * passe. Sans elles, la salle serait louée deux fois le même samedi, et
 * personne ne s'en apercevrait avant le jour dit.
 *
 * @return string  '' si accepté, sinon le message expliquant le refus
 */
function marly_accepter($id_reservation, $reponse = '') {
	include_spip('base/abstract_sql');

	$reservation = sql_fetsel('*', 'spip_reservations', 'id_reservation = ' . intval($id_reservation));
	if (!$reservation) {
		return _T('marly:erreur_introuvable');
	}
	if ($reservation['statut'] === 'acceptee') {
		return '';
	}

	sql_demarrer_transaction();

	$conflits = marly_conflits(
		$reservation['id_salle'],
		$reservation['date_debut'],
		$reservation['date_fin'],
		$id_reservation
	);

	if ($conflits) {
		sql_terminer_transaction();
		return _T('marly:erreur_creneau_pris', array(
			'nom' => $conflits[0]['organisme'] ?: $conflits[0]['nom'],
		));
	}

	sql_updateq('spip_reservations', array(
		'statut'          => 'acceptee',
		'reponse'         => $reponse,
		'id_auteur'       => intval($GLOBALS['visiteur_session']['id_auteur'] ?? 0),
		'date_traitement' => date('Y-m-d H:i:s'),
	), 'id_reservation = ' . intval($id_reservation));

	sql_terminer_transaction();

	marly_notifier($id_reservation, 'acceptee');
	return '';
}

/** Refuse ou annule. Aucun verrou nécessaire : on libère, on ne prend pas. */
function marly_changer_statut($id_reservation, $statut, $reponse = '') {
	if (!in_array($statut, array('refusee', 'annulee', 'demande'), true)) {
		return _T('marly:erreur_statut');
	}

	sql_updateq('spip_reservations', array(
		'statut'          => $statut,
		'reponse'         => $reponse,
		'id_auteur'       => intval($GLOBALS['visiteur_session']['id_auteur'] ?? 0),
		'date_traitement' => date('Y-m-d H:i:s'),
	), 'id_reservation = ' . intval($id_reservation));

	marly_notifier($id_reservation, $statut);
	return '';
}

/**
 * Prévient par courriel le demandeur, et la mairie à la création.
 *
 * Une réservation qui change de statut sans que personne ne soit prévenu ne
 * sert à rien : le demandeur rappellerait la mairie pour savoir où en est sa
 * demande, ce que le formulaire était censé éviter.
 */
function marly_notifier($id_reservation, $evenement) {
	include_spip('inc/config');

	$reservation = sql_fetsel('*', 'spip_reservations', 'id_reservation = ' . intval($id_reservation));
	if (!$reservation) {
		return;
	}
	/* Le courriel doit nommer ce qui est réservé, salle ou manifestation. */
	if (intval($reservation['id_manifestation'])) {
		$objet = sql_fetsel('titre', 'spip_manifestations',
			'id_manifestation = ' . intval($reservation['id_manifestation']));
	} else {
		$objet = sql_fetsel('titre', 'spip_salles',
			'id_salle = ' . intval($reservation['id_salle']));
	}

	$contexte = array(
		'salle'      => $objet['titre'] ?? '',
		'places'     => intval($reservation['places']),
		'date_debut' => $reservation['date_debut'],
		'date_fin'   => $reservation['date_fin'],
		'nom'        => $reservation['nom'],
		'reponse'    => $reservation['reponse'],
		'site'       => $GLOBALS['meta']['nom_site'] ?? '',
	);

	$sujet = _T('marly:courriel_sujet_' . $evenement, $contexte);
	$corps = _T('marly:courriel_corps_' . $evenement, $contexte);

	$envoyer = charger_fonction('envoyer_mail', 'inc', true);
	if (!$envoyer) {
		spip_log("marly : envoyer_mail indisponible, notification $evenement perdue pour $id_reservation", 'marly' . _LOG_ERREUR);
		return;
	}

	if ($reservation['courriel']) {
		$envoyer($reservation['courriel'], $sujet, $corps);
	}

	/* À la création, c'est la mairie qu'il faut alerter : une demande qui
	   dort dans une base que personne ne consulte n'est pas une demande. */
	if ($evenement === 'demande') {
		$mairie = lire_config('marly/courriel', '') ?: ($GLOBALS['meta']['email_webmaster'] ?? '');
		if ($mairie) {
			$envoyer($mairie, _T('marly:courriel_sujet_mairie', $contexte), $corps);
		}
	}
}

/* ===========================================================================
   MANIFESTATIONS — la mécanique du stock
   ---------------------------------------------------------------------------
   Rien à voir avec les salles. Une salle se verrouille, une manifestation se
   décompte. Vingt personnes peuvent s'inscrire au même repas ; la question
   n'est jamais « est-ce libre ? » mais « en reste-t-il assez ? ».
   =========================================================================== */

/**
 * Places restantes. Rend PHP_INT_MAX si la manifestation n'a pas de jauge —
 * une kermesse en plein air n'en a pas, et lui en inventer une reviendrait à
 * refuser des gens sans raison.
 */
function marly_places_restantes($id_manifestation) {
	$manif = sql_fetsel('places', 'spip_manifestations',
		'id_manifestation = ' . intval($id_manifestation));
	if (!$manif) {
		return 0;
	}
	if (!intval($manif['places'])) {
		return PHP_INT_MAX;
	}

	$prises = sql_getfetsel('SUM(places)', 'spip_reservations', array(
		'id_manifestation = ' . intval($id_manifestation),
		"statut IN ('demande','acceptee')",
	));

	return max(0, intval($manif['places']) - intval($prises));
}

/** Les inscriptions sont-elles ouvertes en ce moment ? */
function marly_inscriptions_ouvertes($manif) {
	if ($manif['statut'] !== 'publie') {
		return 'ferme';
	}
	$maintenant = date('Y-m-d H:i:s');
	if ($manif['ouverture'] > '0000-00-00 00:00:00' && $maintenant < $manif['ouverture']) {
		return 'pas_encore';
	}
	if ($manif['cloture'] > '0000-00-00 00:00:00' && $maintenant > $manif['cloture']) {
		return 'clos';
	}
	if ($manif['date_debut'] > '0000-00-00 00:00:00' && $maintenant > $manif['date_debut']) {
		return 'passe';
	}
	return 'ouvert';
}

/**
 * Inscrit quelqu'un à une manifestation.
 *
 * LE POINT DÉLICAT. Deux personnes qui prennent les deux dernières places à
 * la même seconde doivent être départagées : sans transaction, les deux
 * lisent « 2 restantes » et les deux s'inscrivent, et la mairie découvre 62
 * inscrits pour 60 couverts le jour du repas.
 *
 * On recompte donc À L'INTÉRIEUR de la transaction, juste avant d'écrire.
 *
 * @return array  array('erreur' => '...') ou array('id_reservation' => n)
 */
function marly_inscrire($id_manifestation, $places, $donnees) {
	$places = max(1, intval($places));

	$manif = sql_fetsel('*', 'spip_manifestations',
		'id_manifestation = ' . intval($id_manifestation));
	if (!$manif) {
		return array('erreur' => _T('marly:erreur_introuvable'));
	}

	if (($etat = marly_inscriptions_ouvertes($manif)) !== 'ouvert') {
		return array('erreur' => _T('marly:erreur_inscriptions_' . $etat));
	}

	$max = max(1, intval($manif['places_par_personne']));
	if ($places > $max) {
		return array('erreur' => _T('marly:erreur_trop_de_places', array('n' => $max)));
	}

	sql_demarrer_transaction();

	$restantes = marly_places_restantes($id_manifestation);
	if ($restantes < $places) {
		sql_terminer_transaction();
		return array('erreur' => $restantes
			? _T('marly:erreur_reste_seulement', array('n' => $restantes))
			: _T('marly:erreur_complet'));
	}

	/* Une manifestation en validation automatique confirme tout de suite :
	   c'est ce qu'attend quelqu'un qui s'inscrit au repas des aînés. Une
	   manifestation à arbitrage laisse la mairie décider, comme une salle. */
	$statut = ($manif['validation'] === 'auto') ? 'acceptee' : 'demande';

	$id = sql_insertq('spip_reservations', array_merge($donnees, array(
		'id_manifestation' => intval($id_manifestation),
		'id_salle'         => 0,
		'places'           => $places,
		'statut'           => $statut,
		'date_debut'       => $manif['date_debut'],
		'date_fin'         => $manif['date_fin'],
		'jeton'            => md5(uniqid((string) mt_rand(), true)),
		'date'             => date('Y-m-d H:i:s'),
	)));

	sql_terminer_transaction();

	if (!$id) {
		return array('erreur' => _T('marly:erreur_enregistrement'));
	}

	marly_notifier($id, $statut === 'acceptee' ? 'inscrit' : 'demande');
	return array('id_reservation' => $id);
}
