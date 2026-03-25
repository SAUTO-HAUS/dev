<?php
namespace App\Services;

class FacetService
{
    private $db;
    private $prefx;
    private $cacheDir;
    private $cacheTTL = 60; 
    
    private $selectFilters = ['br', 'mo', 'gr', 'bt', 'fl', 'tra', 'wd', 'clr'];
    private $rangeFilters = ['yr', 'mlg', 'vol', 'prc', 'sts'];
    
    public function __construct($db, $prefx, $cacheDir = null)
    {
        $this->db = $db;
        $this->prefx = $prefx;
        $this->cacheDir = $cacheDir ?: dirname(__DIR__, 2) . '/cache';
    }
    
    /**
     * Get facets for all filters based on current selection
     * 
     * @param array 
     * @param string 
     * @return array
     */
    public function getFacets(array $currentFilters, string $catalogType = 'all'): array
    {
        $cacheKey = $this->getCacheKey($currentFilters, $catalogType);
        $cached = $this->getFromCache($cacheKey);
        
        if ($cached !== null) {
            return $cached;
        }
        
        $result = [
            'total' => 0,
            'facets' => [],
            'ranges' => [],
            'models_by_brand' => []
        ];
        
        $result['total'] = $this->getTotalCount($currentFilters, $catalogType);
        
        foreach ($this->selectFilters as $filter) {
            $result['facets'][$filter] = $this->getFilterFacet($filter, $currentFilters, $catalogType);
        }
        
        foreach ($this->rangeFilters as $filter) {
            $result['ranges'][$filter] = $this->getFilterRange($filter, $currentFilters, $catalogType);
        }
        
        $result['models_by_brand'] = $this->getModelsByBrand($currentFilters, $catalogType);
        
        $this->saveToCache($cacheKey, $result);
        
        return $result;
    }
    
    private function getTotalCount(array $filters, string $catalogType): int
    {
        $sql = $this->buildBaseQuery($catalogType);
        $params = [];
        
        $sql .= $this->buildFilterConditions($filters, $params);
        
        $countSql = "SELECT COUNT(*) as cnt FROM ({$sql}) as subq";
        
        try {
            $stmt = $this->db->prepare($countSql);
            $stmt->execute($params);
            $row = $stmt->fetch(\PDO::FETCH_ASSOC);
            return (int)($row['cnt'] ?? 0);
        } catch (\PDOException $e) {
            error_log("FacetService::getTotalCount error: " . $e->getMessage());
            return 0;
        }
    }
    
    private function getFilterFacet(string $filterName, array $currentFilters, string $catalogType): array
    {

        $filtersWithoutSelf = $currentFilters;
        unset($filtersWithoutSelf[$filterName]);
        
        if ($filterName === 'br') {
            unset($filtersWithoutSelf['mo']);
        }
        
        $sql = $this->buildBaseQuery($catalogType);
        $params = [];
        
        $sql .= $this->buildFilterConditions($filtersWithoutSelf, $params);
        
        $facetSql = "SELECT `{$filterName}` as val, COUNT(*) as cnt 
                     FROM ({$sql}) as subq 
                     WHERE `{$filterName}` IS NOT NULL AND `{$filterName}` != ''
                     GROUP BY `{$filterName}` 
                     ORDER BY cnt DESC, val ASC";
        
        try {
            $stmt = $this->db->prepare($facetSql);
            $stmt->execute($params);
            $results = $stmt->fetchAll(\PDO::FETCH_ASSOC);
            
            $facet = [];
            foreach ($results as $row) {
                $facet[] = [
                    'value' => $row['val'],
                    'count' => (int)$row['cnt']
                ];
            }
            return $facet;
        } catch (\PDOException $e) {
            error_log("FacetService::getFilterFacet error for {$filterName}: " . $e->getMessage());
            return [];
        }
    }
    
    private function getFilterRange(string $filterName, array $currentFilters, string $catalogType): array
    {

        $filtersWithoutSelf = $currentFilters;
        unset($filtersWithoutSelf[$filterName]);
        
        $sql = $this->buildBaseQuery($catalogType);
        $params = [];
        
        $sql .= $this->buildFilterConditions($filtersWithoutSelf, $params);
        
        $rangeSql = "SELECT MIN(`{$filterName}`) as min_val, MAX(`{$filterName}`) as max_val 
                     FROM ({$sql}) as subq 
                     WHERE `{$filterName}` IS NOT NULL AND `{$filterName}` > 0";
        
        try {
            $stmt = $this->db->prepare($rangeSql);
            $stmt->execute($params);
            $row = $stmt->fetch(\PDO::FETCH_ASSOC);
            
            return [
                'min' => $row['min_val'] !== null ? (int)$row['min_val'] : null,
                'max' => $row['max_val'] !== null ? (int)$row['max_val'] : null
            ];
        } catch (\PDOException $e) {
            error_log("FacetService::getFilterRange error for {$filterName}: " . $e->getMessage());
            return ['min' => null, 'max' => null];
        }
    }
    
