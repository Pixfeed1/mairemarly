#!/usr/bin/env python3
"""
Construit apercu/reservation-publiable.html : la page de réservation telle
que la verra un habitant, avec des données d'exemple.

Comme pour l'accueil, la page est régénérée intégralement à partir du CSS et
du sprite réels — jamais retouchée à la main, sinon elle diverge du thème.

    python3 outils/construire-apercu-reservation.py
"""
import base64, os, re

R = os.path.dirname(os.path.dirname(os.path.abspath(__file__)))
lire = lambda *p: open(os.path.join(R, *p), encoding='utf-8').read()

polices = lire('squelettes', 'css', 'polices.css')
def _incorporer(m):
    with open(os.path.join(R, 'squelettes', 'fonts', m.group(1)), 'rb') as f:
        return 'url("data:font/woff2;base64,%s")' % base64.b64encode(f.read()).decode()
polices = re.sub(r'url\("\.\./fonts/([^"]+)"\)', _incorporer, polices)

css    = polices + '\n' + lire('squelettes', 'css', 'theme.css')
sprite = lire('apercu', 'icones-sprite.html')
embleme = re.sub(r'\[\(#REM\).*?\]', '', lire('squelettes', 'inc', 'embleme.html'), flags=re.S).strip()

# L'en-tête est repris tel quel de l'aperçu d'accueil, jusqu'à la bannière.
entete = lire('apercu', 'entete.html')
entete = entete[entete.index('<header'): entete.index('</header>') + len('</header>')]
entete = re.sub(r'<img class="sceau"[^>]*>', embleme, entete)

