<?php defined( '_DOIT' ) or die( 'Restricted access' );

include(__DIR__.'/calc_translate.php');
$t = $calc_trans[$lang_code] ?? $calc_trans['ro'];

$admin_dir = isset($_COOKIE['admin_dir']) ? $_COOKIE['admin_dir'] : 'adminsauto';

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
    
    #catalog-container .search-box {
        margin-bottom: 1.5rem;
    }
    
    #catalog-container .search-box input {
        width: 100%;
        padding: 0.8rem 1rem;
        border: 2px solid #ddd;
        border-radius: 8px;
        font-size: 1rem;
        transition: border-color 0.3s;
    }
    
    #catalog-container .search-box input:focus {
        outline: none;
        border-color: #e2001a;
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
    
    #catalog-container .offers-table .pdf-lang-select {
        padding: 0.4rem;
        border: 1px solid #ddd;
        border-radius: 6px;
        font-size: 0.85rem;
        cursor: pointer;
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
        <a href="/'.$_COOKIE['lang'].'/'.$admin_dir.'/calculator/calc" class="back-btn">'.$t['back_to_calc'].'</a>
    </div>
    
    <div class="search-box">
        <input type="text" id="search-input" placeholder="🔍" onkeyup="searchOffers()" onfocus="this.placeholder=&quot;&quot;" onblur="this.placeholder=&quot;🔍&quot;">
    </div>
    
    <div class="total-info" id="total-info"></div>
    
    <table class="offers-table">
        <thead>
            <tr>
                <th>'.$t['client_name'].'</th>
                <th>'.$t['brand'].' / '.$t['model'].'</th>
                <th>'.$t['year_vehicle'].'</th>
                <th>'.$t['bodywork'].'</th>
                <th>'.$t['mileage'].'</th>
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
    let searchTimeout = null;
    
    window.searchOffers = function() {
        clearTimeout(searchTimeout);
        searchTimeout = setTimeout(() => {
            loadOffers(1);
        }, 300);
    };
    
    function loadOffers(page = 1) {
        currentPage = page;
        const search = document.getElementById("search-input").value.trim();
        
        fetch("/ajax.php", {
            method: "POST",
            headers: { "Content-Type": "application/x-www-form-urlencoded" },
            body: "tp=adm&pg=calculator&fn=get_offers&page=" + page + "&search=" + encodeURIComponent(search)
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
                    <td class="client-name">${offer.client_name}</td>
                    <td class="vehicle-info">${offer.brand} ${offer.model}</td>
                    <td>${offer.year}</td>
                    <td>${offer.bodywork || "-"}</td>
                    <td>${offer.mileage ? parseInt(offer.mileage).toLocaleString("ro-MD") + " km" : "-"}</td>
                    <td><strong>${parseInt(totalMdl).toLocaleString("ro-MD")}</strong> MDL</td>
                    <td class="date-col">${date}</td>
                    <td class="actions">
                        <select class="pdf-lang-select" id="lang-${offer.id}">
                            <option value="ro">RO</option>
                            <option value="ru">RU</option>
                            <option value="en">EN</option>
                        </select>
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
    
    // PDF translations - same as calc.php
    const pdfTranslations = {
        ro: {
            results: "Ofertă comercială",
            client: "Client",
            value_mdl: "Valoarea in vama (MDL)",
            excise: "Acciza",
            customs_duty: "Taxa proceduri vamale",
            damage_protection: "Protectie impotriva daunelor",
            export_declaration: "Declaratia de export (MRN)",
            bank_commission: "Comision bancar SWIFT",
            auction_commission: "Comision licitatie",
            pollution_tax: "Taxa de poluare",
            shipping_docs: "Livrarea documentelor",
            accessories: "Accesorii",
            transaction_commission: "Comision pentru tranzactie",
            total: "TOTAL COSTURI VAMUIRE",
            vehicle_total: "SUMA TOTALA VEHICUL"
        },
        ru: {
            results: "Коммерческое предложение",
            client: "Клиент",
            value_mdl: "Таможенная стоимость (MDL)",
            excise: "Акциз",
            customs_duty: "Сбор за таможенные процедуры",
            damage_protection: "Защита от повреждений",
            export_declaration: "Декларация экспорта (MRN)",
            bank_commission: "Банковская комиссия SWIFT",
            auction_commission: "Комиссия аукциона",
            pollution_tax: "Налог на загрязнение",
            shipping_docs: "Доставка документов",
            accessories: "Аксессуары",
            transaction_commission: "Комиссия за транзакцию",
            total: "ИТОГО РАСХОДЫ НА РАСТАМОЖКУ",
            vehicle_total: "ОБЩАЯ СУММА ЗА АВТОМОБИЛЬ"
        },
        en: {
            results: "Commercial Offer",
            client: "Client",
            value_mdl: "Customs Value (MDL)",
            excise: "Excise Tax",
            customs_duty: "Customs Duty",
            damage_protection: "Damage Protection",
            export_declaration: "Export Declaration (MRN)",
            bank_commission: "SWIFT Bank Commission",
            auction_commission: "Auction Commission",
            pollution_tax: "Pollution Tax",
            shipping_docs: "Document Delivery",
            accessories: "Accessories",
            transaction_commission: "Transaction Commission",
            total: "TOTAL CUSTOMS COSTS",
            vehicle_total: "TOTAL VEHICLE COST"
        }
    };
    
    window.generatePDF = function(offerId) {
        // Get selected language from dropdown
        const lang = document.getElementById("lang-" + offerId).value;
        
        // Get offer from server
        fetch("/ajax.php", {
            method: "POST",
            headers: { "Content-Type": "application/x-www-form-urlencoded" },
            body: "tp=adm&pg=calculator&fn=get_offer&offer_id=" + offerId
        })
        .then(res => res.json())
        .then(data => {
            if (data.success && data.offer) {
                createPDF(data.offer, lang);
            } else {
                alert("Eroare la încărcare ofertă");
            }
        })
        .catch(err => {
            alert("Eroare la încărcare ofertă");
        });
    };
    
    function createPDF(offer, lang) {
        const t = pdfTranslations[lang] || pdfTranslations.ro;
        const calcData = JSON.parse(offer.calculation_data || "{}");
        
        function formatNumber(num) {
            return Math.round(num).toLocaleString("ro-MD");
        }
        
        const values = {
            value: calcData.value || {mdl: 0, eur: 0},
            excise: calcData.excise || {mdl: 0, eur: 0},
            customs: calcData.customs || {mdl: 0, eur: 0},
            damage: calcData.damage || {mdl: 0, eur: 0},
            exportDecl: calcData.exportDecl || {mdl: 0, eur: 0},
            bank: calcData.bank || {mdl: 0, eur: 0},
            auction: calcData.auction || {mdl: 0, eur: 0},
            pollution: calcData.pollution || {mdl: 0, eur: 0},
            shipping: calcData.shipping || {mdl: 0, eur: 0},
            accessories: calcData.accessories || {mdl: 0, eur: 0},
            transaction: calcData.transaction || {mdl: 0, eur: 0},
            total: calcData.total || {mdl: 0, eur: 0},
            vehicle: calcData.vehicle || {mdl: 0, eur: 0}
        };
        
        const fileName = (offer.client_name + "_" + offer.brand + "_" + offer.model + "_" + offer.year)
            .replace(/\s+/g, "_")
            .replace(/[^a-zA-Z0-9_]/g, "")
            + ".pdf";
        
        // For Russian - use html2canvas (supports Cyrillic)
        if (lang === "ru") {
            const pdfContent = document.createElement("div");
            pdfContent.id = "pdf-temp-content";
            pdfContent.style.cssText = "position:absolute;left:-9999px;width:700px;padding:40px;font-family:Arial,sans-serif;background:#fff;";
            pdfContent.innerHTML = `
                <h1 style="text-align:center;font-size:18px;margin-bottom:10px;font-weight:bold;">${t.results}</h1>
                <p style="text-align:center;font-size:14px;margin-bottom:5px;">${offer.brand} ${offer.model} ${offer.year}</p>
                <p style="text-align:center;color:#666;margin-bottom:5px;">${t.client}: ${offer.client_name}</p>
                <p style="text-align:center;color:#666;margin-bottom:20px;font-size:10px;">${new Date(offer.created_at).toLocaleDateString("ro-RO")}</p>
                <hr style="border:none;border-top:1px solid #ccc;margin-bottom:20px;">
                <table style="width:100%;border-collapse:collapse;font-size:11px;">
                    <tr><td style="padding:8px 0;">${t.value_mdl}</td><td style="text-align:right;font-weight:bold;">${formatNumber(parseFloat(values.value.mdl))} MDL  (${formatNumber(parseFloat(values.value.eur))} EUR)</td></tr>
                    <tr><td style="padding:8px 0;">${t.excise}</td><td style="text-align:right;font-weight:bold;">${formatNumber(parseFloat(values.excise.mdl))} MDL  (${formatNumber(parseFloat(values.excise.eur))} EUR)</td></tr>
                    <tr><td style="padding:8px 0;">${t.customs_duty}</td><td style="text-align:right;font-weight:bold;">${formatNumber(parseFloat(values.customs.mdl))} MDL  (${formatNumber(parseFloat(values.customs.eur))} EUR)</td></tr>
                    <tr><td style="padding:8px 0;">${t.damage_protection}</td><td style="text-align:right;font-weight:bold;">${formatNumber(parseFloat(values.damage.mdl))} MDL  (${formatNumber(parseFloat(values.damage.eur))} EUR)</td></tr>
                    <tr><td style="padding:8px 0;">${t.export_declaration}</td><td style="text-align:right;font-weight:bold;">${formatNumber(parseFloat(values.exportDecl.mdl))} MDL  (${formatNumber(parseFloat(values.exportDecl.eur))} EUR)</td></tr>
                    <tr><td style="padding:8px 0;">${t.bank_commission}</td><td style="text-align:right;font-weight:bold;">${formatNumber(parseFloat(values.bank.mdl))} MDL  (${formatNumber(parseFloat(values.bank.eur))} EUR)</td></tr>
                    <tr><td style="padding:8px 0;">${t.auction_commission}</td><td style="text-align:right;font-weight:bold;">${formatNumber(parseFloat(values.auction.mdl))} MDL  (${formatNumber(parseFloat(values.auction.eur))} EUR)</td></tr>
                    <tr><td style="padding:8px 0;">${t.pollution_tax}</td><td style="text-align:right;font-weight:bold;">${formatNumber(parseFloat(values.pollution.mdl))} MDL  (${formatNumber(parseFloat(values.pollution.eur))} EUR)</td></tr>
                    <tr><td style="padding:8px 0;">${t.shipping_docs}</td><td style="text-align:right;font-weight:bold;">${formatNumber(parseFloat(values.shipping.mdl))} MDL  (${formatNumber(parseFloat(values.shipping.eur))} EUR)</td></tr>
                    <tr><td style="padding:8px 0;">${t.accessories}</td><td style="text-align:right;font-weight:bold;">${formatNumber(parseFloat(values.accessories.mdl))} MDL  (${formatNumber(parseFloat(values.accessories.eur))} EUR)</td></tr>
                    <tr><td style="padding:8px 0;">${t.transaction_commission}</td><td style="text-align:right;font-weight:bold;">${formatNumber(parseFloat(values.transaction.mdl))} MDL  (${formatNumber(parseFloat(values.transaction.eur))} EUR)</td></tr>
                </table>
                <div style="background:#e2001a;color:#fff;padding:12px 15px;margin-top:20px;display:flex;justify-content:space-between;font-weight:bold;font-size:12px;">
                    <span>${t.total}</span>
                    <span>${formatNumber(parseFloat(values.total.mdl))} MDL  (${formatNumber(parseFloat(values.total.eur))} EUR)</span>
                </div>
                <div style="background:#555;color:#fff;padding:12px 15px;display:flex;justify-content:space-between;font-weight:bold;font-size:12px;">
                    <span>${t.vehicle_total}</span>
                    <span>${formatNumber(parseFloat(values.vehicle.mdl))} MDL  (${formatNumber(parseFloat(values.vehicle.eur))} EUR)</span>
                </div>
            `;
            document.body.appendChild(pdfContent);
            
            html2canvas(pdfContent, { scale: 2, useCORS: true, backgroundColor: "#ffffff" }).then(canvas => {
                const { jsPDF } = window.jspdf;
                const doc = new jsPDF("p", "mm", "a4");
                const imgData = canvas.toDataURL("image/jpeg", 0.92);
                const pageWidth = doc.internal.pageSize.getWidth();
                const imgWidth = pageWidth - 20;
                const imgHeight = (canvas.height * imgWidth) / canvas.width;
                
                doc.addImage(imgData, "JPEG", 10, 10, imgWidth, imgHeight);
                doc.save(fileName);
                
                document.body.removeChild(pdfContent);
            });
            return;
        }
        
        // For RO and EN - use jsPDF text
        function removeDiacritics(str) {
            return str.normalize("NFD").replace(/[\u0300-\u036f]/g, "");
        }
        
        const { jsPDF } = window.jspdf;
        const doc = new jsPDF();
        const pageWidth = doc.internal.pageSize.getWidth();
        let y = 20;
        
        // Title
        doc.setFontSize(16);
        doc.setFont("helvetica", "bold");
        doc.text(removeDiacritics(t.results), pageWidth / 2, y, { align: "center" });
        y += 10;
        
        // Vehicle info
        doc.setFontSize(12);
        doc.setFont("helvetica", "normal");
        const vehicleInfo = removeDiacritics(offer.brand + " " + offer.model + " " + offer.year);
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
        
        // Results - using translations
        const results = [
            { key: "value", label: t.value_mdl },
            { key: "excise", label: t.excise },
            { key: "customs", label: t.customs_duty },
            { key: "damage", label: t.damage_protection },
            { key: "exportDecl", label: t.export_declaration },
            { key: "bank", label: t.bank_commission },
            { key: "auction", label: t.auction_commission },
            { key: "pollution", label: t.pollution_tax },
            { key: "shipping", label: t.shipping_docs },
            { key: "accessories", label: t.accessories },
            { key: "transaction", label: t.transaction_commission }
        ];
        
        doc.setFontSize(11);
        results.forEach(item => {
            if (calcData[item.key]) {
                doc.setFont("helvetica", "normal");
                doc.text(removeDiacritics(item.label), 20, y);
                doc.setFont("helvetica", "bold");
                doc.text(formatNumber(parseFloat(calcData[item.key].mdl || 0)) + " MDL  (" + formatNumber(parseFloat(calcData[item.key].eur || 0)) + " EUR)", pageWidth - 20, y, { align: "right" });
                y += 8;
            }
        });
        
        y += 5;
        doc.line(20, y, pageWidth - 20, y);
        y += 10;
        
        // Total
        if (calcData.total) {
            doc.setFillColor(226, 0, 26);
            doc.rect(15, y - 5, pageWidth - 30, 12, "F");
            doc.setTextColor(255, 255, 255);
            doc.setFontSize(12);
            doc.text(removeDiacritics(t.total), 20, y + 3);
            doc.text(formatNumber(parseFloat(calcData.total.mdl || 0)) + " MDL  (" + formatNumber(parseFloat(calcData.total.eur || 0)) + " EUR)", pageWidth - 20, y + 3, { align: "right" });
            y += 15;
        }
        
        // Vehicle total
        if (calcData.vehicle) {
            doc.setFillColor(85, 85, 85);
            doc.rect(15, y - 5, pageWidth - 30, 12, "F");
            doc.text(removeDiacritics(t.vehicle_total), 20, y + 3);
            doc.text(formatNumber(parseFloat(calcData.vehicle.mdl || 0)) + " MDL  (" + formatNumber(parseFloat(calcData.vehicle.eur || 0)) + " EUR)", pageWidth - 20, y + 3, { align: "right" });
        }
        
        doc.setTextColor(0, 0, 0);
        
        doc.save(fileName);
    }
})();
</script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf/2.5.1/jspdf.umd.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/html2canvas/1.4.1/html2canvas.min.js"></script>';

echo $rtrn;
