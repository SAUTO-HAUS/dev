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

// Build status counts (separate query without status filter so all options are counted)
$sql_sc = 'SELECT `act`, `n_a`, `is_at_client` FROM '.$prefx.'_car_ctlg WHERE 1=1 ';
foreach($arr_types as $v){
	if ( isset($_POST[$v.'_search'])&&$_POST[$v.'_search']!='all' ) { $sql_sc .= ' AND `'.$v.'` = :'.$v.''; }
}
$pdo_sc = $db->prepare($sql_sc);
$pdo_sc->execute($query_args);
$status_counts = ['active'=>0,'sterse'=>0,'nu_stoc'=>0,'la_client'=>0];
foreach ($pdo_sc as $r){
	if ($r['act']=='1')          $status_counts['active']++;
	if ($r['act']=='0')          $status_counts['sterse']++;
	if ($r['n_a']=='1')          $status_counts['nu_stoc']++;
	if ($r['is_at_client']=='1') $status_counts['la_client']++;
}

// Build status options with dynamic counts
$cur_st = $_POST['status_search'] ?? 'all';
$status_labels = [
	'active'   => $lng['w']['st_active']    ?? 'Active',
	'sterse'   => $lng['w']['st_sterse']    ?? 'Sterse',
	'nu_stoc'  => $lng['w']['st_nu_stoc']   ?? 'Nu e în stoc',
	'la_client'=> $lng['w']['st_la_client'] ?? 'Mașina la client',
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
	'author'       => $its_author       ?? [],
	'status'       => $its_status       ?? [],
];
