<?php
defined( '_DOIT' ) or die( 'Restricted access' );

header('Content-Type: application/json');

if (!isset($_POST['contract_nr']) || empty($_POST['contract_nr'])) {
    echo json_encode(['success' => false, 'message' => 'Contract number required']);
    exit;
}

$contract_nr = trim($_POST['contract_nr']);

try {
    // Поиск контракта в базе данных
    // Предполагаем, что контракты сохраняются в таблице gh3sp_docs_ctlg
    $pdo = $db->prepare('SELECT * FROM '.$prefx.'_docs_ctlg WHERE inf LIKE ? ORDER BY id DESC LIMIT 1');
    $pdo->execute(['%cont_nr==' . $contract_nr . '%']);
    
    $contract = $pdo->fetch(PDO::FETCH_ASSOC);
    
    if (!$contract) {
        echo json_encode(['success' => false, 'message' => 'Contract not found']);
        exit;
    }
    
    // Парсинг данных из поля inf
    $contract_data = [];
    $inf_parts = explode('&&', $contract['inf']);
    
    foreach ($inf_parts as $part) {
        if (strpos($part, '==') !== false) {
            list($key, $value) = explode('==', $part, 2);
            $contract_data[$key] = $value;
        }
    }
    
    // Подготовка данных для ответа - только основные данные покупателя для cesionar
    $response_data = [
        'cont_nr' => $contract_nr,
        'u_cf_idno' => isset($contract_data['u_cf_idno']) ? $contract_data['u_cf_idno'] : '',
        'u_nm' => isset($contract_data['u_nm']) ? $contract_data['u_nm'] : ''
    ];
    
    echo json_encode([
        'success' => true, 
        'data' => $response_data,
        'message' => 'Contract data loaded successfully'
    ]);
    
} catch (Exception $e) {
    echo json_encode([
        'success' => false, 
        'message' => 'Database error: ' . $e->getMessage()
    ]);
}
?>
