<?php
/**
 * Parsing cross-post scheduler. Per active filter, queues the CHEAPEST still-
 * unpublished cars for 999.md / Facebook / Telegram, respecting a per-channel rate
 * limit set in Parsing → Settings ("N cars / H hours", e.g. 999 = 2 / 24h).
 *
 * Rate limit is a SLIDING WINDOW: for each filter+channel we count how many of that
 * filter's cars were actually published on that channel in the last H hours (from
 * the schedule tables' published_at) and only queue up to the remaining budget. So
 * "2 / 24h" means at most 2 in any rolling 24-hour window — the cron can run often
 * without over-posting.
 *
 * Only cars already PUBLISHED on sauto (from that filter) are eligible, ordered by
 * final MD price (car_ctlg.prc) ascending — cheapest first. Fewer eligible than the
 * budget → all of them are queued.
 *
 * We don't post here — we INSERT into the existing schedule tables; the existing
 * crons (scheduled_facebook_posts.php / scheduled_telegram_posts.php /
 * sauto_personal_cron.php) do the real posting within ~5 min, with their own
 * retry / 999-cooldown logic.
 *
 * Run OFTEN (the sliding window enforces the real rate), e.g. every 30 min:
 *   star/30 star star star star cd /home/sautom/public_html && /usr/local/bin/php console/parsing_crosspost_cron.php >> logs/parsing_crosspost.log 2>&1
 *
 * Browser: https://www.sauto.md/console/parsing_crosspost_cron.php?token=cron2026[&dry=1]
 */

$IS_CLI = (php_sapi_name() === 'cli');
if (!$IS_CLI && (($_GET['token'] ?? '') !== 'cron2026')) { http_response_code(403); die('Forbidden'); }
if (!$IS_CLI) header('Content-Type: text/plain; charset=utf-8');

date_default_timezone_set('Europe/Chisinau');
chdir(__DIR__);
define('_DOIT', 1);
define('_DEFAULT', 'content/default');
require_once __DIR__ . '/../environment.php';
require ('../' . _DEFAULT . '/dbi.php');
require_once __DIR__ . '/../App/Services/Parsing/Build999Payload.php';
$prefx = 'gh3sp';

$dryRun = (($_GET['dry'] ?? '') === '1');
$ts = fn() => '[' . date('Y-m-d H:i:s') . ']';

// ── Load per-channel limits from settings (key/value). Defaults if unset. ──
// Self-create the marker column (no migration runner). Flags cars this cron
// cross-posted to 999, so the filter card's "999" button can list them.
try {
    $col = $db->query("SHOW COLUMNS FROM {$prefx}_parsing_cars LIKE 'crosspost_999'");
    if ($col && $col->rowCount() === 0) {
        $db->exec("ALTER TABLE {$prefx}_parsing_cars ADD COLUMN `crosspost_999` TINYINT(1) NOT NULL DEFAULT 0, ADD INDEX (`crosspost_999`)");
    }
} catch (\Throwable $e) { /* best-effort */ }

$settings = [];
foreach ($db->query("SELECT setting_key, setting_value FROM {$prefx}_parsing_settings")->fetchAll(PDO::FETCH_ASSOC) as $r) {
    $settings[$r['setting_key']] = $r['setting_value'];
}
$cfg = [
    '999' => [
        'on'    => ($settings['crosspost_999_enabled'] ?? '0') === '1',
        'count' => max(0, (int)($settings['crosspost_999_count'] ?? 2)),
        'hours' => max(1, (int)($settings['crosspost_999_hours'] ?? 24)),
        'table' => "{$prefx}_sauto_personal_schedules",
        'flag'  => null,
    ],
    'fb' => [
        'on'    => ($settings['crosspost_fb_enabled'] ?? '0') === '1',
        'count' => max(0, (int)($settings['crosspost_fb_count']  ?? 10)),
        'hours' => max(1, (int)($settings['crosspost_fb_hours']  ?? 24)),
        'table' => "{$prefx}_scheduled_facebook_posts",
        'flag'  => 'facebook_published',
    ],
    'tg' => [
        'on'    => ($settings['crosspost_tg_enabled'] ?? '0') === '1',
        'count' => max(0, (int)($settings['crosspost_tg_count']  ?? 10)),
        'hours' => max(1, (int)($settings['crosspost_tg_hours']  ?? 24)),
        'table' => "{$prefx}_scheduled_telegram_posts",
        'flag'  => 'telegram_published',
    ],
];

