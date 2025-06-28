<?php defined( '_DOIT' ) or die( 'Restricted access' );

$abr = 'AV';

$rtrn = '
<style>
	.logo {float:left;}
	.date {text-align:center; padding:15mm 0 0; float:left;}
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
	.head > .inf > .pos {float:right;}
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
</style>

<div id="p_cont" class="base">
	<div class="pg bg">
		<img class="logo" src="/media/images/site/v2/logo_b.svg" width="33%" />
		<div class="head">
			<div class="nr">'.$abr.$cont_y.$cont_q.'/'.$cont_n.'</div>
			<div class="ttl">Contract de Arvună</div>
			<div class="inf">
				<span class="pos">mun. Chișinău</span>
			</div>
		</div>
		<div class="gr">
			<p>Noi subsemnaţii "SAUTO" SRL, în persoana Directorului domnului Olăriță Sergiu, cu sediul la adresa or. Chișinău, str. Calea Mosilor 11, pe de o parte Nume Prenume <span class="txtln">&nbsp;&nbsp;<span class="txt_cpt">'.strtolower($_POST['u_nm']).'</span>&nbsp;&nbsp;</span> de altă parte, acționînd de bună voie și conştientizând semnificaţia acţiunilor noastre, am încheiat prezentul Acord cu privire la următoarele:
			</p>
		</div>
		<div class="gr">
			<p><b>1.</b> Părţile reies din faptul că intenţiile lor corespund cu încheierea contractului de vînzare-cumpărare a autovehiculul de model <span class="txtln">&nbsp;&nbsp;<span class="txt_cpt">'.$_POST['br'].' '.$_POST['mo'].'</span>&nbsp;&nbsp;</span> de pe terenul pentru expunere "SAUTO" SRL.</p>
		</div>
		<div class="gr">
			<p><b>2.</b> În contul viitorului contract de vînzare-cumpărare a autovehicolului domnul si/sau doamna achită, la SAUTO SRL reprezentat de Olarita Sergiu.</p>
		</div>
		<div class="gr">
			<p><b>3.</b> Preţul automobilului constituie <span class="txtln">&nbsp;&nbsp;'.parseCurr($_POST['prc_eur']).'&nbsp;&nbsp;</span> Euro
			</br>Automobilul este arvonat in suma de <span class="txtln">&nbsp;&nbsp;'.parseCurr($_POST['prc']).'&nbsp;&nbsp;</span> lei</p>
		</div>
		<div class="gr">
			<p><b>4.</b> Părţile care au încheiat prezentul Acord, se obligă să incheie contractul de vînzare-cumpărare a autovehiculul atunci cind se gaseste automobilul potrivit.</p>
		</div>
		<div class="gr">
			<p><b>5.</b> Cheltuielile legate cu legalizarea notarialei; şi înregistrarea Autovehiculul pe numele Cumpărătorului sunt suportate de Cumparatorul autovehiculului.</p>
		</div>
		<div class="gr">
			<p><b>6.</b> Cumpărătorul autovehiculul s-a familirizat cu starea tehnică a autovehiculul şi este de acord să-l achiziționeze în termenul indicat în punctul 4 al prezentului Acord fără înaintarea ulterioară a careva pretenții către Vînzător.</p>
		</div>
		<div class="gr">
			<p><b>7.</b> Data vînzării autovehiculul se consideră drept ziua transmiterii autovehiculului în posesia Cumpărătorului de către Vînzător si achitării taxei de cumpărare de către cealaltă parte a prezentului contract.</p>
		</div>
		<div class="gr">
			<p><b>8.</b> În cazul neîndiplinirii sau îndeplinirii necorespunzătoare a obligaţiilor prevăzute de prezentul acord, părţile poartă răspunderea confor Codului Civil în vigoare al Republicii Moldova (art. 631-633 Codului Civi al Republicii Moldova), în cazul dacă pentru neîndeplinirea prezentului Acord poartă răspunderea Partea care a vărsat arvuna (viitorul Cumpărător), arvuna rămîne în posesia Părţii celelalte (Olăriță Sergiu) iar în caz de refuz la creditare vînzătorul este obligat să întoarcă arvona înapoi.</p>
		</div>
		<div class="gr">
			<p><b>9.</b> Toate modificările si completările prezentului Acord sunt valabile si au valoare juridcă identică cu cea a prezentului Acord, dacă ele au fost efectuate în scris şi sunt semnate de amblele Părţi.</p>
		</div>
		<div class="gr">
			<p><b>10.</b> Părţile prezentului Acord au declarat că ele nu sunt lipsite de capacitatea de exerciţiu, nu suferă boli care ar împiedica conştientizarea sesnului şi a esenţei acordului semnat de ele, precum şi că lipsesc circumstanţele care le formează la încheierea aceste tranzacţii la condiţii extrem de nefavorabile pentru ele.</p>
		</div>
		<div class="gr">
			<p><b>11.</b> Conţinutul tranzacţiei, sensul ei, efectele juridice, răspunderea, drepturile şi obligaţiile Părţilor au fost explicate.</p>
		</div>
		<div class="gr">
			<p><b>12.</b> Arvona se restituie in caz daca clientul se refuza de servicului auto la comanda pînă la licitarea automobilului cautat pe platforme de achizitii pentru dealeri.</p>
		</div>
		
		<div class="flx">
			<div class="ws"></div>
			<div class="sign">
				<div class="s1">Olăriță Sergiu<span style="position: absolute;bottom: -7mm;">+(373) 68689995 </span><div class="ln"></div>'.(isset($_POST['stamp'])&&$_POST['stamp']==1?'<div class="stamp ghost"><div class="signature"></div></div>':'').'</div>
				<div class="s2"><span class="txt_cpt">'.strtolower($_POST['u_nm']).'</span><div class="ln"></div></div>
			</div>
		</div>
	</div>
</div>
';

echo $rtrn;
?>