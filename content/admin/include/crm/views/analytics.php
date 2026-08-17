<?php defined('_DOIT') or die('Restricted access');

require_once(_ADM_INCL.'/crm/crm_lang.php');

$lang_url       = $_COOKIE['lang'] ?? 'ro';
$admin_dir_name = 'adminsauto';

// Selected month/year from GET params, clamped to valid range
$sel_year  = (int)($_GET['y']  ?? date('Y'));
$sel_month = (int)($_GET['m']  ?? date('n'));
$user_f    = (int)($_GET['uid'] ?? 0);
$all_year  = !empty($_GET['all_year']);

$sel_year  = max(2020, min((int)date('Y'), $sel_year));
$sel_month = max(1,    min(12,             $sel_month));

if ($all_year) {
    $date_from = sprintf('%04d-01-01', $sel_year);
    $date_to   = sprintf('%04d-12-31', $sel_year);
    // Previous period = previous year
    $prev_from = sprintf('%04d-01-01', $sel_year - 1);
    $prev_to   = sprintf('%04d-12-31', $sel_year - 1);
} else {
    $date_from = sprintf('%04d-%02d-01', $sel_year, $sel_month);
    $date_to   = date('Y-m-t', strtotime($date_from));
    // Previous month date range used for % diff badges
    $prev_ts   = strtotime($date_from . ' -1 month');
    $prev_from = date('Y-m-01', $prev_ts);
    $prev_to   = date('Y-m-t',  $prev_ts);
}

$analytics_url = "/$lang_url/$admin_dir_name/crm/analytics";

// Returns % change between current and previous value, null if prev is 0
function ana_pct_diff(int $cur, int $prev): ?float {
    if ($prev === 0) return null;
    return round(($cur - $prev) / $prev * 100, 1);
}

// Builds WHERE clause + params for crm_leads filtered by created_at period
function ana_where(string $prefx, string $df, string $dt, int $uid, string $alias = 'l'): array {
    $w = "DATE({$alias}.created_at) BETWEEN :df AND :dt AND {$alias}.status != 'junk'";
    $p = [':df' => $df, ':dt' => $dt];
    if ($uid) { $w .= " AND {$alias}.owner_id = :uid"; $p[':uid'] = $uid; }
    return [$w, $p];
}

[$bw,  $bp]  = ana_where($prefx, $date_from, $date_to,  $user_f);
[$bwp, $bpp] = ana_where($prefx, $prev_from, $prev_to,  $user_f);

// Counts closed deals from docs_ctlg (tx_status='closed', crm_tracked=1).
// This matches the same source as /crm/closed, so numbers stay in sync.
function ana_count_closed(PDO $db, string $prefx, string $df, string $dt, int $uid): int {
    $w = "d.tx_status = 'closed' AND d.crm_tracked = 1 AND (d.archived IS NULL OR d.archived = 0) AND d.date BETWEEN :df AND :dt";
    $p = [':df' => $df, ':dt' => $dt];
    if ($uid) {
        $w .= " AND d.adm = :uid";
        $p[':uid'] = $uid;
    }
    $r = $db->prepare("SELECT COUNT(DISTINCT d.id) FROM {$prefx}_docs_ctlg d WHERE $w");
    $r->execute($p);
    return (int)$r->fetchColumn();
}

// KPI: total new leads created in selected period
$r = $db->prepare("SELECT COUNT(*) FROM {$prefx}_crm_leads l WHERE $bw");  $r->execute($bp);
$kpi_leads = (int)$r->fetchColumn();
$r = $db->prepare("SELECT COUNT(*) FROM {$prefx}_crm_leads l WHERE $bwp"); $r->execute($bpp);
$kpi_leads_prev = (int)$r->fetchColumn();

// KPI: closed deals counted from documents (same logic as /crm/closed)
$kpi_closed      = ana_count_closed($db, $prefx, $date_from, $date_to,  $user_f);
$kpi_closed_prev = ana_count_closed($db, $prefx, $prev_from, $prev_to,  $user_f);

// KPI: leads currently in transaction or closed status
$r = $db->prepare("SELECT COUNT(*) FROM {$prefx}_crm_leads l WHERE $bw AND l.status IN ('transaction','closed')"); $r->execute($bp);
$kpi_tx = (int)$r->fetchColumn();
$r = $db->prepare("SELECT COUNT(*) FROM {$prefx}_crm_leads l WHERE $bwp AND l.status IN ('transaction','closed')"); $r->execute($bpp);
$kpi_tx_prev = (int)$r->fetchColumn();

// KPI: leads still in progress (active, missed, unprocessed, processed)
$r = $db->prepare("SELECT COUNT(*) FROM {$prefx}_crm_leads l WHERE $bw AND l.status IN ('active','missed','unprocessed','processed')"); $r->execute($bp);
$kpi_active = (int)$r->fetchColumn();
$r = $db->prepare("SELECT COUNT(*) FROM {$prefx}_crm_leads l WHERE $bwp AND l.status IN ('active','missed','unprocessed','processed')"); $r->execute($bpp);
$kpi_active_prev = (int)$r->fetchColumn();

// KPI: conversion rate = closed docs / total leads
$conv      = $kpi_leads > 0 ? round($kpi_closed      / $kpi_leads      * 100, 1) : 0;
$conv_prev = $kpi_leads_prev > 0 ? round($kpi_closed_prev / $kpi_leads_prev * 100, 1) : 0;

