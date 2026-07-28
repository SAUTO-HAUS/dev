<?php

error_reporting(E_ALL);
ini_set('display_errors', 1);
date_default_timezone_set('Europe/Chisinau');

chdir(__DIR__);
define('_DOIT', 1);
define('_DEFAULT', 'content/default');

spl_autoload_register(function ($class) {
    $baseDir = __DIR__ . '/../';
    $classPath = $baseDir . str_replace('\\', DIRECTORY_SEPARATOR, $class) . '.php';
    if (file_exists($classPath)) {
        require_once $classPath;
    }
});

require_once __DIR__ . '/../environment.php';
require ('../' . _DEFAULT . '/dbi.php');
require_once ('../' . _DEFAULT . '/functions.php');
require_once __DIR__ . '/parsing_trims_helper.php';   // translate_korean_trims()

use App\Core\Container;
use App\Services\Parsing\ParsingOrchestrator;

$_SERVER['DOCUMENT_ROOT'] = realpath(__DIR__ . '/..');

Container::set('db', $db);
Container::set('prefix', 'gh3sp');

$lockFile = __DIR__ . '/../logs/parsing_enrich.lock';
$lockHandle = fopen($lockFile, 'c');
if ($lockHandle === false || !flock($lockHandle, LOCK_EX | LOCK_NB)) {
    echo "[" . date('Y-m-d H:i:s') . "] Another enrich run is active — skipping.\n";
    exit;
}

try {
    $orchestrator = new ParsingOrchestrator();
    $enriched = $orchestrator->enrichRecent(300, 120);
    echo "[" . date('Y-m-d H:i:s') . "] Enriched {$enriched} cars\n";

    $warmed = warm_openlane_covers($db, 'gh3sp', 200, 60);
    echo "[" . date('Y-m-d H:i:s') . "] Warmed {$warmed} OpenLane covers\n";

    $tr = translate_korean_trims($db, 'gh3sp');
    echo "[" . date('Y-m-d H:i:s') . "] Trims translated: {$tr}\n";
} catch (\Throwable $e) {
    echo "[" . date('Y-m-d H:i:s') . "] Enrich error: " . $e->getMessage() . "\n";
} finally {
    flock($lockHandle, LOCK_UN);
    fclose($lockHandle);
}

function warm_openlane_covers(PDO $db, string $prefx, int $limit, int $timeLimitSec): int
{
    $cacheDir = $_SERVER['DOCUMENT_ROOT'] . '/tmp/parsing_imgcache';
    if (!is_dir($cacheDir)) @mkdir($cacheDir, 0755, true);

    $stmt = $db->prepare("SELECT images_local FROM {$prefx}_parsing_cars
        WHERE source = 'openlane' AND status IN ('proposed','favorite')
          AND images_local IS NOT NULL AND images_local <> '[]'
        ORDER BY found_at DESC LIMIT ?");
    $stmt->bindValue(1, $limit, PDO::PARAM_INT);
    $stmt->execute();
    $rows = $stmt->fetchAll(PDO::FETCH_COLUMN);

    $start = time();
    $done = 0;
    foreach ($rows as $json) {
        if (time() - $start >= $timeLimitSec) break;
        $imgs = json_decode($json, true);
        if (!is_array($imgs) || empty($imgs)) continue;
        $first = $imgs[0];
        $url = is_array($first) ? ($first['url'] ?? '') : (string)$first;
        if ($url === '' || stripos($url, 'images.openlane.eu') === false) continue;

        $cacheFile = $cacheDir . '/' . sha1($url) . '_600.jpg';
        if (is_file($cacheFile)) continue; // already warm

        $ch = curl_init($url);
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_TIMEOUT        => 20,
            CURLOPT_SSL_VERIFYPEER => false,
            CURLOPT_ENCODING       => '',
            CURLOPT_HTTPHEADER     => [
                'User-Agent: Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/120.0.0.0 Safari/537.36',
                'Accept: image/*',
                'Referer: https://www.openlane.eu/',
            ],
        ]);
        $bytes = curl_exec($ch);
        $code  = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        if ($bytes === false || $code !== 200 || strlen($bytes) < 500) continue;

        if (function_exists('imagecreatefromstring')) {
            $src = @imagecreatefromstring($bytes);
            if ($src !== false) {
                $w = imagesx($src); $h = imagesy($src);
                if ($w > 600) {
                    $nw = 600; $nh = (int)round($h * (600 / $w));
                    $dst = imagecreatetruecolor($nw, $nh);
                    imagecopyresampled($dst, $src, 0, 0, 0, 0, $nw, $nh, $w, $h);
                    ob_start(); imagejpeg($dst, null, 82); $small = ob_get_clean();
                    imagedestroy($dst);
                    if ($small && strlen($small) >= 500) $bytes = $small;
                }
                imagedestroy($src);
            }
        }
        @file_put_contents($cacheFile, $bytes, LOCK_EX);
        $done++;
    }
    return $done;
}
