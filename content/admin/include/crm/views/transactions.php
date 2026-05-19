<?php defined('_DOIT') or die('Restricted access');

require_once(_ADM_INCL.'/crm/crm_lang.php');

$lang_url       = $_COOKIE['lang'] ?? 'ro';
$admin_dir_name = 'adminsauto';

$view   = $crm_tx_view ?? 'transaction'; // 'transaction' or 'closed'
$dept   = in_array($_GET['dept'] ?? '', ['stock','order','pruncul']) ? $_GET['dept'] : 'stock';
if (!empty($crm_access)) $dept = $crm_access;
$q      = trim($_GET['q'] ?? '');
$origin_f = in_array($_GET['origin'] ?? '', ['crm','direct']) ? $_GET['origin'] : '';

$doc_gr = ($dept === 'order') ? 'ordercars' : 'cars';

$doc_types_transaction = ['con_plata', 'con_arvon', 'cesionar', 'act_compensare', 'vinzare_avans'];
$doc_types_closed      = ['vinzare_avans'];
$doc_types = ($view === 'closed') ? $doc_types_closed : $doc_types_transaction;

$doc_labels = [
    'con_plata'      => 'Cont de plată',
    'con_arvon'      => 'Contract de arvună',
    'con_arvon_com'  => 'Arvună la Comandă',
    'vinzare_avans'  => 'Contract V-C (avans)',
    'cesionar'       => 'Anexă (Cesiune drept de plată)',
    'act_compensare' => 'Act de compensare',
];
$doc_labels_short = [
    'con_plata'      => 'Cont plată',
    'con_arvon'      => 'Arvună',
    'con_arvon_com'  => 'Arvună Cmd',
    'vinzare_avans'  => 'V-C avans',
    'cesionar'       => 'Cesiune',
    'act_compensare' => 'Compensare',
];

$ph = implode(',', array_fill(0, count($doc_types), '?'));
$params = array_merge([$doc_gr], $doc_types);

$where = "d.gr = ? AND d.f IN ($ph) AND (d.archived IS NULL OR d.archived = 0) AND d.crm_tracked = 1";
if (!empty($crm_access)) {
    // Restricted user: show only their dept
    $where .= " AND (
        EXISTS (SELECT 1 FROM {$prefx}_crm_leads l WHERE l.id = d.lead_id AND l.department = ?)
        OR EXISTS (SELECT 1 FROM {$prefx}_adm_usr au WHERE au.id = d.adm AND au.crm_access = ?)
    )";
    $params[] = $crm_access;
    $params[] = $crm_access;
} elseif ($dept === 'pruncul') {
    // Full-access user viewing pruncul tab
    $where .= " AND (
        EXISTS (SELECT 1 FROM {$prefx}_crm_leads l WHERE l.id = d.lead_id AND l.department = 'pruncul')
        OR EXISTS (SELECT 1 FROM {$prefx}_adm_usr au WHERE au.id = d.adm AND au.crm_access = 'pruncul')
    )";
} elseif ($dept === 'stock') {
    // Full-access user viewing stock tab: exclude pruncul
    $where .= " AND NOT EXISTS (SELECT 1 FROM {$prefx}_adm_usr au WHERE au.id = d.adm AND au.crm_access = 'pruncul')
        AND NOT EXISTS (SELECT 1 FROM {$prefx}_crm_leads l WHERE l.id = d.lead_id AND l.department = 'pruncul')";
}
if ($view === 'closed') {
    $where .= " AND d.tx_status = 'closed'";
} else {
    $where .= " AND (d.tx_status IS NULL OR d.tx_status = 'transaction')";
}
if ($origin_f === 'crm')    { $where .= " AND d.lead_id IS NOT NULL"; }
elseif ($origin_f === 'direct') { $where .= " AND d.lead_id IS NULL"; }
if ($q !== '') {
    $ql = mb_strtolower($q);
    $normalize = function(string $s): string {
        return strtr(mb_strtolower($s), ['ă'=>'a','â'=>'a','î'=>'i','ș'=>'s','ț'=>'t','ş'=>'s','ţ'=>'t','е'=>'e']);
    };
    $ql_norm = $normalize($ql);
    // Match doc type keys whose label contains the search term
    $doc_type_matches = [];
    foreach ($doc_labels as $key => $label) {
        if (strpos($normalize($label), $ql_norm) !== false || strpos($normalize($doc_labels_short[$key] ?? ''), $ql_norm) !== false || strpos($key, $ql_norm) !== false) {
            $doc_type_matches[] = $key;
        }
    }
    $doc_type_cond = '';
    $doc_type_params = [];
    if (!empty($doc_type_matches)) {
        $dph = implode(',', array_fill(0, count($doc_type_matches), '?'));
        $doc_type_cond = " OR d.f IN ($dph)";
        $doc_type_params = $doc_type_matches;
    }
    $where .= " AND (
        u.nm LIKE ?
        OR u.cf_idno LIKE ?
        OR u.phn LIKE ?
        OR a.name LIKE ?
        OR d.inf LIKE ?
        OR d.date LIKE ?
        $doc_type_cond
    )";
    $params[] = "%$q%";
    $params[] = "%$q%";
    $params[] = "%$q%";
    $params[] = "%$q%";
    $params[] = "%$q%";
    $params[] = "%$q%";
    foreach ($doc_type_params as $v) $params[] = $v;
}

