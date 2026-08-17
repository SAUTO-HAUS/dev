<?php defined('_DOIT') or die('Restricted access');

/**
 * Which cars a B2B client has been looking at: /adminsauto/b2b/history?id=X
 *
 * Only car views (B2bAudit::PAGE_VIEW). The site records them through
 * b2b_log_car_view() and deduplicates over 30 minutes, so a row is a genuine
 * visit rather than a page refresh.
 *
 * A raw log is unreadable, so two things are done to it:
 *   - each entry is joined to gh3sp_car_ctlg, turning "target 8412" into the car,
 *     its price and the SOURCE it was imported from (Encar, OpenLane, ...);
 *   - cars looked at more than once are marked, because a repeat visit is the
 *     signal worth seeing — it is what separates interest from browsing.
 */

use App\Services\B2b\B2bAudit;
use App\Services\B2b\B2bAuth;
use App\Services\B2b\B2bConfig;

$lang = $_COOKIE['lang'] ?? 'ro';
$t    = b2b_adm_lang($lang);

$uid = (int)($_GET['id'] ?? 0);

$client = $uid > 0 ? B2bAuth::findById($uid) : null;
if (!$client) {
    echo '<div class="b2ba"><div class="b2ba-empty">404</div></div>';
    return;
}

$clientUrl = '/'.$lang.'/'.$admin_dir.'/b2b/user?id='.$uid;
$baseUrl   = '/'.$lang.'/'.$admin_dir.'/b2b/history?id='.$uid;

$perPage = 100;
$page    = max(1, (int)($_GET['p'] ?? 1));
$total   = B2bAudit::countForUser($uid, B2bAudit::PAGE_VIEW);
$pages   = max(1, (int)ceil($total / $perPage));
$page    = min($page, $pages);

$logs = B2bAudit::forUser($uid, $perPage, ($page - 1) * $perPage, B2bAudit::PAGE_VIEW);

// ---------------------------------------------------------------- summary
// Counted over the whole log, not the current page: these are facts about the
// client and must not change as you page through.
$pfx        = B2bConfig::prefix();
$lastLogin  = null;
$uniqCars   = 0;
$sourceTop  = [];

try {
    // The last login stays in the summary even though the list below is car
    // views only: "when was he last here" is the context for everything under it.
    $q = $db->prepare('SELECT MAX(created_at) FROM '.B2bConfig::table('activity_logs')
        .' WHERE b2b_user_id = :uid AND action_type = :a');
    $q->execute([':uid' => $uid, ':a' => B2bAudit::LOGIN]);
    $lastLogin = $q->fetchColumn() ?: null;

    // DISTINCT: the same car opened on three days is one car he is interested in.
    $q = $db->prepare('SELECT COUNT(DISTINCT target_id) FROM '.B2bConfig::table('activity_logs')
        .' WHERE b2b_user_id = :uid AND action_type = :a AND target_id IS NOT NULL');
    $q->execute([':uid' => $uid, ':a' => B2bAudit::PAGE_VIEW]);
    $uniqCars = (int)$q->fetchColumn();

    // Which source he actually shops in. Computed over the whole history, not
    // from the rows on screen, which would only cover the last 100.
    $q = $db->prepare(
        'SELECT COALESCE(NULLIF(c.parsing_source, ""), "stock") AS src, COUNT(DISTINCT l.target_id) AS n
           FROM '.B2bConfig::table('activity_logs').' l
           JOIN '.$pfx.'_car_ctlg c ON c.id = l.target_id
          WHERE l.b2b_user_id = :uid AND l.action_type = :a AND l.target_id IS NOT NULL
          GROUP BY src ORDER BY n DESC'
    );
    $q->execute([':uid' => $uid, ':a' => B2bAudit::PAGE_VIEW]);
    $sourceTop = $q->fetchAll(PDO::FETCH_ASSOC) ?: [];
} catch (Throwable $e) {
    // The summary is a nicety; the list below still renders.
}

