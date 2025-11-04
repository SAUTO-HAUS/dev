<?php
/**
 * Test script for random 999.md posting times
 * This script demonstrates the 999.md random time generation functionality
 */

require_once __DIR__ . '/App/Helper/RandomTimeHelper.php';

echo "<h2>999.md Random Time Generation Test</h2>\n";
echo "<p><strong>🔧 Configurable from Admin Panel:</strong> /adminsauto/sett/publication_settings</p>\n";

echo "<h3>🏪 999.md Random Times</h3>\n";
echo "<p>Generating 15 random 999.md times using database settings:</p>\n";
echo "<ul>\n";

// Generate 15 random 999.md times
for ($i = 1; $i <= 15; $i++) {
    $randomTime = \App\Helper\RandomTimeHelper::generateRandom999mdTime();
    echo "<li>999.md Test #{$i}: <strong>{$randomTime}</strong></li>\n";
}

echo "</ul>\n";

echo "<h3>📊 Comparison with Other Platforms</h3>\n";
echo "<table border='1' cellpadding='8' cellspacing='0' style='border-collapse: collapse; margin: 20px 0;'>\n";
echo "<tr style='background: #f8f9fa;'>\n";
echo "<th>Platform</th><th>Random Time 1</th><th>Random Time 2</th><th>Random Time 3</th>\n";
echo "</tr>\n";

// Generate comparison times
for ($i = 1; $i <= 3; $i++) {
    $facebookTime = \App\Helper\RandomTimeHelper::generateRandomFacebookTime();
    $telegramTime = \App\Helper\RandomTimeHelper::generateRandomTelegramTime();
    $time999md = \App\Helper\RandomTimeHelper::generateRandom999mdTime();
    
    echo "<tr>\n";
    echo "<td><strong>Facebook</strong></td><td>{$facebookTime}</td><td>" . \App\Helper\RandomTimeHelper::generateRandomFacebookTime() . "</td><td>" . \App\Helper\RandomTimeHelper::generateRandomFacebookTime() . "</td>\n";
    echo "</tr>\n";
    echo "<tr>\n";
    echo "<td><strong>Telegram</strong></td><td>{$telegramTime}</td><td>" . \App\Helper\RandomTimeHelper::generateRandomTelegramTime() . "</td><td>" . \App\Helper\RandomTimeHelper::generateRandomTelegramTime() . "</td>\n";
    echo "</tr>\n";
    echo "<tr>\n";
    echo "<td><strong>999.md</strong></td><td>{$time999md}</td><td>" . \App\Helper\RandomTimeHelper::generateRandom999mdTime() . "</td><td>" . \App\Helper\RandomTimeHelper::generateRandom999mdTime() . "</td>\n";
    echo "</tr>\n";
    break; // Only show one row for comparison
}

echo "</table>\n";

echo "<h3>✅ Verification</h3>\n";
echo "<p>All times should be:</p>\n";
echo "<ul>\n";
echo "<li><strong>999.md:</strong> Within configured time ranges (default: 18:00-22:00)</li>\n";
echo "<li><strong>Intervals:</strong> Based on configured intervals (default: 5 minutes)</li>\n";
echo "<li><strong>Randomness:</strong> Different each time the page is refreshed</li>\n";
echo "<li><strong>Independence:</strong> Each platform uses separate settings</li>\n";
echo "<li><strong>Configurable:</strong> Change ranges in admin panel: /adminsauto/sett/publication_settings</li>\n";
echo "<li><strong>Replaces:</strong> Fixed dropdown with 18:00, 19:00, 20:00, 21:00, 22:00</li>\n";
echo "</ul>\n";

echo "<h3>🎯 Usage in SAUTO Personal</h3>\n";
echo "<p>The random 999.md times are now used in:</p>\n";
echo "<ul>\n";
echo "<li><strong>features_form.php</strong> - Stock cars SAUTO Personal scheduling</li>\n";
echo "<li><strong>order_features_form.php</strong> - Order cars SAUTO Personal scheduling</li>\n";
echo "<li>Instead of fixed dropdown, users get random times automatically</li>\n";
echo "<li>Each car gets a different posting time within the configured range</li>\n";
echo "</ul>\n";

echo "<p><strong>Current time:</strong> " . date('Y-m-d H:i:s') . "</p>\n";
echo "<p><em>Refresh this page to see new random times generated.</em></p>\n";
?>
