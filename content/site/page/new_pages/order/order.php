<?php defined('_DOIT') or die('Restricted access');

$orderTranslationsPath = __DIR__ . '/order_lang.php';
if (!is_file($orderTranslationsPath)) {
    die('Translation file not found');
}

$orderTranslations = include $orderTranslationsPath;
if (!is_array($orderTranslations)) {
    die('Invalid translation file');
}

$currentLang = isset($_COOKIE['lang']) ? $_COOKIE['lang'] : 'ro';
if (!isset($orderTranslations[$currentLang])) {
    $currentLang = 'ro';
}

$orderTranslate = function($keys) use ($orderTranslations, $currentLang) {
    $keys = is_array($keys) ? $keys : [$keys];
    $value = $orderTranslations[$currentLang] ?? [];
    foreach ($keys as $key) {
        if (!isset($value[$key])) {
            return '';
        }
        $value = $value[$key];
    }
    return $value;
};

$metaTitle = $orderTranslate(['meta', 'title']);
$metaDescription = $orderTranslate(['meta', 'description']);
$metaKeywords = $orderTranslate(['meta', 'keywords']);
$h1 = $orderTranslate(['meta', 'h1']);
?>

<h1><?=htmlspecialchars($h1, ENT_QUOTES, 'UTF-8')?></h1>
