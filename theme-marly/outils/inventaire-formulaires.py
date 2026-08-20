#!/usr/bin/env python3
"""
Inventaire des champs du back office.

    python3 outils/inventaire-formulaires.py

Imprime, pour chaque champ, l'etiquette employee sur chaque ecran. C'est le
seul moyen de voir d'un coup si la meme question est posee deux fois de deux
facons differentes — ce qui est arrive avec l'ordre d'affichage, corrige sur
un ecran et oublie sur les autres.

A lire avant de livrer un ecran nouveau : si le champ existe deja ailleurs,
il doit se presenter pareil, ou la divergence doit etre voulue et declaree
dans verifier-squelettes.py.
"""
import io, re, os, glob
from collections import defaultdict

RACINE = os.path.dirname(os.path.dirname(os.path.abspath(__file__)))
FORMS = os.path.join(RACINE, '..', 'plugin-marly', 'formulaires')
LANG = os.path.join(RACINE, '..', 'plugin-marly', 'lang', 'marly_fr.php')

textes = dict(re.findall(r"^\s*'([a-z0-9_]+)'\s*=>\s*'(.*?)',\s*$",
                         io.open(LANG, encoding='utf-8').read(), re.M))

champs = defaultdict(dict)
for f in sorted(glob.glob(os.path.join(FORMS, '*.html'))):
    ecran = os.path.basename(f).replace('editer_', '').replace('.html', '')
    s = io.open(f, encoding='utf-8').read()
    for li in re.findall(r'<li class="marly-champ[^"]*">(.*?)</li>', s, re.S):
        n = re.search(r'name="([a-z_]+)"', li)
        l = re.search(r'<label[^>]*>\s*<:marly:([a-z0-9_]+):>', li)
        if n and l:
            champs[n.group(1)][ecran] = l.group(1)

for nom, par_ecran in sorted(champs.items()):
    divergent = len(set(par_ecran.values())) > 1
    print(('!! ' if divergent else '   ') + nom)
    for ecran, cle in sorted(par_ecran.items()):
        print('       %-18s %-26s %s' % (ecran, cle, textes.get(cle, '?')))
print()
print('!! = le meme champ ne porte pas la meme etiquette partout')
