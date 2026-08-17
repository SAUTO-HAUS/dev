<?php
/**
 * Encar taxonomy dump — pulls the full brand → model → submodel tree
 * from Encar's interactive-navigation endpoint and saves it locally.
 *
 * Can run via CLI:
 *   php console/encar_taxonomy_dump.php
 *
 * Or via browser (requires the token below):
 *   https://your-site/console/encar_taxonomy_dump.php?token=encar2026dump
 */

error_reporting(E_ALL);
ini_set('display_errors', 1);
ignore_user_abort(true);
set_time_limit(0);

// Allow browser access with token.
$EXPECTED_TOKEN = 'encar2026dump';
if (php_sapi_name() !== 'cli') {
    if (($_GET['token'] ?? '') !== $EXPECTED_TOKEN) {
        http_response_code(403);
        die('Forbidden — missing or invalid token. Append ?token=' . $EXPECTED_TOKEN . ' to the URL.');
    }
    // HTML, not text/plain: a paused run continues itself with a <meta refresh>, and
    // plain text would only print that tag instead of acting on it. <pre> keeps the
    // log looking exactly the same.
    header('Content-Type: text/html; charset=utf-8');
    echo "<pre style=\"font:13px/1.4 monospace\">";
    set_time_limit(600); // long script
}

chdir(__DIR__);

$outFile = realpath(__DIR__ . '/..') . '/App/Services/Parsing/Adapters/encar_taxonomy.json';

function encar_call(string $url): ?array
{
    // Kept in a variable so a retry can rebuild an identical request.
    $options = [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_FOLLOWLOCATION => true,
        CURLOPT_TIMEOUT        => 30,
        CURLOPT_SSL_VERIFYPEER => false,
        CURLOPT_SSL_VERIFYHOST => false,
        CURLOPT_HTTPHEADER     => [
            'Accept: application/json',
            'Referer: https://www.encar.com/',
            'User-Agent: Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/120.0.0.0 Safari/537.36',
        ],
    ];
    $ch = curl_init($url);
    curl_setopt_array($ch, $options);
    // Encar's CDN blocks this server's address (it answers 407), so leave through the
    // proxy from .env when one is configured. Without it, nothing changes.
    if (!class_exists('\App\Services\Parsing\OutboundProxy')) {
        require_once __DIR__ . '/../App/Services/Parsing/OutboundProxy.php';
    }
    \App\Services\Parsing\OutboundProxy::apply($ch, 'encar');

    $body = curl_exec($ch);
    $status = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $err = curl_error($ch);
    curl_close($ch);

    // 407 is Encar's CDN telling us to slow down; it lifts after a while. Give it a
    // few chances instead of losing the whole run — a full dump is ~1500 requests and
    // hitting one refusal in the middle used to throw all of it away.
    static $backoff = [10, 30, 60];
    if ($status === 407) {
        foreach ($backoff as $wait) {
            echo "  [407] refused — waiting {$wait}s and retrying...\n";
            @ob_flush(); @flush();
            sleep($wait);
            $ch = curl_init($url);
            curl_setopt_array($ch, $options);
            \App\Services\Parsing\OutboundProxy::apply($ch, 'encar');
            $body = curl_exec($ch);
            $status = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            curl_close($ch);
            if ($status !== 407) break;
        }
    }

    // Status 0 means the request never left — almost always a proxy that does not
    // answer. Without the cURL message that looks identical to "Encar refused us".
    echo "  [{$status}] {$url}" . ($status === 0 && $err !== '' ? "  — {$err}" : '') . "\n";
    @ob_flush(); @flush();
    if ($status !== 200 || empty($body)) {
        return null;
    }
    return json_decode($body, true);
}

/**
 * Walk iNav.Nodes recursively looking for any Node whose Name matches $facetKey.
 * Each match yields the Facets[] array (each facet has Value/DisplayValue/Count).
 */
function extract_facet_options(array $data, string $facetKey): array
{
    $found = [];
    $stack = [$data];
    while ($stack) {
        $cur = array_pop($stack);
        if (!is_array($cur)) continue;

        if (isset($cur['Name']) && $cur['Name'] === $facetKey && !empty($cur['Facets'])) {
            foreach ($cur['Facets'] as $facet) {
                $value = $facet['Value'] ?? null;
                if ($value === null) continue;
                $engName = $facet['Metadata']['EngName'][0] ?? null;
                $minYearVal = $facet['Metadata']['MinYear'][0] ?? $facet['Metadata']['FromYear'][0] ?? null;
                $maxYearVal = $facet['Metadata']['MaxYear'][0] ?? $facet['Metadata']['ToYear'][0] ?? null;
                $minYear = $minYearVal ? (int)substr((string)$minYearVal, 0, 4) : null;
                $maxYear = $maxYearVal ? (int)substr((string)$maxYearVal, 0, 4) : null;
                $found[$value] = [
                    'value' => $value,
                    'label' => $facet['DisplayValue'] ?? $value,
                    'eng_name' => $engName,
                    'count' => $facet['Count'] ?? 0,
                    'min_year' => $minYear,
                    'max_year' => $maxYear,
                ];
            }
        }

        foreach ($cur as $v) {
            if (is_array($v)) $stack[] = $v;
        }
    }
    return $found;
}

