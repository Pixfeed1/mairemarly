<?php
/**
 * Complète les fiches de commerces avec les coordonnées trouvées en ligne.
 * ---------------------------------------------------------------------------
 *   php theme-marly/outils/completer-commerces.php /chemin/racine-web           # essai
 *   php theme-marly/outils/completer-commerces.php /chemin/racine-web ecrire    # pour de vrai
 *
 * L'ancienne page « Commerces et Services » ne donnait que des noms. Ces
 * coordonnées viennent d'annuaires publics en ligne, pas de la mairie : elles
 * sont donc PROBABLES, pas certaines. C'est la raison pour laquelle les fiches
 * restent en brouillon — la secrétaire décroche son téléphone, vérifie, et
 * publie. Le travail passe de « tout saisir » à « confirmer », ce qui n'est
 * pas le même travail.
 *
 * DEUX GARDE-FOUS.
 *
 * Il n'écrase JAMAIS un champ déjà rempli. Ce que la mairie a saisi fait
 * autorité sur ce qu'un annuaire raconte, toujours, sans exception. Un script
 * qui repasse derrière une correction humaine est un script qui la détruit.
 *
 * Il ne touche pas aux fiches périmées : deux commerces de la liste ont fermé
 * en 2018 et le script se contente de les nommer. Supprimer une fiche est une
 * décision de mairie, pas de programme.
 *
 * Il est REJOUABLE : un champ déjà rempli est sauté, donc une deuxième
 * exécution ne fait rien.
 */

error_reporting(E_ALL);
ini_set('display_errors', 'stderr');

if (PHP_SAPI !== 'cli') {
	die("Ce script ne s'utilise qu'en ligne de commande.\n");
}

$racine = isset($argv[1]) ? rtrim($argv[1], '/') : getcwd();
$ecrire = $racine . '/ecrire';
$ecrire_pour_de_vrai = in_array('ecrire', $argv, true);

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
include_spip('inc/marly_commerces');
include_spip('inc/marly_geocodage');
include_spip('inc/marly_outils');

/* En ligne de commande personne n'est connecte : SPIP refuse alors en silence
   l'ecriture sur les objets. On se presente en webmestre, comme les autres
   scripts de ce dossier. */
$GLOBALS['visiteur_session'] = array(
	'id_auteur' => 1,
	'statut'    => '0minirezo',
	'webmestre' => 'oui',
	'nom'       => 'completion CLI',
);

/* Ce que les annuaires publics donnent, releve le 24 aout 2026. Les fiches
   sans numero trouve n'ont que leur adresse : c'est deja de quoi publier, et
   la mairie ajoutera le numero apres son coup de fil. */
$trouve = array(
	'Boucherie-charcuterie Proxi Desse' => array(
		'telephone' => '03 23 60 21 06',
		'lieu'      => '30 rue de la Poterie, 02120 Marly-Gomont'),
	'Boulangerie « La mie des Marlysiens »' => array(
		'telephone' => '03 23 60 53 46',
		'lieu'      => '1 rue de la Poterie, 02120 Marly-Gomont'),
	'Coiffure et esthétique Maryse' => array(
		'telephone' => '03 23 60 22 58',
		'site'      => 'https://salonmaryse.fr/',
		'lieu'      => '6 rue de la Poterie, 02120 Marly-Gomont'),

	'Cabinet médical' => array(
		'telephone' => '03 23 05 75 80',
		'lieu'      => '20 rue de la Poterie, 02120 Marly-Gomont'),
	'Cabinet de kinésithérapie' => array(
		'telephone' => '03 23 60 66 38',
		'lieu'      => '16 rue de la Poterie, 02120 Marly-Gomont'),
	'Pharmacie Horiot' => array(
		'telephone' => '03 23 60 21 84',
		'lieu'      => '7 rue de la Poterie, 02120 Marly-Gomont'),
	'Cabinet infirmier — Florence Alizard' => array(
		'lieu'      => '20 rue de la Poterie, 02120 Marly-Gomont'),
	'Cabinet infirmier — Odile Pecriaud' => array(
		'lieu'      => '6 bis rue de la Poterie, 02120 Marly-Gomont'),

	'Garage Finet' => array(
		'telephone' => '06 03 32 31 30',
		'lieu'      => '21 rue d’Ermichamp, 02120 Marly-Gomont'),
	'Ets Ramolu' => array(
		'telephone' => '03 23 60 20 37',
		'lieu'      => '16 bis rue de Gomont, 02120 Marly-Gomont'),
	'Dumange Nicolas' => array(
		'telephone' => '06 25 12 80 52',
		'courriel'  => 'dumangett@hotmail.com',
		'lieu'      => '28 rue de Gomont, 02120 Marly-Gomont'),
	'Vallerand Romain' => array(
		'lieu'      => '17 rue de Gomont, 02120 Marly-Gomont'),
	'Dequesne François' => array(
		'lieu'      => '10 bis rue d’Englancourt, 02120 Marly-Gomont'),

	'La Poste' => array(
		'lieu'      => '27 rue de la Poterie, 02120 Marly-Gomont'),
	'Gîte et chambre d’hôte' => array(
		'lieu'      => '12 rue de Chigny, 02120 Marly-Gomont'),
);

