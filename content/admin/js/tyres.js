var reqType = 'adm';
var reqPage = 'tyres';

$(document).ready(function(){
	$(document).on('click', '.bx > .comment', function(){ overlay('open', $(this).parent().children('.comment_txt'), 'self'); })
	//$(document).on('click', '.bx > .print', function(){ overlay('open', $(this).parent().children('.print_bx'), 'self'); })
	
	//___________________________________________________________________________________________________________ ITEM ADM_MENU BTNS CLICK
	//-------------------------------------------------------ADD_NEW
	$(document).on('click', '#add_new', function(){
		var data = {}; data['tp'] = reqType; data['pg'] = reqPage; data['fn'] = 'add_new'; data['sub'] = 'start';
		data['author'] = $('#xInf').data('u');
		data['bx_id'] = (Math.random() + 1).toString(36).substring(7);
		ajaxIt(data);
	})
	
	//-------------------------------------------------------EDIT
	$(document).on('click', '.adm_menu > .btn[data-fn="edit"]', function(){
		var data = {}; data['tp'] = reqType; data['pg'] = reqPage; data['fn'] = 'edit'; data['sub'] = 'start';
		data['id'] = $(this).closest('.bx').data('id');
		ajaxIt(data);
	})
	
	//-------------------------------------------------------AV, N/A (FN_AV)
	$(document).on('click','.adm_menu > .btn.fn_av',function(){
		var confirmation = confirm( $(this).attr('title')+'?' );
		if (confirmation){
			var data = {}; data['tp'] = reqType; data['pg'] = reqPage; data['fn'] = $(this).data('fn');
			data['id'] = $(this).closest('.bx').data('id');
			ajaxIt(data);
			var zAlt = $(this).data('alt');
			$(this).data({'fn':($(this).data('fn')=='av0'?'av1':'av0'), 'alt':$(this).attr('title') }).attr('title', zAlt);
		}
	})
	
	//-------------------------------------------------------HIDE, REVEAL (FN_HR)
	$(document).on('click','.adm_menu > .btn.fn_hr',function(){
		var confirmation = confirm( $(this).attr('title')+'?' );
		if (confirmation){
			var data = {}; data['tp'] = reqType; data['pg'] = reqPage; data['fn'] = $(this).data('fn');
			data['id'] = $(this).closest('.bx').data('id');
			ajaxIt(data);
			var zAlt = $(this).data('alt');
			$(this).data({'fn':(data['fn']=='hide'?'reveal':'hide'), 'alt':$(this).attr('title') }).attr('title', zAlt);
			if ( data['fn']=='hide' ){ $(this).closest('.bx').addClass('hided'); } else { $(this).closest('.bx').removeClass('hided'); }
		}
	})
	
	//-------------------------------------------------------DELETE, RESTORE, ERASE (FN_DRE)
	$(document).on('click','.adm_menu > .btn.fn_dre',function(){
		var confirmation = confirm( $(this).attr('title')+'?' );
		if (confirmation){
			var data = {}; data['tp'] = reqType; data['pg'] = reqPage; data['fn'] = $(this).data('fn');
			data['id'] = $(this).closest('.bx').data('id');
			ajaxIt(data);
			$(this).closest('.bx').css({'transform':'scale(0)', 'opacity':'0'}).delay(500).queue(function() { $(this).remove(); });
		}
	})
	
	//___________________________________________________________________________________________________________ OVERLAY ACTIONS
	//---------------------------------------------------------seo, commentary
	$(document).on('click', '#content_box .seo .button, #content_box .txt .button', function(){
		if ( $(this).parent().children('.content').hasClass('active') ){ $(this).parent().children('.content').removeClass('active'); } else { $(this).parent().children('.content').addClass('active'); }
	})
	
	//---------------------------------------------------------check photo count
	$(document).on('change', '#content_box .img_bx input[type="file"]', function(){ if ( $(this)[0].files.length>0 ){ $(this).closest('.img_bx').find('.dd_plc').removeClass('empty'); } })
	//---------------------------------------------------------check input .need
	$(document).on('input', '.need', function(){ $(this).removeClass('empty'); })
	
	//---------------------------------------------------------model select
	$(document).on('change', '#content_box .brand', function(){
		var data = {}; data['tp'] = reqType; data['pg'] = reqPage; data['fn'] = 'add_new'; data['sub'] = 'mo_search';
		data['br'] = $(this).val(); data['bx_id'] = $(this).closest('.bx').data('bx_id');
		ajaxIt(data);
	})
	
	//---------------------------------------------------------input seo changed
	$(document).on('input', '#content_box .seo > .content > label > input', function(){
		var chCheck = $(this).parent().children('.change_checker');
		if (chCheck!='1'){chCheck.val('1');}
	})
	
	//___________________________________________________________________________________________________________ CONFIRM BUTTON
	$(document).on('click', '#content_box > .bx .confirm', function(){
		var x = 0; var bx_id = $(this).closest('.bx').data('bx_id');
		
		//-------------------------------------------------------ADD NEW
		if ( $(this).data('fn')=='add_new' ){
			var fileInp = $('#content_box > .id_'+bx_id+' > .img_bx input[type="file"]');
			if ( !fileInp[0].files.length > 0 ){ x=1; $('#content_box > .id_'+bx_id+' > .img_bx > .dd_plc').addClass('empty'); }
			$('#content_box > .id_'+bx_id+' .need:not(.nmb)').each(function(){ if( $(this).val() ){ $(this).removeClass('empty'); } else { x=1; $(this).addClass('empty'); } })
			$('#content_box > .id_'+bx_id+' .need.nmb').each(function(){ if( $(this).val()>0 ){ $(this).removeClass('empty'); } else { x=1; $(this).addClass('empty'); } })
			
			if (x==0){
				var data = {}; data['tp'] = reqType; data['pg'] = reqPage; data['fn'] = 'add_new'; data['sub'] = 'end';
				data['author'] = $('#xInf').data('u'); data['bx_id'] = bx_id;
				$('#content_box > .id_'+bx_id+' .need:not([type="file"]), #content_box > .id_'+bx_id+' .use[type="radio"]:checked').each(function(){ data[$(this).attr('name')] = $(this).val(); })
				$('#content_box > .id_'+bx_id+' .no_need').each(function(){ data[$(this).attr('name')] = $(this).val(); })
				
				//console.log(bx_id);
				//localStorage.setItem('z_ajx_strt', unixTime());
				
				$('#content_box > .id_'+bx_id+' > .top > .close').click();
				$('#add_new').after('<div id="id_'+bx_id+'" class="bx wait"><div class="txt"><span class="v1">Making</span><span class="v2">Queue</span></div><div class="ico loading"></div></div>');
				
				fn_ajxQ(bx_id, 'add');
				function ajxChk(){
					if ( fn_ajxQ(bx_id, 'chk') ){ $('#id_'+bx_id).addClass('now'); ajaxItImg( fileInp, data ); } 
					else { setTimeout(function(){ ajxChk(); }, 3000 ); }
				} ajxChk();
			}
			
		}
		
		//-------------------------------------------------------EDIT
		if ( $(this).data('fn')=='edit' ){
			var fileInp = $('#content_box > .id_'+bx_id+' > .img_bx input[type="file"]');
			if ( !fileInp[0].files.length > 0 ){ x=1; $('#content_box > .id_'+bx_id+' > .img_bx > .dd_plc').addClass('empty'); }
			$('#content_box > .id_'+bx_id+' .need:not(.nmb)').each(function(){ if( $(this).val() ){ $(this).removeClass('empty'); } else { x=1; $(this).addClass('empty'); } })
			$('#content_box > .id_'+bx_id+' .need.nmb').each(function(){ if( $(this).val()>0 ){ $(this).removeClass('empty'); } else { x=1; $(this).addClass('empty'); } })
			
			var data = {}; data['tp'] = reqType; data['pg'] = reqPage; data['fn'] = 'edit'; data['sub'] = 'end';
			data['author'] = $('#xInf').data('u'); data['bx_id'] = bx_id;
			$('#content_box > .id_'+bx_id+' .need:not([type="file"]), #content_box > .id_'+bx_id+' .use[type="radio"]:checked').each(function(){ data[$(this).attr('name')] = $(this).val(); })
			$('#content_box > .id_'+bx_id+' .no_need').each(function(){ data[$(this).attr('name')] = $(this).val(); })
			
			data['main_img'] = $('#content_box > .id_'+bx_id+' input.main_img:checked').val();
			
			data['del_img'] = [];
			$('#content_box > .id_'+bx_id+' .del_img:checked').each(function(){ data['del_img'].push( $(this).val() ); });
			
			$('#content_box > .id_'+bx_id+' > .top > .close').click();
			$('#content > .ctlg > .bx[data-id="'+bx_id+'"]').addClass('wait').attr('id','id_'+bx_id).html('<div class="txt"><span class="v1">Changing</span><span class="v2">Queue</span></div><div class="ico loading"></div>');
			
			if (x==0){ fn_ajxQ(bx_id, 'add'); }
			else if (x==1){ fn_ajxQ(bx_id, 'add_prior');}
			
			function ajxChk(){
				if ( fn_ajxQ(bx_id, 'chk') ){ $('#id_'+bx_id).addClass('now'); ajaxItImg( fileInp, data ); } 
				else { setTimeout(function(){ ajxChk(); }, 3000 ); }
			} ajxChk();
		}
		
	})
	
	//___________________________________________________________________________________________________________ PHOTO
	//-----------DELETE
	$(document).on('click', '#content_box .prv.imgs.ready .it .btn.delete', function(){
		//var it  = $(this).closest('.it');
		//if ( it.hasClass('img2del') ){ it.removeClass('img2del'); } else { it.addClass('img2del'); }
		
		/*
		var confirmation = confirm( $(this).attr('title')+'?' );
		if (confirmation){
			var data = {}; data['tp'] = reqType; data['pg'] = reqPage; data['fn'] = 'delete_photo';
			data['photo_id'] = $(this).parent().data('id');
			data['it_id'] = $(this).parent().parent().data('it_id');
			//ajaxIt(data);
			$(this).parent().remove();
		}
		*/
	})
	//-----------DO MAIN
	$(document).on('click', '#content_box > .exist_photos > .photo > .do_main', function(){
		var confirmation = confirm( $(this).attr('title')+'?' );
		if (confirmation){
			var data = {}; data['tp'] = reqType; data['pg'] = reqPage; data['fn'] = 'new_main_photo';
			data['photo_id'] = $(this).parent().data('id');
			data['it_id'] = $(this).parent().parent().data('it_id');
			//ajaxIt(data);
			
			$('#content_box > .exist_photos .photo.main').removeClass('main');
			$(this).parent().addClass('main');
			
		}
	})
	//-----------DOWNLOAD ZIP
	$(document).on('click', '#content_box .download_zip', function(){
		var confirmation = confirm( $(this).attr('title')+'?' );
		if (confirmation){
			var data = {}; data['tp'] = reqType; data['pg'] = reqPage; data['fn'] = 'download_zip';
			data['it_id'] = $(this).data('it_id');
			ajaxIt(data);
		}
	})
	//-----------CHANGE IMG POSITION
	$(document).on('click', '#content_box .chng_pos:not(".done")', function(){
		var confirmation = confirm( $(this).children('.off').attr('title')+'?' );
		if (confirmation){
			$(this).addClass('done');
			var bx = $('#content_box .prv > .its.bx'); bx.css({'height':bx.height()});
			
			$('.prv.ready .it.f_img').each(function(){ var pos = $(this).position(); $(this).data({'posL':pos.left, 'posT':pos.top}).css({'left':pos.left, 'top':pos.top});}) //.offset()
				.promise().done(function(){ $('.prv.ready .it.f_img').css('position','absolute').addClass('posing'); })
			
			$('#content_box .prv.ready .it.f_img.posing')
				.draggable({ start:function(){$(this).addClass('dragging');}, revert:'invalid', revertDuration:100, drag:function(){}, stop:function(){$(this).removeClass('dragging');} })
				.droppable({
					accept: '.dragging', //accept: function(elem){ return elem.hasClass($(this).attr('title')); }, hoverClass: 'highlight',
					drop: function(e, ui){
						var drg = $(ui.draggable), drgN = drg.data('n'), drgL = drg.data('posL'), drgT = drg.data('posT'), drp = $(this), drpN = drp.data('n'), drpL = drp.data('posL'), drpT = drp.data('posT');
						
						for(n=1;n<=$('.it.f_img.posing').length;n++){
							if ( (n>=drpN && n<drgN) || (n>drgN && n<=drpN) ){
								var el = $('.prv.ready .it.f_img.posing[data-n="'+n+'"]');
								var s = $('.prv.ready .it.f_img.posing[data-n="'+( drgN>drpN?n+1:n-1 )+'"]'), sN = s.data('n'), sL = s.data('posL'), sT = s.data('posT');
								el.addClass('chng').data({'xN':sN, 'xL':sL, 'xT':sT}).animate({'left':sL, 'top':sT}, 400);
								el.children('.nm').html(sN);
							}
							
							if ( (drgN>drpN && n==drgN) || (drgN<drpN && n==drpN) ){
								$('.it.f_img.posing.chng').each(function(){var el = $(this); el.data({'n':el.data('xN'), 'posL':el.data('xL'), 'posT':el.data('xT')}).attr('data-n', el.data('xN')).css('order', el.data('xN')).removeClass('chng'); })
								drg.data({'n':drpN, 'posL':drpL, 'posT':drpT}).css({'order':drpN}).attr('data-n',drpN).animate({'left':drpL, 'top':drpT}, 200)
								drg.children('.nm').html(drpN);
								return false;
							}
						}
					}
				})
				.droppable( 'option', 'tolerance', 'intersect' )
				.draggable( 'enable' );
		}
	})
	
	$(document).on('click', '#content_box .chng_pos.done', function(){
		var confirmation = confirm( $(this).children('.on').attr('title')+'?' );
		if (confirmation){
			$(this).removeClass('done');
			$('.prv > .its.bx').css({'height':'auto'});
			$('.it.f_img.posing').draggable( 'disable' ).css({'position':'unset'}).removeClass('posing');
			
			var data = {}; data['tp'] = reqType; data['pg'] = reqPage; data['fn'] = 'edit'; data['sub'] = 'pos_img';
			data['bx_id'] = $(this).data('it_id'); data['pos_img'] = {};
			$('#content_box .prv.ready .it.f_img').each(function(){ data['pos_img'][$(this).data('n')] = $(this).data('id'); })
			ajaxIt(data);
		}
	})
	
	//--------------------------------------------------------------------------------------------------------------MORE_BUTTON
	$(document).on('click', '#more_it', function(){
		var data = {}; data['tp'] = reqType; data['pg'] = reqPage; data['fn'] = 'more';
		data['it_qu'] = $('#it_cnt').data('count');
		data['it_pos'] = $('#it_cnt').data('pos');
		
		$('.s_main').each(function(){ data[$(this).attr('name')] = $(this).val(); })
		ajaxIt(data);
	})
	
	//--------------------------------------------------------------------------------------------------------------FILTER SEARCH
	$(document).on('click', '#search_content .search_select[clicked!="1"]', function(){
	
		$('#search_content .search_select[clicked="1"]').attr('clicked',null);
		var selectz = $(this).val();
		var data = {}; data['tp'] = reqType; data['pg'] = reqPage; data['fn'] = 'filter';
		
		$('.s_main').not(this).each(function(){ data[$(this).attr('name')] = $(this).val(); })
		$(this).attr({'clicked':'1', 'selectz':selectz});
		ajaxIt(data);
	})
	
	$(document).on('change', '#search_content .search_select', function(){
		var data = {}; data['tp'] = reqType; data['pg'] = reqPage; data['fn'] = 'search';
		data['it_qu'] = $('#it_cnt').data('count');
		data['it_pos'] = $('#it_cnt').data('pos');
		
		$('.s_main').each(function(){ data[$(this).attr('name')] = $(this).val(); })
		ajaxIt(data);
	})

});



