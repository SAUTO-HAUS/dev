<?php defined( '_DOIT' ) or die( 'Restricted access' );

// Include RBAC system
require_once dirname(__DIR__) . '/include/rbac.php';
require_once dirname(__DIR__) . '/include/rbac_config.php';

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

// Create admin preferences table if it doesn't exist
try {
    $db->exec('CREATE TABLE IF NOT EXISTS '.$prefx.'_admin_preferences (
        admin_id INT PRIMARY KEY,
        last_role_selection VARCHAR(50),
        last_branch_selection INT,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
    )');
} catch (Exception $e) {
    // Table might already exist, continue
}

// Handle role updates
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_role'])) {
    $user_id_to_update = (int)$_POST['user_id'];
    $new_role = $_POST['role'];
    $new_branch_id = !empty($_POST['branch_id']) ? (int)$_POST['branch_id'] : null;
    
    try {
        // Update user role
        $pdo = $db->prepare('UPDATE '.$prefx.'_adm_usr SET role = ?, branch_id = ? WHERE id = ?');
        $pdo->execute([$new_role, $new_branch_id, $user_id_to_update]);
        
        // Save admin's last selections for future use
        $pdo = $db->prepare('INSERT INTO '.$prefx.'_admin_preferences (admin_id, last_role_selection, last_branch_selection) 
                           VALUES (?, ?, ?) 
                           ON DUPLICATE KEY UPDATE 
                           last_role_selection = VALUES(last_role_selection), 
                           last_branch_selection = VALUES(last_branch_selection)');
        $pdo->execute([$user_id, $new_role, $new_branch_id]);
        
        $success_message = 'Роль пользователя успешно обновлена!';
    } catch (Exception $e) {
        $error_message = 'Ошибка при обновлении роли: ' . $e->getMessage();
    }
}

// Get admin's last selections
$pdo = $db->prepare('SELECT last_role_selection, last_branch_selection FROM '.$prefx.'_admin_preferences WHERE admin_id = ?');
$pdo->execute([$user_id]);
$admin_preferences = $pdo->fetch(PDO::FETCH_ASSOC);

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
        case 'gordon': $role_display = 'Root Admin'; break;
        case 'admin': $role_display = 'Admin'; break;
        case 'publisher': $role_display = 'Publisher'; break;
        case 'publisher_limited': $role_display = 'Publisher-Limited'; break;
        default: $role_display = $user['type'] ?? 'Not assigned';
    }
    
    echo '<div style="margin-bottom: 15px;">Текущая роль: <strong>'.$role_display.'</strong></div>';
    
    // Role update form
    echo '<form method="post" style="border-top: 1px solid #eee; padding-top: 15px;">';
    echo '<input type="hidden" name="user_id" value="'.$user['id'].'">';
    
    // Get submitted values for this user if any (check if this user was just updated)
    $selected_role = '';
    $selected_branch = '';
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_role']) && (int)$_POST['user_id'] === $user['id']) {
        $selected_role = $_POST['role'] ?? '';
        $selected_branch = $_POST['branch_id'] ?? '';
    } else {
        // Load admin's saved preferences as default values
        if ($admin_preferences) {
            $selected_role = $admin_preferences['last_role_selection'] ?? '';
            $selected_branch = $admin_preferences['last_branch_selection'] ?? '';
        }
    }
    
    echo '<div style="margin-bottom: 10px;">';
    echo '<label>Новая роль:</label><br>';
    echo '<select name="role" style="width: 100%; padding: 8px; margin-top: 5px;" required>';
    echo '<option value=""'.($selected_role === '' ? ' selected' : '').'>-- Выберите новую роль --</option>';
    echo '<option value="gordon"'.($selected_role === 'gordon' ? ' selected' : '').'>Root Admin</option>';
    echo '<option value="admin"'.($selected_role === 'admin' ? ' selected' : '').'>Admin</option>';
    echo '<option value="publisher"'.($selected_role === 'publisher' ? ' selected' : '').'>Publisher</option>';
    echo '<option value="publisher_limited"'.($selected_role === 'publisher_limited' ? ' selected' : '').'>Publisher-Limited</option>';
    
    // JavaScript to preserve selected values after form submission
    if ($selected_role !== '' || $selected_branch !== '') {
        echo '<script>';
        echo 'setTimeout(function() {';
        echo '  var forms = document.querySelectorAll("form");';
        echo '  for (var i = 0; i < forms.length; i++) {';
        echo '    var hiddenInput = forms[i].querySelector("input[name=\"user_id\"][value=\"'.$user['id'].'\"]");';
        echo '    if (hiddenInput) {';
        if ($selected_role !== '') {
            echo '      var roleSelect = forms[i].querySelector("select[name=\"role\"]");';
            echo '      if (roleSelect) roleSelect.value = "'.$selected_role.'";';
        }
        if ($selected_branch !== '') {
            echo '      var branchSelect = forms[i].querySelector("select[name=\"branch_id\"]");';
            echo '      if (branchSelect) branchSelect.value = "'.$selected_branch.'";';
        }
        echo '      break;';
        echo '    }';
        echo '  }';
        echo '}, 100);';
        echo '</script>';
    }
    echo '</select>';
    echo '</div>';
    
    echo '<div style="margin-bottom: 10px;">';
    echo '<label>Филиал (для Publisher-Limited):</label><br>';
    echo '<select name="branch_id" style="width: 100%; padding: 8px; margin-top: 5px;">';
    echo '<option value=""'.($selected_branch === '' ? ' selected' : '').'>-- Выберите филиал (опционально) --</option>';
    foreach ($branches as $branch) {
        $branch_selected = (string)$selected_branch === (string)$branch['id'] ? ' selected' : '';
        echo '<option value="'.$branch['id'].'"'.$branch_selected.'>'.$branch['name'].'</option>';
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
echo '<li><strong>Root Admin:</strong> Полный доступ ко всем разделам, включая управление пользователями и системные настройки</li>';
echo '<li><strong>Admin:</strong> Почти полный доступ, кроме управления пользователями и системных настроек</li>';
echo '<li><strong>Publisher:</strong> Может заливать и редактировать автомобили, работает со всеми филиалами</li>';
echo '<li><strong>Publisher-Limited:</strong> Те же права что Publisher, но только для одного филиала</li>';
echo '</ul>';
echo '</div>';

?>
