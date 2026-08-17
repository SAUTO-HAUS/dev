/* ===========================================================================
   B2B pricing tables editor. One Save button per card (commission / delivery /
   eu_params / kr_params). Tier cards can add/remove rows; param cards are fixed
   rows with a toggle + amount. Rows are sent as JSON to fn=save_pricing.
   =========================================================================== */
(function () {
    'use strict';

    var root = document.getElementById('b2ba-pricing');
    if (!root) return;

    var msgBox = document.getElementById('b2ba-msg');
    // Non-empty on /adminsauto/b2b/pricing?user=X or when embedded in the client
    // page tab: writes go to that client's own tables, not the shared global set.
    var pricingUser = root.dataset.b2bPricingUser || '';
    // Embedded = rendered inside the client page "Prețuri" tab (no own header).
    var embedded = root.classList.contains('b2bp-embed');

    // After a per-client save/reset we reload to refresh the badges; keep the
    // Prețuri tab active across that reload when embedded in the client page.
    function reloadKeepingTab() {
        if (embedded) { window.location.hash = 'tab-prices'; }
        window.location.reload();
    }

    function say(text, kind) {
        if (!msgBox) { if (kind === 'error' && text) alert(text); return; }
        msgBox.textContent = text || '';
        msgBox.className = 'b2ba-msg' + (text ? (kind === 'error' ? ' is-error' : ' is-ok') : '');
        if (text) msgBox.scrollIntoView({ block: 'nearest', behavior: 'smooth' });
    }

    function api(fn, data) {
        var body = new URLSearchParams();
        body.set('tp', 'adm');
        body.set('pg', 'b2b');
        body.set('fn', fn);
        Object.keys(data || {}).forEach(function (k) { body.set(k, data[k]); });

        return fetch('/ajax.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/x-www-form-urlencoded; charset=UTF-8' },
            body: body.toString(),
            credentials: 'same-origin'
        }).then(function (r) { return r.json(); })
          .catch(function () { return { ok: false, error: 'Eroare de rețea.' }; });
    }

    // ---- Per-client: flag values differing from the global B2B price --------
    // Keeps the highlight live while the admin edits (server render sets the
    // initial state; a save reloads and re-renders authoritatively).
    function markDiff(input) {
        var cell = input.closest('.b2bp-vcell');
        if (!cell) return;
        var tr = input.closest('tr');
        var ref = input.dataset.ref;
        var differs;
        if (ref === undefined || ref === '') {
            differs = !!pricingUser; // per client: custom; global: nothing to compare
        } else {
            differs = (parseInt(input.value, 10) || 0) !== (parseInt(ref, 10) || 0);
        }
        // Param rows also differ when the on/off toggle differs from global.
        if (input.dataset.refEn !== undefined && input.dataset.refEn !== '') {
            var en = tr && tr.querySelector('.b2bp-en');
            if (en && en.checked !== (input.dataset.refEn === '1')) differs = true;
        }
        cell.classList.toggle('is-diff', differs);
        if (tr) tr.classList.toggle('b2bp-diff', differs);
    }

    // Live highlight on both pages: vs global B2B per client, vs retail globally.
    root.addEventListener('input', function (e) {
        var val = e.target.closest('.b2bp-val');
        if (val) markDiff(val);
    });
    root.addEventListener('change', function (e) {
        var en = e.target.closest('.b2bp-en');
        if (!en) return;
        var tr = en.closest('tr');
        var val = tr && tr.querySelector('.b2bp-val');
        if (val) markDiff(val);
    });

    // The mobile card layout prints each cell's data-label above the field (the
    // <thead> is hidden there). Server-rendered rows carry it; a row added here
    // takes the labels from the card's own header, so no strings live in the JS.
    function labelCells(card, tr) {
        var heads = card.querySelectorAll('thead th');
        Array.prototype.forEach.call(tr.children, function (td, i) {
            var label = heads[i] ? heads[i].textContent.trim() : '';
            if (label) td.setAttribute('data-label', label);
        });
    }

    // ---- Add a tier row (clones the shape of an existing one) ---------------
    root.querySelectorAll('[data-b2b-tier-add]').forEach(function (btn) {
        btn.addEventListener('click', function () {
            var card = btn.closest('.b2bp-card');
            var tbody = card.querySelector('tbody');
            var tr = document.createElement('tr');
            tr.setAttribute('data-row', '');
            tr.setAttribute('data-id', '0'); // 0 = new row, inserted server-side
            tr.innerHTML =
                '<td><input type="number" min="0" step="1" class="b2bp-from" value="0"></td>' +
                '<td><input type="number" min="0" step="1" class="b2bp-to" value=""></td>' +
                '<td class="b2bp-vcell"><input type="number" min="0" step="1" class="b2bp-val" value="0" data-ref=""></td>' +
                '<td class="b2bp-ref"><span class="b2bp-refv">&mdash;</span></td>' + // ref: none for a brand-new band
                '<td><button type="button" class="b2bp-del">&times;</button></td>';
            labelCells(card, tr);
            tbody.appendChild(tr);
            markDiff(tr.querySelector('.b2bp-val')); // new band: differs per client, nothing to compare globally
        });
    });

    // ---- Remove a tier row (event delegation) -------------------------------
    root.addEventListener('click', function (e) {
        var del = e.target.closest('.b2bp-del');
        if (!del) return;
        var tr = del.closest('tr');
        if (tr) tr.remove();
    });

    // ---- Collect + save a card ----------------------------------------------
    function collect(card) {
        var section = card.dataset.section;
        var rows = [];

        card.querySelectorAll('tbody tr[data-row]').forEach(function (tr) {
            var id = parseInt(tr.dataset.id || '0', 10) || 0;

            // Param cards (a fixed list of costs), as opposed to tier cards. Suffix
            // test so a new region's costs card works without touching this again.
            if (section.slice(-7) === '_params') {
                var en = tr.querySelector('.b2bp-en');
                var amt = tr.querySelector('.b2bp-val');
                rows.push({
                    id: id,
                    key: tr.dataset.key || '', // param_key: the server saves by key, not id
                    enabled: en && en.checked ? 1 : 0,
                    amount: parseInt(amt && amt.value, 10) || 0
                });
            } else {
                var from = tr.querySelector('.b2bp-from');
                var to   = tr.querySelector('.b2bp-to');
                var val  = tr.querySelector('.b2bp-val');
                var toVal = (to && to.value !== '') ? (parseInt(to.value, 10) || 0) : null;
                rows.push({
                    id: id,
                    price_from: parseInt(from && from.value, 10) || 0,
                    price_to: toVal,
                    value: parseInt(val && val.value, 10) || 0
                });
            }
        });

        var payload = { section: section, rows: JSON.stringify(rows) };
        if (pricingUser) payload.user_id = pricingUser;
        return payload;
    }

    root.querySelectorAll('[data-b2b-pricing-save]').forEach(function (btn) {
        btn.addEventListener('click', function () {
            var card = btn.closest('.b2bp-card');
            btn.disabled = true;
            api('save_pricing', collect(card)).then(function (res) {
                btn.disabled = false;
                if (res && res.ok) {
                    // Saving a client card turns it "custom"; reload so the badge
                    // and reset button reflect that.
                    if (pricingUser) { reloadKeepingTab(); return; }
                    say(root.dataset.savedMsg || 'Salvat.', 'ok');
                } else {
                    say((res && res.error) || 'Eroare.', 'error');
                }
            });
        });
    });

    // ---- Offer deadline ------------------------------------------------------
    // Scoped like the price cards: with a client id it is their own deadline,
    // without one the general offer. Empty input clears it.
    var offerInput = document.getElementById('b2bp-offer-input');
    var offerLeft  = root.querySelector('.b2bp-offer-left');

    // Same wording the partner sees on the site: the words and their plural forms
    // come from the server (data-d / data-h / data-m), only the rule lives here.
    function pluralIndex(n, lang) {
        if (lang === 'ru') {
            var m10 = n % 10, m100 = n % 100;
            if (m10 === 1 && m100 !== 11) return 0;
            if (m10 >= 2 && m10 <= 4 && (m100 < 12 || m100 > 14)) return 1;
            return 2;
        }
        if (lang === 'en') return n === 1 ? 0 : 1;
        if (n === 1) return 0;
        var r = n % 100;
        return (r >= 1 && r <= 19) ? 1 : 2;
    }

    function humanLeft(seconds, el) {
        var lang = el.dataset.lang || 'ro';
        var n, forms;
        if (seconds >= 86400)   { n = Math.floor(seconds / 86400); forms = (el.dataset.d || '').split('|'); }
        else if (seconds >= 3600) { n = Math.floor(seconds / 3600);  forms = (el.dataset.h || '').split('|'); }
        else                    { n = Math.max(1, Math.floor(seconds / 60)); forms = (el.dataset.m || '').split('|'); }
        return n + ' ' + (forms[pluralIndex(n, lang)] || forms[forms.length - 1] || '');
    }

    function paintLeft() {
        if (!offerLeft) return;
        var end = parseInt(offerLeft.dataset.end || '', 10);
        if (!end) { offerLeft.textContent = offerLeft.dataset.none || ''; return; }
        var left = end - Math.floor(Date.now() / 1000);
        offerLeft.textContent = left <= 0 ? (offerLeft.dataset.expired || '') : humanLeft(left, offerLeft);
        offerLeft.classList.toggle('is-expired', left <= 0);
    }
    paintLeft();
    if (offerLeft) setInterval(paintLeft, 30000);

    // Our Romanian hint only stands in while the field is genuinely empty; once
    // a date is picked the browser's own editor takes the space back.
    if (offerInput) {
        var dtWrap = offerInput.closest('.b2bp-dt');
        var syncPh = function () {
            if (dtWrap) dtWrap.classList.toggle('is-empty', offerInput.value === '');
        };
        offerInput.addEventListener('input', syncPh);
        offerInput.addEventListener('change', syncPh);
        syncPh();
    }

    root.addEventListener('click', function (e) {
        var btn = e.target.closest('[data-b2b-offer-save]');
        if (!btn || !offerInput) return;

        var data = { expires_at: offerInput.value || '' };
        if (pricingUser) data.user_id = pricingUser;

        btn.disabled = true;
        api('save_offer_expiry', data).then(function (res) {
            btn.disabled = false;
            if (res && res.ok) {
                // Reload so the badge, the countdown and the reference column all
                // come back from the server rather than being patched here.
                reloadKeepingTab();
                return;
            }
            say((res && res.error) || 'Eroare.', 'error');
        });
    });

    // ---- Reset a client card back to the global table -----------------------
    root.addEventListener('click', function (e) {
        var btn = e.target.closest('[data-b2b-pricing-reset]');
        if (!btn || !pricingUser) return;
        if (!window.confirm(btn.dataset.confirm || root.dataset.resetMsg || 'Revenire la global?')) return;

        btn.disabled = true;
        api('reset_pricing', { section: btn.dataset.section, user_id: pricingUser }).then(function (res) {
            if (res && res.ok) { reloadKeepingTab(); return; }
            btn.disabled = false;
            say((res && res.error) || 'Eroare.', 'error');
        });
    });
})();
