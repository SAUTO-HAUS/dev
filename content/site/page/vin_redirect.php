<?php defined('_DOIT') or die('Restricted access');

$car_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if (!$car_id) {
    header('Location: /' . ($_COOKIE['lang'] ?? 'ro') . '/vin-check');
    exit;
}

$pdo = $db->prepare('SELECT vin, vin_check_enabled FROM ' . $prefx . '_car_ctlg WHERE id = :id LIMIT 1');
$pdo->execute(['id' => $car_id]);
$car = $pdo->fetch(PDO::FETCH_ASSOC);

if (!$car || empty($car['vin']) || strlen($car['vin']) !== 17 || !$car['vin_check_enabled']) {
    header('Location: /' . ($_COOKIE['lang'] ?? 'ro') . '/vin-check');
    exit;
}

$cv_config = include($_SERVER['DOCUMENT_ROOT'] . '/App/config/carvertical.php');

if (empty($cv_config['affiliate_id'])) {
    header('Location: /' . ($_COOKIE['lang'] ?? 'ro') . '/vin-check');
    exit;
}

$vin = $car['vin'];
$affiliate_id = $cv_config['affiliate_id'];
$base_url = $cv_config['base_url'];
$country = $cv_config['country'];

$rtrn = '<!DOCTYPE html><html><head><meta charset="UTF-8"><title>Redirecting...</title></head><body>
<form id="f" method="POST" action="' . htmlspecialchars($base_url . '/' . $country) . '">
<input type="hidden" name="' . htmlspecialchars($cv_config['affiliate_param']) . '" value="' . htmlspecialchars($affiliate_id) . '">
<input type="hidden" name="' . htmlspecialchars($cv_config['vin_param']) . '" value="' . htmlspecialchars($vin) . '">
</form><script>document.getElementById("f").submit();</script></body></html>';

echo $rtrn;
exit;
