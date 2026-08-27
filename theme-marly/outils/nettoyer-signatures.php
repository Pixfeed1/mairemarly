<?php
/**
 * Les lignes de signature de l'ancien site, restees dans le texte des articles.
 * ---------------------------------------------------------------------------
 *   php theme-marly/outils/nettoyer-signatures.php /chemin/racine-web
 *   php theme-marly/outils/nettoyer-signatures.php /chemin/racine-web --ecrire
 *
 * L'ancien site imprimait sous chaque titre une ligne « Le 5 août 2021, par
 * severine ». L'import a repris le corps des articles tel quel, et devait
 * l'enlever — mais son motif exige que la signature occupe un paragraphe à
 * elle seule. Quand elle partage le paragraphe avec la première phrase, elle
 * survit, et le prénom de la secrétaire de mairie s'affiche dans le résumé de
 * la page d'accueil.
 *
 * SANS --ecrire, LE SCRIPT NE TOUCHE A RIEN. Il montre, article par article,
 * ce qu'il retirerait et ce qui resterait. On lit d'abord, on écrit ensuite.
 *
 * Le motif est volontairement étroit : une date en toutes lettres, une virgule,
 * « par », un nom, une virgule ou un point. Il ne retire jamais un « par »
 * isolé au milieu d'une phrase — « distribué par la mairie » ne bouge pas.
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
include_spip('action/editer_objet');

/* SANS CETTE SESSION, objet_modifier() est refuse EN SILENCE : le script
   annoncerait des articles nettoyes qui ne le sont pas. C'est la regle 66 du
   verificateur, ecrite apres que six fiches d'associations soient restees en
   brouillon alors que l'import disait les avoir publiees. */
$GLOBALS['visiteur_session'] = array(
	'id_auteur' => 1,
	'statut'    => '0minirezo',
	'webmestre' => 'oui',
	'nom'       => 'nettoyage CLI',
);

$ecrire = in_array('--ecrire', $argv, true);

/* DEUX MOTIFS, ESSAYES DANS CET ORDRE, et l'ordre compte.

   Le premier prend la signature quand elle occupe un paragraphe entier, en
   emportant SES DEUX balises. Sans lui, le second retirait le texte et
   laissait le </p> orphelin derriere — mesure faite avant d'ecrire une ligne
   en base, sur sept cas types.

   Le second prend la signature collee a la premiere phrase, le cas que
   l'import avait laisse passer.

   Les deux sont ancres en DEBUT de champ : une signature au milieu d'un
   article n'en est pas une. Et « par » doit suivre une date en toutes
   lettres, donc « distribue par la mairie » ne bouge jamais. */
$jour  = '(?:1er|\d{1,2})';
/* LES MOIS S'ECRIVENT AUSSI EN ENTITES HTML. L'ancien site servait
   << ao&ucirc;t >> et << d&eacute;cembre >> ; l'import a repris le corps tel
   quel, entites comprises. Un motif ecrit avec les seuls accents ne trouve
   alors RIEN, et le script conclut a tort qu'il n'y a rien a nettoyer.
   Chaque mois accentue est donc donne dans ses deux formes. */
$mois  = '(?:janvier|f(?:é|&eacute;)vrier|mars|avril|mai|juin|juillet'
	. '|ao(?:û|&ucirc;)t|septembre|octobre|novembre|d(?:é|&eacute;)cembre)';
$date  = 'Le\s+' . $jour . '\s+' . $mois . '\s+\d{4}';
/* LE NOM EST DANS UN LIEN, et c'est ce qui a fait echouer les deux premieres
   versions. L'ancien site ecrivait :

       <p>Le 20 d&eacute;cembre 2023, par <a href="spip.php?auteur8">severine</a>,</p>

   Le motif interdisait le caractere < dans le nom — precaution raisonnable
   pour ne pas deborder sur le texte suivant — et ne pouvait donc jamais
   reconnaitre un nom entoure d'une balise. Dix-sept articles portaient la
   signature, le script en trouvait zero, et il l'annoncait sans reserve.

   Le lien est donc admis, mais ENCADRE : une seule balise ouvrante, un nom
   sans chevron dedans, une seule fermante. Le motif ne peut pas s'emballer et
   avaler la suite de l'article. */
$nom = '(?:<a[^>]{0,120}>)?[^<,.\n]{1,40}(?:</a>)?';
$motifs = array(
	'#^\s*<p>\s*' . $date . '\s*,\s*par\s+' . $nom . '\s*[,.]?\s*</p>\s*#ui',
	'#^\s*' . $date . '\s*,\s*par\s+' . $nom . '\s*[,.]?\s*#ui',
);

/* TROIS CHAMPS, ET LE PREMIER EST CELUI QU'ON AVAIT OUBLIE. La premiere
   version ne lisait que texte et descriptif : elle n'a rien trouve, alors que
   la ligne s'affichait sur la page d'accueil. SPIP range le resume dans
   CHAPO, et c'est chapo que #INTRODUCTION sert en priorite. */
