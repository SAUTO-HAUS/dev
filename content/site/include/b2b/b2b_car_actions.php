<?php defined( '_DOIT' ) or die( 'Restricted access' );

/**
 * B2B action panel on the car page (spec 2.3.2 / 2.3.3):
 * generate proforma, send to Super Admin, save to cabinet.
 * Renders only for an authenticated partner; guests see the page unchanged.
 */

use App\Services\B2b\B2bConfig;
use App\Services\B2b\B2bInvoice;

if (!function_exists('b2b_car_actions_html')) {

    function b2b_car_actions_html(int $carId, array $car = []): string
    {
        if (!function_exists('b2b_is_client') || !b2b_is_client() || $carId <= 0) {
            return '';
        }

        $lang = $_COOKIE['lang'] ?? 'ro';
        $t    = b2b_actions_lang($lang);
        $user = b2b_user();
        $esc  = fn($s) => htmlspecialchars((string)$s, ENT_QUOTES, 'UTF-8');

        // The star must start in the right state, otherwise the first click would
        // try to save an already saved car.
        $isSaved = b2b_car_is_saved($carId);

        // Prefilled from the configured rules; the partner can overwrite it.
        $advance = number_format(B2bInvoice::suggestedAdvance($car), 0, '.', '');
        $csrf    = $esc(b2b_csrf_token());
        $company = $esc(\App\Services\B2b\B2bAuth::displayName($user));

        // The car page is not a B2B page, so it does not load the module assets
        // on its own.
        include_once( _SITE_PAGE.'/b2b/_layout.php' );

        return b2b_assets().'
<div class="b2b-actions" data-car-id="'.$carId.'" data-csrf="'.$csrf.'">
    <div class="b2b-actions__head">
        <span class="b2b-actions__badge">B2B</span>
        <span class="b2b-actions__company">'.$company.'</span>
    </div>

    <div class="b2b-actions__row">
        <span class="b2b-actions__label">'.$esc($t['advance']).'</span>
        <div class="b2b-actions__amount">
            <!-- Class-based, no ids: the JS scopes every lookup to the closest
                 .b2b-actions, so the panel stays safe to render more than once. -->
            <input type="number" class="b2b-advance" min="1" step="1" value="'.$advance.'" aria-label="'.$esc($t['advance']).'" />
            <select class="b2b-currency" aria-label="'.$esc($t['currency']).'">
                <option value="EUR">EUR</option>
                <option value="MDL">MDL</option>
                <option value="USD">USD</option>
            </select>
        </div>
    </div>

    <div class="b2b-actions__btns">
        <button type="button" class="b2b-btn b2b-btn--primary" data-b2b-action="invoice">'.$esc($t['invoice']).'</button>
        <button type="button" class="b2b-btn b2b-btn--ghost" data-b2b-action="request">'.$esc($t['send']).'</button>
        <button type="button" class="b2b-btn b2b-btn--icon'.($isSaved ? ' is-saved' : '').'" data-b2b-action="save" title="'.$esc($t['save']).'" aria-label="'.$esc($t['save']).'">&#9733;</button>
    </div>

    <div class="b2b-actions__msg" role="status" aria-live="polite"></div>

    <div class="b2b-modal" hidden>
        <div class="b2b-modal__backdrop" data-b2b-close></div>
        <div class="b2b-modal__box" role="dialog" aria-modal="true" aria-label="'.$esc($t['send']).'">
            <h3 class="b2b-modal__ttl">'.$esc($t['send']).'</h3>
            <p class="b2b-modal__hint">'.$esc($t['send_hint']).'</p>
            <textarea class="b2b-modal__comment" rows="4" maxlength="1000" placeholder="'.$esc($t['comment']).'"></textarea>
            <label class="b2b-modal__check">
                <input type="checkbox" class="b2b-attach-invoice" checked /> '.$esc($t['attach']).'
            </label>
            <div class="b2b-modal__btns">
                <button type="button" class="b2b-btn b2b-btn--ghost" data-b2b-close>'.$esc($t['cancel']).'</button>
                <button type="button" class="b2b-btn b2b-btn--primary" data-b2b-action="request-confirm">'.$esc($t['send_now']).'</button>
            </div>
        </div>
    </div>
</div>';
    }

    /** Cached per request so repeated renders do not re-query. */
    function b2b_car_is_saved(int $carId): bool
    {
        static $cache = [];

        if (isset($cache[$carId])) {
            return $cache[$carId];
        }

        try {
            $stmt = B2bConfig::db()->prepare(
                'SELECT id FROM '.B2bConfig::table('saved_cars')
                .' WHERE b2b_user_id = :uid AND car_id = :car LIMIT 1'
            );
            $stmt->execute([':uid' => b2b_user_id(), ':car' => $carId]);
            return $cache[$carId] = (bool)$stmt->fetchColumn();
        } catch (\Throwable $e) {
            return $cache[$carId] = false;
        }
    }

    function b2b_actions_lang(string $lang): array
    {
        $L = [
            'ro' => [
                'advance'   => 'Suma avansului',
                'currency'  => 'Moneda',
                'invoice'   => 'Generează cont plată (Avans)',
                'send'      => 'Transmite la Super Admin',
                'send_now'  => 'Trimite',
                'save'      => 'Salvează în cabinet',
                'comment'   => 'Comentariu (opțional)',
                'attach'    => 'Atașează ultima proformă generată',
                'cancel'    => 'Anulează',
                'send_hint' => 'Cererea ajunge instant la Super Admin pentru validarea achiziției.',
            ],
            'ru' => [
                'advance'   => 'Сумма аванса',
                'currency'  => 'Валюта',
                'invoice'   => 'Сформировать счёт (аванс)',
                'send'      => 'Отправить Супер Админу',
                'send_now'  => 'Отправить',
                'save'      => 'Сохранить в кабинет',
                'comment'   => 'Комментарий (необязательно)',
                'attach'    => 'Приложить последний счёт',
                'cancel'    => 'Отмена',
                'send_hint' => 'Заявка мгновенно поступит Супер Админу для подтверждения покупки.',
            ],
            'en' => [
                'advance'   => 'Advance amount',
                'currency'  => 'Currency',
                'invoice'   => 'Generate payment invoice (advance)',
                'send'      => 'Send to Super Admin',
                'send_now'  => 'Send',
                'save'      => 'Save to cabinet',
                'comment'   => 'Comment (optional)',
                'attach'    => 'Attach the latest generated invoice',
                'cancel'    => 'Cancel',
                'send_hint' => 'The request reaches the Super Admin instantly for purchase validation.',
            ],
        ];

        return $L[$lang] ?? $L['ro'];
    }

    /** Shown instead of the car when the region is not covered by the plan (spec 3.2). */
    function b2b_restricted_html(string $lang): string
    {
        $L = [
            'ro' => ['ttl' => 'Acces restricționat', 'txt' => 'Restricționat conform planului B2B. Nu aveți acces la licitațiile din această regiune.', 'btn' => 'Înapoi la catalog'],
            'ru' => ['ttl' => 'Доступ ограничен',   'txt' => 'Ограничено согласно вашему B2B-плану. У вас нет доступа к аукционам этого региона.', 'btn' => 'Назад в каталог'],
            'en' => ['ttl' => 'Access restricted',  'txt' => 'Restricted by your B2B plan. You do not have access to auctions from this region.',  'btn' => 'Back to catalog'],
        ];
        $t = $L[$lang] ?? $L['ro'];

        include_once( _SITE_PAGE.'/b2b/_layout.php' );

        return b2b_assets().'
<div class="b2b-restricted">
    <div class="b2b-restricted__ico">&#128274;</div>
    <h1 class="b2b-restricted__ttl">'.htmlspecialchars($t['ttl']).'</h1>
    <p class="b2b-restricted__txt">'.htmlspecialchars($t['txt']).'</p>
    <a class="b2b-btn b2b-btn--primary" href="/'.htmlspecialchars($lang).'/ordercars">'.htmlspecialchars($t['btn']).'</a>
</div>';
    }
}
