<?php
/**
 * SAUTO Sitemap Generator - REAL VERSION
 * This version connects to the actual database and generates real sitemaps
 * Use with caution - this affects production data
 */

// Normalise host context before loading configuration so environment detection works in CLI
$productionHost = 'www.sauto.md';
$httpsScheme = 'https';

if (PHP_SAPI === 'cli') {
    // When running from CLI there is no HTTP context, so force production host to ensure
    // that configuration picks the correct environment.
    $_SERVER['HTTP_HOST'] = $productionHost;
    $_SERVER['HTTPS'] = 'on';
    $_SERVER['REQUEST_SCHEME'] = $httpsScheme;
    $_SERVER['SERVER_PORT'] = 443;
    $_SERVER['HTTP_USER_AGENT'] = $_SERVER['HTTP_USER_AGENT'] ?? 'CLI';
} elseif (!isset($_SERVER['HTTP_HOST']) || $_SERVER['HTTP_HOST'] === '') {
    // Fallback for other non-web contexts
    $_SERVER['HTTP_HOST'] = $productionHost;
}

if (!isset($_SERVER['REQUEST_URI'])) {
    $_SERVER['REQUEST_URI'] = '/';
}
if (!isset($_SERVER['HTTP_USER_AGENT'])) {
    $_SERVER['HTTP_USER_AGENT'] = 'CLI';
}

// Load configuration
require_once __DIR__ . '/config.php';

// Ensure we're running in the correct environment after configuration is loaded
if (!isset($_SERVER['HTTP_HOST']) || $_SERVER['HTTP_HOST'] !== getSitemapDomain()) {
    $_SERVER['HTTP_HOST'] = getSitemapDomain();
}

if (isProductionEnvironment()) {
    $_SERVER['HTTPS'] = 'on';
    $_SERVER['REQUEST_SCHEME'] = $httpsScheme;
    $_SERVER['SERVER_PORT'] = 443;
}

if (!isset($_SERVER['REMOTE_ADDR'])) {
    $_SERVER['REMOTE_ADDR'] = '127.0.0.1';
}

