<?php defined( '_DOIT' ) or die( 'Restricted access' );

$abr = 'CP';

$rtrn = '
<style>
	.date {text-align:center; padding:15mm 0 0; float:left;}
	.ttl {text-align:center; padding:15mm 0 0; clear:both; font-size:1.4rem; font-weight:bold;}
	tr > td.id {width:10%;} tr > td.nm {width:50%;} tr > td.prc {width:40%;}
	.cont {margin:0;}
	.txt_up {text-transform:uppercase;}
	.txt_cpt {text-transform:capitalize;}
	.buyer {margin-top:1rem;}
</style>

<div id="p_cont" class="base">';
$br = isset($_POST['br']) ? (is_array($_POST['br']) ? ($_POST['br'][0] ?? '') : $_POST['br']) : '';
$mo = isset($_POST['mo']) ? (is_array($_POST['mo']) ? ($_POST['mo'][0] ?? '') : $_POST['mo']) : '';
$vin = isset($_POST['vin']) ? (is_array($_POST['vin']) ? ($_POST['vin'][0] ?? '') : $_POST['vin']) : '';
	$sum = 0;
	for ($i=1;$i<=1;$i++){
		$sum += (isset($_POST['prc']) && is_numeric($_POST['prc'])) ? floatval($_POST['prc']) : 0;
		$rtrn .= '
		<div class="pg bg">
			<div class="date">'.$zdate.'</div>
			<img class="logo" src="/media/images/site/v2/logo_b.svg" width="33%" />
			<div class="ln"></div>
			<span class="cont">'.$zcont.'</span>
			<div class="cont"></div>
			<div class="ttl">Cont de plata nr. '.$abr.$cont_y.$cont_q.'/'.$cont_n.'</div>
			<div class="flx">
				<table>
					<tr><td class="id">№</td><td class="nm txt_up">Denumirea marfuri (serviciilor)<br/>Название товара (услуг)</td><td class="prc">Pret pentru o unitate<br/>Цена за единицу<br/>'.(isset($_POST['cur'])?$_POST['cur']:'MDL').'</td></tr>
					<tr><td class="id">1</td><td class="nm txt_up"><span>Plata in avans pentru automobilul </span>'.($br !== '' ? ucwords(strtolower(str_replace('_', ' ', $br))) : '').' '.($mo !== '' ? ucwords(str_replace('_', ' ', $mo)) : '').($vin !== ''?'<br/>VIN: '.$vin:'').'</td><td class="prc">'.parseCurr($_POST['prc']).'.00</td></tr>
					<tr><td class="id"></td><td class="nm txt_up">TOTAL</td><td class="prc">'.parseCurr($sum).'.00</td></tr>
				</table>
				<div class="buyer">Platitor: <span class="txt_cpt">'.(isset($_POST['u_nm']) ? strtolower((string)$_POST['u_nm']) : '').'</span>, '.(isset($_POST['u_tp'])&&$_POST['u_tp']=='fiz'?'cp':'cf').' <span class="txt_up">'.(isset($_POST['u_cf_idno'])?$_POST['u_cf_idno']:'').'</span></div>
				<div class="ws"></div>
				<div class="sign">
					<div class="s1">Semnatura<div class="ln"></div>'.(isset($_POST['stamp'])&&$_POST['stamp']==1?'<div class="stamp ghost"><div class="signature"></div></div>':'').'</div>
					<div class="s2">L. Ş.<div class="ln"></div></div>
				</div>
			</div>
		</div>';
	}
$rtrn .= '
</div>';

// Include KYC pages
include(__DIR__.'/kyc_helper.php');
if (requiresKycPages('con_plata')) {
    $rtrn .= '
    <div class="sep"></div>';
    
    includeKycPages();
}

echo $rtrn;
?>