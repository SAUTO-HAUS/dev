<?php

namespace App\Services;

/**
 * Hooks for triggering publication when cars are added/updated
 */
class PublicationHooks
{
    /**
     * Hook to call after car is saved (add/edit)
     * 
     * @param int $carId Car ID
     * @param array $carData Car data
     * @param bool $isNew Whether this is a new car
     * @return array Publication results
     */
    public static function afterCarSave($carId, $carData, $isNew = false)
    {
        // Only auto-publish for new cars or when explicitly requested
        if (!$isNew && !isset($carData['trigger_publication'])) {
            return ['message' => 'Auto-publication skipped for existing car'];
        }

        try {
            // Initialize AutoPublicationService
            global $db, $prefx;
            $autoPublisher = new AutoPublicationService($db, $prefx);
            
            // Trigger auto-publication
            $results = $autoPublisher->triggerAutoPublication($carId);
            
            return $results;
        } catch (\Exception $e) {
            error_log("PublicationHooks: Error in afterCarSave - " . $e->getMessage());
            return ['error' => $e->getMessage()];
        }
    }

    /**
     * Hook to call when catalog_type is changed
     * 
     * @param int $carId Car ID
     * @param string $oldType Old catalog type
     * @param string $newType New catalog type
     * @return array Publication results
     */
    public static function onCatalogTypeChange($carId, $oldType, $newType)
    {
        try {
            global $db, $prefx;
            $autoPublisher = new AutoPublicationService($db, $prefx);
            
            // Reset publication flags when type changes
            $stmt = $db->prepare("UPDATE {$prefx}_car_ctlg SET 
                                 telegram_published = 0, 
                                 facebook_published = 0 
                                 WHERE id = ?");
            $stmt->execute([$carId]);
            
            // Trigger publication for new type
            $results = $autoPublisher->autoPublishCar($carId, $newType);
            
            return $results;
        } catch (\Exception $e) {
            error_log("PublicationHooks: Error in onCatalogTypeChange - " . $e->getMessage());
            return ['error' => $e->getMessage()];
        }
    }

    /**
     * Add publication trigger to car forms
     * Call this in car add/edit forms to add publication controls
     * 
     * @param string $catalogType Current catalog type
     * @param bool $isNew Whether this is a new car
     * @return string HTML for publication controls
     */
    public static function renderPublicationControls($catalogType = 'in_stock', $isNew = false)
    {
        $catalogLabel = $catalogType === 'on_order' ? 'под заказ' : 'в наличии';
        
        $html = '<div class="publication-controls">';
        $html .= '<h4>Настройки публикации</h4>';
        
        if ($isNew) {
            $html .= '<label>';
            $html .= '<input type="checkbox" name="auto_publish" value="1" checked> ';
            $html .= "Автоматически опубликовать в каналах для автомобилей {$catalogLabel}";
            $html .= '</label>';
        } else {
            $html .= '<label>';
            $html .= '<input type="checkbox" name="trigger_publication" value="1"> ';
            $html .= "Переопубликовать в каналах для автомобилей {$catalogLabel}";
            $html .= '</label>';
        }
        
        $html .= '</div>';
        
        return $html;
    }
}
