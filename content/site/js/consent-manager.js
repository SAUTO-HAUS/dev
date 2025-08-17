/**
 * Modern Consent Manager with Google Consent Mode v2
 * Compliant with GDPR, CCPA, and other privacy regulations
 */
class ConsentManager {
    constructor(options = {}) {
        this.options = {
            storageKey: 'sauto_consent_v2',
            storageVersion: '2.1',
            expiryDays: 365,
            showBanner: true,
            showModal: false,
            autoShow: true,
            ...options
        };
        
        this.languageTexts = {
            'ro': {
                'title': 'Setări Cookie-uri',
                'subtitle': 'Controlează cum sunt utilizate datele tale',
                'description': 'Respectăm confidențialitatea ta. Alege ce tipuri de cookie-uri să accepti.',
                'privacy_link': 'Politica de confidențialitate',
                'banner_text': 'Folosim cookie-uri pentru a îmbunătăți experiența ta pe site.',
                'accept_all': 'Accept toate',
                'accept_essential': 'Esențiale',
                'customize': 'Personalizează',
                'save_preferences': 'Salvează',
                'footer_text': '',
                'footer_link': ''
            },
            'ru': {
                'title': 'Настройки Cookie',
                'subtitle': 'Контролируйте использование ваших данных',
                'description': 'Мы уважаем вашу конфиденциальность. Выберите типы файлов cookie для принятия.',
                'privacy_link': 'Политика конфиденциальности',
                'banner_text': 'Мы используем файлы cookie для улучшения вашего опыта на сайте.',
                'accept_all': 'Принять все',
                'accept_essential': 'Основные',
                'customize': 'Настроить',
                'save_preferences': 'Сохранить',
                'footer_text': '',
                'footer_link': ''
            },
            'en': {
                'title': 'Cookie Settings',
                'subtitle': 'Control how your data is used',
                'description': 'We respect your privacy. Choose which types of cookies to accept.',
                'privacy_link': 'Privacy Policy',
                'banner_text': 'We use cookies to improve your experience on our site.',
                'accept_all': 'Accept All',
                'accept_essential': 'Essential',
                'customize': 'Customize',
                'save_preferences': 'Save',
                'footer_text': '',
                'footer_link': ''
            }
        };

        this.consentTypes = {
            functionality_storage: { required: true, default: 'granted' },
            security_storage: { required: true, default: 'granted' },
            ad_storage: { required: false, default: 'denied' },
            ad_user_data: { required: false, default: 'denied' },
            ad_personalization: { required: false, default: 'denied' },
            analytics_storage: { required: false, default: 'denied' },
            personalization_storage: { required: false, default: 'denied' }
        };

        // Initialize with default values
        this.currentConsent = {
            functionality_storage: true,
            security_storage: true,
            ad_storage: false,
            ad_user_data: false,
            ad_personalization: false,
            analytics_storage: false,
            personalization_storage: false
        };
        this.isInitialized = false;
        
        this.init();
    }

    init() {
        if (this.isInitialized) return;

        console.log('ConsentManager init() called');

        // Initialize Google Consent Mode v2
        this.initGoogleConsentMode();

        // Set up event listeners first
        this.setupEventListeners();
        
        // Bind methods to preserve 'this' context
        this.acceptAll = this.acceptAll.bind(this);
        this.acceptEssential = this.acceptEssential.bind(this);
        this.showModal = this.showModal.bind(this);
        this.saveCustomPreferences = this.saveCustomPreferences.bind(this);
        this.getLanguageTexts = this.getLanguageTexts.bind(this);

        // Load existing consent or show consent interface
        this.loadConsent();

        this.isInitialized = true;

        console.log('ConsentManager initialized. Stored consent:', this.getStoredConsent());
    }

    initGoogleConsentMode() {
        // Initialize gtag if not available
        if (typeof gtag === 'undefined') {
            window.dataLayer = window.dataLayer || [];
            window.gtag = function() { dataLayer.push(arguments); };
        }

        // Set default consent states (denied for all non-essential)
        gtag('consent', 'default', {
            'functionality_storage': 'granted',
            'security_storage': 'granted',
            'ad_storage': 'denied',
            'ad_user_data': 'denied',
            'ad_personalization': 'denied',
            'analytics_storage': 'denied',
            'personalization_storage': 'denied'
        });
    }

