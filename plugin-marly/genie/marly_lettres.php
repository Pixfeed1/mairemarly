<?php
/**
 * Tâche périodique : poursuit l'envoi des lettres en cours.
 * ---------------------------------------------------------------------------
 * SPIP l'appelle au fil des visites. L'envoi avance donc tout seul, lot par
 * lot, sans que personne ait à laisser une page ouverte.
 *
 * Sur un site peu visité, l'envoi peut traîner : c'est un compromis assumé.
 * L'alternative — tout envoyer d'un coup — casse au-delà de quelques dizaines
 * d'abonnés, et personne ne sait alors qui a reçu quoi.
 */

if (!defined('_ECRIRE_INC_VERSION')) {
	return;
}

function genie_marly_lettres_dist($t) {
	include_spip('inc/marly_lettres');

	$en_cours = sql_allfetsel('id_lettre', 'spip_lettres', "statut = 'envoi'", '', 'id_lettre');
	foreach ($en_cours as $lettre) {
		marly_envoyer_lot($lettre['id_lettre']);
	}

	return 1;
}
