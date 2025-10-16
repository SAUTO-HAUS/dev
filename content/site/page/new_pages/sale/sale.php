<?php defined('_DOIT') or die('Restricted access');

// Load translations
$saleTranslations = require __DIR__ . '/sale_lang.php';

// Fix language detection order
$requestedLang = $_COOKIE['lang'] ?? 'ru';
$uriParts = explode('/', trim($_SERVER['REQUEST_URI'] ?? '', '/'));
if (isset($uriParts[0]) && in_array($uriParts[0], ['ro', 'ru', 'en'])) {
    $requestedLang = $uriParts[0];
}

$availableLocales = array_keys($saleTranslations);
if (!in_array($requestedLang, $availableLocales, true)) {
    $requestedLang = 'ru';
}
if (!isset($saleTranslations[$requestedLang])) {
    $requestedLang = 'ru';
}

// Set SEO meta data based on current language
if (isset($saleTranslations[$requestedLang]['meta'])) {
    $seo_title = $saleTranslations[$requestedLang]['meta']['title'];
    $seo_description = $saleTranslations[$requestedLang]['meta']['description'];
    $seo_keywords = $saleTranslations[$requestedLang]['meta']['keywords'];
    $seo_h1 = $saleTranslations[$requestedLang]['meta']['h1'];
}

$currentSaleTranslations = $saleTranslations[$requestedLang];
$fallbackSaleTranslations = $saleTranslations['ru'];

$saleTranslate = function (array $path) use ($currentSaleTranslations, $fallbackSaleTranslations) {
    $value = $currentSaleTranslations;
    foreach ($path as $segment) {
        if (is_array($value) && array_key_exists($segment, $value)) {
            $value = $value[$segment];
        } else {
            $value = $fallbackSaleTranslations;
            foreach ($path as $fallbackSegment) {
                if (is_array($value) && array_key_exists($fallbackSegment, $value)) {
                    $value = $value[$fallbackSegment];
                } else {
                    return '';
                }
            }
            return $value;
        }
    }

    return $value;
};

$introParagraphs = $saleTranslate(['intro', 'paragraphs']);
$introParagraphs = is_array($introParagraphs) ? $introParagraphs : [];

$benefitItems = $saleTranslate(['benefits', 'items']);
$benefitItems = is_array($benefitItems) ? $benefitItems : [];

$howSteps = $saleTranslate(['how', 'steps']);
$howSteps = is_array($howSteps) ? $howSteps : [];

$compareItems = $saleTranslate(['compare', 'items']);
$compareItems = is_array($compareItems) ? $compareItems : [];

$faqItems = $saleTranslate(['faq', 'items']);
$faqItems = is_array($faqItems) ? $faqItems : [];
?>
<link rel="stylesheet" type="text/css" href="/content/site/page/new_pages/sale/sale.css?v=1.7">
<script src="/content/site/page/new_pages/sale/sale.js?v=1.2" defer></script>

<!-- Structured Data for SEO -->
<script type="application/ld+json">
{
    "@context": "https://schema.org",
    "@type": "Service",
    "name": "<?=htmlspecialchars($saleTranslate(['intro', 'title']), ENT_QUOTES, 'UTF-8')?>",
    "description": "<?=htmlspecialchars($saleTranslate(['intro', 'paragraphs'])[0] ?? '', ENT_QUOTES, 'UTF-8')?>",
    "provider": {
        "@type": "Organization",
        "name": "Sauto Haus",
        "url": "https://sauto.md"
    },
    "serviceType": "<?=$saleTranslate(['meta', 'keywords'])?>",
    "areaServed": {
        "@type": "Country",
        "name": "Moldova"
    }
}
</script>

