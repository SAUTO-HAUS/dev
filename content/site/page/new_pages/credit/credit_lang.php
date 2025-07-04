<?php defined( '_DOIT' ) or die( 'Restricted access' ); ?>

<?php

// Cream array-ul temporar înainte de a-l combina cu $lng
$lng_credit_page = array(
    // Plasăm toate traducerile în subarray-ul 'w' pentru a fi compatibile cu structura site-ului
    'w' => array(
        // Hero Section
        'credit_title' => array(
            'ro' => 'Cumpărați automobilul cu ușurință Credit și Leasing în Moldova',
            'ru' => 'Покупайте автомобиль с лёгкостью Кредит и Лизинг в Молдове',
            'en' => 'Buy a car with ease Credit and Leasing in Moldova'
        ),
        
        // Calculator Section
        'calculator_title' => array(
            'ro' => 'Calculator Credit Auto',
            'ru' => 'Кредитный калькулятор',
            'en' => 'Car Loan Calculator'
        ),
        'loan_amount_eur' => array(
            'ro' => 'Suma creditului (EUR)',
            'ru' => 'Сумма кредита (EUR)',
            'en' => 'Loan amount (EUR)'
        ),
        'loan_term_months' => array(
            'ro' => 'Termenul (luni)',
            'ru' => 'Срок кредита (месяцев)',
            'en' => 'Term (months)'
        ),
        'monthly_payment_est' => array(
            'ro' => 'Rata lunară estimată',
            'ru' => 'Ежемесячный платеж',
            'en' => 'Estimated monthly payment'
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
