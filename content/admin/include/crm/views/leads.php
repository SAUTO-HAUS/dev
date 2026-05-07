<?php defined('_DOIT') or die('Restricted access');

require_once(_ADM_INCL.'/crm/crm_lang.php');

$lang_url       = $_COOKIE['lang'] ?? 'ro';
$admin_dir_name = 'adminsauto';

crm_process_ttl($db, $prefx);
crm_process_rot_archive($db, $prefx);

$dept    = in_array($_GET['dept'] ?? '', ['stock','order','pruncul']) ? $_GET['dept'] : 'stock';
if (!empty($crm_access)) $dept = $crm_access;
$view    = in_array($_GET['view'] ?? '', ['leads','transaction','closed','junk']) ? $_GET['view'] : 'leads';
$from    = in_array($_GET['from'] ?? '', ['leads','transaction','closed']) ? $_GET['from'] : 'leads';

// Month/year filter
$leads_year  = (int)($_GET['ly'] ?? 0);
$leads_month = (int)($_GET['lm'] ?? 0);
$leads_all   = ($leads_year === 0 && $leads_month === 0);
if (!$leads_all) {
    $leads_year  = $leads_year  ?: (int)date('Y');
    $leads_month = $leads_month ?: (int)date('n');
    $leads_year  = max(2020, min((int)date('Y'), $leads_year));
    $leads_month = max(1, min(12, $leads_month));
    $leads_date_from = sprintf('%04d-%02d-01', $leads_year, $leads_month);
    $leads_date_to   = date('Y-m-t', strtotime($leads_date_from));
}
$src_raw_arr = isset($_GET['src']) ? (array)$_GET['src'] : ['0'];
$src_fs = [];
foreach ($src_raw_arr as $sr) {
    if (is_numeric($sr)) { $v = (int)$sr; if ($v !== 0) $src_fs[] = $v; }
    elseif ($sr !== '0') $src_fs[] = $sr;
}
$src_f = count($src_fs) === 1 ? $src_fs[0] : (empty($src_fs) ? 0 : $src_fs);
// Build src query string for use in URLs
function src_qs(array $src_fs): string {
    if (empty($src_fs)) return 'src%5B%5D=0';
    return implode('&', array_map(fn($v) => 'src%5B%5D='.urlencode((string)$v), $src_fs));
}
$src_qs = src_qs($src_fs);
$q       = trim($_GET['q'] ?? '');
$rot_warn = (int)crm_get_setting($db, $prefx, 'rot_warn_hours', 24);
$rot_max  = (int)crm_get_setting($db, $prefx, 'rot_max_hours', 48);
$can_see_all = crm_can_see_all($user_role, $user_id);

// prev_status filter for junk view
$junk_prev_statuses = [
    'leads'       => ['active','missed','unprocessed','processed'],
    'transaction' => ['transaction'],
    'closed'      => ['closed'],
];

if ($view === 'leads') {
    $view_statuses = ['active','missed','unprocessed','processed'];
} elseif ($view === 'transaction') {
    $view_statuses = ['transaction'];
} elseif ($view === 'junk') {
    $view_statuses = ['junk'];
} else {
    $view_statuses = ['closed'];
}

$placeholders = implode(',', array_fill(0, count($view_statuses), '?'));
$params = [];
$where_parts = [];
if (!empty($crm_my_leads)) {
    $where_parts[] = "l.owner_id = ?";
    $params[] = (int)$user_id;
} else {
    $where_parts[] = "l.department = ?";
    $params[] = $dept;
}
$where_parts[] = "l.status IN ($placeholders)";
foreach ($view_statuses as $s) $params[] = $s;
$junk_prev_sts = [];
if ($view === 'junk') {
    $junk_prev_sts = $junk_prev_statuses[$from];
    if ($q === '') {
        $prev_ph = implode(',', array_fill(0, count($junk_prev_sts), '?'));
        $where_parts[] = "l.prev_status IN ($prev_ph)";
        foreach ($junk_prev_sts as $s) $params[] = $s;
    }
    // When $q is set, prev_status filtering is handled inside $cond below
}
if (empty($crm_my_leads) && !$can_see_all) {
    $where_parts[] = "(l.owner_id = ? OR (l.status IN ('unprocessed','processed') AND l.owner_id IS NULL))";
    $params[] = (int)$user_id;
}
if (!$leads_all) {
    $where_parts[] = "DATE(l.created_at) BETWEEN ? AND ?";
    $params[] = $leads_date_from;
    $params[] = $leads_date_to;
}
if (!empty($src_fs)) {
    $src_or = [];
    foreach ($src_fs as $sv) {
        if (is_int($sv) && $sv > 0) { $src_or[] = "l.source_id = ?"; $params[] = $sv; }
        elseif ($sv === -1) { $src_or[] = "(l.source_id IS NULL AND l.origin != 'inbox')"; }
        elseif (is_string($sv) && strpos($sv, 'inbox:') === 0) {
            [, $ic, $ip] = array_pad(explode(':', $sv, 3), 3, '');
            $src_or[] = "EXISTS (SELECT 1 FROM {$prefx}_crm_inbox_sessions s WHERE s.id = (SELECT MIN(id) FROM {$prefx}_crm_inbox_sessions WHERE lead_id = l.id) AND s.channel = ? AND s.page_id = ?)";
            $params[] = $ic; $params[] = $ip;
        }
    }
    if (!empty($src_or)) $where_parts[] = '(' . implode(' OR ', $src_or) . ')';
}
if ($q !== '') {
    // Map display labels to DB values for channel and page_id
    $ch_map = ['facebook'=>'Facebook','instagram'=>'Instagram','telegram'=>'Telegram','viber'=>'Viber','site'=>'Site','999md'=>'999'];
    $pg_map = ['sautohaus_999md'=>'SAUTO-HAUS','regular_999md'=>'Comerciale','order_999md'=>'Comanda','korea_999md'=>'Encars','regular_telegram'=>'AutoMoldova','order_telegram'=>'AutoimportMD','725963964220309'=>'SAUTO','482777831588669'=>'Vânzări Piața Pruncu','cars'=>'Cars','ordercars'=>'Order Cars','order'=>'Order','credit'=>'Credit','tradein'=>'Trade-in','sale'=>'Sale','tyres'=>'Tyres','contacts'=>'Contacts','calculator'=>'Calculator'];
    $ql = mb_strtolower($q);

    // Find DB keys whose display label contains the search term
    $ch_matches = array_keys(array_filter($ch_map, function($label) use ($ql) { return strpos(mb_strtolower($label), $ql) !== false; }));
    $pg_matches = array_keys(array_filter($pg_map, function($label) use ($ql) { return strpos(mb_strtolower($label), $ql) !== false; }));
    // Also match raw DB values
    foreach (array_keys($ch_map) as $k) { if (strpos($k, $ql) !== false) $ch_matches[] = $k; }
    foreach (array_keys($pg_map) as $k) { if (strpos($k, $ql) !== false) $pg_matches[] = $k; }
    // "mesaj" or "message" = any lead with an inbox session
    $is_mesaj = strpos('mesaj', $ql) !== false || strpos('message', $ql) !== false;

    // Map search term to status DB values
    $status_label_map = [
        // RO
        'activ'        => 'active',
        'în lucru'     => 'processed',
        'in lucru'     => 'processed',
        'prelucrat'    => 'processed',
        'neprelucrat'  => 'unprocessed',
        'ratat'        => 'missed',
        'putrezit'     => 'rot',
        'necalitativ'  => 'junk',
        'tranzacție'   => 'transaction',
        'tranzactie'   => 'transaction',
        'finalizat'    => 'closed',
        // RU
        'активный'     => 'active',
        'активен'      => 'active',
        'в работе'     => 'processed',
        'обработан'    => 'processed',
        'необработан'  => 'unprocessed',
        'пропущен'     => 'missed',
        'гниёт'        => 'rot',
        'гнилой'       => 'rot',
        'некачественный' => 'junk',
        'сделка'       => 'transaction',
        'закрыт'       => 'closed',
        // EN
        'active'       => 'active',
        'in progress'  => 'processed',
        'processed'    => 'processed',
        'unprocessed'  => 'unprocessed',
        'missed'       => 'missed',
        'rotting'      => 'rot',
        'junk'         => 'junk',
        'transaction'  => 'transaction',
        'closed'       => 'closed',
    ];
    $matched_status = null;
    foreach ($status_label_map as $label => $db_val) {
        if (strpos(mb_strtolower($label), $ql) !== false || strpos($ql, mb_strtolower($label)) !== false) {
            $matched_status = $db_val;
            break;
        }
    }

    $cond = "l.phone LIKE ? OR l.client_name LIKE ? OR l.car_label LIKE ?
        OR EXISTS (SELECT 1 FROM {$prefx}_crm_sources _s WHERE _s.id = l.source_id AND _s.name LIKE ?)
        OR EXISTS (SELECT 1 FROM {$prefx}_adm_usr _u WHERE _u.id = l.owner_id AND _u.name LIKE ?)";
    $params[] = "%$q%"; $params[] = "%$q%"; $params[] = "%$q%"; $params[] = "%$q%"; $params[] = "%$q%";

    // Status search
    $junk_status_search = false;
    if ($view === 'junk' && $matched_status === 'junk') {
        array_splice($params, count($params) - 5, 5);
        $cond = "l.is_rot = 0";
        $junk_status_search = true;
    } elseif ($view === 'junk' && $matched_status === 'rot') {
        array_splice($params, count($params) - 5, 5);
        $cond = "l.is_rot = 1";
        $junk_status_search = true;
    } elseif ($matched_status === 'rot') {
        $cond .= " OR TIMESTAMPDIFF(HOUR, COALESCE(l.last_action_at, l.created_at), NOW()) >= 24";
    } elseif ($matched_status && in_array($matched_status, $view_statuses, true)) {
        $cond .= " OR l.status = ?";
        $params[] = $matched_status;
    }

    if (!$junk_status_search) {
        if ($is_mesaj) {
            $cond .= " OR EXISTS (SELECT 1 FROM {$prefx}_crm_inbox_sessions _ins WHERE _ins.lead_id = l.id)";
        } elseif (!empty($ch_matches) || !empty($pg_matches)) {
            $all_ch = array_unique($ch_matches);
            $all_pg = array_unique($pg_matches);
            if (!empty($all_ch)) {
                $ph = implode(',', array_fill(0, count($all_ch), '?'));
                $cond .= " OR EXISTS (SELECT 1 FROM {$prefx}_crm_inbox_sessions _ins WHERE _ins.lead_id = l.id AND _ins.channel IN ($ph))";
                foreach ($all_ch as $v) $params[] = $v;
            }
            if (!empty($all_pg)) {
                $ph = implode(',', array_fill(0, count($all_pg), '?'));
                $cond .= " OR EXISTS (SELECT 1 FROM {$prefx}_crm_inbox_sessions _ins WHERE _ins.lead_id = l.id AND _ins.page_id IN ($ph))";
                foreach ($all_pg as $v) $params[] = $v;
            }
        } else {
            // fallback: search raw in channel/page_id
            $cond .= " OR EXISTS (SELECT 1 FROM {$prefx}_crm_inbox_sessions _ins WHERE _ins.lead_id = l.id AND (_ins.channel LIKE ? OR _ins.page_id LIKE ?))";
            $params[] = "%$q%"; $params[] = "%$q%";
        }
    }

    $where_parts[] = "($cond)";
    $q_where_cond   = "($cond)";
    $q_where_params = array_splice($params, count($params) - substr_count($cond, '?'));
    $params         = array_merge($params, $q_where_params);
}
$where_sql = implode(' AND ', $where_parts);


