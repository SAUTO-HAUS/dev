<?php
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

$stmt = $db->prepare("
    SELECT * FROM gh3sp_adverts
    WHERE DATE_FORMAT(publish_datetime, '%Y-%m-%d %H:%i') = DATE_FORMAT(:now, '%Y-%m-%d %H:%i') and active = 1
");
$stmt->execute([':now' => $currentDatetime->format('Y-m-d H:i:s')]);

$adverts = $stmt->fetchAll(PDO::FETCH_ASSOC);

if (empty($adverts)) {
    ///echo "[" . date('Y-m-d H:i:s') . "] INFO: No adverts found to publish.\n";
    //$end = microtime(true);
    //$executionTime = $end - $start;

    //echo "Время выполнения скрипта: " . round($executionTime, 4) . " секунд\n";
    return;
}
$i = 0;
echo "[" . date('Y-m-d H:i:s') . "] Adverts:: " . count($adverts) . "\n";

foreach ($adverts as $advert) {
    $car = (new Car())->getCarById($advert['car_id']);
    $images = (!empty($advert['images'])) ? json_decode($advert['images']) : [];
    $images = (is_countable($images) && count($images) > 20) ? array_slice($images, 0, 20) : $images;

    if (!is_countable($images))
        echo "[" . date('Y-m-d H:i:s') . "] IMAGES {$images}\n";

    if ($advert['published'] == 1) {
        //$images = json_decode($advert['images']);
        if (empty($advert['999_id'])) {
            $updateStmt = $db->prepare("UPDATE gh3sp_adverts SET published = 0 WHERE id = :id");
            $updateStmt->execute([':id' => $advert['id']]);
        } else {
            try {
                $responseUpdate = (new Api999Service($car['999_api_id']))->updateAdvert($advert['999_id'], [["id" => "14", "value" => $images]]);
                echo "[" . date('Y-m-d H:i:s') . "] Advert {$advert['999_id']} is already update images. Response1: " . json_encode($responseUpdate, JSON_UNESCAPED_UNICODE) . "\n";

                $i++;
                if (!empty($responseUpdate['success']) && $responseUpdate['success']) {
                } else {
                    sleep(1);
                    $responseUpdate = (new Api999Service($car['999_api_id']))->updateAdvert($advert['999_id'], [["id" => "14", "value" => $images]]);
                    echo "[" . date('Y-m-d H:i:s') . "] Advert-1 {$advert['999_id']} is already update images. Response1: " . json_encode($responseUpdate, JSON_UNESCAPED_UNICODE) . "\n";
                    $i++;
                }

                if (!empty($responseUpdate['success']) && $responseUpdate['success']) {
                    //$response = (new Api999Service($car['999_api_id']))->republishAdvert($car['999_id']);
                    $response = (new Api999Service($car['999_api_id']))->republishAdvert($advert['999_id']);
                    $i++;
                    echo "[" . date('Y-m-d H:i:s') . "] republishAdvert type = {$advert['type']} {$advert['999_id']} Response: " . json_encode($responseUpdate, JSON_UNESCAPED_UNICODE) . "\n";
                } else {
                    /*$db->prepare("UPDATE gh3sp_adverts SET publish_datetime = DATE_ADD(publish_datetime, INTERVAL ".rand(3, 10)." MINUTE) WHERE id = :id
            ")->execute([
                        ':id' => $advert['id']
                    ]);*/
                    sleep(1);
                    ///continue;
                }

                $advert999_id = $advert['999_id'];
                echo "[" . date('Y-m-d H:i:s') . "] Advert {$advert999_id} is already republished\n";
            } catch (\Exception $e) {
                echo "[" . date('Y-m-d H:i:s') . "] Advert {$advert['999_id']} Exception: " . json_encode($e, JSON_UNESCAPED_UNICODE) . "\n";
            }
        }
    } else {
        $newItem = [
            'id' => "14",
            'value' => $images,
        ];
        $ad = json_decode($car['999'], true);
        $features = $ad['features'];

        $exists = false;

        foreach ($features as $item) {
            if ($item['id'] == 14) {
                $exists = true;
                break;
            }
        }

        if (!$exists) {
            $features[] = $newItem;
        }

        $response = (new Api999Service($car['999_api_id']))->setAdvert($ad['category_id'], $ad['subcategory_id'], $ad['offer_type'], $features);
        $i++;
        echo "[" . date('Y-m-d H:i:s') . "] setAdvert " . $advert['car_id'] . " ".($response['advert']['id'] ?? '')." Response: " . json_encode($response, JSON_UNESCAPED_UNICODE) . "\n";

        if (empty($response['advert']['id']) && isset($response['code']) && $response['code'] == 429) {
            sleep(1);
            $response = (new Api999Service($car['999_api_id']))->setAdvert($ad['category_id'], $ad['subcategory_id'], $ad['offer_type'], $features);

            echo "[" . date('Y-m-d H:i:s') . "] setAdvert " . $advert['car_id'] . " ".($response['advert']['id'] ?? '')." Response: " . json_encode($response, JSON_UNESCAPED_UNICODE) . "\n";
            $i++;
            /*$db->prepare("UPDATE gh3sp_adverts SET publish_datetime = DATE_ADD(publish_datetime, INTERVAL ".rand(2, 5)." MINUTE) WHERE id = :id
        ")->execute([
                ':id' => $advert['id']
            ]);
            continue;*/
        }



        if (!empty($response['advert']['id'])) {
            try {
                //$images = json_decode($advert['images']);
                $add_img = (!empty($images)) ? $images : [];
                $responseUpdate = (new Api999Service($car['999_api_id']))->updateAdvert($response['advert']['id'], [["id" => "14", "value" => $add_img]]);
                echo "[" . date('Y-m-d H:i:s') . "] Advert {$response['advert']['id']} is already update images. Response2: " . json_encode($responseUpdate, JSON_UNESCAPED_UNICODE) . "\n";
                $i++;

                $updateStmt = $db->prepare("UPDATE gh3sp_adverts SET published = 1, 999_id = :999_id WHERE id = :id");
                $updateStmt->execute([':id' => $advert['id'], ':999_id' => $response['advert']['id']]);

                $advert999_id = $response['advert']['id'];
                echo "[" . date('Y-m-d H:i:s') . "] Advert {$advert999_id} is already published\n";
            } catch (\Exception $e) {
                echo "[" . date('Y-m-d H:i:s') . "] Advert {$advert['999_id']} Exception1: " . json_encode($e, JSON_UNESCAPED_UNICODE) . "\n";
            }
        } else {
            /*$db->prepare("UPDATE gh3sp_adverts SET publish_datetime = DATE_ADD(publish_datetime, INTERVAL ".rand(2, 5)." MINUTE) WHERE id = :id
        ")->execute([
                ':id' => $advert['id']
            ]);
            continue;*/
        }
    }

    $type = $advert['type'];
    $schedule = Schedules::plus[$type] ?? [];
    $currentDate = $advert['publish_datetime'];
    $nextDatetime = (new Schedules())->getNextPublishDatetime($currentDate, $schedule, $advert['shift_sequence']);

    if ($type != 'base') {
        if (!empty($images) && is_countable($images)) {
            $rotatableImages = array_slice($images, 0, 5);
            $staticImages = array_slice($images, 5);
            $firstImage = array_shift($rotatableImages);
            $rotatableImages[] = $firstImage;

            $images = array_merge($rotatableImages, $staticImages);

            $im = $db->prepare("
            UPDATE gh3sp_adverts
            SET images = :images
            WHERE id = :id
            ")->execute([
                ':images' => json_encode($images),
                ':id' => $advert['id']
            ]);

            echo "[" . date('Y-m-d H:i:s') . "] Advert images {$im}\n";
        }
    }

    $db->prepare("
            UPDATE gh3sp_adverts
            SET publish_datetime = :nextDatetime 
            WHERE id = :id
        ")->execute([
        ':nextDatetime' => $nextDatetime,
        ':id' => $advert['id']
    ]);

    if ($i % 3 == 0) {
        sleep(1);
    }

    if (isset($advert999_id))
        echo "[" . date('Y-m-d H:i:s') . "] Advert {$advert999_id} is already scheduled for {$nextDatetime}\n";
    else
        echo "[" . date('Y-m-d H:i:s') . "] Advert error\n";

    sleep(1);

}

//$end = microtime(true);
//$executionTime = $end - $start;

//echo "Время выполнения скрипта: " . round($executionTime, 4) . " секунд\n";