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
#    Sauf ceux de SPIP : la connexion appartient au noyau, elle porte la
#    session, le cookie et le retour vers la page d'ou l'on venait. On
#    l'habille en CSS, on ne la reecrit pas — donc pas de fichier chez nous.
#    Cette liste ne se remplit qu'apres avoir constate l'appel, pas par
#    precaution : un nom ajoute d'avance rouvrirait la porte que la regle ferme.
FORMULAIRES_DE_SPIP = {'login', 'oubli', 'mot_de_passe'}
for f in sorted(glob.glob('squelettes/**/*.html', recursive=True)):
    s = open(f, encoding='utf-8').read()
    for m in re.finditer(r'#FORMULAIRE_([A-Z_]+)', s):
        nom = m.group(1).lower()
        if nom in FORMULAIRES_DE_SPIP:
            continue
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

# 30. Publier un article retimbre sa date : SPIP la remet a << maintenant >>
#     sauf date future (ecrire/action/editer_article.php, article_instituer).
#     Un script qui publie des articles historiques doit donc leur RENDRE
#     leur date apres coup, par sql_updateq. Le deuxieme import l'a paye :
#     21 comptes rendus de 2016-2020 dates du jour de l'import.
for _f in sorted(glob.glob(os.path.join(RACINE, 'outils', '*.php'))):
    _src = open(_f, encoding='utf-8').read()
    if re.search(r"objet_modifier\('article'", _src) and "'publie'" in _src \
            and not re.search(r"sql_updateq\('spip_articles',\s*array\('date'", _src):
        signaler(os.path.relpath(_f, os.path.join(RACINE, '..')), 1,
                 "publie des articles sans leur rendre leur date : SPIP retimbre la date "
                 "a la publication, les articles historiques seraient dates du jour")

# 31. Un script qui rapatrie des fichiers d'un autre site doit les ATTACHER
#     aux articles (ajouter_un_document), pas seulement les poser dans IMG/.
#     Les gabarits montrent les DOCUMENTS d'un article ; un PDF simplement
#     copie sur le disque reste invisible. Le premier import l'a montre :
#     30 comptes rendus en ligne, aucun PDF a telecharger.
for _f in sorted(glob.glob(os.path.join(RACINE, 'outils', '*.php'))):
    _src = open(_f, encoding='utf-8').read()
    if 'IMG/ancien-site' in _src and 'ajouter_un_document' not in _src:
        signaler(os.path.relpath(_f, os.path.join(RACINE, '..')), 1,
                 "rapatrie des fichiers sans les attacher comme documents SPIP : "
                 "les cartes Telecharger et la galerie resteront vides")

# 32. Un auteur cree par l'API nait en << 5poubelle >>, que les boucles
#     AUTEURS excluent : sa signature n'apparait jamais sur le site. Un
#     script qui cree des auteurs doit poser leur statut lui-meme (1comite,
#     sans login). Severine, premiere fournee : 30 articles signes d'une
#     fiche invisible.
for _f in sorted(glob.glob(os.path.join(RACINE, 'outils', '*.php'))):
    _src = open(_f, encoding='utf-8').read()
    if "objet_inserer('auteur')" in _src and "'1comite'" not in _src:
        signaler(os.path.relpath(_f, os.path.join(RACINE, '..')), 1,
                 "cree des auteurs sans poser leur statut : nes en 5poubelle, "
                 "leurs signatures resteront invisibles sur le site")

# 33. Une classe du theme reprise comme MODIFICATEUR : le piege silencieux.
#     Le plan du site a d'abord porte .plan-bloc.large ; or .large existe
#     deja (le conteneur pleine largeur, qui porte margin-inline:auto), et
#     ce margin auto a recentre le bloc en le retrecissant de 79px. Rien
#     n'etait casse a la lecture du code : c'est la mesure des positions
#     dans le navigateur qui l'a montre. La regle rend la collision visible
#     avant le rendu.
_css = os.path.join(RACINE, 'squelettes', 'css', 'theme.css')
if os.path.isfile(_css):
    _brut = open(_css, encoding='utf-8').read()
    # Les commentaires sont remplaces par AUTANT de sauts de ligne qu'ils en
    # contenaient : sans cela les numeros de ligne annonces seraient decales.
    _sans = re.sub(r'/\*.*?\*/', lambda _c: '\n' * _c.group(0).count('\n'), _brut, flags=re.S)
    _seuls = set()
    for _m in re.finditer(r'([^{}]+)\{', _sans):
        for _sel in _m.group(1).split(','):
            _s = _sel.strip()
            if re.fullmatch(r'\.[A-Za-z0-9_-]+', _s):
                _seuls.add(_s[1:])
    for _m in re.finditer(r'([^{}]+)\{', _sans):
        # Le motif happe aussi les blancs qui suivent la regle precedente :
        # on se cale sur le premier caractere reel du selecteur.
        _debut = _m.start() + len(_m.group(1)) - len(_m.group(1).lstrip())
        _ligne = _sans[:_debut].count(chr(10)) + 1
        for _sel in _m.group(1).split(','):
            for _c in re.finditer(r'\.([A-Za-z0-9_-]+)\.([A-Za-z0-9_-]+)', _sel):
                if _c.group(2) in _seuls:
                    signaler('squelettes/css/theme.css', _ligne,
                             f'.{_c.group(1)}.{_c.group(2)} reprend .{_c.group(2)}, '
                             f'qui est deja une classe du theme : ses proprietes '
                             f's appliqueront ici aussi, en silence. Choisir un autre nom')

