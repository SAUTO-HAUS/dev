<?php

namespace App\Services;

use App\Core\Container;
use Exception;

class MonitorService
{
    private $db;
    private $prefix;
    
    public function __construct()
    {
        $this->db = Container::get('db');
        $this->prefix = Container::get('prefix');
    }
    
    /**
     * Get complete system status
     * @return array 
     */
    public function getSystemStatus()
    {
        $status = [
            'status' => 'ok',
            'database' => 'unknown',
            'cars' => 'unknown',
            'leads' => 'unknown',
            'disk' => 'unknown',
            'publications' => 'unknown',
            'server_time' => date('d-m-Y H:i:s'),
            'last_error' => null,
            'details' => []
        ];
        
        try {
            $dbStatus = $this->checkDatabase();
            $status['database'] = $dbStatus['status'];
            $status['details']['database'] = $dbStatus;

            $carsStatus = $this->checkCars();
            $status['cars'] = $carsStatus['status'];
            $status['details']['cars'] = $carsStatus;

            $leadsStatus = $this->checkLeads();
            $status['leads'] = $leadsStatus['status'];
            $status['details']['leads'] = $leadsStatus;

            $diskStatus = $this->checkDiskSpace();
            $status['disk'] = $diskStatus['status'];
            $status['details']['disk'] = $diskStatus;

            $publicationStatus = $this->checkPublicationErrors();
            $status['publications'] = $publicationStatus['status'];
            $status['details']['publications'] = $publicationStatus;

            $lastError = $this->getLastError();
            $status['last_error'] = $lastError;
            $status['details']['last_error'] = $lastError;

            if ($status['database'] === 'error' || $status['cars'] === 'error' || $status['leads'] === 'error' || $status['disk'] === 'error' || $status['publications'] === 'error') {
                $status['status'] = 'error';
            } elseif ($status['database'] === 'warning' || $status['cars'] === 'warning' || $status['leads'] === 'warning' || $status['disk'] === 'warning' || $status['publications'] === 'warning') {
                $status['status'] = 'warning';
            } else {
                $status['status'] = 'ok';
            }
            
        } catch (Exception $e) {
            $status['status'] = 'error';
            $status['last_error'] = [
                'message' => $e->getMessage(),
                'time' => date('d-m-Y H:i:s')
            ];
        }
        
        return $status;
    }
    
    /**
     * Check database connection
     * @return array Database status
     */
    public function checkDatabase()
    {
        try {
            $startTime = microtime(true);

            $stmt = $this->db->query('SELECT 1 as test');
            $result = $stmt->fetch();
            
            $responseTime = round((microtime(true) - $startTime) * 1000, 2);
            
            if ($result && $result['test'] == 1) {
                return [
                    'status' => 'ok',
                    'message' => 'Соединение с БД активно',
                    'response_time' => $responseTime . ' ms'
                ];
            } else {
                return [
                    'status' => 'error',
                    'message' => 'Ошибка проверки БД',
                    'response_time' => $responseTime . ' ms'
                ];
            }
        } catch (Exception $e) {
            return [
                'status' => 'error',
                'message' => 'Ошибка подключения к БД: ' . $e->getMessage(),
                'response_time' => null
            ];
        }
    }
    
    /**
     * Check cars catalog status
     * @return array Cars status
     */
    public function checkCars()
    {
        try {
            $stmt = $this->db->query(
                'SELECT id, date, br_nm as brand, mo_nm as model FROM ' . $this->prefix . '_car_ctlg 
                WHERE act = 1 ORDER BY id DESC LIMIT 1'
            );
            $lastCar = $stmt->fetch();
            
            if (!$lastCar) {
                return [
                    'status' => 'warning',
                    'message' => 'Нет записей в каталоге',
                    'last_car' => null,
                    'last_date' => null
                ];
            }
            
            $lastDate = $lastCar['date'];
            $daysSinceLastCar = floor((time() - $lastDate) / 86400);

            if ($daysSinceLastCar > 7) {
                $statusType = 'warning';
                $message = 'Последняя машина добавлена ' . $daysSinceLastCar . ' дней назад';
            } else {
                $statusType = 'ok';
                $message = 'Каталог обновляется регулярно';
            }
            
            return [
                'status' => $statusType,
                'message' => $message,
                'last_car' => [
                    'id' => $lastCar['id'],
                    'brand' => $lastCar['brand'],
                    'model' => $lastCar['model']
                ],
                'last_date' => date('d-m-Y H:i:s', $lastDate),
                'days_ago' => $daysSinceLastCar
            ];
            
        } catch (Exception $e) {
            return [
                'status' => 'error',
                'message' => 'Ошибка проверки каталога: ' . $e->getMessage(),
                'last_car' => null,
                'last_date' => null
            ];
        }
    }
    
