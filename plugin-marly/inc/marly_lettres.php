<?php
/**
 * Composition et envoi de la lettre d'information.
 * ---------------------------------------------------------------------------
 * Trois contraintes commandent tout ce fichier, et aucune n'est esthétique.
 *
 * 1. ON N'ENVOIE PAS DEUX CENTS COURRIELS DANS UNE REQUÊTE HTTP. Le serveur
 *    coupe au bout de trente secondes ; la moitié des abonnés reçoit deux
 *    fois, l'autre rien, et personne ne sait où ça s'est arrêté. D'où l'envoi
 *    par lots, avec un curseur qui retient le dernier servi.
 *
 * 2. LA DÉSINSCRIPTION DOIT SE FAIRE EN UN SEUL CLIC, sans page qui redemande
 *    confirmation. C'est l'exigence de Gmail et Yahoo pour les expéditeurs en
 *    nombre, durcie en novembre 2025 et reprise par Microsoft. Le lien inséré
 *    dans chaque envoi désinscrit donc immédiatement — contrairement au
 *    formulaire public, où l'on passe par un courriel de vérification pour
 *    empêcher qu'on désabonne son voisin. Ici, celui qui clique tient déjà
 *    l'adresse : il l'a reçue.
 *
 * 3. UNE VERSION TEXTE ACCOMPAGNE TOUJOURS LA VERSION HTML. Un courriel
 *    uniquement HTML est un signal de spam pour la plupart des filtres, et
 *    illisible pour qui lit ses messages en texte brut.
 */

if (!defined('_ECRIRE_INC_VERSION')) {
	return;
}

include_spip('inc/config');
include_spip('inc/filtres');

/** Combien d'abonnés par passage. Assez pour avancer, assez peu pour ne
 *  jamais approcher la limite de temps du serveur. */
define('_MARLY_LOT', 25);

/**
 * L'adresse de désinscription d'un abonné.
 * Un clic dessus suffit : pas de page intermédiaire, pas de confirmation.
 */
function marly_lien_desinscription($jeton) {
	/* Une ACTION, et non une page. Une page publique ne doit pas ecrire en
	   base : un aspirateur de liens desinscrirait des gens en passant. */
	return url_absolue('spip.php?action=marly_abonne&faire=desinscrire&jeton=' . $jeton);
}

/**
 * Les trois derniers articles publiés, pour la reprise en fin de lettre.
 *
 * Pas de choix à faire par le secrétariat : les trois derniers articles sont,
 * par construction, ceux qu'on vient de publier. Un sélecteur d'articles
 * serait un travail de plus à chaque lettre, pour un résultat presque
 * toujours identique.
 */
function marly_dernieres_actualites($combien = 3) {
	$rows = sql_allfetsel('id_article, titre, date', 'spip_articles',
		"statut = 'publie'", '', 'date DESC', '0,' . intval($combien));

	$actus = array();
	foreach ($rows as $row) {
		$actus[] = array(
			'titre' => $row['titre'],
			'date'  => affdate($row['date']),
			/* Absolue, toujours. Une adresse relative dans un courriel ne
			   mène nulle part : il n'y a pas de page d'où repartir. */
			'url'   => url_absolue(generer_url_entite($row['id_article'], 'article')),
		);
	}
	return $actus;
}

/**
 * Le corps HTML d'un envoi, personnalisé pour un abonné.
 *
 * Styles en ligne, tableau de mise en page : ce n'est pas de la nostalgie.
 * Les clients de messagerie ignorent les feuilles de style externes, et
 * Outlook ne sait toujours pas mettre en page en flexbox.
 *
 * Le cadre ne porte PAS de largeur fixe. Une largeur fixe devient le plancher
 * de la mise en page : le courriel ne peut plus se replier, et sur un
 * téléphone il est coupé sur la droite. max-width n'y peut rien — c'est la
 * largeur fixe qui commande. On écrit donc l'inverse : toute la largeur
 * disponible, plafonnée à 600 px.
 *
 * Outlook pour bureau, lui, ignore max-width. D'où le tableau entre
 * <!--[if mso]-->, que seul Outlook lit : il lui donne ses 600 px en dur.
 */
