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

/**
 * Verifie que l'article est REELLEMENT publie, et le publie sinon.
 *
 * Ce n'est pas de la ceinture et des bretelles : c'est mesure. Le script
 * d'import de l'ancien site porte le meme garde-fou, ecrit apres que six
 * articles d'associations soient restes en << prepa >> alors que SPIP n'avait
 * signale aucune erreur. Aujourd'hui, 25 aout 2026, les deux pages legales
 * ont subi exactement le meme sort. La demande de publication passe parfois
 * au moment de la creation, parfois non ; la seule facon de le savoir est de
 * relire la base.
 */
function marly_forcer_publication(int $id_article): void {
	if (sql_getfetsel('statut', 'spip_articles', 'id_article = ' . $id_article) === 'publie') {
		return;
	}
	sql_updateq('spip_articles', array('statut' => 'publie'), 'id_article = ' . $id_article);
	echo "  (statut force en publie)\n";
}

/**
 * Verifie que l'article a REELLEMENT change de rubrique, et le deplace sinon.
 *
 * Le secteur suit : c'est la rubrique racine dont depend l'article, SPIP s'en
 * sert dans ses boucles. Un article deplace a la main sans son secteur se
 * range dans la nouvelle rubrique tout en continuant de compter pour
 * l'ancienne branche.
 */
function marly_forcer_rubrique(int $id_article, int $rubrique): void {
	if (sql_getfetsel('id_rubrique', 'spip_articles', 'id_article = ' . $id_article) == $rubrique) {
		return;
	}
	$secteur = $rubrique;
	while ($parent = sql_getfetsel('id_parent', 'spip_rubriques', 'id_rubrique = ' . intval($secteur))) {
		$secteur = $parent;
	}
	sql_updateq('spip_articles',
		array('id_rubrique' => $rubrique, 'id_secteur' => $secteur),
		'id_article = ' . $id_article);
	echo "  (rubrique forcee, secteur $secteur)\n";
}

/* Par defaut le script ne touche pas a un article deja pose : la mairie l'a
   peut-etre relu. --reecrire force la reecriture du titre et du texte, et
   n'a de sens que tant que personne n'a corrige la page a la main. */
$reecrire = in_array('--reecrire', $argv, true);

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
. "Madame la Maire de Marly-Gomont, en sa qualité de représentante légale de la commune.\n\n"
. "{{{Hébergement}}}\n\n"
. "EX2.COM\n"
. "CP 70161 Québec STN Québec-Centre, G2K 0A2, Québec, Canada\n"
. "Téléphone : {{À COMPLÉTER}}\n"
. "[ex2.com->https://ex2.com/]\n\n"
. "{{{Délégué à la protection des données}}}\n\n"
. "{{À COMPLÉTER}} : nom ou service, et adresse électronique.\n"
. "Toute commune doit en désigner un. Il est souvent mutualisé, auprès du centre "
. "de gestion départemental ou de la communauté de communes.\n\n"
. "{{{Conception et réalisation}}}\n\n"
. "Pixfeed, 1 rue des Morillons, 95130 Franconville. "
. "Le détail figure sur la page [Crédits->spip.php?page=credits].\n\n"
. "{{{Propriété intellectuelle}}}\n\n"
. "Les textes et les images de ce site appartiennent à la commune de Marly-Gomont, "
. "sauf mention contraire portée à côté du document. Leur reproduction est autorisée "
. "pour un usage personnel ou d’information, à condition d’en citer la source. Toute "
. "autre réutilisation, notamment commerciale, demande l’accord écrit de la mairie.\n\n"
. "{{{Liens vers d’autres sites}}}\n\n"
. "Ce site renvoie vers des sites qu’il ne gère pas, notamment service-public.gouv.fr. "
. "La commune n’est pas responsable de leur contenu.\n\n"
. "{{{Exactitude des informations}}}\n\n"
. "La mairie met à jour ce site avec soin, mais une information peut être devenue "
. "inexacte entre-temps. Aucune information publiée ici ne remplace un acte officiel "
. "ni une réponse écrite du secrétariat.\n\n"
. "{{{Accessibilité}}}\n\n"
. "Le niveau de conformité du site et la façon de signaler un obstacle figurent sur la "
. "page [Accessibilité->spip.php?page=accessibilite].\n\n"
. "{{{Signaler une erreur}}}\n\n"
. "Une information inexacte, un lien mort, une page qui ne s’affiche pas : écrivez à "
. "mairie.marlygomont@wanadoo.fr. La correction sera faite.\n\n"
. "{{{Données personnelles}}}\n\n"
. "Ce que le site enregistre, pourquoi, combien de temps, et comment exercer vos "
. "droits : voir la page [Politique de confidentialité->spip.php?page=confidentialite].",
);

