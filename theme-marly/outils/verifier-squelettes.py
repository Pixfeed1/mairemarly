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
        if s.count(f'<//B_{nom}>') > 1:
            signaler(f, 1, f'<//B_{nom}> ecrit {s.count(f"<//B_{nom}>")} fois : il n\'en faut qu\'un')

    # 3bis. La boucle doit se trouver ENTRE <B_x> et </B_x>. Fermer le bloc
    #       avant elle ne provoque aucune erreur : SPIP sort le contenu, mais
    #       APRES le reste de la page. Le tableau se retrouvait sous le pied
    #       de page de SPIP, et l'alternative << aucun element >> s'affichait
    #       en meme temps que les elements.
    for nom in set(re.findall(r'<B_([a-zA-Z0-9_]+)>', s)):
        if f'</B_{nom}>' in s and f'<BOUCLE_{nom}(' in s:
            if s.index(f'</B_{nom}>') < s.index(f'<BOUCLE_{nom}('):
                signaler(f, s[:s.index(f'</B_{nom}>')].count(chr(10)) + 1,
                         f'</B_{nom}> ferme le bloc AVANT la boucle : le contenu sortira hors de la page')

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
        # #VAL{marly:cle}|_T : la forme employee quand la chaine part dans du
        # JavaScript, ou une apostrophe non encodee casserait le script.
        cles |= set(re.findall(r'#VAL\{marly:([a-z0-9_]+)\}', t))
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

# 19. Tout filtre marly_* appele dans un gabarit doit vivre dans
#     marly_fonctions.php, le seul fichier que SPIP charge pour compiler un
#     squelette. Range ailleurs, le filtre existe pour le PHP mais reste
#     introuvable pour un gabarit, qui s'arrete sur
#     << Filtre marly_xxx non defini >>.
_FONCTIONS = os.path.join(RACINE, '..', 'plugin-marly', 'marly_fonctions.php')
if os.path.exists(_FONCTIONS):
    _src = open(_FONCTIONS, encoding='utf-8').read()
    _appeles = set()
    for _f in glob.glob('squelettes/**/*.html', recursive=True) + \
              glob.glob(os.path.join(RACINE, '..', 'plugin-marly', '**', '*.html'), recursive=True):
        _appeles |= set(re.findall(r'\|(marly_[a-z0-9_]+)', open(_f, encoding='utf-8').read()))
    for _nom in sorted(_appeles):
        if (f'function filtre_{_nom}_dist' not in _src
                and f'function filtre_{_nom}(' not in _src
                and f'function {_nom}(' not in _src):
            signaler('plugin-marly/marly_fonctions.php', 1,
                     f'filtre {_nom} appele dans un gabarit mais absent de ce fichier')

# 20. Tout fichier PHP du plugin doit contenir au moins une fonction.
#     Une modification automatique mal cadrée peut vider un fichier de tout
#     son contenu sans que rien ne s'en plaigne : php -l trouve << <?php >>
#     parfaitement valide, et l'erreur n'apparait qu'a l'execution, sous la
#     forme d'une fonction introuvable appelee depuis ailleurs. C'est arrive
#     a inc/marly_associations.php, reduit a sa premiere ligne.
for _f in sorted(glob.glob(os.path.join(RACINE, '..', 'plugin-marly', '**', '*.php'), recursive=True)):
    _src = open(_f, encoding='utf-8').read()
    if 'function ' not in _src and 'lang/' not in _f and 'paquet' not in _f:
        signaler(_f.replace(os.path.join(RACINE, '..'), '').lstrip('/'), 1,
                 'fichier PHP sans aucune fonction : contenu perdu ?')