function marly_lettre_html($lettre, $abonne) {
	$site   = $GLOBALS['meta']['nom_site'] ?? '';
	$url    = url_absolue('./');
	$desinscrire = marly_lien_desinscription($abonne['jeton']);

	$titre = htmlspecialchars($lettre['titre'], ENT_QUOTES, 'UTF-8');
	$chapo = $lettre['chapo'] ? '<p style="margin:0 0 22px;font-size:17px;line-height:1.55;color:#55504A;">'
		. htmlspecialchars($lettre['chapo'], ENT_QUOTES, 'UTF-8') . '</p>' : '';

	/* liens_absolus() et non propre() seul : les raccourcis de SPIP produisent
	   des adresses relatives, qui ne mènent nulle part dans un courriel — il
	   n'y a pas de page d'où repartir. C'est ce qui permet d'écrire
	   [le compte rendu->art12] dans une lettre et que le lien fonctionne. */
	$texte = liens_absolus(propre($lettre['texte']));

	/* Le texte de prévisualisation : ce que les messageries affichent APRÈS
	   l'objet, dans la liste des messages. Sans lui, elles y mettent le
	   premier texte venu — ici « Mairie de Marly-Gomont », déjà lisible dans
	   le nom de l'expéditeur, donc une ligne perdue. */
	$apercu = htmlspecialchars(
		$lettre['chapo'] ?: textebrut(propre($lettre['texte'])), ENT_QUOTES, 'UTF-8');
	$apercu = spip_substr(trim($apercu), 0, 140);

	$date = $lettre['date'] && $lettre['date'] !== '0000-00-00 00:00:00'
		? ' &nbsp;·&nbsp; ' . affdate($lettre['date']) : '';

	/* La vidéo. Elle ne se LIT pas dans un courriel : Gmail, Outlook et la
	   plupart des autres suppriment la balise. Tous les envois en nombre font
	   donc la même chose — un appel à la regarder, qui ouvre la vidéo. */
	$video = '';
	if (!empty($lettre['video'])) {
		$lien = htmlspecialchars($lettre['video'], ENT_QUOTES, 'UTF-8');
		$video = <<<VIDEO
		<tr><td class="marly-marge" style="padding:0 36px 26px;">
			<table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="background:#F3EFE6;">
			<tr><td align="center" style="padding:22px 24px;font-family:Helvetica,Arial,sans-serif;">
				<p style="margin:0 0 14px;font-size:15px;line-height:1.5;color:#55504A;">Une vidéo accompagne cette lettre.</p>
				<a href="$lien" style="display:inline-block;background:#1E5B41;color:#FFFFFF;
				   text-decoration:none;padding:13px 24px;font-size:15px;font-weight:bold;">&#9654;&nbsp; Regarder la vidéo</a>
			</td></tr>
			</table>
		</td></tr>
VIDEO;
	}

	/* Les dernières actualités du site. C'est le renvoi vers le site : une
	   lettre d'information n'a pas à tout contenir, elle a à donner envie
	   d'aller voir. */
	$actus = '';
	if (!empty($lettre['actus'])) {
		$lignes = '';
		$liste = marly_dernieres_actualites();
		$dernier = count($liste) - 1;
		foreach ($liste as $rang => $a) {
			$t = htmlspecialchars($a['titre'], ENT_QUOTES, 'UTF-8');
			$u = htmlspecialchars($a['url'], ENT_QUOTES, 'UTF-8');
			$d = htmlspecialchars($a['date'], ENT_QUOTES, 'UTF-8');
			/* Pas de trait sous le dernier : un separateur qui ne separe plus
			   rien se lit comme le debut de quelque chose qui manque. */
			$trait = $rang < $dernier ? 'border-bottom:1px solid #EDE7DA;' : '';
			$lignes .= '<tr><td style="padding:0 0 13px;' . $trait . '">'
				. '<a href="' . $u . '" style="font-family:Helvetica,Arial,sans-serif;font-size:16px;'
				. 'font-weight:bold;color:#1D1A1A;text-decoration:none;">' . $t . '</a><br>'
				. '<span style="font-family:Helvetica,Arial,sans-serif;font-size:13px;color:#55504A;">'
				. $d . '</span></td></tr>'
				. ($rang < $dernier ? '<tr><td style="height:13px;line-height:13px;">&nbsp;</td></tr>' : '');
		}
		if ($lignes) {
			$actus = <<<ACTUS
		<tr><td class="marly-marge" style="padding:6px 36px 24px;">
			<h2 style="margin:0 0 16px;font-family:Georgia,serif;font-size:20px;color:#1E5B41;">À lire aussi sur le site</h2>
			<table role="presentation" width="100%" cellpadding="0" cellspacing="0">$lignes</table>
		</td></tr>
ACTUS;
		}
	}

	/* Les coordonnées de la mairie. Un courriel institutionnel doit dire d'où
	   il vient autrement que par son adresse d'expédition, et c'est aussi ce
	   qui permet à quelqu'un de répondre par téléphone plutôt qu'en écrivant. */
	$coord = array_filter(array(
		lire_config('marly/adresse', ''),
		trim(lire_config('marly/code_postal', '') . ' ' . lire_config('marly/ville', '')),
		lire_config('marly/telephone', ''),
	));
	$coord = $coord
		? '<p style="margin:0 0 10px;font-family:Helvetica,Arial,sans-serif;font-size:13px;'
		  . 'line-height:1.5;color:#55504A;">'
		  . htmlspecialchars(implode(' &nbsp;·&nbsp; ', $coord), ENT_QUOTES, 'UTF-8') . '</p>'
		: '';
	$coord = str_replace('&amp;nbsp;', '&nbsp;', $coord);

	return <<<HTML
<!doctype html>
<html lang="fr"><head><meta charset="utf-8">
<meta name="viewport" content="width=device-width,initial-scale=1">
<title>$titre</title>
<meta name="color-scheme" content="light">
<meta name="supported-color-schemes" content="light">
<style>
	/* Sur un telephone, les marges laterales du cadre mangent le texte : il
	   reste moins de 290 px de ligne. On les reduit. Les messageries qui
	   ignorent les media queries — Outlook pour bureau — gardent la mise en
	   page large, qui leur convient de toute facon. */
	@media (max-width:620px) {
		.marly-marge { padding-left:20px !important; padding-right:20px !important; }
		.marly-bord  { padding-left:8px  !important; padding-right:8px  !important; }
	}
</style>
</head>
<body style="margin:0;padding:0;background:#FDF8EE;">

<div style="display:none;max-height:0;overflow:hidden;mso-hide:all;">$apercu</div>
<div style="display:none;max-height:0;overflow:hidden;mso-hide:all;">&#8199;&#65279;&#8199;&#65279;&#8199;&#65279;&#8199;&#65279;&#8199;&#65279;&#8199;&#65279;&#8199;&#65279;&#8199;&#65279;</div>

<table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="background:#FDF8EE;">
<tr><td align="center" class="marly-bord" style="padding:28px 14px;">

	<!--[if mso]><table role="presentation" width="600" cellpadding="0" cellspacing="0"><tr><td><![endif]-->
	<table role="presentation" width="100%" cellpadding="0" cellspacing="0"
	       style="width:100%;max-width:600px;background:#FFFFFF;border-top:5px solid #1E5B41;">

		<tr><td class="marly-marge" style="padding:30px 36px 8px;">
			<p style="margin:0;font-family:Georgia,serif;font-size:13px;letter-spacing:.08em;
			          text-transform:uppercase;color:#1E5B41;">$site$date</p>
		</td></tr>

		<tr><td class="marly-marge" style="padding:0 36px 26px;">
			<h1 style="margin:6px 0 18px;font-family:Georgia,serif;font-size:30px;line-height:1.15;color:#1D1A1A;">$titre</h1>
			$chapo
			<div style="font-family:Helvetica,Arial,sans-serif;font-size:16px;line-height:1.65;color:#1D1A1A;">
				$texte
			</div>
		</td></tr>

$video$actus
		<tr><td class="marly-marge" style="padding:22px 36px 30px;border-top:1px solid #DED6C6;">
$coord			<p style="margin:0 0 10px;font-family:Helvetica,Arial,sans-serif;font-size:13px;line-height:1.5;color:#55504A;">
				Vous recevez ce message au titre de votre abonnement à la lettre
				d’information de la commune. Vous pouvez à tout moment demander
				l’accès aux informations vous concernant, leur rectification ou
				leur effacement, en écrivant à la mairie.
			</p>
			<p style="margin:0;font-family:Helvetica,Arial,sans-serif;font-size:13px;">
				<a href="$desinscrire" style="color:#1E5B41;">Me désinscrire</a>
				&nbsp;·&nbsp;
				<a href="$url" style="color:#1E5B41;">Voir le site</a>
			</p>
		</td></tr>

	</table>
	<!--[if mso]></td></tr></table><![endif]-->

</td></tr></table>
</body></html>
HTML;
}

