<?php defined( '_DOIT' ) or die( 'Restricted access' );

/**
 * Loads all data needed to compute the "MD: price" (full landed cost in
 * Moldova) for parsing cars, and emits it as a window.PARSING_PRICING JSON
 * blob. The actual math lives in parsing.js (parsingCalcMd), which mirrors
 * the calculator at /calculator/calc so there is a single source of truth
 * for the customs formula.
 *
 * Include this once per page (ctlg / published / favorites) before parsing.js.
 * Requires $db and $prefx in scope.
 */

if (!function_exists('parsing_pricing_eur_rate')) {
    /**
     * EUR->MDL rate, identical strategy to the public calculator
     * (sauto.md/ro/calculator): live from BNM, fallback to the gh3sp_exchange
     * DB cache, then 19.50 as a last resort.
     */
    function parsing_pricing_eur_rate($db, $prefx): float
    {
        $eur_rate = 19.50;
        try {
            $bnm_xml = @simplexml_load_file(
                'https://bnm.md/ru/official_exchange_rates?get_xml=1&date=' . date('d.m.Y'),
                'SimpleXMLElement',
                LIBXML_NOCDATA
            );
            if ($bnm_xml) {
                foreach ($bnm_xml->Valute as $valute) {
                    if ((string)$valute->CharCode === 'EUR') {
                        $rate = floatval(str_replace(',', '.', (string)$valute->Value));
                        if ($rate > 0) $eur_rate = $rate;
                        break;
                    }
                }
            }
        } catch (Exception $e) {}

        // Fallback to DB cache if BNM fetch failed (rate still default).
        if ($eur_rate === 19.50) {
            try {
                $stmt = $db->prepare('SELECT `value` FROM '.$prefx.'_exchange WHERE `name` = "EUR" LIMIT 1');
                $stmt->execute();
                $row = $stmt->fetch(PDO::FETCH_ASSOC);
                if ($row && floatval($row['value']) > 0) $eur_rate = floatval($row['value']);
            } catch (Exception $e) {}
        }

        return $eur_rate;
    }
}

