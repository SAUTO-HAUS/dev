<?php defined('_DOIT') or die('Restricted access');

require_once(_ADM_INCL.'/crm/crm_lang.php');

$lang_url       = $_COOKIE['lang'] ?? 'ro';
$admin_dir_name = 'adminsauto';

$view = $crm_tx_view ?? 'transaction'; // 'transaction' or 'closed'
$dept = in_array($_GET['dept'] ?? '', ['stock','order','pruncul']) ? $_GET['dept'] : 'stock';
if (!empty($crm_access)) $dept = $crm_access;
$q    = trim($_GET['q'] ?? '');

$doc_gr = ($dept === 'order') ? 'ordercars' : 'cars';

$doc_types_transaction = ['con_plata', 'con_arvon'];
$doc_types_closed      = ['vinzare_avans'];
$doc_types = ($view === 'closed') ? $doc_types_closed : $doc_types_transaction;

$ph = implode(',', array_fill(0, count($doc_types), '?'));
$params = array_merge([$doc_gr], $doc_types);

$where = "d.gr = ? AND d.f IN ($ph) AND d.archived = 1 AND d.crm_tracked = 1";
if (!empty($crm_access)) {
    $where .= " AND (
        EXISTS (SELECT 1 FROM {$prefx}_crm_leads l WHERE l.id = d.lead_id AND l.department = ?)
        OR EXISTS (SELECT 1 FROM {$prefx}_adm_usr au WHERE au.id = d.adm AND au.crm_access = ?)
    )";
    $params[] = $crm_access;
    $params[] = $crm_access;
}
if ($q !== '') {
    $where .= " AND (
        u.nm LIKE ?
        OR u.cf_idno LIKE ?
        OR u.phn LIKE ?
        OR a.name LIKE ?
        OR d.inf LIKE ?
        OR d.date LIKE ?
    )";
    $params[] = "%$q%";
    $params[] = "%$q%";
    $params[] = "%$q%";
    $params[] = "%$q%";
    $params[] = "%$q%";
    $params[] = "%$q%";
}

$stmt = $db->prepare("
    SELECT d.id, d.f, d.gr, d.date, d.inf, d.adm, d.owner_adm,
           u.nm AS client_name, u.cf_idno, u.tp AS client_tp, u.phn AS client_phn,
           COALESCE(oa.name, a.name) AS adm_name
    FROM {$prefx}_docs_ctlg d
    LEFT JOIN {$prefx}_docs_u u ON u.id = d.u
    LEFT JOIN {$prefx}_adm_usr a ON a.id = d.adm
    LEFT JOIN {$prefx}_adm_usr oa ON oa.id = d.owner_adm
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
    $cnt_where = "d.gr = ? AND d.f IN ($ph) AND d.archived = 1 AND d.crm_tracked = 1";
    if ($d === 'pruncul') {
        $cnt_where .= " AND (
            EXISTS (SELECT 1 FROM {$prefx}_crm_leads l WHERE l.id = d.lead_id AND l.department = ?)
            OR EXISTS (SELECT 1 FROM {$prefx}_adm_usr au WHERE au.id = d.adm AND au.crm_access = ?)
        )";
        $p2[] = 'pruncul';
        $p2[] = 'pruncul';
    }
    $sc = $db->prepare("SELECT COUNT(*) FROM {$prefx}_docs_ctlg d WHERE $cnt_where");
    $sc->execute($p2);
    $dept_counts[$d] = (int)$sc->fetchColumn();
}

$archive_page = ($view === 'closed') ? 'closed_archive' : 'tx_archive';
$origin_page  = ($view === 'closed') ? 'closed' : 'transaction';
$base_url     = "/$lang_url/$admin_dir_name/crm/$archive_page";

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

$today     = date('Y-m-d');
$yesterday = date('Y-m-d', strtotime('-1 day'));

$docs_per_day = [];
foreach ($docs as $doc) {
    $d = $doc->date ? date('Y-m-d', strtotime($doc->date)) : '0000-00-00';
    $docs_per_day[$d] = ($docs_per_day[$d] ?? 0) + 1;
}

if (!function_exists('tx_parse_inf')) {
    function tx_parse_inf(string $inf): array {
        $r = [];
        foreach (explode('&&', $inf) as $part) {
            $kv = explode('==', $part, 2);
            if (count($kv) === 2) $r[trim($kv[0])] = trim($kv[1]);
        }
        return $r;
    }
}
?>

