<?php
/**
 * SAUTO Sitemap Generator - REAL VERSION
 * This version connects to the actual database and generates real sitemaps
 * Use with caution - this affects production data
 */

// Load configuration
require_once __DIR__ . '/config.php';

// Ensure we're running in the correct environment
if (!defined('STDIN')) {
    // Set the correct host for URL generation based on environment
    $_SERVER['HTTP_HOST'] = getSitemapDomain();
    $_SERVER['HTTPS'] = 'on';
    $_SERVER['REQUEST_SCHEME'] = 'https';
}
if (!isset($_SERVER['REMOTE_ADDR'])) {
    $_SERVER['REMOTE_ADDR'] = '127.0.0.1';
}

try {
    require_once __DIR__ . '/../content/default/config.php';
    require_once __DIR__ . '/../content/default/dbi.php';
} catch (Exception $e) {
    echo "Configuration error: " . $e->getMessage() . "\n";
    echo "Using fallback database connection...\n";
    // Fallback will be handled in constructor
}

class SitemapGeneratorReal {
    
    private $baseUrl;
    private $maxUrlsPerFile = 7000;
    private $outputDir = __DIR__ . '/..';
    private $logFile = 'sitemap_generation_real.log';
    private $languages = ['ro', 'ru', 'en'];
    private $db;
    private $prefx;
    
    public function __construct() {
        global $db, $prefx;
        $this->db = $db;
        $this->prefx = $prefx;
        $this->baseUrl = getSitemapBaseUrl();
        $this->log("Real sitemap generation started at " . date('Y-m-d H:i:s'));
    }
    
    /**
     * Main generation method
     */
    public function generate() {
        try {
            $this->log("Starting sitemap generation...");
            
            // Backup current files before generation
            $this->backupCurrentFiles();
            
            // Get all pages
            $pages = $this->getAllPages();
            $this->log("Found " . count($pages) . " total pages");
            
            // Filter and process pages
            $pages = $this->filterPages($pages);
            $pages = $this->removeDuplicateUrls($pages);
            $pages = $this->processPages($pages);
            
            $this->log("Processing " . count($pages) . " unique pages");
            
            // Split into sub-files
            $subFiles = [];
            $chunks = array_chunk($pages, $this->maxUrlsPerFile);
            
            foreach ($chunks as $index => $chunk) {
                $fileName = 'sitemap-' . ($index + 1) . '.xml';
                $this->generateSubFile($fileName, $chunk);
                $subFiles[] = $fileName;
            }
            
            // Generate main index file
            $this->generateIndexFile($subFiles);
            
            // Validate all files
            $isValid = $this->validateFiles($subFiles);
            
            if (!$isValid) {
                $this->log("ERROR: Generated files failed validation");
                $this->fallbackToPreviousVersion();
                return false;
            }
            
            $this->log("Sitemap generation completed successfully!");
            return true;
            
        } catch (Exception $e) {
            $this->log("ERROR during generation: " . $e->getMessage());
            $this->fallbackToPreviousVersion();
            return false;
        }
    }
    
    /**
     * Get all pages from real SAUTO database
     */
    private function getAllPages() {
        $pages = [];
        
        // Get cars from real database
        $cars = $this->getCarsFromDatabase();
        foreach ($cars as $car) {
            $pages[] = [
                'type' => 'car',
                'url' => '/ro/cars/' . $car['id'],
                'lastmod' => $car['updated_at'] ?: $car['created_at'],
                'status' => $car['status'],
                'created_at' => $car['created_at'],
                'translations' => $this->getCarTranslations($car['id'])
            ];
        }
        
        // Get tires from real database
        $tires = $this->getTiresFromDatabase();
        foreach ($tires as $tire) {
            $pages[] = [
                'type' => 'tire',
                'url' => '/ro/tires/' . $tire['slug'],
                'lastmod' => $tire['updated_at'] ?: $tire['created_at'],
                'status' => 'active',
                'translations' => $this->getTireTranslations($tire['id'])
            ];
        }
        
        // Get static pages
        $staticPages = $this->getStaticPages();
        foreach ($staticPages as $page) {
            $pages[] = [
                'type' => 'static',
                'url' => $page['url'],
                'lastmod' => $page['updated_at'],
                'status' => 'active',
                'page_type' => $page['type'],
                'translations' => $page['translations']
            ];
        }
        
        return $pages;
    }
    
