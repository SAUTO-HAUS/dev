<?php defined('_DOIT') or die('Restricted access');

require_once(_ADM_INCL.'/crm/crm_lang.php');

$saved_section = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['crm_section'])) {
    $section = $_POST['crm_section'];

    if ($section === 'general') {
        foreach (['call_min_duration','lead_ttl_hours','rot_warn_hours','rot_max_hours','takeover_threshold'] as $k) {
            if (isset($_POST[$k])) crm_set_setting($db, $prefx, $k, trim($_POST[$k]));
        }
        // Save work hours together
        foreach (['work_hours_start','work_hours_end'] as $k) {
            if (isset($_POST[$k])) crm_set_setting($db, $prefx, $k, trim($_POST[$k]));
        }
        $work_days_arr = isset($_POST['work_days_arr']) && is_array($_POST['work_days_arr'])
            ? implode(',', array_map('intval', $_POST['work_days_arr']))
            : '';
        crm_set_setting($db, $prefx, 'work_days', $work_days_arr);
    }

    if ($section === 'sources') {
        if (isset($_POST['src_id']) && is_array($_POST['src_id'])) {
            foreach ($_POST['src_id'] as $i => $src_id) {
                $src_id    = (int)$src_id;
                $src_phone = crm_norm_phone(trim($_POST['src_phone'][$i] ?? ''));
                $src_name  = trim($_POST['src_name'][$i]  ?? '');
                $src_dept  = in_array($_POST['src_dept'][$i] ?? '', ['stock','order','pruncul']) ? $_POST['src_dept'][$i] : 'stock';
                $src_color = trim($_POST['src_color'][$i]  ?? '#888888');
                $src_order = (int)($_POST['src_order'][$i] ?? $i);
                $src_active= isset($_POST['src_active'][$i]) ? 1 : 0;
                if ($src_id > 0) {
                    $stmt = $db->prepare("UPDATE {$prefx}_crm_sources SET phone_number=:ph,name=:nm,department=:dept,color=:color,sort_order=:ord,active=:act WHERE id=:id");
                    $stmt->execute([':ph'=>$src_phone,':nm'=>$src_name,':dept'=>$src_dept,':color'=>$src_color,':ord'=>$src_order,':act'=>$src_active,':id'=>$src_id]);
                } elseif ($src_phone && $src_name) {
                    $stmt = $db->prepare("INSERT INTO {$prefx}_crm_sources (phone_number,name,department,color,sort_order,active) VALUES (:ph,:nm,:dept,:color,:ord,:act)");
                    $stmt->execute([':ph'=>$src_phone,':nm'=>$src_name,':dept'=>$src_dept,':color'=>$src_color,':ord'=>$src_order,':act'=>$src_active]);
                }
            }
        }
        if (isset($_POST['src_delete']) && is_array($_POST['src_delete'])) {
            $del = $db->prepare("DELETE FROM {$prefx}_crm_sources WHERE id=:id");
            foreach ($_POST['src_delete'] as $did) { $did=(int)$did; if($did>0) $del->execute([':id'=>$did]); }
        }
    }

    if ($section === 'stt') {
        foreach (['stt_enabled','stt_language','stt_min_dur'] as $k) {
            if (isset($_POST[$k])) crm_set_setting($db, $prefx, $k, trim($_POST[$k]));
        }
    }

    if ($section === 'ai_inbox') {
        foreach ([
            'inbox_ai_prompt_default',
            'inbox_ai_prompt_telegram',
            'inbox_ai_prompt_999md',
            'inbox_ai_prompt_sautohaus_999md',
            'inbox_ai_prompt_order_999md',
            'inbox_ai_prompt_korea_999md',
            'inbox_ai_prompt_usa_999md',
            'inbox_ai_prompt_regular_999md',
            'inbox_ai_prompt_facebook',
            'inbox_ai_prompt_instagram',
            'inbox_ai_prompt_viber',
        ] as $k) {
            if (isset($_POST[$k])) crm_set_setting($db, $prefx, $k, trim($_POST[$k]));
        }
        // AI enable toggles per channel (checkbox: present = 1, absent = 0)
        $enable_channels = ['telegram','999md','sautohaus_999md','order_999md','korea_999md','usa_999md','regular_999md','facebook','instagram','viber'];
        foreach ($enable_channels as $ch) {
            $val = isset($_POST['inbox_ai_enabled_' . $ch]) ? '1' : '0';
            crm_set_setting($db, $prefx, 'inbox_ai_enabled_' . $ch, $val);
        }
    }

    if ($section === 'viber') {
        foreach (['viber_bot_token','viber_welcome_msg'] as $k) {
            if (isset($_POST[$k])) crm_set_setting($db, $prefx, $k, trim($_POST[$k]));
        }
    }

    if ($section === 'webhooks') {
        if (isset($_POST['fb_webhook_verify_token'])) crm_set_setting($db, $prefx, 'fb_webhook_verify_token', trim($_POST['fb_webhook_verify_token']));
    }

    if ($section === 'agents') {
        // Delete agents
        if (isset($_POST['agent_delete']) && is_array($_POST['agent_delete'])) {
            $del = $db->prepare("DELETE FROM {$prefx}_adm_usr WHERE id=:id");
            foreach ($_POST['agent_delete'] as $did) {
                $did = (int)$did;
                if ($did <= 0) continue;
                if ($did === 1) continue; // protect super-admin
                if ($did === (int)$user_id) continue; // can't delete yourself
                $del->execute([':id' => $did]);
            }
        }
        // Add new agent
        if (!empty(trim($_POST['new_agent_name'] ?? ''))) {
            $ins = $db->prepare("INSERT INTO {$prefx}_adm_usr (name, pbx_login, crm_phone, department, crm_access, login, pass) VALUES (:n,:p,:ph,:dept,:access,:l,:pw)");
            $new_name   = trim($_POST['new_agent_name']);
            $new_pbx    = trim($_POST['new_agent_pbx']    ?? '');
            $new_ph     = trim($_POST['new_agent_phone']   ?? '');
            $new_dept   = in_array($_POST['new_agent_dept']       ?? '', ['stock','order','pruncul']) ? $_POST['new_agent_dept']       : null;
            $new_access = in_array($_POST['new_agent_crm_access'] ?? '', ['stock','order','pruncul']) ? $_POST['new_agent_crm_access'] : null;
            $new_login  = strtolower(preg_replace('/\s+/', '_', $new_name));
            $ins->execute([':n'=>$new_name,':p'=>$new_pbx,':ph'=>$new_ph,':dept'=>$new_dept,':access'=>$new_access,':l'=>$new_login,':pw'=>md5(uniqid())]);
        }
        // Update existing
        if (isset($_POST['agent_name']) && is_array($_POST['agent_name'])) {
            $upd = $db->prepare("UPDATE {$prefx}_adm_usr SET name=:v WHERE id=:id");
            foreach ($_POST['agent_name'] as $uid => $val) { $val=trim($val); if($val!=='') $upd->execute([':v'=>$val,':id'=>(int)$uid]); }
        }
        if (isset($_POST['pbx_login']) && is_array($_POST['pbx_login'])) {
            $upd = $db->prepare("UPDATE {$prefx}_adm_usr SET pbx_login=:v WHERE id=:id");
            foreach ($_POST['pbx_login'] as $uid => $val) { $upd->execute([':v'=>trim($val),':id'=>(int)$uid]); }
        }
        if (isset($_POST['crm_phone']) && is_array($_POST['crm_phone'])) {
            $upd = $db->prepare("UPDATE {$prefx}_adm_usr SET crm_phone=:v WHERE id=:id");
            foreach ($_POST['crm_phone'] as $uid => $val) { $upd->execute([':v'=>crm_norm_phone(trim($val)),':id'=>(int)$uid]); }
        }
        if (isset($_POST['agent_dept']) && is_array($_POST['agent_dept'])) {
            $upd = $db->prepare("UPDATE {$prefx}_adm_usr SET department=:v WHERE id=:id");
            foreach ($_POST['agent_dept'] as $uid => $val) {
                $val = in_array($val, ['stock','order','pruncul']) ? $val : null;
                $upd->execute([':v'=>$val,':id'=>(int)$uid]);
            }
        }
        if (isset($_POST['agent_crm_access']) && is_array($_POST['agent_crm_access'])) {
            $upd = $db->prepare("UPDATE {$prefx}_adm_usr SET crm_access=:v WHERE id=:id");
            foreach ($_POST['agent_crm_access'] as $uid => $val) {
                $val = in_array($val, ['stock','order','pruncul']) ? $val : null;
                $upd->execute([':v'=>$val,':id'=>(int)$uid]);
            }
        }
    }

    crm_audit($db, $prefx, 'settings_saved', 'settings', 0, '', '', (int)$user_id, $user_name ?? '');
    $saved_section = $section;
}

