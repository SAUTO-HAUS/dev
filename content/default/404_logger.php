<?php
defined('_DOIT') or die('Restricted access');

class Error404Logger {
    private static $logFile = null;
    private static $maxEntries = 10000;
    
    private static function initLogFile() {
        if (self::$logFile === null) {
            self::$logFile = $_SERVER['DOCUMENT_ROOT'] . '/logs/404_errors.json';
            $dir = dirname(self::$logFile);
            if (!is_dir($dir)) {
                mkdir($dir, 0755, true);
            }
        }
        return self::$logFile;
    }
    
    private static function isBot($userAgent) {
        $botPatterns = [
            'googlebot', 'bingbot', 'yandexbot', 'duckduckbot', 'slurp',
            'baiduspider', 'facebookexternalhit', 'twitterbot', 'rogerbot',
            'linkedinbot', 'embedly', 'quora link preview', 'showyoubot',
            'outbrain', 'pinterest', 'applebot', 'semrushbot', 'ahrefsbot',
            'mj12bot', 'dotbot', 'petalbot', 'bytespider', 'crawler', 'spider',
            'bot', 'crawl', 'slurp', 'mediapartners'
        ];
        
        $userAgentLower = strtolower($userAgent);
        foreach ($botPatterns as $pattern) {
            if (strpos($userAgentLower, $pattern) !== false) {
                return true;
            }
        }
        return false;
    }
    
