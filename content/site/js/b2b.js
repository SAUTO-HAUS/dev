/* ===========================================================================
   B2B module: registration, login and the car-page actions
   (proforma, send to Super Admin, save).

   Every request goes to POST /ajax.php with tp=ste, the site's existing AJAX
   flow. No external dependencies: the public page loads no framework.
   =========================================================================== */
(function () {
    'use strict';

    // ------------------------------------------------ password visibility
    // Eye toggle on password fields: flip the input between password and text.
    document.addEventListener('click', function (e) {
        var btn = e.target.closest('[data-b2b-pass-toggle]');
        if (!btn) return;
        var wrap  = btn.closest('.b2b-pass-wrap');
        var input = wrap && wrap.querySelector('input');
        if (!input) return;
        var show = input.type === 'password';
        input.type = show ? 'text' : 'password';
        btn.classList.toggle('is-on', show);
        btn.setAttribute('aria-label', (show ? btn.dataset.hide : btn.dataset.show) || '');
    });

    // Hero "change password" opens a modal (centered, dimmed backdrop); the ×,
    // the backdrop and Escape close it.
    function pwModal(open) {
        var modal = document.getElementById('b2b-pw-modal');
        if (!modal) return;
        modal.hidden = !open;
        // Lock the page scroll behind the modal while it's open.
        document.documentElement.classList.toggle('b2b-modal-open', !!open);
        var trg = document.querySelector('[data-b2b-pw-toggle]');
        if (trg) trg.classList.toggle('is-open', !!open);
        if (open) {
            var first = modal.querySelector('input');
            if (first) setTimeout(function () { first.focus(); }, 60);
        }
    }
    document.addEventListener('click', function (e) {
        if (e.target.closest('[data-b2b-pw-toggle]')) { e.preventDefault(); pwModal(true); }
        else if (e.target.closest('[data-b2b-pw-close]')) { e.preventDefault(); pwModal(false); }
    });
    document.addEventListener('keydown', function (e) {
        var modal = document.getElementById('b2b-pw-modal');
        if (e.key === 'Escape' && modal && !modal.hidden) pwModal(false);
    });

    // ------------------------------------------------------------------ utils

    /**
     * Calls a B2B endpoint. The server always answers JSON; a network failure or
     * an unparsable body becomes the same error shape, so callers never have to
     * tell the two apart.
     */
    function api(fn, data, csrf) {
        var body = new URLSearchParams();
        body.set('tp', 'ste');
        body.set('fn', fn);
        body.set('csrf', csrf || '');

        Object.keys(data || {}).forEach(function (k) {
            if (data[k] !== null && data[k] !== undefined) body.set(k, data[k]);
        });

        return fetch('/ajax.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/x-www-form-urlencoded; charset=UTF-8' },
            body: body.toString(),
            credentials: 'same-origin'
        })
            .then(function (r) { return r.json(); })
            .catch(function () { return { ok: false, error: msg('err_network') }; });
    }

    var MESSAGES = {
        ro: { err_network: 'Eroare de rețea. Încercați din nou.', pass_mismatch: 'Parolele nu coincid.', working: 'Se procesează…', phone_len: 'Numărul de telefon trebuie să conțină 8 cifre.' },
        ru: { err_network: 'Ошибка сети. Попробуйте ещё раз.', pass_mismatch: 'Пароли не совпадают.', working: 'Обработка…', phone_len: 'Номер телефона должен содержать 8 цифр.' },
        en: { err_network: 'Network error. Please try again.', pass_mismatch: 'Passwords do not match.', working: 'Processing…', phone_len: 'The phone number must contain 8 digits.' }
    };

    // Read from <body data-lng>, not the cookie: the language cookie is HttpOnly
    // so that an audit does not flag it as a script-readable cookie.
    function lang() {
        var l = (document.body && document.body.getAttribute('data-lng')) || 'ro';
        return MESSAGES[l] ? l : 'ro';
    }

    function msg(key) {
        return MESSAGES[lang()][key] || key;
    }

    /** Writes a message into the given .b2b-form__msg / .b2b-actions__msg box. */
    function say(box, text, kind, allowHtml) {
        if (!box) return;
        box.classList.remove('is-error', 'is-ok');
        if (!text) { box.textContent = ''; return; }
        // allowHtml is only used for links we build ourselves, never for text
        // coming from the server.
        if (allowHtml) box.innerHTML = text; else box.textContent = text;
        box.classList.add(kind === 'ok' ? 'is-ok' : 'is-error');
    }

    function busy(btn, on) {
        if (!btn) return;
        if (on) {
            btn.dataset.label = btn.textContent;
            btn.textContent = msg('working');
            btn.disabled = true;
        } else {
            if (btn.dataset.label) btn.textContent = btn.dataset.label;
            btn.disabled = false;
        }
    }

    /** CSRF session expired: reloading the page issues a fresh token. */
    function handleCsrf(res) {
        if (res && res.csrf_expired) {
            window.location.reload();
            return true;
        }
        return false;
    }

    // -------------------------------------------------------------- register

    /** Digits only, without a leading zero: the +373 prefix is fixed in the markup. */
    function phoneValue(input) {
        return input.value.replace(/\D/g, '').replace(/^0+/, '');
    }

    /**
     * Moldova only: exactly 8 digits. Mirrors B2bPhone::normalizeMd() on the
     * server, which is the check that actually protects the account.
     */
    function phoneError(input) {
        var digits = phoneValue(input);
        if (!digits) return '';
        return digits.length === 8 ? '' : msg('phone_len');
    }

    /** Clears the error marker as soon as the number becomes valid. */
    function initPhone(form) {
        var input = form.querySelector('.b2b-phone input');
        if (!input) return;
        input.addEventListener('input', function () {
            if (input.classList.contains('is-invalid') && !phoneError(input)) {
                input.classList.remove('is-invalid');
            }
        });
    }

    function initRegister() {
        var form = document.getElementById('b2b-register-form');
        if (!form) return;

        var success = document.getElementById('b2b-register-success');
        var box     = form.querySelector('.b2b-form__msg');
        var csrf    = form.dataset.csrf;

        initPhone(form);

        form.addEventListener('submit', function (e) {
            e.preventDefault();

            var btn  = form.querySelector('button[type="submit"]');
            var pass = form.querySelector('[name="password"]');
            var pass2 = form.querySelector('[name="password_repeat"]');

            form.querySelectorAll('.is-invalid').forEach(function (el) { el.classList.remove('is-invalid'); });

            if (pass.value !== pass2.value) {
                pass2.classList.add('is-invalid');
                say(box, msg('pass_mismatch'), 'error');
                pass2.focus();
                return;
            }

            var phoneInput = form.querySelector('[name="phone"]');
            var phoneErr = phoneError(phoneInput);
            if (phoneErr) {
                phoneInput.classList.add('is-invalid');
                say(box, phoneErr, 'error');
                phoneInput.focus();
                return;
            }

            say(box, '');
            busy(btn, true);

            var ptype = form.querySelector('[name="person_type"]:checked');

            var mkt = form.querySelector('[name="marketing_optin"]');

            api('b2b_register', {
                person_type: ptype ? ptype.value : '',
                email:       form.querySelector('[name="email"]').value.trim(),
                phone:       phoneValue(form.querySelector('[name="phone"]')),
                full_name:   form.querySelector('[name="full_name"]').value.trim(),
                password:    pass.value,
                // Optional: an unchecked box submits "" and must not block signup.
                marketing_optin: (mkt && mkt.checked) ? 'yes' : ''
            }, csrf).then(function (res) {
                busy(btn, false);
                if (handleCsrf(res)) return;

                if (!res.ok) {
                    if (res.field) {
                        var bad = form.querySelector('[name="' + res.field + '"]');
                        if (bad) { bad.classList.add('is-invalid'); bad.focus(); }
                    }
                    say(box, res.error, 'error');
                    return;
                }

                // The session is already open: go straight to the cabinet. The
                // confirmation block stays as the fallback for a response without
                // a redirect (an older cached ajax.php, say).
                if (res.redirect) { window.location.href = res.redirect; return; }

                form.hidden = true;
                if (success) success.hidden = false;
                window.scrollTo({ top: 0, behavior: 'smooth' });
            });
        });
    }

    // ------------------------------------------------------------------ login

    function initLogin() {
        var form = document.getElementById('b2b-login-form');
        if (!form) return;

        var csrf = form.dataset.csrf;
        var box  = form.querySelector('.b2b-form__msg');

        form.addEventListener('submit', function (e) {
            e.preventDefault();

            var btn = form.querySelector('button[type="submit"]');
            say(box, '');
            busy(btn, true);

            api('b2b_login', {
                login:    form.querySelector('[name="login"]').value.trim(),
                password: form.querySelector('[name="password"]').value
            }, csrf).then(function (res) {
                busy(btn, false);
                if (handleCsrf(res)) return;

                if (!res.ok) { say(box, res.error, 'error'); return; }
                window.location.href = res.redirect;
            });
        });
    }

    // -------------------------------------------------- password change / reset

    // Cabinet: change own password (current + new + repeat).
    function initChangePassword() {
        var form = document.getElementById('b2b-change-password-form');
        if (!form) return;
        var csrf = form.dataset.csrf;
        var box  = form.querySelector('.b2b-form__msg');

        form.addEventListener('submit', function (e) {
            e.preventDefault();
            var np = form.querySelector('[name="new"]').value;
            var cf = form.querySelector('[name="confirm"]').value;
            if (np !== cf) { say(box, msg('pass_mismatch'), 'error'); return; }

            var btn = form.querySelector('button[type="submit"]');
            say(box, ''); busy(btn, true);

            api('b2b_change_password', {
                current: form.querySelector('[name="current"]').value,
                'new':   np,
                confirm: cf
            }, csrf).then(function (res) {
                busy(btn, false);
                if (handleCsrf(res)) return;
                if (!res.ok) { say(box, res.error, 'error'); return; }
                form.reset();
                say(box, res.message, 'ok');
            });
        });
    }

    // Forgot password: request a reset link (answer is always generic).
    function initForgot() {
        var form = document.getElementById('b2b-forgot-form');
        if (!form) return;
        var csrf = form.dataset.csrf;
        var box  = form.querySelector('.b2b-form__msg');

        form.addEventListener('submit', function (e) {
            e.preventDefault();
            var btn = form.querySelector('button[type="submit"]');
            say(box, ''); busy(btn, true);

            api('b2b_forgot_password', {
                email: form.querySelector('[name="email"]').value.trim()
            }, csrf).then(function (res) {
                busy(btn, false);
                if (handleCsrf(res)) return;
                say(box, res.message || '', 'ok');
                form.reset();
            });
        });
    }

    // Reset page: set the new password using the token from the emailed link.
    function initReset() {
        var form = document.getElementById('b2b-reset-form');
        if (!form) return;
        var csrf    = form.dataset.csrf;
        var box     = form.querySelector('.b2b-form__msg');
        var success = document.getElementById('b2b-reset-success');

        form.addEventListener('submit', function (e) {
            e.preventDefault();
            var np = form.querySelector('[name="new"]').value;
            var cf = form.querySelector('[name="confirm"]').value;
            if (np !== cf) { say(box, msg('pass_mismatch'), 'error'); return; }

            var btn = form.querySelector('button[type="submit"]');
            say(box, ''); busy(btn, true);

            api('b2b_reset_password', {
                token:   form.querySelector('[name="token"]').value,
                'new':   np,
                confirm: cf
            }, csrf).then(function (res) {
                busy(btn, false);
                if (handleCsrf(res)) return;
                if (!res.ok) { say(box, res.error, 'error'); return; }
                form.hidden = true;
                if (success) success.hidden = false;
                window.scrollTo({ top: 0, behavior: 'smooth' });
            });
        });
    }

    // ------------------------------------------------------ car page actions

    function initCarActions() {
        document.querySelectorAll('.b2b-actions').forEach(function (panel) {
            var carId = panel.dataset.carId;
            var csrf  = panel.dataset.csrf;
            var box   = panel.querySelector('.b2b-actions__msg');
            var modal = panel.querySelector('.b2b-modal');

            // Last proforma generated from this panel, so it can be attached to
            // the Super Admin request without regenerating it.
            var lastInvoiceId = null;

            function amount() {
                var input = panel.querySelector('.b2b-advance');
                return input ? input.value : '0';
            }

            function currency() {
                var sel = panel.querySelector('.b2b-currency');
                return sel ? sel.value : 'EUR';
            }

            function openModal(open) {
                if (modal) modal.hidden = !open;
            }

            panel.addEventListener('click', function (e) {
                var trigger = e.target.closest('[data-b2b-action], [data-b2b-close]');
                if (!trigger) return;

                if (trigger.hasAttribute('data-b2b-close')) { openModal(false); return; }

                var action = trigger.dataset.b2bAction;

                // ---- Generate payment invoice (advance) ----------------------
                if (action === 'invoice') {
                    say(box, '');
                    busy(trigger, true);

                    api('b2b_create_invoice', {
                        car_id:   carId,
                        advance:  amount(),
                        currency: currency()
                    }, csrf).then(function (res) {
                        busy(trigger, false);
                        if (handleCsrf(res)) return;

                        if (!res.ok) { say(box, res.error, 'error'); return; }

                        lastInvoiceId = res.invoice_id;

                        // Built from our own data, not from server text.
                        var a = document.createElement('a');
                        a.href = res.url;
                        a.target = '_blank';
                        a.rel = 'noopener';
                        a.textContent = res.invoice_no;

                        say(box, '', 'ok');
                        box.textContent = res.message + ' ';
                        box.appendChild(a);
                        box.classList.add('is-ok');

                        window.open(res.url, '_blank', 'noopener');
                    });
                    return;
                }

                // ---- Send to Super Admin: open the modal ---------------------
                if (action === 'request') {
                    say(box, '');
                    openModal(true);
                    var ta = panel.querySelector('.b2b-modal__comment');
                    if (ta) ta.focus();
                    return;
                }

                // ---- Send to Super Admin: confirm ----------------------------
                if (action === 'request-confirm') {
                    var comment = panel.querySelector('.b2b-modal__comment');
                    var attach  = panel.querySelector('.b2b-attach-invoice');

                    busy(trigger, true);

                    api('b2b_send_request', {
                        car_id:     carId,
                        comment:    comment ? comment.value.trim() : '',
                        invoice_id: (attach && attach.checked && lastInvoiceId) ? lastInvoiceId : ''
                    }, csrf).then(function (res) {
                        busy(trigger, false);
                        if (handleCsrf(res)) return;

                        openModal(false);
                        if (!res.ok) { say(box, res.error, 'error'); return; }

                        if (comment) comment.value = '';
                        // The request is recorded and the admin is notified via the
                        // admin bell; the client just gets a confirmation.
                        say(box, res.message, 'ok');
                    });
                    return;
                }

                // ---- Save / remove from the cabinet --------------------------
                if (action === 'save') {
                    var saved = trigger.classList.contains('is-saved');
                    trigger.disabled = true;

                    api(saved ? 'b2b_unsave_car' : 'b2b_save_car', { car_id: carId }, csrf).then(function (res) {
                        trigger.disabled = false;
                        if (handleCsrf(res)) return;

                        if (!res.ok) { say(box, res.error, 'error'); return; }

                        trigger.classList.toggle('is-saved', !!res.saved);
                        say(box, res.message, 'ok');
                    });
                }
            });

            // Escape closes the modal.
            document.addEventListener('keydown', function (e) {
                if (e.key === 'Escape' && modal && !modal.hidden) openModal(false);
            });
        });
    }

    // ------------------------------------------------------------------- boot

    function init() {
        initRegister();
        initLogin();
        initChangePassword();
        initForgot();
        initReset();
        initCarActions();
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', init);
    } else {
        init();
    }
})();

