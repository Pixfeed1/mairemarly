#!/usr/bin/env python3
"""
Contrôle statique des squelettes, avant de pousser.

    python3 outils/verifier-squelettes.py

Ce script ne remplace pas un vrai SPIP : il attrape les fautes qui ne se
voient qu'au premier chargement, et qui coûtent un aller-retour serveur
chacune. Chaque règle vient d'une erreur réellement rencontrée.
"""
import re, sys, glob, os

RACINE = os.path.dirname(os.path.dirname(os.path.abspath(__file__)))
os.chdir(RACINE)
fautes = []


def signaler(f, ligne, quoi):
    fautes.append(f'{f}:{ligne}  {quoi}')


for f in sorted(glob.glob('squelettes/**/*.html', recursive=True)):
    s = open(f, encoding='utf-8').read()
    lignes = lambda pos: s[:pos].count('\n') + 1

    # 1. Balises SPIP dans un commentaire REM.
    #    SPIP compile ce qu'il y a dedans : un #CHEMIN cite en prose devient
    #    un appel sans argument, et le squelette part en erreur.
    for m in re.finditer(r'\[\(#REM\)(.*?)\n?\]', s, re.S):
        for t in sorted(set(re.findall(r'#[A-Z_]{2,}', m.group(1)))):
            signaler(f, lignes(m.start()), f'balise {t} citee dans un commentaire REM — retirer le #')

    # 2. Boucles ouvertes et jamais fermees.
    for nom in set(re.findall(r'<BOUCLE_([a-zA-Z0-9_]+)\s*\(', s)):
        if s.count(f'</BOUCLE_{nom}>') != 1:
            signaler(f, 1, f'BOUCLE_{nom} : {s.count(f"</BOUCLE_{nom}>")} fermeture(s), il en faut 1')

    # 3. Blocs optionnels mal apparies. <B_x> doit avoir </B_x>, et <//B_x>
    #    ne peut exister sans <B_x>.
    for nom in set(re.findall(r'<B_([a-zA-Z0-9_]+)>', s)):
        if f'</B_{nom}>' not in s:
            signaler(f, 1, f'<B_{nom}> ouvert sans </B_{nom}>')
    for nom in set(re.findall(r'<//B_([a-zA-Z0-9_]+)>', s)):
        if f'<B_{nom}>' not in s:
            signaler(f, 1, f'<//B_{nom}> sans <B_{nom}> correspondant')

    # 4. Feuilles de style et scripts appeles mais absents du depot.
    for m in re.finditer(r'#CHEMIN\{([^}]+)\}', s):
        if not os.path.exists(os.path.join('squelettes', m.group(1))):
            signaler(f, lignes(m.start()), f'CHEMIN vers un fichier absent : {m.group(1)}')

    # 5. Icones appelees mais absentes du sprite. Une icone manquante ne
    #    provoque aucune erreur : elle s'affiche vide, en silence.
    sprite = open('squelettes/inc/icones.html', encoding='utf-8').read()
    for m in re.finditer(r'<use href="#(ri-[a-z0-9-]+)"', s):
        if f'id="{m.group(1)}"' not in sprite:
            signaler(f, lignes(m.start()), f'icone absente du sprite : {m.group(1)}')

# 7. Une entree de menu declaree dans le plugin doit avoir son ecran.
#    Sans ce controle, l'entree apparait, on clique, et on obtient une page
#    vide — l'erreur ne se voit qu'a l'usage, jamais a la lecture.
paquet = os.path.join(RACINE, '..', 'plugin-marly', 'paquet.xml')
if os.path.exists(paquet):
    src = open(paquet, encoding='utf-8').read()
    for m in re.finditer(r'<menu\s+nom="([a-z_]+)"', src):
        ecran = os.path.join(RACINE, '..', 'plugin-marly',
                             'prive/squelettes/contenu', m.group(1) + '.html')
        if not os.path.exists(ecran):
            signaler('plugin-marly/paquet.xml', 1,
                     f"menu {m.group(1)} declare, mais prive/squelettes/contenu/{m.group(1)}.html manque")

    # Une tache periodique declaree doit exister elle aussi. ATTENTION : SPIP
    # PREFIXE le nom avec celui du plugin. Declarer nom="lettres" cherche
    # genie_marly_lettres_dist dans genie/marly_lettres.php. Declarer
    # nom="marly_lettres" chercherait marly_marly_lettres — erreur vecue.
    prefixe = re.search(r'prefix="([a-z_]+)"', src)
    prefixe = prefixe.group(1) if prefixe else 'marly'
    for m in re.finditer(r'<genie\s+nom="([a-z_]+)"', src):
        attendu = prefixe + '_' + m.group(1)
        tache = os.path.join(RACINE, '..', 'plugin-marly', 'genie', attendu + '.php')
        if not os.path.exists(tache):
            signaler('plugin-marly/paquet.xml', 1,
                     f"tache {m.group(1)} declaree : SPIP cherchera genie/{attendu}.php, absent")
        elif f'function genie_{attendu}_dist' not in open(tache, encoding='utf-8').read():
            signaler(f'plugin-marly/genie/{attendu}.php', 1,
                     f"la fonction doit s'appeler genie_{attendu}_dist")
        if m.group(1).startswith(prefixe + '_'):
            signaler('plugin-marly/paquet.xml', 1,
                     f"genie nom=\"{m.group(1)}\" : SPIP ajoutera deja le prefixe, retirer \"{prefixe}_\"")