try {
    if (!defined('_DOIT')) {
        define('_DOIT', 1);
    }
    if (!function_exists('usr_agent')) {
        function usr_agent() {
            return 'CLI';
        }
    }
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
    private $tableColumnsCache = [];
    
    public function __construct() {
        global $db, $prefx;
        $this->db = $db;
        $this->prefx = $prefx;
        $this->baseUrl = getSitemapBaseUrl();
        $this->log("Real sitemap generation started at " . date('Y-m-d H:i:s'));
    }

    /**
     * Check if a database table exists
     */
    private function tableExists($tableName) {
        try {
            $stmt = $this->db->prepare('SHOW TABLES LIKE ?');
            $stmt->execute([$tableName]);
            return $stmt->fetchColumn() !== false;
        } catch (Exception $e) {
            $this->log("WARNING: Unable to verify table existence for {$tableName}: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Get (and cache) column list for a table
     */
    private function getTableColumns($tableName) {
        if (isset($this->tableColumnsCache[$tableName])) {
            return $this->tableColumnsCache[$tableName];
        }

        if (!$this->tableExists($tableName)) {
            $this->tableColumnsCache[$tableName] = [];
            return [];
        }

        try {
            $stmt = $this->db->query("SHOW COLUMNS FROM `{$tableName}`");
            $columns = [];
            foreach ($stmt as $column) {
                if (!empty($column['Field'])) {
                    $columns[] = $column['Field'];
                }
            }
            $this->tableColumnsCache[$tableName] = $columns;
            return $columns;
        } catch (Exception $e) {
            $this->log("WARNING: Unable to fetch columns for {$tableName}: " . $e->getMessage());
            $this->tableColumnsCache[$tableName] = [];
            return [];
        }
    }

    /**
     * Determine if a column exists in a table
     */
    private function columnExists($tableName, $columnName) {
        return in_array($columnName, $this->getTableColumns($tableName), true);
    }

    /**
     * Attempt to parse a column value into a DateTimeImmutable instance
     */
    private function parseDateValue($value) {
        if ($value === null || $value === '' || $value === '0000-00-00' || $value === '0000-00-00 00:00:00') {
            return null;
        }

        try {
            if (is_numeric($value)) {
                $numeric = (int)$value;
                if ($numeric <= 0) {
                    return null;
                }

                // Detect milliseconds timestamps
                if ($numeric > 2000000000 && $numeric < 2000000000000) {
                    $numeric = (int)round($numeric / 1000);
                }

                $date = (new DateTimeImmutable('@' . $numeric))->setTimezone(new DateTimeZone(date_default_timezone_get()));
                return $date;
            }

            $date = new DateTimeImmutable($value);
            return $date;
        } catch (Exception $e) {
            return null;
        }
    }

    /**
     * Extract the first valid date from row data using preferred column order
     */
    private function extractDateTime(array $row, array $preferredKeys) {
        foreach ($preferredKeys as $key) {
            if (array_key_exists($key, $row)) {
                $date = $this->parseDateValue($row[$key]);
                if ($date instanceof DateTimeImmutable) {
                    return $date;
                }
            }
        }
        return null;
    }

    /**
     * Format DateTimeImmutable to sitemap string format
     */
    private function formatDate($date) {
        if ($date instanceof DateTimeInterface) {
            return $date->format('Y-m-d');
        }
        return date('Y-m-d');
    }

    /**
     * Normalise translation language codes to allowed set
     */
    private function normalizeLanguages(array $languages) {
        $normalized = [];
        foreach ($languages as $lang) {
            $lang = strtolower(trim($lang));
            if (in_array($lang, $this->languages, true) && !in_array($lang, $normalized, true)) {
                $normalized[] = $lang;
            }
        }

        if (empty($normalized)) {
            $normalized[] = 'ro';
        }

        return $normalized;
    }

    /**
     * Localise an URL path to a specific language
     */
    private function localizeUrl($path, $language) {
        if (!str_starts_with($path, '/')) {
            $path = '/' . ltrim($path, '/');
        }

        if (preg_match('#^/[a-z]{2}/#', $path)) {
            return '/' . $language . substr($path, 3);
        }

        // Ensure we always have language prefix
        return '/' . $language . (str_starts_with($path, '/') ? $path : '/' . $path);
    }

    /**
     * Get last modification time for a relative file path
     */
    private function getFileLastmod($relativePath) {
        $fullPath = dirname(__DIR__) . '/' . ltrim($relativePath, '/');
        if (file_exists($fullPath)) {
            $timestamp = filemtime($fullPath);
            if ($timestamp !== false) {
                return (new DateTimeImmutable('@' . $timestamp))->setTimezone(new DateTimeZone(date_default_timezone_get()));
            }
        }
        return null;
    }

    /**
     * Determine if a page record should be kept in sitemap
     */
    private function shouldIncludePage(array $page) {
        switch ($page['type']) {
            case 'car':
                if (!empty($page['is_archived']) || !empty($page['is_deleted'])) {
                    return false;
                }

                if (!empty($page['status']) && $page['status'] === 'sold') {
                    if (empty($page['sold_at']) || !($page['sold_at'] instanceof DateTimeInterface)) {
                        return false;
                    }
                }
                return true;

            case 'tire':
                if (!empty($page['is_archived']) || !empty($page['is_deleted'])) {
                    return false;
                }
                return true;

            case 'static':
                return true;

            default:
                return false;
        }
    }

    /**
     * Retrieve available translations for an entity
     */
    private function getItemTranslations($type, $itemId) {
        $table = $this->prefx . '_seo2';
        if (!$this->tableExists($table)) {
            return $this->languages;
        }

        try {
            $stmt = $this->db->prepare("SELECT DISTINCT `lng` FROM `{$table}` WHERE `tp` = :tp AND `p1` = :p1 AND `it_id` = :id");
            $stmt->execute([
                ':tp' => 'item',
                ':p1' => $type,
                ':id' => $itemId
            ]);

            $languages = [];
            foreach ($stmt as $row) {
                if (!empty($row['lng'])) {
                    $languages[] = $row['lng'];
                }
            }

            $languages = $this->normalizeLanguages($languages);

            if (!in_array('ro', $languages, true)) {
                array_unshift($languages, 'ro');
            }

            return $languages;
        } catch (Exception $e) {
            $this->log("WARNING: Unable to fetch translations for {$type} #{$itemId}: " . $e->getMessage());
            return $this->languages;
        }
    }

    /**
     * Remove obsolete sitemap files that are no longer generated
     */
    private function cleanupOldSitemaps(array $generatedFiles) {
        $existing = glob($this->outputDir . '/sitemap-*.xml');
        $generatedSet = array_map(fn($file) => $this->outputDir . '/' . $file, $generatedFiles);

        foreach ($existing as $filePath) {
            if (!in_array($filePath, $generatedSet, true)) {
                @unlink($filePath);
            }
        }
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

            $this->cleanupOldSitemaps($subFiles);

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
        $pages = array_merge($pages, $this->getCarsFromDatabase());
        $pages = array_merge($pages, $this->getTiresFromDatabase());
        $pages = array_merge($pages, $this->getStaticPages());
        return $pages;
    }
    
    /**
     * Retrieve car entries from the database with filtering applied
     */
    private function getCarsFromDatabase() {
        $pages = [];

        try {
            if ($this->tableExists('cars')) {
                $pages = array_merge($pages, $this->fetchCarsFromModernTable());
            }

            if (empty($pages)) {
                $pages = array_merge($pages, $this->fetchCarsFromLegacyCatalog());
            }

            $this->log('Retrieved ' . count($pages) . ' cars for sitemap inclusion');
        } catch (Exception $e) {
            $this->log('ERROR retrieving cars: ' . $e->getMessage());
        }

        return $pages;
    }
    
    /**
     * Retrieve tyre entries from the database
     */
    private function getTiresFromDatabase() {
        $pages = [];
        $table = $this->prefx . '_tyre_ctlg';

        try {
            $columns = $this->getTableColumns($table);
            if (empty($columns)) {
                return [];
            }

            $preferred = [
                'n_a', 'act', 'vis', 'created_at', 'created', 'date', 'updated_at',
                'updated', 'upd', 'modified_at', 'last_update', 'deleted', 'it'
            ];

            $selectColumns = array_unique(array_merge(['id'], array_intersect($preferred, $columns)));
            $columnList = implode(', ', array_map(fn($col) => "`$col`", $selectColumns));

            $sql = "SELECT {$columnList} FROM `{$table}` WHERE 1=1";
            if (in_array('act', $columns, true)) {
                $sql .= " AND `act` = 1";
            }
            if (in_array('vis', $columns, true)) {
                $sql .= " AND `vis` = 1";
            }
            if (in_array('deleted', $columns, true)) {
                $sql .= " AND (`deleted` = 0 OR `deleted` IS NULL)";
            }
            if (in_array('it', $columns, true)) {
                $sql .= " AND (`it` IS NULL OR `it` != 'it_arh')";
            }

            $stmt = $this->db->query($sql);
            $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

            foreach ($rows as $row) {
                $pages[] = $this->mapTyreRowToPage($row);
            }

            $this->log('Retrieved ' . count($pages) . ' tyres for sitemap inclusion');
        } catch (Exception $e) {
            $this->log('ERROR retrieving tyres: ' . $e->getMessage());
        }

        return $pages;
    }
    
    /**
     * Build static pages metadata
     */
    private function getStaticPages() {
        $staticPages = [
            ['path' => '/ro/', 'file' => 'content/site/page/home.php', 'category' => 'core'],
            ['path' => '/ro/cars', 'file' => 'content/site/page/cars.php', 'category' => 'core'],
            ['path' => '/ro/tyres', 'file' => 'content/site/page/tyres.php', 'category' => 'core'],
            ['path' => '/ro/services', 'file' => 'content/site/page/services.php', 'category' => 'core'],
            ['path' => '/ro/contacts', 'file' => 'content/site/page/contacts.php', 'category' => 'core'],
            ['path' => '/ro/about', 'file' => 'content/site/page/about.php', 'category' => 'core'],
            ['path' => '/ro/credit', 'file' => 'content/site/page/credit.php', 'category' => 'core'],
            ['path' => '/ro/tradein', 'file' => 'content/site/page/new_pages/tradein/tradein.php', 'category' => 'core'],
            ['path' => '/ro/privacy', 'file' => 'content/site/page/privacy.php', 'category' => 'legal'],
            ['path' => '/ro/terms', 'file' => 'content/site/page/terms.php', 'category' => 'legal'],
            ['path' => '/ro/warranty', 'file' => 'content/site/page/warranty.php', 'category' => 'support']
        ];

        $pages = [];
        foreach ($staticPages as $page) {
            $lastmod = $this->getFileLastmod($page['file']);
            if (!$lastmod) {
                $lastmod = new DateTimeImmutable('now');
            }

            $pages[] = [
                'type' => 'static',
                'url' => $page['path'],
                'created_at' => $lastmod,
                'lastmod' => $lastmod,
                'status' => 'active',
                'page_type' => $page['category'],
                'is_archived' => false,
                'is_deleted' => false,
                'translations' => $this->languages
            ];
        }

        return $pages;
    }
    
    
    /**
     * Fetch cars from modern table structure
     */
    private function fetchCarsFromModernTable() {
        $table = 'cars';
        $columns = $this->getTableColumns($table);
        if (empty($columns)) {
            return [];
        }

        $preferred = [
            'slug', 'status', 'is_deleted', 'deleted', 'deleted_at', 'is_archived', 'archived', 'archived_at',
            'availability', 'available', 'visibility', 'created_at', 'created', 'date', 'updated_at', 'updated',
            'modified_at', 'last_update', 'sold_at', 'sold_date', 'sale_date', 'sold_time', 'sold_timestamp',
            'sold_on', 'status_changed_at'
        ];

        $selectColumns = array_unique(array_merge(['id'], array_intersect($preferred, $columns)));
        $columnList = implode(', ', array_map(fn($col) => "`$col`", $selectColumns));

        $sql = "SELECT {$columnList} FROM `{$table}`";
        $conditions = [];

        if (in_array('is_deleted', $columns, true)) {
            $conditions[] = '`is_deleted` = 0';
        }
        if (in_array('deleted', $columns, true)) {
            $conditions[] = '`deleted` = 0';
        }
        if (in_array('deleted_at', $columns, true)) {
            $conditions[] = "(`deleted_at` IS NULL OR `deleted_at` = '0000-00-00 00:00:00')";
        }
        if (in_array('is_archived', $columns, true)) {
            $conditions[] = '`is_archived` = 0';
        }
        if (in_array('archived', $columns, true)) {
            $conditions[] = '`archived` = 0';
        }
        if (in_array('archived_at', $columns, true)) {
            $conditions[] = "(`archived_at` IS NULL OR `archived_at` = '0000-00-00 00:00:00')";
        }
        if (in_array('visibility', $columns, true)) {
            $conditions[] = "(`visibility` IS NULL OR `visibility` IN ('public', 'visible', ''))";
        }
        if (in_array('status', $columns, true)) {
            $conditions[] = "`status` IN ('active', 'sold', 'available', 'not_available', 'out_of_stock')";
        }

        if (!empty($conditions)) {
            $sql .= ' WHERE ' . implode(' AND ', $conditions);
        }

        $stmt = $this->db->query($sql);
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

        $pages = [];
        foreach ($rows as $row) {
            $pages[] = $this->mapCarRowToPage($row);
        }

        return $pages;
    }

    /**
     * Fetch cars from legacy catalog structure
     */
    private function fetchCarsFromLegacyCatalog() {
        $table = $this->prefx . '_car_ctlg';
        $columns = $this->getTableColumns($table);
        if (empty($columns)) {
            return [];
        }

        $preferred = [
            'n_a', 'act', 'vis', 'it', 'created_at', 'created', 'date', 'updated_at', 'updated', 'upd',
            'modified_at', 'last_update', 'time_shift', 'sold_at', 'sold_date', 'sale_date', 'sold_time',
            'sold_timestamp', 'sold_on', 'n_a_date', 'n_a_time', 'n_a_updated', 'n_a_updated_at', 'deleted'
        ];

        $selectColumns = array_unique(array_merge(['id'], array_intersect($preferred, $columns)));
        $columnList = implode(', ', array_map(fn($col) => "`$col`", $selectColumns));

        $sql = "SELECT {$columnList} FROM `{$table}` WHERE 1=1";
        if (in_array('act', $columns, true)) {
            $sql .= " AND `act` = 1";
        }
        if (in_array('vis', $columns, true)) {
            $sql .= " AND `vis` = 1";
        }
        if (in_array('it', $columns, true)) {
            $sql .= " AND (`it` IS NULL OR `it` != 'it_arh')";
        }
        if (in_array('deleted', $columns, true)) {
            $sql .= " AND (`deleted` = 0 OR `deleted` IS NULL)";
        }

        $stmt = $this->db->query($sql);
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

        $pages = [];
        foreach ($rows as $row) {
            $pages[] = $this->mapCarRowToPage($row, true);
        }

        return $pages;
    }

    /**
     * Map car row to sitemap page definition
     */
    private function mapCarRowToPage(array $row, $legacy = false) {
        $id = (int)$row['id'];

        $status = 'active';
        if (isset($row['status'])) {
            $rawStatus = strtolower(trim((string)$row['status']));
            if (in_array($rawStatus, ['sold', 'not_available', 'out_of_stock', 'unavailable'], true)) {
                $status = 'sold';
            }
        } elseif (isset($row['n_a'])) {
            $status = ((int)$row['n_a'] === 1) ? 'sold' : 'active';
        } elseif (isset($row['available'])) {
            $status = ((int)$row['available'] === 1) ? 'active' : 'sold';
        }

        $soldAt = $this->extractDateTime($row, [
            'sold_at', 'sold_date', 'sale_date', 'sold_time', 'sold_timestamp', 'sold_on',
            'status_changed_at', 'n_a_date', 'n_a_time', 'n_a_updated', 'n_a_updated_at'
        ]);
        if ($status !== 'sold') {
            $soldAt = null;
        }

        $createdAt = $this->extractDateTime($row, ['created_at', 'created', 'date', 'added_at', 'd']);
        if (!$createdAt) {
            $createdAt = $this->extractDateTime($row, ['updated_at', 'updated', 'upd', 'modified_at', 'last_update', 'time_shift']);
        }
        if (!$createdAt) {
            $createdAt = new DateTimeImmutable('now');
        }

        $updatedAt = $this->extractDateTime($row, ['updated_at', 'updated', 'upd', 'modified_at', 'last_update', 'time_shift']);
        if (!$updatedAt) {
            $updatedAt = $soldAt ?: $createdAt;
        }

        $isDeleted = false;
        if (isset($row['is_deleted'])) {
            $isDeleted = (bool)$row['is_deleted'];
        } elseif (isset($row['deleted'])) {
            $isDeleted = (bool)$row['deleted'];
        } elseif (isset($row['act']) && (int)$row['act'] !== 1) {
            $isDeleted = true;
        }

        $isArchived = false;
        if (isset($row['is_archived'])) {
            $isArchived = (bool)$row['is_archived'];
        } elseif (isset($row['archived'])) {
            $isArchived = (bool)$row['archived'];
        } elseif (isset($row['it']) && $row['it'] === 'it_arh') {
            $isArchived = true;
        }

        return [
            'type' => 'car',
            'id' => $id,
            'url' => '/ro/cars/' . $id,
            'created_at' => $createdAt,
            'lastmod' => $updatedAt,
            'status' => $status,
            'sold_at' => $soldAt,
            'is_archived' => $isArchived,
            'is_deleted' => $isDeleted,
            'translations' => $this->getItemTranslations('cars', $id)
        ];
    }

    /**
     * Map tyre row to sitemap page definition
     */
    private function mapTyreRowToPage(array $row) {
        $id = (int)$row['id'];

        $status = 'active';
        if (isset($row['n_a']) && (int)$row['n_a'] === 1) {
            $status = 'out_of_stock';
        }

        $createdAt = $this->extractDateTime($row, ['created_at', 'created', 'date']);
        if (!$createdAt) {
            $createdAt = new DateTimeImmutable('now');
        }

        $updatedAt = $this->extractDateTime($row, ['updated_at', 'updated', 'upd', 'modified_at', 'last_update']);
        if (!$updatedAt) {
            $updatedAt = $createdAt;
        }

        return [
            'type' => 'tire',
            'id' => $id,
            'url' => '/ro/tyres/' . $id,
            'created_at' => $createdAt,
            'lastmod' => $updatedAt,
            'status' => $status,
            'sold_at' => null,
            'is_archived' => false,
            'is_deleted' => false,
            'translations' => $this->getItemTranslations('tyres', $id)
        ];
    }
    
    /**
     * Filter pages according to inclusion rules
     */
    private function filterPages($pages) {
        $filtered = [];
        
        foreach ($pages as $page) {
            if (!$this->shouldIncludePage($page)) {
                continue;
            }

            if (!$this->isCanonicalUrl($page['url'])) {
                continue;
            }

            $filtered[] = $page;
        }

        return $this->removeDuplicateUrls($filtered);
    }
    
    /**
     * Process pages: calculate priority, changefreq, format data
     */
    private function processPages($pages) {
        $processed = [];
        
        $seenLoc = [];

        foreach ($pages as $page) {
            $loc = $this->baseUrl . $page['url'];
            if (isset($seenLoc[$loc])) {
                continue;
            }
            $seenLoc[$loc] = true;

            $lastmodDate = null;
            if (isset($page['lastmod']) && $page['lastmod'] instanceof DateTimeInterface) {
                $lastmodDate = $page['lastmod'];
            } else {
                $lastmodDate = $this->parseDateValue($page['lastmod'] ?? null);
            }
            if (!$lastmodDate) {
                $lastmodDate = new DateTimeImmutable('now');
            }

            $priorityValue = $this->calculatePriority($page);
            $priorityValue = max(0.1, min(1.0, $priorityValue));

            $processed[] = [
                'loc' => $loc,
                'lastmod' => $lastmodDate->format('Y-m-d'),
                'changefreq' => $this->getChangeFreq($page),
                'priority' => number_format($priorityValue, 1, '.', ''),
                'hreflang' => $this->getHrefLangLinks($page)
            ];
        }

        return $processed;
    }
    
    /**
     * Calculate priority based on page type and rules
     */
    private function calculatePriority($page) {
        switch ($page['type']) {
            case 'car':
                if (!empty($page['status']) && $page['status'] === 'sold') {
                    return 0.2;
                }

                $createdAt = $page['created_at'] ?? null;
                if (!($createdAt instanceof DateTimeInterface)) {
                    $createdAt = $this->parseDateValue($createdAt);
                }
                if (!$createdAt instanceof DateTimeInterface) {
                    $createdAt = new DateTimeImmutable('now');
                }

                $daysOld = (new DateTimeImmutable('now'))->diff($createdAt)->days;

                if ($daysOld <= 30) {
                    return 1.0;
                }
                if ($daysOld <= 60) {
                    return 0.9;
                }
                if ($daysOld <= 120) {
                    return 0.7;
                }
                return 0.6;

            case 'tire':
                return (!empty($page['status']) && $page['status'] === 'out_of_stock') ? 0.2 : 0.3;

            case 'static':
                $category = $page['page_type'] ?? 'core';
                if ($category === 'legal') {
                    return 0.3;
                }
                if ($category === 'support') {
                    return 0.4;
                }
                return 0.5;

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
        $translations = $page['translations'] ?? $this->languages;
        if (!is_array($translations)) {
            $translations = $this->languages;
        }

        $translations = $this->normalizeLanguages($translations);
        $links = [];

        foreach ($translations as $lang) {
            $links[] = [
                'hreflang' => $lang,
                'href' => $this->baseUrl . $this->localizeUrl($page['url'], $lang)
            ];
        }

        if (!empty($translations)) {
            $links[] = [
                'hreflang' => 'x-default',
                'href' => $this->baseUrl . $this->localizeUrl($page['url'], $translations[0])
            ];
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
                $hrefLang = $xml->createElementNS('http://www.w3.org/1999/xhtml', 'xhtml:link');
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
        
        $globalUrls = [];

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

            $urlsInFile = $this->extractUrlsFromFile($subPath);
            foreach ($urlsInFile as $urlValue) {
                if (isset($globalUrls[$urlValue])) {
                    $errors[] = "Duplicate URL found across sitemap files: $urlValue (in {$globalUrls[$urlValue]} and $subFile)";
                } else {
                    $globalUrls[$urlValue] = $subFile;
                }
            }

            // Check URL count limit
            $urlCount = count($urlsInFile);
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
            if ($lang === 'x-default') {
                continue;
            }

            if (!in_array($lang, $this->languages, true)) {
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
    private function extractUrlsFromFile($filePath) {
        $urls = [];
        $previousState = libxml_use_internal_errors(true);

        try {
            $xml = new DOMDocument();
            if ($xml->load($filePath)) {
                $urlNodes = $xml->getElementsByTagName('url');
                foreach ($urlNodes as $urlNode) {
                    $loc = $urlNode->getElementsByTagName('loc');
                    if ($loc->length > 0) {
                        $urls[] = trim($loc->item(0)->textContent);
                    }
                }
            }
        } finally {
            libxml_clear_errors();
            libxml_use_internal_errors($previousState);
        }

        return $urls;
    }

    private function countUrlsInFile($filePath) {
        return count($this->extractUrlsFromFile($filePath));
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
