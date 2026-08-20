#!/bin/sh
# Deploiement du site sur le serveur.
# ---------------------------------------------------------------------------
#   sh ~/deployer.sh
#
# Le depot git n'est PAS le site. On tire les sources dans un coin, puis on
# les recopie dans la racine web.
#
# Pourquoi une copie et non un lien symbolique — la question a coute une
# journee. PHP suit les liens sans rien demander : les squelettes etaient
# donc bien lus, les pages se composaient, tout avait l'air de marcher.
# Apache, lui, refuse de servir un fichier a travers un lien des que l'hote
# a mis Options -FollowSymLinks ou SymLinksIfOwnerMatch — ce qui est le
# reglage courant en mutualise. Resultat : le HTML arrivait, les feuilles de
# style repondaient 403, et la page s'affichait toute nue.
#
# Une copie ne depend d'aucun reglage Apache. C'est aussi ce que fait
# n'importe quel deploiement SPIP serieux : le depot d'un cote, la racine
# web de l'autre, et une commande pour passer de l'un a l'autre.
set -e

DEPOT="$HOME/depot-marly"
SITE="$HOME/marlygomont.pixfeed.net"
BRANCHE="claude/refonte-spip-mairie-marly-o47sn4"

echo "--- Sources"
git -C "$DEPOT" fetch origin "$BRANCHE"
git -C "$DEPOT" checkout "$BRANCHE"
git -C "$DEPOT" reset --hard "origin/$BRANCHE"

echo "--- Squelettes"
mkdir -p "$SITE/squelettes"
rsync -a --delete "$DEPOT/theme-marly/squelettes/" "$SITE/squelettes/"

echo "--- Plugin"
mkdir -p "$SITE/plugins/marly"
rsync -a --delete "$DEPOT/plugin-marly/" "$SITE/plugins/marly/"

echo "--- Cache"
rm -rf "$SITE/tmp/cache" "$SITE/tmp/dir_secrets.php" 2>/dev/null || true

echo
echo "Deploye. Version du plugin :"
grep -o 'version="[^"]*"' "$SITE/plugins/marly/paquet.xml" | head -1
