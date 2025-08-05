var interval;
// Global function to detect current page for AJAX routing
function getReqPage() {
	// Check URL to determine current admin page
	if (window.location.href.indexOf('/cars/') !== -1) {
		return 'cars';
	} else if (window.location.href.indexOf('/tyres/') !== -1) {
		return 'tyres';
	} else if (window.location.href.indexOf('/users/') !== -1) {
		return 'users';
	} else if (window.location.href.indexOf('/slider/') !== -1) {
		return 'slider';
	}
	// Default fallback
	return 'unknown';
}

$(document).ready(function() {
	
	if ( typeof Cookies.get('xtype') !== 'undefined' ){ Cookies.remove('xtype', { path: '/' }) }
	
	/*
	var def_clr = $('html').css('--clr');
	$('.bx > .img > .url').on('mouseenter', function(){ $('html').css('--clr', 'green'); })
	$('.bx > .img > .url').on('mouseleave', function(){ $('html').css('--clr', def_clr); })
	*/
	
	if (localStorage.getItem('z_adm_sess_end') !== null ){
		if ( unixTime() < localStorage.getItem('z_adm_sess_end') ){
			localStorage.setItem( 'z_adm_sess_end', (unixTime() + 60 * 60 * 3) );
		}else{
			localStorage.removeItem( 'z_adm_sess_start' );
			localStorage.removeItem( 'z_adm_sess_end' );
		}
	}else{
		localStorage.setItem( 'z_adm_sess_end', (unixTime() + 60 * 60 * 3) );
		localStorage.setItem( 'z_adm_sess_start', unixTime() );
	}
	
	//---------------------------------------------------------------------------------------close content box
	$(document).on('click', '#content_box .close', function(){
		$(this).closest('.bx').addClass('none');
		$('#content_box').removeClass('act');
		$('#main_admin').removeClass('no_active');
		
		if ( !$('body').hasClass('ajx') ){$('#content_box').html('');}
	})
	
	$('#search_content > .open_close').click(function(){
		var activeStatus = $(this).parent().attr('active');
		if ( activeStatus == '0' ){
			$(this).parent().css('right','0').attr('active', '1');
			$('#content .catalog_page').css({'width':'calc(100% - 260px)'});
		} else {
			$(this).parent().css('right','-260px').attr('active', '0');
			$('#content .catalog_page').css({'width':'100%'});
		}
	})
	
	$(document).on('mouseover', '#exist_photos > .photo', function(e){
		var thisImg = $(this).attr('this_img');
		$('#zoom_box').css({'display':'block', 'background-image':'url('+thisImg+')'});
	})
	$(document).on('mousemove', '#exist_photos > .photo', function(e){
		var dT = $(document).scrollTop();
		var dW = $(document).width()*1;
		var sT = $('#content_box').scrollTop();
		
		var thisW = $('#zoom_box').css('width').replace(/[^-\d\.]/g, '')*1;
		var thisH = $('#zoom_box').css('height').replace(/[^-\d\.]/g, '')*1;
		var thisX = e.pageX - (thisW / 2);
		
		if(e.shiftKey){ $('#zoom_box').css({'top':sT + (e.pageY-dT) - (thisH/2)+'px', 'left':e.pageX-(thisW/2)+'px'}); }
		else{ $('#zoom_box').css({'top':sT + (e.pageY-dT) + 20+'px', 'left':thisX+'px'}); }
		
		if ( e.pageX - thisW/2 < 0 ){ $('#zoom_box').css({'left':'0'}); }
		if ( e.pageX + thisW/2 > dW ){ $('#zoom_box').css({'left':dW-thisW}); }
	})
	$(document).on('mousewheel', '#exist_photos > .photo', function(e){
		if(e.shiftKey){
			e.preventDefault();
		
			var addW = 45; var addH = 30; var addX = 22.5;
			var thisW = $('#zoom_box').css('width').replace(/[^-\d\.]/g, '')*1;
			var thisH = $('#zoom_box').css('height').replace(/[^-\d\.]/g, '')*1;
			var thisX = $('#zoom_box').css('left').replace(/[^-\d\.]/g, '')*1;
		
			if (e.deltaY == '1'){ $('#zoom_box').css({'width':thisW+addW+'px', 'height':thisH+addH+'px', 'left':thisX-addX+'px'}); }
			else if (e.deltaY == '-1'){	$('#zoom_box').css({'width':thisW-addW+'px', 'height':thisH-addH+'px', 'left':thisX+addX+'px'}); }
		}
	})
	$(document).on('mouseout', '#exist_photos > .photo', function(e){
		$('#zoom_box').css({'display':'none'});
	})
	
	if (typeof reqPage !== 'undefined'){
		$(document).on('click', '#prev_w_pos.act', function(){$(this).addClass('ghost');});
		$(window).scroll(function(){ localStorage.setItem( 'z_adm_pg_'+reqPage+'_w_pos', $(window).scrollTop() ); $('#prev_w_pos.act').removeClass('act'); }); //Curent win pos (save/load)
		var dlay = 0, w_pos = localStorage.getItem('z_adm_pg_'+reqPage+'_w_pos');
		if (w_pos !== null){
			if (w_pos>0 && $(window).scrollTop()!=w_pos){$('#prev_w_pos').addClass('act').removeClass('ghost');}
			$('#prev_w_pos.act').on('click', function(){ 
				if ( ( w_pos*1 + $(window).height() + 100 ) > $('#content > .ctlg').outerHeight(true) ){
					// Use dynamic page detection instead of global reqPage
					var currentPage = getReqPage();
					var moreBtnClk = parseInt( localStorage.getItem('z_adm_pg_'+currentPage+'_more_btn_clk') ) || 0;
					if ( moreBtnClk>0 ){
						var itCnt = $('#car_countz').data('count');
						$('#car_countz').data('count', moreBtnClk * itCnt);
						// Only trigger if we're on the correct page to avoid mixed AJAX calls
						if (currentPage === 'cars' && window.location.href.indexOf('/cars/') !== -1) {
							console.log('Auto-triggering more button for cars page');
							$('#more_it').data('i', moreBtnClk).click();
						} else if (currentPage === 'tyres' && window.location.href.indexOf('/tyres/') !== -1) {
							console.log('Auto-triggering more button for tyres page');
							$('#more_it').data('i', moreBtnClk).click();
						} else {
							console.log('Skipping auto-trigger - page mismatch:', currentPage, window.location.href);
						}
						$('#car_countz').data('count', itCnt);
						dlay = 300;
					}
				}
				$('html, body').delay(dlay).queue(function(){$(this).animate({ scrollTop: w_pos+'px' }, 1000); $(this).dequeue();});
			})
		}
		
		if($.inArray(reqPage, ['cars', 'tyres']) !== -1){
			var tg = $('#content > .ctlg');
			if (localStorage.getItem('z_adm_pg_'+reqPage+'_ctlg_dspl_tp') !== null){ tg.addClass('list start').delay(300).queue(function(){tg.removeClass('start'); tg.dequeue();}); }
			$('#content > .ctlg_dspl_tp').on('click', function(){
				if ( $(tg).hasClass('list') ){ tg.removeClass('list'); localStorage.removeItem( 'z_adm_pg_'+reqPage+'_ctlg_dspl_tp' ); }
				else{ tg.addClass('list'); localStorage.setItem( 'z_adm_pg_'+reqPage+'_ctlg_dspl_tp', 'list' ); }
				tg.addClass('fx').delay(300).queue(function(){tg.removeClass('fx'); tg.dequeue();})
			});
		}
		// More button handler moved to cars.js to avoid conflicts
		
		$('#search_content > .s_main').on('change', function(){
			var fltrSel = {};
			$('#search_content > .s_main').each(function(){
				fltrSel[ $(this).attr('type') ] = $(this).val();
			})
			localStorage.setItem( 'z_adm_pg_'+reqPage+'_fltr_sel', fltrSel );
		})
	}
	
	//---------------------------------------------------------------------------------------ovrl DD files
	$(document).on('change', '#content_box .dd_plc > input[type="file"]', function(e){
		var bx_id = $(this).closest('.bx').data('bx_id');
		$('#content_box .id_'+bx_id+' .dd_plc').removeClass('drag');
		var file, tp, sz, el, szSum=0, cnt = $(this)[0].files.length, bg, bgSrc = '/media/images/site/icon';
		$('#content_box .id_'+bx_id+' .inf.h').removeClass('h'); $('#content_box .id_'+bx_id+' .prv.h').removeClass('h'); $('#content_box .id_'+bx_id+' .prv:not(.ready) > .its').html('');
		
		function bCalc(v){var x=v/1024>1?(v/(Math.pow(1024,2))>1?(v/(Math.pow(1024,3))>1?(v/(Math.pow(1024,4))>1?'>1GB':(v/(Math.pow(1024,3))).toFixed(2)+' GB'):(v/(Math.pow(1024,2))).toFixed(1)+' MB'):(v/1024).toFixed(0)+' KB'):v+' B'; return x;}
		function fTp(v){var x=v=='application/pdf'?'pdf':(v=='text/plain'?'txt':($.inArray(v, ['image/jpg','image/png','image/jpeg','image/gif','image/webp']) !== -1)?'img':(v=='application/msword'?'doc':(v=='application/vnd.ms-excel'?'xls':(v=='text/xml'?'xml':(v=='application/json'?'json':($.inArray(v, ['application/vnd.oasis.opendocument.formula','application/vnd.oasis.opendocument.text','application/vnd.oasis.opendocument.spreadsheet','application/vnd.oasis.opendocument.charts','application/vnd.oasis.opendocument.presentations']) !== -1?'odf':'uknown')))))); return x;}
		
		for (var i = 0; i < cnt; i++){
			file = window.URL.createObjectURL(this.files[i]);
			tp = fTp(this.files[i].type); sz = this.files[i].size;
			szSum += sz; bg = tp=='img'?file:bgSrc+'/'+tp+'.svg';
			//var nm = (this.files[i].name).length>20?(this.files[i].name).replace(/(.{20})/g,"$1 "):'--'+this.files[i].name;
			
			var el = tp=='img'?$('#content_box .id_'+bx_id+' .prv.imgs:not(.ready) > .its'):$('#content_box .id_'+bx_id+' .prv.docs:not(.ready) > .its');
			el.append(''
				+ '<label class="it '+i+' f_'+tp+' new h" data-id="'+i+'" title="'+this.files[i].type+'">'
					+ '<input type="radio" class="use main_img new none" name="main_img" value="'+i+'" data-id="'+i+'" '+(i===0 && !$('#content_box').data('car-id')?'checked="checked"':'')+' />'
					+ '<div class="ico ghost"></div>'
					+ '<img class="img" src="'+bg+'" /><div class="nm">'+this.files[i].name+'</div><div class="sz">'+bCalc(sz)+'</div>'
				+ '</label>'
			)
			$('#content_box .id_'+bx_id+' .prv:not(.ready) .it.h.'+i).delay(100).queue(function(){$(this).removeClass('h'); $(this).dequeue();})
		}
		$('#content_box .id_'+bx_id+' .inf > .c').html(cnt); $('#content_box .id_'+bx_id+' .inf > .s').html(bCalc(szSum));
		$.each($('#content_box .id_'+bx_id+' .prv:not(.ready) .its'), function(){
			if ($(this).children('.it').length==0){$(this).closest('.prv').addClass('h');}
		})
	})
	
	var counter = 0;
	$(document)
		.on('dragenter', '#content_box', function(e){//e.stopPropagation();
			counter++;
			e.preventDefault();
			$(this).find('.dd_plc').addClass('drag');
		})
		.on('dragleave', '#content_box', function(){
			counter--;
			if (counter === 0){
				$(this).find('.dd_plc').removeClass('drag');
			}
		})
		.on('dragover', '#content_box .add_files > .dd_plc > .f', function(e){//dragover
			$(this).closest('.dd_plc').addClass('hov');
			$('body').addClass('e_drop');
		})
		.on('dragleave', '#content_box .add_files > .dd_plc', function(){
			$(this).removeClass('hov');
			$('body').removeClass('e_drop');
		})
		.on('drop', '#content_box .add_files > .dd_plc > .f', function(){//dragleave
			counter--;
			$(this).closest('.dd_plc').removeClass('drag').removeClass('hov');
			$('body').removeClass('e_drop');
		})
	
	$(document).on('click', '#content_box .fill_fields', function(){
		var bx_id = $(this).closest('.bx').data('bx_id');
		$('#content_box .id_'+bx_id+' .brand').val('acura').trigger('change').delay(100).queue(function(){ $('#content_box .id_'+bx_id+' .model').val('cl').trigger('change'); $(this).dequeue(); });
		$('#content_box .id_'+bx_id+' .bodytype').val('sdn').trigger('change');
		$('#content_box .id_'+bx_id+' .fuel').val('gsl').trigger('change');
		$('#content_box .id_'+bx_id+' .transmission').val('tpt').trigger('change');
		$('#content_box .id_'+bx_id+' .wheel_drive').val('44').trigger('change');
		$('#content_box .id_'+bx_id+' .color').val('l_grn').trigger('change');
		$('#content_box .id_'+bx_id+' .location').val('1').trigger('change');
		
		$('#content_box .id_'+bx_id+' .year').val('1').trigger('change');
		$('#content_box .id_'+bx_id+' .seats').val('1').trigger('change');
		$('#content_box .id_'+bx_id+' .mileage').val('1').trigger('change');
		$('#content_box .id_'+bx_id+' .engine').val('1').trigger('change');
		$('#content_box .id_'+bx_id+' .hp').val('1').trigger('change');
		$('#content_box .id_'+bx_id+' .price').val('1').trigger('change');
	})
});
//______________________________________________________________________________________________________________ END OF READY