if (!function_exists('parsing_pricing_payload')) {
    /**
     * @param bool $b2b when true, the 4 partner-facing tables (commission, EU
     *   delivery, EU/KR fixed costs) are read from the gh3sp_b2b_* set instead of
     *   gh3sp_parsing_*. Korea markup, customs config and the EUR rate stay retail.
     * @param int|null $b2bUserId when set (and $b2b), each of the 4 tables uses the
     *   client's own rows if they exist, otherwise the global (b2b_user_id IS NULL)
     *   set. Fallback is per table, so a client can override just one table.
     */
    function parsing_pricing_payload($db, $prefx, bool $b2b = false, ?int $b2bUserId = null): array
    {
        static $cache = [];
        $ck = $b2b ? ('b2b:'.($b2bUserId > 0 ? $b2bUserId : 0)) : 'retail';
        if (isset($cache[$ck])) return $cache[$ck];

        // The 4 tables that have a B2B variant; everything else stays retail.
        $tp = $b2b ? $prefx.'_b2b' : $prefx.'_parsing';

        // Per-table fallback for B2B: the client's own rows when present, else the
        // shared global rows (b2b_user_id IS NULL). Retail tables have no such
        // column, so the fragment is empty and the query is unchanged.
        $scopeWhere = function (string $table) use ($db, $b2b, $b2bUserId): string {
            if (!$b2b) return '';
            if ($b2bUserId > 0) {
                try {
                    $q = $db->prepare('SELECT 1 FROM '.$table.' WHERE b2b_user_id = ? LIMIT 1');
                    $q->execute([$b2bUserId]);
                    if ($q->fetchColumn()) return ' WHERE b2b_user_id = '.(int)$b2bUserId;
                } catch (Exception $e) {}
            }
            return ' WHERE b2b_user_id IS NULL';
        };

        $out = [
            'eur_rate' => parsing_pricing_eur_rate($db, $prefx), // MDL per 1 EUR, live from BNM
            'hybrid_discount_full' => 25.0,
            'hybrid_discount_plugin' => 50.0,
            'damage_protection_rate' => 1.2,
            'excise_rates' => [],               // grid: fuel_type/capacity/age -> rate (MDL per cm3)
            'eu_delivery' => [],                // Europe delivery tiers
            'commission' => [],                 // shared commission tiers
            'eu_params' => [],                  // Europe fixed costs (EUR)
            'kr_params' => [],                  // Korea fixed costs (EUR)
            'kr_markup' => [],                  // Korea price markup tiers (EUR, added to car price)
            'us_params' => [],                  // America fixed costs (EUR)
            'us_markup' => [],                  // America price markup tiers (EUR, added to car price)
        ];

        // Calculator settings: discounts only. eur_rate comes from BNM above,
        // same as the public calculator (sauto.md/ro/calculator), NOT from the
        // calculator_settings static value.
        try {
            $stmt = $db->query('SELECT setting_key, setting_value FROM '.$prefx.'_calculator_settings');
            foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $r) {
                switch ($r['setting_key']) {
                    case 'hybrid_discount_full':   $out['hybrid_discount_full'] = (float)$r['setting_value']; break;
                    case 'hybrid_discount_plugin': $out['hybrid_discount_plugin'] = (float)$r['setting_value']; break;
                    case 'damage_protection_rate': $out['damage_protection_rate'] = (float)$r['setting_value']; break;
                }
            }
        } catch (Exception $e) {}

        // Excise grid (same table the calculator reads).
        try {
            $stmt = $db->query('SELECT fuel_type, capacity_min, capacity_max, age_min, age_max, rate
                FROM '.$prefx.'_calculator_excise_rates ORDER BY fuel_type, capacity_min, age_min');
            $out['excise_rates'] = $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (Exception $e) {}

        // Europe delivery tiers (B2B variant when $b2b, client rows when scoped).
        try {
            $tbl  = $tp.'_eu_tiers';
            $stmt = $db->query('SELECT price_from, price_to, delivery FROM '.$tbl.$scopeWhere($tbl).' ORDER BY sort_order, price_from');
            $out['eu_delivery'] = $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (Exception $e) {}

        // Shared commission tiers (B2B variant when $b2b, client rows when scoped).
        try {
            $tbl  = $tp.'_commission_tiers';
            $stmt = $db->query('SELECT price_from, price_to, commission FROM '.$tbl.$scopeWhere($tbl).' ORDER BY sort_order, price_from');
            $out['commission'] = $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (Exception $e) {}

        // Europe fixed cost params (B2B variant when $b2b; only enabled ones matter).
        try {
            $tbl  = $tp.'_eu_params';
            $stmt = $db->query('SELECT param_key, value_type, amount_eur, enabled FROM '.$tbl.$scopeWhere($tbl).' ORDER BY sort_order, id');
            $out['eu_params'] = $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (Exception $e) {}

        // Korea fixed cost params (B2B variant when $b2b, client rows when scoped).
        try {
            $tbl  = $tp.'_kr_params';
            $stmt = $db->query('SELECT param_key, value_type, amount_eur, enabled FROM '.$tbl.$scopeWhere($tbl).' ORDER BY sort_order, id');
            $out['kr_params'] = $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (Exception $e) {}

        // Korea price markup tiers (added on top of the EUR car price by band).
        try {
            $stmt = $db->query('SELECT price_from, price_to, markup FROM '.$prefx.'_parsing_kr_markup_tiers ORDER BY sort_order, price_from');
            $out['kr_markup'] = $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (Exception $e) {}

        // America fixed costs (B2B variant when $b2b, client rows when scoped).
        // Falls back to the retail table when the B2B one has no rows: before
        // add_b2b_us_params.sql is run there is nothing to read, and an empty array
        // here would silently price every American car at the bare car price.
        try {
            $tbl  = $tp.'_us_params';
            $stmt = $db->query('SELECT param_key, value_type, amount_eur, enabled FROM '.$tbl.$scopeWhere($tbl).' ORDER BY sort_order, id');
            $out['us_params'] = $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (Exception $e) {}
        if (!$out['us_params']) {
            try {
                $stmt = $db->query('SELECT param_key, value_type, amount_eur, enabled FROM '.$prefx.'_parsing_us_params ORDER BY sort_order, id');
                $out['us_params'] = $stmt->fetchAll(PDO::FETCH_ASSOC);
            } catch (Exception $e) {}
        }

        // America price markup tiers (same role as the Korea markup).
        try {
            $stmt = $db->query('SELECT price_from, price_to, markup FROM '.$prefx.'_parsing_us_markup_tiers ORDER BY sort_order, price_from');
            $out['us_markup'] = $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (Exception $e) {}

        $cache[$ck] = $out;
        return $out;
    }
}

if (!function_exists('parsing_price_override')) {
    /**
     * Applies a per-line price override to a computed amount.
     *
     * Used by the B2B module (content/site/include/b2b/b2b_pricing.php) to replace
     * the fees a dealer does not pay. Passing null (the default everywhere else)
     * leaves the amount untouched, so the public breakdown is unchanged.
     *
     * Modes:
     *   zero  - fee cancelled; the line stays visible, struck through at 0
     *   fixed - fee replaced by a flat value
     *   range - estimate; `min` drives the math (per the spec note on the
     *           orientation total) and `display` carries the text
     *
     * @param array<string, array>|null $overrides
     * @return array{amount: float, display: ?string, strike: bool}
     */
    function parsing_price_override(?array $overrides, string $key, float $amount): array
    {
        $out = ['amount' => $amount, 'display' => null, 'strike' => false];

        if (!$overrides || !isset($overrides[$key]) || !is_array($overrides[$key])) {
            return $out;
        }

        $rule = $overrides[$key];
        switch ($rule['mode'] ?? '') {
            case 'zero':
                $out['amount'] = 0.0;
                $out['strike'] = true;
                break;

            case 'fixed':
                $out['amount'] = (float)($rule['value'] ?? 0);
                break;

            case 'range':
                $min = (float)($rule['min'] ?? 0);
                $max = (float)($rule['max'] ?? 0);
                // The customs base and the total must use a concrete number;
                // the lower bound keeps the estimate honest (never understated
                // against the customer once the real freight is known).
                $out['amount']  = $min;
                $out['display'] = number_format($min, 0, '.', ' ') . ' € - ' . number_format($max, 0, '.', ' ') . ' €';
                break;
        }

        return $out;
    }
}

if (!function_exists('parsing_md_breakdown_kr')) {
    // Pick the tier whose [price_from, price_to] contains $priceEur.
    function parsing_tier_value(array $tiers, float $priceEur, string $field): float
    {
        foreach ($tiers as $t) {
            $from = (float)($t['price_from'] ?? 0);
            $toRaw = $t['price_to'] ?? null;
            $to = ($toRaw === null || $toRaw === '') ? INF : (float)$toRaw;
            if ($priceEur >= $from && $priceEur <= $to) return (float)($t[$field] ?? 0);
        }
        return 0.0;
    }

    // Korea (Encar) markup amount for a given EUR car price (0 if no tier matches).
    function parsing_kr_markup_amount($db, $prefx, float $priceEur): float
    {
        if ($priceEur <= 0) return 0.0;
        $P = parsing_pricing_payload($db, $prefx);
        return parsing_tier_value($P['kr_markup'] ?? [], $priceEur, 'markup');
    }

    // The DISPLAYED/published Korea price = source EUR price + its markup tier.
    function parsing_kr_marked_price($db, $prefx, float $priceEur): float
    {
        return $priceEur + parsing_kr_markup_amount($db, $prefx, $priceEur);
    }

    function parsing_find_excise_rate(array $rates, string $fuel, int $cap, int $age): float
    {
        foreach ($rates as $r) {
            if (($r['fuel_type'] ?? '') !== $fuel) continue;
            $cMin = (int)$r['capacity_min']; $cMax = (int)$r['capacity_max'];
            $aMin = (int)$r['age_min'];      $aMax = (int)$r['age_max'];
            $capOk = $cap >= $cMin && ($cMax === 0 || $cap <= $cMax);
            $ageOk = $age >= $aMin && ($aMax === 0 || $age <= $aMax);
            if ($capOk && $ageOk) return (float)$r['rate'];
        }
        return 0.0;
    }

    // Customs cost in EUR (excise + luxury + 0.4% fee) for a customs base in EUR.
    // Backwards-compatible total; use parsing_customs_parts() for the split lines.
    function parsing_customs_eur(array $P, float $baseEur, string $fuel, int $cap, int $year): float
    {
        $p = parsing_customs_parts($P, $baseEur, $fuel, $cap, $year);
        return $p['customs'] + $p['luxury'];
    }

    // Customs components in EUR, split so the breakdown can show the luxury tax on
    // its own line (as /adminsauto/calculator/calc does). Returns:
    //   ['customs' => excise + 0.4% fee, 'luxury' => luxury excise]
    function parsing_customs_parts(array $P, float $baseEur, string $fuel, int $cap, int $year): array
    {
        $rate = (float)($P['eur_rate'] ?? 19.5);
        $valueMdl = $baseEur * $rate;
        $age = $year ? ((int)date('Y') - $year) : 0;
        $fuel = strtolower($fuel);

        // Excise mirrors /adminsauto/calculator/calc exactly:
        //   - benzina / diesel: rate(base, cc, age) * cc, no discount
        //   - hybrid (full, petrol):  base petrol, FULL discount (~25%)
        //   - hybrid_plugin (petrol): base petrol, PLUGIN discount (~50%)
        //   - diesel_hybrid (plug-in diesel): base diesel, PLUGIN discount
        //   - electric: 0
        $exciseMdl = 0.0;
        if ($fuel !== 'electric' && $cap > 0) {
            $discPlugin = (float)($P['hybrid_discount_plugin'] ?? 0);
            $discFull   = (float)($P['hybrid_discount_full'] ?? 0);
            if ($fuel === 'diesel' || $fuel === 'benzina') {
                $exciseMdl = parsing_find_excise_rate($P['excise_rates'] ?? [], $fuel, $cap, $age) * $cap;
            } elseif ($fuel === 'hybrid') {
                // Full hybrid on petrol.
                $exciseMdl = parsing_find_excise_rate($P['excise_rates'] ?? [], 'benzina', $cap, $age) * $cap;
                $exciseMdl *= (1 - $discFull / 100);
            } elseif ($fuel === 'hybrid_plugin') {
                // Plug-in hybrid on petrol.
                $exciseMdl = parsing_find_excise_rate($P['excise_rates'] ?? [], 'benzina', $cap, $age) * $cap;
                $exciseMdl *= (1 - $discPlugin / 100);
            } elseif ($fuel === 'diesel_hybrid') {
                // Plug-in hybrid on diesel.
                $exciseMdl = parsing_find_excise_rate($P['excise_rates'] ?? [], 'diesel', $cap, $age) * $cap;
                $exciseMdl *= (1 - $discPlugin / 100);
            }
        }

        $v = $valueMdl; $luxMdl = 0.0;
        if ($v >= 600000 && $v <= 700000) $luxMdl = $v * 0.02;
        elseif ($v > 700000 && $v <= 800000) $luxMdl = $v * 0.03;
        elseif ($v > 800000 && $v <= 900000) $luxMdl = $v * 0.04;
        elseif ($v > 900000 && $v <= 1000000) $luxMdl = $v * 0.05;
        elseif ($v > 1000000 && $v <= 1200000) $luxMdl = $v * 0.06;
        elseif ($v > 1200000 && $v <= 1400000) $luxMdl = $v * 0.07;
        elseif ($v > 1400000 && $v <= 1600000) $luxMdl = $v * 0.08;
        elseif ($v > 1600000 && $v <= 1800000) $luxMdl = $v * 0.09;
        elseif ($v > 1800000) $luxMdl = $v * 0.10;

        $feeMdl = $valueMdl * 0.004;
        $maxFeeMdl = 1800 * $rate;
        if ($feeMdl > $maxFeeMdl) $feeMdl = $maxFeeMdl;

        return [
            'customs' => ($exciseMdl + $feeMdl) / $rate, // excise + 0.4% procedure fee
            'luxury'  => $luxMdl / $rate,                // luxury tax (0 below 600k MDL)
        ];
    }

    /**
     * Full landed-cost breakdown for a Korea (Encar) car. Returns an ordered
     * list of [key, amount_eur] lines plus the total, or null if not computable.
     * Customs base = car price + sea freight (RoRo). RoRo is NOT added again
     * as a separate fixed cost.
     *
     * @param array $car ['price_eur','fuel','capacity','year']
     * @param array<string, array>|null $overrides per-line overrides (B2B pricing);
     *        null = the standard public breakdown.
     */
    function parsing_md_breakdown_kr($db, $prefx, array $car, ?array $overrides = null, bool $b2b = false, ?int $b2bUserId = null): ?array
    {
        $priceEur = (float)($car['price_eur'] ?? 0);
        if ($priceEur <= 0) return null;

        $P = parsing_pricing_payload($db, $prefx, $b2b, $b2bUserId);

        // Korea price markup: a flat amount added on top of the converted car price
        // by price band (e.g. < 10000 → +300). The markup becomes the NEW car price
        // and the base for the whole MD breakdown.
        $markup = parsing_tier_value($P['kr_markup'] ?? [], $priceEur, 'markup');
        $priceEur += $markup;

        $roro = 0.0;
        $container = 0.0;
        $containerOn = false;
        foreach ($P['kr_params'] ?? [] as $p) {
            $k = (string)($p['param_key'] ?? '');
            if ($k === 'sea_freight_roro') { $roro = (float)$p['amount_eur']; }
            // The alternative freight the visitor may pick on the public table.
            elseif ($k === 'sea_freight_container') {
                $container   = (float)$p['amount_eur'];
                $containerOn = (int)($p['enabled'] ?? 0) === 1;
            }
        }

        // Applied BEFORE the customs base is built: sea freight is part of that base,
        // so overriding the printed line afterwards would leave customs computed on
        // the old freight and the total would not add up.
        $roroOv = parsing_price_override($overrides, 'sea_freight_roro', $roro);
        $roro   = $roroOv['amount'];

        $base = $priceEur + $roro;
        $cust = parsing_customs_parts($P, $base, (string)($car['fuel'] ?? ''), (int)($car['capacity'] ?? 0), (int)($car['year'] ?? 0));
        $commission = parsing_tier_value($P['commission'] ?? [], $priceEur, 'commission');

        $lines = [];
        $lines[] = ['key' => 'price_car', 'amount' => round($priceEur)];
        $lines[] = [
            'key'     => 'sea_freight_roro',
            'amount'  => round($roro),
            'display' => $roroOv['display'],
            'strike'  => $roroOv['strike'],
        ];
        $lines[] = ['key' => 'customs', 'amount' => round($cust['customs'])];
        if ($cust['luxury'] > 0) $lines[] = ['key' => 'luxury_tax', 'amount' => round($cust['luxury'])];

        // All enabled KR fixed costs except the two freight options (already in
        // the base, and only one of them is ever charged).
        $polish = 0.0;
        foreach ($P['kr_params'] ?? [] as $p) {
            if ((int)($p['enabled'] ?? 0) !== 1) continue;
            $key = (string)($p['param_key'] ?? '');
            if ($key === 'sea_freight_roro' || $key === 'sea_freight_container') continue;
            $amt = (float)$p['amount_eur'];
            if (($p['value_type'] ?? 'fixed') === 'percent') $amt = $priceEur * ($amt / 100);
            $ov = parsing_price_override($overrides, $key, $amt);
            $line = ['key' => $key, 'amount' => round($ov['amount']), 'display' => $ov['display'], 'strike' => $ov['strike']];
            // Marked so the public table can render it with a tick that takes it
            // out of the total; it is charged (ticked) by default.
            if ($key === 'polish_cleaning' && $ov['display'] === null && !$ov['strike']) {
                $line['optional'] = true;
                $polish = (float)$line['amount'];
            }
            $lines[] = $line;
        }

        $comOv = parsing_price_override($overrides, 'commission', $commission);
        $lines[] = ['key' => 'commission', 'amount' => round($comOv['amount']), 'display' => $comOv['display'], 'strike' => $comOv['strike']];

        $total = 0;
        foreach ($lines as $l) $total += $l['amount'];

        // Container variant: freight sits INSIDE the customs base, so the whole
        // customs calculation has to be redone rather than the total nudged by
        // the price difference. Computed here and handed to the view, which
        // swaps the numbers in the browser — the car's stored price stays the
        // RoRo one.
        $alt = null;
        if ($containerOn && $container > 0 && $roroOv['display'] === null && !$roroOv['strike']) {
            $cOv   = parsing_price_override($overrides, 'sea_freight_container', $container);
            $cFrgt = $cOv['amount'];
            $cCust = parsing_customs_parts($P, $priceEur + $cFrgt, (string)($car['fuel'] ?? ''),
                                           (int)($car['capacity'] ?? 0), (int)($car['year'] ?? 0));
            // Only offered while both variants agree on whether the luxury tax
            // applies: otherwise the rows on screen would stop adding up to the
            // total, since that row is printed only when it is charged.
            if (($cCust['luxury'] > 0) !== ($cust['luxury'] > 0)) {
                $cFrgt = null;
            }
            $alt = $cFrgt === null ? null : [
                'freight' => round($cFrgt),
                'customs' => round($cCust['customs']),
                'luxury'  => round($cCust['luxury']),
                // Same total, with the three affected lines replaced.
                'total'   => $total - round($roro) - round($cust['customs']) - round($cust['luxury'])
                           + round($cFrgt) + round($cCust['customs']) + round($cCust['luxury']),
            ];
        }

        return [
            'lines'     => $lines,
            'total'     => $total,
            'eur_rate'  => $P['eur_rate'],
            'route'     => 'kr',
            // What the public table may offer as a temporary recalculation.
            'freight_alt' => $alt,
            'polish'      => round($polish),
            // Derived here, not set by the caller, so a call site cannot apply the
            // dealer prices and forget to label them. True whenever the B2B tables
            // are used (global or per-client).
            'b2b'       => $b2b,
            'estimated' => $roroOv['display'] !== null, // total is an estimate: freight is a range
        ];
    }

    /**
     * Full landed-cost breakdown for a Europe car (OpenLane / eCarsTrade). Mirrors
     * parsing.js computeLanded() for the EU branch: customs base = car price +
     * road delivery (price-tier), plus customs, EU fixed costs and commission.
     *
     * @param array $car ['price_eur','fuel','capacity','year']
     * @param array<string, array>|null $overrides per-line overrides (B2B pricing);
     *        null = the standard public breakdown.
     */
    function parsing_md_breakdown_eu($db, $prefx, array $car, ?array $overrides = null, bool $b2b = false, ?int $b2bUserId = null): ?array
    {
        $priceEur = (float)($car['price_eur'] ?? 0);
        if ($priceEur <= 0) return null;

        $P = parsing_pricing_payload($db, $prefx, $b2b, $b2bUserId);

        // Europe delivery is a price-based tier (not a flat RoRo fee).
        $delivery = parsing_tier_value($P['eu_delivery'] ?? [], $priceEur, 'delivery');

        // Applied before the base for the same reason as RoRo on the KR route.
        $delOv    = parsing_price_override($overrides, 'eu_delivery', $delivery);
        $delivery = $delOv['amount'];

        $base = $priceEur + $delivery;
        $cust = parsing_customs_parts($P, $base, (string)($car['fuel'] ?? ''), (int)($car['capacity'] ?? 0), (int)($car['year'] ?? 0));
        $commission = parsing_tier_value($P['commission'] ?? [], $priceEur, 'commission');

        $lines = [];
        $lines[] = ['key' => 'price_car', 'amount' => round($priceEur)];
        $lines[] = [
            'key'     => 'eu_delivery',
            'amount'  => round($delivery),
            'display' => $delOv['display'],
            'strike'  => $delOv['strike'],
        ];
        $lines[] = ['key' => 'customs', 'amount' => round($cust['customs'])];
        if ($cust['luxury'] > 0) $lines[] = ['key' => 'luxury_tax', 'amount' => round($cust['luxury'])];

        // All enabled EU fixed costs.
        foreach ($P['eu_params'] ?? [] as $p) {
            if ((int)($p['enabled'] ?? 0) !== 1) continue;
            $key = (string)($p['param_key'] ?? '');
            $amt = (float)$p['amount_eur'];
            if (($p['value_type'] ?? 'fixed') === 'percent') $amt = $priceEur * ($amt / 100);
            $ov = parsing_price_override($overrides, $key, $amt);
            $lines[] = ['key' => $key, 'amount' => round($ov['amount']), 'display' => $ov['display'], 'strike' => $ov['strike']];
        }

        $comOv = parsing_price_override($overrides, 'commission', $commission);
        $lines[] = ['key' => 'commission', 'amount' => round($comOv['amount']), 'display' => $comOv['display'], 'strike' => $comOv['strike']];

        $total = 0;
        foreach ($lines as $l) $total += $l['amount'];

        return [
            'lines'     => $lines,
            'total'     => $total,
            'eur_rate'  => $P['eur_rate'],
            'route'     => 'eu',
            // See the KR route: derived, never set by the caller.
            'b2b'       => $b2b,
            'estimated' => $delOv['display'] !== null,
        ];
    }

    // America (AutoTrader) markup amount for a given EUR car price.
    function parsing_us_markup_amount($db, $prefx, float $priceEur): float
    {
        if ($priceEur <= 0) return 0.0;
        $P = parsing_pricing_payload($db, $prefx);
        return parsing_tier_value($P['us_markup'] ?? [], $priceEur, 'markup');
    }

    // The DISPLAYED/published America price = source EUR price + its markup tier.
    function parsing_us_marked_price($db, $prefx, float $priceEur): float
    {
        return $priceEur + parsing_us_markup_amount($db, $prefx, $priceEur);
    }

    /**
     * Full landed-cost breakdown for an America (AutoTrader) car. Structurally
     * identical to the Korea route — the client's cost list matches it line for
     * line: transport is part of the customs base, everything else is flat, and
     * customs + luxury tax are computed per car from the price.
     *
     * @param array $car ['price_eur','fuel','capacity','year']
     * @param array<string, array>|null $overrides per-line overrides (B2B pricing)
     */
    function parsing_md_breakdown_us($db, $prefx, array $car, ?array $overrides = null, bool $b2b = false, ?int $b2bUserId = null): ?array
    {
        $priceEur = (float)($car['price_eur'] ?? 0);
        if ($priceEur <= 0) return null;

        $P = parsing_pricing_payload($db, $prefx, $b2b, $b2bUserId);

        // Markup becomes the new car price and the base for the whole breakdown
        // (same rule as Korea). Percent costs — the local tax — are charged on
        // that marked price too: business decision, the 2% applies to the car
        // price as quoted to the customer, markup included.
        $markup = parsing_tier_value($P['us_markup'] ?? [], $priceEur, 'markup');
        $priceEur += $markup;

        $freight = 0.0;
        foreach ($P['us_params'] ?? [] as $p) {
            if (($p['param_key'] ?? '') === 'sea_freight') { $freight = (float)$p['amount_eur']; break; }
        }

        // Overridden before the customs base is built: freight is part of that
        // base, so patching the printed line afterwards would leave customs
        // computed on the old figure and the total would not add up.
        $frOv    = parsing_price_override($overrides, 'sea_freight', $freight);
        $freight = $frOv['amount'];

        $base = $priceEur + $freight;
        $cust = parsing_customs_parts($P, $base, (string)($car['fuel'] ?? ''), (int)($car['capacity'] ?? 0), (int)($car['year'] ?? 0));
        $commission = parsing_tier_value($P['commission'] ?? [], $priceEur, 'commission');

        $lines = [];
        $lines[] = ['key' => 'price_car', 'amount' => round($priceEur)];
        $lines[] = [
            'key'     => 'sea_freight',
            'amount'  => round($freight),
            'display' => $frOv['display'],
            'strike'  => $frOv['strike'],
        ];
        $lines[] = ['key' => 'customs', 'amount' => round($cust['customs'])];
        if ($cust['luxury'] > 0) $lines[] = ['key' => 'luxury_tax', 'amount' => round($cust['luxury'])];

        // All enabled America fixed costs except the freight already in the base.
        foreach ($P['us_params'] ?? [] as $p) {
            if ((int)($p['enabled'] ?? 0) !== 1) continue;
            $key = (string)($p['param_key'] ?? '');
            if ($key === 'sea_freight') continue;
            $amt = (float)$p['amount_eur'];
            if (($p['value_type'] ?? 'fixed') === 'percent') $amt = $priceEur * ($amt / 100);
            $ov = parsing_price_override($overrides, $key, $amt);
            $lines[] = ['key' => $key, 'amount' => round($ov['amount']), 'display' => $ov['display'], 'strike' => $ov['strike']];
        }

        $comOv = parsing_price_override($overrides, 'commission', $commission);
        $lines[] = ['key' => 'commission', 'amount' => round($comOv['amount']), 'display' => $comOv['display'], 'strike' => $comOv['strike']];

        $total = 0;
        foreach ($lines as $l) $total += $l['amount'];

        return [
            'lines'     => $lines,
            'total'     => $total,
            'eur_rate'  => $P['eur_rate'],
            'route'     => 'us',
            'b2b'       => $b2b,
            'estimated' => $frOv['display'] !== null,
        ];
    }

    /**
     * Pick the landed-cost route for a source. Every call site used to inline
     * `source === 'encar' ? kr : eu`, which silently sent a new source down the
     * Europe route — this keeps the mapping in ONE place.
     */
    function parsing_md_breakdown_for($db, $prefx, ?string $source, array $car, ?array $overrides = null, bool $b2b = false, ?int $b2bUserId = null): ?array
    {
        switch (strtolower(trim((string)$source))) {
            case 'encar':      return parsing_md_breakdown_kr($db, $prefx, $car, $overrides, $b2b, $b2bUserId);
            case 'autotrader': return parsing_md_breakdown_us($db, $prefx, $car, $overrides, $b2b, $b2bUserId);
            default:           return parsing_md_breakdown_eu($db, $prefx, $car, $overrides, $b2b, $b2bUserId);
        }
    }
}

if (!function_exists('parsing_md_price_table')) {

    /**
     * Behaviour of the two controls in the Korea table: the RoRo/Container
     * picker and the polish tick.
     *
     * Emitted once per request, and it works by delegation, because the block is
     * printed twice on a car page (mobile flow + desktop column) and both copies
     * must react. Every figure it needs is already on the block as a data-*
     * attribute — the browser only picks between numbers the server computed, it
     * never does customs arithmetic of its own.
     */
    function parsing_md_price_script(): string
    {
        static $done = false;
        if ($done) return '';
        $done = true;

        return '<script>(function(){
    if (window.__mdPriceOpts) return;
    window.__mdPriceOpts = 1;

    function fmt(n){ return String(Math.round(n)).replace(/\B(?=(\d{3})+(?!\d))/g, " ") + " €"; }
    // The headline price uses commas (parseCurr), the table uses spaces.
    function fmtTop(n){ return String(Math.round(n)).replace(/\B(?=(\d{3})+(?!\d))/g, ","); }

    // The big price at the top of the car page must never disagree with the
    // total right below it, so it follows the same switches. Mirrored ONLY
    // while the two start out equal: on a B2B page, or wherever the shown
    // price was set by hand, the headline is not this breakdown\'s total and
    // must be left alone.
    var topEls = null, mirror = null;
    function canMirror(block){
        if (mirror !== null) return mirror;
        topEls = document.querySelectorAll(".prc .val .i");
        var total = parseInt(block.dataset.total || "0", 10) || 0;
        mirror = topEls.length > 0 && total > 0;
        Array.prototype.forEach.call(topEls, function(el){
            var n = parseInt(String(el.textContent).replace(/[^0-9]/g, ""), 10);
            if (!n || n !== total) mirror = false;
        });
        return mirror;
    }

    function cell(block, key){
        var tr = block.querySelector(\'tr[data-mdp-key="\' + key + \'"]\');
        return tr ? tr.querySelector(".mdp-amt") : null;
    }

    function apply(block){
        var sel    = block.querySelector(".mdp-freight");
        var tick   = block.querySelector(".mdp-polish");
        var isCont = !!sel && sel.value === "container";
        var polish = parseInt(block.dataset.polish || "0", 10) || 0;

        var total = parseInt(block.dataset.total || "0", 10) || 0;
        if (isCont && block.dataset.altTotal) {
            total = parseInt(block.dataset.altTotal, 10) || total;
            var f = cell(block, "sea_freight_roro"); if (f) f.textContent = fmt(block.dataset.freight);
            var c = cell(block, "customs");          if (c) c.textContent = fmt(block.dataset.customs);
            var l = cell(block, "luxury_tax");       if (l) l.textContent = fmt(block.dataset.luxury);
        } else if (sel) {
            // Back to the canonical RoRo figures the page was rendered with.
            var f2 = cell(block, "sea_freight_roro"); if (f2) f2.textContent = f2.dataset.base;
            var c2 = cell(block, "customs");          if (c2) c2.textContent = c2.dataset.base;
            var l2 = cell(block, "luxury_tax");       if (l2) l2.textContent = l2.dataset.base;
        }

        if (tick && !tick.checked) { total -= polish; }
        var row = tick ? tick.closest("tr") : null;
        if (row) { row.classList.toggle("mdp-row-off", !tick.checked); }

        var tot = block.querySelector(".mdp-total .mdp-amt");
        if (tot) tot.textContent = fmt(total);

        if (canMirror(block)) {
            Array.prototype.forEach.call(topEls, function(el){ el.textContent = fmtTop(total); });
        }
    }

    // Remember the rendered figures once, so switching back is exact rather
    // than recomputed.
    function remember(block){
        ["sea_freight_roro", "customs", "luxury_tax"].forEach(function(k){
            var el = cell(block, k);
            if (el && !el.dataset.base) el.dataset.base = el.textContent;
        });
    }

    document.addEventListener("change", function(e){
        var el = e.target;
        if (!el || (!el.classList.contains("mdp-freight") && !el.classList.contains("mdp-polish"))) return;
        var block = el.closest(".md-price-block");
        if (!block) return;
        remember(block);
        apply(block);
        var note = block.querySelector(".mdp-container-note");
        if (note) {
            var sel = block.querySelector(".mdp-freight");
            note.hidden = !(sel && sel.value === "container");
        }
    });
})();</script>';
    }
    /**
     * Render the "price to your door" breakdown as an HTML table for the public
     * car page. $lang in {ro, ru, en}. Returns '' if breakdown is null.
     */
    function parsing_md_price_table(?array $breakdown, string $lang = 'ro'): string
    {
        if (!$breakdown || empty($breakdown['lines'])) return '';

        $L = [
            'ro' => [
                'title' => 'Preț Moldova',
                'price_car' => 'Preț automobil', 'sea_freight_roro' => 'Transport maritim (RoRo)',
                'eu_delivery' => 'Livrare din Europa',
                'export_declaration' => 'Declarație de export', 'bank_commission' => 'Comision bancar',
                'auction_commission' => 'Comision licitație', 'pollution_tax' => 'Taxă de poluare',
                'shipping_docs' => 'Documente transport',
                'customs' => 'Devamare', 'luxury_tax' => 'Taxă de lux', 'auction_fee_encar' => 'Taxă licitație EnCar',
                'delivery_incheon' => 'Livrare în port Incheon', 'inspection' => 'Inspecție auto',
                'broker_korea' => 'Serviciu broker Coreea', 'interpol_check' => 'Verificare Interpol',
                'recycling_tax' => 'Taxă de poluare', 'commission' => 'Comision',
                'sea_freight' => 'Transport', 'auction_fee' => 'Taxă licitație',
                'delivery_to_port' => 'Livrare în port', 'broker_service' => 'Serviciu broker',
                'local_tax' => 'Impozit local',
                'sea_freight_container' => 'Transport maritim (Container)',
                'polish_cleaning' => 'Polisare + curățare chimică',
                'container_note' => '* Cu containerul, livrarea ajunge cu aproximativ o lună mai târziu decât cu RoRo.',
                'total' => 'TOTAL preț până acasă',
                'estimate_note' => '* Transportul maritim se calculează exact la momentul îmbarcării pe navă. Totalul afișat este estimativ.',
                'b2b_badge' => 'Preț partener B2B',
            ],
            'ru' => [
                'title' => 'Цена Молдова',
                'price_car' => 'Цена автомобиля', 'sea_freight_roro' => 'Морская доставка (RoRo)',
                'eu_delivery' => 'Доставка из Европы',
                'export_declaration' => 'Экспортная декларация', 'bank_commission' => 'Банковская комиссия',
                'auction_commission' => 'Комиссия аукциона', 'pollution_tax' => 'Налог на загрязнение',
                'shipping_docs' => 'Транспортные документы',
                'customs' => 'Растаможка', 'luxury_tax' => 'Налог на роскошь', 'auction_fee_encar' => 'Комиссия аукциона EnCar',
                'delivery_incheon' => 'Доставка в порт Инчхон', 'inspection' => 'Осмотр авто',
                'broker_korea' => 'Услуга брокера Корея', 'interpol_check' => 'Проверка по Интерполу',
                'recycling_tax' => 'Налог на загрязнение', 'commission' => 'Комиссия',
                'sea_freight' => 'Транспорт', 'auction_fee' => 'Аукционный сбор',
                'delivery_to_port' => 'Доставка в порт', 'broker_service' => 'Услуга брокера',
                'local_tax' => 'Местный налог',
                'sea_freight_container' => 'Морская доставка (Контейнер)',
                'polish_cleaning' => 'Полировка + химчистка',
                'container_note' => '* Контейнером доставка приходит примерно на месяц позже, чем RoRo.',
                'total' => 'ИТОГО цена до дома',
                'estimate_note' => '* Морская доставка рассчитывается точно в момент погрузки на судно. Указанная сумма является ориентировочной.',
                'b2b_badge' => 'Цена партнёра B2B',
            ],
            'en' => [
                'title' => 'Price Moldova',
                'price_car' => 'Car price', 'sea_freight_roro' => 'Sea freight (RoRo)',
                'eu_delivery' => 'Delivery from Europe',
                'export_declaration' => 'Export declaration', 'bank_commission' => 'Bank commission',
                'auction_commission' => 'Auction commission', 'pollution_tax' => 'Pollution tax',
                'shipping_docs' => 'Shipping documents',
                'customs' => 'Customs clearance', 'luxury_tax' => 'Luxury tax', 'auction_fee_encar' => 'EnCar auction fee',
                'delivery_incheon' => 'Delivery to Incheon port', 'inspection' => 'Vehicle inspection',
                'broker_korea' => 'Korea broker service', 'interpol_check' => 'Interpol check',
                'recycling_tax' => 'Pollution tax', 'commission' => 'Commission',
                'sea_freight' => 'Transport', 'auction_fee' => 'Auction fee',
                'delivery_to_port' => 'Delivery to port', 'broker_service' => 'Broker service',
                'local_tax' => 'Local tax',
                'sea_freight_container' => 'Sea freight (Container)',
                'polish_cleaning' => 'Polishing + chemical cleaning',
                'container_note' => '* By container the car arrives roughly one month later than by RoRo.',
                'total' => 'TOTAL price to your door',
                'estimate_note' => '* Sea freight is calculated exactly at the moment of loading onto the vessel. The total shown is an estimate.',
                'b2b_badge' => 'B2B partner price',
            ],
        ];
        $t = $L[$lang] ?? $L['ro'];
        $fmt = fn($n) => number_format($n, 0, '.', ' ');

        // Korea only: the visitor may price the car with container freight instead
        // of RoRo, and drop the polish line. Both are a temporary recalculation on
        // screen — the price stored on the car is always the RoRo one, polish
        // included. Never offered on a table that is already showing text instead
        // of numbers, or the swap would have nothing to swap.
        $alt = $breakdown['freight_alt'] ?? null;

        $rows = '';
        foreach ($breakdown['lines'] as $line) {
            $strike = !empty($line['strike']);
            // Zero lines are normally noise, but a cancelled fee is the point of the
            // B2B table: keep it struck through so the discount is visible.
            if ((float)$line['amount'] <= 0 && !$strike) continue;

            $key   = (string)$line['key'];
            $label = $t[$key] ?? ucfirst(str_replace('_', ' ', $key));

            // `display` replaces the amount with text, e.g. an estimated range.
            $value = isset($line['display']) && $line['display'] !== null
                ? htmlspecialchars((string)$line['display'])
                : '<span class="mdp-amt">'.$fmt($line['amount']).' €</span>';

            // B2B: on a modified line, show the standard price beside this client's
            // own price. A cancelled fee (struck row) shows what it used to cost.
            if (isset($line['std_amount']) && !isset($line['display'])) {
                $std = (float)$line['std_amount'];
                if ($strike) {
                    if ($std > 0) { $value = '<span class="mdp-amt">'.$fmt($std).' €</span>'; }
                } elseif ($std > 0 && $std != (float)$line['amount']) {
                    $value = '<span class="mdp-old">'.$fmt($std).' €</span> '.$value;
                }
            }

            // The freight label becomes the RoRo/Container picker; polish gets a
            // tick that takes it out of the total. Both keep the plain label when
            // the data for the swap is not there.
            $cell = htmlspecialchars($label);
            if ($key === 'sea_freight_roro' && $alt) {
                // First option is the row's own label, the one the table has
                // always shown; only the alternative needed a new string. The
                // delay warning sits right under the picker that causes it, not
                // down by the total.
                $cell = '<select class="mdp-freight" aria-label="'.htmlspecialchars($label).'">'
                      . '<option value="roro">'.htmlspecialchars($label).'</option>'
                      . '<option value="container">'.htmlspecialchars($t['sea_freight_container'] ?? 'Container').'</option>'
                      . '</select>'
                      . '<span class="mdp-container-note" hidden>'
                      . htmlspecialchars($t['container_note'] ?? '').'</span>';
            } elseif (!empty($line['optional'])) {
                $cell = '<label class="mdp-optin"><input type="checkbox" class="mdp-polish" checked>'
                      . '<span>'.htmlspecialchars($label).'</span></label>';
            }

            $rows .= '<tr'.($strike ? ' class="mdp-cancelled"' : '').' data-mdp-key="'.htmlspecialchars($key).'">'
                   . '<td class="mdp-label">'.$cell.'</td>'
                   . '<td class="mdp-val">'.$value.'</td></tr>';
        }

        // Elegant card: gradient header, soft rows, pill-style values, bold total.
        $css = '<style>
            /* CARFAX button under the price table (AutoTrader cars). The report
               itself lives on carfax.ca, so this is a link, not a rendered block
               like the Encar/OpenLane reports that sit in the same slot. */
            .carfax-report-btn{
                display:flex; align-items:center; justify-content:center; gap:.45rem;
                margin:-18px 0 30px;
                padding:14px 18px;
                border:1px solid #efefef;
                border-radius:18px;
                background:#fff;
                box-shadow:0 10px 30px rgba(20,20,40,.07);
                color:#e2001a; font-weight:700; font-size:1rem; text-decoration:none;
                transition:border-color .15s, box-shadow .15s;
            }
            /* The wordmark carries its own width:250 height:37 attributes; CSS
               overrides them so it scales with the label instead of the viewport. */
            .carfax-report-btn svg{
                display:block; height:1.25em; width:auto;
            }
            .carfax-report-btn:hover{
                border-color:#e2001a;
                box-shadow:0 12px 34px rgba(226,0,26,.14);
            }

            /* Show the table once per breakpoint (it is rendered in both the
               mobile flow and the desktop column). */
            .md-price-mobile-only{display:none;}
            @media (max-width:768px){
                .md-price-mobile-only{display:block;}
                .md-price-desktop-only{display:none;}
                /* The negative top margin above tucks the button under the price
                   table on desktop; on a narrow screen the two stack edge to edge
                   and need the breathing room back. */
                .carfax-report-btn{margin-top:0;}
            }
            .md-price-block{
                margin:0 0 30px;
                font-family:inherit;
                background:#fff;
                border:1px solid #efefef;
                border-radius:18px;
                box-shadow:0 10px 30px rgba(20,20,40,.07);
                overflow:hidden;
                box-sizing:border-box;
            }
            .md-price-title{
                margin:0;
                padding:4px 28px;
                font-size:1.2rem;
                font-weight:bold;
                color:#fff;
                text-transform:uppercase;
                letter-spacing:.4px;
                background:linear-gradient(135deg,#e2001a 0%,#b30015 100%);
            }
            .md-price-table{width:100%;border-collapse:collapse;}
            .md-price-table td{
                padding:11px 28px;
                font-size:.97rem;
                line-height:1.25;
            }
            .md-price-table tbody tr{transition:background .15s;}
            .md-price-table tbody tr:nth-child(even){background:#fafafa;}
            .md-price-table tbody tr:hover{background:#fdeef0;}
            .md-price-table .mdp-label{color:#3a3a3a;font-weight:500;}
            .md-price-table .mdp-val{
                text-align:right;white-space:nowrap;
                font-weight:700;color:#1a1a1a;
            }
            /* Fee cancelled for B2B partners: shown struck through at 0 €. */
            .md-price-table tr.mdp-cancelled .mdp-label,
            .md-price-table tr.mdp-cancelled .mdp-val{
                text-decoration:line-through;
                color:#9a9a9a;
            }
            /* The two things the visitor can change: freight type and whether the
               polish is included. Drawn to sit in the row like the labels around
               them, not like a form dropped into a price list. */
            .md-price-table .mdp-freight{
                font:inherit;color:#3a3a3a;font-weight:500;
                max-width:100%;padding:4px 30px 4px 10px;
                border:1px solid #e6c4c8;border-radius:9px;background:#fff;
                -webkit-appearance:none;appearance:none;cursor:pointer;
                background-image:url("data:image/svg+xml,%3Csvg xmlns=%27http://www.w3.org/2000/svg%27 width=%2712%27 height=%278%27 viewBox=%270 0 12 8%27 fill=%27none%27%3E%3Cpath d=%27M1 1.5L6 6.5L11 1.5%27 stroke=%27%23e2001a%27 stroke-width=%272%27 stroke-linecap=%27round%27 stroke-linejoin=%27round%27/%3E%3C/svg%3E");
                background-repeat:no-repeat;background-position:right 10px center;
                transition:border-color .15s;
            }
            .md-price-table .mdp-freight:hover{border-color:#e2001a;}
            .md-price-table .mdp-freight:focus{outline:none;border-color:#e2001a;box-shadow:0 0 0 3px rgba(226,0,26,.12);}
            /* Delay warning, under the picker it belongs to. The [hidden] rule is
               more specific than the base one, so the attribute still wins. */
            .md-price-table .mdp-container-note{
                display:block;margin-top:7px;
                color:#8a8a8a;font-size:.76rem;line-height:1.35;font-weight:400;
                white-space:normal;
            }
            .md-price-table .mdp-container-note[hidden]{display:none;}
            .md-price-table .mdp-optin{
                display:inline-flex;align-items:center;gap:9px;
                cursor:pointer;color:#3a3a3a;font-weight:500;
            }
            .md-price-table .mdp-optin input{
                width:17px;height:17px;margin:0;flex:0 0 17px;
                accent-color:#e2001a;cursor:pointer;
            }
            /* Unticked: the row stays visible so the price can be put back. */
            .md-price-table tr.mdp-row-off .mdp-optin span,
            .md-price-table tr.mdp-row-off .mdp-val{
                text-decoration:line-through;color:#9a9a9a;
            }
            .md-price-badge{
                display:inline-block;margin:12px 28px 0;
                padding:4px 12px;border-radius:999px;
                background:#eafaf0;color:#0f7a3d;
                font-size:.78rem;font-weight:700;letter-spacing:.2px;
            }
            .md-price-note{
                margin:0;padding:10px 28px 16px;
                color:#8a8a8a;font-size:.78rem;line-height:1.4;
            }
            @media (max-width:600px){
                .md-price-badge{margin:10px 18px 0;}
                .md-price-note{padding:8px 18px 14px;font-size:.72rem;}
            }
            .md-price-table .mdp-total{background:#fff!important;}
            .md-price-table .mdp-total td{
                border-top:2px dashed #e2001a;
                font-size:1.25rem;
                font-weight:bold;
                padding-top:16px;padding-bottom:18px;
            }
            .md-price-table .mdp-total .mdp-label{color:#e2001a;text-transform:uppercase;letter-spacing:.3px;}
            .md-price-table .mdp-total .mdp-val{color:#1a1a1a;font-size:1.4rem;}
            /* Standard price struck through beside the partner B2B price
               (on every modified line and on the total). */
            .md-price-table .mdp-old{
                position:relative;display:inline-block;
                color:#6b6b6b;font-weight:600;font-size:.82rem;
                margin-right:7px;white-space:nowrap;
            }
            /* Diagonal red strike, same as the .o_val--b2b old price (not a plain
               horizontal line-through). */
            .md-price-table .mdp-old::after{
                content:"";position:absolute;left:0;right:0;top:50%;
                height:1.5px;border-radius:1px;margin-top:-.75px;
                background:linear-gradient(90deg, transparent, #CE3226 18%, #CE3226 82%, transparent);
                transform:rotate(-16deg);transform-origin:center;
            }
            .md-price-table .mdp-total .mdp-old{font-size:.9rem;}
            @media (max-width:600px){
                .md-price-block{border-radius:14px;}
                .md-price-title{font-size:1.05rem;padding:16px 18px;}
                .md-price-table td{font-size:.92rem;padding:10px 18px;}
                .md-price-table .mdp-total td{font-size:1.1rem;}
                .md-price-table .mdp-total .mdp-val{font-size:1.2rem;}
            }
            /* The title is a full-width clickable header; the table body below it
               toggles open/closed. Header stays visible always. */
            .md-price-title{
                position:relative;
                width:100%;
                display:flex;align-items:center;justify-content:center;gap:14px;
                cursor:pointer;
                border:none;
                text-align:center;
                font-family:inherit;
            }
            .md-price-title .mdp-title-text{display:inline-flex;align-items:center;gap:14px;}
            /* Themed route: KR flag ··car··> MD flag */
            .md-price-route{display:inline-flex;align-items:center;gap:7px;}
            .md-price-title .mdp-flag{
                width:34px;height:auto;flex:0 0 34px;
            }
            .md-price-route .mdp-path{
                display:inline-flex;align-items:center;
                color:rgba(255,255,255,.9);
            }
            .md-price-route .mdp-path:before,
            .md-price-route .mdp-path:after{
                content:"";width:38px;height:0;
                border-top:2px dashed rgba(255,255,255,.85);
            }
            .md-price-route .mdp-car{
                width:40px;height:auto;flex:0 0 40px;margin:0 2px;
                filter:brightness(0) invert(1); /* recolor the black SVG to white */
            }
            .md-price-title:after{
                content:"\\25BE";
                position:absolute;right:28px;top:50%;
                transform:translateY(-50%);
                font-size:2.2rem;line-height:1;
                transition:transform .2s;
            }
            .md-price-block.open .md-price-title:after{transform:translateY(-50%) rotate(180deg);}
            /* Static (always-open) header: no toggle arrow, no pointer cursor. */
            .md-price-title-static{cursor:default;}
            .md-price-title-static:after{display:none;}
            .md-price-body{display:none;}
            .md-price-block.open .md-price-body{display:block;}
            @media (max-width:600px){
                /* Left-align everything on mobile so the toggle arrow on the
                   right always has room; shrink icons so the title fits on one row. */
                .md-price-title{justify-content:flex-start;text-align:left;padding:6px 40px 6px 14px;gap:9px;font-size:.95rem;}
                .md-price-title:after{right:14px;font-size:1.7rem;}
                .md-price-title .mdp-flag{width:32px;flex:0 0 32px;}
                .md-price-title .mdp-car{width:42px;flex:0 0 42px;}
                .md-price-title .mdp-title-text{gap:8px;flex-wrap:nowrap;white-space:nowrap;}
                .md-price-route{gap:5px;}
                .md-price-route .mdp-path:before,
                .md-price-route .mdp-path:after{width:12px;}
                .md-price-block{margin:15px 0;} /* equal top/bottom on mobile */
            }
        </style>';

        // Origin flag of the route the breakdown was computed with. Defaulting to
        // Korea for anything that isn't Europe put a Korean flag on US cars.
        //
        // The 'us' key predates the move to Canada as the import country and is
        // still what parsing_md_breakdown_us() returns; AutoTrader is its only
        // user and those cars ship from Canada, so it flies the Canadian flag.
        $originFlags = [
            'eu' => '<img class="mdp-flag" src="/content/admin/page/parsing/media-parsing/flag-europe.svg" alt="EU">',
            'us' => '<img class="mdp-flag" src="/content/admin/page/parsing/media-parsing/flag-canada.svg" alt="CA">',
            'kr' => '<img class="mdp-flag" src="/content/admin/page/parsing/media-parsing/flag-korea.svg" alt="KR">',
        ];
        $originFlag = $originFlags[$breakdown['route'] ?? 'kr'] ?? $originFlags['kr'];
        $route = '<span class="md-price-route">'
            . $originFlag
            . '<span class="mdp-path"><img class="mdp-car" src="/content/admin/page/parsing/media-parsing/car-calc.svg?v=3" alt=""></span>'
            . '<img class="mdp-flag" src="/content/admin/page/parsing/media-parsing/flag-md.svg" alt="MD">'
            . '</span>';

        // Estimate footnote below the table.
        $note = !empty($breakdown['estimated'])
            ? '<p class="md-price-note">'.htmlspecialchars($t['estimate_note'] ?? '').'</p>'
            : '';
        // Every figure the swap needs, precomputed server-side: freight is part
        // of the customs base, so the browser must not try to derive the other
        // variant from the difference in freight alone.
        $data = ' data-total="'.(int)$breakdown['total'].'"';
        if ($alt) {
            $data .= ' data-alt-total="'.(int)$alt['total'].'"'
                   . ' data-freight="'.(int)$alt['freight'].'"'
                   . ' data-customs="'.(int)$alt['customs'].'"'
                   . ' data-luxury="'.(int)$alt['luxury'].'"';
        }
        if (!empty($breakdown['polish'])) {
            $data .= ' data-polish="'.(int)$breakdown['polish'].'"';
        }

        $tableHtml = '<div class="md-price-block open"'.$data.'>'
            . '<div class="md-price-title md-price-title-static">'
            . '<span class="mdp-title-text">'.$route.'</span></div>'
            . '<div class="md-price-body">'
            . '<table class="md-price-table"><tbody>'.$rows
            . '<tr class="mdp-total"><td class="mdp-label">'.htmlspecialchars($t['total']).'</td>'
            . '<td class="mdp-val">'
            . ((!empty($breakdown['retail_total']) && (float)$breakdown['retail_total'] > (float)$breakdown['total'])
                ? '<span class="mdp-old">'.$fmt($breakdown['retail_total']).' €</span> ' : '')
            . '<span class="mdp-amt">'.$fmt($breakdown['total']).' €</span></td></tr>'
            . '</tbody></table>'
            . $note
            . '</div></div>';

        return $css.parsing_md_price_script().'<div style="clear:both"></div>'.$tableHtml;
    }
}

if (!function_exists('parsing_fuel_code')) {
    function parsing_fuel_code(?string $val): string {
        $v = trim((string)$val);
        if ($v === '') return '';
        // Already an internal code.
        $codes = ['benzina','diesel','lpg','hybrid','hybrid_plugin','diesel_hybrid','gasoline_lpg','gasoline_cng','electric','other'];
        if (in_array($v, $codes, true)) return $v;
        $k = mb_strtolower($v, 'UTF-8');
        $map = [
            'дизель'=>'diesel','diesel'=>'diesel',
            'бензин'=>'benzina','petrol'=>'benzina','gasoline'=>'benzina','бензиновый'=>'benzina',
            'электро'=>'electric','электрический'=>'electric','electric'=>'electric',
            'гибрид'=>'hybrid','hybrid'=>'hybrid','hybrid (petrol/electric)'=>'hybrid',
            'гибрид (бензин / электрический)'=>'hybrid',
            'hybrid (diesel/electric)'=>'diesel_hybrid','гибрид (дизель / электрический)'=>'diesel_hybrid',
            'газ(гбо)'=>'gasoline_cng','natural gas'=>'gasoline_cng','cng'=>'gasoline_cng',
            'сжиженный нефтяной газ'=>'lpg','lpg'=>'lpg','газ'=>'lpg',
        ];
        return $map[$k] ?? $v;
    }
}

// Same idea for gearbox (RU/raw → internal code).
if (!function_exists('parsing_gear_code')) {
    function parsing_gear_code(?string $val): string {
        $v = trim((string)$val);
        if ($v === '') return '';
        if (in_array($v, ['automat','manual','semi-auto','cvt'], true)) return $v;
        $k = mb_strtolower($v, 'UTF-8');
        if (strpos($k,'автомат')!==false || strpos($k,'automat')!==false || strpos($k,'auto')!==false) return 'automat';
        if (strpos($k,'механ')!==false || strpos($k,'manual')!==false || strpos($k,'ручн')!==false) return 'manual';
        if (strpos($k,'cvt')!==false || strpos($k,'вариатор')!==false) return 'cvt';
        return $v;
    }
}

if (!function_exists('parsing_card_title')) {
    function parsing_card_title(array $c): string {
        $brand = trim((string)($c['brand'] ?? ''));
        $model = trim((string)($c['model'] ?? ''));
        if ($model === '') return trim($brand . ' ' . ($c['year'] ?? ''));
        // Cut the model at the first token that looks like engine/spec noise:
        // a number with a dot (2.0), kW/hp/TDi/TDI/d after digits, "Nd"/"5d".
        $words = preg_split('/\s+/', $model);
        $keep = [];
        foreach ($words as $w) {
            // stop at engine/displacement/power/door tokens
            if (preg_match('/^\d+[.,]\d+$/', $w)        // 2.0
                || preg_match('/\d+\s*(kw|hp|ps|tdi|tfsi|cdi|dci|hdi)/i', $w)
                || preg_match('/^\d{2,3}$/', $w)         // 30, 85 (trim level numbers)
                || preg_match('/^\d+d$/i', $w)) break;   // 5d
            $keep[] = $w;
            if (count($keep) >= 3) break;                // never more than 3 words
        }
        if (!$keep) $keep[] = $words[0];
        return trim($brand . ' ' . implode(' ', $keep));
    }
}

if (!function_exists('parsing_engine_liters')) {
    function parsing_engine_liters(array $c): string {
        $cc = (int)($c['engine_volume'] ?? 0);
        if ($cc >= 600 && $cc <= 9000) {
            return number_format($cc / 1000, 1, '.', '');
        }
        $hay = trim((string)($c['title_ro'] ?? '') . ' ' . ($c['model'] ?? '') . ' ' . ($c['title'] ?? ''));
        if (preg_match('/(?<![\d.])([1-8])[.,](\d)(?![\d.])/u', $hay, $m)) {
            return $m[1] . '.' . $m[2];
        }
        return '';
    }
}
