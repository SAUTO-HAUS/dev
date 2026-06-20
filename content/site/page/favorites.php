<?php defined( '_DOIT' ) or die( 'Restricted access' );

$lang = $_COOKIE['lang'] ?? 'ro';

$titles = [
	'ro' => 'Favorite',
	'ru' => 'Избранное',
	'en' => 'Favorites',
];
$empty = [
	'ro' => 'Nu ai niciun automobil în favorite',
	'ru' => 'У вас нет избранных автомобилей',
	'en' => 'You have no favorite cars yet',
];
$ttl  = $titles[$lang] ?? $titles['ro'];
$emp  = $empty[$lang]  ?? $empty['ro'];

$sa['meta']['h1']  = $ttl;
$sa['meta']['ttl'] = $ttl . ' | Sauto.md';

echo '<div class="gr fav-page">';
echo '<h1>'.$ttl.'</h1>';
echo '<div id="fav_loading" class="fav-loading" style="display:none;">…</div>';
echo '<div id="fav_empty" class="fav-empty" style="display:none;">'.$emp.'</div>';
echo '<div class="cnt list" id="fav_container" data-empty-text="'.htmlspecialchars($emp, ENT_QUOTES).'"></div>';
echo '</div>';
