<?php
/**
 * Example implementation of branch filtering for publisher_limited users
 * This shows how to integrate the rbac_get_branch_filter_sql() function
 * into your existing queries for cars and docs
 */

// Include RBAC functions
require_once 'rbac_config.php';

/**
 * Example: Filter cars query for publisher_limited users
 */
function getCarsWithBranchFilter($user_role, $user_branch_id) {
    global $db, $prefx;
    
    // Base query
    $sql = "SELECT * FROM {$prefx}_cars WHERE status = 'active'";
    
    // Add branch filter for publisher_limited
    $branch_filter = rbac_get_branch_filter_sql($user_role, $user_branch_id);
    $sql .= $branch_filter;
    
    // Add other conditions as needed
    $sql .= " ORDER BY created_at DESC";
    
    $pdo = $db->prepare($sql);
    $pdo->execute();
    return $pdo->fetchAll();
}

/**
 * Example: Filter docs query for publisher_limited users
 */
function getDocsWithBranchFilter($user_role, $user_branch_id) {
    global $db, $prefx;
    
    // Base query
    $sql = "SELECT * FROM {$prefx}_docs WHERE status = 'active'";
    
    // Add branch filter for publisher_limited
    $branch_filter = rbac_get_branch_filter_sql($user_role, $user_branch_id);
    $sql .= $branch_filter;
    
    // Add other conditions as needed
    $sql .= " ORDER BY created_at DESC";
    
    $pdo = $db->prepare($sql);
    $pdo->execute();
    return $pdo->fetchAll();
}

/**
 * Example: Usage in a page (like docs.php or cars/catalog.php)
 */
function exampleUsageInPage() {
    // Get current user info (this would be from session/auth)
    $user_role = $_SESSION['user_role'] ?? 'publisher_limited';
    $user_branch_id = $_SESSION['user_branch_id'] ?? 1;
    
    // For cars
    $cars = getCarsWithBranchFilter($user_role, $user_branch_id);
    
    // For docs
    $docs = getDocsWithBranchFilter($user_role, $user_branch_id);
    
    // Display results
    foreach ($cars as $car) {
        // Only cars from user's branch will be shown for publisher_limited
        echo "Car ID: {$car['id']}, Branch: {$car['branch_id']}<br>";
    }
    
    foreach ($docs as $doc) {
        // Only docs from user's branch will be shown for publisher_limited
        echo "Doc ID: {$doc['id']}, Branch: {$doc['branch_id']}<br>";
    }
}

/**
 * Example: Integration with existing WHERE conditions
 */
function getFilteredDataWithExistingConditions($user_role, $user_branch_id, $additional_where = '') {
    global $db, $prefx;
    
    // Start with base WHERE clause
    $where_conditions = "WHERE status = 'active'";
    
    // Add additional conditions if provided
    if ($additional_where) {
        $where_conditions .= " AND " . $additional_where;
    }
    
    // Add branch filter
    $branch_filter = rbac_get_branch_filter_sql($user_role, $user_branch_id);
    $where_conditions .= $branch_filter;
    
    $sql = "SELECT * FROM {$prefx}_cars {$where_conditions} ORDER BY created_at DESC";
    
    $pdo = $db->prepare($sql);
    $pdo->execute();
    return $pdo->fetchAll();
}

/**
 * How the function works:
 * 
 * For gordon/admin roles:
 * - Returns empty string (no filtering)
 * - Sees all cars/docs from all branches
 * 
 * For publisher role:
 * - Returns empty string (has all_branches = true)
 * - Sees all cars/docs from all branches
 * 
 * For publisher_limited role:
 * - Returns " AND branch_id = X" where X is user's branch_id
 * - Sees only cars/docs from their assigned branch
 */
?>
