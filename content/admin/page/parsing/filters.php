<?php defined( '_DOIT' ) or die( 'Restricted access' );

include_once _ADM_PAGE.'/parsing/parsing_lang.php';
$t = $parsing_lang;

// Access — ids configured in include/parsing_access.php.
require_once(_ADM_INCL.'/parsing_access.php');
if (!parsing_has_access($user_id ?? 0)) {
    echo '<span class="err">'.$t['access_denied'].'</span>';
    return;
}

$parsingEncarOnly = parsing_is_encar_only($user_id ?? 0);
if ($parsingEncarOnly) {
    echo '<style>#sp-ecarstrade,#sp-openlane,.link-direct-bar,'
        .'.parsing-tabs .tab[href$="/parsing/published"],'
        .'.parsing-tabs .tab[href$="/parsing/settings"],'
        .'.parsing-tabs .tab-favorites{display:none !important;}</style>';
}

$savedFilters = [];
try {
    // Self-create the per-filter publish cap column so the card input works even
    // before the first save (no migration runner in this project).
    try {
        $col = $db->query("SHOW COLUMNS FROM {$prefx}_parsing_filters LIKE 'publish_limit'");
        if ($col && $col->rowCount() === 0) {
            $db->exec("ALTER TABLE {$prefx}_parsing_filters ADD COLUMN `publish_limit` INT(11) NOT NULL DEFAULT 0");
        }
    } catch (Exception $e) { /* best-effort */ }
    // Adaptive backoff counter (consecutive runs that imported 0 new cars).
    try {
        $col = $db->query("SHOW COLUMNS FROM {$prefx}_parsing_filters LIKE 'idle_runs'");
        if ($col && $col->rowCount() === 0) {
            $db->exec("ALTER TABLE {$prefx}_parsing_filters ADD COLUMN `idle_runs` INT(11) NOT NULL DEFAULT 0");
        }
    } catch (Exception $e) { /* best-effort */ }

    // published_count reflects the REAL car state (parsing_cars.status), not the
    // publish queue. A car published manually via Edit becomes status="published"
    // but its queue job stays "failed" — counting the queue showed 24/25 when the
    // site actually had 25. Count the cars themselves so manual + auto both show up.
    $stmt = $db->prepare('SELECT f.*, (
            SELECT COUNT(*) FROM '.$prefx.'_parsing_cars c
            WHERE c.filter_id = f.id
        ) AS imported_count, (
            SELECT COUNT(*) FROM '.$prefx.'_parsing_cars c
            WHERE c.filter_id = f.id AND c.status = "published"
        ) AS published_count, (
            SELECT COUNT(*) FROM '.$prefx.'_parsing_cars c
            WHERE c.filter_id = f.id AND c.status = "unavailable"
        ) AS sold_count
        FROM '.$prefx.'_parsing_filters f ORDER BY f.id DESC');
    $stmt->execute();
    $savedFilters = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (Exception $e) {

}

$it_br = [];
try {
    $pdo = (new \App\Db\Brand())->getBrands();
    foreach ($pdo as $r) {
        $it_br[$r['br']] = $r['br_nm'];
    }
} catch (Exception $e) {
}

// Brand options for e-CarsTrade / OPENLane (sauto DB brands).
$brandOptionsSauto = '<option value="">'.$t['opt_all'].'</option>';
foreach ($it_br as $brCode => $brNm) {
    $brandOptionsSauto .= '<option value="'.htmlspecialchars($brCode).'" data-br-nm="'.htmlspecialchars($brNm).'">'.htmlspecialchars($brNm).'</option>';
}

// Brand + model options for Encar come directly from encar_taxonomy.json.
// The keys in that file are the exact strings the Encar API expects.
$encarTaxonomy = null;
$encarTaxonomyFile = __DIR__ . '/../../../../App/Services/Parsing/Adapters/encar_taxonomy.json';
if (file_exists($encarTaxonomyFile)) {
    $encarTaxonomy = json_decode(file_get_contents($encarTaxonomyFile), true);
}

$encarBrandsForJs = [];  // { key: { label, models: [{key, label}] } } — used by JS
$brandOptionsEncar = '<option value="">'.$t['opt_all'].'</option>';

// Encar's EngName is mostly clean but a few entries arrive glued or all-caps
// ("Santafe", "AVANTE", "Ioniq5"). Tidy the display label without touching the
// taxonomy file (which the weekly cron regenerates). Known fixes win; otherwise
// apply light rules: split a trailing digit off a name ("Ioniq5" -> "Ioniq 5").
$encarPrettyName = function (string $name): string {
    static $fixes = [
        'Santafe'   => 'Santa Fe',
        'AVANTE'    => 'Avante',
        'Maxcruz'   => 'Max Cruz',
        'MarkX'     => 'Mark X',
        'SpaceTourer' => 'Space Tourer',
        // Glued / abbreviated brand names from Encar taxonomy.
        'Astonmartin' => 'Aston Martin',
        'Landrover'   => 'Land Rover',
        'Rollsroyce'  => 'Rolls-Royce',
        'Alfaromeo'   => 'Alfa Romeo',
        'ChevroletGMDaewoo'     => 'Chevrolet',
        'Renault-KoreaSamsung'  => 'Renault',
        'RenaultSamsung'        => 'Renault',
    ];
    $key = trim($name);
    if (isset($fixes[$key])) return $fixes[$key];
    // CamelCase concatenation (e.g. "ChevroletGMDaewoo" -> "Chevrolet"): keep the
    // first word when a lowercase run is immediately followed by an uppercase.
    if (preg_match('/^([A-Z][a-z]+)(?=[A-Z])/', $key, $cm)) {
        return $cm[1];
    }
    // "Ioniq5" -> "Ioniq 5" (letter directly followed by a digit, len>3 to avoid
    // real names like "i30", "iX3", "C4", "bB").
    if (mb_strlen($key) > 3 && preg_match('/^([A-Za-z][A-Za-z]+)(\d+)$/', $key, $m)) {
        return $m[1] . ' ' . $m[2];
    }
    return $key;
};

// Car-count suffix next to a filter option is intentionally disabled — the
// dropdown shows just "BMW" / "X5", no "(1.234)". (The count is still used
// elsewhere to skip brands/models with zero cars; only the label is hidden.)
$encarCountSuffix = function ($count): string {
    return '';
};

if ($encarTaxonomy && !empty($encarTaxonomy['brands'])) {
    $encarBrandList = [];
    foreach ($encarTaxonomy['brands'] as $key => $info) {
        // Skip brands that have no cars on Encar at all (count_native = 0) —
        // searching them always returns 0, so they only clutter the dropdown.
        if (isset($info['count_native']) && (int)$info['count_native'] === 0) {
            continue;
        }
        $engName = $info['eng_name'] ? $encarPrettyName(str_replace('_', ' ', $info['eng_name'])) : null;
        $brandBase = $engName ?: ($info['name_kr'] ?? $key);
        // Append the car count so the dropdown reads e.g. "BMW (1.234)".
        $displayLabel = $brandBase . $encarCountSuffix($info['count_native'] ?? 0);
        $cleanGenLabel = function (string $gk, array $gv) {
            // Extract a clean base label (code or English name) without year.
            $base = '';
            if (!empty($gv['eng_name'])) {
                $base = str_replace('_', ' ', $gv['eng_name']);
            } elseif (preg_match('/\(([A-Za-z0-9 ]+?)_?\)/u', $gk, $m)) {
                $base = trim($m[1]);
            } else {
                $stripped = preg_replace('/^(더 넥스트 |올 뉴 |더 뉴 |더 |뉴 )/u', '', $gk);
                $stripped = preg_replace('/\d+세대/u', '', $stripped);
                $stripped = trim($stripped);
                // Drop if still contains Korean (means no Latin code was extractable).
                if (preg_match('/[\x{AC00}-\x{D7A3}]/u', $stripped)) {
                    return '';
                }
                $base = $stripped;
            }
            // Append year range if known.
            $min = $gv['min_year'] ?? null;
            $max = $gv['max_year'] ?? null;
            if ($min && $max && $min === $max)        $base .= ' (' . $min . ')';
            elseif ($min && $max)                     $base .= ' (' . $min . '-' . $max . ')';
            elseif ($min)                             $base .= ' (' . $min . '+)';
            return $base;
        };

        $modelsJs = [];
        foreach ($info['models'] ?? [] as $mk => $mv) {
            // Skip models with no cars on Encar (count = 0) — they only produce
            // empty searches and clutter the model dropdown.
            if (isset($mv['count']) && (int)$mv['count'] === 0) {
                continue;
            }
            $gens = [];
            foreach ($mv['generations'] ?? [] as $gk => $gv) {
                $label = $cleanGenLabel($gk, $gv);
                if ($label === '') continue;
                $gens[] = ['key' => $gk, 'label' => $label];
            }
            $modelBase = $mv['eng_name'] ? $encarPrettyName(str_replace('_', ' ', $mv['eng_name'])) : ($mv['name_kr'] ?? $mk);
            $modelsJs[] = [
                'key'   => $mk,
                'label' => $modelBase . $encarCountSuffix($mv['count'] ?? 0),
                'sort_key' => $modelBase,   // sort by name, not the count-suffixed label
                'generations' => $gens,
            ];
        }
        // If every model was filtered out (all count=0), drop the brand too so it
        // doesn't sit in the list with an empty model dropdown.
        if (empty($modelsJs)) {
            continue;
        }
        // Sort models alphabetically by name (sort_key), ignoring the count suffix.
        usort($modelsJs, fn($a, $b) => strcasecmp($a['sort_key'], $b['sort_key']));
        $encarBrandsForJs[$key] = [
            'label'  => $displayLabel,
            'models' => $modelsJs,
        ];
        $encarBrandList[] = [
            'key' => $key, 'label' => $displayLabel, 'sort_key' => $brandBase,
            'base' => $brandBase, 'count' => (int)($info['count_native'] ?? 0),
            'models' => $modelsJs,
        ];
    }

    // Encar lists some makes twice under different Korean codes that resolve to
    // the same English name (e.g. "쉐보레(GM대우)" + "쉐보레" → both "Chevrolet",
    // "르노코리아(삼성)" + "르노" → both "Renault"). Merge them into one option:
    // the value carries all KR codes joined by "|" so the search covers both, the
    // counts are summed, and their model lists are combined (de-duplicated).
    $mergedBrands = [];
    foreach ($encarBrandList as $b) {
        $nameKey = mb_strtolower(trim($b['base']), 'UTF-8');
        if (!isset($mergedBrands[$nameKey])) {
            $mergedBrands[$nameKey] = $b;
            $mergedBrands[$nameKey]['keys'] = [$b['key']];
        } else {
            $mergedBrands[$nameKey]['keys'][] = $b['key'];
            $mergedBrands[$nameKey]['count'] += $b['count'];
            // Combine model lists, keeping the first of each model name.
            $seenMo = [];
            foreach ($mergedBrands[$nameKey]['models'] as $m) $seenMo[mb_strtolower($m['sort_key'] ?? $m['label'], 'UTF-8')] = true;
            foreach ($b['models'] as $m) {
                $mk = mb_strtolower($m['sort_key'] ?? $m['label'], 'UTF-8');
                if (!isset($seenMo[$mk])) { $mergedBrands[$nameKey]['models'][] = $m; $seenMo[$mk] = true; }
            }
        }
    }

    // Rebuild the JS map + the <option> list from the merged brands. The option
    // value is the "|"-joined KR codes; encarBrandsForJs is keyed by that same value.
    $encarBrandsForJs = [];
    $finalBrandList = [];
    foreach ($mergedBrands as $b) {
        $valueKey = implode('|', $b['keys']);
        usort($b['models'], fn($a, $c) => strcasecmp($a['sort_key'] ?? $a['label'], $c['sort_key'] ?? $c['label']));
        $encarBrandsForJs[$valueKey] = [
            'label'  => $b['base'] . $encarCountSuffix($b['count']),
            'models' => $b['models'],
        ];
        $finalBrandList[] = [
            'key' => $valueKey,
            'label' => $b['base'] . $encarCountSuffix($b['count']),
            'sort_key' => $b['base'],
        ];
    }
    usort($finalBrandList, fn($a, $b) => strcasecmp($a['sort_key'], $b['sort_key']));
    foreach ($finalBrandList as $b) {
        $brandOptionsEncar .= '<option value="'.htmlspecialchars($b['key']).'">'.htmlspecialchars($b['label']).'</option>';
    }
}

// Translate filter brand/model keys to English using Encar taxonomy.
$encarTranslate = function(?string $brandKey, ?string $modelKey) use ($encarTaxonomy, $encarPrettyName): string {
    if (!$brandKey) return '';
    // Merged brands store the value as "르노|르노코리아(삼성)" (several Encar codes for
    // the same make, joined by "|" — by design, so search covers both). The raw key
    // isn't a taxonomy key, so resolve to a real segment. Prefer the segment that
    // actually CONTAINS this model (SM6 lives under 르노코리아(삼성), not 르노); else
    // the first segment that exists in the taxonomy.
    if ($encarTaxonomy && empty($encarTaxonomy['brands'][$brandKey]) && strpos($brandKey, '|') !== false) {
        $segs = array_filter(array_map('trim', explode('|', $brandKey)));
        $picked = null;
        if ($modelKey) {
            foreach ($segs as $seg) {
                if (!empty($encarTaxonomy['brands'][$seg]['models'][$modelKey])) { $picked = $seg; break; }
            }
        }
        if ($picked === null) {
            foreach ($segs as $seg) {
                if (!empty($encarTaxonomy['brands'][$seg])) { $picked = $seg; break; }
            }
        }
        if ($picked !== null) $brandKey = $picked;
    }
    if (!$encarTaxonomy || empty($encarTaxonomy['brands'][$brandKey])) {
        return trim(($brandKey ?? '') . ' ' . ($modelKey ?? ''));
    }
    $brandInfo = $encarTaxonomy['brands'][$brandKey];
    $brand = $brandInfo['eng_name'] ?: $brandKey;
    $brand = $encarPrettyName(str_replace('_', ' ', $brand));
    if (!$modelKey) return $brand;
    $modelInfo = $brandInfo['models'][$modelKey] ?? null;
    $model = ($modelInfo['eng_name'] ?? null) ?: $modelKey;
    $model = $encarPrettyName(str_replace('_', ' ', $model));
    return trim($brand . ' ' . $model);
};

// ── OpenLane brand + model options (from openlane_taxonomy.json) ──
// Built by console/openlane_taxonomy_dump.php from OpenLane's real make/model
// facets, so every option matches the search API exactly (and carries a count).
$openlaneTaxonomy = null;
$openlaneTaxonomyFile = __DIR__ . '/../../../../App/Services/Parsing/Adapters/openlane_taxonomy.json';
if (file_exists($openlaneTaxonomyFile)) {
    $openlaneTaxonomy = json_decode(file_get_contents($openlaneTaxonomyFile), true);
}
// No count suffix: OpenLane stock is live, so the taxonomy's counts drift from
// reality (dropdown "X5 (62)" but the search imports 65). Showing them confused
// more than it helped, so labels are name-only.
$openlaneCountSuffix = function ($count): string {
    return '';
};
$brandOptionsOpenlane = '<option value="">'.$t['opt_all'].'</option>';
$openlaneBrandsForJs = [];   // { "BMW": [ {value:"X5", label:"X5 (69)"}, ... ] }

// OpenLane range steps (verified from its advanced filter), rendered as two
// dropdowns (from/to) like the site instead of free numeric inputs.
$olRangeOptions = function (array $steps, string $allLabel): string {
    $html = '<option value="">' . $allLabel . '</option>';
    foreach ($steps as $s) {
        $html .= '<option value="' . $s . '">' . number_format($s, 0, '.', ' ') . '</option>';
    }
    return $html;
};
$olMileageSteps = [50, 5000, 10000, 20000, 30000, 40000, 50000, 60000, 70000, 80000, 90000, 100000, 125000, 150000, 175000, 200000];
$olPriceSteps   = [1000, 2000, 3000, 4000, 5000, 6000, 7000, 8000, 9000, 10000, 11000, 12000, 13000, 14000, 15000, 16000, 17000, 18000, 19000, 20000, 25000, 30000, 35000, 40000, 45000, 50000];
$kmOptionsOpenlane    = $olRangeOptions($olMileageSteps, $t['opt_all'] ?? 'Toate');
$priceOptionsOpenlane = $olRangeOptions($olPriceSteps,   $t['opt_all'] ?? 'Toate');
// OpenLane fuel types — exact codes from its advanced filter (option VALUE is
// the FuelTypeId sent straight to the search API), labels from translations.
$olFuelTypes = [
    '100001' => $t['opt_ol_petrol']      ?? 'Petrol',
    '100003' => $t['opt_ol_diesel']      ?? 'Diesel',
    '100004' => $t['opt_ol_electric']    ?? 'Electric',
    '8'      => $t['opt_ol_mhev_petrol'] ?? 'Mild Hybrid Petrol (MHEV)',
    '9'      => $t['opt_ol_mhev_diesel'] ?? 'Mild Hybrid Diesel (MHEV)',
    '100013' => $t['opt_ol_hev_petrol']  ?? 'Hybrid Petrol (HEV)',
    '100022' => $t['opt_ol_hev_diesel']  ?? 'Hybrid Diesel (HEV)',
    '100023' => $t['opt_ol_phev_petrol'] ?? 'PlugIn Petrol (PHEV)',
    '100024' => $t['opt_ol_phev_diesel'] ?? 'PlugIn Diesel (PHEV)',
    '107003' => $t['opt_ol_cng']         ?? 'CNG',
    '100006' => $t['opt_ol_lpg']         ?? 'LPG',
    '100008' => $t['opt_ol_hydrogen']    ?? 'Hydrogen',
];
// Render the fuel multi-select as a dropdown: a closed trigger that opens a
// vertical checkbox list (name="fuel_type[]"). Behaves like the old single
// select but allows picking several fuels. Nothing pre-checked on a fresh form.
$fuelAllLabel = $t['opt_all'] ?? 'Toate';
$fuelCheckboxes = function (array $map) use ($fuelAllLabel) {
    $h  = '<div class="fuel-dd" data-all="'.htmlspecialchars($fuelAllLabel).'">';
    $h .= '<button type="button" class="fuel-dd-trigger" onclick="parsingToggleFuelDD(this)">'
        . '<span class="fuel-dd-text">'.htmlspecialchars($fuelAllLabel).'</span></button>';
    $h .= '<div class="fuel-dd-menu">';
    foreach ($map as $code => $label) {
        $h .= '<label class="fuel-check"><input type="checkbox" name="fuel_type[]" value="'
            . htmlspecialchars($code) . '" onchange="parsingUpdateFuelDD(this)"> '
            . htmlspecialchars($label) . '</label>';
    }
    return $h . '</div></div>';
};
$fuelChecksOpenlane = $fuelCheckboxes($olFuelTypes);
// Year dropdown: newest first down to 2015 (same start year for all 3 sources).
$yearOptionsOpenlane = '<option value="">' . ($t['opt_all'] ?? 'Toate') . '</option>';
for ($y = (int)date('Y'); $y >= 2015; $y--) {
    $yearOptionsOpenlane .= '<option value="' . $y . '">' . $y . '</option>';
}

// ── Encar colour filters (Color = body, SeatColor = interior) ──
// Only the colours Encar's facets actually accept are listed — its body facet has
// a colour per shade, while the interior one only has tone FAMILIES (검정색 계열 =
// "black tones"), so that list is much shorter. The labels reuse sauto's own
// translated colour names ($lng['l']['car']['clr']), keyed by sauto colour code;
// EncarAdapter maps the code to the Korean facet value.
$clrLabels = $lng['l']['car']['clr'] ?? [];
$encarColorCodes = ['wht','blk','slv','gra','brn','gld','blu','azr','grn','d_grn',
                    'l_grn','red','orn','ylw','vns','prp','pnk','bge'];
$encarSeatColorCodes = ['blk','brn','gra','bge','wht'];
$mkColorOptions = function (array $codes) use ($clrLabels, $t): string {
    $opts = [];
    foreach ($codes as $c) {
        $lbl = $clrLabels[$c] ?? $c;
        $opts[$c] = $lbl;
    }
    asort($opts, SORT_NATURAL | SORT_FLAG_CASE);
    $html = '<option value="">' . ($t['opt_all'] ?? 'Toate') . '</option>';
    foreach ($opts as $c => $lbl) {
        $html .= '<option value="' . htmlspecialchars($c, ENT_QUOTES) . '">'
               . htmlspecialchars($lbl) . '</option>';
    }
    return $html;
};
$colorOptionsEncar     = $mkColorOptions($encarColorCodes);
$seatColorOptionsEncar = $mkColorOptions($encarSeatColorCodes);

// Shared year dropdown (current year → 2015) for Encar / eCarsTrade "from/to".
$yearOptions = '<option value="">' . ($t['opt_all'] ?? 'Toate') . '</option>';
for ($y = (int)date('Y'); $y >= 2015; $y--) {
    $yearOptions .= '<option value="' . $y . '">' . $y . '</option>';
}

// OpenLane body-type options use ITS OWN values (verified from the advanced
// filter): Berline=sedan, Break=wagon, Coupé, Cabriolet, Lighttruck=van, etc.
$bodyOptionsOpenlane = '
    <option value="">'.$t['opt_all'].'</option>
    <option value="SUV">'.($t['opt_body_suv'] ?? 'SUV').'</option>
    <option value="Berline">'.($t['opt_body_sedan'] ?? 'Sedan').'</option>
    <option value="Break">'.($t['opt_body_wagon'] ?? 'Universal').'</option>
    <option value="Hatchback">'.($t['opt_body_hatchback'] ?? 'Hatchback').'</option>
    <option value="Compact">'.($t['opt_body_compact'] ?? 'Compact').'</option>
    <option value="Coupé">'.($t['opt_body_coupe'] ?? 'Coupé').'</option>
    <option value="Cabriolet">'.($t['opt_body_cabrio'] ?? 'Cabriolet').'</option>
    <option value="MPV">'.($t['opt_body_mpv'] ?? 'Minivan / MPV').'</option>
    <option value="Minibus">'.($t['opt_body_minibus'] ?? 'Microbuz').'</option>
    <option value="Lighttruck">'.($t['opt_body_lighttruck'] ?? 'Furgon / Light truck').'</option>
    <option value="Pickup">'.($t['opt_body_pickup'] ?? 'Pickup').'</option>
    <option value="Truck">'.($t['opt_body_truck'] ?? 'Camion').'</option>';
if ($openlaneTaxonomy && !empty($openlaneTaxonomy['makes'])) {
    $olMakes = $openlaneTaxonomy['makes'];
    uksort($olMakes, 'strcasecmp');
    foreach ($olMakes as $make => $info) {
        $cnt = (int)($info['count'] ?? 0);
        if ($cnt <= 0) continue; // no cars → would always return 0
        $brandOptionsOpenlane .= '<option value="'.htmlspecialchars($make, ENT_QUOTES).'">'
            . htmlspecialchars($make) . $openlaneCountSuffix($cnt) . '</option>';
        $models = [];
        foreach (($info['models'] ?? []) as $m) {
            $mn = $m['name'] ?? '';
            if ($mn === '') continue;
            $models[] = [
                'value' => $mn,
                'label' => $mn . $openlaneCountSuffix($m['count'] ?? 0),
            ];
        }
        $openlaneBrandsForJs[$make] = $models;
    }
}

// ── eCarsTrade brand + model options (from ecarstrade_taxonomy.json) ──
// Built by console/ecarstrade_taxonomy_dump.php from real listings.
$ecarsTaxonomy = null;
$ecarsTaxonomyFile = __DIR__ . '/../../../../App/Services/Parsing/Adapters/ecarstrade_taxonomy.json';
if (file_exists($ecarsTaxonomyFile)) {
    $ecarsTaxonomy = json_decode(file_get_contents($ecarsTaxonomyFile), true);
}
$brandOptionsEcars = '<option value="">'.$t['opt_all'].'</option>';
$ecarsBrandsForJs = [];   // { "BMW": [ {value:"X5", label:"X5"}, ... ] }
if ($ecarsTaxonomy && !empty($ecarsTaxonomy['brands'])) {
    $ecBrands = $ecarsTaxonomy['brands'];
    uksort($ecBrands, 'strcasecmp');
    foreach ($ecBrands as $brand => $info) {
        $models = $info['models'] ?? [];
        if (!$models) continue; // no models found → skip empty brand
        $brandOptionsEcars .= '<option value="'.htmlspecialchars($brand, ENT_QUOTES).'">'
            . htmlspecialchars($brand) . '</option>';
        $list = [];
        foreach ($models as $m) {
            $mn = $m['name'] ?? '';
            if ($mn === '') continue;
            $list[] = ['value' => $mn, 'label' => $mn];
        }
        $ecarsBrandsForJs[$brand] = $list;
    }
}

// ── Auto1 brand + model + engine options (from auto1_taxonomy.json) ──
// Built by console/auto1_taxonomy_dump.php from Auto1's /v1/car-search/filters
// endpoint. Make VALUE is Auto1's numeric manufacturer code (sent straight to the
// search API); the display label is the resolved real name (BMW, Audi, ...).
// Models carry Auto1's own value (German-style badges like "3er", "X5"), and each
// model carries its engine variants ("1.5 TDCi") with live stock counts.
$auto1Taxonomy = null;
$auto1TaxonomyFile = __DIR__ . '/../../../../App/Services/Parsing/Adapters/auto1_taxonomy.json';
if (file_exists($auto1TaxonomyFile)) {
    $auto1Taxonomy = json_decode(file_get_contents($auto1TaxonomyFile), true);
}
$brandOptionsAuto1 = '<option value="">'.$t['opt_all'].'</option>';
// { "<code>": [ {value:"3er", label:"3er", engines:[{value:"1.5 TDCi"}]}, ... ] }
$auto1BrandsForJs = [];
if ($auto1Taxonomy && !empty($auto1Taxonomy['makes'])) {
    $a1Makes = $auto1Taxonomy['makes'];
    // Sort by display name, not by numeric code.
    uasort($a1Makes, fn($a, $b) => strcasecmp($a['name'] ?? '', $b['name'] ?? ''));
    foreach ($a1Makes as $code => $info) {
        $name = trim((string)($info['name'] ?? ''));
        if ($name === '') continue;
        $brandOptionsAuto1 .= '<option value="'.htmlspecialchars((string)$code, ENT_QUOTES).'">'
            . htmlspecialchars($name) . '</option>';
        $list = [];
        foreach (($info['models'] ?? []) as $m) {
            $mv = (string)($m['value'] ?? '');
            if ($mv === '') continue;
            // Engines are already ordered by stock (most common first) — keep that
            // order. Counts stay out of the label, like the other sources.
            $engines = [];
            foreach (($m['engines'] ?? []) as $e) {
                $ev = (string)($e['value'] ?? '');
                if ($ev !== '') $engines[] = ['value' => $ev];
            }
            $list[] = ['value' => $mv, 'label' => (string)($m['label'] ?? $mv), 'engines' => $engines];
        }
        $auto1BrandsForJs[(string)$code] = $list;
    }
}
// Auto1 uses the same km/price step dropdowns and year range as OpenLane.
$kmOptionsAuto1    = $olRangeOptions($olMileageSteps, $t['opt_all'] ?? 'Toate');
$priceOptionsAuto1 = $olRangeOptions($olPriceSteps,   $t['opt_all'] ?? 'Toate');
$yearOptionsAuto1  = $yearOptionsOpenlane;
// Auto1 fuelTypes values (from its filters taxonomy): petrol/diesel/gas/hybrid/electro/other.
$a1FuelTypes = [
    'petrol'  => $t['opt_petrol']   ?? 'Benzină',
    'diesel'  => $t['opt_diesel']   ?? 'Diesel',
    'hybrid'  => $t['opt_hybrid']   ?? 'Hibrid',
    'electro' => $t['opt_electric'] ?? 'Electric',
    'gas'     => $t['opt_ol_lpg']   ?? 'GPL / Gaz',
];
$fuelChecksAuto1 = $fuelCheckboxes($a1FuelTypes);
// Auto1 import-country filter → its branchCountries (where the car physically is,
// i.e. where you collect it — the same country published as import_country_id).
// Names come from the dictionary the taxonomy cron builds, so there is no hardcoded
// country list to maintain. Search-only: nothing on the card is rendered from it.
$a1CountryCodes = ['AT','BE','CH','DE','DK','ES','FI','FR','IT','LU','NL','PL','PT','SE','SK'];
$countryOptionsAuto1 = '<option value="">'.$t['opt_all'].'</option>';
if (!function_exists('parsing_auto1_tr')) {
    $a1RepFile = __DIR__ . '/parsing_auto1_report.php';
    if (is_file($a1RepFile)) include_once $a1RepFile;
}
$a1Lang = in_array($_COOKIE['lang'] ?? 'ro', ['ro','ru','en'], true) ? $_COOKIE['lang'] : 'ro';
$a1Countries = [];
foreach ($a1CountryCodes as $cc) {
    $nm = function_exists('parsing_auto1_tr')
        ? parsing_auto1_tr('global.car_details.country_of_origin.'.$cc, $a1Lang)
        : '';
    $a1Countries[$cc] = ($nm !== '' ? $nm : $cc);
}
asort($a1Countries, SORT_NATURAL | SORT_FLAG_CASE);
foreach ($a1Countries as $cc => $nm) {
    $countryOptionsAuto1 .= '<option value="'.htmlspecialchars($cc, ENT_QUOTES).'">'
        . htmlspecialchars($nm) . '</option>';
}

// Auto1 bodyTypes values: cabrio/coupe/combi/limo/suv/van/truck/commercial/smallCar.
$bodyOptionsAuto1 = '
    <option value="">'.$t['opt_all'].'</option>
    <option value="suv">'.($t['opt_body_suv'] ?? 'SUV').'</option>
    <option value="limo">'.($t['opt_body_sedan'] ?? 'Sedan').'</option>
    <option value="combi">'.($t['opt_body_wagon'] ?? 'Universal').'</option>
    <option value="smallCar">'.($t['opt_body_hatchback'] ?? 'Hatchback').'</option>
    <option value="coupe">'.($t['opt_body_coupe'] ?? 'Coupé').'</option>
    <option value="cabrio">'.($t['opt_body_cabrio'] ?? 'Cabriolet').'</option>
    <option value="van">'.($t['opt_body_lighttruck'] ?? 'Furgon / Van').'</option>
    <option value="truck">'.($t['opt_body_pickup'] ?? 'Pickup / Camion').'</option>';

// Source label / logo for filter card tags.
$sourceLogo = function(string $src): string {
    $logos = [
        'encar'      => '/content/admin/page/parsing/media-parsing/encar-logo.webp',
        'ecarstrade' => '/content/admin/page/parsing/media-parsing/ecarstrade-logo.svg',
        'openlane'   => '/content/admin/page/parsing/media-parsing/openlane-logo.svg',
        'auto1'      => '/content/admin/page/parsing/media-parsing/auto1.png',
    ];
    if (isset($logos[$src])) {
        return '<span class="ftag ftag-source"><img src="'.$logos[$src].'" alt="'.strtoupper($src).'" class="ftag-logo"></span>';
    }
    return '<span class="ftag ftag-source">'.strtoupper($src).'</span>';
};

// Fuel checkboxes for Encar / eCarsTrade (internal codes; matches Encar API).
$fuelChecks = $fuelCheckboxes([
    'benzina'       => $t['opt_gasoline'],
    'diesel'        => $t['opt_diesel'],
    'lpg'           => 'LPG',
    'hybrid'        => $t['opt_hybrid'],
    'diesel_hybrid' => $t['opt_diesel_hybrid'],
    'gasoline_lpg'  => $t['opt_gasoline_lpg'],
    'gasoline_cng'  => $t['opt_gasoline_cng'],
    'electric'      => $t['opt_electric'],
    'other'         => $t['opt_other'],
]);

$gearboxOptions = '
    <option value="">'.$t['opt_all'].'</option>
    <option value="automat">'.$t['opt_automatic'].'</option>
    <option value="manual">'.$t['opt_manual'].'</option>
    <option value="semi-auto">'.$t['opt_semi_auto'].'</option>
    <option value="cvt">'.$t['opt_cvt'].'</option>';

$driveOptions = '
    <option value="">'.$t['opt_all'].'</option>
    <option value="fwd">'.$t['opt_fwd'].'</option>
    <option value="rwd">'.$t['opt_rwd'].'</option>
    <option value="awd">'.$t['opt_awd'].'</option>';

$bodyOptions = '
    <option value="">'.$t['opt_all'].'</option>
    <option value="sedan">'.$t['opt_body_sedan'].'</option>
    <option value="suv">'.$t['opt_body_suv'].'</option>
    <option value="hatchback">'.$t['opt_body_hatchback'].'</option>
    <option value="coupe">'.$t['opt_body_coupe'].'</option>
    <option value="wagon">'.$t['opt_body_wagon'].'</option>
    <option value="convertible">'.$t['opt_body_convertible'].'</option>
    <option value="van">'.$t['opt_body_van'].'</option>
    <option value="pickup">'.$t['opt_body_pickup'].'</option>';

$rtrn = '
<link rel="stylesheet" href="/content/admin/page/parsing/parsing.css?v='.filemtime(_ADM_PAGE.'/parsing/parsing.css').'">
<div id="parsing-container">

    <div class="parsing-tabs">
        <a href="/'.$admin_dir.'/parsing/filters" class="tab active">'.$t['tab_filters'].'</a>
        <a href="/'.$admin_dir.'/parsing/ctlg" class="tab">'.$t['tab_ctlg'].'</a>
        <a href="/'.$admin_dir.'/parsing/published" class="tab">'.$t['tab_published'].'</a>
        <a href="/'.$admin_dir.'/parsing/settings" class="tab">'.$t['tab_settings'].'</a>
        <a href="/'.$admin_dir.'/parsing/favorites" class="tab tab-favorites"><img src="/content/admin/page/parsing/media-parsing/favorite.png" alt=""> '.$t['tab_favorites'].'</a>
    </div>

    <!-- Direct link bar -->
    <div class="link-direct-bar">
        <form id="parsing-link-form" onsubmit="parsingFetchByLink(event)">
            <span class="link-direct-bar-label"><img src="/content/admin/page/parsing/media-parsing/link.png" alt="" class="link-icon-lg"> '.$t['link_direct_title'].'</span>
            <input type="url" name="url" placeholder="'.$t['link_placeholder'].'" required>
            <button type="submit" class="btn-primary btn-sm">'.$t['btn_add_car'].'</button>
        </form>
        <div id="parsing-link-result" class="source-search-result"></div>
    </div>

    <!-- ═══════════════════════════════════════════════
         SOURCE PANELS
    ═══════════════════════════════════════════════ -->
    <div class="source-panels">

        <!-- ── ENCAR ── -->
        <div class="source-panel" id="sp-encar">
            <div class="source-panel-head" onclick="parsingTogglePanel(\'encar\')">
                <img src="/content/admin/page/parsing/media-parsing/encar-logo.webp" class="sp-logo-img" alt="Encar">
            </div>
            <div class="source-panel-body sp-collapsed" id="spb-encar">
                <form id="sf-encar" class="source-search-form" onsubmit="parsingSearchSource(event, \'encar\')">
                    <input type="hidden" name="source" value="encar">
                    <div class="search-grid">

                        <div class="field">
                            <label>'.$t['field_brand'].'</label>
                            <select name="brand" id="encar-brand">
                                '.$brandOptionsEncar.'
                            </select>
                        </div>

                        <div class="field">
                            <label>'.$t['field_model'].'</label>
                            <select name="model" id="encar-model">
                                <option value="">'.$t['opt_all'].'</option>
                            </select>
                        </div>

                        <div class="field field-range">
                            <label>'.$t['field_year'].'</label>
                            <div class="range-inputs">
                                <select name="year_from">'.$yearOptions.'</select>
                                <select name="year_to">'.$yearOptions.'</select>
                            </div>
                        </div>

                        <div class="field">
                            <label>'.$t['field_fuel'].'</label>
                            '.$fuelChecks.'
                        </div>

                        <div class="field">
                            <label>'.$t['field_gearbox'].'</label>
                            <select name="gearbox">
                                <option value="">'.$t['opt_all'].'</option>
                                <option value="automat">'.$t['opt_automatic'].'</option>
                                <option value="manual">'.$t['opt_manual'].'</option>
                                <option value="semi-auto">'.$t['opt_semi_auto'].'</option>
                                <option value="cvt">'.$t['opt_cvt'].'</option>
                            </select>
                        </div>

                        <div class="field">
                            <label>'.$t['field_body_type'].'</label>
                            <select name="category">
                                <option value="">'.$t['opt_all'].'</option>
                                <option value="경차">'.$t['opt_cat_minicar'].'</option>
                                <option value="소형차">'.$t['opt_cat_small'].'</option>
                                <option value="준중형차">'.$t['opt_cat_compact'].'</option>
                                <option value="중형차">'.$t['opt_cat_midsize'].'</option>
                                <option value="대형차">'.$t['opt_cat_large'].'</option>
                                <option value="스포츠카">'.$t['opt_cat_sports'].'</option>
                                <option value="SUV">'.$t['opt_cat_suv'].'</option>
                                <option value="RV">'.$t['opt_cat_rv'].'</option>
                                <option value="경승합차">'.$t['opt_cat_lightvan'].'</option>
                                <option value="승합차">'.$t['opt_cat_van'].'</option>
                                <option value="화물차">'.$t['opt_cat_truck'].'</option>
                            </select>
                        </div>

                        <div class="field">
                            <label>'.($t['field_color'] ?? 'Culoare auto').'</label>
                            <select name="color">'.$colorOptionsEncar.'</select>
                        </div>

                        <div class="field">
                            <label>'.($t['field_interior_color'] ?? 'Culoare salon').'</label>
                            <select name="interior_color">'.$seatColorOptionsEncar.'</select>
                        </div>

                        <div class="field field-range">
                            <label>'.$t['field_km_max'].'</label>
                            <div class="range-inputs">
                                <input type="number" name="km_min" min="0" placeholder="'.$t['placeholder_from'].'">
                                <input type="number" name="km_max" min="0" placeholder="'.$t['placeholder_to'].'">
                            </div>
                        </div>

                        <div class="field field-range">
                            <label>'.$t['field_price_max'].'</label>
                            <div class="range-inputs">
                                <input type="number" name="price_min" min="0" placeholder="'.$t['placeholder_from'].'">
                                <input type="number" name="price_max" min="0" placeholder="'.$t['placeholder_to'].'">
                            </div>
                        </div>

                    </div>
                    <div class="search-actions">
                        <button type="button" class="btn-search-action" onclick="parsingSaveSourceFilter(\'encar\')"><img src="/content/admin/page/parsing/media-parsing/save.png" alt="" class="btn-ico"> '.$t['btn_save_as_filter'].'</button>
                        <button type="reset" class="btn-search-action btn-clear"><img src="/content/admin/page/parsing/media-parsing/clean.png" alt="" class="btn-ico"> '.$t['btn_clear_form'].'</button>
                        <span class="search-hint">'.$t['search_needs_filter'].'</span>
                        <button type="submit" class="btn-search-action btn-search-go">'.$t['btn_search_now'].'</button>
                    </div>
                </form>
                <div class="source-search-result" id="ssr-encar"></div>
            </div>
        </div>

        <!-- ── e-CarsTrade ── -->
        <div class="source-panel" id="sp-ecarstrade">
            <div class="source-panel-head" onclick="parsingTogglePanel(\'ecarstrade\')">
                <img src="/content/admin/page/parsing/media-parsing/ecarstrade-logo.svg" class="sp-logo-img" alt="e-CarsTrade">
            </div>
            <div class="source-panel-body sp-collapsed" id="spb-ecarstrade">
                <form id="sf-ecarstrade" class="source-search-form" onsubmit="parsingSearchSource(event, \'ecarstrade\')">
                    <input type="hidden" name="source" value="ecarstrade">
                    <div class="search-grid">

                        <div class="field">
                            <label>'.$t['field_brand'].'</label>
                            <select name="brand" id="ecarstrade-brand">
                                '.$brandOptionsEcars.'
                            </select>
                        </div>

                        <div class="field">
                            <label>'.$t['field_model'].'</label>
                            <select name="model" id="ecarstrade-model" def_text="'.$t['opt_all'].'">
                                <option value="">'.$t['opt_all'].'</option>
                            </select>
                        </div>

                        <div class="field field-range">
                            <label>'.$t['field_year'].'</label>
                            <div class="range-inputs">
                                <select name="year_from">'.$yearOptions.'</select>
                                <select name="year_to">'.$yearOptions.'</select>
                            </div>
                        </div>

                        <div class="field">
                            <label>'.$t['field_fuel'].'</label>
                            '.$fuelChecks.'
                        </div>

                        <div class="field">
                            <label>'.$t['field_gearbox'].'</label>
                            <select name="gearbox">
                                <option value="">'.$t['opt_all'].'</option>
                                <option value="automat">'.$t['opt_automatic'].'</option>
                                <option value="manual">'.$t['opt_manual'].'</option>
                                <option value="semi-auto">'.$t['opt_semi_auto'].'</option>
                            </select>
                        </div>

                        <div class="field">
                            <label>'.$t['field_body_type'].'</label>
                            <select name="body_type">'.$bodyOptions.'</select>
                        </div>

                        <div class="field field-range">
                            <label>'.$t['field_km_max'].'</label>
                            <div class="range-inputs">
                                <input type="number" name="km_min" min="0" placeholder="'.$t['placeholder_from'].'">
                                <input type="number" name="km_max" min="0" placeholder="'.$t['placeholder_to'].'">
                            </div>
                        </div>

                        <div class="field field-range">
                            <label>'.$t['field_price_max'].'</label>
                            <div class="range-inputs">
                                <input type="number" name="price_min" min="0" placeholder="'.$t['placeholder_from'].'">
                                <input type="number" name="price_max" min="0" placeholder="'.$t['placeholder_to'].'">
                            </div>
                        </div>

                    </div>
                    <div class="search-actions">
                        <button type="button" class="btn-search-action" onclick="parsingSaveSourceFilter(\'ecarstrade\')"><img src="/content/admin/page/parsing/media-parsing/save.png" alt="" class="btn-ico"> '.$t['btn_save_as_filter'].'</button>
                        <button type="reset" class="btn-search-action btn-clear"><img src="/content/admin/page/parsing/media-parsing/clean.png" alt="" class="btn-ico"> '.$t['btn_clear_form'].'</button>
                        <span class="search-hint">'.$t['search_needs_filter'].'</span>
                        <button type="submit" class="btn-search-action btn-search-go">'.$t['btn_search_now'].'</button>
                    </div>
                </form>
                <div class="source-search-result" id="ssr-ecarstrade"></div>
            </div>
        </div>

        <!-- ── OPENLane ── -->
        <div class="source-panel" id="sp-openlane">
            <div class="source-panel-head" onclick="parsingTogglePanel(\'openlane\')">
                <img src="/content/admin/page/parsing/media-parsing/openlane-logo.svg" class="sp-logo-img" alt="OPENLane">
            </div>
            <div class="source-panel-body sp-collapsed" id="spb-openlane">
                <form id="sf-openlane" class="source-search-form" onsubmit="parsingSearchSource(event, \'openlane\')">
                    <input type="hidden" name="source" value="openlane">
                    <div class="search-grid">

                        <div class="field">
                            <label>'.$t['field_brand'].'</label>
                            <select class="form-control" name="brand" id="openlane-brand">
                                '.$brandOptionsOpenlane.'
                            </select>
                        </div>

                        <div class="field">
                            <label>'.$t['field_model'].'</label>
                            <select class="form-control" name="model" id="openlane-model" def_text="'.$t['opt_all'].'">
                                <option value="">'.$t['opt_all'].'</option>
                            </select>
                        </div>

                        <div class="field field-range">
                            <label>'.$t['field_year'].'</label>
                            <div class="range-inputs">
                                <select name="year_from">'.$yearOptionsOpenlane.'</select>
                                <select name="year_to">'.$yearOptionsOpenlane.'</select>
                            </div>
                        </div>

                        <div class="field">
                            <label>'.$t['field_fuel'].'</label>
                            '.$fuelChecksOpenlane.'
                        </div>

                        <div class="field">
                            <label>'.$t['field_gearbox'].'</label>
                            <select name="gearbox">
                                <option value="">'.$t['opt_all'].'</option>
                                <option value="automat">'.$t['opt_automatic'].'</option>
                                <option value="manual">'.$t['opt_manual'].'</option>
                            </select>
                        </div>

                        <div class="field">
                            <label>'.$t['field_body_type'].'</label>
                            <select name="body_type">'.$bodyOptionsOpenlane.'</select>
                        </div>

                        <div class="field field-range">
                            <label>'.$t['field_km_max'].'</label>
                            <div class="range-inputs">
                                <select name="km_min">'.$kmOptionsOpenlane.'</select>
                                <select name="km_max">'.$kmOptionsOpenlane.'</select>
                            </div>
                        </div>

                        <div class="field field-range">
                            <label>'.$t['field_price_max'].'</label>
                            <div class="range-inputs">
                                <select name="price_min">'.$priceOptionsOpenlane.'</select>
                                <select name="price_max">'.$priceOptionsOpenlane.'</select>
                            </div>
                        </div>

                    </div>
                    <div class="search-actions">
                        <button type="button" class="btn-search-action" onclick="parsingSaveSourceFilter(\'openlane\')"><img src="/content/admin/page/parsing/media-parsing/save.png" alt="" class="btn-ico"> '.$t['btn_save_as_filter'].'</button>
                        <button type="reset" class="btn-search-action btn-clear"><img src="/content/admin/page/parsing/media-parsing/clean.png" alt="" class="btn-ico"> '.$t['btn_clear_form'].'</button>
                        <span class="search-hint">'.$t['search_needs_filter'].'</span>
                        <button type="submit" class="btn-search-action btn-search-go">'.$t['btn_search_now'].'</button>
                    </div>
                </form>
                <div class="source-search-result" id="ssr-openlane"></div>
            </div>
        </div>

        <div class="source-panel" id="sp-auto1">
            <div class="source-panel-head" onclick="parsingTogglePanel(\'auto1\')">
                <img src="/content/admin/page/parsing/media-parsing/auto1.png" class="sp-logo-img" alt="AUTO1">
            </div>
            <div class="source-panel-body sp-collapsed" id="spb-auto1">
                <form id="sf-auto1" class="source-search-form" onsubmit="parsingSearchSource(event, \'auto1\')">
                    <input type="hidden" name="source" value="auto1">
                    <div class="search-grid">

                        <div class="field">
                            <label>'.$t['field_brand'].'</label>
                            <select class="form-control" name="brand" id="auto1-brand">
                                '.$brandOptionsAuto1.'
                            </select>
                        </div>

                        <div class="field">
                            <label>'.$t['field_model'].'</label>
                            <select class="form-control" name="model" id="auto1-model" def_text="'.$t['opt_all'].'">
                                <option value="">'.$t['opt_all'].'</option>
                            </select>
                        </div>

                        <div class="field">
                            <label>'.($t['field_engine'] ?? 'Motorizare').'</label>
                            <!-- Multi-select, filled by loadAuto1Engines() once a model is
                                 picked: engines are per-model, so there is nothing to show
                                 before that. Reuses the fuel checkbox-dropdown shell. -->
                            <div class="fuel-dd" id="auto1-engine-dd" data-all="'.htmlspecialchars($fuelAllLabel).'">
                                <button type="button" class="fuel-dd-trigger" onclick="parsingToggleFuelDD(this)">
                                    <span class="fuel-dd-text">'.htmlspecialchars($fuelAllLabel).'</span>
                                </button>
                                <div class="fuel-dd-menu"></div>
                            </div>
                        </div>

                        <!-- Fuel sits right after engine (not after Year) so the two
                             pair up on one row in the 2-col mobile grid — Year is a
                             full-width range field and would otherwise split them onto
                             separate rows. Keeps gearbox at nth-child(6). -->
                        <div class="field">
                            <label>'.$t['field_fuel'].'</label>
                            '.$fuelChecksAuto1.'
                        </div>

                        <div class="field field-range">
                            <label>'.$t['field_year'].'</label>
                            <div class="range-inputs">
                                <select name="year_from">'.$yearOptionsAuto1.'</select>
                                <select name="year_to">'.$yearOptionsAuto1.'</select>
                            </div>
                        </div>

                        <div class="field">
                            <label>'.$t['field_gearbox'].'</label>
                            <select name="gearbox">
                                <option value="">'.$t['opt_all'].'</option>
                                <option value="automat">'.$t['opt_automatic'].'</option>
                                <option value="manual">'.$t['opt_manual'].'</option>
                            </select>
                        </div>

                        <div class="field">
                            <label>'.$t['field_body_type'].'</label>
                            <select name="body_type">'.$bodyOptionsAuto1.'</select>
                        </div>

                        <div class="field">
                            <label>'.($t['field_country_origin'] ?? 'Țara de import').'</label>
                            <select name="country_origin">'.$countryOptionsAuto1.'</select>
                        </div>

                        <div class="field field-range">
                            <label>'.$t['field_km_max'].'</label>
                            <div class="range-inputs">
                                <select name="km_min">'.$kmOptionsAuto1.'</select>
                                <select name="km_max">'.$kmOptionsAuto1.'</select>
                            </div>
                        </div>

                        <div class="field field-range">
                            <label>'.$t['field_price_max'].'</label>
                            <div class="range-inputs">
                                <select name="price_min">'.$priceOptionsAuto1.'</select>
                                <select name="price_max">'.$priceOptionsAuto1.'</select>
                            </div>
                        </div>

                    </div>
                    <div class="search-actions">
                        <button type="button" class="btn-search-action" onclick="parsingSaveSourceFilter(\'auto1\')"><img src="/content/admin/page/parsing/media-parsing/save.png" alt="" class="btn-ico"> '.$t['btn_save_as_filter'].'</button>
                        <button type="reset" class="btn-search-action btn-clear"><img src="/content/admin/page/parsing/media-parsing/clean.png" alt="" class="btn-ico"> '.$t['btn_clear_form'].'</button>
                        <span class="search-hint">'.$t['search_needs_filter'].'</span>
                        <button type="submit" class="btn-search-action btn-search-go">'.$t['btn_search_now'].'</button>
                    </div>
                </form>
                <div class="source-search-result" id="ssr-auto1"></div>
            </div>
        </div>

    </div><!-- /source-panels -->

    <!-- ═══════════════════════════════════════════════
         SAVED FILTERS
    ═══════════════════════════════════════════════ -->
    <h2 class="filters-section-title">'.$t['section_filters_saved'].' <span class="filters-count">'.count($savedFilters).'</span></h2>';

if (empty($savedFilters)) {
    $rtrn .= '<div class="empty-state">'.$t['empty_filters'].'</div>';
} else {
    $rtrn .= '<div class="filter-cards">';
    foreach ($savedFilters as $f) {
        $sources = array_filter(array_map('trim', explode(',', $f['sources'])));
        $isActive = (bool)$f['active'];
        $primarySource = $sources[0] ?? '';
        // Fields that live inside criteria_extra (engine, generation, ...) are not
        // columns, so they must be decoded before the card can show them.
        $fx = !empty($f['criteria_extra']) ? (json_decode($f['criteria_extra'], true) ?: []) : [];
        // Auto1 stores the make as its numeric code ("060"), so the raw value would
        // read "060 80" on the card — resolve it through the taxonomy. Encar has its
        // own Korean→English translator; the rest already store real names.
        if ($primarySource === 'encar') {
            $brandModel = $encarTranslate($f['brand'] ?? null, $f['model'] ?? null);
        } elseif ($primarySource === 'auto1') {
            $a1Code  = trim((string)($f['brand'] ?? ''));
            $a1Brand = $auto1Taxonomy['makes'][$a1Code]['name'] ?? $a1Code;
            $brandModel = trim($a1Brand . ' ' . ($f['model'] ?? ''));
        } else {
            $brandModel = trim(($f['brand'] ?? '') . ' ' . ($f['model'] ?? ''));
        }
        $yearRange = '';
        if (!empty($f['year_from']) || !empty($f['year_to'])) {
            $yearRange = ($f['year_from'] ?? '?') . ' – ' . ($f['year_to'] ?? '?');
        }

        $tags = '';
        foreach ($sources as $s) {
            $tags .= $sourceLogo($s);
        }
        if ($brandModel) $tags .= '<span class="ftag">'.htmlspecialchars($brandModel).'</span>';
        // Auto1 engine variants — one tag each, as with fuel.
        if (!empty($fx['engine'])) {
            $engTags = is_array($fx['engine']) ? $fx['engine'] : [$fx['engine']];
            foreach ($engTags as $eng) {
                $eng = trim((string)$eng);
                if ($eng !== '') $tags .= '<span class="ftag">'.htmlspecialchars($eng).'</span>';
            }
        }
        if ($yearRange)  $tags .= '<span class="ftag">'.$yearRange.'</span>';
        if (!empty($f['km_max'])) $tags .= '<span class="ftag">'.number_format($f['km_max'],0,'.',' ').' km</span>';
        if (!empty($f['price_max'])) $tags .= '<span class="ftag">'.number_format($f['price_max'],0,'.',' ').' €</span>';
        if (!empty($f['fuel_type'])) {
            // fuel_type is a CSV of codes — one tag per fuel, label from translations
            // (covers both internal codes and OpenLane numeric ids).
            $fuelLabels = [
                'benzina' => $t['opt_gasoline'], 'gasoline' => $t['opt_gasoline'],
                'diesel' => $t['opt_diesel'], 'lpg' => 'LPG',
                'hybrid' => $t['opt_hybrid'], 'diesel_hybrid' => $t['opt_diesel_hybrid'],
                'gasoline_lpg' => $t['opt_gasoline_lpg'], 'gasoline_cng' => $t['opt_gasoline_cng'],
                'electric' => $t['opt_electric'], 'other' => $t['opt_other'],
            ] + $olFuelTypes;
            foreach (explode(',', $f['fuel_type']) as $fc) {
                $fc = trim($fc);
                if ($fc === '') continue;
                $lbl = $fuelLabels[$fc] ?? $fc;
                $tags .= '<span class="ftag">'.htmlspecialchars($lbl).'</span>';
            }
        }
        if (!empty($f['gearbox'])) $tags .= '<span class="ftag">'.htmlspecialchars($f['gearbox']).'</span>';
        // Extras live in criteria_extra (JSON) rather than their own columns — show
        // the ones an operator picked so a saved filter reads back what was chosen.
        $fx = !empty($f['criteria_extra']) ? (json_decode($f['criteria_extra'], true) ?: []) : [];
        if (!empty($fx['country_origin'])) {
            foreach (explode(',', (string)$fx['country_origin']) as $cc) {
                $cc = strtoupper(trim($cc));
                if ($cc === '') continue;
                $tags .= '<span class="ftag">'.htmlspecialchars($a1Countries[$cc] ?? $cc).'</span>';
            }
        }
        foreach (['color' => $t['field_color'] ?? 'Culoare', 'interior_color' => $t['field_interior_color'] ?? 'Salon'] as $ck => $clbl) {
            if (empty($fx[$ck])) continue;
            $cname = $clrLabels[$fx[$ck]] ?? $fx[$ck];
            $tags .= '<span class="ftag">'.htmlspecialchars($clbl.': '.$cname).'</span>';
        }

        // Operator-facing stat: how many cars from this filter are LIVE on the site
        // right now, plus a "sold" tail only when some have sold (so the count
        // dropping doesn't look like cars vanished). Kept to one clear line.
        $liveCount = (int)($f['published_count'] ?? 0);
        $soldCount = (int)($f['sold_count'] ?? 0);
        $statLine = '<span class="fstat-live">'.$liveCount.' '.($t['on_site_label'] ?? 'pe site').'</span>';
        if ($soldCount > 0) {
            $statLine .= '<span class="fstat-sold"> · '.$soldCount.' '.($t['sold_label'] ?? 'vândute').'</span>';
        }

        $statusLabel = $isActive ? ($t['status_active'] ?? 'Activ') : ($t['status_paused'] ?? 'Oprit');
        $rtrn .= '
        <div class="filter-card'.(!$isActive ? ' filter-card--off' : '').'" data-filter-id="'.(int)$f['id'].'">
            <div class="filter-card-top">
                <div class="filter-card-name">'.htmlspecialchars($f['name']).'</div>
                <label class="toggle-switch" title="'.$t['action_toggle'].'">
                    <input type="checkbox" '.($isActive ? 'checked' : '').' onchange="parsingToggle('.(int)$f['id'].')">
                    <span class="toggle-slider"></span>
                    <span class="toggle-text">'.$statusLabel.'</span>
                </label>
            </div>
            <div class="filter-card-tags">'.$tags.'</div>
            <div class="filter-card-meta">
                <div class="filter-card-limit">
                    <span class="limit-label">'.($t['publish_limit_label'] ?? 'Limită publicare').'</span>
                    <input type="number" class="limit-input" min="0" step="1"
                        value="'.((int)($f['publish_limit'] ?? 0)).'"
                        placeholder="∞"
                        onchange="parsingSetPublishLimit('.(int)$f['id'].', this)">
                </div>
                <div class="filter-card-stats">
                    <span class="filter-stat">'.$statLine.'</span>
                </div>
            </div>
            <div class="filter-card-actions">
                <button class="btn-act" onclick="parsingFilterStats('.(int)$f['id'].')" title="'.($t['action_publish_stats'] ?? 'Statistici').'">
                    <span class="btn-act-ico">📊</span><span class="btn-act-lbl">'.($t['action_publish_stats'] ?? 'Statistici').'</span>
                </button>
                <a class="btn-act btn-act-999" href="/'.$admin_dir.'/parsing/published?filter_id='.(int)$f['id'].'&crosspost999=1" title="'.($t['action_view_999'] ?? 'Publicate pe 999.md').'">
                    <span class="btn-act-lbl">999.md</span>
                </a>
                <button class="btn-act btn-act-icon-only" onclick="parsingLoadIntoPanel('.(int)$f['id'].')" title="'.$t['action_edit'].'">
                    <span class="btn-act-ico">✎</span>
                </button>
                <button class="btn-act btn-act-danger" onclick="parsingDelete('.(int)$f['id'].')" title="'.$t['action_delete'].'">
                    <span class="btn-act-ico">🗑</span>
                </button>
            </div>
        </div>';
    }
    $rtrn .= '</div>';
}

$rtrn .= '
    <div id="save-filter-modal" class="parsing-modal" style="display:none;">
        <div class="parsing-modal-content" style="max-width:380px;">
            <div class="modal-header">
                <h2>'.$t['prompt_filter_name'].'</h2>
                <button class="modal-close" onclick="parsingSaveFilterCancel()">✕</button>
            </div>
            <div class="modal-section">
                <input type="text" id="save-filter-name" placeholder="'.$t['field_name_placeholder'].'" autofocus>
            </div>
            <div class="modal-actions">
                <button class="btn-secondary" onclick="parsingSaveFilterCancel()">'.$t['btn_cancel'].'</button>
                <button class="btn-primary" onclick="parsingSaveFilterConfirm()">'.$t['btn_save'].'</button>
            </div>
        </div>
    </div>

    <div id="duplicate-filter-modal" class="parsing-modal" style="display:none;">
        <div class="parsing-modal-content" style="max-width:400px;">
            <div class="modal-header">
                <h2>'.($t['filter_duplicate_exists'] ?? 'Există deja un filtru similar').'</h2>
                <button class="modal-close" onclick="parsingDuplicateCancel()">✕</button>
            </div>
            <div class="modal-section">
                <p id="duplicate-filter-text" style="margin:0;color:#4b5563;line-height:1.5;"></p>
            </div>
            <div class="modal-actions">
                <button class="btn-secondary" onclick="parsingDuplicateCancel()">'.($t['btn_cancel'] ?? 'Anulează').'</button>
                <button class="btn-primary" onclick="parsingDuplicateEdit()">'.($t['btn_edit_existing'] ?? 'Editează filtrul existent').'</button>
            </div>
        </div>
    </div>
</div><!-- /parsing-container -->

<script>
    window.PARSING_LANG = '.json_encode($t, JSON_UNESCAPED_UNICODE).';
    window.ADMIN_DIR = '.json_encode($admin_dir ?? 'adm').';
    window.ENCAR_BRANDS = '.json_encode($encarBrandsForJs, JSON_UNESCAPED_UNICODE).';
    window.ENCAR_HAS_TAXONOMY = '.(empty($encarBrandsForJs) ? 'false' : 'true').';
    window.OPENLANE_BRANDS = '.json_encode($openlaneBrandsForJs, JSON_UNESCAPED_UNICODE).';
    window.ECARS_BRANDS = '.json_encode($ecarsBrandsForJs, JSON_UNESCAPED_UNICODE).';
    window.AUTO1_BRANDS = '.json_encode($auto1BrandsForJs, JSON_UNESCAPED_UNICODE).';
</script>
<script src="/content/admin/page/parsing/parsing.js?v='.filemtime(_ADM_PAGE.'/parsing/parsing.js').'"></script>
';

echo $rtrn;