$stmt = $db->prepare("
    SELECT d.id, d.f, d.gr, d.date, d.inf, d.adm, d.owner_adm, d.lead_id,
           d.u AS u_id,
           u.nm AS client_name, u.cf_idno, u.tp AS client_tp, u.phn AS client_phn,
           COALESCE(oa.name, a.name) AS adm_name,
           s.name AS source_name, s.color AS source_color
    FROM {$prefx}_docs_ctlg d
    LEFT JOIN {$prefx}_docs_u u ON u.id = d.u
    LEFT JOIN {$prefx}_adm_usr a ON a.id = d.adm
    LEFT JOIN {$prefx}_adm_usr oa ON oa.id = d.owner_adm
    LEFT JOIN {$prefx}_crm_leads l ON l.id = d.lead_id
    LEFT JOIN {$prefx}_crm_sources s ON s.id = l.source_id
    WHERE $where
    ORDER BY d.id DESC
    LIMIT 500
");
$stmt->execute($params);
$docs = $stmt->fetchAll(PDO::FETCH_OBJ);

// Count per dept
$dept_counts = [];
foreach (['stock' => 'cars', 'order' => 'ordercars', 'pruncul' => 'cars'] as $d => $gr) {
    $p2 = [$gr];
    foreach ($doc_types as $t) $p2[] = $t;
    $count_where = "d.gr = ? AND d.f IN ($ph) AND (d.archived IS NULL OR d.archived = 0) AND d.crm_tracked = 1";
    if ($view === 'closed') {
        $count_where .= " AND d.tx_status = 'closed'";
    } else {
        $count_where .= " AND (d.tx_status IS NULL OR d.tx_status = 'transaction')";
    }
    if ($origin_f === 'crm')    { $count_where .= " AND d.lead_id IS NOT NULL"; }
    elseif ($origin_f === 'direct') { $count_where .= " AND d.lead_id IS NULL"; }
    if ($d === 'pruncul') {
        $count_where .= " AND (
            EXISTS (SELECT 1 FROM {$prefx}_crm_leads l WHERE l.id = d.lead_id AND l.department = 'pruncul')
            OR EXISTS (SELECT 1 FROM {$prefx}_adm_usr au WHERE au.id = d.adm AND au.crm_access = 'pruncul')
        )";
    } elseif ($d === 'stock') {
        $count_where .= " AND NOT EXISTS (SELECT 1 FROM {$prefx}_adm_usr au WHERE au.id = d.adm AND au.crm_access = 'pruncul')
            AND NOT EXISTS (SELECT 1 FROM {$prefx}_crm_leads l WHERE l.id = d.lead_id AND l.department = 'pruncul')";
    }
    $sc = $db->prepare("SELECT COUNT(*) FROM {$prefx}_docs_ctlg d WHERE $count_where");
    $sc->execute($p2);
    $dept_counts[$d] = (int)$sc->fetchColumn();
}

$base_url     = "/$lang_url/$admin_dir_name/crm/" . ($view === 'closed' ? 'closed' : 'transaction');
$archive_url  = "/$lang_url/$admin_dir_name/crm/" . ($view === 'closed' ? 'closed_archive' : 'tx_archive');

