<?php defined('_DOIT') or die('Restricted access');

require_once(_ADM_INCL.'/crm/crm_lang.php');
require_once(_ADM_INCL.'/crm/crm_inbox_core.php');

$lang_url       = $_COOKIE['lang'] ?? 'ro';
$admin_dir_name = 'adminsauto';

$page    = max(1, (int)($_GET['p'] ?? 1));
$limit   = 40;
$offset  = ($page - 1) * $limit;
$ch_f    = in_array($_GET['ch'] ?? '', ['facebook','instagram','telegram','viber','site','999md']) ? $_GET['ch'] : '';
$show_closed = !empty($_GET['closed']);
$q       = trim($_GET['q'] ?? '');
$pg_f    = trim($_GET['pg'] ?? ''); // page_id filter for 999md accounts

$accounts_999md = [
    'sautohaus_999md' => 'SAUTO-HAUS',
    'regular_999md'   => 'Comerciale',
    'order_999md'     => 'Comanda',
    'korea_999md'     => 'Encars',
];
$accounts_facebook = [
    '725963964220309'   => 'SAUTO',
    '482777831588669'   => 'Vânzări Piața Pruncu',
];
$accounts_telegram = [
    'regular_telegram' => 'AutoMoldova',
    'order_telegram'   => 'AutoimportMD',
];
$accounts_site = [
    'cars'       => 'Cars',
    'ordercars'  => 'Order Cars',
    'order'      => 'Order',
    'credit'     => 'Credit',
    'tradein'    => 'Trade-in',
    'sale'       => 'Sale',
    'tyres'      => 'Tyres',
    'contacts'   => 'Contacts',
    'calculator' => 'Calculator',
];

// page_ids excluded per department (everything not listed is allowed)
$dept_excluded_page_ids = [
    'stock'  => ['order_999md', 'korea_999md', 'order_telegram', '482777831588669', 'ordercars', 'order'],
    'order'  => ['sautohaus_999md', 'regular_telegram', '482777831588669', 'cars'],
    'pruncul'=> ['order_999md', 'korea_999md', 'order_telegram', 'ordercars', 'order', '725963964220309'],
];

// Filter UI account lists based on crm_access
if (!empty($crm_access) && isset($dept_excluded_page_ids[$crm_access])) {
    $excl_keys = $dept_excluded_page_ids[$crm_access];
    $accounts_999md      = array_diff_key($accounts_999md,      array_flip($excl_keys));
    $accounts_facebook   = array_diff_key($accounts_facebook,   array_flip($excl_keys));
    $accounts_telegram   = array_diff_key($accounts_telegram,   array_flip($excl_keys));
    $accounts_site       = array_diff_key($accounts_site,       array_flip($excl_keys));
}


$where   = ['1=1'];
$params  = [];
if ($ch_f) { $where[] = 's.channel = ?'; $params[] = $ch_f; }
if ($pg_f) { $where[] = 's.page_id = ?'; $params[] = $pg_f; }
if (!empty($crm_access) && isset($dept_excluded_page_ids[$crm_access])) {
    $excl = $dept_excluded_page_ids[$crm_access];
    $ph   = implode(',', array_fill(0, count($excl), '?'));
    $where[]  = "s.page_id NOT IN ($ph)";
    $params   = array_merge($params, $excl);
}
$where[] = $show_closed ? "s.status = 'closed'" : "s.status <> 'closed'";
if ($q) {
    $channel_map = [
        '999' => '999md', '999md' => '999md', '999.md' => '999md',
        'fb' => 'facebook', 'facebook' => 'facebook',
        'ig' => 'instagram', 'insta' => 'instagram', 'instagram' => 'instagram',
        'tg' => 'telegram', 'telegram' => 'telegram',
        'viber' => 'viber', 'site' => 'site',
    ];
    $q_lower = mb_strtolower($q);
    $matched_channel = $channel_map[$q_lower] ?? null;

    $account_map = array_merge($accounts_999md, $accounts_facebook, $accounts_telegram, $accounts_site);
    $matched_accounts = [];
    foreach ($account_map as $acc_key => $acc_label) {
        if (stripos($acc_label, $q) !== false || stripos($acc_key, $q) !== false) {
            $matched_accounts[] = $acc_key;
        }
    }

    $cond = [
        's.sender_name LIKE ?', 's.sender_phone LIKE ?', 's.sender_id LIKE ?',
        's.channel LIKE ?', 's.page_id LIKE ?',
        's.id IN (SELECT session_id FROM ' . $prefx . '_crm_inbox_messages WHERE body LIKE ?)',
    ];
    $params[] = "%$q%"; $params[] = "%$q%"; $params[] = "%$q%";
    $params[] = "%$q%"; $params[] = "%$q%"; $params[] = "%$q%";

    if ($matched_channel) { $cond[] = 's.channel = ?'; $params[] = $matched_channel; }
    if ($matched_accounts) {
        $ph = implode(',', array_fill(0, count($matched_accounts), '?'));
        $cond[] = 's.page_id IN (' . $ph . ')';
        foreach ($matched_accounts as $a) $params[] = $a;
    }

    $where[] = '(' . implode(' OR ', $cond) . ')';
}
$where_sql = implode(' AND ', $where);

