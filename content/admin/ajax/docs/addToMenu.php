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
	
	<div id="cesionar_section">'
		<label class="lbl"><span class="ttl">Nume cesionar</span><input class="cesionar-field" type="text" name="cesionar_nm" title="Nume cesionar" value="'.(isset($_POST['cesionar_nm']) ? htmlspecialchars($_POST['cesionar_nm']) : '').'" /></label>
		<label class="lbl"><span class="ttl">IDNO/CF cesionar</span><input class="cesionar-field" type="text" name="cesionar_cf_idno" title="IDNO/CF cesionar" value="'.(isset($_POST['cesionar_cf_idno']) ? htmlspecialchars($_POST['cesionar_cf_idno']) : '').'" /></label>
		<label class="lbl"><span class="ttl">Cont bancar cesionar</span><input class="cesionar-field" type="text" name="cesionar_account" title="Cont bancar cesionar" value="'.(isset($_POST['cesionar_account']) ? htmlspecialchars($_POST['cesionar_account']) : '').'" /></label>
		<label class="lbl"><span class="ttl">Sumă cesiune * (în valuta contractului)</span><input class="need cesionar-field" type="number" name="cesionar_suma" title="Sumă cesiune" value="'.(isset($_POST['cesionar_suma']) ? $_POST['cesionar_suma'] : '').'" step="0.01" min="0.01" /></label>
	</div>
{{ ... }}
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