# 8. Un formulaire appele doit avoir ses deux fichiers.
for f in sorted(glob.glob('squelettes/**/*.html', recursive=True)):
    s = open(f, encoding='utf-8').read()
    for m in re.finditer(r'#FORMULAIRE_([A-Z_]+)', s):
        nom = m.group(1).lower()
        base = os.path.join(RACINE, '..', 'plugin-marly', 'formulaires', nom)
        if not os.path.exists(base + '.php') and not os.path.exists(base + '.html'):
            signaler(f, s[:m.start()].count(chr(10)) + 1,
                     f"formulaire {nom} appele, mais formulaires/{nom}.php et .html manquent")

# 9. Tout ecran de l'espace prive doit controler l'acces.
#    L'espace prive n'est pas reserve aux administrateurs : un compte
#    redacteur y entre aussi. Un ecran sans #AUTORISER laisse lire — et
#    parfois modifier — ce qui ne le regarde pas. Trouve sur l'ecran de
#    redaction des lettres, qui etait ouvert a tout compte connecte.
for f in sorted(glob.glob(os.path.join(RACINE, '..', 'plugin-marly',
                                       'prive/squelettes/contenu/*.html'))):
    if '#AUTORISER{' not in open(f, encoding='utf-8').read():
        signaler('plugin-marly/prive/squelettes/contenu/' + os.path.basename(f), 1,
                 'ecran prive sans #AUTORISER : accessible a tout compte connecte')

# 10. Un formulaire d'edition doit verifier l'autorisation dans son PHP.
#     Proteger l'ecran ne suffit pas : l'action du formulaire reste une URL
#     qu'on peut appeler directement, sans jamais passer par l'ecran.
for f in sorted(glob.glob(os.path.join(RACINE, '..', 'plugin-marly',
                                       'formulaires/*.php'))):
    nom = os.path.basename(f)[:-4]
    if not (nom.startswith('editer_') or nom.startswith('configurer_')):
        continue
    if 'autoriser(' not in open(f, encoding='utf-8').read():
        signaler('plugin-marly/formulaires/' + nom + '.php', 1,
                 "formulaire d'edition sans appel a autoriser()")

# 11. Toute chaine de langue appelee doit exister.
#     Quand elle manque, SPIP n'affiche pas une erreur : il affiche la cle,
#     underscores remplaces par des espaces. << titre lettre info >> au lieu
#     de << Lettre d'information >>. Ca ressemble a une faute de frappe dans
#     le fichier de langue, on va donc la chercher la ou elle n'est pas.
#     Les chaines vivent dans DEUX fichiers du meme nom, l'un dans le theme
#     et l'autre dans le plugin : SPIP les fusionne, le controle doit donc
#     les fusionner aussi.
LANGS = [os.path.join(RACINE, 'squelettes', 'lang', 'marly_fr.php'),
         os.path.join(RACINE, '..', 'plugin-marly', 'lang', 'marly_fr.php')]
