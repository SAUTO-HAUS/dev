<?php
/**
 * RBAC Configuration Update
 * This file updates the existing admin system to support the new role-based access control
 */

// New role-based menu configuration
$rbac_admin_menu = [
    'gordon' => [
        'cars' => ['add', 'ctlg'],
        'tyres' => ['ctlg'],
        'seo' => ['ctlg'],
        'mail' => ['message', 'order', 'favorites', 'archive'],
        'docs' => ['create', 'ctlg'],
        'sett' => ['info', 'adm_usr', 'roles', 'phone_config']
    ],
    'admin' => [
        'cars' => ['add', 'ctlg'],
        'tyres' => ['ctlg'],
        'seo' => ['ctlg'],
        'mail' => ['message', 'order', 'favorites', 'archive'],
        'docs' => ['create', 'ctlg'],
        'sett' => ['info']
    ],
    'publisher' => [
        'cars' => ['add'],
        'docs' => ['create', 'ctlg']
        // No access to SEO, Mail, Settings, Cars catalog as per business requirements
    ],
    'publisher_limited' => [
        'cars' => ['add'],
        'docs' => ['create', 'ctlg']
        // No access to SEO, Mail, Settings, Cars catalog + branch limited access
    ]
];

// Role permissions mapping - Updated to match business requirements
$rbac_permissions = [
    // Gordon (Суперадминистратор) - Полный доступ ко всем разделам
    'gordon' => [
        'all' => true,
        'user_management' => true,
        'role_management' => true,
        'system_settings' => true,
        'all_branches' => true,
        'cars' => ['create' => true, 'read' => true, 'update' => true, 'delete' => true, 'restore' => true],
        'tyres' => ['create' => true, 'read' => true, 'update' => true, 'delete' => true, 'restore' => true],
        'seo' => ['create' => true, 'read' => true, 'update' => true, 'delete' => true],
        'mail' => ['create' => true, 'read' => true, 'update' => true, 'delete' => true],
        'docs' => ['create' => true, 'read' => true, 'update' => true, 'delete' => true],
        'settings' => ['read' => true, 'update' => true]
    ],
    // Admin (Администратор) - Почти полный доступ, кроме управления пользователями
    'admin' => [
        'user_management' => false,
        'role_management' => false,
        'system_settings' => false,
        'all_branches' => true,
        'cars' => ['create' => true, 'read' => true, 'update' => true, 'delete' => true, 'restore' => true],
        'tyres' => ['create' => true, 'read' => true, 'update' => true, 'delete' => true, 'restore' => true],
        'seo' => ['create' => true, 'read' => true, 'update' => true, 'delete' => true],
        'mail' => ['create' => true, 'read' => true, 'update' => true, 'delete' => true],
        'docs' => ['create' => true, 'read' => true, 'update' => true, 'delete' => true],
        'settings' => ['read' => true, 'update' => false]
    ],
    // Publisher (Публикатор) - Can only add new cars, no catalog management, full docs access to ALL branches
    'publisher' => [
        'user_management' => false,
        'role_management' => false,
        'system_settings' => false,
        'all_branches' => true,
        'branch_limited' => false,
        'cars' => ['create' => true, 'read' => false, 'update' => false, 'delete' => false, 'restore' => false],
        'seo' => ['read' => false],
        'mail' => ['read' => false],
        'docs' => ['create' => true, 'read' => true, 'update' => true, 'delete' => true],
        'settings' => ['read' => false]
    ],
    // Publisher-Limited (Публикатор Филиал) - Can only add new cars, no catalog management, docs access limited to own branch
    'publisher_limited' => [
        'user_management' => false,
        'role_management' => false,
        'system_settings' => false,
        'branch_limited' => true,
        'cars' => ['create' => true, 'read' => false, 'update' => false, 'delete' => false, 'restore' => false],
        'seo' => ['read' => false],
        'mail' => ['read' => false],
        'docs' => ['create' => true, 'read' => true, 'update' => true, 'delete' => true],
        'settings' => ['read' => false]
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
    
    // Check special permissions first
    if ($module === 'user_management' || $module === 'role_management' || $module === 'system_settings') {
        return isset($rbac_permissions[$user_role][$module]) && $rbac_permissions[$user_role][$module] === true;
    }
    
    // Check module-specific permissions
    if (isset($rbac_permissions[$user_role][$module])) {
        $module_perms = $rbac_permissions[$user_role][$module];
        
        if ($module_perms === true) {
            return true;
        }
        
        if ($module_perms === false) {
            return false;
        }
        
        if (is_array($module_perms) && isset($module_perms[$action])) {
            return $module_perms[$action];
        }
    }
    
    return false;
}

/**
 * Check if user can manage other users (only Gordon)
 */
function rbac_can_manage_users($user_role) {
    return rbac_has_permission($user_role, 'user_management');
}

/**
 * Check if user can manage roles (only Gordon)
 */
function rbac_can_manage_roles($user_role) {
    return rbac_has_permission($user_role, 'role_management');
}

/**
 * Check if user can access system settings (only Gordon)
 */
function rbac_can_access_system_settings($user_role) {
    return rbac_has_permission($user_role, 'system_settings');
}

/**
 * Check if user can access specific branch
 */
function rbac_can_access_branch($user_role, $user_branch_id, $target_branch_id) {
    global $rbac_permissions;
    
    // Gordon has access to all branches
    if ($user_role === 'gordon') {
        return true;
    }
    
    // Check if role has access to all branches (admin, publisher)
    if (isset($rbac_permissions[$user_role]['all_branches']) && $rbac_permissions[$user_role]['all_branches']) {
        return true;
    }
    
    // Publisher-Limited has access only to their branch
    if (isset($rbac_permissions[$user_role]['branch_limited']) && $rbac_permissions[$user_role]['branch_limited']) {
        return $user_branch_id == $target_branch_id;
    }
    
    // Default: allow access (for backward compatibility)
    return true;
}

/**
 * RBAC Admin Menu Configuration
 * Maps roles to their allowed admin menu sections
 */
$rbac_admin_menu = [
    'gordon' => [
        'cars' => ['add', 'ctlg'],
        'tyres' => ['ctlg'],
        'seo' => ['ctlg'],
        'mail' => ['message', 'order', 'favorites', 'archive'],
        'docs' => ['add', 'ctlg'],
        'sett' => ['info', 'adm_usr', 'roles', 'phone_config']
    ],
    'admin' => [
        'cars' => ['add', 'ctlg'],
        'tyres' => ['ctlg'],
        'seo' => ['ctlg'],
        'mail' => ['message', 'order', 'favorites', 'archive'],
        'docs' => ['add', 'ctlg'],
        'sett' => ['info']
    ],
    'publisher' => [
        'cars' => ['add'], 
        'docs' => ['add', 'ctlg']
    ],
    'publisher_limited' => [
        'cars' => ['add'], 
        'docs' => ['add', 'ctlg']
    ]
];

// Additional allowed actions for internal routing (not shown in menu)
$rbac_internal_actions = [
    'gordon' => [
        'cars' => ['add', 'create', 'detail', 'ctlg'],
        'tyres' => ['add', 'create', 'detail', 'ctlg'],
        'seo' => ['add', 'create', 'detail', 'ctlg'],
        'mail' => ['message', 'order', 'favorites', 'archive'],
        'docs' => ['add', 'create', 'detail', 'ctlg'],
        'sett' => ['info', 'adm_usr', 'roles', 'phone_config']
    ],
    'admin' => [
        'cars' => ['add', 'create', 'detail', 'ctlg'],
        'tyres' => ['add', 'create', 'detail', 'ctlg'],
        'seo' => ['add', 'create', 'detail', 'ctlg'],
        'mail' => ['message', 'order', 'favorites', 'archive'],
        'docs' => ['add', 'create', 'detail', 'ctlg'],
        'sett' => ['info']
    ],
    'publisher' => [
        'cars' => ['add', 'create', 'detail'],
        'docs' => ['add', 'create', 'detail', 'ctlg']
    ],
    'publisher_limited' => [
        'cars' => ['add', 'create', 'detail'],
        'docs' => ['add', 'create', 'detail', 'ctlg']
    ]
];

/**
 * Get branch filter SQL condition for publisher_limited users
 * @param string $user_role Current user role
 * @param int $user_branch_id Current user's branch ID
 * @param string $table_alias Table alias for the branch_id column (optional)
 * @return string SQL WHERE condition or empty string
 */
function rbac_get_branch_filter_sql($user_role, $user_branch_id, $table_alias = '') {
    global $rbac_permissions;
    
    // No filtering needed for gordon and admin
    if ($user_role === 'gordon' || 
        (isset($rbac_permissions[$user_role]['all_branches']) && $rbac_permissions[$user_role]['all_branches'])) {
        return '';
    }
    
    // Publisher-Limited needs branch filtering
    if (isset($rbac_permissions[$user_role]['branch_limited']) && $rbac_permissions[$user_role]['branch_limited']) {
        $column = $table_alias ? $table_alias . '.branch_id' : 'branch_id';
        return " AND {$column} = " . (int)$user_branch_id;
    }
    
    return '';
}

/**
 * Check if user can delete/restore items
 */
function rbac_can_delete($user_role, $module) {
    return rbac_has_permission($user_role, $module, 'delete');
}

/**
 * Check if user can set items as unavailable (Publishers)
 */
function rbac_can_set_unavailable($user_role, $module) {
    return rbac_has_permission($user_role, $module, 'set_unavailable');
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
