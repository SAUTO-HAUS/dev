var reqType = 'adm';
// Dynamic page detection to avoid conflicts with tyres.js
function getReqPage() {
	// Check URL to determine if we're on cars page
	if (window.location.href.indexOf('/cars/') !== -1) {
		return 'cars';
	}
	// Fallback to cars for this file
	return 'cars';
}
var reqPage = getReqPage();

function sendToFacebookCars() {
	// Get selected time
	const selectedTime = document.getElementById('facebook_schedule_time').value;
	var confirmation = confirm( 'Опубликовать в facebook в ' + selectedTime + '?' );
	if (confirmation){

		$('body').addClass('ajx');
		$('#stts_bar').addClass('act');
		$('#stts_bar > .ln').attr('style','width:100%');


		let carId = $('#content_box').data('car-id'); // Получаем ID автомобиля
		let statusText = $(this).siblings('.status-text'); // Получаем элемент с текстом

		$.ajax({
			url: '/ajax.php',
			method: 'POST',
			data: {
				tp: reqType,
				pg: reqPage,
				fn: 'sendToFacebookCars', // Имя обработчика в PHP
				id: carId,
				local_id: $('select[name=loc]').val(),
				schedule_time: selectedTime
			},
			statusCode: {
				0: function(){
					alert('No internet connection');
				},
				403: function(){
					alert('Forbidden');
				},
				504: function(){
					alert('Прошло слишком много времени - проверьте если пост был опубликован');

					$('#stts_bar').removeClass('act'); $('#stts_bar > .ln').attr('style',''); $('body').removeClass('ajx');
				},
				500: function(){
					alert('Internal server error');
				}
			},
			success: function(response) {
				console.log('Статус обновлен:', response);
				let d = JSON.parse( response);

				if(d['status'] == false) {
					alert("Произошла ошибка публикации");
				}
				else {
					alert("Опубликовано");
				}

				$('#stts_bar').removeClass('act');
				$('#stts_bar > .ln').attr('style','');
				$('body').removeClass('ajx');

				/*
				// Изменяем текст в зависимости от состояния чекбокса
				if (status === 1) {
					statusText.text('Нет в наличии');
				} else {
					statusText.text('Есть в наличии');
				}
				*/
			},
			error: function(xhr, status, error) {
				console.error('Ошибка AJAX:', error);
			}
		});

	}
}

function sendToTelegramCars() {
	// Get selected time
	const selectedTime = document.getElementById('telegram_schedule_time').value;
	var confirmation = confirm( 'Программировать в telegram в ' + selectedTime + '?' );
	if (confirmation){

		$('body').addClass('ajx');
		$('#stts_bar').addClass('act');
		$('#stts_bar > .ln').attr('style','width:100%');


		let carId = $('#content_box').data('car-id'); // Получаем ID автомобиля
		let statusText = $(this).siblings('.status-text'); // Получаем элемент с текстом

		$.ajax({
			url: '/ajax.php',
			method: 'POST',
			data: {
				tp: reqType,
				pg: reqPage,
				fn: 'sendToTelegramCars', // Имя обработчика в PHP
				id: carId,
				schedule_time: selectedTime
			},
			statusCode: {
				0: function(){
					alert('No internet connection');
				},
				403: function(){
					alert('Forbidden');
				},
				504: function(){
					alert('Прошло слишком много времени - проверьте если пост был опубликован');

					$('#stts_bar').removeClass('act'); $('#stts_bar > .ln').attr('style',''); $('body').removeClass('ajx');
				},
				500: function(){
					alert('Internal server error');
				}
			},
			success: function(response) {
				console.log('Статус обновлен:', response);

				let d = JSON.parse( response);
				if(d['status'] == false) {
					alert("Произошла ошибка публикации");
				}
				else {
					alert("Опубликовано");
				}

				$('#stts_bar').removeClass('act');
				$('#stts_bar > .ln').attr('style','');
				$('body').removeClass('ajx');

				/*
				// Изменяем текст в зависимости от состояния чекбокса
				if (status === 1) {
					statusText.text('Нет в наличии');
				} else {
					statusText.text('Есть в наличии');
				}
				*/
			},
			error: function(xhr, status, error) {
				console.error('Ошибка AJAX:', error);
			}
		});

	}
}

window.addEventListener('load', function() {
	if (!$('#content_box').attr('data-car-id')) {
		const selects = document.querySelectorAll('select');
		selects.forEach(select => {
			select.selectedIndex = 0;
		});
	}
});

