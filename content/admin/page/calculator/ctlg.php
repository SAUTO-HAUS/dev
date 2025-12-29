<?php defined( '_DOIT' ) or die( 'Restricted access' );

include(__DIR__.'/calc_translate.php');
$t = $calc_trans[$lang_code] ?? $calc_trans['ro'];

$admin_dir = isset($_COOKIE['admin_dir']) ? $_COOKIE['admin_dir'] : 'admin';

$rtrn = '
<style>
    #catalog-container {
        max-width: 1200px;
        margin: 0 auto;
        padding: 2rem;
        font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif;
    }
    
    #catalog-container .catalog-header {
        display: flex;
        justify-content: space-between;
        align-items: center;
        margin-bottom: 2rem;
    }
    
    #catalog-container .catalog-header h1 {
        margin: 0;
        font-size: 1.8rem;
        color: #333;
    }
    
    #catalog-container .back-btn {
        display: inline-flex;
        align-items: center;
        gap: 0.5rem;
        padding: 0.6rem 1.2rem;
        background: #6c757d;
        color: #fff;
        text-decoration: none;
        border-radius: 8px;
        font-size: 0.95rem;
        transition: background 0.3s;
    }
    
    #catalog-container .back-btn:hover {
        background: #5a6268;
    }
    
    #catalog-container .offers-table {
        width: 100%;
        border-collapse: collapse;
        background: #fff;
        border-radius: 12px;
        overflow: hidden;
        box-shadow: 0 2px 10px rgba(0,0,0,0.1);
    }
    
    #catalog-container .offers-table th,
    #catalog-container .offers-table td {
        padding: 1rem;
        text-align: left;
        border-bottom: 1px solid #eee;
    }
    
    #catalog-container .offers-table th {
        background: #f8f9fa;
        font-weight: 600;
        color: #333;
        font-size: 0.9rem;
        text-transform: uppercase;
    }
    
    #catalog-container .offers-table tr:hover {
        background: #f8f9fa;
    }
    
    #catalog-container .offers-table .vehicle-info {
        font-weight: 600;
        color: #333;
    }
    
    #catalog-container .offers-table .client-name {
        color: #666;
    }
    
    #catalog-container .offers-table .vin-code {
        font-family: monospace;
        font-size: 0.85rem;
        color: #888;
    }
    
    #catalog-container .offers-table .date-col {
        font-size: 0.85rem;
        color: #888;
    }
    
    #catalog-container .offers-table .actions {
        display: flex;
        gap: 0.5rem;
    }
    
    #catalog-container .offers-table .btn-pdf {
        display: inline-flex;
        align-items: center;
        gap: 0.3rem;
        padding: 0.4rem 0.8rem;
        background: #28a745;
        color: #fff;
        text-decoration: none;
        border-radius: 6px;
        font-size: 0.85rem;
        transition: background 0.3s;
    }
    
    #catalog-container .offers-table .btn-pdf:hover {
        background: #1e7e34;
    }
    
    #catalog-container .offers-table .btn-delete {
        display: inline-flex;
        align-items: center;
        gap: 0.3rem;
        padding: 0.4rem 0.8rem;
        background: #dc3545;
        color: #fff;
        border: none;
        border-radius: 6px;
        font-size: 0.85rem;
        cursor: pointer;
        transition: background 0.3s;
    }
    
    #catalog-container .offers-table .btn-delete:hover {
        background: #c82333;
    }
    
    #catalog-container .empty-state {
        text-align: center;
        padding: 4rem 2rem;
        color: #888;
    }
    
    #catalog-container .empty-state .icon {
        font-size: 4rem;
        margin-bottom: 1rem;
    }
    
    #catalog-container .pagination {
        display: flex;
        justify-content: center;
        gap: 0.5rem;
        margin-top: 2rem;
    }
    
    #catalog-container .pagination button {
        padding: 0.5rem 1rem;
        border: 1px solid #ddd;
        background: #fff;
        border-radius: 6px;
        cursor: pointer;
        transition: all 0.3s;
    }
    
    #catalog-container .pagination button:hover {
        background: #f8f9fa;
    }
    
    #catalog-container .pagination button.active {
        background: #e2001a;
        color: #fff;
        border-color: #e2001a;
    }
    
    #catalog-container .pagination button:disabled {
        opacity: 0.5;
        cursor: not-allowed;
    }
    
    #catalog-container .total-info {
        color: #666;
        font-size: 0.9rem;
        margin-bottom: 1rem;
    }
    
    @media (max-width: 768px) {
        #catalog-container {
            padding: 1rem;
        }
        
        #catalog-container .catalog-header {
            flex-direction: column;
            gap: 1rem;
            text-align: center;
        }
        
        #catalog-container .offers-table {
            display: block;
            overflow-x: auto;
        }
    }
</style>