$stmt = $db->prepare("
    SELECT l.*, COALESCE(s.name, sc.name) AS source_name, COALESCE(s.color, sc.color) AS source_color,
           u.name AS owner_name,
           d.inf AS doc_inf,
           al.action AS origin_action,
           ins.channel AS ins_channel,
           ins.page_id AS ins_page_id,
           cl.type AS call_type
    FROM {$prefx}_crm_leads l
    LEFT JOIN {$prefx}_crm_sources s ON s.id = l.source_id
    LEFT JOIN {$prefx}_crm_call_logs cl ON cl.id = (
        SELECT MIN(id) FROM {$prefx}_crm_call_logs WHERE lead_id = l.id
    )
    LEFT JOIN {$prefx}_crm_sources sc ON sc.id = cl.source_id
    LEFT JOIN {$prefx}_adm_usr u ON u.id = l.owner_id
    LEFT JOIN {$prefx}_docs_ctlg d ON d.id = l.doc_id
    LEFT JOIN {$prefx}_crm_audit_log al ON al.id = (
        SELECT MIN(id) FROM {$prefx}_crm_audit_log
        WHERE entity_type='lead' AND entity_id=l.id
    )
    LEFT JOIN {$prefx}_crm_inbox_sessions ins ON ins.id = (
        SELECT MIN(id) FROM {$prefx}_crm_inbox_sessions WHERE lead_id = l.id
    )
    WHERE $where_sql
    ORDER BY l.created_at DESC
    LIMIT 500
");
$stmt->execute($params);
$leads = $stmt->fetchAll(PDO::FETCH_OBJ);

// Auto-switch dept if search results are all from one dept
if ($q !== '' && !empty($leads)) {
    $result_depts = array_unique(array_column((array)$leads, 'department'));
    if (count($result_depts) === 1 && $result_depts[0] !== $dept) {
        $switch_dept = $result_depts[0];
        $redirect_params = $_GET;
        $redirect_params['dept'] = $switch_dept;
        $redirect_url = (parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH)) . '?' . http_build_query($redirect_params);
        header("Location: $redirect_url");
        exit;
    }
}

$sources = crm_get_sources($db, $prefx);

// car_context per lead from inbox sessions
$lead_car_context = [];
if (!empty($leads)) {
    $lead_ids = array_map(fn($l) => (int)$l->id, $leads);
    $ph_ids = implode(',', $lead_ids);
    $cc_rows = $db->query("SELECT lead_id, car_context FROM {$prefx}_crm_inbox_sessions WHERE lead_id IN ($ph_ids) AND car_context IS NOT NULL AND car_context<>'' GROUP BY lead_id")->fetchAll(PDO::FETCH_OBJ);
    foreach ($cc_rows as $cc) $lead_car_context[(int)$cc->lead_id] = $cc->car_context;
}

$dept_counts = [];
if (!empty($crm_my_leads)) {
    $ph_d = implode(',', array_fill(0, count($view_statuses), '?'));
    $p2 = array_merge([(int)$user_id], $view_statuses);
    $sc = $db->prepare("SELECT COUNT(*) FROM {$prefx}_crm_leads l WHERE l.owner_id = ? AND l.status IN ($ph_d)");
    $sc->execute($p2);
    $dept_counts['stock'] = (int)$sc->fetchColumn();
    $dept_counts['order'] = 0;
} else {
    foreach (['stock', 'order', 'pruncul'] as $d) {
        $p2 = [$d];
        $w2 = ["l.department = ?"];
        if ($view === 'junk') {
            $jp = $junk_prev_statuses[$from] ?? $junk_prev_statuses['leads'];
            $jph = implode(',', array_fill(0, count($jp), '?'));
            $w2[] = "l.status = 'junk'";
            $w2[] = "l.prev_status IN ($jph)";
            foreach ($jp as $s) $p2[] = $s;
        } else {
            $ph_d = implode(',', array_fill(0, count($view_statuses), '?'));
            $w2[] = "l.status IN ($ph_d)";
            foreach ($view_statuses as $s) $p2[] = $s;
        }
        if (!$can_see_all) { $w2[] = "(l.owner_id = ? OR (l.status IN ('unprocessed','processed') AND l.owner_id IS NULL))"; $p2[] = (int)$user_id; }
        if (!empty($src_fs)) {
            $src_or2 = [];
            foreach ($src_fs as $sv) {
                if (is_int($sv) && $sv > 0) { $src_or2[] = "l.source_id = ?"; $p2[] = $sv; }
                elseif ($sv === -1) { $src_or2[] = "(l.source_id IS NULL AND l.origin != 'inbox')"; }
                elseif (is_string($sv) && strpos($sv, 'inbox:') === 0) {
                    [, $ic2, $ip2] = array_pad(explode(':', $sv, 3), 3, '');
                    $src_or2[] = "EXISTS (SELECT 1 FROM {$prefx}_crm_inbox_sessions s WHERE s.id = (SELECT MIN(id) FROM {$prefx}_crm_inbox_sessions WHERE lead_id = l.id) AND s.channel = ? AND s.page_id = ?)";
                    $p2[] = $ic2; $p2[] = $ip2;
                }
            }
            if (!empty($src_or2)) $w2[] = '(' . implode(' OR ', $src_or2) . ')';
        }
        if ($q !== '' && isset($q_where_cond)) {
            $w2[] = $q_where_cond;
            foreach ($q_where_params as $qp) $p2[] = $qp;
        }
        if (!$leads_all) {
            $w2[] = "DATE(l.created_at) BETWEEN ? AND ?";
            $p2[] = $leads_date_from;
            $p2[] = $leads_date_to;
        }
        $sc = $db->prepare("SELECT COUNT(*) FROM {$prefx}_crm_leads l WHERE ".implode(' AND ', $w2));
        $sc->execute($p2);
        $dept_counts[$d] = (int)$sc->fetchColumn();
    }
}

// Counts per view tab
$view_tab_counts = [];
foreach (['leads' => ['active','missed','unprocessed'], 'transaction' => ['transaction'], 'closed' => ['closed'], 'junk' => ['junk']] as $vk => $vsts) {
    $vph = implode(',', array_fill(0, count($vsts), '?'));
    $vp = [$dept];
    $vw = ["l.department = ?"];
    foreach ($vsts as $s) $vp[] = $s;
    $vw[] = "l.status IN ($vph)";
    if ($vk === 'junk') {
        $cur_from = ($view !== 'junk') ? $view : $from;
        $jp = $junk_prev_statuses[$cur_from] ?? $junk_prev_statuses['leads'];
        $jph = implode(',', array_fill(0, count($jp), '?'));
        $vw[] = "l.prev_status IN ($jph)";
        foreach ($jp as $s) $vp[] = $s;
    }
    if (!$can_see_all) { $vw[] = "(l.owner_id = ? OR (l.status IN ('unprocessed','processed') AND l.owner_id IS NULL))"; $vp[] = (int)$user_id; }
    $sc = $db->prepare("SELECT COUNT(*) FROM {$prefx}_crm_leads l WHERE ".implode(' AND ', $vw));
    $sc->execute($vp);
    $view_tab_counts[$vk] = (int)$sc->fetchColumn();
}

// Junk count — filtered by dept when crm_access is set
$cur_from = ($view !== 'junk') ? $view : $from;
$jp_all = $junk_prev_statuses[$cur_from] ?? $junk_prev_statuses['leads'];
$jph_all = implode(',', array_fill(0, count($jp_all), '?'));
$jvw = ["l.status = 'junk'", "l.prev_status IN ($jph_all)"];
$jvp = $jp_all;
if (!empty($crm_access)) { $jvw[] = "l.department = ?"; $jvp[] = $crm_access; }
if (!$can_see_all) { $jvw[] = "(l.owner_id = ? OR l.owner_id IS NULL)"; $jvp[] = (int)$user_id; }
$jsc = $db->prepare("SELECT COUNT(*) FROM {$prefx}_crm_leads l WHERE ".implode(' AND ', $jvw));
$jsc->execute($jvp);
$junk_total_count = (int)$jsc->fetchColumn();

