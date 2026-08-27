<?php
/**
 * Ce que les articles importes montrent vraiment comme images.
 * ---------------------------------------------------------------------------
 *   php theme-marly/outils/sonder-images.php /chemin/racine-web
 *
 * L'import de l'ancien site etait cense rapatrier les images dans
 * IMG/ancien-site/, les attacher comme vrais documents SPIP et reecrire les
 * liens dans les textes. Le constat en ligne dit le contraire. Avant d'ecrire
 * la moindre reparation, on mesure OU ca a lache, parmi quatre possibilites
 * qui appellent quatre corrections differentes :
 *
 *   1. le texte ne contient aucune image : rien n'a jamais ete importe ;
 *   2. il en contient, qui pointent encore vers free.fr : le telechargement
 *      n'a pas eu lieu, les liens n'ont pas ete reecrits ;
 *   3. il en contient qui pointent vers IMG/ancien-site/ mais le fichier
 *      n'est pas sur le disque : le telechargement a echoue en silence ;
 *   4. le fichier est la, mais aucun document n'est attache a l'article :
 *      c'est l'attachement qui a lache, pas le rapatriement.
 *
 * L'import ne reconnaissait que les adresses contenant << IMG/ >>. Un SPIP 3
 * ecrit aussi ses vignettes sous local/cache-vignettes/ : celles-la lui
 * passaient sous le nez. La sonde compte les deux formes separement, c'est
 * elle qui dira si c'est la cause.
 *
 * LE SCRIPT NE LIT QUE. Il ne telecharge rien, n'ecrit rien, ne repare rien.
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

$dossier = $racine . '/IMG/ancien-site/';

$articles = sql_allfetsel('id_article, titre, statut, chapo, texte, descriptif', 'spip_articles');
printf("%d article(s) en base.\n", count($articles));
printf("Dossier %s : %s\n\n", $dossier,
	is_dir($dossier) ? count(glob($dossier . '*')) . ' fichier(s)' : 'ABSENT');

$compte = array('sans' => 0, 'free' => 0, 'ancien_ok' => 0, 'ancien_manquant' => 0,
                'vignette' => 0, 'autre' => 0);
$hotes = array();
$manquants = array();
$sans_doc = array();

foreach ($articles as $a) {
	$champ = $a['chapo'] . "\n" . $a['texte'] . "\n" . $a['descriptif'];

	/* Les deux ecritures d'une image dans un texte SPIP : la balise HTML
	   telle quelle, et le raccourci <docNN|...> qui designe un document
	   deja attache. Les compter ensemble donnerait un total juste et une
	   conclusion fausse : le raccourci prouve un attachement, la balise non. */
	preg_match_all(',<img[^>]+src="([^"]+)",i', $champ, $m);
	$srcs = $m[1];
	$raccourcis = preg_match_all(',<(?:doc|img|emb)[0-9]+[|>],i', $champ);

	if (!$srcs and !$raccourcis) { $compte['sans']++; continue; }

	foreach ($srcs as $u) {
		if (preg_match(',^https?://([^/]+),i', $u, $h)) {
			$hotes[strtolower($h[1])] = ($hotes[strtolower($h[1])] ?? 0) + 1;
		}
		if (stripos($u, 'free.fr') !== false)          { $compte['free']++; }
		elseif (stripos($u, 'cache-vignettes') !== false) { $compte['vignette']++; }
		elseif (stripos($u, 'IMG/ancien-site/') !== false) {
			$f = $racine . '/' . ltrim(preg_replace(',^https?://[^/]+/,i', '', $u), '/');
			if (is_file($f)) { $compte['ancien_ok']++; }
			else { $compte['ancien_manquant']++; $manquants[] = $a['id_article'] . ' : ' . $u; }
		}
		else { $compte['autre']++; }
	}

	/* Un document attache, c'est ce qui fait qu'une image survit a un
	   deplacement du site. Un <img src> code en dur, non. */
	$n = sql_countsel('spip_documents_liens',
		"objet='article' AND id_objet=" . intval($a['id_article']));
	if ($srcs and !$n) { $sans_doc[] = $a['id_article'] . ' : ' . $a['titre']; }
}

echo "IMAGES CITEES DANS LES TEXTES\n";
printf("  articles sans aucune image            : %d\n", $compte['sans']);
printf("  liens restes sur free.fr              : %d\n", $compte['free']);
printf("  liens vers des vignettes SPIP 3       : %d\n", $compte['vignette']);
printf("  liens IMG/ancien-site, fichier present: %d\n", $compte['ancien_ok']);
printf("  liens IMG/ancien-site, fichier ABSENT : %d\n", $compte['ancien_manquant']);
printf("  autres liens                          : %d\n", $compte['autre']);

if ($hotes) {
	echo "\nHOTES CITES\n";
	arsort($hotes);
	foreach ($hotes as $h => $n) { printf("  %-40s %d\n", $h, $n); }
}

if ($manquants) {
	echo "\nFICHIERS ANNONCES MAIS ABSENTS DU DISQUE\n";
	foreach (array_slice($manquants, 0, 25) as $l) { echo '  ' . $l . "\n"; }
	if (count($manquants) > 25) { printf("  ... et %d autres\n", count($manquants) - 25); }
}

if ($sans_doc) {
	echo "\nARTICLES QUI MONTRENT UNE IMAGE SANS DOCUMENT ATTACHE\n";
	foreach (array_slice($sans_doc, 0, 25) as $l) { echo '  ' . $l . "\n"; }
	if (count($sans_doc) > 25) { printf("  ... et %d autres\n", count($sans_doc) - 25); }
}

$docs = sql_countsel('spip_documents');
$lies = sql_countsel('spip_documents_liens', "objet='article'");
printf("\nDOCUMENTS EN BASE : %d, dont %d relies a un article.\n", $docs, $lies);

/* Une image ecrite en dur dans le texte n'apparait pas dans les documents :
   c'est elle qu'on perdra au prochain deplacement du site. */
echo "\nSi la ligne << liens restes sur free.fr >> n'est pas a zero, l'ancien\n";
echo "site est encore la source des images affichees, et le jour ou il ferme\n";
echo "elles disparaissent toutes.\n";
