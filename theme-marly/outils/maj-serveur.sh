#!/bin/sh
# Met à jour le thème sur le serveur de test.
# ---------------------------------------------------------------------------
# SPIP compile les gabarits et garde le résultat dans tmp/cache/skel. Sans
# vidage, il continue de servir l'ancienne version après un git pull : on
# croit alors que la modification n'a pas marché, alors qu'elle n'a pas été
# relue. C'est le piège classique, d'où ce script.
#
#   ~/depot-marly/theme-marly/outils/maj-serveur.sh
#
set -e

DEPOT="$(cd "$(dirname "$0")/../.." && pwd)"
SITE="${SITE_SPIP:-$HOME/marlygomont.pixfeed.net}"

echo "Dépôt : $DEPOT"
echo "Site  : $SITE"
echo

if [ ! -d "$SITE/tmp" ]; then
	echo "Erreur : $SITE ne ressemble pas à une installation SPIP (pas de tmp/)." >&2
	echo "Indiquer le bon chemin :  SITE_SPIP=/chemin/du/site $0" >&2
	exit 1
fi

avant=$(git -C "$DEPOT" rev-parse --short HEAD)
git -C "$DEPOT" pull --ff-only
apres=$(git -C "$DEPOT" rev-parse --short HEAD)

if [ "$avant" = "$apres" ]; then
	echo "Rien de nouveau ($avant)."
else
	echo
	echo "$avant → $apres :"
	git -C "$DEPOT" log --oneline "$avant..$apres"
fi

# On ne vide que le cache des gabarits compilés : les images calculées
# (vignettes) sont longues à refaire et n'ont aucune raison de changer.
rm -rf "$SITE/tmp/cache/skel"
echo
echo "Cache des gabarits vidé. Recharger la page."
