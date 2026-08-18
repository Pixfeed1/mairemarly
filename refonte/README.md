# Refonte du site de la mairie de Marly-Gomont — démarche de proposition

Objectif : reconstruire une version moderne du site
`http://marlygomont.free.fr/` (vieux SPIP) **sans accès admin**, à partir du
seul contenu public, pour la **proposer** à la commune et décrocher le client.

> ⚠️ **Ces scripts se lancent depuis TA machine**, pas depuis Claude Code sur
> le web (le conteneur cloud bloque l'accès réseau vers l'extérieur). Clone le
> repo en local, puis lance-les depuis un terminal Linux / macOS / WSL.

## Étapes

### 1. Aspirer le contenu public
```bash
cd refonte
chmod +x aspirer_site.sh inventaire.sh
./aspirer_site.sh http://marlygomont.free.fr
```
Récupère : miroir complet (HTML/images/CSS/PDF), flux RSS SPIP, articles &
rubriques par ID, sitemap, et la liste des archives `archive.org`.
Tout atterrit dans `capture_marlygomont.free.fr/`.

### 2. Extraire l'inventaire
```bash
./inventaire.sh capture_marlygomont.free.fr
```
Produit `inventaire.md` : rubriques, titres d'articles, coordonnées mairie,
documents. C'est ta **matière première** pour la maquette.

### 3. Reconstruire une version moderne
Recommandation : **SPIP 4.x neuf** habillé avec le **Système de Design de
l'État (DSFR)** — le standard graphique des sites publics français. Ça donne
d'emblée un rendu pro **et** conforme aux obligations.

Alternatives selon ton aisance : WordPress, ou un site statique
(Astro/Hugo) si le contenu est figé. Pour une petite commune, le statique +
DSFR est rapide, sûr et quasi gratuit à héberger.

### 4. Argumentaire pour la commune
Points à mettre en avant dans ta proposition :
- **Obligation légale RGAA** (accessibilité) — le site actuel n'est pas conforme.
- **RGPD** (cookies, mentions légales, formulaires).
- **HTTPS** et **responsive mobile** (le site actuel ne l'est probablement pas).
- **Fin de `free.fr`** comme hébergement d'un service public (image, fiabilité).
- **Éco-conception (RGESN)** et rapidité.

## Cadre légal de l'aspiration
Aspirer les **pages publiques** d'un site (comme le fait un navigateur ou
Google) pour préparer une proposition commerciale est légitime. Bonnes
pratiques respectées par le script : User-Agent honnête, délais entre requêtes
(`--wait`), aucune tentative d'accès à `/ecrire/` ou à une zone protégée,
aucune exploitation de faille.

En revanche, pour la **reconstruction livrée**, ne réutilise pas tels quels les
textes/photos protégés sans l'accord de la mairie : la capture sert à
**maquetter** et à **importer le contenu une fois le client signé**.

## Fichiers
| Fichier | Rôle |
|---|---|
| `aspirer_site.sh` | Aspiration du contenu public (miroir + endpoints SPIP + Wayback) |
| `inventaire.sh` | Extraction d'un inventaire lisible (`inventaire.md`) |
| `README.md` | Ce guide |
