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
 * versions d'affilee.
 *
 * Cette fonction est prevue pour la ligne de commande — elle contient un
 * branchement _IS_CLI. On l'appelle donc directement, et le deploiement
 * devient complet a lui seul.
 *
 * A LANCER SOUS L'UTILISATEUR DU SITE, jamais en root : SPIP reecrit ses
 * caches au passage, et un cache appartenant a root est un site casse.
 * deployer.sh s'en charge.
 */

if (PHP_SAPI !== 'cli') {
	die("Ce script ne s'utilise qu'en ligne de commande.\n");
}

$racine = isset($argv[1]) ? rtrim($argv[1], '/') : getcwd();
if (!is_file($racine . '/ecrire/inc_version.php')) {
	fwrite(STDERR, "Racine SPIP introuvable : $racine\n");
	exit(1);
}

/* SPIP lit ces variables pour se situer. En ligne de commande elles
   n'existent pas : on les pose telles qu'un appel local les aurait. */
chdir($racine);
$_SERVER['DOCUMENT_ROOT']   = $racine;
$_SERVER['SCRIPT_FILENAME'] = $racine . '/index.php';
$_SERVER['SCRIPT_NAME']     = '/index.php';
$_SERVER['PHP_SELF']        = '/index.php';
$_SERVER['REQUEST_URI']     = '/';
$_SERVER['REQUEST_METHOD']  = 'GET';
$_SERVER['REMOTE_ADDR']     = '127.0.0.1';
$_SERVER['HTTP_HOST']       = basename($racine);

require_once $racine . '/ecrire/inc_version.php';

if (!function_exists('include_spip')) {
	fwrite(STDERR, "SPIP n'a pas demarre.\n");
	exit(1);
}

include_spip('inc/plugin');
if (!function_exists('plugin_installes_meta')) {
	fwrite(STDERR, "plugin_installes_meta() introuvable : version de SPIP inattendue.\n");
	exit(1);
}

$avant = isset($GLOBALS['meta']['marly_base_version']) ? $GLOBALS['meta']['marly_base_version'] : 'aucune';
plugin_installes_meta();

/* On relit les metas depuis la base : la globale date d'avant la mise a
   jour, et se fier a elle donnerait un compte rendu faux. */
lire_metas();
$apres = isset($GLOBALS['meta']['marly_base_version']) ? $GLOBALS['meta']['marly_base_version'] : 'aucune';

echo "\nmarly_base_version : $avant -> $apres\n";
exit(0);
