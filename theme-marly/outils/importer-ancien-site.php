<?php
/**
 * Importe les articles de l'ancien site (marlygomont.free.fr).
 * ---------------------------------------------------------------------------
 *   php theme-marly/outils/importer-ancien-site.php /racine-web           # essai
 *   php theme-marly/outils/importer-ancien-site.php /racine-web importer  # pour de vrai
 *
 * L'ESSAI est le mode par défaut : il lit tout, montre article par article ce
 * qu'il extraira (titre, date, rubrique de destination, pièces jointes), et
 * n'écrit RIEN. On regarde sa sortie, et seulement ensuite on lance
 * « importer ». C'est la règle des mesures avant les gestes.
 *
 * Pour travailler par tranches, un filtre sur la DESTINATION :
 *
 *   ... importer-ancien-site.php /racine-web cible="Comptes rendus"           # essai
 *   ... importer-ancien-site.php /racine-web importer cible="Comptes rendus"  # réel
 *
 * Seuls les articles dont la rubrique de destination contient le motif sont
 * traités ; les autres sont comptés puis ignorés, sans une ligne d'écran.
 * On importe une tranche, on la vérifie sur le site, on passe à la suivante.
 *
 * Choix assumés :
 *  - les articles gardent leur DATE D'ORIGINE : un compte rendu de 2017
 *    reste daté de 2017, c'est ce qui fait la profondeur du site ;
 *  - les rubriques de l'ancien site sont recréées, sauf trois cas mieux
 *    logés : les comptes rendus vont dans « Vie municipale > Comptes rendus
 *    du conseil », et les articles des associations dans la rubrique de LEUR
 *    association, celle que le site crée déjà pour elles ;
 *  - les images et PDF sont rapatriés dans IMG/ancien-site/ et les liens
 *    récrits : le jour où free.fr ferme, rien ne casse ;
 *  - REJOUABLE : un article déjà importé (même titre, même date) est sauté.
 *
 * Une requête par seconde vers l'ancien site : il est chez free, il est
 * vieux, on le ménage.
 */

error_reporting(E_ALL);
ini_set('display_errors', 'stderr');

if (PHP_SAPI !== 'cli') {
	die("Ce script ne s'utilise qu'en ligne de commande.\n");
}

$racine = isset($argv[1]) ? rtrim($argv[1], '/') : getcwd();
$importer = in_array('importer', $argv, true);
$cible_filtre = '';
foreach ($argv as $arg) {
	if (strpos($arg, 'cible=') === 0) {
		$cible_filtre = trim(substr($arg, 6), " \t\"'");
	}
}
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
include_spip('inc/distant');
include_spip('action/editer_objet');
include_spip('inc/marly_outils');

/* En ligne de commande, personne n'est connecte : SPIP refuse alors
   silencieusement le passage en << publie >> (autoriser('publierdans')
   echoue), et tout reste en << prepa >>, invisible. Le premier import l'a
   montre : 30 articles crees, 0 publie. On se presente donc en webmestre
   avant d'ecrire quoi que ce soit. */
$GLOBALS['visiteur_session'] = array(
	'id_auteur' => 1,
	'statut'    => '0minirezo',
	'webmestre' => 'oui',
	'nom'       => 'import CLI',
);

define('ANCIEN', 'http://marlygomont.free.fr');

/** Va chercher une page de l'ancien site, poliment. */
function ancien_page($url) {
	static $dernier = 0;
	$attente = 1 - (microtime(true) - $dernier);
	if ($attente > 0) {
		usleep((int) ($attente * 1000000));
	}
	$dernier = microtime(true);
	$r = recuperer_url($url, array('taille_max' => 4194304, 'transcoder' => true));
	return $r['page'] ?? '';
}

/**
 * La date de PUBLICATION : « Le 22 février 2018, par X » -> 2018-02-22.
 *
 * Ancrée sur la ligne de signature, et pas sur la première date croisée
 * dans la page : la « Commémoration de l'armistice du 8 mai 1945 » serait
 * sinon entrée en base datée de 1945, la date du titre. C'est l'essai qui
 * l'a montré, avant toute écriture.
 */
