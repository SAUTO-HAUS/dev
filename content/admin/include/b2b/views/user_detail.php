<?php defined('_DOIT') or die('Restricted access');

/**
 * B2B client profile (spec 3.1 / 3.2 / 3.3).
 * Tabs: details, region permissions, activity log, invoices, requests.
 * Every action goes through content/admin/ajax/b2b/ajax.php.
 */

use App\Services\B2b\B2bAudit;
use App\Services\B2b\B2bAuth;
use App\Services\B2b\B2bConfig;
use App\Services\B2b\B2bInvoice;
use App\Services\B2b\B2bRegions;

$lang = $_COOKIE['lang'] ?? 'ro';
$t    = b2b_adm_lang($lang);

$uid = (int)($_GET['id'] ?? 0);

$client = null;
if ($uid > 0) {
    try {
        $stmt = $db->prepare('SELECT * FROM '.B2bConfig::table('users').' WHERE id = :id LIMIT 1');
        $stmt->execute([':id' => $uid]);
        $client = $stmt->fetch(PDO::FETCH_ASSOC) ?: null;
    } catch (Throwable $e) {
        $client = null;
    }
}

if (!$client) {
    echo '<div class="b2ba"><div class="b2ba-empty">404</div></div>';
    return;
}

$listUrl  = '/'.$lang.'/'.$admin_dir.'/b2b/users';
$regions  = B2bRegions::allowed($uid);
$logs     = B2bAudit::forUser($uid, 200);
$invoices = B2bInvoice::forUser($uid, 200);

$requests = [];
try {
    $stmt = $db->prepare(
        'SELECT r.*, i.invoice_no
           FROM '.B2bConfig::table('requests').' AS r
           LEFT JOIN '.B2bConfig::table('invoices').' AS i ON i.id = r.invoice_id
          WHERE r.b2b_user_id = :uid ORDER BY r.id DESC LIMIT 200'
    );
    $stmt->execute([':uid' => $uid]);
    $requests = $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
} catch (Throwable $e) {
    $requests = [];
}

// Per-client pricing status: which of the 4 B2B tables this client has its OWN
// version of (vs. inheriting the global one). Editing is done on the dedicated
// editor /adminsauto/b2b/pricing?user=ID; here we only show a summary.
$priceTables = [
    'commission' => [$prefx.'_b2b_commission_tiers', $t['pricing_commission']],
    'delivery'   => [$prefx.'_b2b_eu_tiers',         $t['pricing_delivery']],
    'eu_params'  => [$prefx.'_b2b_eu_params',        $t['pricing_eu_params']],
    'kr_params'  => [$prefx.'_b2b_kr_params',        $t['pricing_kr_params']],
];
$priceCustom = [];
$priceHasAny = false;
foreach ($priceTables as $k => $info) {
    $priceCustom[$k] = false;
    try {
        $q = $db->prepare('SELECT 1 FROM '.$info[0].' WHERE b2b_user_id = :uid LIMIT 1');
        $q->execute([':uid' => $uid]);
        if ($q->fetchColumn()) {
            $priceCustom[$k] = true;
            $priceHasAny = true;
        }
    } catch (Throwable $e) {
        // Pricing tables not migrated yet: treat as global.
    }
}
$pricingEditUrl = '/'.$lang.'/'.$admin_dir.'/b2b/pricing?user='.$uid;
?>