# 34. Chercher du texte accentue dans du HTML aspire SANS decoder les
#     entites. L'ancien SPIP ecrit << Le 5 ao&ucirc;t 2021, par ... >> : le
#     motif ancre sur la ligne de signature ne matchait donc jamais pour
#     fevrier, aout et decembre, et l'import retombait sur la premiere date
#     du corps — la date de la reunion au lieu de celle de publication.
#     Trois mois sur douze, une quinzaine d'articles dates de travers, et
#     rien qui se voyait a la lecture du code.
_MOIS_ACCENTUES = ('février', 'août', 'décembre')
for _f in sorted(glob.glob(os.path.join(RACINE, 'outils', '*.php'))):
    _src = open(_f, encoding='utf-8').read()
    # Seuls les scripts qui ASPIRENT du HTML distant sont concernes : ailleurs,
    # un mois accentue n'est que du texte (la description d'une association
    # parlait d'aout, et se faisait signaler pour rien).
    if 'recuperer_url' not in _src:
        continue
    # Le controle porte sur la FONCTION, pas sur le fichier : un decodage fait
    # ailleurs (sur les titres) ne protege en rien la lecture des dates.
    _bornes = [_d.start() for _d in re.finditer(r'(?m)^function\s+\w+', _src)] + [len(_src)]
    for _i in range(len(_bornes) - 1):
        _corps = _src[_bornes[_i]:_bornes[_i + 1]]
        _accentue = any(_a in _m for _m in re.findall(r"'[^']*'|\"[^\"]*\"", _corps)
                        for _a in _MOIS_ACCENTUES)
        if _accentue and 'html_entity_decode' not in _corps:
            _nom = re.match(r'function\s+(\w+)', _corps).group(1)
            signaler(os.path.relpath(_f, os.path.join(RACINE, '..')),
                     _src[:_bornes[_i]].count(chr(10)) + 1,
                     f"{_nom}() cherche des mois accentues dans du HTML aspire sans "
                     "appeler html_entity_decode : les entites (ao&ucirc;t) ne matcheront jamais")

# 35. Chercher une ligne de signature dans du HTML aspire SANS ecarter
#     d'abord le <title> et le <h1>. L'ancien SPIP ecrit sa balise titre
#     << TITRE, par AUTEUR - Site du village >> : quand le titre de
#     l'article finit par une date, cette balise prend la forme EXACTE
#     d'une signature, et comme elle vient ligne 4 le motif ancre la mord
#     bien avant la vraie signature du corps. Mesure : article103 de
#     l'ancien site, << FETE DE L'ATTELAGE LE 24 SEPTEMBRE 2017 >>, publie
#     le 7 septembre 2017, importe au 24. Le h1 est ecarte pour la meme
#     raison : il porte le titre, donc parfois une date, que le repli
#     attraperait.
for _f in sorted(glob.glob(os.path.join(RACINE, 'outils', '*.php'))):
    _src = open(_f, encoding='utf-8').read()
    if 'recuperer_url' not in _src:
        continue
    # Meme portee que la regle 34 : la FONCTION, pas le fichier. Un retrait
    # du titre fait ailleurs (a l'extraction du corps) ne protege pas la
    # lecture de la date. Et meme reperage : la fonction qui nomme des mois
    # accentues est celle qui lit les dates.
    _bornes = [_d.start() for _d in re.finditer(r'(?m)^function\s+\w+', _src)] + [len(_src)]
    for _i in range(len(_bornes) - 1):
        _corps = _src[_bornes[_i]:_bornes[_i + 1]]
        if not any(_a in _m for _m in re.findall(r"'[^']*'|\"[^\"]*\"", _corps)
                   for _a in _MOIS_ACCENTUES):
            continue
        _manque = [_b for _b in ('<title', '<h1') if _b not in _corps]
        if _manque:
            _nom = re.match(r'function\s+(\w+)', _corps).group(1)
            signaler(os.path.relpath(_f, os.path.join(RACINE, '..')),
                     _src[:_bornes[_i]].count(chr(10)) + 1,
                     f"{_nom}() lit une date dans du HTML aspire sans ecarter "
                     f"{' ni '.join(_manque)}> : la balise titre de l'ancien SPIP "
                     "(<< TITRE, par AUTEUR >>) se fait passer pour une signature")

# 36. Une DATE posee a la main qui ecrase la date extraite. Le script
#     d'import garde une table de rattrapage pour les pages qui ne signent
#     pas leur publication. Appliquee sans condition, elle survit en
#     silence aux corrections de l'extracteur : la vraie date redevient
#     lisible, la valeur en dur continue de la masquer, et la comparaison
#     avant/apres ne voit rien puisque l'essai applique la meme table que
#     l'import. Mesure : article91, fige au 1er decembre 2016 d'apres sa
#     place dans le plan, alors que sa page signe << Le 15 d&eacute;cembre
#     2016, par ... >> — invisible tant que les entites n'etaient pas
#     decodees, invisible encore apres, a cause de la valeur en dur.
#
#     La regle ne vise QUE les dates, et c'est voulu. Une date posee a la
#     main comble un vide : le jour ou l'extracteur sait lire, elle n'a
#     plus lieu d'etre. Un retitrage ou un reroutage, lui, corrige une
#     donnee bien presente et parfaitement lue — << Nouvel article >> est
#     vraiment le titre de la page. Ceux-la doivent ecraser.
for _f in sorted(glob.glob(os.path.join(RACINE, 'outils', '*.php'))):
    _src = open(_f, encoding='utf-8').read()
    if 'recuperer_url' not in _src:
        continue
    for _c in re.finditer(r'if\s*\(isset\(\$(\w+)\[\$id\]\)\)\s*\{\s*'
                          r'\$date\s*=\s*\$\1\[\$id\]\s*;', _src):
        signaler(os.path.relpath(_f, os.path.join(RACINE, '..')),
                 _src[:_c.start()].count(chr(10)) + 1,
                 f"${_c.group(1)} ecrase la date extraite sans condition : une date "
                 "posee a la main ne doit servir qu'a defaut, sinon elle masquera "
                 "pour toujours celle que l'extracteur finira par savoir lire")

