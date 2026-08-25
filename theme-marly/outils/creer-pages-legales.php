<?php
/**
 * Écrit les pages légales : crédits et mentions légales.
 * ---------------------------------------------------------------------------
 *   php theme-marly/outils/creer-pages-legales.php /chemin/racine-web
 *
 * Les quatre pages légales du site sont des ARTICLES portant un mot-clé. Le
 * gabarit va chercher l'article par ce mot-clé, où qu'il soit rangé. Ce
 * script pose les deux qu'on peut écrire aujourd'hui et crée les mots-clés
 * s'ils manquent.
 *
 * OÙ LES RANGER : LA PREMIÈRE VERSION S'EST TROMPÉE. Elle cherchait « la
 * rubrique où sont déjà les articles techniques », et elle est tombée sur
 * Vie associative. Le fil d'Ariane annonçait donc « Accueil › Vie associative
 * › Crédits », et surtout les deux articles s'affichaient dans la liste de
 * cette rubrique, entre deux comptes rendus d'assemblée générale.
 *
 * Un article publié apparaît TOUJOURS dans la liste de sa rubrique : il n'y a
 * pas de rangement discret. Il leur faut donc une rubrique à eux,
 * « Informations légales », placée sous « Ma mairie » — c'est là qu'un
 * habitant les cherche, et le fil d'Ariane devient vrai. Le script la crée si
 * elle manque, et DÉPLACE les articles déjà posés au mauvais endroit.
 *
 * CE QUI EST VRAI AUJOURD'HUI, ET QUI CHANGERA. Le site est hébergé sur le
 * domaine du prestataire, derrière un mot de passe, le temps de la
 * présentation. Le jour de la mise en service, le domaine devient celui de la
 * commune et l'hébergeur peut changer : ces deux lignes se corrigent alors
 * dans l'espace privé, sans toucher au code. C'est exactement pour cela
 * qu'elles sont dans un article et non dans un gabarit.
 *
 * CE QUE LE SCRIPT N'ÉCRIT PAS. La politique de confidentialité et la
 * déclaration d'accessibilité engagent la commune sur ce que le site fait de
 * données personnelles et sur un niveau de conformité mesuré. Personne ne
 * peut les écrire à sa place, et un texte inventé serait pire que la page
 * « en cours de rédaction » qui s'affiche aujourd'hui.
 *
 * L'éditeur des mentions légales est la COMMUNE, pas le prestataire : c'est
 * elle qui publie. Le prestataire n'apparaît que dans les crédits.
 *
 * Il est REJOUABLE : un article portant déjà le mot-clé est laissé tel quel.
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
include_spip('action/editer_objet');
include_spip('inc/marly_outils');

$GLOBALS['visiteur_session'] = array(
	'id_auteur' => 1,
	'statut'    => '0minirezo',
	'webmestre' => 'oui',
	'nom'       => 'reprise CLI',
);

/**
 * La rubrique des pages légales, créée au besoin.
 *
 * Sous « Ma mairie » quand elle existe, à la racine sinon. Elle n'est pas
 * cachée et ne cherche pas à l'être : « Informations légales » sous « Ma
 * mairie » est un chemin que quelqu'un peut suivre, et le fil d'Ariane des
 * pages devient exact.
 */
function marly_rubrique_legale(): int {
	$titre = 'Informations légales';
	$id = sql_getfetsel('id_rubrique', 'spip_rubriques', 'titre = ' . sql_quote($titre));
	if ($id) {
		return (int) $id;
	}
	$parent = sql_getfetsel('id_rubrique', 'spip_rubriques',
		'id_parent = 0 AND titre LIKE ' . sql_quote('%Ma mairie%'));
	$id = sql_insertq('spip_rubriques', array(
		'titre'      => $titre,
		'descriptif' => 'Mentions légales, confidentialité, accessibilité et crédits du site.',
		'id_parent'  => intval($parent),
		'statut'     => 'publie',
	));
	echo "rubrique creee : $titre" . ($parent ? " (sous Ma mairie)" : " (a la racine)") . "\n";
	return (int) $id;
}

/** Le mot-clé, cherché par son titre exact, créé s'il manque. */
function marly_mot(string $titre): int {
	$id_mot = sql_getfetsel('id_mot', 'spip_mots', 'titre = ' . sql_quote($titre));
	if ($id_mot) {
		return (int) $id_mot;
	}
	$id_groupe = sql_getfetsel('id_groupe', 'spip_groupes_mots', 'titre = ' . sql_quote('Emplacements'));
	if (!$id_groupe) {
		$id_groupe = sql_insertq('spip_groupes_mots', array(
			'titre'      => 'Emplacements',
			'descriptif' => 'Où l’article se place dans le site.',
			'articles'   => 'oui',
			'unseul'     => 'non',
		));
		echo "groupe de mots-cles cree : Emplacements\n";
	}
	$id_mot = sql_insertq('spip_mots', array('titre' => $titre, 'id_groupe' => $id_groupe));
	echo "mot-cle cree : $titre\n";
	return (int) $id_mot;
}

$rubrique = marly_rubrique_legale();

$pages = array();

