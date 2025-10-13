<?php defined( '_DOIT' ) or die( 'Restricted access' );

$abr = 'AR';

$i = 1;
$rtrn = '
<style>
	.logo {float:left; height:10mm; width:auto; position:absolute;}
	.ttl {text-align:center; padding:15mm 0 0; clear:both; font-size:1.4rem; font-weight:bold;}
	tr > td.id {width:10%;} tr > td.nm {width:50%;} tr > td.prc {width:40%;}
	.cont {margin:0;}
	.txt_up {text-transform:uppercase;}
	.txt_cpt {text-transform:capitalize;}
	.buyer {margin-top:1rem;}
	
	.base {font-family:"def_l"; color:#000; font-size:.9rem;}
	.head > .nr {text-align:center; font-size:1.2rem; font-family:"def";}
	.head > .ttl {text-align:center; font-size:1.1rem; font-weight:bold;}
	.head > .inf {width:100%; display:inline-block;}
	.head > .inf > .pos {float:right;}
	.head > .inf > .date {float:right;}
	
	.who {margin-top:1rem; text-align:justify;}
	
	.gr {margin-top:4mm; text-align:justify;}
	.gr > .ttl {text-align:center; font-weight:bold;}
	.gr > .sb {padding-left:10mm;}
	
	table {margin-top:5mm;}
	
	table.n1 tr > td {white-space:pre-wrap;}
	table.n1 tr > td.n1 {width:6%; text-align:center;}
	table.n1 tr > td.n2 {width:47%; text-align:left;}
	table.n1 tr > td.n3 {width:47%; text-align:left;}
	
	.def_fnt {font-family:"def" !important;} 
	
	table.n2 tr {height:10mm;}
	table.n2 tr > td {width:50%;}
	
	.pg:not(.x2) tr > td {padding:1mm 2mm;}
	
	.pg > .gr:first-of-type {margin-top:0;}
	
	.pg > .flx {min-height:100mm;}
	
	.txt_cpt {text-transform:capitalize;}
	
	.pg:not(.x2) p {margin:1mm 0; line-height:4.5mm;}
	
	.txtln {text-decoration:underline;}
</style>';

$br_mo = '';
if ( isset($_POST['br']) && isset($_POST['mo']) ){
	if ( is_array($_POST['br']) && is_array($_POST['mo']) ){
		foreach ($_POST['br'] as $k => $v){
			if ( isset($_POST['mo'][$k]) ){
				$br_mo .= ($k==0?'':', ').$v.' '.$_POST['mo'][$k].(isset($_POST['vin'][$k])?' ('.$_POST['vin'][$k].')':'');
			}
		}
	}
}

$rtrn .= '
<div id="p_cont" class="base">
	<div class="pg bg">
		<img class="logo" src="/media/images/site/v2/logo_b.svg" width="33%" />
		<div class="head">
			<div class="nr">Comandă pentru transport: №'.$abr.$cont_y.$cont_q.'/'.$cont_n.'</div>
			<div class="inf"><span class="date">'.$zdate.'</span></div>
		</div>
		<div class="gr">
			<p>Prezenta comnadă constituie contract pentru o unică transportare și are putere juridică dacă este transmisă prin e-mail sau orice altă modalitate electronică.</p>
		</div>
		
		<table class="n1">
			<tr><td class="n1">'.($i++).'</td><td class="n2">Client</td><td class="n3 def_fnt">'.(isset($_POST['u_nm'])?$_POST['u_nm']:'').'</td></tr>
			<tr><td class="n1">'.($i++).'</td><td class="n2">Țara încărcării/ Țara descărcării</td><td class="n3">'.(isset($_POST['cntr_fr'])?$lng['l']['country'][$_POST['cntr_fr']]:'').' - '.(isset($_POST['cntr_to'])?$lng['l']['country'][$_POST['cntr_to']]:'').'</td></tr>
			<tr><td class="n1">'.($i++).'</td><td class="n2">Cantitatea, marca/ modelul vehicolului, greutatea</td><td class="n3">'.$br_mo.'</td></tr>
			
			<tr><td class="n1">'.($i++).'</td><td class="n2">Valoarea încărcăturii</td><td class="n3">'.(isset($_POST[''])?$_POST['']:'').'</td></tr>
			<tr><td class="n1">'.($i++).'</td><td class="n2">Documente de însoțire</td><td class="n3">CMR, Invoice, TI</td></tr>
			<tr><td class="n1">'.($i++).'</td><td class="n2">Data încărcării</td><td class="n3">'.(isset($_POST[''])?$_POST['']:'').'</td></tr>
			
			<tr><td class="n1">'.($i++).'</td><td class="n2">Adresa încărcării, pers. De contact, telefonul de contact</td><td class="n3">'.(isset($_POST['cntr_fr'])?$lng['l']['country'][$_POST['cntr_fr']]:'').'</td></tr>
			<tr><td class="n1">'.($i++).'</td><td class="n2">Vama la încărcare</td><td class="n3">'.(isset($_POST['cntr_fr'])?$lng['l']['country'][$_POST['cntr_fr']]:'').'</td></tr>
			<tr><td class="n1">'.($i++).'</td><td class="n2">Vama la descărcare/ broker vamal</td><td class="n3">'.(isset($_POST['cntr_to'])?$lng['l']['country'][$_POST['cntr_to']].($_POST['cntr_to']=='MD'?', Leușeni':''):'').'</td></tr>
			<tr><td class="n1">'.($i++).'</td><td class="n2">Destinatar</td><td class="n3">'.(isset($_POST['u_nm'])?$_POST['u_nm']:'').'</td></tr>
			
			<tr><td class="n1">'.($i++).'</td><td class="n2">Adresa descărcării</td><td class="n3">'.(isset($_POST['adr_to'])?$_POST['adr_to']:'').'</td></tr>
			<tr><td class="n1">'.($i++).'</td><td class="n2">Persoana de contact și tel de la locul descărcării</td><td class="n3">'.(isset($_POST[''])?$_POST['']:'').'</td></tr>
			
			<tr><td class="n1">'.($i++).'</td><td class="n2">Plătitor</td><td class="n3">'.(isset($_POST['u_nm'])?$_POST['u_nm']:'').'</td></tr>
			<tr><td class="n1">'.($i++).'</td><td class="n2">Tariful pentru transportare</td><td class="n3">'.(isset($_POST['prc'])?$_POST['prc']:'-').'</td></tr>
			
			<tr><td class="n1">'.($i++).'</td><td class="n2">Modalitatea de plată</td><td class="n3">Transfer bancar</td></tr>
			<tr><td class="n1">'.($i++).'</td><td class="n2">Termenul de achitare</td><td class="n3">'.(isset($_POST['t2pay'])?$_POST['t2pay']:'').'</td></tr>
			<tr><td class="n1">'.($i++).'</td><td class="n2">Nr. Înma. Camion/remorca</td><td class="n3">'.(isset($_POST['plate'])?$_POST['plate']:'').'</td></tr>
		</table>
		
		<div class="gr" style="font-size:.8rem;">
			Note:Transportatorul poartă răspundere pentru marfa transportată, inclusiv pentru integritatea și deteriorarea ei.
			<ol style="margin-top:.3rem;">
				<li>Timpul pentru încărcare, vămuire – 24 ore, descărcare și devamre – 24 ore.</li>
				<li>Pentru staționarea din vina clientului, ultimul achită o amendă în mărime de 100 euro pentru fiecare zi de staționare.</li>
				<li>Clientul poartă răspundere financiară totală pentru corectitudinea oformării procedurilor vamale.</li>
				<li>În cazul reținerii achitării de către client a tarifului pentru transport, clientul este obligat să achite o amendă în mărime de 0,5% din mărimea taxei pentru transport pentru fiecare zi de neachitare.</li>
			</ol>
		</div>
		
		<div class="flx">
			<div class="ws"></div>
			<table class="n2">
				<tr><td>TRANSPORTATOR</td><td>CLIENT</td></tr>
				<tr><td>'.$zcont.'</td><td><b class="txt_cpt">'.($_POST['u_tp']=='fiz'?strtolower($_POST['u_nm']):$_POST['u_nm']).'</b><br/>'.$_POST['u_adr'].'</br>'.($_POST['u_tp']=='fiz'?'cp':'cf').': <span class="txt_up">'.$_POST['u_cf_idno'].'</span><br/>'.($_POST['u_tp']=='fiz'?'dat.nast.: '.date( 'd.m.Y', strtotime( $_POST['u_tva_dt'] ) ):'TVA: '.$_POST['u_tva_dt']).'<br/>'.($_POST['u_tp']=='fiz'?'dat.el.: '.date( 'd.m.Y', strtotime( $_POST['u_iban_dt_tk'] ) ):'IBAN: '.$_POST['u_iban_dt_tk']).'</td></tr>
			</table>
			<div class="ws"></div>
			<div class="sign">
				<div class="s1">Semnatura / L. Ş.<div class="ln"></div>'.(isset($_POST['stamp'])&&$_POST['stamp']==1?'<div class="stamp ghost"><div class="signature"></div></div>':'').'</div>
				<div class="s2">Semnatura / L. Ş.<div class="ln"></div>'.((isset($_POST['usr_stamp'])&&$_POST['usr_stamp']==1)&&isset($_POST['u_cf_idno'])?(file_exists($_SERVER['DOCUMENT_ROOT'].'/'._SITE_IMG.'/stamp_z/'.$_POST['u_cf_idno'].'.jpg')?'<div class="stamp ghost" style="background-image:url(/'._SITE_IMG.'/stamp_z/'.$_POST['u_cf_idno'].'.jpg); width:74mm; height:42mm;"></div>':''):'').'</div>
			</div>
			<div class="ws" style="max-height:20mm;"></div>
		</div>
	</div>';

// Include KYC pages
include(__DIR__.'/kyc_helper.php');
if (requiresKycPages('com_transport')) {
    $rtrn .= '
    <div class="sep"></div>';
    
    includeKycPages();
}

$rtrn .= '
</div>';

echo $rtrn;
?>