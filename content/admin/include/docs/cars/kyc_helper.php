<?php defined( '_DOIT' ) or die( 'Restricted access' );

function populateKycFields() {
    global $_POST;
    
    // Core client information mapping - only if not already set
    $_POST['kyc_client_name'] = $_POST['kyc_client_name'] ?? ($_POST['u_nm'] ?? '');
    $_POST['kyc_idnp'] = $_POST['kyc_idnp'] ?? ($_POST['u_cf_idno'] ?? '');
    $_POST['kyc_address'] = $_POST['kyc_address'] ?? ($_POST['u_adr'] ?? '');
    $_POST['kyc_phone'] = $_POST['kyc_phone'] ?? ($_POST['u_phn'] ?? '');
    $_POST['kyc_email'] = $_POST['kyc_email'] ?? ($_POST['u_eml'] ?? '');
    
    // Set completion date to current date if not set
    $_POST['kyc_completion_date'] = $_POST['kyc_completion_date'] ?? ($_POST['completion_date'] ?? date('d.m.Y'));
    
    // Auto-populate residence address same as domicile if not specified
    if (!isset($_POST['kyc_residence_addr']) && isset($_POST['u_adr']) && $_POST['u_adr'] != '') {
        $_POST['kyc_residence_addr'] = $_POST['residence_addr'] ?? $_POST['u_adr'];
    }
    
    // Set transaction purpose based on contract type - only if not already set
    if (!isset($_POST['kyc_transaction_purpose']) && isset($_POST['doc_f'])) {
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
    $_POST['kyc_doc_type'] = $_POST['kyc_doc_type'] ?? ($_POST['doc_type'] ?? 'buletin');
    $_POST['kyc_doc_series'] = $_POST['kyc_doc_series'] ?? ($_POST['doc_series'] ?? '');
    $_POST['kyc_doc_office'] = $_POST['kyc_doc_office'] ?? ($_POST['doc_office'] ?? '');
    $_POST['kyc_doc_date'] = $_POST['kyc_doc_date'] ?? ($_POST['doc_date'] ?? (isset($_POST['u_iban_dt_tk']) && $_POST['u_iban_dt_tk'] ? date('d.m.Y', strtotime($_POST['u_iban_dt_tk'])) : ''));
    $_POST['kyc_doc_expiry'] = $_POST['kyc_doc_expiry'] ?? ($_POST['doc_expiry'] ?? '');
    $_POST['kyc_citizenship'] = $_POST['kyc_citizenship'] ?? ($_POST['citizenship'] ?? 'Republica Moldova');
    $_POST['kyc_birth_info'] = $_POST['kyc_birth_info'] ?? ($_POST['birth_info'] ?? (isset($_POST['u_tva_dt']) && $_POST['u_tva_dt'] ? date('d.m.Y', strtotime($_POST['u_tva_dt'])) : ''));
    
    // Occupation and employment information - only if not already set
    $_POST['kyc_occupation'] = $_POST['kyc_occupation'] ?? ($_POST['occupation'] ?? '');
    $_POST['kyc_occupation_other'] = $_POST['kyc_occupation_other'] ?? ($_POST['occupation_other'] ?? '');
    $_POST['kyc_institution_name'] = $_POST['kyc_institution_name'] ?? ($_POST['institution_name'] ?? '');
    $_POST['kyc_position'] = $_POST['kyc_position'] ?? ($_POST['position'] ?? '');
    
    // Politically Exposed Person (PEP) information - preserve form values
    // Don't override if already set from form
    if (!isset($_POST['kyc_no_public_function'])) {
        $_POST['kyc_no_public_function'] = $_POST['no_public_function'] ?? '';
    }
    $_POST['kyc_public_function'] = $_POST['kyc_public_function'] ?? ($_POST['public_function'] ?? '');
    $_POST['kyc_public_function_other'] = $_POST['kyc_public_function_other'] ?? ($_POST['public_function_other'] ?? '');
    $_POST['kyc_affiliated_company'] = $_POST['kyc_affiliated_company'] ?? ($_POST['affiliated_company'] ?? '');
    
    // Family members information - only if not already set
    $_POST['kyc_parents_names'] = $_POST['kyc_parents_names'] ?? ($_POST['parents_names'] ?? '');
    $_POST['kyc_spouse_name'] = $_POST['kyc_spouse_name'] ?? ($_POST['spouse_name'] ?? '');
    $_POST['kyc_children_names'] = $_POST['kyc_children_names'] ?? ($_POST['children_names'] ?? '');
    $_POST['kyc_partner_name'] = $_POST['kyc_partner_name'] ?? ($_POST['partner_name'] ?? '');
    
    // Transaction purpose other field - only if not already set
    $_POST['kyc_transaction_purpose_other'] = $_POST['kyc_transaction_purpose_other'] ?? ($_POST['transaction_purpose_other'] ?? '');
    
    // Source of funds - only if not already set
    $_POST['kyc_money_source'] = $_POST['kyc_money_source'] ?? ($_POST['money_source'] ?? 'salary');
    $_POST['kyc_money_source_other'] = $_POST['kyc_money_source_other'] ?? ($_POST['money_source_other'] ?? '');
    
    // SAUTO approval information - only if not already set
    $_POST['kyc_approval_date'] = $_POST['kyc_approval_date'] ?? ($_POST['approval_date'] ?? '');
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

function requiresKycPages($contractType = null, $price = null) {
    // List of contract types that require KYC pages
    $kycContractTypes = [
        'vinzare_proc', 'vinzare_avans', 'vinzare_sauto', 
        'con_plata', 'con_arvon', 'con_arvon_com', 
        'com_transport', 'con_intermed'
    ];
    
    // Check contract type
    $contractType = $contractType ?? ($_POST['doc_f'] ?? '');
    if (!in_array($contractType, $kycContractTypes)) {
        return false;
    }
    
    // Check price threshold based on currency
    $currency = $_POST['cur'] ?? 'MDL';
    
    if ($price !== null) {
        // Convert price to numeric value, handling different formats
        $numericPrice = is_numeric($price) ? (float)$price : (float)str_replace([',', ' '], '', $price);
        
        // Apply currency-specific thresholds
        if ($currency === 'EUR') {
            return $numericPrice >= 10000; // 10,000 EUR threshold
        } else {
            return $numericPrice >= 200000; // 200,000 MDL threshold
        }
    }
    
    // If no price provided, check $_POST for price
    if (isset($_POST['prc']) && $_POST['prc'] !== '') {
        $numericPrice = is_numeric($_POST['prc']) ? (float)$_POST['prc'] : (float)str_replace([',', ' '], '', $_POST['prc']);
        
        // Apply currency-specific thresholds
        if ($currency === 'EUR') {
            return $numericPrice >= 10000; // 10,000 EUR threshold
        } else {
            return $numericPrice >= 200000; // 200,000 MDL threshold
        }
    }
    
    // Default to true if no price available (for backward compatibility)
    return true;
}

function getKycJavaScript() {
    return '
<script>
function toggleKycForm() {
    var priceInputs = document.querySelectorAll(\'input[name="prc"]\');
    var kycSections = document.querySelectorAll(\'.kyc-questionnaire\');
    var currencySelect = document.querySelector(\'select[name="cur"]\');
    
    function checkKycThreshold() {
        priceInputs.forEach(function(priceInput) {
            var price = parseFloat(priceInput.value.replace(/[^0-9.]/g, \'\')) || 0;
            var currency = currencySelect ? currencySelect.value : \'MDL\';
            var showKyc = false;
            
            if (price === 0) {
                showKyc = true; // Show for ADD mode
            } else if (currency === \'EUR\') {
                showKyc = price >= 10000; // 10,000 EUR threshold
            } else {
                showKyc = price >= 200000; // 200,000 MDL threshold
            }
            
            kycSections.forEach(function(section) {
                section.style.display = showKyc ? \'block\' : \'none\';
            });
        });
    }
    
    priceInputs.forEach(function(priceInput) {
        priceInput.addEventListener(\'input\', checkKycThreshold);
    });
    
    if (currencySelect) {
        currencySelect.addEventListener(\'change\', checkKycThreshold);
    }
    
    // Trigger initial check
    checkKycThreshold();
}

// Initialize when DOM is ready
if (document.readyState === \'loading\') {
    document.addEventListener(\'DOMContentLoaded\', toggleKycForm);
} else {
    toggleKycForm();
}
</script>';
}
?>