    /**
     * Get cars from real SAUTO database
     */
    private function getCarsFromDatabase() {
        try {
            // Query to get all cars (active and recently sold for SEO)
            $sql = "SELECT 
                        id, 
                        created_at, 
                        updated_at,
                        status,
                        sold_date
                    FROM {$this->prefx}_cars 
                    WHERE deleted = 0 
                    AND (status = 'active' OR (status = 'sold' AND sold_date > DATE_SUB(NOW(), INTERVAL 6 MONTH)))
                    ORDER BY created_at DESC";
            
            $stmt = $this->db->prepare($sql);
            $stmt->execute();
            $results = $stmt->fetchAll();
            
            $cars = [];
            foreach ($results as $row) {
                $cars[] = [
                    'id' => $row['id'],
                    'created_at' => $row['created_at'],
                    'updated_at' => $row['updated_at'],
                    'status' => $row['status'] === 'active' ? 'in_stock' : 'out_of_stock'
                ];
            }
            
            $this->log("Retrieved " . count($cars) . " cars from database");
            return $cars;
            
        } catch (Exception $e) {
            $this->log("ERROR getting cars: " . $e->getMessage());
            return [];
        }
    }
    
    /**
     * Get tires from real SAUTO database
     */
    private function getTiresFromDatabase() {
        try {
            $sql = "SELECT 
                        id,
                        slug,
                        created_at,
                        updated_at
                    FROM {$this->prefx}_tyres 
                    WHERE deleted = 0 
                    AND status = 'active'
                    ORDER BY created_at DESC";
            
            $stmt = $this->db->prepare($sql);
            $stmt->execute();
            $results = $stmt->fetchAll();
            
            $this->log("Retrieved " . count($results) . " tires from database");
            return $results;
            
        } catch (Exception $e) {
            $this->log("ERROR getting tires: " . $e->getMessage());
            return [];
        }
    }
    
    /**
     * Get static pages
     */
    private function getStaticPages() {
        // Static pages that should be in sitemap
        return [
            [
                'url' => '/ro/',
                'updated_at' => date('Y-m-d'),
                'type' => 'useful',
                'translations' => ['ro', 'ru', 'en']
            ],
            [
                'url' => '/ro/cars',
                'updated_at' => date('Y-m-d'),
                'type' => 'useful',
                'translations' => ['ro', 'ru', 'en']
            ],
            [
                'url' => '/ro/tires',
                'updated_at' => date('Y-m-d'),
                'type' => 'useful',
                'translations' => ['ro', 'ru', 'en']
            ],
            [
                'url' => '/ro/contacts',
                'updated_at' => date('Y-m-d', strtotime('-30 days')),
                'type' => 'useful',
                'translations' => ['ro', 'ru', 'en']
            ],
            [
                'url' => '/ro/about',
                'updated_at' => date('Y-m-d', strtotime('-60 days')),
                'type' => 'useful',
                'translations' => ['ro', 'ru', 'en']
            ],
            [
                'url' => '/ro/privacy-policy',
                'updated_at' => date('Y-m-d', strtotime('-90 days')),
                'type' => 'legal',
                'translations' => ['ro', 'ru']
            ],
            [
                'url' => '/ro/terms',
                'updated_at' => date('Y-m-d', strtotime('-90 days')),
                'type' => 'legal',
                'translations' => ['ro', 'ru']
            ]
        ];
    }
    
    /**
     * Get available translations for a car
     */
    private function getCarTranslations($carId) {
        try {
            // Check which language versions exist for this car
            $translations = ['ro']; // Romanian is always available
            
            // Check if Russian translation exists
            $sql = "SELECT COUNT(*) as count FROM {$this->prefx}_cars_lang 
                    WHERE car_id = ? AND lang = 'ru' AND title IS NOT NULL AND title != ''";
            $stmt = $this->db->prepare($sql);
            $stmt->execute([$carId]);
            $result = $stmt->fetch();
            if ($result && $result['count'] > 0) {
                $translations[] = 'ru';
            }
            
            // Check if English translation exists
            $sql = "SELECT COUNT(*) as count FROM {$this->prefx}_cars_lang 
                    WHERE car_id = ? AND lang = 'en' AND title IS NOT NULL AND title != ''";
            $stmt = $this->db->prepare($sql);
            $stmt->execute([$carId]);
            $result = $stmt->fetch();
            if ($result && $result['count'] > 0) {
                $translations[] = 'en';
            }
            
            return $translations;
            
        } catch (Exception $e) {
            $this->log("ERROR getting car translations for ID $carId: " . $e->getMessage());
            return ['ro']; // Fallback to Romanian only
        }
    }
    