# 37. Un formulaire d'edition qui, une fois enregistre, renvoie vers la LISTE
#     au lieu de la fiche. Deux choses se perdent en chemin, et les deux
#     comptent au moment precis ou elles disparaissent :
#       - le bloc photographie n'apparait qu'APRES le premier enregistrement,
#         SPIP ayant besoin d'un identifiant pour rattacher une image ; renvoye
#         a la liste, on ne le voit jamais sur une fiche qu'on vient de creer ;
#       - le compte rendu de localisation (<< l'adresse a ete localisee >>,
#         << la carte sera centree sur le village >>) s'affiche sur la fiche.
#     Et c'est desoriente : on enregistre une fiche, on se retrouve ailleurs.
#     Le retour a la liste reste a un clic, dans le fil d'Ariane.
_FORMS37 = os.path.join(RACINE, '..', 'plugin-marly', 'formulaires')
for _f in sorted(glob.glob(os.path.join(_FORMS37, 'editer_*.php'))):
    _src = open(_f, encoding='utf-8').read()
    for _c in re.finditer(r"'redirect'\s*=>\s*generer_url_ecrire\(\s*'(\w+)'\s*\)", _src):
        signaler(os.path.relpath(_f, os.path.join(RACINE, '..')),
                 _src[:_c.start()].count(chr(10)) + 1,
                 f"l'enregistrement renvoie vers la liste ({_c.group(1)}) et non vers la "
                 "fiche : le bloc photographie et le compte rendu de localisation "
                 "s'affichent sur la fiche, et ne seront jamais vus")

# 38. Un formulaire d'edition dont l'adresse d'ENVOI ne reporte pas
#     l'identifiant de la fiche. Tant que la saisie est valide, rien ne se
#     voit : SPIP redirige et l'adresse d'envoi n'est jamais affichee. Le
#     jour ou la validation REFUSE, SPIP reaffiche le formulaire a cette
#     adresse-la — sans identifiant, il reaffiche une fiche NEUVE et VIDE au
#     lieu de la fiche en cours avec son message d'erreur.
#
#     Effet sur la mairie : la saisie parait perdue, la fiche parait
#     effacee, et la raison du refus n'est jamais lue. Mesure dans le
#     journal HTTPS : << POST /ecrire/?exec=commerce 200 >>, referer
#     << ?exec=commerce&id_commerce=10 >>. L'identifiant tombe en route.
for _f in sorted(glob.glob(os.path.join(RACINE, '..', 'plugin-marly', 'formulaires', 'editer_*.html'))):
    _src = open(_f, encoding='utf-8').read()
    for _c in re.finditer(r'<form[^>]*action="#ENV\{action\}"', _src):
        signaler(os.path.relpath(_f, os.path.join(RACINE, '..')),
                 _src[:_c.start()].count(chr(10)) + 1,
                 "l'adresse d'envoi ne reporte pas l'identifiant de la fiche : "
                 "un refus de validation reaffichera une fiche neuve et vide, "
                 "et le message d'erreur ne sera jamais lu")

# 39. #FORMULAIRE_ILLUSTRER_DOCUMENT appele avec un OBJET au lieu d'un
#     identifiant de document. Sa signature est
#     formulaires_illustrer_document_charger_dist($id_document) : elle sert a
#     changer la vignette d'un fichier deja depose, pas a poser une image sur
#     une fiche. Appelee avec {commerce,2} elle cherche le document numero 2,
#     ne trouve rien, et rend editable => false. Le formulaire ne s'affiche
#     donc pas, EN SILENCE : l'ecran montre le titre << Photographie >> et son
#     explication, et aucun bouton.
#
#     Le bloc a vecu des mois ainsi sur les associations, les elus et les
#     evenements sans que personne s'en apercoive. La bonne balise est
#     #FORMULAIRE_EDITER_LOGO{objet,id,'',#ENV**}, celle que l'espace prive de
#     SPIP emploie pour les articles et les rubriques.
for _f in sorted(glob.glob(os.path.join(RACINE, '..', 'plugin-marly', 'prive',
                                        'squelettes', '**', '*.html'), recursive=True)):
    _src = open(_f, encoding='utf-8').read()
    for _c in re.finditer(r'#FORMULAIRE_ILLUSTRER_DOCUMENT\{\s*([A-Za-z_]\w*)', _src):
        signaler(os.path.relpath(_f, os.path.join(RACINE, '..')),
                 _src[:_c.start()].count(chr(10)) + 1,
                 f"#FORMULAIRE_ILLUSTRER_DOCUMENT recoit l'objet << {_c.group(1)} >> alors "
                 "qu'il attend un id_document : il rendra editable => false et le bloc "
                 "restera vide sans un mot. Employer #FORMULAIRE_EDITER_LOGO{objet,id,'',#ENV**}")

# 40. plugin_installes_meta() appele avant que inc/texte soit charge.
#     Cette fonction compose son compte rendu avec typo(), qui vit dans
#     inc/texte : chargee apres, elle arrive trop tard et l'appel meurt sur
#     << Call to undefined function typo() >>.
#
#     Le piege est qu'elle n'appelle typo() que lorsqu'elle a quelque chose a
#     RACONTER. Tant qu'aucun plugin n'a de retard, elle se tait et rien ne
#     plante : le defaut ne se montrait qu'aux montees de version, donc
#     exactement quand le deploiement compte, et il faisait croire que la
#     mise a jour avait echoue alors qu'elle venait de reussir.
for _f in sorted(glob.glob(os.path.join(RACINE, 'outils', '*.php'))):
    _src = open(_f, encoding='utf-8').read()
    # L'APPEL, et non une mention : une ligne qui n'est que cet appel suivi
    # d'un point-virgule. Le nom figure aussi dans un commentaire d'en-tete et
    # dans une garde function_exists, qui ne declenchent rien.
    _c = re.search(r'(?m)^[ \t]*plugin_installes_meta\(\)[ \t]*;', _src)
    if not _c:
        continue
    if "include_spip('inc/texte')" not in _src[:_c.start()]:
        signaler(os.path.relpath(_f, os.path.join(RACINE, '..')),
                 _src[:_c.start()].count(chr(10)) + 1,
                 "plugin_installes_meta() est appele avant include_spip('inc/texte') : "
                 "elle compose son compte rendu avec typo() et mourra, mais seulement "
                 "les fois ou un plugin a du retard, c'est-a-dire aux montees de version")

