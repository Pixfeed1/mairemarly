<?php
/**
 * Installation et désinstallation.
 * ---------------------------------------------------------------------------
 * SPIP appelle marly_upgrade() à l'activation et à chaque montée de version.
 * maj_plugin() compare la version enregistrée à la version cible et n'exécute
 * que les étapes manquantes : une installation neuve les joue toutes, une
 * mise à jour ne rejoue rien.
 */

if (!defined('_ECRIRE_INC_VERSION')) {
	return;
}

function marly_upgrade($nom_meta_base_version, $version_cible) {
	$maj = array();

	/* 1.0.0 — le plugin ne faisait que des réglages : rien en base. */
	$maj['1.0.0'] = array();

	/* 2.0.0 — les deux tables de la réservation. creer_base() les crée à
	   partir des déclarations de base/marly.php : aucun SQL écrit ici, donc
	   aucune divergence possible entre la déclaration et la table réelle. */
	$maj['2.0.0'] = array(
		array('maj_tables', array('spip_salles', 'spip_reservations')),
	);

	/* 3.0.0 — les manifestations, et deux colonnes de plus sur les
	   réservations. maj_tables ajoute les colonnes manquantes sans toucher
	   aux données : les réservations de salles déjà saisies survivent. */
	$maj['3.0.0'] = array(
		array('maj_tables', array('spip_manifestations', 'spip_reservations')),
	);

	/* 3.1.0 — les tables passent en objets editoriaux declares (l'API de
	   SPIP 4). Aucune colonne ne change : c'est la declaration qui evolue,
	   pour ouvrir les logos et la recherche. maj_tables est rejoue par
	   securite, il ne fera rien si tout est deja en place. */
	$maj['3.1.0'] = array(
		array('maj_tables', array('spip_manifestations', 'spip_salles', 'spip_reservations')),
	);

	/* 3.2.0 — une colonne video sur les evenements. */
	$maj['3.2.0'] = array(
		array('maj_tables', array('spip_manifestations')),
	);

	/* 3.4.0 — la table des abonnes a la lettre d'information. */
	$maj['3.4.0'] = array(
		array('maj_tables', array('spip_abonnes')),
	);

	/* 3.5.0 — prenom, code postal et commune sur les abonnes. */
	$maj['3.5.0'] = array(
		array('maj_tables', array('spip_abonnes')),
	);

	/* 3.6.0 — les lettres envoyees. */
	$maj['3.6.0'] = array(
		array('maj_tables', array('spip_lettres')),
	);

	/* 3.9.0 — un lien video et la reprise des dernieres actualites sur les
	   lettres. Les lettres deja ecrites gardent leur contenu : maj_tables
	   ajoute les colonnes, il ne reecrit rien. */
	$maj['3.9.0'] = array(
		array('maj_tables', array('spip_lettres')),
	);

	/* 3.11.0 — les demarches, et le socle national pose une seule fois.
	   marly_poser_socle() ne fait rien si la table contient deja quelque
	   chose : une fiche que la mairie a supprimee ne doit pas revenir a la
	   mise a jour suivante. */
	$maj['3.11.0'] = array(
		array('maj_tables', array('spip_demarches')),
		array('marly_poser_socle'),
	);

	/* 3.12.0 — les elus, et six colonnes de plus sur les demarches. */
	$maj['3.12.0'] = array(
		array('maj_tables', array('spip_elus', 'spip_demarches')),
	);

	/* 3.13.0 — les raccourcis de la page d'accueil. */
	$maj['3.13.0'] = array(
		array('maj_tables', array('spip_raccourcis')),
	);

	/* 3.14.0 — l'annuaire des associations, et la fiche << creer une
	   association >> qui manquait au socle. */
	$maj['3.14.0'] = array(
		array('maj_tables', array('spip_associations')),
		array('marly_ajouter_demarche_association'),
	);

	/* 3.16.0 — la rubrique ou une association publie ses articles. */
	$maj['3.16.0'] = array(
		array('maj_tables', array('spip_associations')),
	);

	/* 3.18.0 — les lieux de la commune, et le lieu d'une association. */
	$maj['3.18.0'] = array(
		array('maj_tables', array('spip_lieux', 'spip_associations')),
	);

	/* 3.20.0 — les coordonnees d'une association, tirees de son adresse. */
	$maj['3.20.0'] = array(
		array('maj_tables', array('spip_associations')),
	);

	/* 3.21.0 — les associations saisies avant que la creation automatique
	   n'existe recoivent leur rubrique. Une seule fois. */
	$maj['3.21.0'] = array(
		array('marly_rubriques_associations_manquantes'),
	);

	include_spip('base/upgrade');
	maj_plugin($nom_meta_base_version, $version_cible, $maj);
}

/**
 * Pose le socle national des démarches, si et seulement si la table est vide.
 *
 * La condition compte plus que l'insertion. Sans elle, une mairie qui aurait
 * supprimé « Attestation d'accueil » parce qu'elle ne la délivre pas la
 * verrait réapparaître à chaque mise à jour du plugin, sans comprendre
 * pourquoi. Le socle est un point de départ, pas une norme imposée.
 */