/* ===========================================================================
   Saved searches (cabinet -> "Filtrele mele").
   Create posts the form; delete removes one. Both reload, because the matching
   car lists are rendered server-side and would otherwise go stale.
   =========================================================================== */
(function () {
    'use strict';

    var form = document.getElementById('b2b_filter_form');
    var list = document.querySelector('.b2b-filters');
    if (!form && !list) return;

    var panel = document.querySelector('.b2b-panel');
    var csrf  = panel ? panel.dataset.csrf : '';

    function api(fn, data) {
        var body = new URLSearchParams();
        body.set('tp', 'ste');
        body.set('pg', 'b2b');
        body.set('fn', fn);
        body.set('csrf', csrf);
        Object.keys(data || {}).forEach(function (k) { body.set(k, data[k]); });

        return fetch('/ajax.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/x-www-form-urlencoded; charset=UTF-8' },
            body: body.toString(),
            credentials: 'same-origin'
        }).then(function (r) { return r.json(); })
          .catch(function () { return { ok: false, error: 'Network error.' }; });
    }

    // ---- brand -> model, from the map the page shipped with the form --------
    var br = document.getElementById('b2b_flt_br');
    var mo = document.getElementById('b2b_flt_mo');
    if (br && mo) {
        br.addEventListener('change', function () {
            var models = (window.B2B_FLT_MODELS || {})[br.value] || {};
            var keys = Object.keys(models);
            mo.innerHTML = '<option value="">' + (mo.dataset.any || mo.options[0].textContent) + '</option>';
            keys.forEach(function (code) {
                var o = document.createElement('option');
                o.value = code;
                o.textContent = models[code];
                mo.appendChild(o);
            });
            mo.disabled = keys.length === 0;
        });
    }

    // ---- create / save an edit ----------------------------------------------
    // One form for both: the hidden filter_id decides which endpoint runs, so an
    // edit reuses the whole brand -> model and styled-select machinery.
    var idFld  = document.getElementById('b2b_filter_id');
    var ttl    = document.getElementById('b2b_filter_ttl');
    var submit = document.getElementById('b2b_filter_submit');
    var cancel = document.getElementById('b2b_filter_cancel');

    if (form) {
        form.addEventListener('submit', function (e) {
            e.preventDefault();
            var msg = document.getElementById('b2b_filter_msg');
            var btn = submit || form.querySelector('button[type=submit]');
            var data = {};

            new FormData(form).forEach(function (v, k) { if (v !== '') data[k] = v; });
            // Display names travel with the codes so the saved filter still reads
            // correctly once the matching cars are gone from the catalog.
            if (br && br.value) data.br_nm = br.options[br.selectedIndex].textContent;
            if (mo && mo.value) data.mo_nm = mo.options[mo.selectedIndex].textContent;

            if (btn) btn.disabled = true;
            api(data.filter_id ? 'b2b_edit_filter' : 'b2b_add_filter', data).then(function (res) {
                if (btn) btn.disabled = false;
                if (!res.ok) { if (msg) { msg.textContent = res.error || ''; msg.className = 'b2b-fltform__msg is-error'; } return; }
                window.location.reload();
            });
        });
    }

    // ---- edit: load a saved filter back into the form -----------------------
    function setField(name, val) {
        var f = form.elements[name];
        if (!f) return;
        f.value = val;
        // The styled dropdown and the brand -> model handler both listen for
        // 'change', which a scripted assignment does not fire on its own.
        if (f.tagName === 'SELECT') f.dispatchEvent(new Event('change', { bubbles: true }));
    }

    function fillForm(c) {
        c = c || {};

        setField('br', c.br || '');   // rebuilds the model list this filter needs
        if (mo && c.mo) {
            var known = Array.prototype.some.call(mo.options, function (o) { return o.value === c.mo; });
            // A model that has left the catalog must stay selectable, or saving
            // the edit would silently widen the filter to the whole brand.
            if (!known) {
                var o = document.createElement('option');
                o.value = c.mo;
                o.textContent = c.mo_nm || c.mo;
                mo.appendChild(o);
            }
            mo.disabled = false;
        }
        ['mo', 'region', 'fl', 'tra', 'yr_from', 'yr_to', 'vol_from', 'vol_to',
         'mlg_to', 'prc_from', 'prc_to'].forEach(function (k) {
            setField(k, c[k] != null ? c[k] : '');
        });
    }

    function setMode(id) {
        if (idFld) idFld.value = id || '';
        if (ttl && form.dataset.ttlNew) ttl.textContent = id ? form.dataset.ttlEdit : form.dataset.ttlNew;
        if (submit && form.dataset.btnNew) submit.textContent = id ? form.dataset.btnEdit : form.dataset.btnNew;
        if (cancel) cancel.hidden = !id;
        form.classList.toggle('is-editing', !!id);
    }

    document.addEventListener('click', function (e) {
        var ed = e.target.closest('[data-b2b-filter-edit]');
        if (!ed || !form) return;
        // It sits inside <summary>, so without this the click would fold the row.
        e.preventDefault();

        var c = {};
        try { c = JSON.parse(ed.dataset.flt || '{}'); } catch (err) { c = {}; }
        fillForm(c);
        setMode(ed.dataset.b2bFilterEdit);

        var msg = document.getElementById('b2b_filter_msg');
        if (msg) { msg.textContent = ''; msg.className = 'b2b-fltform__msg'; }
        form.scrollIntoView({ behavior: 'smooth', block: 'start' });
    });

    if (cancel) {
        cancel.addEventListener('click', function () {
            fillForm({});
            setMode('');
        });
    }

    // ---- delete -------------------------------------------------------------
    document.addEventListener('click', function (e) {
        var del = e.target.closest('[data-b2b-filter-del]');
        if (!del) return;
        // It sits inside <summary>, so without this the click would also fold
        // the section while the request is still running.
        e.preventDefault();
        del.disabled = true;
        api('b2b_del_filter', { filter_id: del.dataset.b2bFilterDel }).then(function (res) {
            if (!res.ok) { del.disabled = false; alert(res.error || ''); return; }
            window.location.reload();
        });
    });
})();

