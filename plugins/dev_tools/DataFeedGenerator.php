<?php
/**
 * Data Feed Generator Base Class
 * 
 * Provides common functionality for generating Facebook/Google Shopping XML feeds
 * with proper logging and error handling.
 * 
 * @author SAUTO Development Team
 * @created 2025-11-21
 */

use LaLit\Array2XML;

class DataFeedGenerator {
    
    protected $db;
    protected $prefx;
    protected $lng;
    protected $feedType;
    protected $outputFile;
    protected $startTime;
    protected $previousCarIds = [];
    protected $currentCarIds = [];
    
    /**
     * Constructor
     * 
     * @param PDO $db Database connection
     * @param string $prefx Database table prefix
     * @param array $lng Language array
     * @param string $feedType Feed type: 'main', 'pruncul', 'orders'
     * @param string $outputFile Output XML file path
     */
    public function __construct($db, $prefx, $lng, $feedType, $outputFile) {
        $this->db = $db;
        $this->prefx = $prefx;
        $this->lng = $lng;
        $this->feedType = $feedType;
        $this->outputFile = $outputFile;
        $this->startTime = microtime(true);
    }
    
    /**
     * Get SQL WHERE clause for specific feed type
     * 
     * @return string SQL WHERE clause
     */
    protected function getWhereClause() {
        $baseWhere = "WHERE `n_a`=0 AND `vis`=1 AND `act`=1";
        
        switch ($this->feedType) {
            case 'main':
                // Main catalog: in-stock, main location only, no orders
                return $baseWhere . " AND `catalog_type`='in_stock' AND `loc`='1'";
                
            case 'pruncul':
                // Pruncul branch: in-stock, Pruncul location only, no orders
                return $baseWhere . " AND `catalog_type`='in_stock' AND `loc`='2'";
                
            case 'orders':
                // Orders: on_order type, active timer only, all locations
                return $baseWhere . " AND `catalog_type`='on_order' AND `offer_timer_end` > UNIX_TIMESTAMP()";
                
            default:
                throw new Exception("Unknown feed type: {$this->feedType}");
        }
    }
    
    /**
     * Load previous car IDs from existing feed for comparison
     */
    protected function loadPreviousCarIds() {
        if (file_exists($this->outputFile)) {
            $xml = @simplexml_load_file($this->outputFile);
            if ($xml && isset($xml->channel->item)) {
                foreach ($xml->channel->item as $item) {
                    if (isset($item->children('g', true)->id)) {
                        $carId = (string)$item->children('g', true)->id;
                        // Remove 'c' prefix to get numeric ID
                        $carId = str_replace('c', '', $carId);
                        $this->previousCarIds[] = $carId;
                    }
                }
            }
        }
    }
    
