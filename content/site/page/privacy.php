<?php defined( '_DOIT' ) or die( 'Restricted access' ); ?>

<div id="xtra_page">
	<style>
		#xtra_page .top {width:100%; height:200px; overflow:hidden; position:relative; text-align:center; text-transform:uppercase;}
		#xtra_page .top div {color:#5c5a5a; font-weight:bold !important; font-size:22px; line-height:12px;}
		#xtra_page p {border-left:2px solid #d52929; padding:0 10px; margin-left:10px;}
		#xtra_page li {border-left:2px solid #555; padding:0 10px; list-style:none; margin:5px 0;}
		#xtra_page * {line-height: 30px;}
		/* The privacy notice nests sub-lists (data / purpose / legal basis) under
		   each processing activity; without this they inherit the same left bar
		   as the parent and the hierarchy disappears. */
		#xtra_page li > ul {margin:5px 0 10px 10px;}
		#xtra_page li > ul > li {border-left:1px dotted #aaa; color:#555;}
		#xtra_page h1 {font-size:24px; line-height:1.35; margin:10px 0 15px;}
		#xtra_page h2 {font-size:18px; line-height:1.4; margin:25px 0 8px; color:#d52929;}
		/* Cookie inventory. Wrapped in its own scroll container so a narrow
		   phone scrolls the table instead of the whole page sideways. */
		#xtra_page .cookie-table {display:block; overflow-x:auto; width:100%; border-collapse:collapse; margin:10px 0 15px; font-size:14px;}
		#xtra_page .cookie-table th, #xtra_page .cookie-table td {border:1px solid #ddd; padding:6px 10px; text-align:left; line-height:1.5; white-space:nowrap;}
		#xtra_page .cookie-table th {background:#f5f5f5; font-weight:bold;}
	</style>
	
	<div class="top">
		<img src="/media/images/site/<?php e($t_mp[2]); ?>.png" height="80%" />
		<div><?php echo $lang_xtra_menu[ $t_mp[2] ] ?></div>
	</div>
	
	<div>
		<?php echo $lang_xtra_page[$t_mp[2]]; ?>
	</div>
</div>
