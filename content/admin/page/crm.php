<?php defined('_DOIT') or die('Restricted access');

require_once(_ADM_INCL.'/crm/crm_core.php');

if (!crm_has_access($user_role, $user_id)) {
    echo '<div style="padding:2rem;color:#e2001a;">Acces restricționat.</div>';
    return;
}

// Load current user's CRM access restriction (null = all, 'stock', 'order', 'pruncul')
$_crm_access_row = $db->prepare("SELECT crm_access FROM {$prefx}_adm_usr WHERE id=:id LIMIT 1");
$_crm_access_row->execute([':id' => (int)$user_id]);
$crm_access = $_crm_access_row->fetchColumn() ?: null;
// crm_access overrides role — even admins are restricted if crm_access is set

$crm_action = $t_mp[4] ?? 'leads';

switch ($crm_action) {
    case 'leads':
        include(_ADM_INCL.'/crm/views/leads.php');
        break;
    case 'my_leads':
        $crm_my_leads = true;
        include(_ADM_INCL.'/crm/views/leads.php');
        break;
    case 'lead':
        include(_ADM_INCL.'/crm/views/lead_detail.php');
        break;
    case 'calls':
        include(_ADM_INCL.'/crm/views/calls.php');
        break;
    case 'transaction':
        $crm_tx_view = 'transaction';
        if (!isset($_GET['dept'])) $_GET['dept'] = $crm_access ?? 'stock';
        if ($crm_access) $_GET['dept'] = $crm_access;
        include(_ADM_INCL.'/crm/views/transactions.php');
        break;
    case 'closed':
        $crm_tx_view = 'closed';
        if (!isset($_GET['dept'])) $_GET['dept'] = $crm_access ?? 'stock';
        if ($crm_access) $_GET['dept'] = $crm_access;
        include(_ADM_INCL.'/crm/views/transactions.php');
        break;
    case 'tx_archive':
        $crm_tx_view = 'transaction';
        if (!isset($_GET['dept'])) $_GET['dept'] = $crm_access ?? 'stock';
        if ($crm_access) $_GET['dept'] = $crm_access;
        include(_ADM_INCL.'/crm/views/tx_archive.php');
        break;
    case 'closed_archive':
        $crm_tx_view = 'closed';
        if (!isset($_GET['dept'])) $_GET['dept'] = $crm_access ?? 'stock';
        if ($crm_access) $_GET['dept'] = $crm_access;
        include(_ADM_INCL.'/crm/views/tx_archive.php');
        break;
    case 'junk':
        $_GET['view'] = 'junk';
        if (!isset($_GET['dept'])) $_GET['dept'] = $crm_access ?? 'stock';
        if ($crm_access) $_GET['dept'] = $crm_access;
        include(_ADM_INCL.'/crm/views/leads.php');
        break;
    case 'inbox':
        include(_ADM_INCL.'/crm/views/inbox.php');
        break;
    case 'inbox_chat':
        include(_ADM_INCL.'/crm/views/inbox_chat.php');
        break;
    case 'analytics':
        if (!crm_can_analytics($user_role, $user_id)) {
            echo '<div style="padding:2rem;color:#e2001a;">Acces restricționat.</div>';
            return;
        }
        include(_ADM_INCL.'/crm/views/analytics.php');
        break;
    case 'phones':
        if (!crm_can_settings($user_role, $user_id)) {
            echo '<div style="padding:2rem;color:#e2001a;">Acces restricționat — doar Administrator.</div>';
            return;
        }
        include(_ADM_INCL.'/crm/views/phones.php');
        break;
    case 'settings':
        if (!crm_can_settings($user_role, $user_id)) {
            echo '<div style="padding:2rem;color:#e2001a;">Acces restricționat — doar Administrator.</div>';
            return;
        }
        include(_ADM_INCL.'/crm/views/settings.php');
        break;
default:
        include(_ADM_INCL.'/crm/views/leads.php');
}
