<?php

namespace App\Services;

use App\Core\Container;

class PhoneReplacementService
{
    private $db;
    private $prefix;
    /**
     * Phone number configuration for different contexts
     * @var array
     */
    private $phoneConfig = [];
    
    /**
     * Cached transit status IDs
     * @var array
     */
    private $transitStatusIds = null;
    
    public function __construct()
    {
        $this->db = Container::get('db');
        $this->prefix = Container::get('prefix');
        $this->loadPhoneConfig();
    }
    
    /**
     * Load phone configuration from database
     */
    private function loadPhoneConfig()
    {
        try {
            $query = "SELECT context, phone_number FROM phone_config";
            $stmt = $this->db->prepare($query);
            $stmt->execute();
            
            while ($row = $stmt->fetch(\PDO::FETCH_ASSOC)) {
                $this->phoneConfig[$row['context']] = $row['phone_number'];
            }
            
            // Fallback to default values if database is empty
            if (empty($this->phoneConfig)) {
                $this->phoneConfig = [
                    'prunkul' => '+37379600747',
                    'stock_website' => '+37379600386',
                    'on_order' => '+37379500735',
                    'website_all' => '+37379600361'
                ];
            }
        } catch (\Exception $e) {
            // Fallback to default values on error
            $this->phoneConfig = [
                'prunkul' => '+37379600747',
                'stock_website' => '+37379600386',
                'on_order' => '+37379500735',
                'website_all' => '+37379600361'
            ];
        }
    }
    
    /**
     * Get phone number for car based on priority conditions
     * @param array $carData - Car data from database
     * @param string $context - Context: 'card', 'page', 'general'
     * @return string Phone number
     */
    public function getPhoneForCar($carData, $context = 'card')
    {
        // Priority 1: Prunkul location
        if (isset($carData['loc']) && $this->isLocationPrunkul($carData['loc'])) {
            return $this->phoneConfig['prunkul'];
        }
        
        // Priority 2: Stock-website group
        if (isset($carData['group']) && $carData['group'] === 'Stock-website') {
            return $this->phoneConfig['stock_website'];
        }
        
        // Priority 3: Status "soon" (la comandă/под заказ) or "in transit"
        if ((isset($carData['soon']) && $carData['soon'] == 1) || 
            (isset($carData['sts']) && $this->isStatusInTransit($carData['sts']))) {
            return $this->phoneConfig['on_order'];
        }
        
        if ($context === 'order_page') {
            return $this->phoneConfig['on_order'];
        }
        
        // Priority 5: Default - Stock-website (according to task requirements)
        return $this->phoneConfig['stock_website'];
    }
    
    /**
     * Get phone number for general site usage (headers, footers, etc.)
     * @return string
     */
    public function getGeneralPhone()
    {
        return $this->phoneConfig['website_all'];
    }
    
    /**
     * Get phone number for order page
     * @return string
     */
    public function getOrderPagePhone()
    {
        return $this->phoneConfig['on_order'];
    }
    
    /**
     * Check if location is Prunkul
     * @param mixed $location
     * @return bool
     */
    private function isLocationPrunkul($location)
    {
        // Prunkul Branch - Pietrăriei 3 (ID = 2, can be string '2' or integer 2)
        return in_array($location, [2, '2']) ||
               strpos(strtolower($location), 'prunkul') !== false;
    }
    
    /**
     * Load and cache transit status IDs
     */
    private function loadTransitStatusIds()
    {
        if ($this->transitStatusIds !== null) {
            return $this->transitStatusIds;
        }
        
        try {
            $query = "SELECT id FROM {$this->prefix}_car_status WHERE 
                     LOWER(name_ro) LIKE '%în drum%' OR 
                     LOWER(name_ru) LIKE '%в пути%' OR 
                     LOWER(name_en) LIKE '%in transit%' OR
                     LOWER(name_ro) LIKE '%transport%' OR
                     LOWER(name_ru) LIKE '%транспорт%'";
            
            $stmt = $this->db->prepare($query);
            $stmt->execute();
            
            $this->transitStatusIds = [];
            while ($row = $stmt->fetch(\PDO::FETCH_ASSOC)) {
                $this->transitStatusIds[] = (int)$row['id'];
            }
            
        } catch (\Exception $e) {
            $this->transitStatusIds = [];
        }
        
        return $this->transitStatusIds;
    }
    
