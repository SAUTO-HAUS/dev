<?php defined( '_DOIT' ) or die( 'Restricted access' );

/**
 * /{lang}/b2b/cont-plata?car=ID — payment-invoice form for a logged-in partner.
 * Included from cabinet.php (which has already loaded _layout.php, $user, $userId).
 *
 * Fields (Document / Auto / Cumpărător) are pre-filled from the car + the client
 * profile; on submit fn=b2b_create_invoice freezes them onto the proforma, then
 * notifies the Super Admin. The generated document reuses invoice_template.php.
 */

use App\Services\B2b\B2bConfig;
use App\Services\B2b\B2bInvoice;
use App\Services\B2b\B2bRegions;
use App\Services\B2b\B2bPhone;

$lang  = $_COOKIE['lang'] ?? 'ro';
$carId = isset($_GET['car']) ? (int)$_GET['car'] : 0;
$car   = B2bInvoice::loadCar($carId);

$T = [
    'ro' => [
        'title' => 'Creează cont de plată', 'sub' => 'Verifică datele și apasă Creează. Documentul se generează și administratorul e anunțat.',
        'doc' => 'Document', 'auto' => 'Auto', 'buyer' => 'Cumpărător',
        'date' => 'Data', 'brand' => 'Marca', 'model' => 'Model', 'vin' => 'Cod VIN',
        'price' => 'Suma avansului', 'mdl_hint' => 'Suma este în lei (MDL).',
        'pct_hint' => 'Avansul reprezintă %s%% din prețul mașinii.',
        'type' => 'Tip', 'fiz' => 'Persoană fizică', 'jur' => 'Persoană juridică',
        'name' => 'Nume, prenume', 'idno' => 'IDNP / IDNO', 'phone' => 'Telefon',
        'submit' => 'Creează cont de plată', 'back' => '← Înapoi la mașină',
        'submit_note' => 'După ce creezi contul de plată, cererea ajunge instant la echipa Sauto și te contactăm în scurt timp pentru confirmare.',
        'req_msg' => 'Completează câmpurile obligatorii: nume, IDNP/IDNO și telefon.',
        'idno_msg' => 'IDNP/IDNO trebuie să conțină exact 13 cifre.',
        'not_found' => 'Mașina nu a fost găsită.', 'restricted' => 'Restricționat conform planului tău.',
        'already' => 'Aveți deja un cont de plată pentru această mașină.', 'view_invoice' => 'Vezi contul de plată',
    ],
    'ru' => [
        'title' => 'Создать счёт на оплату', 'sub' => 'Проверьте данные и нажмите Создать. Документ сформируется, администратор получит уведомление.',
        'doc' => 'Документ', 'auto' => 'Авто', 'buyer' => 'Покупатель',
        'date' => 'Дата', 'brand' => 'Марка', 'model' => 'Модель', 'vin' => 'VIN-код',
        'price' => 'Сумма аванса', 'mdl_hint' => 'Сумма в леях (MDL).',
        'pct_hint' => 'Аванс — %s%% от цены авто.',
        'type' => 'Тип', 'fiz' => 'Физическое лицо', 'jur' => 'Юридическое лицо',
        'name' => 'Имя, фамилия', 'idno' => 'IDNP / IDNO', 'phone' => 'Телефон',
        'submit' => 'Создать счёт на оплату', 'back' => '← Назад к авто',
        'submit_note' => 'После создания счёта заявка мгновенно поступает команде Sauto — мы свяжемся с вами в ближайшее время для подтверждения.',
        'req_msg' => 'Заполните обязательные поля: имя, IDNP/IDNO и телефон.',
        'idno_msg' => 'IDNP/IDNO должен содержать ровно 13 цифр.',
        'not_found' => 'Автомобиль не найден.', 'restricted' => 'Ограничено согласно вашему плану.',
        'already' => 'У вас уже есть счёт на оплату для этого авто.', 'view_invoice' => 'Смотреть счёт на оплату',
    ],
    'en' => [
        'title' => 'Create payment invoice', 'sub' => 'Check the details and press Create. The document is generated and the administrator is notified.',
        'doc' => 'Document', 'auto' => 'Car', 'buyer' => 'Buyer',
        'date' => 'Date', 'brand' => 'Brand', 'model' => 'Model', 'vin' => 'VIN code',
        'price' => 'Advance amount', 'mdl_hint' => 'Amount is in lei (MDL).',
        'pct_hint' => 'The advance is %s%% of the car price.',
        'type' => 'Type', 'fiz' => 'Individual', 'jur' => 'Legal entity',
        'name' => 'Name, surname', 'idno' => 'IDNP / IDNO', 'phone' => 'Phone',
        'submit' => 'Create payment invoice', 'back' => '← Back to the car',
        'submit_note' => 'Once you create the payment invoice, your request reaches the Sauto team instantly and we\'ll contact you shortly to confirm.',
        'req_msg' => 'Please fill in the required fields: name, IDNP/IDNO and phone.',
        'idno_msg' => 'IDNP/IDNO must be exactly 13 digits.',
        'not_found' => 'Car not found.', 'restricted' => 'Restricted by your plan.',
        'already' => 'You already have a payment invoice for this car.', 'view_invoice' => 'View payment invoice',
    ],
];
$t = $T[$lang] ?? $T['ro'];