<style>
#sale-page {
    margin: 0 !important;
    padding: 0 !important;
}
#sale-page .sale-hero h1 {
    font-size: clamp(24px, 3.4vw, 34px) !important;
}
@media (max-width: 680px) {
    #sale-page .sale-hero h1 {
        font-size: 26px !important;
    }
}
.sale-section.sale-intro {
    margin: 10px 0 10px 0 !important;
    padding: 0 !important;
}
.sale-intro .sale-container {
    padding-top: 50px !important;
    padding-bottom: 50px !important;
}
.sale-background {
    background: linear-gradient(to right, #6d6d6d 0%, #5c5c5c 100%) !important;
    opacity: 1 !important;
}
.sale-hero__icons {
    position: absolute !important;
    top: -20px !important;
    left: -20px !important;
    right: auto !important;
}
.sale-section.sale-benefits {
    padding-top: 15px !important;
    padding-bottom: 15px !important;
    margin: 0 !important;
}
.sale-section.sale-intro {
    margin-bottom: 0 !important;
}
.sale-grid.sale-benefits__grid .sale-card {
    background-color: #f1f1f1 !important;
    border: 0.1rem solid #d7d8db !important;
}
.sale-grid.sale-benefits__grid {
    display: grid !important;
    grid-template-columns: repeat(5, 1fr) !important;
    gap: 20px !important;
}
.sale-hero.copy-block {
    padding-bottom: 10px !important;
}
.sale-section.sale-how {
    margin-top: 0 !important;
    padding-top: 25px !important;
    padding-bottom: 25px !important;
}
/* Desktop grid layout for sale-steps */
@media (min-width: 769px) {
    .sale-steps {
        display: grid !important;
        grid-template-columns: 1fr 1fr 1fr 1fr !important;
        grid-template-rows: 15rem 15rem !important;
        gap: 1rem !important;
        width: 100% !important;
        margin: 0 !important;
        padding: 0 !important;
        justify-content: center !important;
    }
    .sale-steps .sale-step:nth-child(1) {
        grid-row: 1 / span 2 !important;
        grid-column: 1 / 2 !important;
    }
    .sale-steps .sale-step:nth-child(2) { grid-row: 1 !important; grid-column: 2 !important; }
    .sale-steps .sale-step:nth-child(3) { grid-row: 1 !important; grid-column: 3 !important; }
    .sale-steps .sale-step:nth-child(4) { grid-row: 1 !important; grid-column: 4 !important; }
    .sale-steps .sale-step:nth-child(5) { grid-row: 2 !important; grid-column: 2 !important; }
    .sale-steps .sale-step:nth-child(6) { grid-row: 2 !important; grid-column: 3 !important; }
    .sale-steps .sale-step:nth-child(7) { grid-row: 2 !important; grid-column: 4 !important; }
}
.sale-steps .sale-step {
    background-color: #f1f1f1 !important;
    border: 0.1rem solid #d7d8db !important;
}
.sale-section.sale-compare {
    margin-top: 0 !important;
    padding-top: 25px !important;
    padding-bottom: 25px !important;
}
#sale-page .sale-compare__card {
    background-color: #ffffff !important;
    border: 0.1rem solid #d7d8db !important;
}
.sale-compare__grid .sale-compare__card {
    background-color: #ffffff !important;
    border: 0.1rem solid #d7d8db !important;
}
.sale-compare__card.is-visible {
    background-color: #ffffff !important;
    border: 0.1rem solid #d7d8db !important;
}
#sale-page .sale-compare__card.is-visible {
    background: #ffffff !important;
    background-color: #ffffff !important;
    background-image: none !important;
    border: 0.1rem solid #d7d8db !important;
}
#sale-page .sale-section.sale-compare .sale-compare__card {
    background: #ffffff !important;
    background-color: #ffffff !important;
    background-image: none !important;
    border: 0.1rem solid #d7d8db !important;
}
.sale-section.sale-security {
    margin-top: 0 !important;
    padding-top: 10px !important;
    padding-bottom: 10px !important;
}
.sale-section.sale-faq {
    margin-top: 0 !important;
    padding-top: 15px !important;
    padding-bottom: 15px !important;
}
.sale-container {
    margin-top: 20px !important;
}
/* Desktop - equal padding/margin for sale-container */
@media (min-width: 769px) {
    .sale-container {
        margin: 15px auto !important;
        padding: 15px 0 !important;
    }
}
.sale-steps .sale-step:nth-child(1) {
    position: relative !important;
}
.sale-steps .sale-step:nth-child(1) .category-car-image {
    position: absolute !important;
    bottom: 0px !important;
    left: 50% !important;
    transform: translateX(-50%) !important;
    margin-top: 0 !important;
    text-align: center !important;
}
.sale-step .car-category-image {
    max-width: 100% !important;
    width: 100% !important;
    height: auto !important;
    opacity: 0.9 !important;
}
#sale-page .sale-steps .sale-step:nth-child(1) .category-car-image .car-category-image {
    max-width: 100% !important;
    width: 100% !important;
    height: auto !important;
}
/* Mobile version */
@media (max-width: 768px) {
    #sale-page {
        margin-top: 40px !important;
    }
    #sale-page .sale-section.sale-intro {
        padding-top: 10px !important;
        padding-bottom: 10px !important;
    }
    #sale-page .sale-hero.copy-block {
        padding: 0 20px !important;
        margin-top: -15px !important;
    }
    #sale-page .sale-hero__icons {
        top: -40px !important;
        left: 0px !important;
    }
    #sale-page .sale-intro .sale-container {
        display: flex !important;
        flex-direction: column !important;
        grid-template-columns: none !important;
        gap: 20px !important;
    }
    .sale-steps .sale-step:nth-child(1) .category-car-image {
        display: none !important;
    }
    .sale-step {
        min-width: 250px !important;
        max-width: 250px !important;
        width: 250px !important;
        height: 200px !important;
        padding: 20px !important;
        box-sizing: border-box !important;
        display: flex !important;
        flex-direction: column !important;
        justify-content: flex-start !important;
        word-wrap: break-word !important;
        overflow-wrap: break-word !important;
    }
    .sale-steps {
        overflow-x: auto !important;
        overflow-y: hidden !important;
        touch-action: pan-x !important;
    }
    .sale-how::after {
        display: none !important;
        content: '' !important;
    }
    #sale-page .sale-grid.sale-benefits__grid {
        display: flex !important;
        flex-direction: column !important;
        grid-template-columns: none !important;
        gap: 15px !important;
    }
}
/* Override CSS file styles for smaller screens */
@media (max-width: 680px) {
    #sale-page .sale-section.sale-intro {
        padding-top: 10px !important;
        padding-bottom: 10px !important;
    }
}
</style>

