<?php defined( '_DOIT' ) or die( 'Restricted access' );

$sql = 'SELECT * FROM '.$prefx.'_catalog WHERE 1=1 AND `visible`="1" AND `active`="1" ';

foreach($arr_types as $key){
	if ( isset($_POST[$key.'_search'])&&$_POST[$key.'_search']!='' ) {$sql .= ' AND `'.$key.'` = :'.$key.''; $query_args[$key] = $_POST[$key.'_search'];}
}

$pdo = $db->prepare($sql);
$pdo->execute($query_args);

foreach ($pdo as $row){
	foreach($arr_types as $key){
		if($row[$key]!=''){ $filter_arr[$key][] = $row[$key]; }
	}
}

foreach($filter_arr as $type => $arr){
	//if( isset($filter_arr[$type]) ){$countz = array_count_values( $filter_arr[$type] );}
	
	$filter_arr[$type] = array_unique( $filter_arr[$type] ); sort( $filter_arr[$type] , SORT_NATURAL );
	
	$i=0;
	foreach($filter_arr[$type] as $key){
		if($i==0){ ${'cars_'.$type}[] = '<option value="">'.$lang_all.'</option>'; }
				
		$isSelected = $key == $_POST[$type."_search"] ? 'selected="selected"' : '';
		
		if( in_array($type,array('bodytype', 'fuel', 'transmission', 'wheel_drive', 'color')) ){
			${'cars_'.$type}[] = '<option value="'.$key.'" '.$isSelected.'>'.${'info_'.$type}[$key].'</option>';
		}
		else {
			${'cars_'.$type}[] = '<option value="'.$key.'" '.$isSelected.'>'.ucwords( str_replace('-',' ',$key) ).'</option>';
		}
		
		$i++;
	}	
}

$search = array(
	'brand'=>$cars_brand,
	'model'=>$cars_model,
	'year'=>$cars_year,
	'bodytype'=>$cars_bodytype,
	'fuel'=>$cars_fuel,
	'transmission'=>$cars_transmission,
	'wheel_drive'=>$cars_wheel_drive,
	'color'=>$cars_color/*,
	'prima_rata'=>$cars_prima_rata,
	'price'=>$cars_price*/
);
	
?>