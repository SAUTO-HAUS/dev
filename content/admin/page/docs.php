<?php defined( '_DOIT' ) or die( 'Restricted access' );

$rtrn = '';

//$admin_menu_dev1[$user_type]['docs']

if ( isset($t_mp[4]) ){
	//if ( in_array( $t_mp[4], $admin_menu_dev1[$user_type]['docs'] ) )
	if ( $t_mp[4]=='create' || $t_mp[4]=='add' ){
		
		if ($user_type=='dev'){
			$docs_ar = [
				'cars'=>[
					'sell'=>[ 
						'cesionar'=>'Annexa (Cesiune drept de plată)'
						,'con_plata'=>'Cont de plata'
					    ,'con_arvon'=>'Contract de arvună'
						,'con_arvon_com'=>'Contract de arvună (la comanda)'
						,'com_transport'=>'Comanda pentru transport'
						,'con_intermed'=>'Contract de intermediere [TEST]'
						// ,'vinzare_proc'=>'Contract de vânzare-cumpărare'
						,'vinzare_avans'=>'Contract de vânzare-cumpărare ( avans )'
						,'vinzare_sauto'=>'Contract de vânzare-cumpărare ( Sauto cumparator )'
						,'invoice'=>'Invoice'
					]
				]
			];
		} elseif ($user_type=='x1'){
			$docs_ar = [
				'cars'=>[
					'sell'=>[ 
						'con_intermed'=>'Contract de intermediere [TEST]'
					]
				]
			];
		} else {
			$docs_ar = [
				'cars'=>[
					'sell'=>[ 
						'cesionar'=>'Annexa (Cesiune drept de plată)'
						,'con_plata'=>'Cont de plata'
					    ,'con_arvon'=>'Contract de arvună'
						,'con_arvon_com'=>'Contract de arvună (la comanda)'
						,'com_transport'=>'Comanda pentru transport'
						,'con_intermed'=>'Contract de intermediere [TEST]'
						// ,'vinzare_proc'=>'Contract de vânzare-cumpărare'
						,'vinzare_avans'=>'Contract de vânzare-cumpărare ( avans )'
						,'vinzare_sauto'=>'Contract de vânzare-cumpărare ( Sauto cumparator )'
						,'invoice'=>'Invoice'
					]//,'contract_de_intermediere'=>'Contract de intermediere'
				]
			];
		}
		
		
		$rtrn .= '
		<style>
			#docs > .gr {padding-left:2rem;}
			#docs > .gr > .tp {padding-left:2rem;}
			#docs > .gr > .tp > .its {border-left:1px solid; padding:0 0 0 .1rem;}
			#docs > .gr > .tp > .its > .it {padding:.25rem .25rem; position:relative; transition:.2s; display:block;}
			#docs > .gr > .tp > .its > .it:hover {background-color:var(--clr); color:#fff;}
			#docs > .gr > .tp > .its > .it > .txt {padding:.25rem;}
		</style>
		<div id="docs">';
			foreach($docs_ar as $gr => $ar){$rtrn .= '
				<span>'.strtoupper($gr).'</span><br/>
				<div class="gr">';
					foreach($ar as $tp => $ar2){$rtrn .= '
						<span>'.strtoupper($tp).'</span><br/>
						<div class="tp">
							<div class="its">';
								foreach($ar2 as $k => $v){$rtrn .= '
									<a class="it" href="'.$gr.'/'.$k.'"><span class="txt">'.$v.'</span></a>';
								}
							$rtrn .= '
							</div>';
						$rtrn .= '
						</div>';
					}
				$rtrn .= '
				</div>';
			}
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
			
			.docs > .list {width:100%; font-family:Verdana; font-size:.8rem; border-top: 1px solid; margin:1rem 0 0; padding:1rem 0;}
			.docs > .list input.srch {cursor:auto;}
			
			.docs > .list .rowz {display:flex; cursor:default;}
			.docs > .list .rowz > .col {flex:1; overflow-wrap:anywhere; padding:.5rem .25rem;}
			
			.docs > .list .rowz > .col.max {flex:1 0 12rem;}
			.docs > .list .rowz > .col.med {flex:1 0 6rem;}
			.docs > .list .rowz > .col.min_med {flex:1 0 4rem;}
			.docs > .list .rowz > .col.min {/*flex:1 0 1rem;*/ flex:inherit;}
			
			.sep {width:100%; text-align:center; color:#bf4040; margin:.5rem 0;}
			
			.bx {position:relative; display:block; margin:.2rem 0; transition:.3s;}
			.bx.odd {background-color:#fff;}
			.bx.even {background-color:#fff8f8;}
			.bx.hide {transform:scale(0); opacity:0;}
			.bx:hover {background-color:#f4e0e0;}
			
			.bx .u > .nm {text-transform:capitalize;}
			.bx .nr > .date,
			.bx .it > .vin	{color:#9f9f9f; font-size:.7rem;}
			
			.bx > .info {width:100%; min-height:3rem;}
			
			.bx > .btns {width:0; height:100%; overflow:hidden; display:flex; flex-flow:row wrap; justify-content:center; opacity:0; position:absolute; top:0; right:0; background-color:#f4e0e0ee; transition:.1s;}
			.bx > input[name="btns_act"]:checked ~ .btns {width:100%; opacity:1;}
			.bx > .btns > input[type="submit"] {background:none; color:inherit; border:none; font:inherit; outline:inherit;}
			.bx > .btns > .btn {height:inherit; align-items:center; display:flex; padding:0 1rem; cursor:pointer; align-self:center; border-radius:.75rem; margin:0 1rem; transition:.25s;}
			.bx > .btns > .btn:hover {background-color:#bf4040; color:#fff;}
			
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
			$(document).ready(function(){
				var reqType = "adm";
				var reqPage = "docs";
				
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
						
						// Restore scroll position to the edited document
						if (scrollPosition) {
							setTimeout(function() {
								window.scrollTo(0, parseInt(scrollPosition));
								localStorage.removeItem("docsScrollPosition");
							}, 1);
						} else {
							// Fallback: scroll to the edited document
							setTimeout(function() {
								editedDoc[0].scrollIntoView({ behavior: "smooth", block: "center" });
							}, 1);
						}
					}
					localStorage.removeItem("keepButtonsVisible");
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
								if (e.target !== this){ return; }
								if ( $("#content > .tmp_form").length ){ $("#content > .tmp_form").remove(); } //remove old one form
								$("#content").prepend("<form class=\"tmp_form none\" target=\"_blank\" method=\"POST\" action=\"/'._ADM_INCL.'/docs_print.php\"></form>"); //add new one form to html
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
									+"<span style=\"color: #6c757d; font-weight: 500;\">📝 Created by:</span>"
									+"<span style=\"color: #e2001a; font-weight: 600; background-color: rgba(226,0,26,0.1); padding: 0.25rem 0.5rem; border-radius: 4px;\">"+createdBy+"</span>"
									+"</div>"
									+"<div style=\"display: flex; align-items: center; gap: 0.5rem;\">"
									+"<span style=\"color: #6c757d; font-weight: 500;\">✏️ Last edited by:</span>"
									+"<span style=\"color: #495057; font-weight: 600; background-color: rgba(73,80,87,0.1); padding: 0.25rem 0.5rem; border-radius: 4px;\">"+lastEditedBy+"</span>"
									+"</div>"
									+"</div>"
									+"</div>"
								);
								
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
								base.find("input[type=\"submit\"]").trigger("click");
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
							$.each( data["inp"], function(k,v){
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
							
							// Close the overlay and reload AFTER successful save
							$("#overlay").hide();
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
					$("#its_bx > .lbl [data-n=\"x\"]").data( "n", next ).attr( "data-n", next ).removeAttr("disabled");
					$(this).data( "next", (next+1) ).attr( "data-next", (next+1) );
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
				
				$i=1; $date = '';
				$currentYear = date('Y');
				$pdo = $db->prepare('SELECT 
					u.id u_id, u.nm u_nm, u.tp u_tp, u.cf_idno u_cf_idno, u.tva_dt u_tva_dt, u.iban_dt_tk u_iban_dt_tk, u.adr u_adr, u.phn u_phn, u.eml u_eml, 
					c.*, 
					c.last_edited_by
					FROM 
						'.$prefx.'_docs_u AS u 
						INNER JOIN 
						'.$prefx.'_docs_ctlg AS c 
					ON u.id=c.u 
					WHERE YEAR(c.date) = :currentYear
					ORDER BY c.date DESC, c.id DESC'); 
				$pdo->execute(['currentYear' => $currentYear]);
					
				$rtrn .= '
				<div class="rowz hdr">
					<div class="col">Date</div>
					<div class="col">Doc</div>
					<div class="col min_med">Nr</div>
					<div class="col max">Contragent</div>
					<div class="col">Suma</div>
					<div class="col max">Info</div>
					<div class="col min">Added by</div>
					<div class="col min">V</div>
					<div class="col min">X</div>
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
					<label class="bx '.( $i%2>0?'odd':'even' ).'" data-id="'.$r['id'].'" data-u_id="'.$r['u_id'].'" data-tags="'.strtr(mb_strtolower( $r['u_nm'].' '.$r['u_cf_idno'].' '.$r['u_tp'].' '.$r['abr'].$r['y'].$r['q'].'/'.$r['n'].' '.( isset($inf['br'])?$inf['br']:'' ).' '.( isset($inf['mo'])?$inf['mo']:'' ).' '.( isset($inf['vin'])?$inf['vin']:'' ).' '.( isset($inf['prc'])?$inf['prc']:'' ).' '.date( 'd.m.Y', strtotime( $r['date'] ) ).' '.$r['f'].' '.( isset($adm_ar[ $r['adm'] ])?$adm_ar[ $r['adm'] ]:$r['adm'] ), 'UTF-8' ), ['ă'=>'a', 'â'=>'a', 'î'=>'i', 'ș'=>'s', 'ț'=>'t', '_'=>' ']).'">
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
							'.(isset($inf['yr'])?'data-yr="'.$inf['yr'].'"':'').' '.(isset($inf['clr'])?'data-clr="'.$inf['clr'].'"':'').' '.(isset($inf['loc'])?'data-loc="'.$inf['loc'].'"':'').' 
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
							<div class="col min_med">'.$r['abr'].$r['y'].$r['q'].'/'.$r['n'].'</div>
							<div class="col max" data-id="'.$r['u_id'].'" data-tp="'.$r['u_tp'].'"><span class="u_nm">'.mb_convert_case($r['u_nm'], MB_CASE_TITLE, 'UTF-8').'</span> <span class="u_cf_idno">'.$r['u_cf_idno'].'</span></div>
							<div class="col"><span class="prc">'.( isset($inf['prc'])?$inf['prc']:'-' ).'</span></div>
							<div class="col max">'.$br_mo_vin.'</div>
							<div class="col min">'.( isset($adm_ar[ $r['adm'] ])?$adm_ar[ $r['adm'] ]:$r['adm'] ).'</div>
							<div class="col min">-</div>
							<div class="col min">-</div>
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
					</label>';
					$i++;
				}
			$rtrn .= '
		</div>
	</div>
	<script>
	$(document).ready(function(){
		var years = new Set();
		var currentYear = new Date().getFullYear();
		
		$(".docs > .list > .bx").each(function(){
			var dateStr = $(this).find(".values").data("date");
			if (dateStr) {
				var year = new Date(dateStr).getFullYear();
				years.add(year);
				$(this).attr("data-year", year);
			}
		});
		
		var sortedYears = Array.from(years).sort(function(a, b){ return b - a; });
		var yearFilter = $("<select style=\"padding:0.5rem 1.5rem 0.5rem 0.5rem; margin:0 0 0 1rem; border:1px solid #ddd; background:#fff; cursor:pointer; display:inline-block; vertical-align:top;\"></select>");
		yearFilter.append("<option value=\"all\">ALL</option>");
		sortedYears.forEach(function(year){
			yearFilter.append("<option value=\"" + year + "\">" + year + "</option>");
		});
		
		var yearWrapper = $("<span style=\"position:relative; display:inline-block; float:right; margin-right:1rem;\"></span>");
		yearWrapper.append(yearFilter);
		yearWrapper.append("<span style=\"position:absolute; right:0.1rem; top:50%; transform:translateY(-50%); pointer-events:none; font-size:1rem;\">▼</span>");
		
		$(".docs > .find.doc").append(yearWrapper);
		yearFilter.val(currentYear);
		
		yearFilter.on("change", function(){
			var year = $(this).val();
			if (year === "all") {
				$(".docs > .list > .bx").removeClass("none");
			} else {
				$(".docs > .list > .bx").each(function(){
					$(this).attr("data-year") == year ? $(this).removeClass("none") : $(this).addClass("none");
				});
			}
		}).trigger("change");
	});
	</script>';
}elseif ( isset($t_mp[5]) ){
		if ( file_exists(_ADM_INCL.'/docs/'.$t_mp[4].'/'.$t_mp[5].'.php') ){
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
					if (!ok){ e.preventDefault(); }
					else {
						setTimeout(function (){
							window.location.href = "/"+ Cookies.get("lang") +"/adminsauto/docs/ctlg";
						}, 1000);
					}
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
					$("#its_bx > .lbl [data-n=\"x\"]").data( "n", next ).attr( "data-n", next ).removeAttr("disabled");
					$(this).data( "next", (next+1) ).attr( "data-next", (next+1) );
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
								$("#find_user_rslt").append( el.prop("outerHTML") );
							}
						})
					}
				})
				
				$(document).on("click", "#find_user_rslt > p", function(){
					$.each($(this).data(), function(k,v){
						$("form").find("[name=\"u_"+k+"\"]").val(v);
						if (k=="tp"){ $("form").find("[name=\"u_"+k+"\"]").trigger("change"); }
					})
					$("#find_user_rslt").html("");
				})
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
				
				<form target="_blank" method="POST" action="/'._ADM_INCL.'/docs_print.php">
					<div class="doc_f" style="text-align:center;">'.strtoupper( strtr($t_mp[4].', '.$t_mp[5], '_', ' ') ).'</div>
					
					<input type="hidden" name="doc_gr" value="'.$t_mp[4].'" />
					<input type="hidden" name="doc_f" value="'.$t_mp[5].'" />';
					
					include(__DIR__.'/../ajax/docs/menu.php');
					
					$rtrn .= '
					<br/>
					<label '.($user_type=='dev'?'':'style="display:none;"').'>Save? <input type="checkbox" name="save_inf" value="1" '.(/*myIp()=='188.244.20.158'*/$user_type=='dev'?'':'checked="checked"').' style="accent-color:#e2001a;" /></label>
					'.($user_type!='dev'?'<input type="hidden" name="save_inf" value="1" />':'').'
					<label style="margin:0 0 0 1rem;">Print<input type="checkbox" name="fn" value="print_it" checked="checked" style="accent-color:#e2001a;" /></label>
					<label style="margin:0 0 0 1rem;">Stampila<input type="checkbox" name="stamp" value="1" style="accent-color:#e2001a;" /></label> <!--checked="checked"-->
					<label style="margin:0 0 0 1rem;">Stampila client<input type="checkbox" name="usr_stamp" value="1" style="accent-color:#e2001a;" /></label>
					
					<input type="submit" style="width:100%; margin:1rem 0; padding:1rem; cursor:pointer;" value="Creați fișier" />
				</form>
			</div>';
		}else{$rtrn .= 'Something went wrong.';}
	}
}

echo $rtrn;
?>