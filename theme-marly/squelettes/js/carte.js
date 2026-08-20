/**
 * La carte interactive, sur la fiche d'une association et la page des lieux.
 * ---------------------------------------------------------------------------
 * Leaflet est hébergé ICI, avec le site : aucune bibliothèque ne vient d'un
 * distributeur tiers. Les tuiles, elles, viennent d'OpenStreetMap au fil de
 * la navigation : c'est ce qui permet de se déplacer et de zoomer librement.
 *
 * Les points viennent du HTML : data-lat/data-lon sur le cadre pour un point
 * seul, ou sur les éléments [data-carte-point] d'une liste pour plusieurs.
 * Sans JavaScript, le cadre reste masqué et la liste porte l'information.
 */
(function () {
	'use strict';

	if (typeof L === 'undefined') { return; }
	var cadre = document.querySelector('.carte-cadre[data-carte]');
	if (!cadre) { return; }

	var points = [];
	if (cadre.getAttribute('data-lat')) {
		points.push({
			lat: parseFloat(cadre.getAttribute('data-lat')),
			lon: parseFloat(cadre.getAttribute('data-lon')),
			nom: cadre.getAttribute('data-nom') || ''
		});
	}
	Array.prototype.forEach.call(document.querySelectorAll('[data-carte-point]'), function (el) {
		points.push({
			lat: parseFloat(el.getAttribute('data-lat')),
			lon: parseFloat(el.getAttribute('data-lon')),
			nom: el.getAttribute('data-nom') || ''
		});
	});
	points = points.filter(function (p) { return isFinite(p.lat) && isFinite(p.lon); });
	if (!points.length) { return; }

	cadre.classList.add('carte-active');

	/* Pas de prefixe « Leaflet », juste le credit du a OpenStreetMap. */
	var carte = L.map(cadre, { attributionControl: false, scrollWheelZoom: false });
	L.control.attribution({ prefix: false }).addTo(carte);
	L.tileLayer('https://tile.openstreetmap.org/{z}/{x}/{y}.png', {
		maxZoom: 19,
		attribution: '© <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a>'
	}).addTo(carte);

	/* Le marqueur aux couleurs de la charte : une goutte dessinee ici meme,
	   aucune image a charger. */
	var epingle = L.divIcon({
		className: 'carte-epingle',
		html: '<svg viewBox="0 0 30 42" width="30" height="42" aria-hidden="true">'
			+ '<path d="M15 0C6.7 0 0 6.7 0 15c0 11 15 27 15 27s15-16 15-27C30 6.7 23.3 0 15 0z" fill="#1E5B41"/>'
			+ '<circle cx="15" cy="15" r="5.5" fill="#FDF8EE"/></svg>',
		iconSize: [30, 42],
		iconAnchor: [15, 42],
		popupAnchor: [0, -40]
	});

	var positions = [];
	points.forEach(function (p) {
		var m = L.marker([p.lat, p.lon], { icon: epingle }).addTo(carte);
		if (p.nom) { m.bindPopup(p.nom); }
		positions.push([p.lat, p.lon]);
	});

	if (positions.length === 1) {
		carte.setView(positions[0], 16);
	} else {
		carte.fitBounds(L.latLngBounds(positions).pad(0.25));
	}
})();
