<?php defined('_DOIT') or die('Restricted access');

require_once(_ADM_INCL.'/crm/crm_lang.php');

$lang_url       = $_COOKIE['lang'] ?? 'ro';
$admin_dir_name = 'adminsauto';
$base_url       = "/$lang_url/$admin_dir_name/crm/calls";

// Filters
$tab     = in_array($_GET['tab'] ?? '', ['in','missed','out']) ? $_GET['tab'] : 'all';
$period  = in_array($_GET['period'] ?? '', ['1','7','30','90','0']) ? $_GET['period'] : '30';
$q       = trim($_GET['q'] ?? '');
$page    = max(1, (int)($_GET['p'] ?? 1));
$limit   = 50;
$offset  = ($page - 1) * $limit;

// Build WHERE
$where_parts = ['1=1'];
$params      = [];

if ($tab === 'in') {
    $where_parts[] = "c.type = 'in' AND c.status NOT IN ('missed','cancel')";
} elseif ($tab === 'out') {
    $where_parts[] = "c.type = 'out'";
} elseif ($tab === 'missed') {
    $where_parts[] = "c.type = 'in' AND c.status IN ('missed','cancel')";
}

if ($period === '1') {
    $where_parts[] = "DATE(c.start_at) = CURDATE()";
} elseif ((int)$period > 0) {
    $where_parts[] = "c.start_at >= DATE_SUB(NOW(), INTERVAL ? DAY)";
    $params[] = (int)$period;
}

if ($q) {
    $where_parts[] = "(c.phone LIKE ? OR l.client_name LIKE ? OR c.pbx_user LIKE ? OR COALESCE(u.name, u2.name) LIKE ? OR COALESCE(u.crm_phone, u2.crm_phone) LIKE ? OR COALESCE(sc.name, s.name, sd.name) LIKE ?)";
    $params[] = "%$q%";
    $params[] = "%$q%";
    $params[] = "%$q%";
    $params[] = "%$q%";
    $params[] = "%$q%";
    $params[] = "%$q%";
}

$where_sql = implode(' AND ', $where_parts);

// Counts per tab (always full period filter, no tab filter)
$period_sql = $period === '1' ? "AND DATE(c.start_at) = CURDATE()" : ((int)$period > 0 ? "AND c.start_at >= DATE_SUB(NOW(), INTERVAL {$period} DAY)" : '');
$count_joins = "LEFT JOIN {$prefx}_crm_leads l ON l.id = c.lead_id
    LEFT JOIN {$prefx}_crm_sources sc ON sc.id = c.source_id
    LEFT JOIN {$prefx}_crm_sources s  ON s.id = l.source_id
    LEFT JOIN {$prefx}_crm_sources sd ON sd.active = 1 AND REGEXP_REPLACE(REPLACE(sd.phone_number,' ',''), '^(373|0)', '') = c.diversion
    LEFT JOIN {$prefx}_adm_usr u  ON u.pbx_login = c.pbx_user
    LEFT JOIN {$prefx}_adm_usr u2 ON u2.name = c.pbx_user AND u.id IS NULL";
$q_sql    = $q ? "AND (c.phone LIKE ? OR l.client_name LIKE ? OR c.pbx_user LIKE ? OR COALESCE(u.name, u2.name) LIKE ? OR COALESCE(u.crm_phone, u2.crm_phone) LIKE ? OR COALESCE(sc.name, s.name, sd.name) LIKE ?)" : '';
$q_params = $q ? ["%$q%", "%$q%", "%$q%", "%$q%", "%$q%", "%$q%"] : [];

// Filter by department: source matches OR agent's crm_access matches
$dept_sql    = !empty($crm_access) ? "AND (
    COALESCE(sc.department, s.department, sd.department) = ?
    OR COALESCE(u.crm_access, u2.crm_access) = ?
)" : '';
$dept_params = !empty($crm_access) ? [$crm_access, $crm_access] : [];

$s = $db->prepare("SELECT COUNT(*) FROM {$prefx}_crm_call_logs c $count_joins WHERE 1=1 $period_sql $dept_sql $q_sql");
$s->execute(array_merge($dept_params, $q_params)); $count_all = (int)$s->fetchColumn();

$s = $db->prepare("SELECT COUNT(*) FROM {$prefx}_crm_call_logs c $count_joins WHERE c.type='in' AND c.status NOT IN ('missed','cancel') $period_sql $dept_sql $q_sql");
$s->execute(array_merge($dept_params, $q_params)); $count_in = (int)$s->fetchColumn();

