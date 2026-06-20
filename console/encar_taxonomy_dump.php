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
    header('Content-Type: text/plain; charset=utf-8');
    set_time_limit(600); // long script
}

chdir(__DIR__);

$outFile = realpath(__DIR__ . '/..') . '/App/Services/Parsing/Adapters/encar_taxonomy.json';

function encar_call(string $url): ?array
{
    $ch = curl_init($url);
    curl_setopt_array($ch, [
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
    ]);
    $body = curl_exec($ch);
    $status = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    echo "  [{$status}] {$url}\n";
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

// DEBUG: dump first response raw so we can see iNav structure.
$debugFile = realpath(__DIR__ . '/..') . '/logs/encar_inav_debug.json';
@mkdir(dirname($debugFile), 0755, true);

$fetchGenerations = isset($_GET['gens']) && $_GET['gens'] === '1';

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
        // Some brands are LOT — keep loop light, but we want completeness.
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

        // Persist progress after each brand so a partial run still leaves usable data.
        ksort($taxonomy['brands']);
        file_put_contents($outFile, json_encode($taxonomy, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
    }
}

ksort($taxonomy['brands']);
file_put_contents($outFile, json_encode($taxonomy, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));

echo "\nDone. " . count($taxonomy['brands']) . " brands saved to:\n";
echo $outFile . "\n";
