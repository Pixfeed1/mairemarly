#!/bin/sh
# Depose les sources dans la racine web.
# ---------------------------------------------------------------------------
#   sh theme-marly/outils/deployer.sh [/chemin/vers/racine-web]
#
# Le depot git n'est PAS le site. On recopie les sources dans la racine web.
#
# Pourquoi une copie et non un lien symbolique — la question a coute deux
# deploiements. PHP suit les liens sans rien demander : les squelettes
# etaient donc lus, les pages se composaient, tout avait l'air de marcher.
# Apache, lui, refuse de servir un fichier a travers un lien des que l'hote
# a pose Options -FollowSymLinks ou SymLinksIfOwnerMatch — le reglage
# courant en mutualise. Le HTML arrivait, les feuilles de style repondaient
# 403, et la page s'affichait toute nue.
#
# Ce script ne touche PAS a git : la mise a jour des sources se fait avant,
# en une ligne. Un script qui se met a jour lui-meme pendant qu'il s'execute
# est une mauvaise idee — le shell le lit au fur et a mesure.
set -e

# Le depot, c'est la racine du dossier qui contient ce script. Aucun chemin
# en dur : le script marche quel que soit l'utilisateur et l'emplacement.
DEPOT=$(cd "$(dirname "$0")/../.." && pwd)

