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
        foreach ($P['kr_params'] ?? [] as $p) {
            if (($p['param_key'] ?? '') === 'sea_freight_roro') { $roro = (float)$p['amount_eur']; break; }
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

        // All enabled KR fixed costs except RoRo (already in the base).
        foreach ($P['kr_params'] ?? [] as $p) {
            if ((int)($p['enabled'] ?? 0) !== 1) continue;
            $key = (string)($p['param_key'] ?? '');
            if ($key === 'sea_freight_roro') continue;
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
            'route'     => 'kr',
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
}

if (!function_exists('parsing_md_price_table')) {
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
                'total' => 'TOTAL price to your door',
                'estimate_note' => '* Sea freight is calculated exactly at the moment of loading onto the vessel. The total shown is an estimate.',
                'b2b_badge' => 'B2B partner price',
            ],
        ];
        $t = $L[$lang] ?? $L['ro'];
        $fmt = fn($n) => number_format($n, 0, '.', ' ');

        $rows = '';
        foreach ($breakdown['lines'] as $line) {
            $strike = !empty($line['strike']);
            // Zero lines are normally noise, but a cancelled fee is the point of the
            // B2B table: keep it struck through so the discount is visible.
            if ((float)$line['amount'] <= 0 && !$strike) continue;

            $label = $t[$line['key']] ?? ucfirst(str_replace('_', ' ', $line['key']));

            // `display` replaces the amount with text, e.g. an estimated range.
            $value = isset($line['display']) && $line['display'] !== null
                ? htmlspecialchars((string)$line['display'])
                : $fmt($line['amount']).' €';

            $rows .= '<tr'.($strike ? ' class="mdp-cancelled"' : '').'>'
                   . '<td class="mdp-label">'.htmlspecialchars($label).'</td>'
                   . '<td class="mdp-val">'.$value.'</td></tr>';
        }

        // Elegant card: gradient header, soft rows, pill-style values, bold total.
        $css = '<style>
            /* Show the table once per breakpoint (it is rendered in both the
               mobile flow and the desktop column). */
            .md-price-mobile-only{display:none;}
            @media (max-width:768px){
                .md-price-mobile-only{display:block;}
                .md-price-desktop-only{display:none;}
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
            .md-price-table .mdp-total .mdp-val{color:#e2001a;font-size:1.4rem;}
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

        $originFlag = (($breakdown['route'] ?? 'kr') === 'eu')
            ? '<img class="mdp-flag" src="/content/admin/page/parsing/media-parsing/flag-europe.svg" alt="EU">'
            : '<img class="mdp-flag" src="/content/admin/page/parsing/media-parsing/flag-korea.svg" alt="KR">';
        $route = '<span class="md-price-route">'
            . $originFlag
            . '<span class="mdp-path"><img class="mdp-car" src="/content/admin/page/parsing/media-parsing/car-calc.svg?v=3" alt=""></span>'
            . '<img class="mdp-flag" src="/content/admin/page/parsing/media-parsing/flag-md.svg" alt="MD">'
            . '</span>';

        // B2B badge above the table, estimate footnote below.
        $badge = !empty($breakdown['b2b'])
            ? '<div class="md-price-badge">'.htmlspecialchars($t['b2b_badge'] ?? '').'</div>'
            : '';
        $note = !empty($breakdown['estimated'])
            ? '<p class="md-price-note">'.htmlspecialchars($t['estimate_note'] ?? '').'</p>'
            : '';

        $tableHtml = '<div class="md-price-block open">'
            . '<div class="md-price-title md-price-title-static">'
            . '<span class="mdp-title-text">'.$route.'</span></div>'
            . '<div class="md-price-body">'
            . $badge
            . '<table class="md-price-table"><tbody>'.$rows
            . '<tr class="mdp-total"><td class="mdp-label">'.htmlspecialchars($t['total']).'</td>'
            . '<td class="mdp-val">'.$fmt($breakdown['total']).' €</td></tr>'
            . '</tbody></table>'
            . $note
            . '</div></div>';

        return $css.'<div style="clear:both"></div>'.$tableHtml;
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
