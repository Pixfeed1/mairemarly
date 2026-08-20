<?php
/**
 * Crée les quatre associations reprises de l'ancien site, sans navigateur.
 * ---------------------------------------------------------------------------
 *   php theme-marly/outils/creer-associations-initiales.php /chemin/racine-web
 *
 * Pourquoi ce script : les INSERT mysql directs cassent les accents (le
 * client et SPIP ne lisent pas la base avec le même réglage), et les
 * enregistrements par le navigateur butaient sur des pages blanches sans
 * la moindre erreur PHP. Ici on passe par SPIP LUI-MÊME, amorcé comme dans
 * outils/majbase.php : même connexion, même charset, mêmes fonctions que
 * le formulaire — rubrique créée, adresse localisée, cache prévenu.
 *
 * Il est REJOUABLE : une association dont le nom existe déjà est sautée.
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
include_spip('inc/marly_associations');
include_spip('inc/marly_geocodage');
include_spip('inc/marly_outils');

$fiches = array(
	array(
		'nom'       => 'AS Marly-Gomont',
		'theme'     => 'sport',
		'activite'  => "Le club de football du village, affilié à la Fédération française. Une équipe masculine en championnat de district de l'Aisne, une équipe féminine, et le traditionnel Challenge de la Municipalité chaque été au stade.",
		'president' => 'Alain Braghiéri',
		'courriel'  => 'alain.braghieri@orange.fr',
		'site'      => 'https://asmg.footeo.com/',
		'lieu'      => 'stade, Marly-Gomont',
	),
	array(
		'nom'       => 'TTMG, tennis de table de Marly-Gomont',
		'theme'     => 'sport',
		'activite'  => 'Le club de tennis de table du village, né en 2016. Entraînements ouverts aux débutants comme aux joueurs confirmés.',
		'president' => 'M. Mercadier',
	),
	array(
		'nom'       => 'Harmonie Municipale de Marly-Gomont',
		'theme'     => 'culture',
		'activite'  => "L'harmonie du village depuis 1880. Elle accompagne les cérémonies, donne son concert annuel, et propose des cours de musique et de chant pour tous les niveaux.",
		'president' => 'Victor Mulet',
		'horaires'  => 'Répétitions le samedi de 17 h à 19 h',
		'site'      => 'https://visiteur.harmonie-marly-gomont.com/',
	),
	array(
		'nom'       => "Comité d'animation de Marly-Gomont",
		'theme'     => 'culture',
		'activite'  => "Le comité organise les temps forts du village : la fête communale au début du mois d'août, la brocante de printemps, et les repas et animations qui rythment l'année.",
		'president' => 'Franck Delannoy',
		'lieu'      => 'salle des fêtes, Marly-Gomont',
	),
);

$creees = 0;
foreach ($fiches as $fiche) {
	if (sql_countsel('spip_associations', 'nom = ' . sql_quote($fiche['nom']))) {
		echo 'existe deja : ' . $fiche['nom'] . "\n";
		continue;
	}

	$fiche += array('courriel' => '', 'site' => '', 'lieu' => '', 'horaires' => '',
	                'latitude' => '', 'longitude' => '', 'statut' => 'publie');

	if ($fiche['lieu'] !== '') {
		$point = marly_geocoder($fiche['lieu']);
		$fiche['latitude']  = $point['latitude'] ?? '';
		$fiche['longitude'] = $point['longitude'] ?? '';
		/* Nominatim demande une seconde entre deux requetes. */
		sleep(1);
	}

	$id = sql_insertq('spip_associations', $fiche);
	if (!$id) {
		fwrite(STDERR, 'ECHEC insertion : ' . $fiche['nom'] . "\n");
		continue;
	}

	$id_rubrique = marly_rubrique_association($fiche['nom']);
	if ($id_rubrique) {
		sql_updateq('spip_associations', array('id_rubrique' => $id_rubrique),
			'id_association = ' . intval($id));
	}

	echo 'creee : ' . $fiche['nom']
		. ($fiche['latitude'] !== '' ? ' (localisee)' : '')
		. ($id_rubrique ? " (rubrique $id_rubrique)" : ' (RUBRIQUE NON CREEE)')
		. "\n";
	$creees++;
}

marly_invalider_cache();
echo "\n$creees fiche(s) creee(s). Verifiez la page publique Associations.\n";
