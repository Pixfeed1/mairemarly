# Ce que la mairie pilote depuis l'espace privé

Ce document dit, pour chaque partie du site, **où l'on clique** pour la
changer. Il sert à deux personnes : la secrétaire de mairie qui alimentera le
site, et le prestataire qui l'installera.

Rien de ce qui suit ne demande de plugin. Tout se fait avec les rubriques,
les articles et les mots-clés de SPIP.

---

## 1. À créer une seule fois, à l'installation

### Le groupe de mots-clés « Icônes »

`Édition ▸ Mots-clés ▸ Créer un groupe de mots-clés`

| Réglage | Valeur |
|---|---|
| Titre du groupe | `Icônes` — **exactement**, accent compris : les gabarits le cherchent sous ce nom |
| Contenu du groupe | Articles |
| Choix multiple | Non — une icône par raccourci |

Puis un mot-clé par ligne de `outils/palette-icones.txt` :

- **Titre** du mot-clé : le libellé français (`Maison, urbanisme`)
- **Descriptif** du mot-clé : le nom technique (`ri-home-4-line`)

Le descriptif n'est jamais affiché aux visiteurs. Il ne sert qu'à dire au
gabarit quel dessin sortir. La secrétaire, elle, ne voit que « Maison ».

> **La contrainte à connaître.** Le site n'embarque que les icônes de cette
> palette — c'est ce qui tient le fichier à 19 Ko au lieu de 346 Ko pour la
> bibliothèque complète. Une icône hors palette ne s'affichera pas. En
> ajouter une prend trente secondes, mais c'est une intervention technique :
> ajouter la ligne dans `outils/palette-icones.txt`, relancer
> `outils/construire-icones.py`, créer le mot-clé.

### Le groupe de mots-clés « Emplacements »

Même chemin. Titre : `Emplacements`, contenu : Articles, choix multiple :
**oui** — un même article peut être à la fois une démarche et un raccourci.
Il contient trois mots-clés :

| Mot-clé | Ce qu'il fait |
|---|---|
| `Raccourcis` | l'article devient un des six ronds « Accès rapides » |
| `Menu` | l'article devient une entrée du menu principal |
| `Démarches` | l'article entre dans la liste déroulante « Je souhaite… » et dans le pied de page |
| `Bannière` | le logo de l'article devient la photographie de la bannière d'accueil |

---

## 2. Ce qui se pilote ensuite, au quotidien

### Le menu principal

Le socle, ce sont les **rubriques à la racine**. En créer une l'ajoute au
menu. Pour l'ordre, on numérote les titres :

```
10. Ma mairie
20. Vivre au village
30. Bouger, sortir
```

SPIP masque le numéro à l'affichage : le visiteur lit « Ma mairie ».

Une rubrique ne peut pointer que sur elle-même. Pour mettre au menu autre
chose — le site de France Services, service-public.fr, un article isolé — on
crée un **article portant le mot-clé `Menu`**. Ces entrées viennent après les
rubriques, dans la limite de quatre.

### Les six accès rapides

Un **article portant le mot-clé `Raccourcis`**, et trois choses :

| Ce qu'on veut | Où on le met |
|---|---|
| Le libellé | le titre de l'article (`10. Comptes rendus`) |
| L'icône | un mot-clé du groupe `Icônes` |
| La destination | voir ci-dessous |

Tant qu'aucun article ne porte le mot-clé, ce sont six destinations par
défaut qui s'affichent. Le site n'est jamais vide.

### La photographie de la bannière

Un **article portant le mot-clé `Bannière`** :

| Ce qu'on veut | Où on le met |
|---|---|
| La photographie | le **logo de l'article** (colonne de droite, « Ajouter un logo ») |
| Le crédit photographique | le **descriptif** de l'article |

On peut en préparer plusieurs et changer au fil des saisons : c'est la plus
récemment publiée qui s'affiche.