corps = '''
<main id="contenu-principal">
<div class="large page-reservation">

<section class="manifs">
	<h1 class="titre-bloc">S’inscrire <span>à une sortie, un repas, une manifestation</span></h1>

	<div class="manifs-grille">

		<article class="manif">
			<p class="manif-quand">dimanche 11 octobre · 12h00</p>
			<h2 class="manif-titre">Repas des aînés</h2>
			<p class="manif-lieu"><svg class="ico" aria-hidden="true"><use href="#ri-map-pin-2-line"/></svg> Salle des fêtes</p>
			<div class="manif-texte"><p>Le repas annuel offert par la commune aux habitants de 65 ans et plus. Inscription obligatoire avant le 3 octobre, le temps de commander les couverts.</p></div>
			<p class="manif-tarif">Gratuit pour les Marlygomontois de plus de 65 ans · 22 € pour les accompagnants</p>
			<p class="manif-places">Places restantes : <strong>17</strong> sur 90</p>
			<div class="manif-formulaire">
				<div class="formulaire_spip">
					<form method="post" action="#">
						<ul>
							<li><label for="p1">Nombre de places</label><input type="number" id="p1" value="1" min="1" max="4"></li>
							<li><label for="n1">Nom et prénom</label><input type="text" id="n1"></li>
							<li><label for="c1">Adresse électronique</label><input type="email" id="c1"></li>
							<li><label for="t1">Téléphone</label><input type="tel" id="t1"></li>
							<li><label for="m1">Précision</label><textarea id="m1" rows="2"></textarea>
								<small>Régime alimentaire, personne à mobilité réduite…</small></li>
						</ul>
						<p class="boutons"><input type="submit" class="submit" value="Je m’inscris"></p>
					</form>
				</div>
			</div>
		</article>

		<article class="manif">
			<p class="manif-quand">dimanche 7 juin · 06h00</p>
			<h2 class="manif-titre">Brocante du village — emplacements</h2>
			<p class="manif-lieu"><svg class="ico" aria-hidden="true"><use href="#ri-map-pin-2-line"/></svg> Rue de l’Église et place de la Mairie</p>
			<div class="manif-texte"><p>Réservez votre emplacement pour la brocante annuelle. Un emplacement fait 4 mètres linéaires. Les demandes sont validées par la mairie dans l’ordre d’arrivée.</p></div>
			<p class="manif-tarif">6 € l’emplacement · gratuit pour les habitants de la commune</p>
			<p class="manif-places">Places restantes : <strong>34</strong> sur 60</p>
			<div class="manif-formulaire">
				<div class="formulaire_spip">
					<form method="post" action="#">
						<ul>
							<li><label for="p2">Nombre de places</label><input type="number" id="p2" value="1" min="1" max="3"></li>
							<li><label for="n2">Nom et prénom</label><input type="text" id="n2"></li>
							<li><label for="c2">Adresse électronique</label><input type="email" id="c2"></li>
							<li><label for="t2">Téléphone</label><input type="tel" id="t2"></li>
							<li><label for="m2">Précision</label><textarea id="m2" rows="2"></textarea>
								<small>Mètres d’emplacement souhaités, véhicule à garer…</small></li>
						</ul>
						<p class="boutons"><input type="submit" class="submit" value="Je m’inscris"></p>
					</form>
				</div>
			</div>
		</article>

	</div>
</section>

	<section class="salle">
		<h1 class="titre-bloc">Réserver <span>la salle des fêtes</span></h1>

		<div class="salle-grille">

			<div class="salle-conditions">
				<div class="salle-texte">
					<p>La salle des fêtes accueille les repas de famille, les
					assemblées générales et les manifestations associatives. Elle
					dispose d’une cuisine équipée, de vaisselle pour 120 couverts,
					de tables et de chaises, et d’une sonorisation.</p>
					<p>Les associations de la commune sont prioritaires. La remise
					des clés se fait en mairie, aux heures d’ouverture.</p>
				</div>

				<dl class="fiche">
					<dt>Capacité</dt><dd>120 personnes assises</dd>
					<dt>Tarif habitants de la commune</dt><dd>80 € la journée</dd>
					<dt>Tarif hors commune</dt><dd>150 € la journée</dd>
					<dt>Caution</dt><dd>300 €</dd>
				</dl>

				<div class="salle-occupe">
					<h2>Dates déjà retenues</h2>
					<ul>
						<li><strong>samedi 12 septembre 2026</strong><span>10h00 – 23h59</span></li>
						<li><strong>dimanche 4 octobre 2026</strong><span>09h00 – 18h00</span></li>
						<li><strong>samedi 21 novembre 2026</strong><span>14h00 – 23h59</span></li>
					</ul>
				</div>
			</div>

			<div class="salle-formulaire">
				<div class="formulaire_spip formulaire_reserver_salle">
					<form method="post" action="#">
						<p class="explication">Ce formulaire enregistre une <strong>demande</strong> :
						il ne réserve pas la salle. La mairie vous répondra par courriel.
						Les associations de la commune sont prioritaires.</p>

						<fieldset>
							<legend>Quand</legend>
							<ul>
								<li class="editer"><label for="d">Date souhaitée</label>
									<input type="date" id="d" value="2026-10-17"></li>
								<li class="editer"><label for="h1">De</label>
									<input type="time" id="h1" value="09:00"></li>
								<li class="editer"><label for="h2">À</label>
									<input type="time" id="h2" value="23:00"></li>
							</ul>
						</fieldset>

						<fieldset>
							<legend>Qui</legend>
							<ul>
								<li class="editer"><label for="n">Nom et prénom</label>
									<input type="text" id="n"></li>
								<li class="editer"><label for="o">Association ou organisme</label>
									<input type="text" id="o">
									<small>Laissez vide s’il s’agit d’une demande personnelle.</small></li>
								<li class="editer"><label for="c">Adresse électronique</label>
									<input type="email" id="c"></li>
								<li class="editer"><label for="t">Téléphone</label>
									<input type="tel" id="t"></li>
								<li class="editer"><label for="m">Motif de la demande</label>
									<textarea id="m" rows="3"></textarea>
									<small>Exemple : repas de famille, assemblée générale, loto de l’association.</small></li>
							</ul>
						</fieldset>

						<p class="boutons"><input type="submit" class="submit" value="Envoyer ma demande"></p>
					</form>
				</div>
			</div>

		</div>
	</section>

</div>
</main>
'''

page = f'''<!doctype html>
<html lang="fr">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>Réserver et s’inscrire — Marly-Gomont</title>
<meta name="robots" content="noindex, nofollow">
<style>
{css}
</style>
</head>
<body class="page-reservation">
{sprite}
{entete}
{corps}
</body>
</html>
'''
open(os.path.join(R, 'apercu', 'reservation-publiable.html'), 'w', encoding='utf-8').write(page)
print(f"apercu/reservation-publiable.html : {len(page)} octets")
