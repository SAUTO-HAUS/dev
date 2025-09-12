<?php defined( '_DOIT' ) or die( 'Restricted access' );

$abr = 'TE';

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
	
	.pg:not(.d2) tr > td {padding:2mm;}
	
	.pg > .gr:first-of-type {margin-top:0;}
	
	.pg.d1 > .flx {min-height:260mm;}
	.pg.d1 > .flx > .sign {margin-top:10mm;}
	
	.pg.d1.p3 > .flx {min-height:140mm;}
	
	.pg.d2 {font-size:.9rem;}
	.pg.d2 .res > .its {display:flex; flex-flow:row wrap; justify-content:space-between;}
	.pg.d2 .res > .its > span {width:25%; margin:5mm 0 0;}
	.pg.d2 .flx {min-height:280mm;}
	.pg.d2 .gr {margin-top:3mm;}
	.pg.d2 .gr > .inf {margin-top:10mm;}
	.pg.d2 .gr > .inf > .x {width:50%;}
	.pg.d2 .gr > .inf > .x .ttl {font-size:1.2rem;}
	.pg.d2 .gr > .inf > .x > * {width:100%; display:block; position:relative; margin:2mm 0;}
	.pg.d2 .gr > .inf > .n1 {float:left; text-align:left;}
	.pg.d2 .gr > .inf > .n2 {float:right; text-align:right;}
	.pg.d2 .head > .nr {text-align:left; font-size:1rem; font-family:"def_l";}
	.pg.d2 .head > .ttl {margin-top:10mm; font-family:"def"; font-weight:normal;}
	
	table .n2, .txt_up {text-transform:uppercase;}
	.txt_cpt {text-transform:capitalize;}
	
	.pg:not(.d2) p {margin:1mm 0; line-height:4.5mm;}
	
	.ndr_ln {text-decoration:underline;}
	
	#dmg_clk_bx {width:100%; height:45mm; background-color:#fff; position:relative; border:1px solid #eee; text-align:center;}
	#dmg_clk_bx > .img {height:100%; display:inline-block; position:relative;}
	#dmg_clk_bx > .img > .el {width:6mm; height:6mm; overflow:hidden; border-radius:50%; display:flex; justify-content:center; align-items:center; position:absolute; z-index:2; background-color:#e2001a; color:#fff; opacity:.7;}
	#dmg_clk_bx > .img > .el.hide {opacity:0; transform:scale(0);}
	#dmg_clk_bx > .img > .el.inp_hov {opacity:1; transform:scale(1.5);}
	
	#dmg_txt_bx {display: inline;}
