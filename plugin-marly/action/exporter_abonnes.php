<?php
/**
 * Export CSV des abonnés confirmés.
 * ---------------------------------------------------------------------------
 * Utile pour deux choses : une sauvegarde hors du site, et le jour où la
 * mairie changerait d'outil. Un fichier d'abonnés qu'on ne peut pas sortir
 * est une donnée prise en otage.
 *
 * Point-virgule et BOM UTF-8 : c'est ce qu'attend Excel en configuration
 * française. Avec une virgule, il met toute la ligne dans une seule cellule ;
 * sans BOM, il abîme les accents. Ce n'est pas élégant, c'est ce qui marche
 * sur le poste où le fichier sera ouvert.
 */

if (!defined('_ECRIRE_INC_VERSION')) {
	return;
}

function action_exporter_abonnes_dist($arg = null) {

	if (is_null($arg)) {
		$securiser = charger_fonction('securiser_action', 'inc');
		$securiser();
	}

	if (!autoriser('modifier', 'salle')) {
		return;
	}

	$abonnes = sql_allfetsel(
		'courriel, prenom, nom, code_postal, ville, date_confirmation',
		'spip_abonnes',
		"statut = 'confirme'",
		'', 'courriel'
	);

	$nom_fichier = 'abonnes-' . date('Y-m-d') . '.csv';

	header('Content-Type: text/csv; charset=UTF-8');
	header('Content-Disposition: attachment; filename="' . $nom_fichier . '"');
	header('Cache-Control: no-store');

	$sortie = fopen('php://output', 'w');
	fwrite($sortie, "\xEF\xBB\xBF");   /* le BOM, pour Excel */

	fputcsv($sortie, array('Adresse', 'Prénom', 'Nom', 'Code postal', 'Commune', 'Confirmé le'), ';');
	foreach ($abonnes as $a) {
		fputcsv($sortie, array(
			$a['courriel'], $a['prenom'], $a['nom'],
			$a['code_postal'], $a['ville'],
			($a['date_confirmation'] > '0000-00-00 00:00:00') ? $a['date_confirmation'] : '',
		), ';');
	}
	fclose($sortie);

	exit;
}
