<?php
/**
 * Debug script to check consent system loading on live site
 */
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>SAUTO Consent System - Cookie Test</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 20px; background: #f5f5f5; }
        .debug-box { background: white; padding: 20px; margin: 10px 0; border-radius: 8px; box-shadow: 0 2px 4px rgba(0,0,0,0.1); }
        .debug-box h3 { margin-top: 0; color: #333; }
        button { padding: 10px 15px; margin: 5px; background: #e2001a; color: white; border: none; border-radius: 4px; cursor: pointer; }
        button:hover { background: #b8001a; }
        .status { padding: 10px; margin: 10px 0; border-radius: 4px; }
        .success { background: #d4edda; color: #155724; border: 1px solid #c3e6cb; }
        .error { background: #f8d7da; color: #721c24; border: 1px solid #f5c6cb; }
        .info { background: #d1ecf1; color: #0c5460; border: 1px solid #bee5eb; }
        pre { background: #f8f9fa; padding: 10px; border-radius: 4px; overflow-x: auto; font-size: 12px; }
        a { color: #e2001a; text-decoration: none; }
        a:hover { text-decoration: underline; }
        .consent-state { background: #f8f9fa; padding: 15px; border-radius: 4px; margin: 10px 0; }
        .toggle-test { background: #fff3cd; padding: 10px; border-radius: 4px; margin: 5px 0; }
    </style>
</head>
<body>
    <h1>🍪 SAUTO Consent System - Cookie Functionality Test</h1>
    
    <div class="debug-box">
        <h3>Cookie Tests:</h3>
        <button onclick="showConsentModal()">Open Consent Modal</button>
        <button onclick="showCurrentConsentState()">Show Current Consent State</button>
        <button onclick="testToggleFunctionality()">Test Toggle Functionality</button>
        <button onclick="clearAllConsent()">Clear All Consent & Reload</button>
        <button onclick="testGoogleIntegration()">🧪 Test Google Integration</button>
    </div>

    <div class="debug-box">
        <h3>Live Site Tests:</h3>
        <p><a href="https://www.testline8392.sauto.md/" target="_blank">Test Main Page</a></p>
        <p><strong>Instructions:</strong> Test consent toggles on main site to verify they work correctly</p>
    </div>

    <div id="consent-state-display" class="debug-box" style="display: none;">
        <h3>Current Consent State:</h3>
        <div id="consent-details"></div>
    </div>

    <!-- Include consent interface like main site -->
    <?php include(__DIR__ . '/content/site/include/consent-interface.php'); ?>
    
    <!-- Load consent system -->
    <link rel="stylesheet" href="/content/site/css/consent-modal.css">
    <script src="/content/site/js/consent-manager.js" onload="console.log('consent-manager.js loaded successfully')" onerror="console.error('Failed to load consent-manager.js')"></script>
    <script>
        // Initialize ConsentManager for testing
        document.addEventListener('DOMContentLoaded', function() {
            if (typeof ConsentManager !== 'undefined') {
                window.consentManager = new ConsentManager({
                    autoShow: false,
                    showBanner: false,
                    showModal: false
                });
                console.log('ConsentManager initialized for testing');
            }
        });

        // Cookie functionality test functions
        function clearAllConsent() {
            if (window.consentManager) {
                window.consentManager.revokeConsent();
                alert('All consent cleared! Reloading page...');
                setTimeout(() => location.reload(), 1000);
            } else {
                alert('ConsentManager not available');
            }
        }

        function testGoogleIntegration() {
            console.log('=== GOOGLE INTEGRATION TEST ===');
            
            let results = [];
            
            // Test 1: Check if gtag exists
            if (typeof gtag !== 'undefined') {
                results.push('✅ gtag function exists');
            } else {
                results.push('❌ gtag function not found');
            }
            
            // Test 2: Check dataLayer
            if (typeof dataLayer !== 'undefined' && Array.isArray(dataLayer)) {
                results.push(`✅ dataLayer exists with ${dataLayer.length} items`);
                
                // Show recent dataLayer entries
                const recentEntries = dataLayer.slice(-5);
                results.push('📊 Recent dataLayer entries:');
                recentEntries.forEach((entry, index) => {
                    results.push(`  ${index + 1}. ${JSON.stringify(entry)}`);
                });
            } else {
                results.push('❌ dataLayer not found or invalid');
            }
            
            // Test 3: Test consent update
            if (typeof gtag !== 'undefined') {
                results.push('🧪 Testing consent update...');
                
                // Test analytics consent
                gtag('consent', 'update', {
                    'analytics_storage': 'granted'
                });
                results.push('✅ Sent analytics_storage: granted to Google');
                
                // Test ad consent
                gtag('consent', 'update', {
                    'ad_storage': 'denied',
                    'ad_user_data': 'denied',
                    'ad_personalization': 'denied'
                });
                results.push('✅ Sent ad consents: denied to Google');
            }
            
            // Test 4: Check current consent state
            if (window.consentManager) {
                const currentState = window.consentManager.currentConsent;
                results.push('📋 Current consent state:');
                Object.keys(currentState).forEach(key => {
                    const status = currentState[key] ? 'GRANTED' : 'DENIED';
                    results.push(`  ${key}: ${status}`);
                });
            }
            
            // Test 5: Network requests check
            results.push('🌐 Check Network tab for:');
            results.push('  - google-analytics.com requests');
            results.push('  - googletagmanager.com requests');
            results.push('  - Look for gcs parameter (Google Consent State)');
            
            // Display results
            const resultText = results.join('\n');
            console.log(resultText);
            
            // Display results in copyable format on page
            const testDiv = document.getElementById('google-test-results') || document.createElement('div');
            testDiv.id = 'google-test-results';
            testDiv.className = 'debug-box';
            testDiv.innerHTML = `
                <h3>🧪 Google Integration Test Results:</h3>
                <textarea readonly style="width: 100%; height: 300px; font-family: monospace; font-size: 12px; background: #f5f5f5; border: 1px solid #ddd; padding: 10px;">${resultText}</textarea>
                <button onclick="copyTestResults()" style="margin-top: 10px; padding: 8px 16px; background: #007cba; color: white; border: none; border-radius: 4px; cursor: pointer;">📋 Copy Results</button>
            `;
            
            const existingDiv = document.getElementById('google-test-results');
            if (!existingDiv) {
                document.body.appendChild(testDiv);
            }
            
            // Scroll to results
            testDiv.scrollIntoView({ behavior: 'smooth' });
        }

        function copyTestResults() {
            const textarea = document.querySelector('#google-test-results textarea');
            if (textarea) {
                textarea.select();
                textarea.setSelectionRange(0, 99999); // For mobile devices
                navigator.clipboard.writeText(textarea.value).then(() => {
                    alert('✅ Results copied to clipboard!');
                }).catch(() => {
                    // Fallback for older browsers
                    document.execCommand('copy');
                    alert('✅ Results copied to clipboard!');
                });
            }
        }

        function showConsentModal() {
            if (window.consentManager) {
                window.consentManager.showModal();
            } else {
                alert('ConsentManager not available');
            }
        }

        function showCurrentConsentState() {
            if (!window.consentManager) {
                alert('ConsentManager not available');
                return;
            }

            const stateDisplay = document.getElementById('consent-state-display');
            const detailsDiv = document.getElementById('consent-details');
            
            const currentState = window.consentManager.currentConsent;
            const consentTypes = window.consentManager.consentTypes;
            
            let html = '<div class="consent-state">';
            html += '<h4>Stored Consent Preferences:</h4>';
            html += '<pre>' + JSON.stringify(currentState, null, 2) + '</pre>';
            
            // Technical types (not shown to users)
            html += '<h4>🔧 Tipuri Tehnice (Interne):</h4>';
            const technicalTypes = ['functionality_storage', 'security_storage'];
            technicalTypes.forEach(type => {
                if (consentTypes[type]) {
                    const config = consentTypes[type];
                    const isActive = currentState[type];
                    const technicalNames = {
                        functionality_storage: 'Cookie-uri funcționale (tehnic)',
                        security_storage: 'Cookie-uri de securitate (tehnic)'
                    };
                    html += `<div class="toggle-test" style="background: #f0f0f0;">
                        <strong>${technicalNames[type]}</strong>: ${isActive ? '✅ ACTIV' : '❌ INACTIV'} (Obligatoriu)
                        <br><small>Tip tehnic: ${type}</small>
                    </div>`;
                }
            });
            
            // User-facing types (shown in modal)
            html += '<h4>👤 Tipuri Vizibile Utilizatorului (ca în Modal):</h4>';
            const userTypes = ['analytics_storage', 'ad_storage', 'ad_personalization', 'personalization_storage'];
            const bannerNames = {
                analytics_storage: 'Cookie-uri de analiză',
                ad_storage: 'Cookie-uri publicitare', 
                ad_personalization: 'Personalizare reclame',
                personalization_storage: 'Cookie-uri de personalizare'
            };
            
            userTypes.forEach(type => {
                if (consentTypes[type]) {
                    const config = consentTypes[type];
                    const isActive = currentState[type];
                    const displayName = bannerNames[type] || type;
                    html += `<div class="toggle-test">
                        <strong>${displayName}</strong>: ${isActive ? '✅ ACTIV' : '❌ INACTIV'} (Opțional)
                        <br><small>Tip tehnic: ${type}</small>
                    </div>`;
                }
            });
            
            // Technical type not shown to users but tracked internally
            html += '<h4>🔍 Tip Tehnic Suplimentar (Nu e în Modal):</h4>';
            if (consentTypes['ad_user_data']) {
                const config = consentTypes['ad_user_data'];
                const isActive = currentState['ad_user_data'];
                html += `<div class="toggle-test" style="background: #fff3cd;">
                    <strong>Date utilizator pentru publicitate</strong>: ${isActive ? '✅ ACTIV' : '❌ INACTIV'} (Opțional)
                    <br><small>Tip tehnic: ad_user_data - Nu apare în modal, doar pentru Google Consent Mode</small>
                </div>`;
            }
            
            html += '</div>';
            detailsDiv.innerHTML = html;
            stateDisplay.style.display = 'block';
        }

        function testToggleFunctionality() {
            if (!window.consentManager) {
                alert('ConsentManager not available');
                return;
            }

            // Open modal first
            window.consentManager.showModal();
            
            setTimeout(() => {
                const modal = document.getElementById('consent-overlay');
                if (!modal) {
                    alert('Modal not found');
                    return;
                }

                const toggles = modal.querySelectorAll('[data-consent]');
                console.log('=== TESTING TOGGLE FUNCTIONALITY ===');
                console.log('Found toggles:', toggles.length);
                
                toggles.forEach((toggle, index) => {
                    const consentType = toggle.dataset.consent;
                    const isRequired = window.consentManager.consentTypes[consentType]?.required;
                    
                    console.log(`Toggle ${index + 1}: ${consentType}`);
                    console.log('  Required:', isRequired);
                    console.log('  Current state:', toggle.classList.contains('active'));
                    
                    if (!isRequired) {
                        console.log('  Testing click...');
                        const beforeState = toggle.classList.contains('active');
                        toggle.click();
                        
                        setTimeout(() => {
                            const afterState = toggle.classList.contains('active');
                            console.log(`  Before: ${beforeState}, After: ${afterState}, Changed: ${beforeState !== afterState}`);
                        }, 100);
                    }
                });
                
                alert('Toggle test running - check console for results');
            }, 300);
        }

        function clearAllConsent() {
            localStorage.removeItem('sauto_consent_preferences');
            location.reload();
        }
    </script>
</body>
</html>
