<?php defined('_DOIT') or die('Restricted access');

/**
 * "B2B Management" AJAX (spec 3 / 1.2.C).
 *
 * Routed by /ajax.php once the admin session is validated. The role is
 * re-checked here: ajax.php lets `gordon` past its generic check, so this is
 * where the Super-Admin-only restriction actually takes effect.
 */

use App\Core\Container;
use App\Services\B2b\B2bAudit;
use App\Services\B2b\B2bAuth;
use App\Services\B2b\B2bConfig;
use App\Services\B2b\B2bRegions;

header('Content-Type: application/json; charset=UTF-8');

try {
    Container::get('db');
} catch (\Throwable $e) {
    Container::set('db', $db);
    Container::set('prefix', $prefx);
}

if (($user_role ?? '') !== 'gordon') {
    echo json_encode(['ok' => false, 'error' => 'Acces restricționat.']);
    exit;
}

$fn  = (string)($_POST['fn'] ?? '');
$uid = (int)($_POST['user_id'] ?? 0);

/** @return never */
function b2b_adm_out(array $payload): void
{
    echo json_encode($payload, JSON_UNESCAPED_UNICODE);
    exit;
}

switch ($fn) {

    // ---- Approve / block account (spec 3.1) ---------------------------------
    case 'set_status': {
        $status = (string)($_POST['status'] ?? '');
        if (!in_array($status, ['pending', 'active', 'blocked'], true)) {
            b2b_adm_out(['ok' => false, 'error' => 'Status invalid.']);
        }
        if (!B2bAuth::findById($uid)) {
            b2b_adm_out(['ok' => false, 'error' => 'Client inexistent.']);
        }

        try {
            $db->prepare('UPDATE '.B2bConfig::table('users').' SET status = :s WHERE id = :id')
               ->execute([':s' => $status, ':id' => $uid]);
        } catch (Throwable $e) {
            B2bConfig::log('b2b_error.log', 'admin set_status err='.$e->getMessage());
            b2b_adm_out(['ok' => false, 'error' => 'Statusul nu a putut fi schimbat.']);
        }

        // A non-active account must not keep open sessions.
        if ($status !== 'active') {
            B2bAuth::destroyAllSessions($uid);
        }

        B2bAudit::log($uid, B2bAudit::STATUS_CHANGED, ['status' => $status, 'by_admin' => (int)$user_id]);

        b2b_adm_out(['ok' => true, 'status' => $status]);
    }

    // ---- Region permissions (spec 3.2) --------------------------------------
    case 'set_permissions': {
        if (!B2bAuth::findById($uid)) {
            b2b_adm_out(['ok' => false, 'error' => 'Client inexistent.']);
        }

        $raw = $_POST['regions'] ?? [];
        if (is_string($raw)) {
            $raw = $raw === '' ? [] : explode(',', $raw);
        }

        $regions = [];
        foreach ((array)$raw as $r) {
            $r = trim((string)$r);
            if (in_array($r, B2bConfig::REGIONS, true)) {
                $regions[] = $r;
            }
        }

        try {
            B2bRegions::save($uid, $regions);
        } catch (Throwable $e) {
            B2bConfig::log('b2b_error.log', 'admin set_permissions err='.$e->getMessage());
            b2b_adm_out(['ok' => false, 'error' => 'Permisiunile nu au putut fi salvate.']);
        }

        B2bAudit::log($uid, B2bAudit::PERMISSIONS_CHANGED, ['regions' => $regions, 'by_admin' => (int)$user_id]);

        b2b_adm_out(['ok' => true, 'regions' => $regions]);
    }

    // ---- Password reset (spec 3.1) ------------------------------------------
    case 'reset_password': {
        if (!B2bAuth::findById($uid)) {
            b2b_adm_out(['ok' => false, 'error' => 'Client inexistent.']);
        }

        $res = B2bAuth::setPassword($uid, (string)($_POST['password'] ?? ''));
        if (!$res['ok']) {
            b2b_adm_out($res);
        }

        b2b_adm_out(['ok' => true]);
    }

    // ---- Client legal details (used on the proforma) ------------------------
    case 'save_profile': {
        if (!B2bAuth::findById($uid)) {
            b2b_adm_out(['ok' => false, 'error' => 'Client inexistent.']);
        }

        // Whitelist: email, status and password hash are NOT editable here.
        $allowed = ['company_name', 'representative_name', 'phone_number',
                    'legal_address', 'bank_name', 'bank_iban', 'vat_code', 'admin_note'];

        $sets = [];
        $args = [':id' => $uid];

        foreach ($allowed as $field) {
            if (!array_key_exists($field, $_POST)) {
                continue;
            }
            $sets[] = '`'.$field.'` = :'.$field;
            $args[':'.$field] = mb_substr(trim((string)$_POST[$field]), 0, 2000);
        }

        // IDNO is validated separately: it is the fiscal id shown on the proforma.
        if (array_key_exists('idno', $_POST)) {
            $idno = trim((string)$_POST['idno']);
            if (!preg_match('/^\d{13}$/', $idno)) {
                b2b_adm_out(['ok' => false, 'error' => 'IDNO trebuie să conțină exact 13 cifre.']);
            }
            $sets[] = '`idno` = :idno';
            $args[':idno'] = $idno;
        }

        if (!$sets) {
            b2b_adm_out(['ok' => true]);
        }

        try {
            $db->prepare('UPDATE '.B2bConfig::table('users').' SET '.implode(', ', $sets).' WHERE id = :id')
               ->execute($args);
        } catch (Throwable $e) {
            B2bConfig::log('b2b_error.log', 'admin save_profile err='.$e->getMessage());
            b2b_adm_out(['ok' => false, 'error' => 'Datele nu au putut fi salvate.']);
        }

        b2b_adm_out(['ok' => true]);
    }

    // ---- Permanent delete (spec 3.1) ----------------------------------------
    case 'delete_user': {
        if (!B2bAuth::findById($uid)) {
            b2b_adm_out(['ok' => false, 'error' => 'Client inexistent.']);
        }

        try {
            // Foreign keys are ON DELETE CASCADE: sessions, OTPs, permissions,
            // logs, invoices and requests go with the account.
            $db->prepare('DELETE FROM '.B2bConfig::table('users').' WHERE id = :id')->execute([':id' => $uid]);
        } catch (Throwable $e) {
            B2bConfig::log('b2b_error.log', 'admin delete_user err='.$e->getMessage());
            b2b_adm_out(['ok' => false, 'error' => 'Clientul nu a putut fi șters.']);
        }

        b2b_adm_out(['ok' => true]);
    }

    // ---- Request status ------------------------------------------------------
    case 'set_request_status': {
        $rid    = (int)($_POST['request_id'] ?? 0);
        $status = (string)($_POST['status'] ?? '');

        if (!in_array($status, ['new', 'seen', 'approved', 'rejected'], true)) {
            b2b_adm_out(['ok' => false, 'error' => 'Status invalid.']);
        }

        try {
            $stmt = $db->prepare('UPDATE '.B2bConfig::table('requests').' SET status = :s WHERE id = :id');
            $stmt->execute([':s' => $status, ':id' => $rid]);
            if ($stmt->rowCount() === 0) {
                // Missing row, or already in that status - both are benign.
                $check = $db->prepare('SELECT id FROM '.B2bConfig::table('requests').' WHERE id = :id LIMIT 1');
                $check->execute([':id' => $rid]);
                if (!$check->fetchColumn()) {
                    b2b_adm_out(['ok' => false, 'error' => 'Cerere inexistentă.']);
                }
            }
        } catch (Throwable $e) {
            B2bConfig::log('b2b_error.log', 'admin set_request_status err='.$e->getMessage());
            b2b_adm_out(['ok' => false, 'error' => 'Statusul nu a putut fi schimbat.']);
        }

        b2b_adm_out(['ok' => true, 'status' => $status]);
    }

    // ---- Module settings -----------------------------------------------------
    case 'save_settings': {
        $allowed = [
            'b2b_sms_driver', 'b2b_sms_sender', 'b2b_sms_api_user', 'b2b_sms_api_pass',
            'b2b_sms_api_key', 'b2b_sms_api_url', 'b2b_sms_debug_email',
            'b2b_whatsapp_driver', 'b2b_whatsapp_phone_id', 'b2b_whatsapp_token', 'b2b_whatsapp_template',
            'b2b_superadmin_phone', 'b2b_superadmin_email',
            'b2b_advance_default', 'b2b_advance_mode', 'b2b_advance_percent', 'b2b_advance_max',
        ];

        $incoming = $_POST['settings'] ?? [];
        if (!is_array($incoming)) {
            b2b_adm_out(['ok' => false, 'error' => 'Date invalide.']);
        }

        try {
            foreach ($incoming as $key => $value) {
                if (!in_array($key, $allowed, true)) {
                    continue; // ignore unexpected keys
                }
                B2bConfig::set($key, mb_substr(trim((string)$value), 0, 1000));
            }
        } catch (Throwable $e) {
            B2bConfig::log('b2b_error.log', 'admin save_settings err='.$e->getMessage());
            b2b_adm_out(['ok' => false, 'error' => 'Setările nu au putut fi salvate.']);
        }

        b2b_adm_out(['ok' => true]);
    }

    default:
        b2b_adm_out(['ok' => false, 'error' => 'Funcție necunoscută: '.$fn]);
}
