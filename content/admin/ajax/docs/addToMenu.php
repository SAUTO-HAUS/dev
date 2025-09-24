//__________________________________________________________________________________________CESIONAR
if ( $t_mp[5]=='cesionar' || isset($mixall) ){
	// Auto-generate Cesionar number CS-YYYY-XXX with database counter
	$cesionar_year = date('Y');
	$cesionar_seq = 1;
	
	// Get next sequence number from database
	try {
		$pdo_seq = $db->prepare('SELECT COUNT(*) + 1 as next_seq FROM '.$prefx.'_docs_ctlg WHERE f = "cesionar" AND YEAR(date) = ?');
		$pdo_seq->execute([$cesionar_year]);
		$seq_result = $pdo_seq->fetch(PDO::FETCH_ASSOC);
		if ($seq_result) {
			$cesionar_seq = $seq_result['next_seq'];
		}
	} catch (Exception $e) {
		// Fallback to 1 if database error
		$cesionar_seq = 1;
	}
	
	$cesionar_number = 'CS-'.$cesionar_year.'-'.str_pad($cesionar_seq, 3, '0', STR_PAD_LEFT);
	
	$rtrn .= ( isset($mixall)?'<form class="menu_cesionar">':'' ).'
	<div class="ttl">Bazele datelor</div>
	<label class="lbl"><span class="ttl">Data</span><input class="need dt" type="date" name="date" min="1900-01-01" max="2099-12-31" title="Data" value="'.(isset($_POST['date']) ? $_POST['date'] : date('Y-m-d')).'" /></label>
	<label class="lbl"><span class="ttl">Numărul Anexa</span><input class="need" type="text" name="annexa_nr" title="Numărul Anexa" value="'.(isset($_POST['annexa_nr']) ? htmlspecialchars($_POST['annexa_nr']) : $cesionar_number).'" /></label>
	<label class="lbl"><span class="ttl">Număr contract</span><input class="need" type="text" name="cont_nr" title="Număr contract" value="'.(isset($_POST['cont_nr']) ? htmlspecialchars($_POST['cont_nr']) : '').'" /></label>
	
	<div class="ttl">Auto</div>
	<label class="lbl"><span class="ttl">Brand</span><select name="br" title="Brand">
		<option value="x" class="def" disabled selected>-</option>
		'.$br_html.'
	</select></label>
	<label class="lbl"><span class="ttl">Model</span><select name="mo" title="Model">
		<option value="x" data-br="" class="def" disabled selected>-</option>
		'.$mo_html.'
	</select></label>
	<label class="lbl"><span class="ttl">Year</span><input class="need" type="text" name="yr" title="Year" value="'.(isset($_POST['yr']) ? htmlspecialchars($_POST['yr']) : '').'" /></label>
	<label class="lbl"><span class="ttl">Color</span><select name="clr" title="Color">
		<option value="" selected>-</option>
		'.$clr_html.'
	</select></label>
	<label class="lbl"><span class="ttl">VIN code</span><input class="need" type="text" name="vin" title="VIN code" value="'.(isset($_POST['vin']) ? htmlspecialchars($_POST['vin']) : '').'" /></label>
	<label class="lbl"><span class="ttl">Price</span><input class="need" type="number" name="prc" title="Price" value="'.(isset($_POST['prc']) ? $_POST['prc'] : '').'" /></label>
	<label class="lbl"><span class="ttl">Currency</span><select name="cur" title="Currency">
		<option value="MDL"'.(isset($_POST['cur']) && $_POST['cur'] == 'MDL' ? ' selected' : (!isset($_POST['cur']) ? ' selected' : '')).'>MDL</option>
		<option value="EUR"'.(isset($_POST['cur']) && $_POST['cur'] == 'EUR' ? ' selected' : '').'>EUR</option>
	</select></label>
	
	<div class="ttl">Cumpărător</div>
	<label class="lbl"><span class="ttl">Tip</span><select name="u_tp">
		<option value="fiz"'.(isset($_POST['u_tp']) && $_POST['u_tp'] == 'fiz' ? ' selected' : (!isset($_POST['u_tp']) ? ' selected' : '')).'>Fizic</option>
		<option value="jur"'.(isset($_POST['u_tp']) && $_POST['u_tp'] == 'jur' ? ' selected' : '').'>Juridic</option>
	</select></label>
	<label class="lbl"><span class="ttl">IDNO/CF</span><input class="need" type="text" name="u_cf_idno" title="IDNO/CF" value="'.(isset($_POST['u_cf_idno']) ? htmlspecialchars($_POST['u_cf_idno']) : '').'" /></label>
	<label class="lbl"><span class="ttl">Name</span><input class="need" type="text" name="u_nm" title="Name" value="'.(isset($_POST['u_nm']) ? htmlspecialchars($_POST['u_nm']) : '').'" /></label>
	<label class="lbl"><span class="ttl">Data nasterii/TVA</span><input type="text" name="u_tva_dt" title="Data nasterii" value="'.(isset($_POST['u_tva_dt']) ? htmlspecialchars($_POST['u_tva_dt']) : '').'" /></label>
	<label class="lbl"><span class="ttl">Data eliberării/IBAN</span><input type="text" name="u_iban_dt_tk" title="Data eliberării" value="'.(isset($_POST['u_iban_dt_tk']) ? htmlspecialchars($_POST['u_iban_dt_tk']) : '').'" /></label>
	<label class="lbl max"><span class="ttl">Adresă</span><textarea name="u_adr" title="Adresă" rows="2">'.(isset($_POST['u_adr']) ? htmlspecialchars($_POST['u_adr']) : 'Republica Moldova, mun.Chișinău, or.Chișinău, str.').'</textarea></label>
	<label class="lbl"><span class="ttl">Phone</span><input type="text" name="u_phn" title="Phone" value="'.(isset($_POST['u_phn']) ? htmlspecialchars($_POST['u_phn']) : '+373').'" /></label>
	<label class="lbl"><span class="ttl">Email</span><input type="text" name="u_eml" title="Email" value="'.(isset($_POST['u_eml']) ? htmlspecialchars($_POST['u_eml']) : '').'" /></label>
	
	<div class="ttl">Cesionar (terță parte care primește dreptul de plată)</div>
	<label class="lbl"><span class="ttl">Tip</span><select name="cesionar_tp" class="cesionar-field">
		<option value="fiz"'.(isset($_POST['cesionar_tp']) && $_POST['cesionar_tp'] == 'fiz' ? ' selected' : (!isset($_POST['cesionar_tp']) ? ' selected' : '')).'>Fizic</option>
		<option value="jur"'.(isset($_POST['cesionar_tp']) && $_POST['cesionar_tp'] == 'jur' ? ' selected' : '').'>Juridic</option>
	</select></label>
	<label class="lbl"><span class="ttl">IDNO/CF</span><input class="need" type="text" name="cesionar_cf_idno" class="cesionar-field" title="Cesionar IDNO/CF" value="'.(isset($_POST['cesionar_cf_idno']) ? htmlspecialchars($_POST['cesionar_cf_idno']) : '').'" /></label>
	<label class="lbl"><span class="ttl">Name *</span><input class="need" type="text" name="cesionar_nm" class="cesionar-field" title="Cesionar Name" value="'.(isset($_POST['cesionar_nm']) ? htmlspecialchars($_POST['cesionar_nm']) : '').'" /></label>
	<label class="lbl"><span class="ttl">Data nasterii/TVA</span><input type="text" name="cesionar_tva_dt" class="cesionar-field" title="Cesionar Data nasterii" value="'.(isset($_POST['cesionar_tva_dt']) ? htmlspecialchars($_POST['cesionar_tva_dt']) : '').'" /></label>
	<label class="lbl"><span class="ttl">Data eliberării/IBAN</span><input type="text" name="cesionar_iban_dt_tk" class="cesionar-field" title="Cesionar Data eliberării" value="'.(isset($_POST['cesionar_iban_dt_tk']) ? htmlspecialchars($_POST['cesionar_iban_dt_tk']) : '').'" /></label>
	<label class="lbl"><span class="ttl">Cont bancar</span><input type="text" name="cesionar_account" class="cesionar-field" title="Cesionar Account" value="'.(isset($_POST['cesionar_account']) ? htmlspecialchars($_POST['cesionar_account']) : '').'" /></label>
	<label class="lbl max"><span class="ttl">Adresă</span><textarea name="cesionar_adr" class="cesionar-field" title="Cesionar Adresă" rows="2">'.(isset($_POST['cesionar_adr']) ? htmlspecialchars($_POST['cesionar_adr']) : 'Republica Moldova, mun.Chișinău, or.Chișinău, str.').'</textarea></label>
	<label class="lbl"><span class="ttl">Phone</span><input type="text" name="cesionar_phn" class="cesionar-field" title="Cesionar Phone" value="'.(isset($_POST['cesionar_phn']) ? htmlspecialchars($_POST['cesionar_phn']) : '+373').'" /></label>
	<label class="lbl"><span class="ttl">Email</span><input type="text" name="cesionar_eml" class="cesionar-field" title="Cesionar Email" value="'.(isset($_POST['cesionar_eml']) ? htmlspecialchars($_POST['cesionar_eml']) : '').'" /></label>
	<label class="lbl"><span class="ttl">Sumă cesiune * (în valuta contractului)</span><input class="need" type="number" name="cesionar_suma" class="cesionar-field" title="Sumă cesiune" value="'.(isset($_POST['cesionar_suma']) ? $_POST['cesionar_suma'] : '').'" step="0.01" min="0.01" /></label>
	
	<div class="ttl">"Garanție", text suplimentar</div>
	<div id="grnt_fld_bx" name="grnt_txt" data-qu="0"></div>
	<div class="btn" data-fn="add_grnt_fld">Adăugați</div>
	
	'.( isset($mixall)?'</form>':'' );
}

