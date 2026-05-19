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
    echo '<meta name="viewport" content="width=800"/>';
    echo '<title>404 - '.$lang_404.'</title>';
    return;
}

if ( isset($t_mp[1]) ){
    if ( in_array($t_mp[1], $lang_arr, true) ){ $zlng = $t_mp[1]; }
    else{ $zlng = 'ro'; }
}else{ $zlng = 'ro'; }

$current_lang = isset($_COOKIE['lang']) ? $_COOKIE['lang'] : $zlng;

$z2 = isset($t_mp[2]) ? $t_mp[2] : '';
$z3 = !isset($t_mp[3])?'':(in_array($z2, ['cars', 'ordercars', 'tyres'])?( toNumber($t_mp[3])>0?toNumber($t_mp[3]):'' ):$t_mp[3]);

// Current year for SEO freshness markers in titles/descriptions (e.g. "['.$cy.']").
// Auto-rolls over on Jan 1st — no need to update titles manually each year.
$cy = date('Y');

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
$sa['meta']['ttl'] = 'Sauto.md — Vânzare auto în Chișinău, Moldova';
$sa['meta']['dsc'] = 'Sauto.md — dealer auto în Chișinău. Mașini din Europa în stoc și la comandă. Credit, leasing, trade-in.';
$sa['meta']['kwd'] = 'sauto, md, auto moldova, masini chisinau';

if ($current_lang == 'ru') {
    $sa['meta']['h1'] = 'Продажа автомобилей в Кишинёве';
} elseif ($current_lang == 'en') {
    $sa['meta']['h1'] = 'Cars for Sale in Chișinău';
} else {
    $sa['meta']['h1'] = 'Vânzări auto în Chișinău';
}

// =====================================================================
// HOME PAGE (/ro, /ru, /en)
// =====================================================================
if ($z2 === '' || $z2 === 'home') {
    switch ($current_lang) {
        case 'ru':
            $sa['meta']['ttl'] = 'Продажа авто Кишинёв ['.$cy.'] — Наличие и заказ из Европы | Sauto.md';
            $sa['meta']['dsc'] = 'Sauto.md — автосалон в Кишинёве: проверенные авто из Европы в наличии и под заказ. ✓ Кредит и лизинг ✓ Trade-in за 1 день ✓ Гарантия. Бесплатная консультация.';
            $sa['meta']['kwd'] = 'продажа авто кишинев, авто молдова, купить машину, автосалон sauto, авто из европы, кредит авто, лизинг авто, trade in';
            $sa['meta']['h1'] =  'Продажа автомобилей в Кишинёве';
            break;
        case 'en':
            $sa['meta']['ttl'] = 'Car Sales Chișinău ['.$cy.'] — In Stock & On Order from Europe | Sauto.md';
            $sa['meta']['dsc'] = 'Sauto.md — dealership in Chișinău: inspected European cars in stock and on order. ✓ Credit & leasing ✓ Trade-in in 1 day ✓ Warranty. Free consultation.';
            $sa['meta']['kwd'] = 'cars moldova, buy car chisinau, sauto dealership, european cars, car credit moldova, car leasing, trade in';
            $sa['meta']['h1'] =  'Car Sales in Chișinău';
            break;
        default: // ro
            $sa['meta']['ttl'] = 'Vânzare auto Chișinău ['.$cy.'] — Stoc și comandă din Europa | Sauto.md';
            $sa['meta']['dsc'] = 'Sauto.md — dealer auto în Chișinău: mașini verificate din Europa, în stoc și la comandă. ✓ Credit și leasing ✓ Trade-in în 1 zi ✓ Garanție. Consultanță gratuită.';
            $sa['meta']['kwd'] = 'vanzare auto chisinau, masini moldova, cumpara masina, sauto dealer, auto din europa, credit auto, leasing auto, trade in';
            $sa['meta']['h1'] =  'Vânzări auto în Chișinău';
            break;
    }
}

// =====================================================================
// CARS - main catalog /ro/cars (no brand/model/id)
// =====================================================================
if ($z2 === 'cars' && $z3 === '' && !isset($q_mp[1])) {
    switch ($current_lang) {
        case 'ru':
            $sa['meta']['ttl'] = 'Купить авто в Кишинёве ['.$cy.'] — Авто в наличии | Sauto.md';
            $sa['meta']['dsc'] = '✓ Проверенные авто из Европы в Кишинёве. Каталог '.$cy.': без пробега по РМ. Кредит, Лизинг, Trade-in, Гарантия. Бесплатный тест-драйв.';
            $sa['meta']['kwd'] = 'каталог авто кишинев, купить машину молдова, авто в наличии, авто из европы, машины sauto, продажа автомобилей';
            $sa['meta']['h1'] = 'Каталог автомобилей в наличии';
            break;
        case 'en':
            $sa['meta']['ttl'] = 'Buy Cars in Chișinău ['.$cy.'] — Cars in Stock | Sauto.md';
            $sa['meta']['dsc'] = '✓ Inspected European cars in Chișinău. '.$cy.' Catalog: no mileage in Moldova. Credit, Leasing, Trade-in, Warranty. Free test drive.';
            $sa['meta']['kwd'] = 'car catalog chisinau, buy car moldova, cars in stock, european cars, sauto cars, car sales';
            $sa['meta']['h1'] = 'Cars in Stock Catalog';
            break;
        default: // ro
            $sa['meta']['ttl'] = 'Cumpără auto în Chișinău ['.$cy.'] — Mașini în stoc | Sauto.md';
            $sa['meta']['dsc'] = '✓ Mașini verificate din Europa la Chișinău. Catalog '.$cy.': fără parcurs prin RM. Credit, Leasing, Trade-in, Garanție. Test-drive gratuit.';
            $sa['meta']['kwd'] = 'catalog auto chisinau, cumpara masina moldova, auto in stoc, masini din europa, automobile sauto, vanzare auto';
            $sa['meta']['h1'] = 'Catalog automobile în stoc';
            break;
    }
}

// =====================================================================
// ORDERCARS - on-order catalog /ro/ordercars
// =====================================================================
if ($z2 === 'ordercars' && $z3 === '' && !isset($q_mp[1])) {
    switch ($current_lang) {
        case 'ru':
            $sa['meta']['ttl'] = 'Авто под заказ из Европы ['.$cy.'] — Экономия до 45% | Sauto.md';
            $sa['meta']['dsc'] = '✓ Авто из Европы под ключ. Экономия до 45% vs салон. Подбор, проверка, доставка за 14 дней, растаможка. Гарантия на каждое авто. Бесплатная консультация.';
            $sa['meta']['kwd'] = 'авто под заказ кишинев, заказать машину из европы, авто под заказ молдова, импорт авто, sauto заказ';
            $sa['meta']['h1'] = 'Автомобили под заказ из Европы';
            break;
        case 'en':
            $sa['meta']['ttl'] = 'Cars on Order from Europe ['.$cy.'] — Save 45% | Sauto.md';
            $sa['meta']['dsc'] = '✓ Cars from Europe turnkey. Save up to 45% vs showroom. Selection, inspection, 14-day delivery, customs. Warranty on every car. Free consultation.';
            $sa['meta']['kwd'] = 'cars on order chisinau, order car from europe, cars on order moldova, car import, sauto order';
            $sa['meta']['h1'] = 'Cars on Order from Europe';
            break;
        default: // ro
            $sa['meta']['ttl'] = 'Auto la comandă din Europa ['.$cy.'] — Economie 45% | Sauto.md';
            $sa['meta']['dsc'] = '✓ Mașini din Europa la cheie. Economie până la 45% vs salon. Selecție, verificare, livrare în 14 zile, vămuire. Garanție inclusă. Consultanță gratuită.';
            $sa['meta']['kwd'] = 'auto la comanda chisinau, comanda masina din europa, auto la comanda moldova, import auto, sauto comanda';
            $sa['meta']['h1'] = 'Automobile la comandă din Europa';
            break;
    }
}