> **Pas dans « Logo du site ».** Ce champ porte un nom qui promet un logo ;
> y déposer un logo donnerait un logo étiré sur 1920 px en travers de la
> bannière. Il reste libre pour ce que son nom annonce.

### La liste « Je souhaite… »

Un **article portant le mot-clé `Démarches`**. Le titre devient la ligne de la
liste, l'article lui-même est la page qui explique la démarche.

S'il n'y en a aucun, la liste déroulante disparaît au profit d'un simple lien
vers la rubrique. Une liste vide serait pire que pas de liste.

---

## 3. Faire pointer un raccourci ailleurs que sur son article

C'est une fonction native de SPIP, l'**article virtuel**. Dans le champ
« Chapeau » de l'article, on écrit `=` suivi de l'adresse :

| Ce qu'on écrit dans le chapeau | Où mène le raccourci |
|---|---|
| *(rien)* | l'article lui-même |
| `=spip.php?rubrique3` | la rubrique 3 du site |
| `=https://www.service-public.fr/` | le site extérieur |

Les liens vers l'extérieur portent automatiquement un petit pictogramme et la
mention « (site extérieur) », lue par les lecteurs d'écran.

---

## 4. Les réglages : un écran, des champs étiquetés

Tout ce qui n'est pas du contenu — coordonnées, horaires, réseaux sociaux —
se saisit au même endroit :

**Configuration ▸ Réglages de la commune**

| Bloc | Champs |
|---|---|
| Coordonnées de la mairie | téléphone, courriel, adresse, code postal, commune, horaires |
| Réseaux sociaux | Facebook, Instagram, YouTube, X, LinkedIn, TikTok, WhatsApp, Mastodon, Bluesky |
| Application d'alerte | nom et adresse (PanneauPocket, IntraMuros…) |

**Un champ laissé vide n'affiche rien.** Une commune qui n'a pas de TikTok
n'a pas d'icône TikTok, sans avoir eu quoi que ce soit à désactiver.

Les adresses web sont vérifiées à l'enregistrement : une adresse qui ne
commence pas par `https://` est refusée sur-le-champ, plutôt qu'enregistrée
et découverte morte des mois plus tard.

Cet écran vient du plugin `plugin-marly/`, livré dans le même dépôt. Il
n'ajoute rien d'autre : ni table, ni champ, ni traitement. Il existe pour que
déclarer un Facebook ne demande pas de créer un article.

---

## 5. Ce qui reste à vérifier sur une vraie installation

Ces gabarits n'ont pas encore tourné sur un SPIP en fonctionnement — ils ont
été écrits et relus, pas exécutés. À contrôler au premier déploiement local :

- le critère `{type=Icônes}` avec son accent ;
- le critère `{titre_mot=Raccourcis}` ;
- le rendu de `#VIRTUEL` sur un article virtuel ;
- le comportement de `spip.php?article=NN` depuis la liste « Je souhaite… ».


---

## 6. Réserver une salle

C'est une application, pas une page : deux tables en base, un cycle de
décisions, des courriels. Elle vit dans `plugin-marly/`.

### Créer une salle

`Édition ▸ Salles à louer ▸ Créer une salle`

| Champ | Ce qu'on y met |
|---|---|
| Nom | « Salle des fêtes », « Salle du conseil » |
| Capacité | le nombre autorisé par la commission de sécurité, pas le nombre de chaises |
| Tarifs | texte libre : « 80 € la journée », « gratuit pour les associations » |
| Caution | idem |
| Délai minimum | en jours. Une demande faite plus tard est refusée automatiquement |
| Délai maximum | en jours. Évite les réservations posées deux ans à l'avance |
| Description | ce qui est fourni : tables, chaises, cuisine, vaisselle |
| Ouverte à la réservation | tant que c'est « non », la salle n'apparaît pas sur le site |

Les délais sont **par salle**, parce qu'ils ne sont pas les mêmes partout —
Bidart impose 6 mois maximum sur une salle et 1 à 3 mois sur l'autre.

