<?php defined( '_DOIT' ) or die( 'Restricted access' );

$abr = 'AN2';

$zcont = '
<b>"SAUTO" SRL</b><br/>
<span>Republica Moldova, MD-2084, mun.Chişinau</span><br/>
<span>or.Cricova, str.Chisinaului 84, ap.(of.) 39</span><br/>
<span>IBAN: <b>MD64VI022512000000171MDL</b></span><br/>
<span>în B.C."VICTORIABANK S.A.", <b>VICBMD2XXXX</b></span><br/>
<span>c/f <b>1017600006845</b>, c/TVA <b>0609417</b></span>';

// Check if cesionar is enabled
$has_cesionar = isset($_POST['add_cesionar']) && $_POST['add_cesionar'] == '1';

// Get Annexa number
$annexa_number = isset($_POST['annexa_nr']) ? $_POST['annexa_nr'] : 'AN2-'.date('Y').'-001';

// Determine page count and numbering
$page_count = $has_cesionar ? 2 : 1;
$page_1_num = $has_cesionar ? '1/2' : '1/1';
$page_2_num = '2/2';

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
	table.n2 tr > td {width:50%;}
	
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
	
	.page-number {position:fixed; bottom:10mm; right:10mm; font-size:0.9rem; color:#666;}
	
	.page-break {page-break-before:always;}
</style>
<div id="p_cont" class="base">
	<div class="pg d1 p1 bg">
		<div class="head">
			<div class="nr">ANNEXA nr. '.$annexa_number.'</div>
			<div class="ttl">Anexa nr. 2 la Contract de vânzare-cumpărare / Contract de avans '.strtoupper(isset($_POST['cont_nr']) ? $_POST['cont_nr'] : '').' din '.$zdate.'</div>
		</div>
		<div class="who">
			<p><strong>În baza punctului 4.17 al contractului de mai sus</strong>, prin prezenta anexă se stabilește cesiunea dreptului de plată pentru autovehiculul specificat în contract.</p>';

if ($has_cesionar) {
	$cesionar_suma = isset($_POST['cesionar_suma']) ? number_format($_POST['cesionar_suma'], 2) : '0.00';
	$currency = isset($_POST['cur']) ? $_POST['cur'] : 'MDL';
	
	$rtrn .= '
			<br/><p><strong>SAUTO SRL</strong> transferă către <strong class="txt_cpt">'.strtolower(isset($_POST['cesionar_nm']) ? $_POST['cesionar_nm'] : '').'</strong> creanța de la <strong class="txt_cpt">'.strtolower(isset($_POST['u_nm']) ? $_POST['u_nm'] : '').'</strong> în sumă de <strong>'.$cesionar_suma.' '.$currency.'</strong>.</p>
			<br/><p><strong class="txt_cpt">'.strtolower(isset($_POST['cesionar_nm']) ? $_POST['cesionar_nm'] : '').'</strong> primește de la SAUTO SRL creanța de la <strong class="txt_cpt">'.strtolower(isset($_POST['u_nm']) ? $_POST['u_nm'] : '').'</strong> în suma de <strong>'.$cesionar_suma.' '.$currency.'</strong>.</p>
			<br/><p><strong class="txt_cpt">'.strtolower(isset($_POST['cesionar_nm']) ? $_POST['cesionar_nm'] : '').'</strong> se obligă să achite companiei SAUTO SRL suma de <strong>'.$cesionar_suma.' '.$currency.'</strong> conform termenilor stabiliți.</p>';
} else {
	$rtrn .= '
			<br/><p>Prezenta anexă confirmă transferul drepturilor de plată conform contractului de bază, fără implicarea unei terțe părți (cesionar).</p>
			<br/><p>Toate obligațiile de plată rămân între părțile contractante inițiale conform termenilor stabiliți în contractul principal.</p>';
}

$rtrn .= '
		</div>
		
		<div class="flx">
			<div class="ws"></div>
			<table class="n2">
				<tr>
					<td>VÂNZĂTOR</td>
					<td>CUMPĂRĂTOR</td>
				</tr>
				<tr>
					<td>'.$zcont.'</td>
					<td><b class="txt_cpt">'.strtolower(isset($_POST['u_nm']) ? $_POST['u_nm'] : '').'</b><br/>'.(isset($_POST['u_adr']) ? $_POST['u_adr'] : '').'<br/>'.(isset($_POST['u_tp']) && $_POST['u_tp']=='fiz'?'cp':'cf').': <span class="txt_up">'.(isset($_POST['u_cf_idno']) ? $_POST['u_cf_idno'] : '').'</span>';

if (isset($_POST['u_tva_dt']) && $_POST['u_tva_dt'] != '') {
	$rtrn .= '<br/>'.(isset($_POST['u_tp']) && $_POST['u_tp']=='fiz'?'dat.nașterii: '.$_POST['u_tva_dt']:'TVA: '.$_POST['u_tva_dt']);
}
if (isset($_POST['u_iban_dt_tk']) && $_POST['u_iban_dt_tk'] != '') {
	$rtrn .= '<br/>'.(isset($_POST['u_tp']) && $_POST['u_tp']=='fiz'?'dat.eliberării: '.$_POST['u_iban_dt_tk']:'IBAN: '.$_POST['u_iban_dt_tk']);
}

$rtrn .= '</td>
				</tr>
			</table>
			<div class="ws" style="max-height:20mm;"></div>
		</div>
		<div class="page-number">'.$page_1_num.'</div>
		<div class="conf">CONFIDENTIAL</div>
	</div>';

// PAGE 2 - CESIONAR (only if cesionar is enabled)
if ($has_cesionar) {
	$cesionar_suma = isset($_POST['cesionar_suma']) ? number_format($_POST['cesionar_suma'], 2) : '0.00';
	$currency = isset($_POST['cur']) ? $_POST['cur'] : 'MDL';
	
	$rtrn .= '
	<div class="pg d2 p2 bg page-break">
		<div class="head">
			<div class="nr">ANNEXA nr. '.$annexa_number.' (continuare)</div>
			<div class="ttl">Cesionar - Terță parte care primește dreptul de plată</div>
		</div>
		<div class="who">
			<p><strong>În baza punctului 4.17 al contractului '.strtoupper(isset($_POST['cont_nr']) ? $_POST['cont_nr'] : '').'</strong>, prin prezenta se confirmă cesiunea dreptului de plată către terța parte:</p>
			
			<p><strong>SAUTO SRL</strong> transferă către <strong class="txt_cpt">'.strtolower(isset($_POST['cesionar_nm']) ? $_POST['cesionar_nm'] : '').'</strong> creanța de la <strong class="txt_cpt">'.strtolower(isset($_POST['u_nm']) ? $_POST['u_nm'] : '').'</strong> în sumă de <strong>'.$cesionar_suma.' '.$currency.'</strong>.</p>
			
			<p><strong class="txt_cpt">'.strtolower(isset($_POST['cesionar_nm']) ? $_POST['cesionar_nm'] : '').'</strong> primește de la SAUTO SRL creanța de la <strong class="txt_cpt">'.strtolower(isset($_POST['u_nm']) ? $_POST['u_nm'] : '').'</strong> în suma de <strong>'.$cesionar_suma.' '.$currency.'</strong>.</p>
			
			<p><strong class="txt_cpt">'.strtolower(isset($_POST['cesionar_nm']) ? $_POST['cesionar_nm'] : '').'</strong> se obligă să achite companiei SAUTO SRL suma de <strong>'.$cesionar_suma.' '.$currency.'</strong> conform termenilor stabiliți.</p>
		</div>
		
		<div class="flx">
			<div class="ws"></div>
			<table class="n2">
				<tr>
					<td>SAUTO SRL</td>
					<td>CESIONAR</td>
				</tr>
				<tr>
					<td>'.$zcont.'</td>
					<td><b class="txt_cpt">'.strtolower(isset($_POST['cesionar_nm']) ? $_POST['cesionar_nm'] : '').'</b><br/>'.(isset($_POST['cesionar_adr']) ? $_POST['cesionar_adr'] : '').'<br/>'.(isset($_POST['cesionar_tp']) && $_POST['cesionar_tp']=='fiz'?'cp':'cf').': <span class="txt_up">'.(isset($_POST['cesionar_cf_idno']) ? $_POST['cesionar_cf_idno'] : '').'</span>';
	
	if (isset($_POST['cesionar_tva_dt']) && $_POST['cesionar_tva_dt'] != '') {
		$rtrn .= '<br/>'.(isset($_POST['cesionar_tp']) && $_POST['cesionar_tp']=='fiz'?'dat.nașterii: '.htmlspecialchars($_POST['cesionar_tva_dt']):'TVA: '.htmlspecialchars($_POST['cesionar_tva_dt']));
	}
	if (isset($_POST['cesionar_iban_dt_tk']) && $_POST['cesionar_iban_dt_tk'] != '') {
		$rtrn .= '<br/>'.(isset($_POST['cesionar_tp']) && $_POST['cesionar_tp']=='fiz'?'dat.eliberării: '.htmlspecialchars($_POST['cesionar_iban_dt_tk']):'IBAN: '.htmlspecialchars($_POST['cesionar_iban_dt_tk']));
	}
	if (isset($_POST['cesionar_account']) && $_POST['cesionar_account'] != '') {
		$rtrn .= '<br/>Cont: '.htmlspecialchars($_POST['cesionar_account']);
	}
	if (isset($_POST['cesionar_phn']) && $_POST['cesionar_phn'] != '') {
		$rtrn .= '<br/>Tel: '.htmlspecialchars($_POST['cesionar_phn']);
	}
	if (isset($_POST['cesionar_eml']) && $_POST['cesionar_eml'] != '') {
		$rtrn .= '<br/>Email: '.htmlspecialchars($_POST['cesionar_eml']);
	}
	
	$rtrn .= '</td>
				</tr>
			</table>
			
			<div class="gr">
				<p><strong>În sumă de: '.$cesionar_suma.' '.$currency.'</strong></p>
				<p>Referință la contractul principal: <strong>'.strtoupper(isset($_POST['cont_nr']) ? $_POST['cont_nr'] : '').'</strong></p>
				<p>Data anexei: <strong>'.$zdate.'</strong></p>
			</div>
			
			<div class="ws" style="max-height:20mm;"></div>
		</div>
		<div class="page-number">'.$page_2_num.'</div>
		<div class="conf">CONFIDENTIAL</div>
	</div>';
}

$rtrn .= '
</div>';

echo $rtrn;
?>
