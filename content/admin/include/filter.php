<?php defined( '_DOIT' ) or die( 'Restricted access' );

if ($t_mp[3]=='cars' || $t_mp[3]=='ordercars'){
	$arr_types = array('br','mo','yr','fl','tra','author','act');

	$query_args = array();
	$sql = 'SELECT * FROM '.$prefx.'_car_ctlg WHERE 1=1 ';
	if ($t_mp[3]=='ordercars'){
		$sql .= ' AND catalog_type = "on_order" ';
	} else {
		$sql .= ' AND catalog_type = "in_stock" ';
	}

	$pdo = $db->prepare($sql);
	$pdo->execute($query_args);

	foreach ($pdo as $r){
		foreach($arr_types as $k){
			if( isset($r[$k]) && $r[$k] !== '' ){ $it_ar[$k][] = $r[$k]; }
		}
	}

	$filter_labels = [
		'br'     => $lng['l']['car']['spec']['br']  ?? ($lang_br ?? 'Marca'),
		'mo'     => $lng['l']['car']['spec']['mo']  ?? ($lang_mo ?? 'Model'),
		'yr'     => $lng['l']['car']['spec']['yr']  ?? 'An',
		'fl'     => $lng['l']['car']['spec']['fl']  ?? 'Combustibil',
		'tra'    => $lng['l']['car']['spec']['tra'] ?? 'Cutie',
		'author' => $lang_author ?? 'Autor',
		'act'    => $lang_act ?? 'Status',
	];

	echo '<div id="search_content" active="0">';
	echo '<div class="open_close"></div>';
	echo '<div class="filter-header">';
	echo '<span class="filter-title">Filtre</span>';
	echo '<a class="filter-reset" href="javascript:void(0)">'.($lng['w']['clear'] ?? 'Reset').'</a>';
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

		$label = isset($filter_labels[$tp]) ? $filter_labels[$tp] : strtoupper($tp);

		echo '<div class="filter-group">';
		echo '<label class="filter-label">'.$label.'</label>';
		echo '<select type="'.$tp.'" id="filter_'.$tp.'" name="'.$tp.'_search" class="search_select s_main filter-select" tabindex="1">';
		echo '<option value="all">'.$lang_all.'</option>';

		if( in_array($tp, array('vis', 'act')) ){
			foreach($it_ar[$tp] as $k => $v){
				$sel='';
				if(isset($_GET[$tp])&&$_GET[$tp]==$v){$sel=' selected="selected" ';}
				if( $tp=='act' && $v == '1' ){$sel=' selected="selected" ';}
				echo '<option value="'.$v.'" '.$sel.'>'.${'info_'.$tp}[$v].' ('.$countz[$v].')</option>';
			}
		} elseif( in_array($tp, array('fl','tra')) ){
			foreach($it_ar[$tp] as $k => $v){
				$sel='';
				if(isset($_GET[$tp])&&$_GET[$tp]==$v){$sel=' selected="selected" ';}
				$lbl = $lng['l']['car'][$tp][$v] ?? ucwords(str_replace('-',' ',$v));
				echo '<option value="'.$v.'" '.$sel.'>'.$lbl.' ('.$countz[$v].')</option>';
			}
		} else {
			foreach($it_ar[$tp] as $k => $v){
				$sel='';
				if(isset($tq_mp[$i])&&$tq_mp[$i]==$v){$sel=' selected="selected" ';}
				elseif(isset($tq_mp[$i-1])&&$tq_mp[$i-1]==$v){$sel=' selected="selected" ';}
				elseif(isset($_GET[$tp])&&$_GET[$tp]==$v){$sel=' selected="selected" ';}
				echo '<option value="'.$v.'" '.$sel.'>'.ucwords( str_replace('-',' ',$v) ).' ('.$countz[$v].')</option>';
			}
		}

		echo '</select>';
		echo '</div>';
		$i++;
	}
	echo '</div>';
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
			//$countz = array_count_values($arr);//подсчет
			if( isset($it_ar[$tp]) ){$countz = array_count_values( $it_ar[$tp] );}

			echo '
			'.(isset(${'lang_'.$tp})?${'lang_'.$tp}:$tp).'<br />
			<select type="'.$tp.'" id="filter_'.$tp.'" name="'.$tp.'_search" class="search_select s_main" tabindex="1" >
				<option value="all">'.$lang_all.'</option>
			';

			$it_ar[$tp] = array_unique( $it_ar[$tp] ); sort( $it_ar[$tp] );//сортировка

			//с переводом языка
			if( in_array($tp,array('vis', 'act', 'c', 'ss')) ){
				foreach($it_ar[$tp] as $k => $v){
					$sel='';
					if(isset($_GET[$tp])&&$_GET[$tp]==$v){$sel=' selected="selected" ';}
					if( in_array($tp,array('vis', 'act')) && $v == '1' ){$sel=' selected="selected" ';}
					echo '<option value="'.$v.'" '.$sel.'>'.(isset($lng['l']['tyre'][$tp][$v])?$lng['l']['tyre'][$tp][$v]:${'info_'.$tp}[$v]).' ('.$countz[$v].')</option>';
				}
			}
			//без перевода
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
