<?php 
include_once($_SERVER['DOCUMENT_ROOT'].'/environment.php');

$_doc_gr_raw    = $_POST['doc_gr'] ?? '';
$_doc_gr_folder = in_array($_doc_gr_raw, ['ordercars','cars_extra']) ? 'cars' : $_doc_gr_raw;
if ( isset($_POST['doc_f']) && file_exists(__DIR__.'/docs/'.$_doc_gr_folder.'/'.$_POST['doc_f'].'.php') && $_doc_gr_raw !== '' ){
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
		// Retrieve existing document data from database first so we can fall back to DB values
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
						} elseif ( in_array($key, ['vca_contract_id', 'vca_contract_nr', 'vca_date', 'vca_amount', 'vcs_contract_id', 'vcs_contract_nr', 'vcs_date', 'vcs_amount', 'compensation_amount'], true) ) {
							$_POST[$key] = $value;
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
			// Fill cont_y/q/n and u_id from DB if not in POST
			if ( $doc_data ) {
				if ( !isset($_POST['cont_y']) || $_POST['cont_y'] === '' ) $_POST['cont_y'] = $doc_data['y'] ?? '';
				if ( !isset($_POST['cont_q']) || $_POST['cont_q'] === '' ) $_POST['cont_q'] = $doc_data['q'] ?? '';
				if ( !isset($_POST['cont_n']) || $_POST['cont_n'] === '' ) $_POST['cont_n'] = $doc_data['n'] ?? '';
				if ( !isset($_POST['u_id'])   || $_POST['u_id']   == 0  ) $_POST['u_id']   = $doc_data['u']      ?? '';
			}
			// Fill client fields from docs_u
			$_u_id = (int)($_POST['u_id'] ?? 0);
			if ( $_u_id > 0 ) {
				$_u_stmt = $db->prepare('SELECT * FROM '.$prefx.'_docs_u WHERE id=? LIMIT 1');
				$_u_stmt->execute([$_u_id]);
				$_u_row = $_u_stmt->fetch(PDO::FETCH_ASSOC);
				if ( $_u_row ) {
					foreach ( $_u_row as $_uk => $_uv ) {
						if ( !isset($_POST['u_'.$_uk]) || $_POST['u_'.$_uk] === '' ) {
							$_POST['u_'.$_uk] = $_uv;
						}
					}
				}
			}
		}
		$cont_y = $_POST['cont_y'] ?? '';
		$cont_q = $_POST['cont_q'] ?? '';
		$cont_n = $_POST['cont_n'] ?? '';
	}

	ob_start();
	echo '
	<!DOCTYPE html>
	<html>
		<head>
			<meta http-equiv="Content-type" content="text/html; charset=UTF-8" />
			<meta name="viewport" content="width=device-width, initial-scale=1, minimum-scale=1" id="mobile_viewport" />
			
			<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
			
			<title>Print</title>
			
			<link rel="stylesheet" type="text/css" href="/content/default/css/default.css" />
			<script>
			function crmShowTransactionConfirm(docId) {
				var _lang = (document.cookie.match(/(?:^|; )lang=([^;]*)/) || [])[1] || "ro";
				var _tx = {
					ro: {title: "Tranzacția a fost finalizată?", yes: "Da",  no: "Nu"},
					ru: {title: "Сделка завершена?",  yes: "Да", no: "Нет"},
					en: {title: "Transaction completed?", yes: "Yes", no: "No"}
				};
				var _t = _tx[_lang] || _tx.ro;
				var overlay = document.createElement("div");
				overlay.style.cssText = "position:fixed;inset:0;background:rgba(0,0,0,0.55);z-index:99999;display:flex;align-items:center;justify-content:center;";
				overlay.innerHTML = \'<div style="background:#fff;border-radius:14px;padding:2rem 2rem 1.5rem;max-width:360px;width:90%;box-shadow:0 8px 32px rgba(0,0,0,.22);text-align:center;">\'+
					\'<div style="font-size:1.05rem;font-weight:700;color:#191919;margin-bottom:1.2rem;">\' + _t.title + \'</div>\'+
					\'<div style="display:flex;gap:1rem;justify-content:center;">\'+
					\'<button id="crm-tc-no" class="crm-tc-btn" style="flex:1;padding:0.75rem;border:1.5px solid #e5e7eb;background:#fff;border-radius:8px;font-size:0.95rem;font-weight:600;color:#555;cursor:pointer;">\' + _t.no + \'</button>\'+
					\'<button id="crm-tc-yes" class="crm-tc-btn" style="flex:1;padding:0.75rem;background:#E61E2D;border:none;border-radius:8px;font-size:0.95rem;font-weight:700;color:#fff;cursor:pointer;">\' + _t.yes + \'</button>\'+
					\'<style>.crm-tc-btn{transition:transform 0.15s;}.crm-tc-btn:hover{transform:scale(1.05);}</style>\'+
					\'</div></div>\';
				document.body.appendChild(overlay);
				function doChoice(txStatus) {
					document.getElementById("crm-tc-yes").disabled = true;
					document.getElementById("crm-tc-no").disabled  = true;
					fetch("/ajax.php", {
						method: "POST",
						headers: {"Content-Type": "application/x-www-form-urlencoded"},
						body: "tp=adm&pg=crm&fn=set_doc_tx_status&id=" + docId + "&tx_status=" + txStatus
					}).then(function(){ window.close(); }).catch(function(){ window.close(); });
				}
				document.getElementById("crm-tc-yes").addEventListener("click", function(){ doChoice("closed"); });
				document.getElementById("crm-tc-no").addEventListener("click",  function(){ doChoice("transaction"); });
			}
			</script>
			<style>
				@media print {
					@page {size:auto; size: A4 '.(isset($_POST['doc_f']) && in_array($_POST['doc_f'], ['foaie_parcurs','foaie_parcurs_cars']) ? 'landscape' : 'portrait').'; margin:0;}
					* {-webkit-print-color-adjust:exact !important; color-adjust:exact !important; print-color-adjust:exact !important;}
					.sep {display:none;}
				}
				
				html, body {min-height:auto !important; height:auto !important; margin:0; padding:0;}
				body {background-color:#fff;}
				
				.base {font-family:"def"; filter:grayscale(1); -webkit-filter:grayscale(1);}
				.base > .pg {width:'.(isset($_POST['doc_f']) && in_array($_POST['doc_f'], ['foaie_parcurs','foaie_parcurs_cars']) ? '297mm' : '210mm').'; min-height:'.(isset($_POST['doc_f']) && in_array($_POST['doc_f'], ['foaie_parcurs','foaie_parcurs_cars']) ? '209mm' : '296mm').'; margin:0 auto; padding:'.(isset($_POST['doc_f']) && in_array($_POST['doc_f'], ['foaie_parcurs','foaie_parcurs_cars']) ? '5mm' : '5mm 10mm').'; background-color:#fff; position:relative; box-sizing:border-box;}
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
				
				.flx {display:flex; flex-flow:column wrap; justify-content:space-evenly;}
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
				
				@media screen and (max-width:767px), screen and (orientation:portrait) and (max-width:900px) {
					body:not(.pdf-export) .base > .pg {width:100% !important; height:auto !important; padding:3mm 4mm !important; box-sizing:border-box;}
					body:not(.pdf-export) .base > .pg.bg {background-size:cover;}
					body:not(.pdf-export) .cont {font-size:0.7rem;}
					body:not(.pdf-export) .logo {max-width:80px;}
					body:not(.pdf-export) .logo img {max-width:100%; height:auto;}
					body:not(.pdf-export) .sign > * {width:48%;}
					body:not(.pdf-export) .sign {margin-top:10mm;}
					body:not(.pdf-export) table {font-size:0.65rem; table-layout:fixed; word-wrap:break-word;}
					body:not(.pdf-export) tr > td {padding:1.5mm 1mm;}
					body:not(.pdf-export) tr > td div[style*="white-space"] {white-space:normal !important;}
					body:not(.pdf-export) tr > td span[style*="width"], body:not(.pdf-export) tr > td div[style*="width"] {width:auto !important; max-width:100% !important;}
					body:not(.pdf-export) tr > td span[style*="margin-left: 15mm"], body:not(.pdf-export) tr > td span[style*="margin-left:15mm"] {margin-left:2mm !important;}
					body:not(.pdf-export) tr > td span[style*="margin-left: 5mm"], body:not(.pdf-export) tr > td span[style*="margin-left:5mm"] {margin-left:1mm !important;}
					body:not(.pdf-export) .flx {min-height:auto;}
					body:not(.pdf-export) .stamp {width:20mm; height:20mm; top:-10mm; right:0;}
					body:not(.pdf-export) .stamp > .signature {top:2mm; left:-5mm;}
					body:not(.pdf-export) .sign > .s1, body:not(.pdf-export) .sign > .s2 {position:relative;}
					body:not(.pdf-export) .head div {font-size:0.75rem !important;}
				}
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
					
					$_doc_f_check  = $_POST['doc_f'] ?? '';
					$_doc_fn       = $_POST['fn'] ?? '';
					$_doc_gr_check = $_POST['doc_gr'] ?? '';

					if ( $_doc_fn === 'save_pdf' ){
						echo '
						$(".sep").remove();
						$("body").addClass("pdf-export");
						$(".fp_page").css({"min-height":"auto"});
						if(window.innerWidth < 768){ $(".fp-footer").remove(); $(".fp_page").css({"padding":"3mm","min-height":"auto"}); $(".fp-tables").css("margin-top","3mm"); }

						setTimeout(function(){
							var element = document.getElementById("p_cont");
							var opt = {
								margin:       0,
								filename:     "sauto_doc.pdf",
								image:        { type: "jpeg", quality: 0.98 },
								html2canvas:  { scale: 2, ignoreElements : (".sep"), useCORS: true },
								jsPDF:        { orientation: "'.(isset($_POST['doc_f']) && in_array($_POST['doc_f'], ['foaie_parcurs','foaie_parcurs_cars']) ? 'landscape' : 'portrait').'", unit: "mm", format: "a4" },
								pagebreak:    { mode: "avoid-all" }
							};
							html2pdf().set(opt).from(element).save().then(function(){
								$("body").removeClass("pdf-export");
							});
						}, 100);';
					} elseif ( $_doc_fn === 'print_it' ){
						// New document (no id in POST) — crm_tracked will be set after this HTML is buffered
						$_is_new_doc = empty($_POST['id']) && !empty($_POST['save_inf']);
						if (!$_is_new_doc) {
							$_doc_db_p = $doc_data ?? null;
							if (!$_doc_db_p && !empty($_POST['id'])) {
								$_s2 = $db->prepare('SELECT tx_status, crm_tracked FROM '.$prefx.'_docs_ctlg WHERE id=? LIMIT 1');
								$_s2->execute([(int)$_POST['id']]);
								$_doc_db_p = $_s2->fetch(PDO::FETCH_ASSOC) ?: null;
							}
							$_show_confirm_print = in_array($_doc_f_check, ['vinzare_avans'])
								&& (int)($_doc_db_p['crm_tracked'] ?? 0) === 1
								&& ($_doc_db_p['tx_status'] ?? '') !== 'closed'
								&& $_doc_gr_check === 'cars';
						} else {
							// Creation: we know crm_tracked=1 will be set for these doc types
							$_crm_doc_types_check = ['con_plata','con_arvon','con_arvon_com','vinzare_avans','cesionar','act_compensare'];
							$_show_confirm_print = in_array($_doc_f_check, ['vinzare_avans'])
								&& in_array($_doc_f_check, $_crm_doc_types_check)
								&& $_doc_gr_check === 'cars';
						}
						if ($_show_confirm_print) {
							echo '
							window.print();
							crmShowTransactionConfirm(__CRM_DOC_ID__);';
						} else {
							echo '
							window.print();';
						}
					} else {
						$_doc_db = $doc_data ?? null;
						if (!$_doc_db && !empty($_POST['id'])) {
							$_s = $db->prepare('SELECT tx_status, crm_tracked FROM '.$prefx.'_docs_ctlg WHERE id=? LIMIT 1');
							$_s->execute([(int)$_POST['id']]);
							$_doc_db = $_s->fetch(PDO::FETCH_ASSOC) ?: null;
						}
						$_tx_status_check   = $_doc_db['tx_status']   ?? '';
						$_crm_tracked_check = (int)($_doc_db['crm_tracked'] ?? 0);
						$_show_confirm = in_array($_doc_f_check, ['vinzare_avans'])
							&& $_crm_tracked_check === 1
							&& in_array($_doc_fn, ['show_it', 'edit_sbmt'])
							&& $_tx_status_check !== 'closed'
							&& ($_POST['doc_view'] ?? '') !== '1'
							&& (
								$_doc_gr_check === 'cars' ||
								($_doc_gr_check === 'ordercars' && $_doc_fn === 'edit_sbmt')
							);
						if ($_show_confirm) {
							echo '
							crmShowTransactionConfirm(__CRM_DOC_ID__);';
						} elseif ($_doc_fn !== "show_it") {
							echo '
							window.close();';
						}
					}
				echo '
				})
			</script>
		</head>
		<body>';
		
		include(__DIR__.'/docs/'.$_doc_gr_folder.'/'.$_POST['doc_f'].'.php');
		
		echo '
	</body>
