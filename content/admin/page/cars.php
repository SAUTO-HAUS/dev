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
        header('Location: ' . $redirect_url);
        exit;
	} else {
		$rtrn = '<span class="err">Check the URL</span>';
	}
	echo $rtrn;
}