$view_slug = !empty($crm_my_leads) ? 'my_leads' : (($view === 'transaction') ? 'transaction' : (($view === 'closed') ? 'closed' : (($view === 'junk') ? 'junk' : 'leads')));
$base_url = "/$lang_url/$admin_dir_name/crm/$view_slug";

// Counts per status for filter modal
$status_filter = array_filter(explode(',', $_GET['sf'] ?? ''));
$status_filter = array_values(array_intersect($status_filter, ['active','processed','unprocessed','missed','rot']));
$sf_qs = !empty($status_filter) ? '&sf=' . urlencode(implode(',', $status_filter)) : '';

$status_counts = [];

// Count rotting first (24h+, not missed) — these show as "Putrezit" in UI
$rot_cp = [$dept];
$rot_cw = ["l.department = ?", "l.status IN ('active','processed','unprocessed')",
           "TIMESTAMPDIFF(HOUR, COALESCE(l.last_action_at, l.created_at), NOW()) >= 24"];
if (!$can_see_all) { $rot_cw[] = "(l.owner_id = ? OR (l.status = 'unprocessed' AND l.owner_id IS NULL))"; $rot_cp[] = (int)$user_id; }
$cs = $db->prepare("SELECT COUNT(*) FROM {$prefx}_crm_leads l WHERE ".implode(' AND ', $rot_cw));
$cs->execute($rot_cp);
$status_counts['rot'] = (int)$cs->fetchColumn();

// Count per status — exclude rotting leads (they show as Putrezit, not their real status)
foreach (['active','processed','unprocessed','missed'] as $st) {
    $cp = [$dept, $st];
    $cw = ["l.department = ?", "l.status = ?"];
    if (in_array($st, ['active','processed','unprocessed'])) {
        $cw[] = "TIMESTAMPDIFF(HOUR, COALESCE(l.last_action_at, l.created_at), NOW()) < 24";
    }
    if (!$can_see_all) { $cw[] = "(l.owner_id = ? OR (l.status = 'unprocessed' AND l.owner_id IS NULL))"; $cp[] = (int)$user_id; }
    $cs = $db->prepare("SELECT COUNT(*) FROM {$prefx}_crm_leads l WHERE ".implode(' AND ', $cw));
    $cs->execute($cp);
    $status_counts[$st] = (int)$cs->fetchColumn();
}

// Apply status filter to leads list
if (!empty($status_filter)) {
    $leads = array_values(array_filter($leads, function($lead) use ($status_filter) {
        // Putrezit = active/processed/unprocessed cu 24h+ fără acțiune
        $is_rot = ($lead->status !== 'missed' &&
                   in_array($lead->status, ['active','processed','unprocessed']) &&
                   crm_rot_class($lead->last_action_at) !== 'crm-rot-0');
        if (in_array('rot', $status_filter) && $is_rot) return true;
        // Statusurile normale — doar dacă nu e putrezit
        if (!$is_rot && in_array($lead->status, $status_filter)) return true;
        return false;
    }));
}
?>