// Funnel breakdown: all from leads table, filtered by created_at period
$r = $db->prepare("SELECT
    SUM(l.status IN ('active','missed','unprocessed','processed')) AS new_cnt,
    SUM(l.status = 'transaction')                                  AS tx_cnt,
    SUM(l.status = 'closed')                                       AS cl_cnt
    FROM {$prefx}_crm_leads l WHERE $bw");
$r->execute($bp); $funnel = $r->fetchObject();
$funnel_new_cnt   = (int)($funnel->new_cnt ?? 0);
$funnel_tx_cnt    = (int)($funnel->tx_cnt  ?? 0);
$funnel_cl_cnt    = (int)($funnel->cl_cnt  ?? 0);
$funnel_total_raw = $funnel_new_cnt + $funnel_tx_cnt + $funnel_cl_cnt;
$funnel_total     = max($funnel_total_raw, 1);

// Tranzactii din docs_ctlg filtrate dupa luna selectata
$r2 = $db->prepare("SELECT
    SUM((d.tx_status IS NULL OR d.tx_status = 'transaction') AND d.f IN ('con_plata','con_arvon','con_arvon_com','cesionar','act_compensare','vinzare_avans') AND d.lead_id IS NOT NULL) AS tx_crm,
    SUM((d.tx_status IS NULL OR d.tx_status = 'transaction') AND d.f IN ('con_plata','con_arvon','con_arvon_com','cesionar','act_compensare','vinzare_avans') AND d.lead_id IS NULL)     AS tx_direct,
    SUM(d.tx_status = 'closed' AND d.f = 'vinzare_avans' AND d.lead_id IS NOT NULL)                                                                                                     AS cl_crm,
    SUM(d.tx_status = 'closed' AND d.f = 'vinzare_avans' AND d.lead_id IS NULL)                                                                                                         AS cl_direct
    FROM {$prefx}_docs_ctlg d
    WHERE d.crm_tracked = 1 AND (d.archived IS NULL OR d.archived = 0)
      AND DATE(d.date) BETWEEN :df AND :dt");
$r2->execute([':df' => $date_from, ':dt' => $date_to]);
$funnel2 = $r2->fetchObject();
$funnel_tx_crm    = (int)($funnel2->tx_crm    ?? 0);
$funnel_tx_direct = (int)($funnel2->tx_direct ?? 0);
$funnel_cl_crm    = (int)($funnel2->cl_crm    ?? 0);
$funnel_cl_direct = (int)($funnel2->cl_direct ?? 0);
$funnel_tx_total  = $funnel_tx_crm + $funnel_tx_direct;
$funnel_cl_total  = $funnel_cl_crm + $funnel_cl_direct;
$funnel_doc_total = max($funnel_tx_total + $funnel_cl_total, 1);

// Activity trend: leads created per month for the last 12 months
$trend_stmt = $db->prepare("
    SELECT DATE_FORMAT(l.created_at,'%Y-%m') AS ym,
           COUNT(*) AS leads_cnt
    FROM {$prefx}_crm_leads l
    WHERE l.created_at >= DATE_SUB(:d1, INTERVAL 11 MONTH)
      AND l.created_at < DATE_ADD(:d2, INTERVAL 1 MONTH)
      AND l.status != 'junk'
    GROUP BY ym ORDER BY ym ASC");
$trend_stmt->execute([':d1' => $date_from, ':d2' => $date_to]);
$trend_data = $trend_stmt->fetchAll(PDO::FETCH_OBJ);

// Closed deals per month from documents (for the trend line)
$trend_closed_stmt = $db->prepare("
    SELECT DATE_FORMAT(d.date,'%Y-%m') AS ym, COUNT(*) AS cnt
    FROM {$prefx}_docs_ctlg d
    WHERE d.tx_status = 'closed' AND d.crm_tracked = 1 AND (d.archived IS NULL OR d.archived = 0)
      AND d.date >= DATE_SUB(:d1, INTERVAL 11 MONTH)
      AND d.date < DATE_ADD(:d2, INTERVAL 1 MONTH)
    GROUP BY ym ORDER BY ym ASC");
$trend_closed_stmt->execute([':d1' => $date_from, ':d2' => $date_to]);
$trend_closed_map = [];
foreach ($trend_closed_stmt->fetchAll(PDO::FETCH_OBJ) as $tc) $trend_closed_map[$tc->ym] = (int)$tc->cnt;

// Fill all 12 months, inserting 0 for months with no data
$trend_map = [];
foreach ($trend_data as $t) $trend_map[$t->ym] = $t;
$trend_labels = [];
$trend_leads  = [];
$trend_tx     = [];
for ($i = 11; $i >= 0; $i--) {
    $ym = date('Y-m', strtotime($date_from . " -$i months"));
    $trend_labels[] = $ym;
    $trend_leads[]  = (int)($trend_map[$ym]->leads_cnt ?? 0);
    $trend_tx[]     = $trend_closed_map[$ym] ?? 0;
}

// Closed deals by month for the selected year (bar chart)
$sales_stmt = $db->prepare("
    SELECT MONTH(d.date) AS mo, COUNT(*) AS cnt
    FROM {$prefx}_docs_ctlg d
    WHERE d.tx_status = 'closed' AND d.crm_tracked = 1 AND (d.archived IS NULL OR d.archived = 0)
      AND YEAR(d.date) = :yr
    GROUP BY mo ORDER BY mo ASC");
$sales_stmt->execute([':yr' => $sel_year]);
$sales_raw = $sales_stmt->fetchAll(PDO::FETCH_OBJ);
$sales_by_month = array_fill(1, 12, 0);
foreach ($sales_raw as $s) $sales_by_month[(int)$s->mo] = (int)$s->cnt;

// Top 5 managers ranked by closed documents in selected period (split CRM vs Direct)
$top_uid_cond = $user_f ? " AND d.adm = :uid" : "";
$top_params   = [':df' => $date_from, ':dt' => $date_to];
if ($user_f) $top_params[':uid'] = $user_f;
$top_stmt = $db->prepare("
    SELECT u.name,
           COUNT(d.id) AS closed,
           SUM(d.lead_id IS NOT NULL) AS closed_crm,
           SUM(d.lead_id IS NULL) AS closed_direct
    FROM {$prefx}_docs_ctlg d
    JOIN {$prefx}_adm_usr u ON u.id = d.adm
    WHERE d.tx_status = 'closed' AND d.crm_tracked = 1 AND (d.archived IS NULL OR d.archived = 0)
      AND DATE(d.date) BETWEEN :df AND :dt
      $top_uid_cond
    GROUP BY d.adm ORDER BY closed DESC LIMIT 8");
$top_stmt->execute($top_params);
$top_mgr = $top_stmt->fetchAll(PDO::FETCH_OBJ);

// Lead sources: classic sources + inbox sources grouped by channel/page_id
$bw_src2 = str_replace([':df',':dt',':uid'], [':df2',':dt2',':uid2'], $bw);
$bp_src2 = [':df2' => $date_from, ':dt2' => $date_to, ':src_unknown' => ($cL['source_unknown'] ?? 'Apel direct')];
if ($user_f) $bp_src2[':uid2'] = $user_f;
$src_stmt = $db->prepare("
    SELECT name, color, SUM(total) AS total, SUM(closed) AS closed FROM (
        SELECT COALESCE(s.name, :src_unknown) AS name,
               COALESCE(s.color,'#9ca3af') AS color,
               COUNT(l.id) AS total,
               SUM(l.status IN ('transaction','closed')) AS closed
        FROM {$prefx}_crm_leads l
        LEFT JOIN {$prefx}_crm_sources s ON s.id = l.source_id
        WHERE $bw AND l.origin != 'inbox'
        GROUP BY l.source_id
        UNION ALL
        SELECT CONCAT('Mesaj · ',
                   CASE ins.channel WHEN '999md' THEN '999' WHEN 'facebook' THEN 'Facebook' WHEN 'instagram' THEN 'Instagram' WHEN 'telegram' THEN 'Telegram' WHEN 'viber' THEN 'Viber' WHEN 'site' THEN 'Site' ELSE ins.channel END,
                   ' · ',
                   CASE ins.page_id
                       WHEN 'order_999md'      THEN 'Comanda'
                       WHEN 'korea_999md'      THEN 'Encars'
                       WHEN 'usa_999md'        THEN 'SautoSUA'
                       WHEN 'sautohaus_999md'  THEN 'SAUTO-HAUS'
                       WHEN 'regular_999md'    THEN 'Comerciale'
                       WHEN 'order_telegram'   THEN 'Comanda'
                       WHEN 'regular_telegram' THEN 'Stock'
                       WHEN '725963964220309'  THEN 'SAUTO'
                       WHEN '482777831588669'  THEN 'Piața Pruncu'
                       WHEN 'ordercars'        THEN 'Order Cars'
                       WHEN 'cars'             THEN 'Stock'
                       WHEN 'order'            THEN 'Order'
                       WHEN 'credit'           THEN 'Credit'
                       WHEN 'tradein'          THEN 'Trade-in'
                       WHEN 'sale'             THEN 'Sale'
                       ELSE ins.page_id END
               ) AS name,
               '#6366f1' AS color,
               COUNT(l.id) AS total,
               SUM(l.status IN ('transaction','closed')) AS closed
        FROM {$prefx}_crm_leads l
        JOIN {$prefx}_crm_inbox_sessions ins ON ins.id = (SELECT MIN(id) FROM {$prefx}_crm_inbox_sessions WHERE lead_id = l.id)
        WHERE $bw_src2 AND l.origin = 'inbox'
        GROUP BY ins.channel, ins.page_id
    ) _src
    GROUP BY name, color ORDER BY total DESC LIMIT 12");
$src_stmt->execute(array_merge($bp, $bp_src2));
$src_data = $src_stmt->fetchAll(PDO::FETCH_OBJ);
$src_max  = !empty($src_data) ? max(array_map(fn($x) => (int)$x->total, $src_data)) : 1;

// Active admin users for the manager filter dropdown
$users_list = $db->query("SELECT id, name FROM {$prefx}_adm_usr WHERE act=1 ORDER BY name ASC")->fetchAll(PDO::FETCH_OBJ);

// Month navigation labels and prev/next links
$month_names_full = ['Ianuarie','Februarie','Martie','Aprilie','Mai','Iunie','Iulie','August','Septembrie','Octombrie','Noiembrie','Decembrie'];
$sel_month_label  = $all_year ? (string)$sel_year : (($month_names_full[$sel_month - 1] ?? '') . ' ' . $sel_year);

$prev_nav_m = $sel_month === 1  ? 12 : $sel_month - 1;
$prev_nav_y = $sel_month === 1  ? $sel_year - 1 : $sel_year;
$next_nav_m = $sel_month === 12 ? 1  : $sel_month + 1;
$next_nav_y = $sel_month === 12 ? $sel_year + 1 : $sel_year;
$is_future  = ($next_nav_y > (int)date('Y')) || ($next_nav_y == (int)date('Y') && $next_nav_m > (int)date('n'));

// Builds the analytics page URL with y/m/uid params
function ana_nav_url(string $base, int $y, int $m, int $uid, bool $all_year = false): string {
    $url = $base . '?y=' . $y . '&m=' . $m . ($uid ? '&uid=' . $uid : '');
    if ($all_year) $url .= '&all_year=1';
    return $url;
}

// Renders a green/red % diff badge (used next to KPI values)
function ana_diff_badge($diff): string {
    if ($diff === null) return '';
    $color = $diff >= 0 ? '#22c55e' : '#ef4444';
    $arrow = $diff >= 0 ? '↑' : '↓';
    return '<span style="display:inline-flex;align-items:center;gap:2px;font-size:0.72rem;font-weight:700;color:' . $color . ';background:' . $color . '1a;border-radius:20px;padding:1px 7px;margin-left:6px;">'
        . $arrow . ' ' . abs($diff) . '%</span>';
}
?>

<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>

<style>
#ana-wrap { padding: 0 0 2rem; font-family: inherit; }
.ana-topbar { display:flex; align-items:center; justify-content:space-between; margin-bottom:1.5rem; flex-wrap:wrap; gap:0.75rem; }
.ana-title { font-size:1.35rem; font-weight:700; color:#111; }
.ana-updated { font-size:0.78rem; color:#aaa; display:flex; align-items:center; gap:0.4rem; }

/* Month nav */
.ana-month-nav { display:flex; align-items:center; gap:0.5rem; }
.ana-month-nav a, .ana-month-nav span { display:inline-flex; align-items:center; justify-content:center; width:28px; height:28px; border-radius:6px; border:1px solid #e5e7eb; background:#fff; color:#333; font-size:0.85rem; text-decoration:none; cursor:pointer; }
.ana-month-nav a:hover { background:#f3f4f6; }
.ana-month-nav .ana-month-label { width:auto; padding:0 0.75rem; font-size:0.82rem; font-weight:600; color:#333; white-space:nowrap; }

/* KPI cards */
.ana-kpi-grid { display:grid; grid-template-columns:repeat(4,1fr); gap:1rem; margin-bottom:1.25rem; }
.ana-kpi-card { background:#fff; border:1px solid #f0f0f0; border-radius:12px; padding:1.1rem 1.25rem; }
.ana-kpi-label { font-size:0.78rem; color:#999; font-weight:500; margin-bottom:0.4rem; }
.ana-kpi-val { font-size:1.9rem; font-weight:700; color:#111; line-height:1.1; display:flex; align-items:center; flex-wrap:wrap; gap:0.25rem; }
.ana-kpi-sub { font-size:0.72rem; color:#bbb; margin-top:0.3rem; }

/* Row layouts */
.ana-row-3 { display:grid; grid-template-columns:1fr 2fr 1fr; gap:1rem; margin-bottom:1rem; }
.ana-row-2 { display:grid; grid-template-columns:1fr 1fr; gap:1rem; margin-bottom:1rem; }
.ana-card { background:#fff; border:1px solid #f0f0f0; border-radius:12px; padding:1.1rem 0.75rem; }
.ana-card-title { font-size:0.82rem; font-weight:700; color:#111; margin-bottom:1rem; display:flex; align-items:center; justify-content:space-between; }

/* Funnel */
.ana-funnel { display:flex; flex-direction:column; gap:0.6rem; margin-top:0.5rem; }
.ana-funnel-item { display:flex; align-items:center; gap:0.6rem; font-size:0.8rem; flex-wrap:wrap; }
.ana-funnel-sub { width:100%; padding-left:22px; display:flex; gap:0.6rem; font-size:0.72rem; margin-top:1px; }
.ana-funnel-dot { width:10px; height:10px; border-radius:50%; flex-shrink:0; }
.ana-funnel-name { color:#555; min-width:110px; }
.ana-funnel-count { color:#111; font-weight:600; min-width:40px; }
.ana-funnel-pct { color:#aaa; font-size:0.72rem; }

/* Top managers */
.ana-mgr-list { display:flex; flex-direction:column; gap:0.55rem; }
.ana-mgr-row { display:flex; align-items:center; gap:0.25rem; font-size:0.82rem; }
.ana-mgr-rank { width:10px; color:#bbb; font-weight:700; font-size:0.75rem; flex-shrink:0; }
.ana-mgr-name { flex:1; color:#111; font-weight:500; overflow:hidden; text-overflow:ellipsis; white-space:nowrap; }
.ana-mgr-sales { font-weight:700; color:#E61E2D; font-size:0.82rem; white-space:nowrap; }
.ana-mgr-total { font-size:0.72rem; color:#aaa; white-space:nowrap; }
.ana-mgr-row:first-child .ana-mgr-name { color:#E61E2D; font-weight:700; }
.ana-mgr-row:first-child .ana-mgr-rank { color:#E61E2D; }

/* Sources table */
.ana-src-table { width:100%; border-collapse:collapse; font-size:0.8rem; }
.ana-src-table th { color:#aaa; font-weight:500; text-align:left; padding:0.3rem 0.5rem; font-size:0.72rem; border-bottom:1px solid #f0f0f0; }
.ana-src-table td { padding:0.45rem 0.5rem; border-bottom:1px solid #f8f8f8; vertical-align:middle; }
.ana-src-bar-wrap { background:#f0f0f0; border-radius:4px; height:6px; min-width:60px; }
.ana-src-bar { background:#111; border-radius:4px; height:6px; }

/* Filter bar */
.ana-filter-bar { display:flex; align-items:center; gap:0.5rem; margin-bottom:1.25rem; flex-wrap:wrap; }
.ana-filter-bar select { padding:0.35rem 0.6rem; border:1px solid #e5e7eb; border-radius:8px; font-size:0.8rem; background:#fff; color:#333; }
.ana-filter-bar a { font-size:0.78rem; color:#888; text-decoration:none; padding:0.35rem 0.75rem; border:1px solid #e5e7eb; border-radius:8px; background:#fff; }
.ana-filter-bar a:hover { background:#f3f4f6; }

/* Per-section month selector */
.ana-sec-nav { display:inline-flex; align-items:center; gap:3px; }
.ana-sec-nav button { background:none; border:1px solid #e5e7eb; border-radius:5px; width:22px; height:22px; cursor:pointer; font-size:0.8rem; color:#555; display:inline-flex; align-items:center; justify-content:center; padding:0; }
.ana-sec-nav button:hover { background:#f3f4f6; }
.ana-sec-nav button:disabled { opacity:0.3; cursor:default; }
.ana-sec-label { font-size:0.72rem; font-weight:600; color:#555; white-space:nowrap; padding:0 4px; }

@media(max-width:900px){
    .ana-kpi-grid { grid-template-columns:repeat(2,1fr); }
    .ana-row-3, .ana-row-2 { grid-template-columns:1fr; }
}
</style>

<div id="ana-wrap">

    <!-- Top bar -->
    <div class="ana-topbar">
        <div class="ana-title"><?= $cL['analytics_title'] ?? 'Statistică' ?></div>
        <div style="display:flex;align-items:center;gap:0.75rem;flex-wrap:wrap;">
            <div class="ana-updated"><?= $cL['analytics_updated'] ?? 'Actualizat' ?> <?= date('H:i') ?></div>
            <a href="/content/admin/action/crm_export.php?y=<?= $sel_year ?>&m=<?= $sel_month ?>&uid=<?= $user_f ?>" target="_blank" class="crm-btn crm-btn-outline crm-btn-sm"><?= $cL['export_csv'] ?? 'Export CSV' ?></a>
        </div>
    </div>

    <!-- Filter bar -->
    <div class="ana-filter-bar">
        <!-- Month nav — hidden in all_year mode -->
        <?php if (!$all_year): ?>
        <div class="ana-month-nav">
            <a href="<?= ana_nav_url($analytics_url, $prev_nav_y, $prev_nav_m, $user_f) ?>">‹</a>
            <span class="ana-month-label"><?= $sel_month_label ?></span>
            <?php if (!$is_future): ?>
            <a href="<?= ana_nav_url($analytics_url, $next_nav_y, $next_nav_m, $user_f) ?>">›</a>
            <?php else: ?>
            <span style="opacity:0.3;cursor:default;">›</span>
            <?php endif; ?>
        </div>
        <?php endif; ?>
        <!-- Year selector -->
        <form method="GET" action="<?= $analytics_url ?>" style="display:contents;">
            <?php if (!$all_year): ?><input type="hidden" name="m" value="<?= $sel_month ?>"><?php endif; ?>
            <?php if ($all_year): ?><input type="hidden" name="all_year" value="1"><?php endif; ?>
            <select name="y" onchange="this.form.submit()" style="padding:0.35rem 0.6rem;border:1px solid #e5e7eb;border-radius:8px;font-size:0.8rem;">
                <?php for ($y = (int)date('Y'); $y >= 2023; $y--): ?>
                <option value="<?= $y ?>" <?= $y===$sel_year?'selected':'' ?>><?= $y ?></option>
                <?php endfor; ?>
            </select>
            <!-- Manager filter -->
            <select name="uid" onchange="this.form.submit()">
                <option value="0"><?= $cL['all_managers'] ?? 'Toți managerii' ?></option>
                <?php foreach ($users_list as $ul): ?>
                <option value="<?= $ul->id ?>" <?= $user_f===(int)$ul->id?'selected':'' ?>><?= htmlspecialchars($ul->name) ?></option>
                <?php endforeach; ?>
            </select>
        </form>
        <!-- Tot anul toggle -->
        <a href="<?= $all_year ? ana_nav_url($analytics_url, $sel_year, $sel_month, $user_f) : $analytics_url.'?y='.$sel_year.'&all_year=1'.($user_f?'&uid='.$user_f:'') ?>"
           class="crm-btn <?= $all_year ? 'crm-btn-dark' : 'crm-btn-outline' ?> crm-btn-sm"
           style="text-decoration:none;">
            <?= $all_year ? ($cL['ana_back_month'] ?? 'Revino la lună') : ($cL['ana_all_year'] ?? 'Tot anul') ?>
        </a>
    </div>

    <!-- KPI cards -->
    <div class="ana-kpi-grid">
        <div class="ana-kpi-card">
            <div class="ana-kpi-label"><?= $cL['kpi_leads_total'] ?? 'Leaduri noi' ?></div>
            <div class="ana-kpi-val"><?= number_format($kpi_leads) ?><?= ana_diff_badge(ana_pct_diff($kpi_leads, $kpi_leads_prev)) ?></div>
            <div class="ana-kpi-sub"><?= $cL['vs_prev_month'] ?? 'vs. luna precedentă' ?></div>
        </div>
        <div class="ana-kpi-card">
            <div class="ana-kpi-label"><?= $cL['kpi_conversion'] ?? 'Conversie în tranzacții' ?></div>
            <div class="ana-kpi-val"><?= $conv ?>%<?= ana_diff_badge(ana_pct_diff((int)($conv*10), (int)($conv_prev*10))) ?></div>
            <div class="ana-kpi-sub"><?= $cL['vs_prev_month'] ?? 'vs. luna precedentă' ?></div>
        </div>
        <div class="ana-kpi-card">
            <div class="ana-kpi-label"><?= $cL['kpi_closed'] ?? 'Total tranzacții încheiate' ?></div>
            <div class="ana-kpi-val"><?= number_format($kpi_closed) ?> <span style="font-size:0.9rem;font-weight:500;color:#aaa;"><?= $cL['kpi_closed_label'] ?? 'încheiate' ?></span><?= ana_diff_badge(ana_pct_diff($kpi_closed, $kpi_closed_prev)) ?></div>
            <div class="ana-kpi-sub"><?= $cL['vs_prev_month'] ?? 'vs. luna precedentă' ?></div>
        </div>
        <div class="ana-kpi-card">
            <div class="ana-kpi-label"><?= $cL['kpi_in_progress'] ?? 'În proces' ?></div>
            <div class="ana-kpi-val"><?= number_format($kpi_active) ?><?= ana_diff_badge(ana_pct_diff($kpi_active, $kpi_active_prev)) ?></div>
            <div class="ana-kpi-sub"><?= $cL['vs_prev_month'] ?? 'vs. luna precedentă' ?></div>
        </div>
    </div>

    <!-- Row: Funnel + Activity + Top managers -->
    <div class="ana-row-3">

        <!-- Funnel -->
        <div class="ana-card" id="sec-funnel">
            <div class="ana-card-title">
                <?= $cL['ana_funnel'] ?? 'Pâlnie vânzări' ?>
                <div class="ana-sec-nav" data-section="funnel">
                    <button onclick="anaSecPrev('funnel')">‹</button>
                    <span class="ana-sec-label"><?= $sel_month_label ?></span>
                    <button onclick="anaSecNext('funnel')" <?= $is_future ? 'disabled' : '' ?>>›</button>
                </div>
            </div>
            <div style="position:relative;width:140px;margin:0 auto 1rem;">
                <canvas id="chartFunnel" width="140" height="140"></canvas>
                <div style="position:absolute;top:50%;left:50%;transform:translate(-50%,-50%);text-align:center;pointer-events:none;">
                    <div style="font-size:1.4rem;font-weight:700;color:#111;line-height:1;"><?= $funnel_total ?></div>
                    <div style="font-size:0.68rem;color:#aaa;"><?= $cL['ana_leads'] ?? 'Leaduri' ?></div>
                </div>
            </div>

            <!-- Sectiunea Leaduri CRM -->
            <div style="font-size:0.7rem;font-weight:700;color:#aaa;text-transform:uppercase;letter-spacing:0.05em;margin-bottom:0.4rem;"><?= $cL['ana_funnel_crm'] ?? 'Leaduri CRM' ?></div>
            <div class="ana-funnel" style="margin-bottom:1rem;">
                <div class="ana-funnel-item">
                    <span class="ana-funnel-dot" style="background:#111;"></span>
                    <span class="ana-funnel-name"><?= $cL['ana_funnel_new'] ?? 'Noi / Active' ?></span>
                    <span class="ana-funnel-count"><?= $funnel_new_cnt ?></span>
                    <span class="ana-funnel-pct">(<?= $funnel_total > 0 ? round($funnel_new_cnt/$funnel_total*100) : 0 ?>%)</span>
                </div>
                <div class="ana-funnel-item">
                    <span class="ana-funnel-dot" style="background:#9ca3af;"></span>
                    <span class="ana-funnel-name"><?= $cL['ana_funnel_tx'] ?? 'În tranzacție' ?></span>
                    <span class="ana-funnel-count"><?= $funnel_tx_cnt ?></span>
                    <span class="ana-funnel-pct">(<?= $funnel_total > 0 ? round($funnel_tx_cnt/$funnel_total*100) : 0 ?>%)</span>
                </div>
                <div class="ana-funnel-item">
                    <span class="ana-funnel-dot" style="background:#E61E2D;"></span>
                    <span class="ana-funnel-name"><?= $cL['ana_funnel_fin'] ?? 'Finalizate' ?></span>
                    <span class="ana-funnel-count"><?= $funnel_cl_cnt ?></span>
                    <span class="ana-funnel-pct">(<?= $funnel_total > 0 ? round($funnel_cl_cnt/$funnel_total*100) : 0 ?>%)</span>
                </div>
            </div>

            <!-- Sectiunea Documente Directe -->
            <div style="font-size:0.7rem;font-weight:700;color:#aaa;text-transform:uppercase;letter-spacing:0.05em;margin-bottom:0.4rem;"><?= $cL['ana_funnel_docs'] ?? 'Documente directe' ?></div>
            <div class="ana-funnel">
                <div class="ana-funnel-item">
                    <span class="ana-funnel-dot" style="background:#9ca3af;"></span>
                    <span class="ana-funnel-name"><?= $cL['ana_funnel_tx'] ?? 'În tranzacție' ?></span>
                    <span class="ana-funnel-count"><?= $funnel_tx_direct ?></span>
                </div>
                <div class="ana-funnel-item">
                    <span class="ana-funnel-dot" style="background:#E61E2D;"></span>
                    <span class="ana-funnel-name"><?= $cL['ana_funnel_fin'] ?? 'Finalizate' ?></span>
                    <span class="ana-funnel-count"><?= $funnel_cl_direct ?></span>
                </div>
            </div>

            <!-- Sectiunea Total -->
            <div style="font-size:0.7rem;font-weight:700;color:#aaa;text-transform:uppercase;letter-spacing:0.05em;margin-bottom:0.4rem;margin-top:1rem;"><?= $cL['ana_funnel_total'] ?? 'Total' ?></div>
            <div class="ana-funnel">
                <div class="ana-funnel-item">
                    <span class="ana-funnel-dot" style="background:#9ca3af;"></span>
                    <span class="ana-funnel-name"><?= $cL['ana_funnel_tx'] ?? 'În tranzacție' ?></span>
                    <span class="ana-funnel-count" style="font-weight:700;"><?= $funnel_tx_total ?></span>
                </div>
                <div class="ana-funnel-item">
                    <span class="ana-funnel-dot" style="background:#E61E2D;"></span>
                    <span class="ana-funnel-name"><?= $cL['ana_funnel_fin'] ?? 'Finalizate' ?></span>
                    <span class="ana-funnel-count" style="font-weight:700;"><?= $funnel_cl_total ?></span>
                </div>
            </div>
        </div>

        <!-- Activity chart -->
        <div class="ana-card" id="sec-activity" style="display:flex;flex-direction:column;">
            <div class="ana-card-title">
                <?= $cL['ana_activity'] ?? 'Dinamica activității' ?>
                <div class="ana-sec-nav" data-section="activity">
                    <button onclick="anaSecPrev('activity')">‹</button>
                    <span class="ana-sec-label"><?= $sel_month_label ?></span>
                    <button onclick="anaSecNext('activity')" <?= $is_future ? 'disabled' : '' ?>>›</button>
                </div>
            </div>
            <div style="flex:1;min-height:180px;position:relative;">
                <canvas id="chartActivity" style="position:absolute;top:0;left:0;width:100%;height:100%;"></canvas>
            </div>
        </div>

        <!-- Top managers -->
        <div class="ana-card" id="sec-managers">
            <div class="ana-card-title">
                <?= $cL['ana_top_managers'] ?? 'Top Manageri' ?>
                <div class="ana-sec-nav" data-section="managers">
                    <button onclick="anaSecPrev('managers')">‹</button>
                    <span class="ana-sec-label"><?= $sel_month_label ?></span>
                    <button onclick="anaSecNext('managers')" <?= $is_future ? 'disabled' : '' ?>>›</button>
                </div>
            </div>
            <?php if (!empty($top_mgr)): ?>
            <div class="ana-mgr-list">
                <?php foreach ($top_mgr as $i => $mgr): ?>
                <div class="ana-mgr-row">
                    <span class="ana-mgr-rank"><?= $i+1 ?>.</span>
                    <span class="ana-mgr-name"><?= htmlspecialchars($mgr->name) ?></span>
                    <span class="ana-mgr-sales" style="display:flex;flex-direction:column;align-items:flex-end;gap:2px;">
                        <?php if ((int)$mgr->closed_crm > 0): ?>
                        <span style="color:#E61E2D;font-size:0.75rem;">CRM — <?= (int)$mgr->closed_crm ?> <?= $cL['ana_sales'] ?? 'vânzări' ?></span>
                        <?php endif; ?>
                        <?php if ((int)$mgr->closed_direct > 0): ?>
                        <span style="color:#6b7280;font-size:0.75rem;">Direct — <?= (int)$mgr->closed_direct ?> <?= $cL['ana_sales'] ?? 'vânzări' ?></span>
                        <?php endif; ?>
                    </span>
                </div>
                <?php endforeach; ?>
            </div>
            <?php if (!empty($top_mgr)): $total_sales = array_sum(array_map(fn($x)=>(int)$x->closed, $top_mgr)); ?>
            <div style="margin-top:0.75rem;padding-top:0.6rem;border-top:1px solid #f0f0f0;font-size:0.78rem;color:#555;display:flex;justify-content:space-between;">
                <span><?= $cL['ana_total_sales'] ?? 'Total vânzări' ?></span>
                <span style="font-weight:700;"><?= $total_sales ?> <?= $cL['ana_sales'] ?? 'vânzări' ?></span>
            </div>
            <?php endif; ?>
            <?php else: ?>
            <div style="color:#bbb;font-size:0.82rem;text-align:center;padding:1rem 0;">—</div>
            <?php endif; ?>
        </div>
    </div>

    <!-- Row: Sales chart + Sources table -->
    <div class="ana-row-2">

        <!-- Sales by month -->
        <div class="ana-card" id="sec-sales" style="display:flex;flex-direction:column;">
            <div class="ana-card-title">
                <?= $cL['ana_sales_chart'] ?? 'Tranzacții încheiate' ?>
                <div class="ana-sec-nav" data-section="sales" data-mode="year">
                    <button onclick="anaSecPrev('sales')">‹</button>
                    <span class="ana-sec-label"><?= $sel_year ?></span>
                    <button onclick="anaSecNext('sales')" <?= $sel_year >= (int)date('Y') ? 'disabled' : '' ?>>›</button>
                </div>
            </div>
            <div style="flex:1;min-height:180px;position:relative;">
                <canvas id="chartSales" style="position:absolute;inset:0;width:100%!important;height:100%!important;"></canvas>
            </div>
        </div>

        <!-- Sources table -->
        <div class="ana-card" id="sec-sources">
            <div class="ana-card-title">
                <?= $cL['ana_sources'] ?? 'Leaduri pe surse' ?>
                <div class="ana-sec-nav" data-section="sources">
                    <button onclick="anaSecPrev('sources')">‹</button>
                    <span class="ana-sec-label"><?= $sel_month_label ?></span>
                    <button onclick="anaSecNext('sources')" <?= $is_future ? 'disabled' : '' ?>>›</button>
                </div>
            </div>
            <?php if (!empty($src_data)): ?>
            <table class="ana-src-table">
                <thead>
                    <tr>
                        <th><?= $cL['ana_src_source'] ?? 'Sursă' ?></th>
                        <th><?= $cL['ana_src_leads'] ?? 'Leaduri' ?></th>
                        <th><?= $cL['ana_src_conv'] ?? 'Conversie' ?></th>
                        <th style="min-width:80px;"></th>
                    </tr>
                </thead>
                <tbody>
                <?php foreach ($src_data as $src):
                    $src_conv = $src->total > 0 ? round($src->closed / $src->total * 100, 1) : 0;
                    $src_bar  = $src_max > 0 ? round($src->total / $src_max * 100) : 0;
                ?>
                <tr>
                    <td style="color:#111;font-weight:500;">
                        <?php if (strpos($src->name, 'Mesaj · ') === 0): ?>
                        <img src="/content/admin/include/crm/icons/sms-lead.svg" style="width:14px;height:14px;vertical-align:middle;margin-right:4px;opacity:0.7;" title="Mesaj inbox">
                        <?php else: ?>
                        <img src="/content/admin/include/crm/icons/phone-lead.svg" style="width:14px;height:14px;vertical-align:middle;margin-right:4px;opacity:0.7;" title="Apel / sursă">
                        <?php endif; ?>
                        <?= htmlspecialchars($src->name) ?>
                    </td>
                    <td style="font-weight:600;color:#111;"><?= (int)$src->total ?></td>
                    <td style="color:#555;"><?= $src_conv ?>%</td>
                    <td><div class="ana-src-bar-wrap"><div class="ana-src-bar" style="width:<?= $src_bar ?>%;background:<?= htmlspecialchars($src->color ?: '#111') ?>;"></div></div></td>
                </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
            <?php else: ?>
            <div style="color:#bbb;font-size:0.82rem;text-align:center;padding:1rem 0;">—</div>
            <?php endif; ?>
        </div>
    </div>

</div>

<script>
Chart.defaults.font.family = 'inherit';
Chart.defaults.color = '#888';

// Funnel donut
new Chart(document.getElementById('chartFunnel'), {
    type: 'doughnut',
    data: {
        datasets: [{
            data: [<?= (int)($funnel->new_cnt??0) ?>, <?= (int)($funnel->tx_cnt??0) ?>, <?= $funnel_cl_cnt ?>],
            backgroundColor: ['#111111','#d1d5db','#E61E2D'],
            borderWidth: 0,
            hoverOffset: 4
        }]
    },
    options: {
        cutout: '72%',
        plugins: { legend: { display: false }, tooltip: { enabled: true } },
        responsive: false
    }
});

// Activity line chart
new Chart(document.getElementById('chartActivity'), {
    type: 'line',
    data: {
        labels: <?= json_encode(array_map(fn($l) => substr($l, 5) . '/' . substr($l, 0, 4), $trend_labels)) ?>,
        datasets: [
            {
                label: '<?= addslashes($cL['ana_leads'] ?? 'Leaduri noi') ?>',
                data: <?= json_encode($trend_leads) ?>,
                borderColor: '#111',
                backgroundColor: 'rgba(17,17,17,0.07)',
                borderWidth: 2,
                pointRadius: 3,
                tension: 0.3,
                fill: true
            },
            {
                label: '<?= addslashes($cL['ana_funnel_closed'] ?? 'Tranzacții') ?>',
                data: <?= json_encode($trend_tx) ?>,
                borderColor: '#E61E2D',
                backgroundColor: 'rgba(230,30,45,0.07)',
                borderWidth: 2,
                pointRadius: 3,
                tension: 0.3,
                fill: true
            }
        ]
    },
    options: {
        responsive: true,
        maintainAspectRatio: false,
        plugins: { legend: { position: 'top', labels: { boxWidth: 12, font: { size: 11 } } } },
        scales: { y: { beginAtZero: true, ticks: { font: { size: 11 } } }, x: { ticks: { font: { size: 10 } } } }
    }
});

// Sales bar chart
const monthNames = <?= json_encode(array_values($month_names_full)) ?>;
const chartSalesInst = new Chart(document.getElementById('chartSales'), {
    type: 'bar',
    data: {
        labels: monthNames,
        datasets: [{
            data: <?= json_encode(array_values($sales_by_month)) ?>,
            backgroundColor: monthNames.map((_, i) => (i + 1) === <?= $sel_month ?> ? '#E61E2D' : '#e5e7eb'),
            borderRadius: 4,
            borderSkipped: false
        }]
    },
    options: {
        responsive: true,
        maintainAspectRatio: false,
        plugins: { legend: { display: false } },
        scales: { y: { beginAtZero: true, ticks: { font: { size: 11 } } }, x: { ticks: { font: { size: 10 } } } }
    }
});

// ── Per-section navigation ─────────────────────────────────────────────────────

const anaGlobalY = <?= $sel_year ?>;
const anaGlobalM = <?= $sel_month ?>;
const anaUid     = <?= $user_f ?>;
const anaLabelTx = <?= json_encode($cL['ana_funnel_closed'] ?? 'Tranzacții') ?>;
const anaLabelLd = <?= json_encode($cL['ana_leads'] ?? 'Leaduri noi') ?>;
const anaLabelSales = <?= json_encode($cL['ana_sales'] ?? 'vânzări') ?>;
const anaLabelTotal = <?= json_encode($cL['ana_total_sales'] ?? 'Total vânzări') ?>;
const anaLabelNoi   = <?= json_encode($cL['ana_funnel_new'] ?? 'Noi / Active') ?>;
const anaLabelTxPrc = <?= json_encode($cL['ana_funnel_process'] ?? 'În tranzacție') ?>;
const anaLabelCl    = <?= json_encode($cL['ana_funnel_closed'] ?? 'Tranzacție finalizată') ?>;
const todayY = <?= (int)date('Y') ?>;
const todayM = <?= (int)date('n') ?>;

// State: current y/m per section
const anaState = {
    funnel:   { y: anaGlobalY, m: anaGlobalM },
    activity: { y: anaGlobalY, m: anaGlobalM },
    managers: { y: anaGlobalY, m: anaGlobalM },
    sales:    { y: anaGlobalY, m: anaGlobalM },
    sources:  { y: anaGlobalY, m: anaGlobalM },
};

// Global nav uses page reload; per-section nav uses anaSecLoad()

function anaIsYearMode(sec) {
    const nav = document.querySelector(`.ana-sec-nav[data-section="${sec}"]`);
    return nav && nav.dataset.mode === 'year';
}
function anaSecPrev(sec) {
    const s = anaState[sec];
    if (anaIsYearMode(sec)) { s.y--; } else { if (s.m === 1) { s.m = 12; s.y--; } else { s.m--; } }
    anaSecLoad(sec);
}
function anaSecNext(sec) {
    const s = anaState[sec];
    if (anaIsYearMode(sec)) {
        if (s.y >= todayY) return;
        s.y++;
    } else {
        const nm = s.m === 12 ? 1 : s.m + 1;
        const ny = s.m === 12 ? s.y + 1 : s.y;
        if (ny > todayY || (ny === todayY && nm > todayM)) return;
        s.m = nm; s.y = ny;
    }
    anaSecLoad(sec);
}

function anaSecUpdateNav(sec) {
    const s = anaState[sec];
    const nav = document.querySelector(`.ana-sec-nav[data-section="${sec}"]`);
    if (!nav) return;
    const isSales = nav.dataset.mode === 'year';
    const label = nav.querySelector('.ana-sec-label');
    const btnNext = nav.querySelectorAll('button')[1];
    if (isSales) {
        label.textContent = s.y;
        const ny = s.m === 12 ? s.y + 1 : s.y;
        btnNext.disabled = s.y >= todayY;
    } else {
        const months = <?= json_encode($month_names_full) ?>;
        label.textContent = months[s.m - 1] + ' ' + s.y;
        const nm = s.m === 12 ? 1 : s.m + 1;
        const ny = s.m === 12 ? s.y + 1 : s.y;
        btnNext.disabled = (ny > todayY || (ny === todayY && nm > todayM));
    }
}

function anaSecLoad(sec) {
    anaSecUpdateNav(sec);
    const s = anaState[sec];
    const url = `/ajax.php?tp=adm&pg=crm&section=${sec}&y=${s.y}&m=${s.m}&uid=${anaUid}`;
    fetch(url).then(r => r.json()).then(d => {
        if (!d.ok) return;
        switch (sec) {
            case 'funnel':   anaRenderFunnel(d);   break;
            case 'activity': anaRenderActivity(d); break;
            case 'managers': anaRenderManagers(d); break;
            case 'sales':    anaRenderSales(d);    break;
            case 'sources':  anaRenderSources(d);  break;
        }
    });
}

function anaRenderFunnel(d) {
    // Update donut chart
    const c = Chart.getChart('chartFunnel');
    if (c) { c.data.datasets[0].data = [d.new, d.tx, d.cl]; c.update(); }
    // Update center text (first div = number, second = label)
    const wrap = document.querySelector('#sec-funnel [style*="position:absolute"]');
    if (wrap) wrap.querySelectorAll('div')[0].textContent = d.total;
    // Update list
    const items = document.querySelectorAll('#sec-funnel .ana-funnel-item');
    if (items[0]) { items[0].querySelector('.ana-funnel-count').textContent = d.new; items[0].querySelector('.ana-funnel-pct').textContent = `(${d.new_pct}%)`; }
    if (items[1]) { items[1].querySelector('.ana-funnel-count').textContent = d.tx;  items[1].querySelector('.ana-funnel-pct').textContent = `(${d.tx_pct}%)`; }
    if (items[2]) { items[2].querySelector('.ana-funnel-count').textContent = d.cl;  items[2].querySelector('.ana-funnel-pct').textContent = `(${d.cl_pct}%)`; }
}

function anaRenderActivity(d) {
    const c = Chart.getChart('chartActivity');
    if (!c) return;
    c.data.labels = d.labels;
    c.data.datasets[0].data = d.leads;
    c.data.datasets[1].data = d.closed;
    c.update();
}

function anaRenderManagers(d) {
    const card = document.querySelector('#sec-managers');
    if (!card) return;
    let html = '';
    if (d.rows && d.rows.length) {
        html += '<div class="ana-mgr-list">';
        d.rows.forEach((r, i) => {
            const crm    = parseInt(r.closed_crm)    || 0;
            const direct = parseInt(r.closed_direct) || 0;
            let salesHtml = '<span style="display:flex;flex-direction:column;align-items:flex-end;gap:2px;" class="ana-mgr-sales">';
            if (crm > 0)    salesHtml += `<span style="color:#E61E2D;font-size:0.75rem;">CRM — ${crm} ${anaLabelSales}</span>`;
            if (direct > 0) salesHtml += `<span style="color:#6b7280;font-size:0.75rem;">Direct — ${direct} ${anaLabelSales}</span>`;
            salesHtml += '</span>';
            html += `<div class="ana-mgr-row">
                <span class="ana-mgr-rank">${i+1}.</span>
                <span class="ana-mgr-name">${r.name}</span>
                ${salesHtml}
            </div>`;
        });
        html += '</div>';
        html += `<div style="margin-top:0.75rem;padding-top:0.6rem;border-top:1px solid #f0f0f0;font-size:0.78rem;color:#555;display:flex;justify-content:space-between;">
            <span>${anaLabelTotal}</span>
            <span style="font-weight:700;">${d.total} ${anaLabelSales}</span>
        </div>`;
    } else {
        html = '<div style="color:#bbb;font-size:0.82rem;text-align:center;padding:1rem 0;">—</div>';
    }
    // Replace content after the title div
    const title = card.querySelector('.ana-card-title');
    while (title.nextSibling) title.nextSibling.remove();
    card.insertAdjacentHTML('beforeend', html);
}

function anaRenderSales(d) {
    const c = Chart.getChart('chartSales');
    if (!c) return;
    c.data.datasets[0].data = d.data;
    c.data.datasets[0].backgroundColor = d.data.map((_, i) => (i + 1) === d.sel_month ? '#E61E2D' : '#e5e7eb');
    c.update();
}

function anaRenderSources(d) {
    const card = document.querySelector('#sec-sources');
    if (!card) return;
    let html = '';
    if (d.rows && d.rows.length) {
        html += `<table class="ana-src-table"><thead><tr>
            <th><?= htmlspecialchars($cL['ana_src_source'] ?? 'Sursă') ?></th>
            <th><?= htmlspecialchars($cL['ana_src_leads'] ?? 'Leaduri') ?></th>
            <th><?= htmlspecialchars($cL['ana_src_conv'] ?? 'Conversie') ?></th>
            <th style="min-width:80px;"></th>
        </tr></thead><tbody>`;
        d.rows.forEach(r => {
            const isMsg = r.name.startsWith('Mesaj · ');
            const icon  = isMsg
                ? '<img src="/content/admin/include/crm/icons/sms-lead.svg" style="width:14px;height:14px;vertical-align:middle;margin-right:4px;opacity:0.7;" title="Mesaj inbox">'
                : '<img src="/content/admin/include/crm/icons/phone-lead.svg" style="width:14px;height:14px;vertical-align:middle;margin-right:4px;opacity:0.7;" title="Apel / sursă">';
            html += `<tr>
                <td style="color:#111;font-weight:500;">${icon}${r.name}</td>
                <td style="font-weight:600;color:#111;">${r.total}</td>
                <td style="color:#555;">${r.conv}%</td>
                <td><div class="ana-src-bar-wrap"><div class="ana-src-bar" style="width:${r.bar}%;background:${r.color};"></div></div></td>
            </tr>`;
        });
        html += '</tbody></table>';
    } else {
        html = '<div style="color:#bbb;font-size:0.82rem;text-align:center;padding:1rem 0;">—</div>';
    }
    const title = card.querySelector('.ana-card-title');
    while (title.nextSibling) title.nextSibling.remove();
    card.insertAdjacentHTML('beforeend', html);
}
</script>