$cnt = $db->prepare("SELECT COUNT(*) FROM {$prefx}_crm_inbox_sessions s WHERE $where_sql");
$cnt->execute($params);
$total = (int)$cnt->fetchColumn();
$pages = max(1, ceil($total / $limit));

// Count archived (closed) sessions for badge
$arch_where = ["s.status = 'closed'"];
$arch_params = [];
if (!empty($crm_access) && isset($dept_excluded_page_ids[$crm_access])) {
    $excl2 = $dept_excluded_page_ids[$crm_access];
    $ph2   = implode(',', array_fill(0, count($excl2), '?'));
    $arch_where[]  = "s.page_id NOT IN ($ph2)";
    $arch_params   = $excl2;
}
$cnt_arch = $db->prepare("SELECT COUNT(*) FROM {$prefx}_crm_inbox_sessions s WHERE " . implode(' AND ', $arch_where));
$cnt_arch->execute($arch_params);
$total_archived = (int)$cnt_arch->fetchColumn();

$stmt = $db->prepare("
    SELECT s.*,
           l.phone AS lead_phone, l.client_name AS lead_name, l.status AS lead_status,
           (SELECT body FROM {$prefx}_crm_inbox_messages WHERE session_id=s.id ORDER BY created_at DESC LIMIT 1) AS last_msg,
           (SELECT created_at FROM {$prefx}_crm_inbox_messages WHERE session_id=s.id ORDER BY created_at DESC LIMIT 1) AS last_msg_at,
           (SELECT COUNT(*) FROM {$prefx}_crm_inbox_messages WHERE session_id=s.id AND direction='in') AS msg_count
    FROM {$prefx}_crm_inbox_sessions s
    LEFT JOIN {$prefx}_crm_leads l ON l.id = s.lead_id
    WHERE $where_sql
    ORDER BY COALESCE(
        (SELECT created_at FROM {$prefx}_crm_inbox_messages WHERE session_id=s.id ORDER BY created_at DESC LIMIT 1),
        s.updated_at
    ) DESC
    LIMIT $limit OFFSET $offset
");
$stmt->execute($params);
$sessions = $stmt->fetchAll(PDO::FETCH_OBJ);

$base_url = "/$lang_url/$admin_dir_name/crm/inbox";

$channel_icons = [
    '999md'     => ['icon' => '9', 'color' => '#FF6B00', 'label' => '999.md',    'svg' => '999.svg'],
    'telegram'  => ['icon' => '✈', 'color' => '#229ED9', 'label' => 'Telegram',  'svg' => 'telegram.svg'],
    'facebook'  => ['icon' => 'f', 'color' => '#1877F2', 'label' => 'Facebook',  'svg' => 'facebook.svg'],
    'instagram' => ['icon' => '📷', 'color' => '#E1306C', 'label' => 'Instagram', 'svg' => 'instagram.svg'],
    'site'      => ['icon' => '🌐', 'color' => '#E61E2D', 'label' => 'Site',     'svg' => 'sauto.png'],
    'viber'     => ['icon' => 'V', 'color' => '#7360F2', 'label' => 'Viber',     'svg' => 'viber.svg'],
    'other'     => ['icon' => '?', 'color' => '#888',    'label' => 'Altul',     'svg' => ''],
];
?>


<div id="crm-inbox-wrap">
    <div class="calls-header">
        <div class="calls-header-title">
            <span class="calls-header-icon">
                <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z"></path>
                </svg>
            </span>
            <span class="calls-header-text"><?= $show_closed ? ($cL['inbox_archive_title'] ?? 'Arhivă Mesaje') : $cL['inbox_title'] ?></span>
            <?php if ($total > 0): ?>
            <span style="display:inline-flex;align-items:center;justify-content:center;min-width:22px;height:22px;padding:0 6px;border-radius:50px;background:#E61E2D;color:#fff;font-size:0.72rem;font-weight:700;margin-left:6px;"><?= number_format($total) ?></span>
            <?php endif; ?>
        </div>
    </div>

    <div class="crm-filters">
        <div class="calls-search-wrap">
            <input type="text" id="inbox-search" value="<?= htmlspecialchars($q) ?>" placeholder="<?= htmlspecialchars($cL['inbox_search_ph']) ?>" style="padding-right:2rem;">
            <?php if ($q || $ch_f): ?>
            <a href="<?= $base_url ?><?= $show_closed ? '?closed=1' : '' ?>" class="calls-search-reset">✕</a>
            <?php else: ?>
            <span class="calls-search-ico"><img src="/content/admin/include/crm/icons/search.svg" width="15" height="15"></span>
            <?php endif; ?>
        </div>
        <a href="<?= $base_url ?><?= $show_closed ? '' : '?closed=1' ?>"
           class="crm-btn <?= $show_closed ? 'crm-btn-dark' : 'crm-btn-outline' ?>"
           style="margin-left:auto;display:inline-flex;align-items:center;gap:0.3rem;padding-top:0.65rem;padding-bottom:0.65rem;">
            <?php if ($show_closed): ?>
                <span style="display:inline-flex;align-items:center;"><svg width="14" height="14" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg"><path fill-rule="evenodd" clip-rule="evenodd" d="M6.3508 12.7499L11.2096 17.4615L10.1654 18.5383L3.42264 11.9999L10.1654 5.46148L11.2096 6.53833L6.3508 11.2499L21 11.2499L21 12.7499L6.3508 12.7499Z" fill="currentColor"/></svg></span><?= $cL['inbox_active'] ?>
            <?php else: ?>
                <span style="display:inline-flex;align-items:center;"><svg width="14" height="14" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg"><path d="M9 12C9 11.5341 9 11.3011 9.07612 11.1173C9.17761 10.8723 9.37229 10.6776 9.61732 10.5761C9.80109 10.5 10.0341 10.5 10.5 10.5H13.5C13.9659 10.5 14.1989 10.5 14.3827 10.5761C14.6277 10.6776 14.8224 10.8723 14.9239 11.1173C15 11.3011 15 11.5341 15 12C15 12.4659 15 12.6989 14.9239 12.8827C14.8224 13.1277 14.6277 13.3224 14.3827 13.4239C14.1989 13.5 13.9659 13.5 13.5 13.5H10.5C10.0341 13.5 9.80109 13.5 9.61732 13.4239C9.37229 13.3224 9.17761 13.1277 9.07612 12.8827C9 12.6989 9 12.4659 9 12Z" stroke="currentColor" stroke-width="1.5"/><path d="M20.5 7V13C20.5 16.7712 20.5 18.6569 19.3284 19.8284C18.1569 21 16.2712 21 12.5 21H11.5M3.5 7V13C3.5 16.7712 3.5 18.6569 4.67157 19.8284C5.37634 20.5332 6.3395 20.814 7.81608 20.9259" stroke="currentColor" stroke-width="1.5" stroke-linecap="round"/><path d="M12 3H4C3.05719 3 2.58579 3 2.29289 3.29289C2 3.58579 2 4.05719 2 5C2 5.94281 2 6.41421 2.29289 6.70711C2.58579 7 3.05719 7 4 7H20C20.9428 7 21.4142 7 21.7071 6.70711C22 6.41421 22 5.94281 22 5C22 4.05719 22 3.58579 21.7071 3.29289C21.4142 3 20.9428 3 20 3H16" stroke="currentColor" stroke-width="1.5" stroke-linecap="round"/></svg></span>
                <?= $cL['inbox_archive'] ?>
                <?php if ($total_archived > 0): ?><span class="calls-tab-count"><?= $total_archived ?></span><?php endif; ?>
            <?php endif; ?>
        </a>
    </div>
    <script>
    (function(){
        var inp = document.getElementById('inbox-search');
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

    <!-- Channel filter icons -->
    <div class="inbox-channels-bar" style="display:flex;gap:0.5rem;margin-bottom:1rem;flex-wrap:wrap;align-items:center;">
        <a href="<?= $base_url ?>?<?= http_build_query(['q'=>$q,'ch'=>'','closed'=>$show_closed?1:null]) ?>"
           style="display:inline-flex;align-items:center;gap:0.3rem;padding:0.3rem 0.75rem;border-radius:20px;font-size:0.75rem;font-weight:600;text-decoration:none;border:2px solid <?= $ch_f===''?'#222':'#ddd' ?>;background:<?= $ch_f===''?'#222':'#fff' ?>;color:<?= $ch_f===''?'#fff':'#555' ?>;">
            <?= $cL['call_tab_all'] ?? 'Toate' ?>
        </a>
        <?php foreach ($channel_icons as $ck => $cv):
            if ($ck === 'other') continue;
            $active = $ch_f === $ck;
            $icons_path = '/content/admin/include/crm/icons/';
        ?>
        <?php
        $submenus = [
            '999md'    => ['id' => 'submenu-999',      'color' => '#FF6B00', 'accounts' => $accounts_999md],
            'facebook' => ['id' => 'submenu-facebook',  'color' => '#1877F2', 'accounts' => $accounts_facebook],
            'telegram' => ['id' => 'submenu-telegram',  'color' => '#229ED9', 'accounts' => $accounts_telegram],
            'site'     => ['id' => 'submenu-site',      'color' => '#E61E2D', 'accounts' => $accounts_site],
        ];
        if (isset($submenus[$ck])): $sub = $submenus[$ck];
        $single_acc_key = count($sub['accounts']) === 1 ? array_key_first($sub['accounts']) : null;
        ?>
        <div style="position:relative;display:inline-block;">
            <a href="<?= $base_url ?>?<?= http_build_query(['q'=>$q,'ch'=>$ck,'pg'=>$single_acc_key??'','closed'=>$show_closed?1:null]) ?>"
               title="<?= $cv['label'] ?>"
               style="display:inline-flex;align-items:center;justify-content:center;width:38px;height:38px;border-radius:50%;background:<?= $active ? $cv['color'].'22' : '#f3f4f6' ?>;text-decoration:none;border:2px solid <?= $active ? $cv['color'] : '#e5e7eb' ?>;transition:all 0.15s;"
               <?php if (!$single_acc_key): ?>
               onmouseenter="document.getElementById('<?= $sub['id'] ?>').style.display='block'"
               onmouseleave="setTimeout(()=>{var el=document.getElementById('<?= $sub['id'] ?>');if(el&&!el.matches(':hover'))el.style.display='none'},200)"
               <?php endif; ?>>
                <?php if ($cv['svg']): ?>
                <img src="<?= $icons_path . $cv['svg'] ?>" width="22" height="22" style="object-fit:contain;">
                <?php else: ?>
                <span style="font-size:0.85rem;font-weight:700;color:<?= $cv['color'] ?>;"><?= $cv['icon'] ?></span>
                <?php endif; ?>
            </a>
            <?php if (!$single_acc_key): ?>
            <div id="<?= $sub['id'] ?>"
                 style="display:none;position:absolute;top:42px;left:0;background:#fff;border:1px solid #e5e7eb;border-radius:8px;box-shadow:0 4px 12px rgba(0,0,0,0.1);z-index:100;min-width:160px;padding:0.4rem 0;"
                 onmouseenter="this.style.display='block'"
                 onmouseleave="this.style.display='none'">
                <?php foreach ($sub['accounts'] as $acc_key => $acc_label): ?>
                <a href="<?= $base_url ?>?<?= http_build_query(['q'=>$q,'ch'=>$ck,'pg'=>$acc_key,'closed'=>$show_closed?1:null]) ?>"
                   style="display:block;padding:0.4rem 0.9rem;font-size:0.78rem;color:<?= $pg_f===$acc_key?$sub['color']:'#333' ?>;font-weight:<?= $pg_f===$acc_key?'700':'400' ?>;text-decoration:none;white-space:nowrap;"
                   onmouseenter="this.style.background='#f9fafb'" onmouseleave="this.style.background=''">
                    <?= htmlspecialchars($acc_label) ?>
                </a>
                <?php endforeach; ?>
            </div>
            <?php endif; ?>
        </div>
        <?php else: ?>
        <a href="<?= $base_url ?>?<?= http_build_query(['q'=>$q,'ch'=>$ck,'closed'=>$show_closed?1:null]) ?>"
           title="<?= $cv['label'] ?>"
           style="display:inline-flex;align-items:center;justify-content:center;width:38px;height:38px;border-radius:50%;background:<?= $active ? $cv['color'].'22' : '#f3f4f6' ?>;text-decoration:none;border:2px solid <?= $active ? $cv['color'] : '#e5e7eb' ?>;transition:all 0.15s;">
            <?php if ($cv['svg']): ?>
            <img src="<?= $icons_path . $cv['svg'] ?>" width="22" height="22" style="object-fit:contain;">
            <?php else: ?>
            <span style="font-size:0.85rem;font-weight:700;color:<?= $cv['color'] ?>;"><?= $cv['icon'] ?></span>
            <?php endif; ?>
        </a>
        <?php endif; ?>
        <?php endforeach; ?>
    </div>
    <script>
    (function(){
        if (window.innerWidth > 1024) return;
        var bars = document.querySelectorAll('.inbox-channels-bar [id^="submenu-"]');
        bars.forEach(function(sub) {
            var wrap = sub.parentElement;
            var link = wrap.querySelector('a');
            if (!link) return;
            var href = link.getAttribute('href');
            var color = link.style.borderColor || '#333';
            // Dezactiveaza hover
            link.removeAttribute('onmouseenter');
            link.removeAttribute('onmouseleave');
            sub.removeAttribute('onmouseenter');
            sub.removeAttribute('onmouseleave');
            // Adauga link "Toate" in submeniu
            var allLink = document.createElement('a');
            allLink.href = href;
            allLink.style.cssText = 'display:block;padding:0.4rem 0.9rem;font-size:0.78rem;color:#333;font-weight:700;text-decoration:none;white-space:nowrap;border-bottom:1px solid #f0f0f0;';
            allLink.textContent = <?= json_encode($cL['call_tab_all'] ?? 'Toate') ?>;
            sub.insertBefore(allLink, sub.firstChild);
            // Touch/click pe icon deschide submeniu
            link.addEventListener('touchend', function(e) {
                e.preventDefault();
                var isOpen = sub.style.display === 'block';
                document.querySelectorAll('.inbox-channels-bar [id^="submenu-"]').forEach(function(s){ s.style.display='none'; });
                if (!isOpen) sub.style.display = 'block';
            });
            // Click in afara inchide
            document.addEventListener('touchend', function(e){
                if (!wrap.contains(e.target)) sub.style.display = 'none';
            });
        });
    })();
    </script>

    <div class="calls-table-wrap">
        <?php if (!empty($sessions)): ?>
        <table class="calls-tbl">
            <thead>
                <tr>
                    <th><?= $cL['inbox_col_channel'] ?></th>
                    <th><?= $cL['inbox_col_sender'] ?></th>
                    <th><?= $cL['inbox_col_last_msg'] ?></th>
                    <th><?= $cL['inbox_col_date'] ?></th>
                    <th><?= $cL['inbox_col_status'] ?></th>
                    <th><?= $cL['inbox_col_lead'] ?></th>
                    <th style="width:40px;"></th>
                </tr>
            </thead>
            <tbody>
            <?php
            $last_day    = null;
            $today       = date('Y-m-d');
            $yesterday   = date('Y-m-d', strtotime('-1 day'));
            $day_names   = $cL['day_names']   ?? ['Sun','Mon','Tue','Wed','Thu','Fri','Sat'];
            $month_names = $cL['month_names'] ?? ['Jan','Feb','Mar','Apr','May','Jun','Jul','Aug','Sep','Oct','Nov','Dec'];
            foreach ($sessions as $s):
                $ch_info  = $channel_icons[$s->channel] ?? $channel_icons['other'];
                $chat_url = "/$lang_url/$admin_dir_name/crm/inbox_chat?sid={$s->id}";
                $sess_day = date('Y-m-d', strtotime($s->last_msg_at ?: $s->updated_at));
                if ($sess_day !== $last_day) {
                    $last_day = $sess_day;
                    $day_ts = strtotime($sess_day);
                    $dow = (int)date('w', $day_ts);
                    $dom = (int)date('j', $day_ts);
                    $mon = (int)date('n', $day_ts) - 1;
                    $yr2 = date('Y', $day_ts);
                    if ($sess_day === $today)          $day_label = $cL['day_today']     ?? 'Astăzi';
                    elseif ($sess_day === $yesterday)  $day_label = $cL['day_yesterday'] ?? 'Ieri';
                    else $day_label = ($day_names[$dow] ?? '') . ', ' . $dom . ' ' . ($month_names[$mon] ?? '') . ($yr2 !== date('Y') ? ' ' . $yr2 : '');
            ?>
            <tr class="crm-inbox-day-sep">
                <td colspan="7"><span class="day-label"><?= htmlspecialchars($day_label) ?></span></td>
            </tr>
            <?php } ?>
            <?php $is_unread = (int)$s->unread_count > 0; ?>
            <tr onclick="window.location='<?= $chat_url ?>'" style="cursor:pointer;<?= $is_unread ? 'background:#fff9f9;' : '' ?>" data-lead-id="<?= (int)$s->lead_id ?>" data-session-id="<?= (int)$s->id ?>">
                <td>
                    <div style="display:inline-flex;align-items:center;gap:0.4rem;">
                        <span style="display:inline-flex;align-items:center;justify-content:center;width:32px;height:32px;border-radius:50%;background:<?= $ch_info['color'].'22' ?>;border:2px solid <?= $ch_info['color'] ?>;flex-shrink:0;">
                            <?php if (!empty($ch_info['svg'])): ?>
                            <img src="/content/admin/include/crm/icons/<?= $ch_info['svg'] ?>" width="18" height="18" style="object-fit:contain;">
                            <?php else: ?>
                            <span style="font-size:0.75rem;font-weight:700;color:<?= $ch_info['color'] ?>;"><?= $ch_info['icon'] ?></span>
                            <?php endif; ?>
                        </span>
                        <div>
                            <div style="font-size:0.8rem;color:#555;font-weight:600;"><?= $ch_info['label'] ?></div>
                            <?php
                            $all_page_labels = array_merge($accounts_999md, $accounts_facebook, $accounts_telegram, $accounts_site);
                            $sub_label = $all_page_labels[$s->page_id] ?? null;
                            if ($sub_label): ?>
                            <div style="font-size:0.65rem;color:#aaa;"><?= htmlspecialchars($sub_label) ?></div>
                            <?php endif; ?>
                            <?php if ($s->channel === '999md' && !empty($s->advert_title)): ?>
                            <div style="font-size:0.65rem;color:#888;margin-top:1px;max-width:120px;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;" title="<?= htmlspecialchars($s->advert_title) ?>">
                                <?= htmlspecialchars($s->advert_title) ?>
                            </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </td>
                <td>
                    <div style="display:flex;align-items:center;gap:0.4rem;">
                        <span class="calls-client-phone" style="font-size:0.88rem;<?= $is_unread ? 'font-weight:700;' : '' ?>"><?= htmlspecialchars($s->sender_name ?: $s->sender_id) ?></span>
                        <?php if ($is_unread): ?>
                        <span class="inbox-unread-badge" style="background:#E61E2D;color:#fff;font-size:0.65rem;font-weight:700;padding:0.1rem 0.4rem;border-radius:20px;flex-shrink:0;"><?= (int)$s->unread_count ?></span>
                        <?php endif; ?>
                    </div>
                    <?php if ($s->sender_phone): ?>
                    <div class="calls-client-name"><?= htmlspecialchars($s->sender_phone) ?></div>
                    <?php endif; ?>
                </td>
                <td>
                    <div style="color:#555;font-size:0.82rem;max-width:320px;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;"><?= htmlspecialchars(mb_substr($s->last_msg ?? '—', 0, 80)) ?></div>
                    <div style="font-size:0.7rem;color:#bbb;margin-top:0.1rem;"><?= (int)$s->msg_count ?> <?= $cL['inbox_msg_count'] ?></div>
                </td>
                <td>
                    <div class="calls-time-main"><?= $s->last_msg_at ? date('H:i', strtotime($s->last_msg_at)) : '—' ?></div>
                    <div class="calls-time-dur"><?= $s->last_msg_at ? date('d.m', strtotime($s->last_msg_at)) : '' ?></div>
                </td>
                <td>
                    <?php if ($s->status === 'ai'): ?>
                    <span style="display:inline-flex;align-items:center;gap:0.3rem;background:#f97316;color:#fff;padding:0.2rem 0.5rem;border-radius:4px;font-size:0.7rem;font-weight:700;">
                        <img src="/content/admin/include/crm/icons/bot.svg" width="13" height="13" style="filter:brightness(0) invert(1);"> <?= $cL['inbox_status_ai'] ?>
                    </span>
                    <?php elseif ($s->status === 'active'): ?>
                    <span style="display:inline-flex;align-items:center;gap:0.3rem;background:#22c55e;color:#fff;padding:0.2rem 0.5rem;border-radius:4px;font-size:0.7rem;font-weight:700;">
                        <img src="/content/admin/include/crm/icons/manager.svg" width="13" height="13" style="filter:brightness(0) invert(1);"> <?= $cL['inbox_status_manager'] ?>
                    </span>
                    <?php else: ?>
                    <span style="background:#6b7280;color:#fff;padding:0.15rem 0.5rem;border-radius:3px;font-size:0.7rem;font-weight:700;display:inline-flex;align-items:center;gap:0.3rem;">
                        <img src="/content/admin/include/crm/icons/archive.svg" width="13" height="13" style="filter:brightness(0) invert(1);flex-shrink:0;"> <?= $cL['inbox_status_closed'] ?>
                    </span>
                    <?php endif; ?>
                </td>
                <td>
                    <?php if ($s->lead_id): ?>
                    <a href="/<?= $lang_url ?>/<?= $admin_dir_name ?>/crm/lead?id=<?= $s->lead_id ?>"
                       onclick="event.stopPropagation()"
                       style="text-decoration:none;display:inline-flex;align-items:center;gap:0.35rem;color:#191919;">
                        <span style="background:#E61E2D;color:#fff;font-size:0.72rem;font-weight:700;padding:0.15rem 0.45rem;border-radius:4px;flex-shrink:0;">#<?= $s->lead_id ?></span>
                        <?php if ($s->lead_name): ?><span style="font-size:0.82rem;color:#444;font-weight:600;"><?= htmlspecialchars($s->lead_name) ?></span><?php endif; ?>
                    </a>
                    <?php else: ?>
                    <span style="color:#ccc;font-size:0.75rem;">—</span>
                    <?php endif; ?>
                </td>
                <td onclick="event.stopPropagation()">
                    <?php if (!$show_closed && !(int)$s->unread_count): ?>
                    <button type="button" onclick="inboxArchiveLead(<?= (int)$s->lead_id ?>, <?= (int)$s->id ?>)" class="leads-archive-btn" title="<?= htmlspecialchars($cL['btn_delete_lead']) ?>">
                        <img src="/content/admin/include/crm/icons/archive.svg" width="18" height="18">
                    </button>
                    <?php endif; ?>
                </td>
            </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
        <script>
        if (window.innerWidth <= 1024) {
            document.querySelectorAll('.crm-inbox-day-sep td').forEach(function(td) {
                td.style.setProperty('background', 'transparent', 'important');
                td.style.setProperty('border', 'none', 'important');
                td.style.setProperty('padding', '0.5rem 0 0.25rem', 'important');
            });
            document.querySelectorAll('.crm-inbox-day-sep .day-label').forEach(function(el) {
                el.style.setProperty('background', 'transparent', 'important');
            });

            var IS_CLOSED = <?= $show_closed ? 'true' : 'false' ?>;

            document.querySelectorAll('#crm-inbox-wrap .calls-tbl tr:not(.crm-inbox-day-sep)').forEach(function(row) {
                var leadId    = parseInt(row.getAttribute('data-lead-id'))    || 0;
                var sessionId = parseInt(row.getAttribute('data-session-id')) || 0;
                if (!leadId && !sessionId) return;

                var bg = document.createElement('div');
                if (IS_CLOSED) {
                    bg.style.cssText = 'position:absolute;top:0;right:0;bottom:0;width:80px;background:#16a34a;border-radius:10px;display:flex;align-items:center;justify-content:center;opacity:0;pointer-events:none;';
                    bg.innerHTML = '<svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="#fff" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><path d="M9 14l-4-4 4-4"/><path d="M5 10h11a4 4 0 0 1 0 8h-1"/></svg>';
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
                        setTimeout(function() {
                            if (IS_CLOSED) {
                                inboxReopenSession(sessionId || null);
                            } else {
                                inboxArchiveLead(leadId || null, sessionId || null);
                            }
                        }, 200);
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
        <?php else: ?>
        <div class="calls-empty"><?= $cL['inbox_empty'] ?></div>
        <?php endif; ?>
    </div>

    <?php if ($pages > 1): ?>
    <div class="calls-pagination">
        <?php for ($i = 1; $i <= $pages; $i++): ?>
        <a href="<?= $base_url ?>?<?= http_build_query(['q'=>$q,'ch'=>$ch_f,'p'=>$i,'closed'=>$show_closed?1:null]) ?>"
           class="calls-page-btn <?= $i==$page?'active':'' ?>"><?= $i ?></a>
        <?php endfor; ?>
        <span class="calls-page-info"><?= number_format($total) ?> total</span>
    </div>
    <?php endif; ?>
</div>
<script>
function inboxReopenSession(sessionId) {
    fetch('/ajax.php', {method:'POST', headers:{'Content-Type':'application/x-www-form-urlencoded'}, body:'tp=adm&pg=crm&fn=inbox_reopen_session&sid='+sessionId})
    .then(function(r){ return r.json(); })
    .then(function(d){ if (d.ok) location.reload(); else alert(d.msg||'Eroare'); });
}

function inboxArchiveLead(leadId, sessionId) {
    var closeBody = 'tp=adm&pg=crm&fn=inbox_close_session&sid='+sessionId;
    var p = fetch('/ajax.php', {method:'POST', headers:{'Content-Type':'application/x-www-form-urlencoded'}, body:closeBody}).then(r=>r.json());
    if (leadId) {
        var deleteBody = 'tp=adm&pg=crm&fn=delete_lead&id='+leadId;
        p = p.then(function() {
            return fetch('/ajax.php', {method:'POST', headers:{'Content-Type':'application/x-www-form-urlencoded'}, body:deleteBody}).then(r=>r.json());
        });
    }
    p.then(function(d) {
        if (d.ok) location.reload();
        else alert(d.msg||'Eroare');
    });
}

// Reload from server when navigating back (prevent stale cache)
window.addEventListener('pageshow', function(e) {
    if (e.persisted) window.location.reload();
});
(function(){
    var lastMsgId = <?= (int)$db->query("SELECT COALESCE(MAX(id),0) FROM {$prefx}_crm_inbox_messages WHERE direction='in'")->fetchColumn() ?>;
    var lastUnread = <?= (int)$db->query("SELECT COALESCE(SUM(unread_count),0) FROM {$prefx}_crm_inbox_sessions WHERE status<>'closed'")->fetchColumn() ?>;
    var ajaxUrl = '/ajax.php';
    var pageUrl = window.location.href;

    function refreshInbox() {
        fetch(pageUrl, { headers: {'X-Requested-With': 'XMLHttpRequest'} })
        .then(function(r){ return r.text(); })
        .then(function(html){
            var parser = new DOMParser();
            var doc = parser.parseFromString(html, 'text/html');
            var newWrap = doc.getElementById('crm-inbox-wrap');
            var curWrap = document.getElementById('crm-inbox-wrap');
            if (newWrap && curWrap) {
                curWrap.innerHTML = newWrap.innerHTML;
            }
        })
        .catch(function(){});
    }

    setInterval(function(){
        fetch(ajaxUrl, {
            method: 'POST',
            headers: {'Content-Type':'application/x-www-form-urlencoded'},
            body: 'tp=adm&pg=crm&fn=poll_inbox'
        })
        .then(function(r){ return r.json(); })
        .then(function(d){
            if (!d.ok) return;
            if (d.last_msg_id > lastMsgId || d.total_unread !== lastUnread) {
                lastMsgId = d.last_msg_id;
                lastUnread = d.total_unread;
                refreshInbox();
            }
        })
        .catch(function(){});
    }, 5000);
})();
</script>
