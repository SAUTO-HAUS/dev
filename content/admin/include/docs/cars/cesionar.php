<?php defined( '_DOIT' ) or die( 'Restricted access' );

/*
нужно создать в документах еще один документ, это должна быть как 5 страница контракта - когда это нужно,и возможность создания 
отдельно как документа

назвать её Cesionar - при её нажатии должно автоматически добавлятся anexa nr 2,при нажатии открываются такие же поля как при заполнения
для физ или юридических лиц,соответственно эти данные идут в anexa nr 2,anexa nr 2 должна создаваться как отдельный документ так и в 
месте с контрактом,и ниже всех полей которые заполняются как у физ лица нужен ещё пункт как в гарантии чтоб прописывать условие 
в ручную
*/

$abr = 'VCA';

$zcont = '
<b>“SAUTO” SRL</b><br/>
<span>Republica Moldova, MD-2084, mun.Chişinau</span><br/>
<span>or.Cricova, str.Chisinaului 84, ap.(of.) 39</span><br/>
<span>IBAN: <b>MD64VI022512000000171MDL</b></span><br/>
<span>în B.C.“VICTORIABANK S.A.”, <b>VICBMD2XXXX</b></span><br/>
<span>c/f <b>1017600006845</b>, c/TVA <b>0609417</b></span>';

