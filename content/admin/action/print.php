<?php //defined( '_DOIT' ) or die( 'Restricted access' );
include_once('environment.php');

define('_DOIT', 1); define('_DEFAULT', $_SERVER['DOCUMENT_ROOT'].'/content/default');
require_once (_DEFAULT.'/language.php');
$z_site = 'https://www.sauto.md';
$zcont = '
<b>“SAUTO” SRL</b><br/>
<span>Republica Moldova, MD-2084, mun.Chişinau</span><br/>
<span>or.Cricova, str.Chisinaului 84, ap.(of.) 39</span><br/>
<span>IBAN: <b>MD26VI022582000000500MDL</b></span><br/>
<span>în B.C.“VICTORIABANK S.A.”, <b>VICBMD2XXXX</b></span><br/>
<span>c/f <b>1017600006845</b>, c/TVA <b>0609417</b></span>';

$rtrn = '
<!DOCTYPE html>
<html>
	<head>
		<meta http-equiv="Content-type" content="text/html; charset=UTF-8" />
		<meta name="viewport" content="width=device-width, initial-scale=1, minimum-scale=1" id="mobile_viewport" />
		
		<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
		
		<title>Print</title>
		
		<link rel="stylesheet" type="text/css" href="/content/default/css/default.css" />
		<style>
			@media print {
				@page {size:auto; size: A4 portrait; margin:0;}
				* {-webkit-print-color-adjust:exact !important; color-adjust:exact !important; print-color-adjust:exact !important;}
				.sep {display:none;}
			}
			
			.base {font-family:"def";}
			.base > .pg {width:210mm; height:297mm; margin:0 auto; padding:5mm 20mm; background-color:#fff; position:relative;}
			.base > .pg.bg {background:#fffe url("/media/images/site/print/bg_pg.webp") repeat center / contain; background-blend-mode:soft-light;}
			.cont {width:100%; float:left; padding:5mm 0 0; font-size:0.8rem;}
			.logo {float:right;}
			.ln {border-bottom:1px solid; clear:both; padding:2mm;}
			.sep {clear:both; padding:2mm;}
			
			table {width:100%; border-collapse:collapse; margin-top:5mm;}
			tr {border:1px solid;}
			tr > td {text-align:center; padding:3mm 0;}
			tr > td:not(:first-child) {border-left:1px solid;}
			
			.sign {margin-top:20mm;}
			.sign > * {width:40%; display:flex; flex-flow:row; justify-content:space-between; align-items:center;}
			.sign > * > .ln {flex-grow:1; line-height:0; text-align:center; position:relative;}
			.sign > * > .ln > .sgn {position:absolute; top:0; left:0; transform:translateY(-50%);}
			.sign > .s1 {float:left;}
			.sign > .s2 {float:right;}
			
			.flx {display:flex; flex-flow:column wrap; justify-content:space-evenly; min-height:150mm;}
			.flx > .ws {flex-grow:1;}
			
			/*
			ol {list-style-type:none; counter-reset:item; margin:0; padding:0;}
			ol > li {display:table; counter-increment:item; margin-bottom:0.6em;}
			ol > li:before {content: counters(item, ".") ". "; display: table-cell; padding-right: 0.6em;}
			li ol > li {margin:0;}
			li ol > li:before {content:counters(item, ".") " ";}
			*/
			ol {counter-reset:item;}
			li{display:block;}
			li:before {content:counters(item, ".") ". "; counter-increment:item;}
			
			.conf {position:absolute; left:5mm; bottom:3mm; opacity:.2; font-size:.7rem;}
		</style>
		<script>
			/*window.print();*/
			
			$(document).ready(function(){
				function px2cm(px){
					var d = $("<div/>").css({ position: "absolute", top : "-1000cm", left : "-1000cm", height : "1000cm", width : "1000cm" }).appendTo("body");
					var px_per_cm = d.height() / 1000;
					d.remove();
					return px / px_per_cm;
				}
				//px2cm(100);
				//alert(px2cm(1000));
			})
		</script>
	</head>
	<body>';
		if (1==1){
			$rtrn .= '
			<style>
				.base {font-family:"def_l"; color:#000;}
				.head > .nr {text-align:center; font-size:1.2rem; font-family:"def";}
				.head > .nm {text-align:center; font-size:1.1rem; font-weight:bold;}
				.head > .inf {width:100%; display:inline-block;}
				.head > .inf > .pos {float:left;}
				.head > .inf > .date {float:right;}
				
				.who {margin-top:1rem; text-align:justify;}
				
				.gr {margin-top:7mm; text-align:justify;}
				.gr > .ttl {text-align:center; font-weight:bold;}
				.gr > .sb {padding-left:10mm;}
				
				table.n1 tr > td.n1 {width:20%;}
				table.n1 tr > td.n2 {width:30%;}
				
				table.n2 tr {height:10mm;}
				table.n2 tr > td {width:50%;}
				
				.pg.x > .flx {min-height:230mm;}
				.pg.x2 > .flx {min-height:280mm;}
				
				.pg.x2 .res > .its {display:flex; flex-flow:row wrap; justify-content:space-between;}
				.pg.x2 .res > .its > span {margin:5mm 0 0;}
				.pg.x2 .gr > .inf {margin-top:15mm;}
				.pg.x2 .gr > .inf > .x {width:50%;}
				.pg.x2 .gr > .inf > .x .ttl {font-size:1.2rem;}
				.pg.x2 .gr > .inf > .x > * {width:100%; display:block;}
				.pg.x2 .gr > .inf > .n1 {float:left; text-align:left;}
				.pg.x2 .gr > .inf > .n2 {float:right; text-align:right;}
				.pg.x2 .head > .nr {margin-top:10mm; text-align:left; font-size:1rem;}
				.pg.x2 .head > .nm {margin-top:25mm; font-family:"def"; font-weight:normal;}
			</style>
			<div id="p_cont" class="base">
				<div class="pg bg">
					<div class="head">
						<div class="nr">CONTRACT nr. AF08/2790</div>
						<div class="nm">Contract de vânzare-cumpărare a autovehiculului</div>
						<div class="inf">
							<span class="pos">mun. Chișinău</span><span class="date">09.08.2023</span>
						</div>
					</div>
					<div class="who">
						<b>SAUTO SRL</b>, reprezentat legal de dl Olăriță Veaceslav, în calitate de administrator, care reprezintă interesele Societăţii în baza Actului Constitutiv și normativelor interne, înregistrată la Camera Înregistrării de Stat cu Numarul de Identificare de Stat – 1017600006845, denunumit în continare <b>Vînzător</b>
						<br/><br/>Turcan Mihail  cp 2006002038168, Chisinau, Str. Calea Orheiului nr.109 bl.3 ap.144 în calitate de <b>Cumparator</b>, au convenit încheierea prezentului contract, după cum urmează
					</div>
					<div class="gr">
						<h3 class="ttl">1. OBIECTUL CONTRACTULUI.</h3>
						<p><b>1.1.</b> Obiectul prezentului contract este o unitate de transport:</p>
						<table class="n1">
							<tr><td class="n1">Marca</td><td class="n2">Seat</td><td class="n1">Model</td><td class="n2">Ibiza</td></tr>
							<tr><td class="n1">Anul Fabricatiei</td><td class="n2">2013</td><td class="n1">Culoare</td><td class="n2"></td></tr>
							<tr><td class="n1">Serie caroserie</td><td class="n2">VSSZZZ6JZDR188695</td><td class="n1"></td><td class="n2"></td></tr>
							<tr><td class="n3" rowspan="1" colspan="4">Livrarea automobilului se execută la adresa: Mun. Chisinau, str. Calea Moșilor 11</td></tr>
						</table>
						<p><b>1.2.</b> Vânzătorul se obligă să transfere în proprietatea Cumpărătorului, iar Cumpărătorul se obligă să preia/accepte și să achite prețul pentru automobilul/automobilele.</p>
					</div>
					<div class="gr">
						<h3 class="ttl">2. TERMENELE ȘI CONDIȚIILE DE LIVRARE.</h3>
						<p><b>2.1.</b> Vânzătorul execută livrarea autovehiculului către Cumpărător, întocmește și transmite Cumpărătorului documentele corespunzătoare necesare pentru înregistrarea dreptului de proprietate la Agenția Servicii Publice, în decurs de 25 (Douazeci si cinci) zile calendaristice din momentul semnării prezentului contract, cu condiția îndeplinirii de către Cumpărător a tuturor obligațiunilor ce îi revin in punctul 3.3. Toate cheltuielel ce țin de întocmirea certificatului de înmatriculare și a altor taxe adiționale ca: impozitul rutier anual, plata pentru revizia tehnica precum și asigurarea sînt suportate de Cumpărător.</p>
						<p><b>2.2.</b> Din momentul livrării efective şi semnării actului de predare primire de către Părțile contractante, riscul cedării/defectării autovehiculului trece la Cumpărător.</p>
						<p><b>2.3.</b> Livrarea automobilului/automobilelor se înregistrează printr-un act de primire-predare (Anexa nr. 1) care este o parte integrantă din prezentul contract și confirmă faptul livrării automobilului/automobilelor la adresa furnizată de către Cumpărător în acest sens.</p>
						<p><b>2.4.</b> Cumpărătorul este obligat să preia automobilul/automobilele în perioada specificată la punctul 2.1. al acestui contract. Încălcarea condițiilor de acceptare/preluare a automobilului/automobilelor specificate la punctul 2.1. nu exonerează Cumpărătorul de obligația de a prelua automobilului/automobilele. În cazul întârzierii preluării automobilului/automobilelor, riscul defectării accidentale ale acestuia/acestora aparține Cumpărătorului din data stabilită pentru preluarea  automobilului/automobilelor în conformitate cu p.2.1.</p>
						<p><b>2.5.</b> Vânzătorul are dreptul să întârzie livrarea automobilului/automobilelor pînă la momentul îndeplinirii de către Cumpărător a tuturor obligațiilor de plată.</p>
					</div>
					<div class="conf">CONFIDENTIAL</div>
				</div>
				
				<div class="sep"></div>
				<div class="pg bg">
					<div class="gr">
						<h3 class="ttl">3. PREȚUL ȘI ORDINEA EFFECTUĂRII PLĂȚILOR.</h3>
						<p><b>3.1.</b> Prețul total al automobilului constituie 132,000.00 lei și achitarea lui se va efectua conform termenilor stabiliți în p.3.3 al prezentului contract.</p>
						<p><b>3.2.</b> Costul specificat în p. 3.1. include cheltuielile suportate de către Vânzător în procesul de livrare a automobilului/automobilelor la depozitul acestuia precum și toate sumele necesare procedurii de vămuire. În cazul unor modificării ale reglementărilor administrative, întroduse după semnarea acestui contract și care implică costuri suplementare din partea Vânzătorului, în ceea ce privește procedura de vămuire sau de înregistrare fiscală, costul automobilului/automobilelor ca fiind majorat în funcție de cheltuielile suplementare suportate de către Vânzător în acest sens.</p>
						<p><b>3.3.</b> Cumparatorul achita in avans suma de 132,000.00 lei.</p>
					</div>
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
					</div>
					<div class="conf">CONFIDENTIAL</div>
				</div>
				
				<div class="sep"></div>
				<div class="pg bg">
					<div class="gr">
						<p><b>4.10.</b> În momentul efectuării achiziției, Cumpărătorul este obligat să se familiarizeze cu Ghidul de exploatare și întreținere a automobilului.</p>
						<p><b>4.11.</b> Cumparatorul se obligă să aibă o atitudine grijulie față de automobil, să exploateze automobilul conform destinației acestuia, ținând cont de posibilitățile și specificațiile tehnice ale acestuia.</p>
						<p><b>4.12.</b> Cumparatorul se obligă să asigure la timp deservirea tehnică a automobilului în cadrul unui centru de deservire autorizat, în conformitate cu kilometrajul parcurs.</p>
						<p><b>4.13.</b> Cumparatorul se obligă să alimenteze automobilul doar cu tipul de combustibil recomandat de către Producător.</p>
						<p><b>4.14.</b> Cumparatorul se obligă după deservirea tehnică să efectueze mențiunea corespunzătoare în Cartela de deservire.</p>
						<p><b>4.15.</b> Cumparatorul se obligă să păstreze prezentele prevederi, în calitate de parte integrantă a documentelor automobilului, și să le transmită împreună cu automobilul în cazul schimbării proprietarului.</p>
						<p><b>4.16.</b> Cumparatorul și Vînzătorul se obligă să respecte prevederile prezentului contract.</p>
						<p><b>4.17.</b> Cumparatorul se obligă să achite serviciile de diagnosticare și înlăturarea a defecțiunilor în cazul în care se depistează că defecțiunile date au survenit din cauza părții exploatatoare după procurare.</p>
					</div>
					<div class="gr">
						<h3 class="ttl">5. ALTE CLAUZE.</h3>
						<p><b>5.1.</b> Prezentul contract este întocmit în limba română, în trei exemplare (câte un exemplar pentru fiecare parte, Vânzător și Cumpărător, ASP), care produc aceleași efecte juridice. Părțile înțeleg în întregime obiectul prezentului contract și convin ca orice modificări la acesta să fie întocmite în formă scrisă în mod anticipat.</p>
						<p><b>5.2.</b> Părţile se obligă să soluționeze orice litigii pe calea negocierilor amiabile. Părțile stabilesc și sunt de acord ca termenul de înaintare a pretențiilor care decurg din prezentul contract, este de 15 zile calendaristice, de la data primirii acestora. în cazul în care Părțile nu ajung la un consens, litigiile sunt soluționate de către o instanță competentă a Republicii Moldova.</p>
						<p><b>5.3.</b> Cumpărătorul exprimă consimțământul pentru prelucrarea și folosirea de către Vânzător și/sau persoana autorizată pentru deservirea tehnică, datelor cu caracter personal (în special, doar ne limitându-se - numele si prenumele, adresa poștală, numărul de telefon , email, locul de munca si etc.) în scopul informării Cumpărătorului și suportului relațiilor de contact în cadrul contractului încheiat.</p>
						<p><b>5.4.</b> Prezentul contract este confidențial.</p>
						<p><b>5.5.</b> Prezentul contract întră în vigoare la data semnării acestuia de către ambele părți și este valabil pînă la executarea sa în întregime.</p>
						<p><b>5.6.</b> La prezentul contract se anexează:</p>
						<p class="sb"><b>5.6.1.</b> Anexa nr.1 - Actul de primire-predare.</p>
					</div>
					<div class="conf">CONFIDENTIAL</div>
				</div>
				
				<div class="sep"></div>
				<div class="pg bg x">
					<div class="flx">
						<div class="gr">
							<h3 class="ttl">6. GARANȚII</h3>
							<p><b>6.1.</b> Vânzătorul confirmă Cumpărătorului că este proprietar a autovehiculului conform adeverinței vamale, precum și garantează Cumpărătorului că autovehiculul nu este obiect al gajului, sechestrului, nu este obiect al unui litigiu, la fel nu este grevat de careva drepturi ale terțelor persoane. În caz contrar, Vânzătorul se obligă în termen de 3 zile lucrătoare să restituie Cumpărătorului prețul integral al prezentului contract și prejudiciul cauzat.</p>
						</div>
						<div class="ws"></div>
						<table class="n2">
							<tr><td>VINZATOR</td><td>CUMPARATOR</td></tr>
							<tr><td>'.$zcont.'</td><td><b>Turcan Mihail</b><br/>Republica Moldova, mun.Chişinau, or.Chisinau, str.Calea Orheiului 109, BL.3, ap.144</br>cp 2006002038168<br/>dat.nast. 12.08.1989<br/>dat.el. 18.08.2014</td></tr>
						</table>
						<div class="ws"></div>
						<div class="sign">
							<div class="s1">Semnatura<div class="ln"></div></div>
							<div class="s2">L. Ş.<div class="ln"></div></div>
						</div>
					</div>
					<div class="conf">CONFIDENTIAL</div>
				</div>
				
				<div class="sep"></div>
				<div class="pg bg x2">
					<div class="flx">
						<div class="head">
							<div class="anx">Anexa nr 1 la</div>
							<div class="nr">Contractul de vânzare-cumpărare nr.AF08/2790__din_09.08.2023</div>
							<div class="nm">ACT DE PREDARE - PRIMIRE<br/>DIN DATA DE 09.08.2023</div>
						</div>
						<div class="gr">
							<p><b>SAUTO SRL</b>, reprezentat legal de dl Olăriță Veaceslav, în calitate de administrator, care reprezintă interesele Societăţii în baza Actului Constitutiv și normativelor interne, înregistrată la Camera Înregistrării de Stat cu Numarul de Identificare de Stat – 1017600006845, denunumit în continare <b>Vînzător</b></p>
							<p>Turcan Mihail, cp 2006002038168, or.Chisinau, str.Calea Orheiului nr.109 BL.3 ap.144 in calitate de <b>Cumpărător</b>, au convenit încheierea prezentului contract, după cum urmează am luat cunostinta cu factura și sau specificația din anexa contractului de vânzare cumpărare încheiat cu Vînzătorul, acesta primeşte în proprietate autoturismul descris mai jos, în stare buna de functionare, cu toate accesoriile conform configuratiei:</p>
							<table class="n1">
								<tr><td class="n1">Marca</td><td class="n2">Seat</td><td class="n1">Model</td><td class="n2">Ibiza</td></tr>
								<tr><td class="n1">Anul Fabricatiei</td><td class="n2">2013</td><td class="n1">Culoare</td><td class="n2"></td></tr>
								<tr><td class="n1">Serie caroserie</td><td class="n2">VSSZZZ6JZDR188695</td><td class="n1"></td><td class="n2"></td></tr>
							</table>
							<div class="inf">
								<div class="x n1">
									<p>Am predat</p><br/>
									<p><b><span class="ttl">VINZATOR</span><br/>SAUTO SRL</b></p>
									<p>_______________</p>
								</div>
								<div class="x n2">
									<p>Am primit</p><br/>
									<p><b><span class="ttl">CUMPĂRĂTOR</span></b></p>
									<p>_______________</p>
									<p>Tel._________________</p>
									<p>Email_________________</p>
								</div>
							</div>
						</div>
						<div class="ws"></div>
						<div class="res">
							<b>Din ce surse ati aflat de produsul nostru:</b><br/>
							<div class="its">
								<span>999.md</span> <span>De pe sait SAUTO.MD</span> <span>Pozitionare(de pe strada)</span> <span>Prin recomandare (cunostinte)</span> <span>Am procurat în anterior</span> <span>Cunosc de mult compania</span> <span>Facebook</span> <span>Instagram</span>
							</div>
						</div>
					</div>
					<div class="conf">CONFIDENTIAL</div>
				</div>
			</div>';
		}
	$rtrn .= '
	</body>
</html>';

echo $rtrn;

?>