echo b2b_assets();

if (!$car) {
    echo '<div class="b2b-page b2b-cp"><div class="b2b-card"><p class="b2b-card__sub" style="text-align:center;">'.b2b_esc($t['not_found']).'</p></div></div>';
    return;
}

// Same region guard as the invoice/request flow.
$region = B2bRegions::regionForCar($carId);
if ($region !== null && !B2bRegions::isAllowed($userId, $region)) {
    echo '<div class="b2b-page b2b-cp"><div class="b2b-card"><p class="b2b-card__sub" style="text-align:center;">'.b2b_esc($t['restricted']).'</p></div></div>';
    return;
}

// One payment invoice per car per partner: if it already exists, link to it
// instead of showing the form again (direct-link guard; the panel already hides
// the create button once an invoice is issued).
$existingInv = B2bInvoice::findForCar($userId, $carId);
if ($existingInv) {
    echo '<div class="b2b-page b2b-cp">'
       . '<a class="b2b-cp-back" href="/'.b2b_esc($lang).'/ordercars/'.(int)$carId.'">'.b2b_esc($t['back']).'</a>'
       . '<div class="b2b-card"><div class="b2b-card__head">'
       . '<h1 class="b2b-card__ttl">'.b2b_esc($t['title']).'</h1>'
       . '<p class="b2b-card__sub">'.b2b_esc($t['already']).'</p></div>'
       . '<a class="b2b-btn b2b-btn--primary b2b-btn--block" href="'.b2b_esc(B2bInvoice::path($existingInv)).'">'.b2b_esc($t['view_invoice']).'</a>'
       . '</div></div>';
    return;
}

// Prefill: car data from the catalog, buyer data from the client profile.
$brand = trim((string)($car['br_nm'] ?: str_replace('_', ' ', (string)$car['br'])));
$model = trim((string)($car['mo_nm'] ?: str_replace('_', ' ', (string)$car['mo'])));
$vin   = (string)($car['vin'] ?? '');

// The document is always issued in MDL. The advance is computed directly in MDL:
// the fixed amount is already MDL; the percentage is converted from the car's
// currency at the BNM rate (same source as the public calculator).
$price = (int)round(B2bInvoice::suggestedAdvanceMdl($car));

// Explain how the number was reached: in percent mode, "X% of the car price".
if (B2bConfig::get('b2b_advance_mode', 'fixed') === 'percent') {
    $pct       = (float)B2bConfig::get('b2b_advance_percent', '10');
    $pctTxt    = rtrim(rtrim(sprintf('%.1f', $pct), '0'), '.');
    $priceHint = sprintf($t['pct_hint'], $pctTxt);
} else {
    $priceHint = $t['mdl_hint'];
}

$buyerType  = ($user['person_type'] ?? 'individual') === 'company' ? 'jur' : 'fiz';
$buyerName  = (string)($user['full_name'] ?? '');
$buyerIdno  = (string)($user['idno'] ?? '');
$buyerPhone = B2bPhone::local((string)($user['phone_number'] ?? ''));

$csrf = b2b_esc(b2b_csrf_token());
$e    = fn($v) => b2b_esc($v);

