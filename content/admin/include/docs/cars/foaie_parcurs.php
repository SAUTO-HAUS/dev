<?php defined( '_DOIT' ) or die( 'Restricted access' );

$abr = 'FP';

// Auto-increment number for foaie de parcurs starting from 40992836
$fp_start_nr = 40992836;
if (isset($cont_n)) {
	$fp_nr = $fp_start_nr + intval($cont_n) - 1;
} else {
	$fp_nr = $fp_start_nr;
}

$rtrn = '
<style>
	.fp_page {font-family:"Times New Roman", Times, serif !important; background-color:#f0f0f0 !important;}
	.fp_page .top-right {position:absolute; top:5mm; right:5mm; text-align:right; font-size:0.7rem; line-height:1.4; font-weight:bold;}
	</style>
<div id="p_cont" class="base">
	<div class="pg fp_page">
		<div class="top-right">Formular tipizat<br>Типовая форма</div>
		
		<div style="text-align:center; font-size:0.65rem; padding-top:10mm;">Aprobat prin Ordinul comun al Departamentului Statisticii al Republicii Moldova şi Ministerului Finantelor al Republicii Moldova nr.24/36 din 25 martie 1998<br>Утверждена совместным приказом Департамента статистики и Министерства финансов Республики Молдова № 24/36 от 25 марта 1998 г.</div>
		
		<div style="display:flex; justify-content:space-between; align-items:flex-start; margin-top:5mm;">
			<div style="text-align:center; width:30%;">
				<div style="font-size:0.7rem;">Ştampila transportatorului</div>
				<div style="font-size:0.65rem;">Печать (штамп) перевозчика</div>
			</div>
			<div style="text-align:center; width:40%;">
				<div style="font-size:1rem; font-weight:bold;">FOAIE DE PARCURS PENTRU<br>AUTOCAMIOANE</div>
				<div style="font-size:0.8rem;">ПУТЕВОЙ ЛИСТ ГРУЗОВОГО АВТОМОБИЛЯ</div>
			</div>
			<div style="text-align:center; width:30%;">
				<div style="font-size:0.8rem;">SERIA&nbsp;&nbsp;&nbsp;&nbsp;<b>DAA&nbsp;&nbsp;&nbsp;&nbsp;Nr. '.$fp_nr.'</b></div>
			</div>
		</div>
	</div>
</div>';

echo $rtrn;
?>