$champs_lus = array('chapo', 'texte', 'descriptif');
$res = sql_select('id_article, titre, ' . implode(', ', $champs_lus),
	'spip_articles', '', '', 'date DESC');
$vus = 0;
$touches = 0;

while ($a = sql_fetch($res)) {
	$champs = array();

	foreach ($champs_lus as $champ) {
		$avant = (string) $a[$champ];
		if ($avant === '') {
			continue;
		}
		$apres = $avant;
		foreach ($motifs as $m) {
			$essai = preg_replace($m, '', $apres, 1);
			if ($essai !== null && $essai !== $apres) {
				$apres = $essai;
				break;
			}
		}
		if ($apres !== null && $apres !== $avant) {
			$champs[$champ] = ltrim($apres);
			$vus++;
			printf("\n[%d] %s\n", $a['id_article'], $a['titre']);
			printf("    champ %s\n", $champ);
			printf("    retire : %s\n", trim(mb_substr($avant, 0, mb_strlen($avant) - mb_strlen($apres))));
			printf("    reste  : %s...\n", trim(mb_substr($champs[$champ], 0, 70)));
		}
	}

	if ($champs && $ecrire) {
		objet_modifier('article', $a['id_article'], $champs);
		/* On relit : une ecriture dont personne ne verifie le resultat ment
		   tot ou tard. Regle 68 du verificateur. */
		$relu = sql_getfetsel('texte', 'spip_articles', 'id_article = ' . intval($a['id_article']));
		if (isset($champs['texte']) && trim($relu) !== trim($champs['texte'])) {
			echo "    ECHEC : le texte n'a pas ete enregistre.\n";
		} else {
			$touches++;
		}
	}
}

echo "\n";
if (!$vus) {
	/* UN SCRIPT QUI NE TROUVE RIEN DOIT MONTRER CE QU'IL A REGARDE. Sinon on
	   ne sait pas s'il n'y a rien a nettoyer, ou si le motif cherche a cote —
	   et c'est arrive : la premiere version ignorait le champ chapo et
	   annoncait << rien a faire >> pendant que la signature s'affichait sur
	   la page d'accueil. */
	echo "Aucune ligne de signature trouvee.\n\n";
	echo "VOICI CE QUI A ETE LU, sur les cinq articles les plus recents.\n";
	echo "Si une signature apparait ci-dessous, c'est le motif qui est en cause.\n";
	$r2 = sql_select('id_article, titre, ' . implode(', ', $champs_lus),
		'spip_articles', 'statut = ' . sql_quote('publie'), '', 'date DESC', '0,5');
	while ($a = sql_fetch($r2)) {
		printf("\n[%d] %s\n", $a['id_article'], $a['titre']);
		foreach ($champs_lus as $champ) {
			$v = trim((string) $a[$champ]);
			printf("    %-11s %s\n", $champ . ' :',
				$v === '' ? '(vide)' : str_replace(array("\r", "\n"), ' ', mb_substr($v, 0, 110)));
		}
	}

	/* SONDE LARGE. Les cinq plus recents ne prouvent rien : ce sont les pages
	   legales, ecrites par nous. On cherche donc, sur TOUS les articles, le
	   mot << par >> dans les 120 premiers caracteres — sans exiger de date ni
	   de forme. Ce qui sort ici et que le motif strict n'a pas pris, c'est
	   exactement ce qu'il faut lui apprendre a reconnaitre. */
	echo "\nSONDE LARGE : le mot << par >> en tete de champ, tous articles.\n";
	$trouves = 0;
	$r3 = sql_select('id_article, titre, ' . implode(', ', $champs_lus),
		'spip_articles', '', '', 'date DESC');
	while ($a = sql_fetch($r3)) {
		foreach ($champs_lus as $champ) {
			$v = trim((string) $a[$champ]);
			$tete = mb_substr($v, 0, 120);
			if ($v !== '' && preg_match('#\bpar\b#ui', $tete)) {
				$trouves++;
				printf("\n  [%d] %s\n", $a['id_article'], $a['titre']);
				printf("      %s : %s\n", $champ, str_replace(array("\r", "\n"), ' ', $tete));
			}
		}
	}
	if (!$trouves) {
		echo "  Rien. Le mot << par >> n'apparait en tete d'aucun champ.\n";
		echo "  Il n'y a donc plus de signature dans le contenu.\n";
	}
	echo "\n";
} elseif ($ecrire) {
	echo "$touches article(s) nettoye(s).\n";
	include_spip('inc/invalideur');
	suivre_invalideur("id='id_article'");
	echo "Cache invalide. Recharger la page d'accueil.\n";
} else {
	echo "$vus champ(s) concerne(s). RIEN N'A ETE MODIFIE.\n";
	echo "Relancer avec --ecrire pour appliquer.\n";
}