    loadConsent() {
        const stored = this.getStoredConsent();
        console.log('loadConsent() called. Stored data:', stored);

        if (stored && this.isValidConsent(stored)) {
            // Update currentConsent but ensure required cookies are always true
            this.currentConsent = {
                ...stored.preferences,
                functionality_storage: true,
                security_storage: true
            };
            this.updateGoogleConsent();
            console.log('Valid consent found, updated currentConsent:', this.currentConsent);
            
            // Ensure no banner is visible
            const existingBanner = document.getElementById('consent-banner');
            if (existingBanner) {
                console.log('Removing existing banner due to valid consent');
                existingBanner.remove();
            }
            return;
        }

        // Show consent interface immediately if no valid consent
        console.log('No valid consent found, showing interface. Options:', this.options);
        if (this.options.autoShow) {
            if (this.options.showModal) {
                console.log('Showing modal...');
                this.showModal();
            } else if (this.options.showBanner) {
                console.log('Showing banner...');
                this.showBanner();
            }
        } else {
            console.log('AutoShow is disabled');
        }
    }

    getStoredConsent() {
        try {
            const stored = localStorage.getItem(this.options.storageKey);
            return stored ? JSON.parse(stored) : null;
        } catch (e) {
            console.warn('ConsentManager: Error reading stored consent', e);
            return null;
        }
    }

    isValidConsent(stored) {
        if (!stored || !stored.version || !stored.timestamp || !stored.preferences) {
            console.log('Invalid consent: missing required fields');
            return false;
        }

        // Check if consent is expired
        const expiryTime = stored.timestamp + (this.options.expiryDays * 24 * 60 * 60 * 1000);
        const isExpired = Date.now() > expiryTime;
        console.log('Consent expiry check:', {
            timestamp: stored.timestamp,
            expiryTime: expiryTime,
            now: Date.now(),
            isExpired: isExpired
        });
        
        if (isExpired) {
            console.log('Consent expired, will show banner');
            return false;
        }

        // Check if version matches
        if (stored.version !== this.options.storageVersion) {
            console.log('Version mismatch:', stored.version, 'vs', this.options.storageVersion);
            return false;
        }

        console.log('Consent is valid, banner will NOT show');
        return true;
    }

    saveConsent(preferences) {
        console.log('saveConsent() called with:', preferences);
        this.currentConsent = preferences;
        
        const consentData = {
            version: this.options.storageVersion,
            timestamp: Date.now(),
            preferences: preferences
        };

        try {
            localStorage.setItem(this.options.storageKey, JSON.stringify(consentData));
            console.log('Consent saved to localStorage:', consentData);
            
            // Verify it was saved
            const saved = localStorage.getItem(this.options.storageKey);
            console.log('Verification - stored data:', saved);
        } catch (e) {
            console.warn('ConsentManager: Error saving consent', e);
        }

        this.updateGoogleConsent();
    }

    updateGoogleConsent() {
        if (typeof gtag === 'undefined') return;
        if (!this.currentConsent) return;
        
        const consentUpdate = {};
        
        Object.keys(this.consentTypes).forEach(type => {
            consentUpdate[type] = this.currentConsent[type] ? 'granted' : 'denied';
        });

        gtag('consent', 'update', consentUpdate);
    }

