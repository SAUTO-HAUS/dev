<?php defined( '_DOIT' ) or die( 'Restricted access' );

$abr = 'AC';

$date = $_POST['date'] ?? date('Y-m-d');
$date_formatted = date('d.m.Y', strtotime($date));

$vca_contract_nr = $_POST['vca_contract_nr'] ?? '';
$vca_date = $_POST['vca_date'] ?? '';
$vca_date_formatted = !empty($vca_date) ? date('d.m.Y', strtotime($vca_date)) : '';
$vca_amount = $_POST['vca_amount'] ?? '0.00';

$vcs_contract_nr = $_POST['vcs_contract_nr'] ?? '';
$vcs_date = $_POST['vcs_date'] ?? '';
$vcs_date_formatted = !empty($vcs_date) ? date('d.m.Y', strtotime($vcs_date)) : '';
$vcs_amount = $_POST['vcs_amount'] ?? '0.00';

$compensation_amount = $_POST['compensation_amount'] ?? '0.00';

$u_nm = $_POST['u_nm'] ?? '';
$u_cf_idno = $_POST['u_cf_idno'] ?? '';

$vca_amount_formatted = number_format((float)$vca_amount, 2, '.', '');
$vcs_amount_formatted = number_format((float)$vcs_amount, 2, ',', '');
$compensation_amount_formatted = number_format((float)$compensation_amount, 2, ',', '');

$rtrn = '
<style>
	.logo {float:left;}
	.date {text-align:center; padding:5mm 0 0; float:left;}
	.ttl {text-align:center; padding:5mm 0 0; clear:both; font-size:1.4rem; font-weight:bold;}
	tr > td.id {width:10%;} tr > td.nm {width:50%;} tr > td.prc {width:40%;}
	.cont {margin:0;}
	.txt_up {text-transform:uppercase;}
	.txt_cpt {text-transform:capitalize;}
	.buyer {margin-top:1rem;}
	
	.base {font-family:"def_l"; color:#000;}
	.head {margin-top:45mm;}
	.head > .nr {text-align:center; font-size:1.2rem; font-family:"def";}
	.head > .ttl {text-align:center; font-size:1.3rem; font-weight:bold;}
	.head > .inf {width:100%; display:inline-block;}
	.head > .inf > .pos {float:left;}
	.head > .inf > .date {float:left;}
	
	.who {margin-top:1rem; text-align:justify;}
	
	.gr {margin-top:5mm; text-align:justify;}
	.gr > .ttl {text-align:center; font-weight:bold;}
	.gr > .sb {padding-left:10mm;}
	
	table {margin-top:1mm;}
	
	table.n1 tr > td.n1 {width:20%;}
	table.n1 tr > td.n2 {width:30%;}
	
	table.n2 tr {height:10mm;}
	table.n2 tr > td {width:50%;}
	
	.pg:not(.x2) tr > td {padding:1mm;}
	
	.pg > .gr:first-of-type {margin-top:0;}
	
	.pg > .flx {min-height:10mm;}
	
	.txt_cpt {text-transform:capitalize;}
	
	.pg:not(.x2) p {margin:1mm 0; line-height:4.5mm;}
	
	.txtln {text-decoration:underline;}
</style>

<div id="p_cont" class="base">
	<div class="pg bg">
		<img class="logo" src="/media/images/site/v2/logo_b.svg" width="33%" />
		<div class="head">
			<div class="ttl">ACT DE COMPENSARE</div>
			<div class="inf">
				<span class="date">'.$date_formatted.'</span>
			</div>
		</div>
		<div class="gr">
			<p>Prezentul act de compensare este întocmit în baza art. 651 din Codul Civil al Republicii Moldova și contractelor de vânzare-cumpărare la situația din '.$date_formatted.' între <b>SAUTO SRL</b> CF: <b>1017600006845</b> și <b><span class="txt_cpt">'.strtolower($u_nm).'</span></b> CP: <b><span class="txt_up">'.$u_cf_idno.'</span></b>.</p>
		</div>
		<div class="gr">
			<p>La data de '.$vca_date_formatted.' în baza contractului <b>'.$vca_contract_nr.'</b> din '.$vca_date_formatted.' <b><span class="txt_cpt">'.strtolower($u_nm).'</span></b> are datorie față de <b>SAUTO SRL</b> în sumă de '.$vca_amount_formatted.' lei.</p>
		</div>
		<div class="gr">
			<p>La data de '.$vcs_date_formatted.' în baza contractului de vânzare procurare <b>'.$vcs_contract_nr.'</b> din '.$vcs_date_formatted.' <b>SAUTO SRL</b> are datorie față de <b><span class="txt_cpt">'.strtolower($u_nm).'</span></b> în sumă de '.$vcs_amount_formatted.' lei.</p>
		</div>
		<div class="gr">
			<p>Prin prezentul act ambele părți au convenit de a efectua stingerea reciprocă în sumă de '.$compensation_amount_formatted.' lei.</p>
		</div>
		
		<div class="flx">
			<div class="ws"></div>
			<div class="sign">
				<div class="s1"><b>SAUTO SRL</b><span style="position: absolute;bottom: -7mm;">Administrator</span><div class="ln"></div>'.(isset($_POST['stamp'])&&$_POST['stamp']==1?'<div class="stamp ghost"><div class="signature"></div></div>':'').'</div>
				<div class="s2"><b><span class="txt_cpt">'.strtolower($u_nm).'</span></b><div class="ln"></div></div>
			</div>
		</div>
	</div>
</div>';

echo $rtrn;
