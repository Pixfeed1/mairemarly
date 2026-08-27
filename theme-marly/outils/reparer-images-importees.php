<?php
/**
 * Rend aux articles importes les images que l'import n'avait pas vues.
 * ---------------------------------------------------------------------------
 *   php theme-marly/outils/reparer-images-importees.php /chemin/racine-web
 *   php theme-marly/outils/reparer-images-importees.php /chemin/racine-web --ecrire
 *
 * SANS --ecrire, LE SCRIPT NE TOUCHE A RIEN. Il dit ce qu'il ferait, et il
 * verifie que chaque source repond avant de l'annoncer recuperable.
 *
 * CE QUI S'ETAIT PASSE. L'import de l'ancien site ne reconnaissait que les
 * adresses contenant << IMG/ >>. Or un SPIP 3 affiche ses images par une
 * vignette calculee, rangee sous local/cache-vignettes/. Ces balises-la lui
 * sont donc passees sous le nez : dix-sept articles, une cinquantaine
 * d'images, toutes cassees a l'ecran depuis l'import.
 *
 * Mesure du 27 aout 2026 : les originaux repondent encore sur
 * marlygomont.free.fr, en pleine resolution. Une vignette L500xH708 porte le
 * nom de son original entre un dossier de dimensions et un suffixe de
 * hachage ; on le deshabille pour reconstruire l'adresse d'origine.
 *
 * TROIS CHOSES NE SONT PAS DES PHOTOS et se retirent au lieu de se reparer :
 * la puce de liste de SPIP 3 (8x11), les pictogrammes de type de fichier PDF
 * et Word (52x52), et un lien vers une page Vistaprint. Le critere est
 * mesurable et net : une balise declaree a 60 pixels de large ou moins n'est
 * pas une photographie d'article, c'est un ornement de l'ancien moteur.
 *
 * POURQUOI ATTACHER COMME DOCUMENT plutot que reecrire le src. Un src corrige
 * ne montre l'image que dans le corps du texte. Un DOCUMENT attache devient
 * l'illustration de l'article dans les listes et sur la page d'accueil, parce
 * que les gabarits retombent depuis peu sur la premiere image jointe. Dix-sept
 * articles gagnent donc leur vignette, et pas seulement leur photo.
 *
 * L'ancien site reste en LECTURE SEULE : on ne lit que des fichiers publics.
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

$ecrire = in_array('--ecrire', $argv, true);

/* SANS CETTE SESSION, objet_modifier() est refuse EN SILENCE. Regle 66. */
$GLOBALS['visiteur_session'] = array(
	'id_auteur' => 1,
	'statut'    => '0minirezo',
	'webmestre' => 'oui',
	'nom'       => 'reparation CLI',
);

define('ANCIEN', 'http://marlygomont.free.fr');
$dossier_img = $racine . '/IMG/ancien-site/';
include_spip('inc/distant');
include_spip('action/ajouter_documents');
include_spip('action/editer_liens');
include_spip('inc/charsets');
$ajouter = charger_fonction('ajouter_un_document', 'action', true);
if (!$ajouter and $ecrire) {
	fwrite(STDERR, "Le plugin medias ne repond pas : rien ne peut etre attache.\n");
	exit(1);
}

/** Le nom du fichier d'origine, deshabille de ce que SPIP 3 lui ajoute :
    le dossier L500xH708/ devant, un suffixe de hachage avant l'extension.
    LA CASSE EST CONSERVEE — P1010061.jpg et p1010061.jpg sont deux adresses
    differentes sur un serveur Apache, et c'est la premiere qui existe. */
function nom_origine($src) {
	$chemin = parse_url($src, PHP_URL_PATH);
	$base = basename($chemin === null ? $src : $chemin);
	return preg_replace('/-[0-9a-f]{4,8}(\.[a-z0-9]+)$/i', '$1', $base);
}

/** Les adresses a essayer, dans l'ordre : l'original en pleine resolution
    d'abord, la vignette calculee ensuite. */
function adresses_possibles($src) {
	$nom = nom_origine($src);
	$ext = strtolower(pathinfo($nom, PATHINFO_EXTENSION));
	$a = array();
	if ($ext !== '') { $a[] = ANCIEN . '/IMG/' . $ext . '/' . $nom; }
	$a[] = (strpos($src, 'http') === 0) ? $src : ANCIEN . '/' . ltrim($src, '/');
	return $a;
}

/** Repond, et avec quoi ? En essai on ne tire que les premiers octets. */
function source_repond($u, $complet = false) {
	$r = recuperer_url($u, array(
		'taille_max'  => $complet ? 20971520 : 4096,
		'transcoder'  => false,
	));
	if (empty($r['page'])) { return false; }
	if (isset($r['status']) and intval($r['status']) and intval($r['status']) !== 200) { return false; }
	return $complet ? $r['page'] : true;
}

$articles = sql_allfetsel('id_article, titre, chapo, texte, descriptif', 'spip_articles');
$champs_scrutes = array('chapo', 'texte', 'descriptif');

$n_photo = $n_recuperable = $n_perdue = $n_ornement = $n_externe = 0;
$n_articles = $n_ecrits = 0;

