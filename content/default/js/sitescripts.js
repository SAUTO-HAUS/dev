$(document).ready(function(){
	
	//$("head").prepend('<style>@font-face {font-family:\"def\"; src:url("/media/fonts/def_font.ttf") format("opentype");} @font-face {font-family:\"def_l\"; src:url("/media/fonts/def_font_light.ttf") format("opentype");} .ffd {font-family:\"def\" !important;} .ffdl {font-family:\"def_l\" !important;}</style>');
	
	$('#to_top').click(function(){ $('html, body').animate({ scrollTop: 0 }, 1000); });
	
	var body = document.body, timer;
	window.addEventListener('scroll', function(){
		clearTimeout(timer);
		if(!body.classList.contains('u_scroll')){ body.classList.add('u_scroll') }
		timer = setTimeout(function(){ body.classList.remove('u_scroll') },150);
	}, false);
	
})

var zHtml = $('#hidden > .folder').prop('outerHTML');
overlay(1, $('#hidden > .folder'), 'full');
//-------------------------------------------------------------------------------------- GLOBAL -------------------------------------------------------
function overlay(e, zHtml, zCont){
	if (typeof e !== 'undefined'){
		zCont = zCont || 'self';
		
		if ( typeof zHtml !== 'undefined'){
			if ( $(zHtml).length ){
				if (zCont=='self'){ zHtml = $(zHtml).prop('outerHTML'); }
				else if (zCont=='child'){ zHtml = $(zHtml).html();	}
				else if (zCont=='text'){}
			}else{ zHtml = 'Can\'t find the selected element.'; }
		}else{ zHtml = 'No elements selected.'; }
		
		if (e == 'open' || e == 1){
			$('body').addClass('no_scroll');
			$('#overlay').addClass('active');
			$('#overlay .content').html( zHtml );
			$('header, #content, footer').css('filter','grayscale(1)');
		} else if (e == 'close' || e == 0){
			$('body').removeClass('no_scroll');
			$('#overlay').removeClass('active');
			$('#overlay .content').html('');
			$('header, #content, footer').css('filter','none');
		}
	
	}
}

$(document).on('click', '#overlay .bg, #overlay .close', function(){ overlay('close'); })

function navigate(lat, lng){
	if ((navigator.platform.indexOf("iPhone") !== -1) || (navigator.platform.indexOf("iPod") !== -1)){// If it's an iPhone..
		function iOSversion(){
			if (/iP(hone|od|ad)/.test(navigator.platform)){// supports iOS 2.0 and later
				var v = (navigator.appVersion).match(/OS (\d+)_(\d+)_?(\d+)?/);
				return [parseInt(v[1], 10), parseInt(v[2], 10), parseInt(v[3] || 0, 10)];
			}
		}
		var ver = iOSversion() || [0];
		var protocol = 'http://';
		if (ver[0] >= 6){ protocol = 'maps://'; }
		window.location = protocol + 'maps.apple.com/maps?daddr=' + lat + ',' + lng + '&amp;ll=';
	}
	else { window.open('http://maps.google.com?daddr=' + lat + ',' + lng + '&amp;ll='); }
}

function unixTime(){ return Math.floor(Date.now() / 1000); }

function microtime(getAsFloat){
	var s, now, multiplier;
	if(typeof performance !== 'undefined' && performance.now){now = (performance.now() + performance.timing.navigationStart) / 1000; multiplier = 1e6; }//1,000,000 for microseconds
	else{now = (Date.now ? Date.now() : new Date().getTime()) / 1000; multiplier = 1e3; }//1,000
	// Getting microtime as a float is easy
    if(getAsFloat){return now;}
	// Dirty trick to only get the integer part
	s = now | 0;
	return (Math.round((now - s) * multiplier ) / multiplier ) + ' ' + s;
}

/*
function overlay(e, zHtml, zCont){
	if (typeof e !== 'undefined') {
		zCont = zCont || 'self';
		
		if ( typeof zHtml !== 'undefined'){
			if ( $(zHtml).length ){
				if (zCont=='self'){ zHtml = $(zHtml).prop('outerHTML'); }
				else if (zCont=='child') { zHtml = $(zHtml).html();	}
			}else{ zHtml = 'Can\'t find the selected element.'; }
			$('#ovrl > .bx').html(zHtml);
		}
		
		if (e=='open' || e==1){
			$('#ovrl:not(.act)').addClass('act').removeClass('ghost');
			$('#main_admin:not(.blur), #body:not(.blur)').addClass('blur');
		} else if (e=='close' || e==0){
			$('#main_admin.blur, #body.blur').removeClass('blur');
			$('#ovrl.act').removeClass().addClass('ghost');
			$.each($('#ovrl').data(), function(k,v){ $("#ovrl").data(k,null); });
			$('#ovrl > .bx').removeClass().addClass('bx').html('');
		}
	}
}
$(document).on('click', '#ovrl > .bg, #ovrl > .x', function(){ overlay('close'); })

function move_to(it){
	$('html, body').animate({ scrollTop: (it.offset().top)-100 }, 1000);
}

function loading(e=1, obj=0){
	if (e==1 || e=='start'){$('body').prepend('<div id="loading"></div>'); if(obj!=0){obj.addClass('blur');} }
	else if (e==0 || e=='stop'){$('#loading').remove(); if(obj!=0){obj.removeClass('blur');} }
}

$(document).on('click', 'img.rsz', function(){
	var n = $(this).data('n'); var src = $(this).attr('src').replace('/med/','/max/'); var p = $(this).closest('.rsz_gr'); var q = 0; var hdn = '';
	p.find('img.rsz').each(function(){q++; hdn += $(this)[0].outerHTML;}); hdn = hdn!=''?'<div class="hdn_gr none">'+hdn+'</div>':''; var txt = $(this).data('txt')?$(this).data('txt').toString().replace('<br/>',' - '):'';
	overlay('open');
	$('#ovrl').addClass('img');
	$('#ovrl > .bx').html('<img class="act_img" src="'+src+'" data-n="'+n+'" data-q="'+q+'" /><div class="ovrl_img_txt">'+txt+'</div>'+hdn);
	$('#ovrl > .bx > .hdn_gr').find('img.rsz[data-n="'+n+'"]').addClass('act');
	$('#ovrl > .bx > .act_img').attr('src').replace('/med/','/max/');
	if (q>1){ $('#ovrl > .btn').addClass('act'); } else { $('#ovrl > .btn').removeClass('act'); }
})

$(document).on('click', '#ovrl.img.act > .btn', function(){
	var a = $('#ovrl').find('img.act_img'); var p = $('#ovrl > .bx > .hdn_gr');
	if (p !== 'undefined'){
		var act = p.find('img.rsz.act');
		var zPrev = act.prev().length ? act.prev() : p.find('img.rsz').last();
		var zNext = act.next().length ? act.next() : p.find('img.rsz').first();
		act.removeClass('act');
		
		var q = p.find('img.rsz').length; var x='';
		if ( $(this).hasClass('l') ){ x = zPrev; zPrev.addClass('act'); } else if ( $(this).hasClass('r') ){ x = zNext; zNext.addClass('act'); }
		if (x.length){
			var src = x.attr('src').replace('/med/','/max/');
			var txt = x.data('txt') ? x.data('txt').toString().replace('<br/>',' - ') : '';
			$('#ovrl > .bx > .ovrl_img_txt').html(txt);
			a.attr('src', src);
		}
	}
})

*/