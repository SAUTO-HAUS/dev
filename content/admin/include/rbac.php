<?php
/**
 * Role-Based Access Control (RBAC) System
 */

class RBAC {
    private $db;
    private $prefx;
    private $user_id;
    private $user_role;
    private $user_branch_id;
    private $user_permissions;
    
    public function __construct($db, $prefx, $user_id = null) {
        $this->db = $db;
        $this->prefx = $prefx;
        $this->user_id = $user_id;
        
        if ($user_id) {
            $this->loadUserPermissions();
        }
    }
    
    /**
     * Load user permissions and role data
     */
    private function loadUserPermissions() {
        $pdo = $this->db->prepare('SELECT role, branch_id, permissions FROM '.$this->prefx.'_adm_usr WHERE id = :id AND act = 1');
        $pdo->execute(['id' => $this->user_id]);
        $user = $pdo->fetch(PDO::FETCH_ASSOC);
        
        if ($user) {
            $this->user_role = $user['role'];
            $this->user_branch_id = $user['branch_id'];
            
            // Load role permissions
            $role_permissions = $this->getRolePermissions($this->user_role);
            
            // Merge with user-specific permissions if any
            $user_specific = $user['permissions'] ? json_decode($user['permissions'], true) : [];
            $this->user_permissions = array_merge($role_permissions, $user_specific);
        }
    }
    
    /**
     * Get permissions for a specific role
     */
    public function getRolePermissions($role_code) {
        $pdo = $this->db->prepare('SELECT permissions FROM '.$this->prefx.'_roles WHERE code = :code AND active = 1');
        $pdo->execute(['code' => $role_code]);
        $role = $pdo->fetch(PDO::FETCH_ASSOC);
        
        if ($role && $role['permissions']) {
            return json_decode($role['permissions'], true);
        }
        
        return [];
    }
    
    /**
     * Check if user has permission for a specific action
     */
    public function hasPermission($module, $action = 'read') {
        // Gordon has all permissions
        if ($this->user_role === 'gordon' || (isset($this->user_permissions['all']) && $this->user_permissions['all'])) {
            return true;
        }
        
        // Check module-specific permissions
        if (isset($this->user_permissions[$module])) {
            $module_perms = $this->user_permissions[$module];
            
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
    public function canAccessBranch($branch_id) {
        if ($this->user_role === 'gordon') {
            return true;
        }
        
        if (isset($this->user_permissions['all_branches']) && $this->user_permissions['all_branches']) {
            return true;
        }
        
        if (isset($this->user_permissions['branch_limited']) && $this->user_permissions['branch_limited']) {
            return $this->user_branch_id == $branch_id;
        }
        
        return true;
    }
    
    /**
     * Get admin menu based on user permissions
     */
    public function getAdminMenu() {
        $menu = [];
        
        if ($this->hasPermission('cars', 'read')) {
            $menu['cars'] = ['ctlg'];
            if ($this->hasPermission('cars', 'create')) {

            }
        }
        
        if ($this->hasPermission('tyres', 'read')) {
            $menu['tyres'] = ['ctlg'];
            if ($this->hasPermission('tyres', 'create')) {

            }
        }
        
        if ($this->hasPermission('seo', 'read')) {
            $menu['seo'] = ['ctlg'];
        }
        
        if ($this->hasPermission('mail', 'read')) {
            $menu['mail'] = ['message', 'order', 'favorites', 'archive'];
        }
        
        if ($this->hasPermission('docs', 'read')) {
            $menu['docs'] = ['ctlg'];
            if ($this->hasPermission('docs', 'create')) {
                $menu['docs'][] = 'create';
            }
        }
        
        if ($this->hasPermission('settings', 'read')) {
            $menu['sett'] = ['info'];
            
            if ($this->user_role === 'gordon') {
                $menu['sett'][] = 'adm_usr';
                $menu['sett'][] = 'roles';
            }
        }
        
        return $menu;
    }
    
    public function getUserRole() {
        return $this->user_role;
    }
    
    public function getUserBranchId() {
        return $this->user_branch_id;
    }
}
?>