$s = $db->prepare("SELECT COUNT(*) FROM {$prefx}_crm_call_logs c $count_joins WHERE c.type='in' AND c.status IN ('missed','cancel') $period_sql $dept_sql $q_sql");
$s->execute(array_merge($dept_params, $q_params)); $count_missed = (int)$s->fetchColumn();

$s = $db->prepare("SELECT COUNT(*) FROM {$prefx}_crm_call_logs c $count_joins WHERE c.type='out' $period_sql $dept_sql $q_sql");
$s->execute(array_merge($dept_params, $q_params)); $count_out = (int)$s->fetchColumn();

$missed_count = $count_missed;

// Total
$cnt_stmt = $db->prepare("SELECT COUNT(*) FROM {$prefx}_crm_call_logs c $count_joins WHERE $where_sql $dept_sql");
$cnt_stmt->execute(array_merge($params, $dept_params));
$total = (int)$cnt_stmt->fetchColumn();
$pages = max(1, ceil($total / $limit));

$tab_counts = ['all' => $count_all, 'in' => $count_in, 'missed' => $count_missed, 'out' => $count_out];

// Fetch calls with source info — prefer call source_id, then lead source, then diversion match
$calls_stmt = $db->prepare("
    SELECT c.*,
           l.client_name,
           COALESCE(sc.name,  s.name,  sd.name)  AS source_name,
           COALESCE(sc.color, s.color, sd.color) AS source_color,
           COALESCE(u.name,  u2.name)  AS agent_name,
           COALESCE(u.crm_phone, u2.crm_phone) AS agent_phone
    FROM {$prefx}_crm_call_logs c
    LEFT JOIN {$prefx}_crm_leads l  ON l.id = c.lead_id
    LEFT JOIN {$prefx}_crm_sources sc ON sc.id = c.source_id
    LEFT JOIN {$prefx}_crm_sources s  ON s.id = l.source_id
    LEFT JOIN {$prefx}_crm_sources sd ON sd.active = 1
        AND REGEXP_REPLACE(REPLACE(sd.phone_number,' ',''), '^(373|0)', '') = c.diversion
    LEFT JOIN {$prefx}_adm_usr u  ON u.pbx_login = c.pbx_user
    LEFT JOIN {$prefx}_adm_usr u2 ON u2.name = c.pbx_user AND u.id IS NULL
    WHERE $where_sql $dept_sql
    ORDER BY c.start_at DESC
    LIMIT $limit OFFSET $offset
");
$calls_stmt->execute(array_merge($params, $dept_params));
$calls = $calls_stmt->fetchAll(PDO::FETCH_OBJ);
?>

<div id="crm-calls-wrap">

    <!-- Header -->
    <div class="calls-header">
        <div class="calls-header-title">
            <span class="calls-header-icon">
                <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <path d="M22 16.92v3a2 2 0 0 1-2.18 2 19.79 19.79 0 0 1-8.63-3.07A19.5 19.5 0 0 1 4.69 12 19.79 19.79 0 0 1 1.61 3.4 2 2 0 0 1 3.6 1.22h3a2 2 0 0 1 2 1.72c.127.96.361 1.903.7 2.81a2 2 0 0 1-.45 2.11L7.91 8.77a16 16 0 0 0 6.29 6.29l.95-.95a2 2 0 0 1 2.11-.45c.907.339 1.85.573 2.81.7A2 2 0 0 1 22 16.92z"/>
                </svg>
            </span>
            <span class="calls-header-text"><?= $cL['call_log_title'] ?></span>
            <button class="calls-settings-btn calls-settings-btn-mobile" onclick="crmOpenCallSettings()" title="<?= htmlspecialchars($cL['call_settings_title']) ?>">
                <img src="/content/admin/include/crm/icons/setting.svg" width="25" height="25">
            </button>
        </div>
    </div>

    <!-- Tabs -->
    <?php
    $tab_items = [
        'all'    => $cL['call_tab_all'],
        'in'     => $cL['call_tab_in'],
        'missed' => $cL['call_tab_missed'],
        'out'    => $cL['call_tab_out'],
    ];
    ?>
    <!-- Tabs + Period dropdown on same row -->
    <form method="GET" action="<?= $base_url ?>" id="calls-filter-form" style="margin:0;">
        <input type="hidden" name="tab" value="<?= htmlspecialchars($tab) ?>">
        <div class="calls-tabs">
            <?php foreach ($tab_items as $tk => $tv): ?>
            <a class="calls-tab <?= $tab===$tk?'active':'' ?>"
               href="<?= $base_url ?>?tab=<?= $tk ?>&period=<?= $period ?>&q=<?= urlencode($q) ?>">
                <?= htmlspecialchars($tv) ?>
                <span class="calls-tab-count"><?= $tab_counts[$tk] ?></span>
            </a>
            <?php endforeach; ?>
            <div class="calls-tabs-spacer"></div>
            <div class="calls-tabs-period">
                <select name="period" class="calls-period-select" onchange="document.getElementById('calls-filter-form').submit()">
                    <option value="1"  <?= $period==='1' ?'selected':'' ?>><?= $cL['call_period_today'] ?></option>
                    <option value="7"  <?= $period==='7' ?'selected':'' ?>><?= $cL['call_period_7'] ?></option>
                    <option value="30" <?= $period==='30'?'selected':'' ?>><?= $cL['call_period_30'] ?></option>
                    <option value="90" <?= $period==='90'?'selected':'' ?>><?= $cL['call_period_90'] ?></option>
                    <option value="0"  <?= $period==='0' ?'selected':'' ?>><?= $cL['call_period_all'] ?></option>
                </select>
            </div>
            <div class="calls-search-wrap">
                <input type="text" id="calls-search" name="q" value="<?= htmlspecialchars($q) ?>" placeholder="<?= htmlspecialchars($cL['call_search_ph']) ?>">
                <?php if ($q): ?>
                <a href="<?= $base_url ?>?tab=<?= $tab ?>&period=<?= $period ?>" class="calls-search-reset">✕</a>
                <?php else: ?>
                <span class="calls-search-ico"><img src="/content/admin/include/crm/icons/search.svg" width="15" height="15"></span>
                <?php endif; ?>
            </div>
        </div>
    </form>
    <script>
    (function(){
        var inp = document.getElementById('calls-search');
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
                    <th><?= $cL['call_col_type'] ?></th>
                    <th><?= $cL['call_col_client'] ?></th>
                    <th><?= $cL['call_col_manager'] ?></th>
                    <th><?= $cL['call_col_source'] ?></th>
                    <th><?= $cL['call_col_time'] ?></th>
                    <th><?= $cL['call_col_audio'] ?></th>
                    <th style="display:table-cell;">
                        <div style="display:flex;align-items:center;justify-content:space-between;width:100%;">
                            <?= $cL['call_col_status'] ?>
                            <button class="calls-settings-btn" onclick="crmOpenCallSettings()" title="<?= htmlspecialchars($cL['call_settings_title']) ?>">
                                <img src="/content/admin/include/crm/icons/setting.svg" width="25" height="25">
                            </button>
                        </div>
                    </th>
                </tr>
            </thead>
            <tbody>
            <?php if (!empty($calls)): ?>
            <?php
            $calls_last_day = '';
            $calls_today     = date('Y-m-d');
            $calls_yesterday = date('Y-m-d', strtotime('-1 day'));
            $calls_per_day = [];
            foreach ($calls as $cl) {
                $d = date('Y-m-d', strtotime($cl->start_at));
                $calls_per_day[$d] = ($calls_per_day[$d] ?? 0) + 1;
            }
            ?>
            <?php foreach ($calls as $c):
                $cs        = $c->status ?? 'missed'; // answered | missed | cancel | busy
                $is_out    = ($c->type === 'out');
                $is_answered = ($cs === 'answered');
                $is_missed   = ($cs === 'missed');
                $is_cancel   = ($cs === 'cancel');
                $is_busy     = ($cs === 'busy');
                $dur_min   = floor($c->duration / 60);
                $dur_str   = $dur_min > 0 ? "{$dur_min} min" : ($c->duration > 0 ? "{$c->duration} sec" : '—');
                $phone_fmt = crm_format_phone($c->phone);
                $src_color = $c->source_color ?: '#888';
                $src_name  = $c->source_name ?: ($cL['source_unknown'] ?? 'Apel direct');
                $row_class = ($is_missed || $is_cancel) ? 'row-missed' : ($is_busy ? 'row-dimmed' : '');
                $call_day = date('Y-m-d', strtotime($c->start_at));
                if ($call_day !== $calls_last_day) {
                    $calls_last_day = $call_day;
                    $day_ts = strtotime($call_day);
                    $dow = (int)date('w', $day_ts);
                    $dom = (int)date('j', $day_ts);
                    $mon = (int)date('n', $day_ts) - 1;
                    $yr2 = date('Y', $day_ts);
                    $day_names   = $cL['day_names']   ?? ['Sun','Mon','Tue','Wed','Thu','Fri','Sat'];
                    $month_names = $cL['month_names'] ?? ['Jan','Feb','Mar','Apr','May','Jun','Jul','Aug','Sep','Oct','Nov','Dec'];
                    if ($call_day === $calls_today)          $day_label = $cL['day_today']     ?? 'Astăzi';
                    elseif ($call_day === $calls_yesterday)  $day_label = $cL['day_yesterday'] ?? 'Ieri';
                    else $day_label = ($day_names[$dow] ?? '') . ', ' . $dom . ' ' . ($month_names[$mon] ?? '') . ($yr2 !== date('Y') ? ' ' . $yr2 : '');
            ?>
            <tr class="crm-calls-day-sep">
                <td colspan="7"><span class="day-label"><?= htmlspecialchars($day_label) ?> <span style="opacity:0.7;font-weight:400;">(<?= $calls_per_day[$call_day] ?? 0 ?>)</span></span></td>
            </tr>
            <?php } ?>
            <tr class="<?= $row_class ?>">

                <!-- Type icon -->
                <td>
                    <?php
                    $icons_base = '/content/admin/include/crm/icons/';
                    if ($is_out):
                        if ($is_answered): ?>
                            <img src="<?= $icons_base ?>phone-outbound-answered.svg" width="22" height="22" title="<?= htmlspecialchars($cL['call_out_answered'] ?? 'Ieșit - Răspuns') ?>">
                        <?php elseif ($is_busy): ?>
                            <img src="<?= $icons_base ?>phone-outbound-busy.svg" width="22" height="22" title="<?= htmlspecialchars($cL['call_out_busy'] ?? 'Ieșit - Ocupat') ?>">
                        <?php else: ?>
                            <img src="<?= $icons_base ?>phone-outbound-missed.svg" width="22" height="22" title="<?= htmlspecialchars($cL['call_out_missed'] ?? 'Ieșit - Nerespuns') ?>">
                        <?php endif;
                    else:
                        if ($is_answered): ?>
                            <img src="<?= $icons_base ?>phone-inbound-answered.svg" width="22" height="22" title="<?= htmlspecialchars($cL['call_answered'] ?? 'Primit - Răspuns') ?>">
                        <?php elseif ($is_busy): ?>
                            <img src="<?= $icons_base ?>phone-inbound-busy.svg" width="22" height="22" title="<?= htmlspecialchars($cL['call_in_busy'] ?? 'Primit - Ocupat') ?>">
                        <?php else: ?>
                            <img src="<?= $icons_base ?>phone-inbound-missed.svg" width="22" height="22" title="<?= htmlspecialchars($cL['call_missed'] ?? 'Primit - Ratat') ?>">
                        <?php endif;
                    endif; ?>
                </td>

                <!-- Client -->
                <td>
                    <?php if (!empty($c->client_name)): ?>
                    <div class="calls-client-name"><?= htmlspecialchars($c->client_name) ?></div>
                    <?php endif; ?>
                    <div style="display:flex;align-items:center;gap:0.4rem;">
                        <div class="calls-client-phone"><?= htmlspecialchars($phone_fmt) ?></div>
                        <?php $viber_phone = preg_replace('/\D/', '', $c->phone); ?>
                        <a href="viber://chat?number=+<?= $viber_phone ?>" title="Deschide în Viber" class="crm-contact-icon">
                            <img src="/content/admin/include/crm/icons/viber.svg" style="width:20px;height:20px;object-fit:contain;display:block;">
                        </a>
                        <a href="https://wa.me/<?= $viber_phone ?>" target="_blank" title="Deschide în WhatsApp" class="crm-contact-icon">
                            <img src="/content/admin/include/crm/icons/whatsapp.svg" style="width:20px;height:20px;object-fit:contain;display:block;">
                        </a>
                    </div>
                </td>

                <!-- Manager -->
                <td style="color:#555;font-size:0.8rem;">
                    <span class="calls-td-manager"><?= htmlspecialchars($c->agent_name ?: ($c->pbx_user ?: '—')) ?></span>
                    <?php if (!empty($c->agent_phone)): ?>
                    <div style="font-size:0.72rem;color:#aaa;margin-top:0.15rem;"><?= htmlspecialchars($c->agent_phone) ?></div>
                    <?php endif; ?>
                </td>

                <!-- Source -->
                <td style="font-size:0.82rem;color:<?= $is_answered?'#333':'#aaa' ?>;">
                    <span class="calls-td-source"><?= htmlspecialchars($src_name) ?></span>
                </td>

                <!-- Time & Duration -->
                <td>
                    <div class="calls-time-date"><?= date('d.m.Y', strtotime($c->start_at)) ?></div>
                    <div class="calls-time-row"><span class="calls-time-main"><?= date('H:i', strtotime($c->start_at)) ?></span> <span class="calls-time-dur">/ <?= $dur_str ?></span></div>
                </td>

                <!-- Audio -->
                <td>
                    <?php if ($c->recording_url && $is_answered):
                        $stt_done   = !empty($c->transcript);
                        $stt_status = $c->stt_status ?? 'none';
                    ?>
                    <div class="calls-audio-cell">
                        <button class="calls-play-btn" onclick="crmPlayAudio('<?= htmlspecialchars($c->recording_url) ?>', this)" title="Play">
                            <img src="/content/admin/include/crm/icons/play.svg" width="14" height="14" class="play-icon">
                        </button>
                        <div class="calls-speed-wrap" data-rate="1">
                            <button class="calls-speed-btn" onclick="crmSetSpeed(this,1)"   style="background:#f5f5f5;border:none;padding:1px 5px;font-size:0.68rem;font-weight:700;color:#333;cursor:pointer;line-height:1.5;">1x</button>
                            <button class="calls-speed-btn" onclick="crmSetSpeed(this,1.5)" style="background:none;border:none;border-left:1px solid #e0e0e0;padding:1px 5px;font-size:0.68rem;font-weight:700;color:#888;cursor:pointer;line-height:1.5;">1.5x</button>
                            <button class="calls-speed-btn" onclick="crmSetSpeed(this,2)"   style="background:none;border:none;border-left:1px solid #e0e0e0;padding:1px 5px;font-size:0.68rem;font-weight:700;color:#888;cursor:pointer;line-height:1.5;">2x</button>
                        </div>
                        <button class="calls-speed-wrap-mobile" data-rate="1" onclick="crmCycleSpeed(this)" style="background:none;border:1px solid #e0e0e0;border-radius:4px;width:36px;height:24px;font-size:0.75rem;font-weight:700;color:#888;cursor:pointer;align-items:center;justify-content:center;flex-shrink:0;padding:0;">1x</button>
                        <?php if ($stt_done): ?>
                        <span class="calls-audio-sep"></span>
                        <span class="calls-txt-btn"
                            onclick="crmShowTooltip(event, 'ctt-<?= $c->id ?>')"
                            style="cursor:pointer;">
                            <img src="/content/admin/include/crm/icons/texts.svg" class="calls-txt-icon">
                        </span>
                        <div id="ctt-<?= $c->id ?>" style="display:none;position:fixed;z-index:9999;background:#1e1e1e;border-radius:8px;padding:0.85rem 1rem;font-size:0.78rem;color:#f5f5f5;max-width:340px;min-width:200px;line-height:1.65;white-space:pre-wrap;box-shadow:0 6px 24px rgba(0,0,0,.4);"><?= htmlspecialchars($c->transcript) ?></div>
                        <?php elseif ($stt_status === 'pending'): ?>
                        <span style="font-size:0.72rem;color:#f97316;">⏳</span>
                        <?php else: ?>
                        <span class="calls-audio-sep"></span>
                        <button class="calls-txt-btn" onclick="crmTranscribeCall(<?= $c->id ?>, this)">
                            <img src="/content/admin/include/crm/icons/texts.svg" class="calls-txt-icon">
                        </button>
                        <?php endif; ?>
                    </div>
                    <?php elseif (!$is_answered): ?>
                    <div class="calls-audio-cell">
                        <button class="calls-play-btn dimmed" disabled>
                            <img src="/content/admin/include/crm/icons/play.svg" width="14" height="14">
                        </button>
                    </div>
                    <?php else: ?>
                    <span style="color:#ccc;font-size:0.75rem;">—</span>
                    <?php endif; ?>
                </td>

                <!-- Status -->
                <td>
                    <?php if ($is_cancel): ?>
                        <span class="calls-status-noanswer"><?= $cL['call_no_answer'] ?></span>
                    <?php elseif ($is_missed): ?>
                        <span class="calls-status-missed"><?= $cL['call_missed'] ?></span>
                    <?php elseif ($is_busy): ?>
                        <span class="calls-status-busy"><?= $cL['call_busy'] ?></span>
                    <?php elseif ($c->lead_id): ?>
                        <a href="/<?= $lang_url ?>/<?= $admin_dir_name ?>/crm/lead?id=<?= $c->lead_id ?>" class="calls-status-lead">
                            <?= $cL['call_lead_created'] ?>
                        </a>
                    <?php else: ?>
                        <a href="#" class="calls-status-create" onclick="crmCallCreateLead(<?= $c->id ?>, this); return false;">
                            <?= $cL['call_create_lead'] ?>
                        </a>
                    <?php endif; ?>
                </td>

            </tr>
            <?php endforeach; ?>
            <?php else: ?>
            <tr><td colspan="7" class="calls-empty">📭 <?= $cL['no_calls'] ?? 'Nu există apeluri în această secțiune.' ?></td></tr>
            <?php endif; ?>
            </tbody>
        </table>
    </div>

    <!-- Pagination -->
    <?php if ($pages > 1): ?>
    <div class="calls-pagination">
        <?php for ($i = 1; $i <= $pages; $i++): ?>
        <a href="<?= $base_url ?>?<?= http_build_query(['tab'=>$tab,'period'=>$period,'q'=>$q,'p'=>$i]) ?>"
           class="calls-page-btn <?= $i===$page?'active':'' ?>"><?= $i ?></a>
        <?php endfor; ?>
        <span class="calls-page-info"><?= number_format($total) ?> total</span>
    </div>
    <?php endif; ?>

</div>

<!-- Call Settings Modal -->
<div id="crm-call-settings-overlay" class="csm-overlay" onclick="if(event.target===this)crmCloseCallSettings()">
    <div class="csm-modal">
        <div class="csm-header">
            <span class="csm-title"><?= htmlspecialchars($cL['call_settings_title']) ?></span>
            <button class="csm-close" onclick="crmCloseCallSettings()">✕</button>
        </div>
        <div class="csm-body">
            <div class="csm-row">
                <div class="csm-row-label">
                    <div class="csm-row-title"><?= htmlspecialchars($cL['call_settings_min_dur_title']) ?></div>
                    <div class="csm-row-sub"><?= htmlspecialchars($cL['call_settings_min_dur_sub']) ?></div>
                </div>
                <input type="number" id="csm-min-dur" class="csm-number-input" value="<?= (int)crm_get_setting($db, $prefx, 'call_min_duration', 10) ?>" min="0" max="300">
            </div>
            <div class="csm-divider"></div>
            <div class="csm-row">
                <div class="csm-row-label">
                    <div class="csm-row-title"><?= htmlspecialchars($cL['call_settings_skip_short_title']) ?></div>
                    <div class="csm-row-sub"><?= htmlspecialchars($cL['call_settings_skip_short_sub']) ?></div>
                </div>
                <label class="csm-toggle">
                    <input type="checkbox" id="csm-skip-short" <?= (int)crm_get_setting($db, $prefx, 'call_min_duration', 10) > 0 ? 'checked' : '' ?>>
                    <span class="csm-toggle-slider"></span>
                </label>
            </div>
            <div class="csm-footer-note"><?= htmlspecialchars($cL['call_settings_note']) ?></div>
        </div>
        <div class="csm-actions">
            <button class="csm-btn-save" onclick="crmSaveCallSettings()"><?= htmlspecialchars($cL['save']) ?></button>
        </div>
    </div>
</div>

<script>
var _crmAudio = null;
var _crmAudioBtn = null;
var _crmSpeedBtn = null;
var _playIcon  = '/content/admin/include/crm/icons/play.svg';
var _pauseIcon = '/content/admin/include/crm/icons/pause.svg';

function crmCycleSpeed(btn) {
    var rates = [1, 1.5, 2];
    var cur = parseFloat(btn.dataset.rate) || 1;
    var next = rates[(rates.indexOf(cur) + 1) % rates.length];
    btn.dataset.rate = next;
    btn.textContent = next + 'x';
    btn.style.color = next === 1 ? '#888' : '#E61E2D';
    btn.style.borderColor = next === 1 ? '#e0e0e0' : '#E61E2D';
    if (_crmAudio) _crmAudio.playbackRate = next;
}

function crmSetSpeed(btn, rate) {
    var wrap = btn.closest('.calls-speed-wrap');
    wrap.querySelectorAll('.calls-speed-btn').forEach(function(b) {
        b.style.background = 'none';
        b.style.color = '#888';
        b.style.borderLeft = '1px solid #e0e0e0';
    });
    btn.style.background = rate === 1 ? '#f5f5f5' : (rate === 1.5 ? 'rgba(230,30,45,0.08)' : 'rgba(230,30,45,0.18)');
    btn.style.color = rate === 1 ? '#333' : (rate === 1.5 ? '#e05060' : '#E61E2D');
    wrap.dataset.rate = rate;
    if (_crmAudio) _crmAudio.playbackRate = rate;
}

function crmPlayAudio(url, btn) {
    var img = btn.querySelector('img');
    var wrap = btn.parentNode.querySelector('.calls-speed-wrap');
    var wrapMobile = btn.parentNode.querySelector('.calls-speed-wrap-mobile');
    var speed = wrap ? parseFloat(wrap.dataset.rate) || 1 : (wrapMobile ? parseFloat(wrapMobile.dataset.rate) || 1 : 1);
    // If same button — toggle pause/play
    if (_crmAudio && _crmAudioBtn === btn) {
        if (_crmAudio.paused) {
            _crmAudio.play();
            if (img) img.src = _pauseIcon;
        } else {
            _crmAudio.pause();
            if (img) img.src = _playIcon;
        }
        return;
    }
    // Stop previous
    if (_crmAudio) {
        _crmAudio.pause();
        if (_crmAudioBtn) {
            var prevImg = _crmAudioBtn.querySelector('img');
            if (prevImg) prevImg.src = _playIcon;
            // Reset speed buttons on previous row
            var prevWrap = _crmAudioBtn.parentNode.querySelector('.calls-speed-wrap');
            if (prevWrap) {
                prevWrap.dataset.rate = 1;
                prevWrap.querySelectorAll('.calls-speed-btn').forEach(function(b, i) {
                    b.style.background = i === 0 ? '#f5f5f5' : 'none';
                    b.style.color = i === 0 ? '#333' : '#888';
                });
            }
            var prevWrapMobile = _crmAudioBtn.parentNode.querySelector('.calls-speed-wrap-mobile');
            if (prevWrapMobile) {
                prevWrapMobile.dataset.rate = 1;
                prevWrapMobile.textContent = '1x';
                prevWrapMobile.style.color = '#888';
                prevWrapMobile.style.borderColor = '#e0e0e0';
            }
        }
    }
    // Reset speed to 1x on the new row
    if (wrap) {
        wrap.dataset.rate = 1;
        wrap.querySelectorAll('.calls-speed-btn').forEach(function(b, i) {
            b.style.background = i === 0 ? '#f5f5f5' : 'none';
            b.style.color = i === 0 ? '#333' : '#888';
        });
    }
    if (wrapMobile) {
        wrapMobile.dataset.rate = 1;
        wrapMobile.textContent = '1x';
        wrapMobile.style.color = '#888';
        wrapMobile.style.borderColor = '#e0e0e0';
    }
    _crmAudio = new Audio(url);
    _crmAudio.playbackRate = 1;
    _crmAudioBtn = btn;
    _crmSpeedBtn = wrap || wrapMobile || null;
    if (img) img.src = _pauseIcon;
    _crmAudio.play();
    _crmAudio.onended = function() {
        if (img) img.src = _playIcon;
        _crmAudio = null;
        _crmAudioBtn = null;
        _crmSpeedBtn = null;
    };
}

function crmShowTooltip(e, id) {
    var tip = document.getElementById(id);
    if (!tip) return;
    crmOpenTranscriptModal(tip.textContent || tip.innerText);
}
function crmHideTooltip(id) { /* handled by modal close */ }
function crmToggleTranscript(id, btn) {
    var box = document.getElementById('ctr-' + id);
    if (!box) return;
    crmOpenTranscriptModal(box.textContent || box.innerText);
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
        overlay.addEventListener('click', function(e){ if(e.target===overlay) crmCloseTranscriptModal(); });
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

function crmTranscribeCall(callId, btn) {
    btn.disabled = true;
    btn.innerHTML = '<img src="/content/admin/include/crm/icons/texts.svg" class="calls-txt-icon" style="opacity:.4;">';
    fetch('/ro/adminsauto/ajax/?tp=adm&pg=crm', {
        method: 'POST',
        headers: {'Content-Type': 'application/x-www-form-urlencoded'},
        body: 'fn=stt_transcribe&call_id=' + callId
    }).then(r => r.json()).then(d => {
        if (d.ok && d.transcript) {
            btn.outerHTML = '<button class="calls-txt-btn" onclick="crmToggleTranscript(' + callId + ', this)"><img src="/content/admin/include/crm/icons/texts.svg" class="calls-txt-icon"></button>'
                + '<div id="ctr-' + callId + '" style="display:none;position:absolute;z-index:10;background:#fff;border:1px solid #e0e0e0;border-radius:6px;padding:0.75rem;font-size:0.75rem;color:#333;max-width:320px;line-height:1.6;white-space:pre-wrap;box-shadow:0 4px 12px rgba(0,0,0,.1);">' + d.transcript.replace(/</g,'&lt;') + '</div>';
        } else {
            btn.disabled = false;
            btn.title = d.msg || 'Eroare';
            btn.innerHTML = '<img src="/content/admin/include/crm/icons/texts.svg" class="calls-txt-icon" style="opacity:.3;">';
        }
    }).catch(() => {
        btn.disabled = false;
        btn.innerHTML = '<img src="/content/admin/include/crm/icons/texts.svg" class="calls-txt-icon" style="opacity:.3;">';
    });
}

function crmOpenCallSettings() {
    document.getElementById('crm-call-settings-overlay').classList.add('open');
}
function crmCloseCallSettings() {
    document.getElementById('crm-call-settings-overlay').classList.remove('open');
}
function crmSaveCallSettings() {
    var minDur = document.getElementById('csm-min-dur').value;
    var skipShort = document.getElementById('csm-skip-short').checked ? '1' : '0';
    var btn = document.querySelector('.csm-btn-save');
    btn.disabled = true;
    fetch('/<?= $lang_url ?>/<?= $admin_dir_name ?>/ajax/?tp=adm&pg=crm', {
        method: 'POST',
        headers: {'Content-Type': 'application/x-www-form-urlencoded'},
        body: 'fn=save_call_settings&call_min_duration=' + encodeURIComponent(minDur) + '&skip_short=' + skipShort
    }).then(r => r.json()).then(d => {
        btn.disabled = false;
        if (d.ok) { crmCloseCallSettings(); }
        else { alert(d.msg || 'Eroare'); }
    }).catch(() => { btn.disabled = false; });
}
// Sync toggle with number input
document.addEventListener('DOMContentLoaded', function() {
    var inp = document.getElementById('csm-min-dur');
    var tog = document.getElementById('csm-skip-short');
    if (!inp || !tog) return;
    tog.addEventListener('change', function() {
        if (!this.checked) inp.value = 0;
        else if (parseInt(inp.value) === 0) inp.value = 10;
    });
    inp.addEventListener('input', function() {
        tog.checked = parseInt(this.value) > 0;
    });
});

function crmCallCreateLead(callId, el) {
    el.style.opacity = '0.5';
    el.style.pointerEvents = 'none';
    fetch('/<?= $lang_url ?>/<?= $admin_dir_name ?>/ajax/?tp=adm&pg=crm', {
        method: 'POST',
        headers: {'Content-Type': 'application/x-www-form-urlencoded'},
        body: 'fn=call_create_lead&call_id=' + callId
    })
    .then(function(r){ return r.json(); })
    .then(function(d){
        if (d.ok && d.lead_id) {
            window.location.href = '/<?= $lang_url ?>/<?= $admin_dir_name ?>/crm/lead?id=' + d.lead_id;
        } else {
            el.style.opacity = '';
            el.style.pointerEvents = '';
            alert(d.msg || 'Eroare la creare lead');
        }
    })
    .catch(function(){
        el.style.opacity = '';
        el.style.pointerEvents = '';
        alert('Eroare de conexiune');
    });
}
</script>
