<?php defined( '_DOIT' ) or die( 'Restricted access' );

/**
 * B2B action panel on the car page (spec 2.3.2 / 2.3.3):
 * generate proforma, send to Super Admin. Saving to the cabinet is done via the
 * site favourites heart, which mirrors into gh3sp_b2b_saved_cars for a partner.
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
        $esc  = fn($s) => htmlspecialchars((string)$s, ENT_QUOTES, 'UTF-8');

        // The car page is not a B2B page, so it does not load the module assets
        // on its own.
        include_once( _SITE_PAGE.'/b2b/_layout.php' );

        // One payment invoice per car per partner: if it already exists, the panel
        // links to it instead of offering to create a duplicate.
        $existing = B2bInvoice::findForCar(b2b_user_id(), $carId);
        if ($existing) {
            return b2b_assets().'
<div class="b2b-actions">
    <div class="b2b-actions__head">
        <h3 class="b2b-actions__ttl">'.$esc($t['title']).'</h3>
    </div>
    <p class="b2b-actions__hint">'.$esc($t['already_hint']).'</p>

    <a class="b2b-btn b2b-btn--primary b2b-actions__cta" href="'.$esc(B2bInvoice::path($existing)).'">'.$esc($t['invoice_view']).'</a>
</div>';
        }

        // One clear action: open the payment-invoice form (fills in the details,
        // generates the proforma AND notifies the Super Admin — the merged flow).
        $formUrl = '/'.$esc($lang).'/b2b/cont-plata?car='.(int)$carId;

        // Advance figure, same basis as the form: a flat MDL amount, or a percent
        // of the car price converted to MDL at the BNM rate.
        $advMdl = (int)round(B2bInvoice::suggestedAdvanceMdl($car));
        $advTxt = number_format($advMdl, 0, '.', ' ').' MDL';

        $advNote = '';
        if (B2bConfig::get('b2b_advance_mode', 'fixed') === 'percent') {
            $pct     = (float)B2bConfig::get('b2b_advance_percent', '10');
            $pctTxt  = rtrim(rtrim(sprintf('%.1f', $pct), '0'), '.');
            $advNote = sprintf($t['pct_note'], $pctTxt);
        }

        return b2b_assets().'
<div class="b2b-actions">
    <div class="b2b-actions__head">
        <h3 class="b2b-actions__ttl">'.$esc($t['title']).'</h3>
    </div>
    <p class="b2b-actions__hint">'.$esc($t['hint']).'</p>

    <div class="b2b-actions__adv">
        <span class="b2b-actions__adv-lbl">'.$esc($t['adv_label']).'</span>
        <span class="b2b-actions__adv-val">'.$esc($advTxt).'</span>
        '.($advNote !== '' ? '<span class="b2b-actions__adv-note">'.$esc($advNote).'</span>' : '').'
    </div>

    <a class="b2b-btn b2b-btn--primary b2b-actions__cta" href="'.$formUrl.'">'.$esc($t['invoice']).'</a>
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
                'title'     => 'Rezervă această mașină',
                'hint'      => 'Creează contul de plată și mașina e rezervată pentru tine. Fii primul!',
                'advance'   => 'Suma avansului',
                'adv_label' => 'Avans',
                'pct_note'  => '%s%% din prețul mașinii',
                'invoice_view' => 'Vezi contul de plată',
                'already_hint' => 'Aveți deja un cont de plată pentru această mașină.',
                'currency'  => 'Moneda',
                'invoice'   => 'Creează cont de plată',
                'send'      => 'Trimite cerere',
                'send_now'  => 'Trimite',
                'save'      => 'Salvează în cabinet',
                'comment'   => 'Comentariu (opțional)',
                'attach'    => 'Atașează ultimul cont de plată',
                'cancel'    => 'Anulează',
                'send_hint' => 'Cererea ajunge instant la administrator pentru confirmare.',
            ],
            'ru' => [
                'title'     => 'Забронируйте это авто',
                'hint'      => 'Создайте счёт на оплату — и машина закреплена за вами. Успейте первым!',
                'advance'   => 'Сумма аванса',
                'adv_label' => 'Аванс',
                'pct_note'  => '%s%% от цены авто',
                'invoice_view' => 'Смотреть счёт на оплату',
                'already_hint' => 'У вас уже есть счёт на оплату для этого авто.',
                'currency'  => 'Валюта',
                'invoice'   => 'Создать счёт на оплату',
                'send'      => 'Отправить заявку',
                'send_now'  => 'Отправить',
                'save'      => 'Сохранить в кабинет',
                'comment'   => 'Комментарий (необязательно)',
                'attach'    => 'Приложить последний счёт',
                'cancel'    => 'Отмена',
                'send_hint' => 'Заявка мгновенно поступит администратору для подтверждения.',
            ],
            'en' => [
                'title'     => 'Reserve this car',
                'hint'      => 'Create the payment invoice and the car is reserved for you. Be the first!',
                'advance'   => 'Advance amount',
                'adv_label' => 'Advance',
                'pct_note'  => '%s%% of the car price',
                'invoice_view' => 'View payment invoice',
                'already_hint' => 'You already have a payment invoice for this car.',
                'currency'  => 'Currency',
                'invoice'   => 'Create payment invoice',
                'send'      => 'Send request',
                'send_now'  => 'Send',
                'save'      => 'Save to cabinet',
                'comment'   => 'Comment (optional)',
                'attach'    => 'Attach the latest payment invoice',
                'cancel'    => 'Cancel',
                'send_hint' => 'The request reaches the administrator instantly for confirmation.',
            ],
        ];

        return $L[$lang] ?? $L['ro'];
    }

    /** Shown instead of the car when the region is not covered by the plan (spec 3.2). */
    /**
     * The partner's active gifts, shown on the car page under the offer
     * countdown. Same markup and classes as the cabinet list, so the two are
     * styled once and cannot drift apart.
     *
     * Not gated on the car having a B2B price: a gift is granted to the ACCOUNT
     * and reads "free with any car purchased from us", so it holds for whatever
     * the partner is looking at. Nothing is rendered for guests, or for a
     * partner who has no gifts.
     *
     * @param string $variant 'page-d' (desktop slot) or 'page-m' (mobile slot);
     *                        CSS shows exactly one, at the same 640px breakpoint
     *                        the countdown uses.
     */
    function b2b_gift_car_html(string $variant = 'page-d'): string
    {
        if (!function_exists('b2b_is_client') || !b2b_is_client()) {
            return '';
        }

        try {
            $gifts = \App\Services\B2b\B2bGift::forUser(b2b_user_id(), 5);
        } catch (\Throwable $e) {
            return ''; // table not migrated: the car page must not break
        }
        if (!$gifts) {
            return '';
        }

        include_once( _SITE_PAGE.'/b2b/_layout.php' );

        $lang = $_COOKIE['lang'] ?? 'ro';
        $t    = b2b_lang($lang);
        $esc  = fn($s) => htmlspecialchars((string)$s, ENT_QUOTES, 'UTF-8');

        $out = '<ul class="b2b-gifts b2b-gifts--car b2b-gifts--'.$esc($variant).'">';
        foreach ($gifts as $g) {
            $what = \App\Services\B2b\B2bGift::describe($g, $lang);
            $note = trim((string)($g['note'] ?? ''));

            // No "new" badge here: that state is the cabinet's notification, and
            // this block is about the perk itself.
            $out .= '<li class="b2b-gift">'
                  . '<span class="b2b-gift__ico" aria-hidden="true">&#127873;</span>'
                  . '<div class="b2b-gift__body">'
                  . '<p class="b2b-gift__what">'.$esc($what !== '' ? $what : $t['gift_generic']).'</p>'
                  . '<p class="b2b-gift__free">'.$esc($t['gift_free']).'</p>'
                  . ($note !== '' ? '<p class="b2b-gift__note">'.$esc($note).'</p>' : '')
                  . '</div>'
                  . '</li>';
        }

        return $out.'</ul>';
    }

    function b2b_restricted_html(string $lang): string
    {
        $L = [
            'ro' => ['ttl' => 'Acces restricționat', 'txt' => 'Restricționat conform planului tău. Nu aveți acces la licitațiile din această regiune.', 'btn' => 'Înapoi la catalog'],
            'ru' => ['ttl' => 'Доступ ограничен',   'txt' => 'Ограничено согласно вашему плану. У вас нет доступа к аукционам этого региона.', 'btn' => 'Назад в каталог'],
            'en' => ['ttl' => 'Access restricted',  'txt' => 'Restricted by your plan. You do not have access to auctions from this region.',  'btn' => 'Back to catalog'],
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
