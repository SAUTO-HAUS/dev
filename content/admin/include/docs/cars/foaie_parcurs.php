<?php defined( '_DOIT' ) or die( 'Restricted access' );

$abr = 'FP';

// Auto-increment number for foaie de parcurs starting from 40992836
$fp_start_nr = 40992836;
if (isset($cont_n)) {
	$fp_nr = $fp_start_nr + intval($cont_n) - 1;
} else {
	$fp_nr = $fp_start_nr;
}

// Parse autovehicul to extract marca and nr inmatriculare
$autovehicul_marca = '';
$autovehicul_nr = '';
$remorca_marca = '';
$remorca_nr = '';
if (isset($_POST['autovehicul']) && !empty($_POST['autovehicul'])) {
	// Format: "MERCEDES ACTROS - SMM149 | KASSBORHER - X239XC"
	$parts = explode(' | ', $_POST['autovehicul']);
	if (isset($parts[0])) {
		$auto_parts = explode(' - ', $parts[0]);
		if (isset($auto_parts[0])) {
			$autovehicul_marca = trim($auto_parts[0]); // MERCEDES ACTROS
		}
		if (isset($auto_parts[1])) {
			$autovehicul_nr = trim($auto_parts[1]); // SMM149
		}
	}
	if (isset($parts[1])) {
		$remorca_parts = explode(' - ', $parts[1]);
		if (isset($remorca_parts[0])) {
			$remorca_marca = trim($remorca_parts[0]); // KASSBORHER
		}
		if (isset($remorca_parts[1])) {
			$remorca_nr = trim($remorca_parts[1]); // X239XC
		}
	}
}

