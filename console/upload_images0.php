<?php
//die;
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

require_once __DIR__ . '/../environment.php';
require ('../' . _DEFAULT . '/dbi.php');
require_once ('../' . _DEFAULT . '/functions.php');

use App\Core\Container;
use App\Db\Car;
use App\Helper\Schedules;
use App\Services\Api999Service;

$currentDatetime = new DateTime();
Container::set('db', $db);
Container::set('prefix', 'gh3sp');

$car_id = 11423;
$car_id = 11457;

$stmt = $db->prepare("SELECT * FROM gh3sp_car_ctlg WHERE id=:id AND updated=0");
$stmt->execute([':id' => $car_id]);

$data = $stmt->fetchAll(\PDO::FETCH_ASSOC);
$path = $_SERVER['DOCUMENT_ROOT'] . '/media/images/upload/car/';
$path = __DIR__ . '/../media/images/upload/car/';
$newPath = __DIR__ . '/../tmp/';

if (!empty($data)) {
    foreach ($data as $car) {
        $exists = false;
        $images999 = [];

        $imgs = (new Car())->getCarsImg($car['id']);

        if (empty($imgs))
            continue;

        $images = array_chunk($imgs, 20);

        if (is_array($images[0])) {
            $ad = json_decode($car['999'], true);

            foreach ($ad['features'] as $k => $item) {
                if ($item['id'] == 14) {
                    unset($ad['features'][$k]);
                    break;
                }
            }

            foreach ($images[0] as $key => $img) {
                $imgPath = $path . $img['path'] . '/' . $img['it_id'] . '/high/' . $img['name'] . '.' . $img['ff'];

                if (!file_exists($imgPath)) {
                    continue;
                }

                $uniqueString = uniqid('img_', true);
                $newFilename = $newPath.$uniqueString. '.' . $img['ff'];

                if (!copy($imgPath, $newFilename)) {
                    continue;
                }
echo $newFilename."\n";
                $imageLink = (new Api999Service($car['999_api_id']))->uploadImage($newFilename);
                echo "**[" . date('Y-m-d H:i:s') . "] imageLink ".$car['id']." Response: " . json_encode($imageLink, JSON_UNESCAPED_UNICODE) . "\n";

                if (is_array($imageLink) && !empty($imageLink['image_id'])) {
                    $images999[] = $imageLink['image_id'];
                }
                if ($key % 4 == 0) {
                    sleep(1);
                }

                unlink($newFilename);
            }

            if (!empty($images999)) {
                $ad['features'][] = ["id" => "14", "value" => $images999];

                $car['999'] = $ad;

                $featuresJson = json_encode($car['999'], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);

                $stmt = $db->prepare("
                    UPDATE gh3sp_car_ctlg 
                    SET `999` = :featuresJson, updated=1 
                    WHERE id = :carId 
                ");
                $stmt->execute([
                    ':featuresJson' => $featuresJson,
                    ':carId' => $car['id'],
                ]);

                $q = "SELECT * FROM gh3sp_adverts WHERE car_id=:id AND updated=0 ORDER BY id";
                $stmt = $db->prepare($q);
                $stmt->execute([':id' => $car['id']]);

                $adverts = $stmt->fetchAll(PDO::FETCH_ASSOC);

                if (!empty($adverts)) {
                    $images = $images999;
                    foreach ($adverts as $key => $advert) {
                        if ($advert['type'] != 'base') {
                            $images = rotateImage($images);
                        }

                        $r = $db->prepare("
                            UPDATE gh3sp_adverts 
                            SET images = :images, updated = 1 
                            WHERE id = :id
                         ")->execute([
                            ':images' => json_encode($images),
                            ':id' => $advert['id']
                        ]);

                        if ($r) {
                            echo "**[" . date('Y-m-d H:i:s') . "] set images advert ".$advert['id']."\n";
                        } else {
                            echo "**[" . date('Y-m-d H:i:s') . "] not set images advert ".$advert['id']. "\n";
                        }

                        if ($key % 4 == 0) {
                            sleep(1);
                        }
                    }
                }
            }
        }
        sleep(2);
    }
}

function rotateImage($images)
{
    $rotatableImages = array_slice($images, 0, 5);
    $staticImages = array_slice($images, 5);
    $firstImage = array_shift($rotatableImages);
    $rotatableImages[] = $firstImage;

    $images = array_merge($rotatableImages, $staticImages);

    return $images;
}

exit(0);