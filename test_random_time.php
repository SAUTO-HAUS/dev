<?php
/**
 * Test script for random Facebook and Telegram posting times
 * This script demonstrates the random time generation functionality
 */

require_once __DIR__ . '/App/Helper/RandomTimeHelper.php';

echo "<h2>Random Social Media Time Generation Test</h2>\n";
echo "<p><strong>🔧 Configurable from Admin Panel:</strong> /adminsauto/sett/publication_settings</p>\n";

echo "<h3>📘 Facebook Random Times</h3>\n";
echo "<p>Generating 10 random Facebook times using database settings:</p>\n";
echo "<ul>\n";

// Generate 10 random Facebook times
for ($i = 1; $i <= 10; $i++) {
    $randomTime = \App\Helper\RandomTimeHelper::generateRandomFacebookTime();
    echo "<li>Facebook Test #{$i}: <strong>{$randomTime}</strong></li>\n";
}

echo "</ul>\n";

echo "<h3>📱 Telegram Random Times</h3>\n";
echo "<p>Generating 10 random Telegram times using database settings:</p>\n";
echo "<ul>\n";

// Generate 10 random Telegram times
for ($i = 1; $i <= 10; $i++) {
    $randomTime = \App\Helper\RandomTimeHelper::generateRandomTelegramTime();
    echo "<li>Telegram Test #{$i}: <strong>{$randomTime}</strong></li>\n";
}

echo "</ul>\n";

echo "<h3>Custom Time Range Test</h3>\n";
echo "<p>Generating 10 random times between 19:00 and 21:00 with 10-minute intervals:</p>\n";
echo "<ul>\n";

// Test custom parameters
for ($i = 1; $i <= 10; $i++) {
    $customTime = \App\Helper\RandomTimeHelper::generateRandomTime(19, 21, 10);
    echo "<li>Custom Test #{$i}: {$customTime}</li>\n";
}

echo "</ul>\n";

echo "<h3>✅ Verification</h3>\n";
echo "<p>All times should be:</p>\n";
echo "<ul>\n";
echo "<li><strong>Facebook & Telegram:</strong> Within configured time ranges (default: 18:00-22:00)</li>\n";
echo "<li><strong>Intervals:</strong> Based on configured intervals (default: 5 minutes)</li>\n";
echo "<li><strong>Randomness:</strong> Different each time the page is refreshed</li>\n";
echo "<li><strong>Independence:</strong> Facebook and Telegram use separate settings</li>\n";
echo "<li><strong>Configurable:</strong> Change ranges in admin panel: /adminsauto/sett/publication_settings</li>\n";
echo "</ul>\n";

echo "<p><strong>Current time:</strong> " . date('Y-m-d H:i:s') . "</p>\n";
echo "<p><em>Refresh this page to see new random times generated.</em></p>\n";
?>