<div id="crm-leads-wrap">

    <!-- Header -->
    <div class="calls-header">
        <div class="calls-header-title">
            <span class="calls-header-icon">
                <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M23 21v-2a4 4 0 0 0-3-3.87"/><path d="M16 3.13a4 4 0 0 1 0 7.75"/>
                </svg>
            </span>
            <span class="calls-header-text">
                <?php if (!empty($crm_my_leads)): ?>
                    <?= $cL['tab_my_leads'] ?? 'Lead-urile mele' ?>
                <?php elseif ($view === 'junk'): ?>
                    <?= $cL['tab_junk'] ?? 'Arhivă Leaduri' ?>
                <?php elseif ($view === 'transaction'): ?>
                    <?= $cL['tab_transactions'] ?? 'Tranzacții' ?>
                <?php elseif ($view === 'closed'): ?>
                    <?= $cL['tab_closed'] ?? 'Tranzacții Încheiate' ?>
                <?php else: ?>
                    <?= $dept === 'stock' ? $cL['dept_stock'] : ($dept === 'pruncul' ? $cL['dept_pruncul'] : $cL['dept_order']) ?>
                <?php endif; ?>
                <?php $total_view = $dept_counts[$dept] ?? 0; if ($total_view > 0): ?>
                    <span style="display:inline-flex;align-items:center;justify-content:center;min-width:22px;height:22px;padding:0 6px;border-radius:50px;background:#E61E2D;color:#fff;font-size:0.72rem;font-weight:700;margin-left:6px;"><?= $total_view ?></span>
                <?php endif; ?>
            </span>
        </div>
        <?php
        $show_junk_btn = true;
        if ($show_junk_btn):
        $junk_href = $view==='junk'
            ? "/$lang_url/$admin_dir_name/crm/$from?dept=$dept&$src_qs"
            : "/$lang_url/$admin_dir_name/crm/junk?dept=$dept&from=" . (!empty($crm_my_leads) ? 'leads' : $view) . "&$src_qs&q=".urlencode($q);
        ?>
        <div style="display:flex;align-items:stretch;gap:0.5rem;margin-left:0.5rem;" id="leads-header-right">
        <a class="crm-btn <?= $view==='junk' ? 'crm-btn-dark' : 'crm-btn-outline' ?> crm-btn-sm"
           href="<?= $junk_href ?>"
           style="display:inline-flex;align-items:center;gap:0.35rem;text-decoration:none;">
            <?php if ($view === 'junk'): ?>
                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg"><path fill-rule="evenodd" clip-rule="evenodd" d="M6.3508 12.7499L11.2096 17.4615L10.1654 18.5383L3.42264 11.9999L10.1654 5.46148L11.2096 6.53833L6.3508 11.2499L21 11.2499L21 12.7499L6.3508 12.7499Z" fill="currentColor"/></svg>
                <?= $cL['tab_leads_active'] ?? 'Leaduri active' ?>

            <?php else: ?>
                <svg width="13" height="13" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg"><path d="M9 12C9 11.5341 9 11.3011 9.07612 11.1173C9.17761 10.8723 9.37229 10.6776 9.61732 10.5761C9.80109 10.5 10.0341 10.5 10.5 10.5H13.5C13.9659 10.5 14.1989 10.5 14.3827 10.5761C14.6277 10.6776 14.8224 10.8723 14.9239 11.1173C15 11.3011 15 11.5341 15 12C15 12.4659 15 12.6989 14.9239 12.8827C14.8224 13.1277 14.6277 13.3224 14.3827 13.4239C14.1989 13.5 13.9659 13.5 13.5 13.5H10.5C10.0341 13.5 9.80109 13.5 9.61732 13.4239C9.37229 13.3224 9.17761 13.1277 9.07612 12.8827C9 12.6989 9 12.4659 9 12Z" stroke="currentColor" stroke-width="1.5"/><path d="M20.5 7V13C20.5 16.7712 20.5 18.6569 19.3284 19.8284C18.1569 21 16.2712 21 12.5 21H11.5M3.5 7V13C3.5 16.7712 3.5 18.6569 4.67157 19.8284C5.37634 20.5332 6.3395 20.814 7.81608 20.9259" stroke="currentColor" stroke-width="1.5" stroke-linecap="round"/><path d="M12 3H4C3.05719 3 2.58579 3 2.29289 3.29289C2 3.58579 2 4.05719 2 5C2 5.94281 2 6.41421 2.29289 6.70711C2.58579 7 3.05719 7 4 7H20C20.9428 7 21.4142 7 21.7071 6.70711C22 6.41421 22 5.94281 22 5C22 4.05719 22 3.58579 21.7071 3.29289C21.4142 3 20.9428 3 20 3H16" stroke="currentColor" stroke-width="1.5" stroke-linecap="round"/></svg>
                <?= $cL['tab_junk'] ?>
                <?php if ($junk_total_count > 0): ?><span class="calls-tab-count"><?= $junk_total_count ?></span><?php endif; ?>
            <?php endif; ?>
        </a>
        <?php endif; // end show_junk_btn ?>
        <?php
        // Month navigator
        $mn_ro = ['','Ianuarie','Februarie','Martie','Aprilie','Mai','Iunie','Iulie','August','Septembrie','Octombrie','Noiembrie','Decembrie'];
        if ($leads_all) {
            $nav_label = 'Toate';
            $prev_ly = (int)date('Y'); $prev_lm = (int)date('n') - 1;
            if ($prev_lm < 1) { $prev_lm = 12; $prev_ly--; }
            $is_future_month = true;
            $next_ly = 0; $next_lm = 0;
        } else {
            $nav_label = $mn_ro[$leads_month] . ' ' . $leads_year;
            $prev_lm = $leads_month - 1; $prev_ly = $leads_year;
            if ($prev_lm < 1) { $prev_lm = 12; $prev_ly--; }
            $next_lm = $leads_month + 1; $next_ly = $leads_year;
            if ($next_lm > 12) { $next_lm = 1; $next_ly++; }
            $is_future_month = ($next_ly > (int)date('Y')) || ($next_ly == (int)date('Y') && $next_lm > (int)date('n'));
        }
        $nav_base = "/$lang_url/$admin_dir_name/crm/leads?dept=$dept&view=$view&{$src_qs}&q=" . urlencode($q) . $sf_qs;
        $prev_url = $nav_base . "&ly=$prev_ly&lm=$prev_lm";
        $all_url  = $nav_base;
        $next_url = !$is_future_month ? $nav_base . "&ly=$next_ly&lm=$next_lm" : '';
        ?>
        <div id="leads-month-nav" style="display:flex;align-items:stretch;gap:0;border:1px solid #e0e0e0;border-radius:8px;overflow:hidden;background:#f3f4f6;font-size:0.82rem;font-weight:600;">
            <a href="<?= $prev_url ?>" style="display:flex;align-items:center;justify-content:center;padding:0 0.75rem;color:#555;text-decoration:none;font-size:1.1rem;line-height:1;">‹</a>
            <a href="<?= $all_url ?>" style="display:flex;align-items:center;justify-content:center;flex:1;padding:0 0.7rem;color:<?= $leads_all?'#E61E2D':'#444' ?>;font-weight:600;text-decoration:none;white-space:nowrap;border-left:1px solid #e0e0e0;border-right:1px solid #e0e0e0;"><?= $nav_label ?></a>
            <?php if ($next_url): ?>
            <a href="<?= $next_url ?>" style="display:flex;align-items:center;justify-content:center;padding:0 0.75rem;color:#555;text-decoration:none;font-size:1.1rem;line-height:1;">›</a>
            <?php else: ?>
            <span style="display:flex;align-items:center;justify-content:center;padding:0 0.75rem;color:#ccc;font-size:1.1rem;line-height:1;">›</span>
            <?php endif; ?>
        </div>
        </div><!-- end leads-header-right -->
    </div>

    <!-- Tabs row -->
    <form method="GET" action="<?= $base_url ?>" id="leads-filter-form" style="margin:0;">
        <input type="hidden" name="view" value="<?= htmlspecialchars($view) ?>">
        <div class="calls-tabs">
            <!-- Dept tabs -->
            <?php if (empty($crm_my_leads) && empty($crm_access)):
            $dept_page = ($view === 'junk') ? 'junk' : $view;
            $dept_extra = ($view === 'junk') ? "&from=$from" : '';
            ?>
            <a class="calls-tab calls-tab-dept <?= $dept==='stock'?'active':'' ?>"
               href="/<?= $lang_url ?>/<?= $admin_dir_name ?>/crm/<?= $dept_page ?>?dept=stock<?= $dept_extra ?>&<?= $src_qs ?>&q=<?= urlencode($q) ?><?= $sf_qs ?>">
                <?= $cL['dept_stock'] ?>
                <?php if ($dept_counts['stock'] > 0): ?><span class="calls-tab-count"><?= $dept_counts['stock'] ?></span><?php endif; ?>
            </a>
            <a class="calls-tab calls-tab-dept calls-tab-order <?= $dept==='order'?'active':'' ?>"
               href="/<?= $lang_url ?>/<?= $admin_dir_name ?>/crm/<?= $dept_page ?>?dept=order<?= $dept_extra ?>&<?= $src_qs ?>&q=<?= urlencode($q) ?><?= $sf_qs ?>">
                <?= $cL['dept_order'] ?>
                <?php if ($dept_counts['order'] > 0): ?><span class="calls-tab-count"><?= $dept_counts['order'] ?></span><?php endif; ?>
            </a>
            <a class="calls-tab calls-tab-dept <?= $dept==='pruncul'?'active':'' ?>"
               href="/<?= $lang_url ?>/<?= $admin_dir_name ?>/crm/<?= $dept_page ?>?dept=pruncul<?= $dept_extra ?>&<?= $src_qs ?>&q=<?= urlencode($q) ?><?= $sf_qs ?>">
                <?= $cL['dept_pruncul'] ?>
                <?php if (($dept_counts['pruncul'] ?? 0) > 0): ?><span class="calls-tab-count"><?= $dept_counts['pruncul'] ?></span><?php endif; ?>
            </a>
            <?php endif; ?>

            <div style="margin-left:auto;display:flex;align-items:center;gap:0.5rem;">
                <?php if ($view === 'leads'): ?>
                <button type="button" onclick="leadsFilterOpen()" style="display:flex;align-items:center;gap:0.35rem;background:<?= !empty($status_filter)?'#E61E2D':'#f3f4f6' ?>;color:<?= !empty($status_filter)?'#fff':'#444' ?>;border:1px solid <?= !empty($status_filter)?'#c01020':'#d1d5db' ?>;border-radius:8px;padding:0.35rem 0.75rem;font-size:0.82rem;font-weight:600;cursor:pointer;white-space:nowrap;">
                    <img src="/content/admin/include/crm/icons/filter.svg" width="14" height="14" <?= !empty($status_filter) ? 'style="filter:brightness(0) invert(1);"' : '' ?>>
                    <?= $cL['btn_filter'] ?? 'Filtru' ?>
                    <?php if (!empty($status_filter)): ?><span style="background:rgba(255,255,255,0.3);border-radius:4px;padding:0 5px;"><?= count($status_filter) ?></span><?php endif; ?>
                </button>
                <?php endif; ?>
                <!-- Source filter multi-select -->
                <?php
                $src_all_opts = [];
                foreach ($sources as $src) $src_all_opts[] = ['val' => (string)$src->id, 'label' => $src->name, 'group' => null];
                $src_all_opts[] = ['val' => '-1', 'label' => $cL['source_unknown'] ?? 'Necunoscută', 'group' => null];
                $inbox_groups = [
                    'Mesaje 999.md' => [
                        'inbox:999md:order_999md' => 'Mesaj · 999 · Comanda',
                        'inbox:999md:korea_999md' => 'Mesaj · 999 · Corea',
                        'inbox:999md:sautohaus_999md' => 'Mesaj · 999 · Stock',
                        'inbox:999md:regular_999md' => 'Mesaj · 999 · Comerciale',
                    ],
                    'Mesaje Telegram' => [
                        'inbox:telegram:order_telegram' => 'Mesaj · Telegram · Comanda',
                        'inbox:telegram:regular_telegram' => 'Mesaj · Telegram · Stock',
                    ],
                    'Mesaje Facebook / Instagram' => [
                        'inbox:facebook:725963964220309' => 'Mesaj · Facebook · SAUTO',
                        'inbox:facebook:482777831588669' => 'Mesaj · Facebook · Piața Pruncu',
                    ],
                    'Mesaje Site' => [
                        'inbox:site:cars' => 'Mesaj · Site · Stock',
                        'inbox:site:ordercars' => 'Mesaj · Site · Order Cars',
                        'inbox:site:order' => 'Mesaj · Site · Order',
                        'inbox:site:credit' => 'Mesaj · Site · Credit',
                        'inbox:site:tradein' => 'Mesaj · Site · Trade-in',
                        'inbox:site:sale' => 'Mesaj · Site · Sale',
                    ],
                    'Mesaje Viber' => [
                        'inbox:viber:' => 'Mesaj · Viber',
                    ],
                ];
                foreach ($inbox_groups as $grp_label => $grp_opts) {
                    foreach ($grp_opts as $v => $l) $src_all_opts[] = ['val' => $v, 'label' => $l, 'group' => $grp_label];
                }
                $src_fs_str = array_map('strval', $src_fs);
                $src_selected_labels = [];
                foreach ($src_all_opts as $o) { if (in_array($o['val'], $src_fs_str, true)) $src_selected_labels[] = $o['label']; }
                $src_btn_label = empty($src_selected_labels) ? ($cL['all'] ?? 'Toate') : implode(', ', $src_selected_labels);
                $src_active = !empty($src_fs);
                ?>
                <button type="button" id="src-multiselect-btn"
                    onclick="srcMultiToggle()"
                    style="display:flex;align-items:center;gap:0.35rem;background:<?= $src_active?'#E61E2D':'#f3f4f6' ?>;color:<?= $src_active?'#fff':'#444' ?>;border:1px solid <?= $src_active?'#c01020':'#d1d5db' ?>;border-radius:8px;padding:0.35rem 0.75rem;font-size:0.82rem;font-weight:600;cursor:pointer;white-space:nowrap;max-width:200px;overflow:hidden;text-overflow:ellipsis;box-sizing:border-box;">
                    <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><line x1="4" y1="6" x2="20" y2="6"/><line x1="4" y1="12" x2="14" y2="12"/><line x1="4" y1="18" x2="10" y2="18"/></svg>
                    <span style="overflow:hidden;text-overflow:ellipsis;"><?= htmlspecialchars($src_btn_label) ?></span>
                    <?php if ($src_active): ?><span style="background:rgba(255,255,255,0.3);border-radius:4px;padding:0 5px;font-size:0.75rem;"><?= count($src_fs) ?></span><?php endif; ?>
                </button>
                <script>
                function srcMultiToggle() {
                    document.getElementById('src-multiselect-modal').style.display = '';
                }
                function srcMultiClose() {
                    document.getElementById('src-multiselect-modal').style.display = 'none';
                }
                function srcCheckAll(cb) {
                    document.querySelectorAll('#src-multiselect-modal input[name="src[]"]').forEach(function(c){ c.checked = false; });
                }
                function srcCheckChange() {
                    var any = Array.from(document.querySelectorAll('#src-multiselect-modal input[name="src[]"]')).some(function(c){ return c.checked; });
                    document.getElementById('src-check-all').checked = !any;
                }
                function srcApply() {
                    srcMultiClose();
                    var form = document.getElementById('leads-filter-form');
                    // Remove old src[] hidden inputs
                    form.querySelectorAll('input[name="src[]"]').forEach(function(el){ el.remove(); });
                    // Copy checked values from modal into form as hidden inputs
                    var checked = document.querySelectorAll('#src-multiselect-modal input[name="src[]"]:checked');
                    if (checked.length === 0) {
                        var h = document.createElement('input');
                        h.type = 'hidden'; h.name = 'src[]'; h.value = '0';
                        form.appendChild(h);
                    } else {
                        checked.forEach(function(c) {
                            var h = document.createElement('input');
                            h.type = 'hidden'; h.name = 'src[]'; h.value = c.value;
                            form.appendChild(h);
                        });
                    }
                    form.submit();
                }
                </script>
                <!-- Search -->
                <div class="calls-search-wrap">
                    <input type="text" id="leads-search" name="q" value="<?= htmlspecialchars($q) ?>" placeholder="<?= htmlspecialchars($cL['leads_search_ph']) ?>">
                    <?php if ($q): ?>
                    <a href="<?= $base_url ?>?dept=<?= $dept ?>&view=<?= $view ?>&<?= $src_qs ?>" class="calls-search-reset">✕</a>
                    <?php else: ?>
                    <span class="calls-search-ico"><img src="/content/admin/include/crm/icons/search.svg" width="15" height="15"></span>
                    <?php endif; ?>
                </div>
            </div>

        </div>
    </form>
    <script>
    (function(){
        var inp = document.getElementById('leads-search');
        if (!inp) return;
        var t = null;
        inp.addEventListener('input', function(){
            clearTimeout(t);
            t = setTimeout(function(){
                var params = new URLSearchParams(window.location.search);
                var v = inp.value.trim();
                if (v) params.set('q', v); else params.delete('q');
                params.delete('p');
                window.location.href = '<?= $base_url ?>?' + params.toString();
            }, 1000);
        });
    })();
    </script>

    <!-- Table -->
    <div class="calls-table-wrap">
        <table class="calls-tbl">
            <thead>
                <tr>
                    <th style="width:5%;"><?= $cL['col_date'] ?></th>
                    <th style="width:10%;"><?= $cL['col_status'] ?></th>
                    <th style="width:12%;"><?= $cL['col_source'] ?></th>
                    <th style="width:24%;white-space:nowrap;"><?= $cL['col_content'] ?></th>
                    <th style="width:18%;"><?= $cL['lbl_car'] ?? 'Marcă, Model' ?></th>
                    <th style="width:3%;"><?= $cL['col_transcript'] ?></th>
                    <th style="width:13%;white-space:nowrap;"><?= $cL['col_owner'] ?></th>
                    <th style="width:15%;"></th>
                </tr>
            </thead>
            <tbody>
            <?php if (!empty($leads)): ?>
            <?php
            $last_day = '';
            $today     = date('Y-m-d');
            $yesterday = date('Y-m-d', strtotime('-1 day'));
            $leads_per_day = [];
            foreach ($leads as $l) {
                $d = date('Y-m-d', strtotime($l->created_at));
                $leads_per_day[$d] = ($leads_per_day[$d] ?? 0) + 1;
            }
            ?>
            <?php foreach ($leads as $lead):
                $rot_cls    = crm_rot_class($lead->last_action_at, $rot_warn, $rot_max);
                $phone_fmt  = crm_format_phone($lead->phone);
                $detail_url = "/$lang_url/$admin_dir_name/crm/lead?id={$lead->id}";
                $src_color  = $lead->source_color ?: '#888';
                $src_name   = $lead->source_name  ?: $cL['source_unknown'];
                $is_pool    = !$lead->owner_id;
                // Marquee content: transcript or last message
                $marquee_text = '';
                if (!empty($lead->transcript)) {
                    $marquee_text = $lead->transcript;
                }
                // Car info from doc
                $car = '';
                if (!empty($lead->doc_inf)) {
                    $inf_parts = [];
                    foreach (explode('&&', $lead->doc_inf) as $part) {
                        $kv = explode('==', $part, 2);
                        if (count($kv) === 2) $inf_parts[trim($kv[0])] = trim($kv[1]);
                    }
                    $br = $inf_parts['br'] ?? '';
                    $mo = $inf_parts['mo'] ?? '';
                    $yr = $inf_parts['yr'] ?? '';
                    $car = trim(ucwords(strtolower(str_replace('_',' ',$br))) . ' ' . ucwords(str_replace('_',' ',$mo)) . ($yr ? ' ' . $yr : ''));
                }
                $lead_day = date('Y-m-d', strtotime($lead->created_at));
                if ($lead_day !== $last_day) {
                    $last_day = $lead_day;
                    $day_ts = strtotime($lead_day);
                    $dow = (int)date('w', $day_ts);
                    $dom = (int)date('j', $day_ts);
                    $mon = (int)date('n', $day_ts) - 1;
                    $yr2 = date('Y', $day_ts);
                    $day_names   = $cL['day_names']   ?? ['Sun','Mon','Tue','Wed','Thu','Fri','Sat'];
                    $month_names = $cL['month_names'] ?? ['Jan','Feb','Mar','Apr','May','Jun','Jul','Aug','Sep','Oct','Nov','Dec'];
                    if ($lead_day === $today)     $day_label = $cL['day_today']     ?? 'Astăzi';
                    elseif ($lead_day === $yesterday) $day_label = $cL['day_yesterday'] ?? 'Ieri';
                    else $day_label = ($day_names[$dow] ?? '') . ', ' . $dom . ' ' . ($month_names[$mon] ?? '') . ($yr2 !== date('Y') ? ' ' . $yr2 : '');
            ?>
            <tr class="crm-leads-day-sep">
                <td colspan="8"><span class="day-label"><?= htmlspecialchars($day_label) ?> <span style="opacity:0.7;font-weight:400;">(<?= $leads_per_day[$lead_day] ?? 0 ?>)</span></span></td>
            </tr>
            <?php } ?>
            <tr class="crm-leads-row <?= $rot_cls ?>" data-lead-id="<?= $lead->id ?>" style="position:relative;overflow:hidden;" onclick="window.location='<?= $detail_url ?>'">

                <!-- Col 1: Data + origin icon -->
                <?php
                $lead_origin  = $lead->origin ?? '';
                $origin_audit = $lead->origin_action ?? '';
                $inbox_ch     = $lead->ins_channel ?? null;
                $inbox_pg     = $lead->ins_page_id ?? null;

                $inbox_ch_labels = [
                    'facebook'  => 'Facebook',
                    'instagram' => 'Instagram',
                    'telegram'  => 'Telegram',
                    'viber'     => 'Viber',
                    'site'      => 'Site',
                    '999md'     => '999',
                ];
                $inbox_pg_labels = [
                    'sautohaus_999md'  => 'SAUTO-HAUS',
                    'regular_999md'    => 'Comerciale',
                    'order_999md'      => 'Comanda',
                    'korea_999md'      => 'Encars',
                    'regular_telegram' => 'AutoMoldova',
                    'order_telegram'   => 'AutoimportMD',
                    '725963964220309'  => 'SAUTO',
                    '482777831588669'  => 'Vânzări Piața Pruncu',
                    'cars'             => 'Cars',
                    'ordercars'        => 'Order Cars',
                    'order'            => 'Order',
                    'credit'           => 'Credit',
                    'tradein'          => 'Trade-in',
                    'sale'             => 'Sale',
                    'tyres'            => 'Tyres',
                    'contacts'         => 'Contacts',
                    'calculator'       => 'Calculator',
                ];
                $inbox_label = null;
                if ($inbox_ch) {
                    $ch_label = $inbox_ch_labels[$inbox_ch] ?? ucfirst($inbox_ch);
                    $pg_label = ($inbox_pg && isset($inbox_pg_labels[$inbox_pg])) ? $inbox_pg_labels[$inbox_pg] : null;
                    $inbox_label = 'Mesaj · ' . $ch_label . ($pg_label ? ' · ' . $pg_label : '');
                }

                $origin_style = 'opacity:0.7;';
                if ($lead_origin === 'manual') {
                    $origin_icon = '/content/admin/include/crm/icons/hand-lead.svg';
                    $origin_tip  = 'Manual';
                } elseif ($lead_origin === 'call' || strpos($origin_audit, '_from_call') !== false) {
                    if (($lead->call_type ?? '') === 'out') {
                        $origin_icon  = '/content/admin/include/crm/icons/phone-outgoing-lead.svg';
                        $origin_style = 'opacity:0.7;';
                        $origin_tip   = 'Apel ieșit';
                    } else {
                        $origin_icon  = '/content/admin/include/crm/icons/phone-answered.svg';
                        $origin_style = 'opacity:0.7;';
                        $origin_tip   = 'Apel intrat';
                    }
                } elseif ($lead_origin === 'inbox' || $inbox_ch || strpos($origin_audit, 'lead_created_from_') !== false) {
                    $origin_icon = '/content/admin/include/crm/icons/sms-lead.svg';
                    $origin_tip  = $inbox_ch ? ucfirst($inbox_ch) : 'Mesaj';
                } else {
                    $origin_icon = '/content/admin/include/crm/icons/hand-lead.svg';
                    $origin_tip  = 'Manual';
                }
                ?>
                <td style="white-space:nowrap;">
                    <div class="leads-dt-desktop" style="font-size:0.82rem;font-weight:600;color:#191919;"><?= date('d.m', strtotime($lead->created_at)) ?></div>
                    <div class="leads-dt-desktop" style="display:flex;align-items:center;gap:0.3rem;margin-top:2px;">
                        <span style="font-size:0.75rem;color:#888;"><?= date('H:i', strtotime($lead->created_at)) ?></span>
                        <img src="<?= $origin_icon ?>" width="18" height="18" title="<?= htmlspecialchars($origin_tip) ?>" style="<?= $origin_style ?>">
                    </div>
                    <div class="leads-dt-mobile" style="display:none;font-size:0.75rem;font-weight:600;color:#191919;"><?= date('d.m', strtotime($lead->created_at)) ?>/<?= date('H:i', strtotime($lead->created_at)) ?><img src="<?= $origin_icon ?>" width="14" height="14" title="<?= htmlspecialchars($origin_tip) ?>" style="<?= $origin_style ?>margin-left:4px;vertical-align:middle;"></div>
                </td>

                <!-- Col 2: Status -->
                <td>
                    <div style="display:flex;align-items:center;gap:0.4rem;flex-wrap:nowrap;">
                        <?php $is_rot = ($rot_cls !== 'crm-rot-0' && $lead->status !== 'missed'); ?>
                        <span class="crm-status <?= $is_rot ? 'rot' : $lead->status ?>"><?= $is_rot ? strtoupper($cL['filter_rot'] ?? 'PUTREZIT') : crm_status_label($lead->status, $crm_lang) ?></span>
                        <?php if ($lead->phone): $clean_phone = preg_replace('/\D/', '', $lead->phone); ?>
                        <a href="viber://chat?number=+<?= $clean_phone ?>" title="Viber" class="crm-contact-icon">
                            <img src="/content/admin/include/crm/icons/viber.svg" style="width:18px;height:18px;object-fit:contain;display:block;">
                        </a>
                        <a href="https://wa.me/<?= $clean_phone ?>" target="_blank" title="WhatsApp" class="crm-contact-icon">
                            <img src="/content/admin/include/crm/icons/whatsapp.svg" style="width:18px;height:18px;object-fit:contain;display:block;">
                        </a>
                        <?php endif; ?>
                    </div>
                </td>

                <!-- Col 3: Sursă -->
                <td>
                    <?php if ($inbox_label): ?>
                    <span class="crm-source-badge" style="background:#555;font-size:0.7rem;"><?= htmlspecialchars($inbox_label) ?></span>
                    <?php else: ?>
                    <span class="crm-source-badge" style="background:<?= htmlspecialchars($src_color) ?>;"><?= htmlspecialchars($src_name) ?></span>
                    <?php endif; ?>
                </td>

                <!-- Col 4: Client + Conținut -->
                <td>
                    <div style="display:flex;align-items:center;gap:0.5rem;flex-wrap:wrap;">
                        <span style="font-weight:700;font-size:0.92rem;color:#111;white-space:nowrap;"><?= htmlspecialchars($phone_fmt) ?></span>
                        <?php if ($car): ?>
                        <span style="font-size:0.75rem;color:#888;font-weight:600;">· <?= htmlspecialchars($car) ?></span>
                        <?php endif; ?>
                        <?php if ($lead->phone): $clean_phone = preg_replace('/\D/', '', $lead->phone); ?>
                        <a href="viber://chat?number=+<?= $clean_phone ?>" title="Viber" class="leads-contact-icon-mobile" onclick="event.stopPropagation()">
                            <img src="/content/admin/include/crm/icons/viber.svg" style="width:20px;height:20px;object-fit:contain;display:block;">
                        </a>
                        <a href="https://wa.me/<?= $clean_phone ?>" target="_blank" title="WhatsApp" class="leads-contact-icon-mobile" onclick="event.stopPropagation()">
                            <img src="/content/admin/include/crm/icons/whatsapp.svg" style="width:20px;height:20px;object-fit:contain;display:block;">
                        </a>
                        <?php endif; ?>
                        <?php if ($marquee_text): ?>
                        <span class="leads-contact-icon-mobile calls-txt-btn"
                            onclick="event.stopPropagation(); crmShowTooltip(event, 'ltt-<?= $lead->id ?>')"
                            style="cursor:pointer;">
                            <img src="/content/admin/include/crm/icons/texts.svg" style="width:34px;height:34px;object-fit:contain;display:block;">
                        </span>
                        <div id="ltt-<?= $lead->id ?>" style="display:none;position:fixed;z-index:9999;background:#1e1e1e;border-radius:8px;padding:0.85rem 1rem;font-size:0.78rem;color:#f5f5f5;max-width:340px;min-width:200px;line-height:1.65;white-space:pre-wrap;box-shadow:0 6px 24px rgba(0,0,0,.4);"><?= htmlspecialchars($marquee_text) ?></div>
                        <?php endif; ?>
                    </div>
                </td>

                <!-- Col Marcă, Model -->
                <?php $lead_car = $lead_car_context[(int)$lead->id] ?? $lead->car_label ?? $car; ?>
                <td style="font-size:0.82rem;color:#333;"><?= htmlspecialchars($lead_car ?: '—') ?></td>

                <!-- Col T: Transcriere -->
                <td style="text-align:center;width:36px;">
                    <?php if ($marquee_text): ?>
                    <span class="calls-txt-btn leads-txt-desktop"
                        onclick="event.stopPropagation(); crmShowTooltip(event, 'ltt-<?= $lead->id ?>')">
                        <img src="/content/admin/include/crm/icons/texts.svg" class="calls-txt-icon">
                    </span>
                    <?php endif; ?>
                </td>

                <!-- Col 5: Manager -->
                <td style="white-space:nowrap;">
                    <?php if ($is_pool): ?>
                    <button class="crm-btn crm-btn-primary crm-btn-sm" style="white-space:nowrap;" onclick="event.stopPropagation(); crmTakeLead(<?= $lead->id ?>, this)"><?= $cL['btn_take'] ?></button>
                    <?php else: ?>
                    <span style="font-size:0.8rem;color:#444;"><?= htmlspecialchars($lead->owner_name ?: '—') ?></span>
                    <?php endif; ?>
                </td>

                <!-- Col 6: More + Archive -->
                <td style="width:72px;">
                    <div style="display:flex;align-items:center;justify-content:space-between;padding:0 4px;">
                        <button type="button" onclick="event.stopPropagation(); leadsMoreOpen(<?= $lead->id ?>, '<?= $lead->department ?>');" class="leads-more-btn<?= $view === 'junk' ? ' leads-more-btn-junk' : '' ?>" style="background:none;border:none;cursor:pointer;padding:0.2rem;">
                            <img src="/content/admin/include/crm/icons/more.svg" width="22" height="22">
                        </button>
                        <?php if ($view !== 'junk'): ?>
                        <button type="button" onclick="event.stopPropagation(); leadsArchiveDirect(<?= $lead->id ?>);" class="leads-archive-btn leads-archive-btn-desktop" title="<?= htmlspecialchars($cL['btn_delete_lead']) ?>">
                            <img src="/content/admin/include/crm/icons/archive.svg" width="18" height="18">
                        </button>
                        <?php endif; ?>

                    </div>
                </td>
            </tr>
            <?php endforeach; ?>
            <?php else: ?>
            <tr><td colspan="8" class="calls-empty">📭 <?= $cL['no_leads'] ?></td></tr>
            <?php endif; ?>
            </tbody>
        </table>
    </div>

