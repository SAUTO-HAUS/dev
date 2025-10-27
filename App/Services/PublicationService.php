<?php

namespace App\Services;

/**
 * Service for managing publication settings and operations
 */
class PublicationService
{
    private $db;
    private $prefx;

    public function __construct($database, $prefix = 'gh3sp')
    {
        $this->db = $database;
        $this->prefx = $prefix;
    }

    /**
     * Check if auto-publication is enabled for catalog type
     * 
     * @param string $catalogType 'in_stock' or 'on_order'
     * @return bool
     */
    public function isAutoPublishEnabled($catalogType)
    {
        $settingName = $catalogType === 'on_order' ? 'auto_publish_order' : 'auto_publish_regular';
        return $this->getSetting($settingName) == 1;
    }

    /**
     * Get Telegram settings for catalog type
     * 
     * @param string $catalogType 'in_stock' or 'on_order'
     * @return array|null
     */
    public function getTelegramSettings($catalogType)
    {
        $prefix = $catalogType === 'on_order' ? 'order' : 'regular';
        
        $botToken = $this->getSetting($prefix . '_telegram_bot_token');
        $chatId = $this->getSetting($prefix . '_telegram_chat_id');
        
        if (empty($botToken) || empty($chatId)) {
            return null;
        }
        
        return [
            'bot_token' => $botToken,
            'chat_id' => $chatId
        ];
    }

    /**
     * Get Facebook settings for catalog type
     * 
     * @param string $catalogType 'in_stock' or 'on_order'
     * @return array|null
     */
    public function getFacebookSettings($catalogType)
    {
        $prefix = $catalogType === 'on_order' ? 'order' : 'regular';
        
        $pageId = $this->getSetting($prefix . '_facebook_page_id');
        $token = $this->getSetting($prefix . '_facebook_token');
        
        if (empty($pageId) || empty($token)) {
            return null;
        }
        
        return [
            'page_id' => $pageId,
            'token' => $token
        ];
    }

    /**
     * Get 999.md API settings for catalog type
     * 
     * @param string $catalogType 'in_stock' or 'on_order'
     * @return array|null
     */
    public function get999mdSettings($catalogType)
    {
        $prefix = $catalogType === 'on_order' ? 'order' : 'regular';
        
        $account = $this->getSetting($prefix . '_999md_account');
        $token = $this->getSetting($prefix . '_999md_token');
        
        if (empty($account) || empty($token)) {
            return null;
        }
        
        return [
            'account' => $account,
            'token' => $token
        ];
    }

    /**
     * Generate Facebook message for car
     * 
     * @param array $carData Car data
     * @param string $catalogType 'in_stock' or 'on_order'
     * @return string
     */
    public function generateFacebookMessage($carData, $catalogType)
    {
        // Build car title
        $title = trim(($carData['br'] ?? '') . ' ' . ($carData['mo'] ?? ''));
        if (empty($title)) {
            $title = $carData['name'] ?? 'Автомобиль';
        }
        
        // Format price
        $price = '';
        if (!empty($carData['prc'])) {
            $price = number_format($carData['prc'], 0, '.', ' ') . ' €';
        }
        
        // Start message
        $message = "🔹 {$title}";
        if ($price) {
            $message .= " {$price}";
        }
        
        // Add catalog type label
        if ($catalogType === 'on_order') {
            $message .= "\n\n📋 <b>ПОД ЗАКАЗ</b>";
            $message .= "\n⏰ Срок поставки: " . ($carData['delivery_time'] ?? '2-4 недели');
        }
        
        // Add car details
        $details = [];
        if (!empty($carData['yr'])) {
            $details[] = "📅 " . $carData['yr'] . " г.";
        }
        if (!empty($carData['eng'])) {
            $details[] = "🔧 " . $carData['eng'] . "L";
        }
        if (!empty($carData['fuel'])) {
            $fuelTypes = [
                'petrol' => '⛽ Бензин',
                'diesel' => '🛢️ Дизель',
                'hybrid' => '🔋 Гибрид',
                'electric' => '⚡ Электро'
            ];
            $details[] = $fuelTypes[$carData['fuel']] ?? "🔧 " . $carData['fuel'];
        }
        if (!empty($carData['trans'])) {
            $transTypes = [
                'manual' => '🎛️ Механика',
                'automatic' => '🔄 Автомат',
                'cvt' => '🔄 Вариатор'
            ];
            $details[] = $transTypes[$carData['trans']] ?? "🎛️ " . $carData['trans'];
        }
        
        if (!empty($details)) {
            $message .= "\n\n" . implode(" | ", $details);
        }
        
        // Add description if available
        if (!empty($carData['desc']) && strlen(trim($carData['desc'])) > 10) {
            $description = strip_tags($carData['desc']);
            $description = substr($description, 0, 200);
            if (strlen($carData['desc']) > 200) {
                $description .= '...';
            }
            $message .= "\n\n📝 " . $description;
        }
        
        // Add link to car page
        if (!empty($carData['id'])) {
            $brand = str_replace('_', '-', strtolower($carData['br'] ?? ''));
            $model = str_replace('_', '-', strtolower($carData['mo'] ?? ''));
            $catalogPath = $catalogType === 'on_order' ? 'ordercars' : 'cars';
            
            $message .= "\n\n👉 Подробнее: https://www.sauto.md/ro/{$catalogPath}/{$brand}-{$model}?utm_source=social&utm_medium=facebook&utm_campaign=auto_post";
        }
        
        return $message;
    }