### Le cycle d'une réservation

```
        formulaire public
               │
               ▼
          ┌─────────┐   la mairie accepte    ┌──────────┐
          │ demande │ ─────────────────────► │ acceptée │ ── le créneau est pris
          └─────────┘                        └──────────┘
               │                                   │
               │ la mairie refuse                  │ la mairie annule
               ▼                                   ▼
          ┌─────────┐                        ┌──────────┐
          │ refusée │                        │ annulée  │
          └─────────┘                        └──────────┘
```

**Seul « acceptée » bloque un créneau.** Deux personnes peuvent demander le
même samedi : c'est normal, et c'est à la mairie de trancher. Un logiciel qui
interdirait la seconde demande cacherait le conflit au lieu de le montrer.

L'écran `Édition ▸ Réservations de salles` signale explicitement les
concurrences : « Attention, ce créneau est déjà accordé à… »

### Ce qui est refusé automatiquement

- une date passée ;
- une date hors des délais de la salle ;
- un créneau déjà accordé.

Le demandeur le voit **avant** d'avoir rempli le reste du formulaire.

### Les courriels

| Quand | Qui reçoit |
|---|---|
| Demande déposée | le demandeur (accusé de réception) **et** la mairie |
| Acceptée | le demandeur |
| Refusée | le demandeur, avec le motif saisi par la mairie |
| Annulée | le demandeur |

L'adresse de la mairie est celle des réglages ; à défaut, celle du webmestre.

### La limite qu'il faut connaître

Le verrouillage se joue **à l'acceptation**, pas à la demande. Deux agents
qui accepteraient deux demandes concurrentes à quelques secondes d'intervalle
sont départagés par une transaction : la seconde reçoit « le créneau vient
d'être accordé à… » et n'est pas enregistrée. La salle ne peut pas être
louée deux fois.

En revanche il n'y a **pas de paiement en ligne**. Une commune ne peut pas
brancher Stripe ou PayPal : les encaissements publics passent par PayFiP, un
service de la DGFiP qui demande une démarche administrative. À traiter
séparément si la mairie le souhaite.


---

## 7. Où trouver quoi dans l'espace privé

| Menu | Écran | Ce qu'on y fait |
|---|---|---|
| **Édition** | Réservations de salles | accepter ou refuser les demandes |
| | Lettres d'information | rédiger, s'envoyer un essai, envoyer, suivre |
| | Abonnés à la lettre d'information | la liste, et l'export CSV |
| | Événements et inscriptions | créer un événement, voir les inscrits |
| | Salles à louer | créer une salle, ses tarifs, ses délais |
| **Configuration** | Réglages de la commune | téléphone, adresse, horaires, réseaux sociaux |

### Envoyer une lettre, dans l'ordre

1. `Lettres d'information ▸ Rédiger une lettre` — objet, accroche, texte.
2. **`M'envoyer un essai`** — et relire dans sa propre boîte. C'est le dernier
   moment où une coquille se rattrape.
3. `Envoyer maintenant` — le nombre d'abonnés est annoncé, une confirmation
   est demandée.
4. Le compteur avance tout seul, par lots de 25. **On peut fermer la page.**

Une lettre partie ne se modifie plus : le formulaire se ferme dès l'envoi
lancé. Laisser modifier ferait croire qu'on peut corriger ce que les gens ont
déjà reçu.

