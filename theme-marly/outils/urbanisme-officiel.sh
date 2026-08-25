#!/bin/sh
# Quel document d'urbanisme s'applique a la commune, d'apres l'Etat.
# ---------------------------------------------------------------------------
#   sh theme-marly/outils/urbanisme-officiel.sh [code INSEE]
#
# Trois reponses possibles, et elles ne se devinent pas :
#   - un PLU ou un PLUi, et c'est le maire qui signe les autorisations ;
#   - une carte communale, et c'est le maire aussi si le conseil l'a decide ;
#   - aucun document, donc le reglement national d'urbanisme (RNU), et la le
#     maire signe AU NOM DE L'ETAT, apres avis conforme du prefet.
#
# Les annuaires en ligne recopient cette information et se trompent : plusieurs
# communes d'une meme intercommunalite peuvent etre couvertes par un PLUi quand
# leurs voisines ne le sont pas. On interroge donc le Geoportail de l'urbanisme
# par l'API carto de l'IGN, qui sert la donnee versee par les collectivites.
#
# Ce script ne modifie rien.
set -e

INSEE=${1:-02469}
API="https://apicarto.ign.fr/api/gpu/municipality?insee=$INSEE"

echo "Source  : Geoportail de l'urbanisme, via l'API carto de l'IGN"
echo "Commune : INSEE $INSEE"
echo

reponse=$(curl -sSL -H 'Accept: application/json' "$API")
if [ -z "$reponse" ]; then
	echo "Pas de reponse. Reessayer, ou ouvrir :"
	echo "  https://www.geoportail-urbanisme.gouv.fr/"
	exit 1
fi

# On affiche la reponse brute : c'est la piece justificative, et les noms de
# champs de cette API ont deja change une fois.
echo "--- reponse brute ---"
echo "$reponse" | tr ',' '\n' | sed 's/^[[:space:]]*//'
echo "--- fin ---"
echo

# Lecture assistee, sans dependance : on cherche les cles qui portent la
# reponse. Si l'API les renomme, la lecture brute ci-dessus reste lisible.
partition=$(echo "$reponse" | grep -o '"partition"[[:space:]]*:[[:space:]]*"[^"]*"' | head -1 | sed 's/.*"\([^"]*\)"$/\1/')
rnu=$(echo "$reponse" | grep -o '"is_rnu"[[:space:]]*:[[:space:]]*[a-z]*' | head -1 | sed 's/.*:[[:space:]]*//')

if [ "$rnu" = "true" ]; then
	echo "Aucun document local : la commune releve du reglement national"
	echo "d'urbanisme. Le maire delivre les autorisations AU NOM DE L'ETAT et"
	echo "non au nom de la commune, apres avis conforme du prefet (L422-1 et"
	echo "L422-5 du code de l'urbanisme). L'instruction est assuree par les"
	echo "services de l'Etat. Le seuil de la declaration prealable reste a"
	echo "20 m2 : le relevement a 40 m2 suppose une zone urbaine d'un PLU."
elif [ -n "$partition" ]; then
	echo "Document en vigueur : $partition"
	echo "La commune est couverte par un document local. Le maire delivre les"
	echo "autorisations au nom de la commune."
else
	echo "Ni is_rnu ni partition dans la reponse : lire le brut ci-dessus."
fi
echo
echo "A confirmer aupres de la mairie dans tous les cas : une deliberation du"
echo "conseil peut rendre la declaration prealable obligatoire pour les"
echo "clotures, ce qu'aucun fichier national ne dit."
