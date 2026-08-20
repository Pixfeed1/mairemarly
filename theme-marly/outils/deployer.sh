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
mkdir -p "$SITE/plugins/marly"
rsync -a --delete "$DEPOT/plugin-marly/" "$SITE/plugins/marly/"

# Deployer en root laisserait des fichiers appartenant a root dans le site
# d'un utilisateur : selon la configuration (suexec, php-fpm, mod_userdir),
# Apache refuse alors de les servir ou PHP de les lire. On rend les fichiers
# au proprietaire de la racine web, quel qu'il soit.
if [ "$(id -u)" = "0" ]; then
	echo "--- Proprietaire"
	chown -R --reference="$SITE" "$SITE/squelettes" "$SITE/plugins/marly"
fi

echo "--- Cache"
rm -rf "$SITE/tmp/cache"

echo
echo "Deploye. Version du plugin :"
grep -o 'version="[^"]*"' "$SITE/plugins/marly/paquet.xml" | head -1
