<?php defined( '_DOIT' ) or die( 'Restricted access' ); ?>

<style>	
	#information {display:flex; flex-flow:row wrap; justify-content:space-between;}
	
	#information > .menu {width:23%; height:210px; display:block; background-color:#fff; margin:0 0 25px 0; transition:0.3s; border-radius:12px; padding-top:25px;}
	#information > .menu:hover {}
	
	#information > .menu .img {width:100%; height:100px; position:relative;}
	
	#information > .menu .img .def,
	#information > .menu .img .act {
		width:100%;
		height:100%;
		background-size:contain;
		background-position:center;
		background-repeat:no-repeat;
		position:absolute;
		transition:0.3s;
	}
		
	#information > .menu .img .act,
	#information > .menu:hover .img .def {opacity:0;}
	#information > .menu .img .def,
	#information > .menu:hover .img .act {opacity:1;}
		
	#information > .menu .txt {width:100%; line-height:60px; text-align:center; font-size:1rem; text-transform:uppercase; color:#292929; transition:0.3s;}
	#information > .menu:hover .txt {}
	
	#information {width:100%;}
	
	#information > .m_img {height:15rem;}
	
	#information > .ttl {width:100%; font-size:1.5rem; text-transform:uppercase; text-align:left; margin:2rem 0 0 0;}
	
	#information .txt {font-family:"def_l"; font-size:1.4rem;}
	
	#information > .txt,
	#information > .txt * {padding:1rem 2rem;}
	
	#information > .txt > p {border-left:2px solid #d52929; display:inline-block; margin:.5rem auto;}
	
	#information > .txt ul {padding:0 0 0 2rem; margin-bottom:2rem;}
	#information > .txt li {border-left:2px solid #292929; margin:.5rem 0; list-style:none; padding:.5rem 1rem;}
	
	#information > .txt > p > strong,
	#information > .txt > p > b,
	#information > .txt li > b {}
	
	#information > .txt b {font-family:"def"; font-weight:normal; font-size:1.2rem; padding:0;}
	#information > .txt > b {margin-top:2rem; display:inline-block; font-size:1.3rem;}
	
	#information > .txt a {padding:0;}
</style>

<div id="information">
	
	<?php 
	echo '
	<img class="m_img" src="/media/images/site/'.$t_mp[2].'.png" />
		
	<h1 class="ttl">'.$lang_xtra_menu[ $t_mp[2] ].'</h1>
	<div class="txt">'.$lang_xtra_page[$t_mp[2]].'</div>
	';
	?>
	
</div>
