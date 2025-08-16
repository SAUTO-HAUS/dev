/**
 * Modern Consent Manager with Google Consent Mode v2
 * Compliant with GDPR, CCPA, and other privacy regulations
 */
class ConsentManager {
    constructor(options = {}) {
        this.options = {
            storageKey: 'sauto_consent_preferences',
            storageVersion: '2.1',
            expiryDays: 365,
            showBanner: true,
            showModal: false,
            autoShow: true,
            ...options
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

        this.currentConsent = {};
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
            this.currentConsent = stored.preferences;
            this.updateGoogleConsent();
            console.log('Valid consent found, not showing interface');
            
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
        const consentUpdate = {};
        
        Object.keys(this.consentTypes).forEach(type => {
            consentUpdate[type] = this.currentConsent[type] ? 'granted' : 'denied';
        });

        gtag('consent', 'update', consentUpdate);
    }

    acceptAll() {
        console.log('acceptAll() called');
        const preferences = {};
        Object.keys(this.consentTypes).forEach(type => {
            preferences[type] = true;
        });
        console.log('Saving preferences:', preferences);
        this.saveConsent(preferences);
        this.hideConsentInterface();
        this.dispatchConsentEvent('consentAccepted', preferences);
        console.log('All cookies accepted and saved');
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
        this.createModalHTML();
        const overlay = document.getElementById('consent-overlay');
        if (overlay) {
            setTimeout(() => overlay.classList.add('show'), 100);
            this.trapFocus(overlay);
        }
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
        banner.innerHTML = `
            <div class="consent-banner-content">
                <div class="consent-banner-text">
                    Folosim cookie-uri pentru a îmbunătăți experiența ta pe site. 
                    <a href="/${document.documentElement.lang || 'ro'}/privacy" target="_blank">Politica de confidențialitate</a>
                </div>
                <div class="consent-banner-actions">
                    <button class="consent-btn consent-btn-outline" onclick="consentManager.acceptEssential()">
                        Doar esențiale
                    </button>
                    <button class="consent-btn consent-btn-secondary" onclick="consentManager.showModal()">
                        Personalizează
                    </button>
                    <button class="consent-btn consent-btn-primary" onclick="consentManager.acceptAll()">
                        Accept toate
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
        overlay.innerHTML = `
            <div class="consent-modal" role="dialog" aria-labelledby="consent-title" aria-describedby="consent-description">
                <div class="consent-header">
                    <h2 id="consent-title" class="consent-title">Setări Cookie-uri</h2>
                    <p class="consent-subtitle">Controlează cum sunt utilizate datele tale</p>
                </div>
                
                <div class="consent-body">
                    <p id="consent-description" class="consent-description">
                        Respectăm confidențialitatea ta. Alege ce tipuri de cookie-uri să accepti. 
                        <a href="/${document.documentElement.lang || 'ro'}/privacy" target="_blank">Citește politica de confidențialitate</a>
                    </p>
                    
                    <div class="consent-options">
                        ${this.generateConsentOptions()}
                    </div>
                </div>
                
                <div class="consent-actions">
                    <button class="consent-btn consent-btn-outline" onclick="consentManager.acceptEssential()">
                        Doar esențiale
                    </button>
                    <button class="consent-btn consent-btn-secondary" onclick="consentManager.saveCustomPreferences()">
                        Salvează preferințele
                    </button>
                    <button class="consent-btn consent-btn-primary" onclick="consentManager.acceptAll()">
                        Accept toate
                    </button>
                </div>
                
                <div class="consent-footer">
                    <p class="consent-footer-text">
                        Poți modifica aceste setări oricând din <a href="/${document.documentElement.lang || 'ro'}/privacy">pagina de confidențialitate</a>
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

    generateConsentOptions() {
        const options = {
            functionality_storage: {
                title: 'Cookie-uri funcționale',
                description: 'Necesare pentru funcționarea de bază a site-ului (autentificare, preferințe limba)',
                required: true
            },
            analytics_storage: {
                title: 'Cookie-uri de analiză',
                description: 'Ne ajută să înțelegem cum folosești site-ul pentru a-l îmbunătăți',
                required: false
            },
            ad_storage: {
                title: 'Cookie-uri publicitare',
                description: 'Utilizate pentru afișarea de reclame relevante',
                required: false
            },
            ad_personalization: {
                title: 'Personalizare reclame',
                description: 'Personalizează reclamele în funcție de interesele tale',
                required: false
            },
            personalization_storage: {
                title: 'Cookie-uri de personalizare',
                description: 'Salvează preferințele tale pentru o experiență personalizată',
                required: false
            }
        };

        return Object.keys(options).map(key => {
            const option = options[key];
            const isChecked = this.currentConsent[key] || option.required;
            
            return `
                <div class="consent-option ${option.required ? 'required' : ''}" onclick="consentManager.toggleOption('${key}')">
                    <div class="consent-toggle ${isChecked ? 'active' : ''}" data-consent="${key}"></div>
                    <div class="consent-option-content">
                        <h3 class="consent-option-title">
                            ${option.title}
                            ${option.required ? '<span class="consent-required-badge">Obligatoriu</span>' : ''}
                        </h3>
                        <p class="consent-option-description">${option.description}</p>
                    </div>
                </div>
            `;
        }).join('');
    }

    toggleOption(consentType) {
        if (this.consentTypes[consentType]?.required) return;
        
        const toggle = document.querySelector(`[data-consent="${consentType}"]`);
        if (toggle) {
            toggle.classList.toggle('active');
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
            
            // Accept all button - check multiple variations
            if (e.target && (
                e.target.matches('.consent-btn-primary') || 
                e.target.textContent?.includes('Accept') || 
                e.target.textContent?.includes('Принять') ||
                e.target.textContent?.includes('toate')
            )) {
                console.log('Accept all button clicked');
                e.preventDefault();
                e.stopPropagation();
                this.acceptAll();
                return;
            }
            
            // Personalize button - check multiple variations and parent elements
            if (e.target && (
                e.target.matches('.consent-btn-secondary') || 
                e.target.textContent?.includes('Personalizează') || 
                e.target.textContent?.includes('Настроить') ||
                e.target.textContent?.includes('Personalizeaz') ||
                e.target.closest('.consent-btn-secondary')
            )) {
                console.log('Personalize button clicked');
                e.preventDefault();
                e.stopPropagation();
                this.showModal();
                return;
            }
            
            // Essential only button
            if (e.target && (
                e.target.matches('.consent-btn-outline') || 
                e.target.textContent?.includes('esențiale') || 
                e.target.textContent?.includes('необходимые') ||
                e.target.textContent?.includes('Doar')
            )) {
                console.log('Essential only button clicked');
                e.preventDefault();
                e.stopPropagation();
                this.acceptEssential();
                return;
            }

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

            // Toggle consent options
            if (e.target && e.target.closest('[data-consent-toggle]')) {
                const toggleElement = e.target.closest('[data-consent-toggle]');
                const consentType = toggleElement.getAttribute('data-consent-toggle');
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
    if (typeof consentManager === 'undefined' || !consentManager) {
        consentManager = new ConsentManager({
            showBanner: true,
            showModal: false,
            autoShow: true
        });
    } else {
        console.log('ConsentManager already exists, skipping initialization');
    }
});

// Global functions for backward compatibility
function setConsent(ad, userData, personalization, analytics) {
    if (consentManager) {
        consentManager.acceptCustom({
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
    if (consentManager) {
        consentManager.showModal();
    }
}

function acceptAllConsent() {
    if (consentManager) {
        consentManager.acceptAll();
    }
}
