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
    
    // Подготовка данных для ответа
    $response_data = [
        'cont_nr' => $contract_nr,
        'br' => isset($contract_data['br']) ? $contract_data['br'] : '',
        'mo' => isset($contract_data['mo']) ? $contract_data['mo'] : '',
        'vin' => isset($contract_data['vin']) ? $contract_data['vin'] : '',
        'prc' => isset($contract_data['prc']) ? $contract_data['prc'] : '',
        'cur' => isset($contract_data['cur']) ? $contract_data['cur'] : 'MDL',
        'u_tp' => isset($contract_data['u_tp']) ? $contract_data['u_tp'] : 'fiz',
        'u_cf_idno' => isset($contract_data['u_cf_idno']) ? $contract_data['u_cf_idno'] : '',
        'u_nm' => isset($contract_data['u_nm']) ? $contract_data['u_nm'] : '',
        'u_tva_dt' => isset($contract_data['u_tva_dt']) ? $contract_data['u_tva_dt'] : '',
        'u_iban_dt_tk' => isset($contract_data['u_iban_dt_tk']) ? $contract_data['u_iban_dt_tk'] : '',
        'u_adr' => isset($contract_data['u_adr']) ? $contract_data['u_adr'] : '',
        'u_phn' => isset($contract_data['u_phn']) ? $contract_data['u_phn'] : '',
        'u_eml' => isset($contract_data['u_eml']) ? $contract_data['u_eml'] : ''
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