$(document).ready(function(){
	// Initialize display limit from localStorage after a short delay
	setTimeout(function() {
		initializeDisplayLimit();
	}, 100);
	
	// Handle display limit dropdown change
	$(document).on('change', '#cars-display-limit', function() {
		console.log('Dropdown changed to:', $(this).val());
		handleDisplayLimitChange($(this).val());
	});
	
	$(document).on('click', '.bx > .comment', function(){ overlay('open', $(this).parent().children('.comment_txt'), 'self'); })
	$(document).on('click', '.bx > .print', function(){ overlay('open', $(this).parent().children('.print_bx'), 'self'); })
	
	//___________________________________________________________________________________________________________ ITEM ADM_MENU BTNS CLICK
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
			var data = {};
			data['tp'] = reqType;
			data['pg'] = reqPage;
			data['fn'] = $(this).data('fn');
			data['id'] = $(this).closest('.bx').data('id');
			ajaxIt(data);
			$(this).closest('.bx').css({'transform':'scale(0)', 'opacity':'0'}).delay(500).queue(function() { $(this).remove(); });
		}
	})
	
	//___________________________________________________________________________________________________________ OVERLAY ACTIONS
	//---------------------------------------------------------seo, commentary
	$(document).on('click', '#content_box .seo .button, #content_box .txt .button', function(){ if ( $(this).parent().children('.content').hasClass('active') ){ $(this).parent().children('.content').removeClass('active'); } else { $(this).parent().children('.content').addClass('active'); }
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
		var x = 0;
		var bx_id = $(this).closest('.bx').data('bx_id');

		//-------------------------------------------------------EDIT
		if ( $(this).data('fn')==='edit' ){
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
	}).on('click', '#content_box .chng_pos.done', function(){
		var confirmation = confirm( $(this).children('.on').attr('title')+'?' );
		if (confirmation){
			$(this).removeClass('done');
			$('.prv > .its.bx').css({'height':'auto'});
			$('.it.f_img.posing').draggable( 'disable' ).css({'position':'unset'}).removeClass('posing');

			var data = {};
			data['tp'] = reqType;
			data['pg'] = reqPage;
			data['fn'] = 'edit';
			data['sub'] = 'pos_img';
			data['bx_id'] = $(this).data('it_id');
			data['pos_img'] = {};
			$('#content_box .prv.ready .it.f_img').each(function(){
				data['pos_img'][$(this).data('n')] = $(this).data('id');
			})
			ajaxIt(data);
		}
	})
	
	//-----------------------------MORE_BUTTON
	$(document).on('click', '#more_it', function(){
		// Save to localStorage (from sitescripts.js functionality)
		localStorage.setItem( 'z_adm_pg_'+getReqPage()+'_more_btn_clk', $(this).data('i') );
		$(this).data('i', ($(this).data('i') + 1) ).attr('data-i', ($(this).data('i') + 1) );
		
		// AJAX call to load more items - force 'cars' to avoid tyres.js conflicts
		var data = {}; data['tp'] = 'adm'; data['pg'] = 'cars'; data['fn'] = 'more';
		data['it_qu'] = $('#it_cnt').data('count');
		data['it_pos'] = $('#it_cnt').data('pos');
		
		$('.s_main').each(function(){ data[$(this).attr('name')] = $(this).val(); })
		ajaxIt(data);
	})
	
	//-------------------------------FILTER SEARCH
	$(document).on('click', '#search_content .search_select[clicked!="1"]', function(){
	
		$('#search_content .search_select[clicked="1"]').attr('clicked',null);
		var selectz = $(this).val();
		var data = {}; data['tp'] = reqType; data['pg'] = getReqPage(); data['fn'] = 'filter';
		
		$('.s_main').not(this).each(function(){ data[$(this).attr('name')] = $(this).val(); })
		$(this).attr({'clicked':'1', 'selectz':selectz});
		ajaxIt(data);
	});

	//------------------------------ Submit cars forms
	$(document).on('submit', '#sautoForm', async function (event) {
		event.preventDefault();

		let bx_id = $(this).closest('.bx').data('bx_id');

		let contentBox = $('.id_' + bx_id);
		let $form = $('#sautoForm');

		let main_img = $('#content_box > .id_'+bx_id+' input.main_img:checked').val();
		let del_img = [];
		$('#content_box > .id_'+bx_id+' .del_img:checked').each(function(){
			del_img.push($(this).val());
		});

		let fileInput = contentBox.find('.img_bx input[type="file"]');
		let isValid = validateInputsSauto(contentBox, fileInput);

		if (isValid) {
			let confirmButton = $('button.confirm');
			$(confirmButton).addClass('disabled').attr('disabled', 'disabled').text(confirmButton.data('processing'));
			let data = collectFormDataSauto($form, bx_id);

			data.append('main_img', main_img);
			data.append('del_img', del_img);

			await ajaxCarImg(fileInput, data);
			finishProcess(confirmButton);
		}
	}).on('submit', '#main_form_999', async function (event) {
		event.preventDefault();

		let bx_id = $(this).closest('.bx').data('bx_id');
		let contentBox = $('.id_' + bx_id);
		let $formSauto = $('#sautoForm');

		let main_img = $('#content_box > .id_'+bx_id+' input.main_img:checked').val();
		let del_img = [];
		$('#content_box > .id_'+bx_id+' .del_img:checked').each(function(){
			del_img.push($(this).val());
		});

		// Synchronize price from 999 form to main form before data collection
		console.log('=== SEARCHING FOR PRICE FIELD ===');
		let allNumberInputs = $('#main_form_999').find('input[type="number"]');
		console.log('All number inputs found:', allNumberInputs.length);
		allNumberInputs.each(function(index) {
			console.log(`Input ${index}:`, this, 'name:', $(this).attr('name'), 'value:', $(this).val());
		});
		
		let priceField = $('#main_form_999').find('input[type="number"]').filter(function() {
			return $(this).attr('name') && $(this).attr('name').includes('feature[') && 
				   $(this).closest('.form-group').find('select[name*="feature_units"]').length > 0;
		});
		console.log('Price field after filtering:', priceField.length);
		
		if (priceField.length > 0) {
			let priceValue = priceField.val();
			let priceUnitSelect = priceField.closest('.form-group').find('select[name*="feature_units"]');
			let priceUnit = priceUnitSelect.val();
			
			console.log('=== PRICE SYNCHRONIZATION DEBUG ===');
			console.log('Price field found:', priceField.length);
			console.log('Price field element:', priceField[0]);
			console.log('Price field name attribute:', priceField.attr('name'));
			console.log('Price field raw DOM value:', priceField[0].value);
			console.log('Price field jQuery val():', priceValue, 'type:', typeof priceValue);
			console.log('Price unit select:', priceUnitSelect[0]);
			console.log('Price unit value:', priceUnit);
			
			// Update the main form's price field
			if (priceValue && priceUnit) {
				let prcField = $formSauto.find('input[name="prc"]');
				let curField = $formSauto.find('select[name="cur"], input[name="cur"]');
				
				console.log('Before update - prc field value:', prcField.val());
				console.log('Before update - cur field value:', curField.val());
				
				prcField.val(priceValue);
				curField.val(priceUnit.toUpperCase());
				
				console.log('After update - prc field value:', prcField.val());
				console.log('After update - cur field value:', curField.val());
			}
			console.log('=== END PRICE SYNCHRONIZATION DEBUG ===');
		}
		
		let dataSauto = collectFormDataSauto($formSauto, bx_id);
		dataSauto.append('main_img', main_img);
		dataSauto.append('del_img', del_img);

		let fileInput = contentBox.find('.img_bx input[type="file"]');

		if (!fileInput[0].files.length && !$('#content_box').data('car-id')) {
			if (!confirm('No car image uploaded. Do you want to continue without an image?')) {
				return false;
			}
		}

		let confirmButton = $('button.confirm_999');
		$(confirmButton).addClass('disabled').attr('disabled', 'disabled').text(confirmButton.data('processing'));
		let checkbox_n_a_new = $('.car-checkbox-n_a_new');
		let isChecked = checkbox_n_a_new.prop('checked');

		console.log('Form submission started');

		let carId = await ajaxCarImg(fileInput, dataSauto);
		console.log('ajaxCarImg returned with carId:', carId);
		
		if (!carId) {
			// Handle the case when ajaxCarImg fails
			console.error('Failed to get car ID from ajaxCarImg');
			showError('Failed to upload car data. Please try again.');
			finishProcess(confirmButton);
			return false;
		}

		if ($('#content_box').data('car-id')) {
			carId = $('#content_box').data('car-id');
			console.log('Using car ID from content_box data attribute:', carId);
		}
		
		let data = {
			tp: reqType,
			pg: reqPage,
			fn: '999_catalog',
			sub: 'set_999',
			category: $(this).val(),
			bx_id: $(this).closest('.bx').data('bx_id'),
			carId: carId,
			isChecked: isChecked
		};

		console.log('Preparing final data for ajaxMain:', data);
		const $form = $(this);
		data['form_data'] = $form.serialize();

		console.log('Calling ajaxMain...');
		try {
			$.ajax({
				url: '/ajax.php',
				method: 'POST',
				data: data,
				dataType: 'json',
				success: function(response) {
					console.log('ajaxMain response received:', response);
					if (response.rtrn?.error) {
						let errorMessages = '';
						if (response.rtrn.error.reason) {
							errorMessages = 'Reason: ' + response.rtrn.error.reason + '\n';
						}

						if (response.rtrn.error.errors?.length > 0) {
							response.rtrn.error.errors.forEach(error => {
								errorMessages += `Feature ID: ${error.feature_id}, Message: ${error.message}\n`;
							});
						}

						if (!errorMessages) {
							errorMessages = 'Something went wrong. Please try again later.';
						}

						showError(errorMessages);
					} else {
						console.log('ajaxMain completed successfully');
					}
					finishProcess(confirmButton); // Always finish the process regardless of success or error
				},
				error: function(jqXHR, textStatus, errorThrown) {
					// Handle AJAX failure
					console.error('AJAX request failed:', textStatus, errorThrown);
					showError('Failed to complete the car submission. Please try again.');
					finishProcess(confirmButton);
				}
			});
		} catch (error) {
			console.error('Exception in form submission:', error);
			showError('An error occurred during form submission: ' + error.message);
			finishProcess(confirmButton);
		}
	}).on('change', '#search_content .search_select', function(){
		var data = {}; data['tp'] = reqType; data['pg'] = reqPage; data['fn'] = 'search';
		data['it_qu'] = $('#it_cnt').data('count');
		data['it_pos'] = $('#it_cnt').data('pos');
		
		$('.s_main').each(function(){ data[$(this).attr('name')] = $(this).val(); })
		ajaxIt(data);
	}).on('change', '#content_box .category', function(){
		let data = {
			tp: reqType,
			pg: reqPage,
			fn: '999_catalog',
			sub: 'get_subcategory',
			category: $(this).val(),
			bx_id: $(this).closest('.bx').data('bx_id'),
		};
		if ($(this).val()) {
			ajaxMain(data, function (response) {
				var defText = $('#content_box .id_'+response.rtrn.bx_id+' .subcategory').attr('def_text');
				defText = '<option value="">'+defText+'</option>';
				$('#content_box .id_'+response.rtrn.bx_id+' .subcategory').html(defText + response.rtrn.str);
			});
		}
	}).on('change', '#content_box .subcategory', function(){
		let data = {
			tp: reqType,
			pg: reqPage,
			fn: '999_catalog',
			sub: 'get_subcategory_offer_types',
			category: $(this).closest('.bx').find('.category').val(),
			subcategory: $(this).val(),
			bx_id: $(this).closest('.bx').data('bx_id'),
		};

		if ($(this).val()) {
			ajaxMain(data, function (response) {
				var defText = $('#content_box .id_'+response.rtrn.bx_id+' .subcategory_offer_types').attr('def_text');
				defText = '<option value="">'+defText+'</option>';
				$('#content_box .id_'+response.rtrn.bx_id+' .subcategory_offer_types').html(defText + response.rtrn.str);
			});
		}
	}).on('change', '#content_box .subcategory_offer_types', function(){
		let data = {
			tp: reqType,
			pg: reqPage,
			fn: '999_catalog',
			sub: 'get_features',
			category: $(this).closest('.bx').find('.category').val(),
			subcategory: $(this).closest('.bx').find('.subcategory').val(),
			offer_type: $(this).val(),
			account_id: $(this).closest('.bx').find('.account_999_id').val(),
			bx_id: $(this).closest('.bx').data('bx_id'),
		};

		if ($(this).val()) {
			ajaxMain(data, function (response) {
				$('#content_box .id_'+response.rtrn.bx_id+' .features').html(response.rtrn.str);
			});
		}
	}).on('change', '#content_box .account_999_id', function(){
		let data = {
			tp: reqType,
			pg: reqPage,
			fn: '999_catalog',
			sub: 'get_phone',
			account_id: $(this).val(),
			feature_id: $('.feature-contacts').data('feature-id')
		};
		ajaxMain(data, function (response) {
			$('.feature-contacts').html(response.rtrn.str);
		});
	}).on('change', '.feature-select', function(){
		const featureId = this.dataset.featureId;
		const dependsOnId = this.value;
		const dependentSelect = document.querySelector(`.feature-select[data-depends-on="${featureId}"]`);

		if (dependentSelect) {
			const dependentFeatureId = dependentSelect.dataset.featureId;
			
			// Clear dependent select immediately
			const defText = $(dependentSelect).attr('def_text') || 'Select...';
			$(dependentSelect).html(`<option value="">${defText}</option>`);
			
			let data = {
				tp: reqType,
				pg: reqPage,
				fn: '999_catalog',
				sub: 'get_features_depends',
				feature_id: dependentFeatureId,
				subcategory: $(this).closest('.bx').find('.subcategory').val(),
				dependency_feature_id: featureId,
				parent_option_id: dependsOnId,
				bx_id: $(this).closest('.bx').data('bx_id'),
			};

			if ($(this).val()) {
				// Add small delay to ensure DOM is ready
				setTimeout(() => {
					ajaxMain(data, function (response) {
						var defText = $(dependentSelect).attr('def_text') || 'Select...';
						defText = '<option value="">'+defText+'</option>';
						$(dependentSelect).html(defText + response.rtrn.str);
					});
				}, 100);
			}
		}
	}).on('change', '.scenario-option-radio', function(){
		const scenarios = {
			simple: ['109', '112', '113', '111', '130', '132', '133', '134', '142', '147', '148'],
			medium: ['109', '115', '110', '114', '116', '112', '113', '111', '118', '124', '123', '117', '2201', '130', '131', '132', '133', '134', '136', '137', '141', '142', '143', '144', '147', '148', '150'],
			maximal: ['109', '115', '110', '114', '116', '1639', '112', '113', '111', '119', '118', '126', '124', '125', '128', '123', '117', '1638', '122', '1766', '2201', '130', '131', '132', '133', '134', '135', '136', '137', '138', '139', '140', '141', '142', '143', '144', '145', '147', '148', '149', '150']
		};

		const selectedScenario = scenarios[this.value];

		$('.features input[type="checkbox"]').prop('checked', false);

		if (selectedScenario) {
			selectedScenario.forEach(option => {
				const checkbox = $(`#feature_${option}`);
				if (checkbox.length) {
					checkbox.prop('checked', true);
				}
			});
		}
	}).on('change', '#announcement_type', function(){
		$('input[name="promotions"]')
			.closest('div')
			.hide()
			.find('input')
			.prop('disabled', true);
		$('input[name="promotions"]').prop('checked', false);

		switch($(this).val()) {
			case 'auto_company':
				showPromotions(['plus', 'turbo', 'test'], 'plus');
				break;
			case 'auto_company_min':
				showPromotions(['basic', 'lite'], 'basic');
				break;
			case 'auto_realization':
				showPromotions(['plus'], 'plus');
				break;
			case 'auto_realization_min':
				showPromotions(['lite'], 'lite');
				break;
			default:
				showAllPromotions();
		}
		initializeSchedules();
	})


	function showPromotions(allowedTypes, defaultType) {
		allowedTypes.forEach(function(type) {
			$(`input[name="promotions"][value="${type}"]`)
				.closest('div')
				.show()
				.find('input')
				.prop('disabled', false);
		});

		if (defaultType) {
			$(`input[name="promotions"][value="${defaultType}"]`).prop('checked', true);
		} else {
			$('input[name="promotions"]:visible:first').prop('checked', true);
		}
	}

	function showAllPromotions() {
		$('input[name="promotions"]')
			.closest('div')
			.show()
			.find('input')
			.prop('disabled', false);

		$('input[name="promotions"]:first').prop('checked', true);
	}

	function checkFormValidity() {
		let confirmButton = $('.confirm_999');
		let requiredFields = $('.form-control.required, .form-check-input[required]:not(#confirm_rules)');
		let contactCheckboxes = $('.form-check-input.contact');
		let confirmRulesCheckbox = $('#confirm_rules');

		let allValid = true;

		requiredFields.each(function () {
			const $field = $(this);
			if ($field.is(':checkbox')) {
				if (!$field.is(':checked')) {
					$field.addClass('empty');
					allValid = false;
				}
			} else if ($field.is('textarea')) {
				if (!$field.val().trim()) {
					$field.addClass('empty');
					allValid = false;
				}
			} else if (!$field.val().trim()) {
				$field.addClass('empty');
				allValid = false;
			}
		});

		if (!contactCheckboxes.is(':checked')) {
			contactCheckboxes.parent('.form-check').addClass('empty');
			allValid = false;
		}

		if (!confirmRulesCheckbox.is(':checked')) {
			confirmRulesCheckbox.parent('.form-check').addClass('empty');
			allValid = false;
		}

		if (allValid) {
			$(confirmButton).css({ 'pointer-events': 'auto', 'opacity': '1' });
		} else {
			$(confirmButton).css({ 'pointer-events': 'none', 'opacity': '0.5' });
		}
	}

	// Ensure confirm_rules checkbox stays checked
	$('#confirm_rules').prop('checked', true);
	
	// Force confirm_rules to stay checked with interval
	setInterval(function() {
		if ($('#confirm_rules').length && !$('#confirm_rules').is(':checked')) {
			$('#confirm_rules').prop('checked', true);
			console.log(' Forțat confirm_rules să rămână bifat');
		}
	}, 100);
	
	// Initial check
	checkFormValidity();

	let texts = {};
	$.getJSON("/api/texts.json", function (data) {
		texts = data;
	});

	// Attach event listeners
	$(document).on('input change', '.form-control.required, .form-check-input[required], .form-check-input.contact', function () {
		if ($(this).hasClass('contact')) {
			$('.contact-container').removeClass('empty');
		}
		$(this).removeClass('empty');
		checkFormValidity();
	}).on('change', '#confirm_rules', function () {
		// Forțează checkbox-ul să rămână bifat
		$(this).prop('checked', true);
		$(this).parent('.form-check').removeClass('empty');
		checkFormValidity();
	}).on('change', "#feature_20, #feature_21", function () {
		updateField1404();
	}).on("change", "#announcement_type", function () {
		const type = $(this).val();
		const textOptionsWrapper = $("#text_options_wrapper");
		const textOptions = $("#text_options");
		const textArea = $("#feature_13");

		textOptionsWrapper.hide();
		textOptions.empty();
		textArea.val("");

		if (type === "auto_company" || type === "auto_company_min") {
			texts['auto_company'].forEach((item, index) => {
				const radioButton = `
						<div class="text-option-wrapper" style="margin-right: 20px; margin-bottom: 10px;">
							<label style="display: inline-block; text-align: center;">
								<input type="radio" name="text_option" value="${index}" class="text-option-radio">
								<span>${item.title}</span>
							</label>
							<div class="text-preview" style="border: 1px solid #ccc; padding: 10px; margin-top: 5px; border-radius: 5px; background: #f9f9f9;">
								${item.text}
							</div>
						</div>
					`;
				textOptions.append(radioButton);
			});
			textOptionsWrapper.show();
		} else if (type === "auto_realization" || type === "auto_realization_min") {
			textArea.val(texts['auto_realization'][0].text);
		}
	}).on("change", ".text-option-radio", function () {
		const index = $(this).val();
		const selectedText = texts['auto_company'][index].text;
		$("#feature_13").val(selectedText);
	});
});


