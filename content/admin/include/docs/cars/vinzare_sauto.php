<?php defined( '_DOIT' ) or die( 'Restricted access' );

$abr = 'VCS';

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
	table.n2 tr > td {width:50%;}
	
	.pg:not(.x2) tr > td {padding:2mm;}
	
	.pg > .gr:first-of-type {margin-top:0;}
	
	.pg.x1 > .flx {min-height:260mm;}
	.pg.x1 > .flx > .sign {margin-top:10mm;}
	
	.pg.x2 > .flx {min-height:280mm;}
	
	.pg.x2 .res > .its {display:flex; flex-flow:row wrap; justify-content:space-between;}
	.pg.x2 .res > .its > span {width:25%; margin:5mm 0 0;}
	.pg.x2 .gr > .inf {margin-top:15mm;}
	.pg.x2 .gr > .inf > .x {width:50%;}
	.pg.x2 .gr > .inf > .x .ttl {font-size:1.2rem;}
	.pg.x2 .gr > .inf > .x > * {width:100%; display:block; position:relative;}
	.pg.x2 .gr > .inf > .n1 {float:left; text-align:left;}
	.pg.x2 .gr > .inf > .n2 {float:right; text-align:right;}
	.pg.x2 .head > .nr {margin-top:10mm; text-align:left; font-size:1rem;}
	.pg.x2 .head > .ttl {margin-top:25mm; font-family:"def"; font-weight:normal;}
	
	table .n2, .txt_up {text-transform:uppercase;}
	.txt_cpt {text-transform:capitalize;}
	
	.pg:not(.x2) p {margin:1mm 0; line-height:4.5mm;}
