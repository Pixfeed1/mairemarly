<?php
/**
 * Confirmer un abonnement, ou s'en désinscrire, depuis un lien reçu.
 * ---------------------------------------------------------------------------
 * Ce fichier existe parce qu'une page publique ne doit pas écrire en base.
 * Un lien cliqué dans un courriel arrive en GET : s'il pointait sur un
 * squelette qui met à jour la table, n'importe quel aspirateur de liens
 * désinscrirait des gens en passant.
 *
 * Ici, le jeton EST l'autorisation. Il fait trente-deux caractères tirés au
 * hasard et n'est connu que de la personne qui a reçu le message : détenir
 * le lien, c'est détenir la boîte aux lettres.
 *
 * L'action répond aussi en POST, sans rien d'autre : c'est ce que font Gmail
 * et Yahoo quand le lecteur clique leur propre bouton « se désabonner »
 * (List-Unsubscribe-Post). Une page de confirmation à ce moment-là ferait
 * échouer la désinscription.
 */

if (!defined('_ECRIRE_INC_VERSION')) {
	return;
}

function action_marly_abonne_dist() {
	include_spip('inc/headers');

	$jeton = preg_replace('/[^a-f0-9]/i', '', (string) _request('jeton'));
	$faire = (string) _request('faire');
	$fait  = 'inconnu';

	if (strlen($jeton) >= 16 and in_array($faire, array('confirmer', 'desinscrire'), true)) {
		$abonne = sql_fetsel('id_abonne, statut', 'spip_abonnes', 'jeton = ' . sql_quote($jeton));

		if ($abonne) {
			$ou = 'id_abonne = ' . intval($abonne['id_abonne']);

			if ($faire === 'desinscrire') {
				sql_updateq('spip_abonnes', array('statut' => 'desinscrit'), $ou);
				$fait = 'desinscrit';
			} elseif ($abonne['statut'] === 'desinscrit') {
				/* Le même jeton sert à confirmer ET à se désinscrire. Sans ce
				   refus, un vieux courriel de confirmation retrouvé dans une
				   boîte réabonnerait quelqu'un qui s'était désinscrit. */
				$fait = 'inconnu';
			} else {
				sql_updateq('spip_abonnes', array(
					'statut'            => 'confirme',
					'date_confirmation' => date('Y-m-d H:i:s'),
				), $ou);
				$fait = 'confirme';
			}
		}
		spip_log("marly : $faire jeton " . substr($jeton, 0, 6) . '... -> ' . $fait, 'marly.' . _LOG_INFO_IMPORTANTE);
	}

	if (isset($_SERVER['REQUEST_METHOD']) and $_SERVER['REQUEST_METHOD'] === 'POST') {
		http_response_code(200);
		header('Content-Type: text/plain; charset=utf-8');
		echo "OK\n";
		exit;
	}

	redirige_par_entete(generer_url_public('newsletter', 'fait=' . $fait));
}
