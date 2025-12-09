<?php defined( '_DOIT' ) or die( 'Restricted access' );

if (isset($t_mp[4])) {
	$rtrn = '';
	if ($t_mp[4] == 'ctlg') {
        include _ADM_PAGE.'/cars/brands_seo.php';
	} else {
		$rtrn = '<span class="err">Check the URL</span>';
	}
	echo $rtrn;
}