foreach ($articles as $a) {
	$id = intval($a['id_article']);
	$modifs = array();
	$lignes = array();

	foreach ($champs_scrutes as $champ) {
		$avant = (string) $a[$champ];
		if (!preg_match_all(',<img\b[^>]*>,i', $avant, $m)) { continue; }
		$apres = $avant;

		foreach ($m[0] as $tag) {
			if (!preg_match(',\bsrc\s*=\s*["\']([^"\']+)["\'],i', $tag, $s)) { continue; }
			$src = html_entity_decode($s[1], ENT_QUOTES, 'UTF-8');

			/* 1. Les ornements de l'ancien moteur : puce de liste, pictogramme
			      de type de fichier. La largeur declaree les designe sans
			      ambiguite, et elle est ecrite dans la balise. */
			$large = preg_match(',\bwidth\s*=\s*["\']?(\d+),i', $tag, $w) ? intval($w[1]) : 0;
			if ($large and $large <= 60) {
				$apres = str_replace($tag, '', $apres);
				$lignes[] = sprintf("    ORNEMENT %dpx retire : %s", $large, basename($src));
				$n_ornement++;
				continue;
			}

			/* 2. Ce qui est heberge ailleurs que sur l'ancien site : on ne peut
			      ni le rapatrier legitimement ni le garder, il finira casse. */
			if (preg_match(',^https?://,i', $src) and stripos($src, 'marlygomont.free.fr') === false) {
				$apres = str_replace($tag, '', $apres);
				$lignes[] = '    EXTERNE retire : ' . preg_replace(',\?.*$,', '', $src);
				$n_externe++;
				continue;
			}

			$n_photo++;
			$nom = nom_origine($src);

			/* La legende, quand l'ancien site en portait une : elle devient le
			   titre du document, sinon elle serait perdue avec la balise. */
			$titre = '';
			if (preg_match(',\btitle\s*=\s*["\']([^"\']*)["\'],i', $tag, $t)) { $titre = trim($t[1]); }
			elseif (preg_match(',\balt\s*=\s*["\']([^"\']*)["\'],i', $tag, $t)) { $titre = trim($t[1]); }
			if (preg_match(',^(JPG|PNG|GIF|PDF|Word)\s*-,i', $titre)) { $titre = ''; }
			$titre = html_entity_decode($titre, ENT_QUOTES, 'UTF-8');

			$trouvee = '';
			foreach (adresses_possibles($src) as $u) {
				if (source_repond($u)) { $trouvee = $u; break; }
			}
			if ($trouvee === '') {
				$lignes[] = '    PERDUE : ' . $nom . ' ne repond nulle part';
				$n_perdue++;
				continue;
			}
			$n_recuperable++;
			$lignes[] = '    PHOTO   : ' . $nom . ' <- ' . $trouvee
			          . ($titre !== '' ? ' [' . $titre . ']' : '');

			if (!$ecrire) { continue; }

			/* Ecriture : rapatrier, attacher comme document, remplacer la
			   balise par le raccourci SPIP du document. */
			if (!is_dir($dossier_img)) { @mkdir($dossier_img, 0755, true); }
			$cible = $dossier_img . $nom;
			if (!file_exists($cible)) {
				$octets = source_repond($trouvee, true);
				if ($octets === false) { $lignes[] = '      ECHEC du telechargement'; continue; }
				file_put_contents($cible, $octets);
			}
			/* ajouter_un_document DEPLACE le fichier qu'on lui tend : on lui
			   donne une copie, l'original reste dans IMG/ancien-site/. */
			$copie = $dossier_img . 'copie-' . $nom;
			if (!copy($cible, $copie)) { $lignes[] = '      ECHEC de la copie'; continue; }
			$id_doc = $ajouter('new', array(
				'name'     => $nom,
				'tmp_name' => $copie,
				'titre'    => $titre,
				'distant'  => false,
			), 'article', $id, 'image');
			if (file_exists($copie)) { @unlink($copie); }
			if (!is_numeric($id_doc) or $id_doc <= 0) {
				$lignes[] = '      ECHEC de l attachement : ' . $id_doc;
				continue;
			}
			objet_associer(array('document' => intval($id_doc)), array('article' => $id));
			sql_updateq('spip_documents', array('statut' => 'publie'),
				'id_document = ' . intval($id_doc));
			$apres = str_replace($tag, '<img' . intval($id_doc) . '|center>', $apres);
			$lignes[] = '      -> document ' . intval($id_doc);
		}

		/* Une balise retiree laisse souvent un paragraphe vide derriere elle. */
		$apres = preg_replace(',<p[^>]*>\s*</p>,i', '', $apres);
		$apres = preg_replace(',(<br\s*/?>\s*){3,},i', '<br>', $apres);

		if ($apres !== $avant) { $modifs[$champ] = $apres; }
	}

	if (!$lignes) { continue; }
	$n_articles++;
	printf("\n[%d] %s\n%s\n", $id, $a['titre'], implode("\n", $lignes));

	if ($modifs and $ecrire) {
		objet_modifier('article', $id, $modifs);
		/* On relit : une ecriture dont personne ne verifie le resultat ment
		   tot ou tard. Regle 68 du verificateur. */
		$relu = sql_getfetsel('texte', 'spip_articles', 'id_article = ' . $id);
		if (isset($modifs['texte']) and trim($relu) !== trim($modifs['texte'])) {
			echo "    ECHEC : le texte n'a pas ete enregistre.\n";
		} else {
			$n_ecrits++;
		}
	}
}

printf("\n%s\n", str_repeat('-', 66));
printf("%d article(s) concerne(s).\n", $n_articles);
printf("  photographies trouvees dans les textes : %d\n", $n_photo);
printf("    dont recuperables sur l'ancien site  : %d\n", $n_recuperable);
printf("    dont perdues                         : %d\n", $n_perdue);
printf("  ornements de l'ancien moteur retires   : %d\n", $n_ornement);
printf("  images hebergees ailleurs retirees     : %d\n", $n_externe);
if ($ecrire) {
	printf("\n%d article(s) reecrit(s) en base.\n", $n_ecrits);
} else {
	echo "\nESSAI A BLANC : rien n'a ete telecharge, rien n'a ete ecrit.\n";
	echo "Relancer avec --ecrire pour appliquer.\n";
}
