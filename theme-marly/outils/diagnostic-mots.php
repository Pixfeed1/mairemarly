<?php
/**
 * Qui porte quel mot-clé, et dans quel groupe.
 * ---------------------------------------------------------------------------
 *   php theme-marly/outils/diagnostic-mots.php /chemin/racine-web
 *
 * MESURE DU 26 AOÛT 2026 : la bande d'actualités de l'accueil devait écarter
 * les quatre pages légales par le critère {type_mot!=Emplacements}. Elle les a
 * affichées quand même, en tête. Deux causes possibles, et une seule est la
 * bonne :
 *
 *   a) le groupe ne s'appelle pas « Emplacements » — les mots-clés existaient
 *      peut-être déjà, rangés ailleurs ;
 *   b) le critère type_mot n'accepte pas la négation comme titre_mot le fait.
 *
 * Ce script tranche : il montre les groupes, les mots, leur rattachement, et
 * les articles les plus récents avec les mots qu'ils portent. Dans les deux
 * cas la correction passera par {titre_mot!=…}, le seul mécanisme dont ce
 * site a déjà la preuve qu'il fonctionne — encore faut-il connaître les
 * intitulés exacts des mots-clés à écarter. C'est ce que ce script donne.
 *
 * Le script ne LIT que. Il ne touche à rien.
 */
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

function titre($t) { echo "\n=== $t ===\n"; }

titre('LES GROUPES DE MOTS-CLES');
$res = sql_select('id_groupe, titre', 'spip_groupes_mots', '', '', 'titre');
$groupes = array();
while ($g = sql_fetch($res)) {
	$groupes[$g['id_groupe']] = $g['titre'];
	printf("  %3d  %s\n", $g['id_groupe'], $g['titre']);
}
if (!$groupes) {
	echo "  aucun groupe.\n";
}

titre('LES MOTS-CLES, ET LE GROUPE OU ILS SONT RANGES');
$mots = array();
$res = sql_select('id_mot, titre, id_groupe', 'spip_mots', '', '', 'titre');
while ($m = sql_fetch($res)) {
	$mots[$m['id_mot']] = $m;
	$g = isset($groupes[$m['id_groupe']]) ? $groupes[$m['id_groupe']] : '(groupe ' . $m['id_groupe'] . ' introuvable)';
	$n = sql_countsel('spip_mots_liens',
		'id_mot = ' . intval($m['id_mot']) . ' AND objet = ' . sql_quote('article'));
	printf("  %3d  %-28s groupe : %-20s  %d article(s)\n", $m['id_mot'], $m['titre'], $g, $n);
}

titre('LES 10 ARTICLES PUBLIES LES PLUS RECENTS, ET LEURS MOTS-CLES');
$res = sql_select('id_article, titre, date', 'spip_articles',
	'statut = ' . sql_quote('publie'), '', 'date DESC', '0,10');
while ($a = sql_fetch($res)) {
	/* Pas de jointure ecrite a la main : une requete simple, et les
	   intitules repris de la table deja lue plus haut. Une jointure qui
	   echoue rend un resultat vide sans rien dire, et le diagnostic
	   mentirait — c'est deja arrive avec le recensement. */
	$portes = array();
	$r2 = sql_select('id_mot', 'spip_mots_liens',
		'objet = ' . sql_quote('article') . ' AND id_objet = ' . intval($a['id_article']));
	while ($x = sql_fetch($r2)) {
		$m = isset($mots[$x['id_mot']]) ? $mots[$x['id_mot']] : null;
		$portes[] = $m
			? $m['titre'] . ' [' . (isset($groupes[$m['id_groupe']]) ? $groupes[$m['id_groupe']] : '?') . ']'
			: '(mot ' . $x['id_mot'] . ' disparu)';
	}
	printf("  %s  %-52s %s\n", substr($a['date'], 0, 10),
		mb_substr($a['titre'], 0, 52), $portes ? implode(', ', $portes) : '(aucun mot-cle)');
}

echo "\n";
