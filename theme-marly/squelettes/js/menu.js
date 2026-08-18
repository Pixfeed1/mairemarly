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
	   sinon le défilement reste bloqué alors que le tiroir a disparu.
	   ATTENTION : ce seuil doit rester identique à celui de theme.css. */
	var grandEcran = window.matchMedia('(min-width: 1200px)');
	var surChangement = function (e) { if (e.matches && ouvert) { basculer(false); } };
	if (grandEcran.addEventListener) { grandEcran.addEventListener('change', surChangement); }
	else if (grandEcran.addListener) { grandEcran.addListener(surChangement); }

	majInertie();
})();

/**
 * Panneau de recherche plein écran.
 * ---------------------------------------------------------------------------
 * Amélioration progressive : la loupe est un vrai lien vers la page de
 * recherche. Sans JavaScript elle y mène et rien n'est perdu. Avec, on
 * intercepte le clic et le panneau s'ouvre par-dessus la page courante —
 * chercher ne doit pas faire perdre l'endroit où l'on se trouvait.
 */
(function () {
	'use strict';

	var declencheurs = document.querySelectorAll('[data-ouvre-recherche]');
	var panneau = document.getElementById('panneau-recherche');
	if (!declencheurs.length || !panneau) { return; }

	var champ    = panneau.querySelector('input[type="search"]');
	var fermeture = panneau.querySelector('[data-ferme-recherche]');
	var ouvert   = false;
	var appelant = null;   /* pour rendre le focus à qui l'a ouvert */

	function basculer(vers, origine) {
		ouvert = (typeof vers === 'boolean') ? vers : !ouvert;

		panneau.setAttribute('data-ouvert', ouvert ? 'oui' : 'non');
		panneau.setAttribute('aria-hidden', ouvert ? 'false' : 'true');
		if ('inert' in HTMLElement.prototype) { panneau.inert = !ouvert; }
		document.documentElement.style.overflow = ouvert ? 'hidden' : '';

		if (ouvert) {
			appelant = origine || null;
			if (champ) { champ.focus(); champ.select(); }
		} else if (appelant) {
			appelant.focus();
			appelant = null;
		}
	}

	Array.prototype.forEach.call(declencheurs, function (el) {
		el.addEventListener('click', function (e) {
			e.preventDefault();          /* le lien reste le repli sans JS */
			basculer(true, el);
		});
	});

	if (fermeture) {
		fermeture.addEventListener('click', function () { basculer(false); });
	}

	/* Cliquer en dehors du champ ferme : le fond n'est pas une zone morte. */
	panneau.addEventListener('click', function (e) {
		if (e.target === panneau) { basculer(false); }
	});

	document.addEventListener('keydown', function (e) {
		if (!ouvert) { return; }

		if (e.key === 'Escape') { basculer(false); return; }

		/* Le focus ne doit pas s'échapper derrière le panneau. */
		if (e.key !== 'Tab') { return; }
		var cibles = panneau.querySelectorAll('a[href], button:not([disabled]), input');
		if (!cibles.length) { return; }
		var premier = cibles[0], dernier = cibles[cibles.length - 1];
		if (e.shiftKey && document.activeElement === premier) {
			e.preventDefault(); dernier.focus();
		} else if (!e.shiftKey && document.activeElement === dernier) {
			e.preventDefault(); premier.focus();
		}
	});

	if ('inert' in HTMLElement.prototype) { panneau.inert = true; }
})();
