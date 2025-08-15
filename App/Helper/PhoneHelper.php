<?php

namespace App\Helper;

use App\Services\PhoneReplacementService;

class PhoneHelper
{
    private static $phoneService = null;
    
    /**
     * Get phone service instance
     * @return PhoneReplacementService
     */
    private static function getPhoneService()
    {
        if (self::$phoneService === null) {
            self::$phoneService = new PhoneReplacementService();
        }
        return self::$phoneService;
    }
    
    /**
     * Get phone number for car
     * @param array $carData
     * @param string $context
     * @return string
     */
    public static function getCarPhone($carData, $context = 'card')
    {
        return self::getPhoneService()->getPhoneForCar($carData, $context);
    }
    
    /**
     * Get general site phone
     * @return string
     */
    public static function getGeneralPhone()
    {
        return self::getPhoneService()->getGeneralPhone();
    }
    
    /**
     * Get phone number for order page
     * @return string
     */
    public static function getOrderPhone()
    {
        return self::getPhoneService()->getOrderPagePhone();
    }
    
    /**
     * Format phone for display
     * @param string $phone
     * @param string $format
     * @return string
     */
    public static function formatPhone($phone, $format = 'display')
    {
        return self::getPhoneService()->formatPhone($phone, $format);
    }
    
    /**
     * Get contextual phone number based on URL segments and car data
     * @param array $urlSegments - URL segments (t_mp array)
     * @param array $carData - Optional car data
     * @return string
     */
    public static function getContextualPhone($urlSegments = [], $carData = null)
    {
        // Check if we're on the order page (/services/order)
        if (is_array($urlSegments) && 
            count($urlSegments) >= 4 && 
            $urlSegments[2] === 'services' && 
            $urlSegments[3] === 'order') {
            return self::getOrderPhone();
        }
        
        // If car data is provided, get car-specific phone
        if ($carData) {
            return self::getCarPhone($carData, 'contextual');
        }
        
        // Default to general phone
        return self::getGeneralPhone();
    }
}