//__________________________________________________________________________________________ANNEXA (CESIUNE DREPT DE PLATĂ)
if ( $t_mp[5]=='annexa' || isset($mixall) ){
	// Auto-generate Annexa number AN2-YYYY-XXX with database counter
	$annexa_year = date('Y');
	$annexa_seq = 1;
	
	// Get next sequence number from database
	try {
		$pdo_seq = $db->prepare('SELECT COUNT(*) + 1 as next_seq FROM '.$prefx.'_docs_ctlg WHERE f = "annexa" AND YEAR(date) = ?');
		$pdo_seq->execute([$annexa_year]);
		$seq_result = $pdo_seq->fetch(PDO::FETCH_ASSOC);
		if ($seq_result) {
			$annexa_seq = $seq_result['next_seq'];
		}
	} catch (Exception $e) {
		// Fallback to 1 if database error
		$annexa_seq = 1;
	}
	
	$annexa_number = 'AN2-'.$annexa_year.'-'.str_pad($annexa_seq, 3, '0', STR_PAD_LEFT);
	
	// Load contracts for dropdown
	$contracts_options = '';
	try {
		$pdo_contracts = $db->prepare('
			SELECT id, abr, y, q, n, date, inf 
			FROM '.$prefx.'_docs_ctlg 
			WHERE f IN ("con_arvon", "vinzare_proc", "vinzare_avans") 
			ORDER BY date DESC, id DESC 
			LIMIT 50
		');
		$pdo_contracts->execute();
		
		foreach ($pdo_contracts as $r) {
			// Parse contract info to get buyer name
			$buyer_name = '';
			if ($r['inf']) {
				$inf_parts = explode('&&', $r['inf']);
				foreach ($inf_parts as $part) {
					if (strpos($part, 'u_nm==') === 0) {
						$buyer_name = str_replace('u_nm==', '', $part);
						break;
					}
				}
			}
			
			// Generate contract number
			$contract_nr = $r['abr'] . $r['y'] . $r['q'] . '/' . $r['n'];
			$contract_date = date('d.m.Y', strtotime($r['date']));
			
			// Contract display text
			$display_text = $contract_nr . ' (' . $contract_date . ')';
			if ($buyer_name) {
				$display_text .= ' - ' . htmlspecialchars($buyer_name);
			}
			
			$selected = (isset($_POST['cont_nr']) && $_POST['cont_nr'] == $contract_nr) ? ' selected' : '';
			$contracts_options .= '<option value="'.$contract_nr.'" data-id="'.$r['id'].'"'.$selected.'>'.$display_text.'</option>';
		}
	} catch (Exception $e) {
		$contracts_options = '<option value="" disabled>Eroare la încărcarea contractelor</option>';
	}
	
	$rtrn .= ( isset($mixall)?'<form class="menu_annexa">':'' ).'
	<div class="ttl">Bazele datelor</div>
	<label class="lbl"><span class="ttl">Data</span><input class="need dt" type="date" name="date" min="1900-01-01" max="2099-12-31" title="Data" value="'.(isset($_POST['date']) ? $_POST['date'] : date('Y-m-d')).'" /></label>
	<label class="lbl"><span class="ttl">Numărul Annexa</span><input class="need" type="text" name="annexa_nr" title="Numărul Annexa" value="'.(isset($_POST['annexa_nr']) ? htmlspecialchars($_POST['annexa_nr']) : $annexa_number).'" /></label>
	
	<div class="ttl">Contractul</div>
	<label class="lbl max"><span class="ttl">Selectați contractul</span><select class="need" name="cont_nr" title="Selectați contractul" onchange="loadContractData()">
		<option value="" disabled'.(empty($_POST['cont_nr']) ? ' selected' : '').'>Selectați contractul...</option>
		'.$contracts_options.'
	</select></label>
	
	<div class="ttl">Auto (se va completa automat din contract)</div>
	<label class="lbl"><span class="ttl">Brand</span><select name="br" title="Brand">
		<option value="x" class="def" disabled selected>-</option>
		'.$br_html.'
	</select></label>
	<label class="lbl"><span class="ttl">Model</span><select name="mo" title="Model">
		<option value="x" data-br="" class="def" disabled selected>-</option>
		'.$mo_html.'
	</select></label>
	<label class="lbl"><span class="ttl">VIN code</span><input type="text" name="vin" title="VIN code" value="'.(isset($_POST['vin']) ? htmlspecialchars($_POST['vin']) : '').'" /></label>
	<label class="lbl"><span class="ttl">Price</span><input type="number" name="prc" title="Price" value="'.(isset($_POST['prc']) ? $_POST['prc'] : '').'" /></label>
	<label class="lbl"><span class="ttl">Currency</span><select name="cur" title="Currency">
		<option value="MDL"'.(isset($_POST['cur']) && $_POST['cur'] == 'MDL' ? ' selected' : (!isset($_POST['cur']) ? ' selected' : '')).'>MDL</option>
		<option value="EUR"'.(isset($_POST['cur']) && $_POST['cur'] == 'EUR' ? ' selected' : '').'>EUR</option>
	</select></label>
	
	<div class="ttl">Cumpărător (se va completa automat din contract)</div>
	<label class="lbl"><span class="ttl">Tip</span><select name="u_tp">
		<option value="fiz"'.(isset($_POST['u_tp']) && $_POST['u_tp'] == 'fiz' ? ' selected' : (!isset($_POST['u_tp']) ? ' selected' : '')).'>Fizic</option>
		<option value="jur"'.(isset($_POST['u_tp']) && $_POST['u_tp'] == 'jur' ? ' selected' : '').'>Juridic</option>
	</select></label>
	<label class="lbl"><span class="ttl">IDNO/CF</span><input type="text" name="u_cf_idno" title="IDNO/CF" value="'.(isset($_POST['u_cf_idno']) ? htmlspecialchars($_POST['u_cf_idno']) : '').'" /></label>
	<label class="lbl"><span class="ttl">Name</span><input type="text" name="u_nm" title="Name" value="'.(isset($_POST['u_nm']) ? htmlspecialchars($_POST['u_nm']) : '').'" /></label>
	<label class="lbl"><span class="ttl">Data nasterii</span><input type="text" name="u_tva_dt" title="Data nasterii" value="'.(isset($_POST['u_tva_dt']) ? htmlspecialchars($_POST['u_tva_dt']) : '').'" /></label>
	<label class="lbl"><span class="ttl">Data eliberării</span><input type="text" name="u_iban_dt_tk" title="Data eliberării" value="'.(isset($_POST['u_iban_dt_tk']) ? htmlspecialchars($_POST['u_iban_dt_tk']) : '').'" /></label>
	<label class="lbl max"><span class="ttl">Adresă</span><textarea name="u_adr" title="Adresă" rows="2">'.(isset($_POST['u_adr']) ? htmlspecialchars($_POST['u_adr']) : 'Republica Moldova, mun.Chișinău, or.Chișinău, str.').'</textarea></label>
	<label class="lbl"><span class="ttl">Phone</span><input type="text" name="u_phn" title="Phone" value="'.(isset($_POST['u_phn']) ? htmlspecialchars($_POST['u_phn']) : '+373').'" /></label>
	<label class="lbl"><span class="ttl">Email</span><input type="text" name="u_eml" title="Email" value="'.(isset($_POST['u_eml']) ? htmlspecialchars($_POST['u_eml']) : '').'" /></label>
	
	<div class="ttl">Opțiuni Cesionar</div>
	<label class="lbl">
		<span class="ttl">Adăugați Cesionar (terță parte)</span>
		<input type="checkbox" name="add_cesionar" value="1"'.(isset($_POST['add_cesionar']) && $_POST['add_cesionar'] == '1' ? ' checked' : '').' onchange="toggleCesionarFields(this)" />
	</label>
	
	<div id="cesionar_fields" style="display: '.(isset($_POST['add_cesionar']) && $_POST['add_cesionar'] == '1' ? 'block' : 'none').';">
		<div class="ttl">Cesionar (terță parte care primește dreptul de plată)</div>
		<label class="lbl"><span class="ttl">Tip</span><select name="cesionar_tp" class="cesionar-field">
			<option value="fiz"'.(isset($_POST['cesionar_tp']) && $_POST['cesionar_tp'] == 'fiz' ? ' selected' : (!isset($_POST['cesionar_tp']) ? ' selected' : '')).'>Fizic</option>
			<option value="jur"'.(isset($_POST['cesionar_tp']) && $_POST['cesionar_tp'] == 'jur' ? ' selected' : '').'>Juridic</option>
		</select></label>
		<label class="lbl"><span class="ttl">IDNO/CF</span><input type="text" name="cesionar_cf_idno" class="cesionar-field" title="Cesionar IDNO/CF" value="'.(isset($_POST['cesionar_cf_idno']) ? htmlspecialchars($_POST['cesionar_cf_idno']) : '').'" /></label>
		<label class="lbl"><span class="ttl">Name *</span><input type="text" name="cesionar_nm" class="cesionar-field cesionar-required" title="Cesionar Name" value="'.(isset($_POST['cesionar_nm']) ? htmlspecialchars($_POST['cesionar_nm']) : '').'" /></label>
		<label class="lbl"><span class="ttl">Data nasterii/TVA</span><input type="text" name="cesionar_tva_dt" class="cesionar-field" title="Cesionar Data nasterii" value="'.(isset($_POST['cesionar_tva_dt']) ? htmlspecialchars($_POST['cesionar_tva_dt']) : '').'" /></label>
		<label class="lbl"><span class="ttl">Data eliberării/IBAN</span><input type="text" name="cesionar_iban_dt_tk" class="cesionar-field" title="Cesionar Data eliberării" value="'.(isset($_POST['cesionar_iban_dt_tk']) ? htmlspecialchars($_POST['cesionar_iban_dt_tk']) : '').'" /></label>
		<label class="lbl"><span class="ttl">Cont bancar</span><input type="text" name="cesionar_account" class="cesionar-field" title="Cesionar Account" value="'.(isset($_POST['cesionar_account']) ? htmlspecialchars($_POST['cesionar_account']) : '').'" /></label>
		<label class="lbl max"><span class="ttl">Adresă</span><textarea name="cesionar_adr" class="cesionar-field" title="Cesionar Adresă" rows="2">'.(isset($_POST['cesionar_adr']) ? htmlspecialchars($_POST['cesionar_adr']) : 'Republica Moldova, mun.Chișinău, or.Chișinău, str.').'</textarea></label>
		<label class="lbl"><span class="ttl">Phone</span><input type="text" name="cesionar_phn" class="cesionar-field" title="Cesionar Phone" value="'.(isset($_POST['cesionar_phn']) ? htmlspecialchars($_POST['cesionar_phn']) : '+373').'" /></label>
		<label class="lbl"><span class="ttl">Email</span><input type="text" name="cesionar_eml" class="cesionar-field" title="Cesionar Email" value="'.(isset($_POST['cesionar_eml']) ? htmlspecialchars($_POST['cesionar_eml']) : '').'" /></label>
		<label class="lbl"><span class="ttl">Sumă cesiune * (în valuta contractului)</span><input type="number" name="cesionar_suma" class="cesionar-field cesionar-required" title="Sumă cesiune" value="'.(isset($_POST['cesionar_suma']) ? $_POST['cesionar_suma'] : '').'" step="0.01" min="0.01" /></label>
	</div>
	
	<script>
	function toggleCesionarFields(checkbox) {
		var fields = document.getElementById("cesionar_fields");
		var cesionarInputs = fields.querySelectorAll(".cesionar-field");
		
		if (checkbox.checked) {
			fields.style.display = "block";
			// Add validation classes to required fields
			cesionarInputs.forEach(function(input) {
				if (input.classList.contains("cesionar-required")) {
					input.classList.add("need");
				}
			});
		} else {
			fields.style.display = "none";
			// Remove validation classes
			cesionarInputs.forEach(function(input) {
				input.classList.remove("need");
			});
		}
	}
	
	// Validate Cesionar fields
	function validateCesionarFields() {
		var checkbox = document.querySelector(\'input[name="add_cesionar"]\');
		if (!checkbox.checked) return true;
		
		var nameField = document.querySelector(\'input[name="cesionar_nm"]\');
		var sumaField = document.querySelector(\'input[name="cesionar_suma"]\');
		
		var isValid = true;
		var errors = [];
		
		if (!nameField.value.trim()) {
			errors.push("Numele Cesionar este obligatoriu");
			nameField.style.borderColor = "red";
			isValid = false;
		} else {
			nameField.style.borderColor = "";
		}
		
		if (!sumaField.value || parseFloat(sumaField.value) <= 0) {
			errors.push("Suma cesiune trebuie să fie mai mare decât 0");
			sumaField.style.borderColor = "red";
			isValid = false;
		} else {
			sumaField.style.borderColor = "";
		}
		
		if (!isValid) {
			alert("Erori de validare:\\n" + errors.join("\\n"));
		}
		
		return isValid;
	}
	
	// Auto-fill buyer data when contract is selected
	function loadContractData() {
		var contractSelect = document.querySelector(\'select[name="cont_nr"]\');
		var contractNr = contractSelect.value;
		var contractId = contractSelect.options[contractSelect.selectedIndex].dataset.id;
		
		if (contractNr && contractId) {
			// AJAX call to load contract data
			var xhr = new XMLHttpRequest();
			xhr.open("POST", "/content/admin/ajax/docs/load_contract.php", true);
			xhr.setRequestHeader("Content-Type", "application/x-www-form-urlencoded");
			
			xhr.onreadystatechange = function() {
				if (xhr.readyState === 4 && xhr.status === 200) {
					try {
						var response = JSON.parse(xhr.responseText);
						if (response.success) {
							fillContractData(response.data);
						} else {
							console.log("Contract not found: " + contractNr);
						}
					} catch (e) {
						console.error("Error parsing response:", e);
					}
				}
			};
			
			xhr.send("contract_nr=" + encodeURIComponent(contractNr) + "&contract_id=" + encodeURIComponent(contractId));
		}
	}
	
	function fillContractData(data) {
		// Fill auto data
		if (data.br) document.querySelector(\'select[name="br"]\').value = data.br;
		if (data.mo) document.querySelector(\'select[name="mo"]\').value = data.mo;
		if (data.vin) document.querySelector(\'input[name="vin"]\').value = data.vin;
		if (data.prc) document.querySelector(\'input[name="prc"]\').value = data.prc;
		if (data.cur) document.querySelector(\'select[name="cur"]\').value = data.cur;
		
		// Fill buyer data
		if (data.u_tp) document.querySelector(\'select[name="u_tp"]\').value = data.u_tp;
		if (data.u_cf_idno) document.querySelector(\'input[name="u_cf_idno"]\').value = data.u_cf_idno;
		if (data.u_nm) document.querySelector(\'input[name="u_nm"]\').value = data.u_nm;
		if (data.u_tva_dt) document.querySelector(\'input[name="u_tva_dt"]\').value = data.u_tva_dt;
		if (data.u_iban_dt_tk) document.querySelector(\'input[name="u_iban_dt_tk"]\').value = data.u_iban_dt_tk;
		if (data.u_adr) document.querySelector(\'textarea[name="u_adr"]\').value = data.u_adr;
		if (data.u_phn) document.querySelector(\'input[name="u_phn"]\').value = data.u_phn;
		if (data.u_eml) document.querySelector(\'input[name="u_eml"]\').value = data.u_eml;
	}
	
	// Event listeners
	document.addEventListener(\'DOMContentLoaded\', function() {
		// Initialize cesionar fields state
		var checkbox = document.querySelector(\'input[name="add_cesionar"]\');
		if (checkbox) {
			toggleCesionarFields(checkbox);
		}
		
		// Add form validation before submit
		var form = document.querySelector(\'.menu_annexa\');
		if (form) {
			form.addEventListener(\'submit\', function(e) {
				if (!validateCesionarFields()) {
					e.preventDefault();
					return false;
				}
			});
		}
	});
	</script>
	
	'.( isset($mixall)?'</form>':'' );
}