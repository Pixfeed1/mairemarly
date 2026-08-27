<?php
/**
 * Parcourt le site servi et vérifie que tout ce qu'il montre répond vraiment.
 * ---------------------------------------------------------------------------
 *   php theme-marly/outils/verifier-fichiers.php https://marlygomont.pixfeed.net \
 *       --auth mairie:motdepasse
 *
 *   Options : --pages 150   nombre maximum de pages parcourues
 *             --externes    vérifie aussi les adresses hors du site
 *
 * POURQUOI CET OUTIL EXISTE. Le 27 août 2026, on a découvert que les 52 PDF
 * de comptes rendus étaient morts depuis cinq jours : ils appartenaient à root
 * et le serveur répondait 404 dessus. Rien ne pouvait le dire. Le vérificateur
 * de squelettes lit le CODE, pas le site servi ; et la bande « Les derniers
 * documents » de la page d'accueil affichait fièrement six liens qui ne
 * menaient nulle part, parce que personne n'avait cliqué.
 *
 * Un site de mairie se juge sur ce genre de détail : un habitant qui clique
 * sur un compte rendu et reçoit une page d'erreur n'y revient pas.
 *
 * CE QUE FAIT LE SCRIPT. Il part de l'accueil, suit les liens internes, et
 * pour chaque page relève tout ce qu'elle demande au serveur — images,
 * feuilles de style, scripts, PDF, pièces jointes. Chacun est interrogé. Tout
 * ce qui ne répond pas 200 est listé AVEC LA PAGE QUI LE RÉCLAME : sans ça on
 * sait qu'un fichier manque, sans savoir où il fait du dégât.
 *
 * IL NE LIT QUE. Aucune écriture, ni sur le disque ni en base.
 *
 * LE MOT DE PASSE N'EST PAS DANS LE FICHIER, et ne doit pas y entrer : ce
 * dépôt est versionné. Il se passe en argument, ou par la variable
 * d'environnement MARLY_AUTH.
 */
error_reporting(E_ALL);
ini_set('display_errors', 'stderr');

if (PHP_SAPI !== 'cli') {
	die("Ce script ne s'utilise qu'en ligne de commande.\n");
}
if (!function_exists('curl_init')) {
	fwrite(STDERR, "L'extension cURL de PHP est absente.\n");
	exit(1);
}

$racine   = '';
$auth     = getenv('MARLY_AUTH') ?: '';
$max      = 150;
$externes = false;
for ($i = 1; $i < $argc; $i++) {
	$a = $argv[$i];
	if ($a === '--auth' and isset($argv[$i + 1]))      { $auth = $argv[++$i]; }
	elseif ($a === '--pages' and isset($argv[$i + 1])) { $max = max(1, intval($argv[++$i])); }
	elseif ($a === '--externes')                       { $externes = true; }
	elseif ($racine === '')                            { $racine = rtrim($a, '/'); }
}
if ($racine === '' or !preg_match(',^https?://,i', $racine)) {
	fwrite(STDERR, "Usage : php verifier-fichiers.php https://le-site [--auth user:pass]\n");
	exit(1);
}
$hote = parse_url($racine, PHP_URL_HOST);

/** Une requête, et rien de plus. HEAD d'abord : on ne veut pas télécharger
    quarante mégaoctets de PDF pour savoir s'ils existent. Certains serveurs
    répondent mal au HEAD — 405, 501 — on repasse alors en GET. */
function interroger($url, $auth, $corps = false) {
	foreach ($corps ? array('GET') : array('HEAD', 'GET') as $methode) {
		$c = curl_init($url);
		curl_setopt_array($c, array(
			CURLOPT_RETURNTRANSFER => true,
			CURLOPT_FOLLOWLOCATION => true,
			CURLOPT_MAXREDIRS      => 5,
			CURLOPT_TIMEOUT        => 25,
			CURLOPT_NOBODY         => ($methode === 'HEAD'),
			CURLOPT_USERAGENT      => 'verification interne du site de la commune',
			CURLOPT_SSL_VERIFYPEER => true,
		));
		if ($auth !== '') { curl_setopt($c, CURLOPT_USERPWD, $auth); }
		$page = curl_exec($c);
		$code = intval(curl_getinfo($c, CURLINFO_HTTP_CODE));
		$type = (string) curl_getinfo($c, CURLINFO_CONTENT_TYPE);
		$err  = curl_error($c);
		curl_close($c);
		if ($methode === 'HEAD' and in_array($code, array(0, 405, 501), true)) { continue; }
		return array('code' => $code, 'type' => $type, 'page' => $page, 'erreur' => $err);
	}
	return array('code' => 0, 'type' => '', 'page' => '', 'erreur' => 'aucune reponse');
}

/** Résout une adresse relative COMME LE FAIT UN NAVIGATEUR, c'est-à-dire
    contre la balise <base> de la page quand elle existe, et contre l'adresse
    de la page sinon.

    LA PREMIÈRE VERSION IGNORAIT LE <base>, et ce n'était pas un détail : elle
    a rendu 57 pages en 404 qui n'existent pour personne. SPIP pose lui-même
    <base href="https://le-site/"> quand les URLs propres sont actives, et
    tout le thème s'appuie dessus — le menu écrit href="evenements/", les
    liens d'articles href="infos/article/x", et c'est correct.

    Un contrôle qui invente des pannes est pire qu'aucun contrôle : on cesse
    de le lire. */
