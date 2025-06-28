<?php

namespace App\Services;

use App\Core\Container;
use App\Db\Car;
use App\Helper\Schedules;
use DateTime;
use Exception;

class Api999Service
{
    public const API_KEY = [1 => [
        "key" => "I_SKyGEvvG5Rfm7lRZeiOTwk7r_F",
        "name" => "SAUTO-HAUS",
    ], 2 => [
        "key" => "EeKkPqGFjEhJZIK3S5KWh59w8jNG",
        "name" => "Sauto-auto-comerciale",
    ], 3 => [
        "key" => "jMEsHjO0FhoRZm0KSsONLpkGLMIK",
        "name" => "Sauto-stock-extern",
    ]];
    protected const BASE_URL = "https://partners-api.999.md";

    private $db;
    private $prefix;
    private $api_key;

    public function __construct(int $key = 1)
    {
        $this->db = Container::get('db');
        $this->prefix = Container::get('prefix');

        if (isset(self::API_KEY[$key])) {
            $this->api_key = self::API_KEY[$key]['key'];
        } else {
            throw new \InvalidArgumentException("Invalid key provided: {$key}");
        }
    }

    /**
     * Get phone numbers
     *
     * @param string $lang
     * @return array
     * @throws \Exception
     */
    public function getPhones(string $lang = 'ru'): array
    {
        $url = self::BASE_URL . "/phone_numbers?lang=" . urlencode($lang);
        return $this->sendCachedRequest($url); //$this->sendRequest($url);
    }

    /**
     * Get all categories
     *
     * @param string $lang
     * @return array
     * @throws \Exception
     */
    public function getCategories(string $lang = 'ru'): array
    {
        $url = self::BASE_URL . "/categories?lang=" . urlencode($lang);
        return $this->sendCachedRequest($url); //$this->sendRequest($url);
    }

    /**
     * Get subcategories for the specified category
     *
     * @param int $categoryId
     * @param string $lang
     * @return array
     * @throws \Exception
     */
    public function getSubcategories(int $categoryId, string $lang = 'ru'): array
    {
        $url = self::BASE_URL . "/categories/" . urlencode($categoryId) . "/subcategories?lang=" . urlencode($lang);
        return $this->sendRequest($url);
    }

    /**
     * Get subcategory offer types for the specified category
     *
     * @param int $categoryId
     * @param int $subcategoryId
     * @param string $lang
     * @return array
     * @throws \Exception
     */
    public function getSubcategoryOfferTypes(int $categoryId, int $subcategoryId, string $lang = 'ru'): array
    {
        $url = self::BASE_URL . "/categories/" . urlencode($categoryId) . "/subcategories/" . urlencode($subcategoryId) . "/offer-types?lang=" . urlencode($lang);
        return $this->sendCachedRequest($url); //$this->sendRequest($url);
    }

    /**
     * Get subcategory features for the specified category
     *
     * @param int $categoryId
     * @param int $subcategoryId
     * @param int $offerTypeId
     * @param string $lang
     * @return array
     * @throws \Exception
     */
    public function getSubcategoryFeatures(int $categoryId, int $subcategoryId, int $offerTypeId, string $lang = 'ru'): array
    {
        $url = self::BASE_URL . "/features?category_id=" . urlencode($categoryId) . "&subcategory_id=" . urlencode($subcategoryId) . "&offer_type=" . urlencode($offerTypeId) . "&lang=" . urlencode($lang);
        return $this->sendCachedRequest($url); //$this->sendRequest($url);
    }

    /**
     * Get dependent options for the specified category
     *
     * @param int $subcategoryId
     * @param int $dependencyFeatureId
     * @param int $parentOptionId
     * @param string $lang
     * @return array
     * @throws \Exception
     */
    public function getDependentOptions(int $subcategoryId, int $dependencyFeatureId, int $parentOptionId, string $lang = 'ru'): array
    {
        $url = self::BASE_URL . "/dependent_options?subcategory_id=" . urlencode($subcategoryId) . "&dependency_feature_id=" . urlencode($dependencyFeatureId) . "&parent_option_id=" . urlencode($parentOptionId) . "&lang=" . urlencode($lang);
        return $this->sendCachedRequest($url); //$this->sendRequest($url);
    }

