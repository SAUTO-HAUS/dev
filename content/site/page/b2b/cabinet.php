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
use App\Services\B2b\B2bGift;
use App\Services\B2b\B2bInvoice;
use App\Services\B2b\B2bSavedFilter;

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

$tabs = ['cabinet' => 'tab_cars', 'viewed' => 'tab_viewed', 'filters' => 'tab_filters',
         'invoices' => 'tab_invoices', 'gifts' => 'tab_gifts'];
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
                    <td class="b2b-td--strong" data-label="'.b2b_esc($t['invoice_no']).'">'.b2b_esc($inv['invoice_no']).'</td>
                    <td data-label="'.b2b_esc($t['invoice_car']).'"><a href="/'.b2b_esc($lang).'/ordercars/'.(int)$inv['car_id'].'">'.b2b_esc($snap['title'] ?? ('#'.(int)$inv['car_id'])).'</a></td>
                    <td data-label="'.b2b_esc($t['invoice_amount']).'">'.b2b_esc(number_format((float)$inv['advance_amount'], 0, '.', '')).' '.b2b_esc($inv['currency']).'</td>
                    <td data-label="'.b2b_esc($t['status']).'"><span class="b2b-badge b2b-badge--'.b2b_esc($dispStatus).'">'.b2b_status_label($dispStatus).'</span></td>
                    <td data-label="'.b2b_esc($t['invoice_date']).'">'.b2b_esc(date('d.m.Y', strtotime((string)$inv['created_at']))).'</td>
                    <td class="b2b-td--action"><a class="b2b-btn b2b-btn--ghost b2b-btn--sm" href="'.b2b_esc(B2bInvoice::path($inv)).'" target="_blank" rel="noopener">'.b2b_esc($t['invoice_open']).'</a></td>
                  </tr>';
        }

        echo '</tbody></table></div>';
    }
}

// -------------------------------------------------- Tab: recently viewed

elseif ($action === 'viewed') {
    // recentlyViewedCarIds() groups by car and orders by the LAST view, so a car
    // opened four times appears once, in the position of its most recent visit —
    // which is what a partner expects from "recently viewed" (the admin history
    // is the one that counts repeats).
    $viewedIds = B2bAudit::recentlyViewedCarIds($userId, 32);

    if (!$viewedIds) {
        echo '<div class="b2b-empty">
                <p>'.b2b_esc($t['no_viewed']).'</p>
                <a class="b2b-btn b2b-btn--primary" href="/'.b2b_esc($lang).'/ordercars">'.b2b_esc($t['browse_catalog']).'</a>
              </div>';
    } else {
        // Same grid as the saved-cars tab. The 'fav' branch takes an explicit id
        // list and spans both catalogs, which is what an arbitrary set needs; the
        // card CSS is scoped to "main .gr > .cnt > .it", hence the wrappers.
        // No .b2b-cars class here: unhearting a car must not drop it from a list
        // that is about what was visited, not about what is saved.
        $card = $car_card('fav', count($viewedIds), $viewedIds, 'av', 0, true);
        echo '<div class="gr"><div class="cnt list">'.$card['txt'].'</div></div>';
    }
}

// ------------------------------------------------- Tab: saved filters

