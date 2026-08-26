#!/usr/bin/env python3
# -*- coding: utf-8 -*-
"""
Audit RGAA mecanique du site.
---------------------------------------------------------------------------
    python3 theme-marly/outils/auditer-rgaa.py https://exemple.fr [utilisateur:motdepasse]

CE QUE CET OUTIL EST, ET CE QU'IL N'EST PAS.

Il verifie les criteres du RGAA 4.1 qui se lisent dans le code source d'une
page : un alt absent, un champ sans etiquette, un titre de niveau saute, un
lien vide. Ceux-la se comptent, et une machine les compte mieux qu'un humain
fatigue.

IL NE REMPLACE PAS UN AUDIT. Le RGAA compte 106 criteres ; une bonne moitie
demande un jugement — la pertinence d'un intitule, l'ordre de lecture reel,
ce qu'annonce un lecteur d'ecran, la couleur employee seule pour porter une
information. Ceux-la sont listes a la fin, non coches, et c'est volontaire :
une declaration d'accessibilite qui s'appuierait sur ce seul script serait
fausse.

L'ECHANTILLON suit la methode du RGAA : les pages obligatoires — accueil,
contact, mentions legales, declaration d'accessibilite, plan du site,
authentification — plus un echantillon de pages representatives, une par
gabarit du site.

Sans dependance : ni bs4, ni requests. Le serveur d'une mairie n'a que ce que
PHP et Python apportent en standard, et un outil qu'on ne peut pas lancer est
un outil qui ne sert pas.
"""
import sys, re, base64, urllib.request, urllib.error
from html.parser import HTMLParser

# --- L'echantillon -----------------------------------------------------------
# Les pages OBLIGATOIRES du RGAA d'abord, puis une page par gabarit. Les pages
# de detail (un article, une fiche) prennent le premier identifiant venu : le
# gabarit compte, pas le contenu.
PAGES = [
    ('Accueil',                    ''),
    ('Contact',                    'spip.php?page=contact'),
    ('Mentions legales',           'spip.php?page=mentions-legales'),
    ('Declaration accessibilite',  'spip.php?page=accessibilite'),
    ('Donnees personnelles',       'spip.php?page=confidentialite'),
    ('Credits',                    'spip.php?page=credits'),
    ('Plan du site',               'spip.php?page=plan'),
    ('Connexion',                  'spip.php?page=login'),
    ('Mot de passe oublie',        'spip.php?page=spip_pass'),
    ('Recherche (resultats)',      'spip.php?page=recherche&recherche=mairie'),
    # Un article qui n'existe pas plutot qu'une page qui n'existe pas : les
    # deux rendent le meme 404, mais la seconde fait ecrire a SPIP une ERREUR
    # dans tmp/log/spip.log a chaque passage de l'audit. Un journal rempli de
    # fausses alarmes rend les vraies invisibles — c'est exactement ce qui a
    # laisse l'erreur de la page de reservation dormir des semaines.
    ('Page introuvable',           'spip.php?page=article&id_article=999999'),
    ('Toutes les demarches',       'spip.php?page=demarches'),
    ('Annuaire des associations',  'spip.php?page=associations'),
    ('Commerces et services',      'spip.php?page=commerces'),
    ('Conseil municipal',          'spip.php?page=conseil'),
    ('Reservation de salle',       'spip.php?page=reservation'),
    ('Lettre d information',       'spip.php?page=newsletter'),
    ('Lieux',                      'spip.php?page=lieux'),
]

# --- Ce qu'une machine ne tranche pas ---------------------------------------
A_LA_MAIN = [
    ('1.3 / 1.9', 'La pertinence de chaque alternative textuelle : « photo » n\'est pas une alternative.'),
    ('3.1',       'Une information portee par la seule couleur — un statut, un lien dans un paragraphe.'),
    ('3.2 / 3.3', 'Le contraste des textes et des composants SUR LE RENDU. La palette a ete calculee le 26 aout 2026 ; un contenu redige peut poser une autre couleur.'),
    ('4.x',       'Les videos et les sons : transcription, sous-titres, audiodescription.'),
    ('7.x',       'Les scripts : ce qu\'annonce un lecteur d\'ecran quand un panneau s\'ouvre, quand une erreur apparait.'),
    ('8.2',       'La validite du code, a passer au validateur du W3C.'),
    ('8.6',       'La pertinence du titre de chaque page, lu hors contexte.'),
    ('9.1',       'La pertinence des titres, pas seulement leur hierarchie.'),
    ('10.x',      'Le rendu sans feuille de style, et l\'agrandissement du seul texte a 200 %.'),
    ('11.10',     'Le controle de saisie : les messages d\'erreur sont-ils compris, et atteignables au clavier.'),
    ('12.8 / 12.9', 'L\'ordre de tabulation reel sur chaque page, et l\'absence de piege au clavier.'),
    ('13.3',      'Les documents joints, PDF compris : ils ont leurs propres exigences, et la mairie en deposera.'),
    ('LECTEUR',   'Un parcours complet avec NVDA ou VoiceOver. Rien ne le remplace.'),
]

