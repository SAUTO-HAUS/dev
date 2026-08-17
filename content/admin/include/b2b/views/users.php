<?php defined('_DOIT') or die('Restricted access');

/** B2B client list with status filters (spec 3.1, account approval). */

use App\Services\B2b\B2bAuth;
use App\Services\B2b\B2bConfig;
use App\Services\B2b\B2bGift;
use App\Services\B2b\B2bPhone;
use App\Services\B2b\B2bRegions;

$lang = $_COOKIE['lang'] ?? 'ro';
$t    = b2b_adm_lang($lang);

$statuses = ['active', 'blocked'];
$filter   = isset($_GET['status']) && in_array($_GET['status'], $statuses, true) ? $_GET['status'] : '';
$search   = trim((string)($_GET['q'] ?? ''));

// Per-status counts for the filter badges.
$counts = ['' => 0, 'active' => 0, 'blocked' => 0];
try {
    $rows = $db->query('SELECT status, COUNT(*) AS n FROM '.B2bConfig::table('users').' GROUP BY status')
               ->fetchAll(PDO::FETCH_ASSOC);
    foreach ($rows as $row) {
        $counts[$row['status']] = (int)$row['n'];
        $counts[''] += (int)$row['n'];
    }
} catch (Throwable $e) {
    // Not migrated yet: show an empty list rather than a fatal error.
}

$sql  = 'SELECT * FROM '.B2bConfig::table('users').' WHERE 1=1';
$args = [];

if ($filter !== '') {
    $sql .= ' AND status = :status';
    $args[':status'] = $filter;
}
if ($search !== '') {
    // Distinct placeholders: this PDO runs with ATTR_EMULATE_PREPARES=false,
    // where reusing one named placeholder across several positions is not supported.
    $sql .= ' AND (login LIKE :q1 OR full_name LIKE :q2 OR email LIKE :q3 OR phone_number LIKE :q4 OR company_name LIKE :q5)';
    $like = '%'.$search.'%';
    $args[':q1'] = $like;
    $args[':q2'] = $like;
    $args[':q3'] = $like;
    $args[':q4'] = $like;
    $args[':q5'] = $like;
}
$sql .= ' ORDER BY FIELD(status, "active", "blocked"), id DESC LIMIT 500';

$users = [];
try {
    $stmt = $db->prepare($sql);
    $stmt->execute($args);
    $users = $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
} catch (Throwable $e) {
    $users = [];
}

$baseUrl = '/'.$lang.'/'.$admin_dir.'/b2b/users';