function ancien_date($texte) {
	/* L'ancien SPIP ecrit les mois accentues en ENTITES dans la ligne de
	   signature : << Le 5 ao&ucirc;t 2021, par ... >>. Sans decodage, la
	   forme signee ne matchait jamais pour fevrier, aout et decembre, et on
	   retombait sur la premiere date venue du corps — la date de la reunion
	   au lieu de celle de la publication. Trois mois sur douze : une
	   quinzaine d'articles dates de travers, vus en comparant page a page.

	   Et la balise <title> de l'ancien SPIP s'ecrit << TITRE, par AUTEUR -
	   Site du village >>. Quand le titre finit par une date, elle prend la
	   forme EXACTE d'une signature, et comme elle vient ligne 4 le motif
	   ancre la mordait bien avant la vraie signature du corps. Le h1 est
	   ecarte pour la meme raison : il porte le titre, donc parfois une
	   date, que le repli attraperait. On cherche une signature, elle n'est
	   ni dans l'entete du document ni dans son titre.

	   Mesure : article103 de l'ancien site. Ligne 4 << FETE DE L'ATTELAGE
	   LE 24 SEPTEMBRE 2017, par severine - Site du village >>, ligne 84
	   << Le 7 septembre 2017, par severine >>. L'import retenait le 24
	   septembre, date de la fete, au lieu du 7, date de publication. */
	$texte = preg_replace('#<title[^>]*>.*?</title>#is', ' ', $texte);
	$texte = preg_replace('#<h1[^>]*>.*?</h1>#is', ' ', $texte);
	$texte = html_entity_decode($texte, ENT_QUOTES | ENT_HTML5, 'UTF-8');
	$mois = array('janvier'=>1,'février'=>2,'fevrier'=>2,'mars'=>3,'avril'=>4,'mai'=>5,
	              'juin'=>6,'juillet'=>7,'août'=>8,'aout'=>8,'septembre'=>9,
	              'octobre'=>10,'novembre'=>11,'décembre'=>12,'decembre'=>12);
	$noms = implode('|', array_keys($mois));
	/* La forme signee d'abord ; a defaut seulement, une date quelconque. */
	if (preg_match('#Le\s+(1er|\d{1,2})\s+(' . $noms . ')\s+(\d{4})\s*,?\s*par#iu', $texte, $m)
	or preg_match('#(1er|\d{1,2})\s+(' . $noms . ')\s+(\d{4})#iu', $texte, $m)) {
		$jour = ($m[1] === '1er') ? 1 : intval($m[1]);
		return sprintf('%04d-%02d-%02d 12:00:00', $m[3], $mois[mb_strtolower($m[2])], $jour);
	}
	return '';
}

/**
 * Le corps d'un article de l'ancien site : le premier bloc
 * <div class="texte_principal">, decoupe en equilibrant les <div>,
 * debarrasse du h1 (le titre, deja pris) et de la ligne de signature
 * << Le 1er mai 2008, par Paul Gosset >> (date et auteur, deja pris).
 */
function ancien_corps($page) {
	$pos = stripos($page, 'class="texte_principal"');
	if ($pos === false) {
		return '';
	}
	$debut = strrpos(substr($page, 0, $pos), '<div');
	if ($debut === false) {
		return '';
	}
	$niveau = 0;
	$i = $debut;
	$fin = strlen($page);
	while (preg_match('#<div\b|</div>#i', $page, $m, PREG_OFFSET_CAPTURE, $i)) {
		$balise = $m[0][0];
		$i = $m[0][1] + strlen($balise);
		$niveau += (stripos($balise, '</') === 0) ? -1 : 1;
		if ($niveau === 0) {
			$fin = $i;
			break;
		}
	}
	$bloc = substr($page, $debut, $fin - $debut);
	$bloc = preg_replace('#^\s*<div[^>]*>#', '', $bloc);
	$bloc = preg_replace('#</div>\s*$#', '', $bloc);
	$bloc = preg_replace('#<h1[^>]*>.*?</h1>#s', '', $bloc, 1);
	$bloc = preg_replace('#<p[^>]*>\s*Le\s+(1er|\d{1,2})\s+\p{L}+\s+\d{4}\s*,?\s*par\b.*?</p>#su', '', $bloc, 1);
	return trim($bloc);
}

/** Rapatrie un fichier de l'ancien site dans IMG/ancien-site/ ; rend son
    nom local, ou '' si rien n'a pu etre recupere. */
function rapatrier_piece($u, $dossier_img) {
	$absolu = (strpos($u, 'http') === 0) ? $u : ANCIEN . '/' . ltrim($u, '/');
	$nomf = basename(parse_url($absolu, PHP_URL_PATH));
	if ($nomf === '') {
		return '';
	}
	if (!is_dir($dossier_img)) {
		@mkdir($dossier_img, 0755, true);
	}
	if (!file_exists($dossier_img . $nomf)) {
		$r = recuperer_url($absolu, array('taille_max' => 20971520, 'transcoder' => false));
		if (empty($r['page'])) {
			return '';
		}
		file_put_contents($dossier_img . $nomf, $r['page']);
	}
	return $nomf;
}