    /**
     * Get advert by ID
     *
     * @param int $advertId
     * @param string $lang
     * @return array
     * @throws \Exception
     */
    public function getAdvert(int $advertId, string $lang = 'ru')
    {
        $url = self::BASE_URL . "/adverts/" . urlencode($advertId) . "?lang=" . urlencode($lang);
        return $this->sendRequest($url);
    }

    /**
     * Get advert features by ID
     *
     * @param int $advertId
     * @param string $lang
     * @return array
     * @throws \Exception
     */
    public function getAdvertFeatures(int $advertId, string $lang = 'ru'): array
    {
        $url = self::BASE_URL . "/adverts/" . urlencode($advertId) . "/features?lang=" . urlencode($lang);
        return $this->sendRequest($url);
    }

    /**
     * Set advert
     *
     * @param string $category_id
     * @param string $subcategory_id
     * @param string $offer_type
     * @param array $features
     * @return array
     * @throws \Exception
     */
    public function setAdvert(string $category_id, string $subcategory_id, string $offer_type, array $features)
    {
        $url = self::BASE_URL . "/adverts";
        $data = [
            'category_id' => $category_id,
            'subcategory_id' => $subcategory_id,
            'offer_type' => $offer_type,
            'features' => $features,
        ];

        return $this->sendRequest($url, 'POST', $data);
    }

    /**
     * Update advert
     *
     * @param int $advertId
     * @param array $features
     * @return array
     * @throws \Exception
     */
    public function updateAdvert(int $advertId, array $features)
    {
        $url = self::BASE_URL . "/adverts/" . $advertId;
        $data = [
            'features' => $features,
        ];
        $logMessage = '';
        $logMessage .= $url . " ";
        $logMessage .= json_encode($data, JSON_UNESCAPED_UNICODE) . "\n";

        //__log($logMessage, 'updateAdvert.log');
        
        $result = $this->sendRequest($url, 'PATCH', $data);

        //__log($result, 'updateAdvert.log');

        return $result;
    }

    /**
     * Republish advert
     *
     * @param int $advertId
     * @return array
     * @throws \Exception
     */
    public function republishAdvert(int $advertId)
    {
        $url = self::BASE_URL . "/adverts/" . urlencode($advertId) . "/republish";
        echo $url.PHP_EOL;
        return $this->sendRequest($url, 'POST');
    }

    /**
     * Set advert access policy
     *
     * @param $car
     * @param string $status
     * @return true
     * @throws \Exception
     */
    public function changeAccessPolicy($car, string $status = 'private')
    {
        try {
            $sql = 'SELECT * FROM ' . $this->prefix . '_adverts WHERE `car_id`=' . $car['id'] . ' and `active` = 1 and `999_id` is not null ORDER BY `type` ASC';
            $stmt = $this->db->prepare($sql);
            $stmt->execute();
            $carAdverts = $stmt->fetchAll(\PDO::FETCH_ASSOC);
            foreach ($carAdverts as $carAdvert) {
               if(empty($carAdvert['999_id'])){
                   continue;
               }

                $url = self::BASE_URL . "/adverts/" . urlencode($carAdvert['999_id']) . "/access_policy";
                $res = $this->sendRequest($url, 'PUT', ['access_policy' => $status]);
            }

            return true;
        }catch (\Exception $e){
            dd($e->getMessage());
        }
    }

    /**
     * @param $filePath
     * @return mixed|string
     */
    public function uploadImage($filePath = null)
    {
        try {
            $apiUrl = self::BASE_URL . '/images';

            return $this->sendFile($apiUrl, $filePath);
        } catch (\Exception $e) {
            return 'Error: ' . $e->getMessage();
        }
    }

