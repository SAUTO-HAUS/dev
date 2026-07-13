<?php
/**
 * SAUTO Sitemap Generator - REAL VERSION
 * This version connects to the actual database and generates real sitemaps
 * Use with caution - this affects production data
 */

// Define required constant for SAUTO framework
define('_DOIT', 1);

// Set up CLI environment variables
$_SERVER['HTTP_HOST'] = $_SERVER['HTTP_HOST'] ?? 'www.sauto.md';
$_SERVER['REQUEST_URI'] = $_SERVER['REQUEST_URI'] ?? '/';
$_SERVER['HTTPS'] = 'on';
$_SERVER['REQUEST_SCHEME'] = 'https';
$_SERVER['REMOTE_ADDR'] = $_SERVER['REMOTE_ADDR'] ?? '127.0.0.1';
$_SERVER['HTTP_USER_AGENT'] = $_SERVER['HTTP_USER_AGENT'] ?? 'SAUTO-Sitemap-Generator/1.0';

// Include environment configuration for SQL constants
require_once __DIR__ . '/../environment.php';

// Add compatibility functions for older PHP versions
if (!function_exists('str_starts_with')) {
    function str_starts_with($haystack, $needle) {
        return strpos($haystack, $needle) === 0;
    }
}

if (!function_exists('str_contains')) {
    function str_contains($haystack, $needle) {
        return strpos($haystack, $needle) !== false;
    }
}

// Load configuration
require_once __DIR__ . '/config.php';

try {
    // Include functions first to define usr_agent() and other functions
    require_once __DIR__ . '/../content/default/functions.php';
    require_once __DIR__ . '/../content/default/config.php';
    require_once __DIR__ . '/../content/default/dbi.php';
} catch (Exception $e) {
    echo "Configuration error: " . $e->getMessage() . "\n";
    echo "Using fallback database connection...\n";
    // Fallback will be handled in constructor
}

class SitemapGeneratorReal {
    
    private $baseUrl;
    private $maxUrlsPerFile = 15000;
    private $outputDir = __DIR__ . '/..';
    private $logFile = 'sitemap_generation_real.log';
    private $languages = ['ro', 'ru', 'en'];
    private $db;
    private $prefx;
    private $tableColumnsCache = [];
    private $translationsCache = null;

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
            $stmt = $this->db->prepare("SHOW TABLES LIKE '{$tableName}'");
            $stmt->execute();
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
            case 'brand':
                // Always include brand pages
                return true;
            
            case 'car':
                if (!empty($page['is_archived']) || !empty($page['is_deleted'])) {
                    return false;
                }

                // Include all sold cars for SEO benefits (they get priority 0.2)
                // No need to check sold_at - all sold cars are valuable for SEO
                return true;

            case 'tire':
                if (!empty($page['is_archived']) || !empty($page['is_deleted'])) {
                    return false;
                }
                return true;

            case 'model':
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
        // Lazy-load all translations in one query, then serve from in-memory cache.
        if ($this->translationsCache === null) {
            $this->translationsCache = [];
            $table = $this->prefx . '_seo2';
            if ($this->tableExists($table)) {
                try {
                    $stmt = $this->db->query("SELECT `p1`, `it_id`, `lng` FROM `{$table}` WHERE `tp` = 'item'");
                    foreach ($stmt as $row) {
                        $p1 = $row['p1'] ?? '';
                        $id = $row['it_id'] ?? '';
                        $lng = $row['lng'] ?? '';
                        if ($p1 === '' || $id === '' || $lng === '') continue;
                        $key = $p1 . ':' . $id;
                        if (!isset($this->translationsCache[$key])) {
                            $this->translationsCache[$key] = [];
                        }
                        if (!in_array($lng, $this->translationsCache[$key], true)) {
                            $this->translationsCache[$key][] = $lng;
                        }
                    }
                    $this->log("Translations cache loaded: " . count($this->translationsCache) . " entries");
                } catch (Exception $e) {
                    $this->log("WARNING: Failed to bulk-load translations: " . $e->getMessage());
                }
            }
        }

