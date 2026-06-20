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
        
        // Default fallback values (only used if DB has no setting)
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
        
        if ($intervalMinutes < 1) {
            $intervalMinutes = $defaultInterval;
        }

        // Parse start and end times into minutes-from-midnight.
        $startParts = explode(':', $startTime);
        $endParts = explode(':', $endTime);
        $startMinutes = (intval($startParts[0]) * 60) + intval($startParts[1] ?? 0);
        $endMinutes = (intval($endParts[0]) * 60) + intval($endParts[1] ?? 0);

        // "From now on" logic: if we are currently inside the window, don't pick a
        // slot in the past — raise the effective start to the current time (rounded
        // up to the next interval). Before the window we use the full window; after
        // it (window already passed today) we also fall back to the full window so
        // the publish cron picks it up the next day.
        $nowMinutes = (intval(date('H')) * 60) + intval(date('i'));
        if ($nowMinutes >= $startMinutes && $nowMinutes < $endMinutes) {
            $startMinutes = (int) (ceil($nowMinutes / $intervalMinutes) * $intervalMinutes);
        }

        return self::generateRandomTimeBetween($startMinutes, $endMinutes, $intervalMinutes);
    }

    /**
     * Pick a random interval-aligned time between two minute-of-day bounds.
     *
     * @param int $startMinutes Start, minutes from midnight
     * @param int $endMinutes   End (exclusive), minutes from midnight
     * @param int $intervalMinutes Step in minutes
     * @return string Time in H:i format
     */
    private static function generateRandomTimeBetween(int $startMinutes, int $endMinutes, int $intervalMinutes): string
    {
        // Guard against an empty/inverted window (e.g. now is past end): clamp to the
        // last valid slot so we always return a sane time inside the window.
        if ($endMinutes <= $startMinutes) {
            $randomMinutes = max(0, $endMinutes - $intervalMinutes);
        } else {
            $totalIntervals = (int) floor(($endMinutes - $startMinutes) / $intervalMinutes);
            if ($totalIntervals < 1) {
                $totalIntervals = 1;
            }
            $randomMinutes = $startMinutes + (rand(0, $totalIntervals - 1) * $intervalMinutes);
        }

        return sprintf('%02d:%02d', intdiv($randomMinutes, 60), $randomMinutes % 60);
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
        if ($intervalMinutes < 1) {
            $intervalMinutes = 5;
        }
        return self::generateRandomTimeBetween($startHour * 60, $endHour * 60, $intervalMinutes);
    }
}
