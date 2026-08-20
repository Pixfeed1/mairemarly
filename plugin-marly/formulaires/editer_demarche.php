<?php
/**
 * Créer ou modifier une fiche démarche.
 *
 * Des champs séparés plutôt qu'un texte libre : c'est ce qui fait que toutes
 * les fiches se ressemblent, quel que soit qui les a saisies, et qu'aucune ne
 * part sans dire où s'adresser.
 */

if (!defined('_ECRIRE_INC_VERSION')) {
	return;
}

function marly_champs_demarche() {
	return array('titre', 'famille', 'icone', 'resume', 'qui', 'comment',
	             'pieces', 'cout', 'delai', 'ou', 'lien', 'lien_faire', 'rang', 'statut',
	             'id_elu', 'contact_tel', 'contact_courriel', 'a_savoir', 'alerte', 'alerte_fin');
}

function formulaires_editer_demarche_charger_dist($id_demarche = 'new') {
	if (!autoriser('modifier', 'salle')) {
		return false;
	}

	$valeurs = array(
		'id_demarche' => $id_demarche,
		'famille'     => 'mairie',
		'icone'       => 'ri-file-text-line',
		'cout'        => 'Gratuit',
		'rang'        => 100,
		'statut'      => 'publie',
		'socle'       => 0,
	);
	foreach (array('titre', 'resume', 'qui', 'comment', 'pieces', 'delai', 'ou', 'lien',
	               'lien_faire', 'contact_tel', 'contact_courriel', 'a_savoir', 'alerte') as $champ) {
		$valeurs[$champ] = '';
	}
	$valeurs['id_elu'] = 0;
	$valeurs['alerte_fin'] = '';

	/* Les deux listes fermées, passées au gabarit plutôt que recopiées dedans :
	   le PHP et le formulaire ne peuvent pas diverger. */
	include_spip('inc/marly_demarches');
	$valeurs['_icones'] = marly_icones_demarches();
	$valeurs['_familles'] = array();
	foreach (marly_familles_demarches() as $cle => $intitule) {
		$valeurs['_familles'][$cle] = _T($intitule);
	}

	/* Les elus, pour le menu deroulant. On les lit ici plutot que dans le
	   gabarit : une boucle sur une table vide y donnerait un select sans
	   option, sans qu'on sache si c'est normal. */
	$valeurs['_elus'] = array(0 => _T('marly:aucun_elu_choisi'));
	foreach (sql_allfetsel('id_elu, nom, prenom, fonction, delegation', 'spip_elus',
	                       "statut = 'publie'", '', 'rang, nom') as $elu) {
		$libelle = trim($elu['prenom'] . ' ' . $elu['nom']);
		if ($elu['delegation']) {
			$libelle .= ' — ' . $elu['delegation'];
		} elseif ($elu['fonction']) {
			$libelle .= ' — ' . $elu['fonction'];
		}
		$valeurs['_elus'][$elu['id_elu']] = $libelle;
	}

	if ($id_demarche !== 'new' and intval($id_demarche)) {
		$fiche = sql_fetsel('*', 'spip_demarches', 'id_demarche = ' . intval($id_demarche));
		if ($fiche) {
			foreach (marly_champs_demarche() as $champ) {
				$valeurs[$champ] = $fiche[$champ] ?? $valeurs[$champ] ?? '';
			}
			/* Une date vide vaut 0000-00-00 en base : l'afficher telle quelle
			   dans un champ date donnerait une date invalide, que le
			   navigateur refuse silencieusement. */
			if ($valeurs['alerte_fin'] === '0000-00-00') {
				$valeurs['alerte_fin'] = '';
			}
			$valeurs['socle'] = $fiche['socle'];
		}
	}

	return $valeurs;
}

function formulaires_editer_demarche_verifier_dist($id_demarche = 'new') {
	$erreurs = array();

	if (!trim((string) _request('titre'))) {
		$erreurs['titre'] = _T('marly:erreur_obligatoire');
	}
	if (!trim((string) _request('resume'))) {
		$erreurs['resume'] = _T('marly:erreur_obligatoire');
	}

	/* « Où s'adresser » est obligatoire, et c'est le seul champ de texte qui
	   l'est avec le résumé. Une fiche qui explique une démarche sans dire où
	   aller renvoie la personne au téléphone du secrétariat : elle fait
	   perdre du temps aux deux. */
	if (!trim((string) _request('ou'))) {
		$erreurs['ou'] = _T('marly:erreur_obligatoire');
	}

	include_spip('inc/marly_demarches');
	if (!array_key_exists(_request('icone'), marly_icones_demarches())) {
		$erreurs['icone'] = _T('marly:erreur_obligatoire');
	}
	if (!array_key_exists(_request('famille'), marly_familles_demarches())) {
		$erreurs['famille'] = _T('marly:erreur_obligatoire');
	}

	foreach (array('lien', 'lien_faire') as $champ) {
		$valeur = trim((string) _request($champ));
		if ($valeur !== '' and !preg_match(',^https?://.+,i', $valeur)) {
			$erreurs[$champ] = _T('marly:erreur_adresse');
		}
	}

	return $erreurs;
}

function formulaires_editer_demarche_traiter_dist($id_demarche = 'new') {
	if (!autoriser('modifier', 'salle')) {
		return array('message_erreur' => _T('avis_operation_impossible'));
	}

	$champs = array();
	foreach (marly_champs_demarche() as $champ) {
		$champs[$champ] = trim((string) _request($champ));
	}
	$champs['rang'] = intval($champs['rang']) ?: 100;
	$champs['id_elu'] = intval($champs['id_elu']);
	$champs['alerte_fin'] = $champs['alerte_fin'] ?: '0000-00-00';
	if (!in_array($champs['statut'], array('publie', 'prepa'), true)) {
		$champs['statut'] = 'publie';
	}

	if ($id_demarche === 'new' or !intval($id_demarche)) {
		$id_demarche = sql_insertq('spip_demarches', $champs);
		if (!$id_demarche) {
			return array('message_erreur' => _T('marly:erreur_enregistrement'));
		}
	} else {
		sql_updateq('spip_demarches', $champs, 'id_demarche = ' . intval($id_demarche));
	}

	return array(
		'message_ok' => _T('marly:demarche_enregistree'),
		'redirect'   => generer_url_ecrire('demarches'),
	);
}
