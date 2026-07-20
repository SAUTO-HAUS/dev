<?php
// Build App/Services/Parsing/Adapters/auto1_taxonomy.json — the
// make/model/engine list the /parsing filter UI uses for Auto1.
//
// Auto1 hands us the whole taxonomy in ONE call: /v1/car-search/filters/{uuid}
// returns carFilters[] = [{value:<manufacturerCode>, label:"a1-manufacturer-NNN",
// mainTypes:[{value,label}]}]. Makes are numeric codes; the human name lives in
// the app's translation bundle as a1-manufacturer-NNN. We fetch that bundle once
// and resolve every code → real name (BMW, Audi, ...). Models already carry their
// real label. Engines need a SECOND call (see step 3) because /filters returns
// every subTypes[] empty. Unlike OpenLane/eCarsTrade neither call loops per make,
// so this is still a fast run.
//
// CRON (weekly, after eCarsTrade 05:00):
//   0 6 * * 1 cd /home/sautom/public_html && /usr/local/bin/php console/auto1_taxonomy_dump.php >> .../logs/auto1_taxonomy.log 2>&1
// Web:  /console/auto1_taxonomy_dump.php?token=cron2026
// Depends on AUTO1_COOKIE in .env (if expired, the old taxonomy stays).

$IS_CLI = (php_sapi_name() === 'cli');
if (!$IS_CLI && ($_GET['token'] ?? '') !== 'cron2026') {
    http_response_code(403); die('Forbidden');
}
if (!$IS_CLI) header('Content-Type: text/plain; charset=utf-8');
ignore_user_abort(true);
set_time_limit(0);
error_reporting(E_ALL); ini_set('display_errors', 1);
date_default_timezone_set('Europe/Chisinau');

chdir(__DIR__);
define('_DOIT', 1);
define('_DEFAULT', 'content/default');
spl_autoload_register(function ($c) {
    $p = __DIR__ . '/../' . str_replace('\\', DIRECTORY_SEPARATOR, $c) . '.php';
    if (file_exists($p)) require_once $p;
});
require_once __DIR__ . '/../environment.php';
require ('../' . _DEFAULT . '/dbi.php');
\App\Core\Container::set('db', $db);
\App\Core\Container::set('prefix', 'gh3sp');
$_SERVER['DOCUMENT_ROOT'] = realpath(__DIR__ . '/..');

use App\Services\Parsing\Adapters\Auto1Adapter;

$adapter = new Auto1Adapter();

// 1) Fetch the filters/taxonomy (makes + models).
echo "[" . date('H:i:s') . "] Fetching Auto1 filters...\n";
$filters = $adapter->fetchFilters();
if (!is_array($filters) || empty($filters['filters']['carFilters'])) {
    echo "FAILED — no carFilters (cookie expired?). Aborting.\n";
    exit(1);
}
$carFilters = $filters['filters']['carFilters'];
echo "  " . count($carFilters) . " makes.\n";

// 2) Resolve manufacturer code → real name from the English translation bundle.
$bundleEn = fetchBundle('en');
$names = extractKeys($bundleEn, 'a1-manufacturer-');
echo "  " . count($names) . " manufacturer names resolved.\n";

// 3) Fetch the engine tree. /filters returns every mainType with an EMPTY
// subTypes[], so engines can only come from the search "compositeCarKey"
// aggregation. One call covers all makes. If it fails we still write the
// taxonomy — makes/models keep working, engine selects just stay empty.
echo "[" . date('H:i:s') . "] Fetching engine tree (compositeCarKey)...\n";
$tree = $adapter->fetchCompositeTree();
if ($tree === null) {
    echo "  WARN: no engine tree — engines will be missing from the filter UI\n";
    $tree = [];
} else {
    $nEng = 0;
    foreach ($tree as $mk) foreach (($mk['i'] ?? []) as $mo) $nEng += count($mo['i'] ?? []);
    echo "  " . count($tree) . " makes / $nEng engines in stock.\n";
}

