<?php defined( '_DOIT' ) or die( 'Restricted access' );

if (isset($t_mp[4])) {
	$rtrn = '';
	if ($t_mp[4] == 'ctlg') {
        include _ADM_PAGE.'/cars/catalog.php';
	} elseif ($t_mp[4] == 'br_lst') {
        include _ADM_PAGE.'/cars/brands_list.php';
    } elseif ($t_mp[4] == 'detail') {
        include _ADM_PAGE.'/cars/car.php';
	} else {
		$rtrn = '<span class="err">Check the URL</span>';
	}
	echo $rtrn;
}