    /**
     * Check if status indicates "in transit"
     * @param mixed $status
     * @return bool
     */
    private function isStatusInTransit($status)
    {
        $transitIds = $this->loadTransitStatusIds();
        
        return in_array((int)$status, $transitIds) ||
               strpos(strtolower($status), 'в пути') !== false ||
               strpos(strtolower($status), 'transit') !== false ||
               strpos(strtolower($status), 'în drum') !== false;
    }
    
    /**
     * Replace all phone numbers in text with appropriate numbers
     * @param string $text
     * @param string $context
     * @param array $carData
     * @return string
     */
    public function replacePhoneNumbers($text, $context = 'general', $carData = null)
    {
        // Enhanced phone number patterns to match ALL possible formats
        $patterns = [
            // Standard formats with various separators (spaces, dashes, dots, brackets)
            '/\+373[\s\-\.\(\)\/\'\"]*\d{2}[\s\-\.\(\)\/\'\"]*\d{3}[\s\-\.\(\)\/\'\"]*\d{3}/',
            '/\(\+373\)[\s\-\.\/\'\"]*\d{2}[\s\-\.\/\'\"]*\d{3}[\s\-\.\/\'\"]*\d{3}/',
            '/00373[\s\-\.\/\'\"]*\d{2}[\s\-\.\/\'\"]*\d{3}[\s\-\.\/\'\"]*\d{3}/',
            '/373[\s\-\.\/\'\"]*\d{2}[\s\-\.\/\'\"]*\d{3}[\s\-\.\/\'\"]*\d{3}/',
            // Compact formats
            '/\+373\d{8}/',
            '/00373\d{8}/',
            '/373\d{8}/',
            // Legacy broad pattern for any Moldova number format
            '/(\+373|00373|373)[\s\-\.\/\'\"_]*\d{2}[\s\-\.\/\'\"_]*\d{3}[\s\-\.\/\'\"_]*\d{3}/',
        ];
        
        $replacementPhone = '';
        
        switch ($context) {
            case 'car_card':
            case 'car_page':
                $replacementPhone = $carData ? $this->getPhoneForCar($carData, $context) : $this->getGeneralPhone();
                break;
            case 'order_page':
                $replacementPhone = $this->getOrderPagePhone();
                break;
            case 'general':
            default:
                $replacementPhone = $this->getGeneralPhone();
                break;
        }
        
        foreach ($patterns as $pattern) {
            $text = preg_replace($pattern, $replacementPhone, $text);
        }
        
        return $text;
    }
    
    /**
     * Get formatted phone number for display
     * @param string $phone
     * @param string $format - 'tel', 'display', 'international'
     * @return string
     */
    public function formatPhone($phone, $format = 'display')
    {
        switch ($format) {
            case 'tel':
                return $phone; // For tel: links
            case 'display':
                // Format: +(373) XX-XXX-XXX
                if (preg_match('/\+373(\d{2})(\d{3})(\d{3})/', $phone, $matches)) {
                    return "+(373) {$matches[1]}-{$matches[2]}-{$matches[3]}";
                }
                return $phone;
            case 'international':
                return $phone;
            default:
                return $phone;
        }
    }
    
    /**
     * Get all configured phone numbers
     * @return array
     */
    public function getAllPhones()
    {
        return $this->phoneConfig;
    }
    
    /**
     * Update phone configuration
     * @param string $key
     * @param string $phone
     */
    public function updatePhoneConfig($key, $phone)
    {
        if (isset($this->phoneConfig[$key])) {
            $this->phoneConfig[$key] = $phone;
        }
    }
    
    /**
     * Validate if phone number is from approved list
     * @param string $phone
     * @return bool
     */
    public function isApprovedPhone($phone)
    {
        $approvedNumbers = array_values($this->phoneConfig);
        return in_array($phone, $approvedNumbers);
    }
    
    /**
     * Get list of authorized phone numbers from database
     * @return array
     */
    public function getAuthorizedPhones()
    {
        return array_values($this->phoneConfig);
    }
}
