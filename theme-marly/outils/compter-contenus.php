<?php
/**
 * Ce que le site contient vraiment.
 * ---------------------------------------------------------------------------
 *   php theme-marly/outils/compter-contenus.php /chemin/racine-web
 *
 * Avant de dessiner un bloc pour la page d'accueil, savoir ce qu'il aurait à
 * montrer. Un bloc « Actualités » qui afficherait deux articles de 2019 est
 * pire que pas de bloc du tout : il dit au visiteur que la commune ne s'en
 * occupe plus.
 *
 * Le script ne LIT que. Il ne touche à rien.
 *
 * L'amorçage est celui de majbase.php, aux mêmes conditions : SPIP démarre par
 * Composer, et le kernel réclame des variables de serveur qui n'existent pas
 * en ligne de commande. Un require de inc_version.php tout seul ne suffit pas,
 * la couche SQL n'est pas chargée — c'est l'erreur qu'a rendue ma première
 * tentative en une ligne.
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

/* table, date a regarder, libelle */
$quoi = array(
	array('spip_articles',        'date',       'Articles'),
	array('spip_manifestations',  'date_debut', 'Manifestations'),
	array('spip_demarches',       '',           'Demarches'),
	array('spip_associations',    '',           'Associations'),
	array('spip_commerces',       '',           'Commerces'),
	array('spip_elus',            '',           'Elus'),
	array('spip_salles',          '',           'Salles'),
	array('spip_lieux',           '',           'Lieux'),
	array('spip_lettres',         '',           'Lettres'),
	array('spip_rubriques',       '',           'Rubriques'),
);

printf("%-18s %8s %8s   %s\n", '', 'publies', 'total', 'plus recent');
printf("%s\n", str_repeat('-', 62));
foreach ($quoi as $q) {
	list($table, $champ, $nom) = $q;
	if (!sql_showtable($table, true)) {
		printf("%-18s %8s\n", $nom, '(table absente)');
		continue;
	}
	$total   = sql_countsel($table);
	$publies = sql_countsel($table, "statut='publie'");
	$recent  = '';
	if ($champ) {
		$recent = (string) sql_getfetsel("MAX($champ)", $table, "statut='publie'");
		if ($recent) {
			$jours = floor((time() - strtotime($recent)) / 86400);
			$recent .= sprintf('  (il y a %d jour%s)', $jours, $jours > 1 ? 's' : '');
		}
	}
	printf("%-18s %8d %8d   %s\n", $nom, $publies, $total, $recent ?: '');
}

/* ---------------------------------------------------------------------------
 * LES IMAGES. La question qui decide de la mise en page de l'accueil : un bloc
 * d'actualites a la maniere des grandes communes suppose des photographies. Si
 * les articles n'en ont pas, aucune mise en page ne les fera apparaitre, et
 * reserver la place d'une image absente donne un trou.
 *
 * On ne suppose pas le nom du role : on demande a la base ceux qu'elle porte.
 * ------------------------------------------------------------------------ */
echo "\nIMAGES ATTACHEES AUX ARTICLES\n";
if (sql_showtable('spip_documents_liens', true)) {
	$roles = sql_allfetsel(
		"role, COUNT(DISTINCT id_objet) AS objets, COUNT(*) AS documents",
		'spip_documents_liens', "objet='article'", 'role', 'objets DESC');
	if (!$roles) {
		echo "  aucun document attache a aucun article.\n";
	}
	foreach ($roles as $r) {
		printf("  role %-14s %4d article(s), %4d document(s)\n",
			$r['role'], $r['objets'], $r['documents']);
	}
	$avec = sql_countsel('spip_articles AS a', array(
		"a.statut='publie'",
		"EXISTS (SELECT 1 FROM spip_documents_liens AS l WHERE l.objet='article'"
		. " AND l.id_objet = a.id_article)"));
	$total = sql_countsel('spip_articles', "statut='publie'");
	printf("  %d article(s) publie(s) sur %d ont au moins un document.\n", $avec, $total);
}

/* Les brouillons comptent : ce sont eux qui attendent une relecture. */
echo "\n";
$prepa = sql_countsel('spip_elus', "statut!='publie'");
if ($prepa) {
	echo "Rappel : $prepa fiche(s) d'elu ne sont pas publiees.\n";
}
