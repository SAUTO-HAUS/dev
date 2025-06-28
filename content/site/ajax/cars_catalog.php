<?php defined( '_DOIT' ) or die( 'Restricted access' );

$sql = 'SELECT * FROM '.$prefx.'_catalog WHERE 1=1 AND `visible`="1" AND `active`="1" ';

foreach($arr_types as $key){
	if ( isset($_POST[$key.'_search'])&&$_POST[$key.'_search']!='' ) {$sql .= ' AND `'.$key.'` = :'.$key.''; $query_args[$key] = $_POST[$key.'_search'];}
}

//MORE CARS------------------------------------------------------------------
if ($_POST['req_func']=='more_cars'){
	$sql .= ' AND `id`<:id ';
	$query_args["id"] = $_POST["car_pos"];
}

$sql .= ' ORDER BY `id` DESC';
$sql .= ' LIMIT :car_count';
$query_args["car_count"] = ($_POST["car_count"]+1);

$pdo = $db->prepare($sql);
$pdo->execute($query_args);

$count = 0;
foreach ($pdo as $row){
	$count++;
	if($count>$_POST["car_count"]){break;}
	
	$c_id = $row['id'];
	$c_brand = $row['brand'];
	$c_model = $row['model'];
	$c_brand_name = $row['brand_name'];
	$c_model_name = $row['model_name'];
	$c_year = $row['year'];
	$c_bodytype = $row['bodytype'];
	$c_engine = $row['engine'];
	$c_fuel = $row['fuel'];
	$c_transmission = $row['transmission'];
	$c_wheel_drive = $row['wheel_drive'];
	$c_color = $row['color'];
	$c_price = $row['price'];
	if ($row['prima_rata']=='0'){$c_prima_rata=$row['prima_rata'];}
	//elseif ($row['prima_rata']=='1'){$c_prima_rata=($c_price*0.1);}
	else {$c_prima_rata=ceil($c_price*0.1);}
	$c_currency = $row['currency'];
    $c_new = $row['new'];
	$c_soon = $row['soon'];
	$c_date = $row['date'];
	$c_hp = $row['hp'];
	$c_path = $row['photo_path'];
	
	if ( ($c_new==1) && (( time() - $c_date ) > 172800) ){
		$pdo = $db->prepare('UPDATE '.$prefx.'_catalog SET `new`=0 WHERE `id`=:id');
		$pdo->execute(array( 'id' => $c_id ));
	}
	
	$c_img = mb_strtolower(str_replace("-","_",$c_id.'_'.$c_brand.'_'.$c_model.'_1'));
	$c_img_folder = str_replace("-","_",mb_strtolower($c_brand));
	
	$pdo = $db->prepare('SELECT * FROM '.$prefx.'_photo WHERE `id`= :id AND `main`="1"');
	$pdo->execute(array( 'id' => $c_id ));

	foreach ($pdo as $row2){
		$c_photo_name = $row2['name'];
	;}
	
	if($c_hp!='0'){$c_hp = $c_hp.' '.$lang_hp.'<br /><span>'.round($c_hp*0.735,0).' '.$lang_kw.'</span>';}
	$top_sales = $row['top_sales'] ? '<div class="top-sales" title="Top Sales">'.$lang_top_sales.'</div>' : '';
	$c_soon = ($c_soon==1) ? '<div class="soon_on_sale">'.$lang_soon.'</div>' : '';
	$not_av = ($row['not_av']==1) ? '<div class="not_av">'.$lang_not_av.'</div>' : '';
	
	$cars[] = '
	<a class="car_box" href="/'.$_COOKIE['lang'].'/car/'.$c_brand.'-'.$c_model.'-'.$c_id.'" title="'.$c_brand_name.' '.$c_model_name.'">
		<div class="img_container">
			'.$top_sales.'
			<img src="/'._CAR_IMG.'/'.$c_path.'/'.$c_id.'/med/'.$c_photo_name.'.jpg" alt="'.$c_brand_name.' '.$c_model_name.'" />
			'.$not_av.$soon_text.'
		</div>
		
		<div class="car_name">
			<div class="car_id"><div class="car_id_in">'.$c_hp.'</div></div>
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
			<!--<div class="prima_rata" title="'.$lang_prima_rata.'"><span style="font-size:8px;">'.$lang_prima_rata.'</span> '.$c_prima_rata.' <span>'.$info_currency[$c_currency].'</span></div>-->
			<div class="prima_rata" title="'.$lang_prima_rata.'"><span style="font-size:12px; text-decoration:line-through; opacity:0.5;"> </span> '.$c_prima_rata.' <span>'.$info_currency[$c_currency].'</span></div>
		</div>
	</a>
	';
}

if ($count<=$_POST['car_count']){$c_id=null;}
?>