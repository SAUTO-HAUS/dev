<?php defined( '_DOIT' ) or die( 'Restricted access' ); ?>

<style>
/* Modern Credit Page Styles */
.credit-page {
    font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Oxygen, Ubuntu, Cantarell, sans-serif;
    line-height: 1.6;
    color: #333;
    max-width: 1200px;
    margin: 0 auto;
    padding: 0 20px;
}

/* Hero Section */
.hero-section {
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    color: white;
    padding: 80px 0;
    text-align: center;
    border-radius: 20px;
    margin: 40px 0;
    position: relative;
    overflow: hidden;
}

.hero-section::before {
    content: '';
    position: absolute;
    top: 0;
    left: 0;
    right: 0;
    bottom: 0;
    background: url('data:image/svg+xml,<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 100 100"><defs><pattern id="grain" width="100" height="100" patternUnits="userSpaceOnUse"><circle cx="25" cy="25" r="1" fill="white" opacity="0.1"/><circle cx="75" cy="75" r="1" fill="white" opacity="0.1"/><circle cx="50" cy="10" r="0.5" fill="white" opacity="0.05"/></pattern></defs><rect width="100" height="100" fill="url(%23grain)"/></svg>');
    opacity: 0.3;
}

.hero-content {
    position: relative;
    z-index: 2;
}

.hero-title {
    font-size: 3.5rem;
    font-weight: 700;
    margin-bottom: 20px;
    text-shadow: 0 2px 4px rgba(0,0,0,0.3);
}

.hero-subtitle {
    font-size: 1.3rem;
    opacity: 0.9;
    margin-bottom: 40px;
    max-width: 600px;
    margin-left: auto;
    margin-right: auto;
}

/* Calculator Section */
.calculator-section {
    background: white;
    border-radius: 20px;
    padding: 50px;
    margin: 40px 0;
    box-shadow: 0 20px 60px rgba(0,0,0,0.1);
    border: 1px solid #f0f0f0;
}

.calculator-title {
    font-size: 2.5rem;
    font-weight: 600;
    text-align: center;
    margin-bottom: 40px;
    color: #2d3748;
}

.calculator-grid {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 40px;
    margin-bottom: 40px;
}

.input-group {
    position: relative;
}

.input-label {
    display: block;
    font-weight: 600;
    margin-bottom: 12px;
    color: #4a5568;
    font-size: 1.1rem;
}

.input-field {
    width: 100%;
    padding: 16px 20px;
    border: 2px solid #e2e8f0;
    border-radius: 12px;
    font-size: 1.1rem;
    transition: all 0.3s ease;
    background: #f8fafc;
}

.input-field:focus {
    outline: none;
    border-color: #667eea;
    background: white;
    box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.1);
}

.slider-container {
    margin: 20px 0;
}

/* Results Section */
.results-section {
    background: linear-gradient(135deg, #f093fb 0%, #f5576c 100%);
    color: white;
    padding: 40px;
    border-radius: 16px;
    text-align: center;
    margin-top: 40px;
}

.result-title {
    font-size: 1.3rem;
    margin-bottom: 20px;
    opacity: 0.9;
}

.result-amount {
    font-size: 3rem;
    font-weight: 700;
    margin-bottom: 10px;
}

.result-range {
    font-size: 1.1rem;
    opacity: 0.8;
}

/* Features Grid */
.features-section {
    margin: 80px 0;
}

.section-title {
    font-size: 2.8rem;
    font-weight: 600;
    text-align: center;
    margin-bottom: 60px;
    color: #2d3748;
}

.features-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(350px, 1fr));
    gap: 40px;
    margin-bottom: 60px;
}

.feature-card {
    background: white;
    padding: 40px 30px;
    border-radius: 16px;
    box-shadow: 0 10px 30px rgba(0,0,0,0.08);
    border: 1px solid #f0f0f0;
    transition: all 0.3s ease;
    position: relative;
    overflow: hidden;
}

