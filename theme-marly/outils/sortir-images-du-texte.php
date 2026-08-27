<?php
/**
 * Sort du texte les raccourcis d'images, qui y font triple emploi.
 * ---------------------------------------------------------------------------
 *   php theme-marly/outils/sortir-images-du-texte.php /chemin/racine-web
 *   php theme-marly/outils/sortir-images-du-texte.php /chemin/racine-web --ecrire
 *
 * MESURE DU 27 AOUT 2026. Sur l'article 60, le nom du fichier apparaissait
 * CINQ fois dans la page servie. Le reparateur d'images avait attache les
 * photographies comme documents — c'etait juste — et insere en plus un
 * raccourci <imgNN|center> a l'endroit de l'ancienne balise. Or le theme
 * montre deja ces documents a deux endroits :
 *
 *   - en tete d'article, la premiere image jointe sert d'illustration ;
 *   - en bas, le portfolio montre les images jointes.
 *
 * Le raccourci faisait donc un troisieme affichage de la meme photographie.
 *
 * ON CORRIGE PAR LES DONNEES, PAS PAR LE GABARIT. Rendre le gabarit malin —
 * ne plus se rabattre quand le texte porte deja une image, exclure du
 * portfolio celles qui y sont — demanderait trois conditions SPIP imbriquees,
 * et il n'y a pas de SPIP sur la machine ou ce theme s'ecrit : elles ne
 * seraient testees qu'en production. Retirer le raccourci ne perd rien : le
 * document reste attache, sa legende reste son titre, l'image reste affichee
 * deux fois moins.
 *
 * Ce qui est perdu, et il faut le dire : la POSITION de l'image dans le texte.
 * Elle etait de toute facon fausse depuis l'import, puisque la balise ne
 * s'affichait pas.
 *
 * On ne touche QUE les raccourcis d'image. <docNN> designe une piece a
 * telecharger, un PDF de compte rendu : celui-la a sa place dans le texte.
 */
error_reporting(E_ALL);
ini_set('display_errors', 'stderr');

if (PHP_SAPI !== 'cli') {
	die("Ce script ne s'utilise qu'en ligne de commande.\n");
}

$racine = isset($argv[1]) ? rtrim($argv[1], '/') : getcwd();
$ecrire = $racine . '/ecrire';
if (!is_file($ecrire . '/inc_version.php') || !is_file($racine . '/vendor/autoload.php')) {
	fwrite(STDERR, "Racine SPIP introuvable ou sans vendor/autoload.php : $racine\n");
	exit(1);
}

chdir($ecrire);
$_SERVER['DOCUMENT_ROOT']   = $racine;
$_SERVER['SCRIPT_FILENAME'] = $ecrire . '/index.php';
$_SERVER['SCRIPT_NAME']     = '/ecrire/index.php';
$_SERVER['PHP_SELF']        = '/ecrire/index.php';
$_SERVER['REQUEST_URI']     = '/ecrire/';
$_SERVER['REQUEST_METHOD']  = 'GET';
$_SERVER['REMOTE_ADDR']     = '127.0.0.1';
$_SERVER['HTTP_HOST']       = basename($racine);

require_once $racine . '/vendor/autoload.php';
define('_ESPACE_PRIVE', true);
include $ecrire . '/inc_version.php';
if (!function_exists('include_spip')) {
	fwrite(STDERR, "SPIP n'a pas demarre.\n");
	exit(1);
}
include_spip('base/abstract_sql');
include_spip('action/editer_objet');

$ecrire = in_array('--ecrire', $argv, true);

/* SANS CETTE SESSION, objet_modifier() est refuse EN SILENCE. Regle 66. */
$GLOBALS['visiteur_session'] = array(
	'id_auteur' => 1,
	'statut'    => '0minirezo',
	'webmestre' => 'oui',
	'nom'       => 'nettoyage CLI',
);

$articles = sql_allfetsel('id_article, titre, chapo, texte, descriptif', 'spip_articles');
$champs_scrutes = array('chapo', 'texte', 'descriptif');
$n_articles = $n_raccourcis = $n_ecrits = 0;

foreach ($articles as $a) {
	$id = intval($a['id_article']);
	$modifs = array();
	$lignes = array();

	foreach ($champs_scrutes as $champ) {
		$avant = (string) $a[$champ];
		/* <img12>, <img12|center>, <img12|left|Legende> : le raccourci
		   d'IMAGE seulement. <doc12> reste, c'est une piece a telecharger. */
		if (!preg_match_all('~<img(\d+)(\|[^>]*)?>~i', $avant, $m, PREG_SET_ORDER)) { continue; }

		$apres = $avant;
		foreach ($m as $trouve) {
			/* On ne retire que si le document est BIEN attache a cet article :
			   sinon on effacerait la seule trace d'une image. */
			$lie = sql_countsel('spip_documents_liens',
				"objet='article' AND id_objet=" . $id . " AND id_document=" . intval($trouve[1]));
			if (!$lie) {
				$lignes[] = '    GARDE : ' . $trouve[0] . ' — document non attache a cet article';
				continue;
			}
			$apres = str_replace($trouve[0], '', $apres);
			$lignes[] = '    retire : ' . $trouve[0];
			$n_raccourcis++;
		}

		/* Le raccourci occupait souvent un paragraphe a lui seul. */
		$net = preg_replace('~<p[^>]*>\s*</p>~i', '', $apres);
		if ($net !== null) { $apres = $net; }
		$net = preg_replace('~\n{3,}~', "\n\n", $apres);
		if ($net !== null) { $apres = $net; }

		/* Une reecriture qui vide un champ non vide est refusee. */
		if (!is_string($apres) or ($apres === '' and $avant !== '')) {
			fwrite(STDERR, "  [$id] $champ : reecriture abandonnee, resultat vide.\n");
			continue;
		}
		if ($apres !== $avant) { $modifs[$champ] = $apres; }
	}

	if (!$lignes) { continue; }
	$n_articles++;
	printf("\n[%d] %s\n%s\n", $id, $a['titre'], implode("\n", $lignes));

	if ($modifs and $ecrire) {
		objet_modifier('article', $id, $modifs);
		/* On relit : une ecriture dont personne ne verifie le resultat ment
		   tot ou tard. Regle 68 du verificateur. */
		$relu = sql_getfetsel('texte', 'spip_articles', 'id_article = ' . $id);
		if (isset($modifs['texte']) and trim($relu) !== trim($modifs['texte'])) {
			echo "    ECHEC : le texte n'a pas ete enregistre.\n";
		} else {
			$n_ecrits++;
		}
	}
}

printf("\n%s\n", str_repeat('-', 66));
printf("%d article(s), %d raccourci(s) d'image.\n", $n_articles, $n_raccourcis);
if ($ecrire) {
	printf("%d article(s) reecrit(s) en base.\n", $n_ecrits);
} else {
	echo "\nESSAI A BLANC : rien n'a ete ecrit.\n";
	echo "Relancer avec --ecrire pour appliquer.\n";
}
