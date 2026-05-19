<?php defined( '_DOIT' ) or die( 'Restricted access' );

$slide_arr = [
	//[ 'img'=>'IMAGE NAME', 'txt'=>'MAIN TEXT', 'css'=>'STYLE CSS', 'xtr'=>'EXTRA TEXT', 'url'=>'LINK TO PAGE' ],
	//[ 'img'=>'', 'txt'=>'', 'css'=>'', 'xtr'=>'', 'url'=>'' ],
	//[ 'img'=>'x4ksyad6tm' ],
	'bs'=>[ //1 группа баннеров. Здесь будут выдаваться баннеры последовательно и первыми
		[ 'img'=>'d37vx9879f' ],
		[ 'img'=>'mma5knn9nz' ],
	],
	'rnd'=>[ //2 группа баннеров. Здесь будут выдаваться баннеры наугад и после 1 группы
		[ 'img'=>'a6fwm1hruk', 'url'=>'/'.$_COOKIE['lang'].'/cars/nissan-qashqai-qashqai-2' ],
		[ 'img'=>'gzdy7witv2', 'url'=>'/'.$_COOKIE['lang'].'/cars?tg=smpl&v=gD2ksAsmc5L' ],
		[ 'img'=>'77hveyio33' ],
		[ 'img'=>'0uuist3lhq' ],
		[ 'img'=>'6xt13vv4lk' ],
		[ 'img'=>'nu16zhtxrw' ],
		[ 'img'=>'gsw33hx8wy' ],
		[ 'img'=>'xr0bfl8554' ],
		[ 'img'=>'flhxp91tn0' ],
		[ 'img'=>'0zpetzxukq' ],
		[ 'img'=>'70cvo0eubi', 'txt'=>'NOTORIUM trademark awards 2022' ],
		[ 'img'=>'5s8o5ds6bm', 'txt'=>'TRANSPORT AUTORIZAT DE AUTOMOBILE', 'xtr'=>'PESTE 5000 AUTOMOBILE LIVRATE' ],
		[ 'img'=>'391nhbzvou', 'txt'=>$lang_slider_text['kadjar']['main_txt'], 'xtr'=>$lang_slider_text['kadjar']['xtra_txt'], 'url'=>'/'.$_COOKIE['lang'].'/cars/renault-kadjar' ],
		[ 'img'=>'cnn1uc6hu0', 'txt'=>$lang_slider_text['comerciale']['main_txt'], 'xtr'=>$lang_slider_text['comerciale']['xtra_txt'] ],
		[ 'img'=>'nph6bq92k2', 'txt'=>$lang_slider_text['platforma']['main_txt'], 'url'=>'/'.$_COOKIE['lang'].'/services/transportation' ],
		[ 'img'=>'3cizv9l0zw' ],
		[ 'img'=>'i0nub22a5h', 'txt'=>$lang_slider_text['order']['main_txt'], 'url'=>'/'.$_COOKIE['lang'].'/order' ],
		[ 'img'=>'50s9cyld8x', 'txt'=>$lang_slider_text['message']['main_txt'], 'xtr'=>$lang_slider_text['message']['xtra_txt'] ]
	]
	
];
?>

<!-- <div id="slider" data-in_t="<?php echo (isset($_COOKIE['usr_id'])&&$_COOKIE['usr_id']=='5'?2:8); ?>" data-dl_t="1">
	<div class="pattern ghost"></div>
		
		<?php 
		// shuffle($slide_arr['rnd']);
		
		// $i=1;
		// foreach ($slide_arr as $gr){
		// 	foreach ($gr as $v){
		// 		if ( isset($v['url']) && $v['url']!='' ){ $z_tag = 'a'; $z_link = ' href="'.$v['url'].'" '; } else { $z_tag = 'span'; $z_link = ''; }
				
		// 		echo '
		// 		<'.$z_tag.' class="slide'.( $i==1?' active':'' ).'" '.$z_link.' numb="'.$i.'" style="background-image:url(/media/images/site/slider/'.$v['img'].$img_frmt.')">';
		// 			if ( isset($v['css']) && $v['css']!='' ){echo '<style>'.$v['css'].'</style>';}
		// 			echo '
		// 			<p class="main_txt">'.( isset($v['txt']) && $v['txt']!='' ? $v['txt'] : '' ).'</p>
		// 			<p class="xtra_txt">'.( isset($v['xtr']) && $v['xtr']!='' ? $v['xtr'] : '' ).'</p>
		// 		</'.$z_tag.'>
		// 		';
		// 		$i++;
		// 	}
		// }
		?>
	
	<div class="text">
		<span class="main_txt"></span>
		<span class="xtra_txt"></span>
	</div>
	
	<div class="timebar">
		<div class="fill min"></div>
	</div> -->
	
	<?php /*
	<video id="vid" preload="auto" loop="loop" muted="muted" autoplay="autoplay" poster="" playsinline="true" type="video/mp4">
		<source src="/<?php e(_VIDEO)?>/S41fLoESs5e2Fb.mp4"></source>
	</video>
	*/ ?>
	
</div>