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
echo "== 4. Version du plugin en base"
# La divergence la plus silencieuse de tout le montage. SPIP ne lance la mise
# a jour d'un plugin QUE lorsqu'il relit la liste des plugins et constate que
# la version declaree differe de celle enregistree. Deployer des fichiers ne
# suffit donc pas : tant que personne n'a charge ?exec=admin_plugin, la base
# reste a l'ancienne version. Les colonnes et les tables nouvelles manquent,
# et rien ne le dit — jusqu'a ce qu'un formulaire echoue a l'enregistrement.
DECLAREE=$(grep -o 'version="[^"]*"' "$SITE/plugins/marly/paquet.xml" | head -1 | sed 's/version="//; s/"//')
CONNECT="$SITE/config/connect.php"
if [ -f "$CONNECT" ]; then
	DB=$(sed -n "s/.*spip_connect_db(\s*'\([^']*\)'\s*,\s*'\([^']*\)'\s*,\s*'\([^']*\)'\s*,\s*'\([^']*\)'\s*,\s*'\([^']*\)'.*/\3|\4|\5|\1/p" "$CONNECT" | head -1)
	LOGIN=$(echo "$DB" | cut -d'|' -f1)
	PASSE=$(echo "$DB" | cut -d'|' -f2)
	BASE=$(echo "$DB"  | cut -d'|' -f3)
	HOTE=$(echo "$DB"  | cut -d'|' -f4)
	if [ -n "$BASE" ]; then
		ENBASE=$(mysql -h "${HOTE:-localhost}" -u "$LOGIN" -p"$PASSE" "$BASE" -N -B \
		         -e "SELECT valeur FROM spip_meta WHERE nom='marly_base_version'" 2>/dev/null)
		printf '   declaree dans paquet.xml : %s\n' "${DECLAREE:-?}"
		printf '   enregistree en base      : %s\n' "${ENBASE:-aucune}"
		if [ "$DECLAREE" != "$ENBASE" ]; then
			echo "   <-- LA MISE A JOUR N'A PAS TOURNE."
			echo "       Chargez $BASE/ecrire/?exec=admin_plugin dans le navigateur."
			SOUCIS=1
		fi
	else
		echo "   connect.php illisible, controle passe"
	fi
else
	echo "   pas de config/connect.php"
fi

echo
echo "== 5. Journal de SPIP (30 dernieres lignes)"
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