/**
 * Attache a l'article, comme vrais documents SPIP, les fichiers rapatries.
 * Le premier import les deposait dans IMG/ancien-site/ sans les JOINDRE :
 * les cartes << Telecharger >> du gabarit, qui lisent les DOCUMENTS de
 * l'article, restaient vides — le PDF de l'ancien site vivait dans la
 * marge de l'article, pas dans son texte.
 *
 * L'association ne passe PAS par le champ << parents >> de medias : sur
 * trente appels identiques, il n'a relie qu'un document, cause jamais
 * elucidee. On associe nous-memes (objet_associer, qui ne cree jamais de
 * doublon), et un document deja cree mais relie a rien — les rates du
 * passage precedent — est ADOPTE plutot que recree. Rejouable.
 */
function attacher_pieces($id_article, $pieces, $dossier_img) {
	/* La fonction vit dans le plugin medias (action/ajouter_documents.php)
	   sous le nom action_ajouter_un_document_dist : elle se charge par
	   charger_fonction, pas par un appel direct — mesure sur le serveur. */
	static $ajouter = null;
	if ($ajouter === null) {
		include_spip('action/ajouter_documents');
		include_spip('action/editer_liens');
		include_spip('inc/charsets');
		$ajouter = charger_fonction('ajouter_un_document', 'action', true);
		if (!$ajouter) {
			fwrite(STDERR, "Le plugin medias ne repond pas : pieces non attachees.\n");
			$ajouter = false;
		}
	}
	if (!$ajouter) {
		return 0;
	}
	$faits = 0;
	foreach ($pieces as $u) {
		$nomf = rapatrier_piece($u, $dossier_img);
		if ($nomf === '') {
			continue;
		}

		/* Retrouver les documents deja en base pour ce fichier : meme
		   normalisation du nom que medias (minuscules, sans accents), et
		   la base du nom sans extension pour attraper aussi les copies
		   suffixees -2 nees des collisions. Les jokers de LIKE ( % _ )
		   sont echappes : nos noms de fichiers sont pleins de _ . */
		$propre = strtolower(translitteration($nomf));
		$base = preg_replace('#\.[^.]+$#', '', $propre);
		$motif = '%' . str_replace(array('\\', '%', '_'), array('\\\\', '\\%', '\\_'), $base) . '%';

		$lie_ici = 0;
		$orphelin = 0;
		foreach (sql_allfetsel('id_document', 'spip_documents',
				'fichier LIKE ' . sql_quote($motif)) as $doc) {
			$id_doc = intval($doc['id_document']);
			$liens = sql_allfetsel('objet, id_objet', 'spip_documents_liens',
				'id_document = ' . $id_doc);
			$ici = false;
			foreach ($liens as $l) {
				if ($l['objet'] === 'article' and intval($l['id_objet']) === intval($id_article)) {
					$ici = true;
				}
			}
			if ($ici) {
				$lie_ici = $id_doc;
			} elseif (!$liens and !$orphelin) {
				$orphelin = $id_doc;
			}
		}

		if ($lie_ici) {
			/* Deja attache a cet article : juste s'assurer qu'il est visible. */
			sql_updateq('spip_documents', array('statut' => 'publie'),
				"id_document = $lie_ici AND statut <> 'publie'");
			continue;
		}

		$id_doc = $orphelin;
		if (!$id_doc) {
			/* Rien en base : creation par medias. La fonction DEPLACE le
			   fichier qu'on lui donne : on lui tend une copie, l'original
			   reste dans IMG/ancien-site/ pour les liens des textes. */
			$copie = $dossier_img . 'copie-' . $nomf;
			if (!copy($dossier_img . $nomf, $copie)) {
				continue;
			}
			$mode = preg_match('#\.(jpe?g|png|gif|webp)$#i', $nomf) ? 'image' : 'document';
			$id_doc = $ajouter('new', array(
				'name'     => $nomf,
				'tmp_name' => $copie,
				'titre'    => '',
				'distant'  => false,
			), 'article', $id_article, $mode);
			if (file_exists($copie)) {
				@unlink($copie);
			}
			if (!is_numeric($id_doc) or $id_doc <= 0) {
				fwrite(STDERR, "  piece $nomf : $id_doc\n");
				continue;
			}
		}

		objet_associer(array('document' => intval($id_doc)), array('article' => intval($id_article)));
		sql_updateq('spip_documents', array('statut' => 'publie'),
			'id_document = ' . intval($id_doc));
		$faits++;
	}
	return $faits;
}

/**
 * Relie la signature d'origine a l'article, et veille au statut de la fiche.
 *
 * Un auteur SPIP peut exister sans aucun compte de connexion : juste un nom,
 * relie a ses articles — c'est ce qu'on avait convenu. MAIS un auteur cree
 * par l'API nait en << 5poubelle >>, que les boucles AUTEURS excluent : la
 * signature n'apparaitrait jamais (Severine, premiere fournee). On pose donc
 * redactrice (1comite), toujours sans login : une signature, pas un acces.
 * Rejouable : fiche, statut et lien ne sont refaits que s'ils manquent.
 */
