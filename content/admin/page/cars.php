<?php defined( '_DOIT' ) or die( 'Restricted access' );

if (isset($t_mp[4])) {
	$rtrn = '';
	if ($t_mp[4] == 'ctlg') {
        include _ADM_PAGE.'/cars/catalog.php';
	} elseif ($t_mp[4] == 'br_lst') {
        include _ADM_PAGE.'/cars/brands_list.php';
    } elseif ($t_mp[4] == 'detail') {
        include _ADM_PAGE.'/cars/car.php';
    } elseif ($t_mp[4] == 'add') {
        // Redirect to the correct detail page for adding a new car
        $redirect_url = '/' . $_COOKIE['lang'] . '/' . $admin_dir . '/cars/detail';
        $rtrn = '<script>window.location.href = "' . $redirect_url . '";</script>';
        $rtrn .= '<div style="text-align:center; padding:2rem;">Redirecting to car add page... <a href="' . $redirect_url . '">Click here if not redirected</a></div>';
	} else {
		$rtrn = '<span class="err">Check the URL</span>';
	}
	echo $rtrn;
}