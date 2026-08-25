<?php
/**
 * Renseigne les deux lignes d'urbanisme propres à la commune.
 * ---------------------------------------------------------------------------
 *   php theme-marly/outils/regler-urbanisme.php /chemin/racine-web
 *
 * La page Urbanisme du site explique les seuils, les délais et l'affichage :
 * c'est du droit national, écrit dans le squelette, rien à saisir. Trois
 * lignes seulement varient d'une commune à l'autre, et le bloc qui les porte
 * reste invisible tant qu'elles sont vides — mieux vaut se taire que de dire
 * le contraire de la loi applicable ici.
 *
 * DEUX SONT DÉSORMAIS ÉTABLIES. Le Géoportail de l'urbanisme, interrogé le
 * 25 août 2026 par outils/urbanisme-officiel.sh, répond pour l'INSEE 02469 :
 *
 *     "insee":"02469","name":"MARLY-GOMONT","is_rnu":true
 *
 * Aucun document local. Il s'ensuit deux choses, et une seule est évidente :
 *
 *   - le seuil de la déclaration préalable reste à 20 m². Le relèvement à
 *     40 m² suppose une zone urbaine, qui suppose un PLU ;
 *   - le maire délivre quand même les autorisations, mais AU NOM DE L'ÉTAT
 *     et non au nom de la commune, après avis conforme du préfet (L422-1 et
 *     L422-5 du code de l'urbanisme). Le guichet reste la mairie : c'est
 *     précisément ce qu'un habitant a besoin de savoir, et c'est ce que les
 *     résumés en ligne rendent de travers en écrivant que « l'État décide ».
 *
 * LA TROISIÈME N'EST PAS ÉCRITE. Les clôtures ne se déclarent que si le
 * conseil municipal l'a voté. Aucun fichier national ne le dit ; seule la
 * mairie le sait. Le champ reste vide, et sa ligne n'apparaît pas.
 *
 * Il est REJOUABLE et NE REMPLACE RIEN : un champ déjà rempli est laissé tel
 * quel. Si la mairie a réécrit le texte à sa main, c'est le sien qui reste.
 * À lancer sous l'utilisateur du site, comme majbase.php.
 */

error_reporting(E_ALL);
ini_set('display_errors', 'stderr');

if (PHP_SAPI !== 'cli') {
	die("Ce script ne s'utilise qu'en ligne de commande.\n");
}

$racine = isset($argv[1]) ? rtrim($argv[1], '/') : getcwd();
$ecrire = $racine . '/ecrire';
if (!is_file($ecrire . '/inc_version.php') or !is_file($racine . '/vendor/autoload.php')) {
	fwrite(STDERR, "Racine SPIP introuvable : $racine\n");
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
include_spip('inc/config');
include_spip('inc/marly_outils');

$GLOBALS['visiteur_session'] = array(
	'id_auteur' => 1,
	'statut'    => '0minirezo',
	'webmestre' => 'oui',
	'nom'       => 'reprise CLI',
);

$lignes = array(
	'urbanisme_document' =>
		'La commune n’a ni plan local d’urbanisme ni carte communale : c’est le '
		. 'règlement national d’urbanisme qui s’applique. Le seuil de la '
		. 'déclaration préalable reste donc fixé à 20 m².',

	'urbanisme_decide' =>
		'La maire délivre les autorisations au nom de l’État, après avis conforme '
		. 'du préfet. Le dossier se dépose en mairie comme partout ailleurs, et '
		. 'l’instruction est assurée par les services de l’État.',
);

$ecrits = 0;
foreach ($lignes as $cle => $texte) {
	$actuel = trim((string) lire_config('marly/' . $cle, ''));
	if ($actuel !== '') {
		echo "DEJA RENSEIGNE, laisse tel quel : $cle\n";
		continue;
	}
	ecrire_config('marly/' . $cle, $texte);
	echo "ecrit : $cle\n";
	$ecrits++;
}

marly_invalider_cache();
echo "\n$ecrits ligne(s) ecrite(s).\n";
echo "Le bloc << Ce qui vaut a Marly-Gomont >> apparait maintenant sur\n";
echo "?page=urbanisme, avec ces deux lignes.\n";
echo "\nReste la troisieme, que seule la mairie connait : le conseil a-t-il\n";
echo "delibere pour rendre la declaration prealable obligatoire sur les\n";
echo "clotures ? Configuration > Reglages de la commune > Urbanisme.\n";
