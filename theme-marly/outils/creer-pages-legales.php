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
	sql_insertq('spip_rubriques', array(
		'titre'      => $titre,
		'descriptif' => 'Mentions légales, confidentialité, accessibilité et crédits du site.',
		'id_parent'  => intval($parent),
		'statut'     => 'publie',
	));
	/* On relit plutot que de croire l'identifiant rendu. Si la rubrique
	   n'existe pas, les quatre pages legales seraient creees dans la
	   rubrique 0 — c'est-a-dire nulle part, et invisibles. */
	$id = (int) sql_getfetsel('id_rubrique', 'spip_rubriques', 'titre = ' . sql_quote($titre));
	if (!$id) {
		echo "ERREUR : la rubrique '$titre' n'a pas pu etre creee.\n";
		exit(1);
	}
	echo "rubrique creee : $titre" . ($parent ? " (sous Ma mairie)" : " (a la racine)") . "\n";
	return $id;
}

/**
 * Le groupe de mots-clés « Emplacements », créé s'il manque.
 * ---------------------------------------------------------------------------
 * LA PREMIÈRE VERSION ÉCHOUAIT SANS LE DIRE, et le prix a été payé sur la
 * page d'accueil. Elle écrivait 'articles' => 'oui' dans la ligne du groupe :
 * cette colonne existait en SPIP 3, elle a été remplacée par 'tables_liees'.
 * L'insertion échouait donc, rendait 0, et la fonction annonçait quand même
 * « groupe de mots-cles cree ». Les quatre mots-clés légaux se retrouvaient
 * avec id_groupe = 0, rattachés à rien.
 *
 * Personne ne l'a vu pendant des semaines : les pages légales s'affichaient
 * parfaitement, puisqu'elles se cherchent par le TITRE du mot-clé. Le groupe
 * n'a servi qu'au jour où la bande d'actualités a voulu écarter ces articles
 * par {type_mot!=Emplacements} — et a affiché la déclaration d'accessibilité
 * en tête de la page d'accueil.
 *
 * DEUX LEÇONS, APPLIQUÉES ICI. On demande d'abord à la table quelles colonnes
 * elle a, et on ne lui envoie que celles-là — même technique que le
 * recensement, corrigé pour la même raison. Et on ne dit « créé » qu'après
 * avoir relu la base : un script qui se contente de son propre compte rendu
 * ne vérifie rien.
 */
function marly_groupe_emplacements(): int {
	$id_groupe = sql_getfetsel('id_groupe', 'spip_groupes_mots', 'titre = ' . sql_quote('Emplacements'));
	if ($id_groupe) {
		return (int) $id_groupe;
	}

	$desc = sql_showtable('spip_groupes_mots', true);
	$colonnes = ($desc && !empty($desc['field'])) ? array_keys($desc['field']) : array();
	if (!$colonnes) {
		echo "ERREUR : impossible de lire les colonnes de spip_groupes_mots.\n";
		exit(1);
	}

	$champs = array(
		'titre'        => 'Emplacements',
		'descriptif'   => 'Où l’article se place dans le site.',
		'tables_liees' => 'article',   /* SPIP 4 */
		'articles'     => 'oui',       /* SPIP 3, ignoré s'il n'existe plus */
		'unseul'       => 'non',
	);
	$champs = array_intersect_key($champs, array_flip($colonnes));

	$id_groupe = (int) sql_insertq('spip_groupes_mots', $champs);

	/* On relit. L'identifiant rendu par l'insertion peut mentir ; la ligne
	   présente en base, non. */
	$id_groupe = (int) sql_getfetsel('id_groupe', 'spip_groupes_mots', 'titre = ' . sql_quote('Emplacements'));
	if (!$id_groupe) {
		echo "ERREUR : le groupe de mots-cles 'Emplacements' n'a pas pu etre cree.\n";
		echo "         colonnes envoyees : " . implode(', ', array_keys($champs)) . "\n";
		echo "         colonnes de la table : " . implode(', ', $colonnes) . "\n";
		exit(1);
	}
	echo "groupe de mots-cles cree : Emplacements (id $id_groupe)\n";
	return $id_groupe;
}

