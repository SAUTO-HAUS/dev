<?php defined( '_DOIT' ) or die( 'Restricted access' ); ?>

<?php
// Include language file
include_once('credit_lang.php');

// Include CSS and JS files for this page
$page_css = '/content/site/page/new_pages/credit/credit.css';
$page_js = '/content/site/page/new_pages/credit/credit.js';
?>

<!-- Include page-specific CSS -->
<link rel="stylesheet" href="<?php echo $page_css; ?>">

<!-- Include Ion Range Slider CSS -->
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/ion-rangeslider@2.3.1/css/ion.rangeSlider.min.css">

<div class="credit-page">
    <!-- Hero Section -->
    <section class="hero-section">
        <div class="hero-content">
            <h1 class="hero-title"><?php echo $lng['credit_title'] ?? 'Creditele Auto'; ?></h1>
            <p class="hero-subtitle"><?php echo $lng['credit_subtitle'] ?? 'Obțineți creditul auto perfect pentru nevoile dumneavoastră cu cele mai competitive rate din piață'; ?></p>
        </div>
    </section>

    <!-- Calculator Section -->
    <section class="calculator-section">
        <h2 class="calculator-title"><?php echo $lng['calculator_title'] ?? 'Calculator Credit Auto'; ?></h2>
        
        <div class="calculator-grid">
            <div class="input-group">
                <label for="suma_creditului" class="input-label"><?php echo $lng['loan_amount'] ?? 'Suma creditului'; ?></label>
                <input type="text" id="suma_creditului" class="input-field" value="25000" placeholder="€25,000">
                <div class="slider-container">
                    <input type="text" id="suma-slider" value="" name="suma-slider" />
                </div>
            </div>
            
            <div class="input-group">
                <label for="termen_creditului" class="input-label"><?php echo $lng['loan_term'] ?? 'Termenul creditului'; ?></label>
                <input type="text" id="termen_creditului" class="input-field" value="36" placeholder="36 luni">
                <div class="slider-container">
                    <input type="text" id="termen-slider" value="" name="termen-slider" />
                </div>
            </div>
        </div>
        
        <div class="results-section">
            <h3 class="result-title"><?php echo $lng['monthly_payment'] ?? 'Rata lunară estimată'; ?></h3>
            <div class="result-amount" id="rata-lunara">€750</div>
            <div class="result-range">
                <?php echo $lng['payment_range'] ?? 'Interval'; ?>: <span id="rata-min">€650</span> - <span id="rata-max">€850</span>
            </div>
        </div>
    </section>

    <!-- Features Section -->
    <section class="features-section">
        <h2 class="section-title"><?php echo $lng['credit_types'] ?? 'Tipuri de Credite'; ?></h2>
        
        <div class="features-grid">
            <div class="feature-card">
                <div class="feature-icon">🚗</div>
                <h3 class="feature-title"><?php echo $lng['personal_auto_credit'] ?? 'Credit Auto Personal'; ?></h3>
                <p class="feature-description"><?php echo $lng['personal_auto_desc'] ?? 'Soluția perfectă pentru achiziționarea automobilului personal cu condiții avantajoase.'; ?></p>
                <ul class="feature-list">
                    <li><?php echo $lng['feature_1'] ?? 'Rate competitive începând de la 8%'; ?></li>
                    <li><?php echo $lng['feature_2'] ?? 'Termen de rambursare până la 5 ani'; ?></li>
                    <li><?php echo $lng['feature_3'] ?? 'Aprobare rapidă în 24 ore'; ?></li>
                    <li><?php echo $lng['feature_4'] ?? 'Fără comisioane ascunse'; ?></li>
                </ul>
            </div>
            
            <div class="feature-card">
                <div class="feature-icon">🏢</div>
                <h3 class="feature-title"><?php echo $lng['business_credit'] ?? 'Credit Auto Business'; ?></h3>
                <p class="feature-description"><?php echo $lng['business_credit_desc'] ?? 'Finanțare specializată pentru flote auto și vehicule comerciale.'; ?></p>
                <ul class="feature-list">
                    <li><?php echo $lng['business_feature_1'] ?? 'Sume mari de finanțare'; ?></li>
                    <li><?php echo $lng['business_feature_2'] ?? 'Condiții flexibile de rambursare'; ?></li>
                    <li><?php echo $lng['business_feature_3'] ?? 'Consultanță specializată'; ?></li>
                    <li><?php echo $lng['business_feature_4'] ?? 'Avantaje fiscale'; ?></li>
                </ul>
            </div>
            
            <div class="feature-card">
                <div class="feature-icon">📋</div>
                <h3 class="feature-title"><?php echo $lng['leasing'] ?? 'Leasing Auto'; ?></h3>
                <p class="feature-description"><?php echo $lng['leasing_desc'] ?? 'Alternativa inteligentă la credit cu opțiuni flexibile de achiziție.'; ?></p>
                <ul class="feature-list">
                    <li><?php echo $lng['leasing_feature_1'] ?? 'Avans redus sau zero'; ?></li>
                    <li><?php echo $lng['leasing_feature_2'] ?? 'Rate lunare mici'; ?></li>
                    <li><?php echo $lng['leasing_feature_3'] ?? 'Opțiune de cumpărare la final'; ?></li>
                    <li><?php echo $lng['leasing_feature_4'] ?? 'Întreținere inclusă'; ?></li>
                </ul>
            </div>
        </div>
    </section>

    <!-- Partners Section -->
    <section class="partners-section">
        <h2 class="section-title"><?php echo $lng['our_partners'] ?? 'Partenerii Noștri'; ?></h2>
        <p style="text-align: center; color: #718096; font-size: 1.1rem; margin-bottom: 40px;">
            <?php echo $lng['partners_desc'] ?? 'Colaborăm cu instituțiile financiare de top pentru a vă oferi cele mai bune condiții'; ?>
        </p>
        
        <div class="partners-grid">
            <div class="partner-card">
                <div class="partner-name">BCR</div>
                <div class="partner-description"><?php echo $lng['bcr_desc'] ?? 'Banca Comercială Română'; ?></div>
            </div>
            <div class="partner-card">
                <div class="partner-name">BRD</div>
                <div class="partner-description"><?php echo $lng['brd_desc'] ?? 'Groupe Société Générale'; ?></div>
            </div>
            <div class="partner-card">
                <div class="partner-name">ING Bank</div>
                <div class="partner-description"><?php echo $lng['ing_desc'] ?? 'ING Bank România'; ?></div>
            </div>
            <div class="partner-card">
                <div class="partner-name">Raiffeisen</div>
                <div class="partner-description"><?php echo $lng['raiffeisen_desc'] ?? 'Raiffeisen Bank'; ?></div>
            </div>
            <div class="partner-card">
                <div class="partner-name">UniCredit</div>
                <div class="partner-description"><?php echo $lng['unicredit_desc'] ?? 'UniCredit Bank'; ?></div>
            </div>
            <div class="partner-card">
                <div class="partner-name">Alpha Bank</div>
                <div class="partner-description"><?php echo $lng['alpha_desc'] ?? 'Alpha Bank România'; ?></div>
            </div>
        </div>
    </section>


    <!-- Call to Action Section -->
    <section class="cta-section">
        <h2 class="cta-title"><?php echo $lng['ready_to_start'] ?? 'Gata să începeți?'; ?></h2>
        <p class="cta-description">
            <?php echo $lng['cta_desc'] ?? 'Contactați-ne astăzi pentru o consultație gratuită și descoperiți cea mai bună opțiune de finanțare pentru dumneavoastră.'; ?>
        </p>
        <div class="cta-buttons">
            <a href="/<?php echo $lang; ?>/contacts" class="btn btn-primary"><?php echo $lng['contact_us'] ?? 'Contactați-ne'; ?></a>
            <a href="tel:+40123456789" class="btn btn-secondary"><?php echo $lng['call_now'] ?? 'Sunați acum'; ?></a>
        </div>
    </section>