// Active gift count per listed client, in one query, so each row can show how
// many that client already has without a query per row.
$giftCounts = [];
if ($users) {
    try {
        $ids = array_map(static fn($u) => (int)$u['id'], $users);
        $in  = implode(',', array_fill(0, count($ids), '?'));
        $gq  = $db->prepare('SELECT b2b_user_id, COUNT(*) FROM '.B2bConfig::prefix().'_b2b_gifts
                              WHERE revoked_at IS NULL AND b2b_user_id IN ('.$in.')
                              GROUP BY b2b_user_id');
        $gq->execute($ids);
        $giftCounts = $gq->fetchAll(PDO::FETCH_KEY_PAIR) ?: [];
    } catch (Throwable $e) {
        $giftCounts = []; // table not migrated yet
    }
}

$giftLabels = B2bGift::labels($lang);
?>

<div class="b2ba">
    <div class="b2ba-head">
        <h1 class="b2ba-h1"><?= b2b_adm_esc($t['users_title']) ?></h1>

        <form class="b2ba-search" method="get" action="<?= b2b_adm_esc($baseUrl) ?>">
            <?php if ($filter !== ''): ?>
                <input type="hidden" name="status" value="<?= b2b_adm_esc($filter) ?>">
            <?php endif; ?>
            <input type="search" name="q" value="<?= b2b_adm_esc($search) ?>" placeholder="<?= b2b_adm_esc($t['search']) ?>">
            <button type="submit" class="b2ba-btn b2ba-btn--ghost">&#128269;</button>
        </form>
    </div>

    <div class="b2ba-msg" id="b2ba-msg" role="status" aria-live="polite"></div>

    <?php
    // Master switch for the personal price deadlines. A partner's own deadline
    // outranks the general offer, so without this there is no single place left to
    // end the preferential prices for everyone at once.
    $ignorePersonal = B2bConfig::get('b2b_ignore_personal_deadlines', '0') === '1';
    ?>
    <label class="b2ba-switch<?= $ignorePersonal ? ' is-on' : '' ?>">
        <input type="checkbox" data-toggle-setting="b2b_ignore_personal_deadlines"
               <?= $ignorePersonal ? 'checked' : '' ?>
               data-msg-on="<?= b2b_adm_esc($t['ignore_personal_on']) ?>"
               data-msg-off="<?= b2b_adm_esc($t['ignore_personal_off']) ?>">
        <span class="b2ba-switch__box" aria-hidden="true"></span>
        <span class="b2ba-switch__txt">
            <strong><?= b2b_adm_esc($t['ignore_personal_title']) ?></strong>
            <small><?= b2b_adm_esc($t['ignore_personal_hint']) ?></small>
        </span>
    </label>

    <nav class="b2ba-filters">
        <?php
        $filterLabels = ['' => $t['filter_all'], 'active' => $t['st_active'], 'blocked' => $t['st_blocked']];
        foreach ($filterLabels as $key => $label) {
            $href = $baseUrl.($key !== '' ? '?status='.$key : '');
            if ($search !== '') {
                $href .= ($key !== '' ? '&' : '?').'q='.urlencode($search);
            }
            echo '<a class="b2ba-filter'.($filter === $key ? ' is-active' : '').'" href="'.b2b_adm_esc($href).'">'
               . b2b_adm_esc($label)
               . ' <span class="b2ba-filter__n">'.(int)($counts[$key] ?? 0).'</span></a>';
        }
        ?>
    </nav>

    <?php if (!$users): ?>
        <div class="b2ba-empty"><?= b2b_adm_esc($t['no_users']) ?></div>
    <?php else: ?>
        <div class="b2ba-table-wrap">
            <table class="b2ba-table">
                <thead>
                    <tr>
                        <th class="b2ba-th--num">#</th>
                        <th><?= b2b_adm_esc($t['col_name']) ?></th>
                        <th><?= b2b_adm_esc($t['col_person_type']) ?></th>
                        <th><?= b2b_adm_esc($t['col_contact']) ?></th>
                        <th><?= b2b_adm_esc($t['col_status']) ?></th>
                        <th></th>
                    </tr>
                </thead>
                <tbody>
                <?php $rowNo = 0; foreach ($users as $u):
                    $uid    = (int)$u['id'];
                    $status = (string)$u['status'];
                    $detail = '/'.b2b_adm_esc($lang).'/'.b2b_adm_esc($admin_dir).'/b2b/user?id='.$uid;
                ?>
                    <tr class="b2ba-row-open" data-b2b-open="<?= $detail ?>">
                        <td class="b2ba-td--num"><?= ++$rowNo ?></td>
                        <?php // The name carries the link: the row opens the profile on click, but
                              // this keeps a real anchor for keyboard and no-JS navigation. ?>
                        <td class="b2ba-td--title"><a class="b2ba-row-link" href="<?= $detail ?>"><?= b2b_adm_esc(B2bAuth::displayName($u)) ?></a></td>
                        <td data-label="<?= b2b_adm_esc($t['col_person_type']) ?>">
                            <?php // Super Admin can correct a mistyped type inline (saves on change, no reload). ?>
                            <select class="b2ba-ptype" data-b2b-list-ptype data-user="<?= $uid ?>"
                                    data-prev="<?= b2b_adm_esc($u['person_type']) ?>" aria-label="<?= b2b_adm_esc($t['person_type']) ?>">
                                <option value="individual"<?= $u['person_type'] === 'individual' ? ' selected' : '' ?>><?= b2b_adm_esc($t['pt_individual']) ?></option>
                                <option value="company"<?= $u['person_type'] === 'company' ? ' selected' : '' ?>><?= b2b_adm_esc($t['pt_company']) ?></option>
                            </select>
                        </td>
                        <td class="b2ba-td--small" data-label="<?= b2b_adm_esc($t['col_contact']) ?>">
                            <?= b2b_adm_esc($u['email']) ?><br>
                            <?= b2b_adm_esc($u['phone_number']) ?>
                        </td>
                        <td class="b2ba-td--status">
                            <span class="b2ba-badge b2ba-badge--<?= b2b_adm_esc($status) ?>">
                                <?= b2b_adm_esc($t['st_'.$status] ?? $status) ?>
                            </span>
                        </td>
                        <td class="b2ba-td--action">
                            <div class="b2ba-row-actions">
                                <?php $tel = B2bPhone::normalize((string)$u['phone_number']); ?>
                                <?php if ($tel !== ''): ?>
                                    <a class="b2ba-btn b2ba-btn--call b2ba-btn--sm" href="tel:<?= b2b_adm_esc($tel) ?>">
                                        <?= b2b_adm_phone_icon() ?><?= b2b_adm_esc($t['call']) ?>
                                    </a>
                                <?php endif; ?>
                                <?php // Full activity log: what they viewed, from which source, when. ?>
                                <a class="b2ba-btn b2ba-btn--ghost b2ba-btn--sm b2ba-btn--hist"
                                   href="/<?= b2b_adm_esc($lang) ?>/<?= b2b_adm_esc($admin_dir) ?>/b2b/history?id=<?= $uid ?>">
                                    <?= b2b_adm_esc($t['hist_open']) ?>
                                </a>
                                <?php // Opens the gift dialog below, pre-filled with this client. ?>
                                <button type="button" class="b2ba-btn b2ba-btn--soft b2ba-btn--sm b2ba-btn--gift"
                                        data-b2b-gift-open data-user="<?= $uid ?>"
                                        data-name="<?= b2b_adm_esc(B2bAuth::displayName($u)) ?>">
                                    <?= b2b_adm_esc($t['gift_send']) ?>
                                    <?php if (($giftCounts[$uid] ?? 0) > 0): ?>
                                        <span class="b2ba-gift-n"><?= (int)$giftCounts[$uid] ?></span>
                                    <?php endif; ?>
                                </button>
                                <?php if ($status === 'blocked'): ?>
                                    <button type="button" class="b2ba-btn b2ba-btn--ok b2ba-btn--sm"
                                            data-b2b-list-status data-user="<?= $uid ?>" data-value="active">
                                        <?= b2b_adm_esc($t['unblock']) ?>
                                    </button>
                                <?php endif; ?>
                                <?php if ($status !== 'blocked'): ?>
                                    <button type="button" class="b2ba-btn b2ba-btn--red b2ba-btn--sm"
                                            data-b2b-list-status data-user="<?= $uid ?>" data-value="blocked"
                                            data-confirm="<?= b2b_adm_esc($t['confirm_block']) ?>">
                                        <?= b2b_adm_esc($t['block']) ?>
                                    </button>
                                <?php endif; ?>
                            </div>
                        </td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php endif; ?>

    <?php
    // One dialog for the whole list; the row button fills in which client it is
    // for. Kept out of the table so the markup is not repeated per row.
    ?>
    <div class="b2ba-modal" id="b2ba-gift" hidden>
        <div class="b2ba-modal__bg" data-b2b-gift-close></div>
        <div class="b2ba-modal__box" role="dialog" aria-modal="true" aria-labelledby="b2ba-gift-ttl">
            <?php
            // Same [data-b2b-gift-close] the backdrop and Cancel use. It sits
            // outside __body on purpose: the body is what scrolls, so the button
            // stays pinned to the corner of the dialog.
            ?>
            <button type="button" class="b2ba-modal__x" data-b2b-gift-close
                    aria-label="<?= b2b_adm_esc($t['cancel']) ?>">&times;</button>

            <div class="b2ba-modal__body">
            <h2 class="b2ba-h2" id="b2ba-gift-ttl"><?= b2b_adm_esc($t['gift_title']) ?></h2>
            <p class="b2ba-hint"><?= b2b_adm_esc($t['gift_hint']) ?></p>
            <p class="b2bg-who"><strong data-b2b-gift-name></strong></p>

            <div class="b2bg-list">
                <?php foreach ($giftLabels as $code => $label): ?>
                    <label class="b2ba-perm">
                        <input type="checkbox" class="b2bg-item" value="<?= b2b_adm_esc($code) ?>">
                        <span><?= b2b_adm_esc($label) ?></span>
                    </label>
                <?php endforeach; ?>
            </div>

            <label class="b2ba-field b2ba-field--wide">
                <span><?= b2b_adm_esc($t['gift_note']) ?></span>
                <textarea id="b2ba-gift-note" rows="2" maxlength="<?= B2bGift::NOTE_MAX ?>"></textarea>
            </label>

            <div class="b2ba-card__foot">
                <button type="button" class="b2ba-btn b2ba-btn--soft" data-b2b-gift-close><?= b2b_adm_esc($t['cancel'] ?? 'Anulează') ?></button>
                <button type="button" class="b2ba-btn b2ba-btn--primary" data-b2b-gift-send><?= b2b_adm_esc($t['gift_send']) ?></button>
            </div>

            <?php
            // Gifts this client already has, loaded when the dialog opens. Sending
            // and withdrawing belong together — splitting them across two pages
            // would mean hunting for the profile just to undo a mistake.
            ?>
            <div class="b2bg-existing" id="b2ba-gift-list"
                 data-title="<?= b2b_adm_esc($t['gift_list_title']) ?>"
                 data-none="<?= b2b_adm_esc($t['gift_none']) ?>"
                 data-revoke="<?= b2b_adm_esc($t['gift_revoke']) ?>"
                 data-confirm="<?= b2b_adm_esc($t['gift_confirm_rev']) ?>"
                 data-st-new="<?= b2b_adm_esc($t['gift_st_new']) ?>"
                 data-st-seen="<?= b2b_adm_esc($t['gift_st_seen']) ?>"
                 data-st-revoked="<?= b2b_adm_esc($t['gift_st_revoked']) ?>"></div>
            </div><!-- /.b2ba-modal__body -->
        </div>
    </div>
</div>

<script src="/content/admin/include/b2b/b2b_admin.js?v=<?= file_exists(_ROOT.'/content/admin/include/b2b/b2b_admin.js') ? date('YmdHis', filemtime(_ROOT.'/content/admin/include/b2b/b2b_admin.js')) : '1' ?>" defer></script>