/** La version texte. Jamais facultative — voir l'en-tête du fichier. */
function marly_lettre_texte($lettre, $abonne) {
	$site = $GLOBALS['meta']['nom_site'] ?? '';
	$texte = trim(textebrut(liens_absolus(propre($lettre['texte']))));
	$chapo = $lettre['chapo'] ? trim($lettre['chapo']) . "\n\n" : '';

	$bloc_video = empty($lettre['video']) ? ''
		: "Regarder la vidéo : " . $lettre['video'] . "\n\n";

	$bloc_actus = '';
	if (!empty($lettre['actus'])) {
		$lignes = '';
		foreach (marly_dernieres_actualites() as $a) {
			$lignes .= "- " . $a['titre'] . " (" . $a['date'] . ")\n  " . $a['url'] . "\n";
		}
		if ($lignes) {
			$bloc_actus = "À LIRE AUSSI SUR LE SITE\n\n" . $lignes . "\n";
		}
	}

	return strtoupper($site) . "\n\n"
		. $lettre['titre'] . "\n"
		. str_repeat('=', min(60, strlen($lettre['titre']))) . "\n\n"
		. $chapo . $texte . "\n\n"
		. $bloc_video . $bloc_actus
		. str_repeat('-', 60) . "\n"
		. "Vous recevez ce message au titre de votre abonnement à la lettre\n"
		. "d’information de la commune. Vous pouvez à tout moment demander\n"
		. "l’accès aux informations vous concernant, leur rectification ou\n"
		. "leur effacement, en écrivant à la mairie.\n\n"
		. "Me désinscrire : " . marly_lien_desinscription($abonne['jeton']) . "\n";
}

