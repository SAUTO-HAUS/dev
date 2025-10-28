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
     * Get Facebook settings for car location or catalog type (backward compatibility)
     * 
     * @param array|string $carDataOrCatalogType Car data with location info OR catalog type string
     * @return array|null
     */
    public function getFacebookSettings($carDataOrCatalogType)
    {
        // Handle backward compatibility - if string passed, assume it's catalog type
        if (is_string($carDataOrCatalogType)) {
            // Old behavior: use catalog type to determine settings
            $catalogType = $carDataOrCatalogType;
            $prefix = $catalogType === 'on_order' ? 'order' : 'regular';
            
            $pageId = $this->getSetting($prefix . '_facebook_page_id');
            $token = $this->getSetting($prefix . '_facebook_token');
            
            // If old settings don't exist, try location-based settings
            if (empty($pageId) || empty($token)) {
                // Default: regular -> location 1, order -> location 2
                $locationId = $catalogType === 'on_order' ? 2 : 1;
                $pageId = $this->getSetting('location_' . $locationId . '_facebook_page_id');
                $token = $this->getSetting('location_' . $locationId . '_facebook_token');
            }
        } else {
            // New behavior: use car location data
            $carData = $carDataOrCatalogType;
            $locationId = $carData['loc'] ?? 1;
            
            $pageId = $this->getSetting('location_' . $locationId . '_facebook_page_id');
            $token = $this->getSetting('location_' . $locationId . '_facebook_token');
        }
        
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
        
        // Start with title
        $message = $title;
        
        // Add price
        if (!empty($carData['prc'])) {
            $price = number_format($carData['prc'], 0, '.', ',') . ' €';
            $message .= "\n" . $price;
        }
        
        // Add catalog type label for orders
        if ($catalogType === 'on_order') {
            $message .= "\n\n📋 LA COMANDĂ";
            $message .= "\n⏰ Termen de livrare: " . ($carData['delivery_time'] ?? '14 zile');
        }
        
        // Add detailed specifications with icons
        if (!empty($carData['yr'])) {
            $message .= "\n📅 An de fabricație: " . $carData['yr'];
        }
        
        // Body type
        if (!empty($carData['bt'])) {
            $bodyTypes = [
                1 => 'Sedan', 2 => 'Hatchback', 3 => 'Combi', 4 => 'Coupe', 
                5 => 'Cabriolet', 6 => 'SUV', 7 => 'Pickup', 8 => 'Minivan',
                9 => 'Limuzină', 10 => 'Roadster'
            ];
            $bodyType = $bodyTypes[$carData['bt']] ?? 'Necunoscut';
            $message .= "\n🚗 Tip caroserie: " . $bodyType;
        }
        
        // Mileage
        if (!empty($carData['mlg'])) {
            $unit = ($carData['unit'] ?? 'km') === 'mi' ? 'mile' : 'km';
            $mileage = number_format($carData['mlg'], 0, '.', ',');
            $message .= "\n📏 Parcurs: " . $mileage . ' ' . $unit;
        }
        
        // Engine capacity
        if (!empty($carData['vol'])) {
            $message .= "\n🔧 Capacitate motor: " . number_format($carData['vol'] * 1000, 0) . ' cm3';
        }
        
        // Power
        if (!empty($carData['hp'])) {
            $kw = round($carData['hp'] * 0.735);
            $message .= "\n⚡ Putere: " . $carData['hp'] . ' hp (' . $kw . ' kw)';
        }
        
        // Fuel type
        if (!empty($carData['fl'])) {
            $fuelTypes = [
                1 => 'Benzină', 2 => 'Diesel', 3 => 'Gaz', 4 => 'Hibrid',
                5 => 'Electric', 6 => 'Plug-in Hybrid', 7 => 'Etanol'
            ];
            $fuel = $fuelTypes[$carData['fl']] ?? 'Necunoscut';
            $message .= "\n⛽ Tip combustibil: " . $fuel;
        }
        
        // Transmission
        if (!empty($carData['tra'])) {
            $transTypes = [
                1 => 'Manuală', 2 => 'Automată', 3 => 'Semiautomată',
                4 => 'CVT', 5 => 'Robotizată'
            ];
            $transmission = $transTypes[$carData['tra']] ?? 'Necunoscut';
            $message .= "\n⚙️ Cutia de viteze: " . $transmission;
        }
        
        // Drive type
        if (!empty($carData['wd'])) {
            $driveTypes = [
                1 => 'Din față', 2 => 'Din spate', 3 => 'Integrală'
            ];
            $drive = $driveTypes[$carData['wd']] ?? 'Necunoscut';
            $message .= "\n🔄 Tip tracțiune: " . $drive;
        }
        
        // Color
        if (!empty($carData['clr'])) {
            $colors = [
                1 => 'Alb', 2 => 'Negru', 3 => 'Gri', 4 => 'Argintiu',
                5 => 'Roșu', 6 => 'Albastru', 7 => 'Verde', 8 => 'Galben',
                9 => 'Maro', 10 => 'Violet', 11 => 'Portocaliu', 12 => 'Bej'
            ];
            $color = $colors[$carData['clr']] ?? 'Altă culoare';
            $message .= "\n🎨 Culoare: " . $color;
        }
        
        // Seats
        if (!empty($carData['sts'])) {
            $message .= "\n👥 Numărul de locuri: " . $carData['sts'];
        }
        
        // Address based on location
        $addresses = [
            1 => 'Chișinău, str. Calea Moşilor 11',
            2 => 'Chișinău, str. Pietrăriei 3'
        ];
        $address = $addresses[$carData['loc'] ?? 1] ?? 'Chișinău';
        $message .= "\n📍 Adresă: " . $address;
        
        // Phone number
        $message .= "\n📞 +37379600361";
        
        // Link to more models
        if (!empty($carData['br'])) {
            $brand = str_replace('_', '-', strtolower($carData['br']));
            $model = str_replace('_', '-', strtolower($carData['mo'] ?? ''));
            $catalogPath = $catalogType === 'on_order' ? 'ordercars' : 'cars';
            
            $message .= "\n🔗 Alte modele aici: https://www.sauto.md/ro/{$catalogPath}/{$brand}-{$model}?utm_source=social&utm_medium=facebook&utm_campaign=auto_post";
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