// Count archived docs for badge
$arch_p = array_merge([$doc_gr], $doc_types);
$arch_w = "d.gr = ? AND d.f IN ($ph) AND d.archived = 1 AND d.crm_tracked = 1";
if (!empty($crm_access)) {
    $arch_w .= " AND (
        EXISTS (SELECT 1 FROM {$prefx}_crm_leads l WHERE l.id = d.lead_id AND l.department = ?)
        OR EXISTS (SELECT 1 FROM {$prefx}_adm_usr au WHERE au.id = d.adm AND au.crm_access = ?)
    )";
    $arch_p[] = $crm_access;
    $arch_p[] = $crm_access;
}
$arch_sc = $db->prepare("SELECT COUNT(*) FROM {$prefx}_docs_ctlg d WHERE $arch_w");
$arch_sc->execute($arch_p);
$archive_count = (int)$arch_sc->fetchColumn();

$today     = date('Y-m-d');
$yesterday = date('Y-m-d', strtotime('-1 day'));

$docs_per_day = [];
foreach ($docs as $doc) {
    $d = $doc->date ? date('Y-m-d', strtotime($doc->date)) : '0000-00-00';
    $docs_per_day[$d] = ($docs_per_day[$d] ?? 0) + 1;
}

function tx_parse_inf(string $inf): array {
    $r = [];
    foreach (explode('&&', $inf) as $part) {
        $kv = explode('==', $part, 2);
        if (count($kv) === 2) $r[trim($kv[0])] = trim($kv[1]);
    }
    return $r;
}
?>