    /**
     * Get available translations for a tire
     */
    private function getTireTranslations($tireId) {
        try {
            // Check which language versions exist for this tire
            $translations = ['ro']; // Romanian is always available
            
            // Check if Russian translation exists
            $sql = "SELECT COUNT(*) as count FROM {$this->prefx}_tyres_lang 
                    WHERE tyre_id = ? AND lang = 'ru' AND title IS NOT NULL AND title != ''";
            $stmt = $this->db->prepare($sql);
            $stmt->execute([$tireId]);
            $result = $stmt->fetch();
            if ($result && $result['count'] > 0) {
                $translations[] = 'ru';
            }
            
            // Check if English translation exists
            $sql = "SELECT COUNT(*) as count FROM {$this->prefx}_tyres_lang 
                    WHERE tyre_id = ? AND lang = 'en' AND title IS NOT NULL AND title != ''";
            $stmt = $this->db->prepare($sql);
            $stmt->execute([$tireId]);
            $result = $stmt->fetch();
            if ($result && $result['count'] > 0) {
                $translations[] = 'en';
            }
            
            return $translations;
            
        } catch (Exception $e) {
            $this->log("ERROR getting tire translations for ID $tireId: " . $e->getMessage());
            return ['ro']; // Fallback to Romanian only
        }
    }
    
    // Include all other methods from the original generator
    // (filterPages, processPages, calculatePriority, etc.)
    
    /**
     * Filter pages according to inclusion rules
     */
    private function filterPages($pages) {
        $filtered = [];
        
        foreach ($pages as $page) {
            // Skip deleted items
            if ($page['status'] === 'deleted') {
                continue;
            }
            
            // Basic URL validation
            if (!$this->isCanonicalUrl($page['url'])) {
                continue;
            }
            
            $filtered[] = $page;
        }
        
        // Remove duplicates
        $filtered = $this->removeDuplicateUrls($filtered);
        
        return $filtered;
    }
    
    /**
     * Process pages: calculate priority, changefreq, format data
     */
    private function processPages($pages) {
        $processed = [];
        
        foreach ($pages as $page) {
            $processedPage = [
                'loc' => $this->baseUrl . $page['url'],
                'lastmod' => date('Y-m-d', strtotime($page['lastmod'])),
                'changefreq' => $this->getChangeFreq($page),
                'priority' => $this->calculatePriority($page),
                'hreflang' => $this->getHrefLangLinks($page)
            ];
            
            $processed[] = $processedPage;
        }
        
        // Shuffle for mixed content
        shuffle($processed);
        
        return $processed;
    }
    
    /**
     * Calculate priority based on page type and rules
     */
    private function calculatePriority($page) {
        switch ($page['type']) {
            case 'car':
                if ($page['status'] === 'out_of_stock') {
                    return 0.2;
                }
                
                $daysOld = (time() - strtotime($page['created_at'])) / (24 * 3600);
                
                if ($daysOld <= 30) return 1.0;
                if ($daysOld <= 60) return 0.9;
                if ($daysOld <= 120) return 0.7;
                return 0.6;
                
            case 'tire':
                return 0.3;
                
            case 'static':
                if ($page['page_type'] === 'useful') return 0.5;
                return 0.3;
                
            default:
                return 0.5;
        }
    }
    
    /**
     * Get change frequency based on page type
     */
    private function getChangeFreq($page) {
        switch ($page['type']) {
            case 'car':
                return 'daily';
            case 'tire':
                return 'weekly';
            case 'static':
                return 'monthly';
            default:
                return 'weekly';
        }
    }
    
    /**
     * Get hreflang links for multilingual support
     */
    private function getHrefLangLinks($page) {
        $links = [];
        
        if (isset($page['translations']) && is_array($page['translations'])) {
            foreach ($page['translations'] as $lang) {
                if (in_array($lang, $this->languages)) {
                    $url = str_replace('/ro/', "/$lang/", $page['url']);
                    $links[] = [
                        'hreflang' => $lang,
                        'href' => $this->baseUrl . $url
                    ];
                }
            }
        }
        
        return $links;
    }
    