/* ===========================================================================
   Styled dropdown for the saved-search form.

   A native <select> renders its open list through the OS, so the blue highlight
   and the plain box cannot be reached from CSS. This keeps the real <select> in
   the DOM — it still carries the value, submits, and drives the brand -> model
   logic — and draws a listbox over it on pointer devices.

   Touch keeps the native picker on purpose: the OS wheel/sheet is better than
   anything we would build, and it is what people expect on a phone.
   =========================================================================== */
(function () {
    'use strict';

    var form = document.getElementById('b2b_filter_form');
    if (!form || !window.matchMedia('(min-width: 769px)').matches) return;

    function build(sel) {
        if (sel.dataset.b2bSel) return;
        sel.dataset.b2bSel = '1';

        var wrap = document.createElement('div');
        wrap.className = 'b2b-sel';
        sel.parentNode.insertBefore(wrap, sel);
        wrap.appendChild(sel);

        var btn = document.createElement('button');
        btn.type = 'button';
        btn.className = 'b2b-sel__btn';
        btn.setAttribute('aria-haspopup', 'listbox');
        btn.setAttribute('aria-expanded', 'false');
        wrap.appendChild(btn);

        var list = document.createElement('ul');
        list.className = 'b2b-sel__list';
        list.setAttribute('role', 'listbox');
        list.hidden = true;
        wrap.appendChild(list);

        function label() {
            var o = sel.options[sel.selectedIndex];
            btn.textContent = o ? o.textContent : '';
            btn.classList.toggle('is-placeholder', !sel.value);
        }

        function render() {
            list.innerHTML = '';
            Array.prototype.forEach.call(sel.options, function (o, i) {
                var li = document.createElement('li');
                li.className = 'b2b-sel__opt' + (i === sel.selectedIndex ? ' is-on' : '');
                li.setAttribute('role', 'option');
                li.setAttribute('aria-selected', i === sel.selectedIndex ? 'true' : 'false');
                li.textContent = o.textContent;
                li.addEventListener('click', function () {
                    sel.selectedIndex = i;
                    // Native event, so the brand -> model handler above still fires.
                    sel.dispatchEvent(new Event('change', { bubbles: true }));
                    close();
                });
                list.appendChild(li);
            });
        }

        function open() {
            if (sel.disabled) return;
            document.querySelectorAll('.b2b-sel.is-open').forEach(function (w) {
                if (w !== wrap) w.classList.remove('is-open');
            });
            render();
            list.hidden = false;
            wrap.classList.add('is-open');
            btn.setAttribute('aria-expanded', 'true');
            var on = list.querySelector('.is-on');
            if (on) on.scrollIntoView({ block: 'nearest' });
        }

        function close() {
            list.hidden = true;
            wrap.classList.remove('is-open');
            btn.setAttribute('aria-expanded', 'false');
            label();
        }

        btn.addEventListener('click', function (e) {
            e.stopPropagation();
            wrap.classList.contains('is-open') ? close() : open();
        });

        btn.addEventListener('keydown', function (e) {
            if (e.key === 'Enter' || e.key === ' ' || e.key === 'ArrowDown') { e.preventDefault(); open(); }
            else if (e.key === 'Escape') close();
        });

        // The model select is rebuilt whenever the brand changes, and starts
        // disabled — mirror both, or the button would show a stale list.
        sel.addEventListener('change', label);
        new MutationObserver(function () {
            btn.disabled = sel.disabled;
            label();
        }).observe(sel, { attributes: true, attributeFilter: ['disabled'], childList: true });

        btn.disabled = sel.disabled;
        label();
    }

    form.querySelectorAll('select').forEach(build);

    document.addEventListener('click', function () {
        document.querySelectorAll('.b2b-sel.is-open').forEach(function (w) {
            w.classList.remove('is-open');
            var l = w.querySelector('.b2b-sel__list');
            if (l) l.hidden = true;
            var b = w.querySelector('.b2b-sel__btn');
            if (b) b.setAttribute('aria-expanded', 'false');
        });
    });
})();