    loadTrackingScripts() {
        console.log('Loading tracking scripts after consent...');
        
        // Load Google Analytics if analytics consent is given
        if (this.currentConsent.analytics_storage && !window._gaLoaded) {
            const gaScript = document.createElement('script');
            gaScript.async = true;
            gaScript.src = 'https://www.googletagmanager.com/gtag/js?id=G-TP4GJ51GSL';
            document.head.appendChild(gaScript);
            
            gaScript.onload = () => {
                gtag('config', 'G-TP4GJ51GSL');
                console.log('Google Analytics loaded and configured');
            };
            
            window._gaLoaded = true;
        }
        
        // Load Facebook Pixel if ad consent is given
        if (this.currentConsent.ad_storage && !window._fbLoaded) {
            const fbScript = document.createElement('script');
            fbScript.async = true;
            fbScript.src = 'https://connect.facebook.net/en_US/fbevents.js';
            document.head.appendChild(fbScript);
            
            fbScript.onload = () => {
                fbq('init', '1316635815226956');
                fbq('track', 'PageView');
                console.log('Facebook Pixel loaded and configured');
            };
            
            window._fbLoaded = true;
        }
        
        // Load Yandex Metrica if analytics consent is given
        if (this.currentConsent.analytics_storage && !window._ymLoaded) {
            const ymScript = document.createElement('script');
            ymScript.type = 'text/javascript';
            ymScript.innerHTML = `
                (function(m,e,t,r,i,k,a){m[i]=m[i]||function(){(m[i].a=m[i].a||[]).push(arguments)};
                m[i].l=1*new Date();k=e.createElement(t),a=e.getElementsByTagName(t)[0],k.async=1,k.src=r,a.parentNode.insertBefore(k,a)})
                (window, document, "script", "https://mc.yandex.ru/metrika/tag.js", "ym");
                ym(87984800, "init", {
                    clickmap:true,
                    trackLinks:true,
                    accurateTrackBounce:true,
                    webvisor:true
                });
            `;
            document.head.appendChild(ymScript);
            console.log('Yandex Metrica loaded and configured');
            window._ymLoaded = true;
        }
    }

    acceptAll() {
        console.log('acceptAll() called');
        // Set all consent types to granted
        Object.keys(this.consentTypes).forEach(type => {
            this.currentConsent[type] = true;
        });
        
        this.saveConsent(this.currentConsent);
        this.updateGoogleConsent();
        this.loadTrackingScripts();
        this.hideConsentInterface();
        
        // Trigger consent accepted event
        this.triggerConsentEvent('accepted', this.currentConsent);
    }

    acceptEssential() {
        const preferences = {};
        Object.keys(this.consentTypes).forEach(type => {
            preferences[type] = this.consentTypes[type].required;
        });
        this.saveConsent(preferences);
        this.hideConsentInterface();
        this.dispatchConsentEvent('consentAccepted', preferences);
    }

    acceptCustom(preferences) {
        // Ensure required consents are always granted
        Object.keys(this.consentTypes).forEach(type => {
            if (this.consentTypes[type].required) {
                preferences[type] = true;
            }
        });
        this.saveConsent(preferences);
        this.hideConsentInterface();
        this.dispatchConsentEvent('consentAccepted', preferences);
    }

    showBanner() {
        console.log('showBanner() called');
        this.createBannerHTML();
        const banner = document.getElementById('consent-banner');
        console.log('Banner element:', banner);
        if (banner) {
            setTimeout(() => {
                banner.classList.add('show');
                console.log('Banner should be visible now');
            }, 100);
        } else {
            console.error('Banner element not found after creation');
        }
    }


    showModal() {
        console.log('showModal() called');
        
        let overlay = document.getElementById('consent-overlay');
        console.log('Modal overlay found:', overlay);
        
        if (!overlay) {
            console.log('Modal not found, creating it...');
            this.createModalHTML();
            overlay = document.getElementById('consent-overlay');
        }
        
        if (overlay) {
            console.log('Showing modal');
            
            document.body.style.overflow = 'hidden';
            document.documentElement.style.overflow = 'hidden';
            document.body.style.position = 'fixed';
            document.body.style.width = '100%';
            
            overlay.style.display = 'flex';
            setTimeout(() => {
                overlay.classList.add('show');
                console.log('Modal classes after show:', overlay.className);
            }, 100);
            
            // Add click outside to close functionality
            overlay.addEventListener('click', (e) => {
                if (e.target === overlay) {
                    this.hideModal();
                }
            });
            
            // Update toggle states based on current consent
            this.updateToggleStates();
            this.trapFocus(overlay);
        } else {
            console.error('Modal overlay still not found after creation attempt');
        }
    }

