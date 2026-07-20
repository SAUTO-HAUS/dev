<?php defined( '_DOIT' ) or die( 'Restricted access' );

require_once(dirname(dirname(dirname(__FILE__))) . '/content/default/language.php');

$sa = array();
$sa['it']['img'] = 'https://www.sauto.md/media/images/site/sauto_new_logo_black.png';

// Check if this is a 404 page - set by detail pages before head.php is included
if (isset($GLOBALS['page_is_404']) && $GLOBALS['page_is_404'] === true) {
    http_response_code(404);
    echo '<meta http-equiv="Content-type" content="text/html; charset=UTF-8" />';
    echo '<meta name="description" content="Page not found. Error 404.">';
    echo '<meta name="keywords" content="Error, 404">';
    echo '<meta name="robots" content="noindex, nofollow">';
    echo '<meta name="viewport" content="width=device-width, initial-scale=1, minimum-scale=1, maximum-scale=1, user-scalable=no" id="mobile_viewport" />';
    echo '<title>404 - '.$lang_404.'</title>';
    return;
}

if ( isset($t_mp[1]) ){
    if ( in_array($t_mp[1], $lang_arr, true) ){ $zlng = $t_mp[1]; }
    else{ $zlng = 'ro'; }
}else{ $zlng = 'ro'; }

$current_lang = isset($_COOKIE['lang']) ? $_COOKIE['lang'] : $zlng;

$z2 = isset($t_mp[2]) ? $t_mp[2] : '';
$z3 = !isset($t_mp[3])?'':(in_array($z2, ['cars', 'ordercars', 'tyres'])?( toNumber($t_mp[3])>0?toNumber($t_mp[3]):$t_mp[3] ):$t_mp[3]);

// Current year for SEO freshness markers in titles/descriptions (e.g. "['.$cy.']").
// Auto-rolls over on Jan 1st — no need to update titles manually each year.
$cy = date('Y');

// =====================================================================
// TITLE LENGTH GUARD
// =====================================================================
// Product titles ("Brand Model Year, BodyType Trans Color — Price€")
// concatenate optional attributes and can exceed the SEO title limit.
//
// This builds the title and, if it is too long, drops optional attributes
// ONE BY ONE from the end of the $attrs array until it fits. Callers must
// order $attrs from highest to lowest SEO value (e.g. [bodyType, transmission,
// color]) so the least valuable attribute (color) is dropped first.
//
// Brand+Model+Year ($core) and the price are never dropped — only as a last
// resort (extremely long brand/model name) the price is dropped too.
function sauto_trim_title($core, $attrs, $price_suffix, $max = 60) {
    $build = function ($attr_list) use ($core, $price_suffix) {
        $t = $core;
        if (!empty($attr_list)) $t .= ', ' . implode(' ', $attr_list);
        return $t . $price_suffix;
    };
    // Drop optional attributes from the end until the title fits.
    while (!empty($attrs) && mb_strlen($build($attrs), 'UTF-8') > $max) {
        array_pop($attrs);
    }
    $full = $build($attrs);
    if (mb_strlen($full, 'UTF-8') <= $max) {
        return $full;
    }
    // Core alone is still too long (very long brand/model): drop the price too.
    return $core;
}

// =====================================================================
// ROBOTS DIRECTIVE
// =====================================================================
$zrbt = 'noindex, nofollow';

// Only allow indexing on the primary sauto.md domain in production
$current_host = strtolower($_SERVER['HTTP_HOST'] ?? '');
if ($current_host !== '') {
    $current_host = preg_replace('/:\d+$/', '', $current_host);
}

if (in_array($current_host, ['sauto.md', 'www.sauto.md'], true)) {
    $zrbt = 'index, follow';
}

// Pages with multiple filters or sorting will have canonical to the base URL.
// The /cars catalog is no longer noindex - it's the main SEO landing page.

$r = ['ttl'=>'', 'h1'=>'', 'dsc'=>'', 'kwd'=>''];

// =====================================================================
// DEFAULT META (fallback - overridden by specific pages below)
// =====================================================================
if ($current_lang == 'ru') {
    $sa['meta']['ttl'] = 'Авто бу и под заказ из Европы — Молдова';
    $sa['meta']['dsc'] = 'Sauto.md — продажа авто в Молдове: проверенные авто бу из Европы в наличии и под заказ. Кредит, лизинг, trade-in. Доставка по всей Молдове.';
    $sa['meta']['h1'] = 'Продажа авто в Молдове';
} elseif ($current_lang == 'en') {
    $sa['meta']['ttl'] = 'Used Cars & Cars on Order from Europe — Moldova';
    $sa['meta']['dsc'] = 'Sauto.md — car sales in Moldova: inspected used cars from Europe, in stock and on order. Credit, leasing, trade-in. Delivery across Moldova.';
    $sa['meta']['h1'] = 'Car Sales in Moldova';
} else {
    $sa['meta']['ttl'] = 'Auto rulate și la comandă din Europa — Moldova';
    $sa['meta']['dsc'] = 'Sauto.md — vânzare auto în Moldova: mașini rulate verificate din Europa, în stoc și la comandă. Credit, leasing, trade-in. Livrare în toată Moldova.';
    $sa['meta']['h1'] = 'Vânzare auto în Moldova';
}
$sa['meta']['kwd'] = 'auto moldova, masini moldova, auto rulate moldova, auto bu moldova, dealer auto moldova, salon auto moldova, sauto';

// =====================================================================
// HOME PAGE (/ro, /ru, /en)
// =====================================================================
if ($z2 === '' || $z2 === 'home') {
    switch ($current_lang) {
        case 'ru':
            $sa['meta']['ttl'] = 'Авто бу Корея, Европа, США — Молдова ['.$cy.']';
            $sa['meta']['dsc'] = 'Авто бу из Кореи, Европы и США, в наличии и под заказ. Проверенные, с гарантией. Кредит, лизинг, trade-in. Доставка по всей Молдове.';
            $sa['meta']['kwd'] = 'авто бу молдова, авто с пробегом молдова, купить машину в молдове, автосалон молдова, авто из кореи, авто из европы, авто из германии, авто из сша, продажа авто молдова, недорогие авто молдова, авто без пробега по рм, кредит авто молдова, лизинг авто, trade in молдова, бу авто молдова, авто в рассрочку';
            $sa['meta']['h1'] =  'Авто бу из Кореи, Европы и США в Молдове';
            break;
        case 'en':
            $sa['meta']['ttl'] = 'Used Cars from Korea, Europe, USA — Moldova ['.$cy.']';
            $sa['meta']['dsc'] = 'Used cars from Korea, Europe and USA, in stock and on order. Inspected, with warranty. Credit, leasing, trade-in. Delivery across Moldova.';
            $sa['meta']['kwd'] = 'used cars moldova, buy car moldova, car dealership moldova, cars from korea, european cars moldova, cars from germany, cars from usa, second hand cars moldova, cheap cars moldova, car loan moldova, car leasing moldova, trade in moldova, cars for sale moldova, auto sales moldova, car import moldova';
            $sa['meta']['h1'] =  'Used Cars from Korea, Europe and USA in Moldova';
            break;
        default: // ro
            $sa['meta']['ttl'] = 'Auto rulate Coreea, Europa, SUA în Moldova ['.$cy.']';
            $sa['meta']['dsc'] = 'Auto rulate din Coreea, Europa și SUA, în stoc și la comandă. Verificate, cu garanție. Credit, leasing, trade-in. Livrare în toată Moldova.';
            $sa['meta']['kwd'] = 'auto rulate moldova, masini rulate moldova, masini second hand moldova, auto bu moldova, vanzare auto moldova, cumpara masina moldova, salon auto moldova, dealer auto moldova, auto din coreea, auto din europa, auto din germania, auto din sua, auto fara parcurs prin rm, credit auto moldova, leasing auto moldova, trade in moldova, autoturisme rulate, pret masini moldova';
            $sa['meta']['h1'] =  'Auto rulate din Coreea, Europa și SUA în Moldova';
            break;
    }
}

// =====================================================================
// CARS - main catalog /ro/cars (no brand/model/id)
// =====================================================================
if ($z2 === 'cars' && $z3 === '' && !isset($q_mp[1])) {
    switch ($current_lang) {
        case 'ru':
            $sa['meta']['ttl'] = 'Каталог авто бу в Молдове ['.$cy.'] — Авто в наличии';
            $sa['meta']['dsc'] = 'Каталог авто бу из Европы в наличии. Проверенные, с гарантией. Кредит, лизинг, trade-in. Бесплатный тест-драйв в салоне.';
            $sa['meta']['kwd'] = 'каталог авто молдова, авто бу молдова, авто с пробегом молдова, купить машину в молдове, авто в наличии молдова, авто из европы, авто из германии, авто без пробега по рм, бу авто молдова, недорогие авто, продажа автомобилей молдова, автосалон молдова, авто в рассрочку молдова, машины из европы цены';
            $sa['meta']['h1'] = 'Каталог авто бу в наличии';
            break;
        case 'en':
            $sa['meta']['ttl'] = 'Used Cars Catalog in Moldova ['.$cy.'] — Cars in Stock';
            $sa['meta']['dsc'] = 'Used cars catalog from Europe, in stock. Inspected, with warranty. Credit, leasing, trade-in. Free test drive at our showroom.';
            $sa['meta']['kwd'] = 'car catalog moldova, used cars moldova, second hand cars moldova, buy car moldova, cars in stock moldova, european cars moldova, cars from germany, cheap cars moldova, car sales moldova, car dealership moldova, cars for sale moldova, no mileage in moldova';
            $sa['meta']['h1'] = 'Used Cars Catalog in Stock';
            break;
        default: // ro
            $sa['meta']['ttl'] = 'Catalog auto rulate în Moldova ['.$cy.'] — Mașini în stoc';
            $sa['meta']['dsc'] = 'Catalog auto rulate din Europa, în stoc. Verificate, cu garanție. Credit, leasing, trade-in. Test-drive gratuit la salon.';
            $sa['meta']['kwd'] = 'catalog auto moldova, auto rulate moldova, masini rulate moldova, masini second hand moldova, auto bu moldova, cumpara masina moldova, auto in stoc moldova, masini din europa, auto din germania, auto fara parcurs prin rm, vanzare auto moldova, automobile rulate, dealer auto moldova, masini ieftine moldova, pret auto moldova';
            $sa['meta']['h1'] = 'Catalog auto rulate în stoc';
            break;
    }
}