    /**
     * Get advert booster settings
     *
     * @param int $advertId
     * @return array
     * @throws \Exception
     */
    public function getAdvertBoosterSettings(int $advertId): array
    {
        $url = self::BASE_URL . "/adverts/" . urlencode($advertId) . "/booster/settings/";
        $responce = $this->sendCachedRequest($url); //$this->sendRequest($url);

        if (empty($responce)) {
            return [];
        }

        return $responce;
        return $this->sendRequest($url);
    }

    /**
     * Update advert booster settings
     *
     * @param int $advertId
     * @param int $period
     * @param int $dailyLimit
     * @return array
     * @throws \Exception
     */
    public function updateAdvertBoosterSettings(int $advertId, int $period, int $dailyLimit, int $click_price): array
    {
        $url = self::BASE_URL . "/adverts/" . urlencode($advertId) . "/booster/settings/";
        $data = [
            "period" => $period,
            "daily_limit" => $dailyLimit,
            "click_price" => $click_price,
        ];

        $this->clearCache($url);

        return $this->sendRequest($url, 'POST', $data);
    }

    /**
     * Pause advert booster
     *
     * @param int $advertId
     * @return array
     * @throws \Exception
     */
    public function pauseAdvertBooster(int $advertId): array
    {
        $url = self::BASE_URL . "/adverts/" . urlencode($advertId) . "/booster/pause/";

        return $this->sendRequest($url, 'POST');
    }

    /**
     * Send HTTP request
     *
     * @param string $url
     * @return array
     * @throws \Exception
     */
    private function sendRequest($url, string $method = 'GET', array $data = null)
    {
        try {
            $jsonData = null;

            $curl = curl_init();
            $options = [
                CURLOPT_URL => $url,
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_HTTPAUTH => CURLAUTH_BASIC,
                CURLOPT_USERPWD => $this->api_key . ":",
                CURLOPT_SSL_VERIFYPEER => true,
            ];

            if (($method === 'POST' || $method === 'PATCH' || $method === 'PUT') && $data !== null) {
                $jsonData = json_encode($data);
                $options[CURLOPT_POSTFIELDS] = $jsonData;
                $options[CURLOPT_HTTPHEADER] = [
                    'Content-Type: application/json',
                    'Content-Length: ' . strlen($jsonData),
                ];
            }

            if ($method === 'PATCH') {
                $options[CURLOPT_CUSTOMREQUEST] = 'PATCH';
            } elseif ($method === 'POST') {
                $options[CURLOPT_POST] = true;
            } elseif ($method === 'PUT') {
                $options[CURLOPT_CUSTOMREQUEST] = 'PUT';
            }

            curl_setopt_array($curl, $options);

            $response = curl_exec($curl);

            //__log($response, 'Curl_Resp.log');

            try {
                //static::logRequest($url, $method, $options, $jsonData);
            } catch (\Exception $e) {
                __log($e, 'logRequestError.log');
            }

            if (curl_errno($curl)) {
                //return ['error' => curl_error($curl)];
                throw new \Exception('cURL Error: ' . curl_error($curl));
            }

            $httpCode = curl_getinfo($curl, CURLINFO_HTTP_CODE);
            curl_close($curl);

            json_decode($response);

            if (json_last_error() === JSON_ERROR_NONE)
                return json_decode($response, true);

            //__log($httpCode, 'Curl_Resp_ERR.log');
            //__log($response, 'Curl_Resp_ERR.log');

            return [
                'success' => false,
                'error' => true,
                'code' => $httpCode,
                'message' => $response,
            ];
        } catch (\Exception $e) {
            __log($e, 'Curl_Error.log');
            return [
                'error' => $e->getMessage(),
                'success' => false,
            ];
        }
    }