//______________________________________________________________________________________________________________END OF READY / AJAX_SUCCESS
function ajaxSuccess(data){
	var data = $.parseJSON(data);
	
	if( data!=null /*$.isArray(data) || data.length*/ ) {
		
		//-------------------------------------------------------------------------DOWNLOAD_ZIP
		if(data.fn=='download_zip'){ window.open(data.url, '_blank'); return false; }
	
		//-------------------------------------------------------------------------CREATE_CONTENT_BOX
		if( (data.fn=='add_new' || data.fn=='edit') && data.sub=='start' ){
			$('#status_bar').css('width','0');
			$('#content_box').prepend(data.rtrn).addClass('act');
			$('#main_admin').addClass('no_active');
		}
	
		//-------------------------------------------------------------------------ADD_NEW
		if(data.fn=='add_new'){
			if (data.sub=='end'){ $('body').addClass('ajx'); $('#stts_bar').addClass('act'); }
			
			//---------------------------model insert
			if (data.sub=='mo_search'){
				var defText = $('#content_box .id_'+data.rtrn.bx_id+' .model').attr('def_text');
				defText = '<option value="">'+defText+'</option>';
				$('#content_box .id_'+data.rtrn.bx_id+' .model').html(defText + data.rtrn.str);
			}
		
			if (data.sub=='file_load'){}
			
			if (data.sub=='make_it'){
				$('#stts_bar').removeClass('act'); $('#stts_bar > .ln').attr('style',''); $('#add_new').removeClass('ghost');
				$('#id_'+data.rtrn.bx_id).replaceWith(data.rtrn.bx); $('body').removeClass('ajx');
				$('#content_box .id_'+data.rtrn.bx_id).remove();
			}
			//location.reload();
		}
		
		//-------------------------------------------------------------------------EDIT
		if(data.fn=='edit'){
			if (data.sub=='end'){ $('body').addClass('ajx'); $('#stts_bar').addClass('act'); }
			
			//---------------------------model insert
			if (data.sub=='mo_search'){
				var defText = $('#content_box .id_'+data.rtrn.bx_id+' .model').attr('def_text');
				defText = '<option value="">'+defText+'</option>';
				$('#content_box .id_'+data.rtrn.bx_id+' .model').html(defText + data.rtrn.str);
			}
		
			if (data.sub=='file_load'){}
			
			if (data.sub=='make_it'){
				$('#stts_bar').removeClass('act'); $('#stts_bar > .ln').attr('style',''); $('#add_new').removeClass('ghost');
				$('#id_'+data.rtrn.bx_id).replaceWith(data.rtrn.bx); $('body').removeClass('ajx');
				$('#content_box .id_'+data.rtrn.bx_id).remove();
			}
		}
		
		//-------------------------------------------------------------------------FILTER AND MORE BUTTON
		if(data.fn=='filter'){
			var need = $('#search_content .search_select[clicked="1"]').attr('type');
			$('#search_content .search_select[clicked="1"]').html('');
			$.each([need], function( index, value ) { $.each(data.search[value], function( index2, value2 ) { $('#filter_'+value).append(value2); }); });
			var selectz = $('#search_content .search_select[clicked="1"]').attr('selectz');
			$('#search_content .search_select[clicked="1"] option[value='+selectz+']').attr('selected','selected')
		}
		
		if(data.fn=='search'||data.fn=='more'){
			$('#it_cnt').data({ 'pos' : data.it_pos }).attr({ 'data-pos' : data.it_pos });
			
			if(data.fn=='more'){ $('#content .ctlg').append(data.rtrn); }
			else if(data.fn=='search'){
				var add_new = $('#add_new').prop('outerHTML');
				$('#content .ctlg').html('').append( add_new + data.rtrn );
				
				var filterArr = [ 'br','mo','w','h','d','c','ss','author','id','act' ];
				$.each(filterArr, function( index, value ) { $.each(data.search[value], function( index2, value2 ) { $('#filter_'+value).append(value2); }); });
				$('#search_content .search_select[clicked="1"]').attr({'clicked':null});
			}
			
			if(data.it_pos==null){ $('#more_it').addClass('none'); }else{ $('#more_it').removeClass('none'); }
		}
	}
}