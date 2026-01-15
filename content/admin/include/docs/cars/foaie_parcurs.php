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
		
		<div class="fp-tables" style="margin-top:5mm;">
			<!-- Rând 1: 4 coloane la 70% din dreapta -->
			<table style="width:70%; border-collapse:collapse; margin-left:30%;">
				<tr>
					<td style="width:5%; border:1px solid #000; border-bottom:1px solid #fff; padding:1mm; text-align:center; font-size:0.7rem; font-weight:bold;">1</td>
					<td style="width:45%; border:1px solid #000; border-bottom:1px solid #fff; padding:1mm; font-size:0.7rem;"><div>Data emiterii</div><div>Дата выдачи</div></td>
					<td style="width:5%; border:1px solid #000; border-bottom:1px solid #fff; padding:1mm; text-align:center; font-size:0.7rem; font-weight:bold;">2</td>
					<td style="width:45%; border:1px solid #000; border-bottom:1px solid #fff; padding:1mm; font-size:0.7rem;"><div>Nr. diagramă tahograf</div><div>№ диаграммы тахографа</div></td>
				</tr>
			</table>
			
			<!-- Rând 2: 6 coloane la 100% -->
			<table style="width:100%; border-collapse:collapse; margin-top:0;">
				<tr>
					<td style="width:20%; border:1px solid #000; padding:1mm; font-size:0.65rem;"><div>Marca autovehiculului, remorcii (semiremorcii)</div><div>Марка автомобиля, прицепа (полуприцепа)</div></td>
					<td style="width:10%; border:1px solid #000; padding:1mm; font-size:0.65rem;"><div>Nr. de înmatriculare</div><div>Регистрационный номер</div></td>
					<td style="width:10%; border:1px solid #000; border-right:1px solid #fff; padding:1mm; font-size:0.65rem;"><div>Nr. inventar</div><div>Гаражный №</div></td>
					<td style="width:25%; border:1px solid #000; border-left:1px solid #fff; padding:1mm; font-size:0.65rem;"><div>Numele şi prenumele şoferilor</div><div>Фамилия и имя водителей</div></td>
					<td style="width:10%; border:1px solid #000; padding:1mm; font-size:0.65rem;"><div>Nr. matricol</div><div>Табельный №</div></td>
					<td style="width:25%; border:1px solid #000; padding:1mm; font-size:0.65rem;"><div>Starea sănătăţii şoferului</div><div>Состояние здоровья водителя</div></td>
				</tr>
			</table>
		</div>
	</div>
</div>';

echo $rtrn;
?>
