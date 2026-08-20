<?php
/**
 * Le socle national des démarches, et les icônes disponibles.
 * ---------------------------------------------------------------------------
 * LE SOCLE est la liste des démarches identiques dans les 34 000 communes de
 * France. Il est posé à l'installation, puis il appartient à la mairie : elle
 * modifie, dépublie, supprime. Rien n'est reposé ensuite — une fiche qu'on a
 * supprimée ne doit pas revenir à la mise à jour suivante.
 *
 * CHAQUE FICHE RENVOIE vers service-public.gouv.fr au lieu de recopier la
 * réglementation. Ce n'est pas de la paresse : une commune qui recopie le
 * droit sur son site diffuse une information fausse deux ans plus tard, et
 * c'est elle qui en répond. Ce que la fiche dit en propre, c'est ce que la
 * mairie fait, elle, et que l'État ne peut pas savoir : où venir, quand, avec
 * quoi.
 *
 * L'étape suivante, quand la commune sera cliente, est le CO-MARQUAGE : la
 * DILA met les ~3 000 fiches « Vos droits et démarches » à disposition en XML,
 * gratuitement, sur convention signée par la commune. La structure ci-dessous
 * est faite pour l'accueillir sans être refaite.
 */

if (!defined('_ECRIRE_INC_VERSION')) {
	return;
}

/**
 * Les icônes proposées pour une démarche.
 *
 * Liste FERMÉE, et c'est volontaire : une icône absente du sprite ne provoque
 * aucune erreur, elle s'affiche vide. On ne laisse donc pas saisir un nom.
 * Toutes celles-ci figurent dans outils/palette-icones.txt.
 */
function marly_icones_demarches() {
	return array(
		'ri-file-text-line'      => 'Document, état civil',
		'ri-file-list-3-line'    => 'Formulaire, dossier',
		'ri-government-line'     => 'Mairie, administration',
		'ri-scales-3-line'       => 'Élections, démocratie',
		'ri-team-line'           => 'Famille, associations',
		'ri-home-4-line'         => 'Logement, urbanisme',
		'ri-tools-line'          => 'Travaux, voirie',
		'ri-map-pin-2-line'      => 'Terrain, plan',
		'ri-bus-2-line'          => 'Véhicule, transports',
		'ri-school-line'         => 'École, enfance',
		'ri-recycle-line'        => 'Déchets, tri',
		'ri-heart-pulse-line'    => 'Santé, social',
		'ri-plant-line'          => 'Environnement',
		'ri-water-flash-line'    => 'Eau, énergie',
		'ri-book-open-line'      => 'Bibliothèque, culture',
		'ri-calendar-check-line' => 'Réservation, rendez-vous',
	);
}

/** Les trois familles, et ce qu'elles veulent dire pour l'habitant. */
function marly_familles_demarches() {
	return array(
		'mairie'   => 'marly:famille_mairie',
		'enligne'  => 'marly:famille_enligne',
		'ailleurs' => 'marly:famille_ailleurs',
	);
}

/**
 * Le socle. Chaque entrée est une fiche prête à publier.
 *
 * « ou » est volontairement écrit de façon générique : c'est la première chose
 * que la mairie personnalisera, et c'est mieux qu'un champ vide, qui laisse
 * partir une fiche sans dire où aller.
 */
