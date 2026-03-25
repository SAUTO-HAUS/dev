<?php defined( '_DOIT' ) or die( 'Restricted access' );

if ($t_mp[3]=='cars' || $t_mp[3]=='ordercars'){
	$arr_types = array('br','mo','yr','fl','tra','bt','wd','catalog_type','author');

	// Translations for select fields
	$info_catalog_type  = array('in_stock' => ($lng['w']['in_stock'] ?? 'În stoc'), 'on_order' => ($lng['w']['on_order'] ?? 'La comandă'));

	$it_ar = array();
	$query_args = array();
	// No catalog_type restriction — show all cars so filter options are global
	$sql = 'SELECT * FROM '.$prefx.'_car_ctlg WHERE 1=1 ';

	$pdo = $db->prepare($sql);
	$pdo->execute($query_args);

	$status_counts = ['active'=>0,'deleted'=>0,'out_of_stock'=>0,'at_client'=>0,'hidden'=>0];
	foreach ($pdo as $r){
		foreach($arr_types as $k){
			if( isset($r[$k]) && $r[$k] !== '' ){ $it_ar[$k][] = $r[$k]; }
		}
		if ($r['act']=='1')          $status_counts['active']++;
		if ($r['act']=='0')          $status_counts['deleted']++;
		if ($r['n_a']=='1')          $status_counts['out_of_stock']++;
		if ($r['is_at_client']=='1') $status_counts['at_client']++;
		if ($r['vis']=='0')          $status_counts['hidden']++;
	}

	$filter_labels = array(
		'br'           => $lng['l']['car']['spec']['br']  ?? ($lang_br  ?? 'Marca'),
		'mo'           => $lng['l']['car']['spec']['mo']  ?? ($lang_mo  ?? 'Model'),
		'yr'           => $lng['l']['car']['spec']['yr']  ?? 'An fabricatie',
		'fl'           => $lng['l']['car']['spec']['fl']  ?? 'Combustibil',
		'tra'          => $lng['l']['car']['spec']['tra'] ?? 'Cutie viteze',
		'bt'           => $lng['l']['car']['spec']['bt']  ?? 'Caroserie',
		'wd'           => $lng['l']['car']['spec']['wd']  ?? 'Tractiune',
		'catalog_type' => 'Tip catalog',
		'author'       => $lang_author ?? 'Autor',
	);

	// Default catalog_type selection based on current page
	$default_catalog_type = ($t_mp[3] == 'ordercars') ? 'on_order' : 'in_stock';

	echo '<div id="search_content" active="0">';
	echo '<div class="open_close"></div>';
	echo '<div class="filter-inner">';
	echo '<div class="filter-header">';
	echo '<span class="filter-title">'.($lng['w']['filter'] ?? 'Filtre').'</span>';
	echo '<a class="filter-reset" href="javascript:void(0)">'.($lng['w']['clear'] ?? 'Resetează').'</a>';
	echo '<a class="filter-close" href="javascript:void(0)">&#x2715;</a>';
	echo '</div>';

	$i = 3;
	foreach ($it_ar as $tp => $arr){
		if( isset($it_ar[$tp]) ){$countz = array_count_values( $it_ar[$tp] );}

		$it_ar[$tp] = array_unique( $it_ar[$tp] );
		if ($tp == 'yr'){
			rsort($it_ar[$tp], SORT_NUMERIC);
		} else {
			sort($it_ar[$tp], SORT_NATURAL);
		}

		$label = $filter_labels[$tp] ?? strtoupper($tp);

		echo '<div class="filter-group">';
		echo '<label class="filter-label">'.$label.'</label>';
		echo '<select type="'.$tp.'" id="filter_'.$tp.'" name="'.$tp.'_search" class="search_select s_main filter-select" tabindex="1">';
		echo '<option value="all">'.($lang_all ?? 'Toate').'</option>';

		if($tp == 'catalog_type'){
			foreach($it_ar[$tp] as $k => $v){
				$sel = ($v == $default_catalog_type) ? ' selected="selected"' : '';
				if(isset($_GET[$tp]) && $_GET[$tp]==$v){ $sel=' selected="selected"'; }
				$lbl = $info_catalog_type[$v] ?? $v;
				echo '<option value="'.$v.'"'.$sel.'>'.$lbl.' ('.$countz[$v].')</option>';
			}
		} elseif( in_array($tp, array('fl','tra','bt','wd')) ){
			foreach($it_ar[$tp] as $k => $v){
				$sel = '';
				if(isset($_GET[$tp]) && $_GET[$tp]==$v){ $sel=' selected="selected"'; }
				$lbl = $lng['l']['car'][$tp][$v] ?? ucwords(str_replace('-',' ',$v));
				echo '<option value="'.$v.'"'.$sel.'>'.$lbl.' ('.$countz[$v].')</option>';
			}
		} else {
			foreach($it_ar[$tp] as $k => $v){
				$sel = '';
				if(isset($tq_mp[$i]) && $tq_mp[$i]==$v){ $sel=' selected="selected"'; }
				elseif(isset($tq_mp[$i-1]) && $tq_mp[$i-1]==$v){ $sel=' selected="selected"'; }
				elseif(isset($_GET[$tp]) && $_GET[$tp]==$v){ $sel=' selected="selected"'; }
				echo '<option value="'.$v.'"'.$sel.'>'.ucwords(str_replace(['-','_'],' ',$v)).' ('.$countz[$v].')</option>';
			}
		}

		echo '</select>';
		echo '</div>';
		$i++;
	}
	// Combined status filter with dynamic counts
	$cur_st = $_GET['status_search'] ?? 'all';
	$status_labels = [
		'active'   => $lng['w']['st_active']    ?? 'Active',
		'deleted'   => $lng['w']['st_deleted']    ?? 'Sterse',
		'out_of_stock'  => $lng['w']['st_out_of_stock']   ?? 'Nu e în stoc',
		'at_client'=> $lng['w']['st_at_client'] ?? 'Mașina la client',
		'hidden'  => $lng['w']['st_hidden']   ?? 'Ascunse',
	];
	echo '<div class="filter-group'.($cur_st!='all' ? ' active' : '').'">';
	echo '<label class="filter-label">'.($lng['w']['status_label'] ?? 'Stare').'</label>';
	echo '<select type="status" id="filter_status" name="status_search" class="search_select s_main filter-select" tabindex="1">';
	echo '<option value="all">'.($lang_all ?? 'Toate').'</option>';
	foreach ($status_labels as $val => $lbl) {
		if ($status_counts[$val] == 0) continue;
		$sel = ($cur_st == $val) ? ' selected="selected"' : '';
		echo '<option value="'.$val.'"'.$sel.'>'.$lbl.' ('.$status_counts[$val].')</option>';
	}
	echo '</select>';
	echo '</div>';

	echo '</div>'; // .filter-inner
	echo '</div>'; // #search_content
}

