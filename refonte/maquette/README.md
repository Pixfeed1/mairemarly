# Maquette — page d'accueil de Marly-Gomont

## Démarrage : une seule commande

```powershell
cd D:\refonte\mairemarly\refonte\maquette
.\preparer_assets.ps1
```

Le script dépose :
- `vendor/` — **Remix Icon** (Apache 2.0), police et CSS auto-hébergés
- `img/` — 7 photographies libres, via Lorem Picsum (photos Unsplash)

Ouvre ensuite `index.html` dans le navigateur.

> Si tu n'exécutes pas le script, la page reste lisible : les icônes passent par
> le CDN de secours et les photos laissent place à des dégradés.

## Remplacer les photos par de vraies photos du village

C'est **le** geste qui fera basculer la maquette. Garde les mêmes noms de
fichiers, la page les reprend automatiquement.

| Fichier | Sujet | Format conseillé |
|---|---|---|
| `img/hero.jpg` | vue du village, bocage, église | 1920 × 900 |
| `img/actu-forum.jpg` | marché, artisans, produits du terroir | 1200 × 700 |
| `img/actu-conseil.jpg` | mairie, salle du conseil | 800 × 500 |
| `img/actu-concert.jpg` | fanfare, instruments | 800 × 500 |
| `img/actu-mairie.jpg` | guichet, accueil | 800 × 500 |
| `img/evt-fete.jpg` | fête, brocante, manèges | 900 × 720 |
| `img/bulletin.jpg` | couverture du bulletin | 500 × 680 |

### Où les trouver

**Photos libres, usage commercial autorisé, sans attribution obligatoire**
- **Pexels** — pexels.com · chercher : *village français*, *campagne*, *marché de producteurs*, *fanfare*
- **Unsplash** — unsplash.com · mêmes recherches en anglais : *french village*, *countryside*, *farmers market*

**Photos du village lui-même**
- **Wikimedia Commons** — chercher « Marly-Gomont ». Licences CC, **attribution obligatoire** : note l'auteur, il ira dans les crédits du site.
- **Géoportail / remonterletemps.ign.fr** — vues aériennes de la commune, licence ouverte

**Gravures anciennes** (pour remplacer mes gravures SVG)
- **Gallica (BnF)** — domaine public : gravures du XIXᵉ, blasons, scènes rurales
- **Old Book Illustrations**, **Rawpixel** — gravures détourées, domaine public

**Le meilleur choix reste tes propres photos.** Une visite au village, vingt
prises de vue, et la maquette change de catégorie. C'est aussi une ligne de
facturation supplémentaire, et l'argument se défend seul auprès des élus.

## Changer la couleur du site

Une seule ligne, en haut du `<style>` de `index.html` :

```css
--accent:#1E5B41;   /* vert bocage — actuel */
```

`#BE061C` pour un rouge institutionnel, `#1F4E79` pour un bleu ardoise.
Tout le site suit : titres, pastilles, liserés, boutons, icônes.

## Avant la mise en production

- [ ] **Supprimer la ligne du CDN Remix Icon** dans `<head>` et ne garder que
      `vendor/remixicon.css`. Un CDN transmet l'adresse IP de chaque visiteur à
      un tiers : une collectivité ne peut pas le faire sans base légale (RGPD).
- [ ] **Auto-héberger les polices Google** de la même façon, pour la même raison.
- [ ] Retirer le bandeau « maquette de proposition ».
- [ ] Rédiger la déclaration d'accessibilité (RGAA) et corriger le lien du pied
      de page, aujourd'hui marqué « non conforme ».
- [ ] Remplacer les contenus d'illustration par les contenus réels.

## Ce qui vient des vraies données

Repris de la capture du site actuel : le nom des rubriques, les titres
d'articles, les associations (ASMG, harmonie municipale, comité d'animation,
secteur paroissial), le bulletin « La Voix du Village », et les coordonnées
de la mairie.

Les **dates** des événements sont en revanche des exemples : à remplacer.
