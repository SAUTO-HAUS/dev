<?php
defined('_DOIT') or die('Restricted access');

require_once($_SERVER['DOCUMENT_ROOT'] . '/content/default/404_logger.php');

$days = isset($_GET['days']) ? (int)$_GET['days'] : 7;
$stats = Error404Logger::getStats($days);
$recentLogs = Error404Logger::getRawLogs(50);

if (isset($_GET['cleanup']) && $_GET['cleanup'] == '1') {
    $deleted = Error404Logger::cleanup(30);
    $cleanup_message = "Deleted {$deleted} old entries.";
}
?>

<style>
.stats-container { padding: 20px; max-width: 1400px; }
.stats-header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px; }
.stats-header h1 { margin: 0; color: #333; }
.stats-controls { display: flex; gap: 10px; }
.stats-controls select, .stats-controls button { padding: 8px 16px; border: 1px solid #ddd; border-radius: 4px; cursor: pointer; }
.stats-controls button { background: #CE3226; color: #fff; border: none; }
.stats-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(180px, 1fr)); gap: 15px; margin-bottom: 25px; }
.stat-card { background: #fff; border: 1px solid #e0e0e0; border-radius: 8px; padding: 15px; text-align: center; }
.stat-card .number { font-size: 32px; font-weight: bold; color: #CE3226; }
.stat-card .label { color: #666; font-size: 13px; margin-top: 5px; }
.stats-section { background: #fff; border: 1px solid #e0e0e0; border-radius: 8px; padding: 15px; margin-bottom: 15px; }
.stats-section h3 { margin: 0 0 12px 0; color: #333; border-bottom: 2px solid #CE3226; padding-bottom: 8px; font-size: 16px; }
.stats-table { width: 100%; border-collapse: collapse; font-size: 13px; }
.stats-table th, .stats-table td { padding: 8px; text-align: left; border-bottom: 1px solid #eee; }
.stats-table th { background: #f5f5f5; font-weight: 600; }
.stats-table tr:hover { background: #fafafa; }
.url-cell { max-width: 350px; overflow: hidden; text-overflow: ellipsis; white-space: nowrap; font-family: monospace; font-size: 11px; }
.category-badge { display: inline-block; padding: 2px 6px; border-radius: 10px; font-size: 10px; font-weight: 500; }
.category-car_id { background: #e3f2fd; color: #1565c0; }
.category-car_old_format { background: #fff3e0; color: #e65100; }
.category-car_brand_model { background: #e8f5e9; color: #2e7d32; }
.category-ordercars { background: #f3e5f5; color: #7b1fa2; }
.category-tyres { background: #fce4ec; color: #c2185b; }
.category-static_file { background: #eceff1; color: #546e7a; }
.category-other { background: #f5f5f5; color: #757575; }
.bot-badge { display: inline-block; padding: 2px 5px; border-radius: 3px; font-size: 9px; }
.bot-badge.bot { background: #ffcdd2; color: #c62828; }
.bot-badge.user { background: #c8e6c9; color: #2e7d32; }
.chart-container { height: 150px; display: flex; align-items: flex-end; gap: 4px; padding: 15px 0; }
.chart-bar { flex: 1; background: #CE3226; border-radius: 3px 3px 0 0; position: relative; min-height: 4px; cursor: pointer; }
.chart-bar:hover { background: #a82820; }
.chart-bar .tooltip { position: absolute; bottom: 100%; left: 50%; transform: translateX(-50%); background: #333; color: #fff; padding: 3px 6px; border-radius: 3px; font-size: 10px; white-space: nowrap; display: none; }
.chart-bar:hover .tooltip { display: block; }
.chart-labels { display: flex; gap: 4px; }
.chart-labels span { flex: 1; text-align: center; font-size: 9px; color: #666; }
.alert { padding: 10px 15px; border-radius: 4px; margin-bottom: 15px; background: #d4edda; color: #155724; border: 1px solid #c3e6cb; }
.two-columns { display: grid; grid-template-columns: 1fr 1fr; gap: 15px; }
@media (max-width: 900px) { .two-columns { grid-template-columns: 1fr; } }
</style>

<div class="stats-container">
    <div class="stats-header">
        <h1>404 Error Statistics</h1>
        <div class="stats-controls">
            <select onchange="window.location.href='?days='+this.value">
                <option value="1" <?= $days == 1 ? 'selected' : '' ?>>Last day</option>
                <option value="7" <?= $days == 7 ? 'selected' : '' ?>>Last 7 days</option>
                <option value="14" <?= $days == 14 ? 'selected' : '' ?>>Last 14 days</option>
                <option value="30" <?= $days == 30 ? 'selected' : '' ?>>Last 30 days</option>
            </select>
            <button onclick="if(confirm('Delete logs older than 30 days?')) window.location.href='?cleanup=1&days=<?= $days ?>'">Cleanup</button>
        </div>
    </div>
    
    <?php if (isset($cleanup_message)): ?>
    <div class="alert"><?= $cleanup_message ?></div>
    <?php endif; ?>
    
    <?php if (isset($stats['error'])): ?>
    <div class="alert" style="background:#fff3cd;color:#856404;border-color:#ffeeba;">No data yet. Logs will appear after 404 errors occur.</div>
    <?php else: ?>
    
    <div class="stats-grid">
        <div class="stat-card">
            <div class="number"><?= number_format($stats['total_404'] ?? 0) ?></div>
            <div class="label">Total 404 (<?= $days ?> days)</div>
        </div>
        <div class="stat-card">
            <div class="number"><?= number_format($stats['bots_vs_users']['users'] ?? 0) ?></div>
            <div class="label">From users</div>
        </div>
        <div class="stat-card">
            <div class="number"><?= number_format($stats['bots_vs_users']['bots'] ?? 0) ?></div>
            <div class="label">From bots</div>
        </div>
        <div class="stat-card">
            <div class="number"><?= count($stats['top_urls'] ?? []) ?></div>
            <div class="label">Unique URLs</div>
        </div>
    </div>
    
    <?php if (!empty($stats['by_date'])): ?>
    <div class="stats-section">
        <h3>404 Errors by Day</h3>
        <?php $maxValue = max($stats['by_date']); ?>
        <div class="chart-container">
            <?php foreach ($stats['by_date'] as $date => $count): ?>
            <div class="chart-bar" style="height: <?= $maxValue > 0 ? ($count / $maxValue) * 100 : 0 ?>%">
                <div class="tooltip"><?= $date ?>: <?= $count ?></div>
            </div>
            <?php endforeach; ?>
        </div>
        <div class="chart-labels">
            <?php foreach ($stats['by_date'] as $date => $count): ?>
            <span><?= date('d/m', strtotime($date)) ?></span>
            <?php endforeach; ?>
        </div>
    </div>
    <?php endif; ?>
    
    <div class="two-columns">
        <div class="stats-section">
            <h3>By Category</h3>
            <table class="stats-table">
                <tr><th>Category</th><th>Count</th></tr>
                <?php foreach ($stats['by_category'] ?? [] as $cat => $count): ?>
                <tr>
                    <td><span class="category-badge category-<?= $cat ?>"><?= $cat ?></span></td>
                    <td><?= number_format($count) ?></td>
                </tr>
                <?php endforeach; ?>
            </table>
        </div>
        
        <div class="stats-section">
            <h3>Top Sources (Referers)</h3>
            <table class="stats-table">
                <tr><th>Domain</th><th>Count</th></tr>
                <?php foreach ($stats['top_referers'] ?? [] as $domain => $count): ?>
                <tr>
                    <td><?= htmlspecialchars($domain) ?></td>
                    <td><?= number_format($count) ?></td>
                </tr>
                <?php endforeach; ?>
            </table>
        </div>
    </div>
    
    <div class="stats-section">
        <h3>Top 404 URLs</h3>
        <table class="stats-table">
            <tr><th>URL</th><th>Count</th></tr>
            <?php foreach ($stats['top_urls'] ?? [] as $url => $count): ?>
            <tr>
                <td class="url-cell" title="<?= htmlspecialchars($url) ?>"><?= htmlspecialchars($url) ?></td>
                <td><?= number_format($count) ?></td>
            </tr>
            <?php endforeach; ?>
        </table>
    </div>
    
    <?php endif; ?>
    
    <div class="stats-section">
        <h3>Recent 404 Errors (last 50)</h3>
        <table class="stats-table">
            <tr><th>Time</th><th>URL</th><th>Category</th><th>Referer</th><th>Type</th></tr>
            <?php if (empty($recentLogs)): ?>
            <tr><td colspan="5" style="text-align:center;color:#666;">No logs yet</td></tr>
            <?php else: ?>
            <?php foreach ($recentLogs as $log): ?>
            <tr>
                <td style="white-space:nowrap;"><?= $log['timestamp'] ?? '-' ?></td>
                <td class="url-cell" title="<?= htmlspecialchars($log['url'] ?? '') ?>"><?= htmlspecialchars($log['url'] ?? '-') ?></td>
                <td><span class="category-badge category-<?= $log['url_category'] ?? 'other' ?>"><?= $log['url_category'] ?? '-' ?></span></td>
                <td class="url-cell" title="<?= htmlspecialchars($log['referer'] ?? '') ?>"><?= htmlspecialchars($log['referer_domain'] ?? 'direct') ?></td>
                <td><span class="bot-badge <?= ($log['is_bot'] ?? false) ? 'bot' : 'user' ?>"><?= ($log['is_bot'] ?? false) ? 'BOT' : 'USER' ?></span></td>
            </tr>
            <?php endforeach; ?>
            <?php endif; ?>
        </table>
    </div>
</div>