// =====================================================================
// CREDIT
// =====================================================================
if ($z2 === 'credit') {
    if (isset($page_title) && isset($page_description)) {
        $sa['meta']['ttl'] = $page_title;
        $sa['meta']['dsc'] = $page_description;
        $sa['meta']['kwd'] = 'credit auto, finantare auto, credit masina, leasing auto, credit personal auto, credit business auto, sauto credit, Moldova';
    } else {
        switch ($current_lang) {
            case 'ru':
                $sa['meta']['ttl'] = 'Автокредит и лизинг в Молдове ['.$cy.'] — Одобрение за 1 час | Sauto.md';
                $sa['meta']['dsc'] = '✓ Автокредит и лизинг до 5 лет. Одобрение за 1 час по паспорту. Аванс от 10%, без скрытых комиссий. Авто в наличии. Бесплатная консультация эксперта.';
                $sa['meta']['kwd'] = 'автокредит, кредит на авто, финансирование авто, лизинг авто, кредит на машину, sauto кредит, Молдова';
                $sa['meta']['h1'] = 'Автокредит в Молдове';
                break;
            case 'en':
                $sa['meta']['ttl'] = 'Car Loan & Leasing in Moldova ['.$cy.'] — Approved in 1 Hour | Sauto.md';
                $sa['meta']['dsc'] = '✓ Car loan and leasing up to 5 years. Approval in 1 hour with passport only. Down payment from 10%, no hidden fees. Cars in stock. Free expert consultation.';
                $sa['meta']['kwd'] = 'car loan, auto financing, car credit, auto leasing, vehicle financing, sauto credit, Moldova';
                $sa['meta']['h1'] = 'Car Loan in Moldova';
                break;
            default: // ro
                $sa['meta']['ttl'] = 'Credit auto și leasing Moldova ['.$cy.'] — Aprobare 1 oră | Sauto.md';
                $sa['meta']['dsc'] = '✓ Credit auto și leasing până la 5 ani. Aprobare în 1 oră doar cu buletinul. Avans de la 10%, fără comisioane ascunse. Mașini în stoc. Consultanță expert gratuită.';
                $sa['meta']['kwd'] = 'credit auto, finantare auto, credit masina, leasing auto, credit personal auto, credit business auto, sauto credit, Moldova';
                $sa['meta']['h1'] = 'Credit auto în Moldova';
                break;
        }
    }
}

// =====================================================================
// ORDER (information about ordering cars)
// =====================================================================
if ($z2 === 'order') {
    switch ($current_lang) {
        case 'ru':
            $sa['meta']['ttl'] = 'Заказ авто из Европы, США, Кореи ['.$cy.'] — Экономия 45% | Sauto.md';
            $sa['meta']['dsc'] = '✓ Доставка авто из Европы, США и Кореи. Экономия до 45% vs автосалон. Подбор, проверка VIN, доставка за 14 дней, полная растаможка. Гарантия включена.';
            $sa['meta']['kwd'] = 'автомобили под заказ, заказ авто из европы, импорт автомобилей, sauto заказ, автомобили из сша, автомобили из кореи, Молдова';
            $sa['meta']['h1'] = 'Автомобили под заказ из Европы, США и Кореи';
            break;
        case 'en':
            $sa['meta']['ttl'] = 'Order Cars from Europe, USA, Korea ['.$cy.'] — Save 45% | Sauto.md';
            $sa['meta']['dsc'] = '✓ Car delivery from Europe, USA and Korea. Save up to 45% vs dealership. Selection, VIN check, 14-day delivery, full customs. Warranty included.';
            $sa['meta']['kwd'] = 'order cars, import cars, cars from europe, cars from usa, cars from korea, sauto order, Moldova';
            $sa['meta']['h1'] = 'Order Cars from Europe, USA and Korea';
            break;
        default: // ro
            $sa['meta']['ttl'] = 'Auto la comandă Europa, SUA, Coreea ['.$cy.'] — Economie 45% | Sauto.md';
            $sa['meta']['dsc'] = '✓ Livrare auto din Europa, SUA și Coreea. Economie până la 45% vs salon. Selecție, verificare VIN, livrare în 14 zile, vămuire completă. Garanție inclusă.';
            $sa['meta']['kwd'] = 'automobile la comanda, import auto, masini din europa, masini din sua, masini din coreea, sauto comanda, Moldova';
            $sa['meta']['h1'] = 'Automobile la comandă din Europa, SUA și Coreea';
            break;
    }
}

// =====================================================================
// CALCULATOR
// =====================================================================
if ($z2 === 'calculator') {
    switch ($current_lang) {
        case 'ru':
            $sa['meta']['ttl'] = 'Калькулятор растаможки авто Молдова ['.$cy.'] — Бесплатно | Sauto.md';
            $sa['meta']['dsc'] = '✓ Точный расчёт растаможки авто онлайн за 30 секунд. Актуальные акцизы '.$cy.', курс EUR от НБМ, таможенные процедуры. 100% бесплатно, без регистрации. Проверено экспертами.';
            $sa['meta']['kwd'] = 'калькулятор растаможки, растаможка авто молдова, акцизы авто, таможенный калькулятор, sauto растаможка';
            $sa['meta']['h1'] = 'Калькулятор растаможки авто в Молдове';
            break;
        case 'en':
            $sa['meta']['ttl'] = 'Customs Calculator Moldova ['.$cy.'] — Free Online | Sauto.md';
            $sa['meta']['dsc'] = '✓ Accurate car customs calculation online in 30 seconds. Live '.$cy.' excise duties, EUR rate from NBM, customs procedures. 100% free, no registration. Expert-verified.';
            $sa['meta']['kwd'] = 'customs calculator moldova, car import tax, excise duty calculator, vehicle customs moldova, sauto calculator';
            $sa['meta']['h1'] = 'Car Customs Calculator Moldova';
            break;
        default: // ro
            $sa['meta']['ttl'] = 'Calculator vămuire auto Moldova ['.$cy.'] — Gratuit | Sauto.md';
            $sa['meta']['dsc'] = '✓ Calcul vamă auto online în 30 secunde. Accize '.$cy.' actualizate, curs EUR de la BNM, proceduri vamale. 100% gratuit, fără înregistrare. Verificat de experți.';
            $sa['meta']['kwd'] = 'calculator vamuire auto, vamuire auto moldova, accize auto, calculator taxe vamale, sauto calculator';
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
            $sa['meta']['ttl'] = 'Trade-in авто в Кишинёве ['.$cy.'] — Обмен за 1 день | Sauto.md';
            $sa['meta']['dsc'] = '✓ Trade-in за 1 день: бесплатная оценка по рынку, авто на выбор, доплата или возврат разницы. Без скрытых комиссий. Гарантия включена. Запись онлайн за 2 минуты.';
            $sa['meta']['kwd'] = 'trade-in авто Кишинев, обмен авто Sauto, trade in Молдова, обменять машину, оценка авто, покупка авто с доплатой, автосалон trade in, быстрый обмен авто, Sauto trade-in';
            $sa['meta']['h1'] = 'Trade-in авто в Кишинёве';
            break;
        case 'en':
            $sa['meta']['ttl'] = 'Trade-In Car in Chișinău ['.$cy.'] — Swap in 1 Day | Sauto.md';
            $sa['meta']['dsc'] = '✓ Trade-in in 1 day: free market valuation, cars to choose, top-up or refund difference. No hidden fees. Warranty included. Book online in 2 minutes.';
            $sa['meta']['kwd'] = 'trade-in Chisinau, car trade-in Moldova, swap my car, Sauto trade in, car valuation, exchange car with cash top-up, buy car with trade-in, fast car exchange, dealership trade in service';
            $sa['meta']['h1'] = 'Trade-in Cars in Chișinău';
            break;
        default: // ro
            $sa['meta']['ttl'] = 'Trade-in auto Chișinău ['.$cy.'] — Schimb în 1 zi | Sauto.md';
            $sa['meta']['dsc'] = '✓ Trade-in în 1 zi: evaluare gratuită la prețul pieței, mașini la alegere, plată diferență sau retur. Fără comisioane. Garanție inclusă. Programare online în 2 minute.';
            $sa['meta']['kwd'] = 'trade-in auto Chisinau, schimb auto Sauto, evaluare masina, trade in Moldova, cumpara masina cu avans, schimb masina cu diferenta, autoturisme noi si rulate, servicii trade-in, schimb rapid auto';
            $sa['meta']['h1'] = 'Trade-in auto în Chișinău';
            break;
    }
}

