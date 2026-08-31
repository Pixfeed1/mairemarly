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

# Les polices sont EMBARQUÉES, pas appelées chez Google : l'aperçu doit se
# comporter comme le site — un CDN transmettrait l'adresse IP du visiteur à
# un tiers, ce qu'une collectivité ne peut pas faire sans base légale.
polices = lire('squelettes', 'css', 'polices.css')
def _incorporer(m):
    nom = m.group(1)
    with open(os.path.join(R, 'squelettes', 'fonts', nom), 'rb') as f:
        b64 = base64.b64encode(f.read()).decode()
    return f'url("data:font/woff2;base64,{b64}")'
polices = re.sub(r'url\("\.\./fonts/([^"]+)"\)', _incorporer, polices)

css = polices + '\n' + lire('squelettes', 'css', 'theme.css')
js = lire('squelettes', 'js', 'menu.js')
sprite = lire('apercu', 'icones-sprite.html')

# L'emblème est repris du squelette, commentaire SPIP retiré.
embleme = re.sub(r'\[\(#REM\).*?\]', '', lire('squelettes', 'inc', 'embleme.html'), flags=re.S).strip()

corps = lire('apercu', 'entete.html')
corps = corps[corps.index('<body>') + 6 : corps.index('<script src=')]
corps = re.sub(r'<svg xmlns[^>]*style="display:none".*?</svg>', '', corps, flags=re.S)
corps = re.sub(r'<img class="sceau"[^>]*>', embleme, corps)
# Le paysage dessiné a été retiré du thème le 27 août 2026, commit d5e6299.
# Le fichier n'existe plus : ce script s'arrêtait dessus depuis ce jour-là, et
# l'aperçu autonome est resté figé sur l'état d'avant. Règle 84.

page = f'''<!doctype html>
<html lang="fr">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>Marly-Gomont</title>
<meta name="robots" content="noindex, nofollow">
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
