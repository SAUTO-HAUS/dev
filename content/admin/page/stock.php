<?php defined( '_DOIT' ) or die( 'Restricted access' );

if (isset($t_mp[4])) {
	$rtrn = '';
	if ($t_mp[4] == 'ctlg') {
        include _ADM_PAGE.'/stock/catalog.php';
	} elseif ($t_mp[4] == 'extern') {
        include _ADM_PAGE.'/stock/extern.php';
	} else {
		$rtrn = '<span class="err">Check the URL</span>';
	}
	echo $rtrn;
}
