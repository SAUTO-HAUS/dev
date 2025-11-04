<?php

namespace App\Helper;

class RandomTimeHelper 
{
    /**
     * Generate a random time between 18:00 and 22:00 with 5-minute intervals
     * Examples: 18:05, 18:10, 18:15, 19:25, 20:40, 21:55, etc.
     * 
     * @return string Time in H:i format (e.g., "18:05", "21:35")
     */
    public static function generateRandomFacebookTime(): string 
    {
        // Start time: 18:00 (18 * 60 = 1080 minutes from midnight)
        $startMinutes = 18 * 60;
        
        // End time: 22:00 (22 * 60 = 1320 minutes from midnight)
        $endMinutes = 22 * 60;
        
        // Generate random minutes in 5-minute intervals
        $totalIntervals = ($endMinutes - $startMinutes) / 5; // 48 intervals (4 hours * 12 intervals per hour)
        $randomInterval = rand(0, $totalIntervals - 1);
        
        // Calculate the actual time
        $randomMinutes = $startMinutes + ($randomInterval * 5);
        
        // Convert back to hours and minutes
        $hours = intval($randomMinutes / 60);
        $minutes = $randomMinutes % 60;
        
        // Format as H:i (e.g., "18:05", "21:35")
        return sprintf('%02d:%02d', $hours, $minutes);
    }
    
    /**
     * Generate a random time between 18:00 and 22:00 with 5-minute intervals for Telegram
     * Same functionality as Facebook but separate method for clarity
     * Examples: 18:05, 18:10, 18:15, 19:25, 20:40, 21:55, etc.
     * 
     * @return string Time in H:i format (e.g., "18:05", "21:35")
     */
    public static function generateRandomTelegramTime(): string 
    {
        // Use the same logic as Facebook - random times between 18:00-22:00
        return self::generateRandomFacebookTime();
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
