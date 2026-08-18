/**
 * Menu mobile — ouverture, fermeture, accessibilité.
 * ---------------------------------------------------------------------------
 * Amélioration progressive : sans JavaScript, le bouton est masqué par le CSS
 * et le tiroir reste fermé. Le site demeure navigable, on ne perd aucun lien
 * puisque le pied de page reprend l'intégralité des rubriques.
 *
 * Exigences RGAA couvertes ici :
 *   - aria-expanded reflète l'état réel du bouton
 *   - le tiroir est retiré de l'ordre de tabulation quand il est fermé
 *   - la touche Échap ferme
 *   - le focus revient sur le bouton après fermeture, jamais dans le vide
 *   - la tabulation ne peut pas sortir du tiroir ouvert
 */
(function () {
	'use strict';

	var bouton = document.querySelector('.burger');
	var tiroir = document.getElementById('menu-mobile');
	var voile  = document.querySelector('.voile-menu');
	if (!bouton || !tiroir) { return; }

	var ouvert = false;

	/* Rend le tiroir inatteignable au clavier quand il est fermé : sans cela,
	   la tabulation part dans des liens invisibles hors de l'écran. */
	function majInertie() {
		tiroir.setAttribute('aria-hidden', ouvert ? 'false' : 'true');
		if ('inert' in HTMLElement.prototype) {
			tiroir.inert = !ouvert;
		}
	}

	function basculer(vers) {
		ouvert = (typeof vers === 'boolean') ? vers : !ouvert;

		bouton.setAttribute('aria-expanded', ouvert ? 'true' : 'false');
		tiroir.setAttribute('data-ouvert', ouvert ? 'oui' : 'non');
		if (voile) { voile.setAttribute('data-ouvert', ouvert ? 'oui' : 'non'); }

		/* Empêche la page de défiler derrière le tiroir ouvert. */
		document.documentElement.style.overflow = ouvert ? 'hidden' : '';

		majInertie();

		if (ouvert) {
			var premier = tiroir.querySelector('a, button');
			if (premier) { premier.focus(); }
		} else {
			bouton.focus();
		}
	}

	bouton.addEventListener('click', function () { basculer(); });

	var fermer = tiroir.querySelector('.fermer');
	if (fermer) { fermer.addEventListener('click', function () { basculer(false); }); }
	if (voile)  { voile.addEventListener('click',  function () { basculer(false); }); }

	document.addEventListener('keydown', function (e) {
		if (!ouvert) { return; }

		if (e.key === 'Escape') { basculer(false); return; }

		/* Piège à focus : la tabulation boucle à l'intérieur du tiroir. */
		if (e.key !== 'Tab') { return; }
		var cibles = tiroir.querySelectorAll('a[href], button:not([disabled]), input');
		if (!cibles.length) { return; }
		var premier = cibles[0];
		var dernier = cibles[cibles.length - 1];

		if (e.shiftKey && document.activeElement === premier) {
			e.preventDefault(); dernier.focus();
		} else if (!e.shiftKey && document.activeElement === dernier) {
			e.preventDefault(); premier.focus();
		}
	});

	/* Repasser en grand écran doit remettre la page dans un état sain :
	   sinon le défilement reste bloqué alors que le tiroir a disparu. */
	var grandEcran = window.matchMedia('(min-width: 1000px)');
	var surChangement = function (e) { if (e.matches && ouvert) { basculer(false); } };
	if (grandEcran.addEventListener) { grandEcran.addEventListener('change', surChangement); }
	else if (grandEcran.addListener) { grandEcran.addListener(surChangement); }

	majInertie();
})();
