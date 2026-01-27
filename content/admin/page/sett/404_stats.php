<?php
defined('_DOIT') or die('Restricted access');

require_once($_SERVER['DOCUMENT_ROOT'] . '/content/default/404_logger.php');

$days = isset($_GET['days']) ? (int)$_GET['days'] : 7;
$stats = Error404Logger::getStats($days);
$recentLogs = Error404Logger::getRawLogs(50);

if (isset($_GET['cleanup']) && $_GET['cleanup'] == '1') {
    $deleted = Error404Logger::cleanup(30);
    $cleanup_message = "Удалено {$deleted} старых записей.";
}

if (isset($_GET['reset']) && $_GET['reset'] == '1') {
    $logFile = $_SERVER['DOCUMENT_ROOT'] . '/logs/404_errors.json';
    if (file_exists($logFile)) {
        file_put_contents($logFile, '[]');
    }
    exit;
}

$categoryNames = [
    'car_id' => 'Авто по ID',
    'car_old_format' => 'Авто (старый формат)',
    'car_brand_model' => 'Авто марка-модель',
    'ordercars' => 'Авто под заказ',
    'tyres' => 'Шины',
    'static_file' => 'Статические файлы',
    'api' => 'API',
    'other' => 'Другое'
];
?>

<style>
.s404 { padding: 15px 0; }
.s404-hdr { display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px; flex-wrap: wrap; gap: 10px; }
.s404-hdr h2 { margin: 0; font-size: 18px; color: #333; }
.s404-ctrl { display: flex; gap: 8px; align-items: center; }
.s404-ctrl select { padding: 6px 10px; border: 1px solid #ccc; border-radius: 4px; font-size: 13px; }
.s404-ctrl .btn { padding: 6px 12px; background: var(--clr, #CE3226); color: #fff; border: none; border-radius: 4px; cursor: pointer; font-size: 13px; }
.s404-ctrl .btn:hover { opacity: 0.9; }

.s404-cards { display: flex; gap: 15px; margin-bottom: 20px; flex-wrap: wrap; }
.s404-card { background: #fff; border: 1px solid #ddd; border-radius: 6px; padding: 15px 20px; min-width: 140px; text-align: center; }
.s404-card .num { font-size: 28px; font-weight: 700; color: var(--clr, #CE3226); }
.s404-card .lbl { font-size: 12px; color: #666; margin-top: 4px; }

.s404-box { background: #fff; border: 1px solid #ddd; border-radius: 6px; margin-bottom: 15px; }
.s404-box-hdr { padding: 12px 15px; border-bottom: 1px solid #eee; font-weight: 600; font-size: 14px; color: #333; }
.s404-box-body { padding: 15px; }

.s404-tbl { width: 100%; border-collapse: collapse; font-size: 13px; }
.s404-tbl th { text-align: left; padding: 8px 10px; background: #f8f8f8; font-weight: 600; border-bottom: 1px solid #ddd; }
.s404-tbl td { padding: 8px 10px; border-bottom: 1px solid #eee; }
.s404-tbl tr:hover td { background: #fafafa; }
.s404-tbl .url { max-width: 300px; overflow: hidden; text-overflow: ellipsis; white-space: nowrap; font-family: monospace; font-size: 12px; }
.s404-tbl .cnt { text-align: right; }
.s404-tbl td.cnt span { display: inline-block; background: #CE3226; color: #fff; padding: 2px 8px; border-radius: 4px; font-weight: 600; }

.s404-tag { display: inline-block; padding: 2px 8px; border-radius: 10px; font-size: 11px; }
.s404-tag.car_id { background: #e3f2fd; color: #1565c0; }
.s404-tag.car_old_format { background: #fff3e0; color: #e65100; }
.s404-tag.car_brand_model { background: #e8f5e9; color: #2e7d32; }
.s404-tag.ordercars { background: #f3e5f5; color: #7b1fa2; }
.s404-tag.tyres { background: #fce4ec; color: #c2185b; }
.s404-tag.static_file { background: #eceff1; color: #546e7a; }
.s404-tag.api { background: #fff8e1; color: #f57f17; }
.s404-tag.other { background: #f5f5f5; color: #757575; }

.s404-tag.bot { background: #ffcdd2; color: #c62828; }
.s404-tag.user { background: #c8e6c9; color: #2e7d32; }

.s404-row { display: flex; gap: 15px; }
.s404-row > div { flex: 1; }
@media (max-width: 800px) { .s404-row { flex-direction: column; } }

.s404-chart { height: 120px; display: flex; align-items: flex-end; gap: 3px; margin-bottom: 5px; }
.s404-bar { flex: 1; background: var(--clr, #CE3226); border-radius: 2px 2px 0 0; min-height: 3px; position: relative; }
.s404-bar:hover { opacity: 0.8; }
.s404-bar span { position: absolute; bottom: 100%; left: 50%; transform: translateX(-50%); background: #333; color: #fff; padding: 2px 5px; border-radius: 3px; font-size: 10px; white-space: nowrap; display: none; }
.s404-bar:hover span { display: block; }
.s404-dates { display: flex; gap: 3px; }
.s404-dates div { flex: 1; text-align: center; font-size: 10px; color: #999; }

.s404-msg { padding: 10px 15px; background: #d4edda; color: #155724; border-radius: 4px; margin-bottom: 15px; }
.s404-empty { text-align: center; padding: 30px; color: #999; }
</style>

<div class="s404">
    <div class="s404-hdr">
        <h2>Статистика ошибок 404</h2>
        <div class="s404-ctrl">
            <select onchange="window.location.href='?days='+this.value">
                <option value="1" <?= $days == 1 ? 'selected' : '' ?>>За день</option>
                <option value="7" <?= $days == 7 ? 'selected' : '' ?>>За 7 дней</option>
                <option value="14" <?= $days == 14 ? 'selected' : '' ?>>За 14 дней</option>
                <option value="30" <?= $days == 30 ? 'selected' : '' ?>>За 30 дней</option>
            </select>
            <button class="btn" onclick="if(confirm('Удалить ВСЕ записи?')) { fetch('?reset=1').then(()=>location.reload()); }">Сбросить</button>
        </div>
    </div>
    
    <?php if (isset($cleanup_message)): ?>
    <div class="s404-msg"><?= $cleanup_message ?></div>
    <?php endif; ?>
    
    <?php if (isset($stats['error'])): ?>
    <div class="s404-empty">Данных пока нет. Логи появятся после возникновения ошибок 404.</div>
    <?php else: ?>
    
    <div class="s404-cards">
        <div class="s404-card">
            <div class="num"><?= number_format($stats['total_404'] ?? 0) ?></div>
            <div class="lbl">Всего 404</div>
        </div>
        <div class="s404-card">
            <div class="num"><?= number_format($stats['bots_vs_users']['users'] ?? 0) ?></div>
            <div class="lbl">От пользователей</div>
        </div>
        <div class="s404-card">
            <div class="num"><?= number_format($stats['bots_vs_users']['bots'] ?? 0) ?></div>
            <div class="lbl">От ботов</div>
        </div>
        <div class="s404-card">
            <div class="num"><?= count($stats['top_urls'] ?? []) ?></div>
            <div class="lbl">Уникальных URL</div>
        </div>
    </div>
    
    <div class="s404-row">
        <?php if (!empty($stats['by_date'])): ?>
        <div class="s404-box">
            <div class="s404-box-hdr">Ошибки по дням</div>
            <div class="s404-box-body">
                <table class="s404-tbl">
                    <tr><th>Дата</th><th class="cnt">Кол-во</th></tr>
                    <?php foreach ($stats['by_date'] as $date => $count): ?>
                    <tr>
                        <td><?= date('d.m.Y', strtotime($date)) ?></td>
                        <td class="cnt"><span><?= number_format($count) ?></span></td>
                    </tr>
                    <?php endforeach; ?>
                </table>
            </div>
        </div>
        <?php endif; ?>
        
        <div class="s404-box">
            <div class="s404-box-hdr">По категориям</div>
            <div class="s404-box-body">
                <table class="s404-tbl">
                    <tr><th>Категория</th><th class="cnt">Кол-во</th></tr>
                    <?php foreach ($stats['by_category'] ?? [] as $cat => $count): ?>
                    <tr>
                        <td><span class="s404-tag <?= $cat ?>"><?= $categoryNames[$cat] ?? $cat ?></span></td>
                        <td class="cnt"><span><?= number_format($count) ?></span></td>
                    </tr>
                    <?php endforeach; ?>
                </table>
            </div>
        </div>
    </div>
    
    <div class="s404-box">
        <div class="s404-box-hdr">Топ URL с ошибкой 404</div>
        <div class="s404-box-body">
            <table class="s404-tbl">
                <tr><th>URL</th><th class="cnt">Кол-во</th></tr>
                <?php foreach ($stats['top_urls'] ?? [] as $url => $count): ?>
                <tr>
                    <td class="url" title="<?= htmlspecialchars($url) ?>"><?= htmlspecialchars($url) ?></td>
                    <td class="cnt"><span><?= number_format($count) ?></span></td>
                </tr>
                <?php endforeach; ?>
            </table>
        </div>
    </div>
    
    <?php endif; ?>
    
    <div class="s404-box">
        <div class="s404-box-hdr">Последние ошибки 404</div>
        <div class="s404-box-body">
            <table class="s404-tbl">
                <tr><th>URL</th><th>Источник</th><th>Категория</th><th class="cnt">Кол-во</th><th>Последний доступ</th><th>Тип</th></tr>
                <?php if (empty($recentLogs)): ?>
                <tr><td colspan="6" class="s404-empty">Логов пока нет</td></tr>
                <?php else: ?>
                <?php foreach ($recentLogs as $log): ?>
                <?php 
                $referer = $log['referer'] ?? '';
                $refererSource = $log['referer_source'] ?? ($log['referer_domain'] ?? 'direct');
                if (empty($refererSource)) $refererSource = 'direct';
                ?>
                <tr>
                    <td class="url" title="<?= htmlspecialchars($log['url'] ?? '') ?>"><?= htmlspecialchars($log['url'] ?? '-') ?></td>
                    <td title="<?= htmlspecialchars($referer) ?>"><?= htmlspecialchars($refererSource) ?></td>
                    <td><span class="s404-tag <?= $log['url_category'] ?? 'other' ?>"><?= $categoryNames[$log['url_category'] ?? 'other'] ?? $log['url_category'] ?></span></td>
                    <td class="cnt"><span><?= $log['count'] ?? 1 ?></span></td>
                    <td style="white-space:nowrap;font-size:12px;"><?= $log['last_access'] ?? $log['timestamp'] ?? '-' ?></td>
                    <td><span class="s404-tag <?= ($log['is_bot'] ?? false) ? 'bot' : 'user' ?>"><?= ($log['is_bot'] ?? false) ? 'БОТ' : 'ЮЗЕР' ?></span></td>
                </tr>
                <?php endforeach; ?>
                <?php endif; ?>
            </table>
        </div>
    </div>
</div>