# 41. Un objet qu'on peut creer et modifier, mais pas supprimer. Les elus,
#     les lieux, les salles, les evenements et les raccourcis etaient dans ce
#     cas : une fiche saisie par erreur restait la pour toujours, et rien a
#     l'ecran ne disait pourquoi.
#
#     L'invariant : tout ecran d'edition (#FORMULAIRE_EDITER_X) doit avoir son
#     action/supprimer_x.php, ET un ecran de liste qui l'appelle. Une action
#     de suppression qu'aucun bouton n'atteint ne sert a personne.
#
#     UNE exception, et elle est voulue : la lettre d'information. Une lettre
#     partie est partie, chez tous ses destinataires. Pouvoir l'effacer du
#     site donnerait l'illusion de la rattraper.
_SANS_SUPPRESSION = {
    'lettre': "une lettre envoyee est partie chez ses destinataires ; "
              "l'effacer du site donnerait l'illusion de la rattraper",
}
_PRIVE = os.path.join(RACINE, '..', 'plugin-marly', 'prive', 'squelettes', 'contenu')
_ACTIONS = os.path.join(RACINE, '..', 'plugin-marly', 'action')
if os.path.isdir(_PRIVE):
    _tous_ecrans = ''
    for _f in glob.glob(os.path.join(_PRIVE, '*.html')):
        _tous_ecrans += open(_f, encoding='utf-8').read()
    for _f in sorted(glob.glob(os.path.join(_PRIVE, '*.html'))):
        _src = open(_f, encoding='utf-8').read()
        for _c in re.finditer(r'#FORMULAIRE_EDITER_(\w+)\{', _src):
            _objet = _c.group(1).lower()
            # Nos objets seulement : #FORMULAIRE_EDITER_LOGO est celui de SPIP,
            # et un logo ne se supprime pas par une action a nous.
            if not os.path.isfile(os.path.join(RACINE, '..', 'plugin-marly',
                                               'formulaires', 'editer_%s.php' % _objet)):
                continue
            if _objet in _SANS_SUPPRESSION:
                continue
            _quoi = None
            if not os.path.isfile(os.path.join(_ACTIONS, 'supprimer_%s.php' % _objet)):
                _quoi = "action/supprimer_%s.php n'existe pas" % _objet
            elif ('supprimer_%s,' % _objet) not in _tous_ecrans:
                _quoi = ("action/supprimer_%s.php existe mais aucun ecran ne l'appelle : "
                         "une suppression qu'aucun bouton n'atteint ne sert a personne" % _objet)
            if _quoi:
                signaler(os.path.relpath(_f, os.path.join(RACINE, '..')),
                         _src[:_c.start()].count(chr(10)) + 1,
                         f"on peut creer et modifier un {_objet}, pas le supprimer : {_quoi}")

# 42. Une lettre d'un autre alphabet qui imite une lettre latine. Un << e >>
#     cyrillique (U+0435) est indiscernable a l'oeil d'un << e >> latin, et
#     pourtant ce n'est pas le meme caractere : un mot qui en contient un
#     echappe a toute recherche, casse un identifiant, ou fait echouer une
#     comparaison de chaine sans que rien ne l'explique.
#
#     C'est arrive dans un commentaire — la, sans consequence. Le meme
#     accident dans un nom de classe CSS, une cle de langue ou un motif
#     coute une soiree a comprendre, parce que le code A L'AIR juste.
_ALPHABETS_ETRANGERS = re.compile('[\u0400-\u04FF\u0370-\u03FF]')
for _rep in ('theme-marly', 'plugin-marly'):
    _base = os.path.join(RACINE, '..', _rep)
    if not os.path.isdir(_base):
        continue
    for _racine, _, _fichiers in os.walk(_base):
        for _nom in _fichiers:
            if not _nom.endswith(('.html', '.php', '.css', '.js', '.py', '.xml')):
                continue
            _f = os.path.join(_racine, _nom)
            try:
                _src = open(_f, encoding='utf-8').read()
            except (UnicodeDecodeError, OSError):
                continue
            for _c in _ALPHABETS_ETRANGERS.finditer(_src):
                signaler(os.path.relpath(_f, os.path.join(RACINE, '..')),
                         _src[:_c.start()].count(chr(10)) + 1,
                         f"caractere {_c.group(0)!r} (U+{ord(_c.group(0)):04X}) : une lettre "
                         "d'un autre alphabet qui imite une lettre latine. Indiscernable a "
                         "l'oeil, mais ce n'est pas le meme caractere")

# 43. Un bloc pose juste sous la rangee de retour et d'outils, sans marge
#     haute. Mesure : sur la fiche d'un elu, l'ecart entre le bas des deux
#     boutons ronds et le haut du portrait valait ZERO. Les boutons venaient
#     se coller a l'image comme s'ils lui appartenaient — on croyait a une
#     barre d'outils de la photographie, alors qu'ils agissent sur la page.
#
#     La regle porte sur le CSS : un selecteur qui suit .fil-rangee dans un
#     gabarit de fiche doit declarer une marge haute non nulle. On la
#     controle sur les blocs nommes qui ouvrent une fiche.
_OUVREURS = ('.elu-tete', '.com-bandeau', '.asso-photo-fiche')
_CSS43 = os.path.join(RACINE, 'squelettes', 'css', 'theme.css')
if os.path.isfile(_CSS43):
    _src = open(_CSS43, encoding='utf-8').read()
    for _nom in _OUVREURS:
        _m = re.search(re.escape(_nom) + r'\{([^}]*)\}', _src)
        if not _m:
            continue
        _marge = re.search(r'margin\s*:\s*([^;]+);', _m.group(1))
        _haut = _marge.group(1).split()[0].strip() if _marge else '0'
        if _haut in ('0', '0px'):
            signaler('squelettes/css/theme.css',
                     _src[:_m.start()].count(chr(10)) + 1,
                     f"{_nom} ouvre une fiche sans marge haute : il viendra se coller "
                     "a la rangee de retour et d'outils qui le precede, et les boutons "
                     "ronds paraitront appartenir a l'image")

