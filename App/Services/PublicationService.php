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
        // Build car title with proper capitalization
        $brand = ucfirst(strtolower($carData['br'] ?? ''));
        $model = ucwords(strtolower(str_replace('_', ' ', $carData['mo'] ?? '')));
        $title = trim($brand . ' ' . $model);
        
        if (empty($title)) {
            $title = $carData['name'] ?? 'Автомобиль';
        }
        
        // Add catalog type label first
        if ($catalogType === 'on_order') {
            $message = "📋 LA COMANDĂ";
            $message .= "\n🚘 " . $title;
            $message .= "\n⏰ Termen de livrare: " . ($carData['delivery_time'] ?? '14 zile');
        } else {
            $message = "✅ În stoc";
            $message .= "\n🚘 " . $title;
        }
        
        // Add price (different text for on_order cars)
        if (!empty($carData['prc'])) {
            $price = number_format($carData['prc'], 0, '.', ',') . ' €';
            if ($catalogType === 'on_order') {
                $message .= "\n💰 Pretul masinii la licitatii Europene: " . $price;
            } else {
                $message .= "\n💰 Preț: " . $price;
            }
        }
        
        // Add detailed specifications with icons
        if (!empty($carData['yr'])) {
            $message .= "\n📅 An fabricație: " . $carData['yr'];
        }
        
        // Body type
        if (!empty($carData['bt'])) {
            $bodyTypes = [
                'sdn' => 'Sedan', 'suv' => 'SUV', 'hbk' => 'Hatchback', 'unv' => 'Universal', 
                'cup' => 'Coupe', 'crv' => 'Crossover', 'mnv' => 'Minivan', 'pkp' => 'Pickup', 
                'van' => 'Furgon', 'mbs' => 'Microbus', 'cbr' => 'Cabriolet', 'cmb' => 'Combi', 
                'rod' => 'Roadster', 'frg' => 'Frigider', 'crr' => 'Purtător'
            ];
            $bodyType = $bodyTypes[$carData['bt']] ?? 'Necunoscut';
            $message .= "\n🚗 Tip caroserie: " . $bodyType;
        }
        
        // Mileage
        if (!empty($carData['mlg'])) {
            $unit = ($carData['unit'] ?? 'km') === 'mi' ? 'mile' : 'km';
            $mileage = number_format($carData['mlg'], 0, '.', ',');
            $message .= "\n📏 Kilometraj: " . $mileage . ' ' . $unit;
        }
        
        // Engine capacity
        if (!empty($carData['vol'])) {
            $message .= "\n🔧 Capacitate motor: " . number_format($carData['vol'], 0, '.', ',') . ' cm³';
        }
        
        // Power
        if (!empty($carData['hp'])) {
            $kw = round($carData['hp'] * 0.735);
            $message .= "\n⚡ Putere: " . $carData['hp'] . ' hp (' . $kw . ' kW)';
        }
        
        // Fuel type
        if (!empty($carData['fl'])) {
            $fuelTypes = [
                'gsl' => 'Benzină', 'gmn' => 'Benzină / Gaz (metan)', 'gpn' => 'Benzină / Gaz (propan)', 
                'hbd' => 'Hibrid', 'dsl' => 'Diesel', 'pih' => 'Plug-in Hibrid', 
                'elc' => 'Electricitate', 'gas' => 'Gaz'
            ];
            $fuel = $fuelTypes[$carData['fl']] ?? 'Necunoscut';
            $message .= "\n⛽ Combustibil: " . $fuel;
        }
        
        // Transmission
        if (!empty($carData['tra'])) {
            $transTypes = [
                'tpt' => 'Tiptronic', 'atm' => 'Automată', 'mnl' => 'Mecanică', 
                'rbt' => 'Robotizată', 'vrr' => 'Variator'
            ];
            $transmission = $transTypes[$carData['tra']] ?? 'Necunoscut';
            $message .= "\n⚙️ Cutie de viteze: " . $transmission;
        }
        
        // Drive type
        if (!empty($carData['wd'])) {
            $driveTypes = [
                '44' => '4x4', 're' => 'Din spate', 'fr' => 'Din față'
            ];
            $drive = $driveTypes[$carData['wd']] ?? 'Necunoscut';
            $message .= "\n🔄 Tracțiune: " . $drive;
        }
        
        // Color
        if (!empty($carData['clr'])) {
            $colors = [
                'l_grn' => 'Verde deschis', 'blu' => 'Albastru', 'brn' => 'Maro', 'cmn' => 'Carmin', 
                'cml' => 'Cameleon', 'bge' => 'Bej', 'wht' => 'Alb', 'vns' => 'Vișiniu', 
                'azr' => 'Azuriu', 'ylw' => 'Galben', 'grn' => 'Verde', 'gld' => 'Auriu', 
                'red' => 'Roșu', 'orn' => 'Portocaliu', 'pnk' => 'Roz', 'slv' => 'Argintiu', 
                'gra' => 'Gri', 'd_grn' => 'Verde închis', 'prp' => 'Violet', 'blk' => 'Negru', 
                'wap' => 'Asfalt umed', 'snd' => 'Nisip'
            ];
            $color = $colors[$carData['clr']] ?? 'Altă culoare';
            $message .= "\n🎨 Culoare: " . $color;
        }
        
        // Seats
        if (!empty($carData['sts'])) {
            $message .= "\n👥 Locuri: " . $carData['sts'];
        }
        
        // Address based on location
        $addresses = [
            1 => 'Chișinău, str. Calea Moşilor 11',
            2 => 'Chișinău, str. Pietrăriei 3'
        ];
        $address = $addresses[$carData['loc'] ?? 1] ?? 'Chișinău';
        $message .= "\n📍 Adresă: " . $address;
        
        // Phone number (different for on_order cars)
        if ($catalogType === 'on_order') {
            $message .= "\n📞 Telefon: +37379600352";
        } else {
            $message .= "\n📞 Telefon: +373 796 00 361";
        }
        
        // Link to more models
        if (!empty($carData['br'])) {
            $brand = str_replace('_', '-', strtolower($carData['br']));
            $model = str_replace('_', '-', strtolower($carData['mo'] ?? ''));
            
            $message .= "\n🔗 Alte modele: sauto.md/ro/cars/{$brand}-{$model}";
        }
        
        return $message;
    }

     /**
     * Generate Telegram message for car
     * 
     * @param array $carData Car data from database
     * @param string $catalogType 'in_stock' or 'on_order'
     * @return string Formatted message
     */
    public function generateTelegramMessage($carData, $catalogType)
    {
        // Set language for message generation
        $_COOKIE['lang'] = 'ro';
        
        // Try to include language file from different possible locations
        $languageFile = null;
        $possiblePaths = [
            $_SERVER['DOCUMENT_ROOT'] . '/language.php',
            __DIR__ . '/../../language.php',
            __DIR__ . '/../../content/default/language.php'
        ];
        
        foreach ($possiblePaths as $path) {
            if (file_exists($path)) {
                $languageFile = $path;
                break;
            }
        }
        
        if ($languageFile) {
            require_once $languageFile;
        } else {
            // Fallback - define basic language arrays if language file not found
            global $lng;
            $lng = [
                'l' => [
                    'car' => [
                        'spec' => [
                            'bt' => 'Tip caroserie',
                            'mlg' => 'Parcurs', 
                            'vol' => 'Capacitate motor',
                            'hp' => 'Putere',
                            'fl' => 'Tip combustibil',
                            'tra' => 'Cutia de viteze',
                            'wd' => 'Tip tracțiune',
                            'clr' => 'Culoare',
                            'sts' => 'Numărul de locuri',
                            'loc' => 'Adresă'
                        ]
                    ]
                ],
                't' => [
                    'x' => [
                        'address' => []
                    ]
                ]
            ];
        }
        
        // Use local helper functions to avoid redeclaration
        $parseCurr = function($number) {
            return number_format($number, 0, '.', ',');
        };
        
        $symb_rplc = function($currency) {
            $symbols = ['USD' => '$', 'EUR' => '€', 'MDL' => 'lei'];
            return $symbols[$currency] ?? $currency;
        };
        
        $caption_lines = [];
        
        if ($catalogType === 'on_order') {
            // Order cars format
            $caption_lines[] = '✅ Pretul masinii la licitatii Europene ' . $parseCurr($carData['prc']) . '€';
            $caption_lines[] = '✨ Plus Garanție de la dealer European';
            $caption_lines[] = '';
            $caption_lines[] = '📋 Detalii despre o mașină disponibilă acum la comandă:';
            
            $car_title = '#' . str_replace(" ", "", $carData['br_nm']) . str_replace(" ", "", $carData['mo_nm']);
            $caption_lines[] = '🚘 Model: ' . $car_title;
            $caption_lines[] = '▪️ An fabricație: ' . $carData['yr'];
            $caption_lines[] = '▪️ Kilometraj: ' . $parseCurr($carData['mlg']) . ' km';
            $caption_lines[] = '';
            $caption_lines[] = '✅ Specificații:';
            $caption_lines[] = '▪️ Motor: ' . $carData['vol'] . 'cc ' . $carData['hp'] . 'hp';
            
            // Fuel type (use language mappings like for in_stock)
            $fuel = isset($lng['l']['car']['fl'][$carData['fl']]) ? $lng['l']['car']['fl'][$carData['fl']] : ($carData['fl'] ?? 'Necunoscut');
            $caption_lines[] = '▪️ Combustibil: ' . $fuel;
            
            // Transmission (use language mappings like for in_stock)
            $transmission = isset($lng['l']['car']['tra'][$carData['tra']]) ? $lng['l']['car']['tra'][$carData['tra']] : ($carData['tra'] ?? 'Necunoscut');
            $caption_lines[] = '▪️ Transmisie: ' . $transmission;
            $caption_lines[] = '';
            $caption_lines[] = '📞 Pentru detalii: +37379600352';
            $caption_lines[] = '';
            $caption_lines[] = '<a href="https://t.me/Sauto_LA_Comanda_bot">👉 Sauto la comandă – deschideți chatul pentru întrebări! 👈</a>';
            
        } else {
            // In stock cars format
            $car_title = '#' . str_replace(" ", "", $carData['br_nm']) . str_replace(" ", "", $carData['mo_nm']);
            $caption_lines[] = $car_title;
            
            $cur = $carData['cur'];
            $prc = ($carData['prc_t'] != 0 && $carData['prc_t'] > time()) ? $carData['prc_n'] : $carData['prc'];
            $caption_lines[] = '✅ ' . $carData['yr'] . ', ' . $parseCurr($prc) . ' ' . $symb_rplc($cur);
            
            // Specifications with icons
            $spec_ar = ['bt', 'mlg', 'vol', 'hp', 'fl', 'tra', 'wd', 'clr', 'sts', 'loc'];
            $iconParams = [
                'bt'  => '🚙',   'mlg' => '🛣',  'vol' => '⚙️',   'hp'  => '💪',
                'fl'  => '🔌⛽', 'tra' => '🔄',   'wd'  => '⬆️',   'clr' => '⚪',
                'sts' => '👥',   'loc' => '📍'
            ];
            
            foreach ($spec_ar as $v) {
                if ($v == 'loc' && $carData[$v] == '0') continue;
                
                $v_lng = isset($lng['l']['car'][$v][$carData[$v]]) ? $lng['l']['car'][$v][$carData[$v]] : $carData[$v];
                $v_lng = $v == 'mlg' ? $parseCurr($carData[$v]) . ' km' : $v_lng;
                $v_lng = $v == 'vol' ? $carData[$v] . ' cm3' : $v_lng;
                $v_lng = $v == 'hp' ? $carData[$v] . ' hp (' . round($carData['hp'] * 0.735, 0) . ' kw)' : $v_lng;
                $v_lng = $v == 'loc' ? $lng['t']['x']['address'][$carData[$v]] : $v_lng;
                
                if (isset($carData[$v]) && $carData[$v] != '') {
                    $v_lng = str_replace("sup", "i", $v_lng);
                    
                    if ($v == 'loc') {
                        $caption_lines[] = $iconParams[$v] . ' ' . $lng['l']['car']['spec'][$v] . ': Chișinău, ' . $v_lng;
                    } else {
                        $caption_lines[] = $iconParams[$v] . ' ' . $lng['l']['car']['spec'][$v] . ': ' . $v_lng;
                    }
                }
            }
            
            $caption_lines[] = '📌 Apasă pe hashtag pentru a vedea alte mașini similare';
            $caption_lines[] = '';
            
            $marka_auto = str_replace(" ", "", $carData['br_nm']);
            $model_auto = str_replace(" ", "", $carData['mo_nm']);
            $caption_lines[] = '<a href="https://t.me/Sauto_B24_bot?start=' . $marka_auto . '_' . $model_auto . '_' . $prc . '_' . $carData['yr'] . '">👉 Comentariile le citim și răspundem imediat 👈</a>';
        }
        
        return implode("\n", $caption_lines);
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