document.addEventListener("DOMContentLoaded", function () {
	const modal = document.getElementById("boosterModal");
	const boosterIcon = document.querySelector(".booster");
	const closeModal = document.querySelector(".closeBoosterModal");
	const saveBtn = document.getElementById("saveBooster");
	const pauseBtn = document.getElementById("pauseBooster");

	// Only proceed if booster modal elements exist
	if (!modal) {
		console.log('Booster modal not found - skipping booster functionality');
		return;
	}

	if (boosterIcon) {
		boosterIcon.addEventListener("click", function () {
			modal.style.display = "block";
		});
	}
	
	if (closeModal) {
		closeModal.addEventListener("click", function () {
			modal.style.display = "none";
		});
	}

	window.addEventListener("click", function (event) {
		if (event.target === modal) {
			modal.style.display = "none";
		}
	});

	if (saveBtn) {
		saveBtn.addEventListener("click", function () {
			const period = document.getElementById("period").value;
			const dailyLimit = document.getElementById("dailyLimit").value;
			const click_price = document.getElementById("click_price").value;

			if (!period || !dailyLimit) {
				$('#boosterModal #period').css('background-color', period ? '' : 'rgba(255,0,0,0.1)');
				$('#boosterModal #dailyLimit').css('background-color', dailyLimit ? '' : 'rgba(255,0,0,0.1)');
				return;
			}
			if(dailyLimit < 10){
				$('#boosterModal #dailyLimit').css('background-color', dailyLimit ? '' : 'rgba(255,0,0,0.1)');
				return;
			}
			if (click_price > dailyLimit * 100) {
				$('#boosterModal #period').css('background-color', click_price ? '' : 'rgba(255,0,0,0.1)');
				return;
			}

			let dataX = {
				'tp': reqType,
				'pg': reqPage,
				'fn': 'saveBooster',
				'id': $('#content_box').data('car-id'),
				'period': period,
				'daily_limit': dailyLimit * 100,
				'click_price': click_price
			};

			$.ajax({
				url:'/ajax.php', method:'POST', type:'POST', data:dataX, async:true, datatype:'json', enctype:'multipart/form-data',
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
					data = $.parseJSON(data);
					if (data.error) {
						$('.errorBooster').html('Error: ' + data.error.message);
					} else {
						modal.style.display = "none";
						location.reload();
						$('.errorBooster').html('');
					}
				}
			});
		});
	}

	$('#boosterModal #period, #boosterModal #dailyLimit').on('input', validateBoosterFields);

	function validateBoosterFields() {
		let period = $('#boosterModal #period').val().trim();
		let dailyLimit = $('#boosterModal #dailyLimit').val().trim();
		let periodValue = parseInt(period, 10);
		let errorBooster = $('.errorBooster');

		$('#boosterModal #period').css('background-color', period ? '' : 'rgba(255,0,0,0.1)');
		$('#boosterModal #dailyLimit').css('background-color', dailyLimit ? '' : 'rgba(255,0,0,0.1)');

		if (!period || isNaN(periodValue) || (periodValue < 1 || (periodValue > 60 && periodValue !== 999))) {
			errorBooster.text('Period must be between 1-60 or exactly 999.');
			$('#boosterModal #period').css('border-color', 'red');
		} else {
			errorBooster.text('');
			$('#boosterModal #period').css('border-color', '');
		}
	}

	pauseBtn.addEventListener("click", function () {
		let dataX = {
			'tp': reqType,
			'pg': reqPage,
			'fn': 'pauseBooster',
			'id': $('#content_box').data('car-id'),
		};

		$.ajax({
			url:'/ajax.php', method:'POST', type:'POST', data:dataX, async:true, datatype:'json', enctype:'multipart/form-data',
			success: function(data){
				console.log(data);
				modal.style.display = "none";
				location.reload();
			}
		});
	});
});