    hideModal() {
        const overlay = document.getElementById('consent-overlay');
        if (overlay) {
            overlay.classList.remove('show');
            setTimeout(() => {
                overlay.style.display = 'none';
                // Restore body scrolling
                document.body.style.overflow = '';
                document.documentElement.style.overflow = '';
                document.body.style.position = '';
                document.body.style.width = '';
            }, 400);
        }
    }

    updateToggleStates() {
        console.log('Updating toggle states with current consent:', this.currentConsent);
        
        Object.keys(this.consentTypes).forEach(type => {
            const toggle = document.querySelector(`[data-consent="${type}"]`);
            if (toggle) {
                const shouldBeActive = this.consentTypes[type].required || this.currentConsent[type];
                console.log(`Toggle ${type}: shouldBeActive=${shouldBeActive}, required=${this.consentTypes[type].required}`);
                
                if (shouldBeActive) {
                    toggle.classList.add('active');
                } else {
                    toggle.classList.remove('active');
                }
            }
        });
    }

    hideConsentInterface() {
        const banner = document.getElementById('consent-banner');
        const overlay = document.getElementById('consent-overlay');
        
        if (banner) {
            banner.classList.remove('show');
            setTimeout(() => {
                if (banner.parentNode) {
                    banner.remove();
                }
            }, 300);
        }
        
        if (overlay) {
            overlay.classList.remove('show');
            setTimeout(() => {
                if (overlay.parentNode) {
                    overlay.remove();
                }
            }, 300);
        }
    }

    createBannerHTML() {
        // Remove any existing banner first
        const existingBanner = document.getElementById('consent-banner');
        if (existingBanner) {
            existingBanner.remove();
        }

        const banner = document.createElement('div');
        banner.id = 'consent-banner';
        banner.className = 'consent-banner';
        const lang = document.documentElement.lang || 'ro';
        const texts = this.getLanguageTexts(lang);
        
        banner.innerHTML = `
            <div class="consent-banner-content">
                <div class="consent-banner-text">
                    ${texts.banner_text} 
                    <a href="/${lang}/privacy" target="_blank">${texts.privacy_link}</a>
                </div>
                <div class="consent-banner-actions">
                    <button class="consent-btn consent-btn-secondary" data-consent-action="accept-essential">
                        ${texts.accept_essential}
                    </button>
                    <button class="consent-btn consent-btn-secondary" data-consent-action="customize">
                        ${texts.customize}
                    </button>
                    <button class="consent-btn consent-btn-primary" data-consent-action="accept-all">
                        ${texts.accept_all}
                    </button>
                </div>
            </div>
        `;
        
        document.body.appendChild(banner);
    }

    createModalHTML() {
        if (document.getElementById('consent-overlay')) return;

        const overlay = document.createElement('div');
        overlay.id = 'consent-overlay';
        overlay.className = 'consent-overlay';
        overlay.style.display = 'none';
        const lang = document.documentElement.lang || 'ro';
        const texts = this.getLanguageTexts(lang);
        
        overlay.innerHTML = `
            <div class="consent-modal">
                <div class="consent-header">
                    <h2 id="consent-title" class="consent-title">${texts.title}</h2>
                    <p class="consent-subtitle">${texts.subtitle}</p>
                </div>
                
                <div class="consent-body">
                    <p id="consent-description" class="consent-description">
                        ${texts.description} 
                        <a href="/${lang}/privacy" target="_blank">${texts.privacy_link}</a>
                    </p>
                    
                    <div class="consent-options">
                        ${this.generateConsentOptions(lang)}
                    </div>
                </div>
                
                <div class="consent-actions">
                    <button class="consent-btn consent-btn-secondary" data-consent-action="accept-custom">
                        ${texts.save_preferences}
                    </button>
                </div>
                
                <div class="consent-footer">
                    <p class="consent-footer-text">
                        ${texts.footer_text} <a href="/${lang}/privacy">${texts.footer_link}</a>
                    </p>
                </div>
            </div>
        `;
        
        document.body.appendChild(overlay);
        
        // Close on overlay click
        overlay.addEventListener('click', (e) => {
            if (e.target === overlay) {
                this.hideConsentInterface();
            }
        });
    }

    getLanguageTexts(lang) {
        return this.languageTexts[lang] || this.languageTexts['ro'];
    }