    /**
     * Check leads activity
     * @return array Leads status
     */
    public function checkLeads()
    {
        try {
            $stmt = $this->db->query(
                'SELECT id, date, folder FROM ' . $this->prefix . '_mail 
                ORDER BY id DESC LIMIT 1'
            );
            $lastLead = $stmt->fetch();
            
            if (!$lastLead) {
                return [
                    'status' => 'warning',
                    'message' => 'Нет записей лидов',
                    'last_lead' => null,
                    'last_date' => null
                ];
            }
            
            $lastDate = $lastLead['date'];
            $hoursSinceLastLead = floor((time() - $lastDate) / 3600);

            if ($hoursSinceLastLead > 48) {
                $statusType = 'warning';
                $message = 'Последний лид получен ' . $hoursSinceLastLead . ' часов назад';
            } elseif ($hoursSinceLastLead > 24) {
                $statusType = 'warning';
                $message = 'Последний лид получен ' . $hoursSinceLastLead . ' часов назад';
            } else {
                $statusType = 'ok';
                $message = 'Лиды поступают регулярно';
            }
            
            return [
                'status' => $statusType,
                'message' => $message,
                'last_lead' => [
                    'id' => $lastLead['id'],
                    'folder' => $lastLead['folder']
                ],
                'last_date' => date('d-m-Y H:i:s', $lastDate),
                'hours_ago' => $hoursSinceLastLead
            ];
            
        } catch (Exception $e) {
            return [
                'status' => 'error',
                'message' => 'Ошибка проверки лидов: ' . $e->getMessage(),
                'last_lead' => null,
                'last_date' => null
            ];
        }
    }
    
