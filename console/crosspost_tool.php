<?php
/**
 * One tool for the auto cross-post pipeline. Pick a mode with ?do=…
 *   test      : audit settings + tables + dry-run per filter (default)
 *   cars      : what cars the crosspost cron queued for 999 in the last 2h + why
 *   cleanup   : delete the 999 test schedules (add &apply=1 to actually delete)
 *   footprint : full disk report — what to delete, what to move to R2, does 100 GB fit
 *   orphans   : delete photo folders whose car no longer exists (dry-run by default)
 *
 *   https://www.sauto.md/console/crosspost_tool.php?token=cron2026&do=test
 */
if (php_sapi_name()!=='cli' && (($_GET['token']??'')!=='cron2026')) { http_response_code(403); die('Forbidden'); }
// From cron there is no query string, so accept "key=value" argv pairs and feed
// them into $_GET — every mode below then works identically either way, and the
// long-running ones (droplocal) escape the LiteSpeed connection timeout.
if (php_sapi_name()==='cli') {
    foreach (array_slice($argv ?? [], 1) as $a) {
        if (strpos($a,'=')!==false) { [$k,$v]=explode('=',$a,2); $_GET[$k]=$v; }
    }
    @set_time_limit(0);
}
define('_DOIT',1); define('_DEFAULT','content/default'); chdir(__DIR__);
// Match the crons' timezone so any schedule rows this tool writes use Chisinau time
// (not the server's UTC), keeping schedule_time consistent with the real crons.
date_default_timezone_set('Europe/Chisinau');
spl_autoload_register(function($c){ $p=__DIR__.'/../'.str_replace('\\',DIRECTORY_SEPARATOR,$c).'.php'; if(is_file($p)) require_once $p; });
require_once __DIR__.'/../environment.php';
require('../'._DEFAULT.'/defines.php'); require('../'._DEFAULT.'/dbi.php');
require_once('../'._DEFAULT.'/functions.php');
$prefx='gh3sp';
\App\Core\Container::set('db',$db); \App\Core\Container::set('prefix',$prefx);
$P="{$prefx}_parsing_cars"; $C="{$prefx}_car_ctlg"; $F="{$prefx}_parsing_filters";
$S999="{$prefx}_sauto_personal_schedules"; $SFB="{$prefx}_scheduled_facebook_posts"; $STG="{$prefx}_scheduled_telegram_posts";
if (php_sapi_name()!=='cli') header('Content-Type: text/plain; charset=utf-8');
function hr($t){ echo "\n==== {$t} ".str_repeat('=',max(3,44-strlen($t)))."\n"; }
$do = $_GET['do'] ?? 'test';
echo "NOW: ".$db->query("SELECT NOW()")->fetchColumn()."   mode: {$do}\n";

$settings=[];
foreach($db->query("SELECT setting_key,setting_value FROM {$prefx}_parsing_settings")->fetchAll(PDO::FETCH_ASSOC) as $r)
    $settings[$r['setting_key']]=$r['setting_value'];
$cfg=[
  '999'=>['on'=>($settings['crosspost_999_enabled']??'0')==='1','count'=>(int)($settings['crosspost_999_count']??2),'hours'=>(int)($settings['crosspost_999_hours']??24),'tbl'=>$S999],
  'fb' =>['on'=>($settings['crosspost_fb_enabled'] ??'0')==='1','count'=>(int)($settings['crosspost_fb_count'] ??10),'hours'=>(int)($settings['crosspost_fb_hours'] ??24),'tbl'=>$SFB],
  'tg' =>['on'=>($settings['crosspost_tg_enabled'] ??'0')==='1','count'=>(int)($settings['crosspost_tg_count'] ??10),'hours'=>(int)($settings['crosspost_tg_hours'] ??24),'tbl'=>$STG],
];

