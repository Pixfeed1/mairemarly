#!/bin/sh
# Pourquoi la base ne se met pas a jour.
# ---------------------------------------------------------------------------
#   sh theme-marly/outils/diagnostic-maj.sh [/chemin/vers/racine-web]
#
# deployer.sh sait DIRE que la base est en retard. Il ne sait pas dire
# POURQUOI. Ce script-ci ne modifie rien : il rassemble en une seule sortie
# les six faits qui separent les explications possibles, pour qu'on arrete de
# supposer.
#
#   1. ce que le plugin DEPOSE sur le serveur declare
#   2. ce que la base a enregistre
#   3. quelles tables existent vraiment
#   4. ce que SPIP a note dans son journal de mise a jour
#   5. si le plugin est vu comme actif
#   6. si le fichier de mise a jour est lisible par PHP
set -e

DEPOT=$(cd "$(dirname "$0")/../.." && pwd)
SITE="$1"
if [ -z "$SITE" ]; then
	for essai in "$HOME/marlygomont.pixfeed.net" /home/*/marlygomont.pixfeed.net; do
		if [ -f "$essai/ecrire/inc_version.php" ]; then SITE="$essai"; break; fi
	done
fi
if [ -z "$SITE" ] || [ ! -f "$SITE/ecrire/inc_version.php" ]; then
	echo "Racine web introuvable. Usage : sh $0 /chemin/vers/racine-web"
	exit 1
fi
echo "Site : $SITE"

echo
echo "=== 1. Ce que le plugin depose declare"
if [ -f "$SITE/plugins/marly/paquet.xml" ]; then
	grep -o -E '(version|schema|prefix)="[^"]*"' "$SITE/plugins/marly/paquet.xml" | head -3
	echo "derniere etape ecrite dans marly_administrations.php :"
	grep -o "\$maj\['[0-9.]*'\]" "$SITE/plugins/marly/marly_administrations.php" | tail -1
else
	echo "  PAS DE PLUGIN dans $SITE/plugins/marly — le deploiement n'a pas eu lieu."
fi

CONNECT="$SITE/config/connect.php"
INFOS=$(sed -n "s/.*spip_connect_db(\s*'\([^']*\)'\s*,\s*'[^']*'\s*,\s*'\([^']*\)'\s*,\s*'\([^']*\)'\s*,\s*'\([^']*\)'.*/\1|\2|\3|\4/p" "$CONNECT" | head -1)
HOTE=$(echo "$INFOS" | cut -d'|' -f1); LOGIN=$(echo "$INFOS" | cut -d'|' -f2)
PASSE=$(echo "$INFOS" | cut -d'|' -f3); BASE=$(echo "$INFOS"  | cut -d'|' -f4)
SQL="mysql -h ${HOTE:-localhost} -u $LOGIN -p$PASSE $BASE -N -B"

echo
echo "=== 2. Ce que la base a enregistre"
$SQL -e "SELECT nom, LEFT(valeur,60) FROM spip_meta
         WHERE nom IN ('marly_base_version','plugin_attente_installation','version_installee')" 2>&1

echo
echo "=== 3. Les tables du plugin qui existent"
for t in salles reservations manifestations abonnes lettres demarches elus raccourcis associations lieux; do
	N=$($SQL -e "SELECT COUNT(*) FROM information_schema.tables
	             WHERE table_schema='$BASE' AND table_name='spip_$t'" 2>/dev/null)
	if [ "$N" = "1" ]; then echo "  spip_$t : oui"; else echo "  spip_$t : ABSENTE"; fi
done
echo "colonnes de spip_associations :"
$SQL -e "SELECT GROUP_CONCAT(column_name ORDER BY ordinal_position) FROM information_schema.columns
         WHERE table_schema='$BASE' AND table_name='spip_associations'" 2>&1

echo
echo "=== 4. Le journal de mise a jour de SPIP"
for f in maj spip marly; do
	if [ -f "$SITE/tmp/log/$f.log" ]; then
		echo "--- tmp/log/$f.log (20 dernieres lignes)"
		tail -20 "$SITE/tmp/log/$f.log"
	else
		echo "--- tmp/log/$f.log : absent"
	fi
done

echo
echo "=== 5. Le plugin est-il vu comme actif"
$SQL -e "SELECT LEFT(valeur,400) FROM spip_meta WHERE nom='plugin'" 2>&1 | tr ',' '\n' | grep -i marly || echo "  marly n'apparait pas dans la meta plugin"

echo
echo "=== 6. PHP peut-il lire le fichier de mise a jour"
php -l "$SITE/plugins/marly/marly_administrations.php" 2>&1
php -l "$SITE/plugins/marly/base/marly.php" 2>&1
ls -l "$SITE/plugins/marly/marly_administrations.php"