<div class="b2ba" data-b2b-user="<?= $uid ?>" data-saved-msg="<?= b2b_adm_esc($t['saved']) ?>">
    <a class="b2ba-back" href="<?= b2b_adm_esc($listUrl) ?>"><?= b2b_adm_esc($t['back_to_list']) ?></a>

    <div class="b2ba-head">
        <div>
            <h1 class="b2ba-h1"><?= b2b_adm_esc(B2bAuth::displayName($client)) ?></h1>
            <p class="b2ba-sub">
                <?= b2b_adm_esc($client['login']) ?> &middot;
                <?= b2b_adm_esc($t['pt_'.$client['person_type']] ?? $client['person_type']) ?> &middot;
                <?= b2b_adm_esc($client['email']) ?> &middot;
                <?= b2b_adm_esc($client['phone_number']) ?>
            </p>
        </div>
        <div class="b2ba-head__actions">
            <span class="b2ba-badge b2ba-badge--<?= b2b_adm_esc($client['status']) ?>" id="b2ba-status-badge">
                <?= b2b_adm_esc($t['st_'.$client['status']] ?? $client['status']) ?>
            </span>

            <?php if ($client['status'] !== 'active'): ?>
                <button type="button" class="b2ba-btn b2ba-btn--ok" data-b2b-admin="status" data-value="active">
                    <?= b2b_adm_esc($t['approve']) ?>
                </button>
            <?php endif; ?>

            <?php if ($client['status'] === 'blocked'): ?>
                <button type="button" class="b2ba-btn b2ba-btn--ok" data-b2b-admin="status" data-value="active">
                    <?= b2b_adm_esc($t['unblock']) ?>
                </button>
            <?php else: ?>
                <button type="button" class="b2ba-btn b2ba-btn--red" data-b2b-admin="status" data-value="blocked"
                        data-confirm="<?= b2b_adm_esc($t['confirm_block']) ?>">
                    <?= b2b_adm_esc($t['block']) ?>
                </button>
            <?php endif; ?>

            <button type="button" class="b2ba-btn b2ba-btn--soft" data-b2b-admin="delete"
                    data-confirm="<?= b2b_adm_esc($t['confirm_delete']) ?>"
                    data-redirect="<?= b2b_adm_esc($listUrl) ?>">
                <?= b2b_adm_esc($t['delete']) ?>
            </button>
        </div>
    </div>

    <div class="b2ba-msg" id="b2ba-msg" role="status" aria-live="polite"></div>

    <nav class="b2ba-tabs">
        <button type="button" class="b2ba-tab is-active" data-b2b-tab="data"><?= b2b_adm_esc($t['tab_data']) ?></button>
        <button type="button" class="b2ba-tab" data-b2b-tab="perms"><?= b2b_adm_esc($t['tab_perms']) ?></button>
        <button type="button" class="b2ba-tab" data-b2b-tab="prices"><?= b2b_adm_esc($t['tab_prices']) ?></button>
        <button type="button" class="b2ba-tab" data-b2b-tab="activity"><?= b2b_adm_esc($t['tab_activity']) ?> <span class="b2ba-tab__n"><?= count($logs) ?></span></button>
        <button type="button" class="b2ba-tab" data-b2b-tab="invoices"><?= b2b_adm_esc($t['tab_invoices']) ?> <span class="b2ba-tab__n"><?= count($invoices) ?></span></button>
        <button type="button" class="b2ba-tab" data-b2b-tab="reqs"><?= b2b_adm_esc($t['tab_reqs']) ?> <span class="b2ba-tab__n"><?= count($requests) ?></span></button>
    </nav>

    <!-- --------------------------------------------------------- Details -->
    <section class="b2ba-pane is-active" data-b2b-pane="data">
        <div class="b2ba-card">
            <div class="b2ba-grid">
                <label class="b2ba-field">
                    <span><?= b2b_adm_esc($t['person_type']) ?></span>
                    <select data-field="person_type">
                        <option value="company"<?= $client['person_type'] === 'company' ? ' selected' : '' ?>><?= b2b_adm_esc($t['pt_company']) ?></option>
                        <option value="individual"<?= $client['person_type'] === 'individual' ? ' selected' : '' ?>><?= b2b_adm_esc($t['pt_individual']) ?></option>
                    </select>
                </label>
                <label class="b2ba-field">
                    <span><?= b2b_adm_esc($t['full_name']) ?></span>
                    <input type="text" data-field="full_name" value="<?= b2b_adm_esc($client['full_name']) ?>" maxlength="190">
                </label>
                <label class="b2ba-field">
                    <span><?= b2b_adm_esc($t['phone']) ?></span>
                    <input type="text" data-field="phone_number" value="<?= b2b_adm_esc($client['phone_number']) ?>" maxlength="32">
                </label>
                <label class="b2ba-field b2ba-field--wide">
                    <span><?= b2b_adm_esc($t['admin_note']) ?></span>
                    <textarea data-field="admin_note" rows="3" maxlength="2000"><?= b2b_adm_esc($client['admin_note'] ?? '') ?></textarea>
                </label>
            </div>

            <div class="b2ba-card__foot">
                <p class="b2ba-meta">
                    <?= b2b_adm_esc($t['registered_at']) ?>:
                    <?= b2b_adm_esc(date('d.m.Y H:i', strtotime((string)$client['created_at']))) ?>
                    <?php if (!empty($client['last_login_ip'])): ?>
                        &middot; <?= b2b_adm_esc($t['last_ip']) ?>: <?= b2b_adm_esc($client['last_login_ip']) ?>
                    <?php endif; ?>
                </p>
                <button type="button" class="b2ba-btn b2ba-btn--primary" data-b2b-admin="save-profile">
                    <?= b2b_adm_esc($t['save']) ?>
                </button>
            </div>
        </div>

        <div class="b2ba-card">
            <h2 class="b2ba-h2"><?= b2b_adm_esc($t['set_password']) ?></h2>
            <div class="b2ba-inline">
                <input type="text" id="b2ba-new-pass" placeholder="<?= b2b_adm_esc($t['new_password']) ?>"
                       autocomplete="new-password" minlength="8">
                <button type="button" class="b2ba-btn b2ba-btn--primary" data-b2b-admin="reset-password">
                    <?= b2b_adm_esc($t['save']) ?>
                </button>
            </div>
        </div>
    </section>

    <!-- ---------------------------------------------- Region permissions -->
    <section class="b2ba-pane" data-b2b-pane="perms">
        <div class="b2ba-card">
            <p class="b2ba-hint"><?= b2b_adm_esc($t['perms_hint']) ?></p>

            <h3 class="b2ba-perm-ttl"><?= b2b_adm_esc($t['perms_regions']) ?></h3>
            <div class="b2ba-perms">
                <?php foreach (B2bConfig::REGIONS as $rg): ?>
                    <label class="b2ba-perm">
                        <input type="checkbox" class="b2ba-region" value="<?= b2b_adm_esc($rg) ?>"
                               <?= in_array($rg, $regions, true) ? 'checked' : '' ?>>
                        <span><?= b2b_adm_esc($t['rg_'.$rg] ?? $rg) ?></span>
                    </label>
                <?php endforeach; ?>
            </div>

            <h3 class="b2ba-perm-ttl"><?= b2b_adm_esc($t['perms_catalog']) ?></h3>
            <div class="b2ba-perms">
                <label class="b2ba-perm">
                    <input type="checkbox" id="b2ba-allow-in-stock"
                           <?= (int)($client['allow_in_stock'] ?? 1) === 1 ? 'checked' : '' ?>>
                    <span><?= b2b_adm_esc($t['cat_in_stock']) ?></span>
                </label>
                <label class="b2ba-perm">
                    <input type="checkbox" id="b2ba-allow-on-order"
                           <?= (int)($client['allow_on_order'] ?? 1) === 1 ? 'checked' : '' ?>>
                    <span><?= b2b_adm_esc($t['cat_on_order']) ?></span>
                </label>
            </div>

            <div class="b2ba-card__foot">
                <span></span>
                <button type="button" class="b2ba-btn b2ba-btn--primary" data-b2b-admin="save-perms">
                    <?= b2b_adm_esc($t['save']) ?>
                </button>
            </div>
        </div>
    </section>

    <!-- ------------------------------------------------ Per-client prices -->
    <section class="b2ba-pane" data-b2b-pane="prices">
        <div class="b2ba-card">
            <p class="b2ba-hint"><?= b2b_adm_esc($t['prices_hint']) ?></p>

            <div class="b2ba-price-status">
                <?php foreach ($priceTables as $k => $info): $custom = $priceCustom[$k]; ?>
                    <div class="b2ba-price-row">
                        <span class="b2ba-price-name"><?= b2b_adm_esc($info[1]) ?></span>
                        <span class="b2bp-tag b2bp-tag--<?= $custom ? 'custom' : 'global' ?>">
                            <?= b2b_adm_esc($custom ? $t['pricing_tag_custom'] : $t['pricing_tag_global']) ?>
                        </span>
                    </div>
                <?php endforeach; ?>
            </div>

            <div class="b2ba-card__foot">
                <span class="b2ba-meta">
                    <?= b2b_adm_esc($priceHasAny ? $t['prices_has_custom'] : $t['prices_all_global']) ?>
                </span>
                <a class="b2ba-btn b2ba-btn--primary" href="<?= b2b_adm_esc($pricingEditUrl) ?>">
                    <?= b2b_adm_esc($t['prices_open_editor']) ?>
                </a>
            </div>
        </div>
    </section>

    <!-- ---------------------------------------------------- Activity log -->
    <section class="b2ba-pane" data-b2b-pane="activity">
        <?php if (!$logs): ?>
            <div class="b2ba-empty"><?= b2b_adm_esc($t['no_activity']) ?></div>
        <?php else: ?>
            <div class="b2ba-table-wrap">
                <table class="b2ba-table">
                    <thead><tr>
                        <th><?= b2b_adm_esc($t['col_date']) ?></th>
                        <th><?= b2b_adm_esc($t['col_action']) ?></th>
                        <th><?= b2b_adm_esc($t['col_details']) ?></th>
                        <th><?= b2b_adm_esc($t['col_ip']) ?></th>
                    </tr></thead>
                    <tbody>
                    <?php foreach ($logs as $log):
                        $details = $log['details'] ? json_decode((string)$log['details'], true) : null;
                        $carId   = is_array($details) ? (int)($details['car_id'] ?? 0) : 0;
                    ?>
                        <tr>
                            <td class="b2ba-td--small"><?= b2b_adm_esc(date('d.m.Y H:i:s', strtotime((string)$log['created_at']))) ?></td>
                            <td><?= b2b_adm_esc($t['ac_'.$log['action_type']] ?? $log['action_type']) ?></td>
                            <td class="b2ba-td--small">
                                <?php if ($carId > 0): ?>
                                    <a href="/<?= b2b_adm_esc($lang) ?>/ordercars/<?= $carId ?>" target="_blank" rel="noopener">#<?= $carId ?></a>
                                <?php endif; ?>
                                <?php if (is_array($details)): ?>
                                    <?php foreach ($details as $dk => $dv): ?>
                                        <?php if ($dk === 'car_id') continue; ?>
                                        <span class="b2ba-kv"><?= b2b_adm_esc($dk) ?>: <?= b2b_adm_esc(is_scalar($dv) ? $dv : json_encode($dv)) ?></span>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </td>
                            <td class="b2ba-td--small"><?= b2b_adm_esc($log['ip_address'] ?? '') ?></td>
                        </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </section>

    <!-- -------------------------------------------------------- Invoices -->
    <section class="b2ba-pane" data-b2b-pane="invoices">
        <?php if (!$invoices): ?>
            <div class="b2ba-empty"><?= b2b_adm_esc($t['no_invoices']) ?></div>
        <?php else: ?>
            <div class="b2ba-table-wrap">
                <table class="b2ba-table">
                    <thead><tr>
                        <th><?= b2b_adm_esc($t['col_invoice']) ?></th>
                        <th><?= b2b_adm_esc($t['col_car']) ?></th>
                        <th>&Sigma;</th>
                        <th><?= b2b_adm_esc($t['col_status']) ?></th>
                        <th><?= b2b_adm_esc($t['col_date']) ?></th>
                        <th></th>
                    </tr></thead>
                    <tbody>
                    <?php foreach ($invoices as $inv):
                        $snap = B2bInvoice::carSnapshot($inv);
                    ?>
                        <tr>
                            <td class="b2ba-td--strong"><?= b2b_adm_esc($inv['invoice_no']) ?></td>
                            <td><a href="/<?= b2b_adm_esc($lang) ?>/ordercars/<?= (int)$inv['car_id'] ?>" target="_blank" rel="noopener"><?= b2b_adm_esc($snap['title'] ?? ('#'.(int)$inv['car_id'])) ?></a></td>
                            <td><?= b2b_adm_esc(number_format((float)$inv['advance_amount'], 2, '.', ' ').' '.$inv['currency']) ?></td>
                            <td><span class="b2ba-badge b2ba-badge--<?= b2b_adm_esc($inv['status']) ?>"><?= b2b_adm_esc($t['st_'.$inv['status']] ?? $inv['status']) ?></span></td>
                            <td class="b2ba-td--small"><?= b2b_adm_esc(date('d.m.Y H:i', strtotime((string)$inv['created_at']))) ?></td>
                            <td><a class="b2ba-btn b2ba-btn--ghost b2ba-btn--sm" href="<?= b2b_adm_esc(B2bInvoice::path($inv)) ?>" target="_blank" rel="noopener"><?= b2b_adm_esc($t['open']) ?></a></td>
                        </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </section>

    <!-- -------------------------------------------------------- Requests -->
    <section class="b2ba-pane" data-b2b-pane="reqs">
        <?php if (!$requests): ?>
            <div class="b2ba-empty"><?= b2b_adm_esc($t['no_requests']) ?></div>
        <?php else: ?>
            <div class="b2ba-table-wrap">
                <table class="b2ba-table">
                    <thead><tr>
                        <th><?= b2b_adm_esc($t['col_car']) ?></th>
                        <th><?= b2b_adm_esc($t['col_invoice']) ?></th>
                        <th><?= b2b_adm_esc($t['col_comment']) ?></th>
                        <th><?= b2b_adm_esc($t['col_status']) ?></th>
                        <th><?= b2b_adm_esc($t['col_date']) ?></th>
                    </tr></thead>
                    <tbody>
                    <?php foreach ($requests as $req): ?>
                        <tr>
                            <td><a href="/<?= b2b_adm_esc($lang) ?>/ordercars/<?= (int)$req['car_id'] ?>" target="_blank" rel="noopener">#<?= (int)$req['car_id'] ?></a></td>
                            <td><?= b2b_adm_esc($req['invoice_no'] ?? '—') ?></td>
                            <td class="b2ba-td--wrap"><?= b2b_adm_esc($req['comment'] ?? '—') ?></td>
                            <td><span class="b2ba-badge b2ba-badge--<?= b2b_adm_esc($req['status']) ?>"><?= b2b_adm_esc($t['st_'.$req['status']] ?? $req['status']) ?></span></td>
                            <td class="b2ba-td--small"><?= b2b_adm_esc(date('d.m.Y H:i', strtotime((string)$req['created_at']))) ?></td>
                        </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </section>
</div>

<script src="/content/admin/include/b2b/b2b_admin.js?v=<?= file_exists(_ROOT.'/content/admin/include/b2b/b2b_admin.js') ? date('YmdHis', filemtime(_ROOT.'/content/admin/include/b2b/b2b_admin.js')) : '1' ?>" defer></script>
