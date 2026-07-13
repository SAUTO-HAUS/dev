//-------------------------------------------------------------------------------------- ON READY -------------------------------------------------------
$(document).ready(function() {

$('body').attr('data-js','1').data('js','1');
var isMobile = $('body').data('mbl');

var $temp = $('<input>');
var $url = $(location).attr('href');
/*
$('.cp_url').on('click', function() {
	$('body').append($temp);
	$temp.val($url).select();
	document.execCommand('copy');
	$temp.remove();
	var zOTxt = $(this).html();
	var zOStl = $(this).attr('style');
	var zW = $(this).width();
	var zNTxt = $(this).parent().data('lng-c');
	$(this).html(zNTxt).css({'min-width':zW+'px'}).delay(1000).queue(function(){
		if(typeof zOStl === "undefined"){$(this).removeAttr('style');}else{$(this).attr('style', zOStl);}
		$(this).html(zOTxt); $(this).dequeue();
	});
})
*/

$('main > .spc_bx > .prc > .cntr > .btn').on('click', function(){
	var nPrc; var par = $(this).parent(); var val = par.children('.val'); var prcEl = $(this).parent().parent().children('.val').children('.i'); var o_prcEl = $(this).parent().parent().children('.o_val').children('.i'); var prc = ( par.data('prc') )*1; var o_prc = ( par.data('o_prc') )*1; var cnt = ( val.text() )*1;
	
	if ( $(this).hasClass('mns') ){
		if ( cnt > 1 ){
			if (cnt==4){cnt-=2;}else{cnt--;}//avoid 3
			val.text( cnt );
			prcEl.text( (prc * cnt).formatNum(0) );
			if (o_prc!=0){
				o_prcEl.text( (o_prc * cnt).formatNum(0) );
			}
		}
	} else {
		if ( cnt < 4 ){
			if (cnt==2){cnt+=2;}else{cnt++;}//avoid 3
			val.text( cnt );
			prcEl.text( (prc * cnt).formatNum(0) );
			if (o_prc!=0){
				o_prcEl.text( (o_prc * cnt).formatNum(0) );
			}
		}
	}
})

$('main > .spc_bx > .doit > .btn.msg').on('click', function(){
	overlay('open', $(this).children('.data'), 'child');
})

$(document).on('click', '#snd_msg .btn.sbmt', function(){
	var chkd = false;
	$(this).data('send', $(this).val() );
	$(this).parent().children('.imp').each( function(){ if ( $(this).val() ){ chkd = true; } } );
	
	if (chkd == true){
		if ( $(this).parent().children('.agmt').children('.cbx.cnfrm').is(':checked') == true ) {
			$(this).addClass('ghost').val( $(this).data('sending')+'...' );
			var data = {}; data['tp'] = 'ste'; data['fn'] = 'snd_msg';
			$(this).parent().children('.use').each(function(){ data[$(this).attr('name')] = $(this).val(); })
			ajaxIt(data);
		}
	}else{
		$(this).val( $(this).data('req_fld') ).addClass('ghost').delay(3000).queue(function(){ $(this).val( $(this).data('send') ).removeClass('ghost').dequeue();})
	}
})

//-----FILTER---[START]--------------------------------------------------

// /^\d*$/.test(value);

$(document).on("click", "#fltr .md_gr .sub .srch", function(){
	var zBr = $(this).data("br");
	$("#fltr .md_gr .srch").not($(this).parent().children(".srch")).prop("checked", false).attr("checked", false);
	$("#fltr .md_gr > #br_"+zBr).prop("checked", true).attr("checked", "checked").trigger("change");
})

$(document).on("click", "#fltr .md_gr > .srch", function(){
	$("#fltr .md_gr > .sub > .srch").prop("checked", false).attr("checked", false);
})

$(document).on("change", "#fltr .sel.br", function(){
	var selVal =  $("option:selected", this).val();
	var nOpt = '';
	if ( selVal != ""){
		$("#fltr .sel.mo option:not(.x)").remove();
		$("#fltr .list.mo > option[data-parent='"+selVal+"']").each(function(){
			nOpt += $(this).prop('outerHTML');
		})
		$("#fltr .sel.mo").append(nOpt);
	}else{
		$("#fltr .sel.mo option:not(.x)").remove();
		$("#fltr .sel.mo").append( $("#fltr .list.mo").html() );
	}
});

/*
$("#fltr .sel.br").on("change", function(){
	var selVal =  $("option:selected", this).val();
	if ( selVal != ""){
		$("#fltr .sel.mo").val( $("option:first", this ).val() ).trigger('change');//.addClass("srch act")
		$("#fltr .sel.mo > option:not(.x)").attr('hidden','hidden');
		$("#fltr .sel.mo > option[data-parent='"+selVal+"']").removeAttr('hidden');
	}else{$("#fltr .sel.mo").val( $("option:first", this ).val() ).trigger('change');}//.removeClass("srch act")
});
*/

//$("#fltr .sel.gr").trigger('change');

$(document).on("change", "#fltr .srch", function(e){
	var zVals = "";
	var zVal = $(this).val();
	var zName = "";
	
	if (zVal!=''&&zVal!=null){ $(this).addClass('y'); }else{ $(this).removeClass('y'); }
	
	if ( $(this).hasClass("inp") && ($(this).hasClass("fr") || $(this).hasClass("to")) ){
		$(this).parent().children(".inp").each(function(){
			zName = $(this).attr("data-tg");
			if (zVals!=""){zVals += "-"}
			if ( $(this).val().length !== 0 && $(this).val()!="0" && $(this).val()!="" ){ 
				zVals += $(this).val(); 
			}else{
				if ( $(this).hasClass("fr") ){ zVals += "x"; }
				else if ( $(this).hasClass("to") ){ zVals += "x"; }
			}
		})
		$("#fltr > .ctrl > .btns > .sbmt").data(zName, zVals).attr("data-" + zName, zVals);
	}else if ( $(this).hasClass("sel") && ($(this).hasClass("fr") || $(this).hasClass("to")) ){
		$(this).parent().children("select.fr, select.to").each(function(){
			zName = $(this).attr("data-tg");
			if (zVals!=""){zVals += "-"}
			if ( $(this).val() && $(this).val().length !== 0 && $(this).val()!="0" ){ 
				zVals += $(this).val(); 
			}else{
				if ( $(this).hasClass("fr") ){ zVals += "x"; }
				else if ( $(this).hasClass("to") ){ zVals += "x"; }
			}
		})
		$("#fltr > .ctrl > .btns > .sbmt").data(zName, zVals).attr("data-" + zName, zVals);
	}else if ( $(this).hasClass("inp") ){
		$(this).parent().children(".inp").each(function(){
			zName = $(this).attr("data-tg");
			if (zVals!=""){zVals += "-"}
			if ( $(this).val().length !== 0 && $(this).val()!="0" && $(this).val()!="" ){ 
				zVals += $(this).val(); 
			}else{
				if ( $(this).hasClass("fr") ){ zVals += "x"; }
				else if ( $(this).hasClass("to") ){ zVals += "x"; }
			}
		})
		$("#fltr > .ctrl > .btns > .sbmt").data(zName, zVals).attr("data-" + zName, zVals);
	}else if ( ($(this).hasClass("sel") && !$(this).hasClass("fr") && !$(this).hasClass("to")) || $(this).hasClass("rad") ){
		zName = $(this).attr("name");
		if ( $(this).val() ){zVals += $(this).val();}else{zVals += "";}
		if ( zName=="br" ){ 
    // Only clear model, preserve other filters
    $("#fltr > .ctrl > .btns > .sbmt").data("mo", "").attr("data-mo", "");
}
		//zVals += $(this).val() ? $(this).val() : "";
	}
	
	var attrs = {};
	attrs["link"] = $("#fltr > .ctrl > .btns").data("link");
	if (zVals=="x-x"){ $("#fltr > .ctrl > .btns > .sbmt").data(zName, "").attr("data-" + zName, ""); }
	else if (zVals!=null && zVals!=""){attrs[zName] = zVals;}
	else if (zVals==null || zVals==""){attrs[zName] = "";}
	
	$("#fltr > .ctrl > .btns > .sbmt").data( attrs );
	
	var zSbmt = $("#fltr > .ctrl > .btns > .sbmt");
	
	var zCnt2 = 0;
	$("#fltr .srch").not(".fr, .to").each(function(){
		if ( ($(this).hasClass("inp") && $(this).val()!="") || ( $(this).hasClass("sel") && $(this).val()!="" && $(this).val()!=null ) ){
		//if ( ( $(this).hasClass("inp") || $(this).hasClass("sel") ) && $(this).val()!="" ){
			zSbmt.data( $(this).data("tg"), zSbmt.data($(this).data("tg")) ).attr( "data-"+$(this).data("tg"), zSbmt.data($(this).data("tg")) );
			zCnt2=1;
		}
	})
	
	//if (zCnt2 == 1){ $("#fltr > .ctrl > .btns > .sbmt:not('.anim')").addClass("anim"); }else{ $("#fltr > .ctrl > .btns > .sbmt.anim").removeClass("anim"); }
	
	
	var zHref = "";
	var zData = $("#fltr > .ctrl > .btns > .sbmt").data();
	var zCnt = 0;
	// Import regions (ordercars) live on a clean path like brand does: /ordercars/korea.
	// BUT the path can only hold ONE thing: if a brand is selected, the region must go to
	// the query string (?ic=korea) instead, otherwise it would be lost.
	var ocRegionSlugs = ["korea", "europe", "usa"];
	var icIsRegion = zData.ic && ocRegionSlugs.indexOf(String(zData.ic).toLowerCase()) !== -1;
	var icInPath = icIsRegion && !zData.br; // region on the path only when there's no brand
	if (zData.br) {
		var brand = zData.br.replace(/_/g, '-');
		zHref = zData.link + brand;
		if (zData.mo) {
			var model = zData.mo.replace(/_/g, '-');
			zHref += "/" + model;
		}
		zCnt++;
	} else if (icInPath) {
		// Region on a clean path; filters/sorting still go in the query string below.
		zHref = zData.link + String(zData.ic).toLowerCase();
	} else {
		zHref = $("#fltr > .ctrl > .btns").data("def");
	}
	
	// Reset our query parameters
	var queryParams = [];
	var hasDetailedFilters = false;
	
	// Check for body type in URL
	// First look for bt parameter in the URL - could be in the main query string
	// or after a second question mark (which is an error but we'll handle it)
	var fullUrl = window.location.href;
	var btMatch = fullUrl.match(/[?&]bt=([^&#]*)/i);
	if (btMatch && btMatch[1] && !zData.bt) {
		zData.bt = btMatch[1];
	}
	
	// Look for color parameter too
	var clrMatch = fullUrl.match(/[?&]clr=([^&#]*)/i);
	if (clrMatch && clrMatch[1] && !zData.clr) {
		zData.clr = clrMatch[1];
	}

	// Preserve import region (korea|usa|europe) selected via the ordercars buttons.
	// It can arrive either as a clean path (/ordercars/korea) or a legacy ?ic= query.
	if (!zData.ic) {
		var icPathMatch = window.location.pathname.match(/\/ordercars\/(korea|europe|usa)(?:\/|$)/i);
		var icMatch = fullUrl.match(/[?&]ic=([^&#]*)/i);
		if (icPathMatch && icPathMatch[1]) {
			zData.ic = icPathMatch[1].toLowerCase();
		} else if (icMatch && icMatch[1]) {
			zData.ic = icMatch[1];
		}
	}
	
	// Ensure all filter parameters are captured
	// First check for input fields (ranges and single values)
	$("#fltr .inp").each(function() {
		var filterKey = $(this).data("tg");
		var filterVal = $(this).val();
		
		if (filterKey && filterVal && filterVal !== "" && !zData[filterKey]) {
			zData[filterKey] = filterVal;
			console.log("Adding input filter:", filterKey, filterVal);
		}
	});
	
	$("#fltr .sel").not(".fr, .to").each(function() {
		var filterKey = $(this).attr("name");
		var filterVal = $(this).val();
		
		if (filterKey && filterVal && filterVal !== "" && filterVal !== null && !zData[filterKey]) {
			zData[filterKey] = filterVal;
			console.log("Adding select filter:", filterKey, filterVal);
		}
	});
	
	// Check for radio buttons
	$("#fltr .rad:checked").each(function() {
		var filterKey = $(this).attr("name");
		var filterVal = $(this).val();
		
		if (filterKey && filterVal && filterVal !== "" && !zData[filterKey]) {
			zData[filterKey] = filterVal;
			console.log("Adding radio filter:", filterKey, filterVal);
		}
	});
	
	queryParams.push("tg=fltr");
	hasDetailedFilters = true;

	// Re-evaluate region-in-path AFTER zData.ic has been fully resolved (from data-ic OR
	// the path above). Region goes on the path ONLY when there's no brand (path holds one
	// thing); with a brand, icInPath stays false so ?ic= is kept in the query below.
	icIsRegion = zData.ic && ocRegionSlugs.indexOf(String(zData.ic).toLowerCase()) !== -1;
	icInPath = icIsRegion && !zData.br;
	if (icInPath && zHref.indexOf("/" + String(zData.ic).toLowerCase()) === -1) {
		zHref = zData.link + String(zData.ic).toLowerCase();
	}

	// Add all filter parameters to the URL, now properly merged
	$.each(zData, function(key, value) {
		// Skip brand and model (in path), link (internal), and the region when it is
		// already carried on the clean path (/ordercars/korea) to avoid a duplicate ?ic=.
		if (key === 'br' || key === 'mo' || key === 'link') { return; }
		if (key === 'ic' && icInPath) { return; }
		if (value !== "") {
			queryParams.push(key + "=" + value);
		}
	});
	
	zHref += "?" + queryParams.join("&");
	
	// Ensure URL has no duplicate parameters or multiple question marks
	if (zHref.indexOf("?") !== zHref.lastIndexOf("?")) {
		// URL contains multiple question marks - fix it
		var urlBase = zHref.split("?")[0];
		var allParams = {};
		
		// Parse all parameters from all query strings
		var parts = zHref.split("?");
		for (var i = 1; i < parts.length; i++) {
			var partParams = parts[i].split("&");
			for (var j = 0; j < partParams.length; j++) {
				var param = partParams[j].split("=");
				if (param.length === 2 && param[0]) {
					// Only keep the first occurrence of each parameter
					if (!allParams[param[0]]) {
						allParams[param[0]] = param[1];
					}
				}
			}
		}
		
		// Rebuild query string correctly
		var newQueryString = "?";
		var paramCount = 0;
		for (var key in allParams) {
			if (paramCount > 0) newQueryString += "&";
			newQueryString += key + "=" + allParams[key];
			paramCount++;
		}
		
		// Set the corrected URL
		zHref = urlBase + newQueryString;
		console.log("Fixed malformed URL:", zHref);
	}
	
	$("#fltr > .ctrl > .btns > .sbmt").attr("href", zHref);
	
})

$(document).on("input", "#fltr > .ext > .data > .srch", function(e){
	//if ( $(this).val().length === 0 ){ $(this).addClass("empty") }else{ $(this).removeClass("empty") }
	$(this).trigger('change');
})

$(document).on("focus", "#fltr .inp[list]", function(){
	var $input = $(this);
	var currentVal = $input.val();
	if (currentVal !== "") {
		$input.data("prev-val", currentVal);
		$input.val("");
		setTimeout(function(){
			if ($input.val() === "") {
				$input.attr("placeholder", currentVal);
			}
		}, 50);
	}
})

$(document).on("blur", "#fltr .inp[list]", function(){
	var $input = $(this);
	var prevVal = $input.data("prev-val");
	if ($input.val() === "" && prevVal && prevVal !== "") {
		$input.val(prevVal);
	}
	$input.removeData("prev-val");
})

$(document).on("click", "#fltr > .ext > .data > .act, #fltr > .ext > .data > .inp", function(){
	$("#fltr > .ext > .data > .act").not(this).prop("checked", false).attr("checked", false);
})

$(document).on("click", "#fltr > .ctrl > .btns > .advn", function(){
	if ( $(this).hasClass("act") ){ $("#fltr > .ext.act").removeClass("act"); $("#fltr > .base > .bt").removeClass("ghost"); $(this).removeClass("act"); }
	else{ $("#fltr > .ext:not('.act')").addClass("act"); $("#fltr > .base > .bt").addClass("ghost"); $(this).addClass("act"); }

	// Fix URLs if they have multiple question marks
	var currentHref = $("#fltr > .ctrl > .btns > .sbmt").attr("href");
	if (currentHref && currentHref.indexOf("?") !== currentHref.lastIndexOf("?")) {
		var baseUrl = currentHref.split("?")[0];
		var params = {};
		
		// Extract all parameters from the malformed URL
		var urlParts = currentHref.split("?");
		for (var i = 1; i < urlParts.length; i++) {
			var partParams = urlParts[i].split("&");
			for (var j = 0; j < partParams.length; j++) {
				var param = partParams[j].split("=");
				if (param.length === 2 && param[0]) {
					params[param[0]] = param[1];
				}
			}
		}
		
		// Reconstruct URL properly
		var queryString = "?";
		var paramCount = 0;
		for (var key in params) {
			if (paramCount > 0) queryString += "&";
			queryString += key + "=" + params[key];
			paramCount++;
		}
		
		// Update the href
		var newHref = baseUrl + queryString;
		$("#fltr > .ctrl > .btns > .sbmt").attr("href", newHref);
	}
})

$(document).on("click", "#fltr > .ctrl > .btns > .unst", function(){
	$("#fltr > .ctrl > .btns > .sbmt.anim").removeClass("anim");
	$("#fltr .sel:not('.gr')").val( $("option:first", this ).val() ).trigger('change');
	$("#fltr .rad").prop("checked", false).attr("checked", false);
	//$("#fltr [type=\"checkbox\"]").prop("checked", false).attr("checked", false);
	$("#fltr .srch.inp").val("");

	// Clearing filters should keep the current import region: reset to /ordercars/<region>
	// instead of the bare /ordercars, so we don't drop out of Korea/Europe/USA.
	var unsetRegionSlugs = ["korea", "europe", "usa"];
	var unsetIcMatch = window.location.pathname.match(/\/ordercars\/(korea|europe|usa)(?:\/|$)/i);
	var unsetRegion = unsetIcMatch ? unsetIcMatch[1].toLowerCase() : "";

	$.each( $("#fltr > .ctrl > .btns > .sbmt").data(), function(k, v) {
		$("#fltr > .ctrl > .btns > .sbmt").attr("data-"+k, "").data(k, "").trigger("change");
	});
	var unsetHref = unsetRegion
		? ($("#fltr > .ctrl > .btns").data("link") + unsetRegion)
		: $("#fltr > .ctrl > .btns").data("def");
	$("#fltr > .ctrl > .btns > .sbmt").attr("href", unsetHref);
})

if ( $('#fltr').length ){
	// Apply .y class to filters that already have values on page load
	$("#fltr select.srch").each(function(){
		var val = $(this).val();
		if (val !== "" && val !== null) {
			$(this).addClass("y");
		}
	});
	$("#fltr input.srch").each(function(){
		var val = $(this).val();
		if (val !== "" && val !== null && val !== "0") {
			$(this).addClass("y");
		}
	});
	
	var initData = $("#fltr > .ctrl > .btns > .sbmt").data();
	var initHref = "";
	// Region (korea|europe|usa) lives on the clean path, same as brand.
	var initRegionSlugs = ["korea", "europe", "usa"];
	var initIc = initData.ic ? String(initData.ic).toLowerCase() : "";
	if (initIc && initRegionSlugs.indexOf(initIc) === -1) { initIc = ""; }
	if (!initIc) {
		var initIcPath = window.location.pathname.match(/\/ordercars\/(korea|europe|usa)(?:\/|$)/i);
		if (initIcPath) { initIc = initIcPath[1].toLowerCase(); }
	}

	// Region goes on the path only when there's no brand; with a brand it stays in the query.
	var initIcInPath = initIc && !(initData.br && initData.br !== "");
	if (initData.br && initData.br !== "") {
		var brand = initData.br.replace(/_/g, '-');
		initHref = $("#fltr > .ctrl > .btns").data("link") + brand;
		if (initData.mo && initData.mo !== "") {
			var model = initData.mo.replace(/_/g, '-');
			initHref += "/" + model;
		}
	} else if (initIcInPath) {
		initHref = $("#fltr > .ctrl > .btns").data("link") + initIc;
	} else {
		initHref = $("#fltr > .ctrl > .btns").data("def");
	}

	var initParams = ["tg=fltr"];
	// When a brand+region combo is active, make sure ?ic= is present even if data-ic was empty.
	if (initIc && !initIcInPath && (!initData.ic || initData.ic === "")) {
		initParams.push("ic=" + initIc);
	}
	$.each(initData, function(key, value) {
		if (key === 'br' || key === 'mo' || key === 'link') { return; }
		if (key === 'ic' && initIcInPath) { return; } // region is already in the path
		if (value !== "" && value !== undefined) {
			initParams.push(key + "=" + value);
		}
	});
	
	initHref += "?" + initParams.join("&");
	
	$("#fltr > .ctrl > .btns > .sbmt").attr("href", initHref);
}

//FIXED
if ( $('#fltr').length ){
	var elH = $('#fltr').outerHeight(true),
	elB = $('#fltr').offset().top + elH + 300,
	fltrLng = $('#fltr').data('lng');
	function filter_pos(){
		var wT = $(document).scrollTop();
		
		if (wT > elB){
			if ( $('#fltr.fix').length === 0 ){ $('#fltr').clone().addClass('fix out').appendTo('body').delay(400).queue(function(){ $(this).removeClass('out'); $(this).dequeue(); }).append('<div class="btn opcl" data-act="0"><div class="img"></div><div class="txt">'+fltrLng+'<div></div>'); $('#fltr:not(".fix")').attr('id', 'fltr_base'); }
		}else{
			if ( $('#fltr.fix').length ){ $('#fltr.fix').addClass('out').delay(400).queue(function(){ $(this).remove(); $(this).dequeue(); }); $('#fltr_base').attr('id', 'fltr'); }
		}
	}
	$(window).on('scroll', function(){ filter_pos(); })
	filter_pos();
	
	$(document).on('click', '#fltr.fix > .btn.opcl', function(){
		if ( $('#fltr.fix.act').length === 0 ){ $('#fltr.fix').addClass('act'); }
		else{ $('#fltr.fix.act').removeClass('act'); }
	})
}

//-----FILTER---[END]----------------------------------------------------
//-----SLIDER---[START]--------------------------------------------------

$("#ann").css("display","block");

function slider(state){
	
	var intervalTime = $('#slider').attr('data-in_t')*1000;
	var delayTime = $('#slider').attr('data-dl_t')*1000;
	
	if (state == 'start'){
		
		$('#slider > .timebar > .fill').css({'transition':'width '+(intervalTime/1000)+'s linear 0s'}).removeClass('min').addClass('max');
		
		if ( $('#slider > .slide.active').children('.main_txt').text() ){
			$('#slider > .text').css('bottom','0%');
			$('#slider > .text > .main_txt').text( $('#slider > .slide.active > .main_txt').text() );
			$('#slider > .text > .xtra_txt').text( $('#slider > .slide.active > .xtra_txt').text() );
		}
		
		slideTimer = setInterval(function() {
			
			$('#slider > .timebar > .fill').removeClass('max').addClass('min');
			
			$('#slider > .timebar > .fill').delay(1).queue(function(){
				$(this).removeClass('min').addClass('max');
				$(this).dequeue();
			});
			
			var active = $('#slider > .slide.active');
			var number = active.attr('numb');
			var count = $('#slider > .slide').length;
			var next = ( (number*1+1) > count ) ? 1 : (number*1+1);
			
			$('#slider > .text').css('bottom','-50%');
			
			active.removeClass('active').delay(delayTime).queue(function(){
				//$(this).css({ 'right':'-100%' });
				$(this).dequeue();
			});
			
			$('#slider > .slide[numb="'+next+'"]').addClass('active').show().css('right','0%').delay(delayTime).queue(function(){
				
				if ( $(this).children('.main_txt').text() ){
					$('#slider > .text').css('bottom','0%');
					$('#slider > .text > .main_txt').text( $(this).children('.main_txt').text() );
					$('#slider > .text > .xtra_txt').text( $(this).children('.xtra_txt').text() );
				}
				$(this).dequeue();
			});
			
		}, intervalTime);
	}
	if (state == 'stop'){
		clearInterval(slideTimer);
		$('#slider > .timebar > .fill').removeClass('max').addClass('min');
		$('#slider > .slide').dequeue();
	}
	
}

slider('start');

$(document).on('mouseenter', '#slider', function(){ slider('stop'); });
$(document).on('mouseleave', '#slider', function(){ slider('start'); });

/*$(document).on({
	'mouseenter' : slider('stop'),
	'mouseleave' : slider('start')
}, '#slider');*/

//-----SLIDER---[END]----------------------------------------------------

$('#offers_page > .box > .button').click(function(){
	if ( $(this).attr('active') != '0' ){
		$(this).attr('active','0');
		$(this).children('.img').css({'background-color':'#aaa'});
		$(this).children('.title').css({'background-color':'transparent', 'color':'#333'});
		$(this).parent().children('.content').css({'max-height':'0'});
	}
	else {
		$(this).attr('active','1');
		$(this).children('.img').css({'background-color':''});
		$(this).children('.title').css({'background-color':'', 'color':''});
		$(this).parent().children('.content').css({'max-height':'2000px'});
	}
})

Number.prototype.formatNum = function(c, d, t){//.formatNum(2,'.',',')
var n = this, 
    c = isNaN(c = Math.abs(c)) ? 2 : c, 
    d = d == undefined ? "." : d, 
    t = t == undefined ? "," : t, 
    s = n < 0 ? "-" : "", 
    i = String(parseInt(n = Math.abs(Number(n) || 0).toFixed(c))), 
    j = (j = i.length) > 3 ? j % 3 : 0;
   return s + (j ? i.substr(0, j) + t : "") + i.substr(j).replace(/(\d{3})(?=\d)/g, "$1" + t) + (c ? d + Math.abs(n - i).toFixed(c).slice(2) : "");
};

function moveMenu(){
	if ( $(window).scrollTop() > ($('header').height()+100) && $('#fixed_menu').attr('active')=='0' ){
		$('#fixed_menu').stop().attr('active','1').css('top','0');
	}
	else if ( $(window).scrollTop() < ($('header').height()+100) && $('#fixed_menu').attr('active')=='1' ){
		$sizeH = $('#fixed_menu').height()*1;
		$('#fixed_menu').stop().attr('active','0').css('top','-'+$sizeH+'px');
	}
}
//moveMenu();

$(window).scroll(function(){
	//moveMenu();
});


function getUrlParameter(sParam) {
    var sPageURL = decodeURIComponent(window.location.search.substring(1)),
        sURLVariables = sPageURL.split('&'),
        sParameterName,
        i;

    for (i = 0; i < sURLVariables.length; i++) {
        sParameterName = sURLVariables[i].split('=');

        if (sParameterName[0] === sParam) {
            return sParameterName[1] === undefined ? true : sParameterName[1];
        }
    }
};

function ajaxSuccess(dataX){
	var data = $.parseJSON(dataX);
	
	if(data.fn=='filter'){
		var needed = $('#search_content .search_select[clicked="1"]').attr('type');
		$('#search_content .search_select[clicked="1"]').html('');
		$.each([needed], function( index, value ) { $.each(data.search[value], function( index2, value2 ) { $('#filter_'+value).append(value2); }); });
		var selectz = $('#search_content .search_select[clicked="1"]').attr('selectz');
		$('#search_content .search_select option[value='+selectz+']').attr('selected','selected')
	}
	
	if(data.fn=='search'){
		$('#content .catalog_page').html('');
		$('#search_content .search_select').html('');
	}
	
	if(data.fn=='search'||data.fn=='more_cars'){
		var moreText = $('#content .catalog_page').attr('moreCarText');
		$('#more_cars').remove();
		$('#car_countz').attr({ count : data.car_count , pos : data.car_pos});
		
		$.each(data.cars, function( index, value ) { $('#content .catalog_page').append(value); });
		
		if(data.car_pos!=null){
			$('#content .catalog_page').append('<div id="more_cars">'+moreText+'</div>');
		}
		
		if(data.fn=='search'){
			var filterArr = [ 'brand', 'model', 'year', 'bodytype', 'fuel', 'transmission', 'wheel_drive', 'color', 'prima_rata', 'price' ];
			$.each(filterArr, function( index, value ) { $.each(data.search[value], function( index2, value2 ) { $('#filter_'+value).append(value2); }); });
			$('#search_content .search_select[clicked="1"]').attr({'clicked':null});
		}
	}
	
	if(data.fn=='snd_msg'){
		var btn = $('div:not(.data) > #snd_msg .btn.sbmt');
		btn.val( btn.data('sent') ).css({'background-color':'#53bd17', 'color':'#fff'}).delay(1000).queue(function(){
			if(data.target=='overlay'){
				overlay('close');
			}else if(data.target=='self'){
				$.each($('#snd_msg .inp.use'), function(){ $(this).val('').trigger('change'); });
			}
			$(this).removeClass('ghost').attr('style',null).val( $(this).data('send') ).dequeue();
		});		
	}
   
    // if(data.fn=='order_car'){
		// if(data.sub=='chng_model'){
		// 	$('#offers .page .func .model').html('')
    //
		// 	$.each(data.models, function( index, value ){
		// 		$('#offers .page .func .model').append(value);
		// 	});
		// }
		// else if(data.sub=='submit'){
		// 	$('#offers .page .func .ready').addClass('active').delay(3000).queue(function(){$(this).removeClass('active'); $('#offers .page .func *:not(.order_submit)').val(''); $(this).dequeue();})
		// }
    // }

    if(data.fn=='order_item'){
        if(data.sub=='chng_model'){
            $('#offers .page .func .model').html('')

            $.each(data.models, function( index, value ){
                $('#offers .page .func .model').append(value);
            });
        }
        else if(data.sub=='submit'){
            $('#offers .page .func .ready').addClass('active').delay(3000).queue(function(){$(this).removeClass('active'); $('#offers .page .func *:not(.order_submit)').val(''); $(this).dequeue();})
        }
    }
}

function changeUrl(dataX){ window.history.pushState(null, null, dataX); }

function ajaxIt(dataX){
	$.ajax({
		url: "/ajax.php",
        type: "POST",
        data : dataX,
		async: true,
		//data : {'name':'123'},
		statusCode: {
            404: function() { $("#message").text("Page not found"); },
            500: function() { $("#message").text("Internal server error"); }
        },
        success: function(data){
            ajaxSuccess(data);
        },
		datatype : "json"
	});
}


$(document).on('click', '#more_cars', function(){
	var data = {};
	
	data['tp'] = 'ste';
	data['fn'] = 'more_cars';
	data['car_count'] = $('#car_countz').attr('count');
	data['car_pos'] = $('#car_countz').attr('pos');
	
	$('.s_main').each(function(){
		data[$(this).attr('name')] = $(this).val();
	})
	$('.s_extra').each(function(){
		data[$(this).attr('name')] = $(this).val();
	})
	
	ajaxIt(data);
})

$(document).on('click', '#search_content .search_select[clicked!="1"]', function(){

	$('#search_content .search_select[clicked="1"]').attr('clicked',null);
	var selectz = $(this).val();
	var data = {};
	data['tp'] = 'ste';
	data['fn'] = 'filter';
	
	$('.s_main').not(this).each(function(){ data[$(this).attr('name')] = $(this).val(); })
	$('.s_extra').not(this).each(function(){ data[$(this).attr('name')] = $(this).val(); })
	$(this).attr({'clicked':'1', 'selectz':selectz});
	ajaxIt(data);
	
})

$(document).on('change', '#search_content .search_select', function(){
	var newUrl = '/'+Cookies.get('lang')+'/'+'cars'+'/';
	var newMainUrl='';
	var newExtraUrl='?';
	var checkMain = 0;
	var checkExtra = 0;
	var data = {};
	
	data['tp'] = 'ste';
	data['fn'] = 'search';
	data['car_count'] = $('#car_countz').attr('count');
	data['car_pos'] = $('#car_countz').attr('pos');
	
	$('.s_main').each(function(){
		if ( $(this).val()!='' ){newMainUrl+=$(this).val()+'/'; checkMain++;}
		data[$(this).attr('name')] = $(this).val();
	})
	$('.s_extra').each(function(){
		if ( $(this).val()!='' ){if(checkExtra>0){newExtraUrl+='&';} newExtraUrl+=($('option:selected', this).parent().attr('type')+'='+$(this).val()); checkExtra++;}
		data[$(this).attr('name')] = $(this).val();
	})

	if ( (checkMain==0&&checkExtra>0) || (checkMain>0&&checkExtra>0) ){newUrl+=newMainUrl+newExtraUrl;}
	else if ( checkMain>0&&checkExtra==0 ){newUrl+=newMainUrl;}

	ajaxIt(data);
	changeUrl(newUrl);
})

$(document).on('change', '#offers .page .func .brand', function(){

	var data = {};
	data['tp'] = 'ste';
	data['fn'] = 'order_item';
	data['pg'] = 'order_car';
	data['sub'] = 'chng_model';
	data['req_brand'] = $(this).val();
	
	ajaxIt(data);
})

$(document).on('click', '#offers .page .func .order_submit', function(){
	
	//var readyToSend = 1;
	var data = {};
	data['tp'] = 'ste';
	data['fn'] = 'order_item';
	data['pg'] = 'order_car';
	data['sub'] = 'submit';
	
	$('#offers .page .func .need').each(function(){
	//	if ( !$(this).val() ){ $(this).addClass('not_ready'); }
		data[$(this).attr('name')] = $(this).val();
	})
		
	$('#offers .page .func .no_need').each(function(){ data[$(this).attr('name')] = $(this).val(); })
	
	//$('#offers .page .func .not_ready').each(function(){readyToSend = 0;})
	
	//if ( readyToSend == 1 ){
		ajaxIt(data);
	//}
})

//$(document).on('change', '#offers .page .func .need', function(){
//	if ( $(this).val()!='' ){ $(this).removeClass('not_ready'); }else{$(this).addClass('not_ready');}
//})

//.on('keyup change', function(){
	//$('.search_select').bind( 'keyup click', function(e) { if ( e.keyCode === 13 ){ carCatalogChange() } } ).on( 'click' ,function(){carCatalogChange() })
//});

/*
$('#car_brand').on('keyup change', function(){
	 ajaxIt();
})
*/

//-----------------------------------------------------------------------ExChanger

$(document).on('mouseup', '#exchange .valute input:not([placeholder])',  function(){
	//$(this).select();
	//document.execCommand( 'copy' );
	
	$('#exchange .valute input[placeholder]').each(function(){
		phVal = $(this).attr('placeholder');
		
		if($(this).val()!=''){ $(this).removeAttr('placeholder'); }
		else{ $(this).val(phVal).removeAttr('placeholder'); }
	})
	
	var thisVal = $(this).val();
	
	$(this).attr('placeholder',thisVal);
	$(this).val('');
	//thisVal1 = thisVal.replace(/\,/g,"")*1;
	//$(this).val(thisVal1);
})


$('#exchange .valute input').on('input',  function(){
	
	var thisVal = $(this).val();
	var thisValr = thisVal.replace(/\,/g,"")*1;
	var thisValz = $(this).attr('valz');
	
	$('#exchange .valute input:not([placeholder])').each(function(){
		otherValz = $(this).attr('valz');
		
		//isVal =  parseInt( ((thisVal*thisValz)/otherValz)*100 ) / 100;
		isVal = ((thisValr*thisValz)/otherValz).formatNum(2);

		$(this).val(isVal);
	})
	
	//thisVal = parseInt(thisVal.formatNum(0));
	
	$(this).val(thisVal);
})


//-----------------------------------------------------------------------Img Overlay
function resizer(){
	if ( $(window).width() >= $(window).height()){
		if ( $('#show_img').css('background-size')!='auto 90%' ){
			$('#show_img').css('background-size','auto 90%')
		}
	}else{
		if ( $('#show_img').css('background-size')!='90% auto' ){
			$('#show_img').css('background-size','90% auto')
		}
	}
}

$(window).resize(function() {
	resizer();
});

$(document).on("click", "main > .pht_bx > .list > .phts > .item", function(){
	var $phtBx = $(this).closest('.pht_bx');
	var it = $phtBx.find('.list .phts .item');
	var dataPos = $(this).data("pos");
	var dataSrc = $(this).data("high") || $(this).attr("src").replace("/med/", "/high/");

	it.removeClass("act");
	$(this).addClass("act");

	$phtBx.find('.big_pht').css("background-image", "url("+dataSrc+")").data({"pos": dataPos, "src": dataSrc});
})

var $activePhtBx = null;

$(document).on('click', 'main > .pht_bx > .big_pht', function(e){
	// Ignore clicks on the in-place nav arrows / the share/fav buttons — they have their own handlers.
	if ($(e.target).closest('.bp-nav, .card-share-btn, .card-fav-btn').length) { return; }
	$activePhtBx = $(this).closest('.pht_bx');
	var dataPos = $(this).data("pos"); var dataCnt = $(this).data("cnt"); var dataSrc = $(this).data("src");

	$('#show_img').addClass('act').css('background-image','url('+dataSrc+')');
	$('#show_img > .status').text(dataPos+' / '+dataCnt);

	resizer();
});

$('#show_img > .left').click(function(){
	var $phtBx = $activePhtBx || $('main > .pht_bx').first();
	var it = $phtBx.find('.list .phts .item');
	var bP = $phtBx.find('.big_pht');
	var dataPos = bP.data("pos"); var dataCnt = bP.data("cnt");

	if (dataCnt>1){
		if (dataPos > 1){dataPos--;}else{dataPos=dataCnt;}
		var newIt = it.filter("[data-pos='"+dataPos+"']");
		var dataSrc = newIt.data("high") || newIt.attr("src").replace("/med/", "/high/");

		$('#show_img').css('background-image','url('+dataSrc+')');
		$('#show_img > .status').text(dataPos+' / '+dataCnt);

		it.removeClass("act"); newIt.addClass("act");
		bP.css("background-image", "url("+dataSrc+")").data({"pos": dataPos, "src": dataSrc});

		resizer();
	}
})

$('#show_img > .right').click(function(){
	var $phtBx = $activePhtBx || $('main > .pht_bx').first();
	var it = $phtBx.find('.list .phts .item');
	var bP = $phtBx.find('.big_pht');
	var dataPos = bP.data("pos"); var dataCnt = bP.data("cnt");

	if (dataCnt>1){
		if (dataPos < dataCnt){dataPos++;}else{dataPos=1;}
		var newIt = it.filter("[data-pos='"+dataPos+"']");
		var dataSrc = newIt.data("high") || newIt.attr("src").replace("/med/", "/high/");

		$('#show_img').css('background-image','url('+dataSrc+')');
		$('#show_img > .status').text(dataPos+' / '+dataCnt);

		it.removeClass("act"); newIt.addClass("act");
		bP.css("background-image", "url("+dataSrc+")").data({"pos": dataPos, "src": dataSrc});

		resizer();
	}
})

$('#show_img > .close').click(function(){ $('#show_img').removeClass('act').css('background-image','none'); })

// Keyboard: Escape closes the fullscreen viewer; arrows change the photo.
$(document).on('keydown', function(e){
	if (!$('#show_img').hasClass('act')) return;
	if (e.key === 'Escape' || e.keyCode === 27) { $('#show_img').removeClass('act').css('background-image','none'); }
	else if (e.key === 'ArrowLeft'  || e.keyCode === 37) { $('#show_img > .left').click(); }
	else if (e.key === 'ArrowRight' || e.keyCode === 39) { $('#show_img > .right').click(); }
})

// In-place arrows on the desktop big photo (change the image without opening fullscreen).
$(document).on('click', '.big_pht > .bp-nav', function(e){
	e.stopPropagation(); // don't open the fullscreen viewer
	var bP = $(this).closest('.big_pht');
	var $phtBx = bP.closest('.pht_bx');
	var it = $phtBx.find('.list .phts .item');
	var dataPos = bP.data('pos'); var dataCnt = bP.data('cnt');
	if (dataCnt <= 1) return;
	if ($(this).hasClass('bp-left')) { dataPos = (dataPos > 1) ? dataPos - 1 : dataCnt; }
	else { dataPos = (dataPos < dataCnt) ? dataPos + 1 : 1; }
	var newIt = it.filter("[data-pos='"+dataPos+"']");
	var dataSrc = newIt.data('high') || newIt.attr('src').replace('/med/', '/high/');
	it.removeClass('act'); newIt.addClass('act');
	bP.css('background-image', 'url('+dataSrc+')').data({ 'pos': dataPos, 'src': dataSrc });
})

$("main > .inf_bx > .menu > .btn").on("click", function(){
	var zName = $(this).data("name");
	$("main > .inf_bx > .menu > .btn").removeClass("act");
	$(this).addClass("act");
	$("main > .inf_bx > .bx > .cnt").removeClass("act");
	$("main > .inf_bx > .bx > .cnt[data-name="+zName+"]").addClass("act");
})

$(".numInput").keydown(function (e) {
        // Allow: backspace, delete, tab, escape, enter and .
	if ($.inArray(e.keyCode, [46, 8, 9, 27, 13, 110, 190]) !== -1 ||
             // Allow: Ctrl+A
		(e.keyCode == 65 && e.ctrlKey === true) ||
             // Allow: Ctrl+C
		(e.keyCode == 67 && e.ctrlKey === true) ||
             // Allow: Ctrl+X
        (e.keyCode == 88 && e.ctrlKey === true) ||
             // Allow: home, end, left, right
        (e.keyCode >= 35 && e.keyCode <= 39)) {
                 // let it happen, don't do anything
        	return;
        }
        // Ensure that it is a number and stop the keypress
	if ((e.shiftKey || (e.keyCode < 48 || e.keyCode > 57)) && (e.keyCode < 96 || e.keyCode > 105)) {
		e.preventDefault();
    }
});

//$("img").on("contextmenu",function(){ return false; }); Block context for imgs

$(document).keyup(function (e) {
    if(!e) e = window.event;
    var keyCode = e.which || e.keyCode

    if (keyCode  == 44) {
		//$('body').css('display','none');
		//location.reload();
		// window.location.replace("http://sauto.md?blip");
    }

    if (keyCode == 37 || keyCode == 39) {
        var $phtBx = $activePhtBx || $('main > .pht_bx').first();
        var it = $phtBx.find('.list .phts .item');
        var bP = $phtBx.find('.big_pht');
        if (it.length === 0 || bP.length === 0) return;

        var dataPos = bP.data("pos");
        var dataCnt = bP.data("cnt");
        if (!dataCnt || dataCnt < 2) return;

        if (keyCode == 37) {
            dataPos = dataPos > 1 ? dataPos - 1 : dataCnt;
        } else {
            dataPos = dataPos < dataCnt ? dataPos + 1 : 1;
        }

        var newIt = it.filter("[data-pos='" + dataPos + "']");
        var dataSrc = newIt.data("high") || newIt.attr("src").replace("/med/", "/high/");

        it.removeClass("act"); newIt.addClass("act");
        bP.css("background-image", "url(" + dataSrc + ")").data({"pos": dataPos, "src": dataSrc});

        if ($('#show_img').hasClass('act')) {
            $('#show_img').css('background-image', 'url(' + dataSrc + ')');
            $('#show_img > .status').text(dataPos + ' / ' + dataCnt);
        }

        resizer();
    }
});

$(document).on('click', '#get_action div', function(){
		
	//var name = $(this)
	
	var data = {};
	
	data['tp'] = 'ste';
	data['fn'] = 'get_act';	
	
	if( $(this).attr('data-name') == 'credit' ){
		data['sub'] = 'credit';
		data['sub_2'] = 'start';
		//ajaxIt(data);
		
		overlay("open", "#hidden > .credit", "self");
	}
	else if ( $(this).attr('data-name') == 'tradein' ){
		data['sub'] = 'tradein';
		data['sub_2'] = 'start';
		//ajaxIt(data);
		
		overlay("open", "#hidden > .tradein", "self");
	}
})

$(document).on('click', '#overlay .credit form input[type="submit"]', function(e){
	e.preventDefault();
	if ( !$(this).parent().find('input[type="checkbox"]').prop('checked') ){ alert('not Checked!') }
	else {
		var data = {};
		data['tp'] = 'ste';
		data['fn'] = 'order_item';
		data['pg'] = 'credit';
		data['sub'] = 'submit';
		
		$('#overlay .credit form input[type!="submit"]').each(function(){
			data[$(this).attr('name')] = $(this).val();
		})
		
		//ajaxIt(data);
	}
})

$(document).on('click', '#overlay .tradein form input[type="submit"]', function(e){
	e.preventDefault();
	var empty = true;
	$(this).parent().find('input[type!="submit"]').each(function(){
		if( $(this).val()=="" ){
			empty = false;
			return false;
		}
	})
	if ( empty == false ){ alert('not Checked!') }
	else {
		var data = {};
		data['tp'] = 'ste';
		data['fn'] = 'order_item';
		data['pg'] = 'tradein';
		data['sub'] = 'submit';
		
		$('#overlay .tradein form input[type!="submit"]').each(function(){
			data[$(this).attr('name')] = $(this).val();
		})
		
		//alert('ajax')
		//ajaxIt(data);
	}
})

});

//-------------------------------------------------------------------------------------- GLOBAL -------------------------------------------------------

// Update offer timers on product cards
setInterval(function(){
	$('.timer-display').each(function(){
		var endTime = $(this).data('end-time');
		var now = Math.floor(Date.now() / 1000);
		var timer = endTime - now;
		
		if (timer <= 0) {
			// Timer expired → hide the countdown; the car is shown as out of stock
			// via its status badge (rendered server-side on the next load).
			$(this).closest('.offer-timer, .mobile-only-timer, .stat').hide();
			$(this).hide();
			return;
		}
		var tD = Math.floor(timer / (24*60*60));
		var tH = Math.floor((timer - (tD*24*60*60)) / (60*60));
		var tM = Math.floor((timer - (tD*24*60*60) - (tH*60*60)) / 60);
		var tS = Math.floor(timer - (tD*24*60*60) - (tH*60*60) - (tM*60));
		
		if (tD < 10) { tD = '0' + tD; }
		if (tH < 10) { tH = '0' + tH; }
		if (tM < 10) { tM = '0' + tM; }
		if (tS < 10) { tS = '0' + tS; }
		
		$(this).html(tD + ':' + tH + ':' + tM + ':' + tS);
	});
}, 1000);

//-------------------------------------------------------------------------------------- ON LOAD -------------------------------------------------------

$(window).on('load', function() {
/*
$('#info_left .gallery_image img').each(function(){
var imgSrc = $(this).attr('src');
var newSrc = imgSrc.replace('/low/','/high/');
$('#preloaded_img').append('<img src="'+newSrc+'" />');
})	
*/

// Predator LED segments after timer
const activeColor = "#ff0707";

// Segment coordinates (5 lines with gap from center, uniform length ~8-9px)
const segmentCoordinates = [
	[25, 7, 25, 0],    // top (vertical)
	[28, 10, 34, 10],  // right (horizontal)
	[28, 13, 34, 18],  // bottom-right (diagonal)
	[22, 13, 16, 18],  // bottom-left (diagonal)
	[16, 10, 22, 10]   // left (horizontal)
];

function initPredatorCanvas() {
	$('.timer-display').each(function() {
		const timerText = $(this).text().trim();
		// Only add Predator symbols if timer shows time format (contains colons), not expired text
		const isActiveTimer = timerText.includes(':');
		
		if (isActiveTimer) {
			// Check if predator wrapper already exists above timer
			if (!$(this).prev('.predator-wrapper').length) {
				const canvasId = 'predator-' + Math.random().toString(36).substr(2, 9);
		
				const isMobile = window.innerWidth <= 768;
			
				const isProductPage = $(this).closest('.mobile-only-timer').length > 0;
				const marginBottom = isMobile ? (isProductPage ? '-15px' : '-5px') : '0px';
				$(this).before('<div class="predator-wrapper" style="display: block; text-align: center; margin-bottom: ' + marginBottom + ';"><canvas class="predator-canvas" id="' + canvasId + '" width="110" height="20"></canvas></div>');
			}
		} else {
			// Remove Predator symbols if timer expired
			$(this).prev('.predator-wrapper').remove();
		}
	});
}

function drawLine(ctx, startX, startY, endX, endY, color) {
	ctx.beginPath();
	ctx.lineWidth = 3;
	ctx.lineCap = "round";
	ctx.strokeStyle = color;
	ctx.moveTo(startX, startY);
	ctx.lineTo(endX, endY);
	ctx.stroke();
}

function drawSegments(ctx, coordinates, offsetX) {
	for (let i = 0; i < coordinates.length; i++) {
		// Randomly show line in red or don't draw it at all (50% chance)
		if (Math.random() > 0.5) {
			drawLine(
				ctx,
				coordinates[i][0] + offsetX,
				coordinates[i][1],
				coordinates[i][2] + offsetX,
				coordinates[i][3],
				activeColor
			);
		}
		// else: line is not drawn (invisible/off state)
	}
}

function animatePredatorCanvas() {
	$('.predator-canvas').each(function() {
		const ctx = this.getContext('2d');
		ctx.clearRect(0, 0, this.width, this.height);
		
		// Draw 3 symbols: above hours, minutes, seconds (adjusted for 110px width)
		drawSegments(ctx, segmentCoordinates, -3);   // Above hours
		drawSegments(ctx, segmentCoordinates, 32);   // Above minutes
		drawSegments(ctx, segmentCoordinates, 67);   // Above seconds
	});
}

setTimeout(function() {
	initPredatorCanvas();
	animatePredatorCanvas();
}, 50);

setInterval(animatePredatorCanvas, 1000);
setInterval(initPredatorCanvas, 1000);

// ============================================================
// BRAND PAGE - LOAD MORE FUNCTIONALITY
// ============================================================
(function() {
	var loadMoreBtn = document.getElementById('load-more-btn');
	var container = document.getElementById('brand-cars-container');

	if (!loadMoreBtn || !container) return;

	var brand = container.dataset.brand || '';
	var model = container.dataset.model || '';
	var pageType = container.dataset.pageType || 'cars';
	var total = parseInt(container.dataset.total) || 0;
	var loaded = parseInt(container.dataset.loaded) || 0;
	var isLoading = false;
	var limit = 16;

	// Update button visibility
	function updateButtonVisibility() {
		if (loaded >= total) {
			loadMoreBtn.style.display = 'none';
		}
	}

	loadMoreBtn.addEventListener('click', function() {
		if (isLoading || loaded >= total) return;

		isLoading = true;
		loadMoreBtn.disabled = true;
		loadMoreBtn.style.opacity = '0.6';

		var spinner = document.querySelector('.load-more-spinner');
		if (spinner) spinner.style.display = 'block';

		var formData = new FormData();
		formData.append('tp', 'ste');
		formData.append('fn', 'load_more_brand_cars');
		formData.append('brand', brand);
		formData.append('model', model);
		formData.append('page_type', pageType);
		formData.append('offset', loaded);
		formData.append('limit', limit);

		fetch('/ajax.php', {
			method: 'POST',
			body: formData
		})
		.then(function(response) { return response.json(); })
		.then(function(data) {
			if (data.html) {
				// Append new cards to container
				container.insertAdjacentHTML('beforeend', data.html);

				// Update loaded count
				loaded += data.count;
				container.dataset.loaded = loaded;

				// Re-initialize mobile sliders if needed
				if (typeof initMobileCardSliders === 'function') {
					initMobileCardSliders();
				}
			}

			// Check if we should hide the button
			if (!data.has_more || loaded >= total) {
				loadMoreBtn.style.display = 'none';
			}
		})
		.catch(function(error) {
			console.error('Error loading more cars:', error);
		})
		.finally(function() {
			isLoading = false;
			loadMoreBtn.disabled = false;
			loadMoreBtn.style.opacity = '1';
			if (spinner) spinner.style.display = 'none';
		});
	});

	updateButtonVisibility();
})();

// Helper: extract brand/model path from current URL
function _srtGetExtraPath(){
	var pathParts = window.location.pathname.split('/').filter(function(p){ return p !== ''; });
	// pathParts[0] = lang, pathParts[1] = cars/ordercars, pathParts[2] = brand, pathParts[3] = model
	var extraPath = '';
	if (pathParts.length >= 3 && !/^\d+$/.test(pathParts[2])) {
		extraPath += '/' + pathParts[2];
		if (pathParts.length >= 4 && !/^\d+$/.test(pathParts[3])) {
			extraPath += '/' + pathParts[3];
		}
	}
	return extraPath;
}

function _srtGetLang(){
	return (document.cookie.match(/(?:^|;\s*)lang=([^;]+)/) || [])[1] || 'ro';
}

// Catalog switch badge — click navigates to the other catalog (preserves filters)
$(document).on('click', '.srt_wrap > .srt_cat_badge', function(){
	var cat = $(this).data('cat'); // 'in_stock' or 'on_order' — target catalog
	var page = $(this).closest('.srt_wrap').data('page');
	if ((cat === 'in_stock' && page === 'cars') || (cat === 'on_order' && page === 'ordercars')) {
		return;
	}
	var target = (cat === 'on_order') ? 'ordercars' : 'cars';
	var lang = _srtGetLang();
	var extraPath = _srtGetExtraPath();
	var params = new URLSearchParams(window.location.search);
	if (params.has('srt') && !params.has('tg')) {
		params.set('tg', 'fltr');
	}
	var qs = params.toString();
	window.location.href = '/' + lang + '/' + target + extraPath + (qs ? ('?' + qs) : '');
});

// Custom sort dropdown — click on button toggles list, click on item navigates
$(document).on('click', '.srt_wrap > .srt_drop .srt_drop_btn', function(e){
	e.stopPropagation();
	var $drop = $(this).parent();
	$('.srt_drop.open').not($drop).removeClass('open');
	$drop.toggleClass('open');
});
$(document).on('click', '.srt_wrap > .srt_drop .srt_drop_list > li', function(){
	var val = $(this).data('val');
	var $wrap = $(this).closest('.srt_wrap');
	var $drop = $wrap.find('.srt_drop');
	var $btn = $drop.find('.srt_drop_btn');
	$btn.contents().filter(function(){return this.nodeType===3;}).remove();
	$btn.find('.srt_drop_ico').after(document.createTextNode(' ' + $(this).text()));
	$wrap.addClass('has_value');
	$drop.removeClass('open');

	var params = new URLSearchParams(window.location.search);
	if (val === '' || val == null) {
		params.delete('srt');
	} else {
		params.set('srt', val);
		if (!params.has('tg')) params.set('tg', 'fltr');
	}
	var qs = params.toString();
	window.location.href = window.location.pathname + (qs ? ('?' + qs) : '');
});
$(document).on('click', function(){
	$('.srt_drop.open').removeClass('open');
});

})