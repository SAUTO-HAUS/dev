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
use App\Services\B2b\B2bGift;
use App\Services\B2b\B2bOffer;
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

    // ---- Block / unblock account (spec 3.1) ---------------------------------
    // Accounts are active on sign-up (no approval), so the admin only toggles
    // between active and blocked.
    case 'set_status': {
        $status = (string)($_POST['status'] ?? '');
        if (!in_array($status, ['active', 'blocked'], true)) {
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

        // A blocked account must not keep open sessions.
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
        // Neither are full_name and phone_number — the client supplied those and
        // only the client owns them; the profile form shows them locked.
        // Company/legal fields are not collected anywhere, so they are not editable.
        $allowed = ['admin_note'];

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
            'b2b_advance_default', 'b2b_advance_mode', 'b2b_advance_percent', 'b2b_advance_max',
            // Master switch on /adminsauto/b2b/users: '1' makes every partner's own
            // deadline inert so the general offer governs all of them again.
            'b2b_ignore_personal_deadlines',
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

    // ---- B2B pricing tables (global set, or one client's own set) ------------
    // A per-client scope (user_id) writes to that client's rows; without it, the
    // global rows (b2b_user_id IS NULL). Every card is a full replace within its
    // scope, so a client detaches from the global table only for the tables saved.
    case 'save_pricing': {
        $section = (string)($_POST['section'] ?? '');
        $rows    = json_decode((string)($_POST['rows'] ?? '[]'), true);
        if (!is_array($rows)) {
            b2b_adm_out(['ok' => false, 'error' => 'Date invalide.']);
        }

        $scopeUser = (int)($_POST['user_id'] ?? 0);
        if ($scopeUser > 0 && !B2bAuth::findById($scopeUser)) {
            b2b_adm_out(['ok' => false, 'error' => 'Client inexistent.']);
        }
        $uidVal   = $scopeUser > 0 ? $scopeUser : null;                 // NULL = global row
        $scopeSql = $scopeUser > 0 ? 'b2b_user_id = '.$scopeUser : 'b2b_user_id IS NULL';

        $pfx = B2bConfig::prefix();
        $tierMap  = [
            'commission' => [$pfx.'_b2b_commission_tiers', 'commission'],
            'delivery'   => [$pfx.'_b2b_eu_tiers',         'delivery'],
        ];
        $paramMap = [
            'eu_params' => $pfx.'_b2b_eu_params',
            'kr_params' => $pfx.'_b2b_kr_params',
            'us_params' => $pfx.'_b2b_us_params',
        ];

        try {
            if (isset($tierMap[$section])) {
                [$table, $valCol] = $tierMap[$section];

                // Full replace within the scope: tiny tables, simpler than diffing.
                $db->beginTransaction();
                $db->prepare('DELETE FROM `'.$table.'` WHERE '.$scopeSql)->execute();

                $ins = $db->prepare('INSERT INTO `'.$table.'`
                    (`b2b_user_id`, `price_from`, `price_to`, `'.$valCol.'`, `sort_order`)
                    VALUES (:uid, :pf, :pt, :val, :so)');
                $so = 0;
                foreach ($rows as $r) {
                    $so++;
                    $pt = $r['price_to'] ?? null;
                    $ins->execute([
                        ':uid' => $uidVal,
                        ':pf'  => (int)($r['price_from'] ?? 0),
                        ':pt'  => ($pt === null || $pt === '') ? null : (int)$pt,
                        ':val' => (int)($r['value'] ?? 0),
                        ':so'  => $so,
                    ]);
                }
                $db->commit();

            } elseif (isset($paramMap[$section])) {
                $table = $paramMap[$section];

                // value_type is a property of the cost, not edited per client; copy
                // it from the global definition so 'percent' is never lost.
                $vtypes = $db->query('SELECT param_key, value_type FROM `'.$table.'` WHERE b2b_user_id IS NULL')
                             ->fetchAll(PDO::FETCH_KEY_PAIR);

                $db->beginTransaction();
                $db->prepare('DELETE FROM `'.$table.'` WHERE '.$scopeSql)->execute();

                $ins = $db->prepare('INSERT INTO `'.$table.'`
                    (`b2b_user_id`, `param_key`, `value_type`, `amount_eur`, `enabled`, `sort_order`)
                    VALUES (:uid, :key, :vt, :amt, :en, :so)');
                $so = 0;
                foreach ($rows as $r) {
                    $key = trim((string)($r['key'] ?? ''));
                    if ($key === '') continue;
                    $so++;
                    $ins->execute([
                        ':uid' => $uidVal,
                        ':key' => $key,
                        ':vt'  => $vtypes[$key] ?? 'fixed',
                        ':amt' => (int)($r['amount'] ?? 0),
                        ':en'  => !empty($r['enabled']) ? 1 : 0,
                        ':so'  => $so,
                    ]);
                }
                $db->commit();

            } else {
                b2b_adm_out(['ok' => false, 'error' => 'Secțiune necunoscută.']);
            }
        } catch (Throwable $e) {
            if ($db->inTransaction()) { $db->rollBack(); }
            B2bConfig::log('b2b_error.log', 'admin save_pricing err='.$e->getMessage());
            b2b_adm_out(['ok' => false, 'error' => 'Prețurile nu au putut fi salvate.']);
        }

        if ($scopeUser > 0) {
            B2bAudit::log($scopeUser, B2bAudit::PERMISSIONS_CHANGED, ['pricing_section' => $section, 'by_admin' => (int)$user_id]);
        }

        b2b_adm_out(['ok' => true]);
    }

    // ---- Revert one of a client's price tables back to the global one --------
    case 'reset_pricing': {
        $scopeUser = (int)($_POST['user_id'] ?? 0);
        $section   = (string)($_POST['section'] ?? '');
        if ($scopeUser <= 0 || !B2bAuth::findById($scopeUser)) {
            b2b_adm_out(['ok' => false, 'error' => 'Client inexistent.']);
        }

        $pfx = B2bConfig::prefix();
        $map = [
            'commission' => $pfx.'_b2b_commission_tiers',
            'delivery'   => $pfx.'_b2b_eu_tiers',
            'eu_params'  => $pfx.'_b2b_eu_params',
            'kr_params'  => $pfx.'_b2b_kr_params',
            'us_params'  => $pfx.'_b2b_us_params',
        ];
        if (!isset($map[$section])) {
            b2b_adm_out(['ok' => false, 'error' => 'Secțiune necunoscută.']);
        }

        try {
            // Removing the client's rows makes the payload fall back to global.
            $db->prepare('DELETE FROM `'.$map[$section].'` WHERE b2b_user_id = :uid')
               ->execute([':uid' => $scopeUser]);
        } catch (Throwable $e) {
            B2bConfig::log('b2b_error.log', 'admin reset_pricing err='.$e->getMessage());
            b2b_adm_out(['ok' => false, 'error' => 'Nu s-a putut reveni la global.']);
        }

        B2bAudit::log($scopeUser, B2bAudit::PERMISSIONS_CHANGED, ['pricing_reset' => $section, 'by_admin' => (int)$user_id]);

        b2b_adm_out(['ok' => true]);
    }

    // ---- Deadline of the price offer, for one scope --------------------------
    // Same scoping rule as save_pricing: with user_id it is that client's own
    // offer, without it the general one. An empty value clears the deadline.
    case 'save_offer_expiry': {
        $scopeUser = (int)($_POST['user_id'] ?? 0);
        $expiresAt = (string)($_POST['expires_at'] ?? '');

        if ($scopeUser > 0 && !B2bAuth::findById($scopeUser)) {
            b2b_adm_out(['ok' => false, 'error' => 'Client inexistent.']);
        }

        try {
            B2bOffer::save($scopeUser > 0 ? $scopeUser : null, $expiresAt, (int)$user_id);
        } catch (Throwable $e) {
            B2bConfig::log('b2b_error.log', 'admin save_offer_expiry err='.$e->getMessage());
            b2b_adm_out(['ok' => false, 'error' => 'Termenul nu a putut fi salvat.']);
        }

        if ($scopeUser > 0) {
            B2bAudit::log($scopeUser, B2bAudit::PERMISSIONS_CHANGED, ['offer_expiry' => $expiresAt, 'by_admin' => (int)$user_id]);
        }

        b2b_adm_out(['ok' => true]);
    }

    // ---- Gifts granted to a client ------------------------------------------
    case 'send_gift': {
        $target = (int)($_POST['user_id'] ?? 0);
        $note   = (string)($_POST['note'] ?? '');

        // The admin JS flattens arrays into a comma-separated string before
        // posting, so accept both shapes rather than silently receiving none.
        $items = $_POST['items'] ?? [];
        if (is_string($items)) {
            $items = $items === '' ? [] : explode(',', $items);
        }
        $items = is_array($items) ? $items : [];

        if ($target <= 0 || !B2bAuth::findById($target)) {
            b2b_adm_out(['ok' => false, 'error' => 'Client inexistent.']);
        }

        // An empty gift would notify the client with nothing to show.
        $giftId = B2bGift::send($target, $items, $note, (int)$user_id);
        if ($giftId <= 0) {
            b2b_adm_out(['ok' => false, 'error' => 'Alege cel puțin un serviciu sau scrie o observație.']);
        }

        B2bAudit::log($target, B2bAudit::GIFT_SENT, [
            'gift_id'  => $giftId,
            'items'    => implode(',', array_intersect((array)$items, B2bGift::SERVICES)),
            'by_admin' => (int)$user_id,
        ], $giftId);

        b2b_adm_out(['ok' => true, 'gift_id' => $giftId]);
    }

    // Gifts of one client, for the dialog opened from the list. Fetched on demand
    // rather than preloaded with the table, which can hold 500 rows.
    case 'list_gifts': {
        $target = (int)($_POST['user_id'] ?? 0);
        if ($target <= 0) {
            b2b_adm_out(['ok' => false, 'error' => 'Client inexistent.']);
        }

        $lang = $_COOKIE['lang'] ?? 'ro';
        $out  = [];
        foreach (B2bGift::historyFor($target) as $g) {
            $out[] = [
                'id'      => (int)$g['id'],
                'what'    => B2bGift::describe($g, $lang),
                'note'    => (string)($g['note'] ?? ''),
                'date'    => date('d.m.Y H:i', strtotime((string)$g['created_at'])),
                'revoked' => !empty($g['revoked_at']),
                'seen'    => !empty($g['seen_at']),
            ];
        }

        b2b_adm_out(['ok' => true, 'gifts' => $out]);
    }

    case 'revoke_gift': {
        $giftId  = (int)($_POST['gift_id'] ?? 0);
        $ownerId = B2bGift::revoke($giftId, (int)$user_id);

        if ($ownerId <= 0) {
            b2b_adm_out(['ok' => false, 'error' => 'Cadoul nu a putut fi retras.']);
        }

        B2bAudit::log($ownerId, B2bAudit::GIFT_REVOKED, [
            'gift_id'  => $giftId,
            'by_admin' => (int)$user_id,
        ], $giftId);

        b2b_adm_out(['ok' => true]);
    }

    default:
        b2b_adm_out(['ok' => false, 'error' => 'Funcție necunoscută: '.$fn]);
}