function signer_article($id_article, $auteur) {
	if ($auteur === '') {
		return array();
	}
	$gestes = array();
	$fiche = sql_fetsel('id_auteur, statut', 'spip_auteurs', 'nom = ' . sql_quote($auteur));
	$id_auteur = $fiche ? intval($fiche['id_auteur']) : 0;
	if (!$id_auteur) {
		$id_auteur = objet_inserer('auteur');
		if (!$id_auteur) {
			return array();
		}
		objet_modifier('auteur', $id_auteur, array('nom' => $auteur, 'statut' => '1comite'));
		$gestes[] = 'auteur cree';
	} elseif ($fiche['statut'] === '5poubelle') {
		sql_updateq('spip_auteurs', array('statut' => '1comite'),
			'id_auteur = ' . $id_auteur);
		$gestes[] = 'signature reparee';
	}
	if (!sql_countsel('spip_auteurs_liens', 'id_auteur = ' . $id_auteur
			. " AND objet = 'article' AND id_objet = " . intval($id_article))) {
		sql_insertq('spip_auteurs_liens', array(
			'id_auteur' => $id_auteur,
			'objet'     => 'article',
			'id_objet'  => intval($id_article),
			'vu'        => 'non',
		));
		/* On relit : le compte rendu ne doit annoncer que ce qui est
		   reellement en base. */
		$gestes[] = sql_countsel('spip_auteurs_liens', 'id_auteur = ' . $id_auteur
			. " AND objet = 'article' AND id_objet = " . intval($id_article))
			? 'signature liee' : 'ECHEC du lien de signature';
	}
	return $gestes;
}

/** Trouve (ou cree, en mode importer) une rubrique par son chemin. */
function rubrique_chemin($chemin, $importer) {
	$id_parent = 0;
	foreach ($chemin as $titre) {
		$id = sql_getfetsel('id_rubrique', 'spip_rubriques',
			'titre = ' . sql_quote($titre) . ' AND id_parent = ' . intval($id_parent));
		if (!$id) {
			if (!$importer) {
				return 0;
			}
			$id = objet_inserer('rubrique', $id_parent);
			if (!$id) {
				return 0;
			}
			objet_modifier('rubrique', $id, array('titre' => $titre, 'statut' => 'publie'));
		}
		$id_parent = $id;
	}
	return $id_parent;
}

/** La rubrique d'une association, retrouvee par sa fiche. */
function rubrique_association($morceau) {
	return intval(sql_getfetsel('id_rubrique', 'spip_associations',
		'nom LIKE ' . sql_quote('%' . $morceau . '%') . ' AND id_rubrique > 0'));
}

/* ------------------------------------------------------------------ le plan */
echo "Lecture du plan de l'ancien site...\n";
$plan = ancien_page(ANCIEN . '/spip.php?page=plan');
if (!$plan) {
	fwrite(STDERR, "Le plan ne repond pas. L'ancien site est-il joignable depuis ce serveur ?\n");
	exit(1);
}

/* La carte article -> rubrique d'origine, construite depuis le PLAN : il
   liste chaque article sous sa rubrique. C'est la source fiable — la
   detection page par page echouait en silence, et l'essai l'a montre :
   tout partait au fourre-tout, y compris les articles des associations. */
$noms_rubriques = array(
	'ça se passe en thiérache', 'Comité d’animation', 'Commerces et Services',
	'Du coté des associations de Marly', 'SECTEUR PAROISSIAL DE MARLY GOMONT',
	'Evènements', 'Forums des artisans et des produits du terroir',
	'Mairie', 'Marly en Images',
);
$origines = array();
$section = '';
foreach (preg_split('#(spip\.php\?article\d+)#', $plan, -1, PREG_SPLIT_DELIM_CAPTURE) as $morceau) {
	if (preg_match('#spip\.php\?article(\d+)#', $morceau, $m)) {
		$origines[intval($m[1])] = $section;
		continue;
	}
	$texte_brut = strip_tags($morceau);
	unset($pos_section);
	foreach ($noms_rubriques as $nom) {
		/* si plusieurs noms figurent dans le morceau, garder le DERNIER
		   nomme avant les articles qui suivent */
		$pos = mb_strripos($texte_brut, $nom);
		if ($pos !== false and (!isset($pos_section) or $pos > $pos_section)) {
			$section = $nom;
			$pos_section = $pos;
		}
	}
}
$ids = array_keys($origines);
sort($ids);
echo count($ids) . " articles reperes dans le plan.\n\n";

$dossier_img = $racine . '/IMG/ancien-site/';

