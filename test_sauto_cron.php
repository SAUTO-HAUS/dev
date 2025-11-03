<?php
/**
 * Manual test script for SAUTO Personal cron job
 */

// Allow manual execution
define('MANUAL_CRON_TRIGGER', true);

echo "=== SAUTO Personal Cron Job Manual Test ===\n";
echo "Current time: " . date('Y-m-d H:i:s') . "\n\n";

// Include the cron job
require_once __DIR__ . '/console/sauto_personal_cron.php';

echo "\n=== Test completed ===\n";