// =====================================================================
// RENT (car rental - disabled in menu but URL accessible)
// =====================================================================
if ($z2 === 'rent') {
    switch ($current_lang) {
        case 'ru':
            $sa['meta']['ttl'] = 'Аренда авто в Кишинёве ['.$cy.'] — Прокат с доставкой | Sauto.md';
            $sa['meta']['dsc'] = '✓ Аренда авто в Кишинёве. КАСКО и ОСАГО включены, поддержка 24/7. Без залога. Бесплатная доставка по Кишинёву. Краткосрочно и долгосрочно.';
            $sa['meta']['kwd'] = 'аренда авто кишинев, прокат автомобилей молдова, аренда машины sauto, car rental chisinau, аренда авто на сутки';
            $sa['meta']['h1'] = 'Аренда автомобилей в Кишинёве';
            break;
        case 'en':
            $sa['meta']['ttl'] = 'Car Rental in Chișinău ['.$cy.'] — Hire with Delivery | Sauto.md';
            $sa['meta']['dsc'] = '✓ Car rental in Chișinău. CASCO and RCA insurance included, 24/7 support. No deposit. Free delivery in Chișinău. Short and long-term.';
            $sa['meta']['kwd'] = 'car rental chisinau, rent a car moldova, sauto rental, car hire chisinau, daily car rental';
            $sa['meta']['h1'] = 'Car Rental in Chișinău';
            break;
        default: // ro
            $sa['meta']['ttl'] = 'Închiriere auto Chișinău ['.$cy.'] — Rent a car cu livrare | Sauto.md';
            $sa['meta']['dsc'] = '✓ Închiriere auto în Chișinău. CASCO și RCA incluse, suport 24/7. Fără gaj. Livrare gratuită în Chișinău. Pe termen scurt și lung.';
            $sa['meta']['kwd'] = 'inchiriere auto chisinau, rent a car moldova, inchiriere masina sauto, rent car chisinau, inchiriere auto pe zi';
            $sa['meta']['h1'] = 'Închiriere auto Chișinău';
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
            $sa['meta']['kwd'] = 'sauto контакты, автосалон кишинев адрес, sauto телефон, sauto.md контакты, как добраться sauto, автосалон молдова';
            $sa['meta']['h1'] = 'Контакты Sauto';
            break;
        case 'en':
            $sa['meta']['ttl'] = 'Contact Sauto.md in Chișinău — Phones, Address, Hours';
            $sa['meta']['dsc'] = 'Sauto Chișinău, Calea Moșilor 11 · Call back in 1 minute · Mon-Sat 9:00-19:00. Car sales, credit, trade-in, insurance. Free customer parking.';
            $sa['meta']['kwd'] = 'sauto contacts, dealership chisinau address, sauto phone, sauto.md contact, how to reach sauto, dealership moldova';
            $sa['meta']['h1'] = 'Sauto Contacts';
            break;
        default: // ro
            $sa['meta']['ttl'] = 'Contacte Sauto.md în Chișinău — Telefoane, adresă, program';
            $sa['meta']['dsc'] = 'Sauto Chișinău, Calea Moșilor 11 · Apel într-un minut · L-S 9:00-19:00. Vânzare auto, credit, trade-in, asigurări. Parcare gratuită pentru clienți.';
            $sa['meta']['kwd'] = 'sauto contacte, dealer chisinau adresa, sauto telefon, sauto.md contact, cum sa ajungi sauto, dealer moldova';
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
            $sa['meta']['ttl'] = 'Шины в Кишинёве ['.$cy.'] — Летние, зимние, всесезонные | Sauto.md';
            $sa['meta']['dsc'] = '✓ Большой выбор шин в наличии: летние, зимние, всесезонные. Размеры R13-R22, популярные бренды. Бесплатный шиномонтаж, гарантия 2 года. Доставка по Молдове.';
            $sa['meta']['kwd'] = 'шины кишинев, купить шины молдова, автошины sauto, летние шины, зимние шины, всесезонные шины, шиномонтаж';
            $sa['meta']['h1'] = 'Каталог шин';
            break;
        case 'en':
            $sa['meta']['ttl'] = 'Tyres in Chișinău ['.$cy.'] — Summer, Winter, All-Season | Sauto.md';
            $sa['meta']['dsc'] = '✓ Wide tyre selection in stock: summer, winter, all-season. Sizes R13-R22, popular brands. Free fitting, 2-year warranty. Delivery in Moldova.';
            $sa['meta']['kwd'] = 'tyres chisinau, buy tyres moldova, sauto tyres, summer tyres, winter tyres, all season tyres';
            $sa['meta']['h1'] = 'Tyre Catalog';
            break;
        default: // ro
            $sa['meta']['ttl'] = 'Anvelope Chișinău ['.$cy.'] — Vară, iarnă, all-season | Sauto.md';
            $sa['meta']['dsc'] = '✓ Selecție mare de anvelope în stoc: vară, iarnă, all-season. Dimensiuni R13-R22, branduri populare. Montaj gratuit, garanție 2 ani. Livrare în Moldova.';
            $sa['meta']['kwd'] = 'anvelope chisinau, cumpara anvelope moldova, sauto anvelope, anvelope vara, anvelope iarna, anvelope all season';
            $sa['meta']['h1'] = 'Catalog anvelope';
            break;
    }
}

