<?php defined( '_DOIT' ) or die( 'Restricted access' );

// Image proxy returns binary; everything else returns JSON.
$actionEarly = $_GET['action'] ?? $_POST['action'] ?? '';
if ($actionEarly !== 'image_proxy') {
    header('Content-Type: application/json; charset=utf-8');
}

$response = ['success' => false, 'error' => null];

// Parsing access — ids configured in include/parsing_access.php.
require_once(_ADM_INCL.'/parsing_access.php');
if (!parsing_has_access($user_id ?? 0)) {
    $response['error'] = 'Access denied';
    echo json_encode($response);
    return;
}

$action = $_POST['action'] ?? $_GET['action'] ?? '';

// Settings-only actions: full-access ids only.
$settingsOnlyActions = ['save_settings', 'save_eu_config', 'openlane_save_cookie', 'ecarstrade_save_cookie'];
if (in_array($action, $settingsOnlyActions, true) && !parsing_is_full($user_id ?? 0)) {
    $response['error'] = 'Access denied';
    echo json_encode($response);
    return;
}

// Clear catalog: allowed for any parsing user (full OR limited), but still gated —
// not for users with no parsing access at all.
if ($action === 'clear_catalog' && !parsing_has_access($user_id ?? 0)) {
    $response['error'] = 'Access denied';
    echo json_encode($response);
    return;
}

if (parsing_is_encar_only($user_id ?? 0)) {
    $_POST['sources'] = ['encar'];
    $_POST['source']  = 'encar';

    if ($action === 'fetch_by_link') {
        $response['error'] = 'Access denied';
        echo json_encode($response);
        return;
    }
}

try {
    switch ($action) {
        case 'save_filter':
            $response = parsing_save_filter($db, $prefx, $user_id ?? 0, $_POST);
            break;
        case 'get_filter':
            $response = parsing_get_filter($db, $prefx, $user_id ?? 0, $_POST);
            break;
        case 'delete_filter':
            $response = parsing_delete_filter($db, $prefx, $user_id ?? 0, $_POST);
            break;
        case 'toggle_filter':
            $response = parsing_toggle_filter($db, $prefx, $user_id ?? 0, $_POST);
            break;
        case 'run_filter':
            $response = parsing_run_filter($db, $prefx, $user_id ?? 0, $_POST);
            break;
        case 'publish_car':
            $response = parsing_publish_car($db, $prefx, $_POST);
            break;
        case 'publish_enqueue':
            $response = parsing_publish_enqueue($db, $prefx, $_POST);
            break;
        case 'publish_queue_status':
            $response = parsing_publish_queue_status($db, $prefx, $_POST);
            break;
        case 'publish_queue_failed':
            $response = parsing_publish_queue_failed($db, $prefx, $_POST);
            break;
        case 'publish_queue_retry':
            $response = parsing_publish_queue_retry($db, $prefx, $_POST);
            break;
        case 'publish_queue_dismiss':
            $response = parsing_publish_queue_dismiss($db, $prefx, $_POST);
            break;
        case 'publish_999_stats':
            $response = parsing_publish_999_stats($db, $prefx);
            break;
        case 'reject_car':
            $response = parsing_reject_car($db, $prefx, $_POST);
            break;
        case 'favorite_car':
            $response = parsing_favorite_car($db, $prefx, $_POST);
            break;
        case 'unfavorite_car':
            $response = parsing_unfavorite_car($db, $prefx, $_POST);
            break;
        case 'get_car_details':
            $response = parsing_get_car_details($db, $prefx, $_POST);
            break;
        case 'enrich_one_md':
            $response = parsing_enrich_one_md($db, $prefx, $_POST);
            break;
        case 'save_car_edits':
            $response = parsing_save_car_edits($db, $prefx, $_POST);
            break;
        case 'ai_enrich_specs':
            $response = parsing_ai_enrich_specs($db, $prefx, $_POST);
            break;
        case 'image_proxy':
            parsing_image_proxy($_GET['url'] ?? '');
            exit;
        case 'remove_published':
            $response = parsing_remove_published($db, $prefx, $_POST);
            break;
        case 'fetch_by_link':
            $response = parsing_fetch_by_link($db, $prefx, $_POST);
            break;
        case 'search_now':
            $response = parsing_search_now($db, $prefx, $_POST);
            break;
        case 'openlane_facets':
            $response = parsing_openlane_facets($db, $prefx, $_POST);
            break;
        case 'openlane_report':
            $response = parsing_openlane_report($db, $prefx, $_POST);
            break;
        case 'ecarstrade_report':
            $response = parsing_ecarstrade_report($db, $prefx, $_POST);
            break;
        case 'openlane_save_cookie':
            $response = parsing_openlane_save_cookie($db, $prefx, $_POST);
            break;
        case 'openlane_check_cookie':
            $response = parsing_openlane_check_cookie($db, $prefx, $_POST);
            break;
        case 'ecarstrade_save_cookie':
            $response = parsing_ecarstrade_save_cookie($db, $prefx, $_POST);
            break;
        case 'ecarstrade_check_cookie':
            $response = parsing_ecarstrade_check_cookie($db, $prefx, $_POST);
            break;
        case 'redownload_images':
            $response = parsing_redownload_images($db, $prefx, $_POST);
            break;
        case 'fetch_all_photos':
            $response = parsing_fetch_all_photos($db, $prefx, $_POST);
            break;
        case 'save_settings':
            $response = parsing_save_settings($db, $prefx, $user_id ?? 0, $_POST);
            break;
        case 'save_eu_config':
            $response = parsing_save_eu_config($db, $prefx, $user_id ?? 0, $_POST);
            break;
        case 'test_report':
            $response = parsing_test_report($db, $prefx, $_POST);
            break;
        case 'clear_catalog':
            $response = parsing_clear_catalog($db, $prefx, $_POST);
            break;
        case 'match_model':
            $response = parsing_match_model($db, $prefx, $_POST);
            break;
        case 'translate_trims':
            $response = parsing_translate_trims($db, $prefx);
            break;
        default:
            $response['error'] = 'Unknown action: ' . $action;
    }
} catch (Exception $e) {
    $response = ['success' => false, 'error' => $e->getMessage()];
}

echo json_encode($response);
exit;

// ---------------------------------------------------------------
// HANDLERS
// ---------------------------------------------------------------

function parsing_save_filter($db, $prefx, $userId, $p) {
    $id = (int)($p['id'] ?? 0);
    $name = trim($p['name'] ?? '');
    $sources = is_array($p['sources'] ?? null) ? implode(',', $p['sources']) : '';

    if ($name === '' || $sources === '') {
        return ['success' => false, 'error' => 'Nume si surse obligatorii'];
    }

    $extra = [];
    if (!empty($p['body_type']))      $extra['body_type'] = $p['body_type'];
    if (!empty($p['seats']))          $extra['seats'] = (int)$p['seats'];
    if (!empty($p['engine_volume']))  $extra['engine_volume'] = (int)$p['engine_volume'];
    if (!empty($p['country_origin'])) $extra['country_origin'] = $p['country_origin'];
    if (!empty($p['car_sub_model']))  $extra['car_sub_model'] = trim($p['car_sub_model']);
    if (!empty($p['category']))       $extra['category'] = $p['category'];
    if (!empty($p['generation']))     $extra['generation'] = trim($p['generation']);
    if (!empty($p['km_min']))         $extra['km_min'] = (int)$p['km_min'];
    if (!empty($p['price_min']))      $extra['price_min'] = (int)$p['price_min'];

    $data = [
        'user_id' => $userId,
        'name' => $name,
        'sources' => $sources,
        'brand' => $p['brand'] ?? null,
        'model' => $p['model'] ?? null,
        'year_from' => !empty($p['year_from']) ? (int)$p['year_from'] : null,
        'year_to' => !empty($p['year_to']) ? (int)$p['year_to'] : null,
        'km_max' => !empty($p['km_max']) ? (int)$p['km_max'] : null,
        'price_max' => !empty($p['price_max']) ? (int)$p['price_max'] : null,
        'fuel_type' => $p['fuel_type'] ?? null,
        'gearbox' => $p['gearbox'] ?? null,
        'drive_type' => $p['drive_type'] ?? null,
        'criteria_extra' => $extra ? json_encode($extra, JSON_UNESCAPED_UNICODE) : null,
    ];

    if ($id > 0) {
        $sets = [];
        foreach ($data as $k => $v) $sets[] = "`$k` = :$k";
        $sql = 'UPDATE '.$prefx.'_parsing_filters SET '.implode(', ', $sets).' WHERE id = :id AND user_id = :uid';
        $data['id'] = $id;
        $data['uid'] = $userId;
        $stmt = $db->prepare($sql);
        $stmt->execute($data);
    } else {
        $data['active'] = 1;
        $cols = array_keys($data);
        $placeholders = array_map(fn($c) => ':' . $c, $cols);
        $sql = 'INSERT INTO '.$prefx.'_parsing_filters (`'.implode('`,`', $cols).'`) VALUES ('.implode(',', $placeholders).')';
        $stmt = $db->prepare($sql);
        $stmt->execute($data);
        $id = (int)$db->lastInsertId();
    }

    return ['success' => true, 'id' => $id];
}

function parsing_get_filter($db, $prefx, $userId, $p) {
    $id = (int)($p['id'] ?? 0);
    $stmt = $db->prepare('SELECT * FROM '.$prefx.'_parsing_filters WHERE id = ? AND user_id = ?');
    $stmt->execute([$id, $userId]);
    $row = $stmt->fetch(PDO::FETCH_ASSOC);
    if (!$row) return ['success' => false, 'error' => 'Negasit'];

    // Merge criteria_extra fields into the top-level filter object so the JS
    // side can set them directly without knowing about the JSON column.
    if (!empty($row['criteria_extra'])) {
        $extra = json_decode($row['criteria_extra'], true);
        if (is_array($extra)) {
            foreach ($extra as $k => $v) {
                if (!isset($row[$k])) $row[$k] = $v;
            }
        }
    }

    return ['success' => true, 'filter' => $row];
}

function parsing_delete_filter($db, $prefx, $userId, $p) {
    $id = (int)($p['id'] ?? 0);
    $stmt = $db->prepare('DELETE FROM '.$prefx.'_parsing_filters WHERE id = ? AND user_id = ?');
    $stmt->execute([$id, $userId]);
    return ['success' => true];
}

function parsing_toggle_filter($db, $prefx, $userId, $p) {
    $id = (int)($p['id'] ?? 0);
    $stmt = $db->prepare('UPDATE '.$prefx.'_parsing_filters SET active = 1 - active WHERE id = ? AND user_id = ?');
    $stmt->execute([$id, $userId]);
    return ['success' => true];
}

function parsing_run_filter($db, $prefx, $userId, $p) {
    $id = (int)($p['id'] ?? 0);
    if ($id <= 0) return ['success' => false, 'error' => 'Invalid filter ID'];

    if (parsing_is_encar_only($userId)) {
        $chk = $db->prepare('SELECT sources FROM '.$prefx.'_parsing_filters WHERE id = ? AND user_id = ? LIMIT 1');
        $chk->execute([$id, $userId]);
        $row = $chk->fetch(PDO::FETCH_ASSOC);
        $srcs = $row ? array_filter(array_map('trim', explode(',', (string)$row['sources']))) : [];
        if (!$row || array_diff($srcs, ['encar'])) {
            return ['success' => false, 'error' => 'Access denied'];
        }
    }

    $orchestrator = new \App\Services\Parsing\ParsingOrchestrator();
    $summary = $orchestrator->runFilter($id, true, 'manual');

    if (isset($summary['error'])) {
        return ['success' => false, 'error' => $summary['error']];
    }

    $totalFound = 0;
    $totalImported = 0;
    $totalDuplicates = 0;
    $totalCount = 0;
    $catalogOffset = 0;
    foreach ($summary as $stats) {
        $totalFound += $stats['found'] ?? 0;
        $totalImported += $stats['imported'] ?? 0;
        $totalDuplicates += $stats['duplicates'] ?? 0;
        $totalCount = max($totalCount, (int)($stats['total_count'] ?? 0));
        $catalogOffset = max($catalogOffset, (int)($stats['catalog_offset'] ?? 0));
    }

    // Detail fields (gearbox, color, VIN, seats, drive type, full gallery) aren't
    // in Encar's search list — only in the per-car detail endpoint. Doing 100
    // detail requests inline would hang this AJAX call, so we launch the enrich
    // worker DETACHED in the background. The import responds instantly; the rows
    // get completed a few seconds later (the user just refreshes the list).
    if ($totalImported > 0) {
        parsing_spawn_enrich_worker();
    }

    return [
        'success'    => true,
        'found'      => $totalFound,
        'imported'   => $totalImported,
        'duplicates' => $totalDuplicates,
        // Progress in the source catalog: how far we've scanned vs the total.
        'total_count'    => $totalCount,
        'catalog_offset' => min($catalogOffset, $totalCount ?: $catalogOffset),
    ];
}