// =====================================================================
// ORDERCARS - on-order catalog /ro/ordercars
// =====================================================================
if ($z2 === 'ordercars' && $z3 === '' && !isset($q_mp[1])) {
    switch ($current_lang) {
        case 'ru':
            $sa['meta']['ttl'] = 'Авто под заказ Корея, Европа, США — Экономия до 45%';
            $sa['meta']['dsc'] = 'Авто под заказ из Кореи, Европы и США под ключ ['.$cy.']. Экономия до 45% vs салон. Подбор, проверка, доставка за 14 дней, растаможка. Гарантия. Доставка по всей Молдове.';
            $sa['meta']['kwd'] = 'авто под заказ молдова, заказать машину из европы, импорт авто молдова, авто из кореи, авто из германии под заказ, авто из бельгии, авто из америки молдова, привезти авто из европы, доставка авто молдова, растаможка авто, авто из европы цена, заказ авто из германии молдова';
            $sa['meta']['h1'] = 'Авто под заказ из Кореи, Европы и США в Молдову';
            break;
        case 'en':
            $sa['meta']['ttl'] = 'Cars on Order from Korea, Europe, USA — Save up to 45%';
            $sa['meta']['dsc'] = 'Cars on order from Korea, Europe and USA, turnkey ['.$cy.']. Save up to 45% vs showroom. Selection, inspection, 14-day delivery, customs. Warranty. Delivery across Moldova.';
            $sa['meta']['kwd'] = 'cars on order moldova, order car from europe, car import moldova, cars from korea, cars from germany on order, cars from belgium, cars from usa moldova, bring car from europe, car delivery moldova, customs clearance moldova';
            $sa['meta']['h1'] = 'Cars on Order from Korea, Europe and USA to Moldova';
            break;
        default: // ro
            $sa['meta']['ttl'] = 'Auto la comandă Coreea, Europa, SUA — Economie până la 45%';
            $sa['meta']['dsc'] = 'Auto la comandă din Coreea, Europa și SUA la cheie ['.$cy.']. Economie până la 45%. Verificare, livrare în 14 zile, vămuire. Garanție inclusă.';
            $sa['meta']['kwd'] = 'auto la comanda moldova, comanda masina din europa, import auto moldova, auto din coreea, auto din germania la comanda, auto din belgia, auto din sua moldova, aducem auto din europa, livrare auto moldova, vamuire auto moldova, auto la comanda din germania pret';
            $sa['meta']['h1'] = 'Auto la comandă din Coreea, Europa și SUA în Moldova';
            break;
    }
}

// =====================================================================
// CREDIT
// =====================================================================
if ($z2 === 'credit') {
    switch ($current_lang) {
        case 'ru':
            $sa['meta']['ttl'] = 'Автокредит и лизинг в Молдове ['.$cy.'] — Одобрение за 1 час';
            $sa['meta']['dsc'] = 'Автокредит и лизинг до 5 лет. Одобрение за 1 час по паспорту. Аванс от 10%, без скрытых комиссий. Авто в наличии. Бесплатная консультация эксперта.';
            $sa['meta']['kwd'] = 'автокредит молдова, кредит на авто молдова, кредит на машину молдова, финансирование авто, лизинг авто молдова, авто в рассрочку молдова, авто в кредит без первоначального взноса, рассрочка на авто, одобрение автокредита, кредит на бу авто';
            $sa['meta']['h1'] = 'Автокредит в Молдове';
            break;
        case 'en':
            $sa['meta']['ttl'] = 'Car Loan & Leasing in Moldova ['.$cy.'] — Approved in 1 Hour';
            $sa['meta']['dsc'] = 'Car loan and leasing up to 5 years. Approval in 1 hour with passport only. Down payment from 10%, no hidden fees. Cars in stock. Free expert consultation.';
            $sa['meta']['kwd'] = 'car loan moldova, auto financing moldova, car credit moldova, auto leasing moldova, vehicle financing, car loan no down payment, car installments moldova, used car loan moldova';
            $sa['meta']['h1'] = 'Car Loan in Moldova';
            break;
        default: // ro
            $sa['meta']['ttl'] = 'Credit auto și leasing Moldova ['.$cy.'] — Aprobare 1 oră';
            $sa['meta']['dsc'] = 'Credit auto și leasing până la 5 ani. Aprobare în 1 oră cu buletinul. Avans de la 10%, fără comisioane. Mașini în stoc.';
            $sa['meta']['kwd'] = 'credit auto moldova, finantare auto, credit masina moldova, leasing auto moldova, credit auto fara avans, rate auto moldova, dobanda credit auto, aprobare credit auto, credit auto rulate, auto in rate moldova, credit pentru masina';
            $sa['meta']['h1'] = 'Credit auto în Moldova';
            break;
    }
}

// =====================================================================
// ORDER (information about ordering cars)
// =====================================================================
if ($z2 === 'order') {
    switch ($current_lang) {
        case 'ru':
            $sa['meta']['ttl'] = 'Заказ авто из Кореи, Европы, США — Экономия до 45%';
            $sa['meta']['dsc'] = 'Доставка авто из Кореи, Европы и США ['.$cy.']. Экономия до 45% vs автосалон. Подбор, проверка VIN, доставка за 14 дней, полная растаможка. Гарантия включена.';
            $sa['meta']['kwd'] = 'автомобили под заказ молдова, заказ авто из европы, импорт автомобилей молдова, автомобили из кореи, автомобили из сша, авто из германии под заказ, авто из бельгии молдова, привезти авто из европы, доставка авто из европы, растаможка авто молдова, заказ авто из европы цена';
            $sa['meta']['h1'] = 'Автомобили под заказ из Кореи, Европы и США';
            break;
        case 'en':
            $sa['meta']['ttl'] = 'Order Cars from Korea, Europe, USA — Save up to 45%';
            $sa['meta']['dsc'] = 'Car delivery from Korea, Europe and USA ['.$cy.']. Save up to 45% vs dealership. Selection, VIN check, 14-day delivery, full customs. Warranty included.';
            $sa['meta']['kwd'] = 'order cars moldova, import cars moldova, cars from korea, cars from europe, cars from usa moldova, cars from germany on order, cars from belgium moldova, bring car from europe, car delivery from europe, customs clearance moldova';
            $sa['meta']['h1'] = 'Order Cars from Korea, Europe and USA';
            break;
        default: // ro
            $sa['meta']['ttl'] = 'Auto la comandă Coreea, Europa, SUA — Economie până la 45%';
            $sa['meta']['dsc'] = 'Livrare auto din Coreea, Europa și SUA ['.$cy.']. Economie până la 45% vs salon. Selecție, verificare VIN, livrare în 14 zile, vămuire completă. Garanție inclusă.';
            $sa['meta']['kwd'] = 'automobile la comanda moldova, import auto moldova, masini din coreea, masini din europa, masini din sua moldova, auto din germania la comanda, auto din belgia moldova, aducem auto din europa, livrare auto din europa, vamuire auto moldova';
            $sa['meta']['h1'] = 'Automobile la comandă din Coreea, Europa și SUA';
            break;
    }
}

// =====================================================================
// CALCULATOR
// =====================================================================
if ($z2 === 'calculator') {
    switch ($current_lang) {
        case 'ru':
            $sa['meta']['ttl'] = 'Калькулятор растаможки авто Молдова ['.$cy.'] — Бесплатно';
            $sa['meta']['dsc'] = 'Расчёт растаможки авто онлайн за 30 секунд. Акцизы '.$cy.', курс EUR от НБМ. Бесплатно, без регистрации. Проверено экспертами.';
            $sa['meta']['kwd'] = 'калькулятор растаможки молдова, растаможка авто молдова, акцизы авто молдова, таможенный калькулятор, расчет растаможки авто, стоимость растаможки молдова, таможенные пошлины авто, растаможка из европы молдова';
            $sa['meta']['h1'] = 'Калькулятор растаможки авто в Молдове';
            break;
        case 'en':
            $sa['meta']['ttl'] = 'Customs Calculator Moldova ['.$cy.'] — Free Online';
            $sa['meta']['dsc'] = 'Car customs calculation online in 30 seconds. '.$cy.' excise duties, EUR rate from NBM. Free, no registration. Expert-verified.';
            $sa['meta']['kwd'] = 'customs calculator moldova, car import tax moldova, excise duty calculator moldova, vehicle customs moldova, customs clearance cost moldova, import duties cars moldova';
            $sa['meta']['h1'] = 'Car Customs Calculator Moldova';
            break;
        default: // ro
            $sa['meta']['ttl'] = 'Calculator devamare (vămuire) auto Moldova ['.$cy.'] — Gratuit';
            $sa['meta']['dsc'] = 'Calculator vămuire auto online în 30 secunde. Accize '.$cy.', curs EUR de la BNM. Gratuit, fără înregistrare. Verificat de experți.';
            $sa['meta']['kwd'] = 'calculator vamuire auto moldova, calculator devamare auto moldova, devamare auto moldova, vamuire auto moldova, accize auto moldova, calculator taxe vamale, calcul vama auto, cost vamuire moldova, cost devamare auto, taxe vamale auto, vamuire auto din europa, devamare auto din europa';
            $sa['meta']['h1'] = 'Calculator vămuire auto Moldova';
            break;
    }
}

// =====================================================================
// TRADEIN
// =====================================================================
if ($z2 === 'tradein') {
    switch ($current_lang) {
        case 'ru':
            $sa['meta']['ttl'] = 'Trade-in авто в Молдове ['.$cy.'] — Обмен авто за 1 день';
            $sa['meta']['dsc'] = 'Trade-in авто за 1 день: бесплатная оценка по рынку, авто бу на выбор, доплата или возврат разницы. Без скрытых комиссий. Гарантия. Услуга по всей Молдове.';
            $sa['meta']['kwd'] = 'trade in авто молдова, обмен авто молдова, trade in молдова, обменять машину молдова, оценка авто молдова, покупка авто с доплатой, автосалон trade in молдова, быстрый обмен авто, сдать авто в трейд ин, выкуп авто молдова, сдать машину автосалону, обмен бу авто';
            $sa['meta']['h1'] = 'Trade-in авто в Молдове';
            break;
        case 'en':
            $sa['meta']['ttl'] = 'Trade-In Car in Moldova ['.$cy.'] — Car Swap in 1 Day';
            $sa['meta']['dsc'] = 'Car trade-in in 1 day: free market valuation, used cars to choose, top-up or refund difference. No hidden fees. Warranty included. Service across Moldova.';
            $sa['meta']['kwd'] = 'trade in moldova, car trade in moldova, swap my car moldova, car valuation moldova, exchange car with cash top-up, buy car with trade in, fast car exchange moldova, dealership trade in service, sell car to dealer moldova, used car exchange';
            $sa['meta']['h1'] = 'Trade-in Cars in Moldova';
            break;
        default: // ro
            $sa['meta']['ttl'] = 'Trade-in auto Moldova ['.$cy.'] — Schimb auto în 1 zi';
            $sa['meta']['dsc'] = 'Trade-in auto în 1 zi: evaluare gratuită la prețul pieței, auto la alegere, plată diferență. Garanție. Serviciu în Moldova.';
            $sa['meta']['kwd'] = 'trade in auto moldova, schimb auto moldova, evaluare masina moldova, trade in moldova, cumpara masina cu avans, schimb masina cu diferenta, autoturisme rulate moldova, servicii trade in moldova, schimb rapid auto, vinde masina la dealer, cumparare auto rulate moldova, schimb auto bu, evaluare auto gratuita moldova';
            $sa['meta']['h1'] = 'Trade-in auto în Moldova';
            break;
    }
}