function validateInputsSauto($contentBox, fileInput) {
	let isValid = true;

	if (!$('#content_box').data('car-id')) {
		if (!fileInput[0].files.length) {
			console.warn('No image uploaded for new car');
			// isValid = false;
			// $contentBox.find('.img_bx > .dd_plc').addClass('empty');
		}
	}

	// Validation of text fields
	$contentBox.find('.need:not(.nmb)').each(function () {
		if ($(this).val()) {
			$(this).removeClass('empty');
		} else {
			isValid = false;
			$(this).addClass('empty');
		}
	});

	// Validation of number fields
	$contentBox.find('.need.nmb').each(function () {
		if ($(this).val() > 0) {
			$(this).removeClass('empty');
		} else {
			isValid = false;
			$(this).addClass('empty');
		}
	});
	return isValid;
}

function collectFormDataSauto($form, bx_id) {
	let data = new FormData();

	data.append('tp', reqType);
	data.append('pg', reqPage);
	data.append('fn', 'add_new');
	data.append('sub', 'end');
	data.append('author', $('#xInf').data('u'));
	data.append('bx_id', bx_id);

	$form.serializeArray().forEach(({ name, value }) => {
		data.append(name, value);
	});

	return data;
}

function checkQueue(bx_id, fileInput, data) {
	function retryCheck() {
		setTimeout(() => checkQueue(bx_id, fileInput, data), 3000);
	}

	if (fn_ajxQ(bx_id, 'chk')) {
		$(`#id_${bx_id}`).addClass('now');
		ajaxItImg(fileInput, data);
	} else {
		retryCheck();
	}
}