</style>
<div id="p_cont" class="base">
	<div class="pg">
		<div class="head">
			<div class="nr">CONTRACT nr. '.$abr.$cont_y.$cont_q.'/'.$cont_n.'</div> <!--CONTRACT nr. VCS33/2742 [VC - vinzare cumparare, 3 - year last digit, 3 - quarter of the year, 2742 = 2727 + number of contract of this year (now 15)]-->
			<div class="ttl">Contract de vânzare-cumpărare a autovehiculului</div>
			<div class="inf">
				<span class="pos">mun. Chișinău</span><span class="date">'.$zdate.'</span>
			</div>
		</div>
		<div class="who">
			<span class="txt_cpt"><b>'.strtolower($_POST['u_nm']).'</b></span>, '.$_POST['u_adr'].', <br/>'.($_POST['u_tp']=='fiz'?'dat.el.: '.date( 'd.m.Y', strtotime( $_POST['u_iban_dt_tk'] ) ).' c/p':'IBAN: '.$_POST['u_iban_dt_tk'].' c/f').': '.$_POST['u_cf_idno'].' in calitate de <b>Vînzător</b>
			<br/><br/>Sauto SRL, cf 1017600006845, TVA 0609417, MD64VI022512000000171MDL, mun.Chisinau or.Cricova str.Chisinaului 84 ap.39 conformându-se normelor de drept civil in vigoare pe teritoriul Republicii Moldova, in calitate de <b>Cumparator</b>, au convenit încheierea prezentului contract, după cum urmează
		</div>
		<div class="gr">
			<h3 class="ttl">1. OBIECTUL CONTRACTULUI.</h3>
			<p><b>1.1.</b> Obiectul prezentului contract este o unitate de transport:</p>
			<table class="n1">
				<tr><td class="n1">Marca</td><td class="n2">'.(isset($_POST['br']) ? ucwords(strtolower(str_replace('_', ' ', $_POST['br']))) : '').'</td><td class="n1">Model</td><td class="n2">'.(isset($_POST['mo']) ? ucwords(str_replace('_', ' ', $_POST['mo'])) : '').'</td></tr>
				<tr><td class="n1">Anul Fabricatiei</td><td class="n2">'.$_POST['yr'].'</td><td class="n1">Culoare</td><td class="n2">'.(isset($lng['l']['car']['clr'][ $_POST['clr'] ])?$lng['l']['car']['clr'][ $_POST['clr'] ]:$_POST['clr']).'</td></tr>
				<tr><td class="n1">Serie caroserie</td><td class="n2">'.$_POST['vin'].'</td><td class="n1"></td><td class="n2"></td></tr>
				<tr><td class="n3" rowspan="1" colspan="4">Livrarea automobilului se execută la adresa: '.$lng['t']['x']['address'][$_POST['loc']].'</td></tr>
			</table>
			<p><b>1.2.</b> Vânzătorul se obligă să transfere în proprietatea Cumpărătorului, iar Cumpărătorul se obligă să preia/accepte și să achite prețul pentru automobilul/automobilele.</p>
		</div>
		<div class="gr">
			<h3 class="ttl">2. TERMENELE ȘI CONDIȚIILE DE LIVRARE.</h3>
			<p><b>2.1.</b> Vânzătorul execută livrarea autovehiculului către Cumpărător, întocmește și transmite Cumpărătorului documentele corespunzătoare necesare pentru înregistrarea dreptului de proprietate la Agenția Servicii Publice, pina la '.( isset($_POST['term_livr'])&&$_POST['term_livr']>=1?$_POST['term_livr']:'35 (Treizeci si cinci)' ).' zile lucratoare din momentul semnării prezentului contract, cu condiția îndeplinirii de către Cumpărător a tuturor obligațiunilor ce îi revin in punctul 3.3. Toate cheltuielel ce țin de întocmirea certificatului de înmatriculare și a altor taxe adiționale ca: impozitul rutier anual, plata pentru revizia tehnica precum și asigurarea sînt suportate de Cumpărător.</p>
			<p><b>2.2.</b> Din momentul livrării efective şi semnării actului de predare primire de către Părțile contractante, riscul cedării/defectării autovehiculului trece la Cumpărător.</p>
			<p><b>2.3.</b> Livrarea automobilului/automobilelor se înregistrează printr-un act de primire-predare (Anexa nr. 1) care este o parte integrantă din prezentul contract și confirmă faptul livrării automobilului/automobilelor la adresa prevazuta in puncutul 1.1 al prezentului contract.</p>
			<p><b>2.4.</b> Cumpărătorul este obligat să preia automobilul/automobilele în perioada specificată la punctul 2.1. al acestui contract. Încălcarea condițiilor de acceptare/preluare a automobilului/automobilelor specificate la punctul 2.1. nu exonerează Cumpărătorul de obligația de a prelua automobilului/automobilele. În cazul întârzierii preluării automobilului/automobilelor, riscul defectării accidentale ale acestuia/acestora aparține Cumpărătorului din data stabilită pentru preluarea  automobilului/automobilelor în conformitate cu p.2.1.</p>
			<p><b>2.5.</b> Vânzătorul are dreptul să întârzie livrarea automobilului/automobilelor pînă la momentul îndeplinirii de către Cumpărător a tuturor obligațiilor de plată.</p>
		</div>
		<div class="gr">
			<h3 class="ttl">3. PREȚUL ȘI ORDINEA EFFECTUĂRII PLĂȚILOR.</h3>
			<p><b>3.1.</b> Prețul total al automobilului constituie '.parseCurr($_POST['prc']).'.00 lei și achitarea lui se va efectua conform termenilor stabiliți în p.3.3 al prezentului contract.</p>
			<p><b>3.2.</b> Costul specificat în p. 3.1. include cheltuielile suportate de către Vânzător în procesul de livrare a automobilului/automobilelor la depozitul acestuia precum și toate sumele necesare procedurii de vămuire. În cazul unor modificării ale reglementărilor administrative, întroduse după semnarea acestui contract și care implică costuri suplementare din partea Vânzătorului, în ceea ce privește procedura de vămuire sau de înregistrare fiscală, costul automobilului/automobilelor ca fiind majorat în funcție de cheltuielile suplementare suportate de către Vânzător în acest sens.</p>
		</div>
		<div class="conf">CONFIDENTIAL</div>
	</div>
	
	<div class="sep"></div>
	<div class="pg">
		<div class="gr">
			<h3 class="ttl">4. RESPONSABILITĂŢILE PĂRŢILOR ŞI CONDIŢIILE DE REZILIERE A CONTRACTULUI.</h3>
			<p><b>4.1.</b> Părțile sunt responsabile pentru neîndeplinirea sau îndeplinirea necorespunzătoare a obligațiilor contractuale, în conformitate cu prevederile prezentului contract și legislația în vigoare pe teritoriul Republicii Moldova.</p>
			<p><b>4.2.</b> În cazul nerespectării de către Vânzător a clauzelor contractuale specificate în p. 2.1. din prezentul contract, Cumpărătorul are dreptul să solicite de la Vânzător plata dobânzii la o rată de 0,1% din suma avansului (depozit) plătit pentru fiecare zi de întârziere.</p>
			<p><b>4.3.</b> În cazul nerespectării de către Cumpărător a termenelor de plată stabilite,Vînzătorul are dreptul să ceară Cumpărătorului să achite o penalitate în mărime de 0,1% din costul automobilului/automobilelor, pentru fiecare zi de întârziere a plății.</p>
			<p><b>4.4.</b> În cazul întârzierii preluării automobilului/automobilelor de 5 (cinci) zile lucrătoare, Vânzătorul poate solicita Cumpărătorului să achite pentru servicii de păstrare sumă de 50 lei pentru fiecare zi de întârziere de la termenul stabilit pentru preluarea automobilului/automobilelor.</p>
			<p><b>4.5.</b> În cazul întârzierii preluării automobilului/automobilelor de 10 (zece) zile lucrătoare, Vânzătorul poate rezilia prezentul contract în mod unilateral.</p>
			<p><b>4.6.</b> În cazul rezilierii contractului în baza p. 4.5. al prezentului contract, Vânzătorul are dreptul să rețină un echivalent bănesc pentru daune-interese în cuantum de 10% din costul total al automobilului/automobilelor.</p>
			<p><b>4.7.</b> În cazul rezilierii contractului în baza p. 4.5. al prezentului contract, Cumpărătorul are dreptul să recupereze toate sumele bănești achitate în virtutea prezentului contract, minus costul pentru serviciul de păstrare, penalități și amenzi aferente.</p>
			<p><b>4.8.</b> Vânzătorul nu poartă răspundere pentru funcționarea corespunzătoare a unor elemente ale funcționalității declarate a automobilului (Apple Car Play, Android Auto, Mirror Link, DAB, TV, Maps, Street View, Media Command, Car Connect, etc.), a căror funcționare are legătură cu posibilitățile de acces la software, serviciile de comunicații/conexiune furnizate de terțe persoane - furnizori de servicii, autori de software, furnizori de servicii în baza autorizațiilor pentru utilizarea acestor programe în baza principiului regional, și/sau în dependență de modelul, tipul, versiunile firmware a dispozitivelor de conexiune.</p>
			<p><b>4.9.</b> Vânzătorul nu asigură efectuarea reparațiilor gratuite după încheerea prezentului contract, daca Cumpărătorul a verificat tehnic automobilul în momentul achiziționării sau dacă nu a oferit vreo garantie care să specifice conditiile responsabilității față de automobilul procurat.</p>
			<p><b>4.10.</b> În momentul efectuării achiziției, Cumpărătorul este obligat să se familiarizeze cu Ghidul de exploatare și întreținere a automobilului.</p>
			<p><b>4.11.</b> Cumparatorul se obligă să aibă o atitudine grijulie față de automobil, să exploateze automobilul conform destinației acestuia, ținând cont de posibilitățile și specificațiile tehnice ale acestuia.</p>
			<p><b>4.12.</b> Cumparatorul se obligă să asigure la timp deservirea tehnică a automobilului în cadrul unui centru de deservire autorizat, în conformitate cu kilometrajul parcurs.</p>
			<p><b>4.13.</b> Cumparatorul se obligă să alimenteze automobilul doar cu tipul de combustibil recomandat de către Producător.</p>
			<p><b>4.14.</b> Cumparatorul se obligă după deservirea tehnică să efectueze mențiunea corespunzătoare în Cartela de deservire.</p>
			<p><b>4.15.</b> Cumparatorul se obligă să păstreze prezentele prevederi, în calitate de parte integrantă a documentelor automobilului, și să le transmită împreună cu automobilul în cazul schimbării proprietarului.</p>
			<p><b>4.16.</b> Cumparatorul și Vînzătorul se obligă să respecte prevederile prezentului contract.</p>
			<p><b>4.17.</b> Cumpărătorul este în drept să transmită altei persoana terțe, care prin urmare se va numi Cesionar, achitarea creanței pentru automobil, în favoarea Vânzătorului și doar cu acordul acestuia. Aceste prevederi vor fi întocmite prin anexa nr 2 iar acordul tuturor părților va fi exprimat prin aplicarea semnaturii olografe sau semnaturii electronice după caz.</p>
		</div>
		<div class="gr">
			<h3 class="ttl">5. ALTE CLAUZE.</h3>
			<p><b>5.1.</b> Prezentul contract este întocmit în limba română, în trei exemplare (câte un exemplar pentru fiecare parte, Vânzător și Cumpărător, ASP), care produc aceleași efecte juridice. Părțile înțeleg în întregime obiectul prezentului contract și convin ca orice modificări la acesta să fie întocmite în formă scrisă în mod anticipat.</p>
			<p><b>5.2.</b> Părţile se obligă să soluționeze orice litigii pe calea negocierilor amiabile. Părțile stabilesc și sunt de acord ca termenul de înaintare a pretențiilor care decurg din prezentul contract, este de 15 zile calendaristice, de la data primirii acestora. în cazul în care Părțile nu ajung la un consens, litigiile sunt soluționate de către o instanță competentă a Republicii Moldova.</p>
			<p><b>5.3.</b> Cumpărătorul exprimă consimțământul pentru prelucrarea și folosirea de către Vânzător și/sau persoana autorizată pentru deservirea tehnică, datelor cu caracter personal (în special, doar ne limitându-se - numele si prenumele, adresa poștală, numărul de telefon , email, locul de munca si etc.) în scopul informării Cumpărătorului și suportului relațiilor de contact în cadrul contractului încheiat.</p>
		</div>
		<div class="conf">CONFIDENTIAL</div>
	</div>
	
	<div class="sep"></div>
	<div class="pg x1">
		<div class="gr">
			<p><b>5.4.</b> Prezentul contract este confidențial.</p>
			<p><b>5.5.</b> Prezentul contract întră în vigoare la data semnării acestuia de către ambele părți și este valabil pînă la executarea sa în întregime.</p>
			<p><b>5.6.</b> La prezentul contract se anexează:</p>
			<p class="sb"><b>5.6.1.</b> Anexa nr.1 - Actul de primire-predare.</p>
		</div>
		<div class="flx">
			<div id="chap6_txt">
				<div class="gr">
					<h3 class="ttl">6. GARANȚII</h3>
					<p><b>6.1.</b> Vânzătorul confirmă Cumpărătorului că este proprietar a autovehiculului conform adeverinței vamale, precum și garantează Cumpărătorului că autovehiculul nu este obiect al gajului, sechestrului, nu este obiect al unui litigiu, la fel nu este grevat de careva drepturi ale terțelor persoane. În caz contrar, Vânzătorul se obligă în termen de 3 zile lucrătoare să restituie Cumpărătorului prețul integral al prezentului contract și prejudiciul cauzat.</p>';
					if ( isset($_POST['grnt_txt']) ){
						$i = 2;
						foreach ($_POST['grnt_txt'] as $v){
							if ($v!=''){
								//if ($i==1){$rtrn .= '<h3 class="ttl">7. Garanție.</h3>';}
								$rtrn .= '
								<p><b>6.'.$i.'.</b> '.$v.'</p>';
								$i++;
							}
						}
					}
				$rtrn .= '
				</div>
			</div>
			<div class="ws"></div>
			<table class="n2">
				<tr><td>CUMPARATOR</td><td>VINZATOR</td></tr>
				<tr><td>'.$zcont.'</td><td><b class="txt_cpt">'.strtolower($_POST['u_nm']).'</b><br/>'.$_POST['u_adr'].'</br>'.($_POST['u_tp']=='fiz'?'cp':'cf').': <span class="txt_up">'.$_POST['u_cf_idno'].'</span><br/>'.($_POST['u_tp']=='fiz'?'dat.nast.: '.date( 'd.m.Y', strtotime( $_POST['u_tva_dt'] ) ):'TVA: '.$_POST['u_tva_dt']).'<br/>'.($_POST['u_tp']=='fiz'?'dat.el.: '.date( 'd.m.Y', strtotime( $_POST['u_iban_dt_tk'] ) ):'IBAN: '.$_POST['u_iban_dt_tk']).'</td></tr>
			</table>
			<div class="ws"></div>
			<div class="sign">
				<div class="s1">Semnatura / L. Ş.<div class="ln"></div>'.(isset($_POST['stamp'])&&$_POST['stamp']==1?'<div class="stamp ghost"><div class="signature"></div></div>':'').'</div>
				<div class="s2">Semnatura / L. Ş.<div class="ln"></div></div>
			</div>
			<div class="ws" style="max-height:20mm;"></div>
		</div>
		<div class="conf">CONFIDENTIAL</div>
	</div>
	
	<div class="sep"></div>
	<div class="pg x2">
		<div class="flx">
			<div class="head">
				<div class="anx">Anexa nr 1 la</div>
				<div class="nr">Contractul de vânzare-cumpărare nr. '.$abr.$cont_y.$cont_q.'/'.$cont_n.'__din_'.$zdate.'</div>
				<div class="ttl">ACT DE PREDARE - PRIMIRE<br/>DIN DATA DE '.$zdate.'</div>
			</div>
			<div class="gr">
				<p><b>SAUTO SRL</b>, reprezentat legal de dl Olăriță Veaceslav, în calitate de administrator, care reprezintă interesele Societăţii în baza Actului Constitutiv și normativelor interne, înregistrată la Camera Înregistrării de Stat cu Numarul de Identificare de Stat – 1017600006845, denunumit în continare <b>Cumpărător</b></p>
				<p><span class="txt_cpt">'.strtolower($_POST['u_nm']).'</span>, '.($_POST['u_tp']=='fiz'?'cp':'cf').' <span class="txt_up">'.$_POST['u_cf_idno'].'</span>, '.$_POST['u_adr'].' in calitate de <b>Vînzător</b>, au convenit încheierea prezentului contract, după cum urmează am luat cunostinta cu factura și sau specificația din anexa contractului de vânzare cumpărare încheiat cu Vînzătorul, acesta primeşte în proprietate autoturismul descris mai jos, în stare buna de functionare, cu toate accesoriile conform configuratiei:</p>
				<table class="n1">
					<tr><td class="n1">Marca</td><td class="n2">'.(isset($_POST['br']) ? ucwords(strtolower(str_replace('_', ' ', $_POST['br']))) : '').'</td><td class="n1">Model</td><td class="n2">'.(isset($_POST['mo']) ? ucwords(str_replace('_', ' ', $_POST['mo'])) : '').'</td></tr>
					<tr><td class="n1">Anul Fabricatiei</td><td class="n2">'.$_POST['yr'].'</td><td class="n1">Culoare</td><td class="n2">'.(isset($lng['l']['car']['clr'][ $_POST['clr'] ])?$lng['l']['car']['clr'][ $_POST['clr'] ]:$_POST['clr']).'</td></tr>
					<tr><td class="n1">Serie caroserie</td><td class="n2">'.$_POST['vin'].'</td><td class="n1"></td><td class="n2"></td></tr>
				</table>
				<div class="inf">
					<div class="x n1">
						<p>Am predat</p><br/>
						<p><b><span class="ttl">CUMPĂRĂTOR</span><br/>SAUTO SRL</b></p>
						<p>_______________'.(isset($_POST['stamp'])&&$_POST['stamp']==1?'<span class="stamp ghost"><span class="signature"></span></span>':'').'</p>
					</div>
					<div class="x n2">
						<p>Am primit</p><br/>
						<p><b><span class="ttl">VINZATOR</span></b></p>
						<p>_______________</p>
						<p>Tel._________________</p>
						<p>Email_________________</p>
					</div>
				</div>
			</div>
			
			<div style="text-align: center;">
				<span style="font-weight: bold; font-size: 0.8rem;">Declarații ale clientului:</span>
			</div>
			<div style="text-align: justify; margin-bottom: 0.5mm; line-height: 3.5mm;">
				<span style="font-size: 0.75rem;"><span style="font-weight: bold;">1.</span> Confirm corectitudinea datelor prezentate și îmi asum obligația să comunic în scris către SAUTO SRL orice modificare referitoare la cele declarate mai sus, în termen de 5 zile lucrătoare de la data eliberării documentului confirmativ. Confirm, de asemenea, proveniența legală a mijloacelor bănești, încasate/depuse în cont, inclusiv a mijloacelor ce vor derula prin contul/conturile mele.<br/>
				<span style="font-weight: bold;">2.</span> Îmi exprim consimțământul expres la prelucrarea de către SAUTO SRL a datelor mele cu caracter personal care sunt prelucrate în scopuri legate de deservirea calitativă aferentă serviciilor prestate de SAUTO SRL, inclusiv alte cazuri conform legislației în vigoare. Aceste acțiuni pot fi efectuate și prin utilizarea mijloacelor informatice automatizate în conformitate cu prevederile Legii Nr. 133 din 08.07.2011 privind protecția datelor cu caracter personal.<br/>
				<span style="font-weight: bold;">3.</span> Prin prezentul, declar pe propria răspundere că sunt beneficiarul efectiv al contului/lor, implicit al mijloacelor bănești utilizate prin intermediul acestuia/acestora.</span>
			</div>
			<div style="margin-top: 1mm; font-size: 0.7rem; text-align: justify;">
				<span style="vertical-align: super; font-size: 0.65rem;">1</span> <span style="font-weight: 600;">Persoană expusă politic</span> - persoana fizică definită conform <span style="font-weight: 600;">art. 8 din Legea nr.308/2017</span> cu privire la prevenirea și combaterea spălării banilor și finanțării terorismului.
			</div>
			
			<div class="ws"></div>
			
		</div>
		<div class="conf">CONFIDENTIAL</div>
	</div>
';

// Include KYC 
include(__DIR__.'/kyc_helper.php');
if (requiresKycPages('vinzare_sauto')) {
	$rtrn .= '
	<div class="sep"></div>';
	
	includeKycPages();
}

echo $rtrn;
?>


