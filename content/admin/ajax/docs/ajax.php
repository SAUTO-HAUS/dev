<?php defined( '_DOIT' ) or die( 'Restricted access' );

$ajax_folder = _ADM_AJAX.'/docs';
$rtrn = '';
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
	$inf_ar = ['br', 'mo', 'vin', 'yr', 'clr', 'prc', 'cur', 'prc_eur', 'prc_av', 'loc', 'term_livr', 'orig', 'cntr_fr', 'cntr_to', 'adr_to', 't2pay', 'plate', 'extras', 'u_nm', 'u_cf_idno', 'u_eur', 'cesionar_nm', 'cesionar_cf_idno', 'cesionar_suma', 'cont_nr', 'annexa_nr', 'add_cesionar', 'cesionar_account', 'base_contract_id', 'description', 'dealer', 'sauto_role', 'seller_name', 'seller_vat', 'seller_account', 'seller_address', 'seller_country', 'seller_swift', 'buyer_name', 'buyer_vat', 'buyer_account', 'buyer_address', 'buyer_country', 'buyer_swift', 'kyc_client_name', 'kyc_idnp', 'kyc_address', 'kyc_phone', 'kyc_email', 'kyc_completion_date', 'kyc_residence_addr', 'kyc_transaction_purpose', 'kyc_doc_type', 'kyc_doc_series', 'kyc_doc_office', 'kyc_doc_date', 'kyc_doc_expiry', 'kyc_citizenship', 'kyc_birth_info', 'kyc_occupation', 'kyc_occupation_other', 'kyc_institution_name', 'kyc_position', 'kyc_no_public_function', 'kyc_public_function', 'kyc_public_function_other', 'kyc_affiliated_company', 'kyc_parents_names', 'kyc_spouse_name', 'kyc_children_names', 'kyc_partner_name', 'kyc_transaction_purpose_other', 'kyc_money_source', 'kyc_money_source_other', 'kyc_approval_date', 'kyc_doc_buletin', 'kyc_doc_permis', 'kyc_doc_pasaport', 'kyc_occupation_angajat', 'kyc_occupation_student', 'kyc_occupation_antreprenor', 'kyc_occupation_somer', 'kyc_occupation_pensionar', 'kyc_public_function_deputat', 'kyc_public_function_judecator', 'kyc_public_function_guvern', 'kyc_public_function_primar', 'kyc_public_function_partid', 'kyc_public_function_consilier', 'kyc_transaction_personal', 'kyc_transaction_family', 'kyc_transaction_company', 'kyc_transaction_resale', 'kyc_transaction_commercial', 'kyc_transaction_transfer', 'kyc_funds_salary', 'kyc_funds_dividends', 'kyc_funds_loan', 'kyc_funds_business', 'kyc_funds_inheritance', 'kyc_funds_donations'];
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
	}
	
	if ( isset($_POST['inp']['pay_val']) && $_POST['inp']['pay_val']!='' ){//Paying values, dates
		$i=0; foreach ($_POST['inp']['pay_val'] as $k => $v){ if ($v!=''){ $inf .= ($i==0?($qu>0?'&&':'').'pays==':'||').( $v.'=>'.( isset($_POST['inp']['pay_date'][$k])?$_POST['inp']['pay_date'][$k]:'' ) ); $i++;} }
	}
	
	if ( isset($_POST['inp']['grnt_txt']) ){//Additional text Warranty
		$i=0; foreach ($_POST['inp']['grnt_txt'] as $v){ if ($v!=''){ $inf .= ($i==0?($qu>0?'&&':'').'grnt_txt==':'||').$v; $i++;} }
	}
	
	if ( isset($_POST['inp']['dmg_pos']) && isset($_POST['inp']['dmg_txt']) ){//Car damages POS / TXT
		$i=0; foreach ($_POST['inp']['dmg_pos'] as $k => $v){ $inf .= ($i==0?($qu>0?'&&':'').'dmg_pos==':'||').$v; $i++; }
		$i=0; foreach ($_POST['inp']['dmg_txt'] as $k => $v){ $inf .= ($i==0?($qu>0?'&&':'').'dmg_txt==':'||').($v!=''?$v:'0'); $i++; }
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


?>