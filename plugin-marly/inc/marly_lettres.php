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
	return url_absolue(generer_url_public('newsletter', 'action=desinscrire&jeton=' . $jeton));
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
	$texte = propre($lettre['texte']);

	return <<<HTML
<!doctype html>
<html lang="fr"><head><meta charset="utf-8">
<meta name="viewport" content="width=device-width,initial-scale=1">
<title>$titre</title>
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
<table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="background:#FDF8EE;">
<tr><td align="center" class="marly-bord" style="padding:28px 14px;">

	<!--[if mso]><table role="presentation" width="600" cellpadding="0" cellspacing="0"><tr><td><![endif]-->
	<table role="presentation" width="100%" cellpadding="0" cellspacing="0"
	       style="width:100%;max-width:600px;background:#FFFFFF;border-top:5px solid #1E5B41;">

		<tr><td class="marly-marge" style="padding:30px 36px 8px;">
			<p style="margin:0;font-family:Georgia,serif;font-size:13px;letter-spacing:.08em;
			          text-transform:uppercase;color:#1E5B41;">$site</p>
		</td></tr>

		<tr><td class="marly-marge" style="padding:0 36px 26px;">
			<h1 style="margin:6px 0 18px;font-family:Georgia,serif;font-size:30px;line-height:1.15;color:#1D1A1A;">$titre</h1>
			$chapo
			<div style="font-family:Helvetica,Arial,sans-serif;font-size:16px;line-height:1.65;color:#1D1A1A;">
				$texte
			</div>
		</td></tr>

		<tr><td class="marly-marge" style="padding:22px 36px 30px;border-top:1px solid #DED6C6;">
			<p style="margin:0 0 10px;font-family:Helvetica,Arial,sans-serif;font-size:13px;line-height:1.5;color:#55504A;">
				Vous recevez ce message au titre de votre abonnement à la lettre
				d’information de la commune. Le nom de l’expéditeur figure en tête.
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
	$texte = trim(textebrut(propre($lettre['texte'])));
	$chapo = $lettre['chapo'] ? trim($lettre['chapo']) . "\n\n" : '';

	return strtoupper($site) . "\n\n"
		. $lettre['titre'] . "\n"
		. str_repeat('=', min(60, strlen($lettre['titre']))) . "\n\n"
		. $chapo . $texte . "\n\n"
		. str_repeat('-', 60) . "\n"
		. "Vous recevez ce message au titre de votre abonnement à la lettre\n"
		. "d’information de la commune.\n\n"
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
		spip_log("marly : lettre $id_lettre terminee, {$lettre['nb_envoyes']} envois", 'marly');
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