    /**
     * Get last 5 errors from log files
     * @return array|null Array of last 5 errors
     */
    public function getLastError()
    {
        try {
            $logFiles = [];
            $logDir = __DIR__ . '/../../logs/';
            
            if (is_dir($logDir)) {
                $files = scandir($logDir);
                foreach ($files as $file) {
                    if (preg_match('/^app\\.log\\d*$/', $file)) {
                        $logFiles[] = $logDir . $file;
                    }
                }
            }
            
            if (empty($logFiles)) {
                return null;
            }
            
            usort($logFiles, function($a, $b) {
                return filemtime($b) - filemtime($a);
            });
            
            $errors = [];
            foreach ($logFiles as $logFile) {
                if (file_exists($logFile) && filesize($logFile) > 0) {
                    $lines = file($logFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
                    if (!empty($lines)) {
                        $recentLines = array_slice($lines, -50);
                        foreach (array_reverse($recentLines) as $line) {
                            if (stripos($line, 'error') !== false || stripos($line, 'exception') !== false) {
                                $errors[] = [
                                    'message' => substr($line, 0, 200),
                                    'file' => basename($logFile),
                                    'time' => date('d-m-Y H:i:s', filemtime($logFile))
                                ];
                                
                                if (count($errors) >= 1) {
                                    break 2;
                                }
                            }
                        }
                    }
                }
            }
            
            return !empty($errors) ? $errors : null;
            
        } catch (Exception $e) {
            return [[
                'message' => 'Ошибка чтения логов: ' . $e->getMessage(),
                'file' => null,
                'time' => date('d-m-Y H:i:s')
            ]];
        }
    }
    
    /**
     * Check publication errors for 999.md, Facebook, and Telegram
     * @return array Publication errors status
     */
    public function checkPublicationErrors()
    {
        try {
            $errors = [
                '999' => ['failed' => 0, 'postponed' => 0, 'errors' => []],
                'facebook' => ['failed' => 0, 'pending' => 0, 'errors' => []],
                'telegram' => ['failed' => 0, 'pending' => 0, 'errors' => []]
            ];
            
            // Check 999.md (sauto_personal_schedules)
            $stmt = $this->db->query(
                "SELECT 
                    COUNT(CASE WHEN status = 'failed' THEN 1 END) as failed,
                    COUNT(CASE WHEN status = 'postponed' THEN 1 END) as postponed
                FROM gh3sp_sauto_personal_schedules
                WHERE created_at >= DATE_SUB(NOW(), INTERVAL 24 HOUR)"
            );
            $result999 = $stmt->fetch();
            if ($result999) {
                $errors['999']['failed'] = (int)$result999['failed'];
                $errors['999']['postponed'] = (int)$result999['postponed'];
            }
            
            // Get last 3 error messages for 999.md
            $stmt = $this->db->query(
                "SELECT error_message, created_at, car_id 
                FROM gh3sp_sauto_personal_schedules
                WHERE (status = 'failed' OR status = 'postponed') 
                AND error_message IS NOT NULL 
                AND error_message != ''
                AND created_at >= DATE_SUB(NOW(), INTERVAL 24 HOUR)
                ORDER BY created_at DESC
                LIMIT 3"
            );
            while ($row = $stmt->fetch()) {
                $errors['999']['errors'][] = [
                    'message' => $row['error_message'],
                    'time' => date('d-m-Y H:i:s', strtotime($row['created_at'])),
                    'car_id' => $row['car_id']
                ];
            }
            
            // Check Facebook scheduled posts
            $stmt = $this->db->query(
                "SELECT 
                    COUNT(CASE WHEN status = 'failed' THEN 1 END) as failed,
                    COUNT(CASE WHEN status = 'pending' THEN 1 END) as pending
                FROM " . $this->prefix . "_scheduled_facebook_posts
                WHERE created_at >= DATE_SUB(NOW(), INTERVAL 24 HOUR)"
            );
            $resultFb = $stmt->fetch();
            if ($resultFb) {
                $errors['facebook']['failed'] = (int)$resultFb['failed'];
                $errors['facebook']['pending'] = (int)$resultFb['pending'];
            }
            
            // Get last 3 error messages for Facebook
            $stmt = $this->db->query(
                "SELECT error_message, created_at, car_id 
                FROM " . $this->prefix . "_scheduled_facebook_posts
                WHERE status = 'failed' 
                AND error_message IS NOT NULL 
                AND error_message != ''
                AND created_at >= DATE_SUB(NOW(), INTERVAL 24 HOUR)
                ORDER BY created_at DESC
                LIMIT 3"
            );
            while ($row = $stmt->fetch()) {
                $errors['facebook']['errors'][] = [
                    'message' => $row['error_message'],
                    'time' => date('d-m-Y H:i:s', strtotime($row['created_at'])),
                    'car_id' => $row['car_id']
                ];
            }
            
            // Check Telegram scheduled posts
            $stmt = $this->db->query(
                "SELECT 
                    COUNT(CASE WHEN status = 'failed' THEN 1 END) as failed,
                    COUNT(CASE WHEN status = 'pending' THEN 1 END) as pending
                FROM " . $this->prefix . "_scheduled_telegram_posts
                WHERE created_at >= DATE_SUB(NOW(), INTERVAL 24 HOUR)"
            );
            $resultTg = $stmt->fetch();
            if ($resultTg) {
                $errors['telegram']['failed'] = (int)$resultTg['failed'];
                $errors['telegram']['pending'] = (int)$resultTg['pending'];
            }
            
            // Get last 3 error messages for Telegram
            $stmt = $this->db->query(
                "SELECT error_message, created_at, car_id 
                FROM " . $this->prefix . "_scheduled_telegram_posts
                WHERE status = 'failed' 
                AND error_message IS NOT NULL 
                AND error_message != ''
                AND created_at >= DATE_SUB(NOW(), INTERVAL 24 HOUR)
                ORDER BY created_at DESC
                LIMIT 3"
            );
            while ($row = $stmt->fetch()) {
                $errors['telegram']['errors'][] = [
                    'message' => $row['error_message'],
                    'time' => date('d-m-Y H:i:s', strtotime($row['created_at'])),
                    'car_id' => $row['car_id']
                ];
            }
            
            // Calculate total errors
            $totalFailed = $errors['999']['failed'] + $errors['facebook']['failed'] + $errors['telegram']['failed'];
            $totalPostponed = $errors['999']['postponed'];
            
            // Determine status
            if ($totalFailed > 10) {
                $statusType = 'error';
                $message = 'Много ошибок публикации!';
            } elseif ($totalFailed > 0 || $totalPostponed > 5) {
                $statusType = 'warning';
                $message = 'Есть ошибки публикации';
            } else {
                $statusType = 'ok';
                $message = 'Публикации работают нормально';
            }
            
            return [
                'status' => $statusType,
                'message' => $message,
                'total_failed' => $totalFailed,
                'total_postponed' => $totalPostponed,
                'details' => $errors
            ];
            
        } catch (Exception $e) {
            return [
                'status' => 'error',
                'message' => 'Ошибка проверки публикаций: ' . $e->getMessage(),
                'total_failed' => null,
                'total_postponed' => null,
                'details' => null
            ];
        }
    }
    
    /**
     * Check disk space
     * @return array Disk space status
     */
    public function checkDiskSpace()
    {
        try {
            $rootPath = $_SERVER['DOCUMENT_ROOT'];
            
            $totalSpace = disk_total_space($rootPath);
            $freeSpace = disk_free_space($rootPath);
            
            if ($totalSpace === false || $freeSpace === false) {
                return [
                    'status' => 'error',
                    'message' => 'Не удалось получить информацию о диске',
                    'total' => null,
                    'free' => null,
                    'used' => null,
                    'percent_free' => null
                ];
            }
            
            $usedSpace = $totalSpace - $freeSpace;
            $percentFree = ($freeSpace / $totalSpace) * 100;
            $percentUsed = 100 - $percentFree;
            
            if ($percentFree < 5) {
                $statusType = 'error';
                $message = 'Критически мало места на диске!';
            } elseif ($percentFree < 10) {
                $statusType = 'warning';
                $message = 'Мало места на диске';
            } else {
                $statusType = 'ok';
                $message = 'Достаточно свободного места';
            }
            
            return [
                'status' => $statusType,
                'message' => $message,
                'total' => $this->formatBytes($totalSpace),
                'free' => $this->formatBytes($freeSpace),
                'used' => $this->formatBytes($usedSpace),
                'percent_free' => round($percentFree, 2),
                'percent_used' => round($percentUsed, 2)
            ];
            
        } catch (Exception $e) {
            return [
                'status' => 'error',
                'message' => 'Ошибка проверки диска: ' . $e->getMessage(),
                'total' => null,
                'free' => null,
                'used' => null,
                'percent_free' => null
            ];
        }
    }
    
    /**
     * Check memory usage
     * @return array Memory usage status
     */
    public function checkMemoryUsage()
    {
        try {
            $memoryUsage = memory_get_usage(true);
            $memoryLimit = ini_get('memory_limit');
            
            // Convert memory_limit to bytes
            $memoryLimitBytes = $this->convertToBytes($memoryLimit);
            
            if ($memoryLimitBytes === -1) {
                return [
                    'status' => 'ok',
                    'message' => 'Лимит памяти не установлен',
                    'current' => $this->formatBytes($memoryUsage),
                    'limit' => 'Unlimited',
                    'percent_used' => 0
                ];
            }
            
            $percentUsed = ($memoryUsage / $memoryLimitBytes) * 100;
            
            // Determine status based on memory usage percentage
            if ($percentUsed > 95) {
                $statusType = 'error';
                $message = 'Критически высокое использование памяти!';
            } elseif ($percentUsed > 80) {
                $statusType = 'warning';
                $message = 'Высокое использование памяти';
            } else {
                $statusType = 'ok';
                $message = 'Использование памяти в норме';
            }
            
            return [
                'status' => $statusType,
                'message' => $message,
                'current' => $this->formatBytes($memoryUsage),
                'limit' => $memoryLimit,
                'percent_used' => round($percentUsed, 2),
                'available' => $this->formatBytes($memoryLimitBytes - $memoryUsage)
            ];
            
        } catch (Exception $e) {
            return [
                'status' => 'error',
                'message' => 'Ошибка проверки памяти: ' . $e->getMessage(),
                'current' => null,
                'limit' => null,
                'percent_used' => null
            ];
        }
    }
    
    /**
     * Convert PHP memory limit string to bytes
     * @param string $value
     * @return int
     */
    private function convertToBytes($value)
    {
        if ($value === '-1') {
            return -1;
        }
        
        $value = trim($value);
        $last = strtolower($value[strlen($value) - 1]);
        $value = (int) $value;
        
        switch ($last) {
            case 'g':
                $value *= 1024;
            case 'm':
                $value *= 1024;
            case 'k':
                $value *= 1024;
        }
        
        return $value;
    }
    
    /**
     * Format bytes to human readable format
     * @param int $bytes
     * @return string
     */
    private function formatBytes($bytes)
    {
        $units = ['B', 'KB', 'MB', 'GB', 'TB'];
        $bytes = max($bytes, 0);
        $pow = floor(($bytes ? log($bytes) : 0) / log(1024));
        $pow = min($pow, count($units) - 1);
        $bytes /= (1 << (10 * $pow));
        
        return round($bytes, 2) . ' ' . $units[$pow];
    }
    
    /**
     * Get server information
     * @return array Server info
     */
    public function getServerInfo()
    {
        return [
            'php_version' => PHP_VERSION,
            'server_time' => date('Y-m-d H:i:s'),
            'memory_usage' => round(memory_get_usage() / 1024 / 1024, 2) . ' MB',
            'memory_limit' => ini_get('memory_limit')
        ];
    }
}
