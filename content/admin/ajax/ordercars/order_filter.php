<?php defined( '_DOIT' ) or die( 'Restricted access' );

$info_catalog_type = array('in_stock' => ($lng['w']['in_stock'] ?? 'În stoc'), 'on_order' => ($lng['w']['on_order'] ?? 'La comandă'));
$info_loc          = array('1' => ($lng['w']['loc_1'] ?? 'str. Calea Moșilor 11'), '2' => ($lng['w']['loc_2'] ?? 'str. Pietrăriei 3'));

foreach($arr_types as $v){
	if ( isset($_POST[$v.'_search']) && $_POST[$v.'_search'] != 'all' ){
		$query_args[$v] = $_POST[$v.'_search'];
	}
}

$status_sql = '';
if (isset($_POST['status_search']) && $_POST['status_search'] != 'all') {
	switch ($_POST['status_search']) {
		case 'active':       $status_sql = ' AND `act`="1" AND `n_a`="0" AND `is_at_client`="0" AND `vis`="1"'; break;
		case 'deleted':      $status_sql = ' AND `act`="0"'; break;
		case 'out_of_stock': $status_sql = ' AND `n_a`="1" AND `act`="1"'; break;
		case 'at_client':    $status_sql = ' AND `is_at_client`="1" AND `act`="1"'; break;
		case 'hidden':       $status_sql = ' AND `vis`="0" AND `act`="1"'; break;
	}
}

foreach($arr_types as $field){
	$sql_f = 'SELECT `'.$field.'` FROM '.$prefx.'_car_ctlg WHERE 1=1'.$status_sql;
	$args_f = [];
	foreach($query_args as $k => $v){
		if ($k === $field) continue;
		$sql_f .= ' AND `'.$k.'` = :'.$k;
		$args_f[$k] = $v;
	}
	$pdo_f = $db->prepare($sql_f);
	$pdo_f->execute($args_f);
	foreach($pdo_f as $r){
		if(isset($r[$field]) && $r[$field] !== '') $fltr_ar[$field][] = $r[$field];
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
		} elseif($tp == 'loc'){
			$lbl = $info_loc[$v] ?? $v;
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

// Status counts: query without status filter but with all active field filters
$sql_sc = 'SELECT `act`, `n_a`, `is_at_client`, `vis` FROM '.$prefx.'_car_ctlg WHERE 1=1 ';
foreach($query_args as $k => $v){ $sql_sc .= ' AND `'.$k.'` = :'.$k; }
$pdo_sc = $db->prepare($sql_sc);
$pdo_sc->execute($query_args);
$status_counts = ['active'=>0,'deleted'=>0,'out_of_stock'=>0,'at_client'=>0,'hidden'=>0];
foreach ($pdo_sc as $r){
	if ($r['act']=='1' && $r['n_a']=='0' && $r['is_at_client']=='0' && $r['vis']=='1') $status_counts['active']++;
	if ($r['act']=='0')                                                                  $status_counts['deleted']++;
	if ($r['n_a']=='1'          && $r['act']=='1')                                       $status_counts['out_of_stock']++;
	if ($r['is_at_client']=='1' && $r['act']=='1')                                       $status_counts['at_client']++;
	if ($r['vis']=='0'          && $r['act']=='1')                                       $status_counts['hidden']++;
}

// Build status options
$cur_st = $_POST['status_search'] ?? 'all';
$status_labels = [
	'active'       => $lng['w']['st_active']       ?? 'Active',
	'deleted'      => $lng['w']['st_deleted']       ?? 'Sterse',
	'out_of_stock' => $lng['w']['st_out_of_stock']  ?? 'Nu e în stoc',
	'at_client'    => $lng['w']['st_at_client']     ?? 'Mașina la client',
	'hidden'       => $lng['w']['st_hidden']        ?? 'Ascunse',
];
$its_status[] = '<option value="all">'.($lang_all??'Toate').'</option>';
foreach ($status_labels as $val => $lbl) {
	if ($status_counts[$val] == 0) continue;
	$sel = ($cur_st==$val) ? ' selected="selected"' : '';
	$its_status[] = '<option value="'.$val.'"'.$sel.'>'.$lbl.' ('.$status_counts[$val].')</option>';
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
	'loc'          => $its_loc          ?? [],
	'author'       => $its_author       ?? [],
	'status'       => $its_status       ?? [],
];
