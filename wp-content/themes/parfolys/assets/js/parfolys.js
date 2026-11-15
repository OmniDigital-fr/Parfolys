/* PARFOLYS : menu mobile accessible et pastilles de format pour les variantes. */
(function () {
	'use strict';

	var i18n = (window.PARFOLYS && window.PARFOLYS.i18n) || {};

	/* --- Menu mobile -------------------------------------------------------- */
	var burger = document.querySelector('.pf-burger');
	var drawer = document.getElementById('pf-drawer');
	if (burger && drawer) {
		var panel = drawer.querySelector('.pf-drawer__panel');
		var closeBtn = drawer.querySelector('.pf-drawer__close');
		var lastFocus = null;

		var focusables = function () {
			return Array.prototype.slice.call(panel.querySelectorAll('a[href], button:not([disabled])'));
		};
		var open = function () {
			lastFocus = document.activeElement;
			drawer.hidden = false;
			document.body.classList.add('pf-drawer-open');
			burger.setAttribute('aria-expanded', 'true');
			var f = focusables();
			if (f.length) { f[0].focus(); }
		};
		var close = function () {
			drawer.hidden = true;
			document.body.classList.remove('pf-drawer-open');
			burger.setAttribute('aria-expanded', 'false');
			if (lastFocus) { lastFocus.focus(); }
		};
		burger.addEventListener('click', open);
		closeBtn.addEventListener('click', close);
		drawer.addEventListener('click', function (e) { if (e.target === drawer) { close(); } });
		document.addEventListener('keydown', function (e) {
			if (drawer.hidden) { return; }
			if (e.key === 'Escape') { close(); }
			if (e.key === 'Tab') {
				var f = focusables();
				if (!f.length) { return; }
				var first = f[0], last = f[f.length - 1];
				if (e.shiftKey && document.activeElement === first) { e.preventDefault(); last.focus(); }
				else if (!e.shiftKey && document.activeElement === last) { e.preventDefault(); first.focus(); }
			}
		});
		window.addEventListener('resize', function () { if (window.innerWidth > 921 && !drawer.hidden) { close(); } });
	}

	/* --- Panneau de filtres (ouvert en desktop, repliable en mobile) ------ */
	var panel = document.querySelector('[data-pf-filters]');
	if (panel) {
		var mq = window.matchMedia('(min-width: 769px)');
		var sync = function () { if (mq.matches) { panel.setAttribute('open', ''); } };
		sync();
		if (mq.addEventListener) { mq.addEventListener('change', sync); }
		var closeF = panel.querySelector('.pf-filters__close');
		if (closeF) {
			closeF.addEventListener('click', function () {
				panel.removeAttribute('open');
				var target = document.querySelector('ul.products, .pf-empty--archive, .woocommerce-result-count');
				if (target) { target.setAttribute('tabindex', '-1'); target.focus(); }
			});
		}
	}

	/* --- Pastilles de variantes -------------------------------------------- */
	var buildSwatches = function (form) {
		var selects = form.querySelectorAll('table.variations select');
		Array.prototype.forEach.call(selects, function (select) {
			if (select.dataset.pfSwatches) { return; }
			select.dataset.pfSwatches = '1';
			var cell = select.closest('td') || select.parentNode;
			cell.classList.add('pf-has-swatches');
			var labelEl = form.querySelector('label[for="' + select.id + '"]');

			var group = document.createElement('div');
			group.className = 'pf-swatches';
			group.setAttribute('role', 'radiogroup');
			if (labelEl) {
				labelEl.id = labelEl.id || ('pf-lbl-' + select.id);
				group.setAttribute('aria-labelledby', labelEl.id);
			}
			select.setAttribute('tabindex', '-1');
			select.setAttribute('aria-hidden', 'true');
			cell.insertBefore(group, select);

			var variations = [];
			try { variations = JSON.parse(form.getAttribute('data-product_variations') || '[]') || []; } catch (e) { variations = []; }
			var attrName = select.getAttribute('data-attribute_name') || select.name;
			var isOut = function (value) {
				if (!Array.isArray(variations) || !variations.length) { return false; }
				var matching = variations.filter(function (v) { var a = v.attributes[attrName]; return a === value || a === ''; });
				return matching.length > 0 && matching.every(function (v) { return !v.is_in_stock; });
			};

			var render = function () {
				group.innerHTML = '';
				var opts = Array.prototype.filter.call(select.options, function (o) { return o.value !== ''; });
				opts.forEach(function (o, idx) {
					var b = document.createElement('button');
					b.type = 'button';
					b.className = 'pf-swatch';
					b.textContent = o.text;
					b.setAttribute('role', 'radio');
					var checked = select.value === o.value;
					b.setAttribute('aria-checked', checked ? 'true' : 'false');
					b.tabIndex = checked || (!select.value && idx === 0) ? 0 : -1;
					if (o.disabled) { b.disabled = true; }
					if (isOut(o.value)) {
						b.classList.add('pf-swatch--oos');
						b.setAttribute('aria-label', o.text + ', ' + (i18n.outOfStock || 'en rupture de stock'));
					}
					b.addEventListener('click', function () {
						select.value = (select.value === o.value) ? '' : o.value;
						select.dispatchEvent(new Event('change', { bubbles: true }));
						if (window.jQuery) { window.jQuery(select).trigger('change'); }
						render();
						var again = group.querySelector('[aria-checked="true"]') || group.querySelector('.pf-swatch');
						if (again) { again.focus(); }
					});
					b.addEventListener('keydown', function (e) {
						var keys = ['ArrowRight', 'ArrowDown', 'ArrowLeft', 'ArrowUp'];
						if (keys.indexOf(e.key) === -1) { return; }
						e.preventDefault();
						var all = Array.prototype.filter.call(group.querySelectorAll('.pf-swatch'), function (x) { return !x.disabled; });
						var i = all.indexOf(b);
						var n = (e.key === 'ArrowRight' || e.key === 'ArrowDown') ? (i + 1) % all.length : (i - 1 + all.length) % all.length;
						all[n].click();
					});
					group.appendChild(b);
				});
			};
			render();
			select.addEventListener('change', render);
			if (window.jQuery) {
				window.jQuery(form).on('woocommerce_update_variation_values reset_data', function () { setTimeout(render, 0); });
			}
		});
	};

	var initSwatches = function () {
		Array.prototype.forEach.call(document.querySelectorAll('form.variations_form'), buildSwatches);
	};
	if (document.readyState === 'loading') {
		document.addEventListener('DOMContentLoaded', initSwatches);
	} else {
		initSwatches();
	}
	if (window.jQuery) {
		window.jQuery(document.body).on('wc_variation_form', function (e) { if (e.target) { buildSwatches(e.target); } });
	}
})();