</div>

<!-- Source Filter Modal -->
<div id="src-multiselect-modal" style="display:none;position:fixed;inset:0;z-index:9998;background:rgba(0,0,0,0.35);" onclick="if(event.target===this)srcMultiClose()">
    <div style="position:absolute;top:50%;left:50%;transform:translate(-50%,-50%);background:#fff;border-radius:14px;width:300px;max-height:80vh;display:flex;flex-direction:column;box-shadow:0 8px 32px rgba(0,0,0,.18);overflow:hidden;">
        <div style="display:flex;align-items:center;justify-content:space-between;padding:1rem 1.25rem 0.75rem;border-bottom:1px solid #f0f0f0;">
            <span style="font-weight:700;font-size:0.95rem;color:#191919;"><?= $cL['src_filter_title'] ?? 'Sursă' ?></span>
            <button type="button" onclick="srcMultiClose()" style="background:none;border:none;font-size:1.2rem;color:#888;cursor:pointer;line-height:1;">✕</button>
        </div>
        <div style="overflow-y:auto;padding:6px 0;">
            <label style="display:flex;align-items:center;gap:8px;padding:6px 14px;font-size:0.82rem;cursor:pointer;font-weight:700;border-bottom:1px solid #f0f0f0;">
                <input type="checkbox" id="src-check-all" onchange="srcCheckAll(this)" <?= empty($src_fs)?'checked':'' ?>> <?= $cL['all'] ?? 'Toate' ?>
            </label>
            <?php
            $last_group = null;
            foreach ($src_all_opts as $o):
                if ($o['group'] !== $last_group):
                    $last_group = $o['group'];
                    if ($o['group'] !== null):
            ?>
            <div style="padding:6px 14px 2px;font-size:0.72rem;font-weight:700;color:#aaa;text-transform:uppercase;letter-spacing:0.05em;"><?= htmlspecialchars($o['group']) ?></div>
            <?php endif; endif; ?>
            <label style="display:flex;align-items:center;gap:8px;padding:5px 14px;font-size:0.82rem;cursor:pointer;" onmouseenter="this.style.background='#f9fafb'" onmouseleave="this.style.background=''">
                <input type="checkbox" name="src[]" value="<?= htmlspecialchars($o['val']) ?>" onchange="srcCheckChange()" <?= in_array($o['val'], $src_fs_str, true)?'checked':'' ?>>
                <?= htmlspecialchars($o['label']) ?>
            </label>
            <?php endforeach; ?>
        </div>
        <div style="padding:0.75rem 1.25rem;border-top:1px solid #f0f0f0;">
            <button type="button" onclick="srcApply()" style="width:100%;background:#E61E2D;color:#fff;border:none;border-radius:8px;padding:0.5rem;font-size:0.88rem;font-weight:700;cursor:pointer;"><?= $cL['btn_apply'] ?? 'Aplică' ?></button>
        </div>
    </div>
