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
                '<td class="b2bp-ref">&mdash;</td>' + // ref: none for a brand-new band
                '<td><button type="button" class="b2bp-del">&times;</button></td>';
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

            if (section === 'eu_params' || section === 'kr_params') {
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