<div id="crm-leads-wrap">

    <div class="calls-header">
        <div class="calls-header-title">
            <span class="calls-header-icon">
                <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <rect x="2" y="3" width="20" height="14" rx="2"/><path d="M8 21h8M12 17v4"/>
                </svg>
            </span>
            <span class="calls-header-text">
                <?= $view === 'closed' ? ($cL['tab_closed'] ?? 'Tranzacții Încheiate') : ($cL['tab_transactions'] ?? 'Tranzacții') ?>
                <?php $total = $dept_counts[$dept] ?? 0; if ($total > 0): ?>
                    <span style="display:inline-flex;align-items:center;justify-content:center;min-width:22px;height:22px;padding:0 6px;border-radius:50px;background:#E61E2D;color:#fff;font-size:0.72rem;font-weight:700;margin-left:6px;"><?= $total ?></span>
                <?php endif; ?>
            </span>
        </div>
        <a href="<?= $archive_url ?>?dept=<?= $dept ?>"
           style="margin-left:auto;display:flex;align-items:center;gap:0.4rem;font-size:0.82rem;color:#555;text-decoration:none;border:1px solid #e0e0e0;border-radius:6px;padding:0.5rem 0.9rem;background:#fff;">
            <img src="/content/admin/include/crm/icons/archive.svg" width="14" height="14" style="opacity:0.6;">
            <?= $view === 'closed' ? ($cL['tx_archive_closed_btn'] ?? 'Arhivă Tranzacții Încheiate') : ($cL['tx_archive_btn'] ?? 'Arhivă Tranzacții') ?>
            <?php if ($archive_count > 0): ?><span class="calls-tab-count"><?= $archive_count ?></span><?php endif; ?>
        </a>
    </div>

    <!-- Tabs -->
    <div class="calls-tabs">
        <?php if (empty($crm_access)): ?>
        <a class="calls-tab calls-tab-dept <?= $dept==='stock'?'active':'' ?>"
           href="<?= $base_url ?>?dept=stock&q=<?= urlencode($q) ?>">
            <?= $cL['dept_stock'] ?? 'Stocul Nostru' ?>
            <?php if ($dept_counts['stock'] > 0): ?><span class="calls-tab-count"><?= $dept_counts['stock'] ?></span><?php endif; ?>
        </a>
        <a class="calls-tab calls-tab-dept calls-tab-order <?= $dept==='order'?'active':'' ?>"
           href="<?= $base_url ?>?dept=order&q=<?= urlencode($q) ?>">
            <?= $cL['dept_order'] ?? 'Auto la Comandă' ?>
            <?php if ($dept_counts['order'] > 0): ?><span class="calls-tab-count"><?= $dept_counts['order'] ?></span><?php endif; ?>
        </a>
        <a class="calls-tab calls-tab-dept <?= $dept==='pruncul'?'active':'' ?>"
           href="<?= $base_url ?>?dept=pruncul&q=<?= urlencode($q) ?>">
            <?= $cL['dept_pruncul'] ?? 'Filială Pruncul' ?>
            <?php if (($dept_counts['pruncul'] ?? 0) > 0): ?><span class="calls-tab-count"><?= $dept_counts['pruncul'] ?></span><?php endif; ?>
        </a>
        <?php endif; ?>

        <div style="margin-left:auto;display:flex;align-items:center;gap:0.5rem;">
            <?php
            $origin_url = function($o) use ($base_url, $dept, $q) {
                $u = $base_url . '?dept=' . $dept;
                if ($o) $u .= '&origin=' . $o;
                if ($q)  $u .= '&q=' . urlencode($q);
                return $u;
            };
            ?>
            <div class="tx-origin-filter" style="display:flex;border:1px solid #e0e0e0;border-radius:6px;overflow:hidden;font-size:0.78rem;">
                <a href="<?= $origin_url('') ?>" style="padding:0.5rem 0.8rem;text-decoration:none;<?= $origin_f===''?'background:#111;color:#fff;font-weight:700;':'background:#fff;color:#555;' ?>"><?= $cL['tx_origin_all'] ?? 'Toate' ?></a>
                <a href="<?= $origin_url('crm') ?>" style="padding:0.5rem 0.8rem;text-decoration:none;border-left:1px solid #e0e0e0;<?= $origin_f==='crm'?'background:#E61E2D;color:#fff;font-weight:700;':'background:#fff;color:#555;' ?>"><?= $cL['tx_origin_crm'] ?? 'Din CRM' ?></a>
                <a href="<?= $origin_url('direct') ?>" style="padding:0.5rem 0.8rem;text-decoration:none;border-left:1px solid #e0e0e0;<?= $origin_f==='direct'?'background:#111;color:#fff;font-weight:700;':'background:#fff;color:#555;' ?>"><?= $cL['tx_origin_direct'] ?? 'Direct' ?></a>
            </div>
            <div class="calls-search-wrap">
                <input type="text" id="tx-search" value="<?= htmlspecialchars($q) ?>" placeholder="Caută nume, IDNP...">
                <?php if ($q): ?>
                <a href="<?= $base_url ?>?dept=<?= $dept ?>" class="calls-search-reset">✕</a>
                <?php else: ?>
                <span class="calls-search-ico"><img src="/content/admin/include/crm/icons/search.svg" width="15" height="15"></span>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <script>
    (function(){
        var inp = document.getElementById('tx-search');
        if (!inp) return;
        var t = null;
        inp.addEventListener('input', function(){
            clearTimeout(t);
            t = setTimeout(function(){
                var url = new URL(window.location.href);
                var v = inp.value.trim();
                if (v) url.searchParams.set('q', v); else url.searchParams.delete('q');
                window.location.href = url.toString();
            }, 800);
        });
    })();
    </script>

    <!-- Table -->
    <div class="calls-table-wrap">
        <table class="calls-tbl crm-tx-tbl">
            <thead>
                <tr>
                    <th style="width:8%;"><?= $cL['tx_col_date'] ?? 'Data' ?></th>
                    <th style="width:16%;"><?= $cL['tx_col_client'] ?? 'Nume client' ?></th>
                    <th style="width:11%;"><?= $cL['tx_col_phone'] ?? 'Telefon' ?></th>
                    <th style="width:11%;"><?= $cL['tx_col_idno'] ?? 'CP / CF' ?></th>
                    <th style="width:15%;"><?= $cL['tx_col_car'] ?? 'Marcă, Model' ?></th>
                    <th style="width:14%;"><?= $cL['tx_col_doc_type'] ?? 'Tip document' ?></th>
                    <th style="width:9%;"><?= $cL['tx_col_manager'] ?? 'Manager' ?></th>
                    <th style="width:11%;"><?= $cL['col_source'] ?? 'Sursă' ?></th>
                    <th style="width:5%;"></th>
                </tr>
            </thead>
            <tbody>
            <?php if (!empty($docs)): ?>
            <?php
            $last_doc_day = null;
            $day_names   = $cL['day_names']   ?? ['Dum','Lun','Mar','Mie','Joi','Vin','Sâm'];
            $month_names = $cL['month_names'] ?? ['Ian','Feb','Mar','Apr','Mai','Iun','Iul','Aug','Sep','Oct','Nov','Dec'];
            foreach ($docs as $doc):
                $inf = tx_parse_inf($doc->inf ?? '');
                $br  = isset($inf['br']) ? ucwords(strtolower(str_replace('_',' ',$inf['br']))) : '';
                $mo  = isset($inf['mo']) ? ucwords(str_replace('_',' ',$inf['mo'])) : '';
                $car = trim("$br $mo") ?: '—';
                $doc_day  = $doc->date ? date('Y-m-d', strtotime($doc->date)) : '0000-00-00';
                $doc_date = $doc->date ? date('d.m.Y', strtotime($doc->date)) : '—';
                if ($doc_day !== $last_doc_day):
                    $last_doc_day = $doc_day;
                    $day_ts = strtotime($doc_day);
                    if ($doc_day === $today)          $day_label = $cL['day_today']     ?? 'Astăzi';
                    elseif ($doc_day === $yesterday)  $day_label = $cL['day_yesterday'] ?? 'Ieri';
                    else {
                        $dow = (int)date('w', $day_ts);
                        $dom = (int)date('j', $day_ts);
                        $mon = (int)date('n', $day_ts) - 1;
                        $yr2 = date('Y', $day_ts);
                        $day_label = ($day_names[$dow] ?? '') . ', ' . $dom . ' ' . ($month_names[$mon] ?? '') . ($yr2 !== date('Y') ? ' ' . $yr2 : '');
                    }
            ?>
            <tr class="crm-leads-day-sep">
                <td colspan="9"><span class="day-label"><?= htmlspecialchars($day_label) ?> <span style="opacity:0.7;font-weight:400;">(<?= $docs_per_day[$doc_day] ?? 0 ?>)</span></span></td>
            </tr>
            <?php endif; ?>
            <tr class="crm-leads-row" data-doc-id="<?= $doc->id ?>" style="cursor:pointer;" onclick="txViewDoc(event, <?= $doc->id ?>, '<?= htmlspecialchars($doc->f) ?>', '<?= htmlspecialchars($doc->gr) ?>', <?= (int)($doc->u_id ?? 0) ?>, '<?= htmlspecialchars($doc->date ?? '') ?>')">
                <td style="white-space:nowrap;font-size:0.82rem;font-weight:600;color:#191919;"><?= $doc_date ?></td>
                <td>
                    <div style="display:flex;align-items:center;gap:0.4rem;flex-wrap:nowrap;">
                        <span style="font-size:0.88rem;color:#111;font-weight:600;"><?= htmlspecialchars(ucwords(strtolower($doc->client_name ?? '')) ?: '—') ?></span>
                        <?php if ($doc->client_phn): $tx_clean_phone = preg_replace('/\D/', '', $doc->client_phn); ?>
                        <a href="viber://chat?number=+<?= $tx_clean_phone ?>" title="Viber" class="crm-contact-icon">
                            <img src="/content/admin/include/crm/icons/viber.svg" style="width:18px;height:18px;object-fit:contain;display:block;">
                        </a>
                        <a href="https://wa.me/<?= $tx_clean_phone ?>" target="_blank" title="WhatsApp" class="crm-contact-icon">
                            <img src="/content/admin/include/crm/icons/whatsapp.svg" style="width:18px;height:18px;object-fit:contain;display:block;">
                        </a>
                        <?php endif; ?>
                    </div>
                </td>
                <td style="font-size:0.82rem;color:#555;"><?= htmlspecialchars($doc->client_phn ?: '—') ?></td>
                <td style="font-size:0.82rem;color:#555;">
                    <?= htmlspecialchars($doc->cf_idno ?: '—') ?>
                </td>
                <td style="font-size:0.82rem;color:#333;"><?= htmlspecialchars($car) ?></td>
                <td>
                    <div style="display:flex;align-items:center;gap:5px;flex-wrap:nowrap;white-space:nowrap;">
                    <?php if ($doc->lead_id): ?>
                    <a href="/<?= $lang_url ?>/<?= $admin_dir_name ?>/crm/lead?id=<?= (int)$doc->lead_id ?>" style="display:inline-flex;align-items:center;font-size:0.68rem;font-weight:700;color:#E61E2D;background:#fef2f2;border:1px solid #fecaca;border-radius:4px;padding:1px 6px;text-decoration:none;">CRM</a>
                    <?php endif; ?>
                    <span class="crm-source-badge tx-doc-badge" style="background:#4b5563;font-size:0.72rem;">
                        <span class="tx-doc-full"><?= htmlspecialchars($doc_labels[$doc->f] ?? $doc->f) ?></span>
                        <span class="tx-doc-short"><?= htmlspecialchars($doc_labels_short[$doc->f] ?? $doc_labels[$doc->f] ?? $doc->f) ?></span>
                    </span>
                    </div>
                </td>
                <td style="font-size:0.8rem;color:#444;"><?= htmlspecialchars($doc->adm_name ?? '—') ?></td>
                <td>
                    <?php if (!empty($doc->source_name)): ?>
                    <span class="crm-source-badge" style="background:<?= htmlspecialchars($doc->source_color ?: '#888') ?>;font-size:0.72rem;"><?= htmlspecialchars($doc->source_name) ?></span>
                    <?php else: ?>
                    <span class="crm-source-badge" style="background:#6b7280;font-size:0.72rem;"><?= $cL['tx_origin_direct'] ?? 'Direct' ?></span>
                    <?php endif; ?>
                </td>
                <td>
                    <button type="button"
                        onclick="txArchive(<?= $doc->id ?>, this)"
                        class="leads-archive-btn"
                        title="Arhivează">
                        <img src="/content/admin/include/crm/icons/archive.svg" width="18" height="18">
                    </button>
                </td>
            </tr>
            <?php endforeach; ?>
            <?php else: ?>
            <tr><td colspan="9" class="calls-empty">📭 <?= $cL['no_leads'] ?? 'Nu sunt înregistrări' ?></td></tr>
            <?php endif; ?>
            </tbody>
        </table>
    </div>