window.setInterval(function(){
	$('.remove_after').each(function(){
		var removeAfter = parseInt ( $(this).attr( 'ra' ) );
		var unixTime =  new Date().getTime() / 1000;
		
		if ( (removeAfter != 0) && (removeAfter - unixTime < 0) ){
			$(this).html( 'ARCHIVE' );
			/*
			var data = {}; data['tp'] = 'adm'; data['pg'] = 'cars'; data['fn'] = 'erase';
			data['id'] = $(this).parent().parent().data('id');
			ajaxIt(data);
			$(this).parent().parent().css({'transform':'scale(0)', 'opacity':'0'}).delay(500).queue(function() { $(this).remove(); });
			*/
		}else{
			var timer = $(this).attr( 'timer' );
			$(this).attr( 'timer' , timer-1 );
			
			var tD = Math.floor( timer / (24*60*60) );
			var tH = Math.floor( ( timer - (tD*24*60*60) ) / (60*60) );
			var tM = Math.floor( ( timer - (tD*24*60*60) - (tH*60*60) ) / 60 );
			var tS = Math.floor( timer - (tD*24*60*60) - (tH*60*60) - (tM*60) );
			
			if ( tD/10 < 1 ){ tD = '0'+tD ;}
			if ( tH/10 < 1 ){ tH = '0'+tH ;}
			if ( tM/10 < 1 ){ tM = '0'+tM ;}
			if ( tS/10 < 1 ){ tS = '0'+tS ;}
			
			$(this).html( tD +', '+ tH +':'+ tM +':'+ tS );
		}
	})
}, 1000);

