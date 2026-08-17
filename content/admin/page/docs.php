<?php defined( '_DOIT' ) or die( 'Restricted access' );

// Client ID-card photos: the list needs the row button, the forms need the
// widget (menu.php pulls the same file).
require_once(_ADM_INCL.'/docs/id_photo.php');

$rtrn = '';

// Load translations from cars.php for document categories
$current_lang = $_COOKIE['lang'] ?? 'ro';
$cars_translations_file = $_SERVER['DOCUMENT_ROOT'] . '/lang/' . $current_lang . '/cars.php';
if (file_exists($cars_translations_file)) {
	$cars_translations = include $cars_translations_file;
	if (!isset($lng['m'])) {
		$lng['m'] = [];
	}
	// Add document category translations to $lng['m']
	$lng['m']['doc_cat_parcare'] = $cars_translations['doc_cat_parcare'] ?? 'Set de acte parcare';
	$lng['m']['doc_cat_comanda'] = $cars_translations['doc_cat_comanda'] ?? 'Set de acte auto la comanda';
	$lng['m']['doc_cat_transport'] = $cars_translations['doc_cat_transport'] ?? 'Transport';
	$lng['m']['doc_cat_sauto_buyer'] = $cars_translations['doc_cat_sauto_buyer'] ?? 'Sauto cumparator';
	$lng['m']['doc_owner_manager']  = $cars_translations['doc_owner_manager']  ?? 'Aparține managerului';
}

//$admin_menu_dev1[$user_type]['docs']