// =====================================================================
// RENT (car rental - disabled in menu but URL accessible)
// =====================================================================
if ($z2 === 'rent') {
    switch ($current_lang) {
        case 'ru':
            $sa['meta']['ttl'] = 'Аренда авто в Молдове ['.$cy.'] — Прокат авто с доставкой';
            $sa['meta']['dsc'] = 'Аренда авто в Молдове. КАСКО и ОСАГО включены, поддержка 24/7. Без залога. Бесплатная доставка. Краткосрочно и долгосрочно. Доставка по всей Молдове.';
            $sa['meta']['kwd'] = 'аренда авто молдова, прокат автомобилей молдова, аренда машины молдова, car rental moldova, аренда авто на сутки, аренда авто без залога, прокат авто молдова, аренда авто посуточно, аренда авто аэропорт молдова, аренда авто долгосрочно молдова, дешевая аренда авто молдова';
            $sa['meta']['h1'] = 'Аренда авто в Молдове';
            break;
        case 'en':
            $sa['meta']['ttl'] = 'Car Rental in Moldova ['.$cy.'] — Rent a Car with Delivery';
            $sa['meta']['dsc'] = 'Car rental in Moldova. CASCO and RCA insurance included, 24/7 support. No deposit. Free delivery. Short and long-term. Delivery across Moldova.';
            $sa['meta']['kwd'] = 'car rental moldova, rent a car moldova, car hire moldova, daily car rental moldova, car rental airport moldova, no deposit car rental, long term car rental moldova, cheap car rental moldova';
            $sa['meta']['h1'] = 'Car Rental in Moldova';
            break;
        default: // ro
            $sa['meta']['ttl'] = 'Închiriere auto Moldova ['.$cy.'] — Rent a car cu livrare';
            $sa['meta']['dsc'] = 'Închiriere auto în Moldova. CASCO și RCA incluse, suport 24/7. Fără gaj. Livrare gratuită. Pe termen scurt și lung. Livrare în toată Moldova.';
            $sa['meta']['kwd'] = 'inchiriere auto moldova, rent a car moldova, inchiriere masina moldova, inchiriere auto pe zi, inchiriere auto fara gaj, inchiriere auto aeroport moldova, inchiriere auto pe termen lung moldova, inchiriere auto ieftin moldova, chirie auto moldova';
            $sa['meta']['h1'] = 'Închiriere auto Moldova';
            break;
    }
}

// =====================================================================
// CONTACTS
// =====================================================================
if ($z2 === 'contacts') {
    switch ($current_lang) {
        case 'ru':
            $sa['meta']['ttl'] = 'Контакты Sauto.md в Кишинёве — Телефоны, адрес, график';
            $sa['meta']['dsc'] = 'Sauto Кишинёв, Calea Moșilor 11 · Звонок за 1 минуту · Пн-Сб 9:00-19:00. Покупка авто, кредит, trade-in, страхование. Бесплатная парковка для клиентов.';
            $sa['meta']['kwd'] = 'sauto контакты, автосалон молдова адрес, sauto телефон, sauto.md контакты, как добраться до sauto, автосалон молдова, calea mosilor 11, автосалоны молдова, sauto адрес, дилер авто молдова контакты';
            $sa['meta']['h1'] = 'Контакты Sauto';
            break;
        case 'en':
            $sa['meta']['ttl'] = 'Contact Sauto.md in Chișinău — Phones, Address, Hours';
            $sa['meta']['dsc'] = 'Sauto Chișinău, Calea Moșilor 11 · Call back in 1 minute · Mon-Sat 9:00-19:00. Car sales, credit, trade-in, insurance. Free customer parking.';
            $sa['meta']['kwd'] = 'sauto contacts, car dealership moldova address, sauto phone, sauto.md contact, how to reach sauto, dealership moldova, calea mosilor 11, car dealers moldova, sauto address, auto dealer moldova contacts';
            $sa['meta']['h1'] = 'Sauto Contacts';
            break;
        default: // ro
            $sa['meta']['ttl'] = 'Contacte Sauto.md în Chișinău — Telefoane, adresă, program';
            $sa['meta']['dsc'] = 'Sauto Chișinău, Calea Moșilor 11 · Apel într-un minut · L-S 9:00-19:00. Vânzare auto, credit, trade-in, asigurări. Parcare gratuită pentru clienți.';
            $sa['meta']['kwd'] = 'sauto contacte, dealer auto moldova adresa, sauto telefon, sauto.md contact, cum sa ajungi la sauto, dealer auto moldova, calea mosilor 11, saloane auto moldova, sauto adresa, contacte dealer auto moldova, program sauto';
            $sa['meta']['h1'] = 'Contacte Sauto';
            break;
    }
}

// =====================================================================
// TYRES - main catalog
// =====================================================================
if ($z2 === 'tyres' && $z3 === '' && !isset($q_mp[1])) {
    switch ($current_lang) {
        case 'ru':
            $sa['meta']['ttl'] = 'Шины в Молдове ['.$cy.'] — Летние, зимние, всесезонные';
            $sa['meta']['dsc'] = 'Большой выбор шин: летние, зимние, всесезонные. Размеры R13-R22. Бесплатный шиномонтаж, гарантия 2 года. Доставка по Молдове.';
            $sa['meta']['kwd'] = 'шины молдова, купить шины молдова, автошины молдова, летние шины молдова, зимние шины молдова, всесезонные шины, шиномонтаж молдова, шины 205 55 r16, шины 225 45 r17, шины r17, шины r18, недорогие шины молдова, резина молдова, купить резину молдова, шины со склада молдова';
            $sa['meta']['h1'] = 'Каталог шин в Молдове';
            break;
        case 'en':
            $sa['meta']['ttl'] = 'Tyres in Moldova ['.$cy.'] — Summer, Winter, All-Season';
            $sa['meta']['dsc'] = 'Wide tyre selection in stock: summer, winter, all-season. Sizes R13-R22, popular brands. Free fitting, 2-year warranty. Delivery across Moldova.';
            $sa['meta']['kwd'] = 'tyres moldova, buy tyres moldova, car tyres moldova, summer tyres moldova, winter tyres moldova, all season tyres, tyre fitting moldova, tyres 205 55 r16, tyres 225 45 r17, tyres r17, tyres r18, cheap tyres moldova, tires moldova';
            $sa['meta']['h1'] = 'Tyre Catalog in Moldova';
            break;
        default: // ro
            $sa['meta']['ttl'] = 'Anvelope Moldova ['.$cy.'] — Vară, iarnă, all-season';
            $sa['meta']['dsc'] = 'Selecție mare de anvelope în stoc: vară, iarnă, all-season. Dimensiuni R13-R22, branduri populare. Montaj gratuit, garanție 2 ani. Livrare în toată Moldova.';
            $sa['meta']['kwd'] = 'anvelope moldova, cumpara anvelope moldova, cauciucuri moldova, anvelope vara moldova, anvelope iarna moldova, anvelope all season, vulcanizare moldova, anvelope 205 55 r16, anvelope 225 45 r17, anvelope r17, anvelope r18, anvelope ieftine moldova, anvelope auto moldova, anvelope din stoc moldova';
            $sa['meta']['h1'] = 'Catalog anvelope Moldova';
            break;
    }
}

// =====================================================================
// SERVICES - main catalog /ro/services
// =====================================================================
if ($z2 === 'services' && !isset($t_mp[3])) {
    switch ($current_lang) {
        case 'ru':
            $sa['meta']['ttl'] = 'Услуги Sauto в Молдове ['.$cy.'] — Trade-in, страховка';
            $sa['meta']['dsc'] = 'Услуги: продажа авто бу, Trade-in за 1 день, оценка, тест-драйв, КАСКО/ОСАГО, заказ из Европы. Сервис по всей Молдове.';
            $sa['meta']['kwd'] = 'услуги автосалона молдова, услуги sauto, trade in молдова, оценка авто молдова, страхование авто молдова, тест драйв молдова, заказ авто из европы, выкуп авто молдова, осаго каско молдова, услуги дилера авто';
            $sa['meta']['h1'] = 'Услуги Sauto в Молдове';
            break;
        case 'en':
            $sa['meta']['ttl'] = 'Sauto Services in Moldova ['.$cy.'] — Trade-in, Insurance';
            $sa['meta']['dsc'] = 'Full service range: used car sales, 1-day Trade-in, free valuation, test drive, CASCO/RCA, order from Europe. Professional service across Moldova.';
            $sa['meta']['kwd'] = 'dealership services moldova, sauto services moldova, trade in moldova, car valuation moldova, car insurance moldova, test drive moldova, car order europe, car buy back moldova, rca casco moldova, auto dealer services';
            $sa['meta']['h1'] = 'Sauto Services in Moldova';
            break;
        default: // ro
            $sa['meta']['ttl'] = 'Servicii Sauto Moldova ['.$cy.'] — Trade-in, asigurări';
            $sa['meta']['dsc'] = 'Gamă completă: vânzare auto rulate, Trade-in în 1 zi, evaluare gratuită, test-drive, CASCO/RCA, comandă din Europa. Servicii profesioniste în toată Moldova.';
            $sa['meta']['kwd'] = 'servicii dealer auto moldova, sauto servicii moldova, trade in moldova, evaluare auto moldova, asigurari auto moldova, test drive moldova, comanda auto din europa, vinde masina moldova, rca casco moldova, servicii salon auto';
            $sa['meta']['h1'] = 'Servicii Sauto în Moldova';
            break;
    }
}