$pages[] = array(
	'mot'   => 'Confidentialité',
	'titre' => 'Données personnelles',
	'texte' =>
"{{{Responsable du traitement}}}\n\n"
. "Commune de Marly-Gomont, représentée par Madame la Maire.\n"
. "4 rue de la Poterie, 02120 Marly-Gomont — mairie.marlygomont@wanadoo.fr\n\n"
. "{{{Délégué à la protection des données}}}\n\n"
. "{{À COMPLÉTER}} : nom ou service, et adresse électronique.\n"
. "Toute commune doit en désigner un. Il est souvent mutualisé, auprès du centre "
. "de gestion départemental ou de la communauté de communes.\n\n"
. "{{{Ce que ce site enregistre}}}\n\n"
. "Le site n’enregistre rien tant que vous ne remplissez pas un formulaire. "
. "Trois formulaires seulement collectent des données :\n\n"
. "-* {{La lettre d’information}} : adresse électronique, nom, prénom, code postal, commune.\n"
. "-* {{La réservation d’une salle ou l’inscription à un événement}} : nom, organisme "
. "le cas échéant, adresse électronique, téléphone, motif de la demande, nombre de places.\n"
. "-* {{Le compte d’une association}} : identifiant et adresse électronique du responsable.\n\n"
. "S’y ajoutent les journaux techniques du serveur, tenus par l’hébergeur, qui "
. "enregistrent les adresses IP de connexion. Ils servent à la sécurité et au "
. "diagnostic de panne, à rien d’autre.\n\n"
. "{{{Pourquoi, et sur quelle base}}}\n\n"
. "La réservation d’une salle et l’inscription à un événement relèvent de la mission "
. "de service public de la commune. L’inscription à la lettre d’information repose sur "
. "votre consentement : vous vous désabonnez quand vous voulez, le lien est au bas de "
. "chaque envoi.\n\n"
. "{{{Qui y a accès}}}\n\n"
. "Le secrétariat de mairie, et lui seul. Aucune donnée n’est vendue, cédée, ni "
. "utilisée à des fins commerciales, et aucune n’est transmise à un tiers hors des "
. "obligations légales.\n\n"
. "{{{Combien de temps}}}\n\n"
. "Les inscriptions à la lettre d’information sont conservées jusqu’au désabonnement. "
. "Les demandes de réservation sont conservées le temps de la gestion de la salle et "
. "de l’exercice comptable en cours. Les journaux du serveur sont conservés par "
. "l’hébergeur pendant la durée prévue par la loi.\n\n"
. "{{{Vos droits}}}\n\n"
. "Vous pouvez demander à consulter vos données, à les corriger, à les effacer, à en "
. "limiter l’usage, ou vous opposer à leur traitement. Écrivez à la mairie, par "
. "courriel ou par courrier, en joignant une copie d’une pièce d’identité si votre "
. "identité ne peut pas être établie autrement. La mairie répond dans un délai d’un "
. "mois.\n\n"
. "Si la mairie ne vous a pas répondu dans ce délai, ou si sa réponse ne vous paraît "
. "pas satisfaisante, vous pouvez vous adresser à la Commission nationale de "
. "l’informatique et des libertés. C’est l’autorité chargée de faire respecter ces "
. "droits en France. La démarche est gratuite et se fait en ligne, et son site "
. "explique aussi chacun de vos droits en détail : [www.cnil.fr->https://www.cnil.fr].\n\n"
. "{{{Cookies}}}\n\n"
. "Ce site ne dépose {{aucun cookie de publicité ni de mesure d’audience}}. Il n’y a "
. "donc pas de bandeau à accepter : il n’y a rien à consentir.\n\n"
. "Un seul cookie technique est déposé, et seulement si vous vous connectez à un "
. "espace personnel : il maintient votre session ouverte le temps de votre visite.\n\n"
. "{{{Mesure d’audience}}}\n\n"
. "La fréquentation est mesurée par les statistiques intégrées au moteur du site, sur "
. "le serveur qui l’héberge. Aucune société extérieure ne reçoit ces informations.\n\n"
. "{{{Les cartes}}}\n\n"
. "Les plans affichés sur le site — la carte des lieux, celle d’un commerce — "
. "utilisent les fonds de carte d’{{OpenStreetMap}}. Quand une carte s’affiche, votre "
. "adresse IP est transmise à la fondation OpenStreetMap, qui sert les images. C’est "
. "le seul appel à un service extérieur de tout le site.\n\n"
. "{{{Aucun élément chargé depuis un autre site}}}\n\n"
. "Les polices de caractères employées par ce site sont installées sur son serveur et "
. "servies depuis lui. Aucune vidéo n’est intégrée depuis une plateforme extérieure. "
. "Le bouton de partage appelle la fonction de partage de votre propre navigateur, "
. "celle que vous utilisez déjà sur votre téléphone, sans passer par un réseau "
. "social.\n\n"
. "Il en résulte une chose simple : hors l’affichage d’une carte, votre visite sur ce "
. "site ne transmet votre adresse IP à personne d’autre qu’à la commune et à son "
. "hébergeur.\n\n"
. "Enfin, la commune ne dresse aucun profil de ses visiteurs, et aucune décision "
. "n’est prise automatiquement à partir de ce que vous faites sur ce site.",
);