    generateConsentOptions(lang = 'ro') {
        let html = '';
        Object.keys(this.consentTypes).forEach(type => {
            const config = this.consentTypes[type];
            const isActive = config.required || this.currentConsent[type];
            const isDisabled = config.required ? 'disabled' : '';
            
            html += `
                <div class="consent-option" data-consent-toggle="${type}">
                    <div class="consent-option-header">
                        <h3 class="consent-option-title">${this.getConsentTitle(type)}</h3>
                        <div class="consent-toggle ${isActive ? 'active' : ''} ${isDisabled}" data-consent="${type}">
                            <div class="consent-toggle-slider"></div>
                        </div>
                    </div>
                    <p class="consent-option-description">${this.getConsentDescription(type)}</p>
                </div>
            `;
        });
        return html;
    }

    getConsentTitle(type) {
        const titles = {
            functionality_storage: 'Cookie-uri funcționale',
            security_storage: 'Cookie-uri de securitate',
            ad_storage: 'Cookie-uri publicitare',
            ad_user_data: 'Date utilizator pentru publicitate',
            ad_personalization: 'Personalizare publicitate',
            analytics_storage: 'Cookie-uri analitice',
            personalization_storage: 'Cookie-uri personalizare'
        };
        return titles[type] || type;
    }

    getConsentDescription(type) {
        const descriptions = {
            functionality_storage: 'Necesare pentru funcționarea de bază a site-ului',
            security_storage: 'Protejează site-ul împotriva atacurilor',
            ad_storage: 'Permit afișarea de publicitate relevantă',
            ad_user_data: 'Colectează date pentru optimizarea publicitații',
            ad_personalization: 'Personalizează publicitatea în funcție de interese',
            analytics_storage: 'Ajută la înțelegerea modului de utilizare a site-ului',
            personalization_storage: 'Personalizează experiența utilizatorului'
        };
        return descriptions[type] || 'Descriere indisponibilă';
    }

    toggleOption(consentType) {
        console.log('toggleOption called for:', consentType);
        console.log('Is required:', this.consentTypes[consentType]?.required);
        console.log('Current consent state before toggle:', this.currentConsent);
        
        if (this.consentTypes[consentType]?.required) {
            console.log('Cannot toggle required consent type');
            return;
        }
        
        const toggle = document.querySelector(`[data-consent="${consentType}"]`);
        console.log('Toggle element found:', toggle);
        if (toggle) {
            const wasActive = toggle.classList.contains('active');
            console.log('Toggle was active before click:', wasActive);
            
            toggle.classList.toggle('active');
            const isActiveNow = toggle.classList.contains('active');
            
            // Update internal state
            this.currentConsent[consentType] = isActiveNow;
            console.log('Toggle is active after click:', isActiveNow);
            console.log('Toggle classes after click:', toggle.className);
            console.log('Updated consent state:', this.currentConsent);
        } else {
            console.error('Toggle element not found for:', consentType);
        }
    }

    saveCustomPreferences() {
        const preferences = {};
        
        Object.keys(this.consentTypes).forEach(type => {
            const toggle = document.querySelector(`[data-consent="${type}"]`);
            preferences[type] = toggle ? toggle.classList.contains('active') : this.consentTypes[type].required;
        });
        
        this.acceptCustom(preferences);
    }

