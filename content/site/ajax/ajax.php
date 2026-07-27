<?php defined( '_DOIT' ) or die( 'Restricted access' );

/*if ( !in_array($_POST['fn'], array('search','more_cars','filter','order_car'), true) ){ die( 'Restricted access' ); }*/

//FILTER + MORE CARS------------------------------------------------------------------
if ( $_POST['fn']=='search'||$_POST['fn']=='more_cars'||$_POST['fn']=='filter' ){

	$arr_types = array('brand','model','year','bodytype','fuel','transmission','wheel_drive','color','prima_rata','price');
	$cars = array();
	$query_args = array();
	$all_search = array();
	$search = array();
	
	require_once (_SITE_AJAX.'/cars_filter.php');
	require_once (_SITE_AJAX.'/cars_catalog.php');
	
	$returnIt = array(
		'fn'=>$_POST['fn'],
		'car_count'=>$_POST['car_count'],
		'car_pos'=>$c_id,
		'cars'=>$cars,
		'search'=>$search
	);
}

//ORDER A CAR------------------------------------------------------------------
elseif ($_POST['fn']=='order_item'){
	
	require_once (_SITE_AJAX.'/order_item.php');
	
	$returnIt = array(
		'fn'=>$_POST['fn'],
		'sub'=>$_POST['sub'],
		'models'=>$models
	);
}

//FAVORITES (render cards for a list of car IDs) ------------------------------
elseif ($_POST['fn']=='fav_cars'){
	require_once(_SITE_INCL.'/functions.php');

	$ids = array();
	if (isset($_POST['ids'])) {
		$raw = is_array($_POST['ids']) ? $_POST['ids'] : explode(',', (string)$_POST['ids']);
		foreach ($raw as $rid) { $rid = (int)$rid; if ($rid > 0) { $ids[] = $rid; } }
	}

	$card = $car_card('fav', count($ids) ?: 1, $ids, 'av', 0, true);

	$returnIt = array(
		'fn'   => 'fav_cars',
		'html' => $card['txt'],
		'count'=> $card['qu'],
		'ids'  => $card['ids'] ?? array()
	);
}

// COMPARE CARS (localStorage list -> side-by-side comparison table) -----------
elseif ($_POST['fn']=='compare_cars'){
	require_once(_SITE_INCL.'/functions.php');       // b2b_prices_for_cars, lang
	require_once(_SITE_INCL.'/compare_table.php');   // compare_table_html

	$ids = array();
	if (isset($_POST['ids'])) {
		$raw = is_array($_POST['ids']) ? $_POST['ids'] : explode(',', (string)$_POST['ids']);
		foreach ($raw as $rid) { $rid = (int)$rid; if ($rid > 0) { $ids[] = $rid; } }
	}

	$validIds = array();
	$html = function_exists('compare_table_html') ? compare_table_html($db, $prefx, $lng, $ids, $validIds) : '';

	$returnIt = array(
		'fn'   => 'compare_cars',
		'html' => $html,
		'count'=> count($validIds),   // only the cars that still exist
		'ids'  => $validIds           // client prunes localStorage to these
	);
}

//SEND MESSAGE------------------------------------------------------------------
elseif ($_POST['fn']=='snd_msg'){
	
	require_once (_SITE_AJAX.'/snd_msg.php');
	
	$ztrgt = ( isset($_POST['target']) ) ? $_POST['target'] : 'self';
	
	$returnIt = array(
		'fn'=>$_POST['fn'],
		'target'=>$ztrgt
	);
}


//LOAD MORE BRAND CARS------------------------------------------------------------------
elseif ($_POST['fn']=='load_more_brand_cars'){
	// Check page type to use appropriate functions file
	$page_type = isset($_POST['page_type']) ? $_POST['page_type'] : 'cars';

	if ($page_type === 'ordercars') {
		require_once(_SITE_INCL.'/order_functions.php');
	} else {
		require_once(_SITE_INCL.'/functions.php');
	}

	$brand = isset($_POST['brand']) ? str_replace('-', '_', $_POST['brand']) : '';
	$model = isset($_POST['model']) && !empty($_POST['model']) ? str_replace('-', '_', $_POST['model']) : null;
	$offset = isset($_POST['offset']) ? (int)$_POST['offset'] : 0;
	$limit = isset($_POST['limit']) ? (int)$_POST['limit'] : 16;

	// Build filter request
	$filter_req = ['tg' => 'fltr', 'br' => $brand];
	if ($model) {
		$filter_req['mo'] = $model;
	}

	// Get additional filter params if any
	$filter_params = ['bt', 'fl', 'tra', 'wd', 'clr', 'yr', 'mlg', 'vol', 'prc', 'sts', 'loc', 'gr', 'srt'];
	foreach ($filter_params as $param) {
		if (isset($_POST[$param]) && !empty($_POST[$param])) {
			$filter_req[$param] = $_POST[$param];
		}
	}

	// Call car_card with pagination support - $is_brand_page=true disables promo inserts
	$card = $car_card('fltr', $limit, $filter_req, 'av', $offset, true);

	$returnIt = array(
		'fn' => $_POST['fn'],
		'html' => $card['txt'],
		'count' => $card['qu'],
		'total' => $card['total'],
		'offset' => $offset,
		'has_more' => ($offset + $card['qu']) < $card['total']
	);
}

elseif  ($_POST['fn']=='calculator'){
    // C8 сумма кредита
    // С10 срок мес
    // C11 процент

    // =C8*((C11/12)+((C11/12)/(СТЕПЕНЬ(1+(C11/12);C10)-1)))
    function calculateCreditPayment($loanAmount, $months, $annualRate) {
        $monthlyRate = $annualRate / 12;

        if ($monthlyRate == 0) {
            // Если 0% — простой расчет без процентов
            return $loanAmount / $months;
        }

        $payment = $loanAmount * (
                $monthlyRate + ($monthlyRate / (pow(1 + $monthlyRate, $months) - 1))
            );

        return $payment;
    }

    $sum = $_POST['suma'];
    $term = $_POST['termen'];
    $rate = 0.20;  // C11 (20% годовых)

    $monthlyPayment = calculateCreditPayment($sum, $term, $rate);

    // от средней суммы плюс 21% и минус 21% что бы получить суммы от и до
    $suma_cu_21_procent = $monthlyPayment * 1.21;
    $suma_fara_21_procent = $monthlyPayment / 1.21;

    $returnIt = array(
        'min_suma'=> round( $suma_fara_21_procent), // сумма от
        'max_suma'=> round( $suma_cu_21_procent), // сумма до
    );
}

//B2B MODULE (register, 2FA login, cabinet actions)-----------------------------
elseif ( strpos((string)($_POST['fn'] ?? ''), 'b2b_') === 0 ){

	require_once (_SITE_AJAX.'/b2b.php');
}

else { die( 'Restricted access' ); }

?>