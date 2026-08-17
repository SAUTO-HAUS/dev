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

    // ------------------------------------------------------------------- gifts
    // One dialog shared by every row of the client list; the row button says
    // which client it is for.

    var giftBox = document.getElementById('b2ba-gift');
    if (giftBox) {
        var giftUser = 0;
        var giftList = document.getElementById('b2ba-gift-list');
        // Set once anything is granted or withdrawn: closing then reloads, so the
        // per-row counters in the table match reality.
        var giftDirty = false;

        function esc(s) {
            return String(s == null ? '' : s).replace(/[&<>"']/g, function (c) {
                return { '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' }[c];
            });
        }

        /** Existing gifts of the open client; withdrawn ones stay, struck through. */
        function giftRender(rows) {
            if (!giftList) return;
            if (!rows || !rows.length) {
                giftList.innerHTML = '<p class="b2ba-hint">' + esc(giftList.dataset.none) + '</p>';
                return;
            }

            var html = '<h3 class="b2bg-existing__ttl">' + esc(giftList.dataset.title) + '</h3><ul class="b2bg-log">';
            rows.forEach(function (g) {
                // Mirrors the profile-page markup in views/user_detail.php: a gift
                // may be nothing but a note, and one badge says where it stands.
                var title = g.what || g.note || '—';
                var showNote = !!(g.what && g.note);
                var mod = g.revoked ? 'revoked' : (g.seen ? 'seen' : 'new');
                var stLabel = g.revoked ? giftList.dataset.stRevoked
                            : (g.seen ? giftList.dataset.stSeen : giftList.dataset.stNew);

                html += '<li class="b2bg-log__item' + (g.revoked ? ' is-revoked' : '') + '">'
                      + '<div class="b2bg-log__main">'
                      + '<div class="b2bg-log__top"><strong>' + esc(title) + '</strong>'
                      + '<span class="b2bg-tag b2bg-tag--' + mod + '">' + esc(stLabel) + '</span></div>'
                      + (showNote ? '<span class="b2bg-log__note">' + esc(g.note) + '</span>' : '')
                      + '<span class="b2bg-log__meta">' + esc(g.date) + '</span>'
                      + '</div>'
                      + (g.revoked ? '' :
                          '<button type="button" class="b2ba-btn b2ba-btn--soft b2ba-btn--sm"'
                        + ' data-b2b-gift-revoke data-gift="' + (parseInt(g.id, 10) || 0) + '">'
                        + esc(giftList.dataset.revoke) + '</button>')
                      + '</li>';
            });
            giftList.innerHTML = html + '</ul>';
        }

        function giftLoad() {
            if (!giftList || !giftUser) return;
            giftList.innerHTML = '';
            api('list_gifts', { user_id: giftUser }).then(function (res) {
                if (res && res.ok) giftRender(res.gifts);
            });
        }

        function giftOpen(id, name) {
            giftUser = id;
            giftBox.querySelector('[data-b2b-gift-name]').textContent = name || '';
            giftBox.querySelectorAll('.b2bg-item').forEach(function (c) { c.checked = false; });
            var note = document.getElementById('b2ba-gift-note');
            if (note) note.value = '';
            giftBox.hidden = false;
            document.documentElement.classList.add('b2ba-noscroll');
            giftLoad();
        }

        function giftClose() {
            giftBox.hidden = true;
            giftUser = 0;
            document.documentElement.classList.remove('b2ba-noscroll');
            if (giftDirty) window.location.reload();
        }

        root.addEventListener('click', function (e) {
            var open = e.target.closest('[data-b2b-gift-open]');
            if (!open) return;
            // The row itself is clickable (opens the profile); this must not.
            e.preventDefault();
            e.stopPropagation();
            giftOpen(parseInt(open.dataset.user, 10) || 0, open.dataset.name);
        });

        giftBox.addEventListener('click', function (e) {
            if (e.target.closest('[data-b2b-gift-close]')) { giftClose(); return; }

            // Withdraw, from inside the dialog. Handled here rather than by the
            // profile-page handler below so the dialog stays open and just
            // refreshes its list.
            var rev = e.target.closest('[data-b2b-gift-revoke]');
            if (rev) {
                if (giftList && giftList.dataset.confirm && !window.confirm(giftList.dataset.confirm)) return;
                busy(rev, true);
                api('revoke_gift', { gift_id: rev.dataset.gift }).then(function (res) {
                    busy(rev, false);
                    if (!res.ok) { alert(res.error || 'Eroare.'); return; }
                    giftDirty = true;
                    giftLoad();
                });
                return;
            }

            var send = e.target.closest('[data-b2b-gift-send]');
            if (!send || !giftUser) return;

            var items = [];
            giftBox.querySelectorAll('.b2bg-item:checked').forEach(function (c) { items.push(c.value); });
            var noteEl = document.getElementById('b2ba-gift-note');

            busy(send, true);
            api('send_gift', {
                user_id: giftUser,
                items: items,
                note: noteEl ? noteEl.value : ''
            }).then(function (res) {
                busy(send, false);
                if (!res.ok) { alert(res.error || 'Eroare.'); return; }
                // Stay open and show it in the list, so several gifts can be
                // granted (or one undone) without reopening the dialog.
                giftDirty = true;
                giftBox.querySelectorAll('.b2bg-item').forEach(function (c) { c.checked = false; });
                if (noteEl) noteEl.value = '';
                giftLoad();
            });
        });

        document.addEventListener('keydown', function (e) {
            if (e.key === 'Escape' && !giftBox.hidden) giftClose();
        });
    }

    // Withdraw a gift, from the client profile. The dialog lives inside .b2ba too,
    // so its own buttons must be skipped here or every click would fire twice.
    root.addEventListener('click', function (e) {
        var rev = e.target.closest('[data-b2b-gift-revoke]');
        if (!rev || rev.closest('#b2ba-gift')) return;
        if (rev.dataset.confirm && !window.confirm(rev.dataset.confirm)) return;

        busy(rev, true);
        api('revoke_gift', { gift_id: rev.dataset.gift }).then(function (res) {
            busy(rev, false);
            if (!res.ok) { say(res.error, 'error'); return; }
            window.location.reload();
        });
    });

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

    // ---- switches that save themselves ---------------------------------------
    // A checkbox carrying data-toggle-setting writes its key straight away: it is a
    // single decision, and a Save button next to one switch invites leaving the page
    // believing it took effect. Reverted visually if the write fails.
    root.addEventListener('change', function (e) {
        var sw = e.target.closest ? e.target.closest('[data-toggle-setting]') : null;
        if (!sw) return;

        var key = sw.dataset.toggleSetting;
        var on = sw.checked;
        var payload = {};
        payload['settings[' + key + ']'] = on ? '1' : '0';

        sw.disabled = true;
        api('save_settings', payload).then(function (res) {
            sw.disabled = false;
            if (!res.ok) {
                sw.checked = !on;
                say(res.error, 'error');
                return;
            }
            say(on ? (sw.dataset.msgOn || '') : (sw.dataset.msgOff || ''), 'ok');
        });
    });
})();