# 21. Un meme champ doit se presenter pareil sur tous les ecrans.
#     C'est la regle qui manquait le jour ou j'ai corrige l'ordre d'affichage
#     sur un ecran en oubliant les autres : deux facons de poser la meme
#     question dans le meme back office, et rien pour le signaler.
#
#     Certaines divergences sont VOULUES : les horaires de la mairie ne sont
#     pas ceux d'une association, l'objet d'un courriel n'est pas le titre
#     d'une salle. Elles sont donc declarees ici, une par une, avec leur
#     raison. Toute divergence non declaree est une erreur.
DIVERGENCES_VOULUES = {
    'horaires': "horaires d'ouverture de la mairie / creneaux d'activite d'une association",
    'lieu':     "lieu d'un evenement / ou se pratique l'activite d'une association",
    'nom':      "nom d'une association / nom d'une personne",
    'rang':     "rang protocolaire d'un elu / place parmi six raccourcis",
    'statut':   "publication d'une fiche / ouverture d'une salle a la reservation",
    'titre':    "intitule d'une fiche / nom d'une salle / objet d'un courriel",
    'video':    "video annoncee dans une lettre / video d'un evenement",
    'adresse':  "adresse postale officielle de la mairie / adresse d'un batiment de la commune",
}
_FORMS = os.path.join(RACINE, '..', 'plugin-marly', 'formulaires')
if os.path.isdir(_FORMS):
    _champs = {}
    for _f in sorted(glob.glob(os.path.join(_FORMS, '*.html'))):
        _ecran = os.path.basename(_f)
        _src = open(_f, encoding='utf-8').read()
        for _li in re.findall(r'<li class="marly-champ[^"]*">(.*?)</li>', _src, re.S):
            _n = re.search(r'name="([a-z_]+)"', _li)
            _l = re.search(r'<label[^>]*>\s*<:marly:([a-z0-9_]+):>', _li)
            if _n and _l:
                _champs.setdefault(_n.group(1), {})[_ecran] = _l.group(1)
    for _nom, _par_ecran in sorted(_champs.items()):
        if len(set(_par_ecran.values())) > 1 and _nom not in DIVERGENCES_VOULUES:
            _detail = ', '.join(f'{e}={c}' for e, c in sorted(_par_ecran.items()))
            signaler('plugin-marly/formulaires', 1,
                     f'le champ {_nom} porte des etiquettes differentes selon l\'ecran ({_detail}) : '
                     f'unifier, ou declarer la divergence dans DIVERGENCES_VOULUES')

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

# 22. Un message de journal doit porter sa gravite, sinon SPIP le jette.
#     spip_log($msg, 'marly') part en niveau << info >>, et le filtre par
#     defaut de SPIP s'arrete un cran au-dessus : le message est ecrit puis
#     abandonne, tmp/log/marly.log n'existe meme pas. On a cherche trois fois
#     dans un fichier vide une reponse qui n'y serait jamais arrivee. La
#     gravite s'ecrit en suffixe du nom du journal, apres un point.
for _f in sorted(glob.glob(os.path.join(RACINE, '..', 'plugin-marly', '**', '*.php'), recursive=True)):
    _src = open(_f, encoding='utf-8').read()
    for _m in re.finditer(r"spip_log\((?:[^()]|\([^()]*\))*?\)", _src):
        _appel = _m.group(0)
        if '_LOG_' in _appel:
            # La gravite est la, mais le point separateur peut manquer :
            # 'marly' . _LOG_ERREUR nomme un journal marly6, et le message
            # part dans un fichier que personne ne lira jamais.
            if re.search(r"'\s*\.\s*_LOG_", _appel) and not re.search(r"\.'\s*\.\s*_LOG_", _appel):
                signaler(os.path.relpath(_f, os.path.join(RACINE, '..')),
                         _src[:_m.start()].count('\n') + 1,
                         "gravite collee au nom du journal sans point : le journal s'appellerait "
                         "marly6. Ecrire 'marly.' . _LOG_...")
            continue
        # Un appel sans nom de journal va dans spip.log, ecrit quoi qu'il arrive.
        if not re.search(r",\s*'[^']+'\s*\)$", _appel):
            continue
        signaler(os.path.relpath(_f, os.path.join(RACINE, '..')),
                 _src[:_m.start()].count('\n') + 1,
                 'spip_log sans gravite : SPIP filtre le niveau info par defaut '
                 "et le message n'est jamais ecrit. Suffixer le nom du journal "
                 "par '.' . _LOG_INFO_IMPORTANTE")

