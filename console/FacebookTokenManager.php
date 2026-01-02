<?php
class FacebookTokenManager
{
    private $appId;
    private $appSecret;
    private $graphVersion;
    private $pageIds;      /* array(string) */
    private $httpTimeout;  /* int */

    public function __construct($options)
    {
        /* обязательные параметры */
        $this->appId        = isset($options['app_id']) ? $options['app_id'] : null;
        $this->appSecret    = isset($options['app_secret']) ? $options['app_secret'] : null;
        $this->graphVersion = isset($options['graph_version']) ? $options['graph_version'] : 'v23.0';

        /* список страниц */
        $pageIds = isset($options['page_ids']) && is_array($options['page_ids']) ? $options['page_ids'] : array();
        $this->pageIds = $this->normalizePageIds($pageIds);

        /* таймаут */
        $this->httpTimeout = isset($options['http_timeout']) ? (int)$options['http_timeout'] : 20;

        if (!$this->appId || !$this->appSecret) {
            throw new Exception('Missing app_id or app_secret');
        }
        if (count($this->pageIds) < 1) {
            throw new Exception('page_ids is empty');
        }
    }

    /* =========================
       Публичные методы
       ========================= */

    public function getTokenStatus($userAccessToken, $pageTokensById)
    {
        /*
          Возвращает статусы:
          - user: is_valid, expires_at, seconds_left
          - pages[pageId]: is_valid, expires_at, seconds_left, present
        */

        $result = array(
            'user'  => null,
            'pages' => array()
        );

        if (!$userAccessToken) {
            $result['user'] = array(
                'present'      => false,
                'is_valid'     => false,
                'expires_at'   => 0,
                'seconds_left' => null
            );
        } else {
            $ud = $this->debugToken($userAccessToken);
            $expiresAt = isset($ud['expires_at']) ? (int)$ud['expires_at'] : 0;

            $result['user'] = array(
                'present'      => true,
                'is_valid'     => isset($ud['is_valid']) ? (bool)$ud['is_valid'] : false,
                'expires_at'   => $expiresAt,
                'seconds_left' => ($expiresAt > 0) ? ($expiresAt - time()) : null
            );
        }

        /* нормализуем входной массив page токенов */
        if (!is_array($pageTokensById)) {
            $pageTokensById = array();
        }

        foreach ($this->pageIds as $pid) {
            $token = isset($pageTokensById[$pid]) ? $pageTokensById[$pid] : null;

            if (!$token) {
                $result['pages'][$pid] = array(
                    'present'      => false,
                    'is_valid'     => false,
                    'expires_at'   => 0,
                    'seconds_left' => null
                );
                continue;
            }

            $pd = $this->debugToken($token);
            $expiresAt = isset($pd['expires_at']) ? (int)$pd['expires_at'] : 0;

            $result['pages'][$pid] = array(
                'present'      => true,
                'is_valid'     => isset($pd['is_valid']) ? (bool)$pd['is_valid'] : false,
                'expires_at'   => $expiresAt,
                'seconds_left' => ($expiresAt > 0) ? ($expiresAt - time()) : null
            );
        }

        return $result;
    }

    public function ensurePageTokens($userAccessToken, $pageTokensById)
    {
        /*
          НИЧЕГО НЕ "ОБНОВЛЯЕТ".
          Этот метод только:
          - проверяет валидность user token
          - если для каких-то страниц нет page token (или передал пусто) — получает их через /me/accounts
          Возвращает массив токенов, чтобы сохранил в БД.
        */

        if (!$userAccessToken) {
            throw new Exception('User access token is empty');
        }

        /* проверяем user token */
        $ud = $this->debugToken($userAccessToken);
        if (!isset($ud['is_valid']) || !$ud['is_valid']) {
            throw new Exception('User token invalid. Нужен новый user token через Facebook Login/Graph API Explorer.');
        }

        if (!is_array($pageTokensById)) {
            $pageTokensById = array();
        }

        /* смотрим, каких страниц не хватает */
        $missing = false;
        foreach ($this->pageIds as $pid) {
            if (!isset($pageTokensById[$pid]) || !$pageTokensById[$pid]) {
                $missing = true;
                break;
            }
        }

        if ($missing) {
            $fresh = $this->fetchPageTokensByMeAccounts($userAccessToken);

            foreach ($this->pageIds as $pid) {
                if (!isset($fresh[$pid]) || !$fresh[$pid]) {
                    throw new Exception(
                        'Page ID not found in /me/accounts response: ' . $pid .
                        '. Проверь доступ к странице и permissions (pages_show_list, pages_manage_posts, pages_read_engagement).'
                    );
                }

                /* дополняем/перезаписываем недостающие */
                $pageTokensById[$pid] = $fresh[$pid];
            }
        }

        return array(
            'user_access_token' => $userAccessToken,
            'pages' => $this->filterOnlyConfiguredPages($pageTokensById)
        );
    }