document.addEventListener('DOMContentLoaded', () => {
	const tooltipElements = document.querySelectorAll('[data-tooltip]');

	tooltipElements.forEach(element => {
		element.addEventListener('mouseover', (e) => {
			const tooltipHTML = e.target.getAttribute('data-tooltip');
			if (tooltipHTML) {
				const tooltip = document.createElement('div');
				tooltip.className = 'custom-tooltip';
				tooltip.innerHTML = tooltipHTML;
				document.body.appendChild(tooltip);

				const rect = e.target.getBoundingClientRect();
				tooltip.style.left = `${rect.left + window.scrollX + rect.width / 2}px`;
				tooltip.style.top = `${rect.bottom + window.scrollY + 10}px`;
			}
		});

		element.addEventListener('mouseout', () => {
			const tooltip = document.querySelector('.custom-tooltip');
			if (tooltip) {
				tooltip.remove();
			}
		});
	});
});

//______________________________________________________________________________________________________________ AJAX
//---------------------------------------------------------ajaxIt
function ajaxIt(dataX){
	$.ajax({
		url:'/ajax.php', method:'POST', type:'POST', data:dataX, async:true, datatype:'json', enctype:'multipart/form-data',
		//xhr:function(){ var myXhr = $.ajaxSettings.xhr(); if(myXhr.upload){ myXhr.upload.addEventListener('progress', progress, false); } return myXhr; },
		statusCode: {
			0: function(){
				alert('No internet connection');
			},
			403: function(){
				alert('Forbidden');
			},
			404: function(){
				alert('Page not found');
			},
			500: function(){
				alert('Internal server error');
			}
		},
        success: function(data){
			// Dynamic success handler based on current page
			var currentPage = getReqPage();
			
			if (currentPage === 'cars') {
				if (typeof ajaxSuccessCars === 'function') {
					ajaxSuccessCars(data);
				} else {
					ajaxSuccess(data);
				}
			} else if (currentPage === 'tyres') {
				if (typeof ajaxSuccess === 'function') {
					ajaxSuccess(data);
				} else {
					console.error('ajaxSuccess function not found for tyres');
				}
			} else {
				// Default fallback
				if (typeof ajaxSuccess === 'function') {
					ajaxSuccess(data);
				} else {
					console.error('No appropriate AJAX success handler found');
				}
			}
		}
	});
}

