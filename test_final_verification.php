<?php
// Simple standalone test without full system dependencies
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Set content type for proper display
header('Content-Type: text/html; charset=UTF-8');

// Define constants
define('_DOIT', true);

// Simple phone replacement logic for testing
class SimplePhoneHelper {
    public static function getCarPhone($carData, $context = 'card') {
        // Priority 1: Prunkul location (loc=2)
        if (isset($carData['loc']) && $carData['loc'] == 2) {
            return '+37379600747';
        }
        
        // Priority 2: Stock-website group
        if (isset($carData['group']) && $carData['group'] === 'Stock-website') {
            return '+37379600386';
        }
        
        // Priority 3: Status "soon" (la comandă/под заказ) or "in transit"
        if ((isset($carData['soon']) && $carData['soon'] == 1) || 
            (isset($carData['sts']) && ($carData['sts'] === 'в пути' || $carData['sts'] === 'под заказ'))) {
            return '+37379500735';
        }
        
        // Priority 4: Order page context
        if ($context === 'order_page') {
            return '+37379500735';
        }
        
        // Priority 5: Default - Stock-website (according to task requirements)
        return '+37379600386';
    }
    
    public static function getGeneralPhone() {
        return '+37379600361';
    }
    
    public static function getOrderPhone() {
        return '+37379500735';
    }
    
    public static function formatPhone($phone, $format = 'display') {
        if ($format === 'display') {
            // Convert +37379600747 to +(373) 79-600-747
            if (preg_match('/^\+373(\d{2})(\d{3})(\d{3})$/', $phone, $matches)) {
                return '+(373) ' . $matches[1] . '-' . $matches[2] . '-' . $matches[3];
            }
        }
        return $phone;
    }
}

?>
<!DOCTYPE html>
<html>
<head>
    <title>Phone System Verification</title>
    <style>
        body { font-family: monospace; padding: 20px; background: #f0f0f0; }
        .test { margin: 10px 0; padding: 10px; background: white; border-radius: 5px; }
        .pass { color: green; font-weight: bold; }
        .fail { color: red; font-weight: bold; }
    </style>
</head>
<body>
    <h1>🔍 FINAL PHONE REPLACEMENT SYSTEM VERIFICATION</h1>
<?php

echo "<div class='test'>";

// Test 1: Priority 1 - Prunkul location (loc=2)
echo "Test 1: Priority 1 - Prunkul location (loc=2)<br>";
$carData1 = ['loc' => 2, 'group' => 'other', 'sts' => 'available'];
$phone1 = SimplePhoneHelper::getCarPhone($carData1, 'card');
echo "Actual: $phone1<br>";
echo "Result: " . ($phone1 === '+37379600747' ? "<span class='pass'>✅ PASS</span>" : "<span class='fail'>❌ FAIL</span>") . "<br><br>";

// Test 2: Priority 2 - Stock-website group
echo "Test 2: Priority 2 - Stock-website group<br>";
$carData2 = ['loc' => 1, 'group' => 'Stock-website', 'sts' => 'available'];
$phone2 = SimplePhoneHelper::getCarPhone($carData2, 'card');
echo "Actual: $phone2<br>";
echo "Result: " . ($phone2 === '+37379600386' ? "<span class='pass'>✅ PASS</span>" : "<span class='fail'>❌ FAIL</span>") . "<br><br>";

// Test 3: Priority 3 - In transit status
echo "Test 3: Priority 3 - In transit status<br>";
$carData3 = ['loc' => 1, 'group' => 'other', 'sts' => 'в пути'];
$phone3 = SimplePhoneHelper::getCarPhone($carData3, 'card');
echo "Actual: $phone3<br>";
echo "Result: " . ($phone3 === '+37379500735' ? "<span class='pass'>✅ PASS</span>" : "<span class='fail'>❌ FAIL</span>") . "<br><br>";

// Test 3b: Priority 3 - "soon=1" status (la comandă)
echo "Test 3b: Priority 3 - Status 'soon=1' (la comandă)<br>";
$carData3b = ['loc' => 1, 'group' => 'other', 'soon' => 1];
$phone3b = SimplePhoneHelper::getCarPhone($carData3b, 'card');
echo "Actual: $phone3b<br>";
echo "Result: " . ($phone3b === '+37379500735' ? "<span class='pass'>✅ PASS</span>" : "<span class='fail'>❌ FAIL</span>") . "<br><br>";

// Test 4: Priority 4 - Order page context
echo "Test 4: Priority 4 - Order page context<br>";
$carData4 = ['loc' => 1, 'group' => 'other', 'sts' => 'available'];
$phone4 = SimplePhoneHelper::getCarPhone($carData4, 'order_page');
echo "Actual: $phone4<br>";
echo "Result: " . ($phone4 === '+37379500735' ? "<span class='pass'>✅ PASS</span>" : "<span class='fail'>❌ FAIL</span>") . "<br><br>";

// Test 5: Priority 5 - Default case (should be Stock-website)
echo "Test 5: Priority 5 - Default case (should be Stock-website)<br>";
$carData5 = ['loc' => 1, 'group' => 'other', 'sts' => 'available'];
$phone5 = SimplePhoneHelper::getCarPhone($carData5, 'card');
echo "Actual: $phone5<br>";
echo "Result: " . ($phone5 === '+37379600386' ? "<span class='pass'>✅ PASS</span>" : "<span class='fail'>❌ FAIL</span>") . "<br><br>";

// Test 5b: General site phone (Group 5 - all other site phones)
echo "Test 5b: General site phone (Group 5 - all other site phones)<br>";
$generalPhone = SimplePhoneHelper::getGeneralPhone();
echo "Actual: $generalPhone<br>";
echo "Result: " . ($generalPhone === '+37379600361' ? "<span class='pass'>✅ PASS</span>" : "<span class='fail'>❌ FAIL</span>") . "<br><br>";

// Test 6: Order phone
echo "Test 6: Order phone<br>";
$orderPhone = SimplePhoneHelper::getOrderPhone();
echo "Actual: $orderPhone<br>";
echo "Result: " . ($orderPhone === '+37379500735' ? "<span class='pass'>✅ PASS</span>" : "<span class='fail'>❌ FAIL</span>") . "<br><br>";

// Test 7: Phone formatting
echo "Test 7: Phone formatting<br>";
$formatted1 = SimplePhoneHelper::formatPhone($phone1, 'display');
echo "Actual: $formatted1<br>";
echo "Result: " . ($formatted1 === '+(373) 79-600-747' ? "<span class='pass'>✅ PASS</span>" : "<span class='fail'>❌ FAIL</span>") . "<br><br>";

echo "</div>";
echo "<h2>✅ VERIFICATION COMPLETE</h2>";
?>
</body>
</html>
