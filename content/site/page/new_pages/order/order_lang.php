<?php defined('_DOIT') or die('Restricted access');

$lng_order_page = [
    'ru' => [
        'hero_label' => 'Контактные данные',
        'hero_title' => 'Найдите свой идеальный автомобиль с нами',
        'hero_subtitle' => 'Заполните форму, и мы свяжемся с вами с подходящим предложением.',
        'contact_label' => 'Свяжитесь с нами',
        'slider_title' => 'Реализованные проекты',
        'slider_subtitle' => 'Более 1500 автомобилей, импортированных под заказ из Европы и Кореи',
        'year_label' => 'Год выпуска',
        'mileage_label' => 'Пробег',
        'view_button' => 'ПОД ЗАКАЗ',
        'request_label' => 'Запрос',
        'offer_label' => 'Что рекомендовали',
        'choice_label' => 'Что выбрал',
    ],
    'ro' => [
        'hero_label' => 'Date de contact',
        'hero_title' => 'Găsește-ți mașina ideală<br>cu noi',
        'hero_subtitle' => 'Completează formularul iar noi revenin cu oferta potrivită pentru tine.',
        'contact_label' => 'Contactați-ne',
        'slider_title' => 'Proiecte realizate',
        'slider_subtitle' => 'Peste 1500 de automobile, importate la comandă din Europa și Coreea',
        'year_label' => 'Anul lansării',
        'mileage_label' => 'Kilometraj',
        'view_button' => 'SUB COMANDĂ',
        'request_label' => 'Cerere',
        'offer_label' => 'Ce am recomandat',
        'choice_label' => 'Ce a ales',
    ],
    'en' => [
        'hero_label' => 'Contact details',
        'hero_title' => 'Find your ideal car with us',
        'hero_subtitle' => 'Fill out the form and we will get back to you with the right offer.',
        'contact_label' => 'Contact us',
        'slider_title' => 'Completed projects',
        'slider_subtitle' => 'More than 1500 cars, imported to order from Europe and Korea',
        'year_label' => 'Year of release',
        'mileage_label' => 'Mileage',
        'view_button' => 'ORDER NOW',
        'request_label' => 'Request',
        'offer_label' => 'What we recommended',
        'choice_label' => 'What they chose',
    ],
];

