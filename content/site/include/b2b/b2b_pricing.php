<?php defined( '_DOIT' ) or die( 'Restricted access' );

/**
 * B2B preferential pricing (spec 2.2 / 1.2.B).
 *
 * SECURITY: applied server-side inside the breakdown computation
 * (parsing_md_breakdown_kr / _eu). No request parameter enables it - the only
 * condition is a valid B2B session - so an ordinary visitor cannot obtain dealer
 * prices and a partner cannot alter them from the browser.
 *
 * Two layers, both driven by the `$b2b = true` flag on the breakdown:
 *   - the GLOBAL B2B pricing tables (gh3sp_b2b_* rows with b2b_user_id IS NULL),
 *     edited in /adminsauto/b2b/pricing, shared by every partner;
 *   - optional PER-CLIENT tables (the same rows scoped to the partner's id),
 *     edited in /adminsauto/b2b/pricing?user=X. Fallback is per table: a partner
 *     with rows in a table uses them, otherwise the global set applies.
 *
 * Each layer also has a deadline (App\Services\B2b\B2bOffer), and the partner's
 * own deadline outranks the general one: while his runs he keeps his prices even
 * if the general offer has lapsed, and when his runs out he drops to the global
 * B2B prices — or to the public ones if the general offer has lapsed too. A
 * partner with no deadline of his own follows the general one. That cascade is
 * applied here, in b2b_effective_client(), so every call site — car page, catalog
 * cards, compare, invoice — inherits it without knowing about it.
 */

use App\Services\B2b\B2bOffer;