LANGS = [l for l in LANGS if os.path.exists(l)]
if LANGS:
    connues = set()
    for l in LANGS:
        connues |= set(re.findall(r"^\s*'([a-z0-9_]+)'\s*=>", open(l, encoding='utf-8').read(), re.M))

    cibles = glob.glob('squelettes/**/*.html', recursive=True)
    cibles += glob.glob(os.path.join(RACINE, '..', 'plugin-marly', '**', '*.html'),
                        recursive=True)
    cibles += [os.path.join(RACINE, '..', 'plugin-marly', 'paquet.xml')]
    cibles += glob.glob(os.path.join(RACINE, '..', 'plugin-marly', '**', '*.php'),
                        recursive=True)

    for f in sorted(set(cibles)):
        t = open(f, encoding='utf-8').read()
        # <:marly:cle:> dans les squelettes, 'marly:cle' dans paquet.xml et le PHP
        cles = set(re.findall(r'<:marly:([a-z0-9_]+)[:|]', t))
        cles |= set(re.findall(r"""["']marly:([a-z0-9_]+)["']""", t))
        court = f.replace(os.path.join(RACINE, '..'), '').lstrip('/')
        for c in sorted({c for c in cles - connues if not c.endswith('_')}):
            signaler(court, 1, f'chaine de langue absente : marly:{c}')

# 12. Toute page appelee doit exister.
#     #URL_PAGE{x} fabrique une adresse sans jamais verifier que x existe :
#     le lien s'affiche, on clique, et SPIP repond une page vide. C'est ainsi
#     que la page d'accueil a offert pendant des semaines cinq raccourcis
#     casses, et que le lien de desinscription de chaque lettre ne menait
#     nulle part.
#     Les pages ci-dessous sont fournies par SPIP lui-meme, pas par le theme.
FOURNIES_PAR_SPIP = {'login', 'recherche', 'plan', 'sommaire', 'article',
                     'rubrique', 'auteur', 'mot', 'site', 'backend'}
for f in sorted(glob.glob('squelettes/**/*.html', recursive=True)):
    s = open(f, encoding='utf-8').read()
    for m in re.finditer(r'#URL_PAGE\{([a-z0-9_-]+)', s):
        page = m.group(1)
        if page in FOURNIES_PAR_SPIP:
            continue
        if not os.path.exists(os.path.join('squelettes', page + '.html')):
            signaler(f, s[:m.start()].count(chr(10)) + 1,
                     f'page appelee mais absente : {page} (squelettes/{page}.html)')

# 6. Accolades CSS. Une accolade orpheline ferme la feuille en avance et
#    toutes les regles suivantes sont ignorees, sans le moindre message.
for f in sorted(glob.glob('squelettes/css/*.css')):
    s = open(f, encoding='utf-8').read()
    if s.count('{') != s.count('}'):
        signaler(f, 1, f"accolades desequilibrees : {s.count('{')} ouvrantes, {s.count('}')} fermantes")

# 13. Toute variable CSS employee doit etre definie.
#     var(--inconnue) ne provoque aucune erreur : la propriete est ignoree,
#     et la couleur tombe silencieusement sur celle du parent. On ne s'en
#     apercoit qu'en regardant la page — si on la regarde.
for f in sorted(glob.glob('squelettes/css/*.css')):
    s = open(f, encoding='utf-8').read()
    definies = set(re.findall(r'(--[a-z0-9-]+)\s*:', s))
    for m in re.finditer(r'var\((--[a-z0-9-]+)\s*(,[^)]*)?\)', s):
        if m.group(1) not in definies and not m.group(2):
            signaler(f, s[:m.start()].count(chr(10)) + 1,
                     f'variable CSS jamais definie : {m.group(1)}')

# 15. paquet.xml doit etre du XML valide.
#     SPIP le lit avec un vrai analyseur : au moindre defaut il rejette le
#     plugin ENTIER — plus de tables, plus de menus, plus de formulaires — et
#     dit seulement << Erreur dans les plugins >>. Un commentaire pose par
#     megarde a l'interieur de la balise ouvrante a suffi a mettre tout le
#     plugin hors service, et le message ne designait pas la ligne fautive.
import xml.dom.minidom
_paquet = os.path.join(RACINE, '..', 'plugin-marly', 'paquet.xml')
if os.path.exists(_paquet):
    try:
        xml.dom.minidom.parse(_paquet)
    except Exception as _e:
        signaler('plugin-marly/paquet.xml', 1, f'XML invalide, le plugin entier sera rejete : {_e}')

# 16. Aucune chaine de langue ne doit etre declaree deux fois.
#     PHP garde la DERNIERE et oublie la premiere, sans un mot. Une cle
#     ajoutee pour un ecran ecrase alors l'etiquette d'un autre : la salle
#     s'est retrouvee avec << Titre >> a la place de << Nom >>, et rien ne le
#     disait — il fallait rouvrir l'ecran pour s'en apercevoir.
for _l in [os.path.join(RACINE, 'squelettes', 'lang', 'marly_fr.php'),
           os.path.join(RACINE, '..', 'plugin-marly', 'lang', 'marly_fr.php')]:
    if not os.path.exists(_l):
        continue
    _vues, _src = {}, open(_l, encoding='utf-8').read().split('\n')
    for _n, _ligne in enumerate(_src, 1):
        _m = re.match(r"\s*'([a-z0-9_]+)'\s*=>", _ligne)
        if not _m:
            continue
        if _m.group(1) in _vues:
            signaler(_l.replace(os.path.join(RACINE, '..'), '').lstrip('/'), _n,
                     f"chaine declaree deux fois : {_m.group(1)} (ligne {_vues[_m.group(1)]} ecrasee)")
        _vues[_m.group(1)] = _n

