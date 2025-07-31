// User management JavaScript
document.addEventListener("DOMContentLoaded", function() {
    // Handle button clicks
    document.addEventListener("click", function(e) {
        if (e.target.classList.contains("btn")) {
            const action = e.target.getAttribute("data-nm");
            const userItem = e.target.closest(".it");
            const userId = userItem.getAttribute("data-id");
            const userName = userItem.querySelector("[data-login]").textContent;
            const userLogin = userItem.querySelector("[data-login]").getAttribute("data-login");
            
            handleUserAction(action, userId, userName, userLogin);
        }
    });
    
    // Handle checkbox changes for active status
    document.addEventListener("change", function(e) {
        if (e.target.type === "checkbox" && e.target.closest(".btn[data-nm='act']")) {
            const userItem = e.target.closest(".it");
            const userId = userItem.getAttribute("data-id");
            const isActive = e.target.checked;
            
            updateUser(userId, "active", isActive);
        }
    });
});

function handleUserAction(action, userId, userName, userLogin) {
    switch(action) {
        case "nm":
            editUserName(userId, userName);
            break;
        case "lgn":
            editUserLogin(userId, userLogin);
            break;
        case "pass":
            changeUserPassword(userId, userName);
            break;
        case "del":
            deleteUser(userId, userName);
            break;
    }
}

function editUserName(userId, currentName) {
    const newName = prompt("Introduceți numele nou:", currentName);
    if (newName && newName !== currentName) {
        updateUser(userId, "name", newName);
    }
}

function editUserLogin(userId, currentLogin) {
    const newLogin = prompt("Introduceți login-ul nou:", currentLogin);
    if (newLogin && newLogin !== currentLogin) {
        updateUser(userId, "login", newLogin);
    }
}

function changeUserPassword(userId, userName) {
    const newPassword = prompt("Introduceți parola nouă pentru " + userName + ":");
    if (newPassword) {
        updateUser(userId, "password", newPassword);
    }
}

function deleteUser(userId, userName) {
    if (confirm("Sunteți sigur că doriți să ștergeți utilizatorul \"" + userName + "\"?")) {
        updateUser(userId, "delete", true);
    }
}

function updateUser(userId, field, value) {
    const formData = new FormData();
    formData.append("user_management_action", field);
    formData.append("user_id", userId);
    formData.append("user_value", value);
    
    fetch(window.location.href, {
        method: "POST",
        body: formData
    })
    .then(response => response.text())
    .then(data => {
        if (data.includes("success")) {
            location.reload();
        } else {
            alert("Eroare: " + data);
        }
    })
    .catch(error => {
        console.error("Error:", error);
        alert("A apărut o eroare la actualizarea utilizatorului.");
    });
}
