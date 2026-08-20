#!/bin/sh
# Verifie que les liens du socle des demarches repondent bien.
# ---------------------------------------------------------------------------
#   sh theme-marly/outils/verifier-liens.sh
#
# Le socle renvoie vers des fiches precises de service-public.gouv.fr et vers
# les teleservices de l'ANTS. Ces adresses ont ete ecrites a la main : une
# seule erreur de reference envoie quelqu'un sur une page inexistante, ou pire
# sur la mauvaise fiche — et rien dans le code ne peut le detecter.
#
# D'ou ce controle. Il ne remplace pas une relecture : une adresse qui repond
# 200 peut toujours pointer vers la mauvaise demarche. Mais il attrape les
# references mortes, qui sont l'erreur la plus probable.
set -u

SOCLE="$(dirname "$0")/../../plugin-marly/inc/marly_demarches.php"
SOUCIS=0

grep -o "'lien[a-z_]*'[[:space:]]*=>[[:space:]]*'https\?://[^']*'" "$SOCLE" \
	| sed "s/.*'\(https\?:\/\/[^']*\)'.*/\1/" | sort -u > /tmp/marly-liens.$$

while IFS= read -r url; do
	code=$(curl -s -o /dev/null -w '%{http_code}' -L --max-time 20 \
	       -A 'Mozilla/5.0 (verification de liens, site de la commune)' "$url")
	printf '%-4s  %s\n' "$code" "$url"
	case "$code" in 200|301|302) ;; *) SOUCIS=1 ;; esac
done < /tmp/marly-liens.$$

rm -f /tmp/marly-liens.$$

echo
if [ "$SOUCIS" = 0 ]; then
	echo "Tous les liens du socle repondent."
else
	echo "Des liens ne repondent pas : corriger inc/marly_demarches.php,"
	echo "puis les fiches deja en base depuis Edition > Demarches."
fi
