#!/bin/sh
# Qui siege au conseil municipal, d'apres la source qui fait foi.
# ---------------------------------------------------------------------------
#   sh theme-marly/outils/elus-officiels.sh [commune]
#
# Le Repertoire national des elus est tenu par les prefectures et le ministere
# de l'Interieur. C'est le seul fichier qui dise, sans intermediaire, qui est
# maire, qui est adjoint et dans quel ordre. Les sites d'annuaires et les
# resumes automatiques recopient ce fichier plus ou moins bien, avec du retard
# et parfois un nom de trop : on va le lire directement.
#
# Ce script ne modifie rien. Il affiche, c'est tout. La saisie des fiches se
# fait a la main dans l'espace prive, apres lecture.
set -e

COMMUNE=${1:-Marly-Gomont}
API="https://www.data.gouv.fr/api/1/datasets/repertoire-national-des-elus-1/"

echo "Source : Repertoire national des elus (ministere de l'Interieur)"
echo "Commune : $COMMUNE"
echo

# L'adresse du fichier change a chaque mise a jour du repertoire. On ne la met
# donc pas en dur : on demande a data.gouv.fr celle du jour.
#
# ON NE DEDUIT PAS L'ADRESSE DU TITRE. Mesure faite le 25 aout 2026 : la fiche
# du jeu de donnees annonce un fichier intitule
#   elus-conseillers-municipaux-cm.csv        (pluriel des deux cotes)
# servi a l'adresse
#   .../elus-conseiller-municipal-cm.csv      (singulier des deux cotes)
# Chercher le titre dans l'adresse ne trouvait donc rien, et le script
# renvoyait << fichier introuvable >> sur un jeu de donnees parfaitement en
# ligne. Ce qui distingue vraiment les cinq fichiers, c'est leur suffixe : cm
# pour les conseillers municipaux, ca pour les conseillers d'arrondissement,
# cd pour les departementaux, cr pour les regionaux, epci pour les
# communautaires. On s'appuie sur lui.
url=$(curl -sSL "$API" | grep -o 'https://[^"]*-cm\.csv' | head -1)
if [ -z "$url" ]; then
	echo "Aucun fichier en -cm.csv dans la fiche du jeu de donnees. Les fichiers"
	echo "publies aujourd'hui sont :"
	curl -sSL "$API" | grep -o 'https://[^"]*\.csv' | sed 's/^/  /'
	echo
	echo "Reprendre celui des conseillers municipaux et l'indiquer a la main."
	exit 1
fi
echo "Fichier : $url"
echo

tmp=$(mktemp)
trap 'rm -f "$tmp"' EXIT
curl -sSL -o "$tmp" "$url"

# Le repertoire a change de separateur au fil des versions : point-virgule
# autrefois, tabulation aujourd'hui. On regarde la premiere ligne plutot que
# de parier.
entete=$(head -1 "$tmp")
sep=';'
if [ "$(printf '%s' "$entete" | tr -cd '\t' | wc -c)" -gt "$(printf '%s' "$entete" | tr -cd ';' | wc -c)" ]; then
	sep=$(printf '\t')
fi

awk -F"$sep" -v commune="$COMMUNE" '
NR == 1 {
	# On repere les colonnes par leur intitule et non par leur rang : le
	# repertoire en ajoute de temps en temps, et un rang en dur se decale
	# sans prevenir.
	for (i = 1; i <= NF; i++) {
		t = $i
		gsub(/^\xef\xbb\xbf/, "", t)          # marque d octets de tete
		if (t ~ /^Lib/ && t ~ /commune/)  c_commune  = i
		else if (t ~ /^Nom/)              c_nom      = i
		else if (t ~ /^Pr/)               c_prenom   = i
		else if (t ~ /^Lib/ && t ~ /fonction/) c_fonction = i
		else if (t ~ /^Date/ && t ~ /naissance/) c_naissance = i
		else if (t ~ /^Date/ && t ~ /mandat/)    c_mandat  = i
	}
	next
}
$c_commune == commune {
	n++
	f = (c_fonction ? $c_fonction : "")
	if (f == "") f = "Conseiller municipal"
	printf "%-28s %-26s %s\n", f, $c_nom " " $c_prenom, (c_mandat ? "mandat depuis le " $c_mandat : "")
}
END {
	print ""
	if (n == 0) {
		print "Aucune ligne pour cette commune. Verifier l orthographe exacte,"
		print "tiret compris, telle qu elle figure au repertoire."
	} else {
		print n " elu(s) au conseil municipal."
		print ""
		print "A lire avec la date de mise a jour du repertoire : une equipe elue en"
		print "mars n y figure qu apres transmission par la prefecture. La fonction"
		print "vide vaut conseiller municipal sans delegation. Le repertoire donne le"
		print "rang des adjoints, jamais le contenu de leur delegation : celui-la ne"
		print "se trouve que dans l arrete de delegation signe par le maire."
	}
}
' "$tmp"
