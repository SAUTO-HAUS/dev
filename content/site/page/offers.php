<?php defined( '_DOIT' ) or die( 'Restricted access' );
	
echo '
<style>
	#offers {display:flex; flex-flow:row wrap; justify-content:space-between;}
	
	#offers .menu {width:23%; height:210px; display:block; background-color:#d2d2d2; margin:0 0 25px 0; transition:0.3s;}
	#offers .menu:hover {background-color:#e2e2e2;}
	
	#offers .menu .img {width:100%; height:150px; position:relative;}
	
	#offers .menu .img .def, #offers .menu .img .act {
		width:100%;
		height:100%;
		background-size:contain;
		background-position:center;
		background-repeat:no-repeat;
		position:absolute;
		transition:0.3s;
	}
		
	#offers .menu .img .act, #offers .menu:hover .img .def {opacity:0;}
	#offers .menu .img .def, #offers .menu:hover .img .act {opacity:1;}
		
	#offers .menu .text {width:100%; line-height:60px; text-align:center; font-size:18px; font-weight:bold; text-transform:uppercase; color:#fcf6f6; transition:0.3s;}
	#offers .menu:hover .text {color:#3a3a3a;}
	
	#offers .page {width:100%;}
	#offers .page .top {width:100%; height:200px; overflow:hidden; position:relative; text-align:center; text-transform:uppercase;}
	#offers .page .top div {color:#d52929; font-weight:bold !important; font-size:22px; line-height:12px;}
	#offers .page .title {font-weight:bold !important; font-size:20px; margin:20px 0 20px 30px;}
	#offers .page .text p {border-left:2px solid #d52929;}
	#offers .page .text li {border-left:2px solid #555; margin:5px 0; list-style:none;}
	#offers .page .text, #offers .page .text * {line-height:30px; font-size:18px; padding:0 30px;}
	#offers .page .text li {padding:0 20px;}
	#offers .page .text h2, #offers .page .text b {padding:0;}
	
	#offers .page .top .bg {/*white-space:nowrap;*/}
	#offers .page .top .bg * {opacity:0.2; position:absolute; font-family:sans-serif; font-weight:bold; color:#fff; filter:blur(2px);}
	
	#offers .page .top .bg .img1  {top:0; animation:img1_move 60s infinite linear;}
	#offers .page .top .bg .img2  {top:0; animation:img2_move 60s infinite linear;}
	
	#offers .page .top .bg .txt1_1, #offers .page .top .bg .txt1_2 {width:100%; top:20%; left:60%; font-size:60px; text-align:left;}
		#offers .page .top .bg .txt1_1 {animation:txt1_1_move 20s infinite linear;}
		#offers .page .top .bg .txt1_2 {animation:txt1_2_move 20s infinite linear;}
		
	#offers .page .top .bg .txt2_1, #offers .page .top .bg .txt2_2 {width:100%; top:60%; right:80%; font-size:40px; text-align:right;}
		#offers .page .top .bg .txt2_1 {animation:txt2_1_move 10s infinite linear;}
		#offers .page .top .bg .txt2_2 {animation:txt2_2_move 10s infinite linear;}
	
	@keyframes img1_move {
		0% {left:20%;}
		100% {left:120%;}
	}
	@keyframes img2_move {
		0% {left:-80%;}
		100% {left:20%;}
	}
	
	@keyframes txt1_1_move {
		0% {left:0%;}
		100% {left:-100%;}
	}
	@keyframes txt1_2_move {
		0% {left:100%;}
		100% {left:0%;}
	}
	
	@keyframes txt2_1_move {
		0% {right:50%;}
		100% {right:-50%;}
	}
	@keyframes txt2_2_move {
		0% {right:150%;}
		100% {right:50%;}
	}
	
</style>
';

echo '<div id="offers">';

