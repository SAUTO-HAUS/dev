<?php
use App\Services\MonitorService;

defined( '_DOIT' ) or die( 'Restricted access' );

if (!isset($i_counts) || $i_counts != 1) {
    http_response_code(403);
    header('Content-Type: application/json');
    echo json_encode(['error' => 'Unauthorized']);
    exit;
}

require_once(_ADM_INCL.'/rbac.php');
require_once(_ADM_INCL.'/rbac_config.php');

if (!isset($user_role) || $user_role !== 'gordon') {
    http_response_code(403);
    header('Content-Type: application/json');
    echo json_encode(['error' => 'Access denied. Terminal available only for superadmin.']);
    exit;
}

if (!isset($_COOKIE['sess']) || empty($_COOKIE['sess'])) {
    http_response_code(403);
    header('Content-Type: application/json');
    echo json_encode(['error' => 'Invalid session']);
    exit;
}

try {
    $monitor = new MonitorService();
    $status = $monitor->getSystemStatus();
    
    header('Content-Type: application/json');
    echo json_encode($status, JSON_UNESCAPED_UNICODE);
    
} catch (Exception $e) {
    http_response_code(500);
    header('Content-Type: application/json');
    echo json_encode([
        'error' => 'Monitor service error',
        'message' => $e->getMessage(),
        'status' => 'error'
    ], JSON_UNESCAPED_UNICODE);
}
