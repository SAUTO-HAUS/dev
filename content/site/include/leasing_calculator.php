<?php defined( '_DOIT' ) or die( 'Restricted access' ); 

if ( isset($t_mp[2]) && $t_mp[2]!='car' ){
	echo '
	<style>
		.leasing_calculator_text{display:none;}
		.leasing_form {padding-top:20px;}
	</style>
	';
	$c_price = 0;
	$c_prima_rata = 0;
}

echo '
<div class="leasing_calculator_text">'.$lang_leas_calc.'</div>

<div class="leasing_form">
	
	<div class="leasing_info">
		'.$lang_calc_info.'
	</div>

	<span>'.$lang_info_calc_price.', &euro;</span> <br />
	<input type="text" name="calc_price" size="35" placeholder="0" value="'.$c_price.'" class="calc_price calc_input"><br />

	<span>'.$lang_info_calc_rate.'</span> <br />
	<input type="text" name="calc_prima_rata" size="35" placeholder="0" value="'./*$c_prima_rata*/($c_price*0.1).'" class="calc_prima_rata calc_input"><br />

	<span>'.$lang_info_calc_term.'</span> <br />
	<select name="calc_month" class="calc_month calc_input">
		<option value="5">5</option>
		<option value="10">10</option>
		<option value="15">15</option>
		<option value="20">20</option>
		<option value="25">25</option>
		<option value="30" selected="selected">30</option>
		<option value="35">35</option>
		<option value="40">40</option>
		<option value="45">45</option>
		<option value="50">50</option>
		<option value="55">55</option>
		<option value="60">60</option>
	</select> <br />
	
	<div class="calc_answer select"></div>
</div>
';

?>