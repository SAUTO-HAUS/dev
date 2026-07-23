<?php defined('_DOIT') or die('Restricted access');

/** "Send to Super Admin" requests across all clients (spec 2.3.3). */

use App\Services\B2b\B2bAuth;
use App\Services\B2b\B2bConfig;
use App\Services\B2b\B2bInvoice;

$lang = $_COOKIE['lang'] ?? 'ro';
$t    = b2b_adm_lang($lang);

$statuses = ['new', 'seen', 'approved', 'rejected'];
$filter   = isset($_GET['status']) && in_array($_GET['status'], $statuses, true) ? $_GET['status'] : '';
$focusId  = (int)($_GET['id'] ?? 0);

$counts = ['' => 0, 'new' => 0, 'seen' => 0, 'approved' => 0, 'rejected' => 0];
try {
    foreach ($db->query('SELECT status, COUNT(*) AS n FROM '.B2bConfig::table('requests').' GROUP BY status')
                ->fetchAll(PDO::FETCH_ASSOC) as $row) {
        $counts[$row['status']] = (int)$row['n'];
        $counts[''] += (int)$row['n'];
    }
} catch (Throwable $e) {
    // not migrated yet
}

$sql = 'SELECT r.*, u.company_name, u.full_name, u.phone_number, i.invoice_no, i.access_key, i.advance_amount, i.currency
          FROM '.B2bConfig::table('requests').' AS r
          JOIN '.B2bConfig::table('users').' AS u ON u.id = r.b2b_user_id
          LEFT JOIN '.B2bConfig::table('invoices').' AS i ON i.id = r.invoice_id
         WHERE 1=1';
$args = [];

if ($filter !== '') {
    $sql .= ' AND r.status = :status';
    $args[':status'] = $filter;
}
$sql .= ' ORDER BY FIELD(r.status, "new", "seen", "approved", "rejected"), r.id DESC LIMIT 500';

$requests = [];
try {
    $stmt = $db->prepare($sql);
    $stmt->execute($args);
    $requests = $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
} catch (Throwable $e) {
    $requests = [];
}

$baseUrl = '/'.$lang.'/'.$admin_dir.'/b2b/requests';
?>

<div class="b2ba">
    <div class="b2ba-head">
        <h1 class="b2ba-h1"><?= b2b_adm_esc($t['requests_title']) ?></h1>
    </div>

    <nav class="b2ba-filters">
        <?php
        $filterLabels = ['' => $t['filter_all'], 'new' => $t['st_new'], 'seen' => $t['st_seen'], 'approved' => $t['st_approved'], 'rejected' => $t['st_rejected']];
        foreach ($filterLabels as $key => $label) {
            $href = $baseUrl.($key !== '' ? '?status='.$key : '');
            echo '<a class="b2ba-filter'.($filter === $key ? ' is-active' : '').'" href="'.b2b_adm_esc($href).'">'
               . b2b_adm_esc($label).' <span class="b2ba-filter__n">'.(int)($counts[$key] ?? 0).'</span></a>';
        }
        ?>
    </nav>

    <div class="b2ba-msg" id="b2ba-msg" role="status" aria-live="polite"></div>

    <?php if (!$requests): ?>
        <div class="b2ba-empty"><?= b2b_adm_esc($t['no_requests']) ?></div>
    <?php else: ?>
        <div class="b2ba-table-wrap">
            <table class="b2ba-table">
                <thead><tr>
                    <th><?= b2b_adm_esc($t['col_client']) ?></th>
                    <th><?= b2b_adm_esc($t['col_car']) ?></th>
                    <th><?= b2b_adm_esc($t['col_invoice']) ?></th>
                    <th><?= b2b_adm_esc($t['col_comment']) ?></th>
                    <th><?= b2b_adm_esc($t['col_status']) ?></th>
                    <th><?= b2b_adm_esc($t['col_date']) ?></th>
                    <th><?= b2b_adm_esc($t['col_actions']) ?></th>
                </tr></thead>
                <tbody>
                <?php foreach ($requests as $req):
                    $rid = (int)$req['id'];
                    $car = B2bInvoice::loadCar((int)$req['car_id']);
                ?>
                    <tr id="b2ba-req-<?= $rid ?>" class="<?= $focusId === $rid ? 'is-focus' : '' ?>">
                        <td>
                            <a href="/<?= b2b_adm_esc($lang) ?>/<?= b2b_adm_esc($admin_dir) ?>/b2b/user?id=<?= (int)$req['b2b_user_id'] ?>"
                               class="b2ba-td--strong"><?= b2b_adm_esc(B2bAuth::displayName($req)) ?></a>
                            <div class="b2ba-td--small"><?= b2b_adm_esc($req['full_name']) ?> · <?= b2b_adm_esc($req['phone_number']) ?></div>
                        </td>
                        <td>
                            <a href="/<?= b2b_adm_esc($lang) ?>/ordercars/<?= (int)$req['car_id'] ?>" target="_blank" rel="noopener">
                                <?= b2b_adm_esc($car['title'] ?? ('#'.(int)$req['car_id'])) ?>
                            </a>
                        </td>
                        <td class="b2ba-td--small">
                            <?php if (!empty($req['invoice_no'])): ?>
                                <a href="/<?= b2b_adm_esc($lang) ?>/b2b/invoice/<?= (int)$req['invoice_id'] ?>?k=<?= b2b_adm_esc($req['access_key']) ?>"
                                   target="_blank" rel="noopener"><?= b2b_adm_esc($req['invoice_no']) ?></a>
                                <div><?= b2b_adm_esc(number_format((float)$req['advance_amount'], 2, '.', ' ').' '.$req['currency']) ?></div>
                            <?php else: ?>
                                &mdash;
                            <?php endif; ?>
                        </td>
                        <td class="b2ba-td--wrap"><?= b2b_adm_esc($req['comment'] ?? '—') ?></td>
                        <td>
                            <span class="b2ba-badge b2ba-badge--<?= b2b_adm_esc($req['status']) ?>" data-b2b-req-badge="<?= $rid ?>">
                                <?= b2b_adm_esc($t['st_'.$req['status']] ?? $req['status']) ?>
                            </span>
                        </td>
                        <td class="b2ba-td--small"><?= b2b_adm_esc(date('d.m.Y H:i', strtotime((string)$req['created_at']))) ?></td>
                        <td class="b2ba-td--nowrap">
                            <button type="button" class="b2ba-btn b2ba-btn--ghost b2ba-btn--sm"
                                    data-b2b-admin="request-status" data-request="<?= $rid ?>" data-value="seen"><?= b2b_adm_esc($t['mark_seen']) ?></button>
                            <button type="button" class="b2ba-btn b2ba-btn--ok b2ba-btn--sm"
                                    data-b2b-admin="request-status" data-request="<?= $rid ?>" data-value="approved"><?= b2b_adm_esc($t['mark_approved']) ?></button>
                            <button type="button" class="b2ba-btn b2ba-btn--danger b2ba-btn--sm"
                                    data-b2b-admin="request-status" data-request="<?= $rid ?>" data-value="rejected"><?= b2b_adm_esc($t['mark_rejected']) ?></button>
                        </td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php endif; ?>
</div>

<script src="/content/admin/include/b2b/b2b_admin.js?v=<?= file_exists(_ROOT.'/content/admin/include/b2b/b2b_admin.js') ? date('YmdHis', filemtime(_ROOT.'/content/admin/include/b2b/b2b_admin.js')) : '1' ?>" defer></script>
