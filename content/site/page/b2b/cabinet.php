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

// Payment-invoice form is its own page (not a cabinet tab).
if ($action === 'cont-plata') {
    include __DIR__ . '/cont_plata.php';
    return;
}

$tabs = ['cabinet' => 'tab_cars', 'invoices' => 'tab_invoices'];
if (!isset($tabs[$action])) {
    $action = 'cabinet';
}

echo b2b_assets();

// ------------------------------------------------------------------- header
// Shared hero + nav cards (identity, saved cars, invoices, compare). The same
// helper renders the /compare page so a logged-in partner keeps this header there.
echo b2b_cabinet_hero($db, $user, $lang, $action);

echo '<div class="b2b-panel" data-csrf="'.$csrf.'">';

// ------------------------------------------------------- Tab 1: saved cars

if ($action === 'cabinet') {
    // Only the partner's favourites (the hearted cars, mirrored into saved_cars),
    // shown as plain cards like the guest /favorites page. No "viewed", no legend.
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

    $hasCars = (bool)$savedIds;

    // Empty state is ALWAYS in the DOM (hidden when there are cars): when the last
    // card is removed client-side, the JS just reveals it — no refresh needed.
    echo '<div class="b2b-empty" id="b2b_cabinet_empty"'.($hasCars ? ' style="display:none;"' : '').'>
            <p>'.b2b_esc($t['no_cars']).'</p>
            <a class="b2b-btn b2b-btn--primary" href="/'.b2b_esc($lang).'/ordercars">'.b2b_esc($t['browse_catalog']).'</a>
          </div>';

    if ($hasCars) {
        // Identical to the guest /favorites page. The whole card grid CSS is scoped
        // to "main .gr > .cnt > .it", so the .gr > .cnt.list wrapper is required or
        // the cards render unstyled. The extra .b2b-cars class only marks the grid
        // for the unfavorite card removal.
        $card = $car_card('fav', count($savedIds), $savedIds, 'av', 0, true);
        echo '<div class="gr" id="b2b_cabinet_grid"><div class="cnt list b2b-cars">'.$card['txt'].'</div></div>';
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
            // Show the Super Admin's decision (linked request), falling back to the
            // invoice's own status when there is no request yet.
            $dispStatus = ($inv['req_status'] ?? '') !== '' ? (string)$inv['req_status'] : (string)$inv['status'];
            echo '<tr>
                    <td class="b2b-td--strong">'.b2b_esc($inv['invoice_no']).'</td>
                    <td><a href="/'.b2b_esc($lang).'/ordercars/'.(int)$inv['car_id'].'">'.b2b_esc($snap['title'] ?? ('#'.(int)$inv['car_id'])).'</a></td>
                    <td>'.b2b_esc(number_format((float)$inv['advance_amount'], 0, '.', '')).' '.b2b_esc($inv['currency']).'</td>
                    <td><span class="b2b-badge b2b-badge--'.b2b_esc($dispStatus).'">'.b2b_status_label($dispStatus).'</span></td>
                    <td>'.b2b_esc(date('d.m.Y', strtotime((string)$inv['created_at']))).'</td>
                    <td><a class="b2b-btn b2b-btn--ghost b2b-btn--sm" href="'.b2b_esc(B2bInvoice::path($inv)).'" target="_blank" rel="noopener">'.b2b_esc($t['invoice_open']).'</a></td>
                  </tr>';
        }

        echo '</tbody></table></div>';
    }
}

// ------------------------------------------------------- (legacy) activity

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
    </div>';

// ------------------------------------------------- Security: change password
// Self-service; the partner types the current + new password. Reset-by-email
// (b2b-forgot) covers the case where they cannot log in at all.
echo '<div class="b2b-panel b2b-pw-panel">
    <div class="b2b-pw">
        <h2 class="b2b-pw__ttl">'.b2b_esc($t['pw_change_title']).'</h2>
        <form class="b2b-form" id="b2b-change-password-form" data-csrf="'.$csrf.'" novalidate>
            <div class="b2b-field">
                <label for="b2b-pw-cur">'.b2b_esc($t['pw_current']).'</label>
                <div class="b2b-pass-wrap">
                    <input type="password" id="b2b-pw-cur" name="current" required autocomplete="current-password" />
                    '.b2b_pass_toggle($t).'
                </div>
            </div>
            <div class="b2b-field">
                <label for="b2b-pw-new">'.b2b_esc($t['pw_new']).'</label>
                <div class="b2b-pass-wrap">
                    <input type="password" id="b2b-pw-new" name="new" minlength="8" required autocomplete="new-password" />
                    '.b2b_pass_toggle($t).'
                </div>
                <small class="b2b-hint">'.b2b_esc($t['password_hint']).'</small>
            </div>
            <div class="b2b-field">
                <label for="b2b-pw-new2">'.b2b_esc($t['pw_new_repeat']).'</label>
                <div class="b2b-pass-wrap">
                    <input type="password" id="b2b-pw-new2" name="confirm" minlength="8" required autocomplete="new-password" />
                    '.b2b_pass_toggle($t).'
                </div>
            </div>
            <div class="b2b-form__msg" role="alert" aria-live="polite"></div>
            <button type="submit" class="b2b-btn b2b-btn--primary">'.b2b_esc($t['pw_save']).'</button>
        </form>
    </div>
</div>';

echo '
</div>';
