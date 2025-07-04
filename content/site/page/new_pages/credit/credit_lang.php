<?php defined( '_DOIT' ) or die( 'Restricted access' ); ?>

<?php

$lng_credit = array(
    // Hero Section
    'credit_title' => array(
        'ro' => 'Cumpărați automobilul cu ușurință Credit și Leasing în Moldova',
        'ru' => 'Покупайте автомобиль с лёгкостью Кредит и Лизинг в Молдове',
        'en' => 'Buy a car with ease Credit and Leasing in Moldova'
    ),
    
    // Calculator Section
    'calculator_title' => array(
        'ro' => 'Calculator Credit Auto',
        'ru' => 'Калькулятор Автокредита',
        'en' => 'Car Loan Calculator'
    ),
    'loan_amount_eur' => array(
        'ro' => 'Suma creditului (EUR)',
        'ru' => 'Сумма кредита (EUR)',
        'en' => 'Loan amount (EUR)'
    ),
    'loan_term_months' => array(
        'ro' => 'Termenul (luni)',
        'ru' => 'Срок (месяцев)',
        'en' => 'Term (months)'
    ),
    'monthly_payment_est' => array(
        'ro' => 'Rata lunară estimată',
        'ru' => 'Ориентировочный ежемесячный платёж',
        'en' => 'Estimated monthly payment'
    ),
    'payment_range_from' => array(
        'ro' => 'De la',
        'ru' => 'От',
        'en' => 'From'
    ),
    'payment_range_to' => array(
        'ro' => 'până la',
        'ru' => 'до',
        'en' => 'to'
    ),
    
    // Old calculator compatibility keys
    'calc_title' => array(
        'ro' => 'Calculator Credit Auto',
        'ru' => 'Калькулятор Автокредита',
        'en' => 'Car Loan Calculator'
    ),
    'calc_title_sum_tl' => array(
        'ro' => 'Suma creditului (EUR)',
        'ru' => 'Сумма кредита (EUR)',
        'en' => 'Loan amount (EUR)'
    ),
    'calc_title_term_tl' => array(
        'ro' => 'Termenul (luni)',
        'ru' => 'Срок (месяцев)',
        'en' => 'Term (months)'
    ),
    'calc_title_rata' => array(
        'ro' => 'Rata lunară estimată:',
        'ru' => 'Ориентировочный ежемесячный платёж:',
        'en' => 'Estimated monthly payment:'
    ),
    'calc_title_luni' => array(
        'ro' => 'luni',
        'ru' => 'месяцев',
        'en' => 'months'
    ),
    'calc_title_plata' => array(
        'ro' => 'de la',
        'ru' => 'от',
        'en' => 'from'
    ),
    'calc_title_plata2' => array(
        'ro' => 'până la',
        'ru' => 'до',
        'en' => 'up to'
    )
);

// Get the language from cookie or default to Romanian
$current_lang = 'ro'; // Default language

// Try to get language from cookie if it exists
if (isset($_COOKIE['lang']) && in_array($_COOKIE['lang'], array('ro', 'ru', 'en'))) {
    $current_lang = $_COOKIE['lang'];
}

// Extract translations for the current language
$lng_credit_page = array();
foreach ($lng_credit as $key => $translations) {
    if (isset($translations[$current_lang])) {
        $lng_credit_page[$key] = $translations[$current_lang];
    }
}

// Add old calculator keys to 'w' array for compatibility
$lng_credit_page['w'] = array(
    'calc_title' => $lng_credit_page['calc_title'],
    'calc_title_sum_tl' => $lng_credit_page['calc_title_sum_tl'],
    'calc_title_term_tl' => $lng_credit_page['calc_title_term_tl'],
    'calc_title_rata' => $lng_credit_page['calc_title_rata'],
    'calc_title_luni' => $lng_credit_page['calc_title_luni'],
    'calc_title_plata' => $lng_credit_page['calc_title_plata'],
    'calc_title_plata2' => $lng_credit_page['calc_title_plata2']
);

// Merge with existing $lng to preserve site translations
if (isset($lng) && is_array($lng)) {
    $lng = array_merge($lng, $lng_credit_page);
} else {
    $lng = $lng_credit_page;
}

?>
