<?php
require_once __DIR__ . '/../environment.php';

define('_DOIT', 1);
require_once __DIR__ . '/../content/default/config.php';
require_once __DIR__ . '/../content/default/dbi.php';

const ACCESS_TOKEN = 'PLACE_TOKEN_HERE';

if (!isset($_GET['token']) || $_GET['token'] !== ACCESS_TOKEN) {
    http_response_code(403);
    echo 'Forbidden';
    exit;
}

header('Content-Type: text/html; charset=UTF-8');

$sql = "SELECT br_nm, COUNT(*) AS cnt FROM {$prefx}_car_ctlg WHERE n_a = 0 AND vis = 1 AND act = 1 GROUP BY br_nm ORDER BY cnt DESC, br_nm ASC";
$stmt = $db->query($sql);
$brands = $stmt->fetchAll(PDO::FETCH_ASSOC);

$totalCars = 0;
foreach ($brands as $row) {
    $totalCars += (int)$row['cnt'];
}
$brandCount = count($brands);

echo '<!DOCTYPE html><html><head><meta charset="UTF-8"><title>Доступные автомобили по брендам</title></head><body>';
echo '<h1>Доступные автомобили по брендам</h1>';
echo '<table border="1" cellpadding="5" cellspacing="0">';
echo '<tr><th>Марка</th><th>Количество</th></tr>';
foreach ($brands as $row) {
    $brand = htmlspecialchars($row['br_nm'], ENT_QUOTES, 'UTF-8');
    $count = $row['cnt'];
    echo "<tr><td>{$brand}</td><td>{$count}</td></tr>";
}
echo "<tr><th>Всего брендов: {$brandCount}</th><th>{$totalCars}</th></tr>";
echo '</table></body></html>';
?>