    /**
     * Generate sub-file with URLs
     */
    private function generateSubFile($fileName, $pages) {
        $xml = new DOMDocument('1.0', 'UTF-8');
        $xml->formatOutput = true;
        
        $urlset = $xml->createElement('urlset');
        $urlset->setAttribute('xmlns', 'http://www.sitemaps.org/schemas/sitemap/0.9');
        $urlset->setAttribute('xmlns:xsi', 'http://www.w3.org/2001/XMLSchema-instance');
        $urlset->setAttribute('xmlns:xhtml', 'http://www.w3.org/1999/xhtml');
        $urlset->setAttribute('xsi:schemaLocation',
            'http://www.sitemaps.org/schemas/sitemap/0.9 ' .
            'http://www.sitemaps.org/schemas/sitemap/0.9/sitemap.xsd');
        
        $xml->appendChild($urlset);
        
        foreach ($pages as $page) {
            $url = $xml->createElement('url');
            $url->appendChild($xml->createElement('loc', htmlspecialchars($page['loc'])));
            $url->appendChild($xml->createElement('lastmod', $page['lastmod']));
            $url->appendChild($xml->createElement('changefreq', $page['changefreq']));
            $url->appendChild($xml->createElement('priority', $page['priority']));
            
            // Add hreflang links
            foreach ($page['hreflang'] as $link) {
                $hrefLang = $xml->createElement('xhtml:link');
                $hrefLang->setAttribute('rel', 'alternate');
                $hrefLang->setAttribute('hreflang', $link['hreflang']);
                $hrefLang->setAttribute('href', htmlspecialchars($link['href']));
                $url->appendChild($hrefLang);
            }
            
            $urlset->appendChild($url);
        }
        
        $filePath = $this->outputDir . '/' . $fileName;
        $xml->save($filePath);
        
        $this->log("Generated sub-file: $fileName with " . count($pages) . " URLs");
    }
    
    /**
     * Generate main index file
     */
    private function generateIndexFile($subFiles) {
        $xml = new DOMDocument('1.0', 'UTF-8');
        $xml->formatOutput = true;
        
        $sitemapindex = $xml->createElement('sitemapindex');
        $sitemapindex->setAttribute('xmlns', 'http://www.sitemaps.org/schemas/sitemap/0.9');
        $sitemapindex->setAttribute('xmlns:xsi', 'http://www.w3.org/2001/XMLSchema-instance');
        $sitemapindex->setAttribute('xsi:schemaLocation',
            'http://www.sitemaps.org/schemas/sitemap/0.9 ' .
            'http://www.sitemaps.org/schemas/sitemap/0.9/siteindex.xsd');
        
        $xml->appendChild($sitemapindex);
        
        foreach ($subFiles as $subFile) {
            $sitemap = $xml->createElement('sitemap');
            $sitemap->appendChild($xml->createElement('loc', $this->baseUrl . '/' . $subFile));
            $sitemap->appendChild($xml->createElement('lastmod', date('Y-m-d')));
            $sitemapindex->appendChild($sitemap);
        }
        
        $xml->save($this->outputDir . '/sitemap.xml');
        $this->log("Generated main index file with " . count($subFiles) . " sub-files");
    }
    
    /**
     * Validate generated XML files
     */
    private function validateFiles($subFiles) {
        $errors = [];
        
        // Validate main index file
        $indexPath = $this->outputDir . '/sitemap.xml';
        if (!file_exists($indexPath)) {
            $errors[] = "Main sitemap.xml file not found";
            $this->log("ERROR: Main sitemap.xml file not found");
            return false;
        }
        
        $indexErrors = $this->validateXmlFile($indexPath, 'index');
        if (!empty($indexErrors)) {
            $errors = array_merge($errors, $indexErrors);
        }
        
        // Validate each sub-file
        foreach ($subFiles as $subFile) {
            $subPath = $this->outputDir . '/' . $subFile;
            if (!file_exists($subPath)) {
                $errors[] = "Sub-file $subFile not found";
                continue;
            }
            
            $subErrors = $this->validateXmlFile($subPath, 'urlset');
            if (!empty($subErrors)) {
                $errors = array_merge($errors, $subErrors);
            }
            
            // Check URL count limit
            $urlCount = $this->countUrlsInFile($subPath);
            if ($urlCount > $this->maxUrlsPerFile) {
                $errors[] = "$subFile exceeds maximum URLs: $urlCount > {$this->maxUrlsPerFile}";
            }
        }
        
        if (empty($errors)) {
            $this->log("All sitemap files validated successfully!");
            return true;
        } else {
            $this->log("Validation errors found:");
            foreach ($errors as $error) {
                $this->log("  - $error");
            }
            return false;
        }
    }
    