function updateField1404() {
	const baseText = $('#feature_1404').attr('start_text');
	const brand = $("#feature_20").val() ? $("#feature_20 option:selected").text().trim().toLowerCase() : "";
	const model = $("#feature_21").val() ? $("#feature_21 option:selected").text().trim().toLowerCase() : "";

	let additionalText = "";
	if (brand) {
		additionalText += `, ${brand}`;
	}
	if (model) {
		additionalText += `, ${model}`;
	}

	$("#feature_1404").val(baseText + additionalText);
}

function ajaxMain(data, callback){
	$.ajax({
		url:'/ajax.php',
		method:'POST',
		data:data,
		async:true,
		dataType:'json',
		enctype:'multipart/form-data',
		statusCode: {
			0: function () {
				alert('No internet connection');
			},
			403: function () {
				alert('Forbidden');
			},
			404: function () {
				alert('Page not found');
			},
			500: function () {
				alert('Internal server error');
			},
		},
		success: function (response) {
			if (typeof callback === 'function') {
				callback(response);
			}
		},
		error: function (xhr, status, error) {
			console.error('AJAX Error:', status, error);
		}
	});
}

//______________________________________________________________________________________________________________END OF READY / AJAX_SUCCESS
function ajaxSuccessCars(data){
	if (typeof data === "string") {
		try {
			data = $.parseJSON(data);
		} catch (e) {
			console.error("Invalid JSON string:", data);
		}
	}
	
	if( data!=null /*$.isArray(data) || data.length*/ ) {
		//------------------------------------DOWNLOAD_ZIP
		if(data.fn === 'download_zip') {
			window.open(data.url, '_blank');
			return false;
		}
	
		//-----------------------------------CREATE_CONTENT_BOX
		if( (data.fn === 'add_new' || data.fn === 'edit') && data.sub === 'start') {
			$('#status_bar').css('width','0');
			$('#content_box').prepend(data.rtrn).addClass('act');
			$('#main_admin').addClass('no_active');
		}
	
		//------------------------------------- ADD NEW
		if(data && data.fn === 'add_new'){
			if (data.sub === 'end'){
				$('body').addClass('ajx');
				if ($('#stts_bar').length) {
					$('#stts_bar').addClass('act');
				}
			}
			
			//---------------------------model insert
			if (data.sub === 'mo_search'){
				var defText = $('#content_box .id_' + data.rtrn.bx_id + ' .model').attr('def_text');
				defText = '<option value="">'+defText+'</option>';
				$('#content_box .id_'+data.rtrn.bx_id+' .model').html(defText + data.rtrn.str);
			}
		
			if (data.sub==='file_load'){}
			
			if (data.sub==='make_it'){
				$('#stts_bar').removeClass('act');
				$('#stts_bar > .ln').removeAttr('style');
				$('#add_new').removeClass('ghost');
				$('#id_' + data.rtrn.bx_id).replaceWith(data.rtrn.bx);
				$('body').removeClass('ajx');

				var backUrl = $('#content_box').attr('back-url');
				if (backUrl) {
					window.location.href = backUrl;
				} else {
					console.warn('Back URL is missing.');
				}
			}
		}
		
		//----------------------------------------EDIT
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
		
		//------------------------------------FILTER AND MORE BUTTON
		if(data.fn=='filter'){
			var need = $('#search_content .search_select[clicked="1"]').attr('type');
			$('#search_content .search_select[clicked="1"]').html('');
			$.each([need], function( index, value ) { $.each(data.search[value], function( index2, value2 ) { $('#filter_'+value).append(value2); }); });
			var selectz = $('#search_content .search_select[clicked="1"]').attr('selectz');
			$('#search_content .search_select[clicked="1"] option[value='+selectz+']').attr('selected','selected')
		}
		
		if(data.fn=='search'||data.fn=='more'){
			$('#it_cnt').data({ 'pos' : data.it_pos }).attr({ 'data-pos' : data.it_pos });
			
			if(data.fn=='more'){ 
				$('#content .ctlg').append(data.rtrn); 
			}
			else if(data.fn=='search'){
				var add_new = $('#add_new').prop('outerHTML');
				$('#content .ctlg').html('').append( add_new + data.rtrn );
				
				var filterArr = [ 'br', 'mo', 'author', 'id', 'vis', 'act' ];
				$.each(filterArr, function( index, value ) { $.each(data.search[value], function( index2, value2 ) { $('#filter_'+value).append(value2); }); });
				$('#search_content .search_select[clicked="1"]').attr({'clicked':null});
			}
			
			if(data.it_pos==null){ $('#more_it').addClass('none'); }else{ $('#more_it').removeClass('none'); }
		}
	}
}