// =====================================================================
// SERVICES SUBPAGES (/ro/services/sale, /ro/services/insurance, etc.)
// =====================================================================
if ($z2 === 'services' && isset($t_mp[3]) && !empty($t_mp[3])) {
    $service_slug = $t_mp[3];
    $service_titles = [
        'ro' => [
            'sale'           => ['ttl' => 'Vinde mașina rapid în Chișinău ['.$cy.'] — Plată pe loc', 'dsc' => 'Vinde-ți mașina către Sauto în 1 oră. Evaluare gratuită, plată pe loc, fără comisioane ascunse. Cel mai bun preț pe piață, garantat. Cumpărăm orice marcă.', 'h1' => 'Vinde-ți mașina către Sauto'],
            'estimation'     => ['ttl' => 'Evaluare auto gratuită Chișinău ['.$cy.'] — În 15 min', 'dsc' => 'Evaluare gratuită a mașinii în 15 minute. Estimare exactă la prețul pieței, raport detaliat, consultanță expert. 100% fără obligații. Programare online.', 'h1' => 'Evaluare auto'],
            'tradein'        => ['ttl' => 'Trade-in auto Chișinău ['.$cy.'] — Schimb în 1 zi', 'dsc' => 'Trade-in la Sauto în 1 zi: evaluare corectă, mașini la schimb, plata diferenței pe loc, acte rapide. Garanție inclusă. Fără bătăi de cap.', 'h1' => 'Trade-in auto'],
            'insurance'      => ['ttl' => 'Asigurări auto RCA și CASCO în Moldova ['.$cy.']', 'dsc' => 'RCA, CASCO, asigurare verde. Eliberare rapidă, fără comisioane ascunse. Prețuri avantajoase, consultanță gratuită. Asigurări pentru orice tip de auto.', 'h1' => 'Asigurări auto'],
            'testdrive'      => ['ttl' => 'Test-drive auto gratuit Chișinău ['.$cy.']', 'dsc' => 'Test-drive gratuit la mașinile din stoc. Programare online în 2 minute, consultant expert, fără obligații. Probează înainte să cumperi.', 'h1' => 'Test-drive auto'],
            'transportation' => ['ttl' => 'Transport auto Europa-Moldova ['.$cy.'] — În 14 zile', 'dsc' => 'Transport auto din Europa în 14 zile: livrare sigură, vămuire completă, asigurare pe traseu. Prețuri transparente, fără surprize. Servicii profesionale.', 'h1' => 'Transport auto'],
            'payment'        => ['ttl' => 'Modalități de plată ['.$cy.'] — Cash, card, credit', 'dsc' => '5 modalități de plată: cash, card, transfer, credit auto, leasing. Procesare în 1 oră, fără comisioane ascunse. Cumpărare sigură și rapidă.', 'h1' => 'Modalități de plată'],
            'terms'          => ['ttl' => 'Termeni și condiții Sauto.md ['.$cy.'] — Transparenți', 'dsc' => 'Termenii și condițiile de utilizare ale Sauto.md și de cumpărare auto. Transparent, clar, conform legislației Republicii Moldova. Actualizat '.$cy.'.', 'h1' => 'Termeni și condiții'],
        ],
        'ru' => [
            'sale'           => ['ttl' => 'Продай авто в Кишинёве ['.$cy.'] — Оплата на месте', 'dsc' => 'Продай машину в Sauto за 1 час. Бесплатная оценка, оплата на месте, без скрытых комиссий. Лучшая цена на рынке, гарантировано. Выкупаем любую марку.', 'h1' => 'Продай машину в Sauto'],
            'estimation'     => ['ttl' => 'Оценка авто бесплатно Кишинёв ['.$cy.'] — За 15 мин', 'dsc' => 'Бесплатная оценка авто за 15 минут. Точная рыночная стоимость, подробный отчёт, консультация эксперта. 100% без обязательств. Запись онлайн.', 'h1' => 'Оценка авто'],
            'tradein'        => ['ttl' => 'Trade-in авто Кишинёв ['.$cy.'] — Обмен за 1 день', 'dsc' => 'Trade-in в Sauto за 1 день: честная оценка, авто на выбор, доплата разницы на месте, быстрое оформление. Гарантия включена. Без забот.', 'h1' => 'Trade-in авто'],
            'insurance'      => ['ttl' => 'Страхование авто ОСАГО и КАСКО в Молдове ['.$cy.']', 'dsc' => 'ОСАГО, КАСКО, зелёная карта. Быстрое оформление, без скрытых комиссий. Выгодные цены, бесплатная консультация. Страхование для любого типа авто.', 'h1' => 'Страхование авто'],
            'testdrive'      => ['ttl' => 'Тест-драйв авто бесплатно Кишинёв ['.$cy.']', 'dsc' => 'Бесплатный тест-драйв автомобилей со склада. Запись онлайн за 2 минуты, эксперт-консультант, без обязательств. Проверь перед покупкой.', 'h1' => 'Тест-драйв авто'],
            'transportation' => ['ttl' => 'Доставка авто Европа-Молдова ['.$cy.'] — За 14 дней', 'dsc' => 'Доставка авто из Европы за 14 дней: безопасная транспортировка, полная растаможка, страхование в пути. Прозрачные цены. Профессиональные услуги.', 'h1' => 'Транспортировка авто'],
            'payment'        => ['ttl' => 'Способы оплаты ['.$cy.'] — Наличные, карта, кредит', 'dsc' => '5 способов оплаты: наличные, карта, перевод, автокредит, лизинг. Оформление за 1 час, без скрытых комиссий. Безопасная и быстрая покупка.', 'h1' => 'Способы оплаты'],
            'terms'          => ['ttl' => 'Условия использования Sauto.md ['.$cy.'] — Прозрачно', 'dsc' => 'Условия использования сайта Sauto.md и покупки авто. Прозрачно, чётко, в соответствии с законодательством РМ. Обновлено в '.$cy.' году.', 'h1' => 'Условия использования'],
        ],
        'en' => [
            'sale'           => ['ttl' => 'Sell Your Car in Chișinău ['.$cy.'] — Instant Payment', 'dsc' => 'Sell your car to Sauto in 1 hour. Free valuation, instant payment, no hidden fees. Best market price, guaranteed. We buy any make.', 'h1' => 'Sell your car to Sauto'],
            'estimation'     => ['ttl' => 'Free Car Valuation Chișinău ['.$cy.'] — In 15 min', 'dsc' => 'Free car valuation in 15 minutes. Accurate market value, detailed report, expert consultation. 100% no obligations. Book online.', 'h1' => 'Car Valuation'],
            'tradein'        => ['ttl' => 'Trade-in Cars Chișinău ['.$cy.'] — Swap in 1 Day', 'dsc' => 'Trade-in at Sauto in 1 day: fair valuation, cars to choose, instant difference payment, fast paperwork. Warranty included. Hassle-free.', 'h1' => 'Trade-in Cars'],
            'insurance'      => ['ttl' => 'Car Insurance RCA and CASCO in Moldova ['.$cy.']', 'dsc' => 'RCA, CASCO, green card. Fast issuance, no hidden fees. Affordable prices, free consultation. Insurance for any type of car.', 'h1' => 'Car Insurance'],
            'testdrive'      => ['ttl' => 'Free Test-Drive in Chișinău ['.$cy.']', 'dsc' => 'Free test-drive on cars in stock. Online booking in 2 minutes, expert consultant, no obligations. Try before you buy.', 'h1' => 'Test-Drive'],
            'transportation' => ['ttl' => 'Car Transport Europe-Moldova ['.$cy.'] — In 14 Days', 'dsc' => 'Car transport from Europe in 14 days: safe delivery, full customs, transit insurance. Transparent prices, no surprises. Professional service.', 'h1' => 'Car Transportation'],
            'payment'        => ['ttl' => 'Payment Methods ['.$cy.'] — Cash, Card, Credit', 'dsc' => '5 payment methods: cash, bank card, transfer, car loan, leasing. Processing in 1 hour, no hidden fees. Safe and fast purchase.', 'h1' => 'Payment Methods'],
            'terms'          => ['ttl' => 'Terms and Conditions Sauto.md ['.$cy.'] — Transparent', 'dsc' => 'Terms and conditions of using Sauto.md and car purchase. Transparent, clear, compliant with Moldova legislation. Updated '.$cy.'.', 'h1' => 'Terms and Conditions'],
        ],
    ];
    $lang_key = isset($service_titles[$current_lang]) ? $current_lang : 'ro';
    if (isset($service_titles[$lang_key][$service_slug])) {
        $sa['meta']['ttl'] = $service_titles[$lang_key][$service_slug]['ttl'];
        $sa['meta']['dsc'] = $service_titles[$lang_key][$service_slug]['dsc'];
        $sa['meta']['h1']  = $service_titles[$lang_key][$service_slug]['h1'];
        $service_kwd_map = [
            'ro' => [
                'sale'           => 'vinde masina chisinau, vinde auto moldova, vinde masina rapid, cumparare auto rulate, vinde masina la dealer, evaluare gratuita auto, vinde auto bu chisinau',
                'estimation'     => 'evaluare auto chisinau, evaluare masina moldova, pret masina evaluare, evaluare gratuita auto, estimare auto bu, cat valoreaza masina mea',
                'tradein'        => 'trade in auto chisinau, schimb auto moldova, schimb masina cu diferenta, trade in masina, evaluare schimb auto',
                'insurance'      => 'asigurari auto moldova, rca chisinau, casco moldova, asigurare verde, asigurare auto ieftin chisinau, rca casco moldova pret',
                'testdrive'      => 'test drive chisinau, test drive auto moldova, programare test drive, test drive gratuit, proba auto chisinau',
                'transportation' => 'transport auto din europa, livrare auto moldova, transport auto germania moldova, aducere auto din europa, transport masini chisinau',
                'payment'        => 'modalitati plata auto, plata cash auto, plata card auto, transfer bancar auto, credit auto plata',
                'terms'          => 'termeni si conditii sauto, conditii utilizare sauto, regulament sauto md',
            ],
            'ru' => [
                'sale'           => 'продать авто кишинев, продать машину молдова, быстрая продажа авто, выкуп авто кишинев, продать машину дилеру, бесплатная оценка авто, продать бу авто',
                'estimation'     => 'оценка авто кишинев, оценка машины молдова, бесплатная оценка авто, стоимость моего авто, оценка бу авто кишинев',
                'tradein'        => 'трейд ин авто кишинев, обмен авто молдова, обмен машины с доплатой, trade in машина, оценка обмен авто',
                'insurance'      => 'страхование авто молдова, осаго кишинев, каско молдова, зеленая карта молдова, дешевая страховка авто, осаго каско цена',
                'testdrive'      => 'тест драйв кишинев, тест драйв авто молдова, запись на тест драйв, бесплатный тест драйв, проба авто кишинев',
                'transportation' => 'транспортировка авто из европы, доставка авто молдова, перевозка авто германия молдова, привоз авто из европы',
                'payment'        => 'способы оплаты авто, оплата наличными авто, оплата картой авто, банковский перевод авто, автокредит оплата',
                'terms'          => 'условия использования sauto, условия sauto md, правила sauto',
            ],
            'en' => [
                'sale'           => 'sell car chisinau, sell car moldova, fast car sale, car buy back chisinau, sell car to dealer, free car valuation, sell used car moldova',
                'estimation'     => 'car valuation chisinau, car appraisal moldova, free car valuation, my car value, used car appraisal',
                'tradein'        => 'trade in car chisinau, car exchange moldova, swap car with difference, trade in vehicle, exchange valuation',
                'insurance'      => 'car insurance moldova, rca chisinau, casco moldova, green card moldova, cheap car insurance, rca casco price',
                'testdrive'      => 'test drive chisinau, car test drive moldova, book test drive, free test drive, car trial chisinau',
                'transportation' => 'car transport from europe, car delivery moldova, car transport germany moldova, bring car from europe',
                'payment'        => 'car payment methods, car cash payment, car card payment, bank transfer car, car loan payment',
                'terms'          => 'sauto terms and conditions, sauto md terms, sauto rules',
            ],
        ];
        $kwd_lang = isset($service_kwd_map[$current_lang]) ? $current_lang : 'ro';
        $sa['meta']['kwd'] = $service_kwd_map[$kwd_lang][$service_slug] ?? ('servicii auto moldova, sauto, '.$service_slug.' moldova');
    }

    // services/sale is a special page with its own translations
    if ($t_mp[3] === 'sale') {
        $saleTranslationsPath = dirname(__DIR__, 2) . '/content/site/page/new_pages/sale/sale_lang.php';
        if (is_file($saleTranslationsPath)) {
            $saleTranslations = include $saleTranslationsPath;
            if (is_array($saleTranslations)) {
                $metaLocale = $saleTranslations[$current_lang]['meta'] ?? $saleTranslations['ru']['meta'] ?? [];
                if (!empty($metaLocale['title']))       { $sa['meta']['ttl'] = $metaLocale['title']; }
                if (!empty($metaLocale['h1']))          { $sa['meta']['h1']  = $metaLocale['h1']; }
                if (!empty($metaLocale['description'])) { $sa['meta']['dsc'] = $metaLocale['description']; }
                if (!empty($metaLocale['keywords']))    { $sa['meta']['kwd'] = $metaLocale['keywords']; }
            }
        }
    }
}