# 44. Un format de date passe a affdate. SPIP traduit les mois et les jours
#     lui-meme, mais seulement quand on le laisse faire : des qu'on lui donne
#     un format, les lettres partent chez la fonction date() de PHP, qui
#     repond dans la langue du serveur. Mesure : la fiche du maire affichait
#     << Fiche mise a jour le 24 August 2026 >> sur un site de commune
#     francaise, parce que le squelette demandait affdate{'j F Y'}.
#
#     Les lettres en cause sont celles qui produisent un MOT et non un
#     nombre : D et l (le jour de la semaine), F et M (le mois), S (le
#     suffixe ordinal, anglais par construction). Les formats purement
#     chiffres, comme 'H\\hi' pour une heure, ne risquent rien.
#
#     A la place : |affdate tout nu rend << 24 aout 2026 >>, |affdate_jourcourt
#     rend << 24 aout >>, |nom_jour rend << lundi >>, |nom_mois rend << aout >>.
_LETTRES_TRADUITES = {'D': 'le jour de la semaine abrege',
                      'l': 'le jour de la semaine',
                      'F': 'le nom du mois',
                      'M': 'le nom du mois abrege',
                      'S': "le suffixe ordinal (st, nd, th : anglais par construction)"}
for _rep in ('theme-marly', 'plugin-marly'):
    _base = os.path.join(RACINE, '..', _rep)
    if not os.path.isdir(_base):
        continue
    for _racine, _, _fichiers in os.walk(_base):
        for _nom in _fichiers:
            if not _nom.endswith('.html'):
                continue
            _f = os.path.join(_racine, _nom)
            try:
                _src = open(_f, encoding='utf-8').read()
            except (UnicodeDecodeError, OSError):
                continue
            for _c in re.finditer(r"affdate\{'([^']*)'\}", _src):
                _format = _c.group(1)
                _i = 0
                _vues = []
                while _i < len(_format):
                    if _format[_i] == chr(92):      # une lettre echappee est litterale
                        _i += 2
                        continue
                    if _format[_i] in _LETTRES_TRADUITES and _format[_i] not in _vues:
                        _vues.append(_format[_i])
                    _i += 1
                if not _vues:
                    continue
                _quoi = ', '.join("%s (%s)" % (_l, _LETTRES_TRADUITES[_l]) for _l in _vues)
                signaler(os.path.relpath(_f, os.path.join(RACINE, '..')),
                         _src[:_c.start()].count(chr(10)) + 1,
                         "affdate{'%s'} : %s. SPIP ne traduit pas un format, il le "
                         "passe a date() de PHP, qui repond en anglais. Utiliser "
                         "|affdate seul, |affdate_jourcourt, |nom_jour ou |nom_mois"
                         % (_format, _quoi))

# 45. Publier un article sans verifier qu'il l'est vraiment. SPIP accepte la
#     demande, ne signale rien, et laisse parfois l'article en << prepa >> —
#     invisible du public, alors que le script annonce avoir publie.
#
#     Deux mesures, a huit mois d'intervalle : six articles d'associations
#     restes en prepa au premier import, puis les deux pages legales le
#     25 aout 2026, creees et annoncees publiees, trouvees en prepa dans la
#     base. Le script d'import porte le garde-fou depuis le premier incident ;
#     le second script ne l'avait pas, faute d'avoir lu le premier.
#
#     La regle : tout script qui publie un article doit RELIRE le statut en
#     base ensuite, et le forcer si besoin. C'est trois lignes, et c'est la
#     seule facon de savoir.
#
#     Les commentaires sont neutralises avant lecture, sinon la phrase qui
#     explique l'erreur declencherait la regle qui l'interdit.
def _sans_commentaires_php(_texte):
    """Vide les commentaires en gardant les sauts de ligne, pour que les
    numeros de ligne signales restent ceux du fichier."""
    def _blanchir(_m):
        return re.sub(r'[^\n]', ' ', _m.group(0))
    _texte = re.sub(r'/\*.*?\*/', _blanchir, _texte, flags=re.S)
    return re.sub(r'//[^\n]*', _blanchir, _texte)

for _f in sorted(glob.glob(os.path.join(RACINE, 'outils', '*.php'))):
    _src = _sans_commentaires_php(open(_f, encoding='utf-8').read())
    # Un script qui demande la publication d'un article, par l'une ou l'autre
    # des deux fonctions de SPIP.
    _publie = re.search(r"objet_(?:modifier|instituer)\(\s*'article'.{0,400}?'statut'\s*=>\s*'publie'",
                        _src, re.S)
    if not _publie:
        continue
    # A-t-il relu le statut en base ensuite ?
    if not re.search(r"sql_getfetsel\(\s*'statut'\s*,\s*'spip_articles'", _src):
        signaler(os.path.relpath(_f, os.path.join(RACINE, '..')),
                 _src[:_publie.start()].count(chr(10)) + 1,
                 "publie un article sans relire son statut en base ensuite : SPIP "
                 "laisse parfois l'article en prepa sans signaler d'erreur, et le "
                 "script annonce une publication qui n'a pas eu lieu")

# 46. Un nom de fichier affiche dans une carte, sans autorisation de coupure.
#     Un navigateur ne coupe une ligne qu'aux espaces et aux traits d'union.
#     Or un nom de fichier n'en a ni l'un ni l'autre :
#     proces_verbal_conseil_municipal_du_09_10_23.pdf est UN SEUL MOT. Il
#     deborde donc de sa colonne et passe sous le bouton de telechargement,
#     mesure sur la fiche d'un compte rendu.
#
#     Les blocs qui portent un nom de fichier doivent declarer
#     overflow-wrap:anywhere. La regle porte sur eux seuls : ailleurs, couper
#     un mot n'importe ou est un defaut, pas une qualite.
_PORTE_NOM_FICHIER = ('.doc-texte b',)
_CSS46 = os.path.join(RACINE, 'squelettes', 'css', 'theme.css')
if os.path.isfile(_CSS46):
    _src = open(_CSS46, encoding='utf-8').read()
    for _sel in _PORTE_NOM_FICHIER:
        _m = re.search(re.escape(_sel) + r'\{([^}]*)\}', _src)
        if not _m:
            continue
        if 'overflow-wrap' not in _m.group(1) and 'word-break' not in _m.group(1):
            signaler('squelettes/css/theme.css',
                     _src[:_m.start()].count(chr(10)) + 1,
                     f"{_sel} affiche un nom de fichier sans autoriser la coupure : "
                     "un nom sans espace ni trait d'union est un seul mot pour le "
                     "navigateur, il debordera sous le bouton")