.feature-card::before {
    content: '';
    position: absolute;
    top: 0;
    left: 0;
    right: 0;
    height: 4px;
    background: linear-gradient(90deg, #667eea, #764ba2);
}

.feature-card:hover {
    transform: translateY(-5px);
    box-shadow: 0 20px 40px rgba(0,0,0,0.12);
}

.feature-icon {
    width: 60px;
    height: 60px;
    background: linear-gradient(135deg, #667eea, #764ba2);
    border-radius: 12px;
    display: flex;
    align-items: center;
    justify-content: center;
    margin-bottom: 20px;
    font-size: 1.5rem;
    color: white;
}

.feature-title {
    font-size: 1.4rem;
    font-weight: 600;
    margin-bottom: 15px;
    color: #2d3748;
}

.feature-description {
    color: #718096;
    line-height: 1.6;
}

.feature-list {
    list-style: none;
    padding: 0;
    margin: 20px 0 0 0;
}

.feature-list li {
    padding: 8px 0;
    position: relative;
    padding-left: 25px;
    color: #4a5568;
}

.feature-list li::before {
    content: '✓';
    position: absolute;
    left: 0;
    color: #48bb78;
    font-weight: bold;
}

/* Partners Section */
.partners-section {
    background: #f8fafc;
    padding: 60px 40px;
    border-radius: 20px;
    margin: 60px 0;
}

.partners-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
    gap: 30px;
    margin-top: 40px;
}

.partner-card {
    background: white;
    padding: 30px 20px;
    border-radius: 12px;
    text-align: center;
    box-shadow: 0 5px 15px rgba(0,0,0,0.05);
    transition: all 0.3s ease;
}

.partner-card:hover {
    transform: translateY(-3px);
    box-shadow: 0 10px 25px rgba(0,0,0,0.1);
}

.partner-name {
    font-weight: 600;
    color: #2d3748;
    margin-bottom: 10px;
}

.partner-description {
    font-size: 0.9rem;
    color: #718096;
}

/* CTA Section */
.cta-section {
    background: linear-gradient(135deg, #2d3748 0%, #4a5568 100%);
    color: white;
    padding: 60px 40px;
    border-radius: 20px;
    text-align: center;
    margin: 60px 0;
}

.cta-title {
    font-size: 2.5rem;
    font-weight: 600;
    margin-bottom: 20px;
}

.cta-description {
    font-size: 1.2rem;
    opacity: 0.9;
    margin-bottom: 40px;
    max-width: 600px;
    margin-left: auto;
    margin-right: auto;
}

.cta-buttons {
    display: flex;
    gap: 20px;
    justify-content: center;
    flex-wrap: wrap;
}

.btn {
    padding: 16px 32px;
    border-radius: 12px;
    font-weight: 600;
    text-decoration: none;
    transition: all 0.3s ease;
    display: inline-block;
    font-size: 1.1rem;
}

.btn-primary {
    background: linear-gradient(135deg, #667eea, #764ba2);
    color: white;
    border: none;
}

.btn-primary:hover {
    transform: translateY(-2px);
    box-shadow: 0 10px 25px rgba(102, 126, 234, 0.3);
}

.btn-secondary {
    background: transparent;
    color: white;
    border: 2px solid white;
}

.btn-secondary:hover {
    background: white;
    color: #2d3748;
}

/* Responsive Design */
@media (max-width: 768px) {
    .hero-title {
        font-size: 2.5rem;
    }
    
    .calculator-grid {
        grid-template-columns: 1fr;
        gap: 30px;
    }
    
    .calculator-section {
        padding: 30px 20px;
    }
    
    .features-grid {
        grid-template-columns: 1fr;
    }
    
    .partners-grid {
        grid-template-columns: 1fr;
    }
    
    .cta-buttons {
        flex-direction: column;
        align-items: center;
    }
    
    .btn {
        width: 100%;
        max-width: 300px;
    }
}

/* Ion Range Slider Custom Styles */
.irs {
    position: relative;
    display: block;
    -webkit-touch-callout: none;
    -webkit-user-select: none;
    -khtml-user-select: none;
    -moz-user-select: none;
    -ms-user-select: none;
    user-select: none;
    font-size: 12px;
    font-family: Arial, sans-serif;
}

.irs-line {
    position: relative;
    display: block;
    overflow: hidden;
    outline: none !important;
    height: 8px;
    top: 25px;
    background: #e2e8f0;
    border-radius: 4px;
}

.irs-bar {
    position: absolute;
    display: block;
    left: 0;
    width: 0;
    height: 8px;
    background: linear-gradient(135deg, #667eea, #764ba2);
    border-radius: 4px;
}

.irs-handle {
    position: absolute;
    display: block;
    box-sizing: border-box;
    cursor: pointer;
    width: 24px;
    height: 24px;
    top: 17px;
    background: white;
    border: 3px solid #667eea;
    border-radius: 50%;
    box-shadow: 0 2px 8px rgba(0,0,0,0.15);
}

.irs-handle:hover {
    background: #667eea;
}

.irs-min, .irs-max {
    color: #718096;
    font-size: 12px;
    line-height: 1.333;
    text-shadow: none;
    top: 0;
    padding: 1px 3px;
    background: rgba(0,0,0,0.1);
    border-radius: 3px;
}

.irs-from, .irs-to, .irs-single {
    color: white;
    font-size: 12px;
    line-height: 1.333;
    text-shadow: none;
    padding: 4px 8px;
    background: #667eea;
    border-radius: 6px;
    white-space: nowrap;
}
</style>

<!--Plugin CSS file with desired skin-->
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/ion-rangeslider/2.3.1/css/ion.rangeSlider.min.css"/>

<!--jQuery-->
<script src="https://cdnjs.cloudflare.com/ajax/libs/jquery/3.4.1/jquery.min.js"></script>

<!--Plugin JavaScript file-->
<script src="https://cdnjs.cloudflare.com/ajax/libs/ion-rangeslider/2.3.1/js/ion.rangeSlider.min.js"></script>

<div class="credit-page">
    <!-- Hero Section -->
    <section class="hero-section">
        <div class="hero-content">
            <h1 class="hero-title">Credit Auto în Moldova</h1>
            <p class="hero-subtitle">
                Obțineți creditul auto ideal cu condiții avantajoase și aprobare rapidă. 
                Calculați rata lunară și alegeți cea mai bună opțiune pentru dvs.
            </p>
        </div>
    </section>

    <!-- Calculator Section -->
    <section class="calculator-section">
        <h2 class="calculator-title">Calculator Credit Auto</h2>
        
        <div class="calculator-grid">
            <div class="input-group">
                <label class="input-label" for="suma_creditului">Suma creditului (EUR)</label>
                <input type="text" id="suma_creditului" class="input-field" value="25000">
                <div class="slider-container">
                    <input type="text" id="suma-slider" name="suma_slider">
                </div>
            </div>
            
            <div class="input-group">
                <label class="input-label" for="termen_creditului">Termenul (luni)</label>
                <input type="text" id="termen_creditului" class="input-field" value="36">
                <div class="slider-container">
                    <input type="text" id="termen-slider" name="termen_slider">
                </div>
            </div>
        </div>

        <div class="results-section">
            <div class="result-title">Rata lunară estimată</div>
            <div class="result-amount" id="rata-lunara">€750</div>
            <div class="result-range">
                De la <span id="rata-min">€720</span> până la <span id="rata-max">€780</span>
            </div>
        </div>
    </section>

    <!-- Features Section -->
    <section class="features-section">
        <h2 class="section-title">Avantajele Creditului Auto</h2>
        
        <div class="features-grid">
            <div class="feature-card">
                <div class="feature-icon">🚗</div>
                <h3 class="feature-title">Credit Personal Auto</h3>
                <p class="feature-description">Confortul și libertatea de mișcare – prioritatea noastră.</p>
                <ul class="feature-list">
                    <li>Suma până la €50,000</li>
                    <li>Fără CASCO obligatoriu</li>
                    <li>Orice an de fabricație</li>
                    <li>Finanțare până la 100%</li>
                    <li>Aprobare în 1 oră</li>
                    <li>Rambursare anticipată fără penalități</li>
                </ul>
            </div>

            <div class="feature-card">
                <div class="feature-icon">🏢</div>
                <h3 class="feature-title">Credit pentru Afaceri</h3>
                <p class="feature-description">Dezvoltați afacerea cu un mijloc de transport fiabil.</p>
                <ul class="feature-list">
                    <li>Pentru persoane fizice și juridice</li>
                    <li>Plata în numerar sau transfer</li>
                    <li>Finanțare pentru automobile comandate</li>
                    <li>Refinanțare credit existent</li>
                    <li>Sprijin pentru startup-uri</li>
                    <li>Fără restricții de circulație</li>
                </ul>
            </div>

            <div class="feature-card">
                <div class="feature-icon">📋</div>
                <h3 class="feature-title">Leasing Auto</h3>
                <p class="feature-description">Soluție modernă pentru utilizarea eficientă a automobilului.</p>
                <ul class="feature-list">
                    <li>Gestionarea optimă a bugetului</li>
                    <li>Finanțare până la 100%</li>
                    <li>Condiții transparente</li>
                    <li>Pentru toate categoriile de clienți</li>
                    <li>Durată flexibilă 12-60 luni</li>
                    <li>Fără comisioane ascunse</li>
                </ul>
            </div>
        </div>
    </section>

    <!-- Partners Section -->
    <section class="partners-section">
        <h2 class="section-title">Partenerii Noștri de Încredere</h2>
        <p style="text-align: center; color: #718096; font-size: 1.1rem; margin-bottom: 40px;">
            Colaborăm doar cu organizații financiare verificate și respectate din Moldova
        </p>
        
        <div class="partners-grid">
            <div class="partner-card">
                <div class="partner-name">Microinvest.md</div>
                <div class="partner-description">Procedură simplă și rapidă</div>
            </div>
            <div class="partner-card">
                <div class="partner-name">Leasing.md</div>
                <div class="partner-description">Experți recunoscuți în leasing</div>
            </div>
            <div class="partner-card">
                <div class="partner-name">BT Leasing</div>
                <div class="partner-description">Standarde europene de fiabilitate</div>
            </div>
            <div class="partner-card">
                <div class="partner-name">Primero.md</div>
                <div class="partner-description">Abordări inovatoare</div>
            </div>
            <div class="partner-card">
                <div class="partner-name">Victoriabank</div>
                <div class="partner-description">Una dintre cele mai mari bănci din Moldova</div>
            </div>
        </div>
    </section>

    <!-- CTA Section -->
    <section class="cta-section">
        <h2 class="cta-title">Gata să Obțineți Creditul Auto?</h2>
        <p class="cta-description">
            Contactați-ne astăzi pentru o consultație gratuită și aflați cum vă putem ajuta 
            să obțineți automobilul dorit cu cele mai bune condiții de credit.
        </p>
        <div class="cta-buttons">
            <a href="/ro/contacts" class="btn btn-primary">Contactați-ne</a>
            <a href="tel:+37322123456" class="btn btn-secondary">Sunați acum</a>
        </div>
    </section>
</div>

<script>
$(document).ready(function() {
    // Initialize sliders
    const sumaSlider = $("#suma-slider").ionRangeSlider({
        skin: "round",
        min: 2000,
        max: 50000,
        from: 25000,
        step: 500,
        prefix: "€",
        prettify_enabled: true,
        prettify_separator: ",",
        onStart: function(data) {
            $("#suma_creditului").val(data.from);
        },
        onChange: function(data) {
            $("#suma_creditului").val(data.from);
            calculatePayment();
        }
    }).data("ionRangeSlider");

    const termenSlider = $("#termen-slider").ionRangeSlider({
        skin: "round",
        min: 6,
        max: 60,
        from: 36,
        step: 1,
        postfix: " luni",
        prettify_enabled: true,
        onStart: function(data) {
            $("#termen_creditului").val(data.from);
        },
        onChange: function(data) {
            $("#termen_creditului").val(data.from);
            calculatePayment();
        }
    }).data("ionRangeSlider");

    // Handle input field changes
    $("#suma_creditului").on("input", function() {
        let val = parseInt($(this).val().replace(/[^0-9]/g, ''), 10);
        if (!isNaN(val)) {
            val = Math.max(2000, Math.min(50000, val));
            val = Math.round(val / 500) * 500;
            $(this).val(val);
            sumaSlider.update({ from: val });
            calculatePayment();
        }
    });

    $("#termen_creditului").on("input", function() {
        let val = parseInt($(this).val(), 10);
        if (!isNaN(val)) {
            val = Math.max(6, Math.min(60, val));
            $(this).val(val);
            termenSlider.update({ from: val });
            calculatePayment();
        }
    });

    function calculatePayment() {
        const suma = parseInt($("#suma_creditului").val(), 10) || 25000;
        const termen = parseInt($("#termen_creditului").val(), 10) || 36;
        
        // Interest rates (annual)
        const rateMin = 0.08; // 8%
        const rateMax = 0.15; // 15%
        
        // Convert to monthly rates
        const monthlyRateMin = rateMin / 12;
        const monthlyRateMax = rateMax / 12;
        
        // Calculate monthly payments using PMT formula
        const pmtMin = suma * (monthlyRateMin * Math.pow(1 + monthlyRateMin, termen)) / 
                      (Math.pow(1 + monthlyRateMin, termen) - 1);
        const pmtMax = suma * (monthlyRateMax * Math.pow(1 + monthlyRateMax, termen)) / 
                      (Math.pow(1 + monthlyRateMax, termen) - 1);
        
        const pmtAvg = (pmtMin + pmtMax) / 2;
        
        // Update display
        $("#rata-lunara").text("€" + Math.round(pmtAvg).toLocaleString());
        $("#rata-min").text("€" + Math.round(pmtMin).toLocaleString());
        $("#rata-max").text("€" + Math.round(pmtMax).toLocaleString());
    }

    // Initial calculation
    calculatePayment();

    // Smooth scrolling for anchor links
    $('a[href^="#"]').on('click', function(event) {
        var target = $(this.getAttribute('href'));
        if( target.length ) {
            event.preventDefault();
            $('html, body').stop().animate({
                scrollTop: target.offset().top - 100
            }, 1000);
        }
    });

    // Add animation on scroll
    function animateOnScroll() {
        $('.feature-card, .partner-card').each(function() {
            const elementTop = $(this).offset().top;
            const elementBottom = elementTop + $(this).outerHeight();
            const viewportTop = $(window).scrollTop();
            const viewportBottom = viewportTop + $(window).height();
            
            if (elementBottom > viewportTop && elementTop < viewportBottom) {
                $(this).addClass('animate-in');
            }
        });
    }

    $(window).on('scroll', animateOnScroll);
    animateOnScroll(); // Initial check
});
</script>

<style>
/* Animation styles */
.feature-card, .partner-card {
    opacity: 0;
    transform: translateY(30px);
    transition: all 0.6s ease;
}

.feature-card.animate-in, .partner-card.animate-in {
    opacity: 1;
    transform: translateY(0);
}

/* Additional responsive improvements */
@media (max-width: 480px) {
    .hero-title {
        font-size: 2rem;
    }
    
    .hero-subtitle {
        font-size: 1.1rem;
    }
    
    .calculator-title {
        font-size: 2rem;
    }
    
    .section-title {
        font-size: 2.2rem;
    }
    
    .result-amount {
        font-size: 2.5rem;
    }
}
</style>