function finishProcess(button) {
	$(button).removeClass('disabled').removeAttr('disabled', 'disabled').text($(button).data('origin'));
	$('body').removeClass('ajx');

	var backUrl = $('#content_box').attr('back-url');
	if (backUrl) {
		window.location.href = backUrl;
	} else {
		console.warn('Back URL is missing.');
	}
}

function showError(message) {
	console.error(message);
	alert(message);
}
$(document).on('change', '.car-checkbox-n_a_new', function() {
	let carId = $(this).data('car-id'); // Получаем ID автомобиля
	let status = this.checked ? 1 : 0; // Если чекбокс включен, ставим 1, иначе 0
	let statusText = $(this).siblings('.status-text'); // Получаем элемент с текстом

	$.ajax({
		url: '/ajax.php',
		method: 'POST',
		data: {
			tp: reqType,
			pg: reqPage,
			fn: 'update_n_a_new', // Имя обработчика в PHP
			id: carId,
			n_a_new: status
		},
		success: function(response) {
			console.log('Статус обновлен:', response);

			// Изменяем текст в зависимости от состояния чекбокса
			if (status === 1) {
				statusText.text('Нет в наличии');
			} else {
				statusText.text('Есть в наличии');
			}
		},
		error: function(xhr, status, error) {
			console.error('Ошибка AJAX:', error);
		}
	});
});