</div>

<!-- Status Filter Modal -->
<div id="leads-filter-modal" style="display:none;position:fixed;inset:0;z-index:9998;background:rgba(0,0,0,0.35);" onclick="if(event.target===this)leadsFilterClose()">
    <div style="position:absolute;top:50%;left:50%;transform:translate(-50%,-50%);background:#fff;border-radius:14px;width:280px;box-shadow:0 8px 32px rgba(0,0,0,.18);overflow:hidden;">
        <div style="display:flex;align-items:center;justify-content:space-between;padding:1rem 1.25rem 0.75rem;border-bottom:1px solid #f0f0f0;">
            <span style="font-weight:700;font-size:0.95rem;color:#191919;"><?= $cL['filter_title'] ?? 'Filtru Lидов' ?></span>
            <button onclick="leadsFilterClose()" style="background:none;border:none;cursor:pointer;color:#999;font-size:1.1rem;line-height:1;">✕</button>
        </div>
        <div style="padding:1rem 1.25rem;display:flex;flex-direction:column;gap:0.65rem;">
            <?php
            $filter_opts = [
                'active'      => [$cL['filter_active']      ?? 'Activ',        '#22c55e', $status_counts['active']      ?? 0],
                'processed'   => [$cL['filter_processed']   ?? 'În lucru',     '#3b82f6', $status_counts['processed']   ?? 0],
                'unprocessed' => [$cL['filter_unprocessed'] ?? 'Neprelucrat',  '#f97316', $status_counts['unprocessed'] ?? 0],
                'missed'      => [$cL['filter_missed']      ?? 'Ratat',        '#9ca3af', $status_counts['missed']      ?? 0],
                'rot'         => [$cL['filter_rot']         ?? 'Putrezit',     '#ef4444', $status_counts['rot']         ?? 0],
            ];
            foreach ($filter_opts as $fk => [$flabel, $fcolor, $fcount]):
                $checked = in_array($fk, $status_filter) ? 'checked' : '';
            ?>
            <label style="display:flex;align-items:center;gap:0.75rem;cursor:pointer;padding:0.1rem 0;">
                <input type="checkbox" name="lf_<?= $fk ?>" value="1" <?= $checked ?>
                    style="width:18px;height:18px;accent-color:#E61E2D;cursor:pointer;">
                <span style="flex:1;font-size:0.9rem;color:#191919;font-weight:500;"><?= htmlspecialchars($flabel) ?></span>
                <?php if ($fcount > 0): ?>
                <span style="background:#ef4444;color:#fff;border-radius:99px;font-size:0.72rem;font-weight:700;padding:0.1rem 0.5rem;min-width:22px;text-align:center;"><?= $fcount ?></span>
                <?php endif; ?>
            </label>
            <?php endforeach; ?>
        </div>
        <div style="display:flex;gap:0.5rem;padding:0.75rem 1.25rem 1.25rem;">
            <button onclick="leadsFilterReset()" style="flex:1;padding:0.6rem;border:1.5px solid #e5e7eb;background:#fff;border-radius:8px;font-size:0.85rem;font-weight:600;color:#555;cursor:pointer;"><?= $cL['filter_reset'] ?? 'Сбросить' ?></button>
            <button onclick="leadsFilterApply()" style="flex:1;padding:0.6rem;background:#E61E2D;border:none;border-radius:8px;font-size:0.85rem;font-weight:700;color:#fff;cursor:pointer;"><?= $cL['filter_apply'] ?? 'Принять' ?></button>
        </div>
    </div>