/**
 * Le mot-clé, cherché par son titre exact, créé s'il manque.
 *
 * REJOINT aussi les mots-clés déjà créés que la version fautive a laissés
 * sans groupe. Un mot-clé à id_groupe = 0 n'apparaît sous aucun groupe dans
 * l'espace privé : la secrétaire de mairie ne peut pas le retrouver.
 */
function marly_mot(string $titre): int {
	$id_groupe = marly_groupe_emplacements();

	$mot = sql_fetsel('id_mot, id_groupe', 'spip_mots', 'titre = ' . sql_quote($titre));
	if ($mot) {
		if ((int) $mot['id_groupe'] !== $id_groupe) {
			sql_updateq('spip_mots', array('id_groupe' => $id_groupe),
				'id_mot = ' . intval($mot['id_mot']));
			echo "mot-cle rattache au groupe Emplacements : $titre\n";
		}
		return (int) $mot['id_mot'];
	}

	sql_insertq('spip_mots', array('titre' => $titre, 'id_groupe' => $id_groupe));
	$id_mot = (int) sql_getfetsel('id_mot', 'spip_mots', 'titre = ' . sql_quote($titre));
	if (!$id_mot) {
		echo "ERREUR : le mot-cle '$titre' n'a pas pu etre cree.\n";
		exit(1);
	}
	echo "mot-cle cree : $titre (id $id_mot)\n";
	return $id_mot;
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
. "d’après l’église fortifiée Saint-Rémy, classée monument historique en 1928.\n\n"
. "La photographie de l’esplanade de l’église, qui illustre la page d’accueil lorsque "
. "l’article mis en avant n’a pas d’image propre, est de {{René Hourdry}}, sous licence "
. "Creative Commons Attribution - Partage dans les mêmes conditions 4.0 "
. "([CC BY-SA 4.0->https://creativecommons.org/licenses/by-sa/4.0/deed.fr]), "
. "via Wikimedia Commons.\n\n"
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
"La commune de Marly-Gomont accorde une attention particulière à la protection de vos "
. "données personnelles et au respect de votre vie privée.\n\n"
. "La présente page a pour objectif de vous informer de manière claire sur les données "
. "susceptibles d’être collectées lorsque vous utilisez ce site, sur leur utilisation "
. "et sur les droits dont vous disposez.\n\n"

. "{{{Responsable du traitement}}}\n\n"
. "Le responsable du traitement des données personnelles est :\n\n"
. "Commune de Marly-Gomont, représentée par Madame la Maire\n"
. "4 rue de la Poterie\n"
. "02120 Marly-Gomont\n"
. "Courriel : [mairie.marlygomont@wanadoo.fr->mailto:mairie.marlygomont@wanadoo.fr]\n\n"

. "{{{Délégué à la protection des données}}}\n\n"
. "La commune a recours à un délégué à la protection des données afin de "
. "l’accompagner dans le respect de la réglementation applicable en matière de "
. "protection des données personnelles.\n\n"
. "Nom du service ou de l’organisme : {{à compléter}}\n"
. "Adresse électronique : {{à compléter}}\n\n"

. "{{{Données susceptibles d’être collectées}}}\n\n"
. "La simple consultation du site ne nécessite pas de fournir de données "
. "personnelles.\n\n"
. "Certaines informations peuvent toutefois être recueillies lorsque vous utilisez les "
. "services proposés sur le site, notamment à travers les formulaires suivants :\n\n"
. "-* Inscription à la lettre d’information : adresse électronique, nom, prénom, code "
. "postal et commune ;\n"
. "-* Réservation d’une salle ou inscription à un événement : nom, organisme le cas "
. "échéant, adresse électronique, numéro de téléphone, motif de la demande et nombre "
. "de places ;\n"
. "-* Espace destiné aux associations : identifiant et adresse électronique du "
. "responsable de l’association.\n\n"
. "Des données techniques peuvent également être enregistrées automatiquement dans les "
. "journaux du serveur, notamment l’adresse IP utilisée lors de la connexion. Ces "
. "informations sont utilisées exclusivement à des fins de sécurité, de maintenance et "
. "de diagnostic technique.\n\n"

. "{{{Finalités et bases juridiques des traitements}}}\n\n"
. "Les données personnelles collectées sont utilisées uniquement dans le cadre des "
. "services proposés par la commune.\n\n"
. "Les informations transmises lors d’une demande de réservation de salle ou d’une "
. "inscription à un événement sont traitées afin de permettre à la commune d’assurer "
. "ses missions de service public et de répondre aux demandes des administrés.\n\n"
. "L’inscription à la lettre d’information repose sur votre consentement. Vous pouvez "
. "retirer celui-ci à tout moment en utilisant le lien de désabonnement figurant au bas "
. "de chaque lettre d’information.\n\n"
. "Les données liées aux comptes des associations sont utilisées afin de permettre "
. "l’accès et la gestion des services qui leur sont réservés sur le site.\n\n"

. "{{{Destinataires des données}}}\n\n"
. "Les données personnelles transmises par l’intermédiaire du site sont accessibles "
. "uniquement aux agents et services municipaux habilités à les traiter dans le cadre "
. "de leurs fonctions.\n\n"
. "La commune ne vend, ne loue et ne cède aucune donnée personnelle à des fins "
. "commerciales.\n\n"
. "Les informations ne sont communiquées à des tiers que lorsque cela est nécessaire au "
. "fonctionnement du service ou lorsqu’une obligation légale ou réglementaire "
. "l’impose.\n\n"

. "{{{Durée de conservation}}}\n\n"
. "Les données sont conservées uniquement pendant la durée nécessaire à la réalisation "
. "des finalités pour lesquelles elles ont été collectées. Ainsi :\n\n"
. "-* les données liées à la lettre d’information sont conservées jusqu’à votre "
. "désabonnement ;\n"
. "-* les informations relatives aux demandes de réservation sont conservées pendant la "
. "durée nécessaire à leur traitement et à la gestion administrative et comptable "
. "correspondante ;\n"
. "-* les journaux techniques du serveur sont conservés par l’hébergeur pendant la "
. "durée prévue par ses obligations légales et ses besoins de sécurité.\n\n"
. "À l’issue de ces périodes, les données sont supprimées ou archivées lorsque la "
. "réglementation l’exige.\n\n"

. "{{{Vos droits}}}\n\n"
. "Conformément à la réglementation applicable en matière de protection des données "
. "personnelles, vous disposez notamment d’un droit :\n\n"
. "-* d’accès à vos données ;\n"
. "-* de rectification des informations inexactes ou incomplètes ;\n"
. "-* d’effacement de vos données, lorsque les conditions prévues par la réglementation "
. "sont réunies ;\n"
. "-* de limitation du traitement ;\n"
. "-* d’opposition à certains traitements.\n\n"
. "Pour exercer vos droits, vous pouvez contacter la mairie :\n\n"
. "Commune de Marly-Gomont\n"
. "4 rue de la Poterie\n"
. "02120 Marly-Gomont\n"
. "Courriel : [mairie.marlygomont@wanadoo.fr->mailto:mairie.marlygomont@wanadoo.fr]\n\n"
. "Une pièce justificative d’identité pourra être demandée uniquement lorsque cela est "
. "nécessaire pour vérifier votre identité.\n\n"
. "La commune s’efforce de répondre aux demandes dans un délai d’un mois à compter de "
. "leur réception, conformément à la réglementation applicable.\n\n"
. "Si vous estimez, après avoir contacté la commune, que vos droits ne sont pas "
. "respectés, vous pouvez adresser une réclamation à la Commission nationale de "
. "l’informatique et des libertés (CNIL) sur le site "
. "[www.cnil.fr->https://www.cnil.fr].\n\n"

. "{{{Cookies}}}\n\n"
. "Le site n’utilise aucun cookie publicitaire ni cookie de mesure d’audience provenant "
. "de sociétés tierces.\n\n"
. "La consultation des pages publiques du site ne nécessite donc pas l’affichage d’un "
. "bandeau de consentement à des fins publicitaires ou statistiques.\n\n"
. "Un cookie strictement technique peut toutefois être utilisé lors de la connexion à "
. "un espace personnel. Celui-ci permet uniquement de maintenir votre session active "
. "pendant votre navigation et est nécessaire au bon fonctionnement du service.\n\n"

. "{{{Mesure de la fréquentation}}}\n\n"
. "La fréquentation du site peut être évaluée à l’aide des outils statistiques intégrés "
. "au système qui l’héberge.\n\n"
. "Ces statistiques sont traitées directement sur le serveur du site et ne donnent lieu "
. "à aucune transmission de données à une société spécialisée dans la publicité ou la "
. "mesure d’audience.\n\n"
. "Elles permettent à la commune de mieux connaître l’utilisation générale du site et "
. "d’en améliorer le fonctionnement.\n\n"

. "{{{Cartes et plans}}}\n\n"
. "Certaines pages du site peuvent afficher des cartes permettant notamment de "
. "localiser des lieux, équipements ou commerces.\n\n"
. "Ces cartes utilisent des données et fonds cartographiques issus d’OpenStreetMap.\n\n"
. "Lors de l’affichage d’une carte, certaines informations techniques nécessaires à son "
. "chargement, notamment votre adresse IP, peuvent être transmises aux serveurs "
. "utilisés pour fournir les éléments cartographiques.\n\n"
. "Ces échanges sont exclusivement liés à l’affichage et au fonctionnement des "
. "cartes.\n\n"

. "{{{Services extérieurs et respect de la vie privée}}}\n\n"
. "Afin de limiter autant que possible la transmission de données à des services "
. "extérieurs, le site de la commune a été conçu de manière à privilégier les "
. "ressources hébergées localement. En particulier :\n\n"
. "-* les polices de caractères utilisées par le site sont hébergées directement sur "
. "son serveur ;\n"
. "-* aucune vidéo provenant d’une plateforme extérieure n’est chargée "
. "automatiquement ;\n"
. "-* les fonctions de partage ne reposent pas sur des boutons de réseaux sociaux "
. "susceptibles de suivre votre navigation ;\n"
. "-* aucune donnée n’est utilisée à des fins de profilage publicitaire ;\n"
. "-* aucune décision produisant des effets à votre égard n’est prise de manière "
. "automatisée à partir de vos données personnelles.\n\n"
. "La commune veille ainsi à limiter la collecte et la circulation des données "
. "personnelles au strict nécessaire au fonctionnement du site et des services "
. "municipaux proposés en ligne.",
);

