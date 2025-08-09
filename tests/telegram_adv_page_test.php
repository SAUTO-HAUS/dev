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
  $translations = require __DIR__ . '/../content/site/page/new_pages/telegram_adv/lang_tel.php';
  foreach ($languages as $lang) {
      $_SERVER['REQUEST_URI'] = '/' . $lang . '/telegram_adv';
      ob_start();
      include __DIR__ . '/../content/site/page/new_pages/telegram_adv/telegram_adv.php';
      $html = ob_get_clean();

      assert_contains('https://t.me/+9ISpx4Lrvoc3NzIy', $html, "Telegram link missing for $lang");
      assert_contains('telegram_adv.css', $html, "CSS not loaded for $lang");
      assert_contains('tg-landing', $html, "tg-landing class missing for $lang");
      assert_contains($translations[$lang]['title'], $html, "Title missing for $lang");
      assert_contains($translations[$lang]['sub'], $html, "Subtitle missing for $lang");
      assert_contains($translations[$lang]['list'][0]['text'], $html, "List item missing for $lang");
      assert_contains($translations[$lang]['button_text'], $html, "Button text missing for $lang");
      assert_contains($translations[$lang]['meta_description'], $html, "Meta description missing for $lang");
      assert_contains('<meta name="description"', $html, "Meta description tag missing for $lang");
      assert_contains('<meta property="og:title"', $html, "OG title tag missing for $lang");
      assert_contains('<meta property="og:description"', $html, "OG description tag missing for $lang");
      assert_contains('<meta name="twitter:card"', $html, "Twitter card meta missing for $lang");
      assert_not_contains('<nav', $html, "Navigation should not be present for $lang");
      assert_not_contains($lottery_terms[$lang], $html, "Lottery mention should not be present for $lang");
      assert_not_contains('<footer', $html, "Footer should not be present for $lang");
  }

echo "telegram_adv smoke test passed\n";