# 23. Une valeur SPIP posee dans du JavaScript doit etre brute et encodee.
#     #ENV{x} protege sa sortie pour du HTML : une adresse d'action y perd
#     ses separateurs, qui deviennent &amp;. SPIP recoit alors des parametres
#     nommes amp;action, ne reconnait plus l'appel et repond une page entiere
#     au lieu du JSON attendu. Rien ne le signale : le code HTTP reste 200.
#     La forme sure est #ENV**{x}|json_encode, qui rend une chaine JavaScript
#     complete, guillemets compris.
for _f in sorted(glob.glob('squelettes/**/*.html', recursive=True) +
                 glob.glob(os.path.join(RACINE, '..', 'plugin-marly', '**', '*.html'), recursive=True)):
    _src = open(_f, encoding='utf-8').read()
    for _bloc in re.finditer(r'<script\b[^>]*>(.*?)</script>', _src, re.S | re.I):
        for _m in re.finditer(r'#ENV(\*{0,2})\{([a-z0-9_]+)[^}]*\}', _bloc.group(1)):
            if _m.group(1) == '**':
                continue
            signaler(_f.replace(os.path.join(RACINE, '..'), '').lstrip('/'),
                     _src[:_bloc.start(1) + _m.start()].count(chr(10)) + 1,
                     f'#ENV{{{_m.group(2)}}} dans du JavaScript sans ** : la valeur sera '
                     'echappee pour du HTML. Ecrire #ENV**{...}|json_encode')

# 23 bis. Le meme piege a la frontiere d'une inclusion : une adresse passee
#     en argument d'INCLURE via #ENV sans etoiles est echappee AVANT d'entrer
#     dans le gabarit inclus. Celui-ci a beau la traiter proprement, elle
#     arrive deja abimee. C'est un oeil humain qui a trouve celui-la.
for _f in sorted(glob.glob('squelettes/**/*.html', recursive=True) +
                 glob.glob(os.path.join(RACINE, '..', 'plugin-marly', '**', '*.html'), recursive=True)):
    _src = open(_f, encoding='utf-8').read()
    for _m in re.finditer(r'\{(_?url[a-z0-9_]*)=#ENV\{', _src):
        signaler(_f.replace(os.path.join(RACINE, '..'), '').lstrip('/'),
                 _src[:_m.start()].count(chr(10)) + 1,
                 f'{_m.group(1)} passe a une inclusion via #ENV sans ** : '
                 "l'adresse sera echappee avant d'entrer dans le gabarit inclus. "
                 'Ecrire #ENV**{...}')

# 24. Un bloc optionnel doit avoir sa balise pivot.
#     [ ... ] ne devient conditionnel que si une balise (#XXX) se trouve
#     DIRECTEMENT dedans. Si toutes les balises du bloc sont deja dans leurs
#     propres crochets, le bloc exterieur n'a pas de pivot : SPIP le prend
#     pour du texte et AFFICHE les crochets sur la page. Vu autour du bouton
#     << signaler >> d'une fiche. On ne controle que les blocs qui ouvrent du
#     HTML ([<...), pour ne pas confondre avec les crochets du JavaScript.
for _f in sorted(glob.glob('squelettes/**/*.html', recursive=True) +
                 glob.glob(os.path.join(RACINE, '..', 'plugin-marly', '**', '*.html'), recursive=True)):
    _src = open(_f, encoding='utf-8').read()
    _i = 0
    while True:
        _i = _src.find('[<', _i)
        if _i < 0:
            break
        _prof, _j, _pivot = 0, _i + 1, False
        while _j < len(_src):
            _c = _src[_j]
            if _c == '[':
                _prof += 1
            elif _c == ']':
                if _prof == 0:
                    break
                _prof -= 1
            elif _prof == 0 and _c == '(' and _src[_j:_j + 2] == '(#':
                _pivot = True
            _j += 1
        if _j < len(_src) and not _pivot:
            signaler(_f.replace(os.path.join(RACINE, '..'), '').lstrip('/'),
                     _src[:_i].count(chr(10)) + 1,
                     'bloc optionnel sans balise pivot : les crochets seront '
                     'affiches tels quels sur la page')
        _i = _j

# 25. Les filtres qui n'existent pas sous le nom qu'on croit.
#     SPIP accepte comme filtre toute fonction PHP : |urlencode marche parce
#     que urlencode() existe. |url_encode ne correspond a rien, et l'erreur
#     n'apparait qu'a l'execution, en tete de page publique. La liste ici
#     recense les graphies fautives deja rencontrees et leurs formes justes.
FILTRES_FAUTIFS = {
    'url_encode': 'urlencode',
    'html_entities': 'entites_html',
    'strip_tags': 'textebrut',
}
for _f in sorted(glob.glob('squelettes/**/*.html', recursive=True) +
                 glob.glob(os.path.join(RACINE, '..', 'plugin-marly', '**', '*.html'), recursive=True)):
    _src = open(_f, encoding='utf-8').read()
    for _nom, _juste in FILTRES_FAUTIFS.items():
        for _m in re.finditer(r'\|' + _nom + r'\b', _src):
            signaler(_f.replace(os.path.join(RACINE, '..'), '').lstrip('/'),
                     _src[:_m.start()].count(chr(10)) + 1,
                     f"filtre inexistant |{_nom} : ecrire |{_juste}")

