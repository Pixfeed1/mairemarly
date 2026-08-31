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

# UN SCRIPT QUI MEURT EN SILENCE EST PIRE QU'UN SCRIPT QUI ECHOUE.
#
# Avec set -e, n'importe quelle commande qui sort en erreur arrete tout, sans
# un mot. Le 30 aout 2026 le deploiement n'a rien affiche — ni en-tete, ni
# erreur — et il a fallu reproduire la ligne fautive a la main pour comprendre
# que c'etait un grep sans resultat. Rien a l'ecran ressemble a rien a faire.
#
# Ce piege nomme la ligne et dit que le travail n'est pas fait.
trap 'CODE=$?; if [ "$CODE" != 0 ]; then
	echo
	echo "  ================================================================"
	echo "  LE DEPLOIEMENT S EST ARRETE LIGNE $LINENO, SANS ALLER AU BOUT."
	echo "  Le site est peut-etre a moitie copie. Relancez apres correction."
	echo "  ================================================================"
fi' EXIT

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

# Le depot est-il ecrivable par l'utilisateur courant ?
# ---------------------------------------------------------------------------
# Un deploiement lance en root laisse des fichiers lui appartenant dans .git/.
# Le pull suivant, lance sous l'utilisateur du site, echoue alors sur
# << cannot open '.git/FETCH_HEAD': Permission denied >> — et comme le pull est
# une commande SEPAREE de ce script, son echec n'empeche rien : on recopie
# fidelement un depot reste a l'ancien commit, et le site ne bouge pas.
#
# On l'a vecu : trois deploiements de suite ont annonce << Deploye >> en
# copiant du vieux code. Le script le dit desormais avant de commencer.
if [ -d "$DEPOT/.git" ] && [ ! -w "$DEPOT/.git" ]; then
	echo "  ================================================================"
	echo "  LE DEPOT N'EST PAS ECRIVABLE par $(id -un)."
	echo "  git pull echouera, et ce script recopiera du code perime sans"
	echo "  que rien ne le signale. A rendre a son proprietaire, en root :"
	echo "      chown -R $(stat -c %U "$DEPOT"):$(stat -c %G "$DEPOT") $DEPOT"
	echo "  ================================================================"
	echo
fi

# ---------------------------------------------------------------------------
# ON NE COPIE PAS UN PHP QUI NE COMPILE PAS.
#
# Le 30 aout 2026, un marly_fonctions.php avec une erreur de syntaxe est parti
# sur le serveur. Le plugin entier cessait de se charger. Le controle des
# pages, lui, a annonce << 8 pages ouvertes, tout repond >> : elles repondaient
# bien 200 et ne contenaient pas << Erreur d'execution >> — elles affichaient
# << Parse error >>, que rien ne cherchait.
#
# Une seconde de php -l avant la copie evite de mettre un site par terre.
if command -v php >/dev/null 2>&1; then
	# LE || true N'EST PAS UNE PRECAUTION, IL EST OBLIGATOIRE. grep qui ne
	# trouve rien sort en ERREUR, et set -e tue alors le script — c'est-a-dire
	# exactement quand tout va bien. Le 30 aout 2026 le deploiement n'a plus
	# rien affiche du tout, pas meme son en-tete : il mourait sur ce grep.
	CASSES=$(find "$DEPOT/plugin-marly" -name '*.php' -exec php -l {} \; 2>&1 \
	         | grep -v '^No syntax errors' | grep -v '^$' || true)
	if [ -n "$CASSES" ]; then
		echo "  ================================================================"
		echo "  RIEN N'A ETE COPIE : du PHP du plugin ne compile pas."
		echo "$CASSES" | head -6 | sed 's/^/  /'
		echo "  ================================================================"
		exit 1
	fi
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

	# IMG/ ET local/ AUSSI, ET C'EST LE PLUS IMPORTANT DES TROIS.
	#
	# Tout script lance en root y depose des fichiers lui appartenant : un
	# import, une reparation d'images, une mise a jour de base. Le serveur
	# refuse alors de les servir — et il repond 404, PAS 403. Un 404 envoie
	# chercher la panne du cote du chemin ou du fichier manquant, alors que
	# le fichier est la, a la bonne taille, lisible par tous.
	#
	# Mesure du 27 aout 2026 : les 44 photographies rapatriees et les 52 PDF
	# de comptes rendus etaient invisibles pour cette seule raison. Les PDF
	# depuis l'import du 22 aout — cinq jours de liens morts sur la page
	# d'accueil, que personne n'avait vus faute d'avoir clique.
	for d in IMG local; do
		if [ -d "$SITE/$d" ]; then
			chown -R --reference="$SITE" "$SITE/$d"
		fi
	done
