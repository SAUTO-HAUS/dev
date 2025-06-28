var reqType = 'adm';
var reqPage = 'mail';

$(document).ready(function(){
	
	//--------------------------------------------------------------------------------------------------------------SEEN
	$(document).on('click', '#mail_content > .mail:not(.seen) > .title > div', function(){
			var data = {};
			data['tp'] = reqType;
			data['pg'] = reqPage;
			data['fn'] = 'seen';
			data['id'] = $(this).parent().parent().attr('data-id');
			ajaxIt(data);
			$(this).parent().parent().addClass('seen');
	})
	
	//--------------------------------------------------------------------------------------------------------------DELETE
	$(document).on('click', '#action_menu > div.delete', function(){
		var confirmation = confirm( $(this).attr('title')+'?' );
		if (confirmation){
			var data = {};
			data['tp'] = reqType;
			data['pg'] = reqPage;
			data['fn'] = 'delete';
			data['id'] = new Array();
			
			$('#mail_content > .mail.checked').each(function(){ data['id'].push( $(this).attr('data-id') ) }).css({'transform':'scale(0)', 'opacity':'0'})//.delay(500).queue(function(){ $(this).remove(); location.reload(); });
			
			ajaxIt(data);
	
		}
	})
	
	$(document).on('click', '#action_menu > div.folder', function(){
		overlay('open', '#hidden > .folder', 'self');
	})
	
	$(document).on('click', '#overlay > .content > .folder > .button', function(){
		var confirmation = confirm( 'Переместить в папку '+$(this).text()+'?' );
		if (confirmation){
			var data = {};
			data['tp'] = reqType;
			data['pg'] = reqPage;
			data['fn'] = 'folder';
			data['folder_name'] = $(this).attr('data-name');
			data['id'] = new Array();
			
			$('#mail_content > .mail.checked').each(function(){ data['id'].push( $(this).attr('data-id') ) });
			
			ajaxIt(data);
		}
	})
	
	$(document).on('click', '#action_menu > div.archive, #action_menu > div.favorites', function(){
		var confirmation = confirm( 'Переместить в '+$(this).text()+'?' );
		if (confirmation){
			var data = {};
			data['tp'] = reqType;
			data['pg'] = reqPage;
			data['fn'] = 'state';
			data['state_name'] = $(this).attr('data-name');
			data['id'] = new Array();
			
			$('#mail_content > .mail.checked').each(function(){ data['id'].push( $(this).attr('data-id') ) });
	
			ajaxIt(data);
		}
	})
	
})

function ajaxSuccess(dataX){
	var data = $.parseJSON(dataX);
	
	if(data.fn=='state'||data.fn=='folder'||data.fn=='delete'){
		location.reload();
	}
}