    /**
     * Validate individual XML file
     */
    private function validateXmlFile($filePath, $type) {
        $errors = [];
        
        // Check if file is readable
        if (!is_readable($filePath)) {
            $errors[] = "File $filePath is not readable";
            return $errors;
        }
        
        // Load and validate XML structure
        libxml_use_internal_errors(true);
        $xml = new DOMDocument();
        $xml->load($filePath);
        
        $xmlErrors = libxml_get_errors();
        if (!empty($xmlErrors)) {
            foreach ($xmlErrors as $error) {
                $errors[] = "XML Error in $filePath: " . trim($error->message);
            }
            libxml_clear_errors();
            return $errors;
        }
        
        // Validate structure based on type
        if ($type === 'index') {
            $errors = array_merge($errors, $this->validateSitemapIndex($xml, $filePath));
        } else {
            $errors = array_merge($errors, $this->validateUrlset($xml, $filePath));
        }
        
        return $errors;
    }
    
    /**
     * Validate sitemap index structure
     */
    private function validateSitemapIndex($xml, $filePath) {
        $errors = [];
        
        // Check root element
        $root = $xml->documentElement;
        if ($root->nodeName !== 'sitemapindex') {
            $errors[] = "$filePath: Root element should be 'sitemapindex', found '{$root->nodeName}'";
        }
        
        // Check namespace
        if ($root->getAttribute('xmlns') !== 'http://www.sitemaps.org/schemas/sitemap/0.9') {
            $errors[] = "$filePath: Missing or incorrect xmlns attribute";
        }
        
        // Check sitemap entries
        $sitemaps = $xml->getElementsByTagName('sitemap');
        if ($sitemaps->length === 0) {
            $errors[] = "$filePath: No sitemap entries found";
        }
        
        foreach ($sitemaps as $sitemap) {
            $loc = $sitemap->getElementsByTagName('loc');
            $lastmod = $sitemap->getElementsByTagName('lastmod');
            
            if ($loc->length === 0) {
                $errors[] = "$filePath: Sitemap entry missing <loc> tag";
            } else {
                $url = $loc->item(0)->textContent;
                if (!filter_var($url, FILTER_VALIDATE_URL) || !str_starts_with($url, 'https://')) {
                    $errors[] = "$filePath: Invalid URL in <loc>: $url";
                }
            }
            
            if ($lastmod->length === 0) {
                $errors[] = "$filePath: Sitemap entry missing <lastmod> tag";
            } else {
                $date = $lastmod->item(0)->textContent;
                if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $date)) {
                    $errors[] = "$filePath: Invalid date format in <lastmod>: $date";
                }
            }
        }
        
        return $errors;
    }
    
    /**
     * Validate urlset structure
     */
    private function validateUrlset($xml, $filePath) {
        $errors = [];
        
        // Check root element
        $root = $xml->documentElement;
        if ($root->nodeName !== 'urlset') {
            $errors[] = "$filePath: Root element should be 'urlset', found '{$root->nodeName}'";
        }
        
        // Check namespaces
        $requiredNamespaces = [
            'xmlns' => 'http://www.sitemaps.org/schemas/sitemap/0.9',
            'xmlns:xsi' => 'http://www.w3.org/2001/XMLSchema-instance',
            'xmlns:xhtml' => 'http://www.w3.org/1999/xhtml'
        ];
        
        foreach ($requiredNamespaces as $attr => $expectedValue) {
            if ($root->getAttribute($attr) !== $expectedValue) {
                $errors[] = "$filePath: Missing or incorrect $attr attribute";
            }
        }
        
        // Check URL entries
        $urls = $xml->getElementsByTagName('url');
        $seenUrls = [];
        
        foreach ($urls as $url) {
            $urlErrors = $this->validateUrlEntry($url, $filePath);
            $errors = array_merge($errors, $urlErrors);
            
            // Check for duplicates
            $loc = $url->getElementsByTagName('loc');
            if ($loc->length > 0) {
                $urlValue = $loc->item(0)->textContent;
                if (in_array($urlValue, $seenUrls)) {
                    $errors[] = "$filePath: Duplicate URL found: $urlValue";
                } else {
                    $seenUrls[] = $urlValue;
                }
            }
        }
        
        return $errors;
    }
    
    /**
     * Validate individual URL entry
     */
    private function validateUrlEntry($urlNode, $filePath) {
        $errors = [];
        $requiredTags = ['loc', 'lastmod', 'changefreq', 'priority'];
        
        foreach ($requiredTags as $tag) {
            $elements = $urlNode->getElementsByTagName($tag);
            if ($elements->length === 0) {
                $errors[] = "$filePath: URL entry missing <$tag> tag";
            } else {
                $value = $elements->item(0)->textContent;
                $errors = array_merge($errors, $this->validateTagValue($tag, $value, $filePath));
            }
        }
        
        // Validate hreflang links
        $hreflangs = $urlNode->getElementsByTagName('link');
        foreach ($hreflangs as $hreflang) {
            if ($hreflang->getAttribute('rel') !== 'alternate') {
                $errors[] = "$filePath: hreflang link missing rel='alternate'";
            }
            
            $href = $hreflang->getAttribute('href');
            if (!filter_var($href, FILTER_VALIDATE_URL) || !str_starts_with($href, 'https://')) {
                $errors[] = "$filePath: Invalid hreflang href: $href";
            }
            
            $lang = $hreflang->getAttribute('hreflang');
            if (!in_array($lang, $this->languages)) {
                $errors[] = "$filePath: Invalid hreflang language: $lang";
            }
        }
        
        return $errors;
    }
    
    /**
     * Validate individual tag values
     */
    private function validateTagValue($tag, $value, $filePath) {
        $errors = [];
        
        switch ($tag) {
            case 'loc':
                if (!filter_var($value, FILTER_VALIDATE_URL) || !str_starts_with($value, 'https://')) {
                    $errors[] = "$filePath: Invalid URL in <loc>: $value";
                }
                if (!str_contains($value, '/ro/')) {
                    $errors[] = "$filePath: URL should contain /ro/ language prefix: $value";
                }
                break;
                
            case 'lastmod':
                if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $value)) {
                    $errors[] = "$filePath: Invalid date format in <lastmod>: $value (should be YYYY-MM-DD)";
                }
                break;
                
            case 'changefreq':
                $validFreqs = ['always', 'hourly', 'daily', 'weekly', 'monthly', 'yearly', 'never'];
                if (!in_array($value, $validFreqs)) {
                    $errors[] = "$filePath: Invalid <changefreq> value: $value";
                }
                break;
                
            case 'priority':
                if (!is_numeric($value) || $value < 0.0 || $value > 1.0) {
                    $errors[] = "$filePath: Invalid <priority> value: $value (should be 0.0-1.0)";
                }
                if ($value == 0.0) {
                    $errors[] = "$filePath: Priority should never be 0.0: $value";
                }
                break;
        }
        
        return $errors;
    }
    
    /**
     * Count URLs in a sitemap file
     */
    private function countUrlsInFile($filePath) {
        $xml = new DOMDocument();
        $xml->load($filePath);
        $urls = $xml->getElementsByTagName('url');
        return $urls->length;
    }
    
    /**
     * Fallback to previous version on error
     */
    private function fallbackToPreviousVersion() {
        $this->log("Attempting fallback to previous valid version");
        
        $backupDir = $this->outputDir . '/sitemap_backup';
        
        // Check if backup directory exists
        if (!is_dir($backupDir)) {
            $this->log("ERROR: No backup directory found at $backupDir");
            return false;
        }
        
        // Get list of backup files
        $backupFiles = glob($backupDir . '/sitemap*.xml');
        if (empty($backupFiles)) {
            $this->log("ERROR: No backup files found in $backupDir");
            return false;
        }
        
        $restored = 0;
        foreach ($backupFiles as $backupFile) {
            $fileName = basename($backupFile);
            $targetFile = $this->outputDir . '/' . $fileName;
            
            if (copy($backupFile, $targetFile)) {
                $this->log("Restored: $fileName");
                $restored++;
            } else {
                $this->log("ERROR: Failed to restore $fileName");
            }
        }
        
        if ($restored > 0) {
            $this->log("Successfully restored $restored files from backup");
            return true;
        } else {
            $this->log("ERROR: Failed to restore any files from backup");
            return false;
        }
    }
    
    /**
     * Backup current sitemap files before generating new ones
     */
    private function backupCurrentFiles() {
        $backupDir = $this->outputDir . '/sitemap_backup';
        
        // Create backup directory if it doesn't exist
        if (!is_dir($backupDir)) {
            if (!mkdir($backupDir, 0755, true)) {
                $this->log("ERROR: Failed to create backup directory: $backupDir");
                return false;
            }
        }
        
        // Backup existing sitemap files
        $sitemapFiles = glob($this->outputDir . '/sitemap*.xml');
        $backed = 0;
        
        foreach ($sitemapFiles as $file) {
            $fileName = basename($file);
            $backupFile = $backupDir . '/' . $fileName;
            
            if (copy($file, $backupFile)) {
                $this->log("Backed up: $fileName");
                $backed++;
            } else {
                $this->log("WARNING: Failed to backup $fileName");
            }
        }
        
        $this->log("Backed up $backed sitemap files");
        return $backed > 0;
    }
    
    /**
     * Log messages
     */
    private function log($message) {
        $timestamp = date('Y-m-d H:i:s');
        $logEntry = "[$timestamp] $message" . PHP_EOL;
        file_put_contents($this->logFile, $logEntry, FILE_APPEND | LOCK_EX);
        echo $logEntry;
    }
    
    // Helper methods
    private function isCanonicalUrl($url) {
        if (empty($url)) {
            return false;
        }
        
        // Check for query parameters (filtering, sorting, utm, etc.)
        if (strpos($url, '?') !== false) {
            return false;
        }
        
        // Check for fragment identifiers
        if (strpos($url, '#') !== false) {
            return false;
        }
        
        // Check for session IDs or tracking parameters in URL path
        $invalidPatterns = [
            '/sessionid=/',
            '/sid=/',
            '/PHPSESSID=/',
            '/utm_/',
            '/ref=/',
            '/source=/',
            '/campaign=/',
            '/sort=/',
            '/filter=/',
            '/page=/',
            '/limit=/',
            '/offset=/'
        ];
        
        foreach ($invalidPatterns as $pattern) {
            if (preg_match($pattern, $url)) {
                return false;
            }
        }
        
        // Check if URL follows expected structure
        $validPatterns = [
            '/\/ro\/cars\/\d+$/',           // /ro/cars/123
            '/\/ro\/tires\/[a-z0-9-]+$/',    // /ro/tires/tire-slug
            '/\/ro\/[a-z-]+$/',             // /ro/contact, /ro/about
            '/\/ro\/$/'                      // /ro/
        ];
        
        foreach ($validPatterns as $pattern) {
            if (preg_match($pattern, $url)) {
                return true;
            }
        }
        
        // If no pattern matches, it's likely not canonical
        return false;
    }
    
    /**
     * Check if URL returns HTTP 200 status
     */
    private function isUrlAccessible($url) {
        try {
            $context = stream_context_create([
                'http' => [
                    'method' => 'HEAD',
                    'timeout' => 5,
                    'user_agent' => 'SAUTO Sitemap Generator/1.0'
                ]
            ]);
            
            $headers = get_headers($url, 1, $context);
            
            if ($headers && isset($headers[0])) {
                return strpos($headers[0], '200') !== false;
            }
            
            return false;
            
        } catch (Exception $e) {
            $this->log("WARNING: Could not check URL accessibility for $url: " . $e->getMessage());
            return true; // Assume accessible to avoid blocking generation
        }
    }
    
    private function removeDuplicateUrls($pages) {
        $seen = [];
        $unique = [];
        
        foreach ($pages as $page) {
            if (!in_array($page['url'], $seen)) {
                $seen[] = $page['url'];
                $unique[] = $page;
            }
        }
        
        return $unique;
    }
}

// Run the generator
if (php_sapi_name() === 'cli') {
    $generator = new SitemapGeneratorReal();
    $generator->generate();
} else {
    echo "This script should be run from command line only.";
}
?>
