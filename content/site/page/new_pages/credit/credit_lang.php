<?php defined( '_DOIT' ) or die( 'Restricted access' ); ?>

<?php

// Create temporary array with credit page translations
$lng_credit_page = array(
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
        'calc_suma' => array(
            'ro' => 'Suma creditului',
            'ru' => 'Сумма кредита',
            'en' => 'Loan amount'
        ),
        'calc_avans' => array(
            'ro' => 'Avansul',
            'ru' => 'Первоначальный взнос',
            'en' => 'Down payment'
        ),
        'calc_perioada' => array(
            'ro' => 'Perioada de rambursare',
            'ru' => 'Период погашения',
            'en' => 'Repayment period'
        ),
        'calc_luni' => array(
            'ro' => 'luni',
            'ru' => 'месяцев',
            'en' => 'months'
        ),
        'calc_rata_lunara' => array(
            'ro' => 'Rata lunară',
            'ru' => 'Ежемесячный платёж',
            'en' => 'Monthly payment'
        ),
        'calc_dobanda' => array(
            'ro' => 'Dobânda anuală',
            'ru' => 'Годовая процентная ставка',
            'en' => 'Annual interest rate'
        ),
        'calc_title_plata' => array(
            'ro' => 'de la',
            'ru' => 'от',
            'en' => 'from'
        ),
        'calc_title_plata2' => array(
            'ro' => 'până la',
            'ru' => 'до',
            'en' => 'to'
        ),

        // Credit Categories Section
        // Personal Credit
        'personal_credit_title' => array(
            'ro' => 'Creditul personal pentru autovehicule',
            'ru' => 'Личный автокредит',
            'en' => 'Personal Auto Credit'
        ),
        'personal_credit_desc' => array(
            'ro' => 'Obțineți mașina visurilor dvs. cu condiții avantajoase',
            'ru' => 'Получите автомобиль своей мечты на выгодных условиях',
            'en' => 'Get your dream car with favorable conditions'
        ),
        
        // Personal Credit Features
        'personal_feature1_title' => array(
            'ro' => 'Minimum 18 ani și experiență de conducere',
            'ru' => 'Минимум 18 лет и водительский опыт',
            'en' => 'Minimum 18 years old and driving experience'
        ),
        'personal_feature1_desc' => array(
            'ro' => 'Pentru a obține un credit auto, trebuie să aveți cel puțin 18 ani și să dețineți permis de conducere valid. Experiența de conducere demonstrează responsabilitatea dvs.',
            'ru' => 'Для получения автокредита необходимо иметь минимум 18 лет и действующие водительские права. Опыт вождения демонстрирует вашу ответственность.',
            'en' => 'To get an auto loan, you must be at least 18 years old and have a valid driver\'s license. Driving experience demonstrates your responsibility.'
        ),
        'personal_feature2_title' => array(
            'ro' => 'CASCO nu este obligatoriu',
            'ru' => 'КАСКО не обязательно',
            'en' => 'CASCO is not mandatory'
        ),
        'personal_feature2_desc' => array(
            'ro' => 'CASCO se încheie doar la dorința dvs. Prețuim libertatea de alegere.',
            'ru' => 'КАСКО оформляется только по вашему желанию. Мы ценим свободу выбора.',
            'en' => 'CASCO is arranged only at your request. We value freedom of choice.'
        ),
        'personal_feature3_title' => array(
            'ro' => 'Orice an de fabricație a automobilului',
            'ru' => 'Любой год выпуска автомобиля',
            'en' => 'Any year of car manufacture'
        ),
        'personal_feature3_desc' => array(
            'ro' => 'Finanțăm achiziția automobilului indiferent de anul de fabricație. Condiția principală – starea tehnică bună.',
            'ru' => 'Финансируем покупку автомобиля независимо от года выпуска. Главное условие – хорошее техническое состояние.',
            'en' => 'We finance the purchase of a car regardless of the year of manufacture. The main condition is good technical condition.'
        ),
        'personal_feature4_title' => array(
            'ro' => 'Finanțare până la 100% din valoare',
            'ru' => 'Финансирование до 100% стоимости',
            'en' => 'Financing up to 100% of value'
        ),
        'personal_feature4_desc' => array(
            'ro' => 'Puteți cumpăra un automobil complet în credit, fără avans obligatoriu.',
            'ru' => 'Вы можете купить автомобиль полностью в кредит, без обязательного первоначального взноса.',
            'en' => 'You can buy a car completely on credit, without a mandatory down payment.'
        ),
        'personal_feature5_title' => array(
            'ro' => 'Aprobare rapidă – până la 1 oră',
            'ru' => 'Быстрое одобрение – до 1 часа',
            'en' => 'Fast approval – up to 1 hour'
        ),
        'personal_feature5_desc' => array(
            'ro' => 'Prețuim timpul dvs. Decizia preliminară se ia prompt.',
            'ru' => 'Мы ценим ваше время. Предварительное решение принимается оперативно.',
            'en' => 'We value your time. The preliminary decision is made promptly.'
        ),
        'personal_feature6_title' => array(
            'ro' => 'Rambursare anticipată fără penalități',
            'ru' => 'Досрочное погашение без штрафов',
            'en' => 'Early repayment without penalties'
        ),
        'personal_feature6_desc' => array(
            'ro' => 'Închideți creditul mai devreme fără costuri suplimentare.',
            'ru' => 'Закрывайте кредит раньше без дополнительных затрат.',
            'en' => 'Close the loan early without additional costs.'
        ),
        'personal_feature7_title' => array(
            'ro' => 'Posibilitatea de a alege perioada de rambursare',
            'ru' => 'Возможность выбора срока погашения',
            'en' => 'Ability to choose repayment period'
        ),
        'personal_feature7_desc' => array(
            'ro' => 'Alegeți perioada de rambursare care vi se potrivește cel mai bine, de la 12 la 84 de luni.',
            'ru' => 'Выберите срок погашения, который подходит вам лучше всего, от 12 до 84 месяцев.',
            'en' => 'Choose the repayment period that suits you best, from 12 to 84 months.'
        ),

        // Business Credit
        'business_credit_title' => array(
            'ro' => 'Creditul pentru business',
            'ru' => 'Кредит для бизнеса',
            'en' => 'Business Credit'
        ),
        'business_credit_desc' => array(
            'ro' => 'Soluții de finanțare pentru întreprinderile care doresc să își extindă parcul auto',
            'ru' => 'Решения финансирования для предприятий, желающих расширить автопарк',
            'en' => 'Financing solutions for businesses looking to expand their vehicle fleet'
        ),
        
        // Business Credit Features
        'business_feature1_title' => array(
            'ro' => 'Finanțare pentru persoane juridice',
            'ru' => 'Финансирование для юридических лиц',
            'en' => 'Financing for legal entities'
        ),
        'business_feature1_desc' => array(
            'ro' => 'Oferim servicii de creditare specializate pentru companii și întreprinderi. Procesul de aprobare este adaptat nevoilor business-ului dvs.',
            'ru' => 'Предлагаем специализированные кредитные услуги для компаний и предприятий. Процесс одобрения адаптирован к потребностям вашего бизнеса.',
            'en' => 'We offer specialized lending services for companies and enterprises. The approval process is tailored to your business needs.'
        ),
        'business_feature2_title' => array(
            'ro' => 'Credite pentru parcul auto',
            'ru' => 'Кредиты для автопарка',
            'en' => 'Fleet financing'
        ),
        'business_feature2_desc' => array(
            'ro' => 'Finanțarea achiziției mai multor autovehicule pentru nevoile companiei dvs.',
            'ru' => 'Финансирование покупки нескольких автомобилей для нужд вашей компании.',
            'en' => 'Financing the purchase of multiple vehicles for your company needs.'
        ),
        'business_feature3_title' => array(
            'ro' => 'Condiții preferențiale',
            'ru' => 'Льготные условия',
            'en' => 'Preferential terms'
        ),
        'business_feature3_desc' => array(
            'ro' => 'Clienții corporativi beneficiază de condiții speciale și rate reduse.',
            'ru' => 'Корпоративные клиенты получают специальные условия и сниженные ставки.',
            'en' => 'Corporate clients benefit from special conditions and reduced rates.'
        ),
        'business_feature4_title' => array(
            'ro' => 'Flexibilitate în rambursare',
            'ru' => 'Гибкость в погашении',
            'en' => 'Repayment flexibility'
        ),
        'business_feature4_desc' => array(
            'ro' => 'Adaptăm graficul de plăți la fluxul de numerar al companiei.',
            'ru' => 'Адаптируем график платежей к денежному потоку компании.',
            'en' => 'We adapt the payment schedule to your company\'s cash flow.'
        ),
        'business_feature5_title' => array(
            'ro' => 'Consultanță specializată',
            'ru' => 'Специализированная консультация',
            'en' => 'Specialized consultation'
        ),
        'business_feature5_desc' => array(
            'ro' => 'Echipa noastră de experți vă va ajuta să alegeți cea mai bună soluție.',
            'ru' => 'Наша команда экспертов поможет вам выбрать лучшее решение.',
            'en' => 'Our expert team will help you choose the best solution.'
        ),
        'business_feature6_title' => array(
            'ro' => 'Proces simplificat de aprobare',
            'ru' => 'Упрощенный процесс одобрения',
            'en' => 'Simplified approval process'
        ),
        'business_feature6_desc' => array(
            'ro' => 'Documentația redusă și proceduri accelerate pentru business-uri.',
            'ru' => 'Сокращенная документация и ускоренные процедуры для бизнеса.',
            'en' => 'Reduced documentation and accelerated procedures for businesses.'
        ),

        // Leasing
        'leasing_title' => array(
            'ro' => 'Leasing auto',
            'ru' => 'Автолизинг',
            'en' => 'Car Leasing'
        ),
        'leasing_desc' => array(
            'ro' => 'Soluția ideală pentru a conduce un automobil nou fără să îl cumpărați',
            'ru' => 'Идеальное решение для вождения нового автомобиля без его покупки',
            'en' => 'The perfect solution for driving a new car without buying it'
        ),
        
        // Leasing Features
        'leasing_feature1_title' => array(
            'ro' => 'Avans redus',
            'ru' => 'Низкий первоначальный взнос',
            'en' => 'Low down payment'
        ),
        'leasing_feature1_desc' => array(
            'ro' => 'Începeți cu un avans de doar 10-20% din valoarea mașinii.',
            'ru' => 'Начните с первоначальным взносом всего 10-20% от стоимости автомобиля.',
            'en' => 'Start with a down payment of only 10-20% of the car value.'
        ),
        'leasing_feature2_title' => array(
            'ro' => 'Rate lunare mici',
            'ru' => 'Небольшие ежемесячные платежи',
            'en' => 'Small monthly payments'
        ),
        'leasing_feature2_desc' => array(
            'ro' => 'Plățile lunare sunt mai mici comparativ cu creditul clasic.',
            'ru' => 'Ежемесячные платежи меньше по сравнению с классическим кредитом.',
            'en' => 'Monthly payments are smaller compared to traditional credit.'
        ),
        'leasing_feature3_title' => array(
            'ro' => 'Mașină nouă la fiecare 2-3 ani',
            'ru' => 'Новый автомобиль каждые 2-3 года',
            'en' => 'New car every 2-3 years'
        ),
        'leasing_feature3_desc' => array(
            'ro' => 'Schimbați mașina cu una nouă la sfârșitul contractului.',
            'ru' => 'Меняйте автомобиль на новый в конце контракта.',
            'en' => 'Change to a new car at the end of the contract.'
        ),
        'leasing_feature4_title' => array(
            'ro' => 'Serviciu complet inclus',
            'ru' => 'Полный сервис включен',
            'en' => 'Full service included'
        ),
        'leasing_feature4_desc' => array(
            'ro' => 'Întreținerea, asigurarea și alte servicii pot fi incluse în contract.',
            'ru' => 'Обслуживание, страхование и другие услуги могут быть включены в контракт.',
            'en' => 'Maintenance, insurance and other services can be included in the contract.'
        ),

        // Partners Section
        'partners_title' => array(
            'ro' => 'Partenerii noștri de încredere',
            'ru' => 'Наши надёжные партнёры',
            'en' => 'Our trusted partners'
        ),
        'partners_subtitle' => array(
            'ro' => 'Colaborăm cu instituții financiare de top pentru a vă oferi cele mai bune condiții',
            'ru' => 'Сотрудничаем с ведущими финансовыми учреждениями, чтобы предложить вам лучшие условия',
            'en' => 'We work with top financial institutions to offer you the best conditions'
        ),
        'submit_application' => array(
            'ro' => 'Trimite cererea',
            'ru' => 'Отправить заявку',
            'en' => 'Submit application'
        ),
        
        // Partner descriptions
        'microinvest_desc' => array(
            'ro' => 'Soluții de creditare flexibile și avantajoase pentru achiziția de autovehicule',
            'ru' => 'Гибкие и выгодные кредитные решения для покупки автомобилей',
            'en' => 'Flexible and advantageous credit solutions for vehicle purchases'
        ),
        'maib_leasing_desc' => array(
            'ro' => 'Servicii de leasing profesionale cu condiții competitive',
            'ru' => 'Профессиональные лизинговые услуги с конкурентными условиями',
            'en' => 'Professional leasing services with competitive conditions'
        ),
        'bt_leasing_desc' => array(
            'ro' => 'Experiență vastă în finanțarea auto și leasing',
            'ru' => 'Большой опыт в автофинансировании и лизинге',
            'en' => 'Extensive experience in auto financing and leasing'
        ),
        'primero_desc' => array(
            'ro' => 'Partener de încredere pentru credite auto rapide și sigure',
            'ru' => 'Надёжный партнёр для быстрых и безопасных автокредитов',
            'en' => 'Trusted partner for fast and secure auto loans'
        ),
        'victoriabank_desc' => array(
            'ro' => 'Bancă cu tradiție, oferind soluții financiare moderne',
            'ru' => 'Банк с традициями, предлагающий современные финансовые решения',
            'en' => 'Traditional bank offering modern financial solutions'
        )
    )
);

// Determine current language from cookie or default to Romanian
$current_lang = 'ro';
if (isset($_COOKIE['lang']) && in_array($_COOKIE['lang'], array('ro', 'ru', 'en'))) {
    $current_lang = $_COOKIE['lang'];
}

// Initialize global $lng array
if (!isset($lng)) {
    $lng = array();
}
if (!isset($lng['w'])) {
    $lng['w'] = array();
}

// Merge credit page translations with existing translations
$lng['w'] = array_merge($lng['w'], $lng_credit_page['w']);

// Helper function to safely get translation
function get_translation($key, $current_lang, $lng) {
    if (!isset($lng['w'][$key])) {
        return '[MISSING: ' . $key . ']';
    }
    if (!is_array($lng['w'][$key])) {
        return '[INVALID: ' . $key . ']';
    }
    if (!isset($lng['w'][$key][$current_lang])) {
        return '[MISSING LANG: ' . $key . '[' . $current_lang . ']]';
    }
    return $lng['w'][$key][$current_lang];
}

?>
