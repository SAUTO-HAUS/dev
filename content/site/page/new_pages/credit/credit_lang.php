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
            'ro' => 'Program flexibil de plăți',
            'ru' => 'Гибкий график платежей',
            'en' => 'Flexible payment schedule'
        ),
        'personal_feature7_desc' => array(
            'ro' => 'Posibilitatea de a adapta plățile la rambursarea parțială anticipată.',
            'ru' => 'Возможность адаптировать выплаты при частичном досрочном погашении.',
            'en' => 'Ability to adapt payments for partial early repayment.'
        ),
        
        // Business Credit
        'business_credit_title' => array(
            'ro' => 'Credit auto pentru afacerea dvs.',
            'ru' => 'Автокредит для вашего бизнеса',
            'en' => 'Auto credit for your business'
        ),
        'business_credit_desc' => array(
            'ro' => 'Dezvoltați afacerea cu transport de încredere',
            'ru' => 'Развивайте бизнес с надежным транспортом',
            'en' => 'Develop business with reliable transport'
        ),
        
        // Business Credit Features
        'business_feature1_title' => array(
            'ro' => 'Pe numele persoanei fizice sau juridice',
            'ru' => 'На имя физического или юридического лица',
            'en' => 'In the name of an individual or legal entity'
        ),
        'business_feature1_desc' => array(
            'ro' => 'Creditul este disponibil atât pentru persoanele fizice (inclusiv întreprinzătorii individuali), cât și pentru companii.',
            'ru' => 'Кредит доступен как физическим лицам (включая индивидуальных предпринимателей), так и компаниям.',
            'en' => 'Credit is available to both individuals (including sole proprietors) and companies.'
        ),
        'business_feature2_title' => array(
            'ro' => 'Modalitate convenabilă de obținere a fondurilor',
            'ru' => 'Удобный способ получения средств',
            'en' => 'Convenient way to obtain funds'
        ),
        'business_feature2_desc' => array(
            'ro' => 'Plata în numerar sau transfer direct în contul companiei dvs.',
            'ru' => 'Оплата наличными или перевод напрямую на счет вашей компании.',
            'en' => 'Cash payment or direct transfer to your company account.'
        ),
        'business_feature3_title' => array(
            'ro' => 'Finanțarea automobilelor comandate',
            'ru' => 'Финансирование заказанных автомобилей',
            'en' => 'Financing of ordered cars'
        ),
        'business_feature3_desc' => array(
            'ro' => 'Obțineți credit chiar dacă automobilul dorit este încă în drum spre Moldova.',
            'ru' => 'Получите кредит, даже если желаемый автомобиль ещё в пути в Молдову.',
            'en' => 'Get a loan even if the desired car is still on its way to Moldova.'
        ),
        'business_feature4_title' => array(
            'ro' => 'Sprijin pentru startup-uri',
            'ru' => 'Поддержка стартапов',
            'en' => 'Startup support'
        ),
        'business_feature4_desc' => array(
            'ro' => 'Procedură simplificată și condiții speciale pentru companiile tinere.',
            'ru' => 'Упрощённая процедура и специальные условия для молодых компаний.',
            'en' => 'Simplified procedure and special conditions for young companies.'
        ),
        'business_feature5_title' => array(
            'ro' => 'Fără restricții de ieșire din țară',
            'ru' => 'Без ограничений на выезд за границу',
            'en' => 'No restrictions on leaving the country'
        ),
        'business_feature5_desc' => array(
            'ro' => 'Automobilul dvs. – activul dvs. fără frontiere.',
            'ru' => 'Ваш автомобиль – ваш актив без границ.',
            'en' => 'Your car is your asset without borders.'
        ),
        'business_feature6_title' => array(
            'ro' => 'Refinanțarea creditului auto existent',
            'ru' => 'Рефинансирование существующего автокредита',
            'en' => 'Refinancing existing auto loan'
        ),
        'business_feature6_desc' => array(
            'ro' => 'Îmbunătățiți condițiile creditului actual.',
            'ru' => 'Улучшите условия текущего кредита.',
            'en' => 'Improve the terms of your current loan.'
        ),
        
        // Leasing
        'leasing_title' => array(
            'ro' => 'Leasing auto - flexibilitate și avantaj',
            'ru' => 'Автолизинг - гибкость и преимущество',
            'en' => 'Auto leasing - flexibility and advantage'
        ),
        'leasing_desc' => array(
            'ro' => 'Obțineți un automobil fără investiții inițiale mari',
            'ru' => 'Получите автомобиль без больших первоначальных вложений',
            'en' => 'Get a car without large initial investments'
        ),
        
        // Partners Section
        'partners_title' => array(
            'ro' => 'Partenerii noștri de încredere',
            'ru' => 'Наши надежные партнеры',
            'en' => 'Our Trusted Partners'
        ),
        'partners_subtitle' => array(
            'ro' => 'Colaborăm doar cu organizații financiare verificate și de încredere din Moldova',
            'ru' => 'Мы сотрудничаем только с проверенными и уважаемыми финансовыми организациями Молдовы',
            'en' => 'We work only with verified and trusted financial organizations in Moldova'
        ),
        'submit_application' => array(
            'ro' => 'Aplică acum',
            'ru' => 'Подать заявку',
            'en' => 'Apply Now'
        ),
        'microinvest_desc' => array(
            'ro' => 'Procedură simplă și rapidă',
            'ru' => 'Простая и быстрая процедура',
            'en' => 'Simple and fast procedure'
        ),
        'maib_leasing_desc' => array(
            'ro' => 'Expert recunoscut în domeniul leasingului',
            'ru' => 'Признанный эксперт в области лизинга',
            'en' => 'Recognized expert in leasing'
        ),
        'bt_leasing_desc' => array(
            'ro' => 'Standarde europene de fiabilitate',
            'ru' => 'Европейские стандарты надёжности',
            'en' => 'European reliability standards'
        ),
        'primero_desc' => array(
            'ro' => 'Abordări inovatoare',
            'ru' => 'Инновационные подходы',
            'en' => 'Innovative approaches'
        ),
        'victoriabank_desc' => array(
            'ro' => 'Una dintre cele mai mari bănci din Moldova',
            'ru' => 'Один из крупнейших банков Молдовы',
            'en' => 'One of the largest banks in Moldova'
        ),
        
        // Leasing Features
        'leasing_feature1_title' => array(
            'ro' => 'Gestionarea optimă a bugetului',
            'ru' => 'Оптимальное управление бюджетом',
            'en' => 'Optimal budget management'
        ),
        'leasing_feature1_desc' => array(
            'ro' => 'Folosiți automobilul fără achiziție imediată.',
            'ru' => 'Пользуйтесь автомобилем без немедленной покупки.',
            'en' => 'Use the car without immediate purchase.'
        ),
        'leasing_feature2_title' => array(
            'ro' => 'Finanțare până la 100% din valoarea automobilului',
            'ru' => 'Финансирование до 100% стоимости автомобиля',
            'en' => 'Financing up to 100% of the car value'
        ),
        'leasing_feature2_desc' => array(
            'ro' => 'Avansul nu este obligatoriu.',
            'ru' => 'Аванс не обязателен.',
            'en' => 'Down payment is not required.'
        ),
        'leasing_feature3_title' => array(
            'ro' => 'Condiții transparente și clare',
            'ru' => 'Прозрачные и понятные условия',
            'en' => 'Transparent and clear conditions'
        ),
        'leasing_feature3_desc' => array(
            'ro' => 'Fără comisioane ascunse.',
            'ru' => 'Без скрытых комиссий.',
            'en' => 'No hidden fees.'
        ),
        'leasing_feature4_title' => array(
            'ro' => 'Pentru toate categoriile de clienți',
            'ru' => 'Для всех категорий клиентов',
            'en' => 'For all categories of clients'
        ),
        'leasing_feature4_desc' => array(
            'ro' => 'Leasingul este disponibil pentru persoane fizice, II și companii.',
            'ru' => 'Лизинг доступен физическим лицам, ИП и компаниям.',
            'en' => 'Leasing is available for individuals, sole proprietors and companies.'
        ),
        'leasing_feature5_title' => array(
            'ro' => 'Perioada flexibilă a contractului',
            'ru' => 'Гибкий срок договора',
            'en' => 'Flexible contract term'
        ),
        'leasing_feature5_desc' => array(
            'ro' => 'De la 12 la 60 de luni.',
            'ru' => 'От 12 до 60 месяцев.',
            'en' => 'From 12 to 60 months.'
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

// Copy credit page translations to global $lng array
if (!isset($lng)) {
    $lng = array();
}
if (!isset($lng['w'])) {
    $lng['w'] = array();
}

// Merge credit page translations with existing translations
$lng['w'] = array_merge($lng['w'], $lng_credit_page['w']);

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
