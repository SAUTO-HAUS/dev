<?php defined( '_DOIT' ) or die( 'Restricted access' );

$abr = 'FP';

$fp_start_nr = 40992836;
if (isset($cont_n)) {
	$fp_nr = $fp_start_nr + intval($cont_n) - 1;
} else {
	$fp_nr = $fp_start_nr;
}

$autovehicul_marca = '';
$autovehicul_nr = '';
$remorca_marca = '';
$remorca_nr = '';
if (isset($_POST['autovehicul']) && !empty($_POST['autovehicul'])) {
	$parts = explode(' | ', $_POST['autovehicul']);
	if (isset($parts[0])) {
		$auto_parts = explode(' - ', $parts[0]);
		if (isset($auto_parts[0])) {
			$autovehicul_marca = trim($auto_parts[0]); 
		}
		if (isset($auto_parts[1])) {
			$autovehicul_nr = trim($auto_parts[1]); 
		}
	}
	if (isset($parts[1])) {
		$remorca_parts = explode(' - ', $parts[1]);
		if (isset($remorca_parts[0])) {
			$remorca_marca = trim($remorca_parts[0]); 
		}
		if (isset($remorca_parts[1])) {
			$remorca_nr = trim($remorca_parts[1]); 
		}
	}
}

$sofer_nume = isset($_POST['sofer']) ? strtoupper($_POST['sofer']) : '';
$data_emiterii_raw = isset($_POST['date']) ? $_POST['date'] : '';
$data_emiterii = '';
if (!empty($data_emiterii_raw)) {
	$date_parts = explode('-', $data_emiterii_raw);
	if (count($date_parts) == 3) {
		$data_emiterii = $date_parts[2] . '.' . $date_parts[1] . '.' . $date_parts[0];
	}
}

$rtrn = '
<style>
	.fp_page {font-family:"Times New Roman", Times, serif;}
	.fp_page .top-right {position:absolute; top:2mm; right:5mm; text-align:right; font-size:0.6rem; line-height:1.4; font-weight:bold;}
	</style>
