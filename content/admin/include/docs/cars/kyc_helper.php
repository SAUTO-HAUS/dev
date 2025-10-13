<?php defined( '_DOIT' ) or die( 'Restricted access' );

function populateKycFields() {
    global $_POST;
    
    // Core client information mapping
    $_POST['kyc_client_name'] = $_POST['u_nm'] ?? '';
    $_POST['kyc_idnp'] = $_POST['u_cf_idno'] ?? '';
    $_POST['kyc_address'] = $_POST['u_adr'] ?? '';
    $_POST['kyc_phone'] = $_POST['u_phn'] ?? '';
    $_POST['kyc_email'] = $_POST['u_eml'] ?? '';
    
    // Set completion date to current date if not set
    $_POST['kyc_completion_date'] = $_POST['completion_date'] ?? date('d.m.Y');
    
    // Auto-populate residence address same as domicile if not specified
    if (isset($_POST['u_adr']) && $_POST['u_adr'] != '') {
        $_POST['kyc_residence_addr'] = $_POST['residence_addr'] ?? $_POST['u_adr'];
    }
    
    // Set transaction purpose based on contract type
    if (isset($_POST['doc_f'])) {
        switch($_POST['doc_f']) {
            case 'vinzare_proc':
            case 'vinzare_avans':
            case 'con_plata':
            case 'con_arvon':
            case 'con_intermed':
                $_POST['kyc_transaction_purpose'] = $_POST['transaction_purpose'] ?? 'personal_use';
                break;
            case 'vinzare_sauto':
            case 'con_arvon_com':
            case 'com_transport':
                $_POST['kyc_transaction_purpose'] = $_POST['transaction_purpose'] ?? 'company_use';
                break;
            default:
                $_POST['kyc_transaction_purpose'] = $_POST['transaction_purpose'] ?? 'personal_use';
        }
    }
    
    // Set default document type to ID card if not specified
    $_POST['kyc_doc_type'] = $_POST['doc_type'] ?? 'buletin';
    $_POST['kyc_doc_series'] = $_POST['doc_series'] ?? '';
    $_POST['kyc_doc_office'] = $_POST['doc_office'] ?? '';
    $_POST['kyc_doc_date'] = $_POST['doc_date'] ?? (isset($_POST['u_iban_dt_tk']) && $_POST['u_iban_dt_tk'] ? date('d.m.Y', strtotime($_POST['u_iban_dt_tk'])) : '');
    $_POST['kyc_doc_expiry'] = $_POST['doc_expiry'] ?? '';
    $_POST['kyc_citizenship'] = $_POST['citizenship'] ?? 'Republica Moldova';
    $_POST['kyc_birth_info'] = $_POST['birth_info'] ?? (isset($_POST['u_tva_dt']) && $_POST['u_tva_dt'] ? date('d.m.Y', strtotime($_POST['u_tva_dt'])) : '');
    
    // Occupation and employment information
    $_POST['kyc_occupation'] = $_POST['occupation'] ?? '';
    $_POST['kyc_occupation_other'] = $_POST['occupation_other'] ?? '';
    $_POST['kyc_institution_name'] = $_POST['institution_name'] ?? '';
    $_POST['kyc_position'] = $_POST['position'] ?? '';
    
    // Politically Exposed Person (PEP) information
    $_POST['kyc_no_public_function'] = $_POST['no_public_function'] ?? 'checked';
    $_POST['kyc_public_function'] = $_POST['public_function'] ?? '';
    $_POST['kyc_public_function_other'] = $_POST['public_function_other'] ?? '';
    $_POST['kyc_affiliated_company'] = $_POST['affiliated_company'] ?? '';
    
    // Family members information
    $_POST['kyc_parents_names'] = $_POST['parents_names'] ?? '';
    $_POST['kyc_spouse_name'] = $_POST['spouse_name'] ?? '';
    $_POST['kyc_children_names'] = $_POST['children_names'] ?? '';
    $_POST['kyc_partner_name'] = $_POST['partner_name'] ?? '';
    
    // Transaction purpose other field
    $_POST['kyc_transaction_purpose_other'] = $_POST['transaction_purpose_other'] ?? '';
    
    // Source of funds - default to salary
    $_POST['kyc_money_source'] = $_POST['money_source'] ?? 'salary';
    $_POST['kyc_money_source_other'] = $_POST['money_source_other'] ?? '';
    
    // SAUTO approval information
    $_POST['kyc_approval_date'] = $_POST['approval_date'] ?? '';
}

function includeKycPages() {
    // Auto-populate KYC fields from contract data
    populateKycFields();
    
    // Capture KYC pages content
    ob_start();
    include(__DIR__.'/kyc_pages.php');
    $kyc_content = ob_get_clean();
    
    // Add KYC pages to the main contract content
    global $rtrn;
    if (isset($rtrn)) {
        $rtrn .= $kyc_content;
    } else {
        echo $kyc_content;
    }
}

function requiresKycPages($contractType) {
    // All contract types that require KYC pages (excluding invoice which is just a billing document)
    $kyc_contracts = [
        'vinzare_proc',      
        'vinzare_avans',     
        'vinzare_sauto',     
        'con_plata',        
        'con_arvon',         
        'con_arvon_com',     
        'com_transport',     
        'con_intermed'       
    ];
    return in_array($contractType, $kyc_contracts);
}
?>