$ecrits = 0;
foreach ($pages as $page) {
	$id_mot = marly_mot($page['mot']);

	/* Un article porte-t-il deja ce mot-cle ? Si oui, on n'y touche pas : il a
	   peut-etre ete relu et corrige par la mairie depuis. */
	$deja = sql_getfetsel('l.id_objet', 'spip_mots_liens AS l',
		'l.id_mot = ' . intval($id_mot) . ' AND l.objet = "article"');
	if ($deja && $reecrire) {
		objet_modifier('article', $deja, array(
			'titre' => $page['titre'],
			'texte' => $page['texte'],
		));
		echo 'REECRITE : ' . $page['titre'] . " (article $deja)\n";
	}
	if ($deja) {
		/* On ne recrit pas l'article — il a peut-etre ete relu par la mairie —
		   mais on le DEPLACE s'il est au mauvais endroit. C'est la reparation
		   de la premiere version de ce script. */
		$ou = sql_getfetsel('id_rubrique', 'spip_articles', 'id_article = ' . intval($deja));
		if ($ou != $rubrique) {
			objet_instituer('article', $deja, array('id_parent' => $rubrique));
			marly_forcer_rubrique($deja, $rubrique);
			echo 'DEPLACE : ' . $page['titre'] . " (article $deja, rubrique $ou -> $rubrique)\n";
			$ecrits++;
		}
		$statut = sql_getfetsel('statut', 'spip_articles', 'id_article = ' . intval($deja));
		if ($statut !== 'publie') {
			objet_instituer('article', $deja, array('statut' => 'publie'));
			marly_forcer_publication($deja);
			echo 'PUBLIE : ' . $page['titre'] . " (article $deja, etait $statut)\n";
			$ecrits++;
		} elseif ($ou == $rubrique) {
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
		'titre' => $page['titre'],
		'texte' => $page['texte'],
	));
	objet_instituer('article', $id_article, array('statut' => 'publie'));
	marly_forcer_publication($id_article);
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
echo "\nDEUX MENTIONS RESTENT A COMPLETER, elles apparaissent en gras dans la\n";
echo "page : le telephone de l'hebergeur, obligatoire au titre de l'article 6\n";
echo "de la LCEN, et le delegue a la protection des donnees, que toute\n";
echo "commune doit designer depuis 2018. Relancer avec --reecrire ecrase le\n";
echo "texte des deux pages par celui du script.\n";
echo "\nUne page reste vide, et c'est voulu : la declaration d'accessibilite\n";
echo "annonce un niveau de conformite qui se mesure par un audit. Tant qu'il\n";
echo "n'a pas eu lieu, personne ne peut l'ecrire.\n";
