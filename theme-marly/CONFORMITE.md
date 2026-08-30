# Conformité — où en est le site

Un site de commune est soumis à des obligations que les sites privés n'ont
pas. Ce document dit, point par point, **ce qui est fait et ce qui ne l'est
pas**. Il sert à ne pas promettre au maire une conformité qui n'existe pas.

## Fait

| Obligation | Où | Ce qui a été fait |
|---|---|---|
| Étiquettes de formulaire | tous les formulaires | chaque champ a un `<label>` lié par `for`/`id` |
| Champs obligatoires signalés | idem | mention **texte** « (obligatoire) » — l'astérisque seul et la couleur seule ne sont pas perçus par tout le monde (RGAA 11.10) |
| Nature des champs annoncée | idem | `autocomplete="name"`, `"email"`, `"tel"`, `"organization"` — WCAG 1.3.5, niveau AA. Cela remplit aussi le formulaire tout seul pour qui a enregistré ses coordonnées, ce qui compte pour une personne âgée |
| Erreurs identifiées en texte | idem | message écrit sous l'étiquette, jamais un simple encadrement rouge |
| Minimisation des données | inscription à la lettre | seule l'adresse électronique est obligatoire. Code postal et commune sont facultatifs : tant que la mairie n'a qu'une lettre pour tout le monde, distinguer les habitants des voisins ne sert à rien. On peut toujours les rendre obligatoires plus tard ; on ne peut pas effacer une collecte déjà faite |
| Information RGPD à la collecte | idem | mention dépliable sous chaque formulaire : finalité, durée, absence de transmission, droits. L'article 13 impose d'informer **au moment de la collecte** — un renvoi vers une page de confidentialité ne suffit pas seul |
| Polices auto-hébergées | tout le site | aucun appel à Google Fonts : un CDN transmet l'IP de chaque visiteur à un tiers |
| Vidéos sans traceur avant consentement | fiches d'événement | façade au clic. Rien de la plateforme n'est chargé — pas même la vignette, qui suffirait à transmettre l'IP |
| Lien d'évitement | toutes les pages | premier élément focusable |
| Désinscription en un clic | envois de la lettre | en-têtes `List-Unsubscribe` et `List-Unsubscribe-Post: One-Click`. Depuis novembre 2025, Gmail et Yahoo exigent une désinscription **sans page de confirmation** ; Microsoft a suivi en mai 2025. Le lien reçu dans un envoi désinscrit donc immédiatement — contrairement au formulaire public, où l'on vérifie par courriel pour empêcher qu'on désabonne son voisin |
| Version texte des courriels | envois de la lettre | un courriel uniquement HTML est un signal de spam pour la plupart des filtres, et illisible pour qui lit en texte brut |
| Protection contre le spam | formulaires publics | jeton CSRF, champ-piège, délai minimum, refus des adresses web dans les champs d'identité, limite de demandes par adresse. **Pas de reCAPTCHA** : service de Google appelé sur chaque visiteur, et barrière pour les usagers déficients visuels ou âgés |
| Navigation au clavier | menu, recherche | `aria-expanded`, piège à focus, Échap ferme, le focus revient au déclencheur |

## Pas fait

| Obligation | Ce qu'il faut |
|---|---|
| **Déclaration d'accessibilité** | la page EXISTE et est publiée depuis le 25 août. Son contenu ne peut pas être arrêté avant la fin de l'audit : elle doit porter le niveau réellement constaté, la date, et la liste des contenus non accessibles |
| **Schéma pluriannuel de mise en accessibilité** | document distinct, publié, couvrant trois ans |
| **Audit RGAA** | **commencé le 30 août 2026.** Les critères mécaniques sont vérifiés : zéro défaut sur les 23 pages de l'échantillon. Restent ceux qui demandent un jugement — voir plus bas |
| **Registre des traitements** | côté mairie, pas côté site |
| **SPF, DKIM et DMARC** | trois enregistrements DNS sur le domaine expéditeur. Sans eux, la lettre part en indésirables — voire est rejetée. Ce n'est pas du code : c'est une configuration chez l'hébergeur du nom de domaine, à faire avant le premier envoi réel |
| **Délégué à la protection des données** | obligatoire pour un organisme public. Peut être mutualisé au niveau de l'intercommunalité — c'est le cas le plus fréquent pour une petite commune |

## Où en est l'audit RGAA — 30 août 2026

`python3 theme-marly/outils/auditer-rgaa.py https://marlygomont.pixfeed.net user:motdepasse`

**Vérifié par machine, zéro défaut** sur les 23 pages de l'échantillon —
les 6 pages obligatoires du RGAA plus une page par gabarit : alternatives
présentes, étiquettes de formulaire, hiérarchie des titres, liens non vides,
contrastes de la palette.

**Vérifié à la main depuis, et corrigé :**

| Critère | Ce qui a été trouvé |
|---|---|
| **8.6** titre de page | les 162 pages portaient **le même titre**. Une variable jamais définie. Chaque page a désormais le sien |
| **9.1** hiérarchie | les 26 pages portent exactement un titre de premier niveau. Vérifié gabarit par gabarit |
| **9.1** pertinence | deux titres de la page de recherche s'écrivaient « actus » et « événements », en minuscules et abrégés, au milieu de cinq intitulés pleins |

**Reste à faire, et rien de ceci ne se mécanise :**

| Critère | Ce qu'il faut |
|---|---|
| **1.3 / 1.9** | la pertinence de chaque alternative. En particulier : la photographie de tête d'un article porte `alt=""`, choix défendable pour une illustration, à trancher si elle porte de l'information |
| **3.1** | une information portée par la seule couleur |
| **8.2** | la validité du code au validateur du W3C, une page par gabarit |
| **10.x** | le rendu sans feuille de style, et l'agrandissement du seul texte à 200 % |
| **12.8 / 12.9** | l'ordre de tabulation réel, page par page |
| **13.3** | **les 52 comptes rendus en PDF.** Ils ont leurs propres exigences, et nous ne les avons pas produits. La déclaration devra les nommer comme contenus non accessibles |
| **Lecteur d'écran** | un parcours complet avec NVDA ou VoiceOver. Rien ne le remplace |

## Ce que ça veut dire concrètement

Les formulaires sont conformes, **les quatre pages obligatoires existent**, et
l'audit mécanique est propre. Le site n'est pas conforme pour autant : la
moitié des 106 critères demande un jugement humain, et cette moitié-là n'a été
entamée que le 30 août.

Ces quatre pages ne sont pas du remplissage : la déclaration d'accessibilité
engage la commune sur un niveau constaté. La rédiger suppose d'avoir fait
l'audit, et l'audit suppose que le site soit terminé.

C'est donc une tâche de fin de chantier, pas une case à cocher — mais elle
doit être annoncée au client dès le devis, parce qu'elle a un coût.