fi

# ON RELIT PLUTOT QUE DE CROIRE LE chown SUR PAROLE.
if [ -d "$SITE/IMG" ]; then
	etrangers=$(find "$SITE/IMG" ! -user "$(stat -c %U "$SITE")" | head -5)
	if [ -n "$etrangers" ]; then
		echo "  ================================================================"
		echo "  DES FICHIERS DE IMG/ N'APPARTIENNENT PAS A $(stat -c %U "$SITE") :"
		echo "$etrangers" | sed 's/^/      /'
		echo "  Le serveur repondra 404 dessus, sans dire pourquoi."
		echo "  A corriger en root :"
		echo "      chown -R $(stat -c %U "$SITE") $SITE/IMG"
		echo "  ================================================================"
	fi
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

# Quel commit vient d'etre copie. Sans cette ligne, rien a l'ecran ne
# distingue un deploiement a jour d'un deploiement qui recopie du vieux code :
# les deux affichent exactement le meme compte rendu.
if [ -d "$DEPOT/.git" ] && command -v git >/dev/null 2>&1; then
	echo "Code deploye     : $(git -C "$DEPOT" log -1 --format='%h du %ad — %s' --date=format:'%d/%m %H:%M' 2>/dev/null)"
fi

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

# ---------------------------------------------------------------------------
# UN COUP D'OEIL SUR LE SITE AVANT DE DIRE << DEPLOYE >>.
#
# Le 29 aout 2026 a 23 h 55, ce script a affiche << Deploye >> et une base a
# jour pendant que TOUTES les pages d'article du site rendaient
# << Erreur d'execution >>. Une fonction du noyau de SPIP avait disparu et un
# filtre du plugin l'appelait encore. Le script ne pouvait pas le savoir : il
# verifiait ce qu'il avait COPIE, jamais ce que le site RENDAIT.
#
# Il ouvre donc maintenant quelques pages. Pas un controle complet — c'est le
# role de verifier-fichiers.php, qui parcourt les 162 pages — mais les six
# familles de gabarits, en trois secondes.
#
# UNE PAGE D'ARTICLE EST TIREE DE LA BASE, et non ecrite en dur : un
# identifiant fige finirait par pointer sur un article depublie, et le
# controle crierait pour rien.
#
# LE MOT DE PASSE DU SITE N'EST PAS DANS CE FICHIER. Le depot est versionne.
#
# Il vient de deux endroits, dans cet ordre : la variable MARLY_AUTH si elle
# est posee, sinon le fichier ~/.marly-auth, hors du depot et lisible du seul
# proprietaire. Le fichier existe parce que la variable, il fallait la reposer
# a chaque session : le 31 aout 2026 le controle a ete saute deux fois de
# suite, une fois parce qu'un ^C avait mange la saisie, une fois parce qu'un
# collage de plusieurs lignes avait ete avale par la lecture interactive. Un
# controle qu'on oublie d'armer ne controle rien.
#
# Sans l'un ni l'autre, il s'annonce comme saute plutot que de faire croire
# qu'il a eu lieu.
echo
FICHIER_AUTH="$HOME/.marly-auth"
if [ -z "$MARLY_AUTH" ] && [ -r "$FICHIER_AUTH" ]; then
	# head -1 : une ligne, sans le retour chariot, meme si le fichier en a
	# recu plusieurs par megarde.
	MARLY_AUTH=$(head -1 "$FICHIER_AUTH" | tr -d '\r\n')
	if [ -n "$MARLY_AUTH" ]; then
		echo "Identifiants lus dans $FICHIER_AUTH."
	fi
fi
if [ -z "$MARLY_AUTH" ]; then
	echo "Controle des pages : SAUTE (aucun identifiant)."
	echo "  Pour l'armer une fois pour toutes :"
	echo "    printf 'mairie:motdepasse\\n' > ~/.marly-auth && chmod 600 ~/.marly-auth && history -c"
	echo "  Ou pour une seule fois :  MARLY_AUTH='mairie:motdepasse' bash $0"
