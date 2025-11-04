<?php

namespace App\Helper;

/**
 * Helper class for generating random social media posting times
 */
class RandomTimeHelper 
{
    /**
     * Generate a random Facebook time using configurable settings from database
     * 
     * @return string Time in H:i format (e.g., "18:05", "21:35")
     */
    public static function generateRandomFacebookTime(): string 
    {
        return self::generateRandomTimeFromSettings('facebook');
    }
    
    /**
     * Generate a random Telegram time using configurable settings from database
     * 
     * @return string Time in H:i format (e.g., "18:05", "21:35")
     */
    public static function generateRandomTelegramTime(): string 
    {
        return self::generateRandomTimeFromSettings('telegram');
    }
    
    /**
     * Generate a random 999.md time using configurable settings from database
     * 
     * @return string Time in H:i format (e.g., "18:05", "21:35")
     */
    public static function generateRandom999mdTime(): string 
    {
        return self::generateRandomTimeFromSettings('999md');
    }
    
    /**
     * Generate random time based on database settings for specified platform
     * 
     * @param string $platform Either 'facebook', 'telegram', or '999md'
     * @return string Time in H:i format
     */
    private static function generateRandomTimeFromSettings(string $platform): string 
    {
        // Get database connection from global scope or create new one
        global $db, $prefx;
        
        // Default fallback values
        $defaultStart = '18:00';
        $defaultEnd = '22:00';
        $defaultInterval = 5;
        
        try {
            if (!$db) {
                // If no global DB connection, create one
                require_once __DIR__ . '/../../environment.php';
                $db = new \PDO(
                    'mysql:host=' . SQL_HOST . ';dbname=' . SQL_DB . ';charset=' . SQL_CHARSET,
                    SQL_USER,
                    SQL_PASS,
                    [
                        \PDO::ATTR_ERRMODE => \PDO::ERRMODE_EXCEPTION,
                        \PDO::ATTR_DEFAULT_FETCH_MODE => \PDO::FETCH_ASSOC
                    ]
                );
                $prefx = 'gh3sp';
            }
            
            // Get settings from database
            $stmt = $db->prepare("SELECT name, value FROM {$prefx}_settings WHERE name LIKE '{$platform}_random_%'");
            $stmt->execute();
            $settings = [];
            while ($row = $stmt->fetch()) {
                $settings[$row['name']] = $row['value'];
            }
            
            // Extract settings with fallbacks
            $startTime = $settings["{$platform}_random_start_time"] ?? $defaultStart;
            $endTime = $settings["{$platform}_random_end_time"] ?? $defaultEnd;
            $intervalMinutes = intval($settings["{$platform}_random_interval_minutes"] ?? $defaultInterval);
            
        } catch (\Exception $e) {
            // If database query fails, use defaults
            $startTime = $defaultStart;
            $endTime = $defaultEnd;
            $intervalMinutes = $defaultInterval;
        }
        
        // Parse start and end times
        $startParts = explode(':', $startTime);
        $endParts = explode(':', $endTime);
        
        $startHour = intval($startParts[0]);
        $startMinute = intval($startParts[1] ?? 0);
        $endHour = intval($endParts[0]);
        $endMinute = intval($endParts[1] ?? 0);
        
        // Convert to minutes from midnight
        $startMinutes = ($startHour * 60) + $startMinute;
        $endMinutes = ($endHour * 60) + $endMinute;
        
        // Generate random time using the custom function
        return self::generateRandomTime($startHour, $endHour, $intervalMinutes);
    }
    
    /**
     * Generate a random time with custom parameters
     * 
     * @param int $startHour Start hour (0-23)
     * @param int $endHour End hour (0-23)
     * @param int $intervalMinutes Interval in minutes (default: 5)
     * @return string Time in H:i format
     */
    public static function generateRandomTime(int $startHour = 18, int $endHour = 22, int $intervalMinutes = 5): string 
    {
        $startMinutes = $startHour * 60;
        $endMinutes = $endHour * 60;
        
        $totalIntervals = ($endMinutes - $startMinutes) / $intervalMinutes;
        $randomInterval = rand(0, $totalIntervals - 1);
        
        $randomMinutes = $startMinutes + ($randomInterval * $intervalMinutes);
        
        $hours = intval($randomMinutes / 60);
        $minutes = $randomMinutes % 60;
        
        return sprintf('%02d:%02d', $hours, $minutes);
    }
}
