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


//__________________________________________________________________________________________INVOICE
if ( $t_mp[5]=='invoice' || isset($mixall) ){
	$rtrn .= ( isset($mixall)?'<form class="menu_invoice">':'' ).'
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
	<label class="lbl"><span class="ttl">VIN code</span><input type="text" name="vin" title="VIN code" value="'.(isset($_POST['vin']) ? htmlspecialchars($_POST['vin']) : '').'" /></label>
	<label class="lbl"><span class="ttl">Price</span><input class="need" type="number" name="prc" title="Price" value="'.(isset($_POST['prc']) ? $_POST['prc'] : '').'" /></label>
	<label class="lbl"><span class="ttl">Currency</span><select name="cur" title="Currency">
		<option value="EUR"'.(isset($_POST['cur']) && $_POST['cur'] == 'EUR' ? ' selected' : (!isset($_POST['cur']) ? ' selected' : '')).'>EUR</option>
		<option value="USD"'.(isset($_POST['cur']) && $_POST['cur'] == 'USD' ? ' selected' : '').'>USD</option>
	</select></label>
	
	<div class="ttl">Descriere & Dealer</div>
	<label class="lbl max"><span class="ttl">Descriere</span><textarea name="description" title="Description" rows="3">'.(isset($_POST['description']) ? htmlspecialchars($_POST['description']) : '').'</textarea></label>
	<label class="lbl"><span class="ttl">Dealer</span><input type="text" name="dealer" title="Dealer" value="'.(isset($_POST['dealer']) ? htmlspecialchars($_POST['dealer']) : 'Sauto').'" /></label>
	
	<div class="ttl">Sauto Role</div>
	<label class="lbl"><span class="ttl">Sauto este</span><select name="sauto_role" title="Sauto Role">
	<option value="buyer"'.(isset($_POST['sauto_role']) && $_POST['sauto_role'] == 'buyer' ? ' selected' : (!isset($_POST['sauto_role']) ? ' selected' : '')).'>Cumpărător</option>
		<option value="seller"'.(isset($_POST['sauto_role']) && $_POST['sauto_role'] == 'seller' ? ' selected' : '').'>Vânzător</option>
	</select></label>
	
	<div class="ttl">Cumpărător</div>
	<label class="lbl"><span class="ttl">Name</span><input class="need" type="text" name="buyer_name" title="Buyer Name" value="'.(isset($_POST['buyer_name']) ? htmlspecialchars($_POST['buyer_name']) : '').'" /></label>
	<label class="lbl"><span class="ttl">VAT/IDNO</span><input type="text" name="buyer_vat" title="Buyer VAT/IDNO" value="'.(isset($_POST['buyer_vat']) ? htmlspecialchars($_POST['buyer_vat']) : '').'" /></label>
	<label class="lbl"><span class="ttl">Cont bancar</span><input type="text" name="buyer_account" title="Buyer Account" value="'.(isset($_POST['buyer_account']) ? htmlspecialchars($_POST['buyer_account']) : '').'" /></label>
	<div style="display: flex; gap: 10px;">
		<label class="lbl" style="flex: 2;"><span class="ttl">Legal Address</span><textarea name="buyer_address" title="Buyer Address" rows="2">'.(isset($_POST['buyer_address']) ? htmlspecialchars($_POST['buyer_address']) : '').'</textarea></label>
		<label class="lbl" style="flex: 1;"><span class="ttl">Țara</span><input type="text" name="buyer_country" title="Buyer Country" value="'.(isset($_POST['buyer_country']) ? htmlspecialchars($_POST['buyer_country']) : '').'" /></label>
		<label class="lbl" style="flex: 1;"><span class="ttl">SWIFT/BIC</span><input type="text" name="buyer_swift" title="Buyer SWIFT/BIC" value="'.(isset($_POST['buyer_swift']) ? htmlspecialchars($_POST['buyer_swift']) : '').'" /></label>
	</div>
	
	<div class="ttl">Vânzător</div>
	<label class="lbl"><span class="ttl">Name</span><input class="need" type="text" name="seller_name" title="Seller Name" value="'.(isset($_POST['seller_name']) ? htmlspecialchars($_POST['seller_name']) : '').'" /></label>
	<label class="lbl"><span class="ttl">VAT/IDNO</span><input type="text" name="seller_vat" title="Seller VAT/IDNO" value="'.(isset($_POST['seller_vat']) ? htmlspecialchars($_POST['seller_vat']) : '').'" /></label>
	<label class="lbl"><span class="ttl">Cont bancar</span><input type="text" name="seller_account" title="Seller Account" value="'.(isset($_POST['seller_account']) ? htmlspecialchars($_POST['seller_account']) : '').'" /></label>
	<div style="display: flex; gap: 10px;">
		<label class="lbl" style="flex: 2;"><span class="ttl">Legal Address</span><textarea name="seller_address" title="Seller Address" rows="2">'.(isset($_POST['seller_address']) ? htmlspecialchars($_POST['seller_address']) : '').'</textarea></label>
		<label class="lbl" style="flex: 1;"><span class="ttl">Țara</span><input type="text" name="seller_country" title="Seller Country" value="'.(isset($_POST['seller_country']) ? htmlspecialchars($_POST['seller_country']) : '').'" /></label>
		<label class="lbl" style="flex: 1;"><span class="ttl">SWIFT/BIC</span><input type="text" name="seller_swift" title="Seller SWIFT/BIC" value="'.(isset($_POST['seller_swift']) ? htmlspecialchars($_POST['seller_swift']) : '').'" /></label>
	</div>
	
	'.( isset($mixall)?'</form>':'' ).'
	
	<script>
	// S-Auto company data
	var sAutoData = {
		name: "Sauto SRL",
		vat: "1017600006845",
		account_eur: "MD51VI022512000000094EUR",
		account_usd: "MD51VI022512000000094USD", 
		address: "Republica Moldova, MD-2084, mun.Chișinău, or.Cricova, str.Chisinaului 84, of. 39"
	};
	
	// Function to show/hide country and swift fields based on SAUTO role
	function toggleCountrySwiftFields() {
		var sautoRole = document.querySelector(\'select[name="sauto_role"]\').value;
		
		// Get all country and swift field containers (now in flex divs)
		var sellerCountryField = document.querySelector(\'input[name="seller_country"]\').closest(\'label\');
		var sellerSwiftField = document.querySelector(\'input[name="seller_swift"]\').closest(\'label\');
		var buyerCountryField = document.querySelector(\'input[name="buyer_country"]\').closest(\'label\');
		var buyerSwiftField = document.querySelector(\'input[name="buyer_swift"]\').closest(\'label\');
		
		if (sautoRole === \'seller\') {
			// Hide seller fields (SAUTO), show buyer fields (client)
			sellerCountryField.style.display = \'none\';
			sellerSwiftField.style.display = \'none\';
			buyerCountryField.style.display = \'block\';
			buyerSwiftField.style.display = \'block\';
		} else if (sautoRole === \'buyer\') {
			// Hide buyer fields (SAUTO), show seller fields (client)
			buyerCountryField.style.display = \'none\';
			buyerSwiftField.style.display = \'none\';
			sellerCountryField.style.display = \'block\';
			sellerSwiftField.style.display = \'block\';
		}
	}

	// Function to auto-fill S-Auto data based on role
	function fillSAutoData() {
		var sautoRole = document.querySelector(\'select[name="sauto_role"]\').value;
		var currency = document.querySelector(\'select[name="cur"]\').value;
		var account = currency === \'EUR\' ? sAutoData.account_eur : sAutoData.account_usd;
		
		// Clear all fields first
		document.querySelector(\'input[name="seller_name"]\').value = "";
		document.querySelector(\'input[name="seller_vat"]\').value = "";
		document.querySelector(\'input[name="seller_account"]\').value = "";
		document.querySelector(\'input[name="seller_country"]\').value = "";
		document.querySelector(\'input[name="seller_swift"]\').value = "";
		document.querySelector(\'textarea[name="seller_address"]\').value = "";
		document.querySelector(\'input[name="buyer_name"]\').value = "";
		document.querySelector(\'input[name="buyer_vat"]\').value = "";
		document.querySelector(\'input[name="buyer_account"]\').value = "";
		document.querySelector(\'input[name="buyer_country"]\').value = "";
		document.querySelector(\'input[name="buyer_swift"]\').value = "";
		document.querySelector(\'textarea[name="buyer_address"]\').value = "";
		
		if (sautoRole === \'seller\') {
			// Sauto as seller - fill seller fields (keep as is - address includes country)
			document.querySelector(\'input[name="seller_name"]\').value = sAutoData.name;
			document.querySelector(\'input[name="seller_vat"]\').value = sAutoData.vat;
			document.querySelector(\'input[name="seller_account"]\').value = account;
			document.querySelector(\'textarea[name="seller_address"]\').value = sAutoData.address;
			// Don\'t fill country and swift - they stay empty for SAUTO (data is in address)
		} else if (sautoRole === \'buyer\') {
			// Sauto as buyer - fill buyer fields (keep as is - address includes country)
			document.querySelector(\'input[name="buyer_name"]\').value = sAutoData.name;
			document.querySelector(\'input[name="buyer_vat"]\').value = sAutoData.vat;
			document.querySelector(\'input[name="buyer_account"]\').value = account;
			document.querySelector(\'textarea[name="buyer_address"]\').value = sAutoData.address;
			// Don\'t fill country and swift - they stay empty for SAUTO (data is in address)
		}
		
		// Toggle visibility of country/swift fields
		toggleCountrySwiftFields();
	}
	
	// Update account when currency changes
	function updateSAutoAccount() {
		var sautoRole = document.querySelector(\'select[name="sauto_role"]\').value;
		var currency = document.querySelector(\'select[name="cur"]\').value;
		var account = currency === \'EUR\' ? sAutoData.account_eur : sAutoData.account_usd;
		
		if (sautoRole === \'seller\') {
			document.querySelector(\'input[name="seller_account"]\').value = account;
		} else if (sautoRole === \'buyer\') {
			document.querySelector(\'input[name="buyer_account"]\').value = account;
		}
	}
	
	// Event listeners
	document.addEventListener(\'DOMContentLoaded\', function() {
		// Auto-fill on page load
		fillSAutoData();
		
		// Auto-fill when S-Auto role changes
		document.querySelector(\'select[name="sauto_role"]\').addEventListener(\'change\', fillSAutoData);
		
		// Update account when currency changes
		document.querySelector(\'select[name="cur"]\').addEventListener(\'change\', updateSAutoAccount);
		
		// Initial toggle of fields
		toggleCountrySwiftFields();
	});
	</script>';
}







//__________________________________________________________________________________________ANNEXA (CESIUNE DREPT DE PLATĂ)

if ( $t_mp[5]=='cesionar' || isset($mixall) ){
	// Auto-generate Annexa number AN-YYYY-XXX with database counter
	$annexa_year = date('Y');
	$annexa_seq = 1;
	
	// Get next sequence number from database
	try {
		$pdo_seq = $db->prepare('SELECT COUNT(*) + 1 as next_seq FROM '.$prefx.'_docs_ctlg WHERE f = "cesionar" AND YEAR(date) = ?');
		$pdo_seq->execute([$annexa_year]);
		$seq_result = $pdo_seq->fetch(PDO::FETCH_ASSOC);
		if ($seq_result) {
			$annexa_seq = $seq_result['next_seq'];
		}
	} catch (Exception $e) {
		// Fallback to 1 if database error
		$annexa_seq = 1;
	}
	
	$annexa_number = 'AN-'.$annexa_year.'-'.str_pad($annexa_seq, 3, '0', STR_PAD_LEFT);
	
	// Get existing contracts for dropdown
	$contracts_html = '<option value="" class="def" disabled selected>Selectează contract...</option>';
	try {
		// Query with JOIN to get buyer data from docs_u table
		$pdo_contracts = $db->prepare('
			SELECT c.id, c.inf, c.date, c.n as cont_nr, c.f as contract_type,
			       u.nm as u_nm, u.cf_idno as u_cf_idno, u.adr as u_adr, 
			       u.phn as u_phn, u.eml as u_eml, u.tp as u_tp
			FROM '.$prefx.'_docs_ctlg c 
			LEFT JOIN '.$prefx.'_docs_u u ON c.u = u.id 
			WHERE c.f IN (?, ?) 
			ORDER BY c.date DESC 
			LIMIT 100
		');
		$pdo_contracts->execute(['vinzare_proc', 'vinzare_avans']);
		
		$contract_count = 0;
		
		foreach ($pdo_contracts as $contract) {
			// Data comes directly from JOIN - no need to parse inf
			$cont_nr = !empty($contract['cont_nr']) ? $contract['cont_nr'] : 'Contract #' . $contract['id'];
			$u_nm = !empty($contract['u_nm']) ? $contract['u_nm'] : 'Client ' . $contract['id'];
			$u_cf_idno = $contract['u_cf_idno'] ?: '';
			$u_adr = $contract['u_adr'] ?: '';
			$u_phn = $contract['u_phn'] ?: '';
			$u_eml = $contract['u_eml'] ?: '';

			// Determine contract type display name
$contract_type_name = '';
if ($contract['contract_type'] == 'vinzare_proc') {
    $contract_type_name = 'Vânzare-cumpărare';
} elseif ($contract['contract_type'] == 'vinzare_avans') {
    $contract_type_name = 'Avans';
}
			
			// Parse inf for currency if available
			$cur = 'MDL'; // default
			if (!empty($contract['inf'])) {
				$pairs = explode('&&', $contract['inf']);
				foreach ($pairs as $pair) {
					if (strpos($pair, 'cur==') === 0) {
						$cur = substr($pair, 5);
						break;
					}
				}
			}
			
			
			$contracts_html .= '<option value="'.$contract['id'].'" data-cont-nr="'.htmlspecialchars($cont_nr).'" data-u-nm="'.htmlspecialchars($u_nm).'" data-u-cf-idno="'.htmlspecialchars($u_cf_idno).'" data-u-adr="'.htmlspecialchars($u_adr).'" data-u-phn="'.htmlspecialchars($u_phn).'" data-u-eml="'.htmlspecialchars($u_eml).'" data-cur="'.htmlspecialchars($cur).'">ID:'.$contract['id'].' - '.htmlspecialchars($u_nm).' - '.htmlspecialchars($contract_type_name).'</option>';
			$contract_count++;
		}
		
		
		// If no contracts found, add informative message
		if ($contract_count === 0) {
			$contracts_html .= '<option value="" disabled>Nu există contracte disponibile</option>';
		}
		
	} catch (Exception $e) {
		// More detailed error handling
		$contracts_html .= '<option value="" disabled>Eroare la încărcarea contractelor: ' . htmlspecialchars($e->getMessage()) . '</option>';
	}
	
	$rtrn .= ( isset($mixall)?'<form class="menu_cesionar">':'' ).'
	<div class="ttl">Bazele datelor</div>
	<label class="lbl"><span class="ttl">Data</span><input class="need dt" type="date" name="date" min="1900-01-01" max="2099-12-31" title="Data" value="'.(isset($_POST['date']) ? $_POST['date'] : date('Y-m-d')).'" /></label>
	<label class="lbl"><span class="ttl">Numărul Anexa</span><input class="need" type="text" name="annexa_nr" title="Numărul Anexa" value="'.(isset($_POST['annexa_nr']) ? htmlspecialchars($_POST['annexa_nr']) : $annexa_number).'" /></label>
	
	<div class="ttl">Selectare Contract</div>
	<label class="lbl"><span class="ttl">Contract de referință *</span><select class="need" name="base_contract_id" id="base_contract_id" title="Contract de referință" onchange="loadContractData()">
		'.$contracts_html.'
	</select></label>
	<label class="lbl"><span class="ttl">Număr contract</span><input class="need" type="text" name="cont_nr" title="Număr contract" value="'.(isset($_POST['cont_nr']) ? htmlspecialchars($_POST['cont_nr']) : '').'" readonly /></label>
	
	<div class="ttl">Cumpărător (din contract original)</div>
	<label class="lbl"><span class="ttl">Nume cumpărător</span><input class="need" type="text" name="u_nm" title="Nume cumpărător" value="'.(isset($_POST['u_nm']) ? htmlspecialchars($_POST['u_nm']) : '').'" /></label>
	<label class="lbl"><span class="ttl">IDNO/CF cumpărător</span><input class="need" type="text" name="u_cf_idno" title="IDNO/CF cumpărător" value="'.(isset($_POST['u_cf_idno']) ? htmlspecialchars($_POST['u_cf_idno']) : '').'" /></label>
	
	<div class="ttl">Cesionar (terța parte care primește dreptul de plată)</div>
	<input type="hidden" name="add_cesionar" value="1" />
	
		<div id="cesionar_section">
		<label class="lbl"><span class="ttl">Nume cesionar</span><input class="cesionar-field" type="text" name="cesionar_nm" title="Nume cesionar" value="'.(isset($_POST['cesionar_nm']) ? htmlspecialchars($_POST['cesionar_nm']) : '').'" /></label>
		<label class="lbl"><span class="ttl">IDNO/CF cesionar</span><input class="cesionar-field" type="text" name="cesionar_cf_idno" title="IDNO/CF cesionar" value="'.(isset($_POST['cesionar_cf_idno']) ? htmlspecialchars($_POST['cesionar_cf_idno']) : '').'" /></label>
		<label class="lbl"><span class="ttl">Cont bancar cesionar</span><input class="cesionar-field" type="text" name="cesionar_account" title="Cont bancar cesionar" value="'.(isset($_POST['cesionar_account']) ? htmlspecialchars($_POST['cesionar_account']) : '').'" /></label>
		<label class="lbl"><span class="ttl">Sumă cesiune * (în valuta contractului)</span><input class="need cesionar-field" type="number" name="cesionar_suma" title="Sumă cesiune" value="'.(isset($_POST['cesionar_suma']) ? $_POST['cesionar_suma'] : '').'" step="0.01" min="0.01" /></label>
	</div>
	<div class="ttl">Text suplimentar (opțional)</div>
	<div id="grnt_fld_bx" name="grnt_txt" data-qu="0"></div>
	<div class="btn" data-fn="add_grnt_fld">Adăugați</div>
	';
	
	// JavaScript section using HEREDOC to avoid quote conflicts
	$rtrn .= <<<'JAVASCRIPT'
	<script>
	function loadContractData() {
		var select = document.getElementById("base_contract_id");
		var selectedOption = select.options[select.selectedIndex];
		
		if (selectedOption.value) {
			// Load contract data from selected option data attributes
			document.querySelector('input[name="cont_nr"]').value = selectedOption.getAttribute('data-cont-nr') || '';
			document.querySelector('input[name="u_nm"]').value = selectedOption.getAttribute('data-u-nm') || '';
			document.querySelector('input[name="u_cf_idno"]').value = selectedOption.getAttribute('data-u-cf-idno') || '';
			
			// Store currency for cesionar amount
			var currency = selectedOption.getAttribute('data-cur') || 'MDL';
			var cesionarSumaInput = document.querySelector('input[name="cesionar_suma"]');
			if (cesionarSumaInput) {
				cesionarSumaInput.setAttribute('data-currency', currency);
				
				// Update cesionar amount label with currency
				var cesionarSumaLabel = cesionarSumaInput.closest('label').querySelector('.ttl');
				if (cesionarSumaLabel) {
					cesionarSumaLabel.textContent = 'Sumă cesiune * (în ' + currency + ')';
				}
			}
		} else {
			// Clear fields if no contract selected
			document.querySelector('input[name="cont_nr"]').value = '';
			document.querySelector('input[name="u_nm"]').value = '';
			document.querySelector('input[name="u_cf_idno"]').value = '';
		}
	}
	
	function toggleCesionarSection() {
		// Cesionar section is always visible now
		var section = document.getElementById("cesionar_section");
		var cesionarFields = document.querySelectorAll(".cesionar-field");
		
		section.style.display = "block";
		// Make cesionar fields required since section is always visible
		cesionarFields.forEach(function(field) {
			if (field.name === "cesionar_nm" || field.name === "cesionar_cf_idno" || field.name === "cesionar_suma") {
				field.classList.add("need");
			}
		});
	}
	
	// Initialize on page load
	document.addEventListener("DOMContentLoaded", function() {
		toggleCesionarSection();

		
		// Check if we're in edit mode and populate cesionar fields from data attributes
		if (typeof $ !== 'undefined' && $("#overlay").length > 0) {
			setTimeout(function() {
				var vals = $(".docs .list .bx.edited .values");
				if (vals.length === 0) {
					vals = $(".docs .list .bx input[name='btns_act']:checked").closest('.bx').find('.values');
				}
				
				if (vals.length > 0) {
					// Cesionar section is always visible and active
					document.getElementById('cesionar_section').style.display = 'block';
					
					// Make cesionar fields required
					var cesionarFields = document.querySelectorAll('.cesionar-field');
					cesionarFields.forEach(function(field) {
						if (field.name === 'cesionar_nm' || field.name === 'cesionar_cf_idno' || field.name === 'cesionar_suma') {
							field.classList.add('need');
						}
					});
					
					// Handle base_contract_id
					var baseContractId = vals.data('base_contract_id');
					if (baseContractId) {
						var contractSelect = document.getElementById('base_contract_id');
						if (contractSelect) {
							contractSelect.value = baseContractId;
							loadContractData();
						}
					}
					
					var grntTxt = vals.data('grnt_txt');
					if (grntTxt) {
						var grntTexts = grntTxt.toString().split('||');
						var grntContainer = document.getElementById('grnt_fld_bx');
						
						if (grntContainer) {
							grntContainer.innerHTML = '';
							grntContainer.setAttribute('data-qu', grntTexts.length);
							
							for (var i = 0; i < grntTexts.length; i++) {
								if (grntTexts[i].trim() !== '') {
									var fieldHtml = '<label class="lbl max"><span class="ttl">6.' + (i + 1) + '.</span><textarea class="need" name="grnt_txt[]" title="Group 6 text" rows="1">' + grntTexts[i] + '</textarea></label>';
									grntContainer.innerHTML += fieldHtml;
								}
							}
						}
					}
				}
			}, 100);
		}

	});
	</script>
JAVASCRIPT;
	
	$rtrn .= ( isset($mixall)?'</form>':'' );
}
	
	
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

		<div class="kyc-questionnaire" style="display: block;">
			
		<div class="ttl">Date Chestionar</div>
			<div style="margin: 10px 0; padding: 10px; border: 1px solid #ddd; border-radius: 5px;">
				<div style="font-weight: bold; margin-bottom: 10px;">Actul de identitate:</div>
				<label style="display: inline-block; margin-right: 15px;"><input type="checkbox" name="kyc_doc_buletin" value="1"'.(isset($_POST['kyc_doc_buletin']) && $_POST['kyc_doc_buletin'] == '1' ? ' checked' : (!isset($_POST['kyc_doc_buletin']) ? ' checked' : '')).'> Buletin de identitate</label>
				<label style="display: inline-block; margin-right: 15px;"><input type="checkbox" name="kyc_doc_permis" value="1"'.(isset($_POST['kyc_doc_permis']) && $_POST['kyc_doc_permis'] == '1' ? ' checked' : '').'> Permis de ședere</label>
				<label style="display: inline-block; margin-right: 15px;"><input type="checkbox" name="kyc_doc_pasaport" value="1"'.(isset($_POST['kyc_doc_pasaport']) && $_POST['kyc_doc_pasaport'] == '1' ? ' checked' : '').'> Pașaport</label>
			</div>
		
			<div style="margin: 10px 0; padding: 10px; border: 1px solid #ddd; border-radius: 5px;">
			<div style="font-weight: bold; margin-bottom: 10px;">Ocupația:</div>
			<label style="display: inline-block; margin-right: 15px; width: 120px;"><input type="checkbox" name="kyc_occupation_angajat" value="1"'.(isset($_POST['kyc_occupation_angajat']) && $_POST['kyc_occupation_angajat'] == '1' ? ' checked' : (!isset($_POST['kyc_occupation_angajat']) ? ' checked' : '')).'> Angajat*</label>
			<label style="display: inline-block; margin-right: 15px; width: 120px;"><input type="checkbox" name="kyc_occupation_student" value="1"'.(isset($_POST['kyc_occupation_student']) && $_POST['kyc_occupation_student'] == '1' ? ' checked' : '').'> Student*</label>
			<label style="display: inline-block; margin-right: 15px; width: 120px;"><input type="checkbox" name="kyc_occupation_antreprenor" value="1"'.(isset($_POST['kyc_occupation_antreprenor']) && $_POST['kyc_occupation_antreprenor'] == '1' ? ' checked' : '').'> Antreprenor*</label>
			<label style="display: inline-block; margin-right: 15px; width: 120px;"><input type="checkbox" name="kyc_occupation_somer" value="1"'.(isset($_POST['kyc_occupation_somer']) && $_POST['kyc_occupation_somer'] == '1' ? ' checked' : '').'> Șomer</label>
			<label style="display: inline-block; margin-right: 15px; width: 120px;"><input type="checkbox" name="kyc_occupation_pensionar" value="1"'.(isset($_POST['kyc_occupation_pensionar']) && $_POST['kyc_occupation_pensionar'] == '1' ? ' checked' : '').'> Pensionar</label>
		</div>
		
		<div style="margin: 10px 0; padding: 10px; border: 1px solid #ddd; border-radius: 5px;">
			<div style="font-weight: bold; margin-bottom: 10px;">Persoană expusă politic (Funcția publică deținută):</div>
			<label style="display: block; margin-bottom: 5px;"><input type="checkbox" name="kyc_no_public_function" value="1"'.(isset($_POST['kyc_no_public_function']) && $_POST['kyc_no_public_function'] == '1' ? ' checked' : (!isset($_POST['kyc_no_public_function']) ? ' checked' : '')).'> Nu dețin funcție publică</label>
			<label style="display: block; margin-bottom: 5px;"><input type="checkbox" name="kyc_public_function_deputat" value="1"'.(isset($_POST['kyc_public_function_deputat']) && $_POST['kyc_public_function_deputat'] == '1' ? ' checked' : '').'> Deputat al Parlamentului RM</label>
			<label style="display: block; margin-bottom: 5px;"><input type="checkbox" name="kyc_public_function_judecator" value="1"'.(isset($_POST['kyc_public_function_judecator']) && $_POST['kyc_public_function_judecator'] == '1' ? ' checked' : '').'> Judecător</label>
			<label style="display: block; margin-bottom: 5px;"><input type="checkbox" name="kyc_public_function_guvern" value="1"'.(isset($_POST['kyc_public_function_guvern']) && $_POST['kyc_public_function_guvern'] == '1' ? ' checked' : '').'> Membru al Guvernului RM</label>
			<label style="display: block; margin-bottom: 5px;"><input type="checkbox" name="kyc_public_function_primar" value="1"'.(isset($_POST['kyc_public_function_primar']) && $_POST['kyc_public_function_primar'] == '1' ? ' checked' : '').'> Primar</label>
			<label style="display: block; margin-bottom: 5px;"><input type="checkbox" name="kyc_public_function_partid" value="1"'.(isset($_POST['kyc_public_function_partid']) && $_POST['kyc_public_function_partid'] == '1' ? ' checked' : '').'> Membru al organelor de conducere ale partidelor politice</label>
			<label style="display: block; margin-bottom: 5px;"><input type="checkbox" name="kyc_public_function_consilier" value="1"'.(isset($_POST['kyc_public_function_consilier']) && $_POST['kyc_public_function_consilier'] == '1' ? ' checked' : '').'> Consilier al autorităților publice locale</label>
		</div>
		
		<div style="margin: 10px 0; padding: 10px; border: 1px solid #ddd; border-radius: 5px;">
			<div style="font-weight: bold; margin-bottom: 10px;">Scopul și natura tranzacțiilor:</div>
			<label style="display: block; margin-bottom: 5px;"><input type="checkbox" name="kyc_transaction_personal" value="1"'.(isset($_POST['kyc_transaction_personal']) && $_POST['kyc_transaction_personal'] == '1' ? ' checked' : (!isset($_POST['kyc_transaction_personal']) ? ' checked' : '')).'> Achiziționarea unui automobil pentru uz personal</label>
			<label style="display: block; margin-bottom: 5px;"><input type="checkbox" name="kyc_transaction_family" value="1"'.(isset($_POST['kyc_transaction_family']) && $_POST['kyc_transaction_family'] == '1' ? ' checked' : '').'> Achiziționarea unui automobil pentru uzul familiei / rudelor</label>
			<label style="display: block; margin-bottom: 5px;"><input type="checkbox" name="kyc_transaction_company" value="1"'.(isset($_POST['kyc_transaction_company']) && $_POST['kyc_transaction_company'] == '1' ? ' checked' : '').'> Achiziționarea unui automobil pentru companie / activitate economică</label>
			<label style="display: block; margin-bottom: 5px;"><input type="checkbox" name="kyc_transaction_resale" value="1"'.(isset($_POST['kyc_transaction_resale']) && $_POST['kyc_transaction_resale'] == '1' ? ' checked' : '').'> Achiziționarea unui automobil în scop de revânzare</label>
			<label style="display: block; margin-bottom: 5px;"><input type="checkbox" name="kyc_transaction_commercial" value="1"'.(isset($_POST['kyc_transaction_commercial']) && $_POST['kyc_transaction_commercial'] == '1' ? ' checked' : '').'> Achiziționarea unui automobil pentru prestarea de servicii comerciale (ex. taxi, livrări)</label>
			<label style="display: block; margin-bottom: 5px;"><input type="checkbox" name="kyc_transaction_transfer" value="1"'.(isset($_POST['kyc_transaction_transfer']) && $_POST['kyc_transaction_transfer'] == '1' ? ' checked' : '').'> Transfer de proprietate între rude (moștenire, donație etc.)</label>
		</div>

        <div style="margin: 10px 0; padding: 10px; border: 1px solid #ddd; border-radius: 5px;">
	<div style="font-weight: bold; margin-bottom: 10px;">Sursa mijloacelor bănești:</div>
	<label style="display: block; margin-bottom: 5px;"><input type="checkbox" name="kyc_funds_salary" value="1"'.(isset($_POST['kyc_funds_salary']) && $_POST['kyc_funds_salary'] == '1' ? ' checked' : (!isset($_POST['kyc_funds_salary']) ? ' checked' : '')).'> Salariu</label>
	<label style="display: block; margin-bottom: 5px;"><input type="checkbox" name="kyc_funds_dividends" value="1"'.(isset($_POST['kyc_funds_dividends']) && $_POST['kyc_funds_dividends'] == '1' ? ' checked' : '').'> Dividende</label>
	<label style="display: block; margin-bottom: 5px;"><input type="checkbox" name="kyc_funds_loan" value="1"'.(isset($_POST['kyc_funds_loan']) && $_POST['kyc_funds_loan'] == '1' ? ' checked' : '').'> Împrumut</label>
	<label style="display: block; margin-bottom: 5px;"><input type="checkbox" name="kyc_funds_business" value="1"'.(isset($_POST['kyc_funds_business']) && $_POST['kyc_funds_business'] == '1' ? ' checked' : '').'> Venit din activitatea de Antreprenor</label>
	<label style="display: block; margin-bottom: 5px;"><input type="checkbox" name="kyc_funds_inheritance" value="1"'.(isset($_POST['kyc_funds_inheritance']) && $_POST['kyc_funds_inheritance'] == '1' ? ' checked' : '').'> Moștenire</label>
	<label style="display: block; margin-bottom: 5px;"><input type="checkbox" name="kyc_funds_donations" value="1"'.(isset($_POST['kyc_funds_donations']) && $_POST['kyc_funds_donations'] == '1' ? ' checked' : '').'> Donații</label>
       </div>

		</div>

		<script>
		// KYC form visibility control based on price
		function toggleKycFormConPlata() {
			var priceInput = document.querySelector(\'form.menu_con_plata input[name="prc"]\');
			var kycSection = document.querySelector(\'form.menu_con_plata .kyc-questionnaire\');
			
			if (priceInput && kycSection) {
				var currency = document.querySelector(\'form.menu_con_plata select[name="cur"]\');
				
				function checkKycThreshold() {
					var price = parseFloat(priceInput.value.replace(/[^0-9.]/g, \'\')) || 0;
					var cur = currency ? currency.value : \'MDL\';
					var showKyc = (price === 0) || (cur === \'EUR\' ? price >= 10000 : price >= 200000);
					kycSection.style.display = showKyc ? \'block\' : \'none\';
				}
				
				priceInput.addEventListener(\'input\', checkKycThreshold);
				
				if (currency) {
					currency.addEventListener(\'change\', checkKycThreshold);
				}
				
				// Trigger initial check
				checkKycThreshold();
			}
		}

		// Initialize when DOM is ready
		if (document.readyState === \'loading\') {
			document.addEventListener(\'DOMContentLoaded\', toggleKycFormConPlata);
		} else {
			toggleKycFormConPlata();
		}
		</script>

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
		<label class="lbl"><span class="ttl">Price, MDL</span><input class="need" type="text" name="prc" title="Price" /></label>
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
		
		<div class="kyc-questionnaire" style="display: block;">
			
		<div class="ttl">Date Chestionar</div>
			<div style="margin: 10px 0; padding: 10px; border: 1px solid #ddd; border-radius: 5px;">
				<div style="font-weight: bold; margin-bottom: 10px;">Actul de identitate:</div>
				<label style="display: inline-block; margin-right: 15px;"><input type="checkbox" name="kyc_doc_buletin" value="1"'.(isset($_POST['kyc_doc_buletin']) && $_POST['kyc_doc_buletin'] == '1' ? ' checked' : (!isset($_POST['kyc_doc_buletin']) ? ' checked' : '')).'> Buletin de identitate</label>
				<label style="display: inline-block; margin-right: 15px;"><input type="checkbox" name="kyc_doc_permis" value="1"'.(isset($_POST['kyc_doc_permis']) && $_POST['kyc_doc_permis'] == '1' ? ' checked' : '').'> Permis de ședere</label>
				<label style="display: inline-block; margin-right: 15px;"><input type="checkbox" name="kyc_doc_pasaport" value="1"'.(isset($_POST['kyc_doc_pasaport']) && $_POST['kyc_doc_pasaport'] == '1' ? ' checked' : '').'> Pașaport</label>
			</div>
		
			<div style="margin: 10px 0; padding: 10px; border: 1px solid #ddd; border-radius: 5px;">
			<div style="font-weight: bold; margin-bottom: 10px;">Ocupația:</div>
			<label style="display: inline-block; margin-right: 15px; width: 120px;"><input type="checkbox" name="kyc_occupation_angajat" value="1"'.(isset($_POST['kyc_occupation_angajat']) && $_POST['kyc_occupation_angajat'] == '1' ? ' checked' : (!isset($_POST['kyc_occupation_angajat']) ? ' checked' : '')).'> Angajat*</label>
			<label style="display: inline-block; margin-right: 15px; width: 120px;"><input type="checkbox" name="kyc_occupation_student" value="1"'.(isset($_POST['kyc_occupation_student']) && $_POST['kyc_occupation_student'] == '1' ? ' checked' : '').'> Student*</label>
			<label style="display: inline-block; margin-right: 15px; width: 120px;"><input type="checkbox" name="kyc_occupation_antreprenor" value="1"'.(isset($_POST['kyc_occupation_antreprenor']) && $_POST['kyc_occupation_antreprenor'] == '1' ? ' checked' : '').'> Antreprenor*</label>
			<label style="display: inline-block; margin-right: 15px; width: 120px;"><input type="checkbox" name="kyc_occupation_somer" value="1"'.(isset($_POST['kyc_occupation_somer']) && $_POST['kyc_occupation_somer'] == '1' ? ' checked' : '').'> Șomer</label>
			<label style="display: inline-block; margin-right: 15px; width: 120px;"><input type="checkbox" name="kyc_occupation_pensionar" value="1"'.(isset($_POST['kyc_occupation_pensionar']) && $_POST['kyc_occupation_pensionar'] == '1' ? ' checked' : '').'> Pensionar</label>
		</div>
		
		<div style="margin: 10px 0; padding: 10px; border: 1px solid #ddd; border-radius: 5px;">
			<div style="font-weight: bold; margin-bottom: 10px;">Persoană expusă politic (Funcția publică deținută):</div>
			<label style="display: block; margin-bottom: 5px;"><input type="checkbox" name="kyc_no_public_function" value="1"'.(isset($_POST['kyc_no_public_function']) && $_POST['kyc_no_public_function'] == '1' ? ' checked' : (!isset($_POST['kyc_no_public_function']) ? ' checked' : '')).'> Nu dețin funcție publică</label>
			<label style="display: block; margin-bottom: 5px;"><input type="checkbox" name="kyc_public_function_deputat" value="1"'.(isset($_POST['kyc_public_function_deputat']) && $_POST['kyc_public_function_deputat'] == '1' ? ' checked' : '').'> Deputat al Parlamentului RM</label>
			<label style="display: block; margin-bottom: 5px;"><input type="checkbox" name="kyc_public_function_judecator" value="1"'.(isset($_POST['kyc_public_function_judecator']) && $_POST['kyc_public_function_judecator'] == '1' ? ' checked' : '').'> Judecător</label>
			<label style="display: block; margin-bottom: 5px;"><input type="checkbox" name="kyc_public_function_guvern" value="1"'.(isset($_POST['kyc_public_function_guvern']) && $_POST['kyc_public_function_guvern'] == '1' ? ' checked' : '').'> Membru al Guvernului RM</label>
			<label style="display: block; margin-bottom: 5px;"><input type="checkbox" name="kyc_public_function_primar" value="1"'.(isset($_POST['kyc_public_function_primar']) && $_POST['kyc_public_function_primar'] == '1' ? ' checked' : '').'> Primar</label>
			<label style="display: block; margin-bottom: 5px;"><input type="checkbox" name="kyc_public_function_partid" value="1"'.(isset($_POST['kyc_public_function_partid']) && $_POST['kyc_public_function_partid'] == '1' ? ' checked' : '').'> Membru al organelor de conducere ale partidelor politice</label>
			<label style="display: block; margin-bottom: 5px;"><input type="checkbox" name="kyc_public_function_consilier" value="1"'.(isset($_POST['kyc_public_function_consilier']) && $_POST['kyc_public_function_consilier'] == '1' ? ' checked' : '').'> Consilier al autorităților publice locale</label>
		</div>
		
		<div style="margin: 10px 0; padding: 10px; border: 1px solid #ddd; border-radius: 5px;">
			<div style="font-weight: bold; margin-bottom: 10px;">Scopul și natura tranzacțiilor:</div>
			<label style="display: block; margin-bottom: 5px;"><input type="checkbox" name="kyc_transaction_personal" value="1"'.(isset($_POST['kyc_transaction_personal']) && $_POST['kyc_transaction_personal'] == '1' ? ' checked' : (!isset($_POST['kyc_transaction_personal']) ? ' checked' : '')).'> Achiziționarea unui automobil pentru uz personal</label>
			<label style="display: block; margin-bottom: 5px;"><input type="checkbox" name="kyc_transaction_family" value="1"'.(isset($_POST['kyc_transaction_family']) && $_POST['kyc_transaction_family'] == '1' ? ' checked' : '').'> Achiziționarea unui automobil pentru uzul familiei / rudelor</label>
			<label style="display: block; margin-bottom: 5px;"><input type="checkbox" name="kyc_transaction_company" value="1"'.(isset($_POST['kyc_transaction_company']) && $_POST['kyc_transaction_company'] == '1' ? ' checked' : '').'> Achiziționarea unui automobil pentru companie / activitate economică</label>
			<label style="display: block; margin-bottom: 5px;"><input type="checkbox" name="kyc_transaction_resale" value="1"'.(isset($_POST['kyc_transaction_resale']) && $_POST['kyc_transaction_resale'] == '1' ? ' checked' : '').'> Achiziționarea unui automobil în scop de revânzare</label>
			<label style="display: block; margin-bottom: 5px;"><input type="checkbox" name="kyc_transaction_commercial" value="1"'.(isset($_POST['kyc_transaction_commercial']) && $_POST['kyc_transaction_commercial'] == '1' ? ' checked' : '').'> Achiziționarea unui automobil pentru prestarea de servicii comerciale (ex. taxi, livrări)</label>
			<label style="display: block; margin-bottom: 5px;"><input type="checkbox" name="kyc_transaction_transfer" value="1"'.(isset($_POST['kyc_transaction_transfer']) && $_POST['kyc_transaction_transfer'] == '1' ? ' checked' : '').'> Transfer de proprietate între rude (moștenire, donație etc.)</label>
		</div>

        <div style="margin: 10px 0; padding: 10px; border: 1px solid #ddd; border-radius: 5px;">
	<div style="font-weight: bold; margin-bottom: 10px;">Sursa mijloacelor bănești:</div>
	<label style="display: block; margin-bottom: 5px;"><input type="checkbox" name="kyc_funds_salary" value="1"'.(isset($_POST['kyc_funds_salary']) && $_POST['kyc_funds_salary'] == '1' ? ' checked' : (!isset($_POST['kyc_funds_salary']) ? ' checked' : '')).'> Salariu</label>
	<label style="display: block; margin-bottom: 5px;"><input type="checkbox" name="kyc_funds_dividends" value="1"'.(isset($_POST['kyc_funds_dividends']) && $_POST['kyc_funds_dividends'] == '1' ? ' checked' : '').'> Dividende</label>
	<label style="display: block; margin-bottom: 5px;"><input type="checkbox" name="kyc_funds_loan" value="1"'.(isset($_POST['kyc_funds_loan']) && $_POST['kyc_funds_loan'] == '1' ? ' checked' : '').'> Împrumut</label>
	<label style="display: block; margin-bottom: 5px;"><input type="checkbox" name="kyc_funds_business" value="1"'.(isset($_POST['kyc_funds_business']) && $_POST['kyc_funds_business'] == '1' ? ' checked' : '').'> Venit din activitatea de Antreprenor</label>
	<label style="display: block; margin-bottom: 5px;"><input type="checkbox" name="kyc_funds_inheritance" value="1"'.(isset($_POST['kyc_funds_inheritance']) && $_POST['kyc_funds_inheritance'] == '1' ? ' checked' : '').'> Moștenire</label>
	<label style="display: block; margin-bottom: 5px;"><input type="checkbox" name="kyc_funds_donations" value="1"'.(isset($_POST['kyc_funds_donations']) && $_POST['kyc_funds_donations'] == '1' ? ' checked' : '').'> Donații</label>
       </div>

		</div>

		<script>
		// KYC form visibility control based on price
		function toggleKycFormConPlata() {
			var priceInput = document.querySelector(\'form.menu_con_plata input[name="prc"]\');
			var kycSection = document.querySelector(\'form.menu_con_plata .kyc-questionnaire\');
			
			if (priceInput && kycSection) {
				priceInput.addEventListener(\'input\', function() {
					var price = parseFloat(this.value.replace(/[^0-9.]/g, \'\')) || 0;
					var showKyc = price >= 200000 || price === 0;
					
					kycSection.style.display = showKyc ? \'block\' : \'none\';
				});
				
				// Trigger initial check
				var initialPrice = parseFloat(priceInput.value.replace(/[^0-9.]/g, \'\')) || 0;
				var showKyc = initialPrice >= 200000 || initialPrice === 0;
				kycSection.style.display = showKyc ? \'block\' : \'none\';
			}
		}

		// Initialize when DOM is ready
		if (document.readyState === \'loading\') {
			document.addEventListener(\'DOMContentLoaded\', toggleKycFormConPlata);
		} else {
			toggleKycFormConPlata();
		}
		</script>

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
		<label class="lbl"><span class="ttl">Price, MDL</span><input class="need" type="text" name="prc" title="Price" /></label>
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
		
    <div class="kyc-questionnaire" style="display: block;">
			
		<div class="ttl">Date Chestionar</div>
			<div style="margin: 10px 0; padding: 10px; border: 1px solid #ddd; border-radius: 5px;">
				<div style="font-weight: bold; margin-bottom: 10px;">Actul de identitate:</div>
				<label style="display: inline-block; margin-right: 15px;"><input type="checkbox" name="kyc_doc_buletin" value="1"'.(isset($_POST['kyc_doc_buletin']) && $_POST['kyc_doc_buletin'] == '1' ? ' checked' : (!isset($_POST['kyc_doc_buletin']) ? ' checked' : '')).'> Buletin de identitate</label>
				<label style="display: inline-block; margin-right: 15px;"><input type="checkbox" name="kyc_doc_permis" value="1"'.(isset($_POST['kyc_doc_permis']) && $_POST['kyc_doc_permis'] == '1' ? ' checked' : '').'> Permis de ședere</label>
				<label style="display: inline-block; margin-right: 15px;"><input type="checkbox" name="kyc_doc_pasaport" value="1"'.(isset($_POST['kyc_doc_pasaport']) && $_POST['kyc_doc_pasaport'] == '1' ? ' checked' : '').'> Pașaport</label>
			</div>
		
			<div style="margin: 10px 0; padding: 10px; border: 1px solid #ddd; border-radius: 5px;">
			<div style="font-weight: bold; margin-bottom: 10px;">Ocupația:</div>
			<label style="display: inline-block; margin-right: 15px; width: 120px;"><input type="checkbox" name="kyc_occupation_angajat" value="1"'.(isset($_POST['kyc_occupation_angajat']) && $_POST['kyc_occupation_angajat'] == '1' ? ' checked' : (!isset($_POST['kyc_occupation_angajat']) ? ' checked' : '')).'> Angajat*</label>
			<label style="display: inline-block; margin-right: 15px; width: 120px;"><input type="checkbox" name="kyc_occupation_student" value="1"'.(isset($_POST['kyc_occupation_student']) && $_POST['kyc_occupation_student'] == '1' ? ' checked' : '').'> Student*</label>
			<label style="display: inline-block; margin-right: 15px; width: 120px;"><input type="checkbox" name="kyc_occupation_antreprenor" value="1"'.(isset($_POST['kyc_occupation_antreprenor']) && $_POST['kyc_occupation_antreprenor'] == '1' ? ' checked' : '').'> Antreprenor*</label>
			<label style="display: inline-block; margin-right: 15px; width: 120px;"><input type="checkbox" name="kyc_occupation_somer" value="1"'.(isset($_POST['kyc_occupation_somer']) && $_POST['kyc_occupation_somer'] == '1' ? ' checked' : '').'> Șomer</label>
			<label style="display: inline-block; margin-right: 15px; width: 120px;"><input type="checkbox" name="kyc_occupation_pensionar" value="1"'.(isset($_POST['kyc_occupation_pensionar']) && $_POST['kyc_occupation_pensionar'] == '1' ? ' checked' : '').'> Pensionar</label>
		</div>
		
		<div style="margin: 10px 0; padding: 10px; border: 1px solid #ddd; border-radius: 5px;">
			<div style="font-weight: bold; margin-bottom: 10px;">Persoană expusă politic (Funcția publică deținută):</div>
			<label style="display: block; margin-bottom: 5px;"><input type="checkbox" name="kyc_no_public_function" value="1"'.(isset($_POST['kyc_no_public_function']) && $_POST['kyc_no_public_function'] == '1' ? ' checked' : (!isset($_POST['kyc_no_public_function']) ? ' checked' : '')).'> Nu dețin funcție publică</label>
			<label style="display: block; margin-bottom: 5px;"><input type="checkbox" name="kyc_public_function_deputat" value="1"'.(isset($_POST['kyc_public_function_deputat']) && $_POST['kyc_public_function_deputat'] == '1' ? ' checked' : '').'> Deputat al Parlamentului RM</label>
			<label style="display: block; margin-bottom: 5px;"><input type="checkbox" name="kyc_public_function_judecator" value="1"'.(isset($_POST['kyc_public_function_judecator']) && $_POST['kyc_public_function_judecator'] == '1' ? ' checked' : '').'> Judecător</label>
			<label style="display: block; margin-bottom: 5px;"><input type="checkbox" name="kyc_public_function_guvern" value="1"'.(isset($_POST['kyc_public_function_guvern']) && $_POST['kyc_public_function_guvern'] == '1' ? ' checked' : '').'> Membru al Guvernului RM</label>
			<label style="display: block; margin-bottom: 5px;"><input type="checkbox" name="kyc_public_function_primar" value="1"'.(isset($_POST['kyc_public_function_primar']) && $_POST['kyc_public_function_primar'] == '1' ? ' checked' : '').'> Primar</label>
			<label style="display: block; margin-bottom: 5px;"><input type="checkbox" name="kyc_public_function_partid" value="1"'.(isset($_POST['kyc_public_function_partid']) && $_POST['kyc_public_function_partid'] == '1' ? ' checked' : '').'> Membru al organelor de conducere ale partidelor politice</label>
			<label style="display: block; margin-bottom: 5px;"><input type="checkbox" name="kyc_public_function_consilier" value="1"'.(isset($_POST['kyc_public_function_consilier']) && $_POST['kyc_public_function_consilier'] == '1' ? ' checked' : '').'> Consilier al autorităților publice locale</label>
		</div>
		
		<div style="margin: 10px 0; padding: 10px; border: 1px solid #ddd; border-radius: 5px;">
			<div style="font-weight: bold; margin-bottom: 10px;">Scopul și natura tranzacțiilor:</div>
			<label style="display: block; margin-bottom: 5px;"><input type="checkbox" name="kyc_transaction_personal" value="1"'.(isset($_POST['kyc_transaction_personal']) && $_POST['kyc_transaction_personal'] == '1' ? ' checked' : (!isset($_POST['kyc_transaction_personal']) ? ' checked' : '')).'> Achiziționarea unui automobil pentru uz personal</label>
			<label style="display: block; margin-bottom: 5px;"><input type="checkbox" name="kyc_transaction_family" value="1"'.(isset($_POST['kyc_transaction_family']) && $_POST['kyc_transaction_family'] == '1' ? ' checked' : '').'> Achiziționarea unui automobil pentru uzul familiei / rudelor</label>
			<label style="display: block; margin-bottom: 5px;"><input type="checkbox" name="kyc_transaction_company" value="1"'.(isset($_POST['kyc_transaction_company']) && $_POST['kyc_transaction_company'] == '1' ? ' checked' : '').'> Achiziționarea unui automobil pentru companie / activitate economică</label>
			<label style="display: block; margin-bottom: 5px;"><input type="checkbox" name="kyc_transaction_resale" value="1"'.(isset($_POST['kyc_transaction_resale']) && $_POST['kyc_transaction_resale'] == '1' ? ' checked' : '').'> Achiziționarea unui automobil în scop de revânzare</label>
			<label style="display: block; margin-bottom: 5px;"><input type="checkbox" name="kyc_transaction_commercial" value="1"'.(isset($_POST['kyc_transaction_commercial']) && $_POST['kyc_transaction_commercial'] == '1' ? ' checked' : '').'> Achiziționarea unui automobil pentru prestarea de servicii comerciale (ex. taxi, livrări)</label>
			<label style="display: block; margin-bottom: 5px;"><input type="checkbox" name="kyc_transaction_transfer" value="1"'.(isset($_POST['kyc_transaction_transfer']) && $_POST['kyc_transaction_transfer'] == '1' ? ' checked' : '').'> Transfer de proprietate între rude (moștenire, donație etc.)</label>
		</div>

        <div style="margin: 10px 0; padding: 10px; border: 1px solid #ddd; border-radius: 5px;">
	<div style="font-weight: bold; margin-bottom: 10px;">Sursa mijloacelor bănești:</div>
	<label style="display: block; margin-bottom: 5px;"><input type="checkbox" name="kyc_funds_salary" value="1"'.(isset($_POST['kyc_funds_salary']) && $_POST['kyc_funds_salary'] == '1' ? ' checked' : (!isset($_POST['kyc_funds_salary']) ? ' checked' : '')).'> Salariu</label>
	<label style="display: block; margin-bottom: 5px;"><input type="checkbox" name="kyc_funds_dividends" value="1"'.(isset($_POST['kyc_funds_dividends']) && $_POST['kyc_funds_dividends'] == '1' ? ' checked' : '').'> Dividende</label>
	<label style="display: block; margin-bottom: 5px;"><input type="checkbox" name="kyc_funds_loan" value="1"'.(isset($_POST['kyc_funds_loan']) && $_POST['kyc_funds_loan'] == '1' ? ' checked' : '').'> Împrumut</label>
	<label style="display: block; margin-bottom: 5px;"><input type="checkbox" name="kyc_funds_business" value="1"'.(isset($_POST['kyc_funds_business']) && $_POST['kyc_funds_business'] == '1' ? ' checked' : '').'> Venit din activitatea de Antreprenor</label>
	<label style="display: block; margin-bottom: 5px;"><input type="checkbox" name="kyc_funds_inheritance" value="1"'.(isset($_POST['kyc_funds_inheritance']) && $_POST['kyc_funds_inheritance'] == '1' ? ' checked' : '').'> Moștenire</label>
	<label style="display: block; margin-bottom: 5px;"><input type="checkbox" name="kyc_funds_donations" value="1"'.(isset($_POST['kyc_funds_donations']) && $_POST['kyc_funds_donations'] == '1' ? ' checked' : '').'> Donații</label>
       </div>

		</div>

		<script>
		// KYC form visibility control based on price
		function toggleKycFormConPlata() {
			var priceInput = document.querySelector(\'form.menu_con_plata input[name="prc"]\');
			var kycSection = document.querySelector(\'form.menu_con_plata .kyc-questionnaire\');
			
			if (priceInput && kycSection) {
				priceInput.addEventListener(\'input\', function() {
					var price = parseFloat(this.value.replace(/[^0-9.]/g, \'\')) || 0;
					var showKyc = price >= 200000 || price === 0;
					
					kycSection.style.display = showKyc ? \'block\' : \'none\';
				});
				
				// Trigger initial check
				var initialPrice = parseFloat(priceInput.value.replace(/[^0-9.]/g, \'\')) || 0;
				var showKyc = initialPrice >= 200000 || initialPrice === 0;
				kycSection.style.display = showKyc ? \'block\' : \'none\';
			}
		}

		// Initialize when DOM is ready
		if (document.readyState === \'loading\') {
			document.addEventListener(\'DOMContentLoaded\', toggleKycFormConPlata);
		} else {
			toggleKycFormConPlata();
		}
		</script>

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
		<label class="lbl"><span class="ttl">Price, MDL</span><input class="need" type="text" name="prc" title="Price" /></label>
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

         <div class="kyc-questionnaire" style="display: block;">
			
		<div class="ttl">Date Chestionar</div>
			<div style="margin: 10px 0; padding: 10px; border: 1px solid #ddd; border-radius: 5px;">
				<div style="font-weight: bold; margin-bottom: 10px;">Actul de identitate:</div>
				<label style="display: inline-block; margin-right: 15px;"><input type="checkbox" name="kyc_doc_buletin" value="1"'.(isset($_POST['kyc_doc_buletin']) && $_POST['kyc_doc_buletin'] == '1' ? ' checked' : (!isset($_POST['kyc_doc_buletin']) ? ' checked' : '')).'> Buletin de identitate</label>
				<label style="display: inline-block; margin-right: 15px;"><input type="checkbox" name="kyc_doc_permis" value="1"'.(isset($_POST['kyc_doc_permis']) && $_POST['kyc_doc_permis'] == '1' ? ' checked' : '').'> Permis de ședere</label>
				<label style="display: inline-block; margin-right: 15px;"><input type="checkbox" name="kyc_doc_pasaport" value="1"'.(isset($_POST['kyc_doc_pasaport']) && $_POST['kyc_doc_pasaport'] == '1' ? ' checked' : '').'> Pașaport</label>
			</div>
		
			<div style="margin: 10px 0; padding: 10px; border: 1px solid #ddd; border-radius: 5px;">
			<div style="font-weight: bold; margin-bottom: 10px;">Ocupația:</div>
			<label style="display: inline-block; margin-right: 15px; width: 120px;"><input type="checkbox" name="kyc_occupation_angajat" value="1"'.(isset($_POST['kyc_occupation_angajat']) && $_POST['kyc_occupation_angajat'] == '1' ? ' checked' : (!isset($_POST['kyc_occupation_angajat']) ? ' checked' : '')).'> Angajat*</label>
			<label style="display: inline-block; margin-right: 15px; width: 120px;"><input type="checkbox" name="kyc_occupation_student" value="1"'.(isset($_POST['kyc_occupation_student']) && $_POST['kyc_occupation_student'] == '1' ? ' checked' : '').'> Student*</label>
			<label style="display: inline-block; margin-right: 15px; width: 120px;"><input type="checkbox" name="kyc_occupation_antreprenor" value="1"'.(isset($_POST['kyc_occupation_antreprenor']) && $_POST['kyc_occupation_antreprenor'] == '1' ? ' checked' : '').'> Antreprenor*</label>
			<label style="display: inline-block; margin-right: 15px; width: 120px;"><input type="checkbox" name="kyc_occupation_somer" value="1"'.(isset($_POST['kyc_occupation_somer']) && $_POST['kyc_occupation_somer'] == '1' ? ' checked' : '').'> Șomer</label>
			<label style="display: inline-block; margin-right: 15px; width: 120px;"><input type="checkbox" name="kyc_occupation_pensionar" value="1"'.(isset($_POST['kyc_occupation_pensionar']) && $_POST['kyc_occupation_pensionar'] == '1' ? ' checked' : '').'> Pensionar</label>
		</div>
		
		<div style="margin: 10px 0; padding: 10px; border: 1px solid #ddd; border-radius: 5px;">
			<div style="font-weight: bold; margin-bottom: 10px;">Persoană expusă politic (Funcția publică deținută):</div>
			<label style="display: block; margin-bottom: 5px;"><input type="checkbox" name="kyc_no_public_function" value="1"'.(isset($_POST['kyc_no_public_function']) && $_POST['kyc_no_public_function'] == '1' ? ' checked' : (!isset($_POST['kyc_no_public_function']) ? ' checked' : '')).'> Nu dețin funcție publică</label>
			<label style="display: block; margin-bottom: 5px;"><input type="checkbox" name="kyc_public_function_deputat" value="1"'.(isset($_POST['kyc_public_function_deputat']) && $_POST['kyc_public_function_deputat'] == '1' ? ' checked' : '').'> Deputat al Parlamentului RM</label>
			<label style="display: block; margin-bottom: 5px;"><input type="checkbox" name="kyc_public_function_judecator" value="1"'.(isset($_POST['kyc_public_function_judecator']) && $_POST['kyc_public_function_judecator'] == '1' ? ' checked' : '').'> Judecător</label>
			<label style="display: block; margin-bottom: 5px;"><input type="checkbox" name="kyc_public_function_guvern" value="1"'.(isset($_POST['kyc_public_function_guvern']) && $_POST['kyc_public_function_guvern'] == '1' ? ' checked' : '').'> Membru al Guvernului RM</label>
			<label style="display: block; margin-bottom: 5px;"><input type="checkbox" name="kyc_public_function_primar" value="1"'.(isset($_POST['kyc_public_function_primar']) && $_POST['kyc_public_function_primar'] == '1' ? ' checked' : '').'> Primar</label>
			<label style="display: block; margin-bottom: 5px;"><input type="checkbox" name="kyc_public_function_partid" value="1"'.(isset($_POST['kyc_public_function_partid']) && $_POST['kyc_public_function_partid'] == '1' ? ' checked' : '').'> Membru al organelor de conducere ale partidelor politice</label>
			<label style="display: block; margin-bottom: 5px;"><input type="checkbox" name="kyc_public_function_consilier" value="1"'.(isset($_POST['kyc_public_function_consilier']) && $_POST['kyc_public_function_consilier'] == '1' ? ' checked' : '').'> Consilier al autorităților publice locale</label>
		</div>
		
		<div style="margin: 10px 0; padding: 10px; border: 1px solid #ddd; border-radius: 5px;">
			<div style="font-weight: bold; margin-bottom: 10px;">Scopul și natura tranzacțiilor:</div>
			<label style="display: block; margin-bottom: 5px;"><input type="checkbox" name="kyc_transaction_personal" value="1"'.(isset($_POST['kyc_transaction_personal']) && $_POST['kyc_transaction_personal'] == '1' ? ' checked' : (!isset($_POST['kyc_transaction_personal']) ? ' checked' : '')).'> Achiziționarea unui automobil pentru uz personal</label>
			<label style="display: block; margin-bottom: 5px;"><input type="checkbox" name="kyc_transaction_family" value="1"'.(isset($_POST['kyc_transaction_family']) && $_POST['kyc_transaction_family'] == '1' ? ' checked' : '').'> Achiziționarea unui automobil pentru uzul familiei / rudelor</label>
			<label style="display: block; margin-bottom: 5px;"><input type="checkbox" name="kyc_transaction_company" value="1"'.(isset($_POST['kyc_transaction_company']) && $_POST['kyc_transaction_company'] == '1' ? ' checked' : '').'> Achiziționarea unui automobil pentru companie / activitate economică</label>
			<label style="display: block; margin-bottom: 5px;"><input type="checkbox" name="kyc_transaction_resale" value="1"'.(isset($_POST['kyc_transaction_resale']) && $_POST['kyc_transaction_resale'] == '1' ? ' checked' : '').'> Achiziționarea unui automobil în scop de revânzare</label>
			<label style="display: block; margin-bottom: 5px;"><input type="checkbox" name="kyc_transaction_commercial" value="1"'.(isset($_POST['kyc_transaction_commercial']) && $_POST['kyc_transaction_commercial'] == '1' ? ' checked' : '').'> Achiziționarea unui automobil pentru prestarea de servicii comerciale (ex. taxi, livrări)</label>
			<label style="display: block; margin-bottom: 5px;"><input type="checkbox" name="kyc_transaction_transfer" value="1"'.(isset($_POST['kyc_transaction_transfer']) && $_POST['kyc_transaction_transfer'] == '1' ? ' checked' : '').'> Transfer de proprietate între rude (moștenire, donație etc.)</label>
		</div>

        <div style="margin: 10px 0; padding: 10px; border: 1px solid #ddd; border-radius: 5px;">
	<div style="font-weight: bold; margin-bottom: 10px;">Sursa mijloacelor bănești:</div>
	<label style="display: block; margin-bottom: 5px;"><input type="checkbox" name="kyc_funds_salary" value="1"'.(isset($_POST['kyc_funds_salary']) && $_POST['kyc_funds_salary'] == '1' ? ' checked' : (!isset($_POST['kyc_funds_salary']) ? ' checked' : '')).'> Salariu</label>
	<label style="display: block; margin-bottom: 5px;"><input type="checkbox" name="kyc_funds_dividends" value="1"'.(isset($_POST['kyc_funds_dividends']) && $_POST['kyc_funds_dividends'] == '1' ? ' checked' : '').'> Dividende</label>
	<label style="display: block; margin-bottom: 5px;"><input type="checkbox" name="kyc_funds_loan" value="1"'.(isset($_POST['kyc_funds_loan']) && $_POST['kyc_funds_loan'] == '1' ? ' checked' : '').'> Împrumut</label>
	<label style="display: block; margin-bottom: 5px;"><input type="checkbox" name="kyc_funds_business" value="1"'.(isset($_POST['kyc_funds_business']) && $_POST['kyc_funds_business'] == '1' ? ' checked' : '').'> Venit din activitatea de Antreprenor</label>
	<label style="display: block; margin-bottom: 5px;"><input type="checkbox" name="kyc_funds_inheritance" value="1"'.(isset($_POST['kyc_funds_inheritance']) && $_POST['kyc_funds_inheritance'] == '1' ? ' checked' : '').'> Moștenire</label>
	<label style="display: block; margin-bottom: 5px;"><input type="checkbox" name="kyc_funds_donations" value="1"'.(isset($_POST['kyc_funds_donations']) && $_POST['kyc_funds_donations'] == '1' ? ' checked' : '').'> Donații</label>
       </div>

		</div>

		<script>
		// KYC form visibility control based on price
		function toggleKycFormConPlata() {
			var priceInput = document.querySelector(\'form.menu_con_plata input[name="prc"]\');
			var kycSection = document.querySelector(\'form.menu_con_plata .kyc-questionnaire\');
			
			if (priceInput && kycSection) {
				priceInput.addEventListener(\'input\', function() {
					var price = parseFloat(this.value.replace(/[^0-9.]/g, \'\')) || 0;
					var showKyc = price >= 200000 || price === 0;
					
					kycSection.style.display = showKyc ? \'block\' : \'none\';
				});
				
				// Trigger initial check
				var initialPrice = parseFloat(priceInput.value.replace(/[^0-9.]/g, \'\')) || 0;
				var showKyc = initialPrice >= 200000 || initialPrice === 0;
				kycSection.style.display = showKyc ? \'block\' : \'none\';
			}
		}

		// Initialize when DOM is ready
		if (document.readyState === \'loading\') {
			document.addEventListener(\'DOMContentLoaded\', toggleKycFormConPlata);
		} else {
			toggleKycFormConPlata();
		}
		</script>

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
				
    <div class="kyc-questionnaire" style="display: block;">
			
		<div class="ttl">Date Chestionar</div>
			<div style="margin: 10px 0; padding: 10px; border: 1px solid #ddd; border-radius: 5px;">
				<div style="font-weight: bold; margin-bottom: 10px;">Actul de identitate:</div>
				<label style="display: inline-block; margin-right: 15px;"><input type="checkbox" name="kyc_doc_buletin" value="1"'.(isset($_POST['kyc_doc_buletin']) && $_POST['kyc_doc_buletin'] == '1' ? ' checked' : (!isset($_POST['kyc_doc_buletin']) ? ' checked' : '')).'> Buletin de identitate</label>
				<label style="display: inline-block; margin-right: 15px;"><input type="checkbox" name="kyc_doc_permis" value="1"'.(isset($_POST['kyc_doc_permis']) && $_POST['kyc_doc_permis'] == '1' ? ' checked' : '').'> Permis de ședere</label>
				<label style="display: inline-block; margin-right: 15px;"><input type="checkbox" name="kyc_doc_pasaport" value="1"'.(isset($_POST['kyc_doc_pasaport']) && $_POST['kyc_doc_pasaport'] == '1' ? ' checked' : '').'> Pașaport</label>
			</div>
		
			<div style="margin: 10px 0; padding: 10px; border: 1px solid #ddd; border-radius: 5px;">
			<div style="font-weight: bold; margin-bottom: 10px;">Ocupația:</div>
			<label style="display: inline-block; margin-right: 15px; width: 120px;"><input type="checkbox" name="kyc_occupation_angajat" value="1"'.(isset($_POST['kyc_occupation_angajat']) && $_POST['kyc_occupation_angajat'] == '1' ? ' checked' : (!isset($_POST['kyc_occupation_angajat']) ? ' checked' : '')).'> Angajat*</label>
			<label style="display: inline-block; margin-right: 15px; width: 120px;"><input type="checkbox" name="kyc_occupation_student" value="1"'.(isset($_POST['kyc_occupation_student']) && $_POST['kyc_occupation_student'] == '1' ? ' checked' : '').'> Student*</label>
			<label style="display: inline-block; margin-right: 15px; width: 120px;"><input type="checkbox" name="kyc_occupation_antreprenor" value="1"'.(isset($_POST['kyc_occupation_antreprenor']) && $_POST['kyc_occupation_antreprenor'] == '1' ? ' checked' : '').'> Antreprenor*</label>
			<label style="display: inline-block; margin-right: 15px; width: 120px;"><input type="checkbox" name="kyc_occupation_somer" value="1"'.(isset($_POST['kyc_occupation_somer']) && $_POST['kyc_occupation_somer'] == '1' ? ' checked' : '').'> Șomer</label>
			<label style="display: inline-block; margin-right: 15px; width: 120px;"><input type="checkbox" name="kyc_occupation_pensionar" value="1"'.(isset($_POST['kyc_occupation_pensionar']) && $_POST['kyc_occupation_pensionar'] == '1' ? ' checked' : '').'> Pensionar</label>
		</div>
		
		<div style="margin: 10px 0; padding: 10px; border: 1px solid #ddd; border-radius: 5px;">
			<div style="font-weight: bold; margin-bottom: 10px;">Persoană expusă politic (Funcția publică deținută):</div>
			<label style="display: block; margin-bottom: 5px;"><input type="checkbox" name="kyc_no_public_function" value="1"'.(isset($_POST['kyc_no_public_function']) && $_POST['kyc_no_public_function'] == '1' ? ' checked' : (!isset($_POST['kyc_no_public_function']) ? ' checked' : '')).'> Nu dețin funcție publică</label>
			<label style="display: block; margin-bottom: 5px;"><input type="checkbox" name="kyc_public_function_deputat" value="1"'.(isset($_POST['kyc_public_function_deputat']) && $_POST['kyc_public_function_deputat'] == '1' ? ' checked' : '').'> Deputat al Parlamentului RM</label>
			<label style="display: block; margin-bottom: 5px;"><input type="checkbox" name="kyc_public_function_judecator" value="1"'.(isset($_POST['kyc_public_function_judecator']) && $_POST['kyc_public_function_judecator'] == '1' ? ' checked' : '').'> Judecător</label>
			<label style="display: block; margin-bottom: 5px;"><input type="checkbox" name="kyc_public_function_guvern" value="1"'.(isset($_POST['kyc_public_function_guvern']) && $_POST['kyc_public_function_guvern'] == '1' ? ' checked' : '').'> Membru al Guvernului RM</label>
			<label style="display: block; margin-bottom: 5px;"><input type="checkbox" name="kyc_public_function_primar" value="1"'.(isset($_POST['kyc_public_function_primar']) && $_POST['kyc_public_function_primar'] == '1' ? ' checked' : '').'> Primar</label>
			<label style="display: block; margin-bottom: 5px;"><input type="checkbox" name="kyc_public_function_partid" value="1"'.(isset($_POST['kyc_public_function_partid']) && $_POST['kyc_public_function_partid'] == '1' ? ' checked' : '').'> Membru al organelor de conducere ale partidelor politice</label>
			<label style="display: block; margin-bottom: 5px;"><input type="checkbox" name="kyc_public_function_consilier" value="1"'.(isset($_POST['kyc_public_function_consilier']) && $_POST['kyc_public_function_consilier'] == '1' ? ' checked' : '').'> Consilier al autorităților publice locale</label>
		</div>
		
		<div style="margin: 10px 0; padding: 10px; border: 1px solid #ddd; border-radius: 5px;">
			<div style="font-weight: bold; margin-bottom: 10px;">Scopul și natura tranzacțiilor:</div>
			<label style="display: block; margin-bottom: 5px;"><input type="checkbox" name="kyc_transaction_personal" value="1"'.(isset($_POST['kyc_transaction_personal']) && $_POST['kyc_transaction_personal'] == '1' ? ' checked' : (!isset($_POST['kyc_transaction_personal']) ? ' checked' : '')).'> Achiziționarea unui automobil pentru uz personal</label>
			<label style="display: block; margin-bottom: 5px;"><input type="checkbox" name="kyc_transaction_family" value="1"'.(isset($_POST['kyc_transaction_family']) && $_POST['kyc_transaction_family'] == '1' ? ' checked' : '').'> Achiziționarea unui automobil pentru uzul familiei / rudelor</label>
			<label style="display: block; margin-bottom: 5px;"><input type="checkbox" name="kyc_transaction_company" value="1"'.(isset($_POST['kyc_transaction_company']) && $_POST['kyc_transaction_company'] == '1' ? ' checked' : '').'> Achiziționarea unui automobil pentru companie / activitate economică</label>
			<label style="display: block; margin-bottom: 5px;"><input type="checkbox" name="kyc_transaction_resale" value="1"'.(isset($_POST['kyc_transaction_resale']) && $_POST['kyc_transaction_resale'] == '1' ? ' checked' : '').'> Achiziționarea unui automobil în scop de revânzare</label>
			<label style="display: block; margin-bottom: 5px;"><input type="checkbox" name="kyc_transaction_commercial" value="1"'.(isset($_POST['kyc_transaction_commercial']) && $_POST['kyc_transaction_commercial'] == '1' ? ' checked' : '').'> Achiziționarea unui automobil pentru prestarea de servicii comerciale (ex. taxi, livrări)</label>
			<label style="display: block; margin-bottom: 5px;"><input type="checkbox" name="kyc_transaction_transfer" value="1"'.(isset($_POST['kyc_transaction_transfer']) && $_POST['kyc_transaction_transfer'] == '1' ? ' checked' : '').'> Transfer de proprietate între rude (moștenire, donație etc.)</label>
		</div>

        <div style="margin: 10px 0; padding: 10px; border: 1px solid #ddd; border-radius: 5px;">
	<div style="font-weight: bold; margin-bottom: 10px;">Sursa mijloacelor bănești:</div>
	<label style="display: block; margin-bottom: 5px;"><input type="checkbox" name="kyc_funds_salary" value="1"'.(isset($_POST['kyc_funds_salary']) && $_POST['kyc_funds_salary'] == '1' ? ' checked' : (!isset($_POST['kyc_funds_salary']) ? ' checked' : '')).'> Salariu</label>
	<label style="display: block; margin-bottom: 5px;"><input type="checkbox" name="kyc_funds_dividends" value="1"'.(isset($_POST['kyc_funds_dividends']) && $_POST['kyc_funds_dividends'] == '1' ? ' checked' : '').'> Dividende</label>
	<label style="display: block; margin-bottom: 5px;"><input type="checkbox" name="kyc_funds_loan" value="1"'.(isset($_POST['kyc_funds_loan']) && $_POST['kyc_funds_loan'] == '1' ? ' checked' : '').'> Împrumut</label>
	<label style="display: block; margin-bottom: 5px;"><input type="checkbox" name="kyc_funds_business" value="1"'.(isset($_POST['kyc_funds_business']) && $_POST['kyc_funds_business'] == '1' ? ' checked' : '').'> Venit din activitatea de Antreprenor</label>
	<label style="display: block; margin-bottom: 5px;"><input type="checkbox" name="kyc_funds_inheritance" value="1"'.(isset($_POST['kyc_funds_inheritance']) && $_POST['kyc_funds_inheritance'] == '1' ? ' checked' : '').'> Moștenire</label>
	<label style="display: block; margin-bottom: 5px;"><input type="checkbox" name="kyc_funds_donations" value="1"'.(isset($_POST['kyc_funds_donations']) && $_POST['kyc_funds_donations'] == '1' ? ' checked' : '').'> Donații</label>
       </div>

		</div>

		<script>
		// KYC form visibility control based on price
		function toggleKycFormConPlata() {
			var priceInput = document.querySelector(\'form.menu_con_plata input[name="prc"]\');
			var kycSection = document.querySelector(\'form.menu_con_plata .kyc-questionnaire\');
			
			if (priceInput && kycSection) {
				priceInput.addEventListener(\'input\', function() {
					var price = parseFloat(this.value.replace(/[^0-9.]/g, \'\')) || 0;
					var showKyc = price >= 200000 || price === 0;
					
					kycSection.style.display = showKyc ? \'block\' : \'none\';
				});
				
				// Trigger initial check
				var initialPrice = parseFloat(priceInput.value.replace(/[^0-9.]/g, \'\')) || 0;
				var showKyc = initialPrice >= 200000 || initialPrice === 0;
				kycSection.style.display = showKyc ? \'block\' : \'none\';
			}
		}

		// Initialize when DOM is ready
		if (document.readyState === \'loading\') {
			document.addEventListener(\'DOMContentLoaded\', toggleKycFormConPlata);
		} else {
			toggleKycFormConPlata();
		}
		</script>

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
		<label class="lbl"><span class="ttl">Adress</span><input class="need" type="text" name="u_adr" value="Republica Moldova, mun.Chişinau, or.Chisinau, str."  title="Adress" /></label>
		';
		
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

		<div class="kyc-questionnaire" style="display: block;">
			
		<div class="ttl">Date Chestionar</div>
			<div style="margin: 10px 0; padding: 10px; border: 1px solid #ddd; border-radius: 5px;">
				<div style="font-weight: bold; margin-bottom: 10px;">Actul de identitate:</div>
				<label style="display: inline-block; margin-right: 15px;"><input type="checkbox" name="kyc_doc_buletin" value="1"'.(isset($_POST['kyc_doc_buletin']) && $_POST['kyc_doc_buletin'] == '1' ? ' checked' : (!isset($_POST['kyc_doc_buletin']) ? ' checked' : '')).'> Buletin de identitate</label>
				<label style="display: inline-block; margin-right: 15px;"><input type="checkbox" name="kyc_doc_permis" value="1"'.(isset($_POST['kyc_doc_permis']) && $_POST['kyc_doc_permis'] == '1' ? ' checked' : '').'> Permis de ședere</label>
				<label style="display: inline-block; margin-right: 15px;"><input type="checkbox" name="kyc_doc_pasaport" value="1"'.(isset($_POST['kyc_doc_pasaport']) && $_POST['kyc_doc_pasaport'] == '1' ? ' checked' : '').'> Pașaport</label>
			</div>
		
			<div style="margin: 10px 0; padding: 10px; border: 1px solid #ddd; border-radius: 5px;">
			<div style="font-weight: bold; margin-bottom: 10px;">Ocupația:</div>
			<label style="display: inline-block; margin-right: 15px; width: 120px;"><input type="checkbox" name="kyc_occupation_angajat" value="1"'.(isset($_POST['kyc_occupation_angajat']) && $_POST['kyc_occupation_angajat'] == '1' ? ' checked' : (!isset($_POST['kyc_occupation_angajat']) ? ' checked' : '')).'> Angajat*</label>
			<label style="display: inline-block; margin-right: 15px; width: 120px;"><input type="checkbox" name="kyc_occupation_student" value="1"'.(isset($_POST['kyc_occupation_student']) && $_POST['kyc_occupation_student'] == '1' ? ' checked' : '').'> Student*</label>
			<label style="display: inline-block; margin-right: 15px; width: 120px;"><input type="checkbox" name="kyc_occupation_antreprenor" value="1"'.(isset($_POST['kyc_occupation_antreprenor']) && $_POST['kyc_occupation_antreprenor'] == '1' ? ' checked' : '').'> Antreprenor*</label>
			<label style="display: inline-block; margin-right: 15px; width: 120px;"><input type="checkbox" name="kyc_occupation_somer" value="1"'.(isset($_POST['kyc_occupation_somer']) && $_POST['kyc_occupation_somer'] == '1' ? ' checked' : '').'> Șomer</label>
			<label style="display: inline-block; margin-right: 15px; width: 120px;"><input type="checkbox" name="kyc_occupation_pensionar" value="1"'.(isset($_POST['kyc_occupation_pensionar']) && $_POST['kyc_occupation_pensionar'] == '1' ? ' checked' : '').'> Pensionar</label>
		</div>
		
		<div style="margin: 10px 0; padding: 10px; border: 1px solid #ddd; border-radius: 5px;">
			<div style="font-weight: bold; margin-bottom: 10px;">Persoană expusă politic (Funcția publică deținută):</div>
			<label style="display: block; margin-bottom: 5px;"><input type="checkbox" name="kyc_no_public_function" value="1"'.(isset($_POST['kyc_no_public_function']) && $_POST['kyc_no_public_function'] == '1' ? ' checked' : (!isset($_POST['kyc_no_public_function']) ? ' checked' : '')).'> Nu dețin funcție publică</label>
			<label style="display: block; margin-bottom: 5px;"><input type="checkbox" name="kyc_public_function_deputat" value="1"'.(isset($_POST['kyc_public_function_deputat']) && $_POST['kyc_public_function_deputat'] == '1' ? ' checked' : '').'> Deputat al Parlamentului RM</label>
			<label style="display: block; margin-bottom: 5px;"><input type="checkbox" name="kyc_public_function_judecator" value="1"'.(isset($_POST['kyc_public_function_judecator']) && $_POST['kyc_public_function_judecator'] == '1' ? ' checked' : '').'> Judecător</label>
			<label style="display: block; margin-bottom: 5px;"><input type="checkbox" name="kyc_public_function_guvern" value="1"'.(isset($_POST['kyc_public_function_guvern']) && $_POST['kyc_public_function_guvern'] == '1' ? ' checked' : '').'> Membru al Guvernului RM</label>
			<label style="display: block; margin-bottom: 5px;"><input type="checkbox" name="kyc_public_function_primar" value="1"'.(isset($_POST['kyc_public_function_primar']) && $_POST['kyc_public_function_primar'] == '1' ? ' checked' : '').'> Primar</label>
			<label style="display: block; margin-bottom: 5px;"><input type="checkbox" name="kyc_public_function_partid" value="1"'.(isset($_POST['kyc_public_function_partid']) && $_POST['kyc_public_function_partid'] == '1' ? ' checked' : '').'> Membru al organelor de conducere ale partidelor politice</label>
			<label style="display: block; margin-bottom: 5px;"><input type="checkbox" name="kyc_public_function_consilier" value="1"'.(isset($_POST['kyc_public_function_consilier']) && $_POST['kyc_public_function_consilier'] == '1' ? ' checked' : '').'> Consilier al autorităților publice locale</label>
		</div>
		
		<div style="margin: 10px 0; padding: 10px; border: 1px solid #ddd; border-radius: 5px;">
			<div style="font-weight: bold; margin-bottom: 10px;">Scopul și natura tranzacțiilor:</div>
			<label style="display: block; margin-bottom: 5px;"><input type="checkbox" name="kyc_transaction_personal" value="1"'.(isset($_POST['kyc_transaction_personal']) && $_POST['kyc_transaction_personal'] == '1' ? ' checked' : (!isset($_POST['kyc_transaction_personal']) ? ' checked' : '')).'> Achiziționarea unui automobil pentru uz personal</label>
			<label style="display: block; margin-bottom: 5px;"><input type="checkbox" name="kyc_transaction_family" value="1"'.(isset($_POST['kyc_transaction_family']) && $_POST['kyc_transaction_family'] == '1' ? ' checked' : '').'> Achiziționarea unui automobil pentru uzul familiei / rudelor</label>
			<label style="display: block; margin-bottom: 5px;"><input type="checkbox" name="kyc_transaction_company" value="1"'.(isset($_POST['kyc_transaction_company']) && $_POST['kyc_transaction_company'] == '1' ? ' checked' : '').'> Achiziționarea unui automobil pentru companie / activitate economică</label>
			<label style="display: block; margin-bottom: 5px;"><input type="checkbox" name="kyc_transaction_resale" value="1"'.(isset($_POST['kyc_transaction_resale']) && $_POST['kyc_transaction_resale'] == '1' ? ' checked' : '').'> Achiziționarea unui automobil în scop de revânzare</label>
			<label style="display: block; margin-bottom: 5px;"><input type="checkbox" name="kyc_transaction_commercial" value="1"'.(isset($_POST['kyc_transaction_commercial']) && $_POST['kyc_transaction_commercial'] == '1' ? ' checked' : '').'> Achiziționarea unui automobil pentru prestarea de servicii comerciale (ex. taxi, livrări)</label>
			<label style="display: block; margin-bottom: 5px;"><input type="checkbox" name="kyc_transaction_transfer" value="1"'.(isset($_POST['kyc_transaction_transfer']) && $_POST['kyc_transaction_transfer'] == '1' ? ' checked' : '').'> Transfer de proprietate între rude (moștenire, donație etc.)</label>
		</div>

        <div style="margin: 10px 0; padding: 10px; border: 1px solid #ddd; border-radius: 5px;">
	<div style="font-weight: bold; margin-bottom: 10px;">Sursa mijloacelor bănești:</div>
	<label style="display: block; margin-bottom: 5px;"><input type="checkbox" name="kyc_funds_salary" value="1"'.(isset($_POST['kyc_funds_salary']) && $_POST['kyc_funds_salary'] == '1' ? ' checked' : (!isset($_POST['kyc_funds_salary']) ? ' checked' : '')).'> Salariu</label>
	<label style="display: block; margin-bottom: 5px;"><input type="checkbox" name="kyc_funds_dividends" value="1"'.(isset($_POST['kyc_funds_dividends']) && $_POST['kyc_funds_dividends'] == '1' ? ' checked' : '').'> Dividende</label>
	<label style="display: block; margin-bottom: 5px;"><input type="checkbox" name="kyc_funds_loan" value="1"'.(isset($_POST['kyc_funds_loan']) && $_POST['kyc_funds_loan'] == '1' ? ' checked' : '').'> Împrumut</label>
	<label style="display: block; margin-bottom: 5px;"><input type="checkbox" name="kyc_funds_business" value="1"'.(isset($_POST['kyc_funds_business']) && $_POST['kyc_funds_business'] == '1' ? ' checked' : '').'> Venit din activitatea de Antreprenor</label>
	<label style="display: block; margin-bottom: 5px;"><input type="checkbox" name="kyc_funds_inheritance" value="1"'.(isset($_POST['kyc_funds_inheritance']) && $_POST['kyc_funds_inheritance'] == '1' ? ' checked' : '').'> Moștenire</label>
	<label style="display: block; margin-bottom: 5px;"><input type="checkbox" name="kyc_funds_donations" value="1"'.(isset($_POST['kyc_funds_donations']) && $_POST['kyc_funds_donations'] == '1' ? ' checked' : '').'> Donații</label>
       </div>

		</div>

		<script>
		// KYC form visibility control based on price
		function toggleKycFormConPlata() {
			var priceInput = document.querySelector(\'form.menu_con_plata input[name="prc"]\');
			var kycSection = document.querySelector(\'form.menu_con_plata .kyc-questionnaire\');
			
			if (priceInput && kycSection) {
				priceInput.addEventListener(\'input\', function() {
					var price = parseFloat(this.value.replace(/[^0-9.]/g, \'\')) || 0;
					var showKyc = price >= 200000 || price === 0;
					
					kycSection.style.display = showKyc ? \'block\' : \'none\';
				});
				
				// Trigger initial check
				var initialPrice = parseFloat(priceInput.value.replace(/[^0-9.]/g, \'\')) || 0;
				var showKyc = initialPrice >= 200000 || initialPrice === 0;
				kycSection.style.display = showKyc ? \'block\' : \'none\';
			}
		}

		// Initialize when DOM is ready
		if (document.readyState === \'loading\') {
			document.addEventListener(\'DOMContentLoaded\', toggleKycFormConPlata);
		} else {
			toggleKycFormConPlata();
		}
		</script>

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
		
    <div class="kyc-questionnaire" style="display: block;">
			
		<div class="ttl">Date Chestionar</div>
			<div style="margin: 10px 0; padding: 10px; border: 1px solid #ddd; border-radius: 5px;">
				<div style="font-weight: bold; margin-bottom: 10px;">Actul de identitate:</div>
				<label style="display: inline-block; margin-right: 15px;"><input type="checkbox" name="kyc_doc_buletin" value="1"'.(isset($_POST['kyc_doc_buletin']) && $_POST['kyc_doc_buletin'] == '1' ? ' checked' : (!isset($_POST['kyc_doc_buletin']) ? ' checked' : '')).'> Buletin de identitate</label>
				<label style="display: inline-block; margin-right: 15px;"><input type="checkbox" name="kyc_doc_permis" value="1"'.(isset($_POST['kyc_doc_permis']) && $_POST['kyc_doc_permis'] == '1' ? ' checked' : '').'> Permis de ședere</label>
				<label style="display: inline-block; margin-right: 15px;"><input type="checkbox" name="kyc_doc_pasaport" value="1"'.(isset($_POST['kyc_doc_pasaport']) && $_POST['kyc_doc_pasaport'] == '1' ? ' checked' : '').'> Pașaport</label>
			</div>
		
			<div style="margin: 10px 0; padding: 10px; border: 1px solid #ddd; border-radius: 5px;">
			<div style="font-weight: bold; margin-bottom: 10px;">Ocupația:</div>
			<label style="display: inline-block; margin-right: 15px; width: 120px;"><input type="checkbox" name="kyc_occupation_angajat" value="1"'.(isset($_POST['kyc_occupation_angajat']) && $_POST['kyc_occupation_angajat'] == '1' ? ' checked' : (!isset($_POST['kyc_occupation_angajat']) ? ' checked' : '')).'> Angajat*</label>
			<label style="display: inline-block; margin-right: 15px; width: 120px;"><input type="checkbox" name="kyc_occupation_student" value="1"'.(isset($_POST['kyc_occupation_student']) && $_POST['kyc_occupation_student'] == '1' ? ' checked' : '').'> Student*</label>
			<label style="display: inline-block; margin-right: 15px; width: 120px;"><input type="checkbox" name="kyc_occupation_antreprenor" value="1"'.(isset($_POST['kyc_occupation_antreprenor']) && $_POST['kyc_occupation_antreprenor'] == '1' ? ' checked' : '').'> Antreprenor*</label>
			<label style="display: inline-block; margin-right: 15px; width: 120px;"><input type="checkbox" name="kyc_occupation_somer" value="1"'.(isset($_POST['kyc_occupation_somer']) && $_POST['kyc_occupation_somer'] == '1' ? ' checked' : '').'> Șomer</label>
			<label style="display: inline-block; margin-right: 15px; width: 120px;"><input type="checkbox" name="kyc_occupation_pensionar" value="1"'.(isset($_POST['kyc_occupation_pensionar']) && $_POST['kyc_occupation_pensionar'] == '1' ? ' checked' : '').'> Pensionar</label>
		</div>
		
		<div style="margin: 10px 0; padding: 10px; border: 1px solid #ddd; border-radius: 5px;">
			<div style="font-weight: bold; margin-bottom: 10px;">Persoană expusă politic (Funcția publică deținută):</div>
			<label style="display: block; margin-bottom: 5px;"><input type="checkbox" name="kyc_no_public_function" value="1"'.(isset($_POST['kyc_no_public_function']) && $_POST['kyc_no_public_function'] == '1' ? ' checked' : (!isset($_POST['kyc_no_public_function']) ? ' checked' : '')).'> Nu dețin funcție publică</label>
			<label style="display: block; margin-bottom: 5px;"><input type="checkbox" name="kyc_public_function_deputat" value="1"'.(isset($_POST['kyc_public_function_deputat']) && $_POST['kyc_public_function_deputat'] == '1' ? ' checked' : '').'> Deputat al Parlamentului RM</label>
			<label style="display: block; margin-bottom: 5px;"><input type="checkbox" name="kyc_public_function_judecator" value="1"'.(isset($_POST['kyc_public_function_judecator']) && $_POST['kyc_public_function_judecator'] == '1' ? ' checked' : '').'> Judecător</label>
			<label style="display: block; margin-bottom: 5px;"><input type="checkbox" name="kyc_public_function_guvern" value="1"'.(isset($_POST['kyc_public_function_guvern']) && $_POST['kyc_public_function_guvern'] == '1' ? ' checked' : '').'> Membru al Guvernului RM</label>
			<label style="display: block; margin-bottom: 5px;"><input type="checkbox" name="kyc_public_function_primar" value="1"'.(isset($_POST['kyc_public_function_primar']) && $_POST['kyc_public_function_primar'] == '1' ? ' checked' : '').'> Primar</label>
			<label style="display: block; margin-bottom: 5px;"><input type="checkbox" name="kyc_public_function_partid" value="1"'.(isset($_POST['kyc_public_function_partid']) && $_POST['kyc_public_function_partid'] == '1' ? ' checked' : '').'> Membru al organelor de conducere ale partidelor politice</label>
			<label style="display: block; margin-bottom: 5px;"><input type="checkbox" name="kyc_public_function_consilier" value="1"'.(isset($_POST['kyc_public_function_consilier']) && $_POST['kyc_public_function_consilier'] == '1' ? ' checked' : '').'> Consilier al autorităților publice locale</label>
		</div>
		
		<div style="margin: 10px 0; padding: 10px; border: 1px solid #ddd; border-radius: 5px;">
			<div style="font-weight: bold; margin-bottom: 10px;">Scopul și natura tranzacțiilor:</div>
			<label style="display: block; margin-bottom: 5px;"><input type="checkbox" name="kyc_transaction_personal" value="1"'.(isset($_POST['kyc_transaction_personal']) && $_POST['kyc_transaction_personal'] == '1' ? ' checked' : (!isset($_POST['kyc_transaction_personal']) ? ' checked' : '')).'> Achiziționarea unui automobil pentru uz personal</label>
			<label style="display: block; margin-bottom: 5px;"><input type="checkbox" name="kyc_transaction_family" value="1"'.(isset($_POST['kyc_transaction_family']) && $_POST['kyc_transaction_family'] == '1' ? ' checked' : '').'> Achiziționarea unui automobil pentru uzul familiei / rudelor</label>
			<label style="display: block; margin-bottom: 5px;"><input type="checkbox" name="kyc_transaction_company" value="1"'.(isset($_POST['kyc_transaction_company']) && $_POST['kyc_transaction_company'] == '1' ? ' checked' : '').'> Achiziționarea unui automobil pentru companie / activitate economică</label>
			<label style="display: block; margin-bottom: 5px;"><input type="checkbox" name="kyc_transaction_resale" value="1"'.(isset($_POST['kyc_transaction_resale']) && $_POST['kyc_transaction_resale'] == '1' ? ' checked' : '').'> Achiziționarea unui automobil în scop de revânzare</label>
			<label style="display: block; margin-bottom: 5px;"><input type="checkbox" name="kyc_transaction_commercial" value="1"'.(isset($_POST['kyc_transaction_commercial']) && $_POST['kyc_transaction_commercial'] == '1' ? ' checked' : '').'> Achiziționarea unui automobil pentru prestarea de servicii comerciale (ex. taxi, livrări)</label>
			<label style="display: block; margin-bottom: 5px;"><input type="checkbox" name="kyc_transaction_transfer" value="1"'.(isset($_POST['kyc_transaction_transfer']) && $_POST['kyc_transaction_transfer'] == '1' ? ' checked' : '').'> Transfer de proprietate între rude (moștenire, donație etc.)</label>
		</div>

        <div style="margin: 10px 0; padding: 10px; border: 1px solid #ddd; border-radius: 5px;">
	<div style="font-weight: bold; margin-bottom: 10px;">Sursa mijloacelor bănești:</div>
	<label style="display: block; margin-bottom: 5px;"><input type="checkbox" name="kyc_funds_salary" value="1"'.(isset($_POST['kyc_funds_salary']) && $_POST['kyc_funds_salary'] == '1' ? ' checked' : (!isset($_POST['kyc_funds_salary']) ? ' checked' : '')).'> Salariu</label>
	<label style="display: block; margin-bottom: 5px;"><input type="checkbox" name="kyc_funds_dividends" value="1"'.(isset($_POST['kyc_funds_dividends']) && $_POST['kyc_funds_dividends'] == '1' ? ' checked' : '').'> Dividende</label>
	<label style="display: block; margin-bottom: 5px;"><input type="checkbox" name="kyc_funds_loan" value="1"'.(isset($_POST['kyc_funds_loan']) && $_POST['kyc_funds_loan'] == '1' ? ' checked' : '').'> Împrumut</label>
	<label style="display: block; margin-bottom: 5px;"><input type="checkbox" name="kyc_funds_business" value="1"'.(isset($_POST['kyc_funds_business']) && $_POST['kyc_funds_business'] == '1' ? ' checked' : '').'> Venit din activitatea de Antreprenor</label>
	<label style="display: block; margin-bottom: 5px;"><input type="checkbox" name="kyc_funds_inheritance" value="1"'.(isset($_POST['kyc_funds_inheritance']) && $_POST['kyc_funds_inheritance'] == '1' ? ' checked' : '').'> Moștenire</label>
	<label style="display: block; margin-bottom: 5px;"><input type="checkbox" name="kyc_funds_donations" value="1"'.(isset($_POST['kyc_funds_donations']) && $_POST['kyc_funds_donations'] == '1' ? ' checked' : '').'> Donații</label>
       </div>

		</div>

		<script>
		// KYC form visibility control based on price
		function toggleKycFormConPlata() {
			var priceInput = document.querySelector(\'form.menu_con_plata input[name="prc"]\');
			var kycSection = document.querySelector(\'form.menu_con_plata .kyc-questionnaire\');
			
			if (priceInput && kycSection) {
				priceInput.addEventListener(\'input\', function() {
					var price = parseFloat(this.value.replace(/[^0-9.]/g, \'\')) || 0;
					var showKyc = price >= 200000 || price === 0;
					
					kycSection.style.display = showKyc ? \'block\' : \'none\';
				});
				
				// Trigger initial check
				var initialPrice = parseFloat(priceInput.value.replace(/[^0-9.]/g, \'\')) || 0;
				var showKyc = initialPrice >= 200000 || initialPrice === 0;
				kycSection.style.display = showKyc ? \'block\' : \'none\';
			}
		}

		// Initialize when DOM is ready
		if (document.readyState === \'loading\') {
			document.addEventListener(\'DOMContentLoaded\', toggleKycFormConPlata);
		} else {
			toggleKycFormConPlata();
		}
		</script>

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