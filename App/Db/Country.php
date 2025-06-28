<?php

namespace App\Db;

class Country
{
    /**
     * Detects the current language from URL
     * 
     * @return string Language code (ro, en, ru)
     */
    private function detectLanguageFromUrl()
    {
        $url = $_SERVER['REQUEST_URI'] ?? '';
        
        // Extract language code from URL pattern like /ru/adminsauto/...
        if (preg_match('#^/([a-z]{2})/#', $url, $matches)) {
            $langCode = $matches[1];
            // Validate that it's one of our supported languages
            if (in_array($langCode, ['ro', 'en', 'ru'])) {
                return $langCode;
            }
        }
        
        // Default to Romanian if no language detected
        return 'ro';
    }
    
    /**
     * Get all countries from database with names in the current language
     * 
     * @param bool $europeanOnly If true, returns only European countries
     * @return array List of countries
     */
    public function getCountries($europeanOnly = false)
    {
        global $db;
        
        // Detect current language from URL
        $langCode = $this->detectLanguageFromUrl();
        
        // Map language code to column name
        switch ($langCode) {
            case 'en':
                $nameColumn = 'name_en';
                break;
            case 'ru':
                $nameColumn = 'name_ru';
                break;
            case 'ro':
            default:
                $nameColumn = 'name_ro';
                break;
        }
        
        // Use the appropriate name column based on the language
        $sql = "SELECT id, {$nameColumn} as name, code, flag, is_european FROM countries";
        if ($europeanOnly) {
            $sql .= " WHERE is_european = 1";
        }
        $sql .= " ORDER BY name ASC";
        
        $stmt = $db->prepare($sql);
        $stmt->execute();
        
        return $stmt->fetchAll();
    }
    
    /**
     * Get country by ID
     * 
     * @param int $id Country ID
     * @return array|bool Country data or false if not found
     */
    public function getCountryById($id)
    {
        global $db;
        
        $sql = "SELECT id, name, code, flag, is_european FROM countries WHERE id = :id";
        $stmt = $db->prepare($sql);
        $stmt->bindParam(':id', $id, \PDO::PARAM_INT);
        $stmt->execute();
        
        return $stmt->fetch();
    }
}
