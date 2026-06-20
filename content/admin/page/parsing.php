<?php defined( '_DOIT' ) or die( 'Restricted access' );

require_once(_ADM_INCL.'/parsing_access.php');
$parsingFullAccess = parsing_is_full($user_id ?? 0);

if (isset($t_mp[4])) {
    if ($t_mp[4] == 'filters') {
        include _ADM_PAGE.'/parsing/filters.php';
    } elseif ($t_mp[4] == 'ctlg') {
        include _ADM_PAGE.'/parsing/ctlg.php';
    } elseif ($t_mp[4] == 'favorites') {
        include _ADM_PAGE.'/parsing/favorites.php';
    } elseif ($t_mp[4] == 'published') {
        include _ADM_PAGE.'/parsing/published.php';
    } elseif ($t_mp[4] == 'settings' && $parsingFullAccess) {
        include _ADM_PAGE.'/parsing/settings.php';
    } elseif ($t_mp[4] == 'logs' && $parsingFullAccess) {
        include _ADM_PAGE.'/parsing/logs.php';
    } else {
        echo '<span class="err">Check the URL or access denied</span>';
    }
} else {
    include _ADM_PAGE.'/parsing/filters.php';
}
