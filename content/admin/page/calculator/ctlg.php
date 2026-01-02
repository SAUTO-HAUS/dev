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
    
    #catalog-container .offers-table .btn-edit {
        display: inline-flex;
        align-items: center;
        gap: 0.3rem;
        padding: 0.4rem 0.8rem;
        background: #007bff;
        color: #fff;
        border: none;
        border-radius: 6px;
        font-size: 0.85rem;
        cursor: pointer;
        transition: background 0.3s;
    }
    
    #catalog-container .offers-table .btn-edit:hover {
        background: #0056b3;
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
                        <button class="btn-edit" onclick="editOffer(${offer.id})">✏️ Edit</button>
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
    
    window.editOffer = function(offerId) {
        // Get offer data from server
        fetch("/ajax.php", {
            method: "POST",
            headers: { "Content-Type": "application/x-www-form-urlencoded" },
            body: "tp=adm&pg=calculator&fn=get_offer&offer_id=" + offerId
        })
        .then(res => res.json())
        .then(data => {
            if (data.success && data.offer) {
                // Store offer data in sessionStorage
                sessionStorage.setItem("editOffer", JSON.stringify(data.offer));
                // Redirect to calculator
                window.location.href = "/'.$_COOKIE['lang'].'/'.$admin_dir.'/calculator/calc";
            } else {
                alert("Eroare la încărcare ofertă");
            }
        })
        .catch(err => {
            alert("Eroare la încărcare ofertă");
        });
    };
    
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
    
    // PDF translations - Romanian only
    const t = {
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
    };
    
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
        const calcData = JSON.parse(offer.calculation_data || "{}");
        
        function formatNumber(num) {
            return Math.round(num).toLocaleString("ro-MD");
        }
        
        function removeDiacritics(str) {
            return str
                .replace(/ă/g, "a").replace(/Ă/g, "A")
                .replace(/â/g, "a").replace(/Â/g, "A")
                .replace(/î/g, "i").replace(/Î/g, "I")
                .replace(/ș/g, "s").replace(/Ș/g, "S")
                .replace(/ț/g, "t").replace(/Ț/g, "T")
                .replace(/ş/g, "s").replace(/Ş/g, "S")
                .replace(/ţ/g, "t").replace(/Ţ/g, "T");
        }
        
        function capitalizeWords(str) {
            var words = str.split(" ");
            var result = [];
            for (var i = 0; i < words.length; i++) {
                var word = words[i];
                if (word.length > 0) {
                    result.push(word.charAt(0).toUpperCase() + word.slice(1).toLowerCase());
                }
            }
            return result.join(" ");
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
            polishing: calcData.polishing || {mdl: 0, eur: 0},
            painting: calcData.painting || {mdl: 0, eur: 0},
            total: calcData.total || {mdl: 0, eur: 0},
            vehicle: calcData.vehicle || {mdl: 0, eur: 0}
        };
        
        const fileName = (offer.client_name + "_" + offer.brand + "_" + offer.model + "_" + offer.year)
            .replace(/\s+/g, "_")
            .replace(/[^a-zA-Z0-9_]/g, "")
            + ".pdf";
        
        const { jsPDF } = window.jspdf;
        const doc = new jsPDF();
        const pageWidth = doc.internal.pageSize.getWidth();
        const pageHeight = doc.internal.pageSize.getHeight();
        
        // Load images
        const bgImg = new Image();
        const logoImg = new Image();
        let bgLoaded = false, logoLoaded = false;
        
        bgImg.src = "/content/admin/page/calculator/img/pdf-bg.jpg";
        logoImg.src = "/content/admin/page/calculator/img/logo.png";
        
        function generatePDFWithImages() {
            if (!bgLoaded || !logoLoaded) return;
            
            // === PAGE 1: Background image with logo ===
            // Background: full width, 3% margin top only
            const marginP1 = pageWidth * 0.03;
            const imgY = marginP1;
            const imgWidth = pageWidth;
            const imgHeight = pageHeight * 0.75;
            doc.addImage(bgImg, "JPEG", 0, imgY, imgWidth, imgHeight);
            
            // Logo with black background
            const bgPadding = 8;
            const bgX = marginP1;
            const logoWidth = 50;
            const logoHeight = 20;
            const bgWidth = logoWidth + bgPadding * 2;
            const bgHeight = logoHeight + bgPadding + marginP1;
            doc.setFillColor(0, 0, 0);
            doc.rect(bgX, 0, bgWidth, bgHeight, "F");
            doc.addImage(logoImg, "PNG", bgX + bgPadding, bgPadding, logoWidth, logoHeight);
            
            // Vehicle info on first page (over background)
            doc.setTextColor(255, 255, 255);
            
            // Title "Oferta comerciala" above brand/model
            doc.setFontSize(24);
            doc.setFont("helvetica", "normal");
            doc.text(removeDiacritics("Oferta comerciala"), 20, pageHeight * 0.25);
            
            // MARCA Model, An (brand uppercase, model capitalized)
            doc.setFontSize(38);
            doc.setFont("helvetica", "bold");
            const brandClean = offer.brand.replace(/_/g, " ").toUpperCase();
            const modelClean = capitalizeWords(offer.model.replace(/_/g, " "));
            var vehicleTitle = brandClean + " " + modelClean + ", " + offer.year;
            doc.text(removeDiacritics(vehicleTitle), 20, pageHeight * 0.30);
            
            // Capacitate motor și tip combustibil pe rând nou (aceeași mărime font)
            var engineInfo = "";
            if (offer.cylinder_capacity) {
                engineInfo += offer.cylinder_capacity + " cm³";
            }
            if (offer.fuel_type) {
                if (engineInfo) engineInfo += ", ";
                engineInfo += capitalizeWords(offer.fuel_type.replace(/_/g, " "));
            }
            if (engineInfo) {
                doc.text(removeDiacritics(engineInfo), 20, pageHeight * 0.36);
            }
            
            // Red rectangle bottom right
            const rectWidth = 50;
            const rectHeight = 75;
            const rectX = pageWidth - rectWidth - marginP1;
            const rectY = pageHeight - rectHeight;
            doc.setFillColor(226, 0, 26);
            doc.rect(rectX, rectY, rectWidth, rectHeight, "F");
            
            // Footer info on page 1
            doc.setTextColor(51, 51, 51);
            doc.setFontSize(10);
            const footerY = pageHeight * 0.85;
            doc.setFont("helvetica", "bold");
            doc.text("SAUTO SRL", 20, footerY);
            doc.setFont("helvetica", "normal");
            doc.text("+373 68 68 99 95", 20, footerY + 5);
            doc.text("info@sauto.md", 20, footerY + 10);
            doc.text("Chisinau str Calea Mosilor 11", 20, footerY + 15);
            
            doc.setFont("helvetica", "bold");
            doc.text("CARP DUMITRU", 100, footerY);
            doc.setFont("helvetica", "normal");
            doc.text("Manager vanzari", 100, footerY + 5);
            doc.text("+373 62166880", 100, footerY + 10);
            doc.text("carp@sauto.md", 100, footerY + 15);
            
            // === PAGE 2: Contents (red background) ===
            doc.addPage();
            
            // Red background with 3% margin
            const margin = pageWidth * 0.03;
            doc.setFillColor(226, 0, 26);
            doc.rect(margin, margin, pageWidth - margin * 2, pageHeight - margin * 2, "F");
            
            // Logo with black background (same as page 1)
            doc.setFillColor(0, 0, 0);
            doc.rect(margin, 0, bgWidth, bgHeight, "F");
            doc.addImage(logoImg, "PNG", margin + bgPadding, bgPadding, logoWidth, logoHeight);
            
            // Title "Continut" (bold, centered)
            doc.setTextColor(255, 255, 255);
            doc.setFontSize(28);
            doc.setFont("helvetica", "bold");
            doc.text(removeDiacritics("Continut"), pageWidth / 2, pageHeight * 0.16, { align: "center" });
            
            // Contents list (centered vertically)
            doc.setFontSize(14);
            doc.setFont("helvetica", "normal");
            var totalItems = 6;
            var itemHeight = 22;
            var totalContentHeight = totalItems * itemHeight;
            var contentY = (pageHeight - totalContentHeight) / 2;
            var contentItems = [
                { title: "Despre noi", page: "03" },
                { title: "Specificatia tehnica", page: "04" },
                { title: "Imagini de produs", page: "05" },
                { title: "Pret", page: "06" },
                { title: "Rapoarte si informatii aditionale", page: "07" },
                { title: "Termeni si conditii", page: "08" }
            ];
            
            // Center horizontally - calculate content width and center it
            var maxTitleWidth = 0;
            contentItems.forEach(function(item) {
                var w = doc.getTextWidth(removeDiacritics(item.title));
                if (w > maxTitleWidth) maxTitleWidth = w;
            });
            var dotsWidth = 60;
            var pageNumWidth = 20;
            var totalWidth = maxTitleWidth + dotsWidth + pageNumWidth;
            var leftX = (pageWidth - totalWidth) / 2;
            var rightX = leftX + totalWidth;
            
            contentItems.forEach(function(item) {
                // Title on left
                doc.text(removeDiacritics(item.title), leftX, contentY);
                
                // Calculate dots width
                var titleWidth = doc.getTextWidth(removeDiacritics(item.title));
                var pageNumWidth = doc.getTextWidth(item.page);
                var dotsStartX = leftX + titleWidth + 5;
                var dotsEndX = rightX - pageNumWidth - 5;
                
                // Draw dots
                var dots = "";
                var dotWidth = doc.getTextWidth(". ");
                var numDots = Math.floor((dotsEndX - dotsStartX) / dotWidth);
                for (var d = 0; d < numDots; d++) dots += ". ";
                doc.text(dots, dotsStartX, contentY);
                
                // Page number
                doc.text(item.page, rightX, contentY, { align: "right" });
                contentY += 22;
            });
            
            // === PAGE 3: Calculation details ===
            doc.addPage();
            doc.setTextColor(0, 0, 0);
            let y = 25;
            
            // Title
            doc.setFontSize(18);
            doc.setFont("helvetica", "bold");
            doc.text(removeDiacritics(t.results), pageWidth / 2, y, { align: "center" });
            y += 12;
            
            // Vehicle info (replace underscores with spaces and capitalize each word)
            doc.setFontSize(12);
            doc.setFont("helvetica", "normal");
            const brandClean2 = capitalizeWords(offer.brand.replace(/_/g, " "));
            const modelClean2 = capitalizeWords(offer.model.replace(/_/g, " "));
            doc.text(removeDiacritics(brandClean2 + " " + modelClean2 + " " + offer.year), pageWidth / 2, y, { align: "center" });
            y += 6;
            
            // Client
            doc.setFontSize(10);
            doc.text(removeDiacritics("Client: " + offer.client_name), pageWidth / 2, y, { align: "center" });
            y += 5;
            
            // Date
            doc.text(new Date(offer.created_at).toLocaleDateString("ro-RO"), pageWidth / 2, y, { align: "center" });
            y += 12;
            
            // Line
            doc.setDrawColor(200);
            doc.line(20, y, pageWidth - 20, y);
            y += 10;
            
            // Results
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
            
            // Add polishing and painting if they have values
            if (calcData.polishing && (parseFloat(calcData.polishing.mdl) > 0 || parseFloat(calcData.polishing.eur) > 0)) {
                results.push({ key: "polishing", label: "Polizare si curatire chimica" });
            }
            if (calcData.painting && (parseFloat(calcData.painting.mdl) > 0 || parseFloat(calcData.painting.eur) > 0)) {
                results.push({ key: "painting", label: "Vopsire" });
            }
            
            doc.setFontSize(11);
            results.forEach(item => {
                const data = calcData[item.key];
                if (data && (parseFloat(data.mdl) > 0 || parseFloat(data.eur) > 0)) {
                    doc.setFont("helvetica", "normal");
                    doc.text(removeDiacritics(item.label), 20, y);
                    doc.setFont("helvetica", "bold");
                    doc.text(formatNumber(parseFloat(data.mdl || 0)) + " MDL  (" + formatNumber(parseFloat(data.eur || 0)) + " EUR)", pageWidth - 20, y, { align: "right" });
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
        
        bgImg.onload = function() { bgLoaded = true; generatePDFWithImages(); };
        logoImg.onload = function() { logoLoaded = true; generatePDFWithImages(); };
        bgImg.onerror = function() { bgLoaded = true; generatePDFWithImages(); };
        logoImg.onerror = function() { logoLoaded = true; generatePDFWithImages(); };
    }
})();
</script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf/2.5.1/jspdf.umd.min.js"></script>';

echo $rtrn;