<form id="tx-view-form" method="POST" target="_blank" action="/content/admin/include/docs_print.php" style="display:none;">
    <input type="hidden" name="fn" value="show_it">
    <input type="hidden" name="doc_view" value="1">
    <input type="hidden" name="doc_f" id="tvf-doc_f">
    <input type="hidden" name="doc_gr" id="tvf-doc_gr">
    <input type="hidden" name="id" id="tvf-id">
    <input type="hidden" name="u_id" id="tvf-u_id">
    <input type="hidden" name="date" id="tvf-date">
</form>
</div>

<script>
function txViewDoc(e, id, f, gr, u_id, date) {
    if (e.target.closest('a, button')) return;
    document.getElementById('tvf-id').value     = id;
    document.getElementById('tvf-doc_f').value  = f;
    document.getElementById('tvf-doc_gr').value = gr;
    document.getElementById('tvf-u_id').value   = u_id;
    document.getElementById('tvf-date').value   = date;
    document.getElementById('tx-view-form').submit();
}
function txArchive(docId, btn) {
    if (btn) btn.disabled = true;
    fetch('/ajax.php', {
        method: 'POST',
        headers: {'Content-Type': 'application/x-www-form-urlencoded'},
        body: 'tp=adm&pg=crm&fn=archive_doc&id=' + docId
    }).then(r => r.json()).then(d => {
        if (d.ok) location.reload();
        else { if (btn) btn.disabled = false; alert(d.msg || 'Eroare'); }
    }).catch(() => { if (btn) btn.disabled = false; });
}

if (window.innerWidth <= 1024) {
    document.querySelectorAll('#crm-leads-wrap .crm-tx-tbl tr.crm-leads-row').forEach(function(row) {
        var docId = parseInt(row.getAttribute('data-doc-id')) || 0;
        if (!docId) return;

        var bg = document.createElement('div');
        bg.style.cssText = 'position:absolute;top:0;right:0;bottom:0;width:80px;background:#ef4444;border-radius:10px;display:flex;align-items:center;justify-content:center;opacity:0;pointer-events:none;';
        bg.innerHTML = '<svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="#fff" stroke-width="2.5" stroke-linecap="round"><polyline points="3 6 5 6 21 6"/><path d="M19 6l-1 14H6L5 6"/><path d="M10 11v6M14 11v6"/><path d="M9 6V4h6v2"/></svg>';
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
                setTimeout(function() { txArchive(docId, null); }, 200);
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