# 26. Tout script du theme doit etre reference par un gabarit.
#     menu.js a vecu sans etre charge NULLE PART : chaque panneau retombait
#     sur son lien de secours, et l'oubli etait invisible. Un script orphelin
#     est soit un oubli de chargement, soit du code mort — les deux se
#     corrigent.
_gabarits = ''
for _f in glob.glob('squelettes/**/*.html', recursive=True):
    _gabarits += open(_f, encoding='utf-8').read()
for _js in sorted(glob.glob('squelettes/js/*.js')):
    _nom = os.path.basename(_js)
    if _nom not in _gabarits:
        signaler(_js, 1, f'script jamais reference par un gabarit : {_nom} ne se charge nulle part')

# 27. Une boucle avec alternative doit produire un texte.
#     L'alternative <//B_x> s'affiche quand la boucle n'a rien PRODUIT, pas
#     quand elle n'a rien trouve. Une boucle au corps vide n'affiche donc
#     JAMAIS sa partie normale et TOUJOURS son alternative — le message
#     << annuaire en cours de constitution >> est reste au milieu de cinq
#     fiches en ligne.
for _f in sorted(glob.glob('squelettes/**/*.html', recursive=True) +
                 glob.glob(os.path.join(RACINE, '..', 'plugin-marly', '**', '*.html'), recursive=True)):
    _src = open(_f, encoding='utf-8').read()
    for _m in re.finditer(r'<BOUCLE_([A-Za-z0-9_]+)\([^)]*\)[^>]*>(\s*)</BOUCLE_\1>', _src):
        if re.search(r'<//B_' + _m.group(1) + r'>', _src):
            signaler(_f.replace(os.path.join(RACINE, '..'), '').lstrip('/'),
                     _src[:_m.start()].count(chr(10)) + 1,
                     f'boucle {_m.group(1)} au corps vide avec alternative : '
                     "l'alternative s'affichera TOUJOURS. Mettre au moins un commentaire HTML dans le corps")

# 28. Un ecran d'edition qui ecrit en base doit prevenir le cache public.
#     Nos formulaires ecrivent par sql_updateq, sans passer par l'API des
#     objets qui signale d'habitude la modification au cache. Sans le
#     signal, la mairie corrige une fiche et le site public ne bouge pas —
#     jusqu'au deploiement suivant, qui vide tout et masque le defaut.
for _f in sorted(glob.glob(os.path.join(RACINE, '..', 'plugin-marly', 'formulaires', 'editer_*.php'))):
    _src = open(_f, encoding='utf-8').read()
    if ('sql_updateq' in _src or 'sql_insertq' in _src) and 'marly_invalider_cache' not in _src:
        signaler(os.path.relpath(_f, os.path.join(RACINE, '..')), 1,
                 'ecrit en base sans appeler marly_invalider_cache() : le site public '
                 'ressert les anciennes pages jusqu au prochain deploiement')

# 29. Un script en ligne de commande qui cree ou modifie des objets SPIP
#     doit d'abord se presenter en webmestre. Sans visiteur_session, SPIP
#     refuse EN SILENCE le passage en << publie >> (autoriser('publierdans')
#     echoue faute de connecte) : le premier import des comptes rendus a
#     cree 30 articles, tous restes en << prepa >>, invisibles.
for _f in sorted(glob.glob(os.path.join(RACINE, 'outils', '*.php'))):
    _src = open(_f, encoding='utf-8').read()
    if re.search(r'objet_inserer\(|objet_modifier\(|marly_rubrique_association\(', _src) \
            and 'visiteur_session' not in _src:
        signaler(os.path.relpath(_f, os.path.join(RACINE, '..')), 1,
                 "cree ou modifie des objets SPIP sans poser visiteur_session (webmestre) : "
                 "la publication sera refusee en silence, tout restera en << prepa >>")

if fautes:
    print('\n'.join(fautes))
    print(f'\n{len(fautes)} probleme(s).')
    sys.exit(1)
print('Squelettes verifies : rien a signaler.')
