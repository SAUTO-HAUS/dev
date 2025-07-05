<?php defined( '_DOIT' ) or die( 'Restricted access' ); ?>

<?php

// Cream array-ul temporar înainte de a-l combina cu $lng
$lng_credit_page = array(
    // Plasăm toate traducerile în subarray-ul 'w' pentru a fi compatibile cu structura site-ului
    'w' => array(
        // Hero Section
        'hero_main_title' => array(
            'ro' => 'CUMPĂRAȚI AUTOMOBILUL CU UȘURINȚĂ',
            'ru' => 'ПОКУПАЙТЕ АВТОМОБИЛЬ С ЛЁГКОСТЬЮ',
            'en' => 'BUY A CAR WITH EASE'
        ),
        'hero_subtitle' => array(
            'ro' => 'CREDIT ȘI LEASING ÎN MOLDOVA',
            'ru' => 'КРЕДИТ И ЛИЗИНГ В МОЛДОВЕ',
            'en' => 'CREDIT AND LEASING IN MOLDOVA'
        ),
        'credit_title' => array(
            'ro' => 'Cumpărați automobilul cu ușurință Credit și Leasing în Moldova',
            'ru' => 'Покупайте автомобиль с лёгкостью Кредит и Лизинг в Молдове',
            'en' => 'Buy a car with ease Credit and Leasing in Moldova'
        ),
        
        // Calculator Section
        'calculator_title' => array(
            'ro' => 'Calculator Credit',
            'ru' => 'Кредитный калькулятор',
            'en' => 'Loan Calculator'
        ),
        'loan_amount_eur' => array(
            'ro' => 'Suma creditului',
            'ru' => 'Сумма кредита',
            'en' => 'Loan amount'
        ),
        'loan_term_months' => array(
            'ro' => 'Termenul',
            'ru' => 'Срок кредита',
            'en' => 'Term'
        ),
        'monthly_payment_est' => array(
            'ro' => 'Rata lunară',
            'ru' => 'Ежемесячный платеж',
            'en' => 'Monthly payment'
        ),
        'payment_range_from' => array(
            'ro' => 'De la',
            'ru' => 'от',
            'en' => 'From'
        ),
        'payment_range_to' => array(
            'ro' => 'până la',
            'ru' => 'до',
            'en' => 'to'
        ),
        
        // Necesar pentru afișarea lunilor în slider
        'calc_title_luni' => array(
            'ro' => 'luni',
            'ru' => 'месяцев',
            'en' => 'months'
        ),
        // Necesar pentru afișarea plății lunare
        'calc_title_plata' => array(
            'ro' => 'de la',
            'ru' => 'от',
            'en' => 'from'
        ),
        'calc_title_plata2' => array(
            'ro' => 'până la',
            'ru' => 'до',
            'en' => 'up to'
        ),
        
        // Footer keys needed for site functionality
        'contacts' => array(
            'ro' => 'Contacte',
            'ru' => 'Контакты',
            'en' => 'Contacts'
        )
    )
);

// Determinăm limba curentă din cookie sau default la română
$current_lang = 'ro';
if (isset($_COOKIE['lang']) && in_array($_COOKIE['lang'], array('ro', 'ru', 'en'))) {
    $current_lang = $_COOKIE['lang'];
}

// Procesăm traducerile pentru limba curentă
$processed_translations = array();
foreach ($lng_credit_page['w'] as $key => $translations) {
    $processed_translations[$key] = $translations[$current_lang];
}

// Creăm structura finală pentru merge
$lng_credit_final = array(
    'w' => $processed_translations
);

// Combinăm cu $lng existent pentru a păstra traducerile globale
$lng = array_merge($lng, $lng_credit_final);

?>