//---------------------------------------------------------uploadIt
function uploadIt(filesX, dataX=[]){
	if ( $('body').hasClass('ajx') ){
		setTimeout( function(){ uploadIt(filesX, dataX) }, 5000 );
	} else {
		var fd = new FormData(); var inp_nm; 
		$.each(dataX, function(k,v){ fd.append(k, v); });
		$.each(filesX, function(){
			inp_nm = $(this).attr('name');
			$.each($(this)[0].files, function(i,f){ fd.append(inp_nm, f); });
		});
		
		var rnd = (Math.random() + 1).toString(36).substring(7);
		$.ajax({
			url:'/ajax.php?rnd='+rnd, method:'POST', type:'POST', data:fd, async:true, datatype:'json', enctype:'multipart/form-data', cache:false, contentType:false, processData:false,
			statusCode: { 0:function(){ alert('No internet connection'); }, 403:function(){ alert('Forbidden'); }, 404:function(){ alert('Page not found'); }, 500:function(){ alert('Internal server error'); } },
			success:function(data){ ajaxSuccess(data); },
			error:function(xhr, status, error){ var err = eval("(" + xhr.responseText + ")"); alert(err.Message); }
		});
	}
}

async function ajaxCarImg(filesX, dataX) {
	console.log('ajaxCarImg started with:', { filesX, dataX });
	$('#stts_bar > .txt > .el').html('Adding to DB');

	try {
		console.log('Making initial AJAX request...');
		const initialResponse = await $.ajax({
			url: '/ajax.php',
			method: 'POST',
			data: dataX,
			dataType: 'json',
			processData: false,
			contentType: false,
			statusCode: {
				0: () => { console.error('No internet connection'); alert('No internet connection'); },
				403: () => { console.error('Forbidden'); alert('Forbidden'); },
				404: () => { console.error('Page not found'); alert('Page not found'); },
				500: () => { console.error('Internal server error'); alert('Internal server error'); }
			},
		});
		
		console.log('Initial response received:', initialResponse);

		const last_id = initialResponse?.rtrn?.last_id;

		if (!last_id) {
			console.error('Error: last_id not received', initialResponse);
			return null;
		}

		if (!$('#content_box').attr('data-car-id')) {
			$('#content_box').attr('data-car-id', last_id);
		}

		// Create FormData for sending all files at once
		const fd = new FormData();

		if (dataX instanceof FormData) {
			for (let [key, value] of dataX.entries()) {
				fd.append(key, value);
			}
		} else {
			$.each(dataX, (key, value) => {
				fd.append(key, value);
			});
		}

		fd.append('sub', 'file_load');
		fd.append('last_id', last_id);

		// Add files to FormData
		if (filesX[0].files && filesX[0].files.length > 0) {
			console.log('Adding files to FormData:', filesX[0].files.length, 'files');
			$.each(filesX[0].files, (index, file) => {
				fd.append(filesX.attr('name') + '[]', file);
			});
			fd.append('img_qu', filesX[0].files.length);
		} else {
			console.warn('No files selected for upload.');
		}

		// Send all files in one request
		console.log('Making file upload AJAX request...');
		const fileUploadResponse = await $.ajax({
			url: '/ajax.php',
			method: 'POST',
			data: fd,
			dataType: 'json',
			cache: false,
			contentType: false,
			processData: false,
			xhr: function () {
				var xhr = $.ajaxSettings.xhr();
				if (xhr.upload) {
					xhr.upload.addEventListener('progress', progress, false);
				}
				return xhr;
			},
			statusCode: {
				0: () => { console.error('No internet connection'); alert('No internet connection'); },
				403: () => { console.error('Forbidden'); alert('Forbidden'); },
				404: () => { console.error('Page not found'); alert('Page not found'); },
				500: () => { console.error('Internal server error'); alert('Internal server error'); }
			},
		});
		
		console.log('File upload response received:', fileUploadResponse);

		if (fileUploadResponse?.rtrn?.last_id) {
			console.log('Successfully completed ajaxCarImg with ID:', fileUploadResponse.rtrn.last_id);
			return fileUploadResponse.rtrn.last_id;
		} else {
			console.error('Error during file upload:', fileUploadResponse);
			return null;
		}
	} catch (error) {
		console.error('Exception in ajaxCarImg:', error);
		return null;
	}
}

