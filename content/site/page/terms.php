<?php defined( '_DOIT' ) or die( 'Restricted access' ); ?>

<div id="xtra_page">
	<style>
		#xtra_page .top {width:100%; height:200px; overflow:hidden; position:relative; text-align:center; text-transform:uppercase;}
		#xtra_page .top div {color:#5c5a5a; font-weight:bold !important; font-size:22px; line-height:12px;}
		#xtra_page h3 {font-weight:bold !important;}
		#xtra_page p {border-left:2px solid #d52929; padding:0 10px; margin-left:10px;}
		#xtra_page li {border-left:2px solid #555; padding:0 10px; list-style:none; margin:5px 0;}
		#xtra_page * {line-height: 30px;}
	</style>
	
	<div class="top">
		<img src="/media/images/site/<?php e($t_mp[2]); ?>.png" height="80%" />
		<div><?php echo $lang_xtra_menu[ $t_mp[2] ] ?></div>
	</div>
	
	<div>
		<?php echo $lang_xtra_page[$t_mp[2]]; ?>
	</div>
</div>