<script>
document.addEventListener('DOMContentLoaded', function() {
    setTimeout(function() {
        const compareCards = document.querySelectorAll('.sale-compare__card');
        compareCards.forEach(function(card) {
            card.style.setProperty('background', '#ffffff', 'important');
            card.style.setProperty('background-color', '#ffffff', 'important');
            card.style.setProperty('background-image', 'none', 'important');
            card.style.setProperty('border', '0.1rem solid #d7d8db', 'important');
        });
        
        const firstStep = document.querySelector('.sale-steps .sale-step:nth-child(1)');
        const carImage = document.querySelector('.sale-steps .sale-step:nth-child(1) .category-car-image');
        if (firstStep) {
            firstStep.style.setProperty('position', 'relative', 'important');
        }
        if (carImage) {
            carImage.style.setProperty('position', 'absolute', 'important');
            carImage.style.setProperty('bottom', '10px', 'important');
            carImage.style.setProperty('left', '50%', 'important');
            carImage.style.setProperty('transform', 'translateX(-50%)', 'important');
            carImage.style.setProperty('margin-top', '0', 'important');
        }
        
        // Force padding for sale-intro section on mobile
        if (window.innerWidth <= 768) {
            const saleIntro = document.querySelector('.sale-section.sale-intro');
            if (saleIntro) {
                saleIntro.style.setProperty('padding-top', '10px', 'important');
                saleIntro.style.setProperty('padding-bottom', '10px', 'important');
            }
        }
    }, 100);
});
</script>

