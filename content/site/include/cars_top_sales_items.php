<?php defined( '_DOIT' ) or die( 'Restricted access' );

$car_count = 4;
$car_array = array();

$arr_types = array('id','brand','model', 'brand_name', 'model_name', 'year','engine','fuel','transmission','prima_rata','price','currency','hp','photo_path', 'top_sales', 'catalog_type');

$query_args = array();
$sql = 'SELECT * FROM '.$prefx.'_catalog WHERE `top_sales` = 1';

if ($car_count > 0){ // Top sales

	$pdo = $db->prepare('SELECT * FROM '.$prefx.'_catalog WHERE `top_sales` = "1" AND `visible` = "1" AND `active` = "1" ORDER BY RAND() LIMIT :count');
	$pdo->execute(array('count' => $car_count));
	foreach ($pdo as $row){
		foreach($arr_types as $key){ $car_array[$i][$key]=$row[$key];}
		$car_array[$i]['img_folder']=str_replace( "-", "_", mb_strtolower( $row['brand'] ) );
		$car_count--;
		$i++;
	}
	
}


foreach($car_array as $key => $value){
	
	$pdo = $db->prepare('SELECT * FROM '.$prefx.'_photo WHERE id=:id AND main=1');
	$pdo->execute(array( 'id' => $value['id'] ));
	foreach ($pdo as $row){ 
		$c_photo_name = $row['name'];
	}
	
	if($value['hp']!='0'){$c_hp = $value['hp'].' '.$lang_hp.'<br /><span>'.round($value['hp']*0.735,0).' '.$lang_kw.'</span>';}else{$c_hp='';}
	
	// Generate correct URL based on catalog_type
	$page_type = (isset($value['catalog_type']) && $value['catalog_type'] == 'on_order') ? 'ordercars' : 'cars';
	
	echo '
    <a class="car_box similar sales" style="position:relative;" href="/'.$_COOKIE['lang'].'/'.$page_type.'/'.$value['brand'].'-'.$value['model'].'-'.$value['id'].'">
		<div style="width:100%; height:30px; text-align:center; float:left; position:absolute; left:0; z-index:15; background-color:rgba(0,0,0,0.7); color:#fff; line-height:30px;">'.${'lang_top_sales'}.'</div>
		<div class="img_container">
			<img src="/'._CAR_IMG.'/'.$value['photo_path'].'/'.$value['id'].'/med/'.$c_photo_name.'.jpg" alt="'.$value['brand_name'].' '.$value['model_name'].'" title="'.$value['brand_name'].' '.$value['model_name'].'" />
		</div>
		
		<div class="car_name">
			<div class="car_id"><div class="car_id_in">'.$c_hp.'</div></div>
			<h3><b class="brand">'.$value['brand_name'].'</b><br/><b class="model">'.$value['model_name'].'</b></h3>
		</div>

		<div class="car_info">
			'.$lang_year.': '.$value['year'].' <br />
			'.$lang_engine.': '.$value['engine'].' cm3 <br />
			'.$lang_fuel.': '.$info_fuel[$value['fuel']].' <br />
			'.$lang_transmission.': '.$info_transmission[$value['transmission']].' <br />
		</div>

		<div class="car_price">
			<div class="price" title="'.$lang_price.'">'.$value['price'].' <span>'.$info_currency[$value['currency']].'</span></div>
			<!--<div class="prima_rata" title="'.$lang_prima_rata.'">'.$value['prima_rata'].' <span>'.$info_currency[$value['currency']].'</span></div>-->
			<div class="prima_rata" title="'.$lang_prima_rata.'"><span style="font-size:12px; text-decoration:line-through; opacity:0.5;"> '.$value['prima_rata'].' </span> '.($value['price']*0.1).' <span>'.$info_currency[$value['currency']].'</span></div>
		</div>
	</a>
    ';
;}


unset($result);
unset($result_photo);
?>