VIDES = {'img','input','br','hr','meta','link','source','area','col','embed','use','path'}

class Page(HTMLParser):
    """Un arbre minimal : ce qu'il faut pour les criteres, rien de plus."""
    def __init__(self):
        super().__init__(convert_charrefs=True)
        self.pile = []
        self.elements = []          # (tag, attrs, texte, ligne, profondeur)
        self.doctype = ''
    def handle_decl(self, d):
        self.doctype = d
    def masque(self):
        """Un aria-hidden sur un ANCETRE retire tout le sous-arbre de l'arbre
        d'accessibilite. Verifier l'element seul faisait crier l'outil sur des
        icones parfaitement masquees par le span qui les porte : cinq fausses
        alertes sur l'annuaire des associations, le 26 aout 2026."""
        return any(x[1].get('aria-hidden') == 'true' for x in self.pile)
    def handle_starttag(self, tag, attrs):
        a = dict(attrs)
        e = [tag, a, '', self.getpos()[0], len(self.pile), self.masque()]
        self.elements.append(e)
        if tag not in VIDES:
            self.pile.append(e)
    def handle_startendtag(self, tag, attrs):
        self.elements.append([tag, dict(attrs), '', self.getpos()[0], len(self.pile), self.masque()])
    def handle_endtag(self, tag):
        for i in range(len(self.pile) - 1, -1, -1):
            if self.pile[i][0] == tag:
                del self.pile[i:]
                return
    def handle_data(self, d):
        if d.strip():
            for e in self.pile:
                e[2] += ' ' + d.strip()

def nom_accessible(e):
    """Le nom d'un lien ou d'un bouton : son texte, ou ce qui en tient lieu."""
    tag, a, txt = e[0], e[1], e[2]
    if a.get('aria-label', '').strip():
        return a['aria-label'].strip()
    if a.get('title', '').strip():
        return a['title'].strip()
    if tag == 'input':
        return (a.get('value') or a.get('alt') or '').strip()
    return ' '.join(txt.split())

def lire(url, auth):
    req = urllib.request.Request(url, headers={'User-Agent': 'audit-rgaa/1.0'})
    if auth:
        req.add_header('Authorization', 'Basic ' +
                       base64.b64encode(auth.encode()).decode())
    try:
        with urllib.request.urlopen(req, timeout=25) as r:
            return r.status, r.read().decode('utf-8', 'replace')
    except urllib.error.HTTPError as e:
        return e.code, e.read().decode('utf-8', 'replace')
    except Exception as e:
        return 0, str(e)

# Des traces de langage SPIP dans la page rendue. Ce n'est pas un critere du
# RGAA : c'est pire, c'est du code qui s'affiche. Un bloc [ ... ] n'evalue
# qu'UNE balise entre parenthesees ; la seconde ressort en clair, filtres
# compris, souvent au milieu d'un href — et le lien ne mene alors nulle part.
#
# Trouve le 26 aout 2026 par le validateur du W3C, qui refusait
# href="mailto:(mairie@…|sinon{'…'}|attribut_html)". Le lien courriel du pied
# etait casse sur toutes les pages du site, et personne ne l'avait vu : a
# l'ecran il ressemble a une adresse.
TRACES_SPIP = (
    '|attribut_html', '|urlencode', '|replace{', '|sinon{', '|couper{',
    '#CONFIG{', '#TELEPHONE', '#COURRIEL', '#LATITUDE', '#LONGITUDE',
)