// =====================================================================
// INFO PAGES (about, privacy, terms, warranty)
// =====================================================================
if (in_array($z2, ['about', 'privacy', 'terms', 'warranty'], true)) {
    $info_meta = [
        'ro' => [
            'about'    => ['ttl' => 'Despre Sauto.md — Dealer auto în Chișinău, Moldova', 'dsc' => 'Sauto — dealer auto de încredere din Chișinău, Moldova. Mașini importate din Europa, servicii complete: credit auto, leasing, trade-in, asigurări. Cumpărați cu încredere.', 'h1' => 'Despre Sauto'],
            'privacy'  => ['ttl' => 'Politica de confidențialitate Sauto.md [GDPR '.$cy.']', 'dsc' => 'Politica de confidențialitate Sauto.md actualizată '.$cy.'. Cum colectăm, folosim și protejăm datele tale personale conform GDPR și legislației Republicii Moldova.', 'h1' => 'Politica de confidențialitate'],
            'terms'    => ['ttl' => 'Termeni și condiții Sauto.md ['.$cy.'] — Transparenți', 'dsc' => 'Termenii și condițiile de utilizare a site-ului Sauto.md și de cumpărare automobile. Clar, transparent, conform legii. Actualizat '.$cy.'.', 'h1' => 'Termeni și condiții'],
            'warranty' => ['ttl' => 'Garanție auto Sauto ['.$cy.'] — 100% verificate tehnic', 'dsc' => 'Toate mașinile Sauto au garanție și sunt verificate tehnic. Asistență 24/7, condiții transparente. Cumpărați cu încredere — verificat înainte de livrare.', 'h1' => 'Garanție auto Sauto'],
        ],
        'ru' => [
            'about'    => ['ttl' => 'О Sauto.md — Автодилер в Кишинёве, Молдова', 'dsc' => 'Sauto — надёжный автодилер в Кишинёве, Молдова. Импорт авто из Европы, полный спектр услуг: автокредит, лизинг, trade-in, страхование. Покупайте с уверенностью.', 'h1' => 'О компании Sauto'],
            'privacy'  => ['ttl' => 'Политика конфиденциальности Sauto.md [GDPR '.$cy.']', 'dsc' => 'Политика конфиденциальности Sauto.md обновлена '.$cy.'. Как мы собираем, используем и защищаем ваши данные согласно GDPR и законодательству РМ.', 'h1' => 'Политика конфиденциальности'],
            'terms'    => ['ttl' => 'Условия использования Sauto.md ['.$cy.'] — Прозрачно', 'dsc' => 'Условия использования сайта Sauto.md и покупки автомобилей. Прозрачно, чётко, в соответствии с законом. Обновлено '.$cy.'.', 'h1' => 'Условия использования'],
            'warranty' => ['ttl' => 'Гарантия авто Sauto ['.$cy.'] — 100% проверены', 'dsc' => 'Все авто Sauto имеют гарантию и техническую проверку. Поддержка 24/7, прозрачные условия. Покупайте с уверенностью — проверено до доставки.', 'h1' => 'Гарантия авто Sauto'],
        ],
        'en' => [
            'about'    => ['ttl' => 'About Sauto.md — Car Dealer in Chișinău, Moldova', 'dsc' => 'Sauto — trusted car dealer in Chișinău, Moldova. Cars imported from Europe, full services: car loan, leasing, trade-in, insurance. Buy with confidence.', 'h1' => 'About Sauto'],
            'privacy'  => ['ttl' => 'Privacy Policy Sauto.md [GDPR '.$cy.']', 'dsc' => 'Sauto.md Privacy Policy updated '.$cy.'. How we collect, use and protect your personal data in accordance with GDPR and Moldova legislation.', 'h1' => 'Privacy Policy'],
            'terms'    => ['ttl' => 'Terms and Conditions Sauto.md ['.$cy.'] — Transparent', 'dsc' => 'Terms and conditions of using Sauto.md website and purchasing cars. Clear, transparent, compliant with the law. Updated '.$cy.'.', 'h1' => 'Terms and Conditions'],
            'warranty' => ['ttl' => 'Sauto Car Warranty ['.$cy.'] — 100% Inspected', 'dsc' => 'All Sauto cars have warranty and technical inspection. 24/7 support, transparent conditions. Buy with confidence — verified before delivery.', 'h1' => 'Sauto Car Warranty'],
        ],
    ];
    $lang_key = isset($info_meta[$current_lang]) ? $current_lang : 'ro';
    if (isset($info_meta[$lang_key][$z2])) {
        $sa['meta']['ttl'] = $info_meta[$lang_key][$z2]['ttl'];
        $sa['meta']['dsc'] = $info_meta[$lang_key][$z2]['dsc'];
        $sa['meta']['h1']  = $info_meta[$lang_key][$z2]['h1'];
        $info_kwd_map = [
            'ro' => [
                'about'    => 'despre sauto, dealer auto moldova, salon auto chisinau, istoria sauto, despre sauto md, dealer auto de incredere chisinau',
                'privacy'  => 'politica confidentialitate sauto, gdpr sauto, protectia datelor sauto md, confidentialitate auto moldova',
                'terms'    => 'termeni conditii sauto, conditii utilizare sauto md, regulament sauto, termeni vanzare auto',
                'warranty' => 'garantie auto sauto, garantie masini chisinau, garantie auto rulate moldova, masini verificate chisinau, garantie auto din europa',
            ],
            'ru' => [
                'about'    => 'о sauto, автодилер молдова, автосалон кишинев, история sauto, о компании sauto md, надежный автодилер кишинев',
                'privacy'  => 'политика конфиденциальности sauto, gdpr sauto, защита данных sauto md, конфиденциальность авто молдова',
                'terms'    => 'условия использования sauto, условия sauto md, правила sauto, условия продажи авто',
                'warranty' => 'гарантия авто sauto, гарантия машины кишинев, гарантия бу авто молдова, проверенные авто кишинев, гарантия авто из европы',
            ],
            'en' => [
                'about'    => 'about sauto, car dealer moldova, car dealership chisinau, sauto history, about sauto md, trusted car dealer chisinau',
                'privacy'  => 'sauto privacy policy, gdpr sauto, data protection sauto md, car privacy moldova',
                'terms'    => 'sauto terms conditions, sauto md terms, sauto rules, car sale terms',
                'warranty' => 'sauto car warranty, car warranty chisinau, used car warranty moldova, inspected cars chisinau, european car warranty',
            ],
        ];
        $info_kwd_lang = isset($info_kwd_map[$current_lang]) ? $current_lang : 'ro';
        $sa['meta']['kwd'] = $info_kwd_map[$info_kwd_lang][$z2] ?? ('sauto, '.$z2.', dealer auto moldova, auto moldova');
    }
}

