<?php
// Simple smoke test for /[lang]/telegram_adv landing page
function assert_contains($needle, $haystack, $message) {
    if (strpos($haystack, $needle) === false) {
        throw new Exception($message);
    }
}

function assert_not_contains($needle, $haystack, $message) {
    if (strpos($haystack, $needle) !== false) {
        throw new Exception($message);
    }
}

// Verify routing presence in index.php and config.php
$index = file_get_contents(__DIR__ . '/../index.php');
assert_contains("telegram_adv/telegram_adv.php", $index, 'Routing to telegram_adv missing in index.php');

$config = file_get_contents(__DIR__ . '/../content/default/config.php');
assert_contains("'telegram_adv'", $config, 'telegram_adv missing from url array in config.php');

// Prepare constants used by template
if (!defined('_DOIT')) {
    define('_DOIT', 1);
}
if (!defined('_DEFAULT')) {
    define('_DEFAULT', 'content/default');
}
if (!defined('_SITE')) {
    define('_SITE', 'content/site');
}

$languages = ['ro', 'ru', 'en'];
$lottery_terms = ['ro' => 'loterie', 'ru' => 'лотере', 'en' => 'lottery'];
foreach ($languages as $lang) {
    $_COOKIE['lang'] = $lang;
    ob_start();
    include __DIR__ . '/../content/site/page/new_pages/telegram_adv/telegram_adv.php';
    $html = ob_get_clean();

    // Access translations loaded by the template
    global $telegram_lang;

    assert_contains('https://t.me/+9ISpx4Lrvoc3NzIy', $html, "Telegram link missing for $lang");
    assert_contains($telegram_lang[$lang]['button_text'], $html, "Button text missing for $lang");
    assert_contains('og:title', $html, "Meta tags missing for $lang");
    assert_not_contains('<nav', $html, "Navigation should not be present for $lang");
    assert_not_contains($lottery_terms[$lang], $html, "Lottery mention should not be present for $lang");
    assert_not_contains('<footer', $html, "Footer should not be present for $lang");
}

echo "telegram_adv smoke test passed\n";
