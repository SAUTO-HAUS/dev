<?php
/**
 * Korean-trim → Latin translation, shared by the parsing crons.
 *
 * Both parsing_cron.php (every 5 min) and parsing_enrich.php include this so the
 * function is defined once. It:
 *   1. translates any Korean car_sub_model via Groq (cached in parsing_settings),
 *   2. applies the cache to car_sub_model,
 *   3. repairs title_ro (which is frozen at import time with the Korean trim) —
 *      first from the cache, then by pulling Korean phrases straight out of the
 *      titles and translating those too (covers trims that came from extractTrim's
 *      static map and never entered the cache).
 */

if (!function_exists('translate_korean_trims')) {
    function translate_korean_trims(PDO $db, string $prefx): int
    {
        $groqKey = defined('GROQ_API_KEY') ? GROQ_API_KEY : '';
        if ($groqKey === '') {
            $env = @file_get_contents(__DIR__ . '/../.env');
            if ($env && preg_match('/GROQ_API_KEY=(.+)/', $env, $m)) $groqKey = trim($m[1]);
        }

        // Helper: batch-translate Korean phrases via Groq. Returns [kr => latin].
        $groqTranslate = function (array $phrases) use ($groqKey): array {
            if (!$phrases || $groqKey === '') return [];
            $prompt = "These are Korean car TRIM / grade names from Encar listings. "
                . "Translate/transliterate each into its standard Latin marketing name. "
                . "Translate EVERY Korean word, including multi-word phrases "
                . "(노블레스->Noblesse, 롱 레인지->Long Range, 플래티넘 에디션->Platinum Edition, "
                . "인텐스 파노라믹->Intense Panoramic, 테크노->Techno). "
                . "The result MUST contain no Korean characters. "
                . "Return ONLY a JSON array of strings, SAME length and order. Input:\n"
                . json_encode(array_values($phrases), JSON_UNESCAPED_UNICODE);
            $payload = ['model' => 'meta-llama/llama-4-scout-17b-16e-instruct',
                'messages' => [['role' => 'system', 'content' => 'You output only a valid JSON array of strings. No markdown.'],
                               ['role' => 'user', 'content' => $prompt]],
                'temperature' => 0, 'max_tokens' => 2000];
            $ch = curl_init('https://api.groq.com/openai/v1/chat/completions');
            curl_setopt_array($ch, [CURLOPT_RETURNTRANSFER => true, CURLOPT_POST => true,
                CURLOPT_POSTFIELDS => json_encode($payload), CURLOPT_TIMEOUT => 40,
                CURLOPT_HTTPHEADER => ['Authorization: Bearer ' . $groqKey, 'Content-Type: application/json']]);
            $body = curl_exec($ch); $code = curl_getinfo($ch, CURLINFO_HTTP_CODE); curl_close($ch);
            if ($code !== 200 || !$body) return [];
            $content = trim(preg_replace('/^```(?:json)?|```$/m', '', json_decode($body, true)['choices'][0]['message']['content'] ?? ''));
            $tr = json_decode($content, true);
            if (!is_array($tr) || count($tr) !== count($phrases)) return [];
            $map = [];
            foreach (array_values($phrases) as $i => $kr) {
                $latin = trim((string)($tr[$i] ?? ''));
                if ($latin !== '' && $latin !== $kr && !preg_match('/[\x{AC00}-\x{D7A3}]/u', $latin)) $map[$kr] = $latin;
            }
            return $map;
        };

        $get = $db->prepare("SELECT setting_value FROM {$prefx}_parsing_settings WHERE setting_key = ?");
        $put = $db->prepare("INSERT INTO {$prefx}_parsing_settings (setting_key, setting_value)
            VALUES (?, ?) ON DUPLICATE KEY UPDATE setting_value = VALUES(setting_value)");
        $updSub = $db->prepare("UPDATE {$prefx}_parsing_cars SET car_sub_model = ?
            WHERE source = 'encar' AND car_sub_model = ?");
        $updTitle = $db->prepare("UPDATE {$prefx}_parsing_cars
            SET title_ro = REPLACE(title_ro, ?, ?)
            WHERE source = 'encar' AND title_ro LIKE CONCAT('%', ?, '%')");

        // 1) Korean trims still in car_sub_model → translate (cache + Groq) and apply.
        $rows = $db->query("SELECT DISTINCT car_sub_model FROM {$prefx}_parsing_cars
            WHERE source = 'encar' AND car_sub_model REGEXP '[가-힣]'")->fetchAll(PDO::FETCH_COLUMN);
        $cache = [];
        $todo = [];
        foreach ($rows as $kr) {
            $get->execute(['trimkr_' . $kr]);
            $cached = $get->fetchColumn();
            if ($cached !== false && $cached !== '') $cache[$kr] = $cached;
            else $todo[] = $kr;
        }
        foreach ($groqTranslate($todo) as $kr => $latin) {
            $cache[$kr] = $latin;
            $put->execute(['trimkr_' . $kr, $latin]);
        }
        foreach ($cache as $kr => $latin) {
            $updSub->execute([$latin, $kr]);
        }

        // 2) Repair title_ro from the full cache (longest first so multi-word wins).
        $allTrims = $db->query("SELECT setting_key, setting_value FROM {$prefx}_parsing_settings
            WHERE setting_key LIKE 'trimkr\\_%'")->fetchAll(PDO::FETCH_KEY_PAIR);
        $cacheMap = [];
        foreach ($allTrims as $key => $latin) {
            $kr = substr($key, strlen('trimkr_'));
            if ($kr !== '' && $latin !== '' && $kr !== $latin) $cacheMap[$kr] = $latin;
        }
        uksort($cacheMap, fn($a, $b) => mb_strlen($b) - mb_strlen($a));
        foreach ($cacheMap as $kr => $latin) {
            $updTitle->execute([$kr, $latin, $kr]);
        }

        // 3) Any title_ro still holding Korean the cache didn't cover: pull the
        //    Korean phrases out, translate, REPLACE, and seed the cache.
        $titles = $db->query("SELECT title_ro FROM {$prefx}_parsing_cars
            WHERE source = 'encar' AND title_ro REGEXP '[가-힣]'")->fetchAll(PDO::FETCH_COLUMN);
        $phrases = [];
        foreach ($titles as $tt) {
            if (preg_match_all('/[\x{AC00}-\x{D7A3}]+(?:\s+[\x{AC00}-\x{D7A3}]+)*/u', $tt, $mm)) {
                foreach ($mm[0] as $p) { $p = trim($p); if ($p !== '') $phrases[$p] = true; }
            }
        }
        $phrases = array_keys($phrases);
        if ($phrases) {
            $map = $groqTranslate($phrases);
            uksort($map, fn($a, $b) => mb_strlen($b) - mb_strlen($a));
            foreach ($map as $kr => $latin) {
                $put->execute(['trimkr_' . $kr, $latin]);
                $updTitle->execute([$kr, $latin, $kr]);
                $updSub->execute([$latin, $kr]);
            }
        }

        return count($cache);
    }
}