</style>
<div id="p_cont" class="base">
	<div class="pg d1 p1 bg">
		<div class="head">
			<div class="nr">CONTRACT nr. '.$abr.$cont_y.$cont_q.'/'.$cont_n.'</div> <!--CONTRACT nr. VC33/2715 [VC - vinzare cumparare, 3 - year last digit, 3 - quarter of the year, 2715 = 2700 + number of contract of this year (now 15)]-->
			<div class="ttl">Contract de intermediere</div>
			<div class="inf">
				<span class="pos">mun. Chișinău</span><span class="date">'.$zdate.'</span>
			</div>
		</div>
		<div class="who">
			<b style="width:100%; display:block; text-align:center;">PĂRȚILE CONTRACTUAL</b>
			<b>SAUTO SRL</b>, IDNO 1017600006845 numit în continuare „Intermediar”, în persoana Administratorului Olăriță Veaceslav, care acționează în baza Statutului, si pe de o parte <span class="ndr_ln">&nbsp;&nbsp;&nbsp;&nbsp;<span class="txt_cpt">'.strtolower($_POST['u_nm']).'</span>, '.($_POST['u_tp']=='fiz'?'cp':'cf').': <span class="txt_up">'.$_POST['u_cf_idno'].'</span>&nbsp;&nbsp;&nbsp;&nbsp;</span> pe de alta parte numit in continuare benificiar numit în continuare ”Client”, iar împreuna Părți, au încheiat prezentul contract cu privire la următoarele clauza, care acționează în baza Statutului,  iar împreuna Părți, au încheiat prezentul contract cu privire la următoarele clauza</p>
		</div>
		<div class="gr">
			<h3 class="ttl">1. OBIECTUL CONTRACTULUI.</h3>
			<p><b>1.1.</b> Conform prevederilor prezentului contract, o parte denumită în continuare (intermediar) se obligă ață de cealaltă parte, denumită în continuare (client) să acționeze în calitate de mijlocitor la încheierea unui sau mai multor contracte de vânzare-cumpărare între Client si Cumpărător.</p>
			<p><b>1.2.</b> Obiectul prezentului Contract îl constituie prestarea de către Intermediar Clientului a serviciilor de intermediere în vânzare a autovehiculului conform anexei nr.1 al prezentului contract prin identificarea cumpărătorului, iar Clientul se obligă să achite în beneficiul Intermediarului remunerație pentru intermediere în formă procentuală în conformitate cu condițiile prezentului Contract.</p>
		</div>
		<div class="gr">
			<h3 class="ttl">2. PREȚUL DE VÎNZARE A AUTOVEHICULULUI.</h3>
			<p><b>2.1.</b> Reieșind din situația pe piață R. Moldova de vânzări a autovehiculelor, la ziua încheierii prezentului contract, Clientul, prin semnarea acestuia, cunoaște prețurile de piață și de comun acord cu Intermediarul au stabilit prețul de vânzare in suma de <span class="ndr_ln">&nbsp;&nbsp;&nbsp;&nbsp;'.parseCurr($_POST['prc']).'&nbsp;&nbsp;&nbsp;&nbsp;</span> Euro autovehiculul <span class="ndr_ln">&nbsp;&nbsp;&nbsp;&nbsp;'.(isset($_POST['br']) ? $_POST['br'] : '').' '.(isset($_POST['mo']) ? $_POST['mo'] : '').'&nbsp;&nbsp;&nbsp;&nbsp;</span> num de inmatriculare <span class="ndr_ln">&nbsp;&nbsp;&nbsp;&nbsp;'.$_POST['vin'].'&nbsp;&nbsp;&nbsp;&nbsp;</span> prețul stabilit de Părți în p.2.1 al prezentului contract poate fi modificat doar prin acord adițional la prezentul contract semnat de părți ce este parte integrantă al prezentului contract.</p>
		</div>
		<div class="gr">
			<h3 class="ttl">3. PREȚUL SERVICIILOR ȘI MODALITATEA DE ACHITARE.</h3>
			<p><b>3.1.</b> Prețul serviciilor achitate de către Client constituie 3% de la prețul stabilit la automobil în p 2.1 a prezentului contract.</p>
			<p><b>3.2.</b> Achitarea serviciilor în cuantumul prevăzut de p.3.1, se efectuează imediat în aceiași zi când este încheiat contractul de vânzare-cumpărare.</p>
			<p><b>3.3.</b> Clientul achită Intermediarului lunar o taxă fixă (cheltuieli de întreținere a autovehiculului în stare curată, parcare) în sumă de 200 (Două sute de lei) pe lună.</p>
			<p><b>3.4.</b> Termenul limită de achitare a taxei fixe prevăzută de p.3.3 al Contractului este data semnării contractului, fiind admise achitări în avans pentru un număr nelimitat de luni sau în cazul rezilierii anticipate, termenul de achitare a datoriei este data rezilierii, suma finală fiind raportată la numărul de luni. Toate achitările sunt efectuate în MDL.</p>
		</div>
		<div class="gr">
			<h3 class="ttl">4. DREPTURILE ȘI OBLIGAȚIILE PĂRȚILOR.</h3>
			<p><b>4.1.</b> Clientul este obligat să pună la dispoziția intermediarului autovehiculul – obiect al intermedierii stipulat în p.2.1 al Contractului, pașaport tehnic, copiile raportului de testare tehnică și a poliței de asigurare sau data perfectării acestora, informația despre autovehicul (starea tehnică, accidentat, reparat, defectele ascunse, recent importat – acte confirmative, reparații curente confirmate prin facturi, numerele telefonului de contact, adresa de e-mail și alte date relevante despre autovehicul și istoria acestuia) prin transmiterea acestora prin act de predare – primire semnat de părți ce este parte integrantă al prezentului Contract.</p>
			<p><b>4.2.</b> Clientul este obligat să decidă în privința costului autovehiculului conf.p.2.1 al contractului, să indice variantele posibile ale vânzării și comunică Intermediarului altă informație care ține de aspectele financiare ale procedurii de vânzare-cumpărare a autovehiculului, iar pe parcursul executării prezentului contract este obligat să se abțină de la contractarea unui alt Intermediar sau identificarea personală a cumpărătorului pe un termen de 6 (șase luni). În cazul indicării acestui termen, contractul este acceptat de Părți ca unul de „intermediere exclusivă”, având ca efecte prevederile art.1182 C.C al R.M, iar în cazul expirării acestui termen, contractul nu mai este exclusiv, devine unul general.</p>
			<p><b>4.3.</b> Clientul este obligat să semneze acordul adițional privind modificarea prevederii p.2.1 al Contractului, în cazul în care ajunge la propria convingere că prețul este unul ne competitiv, este exagerat de mare sau mic în dependență de situația pe piața auto în R. Moldova, în caz contrar intermediarul este în imposibilitate de identificare a potențialului cumpărător și ca efect este generată rezilierea anticipată a contractului.</p>
		</div>
		<div class="conf">CONFIDENTIAL</div>
	</div>
	
	<div class="sep"></div>
	<div class="pg d1 p2 bg">
		<div class="gr">
			<p><b>4.4.</b> Clientul are obligațiunea de a duce răspundere în fața eventualului cumpărător, acesta adresând pretenții Clientului, privind defecțiunile tehnice a autovehiculului, imputabile acestuia.</p>
			<p><b>4.5.</b> Clientul este în drept să verifice în perioada programului de lucru al Intermediarului autovehiculul, să primească informații privind posibilitatea reală de vânzare a acestuia, să solicite și să primească consultații în acest sens, tot o dată de a solicita modificarea prețului de vânzare, de a rezilia contractul adresând intermediarului o notificare de reziliere ca un preaviz de 7 zile lucrătoare din momentul recepționării de Intermediar până la data rezilierii cu perfectarea actelor de predare-primire în acest sens.</p>
			<p><b>4.6.</b> Intermediarul este obligat să întreprindă toate măsurile de identificare a viitorului cumpărător, să intermedieze tranzacția/tranzacțiile avantajoase pentru Client în limita prevederilor prezentului contract, să asigure integritatea și să mențină aspectul interior și exterior al autovehiculului în cazul în care sunt respectate condițiile prevăzute de p.3.3; 3.4 al prezentului Contract.</p>
			<p><b>4.7.</b> Intermediarul este în drept să solicite toată informația despre autovehicul (starea tehnică, testarea efectuată în comun cu clientul și cumpărătorul, proprietate, drepturi reale și garantate și alte informații cu privire la obiectul prezentului contract).</p>
			<p><b>4.8.</b> Intermediarul este în drept să telefoneze clientul, să expedieze mesaje la nr. de telefon, inclusiv la adresa electronică, acesta fiind indicate în actul de predare primire. </p>
			<p><b>4.9.</b> În caz de necesitate, Intermediarul este în drept să pună autovehiculul la dispoziția eventualului cumpărător, verificând prezența permisului de conducere, în scopul examinării autovehiculului în regim de test-drive, examinarea autovehiculului cât și test-drive-ul sunt făcute în prezența unui reprezentant din partea Intermediarului.</p>
		</div>
	
		<div class="gr">
			<h3 class="ttl">5. RĂSPUNDEREA PĂRȚILOR.</h3>
			<p><b>5.1.</b> Clientul de unul singur este responsabil de regimul juridic al autovehiculului transmis conform p.2.1 al Contractului, de starea tehnică a acestuia, actele de proprietate, drepturile terților asupra autovehiculului, iar în relație cu terțul cumpărător și pretențiile acestuia, este responsabil de satisfacerea pretențiilor privind starea tehnică a automobilului, defectele ascunse, regimul juridic a acestuia, drepturile reale sau garantarea a terților în relație cu Clientul și legalitatea actelor sau legalitatea aflării acestui automobil pe teritoriul R. Moldova.</p>
			<p><b>5.2.</b> Clientul prin semnarea prezentului contract declară: automobilul transmis conform p.2.1 și a actului de predare primire – parte integrantă a prezentului contract, este liber de sancțiuni pecuniare, cu acte legale în regulă, legal intrat și aflat pe teritoriul R. Moldova, nu este în căutare, nu este obiect a unui litigiu, nu a fost inundat, modificat structural, nu este delapidat, furat, estorcat, jefuit.</p>
			<p><b>5.3.</b> Răspunderea Intermediarului în relație cu terțul cumpărător sau organele de control și de drept, este doar în limita prezentului Contract și se limitează prin identificarea Cumpărătorului disponibil de a încheia tranzacția de vânzare-cumpărare cu Clientul.</p>
			<p><b>5.4.</b> Intermediarul nu poartă răspundere contractuală, materială, legală, pentru rea-credința Clientului (starea tehnică a automobilului ne satisfăcătoare , defecte ascunse, regimul juridic a acestuia, prezența drepturilor reale sau garantate a terților cu Clientul și legalitatea actelor sau legalitatea aflării acestui automobil pe teritoriul R. Moldova.)</p>
			<p><b>5.5.</b> Intermediarul nu poartă răspundere contractuală, materială, legală pentru rea-credința terțului cumpărător în măsura în care acesta nu-și execută obligațiunile sale prevăzute de contractul de vânzare – cumpărare încheiat cu Clientul, eventual Vânzător.</p>
			<p><b>5.6.</b> Intermediarul nu poartă răspunderea pentru deteriorările sau diminuarea costului autovehiculului dacă acestea sunt legate cu survenirea sau prezența următoarelor circumstanțe: deteriorarea sau defectarea autovehiculului au fost condiționate de proprietățile specifice ale lui, defecte ascunse, despre care Intermediarul nu a știut sau nu a fost informat, de circumstanțele de forță majoră, confirmate în modul corespunzător (acțiunea calamităților naturale – ploi abundente ce ar genera inundații, ninsori abundente, grindină, descărcări electrice, cutremur, furtună, incendiu) și împrejurări care au avut loc nu din vina Intermediarului: sechestrare, ridicare, reținere din partea organelor de drept competente a autovehiculului, în astfel de cazuri Intermediarul anunță de urgență Clientul.</p>
		</div>
		<div class="gr">
			<h3 class="ttl">6. TERMENUL CONTRACTULUI.</h3>
			<p><b>6.1.</b> Contractul este încheiat pe un termen de 1 an din momentul semnării actului de predare primire a autovehiculului.</p>
			<p><b>6.2.</b> Prezentul Contract intră în vigoare din momentul semnării de către Părțile contractante a actului de predare primire a autovehiculului iar efectele acestuia își au extinderea până la executarea obligațiilor contractuale de către Părți, adică până la momentul vânzării autovehiculului, fiind termenul prelungit automat pe un următor termen, încasării onorariilor și cheltuielilor pe orice cale legal admisibilă.</p>
			<p><b>6.3.</b> Prezentul contract este considerat că a fost prelungit pe un nou termen echivalent cu termenul stabilit la p.5.1 al Contractului, în condițiile în care nici Clientul și nici Intermediarul la expirarea termenului nu au inițiat procedura de predare primire a autovehiculului ce constituie obiect a intermedierii iar relațiile contractuale între părți ce țin de executarea obligațiilor de identificare a potențialului cumpărător sau de plata a cheltuielilor de întreținere a autovehiculului conform p. 3.2, 3.3 3.4, fiind efectuate de Părțile prezentului Contract după expirarea termenului contractului.</p>
		</div>
		<div class="conf">CONFIDENTIAL</div>
	</div>
	
	<div class="sep"></div>
	<div class="pg d1 p3 bg">
		<div class="gr">
			<h3 class="ttl">7. CONDIȚIILE DE REZILIERE ȘI NEEXECUTARE.</h3>
			<p><b>7.1.</b> Prezentul contract poate fi reziliat prin semnarea unui acord de reziliere la inițiativa uneia din părți în condițiile încălcărilor esențiale cel puțin a unei norme prevăzute de prezentul contract sau la voința părților, fiind decăzută necesitatea vânzării pentru Client sau imposibilitatea identificării de către Intermediar al potențialului cumpărător, din cauza stării tehnice ne satisfăcătoare a autovehiculului transmis spre intermediere sau/și prețul exagerat de mare, iar în toate cazurile de reziliere se respectă procedura de preaviz de 7 zile lucrătoare expediat de inițiatorul rezilierii celeilalte părți contractante.</p>
			<p><b>7.2.</b> În cazul rezilierii din inițiativa Clientului  prezentului contract, indiferent de cauza acestei rezilieri, Clientul achită Intermediarului cu titlu de compensare a cheltuielilor, suma calculată conform prevederilor p.3.3, 3.4 al Contractului.</p>
			<p><b>7.3.</b> În cazul inițierii procedurii de reziliere anticipată a contractului de către Client sau Intermediar, temei în ambele cazuri servind încălcarea prevederilor p.3.2, 3.3, 3.4, 4.2, 4.3, 4.4, 4.5, 4.6, 4.7, 4.8, 4.9, 5.3, Clientul achită despăgubiri Intermediarului în cuantum de 1,5% din prețul autovehiculului prevăzut în p.2.1 al Contractului.</p>
			<p><b>7.4.</b> Pentru neexecutarea sau executarea necorespunzătoare a prevederilor prezentului contract, Clientului i se aplică sancțiuni sub formă de penalități în cuantum de 5% din suma obligațiunilor de plată calculate conform prevederilor p. 3.1, 3.2, 3.3, 3.4 al prezentului Contract.</p>
		</div>
		<div class="gr">
			<h3 class="ttl">8. MODUL DE SOLUȚIONARE A LITIGIILOR.</h3>
			<p><b>8.1.</b> Toate litigiile și divergențele apărute între Părțile contractante conform prezentului Contract sau în legătură cu el, se soluționează între Părțile contractante pe calea negocierilor.</p>
			<p><b>8.2.</b> În cazul când soluționarea divergențelor pe calea negocierilor nu este posibilă, ele urmează să fie examinate pe calea juridică în conformitate cu legislația în vigoare.</p>
		</div>
		<div class="gr">
			<h3 class="ttl">9. ALTE CLAUZE.</h3>
			<p><b>9.1.</b> Prezentul Contract este perfectat în limba de stat, în două exemplare cu aceiași valoare juridică, câte una pentru fiecare parte.</p>
			<p><b>9.2.</b> Clauzele prezentului Contract pot fi  modificate doar cu acordul ambelor Părți.</p>
			<p><b>9.3.</b> Nici una din Părțile contractante nu este în drept să transmită unei persoane terțe drepturile prevăzute de prezentul Contract fără acordul celelalte Părți.</p>
			<p><b>9.4.</b> Contractul este valabil cu semnarea concomitentă a actului de predare – primire a autovehiculului, Alexa Nr 1 al contractului, aceasta fiind parte integrantă a prezentului contract.</p>
		</div>
		<div class="flx">
			<div id="chap6_txt">
				<div class="gr">
					<h3 class="ttl">10. RECHIZITELE PĂRȚILOR.</h3>
				</div>
			</div>
			<div class="ws"></div>
			<table class="n2">
				<tr><td>INTERMEDIAR</td><td>CLIENT</td></tr>
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
	<div class="pg d2 p1 bg">
		<div class="flx">
			<div class="head">
				<div class="nr">Anexa nr.1 la contractul de intermediere nr. '.$abr.$cont_y.$cont_q.'/'.$cont_n.' din '.$zdate.'</div>
				<div class="ttl">ACT DE PREDARE - PRIMIRE<br/>DIN DATA DE '.$zdate.'</div>
			</div>
			<div class="gr">
				<p>Părțile au încheiat prezentul Act de predare-primire, care confirmă că Clientul a predat, iar intermediarul a primit autovehiculul la parcarea auto situată la adresa: mun. Chișinău, Str. Calea Mosilor 11</p>
				<p>Descrierea autovehiculului conform prezentului Act:</p>
				<ol type="l" start="1">
					<li> Marca și Model: '.(isset($_POST['br']) ? ucwords(strtolower($_POST['br'])) : '').' '.(isset($_POST['mo']) ? ucwords(strtolower($_POST['mo'])) : '').' '.(isset($_POST['yr']) ? $_POST['yr'] : '').'</li>
					<li> Numărul caroseriei: '.$_POST['vin'].'</li>
					<li> Culoare: '.(isset($lng['l']['car']['clr'][ $_POST['clr'] ])?$lng['l']['car']['clr'][ $_POST['clr'] ]:$_POST['clr']).'</li>
					<li> 
						Semne de deteriorare (zgârieturi, îndoituri etc.) 
						<div id="dmg_txt_bx" style="text-decoration:underline;">';
							if (isset($_POST['dmg_txt']) && is_array($_POST['dmg_txt'])) {
								foreach ($_POST['dmg_txt'] as $k => $v){ 
									$rtrn .= ( $v!=''?($k>0?', ':'').'<b>'.($k*1+1).'</b>: '.$v:'' ); 
								}
							}
							$rtrn .= '
						</div>
						<div id="dmg_clk_bx" oncontextmenu="return false;">
							<div class="img"><img class="ghost" src="/media/images/site/blueprint/sdn.jpg" height="100%" />';
								if (isset($_POST['dmg_pos']) && is_array($_POST['dmg_pos'])) {
									foreach ($_POST['dmg_pos'] as $k => $v){
										$v = explode("x", $v);
										$rtrn .= '<div class="el" style="left:calc('.$v[0].'% - 3mm); top:calc('.$v[1].'% - 3mm);">'.($k*1+1).'</div>';
									}
								}
							$rtrn .= '
							</div>
						</div>
						Împreună cu autovehiculul, Clientul transmite Intermediarului cheile și toate documentele necesare: pașaportul tehnic al autovehiculului, precum și alte necesare exploatării acestuia.<br/>';
						if (isset($_POST['extras']) && is_array($_POST['extras'])) {
							foreach ($_POST['extras'] as $k => $v){$rtrn .= ($k>0?', ':'Elemente suplimentare: ').(isset($lng['l']['extras'][$v])?$lng['l']['extras'][$v]:$v);}
						}
					$rtrn .= '
					</li>
				</ol>
				<p>Părțile contractante prin acord comun conf. p. 2.1 din contractul de intermediere, au convenit următorul preț de vânzare al autovehiculului. _______________________________________________________________________________________</p>
				<p>Prezentul Act a fost întocmit în două exemplare originale, câte un exemplar pentru fiecare din Părțile contractante și reprezintă partea integrantă al Contractului de intermediere.</p>
				<p>Proprietarul autovehiculului se obligă să scoată toate anunțurile de pe toate rețelele de socializare, platformele de publicitate – violarea acestei condiții se sancționează cu o amendă de 2000 lei.</p>
				<p>În cazul dacă nu va fi găsit nici un cumpărător, autovehiculul se reîntoarce Clientului în conformitate cu prezentul Act.</p>
				<div class="inf">
					<div class="x n1">
						<p><b><span class="ttl">AM PREDAT</span></b></p>
						<p><span class="txt_cpt">'.strtolower($_POST['u_nm']).'</span> , '.($_POST['u_tp']=='fiz'?'cp':'cf').' <span class="txt_up">'.$_POST['u_cf_idno'].'</span></p>
						<p>_______________'.(isset($_POST['stamp'])&&$_POST['stamp']==1?'<span class="stamp ghost"><span class="signature"></span></span>':'').'</p>
						<p>_______________</p>
						<p>Tel._________________</p>
						<p>Email_________________</p>
					</div>
					<div class="x n2">
						<p><b><span class="ttl">AM PRIMIT</span></b></p>
						<p>Subsemnatul(a) <span class="txt_cpt">'.strtolower($_POST['u_nm']).'</span>, '.($_POST['u_tp']=='fiz'?'cp':'cf').' <span class="txt_up">'.$_POST['u_cf_idno'].'</span> îmi exprim acordul cu privire la prelucrarea datelor mele cu caracter personal de către SAUTO SRL în cadrul prezentului contract, nemijlocit pentru opurtunitățile ce le oferă statutul de « Utilizator Verificat ».
					</div>
				</div>
			</div>
			<p>Sînt informat de către SAUTO SRL – că aceste date vor fi tratate confidențial, în conformitate cu prevederile Directivei CE/95/46 privind protecția persoanelor fizice în ceea ce privește prelucrarea datelor cu caracter personal și libera circulație a acestor date, transpusă prin Legea nr. 133/2011 pentru protecția datelor cu caracter personal cu modificările și completările ulterioare.</p>
			<div class="ws"></div>
			<div class="semn" style="align-self:flex-end;">Semnătura_________________</div>
			<div class="date" style="align-self:flex-end;">'.$zdate.'</div>
		</div>
		<div class="conf">CONFIDENTIAL</div>
	</div>
</div>';

echo $rtrn;
?>