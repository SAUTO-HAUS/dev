<?php
/**
 * SAUTO Sitemap Generator
 * Automatic daily sitemap generation script
 * Follows XML Sitemap Protocol specification
 */

class SitemapGenerator {
    
    private $baseUrl = 'https://www.sauto.md';
    private $maxUrlsPerFile = 7000;
    private $outputDir = __DIR__;
    private $logFile = 'sitemap_generation.log';
    private $languages = ['ro', 'ru', 'en'];
    
    public function __construct() {
        $this->log("Sitemap generation started at " . date('Y-m-d H:i:s'));
    }
    
    /**
     * Main generation method
     */
    public function generate() {
        try {
            // Step 1: Get all pages
            $allPages = $this->getAllPages();
            $this->log("Found " . count($allPages) . " total pages");
            
            // Step 2: Filter and validate pages
            $validPages = $this->filterPages($allPages);
            $this->log("After filtering: " . count($validPages) . " valid pages");
            
            // Step 3: Calculate priorities and metadata
            $processedPages = $this->processPages($validPages);
            
            // Step 4: Split into sub-files
            $chunks = array_chunk($processedPages, $this->maxUrlsPerFile);
            $this->log("Split into " . count($chunks) . " sub-files");
            
            // Step 5: Generate sub-files
            $subFiles = [];
            foreach ($chunks as $index => $chunk) {
                $fileName = 'sitemap-' . ($index + 1) . '.xml';
                $this->generateSubFile($fileName, $chunk);
                $subFiles[] = $fileName;
            }
            
            // Step 6: Generate main index file
            $this->generateIndexFile($subFiles);
            
            // Step 7: Validate all files
            $this->validateFiles($subFiles);
            
            $this->log("Sitemap generation completed successfully");
            
        } catch (Exception $e) {
            $this->log("ERROR: " . $e->getMessage());
            $this->fallbackToPreviousVersion();
        }
    }
    
    /**
     * Get all pages from database/API
     */
    private function getAllPages() {
        $pages = [];
        
        // Get cars
        $cars = $this->getCarsFromDatabase();
        foreach ($cars as $car) {
            $pages[] = [
                'type' => 'car',
                'url' => '/ro/cars/' . $car['id'],
                'lastmod' => $car['updated_at'] ?: $car['created_at'],
                'status' => $car['status'], // 'in_stock', 'out_of_stock', 'deleted'
                'created_at' => $car['created_at'],
                'translations' => $car['translations'] // array of available languages
            ];
        }
        
        // Get tires
        $tires = $this->getTiresFromDatabase();
        foreach ($tires as $tire) {
            $pages[] = [
                'type' => 'tire',
                'url' => '/ro/tires/' . $tire['slug'],
                'lastmod' => $tire['updated_at'] ?: $tire['created_at'],
                'status' => 'active',
                'translations' => $tire['translations']
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
                'page_type' => $page['type'], // 'useful', 'legal'
                'translations' => $page['translations']
            ];
        }
        
        return $pages;
    }
    
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
            
            // Check if page is accessible (HTTP 200)
            if (!$this->isPageAccessible($page['url'])) {
                continue;
            }
            
            // Check if page is not restricted from indexing
            if ($this->isIndexingRestricted($page['url'])) {
                continue;
            }
            
            // Ensure URL is canonical
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
     * Generate hreflang links for multilingual support
     */
    private function getHrefLangLinks($page) {
        $links = [];
        
        if (!empty($page['translations'])) {
            foreach ($page['translations'] as $lang) {
                if (in_array($lang, $this->languages)) {
                    $url = str_replace('/ro/', '/' . $lang . '/', $page['url']);
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
     * Generate sub-file XML
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
                $hrefLang->setAttribute('href', $link['href']);
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
        $allFiles = array_merge(['sitemap.xml'], $subFiles);
        
        foreach ($allFiles as $file) {
            $filePath = $this->outputDir . '/' . $file;
            
            if (!file_exists($filePath)) {
                throw new Exception("File not found: $file");
            }
            
            // Validate XML structure
            $xml = new DOMDocument();
            if (!$xml->load($filePath)) {
                throw new Exception("Invalid XML in file: $file");
            }
            
            $this->log("Validated: $file");
        }
    }
    
    /**
     * Fallback to previous valid version on error
     */
    private function fallbackToPreviousVersion() {
        $this->log("Attempting fallback to previous valid version");
        // Implementation would restore backup files
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
    
    // Database/API methods (to be implemented based on actual data source)
    private function getCarsFromDatabase() {
        // Implementation depends on actual database structure
        return [];
    }
    
    private function getTiresFromDatabase() {
        // Implementation depends on actual database structure
        return [];
    }
    
    private function getStaticPages() {
        // Implementation depends on actual CMS/static pages structure
        return [];
    }
    
    private function isPageAccessible($url) {
        // Check if page returns HTTP 200
        return true; // Placeholder
    }
    
    private function isIndexingRestricted($url) {
        // Check meta robots, headers, etc.
        return false; // Placeholder
    }
    
    private function isCanonicalUrl($url) {
        // Check if URL is canonical (no GET parameters, etc.)
        return true; // Placeholder
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
    $generator = new SitemapGenerator();
    $generator->generate();
}
?>