// Only the buyer section is editable; the document + car data come from the
// listing and are locked. The padlock marks the read-only sections.
$lockIco = '<svg class="b2b-cp-lock" viewBox="0 0 24 24" aria-hidden="true"><path d="M17 8h-1V6a4 4 0 1 0-8 0v2H7a2 2 0 0 0-2 2v9a2 2 0 0 0 2 2h10a2 2 0 0 0 2-2v-9a2 2 0 0 0-2-2zm-7-2a2 2 0 1 1 4 0v2h-4V6z"/></svg>';
?>
<div class="b2b-page b2b-cp">
    <a class="b2b-cp-back" href="/<?= $e($lang) ?>/ordercars/<?= (int)$carId ?>"><?= $e($t['back']) ?></a>

    <div class="b2b-card">
        <div class="b2b-card__head">
            <h1 class="b2b-card__ttl"><?= $e($t['title']) ?></h1>
            <p class="b2b-card__sub"><?= $e($t['sub']) ?></p>
        </div>

        <form class="b2b-form" id="b2b-cp-form" data-csrf="<?= $csrf ?>" data-car="<?= (int)$carId ?>" data-req-msg="<?= $e($t['req_msg']) ?>" data-idno-msg="<?= $e($t['idno_msg']) ?>" novalidate>

            <h2 class="b2b-cp-sec"><?= $lockIco.$e($t['doc']) ?></h2>
            <div class="b2b-field">
                <label for="cp-date"><?= $e($t['date']) ?></label>
                <input type="date" id="cp-date" name="date" value="<?= $e(date('Y-m-d')) ?>" readonly tabindex="-1" />
            </div>

            <h2 class="b2b-cp-sec"><?= $lockIco.$e($t['auto']) ?></h2>
            <div class="b2b-field-row">
                <div class="b2b-field">
                    <label for="cp-br"><?= $e($t['brand']) ?></label>
                    <input type="text" id="cp-br" name="br" value="<?= $e($brand) ?>" maxlength="120" readonly tabindex="-1" />
                </div>
                <div class="b2b-field">
                    <label for="cp-mo"><?= $e($t['model']) ?></label>
                    <input type="text" id="cp-mo" name="mo" value="<?= $e($model) ?>" maxlength="120" readonly tabindex="-1" />
                </div>
            </div>
            <div class="b2b-field">
                <label for="cp-vin"><?= $e($t['vin']) ?></label>
                <input type="text" id="cp-vin" name="vin" value="<?= $e($vin) ?>" maxlength="32" readonly tabindex="-1" />
            </div>
            <div class="b2b-field">
                <label for="cp-price"><?= $e($t['price']) ?></label>
                <div class="b2b-money">
                    <input type="number" id="cp-price" name="price" min="1" step="1" value="<?= (int)$price ?>" readonly tabindex="-1" required />
                    <span class="b2b-money__cur">MDL</span>
                </div>
                <span class="b2b-hint"><?= $e($priceHint) ?></span>
            </div>

            <h2 class="b2b-cp-sec"><?= $e($t['buyer']) ?></h2>
            <div class="b2b-field">
                <span class="b2b-field__lbl"><?= $e($t['type']) ?></span>
                <div class="b2b-radios">
                    <label class="b2b-radio">
                        <input type="radio" name="buyer_type" value="fiz"<?= $buyerType === 'fiz' ? ' checked' : '' ?> />
                        <span><?= $e($t['fiz']) ?></span>
                    </label>
                    <label class="b2b-radio">
                        <input type="radio" name="buyer_type" value="jur"<?= $buyerType === 'jur' ? ' checked' : '' ?> />
                        <span><?= $e($t['jur']) ?></span>
                    </label>
                </div>
            </div>
            <div class="b2b-field">
                <label for="cp-name"><?= $e($t['name']) ?></label>
                <input type="text" id="cp-name" name="buyer_name" value="<?= $e($buyerName) ?>" maxlength="190" required />
            </div>
            <div class="b2b-field-row">
                <div class="b2b-field">
                    <label for="cp-idno"><?= $e($t['idno']) ?></label>
                    <input type="text" id="cp-idno" name="buyer_idno" value="<?= $e($buyerIdno) ?>" maxlength="13" inputmode="numeric" pattern="\d{13}" required />
                </div>
                <div class="b2b-field">
                    <label for="cp-phone"><?= $e($t['phone']) ?></label>
                    <input type="text" id="cp-phone" name="buyer_phone" value="<?= $e($buyerPhone) ?>" maxlength="32" required />
                </div>
            </div>

            <div class="b2b-form__msg" role="alert" aria-live="polite"></div>

            <button type="submit" class="b2b-btn b2b-btn--primary b2b-btn--block"><?= $e($t['submit']) ?></button>

            <p class="b2b-cp-note"><?= $e($t['submit_note']) ?></p>
        </form>
    </div>
