<?php
/**
 * SAUTO Sitemap Test Script
 * Comprehensive testing of sitemap generation and validation
 */

echo "=== SAUTO Sitemap Test Suite ===\n\n";

// Test 1: Check if all files exist
echo "1. Testing file existence...\n";
$requiredFiles = ['sitemap.xml', 'sitemap-1.xml', 'sitemap-2.xml', 'sitemap-3.xml'];
foreach ($requiredFiles as $file) {
    if (file_exists($file)) {
        echo "   ✅ $file exists\n";
    } else {
        echo "   ❌ $file missing\n";
    }
}

// Test 2: Validate XML structure
echo "\n2. Testing XML structure...\n";
foreach ($requiredFiles as $file) {
    if (file_exists($file)) {
        $xml = new DOMDocument();
        if ($xml->load($file)) {
            echo "   ✅ $file - Valid XML\n";
        } else {
            echo "   ❌ $file - Invalid XML\n";
        }
    }
}

// Test 3: Check URL format
echo "\n3. Testing URL format...\n";
foreach (['sitemap-1.xml', 'sitemap-2.xml', 'sitemap-3.xml'] as $file) {
    if (file_exists($file)) {
        $xml = new DOMDocument();
        $xml->load($file);
        $urls = $xml->getElementsByTagName('loc');
        
        $validUrls = 0;
        $totalUrls = $urls->length;
        
        foreach ($urls as $url) {
            $urlValue = $url->nodeValue;
            if (strpos($urlValue, 'https://testline8392.sauto.md/') === 0) {
                $validUrls++;
            }
        }
        
        echo "   ✅ $file - $validUrls/$totalUrls URLs have correct domain\n";
    }
}

// Test 4: Check hreflang links
echo "\n4. Testing hreflang links...\n";
foreach (['sitemap-1.xml', 'sitemap-2.xml', 'sitemap-3.xml'] as $file) {
    if (file_exists($file)) {
        $xml = new DOMDocument();
        $xml->load($file);
        $hreflangs = $xml->getElementsByTagName('link');
        
        $validHreflangs = 0;
        foreach ($hreflangs as $link) {
            $href = $link->getAttribute('href');
            if (strpos($href, 'https://testline8392.sauto.md/') === 0) {
                $validHreflangs++;
            }
        }
        
        echo "   ✅ $file - $validHreflangs hreflang links with correct domain\n";
    }
}

// Test 5: Run validation script
echo "\n5. Running validation script...\n";
ob_start();
include 'validate_sitemap.php';
$validationOutput = ob_get_clean();

if (strpos($validationOutput, 'All sitemap files are valid!') !== false) {
    echo "   ✅ Validation passed\n";
} else {
    echo "   ❌ Validation failed\n";
    echo "   Output: " . substr($validationOutput, 0, 200) . "...\n";
}

// Test 6: Check file sizes
echo "\n6. Testing file sizes...\n";
foreach ($requiredFiles as $file) {
    if (file_exists($file)) {
        $size = filesize($file);
        echo "   📊 $file - " . number_format($size) . " bytes\n";
    }
}

echo "\n=== Test Complete ===\n";
?>
