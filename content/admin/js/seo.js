var reqType = 'adm';
var reqPage = 'seo';

$(document).ready(function(){

	function hasValue(el){
		return $(el).filter(function() { return $(this).val(); }).length > 0;
	}
	
	$('#s > .ii .i > .c > label > textarea').on('input', function() {
		$(this).parent().parent().children('.exec.ghost').removeClass('ghost');
	});
	
	$('#s > .new > .c > .new_page').on('input', function() {
		if ( $('#s > .new > .c > .new_page').val() ){
			$(this).parent().children('.exec.ghost').removeClass('ghost');
		}else{
			$(this).parent().children('.exec:not(.ghost)').addClass('ghost');
		}
	});
	
	$(document).on('click', '#s > .ii > .i > .copy', function(){
		$(this).parent().children('.c').children('.lng_cnt').children('.collect').each(function(){
			$('#s > .new > .c > .lng_cnt > .collect[data-lang="'+$(this).data('lang')+'"][data-name="'+$(this).data('name')+'"]').val( $(this).val() );		
		})
		
		$(this).addClass('copied').delay(2000).queue(function(){
			$(this).removeClass('copied');
			$(this).dequeue();
		});
	})

	//--------------------------------------------------------------------------------------------------------------ADD
	$(document).on('click', '#s > .new .exec', function(){
		var confirmation = confirm( $(this).attr('title')+'?' );
		if (confirmation){
			var data = {};
			data['tp'] = reqType;
			data['pg'] = reqPage;
			data['fn'] = 'add_new';
			
			$(this).parent().children('.lng_cnt').children('.collect').each(function(){ data[$(this).attr('name')] = $(this).val(); })
			data['page'] = $('#s > .new > .c > .new_page').val();
			//var nP = $('#s > .new > .c > .new_page').val().split('?');
			//data['new_page'] = decodeURIComponent(nP[1]);
			
			ajaxIt(data);
		}
	})
	
	//--------------------------------------------------------------------------------------------------------------EDIT
	$(document).on('click', '#s > .ii .exec', function(){
		var confirmation = confirm( $(this).attr('title')+'?' );
		if (confirmation){
			var data = {};
			data['tp'] = reqType;
			data['pg'] = reqPage;
			data['fn'] = 'edit';
			
			$(this).parent().children('.lng_cnt').children('.collect').each(function(){ data[$(this).attr('name')] = $(this).val(); })
			data['type'] = $(this).data('tp');
			data['p1'] = $(this).data('p1');
			data['it_id'] = $(this).data('it_id');
			
			ajaxIt(data);
		}
	})

	//--------------------------------------------------------------------------------------------------------------DELETE
	$(document).on('click', '#s > .ii .del', function(){
		var confirmation = confirm( $(this).attr('title')+'?' );
		if (confirmation){
			var data = {};
			data['tp'] = reqType;
			data['pg'] = reqPage;
			data['fn'] = 'delete';
			
			data['type'] = $(this).data('tp');
			data['p1'] = $(this).data('p1');
			data['it_id'] = $(this).data('it_id');
			
			ajaxIt(data);
		}
	})

});

//______________________________________________________________________________________________________________END OF READY / AJAX_SUCCESS
function ajaxSuccess(data){
	var data = $.parseJSON(data);
	if( data!=null ){
		
		//-------------------------------------------------------------------------ADD_NEW
		if(data.fn=='add_new'){
			location.reload();
		}
		
		//-------------------------------------------------------------------------EDIT
		if(data.fn=='edit'){
			$('#item_'+data.it_id+' .exec:not(.ghost)').addClass('ghost');
			$('#item_'+data.it_id+' .chkr').prop('checked', false);
		}
		
		//-------------------------------------------------------------------------DELETE
		if(data.fn=='delete'){
			var zh = $('#item_'+data.it_id).height();			
			
			$('#item_'+data.it_id).css('height', zh+'px').delay(1).queue(function(){
				$(this).addClass('deleted');
				$(this).dequeue();
			});
		}
		
	}
}