    /**
     * Log publication attempt
     * 
     * @param int $carId Car ID
     * @param string $catalogType Catalog type
     * @param string $channel Publication channel (telegram, facebook, 999md)
     * @param bool $success Success status
     * @param string $message Log message
     */
    public function logPublication($carId, $catalogType, $channel, $success, $message = '')
    {
        try {
            $stmt = $this->db->prepare("
                INSERT INTO {$this->prefx}_publication_log 
                (car_id, catalog_type, channel, success, message, created_at) 
                VALUES (?, ?, ?, ?, ?, NOW())
            ");
            $stmt->execute([$carId, $catalogType, $channel, $success ? 1 : 0, $message]);
        } catch (\PDOException $e) {
            // If table doesn't exist, create it
            $this->createPublicationLogTable();
            // Try again
            try {
                $stmt = $this->db->prepare("
                    INSERT INTO {$this->prefx}_publication_log 
                    (car_id, catalog_type, channel, success, message, created_at) 
                    VALUES (?, ?, ?, ?, ?, NOW())
                ");
                $stmt->execute([$carId, $catalogType, $channel, $success ? 1 : 0, $message]);
            } catch (\PDOException $e2) {
                error_log("PublicationService: Failed to log publication - " . $e2->getMessage());
            }
        }
    }

    /**
     * Get setting value from database
     * 
     * @param string $name Setting name
     * @return string|null
     */
    private function getSetting($name)
    {
        try {
            $stmt = $this->db->prepare("SELECT value FROM {$this->prefx}_settings WHERE name = ? LIMIT 1");
            $stmt->execute([$name]);
            $result = $stmt->fetch(\PDO::FETCH_ASSOC);
            return $result ? $result['value'] : null;
        } catch (\PDOException $e) {
            error_log("PublicationService: Failed to get setting {$name} - " . $e->getMessage());
            return null;
        }
    }

    /**
     * Create publication log table if it doesn't exist
     */
    private function createPublicationLogTable()
    {
        try {
            $sql = "
                CREATE TABLE IF NOT EXISTS {$this->prefx}_publication_log (
                    id INT AUTO_INCREMENT PRIMARY KEY,
                    car_id INT NOT NULL,
                    catalog_type VARCHAR(20) NOT NULL,
                    channel VARCHAR(20) NOT NULL,
                    success TINYINT(1) NOT NULL DEFAULT 0,
                    message TEXT,
                    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                    INDEX idx_car_id (car_id),
                    INDEX idx_catalog_type (catalog_type),
                    INDEX idx_channel (channel),
                    INDEX idx_created_at (created_at)
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
            ";
            $this->db->exec($sql);
        } catch (\PDOException $e) {
            error_log("PublicationService: Failed to create publication log table - " . $e->getMessage());
        }
    }

    /**
     * Get publication statistics
     * 
     * @param string $catalogType Optional catalog type filter
     * @param string $channel Optional channel filter
     * @param int $days Number of days to look back (default 30)
     * @return array
     */
    public function getPublicationStats($catalogType = null, $channel = null, $days = 30)
    {
        try {
            $where = ["created_at >= DATE_SUB(NOW(), INTERVAL ? DAY)"];
            $params = [$days];
            
            if ($catalogType) {
                $where[] = "catalog_type = ?";
                $params[] = $catalogType;
            }
            
            if ($channel) {
                $where[] = "channel = ?";
                $params[] = $channel;
            }
            
            $whereClause = implode(' AND ', $where);
            
            $stmt = $this->db->prepare("
                SELECT 
                    catalog_type,
                    channel,
                    COUNT(*) as total_attempts,
                    SUM(success) as successful,
                    COUNT(*) - SUM(success) as failed
                FROM {$this->prefx}_publication_log 
                WHERE {$whereClause}
                GROUP BY catalog_type, channel
                ORDER BY catalog_type, channel
            ");
            $stmt->execute($params);
            
            return $stmt->fetchAll(\PDO::FETCH_ASSOC);
        } catch (\PDOException $e) {
            error_log("PublicationService: Failed to get publication stats - " . $e->getMessage());
            return [];
        }
    }
}