En cas d'erreur, `Arrêter l'envoi` stoppe les envois restants — mais les
courriels déjà partis sont partis. L'écran le dit en toutes lettres plutôt que
de le laisser espérer.

## Quand une association demande à entrer dans l'annuaire

Le bas de la page Associations du site porte un bouton « Signalez-la à la
mairie ». L'association y remplit sa fiche elle-même : nom, thème, activité,
la personne qui la gère et son courriel. Ce qui se passe ensuite :

1. La fiche arrive **en attente**, invisible du public, et vous recevez un
   courriel. Répondre à ce courriel répond directement au demandeur.
2. Ouvrez `Édition ▸ Associations`, relisez la fiche, corrigez au besoin,
   choisissez « Publiée » et enregistrez. Pour refuser, supprimez la fiche.
3. À la publication, tout le reste est automatique : la personne reçoit la
   confirmation, un accès rédacteur lui est créé avec son courriel comme
   identifiant, et le message lui donne le lien pour choisir son mot de
   passe. Aucun mot de passe ne circule par courriel.

Le compte créé est un compte **rédacteur** : l'association écrit ses
articles dans sa rubrique, et c'est vous qui publiez, comme pour tout ce qui
paraît sur le site.

## Le conseil municipal

`Édition ▸ Élus`. Une fiche par élu. La page publique les range toute seule,
et c'est le champ **Fonction** qui décide :

| Ce que vous écrivez dans Fonction | Où la personne apparaît |
|---|---|
| Commence par « Maire » | En tête, seule et en grand |
| Contient « adjoint » | Dans « Les adjoints », avec sa délégation |
| Contient « conseill » | Dans « Les conseillers », en simple liste |
| Autre chose, « Secrétaire de mairie » par exemple | Nulle part : la page est celle du conseil, et la secrétaire n'est pas élue |

Le formulaire propose les formulations courantes dans une liste déroulante :
servez-vous en, elles sont écrites pour tomber juste.

L'ordre à l'intérieur de chaque groupe est donné par la **Fonction**
elle-même : écrivez « 1re adjointe au maire », « 2e adjoint au maire », et les
fiches se rangent dans cet ordre. Il n'y a pas de numéro à saisir à part.

> **D'où vient l'ordre des adjoints ?** Du procès-verbal d'installation du
> conseil, voté en séance. Ce n'est pas au site de le décider, et ce n'est pas
> non plus à un annuaire en ligne : `outils/elus-officiels.sh` interroge le
> Répertoire national des élus, tenu par les préfectures, et affiche la
> composition officielle du conseil. Le répertoire donne le rang, jamais le
> contenu de la délégation : celui-là ne figure que dans l'arrêté signé par le
> maire.

**Le champ Délégation est le plus important de la page.** Le nom du premier
adjoint ne sert à rien à un habitant ; savoir qu'il a la charge de la voirie
lui évite d'appeler la mauvaise personne. Écrivez-la en clair : « Travaux,
voirie et bâtiments communaux ».

La photographie est facultative. Sans elle, la fiche affiche le sceau de la
commune sur fond vert.

### La fiche d'un élu

Chaque élu a sa page, atteinte en cliquant sur son nom depuis la page du
conseil. On y trouve sa fonction, sa délégation, ses contacts, sa
photographie si vous en déposez une, et le champ **Parcours**.

Le Parcours est facultatif et c'est vous qui l'écrivez : depuis quand la
personne est élue, ce qu'elle a porté, ce qui lui tient à cœur. Quelques
lignes suffisent. Sans lui, la fiche reste correcte : elle affiche ce qu'elle
sait.

Sur la fiche du maire, et sur elle seule, un bloc explique le rôle du maire :
officier d'état civil, exécutant des décisions du conseil, détenteur des
pouvoirs de police. **Ce texte n'est pas à saisir** : il est le même pour
toutes les communes de France, il vient de la loi, et il est écrit dans le
site. Vous n'avez rien à en faire.

La date de mise à jour affichée en bas de fiche est tenue par SPIP : elle
change toute seule à chaque enregistrement.

### La date de la prochaine séance

Elle n'est pas à saisir sur cette page : elle vient de l'agenda. Créez
l'événement dans `Édition ▸ Réservations ▸ Événements` avec « Conseil
municipal » dans le titre, et la page l'annonce d'elle-même. Rien à tenir à
jour à deux endroits.

### La photographie du bandeau

Comme la bannière d'accueil : publiez un article, mettez-lui le mot-clé
**Bannière ma mairie**, déposez-y la photo. Le crédit du photographe se met
dans le descriptif de l'article. Sans article, la page reprend la bannière
d'accueil ; sans elle, le paysage dessiné. La page n'est jamais nue.

## Les quatre pages légales

Mentions légales, politique de confidentialité, déclaration d'accessibilité,
crédits. Toutes les quatre marchent pareil : **un article portant le mot-clé
du même nom.**

| La page | Le mot-clé de l'article | Ce qu'on y écrit |
|---|---|---|
| Mentions légales | `Mentions légales` | éditeur, directeur de la publication, hébergeur |
| Politique de confidentialité | `Confidentialité` | ce que le site enregistre, et les droits des visiteurs |
| Déclaration d'accessibilité | `Accessibilité` | le niveau de conformité et comment signaler un obstacle |
| Crédits | `Crédits` | polices, photographies, logiciels, prestataires |

Tant que l'article n'existe pas, la page dit franchement qu'elle est en cours
de rédaction. **C'est voulu et il ne faut pas y toucher** : une mention légale
approximative est pire qu'absente, elle a l'air d'engager la commune sur des
informations fausses. Ces textes-là engagent la mairie, ils ne peuvent pas
être posés par un prestataire à sa place.

Pour les crédits, voici ce qu'il y a à déclarer aujourd'hui : les polices
Alegreya, Alegreya Sans, Open Sans et Caveat, sous licence SIL Open Font ;
les pictogrammes Remix Icon, sous licence Apache 2.0 ; le moteur SPIP, sous
licence GNU GPL. Les photographies restent à créditer au fur et à mesure que
la mairie en dépose.

## La photographie d'une rubrique

Chaque rubrique s'ouvre désormais sur un bandeau, comme les pages du conseil
municipal ou de l'urbanisme. L'image de ce bandeau, **c'est le logo de la
rubrique** : `Édition ▸ Rubriques`, ouvrez-en une, et déposez une image dans
« Logo ».

| Rubrique | Ce qu'on y met |
|---|---|
| Comptes rendus | la façade de la mairie |
| Travaux et projets | un chantier en cours |
| Actualités | la fête du village, la brocante |

Sans logo, le bandeau affiche le paysage dessiné du site. La page n'est jamais
nue, mais elle n'est pas non plus identique partout : une rubrique avec sa
photo se reconnaît d'un coup d'œil.

Prenez des images **larges**, au moins 1600 pixels de côté : le bandeau occupe
toute la largeur de l'écran et n'affiche qu'une bande horizontale. Une photo
verticale y sera coupée en haut et en bas.

## Urbanisme et travaux

La page répond à la question qui arrive vraiment au guichet : *« je veux poser
un abri de jardin, faut-il déclarer ? »* Les seuils, les délais, l'affichage du
panneau, le certificat d'urbanisme : tout cela est du droit national, écrit
dans le site, **vous n'avez rien à saisir** et rien ne se périme.

### Les trois lignes que vous seuls connaissez

`Configuration ▸ Réglages de la commune`, bloc **Urbanisme**. Trois champs, et
ce sont les trois qui changent d'une commune à l'autre :

| Champ | Pourquoi il compte |
|---|---|
| Le document d'urbanisme applicable | PLU, plan intercommunal, carte communale, ou aucun des trois |
| Qui délivre les autorisations | Avec un document local, le maire. Sans document, l'État, et l'instruction part à la direction départementale des territoires |
| Les clôtures | La déclaration n'est obligatoire que si le conseil l'a votée |

**Tant que les trois sont vides, le bloc n'apparaît pas sur le site.** C'est
voulu : écrire que l'État délivre les permis quand c'est le maire envoie les
habitants frapper à la mauvaise porte, et rien n'est pire qu'une mairie qui se
trompe sur sa propre compétence.

> **Vous ne savez pas quoi mettre ?** `outils/urbanisme-officiel.sh` interroge
> le Géoportail de l'urbanisme, qui publie le document en vigueur commune par
> commune. Les clôtures, elles, ne sont écrites nulle part ailleurs que dans
> vos délibérations.

### C'est une rubrique, et son titre compte

Créez une rubrique **Urbanisme** sous « Ma mairie ». Elle entre alors toute
seule dans le menu du haut, et vous pouvez y publier des articles comme dans
n'importe quelle rubrique : enquête publique, révision du plan intercommunal,
réfection d'une rue. Ils s'affichent sous les explications.

> **Le titre doit commencer par « Urbanisme ».** C'est à ça que le site la
> reconnaît. « Urbanisme et travaux » convient, « L'urbanisme » non : la
> rubrique retomberait sur la présentation ordinaire et perdrait les paliers,
> les délais et le bloc local. Même mécanisme que la rubrique « Comptes
> rendus », que la page du conseil municipal repère de la même façon.

Le **texte** de la rubrique sert d'introduction, au-dessus des trois paliers.
Laissé vide, le site met la sienne. Vous avez le dernier mot sans avoir à
recopier quoi que ce soit.

## L'annuaire des commerces et services

`Édition ▸ Commerces`. Une fiche par commerçant, professionnel de santé,
artisan ou service du village.

| Champ | Ce qu'on met |
|---|---|
| Nom du commerce | Le nom sous lequel les habitants le connaissent, pas la raison sociale |
| Catégorie | Commerces, Santé, Artisans ou Services. Elle s'affiche en étiquette jaune au-dessus du nom |
| Activité | Ce qu'on y trouve, en une ou deux phrases |
| Responsable | Facultatif |
| Téléphone | **Le champ qui compte.** Il devient cliquable : depuis un téléphone, on appuie et l'appel part |
| Adresse | La rue et le numéro. La carte se place toute seule à l'enregistrement |
| Horaires | Écrits comme vous les diriez : « Du mardi au dimanche, de 7h à 13h » |

Il faut **au moins un moyen de contact** — téléphone, courriel ou site — sinon
la fiche est refusée. Ce n'est pas une chicane : un annuaire sans numéro ne
répond à aucune des questions qu'on lui pose.

La photographie est facultative. Sans elle, la fiche affiche le sceau de la
commune sur fond vert, et la liste garde sa régularité.

### Les dix-neuf fiches de départ

L'ancien site donnait dix-neuf noms et rien d'autre : ni téléphone, ni
adresse, ni horaires. Ces dix-neuf fiches ont été créées **en brouillon**,
avec le nom, la catégorie et l'activité.

Elles n'apparaissent pas sur le site tant qu'elles sont en brouillon, et
c'est voulu : une fiche sans numéro ne sert à rien. La liste dans
`Édition ▸ Commerces` est donc une liste de travail. Pour chacune : appeler
le commerçant, noter le numéro et les horaires, choisir « Publiée »,
enregistrer.

Cette liste date d'au moins dix ans. Certains commerces ont sans doute fermé,
d'autres ouvert : c'est aussi ce que ce tour d'appels permet de vérifier. Une
fiche d'un commerce fermé se supprime.

### Une correction signalée par un commerçant

Chaque fiche du site porte, en haut à droite, un bouton crayon qui écrit à la
mairie avec le nom du commerce en objet. Le bas de l'annuaire porte le même
lien. C'est par là qu'un horaire faux se fait corriger, sans que le
commerçant ait besoin d'un compte.

**Listez tous les commerces, sans exception, et laissez l'ordre
alphabétique faire le tri.** Le jour où un commerçant demande pourquoi son
voisin y est et pas lui, la réponse doit être « tout le monde y est,
appelez-nous ». Mettre un commerce en avant, c'est de la publicité faite par
une collectivité, et ça se retourne contre elle.

## Donner un compte à une association

Une association qui veut publier ses actualités écrit ses propres articles.
La mairie les relit et les publie : **le maire est directeur de publication**,
il répond juridiquement de tout ce qui paraît, y compris de ce qu'une
association écrit. C'est la raison du partage, et elle n'est pas
administrative.

### 1. Créer la rubrique

`Édition ▸ Rubriques ▸ Créer une rubrique`, à l'intérieur de « Vie
associative ». Une rubrique par association qui publie.

### 2. Créer le compte

`Édition ▸ Auteurs ▸ Créer un auteur`.

| Champ | Ce qu'on met |
|---|---|
| Nom | Le nom de l'association, pas celui du président |
| Adresse électronique | Celle de l'association |
| Statut | **Rédacteur** |

Le compte appartient à **l'association**, jamais à la personne. Quand le
président change, la mairie modifie l'adresse et réinitialise le mot de
passe : elle ne recrée rien, et l'historique des articles reste cohérent.

Un compte par association, et non un compte partagé : on sait qui a écrit
quoi, ce qui compte le jour où un texte pose problème, et un mot de passe
commun finit toujours par circuler.

### 3. Relier la fiche à la rubrique

`Édition ▸ Associations`, ouvrir la fiche, choisir la rubrique dans
« Rubrique où l'association publie ». Ses articles publiés s'afficheront
alors sur sa fiche.

### Ce que peut et ne peut pas un rédacteur

- **Il peut** écrire un article, le modifier tant qu'il n'est pas publié, y
  joindre des images, et le proposer à la publication.
- **Il ne peut pas** publier, modifier un article publié, toucher aux
  rubriques, aux démarches, aux élus, ni aux réglages du site.

L'article proposé apparaît à la mairie dans `Publication ▸ Suivi de la
publication`. Elle le lit, corrige si besoin, et publie.

## Écrire un article

Tout article, de la mairie ou d'une association, prend automatiquement
l'habillage du site : rien à régler. Quelques gestes suffisent pour
enrichir le texte :

| Vous tapez | Vous obtenez |
|---|---|
| `{{{Le programme}}}` | un intertitre vert |
| `<img2\|gauche>` ou `<img3\|droite>` | la photo numéro 2 (ou 3) posée dans le texte, que le texte habille |
| `<quote>Merci aux bénévoles.</quote>` | une citation posée sur un filet vert |
| `<pratique>La demande se fait en mairie.</pratique>` | un encadré « En pratique » sur fond crème |
| `<important>Inscriptions avant le 15 juin.</important>` | un encadré jaune « À ne pas manquer » |
| des lignes commençant par `-#` | des étapes numérotées à pastilles vertes |
| des lignes `\| Matériel \| Tarif \|` | un tableau habillé (mettez `{{ }}` autour des titres de colonnes) |
| `<bouton>[Télécharger le programme->doc5]</bouton>` | un grand bouton vert centré |

Le numéro dans `<img2>` est celui que l'image porte dans la colonne
« Ajouter une image ou un document » de l'article.

Une composition de plus, pour mettre un contenu en avant : l'encart, la
photo d'un côté, le titre et le lien de l'autre.

```
<encart>
<img4>
{{{Le sentier de la Vallée rouvre}}}
Fermé après les crues de l'hiver, le sentier est de nouveau praticable.
[Voir l'itinéraire->article12]
</encart>
```

Le reste vient tout seul : l'image jointe à l'article s'affiche en grand
sous le titre, les autres images font la galerie « En images » avec leur
titre en légende et leur crédit, les PDF deviennent des cartes
« Télécharger », et quand l'article a trois intertitres ou plus, un
sommaire « Dans cet article » apparaît de lui-même.
La date affichée est celle de la publication ; pour un texte ancien,
choisissez « Afficher une date de rédaction antérieure » dans la colonne
de gauche de l'article.
