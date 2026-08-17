<?php defined('_DOIT') or die('Restricted access');

require_once(_ADM_INCL.'/crm/crm_lang.php');
require_once(_ADM_INCL.'/crm/crm_inbox_core.php');

$lang_url       = $_COOKIE['lang'] ?? 'ro';
$admin_dir_name = 'adminsauto';
$session_id     = (int)($_GET['sid'] ?? 0);

if (!$session_id) {
    echo '<div class="crm-notice error" style="margin:2rem;">' . $cL['inbox_chat_session_invalid'] . '</div>';
    return;
}

$session = $db->prepare("SELECT * FROM {$prefx}_crm_inbox_sessions WHERE id=:id LIMIT 1");
$session->execute([':id' => $session_id]);
$session = $session->fetchObject();

if (!$session) {
    echo '<div class="crm-notice error" style="margin:2rem;">' . $cL['inbox_chat_session_notfound'] . '</div>';
    return;
}

$messages = inbox_get_history($db, $prefx, $session_id, 100);
$db->prepare("UPDATE {$prefx}_crm_inbox_sessions SET unread_count=0 WHERE id=:sid")
   ->execute([':sid' => $session_id]);
$lead     = null;
if ($session->lead_id) {
    $lead = crm_get_lead($db, $prefx, (int)$session->lead_id);
}

$channel_colors = [
    'facebook'  => '#1877F2',
    'instagram' => '#E1306C',
    'telegram'  => '#229ED9',
    'viber'     => '#7360F2',
    'site'      => '#16a34a',
    '999md'     => '#FF6B00',
    'other'     => '#888',
];
$ch_color   = $channel_colors[$session->channel] ?? '#888';
$back_url   = "/$lang_url/$admin_dir_name/crm/inbox";
$lead_url   = $session->lead_id ? "/$lang_url/$admin_dir_name/crm/lead?id={$session->lead_id}" : '';
$is_closed  = $session->status === 'closed';
$mode_class = $is_closed ? 'mode-closed' : ($session->ai_active ? 'mode-ai' : 'mode-manager');
$embed      = !empty($_GET['embed']);

function inbox_linkify(string $text): string {
    $safe = htmlspecialchars($text, ENT_QUOTES, 'UTF-8');
    return preg_replace_callback(
        '/https?:\/\/[^\s\x{00}-\x{1F}"\'<>]+/u',
        fn($m) => '<a href="' . $m[0] . '" target="_blank" rel="noopener noreferrer" style="color:inherit;text-decoration:underline;word-break:break-all;">' . $m[0] . '</a>',
        $safe
    );
}
?>

<style>
/* ── Page shell (title + chat, ca calls/leads) ──────────── */
#crm-inbox-chat-wrap {
    max-width:900px; margin:0 auto 0;
    display:flex; flex-direction:column;
    height:calc(100vh - 90px);
    min-height:0;
}
#crm-inbox-chat-wrap .calls-header { flex-shrink:0; }

/* Embed mode (inside lead_detail iframe) */
#crm-inbox-chat-wrap.embed-mode {
    max-width:none; margin:0;
    height:100vh;
}
#crm-inbox-chat-wrap.embed-mode #inbox-chat-wrap {
    border-radius:0; box-shadow:none; border:none;
}

/* ── Layout ────────────────────────────────────────────── */
#inbox-chat-wrap {
    display:flex; flex-direction:column;
    flex:1; min-height:0;
    border-radius:10px; overflow:hidden;
    box-shadow:0 2px 16px rgba(0,0,0,0.07);
    border:1px solid #e8e8e8;
}

