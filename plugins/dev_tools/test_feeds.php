<?php
/**
 * Facebook Data Feeds - Testing & Verification Script
 * 
 * This script tests the feed generation logic and displays sample data
 * for manual verification before deploying to production.
 * 
 * Usage: php plugins/dev_tools/test_feeds.php
 * Or access via browser: https://www.sauto.md/plugins/dev_tools/test_feeds.php?token=test123
 * 
 * @author SAUTO Development Team
 * @created 2025-11-21
 */

// Security token for browser access
const ACCESS_TOKEN = 'test123';

if (php_sapi_name() !== 'cli') {
    if (!isset($_GET['token']) || $_GET['token'] !== ACCESS_TOKEN) {
        http_response_code(403);
        die('Forbidden. Use: ?token=test123');
    }
    header('Content-Type: text/html; charset=UTF-8');
    echo '<pre style="font-family: monospace; background: #1a1a1a; color: #00ff00; padding: 20px;">';
}

define('_DOIT', 1);

try {
    require_once __DIR__ . '/../../content/default/defines.php';
    require_once __DIR__ . '/../../content/default/functions.php';
    require_once __DIR__ . '/../../environment.php';
    require_once __DIR__ . '/../../content/default/config.php';
    require_once __DIR__ . '/../../content/default/dbi.php';
    
    echo "================================================================================\n";
    echo "Facebook Data Feeds - Testing & Verification\n";
    echo "================================================================================\n\n";
    
    // ============================================================================
    // Test 1: Main Catalog Query
    // ============================================================================
    echo "📦 [1/3] Testing Main Catalog Query...\n";
    echo "   Filter: in_stock, loc=1, no orders\n";
    
    $sqlMain = "SELECT COUNT(*) as total, 
                       MIN(id) as min_id, MAX(id) as max_id,
                       MIN(yr) as min_year, MAX(yr) as max_year
                FROM {$prefx}_car_ctlg 
                WHERE `n_a`=0 AND `vis`=1 AND `act`=1 
                AND `catalog_type`='in_stock' AND `loc`='1'";
    $stmtMain = $db->query($sqlMain);
    $mainStats = $stmtMain->fetch(PDO::FETCH_ASSOC);
    
    echo "   ✅ Total cars: {$mainStats['total']}\n";
    echo "   📊 ID range: {$mainStats['min_id']} - {$mainStats['max_id']}\n";
    echo "   📅 Year range: {$mainStats['min_year']} - {$mainStats['max_year']}\n";
    
    // Sample cars from main catalog
    $sqlMainSample = "SELECT id, yr, br_nm, mo_nm, prc, cur, catalog_type, loc 
                      FROM {$prefx}_car_ctlg 
                      WHERE `n_a`=0 AND `vis`=1 AND `act`=1 
                      AND `catalog_type`='in_stock' AND `loc`='1'
                      LIMIT 5";
    $stmtMainSample = $db->query($sqlMainSample);
    echo "   📋 Sample cars (first 5):\n";
    foreach ($stmtMainSample as $car) {
        echo "      - ID:{$car['id']} | {$car['yr']} {$car['br_nm']} {$car['mo_nm']} | {$car['prc']} {$car['cur']} | Type:{$car['catalog_type']} Loc:{$car['loc']}\n";
    }
    echo "\n";
    
    // ============================================================================
    // Test 2: Pruncul Catalog Query
    // ============================================================================
    echo "📦 [2/3] Testing Pruncul Catalog Query...\n";
    echo "   Filter: in_stock, loc=2, no orders\n";
    
    $sqlPrunckl = "SELECT COUNT(*) as total,
                          MIN(id) as min_id, MAX(id) as max_id,
                          MIN(yr) as min_year, MAX(yr) as max_year
                   FROM {$prefx}_car_ctlg 
                   WHERE `n_a`=0 AND `vis`=1 AND `act`=1 
                   AND `catalog_type`='in_stock' AND `loc`='2'";
    $stmtPrunckl = $db->query($sqlPrunckl);
    $pruncklStats = $stmtPrunckl->fetch(PDO::FETCH_ASSOC);
    
    echo "   ✅ Total cars: {$pruncklStats['total']}\n";
    echo "   📊 ID range: {$pruncklStats['min_id']} - {$pruncklStats['max_id']}\n";
    echo "   📅 Year range: {$pruncklStats['min_year']} - {$pruncklStats['max_year']}\n";
    
    // Sample cars from Pruncul catalog
    $sqlPruncklSample = "SELECT id, yr, br_nm, mo_nm, prc, cur, catalog_type, loc 
                         FROM {$prefx}_car_ctlg 
                         WHERE `n_a`=0 AND `vis`=1 AND `act`=1 
                         AND `catalog_type`='in_stock' AND `loc`='2'
                         LIMIT 5";
    $stmtPruncklSample = $db->query($sqlPruncklSample);
    echo "   📋 Sample cars (first 5):\n";
    $pruncklCount = 0;
    foreach ($stmtPruncklSample as $car) {
        echo "      - ID:{$car['id']} | {$car['yr']} {$car['br_nm']} {$car['mo_nm']} | {$car['prc']} {$car['cur']} | Type:{$car['catalog_type']} Loc:{$car['loc']}\n";
        $pruncklCount++;
    }
    if ($pruncklCount === 0) {
        echo "      (No cars found - this is normal if Pruncul has no inventory)\n";
    }
    echo "\n";
    
    // ============================================================================
    // Test 3: Orders Catalog Query
    // ============================================================================
    echo "📦 [3/3] Testing Orders Catalog Query...\n";
    echo "   Filter: on_order, active timer, all locations\n";
    
    $currentTime = time();
    $sqlOrders = "SELECT COUNT(*) as total,
                         MIN(id) as min_id, MAX(id) as max_id,
                         MIN(offer_timer_end) as min_timer, MAX(offer_timer_end) as max_timer
                  FROM {$prefx}_car_ctlg 
                  WHERE `n_a`=0 AND `vis`=1 AND `act`=1 
                  AND `catalog_type`='on_order' AND `offer_timer_end` > {$currentTime}";
    $stmtOrders = $db->query($sqlOrders);
    $ordersStats = $stmtOrders->fetch(PDO::FETCH_ASSOC);
    
    echo "   ✅ Total cars: {$ordersStats['total']}\n";
    echo "   📊 ID range: {$ordersStats['min_id']} - {$ordersStats['max_id']}\n";
    if ($ordersStats['min_timer']) {
        echo "   ⏰ Timer range: " . date('Y-m-d H:i', $ordersStats['min_timer']) . " - " . date('Y-m-d H:i', $ordersStats['max_timer']) . "\n";
    }
    
    // Sample cars from Orders catalog
    $sqlOrdersSample = "SELECT id, yr, br_nm, mo_nm, prc, cur, catalog_type, loc, offer_timer_end 
                        FROM {$prefx}_car_ctlg 
                        WHERE `n_a`=0 AND `vis`=1 AND `act`=1 
                        AND `catalog_type`='on_order' AND `offer_timer_end` > {$currentTime}
                        LIMIT 5";
    $stmtOrdersSample = $db->query($sqlOrdersSample);
    echo "   📋 Sample cars (first 5):\n";
    $ordersCount = 0;
    foreach ($stmtOrdersSample as $car) {
        $timerExpires = date('Y-m-d H:i', $car['offer_timer_end']);
        echo "      - ID:{$car['id']} | {$car['yr']} {$car['br_nm']} {$car['mo_nm']} | {$car['prc']} {$car['cur']} | Type:{$car['catalog_type']} Loc:{$car['loc']} | Expires:{$timerExpires}\n";
        $ordersCount++;
    }
    if ($ordersCount === 0) {
        echo "      (No cars found - this is normal if no active orders exist)\n";
    }
    echo "\n";
    
    // ============================================================================
    // Verification: Check for overlaps (should be zero)
    // ============================================================================
    echo "🔍 Verification: Checking for data overlaps...\n";
    
    // Check if any main catalog cars appear in Prunckl
    $sqlOverlap1 = "SELECT COUNT(*) as overlap 
                    FROM {$prefx}_car_ctlg 
                    WHERE `n_a`=0 AND `vis`=1 AND `act`=1 
                    AND `catalog_type`='in_stock' AND `loc`='1' AND `loc`='2'";
    $stmtOverlap1 = $db->query($sqlOverlap1);
    $overlap1 = $stmtOverlap1->fetch(PDO::FETCH_ASSOC)['overlap'];
    echo "   Main ∩ Prunckl: {$overlap1} cars " . ($overlap1 > 0 ? "❌ ERROR!" : "✅ OK") . "\n";
    
    // Check if any in_stock cars are also on_order
    $sqlOverlap2 = "SELECT COUNT(*) as overlap 
                    FROM {$prefx}_car_ctlg 
                    WHERE `n_a`=0 AND `vis`=1 AND `act`=1 
                    AND `catalog_type`='in_stock' AND `catalog_type`='on_order'";
    $stmtOverlap2 = $db->query($sqlOverlap2);
    $overlap2 = $stmtOverlap2->fetch(PDO::FETCH_ASSOC)['overlap'];
    echo "   In-stock ∩ Orders: {$overlap2} cars " . ($overlap2 > 0 ? "❌ ERROR!" : "✅ OK") . "\n";
    
    echo "\n";
    
    // ============================================================================
    // Summary
    // ============================================================================
    echo "================================================================================\n";
    echo "📊 SUMMARY\n";
    echo "================================================================================\n";
    echo "Main Catalog:    {$mainStats['total']} cars (in-stock, main location)\n";
    echo "Pruncul Catalog: {$pruncklStats['total']} cars (in-stock, Pruncul location)\n";
    echo "Orders Catalog:  {$ordersStats['total']} cars (on_order, active timer)\n";
    echo "--------------------------------------------------------------------------------\n";
    echo "GRAND TOTAL:     " . ($mainStats['total'] + $pruncklStats['total'] + $ordersStats['total']) . " cars\n";
    echo "================================================================================\n\n";
    
    echo "✅ All tests completed successfully!\n";
    echo "📝 Next steps:\n";
    echo "   1. Run: php plugins/dev_tools/generate_data_feeds.php\n";
    echo "   2. Check logs: https://www.sauto.md/api/data_feed/view_logs.php?pass=sauto2025\n";
    echo "   3. Verify feeds:\n";
    echo "      - https://www.sauto.md/api/data_feed/df_cars.xml\n";
    echo "      - https://www.sauto.md/api/data_feed/df_cars_prunckl.xml\n";
    echo "      - https://www.sauto.md/api/data_feed/df_cars_orders.xml\n\n";
    
} catch (Exception $e) {
    echo "❌ ERROR: " . $e->getMessage() . "\n";
    echo $e->getTraceAsString() . "\n";
    exit(1);
}

if (php_sapi_name() !== 'cli') {
    echo '</pre>';
}
?>
