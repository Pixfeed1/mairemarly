#!/usr/bin/env bash
#
# aspirer_site.sh — Aspiration NON-DESTRUCTIVE du contenu public d'un site SPIP
# ---------------------------------------------------------------------------
# But : récupérer, depuis l'extérieur (aucun accès admin/FTP/BDD), tout le
#       contenu public d'un vieux site SPIP (rubriques, articles, images,
#       documents PDF) afin de reconstruire une version moderne à PROPOSER
#       à la commune.
#
# Ce script ne fait que LIRE des pages publiques (comme un navigateur).
# Il n'exploite aucune faille et ne modifie rien sur la cible.
#
# Usage :
#   ./aspirer_site.sh http://marlygomont.free.fr
#
# Résultat : un dossier ./capture_<hôte>/ contenant
#   - miroir/        le site aspiré (HTML + images + CSS + PDF)
#   - backend.xml    le flux RSS SPIP (liste structurée des articles)
#   - articles/      les articles récupérés par ID (article.php3?id_article=N)
#   - rubriques/     les rubriques récupérées par ID
#   - wayback.txt    les URLs archivées sur archive.org (filet de sécurité)
#
# Prérequis : wget, curl (installés par défaut sur Linux/macOS/WSL).
# ---------------------------------------------------------------------------

set -u

BASE="${1:-}"
if [ -z "$BASE" ]; then
  echo "Usage : $0 http://mon-site.tld" >&2
  exit 1
fi
BASE="${BASE%/}"
HOST="$(printf '%s' "$BASE" | sed -E 's#https?://##; s#/.*##')"
OUT="capture_${HOST}"
UA="Mozilla/5.0 (compatible; refonte-mairie; aspiration contenu public)"
CURL="curl -sSL -A ${UA} --max-time 30"

mkdir -p "$OUT/articles" "$OUT/rubriques"
echo "==> Capture dans : $OUT/"

# ---------------------------------------------------------------------------
# 1) Miroir complet du site public
#    --mirror = récursif + timestamps ; --page-requisites = images/CSS ;
#    --convert-links = liens navigables hors-ligne ; on reste sur le domaine.
#    --wait/--random-wait = poli, on ne martèle pas le serveur.
# ---------------------------------------------------------------------------
echo "==> [1/5] Miroir du site (peut prendre quelques minutes)..."
wget \
  --mirror \
  --page-requisites \
  --convert-links \
  --adjust-extension \
  --no-parent \
  --domains="$HOST" \
  --user-agent="$UA" \
  --wait=1 --random-wait \
  --reject-regex '(\?|&)(action|var_mode|calendrier|forum)' \
  --directory-prefix="$OUT/miroir" \
  --no-verbose \
  "$BASE/" 2>>"$OUT/wget.log" || echo "   (wget terminé avec des avertissements — voir $OUT/wget.log)"

# ---------------------------------------------------------------------------
# 2) Flux backend SPIP = liste structurée des derniers articles (RSS)
#    Plusieurs conventions selon la version de SPIP : on teste les 3.
# ---------------------------------------------------------------------------
echo "==> [2/5] Récupération du flux backend SPIP (RSS)..."
for ep in "spip.php?page=backend" "backend.php3" "backend.php"; do
  if $CURL "$BASE/$ep" -o "$OUT/backend.xml" 2>/dev/null && \
     grep -qi '<rss\|<item>' "$OUT/backend.xml" 2>/dev/null; then
    echo "   Trouvé : $BASE/$ep"
    break
  fi
done

# ---------------------------------------------------------------------------
# 3) Balayage des articles et rubriques par ID incrémental.
#    Les vieux SPIP exposent article.php3?id_article=N (et variantes).
#    On s'arrête après TROUS échecs consécutifs (fin probable du contenu).
# ---------------------------------------------------------------------------
balayer() {
  local type="$1" motif="$2" dossier="$3" maxvide="$4"
  echo "==> Balayage des $type (arrêt après $maxvide absences d'affilée)..."
  local vide=0 n=1
  while [ "$vide" -lt "$maxvide" ]; do
    local url="$BASE/${motif}${n}"
    local page
    page="$($CURL "$url" 2>/dev/null)"
    # Un article valide contient du texte ; une page vide/erreur SPIP non.
    if printf '%s' "$page" | grep -qiE '<title>[^<]{3,}</title>' && \
       ! printf '%s' "$page" | grep -qiE '404|introuvable|n.existe pas'; then
      printf '%s' "$page" > "$dossier/${type}_${n}.html"
      vide=0
    else
      vide=$((vide+1))
    fi
    n=$((n+1))
    [ "$n" -gt 500 ] && break   # garde-fou
    sleep 0.3
  done
  echo "   $(ls -1 "$dossier" 2>/dev/null | wc -l) $type enregistrés."
}
# SPIP moderne : spip.php?articleN ; SPIP ancien : article.php3?id_article=N
echo "==> [3/5] Articles & rubriques par ID..."
balayer "article"  "spip.php?article" "$OUT/articles"  15
balayer "rubrique" "spip.php?rubrique" "$OUT/rubriques" 8
# Variante .php3 si le SPIP moderne n'a rien donné
if [ "$(ls -1 "$OUT/articles" 2>/dev/null | wc -l)" -eq 0 ]; then
  balayer "article"  "article.php3?id_article="  "$OUT/articles"  15
  balayer "rubrique" "rubrique.php3?id_rubrique=" "$OUT/rubriques" 8
fi

# ---------------------------------------------------------------------------
# 4) Plan du site (sitemap) éventuel
# ---------------------------------------------------------------------------
echo "==> [4/5] Recherche d'un sitemap..."
for sm in "sitemap.xml" "spip.php?page=sitemap" "plan.html"; do
  $CURL "$BASE/$sm" -o "$OUT/sitemap_test" 2>/dev/null
  if grep -qi '<url>\|<loc>\|sommaire' "$OUT/sitemap_test" 2>/dev/null; then
    mv "$OUT/sitemap_test" "$OUT/sitemap.xml"; echo "   Trouvé : $sm"; break
  fi
done
rm -f "$OUT/sitemap_test"

# ---------------------------------------------------------------------------
# 5) Filet de sécurité : archive.org (Wayback Machine)
#    Liste toutes les URLs de ce domaine déjà archivées publiquement.
# ---------------------------------------------------------------------------
echo "==> [5/5] Interrogation de la Wayback Machine (archive.org)..."
$CURL "http://web.archive.org/cdx/search/cdx?url=${HOST}*&output=text&fl=original&collapse=urlkey&limit=2000" \
  -o "$OUT/wayback.txt" 2>/dev/null
echo "   $(wc -l < "$OUT/wayback.txt" 2>/dev/null || echo 0) URLs archivées listées."

echo
echo "============================================================"
echo " Capture terminée : $OUT/"
echo "   Pages HTML      : $(find "$OUT/miroir" -name '*.html' 2>/dev/null | wc -l)"
echo "   Images          : $(find "$OUT/miroir" \( -name '*.jpg' -o -name '*.png' -o -name '*.gif' \) 2>/dev/null | wc -l)"
echo "   Documents (PDF) : $(find "$OUT/miroir" -name '*.pdf' 2>/dev/null | wc -l)"
echo "   Articles par ID : $(ls -1 "$OUT/articles" 2>/dev/null | wc -l)"
echo "============================================================"
echo "Étape suivante : ./inventaire.sh $OUT"