function absolue($lien, $base) {
	$lien = trim(html_entity_decode($lien, ENT_QUOTES, 'UTF-8'));
	if ($lien === '' or preg_match(',^(#|mailto:|tel:|javascript:|data:),i', $lien)) { return ''; }
	if (preg_match(',^https?://,i', $lien)) { return $lien; }
	$p = parse_url($base);
	$origine = $p['scheme'] . '://' . $p['host'] . (isset($p['port']) ? ':' . $p['port'] : '');
	if (strpos($lien, '//') === 0) { return $p['scheme'] . ':' . $lien; }
	if (strpos($lien, '/') === 0)  { return $origine . $lien; }
	$dossier = isset($p['path']) ? preg_replace(',/[^/]*$,', '/', $p['path']) : '/';
	$chemin = $dossier . $lien;
	/* Réduire les . et .. comme le ferait un navigateur. */
	$morceaux = array();
	foreach (explode('/', $chemin) as $m) {
		if ($m === '.' or $m === '') { continue; }
		if ($m === '..') { array_pop($morceaux); continue; }
		$morceaux[] = $m;
	}
	return $origine . '/' . implode('/', $morceaux);
}

/* Une page se parcourt, un fichier se vérifie. La distinction se fait sur
   l'extension : c'est grossier, mais SPIP sert ses pages sans extension ou en
   .html, et tout le reste est un fichier. */
function est_fichier($url) {
	$chemin = parse_url($url, PHP_URL_PATH);
	if ($chemin === null or $chemin === '') { return false; }
	/* Delimiteur ~ : le motif contient {2,5}, une virgule le refermerait au
	   milieu. Regle 72 du verificateur, qui vient de l'attraper ici meme. */
	if (!preg_match('~\.([a-z0-9]{2,5})$~i', $chemin, $m)) { return false; }
	return !in_array(strtolower($m[1]), array('html', 'htm', 'php'), true);
}

$a_voir    = array($racine . '/');
$vues      = array();
$fichiers  = array();   /* url => liste des pages qui la reclament */
$soucis    = array();
$n_pages   = 0;

echo "Depart : $racine\n";
if ($auth === '') { echo "Sans authentification.\n"; }
echo str_repeat('-', 70), "\n";

while ($a_voir and $n_pages < $max) {
	$url = array_shift($a_voir);
	$cle = preg_replace(',#.*$,', '', $url);
	if (isset($vues[$cle])) { continue; }
	$vues[$cle] = true;

	$r = interroger($cle, $auth, true);
	$n_pages++;
	if ($r['code'] !== 200) {
		$soucis[] = sprintf("%-4s PAGE   %s", $r['code'] ?: 'ERR', $cle);
		continue;
	}
	if (stripos($r['type'], 'html') === false) { continue; }

	printf("%3d. %s\n", $n_pages, $cle);

	/* LA BALISE <base> EST LE POINT DE DEFAILLANCE UNIQUE DU SITE. Tout le
	   thème écrit ses liens en relatif et s'appuie sur elle. Le jour où elle
	   disparaît, la navigation entière tombe d'un coup, sur toutes les pages
	   sauf l'accueil. C'est donc elle qu'il faut surveiller, et non les
	   adresses relatives qu'elle rend légitimes. */
	$base = $cle;
	if (preg_match(',<base[^>]+href\s*=\s*["\']([^"\']+)["\'],i', $r['page'], $b)) {
		$base = $b[1];
	} else {
		$soucis[] = "BASE MANQUANTE sur $cle — tous les liens relatifs de cette "
		          . "page sont faux pour le visiteur";
	}

	if (preg_match_all(',(?:href|src)\s*=\s*["\']([^"\']+)["\'],i', $r['page'], $m)) {
		foreach ($m[1] as $lien) {
			$u = absolue($lien, $base);
			if ($u === '') { continue; }
			$meme = (parse_url($u, PHP_URL_HOST) === $hote);
			if (!$meme and !$externes) { continue; }

			if (est_fichier($u) or !$meme) {
				$fichiers[$u][$cle] = true;
			} elseif ($meme
					and strpos($u, '/ecrire/') === false
					and strpos($u, 'action=') === false
					and !isset($vues[preg_replace(',#.*$,', '', $u)])) {
				$a_voir[] = $u;
			}
		}
	}
}

echo str_repeat('-', 70), "\n";
printf("%d page(s) parcourue(s), %d adresse(s) de fichier a verifier.\n\n",
	$n_pages, count($fichiers));

$ko = 0;
foreach ($fichiers as $u => $pages) {
	$r = interroger($u, $auth);
	if ($r['code'] === 200) { continue; }
	$ko++;
	printf("%-4s %s\n", $r['code'] ?: 'ERR', $u);
	foreach (array_keys($pages) as $p) { echo "        reclame par  $p\n"; }
	if ($r['erreur']) { echo "        (" . $r['erreur'] . ")\n"; }
}

echo "\n", str_repeat('-', 70), "\n";
foreach ($soucis as $s) { echo $s, "\n"; }
if (!$ko and !$soucis) {
	echo "Tout repond. Aucun lien mort, aucun fichier manquant.\n";
} else {
	printf("%d fichier(s) en defaut, %d page(s) en defaut.\n", $ko, count($soucis));
	echo "\nUN 404 SUR UN FICHIER QUI EXISTE SUR LE DISQUE n'est pas une adresse\n";
	echo "fausse : regarder A QUI IL APPARTIENT. Le serveur refuse ce qui\n";
	echo "n'appartient pas a l'utilisateur du site, et repond 404, pas 403.\n";
	echo "C'est ce qui a tenu les 52 PDF de comptes rendus morts pendant cinq\n";
	echo "jours. La commande :  chown -R <utilisateur> <racine>/IMG\n";
	exit(1);
}
