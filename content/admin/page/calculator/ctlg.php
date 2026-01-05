<?php defined( '_DOIT' ) or die( 'Restricted access' );

include(__DIR__.'/calc_translate.php');
$t = $calc_trans[$lang_code] ?? $calc_trans['ro'];

$admin_dir = isset($_COOKIE['admin_dir']) ? $_COOKIE['admin_dir'] : 'adminsauto';

$logged_user_name = isset($user_name) ? $user_name : (isset($_SESSION['user_name']) ? $_SESSION['user_name'] : 'Manager');

$rtrn = '
<style>
    #catalog-container {
        max-width: 100%;
        margin: 0;
        padding: 1rem 1.5rem;
        font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif;
        background: #f5f5f5;
        min-height: 100vh;
    }
    
    #catalog-container .catalog-header {
        display: flex;
        justify-content: space-between;
        align-items: center;
        margin-bottom: 1rem;
    }
    
    #catalog-container .catalog-header h1 {
        margin: 0;
        font-size: 1.4rem;
        color: #333;
        font-weight: 600;
    }
    
    #catalog-container .back-btn {
        display: inline-flex;
        align-items: center;
        gap: 0.4rem;
        padding: 0.5rem 1rem;
        background: #e2001a;
        color: #fff;
        text-decoration: none;
        border-radius: 6px;
        font-size: 0.85rem;
        font-weight: 500;
        transition: all 0.2s;
    }
    
    #catalog-container .back-btn:hover {
        background: #c00017;
        transform: translateY(-1px);
    }
    
    #catalog-container .search-box {
        margin-bottom: 1rem;
    }
    
    #catalog-container .search-box input {
        width: 300px;
        padding: 0.5rem 0.8rem;
        border: 1px solid #ddd;
        border-radius: 6px;
        font-size: 0.9rem;
        background: #fff;
        transition: all 0.2s;
    }
    
    #catalog-container .search-box input:focus {
        outline: none;
        border-color: #e2001a;
        box-shadow: 0 0 0 3px rgba(226,0,26,0.1);
    }
    
    #catalog-container .offers-table {
        width: 100%;
        border-collapse: separate;
        border-spacing: 0;
        background: #fff;
        border-radius: 8px;
        overflow: hidden;
        box-shadow: 0 1px 3px rgba(0,0,0,0.08);
        font-size: 0.85rem;
    }
    
    #catalog-container .offers-table th,
    #catalog-container .offers-table td {
        padding: 0.6rem 0.8rem;
        text-align: left;
        border-bottom: 1px solid #f0f0f0;
        white-space: nowrap;
    }
    
    #catalog-container .offers-table th {
        background: #fafafa;
        font-weight: 600;
        color: #666;
        font-size: 0.75rem;
        text-transform: uppercase;
        letter-spacing: 0.5px;
    }
    
    #catalog-container .offers-table tbody tr {
        transition: background 0.15s;
    }
    
    #catalog-container .offers-table tbody tr:hover {
        background: #fafafa;
    }
    
    #catalog-container .offers-table .vehicle-info {
        font-weight: 600;
        color: #222;
    }
    
    #catalog-container .offers-table .client-name {
        color: #555;
        max-width: 150px;
        overflow: hidden;
        text-overflow: ellipsis;
    }
    
    #catalog-container .offers-table .date-col {
        font-size: 0.8rem;
        color: #888;
    }
    
    #catalog-container .offers-table .actions {
        display: flex;
        gap: 0.3rem;
    }
    
    #catalog-container .offers-table button {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        padding: 0.5rem 0.8rem;
        border: 1px solid #ddd;
        border-radius: 6px;
        font-size: 0.9rem;
        cursor: pointer;
        transition: all 0.2s;
        min-width: 38px;
        background: #fff;
        color: #333;
    }
    
    #catalog-container .offers-table button:hover {
        background: #e2001a;
        color: #fff;
        border-color: #e2001a;
    }
    
    #catalog-container .offers-table .btn-pdf {
        text-decoration: none;
    }
    
    #catalog-container .offers-table .btn-ai {
        min-width: 44px;
    }
    
    #catalog-container .offers-table .btn-ai:disabled {
        opacity: 0.6;
        cursor: wait;
    }
    
    #catalog-container .empty-state {
        text-align: center;
        padding: 3rem 2rem;
        color: #999;
    }
    
    #catalog-container .empty-state .icon {
        font-size: 2.5rem;
        margin-bottom: 0.5rem;
    }
    
    #catalog-container .pagination {
        display: flex;
        justify-content: center;
        gap: 0.3rem;
        margin-top: 1rem;
    }
    
    #catalog-container .pagination button {
        padding: 0.4rem 0.8rem;
        border: 1px solid #ddd;
        background: #fff;
        border-radius: 4px;
        cursor: pointer;
        font-size: 0.85rem;
        transition: all 0.15s;
    }
    
    #catalog-container .pagination button:hover {
        background: #f5f5f5;
        border-color: #ccc;
    }
    
    #catalog-container .pagination button.active {
        background: #e2001a;
        color: #fff;
        border-color: #e2001a;
    }
    
    #catalog-container .pagination button:disabled {
        opacity: 0.4;
        cursor: not-allowed;
    }
    
    #catalog-container .total-info {
        color: #888;
        font-size: 0.8rem;
        margin-bottom: 0.5rem;
    }
    
    @media (max-width: 768px) {
        #catalog-container {
            padding: 0.8rem;
        }
        
        #catalog-container .catalog-header {
            flex-direction: column;
            gap: 0.8rem;
            text-align: center;
        }
        
        #catalog-container .search-box input {
            width: 100%;
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
                <th>'.$t['date_col'].'</th>
                <th>'.$t['client_name'].'</th>
                <th>'.$t['brand'].' / '.$t['model'].'</th>
                <th>'.$t['year_vehicle'].'</th>
                <th>'.$t['bodywork'].'</th>
                <th>'.$t['mileage'].'</th>
                <th>'.$t['total_col'].'</th>
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
    
    // Translations for bodywork codes
    const bodyworkTranslations = {
        "sdn": "Sedan", "suv": "SUV", "hbk": "Hatchback", "unv": "Universal",
        "cup": "Coupe", "crv": "Crossover", "mnv": "Minivan", "pkp": "Pickup",
        "van": "Furgon", "mbs": "Microbus", "cbr": "Cabriolet", "cmb": "Combi",
        "rod": "Roadster", "frg": "Frigider", "crr": "Purtator"
    };
    
    function translateValue(val) {
        if (!val) return "";
        var key = String(val).toLowerCase().trim();
        return bodyworkTranslations[key] || val.replace(/_/g, " ");
    }
    
    function capitalizeWords(str) {
        return str.split(" ").map(word => word.charAt(0).toUpperCase() + word.slice(1).toLowerCase()).join(" ");
    }
    
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
            const hasAI = offer.ai_features && offer.ai_features !== "null" && offer.ai_features !== "";
            const aiButtonText = hasAI ? "✅" : "🤖";
            
            const brandDisplay = capitalizeWords((offer.brand || "").replace(/_/g, " "));
            const modelDisplay = capitalizeWords((offer.model || "").replace(/_/g, " "));
            const bodyworkDisplay = translateValue(offer.bodywork) || "-";
            
            html += `
                <tr data-id="${offer.id}">
                    <td class="date-col">${date}</td>
                    <td class="client-name">${offer.client_name}</td>
                    <td class="vehicle-info">${brandDisplay} ${modelDisplay}</td>
                    <td>${offer.year}</td>
                    <td>${bodyworkDisplay}</td>
                    <td>${offer.mileage ? parseInt(offer.mileage).toLocaleString("ro-MD") + " km" : "-"}</td>
                    <td><strong>${parseInt(totalMdl).toLocaleString("ro-MD")}</strong> MDL</td>
                    <td class="actions">
                        <button class="btn-edit" onclick="editOffer(${offer.id})">✏️ Edit</button>
                        <button class="btn-ai" onclick="generateAIFeatures(this, ${offer.id})" data-text="${aiButtonText}" data-loading="⏳" data-success="✅" title="Generează Siguranță și Confort cu AI">${aiButtonText}</button>
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
                alert("Error loading offer");
            }
        })
        .catch(err => {
            alert("Error loading offer");
        });
    };
    
    window.deleteOffer = function(offerId) {
        if (!confirm("Are you sure you want to delete this offer?")) {
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
                alert("Error deleting: " + (data.error || "Unknown error"));
            }
        })
        .catch(err => {
            alert("Error deleting offer");
        });
    };
    
    window.generateAIFeatures = function(btn, offerId) {
        // Get offer data first
        fetch("/ajax.php", {
            method: "POST",
            headers: { "Content-Type": "application/x-www-form-urlencoded" },
            body: "tp=adm&pg=calculator&fn=get_offer&offer_id=" + offerId
        })
        .then(res => res.json())
        .then(data => {
            if (data.success && data.offer) {
                const offer = data.offer;
                btn.disabled = true;
                btn.textContent = btn.dataset.loading;
                
                // Call AI to generate features
                fetch("/ajax.php", {
                    method: "POST",
                    headers: { "Content-Type": "application/x-www-form-urlencoded" },
                    body: "tp=adm&pg=calculator&fn=ai_generate_features" +
                          "&offer_id=" + offerId +
                          "&brand=" + encodeURIComponent(offer.brand) +
                          "&model=" + encodeURIComponent(offer.model) +
                          "&year=" + encodeURIComponent(offer.year) +
                          "&fuel_type=" + encodeURIComponent(offer.fuel_type || "") +
                          "&bodywork=" + encodeURIComponent(offer.bodywork || "")
                })
                .then(res => res.json())
                .then(aiData => {
                    if (aiData.success) {
                        // Save features to offer
                        const features = {
                            safety: aiData.safety,
                            comfort: aiData.comfort
                        };
                        
                        // Update offer with features
                        fetch("/ajax.php", {
                            method: "POST",
                            headers: { "Content-Type": "application/x-www-form-urlencoded" },
                            body: "tp=adm&pg=calculator&fn=update_offer_features&offer_id=" + offerId +
                                  "&features=" + encodeURIComponent(JSON.stringify(features))
                        })
                        .then(res => res.json())
                        .then(updateData => {
                            btn.disabled = false;
                            if (updateData.success) {
                                btn.textContent = btn.dataset.success;
                                btn.style.background = "#28a745";
                                setTimeout(() => {
                                    btn.textContent = btn.dataset.text;
                                    btn.style.background = "";
                                }, 2000);
                            } else {
                                btn.textContent = "❌ Eroare";
                                btn.style.background = "#dc3545";
                                setTimeout(() => {
                                    btn.textContent = btn.dataset.text;
                                    btn.style.background = "";
                                }, 2000);
                            }
                        });
                    } else {
                        btn.disabled = false;
                        btn.textContent = "❌ Eroare";
                        btn.style.background = "#dc3545";
                        setTimeout(() => {
                            btn.textContent = btn.dataset.text;
                            btn.style.background = "";
                        }, 2000);
                    }
                })
                .catch(err => {
                    btn.disabled = false;
                    btn.textContent = "❌ Eroare";
                    btn.style.background = "#dc3545";
                    setTimeout(() => {
                        btn.textContent = btn.dataset.text;
                        btn.style.background = "";
                    }, 2000);
                });
            } else {
                btn.textContent = "❌ Eroare";
                btn.style.background = "#dc3545";
                setTimeout(() => {
                    btn.textContent = btn.dataset.text;
                    btn.style.background = "";
                }, 2000);
            }
        })
        .catch(err => {
            btn.textContent = "❌ Eroare";
            btn.style.background = "#dc3545";
            setTimeout(() => {
                btn.textContent = btn.dataset.text;
                btn.style.background = "";
            }, 2000);
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
                // Check if AI features (safety & comfort) have been generated
                var aiFeatures = null;
                try {
                    aiFeatures = JSON.parse(data.offer.ai_features || "null");
                } catch(e) {}
                
                if (!aiFeatures || !aiFeatures.safety || !aiFeatures.comfort || aiFeatures.safety.length === 0 || aiFeatures.comfort.length === 0) {
                    alert("Для экспорта PDF сначала необходимо сгенерировать Безопасность и Комфорт с помощью AI 🤖!");
                    return;
                }
                
                createPDF(data.offer);
            } else {
                alert("Error loading offer");
            }
        })
        .catch(err => {
            alert("Error loading offer");
        });
    };
    
    function createPDF(offer) {
        const calcData = JSON.parse(offer.calculation_data || "{}");
        const loggedUserName = "'.addslashes($logged_user_name).'";
        
        // Allowed users with their contact info
        const allowedUsers = {
            "CARP DUMITRU": { phone: "+373 62166880", email: "carp@sauto.md" },
            "MALITOV DANIEL": { phone: "+373 62166881", email: "danielmalitov@sauto.md" },
            "PORTARESCU ADRIAN": { phone: "+373 62166882", email: "allcars@sauto.md" }
        };
        const userKey = loggedUserName.toUpperCase().trim();
        const userInfo = allowedUsers[userKey] || null;
        
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
        
        // Translations for short codes to full text
        var translations = {
            // Transmission / Cutia de viteze
            "tpt": "Tiptronic",
            "atm": "Automata",
            "mnl": "Mecanica",
            "rbt": "Robotizata",
            "vrr": "Variator",
            // Additional transmission aliases
            "aut": "Automata",
            "auto": "Automata",
            "automat": "Automata",
            "man": "Mecanica",
            "manual": "Mecanica",
            "manuala": "Manuala",
            // Drive type / Tractiunea
            "44": "4x4",
            "re": "Din spate",
            "fr": "Din fata",
            // Additional drive type aliases
            "fata": "Din fata",
            "front": "Din fata",
            "fwd": "Din fata",
            "spate": "Din spate",
            "rear": "Din spate",
            "rwd": "Din spate",
            "4x4": "4x4",
            "awd": "4x4",
            "4wd": "4x4",
            "integral": "Integral",
            // Colors / Culoarea
            "l_grn": "Verde deschis",
            "blu": "Albastru",
            "brn": "Brun",
            "cmn": "Purpuriu",
            "cml": "Cameleon",
            "bge": "Bej",
            "wht": "Alb",
            "vns": "Rosu visiniu",
            "azr": "Albastru",
            "ylw": "Galben",
            "grn": "Verde",
            "gld": "Aur",
            "red": "Rosu",
            "orn": "Portocalie",
            "pnk": "Roz",
            "slv": "Argint",
            "gra": "Gri",
            "d_grn": "Verde inchis",
            "prp": "Violet",
            "blk": "Negru",
            "wap": "Asfalt umed",
            "snd": "Nisip",
            // Additional color aliases
            "white": "Alb",
            "black": "Negru",
            "blue": "Albastru",
            "green": "Verde",
            "yellow": "Galben",
            "orange": "Portocalie",
            "silver": "Argint",
            "gray": "Gri",
            "grey": "Gri",
            "brown": "Brun",
            "beige": "Bej",
            // Bodywork / Caroserie
            "sdn": "Sedan",
            "suv": "SUV",
            "hbk": "Hatchback",
            "unv": "Universal",
            "cup": "Coupe",
            "crv": "Crossover",
            "mnv": "Minivan",
            "pkp": "Pickup",
            "van": "Furgon",
            "mbs": "Microbus",
            "cbr": "Cabriolet",
            "cmb": "Combi",
            "rod": "Roadster",
            "frg": "Frigider",
            "crr": "Purtator",
            // Additional bodywork aliases
            "sedan": "Sedan",
            "hatchback": "Hatchback",
            "universal": "Universal",
            "coupe": "Coupe",
            "crossover": "Crossover",
            "pickup": "Pickup",
            "cabriolet": "Cabriolet",
            "combi": "Combi",
            "roadster": "Roadster",
            // Additional transmission
            "tiptronic": "Tiptronic",
            "cvt": "CVT",
            "dsg": "DSG",
            // Fuel type / Tip combustibil
            "benzina": "Benzina",
            "diesel": "Diesel",
            "hybrid": "Hybrid",
            "plugin_hybrid": "Plug-in Hybrid",
            "electric": "Electric"
        };
        
        function translateValue(val) {
            if (!val) return "";
            var key = String(val).toLowerCase().trim();
            return translations[key] || capitalizeWords(String(val).replace(/_/g, " "));
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
        
        const clientNameClean = (offer.client_name || "").replace(/\s+/g, "_").replace(/[^a-zA-Z0-9_]/g, "");
        const fileName = "offer_" + clientNameClean + "_" + offer.id + ".pdf";
        
        const { jsPDF } = window.jspdf;
        const doc = new jsPDF();
        const pageWidth = doc.internal.pageSize.getWidth();
        const pageHeight = doc.internal.pageSize.getHeight();
        
        // Load images
        const bgImg = new Image();
        const logoImg = new Image();
        const pag03Img = new Image();
        const pag07Img = new Image();
        const pag08Img = new Image();
        let bgLoaded = false, logoLoaded = false, pag03Loaded = false, pag07Loaded = false, pag08Loaded = false;
        
        bgImg.src = "/content/admin/page/calculator/img/pdf-bg.jpg";
        logoImg.src = "/content/admin/page/calculator/img/logo.png";
        pag03Img.src = "/content/admin/page/calculator/img/pag_03.jpg";
        pag07Img.src = "/content/admin/page/calculator/img/pag_07.jpg";
        pag08Img.src = "/content/admin/page/calculator/img/pag_08.jpg";
        
        function generatePDFWithImages() {
            if (!bgLoaded || !logoLoaded || !pag03Loaded || !pag07Loaded || !pag08Loaded) return;
            
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
            const logoWidth = 40;
            const logoHeight = 15;
            const bgWidth = logoWidth + bgPadding * 2;
            const bgHeight = logoHeight + bgPadding * 2;
            doc.setFillColor(0, 0, 0);
            doc.rect(bgX, 0, bgWidth, bgHeight, "F");
            doc.addImage(logoImg, "PNG", bgX + bgPadding, bgPadding, logoWidth, logoHeight);
            
            // Vehicle info on first page (over background)
            doc.setTextColor(255, 255, 255);
            
            // Title "Oferta comerciala" above brand/model
            doc.setFontSize(24);
            doc.setFont("helvetica", "normal");
            doc.text(removeDiacritics("Oferta comerciala"), 20, pageHeight * 0.25);
            
            // MARCA Model, An (brand uppercase, model capitalized) - with text wrapping
            doc.setFontSize(38);
            doc.setFont("helvetica", "bold");
            const brandClean = offer.brand.replace(/_/g, " ").toUpperCase();
            const modelClean = capitalizeWords(offer.model.replace(/_/g, " "));
            var vehicleTitle = brandClean + " " + modelClean + ", " + offer.year;
            var maxTitleWidth = pageWidth - 40 - 50;
            var titleLines = doc.splitTextToSize(removeDiacritics(vehicleTitle), maxTitleWidth);
            doc.text(titleLines, 20, pageHeight * 0.30);
            
            // Calculate Y position after title lines
            var titleEndY = pageHeight * 0.30 + (titleLines.length - 1) * 14;
            
            // Engine capacity and fuel type on new line
            var engineInfo = "";
            if (offer.cylinder_capacity) {
                engineInfo += offer.cylinder_capacity + " cm³";
            }
            if (offer.fuel_type) {
                if (engineInfo) engineInfo += ", ";
                engineInfo += capitalizeWords(offer.fuel_type.replace(/_/g, " "));
            }
            if (engineInfo) {
                doc.text(removeDiacritics(engineInfo), 20, titleEndY + 18);
            }
            
            // Red rectangle bottom right with page number
            const rectWidth = 50;
            const rectHeight = 75;
            const rectX = pageWidth - rectWidth - marginP1;
            const rectY = pageHeight - rectHeight;
            doc.setFillColor(226, 0, 26);
            doc.rect(rectX, rectY, rectWidth, rectHeight, "F");
            doc.setTextColor(255, 255, 255);
            doc.setFontSize(14);
            doc.text("01", rectX + rectWidth / 2, rectY + rectHeight / 2 + 5, { align: "center" });
            
            // Footer info on page 1
            doc.setTextColor(51, 51, 51);
            doc.setFontSize(10);
            const footerY = pageHeight * 0.85;
            doc.setFont("helvetica", "bold");
            doc.text("SAUTO SRL", 20, footerY);
            doc.setFont("helvetica", "normal");
            doc.text("+373 68 68 99 95", 20, footerY + 5);
            doc.text("info@sauto.md", 20, footerY + 10);
            doc.text("Chisinau str. Calea Mosilor 11", 20, footerY + 15);
            
            // Show user info only for allowed users
            if (userInfo) {
                doc.setFont("helvetica", "bold");
                doc.text(removeDiacritics(loggedUserName.toUpperCase()), 100, footerY);
                doc.setFont("helvetica", "normal");
                doc.text("Manager vanzari", 100, footerY + 5);
                doc.text(userInfo.phone, 100, footerY + 10);
                doc.text(userInfo.email, 100, footerY + 15);
            }
            
            // === PAGE 2: Contents (gray background) ===
            doc.addPage();
            
            // Gray background #e8e8e8 with 3% margin
            const margin = pageWidth * 0.03;
            doc.setFillColor(232, 232, 232);
            doc.rect(margin, margin, pageWidth - margin * 2, pageHeight - margin * 2, "F");
            
            // Red rectangle bottom right with page number (for page 2)
            doc.setFillColor(226, 0, 26);
            doc.rect(pageWidth - rectWidth - margin, pageHeight - rectHeight, rectWidth, rectHeight, "F");
            doc.setTextColor(255, 255, 255);
            doc.setFontSize(14);
            doc.text("02", pageWidth - rectWidth / 2 - margin, pageHeight - rectHeight / 2 + 5, { align: "center" });
            
            // Logo with black background (same as page 1)
            doc.setFillColor(0, 0, 0);
            doc.rect(margin, 0, bgWidth, bgHeight, "F");
            doc.addImage(logoImg, "PNG", margin + bgPadding, bgPadding, logoWidth, logoHeight);
            
            // Title "Continut" (bold, centered)
            doc.setTextColor(0, 0, 0);
            doc.setFontSize(28);
            doc.setFont("helvetica", "bold");
            doc.text(removeDiacritics("Continut"), pageWidth / 2, pageHeight * 0.16, { align: "center" });
            
            // Contents list (centered vertically)
            doc.setFontSize(14);
            doc.setFont("helvetica", "normal");
            var totalItems = 5;
            var itemHeight = 22;
            var totalContentHeight = totalItems * itemHeight;
            var contentY = (pageHeight - totalContentHeight) / 2;
            var contentItems = [
                { title: "Despre noi", page: "03" },
                { title: "Specificatia tehnica", page: "04" },
                { title: "Imagini de produs", page: "05" },
                { title: "Pret", page: "06" },
                { title: "Termeni si conditii", page: "07" }
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
            
            // === PAGE 3: Despre noi ===
            doc.addPage();
            
            // Image pag_03 with 3% margin top/left/right, 30% height (drawn first)
            const p3Margin = pageWidth * 0.03;
            const p3ImgY = p3Margin;
            const p3ImgWidth = pageWidth - p3Margin * 2;
            const p3ImgHeight = pageHeight * 0.30;
            doc.addImage(pag03Img, "JPEG", p3Margin, p3ImgY, p3ImgWidth, p3ImgHeight);
            
            // Logo with black background (on top of image)
            doc.setFillColor(0, 0, 0);
            doc.rect(p3Margin, 0, bgWidth, bgHeight, "F");
            doc.addImage(logoImg, "PNG", p3Margin + bgPadding, bgPadding, logoWidth, logoHeight);
            
            // Title "Despre noi"
            doc.setTextColor(0, 0, 0);
            doc.setFontSize(48);
            doc.setFont("helvetica", "bold");
            var p3Y = p3ImgY + p3ImgHeight + 18;
            doc.text(removeDiacritics("Despre noi"), p3Margin, p3Y);
            p3Y += 14;
            
            // Text content - limit width to not overlap with red rectangle
            var textMaxWidth = pageWidth - p3Margin * 2 - rectWidth - 10;
            
            doc.setFontSize(10);
            doc.setFont("helvetica", "bold");
            var firstLine = doc.splitTextToSize(removeDiacritics("SAUTO S.R.L. este o companie specializata in import si vinzarea automobilelor rulate din Europa in Republica Moldova."), textMaxWidth);
            doc.text(firstLine, p3Margin, p3Y);
            p3Y += firstLine.length * 4 + 3;
            
            doc.setFont("helvetica", "normal");
            var aboutTexts = [
                "Avind la baza ca obiectiv oferirea unui larg asortiment de automobile accesibile pentru toti, am devenit unul din cei mai importanti importatori auto din Moldova.",
                "SAUTO S.R.L. inseamna echipa. O echipa unita, bine pregatita si pasionata de domeniul auto, ai carei membri impartasesc, indiferent de nivelul ierarhic, aceleasi valori si principii.",
                "Astazi, reprezentam cu succes branduri auto renumite, lucram in fiecare zi pentru a descoperi solutii noi, pentru a consolida si creste calitatea serviciilor noastre, pentru a fi autentici, profesionisti, moderni si inspirati.",
                "Echipa noastra este pregatita pentru a va consilia sa alegeti model care vi se potriveste cel mai bine.",
                "Va asteptam la noi pentru a cunoaste oameni pasionati si instruiti, gata sa va prezinte o marca auto cu adevarat impresionanta, intr-o parcare moderna si rafinata, la fel cum sunt si masinile insesi.",
                "Printre valorile si principiile impartasite se enumera:"
            ];
            
            aboutTexts.forEach(function(txt) {
                var lines = doc.splitTextToSize(removeDiacritics(txt), textMaxWidth);
                doc.text(lines, p3Margin, p3Y);
                p3Y += lines.length * 4 + 3;
            });
            
            p3Y += 3;
            
            // Two columns: Stabilitate and Profesionalism
            var colWidth = (textMaxWidth - p3Margin) / 2;
            var col1X = p3Margin;
            var col2X = p3Margin + colWidth + p3Margin;
            var colY = p3Y;
            
            // Stabilitate
            doc.setFont("helvetica", "bold");
            doc.text(removeDiacritics("• Stabilitate"), col1X, colY);
            doc.setFont("helvetica", "normal");
            var stabText = "Pentru noi inseamna o viziune clara, neschimbatoare in timp care stau la baza ideologiei companiei noastre. Cultivarea acestei valori da dovada de responsabilitate si inspira incredere clientilor nostri.";
            var stabLines = doc.splitTextToSize(removeDiacritics(stabText), colWidth);
            doc.text(stabLines, col1X, colY + 5);
            
            // Profesionalism
            doc.setFont("helvetica", "bold");
            doc.text(removeDiacritics("• Profesionalism"), col2X, colY);
            doc.setFont("helvetica", "normal");
            var profText = "In vizunea noastra reprezinta oferirea automobilelor care corespund nevoielor si cerintelor clientilor nostri. Noi ne straduim sa furnizam informati complete, clare si precise despre modelele pe care le avem in stoc sau daca sint in asteptare.";
            var profLines = doc.splitTextToSize(removeDiacritics(profText), colWidth);
            doc.text(profLines, col2X, colY + 5);
            
            var maxColHeight = Math.max(stabLines.length, profLines.length) * 4 + 15;
            p3Y = colY + maxColHeight;
            
            // Perfectionizm si flexibilitate
            doc.setFont("helvetica", "bold");
            doc.text(removeDiacritics("• Perfectionizm si flexibilitate"), col1X, p3Y);
            doc.setFont("helvetica", "normal");
            var perfText = "Pentru ca sintem in cautarea unor noi cerinte de piata studiem tendintele si aplicam noi strategii, tindem de a perfectiona si diversifica activitatea pe care o avem.";
            var perfLines = doc.splitTextToSize(removeDiacritics(perfText), textMaxWidth);
            doc.text(perfLines, col1X, p3Y + 5);
            
            // Red rectangle bottom right with page number
            doc.setFillColor(226, 0, 26);
            doc.rect(pageWidth - rectWidth - p3Margin, pageHeight - rectHeight, rectWidth, rectHeight, "F");
            doc.setTextColor(255, 255, 255);
            doc.setFontSize(14);
            doc.text("03", pageWidth - rectWidth / 2 - p3Margin, pageHeight - rectHeight / 2 + 5, { align: "center" });
            
            // === PAGE 4: Specificatia automobilului ===
            doc.addPage();
            
            var p4Margin = pageWidth * 0.03;
            
            // Logo with black background (standard for all pages)
            doc.setFillColor(0, 0, 0);
            doc.rect(p4Margin, 0, bgWidth, bgHeight, "F");
            doc.addImage(logoImg, "PNG", p4Margin + bgPadding, bgPadding, logoWidth, logoHeight);
            
            var p4Y = 50;
            var p4TextMaxWidth = pageWidth - p4Margin * 2 - rectWidth - 10;
            
            // Small title "Specificatia automobilului"
            doc.setTextColor(0, 0, 0);
            doc.setFontSize(14);
            doc.setFont("helvetica", "normal");
            doc.text(removeDiacritics("Specificatia automobilului"), p4Margin + 8, p4Y);
            p4Y += 12;
            
            // Big title with red vertical line (like page 06)
            var titleStartY = p4Y;
            
            // Red vertical line - draw first, aligned with text
            doc.setFillColor(226, 0, 26);
            doc.rect(p4Margin, titleStartY - 8, 4, 32, "F");
            
            doc.setFontSize(32);
            doc.setFont("helvetica", "bold");
            const brandClean2 = offer.brand.replace(/_/g, " ").toUpperCase();
            const modelClean2 = capitalizeWords(offer.model.replace(/_/g, " "));
            var specLine1 = brandClean2 + " " + modelClean2 + ", " + offer.year + ",";
            doc.text(removeDiacritics(specLine1), p4Margin + 10, p4Y);
            p4Y += 12;
            
            // Second line: Capacitate, Tip
            var specLine2 = "";
            if (offer.cylinder_capacity) {
                specLine2 = offer.cylinder_capacity + ", " + capitalizeWords((offer.fuel_type || "").replace(/_/g, " "));
            }
            
            doc.setTextColor(0, 0, 0);
            doc.text(removeDiacritics(specLine2), p4Margin + 10, p4Y);
            p4Y += 25;
            
            // Specifications table - 3 columns with gray background
            doc.setFontSize(13);
            var colWidth = (pageWidth - p4Margin * 2) / 3;
            var col1X = p4Margin;
            var col2X = p4Margin + colWidth;
            var col3X = p4Margin + colWidth * 2;
            var specRowHeight = 22;
            var tablePadding = 8;
            var tableY = p4Y;
            
            // Draw table background (light gray)
            doc.setFillColor(245, 245, 245);
            doc.rect(p4Margin, tableY, pageWidth - p4Margin * 2, specRowHeight * 4 + tablePadding * 2, "F");
            
            p4Y = tableY + tablePadding + 4;
            
            // Row 1: Marca, Model, Anul
            doc.setFont("helvetica", "normal");
            doc.setTextColor(128, 128, 128);
            doc.text("Marca", col1X + 10, p4Y);
            doc.text("Model", col2X + 10, p4Y);
            doc.text("Anul", col3X + 10, p4Y);
            doc.setTextColor(0, 0, 0);
            doc.setFont("helvetica", "bold");
            doc.text(removeDiacritics(capitalizeWords(offer.brand.replace(/_/g, " "))), col1X + 10, p4Y + 7);
            doc.text(removeDiacritics(capitalizeWords(offer.model.replace(/_/g, " "))), col2X + 10, p4Y + 7);
            doc.text(String(offer.year), col3X + 10, p4Y + 7);
            p4Y += specRowHeight;
            
            // Row 2: Caroserie, Numar locuri, Parcurs
            doc.setFont("helvetica", "normal");
            doc.setTextColor(128, 128, 128);
            doc.text("Caroserie", col1X + 10, p4Y);
            doc.text(removeDiacritics("Numarul de locuri"), col2X + 10, p4Y);
            doc.text("Parcurs", col3X + 10, p4Y);
            doc.setTextColor(0, 0, 0);
            doc.setFont("helvetica", "bold");
            doc.text(removeDiacritics(translateValue(offer.bodywork)), col1X + 10, p4Y + 7);
            doc.text(String(offer.seats || ""), col2X + 10, p4Y + 7);
            doc.text(formatNumber(offer.mileage || 0), col3X + 10, p4Y + 7);
            p4Y += specRowHeight;
            
            // Row 3: Capacitate cilindrica, Puterea motorului, Tip combustibil
            doc.setFont("helvetica", "normal");
            doc.setTextColor(128, 128, 128);
            doc.text("Capacitatea cilindrica", col1X + 10, p4Y);
            doc.text("Puterea motorului", col2X + 10, p4Y);
            doc.text("Tip combustibil", col3X + 10, p4Y);
            doc.setTextColor(0, 0, 0);
            doc.setFont("helvetica", "bold");
            doc.text(String(offer.cylinder_capacity || ""), col1X + 10, p4Y + 7);
            doc.text(String(offer.engine_power || ""), col2X + 10, p4Y + 7);
            doc.text(removeDiacritics(translateValue(offer.fuel_type)), col3X + 10, p4Y + 7);
            p4Y += specRowHeight;
            
            // Row 4: Cutie viteze, Tractiunea, Culoarea
            doc.setFont("helvetica", "normal");
            doc.setTextColor(128, 128, 128);
            doc.text("Cutia de viteze", col1X + 10, p4Y);
            doc.text("Tractiunea", col2X + 10, p4Y);
            doc.text("Culoarea", col3X + 10, p4Y);
            doc.setTextColor(0, 0, 0);
            doc.setFont("helvetica", "bold");
            doc.text(removeDiacritics(translateValue(offer.transmission)), col1X + 10, p4Y + 7);
            doc.text(removeDiacritics(translateValue(offer.drive_type)), col2X + 10, p4Y + 7);
            doc.text(removeDiacritics(translateValue(offer.color)), col3X + 10, p4Y + 7);
            p4Y += specRowHeight + tablePadding + 5;
            
            // AI Features: Siguranta si Confort
            var aiFeatures = null;
            try {
                aiFeatures = JSON.parse(offer.ai_features || "null");
            } catch(e) {}
            
            if (aiFeatures && (aiFeatures.safety || aiFeatures.comfort)) {
                // Siguranta section
                if (aiFeatures.safety && aiFeatures.safety.length > 0) {
                    doc.setTextColor(0, 0, 0);
                    doc.setFontSize(16);
                    doc.setFont("helvetica", "bold");
                    doc.text("Siguranta", p4Margin, p4Y);
                    p4Y += 6;
                    
                    doc.setFontSize(10);
                    doc.setFont("helvetica", "normal");
                    aiFeatures.safety.forEach(function(item) {
                        // Draw red rounded square with white checkmark
                        var checkY = p4Y - 3;
                        var boxSize = 3.5;
                        doc.setFillColor(226, 0, 26);
                        doc.roundedRect(p4Margin, checkY, boxSize, boxSize, 0.8, 0.8, "F");
                        // White checkmark inside
                        doc.setDrawColor(255, 255, 255);
                        doc.setLineWidth(0.6);
                        doc.line(p4Margin + 0.7, checkY + 1.8, p4Margin + 1.4, checkY + 2.6);
                        doc.line(p4Margin + 1.4, checkY + 2.6, p4Margin + 2.8, checkY + 1);
                        doc.setTextColor(0, 0, 0);
                        doc.text(removeDiacritics(item), p4Margin + 5.5, p4Y);
                        p4Y += 4.5;
                    });
                    p4Y += 8;
                }
                
                // Confort section
                if (aiFeatures.comfort && aiFeatures.comfort.length > 0) {
                    doc.setTextColor(0, 0, 0);
                    doc.setFontSize(16);
                    doc.setFont("helvetica", "bold");
                    doc.text("Confort", p4Margin, p4Y);
                    p4Y += 6;
                    
                    doc.setFontSize(10);
                    doc.setFont("helvetica", "normal");
                    aiFeatures.comfort.forEach(function(item) {
                        // Draw red rounded square with white checkmark
                        var checkY = p4Y - 3;
                        var boxSize = 3.5;
                        doc.setFillColor(226, 0, 26);
                        doc.roundedRect(p4Margin, checkY, boxSize, boxSize, 0.8, 0.8, "F");
                        // White checkmark inside
                        doc.setDrawColor(255, 255, 255);
                        doc.setLineWidth(0.6);
                        doc.line(p4Margin + 0.7, checkY + 1.8, p4Margin + 1.4, checkY + 2.6);
                        doc.line(p4Margin + 1.4, checkY + 2.6, p4Margin + 2.8, checkY + 1);
                        doc.setTextColor(0, 0, 0);
                        doc.text(removeDiacritics(item), p4Margin + 5.5, p4Y);
                        p4Y += 4.5;
                    });
                }
            }
            
            // Red rectangle bottom right with page number
            doc.setFillColor(226, 0, 26);
            doc.rect(pageWidth - rectWidth - p4Margin, pageHeight - rectHeight, rectWidth, rectHeight, "F");
            doc.setTextColor(255, 255, 255);
            doc.setFontSize(14);
            doc.text("04", pageWidth - rectWidth / 2 - p4Margin, pageHeight - rectHeight / 2 + 5, { align: "center" });
            
            // === PAGE 5: Product Images ===
            doc.addPage();
            const p5Margin = pageWidth * 0.03;
            const p5Gap = pageWidth * 0.015; // smaller gap between images
            
            // Parse images from offer
            var offerImagesArr = [];
            try {
                offerImagesArr = JSON.parse(offer.images || "[]");
            } catch(e) {}
            
            if (offerImagesArr.length > 0) {
                // Load all images first
                var imgPromises = offerImagesArr.map(function(imgPath, idx) {
                    return new Promise(function(resolve) {
                        var img = new Image();
                        img.crossOrigin = "anonymous";
                        img.onload = function() { resolve({ idx: idx, img: img }); };
                        img.onerror = function() { resolve(null); };
                        img.src = imgPath;
                    });
                });
                
                Promise.all(imgPromises).then(function(results) {
                    var imgs = results.filter(function(r) { return r; }).sort(function(a, b) { return a.idx - b.idx; });
                    
                    // Row 1: Big image (full width) - 37% height with logo and title overlay
                    var row1Height = pageHeight * 0.37;
                    var row1Y = p5Margin;
                    var fullWidth = pageWidth - p5Margin * 2;
                    
                    if (imgs[0]) {
                        doc.addImage(imgs[0].img, "JPEG", p5Margin, row1Y, fullWidth, row1Height);
                    }
                    
                    // Logo with black background on top of image
                    doc.setFillColor(0, 0, 0);
                    doc.rect(p5Margin, 0, bgWidth, bgHeight, "F");
                    doc.addImage(logoImg, "PNG", p5Margin + bgPadding, bgPadding, logoWidth, logoHeight);
                    
                    // Title "Imagini de produs" with red vertical line
                    doc.setFillColor(226, 0, 26);
                    doc.rect(p5Margin + 15, row1Y + row1Height * 0.35, 4, 30, "F");
                    doc.setTextColor(255, 255, 255);
                    doc.setFontSize(28);
                    doc.setFont("helvetica", "bold");
                    doc.text(removeDiacritics("Imagini de produs"), p5Margin + 25, row1Y + row1Height * 0.35 + 20);
                    
                    // Use same gap everywhere
                    var gap = p5Gap;
                    
                    // Row 3 position (aligned with top of red rectangle)
                    var rectTopY = pageHeight - rectHeight;
                    var row3ImgHeight = rectHeight * 0.75;
                    
                    // Row 2: calculate to fill remaining space from row1 to row3
                    var row2Y = row1Y + row1Height + gap;
                    var row2Height = rectTopY - row2Y - gap; // fills all remaining height
                    var leftWidth = fullWidth * 0.60 - gap / 2;
                    var rightWidth = fullWidth * 0.40 - gap / 2;
                    var rightImgHeight = (row2Height - gap) / 2;
                    
                    if (imgs[1]) {
                        doc.addImage(imgs[1].img, "JPEG", p5Margin, row2Y, leftWidth, row2Height);
                    }
                    if (imgs[2]) {
                        doc.addImage(imgs[2].img, "JPEG", p5Margin + leftWidth + gap, row2Y, rightWidth, rightImgHeight);
                    }
                    if (imgs[3]) {
                        doc.addImage(imgs[3].img, "JPEG", p5Margin + leftWidth + gap, row2Y + rightImgHeight + gap, rightWidth, rightImgHeight);
                    }
                    
                    // Row 3: 2 images + red rectangle (aligned with top of red rectangle, smaller height)
                    var availableWidth = fullWidth - rectWidth - gap * 2;
                    var col3Width = availableWidth / 2;
                    
                    if (imgs[4]) {
                        doc.addImage(imgs[4].img, "JPEG", p5Margin, rectTopY, col3Width, row3ImgHeight);
                    }
                    if (imgs[5]) {
                        doc.addImage(imgs[5].img, "JPEG", p5Margin + col3Width + gap, rectTopY, col3Width, row3ImgHeight);
                    }
                    
                    // Red rectangle bottom right with page number 05 (standard size like other pages)
                    doc.setFillColor(226, 0, 26);
                    doc.rect(pageWidth - rectWidth - p5Margin, rectTopY, rectWidth, rectHeight, "F");
                    doc.setTextColor(255, 255, 255);
                    doc.setFontSize(14);
                    doc.text("05", pageWidth - rectWidth / 2 - p5Margin, pageHeight - rectHeight / 2 + 5, { align: "center" });
                    
                    continuePDF();
                });
                
                return;
            }
            
            // If no images - just show red rectangle
            doc.setFillColor(226, 0, 26);
            doc.rect(pageWidth - rectWidth - p5Margin, pageHeight - rectHeight, rectWidth, rectHeight, "F");
            doc.setTextColor(255, 255, 255);
            doc.setFontSize(14);
            doc.text("05", pageWidth - rectWidth / 2 - p5Margin, pageHeight - rectHeight / 2 + 5, { align: "center" });
            
            continuePDF();
            
            function continuePDF() {
            // === PAGE 6: Calcul de pret ===
            doc.addPage();
            const p6Margin = pageWidth * 0.03;
            
            // Logo with black background (standard for all pages)
            doc.setFillColor(0, 0, 0);
            doc.rect(p6Margin, 0, bgWidth, bgHeight, "F");
            doc.addImage(logoImg, "PNG", p6Margin + bgPadding, bgPadding, logoWidth, logoHeight);
            
            let y = 38;
            
            // Title "Calcul de pret" with red vertical line
            doc.setFillColor(226, 0, 26);
            doc.rect(p6Margin, y, 4, 26, "F");
            doc.setTextColor(0, 0, 0);
            doc.setFontSize(28);
            doc.setFont("helvetica", "bold");
            doc.text(removeDiacritics("Calcul de"), p6Margin + 10, y + 10);
            doc.text(removeDiacritics("pret"), p6Margin + 10, y + 22);
            y += 34;
            
            // Get toggle states from saved data (default to true for backwards compatibility)
            const toggles = calcData.toggles || {};
            const isToggleEnabled = (key) => toggles[key] !== undefined ? toggles[key] : true;
            
            // Results - only include items with enabled toggles
            const allResults = [
                { key: "value", toggleKey: "value", label: t.value_mdl },
                { key: "excise", toggleKey: "excise", label: t.excise },
                { key: "customs", toggleKey: "customs", label: t.customs_duty },
                { key: "damage", toggleKey: "damage", label: t.damage_protection },
                { key: "exportDecl", toggleKey: "export", label: t.export_declaration },
                { key: "bank", toggleKey: "bank", label: t.bank_commission },
                { key: "auction", toggleKey: "auction", label: t.auction_commission },
                { key: "pollution", toggleKey: "pollution", label: t.pollution_tax },
                { key: "shipping", toggleKey: "shipping", label: t.shipping_docs },
                { key: "accessories", toggleKey: "accessories", label: t.accessories },
                { key: "transaction", toggleKey: "transaction", label: t.transaction_commission },
                { key: "polishing", toggleKey: "polishing", label: "Polizare si curatire chimica" },
                { key: "painting", toggleKey: "painting", label: "Vopsire" }
            ];
            
            // Filter results based on toggle state
            const results = allResults.filter(item => isToggleEnabled(item.toggleKey));
            
            doc.setFontSize(11);
            var valueX = pageWidth - 90; // fixed X position for values column (left-aligned)
            results.forEach(item => {
                const data = calcData[item.key];
                if (data && (parseFloat(data.mdl) > 0 || parseFloat(data.eur) > 0)) {
                    doc.setFont("helvetica", "normal");
                    var labelText = removeDiacritics(item.label);
                    doc.text(labelText, 20, y);
                    // Draw dotted line under the row
                    doc.setDrawColor(220);
                    doc.setLineDashPattern([1, 1], 0);
                    doc.line(20, y + 2, pageWidth - 20, y + 2);
                    doc.setLineDashPattern([], 0);
                    doc.setFont("helvetica", "bold");
                    doc.text(formatNumber(parseFloat(data.mdl || 0)) + " MDL  (" + formatNumber(parseFloat(data.eur || 0)) + " EUR)", valueX, y);
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
                doc.text(formatNumber(parseFloat(calcData.total.mdl || 0)) + " MDL  (" + formatNumber(parseFloat(calcData.total.eur || 0)) + " EUR)", valueX, y + 3);
                y += 15;
            }
            
            // Vehicle total
            if (calcData.vehicle) {
                doc.setFillColor(85, 85, 85);
                doc.rect(15, y - 5, pageWidth - 30, 12, "F");
                doc.text(removeDiacritics(t.vehicle_total), 20, y + 3);
                doc.text(formatNumber(parseFloat(calcData.vehicle.mdl || 0)) + " MDL  (" + formatNumber(parseFloat(calcData.vehicle.eur || 0)) + " EUR)", valueX, y + 3);
            }
            
            doc.setTextColor(0, 0, 0);
            
            // Red rectangle bottom right with page number 06 (standard position)
            doc.setFillColor(226, 0, 26);
            doc.rect(pageWidth - rectWidth - p6Margin, pageHeight - rectHeight, rectWidth, rectHeight, "F");
            doc.setTextColor(255, 255, 255);
            doc.setFontSize(14);
            doc.text("06", pageWidth - rectWidth / 2 - p6Margin, pageHeight - rectHeight / 2 + 5, { align: "center" });
            
            // === PAGE 7: Termeni si conditii ===
            doc.addPage();
            const p7Margin = pageWidth * 0.03;
            
            // Image pag_07 with 3% margin top/left/right, 30% height (same as page 3)
            const p7ImgY = p7Margin;
            const p7ImgWidth = pageWidth - p7Margin * 2;
            const p7ImgHeight = pageHeight * 0.30;
            doc.addImage(pag07Img, "JPEG", p7Margin, p7ImgY, p7ImgWidth, p7ImgHeight);
            
            // Logo with black background (on top of image)
            doc.setFillColor(0, 0, 0);
            doc.rect(p7Margin, 0, bgWidth, bgHeight, "F");
            doc.addImage(logoImg, "PNG", p7Margin + bgPadding, bgPadding, logoWidth, logoHeight);
            
            // Title "Termeni si conditii" (same style as page 3)
            doc.setTextColor(0, 0, 0);
            doc.setFontSize(48);
            doc.setFont("helvetica", "bold");
            var p7Y = p7ImgY + p7ImgHeight + 18;
            doc.text(removeDiacritics("Termeni si conditii"), p7Margin, p7Y);
            p7Y += 14;
            
            // Text content - limit width to not overlap with red rectangle (same as page 3)
            var p7TextMaxWidth = pageWidth - p7Margin * 2 - rectWidth - 10;
            
            // Terms content - two columns
            var termsCol1 = [
                { title: "Pret net", text: "Reprezinta pretul final a produsului cistigat la licitatie care include: costul propriu zis a automobilului si taxele aditionale percepute de platforma." },
                { title: "Costul transportului", text: "Sunt cheluieli totale suportate de Vinzator in momentul transportarii automobilului pina la locul preluarii acestuia de catre Cuparator (locul preluarii - Chisinau str. Calea Mosilor 11)" },
                { title: "Declaratia MRN", text: "MRN (Movement Reference Number), permite identificarea rapida si eficienta a operatiunii de transport. De asemenea ea reprezinta, o confirmare a trecerii tuturor procedurilor de export si de vamuire. Documentul contine, de asemenea, informatii privind transbordarile, avizele autoritatilor competente, detalii privind controalele efectuate de biroul de plecare si de destinatie si numarul de identificare al containerului. MRN este intotdeauna atasat la marfurile transportate." },
                { title: "Comision bancar SWIFT", text: "SWIFT reprezinta un sistem de comunicare care interconecteaza bancile din intreaga lume pentru tranzactii financiare si plati internationale rapide si sigure. Cu alte cuvinte, este o retea de mesagerie, parte a sistemului global de plati. Acesta serveste la executarea platilor in afara unui sistem intern. SWIFT transmite instructiuni de plata, care sunt schimbate de institutiile financiare care participa la o tranzactie. Prin urmare, se spune ca SWIFT este mecanismul fundamental care asigura finantarea comertului international." },
                { title: "Pierderi valutare", text: "Reprezinta pierederile cauzate in urma schimbului valutar efectuat de Vinzator in favoarea Cumparatorului in momentul procurarii valuteit pentru achizitia automobiluluiu." }
            ];
            
            var termsCol2 = [
                { title: "Taxa retur VAT", text: "Reprezinta costuri suplimentare pentru recuperarea TVA-ului la automobilele achizitionate dupa caz acolo unde este nevoie." },
                { title: "Pierderi valutare", text: "Sunt servicii oferite de brocheri pentru depunerea actelor catre sistemul vamal a Republicii Molodva pentru vamuirea marfurilor importate." },
                { title: "Taxa de devamare", text: "Reprezinta costuri care se achita pentru vamuirea automobilului imortat." },
                { title: "Servicii de intrare si stationare in terminalul vamal", text: "Sunt cheltuieli care pot aparea in procesul vamuirii in caz daca timpul destinat pentru acesta procedura se extinde mai mult de 24 ore." },
                { title: "Comision pentru tranzactie", text: "Este venitul obtinut de companie in urma vinzarii automobilului licitat de Cumparator." },
                { title: "Taxa de devamare pentru accesorii", text: "Reprezinta costuri care se achita pentru vamuirea accesoriilor (anvelope, diverse suporturi suplinetare ect) cu automobilul imortat." },
                { title: "Asigurarea CMR", text: "Polita CMR este asigurarea de raspundere a transportatorului pentru marfa transportata in calitate de caraus." },
                { title: "Servicii suplimentare", text: "Sunt servicii suplimentare oferite de Vinzator cu acordul Cumparatorului pentru mentenata vehiculului: vopsire, spalre, reparare, mentenanta etc." },
                { title: "Taxa de lux", text: "se aplica suplimentar si se percepe de Biroul Vamal la automobile a caror valuare in momentul vamuirei depaseste valuarea de 600.000,00 lei." }
            ];
            
            doc.setFontSize(10);
            var p7Col1Width = (p7TextMaxWidth - p7Margin) / 2;
            var p7Col2Width = pageWidth - p7Margin * 3 - p7Col1Width - rectWidth - 5;
            var p7Col1X = p7Margin;
            var p7Col2X = p7Margin + p7Col1Width + p7Margin;
            var p7Col1Y = p7Y;
            var p7Col2Y = p7Y;
            var p7MaxY1 = pageHeight - p7Margin;
            var p7MaxY2 = pageHeight - p7Margin;
            var lineHeight = 3.5;
            var paragraphGap = 3;
            
            // Column 1 - can go to bottom of page
            termsCol1.forEach(function(term) {
                if (p7Col1Y < p7MaxY1) {
                    doc.setFont("helvetica", "bold");
                    var titleText = removeDiacritics(term.title + " - ");
                    doc.text(titleText, p7Col1X, p7Col1Y);
                    var titleWidth = doc.getTextWidth(titleText);
                    doc.setFont("helvetica", "normal");
                    var fullText = removeDiacritics(term.text);
                    var availableWidth = p7Col1Width - titleWidth;
                    var firstLineSplit = doc.splitTextToSize(fullText, availableWidth);
                    doc.text(firstLineSplit[0] || "", p7Col1X + titleWidth, p7Col1Y);
                    p7Col1Y += lineHeight;
                    var remainingText = fullText.substring((firstLineSplit[0] || "").length).trim();
                    if (remainingText) {
                        var remainingLines = doc.splitTextToSize(remainingText, p7Col1Width);
                        remainingLines.forEach(function(line) {
                            if (p7Col1Y < p7MaxY1) {
                                doc.text(line, p7Col1X, p7Col1Y);
                                p7Col1Y += lineHeight;
                            }
                        });
                    }
                    p7Col1Y += paragraphGap;
                }
            });
            
            // Column 2 - limited by red rectangle
            termsCol2.forEach(function(term) {
                if (p7Col2Y < p7MaxY2) {
                    doc.setFont("helvetica", "bold");
                    var titleText = removeDiacritics(term.title + " - ");
                    doc.text(titleText, p7Col2X, p7Col2Y);
                    var titleWidth = doc.getTextWidth(titleText);
                    doc.setFont("helvetica", "normal");
                    var fullText = removeDiacritics(term.text);
                    var availableWidth = p7Col2Width - titleWidth;
                    var firstLineSplit = doc.splitTextToSize(fullText, availableWidth);
                    doc.text(firstLineSplit[0] || "", p7Col2X + titleWidth, p7Col2Y);
                    p7Col2Y += lineHeight;
                    var remainingText = fullText.substring((firstLineSplit[0] || "").length).trim();
                    if (remainingText) {
                        var remainingLines = doc.splitTextToSize(remainingText, p7Col2Width);
                        remainingLines.forEach(function(line) {
                            if (p7Col2Y < p7MaxY2) {
                                doc.text(line, p7Col2X, p7Col2Y);
                                p7Col2Y += lineHeight;
                            }
                        });
                    }
                    p7Col2Y += paragraphGap;
                }
            });
            
            // Red rectangle bottom right with page number 07
            doc.setFillColor(226, 0, 26);
            doc.rect(pageWidth - rectWidth - p7Margin, pageHeight - rectHeight, rectWidth, rectHeight, "F");
            doc.setTextColor(255, 255, 255);
            doc.setFontSize(14);
            doc.text("07", pageWidth - rectWidth / 2 - p7Margin, pageHeight - rectHeight / 2 + 5, { align: "center" });
            
            // === PAGE 8: Contact page ===
            doc.addPage();
            const p8Margin = pageWidth * 0.03;
            
            // Background image pag_08 (same layout as page 1)
            const p8ImgY = p8Margin;
            const p8ImgWidth = pageWidth;
            const p8ImgHeight = pageHeight * 0.75;
            doc.addImage(pag08Img, "JPEG", 0, p8ImgY, p8ImgWidth, p8ImgHeight);
            
            // Logo with black background
            doc.setFillColor(0, 0, 0);
            doc.rect(p8Margin, 0, bgWidth, bgHeight, "F");
            doc.addImage(logoImg, "PNG", p8Margin + bgPadding, bgPadding, logoWidth, logoHeight);
            
            // Footer info on page 8 (on the RIGHT side, unlike page 1)
            doc.setTextColor(51, 51, 51);
            doc.setFontSize(10);
            const p8FooterY = pageHeight * 0.85;
            const p8FooterX = pageWidth - p8Margin - 60;
            doc.setFont("helvetica", "bold");
            doc.text("SAUTO SRL", p8FooterX, p8FooterY, { align: "right" });
            doc.setFont("helvetica", "normal");
            doc.text("+373 68 68 99 95", p8FooterX, p8FooterY + 5, { align: "right" });
            doc.text("info@sauto.md", p8FooterX, p8FooterY + 10, { align: "right" });
            doc.text("Chisinau str. Calea Mosilor 11", p8FooterX, p8FooterY + 15, { align: "right" });
            
            // Red rectangle bottom right with page number 08
            doc.setFillColor(226, 0, 26);
            doc.rect(pageWidth - rectWidth - p8Margin, pageHeight - rectHeight, rectWidth, rectHeight, "F");
            doc.setTextColor(255, 255, 255);
            doc.setFontSize(14);
            doc.text("08", pageWidth - rectWidth / 2 - p8Margin, pageHeight - rectHeight / 2 + 5, { align: "center" });
            
            doc.save(fileName);
            } // end continuePDF
        }
        
        bgImg.onload = function() { bgLoaded = true; generatePDFWithImages(); };
        logoImg.onload = function() { logoLoaded = true; generatePDFWithImages(); };
        pag03Img.onload = function() { pag03Loaded = true; generatePDFWithImages(); };
        pag07Img.onload = function() { pag07Loaded = true; generatePDFWithImages(); };
        pag08Img.onload = function() { pag08Loaded = true; generatePDFWithImages(); };
        bgImg.onerror = function() { bgLoaded = true; generatePDFWithImages(); };
        logoImg.onerror = function() { logoLoaded = true; generatePDFWithImages(); };
        pag03Img.onerror = function() { pag03Loaded = true; generatePDFWithImages(); };
        pag07Img.onerror = function() { pag07Loaded = true; generatePDFWithImages(); };
        pag08Img.onerror = function() { pag08Loaded = true; generatePDFWithImages(); };
    }
})();
</script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf/2.5.1/jspdf.umd.min.js"></script>';

echo $rtrn;