/* Fermees fin 2018. On les nomme, on ne les touche pas : supprimer une fiche
   est une decision de mairie. Le veterinaire est peut-etre remplace par un
   confrere, et l'atelier de motoculture l'est par Maizel a la meme adresse. */
$fermees = array(
	'Cabinet vétérinaire'     => 'ferme le 27 novembre 2018 (SCP Gosset, Mairesse, Vandycke)',
	'Ets Prudhommeaux Daniel' => 'ferme fin 2018 ; Maizel Motoculture occupe la meme adresse',
);

/* Le successeur, propose en brouillon. La mairie confirme qu'il existe
   toujours avant de le publier, comme les autres. */
$nouvelles = array(
	array('nom'       => 'Maizel Motoculture',
	      'categorie' => 'artisan',
	      'activite'  => 'Motoculture : vente, entretien et réparation de matériel.',
	      'telephone' => '03 23 60 20 55',
	      'lieu'      => '3 rue de la Poterie, 02120 Marly-Gomont'),
);

echo $ecrire_pour_de_vrai ? "ECRITURE REELLE\n\n" : "ESSAI — rien ne sera ecrit. Ajouter << ecrire >> pour de vrai.\n\n";

$touchees = 0;
foreach ($trouve as $nom => $valeurs) {
	/* MySQL compare sans distinguer les majuscules : BINARY, pour ne pas
	   confondre deux fiches de noms voisins. */
	$fiche = sql_fetsel('*', 'spip_commerces', 'nom = BINARY ' . sql_quote($nom));
	if (!$fiche) {
		printf("%-42s INTROUVABLE\n", mb_substr($nom, 0, 42));
		continue;
	}

	$champs = array();
	$deja = array();
	foreach ($valeurs as $champ => $valeur) {
		if (trim((string) $fiche[$champ]) !== '') {
			$deja[] = $champ;
			continue;
		}
		$champs[$champ] = $valeur;
	}

	/* Les coordonnees de la carte se deduisent de l'adresse, comme dans le
	   formulaire. Nominatim demande une seconde entre deux requetes. */
	if (isset($champs['lieu']) and trim((string) $fiche['latitude']) === '') {
		if ($ecrire_pour_de_vrai) {
			$point = marly_geocoder($champs['lieu']);
			$champs['latitude']  = $point['latitude'] ?? '';
			$champs['longitude'] = $point['longitude'] ?? '';
			sleep(1);
		} else {
			$champs['latitude'] = $champs['longitude'] = '(a chercher)';
		}
	}

	printf("%-42s %s%s\n", mb_substr($nom, 0, 42),
		$champs ? implode(', ', array_keys($champs)) : 'rien a ajouter',
		$deja ? '   [deja rempli : ' . implode(', ', $deja) . ']' : '');

	if ($champs and $ecrire_pour_de_vrai) {
		sql_updateq('spip_commerces', $champs, 'id_commerce = ' . intval($fiche['id_commerce']));
		$touchees++;
	} elseif ($champs) {
		$touchees++;
	}
}

echo "\nFICHES PERIMEES, a trancher par la mairie :\n";
foreach ($fermees as $nom => $pourquoi) {
	$id = sql_getfetsel('id_commerce', 'spip_commerces', 'nom = BINARY ' . sql_quote($nom));
	printf("  %-30s %s%s\n", mb_substr($nom, 0, 30), $pourquoi,
		$id ? '' : '  (fiche absente)');
}

echo "\nFICHE PROPOSEE :\n";
foreach ($nouvelles as $fiche) {
	if (sql_countsel('spip_commerces', 'nom = BINARY ' . sql_quote($fiche['nom']))) {
		printf("  %-30s DEJA LA, saute\n", $fiche['nom']);
		continue;
	}
	printf("  %-30s a creer en brouillon\n", $fiche['nom']);
	if (!$ecrire_pour_de_vrai) {
		continue;
	}
	$fiche += array('responsable' => '', 'courriel' => '', 'site' => '',
	                'horaires' => '', 'latitude' => '', 'longitude' => '',
	                'rang' => 100, 'statut' => 'prepa');
	$point = marly_geocoder($fiche['lieu']);
	$fiche['latitude']  = $point['latitude'] ?? '';
	$fiche['longitude'] = $point['longitude'] ?? '';
	sleep(1);
	sql_insertq('spip_commerces', $fiche);
}

if ($ecrire_pour_de_vrai) {
	marly_invalider_cache();
}
echo "\n$touchees fiche(s) completee(s). Toutes restent EN BROUILLON.\n";
echo "Ces coordonnees viennent d'annuaires en ligne : a confirmer par telephone avant publication.\n";