<div id="p_cont" class="base">
	<div class="pg fp_page">
		<div class="top-right">Formular tipizat<br>Типовая форма</div>
		
		<div style="text-align:center; font-size:0.6rem;">Aprobat prin Ordinul comun al Departamentului Statisticii al Republicii Moldova şi Ministerului Finantelor al Republicii Moldova nr.24/36 din 25 martie 1998<br>Утверждена совместным приказом Департамента статистики и Министерства финансов Республики Молдова № 24/36 от 25 марта 1998 г.</div>
		
		<div style="display:flex; justify-content:space-between; align-items:flex-start; margin-top:2mm;">
			<div style="text-align:center; width:30%;">
				<div style="font-size:0.6rem;">Ştampila transportatorului</div>
				<div style="font-size:0.6rem;">Печать (штамп) перевозчика</div>
			</div>
			<div style="text-align:center; width:40%;">
				<div style="font-size:1rem; font-weight:bold;">FOAIE DE PARCURS PENTRU<br>AUTOCAMIOANE</div>
				<div style="font-size:0.8rem;">ПУТЕВОЙ ЛИСТ ГРУЗОВОГО АВТОМОБИЛЯ</div>
			</div>
			<div style="text-align:center; width:30%;">
				<div style="font-size:0.8rem;">SERIA&nbsp;&nbsp;&nbsp;&nbsp;<b>DAA&nbsp;&nbsp;&nbsp;&nbsp;Nr. '.$fp_nr.'</b></div>
			</div>
		</div>
		
		<div class="fp-tables" style="margin-top:2mm;">
			<!-- Rând 1: 4 coloane la 70% din dreapta -->
			<table style="width:65%; border-collapse:collapse; margin-left:35%;">
				<tr>
					<td style="width:5%; border:1px solid #000; border-bottom:1px solid #fff; padding:1mm; text-align:center; font-size:0.6rem; font-weight:bold;">1</td>
					<td style="width:45%; border:1px solid #000; border-bottom:1px solid #fff; padding:1mm; font-size:0.6rem; position:relative;">
						<div style="text-align:left;"><div>Data emiterii</div><div>Дата выдачи</div></div>
						<div style="position:absolute; top:50%; left:50%; transform:translate(-50%,-50%); font-weight:bold; font-size:0.8rem;">'.$data_emiterii.'</div>
					</td>
					<td style="width:5%; border:1px solid #000; border-bottom:1px solid #fff; padding:1mm; text-align:center; font-size:0.6rem; font-weight:bold;">2</td>
					<td style="width:45%; border:1px solid #000; border-bottom:1px solid #fff; padding:1mm; text-align:left; font-size:0.6rem;"><div>Nr. diagramă tahograf</div><div>№ диаграммы тахографа</div></td>
				</tr>
			</table>
			
			<!-- Rând 2: 6 coloane la 100% -->
			<table style="width:100%; border-collapse:collapse; margin-top:0;">
				<tr>
					<td style="width:20%; border:1px solid #000; padding:1mm; font-size:0.6rem;"><div>Marca autovehiculului, remorcii (semiremorcii)</div><div>Марка автомобиля, прицепа (полуприцепа)</div></td>
					<td style="width:10%; border:1px solid #000; padding:1mm; font-size:0.6rem;"><div>Nr. de înmatriculare</div><div>Регистрационный номер</div></td>
					<td style="width:10%; border:1px solid #000; border-right:0px solid; padding:1mm; font-size:0.6rem;"><div>Nr. inventar</div><div>Гаражный №</div></td>
					<td style="width:25%; border:1px solid #000; border-left:0px solid; padding:1mm; font-size:0.6rem;"><div>Numele şi prenumele şoferilor</div><div>Фамилия и имя водителей</div></td>
					<td style="width:10%; border:1px solid #000; padding:1mm; font-size:0.6rem;"><div>Nr. matricol</div><div>Табельный №</div></td>
					<td style="width:25%; border:1px solid #000; padding:1mm; font-size:0.6rem;"><div>Starea sănătăţii şoferului</div><div>Состояние здоровья водителя</div></td>
				</tr>
			</table>
			
			<!-- Rând 3: 6 coloane cu cifre 3-8 -->
			<table style="width:100%; border-collapse:collapse; margin-top:0;">
				<tr>
					<td style="width:20%; border:1px solid #000; border-top:1px solid #fff; padding:1mm; text-align:center; font-size:0.6rem; font-weight:bold;">3</td>
					<td style="width:10%; border:1px solid #000; border-top:1px solid #fff; padding:1mm; text-align:center; font-size:0.6rem; font-weight:bold;">4</td>
					<td style="width:10%; border:1px solid #000; border-top:1px solid #fff; border-right:0px solid; padding:1mm; text-align:center; font-size:0.6rem; font-weight:bold;">5</td>
					<td style="width:25%; border:1px solid #000; border-top:1px solid #fff; border-left:0px solid; padding:1mm; text-align:center; font-size:0.6rem; font-weight:bold;">6</td>
					<td style="width:10%; border:1px solid #000; border-top:1px solid #fff; padding:1mm; text-align:center; font-size:0.6rem; font-weight:bold;">7</td>
					<td style="width:25%; border:1px solid #000; border-top:1px solid #fff; padding:1mm; text-align:center; font-size:0.6rem; font-weight:bold;">8</td>
				</tr>
			</table>
			
			<!-- Rând 4: 6 coloane cu date din formular -->
			<table style="width:100%; border-collapse:collapse; margin-top:0;">
				<tr>
					<td style="width:20%; border:1px solid #000; border-top:1px solid #fff; padding:1mm; font-size:0.8rem; font-weight:bold; min-height:5mm; height:5mm;">'.$autovehicul_marca.'</td>
					<td style="width:10%; border:1px solid #000; border-top:1px solid #fff; padding:1mm; font-size:0.8rem; font-weight:bold; min-height:5mm; height:5mm;">'.$autovehicul_nr.'</td>
					<td style="width:10%; border:1px solid #000; border-top:1px solid #fff; border-right:0px solid; padding:1mm; font-size:0.6rem;"></td>
					<td style="width:25%; border:1px solid #000; border-top:1px solid #fff; border-left:0px solid; padding:1mm; font-size:0.8rem; font-weight:bold; min-height:5mm; height:5mm;">'.$sofer_nume.'</td>
					<td style="width:10%; border:1px solid #000; border-top:1px solid #fff; padding:1mm; font-size:0.6rem;"></td>
					<td style="width:25%; border:1px solid #000; border-top:1px solid #fff; border-bottom:1px solid #fff; padding:1mm; font-size:0.6rem;"></td>
				</tr>
			</table>
			
			<!-- Rând 5: 6 coloane cu date remorca -->
			<table style="width:100%; border-collapse:collapse; margin-top:0;">
				<tr>
					<td style="width:20%; border:1px solid #000; border-top:1px solid #fff; padding:1mm; font-size:0.8rem; font-weight:bold; min-height:5mm; height:5mm;">'.$remorca_marca.'</td>
					<td style="width:10%; border:1px solid #000; border-top:1px solid #fff; padding:1mm; font-size:0.8rem; font-weight:bold; min-height:5mm; height:5mm;">'.$remorca_nr.'</td>
					<td style="width:10%; border:1px solid #000; border-top:1px solid #fff; border-right:0px solid; padding:1mm; font-size:0.6rem;"></td>
					<td style="width:25%; border:1px solid #000; border-top:1px solid #fff; border-left:0px solid; padding:1mm; font-size:0.6rem;"></td>
					<td style="width:10%; border:1px solid #000; border-top:1px solid #fff; padding:1mm; font-size:0.6rem;"></td>
					<td style="width:25%; border:1px solid #000; border-top:1px solid #fff; border-bottom:1px solid #fff; padding:1mm; font-size:0.6rem;"></td>
				</tr>
			</table>
			
			<!-- Rând 6: 3 coloane - cifra 9, sarcina soferului, gol -->
			<table style="width:100%; border-collapse:collapse; margin-top:0;">
				<tr>
					<td style="width:3.5%; border:1px solid #000; border-top:1px solid #fff; padding:1mm; text-align:center; font-size:0.6rem; font-weight:bold;">9</td>
					<td style="width:71.5%; border:1px solid #000; border-top:1px solid #fff; padding:1mm; text-align:left; font-size:0.6rem;"><div>Sarcina şoferului:</div><div>Задание водителю:</div></td>
					<td style="width:25%; border:1px solid #000; border-top:1px solid #fff; padding:1mm; font-size:0.6rem;"></td>
				</tr>
			</table>
			
			<!-- Rând 7: 2 coloane - gol, menţiuni speciale -->
			<table style="width:100%; border-collapse:collapse; margin-top:0;">
				<tr>
					<td style="width:75%; border:1px solid #000; border-top:1px solid #fff; padding:1mm; font-size:0.6rem;"></td>
					<td style="width:25%; border:1px solid #000; border-top:1px solid #fff; padding:1mm; font-size:0.6rem; font-weight:bold;"><div>Menţiuni speciale</div><div>Особые отметки</div></td>
				</tr>
			</table>
			
			<!-- Rând 8: 2 coloane - gol, cifra 10 -->
			<table style="width:100%; border-collapse:collapse; margin-top:0;">
				<tr>
					<td style="width:75%; border:1px solid #000; border-top:1px solid #fff; padding:1mm; font-size:0.6rem;"></td>
					<td style="width:25%; border:1px solid #000; border-top:1px solid #fff; padding:1mm; text-align:center; font-size:0.6rem; font-weight:bold;">10</td>
				</tr>
			</table>
			
			<!-- Rând 9: 2 coloane - starea tehnică, gol -->
			<table style="width:100%; border-collapse:collapse; margin-top:0;">
				<tr>
					<td style="width:75%; border:1px solid #000; border-top:1px solid #fff; padding:1mm; font-size:0.6rem; font-weight:bold;"><div>Starea tehnică</div><div>Техническое состояние</div></td>
					<td style="width:25%; border:1px solid #000; border-top:1px solid #fff; padding:1mm; font-size:0.6rem;"></td>
				</tr>
			</table>
			
			<!-- Rând 10: 7 coloane header (6 x 12.5% + 1 x 25%) -->
			<table style="width:100%; border-collapse:collapse; margin-top:0;">
				<tr>
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
			
			<!-- Rând 12: 7 coloane - col 1 "La plecare / При выезде", restul goale -->
			<table style="width:100%; border-collapse:collapse; margin-top:0;">
				<tr>
					<td style="width:9%; border:1px solid #000; border-top:1px solid #fff; padding:1mm; font-size:0.6rem;"><div>La plecare</div><div>При выезде</div></td>
					<td style="width:9%; border:1px solid #000; border-top:1px solid #fff; padding:1mm; text-align:center; font-size:0.6rem; font-weight:bold;"></td>
					<td style="width:9%; border:1px solid #000; border-top:1px solid #fff; padding:1mm; text-align:center; font-size:0.6rem; font-weight:bold;"></td>
					<td style="width:11.4%; border:1px solid #000; border-top:1px solid #fff; padding:1mm; text-align:center; font-size:0.6rem; font-weight:bold;"></td>
					<td style="width:11.4%; border:1px solid #000; border-top:1px solid #fff; padding:1mm; text-align:center; font-size:0.6rem; font-weight:bold;"></td>
					<td style="width:25.2%; border:1px solid #000; border-top:1px solid #fff; padding:0; font-size:0.6rem;">
						<div style="display:flex; height:8mm">
							<div style="width:50%; border-right:1px solid #000; padding:1mm; text-align:center; font-weight:bold; display:flex; align-items:center; justify-content:center;"></div>
							<div style="width:50%; padding:1mm; text-align:center; font-weight:bold; display:flex; align-items:center; justify-content:center;"></div>
						</div>
					</td>
					<td style="width:25%; border:1px solid #000; border-top:1px solid #fff; padding:1mm; text-align:center; font-size:0.6rem; font-weight:bold;"></td>
				</tr>
			</table>
			
			<!-- Rând 13: 7 coloane - col 1 "La sosire / При возвращении", restul goale -->
			<table style="width:100%; border-collapse:collapse; margin-top:0;">
				<tr>
					<td style="width:9%; border:1px solid #000; border-top:1px solid #fff; padding:1mm; font-size:0.6rem;"><div>La Sosire</div><div>При Возвращении</div></td>
					<td style="width:9%; border:1px solid #000; border-top:1px solid #fff; padding:1mm; text-align:center; font-size:0.6rem; font-weight:bold;"></td>
					<td style="width:9%; border:1px solid #000; border-top:1px solid #fff; padding:1mm; text-align:center; font-size:0.6rem; font-weight:bold;"></td>
					<td style="width:11.4%; border:1px solid #000; border-top:1px solid #fff; padding:1mm; text-align:center; font-size:0.6rem; font-weight:bold;"></td>
					<td style="width:11.4%; border:1px solid #000; border-top:1px solid #fff; padding:1mm; text-align:center; font-size:0.6rem; font-weight:bold;"></td>
					<td style="width:25.2%; border:1px solid #000; border-top:1px solid #fff; padding:0; font-size:0.6rem;">
						<div style="display:flex; height:8mm">
							<div style="width:50%; border-right:1px solid #000; padding:1mm; text-align:center; font-weight:bold; display:flex; align-items:center; justify-content:center;"></div>
							<div style="width:50%; padding:1mm; text-align:center; font-weight:bold; display:flex; align-items:center; justify-content:center;"></div>
						</div>
					</td>
					<td style="width:25%; border:1px solid #000; border-top:1px solid #fff; padding:1mm; text-align:center; font-size:0.6rem; font-weight:bold;"></td>
				</tr>
			</table>
			
			<!-- Rând 14: 4 coloane - gol, 17, Parcursul conform documentelor, gol -->
			<table style="width:100%; border-collapse:collapse; margin-top:0;">
				<tr>
					<td style="width:9%; border:1px solid #000; border-top:1px solid #fff; padding:1mm; font-size:0.6rem;"></td>
					<td style="width:4%; border:1px solid #000; border-top:1px solid #fff; padding:1mm; text-align:center; font-size:0.6rem; font-weight:bold;">17</td>
					<td style="width:62%; border:1px solid #000; border-top:1px solid #fff; padding:1mm; text-align:center; font-size:0.6rem;"><div>Parcursul conform documentelor, km</div><div>Пробег по документам, км</div></td>
					<td style="width:25%; border:1px solid #000; border-top:1px solid #fff; padding:1mm; font-size:0.6rem;"></td>
				</tr>
			</table>
			
			<!-- Rând 15: 2 coloane - Date vizînd combustibilul (75%), gol (25%) -->
			<table style="width:100%; border-collapse:collapse; margin-top:0;">
				<tr>
					<td style="width:75%; border:1px solid #000; border-top:1px solid #fff; padding:1mm; font-size:0.6rem; font-weight:bold;"><div>Date vizînd combustibilul, litri/m3</div><div>Данные о горючем, литр/м3</div></td>
					<td style="width:25%; border:1px solid #000; border-top:1px solid #fff; padding:1mm; font-size:0.6rem;"></td>
				</tr>
			</table>
			
			<!-- Rând 16: 8 coloane - Tipul de combustibil, Eliberat, Rest la, Predat, Coeficient, Timp, Consum, gol -->
			<table style="width:100%; border-collapse:collapse; margin-top:0;">
				<tr>
					<td style="width:9%; border:1px solid #000; border-top:1px solid #fff; padding:1mm; font-size:0.6rem;"><div>Tipul de combustibil</div><div>Вид топлива</div></td>
					<td style="width:7%; border:1px solid #000; border-top:1px solid #fff; padding:1mm; font-size:0.6rem;"><div>Eliberat</div><div>Выдано</div></td>
					<td style="width:12%; border:1px solid #000; border-top:1px solid #fff; padding:0; font-size:0.6rem; vertical-align:top;">
						<div style="border-bottom:1px solid #000; padding:1mm;"><div>Rest la:</div><div>Остаток при:</div></div>
						<div style="display:flex;">
							<div style="width:50%; border-right:1px solid #000; padding:1mm; font-size:0.6rem; display:flex; flex-direction:column; justify-content:center; align-items:center; text-align:center;"><div>Plecare</div><div>выезде</div></div>
							<div style="width:50%; padding:1mm; font-size:0.6rem; display:flex; flex-direction:column; justify-content:center; align-items:center; text-align:center;"><div>Sosire</div><div>Возвращении</div></div>
						</div>
					</td>
					<td style="width:6%; border:1px solid #000; border-top:1px solid #fff; padding:1mm; font-size:0.6rem;"><div>Predat</div><div>Сдано</div></td>
					<td style="width:12%; border:1px solid #000; border-top:1px solid #fff; padding:1mm; font-size:0.6rem;"><div style="font-size:0.5rem;">Coeficientul de corecţie a normei</div><div style="font-size:0.5rem;">Коэффициент изменения нормы</div></td>
					<td style="width:16%; border:1px solid #000; border-top:1px solid #fff; padding:0; font-size:0.6rem; vertical-align:top;">
						<div style="border-bottom:1px solid #000; padding:1mm;"><div>Timp în exploatare, ore</div><div>Время работы, часов</div></div>
						<div style="display:flex;">
							<div style="width:50%; border-right:1px solid #000; padding:1mm; font-size:0.6rem; display:flex; flex-direction:column; justify-content:center; align-items:center; text-align:center;"><div>Echipament special</div><div>Спецоборудование</div></div>
							<div style="width:50%; padding:1mm; font-size:0.6rem; display:flex; flex-direction:column; justify-content:center; align-items:center; text-align:center;"><div>Motor</div><div>Двигатель</div></div>
						</div>
					</td>
					<td style="width:13%; border:1px solid #000; border-top:1px solid #fff; padding:0; font-size:0.6rem; vertical-align:top;">
						<div style="border-bottom:1px solid #000; padding:1mm;"><div>Consum combustibil</div><div>Расход горючего</div></div>
						<div style="display:flex;">
							<div style="width:50%; border-right:1px solid #000; padding:1mm; font-size:0.6rem; display:flex; flex-direction:column; justify-content:center; align-items:center; text-align:center;"><div>Normat</div><div>По норме</div></div>
							<div style="width:50%; padding:1mm; font-size:0.6rem; display:flex; flex-direction:column; justify-content:center; align-items:center; text-align:center;"><div>Efectiv</div><div>Фактически</div></div>
						</div>
					</td>
					<td style="width:25%; border:1px solid #000; border-top:1px solid #fff; padding:0; font-size:0.6rem; vertical-align:middle;">
						<div style="border-bottom:1px solid #000; padding:1mm;"></div>
						<div style="padding:1mm;"></div>
					</td>
				</tr>
			</table>
			
			<!-- Rând 17: 11 coloane cu numere, ultima 25%, înălțime 4mm -->
			<table style="width:100%; border-collapse:collapse; margin-top:0;">
				<tr>
					<td style="width:9%; border:1px solid #000; border-top:1px solid #fff; padding:1mm; text-align:center; font-size:0.6rem; font-weight:bold;">18</td>
					<td style="width:7%; border:1px solid #000; border-top:1px solid #fff; padding:1mm; text-align:center; font-size:0.6rem; font-weight:bold;">19</td>
					<td style="width:6%; border:1px solid #000; border-top:1px solid #fff; padding:1mm; text-align:center; font-size:0.6rem; font-weight:bold;">20</td>
					<td style="width:6%; border:1px solid #000; border-top:1px solid #fff; padding:1mm; text-align:center; font-size:0.6rem; font-weight:bold;">21</td>
					<td style="width:6%; border:1px solid #000; border-top:1px solid #fff; padding:1mm; text-align:center; font-size:0.6rem; font-weight:bold;">22</td>
					<td style="width:12%; border:1px solid #000; border-top:1px solid #fff; padding:1mm; text-align:center; font-size:0.6rem; font-weight:bold;">23</td>
					<td style="width:8%; border:1px solid #000; border-top:1px solid #fff; padding:1mm; text-align:center; font-size:0.6rem; font-weight:bold;">24</td>
					<td style="width:8%; border:1px solid #000; border-top:1px solid #fff; padding:1mm; text-align:center; font-size:0.6rem; font-weight:bold;">25</td>
					<td style="width:6.5%; border:1px solid #000; border-top:1px solid #fff; padding:1mm; text-align:center; font-size:0.6rem; font-weight:bold;">26</td>
					<td style="width:6.5%; border:1px solid #000; border-top:1px solid #fff; padding:1mm; text-align:center; font-size:0.6rem; font-weight:bold;">27</td>
					<td style="width:25%; border:1px solid #000; border-top:1px solid #fff; padding:1mm; font-size:0.6rem;"></td>
				</tr>
			</table>
			
			<!-- Rând 18: 11 coloane goale, ultima 25%, înălțime 4mm -->
			<table style="width:100%; border-collapse:collapse; margin-top:0;">
				<tr style="height:5mm;">
					<td style="width:9%; border:1px solid #000; border-top:1px solid #fff; padding:1mm; font-size:0.6rem;"></td>
					<td style="width:7%; border:1px solid #000; border-top:1px solid #fff; padding:1mm; font-size:0.6rem;"></td>
					<td style="width:6%; border:1px solid #000; border-top:1px solid #fff; padding:1mm; font-size:0.6rem;"></td>
					<td style="width:6%; border:1px solid #000; border-top:1px solid #fff; padding:1mm; font-size:0.6rem;"></td>
					<td style="width:6%; border:1px solid #000; border-top:1px solid #fff; padding:1mm; font-size:0.6rem;"></td>
					<td style="width:12%; border:1px solid #000; border-top:1px solid #fff; padding:1mm; font-size:0.6rem;"></td>
					<td style="width:8%; border:1px solid #000; border-top:1px solid #fff; padding:1mm; font-size:0.6rem;"></td>
					<td style="width:8%; border:1px solid #000; border-top:1px solid #fff; padding:1mm; font-size:0.6rem;"></td>
					<td style="width:6.5%; border:1px solid #000; border-top:1px solid #fff; padding:1mm; font-size:0.6rem;"></td>
					<td style="width:6.5%; border:1px solid #000; border-top:1px solid #fff; padding:1mm; font-size:0.6rem;"></td>
					<td style="width:25%; border:1px solid #000; border-top:1px solid #fff; padding:1mm; font-size:0.6rem;"></td>
				</tr>
			</table>
			
			<!-- Rând 19: 11 coloane goale, ultima 25%, înălțime 4mm -->
			<table style="width:100%; border-collapse:collapse; margin-top:0;">
				<tr style="height:5mm;">
					<td style="width:9%; border:1px solid #000; border-top:1px solid #fff; padding:1mm; font-size:0.6rem;"></td>
					<td style="width:7%; border:1px solid #000; border-top:1px solid #fff; padding:1mm; font-size:0.6rem;"></td>
					<td style="width:6%; border:1px solid #000; border-top:1px solid #fff; padding:1mm; font-size:0.6rem;"></td>
					<td style="width:6%; border:1px solid #000; border-top:1px solid #fff; padding:1mm; font-size:0.6rem;"></td>
					<td style="width:6%; border:1px solid #000; border-top:1px solid #fff; padding:1mm; font-size:0.6rem;"></td>
					<td style="width:12%; border:1px solid #000; border-top:1px solid #fff; padding:1mm; font-size:0.6rem;"></td>
					<td style="width:8%; border:1px solid #000; border-top:1px solid #fff; padding:1mm; font-size:0.6rem;"></td>
					<td style="width:8%; border:1px solid #000; border-top:1px solid #fff; padding:1mm; font-size:0.6rem;"></td>
					<td style="width:6.5%; border:1px solid #000; border-top:1px solid #fff; padding:1mm; font-size:0.6rem;"></td>
					<td style="width:6.5%; border:1px solid #000; border-top:1px solid #fff; padding:1mm; font-size:0.6rem;"></td>
					<td style="width:25%; border:1px solid #000; border-top:1px solid #fff; padding:1mm; font-size:0.6rem;"></td>
				</tr>
			</table>
			
			<!-- Rând 20: 3 coloane - cifra 28, text documente, gol 25% -->
			<table style="width:100%; border-collapse:collapse; margin-top:0;">
				<tr>
					<td style="width:3.5%; border:1px solid #000; border-top:1px solid #fff; padding:1mm; text-align:center; font-size:0.6rem; font-weight:bold;">28</td>
					<td style="width:71.5%; border:1px solid #000; border-top:1px solid #fff; padding:1mm; font-size:0.6rem; text-align:left;"><div>Nr. documentelor de confirmare:</div><div>№ подтверждающего документа:</div></td>
					<td style="width:25%; border:1px solid #000; border-top:1px solid #fff; padding:1mm; font-size:0.6rem;"></td>
				</tr>
			</table>
		</div>
		
		<div style="text-align:left; font-size:0.6rem; margin-top: 2mm;">
			Editura de Imprimate «Statistica», a4 (181n) 05.04. t.100000
		</div>
	</div>
	
	<!-- Second Page - VOLUMUL DE TRANSPORTURI -->
	<div class="pg fp_page bg" style="page-break-before: always; background-color:#fff !important; background-image:none !important;">
		<div style="text-align:left; font-size:0.6rem;">
			Editura de Imprimate «Statistica», a4 (181n) 05.04.
		</div>
		<div style="font-size:0.8rem; font-weight:bold; margin-top:1mm;">
			<div>VOLUMUL DE TRANSPORTURI</div>
			<div>ОБЪЕМ ПЕРЕВОЗОК</div>
		</div>
		
		<!-- Main Table -->
		<table style="width:100%; border-collapse:collapse; margin-top:2mm; font-size:0.55rem;">
			<!-- Row 1: Main headers -->
			<tr>
				<td style="width:14%; border:1px solid #000;" rowspan="4"></td>
				<td style="width:43%; border:1px solid #000; text-align:center; font-weight:bold; padding:2mm;" colspan="4">
					<div>Transporturi republicane</div>
					<div>Республиканские перевозки</div>
				</td>
				<td style="width:43%; border:1px solid #000; text-align:center; font-weight:bold; padding:2mm;" colspan="5">
					<div>Transporturi internaționale</div>
					<div>Международные перевозки</div>
				</td>
			</tr>
			<!-- Row 2: Sub-headers (total, din care, din col) -->
			<tr>
				<td style="border:1px solid #000; text-align:center; font-weight:bold; padding:1mm;" rowspan="3">
					<div>Total</div>
					<div>Всего</div>
				</td>
				<td style="border:1px solid #000; text-align:center; font-weight:bold; padding:1mm;" colspan="2">
					<div>Din care:</div>
					<div>В том числе:</div>
				</td>
				<td style="border:1px solid #000; text-align:center; font-weight:bold; padding:1mm;" rowspan="2">
					<div>Din</div>
					<div>Col.29</div>
				</td>
				<td style="border:1px solid #000; text-align:center; font-weight:bold; padding:1mm;" rowspan="3">
					<div>Total</div>
					<div>Всего</div>
				</td>
				<td style="border:1px solid #000; text-align:center; font-weight:bold; padding:1mm;" colspan="3">
					<div>Din care:</div>
					<div>В том числе:</div>
				</td>
				<td style="border:1px solid #000; text-align:center; font-weight:bold; padding:1mm;" rowspan="2">
					<div>Din</div>
					<div>Col.33</div>
				</td>
			</tr>
			<!-- Row 3: Detailed sub-columns -->
			<tr>
				<td style="border:1px solid #000; text-align:center; padding:1mm;" rowspan="2">
					<div>Interur-</div>
					<div>bane</div>
					<div>Между-</div>
					<div>город-</div>
					<div>ные</div>
				</td>
				<td style="border:1px solid #000; text-align:center; padding:1mm;" rowspan="2">
					<div>Urbane și</div>
					<div>suburban</div>
					<div>e</div>
					<div>Внутриго-</div>
					<div>родские и</div>
					<div>пригор.</div>
				</td>
				<td style="border:1px solid #000; text-align:center; padding:1mm;" rowspan="2">
					<div>Export</div>
					<div>Экспорт</div>
				</td>
				<td style="border:1px solid #000; text-align:center; padding:1mm;" rowspan="2">
					<div>Import</div>
					<div>Импорт</div>
				</td>
				<td style="border:1px solid #000; text-align:center; padding:1mm;" rowspan="2">
					<div>Tranzit</div>
					<div>Транзит</div>
				</td>
			</tr>
			<!-- Row 4: cu re-morci text -->
			<tr>
				<td style="border:1px solid #000; text-align:center; padding:1mm;">
					<div>Cu re-</div>
					<div>morci из</div>
					<div>Гр.29 на</div>
					<div>при-</div>
					<div>цепах</div>
				</td>
				<td style="border:1px solid #000; text-align:center; padding:1mm;">
					<div>Cu re-</div>
					<div>morci</div>
					<div>Из гр.33</div>
					<div>на при-</div>
					<div>цепах</div>
				</td>
			</tr>
			<!-- Row 5: Column numbers -->
			<tr>
				<td style="border:1px solid #000; text-align:center; font-weight:bold; padding:1mm;">A</td>
				<td style="border:1px solid #000; text-align:center; font-weight:bold; padding:1mm;">29</td>
				<td style="border:1px solid #000; text-align:center; font-weight:bold; padding:1mm;">30</td>
				<td style="border:1px solid #000; text-align:center; font-weight:bold; padding:1mm;">31</td>
				<td style="border:1px solid #000; text-align:center; font-weight:bold; padding:1mm;">32</td>
				<td style="border:1px solid #000; text-align:center; font-weight:bold; padding:1mm;">33</td>
				<td style="border:1px solid #000; text-align:center; font-weight:bold; padding:1mm;">34</td>
				<td style="border:1px solid #000; text-align:center; font-weight:bold; padding:1mm;">35</td>
				<td style="border:1px solid #000; text-align:center; font-weight:bold; padding:1mm;">36</td>
				<td style="border:1px solid #000; text-align:center; font-weight:bold; padding:1mm;">37</td>
			</tr>
			<!-- Row 6: Transportul încărcăturii -->
			<tr style="height:12mm;">
				<td style="border:1px solid #000; padding:1mm; height:12mm;">
					<div>Transportul</div>
					<div>încărcăturii, tone</div>
					<div>Перевезено грузов,</div>
					<div>тонн</div>
				</td>
				<td style="border:1px solid #000; padding:1mm;"></td>
				<td style="border:1px solid #000; padding:1mm;"></td>
				<td style="border:1px solid #000; padding:1mm;"></td>
				<td style="border:1px solid #000; padding:1mm;"></td>
				<td style="border:1px solid #000; padding:1mm;"></td>
				<td style="border:1px solid #000; padding:1mm;"></td>
				<td style="border:1px solid #000; padding:1mm;"></td>
				<td style="border:1px solid #000; padding:1mm;"></td>
				<td style="border:1px solid #000; padding:1mm;"></td>
			</tr>
			<!-- Row 7: Trafic de încărcături -->
			<tr style="height:12mm;">
				<td style="border:1px solid #000; padding:1mm; height:12mm;">
					<div>Trafic de</div>
					<div>încărcături, t-km</div>
					<div>Грузооборот, т-км</div>
				</td>
				<td style="border:1px solid #000; padding:1mm;"></td>
				<td style="border:1px solid #000; padding:1mm;"></td>
				<td style="border:1px solid #000; padding:1mm;"></td>
				<td style="border:1px solid #000; padding:1mm;"></td>
				<td style="border:1px solid #000; padding:1mm;"></td>
				<td style="border:1px solid #000; padding:1mm;"></td>
				<td style="border:1px solid #000; padding:1mm;"></td>
				<td style="border:1px solid #000; padding:1mm;"></td>
				<td style="border:1px solid #000; padding:1mm;"></td>
			</tr>
			<!-- Row 8: Venturi -->
			<tr style="height:12mm;">
				<td style="border:1px solid #000; padding:1mm; height:12mm;">
					<div>Venturi, lei</div>
					<div>Доходы, леев</div>
				</td>
				<td style="border:1px solid #000; padding:1mm;"></td>
				<td style="border:1px solid #000; padding:1mm;"></td>
				<td style="border:1px solid #000; padding:1mm; text-align:center; font-weight:bold;">X</td>
				<td style="border:1px solid #000; padding:1mm;"></td>
				<td style="border:1px solid #000; padding:1mm;"></td>
				<td style="border:1px solid #000; padding:1mm;"></td>
				<td style="border:1px solid #000; padding:1mm;"></td>
				<td style="border:1px solid #000; padding:1mm;"></td>
				<td style="border:1px solid #000; padding:1mm; text-align:center; font-weight:bold;">X</td>
			</tr>
		</table>
	</div>
</div>';

echo $rtrn;
?>
