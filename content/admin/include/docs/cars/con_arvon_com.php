<?php defined( '_DOIT' ) or die( 'Restricted access' );

$abr = 'VBN';

$rtrn = '
<style>
	.logo {float:left;}
	.date {text-align:center; float:left;}
	.ttl {text-align:center; padding:15mm 0 0; clear:both; font-size:1.4rem; font-weight:bold;}
	tr > td.id {width:10%;} tr > td.nm {width:50%;} tr > td.prc {width:40%;}
	.cont {margin:0;}
	.txt_up {text-transform:uppercase;}
	.txt_cpt {text-transform:capitalize;}
	.buyer {margin-top:1rem;}
	
	.base {font-family:"def_l"; color:#000;}
	.head > .nr {text-align:center; font-size:1.2rem; font-family:"def";}
	.head > .ttl {text-align:center; font-size:1.1rem; font-weight:bold;}
	.head > .inf {width:100%; display:inline-block;}
	.head > .inf > .pos {float:left;}
	.head > .inf > .date {float:right;}
	
	.who {margin-top:1rem; text-align:justify;}
	
	.gr {margin-top:4mm; text-align:justify;}
	.gr > .ttl {text-align:center; font-weight:bold;}
	.gr > .sb {padding-left:10mm;}
	
	table {margin-top:1mm;}
	
	table.n1 tr > td.n1 {width:20%;}
	table.n1 tr > td.n2 {width:30%;}
	
	table.n2 tr {height:10mm;}
	table.n2 tr > td {width:50%;}
	
	.pg:not(.x2) tr > td {padding:2mm;}
	
	.pg > .gr:first-of-type {margin-top:0;}
	
	.pg > .flx {min-height:10mm;}
	
	.txt_cpt {text-transform:capitalize;}
	
	.pg:not(.x2) p {margin:1mm 0; line-height:4.5mm;}
	
	.txtln {text-decoration:underline;}
	
	ul {list-style-type:none;}
	ul > li::before {content:none;}
</style>

<div id="p_cont" class="base">
	<div class="pg bg">
		<img class="logo" src="/media/images/site/v2/logo_b.svg" width="33%" />
		<div class="head">
			<div class="nr">'.$abr.$cont_y.$cont_q.'/'.$cont_n.'</div>
			<div class="ttl">Contract de Arvună</div>
			<div class="inf">
				<span class="pos">mun. Chișinău</span><span class="date">'.$zdate.'</span>
			</div>
		</div>
		<div class="gr">
			<p>"SAUTO" SRL cu sediul în mun. Chișinău, MD2024, str. Calea Moșilor, 11, c/f 1017600006845, în persoana Directorului domnului Olăriță Sergiu, în calitate de vânzător, a primit de la <span class="txtln">&nbsp;&nbsp;<span class="txt_cpt">'.strtolower($_POST['u_nm']).'</span>&nbsp;&nbsp;</span>, IDNP <span class="txtln">&nbsp;&nbsp;<span class="txt_cpt">'.strtolower($_POST['u_cf_idno']).'</span>&nbsp;&nbsp;</span>, în calitate de cumpărător arvuna în suma de <span class="txtln">&nbsp;&nbsp;'.parseCurr($_POST['prc']).'&nbsp;&nbsp;</span> lei (MDL).
			</p>
		</div>
		<div class="gr">
			<p><b>1.</b> Părțile au încheiat de comun acord prezentul contract în vederea participării la licitație pentru procurarea autovehiculului brand <span class="txtln">&nbsp;&nbsp;<span class="txt_cpt">'.(isset($_POST['br']) ? ucwords(strtolower(str_replace('_', ' ', $_POST['br']))) : '').'</span>&nbsp;&nbsp;</span>, model <span class="txtln">&nbsp;&nbsp;<span class="txt_cpt">'.(isset($_POST['mo']) ? ucwords(str_replace('_', ' ', $_POST['mo'])) : '').'</span>&nbsp;&nbsp;</span>, VIN COD nr.<span class="txtln">&nbsp;&nbsp;<span class="txt_cpt">'.$_POST['vin'].'</span>&nbsp;&nbsp;</span>, conform datelor prezentate în ofertă. Suma arvunei va fi inclusă în suma prețului total al autovehiculului.</p> 
		</div>
		<div class="gr">
			<p><b>2.</b> Vânzătorul se obligă:</p>
			<ul>
				<li><b>a.</b> Să participe la licitație în interesul cumpărătorului, ținând cont de faptul că valoarea finală, inclusiv transportarea, serviciile de vamă și toate cheltuielile și serviciile necesare până la înregistrarea la ASP, să nu depășească suma de <span class="txtln">&nbsp;&nbsp;'.parseCurr($_POST['prc_eur']).'&nbsp;&nbsp;</span> Euro.</li>
				<li><b>b.</b> Să încheie contractul de vânzare-cumpărare după licitație, cu prezentarea acestui contract și stabilirea clauzelor până la primirea arvunei. </li>
			</ul>
		</div>
		<div class="gr">
			<p><b>3.</b> Cumpărătorul se obligă:</p>
			<ul>
				<li><b>a.</b> Să achite arvuna.</li>
				<li><b>b.</b> Să încheie contractul de vânzare-cumpărare a autovehiculului după ce s-a câștigat licitația pentru autovehiculul brand <span class="txtln">&nbsp;&nbsp;<span class="txt_cpt">'.(isset($_POST['br']) ? ucwords(strtolower(str_replace('_', ' ', $_POST['br']))) : '').'</span>&nbsp;&nbsp;</span>, model <span class="txtln">&nbsp;&nbsp;<span class="txt_cpt">'.(isset($_POST['mo']) ? ucwords(str_replace('_', ' ', $_POST['mo'])) : '').'</span>&nbsp;&nbsp;</span>,VIN nr.<span class="txtln">&nbsp;&nbsp;<span class="txt_cpt">'.$_POST['vin'].'</span>&nbsp;&nbsp;</span>.  După încheierea contractului de vânzare-cumpărare, să achite suma necesară pentru procurarea autovehiculului.</li>
				<li><b>c.</b> Să achite testarea tehnică a autovehiculului după importul acestuia.</li>
				<li><b>d.</b> Să achite înregistrarea autovehiculului pe numele cumpărătorului.</li>
			</ul>
		</div>
		<div class="gr">
			<p><b>4.</b> Contractul de arvună garantează încheierea contractului de vânzare-cumpărare a autovehiculului indicat în p.1 al prezentului contract.</p>
		</div>
		<div class="gr">
			<p>Cu condiția ca clauzele prezentului contract au fost respectate de vânzător și tranzacția nu a avut loc din vina cumpărătorului sau cumpărătorul a refuzat/renunțat să contracteze, arvuna nu se restituie.</p>
		</div>
		<div class="gr">
			<p>Cu condiția ca clauzele prezentului contract au fost respectate de cumpărător și tranzacția nu a avut loc din vina vânzătorului, sau vânzătorul a refuzat/renunțat să contracteze, sau licitația nu a fost favorabilă, atunci arvuna urmează a fi restituită integral în valoare de 100%.</p>
		</div>
		
		<div class="flx">
			<div class="ws"></div>
			<table class="n2">
				<tr><td>'.$zcont.'</td><td><b class="txt_cpt">'.($_POST['u_tp']=='fiz'?strtolower($_POST['u_nm']):$_POST['u_nm']).'</b><br/>'.$_POST['u_adr'].'</br>'.($_POST['u_tp']=='fiz'?'cp':'cf').': <span class="txt_up">'.$_POST['u_cf_idno'].'</span><br/>'.($_POST['u_tp']=='fiz'?'dat.nast.: '.date( 'd.m.Y', strtotime( $_POST['u_tva_dt'] ) ):'TVA: '.$_POST['u_tva_dt']).'<br/>'.($_POST['u_tp']=='fiz'?'dat.el.: '.date( 'd.m.Y', strtotime( $_POST['u_iban_dt_tk'] ) ):'IBAN: '.$_POST['u_iban_dt_tk']).'</td></tr>
			</table>
			<div class="ws"></div>
			<div class="sign">
				<div class="s1">Semnatura / L. Ş.<div class="ln"></div>'.(isset($_POST['stamp'])&&$_POST['stamp']==1?'<div class="stamp ghost"><div class="signature"></div></div>':'').'</div>
				<div class="s2">Semnatura / L. Ş.<div class="ln"></div>'.((isset($_POST['usr_stamp'])&&$_POST['usr_stamp']==1)&&isset($_POST['u_cf_idno'])?(file_exists($_SERVER['DOCUMENT_ROOT'].'/'._SITE_IMG.'/stamp_z/'.$_POST['u_cf_idno'].'.jpg')?'<div class="stamp ghost" style="background-image:url(/'._SITE_IMG.'/stamp_z/'.$_POST['u_cf_idno'].'.jpg); width:74mm; height:42mm;"></div>':''):'').'</div>
			</div>
			<div class="ws" style="max-height:20mm;"></div>
		</div>
	</div>
</div>
';

echo $rtrn;
?>