function totalFileSize(files) {
	let total = 0;
	$.each(files, function (index, file) {
		total += file.size;
	});
	return total;
}

function ajaxItImg(filesX, dataX){
	$('#stts_bar > .txt > .el').html( 'Adding to DB' );
	$.ajax({
		url:'/ajax.php',
		method:'POST',
		type:'POST',
		data:dataX,
		async:true,
		datatype:'json',
		enctype:'multipart/form-data',
		statusCode: {
			0:function(){ alert('No internet connection'); },
			403:function(){ alert('Forbidden'); },
			404:function(){ alert('Page not found'); },
			500:function(){ alert('Internal server error'); }
		},
		success:function(data){ ajaxSuccess(data);
			var dataR = $.parseJSON(data);
			var inp_nm = filesX.attr('name'), qu = filesX[0].files.length, i_now = 0, last_id = dataR.rtrn.last_id;
			
			filesX.on('ajx', function(){
				if (typeof this.files[i_now] !== 'undefined'){
					var $this = $(this);
					var fd = new FormData();
					dataX['sub'] = 'file_load';//Sub fn name
					fd.append('last_id', last_id);//Last db id
					$.each(dataX, function(k,v){ fd.append(k, v); });//Data
					fd.append('img_k', i_now);//Img key
					fd.append('img_qu', qu);//Img total
					fd.append(inp_nm, filesX[0].files[i_now]);//Img make
			
					var lnW = Math.round( ( (i_now+1) / qu ) * 100 );
					$('#stts_bar > .ln').css('width', lnW+'%'); $('#stts_bar > .txt > .passed').html(lnW+'%'); $('#stts_bar > .txt > .el').html( 'Image #'+(i_now+1)+' / '+qu+' is in progress' );
					
					$.ajax({
						url:'/ajax.php', method:'POST', type:'POST', data:fd, async:true, datatype:'json', enctype:'multipart/form-data', cache:false, contentType:false, processData:false,
						xhr:function(){ var xhr = $.ajaxSettings.xhr(); if(xhr.upload){ xhr.upload.addEventListener('progress', progress, false);  } return xhr; },
						statusCode:{
							0:function(){ alert('No internet connection'); },
							403:function(){ alert('Forbidden'); },
							404:function(){ alert('Page not found'); },
							500:function(){ alert('Internal server error'); }
						},
						success:function(){ i_now++; $this.trigger('ajx'); }
					});
				} else {
					$('#stts_bar > .txt > .el').html( 'Finishing processing' ); $('#stts_bar > .ln').css('width', '100%');
					dataX['sub'] = 'make_it'; dataX['last_id'] = last_id;
					ajaxIt(dataX);
					
					fn_ajxQ(dataX['bx_id'], 'del');
					//return false;
				}
			}).trigger('ajx');
		}
	})
}

