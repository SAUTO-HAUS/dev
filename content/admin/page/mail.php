<?php
	
echo '
<div id="mail_content">
';

echo '
<style>
	.seen{color:#aeaeae !important;}
	
	#mail_content > .date {width:100%; height:50px; line-height:50px; text-align:center; color:#bf4040; clear:both; border-bottom:1px solid #444; margin:0 0 10px 0;}
	
	#mail_content > .mail {float:left; width:100%; min-height:30px; margin:0 0 2px 0; background-color:#fff; border-top:1px solid transparent; border-bottom:1px solid transparent; color:#444; transition:0.3s;}
	#mail_content > .mail.active {margin:20px 0; padding:20px 0;}
	#mail_content > .mail.checked {background-color:#d5f390; color:#555 !important;}
	#mail_content > .mail:hover {border-color:#bf4040 !important; color:#bf4040 !important;}
	#mail_content > .mail > .title {width:inherit; line-height:20px; box-sizing:border-box; cursor:pointer; float:left; padding:10px 0;}
	#mail_content > .mail > .title > input {float:left;}
	#mail_content > .mail > .title > div {overflow:hidden; float:left; text-align:center; font-size:14px; border-right:1px solid #e7e7e7; box-sizing:border-box;}
	#mail_content > .mail > .title > div > a {width:100%; height:100%; float:left; color:inherit;}
	#mail_content > .mail > .message {width:inherit; max-height:0; overflow:hidden; transition:0.3s; float:left; font-size:14px; padding:0 20px; box-sizing:border-box;}
	#mail_content > .mail.active > .message {max-height:100vh; overflow-y:auto;}
	
	#action_menu > div.count {min-width:25%; text-align:center; border-right:1px solid #000;}
	#action_menu > div.folder {background:transparent url("/media/images/site/folder.svg") no-repeat left center /auto 40%;}
	#action_menu > div.favorites {background:transparent url("/media/images/site/favorite.svg") no-repeat left center /auto 40%;}
	#action_menu > div.archive {background:transparent url("/media/images/site/archive.svg") no-repeat left center /auto 40%;}
	#action_menu > div.delete {background:transparent url("/media/images/site/delete.svg") no-repeat left center /auto 40%;}
	
	#mail_content > .mail > .title > .button {transition:0.2s; height:20px;}
	#mail_content > .mail > .title > .button:hover {background-position:50% 100% !important;}
	#mail_content > .mail > .title > .button.reply {background:transparent url("/media/images/site/reply.svg") no-repeat 50% 0 /auto 200%;}
	#mail_content > .mail > .title > .button.delete {background:transparent url("/media/images/site/delete2.svg") no-repeat 50% 0 /auto 200%;}
	
	#mail_content > .mail > .title > .favorites {background-color:#fdb721; color:#fff;}
	
	#overlay > .content > .folder > .button {cursor:pointer; width:50vw; line-height:45px; font-size:15px; background-color:#777; color:#fff; margin:10px auto; transition:0.5s;}
	#overlay > .content > .folder > .button:hover {background-color:#e12229;}
</style>
';
echo "
<script>
$(document).ready(function(){
	
	$('#mail_content > .mail > .title > div').on('click', function(){
		var mail = $(this).parent().parent();
		var isMobile = window.matchMedia('(max-width:767px),(orientation:portrait),(max-height:500px) and (orientation:landscape)').matches;
		if (isMobile && mail.hasClass('active')) {
			mail.removeClass('active');
		} else {
			$('#mail_content > .mail.active').removeClass('active');
			mail.addClass('active');
		}
	})
	
	$('#mail_content > .mail > .title > input[type=\"checkbox\"]').change(function(){
		if ($(this).is(':checked')){
			$(this).parent().parent().addClass('checked');
		}else {
			$(this).parent().parent().removeClass('checked');
		}
		
		var countz = $('#mail_content > .mail > .title > input[type=\"checkbox\"]').filter(':checked').length;
		if (countz>0){ 
			$('#action_menu').addClass('active')
			$('#action_menu > .count').html(countz).attr('data-count', countz);
			$('#main_admin').addClass('slim');
		} else { 
			$('#action_menu').removeClass('active');
			$('#action_menu > .count').html(countz).attr('data-count', '0');
			$('#main_admin').removeClass('slim');
		}
	})
	
	$('#action_menu').html(' \
		<div class=\"count\" data-count=\"0\">0</div> \
		<div class=\"folder\" title=\"To Folder\">В папку</div> \
		<div class=\"favorites\" title=\"Favorites\" data-name=\"favorites\">Избранное</div> \
		<div class=\"archive\" title=\"Archive\" data-name=\"archive\">Архив</div> \
		<div class=\"delete\" title=\"Delete\">Удалить</div> \
	');
	 
	$('#action_menu > .count').hover(
		function(){
			var countz = $(this).attr('data-count');
			$(this).html('Сбросить выбор');
		},
		function(){
			var countz = $(this).attr('data-count');
			$(this).html(countz);
		}
	)
	
	$('#action_menu > .count').on('click', function(){
		$('#mail_content > .mail.checked > .title > input[type=\"checkbox\"]').prop('checked', false);
		$('#mail_content > .mail.checked').removeClass('checked');
		
		$(this).html('0').attr('data-count','0');
		$(this).parent().removeClass('active');
	})
	
})
</script>
";


echo '
<div id="hidden">
	<div class="folder">';
		$pdo = $db->prepare('SELECT * FROM '.$prefx.'_mail_folders WHERE `folder`=1');
		$pdo->execute();
		foreach($pdo as $row){ echo '<div class="button" data-name="'.$row['name'].'">'.$adm_lang[ $row['name'] ].'</div>'; }
	echo '
	</div>		
</div>
';


if ( $t_mp[3]=='mail' && ( isset($t_mp[4]) && $t_mp[4]!='' ) ){
	if ($t_mp[4]=='favorites'){
		$pdo = $db->prepare('SELECT * FROM '.$prefx.'_mail WHERE `favorites`=1 ORDER BY `date` DESC ');
		$pdo->execute();
	}elseif ($t_mp[4]=='archive'){
		$pdo = $db->prepare('SELECT * FROM '.$prefx.'_mail WHERE `archive`=1 ORDER BY `date` DESC ');
		$pdo->execute();
	}elseif ($t_mp[4]=='message' || $t_mp[4]=='order'){
		$pdo = $db->prepare('SELECT * FROM '.$prefx.'_mail WHERE `folder`=:folder AND `archive`=0 ORDER BY `date` DESC ');
		$pdo->execute(array('folder'=>$t_mp[4]));
	}
}else{
	$pdo = $db->prepare('SELECT * FROM '.$prefx.'_mail WHERE `archive`=0 ORDER BY `date` DESC ');
	$pdo->execute();
}

$z_day = 0;
$i=0;
foreach ($pdo as $row){
	$i++;
	
	$seen = $row['seen']==0 ? '' : 'seen';
	$page = !$row['page'] ? '' : '<br/><br/><br/><a title="'.$adm_lang['mail_from_page'].'" href="//'.$_SERVER['HTTP_HOST'].$row['page'].'" target="_blank">'.$row['page'].'</a>';
		
	if ( $z_day != date("d" , $row['date']) || $z_day == 0 ){
		$z_day = date( "d" , $row['date'] );
		
		echo '<div class="date">'.$z_day.' '.date("F" , $row['date']).', '.date("Y" , $row['date']).'</div>';
		
	}
	
	$favorites = ( $row['favorites'] == 1 ) ? 'favorites' : '' ;
	
	echo '
	<div class="mail '.$seen.'" data-id="'.$row['id'].'">
		<div class="title">
			<input type="checkbox" name="check" value="1">
			<div class="folder '.$favorites.'" style="width:10%;" title="Folder">'.$adm_lang[ $row['folder'] ].'</div>
			<div class="date" style="width:10%; font-size:8px;" title="Date">'.date("d.m.Y, H:i:s", $row['date']).'</div>
			<div class="email" style="width:30%;" title="Email">'.$row['email'].'</div>
			<div class="name" style="width:20%;" title="Name">'.$row['name'].'</div>
			<div class="phone" style="width:15%;" title="Phone">'.$row['phone'].'</div>
			<div class="reply button" style="width:10%;" title="Reply">
				<a href="https://mail.google.com/mail/u/0/?view=cm&fs=1&tf=1&source=mailto&to='.$row['email'].'" target="_blank"></a>
			</div>
		</div>
		<div class="message">
			'.nl2br($row['message']).'
			'.$page.'
		</div>
	</div>
	';
}

if ($i==0){echo $adm_lang['empty'];}
	
echo '
</div>
';
	
?>