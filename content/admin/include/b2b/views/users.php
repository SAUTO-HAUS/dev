<?php defined('_DOIT') or die('Restricted access');

/** B2B client list with status filters (spec 3.1, account approval). */

use App\Services\B2b\B2bAuth;
use App\Services\B2b\B2bConfig;
use App\Services\B2b\B2bRegions;

$lang = $_COOKIE['lang'] ?? 'ro';
$t    = b2b_adm_lang($lang);

$statuses = ['pending', 'active', 'blocked'];
$filter   = isset($_GET['status']) && in_array($_GET['status'], $statuses, true) ? $_GET['status'] : '';
$search   = trim((string)($_GET['q'] ?? ''));

// Per-status counts for the filter badges.
$counts = ['' => 0, 'pending' => 0, 'active' => 0, 'blocked' => 0];
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
$sql .= ' ORDER BY FIELD(status, "pending", "active", "blocked"), id DESC LIMIT 500';

$users = [];
try {
    $stmt = $db->prepare($sql);
    $stmt->execute($args);
    $users = $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
} catch (Throwable $e) {
    $users = [];
}

$baseUrl = '/'.$lang.'/'.$admin_dir.'/b2b/users';
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

    <nav class="b2ba-filters">
        <?php
        $filterLabels = ['' => $t['filter_all'], 'pending' => $t['st_pending'], 'active' => $t['st_active'], 'blocked' => $t['st_blocked']];
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
                        <th><?= b2b_adm_esc($t['col_login']) ?></th>
                        <th><?= b2b_adm_esc($t['col_name']) ?></th>
                        <th><?= b2b_adm_esc($t['col_person_type']) ?></th>
                        <th><?= b2b_adm_esc($t['col_contact']) ?></th>
                        <th><?= b2b_adm_esc($t['col_regions']) ?></th>
                        <th><?= b2b_adm_esc($t['col_status']) ?></th>
                        <th><?= b2b_adm_esc($t['col_last_login']) ?></th>
                        <th></th>
                    </tr>
                </thead>
                <tbody>
                <?php foreach ($users as $u):
                    $uid     = (int)$u['id'];
                    $regions = B2bRegions::allowed($uid);
                ?>
                    <tr>
                        <td class="b2ba-td--strong"><?= b2b_adm_esc($u['login']) ?></td>
                        <td><?= b2b_adm_esc(B2bAuth::displayName($u)) ?></td>
                        <td><?= b2b_adm_esc($t['pt_'.$u['person_type']] ?? $u['person_type']) ?></td>
                        <td class="b2ba-td--small">
                            <?= b2b_adm_esc($u['email']) ?><br>
                            <?= b2b_adm_esc($u['phone_number']) ?>
                        </td>
                        <td>
                            <?php if ($regions): ?>
                                <?php foreach ($regions as $rg): ?>
                                    <span class="b2ba-chip"><?= b2b_adm_esc($t['rg_'.$rg] ?? $rg) ?></span>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <span class="b2ba-chip b2ba-chip--muted">&mdash;</span>
                            <?php endif; ?>
                        </td>
                        <td>
                            <span class="b2ba-badge b2ba-badge--<?= b2b_adm_esc($u['status']) ?>">
                                <?= b2b_adm_esc($t['st_'.$u['status']] ?? $u['status']) ?>
                            </span>
                        </td>
                        <td class="b2ba-td--small">
                            <?= $u['last_login_at']
                                ? b2b_adm_esc(date('d.m.Y H:i', strtotime((string)$u['last_login_at'])).' · '.($u['last_login_ip'] ?? ''))
                                : b2b_adm_esc($t['never']) ?>
                        </td>
                        <td>
                            <a class="b2ba-btn b2ba-btn--ghost b2ba-btn--sm"
                               href="/<?= b2b_adm_esc($lang) ?>/<?= b2b_adm_esc($admin_dir) ?>/b2b/user?id=<?= $uid ?>">
                                <?= b2b_adm_esc($t['open']) ?>
                            </a>
                        </td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php endif; ?>
</div>
