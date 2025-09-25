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
<link rel="stylesheet" type="text/css" href="/content/site/page/new_pages/sale/sale.css?v=1.2">
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

<div id="sale-page">
    <section class="sale-section sale-intro sale-animated">
        <div class="sale-background"></div>
        <div class="sale-container">
            <div class="sale-intro__form">
                <div class="sale-form" id="bitrix-form-top">
                    <!-- Bitrix24 form will be loaded here -->
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
                <article class="sale-card spin-on-scroll">
                    <div class="sale-card__icon sale-icon--badge"></div>
                    <h3><?=htmlspecialchars($benefitItems[0] ?? '', ENT_QUOTES, 'UTF-8')?></h3>
                </article>
                <article class="sale-card glow-on-scroll">
                    <div class="sale-card__icon sale-icon--contract"></div>
                    <h3><?=htmlspecialchars($benefitItems[1] ?? '', ENT_QUOTES, 'UTF-8')?></h3>
                </article>
                <article class="sale-card pulse-on-scroll">
                    <div class="sale-card__icon sale-icon--shield"></div>
                    <h3><?=htmlspecialchars($benefitItems[2] ?? '', ENT_QUOTES, 'UTF-8')?></h3>
                </article>
                <article class="sale-card sway-on-scroll">
                    <div class="sale-card__icon sale-icon--speaker"></div>
                    <h3><?=htmlspecialchars($benefitItems[3] ?? '', ENT_QUOTES, 'UTF-8')?></h3>
                </article>
                <article class="sale-card shimmer-on-scroll">
                    <div class="sale-card__icon sale-icon--rocket"></div>
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
            <div class="sale-steps" data-mobile-snap>
                <div class="sale-step">
                    <div class="sale-step__icon sale-icon--garage"></div>
                    <p><?=htmlspecialchars($howSteps[0] ?? '', ENT_QUOTES, 'UTF-8')?></p>
                </div>
                <div class="sale-step">
                    <div class="sale-step__icon sale-icon--inspection"></div>
                    <p><?=htmlspecialchars($howSteps[1] ?? '', ENT_QUOTES, 'UTF-8')?></p>
                </div>
                <div class="sale-step">
                    <div class="sale-step__icon sale-icon--pen"></div>
                    <p><?=htmlspecialchars($howSteps[2] ?? '', ENT_QUOTES, 'UTF-8')?></p>
                </div>
                <div class="sale-step">
                    <div class="sale-step__icon sale-icon--wash"></div>
                    <p><?=htmlspecialchars($howSteps[3] ?? '', ENT_QUOTES, 'UTF-8')?></p>
                </div>
                <div class="sale-step">
                    <div class="sale-step__icon sale-icon--megaphone"></div>
                    <p><?=htmlspecialchars($howSteps[4] ?? '', ENT_QUOTES, 'UTF-8')?></p>
                </div>
                <div class="sale-step">
                    <div class="sale-step__icon sale-icon--handshake"></div>
                    <p><?=htmlspecialchars($howSteps[5] ?? '', ENT_QUOTES, 'UTF-8')?></p>
                </div>
                <div class="sale-step">
                    <div class="sale-step__icon sale-icon--cash"></div>
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
                    <div class="sale-compare__icon sale-icon--clock"></div>
                    <p><?=htmlspecialchars($compareItems[0] ?? '', ENT_QUOTES, 'UTF-8')?></p>
                </div>
                <div class="sale-compare__card">
                    <div class="sale-compare__icon sale-icon--security"></div>
                    <p><?=htmlspecialchars($compareItems[1] ?? '', ENT_QUOTES, 'UTF-8')?></p>
                </div>
                <div class="sale-compare__card">
                    <div class="sale-compare__icon sale-icon--deal"></div>
                    <p><?=htmlspecialchars($compareItems[2] ?? '', ENT_QUOTES, 'UTF-8')?></p>
                </div>
                <div class="sale-compare__card">
                    <div class="sale-compare__icon sale-icon--trust"></div>
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
                <div id="bitrix-form-bottom"></div>
            </div>
        </div>
    </section>
</div>

<script>
(function() {
 
    if (!window.b24FormLoaded) {
        window.b24FormLoaded = true;
        var script = document.createElement('script');
        script.async = true;
        script.src = 'https://cdn-ru.bitrix24.ru/b33145896/crm/form/loader_42.js?' + Math.floor(Date.now()/180000);
        script.onload = function() {
           
            if (window.BX24 && window.BX24.loadForm) {
                // Top form
                window.BX24.loadForm('inline/42/u65756', {
                    id: 'bitrix-form-top',
                    skipMoving: true
                });
                // Bottom form  
                window.BX24.loadForm('inline/42/u65756', {
                    id: 'bitrix-form-bottom',
                    skipMoving: true
                });
            }
        };
        document.head.appendChild(script);
    }
})();
</script>
