#!/usr/bin/env python3
"""
Reconstruit squelettes/inc/icones.html à partir du dépôt Remix Icon.

À relancer chaque fois qu'on emploie une nouvelle classe ri-* dans un
squelette : le sprite ne contient que les icônes réellement utilisées.

    git clone --depth 1 https://github.com/Remix-Design/RemixIcon.git /tmp/ri
    python3 outils/construire-icones.py /tmp/ri/icons
"""
import os, re, sys, glob

SRC = sys.argv[1] if len(sys.argv) > 1 else '/tmp/ri/icons'
RACINE = os.path.dirname(os.path.dirname(os.path.abspath(__file__)))

utilisees = set()
for f in glob.glob(os.path.join(RACINE, 'squelettes/**/*.html'), recursive=True) + \
         glob.glob(os.path.join(RACINE, 'apercu/*.html')):
    utilisees |= set(re.findall(r'\bri-[a-z0-9-]+', open(f, encoding='utf-8').read()))

# La palette proposée à la mairie dans l'espace privé. Ces icônes ne sont pas
# écrites dans les gabarits — elles sont choisies au clic — donc le scan ne
# peut pas les voir. Sans cette lecture, une icône choisie par la mairie
# s'afficherait vide.
palette = os.path.join(RACINE, 'outils/palette-icones.txt')
if os.path.exists(palette):
    for ligne in open(palette, encoding='utf-8'):
        ligne = ligne.strip()
        if ligne and not ligne.startswith('#'):
            utilisees.add(ligne.split()[0])

index = {os.path.splitext(os.path.basename(p))[0]: p
         for p in glob.glob(os.path.join(SRC, '**', '*.svg'), recursive=True)}

symboles, absentes = [], []
for cls in sorted(utilisees):
    p = index.get(cls[3:])
    if not p:
        absentes.append(cls)
        continue
    src = open(p, encoding='utf-8').read()
    vb = re.search(r'viewBox="([^"]+)"', src)
    corps = re.sub(r'^.*?<svg[^>]*>', '', src, flags=re.S)
    corps = re.sub(r'</svg>\s*$', '', corps, flags=re.S).strip().replace('fill="none"', '')
    symboles.append(f'<symbol id="{cls}" viewBox="{vb.group(1) if vb else "0 0 24 24"}">{corps}</symbol>')

sprite = ('<svg xmlns="http://www.w3.org/2000/svg" style="display:none" aria-hidden="true">\n'
          + '\n'.join('\t' + x for x in symboles) + '\n</svg>\n')

entete = (
    "[(#REM)\n"
    "  inc/icones.html — jeu d'icônes embarqué. FICHIER GÉNÉRÉ, ne pas éditer :\n"
    "  relancer outils/construire-icones.py.\n"
    "  ---------------------------------------------------------------------------\n"
    "  Icônes issues de Remix Icon (Apache License 2.0), voir\n"
    "  squelettes/fonts/LICENSE-remixicon.txt\n\n"
    f"  {len(symboles)} icônes embarquées : celles employées dans les gabarits, plus la\n"
    "  palette de outils/palette-icones.txt que la mairie peut choisir au clic. La police\n"
    "  complète pèse 189 Ko et sa feuille de style 157 Ko pour 3229 icônes : ici\n"
    "  quelques kilo-octets, aucun clignotement au chargement, et un SVG reste\n"
    "  lisible par les outils d'assistance là où un glyphe de police est annoncé\n"
    "  comme un caractère inconnu.\n"
    "]\n")

open(os.path.join(RACINE, 'squelettes/inc/icones.html'), 'w', encoding='utf-8').write(entete + sprite)
open(os.path.join(RACINE, 'apercu/icones-sprite.html'), 'w', encoding='utf-8').write(sprite)
print(f"{len(symboles)} icônes embarquées")
if absentes:
    print("  introuvables :", ', '.join(absentes))
