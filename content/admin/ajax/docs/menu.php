<?php defined( '_DOIT' ) or die( 'Restricted access' );

if ( isset($t_mp[5]) || isset($mixall) ){
	if ( !isset($t_mp[5]) ){$t_mp[5]='';}
	
	$it_ar = [];
	$pdo = $db->prepare('SELECT * FROM '.$prefx.'_car_list ORDER BY `br` ASC, `mo` ASC'); $pdo->execute();
	foreach ($pdo as $r){ if ($r['br_nm']!=''){ if ($r['mo']!=''){ $it_ar[ $r['br_nm'] ][] = $r['mo_nm']; } } }
	$br_html = ''; $mo_html = ''; 
	// Get brand mapping for correct values
	$brand_map = [];
	$pdo_brands = $db->prepare('SELECT DISTINCT `br`, `br_nm` FROM '.$prefx.'_car_list WHERE `br_nm` != ""'); 
	$pdo_brands->execute();
	foreach ($pdo_brands as $r_brand) {
		$brand_map[$r_brand['br_nm']] = $r_brand['br'];
	}
	
	foreach($it_ar as $br => $ar){
		$br_value = isset($brand_map[$br]) ? $brand_map[$br] : $br; // Use raw value for backend
		$br_html .= '<option value="'.$br_value.'">'.$br.'</option>';
		foreach($ar as $mo){ $mo_html .= '<option value="'.$mo.'" data-br="'.$br_value.'" class="none">'.$mo.'</option>'; } 
	}
	$clr_html = ''; foreach ($lng['l']['car']['clr'] as $k => $v){ $clr_html .= '<option value="'.$k.'">'.$v.'</option>'; }
	
	//__________________________________________________________________________________________CONT DE PLATA
	if ( $t_mp[5]=='con_plata' || isset($mixall) ){
		$rtrn .= ( isset($mixall)?'<form class="menu_con_plata">':'' ).'
		<div class="ttl">Document</div>
		<label class="lbl"><span class="ttl">Date</span><input class="need dt" type="date" name="date" min="1900-01-01" max="2099-12-31" title="Date" /></label>
		
		<div class="ttl">Auto</div>
		<label class="lbl"><span class="ttl">Brand</span><select name="br" title="Brand">
			<option value="x" class="def" disabled selected>-</option>
			'.$br_html.'
		</select></label>
		<label class="lbl"><span class="ttl">Model</span><select name="mo" title="Model">
			<option value="x" data-br="" class="def" disabled selected>-</option>
			'.$mo_html.'
		</select></label>
		<label class="lbl"><span class="ttl">VIN code</span><input class="need" type="text" name="vin" title="VIN code" /></label>
		<label class="lbl"><span class="ttl">Price</span><input class="need" type="number" name="prc" title="Price" /></label>
		<label class="lbl"><span class="ttl">Currency</span><select name="cur" title="Currency">
			<option value="MDL" selected>MDL</option>
			<option value="EUR">EUR</option>
		</select></label>
		
		<div class="ttl">Cumparator</div>
		<label class="lbl"><span class="ttl">Tip</span><select name="u_tp"><option value="fiz">Fizic</option><option value="jur">Juridic</option></select></label>
		<label class="lbl"><span class="ttl">IDNO</span><input class="need fj" type="text" name="u_cf_idno" title="IDNO" data-fiz="IDNO" data-jur="CF" /></label>
		<label class="lbl"><span class="ttl">Name</span><input class="need fj" type="text" name="u_nm" title="Name" data-fiz="Name" data-jur="SRL" /></label>
		'.( isset($mixall)?'</form>':'' );
		//}
	}
	
	//__________________________________________________________________________________________CONTRACT JURIDICE
	if ( $t_mp[5]=='vinzare_proc' || isset($mixall) ){
		$rtrn .= ( isset($mixall)?'<form class="menu_vinzare_proc">':'' ).'
		<div class="ttl">Document</div>
		<label class="lbl"><span class="ttl">Date</span><input class="need dt" type="date" name="date" min="1900-01-01" max="2099-12-31" title="Date" /></label>
		
		<div class="ttl">Auto</div>
		<label class="lbl"><span class="ttl">Brand</span><select name="br" title="Brand">
			<option value="x" class="def" disabled selected>-</option>
			'.$br_html.'
		</select></label>
		<label class="lbl"><span class="ttl">Model</span><select name="mo" title="Model">
			<option value="x" data-br="" class="def" disabled selected>-</option>
			'.$mo_html.'
		</select></label>
		<label class="lbl"><span class="ttl">Year</span><input class="need" type="text" name="yr" title="Year" /></label>
		<label class="lbl"><span class="ttl">Color</span><select name="clr" title="Color">
			<option value="" selected>-</option>
			'.$clr_html.'
		</select></label>
		<label class="lbl"><span class="ttl">VIN code</span><input class="need" type="text" name="vin" title="VIN code" /></label>';
		//---LOCATION---
		$rtrn .= '<label class="lbl"><span class="ttl">'.$lng['w']['address'].'</span><select class="need" name="loc" title="'.$lng['w']['address'].'" tabindex="12" title="Location">
			<option value="" class="def" disabled selected>-</option>';
			foreach ($lng['t']['x']['address'] as $k => $v){if($k==0){continue;} $rtrn .= '<option value="'.$k.'">'.$v.'</option>';}
		$rtrn .= '</select></label>
		<label class="lbl"><span class="ttl">Price, MDL</span><input class="need" type="number" name="prc" title="Price" /></label>
		<label class="lbl"><span class="ttl">Avans</span><input type="number" name="prc_av" title="Avans" placeholder="0" /></label>
		<label class="lbl"><span class="ttl">Termen de livrare, zile</span><input class="need" type="text" name="term_livr" title="Termen de livrare" min="0" step="1" placeholder="35" /></label>
		<label class="lbl"><span class="ttl">Din ce surse</span>
			<select name="orig">
				<option value="0">-</option>
				<option value="999">999.md</option>
				<option value="ste">De pe sait SAUTO.MD</option>
				<option value="poz">Pozitionare(de pe strada)</option>
				<option value="rec">Prin recomandare (cunostinte)</option>
				<option value="buy">Am procurat în anterior</option>
				<option value="knw">Cunosc de mult compania</option>
				<option value="fb">Facebook</option>
				<option value="ig">Instagram</option>
				<option value="tt">TikTok</option>
			</select>
		</label>
		
		<div class="ttl">Cumparator</div>
		<label class="lbl"><span class="ttl">Tip</span><select name="u_tp"><option value="fiz">Fizic</option><option value="jur">Juridic</option></select></label>
		<label class="lbl"><span class="ttl">IDNO</span><input class="need fj" type="text" name="u_cf_idno" title="IDNO" data-fiz="IDNO" data-jur="CF" /></label>
		<label class="lbl"><span class="ttl">Name</span><input class="need fj" type="text" name="u_nm" title="Name" data-fiz="Name" data-jur="SRL" /></label>
		<label class="lbl"><span class="ttl">Data nasterii</span><input class="need fj dt" type="text" name="u_tva_dt" min="1900-01-01" max="2099-12-31" title="Data nasterii" data-fiz="Data nasterii" data-jur="TVA" /></label> <!--onfocus=\'(this.type="date")\'-->
		<label class="lbl"><span class="ttl">Data elibirat</span><input class="need fj dt" type="text" name="u_iban_dt_tk" min="1900-01-01" max="2099-12-31" title="Data elibirat" data-fiz="Data elibirat" data-jur="IBAN" /></label>
		<label class="lbl"><span class="ttl">Adress</span><input class="need" type="text" name="u_adr" value="Republica Moldova, mun.Chişinau, or.Chisinau, str."  title="Adress" /></label>
		<label class="lbl"><span class="ttl">Phone</span><input type="text" name="u_phn" value="+373" title="Phone" /></label>
		<label class="lbl"><span class="ttl">Email</span><input type="text" name="u_eml" title="Email" /></label>
		'.( isset($mixall)?'</form>':'' );
	}
	
	//__________________________________________________________________________________________CONTRACT JURIDICE
	if ( $t_mp[5]=='vinzare_sauto' || isset($mixall) ){
		$rtrn .= ( isset($mixall)?'<form class="menu_vinzare_sauto">':'' ).'
		<div class="ttl">Document</div>
		<label class="lbl"><span class="ttl">Date</span><input class="need dt" type="date" name="date" min="1900-01-01" max="2099-12-31" title="Date" /></label>
		
		<div class="ttl">Auto</div>
		<label class="lbl"><span class="ttl">Brand</span><select name="br" title="Brand">
			<option value="x" class="def" disabled selected>-</option>
			'.$br_html.'
		</select></label>
		<label class="lbl"><span class="ttl">Model</span><select name="mo" title="Model">
			<option value="x" data-br="" class="def" disabled selected>-</option>
			'.$mo_html.'
		</select></label>
		<label class="lbl"><span class="ttl">Year</span><input class="need" type="text" name="yr" title="Year" /></label>
		<label class="lbl"><span class="ttl">Color</span><select name="clr" title="Color">
			<option value="" selected>-</option>
			'.$clr_html.'
		</select></label>
		<label class="lbl"><span class="ttl">VIN code</span><input class="need" type="text" name="vin" title="VIN code" /></label>';
		//---LOCATION---
		$rtrn .= '<label class="lbl"><span class="ttl">'.$lng['w']['address'].'</span><select class="need" name="loc" title="'.$lng['w']['address'].'" tabindex="12" title="Location">
			<option value="" class="def" disabled selected>-</option>';
			foreach ($lng['t']['x']['address'] as $k => $v){if($k==0){continue;} $rtrn .= '<option value="'.$k.'">'.$v.'</option>';}
		$rtrn .= '</select></label>
		<label class="lbl"><span class="ttl">Price, MDL</span><input class="need" type="number" name="prc" title="Price" /></label>
		<label class="lbl"><span class="ttl">Avans</span><input type="number" name="prc_av" title="Avans" placeholder="0" /></label>
		<label class="lbl"><span class="ttl">Termen de livrare, zile</span><input class="need" type="text" name="term_livr" title="Termen de livrare" min="0" step="1" placeholder="35" /></label>
		
		<div class="ttl">Vinzator</div>
		<label class="lbl"><span class="ttl">Tip</span><select name="u_tp"><option value="fiz">Fizic</option><option value="jur">Juridic</option></select></label>
		<label class="lbl"><span class="ttl">IDNO</span><input class="need fj" type="text" name="u_cf_idno" title="IDNO" data-fiz="IDNO" data-jur="CF" /></label>
		<label class="lbl"><span class="ttl">Name</span><input class="need fj" type="text" name="u_nm" title="Name" data-fiz="Name" data-jur="SRL" /></label>
		<label class="lbl"><span class="ttl">Data nasterii</span><input class="need fj dt" type="text" name="u_tva_dt" min="1900-01-01" max="2099-12-31" title="Data nasterii" data-fiz="Data nasterii" data-jur="TVA" /></label> <!--onfocus=\'(this.type="date")\'-->
		<label class="lbl"><span class="ttl">Data elibirat</span><input class="need fj dt" type="text" name="u_iban_dt_tk" min="1900-01-01" max="2099-12-31" title="Data elibirat" data-fiz="Data elibirat" data-jur="IBAN" /></label>
		<label class="lbl"><span class="ttl">Adress</span><input class="need" type="text" name="u_adr" value="Republica Moldova, mun.Chişinau, or.Chisinau, str."  title="Adress" /></label>
		<label class="lbl"><span class="ttl">Phone</span><input type="text" name="u_phn" value="+373" title="Phone" /></label>
		<label class="lbl"><span class="ttl">Email</span><input type="text" name="u_eml" title="Email" /></label>
		'.( isset($mixall)?'</form>':'' );
	}
	
	//__________________________________________________________________________________________CONTRACT JURIDICE (AVANS)
	if ( $t_mp[5]=='vinzare_avans' || isset($mixall) ){
		$rtrn .= ( isset($mixall)?'<form class="menu_vinzare_avans">':'' ).'
		<div class="ttl">Document</div>
		<label class="lbl"><span class="ttl">Date</span><input class="need dt" type="date" name="date" min="1900-01-01" max="2099-12-31" title="Date" /></label>
		
		<div class="ttl">Auto</div>
		<label class="lbl"><span class="ttl">Brand</span><select name="br" title="Brand">
			<option value="x" class="def" disabled selected>-</option>
			'.$br_html.'
		</select></label>
		<label class="lbl"><span class="ttl">Model</span><select name="mo" title="Model">
			<option value="x" data-br="" class="def" disabled selected>-</option>
			'.$mo_html.'
		</select></label>
		<label class="lbl"><span class="ttl">Year</span><input class="need" type="text" name="yr" title="Year" /></label>
		<label class="lbl"><span class="ttl">Color</span><select name="clr" title="Color">
			<option value="" selected>-</option>
			'.$clr_html.'
		</select></label>
		<label class="lbl"><span class="ttl">VIN code</span><input class="need" type="text" name="vin" title="VIN code" /></label>';
		//---LOCATION---
		$rtrn .= '<label class="lbl"><span class="ttl">'.$lng['w']['address'].'</span><select class="need" name="loc" title="'.$lng['w']['address'].'" tabindex="12" title="Location">
			<option value="" class="def" disabled selected>-</option>';
			foreach ($lng['t']['x']['address'] as $k => $v){if($k==0){continue;} $rtrn .= '<option value="'.$k.'">'.$v.'</option>';}
		$rtrn .= '</select></label>
		<label class="lbl"><span class="ttl">Price, MDL</span><input class="need" type="number" name="prc" title="Price" /></label>
		<label class="lbl"><span class="ttl">Termen de livrare, zile</span><input class="need" type="text" name="term_livr" title="Termen de livrare" min="0" step="1" placeholder="35" /></label>
		<label class="lbl"><span class="ttl">Din ce surse</span>
			<select name="orig">
				<option value="0">-</option>
				<option value="999">999.md</option>
				<option value="ste">De pe sait SAUTO.MD</option>
				<option value="poz">Pozitionare(de pe strada)</option>
				<option value="rec">Prin recomandare (cunostinte)</option>
				<option value="buy">Am procurat în anterior</option>
				<option value="knw">Cunosc de mult compania</option>
				<option value="fb">Facebook</option>
				<option value="ig">Instagram</option>
				<option value="tt">TikTok</option>
			</select>
		</label>
		
		<div class="ttl">Etapele achitarii</div>
		<div id="date_pay_bx" name="pays" data-qu="0"></div>
		<div class="btn" data-fn="add_date_pay">Adăugati</div>
		
		<div class="ttl">Cumparator</div>
		<label class="lbl"><span class="ttl">Tip</span><select name="u_tp"><option value="fiz">Fizic</option><option value="jur">Juridic</option></select></label>
		<label class="lbl"><span class="ttl">IDNO</span><input class="need fj" type="text" name="u_cf_idno" title="IDNO" data-fiz="IDNO" data-jur="CF" /></label>
		<label class="lbl"><span class="ttl">Name</span><input class="need fj" type="text" name="u_nm" title="Name" data-fiz="Name" data-jur="SRL" /></label>
		<label class="lbl"><span class="ttl">Data nasterii</span><input class="need fj dt" type="text" name="u_tva_dt" min="1900-01-01" max="2099-12-31" title="Data nasterii" data-fiz="Data nasterii" data-jur="TVA" /></label> <!--onfocus=\'(this.type="date")\'-->
		<label class="lbl"><span class="ttl">Data elibirat</span><input class="need fj dt" type="text" name="u_iban_dt_tk" min="1900-01-01" max="2099-12-31" title="Data elibirat" data-fiz="Data elibirat" data-jur="IBAN" /></label>
		<label class="lbl"><span class="ttl">Adress</span><input class="need" type="text" name="u_adr" value="Republica Moldova, mun.Chişinau, or.Chisinau, str."  title="Adress" /></label>
		<label class="lbl"><span class="ttl">Phone</span><input type="text" name="u_phn" value="+373" title="Phone" /></label>
		<label class="lbl"><span class="ttl">Email</span><input type="text" name="u_eml" title="Email" /></label>
		
		<div class="ttl">Extra</div>
		<label class="lbl"><span class="ttl">EUR</span><input type="number" name="u_eur" title="EUR" value="0" min="0" step="100" /></label>
		
		<div class="ttl">"Garanție", text suplimentar</div>
		<div id="grnt_fld_bx" name="grnt_txt" data-qu="0"></div>
		<div class="btn" data-fn="add_grnt_fld">Adăugati</div>
		'.( isset($mixall)?'</form>':'' );
	}
	
	//__________________________________________________________________________________________CESIONAR
	if ( $t_mp[5]=='cesionar' || isset($mixall) ){
		$rtrn .= ( isset($mixall)?'<form class="menu_vinzare_avans">':'' ).'
		<div class="ttl">Document</div>
		<label class="lbl"><span class="ttl">Date</span><input class="need dt" type="date" name="date" min="1900-01-01" max="2099-12-31" title="Date" /></label>
		
		<div class="ttl">Auto</div>
		<label class="lbl"><span class="ttl">Brand</span><select name="br" title="Brand">
			<option value="x" class="def" disabled selected>-</option>
			'.$br_html.'
		</select></label>
		<label class="lbl"><span class="ttl">Model</span><select name="mo" title="Model">
			<option value="x" data-br="" class="def" disabled selected>-</option>
			'.$mo_html.'
		</select></label>
		<label class="lbl"><span class="ttl">Year</span><input class="need" type="text" name="yr" title="Year" /></label>
		<label class="lbl"><span class="ttl">Color</span><select name="clr" title="Color">
			<option value="" selected>-</option>
			'.$clr_html.'
		</select></label>
		<label class="lbl"><span class="ttl">VIN code</span><input class="need" type="text" name="vin" title="VIN code" /></label>';
		//---LOCATION---
		$rtrn .= '<label class="lbl"><span class="ttl">'.$lng['w']['address'].'</span><select class="need" name="loc" title="'.$lng['w']['address'].'" tabindex="12" title="Location">
			<option value="" class="def" disabled selected>-</option>';
			foreach ($lng['t']['x']['address'] as $k => $v){if($k==0){continue;} $rtrn .= '<option value="'.$k.'">'.$v.'</option>';}
		$rtrn .= '</select></label>
		<label class="lbl"><span class="ttl">Price, MDL</span><input class="need" type="number" name="prc" title="Price" /></label>
		<label class="lbl"><span class="ttl">Termen de livrare, zile</span><input class="need" type="text" name="term_livr" title="Termen de livrare" min="0" step="1" placeholder="35" /></label>
		<label class="lbl"><span class="ttl">Din ce surse</span>
			<select name="orig">
				<option value="0">-</option>
				<option value="999">999.md</option>
				<option value="ste">De pe sait SAUTO.MD</option>
				<option value="poz">Pozitionare(de pe strada)</option>
				<option value="rec">Prin recomandare (cunostinte)</option>
				<option value="buy">Am procurat în anterior</option>
				<option value="knw">Cunosc de mult compania</option>
				<option value="fb">Facebook</option>
				<option value="ig">Instagram</option>
				<option value="tt">TikTok</option>
			</select>
		</label>
		
		<div class="ttl">Etapele achitarii</div>
		<div id="date_pay_bx" name="pays" data-qu="0"></div>
		<div class="btn" data-fn="add_date_pay">Adăugati</div>
		
		<div class="ttl">Cumparator</div>
		<label class="lbl"><span class="ttl">Tip</span><select name="u_tp"><option value="fiz">Fizic</option><option value="jur">Juridic</option></select></label>
		<label class="lbl"><span class="ttl">IDNO</span><input class="need fj" type="text" name="u_cf_idno" title="IDNO" data-fiz="IDNO" data-jur="CF" /></label>
		<label class="lbl"><span class="ttl">Name</span><input class="need fj" type="text" name="u_nm" title="Name" data-fiz="Name" data-jur="SRL" /></label>
		<label class="lbl"><span class="ttl">Data nasterii</span><input class="need fj dt" type="text" name="u_tva_dt" min="1900-01-01" max="2099-12-31" title="Data nasterii" data-fiz="Data nasterii" data-jur="TVA" /></label> <!--onfocus=\'(this.type="date")\'-->
		<label class="lbl"><span class="ttl">Data elibirat</span><input class="need fj dt" type="text" name="u_iban_dt_tk" min="1900-01-01" max="2099-12-31" title="Data elibirat" data-fiz="Data elibirat" data-jur="IBAN" /></label>
		<label class="lbl"><span class="ttl">Adress</span><input class="need" type="text" name="u_adr" value="Republica Moldova, mun.Chişinau, or.Chisinau, str."  title="Adress" /></label>
		<label class="lbl"><span class="ttl">Phone</span><input type="text" name="u_phn" value="+373" title="Phone" /></label>
		<label class="lbl"><span class="ttl">Email</span><input type="text" name="u_eml" title="Email" /></label>
		
		<div class="ttl">Extra</div>
		<label class="lbl"><span class="ttl">EUR</span><input type="number" name="u_eur" title="EUR" value="0" min="0" step="100" /></label>
		
		<div class="ttl">"Garanție", text suplimentar</div>
		<div id="grnt_fld_bx" name="grnt_txt" data-qu="0"></div>
		<div class="btn" data-fn="add_grnt_fld">Adăugati</div>
		'.( isset($mixall)?'</form>':'' );
	}
	
	//__________________________________________________________________________________________CONTRACT DE ARVUNA
	if ( $t_mp[5]=='con_arvon' || $t_mp[5]=='con_arvon_com' || isset($mixall) ){
		$rtrn .= ( isset($mixall)?'<form class="menu_con_arvon">':'' ).'
		<div class="ttl">Document</div>
		<label class="lbl"><span class="ttl">Date</span><input class="need dt" type="date" name="date" min="1900-01-01" max="2099-12-31" title="Date" /></label>
		
		<div class="ttl">Auto</div>
		<label class="lbl"><span class="ttl">Brand</span><select name="br" title="Brand">
			<option value="x" class="def" disabled selected>-</option>
			'.$br_html.'
		</select></label>
		<label class="lbl"><span class="ttl">Model</span><select name="mo" title="Model">
			<option value="x" data-br="" class="def" disabled selected>-</option>
			'.$mo_html.'
		</select></label>
		<label class="lbl"><span class="ttl">VIN code</span><input class="need" type="text" name="vin" title="VIN code" /></label>
		
		<label class="lbl"><span class="ttl">Price, EUR</span><input class="need" type="number" name="prc_eur" title="Price EUR" /></label>
		<label class="lbl"><span class="ttl">Price, MDL</span><input class="need" type="number" name="prc" title="Price MDL" /></label>
		
		<div class="ttl">Cumparator</div>
		<label class="lbl none"><select name="u_tp"><option value="fiz" selected="selected">Fizic</option><option value="jur">Juridic</option></select></label>
		<label class="lbl"><span class="ttl">IDNO</span><input class="need fj" type="text" name="u_cf_idno" title="IDNO" data-fiz="IDNO" data-jur="CF" /></label>
		<label class="lbl"><span class="ttl">Name</span><input class="need fj" type="text" name="u_nm" title="Name" data-fiz="Name" data-jur="SRL" /></label>
		'.( isset($mixall)?'</form>':'' );
	}
	
	//__________________________________________________________________________________________Comanda pentru transport
	if ( $t_mp[5]=='com_transport' || isset($mixall) ){
		$rtrn .= ( isset($mixall)?'<form class="menu_com_transport">':'' ).'
		<div class="ttl">Document</div>
		<label class="lbl"><span class="ttl">Date</span><input class="need dt" type="date" name="date" min="1900-01-01" max="2099-12-31" title="Date" /></label>
		
		<div class="ttl">Auto</div>
		<div id="its_bx" name="its" data-qu="1">
			<div class="def none">
				<label class="lbl"><span class="ttl">Brand</span><select name="br[]" title="Brand" data-n="x" disabled>
					<option value="x" class="def" disabled selected>-</option>
					'.$br_html.'
				</select></label>
				<label class="lbl"><span class="ttl">Model</span><select name="mo[]" title="Model" data-n="x" disabled>
					<option value="x" data-br="" class="def" disabled selected>-</option>
					'.$mo_html.'
				</select></label>
				<label class="lbl"><span class="ttl">VIN code</span><input class="need" type="text" name="vin[]" title="VIN code" data-n="x" disabled /></label>
			</div>
			
			<label class="lbl"><span class="ttl">Brand</span><select name="br[]" title="Brand" data-n="0">
				<option value="x" class="def" disabled selected>-</option>
				'.$br_html.'
			</select></label>
			<label class="lbl"><span class="ttl">Model</span><select name="mo[]" title="Model" data-n="0">
				<option value="x" data-br="" class="def" disabled selected>-</option>
				'.$mo_html.'
			</select></label>
			<label class="lbl"><span class="ttl">VIN code</span><input class="need" type="text" name="vin[]" title="VIN code" data-n="0" /></label>
			
		</div>
		<div class="btn" data-fn="add_it" data-next="1">Add car</div>
		
		<div class="ttl">Cumparator</div>
		<label class="lbl"><span class="ttl">Tip</span><select name="u_tp"><option value="fiz">Fizic</option><option value="jur" selected>Juridic</option></select></label>
		<label class="lbl"><span class="ttl">CF</span><input class="need fj" type="text" name="u_cf_idno" title="CF" data-fiz="IDNO" data-jur="CF" /></label>
		<label class="lbl"><span class="ttl">SRL</span><input class="need fj" type="text" name="u_nm" title="SRL" data-fiz="Name" data-jur="SRL" /></label>
		<label class="lbl"><span class="ttl">TVA</span><input class="need fj dt" type="text" name="u_tva_dt" min="1900-01-01" max="2099-12-31" title="TVA" data-fiz="Data nasterii" data-jur="TVA" /></label> <!--onfocus=\'(this.type="date")\'-->
		<label class="lbl"><span class="ttl">IBAN</span><input class="need fj dt" type="text" name="u_iban_dt_tk" min="1900-01-01" max="2099-12-31" title="IBAN" data-fiz="Data elibirat" data-jur="IBAN" /></label>
		<label class="lbl"><span class="ttl">Adress</span><input class="need" type="text" name="u_adr" value="Republica Moldova, mun.Chişinau, or.Chisinau, str."  title="Adress" /></label>';
		
		//---LOCATION---
		$rtrn .= '
		<div class="ttl">Info</div>
		<label class="lbl"><span class="ttl">Țară (înc)</span><select class="need" name="cntr_fr" title="Țară (înc)" tabindex="12" title="Location">
			<option value="" class="def" disabled selected>-</option>';
			foreach ($lng['l']['country'] as $k => $v){ $rtrn .= '<option value="'.$k.'">'.$v.'</option>'; }
		$rtrn .= '
		</select></label>
		<label class="lbl"><span class="ttl">Țară (desc)</span><select class="need" name="cntr_to" title="Țară (desc)" tabindex="12" title="Location">
			<option value="" class="def" disabled selected>-</option>';
			foreach ($lng['l']['country'] as $k => $v){ $rtrn .= '<option value="'.$k.'" '.($k=='MD'?'selected':'').'>'.$v.'</option>'; }
		$rtrn .= '
		</select></label>
		<label class="lbl"><span class="ttl">Price</span><input class="need" type="text" name="prc" title="Price" /></label>
		<label class="lbl"><span class="ttl">Adresa descărcării</span><input class="need" type="text" name="adr_to" title="Adresa descărcării" /></label>
		<label class="lbl"><span class="ttl">Termenul de achitare</span><input class="need" type="text" name="t2pay" title="Termenul de achitare" /></label>
		<label class="lbl"><span class="ttl">Nr. Înma. Camion/remorca</span><textarea class="need" name="plate" title="Nr. Înma. Camion/remorca" rows="1"></textarea></label>
		'.( isset($mixall)?'</form>':'' );
	}
	
	//__________________________________________________________________________________________Comanda pentru transport
	if ( $t_mp[5]=='con_intermed' || isset($mixall) ){
		$rtrn .= ( isset($mixall)?'<form class="menu_con_intermed">':'' ).'
		
		<style>
			#dmg_clk_bx {width:100%; height:20vw; background-color:#fff; position:relative; cursor:pointer; border:1px solid #eee; text-align:center;}
			#dmg_clk_bx > .img {height:100%; display:inline-block; position:relative;}
			#dmg_clk_bx > .img > .el {width:6mm; height:6mm; overflow:hidden; border-radius:50%; display:flex; justify-content:center; align-items:center; position:absolute; z-index:2; background-color:#e2001a; color:#fff; opacity:.7; transition:opacity .2s, transform .2s;}
			#dmg_clk_bx > .img > .el.hide {opacity:0; transform:scale(0);}
			#dmg_clk_bx > .img > .el.inp_hov {transform:scale(1.5); opacity:1;}
			#dmg_clk_bx > .help {font-size:.8rem; position:absolute; z-index:3;}
			#dmg_clk_bx > .help > .btn {color:#fff; padding:.5rem; border-radius:1rem; margin:1rem 0 0 1rem; float:left;}
			#dmg_clk_bx > .help > .txt {width:0; height:0; padding:0; line-height:.5rem; background-color:#fffa; border-radius:0 3rem 3rem 0; opacity:0;  transition:opacity 0s, width 0s;}
			#dmg_clk_bx > .help > .txt.act {width:auto; height:auto; padding:3rem 1rem 1rem; opacity:1; transition:opacity .3s, width .3s;}
			#dmg_clk_bx > .help > .txt span {color:var(--clr);}
			
			#dmg_txt_bx {margin-top:1rem; display:flex; flex-flow:row wrap; justify-content:space-around;}
			#dmg_txt_bx > .lbl {width:22%; transition:opacity .2s, transform .2s;}
			
			#dmg_txt_bx > .lbl.hide {opacity:0; transform:scaleX(0);}
		</style>
		<script>
			$(function(){
				$("#dmg_clk_bx > .img").on("click", function(e){
					if (e.target !== this){ return; }
					var bxW = $(this).width(), bxH = $(this).height();
					var ofst = $(this).offset(), relX = e.pageX - ofst.left, relY = e.pageY - ofst.top;
					//var pX = relX / bxW * 100; var pY = relY / bxH * 100;
					var pX = Math.round( (relX / bxW * 100) * 100 ) / 100; var pY = Math.round( (relY / bxH * 100) * 100 ) / 100;
					var qu = $(this).find(".el").length, n = qu+1;
					
					$(this).append("<div class=\"el hide\" data-n=\""+n+"\" style=\"left:calc("+pX+"% - 3mm); top:calc("+pY+"% - 3mm);\"><span class=\"txt\">"+( qu+1 )+"</span><input class=\"pos none\" type=\"text\" name=\"dmg_pos[]\" value=\""+pX+"x"+pY+"\" /></div>"
					).delay(100).queue(function(){ $("#dmg_clk_bx > .img > .el[data-n=\""+n+"\"]").removeClass("hide"); $(this).dequeue(); });
					
					$("#dmg_txt_bx").append(""
						+"<div class=\"lbl hide\" data-n=\""+n+"\">"
							+"<span class=\"ttl\">"+n+"</span>"
							+"<input class=\"txt\" type=\"text\" name=\"dmg_txt[]\" />"
							//+"<input class=\"pos none\" type=\"text\" name=\"dmg_pos["+qu+"]\" value=\""+pX+"x"+pY+"\" />"
						+"</label>"
					).delay(100).queue(function(){ $("#dmg_txt_bx > .lbl[data-n=\""+n+"\"]").removeClass("hide"); $(this).dequeue(); });
					
					$(this).find(".el[data-n=\""+n+"\"]").draggable({
						stop: function(e, ui){
							var bxW = $("#dmg_clk_bx > .img").width(), bxH = $("#dmg_clk_bx > .img").height();
							var ofst = $("#dmg_clk_bx > .img").offset(), relX = e.pageX - ofst.left, relY = e.pageY - ofst.top;
							//var pX = relX / bxW * 100; var pY = relY / bxH * 100;
							var pX = Math.round( (relX / bxW * 100) * 100 ) / 100; var pY = Math.round( (relY / bxH * 100) * 100 ) / 100;
							//$("#dmg_txt_bx > .lbl[data-n=\""+(n)+"\"] > input.pos").val(pX+"x"+pY).attr("value", pX+"x"+pY);
							$(this).css({"left":"calc("+pX+"% - 3mm)", "top":"calc("+pY+"% - 3mm)"}).find("input.pos").val(pX+"x"+pY).attr("value", pX+"x"+pY);
						}
					});
				})
				
				$(document).on("click mousedown", "#dmg_clk_bx > .img > .el", function(e){
					if (e.which == 3 && e.detail === 2){//right button click AND clicked 2 times
						var n = $(this).data("n"), qu = $("#dmg_clk_bx > .img > .el").length;
						$(this).remove();
						$("#dmg_txt_bx > .lbl[data-n=\""+n+"\"]").remove();
						
						for (i=(n+1); i<=qu; i++){
							var el = $("#dmg_clk_bx > .img > .el[data-n=\""+i+"\"]");
							el.data("n", i-1).attr("data-n", i-1);
							el.find(".txt").text(i-1);
							el.find("input.pos").attr("name", "dmg_pos["+(i-2)+"]");
							
							var lbl = $("#dmg_txt_bx > .lbl[data-n=\""+i+"\"]");
							lbl.find(".ttl").text(i-1);
							lbl.find("input.txt").attr("name", "dmg_txt["+(i-2)+"]");
							//lbl.find("input.pos").attr("name", "dmg_pos["+(i-2)+"]");
							lbl.data("n", i-1).attr("data-n", i-1);
						}
					}
				})
				
				$("#dmg_clk_bx > .help > .btn").on("click", function(e){
					if ( $(this).data("stts") == "0" ){
						$(this).data("stts", "1"); $("#dmg_clk_bx > .help > .txt").addClass("act");
					} else {
						$(this).data("stts", "0"); $("#dmg_clk_bx > .help > .txt.act").removeClass("act");
					}
				})
				
				$(document).on({
					mouseenter: function (){
						var n = $(this).closest(".lbl").data("n");
						$("#dmg_clk_bx > .img > .el[data-n=\""+n+"\"]").addClass("inp_hov");
					},
					mouseleave: function (){
						var n = $(this).closest(".lbl").data("n");
						$("#dmg_clk_bx > .img > .el[data-n=\""+n+"\"]").removeClass("inp_hov");
					}
				}, "#dmg_txt_bx > .lbl input");
			})
		</script>
		
		<div class="ttl">Document</div>
		<label class="lbl"><span class="ttl">Date</span><input class="need dt" type="date" name="date" min="1900-01-01" max="2099-12-31" title="Date" /></label>
		
		<div class="ttl">Auto</div>
		<label class="lbl"><span class="ttl">Brand</span><select name="br" title="Brand">
			<option value="x" class="def" disabled selected>-</option>
			'.$br_html.'
		</select></label>
		<label class="lbl"><span class="ttl">Model</span><select name="mo" title="Model">
			<option value="x" data-br="" class="def" disabled selected>-</option>
			'.$mo_html.'
		</select></label>
		<label class="lbl"><span class="ttl">Color</span><select name="clr" title="Color">
			<option value="" selected>-</option>
			'.$clr_html.'
		</select></label>
		<label class="lbl"><span class="ttl">VIN code</span><input class="need" type="text" name="vin" title="VIN code" /></label>
		<label class="lbl"><span class="ttl">Price, EUR</span><input class="need" type="number" name="prc" title="Price" /></label>
		
		<label class="lbl"><span class="ttl">'.$lng['w']['address'].'</span><select class="need" name="loc" title="'.$lng['w']['address'].'" tabindex="12" title="Location">
			<option value="" class="def" disabled selected>-</option>';
			foreach ($lng['t']['x']['address'] as $k => $v){if($k==0){continue;} $rtrn .= '<option value="'.$k.'">'.$v.'</option>';}
		$rtrn .= '</select></label>
		
		<div class="ttl">Cumparator</div>
		<label class="lbl"><span class="ttl">Tip</span><select name="u_tp"><option value="fiz">Fizic</option><option value="jur">Juridic</option></select></label>
		<label class="lbl"><span class="ttl">IDNO</span><input class="need fj" type="text" name="u_cf_idno" title="IDNO" data-fiz="IDNO" data-jur="CF" /></label>
		<label class="lbl"><span class="ttl">Name</span><input class="need fj" type="text" name="u_nm" title="Name" data-fiz="Name" data-jur="SRL" /></label>
		<label class="lbl"><span class="ttl">Data nasterii</span><input class="need fj dt" type="text" name="u_tva_dt" min="1900-01-01" max="2099-12-31" title="Data nasterii" data-fiz="Data nasterii" data-jur="TVA" /></label> <!--onfocus=\'(this.type="date")\'-->
		<label class="lbl"><span class="ttl">Data elibirat</span><input class="need fj dt" type="text" name="u_iban_dt_tk" min="1900-01-01" max="2099-12-31" title="Data elibirat" data-fiz="Data elibirat" data-jur="IBAN" /></label>
		<label class="lbl"><span class="ttl">Adress</span><input class="need" type="text" name="u_adr" value="Republica Moldova, mun.Chişinau, or.Chisinau, str."  title="Adress" /></label>
		<label class="lbl"><span class="ttl">Phone</span><input class="need" type="text" name="u_phn" value="+373" title="Phone" /></label>
		<label class="lbl"><span class="ttl">Email</span><input type="text" name="u_eml" title="Email" /></label>
		
		<div class="ttl">Damage</div>
		<div id="dmg_clk_bx" oncontextmenu="return false;">
			<div class="help">
				<div class="btn" data-stts="0">help</div>
				<div class="txt ghost">
					<p><span>LEFT</span> button <span>click</span> on field <span>>></span> Create element</p>
					<p><span>LEFT</span> button <span>press</span> and <span>HOLD</span> on element <span>>></span> Dragging</p>
					<p><span>RIGHT</span> button <span>double click</span> on element <span>>></span> Delete</p>
				</div>
			</div>
			<div class="img" name="dmg_pos"><img class="ghost" src="/media/images/site/blueprint/sdn.jpg" height="100%" /></div> <!--/media/images/site/v2/suv.svg-->
		</div>
		<div id="dmg_txt_bx" name="dmg_txt"></div>
		
		<style>
			:root {--sz:calc(1vw + 1vh);}
			input [type="checkbox"] {display:none;}
			.addon_bx {display:flex;}
			.addon_bx > .it {width:100px; height:100px; cursor:pointer;}
			.addon_bx > .it > input {display:none;}
			.addon_bx > .it > .img {width:100%; height:calc(100% - 20px); background-color:#ccc; mask:url() no-repeat center / 70%; -webkit-mask:url() no-repeat center / 70%; transition:.2s;}
			.addon_bx > .it > .txt {width:100%; height:20px; text-align:center; text-transform:capitalize; font-size:.8rem;}
			.addon_bx > .it > input:checked ~ .img {background-color:var(--clr);}
		</style>
		
		<div class="ttl">'.$lng['w']['extras'].'</div>
		<div class="addon_bx" name="extras">';
		
		foreach (['fire_ext', 'key', 'matt', 'medkit', 'tools', 'wheel'] as $v){
			$rtrn .= '
			<label class="it">
				<input type="checkbox" name="extras[]" value="'.$v.'" />
				<div class="img" style="mask-image:url(/media/images/site/icon/'.$v.'.svg); -webkit-mask-image:url(/media/images/site/icon/'.$v.'.svg);"></div>
				<div class="txt">'.(isset($lng['l']['extras'][$v])?$lng['l']['extras'][$v]:$v).'</div>
			</label>';
		}
		
		$rtrn .= '
		</div>';
	}
	unset($it_ar, $br_html, $mo_html, $clr_html);
}
?>