</html>';
	
	if (isset($_POST['save_inf']) && $_POST['save_inf']=='1'){
		if (in_array($_POST['doc_f'], ['foaie_parcurs','foaie_parcurs_cars']) && isset($_POST['fp_daa_start']) && $_POST['fp_daa_start'] != '' && (!isset($_POST['doc_view']) || $_POST['doc_view'] != '1')) {
			$fp_daa_val = intval($_POST['fp_daa_start']);
			$fp_x2_key = $_POST['doc_f'];
			$pdo_daa_check = $db->prepare('SELECT `id` FROM '.$prefx.'_info WHERE `name`=:name AND `x1`=:x1 AND `x2`=:x2 LIMIT 1');
			$pdo_daa_check->execute(['name' => 'fp_daa_start', 'x1' => 'cars', 'x2' => $fp_x2_key]);
			if ($pdo_daa_check->fetch()) {
				$pdo_daa_upd = $db->prepare('UPDATE '.$prefx.'_info SET `value`=:value WHERE `name`=:name AND `x1`=:x1 AND `x2`=:x2');
				$pdo_daa_upd->execute(['value' => $fp_daa_val, 'name' => 'fp_daa_start', 'x1' => 'cars', 'x2' => $fp_x2_key]);
			} else {
				$pdo_daa_ins = $db->prepare('INSERT INTO '.$prefx.'_info (`name`, `x1`, `x2`, `value`) VALUES (:name, :x1, :x2, :value)');
				$pdo_daa_ins->execute(['name' => 'fp_daa_start', 'x1' => 'cars', 'x2' => $fp_x2_key, 'value' => $fp_daa_val]);
			}
		}

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
			// Preserve existing non-empty values when the form doesn't submit these fields
			if ($u_tp == 'x' && !empty($existing_user['tp'])) $u_tp = $existing_user['tp'];
			if ($u_nm == 'x' && !empty($existing_user['nm'])) $u_nm = $existing_user['nm'];
			if (($u_tva_dt === 0 || $u_tva_dt === '0' || $u_tva_dt === '') && !empty($existing_user['tva_dt'])) $u_tva_dt = $existing_user['tva_dt'];
			if (($u_iban_dt_tk === 0 || $u_iban_dt_tk === '0' || $u_iban_dt_tk === '') && !empty($existing_user['iban_dt_tk'])) $u_iban_dt_tk = $existing_user['iban_dt_tk'];
			if ($u_adr === '' && !empty($existing_user['adr'])) $u_adr = $existing_user['adr'];
			if ($u_phn === '' && !empty($existing_user['phn'])) $u_phn = $existing_user['phn'];
			if ($u_eml === '' && !empty($existing_user['eml'])) $u_eml = $existing_user['eml'];
			$pdo = $db->prepare('UPDATE '.$prefx.'_docs_u SET
			`tp`=:tp, `nm`=:nm, `cf_idno`=:cf_idno, `tva_dt`=:tva_dt, `iban_dt_tk`=:iban_dt_tk, `adr`=:adr, `phn`=:phn, `eml`=:eml
			WHERE `id`=:id');
			$pdo->execute([ 'tp'=>$u_tp, 'nm'=>$u_nm, 'cf_idno'=>$u_cf_idno, 'tva_dt'=>$u_tva_dt, 'iban_dt_tk'=>$u_iban_dt_tk, 'adr'=>$u_adr, 'phn'=>$u_phn, 'eml'=>$u_eml, 'id'=>$u_id ]);
		}
		
		//__________________INFO generator
		$inf=''; $qu=0;
		$inf_ar = ['br', 'mo', 'vin', 'yr', 'clr', 'prc', 'cur', 'prc_eur', 'prc_av', 'loc', 'term_livr', 'cntr_fr', 'cntr_to', 'adr_to', 't2pay', 'plate', 'extras', 'orig', 'u_eur', 'description', 'dealer', 'sauto_role', 'seller_name', 'seller_vat', 'seller_account', 'seller_address', 'seller_country', 'seller_swift', 'buyer_name', 'buyer_vat', 'buyer_account', 'buyer_address', 'buyer_country', 'buyer_swift', 'annexa_nr', 'cont_nr', 'add_cesionar', 'cesionar_nm', 'cesionar_cf_idno', 'cesionar_account', 'cesionar_suma', 'base_contract_id', 'vca_contract_id', 'vca_contract_nr', 'vca_date', 'vca_amount', 'vcs_contract_id', 'vcs_contract_nr', 'vcs_date', 'vcs_amount', 'compensation_amount', 'sofer', 'autovehicul', 'fp_daa', 'kyc_client_name', 'kyc_idnp', 'kyc_address', 'kyc_phone', 'kyc_email', 'kyc_completion_date', 'kyc_residence_addr', 'kyc_transaction_purpose', 'kyc_doc_type', 'kyc_doc_series', 'kyc_doc_office', 'kyc_doc_date', 'kyc_doc_expiry', 'kyc_citizenship', 'kyc_birth_info', 'kyc_occupation', 'kyc_occupation_other', 'kyc_institution_name', 'kyc_position', 'kyc_no_public_function', 'kyc_public_function', 'kyc_public_function_other', 'kyc_affiliated_company', 'kyc_parents_names', 'kyc_spouse_name', 'kyc_children_names', 'kyc_partner_name', 'kyc_transaction_purpose_other', 'kyc_money_source', 'kyc_money_source_other', 'kyc_approval_date', 'kyc_doc_buletin', 'kyc_doc_permis', 'kyc_doc_pasaport', 'kyc_occupation_angajat', 'kyc_occupation_student', 'kyc_occupation_antreprenor', 'kyc_occupation_somer', 'kyc_occupation_pensionar', 'kyc_public_function_deputat', 'kyc_public_function_judecator', 'kyc_public_function_guvern', 'kyc_public_function_primar', 'kyc_public_function_partid', 'kyc_public_function_consilier', 'kyc_transaction_personal', 'kyc_transaction_family', 'kyc_transaction_company', 'kyc_transaction_resale', 'kyc_transaction_commercial', 'kyc_transaction_transfer', 'kyc_funds_salary', 'kyc_funds_dividends', 'kyc_funds_loan', 'kyc_funds_business', 'kyc_funds_inheritance', 'kyc_funds_donations'];
		$inf_up_ar = ['vin'];
		$kyc_checkbox_fields = ['kyc_doc_buletin', 'kyc_doc_permis', 'kyc_doc_pasaport', 'kyc_occupation_angajat', 'kyc_occupation_student', 'kyc_occupation_antreprenor', 'kyc_occupation_somer', 'kyc_occupation_pensionar', 'kyc_no_public_function', 'kyc_public_function_deputat', 'kyc_public_function_judecator', 'kyc_public_function_guvern', 'kyc_public_function_primar', 'kyc_public_function_partid', 'kyc_public_function_consilier', 'kyc_transaction_personal', 'kyc_transaction_family', 'kyc_transaction_company', 'kyc_transaction_resale', 'kyc_transaction_commercial', 'kyc_transaction_transfer', 'kyc_funds_salary', 'kyc_funds_dividends', 'kyc_funds_loan', 'kyc_funds_business', 'kyc_funds_inheritance', 'kyc_funds_donations'];
		$kyc_default_checked_fields = ['kyc_doc_buletin', 'kyc_no_public_function', 'kyc_transaction_personal', 'kyc_funds_salary'];
		
		$numeric_fields = ['u_eur'];
		
		foreach ($inf_ar as $k => $v){
			if ( in_array($v, $kyc_checkbox_fields) ) {
				$checkbox_value = (isset($_POST[$v]) && $_POST[$v] == '1') ? '1' : '0';
				$inf .= ($qu>0?'&&':'').$v.'=='.$checkbox_value;
				$qu++;
			}
			elseif ( in_array($v, $numeric_fields) && isset($_POST[$v]) ) {
				$inf .= ($qu>0?'&&':'').$v.'=='.$_POST[$v];
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
		
		if (!isset($_POST['doc_view']) || $_POST['doc_view'] != '1') {
			// Ensure we have a valid user ID before inserting
			if ($u_id == 0 && $u_cf_idno != 0) {
				$pdo = $db->prepare('SELECT `id` FROM '.$prefx.'_docs_u WHERE `cf_idno`=:cf_idno LIMIT 1');
				$pdo->execute(['cf_idno' => $u_cf_idno]);
				$user_check = $pdo->fetch(PDO::FETCH_ASSOC);
				if ($user_check) {
					$u_id = $user_check['id'];
				}
			}
			// ID-card scans go on the client, not on this document, so the same
			// photo then shows on every document of that client.
			require_once(__DIR__.'/docs/id_photo.php');
			docs_id_photo_save($db, $prefx, (int)$u_id,
				isset($_POST['id_photo_front']) ? (string)$_POST['id_photo_front'] : null,
				isset($_POST['id_photo_back'])  ? (string)$_POST['id_photo_back']  : null);

			$pdo = $db->prepare('
				INSERT INTO '.$prefx.'_docs_ctlg (`gr`, `f`, `abr`, `y`, `q`, `n`, `cd`, `inf`, `u`, `date`, `adm`, `owner_adm`, `crtd`)
				VALUES (:gr, :f, :abr, :y, :q, :n, :cd, :inf, :u, :date, :adm, :owner_adm, :crtd)
			');
			// Determine creator (adm) from session primarily; fallback to cookie if necessary
			$adm_creator = isset($_SESSION) && isset($_SESSION['user_id']) && $_SESSION['user_id'] !== '' ? $_SESSION['user_id'] : ( $_COOKIE['usr_id'] ?? 0 );
			$owner_adm_val = !empty($_POST['owner_adm']) ? (int)$_POST['owner_adm'] : null;
			$pdo->execute([ 'gr'=>$_POST['doc_gr'], 'f'=>$_POST['doc_f'], 'abr'=>$abr, 'y'=>$cont_y, 'q'=>$cont_q, 'n'=>$cont_n, 'cd'=>$it_cd, 'inf'=>$inf, 'u'=>$u_id, 'date'=>$doc_date, 'adm'=>$adm_creator, 'owner_adm'=>$owner_adm_val, 'crtd'=>date('Y-m-d') ]);
		$_saved_doc_id = (int)$db->lastInsertId();

		if ($info_exist == 1){
			$pdo = $db->prepare('UPDATE '.$prefx.'_info SET `value`=`value`+1 WHERE `name`=:name AND `x1`=:x1 AND `x2`=:x2 ');
			$pdo->execute([ 'name'=>'docs', 'x1'=>$_POST['doc_gr'], 'x2'=>$_POST['doc_f'] ]);
		} else {
			$pdo = $db->prepare('INSERT INTO '.$prefx.'_info (`name`, `xtr`, `x1`, `x2`, `x3`, `value`) VALUES (:name, :xtr, :x1, :x2, :x3, :value)');
			$pdo->execute([ 'name'=>'docs', 'xtr'=>'', 'x1'=>$_POST['doc_gr'], 'x2'=>$_POST['doc_f'], 'x3'=>'', 'value'=>1 ]);
		}

		$crm_bridge = __DIR__ . '/crm/crm_docs_bridge.php';
		$crm_core   = __DIR__ . '/crm/crm_core.php';
		if (file_exists($crm_bridge) && file_exists($crm_core)) {
			if (!defined('_CRM_CORE_LOADED')) {
				require_once($crm_core);
				define('_CRM_CORE_LOADED', 1);
			}
			require_once($crm_bridge);
			$_new_doc_id  = $_saved_doc_id ?? 0;
			$_doc_phone   = $u_phn ?? '';
			$_doc_f       = $_POST['doc_f'] ?? '';
			$_doc_gr      = $_POST['doc_gr'] ?? '';
			$_doc_adm     = (int)($adm_creator ?? 0);
			$_crm_lead_id = (int)($_POST['crm_lead_id'] ?? 0);
			if ($_doc_gr === 'ordercars') {
				$_doc_dept = 'order';
			} elseif ($_doc_adm) {
				$_adm_row = $db->prepare("SELECT crm_access, department FROM {$prefx}_adm_usr WHERE id=? LIMIT 1");
				$_adm_row->execute([$_doc_adm]);
				$_adm_data = $_adm_row->fetch(PDO::FETCH_ASSOC);
				$_doc_dept = ($_adm_data && in_array($_adm_data['crm_access'], ['stock','order','pruncul']))
					? $_adm_data['crm_access']
					: 'stock';
			} else {
				$_doc_dept = 'stock';
			}
			$_crm_doc_types = ['con_plata','con_arvon','con_arvon_com','vinzare_avans','cesionar','act_compensare'];
			if ($_new_doc_id && in_array($_doc_f, $_crm_doc_types)) {
				$db->prepare("UPDATE {$prefx}_docs_ctlg SET crm_tracked=1 WHERE id=:id")
				   ->execute([':id' => $_new_doc_id]);
			}
			if ($_new_doc_id && $_doc_f) {
				if ($_doc_phone) {
					$_bridge_result = docs_bridge_link($db, $prefx, $_new_doc_id, $_doc_f, $_doc_phone, $_doc_adm, $_doc_dept);
				} elseif ($_crm_lead_id) {
					$_bridge_result = docs_bridge_link_by_lead($db, $prefx, $_new_doc_id, $_doc_f, $_crm_lead_id, $_doc_adm, $_doc_dept);
				}
			}
		}
	}
}

$_html_output  = ob_get_clean();
$_final_doc_id = (int)($_saved_doc_id ?? 0);
if (!$_final_doc_id) $_final_doc_id = (int)($_POST['id'] ?? 0);

// At creation: redirect to catalog
$_is_creation    = !empty($_saved_doc_id);
$_needs_redirect = $_is_creation && !empty($_POST['doc_gr']);
if ($_needs_redirect && $_final_doc_id) {
    $lang = $_COOKIE['lang'] ?? 'ro';
    $_is_vinzare_avans = ($_POST['doc_f'] ?? '') === 'vinzare_avans'
        && ($_POST['doc_gr'] ?? '') === 'cars';
    $redirect_url = '/' . $lang . '/adminsauto/docs/ctlg'
        . ($_is_vinzare_avans ? '?crm_confirm=' . $_final_doc_id : '');
    header('Location: ' . $redirect_url);
    exit;
}

echo str_replace('__CRM_DOC_ID__', $_final_doc_id, $_html_output);
}
?>