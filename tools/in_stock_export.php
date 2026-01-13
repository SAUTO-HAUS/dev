<?php

define('_DOIT', 1);
require_once __DIR__ . '/../content/default/defines.php';
require_once __DIR__ . '/../content/default/functions.php';
require_once __DIR__ . '/../environment.php';
require_once __DIR__ . '/../content/default/config.php';
require_once __DIR__ . '/../content/default/dbi.php';

const ACCESS_TOKEN = 'comet-ladder-sphinx-A7K3M9';

if (!isset($_GET['token']) || $_GET['token'] !== ACCESS_TOKEN) {
    http_response_code(403);
    header('Content-Type: application/json; charset=UTF-8');
    echo json_encode(['error' => 'Forbidden', 'message' => 'Invalid or missing token']);
    exit;
}

header('Content-Type: application/json; charset=UTF-8');

// Check if status_changed_at column exists
$statusChangedAtExists = false;
try {
    $checkColumn = $db->query("SHOW COLUMNS FROM {$prefx}_car_ctlg LIKE 'status_changed_at'");
    $statusChangedAtExists = $checkColumn->rowCount() > 0;
} catch (Exception $e) {
    // Column doesn't exist, continue without it
}

// Build SELECT query with all available technical fields
$selectFields = "
    id AS car_id,
    br,
    mo,
    br_nm AS brand,
    mo_nm AS model,
    yr AS year,
    vol AS engine_volume,
    hp AS horsepower,
    fl AS fuel,
    tra AS transmission,
    wd AS drive,
    bt AS body_type,
    clr AS color,
    mlg AS mileage,
    unit AS mileage_unit,
    sts AS car_condition,
    prc AS price,
    loc AS location,
    vin,
    act,
    vis,
    n_a,
    date
";

if ($statusChangedAtExists) {
    $selectFields .= ", status_changed_at";
}

$sql = "SELECT {$selectFields}
FROM {$prefx}_car_ctlg
WHERE catalog_type = 'in_stock'
  AND act = 1
ORDER BY id DESC";

try {
    $stmt = $db->query($sql);
    $cars = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Convert numeric strings to proper types for cleaner JSON
    foreach ($cars as &$car) {
        $car['car_id'] = (int)$car['car_id'];
        $car['year'] = $car['year'] !== null ? (int)$car['year'] : null;
        $car['horsepower'] = $car['horsepower'] !== null ? (int)$car['horsepower'] : null;
        $car['mileage'] = $car['mileage'] !== null ? (int)$car['mileage'] : null;
        $car['price'] = $car['price'] !== null ? (float)$car['price'] : null;
        $car['location'] = $car['location'] !== null ? (int)$car['location'] : null;
        $car['act'] = (int)$car['act'];
        $car['vis'] = (int)$car['vis'];
        $car['n_a'] = (int)$car['n_a'];
        $car['date'] = $car['date'] !== null ? (int)$car['date'] : null;
        
        if ($statusChangedAtExists && isset($car['status_changed_at'])) {
            // Keep as-is (datetime string) or convert to timestamp if needed
        }
    }
    unset($car);
    
    echo json_encode([
        'success' => true,
        'count' => count($cars),
        'generated_at' => time(),
        'data' => $cars
    ], JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
    
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode([
        'error' => 'Database error',
        'message' => 'Failed to fetch data'
    ], JSON_UNESCAPED_UNICODE);
}
