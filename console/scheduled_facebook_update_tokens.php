<?php

// Security check - only allow CLI execution or manual trigger
if (php_sapi_name() !== 'cli' && !defined('MANUAL_CRON_TRIGGER')) {
    http_response_code(403);
    die('This script can only be run from command line or with proper access');
}

// Set error reporting
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Set timezone
date_default_timezone_set('Europe/Chisinau');



require_once 'FacebookTokenManager.php';

try {
    // Database connection using environment settings
    require_once __DIR__ . '/../environment.php';

    $db = new PDO(
        'mysql:host=' . SQL_HOST . ';dbname=' . SQL_DB . ';charset=' . SQL_CHARSET,
        SQL_USER,
        SQL_PASS,
        [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
        ]
    );

    $prefx = 'gh3sp';


    // Get Facebook settings
    $settings = [];
    $stmt = $db->prepare("SELECT name, value FROM {$prefx}_settings WHERE name LIKE '%facebook%'");
    $stmt->execute();
    while ($row = $stmt->fetch()) {
        $settings[$row['name']] = $row['value'];
    }

} catch (Exception $e) {
    echo "ERROR: " . $e->getMessage() . "\n";
    exit(10);
}


try {
    // location_1_facebook_token
    $manager = new FacebookTokenManager(array(
        'app_id' => '1082088863732549',
        'app_secret' => '77368f52ab263907ee1fe3ea72909289',
        'graph_version' => 'v22.0',
        'page_ids' => array(
            '725963964220309',
        ),
        'http_timeout' => 20
    ));

    // location_1_facebook_page_id - 725963964220309

    $userToken = isset($settings['location_1_facebook_user_token']) ? $settings['location_1_facebook_user_token'] : '';
    $pageTokensRaw = isset($settings['location_1_facebook_token']) ? $settings['location_1_facebook_token'] : '';
    $pageTokens = !empty($pageTokensRaw) ? json_decode($pageTokensRaw, true) : array();
    if (!is_array($pageTokens)) $pageTokens = array();

    if (!$userToken) {
        echo "ERROR: user_access_token is empty (DB)\n";
        exit(2);
    }

    /* 4.1) Смотрим статус токенов */
    $status = $manager->getTokenStatus($userToken, $pageTokens);

    echo '<pre>';
        var_dump( $status);
    echo '</pre>';

    if (!$status['user'] || !$status['user']['is_valid']) {
            /*
            ВАЖНО:
            refreshTokens() не спасёт, если user token уже невалиден/отозван/истёк.
            Нужно вручную получить новый user token (login/Graph API Explorer).
            */
        echo "ERROR: user token invalid, need manual re-login\n";
        exit(3);
    }

    /* 4.2) Проверка “за 7 дней до истечения” */
    $sevenDaysSeconds = 7 * 86400;
    $needRefreshUser = false;

    if ($status['user']['seconds_left'] !== null) {
        /* seconds_left может быть null если expires_at не возвращается */
        if ($status['user']['seconds_left'] < $sevenDaysSeconds) {
            $needRefreshUser = true;
        }
    }

    /* 4.3) Если page token отсутствует или невалиден — считаем что его надо “переполучить” */
    $pageTokensClean = $pageTokens;

    if (isset($status['pages']) && is_array($status['pages'])) {
        foreach ($status['pages'] as $pageId => $p) {
            $present = isset($p['present']) ? (bool)$p['present'] : false;
            $valid   = isset($p['is_valid']) ? (bool)$p['is_valid'] : false;

            if (!$present || !$valid) {
                /* удаляем — ensurePageTokens() подтянет заново */
                if (isset($pageTokensClean[$pageId])) {
                    unset($pageTokensClean[$pageId]);
                }
            }
        }
    }

    echo '<pre>';
        var_dump( $needRefreshUser);
    echo '</pre>';

    /* 4.4) Выполняем нужное действие */
    if ($needRefreshUser) {
        /*
        Обновит user token (exchange) и получит новые page tokens для всех page_ids
        */
        $newTokens = $manager->refreshTokens($userToken);

        $newUserToken = $newTokens['user_access_token'];
        $newPageTokens = $newTokens['pages'];

        /* сохраняем в БД */
        // saveTokensToDb($newUserToken, $newPageTokens);
        try {

            $stmt = $db->prepare("
                UPDATE {$prefx}_settings  
                SET value = :value 
                WHERE name = :name 
            ");
            $stmt->execute([ 'name' => 'location_1_facebook_token', 'value' => json_encode($newPageTokens) ]);

            $stmt = $db->prepare("
                UPDATE {$prefx}_settings  
                SET value = '". $newUserToken ."' 
                WHERE name = :name 
            ");
            $stmt->execute([ 'name' => 'location_1_facebook_user_token' ]);

        } catch (Exception $e) {
            // echo "[" . date('Y-m-d H:i:s') . "] Fatal Error: " . $e->getMessage() . "\n";
            // exit(1);
        }

        echo "OK: refreshed user + pages\n";
        echo "user_token_changed=" . (($newUserToken !== $userToken) ? "yes" : "no") . "\n";
        exit(0);

    }
    else {
        /*
          user token ещё живой: просто убедимся, что page tokens на месте,
          и переполучим только те, которых нет/которые невалидны
        */
        $ensured = $manager->ensurePageTokens($userToken, $pageTokensClean);

        $ensuredUserToken = $ensured['user_access_token']; /* будет тот же */
        $ensuredPageTokens = $ensured['pages'];

        /*
          Тут 2 варианта:
          - сохранять всегда (проще)
          - сохранять только если что-то изменилось
          Я сделаю “сохранять всегда”
        */
        // saveTokensToDb($ensuredUserToken, $ensuredPageTokens);
        try {

            $stmt = $db->prepare("
                UPDATE {$prefx}_settings  
                SET value = :value 
                WHERE name = :name 
            ");
            $stmt->execute([ 'name' => 'location_1_facebook_token', 'value' => json_encode($ensuredPageTokens) ]);

        } catch (Exception $e) {
            // echo "[" . date('Y-m-d H:i:s') . "] Fatal Error: " . $e->getMessage() . "\n";
            // exit(1);
        }

        echo "OK: no user refresh, ensured pages\n";
        exit(0);
    }

} catch (Exception $e) {
    echo "ERROR: " . $e->getMessage() . "\n";
    exit(10);
}


unset( $manager);



try {
    // location_2_facebook_token
    $manager = new FacebookTokenManager(array(
        'app_id' => '1082088863732549',
        'app_secret' => '77368f52ab263907ee1fe3ea72909289',
        'graph_version' => 'v22.0',
        'page_ids' => array(
            '482777831588669',
        ),
        'http_timeout' => 20
    ));

    // location_2_facebook_token - 482777831588669

    $userToken = isset($settings['location_1_facebook_user_token']) ? $settings['location_1_facebook_user_token'] : '';
    $pageTokensRaw = isset($settings['location_2_facebook_token']) ? $settings['location_2_facebook_token'] : '';
    $pageTokens = !empty($pageTokensRaw) ? json_decode($pageTokensRaw, true) : array();
    if (!is_array($pageTokens)) $pageTokens = array();

    if (!$userToken) {
        echo "ERROR: user_access_token is empty (DB)\n";
        exit(2);
    }

    /* 4.1) Смотрим статус токенов */
    $status = $manager->getTokenStatus($userToken, $pageTokens);

    echo '<pre>';
    var_dump( $status);
    echo '</pre>';

    if (!$status['user'] || !$status['user']['is_valid']) {
        /*
        ВАЖНО:
        refreshTokens() не спасёт, если user token уже невалиден/отозван/истёк.
        Нужно вручную получить новый user token (login/Graph API Explorer).
        */
        echo "ERROR: user token invalid, need manual re-login\n";
        exit(3);
    }

    /* 4.2) Проверка “за 7 дней до истечения” */
    $sevenDaysSeconds = 7 * 86400;
    $needRefreshUser = false;

    if ($status['user']['seconds_left'] !== null) {
        /* seconds_left может быть null если expires_at не возвращается */
        if ($status['user']['seconds_left'] < $sevenDaysSeconds) {
            $needRefreshUser = true;
        }
    }

    /* 4.3) Если page token отсутствует или невалиден — считаем что его надо “переполучить” */
    $pageTokensClean = $pageTokens;

    if (isset($status['pages']) && is_array($status['pages'])) {
        foreach ($status['pages'] as $pageId => $p) {
            $present = isset($p['present']) ? (bool)$p['present'] : false;
            $valid   = isset($p['is_valid']) ? (bool)$p['is_valid'] : false;

            if (!$present || !$valid) {
                /* удаляем — ensurePageTokens() подтянет заново */
                if (isset($pageTokensClean[$pageId])) {
                    unset($pageTokensClean[$pageId]);
                }
            }
        }
    }

    echo '<pre>';
    var_dump( $needRefreshUser);
    echo '</pre>';

    /* 4.4) Выполняем нужное действие */
    if ($needRefreshUser) {
        /*
        Обновит user token (exchange) и получит новые page tokens для всех page_ids
        */
        $newTokens = $manager->refreshTokens($userToken);

        $newUserToken = $newTokens['user_access_token'];
        $newPageTokens = $newTokens['pages'];

        /* сохраняем в БД */
        // saveTokensToDb($newUserToken, $newPageTokens);
        try {

            $stmt = $db->prepare("
                UPDATE {$prefx}_settings  
                SET value = :value 
                WHERE name = :name 
            ");
            $stmt->execute([ 'name' => 'location_2_facebook_token', 'value' => json_encode($newPageTokens) ]);

            $stmt = $db->prepare("
                UPDATE {$prefx}_settings  
                SET value = '". $newUserToken ."' 
                WHERE name = :name 
            ");
            $stmt->execute([ 'name' => 'location_1_facebook_user_token' ]);

        } catch (Exception $e) {
            // echo "[" . date('Y-m-d H:i:s') . "] Fatal Error: " . $e->getMessage() . "\n";
            // exit(1);
        }

        echo "OK: refreshed user + pages\n";
        echo "user_token_changed=" . (($newUserToken !== $userToken) ? "yes" : "no") . "\n";
        exit(0);

    }
    else {
        /*
          user token ещё живой: просто убедимся, что page tokens на месте,
          и переполучим только те, которых нет/которые невалидны
        */
        $ensured = $manager->ensurePageTokens($userToken, $pageTokensClean);

        $ensuredUserToken = $ensured['user_access_token']; /* будет тот же */
        $ensuredPageTokens = $ensured['pages'];

        /*
          Тут 2 варианта:
          - сохранять всегда (проще)
          - сохранять только если что-то изменилось
          Я сделаю “сохранять всегда”
        */
        // saveTokensToDb($ensuredUserToken, $ensuredPageTokens);
        try {

            $stmt = $db->prepare("
                UPDATE {$prefx}_settings  
                SET value = :value 
                WHERE name = :name 
            ");
            $stmt->execute([ 'name' => 'location_2_facebook_token', 'value' => json_encode($ensuredPageTokens) ]);

        } catch (Exception $e) {
            // echo "[" . date('Y-m-d H:i:s') . "] Fatal Error: " . $e->getMessage() . "\n";
            // exit(1);
        }

        echo "OK: no user refresh, ensured pages\n";
        exit(0);
    }

} catch (Exception $e) {
    echo "ERROR: " . $e->getMessage() . "\n";
    exit(10);
}

