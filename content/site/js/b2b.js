/* ===========================================================================
   B2B module: registration, login and the car-page actions
   (proforma, send to Super Admin, save).

   Every request goes to POST /ajax.php with tp=ste, the site's existing AJAX
   flow. No external dependencies: the public page loads no framework.
   =========================================================================== */
(function () {
    'use strict';

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
        ro: { err_network: 'Eroare de rețea. Încercați din nou.', pass_mismatch: 'Parolele nu coincid.', working: 'Se procesează…',
              digits: 'cifre', phone_len: 'Numărul pentru {country} trebuie să conțină {expected} după prefixul {dial}.' },
        ru: { err_network: 'Ошибка сети. Попробуйте ещё раз.', pass_mismatch: 'Пароли не совпадают.', working: 'Обработка…',
              digits: 'цифр', phone_len: 'Номер для {country} должен содержать {expected} после кода {dial}.' },
        en: { err_network: 'Network error. Please try again.', pass_mismatch: 'Passwords do not match.', working: 'Processing…',
              digits: 'digits', phone_len: 'A {country} number needs {expected} after the {dial} prefix.' }
    };

    function lang() {
        var m = document.cookie.match(/(?:^|;\s*)lang=([^;]*)/);
        var l = m ? m[1] : 'ro';
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

    /**
     * Phone value to submit: dialling code from the picker + the national number.
     *
     * If the user types their own + or 00 prefix, that wins and the picker is
     * ignored. Leading zeros are dropped otherwise, because people type "060..."
     * out of habit, which would become +3730 60...
     */
    function phoneValue(input) {
        var raw = input.value.trim();
        if (!raw) return '';
        if (/^(\+|00)/.test(raw)) return raw;

        var cc = input.closest('.b2b-phone');
        cc = cc && cc.querySelector('.b2b-cc');
        var dial = (cc && cc.dataset.dial) || '+373';

        return dial + raw.replace(/\D/g, '').replace(/^0+/, '');
    }

    /**
     * Digit-count check for the selected country. Returns an error string, or ''
     * when the number is acceptable. Mirrors B2bCountries::validate() on the
     * server, which is the check that actually protects the account.
     */
    function phoneError(input) {
        var raw = input.value.trim();
        if (!raw) return '';
        if (/^(\+|00)/.test(raw)) return ''; // hand-written prefix: server decides

        var cc = input.closest('.b2b-phone');
        cc = cc && cc.querySelector('.b2b-cc');
        if (!cc || !cc.dataset.min) return '';

        var digits = raw.replace(/\D/g, '').replace(/^0+/, '').length;
        var min = parseInt(cc.dataset.min, 10);
        var max = parseInt(cc.dataset.max, 10);
        if (digits >= min && digits <= max) return '';

        var expected = (min === max ? min : min + '-' + max) + ' ' + msg('digits');
        return msg('phone_len')
            .replace('{country}', cc.dataset.name || '')
            .replace('{expected}', expected)
            .replace('{dial}', cc.dataset.dial || '');
    }

    /** Country dialling-code dropdown next to the phone input. */
    function initDialPicker(form) {
        var cc = form.querySelector('.b2b-cc');
        if (!cc) return;

        var btn  = cc.querySelector('.b2b-cc__btn');
        var flag = cc.querySelector('.b2b-cc__flag');
        var code = cc.querySelector('.b2b-cc__code');
        var input = form.querySelector('.b2b-phone input');

        // Clear a stale error as soon as the number becomes valid.
        if (input) {
            input.addEventListener('input', function () {
                if (input.classList.contains('is-invalid') && !phoneError(input)) {
                    input.classList.remove('is-invalid');
                }
            });
        }

        btn.addEventListener('click', function (e) {
            e.stopPropagation();
            var open = cc.classList.toggle('open');
            btn.setAttribute('aria-expanded', open ? 'true' : 'false');
        });

        cc.addEventListener('click', function (e) {
            var li = e.target.closest('li[data-dial]');
            if (!li) return;

            // Carry the whole country over: the length rules move with it.
            cc.dataset.dial = li.dataset.dial;
            cc.dataset.min  = li.dataset.min;
            cc.dataset.max  = li.dataset.max;
            cc.dataset.name = li.dataset.name;

            flag.src = '/media/images/flags/' + li.dataset.iso + '.svg';
            flag.alt = li.dataset.name;
            code.textContent = li.dataset.dial;

            cc.querySelectorAll('li').forEach(function (x) { x.classList.remove('is-active'); });
            li.classList.add('is-active');

            cc.classList.remove('open');
            btn.setAttribute('aria-expanded', 'false');

            // A number valid for the old country may not be for the new one.
            if (input) {
                input.classList.toggle('is-invalid', !!phoneError(input));
                input.focus();
            }
        });

        document.addEventListener('click', function () {
            cc.classList.remove('open');
            btn.setAttribute('aria-expanded', 'false');
        });
        document.addEventListener('keydown', function (e) {
            if (e.key === 'Escape') cc.classList.remove('open');
        });
    }

    function initRegister() {
        var form = document.getElementById('b2b-register-form');
        if (!form) return;

        var success = document.getElementById('b2b-register-success');
        var box     = form.querySelector('.b2b-form__msg');
        var csrf    = form.dataset.csrf;

        initDialPicker(form);

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

            api('b2b_register', {
                person_type: ptype ? ptype.value : '',
                email:       form.querySelector('[name="email"]').value.trim(),
                phone:       phoneValue(form.querySelector('[name="phone"]')),
                full_name:   form.querySelector('[name="full_name"]').value.trim(),
                login:       form.querySelector('[name="login"]').value.trim(),
                password:    pass.value
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

                // Blocking confirmation per the spec: the form goes away.
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

                        say(box, res.message, 'ok');
                        if (comment) comment.value = '';

                        // Without Cloud API, open WhatsApp with the prefilled
                        // message. The request is already stored server-side.
                        if (res.wa_link) window.open(res.wa_link, '_blank', 'noopener');
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
        initCarActions();
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', init);
    } else {
        init();
    }
})();