# La racine web est donnee en argument, ou devinee : le vrai marqueur d'une
# racine SPIP, c'est ecrire/inc_version.php.
SITE="$1"
if [ -z "$SITE" ]; then
	for essai in "$HOME/marlygomont.pixfeed.net" /home/*/marlygomont.pixfeed.net; do
		if [ -f "$essai/ecrire/inc_version.php" ]; then SITE="$essai"; break; fi
	done
fi
if [ -z "$SITE" ] || [ ! -f "$SITE/ecrire/inc_version.php" ]; then
	echo "Racine web introuvable."
	echo "Usage : sh $0 /chemin/vers/racine-web"
	exit 1
fi

echo "Depot : $DEPOT"
echo "Site  : $SITE"
echo

echo "--- Squelettes"
mkdir -p "$SITE/squelettes"
rsync -a --delete "$DEPOT/theme-marly/squelettes/" "$SITE/squelettes/"

echo "--- Plugin"
AVANT=$(grep -o 'version="[^"]*"' "$SITE/plugins/marly/paquet.xml" 2>/dev/null | head -1)
mkdir -p "$SITE/plugins/marly"
rsync -a --delete "$DEPOT/plugin-marly/" "$SITE/plugins/marly/"
APRES=$(grep -o 'version="[^"]*"' "$SITE/plugins/marly/paquet.xml" | head -1)

# Deployer en root laisserait des fichiers appartenant a root dans le site
# d'un utilisateur : selon la configuration (suexec, php-fpm, mod_userdir),
# Apache refuse alors de les servir ou PHP de les lire. On rend les fichiers
# au proprietaire de la racine web, quel qu'il soit.
if [ "$(id -u)" = "0" ]; then
	echo "--- Proprietaire"
	chown -R --reference="$SITE" "$SITE/squelettes" "$SITE/plugins/marly"
fi

# SPIP garde plusieurs caches, et vider le premier ne vide pas les autres.
# Celui des LANGUES en particulier : quand il est en retard, une chaine
# nouvellement ajoutee s'affiche sous la forme de sa cle, underscores changes
# en espaces — << titre lettre info >> au lieu de << Lettre d'information >>.
# Le symptome ne ressemble pas a un cache, il ressemble a une faute de frappe
# dans le fichier de langue. On les vide donc tous : ils se reconstruisent
# seuls a la premiere visite.
echo "--- Caches"
rm -rf "$SITE/tmp/cache"
find "$SITE/local" -maxdepth 1 -name 'cache-*' -exec rm -rf {} + 2>/dev/null || true

# La base ne se met plus a jour toute seule dans un navigateur : on la met a
# jour ici. SPIP n'appelle plugin_installes_meta() que depuis la page des
# plugins ; tant qu'on dependait de ce geste, la base pouvait prendre six
# versions de retard sans que rien ne le signale. C'est ce qui est arrive.
#
# Sous l'utilisateur du site, jamais en root : SPIP reecrit ses caches au
# passage, et un cache appartenant a root rend le site illisible pour Apache.
echo "--- Mise a jour de la base"
PROPRIO=$(stat -c %U "$SITE")
MAJ="php $DEPOT/theme-marly/outils/majbase.php $SITE"
if [ "$(id -u)" = "0" ] && [ "$PROPRIO" != "root" ]; then
	if command -v runuser >/dev/null 2>&1; then
		runuser -u "$PROPRIO" -- $MAJ || echo "  (la mise a jour a echoue, voir ci-dessus)"
	else
		su -s /bin/sh -c "$MAJ" "$PROPRIO" || echo "  (la mise a jour a echoue, voir ci-dessus)"
	fi
else
	$MAJ || echo "  (la mise a jour a echoue, voir ci-dessus)"
fi

echo
echo "Deploye. Version du plugin : $APRES"

# SPIP verifie l'existence des tables A LA COMPILATION du squelette, pas a
# l'execution. Une version qui ajoute une table casse donc TOUT le site
# public tant que la mise a jour n'a pas tourne. C'est la raison pour
# laquelle elle est faite ici, dans la foulee de la copie, et non laissee a
# un geste qu'on peut oublier : entre les deux, le site est par terre.
if [ "$AVANT" != "$APRES" ]; then
	echo
	echo "  La version du plugin a change ($AVANT -> $APRES)."
	echo "  La mise a jour de la base a tourne juste au-dessus ; le controle"
	echo "  ci-dessous dit si elle a abouti."
fi

# On ne se fie pas a ce que la mise a jour vient d'afficher : on relit ce que
# la base dit vraiment. Un script qui se contente de son propre compte rendu
# ne verifie rien.
SCHEMA=$(grep -o 'schema="[^"]*"' "$SITE/plugins/marly/paquet.xml" | head -1 | sed 's/schema="//; s/"//')
CONNECT="$SITE/config/connect.php"
if [ -n "$SCHEMA" ] && [ -f "$CONNECT" ]; then
	INFOS=$(sed -n "s/.*spip_connect_db(\s*'\([^']*\)'\s*,\s*'[^']*'\s*,\s*'\([^']*\)'\s*,\s*'\([^']*\)'\s*,\s*'\([^']*\)'.*/\1|\2|\3|\4/p" "$CONNECT" | head -1)
	HOTE=$(echo "$INFOS" | cut -d'|' -f1)
	LOGIN=$(echo "$INFOS" | cut -d'|' -f2)
	PASSE=$(echo "$INFOS" | cut -d'|' -f3)
	BASE=$(echo "$INFOS"  | cut -d'|' -f4)
	if [ -n "$BASE" ]; then
		ENBASE=$(mysql -h "${HOTE:-localhost}" -u "$LOGIN" -p"$PASSE" "$BASE" -N -B \
		         -e "SELECT valeur FROM spip_meta WHERE nom='marly_base_version'" 2>/dev/null)
		echo
		if [ "$ENBASE" = "$SCHEMA" ]; then
			echo "Base a jour : $ENBASE. Rien d'autre a faire."
		else
			echo "  ================================================================"
			echo "  BASE PAS A JOUR : elle est en ${ENBASE:-aucune}, le plugin attend $SCHEMA."
			echo "  Les tables et colonnes nouvelles N'EXISTENT PAS encore."
			echo
			echo "  La mise a jour lancee plus haut n'a donc pas abouti :"
			echo "  relisez ce qu'elle a affiche. En recours, la page des"
			echo "  plugins la relance depuis un navigateur :"
			echo "      https://marlygomont.pixfeed.net/ecrire/?exec=admin_plugin"
			echo "  ================================================================"
		fi
	fi
fi
