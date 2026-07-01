<?php
/**
 * Phone Number Replacement Script
 * This script replaces all hardcoded phone numbers across the site with dynamic ones
 */

require_once '../content/default/config.php';
require_once '../content/default/dbi.php';

use App\Services\PhoneReplacementService;

// Initialize the phone replacement service
$phoneService = new PhoneReplacementService();

// Define all template files that need phone number replacement
$templateFiles = [
    '../content/site/head.php',
    '../content/site/b_body.php',
    '../content/site/page/contacts.php',
    '../content/site/page/services.php',
    '../content/site/page/tyres.php',
    '../content/default/language.php'
];

// Phone number patterns to search for
$phonePatterns = [
    '/\+373\s?\d{2}\s?\d{3}\s?\d{3}/',
    '/\(\+373\)\s?\d{2}[\s\-]?\d{3}[\s\-]?\d{3}/',
    '/\+373\d{8}/',
    '/00373\d{8}/',
    '/373\d{8}/'
];

// Use only approved 5-group phone system - no additional groups allowed
// All replacements should use PhoneReplacementService methods
$phoneService = new PhoneReplacementService();
$approvedPhones = $phoneService->getAllPhones();

echo "Starting phone number replacement...\n";

foreach ($templateFiles as $file) {
    if (!file_exists($file)) {
        echo "File not found: $file\n";
        continue;
    }
    
    $content = file_get_contents($file);
    $originalContent = $content;
    
    // Replace all phone numbers with approved numbers using PhoneReplacementService
    // No special cases - all unauthorized numbers must be replaced
    $content = $phoneService->replacePhoneNumbers($content, 'general');
    
    if ($content !== $originalContent) {
        file_put_contents($file, $content);
        echo "Updated: $file\n";
    } else {
        echo "No changes needed: $file\n";
    }
}

echo "Phone number replacement completed.\n";

// Generate SQL script for database phone number updates
$sqlScript = "-- Phone Number Database Update Script
-- Generated on " . date('Y-m-d H:i:s') . "

-- Update any phone numbers in text fields (if they exist)
-- You may need to adjust table and column names based on your actual database structure

-- Example updates (adjust as needed):
-- UPDATE {$prefx}_settings SET value = '+37379600361' WHERE setting_name = 'general_phone';
-- UPDATE {$prefx}_settings SET value = '+37379600747' WHERE setting_name = 'prunkul_phone';
-- UPDATE {$prefx}_settings SET value = '+37379600386' WHERE setting_name = 'stock_phone';
-- UPDATE {$prefx}_settings SET value = '+37379500735' WHERE setting_name = 'lacomanda_phone';

-- Search for any text fields containing phone numbers
-- SELECT * FROM {$prefx}_pages WHERE content LIKE '%+373%';
-- SELECT * FROM {$prefx}_settings WHERE value LIKE '%+373%';

-- Add phone configuration table
CREATE TABLE IF NOT EXISTS {$prefx}_phone_config (
    id INT AUTO_INCREMENT PRIMARY KEY,
    context VARCHAR(50) NOT NULL,
    phone_number VARCHAR(20) NOT NULL,
    description VARCHAR(255),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- Insert only approved 5-group phone configuration
INSERT INTO {$prefx}_phone_config (context, phone_number, description) VALUES
('prunkul', '+37379600747', 'Prunkul Branch'),
('stock_website', '+37379600386', 'Website-Stock'),
('on_order', '+37379500735', 'Website-On-Order (in transit + on order)'),
('website_all', '+37379600361', 'Website-All (general number)'),
('admin_default', '+37379600361', 'Admin default (replaces +37379600446)')
ON DUPLICATE KEY UPDATE 
    phone_number = VALUES(phone_number),
    description = VALUES(description),
    updated_at = CURRENT_TIMESTAMP;

-- Remove any unauthorized phone numbers from database
-- UPDATE tables to replace unauthorized numbers with approved ones
-- These queries should be run manually after reviewing your database structure
";

file_put_contents('../sql_scripts/phone_replacement_migration.sql', $sqlScript);
echo "SQL migration script created: ../sql_scripts/phone_replacement_migration.sql\n";
?>
