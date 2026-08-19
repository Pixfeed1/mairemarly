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
| **Déclaration d'accessibilité** | page obligatoire, avec le niveau de conformité réellement constaté (« non conforme », « partiellement conforme », « totalement conforme »), la date de l'audit, les contenus non accessibles, et un moyen de contact. Une commune ne peut pas s'en dispenser |
| **Schéma pluriannuel de mise en accessibilité** | document distinct, publié, couvrant trois ans |
| **Mentions légales** | éditeur, directeur de publication, hébergeur avec ses coordonnées |
| **Politique de confidentialité** | la page vers laquelle pointe la mention des formulaires |
| **Audit RGAA** | les 106 critères. Le thème est écrit pour les respecter, mais **écrit pour** n'est pas **vérifié contre** |
| **Registre des traitements** | côté mairie, pas côté site |
| **SPF, DKIM et DMARC** | trois enregistrements DNS sur le domaine expéditeur. Sans eux, la lettre part en indésirables — voire est rejetée. Ce n'est pas du code : c'est une configuration chez l'hébergeur du nom de domaine, à faire avant le premier envoi réel |
| **Délégué à la protection des données** | obligatoire pour un organisme public. Peut être mutualisé au niveau de l'intercommunalité — c'est le cas le plus fréquent pour une petite commune |

## Ce que ça veut dire concrètement

Les formulaires sont conformes. **Le site ne l'est pas encore**, parce qu'il
lui manque quatre pages obligatoires et un audit.

Ces quatre pages ne sont pas du remplissage : la déclaration d'accessibilité
engage la commune sur un niveau constaté. La rédiger suppose d'avoir fait
l'audit, et l'audit suppose que le site soit terminé.

C'est donc une tâche de fin de chantier, pas une case à cocher — mais elle
doit être annoncée au client dès le devis, parce qu'elle a un coût.
