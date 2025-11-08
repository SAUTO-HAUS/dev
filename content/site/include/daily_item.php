<?php defined( '_DOIT' ) or die( 'Restricted access' ); 

$pdo = $db->prepare('SELECT * FROM '.$prefx.'_catalog WHERE `daily_item`="1" AND `visible`="1" AND `active`="1"');
$pdo->execute();

//$row_cnt = $pdo->fetchColumn();
//if ($row_cnt!=''){echo '<style>#window_2{display:none;}</style>';}

foreach ($pdo as $row){
	echo '
	<style>
		#window_2{display:block;}
		.car_box.daily_item {padding:0; margin-bottom:0;}
		.car_box.daily_item .car_info{padding:0; font-size:10px;}
		.car_box.daily_item .car_price {font-size: 16px;}
		.car_box.daily_item .car_price .car_price_left, .car_box.daily_item .car_price .car_price_right{float:none;}
	</style>
	';
	
    $c_id = $row['id'];
    $c_brand = $row['brand'];
    $c_model = $row['model'];
	$c_brand_name = $row['brand_name'];
    $c_model_name = $row['model_name'];
    $c_year = $row['year'];
    $c_engine = $row['engine'];
    $c_fuel = $row['fuel'];
    $c_transmission = $row['transmission'];
    $c_prima_rata = $row['prima_rata'];
    $c_price = $row['price'];
    $c_currency = $row['currency'];
    $c_daily_item = $row['daily_item'];
    $c_old_price = $row['old_price'];
	$c_new_item = $row['new'];
	$c_hp = $row['hp'];
    $c_path = $row['photo_path'];
    $c_catalog_type = isset($row['catalog_type']) ? $row['catalog_type'] : '';
    
    $c_img_folder = str_replace("-","_",mb_strtolower($c_brand));
    
	$pdo = $db->prepare('SELECT * FROM '.$prefx.'_photo WHERE id=:id AND main=1');
	$pdo->execute(array( 'id' => $c_id ));
	
        foreach ($pdo as $row){
			$c_photo_name = $row['name'];
        ;}
	
	// Generate correct URL based on catalog_type
	$page_type = ($c_catalog_type == 'on_order') ? 'ordercars' : 'cars';
	
    echo '
<div class="left_window">
	<div class="left_side_name"><div></div>'.$lang_daily_offer.'</div>
	
    <a class="car_box daily_item" href="/'.$_COOKIE['lang'].'/'.$page_type.'/'.$c_brand.'-'.$c_model.'-'.$c_id.'" title="'.$c_brand_name.' '.$c_model_name.'">
		<div class="img_container">
			<img src="/'._CAR_IMG.'/'.$c_path.'/'.$c_id.'/med/'.$c_photo_name.'.jpg" alt="'.$c_brand_name.' '.$c_model_name.'" />
		</div>
		
		<div class="car_name">
			<h3><b class="brand">'.$c_brand_name.'</b><br/><b class="model">'.$c_model_name.'</b></h3>
		</div>

		<div class="car_info">
			'.$lang_year.': '.$c_year.' <br />
			'.$lang_engine.': '.$c_engine.' cm3 <br />
			'.$lang_fuel.': '.$info_fuel[$c_fuel].' <br />
			'.$lang_transmission.': '.$info_transmission[$c_transmission].' <br />
		</div>

		<div class="car_price">
			<div class="price" title="'.$lang_price.'">'.$c_price.' <span>'.$info_currency[$c_currency].'</span></div>
			<div class="prima_rata" title="'.$lang_prima_rata.'">'.$c_prima_rata.' <span>'.$info_currency[$c_currency].'</span></div>
		</div>
	</a>
</div>
    ';
}


?>