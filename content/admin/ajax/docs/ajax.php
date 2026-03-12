<?php defined( '_DOIT' ) or die( 'Restricted access' );

$ajax_folder = _ADM_AJAX.'/docs';
$rtrn = '';
//---------------------------------------------GET USER FIELDS
if ( $_POST['fn']=='get_user_fields' ){
	$user_id = isset($_POST['user_id']) ? intval($_POST['user_id']) : 0;
	
	if ($user_id > 0) {
		$fields = [];
		
		// Get all contracts for this user
		$pdo = $db->prepare('SELECT `inf` FROM '.$prefx.'_docs_ctlg WHERE `u`=:user_id');
		$pdo->execute(['user_id' => $user_id]);
		
		foreach ($pdo as $r) {
			if ($r['inf'] != '') {
				$inf_parts = explode('&&', $r['inf']);
				foreach ($inf_parts as $part) {
					$kv = explode('==', $part);
					if (isset($kv[0]) && strpos($kv[0], 'u_') === 0 && strpos($kv[0], 'kyc_') === false) {
						$fields[$kv[0]] = true;
					}
				}
			}
		}
		
		$returnIt = ['fn' => $_POST['fn'], 'fields' => array_keys($fields)];
	} else {
		$returnIt = ['fn' => $_POST['fn'], 'error' => 'Invalid user ID'];
	}
}
//---------------------------------------------EDIT USER
if ( $_POST['fn']=='edit_user' ){
	$user_id = isset($_POST['inp']['user_id']) ? intval($_POST['inp']['user_id']) : 0;
	$tp = isset($_POST['inp']['tp']) ? $_POST['inp']['tp'] : 'fiz';
	$nm = isset($_POST['inp']['nm']) ? $_POST['inp']['nm'] : '';
	$cf_idno = isset($_POST['inp']['cf_idno']) ? $_POST['inp']['cf_idno'] : '';
	$tva_dt = isset($_POST['inp']['tva_dt']) ? $_POST['inp']['tva_dt'] : '';
	$iban_dt_tk = isset($_POST['inp']['iban_dt_tk']) ? $_POST['inp']['iban_dt_tk'] : '';
	$adr = isset($_POST['inp']['adr']) ? $_POST['inp']['adr'] : '';
	$phn = isset($_POST['inp']['phn']) ? $_POST['inp']['phn'] : '';
	$eml = isset($_POST['inp']['eml']) ? $_POST['inp']['eml'] : '';
	
	if ($user_id > 0) {
		$pdo = $db->prepare('UPDATE '.$prefx.'_docs_u SET `tp`=:tp, `nm`=:nm, `cf_idno`=:cf_idno, `tva_dt`=:tva_dt, `iban_dt_tk`=:iban_dt_tk, `adr`=:adr, `phn`=:phn, `eml`=:eml WHERE `id`=:id');
		$pdo->execute([ 'tp'=>$tp, 'nm'=>$nm, 'cf_idno'=>$cf_idno, 'tva_dt'=>$tva_dt, 'iban_dt_tk'=>$iban_dt_tk, 'adr'=>$adr, 'phn'=>$phn, 'eml'=>$eml, 'id'=>$user_id ]);
		$returnIt = [ 'fn'=>$_POST['fn'], 'success'=>true ];
	} else {
		$returnIt = [ 'fn'=>$_POST['fn'], 'error'=>'Invalid user ID' ];
	}
}
//---------------------------------------------SAVE AS PDF
if ( $_POST['fn']=='save_pdf' ){
	$returnIt = [ 'fn'=>$_POST['fn'], 'rtrn'=>$rtrn ];
}
//---------------------------------------------EDIT
if ( $_POST['fn']=='edit_sbmt' ){
	// Check user authentication before document editing
	if (!isset($_SESSION['user_id']) && !isset($_COOKIE['sess'])) {
		__log("Unauthorized document edit attempt from IP: " . myIp());
		$returnIt = [ 'fn'=>$_POST['fn'], 'error'=>'Unauthorized access' ];
		echo json_encode($returnIt);
		exit;
	}
	
	$doc_date = isset($_POST['inp']['date'])&&$_POST['inp']['date']!=''?date( 'Y-m-d', strtotime( $_POST['inp']['date'] ) ):date('Y-m-d');
	
	$u_id = isset($_POST['inp']['u_id'])&&$_POST['inp']['u_id']!=''?$_POST['inp']['u_id']:0;
	$u_tp = isset($_POST['inp']['u_tp'])&&$_POST['inp']['u_tp']!=''?$_POST['inp']['u_tp']:'x';
	$u_nm = isset($_POST['inp']['u_nm'])&&$_POST['inp']['u_nm']!=''?$_POST['inp']['u_nm']:'x';
	$u_cf_idno = isset($_POST['inp']['u_cf_idno'])&&$_POST['inp']['u_cf_idno']!=''?$_POST['inp']['u_cf_idno']:0;
	$u_tva_dt = isset($_POST['inp']['u_tva_dt'])&&$_POST['inp']['u_tva_dt']!=''?$_POST['inp']['u_tva_dt']:0;
	$u_iban_dt_tk = isset($_POST['inp']['u_iban_dt_tk'])&&$_POST['inp']['u_iban_dt_tk']!=''?$_POST['inp']['u_iban_dt_tk']:0;
	$u_adr = isset($_POST['inp']['u_adr'])&&$_POST['inp']['u_adr']!=''?$_POST['inp']['u_adr']:'';
	$u_phn = isset($_POST['inp']['u_phn'])&&$_POST['inp']['u_phn']!=''?$_POST['inp']['u_phn']:'';
	$u_eml = isset($_POST['inp']['u_eml'])&&$_POST['inp']['u_eml']!=''?$_POST['inp']['u_eml']:'';
	
	$it_id = isset($_POST['inp']['id'])&&$_POST['inp']['id']!=''?$_POST['inp']['id']:0;
	$it_cd = isset($_POST['inp']['vin'])&&$_POST['inp']['vin']!=''?( is_array($_POST['inp']['vin'])?'ar':strtoupper($_POST['inp']['vin']) ):'';
	
	$existing_inf_data = [];
	if ($it_id > 0) {
		$pdo_doc = $db->prepare('SELECT `inf` FROM '.$prefx.'_docs_ctlg WHERE `id`=:id LIMIT 1');
		$pdo_doc->execute(['id' => $it_id]);
		$existing_doc = $pdo_doc->fetch(PDO::FETCH_ASSOC);
		if ($existing_doc && $existing_doc['inf'] != '') {
			$inf_parts = explode('&&', $existing_doc['inf']);
			foreach ($inf_parts as $part) {
				if (strpos($part, '==') !== false) {
					list($key, $value) = explode('==', $part, 2);
					$existing_inf_data[$key] = $value;
				}
			}
		}
	}
	
	//GROUP BY `nm`
	$pdo = $db->prepare('SELECT * FROM '.$prefx.'_docs_u WHERE `cf_idno`=:cf_idno LIMIT 1 ');
	$pdo->execute([ 'cf_idno'=>$u_cf_idno ]);
	$existing_user = $pdo->fetch(PDO::FETCH_ASSOC);
	
	if (!$existing_user){// IF NOT FOUND - MAKE IT
		$pdo = $db->prepare('INSERT INTO '.$prefx.'_docs_u (`tp`, `nm`, `cf_idno`, `tva_dt`, `iban_dt_tk`, `adr`, `phn`, `eml`) VALUES (:tp, :nm, :cf_idno, :tva_dt, :iban_dt_tk, :adr, :phn, :eml) ');
		$pdo->execute([ 'tp'=>$u_tp, 'nm'=>$u_nm, 'cf_idno'=>$u_cf_idno, 'tva_dt'=>$u_tva_dt, 'iban_dt_tk'=>$u_iban_dt_tk, 'adr'=>$u_adr, 'phn'=>$u_phn, 'eml'=>$u_eml ]);
		$u_id = $db->lastInsertId();
	} else {
		// User exists - get the ID and update
		$u_id = $existing_user['id'];
		$pdo = $db->prepare('UPDATE '.$prefx.'_docs_u SET 
		`tp`=:tp, `nm`=:nm, `cf_idno`=:cf_idno, `tva_dt`=:tva_dt, `iban_dt_tk`=:iban_dt_tk, `adr`=:adr, `phn`=:phn, `eml`=:eml
		WHERE `id`=:id');
		$pdo->execute([ 'tp'=>$u_tp, 'nm'=>$u_nm, 'cf_idno'=>$u_cf_idno, 'tva_dt'=>$u_tva_dt, 'iban_dt_tk'=>$u_iban_dt_tk, 'adr'=>$u_adr, 'phn'=>$u_phn, 'eml'=>$u_eml, 'id'=>$u_id ]);
	}
	
	//__________________INFO generator
	$inf=''; $qu=0;
	$inf_ar = ['br', 'mo', 'vin', 'yr', 'clr', 'prc', 'cur', 'prc_eur', 'prc_av', 'loc', 'term_livr', 'orig', 'cntr_fr', 'cntr_to', 'adr_to', 't2pay', 'plate', 'extras', 'u_nm', 'u_cf_idno', 'u_eur', 'cesionar_nm', 'cesionar_cf_idno', 'cesionar_suma', 'cont_nr', 'annexa_nr', 'add_cesionar', 'cesionar_account', 'base_contract_id', 'sofer', 'autovehicul', 'fp_daa', 'description', 'dealer', 'sauto_role', 'seller_name', 'seller_vat', 'seller_account', 'seller_address', 'seller_country', 'seller_swift', 'buyer_name', 'buyer_vat', 'buyer_account', 'buyer_address', 'buyer_country', 'buyer_swift', 'vca_contract_id', 'vca_contract_nr', 'vca_date', 'vca_amount', 'vcs_contract_id', 'vcs_contract_nr', 'vcs_date', 'vcs_amount', 'compensation_amount', 'kyc_client_name', 'kyc_idnp', 'kyc_address', 'kyc_phone', 'kyc_email', 'kyc_completion_date', 'kyc_residence_addr', 'kyc_transaction_purpose', 'kyc_doc_type', 'kyc_doc_series', 'kyc_doc_office', 'kyc_doc_date', 'kyc_doc_expiry', 'kyc_citizenship', 'kyc_birth_info', 'kyc_occupation', 'kyc_occupation_other', 'kyc_institution_name', 'kyc_position', 'kyc_no_public_function', 'kyc_public_function', 'kyc_public_function_other', 'kyc_affiliated_company', 'kyc_parents_names', 'kyc_spouse_name', 'kyc_children_names', 'kyc_partner_name', 'kyc_transaction_purpose_other', 'kyc_money_source', 'kyc_money_source_other', 'kyc_approval_date', 'kyc_doc_buletin', 'kyc_doc_permis', 'kyc_doc_pasaport', 'kyc_occupation_angajat', 'kyc_occupation_student', 'kyc_occupation_antreprenor', 'kyc_occupation_somer', 'kyc_occupation_pensionar', 'kyc_public_function_deputat', 'kyc_public_function_judecator', 'kyc_public_function_guvern', 'kyc_public_function_primar', 'kyc_public_function_partid', 'kyc_public_function_consilier', 'kyc_transaction_personal', 'kyc_transaction_family', 'kyc_transaction_company', 'kyc_transaction_resale', 'kyc_transaction_commercial', 'kyc_transaction_transfer', 'kyc_funds_salary', 'kyc_funds_dividends', 'kyc_funds_loan', 'kyc_funds_business', 'kyc_funds_inheritance', 'kyc_funds_donations'];
	$inf_up_ar = ['vin'];
	
	// KYC checkbox fields that need to save both checked (1) and unchecked (0) states
	$kyc_checkbox_fields = ['kyc_doc_buletin', 'kyc_doc_permis', 'kyc_doc_pasaport', 'kyc_occupation_angajat', 'kyc_occupation_student', 'kyc_occupation_antreprenor', 'kyc_occupation_somer', 'kyc_occupation_pensionar', 'kyc_no_public_function', 'kyc_public_function_deputat', 'kyc_public_function_judecator', 'kyc_public_function_guvern', 'kyc_public_function_primar', 'kyc_public_function_partid', 'kyc_public_function_consilier', 'kyc_transaction_personal', 'kyc_transaction_family', 'kyc_transaction_company', 'kyc_transaction_resale', 'kyc_transaction_commercial', 'kyc_transaction_transfer', 'kyc_funds_salary', 'kyc_funds_dividends', 'kyc_funds_loan', 'kyc_funds_business', 'kyc_funds_inheritance', 'kyc_funds_donations'];
	$kyc_default_checked_fields = ['kyc_doc_buletin', 'kyc_no_public_function', 'kyc_transaction_personal', 'kyc_funds_salary'];
	
	// Numeric fields that should always be saved even if 0 or empty
	$numeric_fields = ['u_eur'];
	
	foreach ($inf_ar as $k => $v){
		// Special handling for KYC checkbox fields - save both checked and unchecked states
		if ( in_array($v, $kyc_checkbox_fields) ) {
			$checkbox_value = (isset($_POST['inp'][$v]) && $_POST['inp'][$v] == '1') ? '1' : '0';
			$inf .= ($qu>0?'&&':'').$v.'=='.$checkbox_value;
			$qu++;
		}
		// Special handling for numeric fields - save even if 0 or empty
		elseif ( in_array($v, $numeric_fields) && isset($_POST['inp'][$v]) ) {
			$inf .= ($qu>0?'&&':'').$v.'=='.$_POST['inp'][$v];
			$qu++;
		}
		// Regular handling for other fields - only save if not empty
		elseif ( isset($_POST['inp'][$v])&&$_POST['inp'][$v]!='' ){
			if ( is_array($_POST['inp'][$v]) ){
				$inf .= ($qu>0?'&&':'').$v.'==';
				foreach ($_POST['inp'][$v] as $k2 => $v2){
					if ( in_array($v, $inf_up_ar) ){ $v2 = strtoupper($v2); }
					$inf .= ($k2>0?'||':'').$v2;
				}
			} else { 
				if ( in_array($v, $inf_up_ar) ){ $_POST['inp'][$v] = strtoupper($_POST['inp'][$v]); }
				$inf .= ($qu>0?'&&':'').$v.'=='.$_POST['inp'][$v];
			}
			$qu++;
		}

		elseif ( isset($existing_inf_data[$v]) && $existing_inf_data[$v] != '' ) {
			$inf .= ($qu>0?'&&':'').$v.'=='.$existing_inf_data[$v];
			$qu++;
		}
	}
	
	if ( isset($_POST['inp']['pay_val']) && $_POST['inp']['pay_val']!='' ){//Paying values, dates
		$i=0; foreach ($_POST['inp']['pay_val'] as $k => $v){ if ($v!=''){ $inf .= ($i==0?($qu>0?'&&':'').'pays==':'||').( $v.'=>'.( isset($_POST['inp']['pay_date'][$k])?$_POST['inp']['pay_date'][$k]:'' ) ); $i++;} }
	} elseif ( isset($existing_inf_data['pays']) ) {
		$inf .= ($qu>0?'&&':'').'pays=='.$existing_inf_data['pays'];
	}
	
	if ( isset($_POST['inp']['grnt_txt']) ){//Additional text Warranty
		$i=0; foreach ($_POST['inp']['grnt_txt'] as $v){ if ($v!=''){ $inf .= ($i==0?($qu>0?'&&':'').'grnt_txt==':'||').$v; $i++;} }
	} elseif ( isset($existing_inf_data['grnt_txt']) ) {
		$inf .= ($qu>0?'&&':'').'grnt_txt=='.$existing_inf_data['grnt_txt'];
	}
	
	if ( isset($_POST['inp']['dmg_pos']) && isset($_POST['inp']['dmg_txt']) ){//Car damages POS / TXT
		$i=0; foreach ($_POST['inp']['dmg_pos'] as $k => $v){ $inf .= ($i==0?($qu>0?'&&':'').'dmg_pos==':'||').$v; $i++; }
		$i=0; foreach ($_POST['inp']['dmg_txt'] as $k => $v){ $inf .= ($i==0?($qu>0?'&&':'').'dmg_txt==':'||').($v!=''?$v:'0'); $i++; }
	} elseif ( isset($existing_inf_data['dmg_pos']) && isset($existing_inf_data['dmg_txt']) ) {
		$inf .= ($qu>0?'&&':'').'dmg_pos=='.$existing_inf_data['dmg_pos'];
		$inf .= '&&dmg_txt=='.$existing_inf_data['dmg_txt'];
	}
	
	unset($inf_ar, $qu);
	//__________________
	
	//UPDATE INFO CTLG
	$last_edited_by = isset($_SESSION) && isset($_SESSION['user_id']) && $_SESSION['user_id'] !== '' ? $_SESSION['user_id'] : ( $_COOKIE['usr_id'] ?? 0 );

    // Ensure we have a valid user ID
	if ($u_id == 0 && $u_cf_idno != 0) {
		$pdo = $db->prepare('SELECT `id` FROM '.$prefx.'_docs_u WHERE `cf_idno`=:cf_idno LIMIT 1');
		$pdo->execute(['cf_idno' => $u_cf_idno]);
		$user_check = $pdo->fetch(PDO::FETCH_ASSOC);
		if ($user_check) {
			$u_id = $user_check['id'];
		}
	}

	$pdo = $db->prepare('UPDATE '.$prefx.'_docs_ctlg SET `cd`=:cd, `inf`=:inf, `u`=:u, `date`=:date, `last_edited_by`=:last_edited_by WHERE `id`=:id');
	$pdo->execute([ 'cd'=>$it_cd, 'inf'=>$inf, 'u'=>$u_id, 'date'=>$doc_date, 'last_edited_by'=>$last_edited_by, 'id'=>$it_id ]);
	
	$returnIt = [ 'fn'=>$_POST['fn'], 'rtrn'=>$rtrn ];
}
//---------------------------------------------DELETE ITEM
elseif ( $_POST['fn']=='del_it' ){
	$pdo = $db->prepare('DELETE FROM '.$prefx.'_docs_ctlg WHERE `id`=:id AND `f`=:f AND `gr`=:gr'); $pdo->execute([ 'id'=>$_POST['id'], 'f'=>$_POST['doc'], 'gr'=>$_POST['gr'] ]);
	$returnIt = [ 'fn'=>$_POST['fn'] ];
}
//---------------------------------------------SEARCH DOCS (AJAX)
elseif ( $_POST['fn']=='search_docs' ){
	$search = isset($_POST['q']) ? trim($_POST['q']) : '';
	$loaded_ids = isset($_POST['loaded_ids']) ? $_POST['loaded_ids'] : [];
	
	if ($search !== '' && mb_strlen($search) >= 2) {
		// Normalize search term
		$searchNorm = mb_strtolower($search, 'UTF-8');
		$searchNorm = strtr($searchNorm, ['ă'=>'a', 'â'=>'a', 'î'=>'i', 'ș'=>'s', 'ț'=>'t', '_'=>' ']);
		
		// Get admin users for display
		$adm_ar = [];
		$pdo_adm = $db->prepare('SELECT * FROM '.$prefx.'_adm_usr ORDER BY `id` ASC'); $pdo_adm->execute();
		foreach ($pdo_adm as $ra){ $adm_ar[ $ra['id'] ] = $ra['name']; }
		
		// Search across ALL documents with LIKE - covers all visible columns
		$likeTerm = '%'.$search.'%';
		
		$params = ['s1'=>$likeTerm, 's2'=>$likeTerm, 's3'=>$likeTerm, 's4'=>$likeTerm, 's5'=>$likeTerm, 's6'=>$likeTerm];
		
		$pdo = $db->prepare('SELECT 
			u.id u_id, u.nm u_nm, u.tp u_tp, u.cf_idno u_cf_idno, u.tva_dt u_tva_dt, u.iban_dt_tk u_iban_dt_tk, u.adr u_adr, u.phn u_phn, u.eml u_eml, 
			c.*, 
			c.last_edited_by
			FROM 
				'.$prefx.'_docs_u AS u 
				INNER JOIN 
				'.$prefx.'_docs_ctlg AS c 
			ON u.id=c.u 
			WHERE LOWER(u.nm) LIKE :s1 
			   OR LOWER(u.cf_idno) LIKE :s2 
			   OR LOWER(c.inf) LIKE :s3 
			   OR c.n LIKE :s4
			   OR LOWER(c.f) LIKE :s5
			   OR c.date LIKE :s6
			ORDER BY c.date DESC, c.id DESC
			LIMIT 200');
		$pdo->execute($params);
		
		$results = [];
		$i = 0;
		foreach ($pdo as $r){
			// Skip already loaded docs
			if (is_array($loaded_ids) && in_array($r['id'], $loaded_ids)) { continue; }
			
			$inf = [];
			if ( $r['inf']!='' ){
				foreach ( explode('&&', $r['inf']) as $v){
					$tmp = explode('==', $v);
					if ( isset($tmp[1]) ){ $inf[ $tmp[0] ] = $tmp[1]; }
				}
			}
			
			$br_mo_vin = '';
			if ( isset($inf['br']) && strpos($inf['br'], '||') !== false && strpos($inf['mo'], '||') !== false ){
				$br_ar = explode('||', $inf['br']); $mo_ar = explode('||', $inf['mo']); if ( strpos($inf['vin'], '||') !== false ){ $vin_ar = explode('||', $inf['vin']); }
				foreach ($br_ar as $k => $v){
					if ( isset($mo_ar[$k]) ){
						$br_mo_vin .= ($k>0?', ':'').ucwords(strtolower(str_replace('_', ' ', $v))).' '.ucwords(str_replace('_', ' ', $mo_ar[$k])).( isset($vin_ar[$k])?'['.$vin_ar[$k].']':'' );
					}
				}
			} else {
				$br_formatted = isset($inf['br']) ? ucwords(strtolower(str_replace('_', ' ', $inf['br']))) : '';
				$mo_formatted = isset($inf['mo']) ? ucwords(str_replace('_', ' ', $inf['mo'])) : '';
				$br_mo_vin .= '<span class="br">'.$br_formatted.'</span> <span class="mo">'.$mo_formatted.'</span> <span class="vin">'.(isset($inf['vin'])?'['.$inf['vin'].']':'').'</span>';
			}
			// Build tags for client-side filtering
			$tags = strtr(mb_strtolower( $r['u_nm'].' '.$r['u_cf_idno'].' '.$r['u_tp'].' '.$r['abr'].$r['y'].$r['q'].'/'.$r['n'].' '.( isset($inf['br'])?$inf['br']:'' ).' '.( isset($inf['mo'])?$inf['mo']:'' ).' '.( isset($inf['vin'])?$inf['vin']:'' ).' '.( isset($inf['prc'])?$inf['prc']:'' ).' '.( isset($inf['plate'])?$inf['plate']:'' ).' '.( isset($inf['sofer'])?$inf['sofer']:'' ).' '.( isset($inf['autovehicul'])?$inf['autovehicul']:'' ).' '.date( 'd.m.Y', strtotime( $r['date'] ) ).' '.$r['f'], 'UTF-8' ), ['ă'=>'a', 'â'=>'a', 'î'=>'i', 'ș'=>'s', 'ț'=>'t', '_'=>' ']);

			// Check if any word in tags starts with search term
			$tagWords = preg_split('/\s+/', $tags);
			$tagMatch = false;
			foreach ($tagWords as $tw) { if (strpos($tw, $searchNorm) === 0) { $tagMatch = true; break; } }
			if (!$tagMatch) { continue; }

			$results[] = [
				'id' => $r['id'],
				'tags' => $tags,
				'year' => date('Y', strtotime($r['date'])),
				'html' => '<label class="bx '.( $i%2>0?'odd':'even' ).' ajax-result" data-id="'.$r['id'].'" data-u_id="'.$r['u_id'].'" data-tags="'.$tags.'">
						<div class="values"
							data-id="'.$r['id'].'" data-doc="'.$r['f'].'" data-gr="'.$r['gr'].'"
							data-cont_y="'.$r['y'].'" data-cont_q="'.$r['q'].'" data-cont_n="'.$r['n'].'" 
							data-u_id="'.$r['u_id'].'" data-u_cf_idno="'.$r['u_cf_idno'].'" data-u_nm="'.$r['u_nm'].'" data-date="'.$r['date'].'" 
							data-u_tva_dt="'.( $r['u_tp']=='fiz'&&strtotime($r['u_tva_dt'])!==false?date('Y-m-d',strtotime($r['u_tva_dt'])):$r['u_tva_dt'] ).'" 
							data-u_iban_dt_tk="'.( $r['u_tp']=='fiz'&&strtotime($r['u_iban_dt_tk'])!==false?date('Y-m-d',strtotime($r['u_iban_dt_tk'])):$r['u_iban_dt_tk'] ).'" 
							data-u_adr="'.$r['u_adr'].'" data-u_phn="'.$r['u_phn'].'" data-u_eml="'.$r['u_eml'].'"
							'.(isset($inf['cntr_fr'])?'data-cntr_fr="'.$inf['cntr_fr'].'"':'').' '.(isset($inf['cntr_to'])?'data-cntr_to="'.$inf['cntr_to'].'"':'').' '.(isset($inf['adr_to'])?'data-adr_to="'.$inf['adr_to'].'"':'').'
							'.(isset($inf['t2pay'])?'data-t2pay="'.$inf['t2pay'].'"':'').' '.(isset($inf['plate'])?'data-plate="'.$inf['plate'].'"':'').'
							'.(isset($inf['vin'])?'data-vin="'.$inf['vin'].'"':'').' '.(isset($inf['mo'])?'data-mo="'.$inf['mo'].'"':'').' '.(isset($inf['br'])?'data-br="'.$inf['br'].'"':'').'
							'.(isset($inf['prc'])?'data-prc="'.$inf['prc'].'"':'').' '.(isset($inf['prc_eur'])?'data-prc_eur="'.$inf['prc_eur'].'"':'').' '.(isset($inf['term_livr'])?'data-term_livr="'.$inf['term_livr'].'"':'').' 
							'.(isset($inf['yr'])?'data-yr="'.$inf['yr'].'"':'').' '.(isset($inf['clr'])?'data-clr="'.$inf['clr'].'"':'').' '.(isset($inf['loc'])?'data-loc="'.$inf['loc'].'"':'').' '.(isset($inf['u_eur'])?'data-u_eur="'.$inf['u_eur'].'"':'').' 
							'.(isset($inf['pays'])?'data-pays="'.$inf['pays'].'"':'').' '.(isset($inf['grnt_txt'])?'data-grnt_txt="'.$inf['grnt_txt'].'"':'').'
							'.(isset($inf['extras'])?'data-extras="'.$inf['extras'].'"':'').' '.(isset($inf['dmg_pos'])?'data-dmg_pos="'.$inf['dmg_pos'].'"':'').' '.(isset($inf['dmg_txt'])?'data-dmg_txt="'.$inf['dmg_txt'].'"':'').'
							'.(isset($inf['orig'])?'data-orig="'.$inf['orig'].'"':'').' 
							'.(isset($inf['cur'])?'data-cur="'.$inf['cur'].'"':'').' '.(isset($inf['description'])?'data-description="'.htmlspecialchars($inf['description']).'"':'').' '.(isset($inf['dealer'])?'data-dealer="'.htmlspecialchars($inf['dealer']).'"':'').'
							'.(isset($inf['sauto_role'])?'data-sauto_role="'.$inf['sauto_role'].'"':'').'
							'.(isset($inf['seller_name'])?'data-seller_name="'.htmlspecialchars($inf['seller_name']).'"':'').' '.(isset($inf['seller_vat'])?'data-seller_vat="'.htmlspecialchars($inf['seller_vat']).'"':'').' '.(isset($inf['seller_account'])?'data-seller_account="'.htmlspecialchars($inf['seller_account']).'"':'').'
							'.(isset($inf['seller_address'])?'data-seller_address="'.htmlspecialchars($inf['seller_address']).'"':'').' '.(isset($inf['seller_country'])?'data-seller_country="'.htmlspecialchars($inf['seller_country']).'"':'').' '.(isset($inf['seller_swift'])?'data-seller_swift="'.htmlspecialchars($inf['seller_swift']).'"':'').'
							'.(isset($inf['buyer_name'])?'data-buyer_name="'.htmlspecialchars($inf['buyer_name']).'"':'').' '.(isset($inf['buyer_vat'])?'data-buyer_vat="'.htmlspecialchars($inf['buyer_vat']).'"':'').' '.(isset($inf['buyer_account'])?'data-buyer_account="'.htmlspecialchars($inf['buyer_account']).'"':'').'
							'.(isset($inf['buyer_address'])?'data-buyer_address="'.htmlspecialchars($inf['buyer_address']).'"':'').' '.(isset($inf['buyer_country'])?'data-buyer_country="'.htmlspecialchars($inf['buyer_country']).'"':'').' '.(isset($inf['buyer_swift'])?'data-buyer_swift="'.htmlspecialchars($inf['buyer_swift']).'"':'').' 
							'.(isset($inf['add_cesionar'])?'data-add_cesionar="'.$inf['add_cesionar'].'"':'').' '.(isset($inf['cesionar_account'])?'data-cesionar_account="'.htmlspecialchars($inf['cesionar_account']).'"':'').' '.(isset($inf['cesionar_nm'])?'data-cesionar_nm="'.htmlspecialchars($inf['cesionar_nm']).'"':'').' '.(isset($inf['cesionar_cf_idno'])?'data-cesionar_cf_idno="'.htmlspecialchars($inf['cesionar_cf_idno']).'"':'').' '.(isset($inf['cesionar_suma'])?'data-cesionar_suma="'.$inf['cesionar_suma'].'"':'').' '.(isset($inf['base_contract_id'])?'data-base_contract_id="'.$inf['base_contract_id'].'"':'').' 
							'.(isset($inf['vca_contract_id'])?'data-vca_contract_id="'.$inf['vca_contract_id'].'"':'').' '.(isset($inf['vca_contract_nr'])?'data-vca_contract_nr="'.htmlspecialchars($inf['vca_contract_nr']).'"':'').' '.(isset($inf['vca_date'])?'data-vca_date="'.$inf['vca_date'].'"':'').' '.(isset($inf['vca_amount'])?'data-vca_amount="'.$inf['vca_amount'].'"':'').' 
							'.(isset($inf['vcs_contract_id'])?'data-vcs_contract_id="'.$inf['vcs_contract_id'].'"':'').' '.(isset($inf['vcs_contract_nr'])?'data-vcs_contract_nr="'.htmlspecialchars($inf['vcs_contract_nr']).'"':'').' '.(isset($inf['vcs_date'])?'data-vcs_date="'.$inf['vcs_date'].'"':'').' '.(isset($inf['vcs_amount'])?'data-vcs_amount="'.$inf['vcs_amount'].'"':'').' '.(isset($inf['compensation_amount'])?'data-compensation_amount="'.$inf['compensation_amount'].'"':'').' 
							'.(isset($inf['sofer'])?'data-sofer="'.htmlspecialchars($inf['sofer']).'"':'').' '.(isset($inf['autovehicul'])?'data-autovehicul="'.htmlspecialchars($inf['autovehicul']).'"':'').'
							'.(isset($inf['kyc_doc_buletin'])?'data-kyc_doc_buletin="'.$inf['kyc_doc_buletin'].'"':'').' '.(isset($inf['kyc_doc_permis'])?'data-kyc_doc_permis="'.$inf['kyc_doc_permis'].'"':'').' '.(isset($inf['kyc_doc_pasaport'])?'data-kyc_doc_pasaport="'.$inf['kyc_doc_pasaport'].'"':'').'
							'.(isset($inf['kyc_occupation_angajat'])?'data-kyc_occupation_angajat="'.$inf['kyc_occupation_angajat'].'"':'').' '.(isset($inf['kyc_occupation_student'])?'data-kyc_occupation_student="'.$inf['kyc_occupation_student'].'"':'').' '.(isset($inf['kyc_occupation_antreprenor'])?'data-kyc_occupation_antreprenor="'.$inf['kyc_occupation_antreprenor'].'"':'').' '.(isset($inf['kyc_occupation_somer'])?'data-kyc_occupation_somer="'.$inf['kyc_occupation_somer'].'"':'').' '.(isset($inf['kyc_occupation_pensionar'])?'data-kyc_occupation_pensionar="'.$inf['kyc_occupation_pensionar'].'"':'').'
							'.(isset($inf['kyc_no_public_function'])?'data-kyc_no_public_function="'.$inf['kyc_no_public_function'].'"':'').' '.(isset($inf['kyc_public_function_deputat'])?'data-kyc_public_function_deputat="'.$inf['kyc_public_function_deputat'].'"':'').' '.(isset($inf['kyc_public_function_judecator'])?'data-kyc_public_function_judecator="'.$inf['kyc_public_function_judecator'].'"':'').' '.(isset($inf['kyc_public_function_guvern'])?'data-kyc_public_function_guvern="'.$inf['kyc_public_function_guvern'].'"':'').' '.(isset($inf['kyc_public_function_primar'])?'data-kyc_public_function_primar="'.$inf['kyc_public_function_primar'].'"':'').' '.(isset($inf['kyc_public_function_partid'])?'data-kyc_public_function_partid="'.$inf['kyc_public_function_partid'].'"':'').' '.(isset($inf['kyc_public_function_consilier'])?'data-kyc_public_function_consilier="'.$inf['kyc_public_function_consilier'].'"':'').'
							'.(isset($inf['kyc_transaction_personal'])?'data-kyc_transaction_personal="'.$inf['kyc_transaction_personal'].'"':'').' '.(isset($inf['kyc_transaction_family'])?'data-kyc_transaction_family="'.$inf['kyc_transaction_family'].'"':'').' '.(isset($inf['kyc_transaction_company'])?'data-kyc_transaction_company="'.$inf['kyc_transaction_company'].'"':'').' '.(isset($inf['kyc_transaction_resale'])?'data-kyc_transaction_resale="'.$inf['kyc_transaction_resale'].'"':'').' '.(isset($inf['kyc_transaction_commercial'])?'data-kyc_transaction_commercial="'.$inf['kyc_transaction_commercial'].'"':'').' '.(isset($inf['kyc_transaction_transfer'])?'data-kyc_transaction_transfer="'.$inf['kyc_transaction_transfer'].'"':'').'
							'.(isset($inf['kyc_funds_salary'])?'data-kyc_funds_salary="'.$inf['kyc_funds_salary'].'"':'').' '.(isset($inf['kyc_funds_dividends'])?'data-kyc_funds_dividends="'.$inf['kyc_funds_dividends'].'"':'').' '.(isset($inf['kyc_funds_loan'])?'data-kyc_funds_loan="'.$inf['kyc_funds_loan'].'"':'').' '.(isset($inf['kyc_funds_business'])?'data-kyc_funds_business="'.$inf['kyc_funds_business'].'"':'').' '.(isset($inf['kyc_funds_inheritance'])?'data-kyc_funds_inheritance="'.$inf['kyc_funds_inheritance'].'"':'').' '.(isset($inf['kyc_funds_donations'])?'data-kyc_funds_donations="'.$inf['kyc_funds_donations'].'"':'').'
							data-u_tp="'.$r['u_tp'].'" 
							data-adm="'.$r['adm'].'" data-last_edited_by="'.($r['last_edited_by'] ?? $r['adm']).'"
						></div>
						<div class="rowz info">
							<div class="col"><span class="date">'.date( 'd.m.y', strtotime( $r['date'] ) ).'</span></div>
							<div class="col">'.( strtr(mb_convert_case($r['f'], MB_CASE_TITLE, 'UTF-8'), ['_'=>' ']) ).'</div>
							<div class="col">'.$r['abr'].$r['y'].$r['q'].'/'.$r['n'].'</div>
							<div class="col" data-id="'.$r['u_id'].'" data-tp="'.$r['u_tp'].'">'.( in_array($r['f'], ['foaie_parcurs','foaie_parcurs_cars']) ? '<span class="u_nm">'.(isset($inf['sofer'])?$inf['sofer']:'').'</span>' : '<span class="u_nm">'.mb_convert_case($r['u_nm'], MB_CASE_TITLE, 'UTF-8').'</span> <span class="u_cf_idno">'.$r['u_cf_idno'].'</span>' ).'</div>
							<div class="col"><span class="prc">'.( isset($inf['prc'])?$inf['prc']:'-' ).'</span></div>
							<div class="col">'.( $r['f']=='foaie_parcurs' ? (isset($inf['autovehicul'])?$inf['autovehicul']:'') : ($r['f']=='foaie_parcurs_cars' ? $br_mo_vin.( isset($inf['plate'])?' ['.$inf['plate'].']':'' ) : $br_mo_vin) ).'</div>
							<div class="col">'.($r['f']=='foaie_parcurs' ? (isset($inf['sofer'])?$inf['sofer']:'').', '.(isset($inf['autovehicul'])?$inf['autovehicul']:'') : ($r['f']=='foaie_parcurs_cars' ? (isset($inf['sofer'])?$inf['sofer']:'').', '.(isset($inf['plate'])?$inf['plate']:'') : $br_mo_vin)).'</div>
							<div class="col">'.( isset($adm_ar[ $r['adm'] ])?$adm_ar[ $r['adm'] ]:$r['adm'] ).'</div>
						</div>
						<input type="radio" name="btns_act" class="none">
						<div class="btns">
							<div class="btn show" data-fn="show_it">Vizualiza</div>
							<div class="btn pdf" data-fn="save_pdf">PDF
								 <input type="checkbox" name="stamp" title="Stampila" style="accent-color:#e2001a;" />
								 <input type="checkbox" name="usr_stamp" title="Stampila client" style="accent-color:#e2001a;">
							</div>
							<div class="btn print" data-fn="print_it">Print
								 <input type="checkbox" name="stamp" title="Stampila" style="accent-color:#e2001a;" />
								 <input type="checkbox" name="usr_stamp" title="Stampila client" style="accent-color:#e2001a;">
							</div>
							<div class="btn edit" data-fn="edit_it">Edit</div>
							<div class="btn del" data-fn="del_it">Delete</div>
						</div>
					</label>'
			];
			$i++;
		}
		
		$returnIt = [ 'fn'=>$_POST['fn'], 'results'=>$results, 'count'=>count($results) ];
	} else {
		$returnIt = [ 'fn'=>$_POST['fn'], 'results'=>[], 'count'=>0 ];
	}
}
?>