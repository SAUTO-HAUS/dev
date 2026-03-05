<?php
use App\Services\MonitorService;

defined( '_DOIT' ) or die( 'Restricted access' );

if (!isset($user_role) || $user_role !== 'gordon') {
    http_response_code(403);
    header('Content-Type: application/json');
    echo json_encode(['error' => 'Access denied. Terminal available only for superadmin.']);
    exit;
}

$fn = __post('fn') ?: __get('fn');

if ($fn === 'get_status') {
    try {
        $monitor = new MonitorService();
        $status = $monitor->getSystemStatus();
        
        $returnIt = $status;
        
    } catch (Exception $e) {
        http_response_code(500);
        $returnIt = [
            'error' => 'Monitor service error',
            'message' => $e->getMessage(),
            'status' => 'error'
        ];
    }
}

if ($fn === 'clear_cache') {
    try {
        $cacheDir = $_SERVER['DOCUMENT_ROOT'] . '/cache/';
        $deletedCount = 0;
        $errors = [];
        
        if (is_dir($cacheDir)) {
            $files = scandir($cacheDir);
            foreach ($files as $file) {
                if ($file !== '.' && $file !== '..' && $file !== 'index.html') {
                    $filePath = $cacheDir . $file;
                    if (is_file($filePath)) {
                        if (unlink($filePath)) {
                            $deletedCount++;
                        } else {
                            $errors[] = $file;
                        }
                    }
                }
            }
            
            $returnIt = [
                'success' => true,
                'deleted' => $deletedCount,
                'errors' => $errors,
                'message' => "Удалено файлов: $deletedCount"
            ];
        } else {
            $returnIt = [
                'success' => false,
                'message' => 'Cache directory not found'
            ];
        }
        
    } catch (Exception $e) {
        http_response_code(500);
        $returnIt = [
            'success' => false,
            'error' => 'Cache clear error',
            'message' => $e->getMessage()
        ];
    }
}
