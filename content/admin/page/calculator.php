<?php defined( '_DOIT' ) or die( 'Restricted access' );

if (isset($t_mp[4])) {
    if ($t_mp[4] == 'calc') {
        include _ADM_PAGE.'/calculator/calc.php';
    } elseif ($t_mp[4] == 'rates' && isset($user_role) && $user_role === 'gordon') {
        include _ADM_PAGE.'/calculator/rates.php';
    } elseif ($t_mp[4] == 'usage' && isset($user_role) && $user_role === 'gordon') {
        include _ADM_PAGE.'/calculator/usage.php';
    } else {
        echo '<span class="err">Check the URL or access denied</span>';
    }
}
