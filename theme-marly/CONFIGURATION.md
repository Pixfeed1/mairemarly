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
