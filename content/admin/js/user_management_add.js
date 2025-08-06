// Add User functionality
function showAddUserForm() {
    document.getElementById('addUserForm').classList.add('show');
    document.getElementById('showAddUserBtn').style.display = 'none';
}

function hideAddUserForm() {
    document.getElementById('addUserForm').classList.remove('show');
    document.getElementById('showAddUserBtn').style.display = 'inline-block';
    // Clear form
    document.getElementById('userName').value = '';
    document.getElementById('userLogin').value = '';
    document.getElementById('userPassword').value = '';
    document.getElementById('userRole').value = 'publisher';
    document.getElementById('userBranch').value = '';
}

function submitAddUser() {
    var name = document.getElementById('userName').value.trim();
    var login = document.getElementById('userLogin').value.trim();
    var password = document.getElementById('userPassword').value;
    var role = document.getElementById('userRole').value;
    var branch = document.getElementById('userBranch').value;
    
    if (!name || !login || !password) {
        alert('Пожалуйста, заполните все обязательные поля (Имя, Логин, Пароль)');
        return;
    }
    
    if (password.length < 6) {
        alert('Пароль должен содержать минимум 6 символов');
        return;
    }
    
    var formData = new FormData();
    formData.append('user_management_action', 'add_user');
    formData.append('user_name', name);
    formData.append('user_login', login);
    formData.append('user_password', password);
    formData.append('user_role', role);
    formData.append('user_branch', branch);
    
    fetch(window.location.href, {
        method: 'POST',
        body: formData
    })
    .then(function(response) { return response.text(); })
    .then(function(data) {
        if (data.indexOf('success') !== -1) {
            alert('Пользователь успешно добавлен!');
            location.reload();
        } else {
            alert('Ошибка: ' + data);
        }
    })
    .catch(function(error) {
        alert('Произошла ошибка при добавлении пользователя.');
    });
}
