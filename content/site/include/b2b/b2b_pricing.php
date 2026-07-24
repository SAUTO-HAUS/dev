<?php defined( '_DOIT' ) or die( 'Restricted access' );

/**
 * B2B preferential pricing (spec 2.2 / 1.2.B).
 *
 * SECURITY: applied server-side inside the breakdown computation
 * (parsing_md_breakdown_kr / _eu). No request parameter enables it - the only
 * condition is a valid B2B session - so an ordinary visitor cannot obtain dealer
 * prices and a partner cannot alter them from the browser.
 *
 * Two layers:
 *   - the B2B pricing TABLES (gh3sp_b2b_*), edited in /adminsauto/b2b/pricing,
 *     applied via the `$b2b = true` flag on the breakdown (global for every partner);
 *   - optional PER-CLIENT overrides (b2b_price_overrides), layered on top.
 */

if (!defined('B2B_PRICE_RULES_LOADED')) {
    define('B2B_PRICE_RULES_LOADED', 1);

    /**
     * Per-client price overrides for the logged-in partner, keyed by breakdown
     * `param_key` (commission / eu_delivery / sea_freight_roro). null when there
     * is no partner or no override — the global B2B tables then apply as-is.
     *
     * Each override is a flat 'fixed' value that plugs straight into
     * parsing_price_override(), layered on top of the global B2B tables.
     */
    function b2b_price_overrides(): ?array
    {
        global $db, $prefx;

        if (!function_exists('b2b_is_client') || !b2b_is_client()) {
            return null;
        }
        $uid = b2b_user_id();
        if ($uid <= 0) {
            return null;
        }

        static $cache = [];
        if (array_key_exists($uid, $cache)) {
            return $cache[$uid];
        }

        $out = null;
        try {
            $stmt = $db->prepare('SELECT param_key, amount FROM '.$prefx.'_b2b_price_overrides WHERE b2b_user_id = ?');
            $stmt->execute([$uid]);
            foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $r) {
                $out[(string)$r['param_key']] = ['mode' => 'fixed', 'value' => (float)$r['amount']];
            }
        } catch (\Throwable $e) {
            // Table not migrated yet: fall back to the global B2B prices.
            $out = null;
        }

        return $cache[$uid] = $out;
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

        // CRITICAL: only a logged-in partner gets B2B pricing. A guest must get
        // the unchanged retail breakdown, so this is called from the car page for
        // everyone but only switches tables for a partner. The $b2b flag gates
        // BOTH the table set and the per-client override.
        $isClient  = function_exists('b2b_is_client') && b2b_is_client();
        $overrides = $isClient ? b2b_price_overrides() : null;

        return ($source === 'encar')
            ? parsing_md_breakdown_kr($db, $prefx, $car, $overrides, $isClient)
            : parsing_md_breakdown_eu($db, $prefx, $car, $overrides, $isClient);
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

        if (!function_exists('b2b_is_client') || !b2b_is_client() || !$rows) {
            return [];
        }

        $byParsingId = [];
        foreach ($rows as $r) {
            $pid = (int)($r['parsing_id'] ?? 0);
            $src = (string)($r['parsing_source'] ?? '');
            if ($pid > 0 && in_array($src, ['encar', 'openlane', 'ecarstrade', 'auto1'], true)) {
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
