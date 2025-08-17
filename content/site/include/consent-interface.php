<?php
/**
 * Modern Consent Interface Component
 * Replaces the old consent system with improved UX and Google Consent Mode v2 compliance
 */

// Get language strings
$consentStrings = [
    'ro' => [
        'title' => 'Setări Cookie-uri',
        'subtitle' => 'Controlează cum sunt utilizate datele tale',
        'description' => 'Respectăm confidențialitatea ta. Alege ce tipuri de cookie-uri să accepti.',
        'privacy_link' => 'Politica de confidențialitate',
        'banner_text' => 'Folosim cookie-uri pentru experiența ta.',
        'accept_all' => 'Accept toate',
        'accept_essential' => 'Esențiale',
        'customize' => 'Personalizează',
        'save_preferences' => 'Salvează',
        'required_badge' => 'Obligatoriu',
        'consent_types' => [
            'functionality_storage' => [
                'title' => 'Cookie-uri funcționale',
                'description' => 'Necesare pentru funcționarea de bază a site-ului (autentificare, preferințe limba)'
            ],
            'analytics_storage' => [
                'title' => 'Cookie-uri de analiză',
                'description' => 'Ne ajută să înțelegem cum folosești site-ul pentru a-l îmbunătăți'
            ],
            'ad_storage' => [
                'title' => 'Cookie-uri publicitare',
                'description' => 'Utilizate pentru afișarea de reclame relevante'
            ],
            'ad_personalization' => [
                'title' => 'Personalizare reclame',
                'description' => 'Personalizează reclamele în funcție de interesele tale'
            ],
            'personalization_storage' => [
                'title' => 'Cookie-uri de personalizare',
                'description' => 'Salvează preferințele tale pentru o experiență personalizată'
            ]
        ]
    ],
    'ru' => [
        'title' => 'Настройки Cookie',
        'subtitle' => 'Контролируйте использование ваших данных',
        'description' => 'Мы уважаем вашу конфиденциальность. Выберите типы cookie для принятия.',
        'privacy_link' => 'Читать политику конфиденциальности',
        'banner_text' => 'Мы используем cookie для улучшения вашего опыта на сайте.',
        'accept_all' => 'Принять все',
        'accept_essential' => 'Только необходимые',
        'customize' => 'Настроить',
        'save_preferences' => 'Сохранить',
        'required_badge' => 'Обязательно',
        'consent_types' => [
            'functionality_storage' => [
                'title' => 'Функциональные cookie',
                'description' => 'Необходимы для базовой работы сайта (авторизация, языковые предпочтения)'
            ],
            'analytics_storage' => [
                'title' => 'Аналитические cookie',
                'description' => 'Помогают понять, как вы используете сайт для его улучшения'
            ],
            'ad_storage' => [
                'title' => 'Рекламные cookie',
                'description' => 'Используются для показа релевантной рекламы'
            ],
            'ad_personalization' => [
                'title' => 'Персонализация рекламы',
                'description' => 'Персонализирует рекламу в соответствии с вашими интересами'
            ],
            'personalization_storage' => [
                'title' => 'Cookie персонализации',
                'description' => 'Сохраняет ваши предпочтения для персонализированного опыта'
            ]
        ]
    ],
    'en' => [
        'title' => 'Cookie Settings',
        'subtitle' => 'Control how your data is used',
        'description' => 'We respect your privacy. Choose which types of cookies to accept.',
        'privacy_link' => 'Read privacy policy',
        'banner_text' => 'We use cookies to improve your experience on our site.',
        'accept_all' => 'Accept All',
        'accept_essential' => 'Essential Only',
        'customize' => 'Customize',
        'save_preferences' => 'Save',
        'required_badge' => 'Required',
        'consent_types' => [
            'functionality_storage' => [
                'title' => 'Functional Cookies',
                'description' => 'Required for basic site functionality (authentication, language preferences)'
            ],
            'analytics_storage' => [
                'title' => 'Analytics Cookies',
                'description' => 'Help us understand how you use the site to improve it'
            ],
            'ad_storage' => [
                'title' => 'Advertising Cookies',
                'description' => 'Used to display relevant advertisements'
            ],
            'ad_personalization' => [
                'title' => 'Ad Personalization',
                'description' => 'Personalizes ads based on your interests'
            ],
            'personalization_storage' => [
                'title' => 'Personalization Cookies',
                'description' => 'Saves your preferences for a personalized experience'
            ]
        ]
    ]
];