def auditer(nom, html):
    """Rend une liste de (critere, message). Vide = rien a signaler."""
    f = []

    corps = html
    for _s in TRACES_SPIP:
        i = corps.find(_s)
        if i >= 0:
            extrait = ' '.join(corps[max(0, i - 70):i + len(_s) + 12].split())
            f.append(('SPIP', f'code SPIP non interprete dans la page : …{extrait}…'))

    p = Page()
    try:
        p.feed(html)
    except Exception as e:
        return [('parse', f'la page n\'a pas pu etre lue : {e}')]
    els = p.elements
    par = lambda t: [e for e in els if e[0] == t]

    # 8.1 — un doctype, et il doit etre en tete.
    if not p.doctype or not p.doctype.lower().startswith('doctype html'):
        f.append(('8.1', 'doctype HTML absent ou non conforme'))

    # 8.3 / 8.4 — la langue par defaut, declaree et valide.
    html_el = par('html')
    if not html_el:
        f.append(('8.3', 'element html introuvable'))
    else:
        lang = html_el[0][1].get('lang', '').strip()
        if not lang:
            f.append(('8.3', 'attribut lang absent sur <html>'))
        elif not re.match(r'^[a-z]{2}(-[A-Za-z0-9]+)*$', lang):
            f.append(('8.4', f'code de langue douteux : « {lang} »'))

    # 8.5 — un titre de page, non vide.
    t = par('title')
    if not t or not t[0][2].strip():
        f.append(('8.5', 'titre de page absent ou vide'))

    # 1.1 — chaque image porte une alternative, meme vide si elle est decorative.
    for e in par('img'):
        if 'alt' not in e[1]:
            f.append(('1.1', f'ligne {e[3]} : <img> sans attribut alt — src={e[1].get("src","?")[:60]}'))

    # 1.1 — les SVG decoratifs doivent etre ignores, les autres nommes.
    for e in par('svg'):
        a = e[1]
        if a.get('aria-hidden') == 'true' or e[5]:
            continue
        if not (a.get('role') == 'img' and (a.get('aria-label') or a.get('aria-labelledby'))):
            f.append(('1.1', f'ligne {e[3]} : <svg> ni masque aux lecteurs d\'ecran (aria-hidden) ni nomme (role=img + aria-label)'))

    # 2.1 — un cadre porte un titre.
    for e in par('iframe'):
        if not e[1].get('title', '').strip():
            f.append(('2.1', f'ligne {e[3]} : <iframe> sans title'))

    # 6.1 — un lien a un intitule. Un lien vide est un cul-de-sac.
    for e in par('a'):
        if 'href' not in e[1]:
            continue
        if not nom_accessible(e):
            f.append(('6.1', f'ligne {e[3]} : lien sans intitule — href={e[1].get("href","")[:60]}'))

    # 6.1 — un bouton aussi.
    for e in par('button'):
        if not nom_accessible(e):
            f.append(('6.1', f'ligne {e[3]} : <button> sans intitule'))

    # 11.1 — chaque champ a une etiquette, et elle le designe.
    labels = {e[1].get('for', '') for e in par('label') if e[1].get('for')}
    for e in par('input') + par('select') + par('textarea'):
        a = e[1]
        typ = a.get('type', 'text').lower()
        if e[0] == 'input' and typ in ('hidden', 'submit', 'button', 'reset', 'image'):
            continue
        idf = a.get('id', '')
        if idf and idf in labels:
            continue
        if a.get('aria-label', '').strip() or a.get('aria-labelledby', '').strip():
            continue
        if a.get('title', '').strip():
            f.append(('11.1', f'ligne {e[3]} : champ etiquete par un title seul — name={a.get("name","?")}'))
            continue
        f.append(('11.1', f'ligne {e[3]} : champ sans etiquette — name={a.get("name","?")}, type={typ}'))

    # 11.5 — les boutons radio et cases liees vont dans un fieldset.
    #        (verifie seulement la presence d'une legende quand un fieldset existe)
    for e in par('fieldset'):
        i = els.index(e)
        suite = els[i+1:i+4]
        if not any(x[0] == 'legend' for x in suite):
            f.append(('11.5', f'ligne {e[3]} : <fieldset> sans <legend> en premier enfant'))

    # 9.1 — la hierarchie des titres : un h1, et pas de niveau saute.
    titres = [(int(e[0][1]), e) for e in els if re.match(r'^h[1-6]$', e[0])]
    h1 = [x for x in titres if x[0] == 1]
    if not h1:
        f.append(('9.1', 'aucun <h1> sur la page'))
    elif len(h1) > 1:
        f.append(('9.1', f'{len(h1)} <h1> sur la page : il en faut un seul'))
    precedent = 0
    for niveau, e in titres:
        if precedent and niveau > precedent + 1:
            f.append(('9.1', f'ligne {e[3]} : saut de h{precedent} a h{niveau} — « {" ".join(e[2].split())[:50]} »'))
        precedent = niveau

    # 9.2 / 12.6 — les regions. Une page a un main, et un seul.
    for region, mini, maxi in (('main', 1, 1), ('header', 1, 2), ('footer', 1, 2), ('nav', 1, 9)):
        n = len(par(region))
        if n < mini:
            f.append(('12.6', f'aucune region <{region}> sur la page'))
        elif n > maxi:
            f.append(('12.6', f'{n} regions <{region}> : il en faut au plus {maxi}'))

    # 12.6 — plusieurs nav doivent se distinguer par un nom.
    navs = par('nav')
    if len(navs) > 1:
        noms = [n[1].get('aria-label', '') or n[1].get('aria-labelledby', '') for n in navs]
        if len([x for x in noms if x]) < len(navs):
            f.append(('12.6', f'{len(navs)} <nav> dont {len(navs)-len([x for x in noms if x])} sans aria-label : on ne peut pas les distinguer'))

    # 12.7 — un lien d'evitement, et il doit etre le premier lien de la page.
    liens = [e for e in par('a') if 'href' in e[1]]
    if not liens:
        f.append(('12.7', 'aucun lien sur la page'))
    else:
        premier = liens[0]
        if not premier[1].get('href', '').startswith('#'):
            f.append(('12.7', f'le premier lien de la page n\'est pas un lien d\'evitement — href={premier[1].get("href","")[:50]}'))

    # 8.9 — les balises de presentation.
    for tag in ('center', 'font', 'big', 'tt', 'strike', 'basefont', 'blink', 'marquee'):
        if par(tag):
            f.append(('8.9', f'<{tag}> employe : balise de presentation'))

    # 5.x — les tableaux de donnees.
    for e in par('table'):
        i = els.index(e)
        fin = i + 1
        while fin < len(els) and els[fin][4] > e[4]:
            fin += 1
        dedans = els[i+1:fin]
        if not any(x[0] == 'th' for x in dedans):
            f.append(('5.6', f'ligne {e[3]} : tableau sans <th> — s\'il porte des donnees, ses en-tetes manquent'))
        if not any(x[0] == 'caption' for x in dedans):
            f.append(('5.4', f'ligne {e[3]} : tableau sans <caption>'))
        for th in [x for x in dedans if x[0] == 'th']:
            if not th[1].get('scope') and not th[1].get('id'):
                f.append(('5.7', f'ligne {th[3]} : <th> sans scope ni id'))
                break

    # 8.x — les identifiants en double cassent les liaisons label/champ.
    vus, doubles = set(), set()
    for e in els:
        i = e[1].get('id')
        if i:
            (doubles if i in vus else vus).add(i)
    for i in sorted(doubles):
        f.append(('8.2', f'identifiant en double : id="{i}"'))

    # 13.8 — rien qui bouge tout seul.
    for e in par('marquee') + [x for x in par('video') if 'autoplay' in x[1]] \
             + [x for x in par('audio') if 'autoplay' in x[1]]:
        f.append(('13.8', f'ligne {e[3]} : <{e[0]}> demarre tout seul'))

    return f

