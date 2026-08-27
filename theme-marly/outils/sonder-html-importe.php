<?php
/**
 * Ce que l'ancien site a laissé dans le HTML des articles.
 * ---------------------------------------------------------------------------
 *   php theme-marly/outils/sonder-html-importe.php /chemin/racine-web
 *
 * L'import a recopié le texte des articles tel qu'il était écrit sur free.fr.
 * Ce HTML porte les habitudes de l'ancien thème : des classes qui n'existent
 * plus ici, des balises mal emboîtées, des mises en forme écrites à la main.
 *
 * ON NE SAIT PAS ENCORE SI ÇA SE VOIT, et c'est toute la question. Un
 * navigateur rattrape beaucoup de choses en silence. Si rien ne choque à
 * l'écran, il n'y a rien à réparer et ce serait du travail pour rien.
 *
 * La sonde compte, montre le texte RÉEL sans l'interpréter, et donne l'adresse
 * des articles concernés pour aller regarder. Elle ne répare rien.
 *
 * Chaque motif est nommé par ce qu'il RISQUE de produire à l'écran, et non par
 * sa forme : « un paragraphe entier en gras » se juge, « un p dans un b » ne
 * se juge pas.
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

$controles = array(

	array('nom'   => 'Paragraphe ouvert a l interieur d un gras ou d un italique',
	      'quoi'  => 'le gras deborde sur tout le paragraphe au lieu de trois mots',
	      'motif' => '~<(b|strong|i|em|u)\b[^>]*>(?:(?!</\1>).)*?<p\b~is'),

	array('nom'   => 'Classe de l ancien theme (element_sous, spip, etc.)',
	      'quoi'  => 'aucune regle ne la stylise ici : la mise en forme voulue est perdue',
	      'motif' => '~class\s*=\s*["\'][^"\']*\b(element_sous|element|spip|texte_principal|cadre[a-z_]*)\b~i'),

	array('nom'   => 'Mise en forme ecrite a la main dans un attribut style',
	      'quoi'  => 'couleurs et tailles figees qui ignorent la charte du site',
	      'motif' => '~\sstyle\s*=\s*["\'][^"\']+["\']~i'),

	array('nom'   => 'Balise abandonnee par le HTML (font, center, big, tt)',
	      'quoi'  => 'plus aucune garantie de rendu, et illisible pour un lecteur d ecran',
	      'motif' => '~<(font|center|big|tt|strike)\b~i'),

	array('nom'   => 'Paragraphe vide',
	      'quoi'  => 'un trou dans le texte, souvent la ou une image a disparu',
	      'motif' => '~<p[^>]*>(?:\s|&nbsp;|<br\s*/?>)*</p>~i'),

	array('nom'   => 'Suite d espaces insecables',
	      'quoi'  => 'des blancs qui ne se replient pas : la ligne deborde sur mobile',
	      'motif' => '~(&nbsp;\s*){3,}~i'),

	array('nom'   => 'Tableau de mise en page',
	      'quoi'  => 'illisible pour un lecteur d ecran, et casse la colonne sur mobile',
	      'motif' => '~<table\b~i'),
);

$articles = sql_allfetsel('id_article, titre, statut, chapo, texte, descriptif', 'spip_articles');
printf("%d article(s) en base.\n", count($articles));

$total = 0;
foreach ($controles as $c) {
	$touches = array();
	$exemples = array();
	foreach ($articles as $a) {
		$champ = $a['chapo'] . "\n" . $a['texte'] . "\n" . $a['descriptif'];
		if (!preg_match_all($c['motif'], $champ, $m, PREG_OFFSET_CAPTURE)) { continue; }
		$touches[$a['id_article']] = $a['titre'];
		if (count($exemples) < 3) {
			$pos = max(0, $m[0][0][1] - 60);
			$exemples[] = sprintf("        [%d] ...%s...", $a['id_article'],
				preg_replace('~\s+~', ' ', substr($champ, $pos, 200)));
		}
	}
	$n = count($touches);
	$total += $n;
	printf("\n%s\n  %s\n  %d article(s)%s\n", strtoupper($c['nom']), $c['quoi'], $n,
		$n ? ' : ' . implode(', ', array_slice(array_keys($touches), 0, 20))
		     . (count($touches) > 20 ? '...' : '') : '');
	foreach ($exemples as $e) { echo $e, "\n"; }
}

echo "\n", str_repeat('-', 70), "\n";
if (!$total) {
	echo "Rien a signaler : le HTML importe est propre.\n";
} else {
	echo "AVANT DE REPARER QUOI QUE CE SOIT, ALLER REGARDER.\n";
	echo "Un navigateur rattrape beaucoup de choses en silence. Ouvrir deux ou\n";
	echo "trois des articles cites ci-dessus et juger a l'ecran : si rien ne\n";
	echo "choque, il n'y a rien a faire, et le HTML douteux peut rester tel quel.\n";
	echo "\nL'adresse d'un article se construit ainsi :\n";
	echo "  https://marlygomont.pixfeed.net/spip.php?article<numero>\n";
}