$total = $faits = $ignores = 0;
if ($cible_filtre !== '') {
	echo "Filtre : seulement les articles dont la destination contient « $cible_filtre ».\n\n";
}
foreach ($ids as $id) {
	$page = ancien_page(ANCIEN . "/spip.php?article$id");
	if (!$page) {
		echo "article$id : INJOIGNABLE\n";
		continue;
	}

	/* Le titre, et le sous-titre que l'ancien theme imbrique DANS le h1
	   (d'ou les << Proces verbal >> colles aux titres du premier essai). */
	$titre = $soustitre = '';
	if (preg_match('#<h1[^>]*>(.*?)</h1>#s', $page, $m)
	or preg_match('#<title>(.*?)</title>#s', $page, $m)) {
		$brut = $m[1];
		if (preg_match('#<(span|small|em|i|div)[^>]*>(.*?)</\1>#s', $brut, $ss)) {
			$soustitre = trim(preg_replace('#\s+#', ' ', strip_tags($ss[2])));
			$brut = str_replace($ss[0], ' ', $brut);
		}
		$titre = trim(preg_replace('#\s+#', ' ', strip_tags($brut)));
		$titre = preg_replace('#\s*[-|].{0,40}(Marly[- ]Gomont|village).*$#i', '', $titre);
		/* L'ancien site est inconstant sur la casse (<< réunion de conseil
		   du 29 mai 2018 >>) : la premiere lettre passe en majuscule, le
		   reste du titre ne bouge pas. */
		$titre = mb_strtoupper(mb_substr($titre, 0, 1, 'UTF-8'), 'UTF-8')
			. mb_substr($titre, 1, null, 'UTF-8');
	}
	/* Les entites HTML fuient de l'ancien theme dans les titres
	   (<< le 23 Août 2014&nbsp;: >>, vu a l'essai des associations) :
	   decodees, et l'espace insecable redevient une espace simple. */
	foreach (array('titre', 'soustitre') as $champ) {
		$$champ = html_entity_decode($$champ, ENT_QUOTES | ENT_HTML5, 'UTF-8');
		$$champ = trim(preg_replace('#\s+#u', ' ', str_replace("\u{00A0}", ' ', $$champ)));
	}

	/* Quelques titres de l'ancien site ne peuvent pas etre repris tels
	   quels : << Nouvel article >> pour le texte fondateur de la paroisse.
	   Retitres AVANT le garde-fou anti-doublon, pour que les relances
	   retrouvent l'article sous son nouveau nom. */
	$retitres = array(
		26 => 'La paroisse Notre-Dame de la Salette',
	);
	if (isset($retitres[$id])) {
		$titre = $retitres[$id];
	}

	/* Les articles ecartes volontairement : les coquilles vides, jugees
	   liste en main a l'essai (le compte de mots aide), JAMAIS par un
	   seuil automatique. Montres avec la mention ECARTE, jamais ecrits. */
	$ecartes = array(
		// exemple : 42 => 'deux lignes, sans piece jointe, sans information',
	);
	if ($titre === '') {
		echo "article$id : SANS TITRE, saute\n";
		continue;
	}

	/* La date, l'auteur et la rubrique d'origine :
	   « Le 22 février 2018, par X, dans Y ». */
	$date = ancien_date($page);
	/* Les dates posees a la main, pour les pages qui n'en donnent AUCUNE.
	   A DEFAUT seulement, jamais par-dessus : une valeur en dur qui ecrase
	   l'extraction survit en silence aux corrections de l'extracteur, et
	   masque la vraie date pour toujours.

	   C'est exactement ce qui est arrive au 91 (<< Ouverture special
	   Noel >>). Sa page signe << Le 15 d&eacute;cembre 2016, par ... >>,
	   mais tant que les entites n'etaient pas decodees l'extracteur ne
	   voyait rien, et je l'avais date du 1er decembre d'apres sa place
	   dans le plan. Les entites decodees, la page a repris la main : la
	   date en dur n'avait plus lieu d'etre, et la table est vide.

	   Le jour ou une page n'en donne vraiment aucune, remettre une entree
	   ici : elle ne servira que dans ce cas-la, et le script annoncera de
	   lui-meme qu'il l'ecarte si la page finit par parler. */
	$redates = array();
	if (isset($redates[$id])) {
		if ($date === '') {
			$date = $redates[$id];
		} elseif ($date !== $redates[$id]) {
			echo "article$id : date posee a la main (" . $redates[$id]
				. ") ecartee, la page signe $date\n";
		}
	}
	$auteur = '';
	if (preg_match('#par\s+<a[^>]*auteur[^>]*>(.*?)</a>#si', $page, $m)
	or preg_match('#,\s*par\s+([^,<]{2,60}),\s*dans#u', $page, $m)) {
		$auteur = trim(strip_tags($m[1]));
		/* Nom et prenom, proprement : « severine » devient « Séverine ».
		   Le nom de famille, lui, ne s'invente pas — quand la mairie nous
		   le donnera, une seule correction de la fiche auteur signera d'un
		   coup ses 45 articles. */
		$propres = array(
			'severine' => 'Séverine',
			'secretaire de mairie' => 'Secrétariat de mairie',
		);
		$cle = mb_strtolower($auteur);
		$auteur = $propres[$cle] ?? mb_convert_case($auteur, MB_CASE_TITLE, 'UTF-8');
	}
	$rubrique_origine = $origines[$id] ?? '';

	/* Le corps : le PREMIER bloc <div class="texte_principal"> du squelette
	   d'epoque — il contient le h1 et la ligne de signature, qu'on retire.
	   Les six autres blocs de meme classe sont les gadgets de la colonne
	   (<< Dans la meme rubrique >>, << Votre recherche >>...), a ignorer.
	   Mesure : les motifs naifs d'avant (\btexte\b, id="texte") n'ont
	   JAMAIS rien attrape — 30 comptes rendus importes au corps vide, vus
	   par le compte de mots de l'essai. Les divs s'emboitent, donc on
	   decoupe en EQUILIBRANT les balises, pas au premier </div> venu. */
	$texte = ancien_corps($page);

	/* Les fichiers du site d'epoque : images et documents sous IMG/. */
	$pieces = array();
	preg_match_all(',(?:href|src)="([^"]*IMG/[^"]+)",i', $page, $mm);
	foreach (array_unique($mm[1]) as $u) {
		$pieces[] = $u;
	}

	/* La destination. On ne fait que la RESOUDRE ici : la creation d'une
	   rubrique manquante attend d'avoir passe le filtre cible=, sinon le
	   mode reel filtre creerait les rubriques des articles qu'il ignore. */
	$cible_txt = '';
	$id_rubrique = 0;
	$chemin_a_creer = null;

	/* Le reroutage a la main, prioritaire sur tout : l'ancien site classait
	   parfois n'importe comment. Apres lecture de l'essai complet, on
	   corrige ici, article par article, le chemin de destination ; les
	   rubriques du chemin sont creees a l'import si elles manquent. */
	$reroutes = array(
		/* L'ancien site classait ces articles d'associations en
		   << Evenements >> : ils rejoignent la rubrique de leur asso. */
		39  => array('Vie associative', 'AS Marly-Gomont'),
		31  => array('Vie associative', 'Harmonie Municipale de Marly-Gomont'),
		50  => array('Vie associative', 'Harmonie Municipale de Marly-Gomont'),
		52  => array('Vie associative', 'Harmonie Municipale de Marly-Gomont'),
		/* Le bulletin municipal n'est pas un souvenir : il vit sous la
		   rubrique de la vie municipale, avec les comptes rendus. La
		   rubrique porte le nom d'usage des mairies ; << La voix du
		   village >> est le titre du journal, il reste dans les articles. */
		89  => array('Vie municipale', 'Le bulletin municipal'),
		116 => array('Vie municipale', 'Le bulletin municipal'),
	);
	if (isset($reroutes[$id])) {
		$chemin = $reroutes[$id];
		$cible_txt = implode(' > ', $chemin) . ' [reroute]';
		$id_rubrique = rubrique_chemin($chemin, false);
		$chemin_a_creer = $chemin;
	} elseif (preg_match('#^(r[ée]union\s+(de\s+)?(du\s+)?conseil|[ée]lection du maire)#iu', $titre)
	or ($rubrique_origine === 'Mairie' and stripos($titre, 'conseil') !== false)) {
		$chemin = array('Vie municipale', 'Comptes rendus du conseil');
		$cible_txt = implode(' > ', $chemin);
		$id_rubrique = rubrique_chemin($chemin, false);
		$chemin_a_creer = $chemin;
	} elseif ($rubrique_origine === 'Du coté des associations de Marly'
	or $rubrique_origine === 'SECTEUR PAROISSIAL DE MARLY GOMONT') {
		/* Deux articles que les mots-cles ne peuvent pas trouver : le texte
		   fondateur de la paroisse s'appelle << Nouvel article >>, et la
		   naissance du TTMG ne nomme le club que dans son corps. Assignes
		   par leur numero, verifie a l'essai. */
		$assignes = array(26 => 'Paroisse', 83 => 'TTMG');
		if (isset($assignes[$id])) {
			$id_rubrique = rubrique_association($assignes[$id]);
			$cible_txt = $id_rubrique
				? "rubrique de l'association " . $assignes[$id]
				: 'RUBRIQUE ' . $assignes[$id] . ' INTROUVABLE EN BASE';
		}
		if (!$id_rubrique)
		foreach (array('ASMG' => 'AS Marly', 'TTMG' => 'TTMG', 'armonie' => 'Harmonie',
		               'PAROISS' => 'Paroisse', 'SECTEUR' => 'Paroisse') as $indice => $morceau) {
			if (stripos($titre . ' ' . $rubrique_origine, $indice) !== false) {
				$id_rubrique = rubrique_association($morceau);
				$cible_txt = 'rubrique de l\'association ' . $morceau;
				break;
			}
		}
		if (!$id_rubrique) {
			$chemin = array('Vie associative');
			$cible_txt = 'Vie associative';
			$id_rubrique = rubrique_chemin($chemin, false);
			$chemin_a_creer = $chemin;
		}
	} elseif (mb_stripos($rubrique_origine, 'animation') !== false) {
		$id_rubrique = rubrique_association('animation');
		$cible_txt = 'rubrique du Comité d\'animation';
	}
	if (!$id_rubrique and !$cible_txt) {
		/* Le reste alimente les deux rubriques d'actualite, vivantes :
		   << Événements >> pour ce qu'on raconte (fetes, forums,
		   commemorations, photos), << Infos >> pour ce qu'on annonce
		   (avis, arretes, alertes, fermetures). Les anciens articles y
		   coulent au fond, le gabarit rubrique regroupant par annee ;
		   l'agenda, lui, reste le calendrier pratique des dates a venir. */
		$evenementiel = array('Evènements', 'Forums des artisans et des produits du terroir',
			'ça se passe en thiérache', 'Marly en Images');
		$chemin = in_array($rubrique_origine, $evenementiel, true)
			? array('Événements')
			: array('Infos');
		$cible_txt = implode(' > ', $chemin);
		$id_rubrique = rubrique_chemin($chemin, false);
		$chemin_a_creer = $chemin;
	}

	/* Le filtre cible= : hors tranche, on compte et on passe. */
	if ($cible_filtre !== '' and mb_stripos($cible_txt, $cible_filtre) === false) {
		$ignores++;
		continue;
	}

	/* Le filtre est passe : en mode reel, la rubrique manquante se cree. */
	if (!$id_rubrique and $chemin_a_creer and $importer) {
		$id_rubrique = rubrique_chemin($chemin_a_creer, true);
	}

	/* Rapatrier les fichiers et recrire leurs liens dans le texte, pour la
	   creation comme pour la reparation. L'essai ne telecharge rien. */
	if ($importer) {
		foreach ($pieces as $u) {
			$nomf = rapatrier_piece($u, $dossier_img);
			if ($nomf !== '') {
				$texte = str_replace($u, 'IMG/ancien-site/' . $nomf, $texte);
			}
		}
	}

	$total++;
	$ecarte = $ecartes[$id] ?? '';
	$deja = null;
	if (!$ecarte) {
	/* Comparaison EXACTE (BINARY) : MySQL ignore la casse, et l'ancien site
	   a << La voix du village >> ET << LA VOIX DU VILLAGE >> — deux articles
	   distincts que le garde-fou prenait l'un pour l'autre, date au ping-pong. */
	$deja = sql_fetsel('id_article, statut, date, texte', 'spip_articles', 'titre = BINARY ' . sql_quote($titre)
		. ($date ? ' AND date = ' . sql_quote($date) : ''));
	if (!$deja and $id_rubrique) {
		/* SPIP retimbre la date a la publication (<< maintenant >>, sauf
		   date future) : un article deja importe peut donc porter la
		   mauvaise date et echapper au couple titre+date. On le retrouve
		   par titre et rubrique, et on lui rend sa date plus bas. */
		$deja = sql_fetsel('id_article, statut, date, texte', 'spip_articles', 'titre = BINARY ' . sql_quote($titre)
			. ' AND id_rubrique = ' . intval($id_rubrique));
	}

	/* Un article deja la mais abime (reste en prepa, ou date retimbree
	   par la publication) est repare au passage : le sauter le laisserait
	   invisible, ou date d'aujourd'hui pour toujours. La date se rend
	   APRES la republication, puisque c'est elle qui la retimbre. */
	}
	$mention = $ecarte !== '' ? ' ECARTE : ' . $ecarte : '';
	if ($deja) {
		$mention = ' DEJA LA, saute';
		if ($importer) {
			$gestes = array();
			/* Le corps d'abord : les 30 comptes rendus du premier passage
			   sont vides (l'extraction ne trouvait rien). On ne rend le
			   texte qu'a un article VIDE : jamais par-dessus une version
			   que la mairie aurait retouchee. */
			if ($texte !== '' and trim((string) $deja['texte']) === '') {
				objet_modifier('article', $deja['id_article'], array('texte' => $texte));
				$gestes[] = 'texte rendu';
			}
			if ($deja['statut'] !== 'publie') {
				sql_updateq('spip_articles', array('statut' => 'publie'),
					'id_article = ' . intval($deja['id_article']));
				$gestes[] = 'republie';
			}
			/* Apres une republication, la date se rend TOUJOURS : SPIP vient
			   de la retimbrer, comparer la date lue avant ne dit plus rien. */
			if ($date and ($gestes or $deja['date'] !== $date)) {
				sql_updateq('spip_articles', array('date' => $date),
					'id_article = ' . intval($deja['id_article']));
				$gestes[] = 'date rendue';
			}
			if ($pieces and ($n = attacher_pieces($deja['id_article'], $pieces, $dossier_img))) {
				$gestes[] = $n . ' piece(s) attachee(s)';
			}
			$gestes = array_merge($gestes, signer_article($deja['id_article'], $auteur));
			if ($gestes) {
				$mention = ' DEJA LA, ' . implode(' et ', $gestes);
			}
		}
	}

	/* La taille du texte, pour juger les coquilles vides liste en main :
	   deux lignes avec un PDF joint valent de l'or, deux lignes sans rien
	   sont du bruit. Ca se decide a l'oeil, pas au seuil automatique. */
	$mots = preg_split('#\s+#u', trim(strip_tags($texte)), -1, PREG_SPLIT_NO_EMPTY);
	$mots = count($mots);

	printf("article%-4d %-46s %s %-18s [%s] %4d mots -> %s%s%s\n", $id,
		mb_substr($titre . ($soustitre !== '' ? ' // ' . $soustitre : ''), 0, 46),
		$date ? substr($date, 0, 10) : 'SANS DATE ',
		$auteur !== '' ? 'par ' . mb_substr($auteur, 0, 14) : 'SANS AUTEUR',
		$rubrique_origine !== '' ? mb_substr($rubrique_origine, 0, 22) : 'origine ?',
		$mots,
		$cible_txt,
		$pieces ? ' [' . count($pieces) . ' fichier(s)]' : '',
		$mention);

	if (!$importer or $deja or $ecarte !== '') {
		continue;
	}

	/* Les fichiers sont deja rapatries et les liens du texte recrits,
	   juste avant le garde-fou anti-doublon. */
	if ($texte === '') {
		echo "  ATTENTION : corps vide, l'article sera cree sans texte\n";
	}

	$id_article = objet_inserer('article', $id_rubrique);
	if (!$id_article) {
		echo "  ECHEC de creation\n";
		continue;
	}
	objet_modifier('article', $id_article, array(
		'titre'     => $titre,
		'soustitre' => $soustitre,
		'texte'     => $texte,
		'statut'    => 'publie',
	));
	/* SPIP refuse parfois le passage en publie au moment meme de la
	   creation (les 6 articles d'assos sont restes en prepa), alors que la
	   meme demande, seule, passe juste apres. On verifie le statut REEL et
	   on republie dans la meme passe : plus de deuxieme relance a prevoir. */
	if (sql_getfetsel('statut', 'spip_articles', 'id_article = ' . intval($id_article)) !== 'publie') {
		sql_updateq('spip_articles', array('statut' => 'publie'),
			'id_article = ' . intval($id_article));
	}
	if ($date) {
		sql_updateq('spip_articles', array('date' => $date),
			'id_article = ' . intval($id_article));
	}

	/* Les fichiers rapatries deviennent de vrais documents de l'article :
	   PDF en carte << Telecharger >>, images dans la galerie. */
	attacher_pieces($id_article, $pieces, $dossier_img);

	/* La correspondance, pour la verification finale url par url. */
	$paires = $racine . '/tmp/import-correspondances.txt';
	file_put_contents($paires,
		ANCIEN . "/spip.php?article$id  ->  https://marlygomont.pixfeed.net/spip.php?article$id_article  ($titre)\n",
		FILE_APPEND);

	signer_article($id_article, $auteur);
	$faits++;
}

if ($importer) {
	/* Les statuts poses en direct se propagent aux rubriques : SPIP publie
	   lui-meme toute rubrique qui abrite du contenu publie. */
	include_spip('inc/rubriques');
	calculer_rubriques();
	marly_invalider_cache();
}
echo "\n$total article(s) lu(s)"
	. ($ignores ? ", $ignores hors tranche ignore(s)" : '')
	. ($importer ? ", $faits importe(s)." : ". RIEN n'a ete ecrit : mode essai.") . "\n";
if (!$importer) {
	echo "Pour importer vraiment : php " . basename(__FILE__) . " $racine importer\n";
}
