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
        ro: { err_network: 'Eroare de rețea. Încercați din nou.', pass_mismatch: 'Parolele nu coincid.', working: 'Se procesează…', phone_len: 'Numărul de telefon trebuie să conțină 8 cifre.' },
        ru: { err_network: 'Ошибка сети. Попробуйте ещё раз.', pass_mismatch: 'Пароли не совпадают.', working: 'Обработка…', phone_len: 'Номер телефона должен содержать 8 цифр.' },
        en: { err_network: 'Network error. Please try again.', pass_mismatch: 'Passwords do not match.', working: 'Processing…', phone_len: 'The phone number must contain 8 digits.' }
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
