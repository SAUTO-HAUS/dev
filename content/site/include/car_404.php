<?php defined( '_DOIT' ) or die( 'Restricted access' );

// Renders the "car sold / not found" 404 content into $rtrn, to be shown inside the normal
// site layout. Expects: $car_card (closure), $t_mp, $_COOKIE['lang'], $GLOBALS['not_found_car'].
// Used by both /cars/<id> and /ordercars/<id> detail pages when the car no longer exists.

$c4_section = (isset($t_mp[2]) && in_array($t_mp[2], ['cars', 'ordercars'], true)) ? $t_mp[2] : 'cars';
$c4_lang    = $_COOKIE['lang'] ?? 'ro';

$c4_txt = [
    'ro' => [
        'msg1' => 'Acest automobil a fost deja vândut.',
        'msg2' => 'Verificați ofertele disponibile — stocul se actualizează zilnic.',
        'btn'  => ($c4_section === 'cars') ? 'Automobile în stoc' : 'Automobile la comandă',
    ],
    'ru' => [
        'msg1' => 'Этот автомобиль уже продан.',
        'msg2' => 'Ознакомьтесь с доступными предложениями — каталог обновляется ежедневно.',
        'btn'  => ($c4_section === 'cars') ? 'Автомобили в наличии' : 'Автомобили под заказ',
    ],
    'en' => [
        'msg1' => 'This vehicle has already been sold.',
        'msg2' => 'Check the available offers — our stock is updated daily.',
        'btn'  => ($c4_section === 'cars') ? 'Cars in stock' : 'Cars on order',
    ],
];
$c4_l = $c4_txt[$c4_lang] ?? $c4_txt['ro'];

// Similar cars: same brand/model as the sold car when known, else top cars from the catalog.
$c4_cards = '';
if (isset($car_card) && $car_card instanceof Closure) {
    $c4_req = ['tg' => 'fltr'];
    $c4_car = $GLOBALS['not_found_car'] ?? null;
    if ($c4_car && !empty($c4_car['br'])) {
        $c4_req['br'] = $c4_car['br'];
        if (!empty($c4_car['mo'])) { $c4_req['mo'] = $c4_car['mo']; }
    }
    $c4_card = $car_card('fltr', 8, $c4_req, 'av', 0, true);
    if (empty($c4_card['qu'])) {
        $c4_card = $car_card('top', 8, null);
    }
    $c4_cards = $c4_card['txt'] ?? '';
}

$rtrn .= '<div class="gr car404">';
$rtrn .= '<div class="c404_head">';
$rtrn .= '<img class="c404_logo" src="/'._SITE_IMG.'/logo.png" alt="Sauto" />';
$rtrn .= '<div class="c404_big">404</div>';
$rtrn .= '<p class="c404_msg"><span>'.$c4_l['msg1'].'</span><span>'.$c4_l['msg2'].'</span></p>';
$rtrn .= '<a class="c404_cta" href="/'.$c4_lang.'/'.$c4_section.'">'.$c4_l['btn'].'</a>';
$rtrn .= '</div>';
if (!empty($c4_cards)) {
    $rtrn .= '<div class="cnt list">'.$c4_cards.'</div>';
}
$rtrn .= '</div>';

$rtrn .= '<style>
.car404 .c404_head {text-align:center; margin:2rem auto 0; max-width:680px;}
.car404 .c404_logo {display:block; width:150px; max-width:45%; height:auto; margin:0 auto 1rem; filter:grayscale(1); opacity:.85;}
.car404 .c404_big {font-family:"def_l",Arial,sans-serif; font-weight:800; font-size:6rem; line-height:1; color:#e2001a; margin:0 0 1rem; letter-spacing:2px;}
.car404 .c404_msg {font-size:1.3rem; line-height:1.5; color:#333; font-weight:600; margin:0 0 1.6rem;}
.car404 .c404_msg span {display:block;}
.car404 .c404_cta {display:inline-block; background:#e2001a; color:#fff; font-weight:700; font-size:1rem; text-transform:uppercase; letter-spacing:.5px; text-decoration:none; padding:.9rem 2.2rem; border-radius:.5rem; transition:background .2s, transform .15s;}
.car404 .c404_cta:hover {background:#c40017; color:#fff; transform:translateY(-2px);}
@media (max-width:767px){
  .car404 .c404_head {margin:1.2rem auto 0; padding:0 1rem;}
  .car404 .c404_logo {width:110px; margin-bottom:.8rem;}
  .car404 .c404_big {font-size:3.4rem; margin-bottom:.6rem;}
  .car404 .c404_msg {font-size:1rem; line-height:1.45; margin-bottom:1.2rem;}
  .car404 .c404_cta {font-size:.9rem; padding:.8rem 1.6rem;}
}
</style>';