// Launch console/parsing_enrich.php as a detached background process so it keeps
// running after this request returns. Best-effort: failures are swallowed (the
// cron still enriches as a fallback).
function parsing_spawn_enrich_worker() {
    $script = realpath(__DIR__ . '/../../../../console/parsing_enrich.php');
    if (!$script) return;
    $php = PHP_BINARY ?: 'php';
    try {
        if (stripos(PHP_OS, 'WIN') === 0) {
            // Windows: start /B detaches; wrap paths in quotes for spaces.
            $cmd = 'start /B "" "' . $php . '" "' . $script . '"';
            pclose(popen('cmd /C ' . $cmd, 'r'));
        } else {
            // POSIX: background + disown via nohup, redirect output away.
            exec('nohup "' . $php . '" "' . $script . '" > /dev/null 2>&1 &');
        }
    } catch (\Throwable $e) { /* best-effort — cron will pick it up */ }
}

function parsing_publish_car($db, $prefx, $p) {
    $carId = (int)($p['car_id'] ?? 0);
    $target = $p['target'] ?? 'sauto';
    if (!in_array($target, ['sauto', '999', 'all', 'telegram'], true)) {
        return ['success' => false, 'error' => 'Invalid target: ' . $target];
    }
    $publisher = new \App\Services\Parsing\ParsingPublisher();
    return $publisher->publish($carId, $target);
}

// Add a car to the server-side publish queue (sauto only) and kick the worker
// fire-and-forget. Returns immediately; the worker publishes server-side so the
// operator can leave the page. A per-minute cron is the safety net.
function parsing_publish_enqueue($db, $prefx, $p) {
    $carId = (int)($p['car_id'] ?? 0);
    if ($carId <= 0) return ['success' => false, 'error' => 'Missing car_id'];
    // Only sauto goes server-side. 999/FB/Telegram publish via the browser form.
    if (($p['target'] ?? 'sauto') !== 'sauto') {
        return ['success' => false, 'error' => 'Only sauto is queued server-side'];
    }
    try {
        // Persist the sauto brand/model the browser resolved (BMW 318 → Seria 3 via
        // match_model) so the worker publishes into the right catalog model. Stored
        // in parsing_cars.sauto_br/sauto_mo (columns auto-created if missing).
        $sautoBr = trim((string)($p['sauto_br'] ?? ''));
        $sautoMo = trim((string)($p['sauto_mo'] ?? ''));
        if ($sautoBr !== '' || $sautoMo !== '') {
            parsing_ensure_sauto_match_columns($db, $prefx);
            $db->prepare('UPDATE '.$prefx.'_parsing_cars SET sauto_br = ?, sauto_mo = ? WHERE id = ?')
               ->execute([$sautoBr ?: null, $sautoMo ?: null, $carId]);
        }

        // Remember WHO published, so the catalog shows the real operator name
        // instead of "Parser". The worker has no session, so we store it now.
        $publishedBy = trim((string)($_SESSION['user_name'] ?? ''));
        if ($publishedBy !== '') {
            parsing_ensure_published_by_column($db, $prefx);
            $db->prepare('UPDATE '.$prefx.'_parsing_cars SET published_by = ? WHERE id = ?')
               ->execute([$publishedBy, $carId]);
        }

        $queue = new \App\Services\Parsing\PublishQueue();
        $queue->enqueue($carId, 'sauto');
        parsing_kick_publish_worker();
        return ['success' => true, 'queue' => $queue->counts()];
    } catch (\Throwable $e) {
        return ['success' => false, 'error' => $e->getMessage()];
    }
}