// GLOBAL per-run caps (across ALL filters) so a run with many filters can't queue
// hundreds of posts at once and trip Facebook/Telegram spam limits. Fixed in code
// (not user-editable) at a safe max of 20 per channel per run; round-robin spreads
// the rest across the next runs.
const MAX_PER_RUN = 20;
$capRun = ['999' => MAX_PER_RUN, 'fb' => MAX_PER_RUN, 'tg' => MAX_PER_RUN];

$onOff = fn($ch) => $cfg[$ch]['on'] ? 'ON' : 'OFF';
echo $ts() . " crosspost start" . ($dryRun ? " (DRY-RUN)" : "")
    . " | 999[".$onOff('999')."]={$cfg['999']['count']}/{$cfg['999']['hours']}h(max {$capRun['999']}/run)"
    . " fb[".$onOff('fb')."]={$cfg['fb']['count']}/{$cfg['fb']['hours']}h(max {$capRun['fb']}/run)"
    . " tg[".$onOff('tg')."]={$cfg['tg']['count']}/{$cfg['tg']['hours']}h(max {$capRun['tg']}/run)\n";

$date = date('Y-m-d');
$time = date('H:i:s');

// Queue a car into a channel's schedule table (published NOW so the channel cron
// picks it up next run) and mark the car_ctlg flag so it isn't queued again.
$queue = function(string $ch, int $carCtlgId, string $catalogType) use ($db, $prefx, $cfg, $date, $time, $dryRun) {
    if ($dryRun) return true;
    $c = $cfg[$ch];

    if ($ch === '999') {
        // Build the 999 features payload server-side (no browser form) and save it to
        // car_ctlg.999 so the personal cron can publish it. If we can't build a valid
        // payload (model not learned yet, commercial spec missing, etc.) SKIP the car
        // — better than a failed "No features data" schedule.
        $builder = new \App\Services\Parsing\Build999Payload($db, $prefx);
        $built = $builder->build($carCtlgId);
        if (!$built) return false;

        $json = json_encode($built['payload'], JSON_UNESCAPED_UNICODE);
        $db->prepare("UPDATE {$prefx}_car_ctlg SET `999` = ?, `999_api_id` = ? WHERE id = ?")
           ->execute([$json, $built['account_id'], $carCtlgId]);

        // AUTO-publish: ONE instant slot only (publish once). Unlike the manual UI —
        // which schedules a monthly renewal for 3 months — auto cars are published a
        // single time and that's it.
        $db->prepare("INSERT INTO {$c['table']}
            (car_id, catalog_type, schedule_date, schedule_time, status) VALUES (?, ?, ?, ?, 'pending')")
           ->execute([$carCtlgId, $catalogType, $date, $time]);
        // Mark so the filter card's "999" button can list these cars.
        try {
            $db->prepare("UPDATE {$prefx}_parsing_cars SET crosspost_999 = 1 WHERE car_ctlg_id = ?")
               ->execute([$carCtlgId]);
        } catch (\Throwable $e) { /* column not created yet — ignored */ }
        return true;
    }

    // FB / TG: a single instant post. Table: scheduled_date/scheduled_time.
    $db->prepare("INSERT INTO {$c['table']}
        (car_id, catalog_type, scheduled_date, scheduled_time, status) VALUES (?, ?, ?, ?, 'pending')")
       ->execute([$carCtlgId, $catalogType, $date, $time]);
    if ($c['flag']) {
        $db->prepare("UPDATE {$prefx}_car_ctlg SET `{$c['flag']}` = 2 WHERE id = ? AND COALESCE(`{$c['flag']}`,0) = 0")
           ->execute([$carCtlgId]);
    }
};

// How many cars of THIS filter "count against" the channel's "N cars / Y hours"
// limit right now = cars PUBLISHED within the last Y hours  +  cars STILL WAITING
// (pending/postponed) in the queue. The cron may add a car only while this total is
// below N. This enforces BOTH the user's rate ("5 / 24h" really means max 5 in any
// 24h window) AND prevents runaway accumulation (waiting cars occupy the quota until
// they publish). DISTINCT so a 999 car's 3 renewal rows count as one car. The 999
// table uses published_at + schedule_date; fb/tg use published_at + scheduled_date.
$publishedInWindow = function(int $filterId, string $ch) use ($db, $prefx, $cfg) {
    $c = $cfg[$ch];
    $hours = (int)$c['hours'];
    $st = $db->prepare("SELECT COUNT(DISTINCT cc.id) FROM {$c['table']} sch
            JOIN {$prefx}_car_ctlg cc ON cc.id = sch.car_id
            JOIN {$prefx}_parsing_cars pc ON pc.car_ctlg_id = cc.id
            WHERE pc.filter_id = ?
              AND (
                    sch.status IN ('pending','postponed')
                 OR (sch.status = 'published' AND sch.published_at >= DATE_SUB(NOW(), INTERVAL {$hours} HOUR))
              )");
    $st->execute([$filterId]);
    return (int)$st->fetchColumn();
};

$filters = $db->query("SELECT id, name FROM {$prefx}_parsing_filters WHERE active = 1 ORDER BY id")->fetchAll(PDO::FETCH_ASSOC);
echo $ts() . " " . count($filters) . " active filter(s)\n";
$totals = ['999' => 0, 'fb' => 0, 'tg' => 0];

// ROUND-ROBIN: resume from the filter AFTER the last one we processed, so with many
// filters the global per-run caps don't always starve the tail of the list. We
// rotate the list to start just past the saved cursor, then remember where we stop.
$cursor = (int)($settings['crosspost_cursor'] ?? 0);
if ($cursor > 0 && count($filters) > 1) {
    $startIdx = 0;
    foreach ($filters as $i => $f) { if ((int)$f['id'] > $cursor) { $startIdx = $i; break; } }
    if ($startIdx > 0) {
        $filters = array_merge(array_slice($filters, $startIdx), array_slice($filters, 0, $startIdx));
    }
}
$lastProcessed = 0;

foreach ($filters as $f) {
    $fid = (int)$f['id'];
    $lastProcessed = $fid;

    // Eligible = published on sauto from this filter, live catalog row, has a price,
    // and NOT sold. "Sold" = source auction already ended: status 'unavailable', OR
    // the auction_end/BatchEndDate in raw_data is in the past. This mirrors the
    // "Vândut" (red) badge on /parsing/published so we only cross-post live cars.
    // Only auction sources (ecarstrade/openlane) carry an end date; Encar has none.
    $auctionEnd = "STR_TO_DATE(REPLACE(REPLACE(COALESCE(
        JSON_UNQUOTE(JSON_EXTRACT(pc.raw_data, '$.auction_end')),
        JSON_UNQUOTE(JSON_EXTRACT(pc.raw_data, '$.BatchEndDate')),
        JSON_UNQUOTE(JSON_EXTRACT(pc.raw_data, '$.raw_data.auction_end')),
        JSON_UNQUOTE(JSON_EXTRACT(pc.raw_data, '$.raw_data.BatchEndDate'))
    ), 'T', ' '), 'Z', ''), '%Y-%m-%d %H:%i:%s')";
    // Auto1 dates its Instant Purchase auctions with auctionEndDatetime — a
    // MILLISECOND epoch number, not the ISO text the others use. Compare it in
    // milliseconds too: FROM_UNIXTIME() would render it in the session timezone
    // while the right-hand side is UTC, which would silently shift the cutoff.
    $auto1End = "COALESCE(
        JSON_EXTRACT(pc.raw_data, '$.auctionEndDatetime'),
        JSON_EXTRACT(pc.raw_data, '$.raw_data.auctionEndDatetime')
    )";
    $base = "FROM {$prefx}_parsing_cars pc
             JOIN {$prefx}_car_ctlg cc ON cc.id = pc.car_ctlg_id
             WHERE pc.filter_id = {$fid} AND pc.status = 'published' AND pc.car_ctlg_id > 0
               AND cc.prc > 0
               AND NOT (
                   pc.source IN ('ecarstrade','openlane')
                   AND {$auctionEnd} IS NOT NULL
                   AND {$auctionEnd} <= UTC_TIMESTAMP()
               )
               AND NOT (
                   pc.source = 'auto1'
                   AND {$auto1End} IS NOT NULL
                   AND {$auto1End} <= UNIX_TIMESTAMP() * 1000
               )";

    $did = ['999' => 0, 'fb' => 0, 'tg' => 0];

    // Per channel: budget = min(filter window budget, global remaining this run).
    // Skipped entirely when the channel's toggle is off.
    // ── 999 ──
    $globalLeft = $capRun['999'] - $totals['999'];
    if ($cfg['999']['on'] && $cfg['999']['count'] > 0 && $globalLeft > 0) {
        $budget = min($cfg['999']['count'] - $publishedInWindow($fid, '999'), $globalLeft);
        if ($budget > 0) {
            // Fetch MORE candidates than the budget: cars whose model isn't learned
            // yet are skipped by the builder, so we walk the cheapest list until we've
            // queued `budget` real ones (or run out).
            $rows = $db->query("SELECT cc.id, cc.catalog_type {$base}
                AND (cc.`999_id` IS NULL OR cc.`999_id` = 0)
                AND NOT EXISTS (SELECT 1 FROM {$prefx}_sauto_personal_schedules s
                                WHERE s.car_id = cc.id AND s.status IN ('pending','postponed'))
                ORDER BY cc.prc ASC LIMIT ".($budget * 5))->fetchAll(PDO::FETCH_ASSOC);
            foreach ($rows as $r) {
                if ($did['999'] >= $budget) break;
                if ($queue('999', (int)$r['id'], (string)$r['catalog_type'])) {
                    $totals['999']++; $did['999']++;
                }
            }
        }
    }

    // ── Facebook ──
    $globalLeft = $capRun['fb'] - $totals['fb'];
    if ($cfg['fb']['on'] && $cfg['fb']['count'] > 0 && $globalLeft > 0) {
        $budget = min($cfg['fb']['count'] - $publishedInWindow($fid, 'fb'), $globalLeft);
        if ($budget > 0) {
            $rows = $db->query("SELECT cc.id, cc.catalog_type {$base}
                AND COALESCE(cc.facebook_published, 0) = 0
                ORDER BY cc.prc ASC LIMIT {$budget}")->fetchAll(PDO::FETCH_ASSOC);
            foreach ($rows as $r) { $queue('fb', (int)$r['id'], (string)$r['catalog_type']); $totals['fb']++; $did['fb']++; }
        }
    }

    // ── Telegram ──
    $globalLeft = $capRun['tg'] - $totals['tg'];
    if ($cfg['tg']['on'] && $cfg['tg']['count'] > 0 && $globalLeft > 0) {
        $budget = min($cfg['tg']['count'] - $publishedInWindow($fid, 'tg'), $globalLeft);
        if ($budget > 0) {
            $rows = $db->query("SELECT cc.id, cc.catalog_type {$base}
                AND COALESCE(cc.telegram_published, 0) = 0
                ORDER BY cc.prc ASC LIMIT {$budget}")->fetchAll(PDO::FETCH_ASSOC);
            foreach ($rows as $r) { $queue('tg', (int)$r['id'], (string)$r['catalog_type']); $totals['tg']++; $did['tg']++; }
        }
    }

    if ($did['999'] || $did['fb'] || $did['tg']) {
        echo $ts() . " filter #{$fid} \"{$f['name']}\": 999={$did['999']} fb={$did['fb']} tg={$did['tg']}\n";
    }

    // Stop early once ALL channels hit their global run cap. Cursor stays on this
    // filter so the next run resumes right after it (round-robin fairness).
    if ($totals['999'] >= $capRun['999'] && $totals['fb'] >= $capRun['fb'] && $totals['tg'] >= $capRun['tg']) {
        echo $ts() . " global per-run caps reached — stopping (cursor=#{$lastProcessed}).\n";
        break;
    }
}

// Save the round-robin cursor so the next run continues after the last filter we
// touched. If we went through the whole list, reset to 0 (start over next time).
if (!$dryRun && $lastProcessed > 0) {
    $save = $db->prepare("INSERT INTO {$prefx}_parsing_settings (setting_key, setting_value)
        VALUES ('crosspost_cursor', ?) ON DUPLICATE KEY UPDATE setting_value = VALUES(setting_value)");
    // If the last processed filter is the highest id (we reached the end), reset to 0.
    $maxId = (int)$db->query("SELECT MAX(id) FROM {$prefx}_parsing_filters WHERE active = 1")->fetchColumn();
    $save->execute([$lastProcessed >= $maxId ? '0' : (string)$lastProcessed]);
}

echo $ts() . " queued totals — 999: {$totals['999']}, fb: {$totals['fb']}, tg: {$totals['tg']}\n";
echo $ts() . " done\n";
