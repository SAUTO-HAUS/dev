(function() {
    'use strict';

    const CONFIG = {
        debounceMs: 250,
        apiEndpoint: '/content/site/ajax/get_facets.php',
        enableCounts: false
    };

    let debounceTimer = null;
    let currentRequest = null;
    let lastFacetData = null;
    let isInitialized = false;
    
    // Store original options for Safari compatibility (Safari doesn't support display:none on options)
    const originalOptions = new Map();

    function init() {
        if (!document.getElementById('fltr')) {
            return;
        }

        if (isInitialized) {
            return;
        }
        isInitialized = true;

        bindFilterEvents();

        loadFacets();
    }

    function bindFilterEvents() {
        const fltr = document.getElementById('fltr');
        if (!fltr) return;

        fltr.querySelectorAll('select.srch').forEach(select => {
            select.addEventListener('change', handleFilterChange);
        });

        fltr.querySelectorAll('input.srch').forEach(input => {
            input.addEventListener('change', handleFilterChange);
            input.addEventListener('input', debounce(handleFilterChange, CONFIG.debounceMs));
        });

        fltr.querySelectorAll('.fr, .to').forEach(el => {
            el.addEventListener('blur', handleRangeValidation);
        });
    }

    function handleFilterChange(e) {
        // Debounce the facet request
        if (debounceTimer) {
            clearTimeout(debounceTimer);
        }

        debounceTimer = setTimeout(() => {
            loadFacets();
        }, CONFIG.debounceMs);
    }

    function handleRangeValidation(e) {
        const el = e.target;
        const parent = el.closest('.data');
        if (!parent) return;

        const fromEl = parent.querySelector('.fr');
        const toEl = parent.querySelector('.to');

        if (!fromEl || !toEl) return;

        const fromVal = parseNumericValue(fromEl.value);
        const toVal = parseNumericValue(toEl.value);

        if (fromVal !== null && toVal !== null && fromVal > toVal) {
            const temp = fromEl.value;
            fromEl.value = toEl.value;
            toEl.value = temp;
            fromEl.dispatchEvent(new Event('change', { bubbles: true }));
        }
    }

    function parseNumericValue(val) {
        if (val === '' || val === null || val === undefined || val === 'x') {
            return null;
        }
        const num = parseInt(val.toString().replace(/[^\d]/g, ''), 10);
        return isNaN(num) ? null : num;
    }

    function getCurrentFilters() {
        const fltr = document.getElementById('fltr');
        if (!fltr) return {};

        const filters = {};

        // Get select values
        fltr.querySelectorAll('select.srch').forEach(select => {
            const name = select.name || select.dataset.tg;
            const value = select.value;
            if (name && value && value !== '') {
                filters[name] = value;
            }
        });

        // Get range values
        fltr.querySelectorAll('.data[data-name]').forEach(container => {
            const name = container.dataset.name;
            const fromEl = container.querySelector('.fr');
            const toEl = container.querySelector('.to');

            if (fromEl && toEl) {
                const fromVal = fromEl.value || 'x';
                const toVal = toEl.value || 'x';
                if (fromVal !== 'x' || toVal !== 'x') {
                    filters[name] = fromVal + '-' + toVal;
                }
            }
        });

        // Get radio values
        fltr.querySelectorAll('input.rad:checked').forEach(radio => {
            const name = radio.name;
            const value = radio.value;
            if (name && value) {
                filters[name] = value;
            }
        });

        return filters;
    }

    function loadFacets() {
        // Cancel previous request
        if (currentRequest) {
            currentRequest.abort();
        }

        const filters = getCurrentFilters();
        
        let pageType = 'all';
        let catalogType = 'all';
        if (window.location.pathname.includes('/ordercars')) {
            pageType = 'ordercars';
            catalogType = 'on_order';
        } else if (window.location.pathname.includes('/cars')) {
            pageType = 'cars';
            catalogType = 'in_stock';
        }

        const requestData = {
            ...filters,
            page: pageType,
            catalog_type: catalogType,
            lang: getCookie('lang') || 'ro'
        };

        currentRequest = new XMLHttpRequest();
        currentRequest.open('POST', CONFIG.apiEndpoint, true);
        currentRequest.setRequestHeader('Content-Type', 'application/json');

        currentRequest.onload = function() {
            if (currentRequest.status === 200) {
                try {
                    const response = JSON.parse(currentRequest.responseText);
                    if (response.success && response.data) {
                        lastFacetData = response.data;
                        applyFacets(response.data, filters);
                    }
                } catch (e) {
                    console.error('Error parsing facet response:', e);
                }
            }
            currentRequest = null;
        };

        currentRequest.onerror = function() {
            console.error('Facet request failed');
            currentRequest = null;
        };

        currentRequest.send(JSON.stringify(requestData));
    }

    function applyFacets(data, currentFilters) {
        const fltr = document.getElementById('fltr');
        if (!fltr) return;

        ['bt', 'fl', 'tra', 'wd', 'clr', 'gr'].forEach(filterName => {
            const facetValues = data.facets[filterName] || [];
            const availableValues = new Set(facetValues.map(f => f.value));
            const countMap = {};
            facetValues.forEach(f => { countMap[f.value] = f.count; });

            const select = fltr.querySelector(`select[name="${filterName}"], select.${filterName}`);
            if (!select) return;

            // Store original options on first run
            if (!originalOptions.has(select)) {
                const opts = [];
                select.querySelectorAll('option').forEach(opt => {
                    opts.push({
                        value: opt.value,
                        text: opt.textContent.replace(/\s*\(\d+\)$/, ''),
                        className: opt.className,
                        selected: opt.selected
                    });
                });
                originalOptions.set(select, opts);
                
            }

            const currentValue = select.value;
            const storedOptions = originalOptions.get(select);
            
            // Clear select and rebuild with only available options
            select.innerHTML = '';
            
            let hasCurrentValue = false;
            const addedValues = new Set();
            
            storedOptions.forEach(optData => {
                // Always keep placeholder options
                if (optData.value === '' || optData.className.includes('x')) {
                    const opt = document.createElement('option');
                    opt.value = optData.value;
                    opt.textContent = optData.text;
                    opt.className = optData.className;
                    select.appendChild(opt);
                    addedValues.add(optData.value);
                    return;
                }

                // If API returns empty array, show all options without counts
                if (facetValues.length === 0) {
                    const opt = document.createElement('option');
                    opt.value = optData.value;
                    opt.textContent = optData.text;
                    opt.className = optData.className;
                    select.appendChild(opt);
                    addedValues.add(optData.value);
                    return;
                }

                const isAvailable = availableValues.has(optData.value);
                
                if (isAvailable) {
                    const opt = document.createElement('option');
                    opt.value = optData.value;
                    opt.className = optData.className;
                    
                    if (CONFIG.enableCounts) {
                        const count = countMap[optData.value] || 0;
                        opt.textContent = optData.text + ' (' + count + ')';
                    } else {
                        opt.textContent = optData.text;
                    }
                    
                    select.appendChild(opt);
                    addedValues.add(optData.value);
                    
                    if (optData.value === currentValue) {
                        hasCurrentValue = true;
                    }
                }
            });
            
            // Add any API values that weren't in the original HTML options
            // This handles cases where the database has values not in the initial filter
            if (facetValues.length > 0) {
                const translations = data.translations && data.translations[filterName] ? data.translations[filterName] : {};
                
                facetValues.forEach(facet => {
                    if (!addedValues.has(facet.value)) {
                        const opt = document.createElement('option');
                        opt.value = facet.value;
                        
                        // Use translation if available, otherwise use value
                        const label = translations[facet.value] || facet.value;
                        
                        if (CONFIG.enableCounts) {
                            opt.textContent = label + ' (' + facet.count + ')';
                        } else {
                            opt.textContent = label;
                        }
                        
                        select.appendChild(opt);
                        
                        if (facet.value === currentValue) {
                            hasCurrentValue = true;
                        }
                    }
                });
            }

            // Restore selected value if still available
            if (hasCurrentValue) {
                select.value = currentValue;
            } else if (currentValue && currentValue !== '') {
                select.value = '';
                select.classList.remove('y');
            }
        });

        // Apply brand/model facets
        applyBrandModelFacets(data, currentFilters, fltr);

        // Apply range facets
        applyRangeFacets(data, fltr);

        // Update total count display
        updateTotalCount(data.total);
    }

    function applyBrandModelFacets(data, currentFilters, fltr) {
        const brandFacets = data.facets.br || [];
        const modelsByBrand = data.models_by_brand || {};

        const brandSelect = fltr.querySelector('select.br, select[name="br"]');
        const modelSelect = fltr.querySelector('select.mo, select[name="mo"]');

        if (!brandSelect) return;

        // Apply brand facets
        const availableBrands = new Set(brandFacets.map(f => f.value));
        const brandCountMap = {};
        brandFacets.forEach(f => { brandCountMap[f.value] = f.count; });

        // Store original brand options
        if (!originalOptions.has(brandSelect)) {
            const opts = [];
            brandSelect.querySelectorAll('option').forEach(opt => {
                opts.push({
                    value: opt.value,
                    text: opt.textContent.replace(/\s*\(\d+\)$/, ''),
                    className: opt.className
                });
            });
            originalOptions.set(brandSelect, opts);
        }

        const currentBrand = brandSelect.value;
        const storedBrandOptions = originalOptions.get(brandSelect);
        
        brandSelect.innerHTML = '';
        let hasBrand = false;

        storedBrandOptions.forEach(optData => {
            if (optData.value === '' || optData.className.includes('x')) {
                const opt = document.createElement('option');
                opt.value = optData.value;
                opt.textContent = optData.text;
                opt.className = optData.className;
                brandSelect.appendChild(opt);
                return;
            }

            const normalizedVal = optData.value.replace(/-/g, '_');
            const isAvailable = availableBrands.has(optData.value) || availableBrands.has(normalizedVal);
            
            if (isAvailable) {
                const opt = document.createElement('option');
                opt.value = optData.value;
                opt.className = optData.className;
                
                if (CONFIG.enableCounts) {
                    const count = brandCountMap[optData.value] || brandCountMap[normalizedVal] || 0;
                    opt.textContent = optData.text + ' (' + count + ')';
                } else {
                    opt.textContent = optData.text;
                }
                
                brandSelect.appendChild(opt);

                if (optData.value === currentBrand || normalizedVal === currentBrand) {
                    hasBrand = true;
                }
            }
        });

        if (hasBrand) {
            brandSelect.value = currentBrand;
        } else if (currentBrand) {
            brandSelect.value = '';
            brandSelect.classList.remove('y');
        }

        // Apply model facets if brand is selected
        if (modelSelect && currentBrand) {
            const normalizedBrand = currentBrand.replace(/-/g, '_');
            const brandModels = modelsByBrand[currentBrand] || modelsByBrand[normalizedBrand] || { models: [] };
            const availableModels = new Set(brandModels.models.map(m => m.value));
            const modelCountMap = {};
            brandModels.models.forEach(m => { modelCountMap[m.value] = m.count; });

            // Store original model options
            if (!originalOptions.has(modelSelect)) {
                const opts = [];
                modelSelect.querySelectorAll('option').forEach(opt => {
                    opts.push({
                        value: opt.value,
                        text: opt.textContent.replace(/\s*\(\d+\)$/, ''),
                        className: opt.className
                    });
                });
                originalOptions.set(modelSelect, opts);
            }

            const currentModel = modelSelect.value;
            const storedModelOptions = originalOptions.get(modelSelect);
            
            modelSelect.innerHTML = '';
            let hasModel = false;
            const addedModelValues = new Set();

            // First add placeholder options
            storedModelOptions.forEach(optData => {
                if (optData.value === '' || optData.className.includes('x')) {
                    const opt = document.createElement('option');
                    opt.value = optData.value;
                    opt.textContent = optData.text;
                    opt.className = optData.className;
                    modelSelect.appendChild(opt);
                    addedModelValues.add(optData.value);
                }
            });

            // Then add available models from stored options
            storedModelOptions.forEach(optData => {
                if (optData.value === '' || optData.className.includes('x')) return;

                const normalizedVal = optData.value.replace(/-/g, '_');
                const isAvailable = availableModels.has(optData.value) || availableModels.has(normalizedVal);
                
                if (isAvailable) {
                    const opt = document.createElement('option');
                    opt.value = optData.value;
                    opt.className = optData.className;
                    
                    if (CONFIG.enableCounts) {
                        const count = modelCountMap[optData.value] || modelCountMap[normalizedVal] || 0;
                        opt.textContent = optData.text + ' (' + count + ')';
                    } else {
                        opt.textContent = optData.text;
                    }
                    
                    modelSelect.appendChild(opt);
                    addedModelValues.add(optData.value);
                    addedModelValues.add(normalizedVal);

                    if (optData.value === currentModel || normalizedVal === currentModel) {
                        hasModel = true;
                    }
                }
            });
            
            // Add models from API that weren't in stored options
            brandModels.models.forEach(modelData => {
                const normalizedVal = modelData.value.replace(/-/g, '_');
                const htmlVal = modelData.value.replace(/_/g, '-');
                
                if (!addedModelValues.has(modelData.value) && !addedModelValues.has(normalizedVal) && !addedModelValues.has(htmlVal)) {
                    const opt = document.createElement('option');
                    opt.value = htmlVal; // Use hyphen format for HTML
                    
                    if (CONFIG.enableCounts) {
                        opt.textContent = modelData.name + ' (' + modelData.count + ')';
                    } else {
                        opt.textContent = modelData.name;
                    }
                    
                    modelSelect.appendChild(opt);
                    addedModelValues.add(modelData.value);
                    addedModelValues.add(htmlVal);

                    if (modelData.value === currentModel || htmlVal === currentModel) {
                        hasModel = true;
                    }
                }
            });

            if (hasModel) {
                modelSelect.value = currentModel;
            } else if (currentModel) {
                modelSelect.value = '';
                modelSelect.classList.remove('y');
            }
        }
    }

    function applyRangeFacets(data, fltr) {
        const ranges = data.ranges || {};

        ['yr', 'mlg', 'vol', 'prc', 'sts'].forEach(filterName => {
            const container = fltr.querySelector(`.data[data-name="${filterName}"]`);
            if (!container) return;

            const range = ranges[filterName];

            // For select-based ranges
            const fromSelect = container.querySelector('select.fr');
            const toSelect = container.querySelector('select.to');

            if (fromSelect && toSelect) {
                [fromSelect, toSelect].forEach(select => {
                    // Store original options on first run
                    if (!originalOptions.has(select)) {
                        const opts = [];
                        select.querySelectorAll('option').forEach(opt => {
                            opts.push({
                                value: opt.value,
                                text: opt.textContent,
                                className: opt.className
                            });
                        });
                        originalOptions.set(select, opts);
                    }

                    const currentValue = select.value;
                    const storedOptions = originalOptions.get(select);
                    
                    select.innerHTML = '';
                    
                    storedOptions.forEach(optData => {
                        // Always keep placeholder options
                        if (optData.value === '' || optData.className.includes('x')) {
                            const opt = document.createElement('option');
                            opt.value = optData.value;
                            opt.textContent = optData.text;
                            opt.className = optData.className;
                            select.appendChild(opt);
                            return;
                        }

                        // If no range data, show all options
                        if (!range || range.min === null || range.max === null) {
                            const opt = document.createElement('option');
                            opt.value = optData.value;
                            opt.textContent = optData.text;
                            opt.className = optData.className;
                            select.appendChild(opt);
                            return;
                        }

                        const val = parseNumericValue(optData.value);
                        if (val === null) {
                            const opt = document.createElement('option');
                            opt.value = optData.value;
                            opt.textContent = optData.text;
                            opt.className = optData.className;
                            select.appendChild(opt);
                            return;
                        }

                        const isInRange = val >= range.min && val <= range.max;
                        if (isInRange) {
                            const opt = document.createElement('option');
                            opt.value = optData.value;
                            opt.textContent = optData.text;
                            opt.className = optData.className;
                            select.appendChild(opt);
                        }
                    });

                    // Restore selected value if still exists
                    if (currentValue) {
                        const optionExists = select.querySelector(`option[value="${currentValue}"]`);
                        if (optionExists) {
                            select.value = currentValue;
                        }
                    }
                });
            }

            // For input-based ranges (if no select-based range exists)
            const fromInput = container.querySelector('input.fr');
            const toInput = container.querySelector('input.to');

            if (fromInput && toInput && range && range.min !== null && range.max !== null) {
                fromInput.setAttribute('min', range.min);
                fromInput.setAttribute('max', range.max);
                toInput.setAttribute('min', range.min);
                toInput.setAttribute('max', range.max);
            }
        });
    }

    function updateTotalCount(total) {
        // Don't modify UI - keep original appearance
    }

    function getCookie(name) {
        const match = document.cookie.match(new RegExp('(^| )' + name + '=([^;]+)'));
        return match ? match[2] : null;
    }

    function formatNumber(num) {
        return num.toString().replace(/\B(?=(\d{3})+(?!\d))/g, ' ');
    }

    function debounce(func, wait) {
        let timeout;
        return function executedFunction(...args) {
            const later = () => {
                clearTimeout(timeout);
                func.apply(this, args);
            };
            clearTimeout(timeout);
            timeout = setTimeout(later, wait);
        };
    }

    // Initialize on DOM ready
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', init);
    } else {
        init();
    }

    const observer = new MutationObserver((mutations) => {
        mutations.forEach((mutation) => {
            mutation.addedNodes.forEach((node) => {
                if (node.nodeType === 1 && node.id === 'fltr' && node.classList.contains('fix')) {
                    bindFilterEvents.call(null);
                }
            });
        });
    });

    observer.observe(document.body, { childList: true, subtree: true });

    // Expose for external use
    window.FacetedFilter = {
        refresh: loadFacets,
        getCurrentFilters: getCurrentFilters
    };

})();