$taxonomy = [
    'generated_at' => date('c'),
    'brands' => [],
];

// Encar has ~75 brands and each one costs a request (plus one per model when
// generations are on), so a full run outlives any web request. Work to a time budget,
// keep the half-finished tree in a .part file and pick it up on the next call — in a
// browser the page reloads itself until it is done.
$budget   = max(5, min(120, (int)($_GET['seconds'] ?? (php_sapi_name() === 'cli' ? 3600 : 20))));
$deadline = microtime(true) + $budget;
$partFile = $outFile . '.part';
$timedOut = false;

if (is_file($partFile)) {
    $resumed = json_decode((string)file_get_contents($partFile), true);
    if (is_array($resumed) && !empty($resumed['brands'])) {
        $taxonomy = $resumed;
        $doneSoFar = count(array_filter($taxonomy['brands'], fn($b) => !empty($b['_done'])));
        echo "Resuming — {$doneSoFar} brand(s) already collected.\n";
    }
}

// DEBUG: dump first response raw so we can see iNav structure.
$debugFile = realpath(__DIR__ . '/..') . '/logs/encar_inav_debug.json';
@mkdir(dirname($debugFile), 0755, true);

// Generations are part of the taxonomy, not an extra: without them the file drops
// from ~260 KB to ~110 KB. This used to read $_GET only, so the weekly cron — which
// has no $_GET at all — always produced the reduced version and quietly replaced the
// full one. On by default now; pass gens=0 (or --no-gens) to skip them.
$fetchGenerations = true;
if (isset($_GET['gens']) && $_GET['gens'] === '0') $fetchGenerations = false;
if (php_sapi_name() === 'cli' && in_array('--no-gens', $argv ?? [], true)) $fetchGenerations = false;

