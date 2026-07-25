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

        // Catalog access (in stock / on order) lives on the user row, not in the
        // region table. Saved together with regions in the same panel.
        $allowInStock = !empty($_POST['allow_in_stock']) ? 1 : 0;
        $allowOnOrder = !empty($_POST['allow_on_order']) ? 1 : 0;

        try {
            B2bRegions::save($uid, $regions);
            $db->prepare('UPDATE '.B2bConfig::table('users')
                .' SET allow_in_stock = :ins, allow_on_order = :ord WHERE id = :id')
               ->execute([':ins' => $allowInStock, ':ord' => $allowOnOrder, ':id' => $uid]);
        } catch (Throwable $e) {
            B2bConfig::log('b2b_error.log', 'admin set_permissions err='.$e->getMessage());
            b2b_adm_out(['ok' => false, 'error' => 'Permisiunile nu au putut fi salvate.']);
        }

        B2bAudit::log($uid, B2bAudit::PERMISSIONS_CHANGED, [
            'regions'        => $regions,
            'allow_in_stock' => $allowInStock,
            'allow_on_order' => $allowOnOrder,
            'by_admin'       => (int)$user_id,
        ]);

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

    // ---- Client profile -----------------------------------------------------
    case 'save_profile': {
        if (!B2bAuth::findById($uid)) {
            b2b_adm_out(['ok' => false, 'error' => 'Client inexistent.']);
        }

        // Whitelist: login, email, status and password hash are NOT editable here.
        // Company/legal fields are not collected anywhere, so they are not editable.
        $allowed = ['full_name', 'phone_number', 'admin_note'];

        $sets = [];
        $args = [':id' => $uid];

        foreach ($allowed as $field) {
            if (!array_key_exists($field, $_POST)) {
                continue;
            }
            $sets[] = '`'.$field.'` = :'.$field;
            $args[':'.$field] = mb_substr(trim((string)$_POST[$field]), 0, 2000);
        }

        if (array_key_exists('person_type', $_POST)) {
            $ptype = (string)$_POST['person_type'];
            if (!in_array($ptype, B2bAuth::PERSON_TYPES, true)) {
                b2b_adm_out(['ok' => false, 'error' => 'Tip de persoană invalid.']);
            }
            $sets[] = '`person_type` = :person_type';
            $args[':person_type'] = $ptype;
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
            // Foreign keys are ON DELETE CASCADE: sessions, permissions, logs,
            // invoices and requests go with the account.
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
            'b2b_superadmin_phone', 'b2b_superadmin_phone_2',
            'b2b_superadmin_email', 'b2b_superadmin_email_2',
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

    // ---- Per-client price overrides (Faza C) ---------------------------------
    case 'save_price_overrides': {
        if (!B2bAuth::findById($uid)) {
            b2b_adm_out(['ok' => false, 'error' => 'Client inexistent.']);
        }

        $incoming = json_decode((string)($_POST['overrides'] ?? '{}'), true);
        if (!is_array($incoming)) {
            b2b_adm_out(['ok' => false, 'error' => 'Date invalide.']);
        }

        // Only these lines are overridable per client (commission + transport).
        $allowedKeys = ['commission', 'eu_delivery', 'sea_freight_roro'];
        $tbl = B2bConfig::prefix().'_b2b_price_overrides';

        try {
            $del = $db->prepare('DELETE FROM `'.$tbl.'` WHERE b2b_user_id = :uid AND param_key = :k');
            $ins = $db->prepare('INSERT INTO `'.$tbl.'` (b2b_user_id, param_key, amount) VALUES (:uid, :k, :a)');

            foreach ($allowedKeys as $key) {
                if (!array_key_exists($key, $incoming)) {
                    continue;
                }
                $raw = trim((string)$incoming[$key]);
                // Empty clears the override (fall back to the global B2B price).
                $del->execute([':uid' => $uid, ':k' => $key]);
                if ($raw !== '') {
                    $ins->execute([':uid' => $uid, ':k' => $key, ':a' => (int)$raw]);
                }
            }
        } catch (Throwable $e) {
            B2bConfig::log('b2b_error.log', 'admin save_price_overrides err='.$e->getMessage());
            b2b_adm_out(['ok' => false, 'error' => 'Prețurile nu au putut fi salvate.']);
        }

        B2bAudit::log($uid, B2bAudit::PERMISSIONS_CHANGED, ['price_overrides' => $incoming, 'by_admin' => (int)$user_id]);

        b2b_adm_out(['ok' => true]);
    }

    // ---- B2B pricing tables (Faza B) -----------------------------------------
    case 'save_pricing': {
        $section = (string)($_POST['section'] ?? '');
        $rows    = json_decode((string)($_POST['rows'] ?? '[]'), true);
        if (!is_array($rows)) {
            b2b_adm_out(['ok' => false, 'error' => 'Date invalide.']);
        }

        // section -> [table, value column]. Tiers are fully replaced; param
        // tables are updated row by row (fixed rows, never added/removed).
        $tierMap  = [
            'commission' => [B2bConfig::prefix().'_b2b_commission_tiers', 'commission'],
            'delivery'   => [B2bConfig::prefix().'_b2b_eu_tiers',         'delivery'],
        ];
        $paramMap = [
            'eu_params' => B2bConfig::prefix().'_b2b_eu_params',
            'kr_params' => B2bConfig::prefix().'_b2b_kr_params',
        ];

        try {
            if (isset($tierMap[$section])) {
                [$table, $valCol] = $tierMap[$section];

                // Full replace: the tables are tiny (a handful of price bands),
                // so rebuilding is simpler and safer than per-row diffing.
                $db->beginTransaction();
                $db->prepare('DELETE FROM `'.$table.'`')->execute();

                $ins = $db->prepare('INSERT INTO `'.$table.'`
                    (`price_from`, `price_to`, `'.$valCol.'`, `sort_order`)
                    VALUES (:pf, :pt, :val, :so)');
                $so = 0;
                foreach ($rows as $r) {
                    $so++;
                    $pt = $r['price_to'];
                    $ins->execute([
                        ':pf'  => (int)($r['price_from'] ?? 0),
                        ':pt'  => ($pt === null || $pt === '') ? null : (int)$pt,
                        ':val' => (int)($r['value'] ?? 0),
                        ':so'  => $so,
                    ]);
                }
                $db->commit();

            } elseif (isset($paramMap[$section])) {
                $table = $paramMap[$section];
                $upd = $db->prepare('UPDATE `'.$table.'`
                    SET `enabled` = :en, `amount_eur` = :amt WHERE `id` = :id');
                foreach ($rows as $r) {
                    $id = (int)($r['id'] ?? 0);
                    if ($id <= 0) continue;
                    $upd->execute([
                        ':en'  => !empty($r['enabled']) ? 1 : 0,
                        ':amt' => (int)($r['amount'] ?? 0),
                        ':id'  => $id,
                    ]);
                }
            } else {
                b2b_adm_out(['ok' => false, 'error' => 'Secțiune necunoscută.']);
            }
        } catch (Throwable $e) {
            if ($db->inTransaction()) { $db->rollBack(); }
            B2bConfig::log('b2b_error.log', 'admin save_pricing err='.$e->getMessage());
            b2b_adm_out(['ok' => false, 'error' => 'Prețurile nu au putut fi salvate.']);
        }

        b2b_adm_out(['ok' => true]);
    }

    default:
        b2b_adm_out(['ok' => false, 'error' => 'Funcție necunoscută: '.$fn]);
}
