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

//SEND MESSAGE------------------------------------------------------------------
elseif ($_POST['fn']=='snd_msg'){
	
	require_once (_SITE_AJAX.'/snd_msg.php');
	
	$ztrgt = ( isset($_POST['target']) ) ? $_POST['target'] : 'self';
	
	$returnIt = array(
		'fn'=>$_POST['fn'],
		'target'=>$ztrgt
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

else { die( 'Restricted access' ); }

?>