$rtrn = '
<style>
	.base {font-family:"def_l"; color:#000;}
	.head > .nr {text-align:center; font-size:1.2rem; font-family:"def";}
	.head > .ttl {text-align:center; font-size:1.1rem; font-weight:bold;}
	.head > .inf {width:100%; display:inline-block;}
	.head > .inf > .pos {float:left;}
	.head > .inf > .date {float:right;}
	
	.who {margin-top:1rem; text-align:justify;}
	
	.gr {margin-top:7mm; text-align:justify;}
	.gr > .ttl {text-align:center; font-weight:bold;}
	.gr > .sb {padding-left:10mm;}
	
	table {margin-top:1mm;}
	
	table.n1 tr > td.n1 {width:20%;}
	table.n1 tr > td.n2 {width:30%;}
	
	table.n2 tr {height:10mm;}
	table.n2 tr > td {width:33%;}
	
	.pg:not(.d2) tr > td {padding:2mm;}
	
	.pg > .gr:first-of-type {margin-top:0;}
	
	.pg.d1 > .flx {min-height:240mm;}
	.pg.d1 > .flx > .sign {margin-top:10mm;}
	
	.pg.d2 > .flx {min-height:280mm;}
	
	.pg.d2 .res > .its {display:flex; flex-flow:row wrap; justify-content:space-between;}
	.pg.d2 .res > .its > span {width:25%; margin:5mm 0 0;}
	.pg.d2 .gr > .inf {margin-top:15mm;}
	.pg.d2 .gr > .inf > .x {width:50%;}
	.pg.d2 .gr > .inf > .x .ttl {font-size:1.2rem;}
	.pg.d2 .gr > .inf > .x > * {width:100%; display:block; position:relative;}
	.pg.d2 .gr > .inf > .n1 {float:left; text-align:left;}
	.pg.d2 .gr > .inf > .n2 {float:right; text-align:right;}
	.pg.d2 .head > .nr {text-align:left; font-size:1rem; font-family:"def_l";}
	.pg.d2 .head > .ttl {margin-top:25mm; font-family:"def"; font-weight:normal;}
	
	table .n2, .txt_up {text-transform:uppercase;}
	.txt_cpt {text-transform:capitalize;}
	
	.pg:not(.d2) p {margin:1mm 0; line-height:4.5mm;}
</style>
<div id="p_cont" class="base">
	<div class="pg d1 p1 bg">
		<div class="head">
			<!--<div class="nr">CONTRACT nr. '.$abr.$cont_y.$cont_q.'/'.$cont_n.'</div>--> <!--CONTRACT nr. VC33/2715 [VC - vinzare cumparare, 3 - year last digit, 3 - quarter of the year, 2715 = 2700 + number of contract of this year (now 15)]-->
			<div class="ttl">Anexa nr la Contract de vinzare cumparare '.strtoupper($_POST['cont_nr']).' din '.$zdate.'</div>
		</div>
		<div class="who">
			<p>În baza p 4.17 a prezentului contract SAUTO SRL transfera către Ceban Maxim creanta de la Ceban Natalia în sumă de 402000,00lei</p>
			<br/><p>Ceban Maxim primește de la SAUTO SRL creanta lui Ceban Natalia in suma de 402000,00lei</p>
			<br/><p>Ceban Maxim se obliga sa achite companiei SAUTO SRL sumă de 402000,00lei</p>
			
			<!--
			<b>SAUTO SRL</b>, reprezentat legal de dl Olăriță Veaceslav, în calitate de administrator, care reprezintă interesele Societăţii în baza Actului Constitutiv și normativelor interne, înregistrată la Camera Înregistrării de Stat cu Numarul de Identificare de Stat – 1017600006845, denunumit în continare <b>Vînzător</b>
			<br/><br/><span class="txt_cpt">'.strtolower($_POST['u_nm']).'</span>, '.($_POST['u_tp']=='fiz'?'cp':'cf').': <span class="txt_up">'.$_POST['u_cf_idno'].'</span>, '.$_POST['u_adr'].' în calitate de <b>Cumparator</b>, au convenit încheierea prezentului contract, după cum urmează
			-->
		</div>
		
		<div class="flx">
			<div id="chap6_txt">
				<div class="gr">';
					if ( isset($_POST['grnt_txt']) ){
						foreach ($_POST['grnt_txt'] as $v){
							if ($v!=''){
								$rtrn .= '<br/><p>'.$v.'</p>';
							}
						}
					}
				$rtrn .= '
				</div>
			</div>
			<div class="ws"></div>
			<table class="n2">
				<tr><td>VINZATOR</td><td>CUMPARATOR</td><td>CESIONAR</td></tr>
				<tr>
					<td>'.$zcont.'</td>
					<td><b class="txt_cpt">'.strtolower($_POST['u_nm']).'</b><br/>'.$_POST['u_adr'].'</br>'.($_POST['u_tp']=='fiz'?'cp':'cf').': <span class="txt_up">'.$_POST['u_cf_idno'].'</span><br/>'.($_POST['u_tp']=='fiz'?'dat.nast.: '.date( 'd.m.Y', strtotime( $_POST['u_tva_dt'] ) ):'TVA: '.$_POST['u_tva_dt']).'<br/>'.($_POST['u_tp']=='fiz'?'dat.el.: '.date( 'd.m.Y', strtotime( $_POST['u_iban_dt_tk'] ) ):'IBAN: '.$_POST['u_iban_dt_tk']).'</td>
					<td><b class="txt_cpt">'.strtolower($_POST['u_nm']).'</b><br/>'.$_POST['u_adr'].'</br>'.($_POST['u_tp']=='fiz'?'cp':'cf').': <span class="txt_up">'.$_POST['u_cf_idno'].'</span><br/>'.($_POST['u_tp']=='fiz'?'dat.nast.: '.date( 'd.m.Y', strtotime( $_POST['u_tva_dt'] ) ):'TVA: '.$_POST['u_tva_dt']).'<br/>'.($_POST['u_tp']=='fiz'?'dat.el.: '.date( 'd.m.Y', strtotime( $_POST['u_iban_dt_tk'] ) ):'IBAN: '.$_POST['u_iban_dt_tk']).'</td>
				</tr>
			</table>
			<div class="ws" style="max-height:20mm;"></div>
		</div>
		<div class="conf">CONFIDENTIAL</div>
	</div>
</div>';

echo $rtrn;
?>