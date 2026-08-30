<?php
/**
 * Ce que SPIP fabrique vraiment quand on insere une photo dans un texte.
 * ---------------------------------------------------------------------------
 *   php theme-marly/outils/sonder-raccourci-image.php /chemin/racine-web
 *
 * Le theme habille les photos posees dans le corps d'un article avec les
 * classes spip_documents_gauche et spip_documents_droite. Or le bouton
 * d'insertion de l'espace prive ecrit <img12|left>, en anglais, et le modele
 * image.html construit ses classes dans une fonction PHP qu'aucun grep de
 * l'installation ne retrouve. Autrement dit : personne ne sait ce qui sort.
 *
 * Plutot que de deviner, on demande le calcul a SPIP. La sonde passe quatre
 * raccourcis a propre(), la fonction qui transforme le texte d'un article en
 * HTML, et imprime le resultat tel quel. Elle passe ensuite deux raccourcis
 * colles l'un a l'autre, parce que la mise en rangee de deux ou trois photos
 * depend d'une chose et d'une seule : est-ce que les blocs sortent voisins
 * dans le HTML, ou chacun emballe separement.
 *
 * LE SCRIPT NE LIT QUE. Il n'ecrit rien en base, ne touche a aucun fichier.
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
define('_ESPACE_PRIVE', false);
include $ecrire . '/inc_version.php';
if (!function_exists('include_spip')) {
	fwrite(STDERR, "SPIP n'a pas demarre.\n");
	exit(1);
}
include_spip('base/abstract_sql');
include_spip('inc/texte');

if (!function_exists('propre')) {
	fwrite(STDERR, "propre() indisponible : SPIP n'a pas charge inc/texte.\n");
	exit(1);
}

// Deux images reelles, n'importe lesquelles : la sonde ne les modifie pas.
$images = sql_allfetsel('id_document, fichier, largeur, hauteur', 'spip_documents',
	"mode = 'image' AND extension IN ('jpg','jpeg','png','webp')", '', 'id_document', '0,2');

if (count($images) < 2) {
	fwrite(STDERR, "Il faut au moins deux documents image en base ; trouve : "
		. count($images) . "\n");
	exit(1);
}

$a = $images[0];
$b = $images[1];

printf("Images temoins : %d (%s, %sx%s) et %d (%s, %sx%s)\n\n",
	$a['id_document'], basename($a['fichier']), $a['largeur'], $a['hauteur'],
	$b['id_document'], basename($b['fichier']), $b['largeur'], $b['hauteur']);

$cas = array(
	'sans alignement'      => "<img{$a['id_document']}>",
	'aligne a gauche'      => "<img{$a['id_document']}|left>",
	'centre'               => "<img{$a['id_document']}|center>",
	'aligne a droite'      => "<img{$a['id_document']}|right>",
	'ecrit en francais'    => "<img{$a['id_document']}|gauche>",
	'deux a la suite'      => "<img{$a['id_document']}|left>\n<img{$b['id_document']}|left>",
	'deux dans un para'    => "Du texte. <img{$a['id_document']}|left><img{$b['id_document']}|left> Encore du texte.",
);

foreach ($cas as $nom => $raccourci) {
	echo str_repeat('=', 72) . "\n";
	echo "CAS : $nom\n";
	echo "SAISI : " . str_replace("\n", ' [retour ligne] ', $raccourci) . "\n";
	echo str_repeat('-', 72) . "\n";
	$html = propre($raccourci);
	// Une balise par ligne : c'est l'imbrication qu'on vient lire.
	$html = preg_replace('/></', ">\n<", $html);
	echo trim($html) . "\n\n";
}

echo str_repeat('=', 72) . "\n";
echo "Classes vues, toutes formes confondues :\n";
$tout = '';
foreach ($cas as $raccourci) {
	$tout .= propre($raccourci);
}
// SPIP ecrit ses attributs en guillemets simples sur le conteneur et en
// doubles sur la figure interne : chercher les deux, sinon le releve sort
// vide alors que le HTML brut, lui, montre bien les classes.
if (preg_match_all('/class=(?:"([^"]*)"|\'([^\']*)\')/', $tout, $m)) {
	$m[1] = array_map(function ($a, $b) { return $a !== '' ? $a : $b; },
		$m[1], $m[2]);
	$vues = array();
	foreach ($m[1] as $liste) {
		foreach (preg_split('/\s+/', trim($liste)) as $classe) {
			if ($classe !== '') {
				$vues[$classe] = isset($vues[$classe]) ? $vues[$classe] + 1 : 1;
			}
		}
	}
	ksort($vues);
	foreach ($vues as $classe => $n) {
		printf("  %-40s %d fois\n", $classe, $n);
	}
} else {
	echo "  aucune.\n";
}
