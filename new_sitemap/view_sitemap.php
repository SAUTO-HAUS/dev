<?php
/**
 * Sitemap file viewer with correct XML header
 */

// Set XML header
header('Content-Type: application/xml; charset=utf-8');

// Check which file to display
$file = isset($_GET['file']) ? $_GET['file'] : 'sitemap.xml';

// List of allowed files
$allowedFiles = [
    'sitemap.xml',
    'sitemap-1.xml',
    'sitemap-2.xml',
    'sitemap-3.xml'
];

// Check if file is allowed
if (!in_array($file, $allowedFiles)) {
    http_response_code(404);
    echo '<?xml version="1.0" encoding="UTF-8"?><error>File not allowed</error>';
    exit;
}

// Path to file
$filePath = dirname(__DIR__) . DIRECTORY_SEPARATOR . $file;

// Check if file exists
if (!file_exists($filePath)) {
    http_response_code(404);
    echo '<?xml version="1.0" encoding="UTF-8"?><error>File not found: ' . htmlspecialchars($file) . '</error>';
    exit;
}

// Read and display file content
$content = file_get_contents($filePath);
echo $content;
?>
