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

    # Une tache periodique declaree doit exister elle aussi.
    for m in re.finditer(r'<genie\s+nom="([a-z_]+)"', src):
        tache = os.path.join(RACINE, '..', 'plugin-marly', 'genie', m.group(1) + '.php')
        if not os.path.exists(tache):
            signaler('plugin-marly/paquet.xml', 1,
                     f"tache {m.group(1)} declaree, mais genie/{m.group(1)}.php manque")

# 8. Un formulaire appele doit avoir ses deux fichiers.
for f in sorted(glob.glob('squelettes/**/*.html', recursive=True)):
    s = open(f, encoding='utf-8').read()
    for m in re.finditer(r'#FORMULAIRE_([A-Z_]+)', s):
        nom = m.group(1).lower()
        base = os.path.join(RACINE, '..', 'plugin-marly', 'formulaires', nom)
        if not os.path.exists(base + '.php') and not os.path.exists(base + '.html'):
            signaler(f, s[:m.start()].count(chr(10)) + 1,
                     f"formulaire {nom} appele, mais formulaires/{nom}.php et .html manquent")

# 6. Accolades CSS. Une accolade orpheline ferme la feuille en avance et
#    toutes les regles suivantes sont ignorees, sans le moindre message.
for f in sorted(glob.glob('squelettes/css/*.css')):
    s = open(f, encoding='utf-8').read()
    if s.count('{') != s.count('}'):
        signaler(f, 1, f"accolades desequilibrees : {s.count('{')} ouvrantes, {s.count('}')} fermantes")

if fautes:
    print('\n'.join(fautes))
    print(f'\n{len(fautes)} probleme(s).')
    sys.exit(1)
print('Squelettes verifies : rien a signaler.')