$sofer_nume = isset($_POST['sofer']) ? $_POST['sofer'] : '';

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
					<td style="width:10%; border:1px solid #000; border-right:0px solid; padding:1mm; font-size:0.65rem;"><div>Nr. inventar</div><div>Гаражный №</div></td>
					<td style="width:25%; border:1px solid #000; border-left:0px solid; padding:1mm; font-size:0.65rem;"><div>Numele şi prenumele şoferilor</div><div>Фамилия и имя водителей</div></td>
					<td style="width:10%; border:1px solid #000; padding:1mm; font-size:0.65rem;"><div>Nr. matricol</div><div>Табельный №</div></td>
					<td style="width:25%; border:1px solid #000; padding:1mm; font-size:0.65rem;"><div>Starea sănătăţii şoferului</div><div>Состояние здоровья водителя</div></td>
				</tr>
			</table>
			
			<!-- Rând 3: 6 coloane cu cifre 3-8 -->
			<table style="width:100%; border-collapse:collapse; margin-top:0;">
				<tr>
					<td style="width:20%; border:1px solid #000; border-top:1px solid #fff; padding:1mm; text-align:center; font-size:0.7rem; font-weight:bold;">3</td>
					<td style="width:10%; border:1px solid #000; border-top:1px solid #fff; padding:1mm; text-align:center; font-size:0.7rem; font-weight:bold;">4</td>
					<td style="width:10%; border:1px solid #000; border-top:1px solid #fff; border-right:0px solid; padding:1mm; text-align:center; font-size:0.7rem; font-weight:bold;">5</td>
					<td style="width:25%; border:1px solid #000; border-top:1px solid #fff; border-left:0px solid; padding:1mm; text-align:center; font-size:0.7rem; font-weight:bold;">6</td>
					<td style="width:10%; border:1px solid #000; border-top:1px solid #fff; padding:1mm; text-align:center; font-size:0.7rem; font-weight:bold;">7</td>
					<td style="width:25%; border:1px solid #000; border-top:1px solid #fff; padding:1mm; text-align:center; font-size:0.7rem; font-weight:bold;">8</td>
				</tr>
			</table>
			
			<!-- Rând 4: 6 coloane cu date din formular -->
			<table style="width:100%; border-collapse:collapse; margin-top:0;">
				<tr>
					<td style="width:20%; border:1px solid #000; border-top:1px solid #fff; padding:1mm; font-size:0.7rem;">'.$autovehicul_marca.'</td>
					<td style="width:10%; border:1px solid #000; border-top:1px solid #fff; padding:1mm; font-size:0.7rem;">'.$autovehicul_nr.'</td>
					<td style="width:10%; border:1px solid #000; border-top:1px solid #fff; border-right:0px solid; padding:1mm; font-size:0.7rem;"></td>
					<td style="width:25%; border:1px solid #000; border-top:1px solid #fff; border-left:0px solid; padding:1mm; font-size:0.7rem;">'.$sofer_nume.'</td>
					<td style="width:10%; border:1px solid #000; border-top:1px solid #fff; padding:1mm; font-size:0.7rem;"></td>
					<td style="width:25%; border:1px solid #000; border-top:1px solid #fff; border-bottom:1px solid #fff; padding:1mm; font-size:0.7rem;"></td>
				</tr>
			</table>
			
			<!-- Rând 5: 6 coloane cu date remorca -->
			<table style="width:100%; border-collapse:collapse; margin-top:0;">
				<tr>
					<td style="width:20%; border:1px solid #000; border-top:1px solid #fff; padding:1mm; font-size:0.7rem;">'.$remorca_marca.'</td>
					<td style="width:10%; border:1px solid #000; border-top:1px solid #fff; padding:1mm; font-size:0.7rem;">'.$remorca_nr.'</td>
					<td style="width:10%; border:1px solid #000; border-top:1px solid #fff; border-right:0px solid; padding:1mm; font-size:0.7rem;"></td>
					<td style="width:25%; border:1px solid #000; border-top:1px solid #fff; border-left:0px solid; padding:1mm; font-size:0.7rem;"></td>
					<td style="width:10%; border:1px solid #000; border-top:1px solid #fff; padding:1mm; font-size:0.7rem;"></td>
					<td style="width:25%; border:1px solid #000; border-top:1px solid #fff; border-bottom:1px solid #fff; padding:1mm; font-size:0.7rem;"></td>
				</tr>
			</table>
			
			<!-- Rând 6: 3 coloane - cifra 9, sarcina soferului, gol -->
			<table style="width:100%; border-collapse:collapse; margin-top:0;">
				<tr>
					<td style="width:3.5%; border:1px solid #000; border-top:1px solid #fff; padding:1mm; text-align:center; font-size:0.7rem; font-weight:bold;">9</td>
					<td style="width:71.5%; border:1px solid #000; border-top:1px solid #fff; padding:1mm; font-size:0.7rem;"><div>Sarcina şoferului:</div><div>Задание водителю:</div></td>
					<td style="width:25%; border:1px solid #000; border-top:1px solid #fff; padding:1mm; font-size:0.7rem;"></td>
				</tr>
			</table>
			
			<!-- Rând 7: 2 coloane - gol, menţiuni speciale -->
			<table style="width:100%; border-collapse:collapse; margin-top:0;">
				<tr>
					<td style="width:75%; border:1px solid #000; border-top:1px solid #fff; padding:1mm; font-size:0.7rem;"></td>
					<td style="width:25%; border:1px solid #000; border-top:1px solid #fff; padding:1mm; font-size:0.7rem;"><div>Menţiuni speciale</div><div>Особые отметки</div></td>
				</tr>
			</table>
			
			<!-- Rând 8: 2 coloane - gol, cifra 10 -->
			<table style="width:100%; border-collapse:collapse; margin-top:0;">
				<tr>
					<td style="width:75%; border:1px solid #000; border-top:1px solid #fff; padding:1mm; font-size:0.7rem;"></td>
					<td style="width:25%; border:1px solid #000; border-top:1px solid #fff; padding:1mm; text-align:center; font-size:0.7rem; font-weight:bold;">10</td>
				</tr>
			</table>
			
			<!-- Rând 9: 2 coloane - starea tehnică, gol -->
			<table style="width:100%; border-collapse:collapse; margin-top:0;">
				<tr>
					<td style="width:75%; border:1px solid #000; border-top:1px solid #fff; padding:1mm; font-size:0.7rem;"><div>Starea tehnică</div><div>Техническое состояние</div></td>
					<td style="width:25%; border:1px solid #000; border-top:1px solid #fff; padding:1mm; font-size:0.7rem;"></td>
				</tr>
			</table>
			
			<!-- Rând 10: 7 coloane header (6 x 12.5% + 1 x 25%) -->
			<table style="width:100%; border-collapse:collapse; margin-top:0;">
				<tr">
					<td style="width:9%; border:1px solid #000; border-top:1px solid #fff; padding:1mm; font-size:0.6rem;"></td>
					<td style="width:9%; border:1px solid #000; border-top:1px solid #fff; padding:1mm; font-size:0.6rem;"><div>Data</div><div>Дата</div></td>
					<td style="width:9%; border:1px solid #000; border-top:1px solid #fff; padding:1mm; font-size:0.6rem;"><div>Ora, min.</div><div>Час., мин.</div></td>
					<td style="width:11.4%; border:1px solid #000; border-top:1px solid #fff; padding:1mm; font-size:0.6rem;"><div>Parcurs zero, km</div><div>Нулевой пробег, км</div></td>
					<td style="width:11.4%; border:1px solid #000; border-top:1px solid #fff; padding:1mm; font-size:0.6rem;"><div>Kilometraj</div><div>Показания спидометра, км</div></td>
					<td style="width:25.2%; border:1px solid #000; border-top:1px solid #fff; padding:0; font-size:0.6rem; vertical-align:top;">
						<div style="border-bottom:1px solid #000; padding:1mm;"><div>Autovehiculul e în stare bună de funcţionare</div><div>Автомобиль в технически исправном состоянии</div></div>
						<div style="display:flex;">
							<div style="width:50%; border-right:1px solid #000; padding:1mm; font-size:0.6rem; display:flex; flex-direction:column; justify-content:center; align-items:center; text-align:center;"><div>Semnătura şoferului</div><div>Подпись водителя</div></div>
							<div style="width:50%; padding:1mm; font-size:0.6rem; display:flex; flex-direction:column; justify-content:center; align-items:center; text-align:center;"><div>Semnătura responsabilului</div><div>Подпись ответственного лица</div></div>
						</div>
					</td>
					<td style="width:25%; border:1px solid #000; border-top:1px solid #fff; padding:0; font-size:0.6rem;">
						<div style="border-bottom:1px solid #000; padding:1mm; height:50%;"></div>
						<div style="padding:1mm; height:50%;"></div>
					</td>
				</tr>
			</table>
			
			<!-- Rând 11: 7 coloane cu numere A, 11, 12, 13, 14, 15, 16 -->
			<table style="width:100%; border-collapse:collapse; margin-top:0;">
				<tr>
					<td style="width:9%; border:1px solid #000; border-top:1px solid #fff; padding:1mm; text-align:center; font-size:0.6rem; font-weight:bold;">A</td>
					<td style="width:9%; border:1px solid #000; border-top:1px solid #fff; padding:1mm; text-align:center; font-size:0.6rem; font-weight:bold;">11</td>
					<td style="width:9%; border:1px solid #000; border-top:1px solid #fff; padding:1mm; text-align:center; font-size:0.6rem; font-weight:bold;">12</td>
					<td style="width:11.4%; border:1px solid #000; border-top:1px solid #fff; padding:1mm; text-align:center; font-size:0.6rem; font-weight:bold;">13</td>
					<td style="width:11.4%; border:1px solid #000; border-top:1px solid #fff; padding:1mm; text-align:center; font-size:0.6rem; font-weight:bold;">14</td>
					<td style="width:25.2%; border:1px solid #000; border-top:1px solid #fff; padding:0; font-size:0.6rem;">
						<div style="display:flex;">
							<div style="width:50%; border-right:1px solid #000; padding:1mm; text-align:center; font-weight:bold; display:flex; align-items:center; justify-content:center;">15</div>
							<div style="width:50%; padding:1mm; text-align:center; font-weight:bold; display:flex; align-items:center; justify-content:center;">16</div>
						</div>
					</td>
					<td style="width:25%; border:1px solid #000; border-top:1px solid #fff; padding:1mm; text-align:center; font-size:0.6rem; font-weight:bold;"></td>
				</tr>
			</table>
		</div>
	</div>
</div>';

echo $rtrn;
?>
