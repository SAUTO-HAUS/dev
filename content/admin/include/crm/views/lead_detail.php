<?php defined('_DOIT') or die('Restricted access');

require_once(_ADM_INCL.'/crm/crm_lang.php');

$lang_url       = $_COOKIE['lang'] ?? 'ro';
$admin_dir_name = 'adminsauto';
$lead_id        = (int)($_GET['id'] ?? 0);
$lead           = $lead_id ? crm_get_lead($db, $prefx, $lead_id) : null;

if (!$lead) {
    echo '<div class="crm-notice error" style="margin:2rem;">' . htmlspecialchars($cL['lead_not_found'] ?? 'Lead negăsit.') . '</div>';
    return;
}

$can_see_all = crm_can_see_all($user_role, $user_id);
if (!$can_see_all && $lead->owner_id && (int)$lead->owner_id !== (int)$user_id) {
    echo '<div class="crm-notice error" style="margin:2rem;">' . htmlspecialchars($cL['access_restricted'] ?? 'Acces restricționat.') . '</div>';
    return;
}

$phone_fmt = crm_format_phone($lead->phone);
$src_color = $lead->source_color ?: '#888';
$src_name  = $lead->source_name  ?: ($cL['source_unknown'] ?? 'Sursă necunoscută');
$back_url  = "/$lang_url/$admin_dir_name/crm/leads?dept={$lead->department}";

// Inbox session (needed for source label below)
$inbox_stmt = $db->prepare("SELECT * FROM {$prefx}_crm_inbox_sessions WHERE lead_id=:lid LIMIT 1");
$inbox_stmt->execute([':lid' => $lead_id]);
$inbox_sess = $inbox_stmt->fetchObject() ?: null;

// Build readable source label from inbox session (same logic as leads.php)
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
if ($inbox_sess) {
    $inbox_ch = $inbox_sess->channel ?? null;
    $inbox_pg = $inbox_sess->page_id ?? null;
    $ch_label = $inbox_ch ? ($inbox_ch_labels[$inbox_ch] ?? ucfirst($inbox_ch)) : null;
    $pg_label = ($inbox_pg && isset($inbox_pg_labels[$inbox_pg])) ? $inbox_pg_labels[$inbox_pg] : null;
    if ($ch_label) {
        $src_name  = 'Mesaj · ' . $ch_label . ($pg_label ? ' · ' . $pg_label : '');
        $src_color = '#888';
    }
} elseif ($lead->origin === 'call' && !$lead->source_name) {
    $src_name  = 'Apel';
    $src_color = '#888';
}

// Messages (comments)
$msgs_stmt = $db->prepare("SELECT m.*, u.name AS user_name FROM {$prefx}_crm_messages m LEFT JOIN {$prefx}_adm_usr u ON u.id = m.author_id WHERE m.lead_id = :lid ORDER BY m.created_at ASC");
$msgs_stmt->execute([':lid' => $lead_id]);
$messages = $msgs_stmt->fetchAll(PDO::FETCH_OBJ);

// Call logs
$calls_stmt = $db->prepare("SELECT * FROM {$prefx}_crm_call_logs WHERE lead_id = :lid ORDER BY start_at DESC LIMIT 20");
$calls_stmt->execute([':lid' => $lead_id]);
$call_logs = $calls_stmt->fetchAll(PDO::FETCH_OBJ);

// Owner
$owner_name = '';
if ($lead->owner_id) {
    $os = $db->prepare("SELECT name FROM {$prefx}_adm_usr WHERE id = :id LIMIT 1");
    $os->execute([':id' => $lead->owner_id]);
    $owner_name = $os->fetchColumn() ?: '';
}

$agents_list = $db->query("SELECT id, name FROM {$prefx}_adm_usr WHERE act=1 ORDER BY name")->fetchAll(PDO::FETCH_OBJ);

// Audit
$audit_stmt = $db->prepare("SELECT * FROM {$prefx}_crm_audit_log WHERE entity_type='lead' AND entity_id=:lid ORDER BY created_at ASC LIMIT 50");
$audit_stmt->execute([':lid' => $lead_id]);
$audit_logs = $audit_stmt->fetchAll(PDO::FETCH_OBJ);

// Docs
require_once(_ADM_INCL.'/crm/crm_docs_bridge.php');
$docs_linked = docs_bridge_get_for_lead($db, $prefx, $lead_id);

// Timeline
$timeline = [];
foreach ($call_logs   as $c) $timeline[] = ['type'=>'call',  'ts'=>strtotime($c->start_at),   'data'=>$c];
foreach ($docs_linked as $d) $timeline[] = ['type'=>'doc',   'ts'=>strtotime($d['crtd']),     'data'=>$d];
foreach ($audit_logs  as $a) $timeline[] = ['type'=>'audit', 'ts'=>strtotime($a->created_at), 'data'=>$a];
usort($timeline, fn($a,$b) => $a['ts'] - $b['ts']);

// Tasks
$tasks_stmt = $db->prepare("SELECT * FROM {$prefx}_crm_tasks WHERE lead_id=:lid ORDER BY is_done DESC, created_at ASC");
$tasks_stmt->execute([':lid' => $lead_id]);
$tasks = $tasks_stmt->fetchAll(PDO::FETCH_OBJ);
$tasks_done = array_values(array_filter($tasks, fn($t) => $t->is_done));
$tasks_open = array_values(array_filter($tasks, fn($t) => !$t->is_done));

$show_chat_tab = $inbox_sess !== null;

// Car from catalog
$car_photo_url = '';
$car_label     = $lead->car_label ?? '';
$car_price     = $lead->car_price ?? '';
$car_currency  = $lead->car_currency ?? 'EUR';
$car_id        = (int)($lead->car_id ?? 0);

$car_catalog_type = 'in_stock';
if ($car_id) {
    $cp = $db->prepare("SELECT p.name, p.ff, c.p_path, c.catalog_type FROM {$prefx}_car_pht p JOIN {$prefx}_car_ctlg c ON c.id=p.it_id WHERE p.it_id=:id AND p.main=1 LIMIT 1");
    $cp->execute([':id' => $car_id]);
    $cp_row = $cp->fetchObject();
    if ($cp_row && $cp_row->name) {
        $car_photo_url    = '/media/images/upload/car/' . $cp_row->p_path . '/' . $car_id . '/med/' . $cp_row->name . (!empty($cp_row->ff) ? '.' . $cp_row->ff : '.jpg');
        $car_catalog_type = $cp_row->catalog_type ?? 'in_stock';
    }
}

$dept_label  = $lead->department === 'stock' ? $cL['dept_stock'] : $cL['dept_order'];
$active_tab  = $_GET['tab'] ?? ($show_chat_tab ? 'chat' : 'history');
if (!in_array($active_tab, ['chat','comments','history','tasks'])) $active_tab = 'history';
if ($active_tab === 'chat' && !$show_chat_tab) $active_tab = 'history';

$last_change = date('d M, H:i', strtotime($lead->last_action_at ?? $lead->created_at));
?>
<style>
#crm-lead-wrap { display:flex; height:calc(100vh - 60px); background:#f5f6fa; overflow:hidden; margin-top:0; }

