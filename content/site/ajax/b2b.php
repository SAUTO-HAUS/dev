<?php defined( '_DOIT' ) or die( 'Restricted access' );

/**
 * B2B endpoints (the spec's /api/v1/b2b/... routes, mapped onto the site's
 * existing AJAX flow: POST /ajax.php with tp=ste).
 *
 * Rules:
 *   - the response goes into $returnIt, serialised by ajax.php;
 *   - every action requires a valid CSRF token;
 *   - authentication is read from the session, never from a request parameter.
 */

use App\Services\B2b\B2bAudit;
use App\Services\B2b\B2bAuth;
use App\Services\B2b\B2bConfig;
use App\Services\B2b\B2bCsrf;
use App\Services\B2b\B2bInvoice;
use App\Services\B2b\B2bNotifier;
use App\Services\B2b\B2bPasswordReset;
use App\Services\B2b\B2bRegions;

$b2b_fn   = (string)($_POST['fn'] ?? '');
$b2b_lang = $_COOKIE['lang'] ?? 'ro';

$b2b_fail = function (string $message, array $extra = []) use (&$returnIt, $b2b_fn) {
    $returnIt = array_merge(['fn' => $b2b_fn, 'ok' => false, 'error' => $message], $extra);
};

$b2b_ok = function (array $data = []) use (&$returnIt, $b2b_fn) {
    $returnIt = array_merge(['fn' => $b2b_fn, 'ok' => true], $data);
};

// Localised messages for the cabinet actions handled here (saved cars, invoices,
// requests). The auth flow's own strings live in B2bAuth::msg(); these belong to
// the AJAX layer, so they stay here. Reads the visitor's language cookie.
$b2b_msg = (function () use ($b2b_lang) {
    $cat = [
        'ro' => [
            'car_not_found'    => 'Mașina nu a fost găsită.',
            'car_saved'        => 'Mașina a fost salvată în cabinet.',
            'car_unsaved'      => 'Mașina a fost eliminată din cabinet.',
            'op_failed'        => 'Operațiunea nu a putut fi finalizată.',
            'sync_failed'      => 'Sincronizarea nu a putut fi finalizată.',
            'invoice_exists'   => 'Aveți deja un cont de plată pentru această mașină.',
            'invoice_required' => 'Completați câmpurile obligatorii: nume, IDNP/IDNO și telefon.',
            'idno_format'      => 'IDNP/IDNO trebuie să conțină exact 13 cifre.',
            'invoice_created'  => 'Contul de plată a fost generat.',
            'request_failed'   => 'Cererea nu a putut fi înregistrată.',
            'request_sent'     => 'Cererea a fost transmisă către Super Admin.',
            'unknown_action'   => 'Acțiune necunoscută.',
        ],
        'ru' => [
            'car_not_found'    => 'Автомобиль не найден.',
            'car_saved'        => 'Автомобиль сохранён в кабинете.',
            'car_unsaved'      => 'Автомобиль удалён из кабинета.',
            'op_failed'        => 'Не удалось выполнить операцию.',
            'sync_failed'      => 'Не удалось выполнить синхронизацию.',
            'invoice_exists'   => 'У вас уже есть счёт на оплату для этого автомобиля.',
            'invoice_required' => 'Заполните обязательные поля: имя, IDNP/IDNO и телефон.',
            'idno_format'      => 'IDNP/IDNO должен содержать ровно 13 цифр.',
            'invoice_created'  => 'Счёт на оплату сформирован.',
            'request_failed'   => 'Не удалось зарегистрировать заявку.',
            'request_sent'     => 'Заявка отправлена Супер-администратору.',
            'unknown_action'   => 'Неизвестное действие.',
        ],
        'en' => [
            'car_not_found'    => 'The car was not found.',
            'car_saved'        => 'The car was saved to your cabinet.',
            'car_unsaved'      => 'The car was removed from your cabinet.',
            'op_failed'        => 'The operation could not be completed.',
            'sync_failed'      => 'Synchronisation could not be completed.',
            'invoice_exists'   => 'You already have a payment invoice for this car.',
            'invoice_required' => 'Fill in the required fields: name, IDNP/IDNO and phone.',
            'idno_format'      => 'IDNP/IDNO must contain exactly 13 digits.',
            'invoice_created'  => 'The payment invoice was generated.',
            'request_failed'   => 'The request could not be recorded.',
            'request_sent'     => 'The request was sent to the Super Admin.',
            'unknown_action'   => 'Unknown action.',
        ],
    ];
    $set = $cat[$b2b_lang] ?? $cat['ro'];
    return function (string $key) use ($set, $cat) {
        return $set[$key] ?? ($cat['ro'][$key] ?? $key);
    };
})();

