<?php defined('_DOIT') or die('Restricted access');

/**
 * B2B client profile (spec 3.1 / 3.2 / 3.3).
 * Tabs: details, region permissions, pricing, invoices.
 * Every action goes through content/admin/ajax/b2b/ajax.php.
 */

use App\Services\B2b\B2bAuth;
use App\Services\B2b\B2bConfig;
use App\Services\B2b\B2bGift;
use App\Services\B2b\B2bInvoice;
use App\Services\B2b\B2bPhone;
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
$invoices = B2bInvoice::forUser($uid, 200);

?>

<div class="b2ba" data-b2b-user="<?= $uid ?>" data-saved-msg="<?= b2b_adm_esc($t['saved']) ?>">
    <a class="b2ba-back" href="<?= b2b_adm_esc($listUrl) ?>"><?= b2b_adm_esc($t['back_to_list']) ?></a>

    <div class="b2ba-head">
        <div>
            <div class="b2ba-title-row">
                <h1 class="b2ba-h1"><?= b2b_adm_esc(B2bAuth::displayName($client)) ?></h1>
                <span class="b2ba-badge b2ba-badge--<?= b2b_adm_esc($client['status']) ?>" id="b2ba-status-badge">
                    <?= b2b_adm_esc($t['st_'.$client['status']] ?? $client['status']) ?>
                </span>
            </div>
        </div>
        <div class="b2ba-head__actions">
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
        <button type="button" class="b2ba-tab" data-b2b-tab="invoices"><?= b2b_adm_esc($t['tab_invoices']) ?> <span class="b2ba-tab__n"><?= count($invoices) ?></span></button>
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
                <?php
                // The client owns these: they typed them at signup and nothing here
                // may overwrite them. Same treatment as the locked fields in the
                // client's own payment-invoice form — read-only, greyed, padlock.
                // No data-field attribute, so the save never even sends them.
                ?>
                <div class="b2ba-field">
                    <span><?= b2b_adm_esc($t['full_name']) ?></span>
                    <div class="b2ba-locked">
                        <input type="text" value="<?= b2b_adm_esc($client['full_name']) ?>" readonly tabindex="-1">
                        <?= b2b_adm_lock_icon() ?>
                    </div>
                </div>
                <div class="b2ba-field">
                    <span><?= b2b_adm_esc($t['phone']) ?></span>
                    <div class="b2ba-locked">
                        <input type="text" value="<?= b2b_adm_esc(B2bPhone::local($client['phone_number'])) ?>" readonly tabindex="-1">
                        <?= b2b_adm_lock_icon() ?>
                    </div>
                </div>
                <label class="b2ba-field b2ba-field--wide">
                    <span><?= b2b_adm_esc($t['admin_note']) ?></span>
                    <textarea data-field="admin_note" rows="3" maxlength="2000"><?= b2b_adm_esc($client['admin_note'] ?? '') ?></textarea>
                </label>
            </div>

            <div class="b2ba-card__foot">
                <p class="b2ba-meta">
                    <?= b2b_adm_esc($t['registered_at']) ?>:
                    <?= b2b_adm_esc(date('d.m.Y H:i', strtotime((string)$client['created_at']))) ?>
                </p>
                <button type="button" class="b2ba-btn b2ba-btn--primary" data-b2b-admin="save-profile">
                    <?= b2b_adm_esc($t['save']) ?>
                </button>
            </div>
        </div>

        <?php
        // Gifts are granted from the client list; this is where they can be
        // reviewed and withdrawn. Withdrawn ones stay visible, struck through,
        // so a mistake leaves a trace instead of vanishing.
        $gifts      = B2bGift::historyFor($uid);
        $giftLabels = B2bGift::labels($lang);
        ?>
        <div class="b2ba-card">
            <h2 class="b2ba-h2"><?= b2b_adm_esc($t['gift_list_title']) ?></h2>
            <?php if (!$gifts): ?>
                <p class="b2ba-hint"><?= b2b_adm_esc($t['gift_none']) ?></p>
            <?php else: ?>
                <ul class="b2bg-log">
                    <?php foreach ($gifts as $g):
                        $revoked = !empty($g['revoked_at']);
                        $what    = B2bGift::describe($g, $lang);
                        $note    = trim((string)($g['note'] ?? ''));

                        // A gift may be nothing but a note; using it as the title
                        // beats printing a dash and hiding the real content below.
                        $title    = $what !== '' ? $what : ($note !== '' ? $note : '—');
                        $showNote = ($what !== '' && $note !== '');

                        // One badge, in priority order: withdrawn outranks whether
                        // the client had seen it.
                        [$stKey, $stMod] = $revoked
                            ? ['gift_st_revoked', 'revoked']
                            : (empty($g['seen_at']) ? ['gift_st_new', 'new'] : ['gift_st_seen', 'seen']);
                    ?>
                        <li class="b2bg-log__item<?= $revoked ? ' is-revoked' : '' ?>">
                            <div class="b2bg-log__main">
                                <div class="b2bg-log__top">
                                    <strong><?= b2b_adm_esc($title) ?></strong>
                                    <span class="b2bg-tag b2bg-tag--<?= $stMod ?>"><?= b2b_adm_esc($t[$stKey]) ?></span>
                                </div>
                                <?php if ($showNote): ?>
                                    <span class="b2bg-log__note"><?= b2b_adm_esc($note) ?></span>
                                <?php endif; ?>
                                <span class="b2bg-log__meta"><?= b2b_adm_esc(date('d.m.Y H:i', strtotime((string)$g['created_at']))) ?></span>
                            </div>
                            <?php if (!$revoked): ?>
                                <button type="button" class="b2ba-btn b2ba-btn--soft b2ba-btn--sm"
                                        data-b2b-gift-revoke data-gift="<?= (int)$g['id'] ?>"
                                        data-confirm="<?= b2b_adm_esc($t['gift_confirm_rev']) ?>">
                                    <?= b2b_adm_esc($t['gift_revoke']) ?>
                                </button>
                            <?php endif; ?>
                        </li>
                    <?php endforeach; ?>
                </ul>
            <?php endif; ?>
        </div>

        <div class="b2ba-card">
            <h2 class="b2ba-h2"><?= b2b_adm_esc($t['set_password']) ?></h2>
            <div class="b2ba-inline">
                <input type="text" id="b2ba-new-pass" placeholder="<?= b2b_adm_esc($t['new_password']) ?>"
                       autocomplete="new-password" minlength="6">
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
        <?php
        // Reuse the full pricing editor, scoped to this client. It renders its own
        // 4 cards + save/reset and posts to fn=save_pricing with this user_id.
        $pricingEmbedded = true;
        $pricingUserId   = $uid;
        include _ADM_INCL.'/b2b/views/pricing.php';
        ?>
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
                        <th><?= b2b_adm_esc($t['col_advance']) ?></th>
                        <th><?= b2b_adm_esc($t['col_status']) ?></th>
                        <th><?= b2b_adm_esc($t['col_date']) ?></th>
                        <th></th>
                    </tr></thead>
                    <tbody>
                    <?php foreach ($invoices as $inv):
                        $snap = B2bInvoice::carSnapshot($inv);
                        // Show the Super Admin decision (linked request), fall back to
                        // the invoice's own status when there is no request yet.
                        $invSt = ($inv['req_status'] ?? '') !== '' ? (string)$inv['req_status'] : (string)$inv['status'];
                    ?>
                        <tr>
                            <td class="b2ba-td--strong b2ba-td--title"><?= b2b_adm_esc($inv['invoice_no']) ?></td>
                            <td data-label="<?= b2b_adm_esc($t['col_car']) ?>"><a href="/<?= b2b_adm_esc($lang) ?>/ordercars/<?= (int)$inv['car_id'] ?>" target="_blank" rel="noopener"><?= b2b_adm_esc($snap['title'] ?? ('#'.(int)$inv['car_id'])) ?></a></td>
                            <td data-label="<?= b2b_adm_esc($t['col_advance']) ?>"><?= b2b_adm_esc(number_format((float)$inv['advance_amount'], 0, '.', '').' '.$inv['currency']) ?></td>
                            <td class="b2ba-td--status"><span class="b2ba-badge b2ba-badge--<?= b2b_adm_esc($invSt) ?>"><?= b2b_adm_esc($t['st_'.$invSt] ?? $invSt) ?></span></td>
                            <td class="b2ba-td--small" data-label="<?= b2b_adm_esc($t['col_date']) ?>"><?= b2b_adm_esc(date('d.m.Y', strtotime((string)$inv['created_at']))) ?></td>
                            <td class="b2ba-td--action"><a class="b2ba-btn b2ba-btn--ghost b2ba-btn--sm" href="<?= b2b_adm_esc(B2bInvoice::path($inv)) ?>" target="_blank" rel="noopener"><?= b2b_adm_esc($t['open']) ?></a></td>
                        </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </section>
</div>

<script src="/content/admin/include/b2b/b2b_admin.js?v=<?= file_exists(_ROOT.'/content/admin/include/b2b/b2b_admin.js') ? date('YmdHis', filemtime(_ROOT.'/content/admin/include/b2b/b2b_admin.js')) : '1' ?>" defer></script>
