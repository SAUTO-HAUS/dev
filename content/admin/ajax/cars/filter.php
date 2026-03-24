<?php defined( '_DOIT' ) or die( 'Restricted access' );

$info_catalog_type  = array('in_stock' => ($lng['w']['in_stock'] ?? 'În stoc'), 'on_order' => ($lng['w']['on_order'] ?? 'La comandă'));

// No act="1" restriction — show all cars so deleted ones appear in act dropdown
$sql = 'SELECT * FROM '.$prefx.'_car_ctlg WHERE 1=1 ';

foreach($arr_types as $v){
	if ( isset($_POST[$v.'_search'])&&$_POST[$v.'_search']!='all' ) {$sql .= ' AND `'.$v.'` = :'.$v.''; $query_args[$v] = $_POST[$v.'_search'];}
}

// Combined status filter
if (isset($_POST['status_search']) && $_POST['status_search'] != 'all') {
	switch ($_POST['status_search']) {
		case 'active':    $sql .= ' AND `act`="1"'; break;
		case 'sterse':    $sql .= ' AND `act`="0"'; break;
		case 'nu_stoc':   $sql .= ' AND `n_a`="1"'; break;
		case 'la_client': $sql .= ' AND `is_at_client`="1"'; break;
	}
}

$pdo = $db->prepare($sql);
$pdo->execute($query_args);

foreach ($pdo as $r){
	foreach($arr_types as $v){
		if( isset($r[$v]) && $r[$v] !== '' ){ $fltr_ar[$v][] = $r[$v]; }
	}
}

foreach($fltr_ar as $tp => $ar){
	if( isset($fltr_ar[$tp]) ){$countz = array_count_values( $fltr_ar[$tp] );}

	$fltr_ar[$tp] = array_unique( $fltr_ar[$tp] );
	if ($tp == 'yr'){
		rsort($fltr_ar[$tp], SORT_NUMERIC);
	} else {
		sort($fltr_ar[$tp], SORT_NATURAL);
	}

	$i=0;
	foreach($fltr_ar[$tp] as $v){
		if($i==0){ ${'its_'.$tp}[] = '<option value="all">'.($lang_all ?? 'Toate').'</option>'; }

		$isSelected = (isset($_POST[$tp."_search"]) && (string)$v == (string)$_POST[$tp."_search"]) ? 'selected="selected"' : '';

		if($tp == 'catalog_type'){
			$lbl = $info_catalog_type[$v] ?? $v;
			${'its_'.$tp}[] = '<option value="'.$v.'" '.$isSelected.'>'.$lbl.' ('.$countz[$v].')</option>';
		} elseif( in_array($tp, ['fl','tra','bt','wd']) ){
			$lbl = $lng['l']['car'][$tp][$v] ?? ucwords(str_replace('-',' ',$v));
			${'its_'.$tp}[] = '<option value="'.$v.'" '.$isSelected.'>'.$lbl.' ('.$countz[$v].')</option>';
		} else {
			${'its_'.$tp}[] = '<option value="'.$v.'" '.$isSelected.'>'.ucwords( str_replace(['-','_'],' ',$v) ).' ('.$countz[$v].')</option>';
		}

		$i++;
	}
}

// Build status options
$cur_st = $_POST['status_search'] ?? 'all';
$status_map = ['all'=>($lang_all??'Toate'),'active'=>'Active','sterse'=>'Sterse','nu_stoc'=>'Nu e în stoc','la_client'=>'Mașina la client'];
foreach ($status_map as $val => $lbl) {
	$sel = ($cur_st==$val) ? ' selected="selected"' : '';
	$its_status[] = '<option value="'.$val.'"'.$sel.'>'.$lbl.'</option>';
}

$search = [
	'br'           => $its_br           ?? [],
	'mo'           => $its_mo           ?? [],
	'yr'           => $its_yr           ?? [],
	'fl'           => $its_fl           ?? [],
	'tra'          => $its_tra          ?? [],
	'bt'           => $its_bt           ?? [],
	'wd'           => $its_wd           ?? [],
	'catalog_type' => $its_catalog_type ?? [],
	'author'       => $its_author       ?? [],
	'status'       => $its_status       ?? [],
];
