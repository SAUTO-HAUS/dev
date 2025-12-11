<?php
/**
 * Facebook Data Feeds Generator - Main Cron Script
 * 
 * This script generates all 3 Facebook/Google Shopping XML feeds:
 * 1. Main catalog (in-stock, main location only)
 * 2. Pruncul branch catalog (in-stock, Pruncul location only)
 * 3. Orders catalog (on_order with active timer)
 * 
 * Logs all changes to database for monitoring.
 * 
 * Usage: php plugins/dev_tools/generate_data_feeds.php
 * Cron: 0 4 * * * cd /home/sautom/public_html/plugins/dev_tools && /usr/local/bin/php generate_data_feeds.php >> /home/sautom/public_html/api/data_feed/cron.log 2>&1
 * 
 * @author SAUTO Development Team
 * @created 2025-11-21
 */

// Set timezone
date_default_timezone_set('Europe/Chisinau');

// Security constant
define('_DOIT', 1);

// Start time for total execution tracking
$scriptStartTime = microtime(true);

echo "================================================================================\n";
echo "Facebook Data Feeds Generation Started\n";
echo "Time: " . date('Y-m-d H:i:s') . "\n";
echo "================================================================================\n\n";

try {
    // Load dependencies
    require_once __DIR__ . '/../../content/default/defines.php';
    require_once __DIR__ . '/../../content/default/functions.php';
    require_once __DIR__ . '/../../environment.php';
    require_once __DIR__ . '/../../content/default/config.php';
    require_once __DIR__ . '/../../content/default/dbi.php';
    require_once __DIR__ . '/../../content/default/language.php';
    require_once __DIR__ . '/DataFeedGenerator.php';
    require_once __DIR__ . '/../../plugins/lalit/Constants.php';
    require_once __DIR__ . '/../../plugins/lalit/InitTrait.php';
    require_once __DIR__ . '/../../plugins/lalit/Array2XML.php';
    
    // ============================================================================
    // FEED 1: Main Catalog (in-stock, main location, no orders)
    // ============================================================================
    echo "📦 [1/3] Generating Main Catalog Feed...\n";
    $feedStartTime = microtime(true);
    
    try {
        $mainGenerator = new DataFeedGenerator(
            $db,
            $prefx,
            $lng,
            'main',
            __DIR__ . '/../../api/data_feed/df_cars.xml'
        );
        
        $mainStats = $mainGenerator->generate();
        $mainGenerator->logToDatabase($mainStats, 'success');
        
        echo "   ✅ Main catalog generated successfully\n";
        echo "   📊 Added: {$mainStats['added']} | Removed: {$mainStats['removed']} | Total: {$mainStats['total']}\n";
        echo "   ⏱️  Execution time: {$mainStats['execution_time']}s\n\n";
        
    } catch (Exception $e) {
        echo "   ❌ ERROR generating main catalog: " . $e->getMessage() . "\n\n";
        $mainGenerator->logToDatabase(['added' => 0, 'removed' => 0, 'total' => 0, 'execution_time' => 0], 'error', $e->getMessage());
    }
    
    // ============================================================================
    // FEED 2: Pruncul Branch Catalog (in-stock, Pruncul location, no orders)
    // ============================================================================
    echo "📦 [2/3] Generating Pruncul Branch Catalog Feed...\n";
    $feedStartTime = microtime(true);
    
    try {
        $pruncklGenerator = new DataFeedGenerator(
            $db,
            $prefx,
            $lng,
            'pruncul',
            __DIR__ . '/../../api/data_feed/df_cars_pruncul.xml'
        );
        
        $pruncklStats = $pruncklGenerator->generate();
        $pruncklGenerator->logToDatabase($pruncklStats, 'success');
        
        echo "   ✅ Pruncul catalog generated successfully\n";
        echo "   📊 Added: {$pruncklStats['added']} | Removed: {$pruncklStats['removed']} | Total: {$pruncklStats['total']}\n";
        echo "   ⏱️  Execution time: {$pruncklStats['execution_time']}s\n\n";
        
    } catch (Exception $e) {
        echo "   ❌ ERROR generating Pruncul catalog: " . $e->getMessage() . "\n\n";
        $pruncklGenerator->logToDatabase(['added' => 0, 'removed' => 0, 'total' => 0, 'execution_time' => 0], 'error', $e->getMessage());
    }
    
    // ============================================================================
    // FEED 3: Orders Catalog (on_order with active timer, all locations)
    // ============================================================================
    echo "📦 [3/3] Generating Orders Catalog Feed...\n";
    $feedStartTime = microtime(true);
    
    try {
        $ordersGenerator = new DataFeedGenerator(
            $db,
            $prefx,
            $lng,
            'orders',
            __DIR__ . '/../../api/data_feed/df_cars_orders.xml'
        );
        
        $ordersStats = $ordersGenerator->generate();
        $ordersGenerator->logToDatabase($ordersStats, 'success');
        
        echo "   ✅ Orders catalog generated successfully\n";
        echo "   📊 Added: {$ordersStats['added']} | Removed: {$ordersStats['removed']} | Total: {$ordersStats['total']}\n";
        echo "   ⏱️  Execution time: {$ordersStats['execution_time']}s\n\n";
        
    } catch (Exception $e) {
        echo "   ❌ ERROR generating orders catalog: " . $e->getMessage() . "\n\n";
        $ordersGenerator->logToDatabase(['added' => 0, 'removed' => 0, 'total' => 0, 'execution_time' => 0], 'error', $e->getMessage());
    }
    
    // ============================================================================
    // Summary
    // ============================================================================
    $totalExecutionTime = round(microtime(true) - $scriptStartTime, 3);
    
    echo "================================================================================\n";
    echo "✅ All feeds generation completed\n";
    echo "⏱️  Total execution time: {$totalExecutionTime}s\n";
    echo "📅 Completed at: " . date('Y-m-d H:i:s') . "\n";
    echo "================================================================================\n\n";
    
    echo "📊 Summary:\n";
    echo "   Main Catalog:    {$mainStats['total']} cars (+" . $mainStats['added'] . " / -" . $mainStats['removed'] . ")\n";
    echo "   Pruncul Catalog: {$pruncklStats['total']} cars (+" . $pruncklStats['added'] . " / -" . $pruncklStats['removed'] . ")\n";
    echo "   Orders Catalog:  {$ordersStats['total']} cars (+" . $ordersStats['added'] . " / -" . $ordersStats['removed'] . ")\n";
    echo "   GRAND TOTAL:     " . ($mainStats['total'] + $pruncklStats['total'] + $ordersStats['total']) . " cars\n\n";
    
    echo "🔗 Feed URLs:\n";
    echo "   Main:    https://www.sauto.md/api/data_feed/df_cars.xml\n";
    echo "   Pruncul: https://www.sauto.md/api/data_feed/df_cars_pruncul.xml\n";
    echo "   Orders:  https://www.sauto.md/api/data_feed/df_cars_orders.xml\n\n";
    
    echo "📈 View logs: https://www.sauto.md/api/data_feed/view_logs.php?pass=sauto2025\n\n";
    
} catch (Exception $e) {
    echo "❌ CRITICAL ERROR: " . $e->getMessage() . "\n";
    echo $e->getTraceAsString() . "\n";
    exit(1);
}

exit(0);
?>
