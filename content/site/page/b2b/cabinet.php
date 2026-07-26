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

// Summary counts for the dashboard nav cards (cheap COUNTs, one per section).
$countSaved = $countInvoices = 0;
try {
    $q = $db->prepare('SELECT COUNT(*) FROM '.B2bConfig::table('saved_cars').' WHERE b2b_user_id = :uid');
    $q->execute([':uid' => $userId]); $countSaved = (int)$q->fetchColumn();
    $q = $db->prepare('SELECT COUNT(*) FROM '.B2bConfig::table('invoices').' WHERE b2b_user_id = :uid');
    $q->execute([':uid' => $userId]); $countInvoices = (int)$q->fetchColumn();
} catch (Throwable $e) {}

// Avatar initials from the display name (e.g. "Grigore Botnarenco" -> "GB").
$displayName = \App\Services\B2b\B2bAuth::displayName($user);
$initials = '';
foreach (preg_split('/\s+/', trim($displayName)) ?: [] as $w) {
    if ($w !== '') { $initials .= mb_strtoupper(mb_substr($w, 0, 1)); if (mb_strlen($initials) >= 2) break; }
}
if ($initials === '') { $initials = 'B'; }

// Feather-style icons for the nav cards.
$icoHeart = '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M20.8 4.6a5.5 5.5 0 0 0-7.8 0L12 5.6l-1-1a5.5 5.5 0 1 0-7.8 7.8l1 1L12 21l7.8-7.6 1-1a5.5 5.5 0 0 0 0-7.8z"/></svg>';
$icoDoc   = '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/><line x1="16" y1="13" x2="8" y2="13"/><line x1="16" y1="17" x2="8" y2="17"/></svg>';

// Nav cards double as the section counters: saved cars + payment invoices.
$navItems = [
    'cabinet'  => [$t['tab_cars'],     $countSaved,    $icoHeart],
    'invoices' => [$t['tab_invoices'], $countInvoices, $icoDoc],
];

echo '
<div class="b2b-page b2b-page--wide">
    <div class="b2b-hero">
        <div class="b2b-hero__id">
            <span class="b2b-hero__avatar">'.b2b_esc($initials).'</span>
            <div class="b2b-hero__text">
                <p class="b2b-hero__greet">'.b2b_esc($t['welcome']).'</p>
                <h1 class="b2b-hero__name">'.b2b_esc($displayName).'</h1>
                <span class="b2b-badge b2b-badge--'.b2b_esc($user['status']).'">'.b2b_status_label((string)$user['status']).'</span>
            </div>
        </div>

        <div class="b2b-nav">';
        foreach ($navItems as $slug => [$label, $count, $ico]) {
            $href = '/'.b2b_esc($lang).'/b2b/'.$slug;
            // The favourites heart updates this counter live (see head.php).
            $numAttr = $slug === 'cabinet' ? ' data-b2b-count="saved"' : '';
            echo '<a class="b2b-navcard'.($slug === $action ? ' is-active' : '').'" href="'.$href.'">'
               . '<span class="b2b-navcard__ico">'.$ico.'</span>'
               . '<span class="b2b-navcard__meta">'
               . '<span class="b2b-navcard__num"'.$numAttr.'>'.(int)$count.'</span>'
               . '<span class="b2b-navcard__label">'.b2b_esc($label).'</span>'
               . '</span></a>';
        }
        echo '
        </div>
    </div>

    <div class="b2b-panel" data-csrf="'.$csrf.'">';

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
                    <td>'.b2b_esc(number_format((float)$inv['advance_amount'], 2, '.', ' ')).' '.b2b_esc($inv['currency']).'</td>
                    <td><span class="b2b-badge b2b-badge--'.b2b_esc($dispStatus).'">'.b2b_status_label($dispStatus).'</span></td>
                    <td>'.b2b_esc(date('d.m.Y H:i', strtotime((string)$inv['created_at']))).'</td>
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
    </div>
</div>';
