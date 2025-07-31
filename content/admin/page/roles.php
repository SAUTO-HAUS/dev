<?php defined( '_DOIT' ) or die( 'Restricted access' );

// Include RBAC system (with fallback)
if (file_exists('./include/rbac.php')) {
    require_once './include/rbac.php';
    require_once './include/rbac_config.php';
    $rbac_available = true;
} else {
    $rbac_available = false;
    // Fallback for missing RBAC system
    class RBAC {
        public function __construct($db, $prefx, $user_id = null) {}
        public function getUserRole() { return 'gordon'; }
    }
}

// Get user session info
if (isset($_COOKIE['sess'])) {
    $sess_parts = explode('|', $_COOKIE['sess']);
    if (count($sess_parts) >= 2) {
        $user_id = (int)$sess_parts[0];
    }
}

if (!isset($user_id) || !$user_id) {
    echo '<div style="color: red; padding: 20px;">Eroare: Nu s-a putut identifica utilizatorul. Vă rugăm să vă autentificați din nou.</div>';
    return;
}

// Get user role from database
$pdo = $db->prepare('SELECT role FROM '.$prefx.'_adm_usr WHERE id = ?');
$pdo->execute([$user_id]);
$user_data = $pdo->fetch(PDO::FETCH_ASSOC);
$user_role = $user_data['role'] ?? '';

$rbac = new RBAC($db, $prefx, $user_id);

// Only Gordon can access role management
if ($user_role !== 'gordon') {
    echo '<div style="color: red; padding: 20px;">Доступ запрещен. Только суперадминистратор может управлять ролями.</div>';
    return;
}

echo '<h2>🔐 Управление ролями пользователей</h2>';

// Handle role updates
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_role'])) {
    $user_id_to_update = (int)$_POST['user_id'];
    $new_role = $_POST['role'];
    $new_branch_id = !empty($_POST['branch_id']) ? (int)$_POST['branch_id'] : null;
    
    $pdo = $db->prepare('UPDATE '.$prefx.'_adm_usr SET role = :role, branch_id = :branch_id WHERE id = :id');
    $success = $pdo->execute([
        'role' => $new_role,
        'branch_id' => $new_branch_id,
        'id' => $user_id_to_update
    ]);
    
    if ($success) {
        echo '<div style="color: green; padding: 10px; background: #d4edda; border-radius: 4px; margin: 10px 0;">Роль пользователя успешно обновлена!</div>';
    }
}

// Get all users
$pdo = $db->prepare('SELECT * FROM '.$prefx.'_adm_usr ORDER BY name ASC');
$pdo->execute();
$users = $pdo->fetchAll(PDO::FETCH_ASSOC);

// Get all branches
$pdo = $db->prepare('SELECT * FROM '.$prefx.'_branches ORDER BY name ASC');
$pdo->execute();
$branches = $pdo->fetchAll(PDO::FETCH_ASSOC);

echo '<div style="display: grid; gap: 20px; grid-template-columns: repeat(auto-fill, minmax(400px, 1fr));">';

foreach ($users as $user) {
    $is_current = isset($_COOKIE['usr']) && $_COOKIE['usr'] == $user['login'];
    
    echo '<div style="border: 1px solid #ddd; padding: 20px; border-radius: 8px; background: white;">';
    echo '<div style="display: flex; align-items: center; margin-bottom: 15px;">';
    echo '<div style="width: 12px; height: 12px; border-radius: 50%; margin-right: 10px; background: '.($is_current ? '#007bff' : ($user['act'] == 1 ? '#28a745' : '#dc3545')).';"></div>';
    echo '<strong>'.$user['name'].'</strong> <span style="color: #666; margin-left: 10px;">'.$user['login'].'</span>';
    echo '</div>';
    
    // Current role display
    $role_display = '';
    switch ($user['role']) {
        case 'gordon': $role_display = 'Gordon (Суперадмин)'; break;
        case 'admin': $role_display = 'Admin (Администратор)'; break;
        case 'publisher': $role_display = 'Publisher (Публикатор)'; break;
        case 'publisher_limited': $role_display = 'Publisher-Limited (Публикатор Филиал)'; break;
        default: $role_display = $user['type'] ?? 'Не назначена';
    }
    
    echo '<div style="margin-bottom: 15px;">Текущая роль: <strong>'.$role_display.'</strong></div>';
    
    // Role update form
    echo '<form method="post" style="border-top: 1px solid #eee; padding-top: 15px;">';
    echo '<input type="hidden" name="user_id" value="'.$user['id'].'">';
    
    echo '<div style="margin-bottom: 10px;">';
    echo '<label>Новая роль:</label><br>';
    echo '<select name="role" style="width: 100%; padding: 8px; margin-top: 5px;">';
    echo '<option value="gordon"'.($user['role'] == 'gordon' ? ' selected' : '').'>Gordon (Суперадминистратор)</option>';
    echo '<option value="admin"'.($user['role'] == 'admin' ? ' selected' : '').'>Admin (Администратор)</option>';
    echo '<option value="publisher"'.($user['role'] == 'publisher' ? ' selected' : '').'>Publisher (Публикатор)</option>';
    echo '<option value="publisher_limited"'.($user['role'] == 'publisher_limited' ? ' selected' : '').'>Publisher-Limited (Публикатор Филиал)</option>';
    echo '</select>';
    echo '</div>';
    
    echo '<div style="margin-bottom: 10px;">';
    echo '<label>Филиал (для Publisher-Limited):</label><br>';
    echo '<select name="branch_id" style="width: 100%; padding: 8px; margin-top: 5px;">';
    echo '<option value="">Все филиалы</option>';
    foreach ($branches as $branch) {
        $selected = $user['branch_id'] == $branch['id'] ? ' selected' : '';
        echo '<option value="'.$branch['id'].'"'.$selected.'>'.$branch['name'].'</option>';
    }
    echo '</select>';
    echo '</div>';
    
    echo '<button type="submit" name="update_role" style="background: var(--clr); color: white; border: none; padding: 8px 16px; border-radius: 4px; cursor: pointer;">Обновить роль</button>';
    echo '</form>';
    
    echo '</div>';
}

echo '</div>';

// Role descriptions
echo '<div style="margin-top: 40px; padding: 20px; background: #f8f9fa; border-radius: 8px;">';
echo '<h3>Описание ролей:</h3>';
echo '<ul>';
echo '<li><strong>Gordon (Суперадминистратор):</strong> Полный доступ ко всем разделам, включая управление пользователями и системные настройки</li>';
echo '<li><strong>Admin (Администратор):</strong> Почти полный доступ, кроме управления пользователями и системных настроек</li>';
echo '<li><strong>Publisher (Публикатор):</strong> Может заливать и редактировать автомобили, работает со всеми филиалами</li>';
echo '<li><strong>Publisher-Limited (Публикатор Филиал):</strong> Те же права что Publisher, но только для одного филиала</li>';
echo '</ul>';
echo '</div>';

?>