/**
 * Envoie un lot. Rend le nombre d'envois tentés.
 *
 * Appelée par la tâche périodique, et par le bouton « envoyer maintenant »
 * de l'espace privé — la même fonction dans les deux cas, sinon l'un des
 * deux chemins finit par diverger.
 */
function marly_envoyer_lot($id_lettre) {
	$lettre = sql_fetsel('*', 'spip_lettres', 'id_lettre = ' . intval($id_lettre));
	if (!$lettre or $lettre['statut'] !== 'envoi') {
		return 0;
	}

	$abonnes = sql_allfetsel('id_abonne, courriel, nom, prenom, jeton', 'spip_abonnes', array(
		"statut = 'confirme'",
		'id_abonne > ' . intval($lettre['curseur']),
	), '', 'id_abonne', '0,' . _MARLY_LOT);

	if (!$abonnes) {
		sql_updateq('spip_lettres', array(
			'statut'     => 'envoyee',
			'date_envoi' => date('Y-m-d H:i:s'),
		), 'id_lettre = ' . intval($id_lettre));
		spip_log("marly : lettre $id_lettre terminee, {$lettre['nb_envoyes']} envois", 'marly.' . _LOG_INFO_IMPORTANTE);
		return 0;
	}

	$envoyer = charger_fonction('envoyer_mail', 'inc', true);
	if (!$envoyer) {
		spip_log('marly : envoyer_mail indisponible, envoi suspendu', 'marly' . _LOG_ERREUR);
		return 0;
	}

	$expediteur = lire_config('marly/courriel', '') ?: ($GLOBALS['meta']['email_webmaster'] ?? '');
	$envoyes = intval($lettre['nb_envoyes']);
	$erreurs = intval($lettre['nb_erreurs']);
	$dernier = intval($lettre['curseur']);

	foreach ($abonnes as $abonne) {
		$desinscrire = marly_lien_desinscription($abonne['jeton']);

		/* List-Unsubscribe et son pendant One-Click : c'est ce qui permet au
		   client de messagerie d'afficher son propre bouton « se désabonner »,
		   et c'est exigé des expéditeurs en nombre. Precedence: bulk évite
		   que les réponses automatiques d'absence reviennent en masse. */
		$entetes = "List-Unsubscribe: <$desinscrire>\n"
			. "List-Unsubscribe-Post: List-Unsubscribe=One-Click\n"
			. "Precedence: bulk\n"
			. "Auto-Submitted: auto-generated\n";

		$ok = $envoyer(
			$abonne['courriel'],
			$lettre['titre'],
			marly_lettre_texte($lettre, $abonne),
			$expediteur,
			$entetes,
			marly_lettre_html($lettre, $abonne)
		);

		if ($ok) { $envoyes++; } else { $erreurs++; }
		$dernier = intval($abonne['id_abonne']);
	}

	sql_updateq('spip_lettres', array(
		'curseur'    => $dernier,
		'nb_envoyes' => $envoyes,
		'nb_erreurs' => $erreurs,
	), 'id_lettre = ' . intval($id_lettre));

	return count($abonnes);
}

/** Le nombre d'abonnés qui recevront. */
function marly_nb_destinataires() {
	return sql_countsel('spip_abonnes', "statut = 'confirme'");
}

/** Ce qu'il reste à servir pour une lettre en cours. */
function marly_nb_restants($lettre) {
	return sql_countsel('spip_abonnes', array(
		"statut = 'confirme'",
		'id_abonne > ' . intval($lettre['curseur']),
	));
}
