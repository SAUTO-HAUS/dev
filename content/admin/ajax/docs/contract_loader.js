// JavaScript для автозаполнения данных Annexa из контракта
// Добавить в menu.php для формы Annexa

function loadContractData() {
    var contractNr = document.querySelector('input[name="cont_nr"]').value;
    if (contractNr.trim() === "") {
        return;
    }
    
    // AJAX запрос для загрузки данных контракта
    var xhr = new XMLHttpRequest();
    xhr.open('POST', '/content/admin/ajax/docs/load_contract.php', true);
    xhr.setRequestHeader('Content-Type', 'application/x-www-form-urlencoded');
    
    xhr.onreadystatechange = function() {
        if (xhr.readyState === 4 && xhr.status === 200) {
            try {
                var response = JSON.parse(xhr.responseText);
                if (response.success) {
                    fillContractData(response.data);
                } else {
                    console.log('Contract not found: ' + contractNr);
                    clearContractFields();
                }
            } catch (e) {
                console.error('Error parsing response:', e);
            }
        }
    };
    
    xhr.send('contract_nr=' + encodeURIComponent(contractNr));
}

function fillContractData(data) {
    // Заполнить данные автомобиля
    if (data.br) {
        var brandSelect = document.querySelector('select[name="br"]');
        brandSelect.value = data.br;
        brandSelect.dispatchEvent(new Event('change'));
    }
    
    if (data.mo) {
        setTimeout(function() {
            var modelSelect = document.querySelector('select[name="mo"]');
            modelSelect.value = data.mo;
        }, 100);
    }
    
    if (data.vin) document.querySelector('input[name="vin"]').value = data.vin;
    if (data.prc) document.querySelector('input[name="prc"]').value = data.prc;
    if (data.cur) document.querySelector('select[name="cur"]').value = data.cur;
    
    // Заполнить данные покупателя
    if (data.u_tp) document.querySelector('select[name="u_tp"]').value = data.u_tp;
    if (data.u_cf_idno) document.querySelector('input[name="u_cf_idno"]').value = data.u_cf_idno;
    if (data.u_nm) document.querySelector('input[name="u_nm"]').value = data.u_nm;
    if (data.u_tva_dt) document.querySelector('input[name="u_tva_dt"]').value = data.u_tva_dt;
    if (data.u_iban_dt_tk) document.querySelector('input[name="u_iban_dt_tk"]').value = data.u_iban_dt_tk;
    if (data.u_adr) document.querySelector('textarea[name="u_adr"]').value = data.u_adr;
    if (data.u_phn) document.querySelector('input[name="u_phn"]').value = data.u_phn;
    if (data.u_eml) document.querySelector('input[name="u_eml"]').value = data.u_eml;
    
    console.log('Contract data loaded successfully for: ' + data.cont_nr);
}

function clearContractFields() {
    // Очистить поля если контракт не найден
    var fields = [
        'input[name="vin"]', 'input[name="prc"]', 'input[name="u_cf_idno"]', 
        'input[name="u_nm"]', 'input[name="u_tva_dt"]', 'input[name="u_iban_dt_tk"]',
        'textarea[name="u_adr"]', 'input[name="u_phn"]', 'input[name="u_eml"]'
    ];
    
    fields.forEach(function(selector) {
        var element = document.querySelector(selector);
        if (element) element.value = '';
    });
    
    // Сбросить селекты
    document.querySelector('select[name="br"]').value = 'x';
    document.querySelector('select[name="mo"]').value = 'x';
    document.querySelector('select[name="cur"]').value = 'MDL';
    document.querySelector('select[name="u_tp"]').value = 'fiz';
}

// Event listeners
document.addEventListener('DOMContentLoaded', function() {
    var contractInput = document.querySelector('input[name="cont_nr"]');
    if (contractInput) {
        contractInput.addEventListener('blur', loadContractData);
        contractInput.addEventListener('keypress', function(e) {
            if (e.key === 'Enter') {
                loadContractData();
            }
        });
    }
});
