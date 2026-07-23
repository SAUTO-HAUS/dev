<?php defined('_DOIT') or die('Restricted access');

/**
 * Region traffic report: how many people open each /ordercars/{region} landing
 * page. Data is written by content/site/include/region_stats.php.
 */

$viz_days = isset($_GET['days']) ? (int)$_GET['days'] : 30;
if (!in_array($viz_days, [1, 7, 30, 90, 365], true)) { $viz_days = 30; }

$viz_regions = [
    'korea'  => ['ro' => 'Coreea', 'ru' => 'Корея', 'en' => 'Korea',  'img' => 'south-korea-fl.png'],
    'europe' => ['ro' => 'Europa', 'ru' => 'Европа', 'en' => 'Europe', 'img' => 'european-fl.png'],
    'usa'    => ['ro' => 'SUA',    'ru' => 'США',    'en' => 'USA',    'img' => 'united-states-fl.png'],
    'china'  => ['ro' => 'China',  'ru' => 'Китай',  'en' => 'China',  'img' => 'china.png'],
];

$viz_lang = $_COOKIE['lang'] ?? 'ro';
$viz_rows = [];
$viz_err  = '';
$viz_daily = [];

try {
    // Interval is an int from a whitelist, so it can be inlined (native prepares
    // do not accept a placeholder inside INTERVAL).
    $stmt = $db->query('SELECT `region`,
               COUNT(*) AS views,
               COUNT(DISTINCT `visitor`) AS visitors
        FROM '.$prefx.'_region_views
        WHERE `created_at` >= DATE_SUB(NOW(), INTERVAL '.$viz_days.' DAY)
        GROUP BY `region`');
    foreach ($stmt as $r) {
        $viz_rows[$r['region']] = ['views' => (int)$r['views'], 'visitors' => (int)$r['visitors']];
    }

    // Daily series for the mini chart.
    $d = $db->query('SELECT DATE(`created_at`) AS d, `region`, COUNT(*) AS n
        FROM '.$prefx.'_region_views
        WHERE `created_at` >= DATE_SUB(NOW(), INTERVAL '.$viz_days.' DAY)
        GROUP BY DATE(`created_at`), `region` ORDER BY d ASC');
    foreach ($d as $r) { $viz_daily[$r['d']][$r['region']] = (int)$r['n']; }
} catch (Throwable $e) {
    $viz_err = $e->getMessage();
}

$viz_total_views    = array_sum(array_column($viz_rows, 'views'));
$viz_total_visitors = array_sum(array_column($viz_rows, 'visitors'));

// Sorted by visitors, so the most wanted region is first.
$viz_sorted = $viz_regions;
uksort($viz_sorted, function ($a, $b) use ($viz_rows) {
    return ($viz_rows[$b]['visitors'] ?? 0) <=> ($viz_rows[$a]['visitors'] ?? 0);
});

$viz_t = [
    'ro' => ['title' => 'Vizualizări pe regiuni', 'views' => 'vizualizări', 'visitors' => 'vizitatori',
             'share' => 'cotă', 'total' => 'Total', 'nodata' => 'Încă nu există date.',
             'period' => 'Perioadă', 'd1' => 'Azi', 'd7' => '7 zile', 'd30' => '30 zile',
             'd90' => '90 zile', 'd365' => 'Un an', 'hint' => 'O vizită se numără o dată la 30 de minute per vizitator. Boții sunt excluși.',
             'daily' => 'Pe zile', 'nomigr' => 'Tabelul nu există încă. Rulează sql_scripts/create_region_views_table.sql.'],
    'ru' => ['title' => 'Просмотры по регионам', 'views' => 'просмотров', 'visitors' => 'посетителей',
             'share' => 'доля', 'total' => 'Всего', 'nodata' => 'Данных пока нет.',
             'period' => 'Период', 'd1' => 'Сегодня', 'd7' => '7 дней', 'd30' => '30 дней',
             'd90' => '90 дней', 'd365' => 'Год', 'hint' => 'Визит считается раз в 30 минут на посетителя. Боты исключены.',
             'daily' => 'По дням', 'nomigr' => 'Таблица ещё не создана. Выполните sql_scripts/create_region_views_table.sql.'],
    'en' => ['title' => 'Region views', 'views' => 'views', 'visitors' => 'visitors',
             'share' => 'share', 'total' => 'Total', 'nodata' => 'No data yet.',
             'period' => 'Period', 'd1' => 'Today', 'd7' => '7 days', 'd30' => '30 days',
             'd90' => '90 days', 'd365' => 'One year', 'hint' => 'One visit per visitor per 30 minutes. Bots excluded.',
             'daily' => 'Daily', 'nomigr' => 'Table not created yet. Run sql_scripts/create_region_views_table.sql.'],
];
$T = $viz_t[$viz_lang] ?? $viz_t['ro'];
?>

<style>
#viz { padding: 15px 0; }
#viz .viz-hdr { display: flex; justify-content: space-between; align-items: center; gap: 10px; flex-wrap: wrap; margin-bottom: 18px; }
#viz .viz-hdr h2 { margin: 0; font-size: 18px; color: #333; }
#viz .viz-per { display: flex; gap: 6px; flex-wrap: wrap; }
#viz .viz-per a { padding: 6px 12px; border: 1px solid #ddd; border-radius: 6px; background: #fff; color: #555; text-decoration: none; font-size: 13px; }
#viz .viz-per a.act { background: #CE3226; border-color: #CE3226; color: #fff; }
#viz .viz-hint { font-size: 12px; color: #888; margin: -8px 0 16px; }
#viz .viz-warn { background: #fff6f6; border: 1px solid #f0c8c8; color: #a30015; padding: 12px 15px; border-radius: 6px; margin-bottom: 16px; font-size: 13px; }

#viz .viz-cards { display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 14px; margin-bottom: 22px; }
#viz .viz-card { background: #fff; border: 1px solid #ddd; border-radius: 8px; padding: 16px; }
#viz .viz-card .top { display: flex; align-items: center; gap: 10px; margin-bottom: 10px; }
#viz .viz-card img { width: 34px; height: 34px; object-fit: contain; }
#viz .viz-card .nm { font-size: 15px; font-weight: 600; color: #333; }
#viz .viz-card .num { font-size: 30px; font-weight: 700; color: #CE3226; line-height: 1; }
#viz .viz-card .sub { font-size: 12px; color: #777; margin-top: 6px; }
#viz .viz-bar { height: 6px; background: #f0f0f0; border-radius: 3px; overflow: hidden; margin-top: 12px; }
#viz .viz-bar > i { display: block; height: 100%; background: #CE3226; }
#viz .viz-pct { font-size: 12px; color: #666; margin-top: 6px; }

#viz .viz-box { background: #fff; border: 1px solid #ddd; border-radius: 8px; }
#viz .viz-box-hdr { padding: 12px 15px; border-bottom: 1px solid #eee; font-weight: 600; font-size: 14px; color: #333; }
#viz table { width: 100%; border-collapse: collapse; font-size: 13px; }
#viz th, #viz td { padding: 9px 15px; text-align: left; border-bottom: 1px solid #f2f2f2; }
#viz th { background: #fafafa; color: #666; font-weight: 600; }
#viz td.n { text-align: right; font-variant-numeric: tabular-nums; }
#viz tr:last-child td { border-bottom: none; }
</style>

<div id="viz">
    <div class="viz-hdr">
        <h2><?= htmlspecialchars($T['title']) ?></h2>
        <div class="viz-per">
            <span style="align-self:center;font-size:12px;color:#888;"><?= htmlspecialchars($T['period']) ?>:</span>
            <?php foreach ([1 => 'd1', 7 => 'd7', 30 => 'd30', 90 => 'd90', 365 => 'd365'] as $dv => $dk): ?>
                <a href="?days=<?= $dv ?>" class="<?= $viz_days === $dv ? 'act' : '' ?>"><?= htmlspecialchars($T[$dk]) ?></a>
            <?php endforeach; ?>
        </div>
    </div>

    <div class="viz-hint"><?= htmlspecialchars($T['hint']) ?></div>

    <?php if ($viz_err !== ''): ?>
        <div class="viz-warn"><?= htmlspecialchars($T['nomigr']) ?></div>
    <?php endif; ?>

    <div class="viz-cards">
        <?php foreach ($viz_sorted as $rk => $rv):
            $views    = $viz_rows[$rk]['views']    ?? 0;
            $visitors = $viz_rows[$rk]['visitors'] ?? 0;
            $pct      = $viz_total_visitors > 0 ? round($visitors * 100 / $viz_total_visitors) : 0;
        ?>
        <div class="viz-card">
            <div class="top">
                <img src="/content/admin/page/parsing/media-parsing/<?= htmlspecialchars($rv['img']) ?>" alt="">
                <span class="nm"><?= htmlspecialchars($rv[$viz_lang] ?? $rv['ro']) ?></span>
            </div>
            <div class="num"><?= number_format($visitors, 0, '.', ' ') ?></div>
            <div class="sub"><?= htmlspecialchars($T['visitors']) ?> · <?= number_format($views, 0, '.', ' ') ?> <?= htmlspecialchars($T['views']) ?></div>
            <div class="viz-bar"><i style="width:<?= $pct ?>%"></i></div>
            <div class="viz-pct"><?= $pct ?>% <?= htmlspecialchars($T['share']) ?></div>
        </div>
        <?php endforeach; ?>
    </div>

    <div class="viz-box">
        <div class="viz-box-hdr"><?= htmlspecialchars($T['daily']) ?></div>
        <?php if (!$viz_daily): ?>
            <div style="padding:20px 15px;color:#999;font-size:13px;"><?= htmlspecialchars($T['nodata']) ?></div>
        <?php else: ?>
        <table>
            <tr>
                <th><?= htmlspecialchars($T['period']) ?></th>
                <?php foreach ($viz_regions as $rk => $rv): ?>
                    <th style="text-align:right;"><?= htmlspecialchars($rv[$viz_lang] ?? $rv['ro']) ?></th>
                <?php endforeach; ?>
                <th style="text-align:right;"><?= htmlspecialchars($T['total']) ?></th>
            </tr>
            <?php foreach (array_reverse($viz_daily, true) as $day => $per): ?>
            <tr>
                <td><?= htmlspecialchars($day) ?></td>
                <?php foreach ($viz_regions as $rk => $rv): ?>
                    <td class="n"><?= (int)($per[$rk] ?? 0) ?></td>
                <?php endforeach; ?>
                <td class="n"><strong><?= array_sum($per) ?></strong></td>
            </tr>
            <?php endforeach; ?>
        </table>
        <?php endif; ?>
    </div>
</div>
