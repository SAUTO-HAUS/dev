<!DOCTYPE html>
<html lang="ro">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>SAUTO Sitemap Test - Web Interface</title>
    <style>
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            max-width: 1200px;
            margin: 0 auto;
            padding: 20px;
            background-color: #f5f5f5;
        }
        .container {
            background: white;
            padding: 30px;
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        h1 {
            color: #2c3e50;
            text-align: center;
            margin-bottom: 30px;
        }
        .test-section {
            margin-bottom: 25px;
            padding: 20px;
            border-left: 4px solid #3498db;
            background-color: #f8f9fa;
        }
        .test-section h2 {
            color: #2c3e50;
            margin-top: 0;
        }
        .result {
            margin: 10px 0;
            padding: 8px 12px;
            border-radius: 5px;
        }
        .success {
            background-color: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
        }
        .error {
            background-color: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
        }
        .warning {
            background-color: #fff3cd;
            color: #856404;
            border: 1px solid #ffeaa7;
        }
        .info {
            background-color: #d1ecf1;
            color: #0c5460;
            border: 1px solid #bee5eb;
        }
        .summary {
            margin-top: 30px;
            padding: 20px;
            border-radius: 8px;
            font-weight: bold;
        }
        .summary.success {
            background-color: #d4edda;
            border: 2px solid #28a745;
        }
        .summary.error {
            background-color: #f8d7da;
            border: 2px solid #dc3545;
        }
        .timestamp {
            text-align: center;
            color: #6c757d;
            font-size: 0.9em;
            margin-top: 20px;
        }
        .refresh-btn {
            display: block;
            margin: 20px auto;
            padding: 10px 20px;
            background-color: #007bff;
            color: white;
            text-decoration: none;
            border-radius: 5px;
            text-align: center;
            width: 150px;
        }
        .refresh-btn:hover {
            background-color: #0056b3;
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>🔍 SAUTO Sitemap Test - Web Interface</h1>
        
        <?php
        class WebSitemapTest {
            private $testResults = [];
            private $baseDir;
            
            public function __construct($baseDir = '..') {
                $this->baseDir = rtrim($baseDir, '/\\');
            }
            
            public function runAllTests() {
                echo '<div class="info result">📁 Testing directory: ' . htmlspecialchars(realpath($this->baseDir)) . '</div>';
                
                $this->testFileExistence();
                $this->testXMLStructure();
                $this->testURLFormat();
                $this->testHreflangLinks();
                $this->displayResults();
                
                return empty($this->testResults['errors']);
            }
            
            private function testFileExistence() {
                echo '<div class="test-section">';
                echo '<h2>1. 📄 File Existence Test</h2>';
                
                $files = ['sitemap.xml', 'sitemap-1.xml', 'sitemap-2.xml', 'sitemap-3.xml'];
                
                foreach ($files as $file) {
                    $path = $this->baseDir . DIRECTORY_SEPARATOR . $file;
                    if (file_exists($path)) {
                        echo '<div class="success result">✅ ' . htmlspecialchars($file) . ' exists (' . $this->formatFileSize($path) . ')</div>';
                    } else {
                        echo '<div class="error result">❌ ' . htmlspecialchars($file) . ' missing</div>';
                        $this->testResults['errors'][] = "$file missing";
                    }
                }
                echo '</div>';
            }
            
            private function testXMLStructure() {
                echo '<div class="test-section">';
                echo '<h2>2. 🔧 XML Structure Test</h2>';
                
                $files = ['sitemap.xml', 'sitemap-1.xml', 'sitemap-2.xml', 'sitemap-3.xml'];
                
                foreach ($files as $file) {
                    $path = $this->baseDir . DIRECTORY_SEPARATOR . $file;
                    if (file_exists($path)) {
                        if ($this->isValidXML($path)) {
                            echo '<div class="success result">✅ ' . htmlspecialchars($file) . ' - Valid XML</div>';
                        } else {
                            echo '<div class="error result">❌ ' . htmlspecialchars($file) . ' - Invalid XML</div>';
                            $this->testResults['errors'][] = "$file has invalid XML";
                        }
                    }
                }
                echo '</div>';
            }
            
            private function testURLFormat() {
                echo '<div class="test-section">';
                echo '<h2>3. 🌐 URL Format Test</h2>';
                
                // Găsește fișierele sitemap care există realmente
                $files = $this->getExistingSitemapFiles();
                $expectedDomain = 'https://www.sauto.md/';
                
                foreach ($files as $file) {
                    $path = $this->baseDir . DIRECTORY_SEPARATOR . $file;
                    if (file_exists($path)) {
                        $content = file_get_contents($path);
                        $urlCount = substr_count($content, '<loc>');
                        $correctDomainCount = substr_count($content, $expectedDomain);
                        
                        if ($correctDomainCount == $urlCount && $urlCount > 0) {
                            echo '<div class="success result">✅ ' . htmlspecialchars($file) . ' - ' . $correctDomainCount . '/' . $urlCount . ' URLs have correct domain</div>';
                        } elseif ($correctDomainCount > 0) {
                            echo '<div class="warning result">⚠️ ' . htmlspecialchars($file) . ' - ' . $correctDomainCount . '/' . $urlCount . ' URLs have correct domain</div>';
                            $this->testResults['warnings'][] = "$file has URLs without correct domain";
                        } else {
                            echo '<div class="error result">❌ ' . htmlspecialchars($file) . ' - 0/' . $urlCount . ' URLs have correct domain</div>';
                            $this->testResults['errors'][] = "$file has no URLs with correct domain";
                        }
                    }
                }
                echo '</div>';
            }
            
            private function testHreflangLinks() {
                echo '<div class="test-section">';
                echo '<h2>4. 🌍 Hreflang Links Test</h2>';
                
                // Găsește fișierele sitemap care există realmente
                $files = $this->getExistingSitemapFiles();
                $expectedDomain = 'https://www.sauto.md/';
                
                foreach ($files as $file) {
                    $path = $this->baseDir . DIRECTORY_SEPARATOR . $file;
                    if (file_exists($path)) {
                        $content = file_get_contents($path);
                        $hreflangCount = substr_count($content, 'hreflang=');
                        $correctHreflangCount = substr_count($content, 'href="' . $expectedDomain);
                        
                        if ($hreflangCount == 0) {
                            echo '<div class="info result">ℹ️ ' . htmlspecialchars($file) . ' - No hreflang links found</div>';
                        } elseif ($correctHreflangCount == $hreflangCount) {
                            echo '<div class="success result">✅ ' . htmlspecialchars($file) . ' - ' . $correctHreflangCount . ' hreflang links with correct domain</div>';
                        } else {
                            echo '<div class="warning result">⚠️ ' . htmlspecialchars($file) . ' - ' . $correctHreflangCount . '/' . $hreflangCount . ' hreflang links with correct domain</div>';
                            $this->testResults['warnings'][] = "$file has hreflang links without correct domain";
                        }
                    }
                }
                echo '</div>';
            }
            
            private function isValidXML($file) {
                $content = file_get_contents($file);
                return strpos($content, '<?xml') !== false && 
                       (strpos($content, '<sitemapindex') !== false || strpos($content, '<urlset') !== false);
            }
            
            private function getExistingSitemapFiles() {
                $files = [];
                // Caută fișiere sitemap-N.xml în directorul de bază
                for ($i = 1; $i <= 10; $i++) {
                    $filename = "sitemap-{$i}.xml";
                    $path = $this->baseDir . DIRECTORY_SEPARATOR . $filename;
                    if (file_exists($path)) {
                        $files[] = $filename;
                    }
                }
                return $files;
            }
            
            private function formatFileSize($file) {
                $size = filesize($file);
                $units = ['B', 'KB', 'MB', 'GB'];
                $unit = 0;
                while ($size >= 1024 && $unit < count($units) - 1) {
                    $size /= 1024;
                    $unit++;
                }
                return round($size, 2) . ' ' . $units[$unit];
            }
            
            private function displayResults() {
                echo '<div class="test-section">';
                echo '<h2>📊 Test Summary</h2>';
                
                if (empty($this->testResults['errors']) && empty($this->testResults['warnings'])) {
                    echo '<div class="summary success">🎉 All tests passed successfully!</div>';
                } else {
                    $hasErrors = !empty($this->testResults['errors']);
                    echo '<div class="summary ' . ($hasErrors ? 'error' : 'success') . '">';
                    
                    if ($hasErrors) {
                        echo '<h3>❌ Errors Found:</h3>';
                        foreach ($this->testResults['errors'] as $error) {
                            echo '<div>• ' . htmlspecialchars($error) . '</div>';
                        }
                    }
                    
                    if (!empty($this->testResults['warnings'])) {
                        echo '<h3>⚠️ Warnings:</h3>';
                        foreach ($this->testResults['warnings'] as $warning) {
                            echo '<div>• ' . htmlspecialchars($warning) . '</div>';
                        }
                    }
                    
                    if (!$hasErrors) {
                        echo '<div>✅ Tests completed with warnings only</div>';
                    }
                    
                    echo '</div>';
                }
                echo '</div>';
            }
        }
        
        // Run the test
        $tester = new WebSitemapTest();
        $tester->runAllTests();
        ?>
        
        <a href="<?php echo $_SERVER['PHP_SELF']; ?>" class="refresh-btn">🔄 Refresh Test</a>
        
        <div class="timestamp">
            Last updated: <?php echo date('Y-m-d H:i:s'); ?>
        </div>
    </div>
</body>
</html>
