<?php

namespace App\Services\Parsing;

use PDO;

/**
 * Groq spec enrichment for parsed cars (HP, drive, cc, fuel, gearbox, body,
 * seats). Single source of truth, shared by:
 *   - the card buttons (ajax 'ai_enrich_specs', manual publish)
 *   - the publish queue (auto-publish), so auto-published cars get the same
 *     complete specs as manually published ones.
 *
 * Encar never exposes HP/drive_type; Groq fills them. For eCarsTrade/OpenLane it
 * re-verifies 6 fields once per car (ai_verified flag), then only fills gaps.
 * Returns ['success'=>bool, 'hp','drive_type',...] — same shape the AJAX used.
 */
class SpecEnricher
{
    public static function enrich(PDO $db, string $prefx, int $carId): array
    {
        if ($carId <= 0) return ['success' => false, 'error' => 'Invalid car_id'];

        self::ensureAiVerifiedColumn($db, $prefx);
        $verifiedNow = (int)$db->query("SELECT ai_verified FROM {$prefx}_parsing_cars WHERE id = ".(int)$carId)->fetchColumn();
        if (!$verifiedNow) {
            try {
                (new ParsingOrchestrator())->enrichOnePublic($carId);
            } catch (\Throwable $e) { /* best-effort */ }
        }

        $stmt = $db->prepare("SELECT id, brand, model, year, engine_volume, fuel_type, power_hp, drive_type, seats, body_type, gearbox, color, vin, raw_data,
                              source, source_id, report_data, ai_verified
                              FROM {$prefx}_parsing_cars WHERE id = ? LIMIT 1");
        $stmt->execute([$carId]);
        $car = $stmt->fetch(PDO::FETCH_ASSOC);
        if (!$car) return ['success' => false, 'error' => 'Car not found'];

        // Encar only: pull the inspection report once and cache it. Best-effort.
        if (($car['source'] ?? '') === 'encar' && empty($car['report_data']) && !empty($car['source_id'])) {
            try {
                $adapter = new Adapters\EncarAdapter();
                $report = $adapter->fetchInspectionReport((string)$car['source_id']);
                if ($report) {
                    $db->prepare("UPDATE {$prefx}_parsing_cars SET report_data = ? WHERE id = ?")
                       ->execute([json_encode($report, JSON_UNESCAPED_UNICODE), $carId]);
                }
            } catch (\Throwable $e) { /* best-effort */ }
        }

        if (empty($car['color'])) {
            $db->prepare("UPDATE {$prefx}_parsing_cars SET color = 'blk' WHERE id = ?")->execute([$carId]);
            $car['color'] = 'blk';
        }

        $alreadyVerified = !empty($car['ai_verified']);
        $forceVerify = !$alreadyVerified
            && in_array(($car['source'] ?? ''), ['ecarstrade', 'openlane'], true);
        $forceFields = ['fuel_type','gearbox','engine_volume','power_hp','drive_type','body_type'];
        $trustVin = ($car['source'] ?? '') !== 'ecarstrade';

        // If every AI-fillable field is already set, return cached (no AI call).
        if (!$forceVerify
            && $car['power_hp'] && $car['drive_type'] && $car['seats']
            && $car['engine_volume'] && $car['body_type'] && $car['fuel_type']) {
            return [
                'success'   => true,
                'hp'        => (int)$car['power_hp'],
                'drive_type'=> $car['drive_type'],
                'seats'     => (int)$car['seats'],
                'color'     => $car['color'],
                'cached'    => true,
            ];
        }

        $raw = json_decode($car['raw_data'] ?? '{}', true) ?: [];
        $rawData = $raw['raw_data'] ?? [];
        $category = $rawData['category'] ?? [];
        $grade = $category['gradeEnglishName'] ?? $category['gradeName'] ?? ($raw['title'] ?? '');

        // CLI (publish worker) doesn't always load config.php, so fall back to
        // reading GROQ_API_KEY straight from .env (same as parsing_trims_helper).
        $apiKey = defined('GROQ_API_KEY') ? GROQ_API_KEY : '';
        if (empty($apiKey)) {
            $root = $_SERVER['DOCUMENT_ROOT'] ?? realpath(__DIR__ . '/../../..');
            $envFile = rtrim((string)$root, '/\\') . '/.env';
            if (is_file($envFile)) {
                $env = file_get_contents($envFile);
                if (preg_match('/GROQ_API_KEY=(.+)/', $env, $m)) $apiKey = trim($m[1]);
            }
        }
        if (empty($apiKey)) {
            return ['success' => false, 'error' => 'No Groq API key configured'];
        }
        $apiUrl = 'https://api.groq.com/openai/v1/chat/completions';
        $models = [
            'llama-3.3-70b-versatile',  
            'openai/gpt-oss-120b',      
            'meta-llama/llama-4-scout-17b-16e-instruct', 
            'llama-3.1-8b-instant',    
            'groq/compound',          
        ];

        $need = [
            'hp'            => empty($car['power_hp'])     || ($forceVerify && in_array('power_hp', $forceFields, true)),
            'drive_type'    => empty($car['drive_type'])   || ($forceVerify && in_array('drive_type', $forceFields, true)),
            'seats'         => empty($car['seats']),
            'engine_volume' => empty($car['engine_volume'])|| ($forceVerify && in_array('engine_volume', $forceFields, true)),
            'body_type'     => empty($car['body_type'])    || ($forceVerify && in_array('body_type', $forceFields, true)),
            'fuel_type'     => empty($car['fuel_type'])    || ($forceVerify && in_array('fuel_type', $forceFields, true)),
            'gearbox'       => empty($car['gearbox'])      || ($forceVerify && in_array('gearbox', $forceFields, true)),
        ];

        $fieldSpecs = [
            'hp'            => '"hp": <integer power in HP>',
            'drive_type'    => '"drive_type": "4x4"|"fwd"|"rwd"',
            'seats'         => '"seats": <integer 2-9>',
            'engine_volume' => '"engine_volume": <integer engine displacement in cc, e.g. 1984>',
            'body_type'     => '"body_type": "sedan"|"hatchback"|"wagon"|"suv"|"coupe"|"convertible"|"minivan"|"van"|"pickup"',
            'fuel_type'     => '"fuel_type": "benzina"|"diesel"|"hybrid"|"hybrid_plugin"|"diesel_hybrid"|"electric"|"lpg"|"gasoline_cng" (hybrid=full hybrid petrol, hybrid_plugin=plug-in petrol PHEV, diesel_hybrid=plug-in diesel)',
            'gearbox'       => '"gearbox": "automat"|"manual"|"cvt"|"semi-auto"',
        ];
        $wanted = [];
        foreach ($need as $k => $missing) { if ($missing) $wanted[] = $fieldSpecs[$k]; }
        if (!$wanted) {
            return ['success' => true, 'hp' => (int)$car['power_hp'], 'drive_type' => $car['drive_type'],
                    'seats' => (int)$car['seats'], 'cached' => true];
        }

        $identity = [
            'Brand' => $car['brand'], 'Model' => $car['model'], 'Year' => $car['year'],
            'Grade' => $grade,
        ];
        if ($trustVin) $identity['VIN'] = $car['vin'] ?? '';
        $facts = [];
        foreach ($identity as $label => $v) {
            if ($v !== null && $v !== '') $facts[] = "{$label}: {$v}";
        }

        $specPairs = [
            'Engine'  => $car['engine_volume'] ? $car['engine_volume'].' cc' : '',
            'Power'   => $car['power_hp'] ? $car['power_hp'].' hp' : '',
            'Fuel'    => $car['fuel_type'] ?? '',
            'Gearbox' => $car['gearbox'] ?? '',
            'Body'    => $car['body_type'] ?? '',
        ];
        $numericAnchors = ['Engine', 'Power']; // dropped on re-verify to avoid anchoring
        foreach ($specPairs as $label => $v) {
            if ($v === null || $v === '') continue;
            if ($forceVerify && in_array($label, $numericAnchors, true)) continue;
            $facts[] = $forceVerify ? "{$label} (reported, may be wrong): {$v}" : "{$label}: {$v}";
        }

        if ($forceVerify) {
            $idHint = $trustVin ? "brand/model/year/grade/VIN" : "brand/model/year/grade";
            $prompt = "Identify the EXACT real-world variant of this car from its {$idHint} "
                . "and return its real specs. Reason in this order: (1) determine the correct "
                . "fuel_type for this exact model/year — only change the reported fuel if it is "
                . "clearly wrong for this variant. Fuel rules (use ONLY these codes): a DIESEL "
                . "engine stays diesel — never convert it to petrol; 48V mild-hybrid (MHEV) is "
                . "NOT a hybrid here → use 'diesel' or 'benzina' by its base engine; full self-"
                . "charging hybrid → 'hybrid' (petrol) or 'diesel_hybrid' (diesel); plug-in → "
                . "'hybrid_plugin' (petrol) or 'diesel_hybrid' (diesel PHEV, e.g. Audi e-tron "
                . "TDI, Mercedes 300de). (2) Then give hp as the REAL power (for full/plug-in "
                . "hybrids the COMBINED system power, not the engine alone), plus engine_volume, "
                . "drive_type and body_type. "
                . "Return ONLY JSON with exactly these keys, values strictly from the listed "
                . "options: {" . implode(', ', $wanted) . "}.\n"
                . implode(' | ', $facts);
        } else {
            $prompt = "Given this car, estimate ONLY the missing specs from real-world data "
                . "(use the VIN to identify the exact trim/engine when possible). "
                . "Return ONLY JSON with exactly these keys: {" . implode(', ', $wanted) . "}.\n"
                . implode(' | ', $facts);
        }

        // Try each model in turn. A model is only "good" if it returns HTTP 200 AND
        // parseable JSON — so a model that's rate-limited (429), errors (5xx), or
        // answers in an off-format (e.g. groq/compound's tool wrapper) all fall
        // through to the next one. Only a usable JSON answer stops the loop.
        $parsed = null; $lastErr = '';
        foreach ($models as $model) {
            $payload = [
                'model'    => $model,
                'messages' => [
                    ['role' => 'system', 'content' => 'You output only valid JSON. No markdown, no commentary.'],
                    ['role' => 'user',   'content' => $prompt],
                ],
                'temperature' => 0,
                'max_tokens'  => 150,
            ];

            $ch = curl_init($apiUrl);
            curl_setopt_array($ch, [
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_POST           => true,
                CURLOPT_POSTFIELDS     => json_encode($payload),
                CURLOPT_TIMEOUT        => 40,
                CURLOPT_HTTPHEADER     => [
                    'Authorization: Bearer ' . $apiKey,
                    'Content-Type: application/json',
                ],
            ]);
            $body  = curl_exec($ch);
            $code  = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            $err   = curl_error($ch);
            curl_close($ch);

            if ($code !== 200) {
                $lastErr = "AI HTTP {$code} ({$model}): " . substr((string)$body, 0, 140) . " | curl: {$err}";
                continue;   // rate-limited / error → next model
            }

            $aiResp  = json_decode($body, true);
            $content = $aiResp['choices'][0]['message']['content'] ?? '';
            $content = trim(preg_replace('/^```(?:json)?|```$/m', '', $content));
            $maybe   = json_decode($content, true);
            if (is_array($maybe)) { $parsed = $maybe; break; }   // good answer → done

            $lastErr = "Unparseable output ({$model}): " . substr($content, 0, 140);
            // off-format answer → try the next model
        }

        if (!is_array($parsed)) {
            return ['success' => false, 'error' => $lastErr ?: 'All AI models failed'];
        }

        $bodyCodes = ['sedan','hatchback','wagon','suv','coupe','convertible','minivan','van','pickup'];
        $out = ['success' => true];

        $significantNum = function ($old, $new, float $tol = 0.10): bool {
            $old = (int)$old; $new = (int)$new;
            if ($old <= 0) return true;
            return abs($new - $old) > $old * $tol;
        };

        if ($need['hp']) {
            $hp = (int)($parsed['hp'] ?? 0);
            if ($hp > 0 && $hp <= 2000 && (!$forceVerify || $significantNum($car['power_hp'], $hp))) {
                $db->prepare("UPDATE {$prefx}_parsing_cars SET power_hp = ? WHERE id = ?")->execute([$hp, $carId]);
                $out['hp'] = $hp;
            } else { $out['hp'] = (int)$car['power_hp']; }
        } else { $out['hp'] = (int)$car['power_hp']; }

        if ($need['drive_type']) {
            $drive = strtolower($parsed['drive_type'] ?? '');
            if (in_array($drive, ['4x4','fwd','rwd'], true)) {
                $db->prepare("UPDATE {$prefx}_parsing_cars SET drive_type = ? WHERE id = ?")->execute([$drive, $carId]);
                $out['drive_type'] = $drive;
            } else { $out['drive_type'] = $car['drive_type']; }
        } else { $out['drive_type'] = $car['drive_type']; }

        if ($need['seats']) {
            $seats = (int)($parsed['seats'] ?? 0);
            if ($seats >= 2 && $seats <= 9) {
                $db->prepare("UPDATE {$prefx}_parsing_cars SET seats = ? WHERE id = ?")->execute([$seats, $carId]);
                $out['seats'] = $seats;
            }
        } else { $out['seats'] = (int)$car['seats']; }

        if ($need['engine_volume']) {
            $cc = (int)($parsed['engine_volume'] ?? 0);
            if ($cc >= 600 && $cc <= 9000 && (!$forceVerify || $significantNum($car['engine_volume'], $cc))) {
                $db->prepare("UPDATE {$prefx}_parsing_cars SET engine_volume = ? WHERE id = ?")->execute([$cc, $carId]);
                $out['engine_volume'] = $cc;
            } else { $out['engine_volume'] = (int)$car['engine_volume']; }
        }

        if ($need['body_type']) {
            $bt = strtolower($parsed['body_type'] ?? '');
            if (in_array($bt, $bodyCodes, true)) {
                $db->prepare("UPDATE {$prefx}_parsing_cars SET body_type = ? WHERE id = ?")->execute([$bt, $carId]);
                $out['body_type'] = $bt;
            }
        }

        if ($need['fuel_type']) {
            $ft = strtolower($parsed['fuel_type'] ?? '');
            $fuelCodes = ['benzina','diesel','hybrid','hybrid_plugin','diesel_hybrid','electric','lpg','gasoline_cng','gasoline_lpg'];
            if (in_array($ft, $fuelCodes, true)) {
                $db->prepare("UPDATE {$prefx}_parsing_cars SET fuel_type = ? WHERE id = ?")->execute([$ft, $carId]);
                $out['fuel_type'] = $ft;
            }
        }

        if ($need['gearbox']) {
            $gb = strtolower(trim($parsed['gearbox'] ?? ''));
            if (in_array($gb, ['automat','manual','cvt','semi-auto'], true)) {
                $db->prepare("UPDATE {$prefx}_parsing_cars SET gearbox = ? WHERE id = ?")->execute([$gb, $carId]);
                $out['gearbox'] = $gb;
            }
        }

        $out['color'] = $car['color'];

        // AI ran for this car → mark it so later clicks skip re-verification.
        $db->prepare("UPDATE {$prefx}_parsing_cars SET ai_verified = 1 WHERE id = ?")->execute([$carId]);

        $fin = $db->prepare("SELECT engine_volume, fuel_type, year, source, price_eur
                             FROM {$prefx}_parsing_cars WHERE id = ? LIMIT 1");
        $fin->execute([$carId]);
        if ($f = $fin->fetch(PDO::FETCH_ASSOC)) {
            $out['md_inputs'] = [
                'capacity'  => (int)$f['engine_volume'],
                'fuel'      => $f['fuel_type'],
                'year'      => (int)$f['year'],
                'source'    => $f['source'],
                'price_eur' => (float)$f['price_eur'],
            ];
        }

        return $out;
    }

    private static function ensureAiVerifiedColumn(PDO $db, string $prefx): void
    {
        try {
            $cols = $db->query("SHOW COLUMNS FROM {$prefx}_parsing_cars LIKE 'ai_verified'");
            if ($cols && $cols->rowCount() === 0) {
                $db->exec("ALTER TABLE {$prefx}_parsing_cars
                    ADD COLUMN `ai_verified` TINYINT(1) NOT NULL DEFAULT 0");
            }
        } catch (\Throwable $e) { /* best-effort */ }
    }
}
