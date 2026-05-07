<?php
/**
 * Contact form widget — include anywhere on site
 * Usage: require_once('/path/to/contact_form.php');
 *        sauto_contact_form(['lang' => 'ro', 'title' => 'Contactați-ne']);
 */

function sauto_contact_form(array $opts = []): void {
    $lang   = $opts['lang']   ?? ($_COOKIE['lang'] ?? 'ro');
    $title  = $opts['title']  ?? '';
    $source = $opts['source'] ?? 'site';
    $id     = 'cf_' . substr(md5(uniqid()), 0, 6);

    $labels = [
        'ro' => [
            'name'      => 'Nume',
            'phone'     => 'Telefon',
            'message'   => 'Mesaj',
            'gdpr'      => 'Sunt de acord cu <a href="/ro/privacy" target="_blank" rel="noopener">prelucrarea datelor personale</a>',
            'submit'    => 'Trimite cererea',
            'success_t' => 'Cererea a fost trimisă!',
            'success_s' => 'Vă vom contacta în cel mai scurt timp.',
            'error'     => 'Eroare. Încercați din nou.',
            'req_name'  => 'Introduceți numele',
            'req_phone' => 'Introduceți telefonul',
            'req_gdpr'  => 'Confirmați acordul pentru prelucrarea datelor',
        ],
        'ru' => [
            'name'      => 'Имя',
            'phone'     => 'Телефон',
            'message'   => 'Сообщение',
            'gdpr'      => 'Я согласен на <a href="/ru/privacy" target="_blank" rel="noopener">обработку персональных данных</a>',
            'submit'    => 'Отправить заявку',
            'success_t' => 'Заявка отправлена!',
            'success_s' => 'Мы свяжемся с вами в ближайшее время.',
            'error'     => 'Ошибка. Попробуйте снова.',
            'req_name'  => 'Введите имя',
            'req_phone' => 'Введите телефон',
            'req_gdpr'  => 'Подтвердите согласие на обработку данных',
        ],
        'en' => [
            'name'      => 'Name',
            'phone'     => 'Phone',
            'message'   => 'Message',
            'gdpr'      => 'I agree to the <a href="/en/privacy" target="_blank" rel="noopener">processing of personal data</a>',
            'submit'    => 'Send request',
            'success_t' => 'Request sent!',
            'success_s' => 'We will contact you shortly.',
            'error'     => 'Error. Please try again.',
            'req_name'  => 'Enter your name',
            'req_phone' => 'Enter your phone',
            'req_gdpr'  => 'Please confirm consent',
        ],
    ];
    $l = $labels[$lang] ?? $labels['ro'];
    ?>
<div class="scf-wrap" id="<?= $id ?>">
    <?php if ($title): ?>
    <div class="scf-title"><?= htmlspecialchars($title) ?></div>
    <?php endif; ?>

    <form class="scf-form" onsubmit="scf_submit(event,'<?= $id ?>')" novalidate>
        <input type="hidden" name="source" value="<?= htmlspecialchars($source) ?>">
        <input type="hidden" name="page_url" value="">

        <div class="scf-field">
            <input type="text" name="name" id="<?= $id ?>_name" placeholder=" " maxlength="100" autocomplete="name">
            <label for="<?= $id ?>_name"><?= htmlspecialchars($l['name']) ?> <span>*</span></label>
            <div class="scf-line"></div>
        </div>

        <div class="scf-field">
            <input type="tel" name="phone" id="<?= $id ?>_phone" placeholder=" " maxlength="30" autocomplete="tel"
                   onfocus="if(!this.value)this.value='+373'"
                   onblur="if(this.value==='+373')this.value=''">
            <label for="<?= $id ?>_phone"><?= htmlspecialchars($l['phone']) ?> <span>*</span></label>
            <div class="scf-line"></div>
        </div>

        <div class="scf-field scf-field-ta">
            <textarea name="message" id="<?= $id ?>_msg" placeholder=" " maxlength="2000" rows="3"></textarea>
            <label for="<?= $id ?>_msg"><?= htmlspecialchars($l['message']) ?></label>
            <div class="scf-line"></div>
        </div>

        <div class="scf-gdpr">
            <label class="scf-check-label">
                <input type="checkbox" name="gdpr" class="scf-check-input">
                <span class="scf-check-box">
                    <svg viewBox="0 0 12 10" fill="none" xmlns="http://www.w3.org/2000/svg">
                        <polyline points="1,5 4.5,8.5 11,1" stroke="#fff" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"/>
                    </svg>
                </span>
                <span class="scf-check-text"><?= $l['gdpr'] ?></span>
            </label>
        </div>

        <div class="scf-error" style="display:none;">
            <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round"><circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/></svg>
            <span class="scf-error-text"></span>
        </div>

        <button type="submit" class="scf-btn">
            <span class="scf-btn-text">
                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="vertical-align:middle;margin-right:6px;"><line x1="22" y1="2" x2="11" y2="13"/><polygon points="22 2 15 22 11 13 2 9 22 2"/></svg>
                <?= htmlspecialchars($l['submit']) ?>
            </span>
            <span class="scf-btn-loader" style="display:none;">
                <span class="scf-spinner"></span>
            </span>
        </button>

    </form>

    <div class="scf-success" style="display:none;">
        <div class="scf-success-icon">
            <svg viewBox="0 0 52 52" fill="none" xmlns="http://www.w3.org/2000/svg">
                <circle cx="26" cy="26" r="25" stroke="#E61E2D" stroke-width="2"/>
                <polyline points="14,27 22,35 38,18" stroke="#E61E2D" stroke-width="3" stroke-linecap="round" stroke-linejoin="round"/>
            </svg>
        </div>
        <div class="scf-success-title"><?= htmlspecialchars($l['success_t']) ?></div>
        <div class="scf-success-sub"><?= htmlspecialchars($l['success_s']) ?></div>
    </div>
</div>

<style>
.scf-wrap { font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif; width: 100%; max-width: 520px; }
.scf-title { font-size: 1.5rem; font-weight: 700; color: #111; margin-bottom: 1.8rem; letter-spacing: -0.02em; }

/* Floating label fields */
.scf-field { position: relative; margin-bottom: 1.6rem; }
.scf-field input,
.scf-field textarea {
    width: 100%; border: none; border-bottom: 2px solid #e0e0e0;
    outline: none; padding: 1.2rem 0 0.4rem; font-size: 1rem;
    color: #111; background: transparent; font-family: inherit;
    box-sizing: border-box; transition: border-color 0.2s; resize: none;
}
.scf-field label {
    position: absolute; left: 0; top: 1.1rem;
    font-size: 1rem; color: #999; pointer-events: none;
    transition: all 0.2s cubic-bezier(.4,0,.2,1); transform-origin: left top;
}
.scf-field label span { color: #E61E2D; }
.scf-field input:focus ~ label,
.scf-field input:not(:placeholder-shown) ~ label,
.scf-field textarea:focus ~ label,
.scf-field textarea:not(:placeholder-shown) ~ label {
    transform: translateY(-1rem) scale(0.78);
    color: #E61E2D;
}
.scf-line {
    position: absolute; bottom: 0; left: 0; width: 0; height: 2px;
    background: #E61E2D; transition: width 0.3s cubic-bezier(.4,0,.2,1);
}
.scf-field input:focus ~ .scf-line,
.scf-field textarea:focus ~ .scf-line { width: 100%; }
.scf-field-ta textarea { min-height: 80px; border-bottom: none !important; }

/* Checkbox */
.scf-gdpr { margin: 0.5rem 0 1.4rem; }
.scf-check-label { display: flex; align-items: flex-start; gap: 0.7rem; cursor: pointer; }
.scf-check-input { position: absolute; opacity: 0; width: 0; height: 0; }
.scf-check-box {
    flex-shrink: 0; width: 20px; height: 20px; border: 2px solid #ccc;
    border-radius: 4px; background: #fff; display: flex; align-items: center;
    justify-content: center; transition: all 0.2s; margin-top: 1px;
}
.scf-check-box svg { width: 12px; height: 10px; opacity: 0; transition: opacity 0.15s; }
.scf-check-input:checked ~ .scf-check-box { background: #E61E2D; border-color: #E61E2D; }
.scf-check-input:checked ~ .scf-check-box svg { opacity: 1; }
.scf-check-text { font-size: 0.8rem; color: #666; line-height: 1.5; }
.scf-check-text a { color: #E61E2D; text-decoration: none; border-bottom: 1px solid rgba(230,30,45,0.3); }
.scf-check-text a:hover { border-bottom-color: #E61E2D; }

/* Error */
.scf-error {
    display: flex; align-items: center; gap: 0.4rem;
    color: #c00; font-size: 0.82rem; margin-bottom: 1rem;
    background: #fff5f5; border: 1px solid #fcc; border-radius: 6px; padding: 0.5rem 0.75rem;
}

/* Button */
.scf-btn {
    width: 100%; padding: 1rem 1.5rem; background: #E61E2D;
    color: #fff; border: none; border-radius: 6px; font-size: 1rem;
    font-weight: 600; cursor: pointer; letter-spacing: 0.02em;
    transition: background 0.2s, transform 0.1s, box-shadow 0.2s;
    box-shadow: 0 4px 15px rgba(230,30,45,0.35); display: flex;
    align-items: center; justify-content: center; min-height: 52px;
}
.scf-btn:hover:not(:disabled) { background: #c8172a; box-shadow: 0 6px 20px rgba(230,30,45,0.45); transform: translateY(-1px); }
.scf-btn:active:not(:disabled) { transform: translateY(0); box-shadow: 0 3px 10px rgba(230,30,45,0.3); }
.scf-btn:disabled { opacity: 0.75; cursor: not-allowed; }

/* Spinner */
.scf-spinner {
    display: inline-block; width: 22px; height: 22px;
    border: 2.5px solid rgba(255,255,255,0.35);
    border-top-color: #fff; border-radius: 50%;
    animation: scf-spin 0.7s linear infinite;
}
@keyframes scf-spin { to { transform: rotate(360deg); } }

/* Success */
.scf-success { text-align: center; padding: 2.5rem 1rem; }
.scf-success-icon { margin-bottom: 1rem; }
.scf-success-icon svg { width: 64px; height: 64px; animation: scf-pop 0.4s cubic-bezier(.175,.885,.32,1.275); }
@keyframes scf-pop { from { transform: scale(0.5); opacity: 0; } to { transform: scale(1); opacity: 1; } }
.scf-success-title { font-size: 1.3rem; font-weight: 700; color: #111; margin-bottom: 0.4rem; }
.scf-success-sub { font-size: 0.9rem; color: #666; }
</style>

<script>
function scf_submit(e, formId) {
    e.preventDefault();
    var wrap  = document.getElementById(formId);
    var form  = wrap.querySelector('form');
    var err   = wrap.querySelector('.scf-error');
    var errT  = wrap.querySelector('.scf-error-text');
    var btn   = wrap.querySelector('.scf-btn');
    var btext = wrap.querySelector('.scf-btn-text');
    var bload = wrap.querySelector('.scf-btn-loader');

    var name  = form.querySelector('[name=name]').value.trim();
    var phone = form.querySelector('[name=phone]').value.trim();
    var gdpr  = form.querySelector('[name=gdpr]').checked;

    err.style.display = 'none';

    if (!name)  { errT.textContent = <?= json_encode($l['req_name']) ?>;  err.style.display='flex'; form.querySelector('[name=name]').focus(); return; }
    if (!phone || phone === '+373') { errT.textContent = <?= json_encode($l['req_phone']) ?>; err.style.display='flex'; form.querySelector('[name=phone]').focus(); return; }
    if (!gdpr)  { errT.textContent = <?= json_encode($l['req_gdpr']) ?>;  err.style.display='flex'; return; }

    btn.disabled = true;
    btext.style.display = 'none';
    bload.style.display = 'flex';

    var fd = new FormData(form);
    fd.set('page_url', window.location.href);
    fetch('/api/contact_form.php', { method: 'POST', body: fd })
        .then(function(r){ return r.json(); })
        .then(function(d){
            if (d.ok) {
                form.style.display = 'none';
                wrap.querySelector('.scf-success').style.display = 'block';
            } else {
                errT.textContent = <?= json_encode($l['error']) ?>;
                err.style.display = 'flex';
                btn.disabled = false;
                btext.style.display = '';
                bload.style.display = 'none';
            }
        })
        .catch(function(){
            errT.textContent = <?= json_encode($l['error']) ?>;
            err.style.display = 'flex';
            btn.disabled = false;
            btext.style.display = '';
            bload.style.display = 'none';
        });
}
</script>
<?php } ?>