if ( isset($t_mp[4]) ){
	//if ( in_array( $t_mp[4], $admin_menu_dev1[$user_type]['docs'] ) )
	if ( $t_mp[4]=='create' || $t_mp[4]=='add' ){
		
		if ($user_type=='dev'){
			$docs_ar = [
				'cars'=>[
					($lng['m']['doc_cat_parcare'] ?? 'Set de acte parcare')=>[
						'con_plata'=>'Cont de plata'
					    ,'con_arvon'=>'Contract de arvună'
						,'vinzare_avans'=>'Contract de vânzare-cumpărare ( avans )'
						,'cesionar'=>'Anexa<br>(Cesiune drept de plată)'
						,'con_intermed'=>'Contract de intermediere'
						,'foaie_parcurs_cars'=>'Foaie de parcurs pentru automobile'
						,'act_compensare'=>'Act de compensare'
						// ,'vinzare_proc'=>'Contract de vânzare-cumpărare'
					]
				]
				,'ordercars'=>[
					($lng['m']['doc_cat_comanda'] ?? 'Set de acte auto la comanda')=>[
						'con_plata'=>'Cont de plata'
						,'con_arvon_com'=>'Contract de arvună (la comanda)'
						,'vinzare_avans'=>'Contract de vânzare-cumpărare ( avans )'
						,'cesionar'=>'Anexa<br>(Cesiune drept de plată)'
						,'act_compensare'=>'Act de compensare'
					]
				]
				,'cars_extra'=>[
					($lng['m']['doc_cat_transport'] ?? 'Transport')=>[
						'com_transport'=>'Comanda pentru transport'
						,'invoice'=>'Invoice'
						,'foaie_parcurs'=>'Foaie de parcurs pentru autocamioane'
					]
					,($lng['m']['doc_cat_sauto_buyer'] ?? 'Sauto cumparator')=>[
						'vinzare_sauto'=>'Contract de vânzare-cumpărare<br>( Sauto cumparator )'
					]
				]
			];
		} elseif ($user_type=='x1'){
			$docs_ar = [
				'cars'=>[
					'sell'=>[
						'con_intermed'=>'Contract de intermediere'
					]
				]
			];
		} else {
			$_docs_crm_stmt = $db->prepare("SELECT crm_access FROM {$prefx}_adm_usr WHERE id=? LIMIT 1");
			$_docs_crm_stmt->execute([(int)$user_id]);
			$_docs_crm_access = $_docs_crm_stmt->fetchColumn() ?: null;

			$_docs_cars = [
				($lng['m']['doc_cat_parcare'] ?? 'Set de acte parcare') => [
					'con_plata'         => 'Cont de plata',
					'con_arvon'         => 'Contract de arvună',
					'vinzare_avans'     => 'Contract de vânzare-cumpărare ( avans )',
					'cesionar'          => 'Anexa (Cesiune drept de plată)',
					'act_compensare'    => 'Act de compensare',
					'con_intermed'      => 'Contract de intermediere',
					'foaie_parcurs_cars'=> 'Foaie de parcurs pentru automobile',
				]
			];
			$_docs_ordercars = [
				($lng['m']['doc_cat_comanda'] ?? 'Set de acte auto la comanda') => [
					'con_plata'     => 'Cont de plata',
					'con_arvon_com' => 'Contract de arvună (la comanda)',
					'vinzare_avans' => 'Contract de vânzare-cumpărare ( avans )',
					'cesionar'      => 'Anexa (Cesiune drept de plată)',
					'act_compensare'=> 'Act de compensare',
				]
			];
			$_docs_extra = [
				($lng['m']['doc_cat_transport'] ?? 'Transport') => [
					'com_transport' => 'Comanda pentru transport',
					'invoice'       => 'Invoice',
					'foaie_parcurs' => 'Foaie de parcurs pentru autocamioane',
				],
				($lng['m']['doc_cat_sauto_buyer'] ?? 'Sauto cumparator') => [
					'vinzare_sauto' => 'Contract de vânzare-cumpărare<br>( Sauto cumparator )',
				],
			];

			if ($_docs_crm_access === 'order') {
				$docs_ar = ['ordercars' => $_docs_ordercars, 'cars_extra' => $_docs_extra];
			} elseif ($_docs_crm_access === 'stock' || $_docs_crm_access === 'pruncul') {
				$docs_ar = ['cars' => $_docs_cars, 'cars_extra' => $_docs_extra];
			} else {
				$docs_ar = ['cars' => $_docs_cars, 'ordercars' => $_docs_ordercars, 'cars_extra' => $_docs_extra];
			}
		}
		
		
		$rtrn .= '
		<style>
			#docs {font-family: Arial, sans-serif; padding:1rem;}
			#docs > .gr {margin-bottom:2rem;}
			#docs > .gr > .gr-title {font-size:1.5rem; font-weight:bold; color:#333; margin-bottom:1.5rem; text-align:center;}
			
			#docs > .gr > .categories-row {display:flex; gap:1rem; justify-content:space-between; flex-wrap:wrap;}
			
			#docs > .gr > .categories-row > .tp {flex:1; min-width:calc(25% - 1rem); border:1px solid #ddd; border-radius:8px; overflow:hidden; background:#fff; box-shadow:0 2px 4px rgba(0,0,0,0.1); transition:transform 0.2s;}
			#docs > .gr > .categories-row > .tp:hover {transform:translateY(-2px); box-shadow:0 4px 8px rgba(0,0,0,0.15);}
			#docs > .gr > .categories-row > .tp.collapsed {border:none; box-shadow:none; background:transparent;}
			
			#docs > .gr > .categories-row > .tp > .tp-header {padding:1rem 0.75rem; background:linear-gradient(135deg, #e2001a 0%, #bf0016 100%); color:#fff; cursor:pointer; text-align:center; transition:0.3s; min-height:60px; display:flex; flex-direction:column; justify-content:center; align-items:center;}
			#docs > .gr > .categories-row > .tp > .tp-header:hover {background:linear-gradient(135deg, #bf0016 0%, #a00012 100%);}
			#docs > .gr > .categories-row > .tp > .tp-header > .tp-title {font-size:0.9rem; font-weight:600; line-height:1.3; margin-bottom:0.3rem;}
			#docs > .gr > .categories-row > .tp > .tp-header > .tp-icon {font-size:1rem; transition:transform 0.3s;}
			#docs > .gr > .categories-row > .tp.collapsed > .tp-header > .tp-icon {transform:rotate(-180deg);}
			
			#docs > .gr > .categories-row > .tp > .its {max-height:500px; overflow:hidden; transition:max-height 0.3s ease-out, padding 0.3s ease-out, opacity 0.3s ease-out; padding:0.5rem 0; visibility:visible; opacity:1;}
			#docs > .gr > .categories-row > .tp.collapsed > .its {max-height:0; padding:0; visibility:hidden; opacity:0; height:0;}
			
			#docs > .gr > .categories-row > .tp > .its > .it {padding:0.75rem 0; position:relative; transition:.2s; display:block; border-bottom:1px solid #f0f0f0; text-align:center;}
			#docs > .gr > .categories-row > .tp > .its > .it:last-child {border-bottom:none;}
			#docs > .gr > .categories-row > .tp > .its > .it:hover {background-color:#f8f8f8;}
			#docs > .gr > .categories-row > .tp > .its > .it > .txt {color:#333; font-size:0.9rem;}
			#docs > .gr > .categories-row > .tp > .its > .it:hover > .txt {color:#e2001a; font-weight:500;}
			
			@media (max-width: 1200px) {
				#docs > .gr > .categories-row > .tp {min-width:calc(50% - 0.5rem);}
			}
			@media (max-width: 768px) {
				#docs > .gr > .categories-row > .tp {min-width:100%;}
			}
		</style>
		<div id="docs">
			<div class="gr">
				<div class="categories-row">';
				foreach($docs_ar as $gr => $ar){
					foreach($ar as $tp => $ar2){$rtrn .= '
						<div class="tp collapsed">
							<div class="tp-header" onclick="this.parentElement.classList.toggle(\'collapsed\')">
								<span class="tp-title">'.$tp.'</span>
								<span class="tp-icon">▼</span>
							</div>
							<div class="its">';
								foreach($ar2 as $k => $v){$rtrn .= '
									<a class="it" href="'.$gr.'/'.$k.'"><span class="txt">'.$v.'</span></a>';
								}
							$rtrn .= '
							</div>
						</div>';
					}
				}
			$rtrn .= '
				</div>
			</div>';
		$rtrn .= '
		</div>';
		
		$rtrn .= '
		<style>
			.sauto-info {margin-top:1.5rem; padding:2rem; background:#f9f9f9; border-radius:8px;}
			.sauto-info h3 {margin-bottom:1.5rem; color:#333;}
			.sauto-block {display:inline-block; width:32%; margin:0.5%; padding:0.5rem 1.5rem; background:#fff; border:1px solid #ddd; border-radius:5px; vertical-align:top;}
			.sauto-block pre {margin:0; font-size:0.85rem; line-height:1.6; white-space:pre-wrap;}
			.sauto-block .copy-btn {margin-bottom:1rem; padding:0.75rem 1.5rem; background:#e2001a; color:#fff; border:none; border-radius:4px; cursor:pointer; transition:0.3s; width:100%;}
			.sauto-block .copy-btn:hover {background:#c00016;}
			.sauto-block .copy-btn.copied {background:#28a745;}
		</style>
		
		<div class="sauto-info">
			<h3>'.($lng['w']['bank_details'] ?? 'Date bancare').' SAUTO SRL</h3>
			
			<div class="sauto-block">
				<button class="copy-btn" onclick="copySautoData(\'sauto-mdl\', this)">'.($lng['w']['copy'] ?? 'Copiază').' MDL</button>
				<pre id="sauto-mdl">"SAUTO" SRL
Republica Moldova, MD-2084, mun.Chişinau
or.Cricova, str.Chisinaului 84, of. 39
IBAN: MD64VI022512000000171MDL
în B.C."VICTORIABANK S.A.", VICBMD2XXXX
c/f 1017600006845, c/TVA 0609417</pre>
			</div>
			
			<div class="sauto-block">
				<button class="copy-btn" onclick="copySautoData(\'sauto-eur\', this)">'.($lng['w']['copy'] ?? 'Copiază').' EUR</button>
				<pre id="sauto-eur">"SAUTO" SRL
Republica Moldova, MD-2084, mun.Chişinau
or.Cricova, str.Chisinaului 84, of. 39
IBAN: MD51VI022512000000094EUR
în B.C."VICTORIABANK S.A.", VICBMD2XXXX
c/f 1017600006845, c/TVA 0609417</pre>
			</div>
			
			<div class="sauto-block">
				<button class="copy-btn" onclick="copySautoData(\'sauto-usd\', this)">'.($lng['w']['copy'] ?? 'Copiază').' USD</button>
				<pre id="sauto-usd">"SAUTO" SRL
Republica Moldova, MD-2084, mun.Chişinau
or.Cricova, str.Chisinaului 84, of. 39
IBAN: MD51VI022512000000094USD
în B.C."VICTORIABANK S.A.", VICBMD2XXXX
c/f 1017600006845, c/TVA 0609417</pre>
			</div>
		</div>
		
		<script>
		function copySautoData(elementId, btn) {
			var text = document.getElementById(elementId).textContent;
			var originalText = btn.textContent;
			navigator.clipboard.writeText(text).then(function() {
				btn.textContent = "✓ '.($lng['w']['copied'] ?? 'Copiat').'!";
				btn.classList.add("copied");
				setTimeout(function() {
					btn.textContent = originalText;
					btn.classList.remove("copied");
				}, 2000);
			});
		}
		</script>';
	}elseif ( $t_mp[4]=='ctlg' ){
		// Admins list for owner_adm select in edit modal
		if (empty($_doc_admins)) {
			$_doc_admins_stmt = $db->prepare("SELECT id, name FROM {$prefx}_adm_usr ORDER BY name ASC");
			$_doc_admins_stmt->execute();
			$_doc_admins = $_doc_admins_stmt->fetchAll(PDO::FETCH_ASSOC);
		}
		// Pre-build JS-safe options HTML (single string, no PHP inside JS string)
		$_doc_admins_opts_js = '';
		foreach ($_doc_admins as $_da_x) {
			$_doc_admins_opts_js .= '<option value=\"'.(int)$_da_x['id'].'\">'.str_replace(['"', "'"], ['', ''], $_da_x['name']).'</option>';
		}
		$rtrn .= '
		<style>
			.lbl {position:relative; display:inline-block;}
			.lbl > .ttl {font-size:.7rem; position:absolute; top:-.7rem; left:.25rem;}
		
			.docs > .find.user {width:100%; padding-bottom:1rem;}
			.docs > .find.user select,
			.docs > .find.user input[type="submit"],
			.docs > .find.doc input[type="text"] {padding:1rem; border:1px solid #eee; background-color:#fff; cursor:pointer;}
			.docs > .find.doc input[type="text"]{cursor:auto;}
			
			.docs > .find.user input[type="submit"] {padding:1rem 2rem; transition:.25s;}
			.docs > .find.user input[type="submit"]:hover {background-color:#b8fb76;}
			
			/* User result list styling */
			#find_user_rslt > p {position:relative; padding-right:3rem; transition:.2s;}
			#find_user_rslt > p:hover {background-color:#f0f0f0;}
			#find_user_rslt > p > .edit_user_btn {position:absolute; right:.5rem; top:50%; transform:translateY(-50%); padding:.5rem; cursor:pointer; color:#e2001a; font-size:1.2rem; transition:.2s;}
			.docs > .list input.srch {cursor:auto;}

			.docs > .list .rowz {display:flex; cursor:default; align-items:center;}
			.docs > .list .rowz > .col {overflow-wrap:anywhere; padding:.6rem .5rem; font-size:.78rem; line-height:1.4; box-sizing:border-box;}

			.docs > .list .rowz > .col:nth-child(1) {width:6%; min-width:55px;}
			.docs > .list .rowz > .col:nth-child(2) {width:12%; min-width:80px;}
			.docs > .list .rowz > .col:nth-child(3) {width:10%; min-width:60px;}
			.docs > .list .rowz > .col:nth-child(4) {width:15%;}
			.docs > .list .rowz > .col:nth-child(5) {width:6%; min-width:50px;}
			.docs > .list .rowz > .col:nth-child(6) {width:18%;}
			.docs > .list .rowz > .col:nth-child(7) {width:19%;}
			.docs > .list .rowz > .col:nth-child(8) {width:14%; min-width:60px;}

			.docs > .list .rowz.hdr {background:linear-gradient(135deg,#6c757d 0%,#545b62 100%); color:#fff; font-weight:600; font-size:.75rem; text-transform:uppercase; letter-spacing:.5px; margin-top:1rem;}
			.docs > .list .rowz.hdr > .col {padding:.75rem .5rem; border-right:1px solid rgba(255,255,255,.1);}
			.docs > .list .rowz.hdr > .col:last-child {border-right:none;}

			.sep {width:100%; text-align:center; color:#bf4040; margin:.5rem 0;}

			.bx {position:relative; display:block; margin:0; transition:.2s; border-bottom:1px solid #f0f0f0;}
			.bx.odd {background-color:#fff;}
			.bx.even {background-color:#fafbfc;}
			.bx.hide {transform:scale(0); opacity:0;}
			.bx:hover {background-color:#fff3f3;}
			
			.bx .rowz > .col .date {color:#555; font-weight:400;}
			.bx .rowz > .col .u_nm {font-weight:400; color:#333;}
			.bx .rowz > .col .u_cf_idno {color:#888; font-size:.72rem;}
			.bx .rowz > .col .prc {font-weight:400; color:#333;}
			.bx .rowz > .col .br {color:#333;}
			.bx .rowz > .col .mo {color:#555;}
			.bx .rowz > .col .vin {color:#aaa; font-size:.7rem;}
			
			.bx .u > .nm {text-transform:capitalize;}
			.bx .nr > .date,
			.bx .it > .vin	{color:#9f9f9f; font-size:.7rem;}
			
			.bx > .info {width:100%; min-height:2.8rem;}
			
			.bx > .btns {width:0; height:100%; overflow:hidden; display:flex; flex-flow:row wrap; justify-content:center; opacity:0; position:absolute; top:0; right:0; background-color:#f4e0e0ee; transition:.15s;}
			.bx > input[name="btns_act"]:checked ~ .btns {width:100%; opacity:1;}
			.bx > .btns > input[type="submit"] {background:none; color:inherit; border:none; font:inherit; outline:inherit;}
			.bx > .btns > .btn {height:inherit; align-items:center; display:flex; padding:.4rem 1.2rem; cursor:pointer; align-self:center; border-radius:6px; margin:0 .5rem; transition:.2s; font-size:.78rem; font-weight:500;}
			.bx > .btns > .btn:hover {background-color:#e2001a; color:#fff; transform:translateY(-1px); box-shadow:0 2px 6px rgba(226,0,26,.3);}
			
			/* Special styling for buttons after edit operation */
			.bx.edited > .btns {background-color:#e2001a;}
			.bx.edited > .btns > input[type="submit"] {color:#fff;}
			.bx.edited > .btns > .btn {color:#fff;}
			
			/* Hide buttons initially for edited documents to prevent flash */
			.bx.edited > .btns {
				visibility: hidden;
			}
			.bx.edited.ready > .btns {
				visibility: visible;
			}
			
			/* Client name - show only when edited */
			.bx > .btns > .client-name {
				display: none;
			}
			.bx.edited > .btns > .client-name {
				display: flex;
				align-items: center;
				align-self: center;
				padding: .4rem 1.2rem;
				margin: 0 .5rem;
				font-size: .78rem;
				font-weight: 600;
				color: #000;
			}
			
			#overlay label > input, #overlay label > select {width:100%; padding:1rem; border:1px solid #eee; background-color:#fff; transition:.2s;}
			
			#overlay .content {text-align:left;}
			
			::placeholder, ::-webkit-input-placeholder {text-align:center;}
			#overlay input:not([type="submit"], [type="checkbox"]), select, textarea {width:100%; padding:1rem; border:1px solid #eee; background-color:#fff; transition:border-color .2s; float:left;}
			#overlay textarea {min-height:3rem; padding:.5rem 1rem; resize:vertical;}
			#overlay input[type="submit"] {background-color:#777; color:#fff; transition:.2s;}
			#overlay input[type="submit"]:hover {background-color:#e2001a;}
			#overlay input:focus, select:focus {border-color:#333;}
			
			#overlay .ttl {margin:1rem 0;}
			
			#overlay .lbl {width:31%; position:relative; display:inline-block; margin:.5rem;}
			#overlay .lbl.max {width:100%; margin:.5rem;}
			#overlay .lbl > .ttl {font-size:.7rem; position:absolute; top:-1.7rem; left:.25rem;}
			#overlay .btn {display:inline-block; padding:.5rem 2rem; margin:0 .5rem 1rem; cursor:pointer; background-color:#333; color:#fff; transition:background-color .3s;}
			#overlay .btn:hover {background-color:var(--clr);}
			
			#date_pay_bx input[type="date"], #date_pay_bx .lbl {width:47.2% !important; margin:.5rem !important;}
			
			#overlay .action {width:100%; display:flex; flex-flow:row wrap; justify-content:space-between;}
			#overlay .action > .btn.submit, #overlay .action > .btn.close {width:48%; text-align:center; margin:2rem .5rem 0; padding:1rem; animation:unset;}
			
			/* KYC Form Styling for Edit Mode - to match ADD mode appearance */
			#overlay .kyc-questionnaire {
				margin-top: 30px;
				display: block;
			}
			
			#overlay .kyc-questionnaire .ttl {
				font-size: 1rem;
				font-weight: bold;
				margin-top: 30px;
				margin-bottom: 0;
			}
			
			#overlay .kyc-questionnaire > div {
				margin: 10px 0;
				padding: 10px;
				border: 1px solid #ddd;
				border-radius: 5px;
			}
			
			#overlay .kyc-questionnaire > div > div:first-child {
				font-weight: bold;
				margin-bottom: 10px;
			}
			
			#overlay .kyc-questionnaire label {
				display: inline-block;
				margin-right: 15px;
				margin-bottom: 5px;
				width: auto;
				position: static;
			}
			
			#overlay .kyc-questionnaire label input[type="checkbox"] {
				margin-right: 5px;
				width: auto;
				height: auto;
				padding: 0;
				border: none;
				background: none;
				float: none;
			}
			
			#overlay .kyc-questionnaire label span {
				font-size: 0.9rem;
				line-height: 1.4;
			}
			
			/* Special width for occupation checkboxes */
			#overlay .kyc-questionnaire div:nth-child(3) label {
				width: 120px;
			}
			
			/* Block display for public function and transaction purpose */
			#overlay .kyc-questionnaire div:nth-child(4) label,
			#overlay .kyc-questionnaire div:nth-child(5) label,
			#overlay .kyc-questionnaire div:nth-child(6) label {
				display: block;
				width: auto;
				margin-bottom: 5px;
			}
		</style>
		<script>
			function crmShowTransactionConfirmInline(docId, onDone) {
				var _lang = (document.cookie.match(/(?:^|; )lang=([^;]*)/) || [])[1] || "ro";
				var _tx = {
					ro: {title: "Tranzacția a fost finalizată?", yes: "Da",  no: "Nu"},
					ru: {title: "Сделка завершена?",            yes: "Да",  no: "Нет"},
					en: {title: "Transaction completed?",       yes: "Yes", no: "No"}
				};
				var _t = _tx[_lang] || _tx.ro;
				var overlay = document.createElement("div");
				overlay.style.cssText = "position:fixed;inset:0;background:rgba(0,0,0,0.55);z-index:99999;display:flex;align-items:center;justify-content:center;";
				overlay.innerHTML = "<div style=\"background:#fff;border-radius:14px;padding:2rem 2rem 1.5rem;max-width:360px;width:90%;box-shadow:0 8px 32px rgba(0,0,0,.22);text-align:center;\">" +
					"<div style=\"font-size:1.05rem;font-weight:700;color:#191919;margin-bottom:1.2rem;\">" + _t.title + "</div>" +
					"<div style=\"display:flex;gap:1rem;justify-content:center;\">" +
					"<button id=\"crm-tc-no\" style=\"flex:1;padding:0.75rem;border:1.5px solid #e5e7eb;background:#fff;border-radius:8px;font-size:0.95rem;font-weight:600;color:#555;cursor:pointer;transition:transform 0.15s;\">" + _t.no + "</button>" +
					"<button id=\"crm-tc-yes\" style=\"flex:1;padding:0.75rem;background:#E61E2D;border:none;border-radius:8px;font-size:0.95rem;font-weight:700;color:#fff;cursor:pointer;transition:transform 0.15s;\">" + _t.yes + "</button>" +
					"</div></div>";
				document.body.appendChild(overlay);
				overlay.querySelectorAll("button").forEach(function(b){
					b.addEventListener("mouseenter", function(){ this.style.transform = "scale(1.05)"; });
					b.addEventListener("mouseleave", function(){ this.style.transform = ""; });
				});
				function doChoice(txStatus) {
					document.getElementById("crm-tc-yes").disabled = true;
					document.getElementById("crm-tc-no").disabled  = true;
					fetch("/ajax.php", {
						method: "POST",
						headers: {"Content-Type": "application/x-www-form-urlencoded"},
						body: "tp=adm&pg=crm&fn=set_doc_tx_status&id=" + docId + "&tx_status=" + txStatus
					}).then(function(){ document.body.removeChild(overlay); if (onDone) onDone(); });
				}
				document.getElementById("crm-tc-yes").addEventListener("click", function(){ doChoice("closed"); });
				document.getElementById("crm-tc-no").addEventListener("click",  function(){ doChoice("transaction"); });
			}
		</script>
		<script>
			$(document).ready(function(){
				var reqType = "adm";
				var reqPage = "docs";

				// Show transaction confirm popup after document creation (crm_confirm=docId in URL)
				(function(){
					var urlParams = new URLSearchParams(window.location.search);
					var confirmId = parseInt(urlParams.get("crm_confirm") || 0);
					if (confirmId) {
						// Clean URL without reload
						history.replaceState(null, "", window.location.pathname);
						crmShowTransactionConfirmInline(confirmId, function() {
							window.location.reload();
						});
					}
				})();

				// Check if we need to keep buttons visible after reload
				var keepVisible = localStorage.getItem("keepButtonsVisible");
				var scrollPosition = localStorage.getItem("docsScrollPosition");
				
				if (keepVisible) {
					var editedDoc = $(".docs > .list > .bx[data-id=\"" + keepVisible + "\"]");
					if (editedDoc.length) {
						// Apply edited class first (this hides buttons via CSS)
						editedDoc.addClass("edited");
						editedDoc.find("input[name=\"btns_act\"]").prop("checked", true);
						
						// Minimal delay to ensure CSS is applied, then show with ready class
						setTimeout(function() {
							editedDoc.addClass("ready");
						}, 1);
						
						// Always scroll to the edited document, regardless of saved position
						setTimeout(function() {
							editedDoc[0].scrollIntoView({ behavior: "smooth", block: "center" });
						}, 100);
					}
					localStorage.removeItem("keepButtonsVisible");
					localStorage.removeItem("docsScrollPosition");
				} else if (scrollPosition) {
					// Restore scroll position even without edited document
					setTimeout(function() {
						window.scrollTo(0, parseInt(scrollPosition));
						localStorage.removeItem("docsScrollPosition");
					}, 1);
				}
				
				$(".docs > .find.user select[name=\"tp\"]").on("change", function(){
					var tp = $(this).val(), l = $(".docs > .find.user select[name=\"list\"]");
					l.val("all").change();
					l.children("option:not([value=\"all\"])").each(function(){ if ( $(this).data("tp") == tp || tp == "all" ){ $(this).removeClass("none"); }else{ $(this).addClass("none"); } })
				})
				
				$(".docs > .find.user input[type=\"submit\"]").on("click", function(){})

				$(document).on("click", ".docs > .list > .bx > .btns > .btn input[type=\"checkbox\"]", function(e){
					e.stopPropagation();
				});
				
				$(document).on("input", ".docs > .find input.srch", function(){
					var srchV = $(this).val().toLowerCase().replace("ă","a").replace("â","a").replace("î","i").replace("ș","s").replace("ț","t").replace("_"," ");
					if (srchV != ""){ $(".sep").addClass("none"); }else{ $(".sep").removeClass("none"); }
					$(".docs > .list > .bx").each(function(){ if ( $(this).data("tags").indexOf( srchV ) === -1 ){ $(this).addClass("none") }else{ $(this).removeClass("none"); } })
				})
				
				$(document).on("click", ".docs > .list > .bx > .btns > .btn", function(e){
					var fn = $(this).data("fn");
					
					if ( fn ){ //typeof fn!=="undefined"
						var data = {}; data["tp"] = reqType; data["pg"] = reqPage; data["fn"] = fn;
						var bx = $(this).closest(".bx"); var vals = bx.children(".values");
						data["id"] = vals.data("id"); data["doc"] = vals.data("doc"); data["gr"] = vals.data("gr");
						
						if ( fn=="del_it" ){ //delete button pressed
							if ( confirm( $(this).text()+"?" ) ){
								ajaxIt(data);
								bx.addClass("hide").delay(500).queue(function(){ $(this).remove(); $(this).dequeue(); });
							}
						}
						else if ( $.inArray( fn, ["show_it", "save_pdf", "print_it", "edit_it"] ) !== -1 ){ //other button pressed
							
							if ( fn=="show_it" || fn=="print_it" || fn=="save_pdf" ){
								if ( $("#content > .tmp_form").length ){ $("#content > .tmp_form").remove(); } //remove old one form
								$("#content").prepend("<form class=\"tmp_form none\" target=\"_blank\" method=\"POST\" action=\"/'._ADM_INCL.'/docs_print.php\" novalidate></form>"); //add new one form to html
								$("#content > .tmp_form").html("" //add html to the form
									+"<input type=\"hidden\" name=\"doc_gr\" value=\""+vals.data("gr")+"\" />"
									+"<input type=\"hidden\" name=\"doc_f\" value=\""+vals.data("doc")+"\" />"
									+"<input type=\"hidden\" name=\"fn\" value=\""+fn+"\" />"
									
									+"<input type=\"hidden\" name=\"id\" value=\""+vals.data("id")+"\" />"
									+"<input type=\"hidden\" name=\"u_id\" value=\""+vals.data("u_id")+"\" />"
									
									+"<input type=\"hidden\" name=\"doc_view\" value=\"1\" />"
									+"<input type=\"hidden\" name=\"cont_y\" value=\""+vals.data("cont_y")+"\" />"
									+"<input type=\"hidden\" name=\"cont_q\" value=\""+vals.data("cont_q")+"\" />"
									+"<input type=\"hidden\" name=\"cont_n\" value=\""+vals.data("cont_n")+"\" />"
									+"<input type=\"hidden\" name=\"date\" value=\""+vals.data("date")+"\" />"
									
									+$("#content > .docs > .list > .copy > .menu_"+vals.data("doc")).html()
									+"<label>Stampila? <input type=\"checkbox\" name=\"stamp\" value=\"1\" /></label>"
									+"<label>Stampila client? <input type=\"checkbox\" name=\"usr_stamp\" value=\"1\" /></label>"
									+"<input type=\"submit\" value=\"Print\">"
								);
								var base = $("#content > .tmp_form");
							} else if ( fn=="edit_it" ){
								overlay( "open", "#content > .docs > .list > .copy > .menu_"+vals.data("doc"), "self" );
								
								// Add user info header
								var createdBy = vals.data("adm") && typeof window.adm_ar !== "undefined" && window.adm_ar[vals.data("adm")] ? window.adm_ar[vals.data("adm")] : (vals.data("adm") || "Unknown");
								var lastEditedBy = vals.data("last_edited_by") && typeof window.adm_ar !== "undefined" && window.adm_ar[vals.data("last_edited_by")] ? window.adm_ar[vals.data("last_edited_by")] : (vals.data("last_edited_by") || vals.data("adm") || "Unknown");
								
								$("#overlay > .content > form").prepend(""
									+"<div class=\"doc_info\" style=\"margin-bottom: 1.5rem; padding: 1rem; background: linear-gradient(135deg, #f8f9fa 0%, #e9ecef 100%); border-radius: 8px; border-left: 4px solid #e2001a; box-shadow: 0 2px 4px rgba(0,0,0,0.1);\">"
									+"<div style=\"display: flex; align-items: center; gap: 2rem; font-size: 0.85rem;\">"
									+"<div style=\"display: flex; align-items: center; gap: 0.5rem;\">"
									+"<span style=\"color: #6c757d; font-weight: 500;\">📝 '.addslashes($lng['w']['doc_created_by'] ?? 'Created by').':</span>"
									+"<span style=\"color: #e2001a; font-weight: 600; background-color: rgba(226,0,26,0.1); padding: 0.25rem 0.5rem; border-radius: 4px;\">"+createdBy+"</span>"
									+"</div>"
									+"<div style=\"display: flex; align-items: center; gap: 0.5rem;\">"
									+"<span style=\"color: #6c757d; font-weight: 500;\">✏️ '.addslashes($lng['w']['doc_last_edited_by'] ?? 'Last edited by').':</span>"
									+"<span style=\"color: #495057; font-weight: 600; background-color: rgba(73,80,87,0.1); padding: 0.25rem 0.5rem; border-radius: 4px;\">"+lastEditedBy+"</span>"
									+"</div>"
									+"<div style=\"display:flex; align-items:center; gap:0.5rem;\">"
									+"<span style=\"color:#e2001a; font-weight:600;\">'.addslashes($lng['m']['doc_owner_manager'] ?? 'Aparține managerului').':</span>"
									+"<select name=\"owner_adm\" style=\"padding:0.25rem 0.5rem; font-size:0.85rem; border:1px solid #ddd; border-radius:4px; box-sizing:border-box;\">"
									+"<option value=\"\">— "+createdBy+" —</option>"
									+"'.($_doc_admins_opts_js ?? '').'"
									+"</select>"
									+"</div>"
									+"</div>"
								);
								// Pre-select current owner_adm
								var _cur_owner = vals.data("owner_adm") || "";
								if (_cur_owner) {
									$("#overlay select[name=\"owner_adm\"]").val(String(_cur_owner));
								}

								$("#overlay > .content > form").append(""
									+"<input type=\"hidden\" name=\"doc_gr\" value=\""+vals.data("gr")+"\" />"
									+"<input type=\"hidden\" name=\"doc_f\" value=\""+vals.data("doc")+"\" />"
									+"<input type=\"hidden\" name=\"fn\" value=\""+fn+"\" />"
									+"<input type=\"hidden\" name=\"id\" value=\""+vals.data("id")+"\" />"
									+"<input type=\"hidden\" name=\"u_id\" value=\""+vals.data("u_id")+"\" />"
									+"<div class=\"action\"><div class=\"btn submit\" data-fn=\"edit_sbmt\">Done</div><div class=\"btn close\">Cancel</div></div>"
								);
								var base = $("#overlay");
							}
							
							$.each( vals.data(), function(k,v){
								var el = base.find("[name=\""+k+"\"], [name=\""+k+"[]\"]"), tag = el.prop("tagName"), v = v.toString();
								
								if ( el.length ){
									if ( tag=="SELECT" ){
										if ( typeof v === "string" ){
											var selectValue = v.indexOf("||") >= 0 ? v.split("||")[0] : v;
											el.val(selectValue).trigger("change");
											// Fallback: if no option matched by value (legacy data stored as text), try match by text
											if (!el.val() || el.val() === null || el.val() === "" || el.find("option:selected").length === 0){
												var lowered = selectValue.toString().toLowerCase();
												var opt = el.find("option").filter(function(){ return $(this).text().toLowerCase() === lowered; }).first();
												if (opt.length){ el.val(opt.val()).trigger("change"); }
											}
										}
									} else if ( tag=="INPUT" || tag=="TEXTAREA" ){
										if ( typeof v === "string" ){ 
											// Special handling for KYC checkboxes
											if (el.attr("type") === "checkbox" && k.indexOf("kyc_") === 0) {
												if (v === "1") {
													el.prop("checked", true);
												} else {
													el.prop("checked", false);
												}
											} else {
												el.val(v); 
											}
										}
									} else if ( tag=="DIV" ){
										v = v + "";
										var tmp = v.split("||");
										for (var i = 0; i < tmp.length; i++){
											if (vals.data("doc")=="com_transport"){
												if ( k=="br" && i<(tmp.length-1) ){base.find(".btn[data-fn=\"add_it\"]").trigger("click");}
												var arrEl = base.find("[name=\""+k+"[]\"][data-n=\""+i+"\"]");
												arrEl.val(tmp[i]).trigger("change");
												// Fallback by option text for legacy values
												if (arrEl.prop("tagName") === "SELECT"){ 
													if (!arrEl.val() || arrEl.val() === null || arrEl.val() === "" || arrEl.find("option:selected").length === 0){
														var lowered = tmp[i].toString().toLowerCase();
														var opt = arrEl.find("option").filter(function(){ return $(this).text().toLowerCase() === lowered; }).first();
														if (opt.length){ arrEl.val(opt.val()).trigger("change"); }
													}
												}
											}
										}
									}
								}
								
								if ( v.indexOf("||") >= 0 ){
									v = v + "";
									var tmp = v.split("||");
									for (var i = 0; i < tmp.length; i++){
										if (vals.data("doc")=="com_transport"){
											if ( k=="br" && i<(tmp.length-1) ){base.find(".btn[data-fn=\"add_it\"]").trigger("click");}
											base.find("[name=\""+k+"[]\"][data-n=\""+i+"\"]").val(tmp[i]).trigger("change");
											// Fallback by option text for legacy values
											if (base.find("[name=\""+k+"[]\"][data-n=\""+i+"\"]").prop("tagName") === "SELECT"){ 
												if (!base.find("[name=\""+k+"[]\"][data-n=\""+i+"\"]").val() || base.find("[name=\""+k+"[]\"][data-n=\""+i+"\"]").val() === null || base.find("[name=\""+k+"[]\"][data-n=\""+i+"\"]").val() === "" || base.find("[name=\""+k+"[]\"][data-n=\""+i+"\"]").find("option:selected").length === 0){
													var lowered = tmp[i].toString().toLowerCase();
													var opt = base.find("[name=\""+k+"[]\"][data-n=\""+i+"\"]").find("option").filter(function(){ return $(this).text().toLowerCase() === lowered; }).first();
													if (opt.length){ base.find("[name=\""+k+"[]\"][data-n=\""+i+"\"]").val(opt.val()).trigger("change"); }
												}
											}
										}
									}
								}
								
							})

							// The loop above only writes the hidden inputs; drawing the
							// ID-card previews from them is the widget\'s own job.
							if ( typeof window.docsIdPhotoSync === "function" ){ window.docsIdPhotoSync(base); }

							// Handle payment stages data for vinzare_avans
							if (vals.data("doc") == "vinzare_avans" && vals.data("pays")) {
								var paysData = vals.data("pays").toString();
								if (paysData && paysData !== "") {
									var payStages = paysData.split("||");
									for (var i = 0; i < payStages.length; i++) {
										if (payStages[i] && payStages[i].indexOf("=>") >= 0) {
											// Add new payment stage field
											base.find(".btn[data-fn=\"add_date_pay\"]").trigger("click");
											
											var parts = payStages[i].split("=>");
											var value = parts[0] || "";
											var dateText = parts[1] || "";
											
											// Populate the fields
											var payValInputs = base.find("input[name=\"pay_val[]\"]");
											var payDateInputs = base.find("input[name=\"pay_date[]\"]");
											
											if (payValInputs.length > i) {
												payValInputs.eq(i).val(value);
											}
											if (payDateInputs.length > i) {
												payDateInputs.eq(i).val(dateText);
											}
										}
									}
								}
							}
							
							// Handle grnt_txt (Garanție) data for vinzare_avans
							if (vals.data("doc") == "vinzare_avans" && vals.data("grnt_txt")) {
								var grntData = vals.data("grnt_txt").toString();
								if (grntData && grntData !== "") {
									var grntTexts = grntData.split("||");
									for (var i = 0; i < grntTexts.length; i++) {
										if (grntTexts[i] && grntTexts[i] !== "") {
											// Add new grnt_txt field
											base.find(".btn[data-fn=\"add_grnt_fld\"]").trigger("click");
											
											// Populate the field
											var grntInputs = base.find("textarea[name=\"grnt_txt[]\"]");
											
											if (grntInputs.length > i) {
												grntInputs.eq(i).val(grntTexts[i]);
											}
										}
									}
								}
							}
							
							// Handle act_compensare contract data loading in edit mode
							if (vals.data("doc") == "act_compensare") {
								setTimeout(function() {
									var vcaContractId = vals.data("vca_contract_id");
									var vcsContractId = vals.data("vcs_contract_id");
									
									if (vcaContractId) {
										var vcaEl = base.find("#vca_contract_id")[0];
										if (vcaEl) {
											vcaEl.value = vcaContractId;
											vcaEl.dispatchEvent(new Event("change"));
										}
									}
									
									if (vcsContractId) {
										var vcsEl = base.find("#vcs_contract_id")[0];
										if (vcsEl) {
											vcsEl.value = vcsContractId;
											vcsEl.dispatchEvent(new Event("change"));
										}
									}
								}, 100);
							}
							
							// After all fields are populated, trigger brand change and then set model value
							setTimeout(function() {
								var brandSelect = base.find("select[name=\"br\"]");
								var modelSelect = base.find("select[name=\"mo\"]");
								var modelValue = vals.data("mo");
								
								if (brandSelect.length){ brandSelect.trigger("change"); }
								// Set model value after brand change completes and model options are (visually) filtered
								setTimeout(function() {
									if (modelSelect.length && modelValue && modelValue !== "") {
										modelSelect.val(modelValue).trigger("change");
										if (!modelSelect.val() || modelSelect.find("option:selected").length === 0){
											var lowered = modelValue.toString().toLowerCase();
											var opt = modelSelect.find("option").filter(function(){ return $(this).text().toLowerCase() === lowered; }).first();
											if (opt.length){ modelSelect.val(opt.val()).trigger("change"); }
										}
									}
								}, 300);
							}, 50);
							
							if ( fn=="show_it" || fn=="print_it" || fn=="save_pdf" ){
								if ( $(this).find("input[name=\"stamp\"]").is(":checked") ){ base.find("input[name=\"stamp\"]").prop("checked", true); }
								if ( $(this).find("input[name=\"usr_stamp\"]").is(":checked") ){ base.find("input[name=\"usr_stamp\"]").prop("checked", true); }
								base[0].submit();
							}
						}
						
					}
				})
				
				$(document).on("click", "#overlay .btn.submit[data-fn=\"edit_sbmt\"]", function(e){
					var data = {}; data["tp"] = reqType; data["pg"] = reqPage; data["fn"] = $(this).data("fn");
					data["inp"] = getFormData( $("#overlay form") );
					//console.log(data["inp"]);
					
					// Store the edited document ID and scroll position before reload
					localStorage.setItem("keepButtonsVisible", data["inp"]["id"]);
					localStorage.setItem("docsScrollPosition", window.pageYOffset || document.documentElement.scrollTop);
					
					// Send AJAX request and wait for response
					$.ajax({
						url:"/ajax.php", 
						method:"POST", 
						type:"POST", 
						data:data, 
						async:true, 
						datatype:"json",
						success: function(response){
							// Update UI with saved data
							var bx = $(".docs > .list > .bx[data-id=\""+data["inp"]["id"]+"\"]");
							var vals = bx.find(".values");
							var dateChanged = false;
							var newYear = null;
							
							$.each( data["inp"], function(k,v){
								// Check if date was changed
								if (k === "date" && v) {
									var oldDate = vals.data("date");
									if (oldDate !== v) {
										dateChanged = true;
										newYear = new Date(v).getFullYear();
									}
								}
								
								if ( $.inArray(k, ["br", "mo", "vin", "extras", "dmg_pos", "dmg_txt"]) !== -1 && $.isArray(data["inp"][k]) ){
									v = "";
									for (i=0; i<data["inp"][k].length; i++){ v += (i>0?"||":"")+data["inp"][k][i]; }
									vals.data(k, v).attr("data-"+k, v);
									// Update visual display for arrays
									if (k=="br" || k=="mo") {
										bx.find(".rowz > .col span."+k).text(data["inp"][k].join(", "));
									}
								}
								
								if (k=="pay_val"){
									v = "";
									for (i=0; i<data["inp"]["pay_val"].length; i++){ v += (i>0?"||":"")+data["inp"]["pay_val"][i]+"=>"+data["inp"]["pay_date"][i]; }
									vals.data("pays", v).attr("data-pays", v);
								}
								else if (k=="pay_date"){return;}
								else {
									vals.data(k, v).attr("data-"+k, v);
									bx.find(".rowz > .col span."+k).text(v);
								}
								//console.log(k+"::: "+v)
							})
							
							// Close the overlay
							$("#overlay").hide();

							// Popup for vinzare_avans on edit
							var _editDocF  = data["inp"]["doc_f"]  || "";
							var _editDocGr = data["inp"]["doc_gr"] || "";
							var _editDocId = parseInt(data["inp"]["id"] || 0);
							var _editTxStatus = $(".docs > .list > .bx[data-id=\""+_editDocId+"\"] .values").data("tx_status") || "";
							if (_editDocF === "vinzare_avans" && (_editDocGr === "cars" || _editDocGr === "ordercars") && _editDocId && _editTxStatus !== "closed") {
								crmShowTransactionConfirmInline(_editDocId, function() {
									if (dateChanged && newYear) { window.location.href = window.location.pathname + "?year=" + newYear; return; }
									window.location.reload();
								});
								return;
							}

							// If date changed, always redirect to the new year
							if (dateChanged && newYear) {
								var currentUrl = window.location.search;
								if (currentUrl.indexOf("date_from=") !== -1 || currentUrl.indexOf("date_to=") !== -1) {
									window.location.href = window.location.pathname + "?year=" + newYear;
									return;
								} else {
									window.location.href = window.location.pathname + "?year=" + newYear;
									return;
								}
							}

							// Normal reload if date did not change
							window.location.reload();
						}
					});
				})
				
				function getFormData($form){
					var x_ar = $form.serializeArray(), kv_ar = {};
					$.map(x_ar, function(v, i){
						if (v["name"].indexOf("[]") >= 0){
							var name = v["name"].replace("[]","");
							if ( !( name in kv_ar) ){ kv_ar[ name ] = []; }
							kv_ar[ name ].push( v["value"] );
						}else{
							kv_ar[ v["name"] ] = v["value"];
						}
					});
					
					// Special handling for KYC checkboxes - include unchecked ones as "0"
					var kyc_checkboxes = [
						"kyc_doc_buletin", "kyc_doc_permis", "kyc_doc_pasaport",
						"kyc_occupation_angajat", "kyc_occupation_student", "kyc_occupation_antreprenor", "kyc_occupation_somer", "kyc_occupation_pensionar",
						"kyc_no_public_function", "kyc_public_function_deputat", "kyc_public_function_judecator", "kyc_public_function_guvern", "kyc_public_function_primar", "kyc_public_function_partid", "kyc_public_function_consilier",
						"kyc_transaction_personal", "kyc_transaction_family", "kyc_transaction_company", "kyc_transaction_resale", "kyc_transaction_commercial", "kyc_transaction_transfer",
						"kyc_funds_salary", "kyc_funds_dividends", "kyc_funds_loan", "kyc_funds_business", "kyc_funds_inheritance", "kyc_funds_donations"
					];
					
					$.each(kyc_checkboxes, function(i, checkbox_name) {
						var $checkbox = $form.find("input[name=\"" + checkbox_name + "\"]");
						if ($checkbox.length > 0) {
							// If checkbox exists in form but not in serialized data, it\'s unchecked
							if (!(checkbox_name in kv_ar)) {
								kv_ar[checkbox_name] = "0";
							}
						} else {
							kv_ar[checkbox_name] = "0";
						}
					});
					
					// Special handling for numeric fields - always include even if 0 or empty
					var numeric_fields = ["u_eur"];
					$.each(numeric_fields, function(i, field_name) {
						var $field = $form.find("input[name=\"" + field_name + "\"]");
						if ($field.length > 0) {
							// If field exists but not in serialized data, add it with its value (even if 0)
							if (!(field_name in kv_ar)) {
								kv_ar[field_name] = $field.val() || "0";
							}
						}
					});
					
					//console.log(kv_ar);
					return kv_ar;
				}
				
				$(document).on("keypress", "#overlay input, #overlay select", function(e){//:not([type=\"submit\"])
					if(e.which == 13){//ENTER key
						//$(this).next("input, select").focus();
						//return false;
						
						e.preventDefault();
						$("input, select, textarea")
						[$("input,select,textarea").index(this)+1].focus();
					}
				});
				
				$(document).on("change", "#overlay select[name=\"br\"], #overlay select[name=\"br[]\"]", function(){
					var br = $(this).val(), n = $(this).data("n");
					$("select[name=\"mo\"] > option:not(.none), select[name=\"mo[]\"][data-n=\""+n+"\"] > option:not(.none)").addClass("none");
					$("select[name=\"mo\"] > option[data-br=\""+br+"\"], select[name=\"mo[]\"][data-n=\""+n+"\"] > option[data-br=\""+br+"\"]").removeClass("none");

				});
				
				$(document).on("click", "#overlay .btn[data-fn=\"add_it\"], #content > .tmp_form .btn[data-fn=\"add_it\"]", function(){
					var bx = $("#its_bx");
					bx.data("qu", (bx.data("qu")+1) ).attr("data-qu", bx.data("qu") );
					$("#its_bx").append( bx.find(".def").html() );
					
					var next = $(this).data("next");
					$("#its_bx > .car_group[data-n=\"x\"]").data( "n", next ).attr( "data-n", next );
					$("#its_bx > .car_group[data-n=\""+next+"\"] [data-n=\"x\"]").data( "n", next ).attr( "data-n", next ).removeAttr("disabled");
					$(this).data( "next", (next+1) ).attr( "data-next", (next+1) );
				})
				
				$(document).on("click", "#overlay .btn[data-fn=\"del_it\"]", function(){
					var carGroup = $(this).closest(".car_group");
					var carCount = $("#its_bx > .car_group").length;
					
					// Prevent deleting if only one car remains
					if (carCount <= 1) {
						alert("Невозможно удалить последний автомобиль.");
						return;
					}
					
					carGroup.remove();
				})
				
				$(document).on("change", "#overlay select[name=\"u_tp\"]", function(){
					var v = $(this).val();
					$("#overlay input.fj").each(function(){
						if ( $(this).hasClass("dt") ){ $(this).val("").attr( "type", (v=="fiz"?"date":"text") ); }
						$(this).attr({ "title":$(this).data(v) });
						$(this).parent().children(".ttl").text( $(this).data(v) );
					})
				})
				
				$(document).on("click", "#overlay .btn[data-fn=\"add_date_pay\"]", function(){
					var bx = $("#date_pay_bx");
					bx.data("qu", (bx.data("qu")+1) ).attr("data-qu", bx.data("qu") );
					$("#date_pay_bx").append(""
						+"<label class=\"lbl\"><span class=\"ttl\">Value</span><input type=\"text\" class=\"need\" name=\"pay_val[]\" title=\"Pay value\" /></label>"
						+"<label class=\"lbl\"><span class=\"ttl\">Date, Text</span><input class=\"need dt\" type=\"text\" name=\"pay_date[]\" title=\"Pay date / Text\" /></label>"
					);
				})
				
				$(document).on("click", "#overlay .btn[data-fn=\"add_grnt_fld\"]", function(){
					var bx = $("#grnt_fld_bx");
					bx.data("qu", (bx.data("qu")+1) ).attr("data-qu", bx.data("qu") );
					$("#grnt_fld_bx").append("<label class=\"lbl max\"><span class=\"ttl\">6."+bx.data("qu")+".</span><textarea class=\"need\" name=\"grnt_txt[]\" title=\"Group 6 text\" rows=\"1\" /></textarea></label>");
				})
			})
		</script>
		<div class="docs">
			<div class="find_user none">';
				$pdo = $db->prepare('SELECT * FROM '.$prefx.'_docs_u ORDER BY `nm` ASC'); $pdo->execute();
				foreach ($pdo as $r){
					$rtrn .= '<b 
					data-id="'.$r['id'].'" data-nm="'.$r['nm'].'" data-tp="'.$r['tp'].'" data-cf_idno="'.$r['cf_idno'].'" 
					data-tva_dt="'.$r['tva_dt'].'" data-iban_dt_tk="'.$r['iban_dt_tk'].'" data-adr="'.$r['adr'].'" data-phn="'.$r['phn'].'" data-eml="'.$r['eml'].'"
					></b>';
				}
			$rtrn .= '
			</div>
			<div class="find doc">
				<label class="lbl"><span class="ttl">Search</span><input type="text" class="srch" /></label>
				<span class="btn" style="display:inline-block; padding:0.4rem 1rem; margin:0 0 0 0.5rem; background:#e2001a; color:#fff; cursor:pointer; border-radius:3px; vertical-align:top; line-height:1.8;" onclick="window.location.href=\'/\'+Cookies.get(\'lang\')+\'/adminsauto/docs/ctlg\';">&#10005;</span>
			</div>
			<div class="list">';
				$rtrn .= '<div class="copy none">';
				$mixall = 1;
				include(__DIR__.'/../ajax/docs/menu.php');
				unset($mixall);
				$rtrn .= '</div>';
				
				$adm_ar = [];
				$pdo = $db->prepare('SELECT * FROM '.$prefx.'_adm_usr ORDER BY `id` ASC'); $pdo->execute();
				foreach ($pdo as $r){ $adm_ar[ $r['id'] ] = $r['name']; }
				
				// Make admin array available to JavaScript
			$rtrn .= '<script>window.adm_ar = '.json_encode($adm_ar).';</script>';
			
			// Get all available years from database
			$years_pdo = $db->prepare('SELECT DISTINCT YEAR(c.date) as year FROM '.$prefx.'_docs_ctlg AS c ORDER BY year DESC');
			$years_pdo->execute();
			$all_years = [];
			foreach ($years_pdo as $r) {
				$all_years[] = $r['year'];
			}
			$rtrn .= '<script>window.allYears = '.json_encode($all_years).';</script>';
			
			$i=1; $date = '';
		
		// Check if we should load all documents (via loadall parameter or if there's a search query)
		$loadAll = (isset($_GET['loadall']) && $_GET['loadall'] == '1') || (isset($_GET['search']) && $_GET['search'] != '');
		$yearFilter_sql = (isset($_GET['year']) && $_GET['year'] != '' && $_GET['year'] != 'all') ? intval($_GET['year']) : 0;
		$dateFrom_sql = (isset($_GET['date_from']) && $_GET['date_from'] != '') ? $_GET['date_from'] : '';
		$dateTo_sql = (isset($_GET['date_to']) && $_GET['date_to'] != '') ? $_GET['date_to'] : '';
		
		if ($dateFrom_sql != '' || $dateTo_sql != '') {
			// Date range filter (highest priority)
			$where = [];
			$params = [];
			if ($dateFrom_sql != '') { $where[] = 'c.date >= :df'; $params['df'] = $dateFrom_sql; }
			if ($dateTo_sql != '') { $where[] = 'c.date <= :dt'; $params['dt'] = $dateTo_sql; }
			$pdo = $db->prepare('SELECT 
				u.id u_id, u.nm u_nm, u.tp u_tp, u.cf_idno u_cf_idno, u.tva_dt u_tva_dt, u.iban_dt_tk u_iban_dt_tk, u.adr u_adr, u.phn u_phn, u.eml u_eml,
				u.id_photo_front, u.id_photo_back,
				c.*, 
				c.last_edited_by
				FROM 
					'.$prefx.'_docs_u AS u 
					INNER JOIN 
					'.$prefx.'_docs_ctlg AS c 
				ON u.id=c.u 
				WHERE '.implode(' AND ', $where).'
				ORDER BY c.date DESC, c.id DESC'); 
			$pdo->execute($params);
		} elseif ($yearFilter_sql > 0) {
			// Year filter
			$pdo = $db->prepare('SELECT 
				u.id u_id, u.nm u_nm, u.tp u_tp, u.cf_idno u_cf_idno, u.tva_dt u_tva_dt, u.iban_dt_tk u_iban_dt_tk, u.adr u_adr, u.phn u_phn, u.eml u_eml,
				u.id_photo_front, u.id_photo_back,
				c.*, 
				c.last_edited_by
				FROM 
					'.$prefx.'_docs_u AS u 
					INNER JOIN 
					'.$prefx.'_docs_ctlg AS c 
				ON u.id=c.u 
				WHERE YEAR(c.date) = :yr
				ORDER BY c.date DESC, c.id DESC'); 
			$pdo->execute(['yr' => $yearFilter_sql]);
		} elseif (!$loadAll) {
			// Load only last 2 months initially
			$twoMonthsAgo = date('Y-m-d', strtotime('-2 months'));
			
			$pdo = $db->prepare('SELECT 
				u.id u_id, u.nm u_nm, u.tp u_tp, u.cf_idno u_cf_idno, u.tva_dt u_tva_dt, u.iban_dt_tk u_iban_dt_tk, u.adr u_adr, u.phn u_phn, u.eml u_eml,
				u.id_photo_front, u.id_photo_back,
				c.*, 
				c.last_edited_by
				FROM 
					'.$prefx.'_docs_u AS u 
					INNER JOIN 
					'.$prefx.'_docs_ctlg AS c 
				ON u.id=c.u 
				WHERE c.date >= :twoMonthsAgo
				ORDER BY c.date DESC, c.id DESC'); 
			$pdo->execute(['twoMonthsAgo' => $twoMonthsAgo]);
		} else {
			// Load all documents
			$pdo = $db->prepare('SELECT 
				u.id u_id, u.nm u_nm, u.tp u_tp, u.cf_idno u_cf_idno, u.tva_dt u_tva_dt, u.iban_dt_tk u_iban_dt_tk, u.adr u_adr, u.phn u_phn, u.eml u_eml,
				u.id_photo_front, u.id_photo_back,
				c.*, 
				c.last_edited_by
				FROM 
					'.$prefx.'_docs_u AS u 
					INNER JOIN 
					'.$prefx.'_docs_ctlg AS c 
				ON u.id=c.u 
				ORDER BY c.date DESC, c.id DESC'); 
			$pdo->execute();
		}	
					
				$rtrn .= '
				<div class="rowz hdr">
					<div class="col">Date</div>
					<div class="col">Doc</div>
					<div class="col">Nr</div>
					<div class="col">Client</div>
					<div class="col">Suma</div>
					<div class="col">Auto</div>
					<div class="col">Info</div>
					<div class="col">Added by</div>
				</div>';
				
				foreach ($pdo as $r){
					//if ( $date != $r['date'] ){$date = $r['date']; $rtrn .= '<div class="sep">'.date( 'd.m.Y', strtotime( $r['date'] ) ).'</div>';}
					
					$inf = [];//if ( isset($inf) ){ unset($inf); }
				if ( $r['inf']!='' ){
					foreach ( explode('&&', $r['inf']) as $v){
						$tmp = explode('==', $v);
						if ( isset($tmp[1]) ){ $inf[ $tmp[0] ] = $tmp[1]; }
					}
				}
				$br_mo_vin = '';
					if ( isset($inf['br']) && isset($inf['mo']) && strpos($inf['br'], '||') !== false && strpos($inf['mo'], '||') !== false ){
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
					
					if ( $user_type!='dev' && $r['adm']=='5' ){continue;}
					if ( $user_type=='x1' && ( $r['f']!='con_intermed' || ( $r['f']=='con_intermed' && ( !isset($inf['loc']) || $inf['loc']!='2' ) ) ) ){continue;}
					//if ( $user_type=='dev' && ( $r['f']!='con_intermed' || ( $r['f']=='con_intermed' && ( !isset($inf['loc']) || $inf['loc']!='2' ) ) ) ){continue;}
					if ( $user_type=='x2' && ( !isset($inf['loc']) || $inf['loc']!='2' ) ){continue;}
					
					// Role and ownership filtering
                    $user_role = $_SESSION['user_role'] ?? $user_role ?? null;
                    $user_branch_id = $_SESSION['user_branch_id'] ?? $user_branch_id ?? null;
                    $user_id = $_SESSION['user_id'] ?? $user_id ?? null;

                    // Role-based document filtering
                    if ( $user_role === 'publisher_limited' ){
                        // Publisher Limited: show only own documents
                        if ( $user_id !== null && intval($r['adm']) !== intval($user_id) ){ continue; }
                    } elseif ( $user_role === 'publisher' ){
                        // Publisher: show ALL documents (no filtering)
                        // Continue without any filtering
                    } elseif ( $user_role === 'admin' ){
                        // Admin: show ALL documents (no filtering)
                        // Continue without any filtering
                    } else {
                        // Default ownership rule: show only own docs (except for gordon)
                        if ( $user_role !== 'gordon' && $user_id !== null && intval($r['adm']) !== intval($user_id) ){ continue; }
                    }
					
					$rtrn .= '
					<label class="bx '.( $i%2>0?'odd':'even' ).'" data-id="'.$r['id'].'" data-u_id="'.$r['u_id'].'" data-tags="'.strtr(mb_strtolower( $r['u_nm'].' '.$r['u_cf_idno'].' '.$r['u_tp'].' '.$r['abr'].$r['y'].$r['q'].'/'.$r['n'].' '.( isset($inf['br'])?$inf['br']:'' ).' '.( isset($inf['mo'])?$inf['mo']:'' ).' '.( isset($inf['vin'])?$inf['vin']:'' ).' '.( isset($inf['prc'])?$inf['prc']:'' ).' '.( isset($inf['plate'])?$inf['plate']:'' ).' '.( isset($inf['sofer'])?$inf['sofer']:'' ).' '.( isset($inf['autovehicul'])?$inf['autovehicul']:'' ).' '.date( 'd.m.Y', strtotime( $r['date'] ) ).' '.$r['f'], 'UTF-8' ), ['ă'=>'a', 'â'=>'a', 'î'=>'i', 'ș'=>'s', 'ț'=>'t', '_'=>' ']).'">
						<div class="values"
							data-id="'.$r['id'].'" data-doc="'.$r['f'].'" data-gr="'.$r['gr'].'"
							data-cont_y="'.$r['y'].'" data-cont_q="'.$r['q'].'" data-cont_n="'.$r['n'].'" 
							data-u_id="'.$r['u_id'].'" data-u_cf_idno="'.$r['u_cf_idno'].'" data-u_nm="'.$r['u_nm'].'" data-date="'.$r['date'].'" 
							data-u_tva_dt="'.( $r['u_tp']=='fiz'&&strtotime($r['u_tva_dt'])!==false?date('Y-m-d',strtotime($r['u_tva_dt'])):$r['u_tva_dt'] ).'" 
							data-u_iban_dt_tk="'.( $r['u_tp']=='fiz'&&strtotime($r['u_iban_dt_tk'])!==false?date('Y-m-d',strtotime($r['u_iban_dt_tk'])):$r['u_iban_dt_tk'] ).'" 
							data-u_adr="'.$r['u_adr'].'" data-u_phn="'.$r['u_phn'].'" data-u_eml="'.$r['u_eml'].'"
							data-id_photo_front="'.htmlspecialchars((string)($r['id_photo_front'] ?? '')).'" data-id_photo_back="'.htmlspecialchars((string)($r['id_photo_back'] ?? '')).'"
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
							data-adm="'.$r['adm'].'" data-last_edited_by="'.($r['last_edited_by'] ?? $r['adm']).'" data-owner_adm="'.($r['owner_adm'] ?? '').'"
							data-tx_status="'.($r['tx_status'] ?? '').'"
						></div>
						<div class="rowz info">
							<div class="col"><span class="date">'.date( 'd.m.y', strtotime( $r['date'] ) ).'</span></div>
							<div class="col">'.( strtr(mb_convert_case($r['f'], MB_CASE_TITLE, 'UTF-8'), ['_'=>' ']) ).'</div>
							<div class="col">'.$r['abr'].$r['y'].$r['q'].'/'.$r['n'].'</div>
							<div class="col" data-id="'.$r['u_id'].'" data-tp="'.$r['u_tp'].'">'.( in_array($r['f'], ['foaie_parcurs','foaie_parcurs_cars']) ? '<span class="u_nm">'.(isset($inf['sofer'])?$inf['sofer']:'').'</span>' : '<span class="u_nm">'.mb_convert_case($r['u_nm'], MB_CASE_TITLE, 'UTF-8').'</span> <span class="u_cf_idno">'.$r['u_cf_idno'].'</span>' ).'</div>
							<div class="col"><span class="prc">'.( isset($inf['prc'])?$inf['prc']:'-' ).'</span></div>
							<div class="col">'.( $r['f']=='foaie_parcurs' ? (isset($inf['autovehicul'])?$inf['autovehicul']:'') : ($r['f']=='foaie_parcurs_cars' ? $br_mo_vin.( isset($inf['plate'])?' ['.$inf['plate'].']':'' ) : $br_mo_vin) ).'</div>
							<div class="col">'.($r['f']=='foaie_parcurs' ? (isset($inf['sofer'])?$inf['sofer']:'').', '.(isset($inf['autovehicul'])?$inf['autovehicul']:'') : ($r['f']=='foaie_parcurs_cars' ? (isset($inf['sofer'])?$inf['sofer']:'').', '.(isset($inf['plate'])?$inf['plate']:'') : $br_mo_vin)).'</div>
							<div class="col">'.( !empty($r['owner_adm']) ? (isset($adm_ar[$r['owner_adm']]) ? $adm_ar[$r['owner_adm']] : $r['owner_adm']) : (isset($adm_ar[$r['adm']]) ? $adm_ar[$r['adm']] : $r['adm']) ).'</div>
						</div>
						<input type="radio" name="btns_act" class="none">
						<div class="btns">
							<span class="client-name">'.date( 'd.m.y', strtotime( $r['date'] ) ).' | '.strtr(mb_convert_case($r['f'], MB_CASE_TITLE, 'UTF-8'), ['_'=>' ']).' | '.$r['u_nm'].'</span>
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
							'.docs_id_photo_row_btn($r['id_photo_front'] ?? null, $r['id_photo_back'] ?? null).'
							<div class="btn del" data-fn="del_it">Delete</div>
						</div>
					</label>';
					$i++;
				}
		
		// Add "More" button only if not all documents are loaded and no filters active
		if (!$loadAll && $yearFilter_sql == 0 && $dateFrom_sql == '' && $dateTo_sql == '') {
			$rtrn .= '
			<div id="load_more_btn" style="text-align:center; padding:1.5rem; cursor:pointer; background:#f9f9f9; margin:1rem 0; border:1px solid #ddd;">
				<span style="font-size:1.2rem; color:#666;">More</span>
				<span style="font-size:1rem; color:#999; margin-left:0.5rem;">▼</span>
			</div>';
		}
		
		$rtrn .= '
	</div>
</div>
	<script>
	$(document).ready(function(){
		// Handle "More" button click - load all via AJAX now
		$("#load_more_btn").on("click", function(){
			window.location.href = window.location.pathname + "?loadall=1";
		});
		
		var searchInput = $(".docs > .find.doc input.srch");
		var searchTimer = null;
		var ajaxRequest = null;
		var allDocsLoaded = ($("#load_more_btn").length === 0);
		
		function hlText(txt, q) {
			var low = q.toLowerCase();
			var result = "";
			var i = 0;
			while (i < txt.length) {
				if (i === 0 || /[\s,.\[\]()\/\-]/.test(txt[i-1])) {
					if (txt.substr(i, q.length).toLowerCase() === low) {
						result += "<b style=\"color:#e2001a\">" + txt.substr(i, q.length) + "</b>";
						i += q.length;
						continue;
					}
				}
				result += txt[i];
				i++;
			}
			return result;
		}
		function highlightSearch(rawVal) {
			$(".docs > .list > .bx .rowz.info .col").each(function(){
				var el = $(this);
				if (!el.data("orig-html")) { el.data("orig-html", el.html()); }
				el.html(el.data("orig-html"));
			});
			if (!rawVal || rawVal === "") return;
			$(".docs > .list > .bx:not(.none):not(.srch-hide) .rowz.info .col").each(function(){
				var el = $(this);
				el.find("span, a").each(function(){
					var sp = $(this);
					var txt = sp.text();
					var h = hlText(txt, rawVal);
					if (h !== txt) { sp.html(h); }
				});
				var cNodes = Array.prototype.slice.call(this.childNodes);
				cNodes.forEach(function(node){
					if (node.nodeType === 3 && node.textContent.length > 0) {
						var h = hlText(node.textContent, rawVal);
						if (h !== node.textContent) {
							var wrapper = document.createElement("span");
							wrapper.innerHTML = h;
							node.parentNode.replaceChild(wrapper, node);
						}
					}
				});
			});
		}
		
		// Instant search: client-side filter on loaded docs + AJAX for older docs
		searchInput.on("input", function(){
			var val = $(this).val().toLowerCase().replace(/[ăâ]/g,"a").replace(/[î]/g,"i").replace(/[ș]/g,"s").replace(/[ț]/g,"t").replace(/_/g," ");
			
			// Remove previous AJAX results
			$(".docs > .list > .bx.ajax-result").remove();
		
		if (val === "") {
			// Show all loaded docs, respect year filter
			$(".docs > .list > .bx").removeClass("none srch-hide");
			$("#load_more_btn").show();
			highlightSearch("");
			var yearVal = $(".docs > .find.doc select").val();
			if (yearVal && yearVal !== "all") {
				$(".docs > .list > .bx").each(function(){
					$(this).attr("data-year") == yearVal ? $(this).removeClass("none") : $(this).addClass("none");
				});
			}
			return;
		}
		
		// Hide "More" button during search
		$("#load_more_btn").hide();
		
		var rawVal = $(this).val();
		
		// Client-side filter on already loaded docs
		$(".docs > .list > .bx:not(.ajax-result)").each(function(){
			var tags = $(this).attr("data-tags") || "";
			var words = tags.split(/\s+/);
			var found = false;
			for (var w = 0; w < words.length; w++) {
				if (words[w].indexOf(val) === 0) { found = true; break; }
			}
			if (found) {
				$(this).removeClass("none srch-hide");
			} else {
				$(this).addClass("none srch-hide");
			}
		});
		
		highlightSearch(rawVal);
		
		// If not all docs are loaded, also search server-side
		if (!allDocsLoaded && val.length >= 2) {
			if (searchTimer) clearTimeout(searchTimer);
			if (ajaxRequest) ajaxRequest.abort();
			
			searchTimer = setTimeout(function(){
				// Collect IDs of already loaded docs
				var loadedIds = [];
				$(".docs > .list > .bx:not(.ajax-result)").each(function(){
					loadedIds.push($(this).data("id"));
				});
				
				ajaxRequest = $.ajax({
					url: "/ajax.php",
					method: "POST",
					data: { tp: "adm", pg: "docs", fn: "search_docs", q: val, loaded_ids: loadedIds },
					success: function(response){
						try {
							var data = typeof response === "string" ? JSON.parse(response) : response;
							if (data && data.results && data.results.length > 0) {
								// Check current search value still matches
								var currentVal = searchInput.val().toLowerCase().replace(/[ăâ]/g,"a").replace(/[î]/g,"i").replace(/[ș]/g,"s").replace(/[ț]/g,"t").replace(/_/g," ");
								if (currentVal !== val) return;
								
								var list = $(".docs > .list");
								for (var i = 0; i < data.results.length; i++) {
									// Don\'t add if already exists
									if (list.find(".bx[data-id=\"" + data.results[i].id + "\"]").length === 0) {
										list.append(data.results[i].html);
									}
								}
								highlightSearch(searchInput.val());
							}
						} catch(e) {}
					}
				});
			}, 350);
		}	
		});
		
		// Auto-focus search if loadall is set
		if (window.location.search.indexOf("loadall=1") !== -1) {
			allDocsLoaded = true;
			setTimeout(function(){
				searchInput.focus();
			}, 100);
		}
		
		var currentYear = new Date().getFullYear();
	
	// Add data-year attribute to all loaded documents
	$(".docs > .list > .bx").each(function(){
		var dateStr = $(this).find(".values").data("date");
		if (dateStr) {
			var year = new Date(dateStr).getFullYear();
			$(this).attr("data-year", year);
		}
	});
	
	// Use all years from database (loaded via PHP)
	var sortedYears = window.allYears || [];
	var yearFilter = $("<select style=\"padding:0.5rem 1.5rem 0.5rem 0.5rem; margin:0 0 0 1rem; border:1px solid #ddd; background:#fff; cursor:pointer; display:inline-block; vertical-align:top;\"></select>");
	yearFilter.append("<option value=\"all\">ALL</option>");
	sortedYears.forEach(function(year){
		yearFilter.append("<option value=\"" + year + "\">" + year + "</option>");
	});
		
		var yearWrapper = $("<span class=\"year-wrap\" style=\"position:relative; display:inline-block; float:right; margin-right:1rem;\"></span>");
		yearWrapper.append(yearFilter);
		yearWrapper.append("<span style=\"position:absolute; right:0.1rem; top:50%; transform:translateY(-50%); pointer-events:none; font-size:1rem;\">▼</span>");
		
		// Add date range filters
		var dateFromWrap = $("<span class=\"date-wrap\" data-ph=\"De la\" style=\"position:relative; display:inline-block; vertical-align:top; margin:0 0.25rem;\"></span>");
		var dateFromInput = $("<input type=\"date\" class=\"date-from\" style=\"padding:0.5rem; border:1px solid #ddd; background:#fff; cursor:pointer; width:100%;\" />");
		dateFromWrap.append(dateFromInput);
		var dateToWrap = $("<span class=\"date-wrap\" data-ph=\"Până la\" style=\"position:relative; display:inline-block; vertical-align:top; margin:0 0.25rem;\"></span>");
		var dateToInput = $("<input type=\"date\" class=\"date-to\" style=\"padding:0.5rem; border:1px solid #ddd; background:#fff; cursor:pointer; width:100%;\" />");
		dateToWrap.append(dateToInput);
		function fmtShort(v){ if(!v)return ""; var p=v.split("-"); return p[2]+"."+p[1]+"."+p[0].slice(2); }
		dateFromInput.on("change input", function(){ var w=$(this).parent(); w.toggleClass("has-val",!!this.value); w.attr("data-val",fmtShort(this.value)); });
		dateToInput.on("change input", function(){ var w=$(this).parent(); w.toggleClass("has-val",!!this.value); w.attr("data-val",fmtShort(this.value)); });
		var dateFilterBtn = $("<button style=\"padding:0.5rem 1rem; margin:0 0.5rem; border:1px solid #ddd; background:#e2001a; color:#fff; cursor:pointer; display:inline-block; vertical-align:top;\">Filter</button>");
		var dateClearBtn = $("<button style=\"padding:0.5rem 1rem; margin:0 0.5rem; border:1px solid #ddd; background:#777; color:#fff; cursor:pointer; display:inline-block; vertical-align:top;\">Reset</button>");
		
		$(".docs > .find.doc").append(dateFromWrap);
		$(".docs > .find.doc").append(dateToWrap);
		$(".docs > .find.doc").append(dateFilterBtn);
		$(".docs > .find.doc").append(dateClearBtn);
		$(".docs > .find.doc").append(yearWrapper);
		
		// Set initial year from URL params
		var urlParams = new URLSearchParams(window.location.search);
		var yearParam = urlParams.get("year");
		var dfParam = urlParams.get("date_from");
		var dtParam = urlParams.get("date_to");
		if (dfParam || dtParam) {
			yearFilter.val("all");
		} else if (yearParam) {
			yearFilter.val(yearParam);
		} else {
			yearFilter.val(currentYear);
		}
		
		// Pre-fill date inputs from URL
		if (dfParam) { dateFromInput.val(dfParam).trigger("change"); }
		if (dtParam) { dateToInput.val(dtParam).trigger("change"); }
		
		// Date range filter - server-side reload
		dateFilterBtn.on("click", function(){
			var dateFrom = dateFromInput.val();
			var dateTo = dateToInput.val();
			if (dateFrom || dateTo) {
				var url = window.location.pathname + "?";
				if (dateFrom) url += "date_from=" + dateFrom + "&";
				if (dateTo) url += "date_to=" + dateTo;
				window.location.href = url.replace(/&$/, "");
			}
		});
		
		dateClearBtn.on("click", function(){
			window.location.href = window.location.pathname;
		});
		
		// Year filter - always server-side reload
		yearFilter.on("change", function(){
			var year = $(this).val();
			if (year === "all") {
				window.location.href = window.location.pathname;
			} else {
				window.location.href = window.location.pathname + "?year=" + year;
			}
		});
	});
	</script>';
}elseif ( isset($t_mp[5]) ){
		$_doc_gr_val  = $t_mp[4];
		$_doc_gr_path = in_array($t_mp[4], ['ordercars','cars_extra']) ? 'cars' : $t_mp[4];
		$_doc_admins_stmt = $db->prepare("SELECT id, name FROM {$prefx}_adm_usr ORDER BY name ASC");
		$_doc_admins_stmt->execute();
		$_doc_admins = $_doc_admins_stmt->fetchAll(PDO::FETCH_ASSOC);
		if ( file_exists(_ADM_INCL.'/docs/'.$_doc_gr_path.'/'.$t_mp[5].'.php') ){
			$rtrn .= '
			<style>
				::placeholder, ::-webkit-input-placeholder {text-align:center;}
				input:not([type="submit"], [type="checkbox"]), select, textarea {width:100%; padding:1rem; border:1px solid #eee; background-color:#fff; transition:border-color .2s; float:left;}
				textarea {min-height:3rem; padding:.5rem 1rem; resize:vertical;}
				input[type="submit"] {background-color:#777; color:#fff; transition:.2s;}
				input[type="submit"]:hover {background-color:#e2001a;}
				input:focus, select:focus {border-color:#333;}
				
				form > .ttl {margin:1rem 0;}
				
				.lbl {width:31%; position:relative; display:inline-block; margin:.5rem;}
				.lbl.max {width:100%; margin:.5rem;}
				.lbl > .ttl {font-size:.7rem; position:absolute; top:-.7rem; left:.25rem;}
				
				.doc_pg form .btn {display:inline-block; padding:.5rem 2rem; margin:0 .5rem 1rem; cursor:pointer; background-color:#333; color:#fff; transition:background-color .3s;}
				.doc_pg form .btn:hover {background-color:var(--clr);}
				
				#date_pay_bx .lbl {width:47.2%; margin:.5rem;}
			</style>
			
			<script>
			$(document).ready(function(){
				$("input, select").on("keypress", function(e){//:not([type=\"submit\"])
					if(e.which == 13){//ENTER key
						//$(this).next("input, select").focus();
						//return false;
						
						e.preventDefault();
						$("input, select, textarea")
						[$("input,select,textarea").index(this)+1].focus();
					}
				});
				
				$("input[type=\"submit\"]").on("click", function(e){
			var ok = confirm( "Print?" );
			if (!ok){
				e.preventDefault();
			}
			// Form submits in the same tab (target=_self); PHP redirects this tab to /docs/ctlg after saving
		});
				
				$(document).on("change", "select[name=\"br\"], select[name=\"br[]\"]", function(){
					var br = $(this).val(), n = $(this).data("n");
					$("select[name=\"mo\"] > option:not(.none), select[name=\"mo[]\"][data-n=\""+n+"\"] > option:not(.none)").addClass("none");
					$("select[name=\"mo\"] > option[data-br=\""+br+"\"], select[name=\"mo[]\"][data-n=\""+n+"\"] > option[data-br=\""+br+"\"]").removeClass("none");
				})
				
				$(".doc_pg form .btn[data-fn=\"add_it\"]").on("click", function(){
					var bx = $("#its_bx");
					bx.data("qu", (bx.data("qu")+1) ).attr("data-qu", bx.data("qu") );
					$("#its_bx").append( bx.find(".def").html() );
					
					var next = $(this).data("next");
					$("#its_bx > .car_group[data-n=\"x\"]").data( "n", next ).attr( "data-n", next );
					$("#its_bx > .car_group[data-n=\""+next+"\"] [data-n=\"x\"]").data( "n", next ).attr( "data-n", next ).removeAttr("disabled");
					$(this).data( "next", (next+1) ).attr( "data-next", (next+1) );
				})
				
				$(document).on("click", ".doc_pg form .btn[data-fn=\"del_it\"]", function(){
					var carGroup = $(this).closest(".car_group");
					var carCount = $("#its_bx > .car_group").length;
					
					// Prevent deleting if only one car remains
					if (carCount <= 1) {
						alert("Невозможно удалить последний автомобиль.");
						return;
					}
					
					carGroup.remove();
				})
				
				$("select[name=\"u_tp\"]").on("change", function(){
					var v = $(this).val();
					$("input.fj").each(function(){
						if ( $(this).hasClass("dt") ){ $(this).val("").attr( "type", (v=="fiz"?"date":"text") ); }
						$(this).attr({ "title":$(this).data(v) });
						$(this).parent().children(".ttl").text( $(this).data(v) );
					})
				})
				
				$(".doc_pg form .btn[data-fn=\"add_date_pay\"]").on("click", function(){
					var bx = $("#date_pay_bx");
					bx.data("qu", (bx.data("qu")+1) ).attr("data-qu", bx.data("qu") );
					$("#date_pay_bx").append(""
						+"<label class=\"lbl\"><span class=\"ttl\">Value</span><input type=\"text\" class=\"need\" name=\"pay_val[]\" title=\"Pay value\" /></label>"
						+"<label class=\"lbl\"><span class=\"ttl\">Date, Text</span><input class=\"need dt\" type=\"text\" name=\"pay_date[]\" title=\"Pay date / Text\" /></label>"
					);
				})
				
				$(".doc_pg form .btn[data-fn=\"add_grnt_fld\"]").on("click", function(){
					var bx = $("#grnt_fld_bx");
					bx.data("qu", (bx.data("qu")+1) ).attr("data-qu", bx.data("qu") );
					$("#grnt_fld_bx").append("<label class=\"lbl max\"><span class=\"ttl\">6."+bx.data("qu")+".</span><textarea class=\"need\" name=\"grnt_txt[]\" title=\"Group 6 text\" rows=\"1\" /></textarea></label>");
				})
				
				$(document).on("input", "input[name=\"u_cf_idno\"]", function(){
					$("#find_user_rslt").html("");
					var srchV = $(this).val().toLowerCase().replace("ă","a").replace("â","a").replace("î","i").replace("ș","s").replace("ț","t").replace("_"," ");
					if (srchV != "" && srchV.length > 2){
						$("#find_user > p").each(function(){
							if ( $(this).attr("data-cf_idno").indexOf( srchV ) >= 0 ){
								var el = $(this).clone(); var tmp = el.html().replace(srchV,"<span style=\"color:var(--clr)\">"+srchV+"</span>"); el.html(tmp);
								el.append("<span class=\"edit_user_btn\" title=\"Edit user\" style=\"background:#ffcccc; padding:0.25rem 0.5rem; border-radius:4px; margin-left:1rem; cursor:pointer;\">✏️</span>");
								$("#find_user_rslt").append( el.prop("outerHTML") );
							}
						})
					}
				})
				
				$(document).on("input", "input[name=\"u_nm\"]", function(){
					$("#find_user_rslt").html("");
					var srchV = $(this).val().toLowerCase().replace("ă","a").replace("â","a").replace("î","i").replace("ș","s").replace("ț","t").replace("_"," ");
					if (srchV != "" && srchV.length > 2){
						$("#find_user > p").each(function(){
							if ( $(this).attr("data-nm").toLowerCase().indexOf( srchV ) >= 0 ){
								var el = $(this).clone(); var tmp = el.html().replace(new RegExp("("+srchV+")", "ig"),"<span style=\"color:var(--clr)\">$1</span>"); el.html(tmp);
								el.append("<span class=\"edit_user_btn\" title=\"Edit user\" style=\"background:#ffcccc; padding:0.25rem 0.5rem; border-radius:4px; margin-left:1rem; cursor:pointer;\">✏️</span>");
								$("#find_user_rslt").append( el.prop("outerHTML") );
							}	
						})
					}
				})
				
				$(document).on("click", "#find_user_rslt > p", function(e){
					if ($(e.target).hasClass("edit_user_btn")) {
						e.stopPropagation();
						openUserEditOverlay($(this));
						return;
					}
					
					$.each($(this).data(), function(k,v){
						$("form").find("[name=\"u_"+k+"\"]").val(v);
						if (k=="tp"){ $("form").find("[name=\"u_"+k+"\"]").trigger("change"); }
					})
					if (window.docsNormalizePhone) window.docsNormalizePhone();
					$("#find_user_rslt").html("");
				})
				
				function openUserEditOverlay($userEl) {
				var userData = $userEl.data();
				$("#overlay").remove();
				
				var overlayHtml = "<div id=\"overlay\" style=\"position:fixed; top:0; left:0; width:100%; height:100%; background:rgba(0,0,0,0.8); z-index:9999; display:flex; align-items:center; justify-content:center; overflow-y:auto; padding:2rem;\">";
				overlayHtml += "<div style=\"background:#fff; padding:2rem; border-radius:8px; max-width:800px; width:100%; max-height:90vh; overflow-y:auto;\">";
				overlayHtml += "<h2 style=\"margin-bottom:1.5rem; color:#e2001a;\">Edit User Data</h2>";
				overlayHtml += "<form id=\"edit_user_form\">";
				overlayHtml += "<input type=\"hidden\" name=\"user_id\" value=\"" + userData.id + "\" />";
				
				overlayHtml += "<label class=\"lbl\"><span class=\"ttl\">Type</span>";
				overlayHtml += "<select name=\"tp\" id=\"edit_user_tp\">";
				overlayHtml += "<option value=\"fiz\" " + (userData.tp === "fiz" ? "selected" : "") + ">Fizic</option>";
				overlayHtml += "<option value=\"jur\" " + (userData.tp === "jur" ? "selected" : "") + ">Juridic</option>";
				overlayHtml += "</select></label>";
				
				overlayHtml += "<label class=\"lbl\"><span class=\"ttl\" id=\"lbl_nm\">Name</span>";
				overlayHtml += "<input type=\"text\" name=\"nm\" value=\"" + (userData.nm || "") + "\" /></label>";
				
				overlayHtml += "<label class=\"lbl\"><span class=\"ttl\" id=\"lbl_cf_idno\">IDNP</span>";
				overlayHtml += "<input type=\"text\" name=\"cf_idno\" value=\"" + (userData.cf_idno || "") + "\" /></label>";
				
				overlayHtml += "<label class=\"lbl\"><span class=\"ttl\" id=\"lbl_tva_dt\">Data nasterii</span>";
				overlayHtml += "<input type=\"text\" name=\"tva_dt\" value=\"" + (userData.tva_dt || "") + "\" /></label>";
				
				overlayHtml += "<label class=\"lbl\"><span class=\"ttl\" id=\"lbl_iban_dt_tk\">Data eliberarii</span>";
				overlayHtml += "<input type=\"text\" name=\"iban_dt_tk\" value=\"" + (userData.iban_dt_tk || "") + "\" /></label>";
				
				overlayHtml += "<label class=\"lbl max\"><span class=\"ttl\">Address</span>";
				overlayHtml += "<textarea name=\"adr\" rows=\"2\">" + (userData.adr || "") + "</textarea></label>";
				
				overlayHtml += "<label class=\"lbl\"><span class=\"ttl\">Phone</span>";
				overlayHtml += "<input type=\"text\" name=\"phn\" value=\"" + (userData.phn || "") + "\" /></label>";
				
				overlayHtml += "<label class=\"lbl\"><span class=\"ttl\">Email</span>";
				overlayHtml += "<input type=\"email\" name=\"eml\" value=\"" + (userData.eml || "") + "\" /></label>";
				
				overlayHtml += "<div style=\"display:flex; gap:1rem; margin-top:2rem;\">";
				overlayHtml += "<button type=\"button\" id=\"save_user_btn\" style=\"flex:1; padding:1rem; background:#28a745; color:#fff; border:none; border-radius:4px; cursor:pointer;\">Save</button>";
				overlayHtml += "<button type=\"button\" class=\"close_overlay\" style=\"flex:1; padding:1rem; background:#6c757d; color:#fff; border:none; border-radius:4px; cursor:pointer;\">Cancel</button>";
				overlayHtml += "</div></form></div></div>";
				
				$("body").append(overlayHtml);
				
				updateLabels(userData.tp);
				
				$("#edit_user_tp").on("change", function() {
					updateLabels($(this).val());
				});
				
				$(".close_overlay, #overlay").on("click", function(e) {
					if (e.target === this) { $("#overlay").remove(); }
				});
				
				$("#save_user_btn").on("click", function() {
					var formData = {};
					$("#edit_user_form").serializeArray().forEach(function(field) {
						formData[field.name] = field.value;
					});
					
					$.ajax({
						url: "/ajax.php",
						method: "POST",
						data: { tp: "adm", pg: "docs", fn: "edit_user", inp: formData },
						success: function() {
							$("#overlay").remove();
							location.reload();
						},
						error: function() {
							alert("Error updating user!");
						}
					});
				});
			}
			
			function updateLabels(type) {
				if (type === "fiz") {
					$("#lbl_nm").text("Name");
					$("#lbl_cf_idno").text("IDNP");
					$("#lbl_tva_dt").text("Data nasterii");
					$("#lbl_iban_dt_tk").text("Data eliberarii");
				} else {
					$("#lbl_nm").text("SRL");
					$("#lbl_cf_idno").text("IDNO");
					$("#lbl_tva_dt").text("TVA");
					$("#lbl_iban_dt_tk").text("IBAN");
				}
			}

			(function(){
				var params = new URLSearchParams(window.location.search);
				var crmLeadId = params.get("crm_lead_id") || "";
				var crmPhone  = params.get("crm_phone") || "";
				var crmNm     = params.get("crm_nm")    || "";
				var crmBr     = params.get("crm_br")    || "";
				var crmMo     = params.get("crm_mo")    || "";
				if (!crmLeadId && !crmPhone && !crmNm && !crmBr && !crmMo) return;
				if (crmLeadId) {
					$(".doc_pg form").append("<input type=\"hidden\" name=\"crm_lead_id\" value=\"" + parseInt(crmLeadId) + "\">");
				}

				if (crmPhone) {
					var phnField = $("input[name=\"u_phn\"]");
					if (phnField.length) {
						phnField.val(crmPhone);
						if (window.docsNormalizePhone) window.docsNormalizePhone();
					}
				}
				if (crmNm) {
					var nmField = $("input[name=\"u_nm\"]");
					if (nmField.length) nmField.val(crmNm);
				}
				if (crmBr) {
					var brSel = $("select[name=\"br\"]");
					if (brSel.length) {
						// try match by value (case-insensitive)
						var brLow = crmBr.toLowerCase();
						brSel.find("option").each(function(){
							if ($(this).val().toLowerCase() === brLow || $(this).text().toLowerCase() === brLow) {
								brSel.val($(this).val()).trigger("change");
								return false;
							}
						});
						// after brand change, set model
						if (crmMo) {
							setTimeout(function(){
								var moSel = $("select[name=\"mo\"]");
								var moLow = crmMo.split(" ")[0].toLowerCase();
								var matched = moSel.find("option").filter(function(){
									if (!$(this).val()) return false;
									var v = $(this).val().toLowerCase().split("_").join(" ");
									var t = $(this).text().toLowerCase().split("_").join(" ");
									return v === moLow || t === moLow || v.indexOf(moLow) === 0 || t.indexOf(moLow) === 0;
								}).first();
								if (matched.length) {
									moSel.val(matched.val()).trigger("change");
								}
							}, 200);
						}
					}
				}

				$(".doc_pg form").prepend(
					"<div style=\"background:#e8f4fd;border-left:4px solid #2563eb;padding:0.6rem 1rem;margin-bottom:1rem;font-size:0.82rem;border-radius:0 4px 4px 0;\">" +
					"📋 <strong>Date precompletate din CRM</strong>" +
					(crmNm           ? " · Client: <strong>" + $("<span>").text(crmNm).html() + "</strong>" : "") +
					(crmPhone        ? " · Telefon: <strong>" + $("<span>").text(crmPhone).html() + "</strong>" : "") +
					((crmBr||crmMo)  ? " · Mașina: <strong>" + $("<span>").text((crmBr+" "+crmMo).trim()).html() + "</strong>" : "") +
					"</div>"
				);
			})();
			});
			</script>
			
			<div class="doc_pg" data-gr="'.$t_mp[4].'" data-f="'.$t_mp[5].'">
				<div id="find_user_rslt" style="position:fixed; top:0; right:0; z-index:3; background-color:#fffa; padding:1rem; max-height:30vh; overflow-y:scroll;"></div>
				<div id="find_user" class="none">';
					$pdo = $db->prepare('SELECT * FROM '.$prefx.'_docs_u ORDER BY `nm` ASC'); $pdo->execute();
					foreach ($pdo as $r){
						$rtrn .= '<p style="cursor:pointer;"
						data-id="'.$r['id'].'" data-nm="'.$r['nm'].'" data-cf_idno="'.$r['cf_idno'].'" 
						data-tva_dt="'.$r['tva_dt'].'" data-iban_dt_tk="'.$r['iban_dt_tk'].'" data-adr="'.$r['adr'].'" data-phn="'.$r['phn'].'" data-eml="'.$r['eml'].'"
						data-tp="'.$r['tp'].'"
						>'.$r['nm'].' '.$r['cf_idno'].'</p>';
					}
				$rtrn .= '
				</div>
				
				<form target="_self" method="POST" action="/'._ADM_INCL.'/docs_print.php">
					<div class="doc_f" style="text-align:center;">'.strtoupper( strtr($t_mp[4].', '.$t_mp[5], '_', ' ') ).'</div>
					
					<input type="hidden" name="doc_gr" value="'.$_doc_gr_val.'" />
					<input type="hidden" name="doc_f" value="'.$t_mp[5].'" />';

					include(__DIR__.'/../ajax/docs/menu.php');
					
					$rtrn .= '
					<br/>
					<label '.($user_type=='dev'?'':'style="display:none;"').'>Save? <input type="checkbox" name="save_inf" value="1" '.(/*myIp()=='188.244.20.158'*/$user_type=='dev'?'':'checked="checked"').' style="accent-color:#e2001a;" /></label>
					'.($user_type!='dev'?'<input type="hidden" name="save_inf" value="1" />':'').'
					<label style="margin:0 0 0 1rem;">Print<input type="checkbox" name="fn" value="print_it" checked="checked" style="accent-color:#e2001a;" /></label>
					<label style="margin:0 0 0 1rem;">Stampila<input type="checkbox" name="stamp" value="1" style="accent-color:#e2001a;" /></label> <!--checked="checked"-->
					<label style="margin:0 0 0 1rem;">Stampila client<input type="checkbox" name="usr_stamp" value="1" style="accent-color:#e2001a;" /></label>

					<div style="margin-top:1rem; padding:0.75rem 1rem; background:#f8f8f8; border:1px solid #eee; border-radius:6px;">
						<div style="font-size:1rem; color:#e2001a; margin-bottom:0.4rem;">'.($lng['m']['doc_owner_manager'] ?? 'Aparține managerului').':</div>
						<select name="owner_adm" style="width:100% !important; float:none !important; padding:0.4rem 0.75rem; font-size:0.9rem; box-sizing:border-box;">
							<option value="">— '.htmlspecialchars($user_name ?? 'utilizatorul logat').' (implicit) —</option>';
							foreach ($_doc_admins as $_da) {
								$_da_sel = ((int)$_da['id'] === (int)$user_id) ? ' selected' : '';
								$rtrn .= '<option value="'.htmlspecialchars($_da['id']).'"'.$_da_sel.'>'.htmlspecialchars($_da['name']).'</option>';
							}
							$rtrn .= '
						</select>
					</div>

					<input type="submit" style="width:100%; margin:1rem 0; padding:1rem; cursor:pointer;" value="Creați fișier" />
				</form>
			</div>';
		}else{$rtrn .= 'Something went wrong.';}
	}
}

echo $rtrn;
?>