$s       = crm_get_all_settings($db, $prefx);
$sources = crm_get_sources($db, $prefx);

$work_days_all     = [1=>'Luni',2=>'Marți',3=>'Miercuri',4=>'Joi',5=>'Vineri',6=>'Sâmbătă',7=>'Duminică'];
$current_work_days = explode(',', $s['work_days'] ?? '1,2,3,4,5');

function crm_norm_phone(string $ph): string {
    $d = preg_replace('/\D/', '', $ph);
    if ($d === '') return '';
    if (strlen($d) === 8) return '373' . $d;           // 79600379  → 37379600379
    if (strlen($d) === 9 && $d[0] === '0') return '373' . substr($d, 1); // 079600379 → 37379600379
    if (strlen($d) === 11 && substr($d, 0, 3) === '373') return $d;
    return $d;
}

function crm_fmt_phone(string $ph): string {
    $ph = preg_replace('/\D/', '', $ph);
    if (strlen($ph) === 11 && substr($ph, 0, 3) === '373') $ph = substr($ph, 3);
    if (strlen($ph) === 8) return '0' . substr($ph, 0, 2) . ' ' . substr($ph, 2, 3) . ' ' . substr($ph, 5);
    if (strlen($ph) === 9 && $ph[0] === '0') return substr($ph, 0, 3) . ' ' . substr($ph, 3, 3) . ' ' . substr($ph, 6);
    return $ph;
}

