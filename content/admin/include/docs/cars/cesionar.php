<?php defined( '_DOIT' ) or die( 'Restricted access' );

// Helper function to safely get POST values
function getPost($key, $default = '') {
    return isset($_POST[$key]) ? $_POST[$key] : $default;
}

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
<span>or.Cricova, str.Chisinaului 84, of. 39</span><br/>
<span>IBAN: <b>MD64VI022512000000171MDL</b></span><br/>
<span>în B.C.“VICTORIABANK S.A.”, <b>VICBMD2XXXX</b></span><br/>
<span>c/f <b>1017600006845</b>,<br/>c/TVA <b>0609417</b></span><br/><br/>_____________________';

$rtrn = '
<style>
	.base {font-family:"def_l"; color:#000;}
	.head > .nr {text-align:center; font-size:1.2rem; font-family:"def";}
	.head > .ttl {text-align:center; font-size:1.1rem; font-weight:bold;}
	.head > .inf {width:100%; display:inline-block;}
	.head > .inf > .pos {float:left;}
	.head > .inf > .date {float:right;}
	
	.who {margin-top:5mm; margin-bottom:10mm; text-align:justify;}
	
	.gr {margin-top:7mm; text-align:justify;}
	.gr > .ttl {text-align:center; font-weight:bold;}
	.gr > .sb {padding-left:10mm;}
	
	table {margin-top:5mm;}
	
	table.n1 tr > td.n1 {width:20%;}
	table.n1 tr > td.n2 {width:30%;}
	
	table.n2 tr {height:10mm;}
	table.n2 tr > td {vertical-align:middle; text-align:center; position:relative;}
	table.n2 tr > td .signature {position:absolute; bottom:2mm; left:50%; transform:translateX(-50%);}
	
	/* 3 columns for cesionar table */
	table.n2.cesionar tr > td {width:33%;}
	/* 2 columns for normal annexa table */  
	table.n2.normal tr > td {width:50%;}
	
	.pg:not(.d2) tr > td {padding:2mm;}
	
	.pg > .gr:first-of-type {margin-top:0;}
	
	.pg.d1 > .flx {min-height:auto;}
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
			<div class="ttl">Anexa nr. '.getPost('annexa_nr', 'CS-'.date('Y').'-001').' la Contract de vinzare cumparare '.strtoupper(getPost('cont_nr')).' din '.$zdate.'</div>
		</div>
		<div class="who">';
		
		// Check if cesionar is enabled
		if (getPost('add_cesionar') == '1' && !empty(getPost('cesionar_nm'))) {
			// ANNEXA CU CESIONAR - Referință la punctul 4.17
			$rtrn .= '
			<p>În baza punctului 4.17 a prezentului contract SAUTO SRL transferă către <strong>'.getPost('cesionar_nm').'</strong> cp <strong>'.getPost('cesionar_cf_idno').'</strong> creanța de la <strong>'.getPost('u_nm').'</strong> cp <strong>'.getPost('u_cf_idno').'</strong> în sumă de <strong>'.number_format(floatval(getPost('cesionar_suma', 0)), 2).' '.getPost('cur', 'MDL').'</strong></p>
			<br/><p><strong>'.getPost('cesionar_nm').'</strong> cp <strong>'.getPost('cesionar_cf_idno').'</strong> primește de la SAUTO SRL creanța de la <strong>'.getPost('u_nm').'</strong> cp <strong>'.getPost('u_cf_idno').'</strong> în suma de <strong>'.number_format(floatval(getPost('cesionar_suma', 0)), 2).' '.getPost('cur', 'MDL').'</strong></p>
			<br/><p><strong>'.getPost('cesionar_nm').'</strong>, cp <strong>'.getPost('cesionar_cf_idno').'</strong> se obligă să achite companiei SAUTO SRL suma de <strong>'.number_format(floatval(getPost('cesionar_suma', 0)), 2).' '.getPost('cur', 'MDL').'</strong></p>';
		} else {
			// ANNEXA NORMALĂ - fără cesionar
			$rtrn .= '
			<p>Prezenta anexă se referă la contractul de vânzare-cumpărare nr. <strong>'.strtoupper(getPost('cont_nr')).'</strong> din <strong>'.$zdate.'</strong></p>
			<br/><p>Cumpărătorul <strong>'.getPost('u_nm').'</strong> cp <strong>'.getPost('u_cf_idno').'</strong> confirmă primirea bunurilor conform contractului menționat mai sus.</p>
			<br/><p>Toate condițiile contractului rămân în vigoare și sunt respectate de ambele părți.</p>';
		}
		
		$rtrn .= '
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
			<div class="ws"></div>
			';
			
			// Check if cesionar is enabled for signature table
			if (getPost('add_cesionar') == '1' && !empty(getPost('cesionar_nm'))) {
				// TABLE WITH CESIONAR - 3 columns
				$rtrn .= '<table class="n2 cesionar">
				<tr><td>VINZATOR</td><td>CUMPARATOR</td><td>CESIONAR</td></tr>
				<tr>
					<td>'.$zcont.'</td>
					<td><b>'.getPost('u_nm').'</b><br/>cp <strong>'.getPost('u_cf_idno').'</strong><div class="signature">_____________________</div></td>
					<td><b>'.getPost('cesionar_nm').'</b><br/>cp <strong>'.getPost('cesionar_cf_idno').'</strong><div class="signature">_____________________</div></td>
				</tr>';
			} else {
				// TABLE WITHOUT CESIONAR - 2 columns
				$rtrn .= '<table class="n2 normal">
				<tr><td>VINZATOR</td><td>CUMPARATOR</td></tr>
				<tr>
					<td>'.$zcont.'</td>
					<td><b>'.getPost('u_nm').'</b><br/>cp <strong>'.getPost('u_cf_idno').'</strong><div class="signature">_____________________</div></td>
				</tr>';
			}
			
			$rtrn .= '
			</table>
			<div class="ws" style="max-height:20mm;"></div>
		</div>
		<div class="conf">CONFIDENTIAL</div>
</div>';

echo $rtrn;
?>