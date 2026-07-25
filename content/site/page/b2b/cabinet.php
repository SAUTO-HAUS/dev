<?php defined( '_DOIT' ) or die( 'Restricted access' );

/**
 * /{lang}/b2b/... - B2B cabinet (spec 2.3).
 *
 * Sub-routes on $t_mp[3]: cabinet (default), invoices, requests, activity, logout.
 * The `invoice` route is intercepted earlier in index.php, because the proforma
 * is served as a standalone document without the site layout.
 */

include_once( __DIR__ . '/_layout.php' );
// $car_card comes from content/site/include/functions.php, already loaded by
// body.php for non-ordercars pages. That variant has the 'fav' branch, which renders
// an explicit id list across both catalogs - what the saved-cars grid needs.
// order_functions.php must NOT be included here: its $car_card is on_order-only and
// has no 'fav' branch.

use App\Services\B2b\B2bAudit;
use App\Services\B2b\B2bConfig;
use App\Services\B2b\B2bInvoice;

$lang   = $_COOKIE['lang'] ?? 'ro';
$t      = b2b_lang($lang);
$action = $t_mp[3] ?? 'cabinet';

// Logout needs no page: act and redirect.
if ($action === 'logout') {
    App\Services\B2b\B2bAuth::logout();
    b2b_redirect('/'.$lang.'/b2b-login');
}

if (!b2b_is_client()) {
    b2b_redirect('/'.$lang.'/b2b-login');
}

$user   = b2b_user();
$userId = (int)$user['id'];
$csrf   = b2b_esc(b2b_csrf_token());

$tabs = ['cabinet' => 'tab_cars', 'invoices' => 'tab_invoices', 'requests' => 'tab_requests'];
if (!isset($tabs[$action])) {
    $action = 'cabinet';
}

echo b2b_assets();

// ------------------------------------------------------------------- header

echo '
<div class="b2b-page b2b-page--wide">
    <header class="b2b-head">
        <div class="b2b-head__main">
            <p class="b2b-head__eyebrow">'.b2b_esc($t['welcome']).'</p>
            <h1 class="b2b-head__ttl">'.b2b_esc(\App\Services\B2b\B2bAuth::displayName($user)).'</h1>
        </div>
        <div class="b2b-head__side">
            <div class="b2b-head__row">
                <span class="b2b-head__label">'.b2b_esc($t['status']).'</span>
                <span class="b2b-badge b2b-badge--'.b2b_esc($user['status']).'">'.b2b_status_label((string)$user['status']).'</span>
            </div>
            <a class="b2b-btn b2b-btn--ghost b2b-btn--sm" href="/'.b2b_esc($lang).'/b2b/logout">'.b2b_esc($t['logout']).'</a>
        </div>
    </header>

    <nav class="b2b-tabs">';
        foreach ($tabs as $slug => $labelKey) {
            $href = '/'.b2b_esc($lang).'/b2b/'.($slug === 'cabinet' ? 'cabinet' : $slug);
            echo '<a class="b2b-tab'.($slug === $action ? ' is-active' : '').'" href="'.$href.'">'.b2b_esc($t[$labelKey]).'</a>';
        }
        echo '
    </nav>

    <div class="b2b-panel" data-csrf="'.$csrf.'">';

// ------------------------------------------------------- Tab 1: saved cars

if ($action === 'cabinet') {
    $savedIds = [];
    try {
        $stmt = $db->prepare(
            'SELECT car_id FROM '.B2bConfig::table('saved_cars').' WHERE b2b_user_id = :uid ORDER BY id DESC LIMIT 100'
        );
        $stmt->execute([':uid' => $userId]);
        $savedIds = array_map('intval', $stmt->fetchAll(PDO::FETCH_COLUMN) ?: []);
    } catch (Throwable $e) {
        $savedIds = [];
    }

    // Spec 2.3: cars the partner looked at OR saved. Saved ones come first
    // (explicit intent), then recently viewed, deduplicated.
    $viewedIds = B2bAudit::recentlyViewedCarIds($userId, 50);
    $carIds    = array_values(array_unique(array_merge($savedIds, $viewedIds)));
    $savedLookup = array_flip($savedIds);

    if (!$carIds) {
        echo '<div class="b2b-empty">
                <p>'.b2b_esc($t['no_cars']).'</p>
                <a class="b2b-btn b2b-btn--primary" href="/'.b2b_esc($lang).'/ordercars">'.b2b_esc($t['browse_catalog']).'</a>
              </div>';
    } else {
        // Same cards as the catalog, so B2B prices apply automatically
        // (b2b_prices_for_cars runs inside $car_card).
        $card = $car_card('fav', count($carIds), $carIds, 'av', 0, true);

        echo '<div class="b2b-legend">'
           . '<span class="b2b-chip">'.b2b_esc($t['saved_cars']).': '.count($savedLookup).'</span>'
           . '<span class="b2b-chip b2b-chip--muted">'.b2b_esc($t['viewed_cars']).': '.max(0, count($carIds) - count($savedLookup)).'</span>'
           . '</div>';
        echo '<div class="cars b2b-cars">'.$card['txt'].'</div>';
    }
}

// -------------------------------------------------------- Tab 2: invoices

