<?php
/**
 * Viewer pentru fișierele sitemap cu header-ul XML corect
 */

// Setează header-ul pentru XML
header('Content-Type: application/xml; charset=utf-8');

// Verifică ce fișier să afișeze
$file = isset($_GET['file']) ? $_GET['file'] : 'sitemap.xml';

// Lista fișierelor permise
$allowedFiles = [
    'sitemap.xml',
    'sitemap-1.xml',
    'sitemap-2.xml',
    'sitemap-3.xml'
];

// Verifică dacă fișierul este permis
if (!in_array($file, $allowedFiles)) {
    http_response_code(404);
    echo '<?xml version="1.0" encoding="UTF-8"?><error>File not allowed</error>';
    exit;
}

// Calea către fișier
$filePath = dirname(__DIR__) . DIRECTORY_SEPARATOR . $file;

// Verifică dacă fișierul există
if (!file_exists($filePath)) {
    http_response_code(404);
    echo '<?xml version="1.0" encoding="UTF-8"?><error>File not found: ' . htmlspecialchars($file) . '</error>';
    exit;
}

// Citește și afișează conținutul fișierului
$content = file_get_contents($filePath);
echo $content;
?>
