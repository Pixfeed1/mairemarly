<?php
/**
 * Créer et modifier une manifestation, depuis l'espace privé.
 */

if (!defined('_ECRIRE_INC_VERSION')) {
	return;
}

function marly_champs_manifestation() {
	return array(
		'titre', 'descriptif', 'lieu',
		'date_debut', 'date_fin',
		'places', 'places_par_personne', 'tarif', 'video',
		'validation', 'ouverture', 'cloture', 'statut',
	);
}

function formulaires_editer_manifestation_charger_dist($id_manifestation = 'new') {
	if (!autoriser('modifier', 'salle')) {
		return false;
	}

	$valeurs = array(
		'id_manifestation'    => $id_manifestation,
		'titre'               => '',
		'descriptif'          => '',
		'lieu'                => '',
		'date_debut'          => '',
		'date_fin'            => '',
		'places'              => 0,
		'places_par_personne' => 4,
		'tarif'               => '',
		'validation'          => 'auto',
		'ouverture'           => '',
		'cloture'             => '',
		'statut'              => 'prepa',
	);

	if ($id_manifestation !== 'new' and intval($id_manifestation)) {
		$manif = sql_fetsel('*', 'spip_manifestations',
			'id_manifestation = ' . intval($id_manifestation));
		if ($manif) {
			foreach (marly_champs_manifestation() as $champ) {
				$valeur = $manif[$champ];
				/* Les champs date HTML veulent AAAA-MM-JJTHH:MM, la base
				   stocke AAAA-MM-JJ HH:MM:SS. On traduit ici plutôt que dans
				   le gabarit : une conversion par aller-retour, pas deux. */
				if (in_array($champ, array('date_debut', 'date_fin', 'ouverture', 'cloture'), true)) {
					$valeur = ($valeur > '0000-00-00 00:00:00')
						? str_replace(' ', 'T', substr($valeur, 0, 16))
						: '';
				}
				$valeurs[$champ] = $valeur;
			}
		}
	}

	return $valeurs;
}

function formulaires_editer_manifestation_verifier_dist($id_manifestation = 'new') {
	$erreurs = array();

	if (!trim((string) _request('titre'))) {
		$erreurs['titre'] = _T('marly:erreur_obligatoire');
	}
	if (!trim((string) _request('date_debut'))) {
		$erreurs['date_debut'] = _T('marly:erreur_obligatoire');
	}

	foreach (array('places', 'places_par_personne') as $champ) {
		$v = _request($champ);
		if ($v !== '' && !ctype_digit((string) $v)) {
			$erreurs[$champ] = _T('marly:erreur_nombre');
		}
	}

	/* Une adresse video qu'on ne sait pas traduire donnerait un cadre vide
	   sur le site. On refuse tout de suite, en nommant les plateformes
	   reconnues plutot qu'en disant << adresse invalide >>. */
	$video = trim((string) _request('video'));
	if ($video) {
		include_spip('marly_fonctions');
		if (!filtre_marly_video_embed_dist($video)) {
			$erreurs['video'] = _T('marly:erreur_video');
		}
	}

	$ouverture = trim((string) _request('ouverture'));
	$cloture   = trim((string) _request('cloture'));
	if ($ouverture && $cloture && $cloture < $ouverture) {
		$erreurs['cloture'] = _T('marly:erreur_cloture_avant');
	}

	$debut = trim((string) _request('date_debut'));
	if ($debut && $cloture && $cloture > $debut) {
		$erreurs['cloture'] = _T('marly:erreur_cloture_apres_evenement');
	}

	return $erreurs;
}

function formulaires_editer_manifestation_traiter_dist($id_manifestation = 'new') {
	$champs = array();
	foreach (marly_champs_manifestation() as $champ) {
		$champs[$champ] = trim((string) _request($champ));
	}

	foreach (array('date_debut', 'date_fin', 'ouverture', 'cloture') as $champ) {
		$champs[$champ] = $champs[$champ]
			? str_replace('T', ' ', $champs[$champ]) . ':00'
			: '0000-00-00 00:00:00';
	}
	/* Sans heure de fin, on prend le début : une manifestation ponctuelle
	   n'a pas à en inventer une. */
	if ($champs['date_fin'] === '0000-00-00 00:00:00') {
		$champs['date_fin'] = $champs['date_debut'];
	}

	$champs['places']              = intval($champs['places']);
	$champs['places_par_personne'] = max(1, intval($champs['places_par_personne']));
	if (!in_array($champs['validation'], array('auto', 'mairie'), true)) {
		$champs['validation'] = 'auto';
	}
	if (!in_array($champs['statut'], array('prepa', 'publie'), true)) {
		$champs['statut'] = 'prepa';
	}

	if ($id_manifestation === 'new' or !intval($id_manifestation)) {
		$id_manifestation = sql_insertq('spip_manifestations', $champs);
		if (!$id_manifestation) {
			return array('message_erreur' => _T('marly:erreur_enregistrement'));
		}
	} else {
		sql_updateq('spip_manifestations', $champs,
			'id_manifestation = ' . intval($id_manifestation));
	}

	include_spip('inc/marly_outils');
	marly_invalider_cache();

	return array(
		'message_ok' => _T('marly:manifestation_enregistree'),
		'redirect'   => generer_url_ecrire('manifestations'),
	);
}