def main():
    if len(sys.argv) < 2:
        print(__doc__)
        sys.exit(2)
    base = sys.argv[1].rstrip('/')
    auth = sys.argv[2] if len(sys.argv) > 2 else ''

    total = {}
    lignes = []
    print(f'Audit RGAA mecanique de {base}')
    print(f'{len(PAGES)} pages de l\'echantillon\n')

    for nom, chemin in PAGES:
        url = base + ('/' + chemin if chemin else '/')
        code, html = lire(url, auth)
        if code == 0:
            print(f'  !! {nom:<28} injoignable : {html[:60]}')
            continue
        fautes = auditer(nom, html)
        etat = 'ok' if not fautes else f'{len(fautes)} a corriger'
        print(f'  {code}  {nom:<28} {etat}')
        for crit, msg in fautes:
            total.setdefault(crit, []).append((nom, msg))
        lignes.append((nom, fautes))

    print('\n' + '=' * 74)
    print('CE QUI EST A CORRIGER, PAR CRITERE')
    print('=' * 74)
    if not total:
        print('  Rien a signaler sur les criteres verifiables mecaniquement.')
    for crit in sorted(total, key=lambda c: (-len(total[c]), c)):
        cas = total[crit]
        print(f'\n  Critere {crit} — {len(cas)} occurrence(s)')
        pages = {}
        for nom, msg in cas:
            pages.setdefault(nom, []).append(msg)
        for nom in pages:
            print(f'    {nom} :')
            for msg in pages[nom][:3]:
                print(f'      - {msg}')
            if len(pages[nom]) > 3:
                print(f'      ... et {len(pages[nom]) - 3} autre(s)')

    print('\n' + '=' * 74)
    print('CE QUE CE SCRIPT NE PEUT PAS TRANCHER — A FAIRE A LA MAIN')
    print('=' * 74)
    for crit, quoi in A_LA_MAIN:
        print(f'  {crit:<12} {quoi}')
    print('\nUne declaration d\'accessibilite qui s\'appuierait sur ce seul script')
    print('serait fausse. Ces lignes-la se verifient, page par page, par un humain.')

if __name__ == '__main__':
    main()
