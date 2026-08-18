# Aperçu et vérification de l'en-tête

Bancs d'essai qui permettent de valider le thème **sans installer SPIP**.

| Fichier | Rôle |
|---|---|
| `entete.html` | L'en-tête seul, reflet exact de `squelettes/inc/entete.html` |
| `entete-tiroir.html` | Le même, tiroir mobile ouvert |
| `banc-mobile.html` | Les deux côte à côte dans des iframes de 360 px |
| `debug.html` | `entete.html` + une sonde qui mesure la page |
| `captures/` | Rendus à 360, 768, 1200 et 1440 px |

## Refaire les mesures

```bash
CHROME=/opt/pw-browsers/chromium-1194/chrome-linux/chrome
"$CHROME" --headless=new --disable-gpu --no-sandbox --window-size=1024,800 \
  --virtual-time-budget=5000 --dump-dom "file://$PWD/debug.html" \
  | grep -o 'id="SONDE">[^<]*'
```

La sonde renvoie `vue`, `scroll`, l'état du bouton menu et l'élément le plus à
droite. **Le seul test de débordement qui compte est `scroll == vue`.**

## Deux pièges rencontrés, à ne pas réapprendre

1. **Chromium refuse de descendre sous 500 px de large.** Demander 360 px
   produit une image rognée d'un rendu à 500 px — on croit à un bouton
   manquant alors qu'il est simplement hors du cadre. D'où les iframes.

2. **Le tiroir garé hors écran fausse toute mesure de largeur.** Il ressort à
   +340 px sur `getBoundingClientRect`, sans pour autant créer de défilement.
   La sonde l'exclut désormais.

## Seuil de bascule : 1200 px

Mesuré, pas choisi. La barre déborde jusqu'à 1100 px inclus et rentre à partir
de 1200 px. Au-delà : navigation horizontale. En dessous : bouton et tiroir.
