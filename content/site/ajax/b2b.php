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
use App\Services\B2b\B2bRegions;

$b2b_fn   = (string)($_POST['fn'] ?? '');
$b2b_lang = $_COOKIE['lang'] ?? 'ro';

$b2b_fail = function (string $message, array $extra = []) use (&$returnIt, $b2b_fn) {
    $returnIt = array_merge(['fn' => $b2b_fn, 'ok' => false, 'error' => $message], $extra);
};

$b2b_ok = function (array $data = []) use (&$returnIt, $b2b_fn) {
    $returnIt = array_merge(['fn' => $b2b_fn, 'ok' => true], $data);
};

// ---------------------------------------------------------------- CSRF + auth

if (!B2bCsrf::check($_POST['csrf'] ?? null)) {
    $b2b_fail(B2bAuth::msg('csrf_expired'), ['csrf_expired' => true]);
    return;
}

$b2b_needs_auth = ['b2b_logout', 'b2b_save_car', 'b2b_unsave_car', 'b2b_sync_favorites', 'b2b_create_invoice', 'b2b_send_request'];
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

        $user = B2bAuth::findById((int)$res['user_id']);
        if ($user) {
            B2bNotifier::notifyNewAccount($user);
        }

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

    // ---- Saved cars (spec 2.3.1) --------------------------------------------
    case 'b2b_save_car':
    case 'b2b_unsave_car': {
        $userId = b2b_user_id();
        $carId  = (int)($_POST['car_id'] ?? 0);

        if ($carId <= 0 || !B2bInvoice::loadCar($carId)) {
            $b2b_fail('Mașina nu a fost găsită.');
            break;
        }

        $region = B2bRegions::regionForCar($carId);
        if ($region !== null && !B2bRegions::isAllowed($userId, $region)) {
            B2bAudit::log($userId, B2bAudit::REGION_DENIED, ['car_id' => $carId, 'region' => $region, 'via' => 'save'], $carId);
            $b2b_fail('Mașina nu a fost găsită.'); // do not reveal a plan restriction
            break;
        }

        try {
            if ($b2b_fn === 'b2b_save_car') {
                $db->prepare(
                    'INSERT IGNORE INTO '.B2bConfig::table('saved_cars').' (b2b_user_id, car_id) VALUES (:uid, :car)'
                )->execute([':uid' => $userId, ':car' => $carId]);
                B2bAudit::log($userId, B2bAudit::SAVE_CAR, ['car_id' => $carId], $carId);
                $b2b_ok(['saved' => true, 'message' => 'Mașina a fost salvată în cabinet.']);
            } else {
                $db->prepare(
                    'DELETE FROM '.B2bConfig::table('saved_cars').' WHERE b2b_user_id = :uid AND car_id = :car'
                )->execute([':uid' => $userId, ':car' => $carId]);
                B2bAudit::log($userId, B2bAudit::UNSAVE_CAR, ['car_id' => $carId], $carId);
                $b2b_ok(['saved' => false, 'message' => 'Mașina a fost eliminată din cabinet.']);
            }
        } catch (Throwable $e) {
            B2bConfig::log('b2b_error.log', 'save_car err='.$e->getMessage());
            $b2b_fail('Operațiunea nu a putut fi finalizată.');
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
            $b2b_fail('Sincronizarea nu a putut fi finalizată.');
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
                'message'    => 'Aveți deja un cont de plată pentru această mașină.',
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
            $b2b_fail('Completați câmpurile obligatorii: nume, IDNP/IDNO și telefon.');
            break;
        }

        // IDNP (individuals) / IDNO (companies) are both 13-digit numeric codes.
        if (!preg_match('/^\d{13}$/', trim($docMeta['buyer_idno']))) {
            $b2b_fail('IDNP/IDNO trebuie să conțină exact 13 cifre.');
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
            'message'    => 'Contul de plată a fost generat.',
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
            $b2b_fail('Mașina nu a fost găsită.');
            break;
        }

        $region = B2bRegions::regionForCar($carId);
        if ($region !== null && !B2bRegions::isAllowed($userId, $region)) {
            B2bAudit::log($userId, B2bAudit::REGION_DENIED, ['car_id' => $carId, 'region' => $region, 'via' => 'request'], $carId);
            $b2b_fail('Mașina nu a fost găsită.'); // do not reveal a plan restriction
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
            $b2b_fail('Cererea nu a putut fi înregistrată.');
            break;
        }

        B2bAudit::log($userId, B2bAudit::SEND_TO_ADMIN, [
            'car_id'     => $carId,
            'request_id' => $requestId,
            'invoice_id' => $invoice ? (int)$invoice['id'] : null,
        ], $carId);

        // Already stored and audited, so a notification failure cannot lose it.
        $notify = B2bNotifier::notifyRequest($user, $car, $invoice, $comment, $requestId);

        $b2b_ok([
            'request_id' => $requestId,
            'wa_links'   => $notify['links'] ?? [],
            'message'    => 'Cererea a fost transmisă către Super Admin.',
        ]);
        break;
    }

    default:
        $b2b_fail('Acțiune necunoscută.');
}
