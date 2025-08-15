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
        // TODO: Replace with actual "in transit" status IDs from database
        // This is a placeholder - you need to check your database for the correct status ID(s)
        return in_array($status, [3, 4]) || // Example IDs
               strpos(strtolower($status), 'в пути') !== false ||
               strpos(strtolower($status), 'transit') !== false ||
               strpos(strtolower($status), 'in transit') !== false;
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
        // Phone number patterns to match
        $patterns = [
            '/(\+373|00373)?\s?(\(?\d{2,3}\)?[\s\-]?)[\d\s\-]{5,}/',
            '/\+373\s?\d{2}\s?\d{3}\s?\d{3}/',
            '/\(\+373\)\s?\d{2}[\s\-]?\d{3}[\s\-]?\d{3}/',
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
}