// Add sauto_br/sauto_mo to parsing_cars on first use (no migration runner).
function parsing_ensure_sauto_match_columns($db, $prefx) {
    static $done = false;
    if ($done) return;
    $done = true;
    try {
        $cols = $db->query("SHOW COLUMNS FROM {$prefx}_parsing_cars LIKE 'sauto_br'");
        if ($cols && $cols->rowCount() === 0) {
            $db->exec("ALTER TABLE {$prefx}_parsing_cars
                ADD COLUMN `sauto_br` VARCHAR(50) DEFAULT NULL,
                ADD COLUMN `sauto_mo` VARCHAR(50) DEFAULT NULL");
        }
    } catch (\Throwable $e) { /* best-effort */ }
}

// Add published_by to parsing_cars on first use (the operator who published).
function parsing_ensure_published_by_column($db, $prefx) {
    static $done = false;
    if ($done) return;
    $done = true;
    try {
        $cols = $db->query("SHOW COLUMNS FROM {$prefx}_parsing_cars LIKE 'published_by'");
        if ($cols && $cols->rowCount() === 0) {
            $db->exec("ALTER TABLE {$prefx}_parsing_cars
                ADD COLUMN `published_by` VARCHAR(100) DEFAULT NULL");
        }
    } catch (\Throwable $e) { /* best-effort */ }
}

function parsing_ensure_ai_verified_column($db, $prefx) {
    static $done = false;
    if ($done) return;
    $done = true;
    try {
        $cols = $db->query("SHOW COLUMNS FROM {$prefx}_parsing_cars LIKE 'ai_verified'");
        if ($cols && $cols->rowCount() === 0) {
            $db->exec("ALTER TABLE {$prefx}_parsing_cars
                ADD COLUMN `ai_verified` TINYINT(1) NOT NULL DEFAULT 0");
        }
    } catch (\Throwable $e) { /* best-effort */ }
}

function parsing_publish_queue_status($db, $prefx, $p) {
    try {
        return ['success' => true, 'queue' => (new \App\Services\Parsing\PublishQueue())->counts()];
    } catch (\Throwable $e) {
        return ['success' => false, 'error' => $e->getMessage()];
    }
}

function parsing_publish_queue_failed($db, $prefx, $p) {
    try {
        return ['success' => true, 'failed' => (new \App\Services\Parsing\PublishQueue())->failedJobs()];
    } catch (\Throwable $e) {
        return ['success' => false, 'error' => $e->getMessage()];
    }
}

function parsing_publish_queue_retry($db, $prefx, $p) {
    $jobId = (int)($p['job_id'] ?? 0);
    if ($jobId <= 0) return ['success' => false, 'error' => 'Missing job_id'];
    try {
        $queue = new \App\Services\Parsing\PublishQueue();
        $ok = $queue->retry($jobId);
        if ($ok) parsing_kick_publish_worker();
        return ['success' => $ok, 'queue' => $queue->counts()];
    } catch (\Throwable $e) {
        return ['success' => false, 'error' => $e->getMessage()];
    }
}

function parsing_publish_queue_dismiss($db, $prefx, $p) {
    $jobId = (int)($p['job_id'] ?? 0);
    if ($jobId <= 0) return ['success' => false, 'error' => 'Missing job_id'];
    try {
        $queue = new \App\Services\Parsing\PublishQueue();
        $ok = $queue->dismiss($jobId);
        return ['success' => $ok, 'queue' => $queue->counts()];
    } catch (\Throwable $e) {
        return ['success' => false, 'error' => $e->getMessage()];
    }
}

/**
 * 999.md publications today, grouped by account. Reads the personal scheduler
 * (sauto_personal_schedules), which carries published_at; the account is the
 * car's 999_api_id. Returns one row per account (incl. zero-count ones).
 */
function parsing_publish_999_stats($db, $prefx) {
    // Account labels, keyed by 999_api_id. Display order is the array order.
    $accounts = [
        4 => 'Encars-MD',
        3 => 'Sauto-stock-extern',
        2 => 'Sauto-comerciale',
        1 => 'Sauto-Haus',
    ];
    try {
        // Published today, per account.
        $pubStmt = $db->query("SELECT c.`999_api_id` AS acc, COUNT(*) AS nr
            FROM {$prefx}_sauto_personal_schedules s
            JOIN {$prefx}_car_ctlg c ON c.id = s.car_id
            WHERE s.status = 'published' AND DATE(s.published_at) = CURDATE()
            GROUP BY c.`999_api_id`");
        $published = [];
        foreach ($pubStmt->fetchAll(\PDO::FETCH_ASSOC) as $r) {
            $published[(int)$r['acc']] = (int)$r['nr'];
        }

        // Still waiting (pending/postponed) scheduled for today, per account.
        $penStmt = $db->query("SELECT c.`999_api_id` AS acc, COUNT(*) AS nr
            FROM {$prefx}_sauto_personal_schedules s
            JOIN {$prefx}_car_ctlg c ON c.id = s.car_id
            WHERE s.status IN ('pending', 'postponed') AND s.schedule_date = CURDATE()
            GROUP BY c.`999_api_id`");
        $pending = [];
        foreach ($penStmt->fetchAll(\PDO::FETCH_ASSOC) as $r) {
            $pending[(int)$r['acc']] = (int)$r['nr'];
        }

        $rows = [];
        $total = 0; $totalPending = 0;
        foreach ($accounts as $id => $label) {
            $n = $published[$id] ?? 0;
            $p = $pending[$id] ?? 0;
            $rows[] = ['account' => $label, 'count' => $n, 'pending' => $p];
            $total += $n; $totalPending += $p;
        }
        // Any account id not in the map above (defensive).
        foreach (array_unique(array_merge(array_keys($published), array_keys($pending))) as $id) {
            if (!isset($accounts[$id])) {
                $n = $published[$id] ?? 0;
                $p = $pending[$id] ?? 0;
                $rows[] = ['account' => '999 cont #'.$id, 'count' => $n, 'pending' => $p];
                $total += $n; $totalPending += $p;
            }
        }
        return ['success' => true, 'total' => $total, 'total_pending' => $totalPending, 'rows' => $rows, 'date' => date('d.m.Y')];
    } catch (\Throwable $e) {
        return ['success' => false, 'error' => $e->getMessage()];
    }
}

// Fire-and-forget HTTP request to the worker wrapper (tiny timeout). The worker
// keeps running via ignore_user_abort; a per-minute cron is the safety net.
function parsing_kick_publish_worker() {
    if (!function_exists('curl_init')) return;
    $host = $_SERVER['HTTP_HOST'] ?? 'localhost';
    $scheme = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
    $url = $scheme . '://' . $host . '/console/parsing_publish_worker_web.php?token=cron2026';
    $ch = curl_init($url);
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true, CURLOPT_NOSIGNAL => true,
        CURLOPT_TIMEOUT_MS => 300, CURLOPT_CONNECTTIMEOUT => 2,
        CURLOPT_SSL_VERIFYPEER => false, CURLOPT_SSL_VERIFYHOST => false,
    ]);
    curl_exec($ch);
    curl_close($ch);
}

function parsing_reject_car($db, $prefx, $p) {
    $carId = (int)($p['car_id'] ?? 0);
    $stmt = $db->prepare('UPDATE '.$prefx.'_parsing_cars SET status = "rejected", rejected_at = NOW() WHERE id = ?');
    $stmt->execute([$carId]);
    return ['success' => true];
}

function parsing_favorite_car($db, $prefx, $p) {
    $carId = (int)($p['car_id'] ?? 0);
    $stmt = $db->prepare('UPDATE '.$prefx.'_parsing_cars SET status = "favorite" WHERE id = ?');
    $stmt->execute([$carId]);
    return ['success' => true];
}

function parsing_unfavorite_car($db, $prefx, $p) {
    $carId = (int)($p['car_id'] ?? 0);
    $stmt = $db->prepare('UPDATE '.$prefx.'_parsing_cars SET status = "proposed" WHERE id = ?');
    $stmt->execute([$carId]);
    return ['success' => true];
}

function parsing_get_car_details($db, $prefx, $p) {
    $carId = (int)($p['car_id'] ?? 0);
    if ($carId <= 0) return ['success' => false, 'error' => 'Invalid id'];

    $stmt = $db->prepare('SELECT * FROM '.$prefx.'_parsing_cars WHERE id = ?');
    $stmt->execute([$carId]);
    $car = $stmt->fetch(PDO::FETCH_ASSOC);
    if (!$car) return ['success' => false, 'error' => 'Not found'];

    // Resolve the real VIN: parsing_cars.vin is usually empty for Encar, but the
    // inspection report (report_data) carries the official VIN. Expose it so the
    // characteristics modal can show it.
    if (empty($car['vin']) && !empty($car['report_data'])) {
        $rd = json_decode($car['report_data'], true);
        if (is_array($rd)) {
            $rvin = $rd['inspection']['master']['detail']['vin'] ?? ($rd['record']['vin'] ?? '');
            $rvin = strtoupper(preg_replace('/[^A-HJ-NPR-Z0-9]/i', '', (string)$rvin));
            if (strlen($rvin) === 17) $car['vin'] = $rvin;
        }
    }

    // OpenLane cars imported before the body-type mapping fix have body_type
    // NULL in the DB. Recover it live from raw_data['Size'] ("carsize.Coupé").
    if (empty($car['body_type']) && ($car['source'] ?? '') === 'openlane' && !empty($car['raw_data'])) {
        $raw = json_decode($car['raw_data'], true) ?: [];
        $item = (!empty($raw['Size']) || !empty($raw['CarId'])) ? $raw : ($raw['raw_data'] ?? $raw);
        $size = $item['Size'] ?? $item['SizeName'] ?? '';
        $bt = parsing_openlane_body_code($size);
        if ($bt) $car['body_type'] = $bt;
    }

    return ['success' => true, 'car' => $car];
}

// Lightweight enrichment used by the card's "MD: …" poller. Pulls the detail page
// (which carries the real engine_volume — e.g. eCarsTrade's "Объем двигателя:
// 1499 CC" — for cars whose title has no litres) WITHOUT calling Groq (no cost),
// then returns the values the card needs to compute a correct MD price.
function parsing_enrich_one_md($db, $prefx, $p) {
    $carId = (int)($p['car_id'] ?? 0);
    if ($carId <= 0) return ['success' => false, 'error' => 'Invalid id'];

    // Best-effort: fetch the detail page and fill engine_volume/specs in the DB.
    try {
        (new \App\Services\Parsing\ParsingOrchestrator())->enrichOnePublic($carId);
    } catch (\Throwable $e) { /* best-effort */ }

    $stmt = $db->prepare("SELECT engine_volume, fuel_type, year, source, price_eur
                          FROM {$prefx}_parsing_cars WHERE id = ? LIMIT 1");
    $stmt->execute([$carId]);
    $car = $stmt->fetch(PDO::FETCH_ASSOC);
    if (!$car) return ['success' => false, 'error' => 'Not found'];

    return [
        'success'   => true,
        'capacity'  => (int)$car['engine_volume'],
        'fuel'      => $car['fuel_type'],
        'year'      => (int)$car['year'],
        'source'    => $car['source'],
        'price_eur' => (float)$car['price_eur'],
    ];
}

// Map an OpenLane "Size" label ("carsize.Coupé", "carsize.Berline"...) to an
// internal body code. Mirrors OpenLaneAdapter::normalizeBodyType for cars whose
// body_type was not stored at import time.
function parsing_openlane_body_code(?string $val): ?string {
    if (!$val) return null;
    $k = strtolower(trim($val));
    if (($pos = strrpos($k, '.')) !== false) $k = substr($k, $pos + 1); // strip "carsize."
    $k = strtr($k, ['é'=>'e','è'=>'e','ê'=>'e','ë'=>'e','à'=>'a','â'=>'a','ï'=>'i','î'=>'i','ô'=>'o','û'=>'u','ü'=>'u','ç'=>'c']);
    $k = str_replace([' ', '-', '_'], '', $k);
    $map = [
        'suv'=>'suv', 'sedan'=>'sedan', 'saloon'=>'sedan', 'berline'=>'sedan', 'limousine'=>'sedan',
        'hatchback'=>'hatchback', 'compact'=>'hatchback',
        'estate'=>'wagon', 'wagon'=>'wagon', 'station'=>'wagon', 'stationwagon'=>'wagon', 'break'=>'wagon', 'touring'=>'wagon',
        'coupe'=>'coupe', 'cabrio'=>'convertible', 'cabriolet'=>'convertible', 'convertible'=>'convertible', 'roadster'=>'convertible',
        'mpv'=>'minivan', 'minivan'=>'minivan', 'minibus'=>'van', 'van'=>'van', 'lighttruck'=>'van',
        'pickup'=>'pickup', 'truck'=>'pickup',
    ];
    return $map[$k] ?? null;
}

function parsing_save_car_edits($db, $prefx, $p) {
    $carId = (int)($p['car_id'] ?? 0);
    if ($carId <= 0) return ['success' => false, 'error' => 'Invalid id'];

    // Whitelist of editable columns.
    $allowed = ['brand','model','year','km','fuel_type','gearbox','engine_volume','power_hp',
                'color','body_type','vin','title_ro','description_ro','price_final_eur'];
    $sets = [];
    $params = [];
    foreach ($allowed as $col) {
        if (array_key_exists($col, $p)) {
            $sets[] = "`{$col}` = ?";
            $val = $p[$col];
            if ($val === '' ) $val = null;
            $params[] = $val;
        }
    }
    if (empty($sets)) return ['success' => false, 'error' => 'No fields to update'];

    $params[] = $carId;
    $sql = 'UPDATE '.$prefx.'_parsing_cars SET '.implode(', ', $sets).' WHERE id = ?';
    $db->prepare($sql)->execute($params);

    return ['success' => true];
}

function parsing_ai_enrich_specs($db, $prefx, $p) {
    $carId = (int)($p['car_id'] ?? 0);
    if ($carId <= 0) return ['success' => false, 'error' => 'Invalid car_id'];

    parsing_ensure_ai_verified_column($db, $prefx);
    $verifiedNow = (int)$db->query("SELECT ai_verified FROM {$prefx}_parsing_cars WHERE id = ".(int)$carId)->fetchColumn();
    if (!$verifiedNow) {
        try {
            (new \App\Services\Parsing\ParsingOrchestrator())->enrichOnePublic($carId);
        } catch (\Throwable $e) { /* best-effort */ }
    }

    $stmt = $db->prepare("SELECT id, brand, model, year, engine_volume, fuel_type, power_hp, drive_type, seats, body_type, gearbox, color, vin, raw_data,
                          source, source_id, report_data, ai_verified
                          FROM {$prefx}_parsing_cars WHERE id = ? LIMIT 1");
    $stmt->execute([$carId]);
    $car = $stmt->fetch(PDO::FETCH_ASSOC);
    if (!$car) return ['success' => false, 'error' => 'Car not found'];

    // Encar only: pull the inspection/diagnosis/accident report once (on the
    // first publish/edit) and cache it in report_data. Best-effort — failures
    // never block publishing.
    if (($car['source'] ?? '') === 'encar' && empty($car['report_data']) && !empty($car['source_id'])) {
        try {
            $adapter = new \App\Services\Parsing\Adapters\EncarAdapter();
            $report = $adapter->fetchInspectionReport((string)$car['source_id']);
            if ($report) {
                $upd = $db->prepare("UPDATE {$prefx}_parsing_cars SET report_data = ? WHERE id = ?");
                $upd->execute([json_encode($report, JSON_UNESCAPED_UNICODE), $carId]);
            }
        } catch (\Throwable $e) { /* best-effort */ }
    }

    if (empty($car['color'])) {
        $db->prepare("UPDATE {$prefx}_parsing_cars SET color = 'blk' WHERE id = ?")->execute([$carId]);
        $car['color'] = 'blk';
    }

    // Re-verify only the FIRST time for unsure sources. Once ai_verified=1, drop
    // re-verification so repeated clicks reuse the corrected data (no contradiction,
    // no wasted Groq calls); only genuine gaps get filled from then on.
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

    // Spec verification uses Groq's llama-3.3-70b-versatile — the most capable model
    // on Groq's free tier for factual car knowledge. The old llama-4-scout-17b was
    // too small and hallucinated (Mercedes C300 → 2996cc/367hp; flipped diesel→
    // petrol). 70b is ~5/6 correct on the hard cases (close to gpt-4o), free, and
    // runs only on the 3 buttons (on-demand) so volume stays within the daily limit.
    $apiKey = defined('GROQ_API_KEY') ? GROQ_API_KEY : '';
    if (empty($apiKey)) {
        return ['success' => false, 'error' => 'No Groq API key configured'];
    }
    $apiUrl = 'https://api.groq.com/openai/v1/chat/completions';
    $model  = 'llama-3.3-70b-versatile';

    $need = [
        'hp'            => empty($car['power_hp'])     || ($forceVerify && in_array('power_hp', $forceFields, true)),
        'drive_type'    => empty($car['drive_type'])   || ($forceVerify && in_array('drive_type', $forceFields, true)),
        'seats'         => empty($car['seats']),
        'engine_volume' => empty($car['engine_volume'])|| ($forceVerify && in_array('engine_volume', $forceFields, true)),
        'body_type'     => empty($car['body_type'])    || ($forceVerify && in_array('body_type', $forceFields, true)),
        'fuel_type'     => empty($car['fuel_type'])    || ($forceVerify && in_array('fuel_type', $forceFields, true)),
        'gearbox'       => empty($car['gearbox'])      || ($forceVerify && in_array('gearbox', $forceFields, true)),
    ];
    $needSeats = $need['seats']; // kept for the response shape below

    // Build the JSON shape requested from the AI (only missing fields).
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
        CURLOPT_TIMEOUT        => 40, // gpt-4o is slower than Groq
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
        return ['success' => false, 'error' => "AI HTTP {$code}: " . substr($body, 0, 200) . " | curl: {$err}"];
    }

    $aiResp = json_decode($body, true);
    $content = $aiResp['choices'][0]['message']['content'] ?? '';
    $content = trim(preg_replace('/^```(?:json)?|```$/m', '', $content));
    $parsed = json_decode($content, true);
    if (!is_array($parsed)) {
        return ['success' => false, 'error' => 'AI returned unparseable output: ' . substr($content, 0, 200)];
    }

    // Validate + persist ONLY the fields we asked for (the missing ones).
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

    // AI ran for this car → mark it so later button clicks skip re-verification.
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

function parsing_test_report($db, $prefx, $p) {
    $carId = (int)($p['car_id'] ?? 0);
    if ($carId <= 0) return ['success' => false, 'error' => 'Invalid car_id'];

    $stmt = $db->prepare("SELECT source, source_id, report_data, vin FROM {$prefx}_parsing_cars WHERE id = ? LIMIT 1");
    $stmt->execute([$carId]);
    $car = $stmt->fetch(PDO::FETCH_ASSOC);
    if (!$car) return ['success' => false, 'error' => 'Car not found'];
    if (($car['source'] ?? '') !== 'encar') return ['success' => false, 'error' => 'Not an Encar car'];
    if (empty($car['source_id'])) return ['success' => false, 'error' => 'No source_id'];

    $carid = (string)$car['source_id'];

    $report = null;
    $forceRefresh = !empty($p['refresh']);
    if (!$forceRefresh && !empty($car['report_data'])) {
        $decoded = json_decode($car['report_data'], true);
        if (is_array($decoded)) $report = $decoded;
    }

    try {
        if ($report === null) {
            $adapter = new \App\Services\Parsing\Adapters\EncarAdapter();
            $report = $adapter->fetchInspectionReport($carid);
            if (!$report) {
                return ['success' => false, 'error' => 'Encar returned no report'];
            }
            $upd = $db->prepare("UPDATE {$prefx}_parsing_cars SET report_data = ? WHERE id = ?");
            $upd->execute([json_encode($report, JSON_UNESCAPED_UNICODE), $carId]);
        }

        // The inspection report's VIN is often masked/partial (Encar hides it on
        // some cars). The full 17-char VIN lives in parsing_cars.vin (pulled from
        // the detail endpoint). Inject it so the report shows the COMPLETE VIN,
        // matching what the edit form shows.
        $fullVin = strtoupper(preg_replace('/[^A-HJ-NPR-Z0-9]/i', '', (string)($car['vin'] ?? '')));
        if (strlen($fullVin) === 17 && $fullVin !== '00000000000000000') {
            $report['full_vin'] = $fullVin;
        }

        // Render the translated HTML exactly as it looks on the public product
        // page: same two cards (Istoric + Dotări) and the same visual style
        // (SVG body diagram, equipment grid). publicStyle=true keeps the VIN/photos
        // visible (admin sees everything) while using the public look.
        $html = '';
        try {
            include_once _ADM_PAGE.'/parsing/parsing_report.php';
            $lang = $_COOKIE['lang'] ?? 'ro';
            $histHtml  = parsing_report_html($report, $lang, false, 'history', true);
            $equipHtml = parsing_report_html($report, $lang, false, 'equipment', true);
            // Both calls emit the same <style> block; keep it once.
            $equipHtml = preg_replace('#<style>.*?</style>#s', '', $equipHtml, 1);
            $html = '<div class="encar-report-public">'.$histHtml.$equipHtml.'</div>';
        } catch (\Throwable $e) { $html = '<em>render error: '.htmlspecialchars($e->getMessage()).'</em>'; }

        return ['success' => true, 'carid' => $carid, 'report' => $report, 'html' => $html];
    } catch (\Throwable $e) {
        return ['success' => false, 'error' => $e->getMessage()];
    }
}

// Stream a remote image (Encar / e-CarsTrade / OpenLane CDN) through our server
// so the browser can fetch it without CORS issues. Allows the edit form to
// auto-fill the file input with parsed photos.
function parsing_image_proxy($url) {
    // Release the PHP session lock immediately. Without this every proxied image
    // request serializes behind the same session, so the grid's thumbnails load
    // one-by-one (1,2,3...). Closing the session lets the browser's ~6 parallel
    // connections all hit the proxy at once.
    if (session_status() === PHP_SESSION_ACTIVE) {
        session_write_close();
    }

    // Kill any output compression/buffering for this binary response. With gzip
    // on (ob_gzhandler / zlib.output_compression), our manual Content-Length is
    // the UNcompressed size, so the browser sees a length mismatch and renders
    // the JPEG progressively — which shows up as a squashed-then-corrected
    // thumbnail. Encar images bypass PHP entirely, so they never do this.
    @ini_set('zlib.output_compression', '0');
    if (function_exists('apache_setenv')) { @apache_setenv('no-gzip', '1'); }
    while (ob_get_level() > 0) { @ob_end_clean(); }

    $url = trim((string)$url);
    if ($url === '') { http_response_code(400); echo 'no url'; return; }

    // Accept absolute http(s) URLs only.
    if (!preg_match('#^https?://#i', $url)) { http_response_code(400); echo 'bad url'; return; }

    // Whitelist source hosts to avoid being used as an open proxy.
    $allowedHosts = ['ci.encar.com', 'fem.encar.com', 'www.encar.com', 'api.encar.com',
        'images.openlane.eu', 'ecarstrade.com'];
    $host = parse_url($url, PHP_URL_HOST) ?: '';
    $hostOk = false;
    foreach ($allowedHosts as $h) {
        if (stripos($host, $h) !== false) { $hostOk = true; break; }
    }
    // Also allow internal paths (already-downloaded images).
    if (!$hostOk) {
        if (stripos($host, $_SERVER['HTTP_HOST'] ?? '') !== false) {
            $hostOk = true;
        }
    }
    if (!$hostOk) { http_response_code(403); echo 'host blocked'; return; }

    // If it's a local path on our domain, just read the file.
    $hostMine = $_SERVER['HTTP_HOST'] ?? '';
    if ($hostMine && stripos($host, $hostMine) !== false) {
        $localPath = $_SERVER['DOCUMENT_ROOT'] . parse_url($url, PHP_URL_PATH);
        if (is_file($localPath)) {
            header('Content-Type: image/jpeg');
            header('Content-Length: ' . filesize($localPath));
            readfile($localPath);
            return;
        }
    }

    // Fetch the remote image.
    $referer = 'https://www.encar.com/';
    if (stripos($host, 'ecarstrade') !== false) $referer = 'https://ru.ecarstrade.com/';
    if (stripos($host, 'openlane')   !== false) $referer = 'https://www.openlane.eu/';

    if (stripos($host, 'encar.com') !== false) {
        $url = preg_replace('/\?.*$/', '', $url);
        // 1280px: clearly sharper than the old 900px while still reasonable in
        // size. Encar's impolicy=heightRate resizes server-side; cw=width,
        // rh/ch=height (≈4:3). sauto re-processes on upload anyway.
        $url .= '?impolicy=heightRate&cw=1280&rh=854&ch=854&cg=Center';
    }

    // Size variants (downscaled ONCE at cache-miss, then served from disk):
    //   sz=card    → ~600px  (grid thumbnails, ≈40KB)
    //   sz=gallery → ~1000px (big slider — sharp but ~3-4× lighter than full)
    //   (none)     → full resolution
    $sz = $_GET['sz'] ?? '';
    $maxW = ($sz === 'card') ? 600 : (($sz === 'gallery') ? 1000 : 0);

    // Serve from the shared fetch/cache helper.
    $bytes = parsing_image_fetch_cached($url, $referer, $maxW);
    if ($bytes === null) {
        http_response_code(502);
        echo 'fetch failed';
        return;
    }
    header('Content-Type: image/jpeg');
    header('Content-Length: ' . strlen($bytes));
    header('Cache-Control: public, max-age=2592000, immutable'); // 30 days
    echo $bytes;
    return;
}

// Fetch a remote image (with the given referer), optionally downscale to a max
// width ($maxW px; 0 = keep full size), and disk-cache the result. Returns the
// JPEG bytes or null. Shared by the live proxy and the import-time cache warmer
// so the catalog opens with images already on disk.
function parsing_image_fetch_cached(string $url, string $referer, int $maxW = 0): ?string
{
    $cacheDir  = $_SERVER['DOCUMENT_ROOT'] . '/tmp/parsing_imgcache';
    // Cache key includes the size so 600px/1000px/full never clash.
    $suffix    = $maxW > 0 ? ('_' . $maxW) : '';
    $cacheFile = $cacheDir . '/' . sha1($url) . $suffix . '.jpg';
    $cacheTtl  = 7 * 24 * 3600; // 7 days

    if (is_file($cacheFile) && (time() - filemtime($cacheFile)) < $cacheTtl) {
        $bytes = @file_get_contents($cacheFile);
        if ($bytes !== false && strlen($bytes) >= 500) return $bytes;
    }

    $ch = curl_init($url);
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_FOLLOWLOCATION => true,
        CURLOPT_TIMEOUT        => 20,
        CURLOPT_SSL_VERIFYPEER => false,
        CURLOPT_ENCODING       => '',
        CURLOPT_HTTPHEADER     => [
            'User-Agent: Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/120.0.0.0 Safari/537.36',
            'Accept: image/avif,image/webp,image/apng,image/*,*/*;q=0.8',
            'Referer: ' . $referer,
        ],
    ]);
    $bytes = curl_exec($ch);
    $code  = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    if ($bytes === false || $code !== 200 || strlen($bytes) < 500) {
        return null;
    }

    // Downscale once (GD) to $maxW. Best-effort: on failure keep original.
    if ($maxW > 0 && function_exists('imagecreatefromstring')) {
        $src = @imagecreatefromstring($bytes);
        if ($src !== false) {
            $w = imagesx($src); $h = imagesy($src);
            if ($w > $maxW) {
                $nw = $maxW; $nh = (int)round($h * ($maxW / $w));
                $dst = imagecreatetruecolor($nw, $nh);
                imagecopyresampled($dst, $src, 0, 0, 0, 0, $nw, $nh, $w, $h);
                ob_start(); imagejpeg($dst, null, 82); $small = ob_get_clean();
                imagedestroy($dst);
                if ($small && strlen($small) >= 500) $bytes = $small;
            }
            imagedestroy($src);
        }
    }

    if (!is_dir($cacheDir)) @mkdir($cacheDir, 0755, true);
    @file_put_contents($cacheFile, $bytes, LOCK_EX);
    return $bytes;
}

function parsing_remove_published($db, $prefx, $p) {
    $carId = (int)($p['car_id'] ?? 0);
    $publisher = new \App\Services\Parsing\ParsingPublisher();
    return $publisher->unpublish($carId);
}

function parsing_fetch_by_link($db, $prefx, $p) {
    $url = trim($p['url'] ?? '');
    if ($url === '') {
        return ['success' => false, 'error' => 'URL is empty'];
    }

    // Quickly check we have an adapter for this host before doing any work.
    $adapter = \App\Services\Parsing\AdapterFactory::detectFromUrl($url);
    if (!$adapter) {
        return ['success' => false, 'error' => 'No adapter matches this URL'];
    }

    try {
        $orchestrator = new \App\Services\Parsing\ParsingOrchestrator();
        $result = $orchestrator->runByUrl($url);
        return $result;
    } catch (Throwable $e) {
        return ['success' => false, 'error' => $e->getMessage()];
    }
}

function parsing_search_now($db, $prefx, $p) {
    // Release the PHP session lock immediately — this is a slow request (~2-3s)
    // and we no longer read/write the session here. Without this, the session
    // lock held by THIS request blocks every other admin AJAX call until it
    // finishes, which made the page feel frozen / the spinner run "forever".
    if (session_id() !== '') { @session_write_close(); }

    // Server time just before importing — cars with found_at >= this are the ones
    // we just brought in, so the UI can ring them in the catalog. 10s safety margin
    // covers tiny PHP/MySQL clock differences.
    $importedSince = time() - 10;

    $sources = $p['sources'] ?? [];
    if (!is_array($sources)) {
        $sources = array_filter(array_map('trim', explode(',', (string)$sources)));
    }
    if (empty($sources)) {
        return ['success' => false, 'error' => 'Selectează cel puțin o sursă'];
    }

    $singleSource = count($sources) === 1 ? $sources[0] : null;

    // For Encar: brand/model are already the taxonomy API keys (e.g. "BMW", "아우디", "X5").
    // For other sources: brand is a sauto internal code — look up the display name.
    $brandName = null;
    // Encar AND OpenLane send brand/model as the exact API strings (from their
    // own taxonomy selects), so pass them through unchanged. Other sources send
    // a sauto internal code that must be resolved to a display name.
    $taxonomySource = in_array($singleSource, ['encar', 'openlane', 'ecarstrade'], true);
    if (!empty($p['brand'])) {
        if ($taxonomySource) {
            $brandName = trim($p['brand']);
        } else {
            $stmt = $db->prepare('SELECT `br_nm` FROM '.$prefx.'_car_list WHERE `br` = ? LIMIT 1');
            $stmt->execute([$p['brand']]);
            $brandName = $stmt->fetchColumn() ?: $p['brand'];
        }
    }
    $modelName = null;
    if (!empty($p['model']) && !empty($p['brand'])) {
        if ($taxonomySource) {
            $modelName = trim($p['model']);
        } else {
            $stmt = $db->prepare('SELECT `mo_nm` FROM '.$prefx.'_car_list WHERE `br` = ? AND `mo` = ? LIMIT 1');
            $stmt->execute([$p['brand'], $p['model']]);
            $modelName = $stmt->fetchColumn() ?: $p['model'];
        }
    }

    $criteria = [
        'brand'          => $brandName,
        'model'          => $modelName,
        'generation'     => !empty($p['generation']) ? trim($p['generation']) : null,
        'body_type'      => $p['body_type'] ?? null,
        'category'       => $p['category'] ?? null,
        'seats'          => !empty($p['seats']) ? (int)$p['seats'] : null,
        'year_from'      => !empty($p['year_from']) ? (int)$p['year_from'] : null,
        'year_to'        => !empty($p['year_to']) ? (int)$p['year_to'] : null,
        'km_min'         => !empty($p['km_min']) ? (int)$p['km_min'] : null,
        'km_max'         => !empty($p['km_max']) ? (int)$p['km_max'] : null,
        'engine_volume'  => !empty($p['engine_volume']) ? (int)$p['engine_volume'] : null,
        'fuel_type'      => $p['fuel_type'] ?? null,
        'gearbox'        => $p['gearbox'] ?? null,
        'drive_type'     => $p['drive_type'] ?? null,
        'price_min'      => !empty($p['price_min']) ? (int)$p['price_min'] : null,
        'price_max'      => !empty($p['price_max']) ? (int)$p['price_max'] : null,
        'country_origin'  => $p['country_origin'] ?? null,
        'car_sub_model'   => !empty($p['car_sub_model']) ? trim($p['car_sub_model']) : null,
        'premium_offer'   => !empty($p['premium_offer']) ? trim($p['premium_offer']) : null,
    ];

    $orchestrator = new \App\Services\Parsing\ParsingOrchestrator();
    $found = 0; $imported = 0; $duplicates = 0; $totalCount = 0; $catalogOffset = 0; $errors = [];

    // Resume offset per criteria, stored in the DB (NOT the session — the
    // session lock used to freeze the browser). Each "Search" advances through
    // the source catalog: 1st click 0-200, 2nd 200-400, ... so we gradually
    // pull the WHOLE brand catalog instead of re-scanning the newest 200.
    $offsetKey = 'offset_' . md5(json_encode($criteria, JSON_UNESCAPED_UNICODE) . '|' . implode(',', $sources));
    $ofStmt = $db->prepare('SELECT setting_value FROM '.$prefx.'_parsing_settings WHERE setting_key = ?');
    $ofStmt->execute([$offsetKey]);
    $criteria['_offset_start'] = (int)($ofStmt->fetchColumn() ?: 0);

    foreach ($sources as $sourceCode) {
        $adapter = \App\Services\Parsing\AdapterFactory::create($sourceCode);
        if (!$adapter) {
            $errors[] = "Sursă necunoscută: {$sourceCode}";
            continue;
        }
        try {
            $srcCriteria = $criteria;
            // Encar pulls up to 300 cars per manual search (default is 200).
            if ($sourceCode === 'encar') {
                $srcCriteria['_max_results'] = 300;
            }
            $cars = $adapter->searchByFilter($srcCriteria);
            $found += count($cars);
            if (property_exists($adapter, 'lastTotalCount')) {
                $totalCount = max($totalCount, (int)$adapter->lastTotalCount);
            }
            $resultCounts = [];
            $srcImported = 0; $srcSkipped = 0;
            foreach ($cars as $raw) {
                $result = $orchestrator->ingestCarPublic($raw, null, true);
                $resultCounts[$result] = ($resultCounts[$result] ?? 0) + 1;
                if ($result === 'imported')      { $imported++; $srcImported++; }
                elseif ($result === 'duplicate') { $duplicates++; $srcSkipped++; }
                else                             { $srcSkipped++; }
            }
            if (!empty($resultCounts)) {
                $errors[] = "[{$sourceCode}] results: " . json_encode($resultCounts);
            }

            // Log this manual direct search into parsing_runs so it shows in
            // /parsing/logs (filter_id = 0 means "ad-hoc search", not a saved filter).
            try {
                $foundSrc = count($cars);
                $st = $foundSrc === 0 ? 'partial' : ($srcImported > 0 ? 'success' : 'partial');
                $logStmt = $db->prepare('INSERT INTO '.$prefx.'_parsing_runs
                    (filter_id, source, run_type, started_at, finished_at, status, found_count, imported_count, skipped_count)
                    VALUES (0, :src, "manual", NOW(), NOW(), :st, :f, :i, :s)');
                $logStmt->execute([
                    ':src' => $sourceCode, ':st' => $st,
                    ':f' => $foundSrc, ':i' => $srcImported, ':s' => $srcSkipped,
                ]);
            } catch (\Throwable $e) { /* logging is best-effort */ }
            // Advance the saved offset so the next search continues further into
            // the catalog. If the source returned nothing, wrap back to 0 so a
            // future click re-scans from the top (catches newly listed cars).
            $nextOffset = property_exists($adapter, 'lastOffset') ? (int)$adapter->lastOffset : 0;
            if (count($cars) === 0) $nextOffset = 0;
            $catalogOffset = max($catalogOffset, $nextOffset);
            $saveOf = $db->prepare('INSERT INTO '.$prefx.'_parsing_settings (setting_key, setting_value)
                VALUES (:k, :v) ON DUPLICATE KEY UPDATE setting_value = :v2');
            $saveOf->execute([':k' => $offsetKey, ':v' => (string)$nextOffset, ':v2' => (string)$nextOffset]);
        } catch (\Throwable $e) {
            $errors[] = "[{$sourceCode}] " . $e->getMessage();
        }
    }

    // Send response immediately, then continue enrichment in background
    // so the user sees results instantly while detail data fills in.
    $payload = [
        'success'    => true,
        'found'      => $found,
        'imported'   => $imported,
        'duplicates' => $duplicates,
        'imported_since' => $importedSince,   // ring cars with found_at >= this
        'total_count'    => $totalCount,
        'catalog_offset' => min($catalogOffset, $totalCount ?: $catalogOffset),
        'errors'     => $errors,
    ];

    // Return immediately — search + import only (~2-3s). Detail enrichment
    // (gearbox/seats/images per car) would hang the request if done inline, so we
    // launch it DETACHED in the background; the cron remains a fallback. The user
    // sees results instantly and the rows fill in within a few seconds.
    if ($imported > 0) {
        parsing_spawn_enrich_worker();
    }
    return $payload;
}

// Build the OpenLane "Report" HTML for a car: equipment (grouped) + condition
// Save a fresh OpenLane cookie (+ optional RVT token) into .env. The browser
// cookie string holds special chars, so read RAW $_POST (not the sanitised $p).
function parsing_openlane_save_cookie($db, $prefx, $p) {
    $cookie = trim((string)($_POST['cookie'] ?? ''));
    $rvt    = trim((string)($_POST['rvt'] ?? ''));
    if ($cookie === '') return ['success' => false, 'error' => 'Cookie gol'];

    $envFile = ($_SERVER['DOCUMENT_ROOT'] ?? dirname(__DIR__, 4)) . '/.env';
    $env = is_file($envFile) ? file_get_contents($envFile) : '';

    // Replace or append OPENLANE_COOKIE / OPENLANE_RVT lines.
    $setLine = function ($content, $key, $val) {
        $line = $key . '=' . $val;
        if (preg_match('/^' . preg_quote($key, '/') . '=.*$/m', $content)) {
            return preg_replace('/^' . preg_quote($key, '/') . '=.*$/m', $line, $content);
        }
        return rtrim($content) . "\n" . $line . "\n";
    };
    $env = $setLine($env, 'OPENLANE_COOKIE', $cookie);
    if ($rvt !== '') $env = $setLine($env, 'OPENLANE_RVT', $rvt);

    if (@file_put_contents($envFile, $env) === false) {
        return ['success' => false, 'error' => 'Nu pot scrie .env'];
    }
    return ['success' => true];
}

// Test the saved OpenLane cookie: fetch one detail and see if the VIN comes back
// 17-char (logged in) or masked (expired). Returns logged_in + a sample VIN.
function parsing_openlane_check_cookie($db, $prefx, $p) {
    // Pick the most recent OpenLane car for the test.
    $row = $db->query("SELECT raw_data FROM {$prefx}_parsing_cars WHERE source='openlane' ORDER BY id DESC LIMIT 1")->fetch(PDO::FETCH_ASSOC);
    if (!$row) return ['success' => false, 'error' => 'Nicio mașină OpenLane de testat'];
    $raw = json_decode($row['raw_data'] ?? '{}', true) ?: [];
    $item = (!empty($raw['AuctionId']) ? $raw : ($raw['raw_data'] ?? $raw));
    $aid = (string)($item['AuctionId'] ?? '');
    if ($aid === '') return ['success' => false, 'error' => 'AuctionId lipsă'];

    $adapter = \App\Services\Parsing\AdapterFactory::create('openlane');
    if (!$adapter || !method_exists($adapter, 'fetchDetailRaw')) {
        return ['success' => false, 'error' => 'Adapter indisponibil'];
    }
    $d = $adapter->fetchDetailRaw($aid);
    if (!is_array($d)) {
        return ['success' => true, 'logged_in' => false, 'error' => 'Detaliu null (cookie expirat / 403)'];
    }
    $vin = preg_replace('/[^A-HJ-NPR-Z0-9]/i', '', (string)($d['ChassisNumber'] ?? ''));
    return ['success' => true, 'logged_in' => strlen($vin) === 17, 'vin' => $vin];
}

// Save a fresh eCarsTrade cookie into .env (ECARSTRADE_COOKIE). Raw $_POST.
function parsing_ecarstrade_save_cookie($db, $prefx, $p) {
    $cookie = trim((string)($_POST['cookie'] ?? ''));
    if ($cookie === '') return ['success' => false, 'error' => 'Cookie gol'];
    $envFile = ($_SERVER['DOCUMENT_ROOT'] ?? dirname(__DIR__, 4)) . '/.env';
    $env = is_file($envFile) ? file_get_contents($envFile) : '';
    $line = 'ECARSTRADE_COOKIE=' . $cookie;
    if (preg_match('/^ECARSTRADE_COOKIE=.*$/m', $env)) {
        $env = preg_replace('/^ECARSTRADE_COOKIE=.*$/m', $line, $env);
    } else {
        $env = rtrim($env) . "\n" . $line . "\n";
    }
    if (@file_put_contents($envFile, $env) === false) {
        return ['success' => false, 'error' => 'Nu pot scrie .env'];
    }
    return ['success' => true];
}

// Test the eCarsTrade cookie: fetch a detail page and look for a 17-char VIN.
function parsing_ecarstrade_check_cookie($db, $prefx, $p) {
    $row = $db->query("SELECT source_id FROM {$prefx}_parsing_cars WHERE source='ecarstrade' ORDER BY id DESC LIMIT 1")->fetch(PDO::FETCH_ASSOC);
    if (!$row || empty($row['source_id'])) return ['success' => false, 'error' => 'Nicio mașină eCarsTrade de testat'];

    $adapter = \App\Services\Parsing\AdapterFactory::create('ecarstrade');
    if (!$adapter || !method_exists($adapter, 'fetchById')) {
        return ['success' => false, 'error' => 'Adapter indisponibil'];
    }
    $d = $adapter->fetchById((string)$row['source_id']);
    if (!is_array($d)) {
        return ['success' => true, 'logged_in' => false, 'error' => 'Detaliu null (cookie expirat / login)'];
    }
    $vin = preg_replace('/[^A-HJ-NPR-Z0-9]/i', '', (string)($d['vin'] ?? ''));
    return ['success' => true, 'logged_in' => strlen($vin) === 17, 'vin' => $vin];
}

// (damages). Data comes from the detail endpoint (EtgOptionList + Damage),
// fetched via the adapter and cached in the row's raw_data when possible.
function parsing_openlane_report($db, $prefx, $p) {
    $carId = (int)($p['car_id'] ?? 0);
    if ($carId <= 0) return ['success' => false, 'error' => 'Invalid car_id'];

    $stmt = $db->prepare("SELECT source, source_id, raw_data FROM {$prefx}_parsing_cars WHERE id = ? LIMIT 1");
    $stmt->execute([$carId]);
    $car = $stmt->fetch(PDO::FETCH_ASSOC);
    if (!$car || ($car['source'] ?? '') !== 'openlane') {
        return ['success' => false, 'error' => 'Not an OpenLane car'];
    }

    // Resolve AuctionId (saved in raw_data) and fetch the full detail payload.
    $raw = json_decode($car['raw_data'] ?? '{}', true) ?: [];
    $item = (!empty($raw['CarId']) ? $raw : ($raw['raw_data'] ?? $raw));
    $auctionId = (string)($item['AuctionId'] ?? '');

    $adapter = \App\Services\Parsing\AdapterFactory::create('openlane');
    if (!$adapter || !method_exists($adapter, 'fetchDetailRaw')) {
        return ['success' => false, 'error' => 'Adapter unavailable'];
    }
    $detail = $adapter->fetchDetailRaw($auctionId);
    if (!is_array($detail)) {
        return ['success' => false, 'error' => 'Nu am putut prelua raportul (cookie expirat?)'];
    }

    $lang = in_array($_COOKIE['lang'] ?? 'ro', ['ro','ru','en'], true) ? $_COOKIE['lang'] : 'ro';
    // Condition diagram (shared helper) + equipment list. In the ADMIN we also run
    // the AI translation on the equipment labels (cached) — the public page uses
    // the dictionary-only renderer instead.
    require_once(_ADM_PAGE.'/parsing/parsing_openlane_report.php');
    $conditionHtml = parsing_openlane_condition_html($detail, $lang);
    $items = []; $seen = [];
    foreach (($detail['EtgOptionList'] ?? []) as $opt) {
        $name = trim((string)($opt['Name'] ?? ''));
        if ($name === '') continue;
        $name = preg_replace('/^\(Car\)\s*/i', '', $name);
        $parts = explode(' - ', $name, 2);
        $label = parsing_openlane_tr_equip(count($parts) === 2 ? trim($parts[1]) : trim($parts[0]), $lang);
        $key = mb_strtolower($label, 'UTF-8');
        if (isset($seen[$key])) continue;
        $seen[$key] = true;
        $items[] = ['label' => $label, 'top' => false];
    }
    if ($lang !== 'en' && $carId > 0) {
        $items = parsing_ai_translate_equipment($items, $lang, $carId, 'ol');
    }
    usort($items, function ($a, $b) { return strcasecmp($a['label'], $b['label']); });
    $html = $conditionHtml . parsing_equipment_report_html($items, $lang);
    return ['success' => true, 'html' => $html];
}
// eCarsTrade equipment report — same "Dotări" modal as OpenLane, fed by the
// detail-page equipment list (option-name spans, ★ for high-value).
function parsing_ecarstrade_report($db, $prefx, $p) {
    $carId = (int)($p['car_id'] ?? 0);
    if ($carId <= 0) return ['success' => false, 'error' => 'Invalid car_id'];

    $stmt = $db->prepare("SELECT source, source_id FROM {$prefx}_parsing_cars WHERE id = ? LIMIT 1");
    $stmt->execute([$carId]);
    $car = $stmt->fetch(PDO::FETCH_ASSOC);
    if (!$car || ($car['source'] ?? '') !== 'ecarstrade') {
        return ['success' => false, 'error' => 'Not an eCarsTrade car'];
    }

    $adapter = \App\Services\Parsing\AdapterFactory::create('ecarstrade');
    if (!$adapter || !method_exists($adapter, 'fetchEquipment')) {
        return ['success' => false, 'error' => 'Adapter unavailable'];
    }
    $eq = $adapter->fetchEquipment((string)$car['source_id']);
    if (!is_array($eq)) {
        // Most often the listing was removed/sold on eCarsTrade (404), not a cookie
        // problem. See logs/parsing_ecarstrade.log for the real HTTP status.
        return ['success' => false, 'error' => 'Nu am putut prelua dotările — anunțul nu mai există pe eCarsTrade sau sesiunea a expirat (vezi logs).'];
    }

    $lang  = in_array($_COOKIE['lang'] ?? 'ro', ['ro','ru','en'], true) ? $_COOKIE['lang'] : 'ro';
    $items = $eq['items'] ?? [];
    // eCarsTrade ships labels in a mix of RU/IT/EN. Translate them all into the
    // admin's language via Groq, cached per car+lang on disk so we ask once.
    $items = parsing_ai_translate_equipment($items, $lang, (int)$car['id']);
    require_once(_ADM_PAGE.'/parsing/parsing_openlane_report.php'); // shared equipment renderer
    $html = parsing_equipment_report_html($items, $lang);
    return ['success' => true, 'html' => $html];
}

// Match a parsing source's free-text model name (e.g. eCarsTrade "BMW 320",
// "320d") to the correct sauto model option (e.g. "Seria 3"). The browser sends
// the brand, the raw model text, and the REAL list of sauto model options for
// that brand ([{value,text}, ...]); Groq picks the best-matching option value.
// Cached on disk per brand+raw so we ask once. Returns ['value'=>..,'text'=>..].
function parsing_match_model($db, $prefx, $p) {
    $brand   = trim((string)($p['brand'] ?? ''));
    $rawModel = trim((string)($p['raw_model'] ?? ''));
    $optsJson = (string)($p['options'] ?? '[]');
    $options  = json_decode($optsJson, true);
    if (!is_array($options) || !$options) {
        return ['success' => false, 'error' => 'No options'];
    }
    if ($rawModel === '') return ['success' => false, 'error' => 'No raw model'];

    // Disk cache: <tmp>/parsing_model_match/v<N>_<brand>_<raw>.json. Bump the
    // version when the model or prompt changes so stale answers (e.g. an old 8b
    // "530 -> 3 Series" mis-map) are never reused. v4 = switched to llama-3.3-70b
    // (scout-17b was unstable: same 316 sometimes mapped, sometimes injected new).
    $cacheVer = 'v4';
    $cacheDir = sys_get_temp_dir() . '/parsing_model_match';
    $ck = $cacheDir . '/' . $cacheVer . '_' . preg_replace('/[^a-z0-9]+/i', '_', mb_strtolower($brand . '_' . $rawModel, 'UTF-8')) . '.json';
    if (is_file($ck)) {
        $c = json_decode((string)@file_get_contents($ck), true);
        if (is_array($c) && isset($c['value'])) {
            // Make sure the cached value still exists in the current option list.
            foreach ($options as $o) {
                if ((string)($o['value'] ?? '') === (string)$c['value']) return ['success' => true] + $c + ['cached' => true];
            }
        }
    }

    // Parsing uses Groq only (cost). No OpenAI fallback here on purpose.
    $groqKey = defined('GROQ_API_KEY') ? GROQ_API_KEY : '';
    if (empty($groqKey)) return ['success' => false, 'error' => 'No Groq key'];

    // Give the AI just the texts; it returns the chosen text, we map back to value.
    $optTexts = [];
    foreach ($options as $o) {
        $t = trim((string)($o['text'] ?? ''));
        if ($t !== '') $optTexts[] = $t;
    }
    $listJson = json_encode($optTexts, JSON_UNESCAPED_UNICODE);

    $prompt = "A car is listed as brand \"{$brand}\", model \"{$rawModel}\". "
        . "Pick the SINGLE best-matching model from this official model list for that brand. "
        . "Engine/trim codes map to their series (e.g. BMW 320/318/330 -> \"Seria 3\"/\"3 Series\", "
        . "BMW 740/750 -> \"7 Series\", Mercedes E 220/E 200/E 400/E300de -> \"E Class\", "
        . "C 220/C200 -> \"C Class\", GLC 300 -> \"GLC\", A4/A6 stay as-is). Prefer an EXISTING "
        . "series model over an exact trim code. Return ONLY JSON: {\"match\": \"<exact text from "
        . "the list>\"}. If truly nothing fits, return {\"match\": \"\"}. List:\n" . $listJson;

    $payload = [
        // 70b is far more reliable for series mapping (316 -> Seria 3) than the
        // small scout-17b, which mapped inconsistently. Same model used by the
        // spec-verification step.
        'model' => 'llama-3.3-70b-versatile',
        'messages' => [
            ['role' => 'system', 'content' => 'You output only valid JSON. No markdown, no commentary.'],
            ['role' => 'user',   'content' => $prompt],
        ],
        'temperature' => 0,
        'max_tokens'  => 60,
    ];
    $ch = curl_init('https://api.groq.com/openai/v1/chat/completions');
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_POST           => true,
        CURLOPT_POSTFIELDS     => json_encode($payload),
        CURLOPT_TIMEOUT        => 20,
        CURLOPT_HTTPHEADER     => ['Authorization: Bearer ' . $groqKey, 'Content-Type: application/json'],
    ]);
    $body = curl_exec($ch);
    $code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    if ($code !== 200 || !$body) return ['success' => false, 'error' => "Groq HTTP {$code}"];

    $resp = json_decode($body, true);
    $content = trim(preg_replace('/^```(?:json)?|```$/m', '', $resp['choices'][0]['message']['content'] ?? ''));
    $parsed = json_decode($content, true);
    $matchText = is_array($parsed) ? trim((string)($parsed['match'] ?? '')) : '';
    if ($matchText === '') return ['success' => false, 'error' => 'No match'];

    // Map the chosen text back to its option value. Normalize away spaces/dashes/
    // case ("C-Class" == "C Class" == "cclass") so a tiny formatting difference in
    // the AI answer doesn't drop a valid match and trigger a wrong new model.
    $normTxt = function ($s) {
        $s = mb_strtolower(trim((string)$s), 'UTF-8');
        return preg_replace('/[\s\-_]+/u', '', $s);
    };
    $needle = $normTxt($matchText);
    foreach ($options as $o) {
        if ($normTxt($o['text'] ?? '') === $needle) {
            $out = ['value' => (string)($o['value'] ?? ''), 'text' => trim((string)$o['text'])];
            if (!is_dir($cacheDir)) @mkdir($cacheDir, 0775, true);
            @file_put_contents($ck, json_encode($out, JSON_UNESCAPED_UNICODE));
            return ['success' => true] + $out;
        }
    }
    return ['success' => false, 'error' => 'Match not in list: ' . $matchText];
}

// Translate a list of equipment labels (mixed RU/IT/EN) into $lang using Groq.
// Order/★ flags are preserved; only the label text changes. Cached on disk per
// car+lang so repeated opens are instant and free. Falls back to the originals
// on any error (never blocks the report).
function parsing_ai_translate_equipment(array $items, string $lang, int $carId, string $srcTag = 'ec'): array {
    if (!$items || $lang === '') return $items;

    // Disk cache: <tmp>/parsing_equip_tr/v2_<src>_<car>_<lang>.json
    // Version prefix (v2) invalidates older caches that held wrong AI translations
    // (e.g. "Cruise control" cached as "Limitator de viteză" before it was pinned
    // in the static dictionary).
    $cacheDir = sys_get_temp_dir() . '/parsing_equip_tr';
    $cacheKey = $cacheDir . '/v2_' . $srcTag . '_' . $carId . '_' . $lang . '.json';
    if (is_file($cacheKey)) {
        $cached = json_decode((string)@file_get_contents($cacheKey), true);
        if (is_array($cached) && count($cached) === count($items)) {
            foreach ($items as $i => &$it) {
                if (isset($cached[$i]) && $cached[$i] !== '') $it['label'] = $cached[$i];
            }
            unset($it);
            usort($items, function ($a, $b) {
                return strcasecmp($a['label'], $b['label']);
            });
            return $items;
        }
    }

    // Parsing uses Groq only (cost). No OpenAI fallback on purpose.
    $groqKey = defined('GROQ_API_KEY') ? GROQ_API_KEY : '';
    if (empty($groqKey)) return $items; // no key → keep originals
    $apiUrl = 'https://api.groq.com/openai/v1/chat/completions';
    $apiKey = $groqKey; $model = 'meta-llama/llama-4-scout-17b-16e-instruct';

    $langName = ['ro' => 'Romanian', 'ru' => 'Russian', 'en' => 'English'][$lang] ?? 'Romanian';
    // Number the labels so the AI returns a clean, order-preserving JSON array.
    $src = [];
    foreach ($items as $i => $it) { $src[] = $it['label']; }
    $listJson = json_encode($src, JSON_UNESCAPED_UNICODE);

    $prompt = "Translate each car-equipment feature below into {$langName}. "
        . "These are short automotive option names (mixed Russian, Italian, English). "
        . "Use natural, concise {$langName} automotive terminology. Keep brand/tech names "
        . "(LED, Bluetooth, ABS, ISOFIX, HUD, CD, MP3) as-is. Return ONLY a JSON array of "
        . "strings, SAME length and SAME order as the input. Input:\n" . $listJson;

    $payload = [
        'model' => $model,
        'messages' => [
            ['role' => 'system', 'content' => 'You output only a valid JSON array of strings. No markdown, no commentary.'],
            ['role' => 'user',   'content' => $prompt],
        ],
        'temperature' => 0,
        'max_tokens'  => 2000,
    ];

    $ch = curl_init($apiUrl);
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_POST           => true,
        CURLOPT_POSTFIELDS     => json_encode($payload),
        CURLOPT_TIMEOUT        => 30,
        CURLOPT_HTTPHEADER     => ['Authorization: Bearer ' . $apiKey, 'Content-Type: application/json'],
    ]);
    $body = curl_exec($ch);
    $code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    if ($code !== 200 || !$body) return $items;

    $resp = json_decode($body, true);
    $content = $resp['choices'][0]['message']['content'] ?? '';
    $content = trim(preg_replace('/^```(?:json)?|```$/m', '', $content));
    $tr = json_decode($content, true);
    if (!is_array($tr) || count($tr) !== count($items)) return $items; // mismatch → originals

    // Apply + cache the translated labels (positional).
    $toCache = [];
    foreach ($items as $i => &$it) {
        $t = isset($tr[$i]) ? trim((string)$tr[$i]) : '';
        if ($t !== '') $it['label'] = $t;
        $toCache[$i] = $t !== '' ? $t : $it['label'];
    }
    unset($it);
    if (!is_dir($cacheDir)) @mkdir($cacheDir, 0775, true);
    @file_put_contents($cacheKey, json_encode($toCache, JSON_UNESCAPED_UNICODE));

    // Re-sort: translation may change alphabetical order. Plain A-Z.
    usort($items, function ($a, $b) {
        return strcasecmp($a['label'], $b['label']);
    });
    return $items;
}
// Return OpenLane's filter facets (option lists + counts: makes, models, fuel,
// gearbox, body, premium offers, countries). Cached on disk so the advanced
// filter can populate its dropdowns from REAL OpenLane data (guaranteed to match
// the search API) without hitting OpenLane on every page load.
//   no make  → global facets (all makes + counts), cached 6h
//   ?make=X  → that make's models + counts, cached 6h per make
function parsing_openlane_facets($db, $prefx, $p) {
    $make = trim((string)($p['make'] ?? ''));
    $cacheDir = $_SERVER['DOCUMENT_ROOT'] . '/tmp/parsing_facets';
    $cacheKey = $make !== '' ? 'make_' . preg_replace('/[^a-z0-9]+/i', '_', strtolower($make)) : 'global';
    $cacheFile = $cacheDir . '/' . $cacheKey . '.json';
    $ttl = 6 * 3600; // 6 hours

    if (is_file($cacheFile) && (time() - filemtime($cacheFile)) < $ttl) {
        $cached = json_decode(@file_get_contents($cacheFile), true);
        if (is_array($cached)) return ['success' => true, 'facets' => $cached, 'cached' => true];
    }

    $adapter = \App\Services\Parsing\AdapterFactory::create('openlane');
    if (!$adapter || !method_exists($adapter, 'fetchFacets')) {
        return ['success' => false, 'error' => 'OpenLane adapter unavailable'];
    }
    $criteria = $make !== '' ? ['brand' => $make] : [];
    $facets = $adapter->fetchFacets($criteria);
    if (!is_array($facets)) {
        // Serve stale cache if the live call failed (cookie expired etc.).
        if (is_file($cacheFile)) {
            $cached = json_decode(@file_get_contents($cacheFile), true);
            if (is_array($cached)) return ['success' => true, 'facets' => $cached, 'stale' => true];
        }
        return ['success' => false, 'error' => 'Nu am putut prelua filtrele OpenLane (cookie expirat?)'];
    }

    if (!is_dir($cacheDir)) @mkdir($cacheDir, 0755, true);
    @file_put_contents($cacheFile, json_encode($facets, JSON_UNESCAPED_UNICODE), LOCK_EX);
    return ['success' => true, 'facets' => $facets];
}

function parsing_fetch_all_photos($db, $prefx, $p) {
    $carId = (int)($p['car_id'] ?? 0);
    $stmt = $db->prepare('SELECT source, source_id FROM '.$prefx.'_parsing_cars WHERE id = ?');
    $stmt->execute([$carId]);
    $row = $stmt->fetch(PDO::FETCH_ASSOC);
    if (!$row) return ['success' => false, 'error' => 'Not found'];

    $adapter = \App\Services\Parsing\AdapterFactory::create($row['source']);
    if (!$adapter) return ['success' => false, 'error' => 'No adapter for ' . $row['source']];

    $data = $adapter->fetchById($row['source_id']);
    if (!$data || empty($data['images'])) {
        return ['success' => false, 'error' => 'Nu am putut prelua pozele'];
    }

    // OpenLane's CDN blocks hotlinking, so the browser can't load these URLs
    // directly (403). Route them through our image proxy with sz=gallery, which
    // serves a medium ~1000px copy — sharp enough for the big slider but much
    // lighter/faster than the full 1920px original. Encar URLs stay direct.
    $images = array_map(function ($u) {
        if (is_string($u) && stripos($u, 'images.openlane.eu') !== false) {
            return '/ajax.php?tp=adm&pg=parsing&action=image_proxy&sz=gallery&url=' . rawurlencode($u);
        }
        return $u;
    }, array_values($data['images']));

    // Return all photos for the big gallery slider (no 20-image cap).
    return ['success' => true, 'images' => $images];
}

function parsing_redownload_images($db, $prefx, $p) {
    $carId = (int)($p['car_id'] ?? 0);
    $stmt = $db->prepare('SELECT source, source_id, raw_data FROM '.$prefx.'_parsing_cars WHERE id = ?');
    $stmt->execute([$carId]);
    $row = $stmt->fetch(PDO::FETCH_ASSOC);
    if (!$row) return ['success' => false, 'error' => 'Not found'];

    $raw = json_decode($row['raw_data'] ?? '{}', true);
    $urls = $raw['images'] ?? [];
    if (empty($urls)) return ['success' => false, 'error' => 'No image URLs in raw_data'];

    $pipeline = new \App\Services\Parsing\ParsingPipeline();
    $saved = $pipeline->downloadImagesPublic($urls, $row['source'], $row['source_id']);

    $stmt = $db->prepare('UPDATE '.$prefx.'_parsing_cars SET images_local = ? WHERE id = ?');
    $stmt->execute([json_encode($saved, JSON_UNESCAPED_UNICODE), $carId]);

    return ['success' => true, 'count' => count($saved)];
}

/**
 * Wipe the parsing catalog EXCEPT cars that are/were published on sauto.md
 * (status published / unavailable). Also resets pagination offsets so the next
 * search/cron starts from the top of the source catalog again.
 */
function parsing_clear_catalog($db, $prefx, $p = []) {
    try {
        // Keep published + unavailable (those live on the Published page).
        $del = $db->prepare("DELETE FROM {$prefx}_parsing_cars WHERE status NOT IN ('published','unavailable')");
        $del->execute();
        $deleted = $del->rowCount();

        // Reset filter pagination so cron re-scans from offset 0.
        $db->exec("UPDATE {$prefx}_parsing_filters SET last_offset = 0");

        // Drop manual-search offsets.
        $db->exec("DELETE FROM {$prefx}_parsing_settings WHERE setting_key LIKE 'offset_%'");

        return ['success' => true, 'deleted' => $deleted];
    } catch (\Throwable $e) {
        return ['success' => false, 'error' => $e->getMessage()];
    }
}

/**
 * Translate Korean Encar trims (car_sub_model still in Hangul) to Latin via Groq,
 * in ONE batch, caching each translation so a given trim is translated only once.
 * Cache lives in parsing_settings under "trimkr_<korean>". Run after imports
 * (cron or button) — cheap, since distinct Korean trims are few and cached.
 */
function parsing_translate_trims($db, $prefx) {
    // Distinct Korean trims still untranslated.
    $rows = $db->query("SELECT DISTINCT car_sub_model FROM {$prefx}_parsing_cars
        WHERE source = 'encar' AND car_sub_model REGEXP '[가-힣]'")->fetchAll(PDO::FETCH_COLUMN);
    if (!$rows) return ['success' => true, 'translated' => 0, 'note' => 'nothing to translate'];

    // Skip ones already cached; build the to-translate list.
    $get = $db->prepare("SELECT setting_value FROM {$prefx}_parsing_settings WHERE setting_key = ?");
    $put = $db->prepare("INSERT INTO {$prefx}_parsing_settings (setting_key, setting_value)
        VALUES (?, ?) ON DUPLICATE KEY UPDATE setting_value = VALUES(setting_value)");
    $cache = [];
    $todo = [];
    foreach ($rows as $kr) {
        $get->execute(['trimkr_' . $kr]);
        $cached = $get->fetchColumn();
        if ($cached !== false && $cached !== '') { $cache[$kr] = $cached; }
        else { $todo[] = $kr; }
    }

    // Translate the uncached ones with Groq (one call, JSON array in/out).
    if ($todo) {
        $groqKey = defined('GROQ_API_KEY') ? GROQ_API_KEY : '';
        if (!empty($groqKey)) {
            $listJson = json_encode(array_values($todo), JSON_UNESCAPED_UNICODE);
            $prompt = "These are Korean car TRIM / grade names (complectație) from Encar. "
                . "Transliterate/translate each into its standard Latin marketing name. "
                . "Translate EVERY Korean word, including multi-word trims "
                . "(e.g. 노블레스→Noblesse, 시그니처→Signature, 그래비티→Gravity, 프레스티지→Prestige, "
                . "인텐스 파노라믹→Intense Panoramic, 캘리그래피→Calligraphy). The result MUST contain no "
                . "Korean characters. Return ONLY a JSON array of strings, SAME length and order. Input:\n" . $listJson;
            $payload = [
                'model' => 'meta-llama/llama-4-scout-17b-16e-instruct',
                'messages' => [
                    ['role' => 'system', 'content' => 'You output only a valid JSON array of strings. No markdown.'],
                    ['role' => 'user', 'content' => $prompt],
                ],
                'temperature' => 0, 'max_tokens' => 1500,
            ];
            $ch = curl_init('https://api.groq.com/openai/v1/chat/completions');
            curl_setopt_array($ch, [
                CURLOPT_RETURNTRANSFER => true, CURLOPT_POST => true,
                CURLOPT_POSTFIELDS => json_encode($payload), CURLOPT_TIMEOUT => 30,
                CURLOPT_HTTPHEADER => ['Authorization: Bearer ' . $groqKey, 'Content-Type: application/json'],
            ]);
            $body = curl_exec($ch);
            $code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            curl_close($ch);
            if ($code === 200 && $body) {
                $content = $resp = json_decode($body, true)['choices'][0]['message']['content'] ?? '';
                $content = trim(preg_replace('/^```(?:json)?|```$/m', '', $content));
                $tr = json_decode($content, true);
                if (is_array($tr) && count($tr) === count($todo)) {
                    foreach ($todo as $i => $kr) {
                        $latin = trim((string)($tr[$i] ?? ''));
                        if ($latin !== '') { $cache[$kr] = $latin; $put->execute(['trimkr_' . $kr, $latin]); }
                    }
                }
            }
        }
    }

    // Apply the cache to every car whose car_sub_model matches a Korean key.
    $applied = 0;
    $upd = $db->prepare("UPDATE {$prefx}_parsing_cars SET car_sub_model = ?
        WHERE source = 'encar' AND car_sub_model = ?");
    foreach ($cache as $kr => $latin) {
        $upd->execute([$latin, $kr]);
        $applied += $upd->rowCount();
    }

    $allTrims = $db->query("SELECT setting_key, setting_value FROM {$prefx}_parsing_settings
        WHERE setting_key LIKE 'trimkr\\_%'")->fetchAll(PDO::FETCH_KEY_PAIR);
    $updTitle = $db->prepare("UPDATE {$prefx}_parsing_cars
        SET title_ro = REPLACE(title_ro, ?, ?)
        WHERE source = 'encar' AND title_ro LIKE CONCAT('%', ?, '%')");
    foreach ($allTrims as $key => $latin) {
        $kr = substr($key, strlen('trimkr_'));
        if ($kr === '' || $latin === '' || $kr === $latin) continue;
        $updTitle->execute([$kr, $latin, $kr]);
    }

    return ['success' => true, 'translated' => count($cache), 'cars_updated' => $applied];
}

function parsing_save_settings($db, $prefx, $userId, $p) {
    $allowed = [
        'cron_frequency_minutes', 'default_target_999', 'default_target_facebook',
        'default_target_telegram', 'notification_email', 'notification_telegram_chat_id',
        'moderation_required', 'libretranslate_url', 'libretranslate_enabled',
        'sync_nocturn_hour'
    ];
    $stmt = $db->prepare('INSERT INTO '.$prefx.'_parsing_settings (setting_key, setting_value, updated_by)
        VALUES (:k, :v, :uid)
        ON DUPLICATE KEY UPDATE setting_value = :v2, updated_by = :uid2');
    foreach ($allowed as $key) {
        if (isset($p[$key])) {
            $stmt->execute([
                ':k' => $key,
                ':v' => (string)$p[$key],
                ':v2' => (string)$p[$key],
                ':uid' => $userId,
                ':uid2' => $userId,
            ]);
        }
    }
    return ['success' => true];
}

// Save the pricing config from /parsing/settings. Inputs:
//   tier_<id>_*   Europe delivery tiers (price_from/price_to/delivery)
//   ctier_<id>_*  shared sauto commission tiers (price_from/price_to/commission)
//   param_<id>_*  Europe cost params (amount/enabled)
//   kr_<id>_*     Korea cost params (amount in EUR/enabled)
// Ids must already exist in the DB (seeded by SQL).
function parsing_save_eu_config($db, $prefx, $userId, $p) {
    $tierStmt = $db->prepare('UPDATE '.$prefx.'_parsing_eu_tiers
        SET price_from = :pf, price_to = :pt, delivery = :dl, updated_by = :uid
        WHERE id = :id');
    $commStmt = $db->prepare('UPDATE '.$prefx.'_parsing_commission_tiers
        SET price_from = :pf, price_to = :pt, commission = :cm, updated_by = :uid
        WHERE id = :id');
    $paramStmt = $db->prepare('UPDATE '.$prefx.'_parsing_eu_params
        SET amount_eur = :amt, enabled = :en, updated_by = :uid
        WHERE id = :id');
    $krStmt = $db->prepare('UPDATE '.$prefx.'_parsing_kr_params
        SET amount_eur = :amt, enabled = :en, updated_by = :uid
        WHERE id = :id');
    $tierInsert = $db->prepare('INSERT INTO '.$prefx.'_parsing_eu_tiers
        (price_from, price_to, delivery, sort_order, updated_by)
        VALUES (:pf, :pt, :dl, :so, :uid)');
    $commInsert = $db->prepare('INSERT INTO '.$prefx.'_parsing_commission_tiers
        (price_from, price_to, commission, sort_order, updated_by)
        VALUES (:pf, :pt, :cm, :so, :uid)');
    $kmStmt = $db->prepare('UPDATE '.$prefx.'_parsing_kr_markup_tiers
        SET price_from = :pf, price_to = :pt, markup = :mk, updated_by = :uid
        WHERE id = :id');
    $kmInsert = $db->prepare('INSERT INTO '.$prefx.'_parsing_kr_markup_tiers
        (price_from, price_to, markup, sort_order, updated_by)
        VALUES (:pf, :pt, :mk, :so, :uid)');

    $tierIds = [];
    $commIds = [];
    $paramIds = [];
    $krIds = [];
    $kmIds = [];
    $tierNew = [];
    $commNew = [];
    $kmNew = [];
    foreach (array_keys($p) as $k) {
        if (preg_match('/^tier_new(\d+)_/', $k, $m)) {
            $tierNew[(int)$m[1]] = true;
        } elseif (preg_match('/^ctier_new(\d+)_/', $k, $m)) {
            $commNew[(int)$m[1]] = true;
        } elseif (preg_match('/^kmtier_new(\d+)_/', $k, $m)) {
            $kmNew[(int)$m[1]] = true;
        } elseif (preg_match('/^tier_(\d+)_/', $k, $m)) {
            $tierIds[(int)$m[1]] = true;
        } elseif (preg_match('/^ctier_(\d+)_/', $k, $m)) {
            $commIds[(int)$m[1]] = true;
        } elseif (preg_match('/^kmtier_(\d+)_/', $k, $m)) {
            $kmIds[(int)$m[1]] = true;
        } elseif (preg_match('/^param_(\d+)_/', $k, $m)) {
            $paramIds[(int)$m[1]] = true;
        } elseif (preg_match('/^kr_(\d+)_/', $k, $m)) {
            $krIds[(int)$m[1]] = true;
        }
    }

    $db->beginTransaction();
    try {
        foreach (array_keys($tierIds) as $id) {
            $to = $p['tier_'.$id.'_price_to'] ?? '';
            $tierStmt->execute([
                ':pf'  => (int)($p['tier_'.$id.'_price_from'] ?? 0),
                ':pt'  => ($to === '' ? null : (int)$to),
                ':dl'  => (int)($p['tier_'.$id.'_delivery'] ?? 0),
                ':uid' => $userId,
                ':id'  => $id,
            ]);
        }
        foreach (array_keys($commIds) as $id) {
            $to = $p['ctier_'.$id.'_price_to'] ?? '';
            $commStmt->execute([
                ':pf'  => (int)($p['ctier_'.$id.'_price_from'] ?? 0),
                ':pt'  => ($to === '' ? null : (int)$to),
                ':cm'  => (int)($p['ctier_'.$id.'_commission'] ?? 0),
                ':uid' => $userId,
                ':id'  => $id,
            ]);
        }
        foreach (array_keys($kmIds) as $id) {
            $to = $p['kmtier_'.$id.'_price_to'] ?? '';
            $kmStmt->execute([
                ':pf'  => (int)($p['kmtier_'.$id.'_price_from'] ?? 0),
                ':pt'  => ($to === '' ? null : (int)$to),
                ':mk'  => (int)($p['kmtier_'.$id.'_markup'] ?? 0),
                ':uid' => $userId,
                ':id'  => $id,
            ]);
        }
        foreach (array_keys($paramIds) as $id) {
            $paramStmt->execute([
                ':amt' => (float)($p['param_'.$id.'_amount'] ?? 0),
                ':en'  => !empty($p['param_'.$id.'_enabled']) ? 1 : 0,
                ':uid' => $userId,
                ':id'  => $id,
            ]);
        }
        foreach (array_keys($krIds) as $id) {
            $krStmt->execute([
                ':amt' => (float)($p['kr_'.$id.'_amount'] ?? 0),
                ':en'  => !empty($p['kr_'.$id.'_enabled']) ? 1 : 0,
                ':uid' => $userId,
                ':id'  => $id,
            ]);
        }
        // Insert new tier rows (added in the UI, names tier_new<N>_*).
        foreach (array_keys($tierNew) as $n) {
            $to = $p['tier_new'.$n.'_price_to'] ?? '';
            $tierInsert->execute([
                ':pf'  => (float)($p['tier_new'.$n.'_price_from'] ?? 0),
                ':pt'  => ($to === '' ? null : (float)$to),
                ':dl'  => (float)($p['tier_new'.$n.'_delivery'] ?? 0),
                ':so'  => 99,
                ':uid' => $userId,
            ]);
        }
        foreach (array_keys($commNew) as $n) {
            $to = $p['ctier_new'.$n.'_price_to'] ?? '';
            $commInsert->execute([
                ':pf'  => (float)($p['ctier_new'.$n.'_price_from'] ?? 0),
                ':pt'  => ($to === '' ? null : (float)$to),
                ':cm'  => (float)($p['ctier_new'.$n.'_commission'] ?? 0),
                ':so'  => 99,
                ':uid' => $userId,
            ]);
        }
        foreach (array_keys($kmNew) as $n) {
            $to = $p['kmtier_new'.$n.'_price_to'] ?? '';
            $kmInsert->execute([
                ':pf'  => (float)($p['kmtier_new'.$n.'_price_from'] ?? 0),
                ':pt'  => ($to === '' ? null : (float)$to),
                ':mk'  => (float)($p['kmtier_new'.$n.'_markup'] ?? 0),
                ':so'  => 99,
                ':uid' => $userId,
            ]);
        }
        // Delete tier rows removed in the UI (ids in tier_delete / ctier_delete).
        $tierDel = array_filter(array_map('intval', (array)($p['tier_delete'] ?? [])));
        if ($tierDel) {
            $in = implode(',', array_fill(0, count($tierDel), '?'));
            $db->prepare('DELETE FROM '.$prefx.'_parsing_eu_tiers WHERE id IN ('.$in.')')->execute(array_values($tierDel));
        }
        $commDel = array_filter(array_map('intval', (array)($p['ctier_delete'] ?? [])));
        if ($commDel) {
            $in = implode(',', array_fill(0, count($commDel), '?'));
            $db->prepare('DELETE FROM '.$prefx.'_parsing_commission_tiers WHERE id IN ('.$in.')')->execute(array_values($commDel));
        }
        $kmDel = array_filter(array_map('intval', (array)($p['kmtier_delete'] ?? [])));
        if ($kmDel) {
            $in = implode(',', array_fill(0, count($kmDel), '?'));
            $db->prepare('DELETE FROM '.$prefx.'_parsing_kr_markup_tiers WHERE id IN ('.$in.')')->execute(array_values($kmDel));
        }
        $db->commit();
    } catch (Exception $e) {
        $db->rollBack();
        return ['success' => false, 'error' => $e->getMessage()];
    }
    return ['success' => true];
}