<div id="catalog-container">
    <div class="catalog-header">
        <h1>📋 '.$t['catalog'].'</h1>
        <a href="/'.$_COOKIE['lang'].'/'.$admin_dir.'/calculator" class="back-btn">'.$t['back_to_calc'].'</a>
    </div>
    
    <div class="total-info" id="total-info"></div>
    
    <table class="offers-table">
        <thead>
            <tr>
                <th>#</th>
                <th>'.$t['client_name'].'</th>
                <th>'.$t['brand'].' / '.$t['model'].'</th>
                <th>'.$t['year_vehicle'].'</th>
                <th>'.$t['vin_code'].'</th>
                <th>'.$t['total_col'].'</th>
                <th>'.$t['date_col'].'</th>
                <th>'.$t['actions_col'].'</th>
            </tr>
        </thead>
        <tbody id="offers-tbody">
            <tr>
                <td colspan="8" class="empty-state">
                    <div class="icon">⏳</div>
                    <p>Se încarcă...</p>
                </td>
            </tr>
        </tbody>
    </table>
    
    <div class="pagination" id="pagination"></div>
</div>

<script>
(function() {
    let currentPage = 1;
    
    function loadOffers(page = 1) {
        currentPage = page;
        
        fetch("/ajax.php", {
            method: "POST",
            headers: { "Content-Type": "application/x-www-form-urlencoded" },
            body: "tp=adm&pg=calculator&fn=get_offers&page=" + page
        })
        .then(res => res.json())
        .then(data => {
            if (data.success) {
                renderOffers(data.offers, data.total, data.pages, data.current_page);
            } else {
                document.getElementById("offers-tbody").innerHTML = `
                    <tr>
                        <td colspan="8" class="empty-state">
                            <div class="icon">❌</div>
                            <p>Eroare la încărcare: ${data.error || "Unknown error"}</p>
                        </td>
                    </tr>
                `;
            }
        })
        .catch(err => {
            document.getElementById("offers-tbody").innerHTML = `
                <tr>
                    <td colspan="8" class="empty-state">
                        <div class="icon">❌</div>
                        <p>Eroare la încărcare</p>
                    </td>
                </tr>
            `;
        });
    }
    
    function renderOffers(offers, total, pages, currentPage) {
        const tbody = document.getElementById("offers-tbody");
        const totalInfo = document.getElementById("total-info");
        const pagination = document.getElementById("pagination");
        
        totalInfo.textContent = `Total: ${total} oferte`;
        
        if (offers.length === 0) {
            tbody.innerHTML = `
                <tr>
                    <td colspan="8" class="empty-state">
                        <div class="icon">📭</div>
                        <p>'.$t['no_usage_data'].'</p>
                    </td>
                </tr>
            `;
            pagination.innerHTML = "";
            return;
        }
        
        let html = "";
        offers.forEach((offer, index) => {
            const calcData = JSON.parse(offer.calculation_data || "{}");
            const totalMdl = calcData.total ? calcData.total.mdl : "0";
            const totalEur = calcData.total ? calcData.total.eur : "0";
            const date = new Date(offer.created_at).toLocaleDateString("ro-RO");
            
            html += `
                <tr data-id="${offer.id}">
                    <td>${offer.id}</td>
                    <td class="client-name">${offer.client_name}</td>
                    <td class="vehicle-info">${offer.brand} ${offer.model}</td>
                    <td>${offer.year}</td>
                    <td class="vin-code">${offer.vin || "-"}</td>
                    <td><strong>${parseInt(totalMdl).toLocaleString("ro-MD")}</strong> MDL</td>
                    <td class="date-col">${date}</td>
                    <td class="actions">
                        <button class="btn-pdf" onclick="generatePDF(${offer.id})">📄 PDF</button>
                        <button class="btn-delete" onclick="deleteOffer(${offer.id})">🗑️</button>
                    </td>
                </tr>
            `;
        });
        tbody.innerHTML = html;
        
        // Render pagination
        let paginationHtml = "";
        if (pages > 1) {
            paginationHtml += `<button onclick="loadOffers(${currentPage - 1})" ${currentPage === 1 ? "disabled" : ""}>← Prev</button>`;
            
            for (let i = 1; i <= pages; i++) {
                if (i === 1 || i === pages || (i >= currentPage - 2 && i <= currentPage + 2)) {
                    paginationHtml += `<button onclick="loadOffers(${i})" class="${i === currentPage ? "active" : ""}">${i}</button>`;
                } else if (i === currentPage - 3 || i === currentPage + 3) {
                    paginationHtml += `<button disabled>...</button>`;
                }
            }
            
            paginationHtml += `<button onclick="loadOffers(${currentPage + 1})" ${currentPage === pages ? "disabled" : ""}>Next →</button>`;
        }
        pagination.innerHTML = paginationHtml;
    }
    
    window.loadOffers = loadOffers;
    
    window.deleteOffer = function(offerId) {
        if (!confirm("Sigur doriți să ștergeți această ofertă?")) {
            return;
        }
        
        fetch("/ajax.php", {
            method: "POST",
            headers: { "Content-Type": "application/x-www-form-urlencoded" },
            body: "tp=adm&pg=calculator&fn=delete_offer&offer_id=" + offerId
        })
        .then(res => res.json())
        .then(data => {
            if (data.success) {
                loadOffers(currentPage);
            } else {
                alert("Eroare la ștergere: " + (data.error || "Unknown error"));
            }
        })
        .catch(err => {
            alert("Eroare la ștergere");
        });
    };
    
    // Load offers on page load
    loadOffers(1);
    
    // Store offers data for PDF generation
    let offersData = {};
    
    window.generatePDF = function(offerId) {
        // Get offer from server
        fetch("/ajax.php", {
            method: "POST",
            headers: { "Content-Type": "application/x-www-form-urlencoded" },
            body: "tp=adm&pg=calculator&fn=get_offer&offer_id=" + offerId
        })
        .then(res => res.json())
        .then(data => {
            if (data.success && data.offer) {
                createPDF(data.offer);
            } else {
                alert("Eroare la încărcare ofertă");
            }
        })
        .catch(err => {
            alert("Eroare la încărcare ofertă");
        });
    };
    
    function createPDF(offer) {
        const { jsPDF } = window.jspdf;
        const doc = new jsPDF();
        const pageWidth = doc.internal.pageSize.getWidth();
        let y = 20;
        
        const calcData = JSON.parse(offer.calculation_data || "{}");
        
        // Remove diacritics function
        function removeDiacritics(str) {
            return str.normalize("NFD").replace(/[\u0300-\u036f]/g, "");
        }
        
        function formatNumber(num) {
            return Math.round(num).toLocaleString("ro-MD");
        }
        
        // Title
        doc.setFontSize(16);
        doc.setFont("helvetica", "bold");
        doc.text("OFERTA COMERCIALA", pageWidth / 2, y, { align: "center" });
        y += 10;
        
        // Vehicle info
        doc.setFontSize(12);
        doc.setFont("helvetica", "normal");
        const vehicleInfo = removeDiacritics(offer.brand + " " + offer.model + " " + offer.year + (offer.vin ? " | VIN: " + offer.vin : ""));
        doc.text(vehicleInfo, pageWidth / 2, y, { align: "center" });
        y += 8;
        
        // Client name
        doc.setFontSize(10);
        doc.text(removeDiacritics("Client: " + offer.client_name), pageWidth / 2, y, { align: "center" });
        y += 5;
        
        // Date
        doc.text(new Date(offer.created_at).toLocaleDateString("ro-RO"), pageWidth / 2, y, { align: "center" });
        y += 10;
        
        // Line
        doc.setDrawColor(200);
        doc.line(20, y, pageWidth - 20, y);
        y += 10;
        
        // Results
        const labels = {
            value: "Valoare auto (MDL)",
            excise: "Acciza",
            customs: "Taxa vamala",
            damage: "Protectie daune",
            exportDecl: "Declaratie export",
            bank: "Comision bancar",
            auction: "Comision licitatie",
            pollution: "Taxa poluare",
            shipping: "Documente transport",
            accessories: "Accesorii",
            transaction: "Comision tranzactie"
        };
        
        doc.setFontSize(11);
        for (const [key, label] of Object.entries(labels)) {
            if (calcData[key]) {
                doc.setFont("helvetica", "normal");
                doc.text(removeDiacritics(label), 20, y);
                doc.setFont("helvetica", "bold");
                doc.text(formatNumber(parseFloat(calcData[key].mdl || 0)) + " MDL  (" + formatNumber(parseFloat(calcData[key].eur || 0)) + " EUR)", pageWidth - 20, y, { align: "right" });
                y += 8;
            }
        }
        
        y += 5;
        doc.line(20, y, pageWidth - 20, y);
        y += 10;
        
        // Total
        if (calcData.total) {
            doc.setFillColor(226, 0, 26);
            doc.rect(15, y - 5, pageWidth - 30, 12, "F");
            doc.setTextColor(255, 255, 255);
            doc.setFontSize(12);
            doc.text("TOTAL", 20, y + 3);
            doc.text(formatNumber(parseFloat(calcData.total.mdl || 0)) + " MDL  (" + formatNumber(parseFloat(calcData.total.eur || 0)) + " EUR)", pageWidth - 20, y + 3, { align: "right" });
            y += 15;
        }
        
        // Vehicle total
        if (calcData.vehicle) {
            doc.setFillColor(85, 85, 85);
            doc.rect(15, y - 5, pageWidth - 30, 12, "F");
            doc.text("TOTAL VEHICUL", 20, y + 3);
            doc.text(formatNumber(parseFloat(calcData.vehicle.mdl || 0)) + " MDL  (" + formatNumber(parseFloat(calcData.vehicle.eur || 0)) + " EUR)", pageWidth - 20, y + 3, { align: "right" });
        }
        
        doc.setTextColor(0, 0, 0);
        
        // Save/download PDF
        doc.save("oferta_" + offer.id + "_" + offer.brand + "_" + offer.model + ".pdf");
    }
})();
</script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf/2.5.1/jspdf.umd.min.js"></script>';

echo $rtrn;