elseif ($action === 'filters') {
    $filters = B2bSavedFilter::forUser($userId);

    // Brand -> model options, straight from the catalog like the public filter
    // bar does: a brand nobody sells is not worth watching.
    $brands = $models = [];
    try {
        $q = $db->query('SELECT DISTINCT br, br_nm, mo, mo_nm FROM '.$prefx.'_car_ctlg
                          WHERE vis = "1" AND act = "1" AND br <> "" ORDER BY br_nm, mo_nm');
        foreach ($q->fetchAll(PDO::FETCH_ASSOC) as $r) {
            $brands[$r['br']] = $r['br_nm'];
            if ($r['mo'] !== '') {
                $models[$r['br']][$r['mo']] = $r['mo_nm'];
            }
        }
    } catch (Throwable $e) {
        $brands = $models = [];
    }

    echo '<div class="b2b-filters">';

    // ---- create / edit form
    // The same form does both: editing loads a saved filter into it (b2b.js) and
    // swaps the wording through the data-* labels below, so a partner corrects a
    // filter where they built it instead of deleting and retyping it.
    echo '<form class="b2b-card b2b-fltform" id="b2b_filter_form"
                data-ttl-new="'.b2b_esc($t['flt_new']).'" data-ttl-edit="'.b2b_esc($t['flt_edit_ttl']).'"
                data-btn-new="'.b2b_esc($t['flt_save']).'" data-btn-edit="'.b2b_esc($t['flt_update']).'">
            <input type="hidden" name="filter_id" id="b2b_filter_id" value="">
            <h2 class="b2b-fltform__ttl" id="b2b_filter_ttl">'.b2b_esc($t['flt_new']).'</h2>
            <p class="b2b-hint">'.b2b_esc(\App\Services\B2b\B2bFilterMailer::wants($user) ? $t['flt_hint'] : $t['flt_hint_nomail']).'</p>
            <div class="b2b-fltform__grid">
                <label class="b2b-field b2b-fld--br">
                    <span>'.b2b_esc($t['flt_brand']).'</span>
                    <select name="br" id="b2b_flt_br">
                        <option value="">'.b2b_esc($t['flt_any']).'</option>';
    foreach ($brands as $code => $label) {
        echo '<option value="'.b2b_esc($code).'">'.b2b_esc($label).'</option>';
    }
    echo '      </select>
                </label>
                <label class="b2b-field b2b-fld--mo">
                    <span>'.b2b_esc($t['flt_model']).'</span>
                    <select name="mo" id="b2b_flt_mo" disabled>
                        <option value="">'.b2b_esc($t['flt_any']).'</option>
                    </select>
                </label>
                <label class="b2b-field b2b-fld--yrf">
                    <span>'.b2b_esc($t['flt_year_from']).'</span>
                    <input type="number" name="yr_from" min="1950" max="'.(int)date('Y').'" placeholder="'.b2b_esc($t['flt_any']).'">
                </label>
                <label class="b2b-field b2b-fld--yrt">
                    <span>'.b2b_esc($t['flt_year_to']).'</span>
                    <input type="number" name="yr_to" min="1950" max="'.(int)date('Y').'" placeholder="'.b2b_esc($t['flt_any']).'">
                </label>
                <label class="b2b-field b2b-fld--tra">
                    <span>'.b2b_esc($t['flt_gearbox']).'</span>
                    <select name="tra">
                        <option value="">'.b2b_esc($t['flt_all']).'</option>';
    // Gearbox and fuel use the catalog's own translated labels, so the filter
    // speaks the same language as the car pages.
    foreach (\App\Services\B2b\B2bSavedFilter::GEARBOXES as $code) {
        if (isset($lng['l']['car']['tra'][$code])) {
            echo '<option value="'.b2b_esc($code).'">'.b2b_esc($lng['l']['car']['tra'][$code]).'</option>';
        }
    }
    echo '      </select>
                </label>
                <label class="b2b-field b2b-fld--fl">
                    <span>'.b2b_esc($t['flt_fuel']).'</span>
                    <select name="fl">
                        <option value="">'.b2b_esc($t['flt_all']).'</option>';
    foreach (\App\Services\B2b\B2bSavedFilter::FUELS as $code) {
        if (isset($lng['l']['car']['fl'][$code])) {
            echo '<option value="'.b2b_esc($code).'">'.b2b_esc($lng['l']['car']['fl'][$code]).'</option>';
        }
    }
    echo '      </select>
                </label>
                <label class="b2b-field b2b-fld--volf">
                    <span>'.b2b_esc($t['flt_vol_from']).'</span>
                    <input type="number" name="vol_from" min="0" step="100" placeholder="'.b2b_esc($t['flt_any']).'">
                </label>
                <label class="b2b-field b2b-fld--volt">
                    <span>'.b2b_esc($t['flt_vol_to']).'</span>
                    <input type="number" name="vol_to" min="0" step="100" placeholder="'.b2b_esc($t['flt_any']).'">
                </label>
                <label class="b2b-field b2b-fld--mlg">
                    <span>'.b2b_esc($t['flt_mlg_to']).'</span>
                    <input type="number" name="mlg_to" min="0" step="1000" placeholder="'.b2b_esc($t['flt_any']).'">
                </label>
                <label class="b2b-field b2b-fld--prcf">
                    <span>'.b2b_esc($t['flt_price_from']).'</span>
                    <input type="number" name="prc_from" min="0" step="100" placeholder="'.b2b_esc($t['flt_any']).'">
                </label>
                <label class="b2b-field b2b-fld--prct">
                    <span>'.b2b_esc($t['flt_price_to']).'</span>
                    <input type="number" name="prc_to" min="0" step="100" placeholder="'.b2b_esc($t['flt_any']).'">
                </label>
                <label class="b2b-field b2b-fld--rg">
                    <span>'.b2b_esc($t['flt_region']).'</span>
                    <select name="region">
                        <option value="">'.b2b_esc($t['flt_all']).'</option>';
    // Only the regions this partner may see: offering one his plan hides would
    // create a filter that can never match.
    foreach (b2b_allowed_regions() as $rg) {
        echo '<option value="'.b2b_esc($rg).'">'.b2b_esc($t['rg_'.$rg] ?? $rg).'</option>';
    }
    echo '      </select>
                </label>
            </div>
            <div class="b2b-fltform__foot">
                <span class="b2b-fltform__msg" id="b2b_filter_msg"></span>
                <div class="b2b-fltform__acts">
                    <button type="button" class="b2b-btn b2b-btn--ghost" id="b2b_filter_cancel" hidden>'.b2b_esc($t['flt_cancel']).'</button>
                    <button type="submit" class="b2b-btn b2b-btn--primary" id="b2b_filter_submit">'.b2b_esc($t['flt_save']).'</button>
                </div>
            </div>
          </form>';

    // The model list is per brand; ship it as data so picking a brand does not
    // cost a round trip.
    echo '<script>window.B2B_FLT_MODELS='.json_encode($models, JSON_UNESCAPED_UNICODE | JSON_HEX_TAG).';</script>';

    // ---- existing filters
    if (!$filters) {
        echo '<div class="b2b-empty"><p>'.b2b_esc($t['no_filters']).'</p></div>';
    } else {
        $lbl = ['from' => $t['flt_from'], 'to' => $t['flt_to'], 'any' => $t['flt_any']];
        foreach (B2bConfig::REGIONS as $rg) {
            $lbl['rg_'.$rg] = $t['rg_'.$rg] ?? $rg;
        }
        $lbl['fl']  = $lng['l']['car']['fl']  ?? [];
        $lbl['tra'] = $lng['l']['car']['tra'] ?? [];
        $lbl['cm3'] = $lng['l']['unit']['cm3'] ?? 'cm3';
        $lbl['km']  = $lng['l']['unit']['km']  ?? 'km';

        foreach ($filters as $f) {
            $newIds = B2bSavedFilter::matchIds($f, true, 60);   // since last look
            $allIds = B2bSavedFilter::matchIds($f, false, 60);  // since created
            $isNew  = (bool)$newIds;

            // <details>, so a partner with a dozen filters scans a list of rows
            // instead of scrolling past every car in each one. Only the filters
            // that actually caught something open by themselves — those are the
            // ones worth looking at right now.
            echo '<details class="b2b-flt'.($isNew ? ' is-new' : '').'"'.($isNew ? ' open' : '').'>
                    <summary class="b2b-flt__head">
                        <div class="b2b-flt__id">
                            <p class="b2b-flt__what">'.b2b_esc(B2bSavedFilter::describe($f, $lbl)).'</p>
                        </div>
                        <div class="b2b-flt__right">'
                        .($isNew
                            ? '<span class="b2b-flt__badge">'.count($newIds).' '.b2b_esc($t['flt_new_cars']).'</span>'
                            // Not "no new cars" while a grid of them sits below:
                            // say how many the filter has caught in total.
                            : ($allIds
                                ? '<span class="b2b-flt__count">'.count($allIds).' '.b2b_esc($t['flt_found']).'</span>'
                                : '<span class="b2b-flt__none">'.b2b_esc($t['flt_no_new']).'</span>'))
                        .'<span class="b2b-flt__chev" aria-hidden="true"></span>
                            <button type="button" class="b2b-flt__edit" data-b2b-filter-edit="'.(int)$f['id'].'"
                                    data-flt="'.b2b_esc(json_encode(B2bSavedFilter::criteriaOf($f), JSON_UNESCAPED_UNICODE)).'"
                                    title="'.b2b_esc($t['flt_edit']).'" aria-label="'.b2b_esc($t['flt_edit']).'">
                                <svg viewBox="0 0 24 24" width="14" height="14" fill="none" aria-hidden="true">
                                    <path d="M16.5 3.5a2.121 2.121 0 0 1 3 3L7 19l-4 1 1-4 12.5-12.5z"
                                          stroke="currentColor" stroke-width="2"
                                          stroke-linecap="round" stroke-linejoin="round"/>
                                </svg>
                            </button>
                            <button type="button" class="b2b-flt__del" data-b2b-filter-del="'.(int)$f['id'].'"
                                    title="'.b2b_esc($t['flt_delete']).'" aria-label="'.b2b_esc($t['flt_delete']).'">&times;</button>
                        </div>
                    </summary>';

            if ($allIds) {
                $card = $car_card('fav', count($allIds), $allIds, 'av', 0, true);
                echo '<div class="gr"><div class="cnt list">'.$card['txt'].'</div></div>';
            }

            echo '</details>';
        }

        // Marked AFTER rendering, so this page still highlights what was new.
        B2bSavedFilter::markAllSeen($userId);
    }

    echo '</div>';
}

// ---------------------------------------------------------- Tab 3: gifts

elseif ($action === 'gifts') {
    $gifts = B2bGift::forUser($userId);

    if (!$gifts) {
        echo '<div class="b2b-empty"><p>'.b2b_esc($t['no_gifts']).'</p></div>';
    } else {
        echo '<ul class="b2b-gifts">';
        foreach ($gifts as $g) {
            // Unopened ones lead, styled as a notification; after this render they
            // are marked seen and become plain history entries.
            $isNew = empty($g['seen_at']);
            $what  = B2bGift::describe($g, $lang);
            $note  = trim((string)($g['note'] ?? ''));

            echo '<li class="b2b-gift'.($isNew ? ' is-new' : '').'">
                    <span class="b2b-gift__ico" aria-hidden="true">&#127873;</span>
                    <div class="b2b-gift__body">'
                    .($isNew ? '<span class="b2b-gift__new">'.b2b_esc($t['gift_new']).'</span>' : '').'
                        <p class="b2b-gift__what">'.b2b_esc($what !== '' ? $what : $t['gift_generic']).'</p>
                        <p class="b2b-gift__free">'.b2b_esc($t['gift_free']).'</p>'
                        .($note !== '' ? '<p class="b2b-gift__note">'.b2b_esc($note).'</p>' : '').'
                        <time class="b2b-gift__time">'.b2b_esc(date('d.m.Y', strtotime((string)$g['created_at']))).'</time>
                    </div>
                  </li>';
        }
        echo '</ul>';

        // Marked AFTER rendering, so the page the partner is looking at still
        // highlights what was new. The dot is gone from the next page load on.
        B2bGift::markAllSeen($userId);
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

// The change-password modal is rendered by b2b_cabinet_hero() above, next to the
// button that opens it, so every page carrying the hero has it. Reset-by-email
// (b2b-forgot) covers the case where the partner cannot log in at all.

echo '
</div>';