# 47. Un formulaire en method="get" dont l'action porte une chaine de requete.
#     Le navigateur la JETTE : avec method="get", la chaine de requete de
#     l'action est remplacee par les champs du formulaire. action="#URL_PAGE
#     {recherche}" rend spip.php?page=recherche, la validation partait donc
#     vers spip.php?recherche=..., SPIP ne voyait plus quelle page servir et
#     rendait l'accueil. Le visiteur tapait, validait, et restait sur place —
#     sans message, sans erreur, sans rien a chercher dans un journal.
#
#     Mesure du 26 aout 2026 : quatre formulaires de recherche et le filtre du
#     catalogue etaient dans ce cas. La forme juste etait pourtant deja dans
#     le depot, dans la bande d'acces rapides : action vers spip.php, et la
#     page en champ cache.
for _f in sorted(glob.glob(os.path.join(RACINE, 'squelettes', '**', '*.html'), recursive=True)):
    _src = open(_f, encoding='utf-8').read()
    for _c in re.finditer(r'<form[^>]*method="get"[^>]*>', _src, re.I):
        _balise = _c.group(0)
        _action = re.search(r'action="([^"]*)"', _balise)
        if not _action:
            continue
        _url = _action.group(1)
        if '#URL_PAGE{' not in _url and '?' not in _url:
            continue
        # La forme juste : l'action ne porte pas la page, un champ cache la porte.
        _suite = _src[_c.end():_c.end() + 400]
        if re.search(r'name="page"', _suite):
            continue
        signaler(os.path.relpath(_f, os.path.join(RACINE, '..')),
                 _src[:_c.start()].count(chr(10)) + 1,
                 "formulaire en method=get dont l'action porte la page : le navigateur "
                 "remplace la chaine de requete de l'action par les champs du "
                 "formulaire, la page demandee est perdue et SPIP rend l'accueil. "
                 "Viser spip.php et poser la page en champ cache")

# 48. Des crochets dans les arguments d'un INCLURE. SPIP lit ces arguments
#     avec une grammaire simple : {cle=valeur}, sans calcul. Un
#     {titre=[(#VAL{marly:x}|_T)]} ne provoque pas d'erreur, il empeche
#     seulement la balise d'etre reconnue — et la ligne entiere s'affiche
#     telle quelle, en clair, au milieu de la page.
#
#     Mesure du 26 aout 2026 : << fond=inc/bandeau-page}{titre=Recherche}> >>
#     s'affichait en haut des resultats de recherche, et sur les quatre autres
#     pages de section batties sur le meme include.
#
#     La forme juste : passer la CLE de langue, et traduire dans l'include.
for _f in sorted(glob.glob(os.path.join(RACINE, 'squelettes', '**', '*.html'), recursive=True)):
    _src = open(_f, encoding='utf-8').read()
    for _c in re.finditer(r'<INCLURE\{[^>]*\}>', _src, re.S):
        if '[(' in _c.group(0):
            signaler(os.path.relpath(_f, os.path.join(RACINE, '..')),
                     _src[:_c.start()].count(chr(10)) + 1,
                     "crochets dans les arguments d'un INCLURE : SPIP ne reconnaît "
                     "plus la balise et affiche la ligne en clair au milieu de la "
                     "page. Passer une valeur simple, et calculer dans l'include")

# 49. Du CSS qui vise un balisage que SPIP ne produit pas.
#     Les formulaires de connexion et de mot de passe appartiennent a SPIP :
#     on les habille, on ne les reecrit pas. Une feuille qui vise une classe
#     inexistante ne provoque aucune erreur — elle ne s'applique simplement
#     jamais, et le formulaire s'affiche nu au milieu d'une page soignee.
#     Rien dans un journal, rien a chercher.
#
#     Mesure du 26 aout 2026, sur le site : les champs sont des <div class=
#     "editer editer_login"> dans un div.editer-groupe, pas des <li>, la
#     classe n'est pas editer_var_login, et sur l'oubli c'est saisie_oubli et
#     non editer_oubli. La premiere version de la feuille visait les deux
#     premieres inventions.
#
#     La regle lit la region CONNEXION de la feuille et n'y tolere que deux
#     sortes de classes : les notres, prefixees connexion-, et celles du
#     releve ci-dessous. Toute autre est signalee. Ajouter une classe a nous
#     ne demande donc rien, inventer une classe de SPIP est arrete.
_CLASSES_SPIP_RELEVEES = {
    # relevees sur formulaire_login ET formulaire_oubli : la convention
    # commune aux formulaires SPIP, vue deux fois plutot que supposee.
    'formulaire_spip', 'form-hidden', 'editer-groupe', 'editer', 'obligatoire',
    'boutons', 'btn', 'submit', 'text',
    # propres au formulaire de connexion
    'formulaire_login', 'editer_login', 'editer_password', 'editer_session',
    'etoile', 'details', 'choix', 'checkbox', 'password', 'nofx',
    # propres au formulaire d'oubli
    'formulaire_oubli', 'pass', 'saisie_oubli', 'email',
    # convention d'erreur, deja verifiee sur les formulaires de la lettre
    # d'information et du signalement
    'erreur', 'erreur_message',
    # les notres qui ne portent pas le prefixe
    'titre-bloc', 'fiche-liens', 'bandeau-carton', 'page-simple', 'page-login',
}
_CSS49 = os.path.join(RACINE, 'squelettes', 'css', 'theme.css')
if os.path.exists(_CSS49):
    _src = open(_CSS49, encoding='utf-8').read()
    _debut = _src.find('   CONNEXION ET MOT DE PASSE')
    if _debut < 0:
        signaler('squelettes/css/theme.css', 1,
                 "la region CONNEXION a disparu de la feuille : la regle 49 ne "
                 "garde plus rien, la retirer ou corriger son reperage")
    else:
        # La region s'arrete a la banniere suivante, pas a la fin du fichier.
        # Ecrite sans cette borne, la regle avalait tout ce qu'on ajoutait
        # apres elle : le bloc CONTACT, ecrit le meme jour, a declenche
        # quarante-trois fausses alertes d'un coup.
        _fin = _src.find('/* ====', _debut)
        _region = _src[_debut:_fin] if _fin > 0 else _src[_debut:]
        _sans = re.sub(r'/\*.*?\*/', '', _region, flags=re.S)
        for _m in re.finditer(r'([^{}]+)\{', _sans):
            _sel = _m.group(1).strip()
            if not _sel or _sel.startswith('@') or ':' in _sel.split('.')[0][:1]:
                pass
            for _cl in re.findall(r'\.([A-Za-z_][-\w]*)', _sel):
                if _cl.startswith('connexion-') or _cl in _CLASSES_SPIP_RELEVEES:
                    continue
                _pos = _src.find(_sel)
                signaler('squelettes/css/theme.css',
                         (_src[:_pos].count(chr(10)) + 1) if _pos >= 0 else 0,
                         f"le selecteur << {_sel} >> vise la classe .{_cl}, absente "
                         "du releve du balisage de SPIP : la regle ne s'appliquera "
                         "jamais et le formulaire restera nu. Relever le balisage "
                         "reel avant d'ecrire le style")