foreach (['Y' => 'foreign', 'N' => 'native'] as $carType => $bucket) {
    echo "Fetching brands for CarType.{$carType}...\n";
    $url = 'https://api.encar.com/search/car/list/general'
        . '?count=true'
        . '&q=' . rawurlencode("(And.Hidden.N._.CarType.{$carType}.)")
        . '&inav=' . rawurlencode('|Metadata|Sort');

    $data = encar_call($url);
    if (!$data) {
        echo "  FAIL — skipping.\n";
        continue;
    }

    if ($carType === 'N') {
        file_put_contents($debugFile, json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
        echo "  >> Dumped first response to {$debugFile}\n";
        echo "  >> Top-level keys: " . implode(', ', array_keys($data)) . "\n";
        if (isset($data['iNav'])) {
            echo "  >> iNav keys: " . implode(', ', array_keys($data['iNav'])) . "\n";
        }
    }

    $brands = extract_facet_options($data, 'Manufacturer');
    echo "  Found " . count($brands) . " brands\n";

    foreach ($brands as $brand) {
        $brandKey = $brand['value'];
        if (!isset($taxonomy['brands'][$brandKey])) {
            $taxonomy['brands'][$brandKey] = [
                'name_kr' => $brand['label'],
                'eng_name' => $brand['eng_name'],
                'car_types' => [],
                'models' => [],
            ];
        }
        $taxonomy['brands'][$brandKey]['car_types'][] = $carType;
        $taxonomy['brands'][$brandKey]['count_' . $bucket] = $brand['count'];
    }

    // Step 2: for each brand, fetch its model list.
    foreach ($brands as $brand) {
        $brandKey = $brand['value'];
        // Collected in an earlier pass → skip, so resuming never redoes work.
        if (!empty($taxonomy['brands'][$brandKey]['_done'])) continue;
        if (microtime(true) >= $deadline) { $timedOut = true; break; }

        $url = 'https://api.encar.com/search/car/list/general'
            . '?count=true'
            . '&q=' . rawurlencode("(And.Hidden.N._.(C.CarType.{$carType}._.Manufacturer.{$brandKey}.))")
            . '&inav=' . rawurlencode('|Metadata|Sort');

        echo "  → models for {$brandKey} ({$brand['label']})...\n";
        $data = encar_call($url);
        if (!$data) {
            echo "    FAIL\n";
            continue;
        }
        $models = extract_facet_options($data, 'ModelGroup');
        echo "    " . count($models) . " models\n";

        foreach ($models as $model) {
            $modelKey = $model['value'];
            $taxonomy['brands'][$brandKey]['models'][$modelKey] = [
                'name_kr' => $model['label'],
                'eng_name' => $model['eng_name'],
                'car_type' => $carType,
                'count' => $model['count'],
                'generations' => [],
            ];

            if ($fetchGenerations) {
                $genUrl = 'https://api.encar.com/search/car/list/general'
                    . '?count=true'
                    . '&q=' . rawurlencode("(And.Hidden.N._.(C.CarType.{$carType}._.(C.Manufacturer.{$brandKey}._.ModelGroup.{$modelKey}.)))")
                    . '&inav=' . rawurlencode('|Metadata|Sort');

                $genData = encar_call($genUrl);
                if ($genData) {
                    $generations = extract_facet_options($genData, 'Model');
                    foreach ($generations as $gen) {
                        $genKey = $gen['value'];
                        $taxonomy['brands'][$brandKey]['models'][$modelKey]['generations'][$genKey] = [
                            'name_kr' => $gen['label'],
                            'eng_name' => $gen['eng_name'],
                            'count' => $gen['count'],
                            'min_year' => $gen['min_year'],
                            'max_year' => $gen['max_year'],
                        ];
                    }
                    echo "    {$modelKey}: " . count($generations) . " generations\n";
                }
                usleep(300000);
            }
        }
        usleep(300000); // 0.3s polite delay

        // Brand finished — remember it so a resume walks straight past.
        $taxonomy['brands'][$brandKey]['_done'] = true;

        // Progress goes to a .part file, never over the live taxonomy: a run that dies
        // after two brands must not replace a complete file with those two.
        ksort($taxonomy['brands']);
        file_put_contents($outFile . '.part', json_encode($taxonomy, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
    }
}

ksort($taxonomy['brands']);

// Out of time with brands still to do: save the progress and come back for the rest.
// Nothing is written over the live taxonomy until the tree is complete.
if ($timedOut) {
    file_put_contents($partFile, json_encode($taxonomy, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
    $done = count(array_filter($taxonomy['brands'], fn($b) => !empty($b['_done'])));
    $total = count($taxonomy['brands']);
    echo "\nPaused after {$done}/{$total} brand(s) — progress saved, the live file is untouched.\n";

    if (php_sapi_name() !== 'cli') {
        $next = '?token=' . rawurlencode($EXPECTED_TOKEN) . '&seconds=' . $budget
              . (($_GET['gens'] ?? '') !== '' ? '&gens=' . rawurlencode((string)$_GET['gens']) : '');
        echo "Continuing…\n</pre>";
        echo '<meta http-equiv="refresh" content="2;url=' . htmlspecialchars($next) . '">';
    } else {
        echo "Run the script again to continue where it stopped.\n";
    }
    exit(0);
}

// Finished: the working flags do not belong in the published file.
foreach ($taxonomy['brands'] as $k => $b) unset($taxonomy['brands'][$k]['_done']);

// An empty taxonomy is never useful: it blanks the Encar filter panel and makes every
// import keep the raw Korean brand name, which then fails to publish with "Brand/model
// not in sauto catalog". Encar answered 407 twice today and this script cheerfully
// saved "0 brands" over the real file — so it now refuses to write one at all.
$existing = is_file($outFile) ? json_decode((string)file_get_contents($outFile), true) : null;
$had = is_array($existing) ? count($existing['brands'] ?? []) : 0;
$got = count($taxonomy['brands']);

if ($got === 0) {
    echo "\nFetched 0 brands (Encar refused the request). Nothing was written"
         . ($had > 0 ? " — the existing file with {$had} brand(s) is untouched.\n" : ".\n");
    echo "Restore with: git checkout HEAD -- App/Services/Parsing/Adapters/encar_taxonomy.json\n";
    exit(1);
}
// A run that dies halfway would otherwise replace a full file with its few brands.
if ($had > $got * 2 && (($_GET['force'] ?? '') !== '1')) {
    echo "\nFetched only {$got} brand(s) but the file holds {$had} — that looks like a broken run.\n";
    echo "Nothing was written. Append &force=1 (or delete the file) to overwrite anyway.\n";
    exit(1);
}

file_put_contents($outFile, json_encode($taxonomy, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
@unlink($partFile);   // the tree is complete; a stale .part would resume a finished run

echo "\nDone. {$got} brands saved to:\n";
echo $outFile . "\n";