# 17. Pas de tiret cadratin dans les textes lus par les habitants.
#     Employe comme ponctuation, il donne a un texte francais une tournure
#     mecanique — c'est la marque d'une redaction automatique, et sur le site
#     d'une mairie ca s'entend tout de suite. Une virgule, un deux-points ou
#     une parenthese disent la meme chose sans ce ton-la.
#     Les commentaires du code n'y sont pas soumis : personne ne les lit sur
#     le site.
for _f in [os.path.join(RACINE, 'squelettes', 'lang', 'marly_fr.php'),
           os.path.join(RACINE, '..', 'plugin-marly', 'lang', 'marly_fr.php')]:
    if not os.path.exists(_f):
        continue
    for _n, _ligne in enumerate(open(_f, encoding='utf-8').read().split('\n'), 1):
        if re.match(r"\s*'[a-z0-9_]+'\s*=>", _ligne) and ('\u2014' in _ligne or '\u2013' in _ligne):
            signaler(_f.replace(os.path.join(RACINE, '..'), '').lstrip('/'), _n,
                     'tiret cadratin dans un texte affiche : preferer une virgule ou un deux-points')

# 18. Pas de balise de langue dans un argument de filtre.
#     SPIP n'interprete pas <:marly:cle:> a l'interieur de ?{...} : il le
#     recopie tel quel, et l'ecran affiche << <:marly:publication_oui:> >>.
#     Dix-sept occurrences dormaient dans l'espace prive depuis le debut,
#     invisibles tant qu'aucune liste n'avait de contenu.
#     La bonne forme passe la CLE au ternaire puis traduit le resultat :
#     ?{'marly:oui','marly:non'}|_T — ou, quand une seule branche est une
#     cle, des blocs conditionnels.
for f in sorted(glob.glob('squelettes/**/*.html', recursive=True) +
                glob.glob(os.path.join(RACINE, '..', 'plugin-marly', '**', '*.html'), recursive=True)):
    s = open(f, encoding='utf-8').read()
    for m in re.finditer(r"\?\{[^}]*<:[a-z_]+:", s):
        signaler(f.replace(os.path.join(RACINE, '..'), '').lstrip('/'),
                 s[:m.start()].count(chr(10)) + 1,
                 'balise de langue dans un argument de filtre : elle sera affichee telle quelle')

# 14. Le schema declare doit valoir la derniere etape de mise a jour.
#     paquet.xml porte DEUX numeros : << version >>, celle du plugin, et
#     << schema >>, celle de la base. SPIP ne compare que la seconde a ce
#     qu'il a enregistre : tant qu'elle ne bouge pas, marly_upgrade() n'est
#     jamais appele. Les fichiers sont neufs, les tables restent vieilles, et
#     RIEN ne le dit — jusqu'a ce qu'un gabarit interroge une table absente.
#     Ce piege a laisse la base cinq versions en arriere sans que personne
#     s'en apercoive.
paquet = os.path.join(RACINE, '..', 'plugin-marly', 'paquet.xml')
admin = os.path.join(RACINE, '..', 'plugin-marly', 'marly_administrations.php')
if os.path.exists(paquet) and os.path.exists(admin):
    declare = re.search(r'schema="([0-9.]+)"', open(paquet, encoding='utf-8').read())
    etapes = re.findall(r"\$maj\['([0-9.]+)'\]", open(admin, encoding='utf-8').read())
    if declare and etapes:
        derniere = max(etapes, key=lambda v: [int(n) for n in v.split('.')])
        if declare.group(1) != derniere:
            signaler('plugin-marly/paquet.xml', 1,
                     f'schema="{declare.group(1)}" alors que la derniere etape '
                     f'de mise a jour est {derniere} : marly_upgrade ne sera pas appele')

if fautes:
    print('\n'.join(fautes))
    print(f'\n{len(fautes)} probleme(s).')
    sys.exit(1)
print('Squelettes verifies : rien a signaler.')