// Car descriptions for each car ID
$car_descriptions = [
    11760 => [ // Renault Megane
        'ru' => [
            'request' => 'надёжный автомобиль для города и редких загородных поездок.',
            'offer' => 'Мы предложили комплектацию VW Golf, Mercedes GLC и Audi A3 — всё с пробегом до 150 000 км, в хорошем состоянии.',
            'choice' => 'Renault Megane — клиенту понравился строгий дизайн, роскошь и удобство в управлении.'
        ],
        'ro' => [
            'request' => 'automobil fiabil pentru oraș și călătorii rare în afara orașului.',
            'offer' => 'Am propus configurația VW Golf, Mercedes GLC și Audi A3 — toate cu kilometraj până la 150 000 km, în stare bună.',
            'choice' => 'Renault Megane — clientului i-a plăcut designul strict, luxul și confortul în conducere.'
        ],
        'en' => [
            'request' => 'reliable car for city and occasional country trips.',
            'offer' => 'We offered VW Golf, Mercedes GLC and Audi A3 configurations — all with mileage up to 150,000 km, in good condition.',
            'choice' => 'Renault Megane — the client liked the strict design, luxury and driving comfort.'
        ]
    ],
    11759 => [ // BMW X5
        'ru' => [
            'request' => 'просторный внедорожник для семьи с высоким уровнем комфорта.',
            'offer' => 'Мы предложили BMW X5, Audi Q7 и Mercedes GLE — все с полным приводом и богатой комплектацией.',
            'choice' => 'BMW X5 — клиент выбрал за отличное сочетание мощности, комфорта и престижа.'
        ],
        'ro' => [
            'request' => 'SUV spațios pentru familie cu nivel înalt de confort.',
            'offer' => 'Am propus BMW X5, Audi Q7 și Mercedes GLE — toate cu tracțiune integrală și dotări bogate.',
            'choice' => 'BMW X5 — clientul a ales pentru combinația excelentă de putere, confort și prestigiu.'
        ],
        'en' => [
            'request' => 'spacious SUV for family with high comfort level.',
            'offer' => 'We offered BMW X5, Audi Q7 and Mercedes GLE — all with all-wheel drive and rich equipment.',
            'choice' => 'BMW X5 — client chose for excellent combination of power, comfort and prestige.'
        ]
    ],
    11758 => [ // BMW X3
        'ru' => [
            'request' => 'компактный кроссовер с динамичным характером и премиальным качеством.',
            'offer' => 'Мы предложили BMW X3, Audi Q5 и Mercedes GLC — все с современными технологиями безопасности.',
            'choice' => 'BMW X3 — клиент оценил спортивную управляемость и элегантный дизайн.'
        ],
        'ro' => [
            'request' => 'crossover compact cu caracter dinamic și calitate premium.',
            'offer' => 'Am propus BMW X3, Audi Q5 și Mercedes GLC — toate cu tehnologii moderne de siguranță.',
            'choice' => 'BMW X3 — clientul a apreciat manevrabilitatea sportivă și designul elegant.'
        ],
        'en' => [
            'request' => 'compact crossover with dynamic character and premium quality.',
            'offer' => 'We offered BMW X3, Audi Q5 and Mercedes GLC — all with modern safety technologies.',
            'choice' => 'BMW X3 — client appreciated sporty handling and elegant design.'
        ]
    ],
    11756 => [ // Mercedes-Benz GLE
        'ru' => [
            'request' => 'роскошный внедорожник с передовыми технологиями и максимальным комфортом.',
            'offer' => 'Мы предложили Mercedes GLE, BMW X5 и Audi Q7 — все с панорамной крышей и кожаным салоном.',
            'choice' => 'Mercedes-Benz GLE — клиент выбрал за непревзойденный комфорт и статус.'
        ],
        'ro' => [
            'request' => 'SUV luxos cu tehnologii avansate și confort maxim.',
            'offer' => 'Am propus Mercedes GLE, BMW X5 și Audi Q7 — toate cu acoperiș panoramic și interior din piele.',
            'choice' => 'Mercedes-Benz GLE — clientul a ales pentru confortul de neegalat și statut.'
        ],
        'en' => [
            'request' => 'luxury SUV with advanced technologies and maximum comfort.',
            'offer' => 'We offered Mercedes GLE, BMW X5 and Audi Q7 — all with panoramic roof and leather interior.',
            'choice' => 'Mercedes-Benz GLE — client chose for unmatched comfort and status.'
        ]
    ],
    11753 => [ // Toyota RAV4
        'ru' => [
            'request' => 'надёжный кроссовер для активного образа жизни с низкими расходами на обслуживание.',
            'offer' => 'Мы предложили Toyota RAV4, Honda CR-V и Mazda CX-5 — все с полным приводом и экономичными двигателями.',
            'choice' => 'Toyota RAV4 — клиент выбрал за легендарную надёжность и практичность.'
        ],
        'ro' => [
            'request' => 'crossover fiabil pentru stil de viață activ cu costuri reduse de întreținere.',
            'offer' => 'Am propus Toyota RAV4, Honda CR-V și Mazda CX-5 — toate cu tracțiune integrală și motoare economice.',
            'choice' => 'Toyota RAV4 — clientul a ales pentru fiabilitatea legendară și practicitate.'
        ],
        'en' => [
            'request' => 'reliable crossover for active lifestyle with low maintenance costs.',
            'offer' => 'We offered Toyota RAV4, Honda CR-V and Mazda CX-5 — all with all-wheel drive and economical engines.',
            'choice' => 'Toyota RAV4 — client chose for legendary reliability and practicality.'
        ]
    ],
    11747 => [ // Volvo XC90
        'ru' => [
            'request' => 'семейный автомобиль с высочайшим уровнем безопасности и комфорта для дальних поездок.',
            'offer' => 'Мы предложили Volvo XC90, Audi Q7 и BMW X5 — все с семью местами и передовыми системами безопасности.',
            'choice' => 'Volvo XC90 — клиент оценил скандинавский дизайн и непревзойденную безопасность.'
        ],
        'ro' => [
            'request' => 'automobil de familie cu cel mai înalt nivel de siguranță și confort pentru călătorii lungi.',
            'offer' => 'Am propus Volvo XC90, Audi Q7 și BMW X5 — toate cu șapte locuri și sisteme avansate de siguranță.',
            'choice' => 'Volvo XC90 — clientul a apreciat designul scandinav și siguranța de neegalat.'
        ],
        'en' => [
            'request' => 'family car with highest level of safety and comfort for long trips.',
            'offer' => 'We offered Volvo XC90, Audi Q7 and BMW X5 — all with seven seats and advanced safety systems.',
            'choice' => 'Volvo XC90 — client appreciated Scandinavian design and unmatched safety.'
        ]
    ],
    11744 => [ // Volkswagen Transporter
        'ru' => [
            'request' => 'вместительный фургон для бизнеса с возможностью трансформации в пассажирский вариант.',
            'offer' => 'Мы предложили VW Transporter, Mercedes Vito и Ford Transit — все с дизельными двигателями и большой грузоподъёмностью.',
            'choice' => 'Volkswagen Transporter — клиент выбрал за универсальность и немецкое качество.'
        ],
        'ro' => [
            'request' => 'furgon spațios pentru afaceri cu posibilitate de transformare în variantă de pasageri.',
            'offer' => 'Am propus VW Transporter, Mercedes Vito și Ford Transit — toate cu motoare diesel și capacitate mare de încărcare.',
            'choice' => 'Volkswagen Transporter — clientul a ales pentru versatilitate și calitate germană.'
        ],
        'en' => [
            'request' => 'spacious van for business with possibility to transform into passenger version.',
            'offer' => 'We offered VW Transporter, Mercedes Vito and Ford Transit — all with diesel engines and large load capacity.',
            'choice' => 'Volkswagen Transporter — client chose for versatility and German quality.'
        ]
    ],
    11705 => [ // Audi Q7
        'ru' => [
            'request' => 'премиальный внедорожник с просторным салоном и современными технологиями.',
            'offer' => 'Мы предложили Audi Q7, BMW X5 и Mercedes GLE — все с кожаным салоном и адаптивной подвеской.',
            'choice' => 'Audi Q7 — клиент выбрал за сочетание роскоши, технологий и вместительности.'
        ],
        'ro' => [
            'request' => 'SUV premium cu interior spațios și tehnologii moderne.',
            'offer' => 'Am propus Audi Q7, BMW X5 și Mercedes GLE — toate cu interior din piele și suspensie adaptivă.',
            'choice' => 'Audi Q7 — clientul a ales pentru combinația de lux, tehnologii și spațiu.'
        ],
        'en' => [
            'request' => 'premium SUV with spacious interior and modern technologies.',
            'offer' => 'We offered Audi Q7, BMW X5 and Mercedes GLE — all with leather interior and adaptive suspension.',
            'choice' => 'Audi Q7 — client chose for combination of luxury, technology and space.'
        ]
    ]
];