<div id="crm-leads-wrap">

    <div class="calls-header">
        <div class="calls-header-title">
            <span class="calls-header-icon">
                <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <path d="M21 8v13H3V8"/><path d="M1 3h22v5H1z"/><path d="M10 12h4"/>
                </svg>
            </span>
            <span class="calls-header-text">
                <?= $view === 'closed' ? 'Arhivă Tranzacții Încheiate' : 'Arhivă Tranzacții' ?>
                <?php $total = $dept_counts[$dept] ?? 0; if ($total > 0): ?>
                    <span style="display:inline-flex;align-items:center;justify-content:center;min-width:22px;height:22px;padding:0 6px;border-radius:50px;background:#E61E2D;color:#fff;font-size:0.72rem;font-weight:700;margin-left:6px;"><?= $total ?></span>
                <?php endif; ?>
            </span>
        </div>
        <a href="/<?= $lang_url ?>/<?= $admin_dir_name ?>/crm/<?= $origin_page ?>?dept=<?= $dept ?>"
           style="margin-left:auto;display:flex;align-items:center;gap:0.4rem;font-size:0.82rem;color:#555;text-decoration:none;border:1px solid #e0e0e0;border-radius:6px;padding:0.5rem 0.9rem;background:#fff;">
            ← Înapoi
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
            <div class="calls-search-wrap">
                <input type="text" id="tx-search" value="<?= htmlspecialchars($q) ?>" placeholder="Caută nume, IDNP...">
                <span class="calls-search-ico"><img src="/content/admin/include/crm/icons/search.svg" width="15" height="15"></span>
            </div>
            <?php if ($q): ?>
            <a href="<?= $base_url ?>?dept=<?= $dept ?>" style="border:1px solid #e0e0e0;border-radius:6px;padding:0.65rem 0.9rem;font-size:0.82rem;color:#333;background:#fff;text-decoration:none;">Reset</a>
            <?php endif; ?>
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
                    <th style="width:18%;"><?= $cL['tx_col_client'] ?? 'Nume client' ?></th>
                    <th style="width:12%;"><?= $cL['tx_col_phone'] ?? 'Telefon' ?></th>
                    <th style="width:13%;"><?= $cL['tx_col_idno'] ?? 'CP / CF' ?></th>
                    <th style="width:18%;"><?= $cL['tx_col_car'] ?? 'Marcă, Model' ?></th>
                    <th style="width:16%;"><?= $cL['tx_col_doc_type'] ?? 'Tip document' ?></th>
                    <th style="width:10%;"><?= $cL['tx_col_manager'] ?? 'Manager' ?></th>
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
                    if ($doc_day === $today)         $day_label = $cL['day_today']     ?? 'Astăzi';
                    elseif ($doc_day === $yesterday) $day_label = $cL['day_yesterday'] ?? 'Ieri';
                    else {
                        $dow = (int)date('w', $day_ts);
                        $dom = (int)date('j', $day_ts);
                        $mon = (int)date('n', $day_ts) - 1;
                        $yr2 = date('Y', $day_ts);
                        $day_label = ($day_names[$dow] ?? '') . ', ' . $dom . ' ' . ($month_names[$mon] ?? '') . ($yr2 !== date('Y') ? ' ' . $yr2 : '');
                    }
            ?>
            <tr class="crm-leads-day-sep">
                <td colspan="8"><span class="day-label"><?= htmlspecialchars($day_label) ?> <span style="opacity:0.7;font-weight:400;">(<?= $docs_per_day[$doc_day] ?? 0 ?>)</span></span></td>
            </tr>
            <?php endif; ?>
            <tr class="crm-leads-row" data-doc-id="<?= $doc->id ?>">
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
                <td style="font-size:0.82rem;color:#555;"><?= htmlspecialchars($doc->cf_idno ?: '—') ?></td>
                <td style="font-size:0.82rem;color:#333;"><?= htmlspecialchars($car) ?></td>
                <td>
                    <div style="display:flex;align-items:center;gap:5px;flex-wrap:nowrap;white-space:nowrap;">
                    <span class="crm-source-badge tx-doc-badge" style="background:#4b5563;font-size:0.72rem;">
                        <span class="tx-doc-full"><?= htmlspecialchars($doc_labels[$doc->f] ?? $doc->f) ?></span>
                        <span class="tx-doc-short"><?= htmlspecialchars($doc_labels_short[$doc->f] ?? $doc_labels[$doc->f] ?? $doc->f) ?></span>
                    </span>
                    </div>
                </td>
                <td style="font-size:0.8rem;color:#444;"><?= htmlspecialchars($doc->adm_name ?? '—') ?></td>
                <td>
                    <button type="button"
                        onclick="txUnarchive(<?= $doc->id ?>, this)"
                        class="leads-archive-btn"
                        title="Dezarhivează">
                        <img src="/content/admin/include/crm/icons/back-previous.svg" width="18" height="18">
                    </button>
                </td>
            </tr>
            <?php endforeach; ?>
            <?php else: ?>
            <tr><td colspan="8" class="calls-empty">📭 <?= $cL['no_leads'] ?? 'Nu sunt înregistrări' ?></td></tr>
            <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<script>
function txUnarchive(docId, btn) {
    if (btn) btn.disabled = true;
    fetch('/ajax.php', {
        method: 'POST',
        headers: {'Content-Type': 'application/x-www-form-urlencoded'},
        body: 'tp=adm&pg=crm&fn=unarchive_doc&id=' + docId
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
        bg.style.cssText = 'position:absolute;top:0;right:0;bottom:0;width:80px;background:#16a34a;border-radius:10px;display:flex;align-items:center;justify-content:center;opacity:0;pointer-events:none;';
        bg.innerHTML = '<svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="#fff" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><path d="M3 12a9 9 0 1 0 9-9 9.75 9.75 0 0 0-6.74 2.74L3 8"/><path d="M3 3v5h5"/></svg>';
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
                setTimeout(function() { txUnarchive(docId, null); }, 200);
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
