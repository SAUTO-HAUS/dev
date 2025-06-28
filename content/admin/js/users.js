$(document).ready(function() {

//--------------------------------------------------------------------------------------------------------------ACTION BUTTONS
//---------------------------------------------------------edit
$(document).on('click', '.adm_menu > .edit', function(){
	var confirmation = confirm( $(this).attr('title')+'?' );
	if (confirmation){
	$('#content_box').css({'pointer-events':'auto','opacity':'1'});
		var data = {};
		data['req_type'] = 'adm';
		data['req_func'] = 'edit_car';
		data['id'] = $(this).parent().attr('idz');
		ajaxIt(data);
	}
})

//---------------------------------------------------------daily item
$(document).on('click','.adm_menu > .favorite',function(){
	var confirmation = confirm( $(this).attr('title')+'?' );
	if (confirmation){
		var data = {};
		data['req_type'] = 'adm';
		data['req_func'] = 'daily_car';
		data['id'] = $(this).parent().attr('idz');
		ajaxIt(data);
	}
})

//---------------------------------------------------------hide
$(document).on('click','.adm_menu > .hide',function(){
	var confirmation = confirm( $(this).attr('title')+'?' );
	if (confirmation){
		var data = {};
		data['req_type'] = 'adm';
		data['req_func'] = 'hide_car';
		data['id'] = $(this).parent().attr('idz');
		ajaxIt(data);
	}
})

//---------------------------------------------------------delete
$(document).on('click','.adm_menu > .delete',function(){
	var confirmation = confirm( $(this).attr('title')+'?' );
	if (confirmation){
		var data = {};
		data['req_type'] = 'adm';
		data['req_func'] = 'delete_car';
		data['id'] = $(this).parent().attr('idz');
		ajaxIt(data);
	}
})

//---------------------------------------------------------close content box
$(document).on('click', '#content_box .close', function(){
	$('#content_box').css({'pointer-events':'none','opacity':'0'});
})

//--------------------------------------------------------------------------------------------------------------ADD_NEW_CAR
//---------------------------------------------------------create content_box
$(document).on('click', '#add_new', function(){
	var data = {};
	data['req_type'] = 'adm';
	data['req_func'] = 'add_new';
	data['req_sub'] = 'main';
		
	ajaxIt(data);
})

//---------------------------------------------------------safety, comfort, desription buttons
$(document).on('click', '#safety .button, #comfort .button, #description .button', function(){
	if ( $(this).parent().children('.content').hasClass('active') ){
		$(this).parent().children('.content').removeClass('active');
	}
	else{
		$(this).parent().children('.content').addClass('active');
	}
})

//---------------------------------------------------------add photos button
$(document).on('change', '#new_photos input', function(){
	$('#new_photos > div').html('');
	var textZ = $('#new_photos > div').attr('textZ');
	var files = $(this)[0].files;
	if (files.length > 0){ $('#new_photos > div').removeClass('empty_val'); }
	$('#new_photos > div').append( files.length +' '+textZ );
})

//---------------------------------------------------------input .needed
$(document).on('input', '.needed', function(){
	$(this).removeClass('empty_val');
})

//---------------------------------------------------------model select
$(document).on('input', '#new_brand', function(){
	var data = {};
	data['req_type'] = 'adm';
	data['req_func'] = 'add_new';
	data['req_sub'] = 'model_search';
	data['brand'] = $('#new_brand').val();
	ajaxIt(data);
})

//---------------------------------------------------------select main_photo
$(document).on('click', '#new_img img', function(){
	$('#new_img img').removeClass('selected');
	$(this).addClass('selected');
	$('#content_box > #confirm').removeClass('not_ready');
})

//---------------------------------------------------------confirm button
$(document).on('click', '#content_box > #confirm', function(){
	
	if ( !$(this).hasClass('not_ready') && $(this).attr('step')=='2' ){
		$('#content_box').css({'pointer-events':'none','opacity':'0'});
		
		var data = {};
		data['req_type'] = 'adm';
		data['req_func'] = 'add_new';
		data['req_sub'] = 'main_photo';
		data['index'] = $('#new_img img.selected').attr('idz');
	
		ajaxIt(data);
	}
	
	if ( $(this).attr('step')=='1' ){
		$('#content_box > #confirm').removeClass('not_ready');
		$('.needed').each(function(){ if( $(this).val() ) {$(this).removeClass('empty_val');} else {$(this).addClass('empty_val'); $('#content_box > #confirm').addClass('not_ready');} })
		$('.numInput').each(function(){ if( $(this).val()>0 ) {$(this).removeClass('empty_val');} else {$(this).addClass('empty_val'); $('#content_box > #confirm').addClass('not_ready');} })
	
		var files = $('#new_photos > input')[0].files;
		for (var i = 0; i < files.length; i++){}
		if (i==0){ $('#content_box > #confirm').addClass('not_ready'); $('#new_photos > div').addClass('empty_val'); }
	}
	
	if ( !$(this).hasClass('not_ready') && $(this).attr('step')=='1' ){
		$(this).attr('step','2').addClass('not_ready');
		$('#content_box .step_one').css({'max-height':'0', 'opacity':'0'});
		
		var data = {};
		data['req_type'] = 'adm';
		data['req_func'] = 'add_new';
		data['req_sub'] = 'step1';
		
		data['author'] = $('#user_name').html();
		
		$('.needed').each(function(){ data[$(this).attr('name')] = $(this).val(); })
		/*
		$('#description > .content > textarea').each(function(){ data[$(this).attr('name')] = $(this).val(); })
		
		$('#safety > .content > input:checked').each(function(){ data['safety'] = $(this).val(); })
		
		$('#comfort > .content > input:checked').each(function(){ data[$(this).attr('name')] = $(this).val(); })
		*/
		
		ajaxIt(data);
	}
	
})

//--------------------------------------------------------------------------------------------------------------AJAX_SUCCESS
function ajaxSuccess(data){
	var data = $.parseJSON(data);
	
	//-------------------------------------------------------------------------PHOTO DELETE (zont)
	if(data.req_func=='photo_delete'){
		var thisPhoto = $('#photo_frame .photo.checked[img_id="'+data.photo_id+'"]');
		thisPhoto.css({'transform':'scale(0)'});
		setTimeout(function(){ thisPhoto.remove(); }, 1000);
		
		var count = $('#photo_actions .count').text()*1;
		$('#photo_actions .count').text(count-1);
		if ( (count-1)==0 ){ $('#photo_actions').removeClass('active'); }
	}
	
	//-------------------------------------------------------------------------ADD_NEW_CAR
	if(data.req_func=='add_new'){
		
		//---------------------------open content_box
		if (data.req_sub=='main'){
			$('#content_box').html(data.content).css({'pointer-events':'auto','opacity':'1'});
			$('#status_bar').css('width','0');
		}
		
		//---------------------------model insert
		if (data.req_sub=='model_search'){
			var defText = $('#new_model').attr('def_text');
			defText = '<option value="">'+defText+'</option>';
			$('#new_model').html(defText + data.content);
			$('#status_bar').css('width','0');
		}
		
		//---------------------------upload new photos after confirm
		if (data.req_sub=='step1'){
			$('#status_bar').css('width','0');
			uploadIt('adm', 'add_new', 'img_upload', $('#new_photos input[type=file]'), data.new_id);
		}
		
		//---------------------------do after upload (remove div's and add photos)
		if (data.req_sub=='img_upload'){
			$('#status_bar').css('width','0');
			$('#top').remove();
			$('#main_info').remove();
			$('#add_info').remove();
			$('#price_info').remove();
			$('#new_photos').remove();
			$('#content_box').prepend(data.content);
		}
		
		//---------------------------add car to main catalog
		if (data.req_sub=='main_photo'){
			$("#content .catalog_page #add_new").after(data.content);
		}
	}
	
	//-------------------------------------------------------------------------FILTER AND MORE CARS
	if(data.req_func=='filter'){
		var needed = $('#search_content .search_select[clicked="1"]').attr('type');
		$('#search_content .search_select[clicked="1"]').html('');
		$.each([needed], function( index, value ) { $.each(data.search[value], function( index2, value2 ) { $('#filter_'+value).append(value2); }); });
		var selectz = $('#search_content .search_select[clicked="1"]').attr('selectz');
		$('#search_content .search_select option[value='+selectz+']').attr('selected','selected')
	}
	
	if(data.req_func=='search'){
		$('#content .catalog_page').html('');
		$('#search_content .search_select').html('');
	}
	
	if(data.req_func=='search'||data.req_func=='more_cars'){
		var moreText = $('#content .catalog_page').attr('moreCarText');
		$('#more_cars').remove();
		$('#car_countz').attr({ pos : data.car_pos });
		
		$.each(data.cars, function( index, value ) { $('#content .catalog_page').append(value); });
		
		if(data.car_pos!=null){
			$('#content .catalog_page').append('<div id="more_cars">'+moreText+'</div>');
		}
		
		if(data.req_func=='search'){
			var filterArr = [ 'brand', 'model', 'year', 'bodytype', 'fuel', 'transmission', 'wheel_drive', 'color', 'prima_rata', 'price' ];
			$.each(filterArr, function( index, value ) { $.each(data.search[value], function( index2, value2 ) { $('#filter_'+value).append(value2); }); });
			$('#search_content .search_select[clicked="1"]').attr({'clicked':null});
		}
	}	
}

//--------------------------------------------------------------------------------------------------------------AJAX_IT
//---------------------------------------------------------def ajax
function ajaxIt(dataX){
	$.ajax({
		url: '/ajax.php',
		method: 'POST',
        type: 'POST',
		data: dataX,
		xhr: function(){
			var myXhr = $.ajaxSettings.xhr();
			if(myXhr.upload){ myXhr.upload.addEventListener('progress', progress, false); }
			return myXhr;
		},
		//async: false,
		//data : {'developer':'OMA'},
		statusCode: {
            404: function() { $("#message").text("Page not found"); },
            500: function() { $("#message").text("Internal server error"); }
        },
        success: function(data){
            ajaxSuccess(data);
        },
		datatype: "json"
	});
}

//---------------------------------------------------------upload ajax
function uploadIt(req_type, req_func, req_sub, dataX, idX){
    var fd = new FormData();
	
	fd.append('req_type', req_type);
	fd.append('req_func', req_func);
	fd.append('req_sub', req_sub);
	fd.append('id', idX);
	
	$.each(dataX[0].files, function (i, file) {
		fd.append('photo[]', file);
	});
	
    $.ajax({
        url: '/ajax.php',
		method: 'POST',
        type: 'POST',
		xhr: function(){
			var myXhr = $.ajaxSettings.xhr();
			if(myXhr.upload){ myXhr.upload.addEventListener('progress', progress, false); }
			return myXhr;
		},
        data: fd,
		enctype: 'multipart/form-data',
		cache: false,
		contentType: false,
        processData: false,
		//async: false,
        success: function (data) {
            ajaxSuccess(data);
        },
		datatype: "json"
    });
}

//---------------------------------------------------------ajax progress bar
function progress(e){
	if(e.lengthComputable){
		var progressStatus = (e.loaded/e.total)*100;
		$('#status_bar').css('width',progressStatus+'%');
	}
}

//--------------------------------------------------------------------------------------------------------------MORE CARS BUTTON
$(document).on('click', '#more_cars', function(){
	var searchArr = {};
	
	searchArr['req_type'] = 'adm';
	searchArr['req_func'] = 'more_cars';
	searchArr['car_count'] = $('#car_countz').attr('count');
	searchArr['car_pos'] = $('#car_countz').attr('pos');
	
	$('.s_main').each(function(){
		searchArr[$(this).attr('name')] = $(this).val();
	})
	$('.s_extra').each(function(){
		searchArr[$(this).attr('name')] = $(this).val();
	})
	
	ajaxIt(searchArr);
})

//--------------------------------------------------------------------------------------------------------------PHOTOS (zont)
var mousedDownFired = false;

$(document).on('click', '#photo_frame .photo', function(){
	if(mousedDownFired){ mousedDownFired = false; return; }
	var count = $('#photo_actions .count').text()*1;
	if ( $(this).hasClass('checked') ){ $(this).removeClass('checked');	$('#photo_actions .count').text(count-1); }
	else{ $(this).addClass('checked'); $('#photo_actions .count').text(count+1); }
	photoInformer();
})

function mouseHold( photo ){
	if( photo.hasClass('main_photo') ){ photo.removeClass('main_photo'); }
	else{ photo.addClass('main_photo');	}
	mousedDownFired = true;
}

var timeoutId = 0;
$(document).on('mousedown', '#photo_frame .photo', function() {
	var photo = $(this);
    timeoutId = setTimeout(function(){mouseHold( photo )}, 500);
}).on('mouseup mouseleave', '#photo_frame .photo', function() {
    clearTimeout(timeoutId);
});

//--------------------------------------------------------------------------------------------------------------PHOTO INFORMER (zont)
function photoInformer(){
	if( ($('#photo_actions .count').text()*1) > 0 ){ $('#photo_actions').addClass('active'); }
	else{ $('#photo_actions').removeClass('active'); }
}

$('#photo_actions .uncheck_all').on('click', function(){
	$('#photo_frame .photo.checked').removeClass('checked');
	$('#photo_actions .count').text('0');
	$('#photo_actions').removeClass('active');
})

$('#photo_actions .check_all').on('click', function(){
	$('#photo_frame .photo:not(.checked)').each(function(){
		var count = $('#photo_actions .count').text()*1;
		$(this).addClass('checked');
		$('#photo_actions .count').text(count+1);
	})
	$('#photo_actions').addClass('active');
})

$('#photo_actions .delete').on('click', function(){
	var confirmation = confirm( $(this).attr('title')+'?' );
	if (confirmation) {
		var data = {};
		
		data['req_type'] = 'adm';
		data['req_func'] = 'photo_delete';
		
		$('#photo_frame .photo.checked').each(function(){
			data['photo_id'] = $(this).attr('img_id');
			data['photo_name'] = $(this).attr('img_name');
			ajaxIt(data);
		})
	}
})

$('#photo_frame .add_photo').on('click', function(){
	var confirmation = confirm( $(this).attr('title')+'?' );
	if (confirmation) {
		var data = {};
		
		data['req_type'] = 'adm';
		data['req_func'] = 'photo_add';
		
		$('#photo_frame .photo.checked').each(function(){
			data['photo_id'] = $(this).attr('img_id');
			data['photo_name'] = $(this).attr('img_name');
			ajaxIt(data);
		})
	}
})
//--------------------------------------------------------------------------------------------------------------

});