<?php
exit(0);
//$start = microtime(true);
//echo "Старт: " . date('d.m.Y H:i:s'). "\n";
error_reporting(E_ALL);
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
 
chdir(__DIR__);
define('_DOIT', 1);
define('_DEFAULT', 'content/default');

spl_autoload_register(function ($class) {
    $baseDir = __DIR__ . '/../';
    $classPath = $baseDir . str_replace('\\', DIRECTORY_SEPARATOR, $class) . '.php';
    if (file_exists($classPath)) {
        require_once $classPath;
    }
});

require ('../' . _DEFAULT . '/dbi.php');
require_once ('../' . _DEFAULT . '/functions.php');

use App\Core\Container;
use App\Db\Car;
use App\Helper\Schedules;
use App\Services\Api999Service;

$currentDatetime = new DateTime();
Container::set('db', $db);
Container::set('prefix', 'gh3sp');

$newPath = __DIR__ . '/../tmp/';

echo "Start1\n";
$ids999 = include $newPath."ids999.php";

$q = "SELECT car_id FROM gh3sp_adverts ";
//$q .= "WHERE (999_id IN (".implode(',', $ids999).") OR `images` LIKE '%null%') AND updated=1 AND 999_id IS NOT NULL ";
$q .= "WHERE (999_id IN (100740413,100766830,100750991,100750993,100790310,100790313) OR `images` LIKE '%null%') AND updated=1 AND 999_id IS NOT NULL ";

//$sql = "SELECT * FROM gh3sp_car_ctlg WHERE (id IN (".$q.") OR id IN (11435,11396,11459,11404,11390,11421,11432)) AND updated=1 LIMIT 3";
$sql = "SELECT * FROM gh3sp_car_ctlg WHERE id IN (".$q.") AND updated=1 LIMIT 3";
$stmt = $db->prepare($sql);

//echo $sql."\n";

$stmt->execute();

$data = $stmt->fetchAll(\PDO::FETCH_ASSOC);

if (!empty($data)) {
    foreach ($data as $car) {
        $q = "SELECT * FROM gh3sp_adverts WHERE ";
        $q .= "car_id=:id AND updated=1 AND published = 1 ";
        $q .= "AND 999_id IS NOT NULL ";
        $q .= "ORDER BY id";
        $stmt = $db->prepare($q);
        $stmt->execute([':id' => $car['id']]);

        //echo str_replace(':id', $car['id'], $q)."\n";

        $adverts = $stmt->fetchAll(PDO::FETCH_ASSOC);
        $test = true;

        if (!empty($adverts)) {
            foreach ($adverts as $key => $advert) {

                $images = json_decode($advert['images']);

                if (empty($advert['images']))
                    continue;

                try {
                    if (empty($advert['999_id']))
                        continue;

                    $response = (new Api999Service($car['999_api_id']))->updateAdvert($advert['999_id'], [["id" => "14", "value" => $images]]);

                    if (!empty($response['success']) && $response['success']) {
                        $q = "UPDATE gh3sp_adverts SET updated = 2 WHERE id = :id ";
                        $db->prepare($q)->execute([
                            ':id' => $advert['id']
                        ]);
                        echo "= " . str_replace(':id', $advert['id'], $q) . "\n";
                        echo "=====[" . date('Y-m-d H:i:s') . "] Advert {$advert['999_id']} is update images. Response: " . json_encode($response, JSON_UNESCAPED_UNICODE) . "\n";
                    } else {
                        $test = false;
                        echo "=====[" . date('Y-m-d H:i:s') . "] Advert {$advert['999_id']} is not update images. Response: " . json_encode($response, JSON_UNESCAPED_UNICODE) . "\n";
                        continue;
                    }
                } catch (\Exception $e) {
                    echo "=====[" . date('Y-m-d H:i:s') . "] Error: " . json_encode($e, JSON_UNESCAPED_UNICODE) . "\n";
                }
                if ($key % 4 == 0) {
                    sleep(1);
                }
            }
        }
        sleep(2);

        if ($test) {
            $q = "UPDATE gh3sp_car_ctlg SET updated=2 WHERE id = :carId ";
            $stmt = $db->prepare($q);
            $stmt->execute([
                ':carId' => $car['id'],
            ]);
            //echo "=+ " . str_replace(':carId', $car['id'], $q) . "\n";
        }
    }
}

exit(0);