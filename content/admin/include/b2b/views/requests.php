<?php defined('_DOIT') or die('Restricted access');

/** "Send to Super Admin" requests across all clients (spec 2.3.3). */

use App\Services\B2b\B2bAuth;
use App\Services\B2b\B2bConfig;
use App\Services\B2b\B2bInvoice;

// The price each partner saw is recomputed here, so the breakdown functions and
// the catalog fuel-code map have to be available in the admin context too. Both
// files only declare functions, guarded against a double include.
require_once _ADM_PAGE.'/parsing/parsing_pricing.php';
require_once _SITE_INCL.'/b2b/b2b_pricing.php';

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

/**
 * The price THIS partner saw for the car — never the catalog one, which is the
 * public price. Recomputed with the same breakdown the site runs, flagged for
 * B2B and scoped to the partner: their own price tables when they have any,
 * the global B2B set otherwise. Cars outside the parsing catalogs have no
 * breakdown, so there the catalog price is what the partner saw.
 *
 * @return array{amount:int, cur:string}|null
 */
$clientPrice = function (?array $car, int $clientId) use ($db, $prefx): ?array {
    static $srcPrice = [];   // parsing_id => price_eur, one lookup per car

    if (!$car) {
        return null;
    }

    $pid = (int)($car['parsing_id'] ?? 0);
    $src = (string)($car['parsing_source'] ?? '');

    if ($pid <= 0 || !in_array($src, ['encar', 'openlane', 'ecarstrade', 'auto1', 'autotrader'], true)) {
        $prc = (int)($car['prc'] ?? 0);
        return $prc > 0 ? ['amount' => $prc, 'cur' => (string)($car['cur'] ?? '')] : null;
    }

    if (!array_key_exists($pid, $srcPrice)) {
        try {
            $q = $db->prepare('SELECT price_eur FROM '.$prefx.'_parsing_cars WHERE id = ? LIMIT 1');
            $q->execute([$pid]);
            $srcPrice[$pid] = (float)($q->fetchColumn() ?: 0);
        } catch (Throwable $e) {
            $srcPrice[$pid] = 0.0;
        }
    }
    if ($srcPrice[$pid] <= 0) {
        return null;
    }

    $bdCar = [
        'price_eur' => $srcPrice[$pid],
        'fuel'      => b2b_fuel_code($car['fl'] ?? null),
        'capacity'  => (int)($car['vol'] ?? 0),
        'year'      => (int)($car['yr'] ?? 0),
    ];
    $bd = parsing_md_breakdown_for($db, $prefx, $src, $bdCar, null, true, $clientId);

    return ($bd && !empty($bd['total']))
        ? ['amount' => (int)round($bd['total']), 'cur' => 'EUR']
        : null;
};
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
                        <td class="b2ba-td--title">
                            <a href="/<?= b2b_adm_esc($lang) ?>/<?= b2b_adm_esc($admin_dir) ?>/b2b/user?id=<?= (int)$req['b2b_user_id'] ?>"
                               class="b2ba-td--strong"><?= b2b_adm_esc(B2bAuth::displayName($req)) ?></a>
                            <div class="b2ba-td--small"><?= b2b_adm_esc($req['phone_number']) ?></div>
                        </td>
                        <td data-label="<?= b2b_adm_esc($t['col_car']) ?>">
                            <?php // ?b2b_as opens the car with this partner's pricing (admin-only preview). ?>
                            <a href="/<?= b2b_adm_esc($lang) ?>/ordercars/<?= (int)$req['car_id'] ?>?b2b_as=<?= (int)$req['b2b_user_id'] ?>" target="_blank" rel="noopener">
                                <?= b2b_adm_esc($car['title'] ?? ('#'.(int)$req['car_id'])) ?>
                            </a>
                            <?php // The price this partner saw — their own tables if any, global B2B otherwise. ?>
                            <?php $cp = $clientPrice($car, (int)$req['b2b_user_id']); ?>
                            <?php if ($cp): ?>
                                <div class="b2ba-td--small"><?= b2b_adm_esc(number_format($cp['amount'], 0, '.', ' ')) ?> <?= b2b_adm_esc($cp['cur']) ?></div>
                            <?php endif; ?>
                        </td>
                        <td class="b2ba-td--small" data-label="<?= b2b_adm_esc($t['col_invoice']) ?>">
                            <?php if (!empty($req['invoice_no'])): ?>
                                <a href="/<?= b2b_adm_esc($lang) ?>/b2b/invoice/<?= (int)$req['invoice_id'] ?>?k=<?= b2b_adm_esc($req['access_key']) ?>"
                                   target="_blank" rel="noopener"><?= b2b_adm_esc($req['invoice_no']) ?></a>
                                <div><?= b2b_adm_esc(number_format((float)$req['advance_amount'], 0, '.', '').' '.$req['currency']) ?></div>
                            <?php else: ?>
                                &mdash;
                            <?php endif; ?>
                        </td>
                        <td class="b2ba-td--status">
                            <span class="b2ba-badge b2ba-badge--<?= b2b_adm_esc($req['status']) ?>" data-b2b-req-badge="<?= $rid ?>">
                                <?= b2b_adm_esc($t['st_'.$req['status']] ?? $req['status']) ?>
                            </span>
                        </td>
                        <td class="b2ba-td--small" data-label="<?= b2b_adm_esc($t['col_date']) ?>"><?= b2b_adm_esc(date('d.m.Y', strtotime((string)$req['created_at']))) ?></td>
                        <td class="b2ba-td--nowrap b2ba-td--action">
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
