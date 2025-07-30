<?php
/**
 * SAUTO Safe Sitemap Test - Non-invasive testing
 * Tests sitemap files without affecting main environment
 */

class SafeSitemapTest {
    
    private $testResults = [];
    private $baseDir;
    
    public function __construct($baseDir = '.') {
        $this->baseDir = rtrim($baseDir, '/\\');
        echo "=== SAUTO Safe Sitemap Test ===\n";
        echo "Testing directory: " . realpath($this->baseDir) . "\n\n";
    }
    
    /**
     * Run all tests safely
     */
    public function runAllTests() {
        $this->testFileExistence();
        $this->testXMLStructure();
        $this->testURLFormat();
        $this->testHreflangLinks();
        $this->displayResults();
        
        return empty($this->testResults['errors']);
    }
    
    /**
     * Test if sitemap files exist
     */
    private function testFileExistence() {
        echo "1. Testing file existence...\n";
        
        $files = ['sitemap.xml', 'sitemap-1.xml', 'sitemap-2.xml', 'sitemap-3.xml'];
        
        foreach ($files as $file) {
            $path = $this->baseDir . DIRECTORY_SEPARATOR . $file;
            if (file_exists($path)) {
                echo "   ✅ $file exists\n";
            } else {
                echo "   ❌ $file missing\n";
                $this->testResults['errors'][] = "$file missing";
            }
        }
        echo "\n";
    }
    
    /**
     * Test XML structure without loading into memory
     */
    private function testXMLStructure() {
        echo "2. Testing XML structure...\n";
        
        $files = ['sitemap.xml', 'sitemap-1.xml', 'sitemap-2.xml', 'sitemap-3.xml'];
        
        foreach ($files as $file) {
            $path = $this->baseDir . DIRECTORY_SEPARATOR . $file;
            if (file_exists($path)) {
                if ($this->isValidXML($path)) {
                    echo "   ✅ $file - Valid XML\n";
                } else {
                    echo "   ❌ $file - Invalid XML\n";
                    $this->testResults['errors'][] = "$file has invalid XML";
                }
            }
        }
        echo "\n";
    }
    
    /**
     * Test URL format by reading file content safely
     */
    private function testURLFormat() {
        echo "3. Testing URL format...\n";
        
        $files = ['sitemap-1.xml', 'sitemap-2.xml', 'sitemap-3.xml'];
        $expectedDomain = 'https://www.testline8392.sauto.md/';
        
        foreach ($files as $file) {
            $path = $this->baseDir . DIRECTORY_SEPARATOR . $file;
            if (file_exists($path)) {
                $content = file_get_contents($path);
                $urlCount = substr_count($content, '<loc>');
                $correctDomainCount = substr_count($content, $expectedDomain);
                
                echo "   📊 $file - $correctDomainCount/$urlCount URLs have correct domain\n";
                
                if ($correctDomainCount < $urlCount) {
                    $this->testResults['warnings'][] = "$file has URLs without correct domain";
                }
            }
        }
        echo "\n";
    }
    
    /**
     * Test hreflang links
     */
    private function testHreflangLinks() {
        echo "4. Testing hreflang links...\n";
        
        $files = ['sitemap-1.xml', 'sitemap-2.xml', 'sitemap-3.xml'];
        $expectedDomain = 'https://www.testline8392.sauto.md/';
        
        foreach ($files as $file) {
            $path = $this->baseDir . DIRECTORY_SEPARATOR . $file;
            if (file_exists($path)) {
                $content = file_get_contents($path);
                $hreflangCount = substr_count($content, 'hreflang=');
                $correctHreflangCount = substr_count($content, 'href="' . $expectedDomain);
                
                echo "   📊 $file - $correctHreflangCount hreflang links with correct domain\n";
                
                if ($hreflangCount > 0 && $correctHreflangCount < $hreflangCount) {
                    $this->testResults['warnings'][] = "$file has hreflang links without correct domain";
                }
            }
        }
        echo "\n";
    }
    
    /**
     * Check if XML is valid without loading into DOM
     */
    private function isValidXML($file) {
        $content = file_get_contents($file);
        
        // Basic XML validation
        if (strpos($content, '<?xml') === false) {
            return false;
        }
        
        // Check for basic XML structure
        if (strpos($content, '<sitemapindex') !== false || strpos($content, '<urlset') !== false) {
            return true;
        }
        
        return false;
    }
    
    /**
     * Display test results
     */
    private function displayResults() {
        echo "=== Test Results ===\n";
        
        if (empty($this->testResults['errors']) && empty($this->testResults['warnings'])) {
            echo "✅ All tests passed!\n";
        } else {
            if (!empty($this->testResults['errors'])) {
                echo "❌ Errors found:\n";
                foreach ($this->testResults['errors'] as $error) {
                    echo "   - $error\n";
                }
            }
            
            if (!empty($this->testResults['warnings'])) {
                echo "⚠️  Warnings:\n";
                foreach ($this->testResults['warnings'] as $warning) {
                    echo "   - $warning\n";
                }
            }
        }
        
        echo "\n=== Test Complete ===\n";
    }
}

// Run test if called directly
if (php_sapi_name() === 'cli') {
    $tester = new SafeSitemapTest();
    $success = $tester->runAllTests();
    exit($success ? 0 : 1);
}
?>