# 50. Le piege a robots du formulaire de mot de passe oublie.
#     SPIP pose un champ nobot masque par un style EN LIGNE, et rejette tout
#     envoi qui le trouve rempli. Le style en ligne l'emporte sur la feuille,
#     le piege tient donc tout seul — jusqu'au jour ou quelqu'un ecrit une
#     regle qui le rouvre, avec un !important ou un selecteur trop large.
#     Alors les navigateurs le remplissent par autocompletion, SPIP refuse
#     toutes les demandes, et rien ne le dit : le formulaire a l'air de
#     marcher, il ne repond simplement jamais.
#
#     Aucune raison legitime de styler ce champ. Toute mention est arretee.
for _f in sorted(glob.glob(os.path.join(RACINE, 'squelettes', 'css', '*.css'))):
    _src = open(_f, encoding='utf-8').read()
    _sans = re.sub(r'/\*.*?\*/', '', _src, flags=re.S)
    for _c in re.finditer(r'nobot', _sans):
        signaler(os.path.relpath(_f, os.path.join(RACINE, '..')),
                 _sans[:_c.start()].count(chr(10)) + 1,
                 "le champ nobot est le piege a robots de SPIP, masque par un "
                 "style en ligne : le styler risque de le rendre visible, les "
                 "navigateurs le rempliraient et SPIP rejetterait alors toutes "
                 "les demandes de mot de passe, en silence")

# 51. Un formulaire de connexion sans le cas << deja connecte >>.
#     FORMULAIRE_LOGIN ne rend RIEN quand une session existe : c'est le
#     comportement normal de SPIP, et il laisse notre carte creme entierement
#     vide. Un visiteur qui arrive la voit une page cassee, sans message et
#     sans rien a cliquer.
#
#     Constate le 26 aout 2026 : la page de connexion venait d'etre deployee,
#     le formulaire etait bien rendu cote serveur (verifie au curl), et il
#     avait disparu dans le navigateur — parce que la session y existait.
#     Rien n'etait casse, et tout en avait l'air.
#
#     La forme juste : encadrer l'appel par un test de SESSION, et dire
#     quelque chose dans l'autre cas.
for _f in sorted(glob.glob(os.path.join(RACINE, 'squelettes', '**', '*.html'), recursive=True)):
    _src = open(_f, encoding='utf-8').read()
    _sans_rem = re.sub(r'\[\(#REM\).*?\]', '', _src, flags=re.S)
    if '#FORMULAIRE_LOGIN' not in _sans_rem:
        continue
    if '#SESSION{id_auteur}' in _sans_rem:
        continue
    signaler(os.path.relpath(_f, os.path.join(RACINE, '..')),
             _src[:_src.index('#FORMULAIRE_LOGIN')].count(chr(10)) + 1,
             "formulaire de connexion appele sans traiter le cas deja connecte : "
             "SPIP ne rend rien quand une session existe, la page reste vide et "
             "ressemble a une panne. Encadrer par un test #SESSION{id_auteur}")

# 52. Une deconnexion posee sur le site public avec la mauvaise destination.
#     ecrire/action/logout.php ne renvoie vers l'accueil du site public que si
#     le parametre vaut public ET qu'aucune url n'est fournie. Avec prive — la
#     valeur que SPIP emploie pour lui-meme, et donc celle qu'on recopie sans
#     y penser — le visiteur ressort dans l'espace prive, c'est-a-dire sur la
#     page de connexion qu'il vient de quitter.
#
#     Personne ne verrait l'erreur en relisant le lien : les deux valeurs sont
#     plausibles, et la faute ne se voit qu'apres avoir clique.
for _f in sorted(glob.glob(os.path.join(RACINE, 'squelettes', '**', '*.html'), recursive=True)):
    _src = open(_f, encoding='utf-8').read()
    for _c in re.finditer(r'action=logout[^"\'<>]*', _src):
        if 'logout=public' in _c.group(0):
            continue
        signaler(os.path.relpath(_f, os.path.join(RACINE, '..')),
                 _src[:_c.start()].count(chr(10)) + 1,
                 "deconnexion sur le site public sans logout=public : SPIP "
                 "renverrait le visiteur dans l'espace prive, donc sur la page "
                 "de connexion qu'il vient de quitter")