// =====================================================================
// SERVICES - main catalog /ro/services
// =====================================================================
if ($z2 === 'services' && !isset($t_mp[3])) {
    switch ($current_lang) {
        case 'ru':
            $sa['meta']['ttl'] = 'Услуги Sauto в Кишинёве ['.$cy.'] — Trade-in, страховка | Sauto.md';
            $sa['meta']['dsc'] = '✓ Полный спектр услуг: продажа авто, Trade-in за 1 день, бесплатная оценка, тест-драйв, КАСКО/ОСАГО, заказ из Европы. Профессиональный подход и качественный сервис.';
            $sa['meta']['kwd'] = 'услуги автосалона, sauto услуги, trade in, оценка авто, страхование авто, тест драйв, заказ авто, Кишинёв';
            $sa['meta']['h1'] = 'Услуги Sauto';
            break;
        case 'en':
            $sa['meta']['ttl'] = 'Sauto Services in Chișinău ['.$cy.'] — Trade-in, Insurance | Sauto.md';
            $sa['meta']['dsc'] = '✓ Full service range: car sales, 1-day Trade-in, free valuation, test drive, CASCO/RCA, order from Europe. Professional approach and quality service.';
            $sa['meta']['kwd'] = 'dealership services, sauto services, trade in, car valuation, car insurance, test drive, car order, Chisinau';
            $sa['meta']['h1'] = 'Sauto Services';
            break;
        default: // ro
            $sa['meta']['ttl'] = 'Servicii Sauto Chișinău ['.$cy.'] — Trade-in, asigurări | Sauto.md';
            $sa['meta']['dsc'] = '✓ Gamă completă: vânzare auto, Trade-in în 1 zi, evaluare gratuită, test-drive, CASCO/RCA, comandă din Europa. Abordare profesionistă și servicii de calitate.';
            $sa['meta']['kwd'] = 'servicii dealer auto, sauto servicii, trade in, evaluare auto, asigurari auto, test drive, comanda auto, Chisinau';
            $sa['meta']['h1'] = 'Servicii Sauto';
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
            'sale'           => ['ttl' => 'Vinde mașina rapid în Chișinău ['.$cy.'] — Plată pe loc | Sauto.md', 'dsc' => '✓ Vinde-ți mașina către Sauto în 1 oră. Evaluare gratuită, plată pe loc, fără comisioane ascunse. Cel mai bun preț pe piață, garantat. Cumpărăm orice marcă.', 'h1' => 'Vinde-ți mașina către Sauto'],
            'estimation'     => ['ttl' => 'Evaluare auto gratuită Chișinău ['.$cy.'] — În 15 min | Sauto.md', 'dsc' => '✓ Evaluare gratuită a mașinii în 15 minute. Estimare exactă la prețul pieței, raport detaliat, consultanță expert. 100% fără obligații. Programare online.', 'h1' => 'Evaluare auto'],
            'tradein'        => ['ttl' => 'Trade-in auto Chișinău ['.$cy.'] — Schimb în 1 zi | Sauto.md', 'dsc' => '✓ Trade-in la Sauto în 1 zi: evaluare corectă, mașini la schimb, plata diferenței pe loc, acte rapide. Garanție inclusă. Fără bătăi de cap.', 'h1' => 'Trade-in auto'],
            'insurance'      => ['ttl' => 'Asigurări auto RCA și CASCO în Moldova ['.$cy.'] | Sauto.md', 'dsc' => '✓ RCA, CASCO, asigurare verde. Eliberare rapidă, fără comisioane ascunse. Prețuri avantajoase, consultanță gratuită. Asigurări pentru orice tip de auto.', 'h1' => 'Asigurări auto'],
            'testdrive'      => ['ttl' => 'Test-drive auto gratuit Chișinău ['.$cy.'] | Sauto.md', 'dsc' => '✓ Test-drive gratuit la mașinile din stoc. Programare online în 2 minute, consultant expert, fără obligații. Probează înainte să cumperi.', 'h1' => 'Test-drive auto'],
            'transportation' => ['ttl' => 'Transport auto Europa-Moldova ['.$cy.'] — În 14 zile | Sauto.md', 'dsc' => '✓ Transport auto din Europa în 14 zile: livrare sigură, vămuire completă, asigurare pe traseu. Prețuri transparente, fără surprize. Servicii profesionale.', 'h1' => 'Transport auto'],
            'payment'        => ['ttl' => 'Modalități de plată ['.$cy.'] — Cash, card, credit | Sauto.md', 'dsc' => '✓ 5 modalități de plată: cash, card, transfer, credit auto, leasing. Procesare în 1 oră, fără comisioane ascunse. Cumpărare sigură și rapidă.', 'h1' => 'Modalități de plată'],
            'terms'          => ['ttl' => 'Termeni și condiții Sauto.md ['.$cy.'] — Transparenți', 'dsc' => 'Termenii și condițiile de utilizare ale Sauto.md și de cumpărare auto. Transparent, clar, conform legislației Republicii Moldova. Actualizat '.$cy.'.', 'h1' => 'Termeni și condiții'],
        ],
        'ru' => [
            'sale'           => ['ttl' => 'Продай авто в Кишинёве ['.$cy.'] — Оплата на месте | Sauto.md', 'dsc' => '✓ Продай машину в Sauto за 1 час. Бесплатная оценка, оплата на месте, без скрытых комиссий. Лучшая цена на рынке, гарантировано. Выкупаем любую марку.', 'h1' => 'Продай машину в Sauto'],
            'estimation'     => ['ttl' => 'Оценка авто бесплатно Кишинёв ['.$cy.'] — За 15 мин | Sauto.md', 'dsc' => '✓ Бесплатная оценка авто за 15 минут. Точная рыночная стоимость, подробный отчёт, консультация эксперта. 100% без обязательств. Запись онлайн.', 'h1' => 'Оценка авто'],
            'tradein'        => ['ttl' => 'Trade-in авто Кишинёв ['.$cy.'] — Обмен за 1 день | Sauto.md', 'dsc' => '✓ Trade-in в Sauto за 1 день: честная оценка, авто на выбор, доплата разницы на месте, быстрое оформление. Гарантия включена. Без забот.', 'h1' => 'Trade-in авто'],
            'insurance'      => ['ttl' => 'Страхование авто ОСАГО и КАСКО в Молдове ['.$cy.'] | Sauto.md', 'dsc' => '✓ ОСАГО, КАСКО, зелёная карта. Быстрое оформление, без скрытых комиссий. Выгодные цены, бесплатная консультация. Страхование для любого типа авто.', 'h1' => 'Страхование авто'],
            'testdrive'      => ['ttl' => 'Тест-драйв авто бесплатно Кишинёв ['.$cy.'] | Sauto.md', 'dsc' => '✓ Бесплатный тест-драйв автомобилей со склада. Запись онлайн за 2 минуты, эксперт-консультант, без обязательств. Проверь перед покупкой.', 'h1' => 'Тест-драйв авто'],
            'transportation' => ['ttl' => 'Доставка авто Европа-Молдова ['.$cy.'] — За 14 дней | Sauto.md', 'dsc' => '✓ Доставка авто из Европы за 14 дней: безопасная транспортировка, полная растаможка, страхование в пути. Прозрачные цены. Профессиональные услуги.', 'h1' => 'Транспортировка авто'],
            'payment'        => ['ttl' => 'Способы оплаты ['.$cy.'] — Наличные, карта, кредит | Sauto.md', 'dsc' => '✓ 5 способов оплаты: наличные, карта, перевод, автокредит, лизинг. Оформление за 1 час, без скрытых комиссий. Безопасная и быстрая покупка.', 'h1' => 'Способы оплаты'],
            'terms'          => ['ttl' => 'Условия использования Sauto.md ['.$cy.'] — Прозрачно', 'dsc' => 'Условия использования сайта Sauto.md и покупки авто. Прозрачно, чётко, в соответствии с законодательством РМ. Обновлено в '.$cy.' году.', 'h1' => 'Условия использования'],
        ],
        'en' => [
            'sale'           => ['ttl' => 'Sell Your Car in Chișinău ['.$cy.'] — Instant Payment | Sauto.md', 'dsc' => '✓ Sell your car to Sauto in 1 hour. Free valuation, instant payment, no hidden fees. Best market price, guaranteed. We buy any make.', 'h1' => 'Sell your car to Sauto'],
            'estimation'     => ['ttl' => 'Free Car Valuation Chișinău ['.$cy.'] — In 15 min | Sauto.md', 'dsc' => '✓ Free car valuation in 15 minutes. Accurate market value, detailed report, expert consultation. 100% no obligations. Book online.', 'h1' => 'Car Valuation'],
            'tradein'        => ['ttl' => 'Trade-in Cars Chișinău ['.$cy.'] — Swap in 1 Day | Sauto.md', 'dsc' => '✓ Trade-in at Sauto in 1 day: fair valuation, cars to choose, instant difference payment, fast paperwork. Warranty included. Hassle-free.', 'h1' => 'Trade-in Cars'],
            'insurance'      => ['ttl' => 'Car Insurance RCA and CASCO in Moldova ['.$cy.'] | Sauto.md', 'dsc' => '✓ RCA, CASCO, green card. Fast issuance, no hidden fees. Affordable prices, free consultation. Insurance for any type of car.', 'h1' => 'Car Insurance'],
            'testdrive'      => ['ttl' => 'Free Test-Drive in Chișinău ['.$cy.'] | Sauto.md', 'dsc' => '✓ Free test-drive on cars in stock. Online booking in 2 minutes, expert consultant, no obligations. Try before you buy.', 'h1' => 'Test-Drive'],
            'transportation' => ['ttl' => 'Car Transport Europe-Moldova ['.$cy.'] — In 14 Days | Sauto.md', 'dsc' => '✓ Car transport from Europe in 14 days: safe delivery, full customs, transit insurance. Transparent prices, no surprises. Professional service.', 'h1' => 'Car Transportation'],
            'payment'        => ['ttl' => 'Payment Methods ['.$cy.'] — Cash, Card, Credit | Sauto.md', 'dsc' => '✓ 5 payment methods: cash, bank card, transfer, car loan, leasing. Processing in 1 hour, no hidden fees. Safe and fast purchase.', 'h1' => 'Payment Methods'],
            'terms'          => ['ttl' => 'Terms and Conditions Sauto.md ['.$cy.'] — Transparent', 'dsc' => 'Terms and conditions of using Sauto.md and car purchase. Transparent, clear, compliant with Moldova legislation. Updated '.$cy.'.', 'h1' => 'Terms and Conditions'],
        ],
    ];
    $lang_key = isset($service_titles[$current_lang]) ? $current_lang : 'ro';
    if (isset($service_titles[$lang_key][$service_slug])) {
        $sa['meta']['ttl'] = $service_titles[$lang_key][$service_slug]['ttl'];
        $sa['meta']['dsc'] = $service_titles[$lang_key][$service_slug]['dsc'];
        $sa['meta']['h1']  = $service_titles[$lang_key][$service_slug]['h1'];
        $sa['meta']['kwd'] = 'sauto, '.$service_slug.', servicii auto, chisinau, moldova';
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
            'about'    => ['ttl' => 'Despre Sauto.md — Dealer auto în Chișinău, Moldova', 'dsc' => '✓ Sauto — dealer auto de încredere din Chișinău, Moldova. Mașini importate din Europa, servicii complete: credit auto, leasing, trade-in, asigurări. Cumpărați cu încredere.', 'h1' => 'Despre Sauto'],
            'privacy'  => ['ttl' => 'Politica de confidențialitate Sauto.md [GDPR '.$cy.']', 'dsc' => 'Politica de confidențialitate Sauto.md actualizată '.$cy.'. Cum colectăm, folosim și protejăm datele tale personale conform GDPR și legislației Republicii Moldova.', 'h1' => 'Politica de confidențialitate'],
            'terms'    => ['ttl' => 'Termeni și condiții Sauto.md ['.$cy.'] — Transparenți', 'dsc' => 'Termenii și condițiile de utilizare a site-ului Sauto.md și de cumpărare automobile. Clar, transparent, conform legii. Actualizat '.$cy.'.', 'h1' => 'Termeni și condiții'],
            'warranty' => ['ttl' => 'Garanție auto Sauto ['.$cy.'] — 100% verificate tehnic | Sauto.md', 'dsc' => '✓ Toate mașinile Sauto au garanție și sunt verificate tehnic. Asistență 24/7, condiții transparente. Cumpărați cu încredere — verificat înainte de livrare.', 'h1' => 'Garanție auto Sauto'],
        ],
        'ru' => [
            'about'    => ['ttl' => 'О Sauto.md — Автодилер в Кишинёве, Молдова', 'dsc' => '✓ Sauto — надёжный автодилер в Кишинёве, Молдова. Импорт авто из Европы, полный спектр услуг: автокредит, лизинг, trade-in, страхование. Покупайте с уверенностью.', 'h1' => 'О компании Sauto'],
            'privacy'  => ['ttl' => 'Политика конфиденциальности Sauto.md [GDPR '.$cy.']', 'dsc' => 'Политика конфиденциальности Sauto.md обновлена '.$cy.'. Как мы собираем, используем и защищаем ваши данные согласно GDPR и законодательству РМ.', 'h1' => 'Политика конфиденциальности'],
            'terms'    => ['ttl' => 'Условия использования Sauto.md ['.$cy.'] — Прозрачно', 'dsc' => 'Условия использования сайта Sauto.md и покупки автомобилей. Прозрачно, чётко, в соответствии с законом. Обновлено '.$cy.'.', 'h1' => 'Условия использования'],
            'warranty' => ['ttl' => 'Гарантия авто Sauto ['.$cy.'] — 100% проверены | Sauto.md', 'dsc' => '✓ Все авто Sauto имеют гарантию и техническую проверку. Поддержка 24/7, прозрачные условия. Покупайте с уверенностью — проверено до доставки.', 'h1' => 'Гарантия авто Sauto'],
        ],
        'en' => [
            'about'    => ['ttl' => 'About Sauto.md — Car Dealer in Chișinău, Moldova', 'dsc' => '✓ Sauto — trusted car dealer in Chișinău, Moldova. Cars imported from Europe, full services: car loan, leasing, trade-in, insurance. Buy with confidence.', 'h1' => 'About Sauto'],
            'privacy'  => ['ttl' => 'Privacy Policy Sauto.md [GDPR '.$cy.']', 'dsc' => 'Sauto.md Privacy Policy updated '.$cy.'. How we collect, use and protect your personal data in accordance with GDPR and Moldova legislation.', 'h1' => 'Privacy Policy'],
            'terms'    => ['ttl' => 'Terms and Conditions Sauto.md ['.$cy.'] — Transparent', 'dsc' => 'Terms and conditions of using Sauto.md website and purchasing cars. Clear, transparent, compliant with the law. Updated '.$cy.'.', 'h1' => 'Terms and Conditions'],
            'warranty' => ['ttl' => 'Sauto Car Warranty ['.$cy.'] — 100% Inspected | Sauto.md', 'dsc' => '✓ All Sauto cars have warranty and technical inspection. 24/7 support, transparent conditions. Buy with confidence — verified before delivery.', 'h1' => 'Sauto Car Warranty'],
        ],
    ];
    $lang_key = isset($info_meta[$current_lang]) ? $current_lang : 'ro';
    if (isset($info_meta[$lang_key][$z2])) {
        $sa['meta']['ttl'] = $info_meta[$lang_key][$z2]['ttl'];
        $sa['meta']['dsc'] = $info_meta[$lang_key][$z2]['dsc'];
        $sa['meta']['h1']  = $info_meta[$lang_key][$z2]['h1'];
        $sa['meta']['kwd'] = 'sauto, '.$z2.', auto, chisinau, moldova';
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
            // Build a compact, CTR-friendly title: "Brand Model Year, Type Trans Color — Price€ | Sauto.md"
            $title_core = $sa['it']['r']['br_nm'].' '.$sa['it']['r']['mo_nm'];
            if (!empty($sa['it']['r']['yr'])) $title_core .= ' '.$sa['it']['r']['yr'];
            $title_attrs = [];
            if (!empty($sa['it']['r']['bt']) && isset($lng['l']['car']['bt'][$sa['it']['r']['bt']])) $title_attrs[] = $lng['l']['car']['bt'][$sa['it']['r']['bt']];
            if (!empty($sa['it']['r']['tra']) && isset($lng['l']['car']['tra'][$sa['it']['r']['tra']])) $title_attrs[] = $lng['l']['car']['tra'][$sa['it']['r']['tra']];
            if (!empty($sa['it']['r']['clr']) && isset($lng['l']['car']['clr'][$sa['it']['r']['clr']])) $title_attrs[] = $lng['l']['car']['clr'][$sa['it']['r']['clr']];
            $title_built = $title_core;
            if (!empty($title_attrs)) $title_built .= ', '.implode(' ', $title_attrs);
            if (!empty($sa['it']['r']['prc'])) $title_built .= ' — '.$sa['it']['r']['prc'].$cur_sym;
            $title_built .= ' | Sauto.md';

            $sa['meta']['ttl'] = ($seo_ir == 1 && $seo_r['ttl'] != '') ? $seo_r['ttl'] : $title_built;
            $sa['meta']['h1']  = ($seo_ir == 1 && $seo_r['h1']  != '') ? $seo_r['h1']  : $sa['it']['r']['br_nm'].' '.$sa['it']['r']['mo_nm'].', id-'.$sa['it']['r']['id'];

            // Build a richer description with trigger words (✓ Verified ✓ Warranty ✓ Credit)
            $desc_parts = [];
            $desc_parts[] = '✓ '.$sa['it']['r']['br_nm'].' '.$sa['it']['r']['mo_nm'];
            if (!empty($sa['it']['r']['yr'])) $desc_parts[] = $sa['it']['r']['yr'];
            if (!empty($sa['it']['r']['clr']) && isset($lng['l']['car']['clr'][$sa['it']['r']['clr']])) $desc_parts[] = $lng['l']['car']['clr'][$sa['it']['r']['clr']];
            if (!empty($sa['it']['r']['prc'])) $desc_parts[] = '— '.$sa['it']['r']['prc'].$cur_sym;
            $desc_parts[] = $lng['t']['seo']['car_inf_dsc'];

            $sa['meta']['dsc'] = ($seo_ir == 1 && $seo_r['dsc'] != '') ? $seo_r['dsc'] : implode(' ', $desc_parts);
            $sa['meta']['kwd'] = ($seo_ir == 1 && $seo_r['kwd'] != '') ? $seo_r['kwd'] : mb_strtolower('md,'.$lng['w']['moldova'].','.$lng['w']['chisinau'].','.$lng['w']['sale'].','.$lng['w']['auto'].','.$lng['w']['buy'].','.$sa['it']['r']['br'].','.$sa['it']['r']['mo'].','.$lng['l'][$zl]['bt'][$sa['it']['r']['bt']].','.$lng['l'][$zl]['tra'][$sa['it']['r']['tra']].','.$lng['l'][$zl]['fl'][$sa['it']['r']['fl']].','.$lng['l'][$zl]['clr'][$sa['it']['r']['clr']].',id'.$sa['it']['r']['id'], "UTF-8");
        }
        elseif ($z2 == 'ordercars'){
            // Order car title: "[Prefix] Brand Model Year, attrs — Price€ | Sauto.md"
            $title_core = $lng['t']['seo']['order_car_prefix'].' '.$sa['it']['r']['br_nm'].' '.$sa['it']['r']['mo_nm'];
            if (!empty($sa['it']['r']['yr'])) $title_core .= ' '.$sa['it']['r']['yr'];
            $title_attrs = [];
            if (!empty($sa['it']['r']['bt']) && isset($lng['l']['car']['bt'][$sa['it']['r']['bt']])) $title_attrs[] = $lng['l']['car']['bt'][$sa['it']['r']['bt']];
            if (!empty($sa['it']['r']['tra']) && isset($lng['l']['car']['tra'][$sa['it']['r']['tra']])) $title_attrs[] = $lng['l']['car']['tra'][$sa['it']['r']['tra']];
            if (!empty($sa['it']['r']['clr']) && isset($lng['l']['car']['clr'][$sa['it']['r']['clr']])) $title_attrs[] = $lng['l']['car']['clr'][$sa['it']['r']['clr']];
            $title_built = $title_core;
            if (!empty($title_attrs)) $title_built .= ', '.implode(' ', $title_attrs);
            if (!empty($sa['it']['r']['prc'])) $title_built .= ' — '.$sa['it']['r']['prc'].$cur_sym;
            $title_built .= ' | Sauto.md';

            $sa['meta']['ttl'] = ($seo_ir == 1 && $seo_r['ttl'] != '') ? $seo_r['ttl'] : $title_built;
            $sa['meta']['h1']  = ($seo_ir == 1 && $seo_r['h1']  != '') ? $seo_r['h1']  : $sa['it']['r']['br_nm'].' '.$sa['it']['r']['mo_nm'].', id-'.$sa['it']['r']['id'];

            $desc_parts = [];
            $desc_parts[] = '✓ '.$lng['t']['seo']['order_car_dsc'];
            $desc_parts[] = $sa['it']['r']['br_nm'].' '.$sa['it']['r']['mo_nm'];
            if (!empty($sa['it']['r']['yr'])) $desc_parts[] = $sa['it']['r']['yr'];
            if (!empty($sa['it']['r']['clr']) && isset($lng['l']['car']['clr'][$sa['it']['r']['clr']])) $desc_parts[] = $lng['l']['car']['clr'][$sa['it']['r']['clr']];
            if (!empty($sa['it']['r']['prc'])) $desc_parts[] = '— '.$sa['it']['r']['prc'].$cur_sym;
            $desc_parts[] = $lng['t']['seo']['order_car_dsc_end'];

            $sa['meta']['dsc'] = ($seo_ir == 1 && $seo_r['dsc'] != '') ? $seo_r['dsc'] : implode(' ', $desc_parts);
            $sa['meta']['kwd'] = ($seo_ir == 1 && $seo_r['kwd'] != '') ? $seo_r['kwd'] : mb_strtolower('md,'.$lng['w']['moldova'].','.$lng['w']['chisinau'].','.$lng['w']['sale'].','.$lng['w']['auto'].','.$lng['w']['buy'].','.$sa['it']['r']['br'].','.$sa['it']['r']['mo'].','.$lng['l'][$zl]['bt'][$sa['it']['r']['bt']].','.$lng['l'][$zl]['tra'][$sa['it']['r']['tra']].','.$lng['l'][$zl]['fl'][$sa['it']['r']['fl']].','.$lng['l'][$zl]['clr'][$sa['it']['r']['clr']].',id'.$sa['it']['r']['id'], "UTF-8");
        }
        elseif ($z2 == 'tyres'){
            // Tyre title: "Brand 205/55 R16 — Season — 80€ | Sauto.md"
            $tyre_size = $sa['it']['r']['w'].'/'.$sa['it']['r']['h'].' R'.$sa['it']['r']['d'].($sa['it']['r']['c']==1?'C':'');
            $tyre_ttl = $sa['it']['r']['br'].' '.$tyre_size.' — '.$lng['l']['tyre']['ss'][$sa['it']['r']['ss']].' — '.$sa['it']['r']['prc'].$sa['it']['r']['cur'].' | Sauto.md';
            $tyre_dsc = '✓ '.$lng['w']['sale'].' '.(mb_strtolower($lng['w']['tyres'], "UTF-8")).' '.$sa['it']['r']['br'].' '.$tyre_size.' '.$lng['u']['for'].' '.$sa['it']['r']['prc'].$sa['it']['r']['cur'].' '.$lng['u']['in'].' '.$lng['w']['chisinau'].'. Montaj gratuit, garanție 2 ani.';

            $sa['meta']['ttl'] = ($seo_ir == 1 && $seo_r['ttl'] != '') ? $seo_r['ttl'] : $tyre_ttl;
            $sa['meta']['h1']  = ($seo_ir == 1 && $seo_r['h1']  != '') ? $seo_r['h1']  : ( $sa['it']['r']['br'].' '.$tyre_size.', id-'.$sa['it']['r']['id'] );
            $sa['meta']['dsc'] = ($seo_ir == 1 && $seo_r['dsc'] != '') ? $seo_r['dsc'] : $tyre_dsc;
            $sa['meta']['kwd'] = ($seo_ir == 1 && $seo_r['kwd'] != '') ? $seo_r['kwd'] : mb_strtolower('md,'.$lng['w']['moldova'].','.$lng['w']['chisinau'].','.$lng['w']['sale'].','.$lng['w']['tyres'].','.$lng['w']['buy'].','.$sa['it']['r']['br'].','.$sa['it']['r']['w'].','.$sa['it']['r']['h'].',r'.$sa['it']['r']['d'].($sa['it']['r']['c']==1?'c':'').','.$lng['l']['tyre']['ss'][$sa['it']['r']['ss']].',id'.$sa['it']['r']['id'], "UTF-8");
        }
    }
}

