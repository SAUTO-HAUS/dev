<?php

if ( ( session_id()=='' || !isset($_SESSION) ) ){ session_start(); }

define('ACCESS_TOKEN', 'xK9mP2nQ7wR4sL8vT3yU6hJ5gF1dA0z');

if (!isset($_GET['token']) || $_GET['token'] !== ACCESS_TOKEN) {
    header('HTTP/1.1 404 Not Found');
    die('<!DOCTYPE html><html><head><title>404 Not Found</title></head><body><h1>404 Not Found</h1><p>The page you are looking for could not be found.</p></body></html>');
}

define('_DOIT', 1);
define('_DEFAULT', 'content/default');

require_once 'environment.php';
require_once (_DEFAULT.'/defines.php');
require_once (_DEFAULT.'/functions.php');
require_once (_DEFAULT.'/config.php');

$sql_host = SQL_HOST;
$sql_db = SQL_DB;
$sql_user = SQL_USER;
$sql_pass = SQL_PASS;
$sql_charset = SQL_CHARSET;

$dsn = "mysql:host=$sql_host;dbname=$sql_db;charset=$sql_charset";
$opt = [
    PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    PDO::ATTR_EMULATE_PREPARES   => false,
];

try {
    $pdo = new PDO($dsn, $sql_user, $sql_pass, $opt);
} catch (PDOException $e) {
    die("Database connection failed: " . $e->getMessage());
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    header('Content-Type: application/json');
    
    $action = $_POST['action'];
    
    try {
        if ($action === 'get_brands') {
            $stmt = $pdo->query("SELECT DISTINCT br, br_nm FROM gh3sp_car_list ORDER BY br_nm ASC");
            $brands = $stmt->fetchAll(PDO::FETCH_ASSOC);
            echo json_encode(['success' => true, 'brands' => $brands]);
            
        } elseif ($action === 'get_models') {
            $brand = $_POST['brand'] ?? '';
            $stmt = $pdo->prepare("SELECT id, mo, mo_nm FROM gh3sp_car_list WHERE br = ? ORDER BY mo_nm ASC");
            $stmt->execute([$brand]);
            $models = $stmt->fetchAll(PDO::FETCH_ASSOC);
            echo json_encode(['success' => true, 'models' => $models]);
            
        } elseif ($action === 'get_all') {
            $stmt = $pdo->query("SELECT id, br, mo, br_nm, mo_nm FROM gh3sp_car_list ORDER BY br_nm ASC, mo_nm ASC");
            $cars = $stmt->fetchAll(PDO::FETCH_ASSOC);
            echo json_encode(['success' => true, 'cars' => $cars]);
            
        } elseif ($action === 'add_car') {
            $br = trim($_POST['br'] ?? '');
            $mo = trim($_POST['mo'] ?? '');
            $br_nm = trim($_POST['br_nm'] ?? '');
            $mo_nm = trim($_POST['mo_nm'] ?? '');
            
            if (empty($br) || empty($mo) || empty($br_nm) || empty($mo_nm)) {
                echo json_encode(['success' => false, 'error' => 'Toate câmpurile sunt obligatorii']);
                exit;
            }
            
            $stmt = $pdo->prepare("SELECT id FROM gh3sp_car_list WHERE br = ? AND mo = ?");
            $stmt->execute([$br, $mo]);
            if ($stmt->fetch()) {
                echo json_encode(['success' => false, 'error' => 'Această combinație de marcă și model există deja']);
                exit;
            }
            
            $stmt = $pdo->prepare("INSERT INTO gh3sp_car_list (br, mo, br_nm, mo_nm) VALUES (?, ?, ?, ?)");
            $stmt->execute([$br, $mo, $br_nm, $mo_nm]);
            
            echo json_encode(['success' => true, 'message' => 'Adăugat cu succes', 'id' => $pdo->lastInsertId()]);
            
        } elseif ($action === 'delete_car') {
            $id = intval($_POST['id'] ?? 0);
            
            if ($id <= 0) {
                echo json_encode(['success' => false, 'error' => 'ID invalid']);
                exit;
            }
            
            $stmt = $pdo->prepare("DELETE FROM gh3sp_car_list WHERE id = ?");
            $stmt->execute([$id]);
            
            echo json_encode(['success' => true, 'message' => 'Șters cu succes']);
            
        } elseif ($action === 'update_car') {
            $id = intval($_POST['id'] ?? 0);
            $br = trim($_POST['br'] ?? '');
            $mo = trim($_POST['mo'] ?? '');
            $br_nm = trim($_POST['br_nm'] ?? '');
            $mo_nm = trim($_POST['mo_nm'] ?? '');
            
            if ($id <= 0 || empty($br) || empty($mo) || empty($br_nm) || empty($mo_nm)) {
                echo json_encode(['success' => false, 'error' => 'Toate câmpurile sunt obligatorii']);
                exit;
            }
            
            $stmt = $pdo->prepare("SELECT id FROM gh3sp_car_list WHERE br = ? AND mo = ? AND id != ?");
            $stmt->execute([$br, $mo, $id]);
            if ($stmt->fetch()) {
                echo json_encode(['success' => false, 'error' => 'Această combinație de marcă și model există deja']);
                exit;
            }
            
            $stmt = $pdo->prepare("UPDATE gh3sp_car_list SET br = ?, mo = ?, br_nm = ?, mo_nm = ? WHERE id = ?");
            $stmt->execute([$br, $mo, $br_nm, $mo_nm, $id]);
            
            echo json_encode(['success' => true, 'message' => 'Actualizat cu succes']);
            
        } else {
            echo json_encode(['success' => false, 'error' => 'Acțiune necunoscută']);
        }
        
    } catch (PDOException $e) {
        echo json_encode(['success' => false, 'error' => 'Eroare bază de date: ' . $e->getMessage()]);
    }
    
    exit;
}
?>
<!DOCTYPE html>
<html lang="ro">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gestionare Mărci și Modele Auto</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, 'Helvetica Neue', Arial, sans-serif;
            background: linear-gradient(135deg, #87CEEB 0%, #4682B4 100%);
            min-height: 100vh;
            padding: 40px 20px;
        }
        
        .container {
            max-width: 1200px;
            margin: 0 auto;
            background: white;
            border-radius: 16px;
            box-shadow: 0 20px 60px rgba(0,0,0,0.3);
            padding: 40px;
        }
        
        h1 {
            color: #1a202c;
            margin-bottom: 10px;
            font-size: 32px;
            font-weight: 700;
            letter-spacing: -0.5px;
        }
        
        .subtitle {
            color: #718096;
            font-size: 16px;
            margin-bottom: 40px;
        }
        
        .actions {
            margin-bottom: 30px;
            display: flex;
            gap: 10px;
            flex-wrap: wrap;
        }
        
        .btn {
            padding: 12px 24px;
            border: none;
            border-radius: 8px;
            cursor: pointer;
            font-size: 14px;
            font-weight: 600;
            transition: all 0.3s;
            box-shadow: 0 2px 8px rgba(0,0,0,0.1);
        }
        
        .btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(0,0,0,0.2);
        }
        
        .btn:active {
            transform: translateY(0);
        }
        
        .btn-primary {
            background: linear-gradient(135deg, #4682B4 0%, #6495ED 100%);
            color: white;
        }
        
        .btn-primary:hover {
            background: linear-gradient(135deg, #6495ED 0%, #4682B4 100%);
        }
        
        .btn-success {
            background: #28a745;
            color: white;
        }
        
        .btn-success:hover {
            background: #218838;
        }
        
        .btn-danger {
            background: #dc3545;
            color: white;
        }
        
        .btn-danger:hover {
            background: #c82333;
        }
        
        .btn-secondary {
            background: #6c757d;
            color: white;
        }
        
        .btn-secondary:hover {
            background: #5a6268;
        }
        
        .search-box {
            margin-bottom: 20px;
        }
        
        .search-box input {
            width: 100%;
            max-width: 500px;
            padding: 14px 20px;
            border: 2px solid #e2e8f0;
            border-radius: 10px;
            font-size: 15px;
            transition: all 0.3s;
        }
        
        .search-box input:focus {
            outline: none;
            border-color: #0ea5e9;
            box-shadow: 0 0 0 3px rgba(14, 165, 233, 0.1);
        }
        
        .brand-card {
            background: white;
            border: 2px solid #e2e8f0;
            border-radius: 12px;
            margin-bottom: 16px;
            overflow: hidden;
            transition: all 0.3s;
        }
        
        .brand-card:hover {
            border-color: #cbd5e0;
            box-shadow: 0 4px 12px rgba(0,0,0,0.08);
        }
        
        .brand-header {
            background: #f7fafc;
            padding: 20px 24px;
            cursor: pointer;
            display: flex;
            justify-content: space-between;
            align-items: center;
            transition: all 0.3s;
            border-bottom: 2px solid transparent;
        }
        
        .brand-header:hover {
            background: #edf2f7;
        }
        
        .brand-card.expanded .brand-header {
            background: linear-gradient(135deg, #4682B4 0%, #6495ED 100%);
            border-bottom-color: rgba(255,255,255,0.2);
        }
        
        .brand-card.expanded .brand-header .brand-name,
        .brand-card.expanded .brand-header .brand-toggle {
            color: white;
        }
        
        .brand-card.expanded .brand-header .brand-slug {
            background: rgba(255,255,255,0.2);
            color: white;
        }
        
        .brand-card.expanded .brand-header .brand-count {
            background: rgba(255,255,255,0.3);
            color: white;
        }
        
        .brand-info {
            display: flex;
            align-items: center;
            flex: 1;
        }
        
        .brand-name {
            font-size: 20px;
            font-weight: 700;
            color: #1a202c;
            letter-spacing: -0.3px;
            min-width: 180px;
        }
        
        .brand-slug {
            font-size: 13px;
            color: #718096;
            font-family: 'Courier New', monospace;
            background: #edf2f7;
            padding: 4px 10px;
            border-radius: 6px;
            font-weight: 500;
            width: 160px;
            display: inline-block;
            margin-left: 15px;
        }
        
        .brand-count {
            font-size: 13px;
            background: #4682B4;
            color: white;
            padding: 4px 12px;
            border-radius: 20px;
            font-weight: 600;
            min-width: 80px;
            text-align: center;
            display: inline-block;
            margin-left: 15px;
        }
        
        .brand-toggle {
            font-size: 20px;
            color: #666;
            transition: transform 0.2s;
        }
        
        .brand-card.expanded .brand-toggle {
            transform: rotate(180deg);
        }
        
        .models-container {
            display: none;
            padding: 0;
        }
        
        .brand-card.expanded .models-container {
            display: block;
        }
        
        .model-item {
            padding: 16px 24px;
            border-top: 1px solid #e2e8f0;
            display: flex;
            justify-content: space-between;
            align-items: center;
            transition: all 0.2s;
        }
        
        .model-item:hover {
            background: #f7fafc;
            padding-left: 28px;
        }
        
        .model-info {
            display: flex;
            align-items: center;
            gap: 15px;
            flex: 1;
        }
        
        .model-name {
            font-size: 17px;
            color: #2d3748;
            font-weight: 600;
            min-width: 150px;
        }
        
        .model-slug {
            font-size: 13px;
            color: #718096;
            font-family: 'Courier New', monospace;
            background: #edf2f7;
            padding: 4px 10px;
            border-radius: 6px;
            font-weight: 500;
        }
        
        .model-id {
            font-size: 12px;
            color: #a0aec0;
            font-weight: 500;
        }
        
        .brand-actions {
            display: flex;
            gap: 5px;
            margin-right: 20px;
        }
        
        .modal {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0,0,0,0.5);
            z-index: 1000;
            align-items: center;
            justify-content: center;
        }
        
        .modal.active {
            display: flex;
        }
        
        .modal-content {
            background: white;
            padding: 30px;
            border-radius: 8px;
            max-width: 500px;
            width: 90%;
            max-height: 90vh;
            overflow-y: auto;
        }
        
        .modal-header {
            margin-bottom: 20px;
        }
        
        .modal-header h2 {
            font-size: 22px;
            color: #333;
        }
        
        .form-group {
            margin-bottom: 20px;
        }
        
        .form-group label {
            display: block;
            margin-bottom: 5px;
            font-weight: 500;
            color: #555;
        }
        
        .form-group input {
            width: 100%;
            padding: 10px;
            border: 1px solid #ddd;
            border-radius: 5px;
            font-size: 14px;
        }
        
        .form-group small {
            display: block;
            margin-top: 5px;
            color: #666;
            font-size: 12px;
        }
        
        .modal-footer {
            display: flex;
            gap: 10px;
            justify-content: flex-end;
            margin-top: 20px;
        }
        
        .alert {
            padding: 12px 20px;
            border-radius: 5px;
            margin-bottom: 20px;
            display: none;
        }
        
        .alert.active {
            display: block;
        }
        
        .alert-success {
            background: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
        }
        
        .alert-error {
            background: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
        }
        
        .action-buttons {
            display: flex;
            gap: 5px;
        }
        
        .btn-sm {
            padding: 8px 14px;
            font-size: 13px;
            border-radius: 6px;
        }
        
        .loading {
            text-align: center;
            padding: 40px;
            color: #666;
        }
        @media (max-width: 1024px) {
            .container {
                padding: 30px;
            }
            
            .brand-name {
                min-width: 140px;
                font-size: 18px;
            }
            
            .brand-slug {
                width: 130px;
                font-size: 12px;
            }
            
            .brand-count {
                width: 85px;
                font-size: 12px;
            }
        }
        @media (max-width: 768px) {
            body {
                padding: 20px 10px;
            }
            
            .container {
                padding: 25px 20px;
                border-radius: 12px;
            }
            
            h1 {
                font-size: 26px;
            }
            
            .subtitle {
                font-size: 14px;
            }
            
            .brand-header {
                padding: 16px 18px;
                flex-wrap: wrap;
            }
            
            .brand-info {
                flex-wrap: wrap;
                gap: 10px;
            }
            
            .brand-name {
                min-width: 120px;
                font-size: 17px;
            }
            
            .brand-slug {
                width: 120px;
                margin-left: 0;
            }
            
            .brand-count {
                width: 80px;
                margin-left: 0;
            }
            
            .brand-actions {
                margin-right: 10px;
            }
            
            .model-item {
                padding: 14px 18px;
            }
            
            .model-info {
                flex-wrap: wrap;
                gap: 8px;
            }
            
            .model-name {
                min-width: 100px;
                font-size: 16px;
            }
        }
        @media (max-width: 600px) {
            body {
                padding: 15px 5px;
            }
            
            .container {
                padding: 20px 15px;
                border-radius: 10px;
            }
            
            h1 {
                font-size: 22px;
                margin-bottom: 8px;
            }
            
            .subtitle {
                font-size: 13px;
                margin-bottom: 25px;
            }
            
            .actions {
                flex-direction: column;
                gap: 8px;
            }
            
            .btn {
                width: 100%;
                padding: 12px 20px;
            }
            
            .search-box input {
                max-width: 100%;
                padding: 12px 16px;
                font-size: 14px;
            }
            
            .brand-card {
                margin-bottom: 12px;
            }
            
            .brand-header {
                padding: 14px 12px;
                flex-direction: column;
                align-items: flex-start;
                gap: 12px;
            }
            
            .brand-info {
                width: 100%;
                display: grid;
                grid-template-columns: 1fr auto 1fr;
                align-items: center;
                gap: 6px;
            }
            
            .brand-name {
                font-size: 14px;
                text-align: left;
                justify-self: start;
            }
            
            .brand-slug {
                width: 100px;
                font-size: 11px;
                padding: 3px 6px;
                text-align: center;
                justify-self: center;
            }
            
            .brand-count {
                width: 70px;
                font-size: 11px;
                padding: 3px 6px;
                text-align: center;
                justify-self: end;
            }
            
            .brand-actions {
                width: 100%;
                margin-right: 0;
                justify-content: flex-start;
            }
            
            .brand-actions .btn-sm {
                flex: 1;
            }
            
            .brand-toggle {
                position: static;
                display: block;
                width: 100%;
                text-align: center;
                margin-top: 8px;
                font-size: 18px;
            }
            
            .model-item {
                padding: 12px;
                flex-direction: column;
                align-items: flex-start;
                gap: 10px;
            }
            
            .model-item:hover {
                padding-left: 12px;
            }
            
            .model-info {
                width: 100%;
                flex-direction: column;
                align-items: flex-start;
                gap: 6px;
            }
            
            .model-name {
                min-width: auto;
                font-size: 16px;
            }
            
            .model-slug {
                font-size: 12px;
            }
            
            .action-buttons {
                width: 100%;
                gap: 8px;
            }
            
            .action-buttons .btn-sm {
                flex: 1;
            }
            
            .modal-content {
                width: 95%;
                padding: 25px 20px;
            }
            
            .form-group input {
                font-size: 16px;
            }
        }
        @media (max-width: 400px) {
            h1 {
                font-size: 20px;
            }
            
            .subtitle {
                font-size: 12px;
            }
            
            .brand-name {
                font-size: 16px;
            }
            
            .model-name {
                font-size: 15px;
            }
            
            .btn {
                font-size: 13px;
                padding: 10px 16px;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>
            <svg width="45" height="45" viewBox="0 0 512 512" xmlns="http://www.w3.org/2000/svg" style="vertical-align: middle; margin-right: 12px;">
                <path fill="#0ea5e9" d="M499.99 176h-59.87l-16.64-41.6C406.38 91.63 365.57 64 319.5 64h-127c-46.06 0-86.88 27.63-103.99 70.4L71.87 176H12.01C4.2 176-1.53 183.34.37 190.91l6 24C7.7 220.25 12.5 224 18.01 224h20.07C24.65 235.73 16 252.78 16 272v48c0 16.12 6.16 30.67 16 41.93V416c0 17.67 14.33 32 32 32h32c17.67 0 32-14.33 32-32v-32h256v32c0 17.67 14.33 32 32 32h32c17.67 0 32-14.33 32-32v-54.07c9.84-11.25 16-25.8 16-41.93v-48c0-19.22-8.65-36.27-22.07-48H494c5.51 0 10.31-3.75 11.64-9.09l6-24c1.89-7.57-3.84-14.91-11.65-14.91zm-352.06-17.83c7.29-18.22 24.94-30.17 44.57-30.17h127c19.63 0 37.28 11.95 44.57 30.17L384 208H128l19.93-49.83zM96 319.8c-19.2 0-32-12.76-32-31.9S76.8 256 96 256s48 28.71 48 47.85-28.8 15.95-48 15.95zm320 0c-19.2 0-48 3.19-48-15.95S396.8 256 416 256s32 12.76 32 31.9-12.8 31.9-32 31.9z"/>
            </svg>
            Gestionare Mărci și Modele Auto
        </h1>
        
        <div class="alert alert-success" id="successAlert"></div>
        <div class="alert alert-error" id="errorAlert"></div>
        
        <div class="actions">
            <button class="btn btn-primary" onclick="openAddModelModal()">➕ Adaugă Marcă / Model Nou</button>
            <button class="btn btn-secondary" onclick="loadData()">🔄 Reîmprospătează</button>
        </div>
        
        <div class="search-box">
            <input type="text" id="searchInput" placeholder="🔍 Caută după marcă sau model..." onkeyup="filterTable()">
        </div>
        
        <div id="tableContainer">
            <div class="loading">Se încarcă...</div>
        </div>
    </div>
    
    <!-- Add/Edit Modal -->
    <div class="modal" id="carModal">
        <div class="modal-content">
            <div class="modal-header">
                <h2 id="modalTitle">Adaugă Marcă/Model</h2>
            </div>
            <form id="carForm" onsubmit="saveCar(event)">
                <input type="hidden" id="carId" name="id">
                
                <div class="form-group">
                    <label for="br">Marcă (slug) *</label>
                    <input type="text" id="br" name="br" required>
                    <small>Ex: mercedes_benz, volkswagen, toyota</small>
                </div>
                
                <div class="form-group">
                    <label for="mo">Model (slug) *</label>
                    <input type="text" id="mo" name="mo" required>
                    <small>Ex: eqe, taigo, yaris_cross</small>
                </div>
                
                <div class="form-group">
                    <label for="br_nm">Nume Marcă *</label>
                    <input type="text" id="br_nm" name="br_nm" required>
                    <small>Ex: Mercedes Benz, Volkswagen, Toyota</small>
                </div>
                
                <div class="form-group">
                    <label for="mo_nm">Nume Model *</label>
                    <input type="text" id="mo_nm" name="mo_nm" required>
                    <small>Ex: EQE, Taigo, Yaris Cross</small>
                </div>
                
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" onclick="closeModal()">Anulează</button>
                    <button type="submit" class="btn btn-success">Salvează</button>
                </div>
            </form>
        </div>
    </div>
    
    <script>
        let allCars = [];
        const urlParams = new URLSearchParams(window.location.search);
        const token = urlParams.get('token');
        
        document.addEventListener('DOMContentLoaded', function() {
            loadData();
        });
        
        function loadData() {
            fetch('manage_cars.php?token=' + token, {
                method: 'POST',
                headers: {'Content-Type': 'application/x-www-form-urlencoded'},
                body: 'action=get_all'
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    allCars = data.cars;
                    renderTable(allCars);
                } else {
                    showError(data.error || 'Eroare la încărcarea datelor');
                }
            })
            .catch(error => {
                showError('Eroare de rețea: ' + error.message);
            });
        }
        
        function renderTable(cars) {
            const container = document.getElementById('tableContainer');
            
            if (cars.length === 0) {
                container.innerHTML = '<div class="loading">Nu există înregistrări</div>';
                return;
            }
            
            const brandGroups = {};
            cars.forEach(car => {
                if (!brandGroups[car.br]) {
                    brandGroups[car.br] = {
                        br: car.br,
                        br_nm: car.br_nm,
                        models: []
                    };
                }
                brandGroups[car.br].models.push(car);
            });
            
            const sortedBrands = Object.values(brandGroups).sort((a, b) => 
                a.br_nm.localeCompare(b.br_nm)
            );
            
            let html = '';
            
            sortedBrands.forEach(brand => {
                brand.models.sort((a, b) => a.mo_nm.localeCompare(b.mo_nm));
                
                html += `
                    <div class="brand-card" id="brand-${escapeHtml(brand.br)}">
                        <div class="brand-header" onclick="toggleBrand('${escapeHtml(brand.br)}')">
                            <div class="brand-info">
                                <span class="brand-name">${escapeHtml(brand.br_nm)}</span>
                                <span class="brand-slug">${escapeHtml(brand.br)}</span>
                                <span class="brand-count">${brand.models.length} ${brand.models.length === 1 ? 'model' : 'modele'}</span>
                            </div>
                            <div class="brand-actions" onclick="event.stopPropagation()">
                                <button class="btn btn-primary btn-sm" onclick="addModelToBrand('${escapeHtml(brand.br)}', '${escapeHtml(brand.br_nm)}')">➕ Model</button>
                            </div>
                            <span class="brand-toggle">▼</span>
                        </div>
                        <div class="models-container">
                `;
                
                brand.models.forEach(model => {
                    const carJson = JSON.stringify(model).replace(/'/g, "&#39;");
                    html += `
                        <div class="model-item">
                            <div class="model-info">
                                <span class="model-name">${escapeHtml(model.mo_nm)}</span>
                                <span class="model-slug">${escapeHtml(model.mo)}</span>
                                <span class="model-id">#${model.id}</span>
                            </div>
                            <div class="action-buttons">
                                <button class="btn btn-primary btn-sm" onclick='editCar(${carJson})'>✏️</button>
                                <button class="btn btn-danger btn-sm" onclick="deleteCar(${model.id}, '${escapeHtml(model.br_nm)} ${escapeHtml(model.mo_nm)}')">🗑️</button>
                            </div>
                        </div>
                    `;
                });
                
                html += `
                        </div>
                    </div>
                `;
            });
            
            container.innerHTML = html;
        }
        
        function toggleBrand(brandSlug) {
            const card = document.getElementById('brand-' + brandSlug);
            card.classList.toggle('expanded');
        }
        
        function addModelToBrand(brandSlug, brandName) {
            document.getElementById('modalTitle').textContent = 'Adaugă Model la ' + brandName;
            document.getElementById('carForm').reset();
            document.getElementById('carId').value = '';
            document.getElementById('br').value = brandSlug;
            document.getElementById('br_nm').value = brandName;
            document.getElementById('br').readOnly = true;
            document.getElementById('br_nm').readOnly = true;
            document.getElementById('carModal').classList.add('active');
        }
        
        function filterTable() {
            const searchTerm = document.getElementById('searchInput').value.toLowerCase();
            const filtered = allCars.filter(car => 
                car.br.toLowerCase().includes(searchTerm) ||
                car.mo.toLowerCase().includes(searchTerm) ||
                car.br_nm.toLowerCase().includes(searchTerm) ||
                car.mo_nm.toLowerCase().includes(searchTerm)
            );
            renderTable(filtered);
        }
        
        function openAddModelModal() {
            document.getElementById('modalTitle').textContent = 'Adaugă Model Nou';
            document.getElementById('carForm').reset();
            document.getElementById('carId').value = '';
            document.getElementById('br').readOnly = false;
            document.getElementById('br_nm').readOnly = false;
            document.getElementById('carModal').classList.add('active');
        }
        
        function editCar(car) {
            document.getElementById('modalTitle').textContent = 'Editează Model';
            document.getElementById('carId').value = car.id;
            document.getElementById('br').value = car.br;
            document.getElementById('mo').value = car.mo;
            document.getElementById('br_nm').value = car.br_nm;
            document.getElementById('mo_nm').value = car.mo_nm;
            document.getElementById('br').readOnly = false;
            document.getElementById('br_nm').readOnly = false;
            document.getElementById('carModal').classList.add('active');
        }
        
        function closeModal() {
            document.getElementById('carModal').classList.remove('active');
        }
        
        function saveCar(event) {
            event.preventDefault();
            
            const formData = new FormData(event.target);
            const id = formData.get('id');
            const action = id ? 'update_car' : 'add_car';
            formData.append('action', action);
            
            fetch('manage_cars.php?token=' + token, {
                method: 'POST',
                body: new URLSearchParams(formData)
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    showSuccess(data.message);
                    closeModal();
                    loadData();
                } else {
                    showError(data.error || 'Eroare la salvare');
                }
            })
            .catch(error => {
                showError('Eroare de rețea: ' + error.message);
            });
        }
        
        function deleteCar(id, name) {
            if (!confirm(`Sigur doriți să ștergeți "${name}"?`)) {
                return;
            }
            
            fetch('manage_cars.php?token=' + token, {
                method: 'POST',
                headers: {'Content-Type': 'application/x-www-form-urlencoded'},
                body: `action=delete_car&id=${id}`
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    showSuccess(data.message);
                    loadData();
                } else {
                    showError(data.error || 'Eroare la ștergere');
                }
            })
            .catch(error => {
                showError('Eroare de rețea: ' + error.message);
            });
        }
        
        function showSuccess(message) {
            const alert = document.getElementById('successAlert');
            alert.textContent = message;
            alert.classList.add('active');
            setTimeout(() => alert.classList.remove('active'), 5000);
        }
        
        function showError(message) {
            const alert = document.getElementById('errorAlert');
            alert.textContent = message;
            alert.classList.add('active');
            setTimeout(() => alert.classList.remove('active'), 5000);
        }
        
        function escapeHtml(text) {
            const div = document.createElement('div');
            div.textContent = text;
            return div.innerHTML;
        }
        
        document.getElementById('carModal').addEventListener('click', function(e) {
            if (e.target === this) {
                closeModal();
            }
        });
    </script>
</body>
</html>