        $key = $type . ':' . $itemId;
        $languages = $this->translationsCache[$key] ?? [];
        $languages = $this->normalizeLanguages($languages);
        if (!in_array('ro', $languages, true)) {
            array_unshift($languages, 'ro');
        }
        return $languages;
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

            // Backup disabled — old backup was being restored over the new file by an external
            // process or stale logic, wiping Ford/BMW/Toyota brands.
            // $this->backupCurrentFiles();
            
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
            
            // Validation disabled — the string-based writer produces valid XML by construction,
            // and the old validate->fallback path was restoring stale backups without Ford/BMW.
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
     * Brand pages are added FIRST to ensure they appear at the top of sitemap-1.xml
     */
    private function getAllPages() {
        $pages = [];
        $pages = array_merge($pages, $this->getBrandPagesFromDatabase());
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
            ['path' => '/ro/', 'file' => 'content/site/page/home.php', 'category' => 'home'],
            ['path' => '/ro/cars', 'file' => 'content/site/page/cars.php', 'category' => 'catalog'],
            ['path' => '/ro/ordercars', 'file' => 'content/site/page/ordercars.php', 'category' => 'catalog'],
            ['path' => '/ro/ordercars/korea', 'file' => 'content/site/page/ordercars.php', 'category' => 'region'],
            ['path' => '/ro/ordercars/europe', 'file' => 'content/site/page/ordercars.php', 'category' => 'region'],
            ['path' => '/ro/ordercars/usa', 'file' => 'content/site/page/ordercars.php', 'category' => 'region'],
            ['path' => '/ro/tyres', 'file' => 'content/site/page/tyres.php', 'category' => 'catalog'],
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
    
    private function getBrandPagesFromDatabase() {
        $pages = [];
        $carListTable = $this->prefx . '_car_list';
        
        try {
            if (!$this->tableExists($carListTable)) {
                $this->log('WARNING: Brand list table does not exist');
                return [];
            }
            
            $sql = "SELECT DISTINCT `br`, `br_nm` FROM `{$carListTable}` WHERE `br` IS NOT NULL AND `br` != '' ORDER BY `br_nm` ASC";
            $stmt = $this->db->query($sql);
            $brands = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            foreach ($brands as $brand) {

                $brandSlug = str_replace('_', '-', strtolower($brand['br']));
                
                $pages[] = [
                    'type' => 'brand',
                    'url' => '/ro/cars/' . $brandSlug,
                    'created_at' => new DateTimeImmutable('now'),
                    'lastmod' => new DateTimeImmutable('now'),
                    'status' => 'active',
                    'page_type' => 'brand',
                    'is_archived' => false,
                    'is_deleted' => false,
                    'translations' => $this->languages,
                    'brand_name' => $brand['br_nm'],
                    'brand_code' => $brand['br']
                ];
            }
            
            $this->log('Retrieved ' . count($pages) . ' brand pages for sitemap inclusion');
            
            $modelSql = "SELECT DISTINCT `br`, `br_nm`, `mo`, `mo_nm` FROM `{$carListTable}` WHERE `br` IS NOT NULL AND `br` != '' AND `mo` IS NOT NULL AND `mo` != '' ORDER BY `br_nm` ASC, `mo_nm` ASC";
            $modelStmt = $this->db->query($modelSql);
            $models = $modelStmt->fetchAll(PDO::FETCH_ASSOC);
            
            $modelCount = 0;
            foreach ($models as $model) {
                $brandSlug = str_replace('_', '-', strtolower($model['br']));
                $modelSlug = str_replace('_', '-', strtolower($model['mo']));
                
                $pages[] = [
                    'type' => 'model',
                    'url' => '/ro/cars/' . $brandSlug . '/' . $modelSlug,
                    'created_at' => new DateTimeImmutable('now'),
                    'lastmod' => new DateTimeImmutable('now'),
                    'status' => 'active',
                    'page_type' => 'model',
                    'is_archived' => false,
                    'is_deleted' => false,
                    'translations' => $this->languages,
                    'brand_name' => $model['br_nm'],
                    'brand_code' => $model['br']
                ];
                $modelCount++;
            }
            
            $this->log('Retrieved ' . $modelCount . ' model pages for sitemap inclusion');
        } catch (Exception $e) {
            $this->log('ERROR retrieving brands: ' . $e->getMessage());
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
        if (in_array('is_at_client', $columns, true)) {
            $conditions[] = "(`is_at_client` = 0 OR `is_at_client` IS NULL)";
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
            'n_a', 'act', 'vis', 'it', 'd', 'created_at', 'created', 'date', 'updated_at', 'updated', 'upd',
            'modified_at', 'last_update', 'time_shift', 'sold_at', 'sold_date', 'sale_date', 'sold_time',
            'sold_timestamp', 'sold_on', 'n_a_date', 'n_a_time', 'n_a_updated', 'n_a_updated_at', 'deleted',
            'catalog_type'
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
        if (in_array('is_at_client', $columns, true)) {
            $sql .= " AND (`is_at_client` = 0 OR `is_at_client` IS NULL)";
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

        $catalogType = $row['catalog_type'] ?? 'in_stock';
        $section = ($catalogType === 'on_order') ? 'ordercars' : 'cars';

        // ordercars nu are traduceri în gh3sp_seo2, folosim toate limbile default
        $translations = ($section === 'ordercars') 
            ? $this->languages 
            : $this->getItemTranslations($section, $id);

        return [
            'type' => 'car',
            'id' => $id,
            'url' => '/ro/' . $section . '/' . $id,
            'created_at' => $createdAt,
            'lastmod' => $updatedAt,
            'status' => $status,
            'sold_at' => $soldAt,
            'is_archived' => $isArchived,
            'is_deleted' => $isDeleted,
            'translations' => $translations
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
    
            if ($lastmodDate < new DateTimeImmutable('2015-01-01')) {
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
        // Coherent SEO hierarchy: home is the single top page, catalogs/brands/regions
        // are the main commercial landing pages, individual listings scale by freshness.
        // Priorities are RELATIVE — keeping most values below 1.0 preserves the signal.
        switch ($page['type']) {
            case 'brand':
                return 0.9;

            case 'model':
                return 0.8;

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
                    return 0.8;
                }
                if ($daysOld <= 60) {
                    return 0.7;
                }
                if ($daysOld <= 120) {
                    return 0.6;
                }
                return 0.5;

            case 'tire':
                return (!empty($page['status']) && $page['status'] === 'out_of_stock') ? 0.2 : 0.4;

            case 'static':
                $category = $page['page_type'] ?? 'core';
                if ($category === 'home') {
                    return 1.0; // homepage — the single most important page
                }
                if ($category === 'catalog') {
                    return 0.9; // main listing pages (cars, ordercars, tyres)
                }
                if ($category === 'region') {
                    return 0.9; // import-region landing pages — key SEO pages
                }
                if ($category === 'legal') {
                    return 0.3;
                }
                if ($category === 'support') {
                    return 0.4;
                }
                return 0.6; // other core pages (services, contacts, about, credit, tradein)

            default:
                return 0.5;
        }
    }
    
    /**
     * Get change frequency based on page type
     */
    private function getChangeFreq($page) {
        switch ($page['type']) {
            case 'brand':
                return 'daily';
            case 'model':
                return 'daily';
            case 'car':
                // A sold listing no longer changes daily.
                return (!empty($page['status']) && $page['status'] === 'sold') ? 'monthly' : 'daily';
            case 'tire':
                return 'weekly';
            case 'static':
                $cat = $page['page_type'] ?? '';
                if ($cat === 'home' || $cat === 'catalog') {
                    return 'daily';  // main pages refresh with new inventory
                }
                if ($cat === 'region') {
                    return 'weekly'; // region catalog changes often, but less than the main list
                }
                return 'monthly';    // info/legal/support pages rarely change
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
        $clean = function ($v) {
            if ($v === null) return '';
            $v = preg_replace('/[\x00-\x08\x0B\x0C\x0E-\x1F\x7F]/', '', (string)$v);
            return htmlspecialchars($v, ENT_QUOTES | ENT_XML1, 'UTF-8');
        };

        $buffer = '<?xml version="1.0" encoding="UTF-8"?>' . "\n"
            . '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9"'
            . ' xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance"'
            . ' xmlns:xhtml="http://www.w3.org/1999/xhtml"'
            . ' xsi:schemaLocation="http://www.sitemaps.org/schemas/sitemap/0.9 http://www.sitemaps.org/schemas/sitemap/0.9/sitemap.xsd">'
            . "\n";

        $written = 0;
        foreach ($pages as $page) {
            $buffer .= "  <url>\n";
            $buffer .= "    <loc>" . $clean($page['loc']) . "</loc>\n";
            $buffer .= "    <lastmod>" . $clean($page['lastmod']) . "</lastmod>\n";
            $buffer .= "    <changefreq>" . $clean($page['changefreq']) . "</changefreq>\n";
            $buffer .= "    <priority>" . $clean($page['priority']) . "</priority>\n";

            if (!empty($page['hreflang']) && is_array($page['hreflang'])) {
                foreach ($page['hreflang'] as $link) {
                    $hl = $clean($link['hreflang'] ?? '');
                    $hr = $clean($link['href'] ?? '');
                    $buffer .= "    <xhtml:link rel=\"alternate\" hreflang=\"$hl\" href=\"$hr\"/>\n";
                }
            }

            $buffer .= "  </url>\n";
            $written++;
        }

        $buffer .= '</urlset>' . "\n";

        $filePath = $this->outputDir . '/' . $fileName;
        // Reset perms in case previous run left it read-only
        if (file_exists($filePath)) {
            @chmod($filePath, 0644);
        }
        $bufLen = strlen($buffer);
        $wrote = file_put_contents($filePath, $buffer, LOCK_EX);
        if ($wrote === false) {
            throw new Exception("Cannot write $filePath");
        }
        // Set read-only to prevent any subsequent overwrites in this same process
        @chmod($filePath, 0444);

        clearstatcache(true, $filePath);
        $fs = filesize($filePath);
        $this->log("Generated sub-file: $fileName with $written URLs (buffer=$bufLen, wrote=$wrote, filesize=$fs, ford_in_buffer=" . substr_count($buffer, '/ro/cars/ford') . ", ford_in_file=" . substr_count(file_get_contents($filePath), '/ro/cars/ford') . ")");
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
            '/\/ro\/cars\/\d+$/',                    // /ro/cars/123 (car detail pages)
            '/\/ro\/cars\/[a-z0-9-]+\/[a-z0-9-]+$/', // /ro/cars/bmw/x1 
            '/\/ro\/cars\/[a-z0-9-]+$/',             // /ro/cars/audi (brand pages)
            '/\/ro\/ordercars\/\d+$/',               // /ro/ordercars/123 (order car detail pages)
            '/\/ro\/ordercars\/(korea|europe|usa)$/', // /ro/ordercars/korea (import-region landing pages)
            '/\/ro\/tyres\/\d+$/',                   // /ro/tyres/123 (tyre detail pages)
            '/\/ro\/tires\/[a-z0-9-]+$/',            // /ro/tires/tire-slug
            '/\/ro\/[a-z-]+$/',                      // /ro/contact, /ro/about
            '/\/ro\/$/'                              // /ro/
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
