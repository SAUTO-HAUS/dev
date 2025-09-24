<?php
defined( '_DOIT' ) or die( 'Restricted access' );

// Get list of contracts for Annexa dropdown
$contracts_html = '';

try {
    // Get contracts from database - both types: avans and vânzare-cumpărare
    $pdo = $db->prepare('
        SELECT id, abr, y, q, n, date, inf 
        FROM '.$prefx.'_docs_ctlg 
        WHERE f IN ("con_arvon", "vinzare_proc", "vinzare_avans") 
        ORDER BY date DESC, id DESC 
        LIMIT 100
    ');
    $pdo->execute();
    
    foreach ($pdo as $r) {
        // Parse contract info to get buyer name
        $buyer_name = '';
        if ($r['inf']) {
            $inf_parts = explode('&&', $r['inf']);
            foreach ($inf_parts as $part) {
                if (strpos($part, 'u_nm==') === 0) {
                    $buyer_name = str_replace('u_nm==', '', $part);
                    break;
                }
            }
        }
        
        // Generate contract number
        $contract_nr = $r['abr'] . $r['y'] . $r['q'] . '/' . $r['n'];
        $contract_date = date('d.m.Y', strtotime($r['date']));
        
        // Contract display text
        $display_text = $contract_nr . ' (' . $contract_date . ')';
        if ($buyer_name) {
            $display_text .= ' - ' . $buyer_name;
        }
        
        $contracts_html .= '<option value="'.$contract_nr.'" data-id="'.$r['id'].'">'.$display_text.'</option>';
    }
    
} catch (Exception $e) {
    // Fallback if database error
    $contracts_html = '<option value="" disabled>Eroare la încărcarea contractelor</option>';
}

// Return the HTML for contracts dropdown
echo $contracts_html;
?>