/* ── Left panel ── */
.ld-left { width:350px; flex-shrink:0; background:#fff; border-right:1px solid #f0f0f0; display:flex; flex-direction:column; overflow-y:auto; }
.ld-left-header { display:flex; align-items:center; padding:0.85rem 1rem 0.75rem; border-bottom:1px solid #f5f5f5; position:relative; }
.ld-back-btn { display:inline-flex; align-items:center; gap:0.2rem; font-size:0.78rem; color:#9ca3af; text-decoration:none; padding:0 0.65rem; height:34px; box-sizing:border-box; border-radius:6px; border:1px solid #e5e7eb; background:#fff; transition:all 0.15s; flex-shrink:0; white-space:nowrap; }
.ld-back-btn:hover { background:#f3f4f6; color:#555; }
.ld-left-title { font-size:0.82rem; font-weight:700; color:#333; }
.ld-left-edit { font-size:0.75rem; color:#E61E2D; cursor:pointer; display:flex; align-items:center; gap:0.25rem; text-decoration:none; }
.ld-left-edit:hover { opacity:0.8; }
.ld-close-btn { width:26px; height:26px; border-radius:50%; background:#f5f5f5; border:none; cursor:pointer; display:flex; align-items:center; justify-content:center; color:#888; font-size:1rem; line-height:1; }
.ld-close-btn:hover { background:#e5e5e5; }

.ld-status-bar { display:flex; align-items:center; justify-content:space-between; padding:0.6rem 1rem; border-bottom:1px solid #f5f5f5; }
.ld-status-badge { display:inline-flex; align-items:center; gap:0.4rem; padding:0.3rem 0.75rem; border-radius:20px; font-size:0.78rem; font-weight:700; }
.ld-status-badge.active      { background:#dcfce7; color:#16a34a; }
.ld-status-badge.missed      { background:#fee2e2; color:#dc2626; }
.ld-status-badge.unprocessed { background:#ffedd5; color:#ea580c; }
.ld-status-badge.processed   { background:#dbeafe; color:#2563eb; }
.ld-status-badge.transaction { background:#ede9fe; color:#7c3aed; }
.ld-status-badge.closed      { background:#dcfce7; color:#16a34a; }
.ld-status-badge.junk        { background:#f3f4f6; color:#6b7280; }
.ld-last-change { font-size:0.7rem; color:#aaa; }
.ld-action-btn { width:100%; border-radius:8px; padding:0.5rem; font-size:0.82rem; font-weight:600; cursor:pointer; margin-bottom:0.4rem; transition:filter .15s, box-shadow .15s; }
.ld-action-dark { background:#191919; color:#fff; border:none; }
.ld-action-dark:hover { filter:brightness(1.3); box-shadow:0 2px 8px rgba(0,0,0,0.25); }
.ld-action-green { background:#dcfce7; color:#16a34a; border:1px solid #bbf7d0; }
.ld-action-green:hover { background:#bbf7d0; box-shadow:0 2px 8px rgba(22,163,74,0.2); }
.ld-action-red { background:#fff0f0; color:#dc2626; border:1px solid #fecaca; }
.ld-action-red:hover { background:#fee2e2; box-shadow:0 2px 8px rgba(220,38,38,0.2); }
.ld-doc-link { display:flex; align-items:center; justify-content:center; gap:0.4rem; font-size:0.78rem; color:#555; text-decoration:none; padding:0.4rem 0.6rem; background:#f8f8f8; border-radius:8px; border:1px solid #f0f0f0; transition:background .15s, color .15s, border-color .15s; }
.ld-doc-link:hover { background:#fff0f0; color:#E61E2D; border-color:#fecaca; }

.ld-body { padding:1rem; flex:1; }
.ld-phone-row { display:flex; align-items:center; justify-content:space-between; margin-bottom:1rem; }
.ld-phone { font-size:1.25rem; font-weight:800; color:#E61E2D; letter-spacing:-0.02em; }
.ld-call-btn { display:flex; align-items:center; gap:0.5rem; background:#E61E2D; color:#fff; border:none; border-radius:10px; padding:0.55rem 1.1rem; font-size:0.82rem; font-weight:700; cursor:pointer; white-space:nowrap; box-shadow:0 2px 8px rgba(230,30,45,0.25); transition:background .15s, box-shadow .15s; }
.ld-call-btn:hover { background:#c8181f; box-shadow:0 4px 12px rgba(230,30,45,0.35); }

.ld-field { margin-bottom:0.85rem; }
.ld-field-label { font-size:0.7rem; color:#aaa; font-weight:600; text-transform:uppercase; letter-spacing:.04em; margin-bottom:0.2rem; }
.ld-field-value { font-size:0.88rem; font-weight:700; color:#191919; }
.ld-field-value.empty { color:#bbb; font-weight:400; font-style:italic; }

.ld-car-box { margin-bottom:0.85rem; }
.ld-car-info { display:flex; align-items:baseline; justify-content:space-between; gap:0.5rem; margin-bottom:0.35rem; }
.ld-car-name { font-size:0.88rem; font-weight:700; color:#191919; }
.ld-car-name.empty { color:#bbb; font-weight:400; font-style:italic; }
.ld-car-price { font-size:1rem; font-weight:800; color:#191919; white-space:nowrap; }
.ld-car-photo-wrap { position:relative; margin-top:0.4rem; }
.ld-car-photo { width:100%; height:auto; object-fit:contain; border-radius:8px; display:block; }
.ld-car-type-badge { position:absolute; top:8px; left:8px; font-size:0.7rem; font-weight:700; padding:2px 8px; border-radius:20px; }
.ld-car-type-badge.stock { background:#dcfce7; color:#16a34a; }
.ld-car-type-badge.order { background:#eff6ff; color:#2563eb; }
.ld-car-photo-placeholder { width:100%; height:80px; background:#f5f5f5; border-radius:8px; display:flex; align-items:center; justify-content:center; color:#ccc; font-size:0.75rem; }
.ld-car-link { font-size:0.72rem; color:#E61E2D; text-decoration:none; display:inline-block; margin-top:0.35rem; }
.ld-car-link:hover { text-decoration:underline; }

.ld-edit-btn { display:inline-flex; align-items:center; gap:0.25rem; font-size:0.72rem; color:#aaa; cursor:pointer; background:none; border:none; padding:0; }
.ld-edit-btn:hover { color:#E61E2D; }
.ld-sep { border:none; border-top:1px solid #f0f0f0; margin:0.75rem 0; }

.ld-manager-row { display:flex; align-items:center; justify-content:space-between; }
.ld-manager-name { font-size:0.88rem; font-weight:700; color:#191919; }

/* ── Right panel ── */
.ld-right { flex:1; min-width:0; display:flex; flex-direction:column; overflow:hidden; }
.ld-tabs-bar { display:flex; align-items:center; border-bottom:2px solid #f0f0f0; background:#fff; padding:0 1rem; flex-shrink:0; position:relative; }
.ld-tab { padding:0.75rem 1rem; font-size:0.85rem; font-weight:600; color:#888; cursor:pointer; border-bottom:2px solid transparent; margin-bottom:-2px; white-space:nowrap; background:none; border-top:none; border-left:none; border-right:none; transition:color .15s,border-color .15s; }
.ld-tab:hover { color:#E61E2D; }
.ld-tab.active { color:#E61E2D; border-bottom-color:#E61E2D; background:#fff5f5; }
.ld-tab-count { background:#f0f0f0; color:#666; border-radius:20px; font-size:0.68rem; font-weight:700; padding:0.1rem 0.4rem; margin-left:0.3rem; }
.ld-tab.active .ld-tab-count { background:#fde8e8; color:#E61E2D; }
.ld-tabs-spacer { flex:1; }
.ld-tab-search { display:flex; align-items:center; gap:0.4rem; background:#f5f5f5; border-radius:7px; padding:0.3rem 0.6rem; font-size:0.78rem; color:#aaa; }

.ld-panel { display:none; flex:1; overflow-y:auto; }
.ld-panel.active { display:flex; flex-direction:column; }

/* History */
.ld-timeline { padding:1.25rem; display:flex; flex-direction:column; gap:0; flex:1; }
.ld-tl-item { display:flex; gap:0.75rem; padding:0.65rem 0; border-bottom:1px solid #f8f8f8; align-items:flex-start; }
.ld-tl-item:last-child { border-bottom:none; }
.ld-tl-dot { width:32px; height:32px; border-radius:50%; display:flex; align-items:center; justify-content:center; flex-shrink:0; font-size:0.62rem; font-weight:700; color:#fff; }
.ld-tl-body { flex:1; min-width:0; }
.ld-tl-meta { font-size:0.7rem; color:#bbb; margin-bottom:0.2rem; }
.ld-tl-text { font-size:0.82rem; color:#333; line-height:1.5; }
.ld-tl-empty { text-align:center; color:#bbb; font-size:0.82rem; padding:3rem 0; }

/* Comments */
.ld-comments { padding:1rem 1.25rem; display:flex; flex-direction:column; gap:0; flex:1; }
.ld-comment { display:flex; gap:0.6rem; padding:0.85rem 0; border-bottom:1px solid #f0f0f0; }
.ld-comment:last-child { border-bottom:none; }
.ld-comment-av { width:32px; height:32px; border-radius:50%; display:flex; align-items:center; justify-content:center; color:#fff; font-size:0.75rem; font-weight:700; flex-shrink:0; }
.ld-comment-body { flex:1; }
.ld-comment-meta { font-size:0.7rem; color:#aaa; margin-bottom:0.3rem; display:flex; justify-content:space-between; align-items:center; }
.ld-comment-text { font-size:0.83rem; color:#333; background:#f5f5f5; border-radius:0 8px 8px 8px; padding:0.55rem 0.75rem; line-height:1.5; }
.ld-comment-form { border-top:2px solid #f0f0f0; padding:0.75rem 1.25rem; display:flex; gap:0.5rem; align-items:flex-end; flex-shrink:0; background:#fff; }
.ld-comment-form textarea { flex:1; border:1px solid #e5e5e5; border-radius:8px; padding:0.6rem 0.75rem; font-size:0.83rem; resize:none; height:40px; min-height:40px; max-height:40px; font-family:inherit; outline:none; box-sizing:border-box; }
.ld-comment-form textarea:focus { border-color:#E61E2D; }
.ld-send-btn { background:#E61E2D; color:#fff; border:none; border-radius:8px; padding:0.6rem 1rem; font-size:0.83rem; font-weight:600; cursor:pointer; white-space:nowrap; display:flex; align-items:center; gap:0.4rem; }
.ld-send-btn:hover { background:#c8181f; }

/* Tasks */
.ld-tasks { padding:1rem 1.25rem; display:flex; flex-direction:column; gap:0.6rem; flex:1; }
.ld-task { display:flex; align-items:flex-start; gap:0.6rem; padding:0.7rem 0.85rem; border-radius:10px; border:1px solid #f0f0f0; background:#fafafa; }
.ld-task.done { opacity:0.55; }
.ld-task-circle { width:18px; height:18px; border-radius:50%; border:2px solid #d1d5db; display:flex; align-items:center; justify-content:center; flex-shrink:0; cursor:pointer; margin-top:2px; transition:all .15s; }
.ld-task-circle.done { background:#16a34a; border-color:#16a34a; }
.ld-task-body { flex:1; }
.ld-task-text { font-size:0.83rem; color:#333; line-height:1.5; word-break:break-word; overflow-wrap:break-word; }
.ld-task.done .ld-task-text { text-decoration:line-through; color:#aaa; }
.ld-task-meta { font-size:0.7rem; color:#bbb; margin-top:0.15rem; }
.ld-task-btn { font-size:0.72rem; font-weight:600; color:#16a34a; background:#dcfce7; border:none; border-radius:6px; padding:0.45rem 0.75rem; cursor:pointer; flex-shrink:0; white-space:nowrap; align-self:center; }
.ld-task-btn:hover { background:#bbf7d0; }
.ld-task-undo-btn { font-size:1.1rem; color:#aaa; background:none; border:none; cursor:pointer; flex-shrink:0; padding:0; width:22px; height:22px; display:flex; align-items:center; justify-content:center; border-radius:5px; align-self:center; }
.ld-task-undo-btn:hover { background:#f0f0f0; color:#555; }
@media (max-width:1024px) { .ld-task-undo-btn { width:40px !important; height:40px !important; font-size:1.8rem !important; padding:0 !important; } }
.ld-task-form { border-top:1px solid #f0f0f0; padding:0.75rem 1.25rem; display:flex; gap:0.5rem; align-items:flex-end; flex-shrink:0; background:#fff; }
.ld-task-form input { flex:1; border:1px solid #e5e5e5; border-radius:8px; padding:0.6rem 0.75rem; font-size:0.83rem; font-family:inherit; outline:none; box-sizing:border-box; }
.ld-task-form input:focus { border-color:#E61E2D; }
.ld-task-add { background:#E61E2D; color:#fff; border:none; border-radius:8px; padding:0.6rem 1rem; font-size:0.83rem; font-weight:600; cursor:pointer; white-space:nowrap; }
.ld-task-add:hover { background:#c8181f; }
.ld-tasks-section-label { font-size:0.7rem; color:#bbb; font-weight:700; text-transform:uppercase; letter-spacing:.04em; padding:0.5rem 0 0.25rem; }

/* Chat */
.ld-chat-panel { padding:2rem; text-align:center; flex:1; display:flex; flex-direction:column; align-items:center; justify-content:center; gap:0.75rem; }

/* Inline edit forms */
.ld-inline-form { display:none; flex-direction:column; gap:0.4rem; margin-top:0.4rem; }
.ld-inline-form.active { display:flex; }
.ld-inline-form input, .ld-inline-form select { border:1px solid #e0e0e0; border-radius:7px; padding:0.35rem 0.6rem; font-size:0.83rem; outline:none; width:100%; box-sizing:border-box; }
.ld-inline-form input:focus, .ld-inline-form select:focus { border-color:#E61E2D; }
.ld-inline-btns { display:flex; gap:0.4rem; }
.ld-inline-save { background:#E61E2D; color:#fff; border:none; border-radius:7px; padding:0.3rem 0.75rem; font-size:0.78rem; font-weight:600; cursor:pointer; }
.ld-inline-cancel { background:#f0f0f0; color:#555; border:none; border-radius:7px; padding:0.3rem 0.6rem; font-size:0.78rem; cursor:pointer; }
.ld-input-row { display:flex; align-items:center; gap:0.35rem; }
.ld-sq-save, .ld-sq-cancel { width:34px; height:34px; min-width:34px; border:none; border-radius:7px; font-size:0.85rem; font-weight:700; cursor:pointer; display:flex; align-items:center; justify-content:center; flex-shrink:0; }
.ld-sq-save { background:#E61E2D; color:#fff; }
.ld-sq-save:hover { background:#c8181f; }
.ld-sq-cancel { background:#f0f0f0; color:#555; }
.ld-sq-cancel:hover { background:#e0e0e0; }

/* Car edit form sections */
.ld-car-edit-section { display:flex; flex-direction:column; gap:0.2rem; }
.ld-car-edit-label { font-size:0.68rem; font-weight:700; color:#aaa; text-transform:uppercase; letter-spacing:.04em; }
.ld-car-edit-divider { display:flex; align-items:center; gap:0.5rem; margin:0.2rem 0; }
.ld-car-edit-divider::before, .ld-car-edit-divider::after { content:''; flex:1; height:1px; background:#f0f0f0; }
.ld-car-edit-divider span { font-size:0.68rem; color:#ccc; white-space:nowrap; }

/* Car search dropdown */
.ld-car-search-wrap { position:relative; }
.ld-car-search-results { position:absolute; top:100%; left:0; right:0; background:#fff; border:1px solid #e0e0e0; border-radius:8px; box-shadow:0 4px 12px rgba(0,0,0,.1); z-index:100; max-height:200px; overflow-y:auto; display:none; }
.ld-car-search-results.open { display:block; }
.ld-car-result-item { padding:0.5rem 0.75rem; cursor:pointer; font-size:0.82rem; border-bottom:1px solid #f5f5f5; }
.ld-car-result-item:last-child { border-bottom:none; }
.ld-car-result-item:hover { background:#fde8e8; color:#E61E2D; }
.ld-car-result-item.selected { background:#fde8e8; color:#E61E2D; font-weight:600; }

/* Transcript */
.ld-transcript { margin-top:0.5rem; background:#f0f7ff; border-left:3px solid #229ED9; border-radius:0 6px 6px 0; padding:0.4rem 0.65rem; }
.ld-transcript-label { font-size:0.65rem; font-weight:700; color:#229ED9; margin-bottom:0.2rem; text-transform:uppercase; }
.ld-transcript-text { font-size:0.78rem; color:#333; line-height:1.5; white-space:pre-wrap; }
audio { height:26px; margin-top:0.3rem; }

.ld-del-btn { background:none; border:none; cursor:pointer; padding:0 0.15rem; opacity:.3; transition:opacity .15s; }
.ld-del-btn:hover { opacity:1; }
@media (max-width:1024px) { .ld-del-btn { opacity:.6; padding:0.3rem; } .ld-del-btn img { width:28px !important; height:28px !important; } }
</style>

<!-- MOBILE TABS (injectat doar pe mobile via JS) -->
<div id="ld-mobile-tabs" style="display:none;">
    <a href="<?= $back_url ?>" class="ld-back-btn" style="flex-shrink:0;margin:6px 4px 6px 8px;">
        <svg width="13" height="13" viewBox="0 0 24 24" fill="none"><path fill-rule="evenodd" clip-rule="evenodd" d="M6.3508 12.7499L11.2096 17.4615L10.1654 18.5383L3.42264 11.9999L10.1654 5.46148L11.2096 6.53833L6.3508 11.2499L21 11.2499L21 12.7499L6.3508 12.7499Z" fill="currentColor"/></svg>
    </a>
    <button class="ld-tab active" data-ldm="despre" onclick="ldMobileTab('despre')"><?= $cL['lead_about'] ?? 'Despre' ?></button>
    <?php if ($show_chat_tab): ?>
    <button class="ld-tab" data-ldm="chat" onclick="ldMobileTab('chat')"><?= $cL['tab_chat'] ?? 'Chat' ?></button>
    <?php endif; ?>
    <button class="ld-tab" data-ldm="comments" onclick="ldMobileTab('comments')">
        <?= $cL['tab_comments'] ?? 'Comentarii' ?><?php if (count($messages)): ?><span class="ld-tab-count"><?= count($messages) ?></span><?php endif; ?>
    </button>
    <button class="ld-tab" data-ldm="history" onclick="ldMobileTab('history')"><?= $cL['tab_history'] ?? 'Istoric' ?></button>
    <button class="ld-tab" data-ldm="tasks" onclick="ldMobileTab('tasks')">
        <?= $cL['tab_tasks'] ?? 'Sarcini' ?><?php if (count($tasks_open)): ?><span class="ld-tab-count"><?= count($tasks_open) ?></span><?php endif; ?>
    </button>
</div>

<div id="crm-lead-wrap">

    <!-- LEFT PANEL -->
    <div class="ld-left">

        <!-- Header -->
        <div class="ld-left-header">
            <a href="<?= $back_url ?>" class="ld-back-btn">
                <svg width="13" height="13" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg"><path fill-rule="evenodd" clip-rule="evenodd" d="M6.3508 12.7499L11.2096 17.4615L10.1654 18.5383L3.42264 11.9999L10.1654 5.46148L11.2096 6.53833L6.3508 11.2499L21 11.2499L21 12.7499L6.3508 12.7499Z" fill="currentColor"></path></svg>
                <?= $cL['back'] ?? 'Înapoi' ?>
            </a>
            <span class="ld-left-title" style="position:absolute;left:50%;transform:translateX(-50%);"><?= $cL['lead_about'] ?? 'Despre Lead' ?></span>
        </div>

        <!-- Status bar -->
        <div class="ld-status-bar">
            <span class="ld-status-badge <?= $lead->status ?>">
                <?= crm_status_label($lead->status, $crm_lang) ?>
            </span>
            <span class="ld-last-change"><?= $last_change ?></span>
        </div>

        <!-- Body -->
        <div class="ld-body">

            <!-- Phone + Call -->
            <div class="ld-phone-row">
                <span class="ld-phone" id="ld-phone-txt"><?= $phone_fmt ?></span>
                <button class="ld-call-btn ld-call-btn-mobile" onclick="crmClickToCall('<?= htmlspecialchars($lead->phone) ?>')" id="ld-call-btn">
                    <img src="/content/admin/include/crm/icons/phone-call.svg" width="13" height="13" style="filter:invert(1);">
                    <?= $cL['btn_call'] ?? 'Позвонить' ?>
                </button>
                <button class="ld-edit-btn ld-edit-phone-btn" onclick="ldToggleEdit('phone')" title="Editează telefon">
                    <img src="/content/admin/include/crm/icons/write.svg" width="11" height="11"> <?= $cL['lead_edit'] ?? 'Editează' ?>
                </button>
            </div>
            <div class="ld-inline-form" id="edit-phone">
                <div class="ld-input-row">
                    <input type="text" id="ld-phone-inp" value="<?= htmlspecialchars($lead->phone ?? '') ?>" placeholder="+373XXXXXXXX" onkeydown="if(event.key==='Enter')ldSavePhone()" style="flex:1;">
                    <button class="ld-sq-save" onclick="ldSavePhone()" title="Salvează">✓</button>
                    <button class="ld-sq-cancel" onclick="ldToggleEdit('phone')" title="Anulează">✕</button>
                </div>
            </div>

            <!-- Manager -->
            <div class="ld-field">
                <div class="ld-field-label"><?= $cL['lbl_manager'] ?? 'Manager' ?></div>
                <div class="ld-manager-row">
                    <span class="ld-field-value <?= $owner_name ? '' : 'empty' ?>" id="ld-owner-name-txt">
                        <?= htmlspecialchars($owner_name ?: ($cL['not_set'] ?? '—')) ?>
                    </span>
                    <button class="ld-edit-btn" onclick="ldToggleEdit('owner')">
                        <img src="/content/admin/include/crm/icons/write.svg" width="11" height="11"> <?= $cL['lead_edit'] ?? 'Editează' ?>
                    </button>
                </div>
                <div class="ld-inline-form" id="edit-owner">
                    <div class="ld-input-row">
                        <select id="ld-owner-select" style="flex:1;">
                            <option value="0">—</option>
                            <?php foreach ($agents_list as $ag): ?>
                            <option value="<?= $ag->id ?>" <?= (int)$lead->owner_id===(int)$ag->id?'selected':'' ?>><?= htmlspecialchars($ag->name) ?></option>
                            <?php endforeach; ?>
                        </select>
                        <button class="ld-sq-save" onclick="ldSaveOwner()" title="Salvează">✓</button>
                        <button class="ld-sq-cancel" onclick="ldToggleEdit('owner')" title="Anulează">✕</button>
                    </div>
                </div>
            </div>

            <hr class="ld-sep">

            <!-- Client name -->
            <div class="ld-field">
                <div class="ld-field-label"><?= $cL['lbl_client'] ?? 'Client' ?></div>
                <div style="display:flex;align-items:center;justify-content:space-between;gap:0.4rem;">
                    <span class="ld-field-value <?= $lead->client_name ? '' : 'empty' ?>" id="ld-client-name-txt">
                        <?= htmlspecialchars($lead->client_name ?: ($cL['not_set'] ?? '—')) ?>
                    </span>
                    <button class="ld-edit-btn" onclick="ldToggleEdit('client-name')">
                        <img src="/content/admin/include/crm/icons/write.svg" width="11" height="11"> <?= $cL['lead_edit'] ?? 'Editează' ?>
                    </button>
                </div>
                <div class="ld-inline-form" id="edit-client-name">
                    <div class="ld-input-row">
                        <input type="text" id="ld-client-name-inp" value="<?= htmlspecialchars($lead->client_name ?? '') ?>" onkeydown="if(event.key==='Enter')ldSaveName()" style="flex:1;">
                        <button class="ld-sq-save" onclick="ldSaveName()" title="Salvează">✓</button>
                        <button class="ld-sq-cancel" onclick="ldToggleEdit('client-name')" title="Anulează">✕</button>
                    </div>
                </div>
            </div>

            <hr class="ld-sep">

            <!-- Car -->
            <div class="ld-field">
                <div class="ld-field-label"><?= $cL['lbl_car'] ?? 'Заказ' ?></div>

                <div id="ld-car-display">
                    <div style="display:flex;align-items:center;justify-content:space-between;gap:0.4rem;">
                        <span class="ld-car-name <?= $car_label ? '' : 'empty' ?>" id="ld-car-name-txt">
                            <?= htmlspecialchars($car_label ?: ($cL['not_set'] ?? '—')) ?>
                        </span>
                        <button class="ld-edit-btn" onclick="ldToggleEdit('car')">
                            <img src="/content/admin/include/crm/icons/write.svg" width="11" height="11"> <?= $cL['lead_edit'] ?? 'Editează' ?>
                        </button>
                    </div>
                    <?php if ($car_price): ?>
                    <div><span class="ld-car-price" id="ld-car-price-txt"><?= number_format((float)$car_price, 0, '.', ' ') ?> <?= htmlspecialchars($car_currency) ?></span></div>
                    <?php endif; ?>
                    <?php if ($car_photo_url): ?>
                    <div class="ld-car-photo-wrap">
                        <?php if ($car_id): ?>
                        <a href="/<?= $car_catalog_type === 'in_stock' ? 'cars' : 'ordercars' ?>/<?= $car_id ?>" target="_blank">
                        <?php endif; ?>
                        <img src="<?= htmlspecialchars($car_photo_url) ?>" class="ld-car-photo" alt="car" onerror="this.style.display='none'" style="<?= $car_id ? 'cursor:pointer;' : '' ?>">
                        <?php if ($car_id): ?>
                        </a>
                        <?php endif; ?>
                        <?php $is_stock = $car_catalog_type === 'in_stock'; ?>
                        <span class="ld-car-type-badge <?= $is_stock ? 'stock' : 'order' ?>">
                            <?= $is_stock ? ($cL['car_in_stock']??'Stoc') : ($cL['car_on_order']??'Comandă') ?>
                        </span>
                    </div>
                    <?php endif; ?>
                </div>

                <div class="ld-inline-form" id="edit-car">
                    <input type="hidden" id="ld-car-id-val" value="<?= $car_id ?>">

                    <div class="ld-car-edit-section">
                        <div class="ld-car-edit-label"><?= $cL['car_search_section'] ?? 'Caută în catalog' ?></div>
                        <div class="ld-car-search-wrap">
                            <input type="text" id="ld-car-search" placeholder="<?= htmlspecialchars($cL['car_search_ph'] ?? 'ex: Renault Logan 2020') ?>" autocomplete="off" oninput="ldCarSearch(this.value)">
                            <div class="ld-car-search-results" id="ld-car-results"></div>
                        </div>
                    </div>

                    <div class="ld-car-edit-divider"><span><?= $cL['car_or_manual'] ?? 'sau manual' ?></span></div>

                    <div class="ld-car-edit-section">
                        <div class="ld-car-edit-label"><?= $cL['car_make_model'] ?? 'Marcă &amp; Model' ?></div>
                        <input type="text" id="ld-car-label-inp" value="<?= htmlspecialchars($car_label) ?>" placeholder="<?= htmlspecialchars($cL['car_manual_ph'] ?? 'ex: Toyota Camry 2021') ?>">
                    </div>

                    <div class="ld-car-edit-section">
                        <div class="ld-car-edit-label"><?= $cL['price'] ?? 'Preț' ?></div>
                        <div style="display:flex;gap:0.4rem;">
                            <input type="number" id="ld-car-price-inp" value="<?= htmlspecialchars($car_price) ?>" placeholder="0" style="flex:1;">
                            <select id="ld-car-cur-inp" style="width:72px;">
                                <option value="EUR" <?= $car_currency==='EUR'?'selected':'' ?>>EUR</option>
                                <option value="USD" <?= $car_currency==='USD'?'selected':'' ?>>USD</option>
                                <option value="MDL" <?= $car_currency==='MDL'?'selected':'' ?>>MDL</option>
                            </select>
                        </div>
                    </div>

                    <div class="ld-input-row" style="margin-top:0.2rem;">
                        <button class="ld-sq-save" onclick="ldSaveCar()" style="flex:1;border-radius:8px;font-size:0.8rem;">✓ <?= $cL['save'] ?? 'Salvează' ?></button>
                        <button class="ld-sq-cancel" onclick="ldToggleEdit('car')" style="border-radius:8px;">✕</button>
                    </div>
                </div>
            </div>

            <hr class="ld-sep">

            <!-- Source -->
            <div class="ld-field">
                <div class="ld-field-label"><?= $cL['col_source'] ?? 'Sursă' ?></div>
                <div style="display:flex;align-items:center;gap:0.4rem;">
                    <span style="width:8px;height:8px;border-radius:50%;background:<?= htmlspecialchars($src_color) ?>;flex-shrink:0;display:inline-block;"></span>
                    <span class="ld-field-value"><?= htmlspecialchars($src_name) ?></span>
                </div>
            </div>

            <hr class="ld-sep">

            <!-- Actions -->
            <?php if ($lead->status === 'unprocessed' && !$lead->owner_id): ?>
            <button onclick="crmTakeLead(<?= $lead_id ?>)" class="ld-action-btn ld-action-dark" style="display:flex;align-items:center;gap:0.4rem;justify-content:center;">
                <img src="/content/admin/include/crm/icons/manager.svg" width="14" height="14" style="filter:invert(1);">
                <?= $cL['btn_take'] ?? 'Preluare' ?>
            </button>
            <?php endif; ?>
            <?php if (crm_can_junk($user_role, $user_id)): ?>
            <button onclick="crmDeleteLead(<?= $lead_id ?>)" class="ld-action-btn ld-action-red">
                <?= $cL['btn_delete_lead'] ?? 'Arhivează' ?>
            </button>
            <?php endif; ?>

            <hr class="ld-sep">

            <!-- Docs -->
            <?php
            $car_parts = explode(' ', trim($car_label), 2);
            $crm_br = $car_parts[0] ?? '';
            $crm_mo = $car_parts[1] ?? '';
            $crm_qs = 'crm_lead_id='.(int)$lead->id.'&crm_phone='.urlencode($lead->phone).'&crm_nm='.urlencode($lead->client_name ?? '').'&crm_br='.urlencode($crm_br).'&crm_mo='.urlencode($crm_mo);
            ?>
            <div style="display:flex;flex-direction:column;gap:0.3rem;">
                <?php if ($lead->department === 'order'): ?>
                    <a href="/<?= $lang_url ?>/<?= $admin_dir_name ?>/docs/ordercars/con_plata?<?= $crm_qs ?>" target="_blank" class="ld-doc-link" onclick="return ldDocCheck(this)">
                        <img src="/content/admin/include/crm/icons/doc.svg" width="13" height="13"> <?= $cL['doc_con_plata'] ?>
                    </a>
                    <a href="/<?= $lang_url ?>/<?= $admin_dir_name ?>/docs/ordercars/con_arvon_com?<?= $crm_qs ?>" target="_blank" class="ld-doc-link" onclick="return ldDocCheck(this)">
                        <img src="/content/admin/include/crm/icons/doc.svg" width="13" height="13"> <?= $cL['doc_con_arvon_com'] ?? 'Contract Arvună la Comandă' ?>
                    </a>
                    <a href="/<?= $lang_url ?>/<?= $admin_dir_name ?>/docs/ordercars/cesionar?<?= $crm_qs ?>" target="_blank" class="ld-doc-link" onclick="return ldDocCheck(this)">
                        <img src="/content/admin/include/crm/icons/doc.svg" width="13" height="13"> <?= $cL['doc_cesionar'] ?? 'Anexă (Cesiune drept de plată)' ?>
                    </a>
                    <a href="/<?= $lang_url ?>/<?= $admin_dir_name ?>/docs/ordercars/act_compensare?<?= $crm_qs ?>" target="_blank" class="ld-doc-link" onclick="return ldDocCheck(this)">
                        <img src="/content/admin/include/crm/icons/doc.svg" width="13" height="13"> <?= $cL['doc_act_compensare'] ?? 'Act de compensare' ?>
                    </a>
                    <a href="/<?= $lang_url ?>/<?= $admin_dir_name ?>/docs/ordercars/vinzare_avans?<?= $crm_qs ?>" target="_blank" class="ld-doc-link" onclick="return ldDocCheck(this)">
                        <img src="/content/admin/include/crm/icons/doc.svg" width="13" height="13"> <?= $cL['doc_con_vc'] ?>
                    </a>
                <?php else: ?>
                    <a href="/<?= $lang_url ?>/<?= $admin_dir_name ?>/docs/cars/con_plata?<?= $crm_qs ?>" target="_blank" class="ld-doc-link" onclick="return ldDocCheck(this)">
                        <img src="/content/admin/include/crm/icons/doc.svg" width="13" height="13"> <?= $cL['doc_con_plata'] ?>
                    </a>
                    <a href="/<?= $lang_url ?>/<?= $admin_dir_name ?>/docs/cars/con_arvon?<?= $crm_qs ?>" target="_blank" class="ld-doc-link" onclick="return ldDocCheck(this)">
                        <img src="/content/admin/include/crm/icons/doc.svg" width="13" height="13"> <?= $cL['doc_con_arvon'] ?>
                    </a>
                    <a href="/<?= $lang_url ?>/<?= $admin_dir_name ?>/docs/cars/cesionar?<?= $crm_qs ?>" target="_blank" class="ld-doc-link" onclick="return ldDocCheck(this)">
                        <img src="/content/admin/include/crm/icons/doc.svg" width="13" height="13"> <?= $cL['doc_cesionar'] ?? 'Anexă (Cesiune drept de plată)' ?>
                    </a>
                    <a href="/<?= $lang_url ?>/<?= $admin_dir_name ?>/docs/cars/act_compensare?<?= $crm_qs ?>" target="_blank" class="ld-doc-link" onclick="return ldDocCheck(this)">
                        <img src="/content/admin/include/crm/icons/doc.svg" width="13" height="13"> <?= $cL['doc_act_compensare'] ?? 'Act de compensare' ?>
                    </a>
                    <a href="/<?= $lang_url ?>/<?= $admin_dir_name ?>/docs/cars/vinzare_avans?<?= $crm_qs ?>" target="_blank" class="ld-doc-link" onclick="return ldDocCheck(this)">
                        <img src="/content/admin/include/crm/icons/doc.svg" width="13" height="13"> <?= $cL['doc_con_vc'] ?>
                    </a>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- RIGHT PANEL -->
    <div class="ld-right">

        <!-- Tabs bar -->
        <div class="ld-tabs-bar">
            <?php if ($show_chat_tab): ?>
            <button class="ld-tab <?= $active_tab==='chat'?'active':'' ?>" data-tab="chat" onclick="ldTab('chat')"><?= $cL['tab_chat'] ?? 'Chat' ?></button>
            <?php endif; ?>
            <button class="ld-tab <?= $active_tab==='comments'?'active':'' ?>" data-tab="comments" onclick="ldTab('comments')">
                <?= $cL['tab_comments'] ?? 'Comentarii' ?>
                <?php if (count($messages)): ?><span class="ld-tab-count"><?= count($messages) ?></span><?php endif; ?>
            </button>
            <button class="ld-tab <?= $active_tab==='history'?'active':'' ?>" data-tab="history" onclick="ldTab('history')"><?= $cL['tab_history'] ?? 'Istoric' ?></button>
            <button class="ld-tab <?= $active_tab==='tasks'?'active':'' ?>" data-tab="tasks" onclick="ldTab('tasks')">
                <?= $cL['tab_tasks'] ?? 'Sarcini' ?>
                <?php if (count($tasks_open)): ?><span class="ld-tab-count"><?= count($tasks_open) ?></span><?php endif; ?>
            </button>
            <div class="ld-tabs-spacer"></div>
            <?php if ($lead->phone): $ld_clean_phone = preg_replace('/\D/', '', $lead->phone); ?>
            <div style="position:absolute;left:50%;transform:translateX(-50%);display:flex;align-items:center;gap:0.5rem;">
                <a href="viber://chat?number=+<?= $ld_clean_phone ?>" title="Viber" class="crm-contact-icon">
                    <img src="/content/admin/include/crm/icons/viber.svg" style="width:30px;height:30px;object-fit:contain;display:block;">
                </a>
                <a href="https://wa.me/<?= $ld_clean_phone ?>" target="_blank" title="WhatsApp" class="crm-contact-icon">
                    <img src="/content/admin/include/crm/icons/whatsapp.svg" style="width:30px;height:30px;object-fit:contain;display:block;">
                </a>
            </div>
            <?php endif; ?>
        </div>

        <!-- TAB: CHAT -->
        <?php if ($show_chat_tab): ?>
        <div class="ld-panel <?= $active_tab==='chat'?'active':'' ?>" id="ldpanel-chat" style="overflow:hidden;">
            <iframe src="/<?= $lang_url ?>/<?= $admin_dir_name ?>/crm/inbox_chat?sid=<?= $inbox_sess->id ?>&embed=1"
                    style="width:100%;height:100%;border:none;flex:1;display:block;"
                    id="ld-chat-iframe"></iframe>
        </div>
        <?php endif; ?>

        <!-- TAB: COMMENTS -->
        <div class="ld-panel <?= $active_tab==='comments'?'active':'' ?>" id="ldpanel-comments">
            <div class="ld-comments">
                <?php if (empty($messages)): ?>
                <div class="ld-tl-empty"><?= $cL['lead_no_comments'] ?? 'Niciun comentariu.' ?></div>
                <?php endif; ?>
                <?php foreach ($messages as $m):
                    $init = strtoupper(substr($m->user_name ?: 'S', 0, 1));
                    $is_int = (bool)$m->is_internal;
                ?>
                <div class="ld-comment">
                    <div class="ld-comment-av" style="background:<?= $is_int?'#ca8a04':'#E61E2D' ?>;"><?= $init ?></div>
                    <div class="ld-comment-body">
                        <div class="ld-comment-meta">
                            <span><?= htmlspecialchars($m->user_name ?: ($cL['label_system']??'System')) ?> · <?= date('d.m.Y H:i', strtotime($m->created_at)) ?><?= $is_int?' · <em style="color:#ca8a04;">'.($cL['label_internal']??'intern').'</em>':'' ?></span>
                            <button class="ld-del-btn" onclick="crmDeleteNote(<?= $m->id ?>)"><img src="/content/admin/include/crm/icons/trash.svg" width="22" height="22"></button>
                        </div>
                        <div class="ld-comment-text"><?= nl2br(htmlspecialchars($m->body)) ?></div>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
            <div class="ld-comment-form">
                <textarea id="crm-note-txt" placeholder="<?= htmlspecialchars($cL['note_placeholder'] ?? 'Scrie un comentariu...') ?>" onkeydown="if(event.key==='Enter'){event.preventDefault();crmSaveNote(<?= $lead_id ?>);}"></textarea>
                <button class="ld-send-btn" onclick="crmSaveNote(<?= $lead_id ?>)">
                    <?= $cL['note_save'] ?? 'Trimite' ?>
                </button>
            </div>
        </div>

        <!-- TAB: HISTORY -->
        <div class="ld-panel <?= $active_tab==='history'?'active':'' ?>" id="ldpanel-history">
            <div class="ld-timeline">
                <?php if (empty($timeline)): ?>
                <div class="ld-tl-empty"><?= $cL['lead_no_interactions'] ?? 'Nicio interacțiune.' ?></div>
                <?php endif; ?>
                <?php foreach ($timeline as $item):
                    $ts_fmt = date('d.m.Y H:i', $item['ts']);
                    if ($item['type'] === 'call'):
                        $c = $item['data'];
                        $dur_str = sprintf('%d:%02d', floor($c->duration/60), $c->duration%60);
                        $is_missed = ($c->status==='missed' || $c->duration==0);
                        $is_out = $c->type==='out';
                        $stt_done = !empty($c->transcript);
                        $stt_status = $c->stt_status ?? 'none';
                        $dot_bg = $is_out ? '#2563eb' : ($is_missed ? '#dc2626' : '#16a34a');
                ?>
                <div class="ld-tl-item">
                    <div class="ld-tl-dot" style="background:<?= $dot_bg ?>;">
                        <img src="/content/admin/include/crm/icons/<?= $is_out?'phone-outgoing.svg':'phone-answered.svg' ?>" width="14" height="14" style="filter:invert(1);">
                    </div>
                    <div class="ld-tl-body">
                        <div class="ld-tl-meta"><?= $ts_fmt ?> · <?= $is_out?htmlspecialchars($cL['call_tab_out']):htmlspecialchars($cL['call_tab_in']) ?> · <?= $dur_str ?><?= $is_missed?' · <span style="color:#dc2626;">['.$cL['call_missed'].']</span>':'' ?></div>
                        <div class="ld-tl-text">
                            <?= htmlspecialchars(crm_format_phone($c->phone)) ?>
                            <?php if ($c->recording_url): ?>
                            <div><audio controls src="<?= htmlspecialchars($c->recording_url) ?>"></audio>
                            <?php if (!$stt_done && $stt_status!=='pending'): ?>
                            <span id="stt-status-<?= $c->id ?>" style="font-size:0.7rem;color:#f97316;"><?= $cL['lead_transcribing']??'Transcriere...' ?></span>
                            <?php endif; ?></div>
                            <?php endif; ?>
                            <?php if ($stt_done): ?>
                            <div class="ld-transcript" id="stt-<?= $c->id ?>">
                                <div class="ld-transcript-label"><?= $cL['lead_whisper']??'Transcriere' ?></div>
                                <div class="ld-transcript-text"><?= nl2br(htmlspecialchars($c->transcript)) ?></div>
                            </div>
                            <?php else: ?><div id="stt-<?= $c->id ?>" style="display:none;"></div><?php endif; ?>
                        </div>
                    </div>
                </div>
                <?php elseif ($item['type'] === 'audit'):
                    $a = $item['data'];
                    $old_v = $a->old_value ?? '';
                    $new_v = $a->new_value ?? '';
                    if (in_array($a->action, ['set_owner','take_lead','auto_takeover','manual_takeover'])) {
                        $agent_map = array_column($agents_list, 'name', 'id');
                        if ($old_v===''||$old_v==='pool') $old_v=$cL['audit_pool']??'Pool'; elseif(isset($agent_map[(int)$old_v])) $old_v=$agent_map[(int)$old_v];
                        if ($new_v===''||$new_v==='pool') $new_v=$cL['audit_pool']??'Pool'; elseif(isset($agent_map[(int)$new_v])) $new_v=$agent_map[(int)$new_v];
                    }
                    if (in_array($a->action, ['change_status','auto_processed','lead_created_from_call'])) {
                        if ($old_v) $old_v=crm_status_label($old_v,$crm_lang);
                        if ($new_v) $new_v=crm_status_label($new_v,$crm_lang);
                    }
                    if (in_array($a->action, ['doc_status_change','doc_linked'])) {
                        // parse "doc_f=con_plata doc_id=5618 active→transaction"
                        preg_match('/doc_f=(\S+)/', $old_v, $mf);
                        preg_match('/doc_id=(\d+)/', $old_v, $mid);
                        $parsed_doc_f  = isset($mf[1])  ? docs_bridge_doc_label($mf[1])  : '';
                        $parsed_doc_id = isset($mid[1]) ? (int)$mid[1] : 0;
                        if ($a->action === 'doc_status_change') {
                            $old_v = $parsed_doc_f ? $parsed_doc_f.($parsed_doc_id ? ' #'.$parsed_doc_id : '') : '';
                            $new_v = $new_v ? crm_status_label($new_v, $crm_lang) : '';
                        } else {
                            $old_v = '';
                            $new_v = $parsed_doc_f ? $parsed_doc_f.($parsed_doc_id ? ' #'.$parsed_doc_id : '') : $new_v;
                        }
                    }
                ?>
                <div class="ld-tl-item">
                    <div class="ld-tl-dot" style="background:#94a3b8;font-size:0.55rem;"><?= $cL['label_system']??'SYS' ?></div>
                    <div class="ld-tl-body">
                        <div class="ld-tl-meta"><?= $ts_fmt ?><?= $a->user_name ? ' · '.htmlspecialchars($a->user_name) : '' ?></div>
                        <div class="ld-tl-text" style="color:#64748b;font-size:0.78rem;">
                            <?= htmlspecialchars($cL['audit_'.$a->action]??$a->action) ?>
                            <?php if ($old_v!==''&&$new_v!==''): ?>: <?= htmlspecialchars($old_v) ?> → <strong><?= htmlspecialchars($new_v) ?></strong>
                            <?php elseif ($new_v!==''&&!in_array($a->action,['add_note','delete_note','lead_created_from_call','archived','restored','mark_junk'])): ?>: <strong><?= htmlspecialchars($new_v) ?></strong><?php endif; ?>
                        </div>
                    </div>
                </div>
                <?php elseif ($item['type'] === 'doc'):
                    $d = $item['data'];
                    $doc_label = docs_bridge_doc_label($d['f']);
                    $doc_nr = ($d['abr']??'').($d['y']??'').($d['q']??'').'/'.($d['n']??'');
                    $doc_color = in_array($d['f'],['vinzare_sauto','vinzare_proc'])?'#16a34a':(in_array($d['f'],['con_arvon','con_arvon_com','vinzare_avans'])?'#7c3aed':'#f59e0b');
                ?>
                <div class="ld-tl-item">
                    <div class="ld-tl-dot" style="background:<?= $doc_color ?>;"><img src="/content/admin/include/crm/icons/doc.svg" width="14" height="14" style="filter:invert(1);"></div>
                    <div class="ld-tl-body">
                        <div class="ld-tl-meta"><?= $ts_fmt ?> · <?= $cL['lead_doc_created']??'Document creat' ?></div>
                        <div class="ld-tl-text"><strong><?= htmlspecialchars($doc_label) ?></strong><?php if(trim($doc_nr,'/')): ?> <span style="color:#aaa;font-size:0.73rem;">nr.<?= htmlspecialchars($doc_nr) ?></span><?php endif; ?></div>
                    </div>
                </div>
                <?php endif; endforeach; ?>
            </div>
        </div>

        <!-- TAB: TASKS -->
        <div class="ld-panel <?= $active_tab==='tasks'?'active':'' ?>" id="ldpanel-tasks">
            <div class="ld-tasks">
                <?php if (empty($tasks)): ?>
                <div class="ld-tl-empty"><?= $cL['lead_no_tasks']??'Nicio sarcină.' ?></div>
                <?php endif; ?>
                <?php if (!empty($tasks_done)): ?>
                <div class="ld-tasks-section-label"><?= $cL['task_done']??'Finalizate' ?></div>
                <?php foreach ($tasks_done as $t): ?>
                <div class="ld-task done">
                    <div class="ld-task-circle done"><svg width="10" height="10" viewBox="0 0 12 12" fill="none"><path d="M2 6l3 3 5-5" stroke="#fff" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/></svg></div>
                    <div class="ld-task-body">
                        <div class="ld-task-text"><?= nl2br(htmlspecialchars($t->body)) ?></div>
                        <div class="ld-task-meta"><?= htmlspecialchars($t->author_name??($cL['label_system']??'System')) ?> · <?= date('d M, H:i', strtotime($t->created_at)) ?></div>
                    </div>
                    <button class="ld-task-undo-btn" onclick="crmTaskUndone(<?= $t->id ?>)" title="<?= htmlspecialchars($cL['task_reopen']??'Redeschide') ?>">↩</button>
                </div>
                <?php endforeach; ?>
                <?php if (!empty($tasks_open)): ?>
                <div class="ld-tasks-section-label"><?= $cL['tab_tasks']??'Sarcini' ?></div>
                <?php endif; ?>
                <?php endif; ?>
                <?php foreach ($tasks_open as $t): ?>
                <div class="ld-task" id="task-<?= $t->id ?>">
                    <div class="ld-task-circle" onclick="crmTaskDone(<?= $t->id ?>)"></div>
                    <div class="ld-task-body">
                        <div class="ld-task-text"><?= nl2br(htmlspecialchars($t->body)) ?></div>
                        <div class="ld-task-meta"><?= htmlspecialchars($t->author_name??($cL['label_system']??'System')) ?> · <?= date('d M, H:i', strtotime($t->created_at)) ?></div>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
            <div class="ld-task-form">
                <input type="text" id="crm-task-inp" placeholder="<?= htmlspecialchars($cL['task_placeholder']??'Adaugă sarcină...') ?>" onkeydown="if(event.key==='Enter')crmAddTask(<?= $lead_id ?>)">
                <button class="ld-task-add" onclick="crmAddTask(<?= $lead_id ?>)"><?= $cL['task_add']??'Adaugă' ?></button>
            </div>
        </div>

    </div><!-- /ld-right -->
</div>

<script>
var CRM_LEAD_ID = <?= $lead_id ?>;
var CRM_BACK = '<?= addslashes($back_url) ?>';
var CRM_DEPT = '<?= $lead->department === 'order' ? 'order' : 'stock' ?>';
var CRM_L = {car_in_stock:'<?= addslashes($cL['car_in_stock']??'Stoc') ?>',car_on_order:'<?= addslashes($cL['car_on_order']??'Comandă') ?>'};

function ldDocCheck() {
    var missing = [];
    var phone = document.getElementById('ld-phone-inp');
    if (!phone || !phone.value.trim()) missing.push('Telefon');
    var owner = document.getElementById('ld-owner-select');
    if (!owner || !owner.value || owner.value === '0') missing.push('Manager');
    var name = document.getElementById('ld-client-name-inp');
    if (!name || !name.value.trim()) missing.push('Nume Client');
    var carId = document.getElementById('ld-car-id-val');
    var carLabel = document.getElementById('ld-car-label-inp');
    var hasCar = (carId && carId.value && carId.value !== '0') || (carLabel && carLabel.value.trim());
    if (!hasCar) missing.push('Marcă, Model');
    if (missing.length) {
        alert('Completează câmpurile obligatorii:\n• ' + missing.join('\n• '));
        return false;
    }
    return true;
}

function ldTab(tab) {
    document.querySelectorAll('.ld-tab').forEach(b => b.classList.remove('active'));
    document.querySelectorAll('.ld-panel').forEach(p => p.classList.remove('active'));
    var btn = document.querySelector('.ld-tab[data-tab="'+tab+'"]');
    var panel = document.getElementById('ldpanel-'+tab);
    if (btn) btn.classList.add('active');
    if (panel) panel.classList.add('active');
    history.replaceState(null,'','?id=<?= $lead_id ?>&tab='+tab);
}

function ldToggleEdit(section) {
    var form = document.getElementById('edit-'+section);
    if (!form) return;
    form.classList.toggle('active');
}

function ldEditLead() { ldToggleEdit('client-name'); }

function ldSavePhone() {
    var val = document.getElementById('ld-phone-inp').value.trim();
    if (!val) return;
    fetch('/ajax.php',{method:'POST',headers:{'Content-Type':'application/x-www-form-urlencoded'},
        body:'tp=adm&pg=crm&fn=save_phone&id='+CRM_LEAD_ID+'&phone='+encodeURIComponent(val)
    }).then(r=>r.json()).then(d=>{
        if(d.ok){
            document.getElementById('ld-phone-txt').textContent = d.phone_fmt || val;
            document.getElementById('ld-call-btn').setAttribute('onclick', "crmClickToCall('"+val+"')");
            ldToggleEdit('phone');
        } else alert(d.msg||'Eroare');
    });
}

function ldSaveName() {
    var val = document.getElementById('ld-client-name-inp').value.trim();
    fetch('/ajax.php',{method:'POST',headers:{'Content-Type':'application/x-www-form-urlencoded'},
        body:'tp=adm&pg=crm&fn=save_client_name&id='+CRM_LEAD_ID+'&name='+encodeURIComponent(val)
    }).then(r=>r.json()).then(d=>{
        if(d.ok){
            var el = document.getElementById('ld-client-name-txt');
            el.textContent = val || '—';
            el.className = 'ld-field-value' + (val ? '' : ' empty');
            ldToggleEdit('client-name');
        }
    });
}

function ldSaveOwner() {
    var sel = document.getElementById('ld-owner-select');
    fetch('/ajax.php',{method:'POST',headers:{'Content-Type':'application/x-www-form-urlencoded'},
        body:'tp=adm&pg=crm&fn=set_owner&id='+CRM_LEAD_ID+'&owner_id='+sel.value
    }).then(r=>r.json()).then(d=>{
        if(d.ok){
            document.getElementById('ld-owner-name-txt').textContent = sel.options[sel.selectedIndex].text || '—';
            ldToggleEdit('owner');
        }
    });
}

var ldCarSearchTimer = null;
function ldCarSearch(q) {
    clearTimeout(ldCarSearchTimer);
    var res = document.getElementById('ld-car-results');
    if (q.length < 2) { res.classList.remove('open'); return; }
    ldCarSearchTimer = setTimeout(function() {
        var inp2 = document.getElementById('ld-car-search');
        inp2.style.background = ''; inp2.style.color = ''; inp2.style.fontWeight = '';
        fetch('/ajax.php',{method:'POST',headers:{'Content-Type':'application/x-www-form-urlencoded'},
            body:'tp=adm&pg=crm&fn=car_search&q='+encodeURIComponent(q)+'&dept='+CRM_DEPT
        }).then(r=>r.json()).then(d=>{
            if(!d.ok || !d.items || !d.items.length) { res.innerHTML='<div class="ld-car-result-item" style="color:#aaa;">Niciun rezultat</div>'; res.classList.add('open'); return; }
            res.innerHTML = d.items.map(c =>
                '<div class="ld-car-result-item" onclick="ldSelectCar('+c.id+',\''+c.label.replace(/'/g,"\\'")+ '\','+(c.price||0)+',\''+(c.cur||'')+'\',this)">'+
                '<span style="display:flex;align-items:center;justify-content:space-between;gap:0.5rem;">'+
                '<span>' + c.label + (c.price ? ' <span style="color:#888;font-size:0.78rem;">— ' + c.price + ' ' + (c.cur||'') + '</span>' : '') + '</span>'+
                '<span style="font-size:0.7rem;padding:1px 6px;border-radius:4px;font-weight:600;background:'+(c.stock?'#dcfce7':'#eff6ff')+';color:'+(c.stock?'#16a34a':'#2563eb')+'">'+(c.stock?CRM_L.car_in_stock:CRM_L.car_on_order)+'</span>'+
                '</span></div>'
            ).join('');
            res.classList.add('open');
        });
    }, 300);
}

function ldSelectCar(id, label, price, currency, el) {
    document.getElementById('ld-car-id-val').value = id;
    document.getElementById('ld-car-label-inp').value = label;
    document.getElementById('ld-car-price-inp').value = price;
    document.getElementById('ld-car-cur-inp').value = currency;
    var inp = document.getElementById('ld-car-search');
    inp.value = label;
    inp.style.background = '#fde8e8';
    inp.style.color = '#E61E2D';
    inp.style.fontWeight = '600';
    document.getElementById('ld-car-results').classList.remove('open');
}

function ldSaveCar() {
    var car_id    = document.getElementById('ld-car-id-val').value;
    var car_label = document.getElementById('ld-car-label-inp').value.trim();
    var car_price = document.getElementById('ld-car-price-inp').value;
    var car_cur   = document.getElementById('ld-car-cur-inp').value;
    fetch('/ajax.php',{method:'POST',headers:{'Content-Type':'application/x-www-form-urlencoded'},
        body:'tp=adm&pg=crm&fn=save_car&id='+CRM_LEAD_ID+'&car_id='+car_id+'&car_label='+encodeURIComponent(car_label)+'&car_price='+car_price+'&car_currency='+car_cur
    }).then(r=>r.json()).then(d=>{ if(d.ok) location.reload(); else alert(d.msg||'Eroare'); });
}

function crmClickToCall(phone) {
    fetch('/ajax.php',{method:'POST',headers:{'Content-Type':'application/x-www-form-urlencoded'},
        body:'tp=adm&pg=crm&fn=make_call&id='+CRM_LEAD_ID+'&phone='+encodeURIComponent(phone)
    }).then(r=>r.json()).then(d=>{ if(!d.ok) alert(d.msg||'Eroare PBX'); });
}
function crmTakeLead(id) {
    if (!confirm('<?= addslashes($cL['confirm_take']??'Preiei lead-ul?') ?>')) return;
    fetch('/ajax.php',{method:'POST',headers:{'Content-Type':'application/x-www-form-urlencoded'},body:'tp=adm&pg=crm&fn=take_lead&id='+id}).then(r=>r.json()).then(d=>{ if(d.ok) location.reload(); else alert(d.msg||'Eroare'); });
}
function crmDeleteLead(id) {
    if (!confirm('<?= addslashes($cL['lead_confirm_delete']??'Arhivezi lead-ul?') ?>')) return;
    fetch('/ajax.php',{method:'POST',headers:{'Content-Type':'application/x-www-form-urlencoded'},body:'tp=adm&pg=crm&fn=delete_lead&id='+id}).then(r=>r.json()).then(d=>{ if(d.ok) window.location.href=CRM_BACK; else alert(d.msg||'Eroare'); });
}
function crmChangeStatus(id, status) {
    fetch('/ajax.php',{method:'POST',headers:{'Content-Type':'application/x-www-form-urlencoded'},body:'tp=adm&pg=crm&fn=change_status&id='+id+'&status='+status}).then(r=>r.json()).then(d=>{ if(d.ok) location.reload(); else alert(d.msg||'Eroare'); });
}
function crmSaveNote(id) {
    var txt = document.getElementById('crm-note-txt').value.trim();
    if(!txt) return;
    fetch('/ajax.php',{method:'POST',headers:{'Content-Type':'application/x-www-form-urlencoded'},body:'tp=adm&pg=crm&fn=save_note&id='+id+'&body='+encodeURIComponent(txt)+'&is_internal=0'}).then(r=>r.json()).then(d=>{
        if(d.ok) {
            var url = new URL(location.href);
            url.searchParams.set('tab', 'comments');
            location.href = url.toString();
        } else alert(d.msg||'Eroare');
    });
}
function crmDeleteNote(msgId) {
    fetch('/ajax.php',{method:'POST',headers:{'Content-Type':'application/x-www-form-urlencoded'},body:'tp=adm&pg=crm&fn=delete_note&msg_id='+msgId}).then(r=>r.json()).then(d=>{ if(d.ok){ var u=new URL(location.href); u.searchParams.set('tab','comments'); location.href=u.toString(); } else alert(d.msg||'Eroare'); });
}
function crmAddTask(leadId) {
    var txt = document.getElementById('crm-task-inp').value.trim();
    if(!txt) return;
    fetch('/ajax.php',{method:'POST',headers:{'Content-Type':'application/x-www-form-urlencoded'},body:'tp=adm&pg=crm&fn=add_task&id='+leadId+'&body='+encodeURIComponent(txt)}).then(r=>r.json()).then(d=>{
        if(d.ok) { var u=new URL(location.href); u.searchParams.set('tab','tasks'); location.href=u.toString(); }
        else alert(d.msg||'Eroare');
    });
}
function crmTaskDone(taskId) {
    fetch('/ajax.php',{method:'POST',headers:{'Content-Type':'application/x-www-form-urlencoded'},body:'tp=adm&pg=crm&fn=task_done&task_id='+taskId}).then(r=>r.json()).then(d=>{ if(d.ok){ var u=new URL(location.href); u.searchParams.set('tab','tasks'); location.href=u.toString(); } else alert(d.msg||'Eroare'); });
}
function crmTaskUndone(taskId) {
    fetch('/ajax.php',{method:'POST',headers:{'Content-Type':'application/x-www-form-urlencoded'},body:'tp=adm&pg=crm&fn=task_undone&task_id='+taskId}).then(r=>r.json()).then(d=>{ if(d.ok){ var u=new URL(location.href); u.searchParams.set('tab','tasks'); location.href=u.toString(); } else alert(d.msg||'Eroare'); });
}
function crmTranscribe(callId) {
    fetch('/ajax.php',{method:'POST',headers:{'Content-Type':'application/x-www-form-urlencoded'},body:'tp=adm&pg=crm&fn=stt_transcribe&call_id='+callId}).then(r=>r.json()).then(d=>{
        var st = document.getElementById('stt-status-'+callId);
        if(d.ok && d.transcript){
            var box = document.getElementById('stt-'+callId);
            if(box){ box.innerHTML='<div class="ld-transcript-label"><?= addslashes($cL['lead_whisper']??'Transcriere') ?></div><div class="ld-transcript-text">'+d.transcript.replace(/\n/g,'<br>')+'</div>'; box.style.display=''; }
            if(st) st.remove();
        } else { if(st) st.remove(); }
    });
}
document.addEventListener('DOMContentLoaded', function() {
    document.querySelectorAll('[id^="stt-status-"]').forEach(function(el){
        crmTranscribe(parseInt(el.id.replace('stt-status-','')));
    });
    var tasksDiv = document.querySelector('#ldpanel-tasks .ld-tasks');
    if (tasksDiv) tasksDiv.scrollTop = tasksDiv.scrollHeight;
    document.addEventListener('click', function(e){
        if (!e.target.closest('.ld-car-search-wrap')) {
            var r = document.getElementById('ld-car-results');
            if(r) r.classList.remove('open');
        }
    });

    // Mobile tabs init
    if (window.innerWidth <= 1024) {
        document.getElementById('ld-mobile-tabs').style.display = 'flex';
        var urlTab = new URLSearchParams(location.search).get('tab');
        var validTabs = ['chat','comments','history','tasks'];
        ldMobileTab(validTabs.indexOf(urlTab) !== -1 ? urlTab : 'despre');
    }
});

function ldMobileTab(tab) {
    var left  = document.querySelector('#crm-lead-wrap .ld-left');
    var right = document.querySelector('#crm-lead-wrap .ld-right');

    document.querySelectorAll('#ld-mobile-tabs .ld-tab').forEach(function(b) {
        b.classList.toggle('active', b.getAttribute('data-ldm') === tab);
    });

    if (tab === 'despre') {
        left.classList.add('ld-mobile-active');
        right.classList.remove('ld-mobile-active');
        right.style.height = '';
    } else {
        left.classList.remove('ld-mobile-active');
        right.classList.add('ld-mobile-active');

        // Activează panoul
        document.querySelectorAll('#crm-lead-wrap .ld-panel').forEach(function(p) { p.classList.remove('active'); });
        var panel = document.getElementById('ldpanel-' + tab);
        if (panel) panel.classList.add('active');

        if (tab === 'chat') {
            var tabsBar = document.getElementById('ld-mobile-tabs');
            var headerEl = document.getElementById('header');
            var headerH = headerEl ? headerEl.offsetHeight : 0;
            var h = window.innerHeight - tabsBar.offsetHeight - headerH;
            right.style.height = h + 'px';
            right.style.overflow = 'hidden';
            var chatPanel = document.getElementById('ldpanel-chat');
            if (chatPanel) { chatPanel.style.height = h + 'px'; chatPanel.style.flex = 'none'; }
            var iframe = document.getElementById('ld-chat-iframe');
            if (iframe) { iframe.style.height = h + 'px'; iframe.style.width = '100%'; }
        } else {
            right.style.height = '';
            right.style.overflow = '';
        }
    }
    window.scrollTo(0, 0);
}
</script>
