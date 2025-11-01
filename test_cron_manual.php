<?php
// Test cron manual
define('MANUAL_CRON_TRIGGER', true);

echo "=== MANUAL CRON TEST ===\n";
echo "Starting Facebook cron test...\n\n";

// Include the cron script
include 'scheduled_facebook_posts.php';

echo "\n=== TEST COMPLETED ===\n";
?>