else
	# L'ADRESSE DU SITE VIENT DE LA BASE, ou SPIP la garde. La deduire du nom
	# du dossier marcherait ici et nulle part ailleurs.
	RACINE_SITE=$(mysql -h "${HOTE:-localhost}" -u "$LOGIN" -p"$PASSE" "$BASE" -N -B \
	              -e "SELECT valeur FROM spip_meta WHERE nom='adresse_site'" 2>/dev/null)
	RACINE_SITE=${RACINE_SITE%/}
	# DEUX ARTICLES, ET C'EST TOUT LE SUJET. Le 30 aout 2026, ce controle a
	# annonce << tout repond >> pendant que toutes les pages d'article ILLUSTRE
	# rendaient << Erreur d'execution >>. Il n'en ouvrait qu'un, le plus
	# recent, et celui-la n'avait pas d'image : il passait donc par l'en-tete
	# sans photographie et ne touchait jamais l'autre.
	#
	# Un article porte deux mises en page selon qu'il a une image ou non. Un
	# controle qui n'en essaie qu'une n'en controle que la moitie, et il le
	# dit avec l'aplomb d'un resultat complet.
	ART=$(mysql -h "${HOTE:-localhost}" -u "$LOGIN" -p"$PASSE" "$BASE" -N -B \
	      -e "SELECT id_article FROM spip_articles WHERE statut='publie' ORDER BY date DESC LIMIT 1" 2>/dev/null)
	ART_ILLUSTRE=$(mysql -h "${HOTE:-localhost}" -u "$LOGIN" -p"$PASSE" "$BASE" -N -B \
	      -e "SELECT a.id_article FROM spip_articles a
	          JOIN spip_documents_liens l ON l.id_objet = a.id_article AND l.objet = 'article'
	          JOIN spip_documents d ON d.id_document = l.id_document AND d.mode = 'image'
	          WHERE a.statut = 'publie' GROUP BY a.id_article LIMIT 1" 2>/dev/null)
	ADRESSES="/ \
	          /spip.php?page=actualites \
	          /spip.php?page=associations \
	          /spip.php?page=demarches \
	          /spip.php?page=plan \
	          /spip.php?page=credits"
	if [ -n "$ART" ]; then
		ADRESSES="$ADRESSES /spip.php?page=article&id_article=$ART"
	fi
	if [ -n "$ART_ILLUSTRE" ] && [ "$ART_ILLUSTRE" != "$ART" ]; then
		ADRESSES="$ADRESSES /spip.php?page=article&id_article=$ART_ILLUSTRE"
	fi

	if [ -z "$RACINE_SITE" ]; then
		echo "Controle des pages : SAUTE (adresse du site introuvable en base)."
		ADRESSES=""
	fi

	CASSEES=0
	VUES=0
	for CHEMIN in $ADRESSES; do
		VUES=$((VUES + 1))
		SORTIE=$(curl -s -u "$MARLY_AUTH" --max-time 20 -w '\n%{http_code}' "$RACINE_SITE$CHEMIN" 2>/dev/null)
		CODE=$(printf '%s' "$SORTIE" | tail -1)
		CORPS=$(printf '%s' "$SORTIE" | sed '$d')
		if [ "$CODE" != "200" ]; then
			echo "  CASSE  $CHEMIN  ->  code $CODE"
			CASSEES=$((CASSEES + 1))
		elif printf '%s' "$CORPS" | grep -q "Erreur d’exécution\|Erreur d'execution\|Parse error\|Fatal error\|Warning: \|Notice: "; then
			LIGNE=$(printf '%s' "$CORPS" | grep -o "Erreur d[’']ex[eé]cution[^<]*\|Parse error[^<]*\|Fatal error[^<]*\|Warning: [^<]*\|Notice: [^<]*" | head -1)
			echo "  CASSE  $CHEMIN  ->  $LIGNE"
			CASSEES=$((CASSEES + 1))
		fi
	done

	if [ "$VUES" -eq 0 ]; then
		:
	elif [ "$CASSEES" -eq 0 ]; then
		echo "Controle des pages : $VUES pages ouvertes, tout repond."
	else
		echo
		echo "  ================================================================"
		echo "  $CASSEES PAGE(S) CASSEE(S). Le code est copie, le site ne rend pas."
		echo "  Le journal de SPIP nomme la cause, avec le fichier et la ligne :"
		echo "      tail -20 $SITE/tmp/log/spip.log"
		echo "  ================================================================"
	fi
fi
