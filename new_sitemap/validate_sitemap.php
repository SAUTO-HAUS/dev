<?php
/**
 * SAUTO Sitemap Validation Script
 * Validates generated sitemap files according to XML Sitemap Protocol
 */

// Load configuration
require_once __DIR__ . '/config.php';

class SitemapValidator {
    
    private $logFile = 'validation.log';
    private $requiredTags = ['loc', 'lastmod', 'changefreq', 'priority'];
    
    public function __construct() {
        $this->log("Sitemap validation started at " . date('Y-m-d H:i:s'));
    }
    
    /**
     * Validate all sitemap files
     */
    public function validateAll() {
        $errors = [];
        
        try {
            // Validate main index file
            $indexErrors = $this->validateIndexFile('sitemap.xml');
            if (!empty($indexErrors)) {
                $errors['sitemap.xml'] = $indexErrors;
            }
            
            // Get list of sub-files from index
            $subFiles = $this->getSubFilesFromIndex('sitemap.xml');
            
            // Validate each sub-file
            foreach ($subFiles as $subFile) {
                $subErrors = $this->validateSubFile($subFile);
                if (!empty($subErrors)) {
                    $errors[$subFile] = $subErrors;
                }
            }
            
            if (empty($errors)) {
                $this->log("All sitemap files are valid!");
                return true;
            } else {
                $this->log("Validation errors found:");
                foreach ($errors as $file => $fileErrors) {
                    $this->log("  $file: " . implode(', ', $fileErrors));
                }
                return false;
            }
            
        } catch (Exception $e) {
            $this->log("Validation failed: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Validate main index file
     */
    private function validateIndexFile($fileName) {
        $errors = [];
        $filePath = __DIR__ . '/' . $fileName;
        
        if (!file_exists($filePath)) {
            return ["File does not exist"];
        }
        
        // Load and validate XML structure
        libxml_use_internal_errors(true);
        $xml = new DOMDocument();
        
        if (!$xml->load($filePath)) {
            $xmlErrors = libxml_get_errors();
            foreach ($xmlErrors as $error) {
                $errors[] = "XML Error: " . trim($error->message);
            }
            return $errors;
        }
        
        // Check root element
        if ($xml->documentElement->nodeName !== 'sitemapindex') {
            $errors[] = "Root element should be 'sitemapindex'";
        }
        
        // Check namespace
        $expectedNamespace = 'http://www.sitemaps.org/schemas/sitemap/0.9';
        if ($xml->documentElement->getAttribute('xmlns') !== $expectedNamespace) {
            $errors[] = "Missing or incorrect xmlns attribute";
        }
        
        // Check sitemap entries
        $sitemaps = $xml->getElementsByTagName('sitemap');
        if ($sitemaps->length === 0) {
            $errors[] = "No sitemap entries found";
        }
        
        foreach ($sitemaps as $sitemap) {
            $loc = $sitemap->getElementsByTagName('loc');
            $lastmod = $sitemap->getElementsByTagName('lastmod');
            
            if ($loc->length === 0) {
                $errors[] = "Missing <loc> in sitemap entry";
            } else {
                $url = $loc->item(0)->nodeValue;
                if (!filter_var($url, FILTER_VALIDATE_URL)) {
                    $errors[] = "Invalid URL in <loc>: $url";
                }
            }
            
            if ($lastmod->length === 0) {
                $errors[] = "Missing <lastmod> in sitemap entry";
            } else {
                $date = $lastmod->item(0)->nodeValue;
                if (!$this->isValidDate($date)) {
                    $errors[] = "Invalid date format in <lastmod>: $date";
                }
            }
        }
        
        $this->log("Validated index file: $fileName - " . 
                  (empty($errors) ? "PASS" : count($errors) . " errors"));
        
        return $errors;
    }
    
    /**
     * Validate sub-file
     */
    private function validateSubFile($fileName) {
        $errors = [];
        $filePath = __DIR__ . '/' . $fileName;
        
        if (!file_exists($filePath)) {
            return ["File does not exist"];
        }
        
        // Load and validate XML structure
        libxml_use_internal_errors(true);
        $xml = new DOMDocument();
        
        if (!$xml->load($filePath)) {
            $xmlErrors = libxml_get_errors();
            foreach ($xmlErrors as $error) {
                $errors[] = "XML Error: " . trim($error->message);
            }
            return $errors;
        }
        
        // Check root element
        if ($xml->documentElement->nodeName !== 'urlset') {
            $errors[] = "Root element should be 'urlset'";
        }
        
        // Check namespaces
        $expectedNamespace = 'http://www.sitemaps.org/schemas/sitemap/0.9';
        if ($xml->documentElement->getAttribute('xmlns') !== $expectedNamespace) {
            $errors[] = "Missing or incorrect xmlns attribute";
        }
        
        $expectedXhtmlNamespace = 'http://www.w3.org/1999/xhtml';
        if ($xml->documentElement->getAttribute('xmlns:xhtml') !== $expectedXhtmlNamespace) {
            $errors[] = "Missing or incorrect xmlns:xhtml attribute";
        }
        
        // Check URL entries
        $urls = $xml->getElementsByTagName('url');
        $urlCount = $urls->length;
        
        if ($urlCount === 0) {
            $errors[] = "No URL entries found";
        }
        
        if ($urlCount > 7000) {
            $errors[] = "Too many URLs ($urlCount). Maximum allowed: 7000";
        }
        
        $seenUrls = [];
        
        foreach ($urls as $urlNode) {
            $urlErrors = $this->validateUrlNode($urlNode, $seenUrls);
            $errors = array_merge($errors, $urlErrors);
        }
        
        $this->log("Validated sub-file: $fileName - $urlCount URLs - " . 
                  (empty($errors) ? "PASS" : count($errors) . " errors"));
        
        return $errors;
    }
    
    /**
     * Validate individual URL node
     */
    private function validateUrlNode($urlNode, &$seenUrls) {
        $errors = [];
        
        // Check required tags
        foreach ($this->requiredTags as $tag) {
            $elements = $urlNode->getElementsByTagName($tag);
            if ($elements->length === 0) {
                $errors[] = "Missing required tag: <$tag>";
            }
        }
        
        // Validate <loc>
        $locElements = $urlNode->getElementsByTagName('loc');
        if ($locElements->length > 0) {
            $url = $locElements->item(0)->nodeValue;
            
            if (!filter_var($url, FILTER_VALIDATE_URL)) {
                $errors[] = "Invalid URL: $url";
            }
            
            $expectedDomain = getSitemapBaseUrl() . '/';
            if (!preg_match('/^' . preg_quote($expectedDomain, '/') . '/', $url)) {
                $errors[] = "URL must start with {$expectedDomain}: $url";
            }
            
            // Check for duplicates
            if (in_array($url, $seenUrls)) {
                $errors[] = "Duplicate URL: $url";
            } else {
                $seenUrls[] = $url;
            }
        }
        
        // Validate <lastmod>
        $lastmodElements = $urlNode->getElementsByTagName('lastmod');
        if ($lastmodElements->length > 0) {
            $date = $lastmodElements->item(0)->nodeValue;
            if (!$this->isValidDate($date)) {
                $errors[] = "Invalid lastmod date format: $date";
            }
        }
        
        // Validate <changefreq>
        $changefreqElements = $urlNode->getElementsByTagName('changefreq');
        if ($changefreqElements->length > 0) {
            $changefreq = $changefreqElements->item(0)->nodeValue;
            $validFreqs = ['always', 'hourly', 'daily', 'weekly', 'monthly', 'yearly', 'never'];
            if (!in_array($changefreq, $validFreqs)) {
                $errors[] = "Invalid changefreq value: $changefreq";
            }
        }
        
        // Validate <priority>
        $priorityElements = $urlNode->getElementsByTagName('priority');
        if ($priorityElements->length > 0) {
            $priority = floatval($priorityElements->item(0)->nodeValue);
            if ($priority < 0.0 || $priority > 1.0) {
                $errors[] = "Priority must be between 0.0 and 1.0: $priority";
            }
        }
        
        // Validate hreflang links
        $hreflangElements = $urlNode->getElementsByTagName('link');
        foreach ($hreflangElements as $link) {
            if ($link->getAttribute('rel') === 'alternate') {
                $hreflang = $link->getAttribute('hreflang');
                $href = $link->getAttribute('href');
                
                if (empty($hreflang)) {
                    $errors[] = "Missing hreflang attribute in xhtml:link";
                }
                
                if (!filter_var($href, FILTER_VALIDATE_URL)) {
                    $errors[] = "Invalid href in xhtml:link: $href";
                }
                
                $validLangs = ['ro', 'ru', 'en', 'x-default'];
                if (!in_array($hreflang, $validLangs)) {
                    $errors[] = "Invalid hreflang value: $hreflang";
                }
            }
        }
        
        return $errors;
    }
    
    /**
     * Get sub-files list from index file
     */
    private function getSubFilesFromIndex($indexFile) {
        $subFiles = [];
        $xml = new DOMDocument();
        
        if ($xml->load(__DIR__ . '/' . $indexFile)) {
            $locs = $xml->getElementsByTagName('loc');
            foreach ($locs as $loc) {
                $url = $loc->nodeValue;
                $fileName = basename($url);
                if (preg_match('/^sitemap-\d+\.xml$/', $fileName)) {
                    $subFiles[] = $fileName;
                }
            }
        }
        
        return $subFiles;
    }
    
    /**
     * Check if date is in valid YYYY-MM-DD format
     */
    private function isValidDate($date) {
        return preg_match('/^\d{4}-\d{2}-\d{2}$/', $date) && 
               strtotime($date) !== false;
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
}

// Run validation if called directly
if (php_sapi_name() === 'cli') {
    $validator = new SitemapValidator();
    $isValid = $validator->validateAll();
    exit($isValid ? 0 : 1);
}
?>
