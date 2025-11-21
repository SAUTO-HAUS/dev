<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "<h1>Debug View Logs</h1>";

// Test 1: Basic PHP
echo "<h2>1. PHP Works: ✅</h2>";

// Test 2: Constants
echo "<h2>2. Testing Constants</h2>";
define('_DOIT', 1);
echo "_DOIT defined: ✅<br>";

$docRoot = $_SERVER["DOCUMENT_ROOT"];
echo "DOCUMENT_ROOT: " . $docRoot . "<br>";

define('_DEFAULT', $docRoot . '/content/default');
echo "_DEFAULT: " . _DEFAULT . "<br><br>";

// Test 3: File existence
echo "<h2>3. Testing File Paths</h2>";
$files = [
    'defines.php' => _DEFAULT . '/defines.php',
    'environment.php' => $docRoot . '/environment.php',
    'config.php' => _DEFAULT . '/config.php',
    'dbi.php' => _DEFAULT . '/dbi.php'
];

foreach ($files as $name => $path) {
    $exists = file_exists($path);
    echo "$name: " . ($exists ? "✅ EXISTS" : "❌ NOT FOUND") . " ($path)<br>";
}

echo "<br><h2>4. Testing Includes</h2>";

try {
    require_once (_DEFAULT.'/defines.php');
    echo "defines.php: ✅ Loaded<br>";
} catch (Exception $e) {
    echo "defines.php: ❌ ERROR - " . $e->getMessage() . "<br>";
}

try {
    require_once (_DEFAULT.'/functions.php');
    echo "functions.php: ✅ Loaded<br>";
} catch (Exception $e) {
    echo "functions.php: ❌ ERROR - " . $e->getMessage() . "<br>";
}

try {
    require_once ($docRoot . '/environment.php');
    echo "environment.php: ✅ Loaded<br>";
} catch (Exception $e) {
    echo "environment.php: ❌ ERROR - " . $e->getMessage() . "<br>";
}

try {
    require_once (_DEFAULT.'/config.php');
    echo "config.php: ✅ Loaded<br>";
} catch (Exception $e) {
    echo "config.php: ❌ ERROR - " . $e->getMessage() . "<br>";
}

try {
    require_once (_DEFAULT.'/dbi.php');
    echo "dbi.php: ✅ Loaded<br>";
} catch (Exception $e) {
    echo "dbi.php: ❌ ERROR - " . $e->getMessage() . "<br>";
}

echo "<br><h2>5. Testing Database Connection</h2>";

if (isset($db)) {
    echo "DB object exists: ✅<br>";
    echo "DB type: " . get_class($db) . "<br>";
    
    if (isset($prefx)) {
        echo "Prefix: " . $prefx . "<br>";
        
        // Test query
        try {
            $result = $db->query("SHOW TABLES LIKE '{$prefx}_data_feed_log'");
            $exists = $result->rowCount() > 0;
            echo "Table {$prefx}_data_feed_log: " . ($exists ? "✅ EXISTS" : "❌ NOT FOUND") . "<br>";
            
            if ($exists) {
                $count = $db->query("SELECT COUNT(*) as cnt FROM {$prefx}_data_feed_log")->fetch();
                echo "Records in table: " . $count['cnt'] . "<br>";
            }
        } catch (Exception $e) {
            echo "Query error: ❌ " . $e->getMessage() . "<br>";
        }
    } else {
        echo "Prefix variable: ❌ NOT SET<br>";
    }
} else {
    echo "DB object: ❌ NOT SET<br>";
}

echo "<br><h2>6. All Variables</h2>";
echo "<pre>";
echo "Defined constants:\n";
foreach (get_defined_constants(true)['user'] as $name => $value) {
    if (strpos($name, '_') === 0) {
        echo "$name = " . (is_string($value) ? $value : var_export($value, true)) . "\n";
    }
}
echo "\n\nDefined variables:\n";
echo "prefx = " . (isset($prefx) ? $prefx : 'NOT SET') . "\n";
echo "db = " . (isset($db) ? 'SET (' . get_class($db) . ')' : 'NOT SET') . "\n";
echo "</pre>";
?>
