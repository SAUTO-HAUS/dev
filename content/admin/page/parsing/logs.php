<?php defined( '_DOIT' ) or die( 'Restricted access' );

include_once _ADM_PAGE.'/parsing/parsing_lang.php';
$t = $parsing_lang;

// Logs: full-access ids only (see include/parsing_access.php).
require_once(_ADM_INCL.'/parsing_access.php');
if (!parsing_is_full($user_id ?? 0)) {
    echo '<span class="err">'.$t['access_denied'].'</span>';
    return;
}

$runs = [];
try {
    $stmt = $db->prepare('SELECT pr.*, pf.name AS filter_name
        FROM '.$prefx.'_parsing_runs pr
        LEFT JOIN '.$prefx.'_parsing_filters pf ON pf.id = pr.filter_id
        ORDER BY pr.started_at DESC LIMIT 100');
    $stmt->execute();
    $runs = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (Exception $e) {
    
}

$rtrn = '
<link rel="stylesheet" href="/content/admin/page/parsing/parsing.css?v='.filemtime(_ADM_PAGE.'/parsing/parsing.css').'">
<div id="parsing-container">
    <div class="parsing-header">
        <h1>'.$t['page_logs'].'</h1>
    </div>

    <div class="parsing-tabs">
        <a href="/'.$admin_dir.'/parsing/filters" class="tab">'.$t['tab_filters'].'</a>
        <a href="/'.$admin_dir.'/parsing/ctlg" class="tab">'.$t['tab_ctlg'].'</a>
        <a href="/'.$admin_dir.'/parsing/published" class="tab">'.$t['tab_published'].'</a>
        <a href="/'.$admin_dir.'/parsing/settings" class="tab">'.$t['tab_settings'].'</a>
        <a href="/'.$admin_dir.'/parsing/logs" class="tab active">'.$t['tab_logs'].'</a>
        <a href="/'.$admin_dir.'/parsing/favorites" class="tab tab-favorites"><img src="/content/admin/page/parsing/media-parsing/favorite.png" alt=""> '.$t['tab_favorites'].'</a>
    </div>

    <table class="parsing-table parsing-table-cards">
        <thead>
            <tr>
                <th>'.$t['col_filter'].'</th>
                <th>'.$t['col_source'].'</th>
                <th>'.($t['col_run_type'] ?? 'Tip').'</th>
                <th>'.$t['col_started_at'].'</th>
                <th>'.$t['col_found'].'</th>
                <th>'.$t['col_imported'].'</th>
                <th>'.$t['col_skipped'].'</th>
                <th>'.$t['col_status'].'</th>
                <th>'.$t['col_error'].'</th>
            </tr>
        </thead>
        <tbody>';

if (empty($runs)) {
    $rtrn .= '<tr><td colspan="9" class="empty-state">'.$t['empty_logs'].'</td></tr>';
} else {
    foreach ($runs as $r) {
        $statusClass = $r['status'] === 'success' ? 'badge-on' : ($r['status'] === 'failed' ? 'badge-off' : 'badge-warn');
        $statusLabel = $t['status_'.$r['status']] ?? $r['status'];
        $runType = $r['run_type'] ?? 'cron';
        $runTypeLabel = $runType === 'manual'
            ? ($t['run_type_manual'] ?? 'Manual')
            : ($t['run_type_cron'] ?? 'Automat');
        $runTypeClass = $runType === 'manual' ? 'badge-on' : 'badge-warn';
        // filter_id = 0 → ad-hoc direct search (no saved filter).
        $filterLabel = ((int)$r['filter_id'] === 0)
            ? ($t['filter_adhoc'] ?? 'Căutare directă')
            : ($r['filter_name'] ?? '#'.$r['filter_id']);
        $rtrn .= '<tr>
            <td data-label="'.htmlspecialchars($t['col_filter']).'">'.htmlspecialchars($filterLabel).'</td>
            <td data-label="'.htmlspecialchars($t['col_source']).'">'.strtoupper($r['source']).'</td>
            <td data-label="'.htmlspecialchars($t['col_run_type'] ?? 'Tip').'"><span class="badge '.$runTypeClass.'">'.$runTypeLabel.'</span></td>
            <td data-label="'.htmlspecialchars($t['col_started_at']).'">'.date('d.m.Y H:i:s', strtotime($r['started_at'])).'</td>
            <td data-label="'.htmlspecialchars($t['col_found']).'">'.(int)$r['found_count'].'</td>
            <td data-label="'.htmlspecialchars($t['col_imported']).'">'.(int)$r['imported_count'].'</td>
            <td data-label="'.htmlspecialchars($t['col_skipped']).'">'.(int)$r['skipped_count'].'</td>
            <td data-label="'.htmlspecialchars($t['col_status']).'"><span class="badge '.$statusClass.'">'.htmlspecialchars($statusLabel).'</span></td>
            <td class="error-cell" data-label="'.htmlspecialchars($t['col_error']).'">'.(
                !empty($r['error_message'])
                    ? '<span class="error-toggle" data-full="'.htmlspecialchars($r['error_message'], ENT_QUOTES).'" title="'.htmlspecialchars($t['error_click_full'] ?? 'Click pentru mesajul complet').'">'
                        . htmlspecialchars(mb_substr($r['error_message'], 0, 80))
                        . (mb_strlen($r['error_message']) > 80 ? '…' : '')
                      . '</span>'
                    : ''
            ).'</td>
        </tr>';
    }
}

$rtrn .= '
        </tbody>
    </table>

    <!-- Full error message modal -->
    <div id="parsing-error-modal" class="parsing-modal" style="display:none;">
        <div class="parsing-modal-content" style="max-width:640px;">
            <div class="modal-header">
                <h2>'.($t['col_error'] ?? 'Eroare').'</h2>
                <button class="modal-close" onclick="document.getElementById(\'parsing-error-modal\').style.display=\'none\'">✕</button>
            </div>
            <pre id="parsing-error-text" style="white-space:pre-wrap;word-break:break-word;font-size:0.85rem;color:#991b1b;margin:0;padding:14px;background:#fff5f5;border-radius:8px;max-height:60vh;overflow:auto;"></pre>
        </div>
    </div>
</div>
<script>
(function(){
    document.addEventListener("click", function(e){
        var el = e.target.closest(".error-toggle");
        if (!el) return;
        var full = el.getAttribute("data-full") || "";
        document.getElementById("parsing-error-text").textContent = full;
        document.getElementById("parsing-error-modal").style.display = "flex";
    });
    // Close on overlay click.
    var modal = document.getElementById("parsing-error-modal");
    if (modal) modal.addEventListener("click", function(e){ if (e.target === modal) modal.style.display = "none"; });
})();
</script>
';

echo $rtrn;