if ($t_mp[3]=='tyres'){
	$arr_types = array('br','w','h','d','c','ss','author','id','act');

	$query_args = array();
	$sql = 'SELECT * FROM '.$prefx.'_tyre_ctlg WHERE 1=1 ';

	$pdo = $db->prepare($sql);
	$pdo->execute($query_args);

	foreach ($pdo as $r){
		foreach($arr_types as $k){
			if( isset($r[$k]) ){ $it_ar[$k][] = $r[$k]; }
		}
	}

	echo '
	<div id="search_content" active="0">
		<div class="open_close"></div>';
		$i=3;
		foreach($it_ar as $tp => $arr){
			if( isset($it_ar[$tp]) ){$countz = array_count_values( $it_ar[$tp] );}

			echo '
			'.(isset(${'lang_'.$tp})?${'lang_'.$tp}:$tp).'<br />
			<select type="'.$tp.'" id="filter_'.$tp.'" name="'.$tp.'_search" class="search_select s_main" tabindex="1" >
				<option value="all">'.$lang_all.'</option>
			';

			$it_ar[$tp] = array_unique( $it_ar[$tp] ); sort( $it_ar[$tp] );

			if( in_array($tp,array('vis', 'act', 'c', 'ss')) ){
				foreach($it_ar[$tp] as $k => $v){
					$sel='';
					if(isset($_GET[$tp])&&$_GET[$tp]==$v){$sel=' selected="selected" ';}
					if( in_array($tp,array('vis', 'act')) && $v == '1' ){$sel=' selected="selected" ';}
					echo '<option value="'.$v.'" '.$sel.'>'.(isset($lng['l']['tyre'][$tp][$v])?$lng['l']['tyre'][$tp][$v]:${'info_'.$tp}[$v]).' ('.$countz[$v].')</option>';
				}
			}
			else {
				foreach($it_ar[$tp] as $k => $v){
					$sel='';
					if(isset($tq_mp[$i])&&$tq_mp[$i]==$v){$sel=' selected="selected" ';}
					elseif(isset($tq_mp[$i-1])&&$tq_mp[$i-1]==$v){$sel=' selected="selected" ';}
					elseif(isset($_GET[$tp])&&$_GET[$tp]==$v){$sel=' selected="selected" ';}
					echo '<option value="'.$v.'" '.$sel.'>'.ucwords( str_replace('-',' ',$v) ).' ('.$countz[$v].')</option>';
				}
			}
			echo '
			</select><br />';
			$i++;
		}
	echo '
	</div>';
}