function crm_save_btn(string $section, string $label, bool $saved): string {
    $ok = $saved ? '<span style="color:#22a05a;font-size:0.82rem;font-weight:600;">✓ Salvat</span>' : '';
    return '<div class="crm-settings-save"><button type="submit" class="crm-btn crm-btn-primary crm-btn-sm">'.$label.'</button>'.$ok.'</div>';
}
?>

<div id="crm-wrap">
    <div class="calls-header">
        <div class="calls-header-title">
            <span class="calls-header-icon">
                <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <circle cx="12" cy="12" r="3"/><path d="M19.4 15a1.65 1.65 0 0 0 .33 1.82l.06.06a2 2 0 0 1-2.83 2.83l-.06-.06a1.65 1.65 0 0 0-1.82-.33 1.65 1.65 0 0 0-1 1.51V21a2 2 0 0 1-4 0v-.09A1.65 1.65 0 0 0 9 19.4a1.65 1.65 0 0 0-1.82.33l-.06.06a2 2 0 0 1-2.83-2.83l.06-.06A1.65 1.65 0 0 0 4.68 15a1.65 1.65 0 0 0-1.51-1H3a2 2 0 0 1 0-4h.09A1.65 1.65 0 0 0 4.6 9a1.65 1.65 0 0 0-.33-1.82l-.06-.06a2 2 0 0 1 2.83-2.83l.06.06A1.65 1.65 0 0 0 9 4.68a1.65 1.65 0 0 0 1-1.51V3a2 2 0 0 1 4 0v.09a1.65 1.65 0 0 0 1 1.51 1.65 1.65 0 0 0 1.82-.33l.06-.06a2 2 0 0 1 2.83 2.83l-.06.06A1.65 1.65 0 0 0 19.4 9a1.65 1.65 0 0 0 1.51 1H21a2 2 0 0 1 0 4h-.09a1.65 1.65 0 0 0-1.51 1z"/>
                </svg>
            </span>
            <span class="calls-header-text"><?= $cL['settings'] ?></span>
        </div>
    </div>

    <?php /* ── General + Work hours combined ── */ ?>
    <form method="POST" class="crm-settings">
        <input type="hidden" name="crm_section" value="general">
        <div class="crm-settings-section">
            <h3><?= $cL['settings_general'] ?> / <?= $cL['settings_work_hours'] ?></h3>
            <?php $fields = [
                'call_min_duration' => [$cL['call_min_sec'],   'number', 0, 300],
                'lead_ttl_hours'    => [$cL['lead_ttl_h'],     'number', 1, 720],
                'rot_warn_hours'    => [$cL['rot_warn_h'],     'number', 1, 168],
                'rot_max_hours'     => [$cL['rot_max_h'],      'number', 1, 168],
                'takeover_threshold'=> [$cL['takeover_thresh'],'number', 1, 10],
            ];
            foreach ($fields as $k => [$label, $type, $min, $max]): ?>
            <div class="crm-field-row">
                <label><?= $label ?></label>
                <input type="<?= $type ?>" name="<?= $k ?>" value="<?= htmlspecialchars($s[$k] ?? '') ?>" min="<?= $min ?>" max="<?= $max ?>">
            </div>
            <?php endforeach; ?>
            <div class="crm-field-row">
                <label><?= $cL['work_days'] ?></label>
                <div style="display:flex;gap:0.5rem;flex-wrap:wrap;">
                    <?php foreach ($work_days_all as $d => $dname): ?>
                    <label style="display:flex;align-items:center;gap:0.3rem;font-size:0.8rem;cursor:pointer;">
                        <input type="checkbox" name="work_days_arr[]" value="<?= $d ?>" <?= in_array((string)$d, $current_work_days)?'checked':'' ?>>
                        <?= $dname ?>
                    </label>
                    <?php endforeach; ?>
                </div>
            </div>
            <div class="crm-field-row">
                <label><?= $cL['work_start'] ?></label>
                <input type="time" name="work_hours_start" value="<?= htmlspecialchars($s['work_hours_start'] ?? '09:00') ?>">
            </div>
            <div class="crm-field-row">
                <label><?= $cL['work_end'] ?></label>
                <input type="time" name="work_hours_end" value="<?= htmlspecialchars($s['work_hours_end'] ?? '18:00') ?>">
            </div>
            <?= crm_save_btn('general', $cL['save'], $saved_section==='general' || $saved_section==='work_hours') ?>
        </div>
    </form>

    <?php /* ── Sources ── */ ?>
    <form method="POST" class="crm-settings" id="crm-src-form">
        <input type="hidden" name="crm_section" value="sources">
        <div class="crm-settings-section">
            <h3><?= $cL['settings_sources'] ?></h3>
            <table class="crm-sources-table" id="crm-src-table">
                <thead>
                    <tr><th><?= $cL['settings_src_pbx'] ?></th><th><?= $cL['settings_src_name'] ?></th><th><?= $cL['settings_src_dept'] ?></th><th><?= $cL['settings_src_color'] ?></th><th><?= $cL['settings_src_active'] ?></th><th></th></tr>
                </thead>
                <tbody id="crm-src-tbody">
                <?php foreach ($sources as $i => $src): ?>
                <tr>
                    <td><input type="hidden" name="src_id[]" value="<?= $src->id ?>"><input type="hidden" name="src_order[]" value="<?= $src->sort_order ?>"><input type="text" name="src_phone[]" value="<?= htmlspecialchars(crm_fmt_phone($src->phone_number)) ?>" placeholder="079 600 379"></td>
                    <td><input type="text" name="src_name[]"  value="<?= htmlspecialchars($src->name) ?>"></td>
                    <td><select name="src_dept[]"><option value="stock" <?= $src->department==='stock'?'selected':'' ?>><?= $cL['settings_src_stock'] ?></option><option value="order" <?= $src->department==='order'?'selected':'' ?>><?= $cL['settings_src_order'] ?></option><option value="pruncul" <?= $src->department==='pruncul'?'selected':'' ?>>Filială Pruncul</option></select></td>
                    <td><input type="color" name="src_color[]" value="<?= htmlspecialchars($src->color) ?>"></td>
                    <td style="text-align:center;"><input type="checkbox" name="src_active[<?= $i ?>]" value="1" <?= $src->active?'checked':'' ?>></td>
                    <td><button type="button" class="crm-btn crm-btn-danger crm-btn-sm" onclick="crmDeleteSrc(this, <?= $src->id ?>)">✕</button></td>
                </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
            <div class="crm-settings-save">
                <button type="button" class="crm-btn crm-btn-outline crm-btn-sm" onclick="crmAddSrc()">+ <?= $cL['settings_source_add'] ?></button>
                <button type="submit" class="crm-btn crm-btn-primary crm-btn-sm"><?= $cL['save'] ?></button>
                <?php if ($saved_section==='sources'): ?><span style="color:#22a05a;font-size:0.82rem;font-weight:600;">✓ Salvat</span><?php endif; ?>
            </div>
        </div>
    </form>

    <?php /* ── Agents ── */ ?>
    <form method="POST" class="crm-settings">
        <input type="hidden" name="crm_section" value="agents">
        <div class="crm-settings-section">
            <h3><?= $cL['settings_agents'] ?></h3>
            <table class="crm-sources-table crm-agents-table">
                <thead>
                    <tr>
                        <th><?= $cL['agent_name'] ?></th>
                        <th><?= $cL['settings_src_dept'] ?></th>
                        <th>Acces CRM</th>
                        <th>Login PBX</th>
                        <th><?= $cL['agent_phone'] ?></th>
                    </tr>
                </thead>
                <tbody id="crm-agents-tbody">
                <?php
                $agents = $db->query("SELECT id, name, pbx_login, crm_phone, department, crm_access FROM {$prefx}_adm_usr ORDER BY (crm_phone IS NULL OR crm_phone='') ASC, name ASC")->fetchAll(PDO::FETCH_OBJ);
                foreach ($agents as $ag):
                    $no_phone = empty(trim($ag->crm_phone ?? ''));
                ?>
                <tr<?= $no_phone ? ' style="background:#fff5f5;"' : '' ?>>
                    <td><input type="text" name="agent_name[<?= $ag->id ?>]" value="<?= htmlspecialchars($ag->name ?? '') ?>" style="width:160px;"></td>
                    <td><select name="agent_dept[<?= $ag->id ?>]"><option value=""><?= $cL['all'] ?></option><option value="stock" <?= ($ag->department ?? '')==='stock'?'selected':'' ?>><?= $cL['settings_src_stock'] ?></option><option value="order" <?= ($ag->department ?? '')==='order'?'selected':'' ?>><?= $cL['settings_src_order'] ?></option><option value="pruncul" <?= ($ag->department ?? '')==='pruncul'?'selected':'' ?>>Filială Pruncul</option></select></td>
                    <td><select name="agent_crm_access[<?= $ag->id ?>]"><option value=""><?= $cL['all'] ?></option><option value="stock" <?= ($ag->crm_access ?? '')==='stock'?'selected':'' ?>><?= $cL['settings_src_stock'] ?></option><option value="order" <?= ($ag->crm_access ?? '')==='order'?'selected':'' ?>><?= $cL['settings_src_order'] ?></option><option value="pruncul" <?= ($ag->crm_access ?? '')==='pruncul'?'selected':'' ?>>Filială Pruncul</option></select></td>
                    <td><input type="text" name="pbx_login[<?= $ag->id ?>]" value="<?= htmlspecialchars($ag->pbx_login ?? '') ?>" placeholder="<?= htmlspecialchars($cL['agent_pbx_ph']) ?>" style="width:180px;"></td>
                    <td><input type="text" name="crm_phone[<?= $ag->id ?>]" value="<?= htmlspecialchars(crm_fmt_phone($ag->crm_phone ?? '')) ?>" placeholder="<?= htmlspecialchars($cL['agent_phone_ph']) ?>" style="width:160px;"></td>
                </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
            <div class="crm-settings-save">
                <button type="button" class="crm-btn crm-btn-outline crm-btn-sm" onclick="crmAddAgent()">+ <?= $cL['agent_add'] ?></button>
                <button type="submit" class="crm-btn crm-btn-primary crm-btn-sm"><?= $cL['save'] ?></button>
                <?php if ($saved_section==='agents'): ?><span style="color:#22a05a;font-size:0.82rem;font-weight:600;">✓ Salvat</span><?php endif; ?>
            </div>
        </div>
    </form>

    <?php /* ── STT ── */ ?>
    <form method="POST" class="crm-settings">
        <input type="hidden" name="crm_section" value="stt">
        <div class="crm-settings-section">
            <h3><?= $cL['settings_stt_title'] ?></h3>
            <p class="crm-settings-desc"><?= $cL['settings_stt_desc'] ?></p>
            <div class="crm-field-row">
                <label><?= $cL['settings_stt_enabled'] ?></label>
                <select name="stt_enabled">
                    <option value="0" <?= ($s['stt_enabled'] ?? '0') === '0' ? 'selected' : '' ?>><?= $cL['settings_stt_off'] ?></option>
                    <option value="1" <?= ($s['stt_enabled'] ?? '0') === '1' ? 'selected' : '' ?>><?= $cL['settings_stt_on'] ?></option>
                </select>
            </div>
            <div class="crm-field-row">
                <label><?= $cL['settings_stt_lang'] ?></label>
                <select name="stt_language">
                    <option value="auto" <?= ($s['stt_language'] ?? 'auto') === 'auto' ? 'selected' : '' ?>><?= $cL['settings_stt_lang_auto'] ?></option>
                    <option value="ro"   <?= ($s['stt_language'] ?? '') === 'ro'   ? 'selected' : '' ?>>Română</option>
                    <option value="ru"   <?= ($s['stt_language'] ?? '') === 'ru'   ? 'selected' : '' ?>>Русский</option>
                    <option value="en"   <?= ($s['stt_language'] ?? '') === 'en'   ? 'selected' : '' ?>>English</option>
                </select>
            </div>
            <div class="crm-field-row">
                <label><?= $cL['settings_stt_min_dur'] ?></label>
                <input type="number" name="stt_min_dur" value="<?= htmlspecialchars($s['stt_min_dur'] ?? '30') ?>" min="10" max="300">
            </div>
            <?= crm_save_btn('stt', $cL['save'], $saved_section==='stt') ?>
        </div>
    </form>

    <?php /* ── AI Inbox ── */ ?>
    <form method="POST" class="crm-settings">
        <input type="hidden" name="crm_section" value="ai_inbox">
        <div class="crm-settings-section">
            <h3><?= $cL['settings_ai_title'] ?></h3>
            <div class="crm-field-row crm-field-col">
                <label><?= $cL['settings_ai_prompt_default'] ?></label>
                <textarea name="inbox_ai_prompt_default" rows="18" class="crm-textarea-mono"><?= htmlspecialchars($s['inbox_ai_prompt_default'] ?? 'You represent the SAUTO car dealership (sauto.md). We sell used cars from Europe — both in stock and on order.

LANGUAGE: Detect language from user\'s first message. Respond ONLY in that language for the entire conversation. Never switch languages.

STYLE: Conversational, confident, with light humor. No robotic phrases. Max 1-2 emojis per message only when appropriate. Max 2-4 sentences per reply. Never write long paragraphs.

GOAL: Guide the client to action (call, visit, test drive). Every reply must lead to the next step.

PHONE LOGIC:
- Car in stock (client asks about specific car): give number +373 (79) 60-03-41
- Car on order (looking for something not on site): give number +373 (79) 60-03-26
- Complex question: suggest calling Mon-Fri 9:00-18:00, Sat-Sun 10:00-16:00
- If client asks directly for phone — give it immediately with one short sentence
- Before giving phone, always first engage: clarify a detail, comment on request, show you read the question
- If client writes outside working hours — do not suggest calling right now

DO NOT respond if last message is: "Ok", "Got it", "Thanks", "👍", "👌" or similar acknowledgements.

LEAD TRIGGER: When the client provides their phone number, respond with: LEAD_READY:[number]
Do not promise to call back — you cannot make calls.

ADDRESS: Moldova, Chișinău, str. Calea Moșilor 11
WEBSITE: https://www.sauto.md — reference it often.

CREDIT: Available up to 60 months. Early repayment without penalties. Possible without down payment (better terms with one). All banks require income confirmation. Keep it simple — credit is easy, direct to consultant for exact calculation.

VIN: Not provided until personal visit or direct manager contact.

Never reveal internal company details, staff names, or system architecture.') ?></textarea>
            </div>

            <div class="ai-channels-grid">
                <?php
                $ai_channels = [
                    ['telegram',         'inbox_ai_prompt_telegram',         'telegram.svg'],
                    ['999md',            'inbox_ai_prompt_999md',            '999.svg'],
                    ['sautohaus_999md',  'inbox_ai_prompt_sautohaus_999md',  '999.svg'],
                    ['order_999md',      'inbox_ai_prompt_order_999md',      '999.svg'],
                    ['korea_999md',      'inbox_ai_prompt_korea_999md',      '999.svg'],
                    ['usa_999md',        'inbox_ai_prompt_usa_999md',        '999.svg'],
                    ['regular_999md',    'inbox_ai_prompt_regular_999md',    '999.svg'],
                    ['facebook',         'inbox_ai_prompt_facebook',         'facebook.svg'],
                    ['instagram',        'inbox_ai_prompt_instagram',        'instagram.svg'],
                    ['viber',            'inbox_ai_prompt_viber',            'viber.svg'],
                ];
                $icons_base = '/content/admin/include/crm/icons/';
                foreach ($ai_channels as [$ch, $key, $icon_file]):
                    $val = $s[$key] ?? '';
                    $hasVal = $val !== '';
                    $is_999_sub = (substr($ch, -6) === '_999md') && $ch !== '999md';
                    $is_999_sub = (substr($ch, -6) === '_999md') && $ch !== '999md';
                    $enable_key = 'inbox_ai_enabled_' . $ch;
                    $enabled = !isset($s[$enable_key]) || $s[$enable_key] === '1' || $s[$enable_key] === '';
                    $hint = $is_999_sub
                        ? 'Dacă este gol — se folosește promptul „999.md (toate sub-canalele)", iar dacă și acela e gol — promptul default. Dacă este completat — suprascrie complet pentru acest sub-canal.'
                        : 'Dacă este gol — se folosește promptul default. Dacă este completat — suprascrie complet promptul default pentru acest canal.';
                ?>
                <div class="ai-ch-card<?= $hasVal ? ' ai-ch-active' : '' ?><?= $is_999_sub ? ' ai-ch-sub' : '' ?><?= !$enabled ? ' ai-ch-disabled' : '' ?>" id="ai-ch-<?= $ch ?>">
                    <div class="ai-ch-header" onclick="aiChToggle('<?= $ch ?>')">
                        <span class="ai-ch-icon">
                            <img src="<?= $icons_base . $icon_file ?>" width="18" height="18" alt="<?= $ch ?>">
                        </span>
                        <span class="ai-ch-name"><?= $cL['settings_ai_prompt_'.$ch] ?? $ch ?></span>
                        <span class="ai-ch-badge<?= $hasVal ? ' ai-ch-badge-on' : '' ?>"><?= $hasVal ? 'custom' : 'default' ?></span>
                        <label class="ai-ch-switch" title="Activează/dezactivează AI pe acest canal" onclick="event.stopPropagation();">
                            <input type="checkbox" name="<?= $enable_key ?>" value="1" <?= $enabled ? 'checked' : '' ?> onclick="event.stopPropagation();">
                            <span class="ai-ch-switch-slider" onclick="event.stopPropagation();"></span>
                        </label>
                        <svg class="ai-ch-arrow" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round"><polyline points="6 9 12 15 18 9"/></svg>
                    </div>
                    <div class="ai-ch-body" style="display:none;">
                        <p class="ai-ch-hint"><?= $hint ?></p>
                        <textarea name="<?= $key ?>" rows="14" class="crm-textarea-mono" placeholder="Lasă gol pentru a folosi promptul default..."><?= htmlspecialchars($val) ?></textarea>
                        <?php if ($hasVal): ?>
                        <button type="button" class="ai-ch-clear" onclick="aiChClear('<?= $ch ?>', '<?= $key ?>')">✕ Șterge (revino la default)</button>
                        <?php endif; ?>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>

            <?= crm_save_btn('ai_inbox', $cL['save'], $saved_section==='ai_inbox') ?>
        </div>
    </form>

    <?php /* ── Viber ── */ ?>
    <form method="POST" class="crm-settings">
        <input type="hidden" name="crm_section" value="viber">
        <div class="crm-settings-section">
            <h3><?= $cL['settings_viber_title'] ?></h3>
            <p class="crm-settings-desc"><?= $cL['settings_viber_desc'] ?></p>
            <div class="crm-field-row">
                <label><?= $cL['settings_viber_token'] ?></label>
                <input type="text" name="viber_bot_token" value="<?= htmlspecialchars($s['viber_bot_token'] ?? '') ?>"
                       placeholder="445da6az1s345z78-dazcczb2542zv51a-e0vc5fva17480im9"
                       style="width:420px;" class="crm-mono">
            </div>
            <div class="crm-field-row">
                <label><?= $cL['settings_viber_welcome'] ?></label>
                <input type="text" name="viber_welcome_msg"
                       value="<?= htmlspecialchars($s['viber_welcome_msg'] ?? 'Bună ziua! Sunt asistentul auto Sauto-Haus. Cu ce vă pot ajuta?') ?>"
                       style="width:420px;">
            </div>
            <?= crm_save_btn('viber', $cL['save'], $saved_section==='viber') ?>
        </div>
    </form>

    <?php /* ── Webhooks ── */ ?>
    <form method="POST" class="crm-settings">
        <input type="hidden" name="crm_section" value="webhooks">
        <div class="crm-settings-section">
            <h3><?= $cL['settings_webhooks_title'] ?></h3>
            <div style="font-size:0.82rem;color:#555;line-height:2;">
                <strong>Facebook Messenger + Instagram:</strong><br>
                &nbsp;&nbsp;Callback URL: <code>https://www.sauto.md/fb_webhook.php</code><br>
                &nbsp;&nbsp;Verify Token: <input type="text" name="fb_webhook_verify_token" value="<?= htmlspecialchars($s['fb_webhook_verify_token'] ?? 'sauto_fb_verify') ?>" style="padding:0.25rem 0.5rem;border:1px solid #ddd;border-radius:3px;font-size:0.8rem;width:220px;"><br>
                &nbsp;&nbsp;Subscriptions: <code>messages, messaging_postbacks, instagram_manage_messages</code><br><br>
                <strong>Telegram:</strong><br>
                &nbsp;&nbsp;Webhook URL: <code>https://www.sauto.md/tg_webhook.php</code><br>
                &nbsp;&nbsp;Setare: <code>https://api.telegram.org/bot{TOKEN}/setWebhook?url=https://www.sauto.md/tg_webhook.php</code>
            </div>
            <?= crm_save_btn('webhooks', $cL['save'], $saved_section==='webhooks') ?>
        </div>
    </form>


</div>

<style>
.ai-channels-grid { display:flex; flex-direction:column; gap:6px; margin:1.2rem 0 1.4rem; }
.ai-ch-card { border:1.5px solid #e8e8e8; border-radius:8px; overflow:hidden; transition:border-color .15s; }
.ai-ch-card.ai-ch-sub { margin-left:24px; border-left:3px solid #c5d8ff; }
.ai-ch-card.ai-ch-disabled { opacity:0.55; background:#fafafa; border-color:#e0e0e0; }
.ai-ch-card.ai-ch-disabled .ai-ch-name { color:#888; }
.ai-ch-switch { position:relative; display:inline-block; width:36px; height:20px; flex-shrink:0; cursor:pointer; }
.ai-ch-switch input { opacity:0; width:0; height:0; }
.ai-ch-switch-slider { position:absolute; inset:0; background:#ccc; border-radius:20px; transition:background .2s; }
.ai-ch-switch-slider::before { content:""; position:absolute; left:2px; top:2px; width:16px; height:16px; background:#fff; border-radius:50%; transition:transform .2s; box-shadow:0 1px 2px rgba(0,0,0,0.2); }
.ai-ch-switch input:checked + .ai-ch-switch-slider { background:#22a05a; }
.ai-ch-switch input:checked + .ai-ch-switch-slider::before { transform:translateX(16px); }
.ai-ch-card.ai-ch-active { border-color:#c5d8ff; background:#fafcff; }
.ai-ch-header { display:flex; align-items:center; gap:10px; padding:10px 14px; cursor:pointer; user-select:none; }
.ai-ch-header:hover { background:#f7f7f7; }
.ai-ch-active .ai-ch-header:hover { background:#f0f5ff; }
.ai-ch-icon { display:flex; align-items:center; justify-content:center; width:28px; height:28px; border-radius:6px; flex-shrink:0; background:#f4f4f4; }
.ai-ch-name { font-size:0.9rem; font-weight:600; color:#222; flex:1; }
.ai-ch-badge { font-size:0.72rem; padding:2px 7px; border-radius:10px; background:#f0f0f0; color:#888; font-weight:500; }
.ai-ch-badge-on { background:#dceeff; color:#1a6fd4; }
.ai-ch-arrow { color:#aaa; transition:transform .2s; flex-shrink:0; }
.ai-ch-card.ai-ch-open .ai-ch-arrow { transform:rotate(180deg); }
.ai-ch-body { padding:0 14px 14px; display:flex; flex-direction:column; gap:8px; }
.ai-ch-hint { font-size:0.78rem; color:#888; margin:2px 0 4px; line-height:1.5; }
.ai-ch-body textarea { width:100%; box-sizing:border-box; }
.ai-ch-clear { align-self:flex-start; background:none; border:1px solid #e88; color:#c33; font-size:0.78rem; border-radius:4px; padding:3px 10px; cursor:pointer; }
.ai-ch-clear:hover { background:#fff0f0; }
</style>

<script>
var newSrcIdx = <?= count($sources) ?>;
function crmDeleteSrc(btn, srcId) {
    if (srcId > 0) {
        var inp = document.createElement('input'); inp.type='hidden'; inp.name='src_delete[]'; inp.value=srcId;
        btn.closest('form').appendChild(inp);
    }
    btn.closest('tr').remove();
}
function crmAddSrc() {
    var i = newSrcIdx++;
    var tr = document.createElement('tr');
    tr.innerHTML = '<td><input type="hidden" name="src_id[]" value="0"><input type="hidden" name="src_order[]" value="'+i+'"><input type="text" name="src_phone[]" placeholder="060XXXXX"></td>'
        +'<td><input type="text" name="src_name[]" placeholder="Denumire Canal"></td>'
        +'<td><select name="src_dept[]"><option value="stock"><?= addslashes($cL['settings_src_stock']) ?></option><option value="order"><?= addslashes($cL['settings_src_order']) ?></option><option value="pruncul">Filială Pruncul</option></select></td>'
        +'<td><input type="color" name="src_color[]" value="#888888"></td>'
        +'<td style="text-align:center;"><input type="checkbox" name="src_active['+i+']" value="1" checked></td>'
        +'<td><button type="button" class="crm-btn crm-btn-danger crm-btn-sm" onclick="this.closest(\'tr\').remove()">✕</button></td>';
    document.getElementById('crm-src-tbody').appendChild(tr);
}
document.getElementById('crm-src-form').addEventListener('submit', function() {
    var checked = Array.from(document.querySelectorAll('input[name="work_days_arr[]"]:checked')).map(el=>el.value);
    var inp = document.createElement('input'); inp.type='hidden'; inp.name='work_days'; inp.value=checked.join(',');
    this.appendChild(inp);
});
// work_hours form needs the same handler
document.querySelectorAll('form').forEach(function(f) {
    if (f.querySelector('[name="work_days_arr[]"]')) {
        f.addEventListener('submit', function() {
            var checked = Array.from(this.querySelectorAll('input[name="work_days_arr[]"]:checked')).map(el=>el.value);
            var inp = document.createElement('input'); inp.type='hidden'; inp.name='work_days'; inp.value=checked.join(',');
            this.appendChild(inp);
        });
    }
});

function crmDeleteAgent(btn, agId) {
    if (!confirm('<?= addslashes($cL['confirm_delete_agent']) ?>')) return;
    if (agId > 0) {
        var inp = document.createElement('input'); inp.type='hidden'; inp.name='agent_delete[]'; inp.value=agId;
        btn.closest('form').appendChild(inp);
    }
    btn.closest('tr').remove();
}
function crmAddAgent() {
    var tbody = document.getElementById('crm-agents-tbody');
    var tr = document.createElement('tr');
    tr.innerHTML = '<td><input type="text" name="new_agent_name" placeholder="<?= addslashes($cL['agent_name']) ?>" style="width:160px;"></td>'
        +'<td><select name="new_agent_dept"><option value=""><?= addslashes($cL['all']) ?></option><option value="stock"><?= addslashes($cL['settings_src_stock']) ?></option><option value="order"><?= addslashes($cL['settings_src_order']) ?></option><option value="pruncul">Filială Pruncul</option></select></td>'
        +'<td><select name="new_agent_crm_access"><option value=""><?= addslashes($cL['all']) ?></option><option value="stock"><?= addslashes($cL['settings_src_stock']) ?></option><option value="order"><?= addslashes($cL['settings_src_order']) ?></option><option value="pruncul">Filială Pruncul</option></select></td>'
        +'<td><input type="text" name="new_agent_pbx" placeholder="<?= addslashes($cL['agent_pbx_ph']) ?>" style="width:180px;"></td>'
        +'<td><input type="text" name="new_agent_phone" placeholder="<?= addslashes($cL['agent_phone_ph']) ?>" style="width:160px;"></td>'
        +'<td><button type="button" class="crm-btn crm-btn-danger crm-btn-sm" onclick="this.closest(\'tr\').remove()">✕</button></td>';
    tbody.appendChild(tr);
    tr.querySelector('input').focus();
}

function aiChToggle(ch) {
    var card = document.getElementById('ai-ch-' + ch);
    var body = card.querySelector('.ai-ch-body');
    var isOpen = card.classList.contains('ai-ch-open');
    if (isOpen) {
        body.style.display = 'none';
        card.classList.remove('ai-ch-open');
    } else {
        body.style.display = 'flex';
        card.classList.add('ai-ch-open');
    }
}
document.querySelectorAll('.ai-ch-switch input[type=checkbox]').forEach(function(cb) {
    cb.addEventListener('change', function() {
        var card = cb.closest('.ai-ch-card');
        if (cb.checked) card.classList.remove('ai-ch-disabled');
        else card.classList.add('ai-ch-disabled');
    });
});
function aiChClear(ch, key) {
    var card = document.getElementById('ai-ch-' + ch);
    card.querySelector('textarea[name="' + key + '"]').value = '';
    card.classList.remove('ai-ch-active');
    var badge = card.querySelector('.ai-ch-badge');
    badge.className = 'ai-ch-badge';
    badge.textContent = 'default';
    var clearBtn = card.querySelector('.ai-ch-clear');
    if (clearBtn) clearBtn.remove();
}

function crmViberSetWebhook() {
    var token = document.querySelector('[name="viber_bot_token"]').value.trim();
    if (!token) { alert('<?= addslashes($cL['settings_viber_token']) ?>'); return; }
    var el = document.getElementById('viber-wh-status');
    el.textContent = '<?= addslashes($cL['settings_viber_registering']) ?>';
    fetch('/ajax.php', {
        method: 'POST',
        headers: {'Content-Type': 'application/x-www-form-urlencoded'},
        body: 'tp=adm&pg=crm&action=viber_set_webhook&token=' + encodeURIComponent(token)
    })
    .then(r => r.json())
    .then(d => { el.textContent = d.ok ? '✅ ' + (d.msg || 'Registered') : '❌ ' + (d.msg || 'Error'); })
    .catch(() => { el.textContent = '❌ Request failed'; });
}
</script>