// =====================================================================
// CARS / ORDERCARS / TYRES — DETAIL PAGE (numeric ID)
// =====================================================================
if ( in_array($z2, ['cars', 'ordercars', 'tyres'], true) && is_numeric($z3) && !isset($q_mp[1]) ) {
    $zl = '';
    $p_type = ($z2 == 'cars' || $z2 == 'ordercars') ? 'car' : 'tyre';

    if ($z2 == 'cars' || $z2 == 'ordercars'){
        $pdo = $db->prepare('SELECT * FROM '.$prefx.'_car_ctlg WHERE `id`=:id');
        $pdo2 = $db->prepare('SELECT `name`, `main` FROM '.$prefx.'_car_pht WHERE `it_id`=:id AND `main`="1"');
        $zl = 'car';
    } else {
        $pdo = $db->prepare('SELECT * FROM '.$prefx.'_tyre_ctlg WHERE `id`=:id');
        $pdo2 = $db->prepare('SELECT `name`, `main` FROM '.$prefx.'_tyre_pht WHERE `it_id`=:id AND `main`="1"');
        $zl = 'tyre';
    }

    $pdo->execute(array('id' => $z3));
    $ir = 0;
    foreach ($pdo as $r){ foreach ($r as $k => $v){ $sa['it']['r'][$k] = $v; } $ir = 1; }

    if ($ir == 1){
        // Currency symbol decoded once: language.php stores &#128; (legacy CP1252 entity for €) and &#36; for $.
        // Meta tags pass through htmlspecialchars() which would double-encode "&" → "&amp;", showing literally
        // "38699&#128;" in the title. We normalize the legacy entity to real UTF-8 here.
        $cur_raw = isset($lng['l']['cur'][$sa['it']['r']['cur']]) ? $lng['l']['cur'][$sa['it']['r']['cur']] : '';
        $cur_sym = strtr($cur_raw, ['&#128;' => '€', '&#36;' => '$']);
        $cur_sym = html_entity_decode($cur_sym, ENT_QUOTES, 'UTF-8');
        $pdo2->execute(array('id' => $r['id']));
        foreach ($pdo2 as $r2){
            $sa['it']['img'] = 'https://www.sauto.md/media/images/upload/'.$p_type.'/'.$sa['it']['r']['p_path'].'/'.$sa['it']['r']['id'].'/high/'.$r2['name'].'.jpg';
        }
        unset($pdo2, $r2);

        // Check for SEO override in seo2 table
        $pdo_seo = $db->prepare('SELECT `ttl`, `h1`, `dsc`, `kwd` FROM '.$prefx.'_seo2 USE INDEX (altp) WHERE `lng`=:lng AND `tp`="item" AND `p1`=:p1 AND `p2`=:p2 ');
        $pdo_seo->execute(array('lng' => $zlng, 'p1' => $z2, 'p2' => $z3));
        $seo_ir = 0;
        $seo_r = ['ttl'=>'', 'h1'=>'', 'dsc'=>'', 'kwd'=>''];
        foreach ($pdo_seo as $row_seo){
            $seo_r = $row_seo;
            $seo_ir = 1;
        }

        if ($z2 == 'cars'){
            // Build a compact, CTR-friendly title: "Brand Model Year, Type Trans Color — Price€"
            // Attributes are ordered high→low SEO value (body type, transmission, color);
            // sauto_trim_title() drops them from the end (color first) if the 70-char limit is exceeded.
            $title_core = $sa['it']['r']['br_nm'].' '.$sa['it']['r']['mo_nm'];
            if (!empty($sa['it']['r']['yr'])) $title_core .= ' '.$sa['it']['r']['yr'];
            $title_attrs = [];
            if (!empty($sa['it']['r']['bt']) && isset($lng['l']['car']['bt'][$sa['it']['r']['bt']])) $title_attrs[] = $lng['l']['car']['bt'][$sa['it']['r']['bt']];
            if (!empty($sa['it']['r']['tra']) && isset($lng['l']['car']['tra'][$sa['it']['r']['tra']])) $title_attrs[] = $lng['l']['car']['tra'][$sa['it']['r']['tra']];
            if (!empty($sa['it']['r']['clr']) && isset($lng['l']['car']['clr'][$sa['it']['r']['clr']])) $title_attrs[] = $lng['l']['car']['clr'][$sa['it']['r']['clr']];
            $title_price = !empty($sa['it']['r']['prc']) ? ' — '.$sa['it']['r']['prc'].$cur_sym : '';
            $title_built = sauto_trim_title($title_core, $title_attrs, $title_price);

            $sa['meta']['ttl'] = ($seo_ir == 1 && $seo_r['ttl'] != '') ? $seo_r['ttl'] : $title_built;
            $sa['meta']['h1']  = ($seo_ir == 1 && $seo_r['h1']  != '') ? $seo_r['h1']  : $sa['it']['r']['br_nm'].' '.$sa['it']['r']['mo_nm'].', id-'.$sa['it']['r']['id'];

            // Build a richer description with trigger words (Verified Warranty Credit)
            $desc_parts = [];
            $desc_parts[] = ''.$sa['it']['r']['br_nm'].' '.$sa['it']['r']['mo_nm'];
            if (!empty($sa['it']['r']['yr'])) $desc_parts[] = $sa['it']['r']['yr'];
            if (!empty($sa['it']['r']['clr']) && isset($lng['l']['car']['clr'][$sa['it']['r']['clr']])) $desc_parts[] = $lng['l']['car']['clr'][$sa['it']['r']['clr']];
            if (!empty($sa['it']['r']['prc'])) $desc_parts[] = '— '.$sa['it']['r']['prc'].$cur_sym;
            $desc_parts[] = $lng['t']['seo']['car_inf_dsc'];

            $sa['meta']['dsc'] = ($seo_ir == 1 && $seo_r['dsc'] != '') ? $seo_r['dsc'] : implode(' ', $desc_parts);
            // Spec keywords (body/gearbox/fuel/colour) are added only when the car
            // carries that field AND it has a label. A car can be missing one (e.g.
            // an imported car whose source fuel has no sauto match), which used to
            // emit "Undefined index:" and leave an empty ",," in the list.
            $kwd_parts = [$lng['w']['moldova'], $lng['w']['sale'], $lng['w']['auto'], $lng['w']['buy'],
                          $sa['it']['r']['br'], $sa['it']['r']['mo']];
            foreach (['bt', 'tra', 'fl', 'clr'] as $spec_k) {
                $spec_v = $sa['it']['r'][$spec_k] ?? '';
                if ($spec_v !== '' && isset($lng['l'][$zl][$spec_k][$spec_v])) {
                    $kwd_parts[] = $lng['l'][$zl][$spec_k][$spec_v];
                }
            }
            $kwd_parts[] = 'id'.$sa['it']['r']['id'];
            $sa['meta']['kwd'] = ($seo_ir == 1 && $seo_r['kwd'] != '') ? $seo_r['kwd'] : mb_strtolower(implode(',', $kwd_parts), "UTF-8");
        }
        elseif ($z2 == 'ordercars'){
            // Order car title: "[Prefix] Brand Model Year, attrs — Price€"
            // Attributes ordered high→low SEO value; sauto_trim_title() drops the
            // least valuable ones (color first) if the 70-char limit is exceeded.
            $title_core = $lng['t']['seo']['order_car_prefix'].' '.$sa['it']['r']['br_nm'].' '.$sa['it']['r']['mo_nm'];
            if (!empty($sa['it']['r']['yr'])) $title_core .= ' '.$sa['it']['r']['yr'];
            $title_attrs = [];
            if (!empty($sa['it']['r']['bt']) && isset($lng['l']['car']['bt'][$sa['it']['r']['bt']])) $title_attrs[] = $lng['l']['car']['bt'][$sa['it']['r']['bt']];
            if (!empty($sa['it']['r']['tra']) && isset($lng['l']['car']['tra'][$sa['it']['r']['tra']])) $title_attrs[] = $lng['l']['car']['tra'][$sa['it']['r']['tra']];
            if (!empty($sa['it']['r']['clr']) && isset($lng['l']['car']['clr'][$sa['it']['r']['clr']])) $title_attrs[] = $lng['l']['car']['clr'][$sa['it']['r']['clr']];
            $title_price = !empty($sa['it']['r']['prc']) ? ' — '.$sa['it']['r']['prc'].$cur_sym : '';
            $title_built = sauto_trim_title($title_core, $title_attrs, $title_price);

            $sa['meta']['ttl'] = ($seo_ir == 1 && $seo_r['ttl'] != '') ? $seo_r['ttl'] : $title_built;
            $sa['meta']['h1']  = ($seo_ir == 1 && $seo_r['h1']  != '') ? $seo_r['h1']  : $sa['it']['r']['br_nm'].' '.$sa['it']['r']['mo_nm'].', id-'.$sa['it']['r']['id'];

            $desc_parts = [];
            $desc_parts[] = ''.$lng['t']['seo']['order_car_dsc'];
            $desc_parts[] = $sa['it']['r']['br_nm'].' '.$sa['it']['r']['mo_nm'];
            if (!empty($sa['it']['r']['yr'])) $desc_parts[] = $sa['it']['r']['yr'];
            if (!empty($sa['it']['r']['clr']) && isset($lng['l']['car']['clr'][$sa['it']['r']['clr']])) $desc_parts[] = $lng['l']['car']['clr'][$sa['it']['r']['clr']];
            if (!empty($sa['it']['r']['prc'])) $desc_parts[] = '— '.$sa['it']['r']['prc'].$cur_sym;
            $desc_parts[] = $lng['t']['seo']['order_car_dsc_end'];

            $sa['meta']['dsc'] = ($seo_ir == 1 && $seo_r['dsc'] != '') ? $seo_r['dsc'] : implode(' ', $desc_parts);
            // Spec keywords (body/gearbox/fuel/colour) are added only when the car
            // carries that field AND it has a label. A car can be missing one (e.g.
            // an imported car whose source fuel has no sauto match), which used to
            // emit "Undefined index:" and leave an empty ",," in the list.
            $kwd_parts = [$lng['w']['moldova'], $lng['w']['sale'], $lng['w']['auto'], $lng['w']['buy'],
                          $sa['it']['r']['br'], $sa['it']['r']['mo']];
            foreach (['bt', 'tra', 'fl', 'clr'] as $spec_k) {
                $spec_v = $sa['it']['r'][$spec_k] ?? '';
                if ($spec_v !== '' && isset($lng['l'][$zl][$spec_k][$spec_v])) {
                    $kwd_parts[] = $lng['l'][$zl][$spec_k][$spec_v];
                }
            }
            $kwd_parts[] = 'id'.$sa['it']['r']['id'];
            $sa['meta']['kwd'] = ($seo_ir == 1 && $seo_r['kwd'] != '') ? $seo_r['kwd'] : mb_strtolower(implode(',', $kwd_parts), "UTF-8");
        }
        elseif ($z2 == 'tyres'){
            // Tyre title: "Brand 205/55 R16 Season — Price€"
            // Brand+Size is the core; the season label is the only optional attribute
            // and is dropped by sauto_trim_title() if the 70-char limit is exceeded.
            $tyre_size = $sa['it']['r']['w'].'/'.$sa['it']['r']['h'].' R'.$sa['it']['r']['d'].($sa['it']['r']['c']==1?'C':'');
            $tyre_core = $sa['it']['r']['br'].' '.$tyre_size;
            $tyre_attrs = [];
            if (isset($lng['l']['tyre']['ss'][$sa['it']['r']['ss']])) $tyre_attrs[] = $lng['l']['tyre']['ss'][$sa['it']['r']['ss']];
            $tyre_price = !empty($sa['it']['r']['prc']) ? ' — '.$sa['it']['r']['prc'].$sa['it']['r']['cur'] : '';
            $tyre_ttl = sauto_trim_title($tyre_core, $tyre_attrs, $tyre_price);
            $tyre_dsc = ''.$lng['w']['sale'].' '.(mb_strtolower($lng['w']['tyres'], "UTF-8")).' '.$sa['it']['r']['br'].' '.$tyre_size.' '.$lng['u']['for'].' '.$sa['it']['r']['prc'].$sa['it']['r']['cur'].' '.$lng['u']['in'].' '.$lng['w']['chisinau'].'. Montaj gratuit, garanție 2 ani.';

            $sa['meta']['ttl'] = ($seo_ir == 1 && $seo_r['ttl'] != '') ? $seo_r['ttl'] : $tyre_ttl;
            $sa['meta']['h1']  = ($seo_ir == 1 && $seo_r['h1']  != '') ? $seo_r['h1']  : ( $sa['it']['r']['br'].' '.$tyre_size.', id-'.$sa['it']['r']['id'] );
            $sa['meta']['dsc'] = ($seo_ir == 1 && $seo_r['dsc'] != '') ? $seo_r['dsc'] : $tyre_dsc;
            $sa['meta']['kwd'] = ($seo_ir == 1 && $seo_r['kwd'] != '') ? $seo_r['kwd'] : mb_strtolower($lng['w']['moldova'].','.$lng['w']['sale'].','.$lng['w']['tyres'].','.$lng['w']['buy'].','.$sa['it']['r']['br'].','.$sa['it']['r']['w'].','.$sa['it']['r']['h'].',r'.$sa['it']['r']['d'].($sa['it']['r']['c']==1?'c':'').','.$lng['l']['tyre']['ss'][$sa['it']['r']['ss']].',id'.$sa['it']['r']['id'], "UTF-8");
        }
    }
}

// =====================================================================
// ORDERCARS - IMPORT REGION pages (clean URL)
// /ro/ordercars/korea, /ro/ordercars/europe, /ro/ordercars/usa
// These are dedicated landing pages (own canonical + h1/title/description),
// not brands. Handled before the brand block so "korea" is not looked up as a brand.
// =====================================================================
$oc_region_meta = [
    'korea'  => ['ro' => 'Coreea', 'ru' => 'Корея',  'en' => 'Korea'],
    'europe' => ['ro' => 'Europa', 'ru' => 'Европа', 'en' => 'Europe'],
    'usa'    => ['ro' => 'SUA',    'ru' => 'США',    'en' => 'USA'],
];
$oc_region_key = ($z2 === 'ordercars' && !is_numeric($z3)) ? strtolower($z3) : '';

if ($oc_region_key !== '' && isset($oc_region_meta[$oc_region_key])) {
    $reg = $oc_region_meta[$oc_region_key][$current_lang] ?? $oc_region_meta[$oc_region_key]['ro'];
    switch ($current_lang) {
        case 'ru':
            $sa['meta']['ttl'] = "Авто под заказ из {$reg} — Экономия до 45%";
            $sa['meta']['dsc'] = "Авто под заказ из {$reg} в Молдову [{$cy}], под ключ. Экономия до 45% vs салон. Подбор, проверка VIN, доставка за 14 дней, растаможка. Гарантия. Доставка по всей Молдове.";
            $sa['meta']['h1']  = "Авто под заказ из {$reg}";
            $sa['meta']['kwd'] = mb_strtolower("авто под заказ из {$reg}, заказать авто из {$reg}, импорт авто из {$reg} молдова, авто из {$reg} цена, привезти авто из {$reg} в молдову, доставка авто из {$reg}, растаможка авто {$reg} молдова", "UTF-8");
            break;
        case 'en':
            $sa['meta']['ttl'] = "Cars on Order from {$reg} to Moldova — Save up to 45%";
            $sa['meta']['dsc'] = "Cars on order from {$reg} to Moldova [{$cy}], turnkey. Save up to 45% vs showroom. Selection, VIN check, 14-day delivery, customs. Warranty. Delivery across Moldova.";
            $sa['meta']['h1']  = "Cars on Order from {$reg}";
            $sa['meta']['kwd'] = mb_strtolower("cars on order from {$reg}, order car from {$reg} moldova, import cars from {$reg} moldova, {$reg} car price moldova, bring car from {$reg} to moldova, car delivery from {$reg}, customs clearance {$reg} moldova", "UTF-8");
            break;
        default: // ro
            $sa['meta']['ttl'] = "Auto la comandă din {$reg} — Economie până la 45%";
            $sa['meta']['dsc'] = "Auto la comandă din {$reg} în Moldova [{$cy}], la cheie. Economie până la 45% vs salon. Selecție, verificare VIN, livrare în 14 zile, vămuire. Garanție inclusă.";
            $sa['meta']['h1']  = "Auto la comandă din {$reg}";
            $sa['meta']['kwd'] = mb_strtolower("auto la comanda din {$reg}, comanda auto din {$reg} moldova, import auto din {$reg} moldova, auto din {$reg} pret moldova, aducem auto din {$reg} in moldova, livrare auto din {$reg}, vamuire auto {$reg} moldova", "UTF-8");
            break;
    }
}

