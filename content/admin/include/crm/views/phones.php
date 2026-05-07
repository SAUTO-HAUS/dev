<?php defined('_DOIT') or die('Restricted access');

require_once(_ADM_INCL.'/crm/crm_lang.php');

$lang_url       = $_COOKIE['lang'] ?? 'ro';
$admin_dir_name = 'adminsauto';
$base_url       = "/$lang_url/$admin_dir_name/crm/phones";

// Load sources for dropdown
$sources = crm_get_sources($db, $prefx);

// Filter
$filter_type = in_array($_GET['type'] ?? '', ['physical','virtual']) ? $_GET['type'] : '';
$filter_q    = trim($_GET['q'] ?? '');

$where_parts = ['1=1'];
$params      = [];

if ($filter_type) {
    $where_parts[] = "p.type = ?";
    $params[]      = $filter_type;
}
if ($filter_q) {
    $where_parts[] = "(p.number LIKE ? OR p.label LIKE ? OR p.purpose LIKE ? OR p.assigned_to LIKE ?)";
    $params[] = "%$filter_q%";
    $params[] = "%$filter_q%";
    $params[] = "%$filter_q%";
    $params[] = "%$filter_q%";
}

$where_sql = implode(' AND ', $where_parts);

$phones_stmt = $db->prepare("
    SELECT p.*, s.name AS source_name, s.color AS source_color
    FROM {$prefx}_crm_phones p
    LEFT JOIN {$prefx}_crm_sources s ON s.id = p.source_id
    WHERE $where_sql
    ORDER BY p.sort_order ASC, p.id ASC
");
$phones_stmt->execute($params);
$phones = $phones_stmt->fetchAll(PDO::FETCH_OBJ);

// Counts
$cnt_all = $db->query("SELECT COUNT(*) FROM {$prefx}_crm_phones")->fetchColumn();
$cnt_phy = $db->query("SELECT COUNT(*) FROM {$prefx}_crm_phones WHERE type='physical'")->fetchColumn();
$cnt_vir = $db->query("SELECT COUNT(*) FROM {$prefx}_crm_phones WHERE type='virtual'")->fetchColumn();
?>

<div id="crm-phones-wrap">

    <!-- Header -->
    <div class="phones-header">
        <div class="phones-header-title">
            <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                <rect x="5" y="2" width="14" height="20" rx="2" ry="2"/>
                <line x1="12" y1="18" x2="12.01" y2="18"/>
            </svg>
            <span><?= htmlspecialchars($cL['phones_title']) ?></span>
        </div>
        <button class="phones-add-btn" onclick="phonesOpenModal(null)">
            + <?= htmlspecialchars($cL['phones_add']) ?>
        </button>
    </div>

    <!-- Filter bar -->
    <form method="GET" action="<?= $base_url ?>" class="phones-filter-bar">
        <a href="<?= $base_url ?>?q=<?= urlencode($filter_q) ?>"
           class="phones-type-btn <?= $filter_type===''?'active':'' ?>">
            <?= $cL['all'] ?? 'Toate' ?> <span class="phones-type-cnt"><?= (int)$cnt_all ?></span>
        </a>
        <a href="<?= $base_url ?>?type=physical&q=<?= urlencode($filter_q) ?>"
           class="phones-type-btn <?= $filter_type==='physical'?'active':'' ?>">
            <?= htmlspecialchars($cL['phones_type_physical']) ?> <span class="phones-type-cnt"><?= (int)$cnt_phy ?></span>
        </a>
        <a href="<?= $base_url ?>?type=virtual&q=<?= urlencode($filter_q) ?>"
           class="phones-type-btn <?= $filter_type==='virtual'?'active':'' ?>">
            <?= htmlspecialchars($cL['phones_type_virtual']) ?> <span class="phones-type-cnt"><?= (int)$cnt_vir ?></span>
        </a>
        <div class="phones-search-wrap">
            <input type="hidden" name="type" value="<?= htmlspecialchars($filter_type) ?>">
            <input type="text" name="q" value="<?= htmlspecialchars($filter_q) ?>"
                   placeholder="<?= htmlspecialchars($cL['call_search_ph']) ?>">
            <span class="phones-search-ico"><img src="/content/admin/include/crm/icons/search.svg" width="14" height="14"></span>
        </div>
    </form>

    <!-- Table -->
    <div class="phones-table-wrap">
        <table class="phones-tbl">
            <thead>
                <tr>
                    <th><?= htmlspecialchars($cL['phones_col_number']) ?></th>
                    <th><?= htmlspecialchars($cL['phones_col_label']) ?></th>
                    <th><?= htmlspecialchars($cL['phones_col_type']) ?></th>
                    <th><?= htmlspecialchars($cL['phones_col_purpose']) ?></th>
                    <th><?= htmlspecialchars($cL['phones_col_assigned']) ?></th>
                    <th><?= htmlspecialchars($cL['phones_col_ext']) ?></th>
                    <th><?= htmlspecialchars($cL['phones_col_source']) ?></th>
                    <th style="width:40px;text-align:center;"><?= htmlspecialchars($cL['phones_col_active']) ?></th>
                    <th style="width:70px;"></th>
                </tr>
            </thead>
            <tbody>
            <?php if (!empty($phones)): ?>
            <?php foreach ($phones as $ph):
                $type_label = $ph->type === 'physical'
                    ? '<span class="phones-badge phones-badge-phy">' . htmlspecialchars($cL['phones_type_physical']) . '</span>'
                    : '<span class="phones-badge phones-badge-vir">' . htmlspecialchars($cL['phones_type_virtual']) . '</span>';
                $src_dot = $ph->source_color
                    ? '<span style="display:inline-block;width:8px;height:8px;border-radius:50%;background:' . htmlspecialchars($ph->source_color) . ';margin-right:4px;"></span>'
                    : '';
            ?>
            <tr class="<?= $ph->active ? '' : 'phones-row-inactive' ?>">
                <td>
                    <span class="phones-number"><?= htmlspecialchars(crm_format_phone($ph->number)) ?></span>
                </td>
                <td class="phones-label-cell">
                    <?= htmlspecialchars($ph->label ?: '—') ?>
                </td>
                <td><?= $type_label ?></td>
                <td class="phones-purpose-cell">
                    <?= htmlspecialchars($ph->purpose ?: '—') ?>
                </td>
                <td style="font-size:0.8rem;color:#555;">
                    <?= htmlspecialchars($ph->assigned_to ?: '—') ?>
                </td>
                <td style="font-size:0.8rem;font-family:monospace;color:#666;">
                    <?= htmlspecialchars($ph->pbx_ext ?: '—') ?>
                </td>
                <td style="font-size:0.8rem;">
                    <?php if ($ph->source_name): ?>
                    <?= $src_dot ?><?= htmlspecialchars($ph->source_name) ?>
                    <?php else: ?>
                    <span style="color:#bbb;">—</span>
                    <?php endif; ?>
                </td>
                <td style="text-align:center;">
                    <span class="phones-active-dot <?= $ph->active ? 'active' : 'inactive' ?>"></span>
                </td>
                <td style="text-align:right;">
                    <button class="phones-edit-btn" onclick="phonesOpenModal(<?= $ph->id ?>)" title="Editează">
                        <img src="/content/admin/include/crm/icons/setting.svg" width="16" height="16">
                    </button>
                </td>
            </tr>
            <?php endforeach; ?>
            <?php else: ?>
            <tr>
                <td colspan="9" style="text-align:center;padding:2.5rem;color:#aaa;font-size:0.85rem;">
                    <?= htmlspecialchars($cL['phones_no_data']) ?>
                </td>
            </tr>
            <?php endif; ?>
            </tbody>
        </table>
    </div>

</div>

<!-- Add/Edit Modal -->
<div id="phones-modal-overlay" class="phones-modal-overlay" onclick="if(event.target===this)phonesCloseModal()">
    <div class="phones-modal">
        <div class="phones-modal-header">
            <span id="phones-modal-title" class="phones-modal-title"><?= htmlspecialchars($cL['phones_add']) ?></span>
            <button class="phones-modal-close" onclick="phonesCloseModal()">✕</button>
        </div>
        <div class="phones-modal-body">
            <input type="hidden" id="pm-id" value="">

            <div class="pm-row">
                <label>Număr <span class="pm-req">*</span></label>
                <input type="tel" id="pm-number" placeholder="ex: 37379600341" style="font-family:monospace;">
            </div>
            <div class="pm-row">
                <label><?= htmlspecialchars($cL['phones_col_label']) ?></label>
                <input type="text" id="pm-label" placeholder="<?= htmlspecialchars($cL['phones_label_ph']) ?>">
            </div>
            <div class="pm-row">
                <label><?= htmlspecialchars($cL['phones_col_type']) ?> <span class="pm-req">*</span></label>
                <select id="pm-type">
                    <option value="physical"><?= htmlspecialchars($cL['phones_type_physical']) ?></option>
                    <option value="virtual"><?= htmlspecialchars($cL['phones_type_virtual']) ?></option>
                </select>
            </div>
            <div class="pm-row">
                <label><?= htmlspecialchars($cL['phones_col_purpose']) ?></label>
                <input type="text" id="pm-purpose" placeholder="<?= htmlspecialchars($cL['phones_purpose_ph']) ?>">
            </div>
            <div class="pm-row">
                <label><?= htmlspecialchars($cL['phones_col_assigned']) ?></label>
                <input type="text" id="pm-assigned" placeholder="<?= htmlspecialchars($cL['phones_assigned_ph']) ?>">
            </div>
            <div class="pm-row">
                <label><?= htmlspecialchars($cL['phones_col_ext']) ?></label>
                <input type="text" id="pm-ext" placeholder="<?= htmlspecialchars($cL['phones_ext_ph']) ?>" style="width:120px;font-family:monospace;">
            </div>
            <div class="pm-row">
                <label><?= htmlspecialchars($cL['phones_col_source']) ?></label>
                <select id="pm-source">
                    <option value="">— fără sursă —</option>
                    <?php foreach ($sources as $src): ?>
                    <option value="<?= $src->id ?>"><?= htmlspecialchars($src->name) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="pm-row">
                <label>Ordine</label>
                <input type="number" id="pm-sort" value="0" min="0" max="999" style="width:80px;">
            </div>
            <div class="pm-row">
                <label><?= htmlspecialchars($cL['phones_col_notes']) ?></label>
                <textarea id="pm-notes" rows="3" placeholder="<?= htmlspecialchars($cL['phones_notes_ph']) ?>"></textarea>
            </div>
            <div class="pm-row">
                <label><?= htmlspecialchars($cL['phones_col_active']) ?></label>
                <label class="csm-toggle" style="margin-top:2px;">
                    <input type="checkbox" id="pm-active" checked>
                    <span class="csm-toggle-slider"></span>
                </label>
            </div>
        </div>
        <div class="phones-modal-footer">
            <button class="phones-btn-delete" id="pm-delete-btn" onclick="phonesDelete()" style="display:none;">
                <?= htmlspecialchars($cL['delete'] ?? 'Șterge') ?>
            </button>
            <div style="flex:1;"></div>
            <button class="phones-btn-cancel" onclick="phonesCloseModal()"><?= htmlspecialchars($cL['cancel'] ?? 'Anulează') ?></button>
            <button class="phones-btn-save" onclick="phonesSave()"><?= htmlspecialchars($cL['save']) ?></button>
        </div>
    </div>
</div>

<!-- Inline data for JS -->
<script>
var _phonesData = <?= json_encode(array_map(function($ph) {
    return [
        'id'          => $ph->id,
        'number'      => $ph->number,
        'label'       => $ph->label,
        'type'        => $ph->type,
        'purpose'     => $ph->purpose,
        'assigned_to' => $ph->assigned_to,
        'pbx_ext'     => $ph->pbx_ext,
        'source_id'   => $ph->source_id,
        'notes'       => $ph->notes,
        'active'      => (int)$ph->active,
        'sort_order'  => (int)$ph->sort_order,
    ];
}, $phones), JSON_UNESCAPED_UNICODE) ?>;

var _phonesMsgs = {
    saved:      <?= json_encode($cL['phones_saved']) ?>,
    deleted:    <?= json_encode($cL['phones_deleted']) ?>,
    confirmDel: <?= json_encode($cL['phones_confirm_del']) ?>
};

var _phonesAjaxUrl = '/<?= $lang_url ?>/<?= $admin_dir_name ?>/ajax/?tp=adm&pg=crm';

function phonesOpenModal(id) {
    var overlay = document.getElementById('phones-modal-overlay');
    var titleEl = document.getElementById('phones-modal-title');
    var delBtn  = document.getElementById('pm-delete-btn');

    // Reset
    document.getElementById('pm-id').value        = '';
    document.getElementById('pm-number').value     = '';
    document.getElementById('pm-label').value      = '';
    document.getElementById('pm-type').value       = 'physical';
    document.getElementById('pm-purpose').value    = '';
    document.getElementById('pm-assigned').value   = '';
    document.getElementById('pm-ext').value        = '';
    document.getElementById('pm-source').value     = '';
    document.getElementById('pm-sort').value       = '0';
    document.getElementById('pm-notes').value      = '';
    document.getElementById('pm-active').checked   = true;

    if (id) {
        var ph = _phonesData.find(function(p){ return p.id == id; });
        if (!ph) return;
        titleEl.textContent = 'Editează Număr';
        delBtn.style.display = '';
        document.getElementById('pm-id').value        = ph.id;
        document.getElementById('pm-number').value    = ph.number;
        document.getElementById('pm-label').value     = ph.label || '';
        document.getElementById('pm-type').value      = ph.type;
        document.getElementById('pm-purpose').value   = ph.purpose || '';
        document.getElementById('pm-assigned').value  = ph.assigned_to || '';
        document.getElementById('pm-ext').value       = ph.pbx_ext || '';
        document.getElementById('pm-source').value    = ph.source_id || '';
        document.getElementById('pm-sort').value      = ph.sort_order;
        document.getElementById('pm-notes').value     = ph.notes || '';
        document.getElementById('pm-active').checked  = ph.active == 1;
    } else {
        titleEl.textContent = <?= json_encode($cL['phones_add']) ?>;
        delBtn.style.display = 'none';
    }

    overlay.classList.add('open');
}

function phonesCloseModal() {
    document.getElementById('phones-modal-overlay').classList.remove('open');
}

function phonesSave() {
    var id      = document.getElementById('pm-id').value;
    var number  = document.getElementById('pm-number').value.trim().replace(/\D/g,'');
    var label   = document.getElementById('pm-label').value.trim();
    var type    = document.getElementById('pm-type').value;
    var purpose = document.getElementById('pm-purpose').value.trim();
    var assigned= document.getElementById('pm-assigned').value.trim();
    var ext     = document.getElementById('pm-ext').value.trim();
    var source  = document.getElementById('pm-source').value;
    var sort    = document.getElementById('pm-sort').value;
    var notes   = document.getElementById('pm-notes').value.trim();
    var active  = document.getElementById('pm-active').checked ? '1' : '0';

    if (!number) { document.getElementById('pm-number').focus(); return; }

    var saveBtn = document.querySelector('.phones-btn-save');
    saveBtn.disabled = true;

    var body = 'fn=phones_save'
        + '&id='          + encodeURIComponent(id)
        + '&number='      + encodeURIComponent(number)
        + '&label='       + encodeURIComponent(label)
        + '&type='        + encodeURIComponent(type)
        + '&purpose='     + encodeURIComponent(purpose)
        + '&assigned_to=' + encodeURIComponent(assigned)
        + '&pbx_ext='     + encodeURIComponent(ext)
        + '&source_id='   + encodeURIComponent(source)
        + '&sort_order='  + encodeURIComponent(sort)
        + '&notes='       + encodeURIComponent(notes)
        + '&active='      + active;

    fetch(_phonesAjaxUrl, {
        method: 'POST',
        headers: {'Content-Type':'application/x-www-form-urlencoded'},
        body: body
    }).then(function(r){ return r.json(); }).then(function(d) {
        saveBtn.disabled = false;
        if (d.ok) {
            phonesCloseModal();
            location.reload();
        } else {
            alert(d.msg || 'Eroare');
        }
    }).catch(function(){
        saveBtn.disabled = false;
    });
}

function phonesDelete() {
    var id = document.getElementById('pm-id').value;
    if (!id) return;
    if (!confirm(_phonesMsgs.confirmDel)) return;

    fetch(_phonesAjaxUrl, {
        method: 'POST',
        headers: {'Content-Type':'application/x-www-form-urlencoded'},
        body: 'fn=phones_delete&id=' + encodeURIComponent(id)
    }).then(function(r){ return r.json(); }).then(function(d){
        if (d.ok) {
            phonesCloseModal();
            location.reload();
        } else {
            alert(d.msg || 'Eroare');
        }
    });
}
</script>

<style>
/* ── Phones page ─────────────────────────────────── */
#crm-phones-wrap { padding: 1.25rem 1.5rem; }

.phones-header {
    display: flex;
    align-items: center;
    justify-content: space-between;
    margin-bottom: 1rem;
}
.phones-header-title {
    display: flex;
    align-items: center;
    gap: 0.5rem;
    font-size: 1.05rem;
    font-weight: 600;
    color: #1a1a2e;
}
.phones-add-btn {
    background: #1a1a2e;
    color: #fff;
    border: none;
    border-radius: 6px;
    padding: 0.45rem 1rem;
    font-size: 0.82rem;
    font-weight: 600;
    cursor: pointer;
    transition: background .15s;
}
.phones-add-btn:hover { background: #2d2d4e; }

/* Filter bar */
.phones-filter-bar {
    display: flex;
    align-items: center;
    gap: 0.5rem;
    margin-bottom: 1rem;
    flex-wrap: wrap;
}
.phones-type-btn {
    display: inline-flex;
    align-items: center;
    gap: 0.35rem;
    padding: 0.3rem 0.75rem;
    border-radius: 20px;
    font-size: 0.78rem;
    font-weight: 500;
    color: #555;
    background: #f3f4f6;
    border: 1px solid #e5e7eb;
    text-decoration: none;
    transition: all .15s;
    white-space: nowrap;
}
.phones-type-btn:hover,
.phones-type-btn.active {
    background: #1a1a2e;
    color: #fff;
    border-color: #1a1a2e;
}
.phones-type-cnt {
    background: rgba(255,255,255,.2);
    border-radius: 10px;
    padding: 0 0.4rem;
    font-size: 0.72rem;
}
.phones-type-btn.active .phones-type-cnt { background: rgba(255,255,255,.25); }
.phones-filter-bar .phones-type-btn:not(.active) .phones-type-cnt { background: #e5e7eb; color: #666; }

.phones-search-wrap {
    position: relative;
    margin-left: auto;
}
.phones-search-wrap input {
    padding: 0.3rem 0.6rem 0.3rem 1.8rem;
    border: 1px solid #ddd;
    border-radius: 6px;
    font-size: 0.8rem;
    width: 200px;
    outline: none;
}
.phones-search-wrap input:focus { border-color: #1a1a2e; }
.phones-search-ico {
    position: absolute;
    left: 0.5rem;
    top: 50%;
    transform: translateY(-50%);
    pointer-events: none;
    opacity: .4;
}

/* Table */
.phones-table-wrap { overflow-x: auto; }
.phones-tbl {
    width: 100%;
    border-collapse: collapse;
    font-size: 0.82rem;
}
.phones-tbl th {
    background: #f8f9fa;
    padding: 0.5rem 0.75rem;
    font-size: 0.73rem;
    font-weight: 600;
    color: #888;
    text-align: left;
    border-bottom: 1px solid #e9ecef;
    white-space: nowrap;
}
.phones-tbl td {
    padding: 0.55rem 0.75rem;
    border-bottom: 1px solid #f0f0f0;
    vertical-align: middle;
}
.phones-tbl tr:hover td { background: #fafafa; }
.phones-row-inactive td { opacity: .45; }

.phones-number {
    font-family: monospace;
    font-size: 0.88rem;
    font-weight: 600;
    color: #1a1a2e;
    white-space: nowrap;
}
.phones-label-cell { font-weight: 500; color: #333; }
.phones-purpose-cell { color: #444; max-width: 220px; }

/* Badges */
.phones-badge {
    display: inline-block;
    border-radius: 4px;
    padding: 0.15rem 0.5rem;
    font-size: 0.7rem;
    font-weight: 600;
    white-space: nowrap;
}
.phones-badge-phy { background: #dbeafe; color: #1d4ed8; }
.phones-badge-vir { background: #f3e8ff; color: #7c3aed; }

/* Active dot */
.phones-active-dot {
    display: inline-block;
    width: 10px;
    height: 10px;
    border-radius: 50%;
}
.phones-active-dot.active   { background: #22c55e; }
.phones-active-dot.inactive { background: #d1d5db; }

/* Edit button */
.phones-edit-btn {
    background: none;
    border: none;
    cursor: pointer;
    opacity: .5;
    padding: 3px;
    border-radius: 4px;
    transition: opacity .15s;
}
.phones-edit-btn:hover { opacity: 1; background: #f0f0f0; }

/* ── Modal ─────────────────────────────────────────── */
.phones-modal-overlay {
    display: none;
    position: fixed;
    inset: 0;
    background: rgba(0,0,0,.45);
    z-index: 9000;
    align-items: center;
    justify-content: center;
}
.phones-modal-overlay.open { display: flex; }

.phones-modal {
    background: #fff;
    border-radius: 10px;
    width: 480px;
    max-width: 96vw;
    max-height: 90vh;
    overflow-y: auto;
    box-shadow: 0 8px 40px rgba(0,0,0,.18);
    display: flex;
    flex-direction: column;
}
.phones-modal-header {
    display: flex;
    align-items: center;
    justify-content: space-between;
    padding: 1rem 1.25rem 0.75rem;
    border-bottom: 1px solid #eee;
}
.phones-modal-title { font-size: 0.95rem; font-weight: 700; color: #1a1a2e; }
.phones-modal-close {
    background: none;
    border: none;
    font-size: 1.1rem;
    cursor: pointer;
    color: #999;
    line-height: 1;
    padding: 2px 6px;
}
.phones-modal-close:hover { color: #333; }

.phones-modal-body { padding: 1rem 1.25rem; display: flex; flex-direction: column; gap: 0.65rem; }

.pm-row { display: flex; flex-direction: column; gap: 0.25rem; }
.pm-row label { font-size: 0.75rem; font-weight: 600; color: #666; }
.pm-req { color: #e2001a; }
.pm-row input[type="text"],
.pm-row input[type="tel"],
.pm-row input[type="number"],
.pm-row select,
.pm-row textarea {
    padding: 0.4rem 0.6rem;
    border: 1px solid #ddd;
    border-radius: 5px;
    font-size: 0.82rem;
    color: #333;
    outline: none;
    width: 100%;
    box-sizing: border-box;
    transition: border-color .15s;
}
.pm-row input:focus,
.pm-row select:focus,
.pm-row textarea:focus { border-color: #1a1a2e; }
.pm-row textarea { resize: vertical; }

.phones-modal-footer {
    display: flex;
    align-items: center;
    gap: 0.5rem;
    padding: 0.75rem 1.25rem 1rem;
    border-top: 1px solid #eee;
}
.phones-btn-save {
    background: #1a1a2e;
    color: #fff;
    border: none;
    border-radius: 6px;
    padding: 0.45rem 1.1rem;
    font-size: 0.82rem;
    font-weight: 600;
    cursor: pointer;
}
.phones-btn-save:hover { background: #2d2d4e; }
.phones-btn-save:disabled { opacity: .5; cursor: default; }
.phones-btn-cancel {
    background: #f3f4f6;
    color: #555;
    border: 1px solid #ddd;
    border-radius: 6px;
    padding: 0.45rem 0.9rem;
    font-size: 0.82rem;
    cursor: pointer;
}
.phones-btn-cancel:hover { background: #e9ecef; }
.phones-btn-delete {
    background: #fff0f0;
    color: #dc2626;
    border: 1px solid #fca5a5;
    border-radius: 6px;
    padding: 0.45rem 0.9rem;
    font-size: 0.82rem;
    cursor: pointer;
}
.phones-btn-delete:hover { background: #fee2e2; }
</style>
