/**
 * Menu plein écran.
 * ---------------------------------------------------------------------------
 * Amélioration progressive : sans JavaScript le bouton est masqué par le CSS
 * et le menu reste fermé. Le site demeure navigable — le pied de page reprend
 * l'intégralité des rubriques, aucun lien n'est perdu.
 *
 * Exigences RGAA couvertes :
 *   - aria-expanded reflète l'état réel du bouton, et pilote aussi son dessin
 *     (barres qui deviennent croix) : l'état visuel ne peut pas diverger de
 *     l'état annoncé, puisqu'il n'y a qu'une source
 *   - le menu fermé est retiré de l'ordre de tabulation
 *   - Échap ferme, le focus revient sur le bouton
 *   - la tabulation ne peut pas sortir du menu ouvert
 */
(function () {
	'use strict';

	var bouton  = document.querySelector('.burger');
	var menu    = document.getElementById('menu-plein');
	var entete  = document.querySelector('.entete');
	if (!bouton || !menu) { return; }

	var ouvert = false;

	/* Le menu commence sous l'en-tête, qui reste visible au-dessus de lui.
	   Sa hauteur est mesurée, jamais supposée : elle change avec la largeur
	   de l'écran et avec la longueur du nom de la commune. */
	function mesurerEntete() {
		if (!entete) { return; }
		document.documentElement.style.setProperty('--h-entete', entete.offsetHeight + 'px');
	}

	function majInertie() {
		menu.setAttribute('aria-hidden', ouvert ? 'false' : 'true');
		if ('inert' in HTMLElement.prototype) { menu.inert = !ouvert; }
	}

	function basculer(vers) {
		ouvert = (typeof vers === 'boolean') ? vers : !ouvert;

		if (ouvert) { mesurerEntete(); }

		bouton.setAttribute('aria-expanded', ouvert ? 'true' : 'false');
		menu.setAttribute('data-ouvert', ouvert ? 'oui' : 'non');
		document.documentElement.style.overflow = ouvert ? 'hidden' : '';

		majInertie();

		if (ouvert) {
			var premier = menu.querySelector('input, a, button');
			if (premier) { premier.focus(); }
		} else {
			bouton.focus();
		}
	}

	bouton.addEventListener('click', function () { basculer(); });

	document.addEventListener('keydown', function (e) {
		if (!ouvert) { return; }

		if (e.key === 'Escape') { basculer(false); return; }

		if (e.key !== 'Tab') { return; }
		var cibles = menu.querySelectorAll('a[href], button:not([disabled]), input');
		if (!cibles.length) { return; }
		/* Le bouton de fermeture vit dans l'en-tête, hors du menu : on
		   l'ajoute au cycle pour qu'il reste atteignable au clavier. */
		var premier = bouton, dernier = cibles[cibles.length - 1];
		if (e.shiftKey && document.activeElement === premier) {
			e.preventDefault(); dernier.focus();
		} else if (!e.shiftKey && document.activeElement === dernier) {
			e.preventDefault(); premier.focus();
		}
	});

	/* Repasser en grand écran doit remettre la page dans un état sain :
	   sinon le défilement reste bloqué alors que le menu a disparu.
	   ATTENTION : ce seuil doit rester identique à celui de theme.css. */
	var grandEcran = window.matchMedia('(min-width: 1200px)');
	var surChangement = function (e) { if (e.matches && ouvert) { basculer(false); } };
	if (grandEcran.addEventListener) { grandEcran.addEventListener('change', surChangement); }
	else if (grandEcran.addListener) { grandEcran.addListener(surChangement); }

	window.addEventListener('resize', mesurerEntete);
	mesurerEntete();
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