//---------------------------------------------------------ajax progress bar
function progress(e){
	if(e.lengthComputable){
		var prgrs = Math.round( (e.loaded/e.total)*100 );
		$('#stts_bar > .mcr_ln').css('width', prgrs+'%');
	}
}
/*
function progress(e){
	if(e.lengthComputable){
		if (localStorage.getItem('z_ajx_strt') !== null){
			var strt_t = localStorage.getItem('z_ajx_strt');
			
			var prgrs = Math.round( (e.loaded/e.total)*100 );
			$('#stts_bar > .ln').css('width', prgrs+'%');
			$('#stts_bar > .txt > .passed').html(prgrs+'%');
			
			var s =   ( unixTime() - strt_t );
			var bps =  s ? e.loaded / s : 0 ; //var kbps = bps / 1024 ;
			var b =   e.total - e.loaded;
			var sL = s ? Math.round(b / bps) : '*';
			$('#stts_bar > .txt > .left').html(sL!='*'?sL+'s':sL);
			
			//if (e.loaded==e.total){$('#stts_bar').removeClass('act'); $('#add_new').removeClass('ghost');}
		}
	}
}
*/

function fn_ajxQ(el='', e='chk'){
	var ajxQ = $('body').data('ajxq');
	if (typeof ajxQ === 'undefined' || ajxQ === false) {
		$('body').data('ajxq', '');
		ajxQ = $('body').data('ajxq');
	} //Make data-attr
	
	ajxQ = ajxQ.split(','); //Make arr
	if (e === 'chk'){
		return ajxQ[0] !== '' && ajxQ[0] === el;
	} else {
		if (ajxQ[0] === ''){ ajxQ.splice(0, 1); } //Remove first element if empty
		if (e === 'add'){ ajxQ.push(el); } //Add element
		if (e === 'add_prior'){ ajxQ.splice(1, 0, el); }
		
		var str='', i=0;
		$.each(ajxQ, function(k,v){
			if (e === 'del' && v === el) {
				return;
			}
			str += (i>0?',':'')+v; i++;
		})
		$('body').data('ajxq', str).attr('data-ajxq', str);
	}
}

window.onbeforeunload = function(){ if ( $('body').hasClass('ajx') ){ return "The server is processing requests, abort?"; } }