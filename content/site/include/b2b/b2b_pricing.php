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
 */

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
     * @return array{0: bool, 1: int|null}
     */
    function b2b_effective_client(): array
    {
        if (function_exists('b2b_is_client') && b2b_is_client()) {
            return [true, (int)b2b_user_id()];
        }
        if (!empty($_SESSION['user_id']) && !empty($_GET['b2b_as'])) {
            $asId = (int)$_GET['b2b_as'];
            if ($asId > 0) {
                return [true, $asId];
            }
        }
        return [false, null];
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

        return ($source === 'encar')
            ? parsing_md_breakdown_kr($db, $prefx, $car, null, $isClient, $userId)
            : parsing_md_breakdown_eu($db, $prefx, $car, null, $isClient, $userId);
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
