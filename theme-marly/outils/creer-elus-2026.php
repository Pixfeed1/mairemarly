<?php
/**
 * Crée les onze fiches du conseil municipal élu le 15 mars 2026, EN BROUILLON.
 * ---------------------------------------------------------------------------
 *   php theme-marly/outils/creer-elus-2026.php /chemin/racine-web
 *
 * LA SOURCE. Le Répertoire national des élus, tenu par les préfectures et le
 * ministère de l'Intérieur, relevé le 25 août 2026 par outils/elus-officiels.sh.
 * Ni un annuaire, ni un résumé : le fichier lui-même. La liste qui circulait
 * en ligne mêlait deux mandatures — trois élus de 2020 qui ne siègent plus,
 * deux élues de 2026 absentes, et un conseiller sans nom de famille.
 *
 * LES FONCTIONS SONT ACCORDÉES. Le répertoire écrit tout au masculin :
 * Nathalie Van Hyfte y est « 2ème adjoint au Maire ». Sur le site d'une
 * commune on écrit « 2e adjointe au maire ». L'accord suit la colonne du sexe
 * déclaré à la préfecture, jamais le prénom : un prénom ne dit pas le genre
 * d'une personne, et se tromper sur la fiche nominative d'une élue est
 * exactement ce qu'une mairie voit en premier.
 *
 * LA DÉLÉGATION RESTE VIDE, et c'est le point important. Le répertoire donne
 * le rang des adjoints, jamais ce dont ils ont la charge : cela ne figure que
 * dans l'arrêté signé par le maire après l'installation du conseil. Or c'est
 * la seule information qui serve vraiment à un habitant. Écrire « voirie » au
 * jugé enverrait les gens frapper à la mauvaise porte, et personne ne saurait
 * d'où sort le mot.
 *
 * EN BROUILLON. Les fiches n'apparaissent pas sur le site tant que la mairie
 * ne les a pas relues. Elle seule peut confirmer une orthographe, ajouter une
 * délégation, une permanence, une photographie.
 *
 * Il est REJOUABLE : un élu dont le nom et le prénom existent déjà est sauté.
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
include_spip('inc/marly_outils');

/* En ligne de commande, personne n'est connecté : SPIP refuse alors en
   silence l'écriture. On se présente en webmestre avant d'écrire, comme dans
   les autres scripts. */
$GLOBALS['visiteur_session'] = array(
	'id_auteur' => 1,
	'statut'    => '0minirezo',
	'webmestre' => 'oui',
	'nom'       => 'reprise CLI',
);

/* Le conseil au 15 mars 2026. « sexe » n'est pas enregistré : il ne sert
   qu'ici, à accorder la fonction, et le site n'a aucune raison de le
   conserver. */
$conseil = array(
	array('nom' => 'Delache',   'prenom' => 'Maryse',      'sexe' => 'F', 'fonction' => 'Maire'),

	array('nom' => 'Hersoy',    'prenom' => 'Gontran',     'sexe' => 'M', 'fonction' => '1er adjoint au maire'),
	array('nom' => 'Van Hyfte', 'prenom' => 'Nathalie',    'sexe' => 'F', 'fonction' => '2e adjointe au maire'),
	array('nom' => 'Delaunay',  'prenom' => 'Franck',      'sexe' => 'M', 'fonction' => '3e adjoint au maire'),

	array('nom' => 'Charlot',   'prenom' => 'Patrick',     'sexe' => 'M'),
	array('nom' => 'Flamant',   'prenom' => 'Isabelle',    'sexe' => 'F'),
	array('nom' => 'Gaspard',   'prenom' => 'Nicolas',     'sexe' => 'M'),
	array('nom' => 'Grobarek',  'prenom' => 'Jean-Pierre', 'sexe' => 'M'),
	array('nom' => 'Langlet',   'prenom' => 'Florence',    'sexe' => 'F'),
	array('nom' => 'Midelet',   'prenom' => 'Annick',      'sexe' => 'F'),
	array('nom' => 'Robert',    'prenom' => 'Marianne',    'sexe' => 'F'),
);

$crees = 0;
foreach ($conseil as $elu) {
	/* Sans fonction déclarée, la personne est conseillère ou conseiller
	   municipal : c'est ce que vaut une fonction vide au répertoire. */
	if (empty($elu['fonction'])) {
		$elu['fonction'] = ($elu['sexe'] === 'F') ? 'Conseillère municipale' : 'Conseiller municipal';
	}
	unset($elu['sexe']);

	/* Nom ET prénom comparés à la casse près : MySQL compare sans distinguer
	   les majuscules, et deux fiches proches passeraient l'une pour l'autre.
	   Le prénom compte : deux Midelet dans un village de 483 habitants n'ont
	   rien d'improbable. */
	$deja = sql_countsel('spip_elus',
		'nom = BINARY ' . sql_quote($elu['nom'])
		. ' AND prenom = BINARY ' . sql_quote($elu['prenom']));
	if ($deja) {
		echo 'DEJA LA, saute : ' . $elu['prenom'] . ' ' . $elu['nom'] . "\n";
		continue;
	}

	$elu += array('delegation' => '', 'telephone' => '', 'courriel' => '',
	              'permanence' => '', 'biographie' => '', 'rang' => 100,
	              'statut' => 'prepa');

	$id = sql_insertq('spip_elus', $elu);
	if (!$id) {
		fwrite(STDERR, 'ECHEC insertion : ' . $elu['prenom'] . ' ' . $elu['nom'] . "\n");
		continue;
	}
	echo 'creee (brouillon) : ' . str_pad($elu['fonction'], 24) . $elu['prenom'] . ' ' . $elu['nom'] . "\n";
	$crees++;
}

marly_invalider_cache();
echo "\n$crees fiche(s) creee(s), toutes EN BROUILLON.\n";
echo "Espace prive > Edition > Elus : relire, puis publier.\n";
echo "\nLa delegation de chaque adjoint reste a saisir : elle ne figure que\n";
echo "dans l'arrete signe par le maire, et c'est elle que les habitants lisent\n";
echo "pour savoir a qui s'adresser.\n";