function marly_socle_demarches() {
	$mairie = 'Au secrétariat de la mairie, aux heures d’ouverture.';

	return array(

		// --- État civil -----------------------------------------------------
		array(
			'titre'   => 'Demander un acte de naissance, de mariage ou de décès',
			'famille' => 'mairie', 'icone' => 'ri-file-text-line', 'rang' => 10,
			'resume'  => 'La copie ou l’extrait d’un acte se demande à la mairie du lieu de l’événement — pas à celle du domicile.',
			'qui'     => 'La personne concernée, son conjoint, ses ascendants ou descendants, et son représentant légal. Pour un acte de plus de 75 ans, toute personne peut en obtenir copie.',
			'comment' => 'Sur place, par courrier, ou en ligne. Précisez la date de l’événement et les noms et prénoms des parents.',
			'pieces'  => 'Une pièce d’identité, et le livret de famille si vous l’avez.',
			'cout'    => 'Gratuit',
			'delai'   => 'Immédiat au guichet, quelques jours par courrier',
			'ou'      => $mairie . ' Si l’événement a eu lieu ailleurs, adressez-vous à la mairie de cette commune.',
			'lien'    => 'https://www.service-public.gouv.fr/particuliers/vosdroits/F1427',
		),
		array(
			'titre'   => 'Déclarer un décès',
			'famille' => 'mairie', 'icone' => 'ri-file-text-line', 'rang' => 20,
			'resume'  => 'La déclaration se fait dans les 24 heures à la mairie du lieu du décès.',
			'qui'     => 'Un proche, ou l’entreprise de pompes funèbres mandatée par la famille.',
			'comment' => 'En vous présentant à la mairie avec le certificat médical de décès.',
			'pieces'  => 'Le certificat médical de décès, une pièce d’identité du déclarant, et le livret de famille ou l’acte de naissance du défunt.',
			'cout'    => 'Gratuit',
			'delai'   => 'Dans les 24 heures',
			'ou'      => $mairie,
			'lien'    => 'https://www.service-public.gouv.fr/particuliers/vosdroits/F909',
		),
		array(
			'titre'   => 'Se marier',
			'famille' => 'mairie', 'icone' => 'ri-team-line', 'rang' => 30,
			'resume'  => 'Le mariage se célèbre dans la commune où l’un des deux futurs époux, ou l’un de leurs parents, a son domicile.',
			'qui'     => 'Les deux futurs époux, majeurs.',
			'comment' => 'Retirez le dossier au secrétariat, puis déposez-le complet. La date est fixée avec la mairie une fois le dossier reçu.',
			'pieces'  => 'Acte de naissance de moins de 3 mois, pièce d’identité, justificatif de domicile, et informations sur les témoins.',
			'cout'    => 'Gratuit',
			'delai'   => 'Comptez plusieurs semaines : publication des bans obligatoire pendant 10 jours',
			'ou'      => $mairie,
			'lien'    => 'https://www.service-public.gouv.fr/particuliers/vosdroits/F930',
		),
		array(
			'titre'   => 'Conclure un PACS',
			'famille' => 'mairie', 'icone' => 'ri-team-line', 'rang' => 40,
			'resume'  => 'Depuis 2017, le PACS s’enregistre en mairie et non plus au tribunal.',
			'qui'     => 'Deux personnes majeures, quel que soit leur sexe, vivant en couple.',
			'comment' => 'Prenez rendez-vous au secrétariat avec la convention et le dossier complet.',
			'pieces'  => 'Convention de PACS, déclaration conjointe, actes de naissance de moins de 3 mois, pièces d’identité.',
			'cout'    => 'Gratuit',
			'delai'   => 'Enregistrement le jour du rendez-vous',
			'ou'      => $mairie,
			'lien'    => 'https://www.service-public.gouv.fr/particuliers/vosdroits/F1618',
		),
		array(
			'titre'   => 'Se faire recenser à 16 ans',
			'famille' => 'mairie', 'icone' => 'ri-government-line', 'rang' => 50,
			'resume'  => 'Obligatoire dans les trois mois suivant le seizième anniversaire. L’attestation est exigée pour le permis de conduire et les examens.',
			'qui'     => 'Tout Français, fille ou garçon, à partir de 16 ans.',
			'comment' => 'À la mairie du domicile. Un parent peut faire la démarche si le jeune est mineur.',
			'pieces'  => 'Pièce d’identité, livret de famille, justificatif de domicile.',
			'cout'    => 'Gratuit',
			'delai'   => 'Attestation remise le jour même',
			'ou'      => $mairie,
			'lien'    => 'https://www.service-public.gouv.fr/particuliers/vosdroits/F870',
		),
		array(
			'titre'   => 'S’inscrire sur les listes électorales',
			'famille' => 'enligne', 'icone' => 'ri-scales-3-line', 'rang' => 60,
			'resume'  => 'À faire au plus tard le sixième vendredi précédant un scrutin. L’inscription est automatique à 18 ans, mais pas après un déménagement.',
			'qui'     => 'Tout électeur qui s’installe dans la commune, et tout jeune non inscrit d’office.',
			'comment' => 'En ligne en quelques minutes, ou au secrétariat de la mairie.',
			'pieces'  => 'Pièce d’identité en cours de validité et justificatif de domicile de moins de 3 mois.',
			'cout'    => 'Gratuit',
			'delai'   => 'Effective pour le scrutin suivant, sous réserve du délai légal',
			'ou'      => $mairie,
			'lien'      => 'https://www.service-public.gouv.fr/particuliers/vosdroits/F1965',
			'lien_faire' => 'https://www.service-public.gouv.fr/particuliers/vosdroits/R16396',
		),
		array(
			'titre'   => 'Faire légaliser une signature',
			'famille' => 'mairie', 'icone' => 'ri-file-text-line', 'rang' => 70,
			'resume'  => 'La mairie atteste que la signature apposée devant elle est bien la vôtre.',
			'qui'     => 'Toute personne domiciliée dans la commune.',
			'comment' => 'Présentez-vous au secrétariat avec le document NON SIGNÉ : la signature doit être apposée devant l’agent.',
			'pieces'  => 'Pièce d’identité et justificatif de domicile.',
			'cout'    => 'Gratuit',
			'delai'   => 'Immédiat',
			'ou'      => $mairie,
			'lien'    => 'https://www.service-public.gouv.fr/particuliers/vosdroits/F1414',
		),
		array(
			'titre'   => 'Demander une attestation d’accueil',
			'famille' => 'mairie', 'icone' => 'ri-home-4-line', 'rang' => 80,
			'resume'  => 'Nécessaire pour héberger un étranger venant en France pour une visite privée de moins de trois mois.',
			'qui'     => 'La personne qui héberge, domiciliée dans la commune.',
			'comment' => 'Sur place, au secrétariat. La demande est instruite avant délivrance.',
			'pieces'  => 'Pièce d’identité, justificatif de domicile, justificatifs de ressources, et informations sur la personne accueillie.',
			'cout'    => '30 € en timbre fiscal électronique',
			'delai'   => 'Environ un mois',
			'ou'      => $mairie,
			'lien'    => 'https://www.service-public.gouv.fr/particuliers/vosdroits/F2191',
		),

		// --- Urbanisme ------------------------------------------------------
		array(
			'titre'   => 'Déclaration préalable de travaux',
			'famille' => 'mairie', 'icone' => 'ri-tools-line', 'rang' => 100,
			'resume'  => 'Pour une clôture, un abri de jardin, un changement de fenêtres, un ravalement : les petits travaux qui modifient l’aspect extérieur.',
			'qui'     => 'Le propriétaire, ou son mandataire.',
			'comment' => 'Dépôt du dossier à la mairie, sur place ou par voie électronique. Depuis 2022, la commune est tenue de recevoir les demandes par voie électronique.',
			'pieces'  => 'Formulaire Cerfa, plan de situation, plan de masse, et représentation de l’aspect extérieur.',
			'cout'    => 'Gratuit',
			'delai'   => 'Un mois d’instruction',
			'ou'      => $mairie,
			'lien'    => 'https://www.service-public.gouv.fr/particuliers/vosdroits/F17578',
		),
		array(
			'titre'   => 'Permis de construire',
			'famille' => 'mairie', 'icone' => 'ri-home-4-line', 'rang' => 110,
			'resume'  => 'Pour une construction neuve, ou des travaux importants sur un bâtiment existant.',
			'qui'     => 'Le propriétaire, ou son mandataire.',
			'comment' => 'Dépôt du dossier à la mairie, sur place ou par voie électronique.',
			'pieces'  => 'Formulaire Cerfa, plans, notice descriptive, insertion paysagère. Le recours à un architecte est obligatoire au-delà de 150 m².',
			'cout'    => 'Gratuit',
			'delai'   => 'Deux mois pour une maison individuelle, trois mois dans les autres cas',
			'ou'      => $mairie,
			'lien'    => 'https://www.service-public.gouv.fr/particuliers/vosdroits/F1986',
		),
		array(
			'titre'   => 'Certificat d’urbanisme',
			'famille' => 'mairie', 'icone' => 'ri-map-pin-2-line', 'rang' => 120,
			'resume'  => 'Il indique les règles applicables à un terrain. À demander avant tout achat.',
			'qui'     => 'Toute personne, propriétaire ou non du terrain.',
			'comment' => 'Dépôt du formulaire à la mairie, sur place ou par voie électronique.',
			'pieces'  => 'Formulaire Cerfa et plan de situation.',
			'cout'    => 'Gratuit',
			'delai'   => 'Un mois pour un certificat d’information, deux mois pour un certificat opérationnel',
			'ou'      => $mairie,
			'lien'    => 'https://www.service-public.gouv.fr/particuliers/vosdroits/F1633',
		),

		// --- Ce qui ne se fait pas en mairie ---------------------------------
		array(
			'titre'   => 'Carte d’identité ou passeport',
			'famille' => 'ailleurs', 'icone' => 'ri-government-line', 'rang' => 200,
			'resume'  => 'Ces titres ne se délivrent que dans les mairies équipées d’un dispositif de recueil. Vérifiez avant de vous déplacer.',
			'qui'     => 'Toute personne de nationalité française.',
			'comment' => 'Faites d’abord la pré-demande en ligne sur le site de l’ANTS, puis prenez rendez-vous dans une mairie équipée. Vous pouvez vous rendre dans n’importe laquelle, quel que soit votre domicile.',
			'pieces'  => 'Photo d’identité récente, justificatif de domicile, timbre fiscal pour un passeport, et ancien titre s’il s’agit d’un renouvellement.',
			'cout'    => 'Carte d’identité gratuite. Passeport : 86 € pour un majeur',
			'delai'   => 'Variable selon les rendez-vous disponibles',
			'ou'      => 'Dans une mairie équipée d’un dispositif de recueil. La carte des mairies habilitées est sur le site de l’ANTS.',
			'lien'      => 'https://www.service-public.gouv.fr/particuliers/vosdroits/F14929',
			'lien_faire' => 'https://passeport.ants.gouv.fr/services/geolocaliser-une-mairie-habilitee',
		),
		array(
			'titre'   => 'Carte grise (certificat d’immatriculation)',
			'famille' => 'ailleurs', 'icone' => 'ri-bus-2-line', 'rang' => 210,
			'resume'  => 'Plus aucune démarche de carte grise ne se fait en mairie ni en préfecture depuis 2017.',
			'qui'     => 'Le titulaire du véhicule.',
			'comment' => 'Entièrement en ligne, sur le site de l’ANTS. Méfiez-vous des sites payants qui imitent le site officiel.',
			'pieces'  => 'Selon le cas : certificat de cession, contrôle technique, justificatif de domicile, permis de conduire.',
			'cout'    => 'Variable selon la puissance du véhicule et la région',
			'delai'   => 'Certificat provisoire immédiat',
			'ou'      => 'En ligne uniquement.',
			'lien'      => 'https://www.service-public.gouv.fr/particuliers/vosdroits/F1050',
			'lien_faire' => 'https://immatriculation.ants.gouv.fr/',
		),
		array(
			'titre'   => 'Permis de conduire',
			'famille' => 'ailleurs', 'icone' => 'ri-bus-2-line', 'rang' => 220,
			'resume'  => 'Inscription à l’examen, renouvellement, perte ou vol : tout se fait en ligne.',
			'qui'     => 'Le titulaire du permis, ou le candidat à l’examen.',
			'comment' => 'En ligne sur le site de l’ANTS.',
			'pieces'  => 'Photo d’identité numérique, justificatif de domicile, et selon le cas déclaration de perte ou de vol.',
			'cout'    => 'Gratuit dans la plupart des cas',
			'delai'   => 'Quelques semaines',
			'ou'      => 'En ligne uniquement.',
			'lien'      => 'https://www.service-public.gouv.fr/particuliers/vosdroits/F1922',
			'lien_faire' => 'https://permisdeconduire.ants.gouv.fr/',
		),
		array(
			'titre'   => 'Consulter le cadastre',
			'famille' => 'enligne', 'icone' => 'ri-map-pin-2-line', 'rang' => 230,
			'resume'  => 'Le plan cadastral de la commune est consultable et imprimable gratuitement en ligne.',
			'qui'     => 'Toute personne.',
			'comment' => 'Sur le site officiel du cadastre, en cherchant la commune puis la parcelle.',
			'pieces'  => 'Aucune.',
			'cout'    => 'Gratuit',
			'delai'   => 'Immédiat',
			'ou'      => 'En ligne. Le secrétariat peut vous aider si vous n’avez pas d’ordinateur.',
			'lien_faire' => 'https://www.cadastre.gouv.fr/',
		),
		array(
			'titre'   => 'Impôts et taxes',
			'famille' => 'ailleurs', 'icone' => 'ri-file-list-3-line', 'rang' => 240,
			'resume'  => 'Déclaration de revenus, taxe foncière, paiement : la mairie n’intervient pas.',
			'qui'     => 'Tout contribuable.',
			'comment' => 'Depuis votre espace particulier sur le site des impôts.',
			'pieces'  => 'Numéro fiscal, figurant sur votre dernier avis.',
			'cout'    => 'Gratuit',
			'delai'   => 'Selon le calendrier fiscal',
			'ou'      => 'En ligne, ou auprès du service des impôts des particuliers.',
			'lien_faire' => 'https://www.impots.gouv.fr/',
		),
	);
}