<div id="sale-page">
    <section class="sale-section sale-intro sale-animated">
        <div class="sale-background"></div>
        <div class="sale-container">
            <div class="sale-intro__form">
                <div class="sale-form">
                    <script data-b24-form="inline/42/u65756" data-skip-moving="true">
                    (function(w,d,u){
                    var s=d.createElement('script');s.async=true;s.src=u+'?'+(Date.now()/180000|0);
                    var h=d.getElementsByTagName('script')[0];h.parentNode.insertBefore(s,h);
                    })(window,document,'https://cdn-ru.bitrix24.ru/b33145896/crm/form/loader_42.js');
                    </script>
                </div>
            </div>
            <div class="sale-intro__content">
                <div class="sale-hero copy-block">
                    <div class="sale-hero__icons">
                        <div class="sale-hero__icon sale-icon--wheel" aria-label="<?=$saleTranslate(['intro', 'icon_wheel']) ?: 'Автомобильное колесо'?>">
                            <svg viewBox="0 0 120 120" xmlns="http://www.w3.org/2000/svg" role="img">
                                <title><?=$saleTranslate(['intro', 'icon_wheel']) ?: 'Автомобильное колесо'?></title>
                                <circle cx="60" cy="60" r="54" fill="none" stroke="rgba(255,255,255,0.4)" stroke-width="4" />
                                <circle cx="60" cy="60" r="36" fill="none" stroke="rgba(255,255,255,0.6)" stroke-width="8" />
                                <path d="M60 12 L68 60 L60 108 L52 60 Z" fill="rgba(255,255,255,0.45)" />
                                <path d="M12 60 L60 52 L108 60 L60 68 Z" fill="rgba(255,255,255,0.3)" />
                                <circle cx="60" cy="60" r="8" fill="#FF0304" />
                            </svg>
                        </div>
                        <div class="sale-hero__icon sale-icon--speed" aria-label="<?=$saleTranslate(['intro', 'icon_speed']) ?: 'Скорость'?>">
                            <svg viewBox="0 0 160 100" xmlns="http://www.w3.org/2000/svg" role="img">
                                <title><?=$saleTranslate(['intro', 'icon_speed']) ?: 'Скорость'?></title>
                                <path d="M20 80 Q80 10 140 80" fill="none" stroke="rgba(255,255,255,0.6)" stroke-width="10" stroke-linecap="round" />
                                <circle cx="140" cy="80" r="10" fill="#FF0304" />
                                <path d="M80 80 L110 40" stroke="#FF0304" stroke-width="8" stroke-linecap="round" />
                                <circle cx="80" cy="80" r="12" fill="#101820" />
                                <circle cx="80" cy="80" r="6" fill="#FF0304" />
                            </svg>
                        </div>
                    </div>
                    <h1><?=htmlspecialchars($saleTranslate(['intro', 'title']), ENT_QUOTES, 'UTF-8')?></h1>
                    <?php foreach ($introParagraphs as $paragraph): ?>
                        <p><?=htmlspecialchars($paragraph, ENT_QUOTES, 'UTF-8')?></p>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>
    </section>

    <section class="sale-section sale-benefits sale-animated">
        <div class="sale-container">
            <div class="sale-section__header">
                <div class="section-pretitle"><?=htmlspecialchars($saleTranslate(['benefits', 'pretitle']), ENT_QUOTES, 'UTF-8')?></div>
                <h2><?=htmlspecialchars($saleTranslate(['benefits', 'title']), ENT_QUOTES, 'UTF-8')?></h2>
            </div>
            <div class="sale-grid sale-benefits__grid">
                <article class="sale-card">
                    <div style="background: none !important; border-radius: 0 !important; box-shadow: none !important; padding: 0 !important; width: auto !important; height: auto !important;">
                        <img src="/content/site/page/new_pages/sale/icons/sale-1-1.png" alt="Feature Icon" width="64" height="64" style="background: transparent !important; display: block !important;">
                    </div>
                    <h3><?=htmlspecialchars($benefitItems[0] ?? '', ENT_QUOTES, 'UTF-8')?></h3>
                </article>
                <article class="sale-card">
                    <div style="background: none !important; border-radius: 0 !important; box-shadow: none !important; padding: 0 !important; width: auto !important; height: auto !important;">
                        <img src="/content/site/page/new_pages/sale/icons/sale-1-2.png" alt="Feature Icon" width="64" height="64" style="background: transparent !important; display: block !important;">
                    </div>
                    <h3><?=htmlspecialchars($benefitItems[1] ?? '', ENT_QUOTES, 'UTF-8')?></h3>
                </article>
                <article class="sale-card">
                    <div style="background: none !important; border-radius: 0 !important; box-shadow: none !important; padding: 0 !important; width: auto !important; height: auto !important;">
                        <img src="/content/site/page/new_pages/sale/icons/sale-1-3.png" alt="Feature Icon" width="64" height="64" style="background: transparent !important; display: block !important;">
                    </div>
                    <h3><?=htmlspecialchars($benefitItems[2] ?? '', ENT_QUOTES, 'UTF-8')?></h3>
                </article>
                <article class="sale-card">
                    <div style="background: none !important; border-radius: 0 !important; box-shadow: none !important; padding: 0 !important; width: auto !important; height: auto !important;">
                        <img src="/content/site/page/new_pages/sale/icons/sale-1-4.png" alt="Feature Icon" width="64" height="64" style="background: transparent !important; display: block !important;">
                    </div>
                    <h3><?=htmlspecialchars($benefitItems[3] ?? '', ENT_QUOTES, 'UTF-8')?></h3>
                </article>
                <article class="sale-card">
                    <div style="background: none !important; border-radius: 0 !important; box-shadow: none !important; padding: 0 !important; width: auto !important; height: auto !important;">
                        <img src="/content/site/page/new_pages/sale/icons/sale-1-5.png" alt="Feature Icon" width="64" height="64" style="background: transparent !important; display: block !important;">
                    </div>
                    <h3><?=htmlspecialchars($benefitItems[4] ?? '', ENT_QUOTES, 'UTF-8')?></h3>
                </article>
            </div>
        </div>
    </section>

    <section class="sale-section sale-how sale-animated">
        <div class="sale-container">
            <div class="sale-section__header">
                <div class="section-pretitle"><?=htmlspecialchars($saleTranslate(['how', 'pretitle']), ENT_QUOTES, 'UTF-8')?></div>
                <h2><?=htmlspecialchars($saleTranslate(['how', 'title']), ENT_QUOTES, 'UTF-8')?></h2>
            </div>
            <div class="sale-steps">
                <div class="sale-step">
                    <div style="background: none !important; border-radius: 0 !important; box-shadow: none !important; padding: 0 !important; width: auto !important; height: auto !important;">
                        <img src="/content/site/page/new_pages/sale/icons/sale-2-1.png" alt="Feature Icon" width="64" height="64" style="background: transparent !important; display: block !important;">
                    </div>
                    <p><?=htmlspecialchars($howSteps[0] ?? '', ENT_QUOTES, 'UTF-8')?></p>
                    <div class="category-car-image" style="position: absolute !important; top: 195px !important; left: 50% !important; transform: translateX(-50%) !important; width: 100% !important; text-align: center !important;">
                        <img src="/content/site/page/new_pages/credit/credit-media/car-2.png" alt="Sale Car" class="car-category-image" style="width: 100% !important; max-width: 100% !important; height: auto !important; display: block !important;">
                    </div>
                </div>
                <div class="sale-step">
                    <div style="background: none !important; border-radius: 0 !important; box-shadow: none !important; padding: 0 !important; width: auto !important; height: auto !important;">
                        <img src="/content/site/page/new_pages/sale/icons/sale-2-2.png" alt="Feature Icon" width="64" height="64" style="background: transparent !important; display: block !important;">
                    </div>
                    <p><?=htmlspecialchars($howSteps[1] ?? '', ENT_QUOTES, 'UTF-8')?></p>
                </div>
                <div class="sale-step">
                    <div style="background: none !important; border-radius: 0 !important; box-shadow: none !important; padding: 0 !important; width: auto !important; height: auto !important;">
                        <img src="/content/site/page/new_pages/sale/icons/sale-2-3.png" alt="Feature Icon" width="64" height="64" style="background: transparent !important; display: block !important;">
                    </div>
                    <p><?=htmlspecialchars($howSteps[2] ?? '', ENT_QUOTES, 'UTF-8')?></p>
                </div>
                <div class="sale-step">
                    <div style="background: none !important; border-radius: 0 !important; box-shadow: none !important; padding: 0 !important; width: auto !important; height: auto !important;">
                        <img src="/content/site/page/new_pages/sale/icons/sale-2-4.png" alt="Feature Icon" width="64" height="64" style="background: transparent !important; display: block !important;">
                    </div>
                    <p><?=htmlspecialchars($howSteps[3] ?? '', ENT_QUOTES, 'UTF-8')?></p>
                </div>
                <div class="sale-step">
                    <div style="background: none !important; border-radius: 0 !important; box-shadow: none !important; padding: 0 !important; width: auto !important; height: auto !important;">
                        <img src="/content/site/page/new_pages/sale/icons/sale-2-5.png" alt="Feature Icon" width="64" height="64" style="background: transparent !important; display: block !important;">
                    </div>
                    <p><?=htmlspecialchars($howSteps[4] ?? '', ENT_QUOTES, 'UTF-8')?></p>
                </div>
                <div class="sale-step">
                    <div style="background: none !important; border-radius: 0 !important; box-shadow: none !important; padding: 0 !important; width: auto !important; height: auto !important;">
                        <img src="/content/site/page/new_pages/sale/icons/sale-2-6.png" alt="Feature Icon" width="64" height="64" style="background: transparent !important; display: block !important;">
                    </div>
                    <p><?=htmlspecialchars($howSteps[5] ?? '', ENT_QUOTES, 'UTF-8')?></p>
                </div>
                <div class="sale-step">
                    <div style="background: none !important; border-radius: 0 !important; box-shadow: none !important; padding: 0 !important; width: auto !important; height: auto !important;">
                        <img src="/content/site/page/new_pages/sale/icons/sale-2-7.png" alt="Feature Icon" width="64" height="64" style="background: transparent !important; display: block !important;">
                    </div>
                    <p><?=htmlspecialchars($howSteps[6] ?? '', ENT_QUOTES, 'UTF-8')?></p>
                </div>
            </div>
        </div>
    </section>

    <section class="sale-section sale-compare sale-animated">
        <div class="sale-container">
            <div class="sale-section__header">
                <div class="section-pretitle"><?=htmlspecialchars($saleTranslate(['compare', 'pretitle']), ENT_QUOTES, 'UTF-8')?></div>
                <h2><?=htmlspecialchars($saleTranslate(['compare', 'title']), ENT_QUOTES, 'UTF-8')?></h2>
            </div>
            <div class="sale-compare__grid">
                <div class="sale-compare__card">
                    <div style="background: none !important; border-radius: 0 !important; box-shadow: none !important; padding: 0 !important; width: auto !important; height: auto !important;">
                        <img src="/content/site/page/new_pages/sale/icons/sale-3-1.png" alt="Feature Icon" width="64" height="64" style="background: transparent !important; display: block !important;">
                    </div>
                    <p><?=htmlspecialchars($compareItems[0] ?? '', ENT_QUOTES, 'UTF-8')?></p>
                </div>
                <div class="sale-compare__card">
                    <div style="background: none !important; border-radius: 0 !important; box-shadow: none !important; padding: 0 !important; width: auto !important; height: auto !important;">
                        <img src="/content/site/page/new_pages/sale/icons/sale-3-2.png?v=3" alt="Feature Icon" width="64" height="64" style="background: transparent !important; display: block !important;">
                    </div>
                    <p><?=htmlspecialchars($compareItems[1] ?? '', ENT_QUOTES, 'UTF-8')?></p>
                </div>
                <div class="sale-compare__card">
                    <div style="background: none !important; border-radius: 0 !important; box-shadow: none !important; padding: 0 !important; width: auto !important; height: auto !important;">
                        <img src="/content/site/page/new_pages/sale/icons/sale-3-3.png" alt="Feature Icon" width="64" height="64" style="background: transparent !important; display: block !important;">
                    </div>
                    <p><?=htmlspecialchars($compareItems[2] ?? '', ENT_QUOTES, 'UTF-8')?></p>
                </div>
                <div class="sale-compare__card">
                    <div style="background: none !important; border-radius: 0 !important; box-shadow: none !important; padding: 0 !important; width: auto !important; height: auto !important;">
                        <img src="/content/site/page/new_pages/sale/icons/sale-3-4.png" alt="Feature Icon" width="64" height="64" style="background: transparent !important; display: block !important;">
                    </div>
                    <p><?=htmlspecialchars($compareItems[3] ?? '', ENT_QUOTES, 'UTF-8')?></p>
                </div>
            </div>
        </div>
    </section>

    <section class="sale-section sale-security sale-animated">
        <div class="sale-container">
            <div class="sale-section__header">
                <div class="section-pretitle"><?=htmlspecialchars($saleTranslate(['security', 'pretitle']), ENT_QUOTES, 'UTF-8')?></div>
                <h2><?=htmlspecialchars($saleTranslate(['security', 'title']), ENT_QUOTES, 'UTF-8')?></h2>
            </div>
            <div class="sale-security__content copy-block">
                <p><?=htmlspecialchars($saleTranslate(['security', 'text']), ENT_QUOTES, 'UTF-8')?></p>
            </div>
        </div>
    </section>

    <section class="sale-section sale-faq sale-animated">
        <div class="sale-container">
            <div class="sale-section__header">
                <div class="section-pretitle"><?=htmlspecialchars($saleTranslate(['faq', 'pretitle']), ENT_QUOTES, 'UTF-8')?></div>
                <h2><?=htmlspecialchars($saleTranslate(['faq', 'title']), ENT_QUOTES, 'UTF-8')?></h2>
            </div>
            <div class="sale-faq__tabs" role="tablist">
                <?php foreach ($faqItems as $index => $faq): ?>
                    <button class="sale-faq__tab" role="tab" aria-expanded="<?=($index === 0) ? 'true' : 'false'?>">
                        <span class="sale-faq__question"><?=htmlspecialchars($faq['question'] ?? '', ENT_QUOTES, 'UTF-8')?></span>
                        <span class="sale-faq__answer"><?=htmlspecialchars($faq['answer'] ?? '', ENT_QUOTES, 'UTF-8')?></span>
                    </button>
                <?php endforeach; ?>
            </div>
        </div>
    </section>

    <section class="sale-section sale-cta sale-animated">
        <div class="sale-container">
            <div class="sale-cta__content copy-block">
                <p><?=htmlspecialchars($saleTranslate(['cta', 'line1']), ENT_QUOTES, 'UTF-8')?><br><?=htmlspecialchars($saleTranslate(['cta', 'line2']), ENT_QUOTES, 'UTF-8')?></p>
            </div>
            <div class="sale-form sale-form--bottom">
                <script data-b24-form="inline/42/u65756" data-skip-moving="true">
                (function(w,d,u){
                var s=d.createElement('script');s.async=true;s.src=u+'?'+(Date.now()/180000|0);
                var h=d.getElementsByTagName('script')[0];h.parentNode.insertBefore(s,h);
                })(window,document,'https://cdn-ru.bitrix24.ru/b33145896/crm/form/loader_42.js?'+(Date.now()/180000|0));
                </script>
            </div>
        </div>