</div>

<!-- Lead Actions Modal -->
<div id="leads-more-modal" class="csm-overlay" onclick="if(event.target===this)leadsMoreClose()">
    <div class="csm-modal" style="width:300px;">
        <?php if ($view === 'junk'): ?>
        <div class="csm-header">
            <span class="csm-title"><?= htmlspecialchars($cL['btn_restore_lead'] ?? 'Restaurează lead-ul') ?></span>
            <button class="csm-close" onclick="leadsMoreClose()">✕</button>
        </div>
        <div style="padding:1.25rem 1.4rem 0.5rem;font-size:0.88rem;color:#555;text-align:center;">
            <?= htmlspecialchars($cL['lead_confirm_restore'] ?? 'Restabilești acest lead ca activ?') ?>
        </div>
        <div class="csm-actions" style="flex-direction:column;gap:0.5rem;padding:1rem 1.4rem 1.4rem;">
            <button class="csm-btn-save" onclick="leadsMoreRestore()" style="background:#16a34a;display:flex;align-items:center;gap:0.5rem;justify-content:center;">
                <img src="/content/admin/include/crm/icons/checked.svg" width="18" height="18" style="filter:brightness(0) invert(1);"> <?= htmlspecialchars($cL['btn_restore_lead'] ?? 'Restaurează') ?>
            </button>
        </div>
        <?php else: ?>
        <div class="csm-header">
            <span class="csm-title"><?= htmlspecialchars($cL['lead_actions'] ?? 'Acțiuni lead') ?></span>
            <button class="csm-close" onclick="leadsMoreClose()">✕</button>
        </div>
        <div class="csm-actions" style="flex-direction:column;gap:0.5rem;padding:1rem 1.4rem 1.4rem;">
            <?php if (empty($crm_my_leads)): ?>
            <button class="csm-btn-cancel" onclick="leadsMoreTakeover()" style="display:flex;align-items:center;gap:0.5rem;justify-content:center;">
                <img src="/content/admin/include/crm/icons/manager.svg" width="16" height="16" style="opacity:0.7;"> <?= htmlspecialchars($cL['btn_takeover_lead'] ?? 'Preia lead-ul') ?>
            </button>
            <?php endif; ?>
            <button class="csm-btn-cancel" onclick="leadsMoreSetProcessed()" style="display:flex;align-items:center;gap:0.5rem;justify-content:center;background:#f0f4ff;color:#3b5bdb;border:1px solid #c5d0fa;">
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/></svg>
                <?= htmlspecialchars($cL['filter_processed'] ?? 'În lucru') ?>
            </button>
            <button id="leads-more-btn-to-order" class="csm-btn-cancel" onclick="leadsMoreChangeDept('order')" style="display:flex;align-items:center;gap:0.5rem;justify-content:center;background:#f0fdf4;color:#16a34a;border:1px solid #bbf7d0;">
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M5 12h14"/><path d="M12 5l7 7-7 7"/></svg>
                <?= htmlspecialchars($cL['btn_move_to_order'] ?? 'Aruncă la Comandă') ?>
            </button>
            <button id="leads-more-btn-to-stock" class="csm-btn-cancel" onclick="leadsMoreChangeDept('stock')" style="display:flex;align-items:center;gap:0.5rem;justify-content:center;background:#fff7ed;color:#c2410c;border:1px solid #fed7aa;">
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M19 12H5"/><path d="M12 19l-7-7 7-7"/></svg>
                <?= htmlspecialchars($cL['btn_move_to_stock'] ?? 'Aruncă în Stoc') ?>
            </button>
            <button id="leads-more-btn-to-pruncul" class="csm-btn-cancel" onclick="leadsMoreChangeDept('pruncul')" style="display:flex;align-items:center;gap:0.5rem;justify-content:center;background:#fdf4ff;color:#7e22ce;border:1px solid #e9d5ff;">
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M3 9l9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z"/><polyline points="9 22 9 12 15 12 15 22"/></svg>
                <?= htmlspecialchars($cL['btn_move_to_pruncul'] ?? 'Aruncă la Pruncul') ?>
            </button>
        </div>
        <?php endif; ?>
    </div>
</div>


<script>
(function() {
    var pollTs  = Math.floor(Date.now() / 1000);
    var eventTs = pollTs;
    var seenCallids = {};
    var dept = '<?= addslashes($dept) ?>';

    function pollLeads() {
        fetch('/ajax.php', {
            method: 'POST',
            headers: {'Content-Type': 'application/x-www-form-urlencoded'},
            body: 'tp=adm&pg=crm&fn=poll_leads&dept=' + dept + '&since=' + pollTs
        }).then(r => r.json()).then(d => {
            if (d.ok && d.new_count > 0) {
                showCrmToast('<?= addslashes($cL['tab_leads']) ?>: ' + d.new_count + ' lead-uri noi', 'info', function() { location.reload(); });
            }
            if (d.ts) pollTs = d.ts;
        }).catch(function(){});
    }

    function pollEvents() {
        fetch('/ajax.php', {
            method: 'POST',
            headers: {'Content-Type': 'application/x-www-form-urlencoded'},
            body: 'tp=adm&pg=crm&fn=poll_events&since=' + eventTs
        }).then(r => r.json()).then(d => {
            if (d.ok && d.events) {
                d.events.forEach(function(ev) {
                    if (seenCallids[ev.callid]) return;
                    if (ev.type === 'INCOMING') { seenCallids[ev.callid] = true; showCallPopup(ev); }
                });
            }
            if (d.ts) eventTs = d.ts;
        }).catch(function(){});
    }

    setInterval(pollLeads,  30000);
    setInterval(pollEvents, 5000);
    setTimeout(pollEvents,  1000);
})();

function showCrmToast(msg, type, onClick) {
    var existing = document.getElementById('crm-toast');
    if (existing) existing.remove();
    var div = document.createElement('div');
    div.id = 'crm-toast';
    div.style.cssText = 'position:fixed;top:70px;right:20px;z-index:9999;background:#191919;color:#fff;padding:0.75rem 1.25rem;border-radius:6px;font-size:0.85rem;cursor:pointer;box-shadow:0 4px 16px rgba(0,0,0,0.25);max-width:320px;';
    div.textContent = msg;
    if (onClick) div.addEventListener('click', function() { div.remove(); onClick(); });
    document.body.appendChild(div);
    setTimeout(function() { if (div.parentNode) div.remove(); }, 15000);
}

function showCallPopup(ev) {
    var existing = document.getElementById('crm-call-popup-' + ev.callid);
    if (existing) return;
    var div = document.createElement('div');
    div.id = 'crm-call-popup-' + ev.callid;
    div.style.cssText = 'position:fixed;bottom:24px;right:24px;z-index:9999;background:#fff;border:2px solid #E61E2D;border-radius:8px;padding:1rem 1.25rem;box-shadow:0 8px 32px rgba(0,0,0,0.18);min-width:260px;max-width:320px;';
    var leadLink = ev.lead_url ? '<a href="'+ev.lead_url+'" style="color:#E61E2D;font-weight:700;font-size:0.8rem;">Deschide Lead →</a>' : '';
    div.innerHTML = '<div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:0.5rem;">'
        + '<span style="font-weight:700;color:#191919;font-size:0.9rem;">📞 Apel Incoming</span>'
        + '<span style="cursor:pointer;color:#aaa;font-size:1.1rem;" onclick="this.closest(\'div[id]\').remove()">✕</span>'
        + '</div>'
        + '<div style="font-size:1rem;font-weight:700;color:#E61E2D;margin-bottom:0.3rem;">' + ev.phone + '</div>'
        + (ev.pbx_user ? '<div style="font-size:0.75rem;color:#666;margin-bottom:0.4rem;">Agent: ' + ev.pbx_user + '</div>' : '')
        + leadLink;
    document.body.appendChild(div);
    setTimeout(function() { if (div.parentNode) div.remove(); }, 30000);
}