// =====================================================================
// CARS / ORDERCARS - BRAND / BRAND+MODEL pages (clean URL)
// /ro/cars/ford, /ro/cars/ford/focus, /ro/ordercars/ford, /ro/ordercars/ford/focus
// =====================================================================
if ( in_array($z2, ['cars', 'ordercars'], true) && !is_numeric($z3) && isset($t_mp[3]) && !empty($t_mp[3]) && !($z2 === 'ordercars' && isset($oc_region_meta[strtolower($z3)])) ) {
    $brand_code = str_replace('-', '_', $z3);
    $brand_name = '';
    $model_name = '';

    $pdo_brand = $db->prepare('SELECT `br_nm` FROM '.$prefx.'_car_list WHERE `br`=:br LIMIT 1');
    $pdo_brand->execute(['br' => $brand_code]);
    foreach ($pdo_brand as $brand_row) {
        $brand_name = $brand_row['br_nm'];
    }


    if (!empty($brand_name) && isset($t_mp[4]) && !empty($t_mp[4])) {
        $model_code = str_replace('-', '_', $t_mp[4]);
        $pdo_model = $db->prepare('SELECT `mo_nm` FROM '.$prefx.'_car_list WHERE `br`=:br AND `mo`=:mo LIMIT 1');
        $pdo_model->execute(['br' => $brand_code, 'mo' => $model_code]);
        foreach ($pdo_model as $model_row) {
            $model_name = $model_row['mo_nm'];
        }
    }

    // Fetch live stats (count + year range + min price) from BD for richer SEO meta
    $stats = ['cnt' => 0, 'yr_min' => 0, 'yr_max' => 0, 'prc_min' => 0];
    $stats_cat = ($z2 === 'cars') ? 'in_stock' : 'on_order';
    if (!empty($brand_name)) {
        $stats_sql = 'SELECT COUNT(*) AS cnt, MIN(yr) AS yr_min, MAX(yr) AS yr_max, MIN(prc) AS prc_min FROM '.$prefx.'_car_ctlg WHERE `br`=:br AND `vis`="1" AND `act`="1" AND `catalog_type`=:ct';
        $stats_params = ['br' => $brand_code, 'ct' => $stats_cat];
        if (!empty($model_name)) {
            $stats_sql .= ' AND `mo`=:mo';
            $stats_params['mo'] = $model_code;
        }
        $pdo_stats = $db->prepare($stats_sql);
        $pdo_stats->execute($stats_params);
        foreach ($pdo_stats as $sr) {
            $stats['cnt']     = (int)$sr['cnt'];
            $stats['yr_min']  = (int)$sr['yr_min'];
            $stats['yr_max']  = (int)$sr['yr_max'];
            $stats['prc_min'] = (int)$sr['prc_min'];
        }
    }

    // Build year range fragment: "2015-2023", "2020" if only one year, or "" if none
    $yr_frag = '';
    if ($stats['yr_min'] > 0 && $stats['yr_max'] > 0) {
        $yr_frag = ($stats['yr_min'] === $stats['yr_max']) ? (string)$stats['yr_min'] : $stats['yr_min'].'-'.$stats['yr_max'];
    }
    $cnt = $stats['cnt'];
    $prc_min = $stats['prc_min'];

    // BRAND + MODEL combination
    if (!empty($brand_name) && !empty($model_name)) {
        if ($z2 === 'cars') {
            switch ($current_lang) {
                case 'ru':
                    $sa['meta']['ttl'] = "Купить {$brand_name} {$model_name} в Молдове [{$cy}]";
                    $price_txt = $prc_min > 0 ? " Цены от {$prc_min}€." : '';
                    $sa['meta']['dsc'] = "{$brand_name} {$model_name} в Молдове.{$price_txt} Проверено, с гарантией. Кредит, лизинг, Trade-in, тест-драйв бесплатно.";
                    $sa['meta']['h1']  = "{$brand_name} {$model_name} в Молдове";
                    $sa['meta']['kwd'] = mb_strtolower("{$brand_name} {$model_name}, купить {$brand_name} {$model_name} молдова, {$brand_name} {$model_name} молдова, {$brand_name} {$model_name} бу, {$brand_name} {$model_name} с пробегом, {$brand_name} {$model_name} цена молдова, {$brand_name} {$model_name} из европы, {$brand_name} {$model_name} в наличии молдова, {$brand_name} {$model_name} без пробега по рм", "UTF-8");
                    break;
                case 'en':
                    $sa['meta']['ttl'] = "{$brand_name} {$model_name} for Sale in Moldova [{$cy}]";
                    $price_txt = $prc_min > 0 ? " Prices from €{$prc_min}." : '';
                    $sa['meta']['dsc'] = "{$brand_name} {$model_name} in Moldova.{$price_txt} Inspected, with warranty. Credit, leasing, Trade-in, free test-drive.";
                    $sa['meta']['h1']  = "{$brand_name} {$model_name} in Moldova";
                    $sa['meta']['kwd'] = mb_strtolower("{$brand_name} {$model_name}, buy {$brand_name} {$model_name} moldova, used {$brand_name} {$model_name}, second hand {$brand_name} {$model_name}, {$brand_name} {$model_name} price moldova, {$brand_name} {$model_name} from europe, {$brand_name} {$model_name} in stock moldova, {$brand_name} {$model_name} no mileage moldova", "UTF-8");
                    break;
                default: // ro
                    $sa['meta']['ttl'] = "{$brand_name} {$model_name} de vânzare în Moldova [{$cy}]";
                    $price_txt = $prc_min > 0 ? " Prețuri de la {$prc_min}€." : '';
                    $sa['meta']['dsc'] = "{$brand_name} {$model_name} în Moldova.{$price_txt} Verificat, cu garanție. Credit, leasing, Trade-in, test-drive gratuit.";
                    $sa['meta']['h1']  = "{$brand_name} {$model_name} în Moldova";
                    $sa['meta']['kwd'] = mb_strtolower("{$brand_name} {$model_name}, cumpara {$brand_name} {$model_name} moldova, {$brand_name} {$model_name} rulate, {$brand_name} {$model_name} bu, {$brand_name} {$model_name} second hand, {$brand_name} {$model_name} pret moldova, {$brand_name} {$model_name} din europa, {$brand_name} {$model_name} in stoc moldova, {$brand_name} {$model_name} fara parcurs rm, {$brand_name} {$model_name} de vanzare", "UTF-8");
                    break;
            }
        } else { // ordercars
            switch ($current_lang) {
                case 'ru':
                    $sa['meta']['ttl'] = "{$brand_name} {$model_name} под заказ [{$cy}]";
                    $price_txt = $prc_min > 0 ? " От {$prc_min}€." : '';
                    $sa['meta']['dsc'] = "{$brand_name} {$model_name} под заказ из Европы.{$price_txt} Подбор по VIN, проверка, доставка за 14 дней, растаможка. Экономия до 45%. Гарантия.";
                    $sa['meta']['h1']  = "{$brand_name} {$model_name} под заказ";
                    $sa['meta']['kwd'] = mb_strtolower("{$brand_name} {$model_name} под заказ, заказать {$brand_name} {$model_name} молдова, {$brand_name} {$model_name} из европы, {$brand_name} {$model_name} из германии, {$brand_name} {$model_name} цена под заказ, привезти {$brand_name} {$model_name} в молдову, {$brand_name} {$model_name} молдова заказ", "UTF-8");
                    break;
                case 'en':
                    $sa['meta']['ttl'] = "{$brand_name} {$model_name} on Order [{$cy}]";
                    $price_txt = $prc_min > 0 ? " From €{$prc_min}." : '';
                    $sa['meta']['dsc'] = "{$brand_name} {$model_name} on order from Europe.{$price_txt} VIN check, 14-day delivery, customs. Save up to 45%. Warranty included.";
                    $sa['meta']['h1']  = "{$brand_name} {$model_name} on Order";
                    $sa['meta']['kwd'] = mb_strtolower("{$brand_name} {$model_name} on order, order {$brand_name} {$model_name} moldova, {$brand_name} {$model_name} from europe, {$brand_name} {$model_name} from germany, {$brand_name} {$model_name} order price, bring {$brand_name} {$model_name} to moldova, {$brand_name} {$model_name} moldova order", "UTF-8");
                    break;
                default: // ro
                    $sa['meta']['ttl'] = "{$brand_name} {$model_name} la comandă din Europa [{$cy}]";
                    $price_txt = $prc_min > 0 ? " De la {$prc_min}€." : '';
                    $sa['meta']['dsc'] = "{$brand_name} {$model_name} la comandă din Europa.{$price_txt} Verificare VIN, livrare în 14 zile, vămuire. Economie până la 45%. Garanție inclusă.";
                    $sa['meta']['h1']  = "{$brand_name} {$model_name} la comandă";
                    $sa['meta']['kwd'] = mb_strtolower("{$brand_name} {$model_name} la comanda, comanda {$brand_name} {$model_name} moldova, {$brand_name} {$model_name} din europa, {$brand_name} {$model_name} din germania, {$brand_name} {$model_name} pret la comanda, aducem {$brand_name} {$model_name} in moldova, {$brand_name} {$model_name} moldova comanda", "UTF-8");
                    break;
            }
        }
    }
    // BRAND only
    elseif (!empty($brand_name) && empty($model_name)) {
        if ($z2 === 'cars') {
            switch ($current_lang) {
                case 'ru':
                    $sa['meta']['ttl'] = "Купить {$brand_name} в Молдове [{$cy}] — Все модели";
                    $price_txt = $prc_min > 0 ? " Цены от {$prc_min}€." : '';
                    $sa['meta']['dsc'] = "{$brand_name} в Молдове.{$price_txt} Все модели, с гарантией. Кредит, лизинг, Trade-in, тест-драйв бесплатно.";
                    $sa['meta']['h1']  = "Автомобили {$brand_name}";
                    $sa['meta']['kwd'] = mb_strtolower("{$brand_name} молдова, купить {$brand_name} молдова, {$brand_name} бу молдова, {$brand_name} с пробегом, {$brand_name} в наличии молдова, {$brand_name} из европы, {$brand_name} цена молдова, {$brand_name} без пробега по рм, авто {$brand_name} молдова", "UTF-8");
                    break;
                case 'en':
                    $sa['meta']['ttl'] = "{$brand_name} for Sale in Moldova [{$cy}] — All Models";
                    $price_txt = $prc_min > 0 ? " Prices from €{$prc_min}." : '';
                    $sa['meta']['dsc'] = "{$brand_name} in Moldova.{$price_txt} All models, with warranty. Credit, leasing, Trade-in, free test-drive.";
                    $sa['meta']['h1']  = "{$brand_name} Cars";
                    $sa['meta']['kwd'] = mb_strtolower("{$brand_name} moldova, buy {$brand_name} moldova, used {$brand_name} moldova, second hand {$brand_name}, {$brand_name} in stock moldova, {$brand_name} from europe, {$brand_name} price moldova, {$brand_name} no mileage moldova, {$brand_name} cars moldova", "UTF-8");
                    break;
                default: // ro
                    $sa['meta']['ttl'] = "{$brand_name} de vânzare în Moldova [{$cy}] — Toate modelele";
                    $price_txt = $prc_min > 0 ? " Prețuri de la {$prc_min}€." : '';
                    $sa['meta']['dsc'] = "{$brand_name} în Moldova.{$price_txt} Toate modelele, cu garanție. Credit, leasing, Trade-in, test-drive gratuit.";
                    $sa['meta']['h1']  = "Automobile {$brand_name}";
                    $sa['meta']['kwd'] = mb_strtolower("{$brand_name} moldova, cumpara {$brand_name} moldova, {$brand_name} rulate moldova, {$brand_name} bu, {$brand_name} second hand, {$brand_name} in stoc moldova, {$brand_name} din europa, {$brand_name} pret moldova, {$brand_name} fara parcurs rm, automobile {$brand_name} moldova", "UTF-8");
                    break;
            }
        } else { // ordercars
            switch ($current_lang) {
                case 'ru':
                    $sa['meta']['ttl'] = "{$brand_name} под заказ из Европы [{$cy}]";
                    $price_txt = $prc_min > 0 ? " От {$prc_min}€." : '';
                    $sa['meta']['dsc'] = "Все модели {$brand_name} под заказ из Европы.{$price_txt} Подбор по VIN, доставка за 14 дней, растаможка. Экономия до 45%. Гарантия включена.";
                    $sa['meta']['h1']  = "{$brand_name} под заказ";
                    $sa['meta']['kwd'] = mb_strtolower("{$brand_name} под заказ молдова, заказать {$brand_name} молдова, {$brand_name} из европы, {$brand_name} из германии, {$brand_name} цена под заказ, привезти {$brand_name} в молдову, импорт {$brand_name} молдова", "UTF-8");
                    break;
                case 'en':
                    $sa['meta']['ttl'] = "{$brand_name} on Order from Europe [{$cy}]";
                    $price_txt = $prc_min > 0 ? " From €{$prc_min}." : '';
                    $sa['meta']['dsc'] = "All {$brand_name} models on order from Europe.{$price_txt} VIN check, 14-day delivery, customs. Save up to 45%. Warranty included.";
                    $sa['meta']['h1']  = "{$brand_name} on Order";
                    $sa['meta']['kwd'] = mb_strtolower("{$brand_name} on order moldova, order {$brand_name} moldova, {$brand_name} from europe, {$brand_name} from germany, {$brand_name} order price, bring {$brand_name} to moldova, {$brand_name} import moldova", "UTF-8");
                    break;
                default: // ro
                    $sa['meta']['ttl'] = "{$brand_name} la comandă din Europa [{$cy}]";
                    $price_txt = $prc_min > 0 ? " De la {$prc_min}€." : '';
                    $sa['meta']['dsc'] = "Toate modelele {$brand_name} la comandă din Europa.{$price_txt} Verificare VIN, livrare în 14 zile, vămuire. Economie până la 45%. Garanție inclusă.";
                    $sa['meta']['h1']  = "{$brand_name} la comandă";
                    $sa['meta']['kwd'] = mb_strtolower("{$brand_name} la comanda moldova, comanda {$brand_name} moldova, {$brand_name} din europa, {$brand_name} din germania, {$brand_name} pret la comanda, aducem {$brand_name} in moldova, import {$brand_name} moldova", "UTF-8");
                    break;
            }
        }
    }
}