    private function getModelsByBrand(array $currentFilters, string $catalogType): array
    {

        $filtersForModels = $currentFilters;
        unset($filtersForModels['br']);
        unset($filtersForModels['mo']);
        
        $sql = $this->buildBaseQuery($catalogType);
        $params = [];
        
        $sql .= $this->buildFilterConditions($filtersForModels, $params);
        
        $modelsSql = "SELECT `br`, `mo`, `br_nm`, `mo_nm`, COUNT(*) as cnt 
                      FROM ({$sql}) as subq 
                      WHERE `br` IS NOT NULL AND `br` != '' AND `mo` IS NOT NULL AND `mo` != ''
                      GROUP BY `br`, `mo`, `br_nm`, `mo_nm`
                      ORDER BY `br_nm` ASC, `mo_nm` ASC";
        
        try {
            $stmt = $this->db->prepare($modelsSql);
            $stmt->execute($params);
            $results = $stmt->fetchAll(\PDO::FETCH_ASSOC);
            
            $modelsByBrand = [];
            foreach ($results as $row) {
                $brand = $row['br'];
                if (!isset($modelsByBrand[$brand])) {
                    $modelsByBrand[$brand] = [
                        'brand_name' => $row['br_nm'],
                        'models' => []
                    ];
                }
                $modelsByBrand[$brand]['models'][] = [
                    'value' => $row['mo'],
                    'name' => $row['mo_nm'],
                    'count' => (int)$row['cnt']
                ];
            }
            return $modelsByBrand;
        } catch (\PDOException $e) {
            error_log("FacetService::getModelsByBrand error: " . $e->getMessage());
            return [];
        }
    }
    
    private function buildBaseQuery(string $catalogType): string
    {
        $sql = "SELECT * FROM {$this->prefx}_car_ctlg WHERE `vis`='1' AND `act`='1'";
        
        if ($catalogType === 'in_stock') {
            $sql .= " AND (catalog_type = 'in_stock' OR catalog_type IS NULL)";
        } elseif ($catalogType === 'on_order') {
            $sql .= " AND catalog_type = 'on_order'";
        }
        
        return $sql;
    }

    private function buildFilterConditions(array $filters, array &$params): string
    {
        $conditions = '';
        
        foreach ($filters as $key => $value) {
            if (empty($value) || $key === 'tg') {
                continue;
            }
            
            if (is_string($value)) {
                $value = explode('?', $value)[0];
            }
            
            if (in_array($key, $this->selectFilters)) {
                // For brand/model, normalize hyphen to underscore for database matching
                if ($key === 'br' || $key === 'mo') {
                    $normalizedValue = str_replace('-', '_', $value);
                    $values = [$normalizedValue];
                } else {
                    $values = is_array($value) ? $value : explode('-', $value);
                }
                $values = array_filter($values, function($v) { return $v !== '' && $v !== 'x'; });
                
                if (!empty($values)) {
                    if (count($values) === 1) {
                        $conditions .= " AND `{$key}` = :{$key}";
                        $params[$key] = reset($values);
                    } else {
                        $placeholders = [];
                        foreach ($values as $i => $v) {
                            $placeholder = "{$key}_{$i}";
                            $placeholders[] = ":{$placeholder}";
                            $params[$placeholder] = $v;
                        }
                        $conditions .= " AND `{$key}` IN (" . implode(',', $placeholders) . ")";
                    }
                }
            } elseif (in_array($key, $this->rangeFilters)) {
                $range = is_array($value) ? $value : explode('-', $value);
                
                if (count($range) >= 2) {
                    $minVal = $range[0];
                    $maxVal = $range[1];
                    
                    if ($minVal !== 'x' && $minVal !== '' && $maxVal !== 'x' && $maxVal !== '') {
                        $conditions .= " AND `{$key}` BETWEEN :{$key}_min AND :{$key}_max";
                        $params["{$key}_min"] = (int)min($minVal, $maxVal);
                        $params["{$key}_max"] = (int)max($minVal, $maxVal);
                    } elseif ($minVal !== 'x' && $minVal !== '') {
                        $conditions .= " AND `{$key}` >= :{$key}_min";
                        $params["{$key}_min"] = (int)$minVal;
                    } elseif ($maxVal !== 'x' && $maxVal !== '') {
                        $conditions .= " AND `{$key}` <= :{$key}_max";
                        $params["{$key}_max"] = (int)$maxVal;
                    }
                } elseif (!empty($range[0]) && $range[0] !== 'x') {
                    $conditions .= " AND `{$key}` = :{$key}";
                    $params[$key] = (int)$range[0];
                }
            }
        }
        
        return $conditions;
    }
    
    private function getCacheKey(array $filters, string $catalogType): string
    {
        ksort($filters);
        $filterString = json_encode($filters) . '_' . $catalogType;
        return 'facet_' . md5($filterString);
    }

    private function getFromCache(string $key): ?array
    {
        $cacheFile = $this->cacheDir . '/' . $key . '.cache';
        
        if (!file_exists($cacheFile)) {
            return null;
        }
        
        $mtime = filemtime($cacheFile);
        if (time() - $mtime > $this->cacheTTL) {
            @unlink($cacheFile);
            return null;
        }
        
        $content = @file_get_contents($cacheFile);
        if ($content === false) {
            return null;
        }
        
        $data = @unserialize($content);
        return is_array($data) ? $data : null;
    }
    
    private function saveToCache(string $key, array $data): void
    {
        if (!is_dir($this->cacheDir)) {
            @mkdir($this->cacheDir, 0755, true);
        }
        
        $cacheFile = $this->cacheDir . '/' . $key . '.cache';
        @file_put_contents($cacheFile, serialize($data), LOCK_EX);
    }
    
    public function clearCache(): void
    {
        $files = glob($this->cacheDir . '/facet_*.cache');
        foreach ($files as $file) {
            @unlink($file);
        }
    }

    public static function validateRange($from, $to): array
    {
        $fromVal = is_numeric($from) ? (int)$from : null;
        $toVal = is_numeric($to) ? (int)$to : null;
        
        if ($fromVal !== null && $toVal !== null && $fromVal > $toVal) {
            return ['from' => $toVal, 'to' => $fromVal, 'swapped' => true];
        }
        
        return ['from' => $fromVal, 'to' => $toVal, 'swapped' => false];
    }
}
