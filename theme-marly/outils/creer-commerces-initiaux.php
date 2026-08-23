<?php
/**
 * Crée les fiches de commerces reprises de l'ancien site, EN BROUILLON.
 * ---------------------------------------------------------------------------
 *   php theme-marly/outils/creer-commerces-initiaux.php /chemin/racine-web
 *
 * L'ancienne page « Commerces et Services » donnait dix-neuf noms et rien
 * d'autre : aucun téléphone, aucune adresse, aucun horaire. Ce script pose
 * donc les dix-neuf fiches avec ce qu'on sait — le nom, la catégorie, ce
 * qu'on y trouve — et laisse le contact vide.
 *
 * ELLES SONT CRÉÉES EN « prepa », c'est-à-dire NON PUBLIÉES, et c'est le
 * coeur du procédé. Une fiche de commerce sans numéro ne sert à rien : c'est
 * la seule chose qu'on vient y chercher, et le formulaire de saisie l'exige
 * pour cette raison. Publier dix-neuf fiches muettes donnerait un annuaire
 * qui a l'air rempli et ne répond à aucune question.
 *
 * En brouillon, elles ne paraissent pas sur le site, et l'espace privé les
 * affiche comme une liste de travail : la secrétaire ouvre chaque fiche,
 * appelle le commerçant, note le numéro et les horaires, et passe la fiche
 * en publié. L'annuaire se remplit au rythme des réponses.
 *
 * Cette liste date d'au moins dix ans : certains commerces ont pu fermer,
 * d'autres ouvrir. C'est aussi ce que la mairie vérifiera fiche par fiche.
 *
 * Il est REJOUABLE : un commerce dont le nom existe déjà est sauté.
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
include_spip('inc/marly_commerces');
include_spip('inc/marly_outils');

/* En ligne de commande, personne n'est connecte : SPIP refuse alors en
   silence le passage en << publie >> des objets crees. On se presente en
   webmestre avant d'ecrire, comme dans les autres scripts. */
$GLOBALS['visiteur_session'] = array(
	'id_auteur' => 1,
	'statut'    => '0minirezo',
	'webmestre' => 'oui',
	'nom'       => 'reprise CLI',
);

/* Les dix-neuf entrees de l'ancienne page, telles qu'elle les nommait. Les
   phrases d'activite sont ecrites d'apres ce qu'elle en disait, sans rien
   inventer : quand elle ne disait que le metier, la phrase ne dit que le
   metier. Aucun numero, aucune adresse, aucun horaire : elle n'en donnait
   aucun, et il n'est pas question d'en deviner. */
$fiches = array(
	array('nom' => 'Boucherie-charcuterie Proxi Desse', 'categorie' => 'commerce',
	      'activite' => 'Boucherie, charcuterie, épicerie et produits frais.'),
	array('nom' => 'Boulangerie « La mie des Marlysiens »', 'categorie' => 'commerce',
	      'activite' => 'Pain, viennoiseries et pâtisserie.', 'responsable' => 'Maxime Midelet'),
	array('nom' => 'Coiffure et esthétique Maryse', 'categorie' => 'commerce',
	      'activite' => 'Salon de coiffure et soins esthétiques.'),

	array('nom' => 'Cabinet de kinésithérapie', 'categorie' => 'sante',
	      'activite' => 'Masseurs-kinésithérapeutes.', 'responsable' => 'A. Hersoy et L. Durbecq'),
	array('nom' => 'Cabinet infirmier — Florence Alizard', 'categorie' => 'sante',
	      'activite' => 'Soins infirmiers.', 'responsable' => 'Florence Alizard'),
	array('nom' => 'Cabinet infirmier — Odile Pecriaud', 'categorie' => 'sante',
	      'activite' => 'Soins infirmiers.', 'responsable' => 'Odile Pecriaud'),
	array('nom' => 'Cabinet médical', 'categorie' => 'sante',
	      'activite' => 'Médecins généralistes, sur rendez-vous.',
	      'responsable' => 'Docteurs Tréhou, Papon et Ducollet'),
	array('nom' => 'Cabinet vétérinaire', 'categorie' => 'sante',
	      'activite' => 'Cabinet vétérinaire.', 'responsable' => 'Gosset, Mairesse et Vandycke'),
	array('nom' => 'Pharmacie Horiot', 'categorie' => 'sante',
	      'activite' => 'Officine et matériel médical.'),

	array('nom' => 'Damiens Patrick', 'categorie' => 'artisan',
	      'activite' => 'Commerce de bestiaux.'),
	array('nom' => 'Dequesne François', 'categorie' => 'artisan',
	      'activite' => 'Couvreur.'),
	array('nom' => 'Dumange Nicolas', 'categorie' => 'artisan',
	      'activite' => 'Terrassement.'),
	array('nom' => 'Ets Prudhommeaux Daniel', 'categorie' => 'artisan',
	      'activite' => 'Motoculture et tronçonneuses.', 'responsable' => 'Daniel Prudhommeaux'),
	array('nom' => 'Ets Ramolu', 'categorie' => 'artisan',
	      'activite' => 'Chauffage et sanitaire.'),
	array('nom' => 'Garage Finet', 'categorie' => 'artisan',
	      'activite' => 'Mécanique générale.'),
	array('nom' => 'Vallerand Romain', 'categorie' => 'artisan',
	      'activite' => 'Électricien.'),

	array('nom' => 'Espace C Numérique', 'categorie' => 'service',
	      'activite' => 'Accès à internet.'),
	array('nom' => 'Gîte et chambre d’hôte', 'categorie' => 'service',
	      'activite' => 'Hébergement dans le village.'),
	array('nom' => 'La Poste', 'categorie' => 'service',
	      'activite' => 'Bureau de poste.'),
);

$crees = 0;
foreach ($fiches as $fiche) {
	/* Le nom est compare a la casse pres : MySQL compare sans distinguer les
	   majuscules, et deux fiches proches passeraient l'une pour l'autre. */
	if (sql_countsel('spip_commerces', 'nom = BINARY ' . sql_quote($fiche['nom']))) {
		echo 'DEJA LA, saute : ' . $fiche['nom'] . "\n";
		continue;
	}

	$fiche += array('responsable' => '', 'telephone' => '', 'courriel' => '',
	                'site' => '', 'lieu' => '', 'horaires' => '',
	                'latitude' => '', 'longitude' => '', 'rang' => 100,
	                'statut' => 'prepa');

	$id = sql_insertq('spip_commerces', $fiche);
	if (!$id) {
		fwrite(STDERR, 'ECHEC insertion : ' . $fiche['nom'] . "\n");
		continue;
	}
	echo 'creee (brouillon) : ' . $fiche['nom'] . "\n";
	$crees++;
}

marly_invalider_cache();
echo "\n$crees fiche(s) creee(s), toutes EN BROUILLON.\n";
echo "Elles n'apparaissent pas sur le site : il leur manque un contact.\n";
echo "Espace prive > Edition > Commerces : completer et publier une par une.\n";
