<?php
/**
 * Met la base a jour sans navigateur.
 * ---------------------------------------------------------------------------
 *   php theme-marly/outils/majbase.php /chemin/vers/racine-web
 *
 * SPIP n'appelle plugin_installes_meta() qu'a deux endroits : l'installation
 * initiale du site, et ecrire/exec/admin_plugin.php. Autrement dit, la mise a
 * jour de la base d'un plugin attend qu'un humain charge une page precise.
 * Le jour ou il l'oublie, les fichiers avancent et la base reste en arriere,
 * sans que rien ne le dise : les squelettes cassent, les formulaires perdent
 * des colonnes, et on cherche la panne ailleurs. C'est arrive ici sur six
 * versions d'affilee, de 3.14.0 a 3.21.0.
 *
 * La fonction est prevue pour la ligne de commande, elle contient un
 * branchement _IS_CLI. Restait a la joindre.
 *
 * L'AMORCAGE EST COPIE DE ecrire/index.php, pas invente. SPIP 4.4 demarre par
 * un kernel Composer : inc_version.php ressort a sa ligne 24 si la fonction
 * SpipLeague\Component\Kernel\app n'existe pas encore. Et le kernel retient
 * getcwd() au moment ou on le touche : _ROOT_RESTREINT en decoule. Il faut
 * donc etre DANS ecrire/, comme l'espace prive quand Apache l'execute. Se
 * placer a la racine du site fait chercher les fichiers un cran trop haut.
 *
 * A LANCER SOUS L'UTILISATEUR DU SITE, jamais en root : SPIP reecrit ses
 * caches au passage, et un cache appartenant a root est un site casse.
 * deployer.sh s'en charge.
 */

/* Sans ca, un fichier qui sort en cours de route ne dit rien et on croit a
   un demarrage silencieux. La premiere version de ce script a coute un
   aller-retour pour cette seule raison. */
error_reporting(E_ALL);
ini_set('display_errors', 'stderr');

if (PHP_SAPI !== 'cli') {
	die("Ce script ne s'utilise qu'en ligne de commande.\n");
}

$racine = isset($argv[1]) ? rtrim($argv[1], '/') : getcwd();
$ecrire = $racine . '/ecrire';

if (!is_file($ecrire . '/inc_version.php')) {
	fwrite(STDERR, "Racine SPIP introuvable : $racine\n");
	exit(1);
}
if (!is_file($racine . '/vendor/autoload.php')) {
	fwrite(STDERR, "vendor/autoload.php absent : ce SPIP ne demarre pas par Composer,\n"
		. "l'amorcage ci-dessous ne lui convient pas.\n");
	exit(1);
}

/* Les trois gestes de ecrire/index.php, dans son ordre et depuis son
   dossier. Les variables de serveur n'existent pas en ligne de commande :
   le kernel lit REQUEST_URI et SCRIPT_FILENAME, on les pose telles qu'un
   appel a l'espace prive les aurait laissees. */
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
	fwrite(STDERR, "SPIP n'a pas demarre. Ce qu'il a eu le temps de poser :\n");
	foreach (array('_ECRIRE_INC_VERSION', '_DIR_RESTREINT', '_ROOT_RESTREINT',
	               '_DIR_RACINE', '_ROOT_RACINE', '_FILE_CONNECT') as $c) {
		fwrite(STDERR, sprintf("  %-20s %s\n", $c,
			defined($c) ? (constant($c) === '' ? '(chaine vide)' : constant($c)) : 'NON DEFINI'));
	}
	fwrite(STDERR, '  kernel Composer charge : '
		. (function_exists('SpipLeague\Component\Kernel\app') ? 'oui' : 'NON') . "\n");
	fwrite(STDERR, '  version de PHP : ' . PHP_VERSION . "\n");
	exit(1);
}

include_spip('inc/plugin');
if (!function_exists('plugin_installes_meta')) {
	fwrite(STDERR, "plugin_installes_meta() introuvable : version de SPIP inattendue.\n");
	exit(1);
}

$avant = isset($GLOBALS['meta']['marly_base_version']) ? $GLOBALS['meta']['marly_base_version'] : 'aucune';

/* SPIP ecrit son compte rendu en HTML : son branchement _IS_CLI ne se
   declenche pas ici, parce qu'on lui a pose un REQUEST_URI pour satisfaire
   le kernel. Plutot que de deviner sa condition exacte et de dependre
   d'elle, on ramene la trace au texte nous-memes. */
ob_start();
plugin_installes_meta();
$trace = ob_get_clean();
include_spip('inc/filtres');
if (function_exists('textebrut')) {
	$trace = textebrut($trace);
}
$trace = trim(preg_replace("/\n{3,}/", "\n\n", $trace));
echo $trace === '' ? "Aucun plugin n'avait de retard.\n" : $trace . "\n";

/* On relit les metas depuis la base : la globale date d'avant la mise a
   jour, et se fier a elle donnerait un compte rendu faux. */
lire_metas();
$apres = isset($GLOBALS['meta']['marly_base_version']) ? $GLOBALS['meta']['marly_base_version'] : 'aucune';

echo "\nmarly_base_version : $avant -> $apres\n";
exit(0);
