<?php
/**
 * Test Script for Phone Replacement System
 * This script tests the phone replacement logic with different scenarios
 */

require_once '../App/Services/PhoneReplacementService.php';
require_once '../App/Helper/PhoneHelper.php';

use App\Services\PhoneReplacementService;
use App\Helper\PhoneHelper;

echo "=== Phone Replacement System Test ===\n\n";

// Test 1: Basic phone service functionality
echo "Test 1: Basic Phone Service\n";
echo "----------------------------\n";

$phoneService = new PhoneReplacementService();

// Test all phone configurations
$phones = $phoneService->getAllPhones();
foreach ($phones as $context => $phone) {
    echo "Context: $context => Phone: $phone\n";
}
echo "\n";

// Test 2: Car-specific phone logic
echo "Test 2: Car-Specific Phone Logic\n";
echo "--------------------------------\n";

// Test case 1: Prunkul location (Priority 1)
$carDataPrunkul = [
    'id' => 1,
    'loc' => 1, // Prunkul location ID
    'sts' => 'available',
    'group' => null
];

$phonePrunkul = PhoneHelper::getCarPhone($carDataPrunkul, 'car_page');
echo "Prunkul car phone: $phonePrunkul (Expected: +37379600747)\n";

// Test case 2: Stock-website group (Priority 2)
$carDataStock = [
    'id' => 2,
    'loc' => 3,
    'sts' => 'available',
    'group' => 'Stock-website'
];

$phoneStock = PhoneHelper::getCarPhone($carDataStock, 'car_page');
echo "Stock-website car phone: $phoneStock (Expected: +37379600386)\n";

// Test case 3: In transit status (Priority 3)
$carDataTransit = [
    'id' => 3,
    'loc' => 2,
    'sts' => 'in_transit',
    'group' => null
];

$phoneTransit = PhoneHelper::getCarPhone($carDataTransit, 'car_page');
echo "In transit car phone: $phoneTransit (Expected: +37379500735)\n";

// Test case 4: Default case (Priority 5)
$carDataDefault = [
    'id' => 4,
    'loc' => 2,
    'sts' => 'available',
    'group' => null
];

$phoneDefault = PhoneHelper::getCarPhone($carDataDefault, 'car_page');
echo "Default car phone: $phoneDefault (Expected: +37379600386)\n";
echo "\n";

// Test 3: Context-based phone logic
echo "Test 3: Context-Based Phone Logic\n";
echo "---------------------------------\n";

// Test general phone
$generalPhone = PhoneHelper::getGeneralPhone();
echo "General site phone: $generalPhone (Expected: +37379600361)\n";

// Test order page phone
$orderPhone = PhoneHelper::getOrderPhone();
echo "Order page phone: $orderPhone (Expected: +37379500735)\n";

// Test contextual phone with URL segments
$t_mp_order = ['', 'ro', 'services', 'order'];
$contextualOrderPhone = PhoneHelper::getContextualPhone($t_mp_order);
echo "Contextual order phone: $contextualOrderPhone (Expected: +37379500735)\n";

$t_mp_general = ['', 'ro', 'contacts'];
$contextualGeneralPhone = PhoneHelper::getContextualPhone($t_mp_general);
echo "Contextual general phone: $contextualGeneralPhone (Expected: +37379600361)\n";
echo "\n";

// Test 4: Phone formatting
echo "Test 4: Phone Formatting\n";
echo "------------------------\n";

$testPhone = '+37379600361';
$formattedDisplay = PhoneHelper::formatPhone($testPhone, 'display');
$formattedTel = PhoneHelper::formatPhone($testPhone, 'tel');
$formattedIntl = PhoneHelper::formatPhone($testPhone, 'international');

echo "Original: $testPhone\n";
echo "Display format: $formattedDisplay\n";
echo "Tel format: $formattedTel\n";
echo "International format: $formattedIntl\n";
echo "\n";

// Test 5: Text replacement
echo "Test 5: Text Replacement\n";
echo "------------------------\n";

$testText = 'Call us at +37379600361 for more information.';
$replacedText = $phoneService->replacePhoneNumbers($testText, 'general');
echo "Original text: $testText\n";
echo "Replaced text: $replacedText\n";
echo "\n";

echo "=== All Tests Completed ===\n";
?>
