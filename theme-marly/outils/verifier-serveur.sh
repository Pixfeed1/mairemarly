#!/bin/sh
# Controle du site tel qu'il est REELLEMENT servi.
# ---------------------------------------------------------------------------
#   sh theme-marly/outils/verifier-serveur.sh /chemin/racine-web [adresse] [id:mdp]
#
# Le controle statique (verifier-squelettes.py) lit le code. Celui-ci
# interroge le site en marche : il charge les pages, verifie que TOUT ce
# qu'elles reclament arrive vraiment — feuilles de style, scripts, polices,
# images — et rapporte le journal d'erreurs de SPIP.
#
# Il existe parce qu'un site peut etre parfaitement correct dans le depot et
# casse sur le serveur : un lien symbolique qu'Apache refuse de suivre, un
# .htaccess incompatible, un fichier oublie. Ces pannes-la ne se voient pas
# dans le code, et les chercher une par une coute un aller-retour chacune.
# Ici, un seul suffit.
set -u

SITE="${1:?Usage : sh $0 /chemin/racine-web [adresse] [identifiant:motdepasse]}"
BASE="${2:-https://marlygomont.pixfeed.net}"
AUTH="${3:-mairie:Marly2026}"
TMP=$(mktemp -d)
trap 'rm -rf "$TMP"' EXIT
SOUCIS=0

echo "== 1. Syntaxe PHP du plugin"
TROUVE=0
for f in $(find "$SITE/plugins/marly" -name '*.php' 2>/dev/null); do
	php -l "$f" >"$TMP/lint" 2>&1 || { cat "$TMP/lint"; TROUVE=1; SOUCIS=1; }
done
[ "$TROUVE" = 0 ] && echo "   ok"

echo
echo "== 2. Pages publiques"
for p in "/" "/spip.php?page=reservation" "/spip.php?page=plan" "/spip.php?page=recherche&recherche=mairie"; do
	code=$(curl -s -o "$TMP/page" -w '%{http_code}' -u "$AUTH" "$BASE$p")
	printf '   %-42s %s' "$p" "$code"
	[ "$code" = "200" ] || SOUCIS=1
	# Une erreur de squelette n'empeche pas SPIP de repondre 200 : elle
	# s'ecrit DANS la page. Un code 200 ne suffit donc pas.
	if grep -qiE 'Erreur de (squelette|compilation)|Fatal error|Parse error|Boucle .* non declaree' "$TMP/page"; then
		printf '  <-- erreur DANS la page'
		SOUCIS=1
	fi
	echo
done

echo
echo "== 3. Fichiers reclames par la page d'accueil"
# Le symptome << site en HTML brut >> vient toujours d'ici : la page arrive,
# ce qu'elle reclame n'arrive pas.
curl -s -u "$AUTH" "$BASE/" > "$TMP/accueil"
grep -oE '(href|src)="[^"]+\.(css|js|woff2|svg|png|jpg)"' "$TMP/accueil" \
	| sed -E 's/^(href|src)="//; s/"$//' | sort -u > "$TMP/liens"
if [ ! -s "$TMP/liens" ]; then
	echo "   aucun fichier reclame — la page d'accueil est-elle vide ?"
	SOUCIS=1
fi
while IFS= read -r lien; do
	case "$lien" in
		http*) url="$lien" ;;
		/*)    url="$BASE$lien" ;;
		*)     url="$BASE/$lien" ;;
	esac
	code=$(curl -s -o /dev/null -w '%{http_code}' -u "$AUTH" "$url")
	printf '   %-52s %s\n' "$(echo "$lien" | cut -c1-52)" "$code"
	[ "$code" = "200" ] || SOUCIS=1
done < "$TMP/liens"

echo
echo "== 4. Journal de SPIP (30 dernieres lignes)"
if [ -f "$SITE/tmp/spip.log" ]; then
	tail -30 "$SITE/tmp/spip.log"
else
	echo "   pas de journal — activer Configuration > Maintenance si besoin"
fi

echo
if [ "$SOUCIS" = 0 ]; then
	echo "Tout repond correctement."
else
	echo "Des points signales ci-dessus demandent un examen."
fi
