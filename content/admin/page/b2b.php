<?php defined('_DOIT') or die('Restricted access');

/**
 * "B2B Management" admin module (spec 3). Router modelled on admin/page/crm.php.
 *
 * Route: /{lang}/adminsauto/b2b/{action} - users | user | history | requests | settings
 * Access: Super Admin (`gordon`) only, per the specification.
 */

require_once(_ADM_INCL.'/b2b/b2b_admin_lang.php');

use App\Core\Container;

// The B2B services read $db/$prefx from the container; index.php fills it, but
// the embed context may not have.
try {
    Container::get('db');
} catch (\Throwable $e) {
    Container::set('db', $db);
    Container::set('prefix', $prefx);
}

if (($user_role ?? '') !== 'gordon') {
    echo '<div style="padding:2rem;color:#e2001a;">Acces restricționat.</div>';
    return;
}

$b2b_action = $t_mp[4] ?? 'users';

echo '<link rel="stylesheet" href="/content/admin/include/b2b/b2b_admin.css?v='
   . (file_exists(_ROOT.'/content/admin/include/b2b/b2b_admin.css')
        ? date('YmdHis', filemtime(_ROOT.'/content/admin/include/b2b/b2b_admin.css'))
        : '1')
   . '">';

switch ($b2b_action) {
    case 'user':
        include(_ADM_INCL.'/b2b/views/user_detail.php');
        break;

    case 'history':
        include(_ADM_INCL.'/b2b/views/history.php');
        break;

    case 'requests':
        include(_ADM_INCL.'/b2b/views/requests.php');
        break;

    case 'settings':
        include(_ADM_INCL.'/b2b/views/settings.php');
        break;

    case 'pricing':
        include(_ADM_INCL.'/b2b/views/pricing.php');
        break;

    case 'users':
    default:
        include(_ADM_INCL.'/b2b/views/users.php');
        break;
}
