<?php defined( '_DOIT' ) or die( 'Restricted access' );

$sql = 'SELECT * FROM '.$prefx.'_car_ctlg WHERE 1=1 ';

foreach($arr_types as $v){
	if ( isset($_POST[$v.'_search'])&&$_POST[$v.'_search']!='all' ) {$sql .= ' AND `'.$v.'` = :'.$v.''; $query_args[$v] = $_POST[$v.'_search'];}
}

$pdo = $db->prepare($sql); 
$pdo->execute($query_args);

foreach ($pdo as $r){
	foreach($arr_types as $v){
		if( isset($r[$v]) ){ $fltr_ar[$v][] = $r[$v]; }
	}
}

foreach($fltr_ar as $tp => $ar){
	if( isset($fltr_ar[$tp]) ){$countz = array_count_values( $fltr_ar[$tp] );}
	
	$fltr_ar[$tp] = array_unique( $fltr_ar[$tp] ); sort( $fltr_ar[$tp] , SORT_NATURAL );
	
	$i=0;
	foreach($fltr_ar[$tp] as $v){
		if($i==0){ ${'its_'.$tp}[] = '<option value="all">'.$lang_all.'</option>'; }
				
		$isSelected = (isset($_POST[$tp."_search"]) && (string)$v == (string)$_POST[$tp."_search"]) ? 'selected="selected"' : '';
		
		if( in_array($tp, ['vis', 'act']) ){
			${'its_'.$tp}[] = '<option value="'.$v.'" '.$isSelected.'>'.${'info_'.$tp}[$v].' ('.$countz[$v].')</option>';
		}
		else {
			${'its_'.$tp}[] = '<option value="'.$v.'" '.$isSelected.'>'.ucwords( str_replace('-',' ',$v) ).' ('.$countz[$v].')</option>';
		}
		
		$i++;
	}	
}

$search = [
	'br'=>$its_br,
	'mo'=>$its_mo,
	'author'=>$its_author,
	'id'=>$its_id,
	'vis'=>$its_vis,
	'act'=>$its_act
];
	
?>