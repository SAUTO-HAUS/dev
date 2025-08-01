<?php
/**
 * RBAC Configuration Update
 * This file updates the existing admin system to support the new role-based access control
 */

// New role-based menu configuration
$rbac_admin_menu = [
    'gordon' => [
        'cars' => ['ctlg', 'detail', 'catalog'],
        'tyres' => ['ctlg', 'detail', 'catalog'],
        'seo' => ['ctlg'],
        'mail' => ['message', 'order', 'favorites', 'archive'],
        'docs' => ['create', 'ctlg'],
        'sett' => ['info', 'adm_usr', 'roles']
    ],
    'admin' => [
        'cars' => ['ctlg', 'detail', 'catalog'],
        'tyres' => ['ctlg', 'detail', 'catalog'],
        'seo' => ['ctlg'],
        'mail' => ['message', 'order', 'favorites', 'archive'],
        'docs' => ['create', 'ctlg'],
        'sett' => ['info']
    ],
    'publisher' => [
        'cars' => ['ctlg', 'detail', 'catalog'],
        'tyres' => ['ctlg', 'detail', 'catalog']
    ],
    'publisher_limited' => [
        'cars' => ['ctlg', 'detail', 'catalog'],
        'tyres' => ['ctlg', 'detail', 'catalog']
    ]
];

// Role permissions mapping
$rbac_permissions = [
    'gordon' => [
        'all' => true
    ],
    'admin' => [
        'cars' => ['create' => true, 'read' => true, 'update' => true, 'delete' => true],
        'tyres' => ['create' => true, 'read' => true, 'update' => true, 'delete' => true],
        'seo' => ['create' => true, 'read' => true, 'update' => true, 'delete' => true],
        'mail' => ['create' => true, 'read' => true, 'update' => true, 'delete' => true],
        'docs' => ['create' => true, 'read' => true, 'update' => true, 'delete' => true],
        'settings' => ['read' => true, 'update' => true],
        'all_branches' => true
    ],
    'publisher' => [
        'cars' => ['create' => true, 'read' => true, 'update' => true, 'delete' => false, 'set_unavailable' => true],
        'tyres' => ['create' => true, 'read' => true, 'update' => true, 'delete' => false],
        'all_branches' => true
    ],
    'publisher_limited' => [
        'cars' => ['create' => true, 'read' => true, 'update' => true, 'delete' => false, 'set_unavailable' => true],
        'tyres' => ['create' => true, 'read' => true, 'update' => true, 'delete' => false],
        'branch_limited' => true
    ]
];

/**
 * Check if user has permission for specific action
 */
function rbac_has_permission($user_role, $module, $action = 'read') {
    global $rbac_permissions;
    
    // Gordon has all permissions
    if ($user_role === 'gordon' || (isset($rbac_permissions[$user_role]['all']) && $rbac_permissions[$user_role]['all'])) {
        return true;
    }
    
    // Check module-specific permissions
    if (isset($rbac_permissions[$user_role][$module])) {
        $module_perms = $rbac_permissions[$user_role][$module];
        
        if ($module_perms === true) {
            return true;
        }
        
        if (is_array($module_perms) && isset($module_perms[$action])) {
            return $module_perms[$action];
        }
    }
    
    return false;
}

/**
 * Check if user can access specific branch
 */
function rbac_can_access_branch($user_role, $user_branch_id, $target_branch_id) {
    global $rbac_permissions;
    
    if ($user_role === 'gordon') {
        return true;
    }
    
    if (isset($rbac_permissions[$user_role]['all_branches']) && $rbac_permissions[$user_role]['all_branches']) {
        return true;
    }
    
    if (isset($rbac_permissions[$user_role]['branch_limited']) && $rbac_permissions[$user_role]['branch_limited']) {
        return $user_branch_id == $target_branch_id;
    }
    
    return true;
}

/**
 * Get admin menu based on user role
 */
function rbac_get_admin_menu($user_role) {
    global $rbac_admin_menu;
    
    if (isset($rbac_admin_menu[$user_role])) {
        return $rbac_admin_menu[$user_role];
    }
    
    return [];
}

/**
 * Update existing admin menu to use RBAC if new role is set
 */
function rbac_update_admin_menu($user_type, $user_role = null) {
    global $admin_menu_dev1, $rbac_admin_menu;
    
    // If user has new role, use RBAC menu
    if ($user_role && isset($rbac_admin_menu[$user_role])) {
        return $rbac_admin_menu[$user_role];
    }
    
    // Fallback to old system
    if (isset($admin_menu_dev1[$user_type])) {
        return $admin_menu_dev1[$user_type];
    }
    
    return [];
}

?>
