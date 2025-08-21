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
    private $phoneConfig = [
        'prunkul' => '+37379600747',        // Prunkul Branch
        'stock_website' => '+37379600386',   // Website-Stock
        'on_order' => '+37379500735',        // Website-On-Order (in transit + on order)
        'website_all' => '+37379600361'      // Website-All (general number)
    ];
    
    public function __construct()
    {
        $this->db = Container::get('db');
        $this->prefix = Container::get('prefix');
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
        // Prunkul Branch - Pietrăriei 3 (ID = 2 based on language files verification)
        return in_array($location, [2]) ||
               strpos(strtolower($location), 'prunkul') !== false;
    }
    
    /**
     * Check if status indicates "in transit"
     * @param mixed $status
     * @return bool
     */
    private function isStatusInTransit($status)
    {
        // Query database for actual "in transit" status IDs
        try {
            $query = "SELECT id FROM {$this->prefix}_car_status WHERE 
                     LOWER(name_ro) LIKE '%în drum%' OR 
                     LOWER(name_ru) LIKE '%в пути%' OR 
                     LOWER(name_en) LIKE '%in transit%' OR
                     LOWER(name_ro) LIKE '%transport%' OR
                     LOWER(name_ru) LIKE '%транспорт%'";
            
            $result = $this->db->query($query);
            $transitIds = [];
            
            if ($result) {
                while ($row = $result->fetch_assoc()) {
                    $transitIds[] = (int)$row['id'];
                }
            }
            
            // Check if current status is in transit IDs
            return in_array((int)$status, $transitIds) ||
                   strpos(strtolower($status), 'в пути') !== false ||
                   strpos(strtolower($status), 'transit') !== false ||
                   strpos(strtolower($status), 'în drum') !== false;
                   
        } catch (\Exception $e) {
            // Fallback to string matching if database query fails
            return strpos(strtolower($status), 'в пути') !== false ||
                   strpos(strtolower($status), 'transit') !== false ||
                   strpos(strtolower($status), 'în drum') !== false;
        }
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
            // Specific unauthorized numbers (exact matches)
            '/\+37368689995/',
            '/\+37369977674/',
            '/\+37379977674/',
            '/\+37379954375/',
            '/\+37379600446/',
            '/\+37368500573/',
            // Numbers with any separators
            '/\+373[\s\-\.\/\'\"_]*68[\s\-\.\/\'\"_]*689[\s\-\.\/\'\"_]*995/',
            '/\+373[\s\-\.\/\'\"_]*69[\s\-\.\/\'\"_]*977[\s\-\.\/\'\"_]*674/',
            '/\+373[\s\-\.\/\'\"_]*79[\s\-\.\/\'\"_]*977[\s\-\.\/\'\"_]*674/',
            '/\+373[\s\-\.\/\'\"_]*79[\s\-\.\/\'\"_]*954[\s\-\.\/\'\"_]*375/',
            '/\+373[\s\-\.\/\'\"_]*79[\s\-\.\/\'\"_]*600[\s\-\.\/\'\"_]*446/',
            '/\+373[\s\-\.\/\'\"_]*68[\s\-\.\/\'\"_]*500[\s\-\.\/\'\"_]*573/',
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
     * Get list of unauthorized phone numbers that should be replaced
     * @return array
     */
    public function getUnauthorizedPhones()
    {
        return [
            '+37368689995', '+37369977674', '+37379977674', 
            '+37379954375', '+37379600446', '+37368500573'
        ];
    }
}