function marly_poser_socle() {
	if (sql_countsel('spip_demarches')) {
		spip_log('marly : des demarches existent deja, socle non repose', 'marly');
		return;
	}

	include_spip('inc/marly_demarches');
	$posees = 0;
	foreach (marly_socle_demarches() as $fiche) {
		$fiche['socle']  = 1;
		$fiche['statut'] = 'publie';
		if (sql_insertq('spip_demarches', $fiche)) {
			$posees++;
		}
	}
	spip_log("marly : socle pose, $posees demarches", 'marly');
}

/**
 * Ajoute la fiche « Créer une association », oubliée dans le socle initial.
 *
 * Insérée UNE FOIS, et seulement si aucune fiche ne porte déjà ce titre. Une
 * mairie qui l'aurait supprimée entre-temps ne la verra pas revenir : c'est
 * la même règle que pour le socle, et elle vaut aussi pour les rattrapages.
 */
function marly_ajouter_demarche_association() {
	if (sql_countsel('spip_demarches', 'titre LIKE ' . sql_quote('%association%'))) {
		return;
	}

	sql_insertq('spip_demarches', array(
		'titre'   => 'Créer une association',
		'famille' => 'enligne',
		'icone'   => 'ri-team-line',
		'rang'    => 90,
		'socle'   => 1,
		'statut'  => 'publie',
		'resume'  => 'La déclaration se fait en ligne ou à la préfecture. La mairie n’intervient pas, mais elle peut vous orienter.',
		'qui'     => 'Au moins deux personnes majeures souhaitant mener un projet commun sans but lucratif.',
		'comment' => 'Rédigez les statuts, tenez l’assemblée constitutive, puis déclarez l’association en ligne. La publication au Journal officiel se demande au même moment.',
		'pieces'  => 'Statuts signés, procès-verbal de l’assemblée constitutive, et liste des dirigeants.',
		'cout'    => 'Déclaration gratuite. Publication au Journal officiel : 44 €',
		'delai'   => 'Récépissé sous 5 jours, publication sous un mois environ',
		'ou'      => 'En ligne, ou au greffe des associations de la préfecture. Le secrétariat de la mairie peut vous aider à constituer le dossier.',
		'a_savoir' => 'Une fois l’association déclarée, signalez-la à la mairie : elle figurera dans l’annuaire du site et pourra réserver la salle des fêtes.',
		'lien'      => 'https://www.service-public.gouv.fr/particuliers/vosdroits/F1120',
		'lien_faire' => 'https://www.service-public.gouv.fr/particuliers/vosdroits/R37933',
	));
	spip_log('marly : fiche << creer une association >> ajoutee', 'marly');
}

/**
 * Donne sa rubrique à chaque association qui n'en a pas.
 *
 * Rattrapage pour celles saisies avant que la création automatique n'existe.
 * Il ne tourne qu'une fois, à cette étape de mise à jour : à ce moment-là,
 * aucune mairie n'a encore pu choisir « Aucune » délibérément, puisque le
 * choix vient d'apparaître. Plus tard, ce serait défaire une décision.
 *
 * C'est aussi la première fois que marly_rubrique_association() s'exécute
 * pour de bon. Si l'appel à SPIP échoue, rien n'est perdu : les associations
 * restent sans rubrique, et la mairie peut en choisir une à la main.
 */
function marly_rubriques_associations_manquantes() {
	if (!sql_countsel('spip_associations', 'id_rubrique = 0')) {
		return;
	}

	include_spip('inc/marly_associations');
	$faites = 0;
	foreach (sql_allfetsel('id_association, nom', 'spip_associations', 'id_rubrique = 0') as $a) {
		$id_rubrique = marly_rubrique_association($a['nom']);
		if ($id_rubrique) {
			sql_updateq('spip_associations', array('id_rubrique' => $id_rubrique),
				'id_association = ' . intval($a['id_association']));
			$faites++;
		}
	}
	spip_log("marly : $faites rubrique(s) d'association creee(s) en rattrapage", 'marly');
}

function marly_vider_tables($nom_meta_base_version) {
	/* On supprime les tables : ce sont les nôtres, personne d'autre ne les
	   lit. Les réglages, eux, restent — désactiver le plugin ne doit pas
	   faire perdre le numéro de téléphone de la mairie. Ils seront effacés
	   avec la meta ci-dessous seulement si l'on désinstalle vraiment. */
	sql_drop_table('spip_lieux');
	sql_drop_table('spip_associations');
	sql_drop_table('spip_raccourcis');
	sql_drop_table('spip_elus');
	sql_drop_table('spip_demarches');
	sql_drop_table('spip_lettres');
	sql_drop_table('spip_abonnes');
	sql_drop_table('spip_manifestations');
	sql_drop_table('spip_reservations');
	sql_drop_table('spip_salles');

	effacer_meta($nom_meta_base_version);
}