// Brand change handler for model filtering (both add form and catalog filter)
$(document).on('change', 'select[name="br"], select[name="br_search"]', function() {
	var selectedBrand = $(this).val();
	var modelSelect;
	var bxId = $('.bx').data('bx_id') || 'default';
	
	// Determine which model dropdown to target based on the brand dropdown
	if ($(this).attr('name') === 'br_search') {
		// This is the catalog filter
		modelSelect = $('select[name="mo_search"]');
	} else {
		// This is the add/edit form
		modelSelect = $('select[name="mo"]');
	}
	
	// Skip if no model dropdown found
	if (modelSelect.length === 0) {
		return;
	}
	
	// Clear current model options
	var defaultText = modelSelect.attr('def_text') || 'Model';
	if ($(this).attr('name') === 'br_search') {
		// For filter, use "all" option
		modelSelect.html('<option value="all">All</option>');
	} else {
		// For add form, use default text
		modelSelect.html('<option value="">' + defaultText + '</option>');
	}
	
	if (selectedBrand && selectedBrand !== 'all' && selectedBrand.trim() !== '') {
		// Show loading state
		modelSelect.prop('disabled', true);
		modelSelect.append('<option>Loading...</option>');
		
		// Make AJAX call to get models for selected brand
		console.log('Loading models for brand:', selectedBrand, 'Type:', typeof selectedBrand);
		$.ajax({
			url: '/ajax.php',
			method: 'POST',
			data: {
				tp: 'adm',
				pg: 'cars',
				fn: 'add_new',
				sub: 'mo_search',
				br: selectedBrand,
				bx_id: bxId
			},
			success: function(response) {
				console.log('Model AJAX response received:', response);
				try {
					var data = typeof response === 'string' ? JSON.parse(response) : response;
					console.log('Parsed model data:', data);
					
					// Clear loading state and add default option
					if ($(this).attr('name') === 'br_search') {
						// For filter, use "all" option
						modelSelect.html('<option value="all">All</option>');
					} else {
						// For add form, use default text
						modelSelect.html('<option value="">' + defaultText + '</option>');
					}
					
					// Add the models returned from server
					// Backend returns data in data.rtrn.str format
					var modelsHtml = null;
					if (data.rtrn && data.rtrn.str) {
						modelsHtml = data.rtrn.str;
					} else if (data.str) {
						// Fallback for direct str format
						modelsHtml = data.str;
					}
					
					if (modelsHtml) {
						console.log('Adding models to dropdown:', modelsHtml);
						modelSelect.append(modelsHtml);
					} else {
						console.warn('No models returned for brand:', selectedBrand, 'Full response:', data);
					}
					
					// Re-enable the dropdown
					modelSelect.prop('disabled', false);
					console.log('Model dropdown re-enabled');
				} catch (e) {
					console.error('Error parsing model response:', e, 'Raw response:', response);
					if ($(this).attr('name') === 'br_search') {
						modelSelect.html('<option value="all">All</option>');
					} else {
						modelSelect.html('<option value="">' + defaultText + '</option>');
					}
					modelSelect.prop('disabled', false);
				}
			}.bind(this),
			error: function(xhr, status, error) {
				console.error('Error loading models:', error);
				if ($(this).attr('name') === 'br_search') {
					modelSelect.html('<option value="all">All</option>');
				} else {
					modelSelect.html('<option value="">' + defaultText + '</option>');
				}
				modelSelect.prop('disabled', false);
			}.bind(this)
		});
	} else {
		// If no brand selected, just re-enable the model dropdown
		modelSelect.prop('disabled', false);
	}
});

