<?php

define('_DOIT', 1);
require_once __DIR__ . '/../content/default/defines.php';
require_once __DIR__ . '/../content/default/functions.php';
require_once __DIR__ . '/../environment.php';
require_once __DIR__ . '/../content/default/config.php';
require_once __DIR__ . '/../content/default/dbi.php';

const ACCESS_TOKEN = 'PLACE_TOKEN_HERE';

if (!isset($_GET['token']) || $_GET['token'] !== ACCESS_TOKEN) {
    http_response_code(403);
    echo 'Forbidden';
    exit;
}

header('Content-Type: text/html; charset=UTF-8');

$sql = "SELECT 
    br_nm,
    COUNT(*) AS cnt_total,
    SUM(CASE WHEN loc = '1' THEN 1 ELSE 0 END) AS cnt_main,
    SUM(CASE WHEN loc = '2' THEN 1 ELSE 0 END) AS cnt_pruncul
FROM {$prefx}_car_ctlg 
WHERE n_a = 0 AND vis = 1 AND act = 1 
GROUP BY br_nm 
ORDER BY cnt_total DESC, br_nm ASC";
$stmt = $db->query($sql);
$brands = $stmt->fetchAll(PDO::FETCH_ASSOC);

$totalCars = 0;
$totalMain = 0;
$totalPruncul = 0;
foreach ($brands as $row) {
    $totalCars += (int)$row['cnt_total'];
    $totalMain += (int)$row['cnt_main'];
    $totalPruncul += (int)$row['cnt_pruncul'];
}
$brandCount = count($brands);

echo '<!DOCTYPE html><html><head><meta charset="UTF-8"><title>Доступные автомобили по брендам</title></head><body>';
echo '<h1>Доступные автомобили по брендам</h1>';
echo '<table border="1" cellpadding="5" cellspacing="0">';
echo '<tr><th>Марка</th><th>Количество всего</th><th>Количество (основной филиал)</th><th>Количество (филиал "Прунтул")</th></tr>';
foreach ($brands as $row) {
    $brand = htmlspecialchars($row['br_nm'], ENT_QUOTES, 'UTF-8');
    $countTotal = $row['cnt_total'];
    $countMain = $row['cnt_main'];
    $countPruncul = $row['cnt_pruncul'];
    echo "<tr><td>{$brand}</td><td>{$countTotal}</td><td>{$countMain}</td><td>{$countPruncul}</td></tr>";
}
echo "<tr><th>Всего брендов: {$brandCount}</th><th>{$totalCars}</th><th>{$totalMain}</th><th>{$totalPruncul}</th></tr>";
echo '</table></body></html>';
?>
