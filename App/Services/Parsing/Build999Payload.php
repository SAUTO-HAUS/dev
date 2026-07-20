<?php

namespace App\Services\Parsing;

use PDO;

/**
 * Builds the 999.md "features" payload for a parsing car server-side, so the
 * cross-post cron can schedule cars on 999 without opening the browser form.
 *
 * Strategy — CACHE-FIRST, zero AI/API, zero risk:
 *   The 999 model(21) + generation(2095) ids are learned from cars already published
 *   on 999 (same brand/model/year already carries the correct ids, proven by a
 *   successful publish). A car whose model has never been published is SKIPPED
 *   (returns null) and left for manual publishing — we never guess a 999 model id.
 *
 * Spec → 999 feature-id maps (fuel/drive/colour/body) were extracted from real live
 * ads (see crosspost_tool.php ?do=specmap). Everything else is constant.
 *
 * Only the normal-car subcategory (659) is produced. Commercial vans (Transit,
 * Sprinter, …) use a different subcategory/features and are skipped.
 */
class Build999Payload
{
    private PDO $db;
    private string $prefix;

    // Constant header for a normal-car SAUTO Personal ad (from a real payload).
    private const CATEGORY = '658';
    private const SUBCATEGORY = '659';
    private const OFFER_TYPE = '23844';

    // Spec code → 999 feature id value (extracted from real ads).
    private const FUEL = [   // feature 151
        'dsl' => '24', 'gsl' => '10', 'hbd' => '161', 'elc' => '12617',
        'pih' => '22987', 'pid' => '43422',
        // sauto stores some as gmn/gpn/gas/gsl → petrol
        'gmn' => '10', 'gpn' => '10', 'gas' => '10',
    ];
    private const DRIVE = ['fr' => '5', 're' => '25', '44' => '17']; // feature 108
    private const COLOR = [   // feature 17
        'azr' => '31', 'bge' => '87', 'blk' => '7', 'blu' => '40', 'brn' => '208',
        'gld' => '72', 'gra' => '50', 'grn' => '13', 'orn' => '334', 'red' => '38',
        'slv' => '56', 'wht' => '19', 'ylw' => '179', 'd_grn' => '12',
    ];
    private const BODY = [    // feature 102
        'cup' => '96', 'hbk' => '11', 'mnv' => '49', 'sdn' => '6', 'suv' => '18',
        'unv' => '27', 'van' => '1047', 'pkp' => '1047', 'crv' => '18', 'mbs' => '49',
    ];
    private const GEAR = ['atm' => '2', 'mnl' => '1', 'vrr' => '2', 'tpt' => '2']; // feature 101 group? kept constant 16 in ads

    public function __construct(PDO $db, string $prefix = 'gh3sp')
    {
        $this->db = $db;
        $this->prefix = $prefix;
    }

    // Build feature 13 (description) the same way the manual 999 form does: the chosen
    // standard template (order_personal_texts.json — "AUTO LA COMANDA" / "AUTO DIN
    // COREEA") on top, then the car's own text. The publish cron adds the sauto links
    // above this. Korea cars (import_country_id 41 = South Korea, e.g. Encar) use the
    // Korea template (index 1); everyone else uses the on-order template (index 0).
    private function buildDescription(array $car): string
    {
        $own = trim((string)($car['txt'] ?? ''));

        $tplIndex = ((int)($car['import_country_id'] ?? 0) === 41) ? 1 : 0;
        $tpl = '';
        $jsonPath = (defined('_ROOT') ? _ROOT : dirname(__DIR__, 3)) . '/api/order_personal_texts.json';
        if (is_file($jsonPath)) {
            $data = json_decode((string)@file_get_contents($jsonPath), true);
            $tpl = trim((string)($data['auto_company'][$tplIndex]['text'] ?? ''));
        }

        if ($tpl === '') return $own;
        return $own !== '' ? $tpl . "\n\n" . $own : $tpl;
    }

