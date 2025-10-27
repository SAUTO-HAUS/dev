<?php

namespace App\Services;

/**
 * Service for handling automatic publication of cars based on catalog_type
 */
class AutoPublicationService
{
    private $db;
    private $prefx;
    private $publicationService;

    public function __construct($database, $prefix = 'gh3sp')
    {
        $this->db = $database;
        $this->prefx = $prefix;
        $this->publicationService = new PublicationService($database, $prefix);
    }

    /**
     * Auto-publish car based on catalog_type
     * 
     * @param int $carId Car ID
     * @param string $catalogType 'in_stock' or 'on_order'
     * @return array Publication results
     */
    public function autoPublishCar($carId, $catalogType)
    {
        $results = [
            'telegram' => false,
            'facebook' => false,
            '999md' => false,
            'messages' => []
        ];

        // Check if auto-publication is enabled for this catalog type
        if (!$this->publicationService->isAutoPublishEnabled($catalogType)) {
            $results['messages'][] = "Auto-publication disabled for catalog type: {$catalogType}";
            return $results;
        }

        // Get car data
        $carData = $this->getCarData($carId);
        if (!$carData) {
            $results['messages'][] = "Car not found: {$carId}";
            return $results;
        }

        // Publish to Telegram
        $results['telegram'] = $this->publishToTelegram($carId, $catalogType, $carData);
        
        // Publish to Facebook
        $results['facebook'] = $this->publishToFacebook($carId, $catalogType, $carData);
        
        // Publish to 999.md (if configured)
        $results['999md'] = $this->publishTo999md($carId, $catalogType, $carData);

        return $results;
    }

    /**
     * Get car data from database
     * 
     * @param int $carId Car ID
     * @return array|null Car data
     */
    private function getCarData($carId)
    {
        try {
            $stmt = $this->db->prepare("SELECT * FROM {$this->prefx}_car_ctlg WHERE id = ?");
            $stmt->execute([$carId]);
            return $stmt->fetch(\PDO::FETCH_ASSOC);
        } catch (\PDOException $e) {
            error_log("AutoPublicationService: Failed to get car data - " . $e->getMessage());
            return null;
        }
    }

    /**
     * Publish to Telegram
     * 
     * @param int $carId Car ID
     * @param string $catalogType Catalog type
     * @param array $carData Car data
     * @return bool Success status
     */
    private function publishToTelegram($carId, $catalogType, $carData)
    {
        $telegramSettings = $this->publicationService->getTelegramSettings($catalogType);
        if (!$telegramSettings) {
            $this->publicationService->logPublication($carId, $catalogType, 'telegram', false, 'Settings not configured');
            return false;
        }

        // Here you would implement the actual Telegram API call
        // For now, just simulate success and log
        $this->publicationService->logPublication($carId, $catalogType, 'telegram', true, 'Auto-published via AutoPublicationService');
        
        // Update database flag
        try {
            $stmt = $this->db->prepare("UPDATE {$this->prefx}_car_ctlg SET telegram_published = 1 WHERE id = ?");
            $stmt->execute([$carId]);
            return true;
        } catch (\PDOException $e) {
            error_log("AutoPublicationService: Failed to update Telegram flag - " . $e->getMessage());
            return false;
        }
    }

    /**
     * Publish to Facebook
     * 
     * @param int $carId Car ID
     * @param string $catalogType Catalog type
     * @param array $carData Car data
     * @return bool Success status
     */
    private function publishToFacebook($carId, $catalogType, $carData)
    {
        $facebookSettings = $this->publicationService->getFacebookSettings($catalogType);
        if (!$facebookSettings) {
            $this->publicationService->logPublication($carId, $catalogType, 'facebook', false, 'Settings not configured');
            return false;
        }

        // Generate Facebook message with catalog type label
        $message = $this->publicationService->generateFacebookMessage($carData, $catalogType);

        // Here you would implement the actual Facebook API call
        // For now, just simulate success and log
        $this->publicationService->logPublication($carId, $catalogType, 'facebook', true, 'Auto-published via AutoPublicationService');
        
        // Update database flag
        try {
            $stmt = $this->db->prepare("UPDATE {$this->prefx}_car_ctlg SET facebook_published = 1 WHERE id = ?");
            $stmt->execute([$carId]);
            return true;
        } catch (\PDOException $e) {
            error_log("AutoPublicationService: Failed to update Facebook flag - " . $e->getMessage());
            return false;
        }
    }

    /**
     * Publish to 999.md
     * 
     * @param int $carId Car ID
     * @param string $catalogType Catalog type
     * @param array $carData Car data
     * @return bool Success status
     */
    private function publishTo999md($carId, $catalogType, $carData)
    {
        $apiSettings = $this->publicationService->get999mdSettings($catalogType);
        if (!$apiSettings) {
            $this->publicationService->logPublication($carId, $catalogType, '999md', false, 'Settings not configured');
            return false;
        }

        // Here you would implement the actual 999.md API call using Api999Service
        // For now, just simulate success and log
        $this->publicationService->logPublication($carId, $catalogType, '999md', true, 'Auto-published via AutoPublicationService');
        
        return true;
    }

    /**
     * Trigger auto-publication when car is added/updated
     * Call this from car add/edit forms
     * 
     * @param int $carId Car ID
     * @return array Publication results
     */
    public function triggerAutoPublication($carId)
    {
        // Get car's catalog_type from database
        $carData = $this->getCarData($carId);
        if (!$carData) {
            return ['error' => 'Car not found'];
        }

        $catalogType = $carData['catalog_type'] ?? 'in_stock'; // Default to in_stock for backward compatibility
        
        return $this->autoPublishCar($carId, $catalogType);
    }
}