if ( !in_array($t_mp[3], $offers_arr) ){

	foreach( $offers_arr as $key ){
		echo '
		<a class="menu" href="/'.$_COOKIE['lang'].'/offers/'.$key.'">
			<div class="img">
				<div class="def" style="background-image:url(/media/images/site/offers/'.$key.'.png);"></div>
				<div class="act" style="background-image:url(/media/images/site/offers/'.$key.'_a.png);"></div>
			</div>
			<div class="text">'.$lang_offers[$key]['name'].'</div>
		</a>
		';
	}

}else{
	
	echo '
	<div class="page">
		<div class="top">
			<img src="/media/images/site/offers/'.$t_mp[3].'_a.png" height="80%" />
			<div>'.$lang_offers[ $t_mp[3] ]['name'].'</div>';
			/*<div class="bg">
				<img class="img1" src="/media/images/site/offers/'.$t_mp[3].'.png" height="100%" />
				<img class="img2" src="/media/images/site/offers/'.$t_mp[3].'.png" height="100%" />
				<div class="txt1_1">'.$lang_offers[ $t_mp[3] ]['name'].'</div>
				<div class="txt1_2">'.$lang_offers[ $t_mp[3] ]['name'].'</div>
				<div class="txt2_1">'.$lang_offers[ $t_mp[3] ]['name'].'</div>
				<div class="txt2_2">'.$lang_offers[ $t_mp[3] ]['name'].'</div>
			</div>*/
		echo '
		</div>
		<h3 class="title">'.$lang_offers[ $t_mp[3] ]['title'].'</h3>
		<div class="text">'.$lang_offers[ $t_mp[3] ]['text'].'</div>
	';
	
	if ($t_mp[3]=='order'){
		
		$pdo = $db->prepare('SELECT * FROM '.$prefx.'_cars ORDER BY `brand` ASC');
		$pdo->execute();
		foreach ($pdo as $row){	$c_brand[ $row['brand'] ] = $row['brand_name']; }
		
		echo '<style>
			#offers .page .func {display:flex; flex-flow:row wrap; justify-content:space-between; position:relative;}
			
			#offers .page .func .ready {width:100%; height:100%; transition:0.3s; position:absolute; text-align:center; display:none; opacity:0; background-color:#cfa;}
			#offers .page .func .ready.active {display:block; opacity:0.7;}
			
			#offers .page .func .ready b {position:absolute; margin:0 auto; bottom:5%; left:0; right:0;}
			
			#offers .page .func *:not(.ready) {margin:10px; transition:0.3s;}
			
			#offers .page .func *:hover:not(.ready) {}
			
			#offers .page .func select, #offers .page .func input {
				height:50px;
				text-align:center;
				border:1px solid #c7c7c7;
				box-sizing:border-box;
				background-color:#f8f8f8;
				color:#555;
			}
			#offers .page .func select {width:30%;}
			#offers .page .func input.item {width:47%;}
			#offers .page .func input.person {width:30%;}
			
			#offers .page .func textarea {
				width:100%;
				height:150px;
				border:1px solid #c7c7c7;
				background-color:#f8f8f8;
				padding:20px;
				box-sizing:border-box;
				color:#555;
			}
			
			#offers .page .func .order_submit {width:100%; background-color:#5f5f5f; color:#fff; transition:0.3s; cursor:pointer;}
			#offers .page .func .order_submit:hover {background-color:#d52929;}
			
			#offers .page .func *.not_ready {border:1px solid #f00 !important;}
			
			#offers .page .func .line {background-color:rgba(0,0,0,0.1); width:100%; height:1px;}
		</style>';
		
		echo '<div class="func">';
			
			echo '<div class="ready"><b>'.$gSdk3pF_sent.'</b></div>';
			echo '<div class="line"></div>';
			echo '<select name="brand" class="item brand need" tabindex="1"> <option value="">'.mb_strtoupper($lang_brand, "UTF-8").'</option>';
				foreach ($c_brand as $key => $value){ echo '<option value="'.$key.'">'.$value.'</option>'; }
			echo '</select>';
			
			echo '<select name="model" class="item model need" def_text="'.mb_strtoupper($lang_model, "UTF-8").'" tabindex="2"><option value="">'.mb_strtoupper($lang_model, "UTF-8").'</option></select>';
			
			echo '<select name="fuel" class="item fuel need" tabindex="8"> <option value="">'.mb_strtoupper($lang_fuel, "UTF-8").'</option>';
				foreach ($info_fuel as $key => $value){ echo '<option value="'.$key.'">'.$value.'</option>'; }
			echo '</select>';
			
			echo '<input name="year" class="item numInput no_need" size="16" tabindex="3" placeholder="'.mb_strtoupper($lang_year, "UTF-8").'" type="number" title="'.mb_strtoupper($lang_year, "UTF-8").'">';
			
			echo '<input name="engine" class="item numInput no_need" size="16" tabindex="7" placeholder="'.mb_strtoupper($lang_engine, "UTF-8").' cm³" type="number" title="'.mb_strtoupper($lang_engine, "UTF-8").'">';
			
			echo '<textarea tabindex="14" name="xtra_info" class="no_need" cols="84" rows="5" spellcheck="false" placeholder="'.$lang_offers_order_textarea.'"></textarea>';
			
			echo '<input name="name" class="person need" size="16" tabindex="7" placeholder="'.mb_strtoupper($lang_your_name, "UTF-8").'" type="text" title="'.mb_strtoupper($lang_your_name, "UTF-8").'">';
			
			echo '<input name="phone" class="person numInput no_need" size="16" tabindex="7" placeholder="'.mb_strtoupper($lang_your_phone, "UTF-8").'" type="text" title="'.mb_strtoupper($lang_your_phone, "UTF-8").'">';
			
			echo '<input name="email" class="person need" size="16" tabindex="7" placeholder="EMAIL" type="text" title="EMAIL">';
			
			echo '<input type="button" value="'.$lang_send.'" class="order_submit">';
			
			echo '<input type="text" name="page" class="need" value="'.$_SERVER['REQUEST_URI'].'" style="display:none; />';
			
			echo '<div class="line"></div>';
		echo '</div>';
	}
	
	echo '
	</div>
	';
	
}

echo '</div>';

?>