function crmTakeLead(leadId, btn) {
    if (!confirm('<?= addslashes($cL['confirm_take']) ?>')) return;
    btn.disabled = true;
    fetch('/ajax.php', {
        method: 'POST',
        headers: {'Content-Type': 'application/x-www-form-urlencoded'},
        body: 'tp=adm&pg=crm&fn=take_lead&id=' + leadId
    }).then(r => r.json()).then(d => {
        if (d.ok) location.reload();
        else { btn.disabled = false; alert(d.msg || 'Error'); }
    }).catch(() => { btn.disabled = false; });
}

function crmShowTooltip(e, id) {
    var tip = document.getElementById(id);
    if (!tip) return;
    crmOpenTranscriptModal(tip.textContent || tip.innerText);
}
function crmHideTooltip(id) {}

function leadsFilterOpen() {
    document.getElementById('leads-filter-modal').style.display = '';
}
function leadsFilterClose() {
    document.getElementById('leads-filter-modal').style.display = 'none';
}
function leadsFilterApply() {
    var keys = ['active','processed','unprocessed','missed','rot'];
    var selected = keys.filter(k => document.querySelector('input[name="lf_'+k+'"]')?.checked);
    var url = new URL(window.location.href);
    if (selected.length > 0) url.searchParams.set('sf', selected.join(','));
    else url.searchParams.delete('sf');
    window.location = url.toString();
}
function leadsFilterReset() {
    var url = new URL(window.location.href);
    url.searchParams.delete('sf');
    window.location = url.toString();
}

var _leadsMoreId = 0;
function leadsMoreOpen(leadId, dept) {
    _leadsMoreId = leadId;
    var btnOrder   = document.getElementById('leads-more-btn-to-order');
    var btnStock   = document.getElementById('leads-more-btn-to-stock');
    var btnPruncul = document.getElementById('leads-more-btn-to-pruncul');
    if (btnOrder)   btnOrder.style.display   = dept === 'order'   ? 'none' : 'flex';
    if (btnStock)   btnStock.style.display   = dept === 'stock'   ? 'none' : 'flex';
    if (btnPruncul) btnPruncul.style.display = dept === 'pruncul' ? 'none' : 'flex';
    document.getElementById('leads-more-modal').classList.add('open');
}
function leadsMoreChangeDept(dept) {
    if (!_leadsMoreId) return;
    fetch('/ajax.php', {method:'POST', headers:{'Content-Type':'application/x-www-form-urlencoded'},
        body:'tp=adm&pg=crm&fn=change_dept&id='+_leadsMoreId+'&dept='+dept
    }).then(r=>r.json()).then(d=>{
        if (d.ok) location.reload();
        else alert(d.msg||'Eroare');
    });
}
function leadsMoreClose() {
    document.getElementById('leads-more-modal').classList.remove('open');
    _leadsMoreId = 0;
}
function leadsMoreDelete() {
    if (!_leadsMoreId) return;
    fetch('/ajax.php', {method:'POST', headers:{'Content-Type':'application/x-www-form-urlencoded'},
        body:'tp=adm&pg=crm&fn=delete_lead&id='+_leadsMoreId
    }).then(r=>r.json()).then(d=>{
        if (d.ok) location.reload();
        else alert(d.msg||'Eroare');
    });
}
function leadsMoreTakeover() {
    if (!_leadsMoreId) return;
    fetch('/ajax.php', {method:'POST', headers:{'Content-Type':'application/x-www-form-urlencoded'},
        body:'tp=adm&pg=crm&fn=takeover_lead&id='+_leadsMoreId
    }).then(r=>r.json()).then(d=>{
        if (d.ok) location.reload();
        else alert(d.msg||'Eroare');
    });
}
function leadsMoreSetProcessed() {
    if (!_leadsMoreId) return;
    fetch('/ajax.php', {method:'POST', headers:{'Content-Type':'application/x-www-form-urlencoded'},
        body:'tp=adm&pg=crm&fn=change_status&id='+_leadsMoreId+'&status=processed'
    }).then(r=>r.json()).then(d=>{
        if (d.ok) location.reload();
        else alert(d.msg||'Eroare');
    });
}
function leadsMoreRestore() {
    if (!_leadsMoreId) return;
    fetch('/ajax.php', {method:'POST', headers:{'Content-Type':'application/x-www-form-urlencoded'},
        body:'tp=adm&pg=crm&fn=restore_lead&id='+_leadsMoreId
    }).then(r=>r.json()).then(d=>{
        if (d.ok) location.reload();
        else alert(d.msg||'Eroare');
    });
}
function leadsArchiveDirect(leadId) {
    fetch('/ajax.php', {method:'POST', headers:{'Content-Type':'application/x-www-form-urlencoded'},
        body:'tp=adm&pg=crm&fn=delete_lead&id='+leadId
    }).then(r=>r.json()).then(d=>{
        if (d.ok) location.reload();
        else alert(d.msg||'Eroare');
    });
}
function crmOpenTranscriptModal(text) {
    var overlay = document.getElementById('crm-transcript-modal');
    if (!overlay) {
        overlay = document.createElement('div');
        overlay.id = 'crm-transcript-modal';
        overlay.style.cssText = 'position:fixed;inset:0;z-index:99999;background:rgba(0,0,0,.5);display:flex;align-items:center;justify-content:center;padding:1rem;box-sizing:border-box;';
        overlay.innerHTML = '<div style="background:#1e1e1e;border-radius:12px;max-width:480px;width:100%;max-height:80vh;display:flex;flex-direction:column;box-shadow:0 8px 32px rgba(0,0,0,.5);">'
            + '<div style="flex-shrink:0;display:flex;justify-content:flex-end;padding:0.6rem 0.75rem 0;">'
            + '<button onclick="crmCloseTranscriptModal()" style="background:none;border:none;color:#aaa;font-size:1.4rem;cursor:pointer;line-height:1;padding:0;">✕</button>'
            + '</div>'
            + '<div id="crm-transcript-modal-text" style="flex:1;overflow-y:auto;font-size:0.85rem;color:#f0f0f0;line-height:1.7;white-space:pre-wrap;padding:0.5rem 1.25rem 1.25rem;"></div>'
            + '</div>';
        overlay.addEventListener('click', function(ev){ if(ev.target===overlay) crmCloseTranscriptModal(); });
        document.body.appendChild(overlay);
    }
    document.getElementById('crm-transcript-modal-text').textContent = text;
    overlay.style.display = 'flex';
    var scrollW = window.innerWidth - document.documentElement.clientWidth;
    document.body.style.overflow = 'hidden';
    document.body.style.paddingRight = scrollW + 'px';
}
function crmCloseTranscriptModal() {
    var overlay = document.getElementById('crm-transcript-modal');
    if (overlay) overlay.style.display = 'none';
    document.body.style.overflow = '';
    document.body.style.paddingRight = '';
}

if (window.innerWidth <= 1024) {
    var isJunk = <?= $view === 'junk' ? 'true' : 'false' ?>;
    document.querySelectorAll('#crm-leads-wrap .calls-tbl tr.crm-leads-row').forEach(function(row) {
        var leadId = parseInt(row.getAttribute('data-lead-id')) || 0;
        if (!leadId) return;

        var bg = document.createElement('div');
        if (isJunk) {
            bg.style.cssText = 'position:absolute;top:0;right:0;bottom:0;width:80px;background:#16a34a;border-radius:10px;display:flex;align-items:center;justify-content:center;opacity:0;pointer-events:none;';
            bg.innerHTML = '<svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="#fff" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><path d="M3 12a9 9 0 1 0 9-9 9.75 9.75 0 0 0-6.74 2.74L3 8"/><path d="M3 3v5h5"/></svg>';
        } else {
            bg.style.cssText = 'position:absolute;top:0;right:0;bottom:0;width:80px;background:#ef4444;border-radius:10px;display:flex;align-items:center;justify-content:center;opacity:0;pointer-events:none;';
            bg.innerHTML = '<svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="#fff" stroke-width="2.5" stroke-linecap="round"><polyline points="3 6 5 6 21 6"/><path d="M19 6l-1 14H6L5 6"/><path d="M10 11v6M14 11v6"/><path d="M9 6V4h6v2"/></svg>';
        }
        row.style.position = 'relative';
        row.style.overflow = 'hidden';
        row.appendChild(bg);

        var startX = 0, startY = 0, swiping = false, THRESHOLD = 60;

        row.addEventListener('touchstart', function(e) {
            startX = e.touches[0].clientX;
            startY = e.touches[0].clientY;
            swiping = false;
        }, { passive: true });

        row.addEventListener('touchmove', function(e) {
            var dx = e.touches[0].clientX - startX;
            var dy = e.touches[0].clientY - startY;
            if (Math.abs(dx) > Math.abs(dy) && dx < 0) {
                swiping = true;
                e.preventDefault();
                row.style.transition = 'none';
                row.style.transform = 'translateX(' + Math.max(dx, -80) + 'px)';
                bg.style.opacity = Math.min(Math.abs(dx) / THRESHOLD, 1);
            }
        }, { passive: false });

        row.addEventListener('touchend', function(e) {
            var dx = e.changedTouches[0].clientX - startX;
            if (swiping && dx < -THRESHOLD) {
                row.style.transition = 'transform 0.2s, opacity 0.2s';
                row.style.transform = 'translateX(-100%)';
                row.style.opacity = '0';
                if (isJunk) {
                    setTimeout(function() {
                        fetch('/ajax.php', {method:'POST', headers:{'Content-Type':'application/x-www-form-urlencoded'},
                            body:'tp=adm&pg=crm&fn=restore_lead&id='+leadId
                        }).then(r=>r.json()).then(d=>{ if(d.ok) location.reload(); });
                    }, 200);
                } else {
                    setTimeout(function() { leadsArchiveDirect(leadId); }, 200);
                }
            } else {
                row.style.transition = 'transform 0.2s';
                row.style.transform = 'translateX(0)';
                bg.style.opacity = '0';
            }
            swiping = false;
        }, { passive: true });
    });
}
</script>
