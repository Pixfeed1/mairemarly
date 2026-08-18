#!/usr/bin/env bash
#
# inventaire.sh — Extrait un inventaire lisible depuis une capture aspirée
# ---------------------------------------------------------------------------
# Usage : ./inventaire.sh capture_marlygomont.free.fr
#
# Produit inventaire.md : rubriques, titres d'articles, coordonnées mairie,
# liste des documents. C'est la base de la maquette à proposer à la commune.
# ---------------------------------------------------------------------------

set -u
DIR="${1:-}"
if [ -z "$DIR" ] || [ ! -d "$DIR" ]; then
  echo "Usage : $0 <dossier_de_capture>" >&2
  exit 1
fi
OUT="$DIR/inventaire.md"

# petit utilitaire : enlève les balises HTML
strip() { sed -E 's/<[^>]+>//g; s/&nbsp;/ /g; s/&amp;/\&/g; s/&#39;/'"'"'/g' ; }

{
  echo "# Inventaire du contenu public — $DIR"
  echo
  echo "_Généré automatiquement. À vérifier/compléter à la main avant la maquette._"
  echo

  echo "## Rubriques / menu"
  echo
  # titres des pages rubriques + libellés de menu (liens de navigation)
  grep -rhoiE '<title>[^<]+</title>' "$DIR/rubriques" 2>/dev/null | strip | sed 's/^/- /' | sort -u
  echo

  echo "## Articles"
  echo
  # titres depuis le backend RSS si présent...
  if [ -f "$DIR/backend.xml" ]; then
    grep -oiE '<title>[^<]+</title>' "$DIR/backend.xml" | strip | sed 's/^/- /' | tail -n +2
  fi
  # ...et depuis les articles aspirés par ID
  grep -rhoiE '<title>[^<]+</title>' "$DIR/articles" 2>/dev/null | strip | sed 's/^/- /' | sort -u
  echo

  echo "## Auteurs détectés (noms publics uniquement)"
  echo
  # Les NOMS d'auteurs sont publics (signature des articles) ; les comptes,
  # emails et mots de passe sont en base et ne sortent pas du serveur.
  {
    [ -f "$DIR/backend.xml" ] && grep -oiE '<dc:creator>[^<]+</dc:creator>' "$DIR/backend.xml" | strip
    grep -rhoiE '<[^>]+(class|id)="[^"]*auteur[^"]*"[^>]*>[^<]+' "$DIR" 2>/dev/null | strip
    grep -rhoiE '<meta name="author" content="[^"]+"' "$DIR" 2>/dev/null \
      | sed -E 's/.*content="([^"]+)".*/\1/'
    # signatures presentes dans les <title> : "..., par Untel - Nom du site"
    grep -rhoiE '<title>[^<]+</title>' "$DIR" 2>/dev/null | strip \
      | sed -nE 's/.*,[[:space:]]*[Pp]ar[[:space:]]+([^-]+).*/\1/p'
  } | sed -E 's/^[[:space:]]*[Pp]ar[[:space:]]+//' \
    | sed -E 's/^[[:space:]]+//; s/[[:space:]]+$//' \
    | awk 'length($0)>=3 && length($0)<=60' | sort -u | sed 's/^/- /'
  echo
  echo "> Identifiants, emails et mots de passe ne sont PAS accessibles depuis"
  echo "> l'extérieur. Les comptes seront à recréer dans le nouveau site."
  echo

  echo "## Coordonnées probables de la mairie"
  echo
  echo '```'
  # téléphones (format FR), emails, code postal + ville, horaires
  grep -rhoiE '0[1-9]([ .-]?[0-9]{2}){4}' "$DIR/miroir" 2>/dev/null | sort -u | head -10
  grep -rhoiE '[A-Za-z0-9._%+-]+@[A-Za-z0-9.-]+\.[A-Za-z]{2,}' "$DIR/miroir" 2>/dev/null | sort -u | head -10
  grep -rhoiE '0[0-9]{4}[ ]?[A-ZÀ-Ý][A-Za-zÀ-ÿ -]+' "$DIR/miroir" 2>/dev/null | strip | sort -u | head -10
  echo '```'
  echo

  echo "## Documents téléchargeables (PDF, DOC...)"
  echo
  find "$DIR/miroir" \( -name '*.pdf' -o -name '*.doc' -o -name '*.docx' -o -name '*.odt' \) 2>/dev/null \
    | sed "s#$DIR/miroir/#- #" | sort
  echo

  echo "## Images (banque visuelle récupérée)"
  echo
  echo "Total : $(find "$DIR/miroir" \( -name '*.jpg' -o -name '*.jpeg' -o -name '*.png' -o -name '*.gif' \) 2>/dev/null | wc -l) fichiers dans $DIR/miroir"
} > "$OUT"

echo "Inventaire écrit dans : $OUT"
echo "----------------------------------------"
cat "$OUT"