    protected function sendCachedRequest(string $url, string $method = 'GET', array $data = null, int $duration = 3600)
    {
        $cacheKey = md5($method . $url . json_encode($data));

        $cachePath = $_SERVER['DOCUMENT_ROOT'] . '/cache/';

        $cacheFile = $cachePath . $cacheKey . '.cache';

        if (file_exists($cacheFile) && (time() - filemtime($cacheFile) < $duration)) {
            $cachedData = file_get_contents($cacheFile);
            return json_decode($cachedData, true);
        }

        $response = $this->sendRequest($url, $method, $data);

        if (!is_dir($cachePath)) {
            mkdir($cachePath, 0777, true);
        }
        file_put_contents($cacheFile, json_encode($response));

        return $response;
    }

    /**
     * Удаляет кэш
     *
     * @param string|null $url     — URL, для которого нужно удалить кэш
     * @param string $method       — HTTP-метод (GET, POST и т.д.)
     * @param array|null $data     — Данные запроса (если есть)
     * @return void
     */
    public function clearCache(string $url = null, string $method = 'GET', array $data = null): void
    {
        $cacheDir = $_SERVER['DOCUMENT_ROOT'] . '/cache';

        if (!is_dir($cacheDir)) {
            return;
        }

        if ($url !== null) {
            $cacheKey = md5($method . $url . json_encode($data));
            $cacheFile = $cacheDir . '/' . $cacheKey . '.cache';

            if (file_exists($cacheFile)) {
                unlink($cacheFile);
            }
            return;
        }

        $files = glob($cacheDir . '/*.cache');
        foreach ($files as $file) {
            if (is_file($file)) {
                unlink($file);
            }
        }
    }

    public function sendFile($url, $filePath)
    {
        if (!file_exists($filePath)) {
            throw new \Exception('File does not exist: ' . $filePath);
        }

        $boundary = '---BOUNDARY';
        $fileContents = file_get_contents($filePath);
        $fileName = basename($filePath);

        // Формування тіла запиту
        $body = "--{$boundary}\r\n";
        $body .= "Content-Disposition: form-data; name=\"file\"; filename=\"{$fileName}\"\r\n";
        $body .= "Content-Type: image/jpeg\r\n\r\n";
        $body .= $fileContents . "\r\n";
        $body .= "--{$boundary}--\r\n";

        $curl = curl_init();
        $options = [
            CURLOPT_URL => $url,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_HTTPAUTH => CURLAUTH_BASIC,
            CURLOPT_USERPWD => $this->api_key . ":",
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => $body,
            CURLOPT_HTTPHEADER => [
                "Content-Type: multipart/form-data; boundary={$boundary}",
                "Content-Length: " . strlen($body),
            ],
            CURLOPT_SSL_VERIFYPEER => true,
        ];

        curl_setopt_array($curl, $options);

        $response = curl_exec($curl);

        if (curl_errno($curl)) {
            throw new \Exception('cURL Error: ' . curl_error($curl));
        }

        $httpCode = curl_getinfo($curl, CURLINFO_HTTP_CODE);
        curl_close($curl);

        if ($httpCode >= 400) {
            throw new \Exception('HTTP Error: ' . $httpCode . ' - ' . $response);
        }

        return json_decode($response, true);
    }