// =====================================================================
// CARS / ORDERCARS - BRAND / BRAND+MODEL pages (clean URL)
// /ro/cars/ford, /ro/cars/ford/focus, /ro/ordercars/ford, /ro/ordercars/ford/focus
// =====================================================================
if ( in_array($z2, ['cars', 'ordercars'], true) && !is_numeric($z3) && isset($t_mp[3]) && !empty($t_mp[3]) ) {
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
                    $ttl_extra = $yr_frag !== '' ? " {$yr_frag}" : '';
                    $cnt_frag  = $cnt > 0 ? " — {$cnt} авто" : '';
                    $sa['meta']['ttl'] = "{$brand_name} {$model_name}{$ttl_extra} в Молдове{$cnt_frag} | Sauto.md";
                    $price_txt = $prc_min > 0 ? " Цены от {$prc_min}€." : '';
                    $cnt_dsc   = $cnt > 0 ? "✓ {$cnt} {$brand_name} {$model_name}" : "✓ {$brand_name} {$model_name}";
                    $sa['meta']['dsc'] = "{$cnt_dsc} в Кишинёве.{$price_txt} Все проверены, гарантия включена. Кредит, лизинг, Trade-in, тест-драйв бесплатно. Доставка по Молдове.";
                    $sa['meta']['h1']  = "{$brand_name} {$model_name} в Кишинёве";
                    $sa['meta']['kwd'] = mb_strtolower("{$brand_name}, {$model_name}, купить {$brand_name} {$model_name}, {$brand_name} {$model_name} молдова, {$brand_name} {$model_name} кишинев, sauto", "UTF-8");
                    break;
                case 'en':
                    $ttl_extra = $yr_frag !== '' ? " {$yr_frag}" : '';
                    $cnt_frag  = $cnt > 0 ? " — {$cnt} cars" : '';
                    $sa['meta']['ttl'] = "{$brand_name} {$model_name}{$ttl_extra} in Moldova{$cnt_frag} | Sauto.md";
                    $price_txt = $prc_min > 0 ? " Prices from €{$prc_min}." : '';
                    $cnt_dsc   = $cnt > 0 ? "✓ {$cnt} {$brand_name} {$model_name}" : "✓ {$brand_name} {$model_name}";
                    $sa['meta']['dsc'] = "{$cnt_dsc} in Chișinău.{$price_txt} All inspected, warranty included. Credit, leasing, Trade-in, free test-drive. Delivery in Moldova.";
                    $sa['meta']['h1']  = "{$brand_name} {$model_name} in Chișinău";
                    $sa['meta']['kwd'] = mb_strtolower("{$brand_name}, {$model_name}, buy {$brand_name} {$model_name}, {$brand_name} {$model_name} moldova, {$brand_name} {$model_name} chisinau, sauto", "UTF-8");
                    break;
                default: // ro
                    $ttl_extra = $yr_frag !== '' ? " {$yr_frag}" : '';
                    $cnt_frag  = $cnt > 0 ? " — {$cnt} mașini" : '';
                    $sa['meta']['ttl'] = "{$brand_name} {$model_name}{$ttl_extra} în Moldova{$cnt_frag} | Sauto.md";
                    $price_txt = $prc_min > 0 ? " Prețuri de la {$prc_min}€." : '';
                    $cnt_dsc   = $cnt > 0 ? "✓ {$cnt} {$brand_name} {$model_name}" : "✓ {$brand_name} {$model_name}";
                    $sa['meta']['dsc'] = "{$cnt_dsc} la Chișinău.{$price_txt} Toate verificate, garanție inclusă. Credit, leasing, Trade-in, test-drive gratuit. Livrare în Moldova.";
                    $sa['meta']['h1']  = "{$brand_name} {$model_name} în Chișinău";
                    $sa['meta']['kwd'] = mb_strtolower("{$brand_name}, {$model_name}, cumpara {$brand_name} {$model_name}, {$brand_name} {$model_name} moldova, {$brand_name} {$model_name} chisinau, sauto", "UTF-8");
                    break;
            }
        } else { // ordercars
            switch ($current_lang) {
                case 'ru':
                    $cnt_frag  = $cnt > 0 ? " — {$cnt} вариантов" : '';
                    $sa['meta']['ttl'] = "{$brand_name} {$model_name} под заказ [{$cy}]{$cnt_frag} | Sauto.md";
                    $price_txt = $prc_min > 0 ? " От {$prc_min}€." : '';
                    $sa['meta']['dsc'] = "✓ {$brand_name} {$model_name} под заказ из Европы.{$price_txt} Подбор по VIN, проверка, доставка за 14 дней, растаможка. Экономия до 45%. Гарантия.";
                    $sa['meta']['h1']  = "{$brand_name} {$model_name} под заказ";
                    $sa['meta']['kwd'] = mb_strtolower("{$brand_name}, {$model_name}, заказать {$brand_name} {$model_name}, {$brand_name} {$model_name} под заказ, {$brand_name} {$model_name} из европы, sauto", "UTF-8");
                    break;
                case 'en':
                    $cnt_frag  = $cnt > 0 ? " — {$cnt} options" : '';
                    $sa['meta']['ttl'] = "{$brand_name} {$model_name} on Order [{$cy}]{$cnt_frag} | Sauto.md";
                    $price_txt = $prc_min > 0 ? " From €{$prc_min}." : '';
                    $sa['meta']['dsc'] = "✓ {$brand_name} {$model_name} on order from Europe.{$price_txt} VIN check, 14-day delivery, customs. Save up to 45%. Warranty included.";
                    $sa['meta']['h1']  = "{$brand_name} {$model_name} on Order";
                    $sa['meta']['kwd'] = mb_strtolower("{$brand_name}, {$model_name}, order {$brand_name} {$model_name}, {$brand_name} {$model_name} on order, {$brand_name} {$model_name} from europe, sauto", "UTF-8");
                    break;
                default: // ro
                    $cnt_frag  = $cnt > 0 ? " — {$cnt} variante" : '';
                    $sa['meta']['ttl'] = "{$brand_name} {$model_name} la comandă [{$cy}]{$cnt_frag} | Sauto.md";
                    $price_txt = $prc_min > 0 ? " De la {$prc_min}€." : '';
                    $sa['meta']['dsc'] = "✓ {$brand_name} {$model_name} la comandă din Europa.{$price_txt} Verificare VIN, livrare în 14 zile, vămuire. Economie până la 45%. Garanție inclusă.";
                    $sa['meta']['h1']  = "{$brand_name} {$model_name} la comandă";
                    $sa['meta']['kwd'] = mb_strtolower("{$brand_name}, {$model_name}, comanda {$brand_name} {$model_name}, {$brand_name} {$model_name} la comanda, {$brand_name} {$model_name} din europa, sauto", "UTF-8");
                    break;
            }
        }
    }
    // BRAND only
    elseif (!empty($brand_name) && empty($model_name)) {
        if ($z2 === 'cars') {
            switch ($current_lang) {
                case 'ru':
                    $cnt_frag = $cnt > 0 ? " — {$cnt} авто в наличии" : '';
                    $sa['meta']['ttl'] = "Купить {$brand_name} в Молдове [{$cy}]{$cnt_frag} | Sauto.md";
                    $price_txt = $prc_min > 0 ? " Цены от {$prc_min}€." : '';
                    $cnt_dsc   = $cnt > 0 ? "✓ {$cnt} автомобилей {$brand_name}" : "✓ Каталог {$brand_name}";
                    $sa['meta']['dsc'] = "{$cnt_dsc} в Кишинёве.{$price_txt} Все модели, все года. Проверенные, с гарантией. Кредит, лизинг, Trade-in, тест-драйв бесплатно.";
                    $sa['meta']['h1']  = "Автомобили {$brand_name}";
                    $sa['meta']['kwd'] = mb_strtolower("{$brand_name}, купить {$brand_name}, {$brand_name} молдова, {$brand_name} кишинев, {$brand_name} в наличии, sauto", "UTF-8");
                    break;
                case 'en':
                    $cnt_frag = $cnt > 0 ? " — {$cnt} cars in stock" : '';
                    $sa['meta']['ttl'] = "Buy {$brand_name} in Moldova [{$cy}]{$cnt_frag} | Sauto.md";
                    $price_txt = $prc_min > 0 ? " Prices from €{$prc_min}." : '';
                    $cnt_dsc   = $cnt > 0 ? "✓ {$cnt} {$brand_name} cars" : "✓ {$brand_name} catalog";
                    $sa['meta']['dsc'] = "{$cnt_dsc} in Chișinău.{$price_txt} All models, all years. Inspected, with warranty. Credit, leasing, Trade-in, free test-drive.";
                    $sa['meta']['h1']  = "{$brand_name} Cars";
                    $sa['meta']['kwd'] = mb_strtolower("{$brand_name}, buy {$brand_name}, {$brand_name} moldova, {$brand_name} chisinau, {$brand_name} in stock, sauto", "UTF-8");
                    break;
                default: // ro
                    $cnt_frag = $cnt > 0 ? " — {$cnt} mașini în stoc" : '';
                    $sa['meta']['ttl'] = "Cumpără {$brand_name} în Moldova [{$cy}]{$cnt_frag} | Sauto.md";
                    $price_txt = $prc_min > 0 ? " Prețuri de la {$prc_min}€." : '';
                    $cnt_dsc   = $cnt > 0 ? "✓ {$cnt} mașini {$brand_name}" : "✓ Catalog {$brand_name}";
                    $sa['meta']['dsc'] = "{$cnt_dsc} la Chișinău.{$price_txt} Toate modelele, toți anii. Verificate, cu garanție. Credit, leasing, Trade-in, test-drive gratuit.";
                    $sa['meta']['h1']  = "Automobile {$brand_name}";
                    $sa['meta']['kwd'] = mb_strtolower("{$brand_name}, cumpara {$brand_name}, {$brand_name} moldova, {$brand_name} chisinau, {$brand_name} in stoc, sauto", "UTF-8");
                    break;
            }
        } else { // ordercars
            switch ($current_lang) {
                case 'ru':
                    $cnt_frag = $cnt > 0 ? " — {$cnt} вариантов" : '';
                    $sa['meta']['ttl'] = "{$brand_name} под заказ из Европы [{$cy}]{$cnt_frag} | Sauto.md";
                    $price_txt = $prc_min > 0 ? " От {$prc_min}€." : '';
                    $sa['meta']['dsc'] = "✓ Все модели {$brand_name} под заказ из Европы.{$price_txt} Подбор по VIN, доставка за 14 дней, растаможка. Экономия до 45%. Гарантия включена.";
                    $sa['meta']['h1']  = "{$brand_name} под заказ";
                    $sa['meta']['kwd'] = mb_strtolower("{$brand_name}, {$brand_name} под заказ, заказать {$brand_name}, {$brand_name} из европы, sauto", "UTF-8");
                    break;
                case 'en':
                    $cnt_frag = $cnt > 0 ? " — {$cnt} options" : '';
                    $sa['meta']['ttl'] = "{$brand_name} on Order from Europe [{$cy}]{$cnt_frag} | Sauto.md";
                    $price_txt = $prc_min > 0 ? " From €{$prc_min}." : '';
                    $sa['meta']['dsc'] = "✓ All {$brand_name} models on order from Europe.{$price_txt} VIN check, 14-day delivery, customs. Save up to 45%. Warranty included.";
                    $sa['meta']['h1']  = "{$brand_name} on Order";
                    $sa['meta']['kwd'] = mb_strtolower("{$brand_name}, {$brand_name} on order, order {$brand_name}, {$brand_name} from europe, sauto", "UTF-8");
                    break;
                default: // ro
                    $cnt_frag = $cnt > 0 ? " — {$cnt} variante" : '';
                    $sa['meta']['ttl'] = "{$brand_name} la comandă din Europa [{$cy}]{$cnt_frag} | Sauto.md";
                    $price_txt = $prc_min > 0 ? " De la {$prc_min}€." : '';
                    $sa['meta']['dsc'] = "✓ Toate modelele {$brand_name} la comandă din Europa.{$price_txt} Verificare VIN, livrare în 14 zile, vămuire. Economie până la 45%. Garanție inclusă.";
                    $sa['meta']['h1']  = "{$brand_name} la comandă";
                    $sa['meta']['kwd'] = mb_strtolower("{$brand_name}, {$brand_name} la comanda, comanda {$brand_name}, {$brand_name} din europa, sauto", "UTF-8");
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
                $sa['meta']['ttl'] = "{$brand_name} {$model_name} в Молдове [{$cy}] — Кредит и лизинг | Sauto.md";
                $sa['meta']['dsc'] = "✓ {$brand_name} {$model_name} в Кишинёве. Проверенные авто, гарантия. Кредит, лизинг, Trade-in за 1 день, тест-драйв бесплатно. Sauto.md.";
                $sa['meta']['h1']  = "{$brand_name} {$model_name}";
                break;
            case 'en':
                $sa['meta']['ttl'] = "{$brand_name} {$model_name} in Moldova [{$cy}] — Credit & Leasing | Sauto.md";
                $sa['meta']['dsc'] = "✓ {$brand_name} {$model_name} in Chișinău. Inspected cars, warranty. Credit, leasing, 1-day Trade-in, free test-drive. Sauto.md.";
                $sa['meta']['h1']  = "{$brand_name} {$model_name}";
                break;
            default:
                $sa['meta']['ttl'] = "{$brand_name} {$model_name} în Moldova [{$cy}] — Credit & Leasing | Sauto.md";
                $sa['meta']['dsc'] = "✓ {$brand_name} {$model_name} la Chișinău. Mașini verificate, garanție. Credit, leasing, Trade-in în 1 zi, test-drive gratuit. Sauto.md.";
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