    private static function anonymizeIp($ip) {
        if (filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4)) {
            return preg_replace('/\.\d+$/', '.xxx', $ip);
        }
        return preg_replace('/:[\da-f]+:[\da-f]+:[\da-f]+:[\da-f]+$/i', ':xxxx:xxxx:xxxx:xxxx', $ip);
    }
    
    private static function getUrlCategory($url) {
        if (preg_match('#/cars/(\d+)$#', $url)) return 'car_id';
        if (preg_match('#/cars/[a-z]+-[a-z]+-\d+#i', $url)) return 'car_old_format';
        if (preg_match('#/cars/[a-z-]+$#i', $url)) return 'car_brand_model';
        if (preg_match('#/ordercars/#', $url)) return 'ordercars';
        if (preg_match('#/tyres/#', $url)) return 'tyres';
        if (preg_match('#\.(jpg|png|gif|webp|svg|css|js)#i', $url)) return 'static_file';
        if (preg_match('#/api/#', $url)) return 'api';
        return 'other';
    }
    
    public static function log() {
        $logFile = self::initLogFile();
        
        $userAgent = $_SERVER['HTTP_USER_AGENT'] ?? 'Unknown';
        $referer = $_SERVER['HTTP_REFERER'] ?? '';
        $requestUri = $_SERVER['REQUEST_URI'] ?? '';
        $ip = $_SERVER['REMOTE_ADDR'] ?? '';
        
        $entry = [
            'timestamp' => date('Y-m-d H:i:s'),
            'date' => date('Y-m-d'),
            'url' => $requestUri,
            'url_category' => self::getUrlCategory($requestUri),
            'referer' => $referer,
            'referer_domain' => !empty($referer) ? parse_url($referer, PHP_URL_HOST) : '',
            'user_agent' => substr($userAgent, 0, 200),
            'is_bot' => self::isBot($userAgent),
            'ip_hash' => md5(self::anonymizeIp($ip)),
            'lang' => $_COOKIE['lang'] ?? 'unknown'
        ];
        
        $logs = [];
        if (file_exists($logFile)) {
            $content = file_get_contents($logFile);
            if (!empty($content)) {
                $logs = json_decode($content, true) ?: [];
            }
        }
        
        $found = false;
        foreach ($logs as &$existingLog) {
            if (($existingLog['url'] ?? '') === $entry['url']) {
                $existingLog['count'] = ($existingLog['count'] ?? 1) + 1;
                $existingLog['last_access'] = $entry['timestamp'];
                $found = true;
                break;
            }
        }
        unset($existingLog);
        
        if (!$found) {
            $entry['count'] = 1;
            $logs[] = $entry;
        }
        
        if (count($logs) > self::$maxEntries) {
            $logs = array_slice($logs, -self::$maxEntries);
        }
        
        file_put_contents($logFile, json_encode($logs, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
    }
    
    public static function getStats($days = 7) {
        $logFile = self::initLogFile();
        
        if (!file_exists($logFile)) {
            return ['error' => 'No log file found'];
        }
        
        $content = file_get_contents($logFile);
        $logs = json_decode($content, true) ?: [];
        
        $cutoffDate = date('Y-m-d', strtotime("-{$days} days"));
        
        $recentLogs = array_filter($logs, function($entry) use ($cutoffDate) {
            return ($entry['date'] ?? '') >= $cutoffDate;
        });
        
        $stats = [
            'total_404' => 0,
            'unique_urls' => [],
            'by_category' => [],
            'by_referer_domain' => [],
            'by_date' => [],
            'bots_vs_users' => ['bots' => 0, 'users' => 0],
            'top_urls' => [],
            'top_referers' => []
        ];
        
        foreach ($recentLogs as $entry) {
            $count = $entry['count'] ?? 1;
            $stats['total_404'] += $count;
            
            $url = $entry['url'] ?? '';
            if (!isset($stats['unique_urls'][$url])) {
                $stats['unique_urls'][$url] = 0;
            }
            $stats['unique_urls'][$url] += $count;
            
            $cat = $entry['url_category'] ?? 'other';
            if (!isset($stats['by_category'][$cat])) {
                $stats['by_category'][$cat] = 0;
            }
            $stats['by_category'][$cat] += $count;
            
            $refDomain = $entry['referer_domain'] ?? 'direct';
            if (empty($refDomain)) $refDomain = 'direct';
            if (!isset($stats['by_referer_domain'][$refDomain])) {
                $stats['by_referer_domain'][$refDomain] = 0;
            }
            $stats['by_referer_domain'][$refDomain] += $count;
            
            $date = $entry['date'] ?? 'unknown';
            if (!isset($stats['by_date'][$date])) {
                $stats['by_date'][$date] = 0;
            }
            $stats['by_date'][$date] += $count;
            
            if ($entry['is_bot'] ?? false) {
                $stats['bots_vs_users']['bots']++;
            } else {
                $stats['bots_vs_users']['users']++;
            }
        }
        
        arsort($stats['unique_urls']);
        $stats['top_urls'] = array_slice($stats['unique_urls'], 0, 20, true);
        
        arsort($stats['by_referer_domain']);
        $stats['top_referers'] = array_slice($stats['by_referer_domain'], 0, 10, true);
        arsort($stats['by_category']);
        ksort($stats['by_date']);
        unset($stats['unique_urls']);
        
        return $stats;
    }
    
    public static function getRawLogs($limit = 100, $offset = 0) {
        $logFile = self::initLogFile();
        
        if (!file_exists($logFile)) {
            return [];
        }
        
        $content = file_get_contents($logFile);
        $logs = json_decode($content, true) ?: [];
        $logs = array_reverse($logs);
        
        return array_slice($logs, $offset, $limit);
    }
    
    public static function cleanup($daysToKeep = 30) {
        $logFile = self::initLogFile();
        
        if (!file_exists($logFile)) {
            return 0;
        }
        
        $content = file_get_contents($logFile);
        $logs = json_decode($content, true) ?: [];
        
        $cutoffDate = date('Y-m-d', strtotime("-{$daysToKeep} days"));
        $originalCount = count($logs);
        
        $logs = array_filter($logs, function($entry) use ($cutoffDate) {
            return ($entry['date'] ?? '') >= $cutoffDate;
        });
        
        $logs = array_values($logs);
        file_put_contents($logFile, json_encode($logs, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
        
        return $originalCount - count($logs);
    }
}