$pages[] = array(
	'mot'   => 'Crédits',
	'titre' => 'Crédits',
	'texte' =>
"{{{Conception et réalisation}}}\n\n"
. "{{Pixfeed}} — entreprise individuelle\n"
. "1 rue des Morillons, 95130 Franconville\n"
. "09 54 32 02 85 — contact@pixfeed.net\n"
. "[pixfeed.net->https://pixfeed.net/]\n\n"
. "Conception graphique, développement, intégration et accompagnement de la mairie.\n\n"
. "{{{Hébergement}}}\n\n"
. "Ce site est hébergé par {{EX2.COM}}\n"
. "CP 70161 Québec STN Québec-Centre, G2K 0A2, Québec, Canada\n"
. "[ex2.com->https://ex2.com/]\n\n"
. "{{{Moteur du site}}}\n\n"
. "SPIP, logiciel libre de gestion éditoriale, sous licence GNU GPL.\n"
. "[spip.net->https://www.spip.net]\n\n"
. "{{{Polices et pictogrammes}}}\n\n"
. "Alegreya et Alegreya Sans, par Juan Pablo del Peral. Open Sans, par Steve Matteson. "
. "Caveat, par Pablo Impallari. Sous licence SIL Open Font.\n"
. "Pictogrammes Remix Icon, sous licence Apache 2.0.\n\n"
. "{{{Dessins et photographies}}}\n\n"
. "L’emblème de la commune et le paysage sont des dessins originaux réalisés pour ce site, "
. "d’après l’église fortifiée Saint-Rémy, classée monument historique en 1928.\n"
. "Sauf mention contraire, photographies de la commune de Marly-Gomont.",
);

$pages[] = array(
	'mot'   => 'Mentions légales',
	'titre' => 'Mentions légales',
	'texte' =>
"{{{Éditeur du site}}}\n\n"
. "Commune de Marly-Gomont\n"
. "4 rue de la Poterie, 02120 Marly-Gomont\n"
. "03 23 60 21 85 — mairie.marlygomont@wanadoo.fr\n\n"
. "{{{Directrice de la publication}}}\n\n"
. "Madame la Maire de Marly-Gomont.\n\n"
. "{{{Hébergement}}}\n\n"
. "EX2.COM\n"
. "CP 70161 Québec STN Québec-Centre, G2K 0A2, Québec, Canada\n"
. "[ex2.com->https://ex2.com/]\n\n"
. "{{{Conception et réalisation}}}\n\n"
. "Pixfeed, 1 rue des Morillons, 95130 Franconville. Voir la page [Crédits->spip.php?page=credits].\n\n"
. "{{{Propriété intellectuelle}}}\n\n"
. "Les textes et les images de ce site appartiennent à la commune de Marly-Gomont, "
. "sauf mention contraire. Leur reproduction est autorisée pour un usage personnel ou "
. "d’information ; toute autre réutilisation demande l’accord écrit de la mairie.\n\n"
. "{{{Signaler une erreur}}}\n\n"
. "Une information inexacte, un lien mort, une page qui ne s’affiche pas : "
. "écrivez à mairie.marlygomont@wanadoo.fr, la correction sera faite.",
);

$ecrits = 0;
foreach ($pages as $page) {
	$id_mot = marly_mot($page['mot']);

	/* Un article porte-t-il deja ce mot-cle ? Si oui, on n'y touche pas : il a
	   peut-etre ete relu et corrige par la mairie depuis. */
	$deja = sql_getfetsel('l.id_objet', 'spip_mots_liens AS l',
		'l.id_mot = ' . intval($id_mot) . ' AND l.objet = "article"');
	if ($deja) {
		/* On ne recrit pas l'article — il a peut-etre ete relu par la mairie —
		   mais on le DEPLACE s'il est au mauvais endroit. C'est la reparation
		   de la premiere version de ce script. */
		$ou = sql_getfetsel('id_rubrique', 'spip_articles', 'id_article = ' . intval($deja));
		if ($ou != $rubrique) {
			objet_modifier('article', $deja, array('id_rubrique' => $rubrique));
			echo 'DEPLACE : ' . $page['titre'] . " (article $deja, rubrique $ou -> $rubrique)\n";
			$ecrits++;
		} else {
			echo 'DEJA LA, saute : ' . $page['titre'] . " (article $deja)\n";
		}
		continue;
	}

	$id_article = objet_inserer('article', $rubrique);
	if (!$id_article) {
		fwrite(STDERR, 'ECHEC creation : ' . $page['titre'] . "\n");
		continue;
	}
	objet_modifier('article', $id_article, array(
		'titre'  => $page['titre'],
		'texte'  => $page['texte'],
		'statut' => 'publie',
	));
	/* Publier retimbre la date de l'article. Ici elle doit bien être celle du
	   jour — ces pages sont écrites aujourd'hui — mais on la pose quand même
	   explicitement : c'est le seul moyen de dire que ce n'est pas un oubli.
	   Deux imports d'archives ont déjà été datés du jour faute de l'avoir
	   fait, et le vérificateur le refuse depuis. */
	sql_updateq('spip_articles', array('date' => date('Y-m-d H:i:s')),
		'id_article = ' . intval($id_article));

	sql_insertq('spip_mots_liens', array(
		'id_mot'   => $id_mot,
		'id_objet' => $id_article,
		'objet'    => 'article',
	));
	echo 'ecrite : ' . $page['titre'] . " (article $id_article, rubrique $rubrique)\n";
	$ecrits++;
}

marly_invalider_cache();
echo "\n$ecrits page(s) ecrite(s).\n";
echo "A relire dans Edition > Articles avant de montrer le site.\n";
echo "\nDeux pages restent vides, et c'est voulu : la politique de\n";
echo "confidentialite et la declaration d'accessibilite engagent la commune\n";
echo "sur ce que le site fait des donnees et sur un niveau mesure. Personne ne\n";
echo "peut les ecrire a sa place.\n";