// =====================================================================
// FILTER PAGES (?tg=fltr, ?tg=fltr&srt=...)
// Canonical is set in head.php without query string
// =====================================================================
if ( in_array($z2, ['cars', 'ordercars', 'tyres'], true) && isset($q_mp[1]) && !is_numeric($z3) ) {
    // If multiple parameters or sorting exist, pages have canonical pointing to the base URL
    // Check if brand/model exists in URL to reuse brand/model meta
    $has_multi_filter = false;
    $param_count = 0;
    foreach ($_GET as $k => $v) {
        if ($k === 'tg') continue;
        if ($k === 'srt') { $has_multi_filter = true; continue; }
        $param_count++;
        if (strpos($v, '-') !== false) {
            $has_multi_filter = true;
        }
    }
    if ($param_count > 1) { $has_multi_filter = true; }

    // If brand/model exists in URL (?br=X&mo=Y), use brand/model meta
    $brand_name = '';
    $model_name = '';
    if (isset($_GET['br'])) {
        $brand_code = str_replace('-', '_', $_GET['br']);
        $pdo_brand = $db->prepare('SELECT `br_nm` FROM '.$prefx.'_car_list WHERE `br`=:br LIMIT 1');
        $pdo_brand->execute(['br' => $brand_code]);
        foreach ($pdo_brand as $brand_row) {
            $brand_name = $brand_row['br_nm'];
        }
        if (isset($_GET['mo']) && !empty($brand_name)) {
            $model_code = str_replace('-', '_', $_GET['mo']);
            $pdo_model = $db->prepare('SELECT `mo_nm` FROM '.$prefx.'_car_list WHERE `br`=:br AND `mo`=:mo LIMIT 1');
            $pdo_model->execute(['br' => $brand_code, 'mo' => $model_code]);
            foreach ($pdo_model as $model_row) {
                $model_name = $model_row['mo_nm'];
            }
        }
    }

    if (!empty($brand_name) && !empty($model_name) && $z2 === 'cars') {
        switch ($current_lang) {
            case 'ru':
                $sa['meta']['ttl'] = "{$brand_name} {$model_name} в Молдове [{$cy}] — Кредит и лизинг";
                $sa['meta']['dsc'] = "{$brand_name} {$model_name} в Молдове. Проверенные авто, гарантия. Кредит, лизинг, Trade-in за 1 день, тест-драйв бесплатно.";
                $sa['meta']['h1']  = "{$brand_name} {$model_name}";
                break;
            case 'en':
                $sa['meta']['ttl'] = "{$brand_name} {$model_name} in Moldova [{$cy}] — Credit & Leasing";
                $sa['meta']['dsc'] = "{$brand_name} {$model_name} in Moldova. Inspected cars, warranty. Credit, leasing, 1-day Trade-in, free test-drive.";
                $sa['meta']['h1']  = "{$brand_name} {$model_name}";
                break;
            default:
                $sa['meta']['ttl'] = "{$brand_name} {$model_name} în Moldova [{$cy}] — Credit & Leasing";
                $sa['meta']['dsc'] = "{$brand_name} {$model_name} în Moldova. Mașini verificate, garanție. Credit, leasing, Trade-in în 1 zi, test-drive gratuit.";
                $sa['meta']['h1']  = "{$brand_name} {$model_name}";
                break;
        }
    }

    // On pages with multiple filters or sorting, signal noindex to avoid duplicate content
    // (canonical in head.php already points to the base version)
    if ($has_multi_filter) {
        // Keep crawlable so Google discovers links, but not indexable
        $zrbt = 'noindex, follow';
    }
}

// =====================================================================
// SEO2 DATABASE OVERRIDE (DISABLED) — main pages now use optimized meta from this file
// =====================================================================
// The _seo2 table contains legacy texts (pre-SEO optimization) that would overwrite
// the tuned titles/descriptions defined above. Code is now the source of truth for
// main pages (home, cars, ordercars, services, tyres, rent, credit, contacts, about,
// privacy, terms, warranty, tradein, order, calculator). The _seo2 rows are kept
// untouched in the DB; to re-enable override (e.g. for marketing-driven A/B testing
// from the admin panel), uncomment the block below.
//
// if (in_array($z2, $url_arr)) {
//     $z2_db = $z2 === '' ? 'home' : $z2;
//     if ($z3 === '' && !isset($t_mp[3]) && !isset($q_mp[1])) {
//         $pdo = $db->prepare('SELECT `ttl`, `h1`, `dsc`, `kwd` FROM '.$prefx.'_seo2 USE INDEX (altp) WHERE `lng`=:lng AND `tp`="main" AND `p1`=:p1 ');
//         $pdo->execute(['lng' => $zlng, 'p1' => $z2_db]);
//         foreach ($pdo as $row) {
//             if (!empty($row['ttl'])) { $sa['meta']['ttl'] = $row['ttl']; }
//             if (!empty($row['h1']))  { $sa['meta']['h1']  = $row['h1']; }
//             if (!empty($row['dsc'])) { $sa['meta']['dsc'] = $row['dsc']; }
//             if (!empty($row['kwd'])) { $sa['meta']['kwd'] = $row['kwd']; }
//         }
//     }
// }

// =====================================================================
// OUTPUT META TAGS
// =====================================================================
echo '
<meta http-equiv="Content-type" content="text/html; charset=UTF-8" />
<meta name="robots" content="'.$zrbt.'" />
<meta name="viewport" content="width=device-width, initial-scale=1, minimum-scale=1, maximum-scale=1, user-scalable=no" id="mobile_viewport" />

<title>'.htmlspecialchars($sa['meta']['ttl'], ENT_QUOTES, 'UTF-8').'</title>
<meta name="description" content="'.htmlspecialchars($sa['meta']['dsc'], ENT_QUOTES, 'UTF-8').'" />
<meta name="keywords" content="'.htmlspecialchars($sa['meta']['kwd'], ENT_QUOTES, 'UTF-8').'" />

<meta property="og:title" content="'.htmlspecialchars($sa['meta']['ttl'], ENT_QUOTES, 'UTF-8').'">
<meta property="og:description" content="'.htmlspecialchars($sa['meta']['dsc'], ENT_QUOTES, 'UTF-8').'">
<meta property="og:type" content="website">
<meta property="og:image" content="'.$sa['it']['img'].'">
<meta property="og:image:width" content="1200">
<meta property="og:image:height" content="630">
<meta property="og:image:type" content="image/jpeg">
<meta property="og:site_name" content="Sauto.md">
<meta property="og:url" content="'.($_SERVER['REQUEST_SCHEME'] ?? 'https').'://'.$_SERVER['HTTP_HOST'].$_SERVER['REQUEST_URI'].'">
<meta name="twitter:card" content="summary_large_image">
<meta name="twitter:image" content="'.$sa['it']['img'].'">
';

unset($zrbt);