// ------------------------------------------------- cars + how often each seen
$carIds = [];
foreach ($logs as $l) {
    $tid = (int)($l['target_id'] ?? 0);
    if ($tid > 0) { $carIds[$tid] = true; }
}

$cars   = [];
$visits = [];   // car id => total visits across the whole history
if ($carIds) {
    $ids = array_keys($carIds);
    $in  = implode(',', array_fill(0, count($ids), '?'));

    try {
        $q = $db->prepare('SELECT id, br_nm, mo_nm, yr, prc, parsing_source
                             FROM '.$pfx.'_car_ctlg WHERE id IN ('.$in.')');
        $q->execute($ids);
        foreach ($q->fetchAll(PDO::FETCH_ASSOC) as $c) {
            $cars[(int)$c['id']] = $c;
        }
    } catch (Throwable $e) {
        $cars = [];
    }

    try {
        $q = $db->prepare('SELECT target_id, COUNT(*) FROM '.B2bConfig::table('activity_logs')
            .' WHERE b2b_user_id = ? AND action_type = ? AND target_id IN ('.$in.')
               GROUP BY target_id');
        $q->execute(array_merge([$uid, B2bAudit::PAGE_VIEW], $ids));
        $visits = $q->fetchAll(PDO::FETCH_KEY_PAIR) ?: [];
    } catch (Throwable $e) {
        $visits = [];
    }
}

/** Import source as a label; own stock has its own wording. */
$srcLabel = function (?string $src) use ($t): string {
    $map = [
        'encar'      => 'Encar',
        'openlane'   => 'OpenLane',
        'ecarstrade' => 'eCarsTrade',
        'auto1'      => 'Auto1',
        'autotrader' => 'AutoTrader',
    ];
    $src = (string)$src;
    if ($src === '' || $src === 'stock') { return $t['hist_src_stock']; }
    return $map[$src] ?? $src;
};
?>

<div class="b2ba" data-b2b-user="<?= $uid ?>">
    <a class="b2ba-back" href="<?= b2b_adm_esc($clientUrl) ?>">&larr; <?= b2b_adm_esc(B2bAuth::displayName($client)) ?></a>

    <div class="b2ba-head">
        <h1 class="b2ba-h1">
            <?= b2b_adm_esc($t['hist_title']) ?>
            <span class="b2bp-who"><?= b2b_adm_esc(B2bAuth::displayName($client)) ?></span>
        </h1>
    </div>

    <!-- What the log adds up to, before reading any of it -->
    <div class="b2bh-sum">
        <div class="b2bh-sum__card">
            <span class="b2bh-sum__n"><?= $lastLogin ? b2b_adm_esc(date('d.m.Y', strtotime((string)$lastLogin))) : '—' ?></span>
            <span class="b2bh-sum__l"><?= b2b_adm_esc($t['hist_last_login']) ?></span>
            <?php if ($lastLogin): ?>
                <span class="b2bh-sum__x"><?= b2b_adm_esc(date('H:i', strtotime((string)$lastLogin))) ?></span>
            <?php endif; ?>
        </div>
        <div class="b2bh-sum__card">
            <span class="b2bh-sum__n"><?= (int)$uniqCars ?></span>
            <span class="b2bh-sum__l"><?= b2b_adm_esc($t['hist_cars_seen']) ?></span>
        </div>
        <div class="b2bh-sum__card b2bh-sum__card--wide">
            <span class="b2bh-sum__l"><?= b2b_adm_esc($t['hist_sources']) ?></span>
            <?php if (!$sourceTop): ?>
                <span class="b2bh-sum__x">—</span>
            <?php else: ?>
                <div class="b2bh-srcs">
                    <?php foreach (array_slice($sourceTop, 0, 5) as $s): ?>
                        <span class="b2bh-src">
                            <?= b2b_adm_esc($srcLabel($s['src'])) ?>
                            <b><?= (int)$s['n'] ?></b>
                        </span>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <?php if (!$logs): ?>
        <div class="b2ba-empty"><?= b2b_adm_esc($t['hist_none']) ?></div>
    <?php else: ?>
        <div class="b2ba-table-wrap">
            <table class="b2ba-table b2bh-table">
                <thead>
                    <tr>
                        <th><?= b2b_adm_esc($t['hist_when']) ?></th>
                        <th><?= b2b_adm_esc($t['hist_car']) ?></th>
                        <th><?= b2b_adm_esc($t['hist_price']) ?></th>
                        <th><?= b2b_adm_esc($t['hist_source']) ?></th>
                    </tr>
                </thead>
                <tbody>
                <?php
                $prevDay = '';
                foreach ($logs as $l):
                    $ts    = strtotime((string)$l['created_at']);
                    $day   = date('d.m.Y', $ts);
                    $carId = (int)($l['target_id'] ?? 0);
                    $car   = $cars[$carId] ?? null;
                    $seen  = (int)($visits[$carId] ?? 0);

                    // A date header each time the day changes: the eye finds
                    // "what did he look at on the 5th" without reading every row.
                    if ($day !== $prevDay):
                        $prevDay = $day;
                ?>
                    <tr class="b2bh-day"><td colspan="4"><?= b2b_adm_esc($day) ?></td></tr>
                <?php endif; ?>
                    <tr class="b2bh-row">
                        <td class="b2bh-time b2bh-c-time" data-label="<?= b2b_adm_esc($t['hist_when']) ?>"><?= b2b_adm_esc(date('H:i', $ts)) ?></td>
                        <td class="b2bh-c-car" data-label="<?= b2b_adm_esc($t['hist_car']) ?>">
                            <?php if ($car): ?>
                                <a class="b2bh-car" href="/<?= b2b_adm_esc($lang) ?>/ordercars/<?= $carId ?>" target="_blank" rel="noopener">
                                    <?= b2b_adm_esc(trim(($car['br_nm'] ?? '').' '.($car['mo_nm'] ?? ''))) ?>
                                    <?php if (!empty($car['yr'])): ?><span class="b2bh-yr"><?= (int)$car['yr'] ?></span><?php endif; ?>
                                </a>
                                <?php if ($seen > 1): ?>
                                    <?php // Came back to it — the entry worth noticing. ?>
                                    <span class="b2bh-again" title="<?= b2b_adm_esc($t['hist_times']) ?>">&times;<?= $seen ?></span>
                                <?php endif; ?>
                            <?php else: ?>
                                <?php // Car deleted since; the id is all that survives. ?>
                                <span class="b2bh-gone">#<?= $carId ?></span>
                            <?php endif; ?>
                        </td>
                        <td class="b2bh-c-prc" data-label="<?= b2b_adm_esc($t['hist_price']) ?>">
                            <?php if ($car && (int)($car['prc'] ?? 0) > 0): ?>
                                <span class="b2bh-prc"><?= number_format((float)$car['prc'], 0, '.', ' ') ?> &euro;</span>
                            <?php endif; ?>
                        </td>
                        <td class="b2bh-c-src" data-label="<?= b2b_adm_esc($t['hist_source']) ?>">
                            <?php if ($car): ?>
                                <span class="b2bh-src"><?= b2b_adm_esc($srcLabel($car['parsing_source'] ?? '')) ?></span>
                            <?php endif; ?>
                        </td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>

        <?php if ($pages > 1): ?>
            <div class="b2bh-pager">
                <?php if ($page > 1): ?>
                    <a class="b2ba-btn b2ba-btn--soft b2ba-btn--sm" href="<?= b2b_adm_esc($baseUrl.($page - 1 > 1 ? '&p='.($page - 1) : '')) ?>">&larr;</a>
                <?php endif; ?>
                <span class="b2bh-pager__n"><?= $page ?> / <?= $pages ?></span>
                <?php if ($page < $pages): ?>
                    <a class="b2ba-btn b2ba-btn--soft b2ba-btn--sm" href="<?= b2b_adm_esc($baseUrl.'&p='.($page + 1)) ?>">&rarr;</a>
                <?php endif; ?>
            </div>
        <?php endif; ?>
    <?php endif; ?>
</div>
