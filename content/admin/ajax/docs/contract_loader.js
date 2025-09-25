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
    // Заполнить только основные данные покупателя для cesionar
    if (data.u_cf_idno) document.querySelector('input[name="u_cf_idno"]').value = data.u_cf_idno;
    if (data.u_nm) document.querySelector('input[name="u_nm"]').value = data.u_nm;
    
    console.log('Contract data loaded successfully for: ' + data.cont_nr);
}

function clearContractFields() {
    // Очистить только поля cesionar формы
    var fields = [
        'input[name="u_cf_idno"]', 'input[name="u_nm"]'
    ];
    
    fields.forEach(function(selector) {
        var element = document.querySelector(selector);
        if (element) element.value = '';
    });
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