/* ── Header ────────────────────────────────────────────── */
.inbox-chat-header {
    display:flex; align-items:center; gap:0.65rem;
    padding:0.75rem 1rem;
    background:#fff;
    border-bottom:2px solid #f0f0f0;
    flex-shrink:0;
    transition: border-color 0.3s;
}
.inbox-chat-header.mode-ai    { border-bottom-color:#f97316; background:#fffaf5; }
.inbox-chat-header.mode-manager { border-bottom-color:#22c55e; background:#f8fff9; }
.inbox-chat-header.mode-closed  { border-bottom-color:#9ca3af; background:#f9f9f9; }

.inbox-back-btn {
    color:#9ca3af; font-size:0.78rem; text-decoration:none;
    display:inline-flex; align-items:center; gap:0.2rem;
    padding:0 0.65rem; height:34px; box-sizing:border-box;
    border-radius:6px; border:1px solid #e5e7eb; background:#fff;
    transition:all 0.15s; flex-shrink:0; white-space:nowrap;
}
.inbox-back-btn:hover { background:#f3f4f6; color:#555; }

.inbox-channel-dot {
    width:34px; height:34px; border-radius:50%; color:#fff;
    display:flex; align-items:center; justify-content:center;
    font-size:0.78rem; font-weight:800; flex-shrink:0;
    box-shadow:0 2px 6px rgba(0,0,0,0.15);
}
.inbox-sender-info { flex:1; min-width:0; }
.inbox-sender-name { font-weight:700; font-size:0.92rem; color:#191919; white-space:nowrap; overflow:hidden; text-overflow:ellipsis; }
.inbox-sender-sub  { font-size:0.72rem; color:#9ca3af; margin-top:1px; }

/* ── Header buttons (all equal size) ──────────────────── */
.inbox-hdr-btn {
    display:inline-flex; align-items:center; justify-content:center;
    gap:0.3rem; padding:0 0.85rem; height:34px;
    border-radius:7px; font-size:0.78rem; font-weight:600;
    cursor:pointer; flex-shrink:0; white-space:nowrap;
    border:1.5px solid transparent; transition:all 0.15s;
    text-decoration:none;
}
.inbox-hdr-btn-outline {
    border-color:#e5e7eb; background:#fff; color:#374151;
}
.inbox-hdr-btn-outline:hover { background:#f3f4f6; border-color:#d1d5db; }
.inbox-hdr-btn-primary {
    background:#E61E2D; border-color:#E61E2D; color:#fff;
}
.inbox-hdr-btn-primary:hover { background:#c8111f; }
.inbox-hdr-btn-danger {
    border-color:#e5e7eb; background:#fff; color:#6b7280;
}
.inbox-hdr-btn-danger:hover { background:#f9fafb; border-color:#9ca3af; color:#374151; }
.inbox-hdr-btn-active-green {
    background:#22c55e; border-color:#22c55e; color:#fff;
}
.inbox-hdr-btn-active-green:hover { background:#16a34a; border-color:#16a34a; }
.inbox-hdr-btn-active-orange {
    background:#f97316; border-color:#f97316; color:#fff;
}
.inbox-hdr-btn-active-orange:hover { background:#ea6c10; border-color:#ea6c10; }

/* ── Status badge ──────────────────────────────────────── */
.inbox-status-badge {
    display:inline-flex; align-items:center; gap:0.3rem;
    padding:0 0.65rem; height:34px; box-sizing:border-box;
    border-radius:6px; font-size:0.72rem; font-weight:700;
    color:#fff; flex-shrink:0;
}

/* ── Chat body ─────────────────────────────────────────── */
.inbox-chat-body {
    flex:1; overflow-y:auto; padding:1.25rem 1rem;
    display:flex; flex-direction:column; gap:0.75rem;
    transition: background 0.3s;
}
.inbox-chat-body.mode-ai      { background:#fffaf5; }
.inbox-chat-body.mode-manager { background:#f8fff9; }
.inbox-chat-body.mode-closed  { background:#f7f7f7; }

/* ── Messages ──────────────────────────────────────────── */
.msg-wrap { display:flex; flex-direction:column; }
.msg-wrap.out, .msg-wrap.internal { align-items:flex-end; }
.msg-wrap.in  { align-items:flex-start; }

.msg-bubble {
    max-width:72%; padding:0.6rem 0.95rem;
    font-size:0.86rem; line-height:1.5; word-break:break-word;
    box-shadow:0 1px 3px rgba(0,0,0,0.06);
}
.msg-bubble.in {
    background:#fff; border:1px solid #e8e8e8; color:#1f2937;
    border-radius:3px 14px 14px 14px;
}
.msg-bubble.out {
    background:#E61E2D; color:#fff;
    border-radius:14px 3px 14px 14px;
}
.msg-bubble.out.ai {
    background:#f97316;
    border-radius:14px 3px 14px 14px;
}
.msg-bubble.internal {
    background:#fefce8; border:1px solid #fde047; color:#78350f;
    border-radius:14px 3px 14px 14px;
}
.msg-bubble-photo {
    max-width:72%; padding:0; background:none; border:none; box-shadow:none;
}
.msg-bubble-photo img { border-radius:12px; display:block; max-width:200px; max-height:200px; }
.msg-bubble-form { display:flex; flex-direction:column; gap:0.4rem; padding:0.75rem 1rem !important; min-width:220px; }
.msg-form-row { display:flex; flex-direction:column; gap:0.1rem; }
.msg-form-label { font-size:0.68rem; font-weight:700; text-transform:uppercase; opacity:0.55; letter-spacing:0.04em; }
.msg-form-val { font-size:0.88rem; font-weight:500; word-break:break-all; }
.msg-photo-grid { display:flex; flex-wrap:wrap; gap:4px; max-width:320px; }
.msg-photo-grid img { border-radius:8px; width:100px; height:100px; object-fit:cover; display:block; cursor:pointer; }
.msg-photo-grid.single img { width:200px; height:auto; max-height:200px; }
.msg-meta {
    font-size:0.64rem; color:#b0b7c3; margin-top:0.25rem;
    display:flex; align-items:center; gap:0.3rem;
}

/* ── Footer ────────────────────────────────────────────── */
.inbox-chat-footer {
    padding:0.85rem 1rem; background:#fff;
    border-top:1px solid #f0f0f0; flex-shrink:0;
}

.inbox-footer-notice {
    display:flex; align-items:center; justify-content:center; gap:0.5rem;
    box-sizing:border-box;
    min-height:52px;
    padding:0.75rem 1rem; border-radius:8px;
    font-size:0.84rem; font-weight:500;
    line-height:1.35;
}
.inbox-footer-notice.ai-notice {
    background:#fff7ed; color:#c2410c;
    border:1px solid #fed7aa;
}
.inbox-footer-notice.closed-notice {
    background:#f3f4f6; color:#6b7280;
    border:1px solid #e5e7eb;
}

.inbox-internal-toggle {
    display:inline-flex; align-items:center; gap:0.4rem;
    font-size:0.78rem; color:#6b7280;
    cursor:pointer; user-select:none;
}
.inbox-internal-toggle input { cursor:pointer; accent-color:#f97316; }

.inbox-textarea {
    width:100%; resize:none; box-sizing:border-box;
    border:1.5px solid #e5e7eb; border-radius:8px;
    padding:0.65rem 0.85rem; font-size:0.86rem; line-height:1.5;
    min-height:72px; font-family:inherit; color:#1f2937;
    background:#fff; transition:border-color 0.15s;
}
.inbox-textarea:focus { outline:none; border-color:#E61E2D; }
.inbox-textarea.internal-mode { background:#fefce8; border-color:#fde047; }

.inbox-footer-actions {
    display:flex; justify-content:space-between; align-items:center;
    gap:0.5rem; margin-top:0.5rem;
}
.inbox-send-hint { font-size:0.7rem; color:#c4c9d4; }

.inbox-send-btn {
    display:inline-flex; align-items:center; gap:0.35rem;
    padding:0 1.1rem; height:36px; border-radius:8px;
    background:#E61E2D; color:#fff; font-size:0.82rem;
    font-weight:600; border:none; cursor:pointer;
    transition:background 0.15s;
}
.inbox-send-btn:hover { background:#c8111f; }

/* ── Helpers ───────────────────────────────────────────── */
.inbox-icon { display:inline-flex; align-items:center; vertical-align:middle; }
.inbox-icon svg { display:block; }
</style>

<!-- SVG shortcuts reused in JS too -->
<?php
$svg_bot = '<svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="11" width="18" height="10" rx="2"/><circle cx="12" cy="5" r="2"/><path d="M12 7v4"/><line x1="8" y1="16" x2="8" y2="16"/><line x1="16" y1="16" x2="16" y2="16"/></svg>';
$svg_manager = '<svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 218.582 218.582" fill="currentColor"><path d="M160.798,64.543c-1.211-1.869-2.679-3.143-4.046-4.005c-0.007-2.32-0.16-5.601-0.712-9.385c0.373-4.515,1.676-29.376-13.535-40.585C133.123,3.654,122.676,0,112.294,0c-8.438,0-16.474,2.398-22.629,6.752c-5.543,3.922-8.596,8.188-10.212,11.191c-4.78,0.169-14.683,2.118-19.063,14.745c-4.144,11.944-0.798,19.323,1.663,22.743c-0.161,1.978-0.219,3.717-0.223,5.106c-1.367,0.862-2.835,2.136-4.046,4.005c-2.74,4.229-3.206,9.9-1.386,16.859c3.403,13.012,11.344,15.876,15.581,16.451c2.61,5.218,8.346,15.882,14.086,21.24c2.293,2.14,5.274,3.946,8.86,5.37c4.577,1.816,9.411,2.737,14.366,2.737s9.789-0.921,14.366-2.737c3.586-1.424,6.567-3.23,8.86-5.37c5.74-5.358,11.476-16.022,14.086-21.24c4.236-0.575,12.177-3.44,15.581-16.452C164.004,74.443,163.538,68.771,160.798,64.543z M152.509,78.871c-2.074,7.932-5.781,9.116-7.807,9.116c-0.144,0-0.252-0.008-0.316-0.013c-2.314-0.585-4.454,0.631-5.466,2.808c-1.98,4.256-8.218,16.326-13.226,21.001c-1.377,1.285-3.304,2.425-5.726,3.386c-6.796,2.697-14.559,2.697-21.354,0c-2.422-0.961-4.349-2.101-5.726-3.386c-5.008-4.675-11.246-16.745-13.226-21.001c-0.842-1.81-2.461-2.953-4.314-2.953c-0.376,0-0.762,0.047-1.153,0.146c-0.064,0.006-0.172,0.013-0.315,0.013c-2.025,0-5.732-1.185-7.807-9.115c-1.021-3.903-1.012-7.016,0.024-8.764c0.603-1.016,1.459-1.358,1.739-1.446c2.683-0.291,4.299-2.64,4.075-5.347c-0.005-0.066-0.18-2.39,0.042-5.927c3.441-1.479,8.939-4.396,13.574-9.402c2.359-2.549,4.085-5.672,5.314-8.537c3.351,2.736,8.095,5.951,14.372,8.729c10.751,4.758,32.237,7.021,41.307,7.794c0.375,4.317,0.156,7.263,0.15,7.333c-0.236,2.715,1.383,5.066,4.075,5.357c0.28,0.088,1.136,0.431,1.739,1.446C153.521,71.856,153.53,74.969,152.509,78.871z M184.573,145.65l-43.715-17.485c-1.258-0.502-2.665-0.473-3.903,0.08c-1.236,0.555-2.195,1.588-2.655,2.862l-10.989,30.382l-2.176-6.256l3.462-8.463c0.63-1.542,0.452-3.297-0.477-4.681c-0.929-1.383-2.485-2.213-4.151-2.213H98.614c-1.666,0-3.223,0.83-4.151,2.213c-0.929,1.384-1.107,3.139-0.477,4.681l3.462,8.463l-2.176,6.256l-10.989-30.382c-0.46-1.274-1.419-2.308-2.655-2.862c-1.238-0.554-2.646-0.583-3.903-0.08L34.009,145.65c-13.424,5.369-22.098,18.182-22.098,32.641v35.291c0,2.762,2.239,5,5,5h184.76c2.761,0,5-2.238,5-5v-35.291C206.671,163.832,197.997,151.02,184.573,145.65z M183.054,192.718c0,2.762-2.239,5-5,5h-33.57c-2.761,0-5-2.238-5-5v-15.59c0-2.762,2.239-5,5-5h33.57c2.761,0,5,2.238,5,5V192.718z"/></svg>';
$svg_archive = '<svg width="14" height="14" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg"><path d="M9 12C9 11.5341 9 11.3011 9.07612 11.1173C9.17761 10.8723 9.37229 10.6776 9.61732 10.5761C9.80109 10.5 10.0341 10.5 10.5 10.5H13.5C13.9659 10.5 14.1989 10.5 14.3827 10.5761C14.6277 10.6776 14.8224 10.8723 14.9239 11.1173C15 11.3011 15 11.5341 15 12C15 12.4659 15 12.6989 14.9239 12.8827C14.8224 13.1277 14.6277 13.3224 14.3827 13.4239C14.1989 13.5 13.9659 13.5 13.5 13.5H10.5C10.0341 13.5 9.80109 13.5 9.61732 13.4239C9.37229 13.3224 9.17761 13.1277 9.07612 12.8827C9 12.6989 9 12.4659 9 12Z" stroke="currentColor" stroke-width="1.5"/><path d="M20.5 7V13C20.5 16.7712 20.5 18.6569 19.3284 19.8284C18.1569 21 16.2712 21 12.5 21H11.5M3.5 7V13C3.5 16.7712 3.5 18.6569 4.67157 19.8284C5.37634 20.5332 6.3395 20.814 7.81608 20.9259" stroke="currentColor" stroke-width="1.5" stroke-linecap="round"/><path d="M12 3H4C3.05719 3 2.58579 3 2.29289 3.29289C2 3.58579 2 4.05719 2 5C2 5.94281 2 6.41421 2.29289 6.70711C2.58579 7 3.05719 7 4 7H20C20.9428 7 21.4142 7 21.7071 6.70711C22 6.41421 22 5.94281 22 5C22 4.05719 22 3.58579 21.7071 3.29289C21.4142 3 20.9428 3 20 3H16" stroke="currentColor" stroke-width="1.5" stroke-linecap="round"/></svg>';
$svg_send = '<svg width="14" height="14" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg"><path d="M10.3009 13.6949L20.102 3.89742M10.5795 14.1355L12.8019 18.5804C13.339 19.6545 13.6075 20.1916 13.9458 20.3356C14.2394 20.4606 14.575 20.4379 14.8492 20.2747C15.1651 20.0866 15.3591 19.5183 15.7472 18.3818L19.9463 6.08434C20.2845 5.09409 20.4535 4.59896 20.3378 4.27142C20.2371 3.98648 20.013 3.76234 19.7281 3.66167C19.4005 3.54595 18.9054 3.71502 17.9151 4.05315L5.61763 8.2523C4.48114 8.64037 3.91289 8.83441 3.72478 9.15032C3.56153 9.42447 3.53891 9.76007 3.66389 10.0536C3.80791 10.3919 4.34498 10.6605 5.41912 11.1975L9.86397 13.42C10.041 13.5085 10.1295 13.5527 10.2061 13.6118C10.2742 13.6643 10.3352 13.7253 10.3876 13.7933C10.4468 13.87 10.491 13.9585 10.5795 14.1355Z" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/></svg>';
$svg_lock = '<svg width="13" height="13" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg"><path d="M8.1819 10.7027H6.00008C5.44781 10.7027 5.0001 11.1485 5.00009 11.7008C5.00005 13.3483 5 16.6772 5.00011 18.9189C5.00023 21.4317 8.88618 22 12 22C15.1139 22 19 21.4317 19 18.9189C19 16.6773 19 13.3483 19 11.7008C19 11.1485 18.5523 10.7027 18 10.7027H15.8182M8.1819 10.7027C8.1819 10.7027 8.18193 8.13514 8.1819 6.59459C8.18186 4.74571 9.70887 3 12 3C14.2912 3 15.8182 4.74571 15.8182 6.59459C15.8182 8.13514 15.8182 10.7027 15.8182 10.7027M8.1819 10.7027H15.8182" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/><path fill-rule="evenodd" clip-rule="evenodd" d="M13 16.6181V18C13 18.5523 12.5523 19 12 19C11.4477 19 11 18.5523 11 18V16.6181C10.6931 16.3434 10.5 15.9442 10.5 15.5C10.5 14.6716 11.1716 14 12 14C12.8284 14 13.5 14.6716 13.5 15.5C13.5 15.9442 13.3069 16.3434 13 16.6181Z" fill="currentColor"/></svg>';
?>

<div id="crm-inbox-chat-wrap" <?= $embed ? 'class="embed-mode"' : '' ?>>

    <?php if (!$embed): ?>
    <?php endif; ?>

<div id="inbox-chat-wrap">

    <!-- HEADER -->
    <div class="inbox-chat-header <?= $mode_class ?>">

        <?php if (!$embed): ?>
        <a href="<?= $back_url ?>" class="inbox-back-btn">
            <svg width="13" height="13" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg"><path fill-rule="evenodd" clip-rule="evenodd" d="M6.3508 12.7499L11.2096 17.4615L10.1654 18.5383L3.42264 11.9999L10.1654 5.46148L11.2096 6.53833L6.3508 11.2499L21 11.2499L21 12.7499L6.3508 12.7499Z" fill="currentColor"/></svg>
            <?= $cL['inbox_title'] ?>
        </a>
        <?php endif; ?>

        <?php
        $ch_icon_map = [
            'facebook'  => 'facebook.svg',
            'instagram' => 'instagram.svg',
            'telegram'  => 'telegram.svg',
            'viber'     => 'viber.svg',
            'site'      => 'sauto.png',
            '999md'     => '999.svg',
        ];
        $ch_icon_file = $ch_icon_map[$session->channel] ?? null;
        ?>
        <?php
        $chat_page_labels = [
            'sautohaus_999md'  => 'SAUTO-HAUS',
            'regular_999md'    => 'Comerciale',
            'order_999md'      => 'Comanda',
            'korea_999md'      => 'Encars',
            'usa_999md'        => 'SautoSUA',
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
        $chat_sub_label = $chat_page_labels[$session->page_id] ?? null;
        ?>
        <div style="display:flex;flex-direction:column;align-items:center;gap:2px;">
            <?php if ($session->channel === '999md' && $session->sender_name): ?>
            <a href="https://999.md/ro/profile/<?= urlencode($session->sender_name) ?>" target="_blank" title="Profil 999.md" style="display:block;line-height:0;">
                <span class="inbox-channel-dot" style="background:transparent;box-shadow:none;">
                    <img src="/content/admin/include/crm/icons/999.svg" width="28" height="28" style="object-fit:contain;display:block;">
                </span>
            </a>
            <?php else: ?>
            <span class="inbox-channel-dot" style="background:<?= $ch_icon_file ? 'transparent' : $ch_color ?>; box-shadow:<?= $ch_icon_file ? 'none' : '' ?>;">
                <?php if ($ch_icon_file): ?>
                    <img src="/content/admin/include/crm/icons/<?= $ch_icon_file ?>" width="28" height="28" style="object-fit:contain;display:block;">
                <?php else: ?>
                    <?= strtoupper(substr($session->channel, 0, 1)) ?>
                <?php endif; ?>
            </span>
            <?php endif; ?>
            <?php if ($chat_sub_label): ?>
            <span style="font-size:0.6rem;color:#aaa;white-space:nowrap;max-width:60px;overflow:hidden;text-overflow:ellipsis;"><?= htmlspecialchars($chat_sub_label) ?></span>
            <?php endif; ?>
        </div>
        <div class="inbox-sender-info">
            <?php if ($session->channel === '999md' && $session->sender_name): ?>
            <a href="https://999.md/ro/profile/<?= urlencode($session->sender_name) ?>" target="_blank" class="inbox-sender-name" style="text-decoration:none;color:inherit;"><?= htmlspecialchars($session->sender_name) ?></a>
            <?php else: ?>
            <div class="inbox-sender-name"><?= htmlspecialchars($session->sender_name ?: $session->sender_id) ?></div>
            <?php endif; ?>
            <div class="inbox-sender-sub">
                <?php if ($session->sender_phone): ?><?= htmlspecialchars($session->sender_phone) ?><?php endif; ?>
            </div>
        </div>

        <?php if ($session->channel === 'site'): ?>
            <button class="inbox-hdr-btn inbox-hdr-btn-danger" onclick="inboxCloseSession()">
                <span class="inbox-icon"><?= $svg_archive ?></span> <?= $cL['inbox_chat_btn_archive'] ?>
            </button>
        <?php else: ?>
        <?php if (!$embed && $lead_url): ?>
        <a href="<?= $lead_url ?>" class="inbox-hdr-btn inbox-hdr-btn-outline">
            Lead #<?= $session->lead_id ?>
            <svg width="12" height="12" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg"><path fill-rule="evenodd" clip-rule="evenodd" d="M17.6492 11.2501L12.7904 6.53852L13.8346 5.46167L20.5774 12.0001L13.8346 18.5385L12.7904 17.4617L17.6492 12.7501H3V11.2501H17.6492Z" fill="currentColor"/></svg>
        </a>
        <?php elseif (!$embed && crm_can_see_all($user_role, $user_id)): ?>
        <button class="inbox-hdr-btn inbox-hdr-btn-primary" onclick="inboxCreateLead()">
            <span class="inbox-create-lead-full"><?= $cL['inbox_chat_btn_create_lead'] ?></span>
            <span class="inbox-create-lead-short">+ Lead</span>
        </button>
        <?php endif; ?>

        <?php if ($is_closed): ?>
        <span class="inbox-status-badge inbox-status-closed" style="background:#6b7280;"><?= $cL['inbox_status_closed'] ?></span>
        <?php else: ?>
        <button id="inbox-btn-ai"
            class="inbox-hdr-btn <?= $session->ai_active ? 'inbox-hdr-btn-active-orange' : 'inbox-hdr-btn-outline' ?>"
            onclick="inboxToggleAI(1)">
            <span class="inbox-icon"><?= $svg_bot ?></span>
            AI
        </button>
        <button class="inbox-hdr-btn inbox-hdr-btn-danger" onclick="inboxCloseSession()">
            <span class="inbox-icon"><?= $svg_archive ?></span> <?= $cL['inbox_chat_btn_archive'] ?>
        </button>
        <?php endif; ?>
        <?php endif; ?>

    </div>

    <!-- MESSAGES -->
    <div class="inbox-chat-body <?= $mode_class ?>" id="inbox-chat-body">
    <?php
    $bot_icon_sm = '<svg style="display:inline;vertical-align:middle;margin-right:2px;" xmlns="http://www.w3.org/2000/svg" width="11" height="11" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="11" width="18" height="10" rx="2"/><circle cx="12" cy="5" r="2"/><path d="M12 7v4"/><line x1="8" y1="16" x2="8" y2="16"/><line x1="16" y1="16" x2="16" y2="16"/></svg>';

    // Group consecutive photos from same sender within 60s into one bubble
    $grouped = [];
    $i = 0; $msgs = array_values($messages);
    while ($i < count($msgs)) {
        $m = $msgs[$i];
        if (preg_match('/^\[photo:([^\]]+)\](\n.*)?$/s', trim($m->body), $pm)) {
            $group = ['type'=>'photos','msgs'=>[$m],'photos'=>[$pm[1]],'caption'=>isset($pm[2]) ? trim($pm[2]) : ''];
            $j = $i + 1;
            while ($j < count($msgs)) {
                $n = $msgs[$j];
                if (preg_match('/^\[photo:([^\]]+)\](\n.*)?$/s', trim($n->body), $npm)
                    && $n->direction === $m->direction
                    && (string)$n->author_id === (string)$m->author_id
                    && abs(strtotime($n->created_at) - strtotime($m->created_at)) <= 60) {
                    $group['msgs'][] = $n;
                    $group['photos'][] = $npm[1];
                    $j++;
                } else break;
            }
            $grouped[] = $group;
            $i = $j;
        } else {
            $grouped[] = ['type'=>'msg','msgs'=>[$m]];
            $i++;
        }
    }

    foreach ($grouped as $grp):
        $msg      = $grp['msgs'][0];
        $is_in    = $msg->direction === 'in';
        $is_internal = $msg->is_internal;
        $is_ai    = $msg->is_ai;
        $wrap_cls = $is_in ? 'in' : ($is_internal ? 'internal' : 'out');
        $bubble_cls = $is_in ? 'in' : ($is_internal ? 'internal' : 'out' . ($is_ai ? ' ai' : ''));
        $sender_lbl = $is_in
            ? htmlspecialchars($msg->sender_name ?: $cL['inbox_chat_label_client'])
            : ($is_ai ? $bot_icon_sm . 'AI' : htmlspecialchars($msg->author_name ?: $cL['inbox_status_manager']));
    ?>
    <?php if ($is_in && $session->channel === '999md' && !empty($msg->advert_url)): ?>
    <?php
        $m_aurl   = $msg->advert_url;
        $m_alabel = $msg->advert_title ?: '';
        if (!$m_alabel && preg_match('/(\d{6,})/', $m_aurl, $m_aid)) $m_alabel = 'Anunt #' . $m_aid[1];
    ?>
    <div style="align-self:flex-start;margin-bottom:2px;">
        <a href="<?= htmlspecialchars($m_aurl) ?>" target="_blank"
           style="display:inline-flex;align-items:center;gap:0.3rem;padding:0.2rem 0.6rem;border-radius:12px;background:#fff3e8;border:1px solid #ffd0a8;color:#c85000;font-size:0.7rem;font-weight:600;text-decoration:none;white-space:nowrap;"
           title="<?= htmlspecialchars($m_alabel ?: $m_aurl) ?>">
            <img src="/content/admin/include/crm/icons/999.svg" width="11" height="11" style="flex-shrink:0;">
            <?= htmlspecialchars($m_alabel ?: $m_aurl) ?>
            <svg width="9" height="9" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" style="flex-shrink:0;opacity:0.6;"><path d="M18 13v6a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V8a2 2 0 0 1 2-2h6"/><polyline points="15 3 21 3 21 9"/><line x1="10" y1="14" x2="21" y2="3"/></svg>
        </a>
    </div>
    <?php endif; ?>
    <div class="msg-wrap <?= $wrap_cls ?>">
        <?php if ($grp['type'] === 'photos'): ?>
        <?php $photos = $grp['photos']; $single = count($photos) === 1; $photosJson = htmlspecialchars(json_encode($photos), ENT_QUOTES); ?>
        <div class="msg-bubble-photo">
            <?php if ($single): ?>
            <img src="<?= htmlspecialchars($photos[0]) ?>" onclick="inboxLightbox(<?= $photosJson ?>,0)" style="border-radius:12px;display:block;max-width:200px;max-height:200px;cursor:zoom-in;">
            <?php else: ?>
            <div style="display:flex;flex-wrap:wrap;gap:4px;max-width:320px;">
                <?php foreach ($photos as $pidx => $purl): ?>
                <img src="<?= htmlspecialchars($purl) ?>" onclick="inboxLightbox(<?= $photosJson ?>,<?= $pidx ?>)" style="border-radius:8px;width:100px;height:100px;object-fit:cover;display:inline-block;cursor:zoom-in;">
                <?php endforeach; ?>
            </div>
            <?php endif; ?>
        </div>
        <?php if ($grp['caption']): ?>
        <div class="msg-bubble <?= $bubble_cls ?>" style="margin-top:4px;"><?= nl2br(htmlspecialchars($grp['caption'])) ?></div>
        <?php endif; ?>
        <?php else: ?>
        <?php $msg = $grp['msgs'][0]; ?>
        <?php if (preg_match('/^(Nume|Telefon|Mesaj|Pagina):/m', $msg->body)): ?>
        <?php
        $form_label_map = [
            'Nume'    => $cL['form_lbl_name']    ?? 'Nume',
            'Telefon' => $cL['form_lbl_phone']   ?? 'Telefon',
            'Mesaj'   => $cL['form_lbl_message'] ?? 'Mesaj',
            'Pagina'  => $cL['form_lbl_page']    ?? 'Pagina',
        ];
        ?>
        <div class="msg-bubble <?= $bubble_cls ?> msg-bubble-form">
            <?php
            foreach (explode("\n", $msg->body) as $line) {
                $line = trim($line);
                if (!$line) continue;
                if (preg_match('/^(Nume|Telefon|Mesaj|Pagina):\s*(.*)$/u', $line, $lm)) {
                    $label = $form_label_map[$lm[1]] ?? $lm[1];
                    echo '<div class="msg-form-row"><span class="msg-form-label">' . htmlspecialchars($label) . '</span><span class="msg-form-val">' . inbox_linkify(htmlspecialchars($lm[2])) . '</span></div>';
                } else {
                    echo '<div>' . nl2br(htmlspecialchars($line)) . '</div>';
                }
            }
            ?>
        </div>
        <?php else: ?>
        <div class="msg-bubble <?= $bubble_cls ?>"><?= nl2br(inbox_linkify($msg->body)) ?></div>
        <?php endif; ?>
        <?php endif; ?>
        <div class="msg-meta"><?php if ($is_internal): ?><span style="color:#ca8a04;font-weight:600;"><?= htmlspecialchars(mb_convert_case($cL['label_internal'] ?? 'notiță', MB_CASE_TITLE, 'UTF-8')) ?></span> · <?php endif; ?><?= $sender_lbl ?> · <?= date('d.m H:i', strtotime($msg->created_at)) ?></div>
    </div>
    <?php endforeach; ?>
    <?php if (empty($messages)): ?>
    <div style="text-align:center;color:#c4c9d4;padding:3rem;font-size:0.85rem;"><?= $cL['inbox_chat_no_messages'] ?></div>
    <?php endif; ?>
    </div>

    <!-- FOOTER -->
    <div class="inbox-chat-footer" id="inbox-chat-footer"><?php if ($session->channel === 'site'): ?>
        <div class="inbox-footer-notice" style="background:#f5f5f5;color:#888;font-size:0.85rem;justify-content:center;">
            <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" style="margin-right:6px;flex-shrink:0;"><circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/></svg>
            <?= $cL['inbox_site_readonly'] ?? 'Contact via phone or other channel.' ?>
        </div>
        <?php return; endif; ?>

        <div id="inbox-footer-ai" class="inbox-footer-notice ai-notice" style="display:<?= $session->ai_active ? 'flex' : 'none' ?>;justify-content:center;gap:0.5rem;">
            <span style="display:inline-flex;align-items:center;gap:0.4rem;">
                <span class="inbox-icon"><?= $svg_bot ?></span>
                <span class="inbox-ai-notice-full"><?= $cL['inbox_chat_ai_active_notice'] ?></span>
                <span class="inbox-ai-notice-short"><?php
                    $short = ['ro' => 'Preia discuția', 'ru' => 'Перехватить', 'en' => 'Take over'];
                    echo $short[$_COOKIE['lang'] ?? 'ro'] ?? 'Preia discuția';
                ?></span>
                <img src="/content/admin/include/crm/icons/right.svg" width="20" height="20" style="opacity:0.6;">
            </span>
            <button id="inbox-btn-manager"
                class="inbox-hdr-btn inbox-hdr-btn-active-green"
                onclick="inboxToggleAI(0)"
                style="flex-shrink:0;margin-left:0.2rem;">
                <span class="inbox-icon" style="overflow:hidden;"><?= $svg_manager ?></span>
                <span class="inbox-manager-btn-txt"><?= $cL['inbox_status_manager'] ?></span>
            </button>
        </div>

        <div id="inbox-footer-closed" class="inbox-footer-notice closed-notice" style="display:<?= $is_closed ? 'flex' : 'none' ?>;justify-content:space-between;align-items:center;">
            <span><?= $cL['inbox_chat_archived'] ?></span>
            <button onclick="inboxReopenSession()" class="inbox-reopen-btn" style="margin-left:1rem;padding:0.35rem 0.9rem;border-radius:6px;border:1px solid #aaa;background:#fff;color:#333;cursor:pointer;font-size:0.82rem;display:inline-flex;align-items:center;gap:0.3rem;"><span class="inbox-reopen-icon">&#8617;</span> <?= $cL['inbox_chat_btn_reopen'] ?></button>
        </div>

        <div id="inbox-footer-manager" style="display:<?= (!$session->ai_active && !$is_closed) ? 'block' : 'none' ?>;">
            <?php $channel_supports_photos = (trim($session->channel) !== 'site' && trim($session->channel) !== '999md'); ?>
            <?php if ($channel_supports_photos): ?>
            <div id="inbox-photo-preview" style="display:none;flex-wrap:wrap;gap:8px;margin-bottom:0.5rem;"></div>
            <?php endif; ?>
            <textarea id="inbox-msg-input" class="inbox-textarea"
                placeholder="<?= htmlspecialchars($cL['inbox_chat_placeholder']) ?>"
                onkeydown="if(event.key==='Enter'&&!event.shiftKey){event.preventDefault();inboxSend();}"
                <?php if ($channel_supports_photos): ?>
                ondragover="event.preventDefault();this.style.borderColor='#229ED9';"
                ondragleave="this.style.borderColor='';"
                ondrop="event.preventDefault();this.style.borderColor='';if(event.dataTransfer.files.length)inboxPreviewPhotos(event.dataTransfer.files);"
                <?php endif; ?>
                ></textarea>
            <div class="inbox-footer-actions">
                <label class="inbox-internal-toggle">
                    <input type="checkbox" id="inbox-internal-chk" onchange="toggleInternalMode()">
                    <span id="inbox-internal-label" style="display:inline-flex;align-items:center;gap:0.3rem;">
                        <span class="inbox-icon"><?= $svg_lock ?></span>
                        <?= $cL['inbox_chat_internal_note'] ?>
                    </span>
                </label>
                <div style="display:flex;align-items:center;gap:0.5rem;">
                    <?php if ($channel_supports_photos): ?>
                    <label style="display:inline-flex;align-items:center;justify-content:center;width:36px;height:36px;border-radius:8px;background:#f3f4f6;border:1px solid #e5e7eb;cursor:pointer;transition:background 0.15s;" title="Atașează poză" onmouseenter="this.style.background='#e5e7eb'" onmouseleave="this.style.background='#f3f4f6'">
                        <input type="file" id="inbox-photo-input" accept="image/*" multiple style="display:none;" onchange="inboxPreviewPhotos(this.files)">
                        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="#555" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="3" width="18" height="18" rx="2"/><circle cx="8.5" cy="8.5" r="1.5"/><polyline points="21 15 16 10 5 21"/></svg>
                    </label>
                    <?php endif; ?>
                    <button class="inbox-send-btn" onclick="inboxSend()">
                        <span class="inbox-icon"><?= $svg_send ?></span>
                        <?= $cL['inbox_chat_send'] ?>
                    </button>
                </div>
            </div>
        </div>

    </div>
</div>

</div>

<script>
var INBOX_SESSION_ID = <?= $session_id ?>;
var INBOX_CHANNEL    = '<?= addslashes($session->channel) ?>';
var INBOX_SENDER_ID  = '<?= addslashes($session->sender_id) ?>';

// Scroll to bottom
document.getElementById('inbox-chat-body').scrollTop = 99999;

function toggleInternalMode() {
    var inp = document.getElementById('inbox-msg-input');
    var chk = document.getElementById('inbox-internal-chk');
    inp.classList.toggle('internal-mode', chk.checked);
}

function inboxSend() {
    var txt = document.getElementById('inbox-msg-input').value.trim();
    var photos = _inboxPhotoFiles.filter(function(f){ return f !== null; });
    if (photos.length > 0) { inboxSendPhotos(photos, txt); return; }
    if (!txt) return;
    var isInt = document.getElementById('inbox-internal-chk').checked ? 1 : 0;
    fetch('/ajax.php', {
        method: 'POST',
        headers: {'Content-Type': 'application/x-www-form-urlencoded'},
        body: 'tp=adm&pg=crm&fn=inbox_send&sid=' + INBOX_SESSION_ID + '&body=' + encodeURIComponent(txt) + '&is_internal=' + isInt
    }).then(r => r.text()).then(raw => {
        var d = {};
        try { d = JSON.parse(raw); } catch(e) { d = {ok: true}; }
        if (d.ok !== false) {
            document.getElementById('inbox-msg-input').value = '';
            document.getElementById('inbox-internal-chk').checked = false;
            document.getElementById('inbox-msg-input').classList.remove('internal-mode');
            inboxPoll();
        } else alert(d.msg || 'Eroare');
    });
}

function inboxToggleAI(state) {
    fetch('/ajax.php', {
        method: 'POST',
        headers: {'Content-Type': 'application/x-www-form-urlencoded'},
        body: 'tp=adm&pg=crm&fn=inbox_toggle_ai&sid=' + INBOX_SESSION_ID + '&state=' + state
    }).then(r => r.json()).then(d => {
        if (!d.ok) return;
        var btnManager = document.getElementById('inbox-btn-manager');
        var btnAi      = document.getElementById('inbox-btn-ai');
        var fAi        = document.getElementById('inbox-footer-ai');
        var fMan       = document.getElementById('inbox-footer-manager');
        var hdr        = document.querySelector('.inbox-chat-header');
        var body       = document.getElementById('inbox-chat-body');
        function setMode(m) {
            ['mode-ai','mode-manager','mode-closed'].forEach(function(c){ hdr.classList.remove(c); body.classList.remove(c); });
            hdr.classList.add(m); body.classList.add(m);
        }
        if (state) {
            btnAi.className      = 'inbox-hdr-btn inbox-hdr-btn-active-orange';
            if (btnManager) btnManager.className = 'inbox-hdr-btn inbox-hdr-btn-active-green';
            fAi.style.display  = 'flex';
            fMan.style.display = 'none';
            setMode('mode-ai');
        } else {
            btnAi.className      = 'inbox-hdr-btn inbox-hdr-btn-outline';
            if (btnManager) btnManager.className = 'inbox-hdr-btn inbox-hdr-btn-active-green';
            fAi.style.display  = 'none';
            fMan.style.display = 'block';
            setMode('mode-manager');
        }
    });
}

function inboxReopenSession() {
    fetch('/ajax.php', {
        method: 'POST',
        headers: {'Content-Type': 'application/x-www-form-urlencoded'},
        body: 'tp=adm&pg=crm&fn=inbox_reopen_session&sid=' + INBOX_SESSION_ID
    }).then(r => r.json()).then(d => { if (d.ok) location.reload(); });
}

function inboxCloseSession() {
    if (!confirm('<?= addslashes($cL['inbox_chat_confirm_archive']) ?>')) return;
    fetch('/ajax.php', {
        method: 'POST',
        headers: {'Content-Type': 'application/x-www-form-urlencoded'},
        body: 'tp=adm&pg=crm&fn=inbox_close_session&sid=' + INBOX_SESSION_ID
    }).then(r => r.json()).then(d => { if (d.ok) location.reload(); });
}

function inboxCreateLead() {
    var phone = prompt('<?= addslashes($cL['inbox_chat_prompt_phone']) ?>');
    if (!phone) return;
    fetch('/ajax.php', {
        method: 'POST',
        headers: {'Content-Type': 'application/x-www-form-urlencoded'},
        body: 'tp=adm&pg=crm&fn=inbox_create_lead_manual&sid=' + INBOX_SESSION_ID + '&phone=' + encodeURIComponent(phone)
    }).then(r => r.json()).then(d => { if (d.ok) location.reload(); else alert(d.msg || 'Eroare'); });
}

function appendAdvertBubble(advertUrl, advertTitle) {
    if (!advertUrl) return;
    var label = advertTitle || '';
    if (!label) {
        var m = advertUrl.match(/(\d{6,})/);
        if (m) label = 'Anunt #' + m[1];
        else label = advertUrl;
    }
    var div = document.createElement('div');
    div.style.cssText = 'align-self:flex-start;margin-bottom:2px;';
    var a = document.createElement('a');
    a.href = advertUrl; a.target = '_blank'; a.title = label;
    a.style.cssText = 'display:inline-flex;align-items:center;gap:0.3rem;padding:0.2rem 0.6rem;border-radius:12px;background:#fff3e8;border:1px solid #ffd0a8;color:#c85000;font-size:0.7rem;font-weight:600;text-decoration:none;white-space:nowrap;';
    a.innerHTML = '<img src="/content/admin/include/crm/icons/999.svg" width="11" height="11" style="flex-shrink:0;"> ' + label.replace(/[<>&"]/g, function(c){return {'<':'&lt;','>':'&gt;','&':'&amp;','"':'&quot;'}[c];}) + ' <svg width="9" height="9" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" style="flex-shrink:0;opacity:0.6;"><path d="M18 13v6a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V8a2 2 0 0 1 2-2h6"/><polyline points="15 3 21 3 21 9"/><line x1="10" y1="14" x2="21" y2="3"/></svg>';
    div.appendChild(a);
    document.getElementById('inbox-chat-body').appendChild(div);
}

function appendMessage(body, type, author, advertUrl, advertTitle) {
    if (type === 'in' && advertUrl) appendAdvertBubble(advertUrl, advertTitle);
    var wrap = document.createElement('div');
    wrap.className = 'msg-wrap ' + type;
    var now = new Date();
    var time = ('0'+now.getDate()).slice(-2)+'.'+('0'+(now.getMonth()+1)).slice(-2)+' '+('0'+now.getHours()).slice(-2)+':'+('0'+now.getMinutes()).slice(-2);
    var noteTag = '<span style="color:#ca8a04;font-weight:600;"><?= addslashes(mb_convert_case($cL['label_internal'] ?? 'notiță', MB_CASE_TITLE, 'UTF-8')) ?></span> · ';
    var label = type === 'internal' ? (noteTag + author) : author;
    wrap.innerHTML = '<div class="msg-bubble ' + type + '">' + body.replace(/\n/g,'<br>') + '</div>'
        + '<div class="msg-meta">' + label + ' · ' + time + '</div>';
    var body_el = document.getElementById('inbox-chat-body');
    body_el.appendChild(wrap);
    body_el.scrollTop = 99999;
}

var _inboxPhotoFiles = [];
var _inboxSending = false;

function inboxCompressImage(file, callback) {
    var maxSize = 1200;
    var quality = 0.82;
    var reader = new FileReader();
    reader.onload = function(e) {
        var img = new Image();
        img.onload = function() {
            var w = img.width, h = img.height;
            if (w <= maxSize && h <= maxSize && file.size < 500000) {
                callback(file, e.target.result); return;
            }
            var ratio = Math.min(maxSize/w, maxSize/h, 1);
            var nw = Math.round(w*ratio), nh = Math.round(h*ratio);
            var canvas = document.createElement('canvas');
            canvas.width = nw; canvas.height = nh;
            canvas.getContext('2d').drawImage(img, 0, 0, nw, nh);
            canvas.toBlob(function(blob) {
                var compressed = new File([blob], file.name.replace(/\.[^.]+$/, '.jpg'), {type:'image/jpeg'});
                callback(compressed, canvas.toDataURL('image/jpeg', quality));
            }, 'image/jpeg', quality);
        };
        img.src = e.target.result;
    };
    reader.readAsDataURL(file);
}

function inboxPreviewPhotos(files) {
    var preview = document.getElementById('inbox-photo-preview');
    if (!preview) return;
    for (var i = 0; i < files.length; i++) {
        var file = files[i];
        if (!file.type.startsWith('image/')) continue;
        (function(f) {
            inboxCompressImage(f, function(compressed, dataUrl) {
                _inboxPhotoFiles.push(compressed);
                var idx = _inboxPhotoFiles.length - 1;
                var wrap = document.createElement('div');
                wrap.style.cssText = 'position:relative;display:inline-block;';
                wrap.dataset.idx = idx;
                var img = document.createElement('img');
                img.src = dataUrl;
                img.style.cssText = 'max-height:80px;max-width:120px;border-radius:6px;border:2px solid #229ED9;display:inline-block;';
                var btn = document.createElement('button');
                btn.innerHTML = '×';
                btn.style.cssText = 'position:absolute;top:-6px;right:-6px;width:18px;height:18px;border-radius:50%;background:#E61E2D;border:none;color:#fff;font-size:13px;line-height:1;cursor:pointer;display:flex;align-items:center;justify-content:center;font-weight:700;padding:0;';
                btn.onclick = function() { inboxRemovePhoto(idx, wrap); };
                wrap.appendChild(img);
                wrap.appendChild(btn);
                preview.appendChild(wrap);
                preview.style.display = 'flex';
                document.getElementById('inbox-msg-input').focus();
            });
        })(file);
    }
    document.getElementById('inbox-photo-input').value = '';
}

function inboxRemovePhoto(idx, wrap) {
    _inboxPhotoFiles[idx] = null;
    wrap.remove();
    var remaining = _inboxPhotoFiles.filter(function(f){ return f !== null; });
    if (remaining.length === 0) {
        var preview = document.getElementById('inbox-photo-preview');
        if (preview) preview.style.display = 'none';
    }
}

function inboxClearPhoto() {
    _inboxPhotoFiles = [];
    var preview = document.getElementById('inbox-photo-preview');
    var inp = document.getElementById('inbox-photo-input');
    if (preview) { preview.innerHTML = ''; preview.style.display = 'none'; }
    if (inp) inp.value = '';
}

function inboxSendPhotos(files, caption) {
    if (!files || files.length === 0) return;
    _inboxSending = true;
    var btn = document.querySelector('.inbox-send-btn');
    if (btn) { btn.disabled = true; btn.style.opacity = '0.6'; }

    function doSendPhotos(captionText) {
        var fd = new FormData();
        fd.append('tp', 'adm');
        fd.append('pg', 'crm');
        fd.append('fn', 'inbox_send_photos');
        fd.append('sid', INBOX_SESSION_ID);
        if (captionText) fd.append('caption', captionText);
        for (var i = 0; i < files.length; i++) fd.append('photos[]', files[i]);
        var ctrl = new AbortController();
        var fetchTimer = setTimeout(function(){ ctrl.abort(); }, 8000);
        fetch('/ajax.php', { method: 'POST', body: fd, signal: ctrl.signal })
            .then(r => r.json())
            .then(d => {
                clearTimeout(fetchTimer);
                inboxClearPhoto();
                document.getElementById('inbox-msg-input').value = '';
                if (btn) { btn.disabled = false; btn.style.opacity = ''; }
                _inboxSending = false;
                if (d.ok && d.previews && d.previews.length) {
                    var body_el = document.getElementById('inbox-chat-body');
                    var now = new Date();
                    var time = ('0'+now.getDate()).slice(-2)+'.'+('0'+(now.getMonth()+1)).slice(-2)+' '+('0'+now.getHours()).slice(-2)+':'+('0'+now.getMinutes()).slice(-2);
                    body_el.appendChild(inboxRenderPhotoGroup(d.previews, captionText || '', 'out', <?= json_encode($_SESSION['user_name'] ?? '') ?>, time));
                    body_el.scrollTop = 99999;
                }
                if (d.ok && d.last_id && d.last_id > _inboxLastMsgId) _inboxLastMsgId = d.last_id;
                inboxPoll();
            })
            .catch(function() {
                inboxClearPhoto();
                document.getElementById('inbox-msg-input').value = '';
                if (btn) { btn.disabled = false; btn.style.opacity = ''; }
                _inboxSending = false;
                inboxPoll();
            });
    }

    // Send photos first, then caption text (so text arrives after images in the channel)
    if (caption) {
        doSendPhotos(caption);
    } else {
        doSendPhotos();
    }
}

var _inboxLastMsgId = <?= !empty($messages) ? (int)end($messages)->id : 0 ?>;

function inboxRenderPhotoGroup(photos, caption, dirCls, senderLabel, time) {
    var wrap = document.createElement('div');
    wrap.className = 'msg-wrap ' + dirCls;
    var single = photos.length === 1;
    var captionHtml = caption ? '<div class="msg-bubble ' + dirCls + '" style="margin-top:4px;">' + caption.replace(/</g,'&lt;') + '</div>' : '';
    var meta = '<div class="msg-meta">' + senderLabel + ' · ' + time + '</div>';

    var photoWrap = document.createElement('div');
    photoWrap.className = 'msg-bubble-photo';

    var grid = single ? photoWrap : document.createElement('div');
    if (!single) { grid.style.cssText = 'display:flex;flex-wrap:wrap;gap:4px;max-width:320px;'; photoWrap.appendChild(grid); }

    photos.forEach(function(url, idx) {
        var img = document.createElement('img');
        img.src = url;
        img.style.cssText = single
            ? 'border-radius:12px;display:block;max-width:200px;max-height:200px;cursor:zoom-in;'
            : 'border-radius:8px;width:100px;height:100px;object-fit:cover;display:block;cursor:zoom-in;';
        img.addEventListener('click', function() { inboxLightbox(photos, idx); });
        grid.appendChild(img);
    });

    wrap.appendChild(photoWrap);
    if (caption) {
        var capEl = document.createElement('div');
        capEl.className = 'msg-bubble ' + dirCls;
        capEl.style.marginTop = '4px';
        capEl.textContent = caption;
        wrap.appendChild(capEl);
    }
    var metaEl = document.createElement('div');
    metaEl.className = 'msg-meta';
    metaEl.innerHTML = senderLabel + ' · ' + time;
    wrap.appendChild(metaEl);
    return wrap;
}

var _lbPhotos = [], _lbIdx = 0;
function inboxLightbox(photos, idx) {
    _lbPhotos = photos; _lbIdx = idx;
    var lb = document.getElementById('inbox-lightbox');
    if (!lb) {
        lb = document.createElement('div');
        lb.id = 'inbox-lightbox';
        lb.style.cssText = 'position:fixed;inset:0;background:rgba(0,0,0,0.92);z-index:99999;display:flex;align-items:center;justify-content:center;';
        lb.innerHTML = '<button onclick="inboxLbClose()" style="position:absolute;top:16px;right:20px;background:none;border:none;color:#fff;font-size:32px;cursor:pointer;line-height:1;">×</button>'
            + '<button onclick="inboxLbPrev()" style="position:absolute;left:calc(5vw + 10px);background:rgba(255,255,255,0.15);border:none;color:#fff;font-size:28px;cursor:pointer;padding:10px 16px;border-radius:8px;">‹</button>'
            + '<img id="inbox-lb-img" style="max-width:90vw;max-height:88vh;border-radius:8px;object-fit:contain;box-shadow:0 4px 40px rgba(0,0,0,0.6);">'
            + '<button onclick="inboxLbNext()" style="position:absolute;right:calc(5vw + 10px);background:rgba(255,255,255,0.15);border:none;color:#fff;font-size:28px;cursor:pointer;padding:10px 16px;border-radius:8px;">›</button>'
            + '<a id="inbox-lb-dl" download style="position:absolute;bottom:18px;right:20px;background:rgba(255,255,255,0.15);color:#fff;font-size:0.78rem;padding:6px 14px;border-radius:6px;text-decoration:none;">↓ Download</a>'
            + '<div id="inbox-lb-counter" style="position:absolute;bottom:18px;left:50%;transform:translateX(-50%);color:rgba(255,255,255,0.6);font-size:0.8rem;"></div>';
        lb.addEventListener('click', function(e){ if(e.target===lb) inboxLbClose(); });
        document.body.appendChild(lb);
        document.addEventListener('keydown', function(e){
            if (!document.getElementById('inbox-lightbox')) return;
            if (e.key==='ArrowLeft') inboxLbPrev();
            else if (e.key==='ArrowRight') inboxLbNext();
            else if (e.key==='Escape') inboxLbClose();
        });
    }
    lb.style.display = 'flex';
    inboxLbShow();
}
function inboxLbShow() {
    var url = _lbPhotos[_lbIdx];
    document.getElementById('inbox-lb-img').src = url;
    document.getElementById('inbox-lb-dl').href = url;
    var counter = document.getElementById('inbox-lb-counter');
    counter.textContent = _lbPhotos.length > 1 ? (_lbIdx+1) + ' / ' + _lbPhotos.length : '';
    var lb = document.getElementById('inbox-lightbox');
    lb.querySelector('button:nth-child(2)').style.display = _lbPhotos.length > 1 ? '' : 'none';
    lb.querySelector('button:nth-child(4)').style.display = _lbPhotos.length > 1 ? '' : 'none';
}
function inboxLbPrev() { _lbIdx = (_lbIdx - 1 + _lbPhotos.length) % _lbPhotos.length; inboxLbShow(); }
function inboxLbNext() { _lbIdx = (_lbIdx + 1) % _lbPhotos.length; inboxLbShow(); }
function inboxLbClose() { var lb = document.getElementById('inbox-lightbox'); if(lb) lb.style.display='none'; }

function inboxPoll() {
    fetch('/ajax.php', {
        method: 'POST',
        headers: {'Content-Type': 'application/x-www-form-urlencoded'},
        body: 'tp=adm&pg=crm&fn=inbox_poll&sid=' + INBOX_SESSION_ID + '&last_id=' + _inboxLastMsgId
    }).then(r => r.json()).then(d => {
        if (!d.ok || !d.new_messages || !d.new_messages.length) return;
        var body_el = document.getElementById('inbox-chat-body');
        var msgs = d.new_messages;
        msgs.forEach(function(m){ if (m.id > _inboxLastMsgId) _inboxLastMsgId = m.id; });
        var i = 0;
        while (i < msgs.length) {
            var m = msgs[i];
            var photoMatch = m.body.match(/^\[photo:([^\]]+)\](\n[\s\S]*)?$/);
            if (photoMatch) {
                var photos = [photoMatch[1]];
                var caption = photoMatch[2] ? photoMatch[2].trim() : '';
                var j = i + 1;
                while (j < msgs.length) {
                    var n = msgs[j];
                    var nm = n.body.match(/^\[photo:([^\]]+)\](\n[\s\S]*)?$/);
                    if (nm && n.direction === m.direction && String(n.author_id) === String(m.author_id)
                        && Math.abs(new Date(n.created_at) - new Date(m.created_at)) <= 60000) {
                        photos.push(nm[1]);
                        j++;
                    } else break;
                }
                var dirCls = m.direction === 'in' ? 'in' : (m.is_internal ? 'internal' : 'out');
                var senderLabel = m.sender_name || 'Manager';
                if (m.is_internal) senderLabel = '<span style="color:#ca8a04;font-weight:600;"><?= addslashes(mb_convert_case($cL['label_internal'] ?? 'notiță', MB_CASE_TITLE, 'UTF-8')) ?></span> · ' + senderLabel;
                var t = new Date(m.created_at);
                var time = ('0'+t.getDate()).slice(-2)+'.'+('0'+(t.getMonth()+1)).slice(-2)+' '+('0'+t.getHours()).slice(-2)+':'+('0'+t.getMinutes()).slice(-2);
                body_el.appendChild(inboxRenderPhotoGroup(photos, caption, dirCls, senderLabel, time));
                i = j;
            } else {
                appendMessage(m.body, m.direction === 'in' ? 'in' : (m.is_internal ? 'internal' : 'out'), m.sender_name || 'Manager', m.advert_url, m.advert_title);
                i++;
            }
        }
        body_el.scrollTop = 99999;
    }).catch(function(){});
}

// Poll for new messages every 8 seconds
setInterval(function() {
    if (_inboxSending) return;
    inboxPoll();
}, 8000);
</script>
