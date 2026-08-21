<?php
/**
 * Le socle national des démarches, et les icônes disponibles.
 * ---------------------------------------------------------------------------
 * LE SOCLE est la liste des démarches identiques dans les 34 000 communes de
 * France. Il est posé à l'installation, puis il appartient à la mairie : elle
 * modifie, dépublie, supprime. Rien n'est reposé ensuite : une fiche qu'on a
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
			'resume'  => 'La copie ou l’extrait d’un acte se demande à la mairie du lieu de l’événement, pas à celle du domicile.',
			'qui'     => 'La personne concernée, son conjoint, ses ascendants ou descendants, et son représentant légal. Pour un acte de plus de 75 ans, toute personne peut en obtenir copie.',
			'comment' => 'La demande se fait sur place, par courrier ou en ligne, au choix. Précisez la date de l’événement et les noms et prénoms des parents : ce sont eux qui permettent de retrouver l’acte.',
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
			'comment' => 'Présentez-vous à la mairie avec le certificat établi par le médecin. Dans ce moment difficile, sachez que l’entreprise de pompes funèbres peut faire la déclaration à votre place, et que le secrétariat vous guide pour la suite.',
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
			'comment' => 'Retirez d’abord le dossier au secrétariat, puis rapportez-le complet. La date de la cérémonie se choisit ensuite avec la mairie, une fois le dossier examiné.',
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
			'comment' => 'Prenez rendez-vous au secrétariat : vous viendrez à deux, avec votre convention et les pièces du dossier. L’enregistrement se fait sur place.',
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
			'comment' => 'La démarche se fait à la mairie du domicile, en quelques minutes. Un parent peut venir à la place du jeune, avec le livret de famille.',
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
			'comment' => 'L’inscription se fait en ligne en quelques minutes, ou au secrétariat de la mairie si vous préférez être accompagné.',
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
			'lien'    => 'https://www.service-public.gouv.fr/particuliers/vosdroits/F1411',
		),
		array(
			'titre'   => 'Demander une attestation d’accueil',
			'famille' => 'mairie', 'icone' => 'ri-home-4-line', 'rang' => 80,
			'resume'  => 'Nécessaire pour héberger un étranger venant en France pour une visite privée de moins de trois mois.',
			'qui'     => 'La personne qui héberge, domiciliée dans la commune.',
			'comment' => 'La demande se dépose au secrétariat. Elle est instruite avant d’être délivrée : prévoyez ce délai avant la venue de votre invité.',
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
			'comment' => 'Le dossier se dépose à la mairie, sur place ou par voie électronique. N’hésitez pas à passer en parler avant : vérifier qu’un dossier est complet évite des semaines d’allers-retours.',
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
			'comment' => 'Le formulaire se dépose à la mairie, sur place ou par voie électronique. C’est une précaution qui vaut la peine avant de signer : elle vous dit ce que le terrain permet vraiment.',
			'pieces'  => 'Formulaire Cerfa et plan de situation.',
			'cout'    => 'Gratuit',
			'delai'   => 'Un mois pour un certificat d’information, deux mois pour un certificat opérationnel',
			'ou'      => $mairie,
			'lien'    => 'https://www.service-public.gouv.fr/particuliers/vosdroits/F1633',
		),

		// --- Ce qui ne se fait pas en mairie ---------------------------------
		array(
			'titre'   => 'Demander un second livret de famille',
			'famille' => 'mairie', 'icone' => 'ri-file-text-line', 'rang' => 15,
			'resume'  => 'En cas de perte, de vol ou de séparation, un duplicata du livret de famille se demande à la mairie du domicile.',
			'qui'     => 'L’un des titulaires du livret : chacun des époux, ou le parent.',
			'comment' => "-# Remplissez le formulaire de demande au secrétariat de la mairie de votre domicile.\n-# La mairie transmet aux communes des actes concernés, qui reconstituent le livret.\n-# Vous êtes prévenu quand le duplicata est prêt.",
			'pieces'  => 'Pièce d’identité, justificatif de domicile, et tout élément sur les actes à reporter.',
			'cout'    => 'Gratuit',
			'delai'   => 'Quelques semaines, selon le nombre de communes à solliciter',
			'ou'      => 'Au secrétariat de mairie, aux heures de permanence.',
			'socle'   => 1,
			'lien'    => 'https://www.service-public.gouv.fr/particuliers/vosdroits/F11994',
		),
		array(
			'titre'   => 'Changer de prénom',
			'famille' => 'mairie', 'icone' => 'ri-file-text-line', 'rang' => 17,
			'resume'  => 'Le changement de prénom se demande directement à l’état civil de la mairie, sans passer par un juge.',
			'qui'     => 'Toute personne majeure, ou un mineur représenté par ses parents. Il faut un intérêt légitime : prénom difficile à porter, usage ancien d’un autre prénom.',
			'comment' => "-# Constituez le dossier : formulaire, actes d’état civil, et les preuves de votre intérêt légitime.\n-# Déposez-le à la mairie de votre domicile ou de votre lieu de naissance.\n-# L’officier d’état civil décide. En cas de doute, il saisit le procureur de la République.",
			'pieces'  => 'Copie intégrale de l’acte de naissance, pièce d’identité, justificatif de domicile, et les pièces qui montrent l’intérêt légitime.',
			'cout'    => 'Gratuit',
			'delai'   => 'Variable selon le dossier',
			'ou'      => 'À la mairie du domicile ou du lieu de naissance.',
			'socle'   => 1,
			'lien'    => 'https://www.service-public.gouv.fr/particuliers/vosdroits/F885',
		),
		array(
			'titre'   => 'Acheter ou renouveler une concession au cimetière',
			'famille' => 'mairie', 'icone' => 'ri-map-pin-2-line', 'rang' => 75,
			'resume'  => 'L’emplacement au cimetière communal se demande à la mairie, qui fixe les durées et les tarifs.',
			'qui'     => 'Toute personne souhaitant un emplacement pour elle-même ou sa famille.',
			'comment' => "-# Adressez-vous au secrétariat de mairie, qui tient le registre du cimetière.\n-# Choisissez la durée de concession parmi celles que propose la commune.\n-# L’acte de concession est établi et le règlement se fait auprès de la mairie.",
			'pieces'  => 'Pièce d’identité et justificatif de domicile.',
			'cout'    => 'Selon la durée choisie : le tarif est fixé par délibération du conseil municipal',
			'delai'   => 'Immédiat pour la demande',
			'ou'      => 'Au secrétariat de mairie, aux heures de permanence.',
			'socle'   => 1,
			'lien'    => 'https://www.service-public.gouv.fr/particuliers/vosdroits/F31001',
		),
		array(
			'titre'   => 'Ouvrir une buvette pour une fête (débit de boissons temporaire)',
			'famille' => 'mairie', 'icone' => 'ri-team-line', 'rang' => 78,
			'resume'  => 'Pour tenir une buvette lors d’une fête ou d’un événement, une association doit demander l’autorisation du maire.',
			'qui'     => 'Les associations, dans la limite de cinq autorisations par an.',
			'comment' => "-# Adressez la demande écrite au maire au moins quinze jours avant l’événement, en précisant la date, le lieu et la nature de la fête.\n-# Le maire délivre l’autorisation, limitée aux boissons sans alcool et aux boissons fermentées (bière, vin, cidre).\n-# Affichez l’autorisation sur place le jour de l’événement.",
			'pieces'  => 'Demande écrite datée et signée par le responsable de l’association.',
			'cout'    => 'Gratuit',
			'delai'   => 'Déposez la demande au moins quinze jours avant la fête',
			'ou'      => 'Au secrétariat de mairie, aux heures de permanence.',
			'a_savoir' => 'Les alcools forts sont interdits dans les buvettes temporaires, quelle que soit l’autorisation.',
			'socle'   => 1,
			'lien'    => 'https://www.service-public.gouv.fr/particuliers/vosdroits/F24345',
			'lien_faire' => 'https://www.service-public.gouv.fr/particuliers/vosdroits/R24391',
		),
		array(
			'titre'   => 'Carte d’identité ou passeport',
			'famille' => 'ailleurs', 'icone' => 'ri-government-line', 'rang' => 200,
			'resume'  => 'Ces titres ne se délivrent que dans les mairies équipées d’un dispositif de recueil. Vérifiez avant de vous déplacer.',
			'qui'     => 'Toute personne de nationalité française.',
			'comment' => "-# Faites la pré-demande en ligne sur [le site de l'ANTS->https://ants.gouv.fr/]. Elle est gratuite : seul le timbre fiscal du passeport est payant, et il s'achète sur [timbres.impots.gouv.fr->https://timbres.impots.gouv.fr/].\n-# Prenez rendez-vous dans une mairie équipée. N'importe laquelle, quel que soit votre domicile : les plus proches sont sur [la carte des mairies habilitées->https://passeport.ants.gouv.fr/services/geolocaliser-une-mairie-habilitee].\n-# Déposez le dossier au rendez-vous. La personne concernée doit être présente, ses empreintes sont recueillies.\n-# Un SMS vous prévient quand le titre est prêt : retirez-le, sans rendez-vous, là où vous avez déposé le dossier.",
			'a_savoir' => 'Un titre non retiré dans les trois mois est détruit, sans remboursement. Méfiez-vous des sites payants qui imitent les sites officiels : la pré-demande et le rendez-vous sont gratuits. Avec l’application France Identité, votre nouvelle carte peut aussi prouver votre identité en ligne.',
			'pieces'  => 'Photo d’identité récente ([la norme photo expliquée->https://www.service-public.gouv.fr/particuliers/vosdroits/F10619]), justificatif de domicile, timbre fiscal pour un passeport, et ancien titre s’il s’agit d’un renouvellement.',
			'cout'    => 'Carte d’identité gratuite. Passeport : 86 € pour un majeur',
			'delai'   => 'Variable selon les rendez-vous disponibles',
			'ou'      => 'Dans une mairie équipée d’un dispositif de recueil, sur rendez-vous. [La carte des mairies habilitées->https://passeport.ants.gouv.fr/services/geolocaliser-une-mairie-habilitee] est sur le site de l’ANTS.',
			'lien'      => 'https://www.service-public.gouv.fr/particuliers/vosdroits/F14929',
			'lien_faire' => 'https://passeport.ants.gouv.fr/services/geolocaliser-une-mairie-habilitee',
		),
		array(
			'titre'   => 'Carte grise (certificat d’immatriculation)',
			'famille' => 'ailleurs', 'icone' => 'ri-bus-2-line', 'rang' => 210,
			'resume'  => 'Plus aucune démarche de carte grise ne se fait en mairie ni en préfecture depuis 2017.',
			'qui'     => 'Le titulaire du véhicule.',
			'comment' => 'La démarche se fait entièrement en ligne, sur [le site de l’ANTS->https://immatriculation.ants.gouv.fr/]. Méfiez-vous des sites payants qui imitent le site officiel : le vrai service ne facture que la taxe.',
			'pieces'  => 'Selon le cas : certificat de cession, contrôle technique, justificatif de domicile, permis de conduire.',
			'cout'    => 'Variable selon la puissance du véhicule et la région',
			'delai'   => 'Certificat provisoire immédiat',
			'ou'      => 'En ligne uniquement : plus aucun guichet ne reçoit cette démarche.',
			'lien'      => 'https://www.service-public.gouv.fr/particuliers/vosdroits/F1050',
			'lien_faire' => 'https://immatriculation.ants.gouv.fr/',
		),
		array(
			'titre'   => 'Permis de conduire',
			'famille' => 'ailleurs', 'icone' => 'ri-bus-2-line', 'rang' => 220,
			'resume'  => 'Inscription à l’examen, renouvellement, perte ou vol : tout se fait en ligne.',
			'qui'     => 'Le titulaire du permis, ou le candidat à l’examen.',
			'comment' => 'Tout se passe en ligne, sur [le site de l’ANTS->https://permisdeconduire.ants.gouv.fr/] : l’inscription à l’examen comme le renouvellement après une perte ou un vol.',
			'pieces'  => 'Photo d’identité numérique, justificatif de domicile, et selon le cas déclaration de perte ou de vol.',
			'cout'    => 'Gratuit dans la plupart des cas',
			'delai'   => 'Quelques semaines',
			'ou'      => 'En ligne uniquement : plus aucun guichet ne reçoit cette démarche.',
			'lien'      => 'https://www.service-public.gouv.fr/particuliers/vosdroits/F1922',
			'lien_faire' => 'https://permisdeconduire.ants.gouv.fr/',
		),
		array(
			'titre'   => 'Consulter le cadastre',
			'famille' => 'enligne', 'icone' => 'ri-map-pin-2-line', 'rang' => 230,
			'resume'  => 'Le plan cadastral de la commune est consultable et imprimable gratuitement en ligne.',
			'qui'     => 'Toute personne.',
			'comment' => 'Rendez-vous sur le site officiel du cadastre : cherchez la commune, puis la parcelle. La consultation et l’impression sont gratuites.',
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
			'comment' => 'Tout se passe depuis votre espace particulier sur [le site des impôts->https://www.impots.gouv.fr/]. En cas de difficulté, le service des impôts des particuliers se joint aussi par téléphone.',
			'pieces'  => 'Numéro fiscal, figurant sur votre dernier avis.',
			'cout'    => 'Gratuit',
			'delai'   => 'Selon le calendrier fiscal',
			'ou'      => 'En ligne, ou auprès du service des impôts des particuliers.',
			'lien_faire' => 'https://www.impots.gouv.fr/',
		),
	);
}