// Get current language
$currentLang = $_COOKIE['lang'] ?? 'ro';
$strings = $consentStrings[$currentLang] ?? $consentStrings['ro'];
$privacyUrl = "/{$currentLang}/privacy";

// Generate consent options HTML
function generateConsentOptions($strings) {
    $requiredTypes = ['functionality_storage'];
    $html = '';
    
    foreach ($strings['consent_types'] as $type => $config) {
        $isRequired = in_array($type, $requiredTypes);
        $requiredClass = $isRequired ? 'required' : '';
        $requiredBadge = $isRequired ? '<span class="consent-required-badge">' . $strings['required_badge'] . '</span>' : '';
        
        $html .= "
        <div class=\"consent-option {$requiredClass}\" data-consent-toggle=\"{$type}\">
            <div class=\"consent-toggle\" data-consent=\"{$type}\"></div>
            <div class=\"consent-option-content\">
                <h3 class=\"consent-option-title\">
                    {$config['title']}
                    {$requiredBadge}
                </h3>
                <p class=\"consent-option-description\">{$config['description']}</p>
            </div>
        </div>";
    }
    
    return $html;
}
?>

<!-- Modern Consent Banner (Hidden by default, shown by JS) -->
<div id="consent-banner" class="consent-banner" style="display: none;">
    <div class="consent-banner-content">
        <div class="consent-banner-text">
            <?php echo $strings['banner_text']; ?>
            <a href="<?php echo $privacyUrl; ?>" target="_blank"><?php echo $strings['privacy_link']; ?></a>
        </div>
        <div class="consent-banner-actions">
            <button class="consent-btn consent-btn-outline" data-consent-action="accept-essential">
                <?php echo $strings['accept_essential']; ?>
            </button>
            <button class="consent-btn consent-btn-secondary" data-consent-action="customize">
                <?php echo $strings['customize']; ?>
            </button>
            <button class="consent-btn consent-btn-primary" data-consent-action="accept-all">
                <?php echo $strings['accept_all']; ?>
            </button>
        </div>
    </div>
</div>

<!-- Modern Consent Modal (Hidden by default, shown by JS) -->
<div id="consent-overlay" class="consent-overlay" style="display: none;">
    <div class="consent-modal" role="dialog" aria-labelledby="consent-title" aria-describedby="consent-description">
        <div class="consent-header">
            <h2 id="consent-title" class="consent-title"><?php echo $strings['title']; ?></h2>
            <p class="consent-subtitle"><?php echo $strings['subtitle']; ?></p>
        </div>
        
        <div class="consent-body">
            <p id="consent-description" class="consent-description">
                <?php echo $strings['description']; ?>
                <a href="<?php echo $privacyUrl; ?>" target="_blank"><?php echo $strings['privacy_link']; ?></a>
            </p>
            
            <div class="consent-options">
                <?php echo generateConsentOptions($strings); ?>
            </div>
        </div>
        
        <div class="consent-actions">
            <button class="consent-btn consent-btn-secondary" data-consent-action="accept-custom">
                <?php echo $strings['save_preferences']; ?>
            </button>
        </div>
    </div>
</div>

<script>
// Pass PHP data to JavaScript
window.consentConfig = {
    language: '<?php echo $currentLang; ?>',
    privacyUrl: '<?php echo $privacyUrl; ?>',
    strings: <?php echo json_encode($strings); ?>
};
</script>