// ─────────────────────────── MODE: test ───────────────────────────
if ($do === 'test') {
    hr("SETTINGS");
    foreach($cfg as $ch=>$c) echo "  {$ch}: [".($c['on']?'ON':'OFF')."] {$c['count']} cars / {$c['hours']}h\n";
    echo "  (real cron caps each run at 20/channel + round-robin; OFF channels skipped)\n";

    hr("SCHEDULE TABLES");
    $need=['999'=>['car_id','catalog_type','schedule_date','schedule_time','status','published_at'],
           'fb'=>['car_id','catalog_type','scheduled_date','scheduled_time','status','published_at'],
           'tg'=>['car_id','catalog_type','scheduled_date','scheduled_time','status','published_at']];
    foreach($cfg as $ch=>$c){
        try{ $cols=array_column($db->query("SHOW COLUMNS FROM {$c['tbl']}")->fetchAll(PDO::FETCH_ASSOC),'Field'); }
        catch(\Throwable $e){ echo "  [{$ch}] {$c['tbl']}: MISSING TABLE\n"; continue; }
        $miss=array_diff($need[$ch],$cols);
        echo "  [{$ch}] {$c['tbl']}: ".($miss?("MISSING: ".implode(',',$miss)):"ok")."\n";
    }

    hr("DRY-RUN per filter (potential; ignores 20/run cap + toggle)");
    $win=function($fid,$tbl,$hours) use($db,$C,$P){
        $st=$db->prepare("SELECT COUNT(DISTINCT cc.id) FROM {$tbl} sch JOIN {$C} cc ON cc.id=sch.car_id
            JOIN {$P} pc ON pc.car_ctlg_id=cc.id WHERE pc.filter_id=? AND sch.status='published'
              AND sch.published_at>=DATE_SUB(NOW(),INTERVAL ? HOUR)");
        $st->execute([$fid,$hours]); return (int)$st->fetchColumn();
    };
    $filters=$db->query("SELECT id,name FROM {$F} WHERE active=1 ORDER BY id")->fetchAll(PDO::FETCH_ASSOC);
    echo "  ".count($filters)." active filter(s)\n";
    foreach($filters as $f){
        $fid=(int)$f['id'];
        $base="FROM {$P} pc JOIN {$C} cc ON cc.id=pc.car_ctlg_id
               WHERE pc.filter_id={$fid} AND pc.status='published' AND pc.car_ctlg_id>0 AND cc.prc>0";
        $pub=(int)$db->query("SELECT COUNT(*) {$base}")->fetchColumn();
        $out=[];
        foreach(['999','fb','tg'] as $ch){
            $b=max(0,$cfg[$ch]['count']-$win($fid,$cfg[$ch]['tbl'],$cfg[$ch]['hours']));
            $flt=$ch==='999' ? "AND (cc.`999_id` IS NULL OR cc.`999_id`=0)"
                : ($ch==='fb' ? "AND COALESCE(cc.facebook_published,0)=0" : "AND COALESCE(cc.telegram_published,0)=0");
            $n=$b>0?(int)$db->query("SELECT COUNT(*) FROM (SELECT cc.id {$base} {$flt} ORDER BY cc.prc ASC LIMIT {$b}) x")->fetchColumn():0;
            $out[]="{$ch}={$n}";
        }
        if($pub>0) echo "  #{$fid} \"{$f['name']}\": onSauto={$pub} | ".implode(' ',$out)."\n";
    }

    hr("PUBLISHING CRONS ALIVE?");
    foreach($cfg as $ch=>$c){
        $last=$db->query("SELECT MAX(published_at) FROM {$c['tbl']} WHERE status='published'")->fetchColumn();
        $pend=$db->query("SELECT COUNT(*) FROM {$c['tbl']} WHERE status='pending'")->fetchColumn();
        echo "  [{$ch}] last published: ".($last?:'NEVER')." | pending: {$pend}\n";
    }
    echo "\nDone.\n"; exit;
}

// ─────────────────────────── MODE: cars ───────────────────────────
if ($do === 'cars') {
    hr("999 schedules created in last 2h (from crosspost cron)");
    foreach($db->query("SELECT status,COUNT(*) c FROM {$S999} WHERE created_at>=DATE_SUB(NOW(),INTERVAL 2 HOUR) GROUP BY status")->fetchAll(PDO::FETCH_ASSOC) as $r)
        echo "  {$r['status']}: {$r['c']}\n";

    hr("cars (car + filter + state), last 2h");
    $rows=$db->query("SELECT s.status,s.schedule_date,s.schedule_time,cc.id ctlg,cc.br_nm,cc.mo_nm,cc.yr,cc.prc,cc.`999_id`,
            pc.filter_id,f.name fname,pc.source
        FROM {$S999} s JOIN {$C} cc ON cc.id=s.car_id
        LEFT JOIN {$P} pc ON pc.car_ctlg_id=cc.id LEFT JOIN {$F} f ON f.id=pc.filter_id
        WHERE s.created_at>=DATE_SUB(NOW(),INTERVAL 2 HOUR)
        ORDER BY pc.filter_id, cc.prc ASC")->fetchAll(PDO::FETCH_ASSOC);
    if(!$rows) echo "  (none)\n";
    $cur=null;
    foreach($rows as $r){
        if($r['filter_id']!==$cur){ $cur=$r['filter_id']; echo "\n  Filter #{$r['filter_id']} \"{$r['fname']}\":\n"; }
        $live=$r['999_id']?"999_id={$r['999_id']} (LIVE)":"not on 999";
        echo "    #{$r['ctlg']} {$r['br_nm']} {$r['mo_nm']} {$r['yr']} — {$r['prc']}€ [{$r['source']}]  {$r['status']} @ {$r['schedule_date']} {$r['schedule_time']}  {$live}\n";
    }

    hr("why FAILED? (error messages)");
    $cols=array_column($db->query("SHOW COLUMNS FROM {$S999}")->fetchAll(PDO::FETCH_ASSOC),'Field');
    $ec=in_array('error_message',$cols,true)?'error_message':(in_array('error',$cols,true)?'error':null);
    if($ec){
        foreach($db->query("SELECT {$ec} err,COUNT(*) c FROM {$S999} WHERE status='failed'
            AND created_at>=DATE_SUB(NOW(),INTERVAL 2 HOUR) GROUP BY {$ec} ORDER BY c DESC LIMIT 10")->fetchAll(PDO::FETCH_ASSOC) as $r)
            echo "  [{$r['c']}x] ".substr((string)$r['err'],0,160)."\n";
    }

    hr("999 account cooldowns");
    foreach($db->query("SELECT name,value FROM {$prefx}_settings WHERE name LIKE '999md_upload_cooldown_%'")->fetchAll(PDO::FETCH_ASSOC) as $r){
        $acc=str_replace('999md_upload_cooldown_','',$r['name']); $u=(int)$r['value'];
        echo "  account {$acc}: ".($u>time()?("ON COOLDOWN until ".date('Y-m-d H:i:s',$u)):"ok")."\n";
    }
    echo "\nDone.\n"; exit;
}

// ─────────────────────────── MODE: feat103 ───────────────────────────
// What does 999 expect for feature 103 (engine cc) on commercials (subcat 660)?
// 1) Show the value stored in payloads of commercials ALREADY LIVE on 999 (so we
//    copy a proven-good value). 2) Ask the 999 API for the option list of feature
//    103 (if it's an option field, not free number).
if ($do === 'feat103') {
    hr("feature 103 values in LIVE commercial payloads");
    $rows = $db->query("SELECT cc.id, cc.mo_nm, cc.fl, cc.vol, cc.`999` payload
        FROM {$C} cc
        WHERE cc.gr='com' AND cc.`999_id` > 0 AND cc.`999` IS NOT NULL AND cc.`999` <> ''
        ORDER BY cc.id DESC LIMIT 30")->fetchAll(PDO::FETCH_ASSOC);
    $seen = [];
    foreach ($rows as $r) {
        $d = json_decode((string)$r['payload'], true);
        $v103 = '(absent)';
        if (is_array($d) && !empty($d['features'])) {
            foreach ($d['features'] as $f) if ((string)($f['id'] ?? '')==='103') { $v103 = (string)($f['value'] ?? ''); break; }
        }
        echo "  #{$r['id']} {$r['mo_nm']} fl={$r['fl']} vol={$r['vol']}  → 103='{$v103}'\n";
        if ($v103 !== '(absent)') $seen[$v103] = ($seen[$v103] ?? 0) + 1;
    }
    if ($seen) { echo "  distinct 103 values seen: "; foreach ($seen as $v=>$c) echo "'{$v}'({$c}) "; echo "\n"; }
    if (!$rows) echo "  (no live commercial payloads)\n";

    hr("999 API option list for feature 103 (subcat 660)");
    try {
        $svc = new \App\Services\Api999Service();
        // feature 103 depends on subcategory; try as a plain subcategory feature.
        $res = $svc->getSubcategoryFeatures(660);
        $found = false;
        foreach (($res['features'] ?? $res['Features'] ?? []) as $f) {
            $fid = (string)($f['id'] ?? $f['feature_id'] ?? '');
            if ($fid !== '103') continue;
            $found = true;
            echo "  feature 103 type: ".json_encode($f['type'] ?? $f['input_type'] ?? '?', JSON_UNESCAPED_UNICODE)."\n";
            $opts = $f['options'] ?? $f['Options'] ?? [];
            echo "  options: ".count($opts)."\n";
            foreach (array_slice($opts, 0, 20) as $o)
                echo "    id=".($o['id'] ?? $o['value'] ?? '?')." '".($o['title'] ?? $o['text'] ?? '')."'\n";
        }
        if (!$found) echo "  (feature 103 not in subcategory feature list — may be a free number with a min)\n";
    } catch (\Throwable $e) { echo "  API error: ".$e->getMessage()."\n"; }
    echo "\nDone.\n"; exit;
}

// ─────────────────────────── MODE: fix999slots ───────────────────────────
// Repair cars whose 999 schedule was left with ONLY future renewal slots (+30/+60
// days) after a failed/cleared instant slot — they wouldn't publish until next
// month. Now that auto-publish uses a single slot, we: (1) for each parsing car that
// has pending 999 rows but NONE due today-or-earlier, collapse to a single slot due
// now; (2) drop the extra future renewal rows. Dry-run unless &apply=1.
if ($do === 'fix999slots') {
    $apply = ($_GET['apply'] ?? '') === '1';
    hr($apply ? "FIX 999 slots (collapse to one due-now slot)" : "DRY-RUN fix999slots (add &apply=1)");

    // Parsing cars that have pending 999 rows.
    $cars = $db->query("SELECT DISTINCT s.car_id
        FROM {$S999} s JOIN {$P} pc ON pc.car_ctlg_id = s.car_id
        WHERE s.status = 'pending' AND pc.filter_id IS NOT NULL
          AND NOT EXISTS (SELECT 1 FROM {$C} cc WHERE cc.id = s.car_id AND cc.`999_id` > 0)")
        ->fetchAll(PDO::FETCH_COLUMN);

    $needFix = []; $okDue = 0;
    foreach ($cars as $cid) {
        $cid = (int)$cid;
        $due = (int)$db->query("SELECT COUNT(*) FROM {$S999}
            WHERE car_id={$cid} AND status='pending'
              AND CONCAT(schedule_date,' ',schedule_time) <= NOW()")->fetchColumn();
        if ($due > 0) { $okDue++; continue; }   // already has a slot due now — fine
        $needFix[] = $cid;
    }
    echo "  parsing cars with pending 999: ".count($cars)."\n";
    echo "  already have a due-now slot: {$okDue}\n";
    echo "  need fix (only future slots, won't publish now): ".count($needFix)."\n";

    if (!$apply) {
        if ($needFix) echo "  sample: ".implode(', ', array_slice($needFix, 0, 15))."\n";
        echo "\n  add &apply=1 to give each a single due-now slot.\n\nDone.\n"; exit;
    }
    $date = date('Y-m-d'); $time = date('H:i:s');
    $fixed = 0;
    foreach ($needFix as $cid) {
        // Read the car's catalog_type from any existing row (keep it consistent).
        $ct = (string)$db->query("SELECT catalog_type FROM {$S999} WHERE car_id={$cid} LIMIT 1")->fetchColumn();
        // Delete the future pending rows, insert ONE due now.
        $db->prepare("DELETE FROM {$S999} WHERE car_id=? AND status='pending'")->execute([$cid]);
        $db->prepare("INSERT INTO {$S999} (car_id, catalog_type, schedule_date, schedule_time, status)
            VALUES (?,?,?,?,'pending')")->execute([$cid, $ct, $date, $time]);
        $fixed++;
    }
    echo "\n  fixed {$fixed} car(s): each now has one slot due now.\n";
    echo "\nDone.\n"; exit;
}

// ─────────────────────────── MODE: pendingpayload ───────────────────────────
// Check whether the PENDING 999 cars' saved payload (car_ctlg.999) already has
// feature 7. Payloads built before the fix lack it and will FAIL when the cron
// publishes them. &rebuild=1 rebuilds those payloads in place with Build999Payload
// (adds feature 7) so they publish correctly without waiting for re-queue.
if ($do === 'pendingpayload') {
    require_once __DIR__.'/../App/Services/Parsing/Build999Payload.php';
    $rebuild = ($_GET['rebuild'] ?? '') === '1';
    hr("pending 999 cars — does saved payload have feature 7?");
    $rows = $db->query("SELECT DISTINCT cc.id, cc.br_nm, cc.mo_nm, cc.gr, cc.`999` payload
        FROM {$S999} s JOIN {$C} cc ON cc.id = s.car_id JOIN {$P} pc ON pc.car_ctlg_id = cc.id
        WHERE s.status IN ('pending','postponed') AND pc.filter_id IS NOT NULL
          AND cc.`999` IS NOT NULL AND cc.`999` <> ''")->fetchAll(PDO::FETCH_ASSOC);
    $with7 = 0; $without7 = 0; $toFix = [];
    foreach ($rows as $r) {
        $d = json_decode((string)$r['payload'], true);
        $has = false;
        if (is_array($d) && !empty($d['features'])) {
            foreach ($d['features'] as $f) if ((string)($f['id'] ?? '') === '7') { $has = true; break; }
        }
        if ($has) $with7++; else { $without7++; $toFix[] = (int)$r['id']; }
    }
    echo "  pending cars with payload: ".count($rows)."\n";
    echo "  WITH feature 7: {$with7}\n";
    echo "  WITHOUT feature 7 (will FAIL as-is): {$without7}\n";

    if ($rebuild && $toFix) {
        hr("REBUILD payloads (adds feature 7)");
        $builder = new \App\Services\Parsing\Build999Payload($db, $prefx);
        $ok = 0; $skip = 0;
        foreach ($toFix as $cid) {
            $built = $builder->build($cid);
            if ($built) {
                $json = json_encode($built['payload'], JSON_UNESCAPED_UNICODE);
                $db->prepare("UPDATE {$C} SET `999` = ?, `999_api_id` = ? WHERE id = ?")
                   ->execute([$json, $built['account_id'], $cid]);
                $ok++;
            } else { $skip++; }
        }
        echo "  rebuilt: {$ok}   skipped (couldn't build): {$skip}\n";
    } elseif ($toFix) {
        echo "\n  (add &rebuild=1 to rebuild these payloads now so they publish OK)\n";
    }
    echo "\nDone.\n"; exit;
}

// ─────────────────────────── MODE: filtercheck ───────────────────────────
// For a set of filters, show the cheapest-first list of eligible cars (published on
// sauto, active) with their MD price, and mark which have been published on 999/FB/
// TG. Confirms the cron picks the cheapest and advances through the list. By default
// shows the first 2 + last 3 active filters. &fids=62,63 to pick specific ones.
if ($do === 'filtercheck') {
    // Which filters.
    if (!empty($_GET['fids'])) {
        $fids = array_values(array_filter(array_map('intval', explode(',', $_GET['fids']))));
    } else {
        $all = $db->query("SELECT id FROM {$F} WHERE active=1 ORDER BY id")->fetchAll(PDO::FETCH_COLUMN);
        $fids = array_merge(array_slice($all, 0, 2), array_slice($all, -3));
        $fids = array_values(array_unique(array_map('intval', $fids)));
    }

    foreach ($fids as $fid) {
        $fname = $db->query("SELECT name FROM {$F} WHERE id={$fid}")->fetchColumn();
        hr("filter #{$fid} \"{$fname}\" — cheapest-first eligible cars");
        // Same eligibility as the cron: published on sauto, priced. Cheapest first.
        // No correlated subquery (that timed out) — 999_id/fb/tg flags are enough.
        $st = $db->prepare("SELECT cc.id, cc.br_nm, cc.mo_nm, cc.yr, cc.prc,
                COALESCE(cc.`999_id`,0) AS n999,
                COALESCE(cc.facebook_published,0) AS fb,
                COALESCE(cc.telegram_published,0) AS tg
            FROM {$P} pc JOIN {$C} cc ON cc.id=pc.car_ctlg_id
            WHERE pc.filter_id=? AND pc.status='published' AND pc.car_ctlg_id>0 AND cc.prc>0
            ORDER BY cc.prc ASC LIMIT 25");
        $st->execute([$fid]);
        $rows = $st->fetchAll(PDO::FETCH_ASSOC);
        if (!$rows) { echo "  (no eligible cars)\n"; continue; }
        $i = 0;
        foreach ($rows as $r) {
            $i++;
            $m999 = $r['n999'] > 0 ? '999✓' : '999·';
            $mfb  = $r['fb'] == 1 ? 'FB✓' : ($r['fb'] == 2 ? 'FB⏳' : 'FB·');
            $mtg  = $r['tg'] == 1 ? 'TG✓' : ($r['tg'] == 2 ? 'TG⏳' : 'TG·');
            echo "  ".str_pad($i,2).". #".str_pad($r['id'],6)." ".str_pad(substr($r['br_nm'].' '.$r['mo_nm'],0,24),24)
                ." {$r['yr']}  ".str_pad($r['prc'].'€',8)."  {$m999} {$mfb} {$mtg}\n";
        }
        echo "  legend: 999✓/FB✓/TG✓=published  FB⏳/TG⏳=queued  ·=not yet   (order = MD price asc)\n";
    }
    echo "\nDone.\n"; exit;
}

// ─────────────────────────── MODE: rebuildall ───────────────────────────
// Rebuild the saved 999 payload for EVERY parsing car that still has a pending/
// postponed/failed 999 row, using the current Build999Payload (now with unit on
// 104/107/2). Also clears failed rows so they retry. Use after a payload-format fix.
if ($do === 'rebuildall') {
    require_once __DIR__.'/../App/Services/Parsing/Build999Payload.php';
    $apply = ($_GET['apply'] ?? '') === '1';
    hr($apply ? "REBUILD ALL pending/failed 999 payloads" : "DRY-RUN rebuildall (add &apply=1)");
    $cars = $db->query("SELECT DISTINCT cc.id
        FROM {$S999} s JOIN {$C} cc ON cc.id = s.car_id JOIN {$P} pc ON pc.car_ctlg_id = cc.id
        WHERE s.status IN ('pending','postponed','failed') AND pc.filter_id IS NOT NULL
          AND (cc.`999_id` IS NULL OR cc.`999_id` = 0)")->fetchAll(PDO::FETCH_COLUMN);
    echo "  cars to rebuild: ".count($cars)."\n";
    if (!$apply) { echo "\n  add &apply=1 to rebuild + clear failed.\n\nDone.\n"; exit; }
    $builder = new \App\Services\Parsing\Build999Payload($db, $prefx);
    $ok = 0; $skip = 0;
    foreach ($cars as $cid) {
        $cid = (int)$cid;
        $built = $builder->build($cid);
        if ($built) {
            $db->prepare("UPDATE {$C} SET `999`=?, `999_api_id`=? WHERE id=?")
               ->execute([json_encode($built['payload'], JSON_UNESCAPED_UNICODE), $built['account_id'], $cid]);
            // clear failed rows, ensure one due-now pending exists
            $db->prepare("DELETE FROM {$S999} WHERE car_id=? AND status='failed'")->execute([$cid]);
            $ok++;
        } else { $skip++; }
    }
    echo "  rebuilt: {$ok}   skipped: {$skip}\n";
    echo "\nDone.\n"; exit;
}

// ─────────────────────────── MODE: cmppayload ───────────────────────────
// Compare the feature-id SET of a car published SUCCESSFULLY on 999 (any live ad,
// subcat 659) vs what the auto payload sends. Shows which feature ids the working
// ads have that the auto payload lacks — the missing required field.
if ($do === 'cmppayload') {
    // A known-good live 659 payload (most recent published parsing car with 999_id).
    $good = $db->query("SELECT id, `999` FROM {$C}
        WHERE `999_id` > 0 AND `999` LIKE '%\"subcategory_id\":\"659\"%'
        ORDER BY id DESC LIMIT 1")->fetch(PDO::FETCH_ASSOC);
    // The failing auto car.
    $badId = (int)($_GET['id'] ?? 34215);
    $bad = $db->query("SELECT `999` FROM {$C} WHERE id={$badId}")->fetch(PDO::FETCH_ASSOC);

    $ids = function($json) {
        $d = json_decode((string)$json, true); $out = [];
        foreach (($d['features'] ?? []) as $f) $out[(string)($f['id'] ?? '')] = true;
        return $out;
    };
    $g = $good ? $ids($good['999']) : [];
    $b = $bad ? $ids($bad['999']) : [];

    hr("known-GOOD live 659 payload: car #".($good['id'] ?? '?'));
    echo "  feature ids: ".implode(',', array_keys($g))."\n";
    hr("failing AUTO car #{$badId}");
    echo "  feature ids: ".implode(',', array_keys($b))."\n";
    hr("DIFF");
    $inGoodNotBad = array_diff(array_keys($g), array_keys($b));
    $inBadNotGood = array_diff(array_keys($b), array_keys($g));
    echo "  GOOD has but AUTO lacks: ".($inGoodNotBad ? implode(',', $inGoodNotBad) : '(none)')."\n";
    echo "  AUTO has but GOOD lacks: ".($inBadNotGood ? implode(',', $inBadNotGood) : '(none)')."\n";
    // Also show good car's feature 104 + surrounding.
    if ($good) {
        $gd = json_decode((string)$good['999'], true);
        echo "\n  GOOD car #{$good['id']} full features:\n  ".json_encode($gd['features'] ?? [], JSON_UNESCAPED_UNICODE)."\n";
    }
    echo "\nDone.\n"; exit;
}

// ─────────────────────────── MODE: reqfeatures ───────────────────────────
// Ask the 999 API which features subcategory 659 REQUIRES, then compare against the
// features an auto payload sends (from a car's car_ctlg.999). Reveals a required
// field the auto payload omits — the real reason 999 rejects it (blaming 104).
if ($do === 'reqfeatures') {
    $id = (int)($_GET['id'] ?? 0);
    $svc = new \App\Services\Api999Service();
    hr("999 required features for subcategory 659");
    $required = [];
    try {
        $res = $svc->getSubcategoryFeatures(658, 659, 23844);
        $feats = $res['features'] ?? $res['Features'] ?? $res['data'] ?? [];
        if (!$feats && is_array($res)) $feats = $res;
        foreach ($feats as $f) {
            $fid = (string)($f['id'] ?? $f['feature_id'] ?? '');
            $req = $f['required'] ?? $f['is_required'] ?? $f['mandatory'] ?? null;
            if ($fid === '') continue;
            $isReq = ($req === true || $req === 1 || $req === '1');
            if ($isReq) $required[] = $fid;
            echo "    feature ".str_pad($fid,6)." required=".var_export($req,true)." '".($f['title'] ?? $f['name'] ?? '')."'\n";
        }
    } catch (\Throwable $e) { echo "  API error: ".$e->getMessage()."\n"; echo "  (getSubcategoryFeatures signature may differ — tell me and I'll adjust)\n"; }
    echo "\n  required feature ids: ".implode(',', $required)."\n";

    if ($id > 0) {
        hr("features the AUTO payload sends for car #{$id}");
        $pl = $db->query("SELECT `999` FROM {$C} WHERE id={$id}")->fetchColumn();
        $d = json_decode((string)$pl, true);
        $sent = [];
        foreach (($d['features'] ?? []) as $f) $sent[] = (string)($f['id'] ?? '');
        echo "  sends: ".implode(',', $sent)."\n";
        $missing = array_diff($required, $sent);
        echo "  REQUIRED BUT MISSING: ".($missing ? implode(',', $missing) : '(none)')."\n";
    }
    echo "\nDone.\n"; exit;
}

// ─────────────────────────── MODE: updatelog ───────────────────────────
// Show the last N lines of the UPDATE-path log — cars that already have a 999_id
// and go through updateAdvert (not fresh publish). The 104 failures come from here.
if ($do === 'updatelog') {
    $f = __DIR__.'/../logs/999_update.log';
    $n = (int)($_GET['n'] ?? 5);
    hr("last {$n} UPDATE-path payloads");
    if (!is_file($f)) { echo "  (no update-path log yet)\n\nDone.\n"; exit; }
    $lines = @file($f, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) ?: [];
    foreach (array_slice($lines, -$n) as $l) echo "  ".$l."\n\n";
    echo "\nDone.\n"; exit;
}

// ─────────────────────────── MODE: faillog ───────────────────────────
// Show the last N lines of the FAILURE log (payload + error for cars that 999
// actually rejected — the ones we need to diagnose).
if ($do === 'faillog') {
    $f = __DIR__.'/../logs/999_fail.log';
    $n = (int)($_GET['n'] ?? 5);
    hr("last {$n} FAILED publishes (payload + error)");
    if (!is_file($f)) { echo "  (no failures logged yet — wait for cron to hit a failing car)\n\nDone.\n"; exit; }
    $lines = @file($f, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) ?: [];
    foreach (array_slice($lines, -$n) as $l) echo "  ".$l."\n\n";
    echo "\nDone.\n"; exit;
}

// ─────────────────────────── MODE: debuglog ───────────────────────────
// Show the last N lines of the temp 999 debug log (exact payload sent to setAdvert).
if ($do === 'debuglog') {
    $f = __DIR__.'/../logs/999_debug.log';
    $n = (int)($_GET['n'] ?? 10);
    hr("last {$n} lines of 999_debug.log");
    if (!is_file($f)) { echo "  (no log yet — cron hasn't hit a normal-car publish since debug added)\n\nDone.\n"; exit; }
    $lines = @file($f, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) ?: [];
    foreach (array_slice($lines, -$n) as $l) echo "  ".$l."\n\n";
    echo "\nDone.\n"; exit;
}

// ─────────────────────────── MODE: showpayload ───────────────────────────
// Dump the exact feature 104 + 19 + 4 + 5 values from a car's SAVED payload
// (car_ctlg.999), to see why 999 rejects feature 104. Usage: ?do=showpayload&id=
if ($do === 'showpayload') {
    $id = (int)($_GET['id'] ?? 0);
    if ($id <= 0) { echo "  pass &id=\n\nDone.\n"; exit; }
    $r = $db->query("SELECT br_nm, mo_nm, mlg, yr, `999` FROM {$C} WHERE id={$id}")->fetch(PDO::FETCH_ASSOC);
    hr("car #{$id} {$r['br_nm']} {$r['mo_nm']}  DB.mlg={$r['mlg']} DB.yr={$r['yr']}");
    $d = json_decode((string)$r['999'], true);
    if (!is_array($d) || empty($d['features'])) { echo "  no payload\n\nDone.\n"; exit; }
    echo "  subcategory=".($d['subcategory_id'] ?? '?')."\n";
    foreach ($d['features'] as $f) {
        $fid = (string)($f['id'] ?? '');
        if (in_array($fid, ['104','19','4','5','107','2'], true))
            echo "    feature {$fid} = '".($f['value'] ?? '')."'".(isset($f['unit'])?" unit={$f['unit']}":"")."\n";
    }
    // Count how many features total + whether 104 present.
    $has104 = false; foreach ($d['features'] as $f) if ((string)($f['id']??'')==='104') $has104=true;
    echo "  total features: ".count($d['features'])."   feature 104 present: ".($has104?'YES':'NO')."\n";
    echo "\nDone.\n"; exit;
}

// ─────────────────────────── MODE: km0 ───────────────────────────
// Find the failed 999 cars with feature 104 (km) empty: show their real car_ctlg.mlg
// and what the saved payload sent for 104. Tells us if it's stale payload (rebuild)
// or genuinely missing km in the DB (skip/fix source). &rebuild=1 rebuilds payloads.
if ($do === 'km0') {
    require_once __DIR__.'/../App/Services/Parsing/Build999Payload.php';
    $rebuild = ($_GET['rebuild'] ?? '') === '1';
    $cols = array_column($db->query("SHOW COLUMNS FROM {$S999}")->fetchAll(PDO::FETCH_ASSOC), 'Field');
    $ec = in_array('error_message', $cols, true) ? 'error_message' : 'error';
    hr("failed cars with feature 104 (km) error");
    $rows = $db->query("SELECT DISTINCT cc.id, cc.br_nm, cc.mo_nm, cc.mlg, cc.`999` payload
        FROM {$S999} s JOIN {$C} cc ON cc.id=s.car_id JOIN {$P} pc ON pc.car_ctlg_id=cc.id
        WHERE s.status='failed' AND pc.filter_id IS NOT NULL AND s.{$ec} LIKE '%104%'")->fetchAll(PDO::FETCH_ASSOC);
    $toRebuild = [];
    foreach ($rows as $r) {
        $v104 = '(absent)';
        $d = json_decode((string)$r['payload'], true);
        if (is_array($d) && !empty($d['features'])) foreach ($d['features'] as $f) if ((string)($f['id']??'')==='104') { $v104="'".($f['value']??'')."'"; break; }
        $note = ((int)$r['mlg'] > 0) ? "DB has km={$r['mlg']} → stale payload, rebuild" : "DB mlg=0 → real missing km";
        echo "  #{$r['id']} {$r['br_nm']} {$r['mo_nm']}  DB.mlg={$r['mlg']}  payload104={$v104}  → {$note}\n";
        if ((int)$r['mlg'] > 0) $toRebuild[] = (int)$r['id'];
    }
    if (!$rows) echo "  (none)\n";
    if ($rebuild && $toRebuild) {
        hr("REBUILD payloads for cars with real km");
        $builder = new \App\Services\Parsing\Build999Payload($db, $prefx);
        $ok = 0;
        foreach ($toRebuild as $cid) {
            $built = $builder->build($cid);
            if ($built) {
                $db->prepare("UPDATE {$C} SET `999`=?, `999_api_id`=? WHERE id=?")
                   ->execute([json_encode($built['payload'], JSON_UNESCAPED_UNICODE), $built['account_id'], $cid]);
                // clear the failed row so it retries
                $db->prepare("DELETE FROM {$S999} WHERE car_id=? AND status='failed'")->execute([$cid]);
                $db->prepare("INSERT INTO {$S999} (car_id,catalog_type,schedule_date,schedule_time,status)
                    SELECT ?, 'on_order', CURDATE(), CURTIME(), 'pending'")->execute([$cid]);
                $ok++;
            }
        }
        echo "  rebuilt + re-queued: {$ok}\n";
    } elseif ($toRebuild) {
        echo "\n  (add &rebuild=1 to rebuild these payloads and re-queue)\n";
    }
    echo "\nDone.\n"; exit;
}

// ─────────────────────────── MODE: feat7 ───────────────────────────
// What value do successful NORMAL-car (subcat 659) 999 ads use for feature 7 and
// 104? Reads live payloads to copy proven-good constants. Build999Payload is missing
// feature 7 for normal cars — this shows what to add.
if ($do === 'feat7') {
    hr("feature 7 + 104 in LIVE normal-car (659) payloads");
    $rows = $db->query("SELECT cc.id, cc.mo_nm, cc.mlg, cc.`999` payload
        FROM {$C} cc
        WHERE cc.gr <> 'com' AND cc.`999_id` > 0 AND cc.`999` IS NOT NULL AND cc.`999` <> ''
        ORDER BY cc.id DESC LIMIT 40")->fetchAll(PDO::FETCH_ASSOC);
    $seen7 = []; $has104 = 0; $no104 = 0;
    foreach ($rows as $r) {
        $d = json_decode((string)$r['payload'], true);
        if (!is_array($d) || empty($d['features'])) continue;
        // Only subcat 659 (normal cars).
        if ((string)($d['subcategory_id'] ?? '') !== '659') continue;
        $v7 = '(absent)'; $v104 = '(absent)';
        foreach ($d['features'] as $f) {
            if ((string)($f['id'] ?? '') === '7')   $v7   = (string)($f['value'] ?? '');
            if ((string)($f['id'] ?? '') === '104') $v104 = (string)($f['value'] ?? '');
        }
        if ($v7 !== '(absent)') $seen7[$v7] = ($seen7[$v7] ?? 0) + 1;
        if ($v104 !== '(absent)' && $v104 !== '' && $v104 !== '0') $has104++; else $no104++;
    }
    echo "  feature 7 values seen: ";
    if ($seen7) { foreach ($seen7 as $v => $c) echo "'{$v}'({$c}) "; } else echo "NONE — 7 absent from all normal ads too!";
    echo "\n";
    echo "  feature 104 (km): {$has104} ads have a value, {$no104} empty/zero\n";
    echo "\nDone.\n"; exit;
}

// ─────────────────────────── MODE: brands999 ───────────────────────────
// Which brands of the cars in our filters have NO entry in Build999Payload::brandId().
// Those cars are skipped silently by the cross-post cron — build() returns null, so no
// schedule row is ever created and the car simply never appears on 999. For each miss
// the live 999 brand list is searched so the line can be pasted into the map. Read-only.
if ($do === 'brands999') {
    hr("brands used by filter cars, missing from the 999 map");
    $rows = $db->query("SELECT cc.br, cc.br_nm, COUNT(*) n, SUM(cc.`999_id` > 0) live999
        FROM {$P} pc JOIN {$C} cc ON cc.id = pc.car_ctlg_id
        WHERE pc.filter_id IS NOT NULL AND cc.br <> ''
        GROUP BY cc.br, cc.br_nm ORDER BY n DESC")->fetchAll(PDO::FETCH_ASSOC);

    $missing = [];
    foreach ($rows as $r) {
        if (\App\Services\Parsing\Build999Payload::brandId((string)$r['br']) === null) $missing[] = $r;
    }
    if (!$missing) { echo "  every brand is mapped\n"; echo "\nDone.\n"; exit; }

    // Brands are the options of feature 20 in the car subcategory (658/659/23844) —
    // the same list the manual form fills its brand select from.
    // Shape is features_groups[].features[].options[], with lowercase id/title — the
    // same structure the manual 999 form renders its selects from.
    $opts = [];
    try {
        $svc = new \App\Services\Api999Service();
        foreach ($svc->getSubcategoryFeatures(658, 659, 23844)['features_groups'] ?? [] as $g) {
            foreach ($g['features'] ?? [] as $f) {
                if ((string)($f['id'] ?? '') === '20') { $opts = $f['options'] ?? []; break 2; }
            }
        }
        if (!$opts) echo "  (feature 20 came back without options — check the API answer)\n";
    } catch (\Throwable $e) { echo "  (could not read the 999 brand list: ".$e->getMessage().")\n"; }

    foreach ($missing as $r) {
        $norm = fn($s) => preg_replace('/[^a-z0-9]/', '', mb_strtolower(trim((string)$s), 'UTF-8'));
        $want = $norm(str_replace('_', ' ', (string)$r['br']));
        $wantNm = $norm($r['br_nm']);
        $hit = null; $near = [];
        foreach ($opts as $o) {
            $name = $norm($o['title'] ?? '');
            if ($name === $want || $name === $wantNm) { $hit = $o; break; }
            if ($name !== '' && (strpos($name, $want) !== false || strpos($want, $name) !== false)) $near[] = $o;
        }
        echo '  ' . str_pad((string)$r['br'], 20) . str_pad((string)$r['br_nm'], 22)
           . str_pad($r['n'] . ' cars', 11) . 'live on 999: ' . str_pad((string)$r['live999'], 5);
        if ($hit) {
            echo "999 id " . ($hit['id'] ?? '?') . "  →  add  '{$r['br']}'=>'" . ($hit['id'] ?? '?') . "',\n";
        } elseif ($near) {
            echo "no exact match. Close: ";
            foreach (array_slice($near, 0, 5) as $o) echo "'" . ($o['title'] ?? '') . "'=" . ($o['id'] ?? '') . "  ";
            echo "\n";
        } else {
            echo "not found in the 999 brand list (" . count($opts) . " brands read)\n";
        }
    }
    echo "\n  Add the lines to Build999Payload::brandId(). Until then these cars are\n";
    echo "  skipped by the cross-post cron without any error anywhere.\n";
    echo "\nDone.\n"; exit;
}

// ─────────────────────────── MODE: compact ───────────────────────────
// How many cars from the saved filters are affected by the commercial/pickup split:
// which ones 999 rejects as commercial, which ones are already live in the wrong
// category, and what each will become now that pickups publish as cars. Read-only.
if ($do === 'compact') {
    $cols = array_column($db->query("SHOW COLUMNS FROM {$S999}")->fetchAll(PDO::FETCH_ASSOC), 'Field');
    $ec = in_array('error_message', $cols, true) ? 'error_message' : (in_array('error', $cols, true) ? 'error' : null);

    hr("commercial cars from filters — by body type");
    $rows = $db->query("SELECT COALESCE(NULLIF(cc.bt,''),'(empty)') bt, COUNT(*) n,
            SUM(cc.`999_id` > 0) live999
        FROM {$P} pc JOIN {$C} cc ON cc.id = pc.car_ctlg_id
        WHERE pc.filter_id IS NOT NULL AND cc.gr = 'com'
        GROUP BY bt ORDER BY n DESC")->fetchAll(PDO::FETCH_ASSOC);
    if (!$rows) echo "  (none)\n";
    $totCom = 0; $totPkp = 0;
    foreach ($rows as $r) {
        $totCom += (int)$r['n'];
        if ($r['bt'] === 'pkp') $totPkp += (int)$r['n'];
        $what = $r['bt'] === 'pkp' ? 'PICKUP -> now published as a car (659)' : 'stays commercial (660)';
        echo '  ' . str_pad($r['bt'], 10) . str_pad((string)$r['n'], 6)
           . 'live on 999: ' . str_pad((string)$r['live999'], 6) . $what . "\n";
    }
    echo "  ── {$totCom} commercial in total, {$totPkp} of them pickups\n";

    hr("which models — pickups first");
    foreach ($db->query("SELECT cc.br_nm, cc.mo_nm, COALESCE(NULLIF(cc.bt,''),'?') bt, COUNT(*) n,
            SUM(cc.`999_id` > 0) live999
        FROM {$P} pc JOIN {$C} cc ON cc.id = pc.car_ctlg_id
        WHERE pc.filter_id IS NOT NULL AND cc.gr = 'com'
        GROUP BY cc.br_nm, cc.mo_nm, bt
        ORDER BY (bt = 'pkp') DESC, n DESC LIMIT 40")->fetchAll(PDO::FETCH_ASSOC) as $r) {
        echo '  ' . str_pad($r['bt'], 6) . str_pad($r['br_nm'] . ' ' . $r['mo_nm'], 34)
           . str_pad((string)$r['n'], 5) . 'live on 999: ' . $r['live999'] . "\n";
    }

    hr("999 queue status of those cars");
    foreach ($db->query("SELECT COALESCE(NULLIF(cc.bt,''),'?') bt, s.status, COUNT(*) n
        FROM {$P} pc JOIN {$C} cc ON cc.id = pc.car_ctlg_id
        JOIN {$S999} s ON s.car_id = cc.id
        WHERE pc.filter_id IS NOT NULL AND cc.gr = 'com'
        GROUP BY bt, s.status ORDER BY n DESC")->fetchAll(PDO::FETCH_ASSOC) as $r)
        echo '  ' . str_pad($r['bt'], 6) . str_pad($r['status'], 12) . $r['n'] . "\n";

    if ($ec) {
        hr("failed with a commercial-field error (103 / 1408 / 152)");
        $r = $db->query("SELECT COUNT(DISTINCT cc.id) n
            FROM {$P} pc JOIN {$C} cc ON cc.id = pc.car_ctlg_id
            JOIN {$S999} s ON s.car_id = cc.id
            WHERE pc.filter_id IS NOT NULL AND s.status = 'failed'
              AND (s.{$ec} LIKE '%103%' OR s.{$ec} LIKE '%1408%' OR s.{$ec} LIKE '%152%')")->fetch(PDO::FETCH_ASSOC);
        echo "  {$r['n']} car(s) — these are the ones to reset to 'pending' after deploying the fix\n";

        // The commercial-field error is only one of the reasons. Show every reason these
        // cars failed, split pickup vs the rest, so we don't fix one and leave the pile.
        hr("why they really failed — every message, pickups vs the rest");
        foreach ($db->query("SELECT COALESCE(NULLIF(cc.bt,''),'?') bt,
                COALESCE(NULLIF(s.{$ec},''),'(no message)') msg,
                COUNT(*) rows_n, COUNT(DISTINCT cc.id) cars_n
            FROM {$P} pc JOIN {$C} cc ON cc.id = pc.car_ctlg_id
            JOIN {$S999} s ON s.car_id = cc.id
            WHERE pc.filter_id IS NOT NULL AND cc.gr = 'com' AND s.status = 'failed'
            GROUP BY bt, msg ORDER BY cars_n DESC LIMIT 15")->fetchAll(PDO::FETCH_ASSOC) as $r)
            echo '  ' . str_pad($r['bt'], 5) . str_pad($r['cars_n'] . ' cars', 11)
               . str_pad($r['rows_n'] . ' rows', 11) . substr((string)$r['msg'], 0, 110) . "\n";
    }

    hr("pickups ALREADY live on 999 in the commercial category");
    $r = $db->query("SELECT COUNT(*) n FROM {$P} pc JOIN {$C} cc ON cc.id = pc.car_ctlg_id
        WHERE pc.filter_id IS NOT NULL AND cc.gr = 'com' AND cc.bt = 'pkp' AND cc.`999_id` > 0")
        ->fetch(PDO::FETCH_ASSOC);
    echo "  {$r['n']} advert(s) are live under the wrong category — they keep working,\n";
    echo "  the new rule only applies the next time they are (re)published.\n";

    echo "\nDone.\n"; exit;
}

// ─────────────────────────── MODE: comerr ───────────────────────────
// Show the ACTUAL car data (vol/fuel/gr/mo_nm) for parsing cars that FAILED on 999
// with a commercial-feature error (103 cc, 1408 km, 152 weight), plus what value
// the saved payload sent for feature 103. Explains why 999 rejected it. Read-only.
if ($do === 'comerr') {
    $cols = array_column($db->query("SHOW COLUMNS FROM {$S999}")->fetchAll(PDO::FETCH_ASSOC), 'Field');
    $ec = in_array('error_message', $cols, true) ? 'error_message' : (in_array('error', $cols, true) ? 'error' : null);
    hr("failed commercial cars — real data vs payload");
    $rows = $db->query("SELECT DISTINCT cc.id, cc.gr, cc.br_nm, cc.mo_nm, cc.yr, cc.vol, cc.fl, cc.mlg, cc.sts, cc.`999` payload
        FROM {$S999} s JOIN {$C} cc ON cc.id = s.car_id JOIN {$P} pc ON pc.car_ctlg_id = cc.id
        WHERE s.status = 'failed' AND pc.filter_id IS NOT NULL".($ec ? " AND (s.{$ec} LIKE '%103%' OR s.{$ec} LIKE '%1408%' OR s.{$ec} LIKE '%152%')" : "")."
        LIMIT 20")->fetchAll(PDO::FETCH_ASSOC);
    if (!$rows) echo "  (none)\n";
    foreach ($rows as $r) {
        // What feature 103 the payload sent.
        $f103 = '(absent)';
        $d = json_decode((string)$r['payload'], true);
        if (is_array($d) && !empty($d['features'])) {
            foreach ($d['features'] as $f) if ((string)($f['id'] ?? '') === '103') { $f103 = "'".($f['value'] ?? '')."'"; break; }
        }
        $subcat = (is_array($d) ? ($d['subcategory_id'] ?? '?') : '?');
        echo "  #{$r['id']} [{$r['gr']}] {$r['br_nm']} {$r['mo_nm']} {$r['yr']}  vol={$r['vol']} fl={$r['fl']} mlg={$r['mlg']} sts={$r['sts']}\n";
        echo "        payload subcategory={$subcat}  feature103 sent={$f103}\n";
    }
    echo "\nDone.\n"; exit;
}

// ─────────────────────────── MODE: errors999 ───────────────────────────
// Break down 999 queue FAILED/postponed rows BY SOURCE + the exact error message,
// so we see what's wrong per source (Encar vs OpenLane vs eCarsTrade). Read-only.
if ($do === 'errors999') {
    $cols = array_column($db->query("SHOW COLUMNS FROM {$S999}")->fetchAll(PDO::FETCH_ASSOC), 'Field');
    $ec = in_array('error_message', $cols, true) ? 'error_message' : (in_array('error', $cols, true) ? 'error' : null);

    hr("queue rows by source + status (parsing cars)");
    foreach ($db->query("SELECT pc.source, s.status, COUNT(*) c
        FROM {$S999} s JOIN {$P} pc ON pc.car_ctlg_id = s.car_id
        WHERE pc.filter_id IS NOT NULL
        GROUP BY pc.source, s.status ORDER BY pc.source, s.status")->fetchAll(PDO::FETCH_ASSOC) as $r)
        echo "  ".str_pad($r['source'],12)." ".str_pad($r['status'],10)." → {$r['c']}\n";

    if ($ec) {
        hr("FAILED errors by source (exact message)");
        $rows = $db->query("SELECT pc.source, s.{$ec} err, COUNT(*) c
            FROM {$S999} s JOIN {$P} pc ON pc.car_ctlg_id = s.car_id
            WHERE pc.filter_id IS NOT NULL AND s.status = 'failed' AND s.{$ec} IS NOT NULL AND s.{$ec} <> ''
            GROUP BY pc.source, s.{$ec} ORDER BY c DESC LIMIT 20")->fetchAll(PDO::FETCH_ASSOC);
        if (!$rows) echo "  (no failed rows with a message)\n";
        foreach ($rows as $r)
            echo "  [{$r['c']}x] [{$r['source']}] ".substr((string)$r['err'], 0, 150)."\n";

        hr("POSTPONED reasons by source");
        $rows = $db->query("SELECT pc.source, s.{$ec} err, COUNT(*) c
            FROM {$S999} s JOIN {$P} pc ON pc.car_ctlg_id = s.car_id
            WHERE pc.filter_id IS NOT NULL AND s.status = 'postponed' AND s.{$ec} IS NOT NULL AND s.{$ec} <> ''
            GROUP BY pc.source, s.{$ec} ORDER BY c DESC LIMIT 20")->fetchAll(PDO::FETCH_ASSOC);
        if (!$rows) echo "  (no postponed rows with a message)\n";
        foreach ($rows as $r)
            echo "  [{$r['c']}x] [{$r['source']}] ".substr((string)$r['err'], 0, 150)."\n";
    }
    // &clearfailed=1 removes failed parsing rows from the queue so the cron can
    // re-evaluate those cars cleanly (skipping invalid ones, re-queuing valid ones).
    if (($_GET['clearfailed'] ?? '') === '1') {
        hr("CLEAR failed parsing rows from queue");
        $del = $db->prepare("DELETE s FROM {$S999} s
            WHERE s.status = 'failed'
              AND EXISTS (SELECT 1 FROM {$P} pc WHERE pc.car_ctlg_id = s.car_id AND pc.filter_id IS NOT NULL)");
        $del->execute();
        echo "  deleted {$del->rowCount()} failed row(s).\n";
    } else {
        echo "\n  (add &clearfailed=1 to remove failed rows so the cron retries cleanly)\n";
    }
    echo "\nDone.\n"; exit;
}

// ─────────────────────────── MODE: filter999 ───────────────────────────
// Per filter: how many parsing cars are marked crosspost_999=1 (what the filter's
// "999" button shows) vs how many actually have a 999 queue row vs live on 999.
// Reveals filters where cars are queued but the marker is missing (button empty).
if ($do === 'filter999') {
    $fid = (int)($_GET['fid'] ?? 0);
    $fWhere = $fid ? "AND pc.filter_id = {$fid}" : "";
    hr("per-filter 999 state".($fid ? " (filter {$fid})" : ""));
    $rows = $db->query("SELECT pc.filter_id, f.name,
            SUM(pc.crosspost_999 = 1) AS marked,
            SUM(EXISTS(SELECT 1 FROM {$S999} s WHERE s.car_id = pc.car_ctlg_id)) AS queued_any,
            SUM(EXISTS(SELECT 1 FROM {$S999} s WHERE s.car_id = pc.car_ctlg_id AND s.status='pending')) AS queued_pending,
            SUM(cc.`999_id` > 0) AS live999
        FROM {$P} pc
        JOIN {$C} cc ON cc.id = pc.car_ctlg_id
        LEFT JOIN {$F} f ON f.id = pc.filter_id
        WHERE pc.filter_id IS NOT NULL AND pc.car_ctlg_id > 0 {$fWhere}
        GROUP BY pc.filter_id, f.name
        HAVING marked > 0 OR queued_any > 0 OR live999 > 0
        ORDER BY pc.filter_id")->fetchAll(PDO::FETCH_ASSOC);
    if (!$rows) echo "  (no filters with 999 activity)\n";
    foreach ($rows as $r) {
        $mismatch = ((int)$r['queued_any'] > (int)$r['marked']) ? "  ⚠ QUEUED>MARKED (button under-shows)" : "";
        echo "  #".str_pad($r['filter_id'],4)." \"".str_pad(substr((string)$r['name'],0,26),26)."\"  marked={$r['marked']} queued={$r['queued_any']} pending={$r['queued_pending']} live={$r['live999']}{$mismatch}\n";
    }
    // Offer a repair: set crosspost_999=1 for any parsing car that HAS a 999 queue row.
    if (($_GET['fix'] ?? '') === '1') {
        hr("REPAIR: mark crosspost_999=1 where a 999 queue row exists");
        $n = $db->exec("UPDATE {$P} pc
            SET crosspost_999 = 1
            WHERE pc.filter_id IS NOT NULL AND pc.crosspost_999 = 0
              AND EXISTS (SELECT 1 FROM {$S999} s WHERE s.car_id = pc.car_ctlg_id)");
        echo "  marked {$n} car(s).\n";
    } else {
        echo "\n  (add &fix=1 to set crosspost_999=1 on every car that has a 999 queue row)\n";
    }
    echo "\nDone.\n"; exit;
}

// ─────────────────────────── MODE: accounts ───────────────────────────
// How many cars are published on each 999 account (by car_ctlg.999_api_id), split
// into total-live vs published-today (via the schedule table's published_at). Also
// shows today's image-upload pressure toward the ~1200/day limit.
if ($do === 'accounts') {
    $names = [1 => 'SAUTO-HAUS', 2 => 'Sauto-auto-comerciale', 3 => 'Sauto-stock-extern', 4 => 'Encars-MD'];

    hr("cars LIVE on 999 per account (999_id > 0)");
    $rows = $db->query("SELECT COALESCE(`999_api_id`,0) acc, COUNT(*) c
        FROM {$C} WHERE `999_id` > 0 GROUP BY `999_api_id` ORDER BY acc")->fetchAll(PDO::FETCH_ASSOC);
    $tot = 0;
    foreach ($rows as $r) { $tot += (int)$r['c'];
        $nm = $names[(int)$r['acc']] ?? '(unknown)';
        echo "  account ".str_pad($r['acc'],2)." ".str_pad($nm,24)." → {$r['c']} live ads\n";
    }
    echo "  TOTAL live: {$tot}\n";

    hr("published TODAY per account (schedule published_at = today)");
    $rows = $db->query("SELECT COALESCE(cc.`999_api_id`,0) acc, COUNT(DISTINCT cc.id) c
        FROM {$S999} s JOIN {$C} cc ON cc.id = s.car_id
        WHERE s.status='published' AND DATE(s.published_at) = CURDATE()
        GROUP BY cc.`999_api_id` ORDER BY acc")->fetchAll(PDO::FETCH_ASSOC);
    if (!$rows) echo "  (none published today)\n";
    foreach ($rows as $r) {
        $nm = $names[(int)$r['acc']] ?? '(unknown)';
        // Parsing cars send 10 imgs; est. upload pressure.
        $imgs = (int)$r['c'] * 10;
        echo "  account ".str_pad($r['acc'],2)." ".str_pad($nm,24)." → {$r['c']} cars today  (~{$imgs} imgs, limit ~1200)\n";
    }

    hr("published today — parsing cars only, by SOURCE");
    $rows = $db->query("SELECT pc.source, COALESCE(cc.`999_api_id`,0) acc, COUNT(DISTINCT cc.id) c
        FROM {$S999} s JOIN {$C} cc ON cc.id = s.car_id JOIN {$P} pc ON pc.car_ctlg_id = cc.id
        WHERE s.status='published' AND DATE(s.published_at) = CURDATE() AND pc.filter_id IS NOT NULL
        GROUP BY pc.source, cc.`999_api_id` ORDER BY c DESC")->fetchAll(PDO::FETCH_ASSOC);
    if (!$rows) echo "  (no parsing cars published today)\n";
    foreach ($rows as $r) {
        $nm = $names[(int)$r['acc']] ?? '(unknown)';
        echo "  {$r['source']} → account {$r['acc']} ({$nm}): {$r['c']} cars\n";
    }

    hr("current cooldowns");
    date_default_timezone_set('Europe/Chisinau');
    echo "  now (Chisinau): ".date('Y-m-d H:i:s')."\n";
    foreach ($db->query("SELECT name,value FROM {$prefx}_settings WHERE name LIKE '999md_upload_cooldown_%'")->fetchAll(PDO::FETCH_ASSOC) as $r) {
        $acc = str_replace('999md_upload_cooldown_', '', $r['name']); $u = (int)$r['value'];
        $nm = $names[(int)$acc] ?? '';
        $st = $u > time() ? "COOLDOWN until ".date('Y-m-d H:i', $u) : "expired";
        echo "  account {$acc} {$nm}: {$st}\n";
    }
    echo "\nDone.\n"; exit;
}

// ─────────────────────────── MODE: q999 ───────────────────────────
// Full health check of the 999 auto-publish pipeline: queue state by status/
// source/account, cooldowns, whether cars have a payload + photos, and the last
// error per car. Explains why nothing publishes and why Encar isn't even tried.
if ($do === 'q999') {
    hr("999 queue by status (ALL, parsing cars)");
    $base = "FROM {$S999} s WHERE EXISTS (SELECT 1 FROM {$P} pc WHERE pc.car_ctlg_id=s.car_id AND pc.filter_id IS NOT NULL)";
    foreach ($db->query("SELECT status, COUNT(*) c {$base} GROUP BY status")->fetchAll(PDO::FETCH_ASSOC) as $r)
        echo "  {$r['status']}: {$r['c']}\n";

    hr("by SOURCE + 999 account (pending/postponed only)");
    $rows = $db->query("SELECT pc.source, cc.`999_api_id` acc, cc.catalog_type, COUNT(DISTINCT cc.id) c
        FROM {$S999} s JOIN {$C} cc ON cc.id=s.car_id
        JOIN {$P} pc ON pc.car_ctlg_id=cc.id
        WHERE s.status IN ('pending','postponed') AND pc.filter_id IS NOT NULL
        GROUP BY pc.source, cc.`999_api_id`, cc.catalog_type ORDER BY c DESC")->fetchAll(PDO::FETCH_ASSOC);
    foreach ($rows as $r) echo "  source={$r['source']}  999_api_id={$r['acc']}  catalog_type={$r['catalog_type']}  → {$r['c']} cars\n";
    if (!$rows) echo "  (queue empty)\n";

    hr("account cooldowns");
    echo "  server now: ".date('Y-m-d H:i:s')."  (php time()=".time().")\n";
    $anyCd = false;
    foreach ($db->query("SELECT name,value FROM {$prefx}_settings WHERE name LIKE '999md_upload_cooldown_%'")->fetchAll(PDO::FETCH_ASSOC) as $r) {
        $acc = str_replace('999md_upload_cooldown_', '', $r['name']); $u = (int)$r['value'];
        $state = $u > time() ? ("ON COOLDOWN until ".date('Y-m-d H:i:s', $u)." (".round(($u-time())/60)."min left)")
                             : ("EXPIRED at ".date('Y-m-d H:i:s', $u)." — should NOT block");
        if ($u > time()) $anyCd = true;
        echo "  account {$acc}: raw={$u}  {$state}\n";
    }
    if (!$anyCd) echo "  → no account is ACTUALLY on cooldown now\n";
    // Optional: clear expired cooldown rows so nothing stale lingers.
    if (($_GET['clearcd'] ?? '') === '1') {
        $n = 0;
        foreach ($db->query("SELECT name,value FROM {$prefx}_settings WHERE name LIKE '999md_upload_cooldown_%'")->fetchAll(PDO::FETCH_ASSOC) as $r) {
            if ((int)$r['value'] <= time()) { $db->prepare("DELETE FROM {$prefx}_settings WHERE name=?")->execute([$r['name']]); $n++; }
        }
        echo "  cleared {$n} expired cooldown row(s).\n";
    }

    hr("payload + photos check (pending cars sample)");
    $rows = $db->query("SELECT DISTINCT cc.id, pc.source, cc.`999_api_id` acc,
            (cc.`999` IS NULL OR cc.`999`='') AS no_payload,
            (SELECT COUNT(*) FROM {$prefx}_car_pht ph WHERE ph.it_id=cc.id) AS photos,
            cc.n_a
        FROM {$S999} s JOIN {$C} cc ON cc.id=s.car_id JOIN {$P} pc ON pc.car_ctlg_id=cc.id
        WHERE s.status='pending' AND pc.filter_id IS NOT NULL
        ORDER BY pc.source LIMIT 25")->fetchAll(PDO::FETCH_ASSOC);
    foreach ($rows as $r) {
        $issues = [];
        if ($r['no_payload']) $issues[] = 'NO PAYLOAD(999 empty)';
        if ((int)$r['photos'] === 0) $issues[] = 'NO PHOTOS in car_pht';
        if ((int)$r['n_a'] === 1) $issues[] = 'n_a=1 (out of stock)';
        echo "  car #{$r['id']} [{$r['source']}] acc={$r['acc']} photos={$r['photos']}"
            .($issues ? "  ⚠ ".implode(', ', $issues) : "  ok")."\n";
    }
    if (!$rows) echo "  (no pending parsing cars)\n";

    hr("last errors (failed)");
    $cols = array_column($db->query("SHOW COLUMNS FROM {$S999}")->fetchAll(PDO::FETCH_ASSOC), 'Field');
    $ec = in_array('error_message', $cols, true) ? 'error_message' : (in_array('error', $cols, true) ? 'error' : null);
    if ($ec) {
        foreach ($db->query("SELECT s.{$ec} err, COUNT(*) c {$base} AND s.status='failed' GROUP BY s.{$ec} ORDER BY c DESC LIMIT 10")->fetchAll(PDO::FETCH_ASSOC) as $r)
            echo "  [{$r['c']}x] ".substr((string)$r['err'], 0, 140)."\n";
    }

    hr("photos actually SENT to 999 (feature 14 in saved payload)");
    // For cars already published, count image IDs stored in car_ctlg.999 feature 14
    // — that's exactly what went to 999.md. Confirms the 10-photo cap took effect.
    $rows = $db->query("SELECT cc.id, pc.source, cc.`999_api_id` acc, cc.`999` payload
        FROM {$C} cc JOIN {$P} pc ON pc.car_ctlg_id=cc.id
        WHERE cc.`999_id` > 0 AND pc.filter_id IS NOT NULL AND cc.`999` IS NOT NULL AND cc.`999` <> ''
        ORDER BY cc.id DESC LIMIT 15")->fetchAll(PDO::FETCH_ASSOC);
    foreach ($rows as $r) {
        $n = 0;
        $d = json_decode((string)$r['payload'], true);
        if (is_array($d) && !empty($d['features'])) {
            foreach ($d['features'] as $f) {
                if ((string)($f['id'] ?? '') === '14') {
                    $v = $f['value'] ?? [];
                    $n = is_array($v) ? count($v) : (($v === '' || $v === null) ? 0 : 1);
                    break;
                }
            }
        }
        $flag = ($n > 10) ? "  ⚠ MORE THAN 10" : "";
        echo "  car #{$r['id']} [{$r['source']}] acc={$r['acc']}  → {$n} images sent to 999{$flag}\n";
    }
    if (!$rows) echo "  (no published parsing cars with saved payload yet)\n";

    hr("scheduled time check (are pending cars DUE?)");
    $due = (int)$db->query("SELECT COUNT(*) FROM {$S999} s
        WHERE s.status='pending' AND CONCAT(s.schedule_date,' ',s.schedule_time) <= NOW()
          AND EXISTS (SELECT 1 FROM {$P} pc WHERE pc.car_ctlg_id=s.car_id AND pc.filter_id IS NOT NULL)")->fetchColumn();
    $future = (int)$db->query("SELECT COUNT(*) FROM {$S999} s
        WHERE s.status='pending' AND CONCAT(s.schedule_date,' ',s.schedule_time) > NOW()
          AND EXISTS (SELECT 1 FROM {$P} pc WHERE pc.car_ctlg_id=s.car_id AND pc.filter_id IS NOT NULL)")->fetchColumn();
    echo "  due now: {$due}   scheduled for future: {$future}\n";
    echo "  (future = renewal slots +30/+60 days — normal they wait)\n";
    echo "\nDone.\n"; exit;
}

// ─────────────────────────── MODE: filstat ───────────────────────────
// For ONE filter (?fid=), the full per-channel breakdown: cars on site, and on each
// channel split into published / pending / failed. Explains why the header stats
// (which count only 'published') differ per channel. Usage: ?do=filstat&fid=108
if ($do === 'filstat') {
    $fid = (int)($_GET['fid'] ?? 0);
    if ($fid <= 0) { echo "  pass &fid=<filter_id>\n\nDone.\n"; exit; }
    $fname = $db->query("SELECT name FROM {$F} WHERE id={$fid}")->fetchColumn();
    hr("filter #{$fid} \"{$fname}\" — cross-post breakdown");

    $base = "FROM {$P} pc JOIN {$C} cc ON cc.id=pc.car_ctlg_id
             WHERE pc.filter_id={$fid} AND pc.car_ctlg_id>0
               AND (pc.status='published' OR (pc.status='unavailable' AND pc.car_ctlg_id>0))";
    $site = (int)$db->query("SELECT COUNT(*) {$base}")->fetchColumn();
    echo "  on site (published to sauto): {$site}\n\n";

    $chTables = ['999' => $S999, 'facebook' => $SFB, 'telegram' => $STG];
    foreach ($chTables as $ch => $tbl) {
        $line = "  [".str_pad($ch,9)."] ";
        foreach (['published','pending','postponed','failed'] as $st) {
            try {
                $n = (int)$db->query("SELECT COUNT(DISTINCT pc.car_ctlg_id)
                    FROM {$P} pc JOIN {$tbl} s ON s.car_id=pc.car_ctlg_id AND s.status='{$st}'
                    WHERE pc.filter_id={$fid} AND pc.car_ctlg_id>0")->fetchColumn();
            } catch (\Throwable $e) { $n = 0; }
            $line .= str_pad("{$st}={$n}", 16);
        }
        echo $line."\n";
    }
    echo "\n  (header 'X Facebook' = the published count only)\n";
    echo "\nDone.\n"; exit;
}

// ─────────────────────────── MODE: tgqueue ───────────────────────────
// Why a due Telegram post hasn't gone out: shows how many pending are DUE (past
// their scheduled time) vs future, the oldest due ones (processing order), and the
// scheduled time of a specific car (&id=). The TG cron takes 10 due posts per run,
// oldest first — so a car deep in a big due backlog waits several runs.
if ($do === 'tgqueue') {
    $id = (int)($_GET['id'] ?? 0);
    echo "  server now: ".$db->query("SELECT NOW()")->fetchColumn()."\n";
    $due = (int)$db->query("SELECT COUNT(*) FROM {$STG}
        WHERE status='pending' AND CONCAT(scheduled_date,' ',scheduled_time) <= NOW()")->fetchColumn();
    $future = (int)$db->query("SELECT COUNT(*) FROM {$STG}
        WHERE status='pending' AND CONCAT(scheduled_date,' ',scheduled_time) > NOW()")->fetchColumn();
    hr("Telegram queue");
    echo "  pending DUE now: {$due}   pending future: {$future}\n";
    echo "  (TG cron processes 10 due/run, oldest scheduled first)\n";

    hr("oldest 15 DUE pending (processing order)");
    foreach ($db->query("SELECT id, car_id, scheduled_date, scheduled_time FROM {$STG}
        WHERE status='pending' AND CONCAT(scheduled_date,' ',scheduled_time) <= NOW()
        ORDER BY scheduled_date, scheduled_time LIMIT 15")->fetchAll(PDO::FETCH_ASSOC) as $r)
        echo "    post#{$r['id']} car#{$r['car_id']}  scheduled {$r['scheduled_date']} {$r['scheduled_time']}\n";

    if ($id) {
        hr("car #{$id} position in TG queue");
        $rows = $db->query("SELECT id, scheduled_date, scheduled_time, status FROM {$STG} WHERE car_id={$id}")->fetchAll(PDO::FETCH_ASSOC);
        foreach ($rows as $r) {
            $ahead = (int)$db->query("SELECT COUNT(*) FROM {$STG}
                WHERE status='pending' AND CONCAT(scheduled_date,' ',scheduled_time) <= NOW()
                  AND CONCAT(scheduled_date,' ',scheduled_time) < '{$r['scheduled_date']} {$r['scheduled_time']}'")->fetchColumn();
            echo "    post#{$r['id']} status={$r['status']} scheduled {$r['scheduled_date']} {$r['scheduled_time']}  ({$ahead} due ahead of it)\n";
        }
    }
    echo "\nDone.\n"; exit;
}

// ─────────────────────────── MODE: car ───────────────────────────
// Full cross-post status of ONE car (by car_ctlg id): its schedule rows on each
// channel with status + published_at + any error, so you can tell if it actually
// went out. Usage: ?do=car&id=34351
if ($do === 'car') {
    $id = (int)($_GET['id'] ?? 0);
    if ($id <= 0) { echo "  pass &id=<car_ctlg_id>\n\nDone.\n"; exit; }
    $info = $db->query("SELECT br_nm, mo_nm, yr, prc, `999_id`,
        COALESCE(facebook_published,0) fbp, COALESCE(telegram_published,0) tgp
        FROM {$C} WHERE id={$id}")->fetch(PDO::FETCH_ASSOC);
    hr("car #{$id}: ".($info ? "{$info['br_nm']} {$info['mo_nm']} {$info['yr']}  {$info['prc']}€" : "NOT FOUND"));
    if ($info) {
        echo "  999_id (live on 999): ".($info['999_id'] ?: '(not on 999)')."\n";
        // These flags decide eligibility: FB skips fbp<>0, TG skips tgp<>0, 999 skips 999_id>0.
        echo "  flags: facebook_published={$info['fbp']}  telegram_published={$info['tgp']}   (0=eligible, 1=published, 2=queued)\n";
        // Which filter(s) this car belongs to.
        $f = $db->query("SELECT pc.filter_id, pf.name FROM {$P} pc LEFT JOIN {$F} pf ON pf.id=pc.filter_id WHERE pc.car_ctlg_id={$id}")->fetch(PDO::FETCH_ASSOC);
        if ($f) echo "  filter: #{$f['filter_id']} \"{$f['name']}\"\n";
    }
    $chTables = ['999' => $S999, 'facebook' => $SFB, 'telegram' => $STG];
    foreach ($chTables as $ch => $tbl) {
        try { $cols = array_column($db->query("SHOW COLUMNS FROM {$tbl}")->fetchAll(PDO::FETCH_ASSOC), 'Field'); }
        catch (\Throwable $e) { echo "  [{$ch}] table missing\n"; continue; }
        $ec = in_array('error_message', $cols, true) ? 'error_message' : (in_array('error', $cols, true) ? 'error' : "''");
        $pubCol = in_array('published_at', $cols, true) ? 'published_at' : "''";
        $rows = $db->query("SELECT status, {$pubCol} pub, {$ec} err FROM {$tbl} WHERE car_id={$id} ORDER BY id")->fetchAll(PDO::FETCH_ASSOC);
        if (!$rows) { echo "  [{$ch}] no schedule row\n"; continue; }
        foreach ($rows as $r) {
            $line = "  [{$ch}] status={$r['status']}";
            if (!empty($r['pub'])) $line .= "  published_at={$r['pub']}";
            if (!empty($r['err'])) $line .= "\n         err=".substr((string)$r['err'],0,300);
            echo $line."\n";
        }
    }
    echo "\nDone.\n"; exit;
}

// ─────────────────────────── MODE: fbtg ───────────────────────────
// Health of the Facebook + Telegram queues for parsing cars: rows by status, any
// errors, and how many cars are still waiting per filter. TG/FB have no image-limit
// cooldown, so pending should drain fast. Read-only.
if ($do === 'fbtg') {
    $tables = ['fb' => $SFB, 'tg' => $STG];
    foreach ($tables as $ch => $tbl) {
        hr(strtoupper($ch)." queue by status (parsing cars)");
        try {
            $cols = array_column($db->query("SHOW COLUMNS FROM {$tbl}")->fetchAll(PDO::FETCH_ASSOC), 'Field');
        } catch (\Throwable $e) { echo "  MISSING TABLE {$tbl}\n"; continue; }
        foreach ($db->query("SELECT s.status, COUNT(*) c
            FROM {$tbl} s JOIN {$P} pc ON pc.car_ctlg_id = s.car_id
            WHERE pc.filter_id IS NOT NULL GROUP BY s.status ORDER BY s.status")->fetchAll(PDO::FETCH_ASSOC) as $r)
            echo "  {$r['status']}: {$r['c']}\n";

        // Pending still waiting, per filter.
        $wait = (int)$db->query("SELECT COUNT(DISTINCT s.car_id)
            FROM {$tbl} s JOIN {$P} pc ON pc.car_ctlg_id = s.car_id
            WHERE pc.filter_id IS NOT NULL AND s.status='pending'")->fetchColumn();
        echo "  distinct cars still PENDING: {$wait}\n";

        // Errors, if the table has an error column.
        $ec = in_array('error_message', $cols, true) ? 'error_message' : (in_array('error', $cols, true) ? 'error' : null);
        if ($ec) {
            $errs = $db->query("SELECT s.{$ec} err, COUNT(*) c
                FROM {$tbl} s JOIN {$P} pc ON pc.car_ctlg_id = s.car_id
                WHERE pc.filter_id IS NOT NULL AND s.status='failed' AND s.{$ec} IS NOT NULL AND s.{$ec} <> ''
                GROUP BY s.{$ec} ORDER BY c DESC LIMIT 8")->fetchAll(PDO::FETCH_ASSOC);
            foreach ($errs as $r) echo "  [failed {$r['c']}x] ".substr((string)$r['err'],0,140)."\n";
        }
        // Published today.
        $today = (int)$db->query("SELECT COUNT(DISTINCT s.car_id)
            FROM {$tbl} s JOIN {$P} pc ON pc.car_ctlg_id = s.car_id
            WHERE pc.filter_id IS NOT NULL AND s.status='published' AND DATE(s.published_at)=CURDATE()")->fetchColumn();
        echo "  published today: {$today}\n";
    }
    echo "\nDone.\n"; exit;
}

// ─────────────────────────── MODE: trimqueue ───────────────────────────
// Trim the 999 queue to a per-filter PENDING cap (default 2). For each filter, keep
// the cheapest N pending/postponed cars and delete the rest of that filter's pending
// rows. NEVER touches published rows or cars already live on 999. Resets the
// crosspost_999 marker only for cars fully removed (no remaining queue row, not
// live). Dry-run unless &apply=1. Cap via &cap=N (default 2).
if ($do === 'trimqueue') {
    $apply = (($_GET['apply'] ?? '') === '1');
    $cap   = max(0, (int)($_GET['cap'] ?? 2));
    $ch    = strtolower((string)($_GET['ch'] ?? '999'));   // 999 | tg | fb
    $tblMap = ['999' => $S999, 'tg' => $STG, 'fb' => $SFB];
    if (!isset($tblMap[$ch])) { echo "  unknown ch. use ch=999|tg|fb\n\nDone.\n"; exit; }
    $TBL = $tblMap[$ch];
    hr($apply ? "TRIM {$ch} queue to {$cap} pending/filter" : "DRY-RUN trim {$ch} (add &apply=1), cap={$cap}");

    // Per filter, list pending/postponed parsing cars ordered cheapest-first. Skip
    // cars already live on 999 only for the 999 channel (irrelevant for tg/fb).
    $liveGuard = $ch === '999'
        ? "AND NOT EXISTS (SELECT 1 FROM {$C} c2 WHERE c2.id = cc.id AND c2.`999_id` > 0)" : "";
    $rows = $db->query("SELECT pc.filter_id, cc.id car_id, cc.prc
        FROM {$TBL} s
        JOIN {$C} cc ON cc.id = s.car_id
        JOIN {$P} pc ON pc.car_ctlg_id = cc.id
        WHERE s.status IN ('pending','postponed') AND pc.filter_id IS NOT NULL {$liveGuard}
        GROUP BY pc.filter_id, cc.id, cc.prc
        ORDER BY pc.filter_id, cc.prc ASC")->fetchAll(PDO::FETCH_ASSOC);

    // Decide keep vs drop per filter (keep cheapest `cap`).
    $perFilter = []; $dropCars = []; $keptCars = 0;
    foreach ($rows as $r) {
        $f = (int)$r['filter_id'];
        $perFilter[$f] = ($perFilter[$f] ?? 0) + 1;
        if ($perFilter[$f] <= $cap) { $keptCars++; }
        else { $dropCars[] = (int)$r['car_id']; }
    }
    echo "  filters with pending: ".count($perFilter)."\n";
    echo "  cars kept (<= {$cap}/filter): {$keptCars}\n";
    echo "  cars to DROP (surplus): ".count($dropCars)."\n";

    if (!$apply) { echo "\n  PREVIEW only. Add &apply=1 to delete surplus pending rows.\n\nDone.\n"; exit; }
    if (!$dropCars) { echo "\n  nothing to drop.\n\nDone.\n"; exit; }

    // Delete pending/postponed rows of the surplus cars in this channel's table.
    $chunks = array_chunk($dropCars, 500);
    $deleted = 0;
    foreach ($chunks as $chunk) {
        $in = implode(',', array_map('intval', $chunk));
        $del = $db->prepare("DELETE FROM {$TBL}
            WHERE car_id IN ({$in}) AND status IN ('pending','postponed')");
        $del->execute();
        $deleted += $del->rowCount();
    }
    echo "\n  deleted {$deleted} surplus schedule row(s) across ".count($dropCars)." cars.\n";
    if ($ch === '999') {
        // 999 marker only.
        $db->exec("UPDATE {$P} pc SET crosspost_999 = 0
            WHERE crosspost_999 = 1
              AND NOT EXISTS (SELECT 1 FROM {$S999} s WHERE s.car_id = pc.car_ctlg_id)
              AND NOT EXISTS (SELECT 1 FROM {$C} cc WHERE cc.id = pc.car_ctlg_id AND cc.`999_id` > 0)");
        echo "  reset crosspost_999 marker for fully-removed cars.\n";
    }
    echo "\nDone.\n"; exit;
}

// ─────────────────────────── MODE: delfile ───────────────────────────
// Delete ONE specific junk file after confirming its size + type. Guarded: only
// deletes a plain FILE (never a dir), only under document root, and requires the
// expected size to match (so we never delete the wrong thing). &apply=1 to delete.
//   ?do=delfile&path=media/images/upload/ziw7xzs6&expect=12288687910&apply=1
if ($do === 'delfile') {
    $root = rtrim($_SERVER['DOCUMENT_ROOT'] ?? dirname(__DIR__), '/');
    $rel = $_GET['path'] ?? '';
    $p = ($rel && $rel[0] === '/') ? $rel : $root.'/'.ltrim($rel,'/');
    $apply = ($_GET['apply'] ?? '') === '1';
    $expect = (int)($_GET['expect'] ?? 0);
    hr($apply ? "DELETE file" : "DRY-RUN delfile (add &apply=1)");
    if (!file_exists($p)) { echo "  does not exist\n\nDone.\n"; exit; }
    if (!is_file($p) || is_link($p)) { echo "  NOT a plain file — refusing (safety)\n\nDone.\n"; exit; }
    $real = realpath($p); $realRoot = realpath($root);
    if (strpos($real, $realRoot) !== 0) { echo "  outside document root — refusing\n\nDone.\n"; exit; }
    $sz = (int)filesize($p);
    echo "  file: {$p}\n  size: ".number_format($sz/1073741824,2)." GB\n";
    if ($expect > 0 && $sz !== $expect) {
        echo "  ⚠ size mismatch (expected {$expect}, got {$sz}) — refusing. Re-check with &do=whatis.\n\nDone.\n"; exit;
    }
    if (!$apply) { echo "\n  add &apply=1 (and &expect={$sz}) to delete.\n\nDone.\n"; exit; }
    if (@unlink($p)) echo "\n  ✓ deleted. Freed ".number_format($sz/1073741824,2)." GB.\n";
    else echo "\n  ✗ delete failed (permissions?)\n";
    echo "\nDone.\n"; exit;
}

// ─────────────────────────── MODE: dupfilters ───────────────────────────
// Detect saved parsing filters that duplicate each other: same brand (+ same model,
// or both brand-only) AND at least one shared source. Different sources are NOT
// duplicates (BMW on Encar vs BMW on eCarsTrade are separate catalogs). Read-only —
// lists the groups so you can merge them by hand. ?do=dupfilters
if ($do === 'dupfilters') {
    $norm = fn($s) => mb_strtolower(trim((string)$s));
    $rows = $db->query("SELECT id, name, brand, model, sources, year_from, year_to
        FROM {$F} ORDER BY brand, model, id")->fetchAll(PDO::FETCH_ASSOC);

    // Group by normalized brand+model.
    $groups = [];
    foreach ($rows as $r) {
        $key = $norm($r['brand']) . '|' . $norm($r['model']);
        if ($norm($r['brand']) === '') continue; // skip filters without a brand
        $groups[$key][] = $r;
    }

    hr("duplicate filters (same brand+model + shared source)");
    $found = 0;
    foreach ($groups as $key => $list) {
        if (count($list) < 2) continue;
        // Within the same brand+model, flag only pairs that share a source.
        $dupIds = [];
        for ($i = 0; $i < count($list); $i++) {
            for ($j = $i + 1; $j < count($list); $j++) {
                $si = array_filter(array_map('trim', explode(',', $norm($list[$i]['sources']))));
                $sj = array_filter(array_map('trim', explode(',', $norm($list[$j]['sources']))));
                if (array_intersect($si, $sj)) { $dupIds[$list[$i]['id']] = 1; $dupIds[$list[$j]['id']] = 1; }
            }
        }
        if (!$dupIds) continue;
        $found++;
        [$b, $m] = explode('|', $key);
        echo "\n  ▸ {$list[0]['brand']} ".($m !== '' ? $list[0]['model'] : '(doar marcă)')."\n";
        foreach ($list as $r) {
            $mark = isset($dupIds[$r['id']]) ? '  ⚠ DUP' : '';
            $yr = ($r['year_from'] ?: '?').'–'.($r['year_to'] ?: '?');
            echo "      #{$r['id']}  \"{$r['name']}\"  [{$r['sources']}]  {$yr}{$mark}\n";
        }
    }
    if (!$found) echo "  none — no brand+model+source duplicates.\n";
    echo "\nDone.\n"; exit;
}

// ─────────────────────────── MODE: perftest ───────────────────────────
// Times each server-side phase of /parsing/published so we know what's slow:
// the count query, the main 100-row JOIN query, the pricing payload, and the
// per-card schedule-status lookups. Also EXPLAINs the main query to expose a
// full-table scan / filesort. Read-only. ?do=perftest
if ($do === 'perftest') {
    $ms = function ($t0) { return number_format((microtime(true) - $t0) * 1000, 1); };
    $where = '(pc.status = "published" OR (pc.status = "unavailable" AND pc.car_ctlg_id IS NOT NULL AND pc.car_ctlg_id > 0))';

    hr("row counts");
    $t = microtime(true);
    $total = (int)$db->query('SELECT COUNT(*) FROM '.$P.' pc WHERE '.$where)->fetchColumn();
    echo "  COUNT(*) published: {$total}   [{$ms($t)} ms]\n";
    echo "  total parsing_cars rows: ".(int)$db->query("SELECT COUNT(*) FROM {$P}")->fetchColumn()."\n";
    echo "  total car_list rows:     ".(int)$db->query("SELECT COUNT(*) FROM {$prefx}_car_list")->fetchColumn()."\n";

    $mainSql = 'SELECT pc.*, cc.vis AS ctlg_vis, cc.`999_id` AS ctlg_999_id,
            COALESCE(NULLIF(clc.br_nm, ""), NULLIF(cl.br_nm, ""), pc.brand) AS brand,
            COALESCE(NULLIF(clc.mo_nm, ""), NULLIF(cl.mo_nm, ""), pc.model) AS model
        FROM '.$P.' pc
        LEFT JOIN '.$C.' cc ON cc.id = pc.car_ctlg_id
        LEFT JOIN '.$prefx.'_car_list clc ON clc.br = cc.br AND clc.mo = cc.mo
        LEFT JOIN '.$prefx.'_car_list cl ON cl.br = pc.sauto_br AND cl.mo = pc.sauto_mo
        WHERE '.$where.'
        ORDER BY pc.published_at DESC
        LIMIT 100 OFFSET 0';

    hr("main list query (page 1, LIMIT 100)");
    $t = microtime(true);
    $rows = $db->query($mainSql)->fetchAll(PDO::FETCH_ASSOC);
    echo "  fetched ".count($rows)." rows   [{$ms($t)} ms]\n";

    hr("EXPLAIN main query");
    foreach ($db->query('EXPLAIN '.$mainSql)->fetchAll(PDO::FETCH_ASSOC) as $r) {
        echo "  tbl={$r['table']} type={$r['type']} key=".($r['key'] ?? 'NULL')
            ." rows={$r['rows']} Extra=".($r['Extra'] ?? '')."\n";
    }

    hr("schedule-status lookups (3 tables)");
    $ctlgIds = array_values(array_filter(array_map(fn($c) => (int)($c['car_ctlg_id'] ?? 0), $rows)));
    if ($ctlgIds) {
        $in = implode(',', array_fill(0, count($ctlgIds), '?'));
        foreach (['999'=>$S999,'tg'=>$STG,'fb'=>$SFB] as $k=>$tbl) {
            $t = microtime(true);
            $q = $db->prepare('SELECT car_id, status FROM '.$tbl.' WHERE car_id IN ('.$in.')');
            $q->execute($ctlgIds);
            $n = count($q->fetchAll());
            echo "  {$k}: {$n} rows   [{$ms($t)} ms]\n";
        }
    }

    hr("pricing payload (runs every load, in <script>)");
    $t = microtime(true);
    if (function_exists('parsing_pricing_payload')) {
        $p = parsing_pricing_payload($db, $prefx);
        echo "  parsing_pricing_payload: ".strlen(json_encode($p))." bytes   [{$ms($t)} ms]\n";
    } else {
        require_once __DIR__.'/../content/admin/page/parsing/parsing_pricing.php';
        $p = parsing_pricing_payload($db, $prefx);
        echo "  parsing_pricing_payload: ".strlen(json_encode($p))." bytes   [{$ms($t)} ms]\n";
    }

    hr("distribution (why the filesort is big)");
    $pub = (int)$db->query("SELECT COUNT(*) FROM {$P} WHERE status='published'")->fetchColumn();
    $una = (int)$db->query("SELECT COUNT(*) FROM {$P} WHERE status='unavailable' AND car_ctlg_id > 0")->fetchColumn();
    echo "  published: {$pub}   unavailable+linked: {$una}\n";

    // Would an index on (status, published_at) let MySQL skip the filesort for the
    // common case? Test the simplified single-status ORDER BY.
    hr("test: ORDER BY published_at only (status=published)");
    $t = microtime(true);
    $test = $db->query('SELECT pc.id FROM '.$P.' pc
        WHERE pc.status = "published"
        ORDER BY pc.published_at DESC LIMIT 100')->fetchAll();
    echo "  ".count($test)." rows   [{$ms($t)} ms]\n";
    foreach ($db->query('EXPLAIN SELECT pc.id FROM '.$P.' pc WHERE pc.status="published" ORDER BY pc.published_at DESC LIMIT 100')->fetchAll(PDO::FETCH_ASSOC) as $r) {
        echo "  EXPLAIN: type={$r['type']} key=".($r['key'] ?? 'NULL')." rows={$r['rows']} Extra=".($r['Extra'] ?? '')."\n";
    }

    echo "\nDone.\n"; exit;
}

// ─────────────────────────── MODE: perffix ───────────────────────────
// The published list is slow because car_list has no index on (br, mo), so the
// 4th JOIN (cl ON cl.br = pc.sauto_br AND cl.mo = pc.sauto_mo) does a full-table
// scan per row. This shows the current indexes and, with &apply=1, adds the
// composite indexes that let both car_list JOINs use a key instead of scanning.
if ($do === 'perffix') {
    $apply = ($_GET['apply'] ?? '') === '1';

    $showIdx = function ($tbl) use ($db) {
        hr("indexes on {$tbl}");
        $seen = [];
        foreach ($db->query("SHOW INDEX FROM {$tbl}")->fetchAll(PDO::FETCH_ASSOC) as $r) {
            $seen[$r['Key_name']][(int)$r['Seq_in_index']] = $r['Column_name'];
        }
        foreach ($seen as $name => $cols) { ksort($cols); echo "  {$name}: (".implode(', ', $cols).")\n"; }
        return $seen;
    };

    $clIdx = $showIdx($prefx.'_car_list');
    $pcIdx = $showIdx($P);

    // Type/collation mismatch stops MySQL from using an index on a JOIN. Compare
    // the 4 columns the car_list JOINs key on.
    hr("column types (JOIN keys)");
    $cols = [
        [$prefx.'_car_list', 'br'], [$prefx.'_car_list', 'mo'],
        [$P, 'sauto_br'], [$P, 'sauto_mo'],
        [$C, 'br'], [$C, 'mo'],
    ];
    foreach ($cols as [$tbl, $col]) {
        $r = $db->query("SHOW FULL COLUMNS FROM {$tbl} LIKE ".$db->quote($col))->fetch(PDO::FETCH_ASSOC);
        if ($r) echo "  {$tbl}.{$col}: type={$r['Type']} collation=".($r['Collation'] ?? '(none)')."\n";
        else    echo "  {$tbl}.{$col}: (column not found)\n";
    }

    // Does any index START with (br, mo) / (sauto_br, sauto_mo)? Only a composite
    // led by both columns lets the JOIN use a key.
    $hasComposite = function ($idx, $c1, $c2) {
        foreach ($idx as $cols) {
            ksort($cols); $vals = array_values($cols);
            if (($vals[0] ?? '') === $c1 && ($vals[1] ?? '') === $c2) return true;
        }
        return false;
    };
    $needCl = !$hasComposite($clIdx, 'br', 'mo');
    $needPc = !$hasComposite($pcIdx, 'sauto_br', 'sauto_mo');

    hr("plan");
    echo "  car_list(br, mo):            ".($needCl ? "MISSING — will add" : "already present ✓")."\n";
    echo "  parsing_cars(sauto_br,mo):   ".($needPc ? "MISSING — will add" : "already present ✓")."\n";

    if (!$apply) { echo "\n  add &apply=1 to create the missing index(es).\n\nDone.\n"; exit; }

    if ($needCl) {
        try { $db->exec("ALTER TABLE {$prefx}_car_list ADD INDEX idx_br_mo (br, mo)"); echo "  ✓ car_list idx_br_mo created\n"; }
        catch (\Throwable $e) { echo "  ✗ car_list: ".$e->getMessage()."\n"; }
    }
    if ($needPc) {
        try { $db->exec("ALTER TABLE {$P} ADD INDEX idx_sauto_br_mo (sauto_br, sauto_mo)"); echo "  ✓ parsing_cars idx_sauto_br_mo created\n"; }
        catch (\Throwable $e) { echo "  ✗ parsing_cars: ".$e->getMessage()."\n"; }
    }
    echo "\n  Re-run ?do=perftest to confirm the main query dropped to ~tens of ms.\n\nDone.\n";
    exit;
}

// ─────────────────────────── MODE: collfix ───────────────────────────
// The car_list index still isn't used on the `cl` JOIN because the collations
// differ: car_list.br/mo are utf8mb3_general_ci but parsing_cars.sauto_br/mo are
// utf8mb4_unicode_ci. MySQL can't use an index across incompatible collations, so
// it falls back to a full scan (10s). This converts sauto_br/sauto_mo to match
// car_list (utf8mb3_general_ci) so the composite index finally kicks in.
// Data-safe: values are plain latin brand/model names, no unicode loss expected.
if ($do === 'collfix') {
    $apply = ($_GET['apply'] ?? '') === '1';
    $targetColl = 'utf8mb3_general_ci';
    $targetChar = 'utf8mb3';

    // Guard: any sauto_br/sauto_mo value with bytes outside the mb3 range would be
    // corrupted by the conversion. Count them first; refuse if any exist.
    hr("safety scan");
    $bad = (int)$db->query("SELECT COUNT(*) FROM {$P}
        WHERE sauto_br <> CONVERT(sauto_br USING {$targetChar})
           OR sauto_mo <> CONVERT(sauto_mo USING {$targetChar})")->fetchColumn();
    echo "  rows with non-mb3 chars in sauto_br/sauto_mo: {$bad}\n";
    if ($bad > 0) { echo "\n  ⚠ conversion would lose data — refusing. Investigate these rows first.\n\nDone.\n"; exit; }
    echo "  safe to convert ✓\n";

    if (!$apply) { echo "\n  add &apply=1 to convert sauto_br/sauto_mo to {$targetColl}.\n\nDone.\n"; exit; }

    foreach (['sauto_br', 'sauto_mo'] as $col) {
        try {
            $db->exec("ALTER TABLE {$P} MODIFY {$col} VARCHAR(50) CHARACTER SET {$targetChar} COLLATE {$targetColl}");
            echo "  ✓ {$col} → {$targetColl}\n";
        } catch (\Throwable $e) { echo "  ✗ {$col}: ".$e->getMessage()."\n"; }
    }
    echo "\n  Re-run ?do=perftest — the cl JOIN should now be type=ref and the query ~tens of ms.\n\nDone.\n";
    exit;
}

// ─────────────────────────── MODE: expiredcount ───────────────────────────
// How many on_order cars have an EXPIRED timer right now (what update_expired_offers
// would delete). Read-only — run before triggering the cron so you know the impact.
if ($do === 'expiredcount') {
    $now = time();
    $n = (int)$db->query("SELECT COUNT(*) FROM {$C}
        WHERE catalog_type='on_order' AND offer_timer_end > 0 AND offer_timer_end < {$now}")->fetchColumn();
    hr("on_order cars with EXPIRED timer (would be deleted)");
    echo "  count: {$n}\n";
    foreach ($db->query("SELECT id, br_nm, mo_nm, FROM_UNIXTIME(offer_timer_end) te FROM {$C}
        WHERE catalog_type='on_order' AND offer_timer_end>0 AND offer_timer_end<{$now}
        ORDER BY offer_timer_end DESC LIMIT 15") as $r)
        echo "    #{$r['id']} {$r['br_nm']} {$r['mo_nm']}  expired {$r['te']}\n";
    echo "\nDone.\n"; exit;
}

// ─────────────────────────── MODE: carexists ───────────────────────────
// Verify whether a car still exists after an erase test: DB row, photos in car_pht,
// photo folder on disk, schedule rows. Usage: ?do=carexists&id=34215
if ($do === 'carexists') {
    $id = (int)($_GET['id'] ?? 0);
    if ($id <= 0) { echo "  pass &id=\n\nDone.\n"; exit; }
    $carImg = defined('_CAR_IMG') ? _CAR_IMG : 'media/images/upload/car';
    if ($carImg[0] !== '/' && !preg_match('#^[A-Za-z]:#', $carImg)) { $carImg = rtrim($_SERVER['DOCUMENT_ROOT'] ?? dirname(__DIR__), '/').'/'.ltrim($carImg, '/'); }
    hr("does car #{$id} still exist?");
    $row = $db->query("SELECT id, br_nm, mo_nm, p_path, n_a, catalog_type FROM {$C} WHERE id={$id}")->fetch(PDO::FETCH_ASSOC);
    if ($row) {
        echo "  car_ctlg row: EXISTS — {$row['br_nm']} {$row['mo_nm']}  n_a={$row['n_a']} ct={$row['catalog_type']}\n";
        $dir = rtrim($carImg,'/').'/'.$row['p_path'].'/'.$id;
        echo "  photo folder: ".(is_dir($dir) ? "EXISTS ({$dir})" : "gone")."\n";
    } else {
        echo "  car_ctlg row: DELETED ✓\n";
    }
    // Extra context: timer, and the parsing row's status (why the site may show it
    // as "not in stock" even when car_ctlg.n_a=0).
    if ($row) {
        $extra = $db->query("SELECT offer_timer_end, FROM_UNIXTIME(offer_timer_end) te FROM {$C} WHERE id={$id}")->fetch(PDO::FETCH_ASSOC);
        $now = time();
        $tstate = ((int)$extra['offer_timer_end'] > 0 && (int)$extra['offer_timer_end'] < $now) ? "EXPIRED" : (((int)$extra['offer_timer_end']>0)?"active":"none");
        echo "  offer_timer_end: {$extra['offer_timer_end']} ({$extra['te']})  → {$tstate}\n";
        $pc = $db->query("SELECT id, status FROM {$prefx}_parsing_cars WHERE car_ctlg_id={$id}")->fetch(PDO::FETCH_ASSOC);
        echo "  parsing status: ".($pc ? "{$pc['status']} (parsing #{$pc['id']})" : "(no parsing row)")."\n";
    }
    $pht = (int)$db->query("SELECT COUNT(*) FROM {$prefx}_car_pht WHERE it_id={$id}")->fetchColumn();
    $sch = 0;
    foreach ([$S999,$SFB,$STG] as $t) { try { $sch += (int)$db->query("SELECT COUNT(*) FROM {$t} WHERE car_id={$id}")->fetchColumn(); } catch(\Throwable $e){} }
    $seo = (int)$db->query("SELECT COUNT(*) FROM {$prefx}_seo2 WHERE it_id={$id} AND p1='cars'")->fetchColumn();
    echo "  car_pht rows: {$pht}".($pht?" (still there)":" ✓")."\n";
    echo "  schedule rows: {$sch}".($sch?" (still there)":" ✓")."\n";
    echo "  seo2 rows: {$seo}".($seo?" (still there)":" ✓")."\n";
    echo (!$row && !$pht && !$sch && !$seo) ? "\n  ✓ FULLY ERASED\n" : "\n  ⚠ some traces remain\n";
    echo "\nDone.\n"; exit;
}

// Inspect a single path: is it a file or dir, its size, type (via first bytes),
// mtime. Use to identify a mystery item like the 11GB ziw7xzs6. Read-only.
if ($do === 'whatis') {
    $root = rtrim($_SERVER['DOCUMENT_ROOT'] ?? dirname(__DIR__), '/');
    $rel = $_GET['path'] ?? '';
    $p = ($rel && $rel[0] === '/') ? $rel : $root.'/'.ltrim($rel,'/');
    hr("inspect: {$p}");
    if (!file_exists($p)) { echo "  does not exist\n\nDone.\n"; exit; }
    echo "  type: ".(is_dir($p)?'DIRECTORY':(is_link($p)?'SYMLINK':'FILE'))."\n";
    echo "  size: ".number_format((int)@filesize($p)/1073741824, 2)." GB (".number_format((int)@filesize($p))." bytes)\n";
    echo "  modified: ".date('Y-m-d H:i:s', (int)@filemtime($p))."\n";
    if (is_link($p)) echo "  → points to: ".readlink($p)."\n";
    if (is_file($p)) {
        $fh = @fopen($p, 'rb');
        if ($fh) {
            $head = fread($fh, 200); fclose($fh);
            // Detect common types by signature/first bytes.
            $sig = bin2hex(substr($head, 0, 4));
            $printable = preg_replace('/[^\x20-\x7E]/', '.', substr($head, 0, 120));
            echo "  first bytes (hex): {$sig}\n";
            echo "  first bytes (text): {$printable}\n";
            $type = 'unknown';
            if (strpos($head,'-- MySQL')!==false || stripos($head,'INSERT INTO')!==false || stripos($head,'CREATE TABLE')!==false) $type='SQL dump';
            elseif ($sig==='ffd8ffe0'||$sig==='ffd8ffe1') $type='JPEG image';
            elseif ($sig==='504b0304') $type='ZIP archive';
            elseif ($sig==='1f8b0800') $type='GZIP archive';
            elseif (strpos($printable,'[')===0 || strpos($printable,'{')===0) $type='JSON/log';
            elseif (preg_match('/^\[?\d{4}-\d\d-\d\d/', $printable)) $type='LOG file';
            echo "  likely type: {$type}\n";
        }
    }
    echo "\n  (to delete a junk file: I'll add a guarded delete once you confirm what it is)\n";
    echo "\nDone.\n"; exit;
}

// ─────────────────────────── MODE: heavyphotos ───────────────────────────
// Which cars have OVERSIZED photos (avg file > threshold KB) and how much recompress
// would save. Groups by parsing source to confirm OpenLane/eCarsTrade are the heavy
// ones. Read-only. &kb=400 sets the "heavy" threshold per file.
if ($do === 'heavyphotos') {
    $carImg = defined('_CAR_IMG') ? _CAR_IMG : 'media/images/upload/car';
    if ($carImg[0] !== '/' && !preg_match('#^[A-Za-z]:#', $carImg)) { $carImg = rtrim($_SERVER['DOCUMENT_ROOT'] ?? dirname(__DIR__), '/').'/'.ltrim($carImg, '/'); }
    $fmt = fn($b) => number_format($b/1073741824, 2).' GB';
    // Sample cars per source, measure avg /high/ file size.
    $rows = $db->query("SELECT cc.id, cc.p_path, COALESCE(pc.source,'manual') src
        FROM {$C} cc LEFT JOIN {$P} pc ON pc.car_ctlg_id = cc.id
        WHERE cc.p_path IS NOT NULL AND cc.p_path<>'' ORDER BY cc.id DESC LIMIT 600")->fetchAll(PDO::FETCH_ASSOC);
    $bySrc = []; // src => [bytes, files, cars]
    foreach ($rows as $r) {
        $hi = rtrim($carImg,'/').'/'.$r['p_path'].'/'.$r['id'].'/high';
        if (!is_dir($hi)) continue;
        $b = 0; $n = 0;
        foreach (array_diff(@scandir($hi)?:[], ['.','..']) as $f) { $b += (int)@filesize($hi.'/'.$f); $n++; }
        $s = $r['src'];
        $bySrc[$s]['bytes'] = ($bySrc[$s]['bytes'] ?? 0) + $b;
        $bySrc[$s]['files'] = ($bySrc[$s]['files'] ?? 0) + $n;
        $bySrc[$s]['cars']  = ($bySrc[$s]['cars'] ?? 0) + 1;
    }
    hr("avg /high/ photo size per source (sample)");
    foreach ($bySrc as $src => $d) {
        $avgFile = $d['files'] > 0 ? $d['bytes']/$d['files'] : 0;
        echo "  ".str_pad($src,12)." avg ".str_pad(number_format($avgFile/1024).' KB',10)
            ." ({$d['cars']} cars, {$d['files']} files)\n";
    }
    // Extrapolate potential saving if recompressed to ~250KB/file.
    hr("recompress potential (to ~250KB/file)");
    $target = 250*1024;
    $totCarsWithP = (int)$db->query("SELECT COUNT(*) FROM {$C} WHERE p_path IS NOT NULL AND p_path<>''")->fetchColumn();
    $sampleCars = array_sum(array_column($bySrc, 'cars'));
    $sampleBytes = array_sum(array_column($bySrc, 'bytes'));
    $sampleFiles = array_sum(array_column($bySrc, 'files'));
    if ($sampleCars > 0) {
        $factor = $totCarsWithP / $sampleCars;
        $estCurrent = $sampleBytes * $factor;
        $estAfter = $sampleFiles * $factor * $target;
        echo "  est. /high/ total now: ".$fmt($estCurrent)."\n";
        echo "  est. /high/ after recompress: ".$fmt($estAfter)."\n";
        echo "  POTENTIAL SAVING: ".$fmt(max(0,$estCurrent-$estAfter))."\n";
    }
    echo "\nDone.\n"; exit;
}

// ─────────────────────────── MODE: carfiles ───────────────────────────
// Show the actual files (with sizes) inside a few real car photo folders, so we can
// see where the space goes: /high/, /med/, and any large ORIGINAL files sitting
// directly in the car dir (unresized source photos = the hidden hog). Read-only.
if ($do === 'carfiles') {
    $carImg = defined('_CAR_IMG') ? _CAR_IMG : 'media/images/upload/car';
    if ($carImg[0] !== '/' && !preg_match('#^[A-Za-z]:#', $carImg)) { $carImg = rtrim($_SERVER['DOCUMENT_ROOT'] ?? dirname(__DIR__), '/').'/'.ltrim($carImg, '/'); }
    $dirSize = function(string $dir) use (&$dirSize): int {
        if (!is_dir($dir)) return 0; $s=0;
        foreach (array_diff(@scandir($dir)?:[], ['.','..']) as $f){ $p=$dir.'/'.$f; $s+=is_dir($p)?$dirSize($p):(int)@filesize($p);} return $s;
    };
    $mb = fn($b) => number_format($b/1048576, 2).' MB';
    // Sample: 5 biggest car folders in the heaviest month path (from car_ctlg).
    $rows = $db->query("SELECT id, p_path FROM {$C} WHERE p_path IS NOT NULL AND p_path<>'' ORDER BY id DESC LIMIT 8")->fetchAll(PDO::FETCH_ASSOC);
    foreach ($rows as $r) {
        $base = rtrim($carImg,'/').'/'.$r['p_path'].'/'.$r['id'];
        if (!is_dir($base)) continue;
        hr("car #{$r['id']}  ({$base})");
        // Files directly in the car dir (originals?), + subdir sizes.
        $directBytes = 0; $directCount = 0;
        foreach (array_diff(@scandir($base)?:[], ['.','..']) as $f) {
            $p = $base.'/'.$f;
            if (is_dir($p)) echo "    [dir] {$f}/  = ".$mb($dirSize($p))."\n";
            else { $directBytes += (int)@filesize($p); $directCount++; }
        }
        if ($directCount > 0) echo "    >>> {$directCount} loose file(s) directly in car dir = ".$mb($directBytes)." (originals?)\n";
    }
    echo "\nDone.\n"; exit;
}

// ─────────────────────────── MODE: diskusage ───────────────────────────
// ─────────────────────────── MODE: bigdirs ───────────────────────────
// Find what actually uses the disk: size of each top-level dir under the document
// root (and one level into media/), sorted biggest first. Read-only. Reveals the
// real space hog (may be logs, backups, tmp, offer images, not car photos).
if ($do === 'bigdirs') {
    $root = rtrim($_SERVER['DOCUMENT_ROOT'] ?? dirname(__DIR__), '/');
    $dirSize = function(string $dir) use (&$dirSize): int {
        if (!is_dir($dir)) return 0; $s = 0;
        $items = @scandir($dir); if ($items === false) return 0;
        foreach (array_diff($items, ['.','..']) as $f) {
            $p = $dir.'/'.$f;
            if (is_link($p)) continue; // don't follow symlinks
            $s += is_dir($p) ? $dirSize($p) : (int)@filesize($p);
        }
        return $s;
    };
    $fmt = fn($b) => number_format($b / 1073741824, 2).' GB';
    $depth = (int)($_GET['depth'] ?? 1);
    $scan = $_GET['dir'] ?? $root;
    if ($scan[0] !== '/' && !preg_match('#^[A-Za-z]:#', $scan)) $scan = $root.'/'.ltrim($scan,'/');

    hr("top-level dir sizes under: {$scan}");
    $items = @scandir($scan) ?: [];
    $sizes = [];
    foreach (array_diff($items, ['.','..']) as $f) {
        $p = $scan.'/'.$f;
        if (is_link($p)) continue;
        $sizes[$f] = is_dir($p) ? $dirSize($p) : (int)@filesize($p);
    }
    arsort($sizes);
    $tot = 0;
    foreach ($sizes as $name => $sz) { $tot += $sz; if ($sz > 10485760) echo "  ".str_pad($fmt($sz),12)." {$name}\n"; }
    echo "  ".str_repeat('-',30)."\n  ".str_pad($fmt($tot),12)." TOTAL (this level)\n";
    echo "\n  drill into one: ?do=bigdirs&dir=media/images/upload/car\n";
    echo "\nDone.\n"; exit;
}

// Measure REAL disk space used by car photos: total car-img dir, and how much the
// n_a=1 cars' photo folders occupy (what deletena would free). Read-only.
if ($do === 'diskusage') {
    $carImg = defined('_CAR_IMG') ? _CAR_IMG : 'media/images/upload/car'; if ($carImg[0] !== '/' && !preg_match('#^[A-Za-z]:#', $carImg)) { $carImg = rtrim($_SERVER['DOCUMENT_ROOT'] ?? dirname(__DIR__), '/').'/'.ltrim($carImg, '/'); }
    $dirSize = function(string $dir) use (&$dirSize): int {
        if (!is_dir($dir)) return 0;
        $s = 0;
        foreach (array_diff(scandir($dir) ?: [], ['.','..']) as $f) {
            $p = $dir.'/'.$f;
            $s += is_dir($p) ? $dirSize($p) : (int)@filesize($p);
        }
        return $s;
    };
    $fmt = fn($b) => number_format($b / 1073741824, 2).' GB';

    hr("disk usage of car photos");
    echo "  car-img dir: {$carImg}\n";
    // Total (can be slow on huge trees — sample-free full walk).
    if (($_GET['full'] ?? '') === '1') {
        echo "  TOTAL car-img size: ".$fmt($dirSize($carImg))."  (full walk)\n";
    } else {
        echo "  (add &full=1 for the whole car-img total — may be slow)\n";
    }

    // Size of n_a=1 cars' folders — measure a SAMPLE then extrapolate, so it's fast.
    $rows = $db->query("SELECT id, p_path FROM {$C} WHERE n_a=1 AND p_path IS NOT NULL AND p_path<>'' ORDER BY id DESC LIMIT 200")->fetchAll(PDO::FETCH_ASSOC);
    $sampleBytes = 0; $sampleWithDir = 0;
    foreach ($rows as $r) {
        $dir = rtrim($carImg,'/').'/'.$r['p_path'].'/'.$r['id'];
        if (is_dir($dir)) { $sampleBytes += $dirSize($dir); $sampleWithDir++; }
    }
    $totalNa = (int)$db->query("SELECT COUNT(*) FROM {$C} WHERE n_a=1")->fetchColumn();
    hr("n_a=1 photo footprint (from {$sampleWithDir}-car sample)");
    if ($sampleWithDir > 0) {
        $avg = $sampleBytes / $sampleWithDir;
        echo "  avg per car: ".number_format($avg/1048576, 1)." MB\n";
        echo "  sample total ({$sampleWithDir} cars): ".$fmt($sampleBytes)."\n";
        echo "  ESTIMATED for all {$totalNa} n_a=1 cars: ".$fmt($avg * $totalNa)."\n";
    } else {
        echo "  no photo dirs found in sample\n";
    }
    echo "\nDone.\n"; exit;
}

// ─────────────────────────── MODE: highvsmed ───────────────────────────
// Measure how much /high/ vs /med/ variants occupy across ALL car photo folders,
// from a sample of cars, extrapolated to the whole catalog. Shows how much deleting
// /high/ would free. Read-only.
if ($do === 'highvsmed') {
    $carImg = defined('_CAR_IMG') ? _CAR_IMG : 'media/images/upload/car';
    if ($carImg[0] !== '/' && !preg_match('#^[A-Za-z]:#', $carImg)) { $carImg = rtrim($_SERVER['DOCUMENT_ROOT'] ?? dirname(__DIR__), '/').'/'.ltrim($carImg, '/'); }
    $dirSize = function(string $dir) use (&$dirSize): int {
        if (!is_dir($dir)) return 0; $s = 0;
        foreach (array_diff(scandir($dir) ?: [], ['.','..']) as $f) { $p=$dir.'/'.$f; $s += is_dir($p)?$dirSize($p):(int)@filesize($p); }
        return $s;
    };
    $fmt = fn($b) => number_format($b / 1073741824, 2).' GB';
    $rows = $db->query("SELECT id, p_path FROM {$C} WHERE p_path IS NOT NULL AND p_path<>'' ORDER BY id DESC LIMIT 400")->fetchAll(PDO::FETCH_ASSOC);
    $totalCars = (int)$db->query("SELECT COUNT(*) FROM {$C} WHERE p_path IS NOT NULL AND p_path<>''")->fetchColumn();
    $high = 0; $med = 0; $n = 0;
    foreach ($rows as $r) {
        $base = rtrim($carImg,'/').'/'.$r['p_path'].'/'.$r['id'];
        if (!is_dir($base)) continue;
        $n++;
        $high += $dirSize($base.'/high');
        $med  += $dirSize($base.'/med');
    }
    hr("high vs med (sample {$n} cars, catalog {$totalCars})");
    if ($n > 0) {
        $factor = $totalCars / $n;
        echo "  /high/ sample: ".$fmt($high)."   → est. ALL: ".$fmt($high*$factor)."\n";
        echo "  /med/  sample: ".$fmt($med)."   → est. ALL: ".$fmt($med*$factor)."\n";
        echo "\n  Deleting /high/ everywhere would free ~".$fmt($high*$factor)." (keeping /med/ 800px).\n";
    } else echo "  no photo dirs in sample\n";
    echo "\nDone.\n"; exit;
}

// ─────────────────────────── MODE: footprint ───────────────────────────
// One page that answers "how much disk do we ACTUALLY need?" — everything counting
// toward the cPanel quota (docroot dirs, MySQL, mail/backups outside docroot), with
// car photos split into REFERENCED (must move to R2) vs ORPHAN (just delete). Ends
// with a verdict on whether a 100 GB plan fits. Read-only.
//   ?do=footprint            sampled — fast, ±10%
//   ?do=footprint&full=1     walk every file — exact, slow
//   &budget=300              seconds allowed for the heavy walks (default 150)
if ($do === 'footprint') {
    @set_time_limit(0);
    $root   = rtrim($_SERVER['DOCUMENT_ROOT'] ?? dirname(__DIR__), '/');
    $carImg = defined('_CAR_IMG') ? _CAR_IMG : 'media/images/upload/car';
    if ($carImg[0] !== '/' && !preg_match('#^[A-Za-z]:#', $carImg)) $carImg = $root.'/'.ltrim($carImg, '/');
    $full     = ($_GET['full'] ?? '') === '1';
    $deadline = time() + max(30, (int)($_GET['budget'] ?? 150));
    $G = fn($b) => number_format($b / 1073741824, 2).' GB';
    $M = fn($b) => number_format($b / 1048576, 1).' MB';
    $N = fn($n) => number_format($n);

    // Recursive size + file count in one pass. Skips symlinks so we never follow a
    // link out of the account and double-count.
    $walk = function(string $dir) use (&$walk): array {
        if (!is_dir($dir)) return [0, 0];
        $items = @scandir($dir); if ($items === false) return [0, 0];
        $b = 0; $f = 0;
        foreach ($items as $it) {
            if ($it === '.' || $it === '..') continue;
            $p = $dir.'/'.$it;
            if (is_link($p)) continue;
            if (is_dir($p)) { [$sb, $sf] = $walk($p); $b += $sb; $f += $sf; }
            else { $b += (int)@filesize($p); $f++; }
        }
        return [$b, $f];
    };

    // Same, but skipping one child — lets us size a parent without re-walking a
    // subtree already measured elsewhere (the car dir alone is ~1M files).
    $walkSkip = function(string $dir, array $skip) use ($walk): array {
        if (!is_dir($dir)) return [0, 0];
        $items = @scandir($dir); if ($items === false) return [0, 0];
        $b = 0; $f = 0;
        foreach ($items as $it) {
            if ($it === '.' || $it === '..' || in_array($it, $skip, true)) continue;
            $p = $dir.'/'.$it;
            if (is_link($p)) continue;
            if (is_dir($p)) { [$sb, $sf] = $walk($p); $b += $sb; $f += $sf; }
            else { $b += (int)@filesize($p); $f++; }
        }
        return [$b, $f];
    };

    echo "  mode: ".($full ? "FULL (exact)" : "SAMPLED (±10%, add &full=1 for exact)")."\n";

    // ── 1. Car photos: referenced vs orphan ───────────────────────────────────
    // Enumerate <year>/<month>/<car_id> dirs WITHOUT stat'ing files first — cheap.
    // A dir whose id is gone from car_ctlg is dead weight: it must not be migrated.
    $t0 = microtime(true);
    // car_id => LIST of dirs. The same car can own folders under two different
    // month paths (p_path changed after a re-upload), so keying id => one path
    // silently drops the duplicates and undercounts both size and file count.
    $onDisk = [];
    foreach (array_diff(@scandir($carImg) ?: [], ['.','..']) as $y) {
        $yp = $carImg.'/'.$y; if (!is_dir($yp)) continue;
        foreach (array_diff(@scandir($yp) ?: [], ['.','..']) as $m) {
            $mp = $yp.'/'.$m; if (!is_dir($mp)) continue;
            foreach (array_diff(@scandir($mp) ?: [], ['.','..']) as $cid) {
                if (!ctype_digit($cid)) continue;
                $p = $mp.'/'.$cid;
                if (is_dir($p)) $onDisk[(int)$cid][] = $p;
            }
        }
    }
    $dbIds = array_flip(array_map('intval', $db->query("SELECT id FROM {$C}")->fetchAll(PDO::FETCH_COLUMN)));
    $kept = []; $orphan = []; $dupIds = 0;
    foreach ($onDisk as $id => $paths) {
        if (count($paths) > 1) $dupIds++;
        foreach ($paths as $p) {
            if (isset($dbIds[$id])) $kept[] = $p; else $orphan[] = $p;
        }
    }
    $dirsTotal = count($kept) + count($orphan);

    // Size a set of dirs: all of them when &full=1, otherwise an evenly spread
    // sample extrapolated to the whole set (first-N would bias toward old cars).
    $sizeSet = function(array $dirs, int $sample) use ($walk, $full, $deadline): array {
        $tot = count($dirs);
        if ($tot === 0) return [0, 0, 0, 0];
        $step = $full ? 1 : max(1, (int)floor($tot / $sample));
        $b = 0; $f = 0; $n = 0;
        for ($i = 0; $i < $tot; $i += $step) {
            [$sb, $sf] = $walk($dirs[$i]); $b += $sb; $f += $sf; $n++;
            if (time() > $deadline) break; // budget hit — extrapolate from what we got
        }
        if ($n === 0) return [0, 0, 0, $tot];
        // Extrapolate whenever we didn't measure every folder (sampling OR early exit).
        return [(int)($b / $n * $tot), (int)($f / $n * $tot), $n, $tot];
    };

    [$keptB, $keptF, $keptN, $keptTot]   = $sizeSet($kept, 250);
    [$orphB, $orphF, $orphN, $orphTot]   = $sizeSet($orphan, 150);

    $phtRows = (int)$db->query("SELECT COUNT(*) FROM {$prefx}_car_pht")->fetchColumn();
    $carsDb  = count($dbIds);

    hr("CAR PHOTOS — ".basename($carImg)."/");
    echo "  photo folders on disk: ".$N($dirsTotal)."   (".$N(count($onDisk))." distinct car ids)\n";
    echo "  cars in car_ctlg:      ".$N($carsDb)."\n";
    echo "  car_pht photo rows:    ".$N($phtRows)."\n";
    if ($dupIds > 0) {
        echo "  ".$N($dupIds)." car id(s) own folders under MORE THAN ONE month path\n";
    }
    echo "\n";
    echo "  KEEP   (car still in DB): ".str_pad($N($keptTot).' folders', 20).str_pad($G($keptB), 12)
        .$N($keptF)." files".($full ? '' : "  [from {$keptN} sampled]")."\n";
    echo "  ORPHAN (car deleted):     ".str_pad($N($orphTot).' folders', 20).str_pad($G($orphB), 12)
        .$N($orphF)." files".($full ? '' : "  [from {$orphN} sampled]")."\n";
    echo "  ".str_repeat('-', 60)."\n";
    echo "  car-img TOTAL:            ".str_pad('', 20).str_pad($G($keptB + $orphB), 12).$N($keptF + $orphF)." files\n";
    printf("  (car photo scan: %.1fs)\n", microtime(true) - $t0);

    // ── 2. Junk that must NOT be migrated ─────────────────────────────────────
    // Parser galleries + proxy cache: nothing in the codebase ever deletes these.
    hr("JUNK — delete, never migrate");
    // tmp/parsing_imgcache is inside tmp, so tmp is measured with it excluded.
    $junk = [
        'uploads/parsing'      => [fn() => $walk($root.'/uploads/parsing'),
            'parser galleries (up to 50 full-size photos/car, incl. cars never published)'],
        'tmp/parsing_imgcache' => [fn() => $walk($root.'/tmp/parsing_imgcache'),
            'OpenLane proxy cache (7-day TTL only invalidates, never unlinks)'],
        'tmp (rest)'           => [fn() => $walkSkip($root.'/tmp', ['parsing_imgcache']),
            'temp files (orphaned pub_*/up_* when a publish dies mid-way)'],
        'logs'                 => [fn() => $walk($root.'/logs'), 'app logs'],
    ];
    $junkB = 0; $junkF = 0;
    foreach ($junk as $label => [$measure, $why]) {
        [$b, $f] = $measure();
        $junkB += $b; $junkF += $f;
        echo "  ".str_pad($label, 24).str_pad($G($b), 12).str_pad($N($f).' files', 16)."{$why}\n";
        if (time() > $deadline) { echo "  (budget spent — remaining junk dirs skipped)\n"; break; }
    }
    echo "  ".str_repeat('-', 60)."\n  ".str_pad('JUNK TOTAL', 24).str_pad($G($junkB), 12).$N($junkF)." files\n";

    // ── 3. Everything else in the docroot ─────────────────────────────────────
    hr("DOCROOT — other top-level dirs");
    $otherB = 0;
    foreach (array_diff(@scandir($root) ?: [], ['.','..']) as $f) {
        $p = $root.'/'.$f;
        if (is_link($p)) continue;
        if (in_array($f, ['media', 'uploads', 'tmp', 'logs'], true)) continue; // counted above
        $b = is_dir($p) ? $walk($p)[0] : (int)@filesize($p);
        $otherB += $b;
        if ($b > 52428800) echo "  ".str_pad($f, 24).$G($b)."\n";
        if (time() > $deadline) { echo "  (budget spent)\n"; break; }
    }
    // media/ minus the car dir = video, fonts, files, site graphics, tyres/offer/team.
    // Walked level by level with the car dir skipped — re-walking it would cost the
    // same ~1M stat calls the sampling above exists to avoid.
    $mediaRest = $walkSkip($root.'/media', ['images'])[0]
               + $walkSkip($root.'/media/images', ['upload'])[0]
               + $walkSkip($root.'/media/images/upload', ['car'])[0];
    echo "  ".str_pad('media/ (non-car)', 24).$G($mediaRest)."\n";
    $uploadsRest = $walkSkip($root.'/uploads', ['parsing'])[0]; // parsing counted in JUNK
    echo "  ".str_pad('uploads/ (crm, calc)', 24).$G($uploadsRest)."\n";
    echo "  ".str_repeat('-', 60)."\n  ".str_pad('OTHER TOTAL', 24).$G($otherB + $mediaRest + $uploadsRest)."\n";
    $otherAll = $otherB + $mediaRest + $uploadsRest;

    // ── 4. MySQL — counts toward the cPanel quota too ─────────────────────────
    hr("MYSQL (counts toward the cPanel quota)");
    $dbB = 0;
    try {
        $q = $db->query("SELECT table_name tn, data_length+index_length b, table_rows r
            FROM information_schema.tables WHERE table_schema = DATABASE()
            ORDER BY b DESC LIMIT 12");
        // Cast: information_schema returns NULL for some engines, and PHP 8.4
        // deprecates passing null to number_format().
        foreach ($q as $r) {
            echo "  ".str_pad($r['tn'], 38).str_pad($M((int)$r['b']), 12).$N((int)$r['r'])." rows (est)\n";
        }
        $dbB = (int)$db->query("SELECT SUM(data_length+index_length) FROM information_schema.tables
            WHERE table_schema = DATABASE()")->fetchColumn();
        echo "  ".str_repeat('-', 60)."\n  ".str_pad('DB TOTAL', 38).$G($dbB)."\n";
    } catch (\Throwable $e) {
        echo "  (information_schema not readable: ".$e->getMessage().")\n";
    }

    // ── 5. Outside the docroot — mail + backups are the classic hidden hog ────
    hr("OUTSIDE DOCROOT (mail, backups — often the surprise)");
    $home = dirname($root);
    $homeB = 0;
    $items = @scandir($home);
    if ($items === false) {
        echo "  {$home} not readable (open_basedir) — check cPanel → Disk Usage manually\n";
    } else {
        // Check the budget BEFORE each child, not after: mail/ can hold hundreds of
        // thousands of small files and would blow past the limit in a single walk.
        foreach (array_diff($items, ['.','..']) as $f) {
            $p = $home.'/'.$f;
            if (is_link($p) || $p === $root) continue;
            if (time() > $deadline) { echo "  (budget spent — {$f}/ and the rest not measured)\n"; break; }
            $b = is_dir($p) ? $walk($p)[0] : (int)@filesize($p);
            $homeB += $b;
            if ($b > 104857600) echo "  ".str_pad($f, 24).$G($b)."\n";
        }
        echo "  ".str_repeat('-', 60)."\n  ".str_pad('OUTSIDE TOTAL', 24).$G($homeB)."  (>100MB shown)\n";
    }

    // ── 6. Verdict ────────────────────────────────────────────────────────────
    $now      = $keptB + $orphB + $junkB + $otherAll + $dbB + $homeB;
    $afterJunk = $now - $junkB - $orphB;          // step 1: delete junk + orphans
    $afterR2   = $afterJunk - $keptB;             // step 2: car photos live in R2
    $objects   = $keptF;
    hr("VERDICT");
    echo "  NOW (everything above):            ".$G($now)."\n";
    echo "  after deleting junk + orphans:     ".$G($afterJunk)."   (frees ".$G($junkB + $orphB).")\n";
    echo "  after moving car photos to R2:     ".$G($afterR2)."   (frees another ".$G($keptB).")\n\n";
    echo "  100 GB plan after cleanup only:  ".($afterJunk < 100*1073741824 ? "FITS ✓" : "does NOT fit — R2 needed")."\n";
    echo "  100 GB plan after R2 migration:  ".($afterR2   < 100*1073741824 ? "FITS ✓" : "does NOT fit — look at MySQL/mail above")."\n";

    hr("R2 MIGRATION ESTIMATE");
    echo "  objects to upload: ".$N($objects)." files, ".$G($keptB)."\n";
    printf("  R2 storage cost:   \$%.2f/month  (\$0.015/GB, first 10 GB free, egress \$0)\n",
        max(0, ($keptB/1073741824) - 10) * 0.015);
    printf("  one-off write ops: \$%.2f  (Class A, \$4.50 per million PUTs)\n", $objects / 1000000 * 4.50);
    printf("  upload time @200ms/object: ~%.0f hours of background cron\n", $objects * 0.2 / 3600);
    echo "  NOTE: the server must send ".$G($keptB)." outbound — check the cPanel\n";
    echo "        bandwidth quota before starting, or split it across months.\n";
    echo "\nDone.\n"; exit;
}

// ─────────────────────────── MODE: r2test ───────────────────────────
// End-to-end check of the R2 setup before migrating anything: credentials load,
// PUT, HEAD, LIST and DELETE all work. Writes then removes one tiny test object.
// Also uploads one REAL car photo (&real=1) to prove key layout + content type.
if ($do === 'r2test') {
    hr("R2 connectivity test");
    $r2 = \App\Services\R2Client::fromEnv();
    if (!$r2) {
        echo "  ✗ credentials not found.\n";
        echo "    Expected in ".($_SERVER['DOCUMENT_ROOT'] ?? dirname(__DIR__))."/.env :\n";
        echo "      R2_ACCOUNT_ID, R2_ACCESS_KEY_ID, R2_SECRET_ACCESS_KEY, R2_BUCKET\n";
        echo "\nDone.\n"; exit;
    }
    echo "  ✓ credentials loaded from .env\n";

    $key  = '_r2test/' . date('Ymd_His') . '_' . bin2hex(random_bytes(4)) . '.txt';
    $body = 'sauto r2 test ' . date('c');

    echo "  PUT    {$key} ... ";
    if (!$r2->putObject($key, $body, 'text/plain')) {
        echo "FAILED\n\n  → check the Access Key / Secret / bucket name in .env,\n";
        echo "    and that the token has Object Read & Write on this bucket.\n\nDone.\n"; exit;
    }
    echo "ok\n";

    echo "  HEAD   ... ".($r2->exists($key) ? "ok (object is there)" : "FAILED")."\n";
    [$keys] = $r2->listObjects('_r2test/');
    echo "  LIST   ... ".count($keys)." object(s) under _r2test/\n";
    echo "  DELETE ... ".($r2->deleteObject($key) ? "ok" : "FAILED")."\n";
    echo "  HEAD   ... ".($r2->exists($key) ? "STILL THERE (delete did not work)" : "gone ✓")."\n";

    // deletePrefix is what erasing a car calls — a whole folder at once. Never
    // exercised by the migration, so prove it on throwaway keys.
    hr("prefix delete (what erasing a car uses)");
    $pfx = '_r2test/prefix_' . bin2hex(random_bytes(3)) . '/';
    foreach (['high/a.jpg', 'high/b.jpg', 'med/a.jpg'] as $f) {
        $r2->putObject($pfx . $f, 'x', 'image/jpeg');
    }
    [$before] = $r2->listObjects($pfx);
    echo "  wrote  ... ".count($before)." object(s) under {$pfx}\n";
    $gone = $r2->deletePrefix($pfx);
    [$after] = $r2->listObjects($pfx);
    echo "  deletePrefix() removed {$gone}, remaining: ".count($after)
        .(count($after) === 0 ? "  ✓" : "  ✗ LEFTOVERS")."\n";

    // Optional: push one real photo so we can eyeball the key layout in the dashboard.
    if (($_GET['real'] ?? '') === '1') {
        $carImg = defined('_CAR_IMG') ? _CAR_IMG : 'media/images/upload/car';
        if ($carImg[0] !== '/' && !preg_match('#^[A-Za-z]:#', $carImg)) {
            $carImg = rtrim($_SERVER['DOCUMENT_ROOT'] ?? dirname(__DIR__), '/').'/'.ltrim($carImg, '/');
        }
        $row = $db->query("SELECT p.path, p.name, p.ff, p.it_id FROM {$prefx}_car_pht p
            JOIN {$C} c ON c.id = p.it_id ORDER BY p.id DESC LIMIT 1")->fetch(PDO::FETCH_ASSOC);
        hr("real photo upload");
        if (!$row) { echo "  no car_pht rows found\n"; }
        else {
            $ff  = $row['ff'] ?: 'jpg';
            // Key mirrors the public URL path exactly, so the Worker can map
            // /media/images/upload/car/<key> → R2 object with no translation.
            $rel = $row['path'].'/'.$row['it_id'].'/high/'.$row['name'].'.'.$ff;
            $abs = rtrim($carImg,'/').'/'.$rel;
            echo "  local: {$abs}\n";
            if (!is_file($abs)) echo "  ✗ file not on disk — try another car\n";
            else {
                echo "  size:  ".number_format(filesize($abs)/1024, 1)." KB\n";
                echo "  key:   {$rel}\n";
                echo "  PUT    ... ".($r2->putFile($rel, $abs) ? "ok" : "FAILED")."\n";
                echo "  HEAD   ... ".($r2->exists($rel) ? "ok ✓" : "FAILED")."\n";
                echo "\n  Leave it there — the migration will overwrite the same key.\n";
            }
        }
    } else {
        echo "\n  Add &real=1 to also upload one real car photo as a layout check.\n";
    }
    echo "\nDone.\n"; exit;
}

// ─────────────────────────── MODE: r2gaps ───────────────────────────
// Which live cars have photos the DB knows about but R2 does not. Driven by
// car_pht, not the disk — after a local wipe the filesystem can no longer say
// what should exist, so the table is the only remaining source of truth.
//   ?do=r2gaps              report
//   &fix=1                  re-upload from disk where the file is still there
//   &limit=N                cars per run (default 4000)
if ($do === 'r2gaps') {
    @set_time_limit(0);
    $fix   = ($_GET['fix'] ?? '') === '1';
    $prune = ($_GET['prune'] ?? '') === '1';
    $limit = max(1, (int)($_GET['limit'] ?? 4000));
    $pruned = 0;
    $N = fn($n) => number_format($n);

    @ini_set('memory_limit', '512M');
    $r2 = \App\Services\R2Client::fromEnv();
    if (!$r2) { echo "  R2 not configured\n\nDone.\n"; exit; }
    $base = class_exists('\App\Services\CarPhotoR2') ? \App\Services\CarPhotoR2::base() : '';

    // Pick the car ids FIRST, newest last-published first — those are the ones at
    // risk. Pulling all ~613k car_pht rows at once exhausts the memory cap.
    $offset = max(0, (int)($_GET['offset'] ?? 0));
    $ids = $db->query("SELECT id FROM {$C} ORDER BY id DESC LIMIT {$limit} OFFSET {$offset}")
        ->fetchAll(PDO::FETCH_COLUMN);
    if (!$ids) { echo "  no cars\n\nDone.\n"; exit; }
    $idList = implode(',', array_map('intval', $ids));

    $rows = $db->query("SELECT it_id, path, name, ff FROM {$prefx}_car_pht
        WHERE it_id IN ({$idList}) ORDER BY it_id DESC, pos ASC")->fetchAll(PDO::FETCH_ASSOC);

    hr("cars whose photos are missing from R2");
    echo "  newest ".$N(count($ids))." car(s), ".$N(count($rows))." photo row(s)\n";

    // Group by car so we list each R2 prefix once instead of per photo.
    $byCar = [];
    foreach ($rows as $r) $byCar[(int)$r['it_id']][] = $r;
    echo "  cars with photos: ".$N(count($byCar))."\n\n";

    $checked = 0; $badCars = []; $missTotal = 0; $fixed = 0; $unfixable = 0;
    // LiteSpeed cuts the connection well before 200s, so keep each pass short and
    // page through with &offset instead.
    $stop = time() + 60;
    foreach ($byCar as $carId => $photos) {
        if (time() > $stop) { echo "  (stopped after {$checked} cars — continue with &offset=".($offset + $checked).")\n"; break; }
        $checked++;
        $pPath  = trim((string)$photos[0]['path'], '/');
        $prefix = $pPath . '/' . $carId . '/';

        $inR2 = []; $token = null;
        do {
            [$page, $token] = $r2->listObjects($prefix, $token);
            foreach ($page as $k => $sz) $inR2[substr($k, strlen($prefix))] = $sz;
        } while ($token !== null);

        $miss = [];
        foreach ($photos as $p) {
            $ff = $p['ff'] ?: 'jpg';
            foreach (['high', 'med'] as $sz) {
                $f = $sz . '/' . $p['name'] . '.' . $ff;
                if (!isset($inR2[$f])) $miss[] = $f;
            }
        }
        if (!$miss) continue;

        $missTotal += count($miss);
        $badCars[$carId] = count($miss);

        if ($fix && $base !== '') {
            foreach ($miss as $f) {
                $abs = $base . '/' . $prefix . $f;
                if (is_file($abs)) { if (\App\Services\CarPhotoR2::push($abs)) $fixed++; }
                else $unfixable++;
            }
        }

        // &prune=1: drop car_pht rows whose file is in neither place. The row
        // would otherwise render an <img> that 404s somewhere in the gallery.
        if ($prune) {
            $gone = [];
            foreach ($miss as $f) {
                if (is_file($base . '/' . $prefix . $f)) continue;
                $gone[basename($f, '.' . pathinfo($f, PATHINFO_EXTENSION))] = true;
            }
            foreach (array_keys($gone) as $name) {
                $del = $db->prepare("DELETE FROM {$prefx}_car_pht WHERE it_id = ? AND name = ?");
                $del->execute([$carId, $name]);
                $pruned += $del->rowCount();
            }
        }
    }

    echo "  cars checked      : ".$N($checked)."\n";
    echo "  cars with gaps    : ".$N(count($badCars))."\n";
    echo "  missing objects   : ".$N($missTotal)."\n";
    if ($fix) {
        echo "  re-uploaded       : ".$N($fixed)."\n";
        echo "  GONE (no local)   : ".$N($unfixable)."\n";
    }
    if ($prune) echo "  car_pht rows removed: ".$N($pruned)."\n";
    if ($badCars) {
        echo "\n  affected car ids (newest first):\n    ";
        echo implode(', ', array_slice(array_keys($badCars), 0, 40));
        if (count($badCars) > 40) echo ', … +'.(count($badCars) - 40).' more';
        echo "\n";
        if (!$fix) echo "\n  Add &fix=1 to re-upload the ones still on disk.\n";
    } else {
        echo "\n  ✓ every photo in car_pht has its object in R2\n";
    }
    echo "\nDone.\n"; exit;
}

// ─────────────────────────── MODE: r2push ───────────────────────────
// Upload ONE car's photo folder into R2. For cars published after the bulk
// migration, and as a quick fix whenever droplocal reports a gap.
//   ?do=r2push&id=123
if ($do === 'r2push') {
    $id = (int)($_GET['id'] ?? 0);
    if ($id <= 0) { echo "  pass &id=<car_ctlg id>\n\nDone.\n"; exit; }
    if (!class_exists('\App\Services\CarPhotoR2')) { echo "  CarPhotoR2 not uploaded\n\nDone.\n"; exit; }

    $p = (string)$db->query("SELECT p_path FROM {$C} WHERE id={$id}")->fetchColumn();
    if ($p === '') { echo "  car #{$id} not found or has no p_path\n\nDone.\n"; exit; }

    $dir = \App\Services\CarPhotoR2::base().'/'.trim($p, '/').'/'.$id;
    hr("push car #{$id} to R2");
    echo "  dir: {$dir}\n";
    if (!is_dir($dir)) { echo "  no local folder — nothing to upload\n\nDone.\n"; exit; }

    $n = \App\Services\CarPhotoR2::pushDir($dir);
    echo "  uploaded: {$n} file(s)\n";
    echo "\n  Verify with:  ?do=r2car&id={$id}\n";
    echo "\nDone.\n"; exit;
}

// ─────────────────────────── MODE: droplocal ───────────────────────────
// Delete local car photos that are safely in R2. This is THE irreversible step,
// so it never deletes on trust: for every single file it checks the object is in
// R2 with the same byte size, and one mismatch makes it skip that whole car.
//   ?do=droplocal&id=123          one car (dry-run)
//   ?do=droplocal&prefix=c92a/d72d  one month
//   ?do=droplocal&all=1           everything
//   &apply=1                      actually delete   &limit=N  &budget=240
if ($do === 'droplocal') {
    @set_time_limit(0);
    $apply  = ($_GET['apply'] ?? '') === '1';
    $limit  = max(1, (int)($_GET['limit'] ?? 200));
    $deadline = time() + max(30, (int)($_GET['budget'] ?? 240));
    $N = fn($n) => number_format($n);
    $G = fn($b) => number_format($b / 1073741824, 2).' GB';

    $r2 = \App\Services\R2Client::fromEnv();
    if (!$r2) { echo "  R2 not configured\n\nDone.\n"; exit; }

    $carImg = defined('_CAR_IMG') ? _CAR_IMG : 'media/images/upload/car';
    if ($carImg[0] !== '/' && !preg_match('#^[A-Za-z]:#', $carImg)) {
        $carImg = rtrim($_SERVER['DOCUMENT_ROOT'] ?? dirname(__DIR__), '/').'/'.ltrim($carImg, '/');
    }
    $carImg = rtrim(str_replace('\\', '/', $carImg), '/');

    // Which car folders are in scope.
    $targets = []; // relative 'y/m/id'
    $id     = (int)($_GET['id'] ?? 0);
    $prefix = trim((string)($_GET['prefix'] ?? ''), '/');
    if ($id > 0) {
        $p = (string)$db->query("SELECT p_path FROM {$C} WHERE id={$id}")->fetchColumn();
        if ($p === '') { echo "  car #{$id} has no p_path (deleted?)\n\nDone.\n"; exit; }
        $targets[] = trim($p, '/').'/'.$id;
    } else {
        $scan = $prefix !== '' ? [$prefix] : [];
        if (!$scan) {
            if (($_GET['all'] ?? '') !== '1') {
                echo "  pass &id=N, or &prefix=<y>/<m>, or &all=1\n\nDone.\n"; exit;
            }
            foreach (array_diff(@scandir($carImg) ?: [], ['.','..']) as $y) {
                if (!is_dir($carImg.'/'.$y)) continue;
                foreach (array_diff(@scandir($carImg.'/'.$y) ?: [], ['.','..']) as $m) {
                    if (is_dir($carImg.'/'.$y.'/'.$m)) $scan[] = $y.'/'.$m;
                }
            }
        }
        foreach ($scan as $pfx) {
            foreach (array_diff(@scandir($carImg.'/'.$pfx) ?: [], ['.','..']) as $cid) {
                if (ctype_digit($cid) && is_dir($carImg.'/'.$pfx.'/'.$cid)) $targets[] = $pfx.'/'.$cid;
            }
        }
    }

    hr($apply ? "DELETING local photos already in R2" : "DRY-RUN — what would be deleted (add &apply=1)");
    echo "  car folders in scope: ".$N(count($targets))."\n";
    if (!$targets) { echo "\n  nothing to do\n\nDone.\n"; exit; }

    $okCars = 0; $skipCars = 0; $files = 0; $bytes = 0; $problems = [];

    foreach ($targets as $i => $rel) {
        if ($i >= $limit || time() > $deadline) break;
        $dir = $carImg.'/'.$rel;
        if (!is_dir($dir)) continue;

        // Local inventory.
        $local = [];
        $walk = function (string $d, string $sub) use (&$walk, &$local) {
            foreach (array_diff(@scandir($d) ?: [], ['.','..']) as $f) {
                $p = $d.'/'.$f;
                if (is_link($p)) continue;
                if (is_dir($p)) { $walk($p, $sub.$f.'/'); continue; }
                $local[$sub.$f] = (int)@filesize($p);
            }
        };
        $walk($dir, '');
        if (!$local) continue;

        // R2 inventory for the same prefix.
        $inR2 = []; $token = null;
        do {
            [$page, $token] = $r2->listObjects($rel.'/', $token);
            foreach ($page as $k => $sz) $inR2[substr($k, strlen($rel) + 1)] = $sz;
        } while ($token !== null);

        // Every file must be present AND the same size. No exceptions.
        $bad = [];
        foreach ($local as $f => $sz) {
            if (!isset($inR2[$f]))      { $bad[] = "{$f} (missing in R2)"; continue; }
            if ($inR2[$f] !== $sz)      { $bad[] = "{$f} ({$sz}b local vs {$inR2[$f]}b in R2)"; }
        }
        if ($bad) {
            $skipCars++;
            if (count($problems) < 10) $problems[] = $rel.': '.$bad[0];
            continue;
        }

        $files += count($local);
        $bytes += array_sum($local);
        $okCars++;

        if ($apply) {
            $rm = function (string $d) use (&$rm) {
                foreach (array_diff(@scandir($d) ?: [], ['.','..']) as $f) {
                    $p = $d.'/'.$f;
                    is_dir($p) ? $rm($p) : @unlink($p);
                }
                @rmdir($d);
            };
            $rm($dir);
        }
    }

    echo "  verified fully in R2 : ".$N($okCars)." car(s), ".$N($files)." file(s), ".$G($bytes)."\n";
    echo "  SKIPPED (not safe)   : ".$N($skipCars)."\n";
    foreach ($problems as $p) echo "      {$p}\n";
    if ($skipCars) echo "    → these keep their local copies; re-run the migration verify pass first.\n";

    echo $apply
        ? "\n  ✓ deleted locally. Space freed: ".$G($bytes)."\n"
        : "\n  Nothing was deleted. Add &apply=1 to proceed.\n";
    echo "\nDone.\n"; exit;
}

// ─────────────────────────── MODE: delphoto ───────────────────────────
// Remove one photo from a car: the car_pht row plus every variant in R2
// (jpg/webp × high/med). Positions are renumbered so the gallery stays 1..N.
//   ?do=delphoto&id=9670&pos=15      dry-run    &apply=1 to delete
if ($do === 'delphoto') {
    $id  = (int)($_GET['id'] ?? 0);
    $pos = (int)($_GET['pos'] ?? 0);
    $apply = ($_GET['apply'] ?? '') === '1';
    if ($id <= 0) { echo "  pass &id=<car_ctlg id>&pos=<n>\n\nDone.\n"; exit; }

    $rows = $db->query("SELECT id, name, ff, pos, main, path FROM {$prefx}_car_pht
        WHERE it_id = {$id} ORDER BY pos, id")->fetchAll(PDO::FETCH_ASSOC);
    hr("photos of car #{$id}");
    foreach ($rows as $r) {
        printf("  pos %-4s %s%s.%s\n", $r['pos'], $r['main'] ? '★ ' : '  ', $r['name'], $r['ff']);
    }
    if (!$rows) { echo "  (no photos)\n\nDone.\n"; exit; }
    if ($pos <= 0) { echo "\n  pass &pos=<n> to pick one\n\nDone.\n"; exit; }

    $target = null;
    foreach ($rows as $r) if ((int)$r['pos'] === $pos) { $target = $r; break; }
    if (!$target) { echo "\n  no photo at pos {$pos}\n\nDone.\n"; exit; }

    hr(($apply ? "DELETING" : "DRY-RUN — would delete")." pos {$pos}");
    echo "  name: {$target['name']}.{$target['ff']}".($target['main'] ? "   ★ this is the cover" : '')."\n";

    // Every variant of that photo, whatever sizes and formats exist.
    $r2 = \App\Services\R2Client::fromEnv();
    $keys = [];
    if ($r2) {
        $prefix = trim((string)$target['path'], '/').'/'.$id.'/';
        $token = null;
        do {
            [$page, $token] = $r2->listObjects($prefix, $token);
            foreach (array_keys($page) as $k) {
                if (pathinfo($k, PATHINFO_FILENAME) === $target['name']) $keys[] = $k;
            }
        } while ($token !== null);
    }
    echo "  R2 objects to remove: ".count($keys)."\n";
    foreach ($keys as $k) echo "    {$k}\n";

    if (!$apply) { echo "\n  Add &apply=1 to delete.\n\nDone.\n"; exit; }

    foreach ($keys as $k) $r2->deleteObject($k);
    $db->prepare("DELETE FROM {$prefx}_car_pht WHERE id = ?")->execute([$target['id']]);

    // Renumber, and hand the cover to the new first photo if we removed it.
    $left = $db->query("SELECT id FROM {$prefx}_car_pht WHERE it_id = {$id} ORDER BY pos, id")
        ->fetchAll(PDO::FETCH_COLUMN);
    $upd = $db->prepare("UPDATE {$prefx}_car_pht SET pos = ?, main = ? WHERE id = ?");
    $n = 0;
    foreach ($left as $rid) { $n++; $upd->execute([$n, $n === 1 ? 1 : 0, $rid]); }

    echo "\n  ✓ deleted the photo and ".count($keys)." R2 object(s)\n";
    echo "  ✓ {$n} photo(s) left, renumbered 1..{$n}\n";
    echo "\nDone.\n"; exit;
}

// ─────────────────────────── MODE: fixpath ───────────────────────────
// Rebuild car_pht rows for a car whose photos are in R2 but whose DB rows are
// gone or unusable. Written for cars whose p_path carried a trailing slash: the
// folder migrated fine, but every URL came out with a double slash and my
// depth check mistook the extra slash for a third path segment.
//   ?do=fixpath&id=9670        dry-run    &apply=1 to write
if ($do === 'fixpath') {
    $id = (int)($_GET['id'] ?? 0);
    $apply = ($_GET['apply'] ?? '') === '1';
    if ($id <= 0) { echo "  pass &id=<car_ctlg id>\n\nDone.\n"; exit; }

    $r2 = \App\Services\R2Client::fromEnv();
    if (!$r2) { echo "  R2 not configured\n\nDone.\n"; exit; }

    $car = $db->query("SELECT id, br_nm, mo_nm, p_path FROM {$C} WHERE id={$id}")->fetch(PDO::FETCH_ASSOC);
    if (!$car) { echo "  car #{$id} not in car_ctlg\n\nDone.\n"; exit; }

    $clean  = trim((string)$car['p_path'], '/');   // the trailing slash goes
    $prefix = $clean . '/' . $id . '/';
    hr("rebuild photos for #{$id} — {$car['br_nm']} {$car['mo_nm']}");
    echo "  p_path in DB : '".$car['p_path']."'".($car['p_path'] !== $clean ? "   ← has a stray slash" : '')."\n";
    echo "  R2 prefix    : {$prefix}\n";

    $inR2 = []; $token = null;
    do {
        [$page, $token] = $r2->listObjects($prefix, $token);
        foreach ($page as $k => $sz) $inR2[substr($k, strlen($prefix))] = $sz;
    } while ($token !== null);
    echo "  objects in R2: ".count($inR2)."\n";

    // One row per photo name. The old scheme stored jpg and webp side by side;
    // car_pht holds a single row per name, the site picks the format per browser.
    // Scan BOTH size folders: a photo present only in one of them still deserves
    // a row, and taking just high/ silently dropped one here.
    $names = []; $perDir = [];
    foreach (array_keys($inR2) as $f) {
        $slash = strpos($f, '/');
        if ($slash === false) continue;
        $dir = substr($f, 0, $slash);
        if ($dir !== 'high' && $dir !== 'med') continue;
        $perDir[$dir] = ($perDir[$dir] ?? 0) + 1;
        $base = pathinfo(substr($f, $slash + 1), PATHINFO_FILENAME);
        $ext  = strtolower(pathinfo($f, PATHINFO_EXTENSION));
        // Images only. Every folder also carries an index.html put there as
        // directory-listing protection, and without this it became "photo 15".
        if ($base === '' || !in_array($ext, ['jpg', 'jpeg', 'png', 'webp', 'gif'], true)) continue;
        if (!isset($names[$base]) || $ext === 'jpg') $names[$base] = $ext;
    }
    ksort($names);
    foreach ($perDir as $d => $c) echo "  {$d}/: {$c} file(s)\n";
    echo "  distinct photos: ".count($names)."\n";

    $have = (int)$db->query("SELECT COUNT(*) FROM {$prefx}_car_pht WHERE it_id={$id}")->fetchColumn();
    echo "  car_pht rows now: {$have}\n";
    if (!$names) { echo "\n  nothing to rebuild\n\nDone.\n"; exit; }

    foreach (array_slice(array_keys($names), 0, 5) as $n) echo "    {$n}.{$names[$n]}\n";

    if (!$apply) {
        echo "\n  Would insert ".count($names)." row(s) with path='{$clean}'";
        echo ($car['p_path'] !== $clean ? " and fix car_ctlg.p_path.\n" : ".\n");
        echo "  Add &apply=1 to write.\n\nDone.\n"; exit;
    }

    $db->prepare("DELETE FROM {$prefx}_car_pht WHERE it_id = ?")->execute([$id]);
    $ins = $db->prepare("INSERT INTO {$prefx}_car_pht
        (`it_id`,`tp`,`path`,`name`,`ff`,`main`,`pos`) VALUES (?,?,?,?,?,?,?)");
    $pos = 0;
    foreach ($names as $n => $ext) {
        $pos++;
        $ins->execute([$id, 'img', $clean, $n, $ext, $pos === 1 ? 1 : 0, $pos]);
    }
    if ($car['p_path'] !== $clean) {
        $db->prepare("UPDATE {$C} SET p_path = ? WHERE id = ?")->execute([$clean, $id]);
        echo "  ✓ p_path corrected to '{$clean}'\n";
    }
    echo "  ✓ inserted {$pos} car_pht row(s)\n";
    echo "\n  Check the car page now.\n";
    echo "\nDone.\n"; exit;
}

// ─────────────────────────── MODE: pathdepth ───────────────────────────
// How deep are the p_path values in car_pht? The migration walked exactly
// <year>/<month>/<car_id> and skipped anything else, so older cars stored under
// a three-segment path were never copied to R2 — and were then deleted locally.
// This counts the damage. ?do=pathdepth   &sample=1 to test R2 for a few keys
if ($do === 'pathdepth') {
    $N = fn($n) => number_format($n);

    hr("car_pht rows by path depth");
    $rows = $db->query("SELECT
            LENGTH(path) - LENGTH(REPLACE(path, '/', '')) AS slashes,
            COUNT(DISTINCT it_id) cars, COUNT(*) photos
        FROM {$prefx}_car_pht GROUP BY slashes ORDER BY slashes")->fetchAll(PDO::FETCH_ASSOC);
    foreach ($rows as $r) {
        $seg = (int)$r['slashes'] + 1;
        $note = $seg === 2 ? 'normal — migrated' : 'NOT migrated, photos lost';
        printf("  %d segment(s): %8s car(s), %9s photo(s)   %s\n",
            $seg, $N($r['cars']), $N($r['photos']), $note);
    }

    hr("affected cars still live in car_ctlg");
    $rows = $db->query("SELECT c.catalog_type, c.n_a, COUNT(DISTINCT c.id) cars
        FROM {$C} c JOIN {$prefx}_car_pht p ON p.it_id = c.id
        WHERE LENGTH(p.path) - LENGTH(REPLACE(p.path, '/', '')) <> 1
        GROUP BY c.catalog_type, c.n_a ORDER BY cars DESC")->fetchAll(PDO::FETCH_ASSOC);
    if (!$rows) echo "  none — every affected car is already deleted\n";
    foreach ($rows as $r) {
        printf("  %-10s n_a=%-3s %s car(s)\n", $r['catalog_type'], $r['n_a'], $N($r['cars']));
    }

    hr("example paths");
    foreach ($db->query("SELECT DISTINCT path FROM {$prefx}_car_pht
        WHERE LENGTH(path) - LENGTH(REPLACE(path, '/', '')) <> 1 LIMIT 8") as $r) {
        echo "  {$r['path']}\n";
    }

    // Are any of them actually still in R2? (They should not be, but prove it.)
    if (($_GET['sample'] ?? '') === '1') {
        hr("R2 check on a few of them");
        $r2 = \App\Services\R2Client::fromEnv();
        if (!$r2) { echo "  R2 not configured\n"; }
        else {
            $rows = $db->query("SELECT it_id, path, name, ff FROM {$prefx}_car_pht
                WHERE LENGTH(path) - LENGTH(REPLACE(path, '/', '')) <> 1 LIMIT 10")->fetchAll(PDO::FETCH_ASSOC);
            foreach ($rows as $r) {
                $key = trim($r['path'], '/').'/'.$r['it_id'].'/high/'.$r['name'].'.'.($r['ff'] ?: 'jpg');
                echo "  ".($r2->exists($key) ? 'IN R2 ' : 'MISSING')."  {$key}\n";
            }
        }
    } else {
        echo "\n  Add &sample=1 to test a few keys against R2.\n";
    }

    hr("the affected cars");
    $rows = $db->query("SELECT c.id, c.br_nm, c.mo_nm, c.yr, c.vis, c.act,
            COUNT(p.id) photos, FROM_UNIXTIME(c.date, '%Y-%m-%d') added
        FROM {$C} c JOIN {$prefx}_car_pht p ON p.it_id = c.id
        WHERE LENGTH(p.path) - LENGTH(REPLACE(p.path, '/', '')) <> 1
        GROUP BY c.id ORDER BY c.id")->fetchAll(PDO::FETCH_ASSOC);
    printf("  %-8s %-14s %-16s %-6s %-5s %-8s %s\n", 'id', 'brand', 'model', 'year', 'vis', 'photos', 'added');
    foreach ($rows as $r) {
        printf("  %-8d %-14s %-16s %-6s %-5s %-8d %s\n", $r['id'],
            substr((string)$r['br_nm'], 0, 14), substr((string)$r['mo_nm'], 0, 16),
            $r['yr'], $r['vis'], $r['photos'], $r['added']);
    }

    // The files are gone from disk AND from R2, so the rows only make the page
    // render <img> tags that 404. Removing them is strictly an improvement.
    if (($_GET['prune'] ?? '') === '1') {
        $del = $db->exec("DELETE p FROM {$prefx}_car_pht p
            WHERE LENGTH(p.path) - LENGTH(REPLACE(p.path, '/', '')) <> 1");
        echo "\n  ✓ removed ".$N((int)$del)." dead car_pht row(s) — no more broken images\n";
        echo "\nDone.\n"; exit;
    }

    // Erase the cars outright. Scoped to exactly the 3-segment-path set, never
    // the general "n_a=1 in_stock" population, which is far larger.
    if (($_GET['erase'] ?? '') === '1') {
        $apply = ($_GET['apply'] ?? '') === '1';

        // Only cars whose photos are ALL lost. A car that also has working
        // (2-segment) photos must not be erased — deleting it would throw away
        // pictures that are perfectly fine in R2.
        $ids = $db->query("SELECT c.id FROM {$C} c JOIN {$prefx}_car_pht p ON p.it_id = c.id
            GROUP BY c.id
            HAVING SUM(LENGTH(p.path) - LENGTH(REPLACE(p.path, '/', '')) = 1) = 0")
            ->fetchAll(PDO::FETCH_COLUMN);

        $mixed = $db->query("SELECT c.id FROM {$C} c JOIN {$prefx}_car_pht p ON p.it_id = c.id
            GROUP BY c.id
            HAVING SUM(LENGTH(p.path) - LENGTH(REPLACE(p.path, '/', '')) = 1) > 0
               AND SUM(LENGTH(p.path) - LENGTH(REPLACE(p.path, '/', '')) <> 1) > 0")
            ->fetchAll(PDO::FETCH_COLUMN);

        hr($apply ? "ERASING cars whose photos are all gone" : "DRY-RUN — cars that would be erased");
        echo "  cars with NO usable photo left: ".$N(count($ids))."\n";
        if ($mixed) {
            echo "  ⚠ ".$N(count($mixed))." car(s) have both lost AND working photos — NOT erased:\n";
            echo "      ".implode(', ', array_slice($mixed, 0, 20))."\n";
            echo "    Use &prune=1 instead to drop just their dead rows.\n";
        }
        if (!$apply) {
            echo "\n  Nothing changed. Add &apply=1 to erase.\n";
            echo "  This removes car_ctlg, car_pht, seo2, schedule rows, the photo\n";
            echo "  folder and any R2 objects. It is NOT reversible, and the ".count($ids)."\n";
            echo "  public pages will start returning 404.\n";
            echo "\nDone.\n"; exit;
        }

        $carImg = defined('_CAR_IMG') ? _CAR_IMG : 'media/images/upload/car';
        if ($carImg[0] !== '/' && !preg_match('#^[A-Za-z]:#', $carImg)) {
            $carImg = rtrim($_SERVER['DOCUMENT_ROOT'] ?? dirname(__DIR__), '/').'/'.ltrim($carImg, '/');
        }
        $ok = 0; $fail = 0;
        foreach ($ids as $id) {
            $r = \App\Services\CarEraser::erase($db, $prefx, (int)$id, $carImg);
            $r['ok'] ? $ok++ : $fail++;
        }
        echo "  erased: ".$N($ok)."\n";
        if ($fail) echo "  failed: ".$N($fail)."\n";
        echo "\nDone.\n"; exit;
    }

    echo "\n  Two options:\n";
    echo "    &prune=1   remove only the dead photo rows — pages stay, without a gallery\n";
    echo "    &erase=1   remove the cars entirely (dry-run; add &apply=1 to confirm)\n";
    echo "\nDone.\n"; exit;
}

// ─────────────────────────── MODE: pubqueue ───────────────────────────
// Why auto-publish fails while the same car goes through fine from the edit
// form. Shows the queue state and, more importantly, groups the stored error
// messages so a pattern is visible instead of one-off reports.
//   ?do=pubqueue        &limit=15 for the newest failures
if ($do === 'pubqueue') {
    $limit = max(1, min(60, (int)($_GET['limit'] ?? 15)));
    $Q = "{$prefx}_parsing_publish_queue";
    $N = fn($n) => number_format($n);

    hr("queue state");
    try {
        foreach ($db->query("SELECT status, COUNT(*) c FROM {$Q} GROUP BY status ORDER BY c DESC") as $r) {
            echo "  ".str_pad($r['status'], 12).$N($r['c'])."\n";
        }
    } catch (\Throwable $e) { echo "  table unavailable: ".$e->getMessage()."\n\nDone.\n"; exit; }

    // The same reason repeated a hundred times is one bug, not a hundred.
    // Is the queue draining or filling? A growing backlog is not an error, but it
    // is the thing people mistake for one.
    hr("throughput, last 6 hours");
    try {
        $rows = $db->query("SELECT DATE_FORMAT(finished_at,'%H:00') h, COUNT(*) c,
                AVG(TIMESTAMPDIFF(SECOND, started_at, finished_at)) secs
            FROM {$Q} WHERE status='done' AND finished_at >= NOW() - INTERVAL 6 HOUR
            GROUP BY h ORDER BY h")->fetchAll(PDO::FETCH_ASSOC);
        if (!$rows) echo "  (nothing finished in the last 6h)\n";
        foreach ($rows as $r) {
            printf("  %s  %4d published  ~%0.0fs each\n", $r['h'], $r['c'], (float)$r['secs']);
        }
        $pend = (int)$db->query("SELECT COUNT(*) FROM {$Q} WHERE status='pending'")->fetchColumn();
        $lastH = $rows ? (int)end($rows)['c'] : 0;
        if ($lastH > 0) {
            printf("\n  at the current rate, %s pending would take ~%0.1f h to clear\n",
                $N($pend), $pend / $lastH);
        }
    } catch (\Throwable $e) { echo "  (rate unavailable: ".$e->getMessage().")\n"; }

    hr("failure reasons, most common first");
    $rows = $db->query("SELECT error, COUNT(*) c, MAX(finished_at) last_seen
        FROM {$Q} WHERE status = 'failed' AND error IS NOT NULL AND error <> ''
        GROUP BY error ORDER BY c DESC LIMIT 20")->fetchAll(PDO::FETCH_ASSOC);
    if (!$rows) echo "  (no failed jobs with a stored error)\n";
    foreach ($rows as $r) {
        echo "  ".str_pad('×'.$r['c'], 8)." last ".($r['last_seen'] ?: '?')."\n";
        echo "      ".substr(str_replace(["\n", "\r"], ' ', (string)$r['error']), 0, 200)."\n";
    }

    // Retrying jobs carry an error too, and those are the ones still moving.
    hr("pending jobs that already errored once");
    $rows = $db->query("SELECT error, COUNT(*) c FROM {$Q}
        WHERE status = 'pending' AND error IS NOT NULL AND error <> ''
        GROUP BY error ORDER BY c DESC LIMIT 10")->fetchAll(PDO::FETCH_ASSOC);
    if (!$rows) echo "  (none)\n";
    foreach ($rows as $r) {
        echo "  ×".str_pad($r['c'], 6).substr(str_replace(["\n", "\r"], ' ', (string)$r['error']), 0, 180)."\n";
    }

    hr("newest failures, one line each");
    $rows = $db->query("SELECT id, parsing_car_id, car_ctlg_id, attempts, finished_at, error
        FROM {$Q} WHERE status = 'failed' ORDER BY finished_at DESC LIMIT {$limit}")->fetchAll(PDO::FETCH_ASSOC);
    foreach ($rows as $r) {
        printf("  job %-7s parsing %-8s attempts %-3s %s\n    %s\n",
            $r['id'], $r['parsing_car_id'], $r['attempts'], $r['finished_at'] ?: '?',
            substr(str_replace(["\n", "\r"], ' ', (string)$r['error']), 0, 180));
    }
    if (!$rows) echo "  (none)\n";
    echo "\nDone.\n"; exit;
}

// ─────────────────────────── MODE: photocount ───────────────────────────
// Where photos are lost between the source and the sauto ad. Shows, per source,
// how many URLs the parsing row holds versus how many rows ended up in car_pht,
// so a cap can be traced to the adapter, the enrichment, or ParsingPublisher.
//   ?do=photocount           newest published cars per source
//   &source=auto1            one source   &limit=8   &id=<car_ctlg id>
if ($do === 'photocount') {
    $limit  = max(1, min(40, (int)($_GET['limit'] ?? 8)));
    $only   = trim((string)($_GET['source'] ?? ''));
    $id     = (int)($_GET['id'] ?? 0);

    $sources = $only !== '' ? [$only] : ['encar', 'ecarstrade', 'openlane', 'auto1'];
    if ($id > 0) {
        $r = $db->query("SELECT pc.source FROM {$P} pc WHERE pc.car_ctlg_id = {$id} LIMIT 1")->fetchColumn();
        $sources = [$r ?: 'unknown'];
    }

    echo "  sauto cap now: Encar = ALL, eCarsTrade/OpenLane/Auto1 = 20\n";
    echo "  999 cap:       10 for every parsing car\n";

    foreach ($sources as $src) {
        hr($src);
        $where = $id > 0 ? "pc.car_ctlg_id = {$id}" : "pc.source = ".$db->quote($src)." AND pc.car_ctlg_id > 0";
        $rows = $db->query("SELECT pc.car_ctlg_id cid, pc.images_local, cc.date
            FROM {$P} pc JOIN {$C} cc ON cc.id = pc.car_ctlg_id
            WHERE {$where} ORDER BY pc.car_ctlg_id DESC LIMIT {$limit}")->fetchAll(PDO::FETCH_ASSOC);
        if (!$rows) { echo "  (no published cars)\n"; continue; }

        printf("  %-8s %-10s %-9s %-9s %s\n", 'car', 'published', 'urls', 'car_pht', 'verdict');
        foreach ($rows as $r) {
            $cid  = (int)$r['cid'];
            $imgs = json_decode($r['images_local'] ?? '[]', true);
            $urls = is_array($imgs) ? count($imgs) : 0;
            $pht  = (int)$db->query("SELECT COUNT(*) FROM {$prefx}_car_pht WHERE it_id = {$cid}")->fetchColumn();
            $when = $r['date'] > 0 ? date('m-d H:i', (int)$r['date']) : '?';

            // Fewer URLs than photos on the ad means the source itself gave us
            // that many — no cap in our code can raise it.
            $verdict = $urls === 0 ? 'no urls stored'
                     : ($pht >= $urls ? 'took everything the source had'
                     : 'capped at publish time ('.$pht.' of '.$urls.')');
            printf("  %-8d %-10s %-9d %-9d %s\n", $cid, $when, $urls, $pht, $verdict);
        }
    }
    echo "\n  urls    = entries in parsing_cars.images_local (what we have from the source)\n";
    echo "  car_pht = photos actually on the sauto ad\n";
    echo "  Equal numbers mean the SOURCE is the limit, not our cap.\n";
    echo "\nDone.\n"; exit;
}

// ─────────────────────────── MODE: dedupqueue ───────────────────────────
// Remove duplicate PENDING schedule rows — the same car queued more than once on
// the same channel, which happens when the crosspost button is clicked twice and
// would post the identical car several times over. Keeps the earliest row.
// Dry-run unless &apply=1.
if ($do === 'dedupqueue') {
    $apply = ($_GET['apply'] ?? '') === '1';
    $N = fn($n) => number_format($n);

    // 999 republishes cars on a cycle, so several pending rows for one car at
    // DIFFERENT dates are legitimate there. Default to the social channels only;
    // &chan= picks explicitly.
    $all = ['fb' => ['Facebook', $SFB, 'scheduled_date', 'scheduled_time'],
            'tg' => ['Telegram', $STG, 'scheduled_date', 'scheduled_time'],
            '999' => ['999.md',  $S999, 'schedule_date',  'schedule_time']];
    $pick = trim((string)($_GET['chan'] ?? 'fb,tg'));
    $chans = [];
    foreach (explode(',', $pick) as $k) if (isset($all[trim($k)])) $chans[] = $all[trim($k)];
    if (!$chans) { echo "  &chan= must be fb, tg, 999 or a comma list\n\nDone.\n"; exit; }
    echo "  channels: {$pick}   (999 excluded by default — it republishes on a cycle)\n";
    $total = 0;

    foreach ($chans as [$name, $tbl, $dCol, $tCol]) {
        hr($name);
        try {
            // Group pending rows per car; anything past the first is a duplicate.
            $rows = $db->query("SELECT car_id, COUNT(*) c, MIN(id) keep,
                    COUNT(DISTINCT CONCAT({$dCol},' ',{$tCol})) slots
                FROM {$tbl} WHERE status='pending'
                GROUP BY car_id HAVING c > 1 ORDER BY c DESC")->fetchAll(PDO::FETCH_ASSOC);
            if (!$rows) { echo "  ✓ no duplicates\n"; continue; }

            $extra = 0;
            foreach ($rows as $r) $extra += (int)$r['c'] - 1;
            echo "  cars queued more than once: ".$N(count($rows))."\n";
            echo "  redundant rows            : ".$N($extra)."\n";
            foreach (array_slice($rows, 0, 10) as $r) {
                // slots == 1 means every copy is at the SAME moment: a real
                // double-click. slots > 1 means spread dates — likely intentional.
                $flag = (int)$r['slots'] === 1 ? 'same time — true duplicate' : "{$r['slots']} different times — CHECK";
                echo "    car ".str_pad($r['car_id'], 7)." × {$r['c']}  ({$flag})\n";
            }

            if ($apply) {
                $del = $db->prepare("DELETE FROM {$tbl}
                    WHERE status='pending' AND car_id = ? AND id <> ?");
                $n = 0;
                foreach ($rows as $r) { $del->execute([$r['car_id'], $r['keep']]); $n += $del->rowCount(); }
                echo "  → deleted ".$N($n)." row(s)\n";
                $total += $n;
            } else {
                $total += $extra;
            }
        } catch (\Throwable $e) {
            echo "  table unavailable: ".$e->getMessage()."\n";
        }
    }

    echo $apply
        ? "\n  ✓ removed ".$N($total)." duplicate row(s)\n"
        : "\n  ".$N($total)." duplicate row(s) would be removed. Add &apply=1.\n";
    echo "\nDone.\n"; exit;
}

// ─────────────────────────── MODE: queue ───────────────────────────
// What is waiting to be published on each channel and WHEN. Answers the usual
// "I pressed the button ten minutes ago and nothing happened" — posts are given
// a spread-out time, so pending simply means the hour has not come yet.
//   ?do=queue            &id=<car_ctlg id> to look at one car
if ($do === 'queue') {
    $id  = (int)($_GET['id'] ?? 0);
    $now = (string)$db->query("SELECT NOW()")->fetchColumn();
    $N   = fn($n) => number_format($n);

    $chans = [
        'Facebook' => [$SFB, 'scheduled_date', 'scheduled_time'],
        'Telegram' => [$STG, 'scheduled_date', 'scheduled_time'],
        '999.md'   => [$S999, 'schedule_date',  'schedule_time'],
    ];

    foreach ($chans as $name => [$tbl, $dCol, $tCol]) {
        hr($name);
        try {
            $where = $id > 0 ? " AND car_id = {$id}" : '';
            $due  = (int)$db->query("SELECT COUNT(*) FROM {$tbl}
                WHERE status='pending' AND CONCAT({$dCol},' ',{$tCol}) <= '{$now}'{$where}")->fetchColumn();
            $later = (int)$db->query("SELECT COUNT(*) FROM {$tbl}
                WHERE status='pending' AND CONCAT({$dCol},' ',{$tCol}) > '{$now}'{$where}")->fetchColumn();
            echo "  due now (cron will take these): ".$N($due)."\n";
            echo "  scheduled for later          : ".$N($later)."\n";

            $rows = $db->query("SELECT car_id, {$dCol} d, {$tCol} t, status FROM {$tbl}
                WHERE status='pending'{$where} ORDER BY {$dCol}, {$tCol} LIMIT 8")->fetchAll(PDO::FETCH_ASSOC);
            foreach ($rows as $r) {
                $when = $r['d'].' '.$r['t'];
                echo "    car ".str_pad($r['car_id'], 7)." → {$when}  ".($when <= $now ? '(due)' : '(waiting)')."\n";
            }
            if (!$rows) echo "    (nothing pending".($id ? " for car {$id}" : '').")\n";
        } catch (\Throwable $e) {
            echo "  table unavailable: ".$e->getMessage()."\n";
        }
    }
    echo "\n  server time: {$now}\n";
    echo "  Posts get a spread-out time (App/Helper/RandomTimeHelper), so a fresh\n";
    echo "  one normally waits. 'due now' > 0 with nothing publishing = a real problem.\n";
    echo "\nDone.\n"; exit;
}

// ─────────────────────────── MODE: r2svc ───────────────────────────
// Exercise CarPhotoR2 in isolation before it is wired into the admin: path
// resolution, key mapping, and — the part that actually matters — reading a
// photo whose local copy is gone, straight from R2. Read-only apart from one
// temp file it cleans up itself. ?do=r2svc
if ($do === 'r2svc') {
    $S = '\App\Services\CarPhotoR2';
    hr("CarPhotoR2 self-test");
    if (!class_exists($S)) { echo "  ✗ class not found — is App/Services/CarPhotoR2.php uploaded?\n\nDone.\n"; exit; }
    echo "  ✓ class loads\n";
    echo "  base: ".$S::base()."\n";

    // A real photo to work with.
    $row = $db->query("SELECT p.path, p.name, p.ff, p.it_id FROM {$prefx}_car_pht p
        JOIN {$C} c ON c.id = p.it_id ORDER BY p.id DESC LIMIT 1")->fetch(PDO::FETCH_ASSOC);
    if (!$row) { echo "  no car_pht rows\n\nDone.\n"; exit; }
    $rel = $row['path'].'/'.$row['it_id'].'/high/'.$row['name'].'.'.($row['ff'] ?: 'jpg');

    // 1. Path resolution — the uploads pass a relative path, parsing an absolute one.
    $relInput = (defined('_CAR_IMG') ? _CAR_IMG : 'media/images/upload/car').'/'.$rel;
    $absInput = $S::base().'/'.$rel;
    hr("path handling");
    echo "  relative in : {$relInput}\n";
    echo "    → abs     : ".$S::abs($relInput)."\n";
    echo "    → key     : ".$S::keyFor($relInput)."\n";
    echo "  absolute in : {$absInput}\n";
    echo "    → key     : ".$S::keyFor($absInput)."\n";
    // sauto_personal_cron builds __DIR__.'/../media/...', so the key has to
    // survive a ".." in the middle — that leftover is what broke 999 uploads.
    // base() is <docroot>/media/images/upload/car, hence four levels up.
    $dotdot = dirname($S::base(), 4).'/console/../media/images/upload/car/'.$rel;
    echo "  with ../    : {$dotdot}\n";
    echo "    → key     : ".($S::keyFor($dotdot) ?: '(EMPTY — normalisation broken)')."\n";
    echo "  ".($S::keyFor($relInput) === $rel && $S::keyFor($absInput) === $rel && $S::keyFor($dotdot) === $rel
        ? "✓ all three forms map to the same key: {$rel}"
        : "✗ MISMATCH — expected {$rel}")."\n";

    // 2. Local read.
    hr("read while the file is still on disk");
    echo "  exists(): ".($S::exists($absInput) ? 'true' : 'false')."\n";
    $b = $S::read($absInput);
    echo "  read():   ".($b === null ? 'NULL ✗' : number_format(strlen($b)).' bytes ✓')."\n";

    // 3. The real test: a key that exists in R2 with no file on disk. Those are
    // the cars deleted after their photos were copied — exactly the situation
    // every read site will be in once the local copies are removed.
    hr("read a photo that is NOT on disk (the post-cleanup case)");
    $r2 = \App\Services\R2Client::fromEnv();
    $orphanKey = null;
    if ($r2) {
        $token = null; $scanned = 0;
        do {
            [$page, $token] = $r2->listObjects(substr($rel, 0, 10), $token);
            foreach ($page as $k => $sz) {
                $scanned++;
                if (!is_file($S::base().'/'.$k)) { $orphanKey = $k; break 2; }
            }
        } while ($token !== null && $scanned < 5000);
    }
    if ($orphanKey === null) {
        echo "  (no R2 object without a local file found in the sample — skipped)\n";
    } else {
        $p = $S::base().'/'.$orphanKey;
        echo "  key: {$orphanKey}\n";
        echo "  on disk: ".(is_file($p) ? 'yes' : 'NO — good, this is the case we want')."\n";
        $b2 = $S::read($p);
        echo "  read():  ".($b2 === null ? 'NULL ✗ fallback FAILED' : number_format(strlen($b2)).' bytes ✓ came from R2')."\n";
        $tmp = $S::localCopy($p);
        echo "  localCopy(): ".($tmp === null ? 'NULL ✗' : $tmp.' ✓')."\n";
        if ($tmp && is_file($tmp)) echo "  temp size: ".number_format(filesize($tmp))." bytes (removed on shutdown)\n";
    }
    echo "\nDone.\n"; exit;
}

// ─────────────────────────── MODE: r2car ───────────────────────────
// Compare ONE car's photos on disk against R2. Use it after publishing/uploading
// to prove the write path mirrors into the bucket. ?do=r2car&id=<car_ctlg id>
if ($do === 'r2car') {
    $id = (int)($_GET['id'] ?? 0);
    if ($id <= 0) { echo "  pass &id=<car_ctlg id>\n\nDone.\n"; exit; }
    $r2 = \App\Services\R2Client::fromEnv();
    if (!$r2) { echo "  R2 not configured (.env)\n\nDone.\n"; exit; }

    $row = $db->query("SELECT id, br_nm, mo_nm, p_path FROM {$C} WHERE id={$id}")->fetch(PDO::FETCH_ASSOC);
    if (!$row) { echo "  car #{$id} not in car_ctlg\n\nDone.\n"; exit; }

    $carImg = defined('_CAR_IMG') ? _CAR_IMG : 'media/images/upload/car';
    if ($carImg[0] !== '/' && !preg_match('#^[A-Za-z]:#', $carImg)) {
        $carImg = rtrim($_SERVER['DOCUMENT_ROOT'] ?? dirname(__DIR__), '/').'/'.ltrim($carImg, '/');
    }
    $prefix = trim((string)$row['p_path'], '/').'/'.$id.'/';
    $dir    = rtrim($carImg, '/').'/'.$prefix;

    hr("car #{$id} — {$row['br_nm']} {$row['mo_nm']}");
    echo "  prefix: {$prefix}\n";

    // Disk side.
    $disk = [];
    $walk = function (string $d, string $rel) use (&$walk, &$disk) {
        foreach (array_diff(@scandir($d) ?: [], ['.','..']) as $f) {
            $p = $d.'/'.$f;
            if (is_dir($p)) { $walk($p, $rel.$f.'/'); continue; }
            $disk[$rel.$f] = (int)@filesize($p);
        }
    };
    if (is_dir($dir)) $walk(rtrim($dir, '/'), '');

    // R2 side.
    $inR2 = []; $token = null;
    do {
        [$page, $token] = $r2->listObjects($prefix, $token);
        foreach ($page as $k => $sz) $inR2[substr($k, strlen($prefix))] = $sz;
    } while ($token !== null);

    echo "  on disk: ".count($disk)." file(s)\n";
    echo "  in R2:   ".count($inR2)." object(s)\n\n";

    $missing = array_diff_key($disk, $inR2);
    $extra   = array_diff_key($inR2, $disk);
    $sizeBad = [];
    foreach ($disk as $k => $sz) if (isset($inR2[$k]) && $inR2[$k] !== $sz) $sizeBad[] = $k;

    if (!$missing && !$sizeBad) echo "  ✓ every local file is in R2, same size\n";
    else {
        if ($missing) { echo "  ✗ MISSING from R2 (".count($missing)."):\n";
            foreach (array_slice(array_keys($missing), 0, 10) as $k) echo "      {$k}\n"; }
        if ($sizeBad) { echo "  ✗ size mismatch (".count($sizeBad)."):\n";
            foreach (array_slice($sizeBad, 0, 10) as $k) echo "      {$k}\n"; }
    }
    if ($extra) {
        echo "  ⓘ in R2 but not on disk (".count($extra)."): normal only if the local\n";
        echo "    copies were already deleted for this car.\n";
        foreach (array_slice(array_keys($extra), 0, 5) as $k) echo "      {$k}\n";
    }
    echo "\nDone.\n"; exit;
}

// ─────────────────────────── MODE: fiximgs ───────────────────────────
// Rewrite parsing_cars.images_local from local uploads/parsing/ file references to
// the original remote URLs kept in raw_data, so that folder can be deleted without
// blanking the /parsing catalog. Dry-run unless &apply=1. &limit=N (default 2000).
if ($do === 'fiximgs') {
    @set_time_limit(0);
    $apply = ($_GET['apply'] ?? '') === '1';
    $limit = max(1, (int)($_GET['limit'] ?? 2000));
    $N = fn($n) => number_format($n);

    $total = (int)$db->query("SELECT COUNT(*) FROM {$P} WHERE images_local LIKE '%\"path\"%'")->fetchColumn();
    hr($apply ? "REWRITING images_local → remote URLs" : "DRY-RUN — images_local still pointing at local files");
    echo "  rows referencing uploads/parsing files: ".$N($total)."\n";
    if ($total === 0) { echo "\n  ✓ nothing to fix — uploads/parsing is safe to delete\n\nDone.\n"; exit; }

    $rows = $db->query("SELECT id, raw_data, images_local FROM {$P}
        WHERE images_local LIKE '%\"path\"%' LIMIT {$limit}")->fetchAll(PDO::FETCH_ASSOC);

    $upd = $db->prepare("UPDATE {$P} SET images_local = ? WHERE id = ?");
    $fixed = 0; $noUrls = []; $sample = null;
    foreach ($rows as $r) {
        $raw  = json_decode($r['raw_data'] ?? '{}', true);
        $urls = $raw['images'] ?? [];
        // raw_data is the only place the original CDN links survive; without it the
        // row keeps its local paths and must be left alone rather than blanked.
        if (!is_array($urls) || empty($urls)) { $noUrls[] = (int)$r['id']; continue; }
        $json = json_encode(array_map(fn($u) => ['url' => $u], array_values($urls)), JSON_UNESCAPED_UNICODE);
        if ($sample === null) $sample = ['id' => (int)$r['id'], 'n' => count($urls), 'first' => (string)$urls[0]];
        if ($apply) $upd->execute([$json, (int)$r['id']]);
        $fixed++;
    }

    echo "  this batch: ".$N(count($rows))."\n";
    echo ($apply ? "  rewritten: " : "  would rewrite: ").$N($fixed)."\n";
    if ($sample) {
        echo "  sample #{$sample['id']}: {$sample['n']} image(s), first → {$sample['first']}\n";
    }
    if ($noUrls) {
        echo "  ⚠ ".$N(count($noUrls))." row(s) have NO urls in raw_data — left as-is:\n";
        echo "      ids: ".implode(', ', array_slice($noUrls, 0, 10))."\n";
        echo "      → their cards lose the photo if uploads/parsing is deleted.\n";
    }
    $left = $total - ($apply ? $fixed : 0);
    echo "  remaining after this pass: ".$N(max(0, $left))."\n";
    if (!$apply) {
        echo "\n  To apply:  ?token=cron2026&do=fiximgs&apply=1&limit={$limit}\n";
        echo "  Nothing was changed now.\n";
    } elseif ($left > 0) {
        echo "\n  → re-run to continue.\n";
    } else {
        echo "\n  ✓ done — uploads/parsing can now be deleted.\n";
    }
    echo "\nDone.\n"; exit;
}

// ─────────────────────────── MODE: orphans ───────────────────────────
// Delete photo folders whose car no longer exists in car_ctlg — dead weight left by
// the delete paths that dropped the DB row but not the files. Dry-run unless &apply=1.
//   ?do=orphans                  dry-run: counts, size, sample of ids
//   ?do=orphans&apply=1          delete one batch (&limit=N, default 400)
//   &budget=240                  seconds per run before stopping cleanly
// Safety: every id is re-checked against car_ctlg immediately before its folder goes,
// every path must resolve inside the car-img tree, and folders whose id still has
// car_pht rows are reported instead of deleted (that's a DB inconsistency, not junk).
if ($do === 'orphans') {
    @set_time_limit(0);
    $apply    = ($_GET['apply'] ?? '') === '1';
    $limit    = max(1, (int)($_GET['limit'] ?? 400));
    $deadline = time() + max(30, (int)($_GET['budget'] ?? 240));
    $carImg = defined('_CAR_IMG') ? _CAR_IMG : 'media/images/upload/car';
    if ($carImg[0] !== '/' && !preg_match('#^[A-Za-z]:#', $carImg)) {
        $carImg = rtrim($_SERVER['DOCUMENT_ROOT'] ?? dirname(__DIR__), '/').'/'.ltrim($carImg, '/');
    }
    $norm     = fn(string $p) => str_replace('\\', '/', $p);
    $realBase = realpath($carImg);
    if ($realBase === false) { echo "  car-img base not found: {$carImg}\n\nDone.\n"; exit; }
    $realBase = rtrim($norm($realBase), '/');

    $G = fn($b) => number_format($b / 1073741824, 2).' GB';
    $N = fn($n) => number_format($n);

    // Enumerate <year>/<month>/<car_id> without stat'ing files — cheap.
    // car_id => LIST of dirs: one car can own folders under two month paths, and
    // keying id => one path deletes only the last one while the rest survive.
    $onDisk = [];
    foreach (array_diff(@scandir($carImg) ?: [], ['.','..']) as $y) {
        $yp = $carImg.'/'.$y; if (!is_dir($yp)) continue;
        foreach (array_diff(@scandir($yp) ?: [], ['.','..']) as $m) {
            $mp = $yp.'/'.$m; if (!is_dir($mp)) continue;
            foreach (array_diff(@scandir($mp) ?: [], ['.','..']) as $cid) {
                if (!ctype_digit($cid)) continue;
                $p = $mp.'/'.$cid;
                if (is_dir($p)) $onDisk[(int)$cid][] = $p;
            }
        }
    }
    $dbIds  = array_flip(array_map('intval', $db->query("SELECT id FROM {$C}")->fetchAll(PDO::FETCH_COLUMN)));
    $phtIds = array_flip(array_map('intval', $db->query("SELECT DISTINCT it_id FROM {$prefx}_car_pht")->fetchAll(PDO::FETCH_COLUMN)));

    $orphans = []; $inconsistent = []; $orphanDirs = 0; $dupIds = 0; $dirsTotal = 0;
    foreach ($onDisk as $id => $paths) {
        $dirsTotal += count($paths);
        if (isset($dbIds[$id])) continue;                           // car alive → keep
        if (isset($phtIds[$id])) { $inconsistent[$id] = $paths; continue; } // photos w/o car row
        $orphans[$id] = $paths;
        $orphanDirs += count($paths);
        if (count($paths) > 1) $dupIds++;
    }

    // Recursive delete that also reports what it freed.
    $rmCount = function(string $dir) use (&$rmCount): array {
        $b = 0; $f = 0;
        foreach (array_diff(@scandir($dir) ?: [], ['.','..']) as $it) {
            $p = $dir.'/'.$it;
            if (is_link($p)) { @unlink($p); continue; }
            if (is_dir($p)) { [$sb, $sf] = $rmCount($p); $b += $sb; $f += $sf; }
            else { $b += (int)@filesize($p); if (@unlink($p)) $f++; }
        }
        @rmdir($dir);
        return [$b, $f];
    };

    hr($apply ? "DELETING orphan photo folders" : "DRY-RUN — orphan photo folders (add &apply=1)");
    echo "  photo folders on disk: ".$N($dirsTotal)."   (".$N(count($onDisk))." distinct car ids)\n";
    echo "  cars in car_ctlg:      ".$N(count($dbIds))."\n";
    echo "  ORPHAN car ids:        ".$N(count($orphans))."\n";
    echo "  ORPHAN folders:        ".$N($orphanDirs).($dupIds > 0
        ? "   (".$N($dupIds)." id(s) own more than one folder)" : '')."\n";
    if ($inconsistent) {
        echo "  ⚠ skipped (car_pht rows exist but no car_ctlg row): ".$N(count($inconsistent))."\n";
        echo "    ids: ".implode(', ', array_slice(array_keys($inconsistent), 0, 10))."\n";
        echo "    → DB inconsistency, not junk. Left untouched on purpose.\n";
    }
    if (!$orphans) { echo "\n  ✓ nothing to clean\n\nDone.\n"; exit; }

    // Flat list of every orphan dir, used for both sizing and deleting.
    $flat = [];
    foreach ($orphans as $id => $paths) { foreach ($paths as $p) $flat[] = [$id, $p]; }

    if (!$apply) {
        // Size an evenly spread sample and extrapolate — walking 18k folders is slow.
        $tot = count($flat);
        $step = max(1, (int)floor($tot / 150));
        $sb = 0; $sn = 0;
        $walk = function(string $d) use (&$walk): int {
            if (!is_dir($d)) return 0; $s = 0;
            foreach (array_diff(@scandir($d) ?: [], ['.','..']) as $f) {
                $p = $d.'/'.$f; $s += is_dir($p) ? $walk($p) : (int)@filesize($p);
            }
            return $s;
        };
        for ($i = 0; $i < $tot; $i += $step) { $sb += $walk($flat[$i][1]); $sn++; if (time() > $deadline) break; }
        $est = $sn > 0 ? (int)($sb / $sn * $tot) : 0;
        echo "\n  estimated space to free: ".$G($est)."   [from {$sn} sampled folders]\n";
        echo "  sample ids: ".implode(', ', array_slice(array_keys($orphans), 0, 12))."\n";
        echo "\n  To delete, run in batches:\n";
        echo "    ?token=cron2026&do=orphans&apply=1&limit={$limit}\n";
        echo "  Re-run until it reports 0 remaining. Nothing was changed now.\n";
        echo "\nDone.\n"; exit;
    }

    // ── apply ───────────────────────────────────────────────────────────────
    $recheck = $db->prepare("SELECT 1 FROM {$C} WHERE id = ? LIMIT 1");
    $alive   = [];   // ids re-checked this run, so we query each id once
    $done = 0; $freedB = 0; $freedF = 0; $skipped = 0; $failed = []; $processed = 0;

    foreach ($flat as [$id, $dir]) {
        if ($done >= $limit || time() > $deadline) break;
        $processed++;

        // Re-check live: the listing is a snapshot and a car could have been
        // (re)created since. Cached per id — one car can have several folders.
        if (!array_key_exists($id, $alive)) {
            $recheck->execute([$id]);
            $alive[$id] = (bool)$recheck->fetchColumn();
        }
        if ($alive[$id]) { $skipped++; continue; }

        // Never delete outside the car-img tree, whatever the listing said.
        $real = realpath($dir);
        if ($real === false) { $skipped++; continue; }
        $real = $norm($real);
        if (strpos($real, $realBase.'/') !== 0) { $skipped++; continue; }

        [$b, $f] = $rmCount($dir);
        // Verify it actually went. rmdir() fails silently on a non-empty or
        // read-only dir, and a silent failure here is what made the first run
        // report folders as deleted while they were still on disk.
        if (is_dir($dir)) { $failed[] = $dir; continue; }
        $freedB += $b; $freedF += $f; $done++;
    }

    $remaining = count($flat) - $processed;
    echo "\n  deleted: ".$N($done)." folder(s), ".$N($freedF)." file(s), ".$G($freedB)." freed\n";
    if ($skipped) echo "  skipped (car reappeared or path check failed): ".$N($skipped)."\n";
    if ($failed) {
        echo "  ⚠ FAILED to remove ".$N(count($failed))." folder(s) — still on disk:\n";
        foreach (array_slice($failed, 0, 5) as $d) echo "      {$d}\n";
        echo "    → likely a permissions problem. Send me one of these paths.\n";
    }
    echo "  remaining in this listing: ".$N(max(0, $remaining))."\n";
    echo $remaining > 0
        ? "\n  → re-run the same URL to continue.\n"
        : "\n  ✓ this listing is done — re-run the dry-run to confirm.\n";
    echo "\nDone.\n"; exit;
}

// ─────────────────────────── MODE: deletena ───────────────────────────
// PERMANENTLY delete every car marked out-of-stock (n_a=1) — both in_stock and
// on_order — including DB rows (car_ctlg, car_pht, seo2, schedule rows) AND the
// photo folder on disk. IRREVERSIBLE. Does NOT touch the 999.md ad (kept, per user).
// Dry-run unless &apply=1. Processes in batches; &limit=N (default 300).
if ($do === 'deletena') {
    $apply = (($_GET['apply'] ?? '') === '1');
    $limit = max(1, (int)($_GET['limit'] ?? 300));
    $carImg = defined('_CAR_IMG') ? _CAR_IMG : 'media/images/upload/car'; if ($carImg[0] !== '/' && !preg_match('#^[A-Za-z]:#', $carImg)) { $carImg = rtrim($_SERVER['DOCUMENT_ROOT'] ?? dirname(__DIR__), '/').'/'.ltrim($carImg, '/'); }

    // Recursive dir delete (mirrors the admin erase).
    $rmdirRec = function(string $dir) use (&$rmdirRec) {
        if (!is_dir($dir)) return;
        foreach (array_diff(scandir($dir) ?: [], ['.','..']) as $f) {
            $p = $dir.'/'.$f;
            is_dir($p) ? $rmdirRec($p) : @unlink($p);
        }
        @rmdir($dir);
    };

    // Catalog-type filter. Default 'on_order' so we NEVER delete in_stock cars by
    // accident. Pass &ct=all to include everything, or &ct=in_stock for only stock.
    $ct = $_GET['ct'] ?? 'on_order';
    $ctWhere = '';
    if ($ct === 'on_order')  $ctWhere = " AND catalog_type = 'on_order'";
    elseif ($ct === 'in_stock') $ctWhere = " AND catalog_type = 'in_stock'";
    // 'all' → no extra filter.

    hr(($apply ? "PERMANENTLY DELETE" : "DRY-RUN")." n_a=1 cars — catalog_type={$ct}");
    $total = (int)$db->query("SELECT COUNT(*) FROM {$C} WHERE n_a = 1{$ctWhere}")->fetchColumn();
    echo "  cars with n_a=1 (ct={$ct}): {$total}\n";
    // Breakdown so we don't blindly delete thousands.
    if (!$apply && ($_GET['breakdown'] ?? '') === '1') {
        hr("breakdown of n_a=1 cars");
        foreach ($db->query("SELECT gr, catalog_type, COUNT(*) c FROM {$C} WHERE n_a=1 GROUP BY gr, catalog_type ORDER BY c DESC") as $r)
            echo "  gr={$r['gr']} catalog_type={$r['catalog_type']} → {$r['c']}\n";
        $withPht = (int)$db->query("SELECT COUNT(DISTINCT it_id) FROM {$prefx}_car_pht WHERE it_id IN (SELECT id FROM {$C} WHERE n_a=1)")->fetchColumn();
        $on999 = (int)$db->query("SELECT COUNT(*) FROM {$C} WHERE n_a=1 AND `999_id`>0")->fetchColumn();
        $fromParsing = (int)$db->query("SELECT COUNT(*) FROM {$C} WHERE n_a=1 AND parsing_id IS NOT NULL")->fetchColumn();
        echo "\n  with photos in car_pht: {$withPht}\n";
        echo "  live on 999 (999_id>0): {$on999}\n";
        echo "  from parsing (parsing_id set): {$fromParsing}\n";
        // Year range to spot very old cars.
        $yr = $db->query("SELECT MIN(yr) mn, MAX(yr) mx FROM {$C} WHERE n_a=1 AND yr>0")->fetch(PDO::FETCH_ASSOC);
        $dt = $db->query("SELECT FROM_UNIXTIME(MIN(date)) mn, FROM_UNIXTIME(MAX(date)) mx FROM {$C} WHERE n_a=1 AND date>0")->fetch(PDO::FETCH_ASSOC);
        echo "  car years: {$yr['mn']}–{$yr['mx']}   added dates: {$dt['mn']} … {$dt['mx']}\n";
        echo "\nDone.\n"; exit;
    }
    $rows = $db->query("SELECT id, p_path, gr, `999_id` FROM {$C} WHERE n_a = 1{$ctWhere} ORDER BY id LIMIT {$limit}")
        ->fetchAll(PDO::FETCH_ASSOC);
    echo "  this batch: ".count($rows)."\n";

    if (!$apply) {
        foreach (array_slice($rows, 0, 10) as $r) {
            $dir = rtrim($carImg,'/').'/'.$r['p_path'].'/'.$r['id'];
            $has = is_dir($dir) ? 'photos-on-disk' : 'no-photo-dir';
            echo "    #{$r['id']} gr={$r['gr']} 999_id={$r['999_id']}  {$has}\n";
        }
        echo "\n  IRREVERSIBLE. Add &apply=1 to delete this batch (re-run until 0 left).\n\nDone.\n";
        exit;
    }

    $del = 0; $photosDel = 0;
    foreach ($rows as $r) {
        $id = (int)$r['id'];
        // 1. Photo folder on disk.
        $dir = rtrim($carImg,'/').'/'.$r['p_path'].'/'.$id;
        if (is_dir($dir)) { $rmdirRec($dir); $photosDel++; }
        // 2. DB rows.
        $db->prepare("DELETE FROM {$prefx}_car_pht WHERE it_id = ?")->execute([$id]);
        foreach ([$S999, $SFB, $STG] as $t) {
            try { $db->prepare("DELETE FROM {$t} WHERE car_id = ?")->execute([$id]); } catch (\Throwable $e) {}
        }
        try { $db->prepare("DELETE FROM {$prefx}_seo2 WHERE tp='item' AND p1='cars' AND it_id = ?")->execute([$id]); } catch (\Throwable $e) {}
        try { $db->prepare("UPDATE {$P} SET car_ctlg_id = NULL, status = 'rejected' WHERE car_ctlg_id = ?")->execute([$id]); } catch (\Throwable $e) {}
        $db->prepare("DELETE FROM {$C} WHERE id = ?")->execute([$id]);
        $del++;
    }
    $remain = (int)$db->query("SELECT COUNT(*) FROM {$C} WHERE n_a = 1{$ctWhere}")->fetchColumn();
    echo "\n  deleted {$del} car(s), {$photosDel} photo folder(s).\n";
    echo "  remaining n_a=1 (ct={$ct}): {$remain}".($remain > 0 ? "  — re-run to continue" : "  ✓ all done")."\n";
    echo "\nDone.\n"; exit;
}

// ─────────────────────────── MODE: purgeall ───────────────────────────
// Stop auto-publishing: delete every PENDING/POSTPONED/FAILED queue row on ALL three
// channels (999, FB, TG) for cars that belong to a parsing filter. Cars already LIVE
// (999_id / facebook_published=1 / telegram_published=1) are NOT touched — their ads
// stay up; only the not-yet-published queue rows are removed. Also clears the
// crosspost_999 marker for cars no longer queued. Run BEFORE deleting filters.
// Dry-run unless &apply=1.
if ($do === 'purgeall') {
    $apply = (($_GET['apply'] ?? '') === '1');
    hr($apply ? "PURGE all channels' pending queue (parsing cars)" : "DRY-RUN purgeall (add &apply=1)");
    $tables = ['999' => $S999, 'fb' => $SFB, 'tg' => $STG];
    $totalDel = 0;
    foreach ($tables as $ch => $tbl) {
        $where = "s.status IN ('pending','postponed','failed')
                  AND EXISTS (SELECT 1 FROM {$P} pc WHERE pc.car_ctlg_id = s.car_id AND pc.filter_id IS NOT NULL)";
        try {
            $cnt = (int)$db->query("SELECT COUNT(*) FROM {$tbl} s WHERE {$where}")->fetchColumn();
        } catch (\Throwable $e) { echo "  [{$ch}] table missing\n"; continue; }
        echo "  [{$ch}] pending/postponed/failed to delete: {$cnt}\n";
        if ($apply && $cnt > 0) {
            $del = $db->prepare("DELETE s FROM {$tbl} s WHERE {$where}");
            $del->execute();
            $totalDel += $del->rowCount();
        }
    }
    // How many parsing cars are LIVE on 999 (kept, reassurance).
    $live = (int)$db->query("SELECT COUNT(*) FROM {$C} cc
        WHERE cc.`999_id` > 0 AND EXISTS (SELECT 1 FROM {$P} pc WHERE pc.car_ctlg_id = cc.id AND pc.filter_id IS NOT NULL)")->fetchColumn();
    echo "  parsing cars LIVE on 999 (kept): {$live}\n";
    if (!$apply) { echo "\n  PREVIEW only. Add &apply=1 to delete these queue rows.\n\nDone.\n"; exit; }
    echo "\n  deleted {$totalDel} queue row(s) across all channels.\n";
    // Reset 999 marker for cars now with no queue row and not live.
    try {
        $db->exec("UPDATE {$P} pc SET crosspost_999 = 0
            WHERE crosspost_999 = 1
              AND NOT EXISTS (SELECT 1 FROM {$S999} s WHERE s.car_id = pc.car_ctlg_id)
              AND NOT EXISTS (SELECT 1 FROM {$C} cc WHERE cc.id = pc.car_ctlg_id AND cc.`999_id` > 0)");
        echo "  cleared crosspost_999 marker for purged cars.\n";
    } catch (\Throwable $e) {}
    echo "\n  Now safe to delete the filters. LIVE ads on all channels are untouched.\n";
    echo "\nDone.\n"; exit;
}

// ─────────────────────────── MODE: purge999 ───────────────────────────
// Empty the 999 auto-publish queue for parsing cars: delete every
// sauto_personal_schedules row whose car came from a parsing filter, regardless of
// age or status (pending/postponed/failed/published). PROTECTS cars already live on
// 999 (cc.999_id > 0) — those adverts stay; only their leftover schedule rows go if
// not live. Clears the crosspost_999 marker so the cron can re-queue cleanly.
// Deletes ONLY local queue rows — never touches adverts on 999.md.
// Dry-run unless &apply=1. Add &keeplive=0 to also purge live-999 cars' rows.
if ($do === 'purge999') {
    $apply    = (($_GET['apply'] ?? '') === '1');
    $keepLive = (($_GET['keeplive'] ?? '1') !== '0');   // default: keep rows of live-999 cars
    // ONLY cars the AUTO crosspost cron queued — identified by crosspost_999 = 1,
    // a marker set exclusively at line 132 of parsing_crosspost_cron.php. Cars
    // published MANUALLY (from /parsing or elsewhere) never get this flag, so they
    // are left completely untouched even if they belong to a parsing filter.
    $where = "EXISTS (SELECT 1 FROM {$P} pc
        WHERE pc.car_ctlg_id = s.car_id AND pc.crosspost_999 = 1)";
    if ($keepLive) {
        $where .= " AND NOT EXISTS (SELECT 1 FROM {$C} cc WHERE cc.id = s.car_id AND cc.`999_id` > 0)";
    }
    hr($apply ? "PURGE 999 auto-publish queue (parsing cars)" : "DRY-RUN purge (add &apply=1)");
    $cnt = (int)$db->query("SELECT COUNT(*) FROM {$S999} s WHERE {$where}")->fetchColumn();
    echo "  matching queue rows: {$cnt}".($keepLive ? "  (live-999 cars protected)" : "  (INCLUDING live-999 cars)")."\n";
    foreach ($db->query("SELECT s.status, COUNT(*) c FROM {$S999} s WHERE {$where} GROUP BY s.status")->fetchAll(PDO::FETCH_ASSOC) as $r)
        echo "    {$r['status']}: {$r['c']}\n";
    // How many parsing cars are live on 999 (kept, for reassurance).
    $live = (int)$db->query("SELECT COUNT(*) FROM {$C} cc
        WHERE cc.`999_id` > 0 AND EXISTS (SELECT 1 FROM {$P} pc WHERE pc.car_ctlg_id = cc.id AND pc.filter_id IS NOT NULL)")->fetchColumn();
    echo "  parsing cars already LIVE on 999 (kept): {$live}\n";
    if (!$apply) { echo "\n  PREVIEW only. Add &apply=1 to delete these queue rows.\n\nDone.\n"; exit; }
    $del = $db->prepare("DELETE s FROM {$S999} s WHERE {$where}");
    $del->execute();
    echo "\n  Deleted {$del->rowCount()} queue row(s).\n";
    // Reset the marker ONLY for cars that now have NO queue row AND are NOT live on
    // 999. A car already published (999_id > 0) must keep crosspost_999 = 1 so the
    // filter's "999" button still lists it — the earlier version cleared those too,
    // which is why the buttons went empty.
    try {
        $db->exec("UPDATE {$P} pc SET crosspost_999 = 0
            WHERE crosspost_999 = 1
              AND NOT EXISTS (SELECT 1 FROM {$S999} s WHERE s.car_id = pc.car_ctlg_id)
              AND NOT EXISTS (SELECT 1 FROM {$C} cc WHERE cc.id = pc.car_ctlg_id AND cc.`999_id` > 0)");
        echo "  Cleared crosspost_999 marker for purged (non-live) cars.\n";
    } catch (\Throwable $e) { echo "  (marker reset skipped: ".$e->getMessage().")\n"; }
    echo "\nDone.\n"; exit;
}

// ─────────────────────────── MODE: cleanup ───────────────────────────
if ($do === 'cleanup') {
    $apply=(($_GET['apply']??'')==='1');
    $where="s.created_at >= DATE_SUB(NOW(), INTERVAL 6 HOUR)
            AND EXISTS (SELECT 1 FROM {$P} pc WHERE pc.car_ctlg_id=s.car_id AND pc.filter_id IS NOT NULL)";
    hr("cleanup 999 test schedules");
    $cnt=(int)$db->query("SELECT COUNT(*) FROM {$S999} s WHERE {$where}")->fetchColumn();
    echo "  999 crosspost-test schedules (last 6h, parsing cars): {$cnt}\n";
    foreach($db->query("SELECT status,COUNT(*) c FROM {$S999} s WHERE {$where} GROUP BY status")->fetchAll(PDO::FETCH_ASSOC) as $r)
        echo "    {$r['status']}: {$r['c']}\n";
    if(!$apply){ echo "\n  PREVIEW only. Add &apply=1 to delete.\n\nDone.\n"; exit; }
    $del=$db->prepare("DELETE s FROM {$S999} s WHERE {$where}"); $del->execute();
    echo "\n  Deleted: ".$del->rowCount()." row(s).\n";
    try{ $db->exec("UPDATE {$P} SET crosspost_999=0 WHERE crosspost_999=1"); echo "  Cleared crosspost_999 marker.\n"; }catch(\Throwable $e){}
    echo "\nDone.\n"; exit;
}

// ─────────── MODE: template999 (inspect a real successful 999 payload) ───────────
if ($do === 'template999') {
    hr("real 999 JSON from parsing cars already live on 999");
    // Find parsing cars that were successfully published to 999 (have 999_id) and
    // whose car_ctlg.999 JSON is populated — that's the exact payload shape we must
    // reproduce server-side. Show a few, grouped by source, with their feature ids.
    $src = preg_replace('/[^a-z]/','',strtolower($_GET['src'] ?? ''));
    $srcSql = $src!=='' ? " AND pc.source = ".$db->quote($src) : "";
    $rows=$db->query("SELECT pc.source, cc.id, cc.br_nm, cc.mo_nm, cc.yr, cc.`999_api_id`, cc.`999` js
        FROM {$P} pc JOIN {$C} cc ON cc.id = pc.car_ctlg_id
        WHERE cc.`999_id` > 0 AND cc.`999` IS NOT NULL AND cc.`999` <> '' {$srcSql}
        ORDER BY pc.source, cc.id DESC LIMIT 6")->fetchAll(PDO::FETCH_ASSOC);
    if(!$rows){ echo "  (no parsing car is live on 999 with a saved payload yet)\n"; echo "\nDone.\n"; exit; }
    foreach($rows as $r){
        $j=json_decode($r['js'],true);
        echo "\n  [{$r['source']}] car#{$r['id']} {$r['br_nm']} {$r['mo_nm']} {$r['yr']} (999_api_id={$r['999_api_id']})\n";
        if(!is_array($j)){ echo "    (unparseable JSON)\n"; continue; }
        echo "    category_id={$j['category_id']} subcategory_id={$j['subcategory_id']} offer_type={$j['offer_type']}\n";
        echo "    announcement_type=".($j['announcement_type']??'-')." scenario=".($j['scenario']??'-')." text_option=".($j['text_option']??'-')."\n";
        echo "    features:\n";
        foreach(($j['features']??[]) as $f){
            $v=$f['value']??'';
            if(is_array($v)) $v='['.implode(',',array_slice($v,0,3)).(count($v)>3?',…':'').']';
            else $v=substr((string)$v,0,60);
            echo "      id={$f['id']}  value=[{$v}]\n";
        }
    }
    echo "\nDone.\n"; exit;
}

// ─────────── MODE: decode999 (map car_ctlg specs ↔ 999 feature ids) ───────────
if ($do === 'decode999') {
    hr("car specs vs their 999 feature ids (to decode the mapping)");
    // Pull cars live on 999 with their sauto specs AND the 999 JSON, so we can line
    // up e.g. car.fl='dsl' with feature id X value Y. Helps build the assembler map.
    $rows=$db->query("SELECT pc.source, cc.id, cc.br, cc.mo, cc.br_nm, cc.mo_nm, cc.yr, cc.vol, cc.hp,
            cc.fl, cc.tra, cc.wd, cc.clr, cc.bt, cc.sts, cc.mlg, cc.prc, cc.`999` js
        FROM {$P} pc JOIN {$C} cc ON cc.id = pc.car_ctlg_id
        WHERE cc.`999_id` > 0 AND cc.`999` IS NOT NULL AND cc.`999` <> ''
        ORDER BY cc.id DESC LIMIT 12")->fetchAll(PDO::FETCH_ASSOC);
    foreach($rows as $r){
        $j=json_decode($r['js'],true); if(!is_array($j)) continue;
        $fx=[]; foreach(($j['features']??[]) as $f){ $fx[$f['id']]=$f['value']; }
        // Interesting variable features to line up:
        echo "\n  car#{$r['id']} [{$r['source']}] {$r['br_nm']} {$r['mo_nm']} {$r['yr']}\n";
        echo "    SAUTO: br={$r['br']} mo={$r['mo']} vol={$r['vol']} hp={$r['hp']} fl={$r['fl']} tra={$r['tra']} wd={$r['wd']} clr={$r['clr']} bt={$r['bt']} sts={$r['sts']} mlg={$r['mlg']} prc={$r['prc']}\n";
        echo "    999:   mark(20)=".($fx[20]??'-')." model(21)=".($fx[21]??'-')." gen(2095)=".($fx[2095]??'-')."\n";
        echo "           year(19)=".($fx[19]??'-')." km(104)=".($fx[104]??'-')." hp(107)=".($fx[107]??'-')." price(2)=".($fx[2]??'-')."\n";
        echo "           f102=".($fx[102]??'-')." f108=".($fx[108]??'-')." f17=".($fx[17]??'-')." f151=".($fx[151]??'-')." f2553=".($fx[2553]??'-')." f846=".($fx[846]??'-')." f851=".($fx[851]??'-')."\n";
    }
    echo "\nDone.\n"; exit;
}

// ─── MODE: gencache — build (br,mo,gen-year) → 999 model+generation from live ads ───
if ($do === 'gencache') {
    hr("distinct (brand, model) → 999 model+generation ids from live 999 ads");
    // Every parsing car already on 999 gives us the exact 999 model(21) + gen(2095)
    // for its br/mo. Cars of the same model share them, so this is a reusable cache:
    // future cars of a known model don't need AI/API — just look them up here.
    $rows=$db->query("SELECT cc.br, cc.mo, cc.br_nm, cc.mo_nm, cc.yr, cc.`999` js
        FROM {$P} pc JOIN {$C} cc ON cc.id = pc.car_ctlg_id
        WHERE cc.`999_id` > 0 AND cc.`999` IS NOT NULL AND cc.`999` <> ''")->fetchAll(PDO::FETCH_ASSOC);
    $map=[]; // "br|mo" => ["model"=>.., "gens"=>[year_bucket=>gen]]
    foreach($rows as $r){
        $j=json_decode($r['js'],true); if(!is_array($j)) continue;
        $fx=[]; foreach(($j['features']??[]) as $f) $fx[$f['id']]=$f['value'];
        $key=$r['br'].'|'.$r['mo'];
        $map[$key]['br_nm']=$r['br_nm']; $map[$key]['mo_nm']=$r['mo_nm'];
        $map[$key]['model']=$fx[21]??'?';
        $map[$key]['gens'][(int)($fx[2095]??0)]=($map[$key]['gens'][(int)($fx[2095]??0)]??0)+1;
    }
    ksort($map);
    echo "  distinct br/mo already on 999: ".count($map)."\n\n";
    foreach($map as $k=>$v){
        $gens=[]; foreach($v['gens'] as $g=>$c) $gens[]="{$g}({$c})";
        echo "  {$k}  [{$v['br_nm']} {$v['mo_nm']}]  model999={$v['model']}  gens: ".implode(' ',$gens)."\n";
    }
    echo "\nDone.\n"; exit;
}

// ─── MODE: specmap — auto-extract sauto spec → 999 feature id maps from live ads ───
if ($do === 'specmap') {
    hr("spec → 999 id maps (extracted from all live 999 ads)");
    $rows=$db->query("SELECT cc.fl, cc.tra, cc.wd, cc.clr, cc.bt, cc.sts, cc.`999` js
        FROM {$P} pc JOIN {$C} cc ON cc.id = pc.car_ctlg_id
        WHERE cc.`999_id` > 0 AND cc.`999` IS NOT NULL AND cc.`999` <> ''")->fetchAll(PDO::FETCH_ASSOC);
    // Feature ids for each spec (from decode999): body=102, drive=108, fuel=151, colour=17.
    $spec=['fl'=>151,'tra'=>null,'wd'=>108,'clr'=>17,'bt'=>102,'sts'=>105];
    $maps=[]; // specName => [ sautoCode => [id999 => count] ]
    foreach($rows as $r){
        $j=json_decode($r['js'],true); if(!is_array($j)) continue;
        $fx=[]; foreach(($j['features']??[]) as $f) $fx[$f['id']]=$f['value'];
        foreach($spec as $col=>$fid){
            if($fid===null) continue;
            $code=trim((string)$r[$col]); if($code==='') continue;
            $v=$fx[$fid]??null; if($v===null||is_array($v)) continue;
            $maps[$col][$code][(string)$v]=($maps[$col][$code][(string)$v]??0)+1;
        }
    }
    foreach($maps as $col=>$codes){
        echo "\n  {$col} (feature ".$spec[$col]."):\n";
        ksort($codes);
        foreach($codes as $code=>$ids){
            arsort($ids);
            $parts=[]; foreach($ids as $id=>$c) $parts[]="{$id}({$c})";
            echo "    '{$code}' => ".implode(' ',$parts)."\n";
        }
    }
    echo "\nDone.\n"; exit;
}

// ─── MODE: build999 — run the assembler on a car and compare to the real ad ───
if ($do === 'build999') {
    require_once __DIR__.'/../App/Services/Parsing/Build999Payload.php';
    $b = new \App\Services\Parsing\Build999Payload($db, $prefx);
    $carId = (int)($_GET['car'] ?? 0);

    // If no car given, pick a few live-on-999 cars and compare generated vs real.
    if (!$carId) {
        hr("build999 vs real — sample of live-on-999 cars");
        // One car per distinct brand/model/source, across all sources, for coverage.
        $ids=$db->query("SELECT MAX(cc.id) FROM {$P} pc JOIN {$C} cc ON cc.id=pc.car_ctlg_id
            WHERE cc.`999_id`>0 AND cc.`999` IS NOT NULL AND cc.`999`<>''
            GROUP BY cc.br, cc.mo, pc.source ORDER BY RAND() LIMIT 50")->fetchAll(PDO::FETCH_COLUMN);
        $matches=0; $diffs=0; $skips=0;
        foreach($ids as $cid){
            $gen=$b->build((int)$cid);
            $realJs=$db->query("SELECT `999` FROM {$C} WHERE id=".(int)$cid)->fetchColumn();
            $real=json_decode($realJs,true);
            $rf=[]; foreach(($real['features']??[]) as $f) $rf[$f['id']]=is_array($f['value'])?'[arr]':(string)$f['value'];
            $nm=$db->query("SELECT CONCAT(br_nm,' ',mo_nm) FROM {$C} WHERE id=".(int)$cid)->fetchColumn();
            echo "\n  car#{$cid} [{$nm}]: ";
            if(!$gen){ echo "SKIPPED by builder\n"; $skips++; continue; }
            $gf=[]; foreach($gen['payload']['features'] as $f) $gf[$f['id']]=is_array($f['value'])?'[arr]':(string)$f['value'];
            $keys=['20','21','2095','19','104','107','2','151','108','102','17'];
            $ok=true; $diff=[];
            foreach($keys as $k){
                $g=$gf[$k]??'∅'; $r=$rf[$k]??'∅';
                if($g!==$r){ $ok=false; $diff[]="{$k}:gen={$g}≠real={$r}"; }
            }
            if($ok){ $matches++; echo "MATCH ✓ (acc={$gen['account_id']})"; }
            else { $diffs++; echo "DIFF ✗ ".implode(' ',$diff); }
            echo "\n";
        }
        echo "\n  TOTAL: {$matches} match, {$diffs} diff, {$skips} skipped\n";
        echo "\nDone.\n"; exit;
    }

    // Single car: dump the generated payload in full.
    hr("build999 for car#{$carId}");
    $gen=$b->build($carId);
    if(!$gen){ echo "  builder returned null (model not cached / commercial / missing data)\n\nDone.\n"; exit; }
    echo "  account_id: {$gen['account_id']}\n";
    echo "  category={$gen['payload']['category_id']} subcat={$gen['payload']['subcategory_id']} offer={$gen['payload']['offer_type']}\n";
    foreach($gen['payload']['features'] as $f){
        $v=is_array($f['value'])?'[arr]':substr((string)$f['value'],0,50);
        echo "    id={$f['id']} value=[{$v}]\n";
    }
    echo "\nDone.\n"; exit;
}

// ─── MODE: commercial999 — inspect real COMMERCIAL (gr=com) 999 payloads ───
if ($do === 'commercial999') {
    hr("real COMMERCIAL 999 payloads (gr=com) — different subcategory/features");
    $rows=$db->query("SELECT pc.source, cc.id, cc.br, cc.mo, cc.br_nm, cc.mo_nm, cc.yr, cc.`999_api_id`,
            cc.fl, cc.tra, cc.wd, cc.clr, cc.bt, cc.sts, cc.hp, cc.mlg, cc.prc, cc.`999` js
        FROM {$P} pc JOIN {$C} cc ON cc.id = pc.car_ctlg_id
        WHERE cc.gr='com' AND cc.`999_id` > 0 AND cc.`999` IS NOT NULL AND cc.`999` <> ''
        ORDER BY cc.id DESC LIMIT 6")->fetchAll(PDO::FETCH_ASSOC);
    if(!$rows){ echo "  (no commercial car is live on 999 with a saved payload)\n\nDone.\n"; exit; }
    foreach($rows as $r){
        $j=json_decode($r['js'],true); if(!is_array($j)) continue;
        echo "\n  car#{$r['id']} [{$r['source']}] {$r['br_nm']} {$r['mo_nm']} {$r['yr']} (999_api_id={$r['999_api_id']})\n";
        echo "    SAUTO: br={$r['br']} mo={$r['mo']} fl={$r['fl']} tra={$r['tra']} wd={$r['wd']} clr={$r['clr']} bt={$r['bt']} sts={$r['sts']} hp={$r['hp']} mlg={$r['mlg']} prc={$r['prc']}\n";
        echo "    999:   category={$j['category_id']} subcategory={$j['subcategory_id']} offer_type={$j['offer_type']} scenario=".($j['scenario']??'-')."\n";
        echo "    features:\n";
        foreach(($j['features']??[]) as $f){
            $v=$f['value']??''; if(is_array($v)) $v='['.count($v).' items]'; else $v=substr((string)$v,0,50);
            echo "      id={$f['id']} value=[{$v}]\n";
        }
    }
    echo "\nDone.\n"; exit;
}

// ─── MODE: diag — inspect one car's br/mo/yr and what resolveModelGen returns ───
if ($do === 'diag') {
    require_once __DIR__.'/../App/Services/Parsing/Build999Payload.php';
    $b=new \App\Services\Parsing\Build999Payload($db,$prefx);
    $cid=(int)($_GET['car']??0);
    hr("diag car#{$cid}");
    $c=$db->query("SELECT id,br,mo,br_nm,mo_nm,yr,`999_id` FROM {$C} WHERE id={$cid}")->fetch(PDO::FETCH_ASSOC);
    if(!$c){ echo "  not found\n\nDone.\n"; exit; }
    echo "  car: br={$c['br']} mo={$c['mo']} [{$c['br_nm']} {$c['mo_nm']}] yr={$c['yr']} 999_id={$c['999_id']}\n";
    $mg=$b->resolveModelGen($c['br'],$c['mo'],(int)$c['yr']);
    echo "  resolveModelGen => ".($mg?json_encode($mg):'null')."\n";
    // Show all live ads for this br/mo and their model/gen (why the cache picked one).
    echo "  all live-999 ads for br={$c['br']} mo={$c['mo']}:\n";
    $rows=$db->query("SELECT yr,`999` js FROM {$C} WHERE br=".$db->quote($c['br'])." AND mo=".$db->quote($c['mo'])."
        AND `999_id`>0 AND `999` IS NOT NULL AND `999`<>'' ORDER BY yr")->fetchAll(PDO::FETCH_ASSOC);
    foreach($rows as $r){
        $j=json_decode($r['js'],true); $fx=[]; foreach(($j['features']??[]) as $f)$fx[$f['id']]=$f['value'];
        echo "    yr={$r['yr']}: model21=".($fx[21]??'-')." gen2095=".($fx[2095]??'-')."\n";
    }
    echo "\nDone.\n"; exit;
}

// ─── MODE: sim999 — for each active filter, which cheapest cars would BUILD? ───
if ($do === 'sim999') {
    require_once __DIR__.'/../App/Services/Parsing/Build999Payload.php';
    $b=new \App\Services\Parsing\Build999Payload($db,$prefx);
    hr("sim999 — cheapest un-999 cars per filter: BUILD ok vs SKIP");
    $filters=$db->query("SELECT id,name FROM {$F} WHERE active=1 ORDER BY id")->fetchAll(PDO::FETCH_ASSOC);
    $totBuild=0; $totSkip=0;
    foreach($filters as $f){
        $fid=(int)$f['id'];
        $rows=$db->query("SELECT cc.id, cc.br_nm, cc.mo_nm, cc.yr, cc.prc, pc.source, cc.gr
            FROM {$P} pc JOIN {$C} cc ON cc.id=pc.car_ctlg_id
            WHERE pc.filter_id={$fid} AND pc.status='published' AND cc.prc>0
              AND (cc.`999_id` IS NULL OR cc.`999_id`=0)
            ORDER BY cc.prc ASC LIMIT 4")->fetchAll(PDO::FETCH_ASSOC);
        if(!$rows) continue;
        $line=[];
        foreach($rows as $r){
            $ok=$b->build((int)$r['id']);
            if($ok){ $totBuild++; $line[]="✓{$r['br_nm']} {$r['mo_nm']}({$r['prc']}€)"; }
            else { $totSkip++; $line[]="✗{$r['br_nm']} {$r['mo_nm']}".($r['gr']==='com'?'(com)':''); }
        }
        echo "  #{$fid} \"{$f['name']}\": ".implode('  ',$line)."\n";
    }
    echo "\n  cheapest-4-per-filter: {$totBuild} would build, {$totSkip} skipped\n";
    echo "\nDone.\n"; exit;
}

// ─── MODE: whyskip — for each skipped model, WHY it can't build ───
if ($do === 'whyskip') {
    require_once __DIR__.'/../App/Services/Parsing/Build999Payload.php';
    $b=new \App\Services\Parsing\Build999Payload($db,$prefx);
    hr("why cars get SKIPPED (uncached model? bad spec? commercial?)");
    // Distinct br/mo among cheapest un-999 cars that the builder skips.
    $filters=$db->query("SELECT id FROM {$F} WHERE active=1")->fetchAll(PDO::FETCH_COLUMN);
    $seen=[];
    foreach($filters as $fid){
        $rows=$db->query("SELECT cc.id, cc.br, cc.mo, cc.br_nm, cc.mo_nm, cc.yr, cc.gr, cc.fl, cc.wd, cc.bt, cc.clr, cc.mlg, cc.hp, cc.prc, cc.vol
            FROM {$P} pc JOIN {$C} cc ON cc.id=pc.car_ctlg_id
            WHERE pc.filter_id={$fid} AND pc.status='published' AND cc.prc>0
              AND (cc.`999_id` IS NULL OR cc.`999_id`=0)
            ORDER BY cc.prc ASC LIMIT 6")->fetchAll(PDO::FETCH_ASSOC);
        foreach($rows as $r){
            if($b->build((int)$r['id'])) continue; // builds fine
            $key=$r['br'].'|'.$r['mo'];
            if(isset($seen[$key])) continue; $seen[$key]=1;
            // Diagnose the reason.
            $reasons=[];
            if(($r['gr']??'')==='com'){
                if(\App\Services\Parsing\Build999Payload::brandId($r['br'])===null) $reasons[]='brand not mapped';
                if(($r['mo_nm']??'')==='') $reasons[]='no model name';
            } else {
                $mg=$b->resolveModelGen($r['br'],$r['mo'],(int)$r['yr']);
                if(!$mg) $reasons[]='MODEL NOT IN CACHE (never published on 999)';
                if(\App\Services\Parsing\Build999Payload::brandId($r['br'])===null) $reasons[]='brand not mapped';
            }
            // spec gaps
            foreach(['fl'=>'fuel','wd'=>'drive','bt'=>'body'] as $c=>$n){
                if(trim((string)$r[$c])==='') $reasons[]="empty {$n}";
            }
            if((int)$r['mlg']<=0) $reasons[]='no km';
            if((int)$r['hp']<=0 && ($r['gr']??'')!=='com') $reasons[]='no hp';
            echo "  {$r['br_nm']} {$r['mo_nm']} [{$r['br']}|{$r['mo']}] gr={$r['gr']} fl={$r['fl']} wd={$r['wd']} bt={$r['bt']}: ".implode(', ',$reasons?:['??'])."\n";
        }
    }
    echo "\nDone.\n"; exit;
}

// ─── MODE: api999models — call the live 999 API for a brand's models + generations ───
if ($do === 'api999models') {
    $svc = new \App\Services\Api999Service();
    $brandId = (int)($_GET['brand'] ?? 0);   // 999 brand id, e.g. Toyota=47
    $find    = trim((string)($_GET['find'] ?? '')); // model name to locate, e.g. Aygo
    hr("999 API models for brand id={$brandId}");
    // To get MODELS: dependency_feature_id = 20 (the BRAND feature), parent = brand id.
    // (The model feature itself is 21; generation is 2095 with parent = model id.)
    $res = $svc->getDependentOptions(659, 20, $brandId);
    echo "  raw (first 300): ".substr(json_encode($res, JSON_UNESCAPED_UNICODE),0,300)."\n";
    $opts = $res['Options'] ?? $res['options'] ?? [];
    echo "  ".count($opts)." models returned\n";
    $matchId = null;
    foreach ($opts as $o) {
        $id = $o['id'] ?? $o['value'] ?? '?';
        $title = $o['title'] ?? $o['text'] ?? '';
        $hit = ($find !== '' && stripos($title, $find) !== false);
        if ($hit) { $matchId = $id; echo "  >>> MATCH: id={$id} '{$title}'\n"; }
        elseif ($find === '') echo "    id={$id} '{$title}'\n";
    }
    // Generations: dependency = 21 (the MODEL feature), parent = model id.
    if ($matchId) {
        hr("generations for model id={$matchId}");
        $g = $svc->getDependentOptions(659, 21, (int)$matchId);
        echo "  raw (first 300): ".substr(json_encode($g, JSON_UNESCAPED_UNICODE),0,300)."\n";
        foreach (($g['Options'] ?? $g['options'] ?? []) as $o) {
            echo "    gen id=".($o['id']??$o['value']??'?')." '".($o['title']??$o['text']??'')."'\n";
        }
    }
    echo "\nDone.\n"; exit;
}

// ─────────────────────────── MODE: dupes ───────────────────────────
// Read-only audit of duplicate/wrong brands & models in the catalog, caused by
// parsing sources normalizing names differently (Encar "Mercedes Benz" vs
// OpenLane/eCarsTrade "Mercedes-Benz"; "BYD"/"Byd"; "C Break" vs "C-Class").
// Groups by a normalized key (no case/space/dash/diacritics) and shows how many
// LIVE cars sit under each spelling. NOTHING is modified.
if ($do === 'dupes') {
    $L = "{$prefx}_car_list";
    // Normalize a display name to a comparison key: lowercase, strip diacritics,
    // drop everything but a-z0-9. "Mercedes-Benz"/"Mercedes Benz"→"mercedesbenz".
    $norm = function(string $s): string {
        $s = trim($s);
        if (function_exists('transliterator_transliterate')) {
            $t = @transliterator_transliterate('Any-Latin; Latin-ASCII; Lower()', $s);
            if ($t !== false) $s = $t;
        }
        return preg_replace('/[^a-z0-9]+/', '', mb_strtolower($s, 'UTF-8'));
    };
    // Live-car count per (br) and per (br,mo), from the catalog.
    $brCnt = []; $moCnt = [];
    foreach ($db->query("SELECT br, COUNT(*) c FROM {$C} WHERE br<>'' GROUP BY br") as $r) $brCnt[$r['br']] = (int)$r['c'];
    foreach ($db->query("SELECT br, mo, COUNT(*) c FROM {$C} WHERE br<>'' AND mo<>'' GROUP BY br,mo") as $r) $moCnt[$r['br'].'|'.$r['mo']] = (int)$r['c'];

    hr("DUPLICATE BRANDS (same brand, different spelling)");
    $brands = $db->query("SELECT DISTINCT br, br_nm FROM {$L} WHERE br_nm<>'' ORDER BY br_nm")->fetchAll(PDO::FETCH_ASSOC);
    $byKey = [];
    foreach ($brands as $b) $byKey[$norm($b['br_nm'])][] = $b;
    $dupBrandKeys = [];
    foreach ($byKey as $k => $grp) {
        if (count($grp) < 2) continue;
        $dupBrandKeys[$k] = $grp;
        // Winner = spelling with the most live cars (canonical = "as in catalog").
        usort($grp, fn($a,$b)=>($brCnt[$b['br']]??0)<=>($brCnt[$a['br']]??0));
        $win = $grp[0];
        echo "  ".str_pad($win['br_nm'],22)." (br={$win['br']}, ".($brCnt[$win['br']]??0)." cars)  <= KEEP\n";
        foreach (array_slice($grp,1) as $g)
            echo "     merge: ".str_pad($g['br_nm'],20)." (br={$g['br']}, ".($brCnt[$g['br']]??0)." cars)\n";
    }
    if (!$dupBrandKeys) echo "  none\n";

    hr("DUPLICATE / SPLIT MODELS (per brand, same base model)");
    // Group models within each brand by normalized key. But split names like
    // "C Break", "C AMG", "Sprinter box" share a FIRST token — we also collapse a
    // model whose normalized name is a prefix of, or prefixed by, another model of
    // the same brand (mirrors resolveModelId's fuzzy rule). Reported for review.
    $rows = $db->query("SELECT br, mo, br_nm, mo_nm FROM {$L} WHERE mo_nm<>'' ORDER BY br_nm, mo_nm")->fetchAll(PDO::FETCH_ASSOC);
    $byBrand = [];
    foreach ($rows as $r) $byBrand[$r['br']][] = $r;
    $shownBrand = null; $anyModelDup = false;
    foreach ($byBrand as $br => $models) {
        // exact normalized-key collisions
        $mk = [];
        foreach ($models as $m) $mk[$norm($m['mo_nm'])][] = $m;
        $lines = [];
        foreach ($mk as $k => $grp) {
            if (count($grp) < 2) continue;
            usort($grp, fn($a,$b)=>($moCnt[$b['br'].'|'.$b['mo']]??0)<=>($moCnt[$a['br'].'|'.$a['mo']]??0));
            $win = $grp[0];
            $l = "    ".str_pad($win['mo_nm'],22)." (mo={$win['mo']}, ".($moCnt[$win['br'].'|'.$win['mo']]??0)." cars) <= KEEP\n";
            foreach (array_slice($grp,1) as $g)
                $l .= "       merge: ".str_pad($g['mo_nm'],20)." (mo={$g['mo']}, ".($moCnt[$g['br'].'|'.$g['mo']]??0)." cars)\n";
            $lines[] = $l;
        }
        if ($lines) {
            $anyModelDup = true;
            echo "  [".($models[0]['br_nm'])."]\n".implode('', $lines);
        }
    }
    if (!$anyModelDup) echo "  none (exact-key)\n";

    // ── FRAGMENTED MODELS: same brand, models sharing a first token where one is a
    // "-Class"/base variant. E.g. Mercedes: c / c-amg / c-break / c-class are all
    // C-Class. We DON'T auto-merge these (GLC vs GLC-Coupe are legitimately
    // different) — we just GROUP them by first token so YOU decide. Only brands
    // with a specific &brand=<br> are expanded, else all with >1 in a token group.
    hr("FRAGMENTED MODELS (share first word — YOU decide which merge)");
    $onlyBr = trim((string)($_GET['brand'] ?? ''));
    $firstTok = function(string $s): string {
        $s = mb_strtolower(trim($s), 'UTF-8');
        $s = preg_replace('/[^a-z0-9]+/', ' ', $s);
        return trim(explode(' ', trim($s))[0] ?? '');
    };
    foreach ($byBrand as $br => $models) {
        if ($onlyBr !== '' && strcasecmp($br, $onlyBr) !== 0
            && strcasecmp((string)($models[0]['br_nm'] ?? ''), $onlyBr) !== 0) continue;
        $tok = [];
        foreach ($models as $m) {
            $t = $firstTok($m['mo_nm']);
            if ($t === '') continue;
            $tok[$t][] = $m;
        }
        $blocks = '';
        foreach ($tok as $t => $grp) {
            if (count($grp) < 2) continue;
            usort($grp, fn($a,$b)=>($moCnt[$b['br'].'|'.$b['mo']]??0)<=>($moCnt[$a['br'].'|'.$a['mo']]??0));
            $blocks .= "     token \"{$t}\":\n";
            foreach ($grp as $g)
                $blocks .= "        mo=".str_pad($g['mo'],18)." \"".str_pad($g['mo_nm'],18)."\"  ".($moCnt[$g['br'].'|'.$g['mo']]??0)." cars\n";
        }
        if ($blocks !== '') echo "  [".($models[0]['br_nm'])."]\n".$blocks;
    }
    echo "  (tip: ?do=dupes&brand=Mercedes Benz  to focus one brand)\n";

    hr("SUMMARY");
    $totBrandMoves = 0;
    foreach ($dupBrandKeys as $grp) { usort($grp, fn($a,$b)=>($brCnt[$b['br']]??0)<=>($brCnt[$a['br']]??0)); foreach (array_slice($grp,1) as $g) $totBrandMoves += ($brCnt[$g['br']]??0); }
    echo "  duplicate brand groups: ".count($dupBrandKeys).",  cars needing a brand move: {$totBrandMoves}\n";
    echo "  (read-only — nothing changed. Review, then we build the merge.)\n";
    echo "\nDone.\n"; exit;
}

// ─────────────────────────── MODE: mergemodels ───────────────────────────
// Fold fragmented catalog models into their canonical model, on the LIVE cars.
// Sources split e.g. Mercedes C-Class into c / c_amg / c_break — this moves those
// cars' cc.mo + cc.mo_nm to the canonical model, backs up first, and drops the
// now-empty leftover models from car_list. Dry-run by default; &apply=1 to write.
//
//   ?do=mergemodels            → preview (no writes)
//   ?do=mergemodels&apply=1     → back up + move cars + drop empty models
if ($do === 'mergemodels') {
    $L = "{$prefx}_car_list";
    $apply = ($_GET['apply'] ?? '') === '1';

    // MERGE RULES: [brand_code => [ canonical_mo => [from_mo, ...] ]].
    // A "from" model folds into the canonical only when it's the SAME car (a trim,
    // engine, or body-suffix variant like _avant/_break/_amg/_rs/_vz/_e_tron/_series).
    // Genuinely different models are NEVER folded: different number (CX-3 vs CX-30,
    // A3 vs A4, ID.3 vs ID.4, Model 3 vs Model Y), or a distinct body 999 lists on
    // its own (GLC vs GLC-Coupe, Range Rover vs Sport/Evoque/Velar, Grand Scenic vs
    // Grand Kangoo, Corolla vs Corolla Cross, Yaris vs Yaris Cross). Canonical =
    // spelling with the most live cars (matches "as in catalog").
    $RULES = [
        // Auto1 lists German-market names, so early imports (before
        // ParsingPublisher::canonicalModelName learned to fold them) created "5er"
        // beside the real "5 Series", "e_klasse" beside "e_class", "golf_vii"
        // beside "golf". Same car, different market spelling → fold.
        'bmw' => [
            '1_series' => ['1er'], '2_series' => ['2er'], '3_series' => ['3er'],
            '4_series' => ['4er'], '5_series' => ['5er'], '6_series' => ['6er'],
            '7_series' => ['7er'], '8_series' => ['8er'],
        ],
        'mercedes_benz' => [
            'a_class' => ['a', 'a_amg', 'a_klasse', 'a_klasse_limousine'],
            'b_class' => ['b', 'b_klasse'],
            'c_class' => ['c', 'c_amg', 'c_break', 'c_klasse', 'c_klasse_all_terrain'],
            'e_class' => ['e', 'e_break', 'e_klasse', 'e_klasse_all_terrain'],
            'g_class' => ['g_amg', 'g_klasse'],
            'm_class' => ['m_klasse'],
            'r_class' => ['r_klasse'],
            's_class' => ['s_long', 's_klasse'],
            'v_class' => ['v', 'v_l3', 'v_klasse', 'v_klasse_marco_polo'],
            'x_class' => ['x_klasse'],
            'cla' => ['cla_klasse'], 'cls' => ['cls_klasse'], 'clk' => ['clk_klasse'],
            'gla' => ['gla_klasse'], 'glb' => ['glb_klasse'], 'glc' => ['glc_klasse'],
            'gle' => ['gle_klasse'], 'glk' => ['glk_klasse'], 'gls' => ['gls_klasse'],
            'sl'  => ['sl_klasse'],  'slk' => ['slk_klasse'],
            'vito' => ['vito_tourer'],
        ],
        // Audi: Avant/Allroad are wagons of the same series; e-tron variants of a
        // combustion model fold in ONLY where the base model exists as the same car.
        // (Standalone "e-tron"/"e-tron GT" stay — they ARE distinct EV models.)
        'audi' => [
            'a1' => ['a1_sportback', 'a1_allstreet', 'a1_citycarver'],
            'a3' => ['a3_sportback', 'a3_allstreet', 'a3_limousine'],
            'a4' => ['a4_avant', 'a4_allroad'],
            'a5' => ['a5_sportback'],
            'a6' => ['a6_avant', 'a6_allroad'],
            'a7' => ['a7_sportback'],
            'q3' => ['q3_sportback'], 'q4' => ['q4_sportback'], 'q5' => ['q5_sportback'],
            'q6' => ['q6_sportback'], 'q8' => ['q8_sportback'],
            's3' => ['s3_sportback'], 's5' => ['s5_sportback'], 's7' => ['s7_sportback'],
            'sq5' => ['sq5_sportback'], 'sq8' => ['sq8_sportback'],
            'e_tron' => ['e_tron_sportback'],
        ],
        // Opel dropped these suffixes itself ("Astra K" is just an Astra).
        'opel' => [
            'astra'     => ['astra_k', 'astra_gtc'],
            'mokka'     => ['mokka_x'],
            'grandland' => ['grandland_x'],
            'crossland' => ['crossland_x'],
            'insignia'  => ['insignia_grand_sport', 'insignia_sports_tourer', 'insignia_country_tourer'],
            'zafira'    => ['zafira_tourer'],
        ],
        // Citroen: Picasso/CC bodies of the SAME numbered model fold in; different
        // numbers (C3 vs C4 vs C5) never merge. C5 Aircross is its OWN model — keep.
        'citroen' => [
            'c4' => ['c4_picasso'],
        ],
        'cupra' => [
            'formentor' => ['formentor_vz'],
            'leon'      => ['leon_vz', 'leon_sportstourer', 'leon_sportstourer_vz'],
        ],
        'dacia' => [
            'logan'   => ['logan_mcv', 'logan_van'],
            'dokker'  => ['dokker_van'],
        ],
        'ford' => [
            'focus' => ['focus_rs', 'focus_wagon'],
        ],
        'honda' => [
            'civic' => ['civic_hibrid'],
        ],
        'lexus' => [
            'nx' => ['nx_series'],
            'ux' => ['ux_250h'],
        ],
        'nissan' => [
            'qashqai' => ['qashqai_2'],
        ],
        'renault' => [
            'megane' => ['megane_e_tech'],
        ],
        'toyota' => [
            'prius' => ['prius_plus', 'prius_c'],
        ],
        // Golf generations are one model on sauto ("Golf VII" → Golf). Golf Plus /
        // Sportsvan are NOT here: different cars, they keep their own model.
        'volkswagen' => [
            'passat' => ['passat_cc', 'passat_alltrack'],
            'golf'   => ['golf_i', 'golf_ii', 'golf_iii', 'golf_iv', 'golf_v',
                         'golf_vi', 'golf_vii', 'golf_viii', 'e_golf_vii', 'e_golf'],
        ],
    ];
    // Empty models (0 cars) safe to delete outright — verified on ?do=dupes.
    $DROP_EMPTY = [
        'mercedes_benz' => ['c_class_coupe', 'e_class_coupe', 'e_200', 'series_w123', 'series_w124'],
    ];

    hr($apply ? "APPLYING model merge" : "DRY-RUN model merge (add &apply=1 to write)");

    if ($apply) {
        // Backup table: one row per moved car so a mistake is fully reversible.
        $db->exec("CREATE TABLE IF NOT EXISTS {$prefx}_modelmerge_backup (
            id INT PRIMARY KEY, br VARCHAR(50), mo_old VARCHAR(80), mo_nm_old VARCHAR(120),
            mo_new VARCHAR(80), mo_nm_new VARCHAR(120), moved_at DATETIME)");
    }

    $totalMoved = 0;
    foreach ($RULES as $br => $map) {
        foreach ($map as $toMo => $fromList) {
            // Canonical display name from car_list (what cars should show).
            $tn = $db->prepare("SELECT mo_nm FROM {$L} WHERE br=? AND mo=? LIMIT 1");
            $tn->execute([$br, $toMo]);
            $toNm = $tn->fetchColumn();
            if ($toNm === false) { echo "  ! canonical {$br}/{$toMo} not in car_list — skip\n"; continue; }

            foreach ($fromList as $fromMo) {
                if ($fromMo === $toMo) continue;
                $cnt = (int)$db->query("SELECT COUNT(*) FROM {$C} WHERE br=".$db->quote($br)." AND mo=".$db->quote($fromMo))->fetchColumn();
                echo "  {$br}: {$fromMo} → {$toMo} (\"{$toNm}\") : {$cnt} cars".($cnt?'':' — none')."\n";
                if (!$apply || $cnt === 0) {
                    // still drop the empty leftover model row below
                } else {
                    // Back up the affected cars.
                    $bk = $db->prepare("INSERT INTO {$prefx}_modelmerge_backup
                        (id,br,mo_old,mo_nm_old,mo_new,mo_nm_new,moved_at)
                        SELECT id,br,mo,mo_nm,?,?,NOW() FROM {$C} WHERE br=? AND mo=?
                        ON DUPLICATE KEY UPDATE mo_new=VALUES(mo_new)");
                    $bk->execute([$toMo,$toNm,$br,$fromMo]);
                    // Move the cars.
                    $up = $db->prepare("UPDATE {$C} SET mo=?, mo_nm=? WHERE br=? AND mo=?");
                    $up->execute([$toMo,$toNm,$br,$fromMo]);
                    $totalMoved += $cnt;
                }
                // Remove the now-empty leftover model definition (never the canonical).
                if ($apply && $fromMo !== $toMo) {
                    $left = (int)$db->query("SELECT COUNT(*) FROM {$C} WHERE br=".$db->quote($br)." AND mo=".$db->quote($fromMo))->fetchColumn();
                    if ($left === 0) $db->prepare("DELETE FROM {$L} WHERE br=? AND mo=? LIMIT 1")->execute([$br,$fromMo]);
                }
            }
        }
    }

    hr("DROP EMPTY MODELS (0 cars)");
    foreach ($DROP_EMPTY as $br => $list) {
        foreach ($list as $mo) {
            $c = (int)$db->query("SELECT COUNT(*) FROM {$C} WHERE br=".$db->quote($br)." AND mo=".$db->quote($mo))->fetchColumn();
            $inList = (int)$db->query("SELECT COUNT(*) FROM {$L} WHERE br=".$db->quote($br)." AND mo=".$db->quote($mo))->fetchColumn();
            if ($c > 0) { echo "  {$br}/{$mo}: has {$c} cars — NOT dropping\n"; continue; }
            echo "  {$br}/{$mo}: ".($inList?'drop':'not in list').($apply&&$inList?' — DELETED':'')."\n";
            if ($apply && $inList) $db->prepare("DELETE FROM {$L} WHERE br=? AND mo=? LIMIT 1")->execute([$br,$mo]);
        }
    }

    // Dedup EXACT duplicate car_list rows (same br+mo appearing twice, e.g.
    // Mitsubishi eclipse / Subaru outback). Keep one, drop the extras. Cars are
    // untouched (they point at the shared br+mo code either way). car_list has no
    // reliable PK, so we delete all copies then re-insert a single canonical row.
    hr("DEDUP car_list (same br+mo twice)");
    $dups = $db->query("SELECT br, mo, COUNT(*) c FROM {$L} GROUP BY br, mo HAVING c > 1")->fetchAll(PDO::FETCH_ASSOC);
    if (!$dups) echo "  none\n";
    foreach ($dups as $d) {
        echo "  {$d['br']}/{$d['mo']}: {$d['c']} rows".($apply?" — trimming to 1":"")."\n";
        if ($apply) {
            // Capture one representative row's display names first.
            $one = $db->prepare("SELECT br_nm, mo_nm FROM {$L} WHERE br=? AND mo=? LIMIT 1");
            $one->execute([$d['br'], $d['mo']]);
            $rep = $one->fetch(PDO::FETCH_ASSOC) ?: ['br_nm'=>'', 'mo_nm'=>''];
            $db->prepare("DELETE FROM {$L} WHERE br=? AND mo=?")->execute([$d['br'], $d['mo']]);
            $db->prepare("INSERT INTO {$L} (br, mo, br_nm, mo_nm) VALUES (?,?,?,?)")
               ->execute([$d['br'], $d['mo'], $rep['br_nm'], $rep['mo_nm']]);
        }
    }

    hr("RESULT");
    echo "  ".($apply?"moved {$totalMoved} cars.":"would move cars as listed above (dry-run).")."\n";
    echo "  ".($apply?"backup in {$prefx}_modelmerge_backup (reversible).":"add &apply=1 to execute.")."\n";
    echo "\nDone.\n"; exit;
}

// ─────────────────────────── MODE: pdupes ───────────────────────────
// Read-only audit of duplicate brands/models in gh3sp_parsing_cars — the RAW
// source names that feed the /parsing catalog filter dropdown (NOT car_list).
// This is a SEPARATE table from car_ctlg; "Mercedes Benz" (Encar) vs
// "Mercedes-Benz" (OpenLane/eCarsTrade), "BYD" vs "Byd", "Citroën" vs "Citroen"
// live here as free text. NOTHING is modified.
if ($do === 'pdupes') {
    $norm = function(string $s): string {
        $s = trim($s);
        if (function_exists('transliterator_transliterate')) {
            $t = @transliterator_transliterate('Any-Latin; Latin-ASCII; Lower()', $s);
            if ($t !== false) $s = $t;
        }
        return preg_replace('/[^a-z0-9]+/', '', mb_strtolower($s, 'UTF-8'));
    };
    // Raw brand => count, and brand => [model => count], across ALL statuses.
    $brCnt = []; $moByBr = [];
    $q = $db->query("SELECT brand, model, COUNT(*) c FROM {$P}
        WHERE brand IS NOT NULL AND brand<>'' GROUP BY brand, model");
    foreach ($q as $r) {
        $b = trim((string)$r['brand']); $m = trim((string)$r['model']); $c = (int)$r['c'];
        if ($b === '') continue;
        $brCnt[$b] = ($brCnt[$b] ?? 0) + $c;
        if ($m !== '') $moByBr[$b][$m] = ($moByBr[$b][$m] ?? 0) + $c;
    }

    hr("DUPLICATE BRANDS in parsing_cars (same brand, diff spelling)");
    $byKey = [];
    foreach ($brCnt as $b => $c) $byKey[$norm($b)][] = $b;
    $anyBr = false;
    foreach ($byKey as $grp) {
        if (count($grp) < 2) continue;
        $anyBr = true;
        usort($grp, fn($a,$b)=>$brCnt[$b] <=> $brCnt[$a]);
        echo "  ".str_pad($grp[0],20)." ({$brCnt[$grp[0]]} cars)  <= KEEP\n";
        foreach (array_slice($grp,1) as $g) echo "     merge: ".str_pad($g,18)." ({$brCnt[$g]} cars)\n";
    }
    if (!$anyBr) echo "  none\n";

    hr("FRAGMENTED MODELS in parsing_cars (per brand, first word)");
    $onlyBr = trim((string)($_GET['brand'] ?? ''));
    $firstTok = function(string $s): string {
        $s = mb_strtolower(trim($s), 'UTF-8');
        $s = preg_replace('/[^a-z0-9]+/', ' ', $s);
        return trim(explode(' ', trim($s))[0] ?? '');
    };
    foreach ($moByBr as $br => $models) {
        if ($onlyBr !== '' && strcasecmp($br, $onlyBr) !== 0) continue;
        $tok = [];
        foreach ($models as $m => $c) { $t=$firstTok($m); if($t!=='') $tok[$t][]=[$m,$c]; }
        $blocks='';
        foreach ($tok as $t=>$grp) {
            if (count($grp) < 2) continue;
            usort($grp, fn($a,$b)=>$b[1] <=> $a[1]);
            $blocks .= "     token \"{$t}\":\n";
            foreach ($grp as $g) $blocks .= "        \"".str_pad($g[0],22)."\"  {$g[1]} cars\n";
        }
        if ($blocks!=='') echo "  [{$br}]\n".$blocks;
    }
    echo "  (tip: ?do=pdupes&brand=Mercedes Benz to focus)\n";
    echo "\nDone.\n"; exit;
}

// ─────────────────────────── MODE: backfill ───────────────────────────
// Populate parsing_cars.sauto_br / sauto_mo for EVERY car (not just published),
// using the real publish resolver so each parsing car points at ONE sauto model
// in car_list. Missing models are created in car_list (createMissing=true) so the
// list is complete. After this, the /parsing filter reads brand/model from
// car_list — a single source of truth identical to the site. Dry-run unless
// &apply=1. Processes in batches; safe to re-run (skips rows already mapped).
if ($do === 'backfill') {
    $apply = ($_GET['apply'] ?? '') === '1';
    $limit = (int)($_GET['limit'] ?? 4000);
    $publisher = new \App\Services\Parsing\ParsingPublisher();

    // Ensure the mapping columns exist.
    try {
        $has = $db->query("SHOW COLUMNS FROM {$P} LIKE 'sauto_br'");
        if ($has && $has->rowCount() === 0)
            $db->exec("ALTER TABLE {$P} ADD COLUMN `sauto_br` VARCHAR(50) DEFAULT NULL,
                       ADD COLUMN `sauto_mo` VARCHAR(50) DEFAULT NULL");
    } catch (\Throwable $e) {}

    hr($apply ? "BACKFILL sauto_br/sauto_mo" : "DRY-RUN backfill (add &apply=1)");
    // Rows needing a (re)map: no brand mapping yet, OR no model mapping, OR a
    // sauto_mo that no longer resolves to a car_list row (e.g. the model was merged/
    // renamed after this row was mapped). Those "dangling" rows fall back to the raw
    // name in the dropdown and cause duplicate options — re-resolve them.
    $rows = $db->query("SELECT pc.id, pc.brand, pc.model FROM {$P} pc
        LEFT JOIN {$prefx}_car_list cl ON cl.br = pc.sauto_br AND cl.mo = pc.sauto_mo
        WHERE pc.brand IS NOT NULL AND pc.brand<>''
          AND (pc.sauto_br IS NULL OR pc.sauto_br='' OR pc.sauto_mo IS NULL OR pc.sauto_mo='' OR cl.br IS NULL)
        LIMIT {$limit}")->fetchAll(PDO::FETCH_ASSOC);
    echo "  ".count($rows)." rows needing (re)map this batch\n";

    $ok = 0; $fail = 0; $samples = []; $failSamples = [];
    $up = $db->prepare("UPDATE {$P} SET sauto_br=?, sauto_mo=? WHERE id=?");
    foreach ($rows as $r) {
        $canon = $publisher->resolveCanonicalNames((string)$r['brand'], (string)$r['model'], $apply);
        if (!$canon) {
            $fail++;
            if (count($failSamples) < 20) $failSamples[] = "{$r['brand']} / {$r['model']}";
            continue;
        }
        $ok++;
        if (count($samples) < 20) $samples[] = "{$r['brand']} / {$r['model']}  →  {$canon['br_nm']} / {$canon['mo_nm']}";
        if ($apply) $up->execute([$canon['br'], $canon['mo'], $r['id']]);
    }

    if ($samples) { hr("sample mappings"); foreach ($samples as $s) echo "  {$s}\n"; }
    if ($failSamples) { hr("could not map (need brand in car_list)"); foreach ($failSamples as $s) echo "  {$s}\n"; }

    hr("RESULT");
    echo "  mapped: {$ok}   unmapped: {$fail}   (batch of ".count($rows).")\n";
    $remain = (int)$db->query("SELECT COUNT(*) FROM {$P} pc
        LEFT JOIN {$prefx}_car_list cl ON cl.br = pc.sauto_br AND cl.mo = pc.sauto_mo
        WHERE pc.brand IS NOT NULL AND pc.brand<>''
          AND (pc.sauto_br IS NULL OR pc.sauto_br='' OR pc.sauto_mo IS NULL OR pc.sauto_mo='' OR cl.br IS NULL)")->fetchColumn();
    echo "  still needing map in table: {$remain}".($remain>0 && $apply ? "  — re-run to continue" : "")."\n";
    echo "  ".($apply?"applied.":"dry-run — add &apply=1 (note: dry-run can't match models needing creation).")."\n";
    echo "\nDone.\n"; exit;
}

// ─────────────────────────── MODE: brnm ───────────────────────────
// Show every distinct br code + br_nm in car_list (the spelling the /parsing
// dropdown now displays), plus how many parsing cars map to each. Reveals why a
// brand shows twice: two br codes, or a br_nm that still needs fixing. &fix=1
// applies known spelling fixes to car_list.br_nm (Byd→BYD, Citroen→Citroën).
if ($do === 'brnm') {
    $L = "{$prefx}_car_list";
    $fix = ($_GET['fix'] ?? '') === '1';
    static $BR_NM_FIX = ['Byd'=>'BYD', 'Citroen'=>'Citroën', 'Mercedes-Benz'=>'Mercedes Benz'];

    hr("car_list brands + parsing-car counts");
    $rows = $db->query("SELECT br, br_nm FROM {$L} GROUP BY br, br_nm ORDER BY br_nm")->fetchAll(PDO::FETCH_ASSOC);
    // Count parsing cars per br (via sauto_br).
    $cnt = [];
    foreach ($db->query("SELECT sauto_br, COUNT(*) c FROM {$P} WHERE sauto_br IS NOT NULL AND sauto_br<>'' GROUP BY sauto_br") as $r)
        $cnt[$r['sauto_br']] = (int)$r['c'];
    foreach ($rows as $r) {
        $c = $cnt[$r['br']] ?? 0;
        $flag = isset($BR_NM_FIX[$r['br_nm']]) ? "  ← fix to \"{$BR_NM_FIX[$r['br_nm']]}\"" : '';
        echo "  br=".str_pad($r['br'],18)." br_nm=\"".str_pad($r['br_nm'],18)."\"  {$c} parsing cars{$flag}\n";
    }

    if ($fix) {
        hr("APPLYING br_nm spelling fixes");
        foreach ($BR_NM_FIX as $from => $to) {
            $n = $db->prepare("UPDATE {$L} SET br_nm=? WHERE br_nm=?");
            $n->execute([$to, $from]);
            echo "  \"{$from}\" → \"{$to}\": ".$n->rowCount()." car_list rows\n";
        }
        echo "  done.\n";
    } else {
        echo "\n  (add &fix=1 to correct the flagged spellings in car_list)\n";
    }
    echo "\nDone.\n"; exit;
}

// ─────────────────────────── MODE: syncnames ───────────────────────────
// car_ctlg stores br_nm/mo_nm as a SNAPSHOT taken when the ad is published, so
// fixing a spelling in car_list afterwards (?do=brnm&fix=1, mergemodels) leaves
// every already-published car on the old name. That is why /adminsauto/stock can
// list "BYD" and "Byd" as two brands while ?do=dupes reports car_list is clean.
// This realigns published ads to the catalog via the br/mo codes. Read-only
// unless &apply=1.
if ($do === 'syncnames') {
    $L = "{$prefx}_car_list";
    $apply = ($_GET['apply'] ?? '') === '1';

    // car_list is the source of truth; match on the codes, compare the labels.
    // CAST AS BINARY is required: the table collation is case- and accent-insensitive,
    // so a plain `<>` rates 'Byd' equal to 'BYD' and 'Citroen' equal to 'Citroën' —
    // exactly the differences this mode exists to find.
    $diff = "(CAST(cc.br_nm AS BINARY) <> CAST(cl.br_nm AS BINARY)
              OR CAST(cc.mo_nm AS BINARY) <> CAST(cl.mo_nm AS BINARY))";
    $sel = "FROM {$C} cc JOIN {$L} cl ON cl.br = cc.br AND cl.mo = cc.mo WHERE {$diff}";

    // What /adminsauto/stock actually renders: it groups by br_nm in PHP, so any
    // spelling variant under one br code becomes its own row in the table.
    hr("VARIANTE DE SCRIERE ÎN car_ctlg (ce sparge tabelul din /stock)");
    $split = $db->query("SELECT br, COUNT(DISTINCT CAST(br_nm AS BINARY)) v, COUNT(*) c
        FROM {$C} WHERE br<>'' AND act=1 AND n_a=0
        GROUP BY br HAVING v > 1 ORDER BY c DESC")->fetchAll(PDO::FETCH_ASSOC);
    if (!$split) {
        echo "  ✔ Fiecare cod de marcă are o singură scriere.\n";
    } else {
        foreach ($split as $s) {
            echo "  br={$s['br']} — {$s['v']} scrieri, {$s['c']} mașini:\n";
            $q = $db->prepare("SELECT br_nm, COUNT(*) c FROM {$C}
                WHERE br=? AND act=1 AND n_a=0
                GROUP BY CAST(br_nm AS BINARY) ORDER BY c DESC");
            $q->execute([$s['br']]);
            foreach ($q as $r) echo "      \"{$r['br_nm']}\"  {$r['c']} cars\n";
        }
    }

    // car_list holds one row per model, each repeating br_nm — so a single brand code
    // can carry several spellings across its model rows. Cars pointing at a wrong-spelled
    // model row mirror it faithfully, so the resync below sees no mismatch and the split
    // survives. car_list has to be fixed first (?do=brnm&fix=1).
    // Majority spelling wins, same rule ?do=dupes uses to pick a canonical brand.
    // Generic on purpose: ?do=brnm carries a hand-written map (Byd/Citroen/Mercedes-Benz)
    // that silently misses anything new, which is how "Kia" survived beside "KIA".
    // Runs BEFORE the car_ctlg resync below so one &apply=1 fixes source then copies.
    hr("VARIANTE DE SCRIERE ÎN car_list (sursa adevărului — se repară prima)");
    $lsplit = $db->query("SELECT br, COUNT(DISTINCT CAST(br_nm AS BINARY)) v
        FROM {$L} WHERE br_nm<>'' GROUP BY br HAVING v > 1")->fetchAll(PDO::FETCH_ASSOC);
    if (!$lsplit) {
        echo "  ✔ car_list e curat — fiecare cod de marcă are o singură scriere.\n";
    } else {
        $fixedList = 0;
        foreach ($lsplit as $s) {
            $q = $db->prepare("SELECT br_nm, COUNT(*) rows_ FROM {$L} WHERE br=?
                GROUP BY CAST(br_nm AS BINARY) ORDER BY rows_ DESC");
            $q->execute([$s['br']]);
            $variants = $q->fetchAll(PDO::FETCH_ASSOC);
            $winner = $variants[0]['br_nm'];
            echo "  br={$s['br']} — {$s['v']} scrieri:\n";
            foreach ($variants as $i => $r)
                echo "      \"{$r['br_nm']}\"  {$r['rows_']} rânduri model"
                   . ($i === 0 ? "   <= PĂSTREAZĂ\n" : "   → \"{$winner}\"\n");
            if ($apply) {
                // Rewrite every row of this br: the WHERE is collation-insensitive, so
                // it sweeps all variants at once and only the differing ones change.
                $u = $db->prepare("UPDATE {$L} SET br_nm=? WHERE br=?");
                $u->execute([$winner, $s['br']]);
                $fixedList += $u->rowCount();
            }
        }
        echo $apply
            ? "\n  ✔ car_list corectat: {$fixedList} rânduri rescrise.\n"
            : "\n  (read-only — &apply=1 corectează car_list, apoi propagă în mașini)\n";
    }

    hr($apply ? "APPLYING car_ctlg name resync" : "DRY-RUN car_ctlg name resync (add &apply=1)");
    $total = (int)$db->query("SELECT COUNT(*) {$sel}")->fetchColumn();
    echo "  mașini cu nume desincronizat față de car_list: {$total}\n";

    if ($total) {
        hr("ce se schimbă (grupat)");
        // Group on the binary values too — grouping by the plain columns would fold
        // 'Byd' into 'BYD' under the case-insensitive collation and hide the split.
        foreach ($db->query("SELECT cc.br_nm old_br, cl.br_nm new_br, cc.mo_nm old_mo, cl.mo_nm new_mo,
                             COUNT(*) c {$sel}
                             GROUP BY CAST(cc.br_nm AS BINARY), CAST(cl.br_nm AS BINARY),
                                      CAST(cc.mo_nm AS BINARY), CAST(cl.mo_nm AS BINARY)
                             ORDER BY c DESC LIMIT 100") as $r) {
            $brPart = ($r['old_br'] !== $r['new_br']) ? "\"{$r['old_br']}\" → \"{$r['new_br']}\"" : $r['new_br'];
            $moPart = ($r['old_mo'] !== $r['new_mo']) ? "\"{$r['old_mo']}\" → \"{$r['new_mo']}\"" : $r['new_mo'];
            echo "  ".str_pad($brPart, 34)."  ".str_pad($moPart, 34)."  {$r['c']} cars\n";
        }
    }

    if ($apply && $total) {
        $n = $db->exec("UPDATE {$C} cc JOIN {$L} cl ON cl.br = cc.br AND cl.mo = cc.mo
                        SET cc.br_nm = cl.br_nm, cc.mo_nm = cl.mo_nm
                        WHERE {$diff}");
        hr("RESULT");
        echo "  actualizate: {$n} rânduri car_ctlg\n";
    } elseif (!$total) {
        echo "\n  ✔ Nimic de sincronizat — numele publicate coincid cu catalogul.\n";
    } else {
        echo "\n  (read-only — adaugă &apply=1 ca să scrie)\n";
    }

    // Ads whose br/mo no longer exist in car_list keep a stale name forever and the
    // join above can never reach them.
    $orphans = (int)$db->query("SELECT COUNT(*) FROM {$C} cc
        LEFT JOIN {$L} cl ON cl.br = cc.br AND cl.mo = cc.mo
        WHERE cc.act=1 AND cc.n_a=0 AND cl.br IS NULL")->fetchColumn();
    if ($orphans) {
        hr("ATENȚIE — mașini fără corespondent în car_list");
        echo "  {$orphans} anunțuri active au br/mo care nu mai există în catalog.\n";
        echo "  Nu pot fi resincronizate automat (marcă/model șters sau redenumit).\n";
        foreach ($db->query("SELECT cc.br_nm, cc.mo_nm, cc.br, cc.mo, COUNT(*) c FROM {$C} cc
            LEFT JOIN {$L} cl ON cl.br = cc.br AND cl.mo = cc.mo
            WHERE cc.act=1 AND cc.n_a=0 AND cl.br IS NULL
            GROUP BY cc.br, cc.mo ORDER BY c DESC LIMIT 30") as $r)
            echo "    br={".str_pad($r['br'],16)."} mo={".str_pad($r['mo'],18)."} \"{$r['br_nm']} {$r['mo_nm']}\"  {$r['c']} cars\n";
    }

    echo "\nDone.\n"; exit;
}

// ─────────────────────────── MODE: modelmap ───────────────────────────
// For one brand (?brand=Mercedes Benz), show how each raw parsing model maps to a
// car_list model (mo_nm) — reveals odd mappings (eVito→? ) and models that exist
// in the dropdown. Read-only.
if ($do === 'modelmap') {
    $L = "{$prefx}_car_list";
    $brandName = trim((string)($_GET['brand'] ?? 'Mercedes Benz'));
    $bs = $db->prepare("SELECT br FROM {$L} WHERE LOWER(br_nm)=LOWER(?) LIMIT 1");
    $bs->execute([$brandName]); $brCode = $bs->fetchColumn();
    hr("car_list models for {$brandName} (br={$brCode})");
    if ($brCode !== false) {
        foreach ($db->query("SELECT mo, mo_nm FROM {$L} WHERE br=".$db->quote($brCode)." ORDER BY mo_nm") as $r)
            echo "  {$r['mo']}  =>  \"{$r['mo_nm']}\"\n";
    }
    hr("raw parsing model → mapped car_list model");
    $st = $db->prepare("SELECT pc.model AS raw, cl.mo_nm AS mapped, pc.sauto_mo, COUNT(*) c
        FROM {$P} pc
        LEFT JOIN {$L} cl ON cl.br=pc.sauto_br AND cl.mo=pc.sauto_mo
        WHERE pc.sauto_br=? GROUP BY pc.model, cl.mo_nm, pc.sauto_mo ORDER BY pc.model");
    $st->execute([$brCode]);
    foreach ($st as $r) {
        $mapped = $r['mapped'] ?? '(unmapped)';
        $flag = (mb_strtolower(trim($r['raw'])) !== mb_strtolower(trim((string)$mapped))) ? '' : '';
        echo "  raw=\"".str_pad($r['raw'],22)."\" → \"".str_pad((string)$mapped,22)."\" (mo={$r['sauto_mo']}, {$r['c']})\n";
    }
    echo "\nDone.\n"; exit;
}

// ─────────────────────────── MODE: unmapped ───────────────────────────
// Show parsing_cars rows still without a sauto_br/sauto_mo mapping, grouped by
// raw brand/model, and WHY each fails to resolve (no brand match / no model match).
// Read-only diagnostic.
if ($do === 'unmapped') {
    $publisher = new \App\Services\Parsing\ParsingPublisher();
    $rows = $db->query("SELECT pc.brand, pc.model, COUNT(*) c FROM {$P} pc
        LEFT JOIN {$prefx}_car_list cl ON cl.br = pc.sauto_br AND cl.mo = pc.sauto_mo
        WHERE pc.brand IS NOT NULL AND pc.brand<>''
          AND (pc.sauto_br IS NULL OR pc.sauto_br='' OR pc.sauto_mo IS NULL OR pc.sauto_mo='' OR cl.br IS NULL)
        GROUP BY pc.brand, pc.model ORDER BY c DESC")->fetchAll(PDO::FETCH_ASSOC);
    hr("UNMAPPED parsing_cars (no sauto_br/mo, or dangling mapping)");
    if (!$rows) { echo "  none — every car is mapped\n\nDone.\n"; exit; }
    foreach ($rows as $r) {
        $b = (string)$r['brand']; $m = (string)$r['model']; $c = (int)$r['c'];
        // Probe: can we resolve without creating? then with creating?
        $noCreate = $publisher->resolveCanonicalNames($b, $m, false);
        $why = $noCreate ? "OK (would map to {$noCreate['br_nm']}/{$noCreate['mo_nm']})"
                         : "needs create OR brand not in car_list";
        echo "  ".str_pad("{$b} / {$m}", 40)." {$c}x   → {$why}\n";
    }
    echo "\n  (run ?do=backfill&apply=1 to map the OK ones; the rest need the brand added to car_list)\n";
    echo "\nDone.\n"; exit;
}

// ─────────────────────────── MODE: prevert ───────────────────────────
// Undo parsingfix_backup: restore parsing_cars.brand/model to their ORIGINAL
// (pre-mergeparsing) values. Use when a normalization went the wrong way. Matches
// current rows by the recorded NEW value and writes back the OLD value. Dry-run
// unless &apply=1.
if ($do === 'prevert') {
    $apply = ($_GET['apply'] ?? '') === '1';
    hr($apply ? "REVERTING parsing_cars from backup" : "DRY-RUN revert (add &apply=1)");
    try { $bk = $db->query("SELECT * FROM {$prefx}_parsingfix_backup")->fetchAll(PDO::FETCH_ASSOC); }
    catch (\Throwable $e) { echo "  no backup table\n\nDone.\n"; exit; }
    $n = 0;
    foreach ($bk as $r) {
        // Restore by id (exact row), safest. brand_old/model_old = original.
        if ($apply) {
            $db->prepare("UPDATE {$P} SET brand=?, model=? WHERE id=?")
               ->execute([$r['brand_old'], $r['model_old'], $r['id']]);
        }
        $n++;
    }
    echo "  ".($apply?"restored":"would restore")." {$n} rows to original brand/model\n";
    echo "  ".($apply?"done — you can now re-run the correct normalization.":"add &apply=1 to execute.")."\n";
    echo "\nDone.\n"; exit;
}

// ─────────────────────────── MODE: pcanon ───────────────────────────
// Align parsing_cars.model to the SAUTO canonical spelling (car_list.mo_nm), e.g.
// "C"/"C Break" → "C-Class", so the /parsing dropdown matches the public site and
// car_list. Uses the SAME brand→br + fuzzy model match as ParsingPublisher, then
// rewrites parsing_cars.model to the matched mo_nm. Brands are also fixed. Cars
// with no confident sauto match are left as-is (reported). Dry-run unless &apply=1.
if ($do === 'pcanon') {
    $apply = ($_GET['apply'] ?? '') === '1';
    $onlyBr = trim((string)($_GET['brand'] ?? ''));

    // Use the REAL publishing resolver so parsing_cars ends up with the exact same
    // sauto names publishing would assign — "C Break"→"C-Class", "A6 Avant"→"A6".
    // createMissing=false: audit never creates catalog rows; unmatched stay as-is.
    $publisher = new \App\Services\Parsing\ParsingPublisher();

    hr($apply ? "APPLYING parsing→sauto canonical" : "DRY-RUN parsing→sauto canonical (add &apply=1)");
    if ($apply) {
        $db->exec("CREATE TABLE IF NOT EXISTS {$prefx}_parsingfix_backup (
            id INT PRIMARY KEY, brand_old VARCHAR(120), model_old VARCHAR(160),
            brand_new VARCHAR(120), model_new VARCHAR(160), fixed_at DATETIME)");
    }

    $rows = $db->query("SELECT brand, model, COUNT(*) c FROM {$P}
        WHERE brand IS NOT NULL AND brand<>'' GROUP BY brand, model ORDER BY brand, model")->fetchAll(PDO::FETCH_ASSOC);
    $changed = 0; $cars = 0; $unmatched = [];
    foreach ($rows as $r) {
        $b = trim((string)$r['brand']); $m = trim((string)$r['model']); $c = (int)$r['c'];
        if ($onlyBr !== '' && strcasecmp($b, $onlyBr) !== 0) continue;
        $canon = $publisher->resolveCanonicalNames($b, $m, false);
        $nb = $canon['br_nm'] ?? $b;
        $nm = $canon['mo_nm'] ?? $m;
        if ($canon === null) { $unmatched[] = "{$b} / {$m} ({$c})"; continue; }
        if ($nb === $b && $nm === $m) continue;
        $changed++; $cars += $c;
        $tag = [];
        if ($nb !== $b) $tag[] = "brand \"{$b}\"→\"{$nb}\"";
        if ($nm !== $m) $tag[] = "model \"{$m}\"→\"{$nm}\"";
        echo "  ".implode(', ', $tag)."  ({$c})\n";
        if ($apply) {
            $db->prepare("INSERT INTO {$prefx}_parsingfix_backup
                (id,brand_old,model_old,brand_new,model_new,fixed_at)
                SELECT id,brand,model,?,?,NOW() FROM {$P} WHERE brand=? AND model=?
                ON DUPLICATE KEY UPDATE brand_new=VALUES(brand_new), model_new=VALUES(model_new)")
               ->execute([$nb,$nm,$b,$m]);
            $db->prepare("UPDATE {$P} SET brand=?, model=? WHERE brand=? AND model=?")
               ->execute([$nb,$nm,$b,$m]);
        }
    }
    if ($unmatched) {
        hr("NO sauto match (left as-is)");
        foreach (array_slice($unmatched, 0, 60) as $u) echo "  {$u}\n";
        if (count($unmatched) > 60) echo "  ... +".(count($unmatched)-60)." more\n";
    }
    hr("RESULT");
    echo "  ".($changed)." pairs changed ({$cars} cars); ".count($unmatched)." unmatched\n";
    echo "  ".($apply?"applied. backup in {$prefx}_parsingfix_backup.":"dry-run — add &apply=1.")."\n";
    echo "\nDone.\n"; exit;
}

// ─────────────────────────── MODE: mergeparsing ───────────────────────────
// Normalize RAW brand/model text in gh3sp_parsing_cars so the /parsing filter
// dropdown stops showing duplicates. Brands: fixed spelling map. Models: strip
// safe variant suffixes (commercial sizes L2H2/box/dropside, wagon Avant/Touring/
// Combi/Break, sport AMG/M-badge) BUT protect genuinely-distinct models via a
// keep-list. Dry-run by default; &apply=1 writes. Backs up every changed row.
//
//   ?do=mergeparsing            → preview
//   ?do=mergeparsing&apply=1     → back up + rewrite brand/model
if ($do === 'mergeparsing') {
    $apply = ($_GET['apply'] ?? '') === '1';

    // Brand spelling → canonical (the spelling with the most cars, from pdupes).
    $BRAND_FIX = [
        'byd'           => 'BYD',
        'citroen'       => 'Citroën',
        'mercedes-benz' => 'Mercedes Benz',
    ];

    // Models that CONTAIN a strip-word but are their OWN model — never touch.
    // Matched case-insensitively against the whole raw model name.
    $KEEP = [
        'amg gt', 'amg gt s',                      // AMG GT is a model, not "GT" of AMG
        'glc coupé', 'glc coupe', 'gle coupé', 'gle coupe',
        '2 series gran coupé', '2 series gran tourer', '2 series active tourer',
        '4 series coupé', '4 series gran coupé', '6 series gt', '3 series gt',
        'grand koleos', 'grand scenic', 'grand kangoo', // "Grand X" real Renault models
        'corolla cross', 'yaris cross', 'aygo x', 'proace city', 'e-proace city',
        'transit custom', 'transit connect', 'transit courier',  // distinct Ford models
        'tourneo connect', 'tourneo courier', 'combo life', 'kangoo express',
        'countryman cooper se', 'countryman cooper d', 'countryman cooper s',
        'discovery sport', 'range rover velar', 'range rover evoque', 'range rover sport',
        'expert long', 'crafter', 'id.3', 'id.4', 'id.5',
    ];

    // Suffix patterns stripped from the END of a model name (→ base model). Order
    // matters: longest/most-specific first. Applied only if result is non-empty.
    $STRIP = [
        '/\s+L\d+H\d+$/i',                              // L2H2, L1H1 commercial sizes
        '/\s+(box|dropside|flatbed|chassis cab)$/i',    // commercial body styles
        // Wagon/estate bodies. "Sports Tourer"/"Touring Sports" before bare "Touring".
        '/\s+(Sports Tourer|Touring Sports|Sportstourer|Grand Tourer|Grandtour|Variant|Combi|Touring|Avant|Allroad|Break|Shooting Brake|Wagon|Estate)$/i',
        '/\s+(Gran Coupé|Gran Coupe)$/i',
        '/\s+(AMG|GTI|GTE|GT S|RS|VZ|Long|Maxi|Passenger|Tourer|Express)$/i',
        '/\s+M\d{2,3}[a-z]?$/i',                         // M340d, M550, M135, M440
        '/\s+M[1-9]$/i',                                 // M3, M5, M8 badge (single digit)
        '/\s+M$/i',                                      // i4 M, X5 M
    ];

    hr($apply ? "APPLYING parsing_cars normalize" : "DRY-RUN parsing_cars normalize (add &apply=1)");
    if ($apply) {
        $db->exec("CREATE TABLE IF NOT EXISTS {$prefx}_parsingfix_backup (
            id INT PRIMARY KEY, brand_old VARCHAR(120), model_old VARCHAR(160),
            brand_new VARCHAR(120), model_new VARCHAR(160), fixed_at DATETIME)");
    }

    $keepSet = array_flip(array_map(fn($s)=>mb_strtolower($s,'UTF-8'), $KEEP));
    $canonModel = function(string $m) use ($STRIP, $keepSet): string {
        $orig = $m = trim($m);
        if ($m === '' || isset($keepSet[mb_strtolower($m,'UTF-8')])) return $orig;
        // Strip repeatedly (e.g. "Transit Custom L2H1" → drop size, keep "Custom"
        // which is in KEEP so it stops). One pass per pattern, loop until stable.
        for ($i=0; $i<4; $i++) {
            $before = $m;
            foreach ($STRIP as $rx) {
                $try = preg_replace($rx, '', $m);
                if ($try !== $m && trim($try) !== '' && !isset($keepSet[mb_strtolower(trim($try),'UTF-8')])) {
                    // Re-check: if the STRIPPED result is itself a keep-model, don't strip.
                    $m = trim($try);
                }
            }
            if ($m === $before) break;
        }
        return $m !== '' ? $m : $orig;
    };

    // Load every distinct (brand,model) and compute its normalized form.
    $rows = $db->query("SELECT brand, model, COUNT(*) c FROM {$P}
        WHERE brand IS NOT NULL AND brand<>'' GROUP BY brand, model
        ORDER BY brand, model")->fetchAll(PDO::FETCH_ASSOC);
    $brandChanges = 0; $modelChanges = 0; $carsBrand = 0; $carsModel = 0;
    foreach ($rows as $r) {
        $b = trim((string)$r['brand']); $m = trim((string)$r['model']); $c = (int)$r['c'];
        $nb = $BRAND_FIX[mb_strtolower($b,'UTF-8')] ?? $b;
        $nm = $canonModel($m);
        // KEEP-list protects distinct models even when brand-only case differs.
        if (isset($keepSet[mb_strtolower($m,'UTF-8')])) $nm = $m;
        if ($nb === $b && $nm === $m) continue;
        $tag = [];
        if ($nb !== $b) { $brandChanges++; $carsBrand += $c; $tag[] = "brand \"{$b}\"→\"{$nb}\""; }
        if ($nm !== $m) { $modelChanges++; $carsModel += $c; $tag[] = "model \"{$m}\"→\"{$nm}\""; }
        echo "  ".implode(', ', $tag)."  ({$c} cars)\n";
        if ($apply) {
            $db->prepare("INSERT INTO {$prefx}_parsingfix_backup
                (id,brand_old,model_old,brand_new,model_new,fixed_at)
                SELECT id,brand,model,?,?,NOW() FROM {$P} WHERE brand=? AND model=?
                ON DUPLICATE KEY UPDATE brand_new=VALUES(brand_new), model_new=VALUES(model_new)")
               ->execute([$nb,$nm,$b,$m]);
            $db->prepare("UPDATE {$P} SET brand=?, model=? WHERE brand=? AND model=?")
               ->execute([$nb,$nm,$b,$m]);
        }
    }

    hr("RESULT");
    echo "  brand fixes: {$brandChanges} pairs ({$carsBrand} cars);  model fixes: {$modelChanges} pairs ({$carsModel} cars)\n";
    echo "  ".($apply?"applied. backup in {$prefx}_parsingfix_backup (reversible).":"dry-run — add &apply=1 to write.")."\n";
    echo "\nDone.\n"; exit;
}

// ─────────────────────── MODE: encrec (read-only) ───────────────────────
// Dump the raw Encar "record" node so we can see how Encar marks an accident
// as damage-to-this-car vs damage-caused-to-others. ?do=encrec[&id=<parsing_id>]
if ($do === 'encrec') {
    $id = (int)($_GET['id'] ?? 0);
    if ($id > 0) {
        $st = $db->prepare("SELECT id, brand, model, report_data FROM {$P} WHERE id = ? LIMIT 1");
        $st->execute([$id]);
        $rows = $st->fetchAll(PDO::FETCH_ASSOC);
    } else {
        // No id given: pick cars that actually have a third-party claim, since
        // those are the only ones where the two lists differ.
        $rows = $db->query("SELECT id, brand, model, report_data FROM {$P}
                            WHERE report_data LIKE '%otherAccidentCnt%'
                              AND report_data NOT LIKE '%\"otherAccidentCnt\":0%'
                            ORDER BY id DESC LIMIT 3")->fetchAll(PDO::FETCH_ASSOC);
    }
    if (!$rows) { echo "\nNo car found.\n"; exit; }

    foreach ($rows as $row) {
        hr("car {$row['id']}  {$row['brand']} {$row['model']}");
        $rd  = json_decode((string)$row['report_data'], true);
        $rec = $rd['record'] ?? null;
        if (!is_array($rec)) { echo "  no record node\n"; continue; }

        echo "  counters:\n";
        foreach (['accidentCnt','myAccidentCnt','otherAccidentCnt',
                  'myAccidentCost','otherAccidentCost','ownerChangeCnt'] as $k) {
            if (array_key_exists($k, $rec)) echo "    {$k} = ".var_export($rec[$k], true)."\n";
        }

        echo "  top-level keys: ".implode(', ', array_keys($rec))."\n";

        $acc = $rec['accidents'] ?? null;
        if (!is_array($acc)) { echo "  accidents: none\n"; continue; }
        echo "  accidents (".count($acc)." items), raw:\n";
        foreach ($acc as $i => $a) {
            echo "    [{$i}] ".json_encode($a, JSON_UNESCAPED_UNICODE)."\n";
        }
    }
    echo "\nDone.\n"; exit;
}

// ─────────────────────── MODE: nophotos (read-only) ───────────────────────
// Live ads with an empty gallery. Splits the two causes apart: the source gave
// us no images at all, vs. we had image URLs and the import did not write them.
if ($do === 'nophotos') {
    // Scan car_ctlg ONCE and keep only ids — the NOT EXISTS pass is the expensive
    // part, and repeating it per section is what made this time out. Everything
    // else is then a keyed lookup over a couple of dozen rows.
    $hits = $db->query("SELECT c.id, c.parsing_id, c.br_nm, c.mo_nm, c.dt, c.vis, c.act, c.catalog_type
                        FROM {$C} c
                        WHERE c.parsing_id IS NOT NULL
                          AND NOT EXISTS (SELECT 1 FROM {$prefx}_car_pht ph WHERE ph.it_id = c.id)
                        ORDER BY c.id DESC")->fetchAll(PDO::FETCH_ASSOC);

    $meta = [];
    if ($hits) {
        $pids = array_values(array_unique(array_map(fn($r) => (int)$r['parsing_id'], $hits)));
        $in   = implode(',', array_fill(0, count($pids), '?'));
        $st   = $db->prepare("SELECT id, source, status, images_local FROM {$P} WHERE id IN ({$in})");
        $st->execute($pids);
        foreach ($st->fetchAll(PDO::FETCH_ASSOC) as $m) { $meta[(int)$m['id']] = $m; }
    }

    $urlCount = function ($row) {
        $imgs = json_decode((string)($row['images_local'] ?? ''), true);
        return is_array($imgs) ? count($imgs) : 0;
    };

    $live = 0; $fixable = 0; $bySource = [];
    foreach ($hits as $r) {
        $m = $meta[(int)$r['parsing_id']] ?? [];
        $n = $urlCount($m);
        if ($r['vis'] === '1' && $r['act'] === '1') $live++;
        if ($n > 0) $fixable++;
        $src = $m['source'] ?? '(none)';
        $bySource[$src]['n'] = ($bySource[$src]['n'] ?? 0) + 1;
        $bySource[$src]['f'] = ($bySource[$src]['f'] ?? 0) + ($n > 0 ? 1 : 0);
    }

    hr("summary");
    echo "  published cars with no photos:      ".count($hits)."\n";
    echo "    of which visible on the site now:  {$live}\n";
    echo "    image URLs still stored (fixable): {$fixable}\n";
    echo "    nothing to import from the source: ".(count($hits) - $fixable)."\n";

    hr("by source");
    foreach ($bySource as $src => $b) {
        echo "  ".str_pad($src, 12).str_pad((string)$b['n'], 8)."{$b['f']} fixable\n";
    }

    hr("the fixable ones");
    $any = false;
    foreach ($hits as $r) {
        $m = $meta[(int)$r['parsing_id']] ?? [];
        $n = $urlCount($m);
        if ($n === 0) continue;
        $any = true;
        echo "  ctlg ".str_pad((string)$r['id'], 7)." parsing ".str_pad((string)$r['parsing_id'], 8)
           . str_pad((string)($m['source'] ?? '?'), 11).str_pad($r['br_nm'].' '.$r['mo_nm'], 24)
           . " {$n} urls  {$r['catalog_type']}  vis={$r['vis']} act={$r['act']}  {$r['dt']}\n";
    }
    if (!$any) echo "  none\n";

    // Re-queue: the worker now resumes a published-but-photoless car instead of
    // failing on it, so enqueueing is all the repair needs.
    if (($_GET['fix'] ?? '') === '1') {
        $apply = ($_GET['apply'] ?? '') === '1';
        hr($apply ? "RE-QUEUEING the fixable ones" : "DRY RUN — add &apply=1 to actually queue");
        $queue = new \App\Services\Parsing\PublishQueue();
        $n = 0;
        foreach ($hits as $r) {
            $m = $meta[(int)$r['parsing_id']] ?? [];
            if ($urlCount($m) === 0) continue;
            $n++;
            echo "  ctlg {$r['id']}  parsing {$r['parsing_id']}  {$r['br_nm']} {$r['mo_nm']}\n";
            if ($apply) {
                $queue->clearAutoPublishFailed((int)$r['parsing_id']);
                $queue->enqueue((int)$r['parsing_id'], 'sauto');
            }
        }
        echo "\n  {$n} cars ".($apply ? "queued — the worker picks them up within a minute" : "would be queued")."\n";
    }

    hr("the ones we cannot fix (source stored no image URLs)");
    $any = false;
    foreach ($hits as $r) {
        $m = $meta[(int)$r['parsing_id']] ?? [];
        if ($urlCount($m) > 0) continue;
        $any = true;
        echo "  ctlg ".str_pad((string)$r['id'], 7)." parsing ".str_pad((string)$r['parsing_id'], 8)
           . str_pad((string)($m['source'] ?? '?'), 11).str_pad($r['br_nm'].' '.$r['mo_nm'], 24)
           . " {$r['catalog_type']}  vis={$r['vis']} act={$r['act']}  {$r['dt']}\n";
    }
    if (!$any) echo "  none\n";

    echo "\n  'repairable' = images_local still holds the URLs, so re-running the photo\n";
    echo "  import would fill the gallery without touching anything else.\n";
    echo "\nDone.\n"; exit;
}

// ─────────────────────── MODE: alreadypub (read-only) ───────────────────────
// Why "Already published" failures happen. Two very different causes look the
// same in the UI, and `attempts` plus the publish timestamps separate them:
//   attempts > 1  -> the worker died mid-job and a later run re-claimed it
//   published_at BEFORE started_at -> the car was published by another route
//                                     before this job ever got to run
if ($do === 'alreadypub') {
    $Q = "{$prefx}_parsing_publish_queue";

    // The failures panel DELETES a job the moment "Edit" is clicked, so by the
    // time anyone investigates the rows are gone. Show the whole queue state
    // first — that tells us whether there is anything left to look at.
    hr("queue by status");
    foreach ($db->query("SELECT status, COUNT(*) c FROM {$Q} GROUP BY status ORDER BY c DESC") as $r) {
        echo "  ".str_pad($r['status'], 12).$r['c']."\n";
    }

    hr("every failed job, whatever the reason");
    foreach ($db->query("SELECT error, COUNT(*) c FROM {$Q} WHERE status='failed'
                         GROUP BY error ORDER BY c DESC LIMIT 20") as $r) {
        echo "  ×".str_pad((string)$r['c'], 5).substr(str_replace(["\n","\r"], ' ', (string)$r['error']), 0, 160)."\n";
    }

    // Cars auto-publish gave up on outlive the queue row, so they are the durable
    // record of what went wrong — even after the panel row was dismissed.
    hr("cars flagged autopublish_failed (survives dismissing)");
    try {
        $af = $db->query("SELECT COUNT(*) FROM {$P} WHERE COALESCE(autopublish_failed,0)=1")->fetchColumn();
        echo "  {$af} cars are excluded from auto-publish until retried manually\n";
        $rows = $db->query("SELECT id, brand, model, status, published_at, car_ctlg_id, source
                            FROM {$P} WHERE COALESCE(autopublish_failed,0)=1
                            ORDER BY id DESC LIMIT 20")->fetchAll(PDO::FETCH_ASSOC);
        foreach ($rows as $r) {
            $photos = 0;
            if (!empty($r['car_ctlg_id'])) {
                $ps = $db->prepare("SELECT COUNT(*) FROM {$prefx}_car_pht WHERE it_id = ?");
                $ps->execute([(int)$r['car_ctlg_id']]);
                $photos = (int)$ps->fetchColumn();
            }
            echo "  parsing {$r['id']}  {$r['source']}  ".str_pad($r['brand'].' '.$r['model'], 24)
               . " status={$r['status']}  ctlg={$r['car_ctlg_id']}  photos={$photos}"
               . ($r['car_ctlg_id'] && $photos === 0 ? "   *** NO PHOTOS ***" : "")."\n";
        }
    } catch (\Throwable $e) { echo "  (column not present: ".$e->getMessage().")\n"; }

    hr("failed jobs with 'Already published'");
    $rows = $db->query("SELECT q.id, q.parsing_car_id, q.target, q.attempts, q.status,
                               q.started_at, q.finished_at, q.car_ctlg_id q_ctlg,
                               p.status p_status, p.published_at, p.car_ctlg_id p_ctlg,
                               p.brand, p.model, p.source
                        FROM {$Q} q LEFT JOIN {$P} p ON p.id = q.parsing_car_id
                        WHERE q.error LIKE '%Already published%'
                        ORDER BY q.id DESC LIMIT 40")->fetchAll(PDO::FETCH_ASSOC);
    if (!$rows) { echo "  none left — the panel rows were dismissed (Edit deletes them)\n\nDone.\n"; exit; }

    $diedMid = 0; $preExisting = 0; $unknown = 0;
    foreach ($rows as $r) {
        // Did the car become 'published' before this job was even claimed?
        $verdict = '?';
        if ((int)$r['attempts'] > 1) { $verdict = 'worker died mid-job'; $diedMid++; }
        elseif ($r['published_at'] && $r['started_at'] && $r['published_at'] < $r['started_at']) {
            $verdict = 'published earlier, by another route'; $preExisting++;
        } else { $unknown++; }

        // The real question for the customer: did the car end up complete?
        $photos = 0;
        if (!empty($r['p_ctlg'])) {
            $ps = $db->prepare("SELECT COUNT(*) FROM {$prefx}_car_pht WHERE it_id = ?");
            $ps->execute([(int)$r['p_ctlg']]);
            $photos = (int)$ps->fetchColumn();
        }
        echo "  job {$r['id']}  parsing {$r['parsing_car_id']}  {$r['source']}  {$r['brand']} {$r['model']}\n";
        echo "      attempts={$r['attempts']}  started={$r['started_at']}  finished={$r['finished_at']}\n";
        echo "      parsing.status={$r['p_status']}  published_at={$r['published_at']}  car_ctlg={$r['p_ctlg']}  photos={$photos}\n";
        echo "      => {$verdict}".($photos === 0 && !empty($r['p_ctlg']) ? "   *** NO PHOTOS ***" : "")."\n";
    }

    hr("verdict");
    echo "  worker died mid-job:                {$diedMid}\n";
    echo "  already published by another route: {$preExisting}\n";
    echo "  undetermined:                       {$unknown}\n";
    echo "\n  MAX_ATTEMPTS is 1, so any failure is terminal on the first run and the\n";
    echo "  car is flagged autopublish_failed=1 — that is why it stays in the panel.\n";
    echo "\nDone.\n"; exit;
}

// ─────────────────────── MODE: bodybadge (read-only) ───────────────────────
// Two questions at once: what the card badge would show for real on-order cars,
// and how expensive report_data is to load for a whole page of cards.
if ($do === 'bodybadge') {
    require_once __DIR__.'/../content/admin/page/parsing/parsing_report.php';

    hr("COST: report_data size for published on-order Encar cars");
    $sz = $db->query("SELECT COUNT(*) n, ROUND(AVG(LENGTH(p.report_data))/1024,1) avg_kb,
                             ROUND(MAX(LENGTH(p.report_data))/1024,1) max_kb,
                             ROUND(SUM(LENGTH(p.report_data))/1048576,1) total_mb
                      FROM {$C} c JOIN {$P} p ON p.id = c.parsing_id
                      WHERE c.catalog_type='on_order' AND c.vis='1' AND c.act='1'
                        AND p.report_data IS NOT NULL AND p.report_data <> ''")->fetch(PDO::FETCH_ASSOC);
    echo "  cars with a report: {$sz['n']}\n";
    echo "  avg {$sz['avg_kb']} KB   max {$sz['max_kb']} KB   all together {$sz['total_mb']} MB\n";
    $perPage = 24;
    echo "  => one page of {$perPage} cards would move ~".round(((float)$sz['avg_kb'] * $perPage) / 1024, 1)." MB\n";

    hr("BADGE on the 12 newest on-order cars");
    $rows = $db->query("SELECT c.id, c.br_nm, c.mo_nm, p.report_data
                        FROM {$C} c JOIN {$P} p ON p.id = c.parsing_id
                        WHERE c.catalog_type='on_order' AND c.vis='1' AND c.act='1'
                          AND p.report_data IS NOT NULL AND p.report_data <> ''
                        ORDER BY c.id DESC LIMIT 12")->fetchAll(PDO::FETCH_ASSOC);
    $t0 = microtime(true);
    foreach ($rows as $r) {
        $badge = parsing_report_body_badge(json_decode((string)$r['report_data'], true), 'ro');
        $show  = $badge === null
            ? '(fara raport — fara badge)'
            : str_pad($badge['count'].' '.($badge['letter'] ?: '-'), 6).' '.$badge['title'];
        echo "  ".str_pad((string)$r['id'], 7)." ".str_pad($r['br_nm'].' '.$r['mo_nm'], 26)." {$show}\n";
    }
    $ms = round((microtime(true) - $t0) * 1000, 1);
    echo "\n  decoding + computing ".count($rows)." cars took {$ms} ms\n";
    echo "\nDone.\n"; exit;
}

echo "Unknown mode. Use ?do=test|dupes|pdupes|mergemodels|mergeparsing|...|sim999|whyskip|api999models\n";