# 53. Le nom du site affiche sans trim.
#     Il est saisi a la main dans SPIP, et il l'a ete avec une espace avant et
#     une apres. Le titre sortait donc << _Marly-Gomont_ — Site officiel >>,
#     une espace en tete et deux avant le tiret, dans le titre de CHAQUE page,
#     donc dans chaque resultat Google et dans chaque partage sur les reseaux.
#
#     Mesure du 26 aout 2026 : <title> Marly-Gomont  — Site officiel de la
#     commune</title>. Corriger le champ dans l'espace prive repare le jour
#     meme et rien de plus : il sera ressaisi un jour, par quelqu'un d'autre,
#     et personne ne reverra jamais l'espace en trop.
for _f in sorted(glob.glob(os.path.join(RACINE, 'squelettes', '**', '*.html'), recursive=True)):
    _src = open(_f, encoding='utf-8').read()
    # Les commentaires sont neutralises SANS perdre les sauts de ligne : les
    # retirer purement decalait le compte et la faute etait signalee vingt
    # lignes trop haut. Un numero faux fait chercher au mauvais endroit, ce
    # qui coute plus cher que pas de numero du tout.
    _sans_rem = re.sub(r'\[\(#REM\).*?\]',
                       lambda _m: chr(10) * _m.group(0).count(chr(10)), _src, flags=re.S)
    for _c in re.finditer(r'#NOM_SITE_SPIP((\|\w+(\{[^}]*\})?)*)', _sans_rem):
        if 'trim' in _c.group(1):
            continue
        # Employe comme argument d'un filtre — |sinon{#NOM_SITE_SPIP} — le trim
        # se pose en bout de chaine, sur le resultat.
        _suite = _sans_rem[_c.end():_c.end() + 60]
        if _suite.startswith('}') and 'trim' in _suite[:_suite.find(')') + 1 or 60]:
            continue
        signaler(os.path.relpath(_f, os.path.join(RACINE, '..')),
                 _sans_rem[:_c.start()].count(chr(10)) + 1,
                 "nom du site affiche sans |trim : il est saisi a la main et "
                 "l'a ete avec des espaces autour. Elles se voient dans le titre "
                 "de chaque page, donc dans les resultats de recherche")

# 54. Une page qui rend le 404 sans poser le code 404.
#     Le 404 de squelettes-dist posait lui-meme HTTP/1.0 404 Not Found et les
#     en-tetes de non-mise-en-cache. En le remplacant par le notre, on a
#     remplace aussi ce qu'il posait — et la page introuvable a pu se mettre a
#     repondre << 200 tout va bien >>.
#
#     C'est invisible a l'ecran : le visiteur voit la bonne page. Ce sont les
#     moteurs de recherche qui paient, en gardant chaque adresse morte dans
#     leur index pendant des mois, et en comptant chaque adresse fausse comme
#     une page du site.
#
#     Vaut aussi pour les sept pages de SPIP qu'on a fermees : elles rendent
#     notre 404, elles doivent en rendre le code.
for _f in sorted(glob.glob(os.path.join(RACINE, 'squelettes', '*.html'))):
    _src = open(_f, encoding='utf-8').read()
    if 'type-page=404' not in _src:
        continue
    if re.search(r'#HTTP_HEADER\{HTTP/1\.[01]\s', _src):
        continue
    signaler(os.path.relpath(_f, os.path.join(RACINE, '..')), 1,
             "cette page rend le 404 sans poser le code HTTP 404 : le visiteur "
             "voit la bonne page, les moteurs de recherche recoivent un 200 et "
             "gardent l'adresse morte dans leur index")

# 55. Le champ-piege des formulaires du site, remis a l'ecran.
#     La classe .piege sort le champ de l'ecran ET de l'ordre de tabulation.
#     C'est la barriere qui arrete la quasi-totalite du spam automatique : un
#     robot remplit tous les champs qu'il trouve dans le HTML, un humain ne
#     voit pas celui-la.
#
#     Une seule declaration doit exister, celle du theme, et elle doit
#     positionner le champ hors du cadre. Une seconde regle qui le remettrait
#     dans le flux — un reset trop large, un display:block, un position:static
#     — le rendrait visible : les navigateurs le rempliraient par
#     autocompletion, et TOUS les envois seraient refuses, sans message et
#     sans trace. Le formulaire aurait l'air de marcher et ne repondrait
#     jamais. Meme mecanique que le nobot de SPIP, regle 50, sur un champ qui
#     est le notre.
_CSS55 = os.path.join(RACINE, 'squelettes', 'css', 'theme.css')
if os.path.exists(_CSS55):
    _src = open(_CSS55, encoding='utf-8').read()
    _sans = re.sub(r'/\*.*?\*/', lambda _m: chr(10) * _m.group(0).count(chr(10)), _src, flags=re.S)
    _regles = [(_m.group(1).strip(), _m.group(2), _m.start())
               for _m in re.finditer(r'([^{}]+)\{([^{}]*)\}', _sans)
               if re.search(r'\.piege\b', _m.group(1))]
    if len(_regles) != 1:
        signaler('squelettes/css/theme.css', 1,
                 f"{len(_regles)} regles visent .piege : le champ-piege doit n'en "
                 "avoir qu'une, sinon la derniere peut le remettre a l'ecran et "
                 "faire refuser tous les envois en silence")
    else:
        _sel, _corps, _pos = _regles[0]
        if 'position:absolute' not in _corps.replace(' ', ''):
            signaler('squelettes/css/theme.css', _sans[:_pos].count(chr(10)) + 1,
                     "le champ-piege n'est plus sorti du flux : rendu visible, les "
                     "navigateurs le rempliraient et tous les envois seraient "
                     "refuses, sans message et sans trace")

if fautes:
    print('\n'.join(fautes))
    print(f'\n{len(fautes)} probleme(s).')
    sys.exit(1)
print('Squelettes verifies : rien a signaler.')
