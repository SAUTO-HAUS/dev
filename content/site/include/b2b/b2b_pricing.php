<?php defined( '_DOIT' ) or die( 'Restricted access' );

/**
 * B2B preferential pricing (spec 2.2 / 1.2.B).
 *
 * SECURITY: the rules are applied server-side, inside the breakdown computation
 * (parsing_md_breakdown_kr / _eu). No request parameter enables them - the only
 * condition is a valid B2B session - so an ordinary visitor cannot obtain dealer
 * prices and a partner cannot alter them from the browser. Spec note 5 is
 * satisfied by construction, not by hiding values in CSS.
 *
 * Values are exactly the ones in the technical specification.
 */

if (!defined('B2B_PRICE_RULES_LOADED')) {
    define('B2B_PRICE_RULES_LOADED', 1);

    /**
     * Overrides keyed by the breakdown line `param_key`.
     *
     * Keys match the rows in gh3sp_parsing_kr_params / _eu_params and the
     * commission tiers. The Korea route uses all of them; the Europe route only
     * has `commission` and `pollution_tax`, the rest simply never appear there.
     */
    function b2b_price_rules(): array
    {
        return [
            // Cancelled fees
            'auction_fee_encar' => ['mode' => 'zero'],
            'inspection'        => ['mode' => 'zero'],
            'broker_korea'      => ['mode' => 'zero'],

            // Fixed overrides
            'recycling_tax'     => ['mode' => 'fixed', 'value' => 10],   // pollution tax, KR route
            'pollution_tax'     => ['mode' => 'fixed', 'value' => 10],   // pollution tax, EU route
            'commission'        => ['mode' => 'fixed', 'value' => 500],

            // Estimated range, settled when the car is loaded onto the vessel
            'sea_freight_roro'  => ['mode' => 'range', 'min' => 1750, 'max' => 2550],
        ];
    }

    /** Full rule set for an authenticated partner, null otherwise. */
    function b2b_price_overrides(): ?array
    {
        return (function_exists('b2b_is_client') && b2b_is_client()) ? b2b_price_rules() : null;
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

        $overrides = b2b_price_overrides();

        // The breakdown flags its own result with `b2b` when it receives
        // overrides, so the "B2B partner price" badge cannot be lost here.
        return ($source === 'encar')
            ? parsing_md_breakdown_kr($db, $prefx, $car, $overrides)
            : parsing_md_breakdown_eu($db, $prefx, $car, $overrides);
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