// 4) Build the taxonomy: { code => { name, models:[{value,label,engines[]}] } }.
// Engines carry Auto1's own value ("1.5 TDCi") — it is sent back verbatim as a
// subTypes[] string. Only models/engines that currently have cars get engines;
// the rest keep an empty list rather than disappearing.
$taxonomy = ['generated_at' => date('Y-m-d H:i:s'), 'makes' => []];
foreach ($carFilters as $mk) {
    $code = (string)($mk['value'] ?? '');
    if ($code === '') continue;
    $name = $names[$code] ?? preg_replace('/^a1-manufacturer-/', '', (string)($mk['label'] ?? $code));
    $modelTree = $tree[$code]['i'] ?? [];
    $models = [];
    foreach (($mk['mainTypes'] ?? []) as $m) {
        $mv = (string)($m['value'] ?? '');
        if ($mv === '') continue;
        // Engine list for this model, most-common first so the useful ones are
        // at the top of the dropdown.
        $engines = $modelTree[$mv]['i'] ?? [];
        arsort($engines);
        $models[] = [
            'value'   => $mv,
            'label'   => (string)($m['label'] ?? $mv),
            'engines' => array_map(
                fn($e, $c) => ['value' => (string)$e, 'count' => (int)$c],
                array_keys($engines),
                array_values($engines)
            ),
        ];
    }
    $taxonomy['makes'][$code] = ['name' => $name, 'models' => $models];
}

// 5) Write the taxonomy.
$out = __DIR__ . '/../App/Services/Parsing/Adapters/auto1_taxonomy.json';
file_put_contents($out, json_encode($taxonomy, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT));
echo "[" . date('H:i:s') . "] Wrote " . count($taxonomy['makes']) . " makes to auto1_taxonomy.json\n\n";

// 6) Build the damage/equipment dictionary the report renders from.
// Auto1's API returns damages as stable translation KEYS ("global.damages.dent"),
// and Auto1 publishes its own RO and RU bundles — so the report needs no
// hand-written dictionary and no AI: we lift their official wording. Groups kept:
//   global.damages.*          — damaged part / type / severity / section (845)
//   global.equipment_groups.* — equipment group names (151)
//   global.car_details.*      — accident + body/fuel/gear labels (257)
echo "[" . date('H:i:s') . "] Building RO/RU/EN damage dictionary...\n";
$PREFIXES = ['global.damages.', 'global.equipment_groups.', 'global.car_details.'];
$dict = ['generated_at' => date('Y-m-d H:i:s'), 'keys' => []];
$bundles = ['en' => $bundleEn];
foreach (['ro', 'ru'] as $lang) {
    $bundles[$lang] = fetchBundle($lang);
    if ($bundles[$lang] === '') echo "  WARN: bundle '$lang' empty — that language falls back to EN\n";
}
foreach ($bundles as $lang => $body) {
    if ($body === '') continue;
    $n = 0;
    foreach ($PREFIXES as $pfx) {
        foreach (extractKeys($body, $pfx) as $short => $val) {
            if ($val === '') continue;
            $dict['keys'][$pfx . $short][$lang] = $val;
            $n++;
        }
    }
    echo "  $lang: $n entries\n";
}
$dictOut = __DIR__ . '/../App/Services/Parsing/Adapters/auto1_damage_dict.json';
file_put_contents($dictOut, json_encode($dict, JSON_UNESCAPED_UNICODE));
echo "[" . date('H:i:s') . "] Wrote " . count($dict['keys']) . " keys to auto1_damage_dict.json\n";

// Download one of Auto1's public translation bundles ("" on failure).
function fetchBundle(string $lang): string
{
    $ch = curl_init();
    curl_setopt_array($ch, [
        CURLOPT_URL => 'https://static.auto1.com/@auto1/translations/translations-messages.' . $lang . '.js',
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_TIMEOUT => 60,
        CURLOPT_FOLLOWLOCATION => true,
        CURLOPT_SSL_VERIFYPEER => false,
        CURLOPT_HTTPHEADER => [
            'User-Agent: Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/150.0.0.0 Safari/537.36',
            'Referer: https://www.auto1.com/',
        ],
    ]);
    $body = curl_exec($ch);
    curl_close($ch);
    return is_string($body) ? $body : '';
}

// Pull every "<prefix><short>", "<value>" pair out of a bundle.
// Bundle format: t("global.damages.dent", "Dent", "messages", "en")
function extractKeys(string $body, string $prefix): array
{
    if ($body === '') return [];
    $out = [];
    $rx = '/"' . preg_quote($prefix, '/') . '([^"]+)",\s*"([^"]*)"/';
    if (preg_match_all($rx, $body, $m, PREG_SET_ORDER)) {
        foreach ($m as $row) $out[$row[1]] = $row[2];
    }
    return $out;
}
