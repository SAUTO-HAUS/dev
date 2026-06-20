<?php defined( '_DOIT' ) or die( 'Restricted access' );

$cnt = array();

$cnt = new stdClass();

if (isset($t_mp[3])){
	if ($t_mp[3]=='cars'){
		
		$needed = array('br_nm', 'mo_nm', 'yr', 'bt', 'mlg', 'unit');
		
		$sql = 'SELECT * FROM '.$prefx.'_car_ctlg WHERE 1=1 ';
		$pdo = $db->prepare($sql);
		$pdo->execute();
		
		$i=1;
		/*
		foreach($pdo as $r){
			$cnt[$i]['id'] = $r['id'];
			$cnt[$i]['brand'] = $r['br_nm'];
			$cnt[$i]['model'] = $r['mo_nm'];
			$cnt[$i]['year'] = $r['yr'];
			$cnt[$i]['bodytype'] = $r['bt'];
			$cnt[$i]['mileage'] = $r['mlg'].' '.$r['unit'];
			$cnt[$i]['engine_capacity'] = $r['vol'];
			$cnt[$i]['horse_power'] = $r['hp'];
			$cnt[$i]['fuel'] = $r['fl'];
			$cnt[$i]['transmission'] = $r['tra'];
			$cnt[$i]['wheel_drive'] = $r['wd'];
			$cnt[$i]['seats'] = $r['sts'];
			$cnt[$i]['color'] = $r['clr'];
			$cnt[$i]['price'] = $r['prc'].' '.$r['cur'];
			$i++;
		}
		*/
		foreach($pdo as $r){
			$cnt->$i = new stdClass();
			$cnt->$i->id = $r['id'];
			$cnt->$i->brand = $r['br_nm'];
			$cnt->$i->model = $r['mo_nm'];
			$cnt->$i->year = $r['yr'];
			$cnt->$i->bodytype = $r['bt'];
			$cnt->$i->mileage = $r['mlg'].' '.$r['unit'];
			$cnt->$i->engine_capacity = $r['vol'];
			$cnt->$i->horse_power = $r['hp'];
			$cnt->$i->fuel = $r['fl'];
			$cnt->$i->transmission = $r['tra'];
			$cnt->$i->wheel_drive = $r['wd'];
			$cnt->$i->seats = $r['sts'];
			$cnt->$i->color = $r['clr'];
			$cnt->$i->price = (($r['prc_n']!=0 && $r['prc_n']<$r['prc']) ? $r['prc_n'] : $r['prc']).' '.$r['cur'];
			$i++;
		}

		unset($i);
	}
}

/*
$fp = fopen('api/cars.json', 'w');
fwrite($fp, json_encode($cnt));
fclose($fp);
*/

//echo date("GYimsd", filemtime('api/cars.json'));
$zdata = file_get_contents('api/cars.json');
echo $zdata;


//header('Location: https://www.sauto.md/api/cars.json');

?>