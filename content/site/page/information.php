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

	#information > .m_img {height:15rem; max-width:100%;}

	#information > .ttl {width:100%; font-size:1.5rem; text-transform:uppercase; text-align:left; margin:2rem 0 0 0;}

	/* #information is a flex row, so .txt is a flex item and inherits
	   min-width:auto — it refuses to shrink below the widest thing inside it.
	   The cookie tables use white-space:nowrap, so without min-width:0 that
	   intrinsic width pushes the whole page sideways on a phone, no matter what
	   overflow the table itself declares. */
	#information > .txt {flex:1 1 100%; min-width:0; max-width:100%; box-sizing:border-box;}
	#information .txt {font-family:"def_l"; font-size:1.4rem;}
	/* Long unbroken strings — cookie names, e-mails — must wrap, not overflow. */
	#information > .txt p, #information > .txt li, #information > .txt a {overflow-wrap:anywhere;}
	
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

	/* Privacy/cookie notice: H2 section headings and the cookie inventory table.
	   The table scrolls inside its own box so a phone never scrolls sideways. */
	#information > .txt h2 {font-size:1.25rem; margin:2rem 0 .5rem; padding:0 2rem; color:#d52929;}
	#information > .txt li > ul {margin:.3rem 0 .8rem 1rem; padding-left:1rem;}
	#information > .txt li > ul > li {border-left:1px dotted #aaa; font-size:1.2rem; color:#555;}
	#information > .txt .cookie-table {display:block; overflow-x:auto; width:auto; max-width:100%; box-sizing:border-box; margin:.5rem 2rem 1.5rem; border-collapse:collapse; font-size:1.1rem;}
	#information > .txt .cookie-table th,
	#information > .txt .cookie-table td {border:1px solid #ddd; padding:.5rem .8rem; text-align:left; white-space:nowrap;}
	#information > .txt .cookie-table th {background:#f5f5f5;}

	/* A legal notice has to read as a document. Two template rules fight that,
	   and are undone here — scoped with .legal so the marketing pages keep
	   their look: `> p {display:inline-block}` made consecutive short
	   paragraphs sit side by side on one line, and the blanket
	   `* {padding:1rem 2rem}` indented inline elements such as <time> and <i>. */
	#information > .txt.legal > p {display:block; width:auto; margin:.6rem 0;}
	#information > .txt.legal i,
	#information > .txt.legal em,
	#information > .txt.legal time,
	#information > .txt.legal span,
	#information > .txt.legal strong {padding:0;}
	#information > .txt.legal .cookie-settings-btn {display:inline-block; margin:.5rem 2rem; padding:.55rem 1.1rem;}

	@media (max-width:999px), (orientation: portrait) {
		#information > .m_img {height:9rem;}
		#information > .ttl {font-size:1.25rem; margin:1.2rem 0 0; padding:0 1rem;}
		#information .txt {font-size:1.05rem;}

		#information > .txt,
		#information > .txt * {padding:0;}

		#information > .txt > p {padding:.35rem 1rem;}
		#information > .txt h1 {font-size:1.35rem; line-height:1.35; padding:0 1rem; margin:.8rem 0 .6rem;}
		#information > .txt h2 {font-size:1.12rem; padding:0 1rem; margin:1.6rem 0 .4rem;}
		#information > .txt b {font-size:1.05rem;}
		#information > .txt > b {font-size:1.1rem; padding:0 1rem;}

		#information > .txt ul {padding:0 1rem 0 1.8rem; margin-bottom:1rem;}
		#information > .txt li {padding:.3rem .7rem; font-size:1rem;}
		#information > .txt li > ul {margin:.25rem 0 .6rem .4rem; padding-left:.7rem;}
		#information > .txt li > ul > li {font-size:.95rem;}

		/* Table keeps its own sideways scroll; only the frame gets tighter. */
		#information > .txt .cookie-table {margin:.5rem 1rem 1.2rem; font-size:.92rem;}
		#information > .txt .cookie-table th,
		#information > .txt .cookie-table td {padding:.4rem .55rem;}

		#information > .txt.legal .cookie-settings-btn {margin:.6rem 1rem;}
	}

	/* Small phones: the tables are the only thing that still needs shrinking. */
	@media (max-width:480px) {
		#information .txt {font-size:1rem;}
		#information > .txt h1 {font-size:1.2rem;}
		#information > .txt h2 {font-size:1.05rem;}
		#information > .txt .cookie-table {margin:.5rem .6rem 1rem; font-size:.85rem;}
		#information > .txt .cookie-table th,
		#information > .txt .cookie-table td {padding:.35rem .45rem;}
		#information > .txt > p {padding:.3rem .6rem;}
		#information > .txt h1, #information > .txt h2 {padding:0 .6rem;}
		#information > .txt ul {padding:0 .6rem 0 1.5rem;}
		#information > .txt.legal .cookie-settings-btn {margin:.6rem .6rem; width:calc(100% - 1.2rem); text-align:center;}
	}
</style>

<div id="information">
	
	<?php
	$info_key  = $t_mp[2];
	$info_body = $lang_xtra_page[$info_key];

	// /cookies has no banner image of its own — reuse the privacy one.
	$info_img = ($info_key === 'cookies') ? 'privacy' : $info_key;

	// Pages whose text already opens with its own <h1> (privacy, cookies, credit)
	// must not get a second one from the template: two H1s on one page is exactly
	// what a structure audit flags.
	$info_has_h1 = stripos($info_body, '<h1') !== false;

	// The legal notices need document typography, not the marketing-page layout.
	$info_legal = in_array($info_key, ['privacy', 'cookies'], true) ? ' legal' : '';

	echo '
	<img class="m_img" src="/media/images/site/'.$info_img.'.png" alt="'.htmlspecialchars($lang_xtra_menu[$info_key], ENT_QUOTES).'" />
	';
	echo $info_has_h1
		? '<div class="ttl none"></div>'
		: '<h1 class="ttl">'.$lang_xtra_menu[$info_key].'</h1>';
	echo '
	<div class="txt'.$info_legal.'">'.$info_body.'</div>
	';
	?>
	
</div>