    /**
     * Look up 999 model + generation ids for a car's brand/model/year from cars
     * already live on 999. Returns ['model'=>id,'gen'=>id,'f2553'=>id] or null when
     * the model was never published (so we don't guess).
     */
    public function resolveModelGen(string $br, string $mo, int $year): ?array
    {
        // All live 999 ads of this exact br/mo, with their year + 999 payload.
        $st = $this->db->prepare("SELECT cc.yr, cc.`999` js
            FROM {$this->prefix}_car_ctlg cc
            WHERE cc.br = ? AND cc.mo = ? AND cc.`999_id` > 0
              AND cc.`999` IS NOT NULL AND cc.`999` <> ''");
        $st->execute([$br, $mo]);
        $rows = $st->fetchAll(PDO::FETCH_ASSOC);
        // Never published on 999 → resolve live from the 999 API (same as the form).
        if (!$rows) return $this->resolveViaApi($br, $mo, $year);

        // The MODEL id is one value for the whole br/mo → take the most common (a
        // stray mis-published ad can't outvote the majority). The GENERATION changes
        // by year, so among the ads closest to this year, take the most common gen.
        // This ignores the occasional wrong manual publish (e.g. one XC60 tagged as
        // XC90) that a naive "closest single ad" would pick.
        $modelVotes = []; $byYear = [];
        foreach ($rows as $r) {
            $j = json_decode($r['js'], true);
            if (!is_array($j)) continue;
            $fx = [];
            foreach (($j['features'] ?? []) as $f) $fx[$f['id']] = $f['value'];
            $model = $fx[21] ?? null; $gen = $fx[2095] ?? null;
            if (!$model || !$gen) continue;
            $modelVotes[(string)$model] = ($modelVotes[(string)$model] ?? 0) + 1;
            $byYear[] = ['yr' => (int)$r['yr'], 'gen' => (string)$gen, 'f2553' => (string)($fx[2553] ?? '')];
        }
        if (!$modelVotes) return $this->resolveViaApi($br, $mo, $year);
        arsort($modelVotes);
        $model = (string)array_key_first($modelVotes);

        // Closest year distance, then majority gen at that distance.
        $bestDiff = PHP_INT_MAX;
        foreach ($byYear as $b) { $bestDiff = min($bestDiff, abs($b['yr'] - $year)); }
        $genVotes = []; $f2553Votes = [];
        foreach ($byYear as $b) {
            if (abs($b['yr'] - $year) !== $bestDiff) continue;
            $genVotes[$b['gen']] = ($genVotes[$b['gen']] ?? 0) + 1;
            if ($b['f2553'] !== '') $f2553Votes[$b['f2553']] = ($f2553Votes[$b['f2553']] ?? 0) + 1;
        }
        arsort($genVotes); arsort($f2553Votes);
        return [
            'model'  => $model,
            'gen'    => (string)array_key_first($genVotes),
            'f2553'  => $f2553Votes ? (string)array_key_first($f2553Votes) : '',
        ];
    }

    /**
     * Fallback for models never published on 999: ask the 999 API live (exactly what
     * the manual form does). Brand id → model list → match the sauto model name →
     * generation list → pick the generation whose year range contains the car's year.
     * Result is disk-cached per (br,mo,year) so we don't hammer the API. Null if the
     * model can't be matched — we still skip rather than guess.
     */
    private function resolveViaApi(string $br, string $mo, int $year): ?array
    {
        $markId = self::brandId($br);
        if ($markId === null) return null;

        // Disk cache.
        $ck = sys_get_temp_dir() . '/build999_api/' . preg_replace('/[^a-z0-9]+/i', '_', "{$br}_{$mo}_{$year}") . '.json';
        if (is_file($ck)) {
            $c = json_decode((string)@file_get_contents($ck), true);
            if (is_array($c)) return $c['null'] ?? false ? null : $c;
        }
        @mkdir(dirname($ck), 0775, true);

        $result = null;
        try {
            $svc = new \App\Services\Api999Service();
            // Models: dependency 20 (brand feature), parent = brand id.
            $models = $svc->getDependentOptions((int)self::SUBCATEGORY, 20, (int)$markId)['Options'] ?? [];
            if ($models) {
                $modelId = self::matchOption($models, self::displayModel($mo));
                if ($modelId !== null) {
                    // Generations: dependency 21 (model feature), parent = model id.
                    $gens = $svc->getDependentOptions((int)self::SUBCATEGORY, 21, (int)$modelId)['Options'] ?? [];
                    $genId = self::pickGenerationByYear($gens, $year);
                    if ($genId !== null) {
                        $result = ['model' => (string)$modelId, 'gen' => (string)$genId, 'f2553' => ''];
                    }
                }
            }
        } catch (\Throwable $e) { $result = null; }

        @file_put_contents($ck, json_encode($result ?? ['null' => true]));
        return $result;
    }

    // Best-match an option list by title (normalized, spaces/dashes/case ignored).
    private static function matchOption(array $options, string $want): ?string
    {
        $norm = fn($s) => preg_replace('/[^a-z0-9]+/', '', mb_strtolower(trim((string)$s), 'UTF-8'));
        $w = $norm($want);
        if ($w === '') return null;
        $exact = null; $prefix = null;
        foreach ($options as $o) {
            $t = $norm($o['title'] ?? $o['text'] ?? '');
            $id = (string)($o['id'] ?? $o['value'] ?? '');
            if ($t === '') continue;
            if ($t === $w) { $exact = $id; break; }
            if ($prefix === null && (strpos($t, $w) === 0 || strpos($w, $t) === 0)) $prefix = $id;
        }
        return $exact ?? $prefix;
    }

    // Pick the generation whose "(2015 - 2020)" / "(2021 - н. в)" range contains year.
    // Falls back to the newest generation if no range matches.
    private static function pickGenerationByYear(array $gens, int $year): ?string
    {
        if (!$gens) return null;
        $best = null; $newest = null; $newestFrom = -1;
        foreach ($gens as $g) {
            $id = (string)($g['id'] ?? $g['value'] ?? '');
            $title = (string)($g['title'] ?? $g['text'] ?? '');
            if ($id === '') continue;
            if (preg_match('/(\d{4})\s*[-–]\s*(\d{4}|н\.?\s*в|present|now)?/iu', $title, $m)) {
                $from = (int)$m[1];
                $to = (isset($m[2]) && preg_match('/\d{4}/', $m[2], $mm)) ? (int)$mm[0] : 9999;
                if ($year >= $from && $year <= $to) $best = $id;
                if ($from > $newestFrom) { $newestFrom = $from; $newest = $id; }
            } elseif ($newest === null) {
                $newest = $id;
            }
        }
        return $best ?? $newest;
    }

    // sauto model code (bmw "5_series") → a human name to match against 999 titles.
    private static function displayModel(string $mo): string
    {
        return trim(str_replace('_', ' ', $mo));
    }

    /**
     * Build the full 999 payload for a car_ctlg id. Returns
     *   ['payload'=>[category_id,subcategory_id,offer_type,announcement_type,scenario,
     *                text_option,features=>[...]], 'account_id'=>int]
     * or null if the car can't be built (model not cached / commercial / missing data).
     */
    public function build(int $carCtlgId): ?array
    {
        $st = $this->db->prepare("SELECT * FROM {$this->prefix}_car_ctlg WHERE id = ? LIMIT 1");
        $st->execute([$carCtlgId]);
        $car = $st->fetch(PDO::FETCH_ASSOC);
        if (!$car) return null;

        // Commercial group uses a different subcategory (660) and a simpler payload
        // (model is TEXT, no generation, no equipment flags). Handled separately.
        // EXCEPTION: some models are commercial pickups on the source (correct there)
        // but 999.md has no matching commercial category for them — they must go out
        // as a normal car (659). Force those here regardless of gr.
        if (($car['gr'] ?? '') === 'com' && !self::forceCarOn999($car)) {
            return $this->buildCommercial($car);
        }

        $br = (string)($car['br'] ?? '');
        $mo = (string)($car['mo'] ?? '');
        $year = (int)($car['yr'] ?? 0);
        if ($br === '' || $mo === '' || $year <= 0) return null;

        // 999 brand id from the shared map.
        $markId = self::brandId($br);
        if ($markId === null) return null;

        // Model + generation from the learned cache (skip if unknown).
        $mg = $this->resolveModelGen($br, $mo, $year);
        if (!$mg) return null;

        // Required spec values — skip if we can't map a required one.
        $fuel  = self::FUEL[strtolower((string)($car['fl'] ?? ''))] ?? null;
        $drive = self::DRIVE[(string)($car['wd'] ?? '')] ?? null;
        $body  = self::BODY[strtolower((string)($car['bt'] ?? ''))] ?? null;
        $color = self::COLOR[strtolower((string)($car['clr'] ?? ''))] ?? null;
        $km    = (int)($car['mlg'] ?? 0);
        $hp    = (int)($car['hp'] ?? 0);
        $price = (int)($car['prc'] ?? 0);
        if (!$fuel || !$drive || !$body || $km <= 0 || $hp <= 0 || $price <= 0) return null;

        // Assemble features. Constant ids copied verbatim from a real successful ad.
        $features = [
            ['id' => '20',   'value' => $markId],       // brand
            ['id' => '21',   'value' => $mg['model']],  // model
            ['id' => '2095', 'value' => $mg['gen']],    // generation
            ['id' => '19',   'value' => (string)$year], // year
            // 104 (km) and 107 (hp) MUST carry their unit — 999 rejects them as empty
            // ("Completați câmpul") without it, even though a value is present. This
            // was the real cause of the feature-104 failures.
            ['id' => '104',  'value' => (string)$km, 'unit' => 'km'],  // mileage
            ['id' => '107',  'value' => (string)$hp, 'unit' => 'hp'],  // HP
            ['id' => '2',    'value' => (string)$price, 'unit' => 'eur'],// price
            ['id' => '151',  'value' => $fuel],         // fuel
            ['id' => '108',  'value' => $drive],        // drive
            ['id' => '102',  'value' => $body],         // body
            // constants seen on every ad
            ['id' => '7',    'value' => '12900'],   // required category const (was missing)
            ['id' => '775',  'value' => '18594'],
            ['id' => '593',  'value' => '18668'],
            ['id' => '1761', 'value' => '29672'],
            ['id' => '1763', 'value' => '29677'],
            ['id' => '795',  'value' => '23241'],
            ['id' => '1196', 'value' => '21979'],
            ['id' => '846',  'value' => '19119'],
            ['id' => '851',  'value' => '19086'],
            ['id' => '101',  'value' => '16'],
        ];
        if ($color !== null) $features[] = ['id' => '17', 'value' => $color];
        if ($mg['f2553'] !== '') $features[] = ['id' => '2553', 'value' => $mg['f2553']];

        // All equipment flags on (matches the "maximal" scenario ads).
        $equipmentIds = ['109','115','110','114','116','1639','113','111','112','119','118',
            '126','124','125','128','123','117','1638','122','1766','2201','130','131','132',
            '133','134','135','136','137','138','139','140','141','142','143','144','145',
            '147','148','149','150'];
        foreach ($equipmentIds as $eid) $features[] = ['id' => $eid, 'value' => '1'];

        // Description (feature 13) — standard template + car text (same as the manual
        // form). The schedule cron prepends the sauto links on top when publishing.
        $desc = $this->buildDescription($car);
        if ($desc !== '') $features[] = ['id' => '13', 'value' => $desc];

        // Photos (14) and phone (16) are added by the personal cron at publish time.

        return [
            'account_id' => $this->resolveAccount($car),
            'payload' => [
                'category_id'       => self::CATEGORY,
                'subcategory_id'    => self::SUBCATEGORY,
                'offer_type'        => self::OFFER_TYPE,
                'announcement_type' => 'sauto_personal',
                'scenario'          => 'maximal',
                'text_option'       => '0',
                'features'          => $features,
            ],
        ];
    }

    private const FORCE_CAR_ON_999 = [
        'tesla|cybertruck',
        'ford|ranger',
    ];
    private static function forceCarOn999(array $car): bool
    {
        $key = mb_strtolower(trim((string)($car['br'] ?? ''))) . '|'
             . mb_strtolower(trim((string)($car['mo'] ?? '')));
        return in_array($key, self::FORCE_CAR_ON_999, true);
    }

    // Commercial vans/trucks (gr=com) → subcategory 660, offer 776. Much simpler:
    // model is a TEXT field (id 585), no generation, no equipment flags. Body is
    // always 1047. Extra fields: cc (103), seats (105), km (1408), weight (152).
    private function buildCommercial(array $car): ?array
    {
        $br = (string)($car['br'] ?? '');
        $moNm = (string)($car['mo_nm'] ?? '');
        $year = (int)($car['yr'] ?? 0);
        if ($br === '' || $moNm === '' || $year <= 0) return null;

        $markId = self::brandId($br);
        if ($markId === null) return null;

        $fuel  = self::FUEL[strtolower((string)($car['fl'] ?? ''))] ?? null;
        $drive = self::DRIVE[(string)($car['wd'] ?? '')] ?? null;
        $gear  = self::GEAR_COM[strtolower((string)($car['tra'] ?? ''))] ?? null;
        $color = self::COLOR[strtolower((string)($car['clr'] ?? ''))] ?? '19';
        $km    = (int)($car['mlg'] ?? 0);
        $cc    = (int)($car['vol'] ?? 0);
        $price = (int)($car['prc'] ?? 0);
        $seats = (int)($car['sts'] ?? 0) ?: 3;
        if (!$fuel || !$drive || !$gear || $km <= 0 || $price <= 0) return null;

        $features = [
            ['id' => '20',   'value' => $markId],          // brand
            ['id' => '585',  'value' => $moNm],            // model = TEXT
            ['id' => '775',  'value' => '18592'],          // (commercial constant)
            ['id' => '593',  'value' => '18668'],
            ['id' => '7',    'value' => '12900'],
            ['id' => '2',    'value' => (string)$price],   // price
            ['id' => '1196', 'value' => '21979'],
            ['id' => '151',  'value' => $fuel],            // fuel
            ['id' => '101',  'value' => $gear],            // gearbox
            ['id' => '102',  'value' => '1047'],           // body (always van/truck)
            ['id' => '17',   'value' => $color],           // colour
            ['id' => '19',   'value' => (string)$year],    // year
            ['id' => '105',  'value' => (string)$seats],   // seats
            ['id' => '1408', 'value' => (string)$km],      // mileage (commercial id)
            ['id' => '152',  'value' => '1500'],           // weight (fixed default)
            ['id' => '108',  'value' => $drive],           // drive
        ];
        $isElectric = strtolower((string)($car['fl'] ?? '')) === 'elc';
        $cc103 = ($isElectric || $cc < 998) ? 998 : $cc;
        $features[] = ['id' => '103', 'value' => (string)$cc103];

        $desc = $this->buildDescription($car);
        if ($desc !== '') $features[] = ['id' => '13', 'value' => $desc];

        return [
            'account_id' => $this->resolveAccount($car, true),
            'payload' => [
                'category_id'       => self::CATEGORY,
                'subcategory_id'    => '660',
                'offer_type'        => '776',
                'announcement_type' => 'sauto_personal',
                'scenario'          => 'maximal',
                'text_option'       => '0',
                'features'          => $features,
            ],
        ];
    }

    // Commercial gearbox map (id 101): atm=16, mnl=4 (from real ads).
    private const GEAR_COM = ['atm' => '16', 'mnl' => '4', 'vrr' => '16', 'tpt' => '16'];

    // Which 999 account this car publishes under (same logic as sauto_personal_cron).
    private function resolveAccount(array $car, bool $isCom = false): int
    {
        // Commercial from Korea (Encar, import_country_id=41) → account 4 (Encars-MD),
        // same as non-commercial Korean cars. Commercial from everywhere else → account
        // 2 (Sauto-comerciale), which carries that account's phone. This must win even
        // over a stale 999_api_id (imports default it to 1, which has no forced phone →
        // the ad kept the source phone).
        if ($isCom) {
            return ((int)($car['import_country_id'] ?? 0) === 41) ? 4 : 2;
        }
        if (!empty($car['999_api_id'])) return (int)$car['999_api_id'];
        $importCountry = (int)($car['import_country_id'] ?? 0);
        if ($importCountry === 41) return 4;        // Korea → Encars-MD
        return 3;                                   // else → Sauto-stock-extern
    }

    // sauto brand code → 999 brand id (feature 20). Mirrors order_car.php brandMapping.
    public static function brandId(string $br): ?string
    {
        static $m = [
            'acura'=>'392','alfa_romeo'=>'295','audi'=>'57','bentley'=>'288','bmw'=>'34',
            'brilliance'=>'748','byd'=>'487','cadillac'=>'439','chery'=>'119','chevrolet'=>'167',
            'chrysler'=>'101','citroen'=>'32','cupra'=>'24455','dacia'=>'375','daewoo'=>'99',
            'daihatsu'=>'132','dodge'=>'89','ds_automobiles'=>'24352','faw'=>'504','fiat'=>'41',
            'ford'=>'139','geely'=>'587','gmc'=>'616','great_wall'=>'202','haima'=>'521',
            'haval'=>'23260','honda'=>'149','hummer'=>'247','hyundai'=>'111','infiniti'=>'419',
            'isuzu'=>'14','iveco'=>'1049','jaguar'=>'369','jeep'=>'186','kia'=>'130',
            'lamborghini'=>'12462','lancia'=>'210','land_rover'=>'291','lexus'=>'136','lifan'=>'414',
            'lincoln'=>'305','lotus'=>'1743','maserati'=>'1704','mazda'=>'45','mercedes_benz'=>'22',
            'mini'=>'577','mitsubishi'=>'36','nissan'=>'28','opel'=>'1','peugeot'=>'76',
            'pontiac'=>'284','porsche'=>'282','renault'=>'8','renault_samsung'=>'27737',
            'rolls_royce'=>'266','rover'=>'62','saab'=>'344','seat'=>'200','skoda'=>'143',
            'smart'=>'263','ssangyong'=>'397','subaru'=>'121','suzuki'=>'43','tata'=>'883',
            'tesla'=>'17483','toyota'=>'47','volkswagen'=>'20','volvo'=>'193',
        ];
        return $m[strtolower($br)] ?? null;
    }
}
