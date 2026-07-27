/* ===========================================================================
   B2B Management: admin panel interactions.
   Requests go to /ajax.php with tp=adm&pg=b2b; the admin session is validated
   there and the `gordon` role is re-checked in ajax/b2b/ajax.php.
   =========================================================================== */
(function () {
    'use strict';

    var root = document.querySelector('.b2ba');
    if (!root) return;

    var msgBox = document.getElementById('b2ba-msg');
    var userId = root.dataset.b2bUser || '';

    function say(text, kind) {
        if (!msgBox) { if (kind === 'error') alert(text); return; }
        msgBox.textContent = text || '';
        msgBox.className = 'b2ba-msg' + (text ? (kind === 'error' ? ' is-error' : ' is-ok') : '');
        if (text) msgBox.scrollIntoView({ block: 'nearest', behavior: 'smooth' });
    }

    /** POST to the admin B2B module. The response is always JSON. */
    function api(fn, data) {
        var body = new URLSearchParams();
        body.set('tp', 'adm');
        body.set('pg', 'b2b');
        body.set('fn', fn);

        Object.keys(data || {}).forEach(function (k) {
            var v = data[k];
            if (Array.isArray(v)) {
                // PHP accepts `regions` as a list or a comma-separated string.
                body.set(k, v.join(','));
            } else if (v !== null && v !== undefined) {
                body.set(k, v);
            }
        });

        return fetch('/ajax.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/x-www-form-urlencoded; charset=UTF-8' },
            body: body.toString(),
            credentials: 'same-origin'
        })
            .then(function (r) { return r.json(); })
            .catch(function () { return { ok: false, error: 'Eroare de rețea.' }; });
    }

    function busy(btn, on) {
        if (!btn) return;
        btn.disabled = on;
        btn.style.opacity = on ? '0.6' : '';
    }

    // -------------------------------------------------------------------- tabs

    root.querySelectorAll('[data-b2b-tab]').forEach(function (tab) {
        tab.addEventListener('click', function () {
            var name = tab.dataset.b2bTab;
            root.querySelectorAll('[data-b2b-tab]').forEach(function (x) { x.classList.toggle('is-active', x === tab); });
            root.querySelectorAll('[data-b2b-pane]').forEach(function (p) {
                p.classList.toggle('is-active', p.dataset.b2bPane === name);
            });
        });
    });

    // Re-open a tab named in the URL hash (#tab-prices), e.g. after the embedded
    // pricing editor saves and reloads the page.
    (function () {
        var m = (window.location.hash || '').match(/^#tab-(.+)$/);
        if (!m) return;
        var tab = root.querySelector('[data-b2b-tab="' + m[1] + '"]');
        if (tab) tab.click();
    })();

    // ------------------------------------------------------ users list actions

    // The whole client row opens the profile. Clicks on the action buttons (or the
    // login link) are left to their own handlers, so only "empty" row area navigates.
    root.addEventListener('click', function (e) {
        if (e.target.closest('a, button, input, select, label')) return;
        var row = e.target.closest('[data-b2b-open]');
        if (row && row.dataset.b2bOpen) window.location.href = row.dataset.b2bOpen;
    });

    // Accept / block straight from the clients table. Unlike the profile buttons
    // above, the user id comes from the row's button (many users on this page).
    root.addEventListener('click', function (e) {
        var btn = e.target.closest('[data-b2b-list-status]');
        if (!btn) return;
        if (btn.dataset.confirm && !window.confirm(btn.dataset.confirm)) return;

        busy(btn, true);
        api('set_status', { user_id: btn.dataset.user, status: btn.dataset.value }).then(function (res) {
            if (res && res.ok) {
                window.location.reload();
            } else {
                busy(btn, false);
                say((res && res.error) || 'Eroare.', 'error');
            }
        });
    });

    // Correct a mistyped person type (individual <-> company) inline. Reuses the
    // save_profile handler (it accepts person_type); saves on change, no reload.
    root.addEventListener('change', function (e) {
        var sel = e.target.closest('[data-b2b-list-ptype]');
        if (!sel) return;
        sel.disabled = true;
        api('save_profile', { user_id: sel.dataset.user, person_type: sel.value }).then(function (res) {
            sel.disabled = false;
            if (res && res.ok) {
                sel.dataset.prev = sel.value;
                sel.classList.remove('is-saved'); void sel.offsetWidth; sel.classList.add('is-saved');
            } else {
                if (sel.dataset.prev) sel.value = sel.dataset.prev;
                say((res && res.error) || 'Eroare.', 'error');
            }
        });
    });

    // ----------------------------------------------------------------- actions

    root.addEventListener('click', function (e) {
        var btn = e.target.closest('[data-b2b-admin]');
        if (!btn) return;

        var action = btn.dataset.b2bAdmin;

        if (btn.dataset.confirm && !window.confirm(btn.dataset.confirm)) return;

        // ---- account status -------------------------------------------------
        if (action === 'status') {
            busy(btn, true);
            api('set_status', { user_id: userId, status: btn.dataset.value }).then(function (res) {
                busy(btn, false);
                if (!res.ok) { say(res.error, 'error'); return; }
                // The status changes which buttons apply, so reload.
                window.location.reload();
            });
            return;
        }

        // ---- delete account -------------------------------------------------
        if (action === 'delete') {
            busy(btn, true);
            api('delete_user', { user_id: userId }).then(function (res) {
                busy(btn, false);
                if (!res.ok) { say(res.error, 'error'); return; }
                window.location.href = btn.dataset.redirect || '/';
            });
            return;
        }

        // ---- region permissions ---------------------------------------------
        if (action === 'save-perms') {
            var regions = [];
            root.querySelectorAll('.b2ba-region:checked').forEach(function (cb) { regions.push(cb.value); });

            var inStock = root.querySelector('#b2ba-allow-in-stock');
            var onOrder = root.querySelector('#b2ba-allow-on-order');

            busy(btn, true);
            api('set_permissions', {
                user_id: userId,
                regions: regions,
                allow_in_stock: inStock && inStock.checked ? 1 : 0,
                allow_on_order: onOrder && onOrder.checked ? 1 : 0
            }).then(function (res) {
                busy(btn, false);
                say(res.ok ? (root.dataset.savedMsg || 'Salvat.') : res.error, res.ok ? 'ok' : 'error');
            });
            return;
        }

        // ---- client legal details -------------------------------------------
        if (action === 'save-profile') {
            var payload = { user_id: userId };
            root.querySelectorAll('[data-field]').forEach(function (input) {
                payload[input.dataset.field] = input.value;
            });

            busy(btn, true);
            api('save_profile', payload).then(function (res) {
                busy(btn, false);
                say(res.ok ? (root.dataset.savedMsg || 'Salvat.') : res.error, res.ok ? 'ok' : 'error');
            });
            return;
        }

        // ---- new password ---------------------------------------------------
        if (action === 'reset-password') {
            var input = document.getElementById('b2ba-new-pass');
            if (!input || input.value.length < 6) {
                say('Parola trebuie să aibă minimum 6 caractere.', 'error');
                return;
            }

            busy(btn, true);
            api('reset_password', { user_id: userId, password: input.value }).then(function (res) {
                busy(btn, false);
                if (!res.ok) { say(res.error, 'error'); return; }
                input.value = '';
                say(root.dataset.savedMsg || 'Salvat.', 'ok');
            });
            return;
        }

        // ---- request status -------------------------------------------------
        if (action === 'request-status') {
            var rid = btn.dataset.request;
            busy(btn, true);
            api('set_request_status', { request_id: rid, status: btn.dataset.value }).then(function (res) {
                busy(btn, false);
                if (!res.ok) { say(res.error, 'error'); return; }
                window.location.reload();
            });
            return;
        }

        // ---- module settings ------------------------------------------------
        if (action === 'save-settings') {
            var settings = {};
            document.querySelectorAll('[data-setting]').forEach(function (input) {
                settings['settings[' + input.dataset.setting + ']'] = input.value;
            });

            busy(btn, true);
            api('save_settings', settings).then(function (res) {
                busy(btn, false);
                say(res.ok ? (root.dataset.savedMsg || 'Salvat.') : res.error, res.ok ? 'ok' : 'error');
            });
        }
    });
})();