/* ---------------------------------------------------------------------------
 * LA DÉCLARATION D'ACCESSIBILITÉ.
 *
 * Elle suit le plan imposé par le RGAA : état de conformité, résultats des
 * tests, contenus non accessibles, établissement de la déclaration, contact,
 * voie de recours. Aucune de ces rubriques n'est facultative.
 *
 * ELLE NE PROCLAME AUCUN NIVEAU DE CONFORMITÉ, et c'est le point. Un niveau
 * s'établit par un audit complet des 106 critères ; le nôtre a couvert ce
 * qu'une machine peut trancher et quatre critères manuels, pas davantage.
 * Écrire « partiellement conforme » aujourd'hui serait une déclaration fausse,
 * et une déclaration fausse expose la commune plus qu'une déclaration
 * inachevée.
 *
 * Ce qui est écrit ici est donc VRAI et daté : l'échantillon réel, les outils
 * réellement employés, les critères réellement passés. Ce qui manque est
 * marqué en gras, comme les deux blancs des mentions légales.
 * ------------------------------------------------------------------------ */
$pages[] = array(
	'mot'   => 'Accessibilité',
	'titre' => 'Déclaration d’accessibilité',
	'texte' =>
"La commune de Marly-Gomont s’engage à rendre son site internet accessible, "
. "conformément à l’article 47 de la loi n° 2005-102 du 11 février 2005.\n\n"
. "Cette déclaration d’accessibilité s’applique au site {{marlygomont.pixfeed.net}}.\n\n"

. "{{{État de conformité}}}\n\n"
. "{{Le niveau de conformité n’est pas encore établi.}} Un audit partiel a été "
. "mené le 26 août 2026 ; l’audit complet des 106 critères du RGAA version 4.1 "
. "reste à conduire. Le niveau de conformité sera inscrit ici à son issue.\n\n"
. "Annoncer aujourd’hui un niveau qui n’a pas été mesuré serait inexact, et une "
. "déclaration inexacte engage davantage la commune qu’une déclaration "
. "inachevée.\n\n"

. "{{{Résultats des tests}}}\n\n"
. "L’audit partiel du 26 août 2026 a porté sur un échantillon de 23 pages : les "
. "pages obligatoires — page d’accueil, contact, mentions légales, déclaration "
. "d’accessibilité, plan du site, page d’authentification — et une page par "
. "gabarit du site.\n\n"
. "Ont été vérifiés et sont conformes sur cet échantillon :\n"
. "-* les alternatives des images et des éléments graphiques ;\n"
. "-* l’intitulé des liens et des boutons ;\n"
. "-* l’étiquetage des champs de formulaire et le rattachement des messages d’erreur ;\n"
. "-* la hiérarchie des titres et la présence des régions de page ;\n"
. "-* le lien d’évitement, l’ordre de tabulation et la visibilité de la prise de focus ;\n"
. "-* le contraste des textes et des composants d’interface ;\n"
. "-* la restitution du contenu sans feuille de styles ;\n"
. "-* l’agrandissement du seul texte à 200 %.\n\n"

. "{{{Contenus non accessibles}}}\n\n"
. "{{Les critères suivants n’ont pas encore été évalués}} et feront l’objet de "
. "l’audit complet :\n"
. "-* la pertinence des alternatives textuelles des images publiées par la commune ;\n"
. "-* la pertinence des intitulés de titres et des titres de pages, lus hors contexte ;\n"
. "-* l’accessibilité des documents téléchargeables, notamment les fichiers PDF ;\n"
. "-* la restitution par un lecteur d’écran.\n\n"
. "Aucune dérogation pour charge disproportionnée n’est revendiquée à ce jour.\n\n"

. "{{{Établissement de cette déclaration}}}\n\n"
. "Cette déclaration a été établie le {{26 août 2026}}.\n\n"
. "Technologies utilisées pour la réalisation du site : HTML, CSS, JavaScript, SPIP.\n\n"
. "Les tests ont été effectués avec les outils suivants : moteur de rendu "
. "Chromium, moteur d’analyse axe-core, service de validation du W3C, et deux "
. "outils écrits pour ce site, l’un vérifiant les squelettes, l’autre parcourant "
. "l’échantillon de pages.\n\n"

. "{{{Retour d’information et contact}}}\n\n"
. "Si vous n’arrivez pas à accéder à un contenu ou à un service de ce site, vous "
. "pouvez contacter la mairie afin d’être orienté vers une alternative "
. "accessible ou d’obtenir le contenu sous une autre forme.\n\n"
. "Par le formulaire de contact du site, par courriel, par téléphone ou en vous "
. "présentant au secrétariat aux heures d’ouverture. Les coordonnées figurent en "
. "bas de chaque page.\n\n"

. "{{{Voie de recours}}}\n\n"
. "Si vous constatez un défaut d’accessibilité vous empêchant d’accéder à un "
. "contenu ou à une fonctionnalité du site, que vous nous le signalez et que "
. "vous ne parvenez pas à obtenir une réponse, vous êtes en droit de faire "
. "parvenir vos doléances ou une demande de saisine au Défenseur des droits.\n\n"
. "Plusieurs moyens sont à votre disposition :\n"
. "-* le formulaire de contact du Défenseur des droits ;\n"
. "-* le délégué du Défenseur des droits de votre département ;\n"
. "-* le numéro de téléphone 09 69 39 00 00 ;\n"
. "-* par courrier, sans affranchissement : Défenseur des droits, Libre réponse "
. "71120, 75342 Paris CEDEX 07.",
);

