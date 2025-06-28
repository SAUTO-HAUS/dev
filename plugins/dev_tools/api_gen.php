<?php defined( '_DOIT' ) or die( 'Restricted access' );
//header("Content-Type: application/json; charset=UTF-8");
if ( isset($_GET['tg']) && $_GET['tg']=='car_list' ){
	$cnt = new stdClass();
	$needed = array('br_nm', 'mo_nm', 'yr', 'bt', 'mlg', 'unit');
	
	//$sql = 'SELECT it.*, p.id p_id, p.name p_nm, p.main p_main FROM '.$prefx.'_car_ctlg AS it LEFT JOIN '.$prefx.'_car_pht AS p ON p.id=it.id AND it.act=1 ORDER BY it.id DESC ';
	
	$sql = 'SELECT * FROM '.$prefx.'_car_ctlg WHERE `act`=1 ';
	$pdo = $db->prepare($sql);
	$pdo->execute();
	
	$i=1;
	
	foreach($pdo as $r){
		$cnt->$i = new stdClass();
		$cnt->$i->page = 'https://www.sauto.md/ro/cars/'.$r['id'];
		$cnt->$i->brand = $r['br_nm'];
		$cnt->$i->model = $r['mo_nm'];
		$cnt->$i->year = $r['yr'];
		$cnt->$i->bodytype = (isset($lng['l']['car']['bt'][$r['bt']])) ? $lng['l']['car']['bt'][$r['bt']] : $r['bt'];
		$cnt->$i->mileage = $r['mlg'];
		$cnt->$i->unit = $r['unit'];
		$cnt->$i->engine_capacity = $r['vol'];
		$cnt->$i->horse_power = $r['hp'];
		$cnt->$i->fuel = $lng['l']['car']['fl'][$r['fl']];
		$cnt->$i->transmission = $lng['l']['car']['tra'][$r['tra']];
		$cnt->$i->wheel_drive = $lng['l']['car']['wd'][$r['wd']];
		$cnt->$i->seats = $r['sts'];
		$cnt->$i->color = $lng['l']['car']['clr'][$r['clr']];
		$cnt->$i->price = $r['prc'];
		$cnt->$i->currency = $r['cur'];
		$cnt->$i->photo = new stdClass();
		$cnt->$i->photo->other = new stdClass();
		
		$pdo = $db->prepare('SELECT * FROM '.$prefx.'_car_pht WHERE `it_id`=:it_id ');
		$pdo->execute(array('it_id'=>$r['id']));
		
		$i2=1;
		foreach($pdo as $im){
			if ($im['main']==1){
				$cnt->$i->photo->main = 'https://www.sauto.md/media/images/upload/car/'.$r['p_path'].'/'.$r['id'].'/high/'.$im['name'].'.jpg';
			}else{
				$cnt->$i->photo->other->$i2 = 'https://www.sauto.md/media/images/upload/car/'.$r['p_path'].'/'.$r['id'].'/high/'.$im['name'].'.jpg';
			}
			$i2++;
		}
		
		$i++;
		
	}
	
	unset($i, $i2);
		
	$fp = fopen('api/car_list.json', 'w');
	if ( fwrite($fp, json_encode($cnt)) ){echo 'OK';}else{echo 'ERROR';}
	fclose($fp);
	
	
	/*
	function get_content($URL){
		$ch = curl_init();
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
		curl_setopt($ch, CURLOPT_URL, $URL);
		$data = curl_exec($ch);
		curl_close($ch);
		return $data;
	}
	
	echo get_content('https://www.sauto.md/api/car_list.json');
	*/
	
	//echo date("GYimsd", filemtime('api/cars.json'));
	//$zdata = file_get_contents('api/cars.json');
	//echo $zdata;
	//header('Location: https://www.sauto.md/api/cars.json');
}else{
	echo 'API';
}

?>