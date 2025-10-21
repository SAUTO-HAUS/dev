<?php defined( '_DOIT' ) or die( 'Restricted access' );

if (isset($t_mp[4])) {
	$rtrn = '';
	if ($t_mp[4] == 'ctlg') {
        include _ADM_PAGE.'/ordercars/order_catalog.php';
	} elseif ($t_mp[4] == 'br_lst') {
        include _ADM_PAGE.'/ordercars/order_brands_list.php';
    } elseif ($t_mp[4] == 'detail') {
        include _ADM_PAGE.'/ordercars/order_car.php';
    } elseif ($t_mp[4] == 'add') {
        // Redirect to the correct detail page for adding a new car
        $redirect_url = '/' . $_COOKIE['lang'] . '/' . $admin_dir . '/ordercars/detail';
        $rtrn = '<script>window.location.href = "' . $redirect_url . '";</script>';
        $rtrn .= '<div style="text-align:center; padding:2rem;">Redirecting to car add page... <a href="' . $redirect_url . '">Click here if not redirected</a></div>';
    } elseif ($t_mp[4] == 'create') {
        // Handle create route - redirect to detail page for adding new car
        include _ADM_PAGE.'/ordercars/order_car.php';
	} else {
		$rtrn = '<span class="err">Check the URL</span>';
	}
	echo $rtrn;
}