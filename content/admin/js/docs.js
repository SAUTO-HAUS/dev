$(document).ready(function(){
	
})

//______________________________________________________________________________________________________________END OF READY / AJAX_SUCCESS
function ajaxSuccess(data){
	var data = $.parseJSON(data);
	if( data!=null /*$.isArray(data) || data.length*/ ){
		//-------------------------------------------------------------------------DOWNLOAD_ZIP
		if(data.fn=='edit_sbmt'){ $("#overlay > .close").trigger("click"); }
	}
}