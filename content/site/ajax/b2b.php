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
    $b2b_fail('Sesiune expirată. Reîncărcați pagina și încercați din nou.', ['csrf_expired' => true]);
    return;
}

$b2b_needs_auth = ['b2b_logout', 'b2b_save_car', 'b2b_unsave_car', 'b2b_create_invoice', 'b2b_send_request'];
if (in_array($b2b_fn, $b2b_needs_auth, true) && !b2b_is_client()) {
    $b2b_fail('Autentificare necesară.', ['auth_required' => true]);
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
            'message' => 'Contul a fost creat cu succes și este în așteptarea validării de către administrator.',
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
            $b2b_fail('Restricționat conform planului B2B.');
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

    // ---- Proforma (spec 2.3.2) ----------------------------------------------
    case 'b2b_create_invoice': {
        $res = B2bInvoice::create(
            b2b_user_id(),
            (int)($_POST['car_id'] ?? 0),
            (float)str_replace(',', '.', (string)($_POST['advance'] ?? '0')),
            (string)($_POST['currency'] ?? 'EUR')
        );

        if (!$res['ok']) {
            $b2b_fail($res['error']);
            break;
        }

        $b2b_ok([
            'invoice_id' => (int)$res['invoice']['id'],
            'invoice_no' => $res['invoice']['invoice_no'],
            'url'        => B2bInvoice::path($res['invoice']),
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
            $b2b_fail('Restricționat conform planului B2B.');
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