// ---------------------------------------------------------------- CSRF + auth

if (!B2bCsrf::check($_POST['csrf'] ?? null)) {
    $b2b_fail(B2bAuth::msg('csrf_expired'), ['csrf_expired' => true]);
    return;
}

$b2b_needs_auth = ['b2b_logout', 'b2b_change_password', 'b2b_save_car', 'b2b_unsave_car', 'b2b_sync_favorites', 'b2b_create_invoice', 'b2b_send_request'];
if (in_array($b2b_fn, $b2b_needs_auth, true) && !b2b_is_client()) {
    $b2b_fail(B2bAuth::msg('auth_required'), ['auth_required' => true]);
    return;
}

// -------------------------------------------------------------------- routing

switch ($b2b_fn) {

    // ---- Registration (spec 1.2.A POST /api/v1/b2b/register) ----------------
    case 'b2b_register': {
        $res = B2bAuth::register([
            'person_type'  => $_POST['person_type'] ?? '',
            'email'        => $_POST['email'] ?? '',
            'phone_number' => $_POST['phone'] ?? '',
            'full_name'    => $_POST['full_name'] ?? '',
            'login'        => $_POST['login'] ?? '',
            'password'     => $_POST['password'] ?? '',
        ]);

        if (!$res['ok']) {
            $b2b_fail($res['error'], isset($res['field']) ? ['field' => $res['field']] : []);
            break;
        }

        // Accounts are active immediately (no admin approval), so nothing to notify.
        $b2b_ok([
            'message' => B2bAuth::msg('register_ok'),
        ]);
        break;
    }

    // ---- Login: credentials checked, session opened -------------------------
    case 'b2b_login': {
        $res = B2bAuth::login((string)($_POST['login'] ?? ''), (string)($_POST['password'] ?? ''));

        if (!$res['ok']) {
            $b2b_fail($res['error'], isset($res['status']) ? ['status' => $res['status']] : []);
            break;
        }

        $b2b_ok(['redirect' => '/'.$b2b_lang.'/b2b/cabinet']);
        break;
    }

    case 'b2b_logout': {
        B2bAuth::logout();
        $b2b_ok(['redirect' => '/'.$b2b_lang.'/b2b-login']);
        break;
    }

    // ---- Change password (logged-in partner, from the cabinet) --------------
    case 'b2b_change_password': {
        $current = (string)($_POST['current'] ?? '');
        $new     = (string)($_POST['new'] ?? '');
        $confirm = (string)($_POST['confirm'] ?? '');

        if ($new !== $confirm) {
            $b2b_fail(B2bAuth::msg('password_mismatch'), ['field' => 'confirm']);
            break;
        }
        $res = B2bAuth::changePassword(b2b_user_id(), $current, $new);
        if (!$res['ok']) {
            $b2b_fail($res['error']);
            break;
        }
        $b2b_ok(['message' => B2bAuth::msg('password_changed')]);
        break;
    }

    // ---- Forgot password: email a one-time reset link (always silent) --------
    case 'b2b_forgot_password': {
        B2bPasswordReset::request((string)($_POST['email'] ?? ''), $b2b_lang);
        // Same answer whether or not the email matched an account.
        $b2b_ok(['message' => B2bAuth::msg('forgot_sent')]);
        break;
    }

    // ---- Reset password with a token from the emailed link ------------------
    case 'b2b_reset_password': {
        $new     = (string)($_POST['new'] ?? '');
        $confirm = (string)($_POST['confirm'] ?? '');

        if ($new !== $confirm) {
            $b2b_fail(B2bAuth::msg('password_mismatch'), ['field' => 'confirm']);
            break;
        }
        $res = B2bPasswordReset::complete((string)($_POST['token'] ?? ''), $new);
        if (!$res['ok']) {
            $b2b_fail($res['error']);
            break;
        }
        $b2b_ok(['message' => B2bAuth::msg('reset_done')]);
        break;
    }

    // ---- Saved cars (spec 2.3.1) --------------------------------------------
    case 'b2b_save_car':
    case 'b2b_unsave_car': {
        $userId = b2b_user_id();
        $carId  = (int)($_POST['car_id'] ?? 0);

        if ($carId <= 0 || !B2bInvoice::loadCar($carId)) {
            $b2b_fail($b2b_msg('car_not_found'));
            break;
        }

        $region = B2bRegions::regionForCar($carId);
        if ($region !== null && !B2bRegions::isAllowed($userId, $region)) {
            B2bAudit::log($userId, B2bAudit::REGION_DENIED, ['car_id' => $carId, 'region' => $region, 'via' => 'save'], $carId);
            $b2b_fail($b2b_msg('car_not_found')); // do not reveal a plan restriction
            break;
        }

        try {
            if ($b2b_fn === 'b2b_save_car') {
                $db->prepare(
                    'INSERT IGNORE INTO '.B2bConfig::table('saved_cars').' (b2b_user_id, car_id) VALUES (:uid, :car)'
                )->execute([':uid' => $userId, ':car' => $carId]);
                B2bAudit::log($userId, B2bAudit::SAVE_CAR, ['car_id' => $carId], $carId);
                $b2b_ok(['saved' => true, 'message' => $b2b_msg('car_saved')]);
            } else {
                $db->prepare(
                    'DELETE FROM '.B2bConfig::table('saved_cars').' WHERE b2b_user_id = :uid AND car_id = :car'
                )->execute([':uid' => $userId, ':car' => $carId]);
                B2bAudit::log($userId, B2bAudit::UNSAVE_CAR, ['car_id' => $carId], $carId);
                $b2b_ok(['saved' => false, 'message' => $b2b_msg('car_unsaved')]);
            }
        } catch (Throwable $e) {
            B2bConfig::log('b2b_error.log', 'save_car err='.$e->getMessage());
            $b2b_fail($b2b_msg('op_failed'));
        }
        break;
    }

    // ---- Reconcile favourites: localStorage <-> cabinet (DB) ----------------
    // Called once per session on page load. The site keeps favourites in
    // localStorage; the cabinet reads them from the DB. Clearing the browser
    // (or a second device) would otherwise leave the two out of sync. Here we
    // import the client's local-only favourites into the DB (skipping cars they
    // may not access), then return the full merged list so the site adopts the
    // durable cabinet set. Every id in the reply is guaranteed to be a real,
    // visible, permitted car, so the client can also prune stale ids from it.
    case 'b2b_sync_favorites': {
        $userId = b2b_user_id();

        $localIds = [];
        if (isset($_POST['ids'])) {
            $raw = is_array($_POST['ids']) ? $_POST['ids'] : explode(',', (string)$_POST['ids']);
            foreach ($raw as $r) { $r = (int)$r; if ($r > 0) { $localIds[$r] = $r; } }
        }

        try {
            $st = $db->prepare(
                'SELECT car_id FROM '.B2bConfig::table('saved_cars').' WHERE b2b_user_id = :uid'
            );
            $st->execute([':uid' => $userId]);
            $dbIds = array_map('intval', $st->fetchAll(PDO::FETCH_COLUMN) ?: []);
            $dbSet = array_flip($dbIds);

            // Import local-only favourites that the client is actually allowed to
            // see (same existence + region check as a single save).
            $imported = [];
            $ins = $db->prepare(
                'INSERT IGNORE INTO '.B2bConfig::table('saved_cars').' (b2b_user_id, car_id) VALUES (:uid, :car)'
            );
            foreach ($localIds as $cid) {
                if (isset($dbSet[$cid])) { continue; }
                if (!B2bInvoice::loadCar($cid)) { continue; }             // exists & visible
                $region = B2bRegions::regionForCar($cid);
                if ($region !== null && !B2bRegions::isAllowed($userId, $region)) { continue; } // hidden by plan
                $ins->execute([':uid' => $userId, ':car' => $cid]);
                $imported[] = $cid;
            }

            $merged = array_values(array_unique(array_merge($dbIds, $imported)));
            $b2b_ok(['ids' => $merged, 'imported' => count($imported)]);
        } catch (Throwable $e) {
            B2bConfig::log('b2b_error.log', 'sync_fav err='.$e->getMessage());
            $b2b_fail($b2b_msg('sync_failed'));
        }
        break;
    }

    // ---- Proforma (spec 2.3.2) ----------------------------------------------
    case 'b2b_create_invoice': {
        $userId = b2b_user_id();
        $user   = b2b_user();
        $carId  = (int)($_POST['car_id'] ?? 0);

        // One payment invoice per car per partner: if it already exists, reopen it
        // instead of issuing a duplicate (and re-notifying the admin).
        $existing = B2bInvoice::findForCar($userId, $carId);
        if ($existing) {
            $b2b_ok([
                'invoice_id' => (int)$existing['id'],
                'invoice_no' => $existing['invoice_no'],
                'url'        => B2bInvoice::path($existing),
                'existing'   => true,
                'message'    => $b2b_msg('invoice_exists'),
            ]);
            break;
        }

        // Document fields typed on the payment-invoice form (frozen onto the doc).
        $docMeta = [
            'date'        => (string)($_POST['date'] ?? ''),
            'br'          => (string)($_POST['br'] ?? ''),
            'mo'          => (string)($_POST['mo'] ?? ''),
            'vin'         => (string)($_POST['vin'] ?? ''),
            'buyer_type'  => (string)($_POST['buyer_type'] ?? ''),
            'buyer_name'  => (string)($_POST['buyer_name'] ?? ''),
            'buyer_idno'  => (string)($_POST['buyer_idno'] ?? ''),
            'buyer_phone' => (string)($_POST['buyer_phone'] ?? ''),
        ];

        // Buyer name / IDNP-IDNO / phone are mandatory on an issued document.
        if (trim($docMeta['buyer_name']) === ''
            || trim($docMeta['buyer_idno']) === ''
            || trim($docMeta['buyer_phone']) === '') {
            $b2b_fail($b2b_msg('invoice_required'));
            break;
        }

        // IDNP (individuals) / IDNO (companies) are both 13-digit numeric codes.
        if (!preg_match('/^\d{13}$/', trim($docMeta['buyer_idno']))) {
            $b2b_fail($b2b_msg('idno_format'));
            break;
        }

        $res = B2bInvoice::create(
            $userId,
            $carId,
            (float)str_replace(',', '.', (string)($_POST['advance'] ?? '0')),
            'MDL', // payment invoices are always issued in MDL (converted at the BNM rate on the form)
            $docMeta
        );

        if (!$res['ok']) {
            $b2b_fail($res['error']);
            break;
        }

        $invoice = $res['invoice'];

        // Merge (spec 2.3.3): creating a payment invoice IS the reservation, so
        // record a Super Admin request with the proforma attached and notify him
        // (bell + WhatsApp), the same as the old separate "send to admin" button.
        $car = B2bInvoice::loadCar($carId);
        if ($car) {
            $requestId = 0;
            try {
                $db->prepare(
                    'INSERT INTO '.B2bConfig::table('requests').' (b2b_user_id, car_id, invoice_id, comment)
                     VALUES (:uid, :car, :inv, NULL)'
                )->execute([':uid' => $userId, ':car' => $carId, ':inv' => (int)$invoice['id']]);
                $requestId = (int)$db->lastInsertId();
            } catch (Throwable $e) {
                B2bConfig::log('b2b_error.log', 'invoice->request err='.$e->getMessage());
            }
            B2bAudit::log($userId, B2bAudit::SEND_TO_ADMIN, ['car_id' => $carId, 'invoice_id' => (int)$invoice['id'], 'request_id' => $requestId], $carId);
            try { B2bNotifier::notifyRequest($user, $car, $invoice, '', $requestId); } catch (Throwable $e) {}
        }

        $b2b_ok([
            'invoice_id' => (int)$invoice['id'],
            'invoice_no' => $invoice['invoice_no'],
            'url'        => B2bInvoice::path($invoice),
            'message'    => $b2b_msg('invoice_created'),
        ]);
        break;
    }

    // ---- Send to Super Admin (spec 2.3.3) -----------------------------------
    case 'b2b_send_request': {
        $userId  = b2b_user_id();
        $user    = b2b_user();
        $carId   = (int)($_POST['car_id'] ?? 0);
        $comment = mb_substr(trim((string)($_POST['comment'] ?? '')), 0, 1000);

        $car = B2bInvoice::loadCar($carId);
        if (!$car) {
            $b2b_fail($b2b_msg('car_not_found'));
            break;
        }

        $region = B2bRegions::regionForCar($carId);
        if ($region !== null && !B2bRegions::isAllowed($userId, $region)) {
            B2bAudit::log($userId, B2bAudit::REGION_DENIED, ['car_id' => $carId, 'region' => $region, 'via' => 'request'], $carId);
            $b2b_fail($b2b_msg('car_not_found')); // do not reveal a plan restriction
            break;
        }

        // An attached proforma must belong to this partner and this car.
        $invoice   = null;
        $invoiceId = (int)($_POST['invoice_id'] ?? 0);
        if ($invoiceId > 0) {
            $candidate = B2bInvoice::findById($invoiceId);
            if ($candidate
                && (int)$candidate['b2b_user_id'] === $userId
                && (int)$candidate['car_id'] === $carId) {
                $invoice = $candidate;
            }
        }

        try {
            $db->prepare(
                'INSERT INTO '.B2bConfig::table('requests').' (b2b_user_id, car_id, invoice_id, comment)
                 VALUES (:uid, :car, :inv, :comment)'
            )->execute([
                ':uid'     => $userId,
                ':car'     => $carId,
                ':inv'     => $invoice ? (int)$invoice['id'] : null,
                ':comment' => $comment !== '' ? $comment : null,
            ]);
            $requestId = (int)$db->lastInsertId();
        } catch (Throwable $e) {
            B2bConfig::log('b2b_error.log', 'send_request err='.$e->getMessage());
            $b2b_fail($b2b_msg('request_failed'));
            break;
        }

        B2bAudit::log($userId, B2bAudit::SEND_TO_ADMIN, [
            'car_id'     => $carId,
            'request_id' => $requestId,
            'invoice_id' => $invoice ? (int)$invoice['id'] : null,
        ], $carId);

        // Already stored and audited, so a notification failure cannot lose it.
        B2bNotifier::notifyRequest($user, $car, $invoice, $comment, $requestId);

        $b2b_ok([
            'request_id' => $requestId,
            'message'    => $b2b_msg('request_sent'),
        ]);
        break;
    }

    default:
        $b2b_fail($b2b_msg('unknown_action'));
}
