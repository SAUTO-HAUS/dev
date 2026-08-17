/**
 * Cookie consent engine (Legea 195/2024).
 *
 * Nothing that tracks the visitor is in the page HTML — Google Analytics, GTM,
 * Google Ads, Facebook Pixel and Yandex Metrika are all injected from here, and
 * only after the matching category has been granted. Default state is "denied",
 * so a visitor who never answers the banner is never tracked.
 *
 * State lives in localStorage (source of truth) plus a `cookie_consent` cookie
 * so server-side code can read the decision without JS.
 */
(function (w, d) {
	'use strict';

	var STORE_KEY   = 'sauto_consent';
	var COOKIE_NAME = 'cookie_consent';
	var ID_KEY      = 'sauto_consent_id';
	var POLICY_VER  = 'v1.0';
	var MAX_AGE     = 365 * 24 * 3600; // consent expires after one year
	var API_URL     = '/api/v1/gdpr/consent';

	var cfg = w.SAUTO_CONSENT_CFG || {};

	// --- tracker ids -------------------------------------------------------
	var GA_ID     = 'G-TP4GJ51GSL';
	var ADS_ID    = 'AW-964347386';
	var GTM_ID    = 'GTM-KRRLB4X';
	var FB_PIXEL  = '701415057290990';
	var YM_ID     = 100579107;
	var ADW_CONV  = 865017510;

	// --- storage helpers ---------------------------------------------------

	function readState() {
		try {
			var raw = localStorage.getItem(STORE_KEY);
			if (!raw) return null;
			var o = JSON.parse(raw);
			if (!o || typeof o !== 'object') return null;
			if (o.policy_version !== POLICY_VER) return null; // policy changed -> ask again
			if (!o.ts || (nowSec() - o.ts) > MAX_AGE) return null;
			return o;
		} catch (e) { return null; }
	}

	function writeState(o) {
		try { localStorage.setItem(STORE_KEY, JSON.stringify(o)); } catch (e) {}
		setCookie(COOKIE_NAME, o.status, MAX_AGE);
	}

	function clearState() {
		try { localStorage.removeItem(STORE_KEY); } catch (e) {}
		setCookie(COOKIE_NAME, '', -1);
	}

	function nowSec() { return Math.floor(Date.now() / 1000); }

	function setCookie(name, value, maxAge) {
		var s = name + '=' + encodeURIComponent(value) + ';path=/;max-age=' + maxAge + ';SameSite=Lax';
		if (location.protocol === 'https:') s += ';Secure';
		d.cookie = s;
	}

	// Anonymous, non-identifying trace id. It is the ONLY thing tying a logged
	// decision back to a visitor — no IP, no user agent (see Cap. 3 of the spec).
	function consentId() {
		var id = null;
		try { id = localStorage.getItem(ID_KEY); } catch (e) {}
		if (id) return id;
		id = uuid();
		try { localStorage.setItem(ID_KEY, id); } catch (e) {}
		return id;
	}

	function uuid() {
		if (w.crypto && w.crypto.randomUUID) { return w.crypto.randomUUID(); }
		var buf = new Uint8Array(16);
		if (w.crypto && w.crypto.getRandomValues) { w.crypto.getRandomValues(buf); }
		else { for (var i = 0; i < 16; i++) { buf[i] = Math.floor(Math.random() * 256); } }
		buf[6] = (buf[6] & 0x0f) | 0x40;
		buf[8] = (buf[8] & 0x3f) | 0x80;
		var hex = [];
		for (var j = 0; j < 16; j++) { hex.push((buf[j] + 0x100).toString(16).substr(1)); }
		return hex.slice(0, 4).join('') + '-' + hex.slice(4, 6).join('') + '-' + hex.slice(6, 8).join('')
			+ '-' + hex.slice(8, 10).join('') + '-' + hex.slice(10, 16).join('');
	}

	// --- script injection --------------------------------------------------

	function injectScript(src, attrs) {
		var s = d.createElement('script');
		s.async = true;
		s.src = src;
		s.setAttribute('data-cfasync', 'false');
		if (attrs) { for (var k in attrs) { if (attrs.hasOwnProperty(k)) s.setAttribute(k, attrs[k]); } }
		var f = d.getElementsByTagName('script')[0];
		f.parentNode.insertBefore(s, f);
		return s;
	}

	var loaded = { gtag: false, gtm: false, fb: false, ym: false, adw: false };

	function loadGtag() {
		if (loaded.gtag) return;
		loaded.gtag = true;
		injectScript('https://www.googletagmanager.com/gtag/js?id=' + GA_ID);
		w.gtag('js', new Date());
		// The privacy policy states analytics cookies last 90 days. Google's
		// default for _ga is 2 years, so it is set explicitly here — otherwise
		// the published policy would be a false statement.
		w.gtag('config', GA_ID, { cookie_expires: 90 * 24 * 60 * 60 });
		w.gtag('config', ADS_ID);
	}

	function loadGtm() {
		if (loaded.gtm) return;
		loaded.gtm = true;
		w.dataLayer.push({ 'gtm.start': new Date().getTime(), event: 'gtm.js' });
		injectScript('https://www.googletagmanager.com/gtm.js?id=' + GTM_ID);
	}

	function loadFbPixel() {
		if (loaded.fb) return;
		loaded.fb = true;
		/* eslint-disable */
		!function (f, b, e, v, n, t, s) {
			if (f.fbq) return; n = f.fbq = function () { n.callMethod ? n.callMethod.apply(n, arguments) : n.queue.push(arguments) };
			if (!f._fbq) f._fbq = n; n.push = n; n.loaded = !0; n.version = '2.0'; n.queue = [];
			t = b.createElement(e); t.async = !0; t.src = v; s = b.getElementsByTagName(e)[0]; s.parentNode.insertBefore(t, s)
		}(w, d, 'script', 'https://connect.facebook.net/en_US/fbevents.js');
		/* eslint-enable */
		w.fbq('init', FB_PIXEL);
		w.fbq('track', 'PageView');
	}

	function loadYandexMetrika() {
		if (loaded.ym) return;
		loaded.ym = true;
		w.ym = w.ym || function () { (w.ym.a = w.ym.a || []).push(arguments); };
		w.ym.l = 1 * new Date();
		injectScript('https://mc.yandex.ru/metrika/tag.js');
		w.ym(YM_ID, 'init', { clickmap: true, trackLinks: true, accurateTrackBounce: true, webvisor: false });
	}

	function loadAdwordsRemarketing() {
		if (loaded.adw) return;
		loaded.adw = true;
		w.google_tag_params = w.SAUTO_ADW_PARAMS || {};
		w.google_conversion_id = ADW_CONV;
		w.google_custom_params = w.google_tag_params;
		w.google_remarketing_only = true;
		injectScript('https://www.googleadservices.com/pagead/conversion.js');
	}

	// --- deferred third-party events --------------------------------------

	// Page code emits conversions/ViewContent through onMarketing/onAnalytics.
	// Callbacks queued before a decision run the moment the category is granted,
	// and are dropped entirely if it is refused — so a "reject" visitor never
	// reaches Meta or Google, while an "accept" visitor loses no event to timing.
	var applied = { analytics: false, marketing: false };
	var queued  = { analytics: [], marketing: [] };

	function runCb(fn) { try { fn(); } catch (e) {} }

	function flush(kind) {
		var list = queued[kind];
		queued[kind] = [];
		for (var i = 0; i < list.length; i++) { runCb(list[i]); }
	}

	// --- consent application ----------------------------------------------

	function applyConsent(state, isFreshChoice) {
		var analytics = !!state.analytics;
		var marketing = !!state.marketing;

		// Consent Mode v2 signals — pushed even when nothing is loaded, so that a
		// later "accept" upgrades a container that is already running.
		w.gtag('consent', 'update', {
			'ad_storage':          marketing ? 'granted' : 'denied',
			'ad_user_data':        marketing ? 'granted' : 'denied',
			'ad_personalization':  marketing ? 'granted' : 'denied',
			'analytics_storage':   analytics ? 'granted' : 'denied'
		});

		if (analytics) {
			loadGtag();
			loadYandexMetrika();
			if (isFreshChoice) {
				w.gtag('event', 'page_view', { page_title: d.title, page_location: w.location.href });
			}
		}
		if (marketing) {
			loadFbPixel();
			loadAdwordsRemarketing();
		}
		// GTM is a container for both families; load it as soon as either is allowed.
		if (analytics || marketing) { loadGtm(); }

		applied.analytics = analytics;
		applied.marketing = marketing;
		if (analytics) { flush('analytics'); } else { queued.analytics = []; }
		if (marketing) { flush('marketing'); } else { queued.marketing = []; }
	}

	// --- public state API --------------------------------------------------

	var api = {};

	api.get = function () {
		var s = readState();
		return {
			decided:   !!s,
			status:    s ? s.status : 'pending',
			analytics: s ? !!s.analytics : false,
			marketing: s ? !!s.marketing : false
		};
	};

	api.hasAnalytics = function () { return api.get().analytics; };
	api.hasMarketing = function () { return api.get().marketing; };

	/** Run fn once marketing tags (Facebook Pixel, Google Ads) are allowed. */
	api.onMarketing = function (fn) {
		if (applied.marketing) { runCb(fn); } else { queued.marketing.push(fn); }
	};

	/** Run fn once analytics tags (GA4, Yandex Metrika) are allowed. */
	api.onAnalytics = function (fn) {
		if (applied.analytics) { runCb(fn); } else { queued.analytics.push(fn); }
	};

	/**
	 * Persist a decision, apply it, log it, and close the UI.
	 * @param {boolean} analytics
	 * @param {boolean} marketing
	 * @param {string}  status  accept_all | reject_all | custom_selection
	 */
	api.save = function (analytics, marketing, status) {
		analytics = !!analytics;
		marketing = !!marketing;
		if (!status) {
			status = (analytics && marketing) ? 'accept_all' : ((!analytics && !marketing) ? 'reject_all' : 'custom_selection');
		}
		var state = {
			status: status,
			analytics: analytics,
			marketing: marketing,
			policy_version: POLICY_VER,
			ts: nowSec()
		};
		writeState(state);
		applyConsent(state, true);
		logConsent(state);
		hideUI();
		d.dispatchEvent(new CustomEvent('sauto:consent', { detail: api.get() }));
	};

	api.acceptAll = function () { api.save(true, true, 'accept_all'); };
	api.rejectAll = function () { api.save(false, false, 'reject_all'); };

	/**
	 * Re-open the consent UI so a visitor can change or withdraw their choice.
	 * Shows the banner (so plain Refuz/Accept stay one click away) and the
	 * granular panel on top, since this is reached from an explicit
	 * "cookie settings" link. Wired to window.openCookieManager().
	 */
	api.openManager = function () {
		showBanner();
		openPrefs();
	};

	/** Withdraw everything in one click (used by the policy page shortcut). */
	api.revokeAll = function () { api.rejectAll(); };

	// --- consent journal (Cap. 3) -----------------------------------------

	function logConsent(state) {
		if (!w.fetch) return;
		try {
			fetch(API_URL, {
				method: 'POST',
				headers: { 'Content-Type': 'application/json' },
				body: JSON.stringify({
					consent_id: consentId(),
					global_status: state.status,
					trackers: { analytics: state.analytics, marketing: state.marketing },
					policy_version: POLICY_VER
				}),
				keepalive: true
			}).catch(function () {});
		} catch (e) {}
	}

	// --- banner / modal wiring --------------------------------------------

	function showBanner() {
		var b = d.getElementById('cons_bx');
		if (b) { b.style.display = 'flex'; }
	}

	function hideUI() {
		var b = d.getElementById('cons_bx');
		var p = d.getElementById('cons_pref_bx');
		if (b) b.style.display = 'none';
		if (p) p.classList.remove('is-open');
	}

	function openPrefs() {
		var st = api.get();
		var a = d.getElementById('cons_ck_analytics');
		var m = d.getElementById('cons_ck_marketing');
		if (a) a.checked = st.analytics;
		if (m) m.checked = st.marketing;
		var p = d.getElementById('cons_pref_bx');
		if (p) p.classList.add('is-open');
	}

	function closePrefs() {
		var p = d.getElementById('cons_pref_bx');
		if (p) p.classList.remove('is-open');
		// Someone who already has a decision on file only came here to look:
		// drop the banner too. A visitor who has not decided yet keeps seeing it
		// — the banner is not dismissible without an answer.
		if (readState()) {
			var b = d.getElementById('cons_bx');
			if (b) b.style.display = 'none';
		}
	}

	function bindUI() {
		d.addEventListener('click', function (e) {
			var el = e.target.closest ? e.target.closest('[data-consent-action]') : null;
			if (!el) return;
			e.preventDefault();
			switch (el.getAttribute('data-consent-action')) {
				case 'accept':    api.acceptAll(); break;
				case 'reject':    api.rejectAll(); break;
				case 'settings':  openPrefs(); break;
				case 'close-settings': closePrefs(); break;
				case 'save':
					var a = d.getElementById('cons_ck_analytics');
					var m = d.getElementById('cons_ck_marketing');
					api.save(a && a.checked, m && m.checked, null);
					break;
				case 'manage':    api.openManager(); break;
			}
		});
	}

	// --- boot --------------------------------------------------------------

	// Consent Mode default: everything denied, declared before any Google tag can
	// possibly run. This must stay the first thing pushed into the dataLayer.
	w.dataLayer = w.dataLayer || [];
	if (typeof w.gtag !== 'function') {
		w.gtag = function () { w.dataLayer.push(arguments); };
	}
	w.gtag('consent', 'default', {
		'ad_storage': 'denied',
		'ad_user_data': 'denied',
		'ad_personalization': 'denied',
		'analytics_storage': 'denied',
		'functionality_storage': 'granted',
		'security_storage': 'granted'
	});

	w.SautoConsent = api;
	// Named in the policy page markup required by the spec.
	w.openCookieManager = function () { api.openManager(); };

	function init() {
		bindUI();
		// Consent collected by the pre-2026 banner cannot be relied on (it had no
		// real refuse option), so its keys are dropped and everyone is asked again.
		try {
			localStorage.removeItem('z_cks_alwd');
			localStorage.removeItem('z_cks_alwd_t');
			localStorage.removeItem('z_cks_alwd_v');
		} catch (e) {}

		var st = readState();
		if (st) { applyConsent(st, false); }
		else { clearState(); showBanner(); }
	}

	if (d.readyState === 'loading') { d.addEventListener('DOMContentLoaded', init); }
	else { init(); }

})(window, document);