    public function refreshTokens($userAccessToken)
    {
        /*
          РУЧНОЕ обновление:
          1) проверяет, что текущий user token валиден
          2) делает exchange -> новый (long-lived) user token
          3) заново получает page tokens для всех pageIds через /me/accounts
          Возвращает массив токенов, чтобы сохранил в БД
        */

        if (!$userAccessToken) {
            throw new Exception('User access token is empty');
        }

        /* текущий user token должен быть валиден, иначе exchange не спасает */
        $ud = $this->debugToken($userAccessToken);
        if (!isset($ud['is_valid']) || !$ud['is_valid']) {
            throw new Exception('User token invalid. refreshTokens() не поможет — нужен новый user token через Facebook Login/Graph API Explorer.');
        }

        /* exchange */
        $ex = $this->exchangeForLongLivedUserToken($userAccessToken);
        if (!isset($ex['access_token']) || !$ex['access_token']) {
            throw new Exception('Exchange did not return access_token');
        }

        $newUserToken = $ex['access_token'];

        /* получаем page токены заново */
        $pageTokens = $this->fetchPageTokensByMeAccounts($newUserToken);

        foreach ($this->pageIds as $pid) {
            if (!isset($pageTokens[$pid]) || !$pageTokens[$pid]) {
                throw new Exception(
                    'Page ID not found in /me/accounts response: ' . $pid .
                    '. Проверь доступ к странице и permissions.'
                );
            }
        }

        return array(
            'user_access_token' => $newUserToken,
            'pages' => $this->filterOnlyConfiguredPages($pageTokens)
        );
    }

    /* =========================
       Graph API helpers
       ========================= */

    private function graphBase()
    {
        return 'https://graph.facebook.com/' . $this->graphVersion;
    }

    private function debugToken($token)
    {
        /*
          /debug_token:
          access_token = app_id|app_secret
        */

        $url = 'https://graph.facebook.com/debug_token?' . http_build_query(array(
                'input_token'  => $token,
                'access_token' => $this->appId . '|' . $this->appSecret
            ), '', '&');

        $resp = $this->httpGetJson($url);

        if (!isset($resp['data']) || !is_array($resp['data'])) {
            throw new Exception('debug_token: missing data field');
        }

        return $resp['data'];
    }

    private function exchangeForLongLivedUserToken($currentUserToken)
    {
        /*
          GET /oauth/access_token
          grant_type=fb_exchange_token
        */

        $url = $this->graphBase() . '/oauth/access_token?' . http_build_query(array(
                'grant_type'        => 'fb_exchange_token',
                'client_id'         => $this->appId,
                'client_secret'     => $this->appSecret,
                'fb_exchange_token' => $currentUserToken
            ), '', '&');

        return $this->httpGetJson($url);
    }

    private function fetchPageTokensByMeAccounts($userAccessToken)
    {
        /*
          GET /me/accounts?fields=id,access_token...
          может быть пагинация, поэтому ходим по paging.next пока не найдём все нужные страницы
        */

        $need = array();
        foreach ($this->pageIds as $pid) {
            $need[$pid] = true;
        }

        $found = array();

        $url = $this->graphBase() . '/me/accounts?' . http_build_query(array(
                'fields'       => 'id,name,access_token,tasks',
                'access_token' => $userAccessToken
            ), '', '&');

        while ($url) {
            $resp = $this->httpGetJson($url);

            if (isset($resp['data']) && is_array($resp['data'])) {
                foreach ($resp['data'] as $page) {
                    if (isset($page['id']) && isset($page['access_token'])) {
                        $pid = (string)$page['id'];

                        if (isset($need[$pid])) {
                            $found[$pid] = $page['access_token'];
                        }
                    }
                }
            }

            /* если нашли все нужные — выходим */
            $allFound = true;
            foreach ($need as $pid => $v) {
                if (!isset($found[$pid])) {
                    $allFound = false;
                    break;
                }
            }
            if ($allFound) {
                break;
            }

            /* paging.next */
            $nextUrl = null;
            if (isset($resp['paging']) && is_array($resp['paging']) && isset($resp['paging']['next'])) {
                $nextUrl = $resp['paging']['next'];
            }

            $url = $nextUrl;
        }

        return $found;
    }

    private function httpGetJson($url)
    {
        $ch = curl_init();

        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, $this->httpTimeout);
        curl_setopt($ch, CURLOPT_TIMEOUT, $this->httpTimeout);

        $raw = curl_exec($ch);
        if ($raw === false) {
            $err = curl_error($ch);
            curl_close($ch);
            throw new Exception('cURL GET error: ' . $err);
        }

        $httpCode = (int)curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        $data = json_decode($raw, true);
        if (!is_array($data)) {
            throw new Exception('HTTP=' . $httpCode . ' response is not JSON. Raw=' . substr($raw, 0, 300));
        }

        if (isset($data['error'])) {
            $msg = isset($data['error']['message']) ? $data['error']['message'] : 'Unknown error';
            $code = isset($data['error']['code']) ? $data['error']['code'] : 0;
            $sub  = isset($data['error']['error_subcode']) ? $data['error']['error_subcode'] : 0;
            throw new Exception('Graph error: ' . $msg . ' (code=' . $code . ', subcode=' . $sub . ')');
        }

        return $data;
    }

    /* =========================
       Small helpers
       ========================= */

    private function normalizePageIds($pageIds)
    {
        $out = array();
        $seen = array();

        foreach ($pageIds as $pid) {
            $pid = trim((string)$pid);
            if ($pid === '') {
                continue;
            }
            if (isset($seen[$pid])) {
                continue;
            }
            $seen[$pid] = true;
            $out[] = $pid;
        }

        return $out;
    }

    private function filterOnlyConfiguredPages($pageTokensById)
    {
        /*
          возвращаем только токены тех страниц, которые указаны в page_ids
        */

        if (!is_array($pageTokensById)) {
            $pageTokensById = array();
        }

        $out = array();
        foreach ($this->pageIds as $pid) {
            if (isset($pageTokensById[$pid]) && $pageTokensById[$pid]) {
                $out[$pid] = $pageTokensById[$pid];
            }
        }
        return $out;
    }
}