elseif ($action === 'invoices') {
    $invoices = B2bInvoice::forUser($userId);

    if (!$invoices) {
        echo '<div class="b2b-empty"><p>'.b2b_esc($t['no_invoices']).'</p></div>';
    } else {
        echo '<div class="b2b-table-wrap"><table class="b2b-table">
                <thead><tr>
                    <th>'.b2b_esc($t['invoice_no']).'</th>
                    <th>'.b2b_esc($t['invoice_car']).'</th>
                    <th>'.b2b_esc($t['invoice_amount']).'</th>
                    <th>'.b2b_esc($t['status']).'</th>
                    <th>'.b2b_esc($t['invoice_date']).'</th>
                    <th></th>
                </tr></thead><tbody>';

        foreach ($invoices as $inv) {
            $snap = B2bInvoice::carSnapshot($inv);
            echo '<tr>
                    <td class="b2b-td--strong">'.b2b_esc($inv['invoice_no']).'</td>
                    <td><a href="/'.b2b_esc($lang).'/ordercars/'.(int)$inv['car_id'].'">'.b2b_esc($snap['title'] ?? ('#'.(int)$inv['car_id'])).'</a></td>
                    <td>'.b2b_esc(number_format((float)$inv['advance_amount'], 2, '.', ' ')).' '.b2b_esc($inv['currency']).'</td>
                    <td><span class="b2b-badge b2b-badge--'.b2b_esc($inv['status']).'">'.b2b_status_label((string)$inv['status']).'</span></td>
                    <td>'.b2b_esc(date('d.m.Y H:i', strtotime((string)$inv['created_at']))).'</td>
                    <td><a class="b2b-btn b2b-btn--ghost b2b-btn--sm" href="'.b2b_esc(B2bInvoice::path($inv)).'" target="_blank" rel="noopener">'.b2b_esc($t['invoice_open']).'</a></td>
                  </tr>';
        }

        echo '</tbody></table></div>';
    }
}

// -------------------------------------------------------- Tab 3: requests

elseif ($action === 'requests') {
    $requests = [];
    try {
        $stmt = $db->prepare(
            'SELECT r.*, i.invoice_no
               FROM '.B2bConfig::table('requests').' AS r
               LEFT JOIN '.B2bConfig::table('invoices').' AS i ON i.id = r.invoice_id
              WHERE r.b2b_user_id = :uid
              ORDER BY r.id DESC LIMIT 100'
        );
        $stmt->execute([':uid' => $userId]);
        $requests = $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
    } catch (Throwable $e) {
        $requests = [];
    }

    if (!$requests) {
        echo '<div class="b2b-empty"><p>'.b2b_esc($t['no_requests']).'</p></div>';
    } else {
        echo '<div class="b2b-table-wrap"><table class="b2b-table">
                <thead><tr>
                    <th>'.b2b_esc($t['invoice_car']).'</th>
                    <th>'.b2b_esc($t['invoice_no']).'</th>
                    <th>'.b2b_esc($t['req_comment']).'</th>
                    <th>'.b2b_esc($t['req_status']).'</th>
                    <th>'.b2b_esc($t['invoice_date']).'</th>
                </tr></thead><tbody>';

        foreach ($requests as $req) {
            $car = B2bInvoice::loadCar((int)$req['car_id']);
            echo '<tr>
                    <td><a href="/'.b2b_esc($lang).'/ordercars/'.(int)$req['car_id'].'">'.b2b_esc($car['title'] ?? ('#'.(int)$req['car_id'])).'</a></td>
                    <td>'.b2b_esc($req['invoice_no'] ?? '—').'</td>
                    <td class="b2b-td--wrap">'.b2b_esc($req['comment'] ?? '—').'</td>
                    <td><span class="b2b-badge b2b-badge--'.b2b_esc($req['status']).'">'.b2b_status_label((string)$req['status']).'</span></td>
                    <td>'.b2b_esc(date('d.m.Y H:i', strtotime((string)$req['created_at']))).'</td>
                  </tr>';
        }

        echo '</tbody></table></div>';
    }
}

// ------------------------------------------------------- Tab 4: activity

elseif ($action === 'activity') {
    $logs = B2bAudit::forUser($userId, 100);

    if (!$logs) {
        echo '<div class="b2b-empty"><p>'.b2b_esc($t['no_activity']).'</p></div>';
    } else {
        echo '<ul class="b2b-timeline">';
        foreach ($logs as $log) {
            $details = $log['details'] ? json_decode((string)$log['details'], true) : null;
            $carId   = is_array($details) ? (int)($details['car_id'] ?? 0) : 0;

            echo '<li class="b2b-timeline__item">
                    <span class="b2b-timeline__dot"></span>
                    <div class="b2b-timeline__body">
                        <span class="b2b-timeline__action">'.b2b_action_label((string)$log['action_type']).'</span>'
                        .($carId > 0 ? ' <a class="b2b-timeline__link" href="/'.b2b_esc($lang).'/ordercars/'.$carId.'">#'.$carId.'</a>' : '').'
                        <time class="b2b-timeline__time">'.b2b_esc(date('d.m.Y H:i', strtotime((string)$log['created_at']))).'</time>
                    </div>
                  </li>';
        }
        echo '</ul>';
    }
}

echo '
    </div>
</div>';
