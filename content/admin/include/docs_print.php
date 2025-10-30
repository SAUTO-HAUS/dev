<?php 
include_once($_SERVER['DOCUMENT_ROOT'].'/environment.php');

if ( isset($_POST['doc_f']) && file_exists(__DIR__.'/docs/'.$_POST['doc_gr'].'/'.$_POST['doc_f'].'.php') ){
	define('_DOIT', 1); define('_DEFAULT', $_SERVER['DOCUMENT_ROOT'].'/content/default');

	require_once (_DEFAULT.'/defines.php');
	require_once (_DEFAULT.'/functions.php');
	require_once (_DEFAULT.'/config.php');
	require (_DEFAULT.'/dbi.php');
	require_once (_DEFAULT.'/language.php');
	require_once (_DEFAULT.'/arrays.php');
	
	// Include App classes for PhoneHelper
	require_once ($_SERVER['DOCUMENT_ROOT'].'/App/Core/Container.php');
	require_once ($_SERVER['DOCUMENT_ROOT'].'/App/Helper/PhoneHelper.php');
	require_once ($_SERVER['DOCUMENT_ROOT'].'/App/Services/PhoneReplacementService.php');
	
	// Initialize Container with database connection
	App\Core\Container::set('db', $db);
	App\Core\Container::set('prefix', $prefx);
	
	$z_site = 'https://www.sauto.md';
	$abr = '';
	
	/*$zcont = '
	<b>“SAUTO” SRL</b><br/>
	<span>Republica Moldova, MD-2084, mun.Chişinau</span><br/>
	<span>or.Cricova, str.Chisinaului 84, ap.(of.) 39</span><br/>
	<span>IBAN: <b>MD26VI022582000000500MDL</b></span><br/>
	<span>în B.C.“VICTORIABANK S.A.”, <b>VICBMD2XXXX</b></span><br/>
	<span>c/f <b>1017600006845</b>, c/TVA <b>0609417</b></span>';*/
	
	$cmpn_dtls = [
		'nm'=>'“SAUTO” SRL',
		'adr'=>['cntr'=>'Republica Moldova', 'rgn'=>'Chişinau', 'city'=>'Cricova', 'pstl'=>'MD-2084', 'str'=>'Chisinaului 84', 'ap'=>'39'],
		'iban'=>isset($_POST['cur'])&&$_POST['cur']=='EUR'?'MD51VI022512000000094EUR':'MD64VI022512000000171MDL',
		'bank'=>'în B.C.“VICTORIABANK S.A.”, <b>VICBMD2XXXX</b>',
		'cf'=>'1017600006845',
		'tva'=>'0609417',
		'agnt'=>[ 'slava'=>['nm'=>'Olăriță Veaceslav', 'idno'=>'1017600006845'] ]
	];
	
	$zcont = '
	<b>“SAUTO” SRL</b><br/>
	<span>Republica Moldova, MD-2084, mun.Chişinau</span><br/>
	<span>or.Cricova, str.Chisinaului 84, ap.(of.) 39</span><br/>
	<span>IBAN: <b>'.(isset($_POST['cur'])&&$_POST['cur']=='EUR'?'MD51VI022512000000094EUR':'MD64VI022512000000171MDL').'</b></span><br/>
	<span>în B.C.“VICTORIABANK S.A.”, <b>VICBMD2XXXX</b></span><br/>
	<span>c/f <b>1017600006845</b>, c/TVA <b>0609417</b></span>';
	
	//$y = substr(date('y'), -1);
	$cont_y = date('y');
	$m = date('m')*1; $cont_q = $m*1>0&&$m<=3 ?1 :( $m*1>=4&&$m<=6 ?2 :( $m*1>=7&&$m<=9 ?3 :( $m*1>=10&&$m<=12 ?4 :'X' ) ) );
	$cont_n = 2727;
	
	$zdate = isset($_POST['date'])&&$_POST['date']!=''?date( 'd.m.Y', strtotime( $_POST['date'] ) ):date('d.m.Y');
	$doc_date = isset($_POST['date'])&&$_POST['date']!=''?date( 'Y-m-d', strtotime( $_POST['date'] ) ):date('Y-m-d');
	
	$info_exist = 0;
	$pdo = $db->prepare('SELECT `value` FROM '.$prefx.'_info WHERE `name`=:name AND `x1`=:x1 AND `x2`=:x2 ');
	$pdo->execute([  'name'=>'docs', 'x1'=>$_POST['doc_gr'], 'x2'=>$_POST['doc_f'] ]);
	foreach($pdo as $r){
		$cont_n += ($r['value']*1);
		$info_exist = 1;
	}
	
	if ( isset($_POST['doc_view']) && $_POST['doc_view']=='1' ){
		$cont_y = $_POST['cont_y'];
		$cont_q = $_POST['cont_q'];
		$cont_n = $_POST['cont_n'];
		
		// Retrieve existing document data from database
		if ( isset($_POST['id']) && $_POST['id'] != '' ) {
			$pdo = $db->prepare('SELECT * FROM '.$prefx.'_docs_ctlg WHERE `id`=:id LIMIT 1');
			$pdo->execute(['id' => $_POST['id']]);
			$doc_data = $pdo->fetch(PDO::FETCH_ASSOC);
			
			if ( $doc_data && $doc_data['inf'] != '' ) {
				// Parse the stored information string
				$inf_parts = explode('&&', $doc_data['inf']);
				foreach ( $inf_parts as $part ) {
					if ( strpos($part, '==') !== false ) {
						list($key, $value) = explode('==', $part, 2);
						// Restore fields according to document type
						if ( in_array($key, ['br','mo','vin'], true) ) {
							$hasList = (strpos($value, '||') !== false);
							if ( isset($_POST['doc_f']) && $_POST['doc_f'] === 'com_transport' ) {
								// com_transport expects arrays
								$_POST[$key] = $hasList ? explode('||', $value) : [$value];
							} else {
								// other docs expect scalars
								$_POST[$key] = $hasList ? explode('||', $value)[0] : $value;
							}
						} elseif ( $key === 'pays' ) {
							// Handle payment stages data: "value1=>date1||value2=>date2"
							$payStages = explode('||', $value);
							$_POST['pay_val'] = [];
							$_POST['pay_date'] = [];
							foreach ( $payStages as $stage ) {
								if ( strpos($stage, '=>') !== false ) {
									list($val, $date) = explode('=>', $stage, 2);
									$_POST['pay_val'][] = $val;
									$_POST['pay_date'][] = $date;
								}
							}
						} elseif ( $key === 'grnt_txt' ) {
							// Handle warranty text data
							$_POST['grnt_txt'] = explode('||', $value);
						} else {
							$_POST[$key] = $value;
						}
					}
				}
				// Map brand codes to names for display (works for array or scalar)
				if ( isset($_POST['br']) ){
					$brand_map = [];
					$pdo_brands = $db->prepare('SELECT DISTINCT `br`, `br_nm` FROM '.$prefx.'_car_list WHERE `br_nm` != ""');
					$pdo_brands->execute();
					foreach ($pdo_brands as $r_brand) { $brand_map[$r_brand['br']] = $r_brand['br_nm']; }
					if ( is_array($_POST['br']) ){
						foreach ($_POST['br'] as $i_b => $b_val){ if (isset($brand_map[$b_val])) { $_POST['br'][$i_b] = $brand_map[$b_val]; } }
					} else {
						if (isset($brand_map[$_POST['br']])) { $_POST['br'] = $brand_map[$_POST['br']]; }
					}
				}
			}
		}
	}
	
	echo '
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
				
				body {background-color:#fff;}
				
				.base {font-family:"def"; filter:grayscale(1); -webkit-filter:grayscale(1);}
				.base > .pg {width:210mm; height:297mm; margin:0 auto; padding:5mm 10mm; background-color:#fff; position:relative;}
				.base > .pg.bg {background:#fffc url("/media/images/site/print/bg_pg.webp") repeat center / contain; background-blend-mode:soft-light;}
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
				.sign > .s1 {float:left; position:relative;}
				.sign > .s2 {float:right; position:relative;}
				
				.flx {display:flex; flex-flow:column wrap; justify-content:space-evenly; min-height:180mm;}
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
				
				.stamp {width:40mm; height:40mm; position:absolute; top:-20mm; right:0; background:transparent url("/media/images/site/v2/sauto_stamp.webp") no-repeat center / contain; opacity:.9; mix-blend-mode:multiply;}
				.stamp > .signature {width:100%; height:100%; background:transparent url("/media/images/site/v2/sauto_sign.png") no-repeat 60% 30% / contain; top:3mm; left:-10mm; position:absolute;}
				
				span.stamp {display:block; top:-20mm; left:0;}
				span.stamp > .signature {top:2mm; left:-5mm;}
			</style>
			
			<script src="https://cdnjs.cloudflare.com/ajax/libs/html2pdf.js/0.10.1/html2pdf.bundle.min.js" integrity="sha512-GsLlZN/3F2ErC5ifS5QtgpiJtWd43JWSuIgh7mbzZ8zBps+dvLusV+eNQATqgA/HdeKFVgA5v3S/cIrLF7QnIg==" crossorigin="anonymous" referrerpolicy="no-referrer"></script>
			<script>
				$(document).ready(function(){
					function px2cm(px){
						var d = $("<div/>").css({ position: "absolute", top : "-1000cm", left : "-1000cm", height : "1000cm", width : "1000cm" }).appendTo("body");
						var px_per_cm = d.height() / 1000;
						d.remove();
						return px / px_per_cm;
					}
					//px2cm(100);
					//alert(px2cm(1000));';
					
					if ( isset($_POST['fn']) ){
						if ( $_POST['fn']=='save_pdf' ){
							echo '
							$(".sep").remove();
							
							var element = document.getElementById("p_cont");
							var opt = {
								margin:       0,
								filename:     "sauto_doc.pdf",
								image:        { type: "jpeg", quality: 0.98 },
								html2canvas:  { scale: 2, ignoreElements : (".sep") },
								jsPDF:        { orientation: "portrait" }
							};
							//html2pdf().set(opt).from(element).save();
							html2pdf(element, opt);';
						} elseif ( $_POST['fn']=='print_it' ){
							echo ' 
							window.print(); ';
						}
					}else{
						echo ' 
						window.close(); ';
					}
				echo '
				})
			</script>
		</head>
		<body>';
		
		include(__DIR__.'/docs/'.$_POST['doc_gr'].'/'.$_POST['doc_f'].'.php');
		
		echo '
		</body>
	</html>';
	
	if (isset($_POST['save_inf']) && $_POST['save_inf']=='1'){
		$u_tp = isset($_POST['u_tp'])&&$_POST['u_tp']!=''?$_POST['u_tp']:'x';
		$u_nm = isset($_POST['u_nm'])&&$_POST['u_nm']!=''?$_POST['u_nm']:'x';
		$u_cf_idno = isset($_POST['u_cf_idno'])&&$_POST['u_cf_idno']!=''?$_POST['u_cf_idno']:0;
		$u_tva_dt = isset($_POST['u_tva_dt'])&&$_POST['u_tva_dt']!=''?$_POST['u_tva_dt']:0;
		$u_iban_dt_tk = isset($_POST['u_iban_dt_tk'])&&$_POST['u_iban_dt_tk']!=''?$_POST['u_iban_dt_tk']:0;
		$u_adr = isset($_POST['u_adr'])&&$_POST['u_adr']!=''?$_POST['u_adr']:'';
		$u_phn = isset($_POST['u_phn'])&&$_POST['u_phn']!=''?$_POST['u_phn']:'';
		$u_eml = isset($_POST['u_eml'])&&$_POST['u_eml']!=''?$_POST['u_eml']:'';
		
		$it_cd = isset($_POST['vin'])&&$_POST['vin']!=''?( is_array($_POST['vin'])?'ar':strtoupper($_POST['vin']) ):'';
		
		$abr = isset($abr)?$abr:'';
		$cont_y = isset($cont_y)?$cont_y:'';
		$cont_q = isset($cont_q)?$cont_q:'';
		$cont_n = isset($cont_n)?$cont_n:'';
		
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
		$inf_ar = ['br', 'mo', 'vin', 'yr', 'clr', 'prc', 'cur', 'prc_eur', 'prc_av', 'loc', 'term_livr', 'cntr_fr', 'cntr_to', 'adr_to', 't2pay', 'plate', 'extras', 'orig', 'description', 'dealer', 'sauto_role', 'seller_name', 'seller_vat', 'seller_account', 'seller_address', 'seller_country', 'seller_swift', 'buyer_name', 'buyer_vat', 'buyer_account', 'buyer_address', 'buyer_country', 'buyer_swift', 'annexa_nr', 'cont_nr', 'add_cesionar', 'cesionar_nm', 'cesionar_cf_idno', 'cesionar_account', 'cesionar_suma', 'base_contract_id', 'kyc_client_name', 'kyc_idnp', 'kyc_address', 'kyc_phone', 'kyc_email', 'kyc_completion_date', 'kyc_residence_addr', 'kyc_transaction_purpose', 'kyc_doc_type', 'kyc_doc_series', 'kyc_doc_office', 'kyc_doc_date', 'kyc_doc_expiry', 'kyc_citizenship', 'kyc_birth_info', 'kyc_occupation', 'kyc_occupation_other', 'kyc_institution_name', 'kyc_position', 'kyc_no_public_function', 'kyc_public_function', 'kyc_public_function_other', 'kyc_affiliated_company', 'kyc_parents_names', 'kyc_spouse_name', 'kyc_children_names', 'kyc_partner_name', 'kyc_transaction_purpose_other', 'kyc_money_source', 'kyc_money_source_other', 'kyc_approval_date', 'kyc_doc_buletin', 'kyc_doc_permis', 'kyc_doc_pasaport', 'kyc_occupation_angajat', 'kyc_occupation_student', 'kyc_occupation_antreprenor', 'kyc_occupation_somer', 'kyc_occupation_pensionar', 'kyc_public_function_deputat', 'kyc_public_function_judecator', 'kyc_public_function_guvern', 'kyc_public_function_primar', 'kyc_public_function_partid', 'kyc_public_function_consilier', 'kyc_transaction_personal', 'kyc_transaction_family', 'kyc_transaction_company', 'kyc_transaction_resale', 'kyc_transaction_commercial', 'kyc_transaction_transfer', 'kyc_funds_salary', 'kyc_funds_dividends', 'kyc_funds_loan', 'kyc_funds_business', 'kyc_funds_inheritance', 'kyc_funds_donations'];
		$inf_up_ar = ['vin'];
		$kyc_checkbox_fields = ['kyc_doc_buletin', 'kyc_doc_permis', 'kyc_doc_pasaport', 'kyc_occupation_angajat', 'kyc_occupation_student', 'kyc_occupation_antreprenor', 'kyc_occupation_somer', 'kyc_occupation_pensionar', 'kyc_no_public_function', 'kyc_public_function_deputat', 'kyc_public_function_judecator', 'kyc_public_function_guvern', 'kyc_public_function_primar', 'kyc_public_function_partid', 'kyc_public_function_consilier', 'kyc_transaction_personal', 'kyc_transaction_family', 'kyc_transaction_company', 'kyc_transaction_resale', 'kyc_transaction_commercial', 'kyc_transaction_transfer', 'kyc_funds_salary', 'kyc_funds_dividends', 'kyc_funds_loan', 'kyc_funds_business', 'kyc_funds_inheritance', 'kyc_funds_donations'];
		$kyc_default_checked_fields = ['kyc_doc_buletin', 'kyc_no_public_function', 'kyc_transaction_personal', 'kyc_funds_salary'];
		
		foreach ($inf_ar as $k => $v){
			if ( in_array($v, $kyc_checkbox_fields) ) {
				$checkbox_value = (isset($_POST[$v]) && $_POST[$v] == '1') ? '1' : '0';
				$inf .= ($qu>0?'&&':'').$v.'=='.$checkbox_value;
				$qu++;
			}
			elseif ( isset($_POST[$v])&&$_POST[$v]!='' ){
				if ( is_array($_POST[$v]) ){
					$inf .= ($qu>0?'&&':'').$v.'==';
					foreach ($_POST[$v] as $k2 => $v2){
						if ( in_array($v, $inf_up_ar) ){ $v2 = strtoupper($v2); }
						$inf .= ($k2>0?'||':'').$v2;
					}
				} else {
					if ( in_array($v, $inf_up_ar) ){ $_POST[$v] = strtoupper($_POST[$v]); }
					$inf .= ($qu>0?'&&':'').$v.'=='.$_POST[$v];
				}
				$qu++;
			}
		}
		
		if ( isset($_POST['pay_val']) && $_POST['pay_val']!='' ){//Paying values, dates
			$i=0; foreach ($_POST['pay_val'] as $k => $v){ if ($v!=''){ $inf .= ($i==0?($qu>0?'&&':'').'pays==':'||').( $v.'=>'.( isset($_POST['pay_date'][$k])?$_POST['pay_date'][$k]:'' ) ); $i++;} }
		}
		
		if ( isset($_POST['grnt_txt']) ){//Additional text Warranty
			$i=0; foreach ($_POST['grnt_txt'] as $v){ if ($v!=''){ $inf .= ($i==0?($qu>0?'&&':'').'grnt_txt==':'||').$v; $i++;} }
		}
		
		if ( isset($_POST['dmg_pos']) && isset($_POST['dmg_txt']) ){//Car damages POS / TXT
			$i=0; foreach ($_POST['dmg_pos'] as $k => $v){ $inf .= ($i==0?($qu>0?'&&':'').'dmg_pos==':'||').$v; $i++; }
			$i=0; foreach ($_POST['dmg_txt'] as $k => $v){ $inf .= ($i==0?($qu>0?'&&':'').'dmg_txt==':'||').($v!=''?$v:'0'); $i++; }
		}
		
		unset($inf_ar, $qu);
		//__________________
		
		//ADD INFO TO CTLG
		// Ensure we have a valid user ID before inserting
		if ($u_id == 0 && $u_cf_idno != 0) {
			$pdo = $db->prepare('SELECT `id` FROM '.$prefx.'_docs_u WHERE `cf_idno`=:cf_idno LIMIT 1');
			$pdo->execute(['cf_idno' => $u_cf_idno]);
			$user_check = $pdo->fetch(PDO::FETCH_ASSOC);
			if ($user_check) {
				$u_id = $user_check['id'];
			}
		}
		$pdo = $db->prepare('
			INSERT INTO '.$prefx.'_docs_ctlg (`gr`, `f`, `abr`, `y`, `q`, `n`, `cd`, `inf`, `u`, `date`, `adm`, `crtd`)
			VALUES (:gr, :f, :abr, :y, :q, :n, :cd, :inf, :u, :date, :adm, :crtd)
		');
		        // Determine creator (adm) from session primarily; fallback to cookie if necessary
        $adm_creator = isset($_SESSION) && isset($_SESSION['user_id']) && $_SESSION['user_id'] !== '' ? $_SESSION['user_id'] : ( $_COOKIE['usr_id'] ?? 0 );
        $pdo->execute([ 'gr'=>$_POST['doc_gr'], 'f'=>$_POST['doc_f'], 'abr'=>$abr, 'y'=>$cont_y, 'q'=>$cont_q, 'n'=>$cont_n, 'cd'=>$it_cd, 'inf'=>$inf, 'u'=>$u_id, 'date'=>$doc_date, 'adm'=>$adm_creator, 'crtd'=>date('Y-m-d') ]);
		
		if ($info_exist == 1){
			$pdo = $db->prepare('UPDATE '.$prefx.'_info SET `value`=`value`+1 WHERE `name`=:name AND `x1`=:x1 AND `x2`=:x2 ');
			$pdo->execute([ 'name'=>'docs', 'x1'=>$_POST['doc_gr'], 'x2'=>$_POST['doc_f'] ]);
		} else {
			$pdo = $db->prepare('INSERT INTO '.$prefx.'_info (`name`, `xtr`, `x1`, `x2`, `x3`, `value`) VALUES (:name, :xtr, :x1, :x2, :x3, :value)');
			$pdo->execute([ 'name'=>'docs', 'xtr'=>'', 'x1'=>$_POST['doc_gr'], 'x2'=>$_POST['doc_f'], 'x3'=>'', 'value'=>1 ]);
		}
	}
}
?>