// Display limit functionality
function initializeDisplayLimit() {
	console.log('Initializing display limit...');
	
	// Check if dropdown exists
	if ($('#cars-display-limit').length === 0) {
		console.log('Dropdown not found, retrying in 200ms...');
		setTimeout(initializeDisplayLimit, 200);
		return;
	}
	
	// Get current limit from URL parameter
	var urlParams = new URLSearchParams(window.location.search);
	var urlLimit = urlParams.get('limit');
	console.log('URL limit parameter:', urlLimit);
	
	// Get saved preference from localStorage only if no URL parameter
	var savedLimit = localStorage.getItem('cars_display_limit');
	console.log('Saved limit from localStorage:', savedLimit);
	
	// Determine which limit to use - URL parameter takes priority, otherwise default to 25
	var limitToUse;
	if (urlLimit && ['25', '100', 'all'].includes(urlLimit)) {
		limitToUse = urlLimit;
	} else {
		// Always default to 25 when no valid URL parameter is present
		limitToUse = '25';
	}
	
	$('#cars-display-limit').val(limitToUse);
	localStorage.setItem('cars_display_limit', limitToUse);
	console.log('Set dropdown to:', limitToUse);
}

function handleDisplayLimitChange(newLimit) {
	console.log('Handling limit change to:', newLimit);
	
	// Validate the limit
	if (!['25', '100', 'all'].includes(newLimit)) {
		console.error('Invalid limit value:', newLimit);
		return;
	}
	
	// Save preference to localStorage
	localStorage.setItem('cars_display_limit', newLimit);
	console.log('Saved to localStorage:', newLimit);
	
	// Show loading indicator
	$('#cars-loading').show();
	
	// Get current URL and add/update limit parameter
	var currentUrl = new URL(window.location.href);
	currentUrl.searchParams.set('limit', newLimit);
	
	console.log('Redirecting to:', currentUrl.toString());
	
	// Reload page with new limit parameter
	window.location.href = currentUrl.toString();
}

initializeDisplayLimit();