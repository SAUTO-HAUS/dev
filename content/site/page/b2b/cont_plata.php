<?php defined( '_DOIT' ) or die( 'Restricted access' );

/**
 * /{lang}/b2b/cont-plata?car=ID — payment-invoice form for a logged-in partner.
 * Included from cabinet.php (which has already loaded _layout.php, $user, $userId).
 *
 * Fields (Document / Auto / Cumpărător) are pre-filled from the car + the client
 * profile; on submit fn=b2b_create_invoice freezes them onto the proforma, then
 * notifies the Super Admin. The generated document reuses invoice_template.php.
 */

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
        'price' => 'Suma', 'currency' => 'Moneda', 'type' => 'Tip', 'fiz' => 'Persoană fizică', 'jur' => 'Persoană juridică',
        'name' => 'Nume / Denumire', 'idno' => 'IDNO / IDNP', 'phone' => 'Telefon',
        'submit' => 'Creează cont de plată', 'back' => '← Înapoi la mașină',
        'not_found' => 'Mașina nu a fost găsită.', 'restricted' => 'Restricționat conform planului B2B.',
    ],
    'ru' => [
        'title' => 'Создать счёт на оплату', 'sub' => 'Проверьте данные и нажмите Создать. Документ сформируется, администратор получит уведомление.',
        'doc' => 'Документ', 'auto' => 'Авто', 'buyer' => 'Покупатель',
        'date' => 'Дата', 'brand' => 'Марка', 'model' => 'Модель', 'vin' => 'VIN-код',
        'price' => 'Сумма', 'currency' => 'Валюта', 'type' => 'Тип', 'fiz' => 'Физическое лицо', 'jur' => 'Юридическое лицо',
        'name' => 'Имя / Название', 'idno' => 'IDNO / IDNP', 'phone' => 'Телефон',
        'submit' => 'Создать счёт на оплату', 'back' => '← Назад к авто',
        'not_found' => 'Автомобиль не найден.', 'restricted' => 'Ограничено по плану B2B.',
    ],
    'en' => [
        'title' => 'Create payment invoice', 'sub' => 'Check the details and press Create. The document is generated and the administrator is notified.',
        'doc' => 'Document', 'auto' => 'Car', 'buyer' => 'Buyer',
        'date' => 'Date', 'brand' => 'Brand', 'model' => 'Model', 'vin' => 'VIN code',
        'price' => 'Amount', 'currency' => 'Currency', 'type' => 'Type', 'fiz' => 'Individual', 'jur' => 'Legal entity',
        'name' => 'Name / Company', 'idno' => 'IDNO / IDNP', 'phone' => 'Phone',
        'submit' => 'Create payment invoice', 'back' => '← Back to the car',
        'not_found' => 'Car not found.', 'restricted' => 'Restricted by the B2B plan.',
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

// Prefill: car data from the catalog, buyer data from the client profile.
$brand = trim((string)($car['br_nm'] ?: str_replace('_', ' ', (string)$car['br'])));
$model = trim((string)($car['mo_nm'] ?: str_replace('_', ' ', (string)$car['mo'])));
$vin   = (string)($car['vin'] ?? '');
$price = (int)round(B2bInvoice::suggestedAdvance($car));

$buyerType  = ($user['person_type'] ?? 'individual') === 'company' ? 'jur' : 'fiz';
$buyerName  = (string)($user['full_name'] ?? '');
$buyerIdno  = (string)($user['idno'] ?? '');
$buyerPhone = B2bPhone::local((string)($user['phone_number'] ?? ''));

$csrf = b2b_esc(b2b_csrf_token());
$e    = fn($v) => b2b_esc($v);
?>
<div class="b2b-page b2b-cp">
    <a class="b2b-cp-back" href="/<?= $e($lang) ?>/ordercars/<?= (int)$carId ?>"><?= $e($t['back']) ?></a>

    <div class="b2b-card">
        <div class="b2b-card__head">
            <h1 class="b2b-card__ttl"><?= $e($t['title']) ?></h1>
            <p class="b2b-card__sub"><?= $e($t['sub']) ?></p>
        </div>

        <form class="b2b-form" id="b2b-cp-form" data-csrf="<?= $csrf ?>" data-car="<?= (int)$carId ?>" novalidate>

            <h2 class="b2b-cp-sec"><?= $e($t['doc']) ?></h2>
            <div class="b2b-field">
                <label for="cp-date"><?= $e($t['date']) ?></label>
                <input type="date" id="cp-date" name="date" value="<?= $e(date('Y-m-d')) ?>" />
            </div>

            <h2 class="b2b-cp-sec"><?= $e($t['auto']) ?></h2>
            <div class="b2b-field-row">
                <div class="b2b-field">
                    <label for="cp-br"><?= $e($t['brand']) ?></label>
                    <input type="text" id="cp-br" name="br" value="<?= $e($brand) ?>" maxlength="120" />
                </div>
                <div class="b2b-field">
                    <label for="cp-mo"><?= $e($t['model']) ?></label>
                    <input type="text" id="cp-mo" name="mo" value="<?= $e($model) ?>" maxlength="120" />
                </div>
            </div>
            <div class="b2b-field">
                <label for="cp-vin"><?= $e($t['vin']) ?></label>
                <input type="text" id="cp-vin" name="vin" value="<?= $e($vin) ?>" maxlength="32" />
            </div>
            <div class="b2b-field-row">
                <div class="b2b-field">
                    <label for="cp-price"><?= $e($t['price']) ?></label>
                    <input type="number" id="cp-price" name="price" min="1" step="1" value="<?= (int)$price ?>" required />
                </div>
                <div class="b2b-field">
                    <label for="cp-cur"><?= $e($t['currency']) ?></label>
                    <select id="cp-cur" name="currency">
                        <option value="EUR">EUR</option>
                        <option value="MDL">MDL</option>
                        <option value="USD">USD</option>
                    </select>
                </div>
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
                    <input type="text" id="cp-idno" name="buyer_idno" value="<?= $e($buyerIdno) ?>" maxlength="20" inputmode="numeric" />
                </div>
                <div class="b2b-field">
                    <label for="cp-phone"><?= $e($t['phone']) ?></label>
                    <input type="text" id="cp-phone" name="buyer_phone" value="<?= $e($buyerPhone) ?>" maxlength="32" />
                </div>
            </div>

            <div class="b2b-form__msg" role="alert" aria-live="polite"></div>

            <button type="submit" class="b2b-btn b2b-btn--primary b2b-btn--block"><?= $e($t['submit']) ?></button>
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

    form.addEventListener('submit', function (e) {
        e.preventDefault();
        say('');
        btn.disabled = true;

        var body = new URLSearchParams();
        body.set('tp', 'ste');
        body.set('fn', 'b2b_create_invoice');
        body.set('csrf', form.dataset.csrf);
        body.set('car_id', form.dataset.car);
        body.set('advance', form.querySelector('[name="price"]').value);
        body.set('currency', form.querySelector('[name="currency"]').value);
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