    /**
     * Set advert schedules to gh3sp_adverts table
     * @param $input
     * @param $images
     * @param $advert_id
     * @param $car_id
     * @return void
     */
    public function setAdvertSchedules($input, $images, $advert_id, $car_id)
    {
        $scheduleTypes = [
            'basic' => Schedules::basic,
            'lite' => Schedules::lite,
            'plus' => Schedules::plus,
            'turbo' => Schedules::turbo
        ];

        $promotionType = $input['promotions'];
        $schedule = $scheduleTypes[$promotionType] ?? [];

        $currentDatetime = (new DateTime())->format('Y-m-d H:i:s');

        $lastShiftMinutes = (new Car())->getLastTimeShift()['time_shift'] ?? null;
        $lastShiftSequence = (new Car())->getLastTimeShift()['shift_sequence'] ?? 0;
        $nextShift = (new Schedules())->getNextShiftMinutes($lastShiftMinutes);

        $newNextShift = ($lastShiftSequence > 60) ? $nextShift : $lastShiftSequence + $nextShift;

        $stmt = $this->db->prepare("
            UPDATE gh3sp_car_ctlg
            SET `time_shift` = :timeShift, `shift_sequence` = :shiftSequence
            WHERE id = :carId
        ");
        $stmt->execute([
            ':carId' => $car_id,
            ':timeShift' => $nextShift,
            ':shiftSequence' => $newNextShift
        ]);

        foreach ($schedule as $type => $shifts) {
            $nextDatetime = (new Schedules())->getNextPublishDatetime($currentDatetime, $shifts, $newNextShift);

            $rotatableImages = array_slice($images, 0, 5);
            $staticImages = array_slice($images, 5);
            $firstImage = array_shift($rotatableImages);
            $rotatableImages[] = $firstImage;

            $images = array_merge($rotatableImages, $staticImages);

            $data = [
                ':car_id' => $car_id,
                ':type' => $type,
                ':publish_datetime' => $nextDatetime,
                ':shift_sequence' => $newNextShift,
                ':images' => json_encode($images),
            ];

            if ($type === 'base') {
                $data[':999_id'] = $advert_id;
                $data[':published'] = true;

                $this->db->prepare("
                INSERT INTO gh3sp_adverts (car_id, type, publish_datetime, shift_sequence, images, 999_id, published)
                VALUES (:car_id, :type, :publish_datetime, :shift_sequence, :images, :999_id, :published)")
                    ->execute($data);
            } else {
                $this->db->prepare("
                INSERT INTO gh3sp_adverts (car_id, type, publish_datetime, shift_sequence, images)
                VALUES (:car_id, :type, :publish_datetime, :shift_sequence, :images)")
                    ->execute($data);
            }
        }
    }

    public function setTestAdvertSchedules($images, $advert_id, $car_id)
    {
        $minutes = 3;
        foreach (Schedules::test as $type => $shifts)
        {
            $currentDatetime = new DateTime();
            $currentDatetime->modify('+' . $minutes . ' minutes');

            $rotatableImages = array_slice($images, 0, 5);
            $staticImages = array_slice($images, 5);
            $firstImage = array_shift($rotatableImages);
            $rotatableImages[] = $firstImage;

            $images = array_merge($rotatableImages, $staticImages);

            $data = [
                ':car_id' => $car_id,
                ':type' => $type,
                ':publish_datetime' => $currentDatetime->format('Y-m-d H:i:s'),
                ':shift_sequence' => 5,
                ':images' => json_encode($images),
            ];

            if ($type === 'base') {
                $data[':999_id'] = $advert_id;
                $data[':published'] = true;

                $this->db->prepare("
                INSERT INTO gh3sp_adverts (car_id, type, publish_datetime, shift_sequence, images, 999_id, published)
                VALUES (:car_id, :type, :publish_datetime, :shift_sequence, :images, :999_id, :published)")
                    ->execute($data);
            } else {
                $this->db->prepare("
                INSERT INTO gh3sp_adverts (car_id, type, publish_datetime, shift_sequence, images)
                VALUES (:car_id, :type, :publish_datetime, :shift_sequence, :images)")
                    ->execute($data);
            }

            $minutes+=3;
        }
    }

    private static function logRequest($url, $method, $options = [], $requestBody = null, $response = null, $httpCode = null, $error = null)
    {
        $logMessage = "cURL Request:\n";
        $logMessage .= "URL: " . $url . "\n";
        $logMessage .= "Method: " . $method . "\n";
        $logMessage .= "Options: " . print_r($options, true) . "\n";
        if ($requestBody !== null) {
            $logMessage .= "Request Body:\n" . $requestBody . "\n";
        }
        if ($response !== null) {
            $logMessage .= "Response Code: " . $httpCode . "\n";
            $logMessage .= "Response Body:\n" . $response . "\n";
        }
        if ($error !== null) {
            $logMessage .= "Error: " . $error . "\n";
        }
        $logMessage .= "---------------------------------------------------\n";
        __log($logMessage, 'curl.log');
        //file_put_contents('curl.log', $logMessage, FILE_APPEND);
    }

}
