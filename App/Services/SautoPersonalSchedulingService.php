<?php

namespace App\Services;

use PDO;

class SautoPersonalSchedulingService
{
    private $pdo;
    
    public function __construct()
    {
        global $pdo;
        $this->pdo = $pdo;
    }
    
    /**
     * Save custom schedules for SAUTO Personal
     * 
     * @param int $carId
     * @param string $catalogType 'in_stock' or 'on_order'
     * @param array $schedules Array of ['date' => 'Y-m-d', 'time' => 'H:i']
     * @return bool
     */
    public function saveSchedules($carId, $catalogType, $schedules)
    {
        try {
            // First, remove existing pending schedules for this car
            $this->clearPendingSchedules($carId, $catalogType);
            
            // Insert new schedules
            $stmt = $this->pdo->prepare("
                INSERT INTO gh3sp_sauto_personal_schedules 
                (car_id, catalog_type, schedule_date, schedule_time, status) 
                VALUES (?, ?, ?, ?, 'pending')
            ");
            
            foreach ($schedules as $schedule) {
                if (!empty($schedule['date']) && !empty($schedule['time'])) {
                    $stmt->execute([
                        $carId,
                        $catalogType,
                        $schedule['date'],
                        $schedule['time']
                    ]);
                }
            }
            
            return true;
        } catch (\Exception $e) {
            error_log("Error saving SAUTO Personal schedules: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Get pending schedules that need to be published
     * 
     * @return array
     */
    public function getPendingSchedules()
    {
        $stmt = $this->pdo->prepare("
            SELECT s.*, c.id as car_id, c.999_id as existing_999_id
            FROM gh3sp_sauto_personal_schedules s
            LEFT JOIN gh3sp_car_ctlg c ON s.car_id = c.id
            WHERE s.status = 'pending' 
            AND CONCAT(s.schedule_date, ' ', s.schedule_time) <= NOW()
            ORDER BY s.schedule_date, s.schedule_time
        ");
        
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    /**
     * Mark schedule as published
     * 
     * @param int $scheduleId
     * @param string $apiId
     * @return bool
     */
    public function markAsPublished($scheduleId, $apiId = null)
    {
        $stmt = $this->pdo->prepare("
            UPDATE gh3sp_sauto_personal_schedules 
            SET status = 'published', published_at = NOW(), `999_id` = ?
            WHERE id = ?
        ");
        
        return $stmt->execute([$apiId, $scheduleId]);
    }
    
    /**
     * Mark schedule as failed
     * 
     * @param int $scheduleId
     * @param string $errorMessage
     * @return bool
     */
    public function markAsFailed($scheduleId, $errorMessage)
    {
        $stmt = $this->pdo->prepare("
            UPDATE gh3sp_sauto_personal_schedules 
            SET status = 'failed', error_message = ?
            WHERE id = ?
        ");
        
        return $stmt->execute([$errorMessage, $scheduleId]);
    }
    
    /**
     * Get schedules for a specific car
     * 
     * @param int $carId
     * @param string $catalogType
     * @return array
     */
    public function getCarSchedules($carId, $catalogType)
    {
        $stmt = $this->pdo->prepare("
            SELECT * FROM gh3sp_sauto_personal_schedules 
            WHERE car_id = ? AND catalog_type = ?
            ORDER BY schedule_date, schedule_time
        ");
        
        $stmt->execute([$carId, $catalogType]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
    
    /**
     * Clear pending schedules for a car
     * 
     * @param int $carId
     * @param string $catalogType
     * @return bool
     */
    private function clearPendingSchedules($carId, $catalogType)
    {
        $stmt = $this->pdo->prepare("
            DELETE FROM gh3sp_sauto_personal_schedules 
            WHERE car_id = ? AND catalog_type = ? AND status = 'pending'
        ");
        
        return $stmt->execute([$carId, $catalogType]);
    }
    
    /**
     * Cancel all pending schedules for a car
     * 
     * @param int $carId
     * @param string $catalogType
     * @return bool
     */
    public function cancelSchedules($carId, $catalogType)
    {
        $stmt = $this->pdo->prepare("
            UPDATE gh3sp_sauto_personal_schedules 
            SET status = 'cancelled'
            WHERE car_id = ? AND catalog_type = ? AND status = 'pending'
        ");
        
        return $stmt->execute([$carId, $catalogType]);
    }
}
