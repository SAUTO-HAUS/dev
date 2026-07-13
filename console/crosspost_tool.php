<?php
/**
 * One tool for the auto cross-post pipeline. Pick a mode with ?do=…
 *   test    : audit settings + tables + dry-run per filter (default)
 *   cars    : what cars the crosspost cron queued for 999 in the last 2h + why
 *   cleanup : delete the 999 test schedules (add &apply=1 to actually delete)
 *
 *   https://www.sauto.md/console/crosspost_tool.php?token=cron2026&do=test
 */
if (php_sapi_name()!=='cli' && (($_GET['token']??'')!=='cron2026')) { http_response_code(403); die('Forbidden'); }
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
header('Content-Type: text/plain; charset=utf-8');
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
        'mercedes_benz' => [
            'a_class' => ['a', 'a_amg'],
            'b_class' => ['b'],
            'c_class' => ['c', 'c_amg', 'c_break'],
            'e_class' => ['e', 'e_break'],
            'g_class' => ['g_amg'],
            's_class' => ['s_long'],
            'v_class' => ['v', 'v_l3'],
        ],
        // Audi: Avant/Allroad are wagons of the same series; e-tron variants of a
        // combustion model fold in ONLY where the base model exists as the same car.
        // (Standalone "e-tron"/"e-tron GT" stay — they ARE distinct EV models.)
        'audi' => [
            'a4' => ['a4_avant', 'a4_allroad'],
            'a6' => ['a6_avant', 'a6_allroad'],
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
        'volkswagen' => [
            'passat' => ['passat_cc'],
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

echo "Unknown mode. Use ?do=test|dupes|pdupes|mergemodels|mergeparsing|...|sim999|whyskip|api999models\n";
