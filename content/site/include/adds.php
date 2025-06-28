<?php defined( '_DOIT' ) or die( 'Restricted access' );

//--- Ru ---
if ($_COOKIE['lang']=='ru'||!isset($_COOKIE['lang'])) {
	$lang_tyre_add_txt1 = 'ЛЕГКО И БЫСТРО';
	$lang_tyre_add_txt2 = 'ШИНЫ В КРЕДИТ ПОД НУЛЕВОЙ ПРОЦЕНТ';
;} 

//--- Ro ---
elseif ($_COOKIE['lang']=='ro') {
	$lang_tyre_add_txt1 = 'UȘOR ȘI RAPID';
	$lang_tyre_add_txt2 = 'ANVELOPE IN CREDIT LA ZERO PROCENTE';
;}

//--- En ---
elseif ($_COOKIE['lang']=='en') {
	$lang_tyre_add_txt1 = 'EASY AND RAPID';
	$lang_tyre_add_txt2 = 'TIRES IN CREDIT AT ZERO PROCENTES';
;}

echo '
<style>
	#promo.tyre {width:100%;}
	#promo.tyre > .img {width:100%; height:125px; background-size:cover; background-position:center; background-repeat:no-repeat;}
		#promo.tyre > .img.top {background-image:url(/'._SITE_IMG.'/tyre1.jpg);}
		#promo.tyre > .img.bottom {background-image:url(/'._SITE_IMG.'/tyre2.jpg);}
	#promo.tyre > .text {width:100%; height:75px; position:relative; overflow:hidden; color:#444; font-weight:bold;}
	#promo.tyre > .text > div {width:100%; height:inherit; box-sizing:border-box; padding:5px; position:absolute; right:-100%; animation:text_move 4s infinite ease-in-out; display:table;}
	#promo.tyre > .text > div.first {animation-delay:0s;}
	#promo.tyre > .text > div.second {animation-delay:2s;}

	#promo.tyre > .text > div > p {display:table-cell; vertical-align:middle; text-align:center;}
	
	@keyframes text_move {  
		0% { right:-100%; filter:none;}
		5%, 45% { right:0; filter:none;}
		50%, 100% {right:100%; filter: blur(3px);}
	}
</style>

<a id="promo" class="tyre" href="/'.$_COOKIE['lang'].'/tyres">
	<div class="img top"></div>
	<div class="text">
		<div class="first"><p>'.$lang_tyre_add_txt1.'</p></div>
		<div class="second"><p>'.$lang_tyre_add_txt2.'</p></div>
	</div>
	<div class="img bottom"></div>
</a>
';

?>