</div>

<!-- Include Ion Range Slider JS -->
<script src="https://cdn.jsdelivr.net/npm/ion-rangeslider@2.3.1/js/ion.rangeSlider.min.js"></script>

<!-- Include page-specific JS -->
<script src="<?php echo $page_js; ?>"></script>

    <!-- Calculator Section -->
    <section class="calculator-section">
        <h2 class="calculator-title"><?php echo $lng['calculator_title'] ?? 'Calculator Credit Auto'; ?></h2>
        
        <div class="calculator-grid">
            <div class="input-group">
                <label class="input-label" for="suma_creditului"><?php echo $lng['loan_amount_eur'] ?? 'Suma creditului (EUR)'; ?></label>
                <input type="text" id="suma_creditului" class="input-field" value="25000">
                <div class="slider-container">
                    <input type="text" id="suma-slider" name="suma_slider">
                </div>
            </div>
            
            <div class="input-group">
                <label class="input-label" for="termen_creditului"><?php echo $lng['loan_term_months'] ?? 'Termenul (luni)'; ?></label>
                <input type="text" id="termen_creditului" class="input-field" value="36">
                <div class="slider-container">
                    <input type="text" id="termen-slider" name="termen_slider">
                </div>
            </div>
        </div>

        <div class="results-section">
            <div class="result-title"><?php echo $lng['monthly_payment_est'] ?? 'Rata lunară estimată'; ?></div>
            <div class="result-amount" id="rata-lunara">€750</div>
            <div class="result-range">
                <?php echo $lng['payment_range_from'] ?? 'De la'; ?> <span id="rata-min">€720</span> <?php echo $lng['payment_range_to'] ?? 'până la'; ?> <span id="rata-max">€780</span>
            </div>
        </div>
    </section>

    <!-- Features Section -->
    <section class="features-section">
        <h2 class="section-title"><?php echo $lng['credit_advantages'] ?? 'Avantajele Creditului Auto'; ?></h2>
        
        <div class="features-grid">
            <div class="feature-card">
                <div class="feature-icon">🚗</div>
                <h3 class="feature-title"><?php echo $lng['personal_auto_credit'] ?? 'Credit Personal Auto'; ?></h3>
                <p class="feature-description"><?php echo $lng['personal_auto_desc'] ?? 'Confortul și libertatea de mișcare – prioritatea noastră.'; ?></p>
                <ul class="feature-list">
                    <li><?php echo $lng['personal_feature_1'] ?? 'Suma până la €50,000'; ?></li>
                    <li><?php echo $lng['personal_feature_2'] ?? 'Fără CASCO obligatoriu'; ?></li>
                    <li><?php echo $lng['personal_feature_3'] ?? 'Orice an de fabricație'; ?></li>
                    <li><?php echo $lng['personal_feature_4'] ?? 'Finanțare până la 100%'; ?></li>
                    <li><?php echo $lng['personal_feature_5'] ?? 'Aprobare în 1 oră'; ?></li>
                    <li><?php echo $lng['personal_feature_6'] ?? 'Rambursare anticipată fără penalități'; ?></li>
                </ul>
            </div>

            <div class="feature-card">
                <div class="feature-icon">🏢</div>
                <h3 class="feature-title"><?php echo $lng['business_credit'] ?? 'Credit pentru Afaceri'; ?></h3>
                <p class="feature-description"><?php echo $lng['business_credit_desc'] ?? 'Dezvoltați afacerea cu un mijloc de transport fiabil.'; ?></p>
                <ul class="feature-list">
                    <li><?php echo $lng['business_feature_1'] ?? 'Pentru persoane fizice și juridice'; ?></li>
                    <li><?php echo $lng['business_feature_2'] ?? 'Plata în numerar sau transfer'; ?></li>
                    <li><?php echo $lng['business_feature_3'] ?? 'Finanțare pentru automobile comandate'; ?></li>
                    <li><?php echo $lng['business_feature_4'] ?? 'Refinanțare credit existent'; ?></li>
                    <li><?php echo $lng['business_feature_5'] ?? 'Sprijin pentru startup-uri'; ?></li>
                    <li><?php echo $lng['business_feature_6'] ?? 'Fără restricții de circulație'; ?></li>
                </ul>
            </div>

            <div class="feature-card">
                <div class="feature-icon">📋</div>
                <h3 class="feature-title"><?php echo $lng['leasing'] ?? 'Leasing Auto'; ?></h3>
                <p class="feature-description"><?php echo $lng['leasing_desc'] ?? 'Soluție modernă pentru utilizarea eficientă a automobilului.'; ?></p>
                <ul class="feature-list">
                    <li><?php echo $lng['leasing_feature_1'] ?? 'Gestionarea optimă a bugetului'; ?></li>
                    <li><?php echo $lng['leasing_feature_2'] ?? 'Finanțare până la 100%'; ?></li>
                    <li><?php echo $lng['leasing_feature_3'] ?? 'Condiții transparente'; ?></li>
                    <li><?php echo $lng['leasing_feature_4'] ?? 'Pentru toate categoriile de clienți'; ?></li>
                    <li><?php echo $lng['leasing_feature_5'] ?? 'Durată flexibilă 12-60 luni'; ?></li>
                    <li><?php echo $lng['leasing_feature_6'] ?? 'Fără comisioane ascunse'; ?></li>
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