    /**
     * Generate feed data array
     * 
     * @return array Feed data ready for XML conversion
     */
    public function generateFeedData() {
        $sql = 'SELECT * FROM ' . $this->prefx . '_car_ctlg ' . $this->getWhereClause();
        $pdo = $this->db->prepare($sql);
        $pdo->execute();
        
        $i = 0;
        $ar = [];
        
        foreach ($pdo as $r) {
            // Track current car IDs for change detection
            $this->currentCarIds[] = $r['id'];
            
            // Get car images
            $pdo_img = $this->db->prepare('SELECT `name`, `main` FROM ' . $this->prefx . '_car_pht WHERE `it_id`=:it_id LIMIT 20');
            $pdo_img->execute(array('it_id' => $r['id']));
            $imgs = [];
            
            foreach ($pdo_img as $im) {
                $imageUrl = 'https://www.sauto.md/media/images/upload/car/' . $r['p_path'] . '/' . $r['id'] . '/high/' . $im['name'] . '.jpg';
                if ($im['main'] === 1) {
                    $imgs['m'][] = ['@value' => $imageUrl];
                } else {
                    $imgs['x'][] = ['@value' => $imageUrl];
                }
            }
            
            // Build feed item
            $ar[$i]['g:id'] = 'c' . $r['id'];
            $ar[$i]['g:title'] = $r['yr'] . ' ' . $r['br_nm'] . ' ' . $r['mo_nm'] . ' (id-' . $r['id'] . ')';
            $ar[$i]['g:description'] = $r['yr'] . ' ' . $this->lng['l']['car']['clr'][$r['clr']] . ' ' . $r['br_nm'] . ' ' . $r['mo_nm'] . 
                ' [' . $this->lng['l']['car']['fl'][$r['fl']] . ' ' . $this->lng['l']['car']['tra'][$r['tra']] . ' ' . 
                $this->lng['l']['car']['wd'][$r['wd']] . '] for ' . $r['prc'] . ' ' . $r['cur'];
            $ar[$i]['g:link'] = 'https://www.sauto.md/ro/cars/' . $r['id'];
            $ar[$i]['g:brand'] = $r['br_nm'];
            $ar[$i]['g:image_link'] = $imgs['m'];
            
            if (isset($imgs['x'][0])) {
                $ar[$i]['g:additional_image_link'] = $imgs['x'];
            }
            
            $ar[$i]['g:product_type'] = 'Cars > ' . $r['br_nm'] . ' > ' . $r['mo_nm'];
            $ar[$i]['g:condition'] = 'used';
            $ar[$i]['g:availability'] = 'in_stock';
            $ar[$i]['g:color'] = $this->lng['l']['car']['clr'][$r['clr']];
            $ar[$i]['g:price'] = $r['prc'] . ' ' . $r['cur'];
            
            $ar[$i]['g:product_detail'] = [
                ['g:section_name' => 'Info', 'g:attribute_name' => 'Year', 'g:attribute_value' => $r['yr']],
                ['g:section_name' => 'Info', 'g:attribute_name' => 'Body style', 'g:attribute_value' => $this->lng['l']['car']['bt'][$r['bt']]],
                ['g:section_name' => 'Info', 'g:attribute_name' => 'Fuel', 'g:attribute_value' => $this->lng['l']['car']['fl'][$r['fl']]],
                ['g:section_name' => 'Info', 'g:attribute_name' => 'Transmission', 'g:attribute_value' => $this->lng['l']['car']['tra'][$r['tra']]],
                ['g:section_name' => 'Info', 'g:attribute_name' => 'Drivetrain', 'g:attribute_value' => $this->lng['l']['car']['wd'][$r['wd']]],
                ['g:section_name' => 'Info', 'g:attribute_name' => 'Mileage', 'g:attribute_value' => $r['mlg'] . ' ' . $r['unit']]
            ];
            
            $ar[$i]['g:google_product_category'] = '916';
            $ar[$i]['g:vehicle_fulfillment'] = 'in_store';
            $ar[$i]['g:model'] = $r['mo_nm'];
            $ar[$i]['g:year'] = $r['yr'];
            $ar[$i]['g:mileage'] = $r['mlg'] . ' ' . (strtoupper($r['unit']));
            $ar[$i]['g:body_style'] = $this->lng['l']['car']['bt'][$r['bt']];
            $ar[$i]['g:engine'] = $this->lng['l']['car']['fl'][$r['fl']];
            
            $i++;
        }
        
        return $ar;
    }
    
    /**
     * Generate and save XML feed
     * 
     * @return array Statistics about generation (added, removed, total)
     */
    public function generate() {
        // Load previous car IDs for comparison
        $this->loadPreviousCarIds();
        
        // Generate feed data
        $feedData = $this->generateFeedData();
        
        // Build RSS structure
        $rss = [
            '@attributes' => [
                'xmlns:g' => 'http://base.google.com/ns/1.0',
                'version' => '2.0'
            ]
        ];
        $rss['channel'] = [
            'title' => 'Sauto, cars feed - ' . ucfirst($this->feedType),
            'link' => [
                '@attributes' => [
                    'rel' => 'self',
                    'href' => 'https://www.sauto.md/ro/cars'
                ]
            ]
        ];
        $rss['channel']['item'] = $feedData;
        
        // Convert to XML
        $xml = Array2XML::createXML('rss', $rss);
        
        // Save XML file
        $xml->save($this->outputFile);
        
        // Calculate statistics
        $added = count(array_diff($this->currentCarIds, $this->previousCarIds));
        $removed = count(array_diff($this->previousCarIds, $this->currentCarIds));
        $total = count($this->currentCarIds);
        $executionTime = round(microtime(true) - $this->startTime, 3);
        
        return [
            'added' => $added,
            'removed' => $removed,
            'total' => $total,
            'execution_time' => $executionTime
        ];
    }
    
    /**
     * Log generation results to database
     * 
     * @param array $stats Generation statistics
     * @param string $status Status: 'success' or 'error'
     * @param string|null $errorMessage Error message if failed
     */
    public function logToDatabase($stats, $status = 'success', $errorMessage = null) {
        $sql = "INSERT INTO {$this->prefx}_data_feed_log 
                (`feed_type`, `cars_added`, `cars_removed`, `total_cars`, `execution_time`, `status`, `error_message`)
                VALUES (:feed_type, :added, :removed, :total, :exec_time, :status, :error)";
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute([
            'feed_type' => $this->feedType,
            'added' => $stats['added'] ?? 0,
            'removed' => $stats['removed'] ?? 0,
            'total' => $stats['total'] ?? 0,
            'exec_time' => $stats['execution_time'] ?? 0,
            'status' => $status,
            'error' => $errorMessage
        ]);
    }
}
