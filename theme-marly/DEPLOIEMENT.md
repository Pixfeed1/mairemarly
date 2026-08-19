# Déployer sur marlygomont.pixfeed.net

Cible : `serveur2`, utilisateur `jurojinn`, racine web
`~/marlygomont.pixfeed.net` — le dossier qui contient déjà `cgi-bin` et
`php.ini`.

Principe : **SPIP n'est pas dans le dépôt.** On ne versionne que ce qu'on
écrit — le thème. SPIP s'installe sur le serveur et se met à jour tout seul ;
le thème se met à jour par `git pull`. Les deux ne se marchent pas dessus.

---

## 1. Vérifier PHP

```bash
php -v
```

SPIP 4.4 demande PHP 8.1 au minimum. Si le serveur en propose plusieurs,
c'est le `php.ini` déjà présent dans le dossier qui fixe la version — il faut
la régler avant d'installer, pas après.

## 2. Installer SPIP

L'installeur officiel s'appelle `spip_loader.php`. Il télécharge et
décompresse la bonne version en créant les dossiers avec les bons droits —
plus fiable que déposer une archive à la main.

**Vérifier l'adresse avant de télécharger.** Elle a changé au fil des
versions, et une adresse morte rend un fichier de 16 octets contenant
« File not found. » qu'on installe ensuite sans comprendre pourquoi rien ne
marche. Le test tient en une commande :

```bash
for u in https://www.spip.net/spip-dev/INSTALL/spip_loader.php \
         https://get.spip.net/spip_loader.php ; do
  printf '%-56s ' "$u"
  curl -sIL -m 15 "$u" | awk 'BEGIN{IGNORECASE=1}
    /^HTTP/{c=$2} /^content-length/{l=$2} END{print c, l+0 " octets"}'
done
```

On garde celle qui rend `200` et **plus de 50 000 octets**. Un loader fait
plus de 100 Ko : tout ce qui est en dessous est une page d'erreur.

> Vérifié le 19 août 2026 depuis `serveur2` : les deux adresses rendent
> `200` et **190 156 octets** — c'est le même fichier. Le test reste dans
> cette procédure : ce qui est vrai aujourd'hui ne l'était pas hier, et
> une adresse morte coûte une heure à diagnostiquer.

```bash
cd ~/marlygomont.pixfeed.net
curl -fL -o spip_loader.php <ADRESSE_RETENUE>
ls -l spip_loader.php          # controle : la taille doit correspondre
```

Puis ouvrir `https://marlygomont.pixfeed.net/spip_loader.php` dans le
navigateur.

Si les deux adresses échouent, la page officielle <https://get.spip.net/>
donne le lien à jour.

Il faudra une base de données MySQL (à créer dans le panneau de
l'hébergement) et ses identifiants. **Supprimer `spip_loader.php` une fois
l'installation finie.**

## 3. Poser le thème

On clone **hors de la racine web** : le dépôt contient aussi `refonte/` et
`pentest/`, qui n'ont rien à faire sur le web.

```bash
cd ~
git clone -b claude/refonte-spip-mairie-marly-o47sn4 \
  https://github.com/Pixfeed1/mairemarly.git depot-marly
```

Puis on dit à SPIP où chercher ses gabarits. **Après l'installation**, pas
avant : ce fichier est lu à chaque requête, y compris par l'installeur.

```bash
mkdir -p ~/marlygomont.pixfeed.net/config
cat > ~/marlygomont.pixfeed.net/config/mes_options.php <<'EOF'
<?php
// Les gabarits vivent dans le depot Git, hors de la racine web.
$GLOBALS['dossier_squelettes'] = '../depot-marly/theme-marly/squelettes';
EOF
```

Si l'hébergement refuse de sortir de la racine web, on rapatrie le dépôt
dedans et on pointe `'theme-marly/squelettes'` — en ajoutant alors un
`.htaccess` qui interdit l'accès au dossier du dépôt.

## 4. Interdire l'indexation

Un site de test ne doit pas se retrouver dans Google sous le nom de la
commune. Deux verrous, pas un.

```bash
cd ~/marlygomont.pixfeed.net
printf 'User-agent: *\nDisallow: /\n' > robots.txt
htpasswd -c ~/.htpasswd-marly mairie      # demande un mot de passe
```

Puis dans `.htaccess` à la racine :

```apache
AuthType Basic
AuthName "Site en preparation"
AuthUserFile /home/jurojinn/.htpasswd-marly
Require valid-user
```

`robots.txt` demande poliment ; le mot de passe, lui, ferme la porte. C'est
le second qui compte.

## 5. La boucle de mise à jour

Une fois posé, tester une modification tient en une commande. Le script est
dans le dépôt : `theme-marly/outils/maj-serveur.sh`.

```bash
~/depot-marly/theme-marly/outils/maj-serveur.sh
```

Il fait trois choses : `git pull`, puis vidage du cache des gabarits
compilés — sans quoi SPIP continue de servir l'ancienne version — puis un
rappel de ce qui a changé.

## 6. Ce qui reste à faire une fois SPIP debout

Dans l'espace privé, en suivant `CONFIGURATION.md` :

1. créer les deux groupes de mots-clés, `Icônes` et `Emplacements` ;
2. créer les mots-clés d'icônes depuis `outils/palette-icones.txt` ;
3. créer les rubriques racines qui formeront le menu ;
4. déposer une photographie du village comme logo du site
   (`Configuration ▸ Identité du site`) — elle remplacera l'illustration
   provisoire de la bannière.

## 7. Les trois points à vérifier en premier

Ces gabarits n'ont jamais tourné sur un SPIP en fonctionnement. Au premier
chargement, contrôler dans cet ordre :

| À vérifier | Comment |
|---|---|
| Le critère `{type=Icônes}`, accent compris | créer un raccourci avec une icône, voir si le dessin sort |
| Le critère `{titre_mot=Raccourcis}` | le raccourci apparaît-il à la place des six par défaut ? |
| `#VIRTUEL` sur un article virtuel | mettre `=https://www.service-public.fr/` en chapeau, cliquer |

Si l'un des trois échoue, c'est une correction de gabarit, pas un problème
d'installation.