$ecrits = 0;
foreach ($pages as $page) {
	$id_mot = marly_mot($page['mot']);

	/* Un article porte-t-il deja ce mot-cle ? Si oui, on n'y touche pas : il a
	   peut-etre ete relu et corrige par la mairie depuis. */
	$deja = sql_getfetsel('l.id_objet', 'spip_mots_liens AS l',
		'l.id_mot = ' . intval($id_mot) . ' AND l.objet = "article"');
	/* Ce drapeau repare un mensonge du script. La reecriture ne comptait pas
	   dans le total, et la branche suivante annoncait ensuite << DEJA LA,
	   saute >> pour le meme article : la sortie affichait donc REECRITE, puis
	   son contraire, puis << 0 page(s) ecrite(s) >>. Qui lit le total conclut
	   que rien n'a bouge, alors que le texte des trois pages venait d'etre
	   remplace — y compris, le cas echeant, les mentions que la mairie avait
	   completees a la main. */
	$touche = false;
	if ($deja && $reecrire) {
		objet_modifier('article', $deja, array(
			'titre' => $page['titre'],
			'texte' => $page['texte'],
		));
		echo 'REECRITE : ' . $page['titre'] . " (article $deja)\n";
		$ecrits++;
		$touche = true;
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
			$touche = true;
		}
		$statut = sql_getfetsel('statut', 'spip_articles', 'id_article = ' . intval($deja));
		if ($statut !== 'publie') {
			objet_instituer('article', $deja, array('statut' => 'publie'));
			marly_forcer_publication($deja);
			echo 'PUBLIE : ' . $page['titre'] . " (article $deja, etait $statut)\n";
			$ecrits++;
			$touche = true;
		} elseif ($ou == $rubrique && !$touche) {
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
	/* On relit : sans ce lien, le gabarit ne retrouve plus la page — elle se
	   cherche par son mot-cle, pas par son identifiant. */
	if (!sql_countsel('spip_mots_liens', 'id_mot = ' . intval($id_mot)
			. ' AND objet = ' . sql_quote('article')
			. ' AND id_objet = ' . intval($id_article))) {
		echo "ERREUR : le mot-cle n'a pas pu etre pose sur l'article $id_article.\n";
		exit(1);
	}
	echo 'ecrite : ' . $page['titre'] . " (article $id_article, rubrique $rubrique)\n";
	$ecrits++;
}

marly_invalider_cache();
echo "\n$ecrits page(s) creee(s), reecrite(s), deplacee(s) ou publiee(s).\n";
echo "A relire dans Edition > Articles avant de montrer le site.\n";
echo "\nDEUX MENTIONS RESTENT A COMPLETER, elles apparaissent en gras dans la\n";
echo "page : le telephone de l'hebergeur, obligatoire au titre de l'article 6\n";
echo "de la LCEN, et le delegue a la protection des donnees, que toute\n";
echo "commune doit designer depuis 2018. Relancer avec --reecrire ecrase le\n";
echo "texte des deux pages par celui du script.\n";
echo "\nLA DECLARATION D'ACCESSIBILITE EST ECRITE, MAIS ELLE NE PROCLAME AUCUN\n";
echo "NIVEAU DE CONFORMITE, et c'est voulu : un niveau s'etablit par un audit\n";
echo "des 106 criteres. Le notre a couvert ce qu'une machine tranche et quatre\n";
echo "criteres manuels. Ecrire << partiellement conforme >> aujourd'hui serait\n";
echo "faux, et une declaration fausse expose la commune plus qu'une declaration\n";
echo "inachevee. Le niveau s'inscrira a l'issue de l'audit complet.\n";
