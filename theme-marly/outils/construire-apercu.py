#!/usr/bin/env python3
"""
Reconstruit apercu/publiable.html à partir des squelettes.

La page autonome doit rester le reflet exact du thème. La patcher à la main
la fait diverger : ce script la régénère intégralement, en intégrant CSS,
JavaScript, sprite d'icônes, emblème et paysage.

    python3 outils/construire-apercu.py
"""
import base64, os, re

R = os.path.dirname(os.path.dirname(os.path.abspath(__file__)))
lire = lambda *p: open(os.path.join(R, *p), encoding='utf-8').read()

css = lire('squelettes', 'css', 'theme.css')
js = lire('squelettes', 'js', 'menu.js')
sprite = lire('apercu', 'icones-sprite.html')

# Le paysage devient une donnée intégrée : la page doit tenir en un fichier.
paysage = base64.b64encode(lire('squelettes', 'img', 'paysage.svg').encode()).decode()

# L'emblème est repris du squelette, commentaire SPIP retiré.
embleme = re.sub(r'\[\(#REM\).*?\]', '', lire('squelettes', 'inc', 'embleme.html'), flags=re.S).strip()

corps = lire('apercu', 'entete.html')
corps = corps[corps.index('<body>') + 6 : corps.index('<script src=')]
corps = re.sub(r'<svg xmlns[^>]*style="display:none".*?</svg>', '', corps, flags=re.S)
corps = re.sub(r'<img class="sceau"[^>]*>', embleme, corps)
corps = re.sub(r'src="\.\./squelettes/img/paysage\.svg"',
               f'src="data:image/svg+xml;base64,{paysage}"', corps)

page = f'''<!doctype html>
<html lang="fr">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>Marly-Gomont</title>
<meta name="robots" content="noindex, nofollow">
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Alegreya:wght@400;700&family=Alegreya+Sans:wght@400;500;700;800&family=Caveat:wght@700&family=Open+Sans:wght@400;700&display=swap" rel="stylesheet">
<style>
{css}
.faux-contenu{{ padding:56px 24px 140px; font-family:var(--texte); color:var(--encre-doux); }}
.faux-contenu p{{ max-width:62ch; margin:0 auto 18px; text-align:center; }}
</style>
</head>
<body>
{sprite}
{corps}
<script>
{js}
</script>
</body>
</html>
'''
open(os.path.join(R, 'apercu', 'publiable.html'), 'w', encoding='utf-8').write(page)
print(f"apercu/publiable.html régénéré : {len(page)} octets")