</div>

<script>
(function () {
    var form = document.getElementById('b2b-cp-form');
    if (!form) return;
    var msg = form.querySelector('.b2b-form__msg');
    var btn = form.querySelector('button[type="submit"]');

    function say(text, ok) {
        if (!msg) return;
        msg.textContent = text || '';
        msg.className = 'b2b-form__msg' + (text ? (ok ? ' is-ok' : ' is-error') : '');
    }

    // Clear the invalid mark as soon as the client fixes a mandatory field.
    ['buyer_name', 'buyer_idno', 'buyer_phone'].forEach(function (n) {
        var el = form.querySelector('[name="' + n + '"]');
        if (el) el.addEventListener('input', function () { el.classList.remove('is-invalid'); });
    });

    // IDNP / IDNO is strictly a 13-digit numeric code — no letters or symbols.
    var idnoEl = form.querySelector('[name="buyer_idno"]');
    if (idnoEl) {
        // Block non-digit keystrokes outright so nothing even flashes;
        // paste / autofill / drop are sanitised by the input handler below.
        idnoEl.addEventListener('beforeinput', function (e) {
            if (e.inputType === 'insertText' && e.data && /\D/.test(e.data)) {
                e.preventDefault();
            }
        });
        idnoEl.addEventListener('input', function () {
            var digits = idnoEl.value.replace(/\D/g, '').slice(0, 13);
            if (digits !== idnoEl.value) idnoEl.value = digits;
        });
    }

    form.addEventListener('submit', function (e) {
        e.preventDefault();
        say('');

        // Buyer name / IDNP-IDNO / phone are mandatory.
        var firstBad = null;
        ['buyer_name', 'buyer_idno', 'buyer_phone'].forEach(function (n) {
            var el = form.querySelector('[name="' + n + '"]');
            var empty = !el || el.value.trim() === '';
            if (el) el.classList.toggle('is-invalid', empty);
            if (empty && !firstBad) firstBad = el;
        });
        if (firstBad) {
            say(form.dataset.reqMsg || 'Completați câmpurile obligatorii.', false);
            firstBad.focus();
            return;
        }

        // IDNP / IDNO must be exactly 13 digits.
        var idno = form.querySelector('[name="buyer_idno"]');
        if (idno && !/^\d{13}$/.test(idno.value.trim())) {
            idno.classList.add('is-invalid');
            say(form.dataset.idnoMsg || 'IDNP/IDNO: 13 cifre.', false);
            idno.focus();
            return;
        }

        btn.disabled = true;

        var body = new URLSearchParams();
        body.set('tp', 'ste');
        body.set('fn', 'b2b_create_invoice');
        body.set('csrf', form.dataset.csrf);
        body.set('car_id', form.dataset.car);
        body.set('advance', form.querySelector('[name="price"]').value);
        body.set('currency', 'MDL'); // the document is always issued in MDL
        ['date', 'br', 'mo', 'vin', 'buyer_type', 'buyer_name', 'buyer_idno', 'buyer_phone'].forEach(function (n) {
            var el = form.querySelector('[name="' + n + '"]:checked') || form.querySelector('[name="' + n + '"]');
            body.set(n, el ? el.value : '');
        });

        fetch('/ajax.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/x-www-form-urlencoded; charset=UTF-8' },
            body: body.toString(),
            credentials: 'same-origin'
        })
            .then(function (r) { return r.json(); })
            .then(function (res) {
                if (res && res.ok && res.url) {
                    window.location.href = res.url; // open the generated proforma
                } else {
                    btn.disabled = false;
                    say((res && res.error) || 'Eroare.', false);
                }
            })
            .catch(function () { btn.disabled = false; say('Eroare de rețea.', false); });
    });
})();
</script>
