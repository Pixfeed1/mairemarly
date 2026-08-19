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

## 4. Ce qui n'est PAS pilotable ainsi

Il faut le dire clairement plutôt que de le laisser découvrir :

| Élément | Aujourd'hui | Pour le rendre pilotable |
|---|---|---|
| Téléphone, adresse, horaires | valeurs de repli dans le gabarit, lues par `#CONFIG{marly/...}` | il faut un écran de configuration, donc un plugin — ou les traiter comme du contenu |
| Réseaux sociaux de l'en-tête | adresses en dur | même chose |
| Photographie de la bannière | le logo du site (`Configuration ▸ Identité du site`) | déjà pilotable |
| Crédit photographique | `#CONFIG{marly/credit_photo}` | même remarque que le téléphone |

---

## 5. Ce qui reste à vérifier sur une vraie installation

Ces gabarits n'ont pas encore tourné sur un SPIP en fonctionnement — ils ont
été écrits et relus, pas exécutés. À contrôler au premier déploiement local :

- le critère `{type=Icônes}` avec son accent ;
- le critère `{titre_mot=Raccourcis}` ;
- le rendu de `#VIRTUEL` sur un article virtuel ;
- le comportement de `spip.php?article=NN` depuis la liste « Je souhaite… ».