if (!defined('B2B_PRICE_RULES_LOADED')) {
    define('B2B_PRICE_RULES_LOADED', 1);

    /**
     * The partner whose pricing applies to the current view, as
     * [bool $isClient, int|null $userId].
     *
     * Normally this is the logged-in partner. As a Super Admin convenience, an
     * authenticated admin (identified by a PHP $_SESSION user — B2B clients never
     * have one, they use a selector/validator cookie) may append
     * ?b2b_as=<partner_id> to a car URL to preview exactly the price that partner
     * sees, e.g. by clicking the car from /adminsauto/b2b/requests. The gate is
     * the admin session, so an ordinary visitor cannot forge the parameter to
     * obtain dealer prices.
     *
     * The returned scope is already past the expiry cascade, so a partner whose
     * offer has run out is reported as [true, null] (global prices) or [false,
     * null] (public prices) exactly as if the admin had removed their tables.
     *
     * @return array{0: bool, 1: int|null}
     */
    function b2b_effective_client(): array
    {
        $id = 0;
        if (function_exists('b2b_is_client') && b2b_is_client()) {
            $id = (int)b2b_user_id();
        } elseif (!empty($_SESSION['user_id']) && !empty($_GET['b2b_as'])) {
            $id = (int)$_GET['b2b_as'];
        }
        if ($id <= 0) {
            return [false, null];
        }

        try {
            [$useB2b, $scopeId] = B2bOffer::scopeFor($id);
        } catch (\Throwable $e) {
            // Expiry table missing: keep serving the prices rather than silently
            // repricing every partner at retail.
            return [true, $id];
        }

        return $useB2b ? [true, $scopeId] : [false, null];
    }

    /**
     * Deadline now counting down for this visitor, as a unix timestamp, or null
     * when there is none (guest, or an offer with no time limit).
     */
    function b2b_offer_deadline(): ?int
    {
        $id = 0;
        if (function_exists('b2b_is_client') && b2b_is_client()) {
            $id = (int)b2b_user_id();
        } elseif (!empty($_SESSION['user_id']) && !empty($_GET['b2b_as'])) {
            $id = (int)$_GET['b2b_as'];
        }
        if ($id <= 0) {
            return null;
        }

        try {
            [$useB2b, , $ts] = B2bOffer::scopeFor($id);
        } catch (\Throwable $e) {
            return null;
        }

        return $useB2b ? $ts : null;
    }

    /**
     * Whether a car is priced from the B2B tables at all.
     *
     * Only cars imported through parsing get a landed-cost breakdown, so only
     * they can carry a preferential price. A car from own stock keeps the public
     * price for partners too — nothing about it expires. Same condition as the
     * one b2b_prices_for_cars() uses to build its map.
     */
    function b2b_car_is_priced(array $car): bool
    {
        return !empty($car['parsing_id'])
            && in_array((string)($car['parsing_source'] ?? ''),
                        ['encar', 'openlane', 'ecarstrade', 'auto1', 'autotrader'], true);
    }

    /**
     * Countdown badge for the partner's current offer, or '' when there is
     * nothing to count down.
     *
     * Granularity follows the remaining time: days, then hours under a day, then
     * minutes under an hour. The initial text is rendered here so the badge never
     * flashes empty; b2b-offer-timer in sitescripts.js keeps it ticking and
     * reloads the page once it hits zero, since the prices change at that moment.
     *
     * All wording (including the plural forms) is passed as data attributes — the
     * JS carries no strings, same convention as the admin pricing editor.
     *
     * @param string $variant 'card' on catalog cards, 'page' on the car page
     */
    function b2b_offer_timer_html(string $variant = 'card'): string
    {
        $ts = b2b_offer_deadline();
        if ($ts === null) {
            return '';
        }

        $left = $ts - time();
        if ($left <= 0) {
            return ''; // already lapsed: prices are public again, nothing to show
        }

        $lang = $_COOKIE['lang'] ?? 'ro';
        if (!in_array($lang, ['ro', 'ru', 'en'], true)) {
            $lang = 'ro';
        }

        // [label, [day forms], [hour forms], [minute forms]] — three plural forms
        // per unit (ro: 1 / 2-19 / 20+, ru: 1 / 2-4 / 5+, en: 1 / many / many).
        // Wording and plural rules live in B2bOffer, so this badge and the
        // countdown in the admin pricing editor can never word it differently.
        $label = B2bOffer::label($lang);
        [$dForms, $hForms, $mForms] = B2bOffer::unitForms($lang);
        $text = B2bOffer::humanize($left, $lang);

        return '<span class="b2b-offer-timer b2b-offer-timer--'.$variant.'"'
             . ' data-b2b-offer-end="'.(int)$ts.'"'
             . ' data-lang="'.htmlspecialchars($lang, ENT_QUOTES, 'UTF-8').'"'
             . ' data-d="'.htmlspecialchars(implode('|', $dForms), ENT_QUOTES, 'UTF-8').'"'
             . ' data-h="'.htmlspecialchars(implode('|', $hForms), ENT_QUOTES, 'UTF-8').'"'
             . ' data-m="'.htmlspecialchars(implode('|', $mForms), ENT_QUOTES, 'UTF-8').'">'
             . '<span class="b2b-offer-timer__lbl">'.htmlspecialchars($label, ENT_QUOTES, 'UTF-8').'</span>'
             . '<strong class="b2b-offer-timer__val">'.htmlspecialchars($text, ENT_QUOTES, 'UTF-8').'</strong>'
             . '</span>';
    }


    /**
     * Breakdown for a parsing car with the prices that apply to the current
     * visitor. Single computation point shared by the car page and the catalog
     * cards, so the two cannot diverge.
     *
     * @param string $source parsing_source ('encar' => KR route, otherwise EU)
     * @param array  $car    ['price_eur','fuel','capacity','year']
     */
    function b2b_breakdown_for(string $source, array $car): ?array
    {
        global $db, $prefx;

        // CRITICAL: only a logged-in partner (or an admin previewing as one) gets
        // B2B pricing. A guest must get the unchanged retail breakdown, so this is
        // called from the car page for everyone but only switches tables for a
        // partner. The $b2b flag gates the table set; the user id selects that
        // partner's own tables when present.
        [$isClient, $userId] = b2b_effective_client();

        return parsing_md_breakdown_for($db, $prefx, $source, $car, null, $isClient, $userId);
    }

    /**
     * Catalog fuel codes -> excise calculator codes. The hybrid sub-type matters
     * (different excise base and discount), so pih/pid are not collapsed to
     * 'hybrid'.
     */
    function b2b_fuel_code(?string $catalogFuel): string
    {
        $map = [
            'gsl' => 'benzina', 'gmn' => 'benzina', 'gpn' => 'benzina', 'gas' => 'benzina',
            'dsl' => 'diesel',
            'hbd' => 'hybrid', 'pih' => 'hybrid_plugin', 'pid' => 'diesel_hybrid',
            'elc' => 'electric',
        ];
        return $map[(string)$catalogFuel] ?? '';
    }

    /**
     * B2B prices for a set of catalog rows, in one query.
     *
     * car_ctlg.prc holds the public landed price, so without this a partner would
     * see higher prices in the grid than on the car page. Recomputed only for
     * parsing cars (others have no breakdown) and only for a B2B session.
     *
     * @param array<int, array<string, mixed>> $rows gh3sp_car_ctlg rows
     * @return array<int, int> car_id => B2B price (EUR)
     */
    function b2b_prices_for_cars(array $rows): array
    {
        global $db, $prefx;

        // Same audience gate as the car page: a real partner, or an admin previewing
        // as one via ?b2b_as. Guests get retail (empty map).
        [$isClientView] = b2b_effective_client();
        if (!$isClientView || !$rows) {
            return [];
        }

        $byParsingId = [];
        foreach ($rows as $r) {
            $pid = (int)($r['parsing_id'] ?? 0);
            $src = (string)($r['parsing_source'] ?? '');
            if ($pid > 0 && in_array($src, ['encar', 'openlane', 'ecarstrade', 'auto1', 'autotrader'], true)) {
                $byParsingId[$pid] = $r;
            }
        }
        if (!$byParsingId) {
            return [];
        }

        // One SELECT for the whole card set, not one per card.
        $ids = array_keys($byParsingId);
        $placeholders = implode(',', array_fill(0, count($ids), '?'));

        try {
            $stmt = $db->prepare(
                'SELECT id, price_eur FROM '.$prefx.'_parsing_cars WHERE id IN ('.$placeholders.')'
            );
            $stmt->execute($ids);
            $sourcePrices = $stmt->fetchAll(PDO::FETCH_KEY_PAIR) ?: [];
        } catch (Exception $e) {
            return [];
        }

        $out = [];
        foreach ($byParsingId as $pid => $r) {
            $priceEur = (float)($sourcePrices[$pid] ?? 0);
            if ($priceEur <= 0) continue;

            $bd = b2b_breakdown_for((string)$r['parsing_source'], [
                'price_eur' => $priceEur,
                'fuel'      => b2b_fuel_code($r['fl'] ?? null),
                'capacity'  => (int)($r['vol'] ?? 0),
                'year'      => (int)($r['yr'] ?? 0),
            ]);

            if ($bd !== null) {
                $out[(int)$r['id']] = (int)$bd['total'];
            }
        }

        return $out;
    }
}