    setupEventListeners() {
        // Handle escape key
        document.addEventListener('keydown', (e) => {
            if (e.key === 'Escape' && document.getElementById('consent-overlay')) {
                this.hideConsentInterface();
            }
        });

        // Set up banner button event listeners using event delegation
        document.addEventListener('click', (e) => {
            // Only log clicks within consent banner
            if (e.target.closest('#consent-banner') || e.target.closest('#consent-overlay')) {
                console.log('Consent click detected on:', e.target, 'Text:', e.target.textContent, 'Classes:', e.target.className);
            }
            
            // Remove all text-based detection - use only data attributes

            // Data attribute buttons
            if (e.target && e.target.matches('[data-consent-action="customize"]')) {
                console.log('Customize button clicked via data attribute');
                e.preventDefault();
                e.stopPropagation();
                this.showModal();
                return;
            }

            if (e.target && e.target.matches('[data-consent-action="accept-all"]')) {
                console.log('Accept all button clicked via data attribute');
                e.preventDefault();
                e.stopPropagation();
                this.acceptAll();
                return;
            }
            
            if (e.target && e.target.matches('[data-consent-action="accept-essential"]')) {
                console.log('Accept essential button clicked via data attribute');
                e.preventDefault();
                e.stopPropagation();
                this.acceptEssential();
                return;
            }
            
            if (e.target && e.target.matches('[data-consent-action="accept-custom"]')) {
                console.log('Accept custom button clicked via data attribute');
                e.preventDefault();
                e.stopPropagation();
                this.saveCustomPreferences();
                return;
            }

            // Toggle consent options - respond to clicks on toggle or entire option
            if (e.target && (e.target.matches('[data-consent]') || e.target.closest('[data-consent-toggle]'))) {
                let consentType;
                if (e.target.matches('[data-consent]')) {
                    consentType = e.target.getAttribute('data-consent');
                } else {
                    consentType = e.target.closest('[data-consent-toggle]').getAttribute('data-consent-toggle');
                }
                console.log('Toggle consent option:', consentType);
                e.preventDefault();
                e.stopPropagation();
                this.toggleOption(consentType);
                return;
            }
        });
    }

    trapFocus(element) {
        const focusableElements = element.querySelectorAll(
            'button, [href], input, select, textarea, [tabindex]:not([tabindex="-1"])'
        );
        
        const firstElement = focusableElements[0];
        const lastElement = focusableElements[focusableElements.length - 1];
        
        element.addEventListener('keydown', (e) => {
            if (e.key === 'Tab') {
                if (e.shiftKey) {
                    if (document.activeElement === firstElement) {
                        lastElement.focus();
                        e.preventDefault();
                    }
                } else {
                    if (document.activeElement === lastElement) {
                        firstElement.focus();
                        e.preventDefault();
                    }
                }
            }
        });
        
        firstElement?.focus();
    }

    dispatchConsentEvent(eventName, data) {
        const event = new CustomEvent(eventName, {
            detail: { preferences: data, manager: this }
        });
        document.dispatchEvent(event);
    }

    // Public API methods
    getConsent(type) {
        return this.currentConsent[type] || false;
    }

    hasConsent() {
        return Object.keys(this.currentConsent).length > 0;
    }

    revokeConsent() {
        localStorage.removeItem(this.options.storageKey);
        this.currentConsent = {};
        this.initGoogleConsentMode();
        this.loadConsent();
    }

    updateConsent(preferences) {
        this.acceptCustom(preferences);
    }
}

// Initialize consent manager
let consentManager;

// Initialize when DOM is ready
document.addEventListener('DOMContentLoaded', () => {
    console.log('DOM loaded, initializing ConsentManager...');
    
    // Clean up old consent data first
    ['z_cks_alwd', 'z_cks_alwd_t', 'z_cks_alwd_v'].forEach(key => {
        if (localStorage.getItem(key) !== null) {
            localStorage.removeItem(key);
        }
    });
    
    // Check if already initialized to prevent duplicates
    if (typeof window.consentManager === 'undefined' || !window.consentManager) {
        window.consentManager = new ConsentManager({
            showBanner: true,
            showModal: false,
            autoShow: true
        });
        console.log('ConsentManager initialized on main site:', window.consentManager);
    } else {
        console.log('ConsentManager already exists, skipping initialization');
    }
});

// Global functions for backward compatibility
function setConsent(ad, userData, personalization, analytics) {
    if (window.consentManager) {
        window.consentManager.acceptCustom({
            functionality_storage: true,
            security_storage: true,
            ad_storage: ad,
            ad_user_data: userData,
            ad_personalization: personalization,
            analytics_storage: analytics,
            personalization_storage: analytics
        });
    }
}

function showConsentModal() {
    if (window.consentManager) {
        window.consentManager.showModal();
    }
}

function acceptAllConsent() {
    if (window.consentManager) {
        window.consentManager.acceptAll();
    }
}
