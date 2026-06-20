(function () {
    const AJAX_BASE = '/ajax.php';
    const L = (key, fallback) => (window.PARSING_LANG && window.PARSING_LANG[key]) || fallback || key;

    function ajax(action, data) {
        const body = new FormData();
        body.append('tp', 'adm');
        body.append('pg', 'parsing');
        body.append('action', action);
        for (const k in data) {
            if (data[k] === null || data[k] === undefined) continue;
            if (Array.isArray(data[k])) {
                data[k].forEach(v => body.append(k + '[]', v));
            } else {
                body.append(k, data[k]);
            }
        }
        return fetch(AJAX_BASE, { method: 'POST', body, credentials: 'same-origin' })
            .then(r => r.json());
    }

    function errorAlert(res) {
        const prefix = L('error_generic', 'Error');
        const detail = (res && res.error) ? res.error : '';
        alert(prefix + (detail ? ': ' + detail : ''));
    }

    // Full-page dimming overlay with a big spinner — shown while a search/import
    // runs so the page behind is greyed out (0.65) and the spinner stands out.
    function parsingShowOverlay() {
        let ov = document.getElementById('parsing-busy-overlay');
        if (!ov) {
            ov = document.createElement('div');
            ov.id = 'parsing-busy-overlay';
            ov.innerHTML = '<span class="pbo-spinner"></span>';
            document.body.appendChild(ov);
        }
        ov.style.display = 'flex';
    }
    function parsingHideOverlay() {
        const ov = document.getElementById('parsing-busy-overlay');
        if (ov) ov.style.display = 'none';
    }

    // ---------- Source panels (accordion) ----------

    window.parsingTogglePanel = function (src) {
        const body = document.getElementById('spb-' + src);
        if (!body) return;
        const willOpen = body.classList.contains('sp-collapsed');

        // Exclusive accordion: opening one panel collapses the other two.
        if (willOpen) {
            document.querySelectorAll('.source-panel-body').forEach(b => {
                if (b !== body) b.classList.add('sp-collapsed');
            });
            document.querySelectorAll('.source-panel').forEach(p => p.classList.remove('sp-open'));
        }

        body.classList.toggle('sp-collapsed', !willOpen);
        const panel = document.getElementById('sp-' + src);
        if (panel) panel.classList.toggle('sp-open', willOpen);
    };

    function sourceFormHasInput(form) {
        if (!form) return false;
        const fields = form.querySelectorAll('input, select');
        for (const el of fields) {
            if (el.type === 'hidden' || el.name === 'source') continue;
            if ((el.value || '').trim() !== '') return true;
        }
        return false;
    }

    function setSearchHint(form, show) {
        if (!form) return;
        const actions = form.querySelector('.search-actions');
        if (actions) actions.classList.toggle('show-hint', !!show);
    }

    // Any input clears a previously shown "select something" hint.
    ['input', 'change'].forEach(function (evt) {
        document.addEventListener(evt, function (e) {
            const form = e.target && e.target.closest && e.target.closest('.source-search-form');
            if (form && sourceFormHasInput(form)) setSearchHint(form, false);
        });
    });

    // Load models when brand changes inside any source form.
    document.addEventListener('change', function (e) {
        if (e.target && e.target.id === 'encar-brand') {
            loadEncarModels(e.target.value);
            loadEncarGenerations(e.target.value, '');
            return;
        }
        if (e.target && e.target.id === 'encar-model') {
            const brandKey = (document.getElementById('encar-brand') || {}).value || '';
            loadEncarGenerations(brandKey, e.target.value);
            return;
        }
        if (e.target && e.target.id === 'openlane-brand') {
            loadOpenlaneModels(e.target.value);
            return;
        }
        if (e.target && e.target.id === 'ecarstrade-brand') {
            loadEcarsModels(e.target.value);
            return;
        }
        if (e.target && e.target.matches('.source-search-form .brand')) {
            const form = e.target.closest('.source-search-form');
            if (!form) return;
            const modelSel = form.querySelector('.model');
            loadModelsBySautoBrand(e.target.value, modelSel);
        }
    });

    function loadEncarGenerations(brandKey, modelKey, selectedGen) {
        const sel = document.getElementById('encar-generation');
        if (!sel) return;
        sel.innerHTML = '<option value="">' + L('filter_all', 'Toate') + '</option>';
        if (!brandKey || !modelKey) return;

        const brands = window.ENCAR_BRANDS || {};
        const brand = brands[brandKey];
        if (!brand || !brand.models) return;
        const model = brand.models.find(m => m.key === modelKey);
        if (!model || !model.generations || !model.generations.length) return;

        model.generations.forEach(function (g) {
            const opt = document.createElement('option');
            opt.value = g.key;
            opt.textContent = g.label || g.key;
            if (selectedGen && g.key === selectedGen) opt.selected = true;
            sel.appendChild(opt);
        });
    }

    // Populate Encar model select from the taxonomy data embedded in the page.
    function loadEncarModels(brandKey, selectedModel) {
        const modelSel = document.getElementById('encar-model');
        if (!modelSel) return;
        modelSel.innerHTML = '<option value="">' + L('filter_all', 'Toate') + '</option>';
        if (!brandKey) return;

        const brands = window.ENCAR_BRANDS || {};
        const entry = brands[brandKey];
        if (!entry || !entry.models || !entry.models.length) return;

        entry.models.forEach(function (m) {
            const opt = document.createElement('option');
            opt.value = m.key;
            opt.textContent = m.label || m.key;
            if (selectedModel && m.key === selectedModel) opt.selected = true;
            modelSel.appendChild(opt);
        });
    }

    // Populate OpenLane model select from the taxonomy embedded in the page
    // (window.OPENLANE_BRANDS: { "BMW": [{value,label}, ...] }). Real OpenLane
    // models per make, with counts — matches the search API exactly.
    function loadOpenlaneModels(brand, selectedModel) {
        const modelSel = document.getElementById('openlane-model');
        if (!modelSel) return;
        const defText = modelSel.getAttribute('def_text') || '';
        modelSel.innerHTML = '<option value="">' + defText + '</option>';
        if (!brand) return;
        const models = (window.OPENLANE_BRANDS || {})[brand] || [];
        models.forEach(function (m) {
            const opt = document.createElement('option');
            opt.value = m.value;
            opt.textContent = m.label || m.value;
            if (selectedModel && m.value === selectedModel) opt.selected = true;
            modelSel.appendChild(opt);
        });
    }

    // Populate eCarsTrade model select from window.ECARS_BRANDS (real models per
    // brand, scraped from listings — see ecarstrade_taxonomy.json).
    function loadEcarsModels(brand, selectedModel) {
        const modelSel = document.getElementById('ecarstrade-model');
        if (!modelSel) return;
        const defText = modelSel.getAttribute('def_text') || '';
        modelSel.innerHTML = '<option value="">' + defText + '</option>';
        if (!brand) return;
        const models = (window.ECARS_BRANDS || {})[brand] || [];
        models.forEach(function (m) {
            const opt = document.createElement('option');
            opt.value = m.value;
            opt.textContent = m.label || m.value;
            if (selectedModel && m.value === selectedModel) opt.selected = true;
            modelSel.appendChild(opt);
        });
    }

    // Trigger model load on page ready if brand already has a value (e.g. after filter restore).
    document.addEventListener('DOMContentLoaded', function () {
        const brandEl = document.getElementById('encar-brand');
        if (brandEl && brandEl.value) {
            loadEncarModels(brandEl.value);
        }
        const olBrand = document.getElementById('openlane-brand');
        if (olBrand && olBrand.value) {
            loadOpenlaneModels(olBrand.value);
        }
        const ecBrand = document.getElementById('ecarstrade-brand');
        if (ecBrand && ecBrand.value) {
            loadEcarsModels(ecBrand.value);
        }
        // Cached cover images may already be complete before their inline onload
        // attaches (notably on refresh), leaving them stuck invisible. Mark any
        // already-loaded cover visible so it never hides.
        document.querySelectorAll('.car-cover').forEach(function (img) {
            if (img.complete && img.naturalWidth > 0) {
                img.classList.add('is-loaded');
            }
        });
    });

    // Populate models via sauto AJAX (for e-CarsTrade / OPENLane).
    function loadModelsBySautoBrand(brandValue, modelSelect) {
        if (!modelSelect) return;
        const defText = modelSelect.getAttribute('def_text') || '';
        modelSelect.innerHTML = '<option value="">' + defText + '</option>';
        if (!brandValue) return;

        const body = new FormData();
        body.append('tp', 'adm');
        body.append('pg', 'cars');
        body.append('fn', 'add_new');
        body.append('sub', 'mo_search');
        body.append('br', brandValue);
        body.append('bx_id', 'parsing-search');

        fetch('/ajax.php', { method: 'POST', body, credentials: 'same-origin' })
            .then(r => r.json())
            .then(data => {
                const html = (data && data.rtrn && data.rtrn.str) || '';
                if (html) modelSelect.insertAdjacentHTML('beforeend', html);
            })
            .catch(err => console.error('loadModels failed:', err));
    }

    // Read all non-empty fields from a source form and return as plain object.
    function readSourceForm(src) {
        const form = document.getElementById('sf-' + src);
        if (!form) return null;
        const fd = new FormData(form);
        const data = {};
        fd.forEach((v, k) => {
            const value = (typeof v === 'string') ? v.trim() : v;
            if (value !== '' && value !== null && value !== undefined) data[k] = value;
        });
        // source is already a hidden field in each form, but make sure sources[] is also set.
        data.sources = [src];
        return data;
    }

    // Search button handler — called from each form's onsubmit.
    window.parsingSearchSource = function (e, src) {
        e.preventDefault();
        const form = document.getElementById('sf-' + src);
        // Block the search if nothing is selected (would pull the whole
        // catalogue) and show the hint above the button instead.
        if (!sourceFormHasInput(form)) {
            setSearchHint(form, true);
            return;
        }
        setSearchHint(form, false);
        const data = readSourceForm(src);
        const result = document.getElementById('ssr-' + src);
        if (!result) return;

        result.className = 'source-search-result';
        result.innerHTML = '';
        parsingShowOverlay();

        ajax('search_now', data).then(res => {
            parsingHideOverlay();
            if (res && res.success) {
                result.className = 'source-search-result show ok';
                const imported = res.imported || 0;
                const dups = res.duplicates || 0;
                // Imported now + how many were already in our DB (duplicates skipped).
                let detail = '';
                if (dups > 0) {
                    detail += ' <span class="ssr-sub">(' + dups + ' ' + L('already_have', 'deja la noi') + ')</span>';
                }
                // Remember the cutoff so the cars just imported get the amber ring
                // when "Vezi catalogul" lands on /parsing/ctlg.
                if (imported > 0) {
                    try { sessionStorage.setItem('parsing_ring_since', String(res.imported_since || 0)); } catch (e) {}
                    // Translate any Korean Encar trims to Latin in the background
                    // (batched + cached); they show up translated after the catalog loads.
                    if (src === 'encar') ajax('translate_trims', {}).catch(() => {});
                }
                result.innerHTML = '<span class="ssr-count"><strong>' + imported + '</strong> ' + L('imported_label', 'importate') + detail + '</span>' +
                    '<a class="btn-see-catalog" href="/' + (window.ADMIN_DIR || 'adm') + '/parsing/ctlg">' + L('see_proposed', 'Vezi catalogul') + '</a>';
            } else {
                result.className = 'source-search-result show err';
                result.textContent = (res && res.error) ? res.error : L('error_generic', 'Eroare');
            }
        }).catch(err => {
            parsingHideOverlay();
            result.className = 'source-search-result show err';
            result.textContent = L('error_generic', 'Eroare');
        });
    };

    // Save the current source panel form as a named filter.
    let _saveFilterSrc = null;
    window.parsingSaveSourceFilter = function (src) {
        _saveFilterSrc = src;
        const modal = document.getElementById('save-filter-modal');
        const input = document.getElementById('save-filter-name');
        if (!modal || !input) return;
        input.value = '';
        modal.style.display = 'flex';
        setTimeout(() => input.focus(), 50);
    };
    window.parsingSaveFilterCancel = function () {
        document.getElementById('save-filter-modal').style.display = 'none';
        _saveFilterSrc = null;
    };
    window.parsingSaveFilterConfirm = function () {
        const name = (document.getElementById('save-filter-name').value || '').trim();
        if (!name || !_saveFilterSrc) return;
        const data = readSourceForm(_saveFilterSrc);
        if (!data) return;
        data.name = name;
        document.getElementById('save-filter-modal').style.display = 'none';
        ajax('save_filter', data).then(res => {
            if (res && res.success) location.reload();
            else errorAlert(res);
        });
    };
    document.addEventListener('keydown', function (e) {
        const modal = document.getElementById('save-filter-modal');
        if (!modal || modal.style.display === 'none') return;
        if (e.key === 'Escape') parsingSaveFilterCancel();
        if (e.key === 'Enter') parsingSaveFilterConfirm();
    });

    // Load a saved filter back into the correct source panel.
    window.parsingLoadIntoPanel = function (id) {
        ajax('get_filter', { id }).then(res => {
            if (!res || !res.success) return errorAlert(res);
            const f = res.filter;

            // Determine which source panel to open (first source in list).
            const sources = (f.sources || '').split(',').map(s => s.trim()).filter(Boolean);
            const src = sources[0] || 'encar';

            // Open the panel if collapsed.
            const body = document.getElementById('spb-' + src);
            if (body && body.classList.contains('sp-collapsed')) {
                parsingTogglePanel(src);
            }

            const form = document.getElementById('sf-' + src);
            if (!form) return;

            // Set scalar fields.
            ['brand','model','car_sub_model','year_from','year_to','km_max','price_max',
             'fuel_type','gearbox','drive_type','body_type','country_origin','seats']
                .forEach(k => {
                    const el = form.querySelector('[name="' + k + '"]');
                    if (el && f[k] != null) el.value = f[k];
                });

            // Reload models if brand is set.
            if (f.brand) {
                if (src === 'encar') {
                    // Encar: instant from taxonomy data embedded in page.
                    const brandEl = document.getElementById('encar-brand');
                    if (brandEl) brandEl.value = f.brand;
                    loadEncarModels(f.brand, f.model);
                } else {
                    const brandEl = form.querySelector('[name="brand"]');
                    const modelEl = form.querySelector('[name="model"]');
                    if (brandEl && modelEl) {
                        loadModelsBySautoBrand(f.brand, modelEl);
                        if (f.model) {
                            setTimeout(() => { modelEl.value = f.model; }, 600);
                        }
                    }
                }
            }

            form.scrollIntoView({ behavior: 'smooth', block: 'start' });
            // A loaded filter has values, so clear any stale hint.
            setSearchHint(form, false);
        });
    };

    // ---------- Filters ----------

    window.parsingDelete = function (id) {
        if (!confirm(L('confirm_delete_filter'))) return;
        ajax('delete_filter', { id }).then(res => {
            if (res && res.success) location.reload();
            else errorAlert(res);
        });
    };

    window.parsingToggle = function (id) {
        ajax('toggle_filter', { id }).then(res => {
            if (res && res.success) location.reload();
        });
    };

    window.parsingRunNow = function (id) {
        if (!confirm(L('confirm_run_now'))) return;
        const btn = document.querySelector('[onclick*="parsingRunNow(' + id + ')"]');
        if (btn) btn.textContent = '…';
        ajax('run_filter', { id }).then(res => {
            if (btn) btn.textContent = '▶';
            if (res && res.success) {
                let msg = (res.imported || 0) + ' ' + L('imported_label', 'importate');
                if (res.duplicates > 0) msg += ' · ' + res.duplicates + ' ' + L('already_have', 'deja la noi');
                alert(msg);
                if (res.imported > 0) location.reload();
            } else errorAlert(res);
        });
    };

    // ---------- Proposed cars ----------
    // Make sure HP + drive_type are filled in DB (via Groq AI) before any
    // publish/edit action. Shows a brief loading state on the card.
    function ensureSpecsEnriched(carId) {
        const card = document.querySelector('[data-car-id="' + carId + '"]');
        if (card) card.classList.add('card-loading');
        return ajax('ai_enrich_specs', { car_id: carId })
            .catch(() => null)
            .then(res => {
                if (card) card.classList.remove('card-loading');
                applyMdInputs(carId, res);
                return res;
            });
    }

    function applyMdInputs(carId, res) {
        if (!res || !res.md_inputs) return;
        const card = document.querySelector('[data-car-id="' + carId + '"]');
        if (!card) return;
        const mi = res.md_inputs;
        if (mi.capacity != null) card.setAttribute('data-capacity', mi.capacity);
        if (mi.fuel) card.setAttribute('data-fuel', mi.fuel);
        if (mi.year) card.setAttribute('data-year', mi.year);
        card.removeAttribute('data-md-pending');
        renderMdPrices();
    }

    // Resolve the car's sauto brand + model codes the SAME way the publish form
    // does: brand → its sauto code (prefix-style), then load that brand's sauto
    // model list and pick the best match with Groq (match_model) — so e.g.
    // "BMW 318" maps to "Seria 3", not a raw "318" the catalog doesn't have.
    // Returns { br, mo } (either may be '' if not resolvable).
    function resolveSautoBrandModel(brandName, modelName) {
        const brName = (brandName || '').trim();
        const moName = (modelName || '').trim();
        if (!brName) return Promise.resolve({ br: '', mo: '' });
        const brCode = brName.toLowerCase().replace(/[\s-]+/g, '_');

        // Load this brand's sauto models (HTML <option>s from mo_search).
        const body = new FormData();
        body.append('tp', 'adm'); body.append('pg', 'cars');
        body.append('fn', 'add_new'); body.append('sub', 'mo_search');
        body.append('br', brCode); body.append('bx_id', 'parsing-publish');
        return fetch('/ajax.php', { method: 'POST', body, credentials: 'same-origin' })
            .then(r => r.json())
            .then(data => {
                const html = (data && data.rtrn && data.rtrn.str) || '';
                if (!html || !moName) return { br: brCode, mo: '' };
                // Parse the returned <option> list into {value,text}.
                const tmp = document.createElement('select');
                tmp.innerHTML = html;
                const options = Array.from(tmp.options)
                    .filter(o => o.value !== '')
                    .map(o => ({ value: o.value, text: o.textContent.trim() }));
                if (!options.length) return { br: brCode, mo: '' };
                // Exact text/value match first (cheap), else Groq match_model.
                const exact = options.find(o =>
                    o.text.toLowerCase() === moName.toLowerCase() ||
                    o.value.toLowerCase() === moName.toLowerCase());
                if (exact) return { br: brCode, mo: exact.value };
                return ajax('match_model', {
                    brand: brName, raw_model: moName, options: JSON.stringify(options)
                }).then(res => ({ br: brCode, mo: (res && res.success && res.value) ? res.value : '' }))
                  .catch(() => ({ br: brCode, mo: '' }));
            })
            .catch(() => ({ br: brCode, mo: '' }));
    }

    window.parsingPublish = function (carId, target) {
        if (target === 'sauto') {
            // Publish via the SERVER-SIDE queue: enrich specs (HP/drive into the
            // DB), resolve the sauto brand+model (BMW 318 → Seria 3) like the form,
            // enqueue with those codes, then drop the card. The worker publishes
            // server-side, so the operator can queue more cars or leave the page.
            const card = document.querySelector('[data-car-id="' + carId + '"]');
            if (card) card.classList.add('card-loading');
            const brandName = card ? card.getAttribute('data-brand') : '';
            const modelName = card ? card.getAttribute('data-model') : '';
            Promise.all([
                ensureSpecsEnriched(carId),
                resolveSautoBrandModel(brandName, modelName)
            ]).then(([_specs, bm]) => {
                ajax('publish_enqueue', {
                    car_id: carId, target: 'sauto', sauto_br: bm.br, sauto_mo: bm.mo
                }).then(res => {
                    if (card) card.classList.remove('card-loading');
                    if (res && res.success) {
                        if (card) card.remove();
                        updatePublishQueueBadge(res.queue);
                    } else {
                        errorAlert(res);
                    }
                });
            });
            return;
        }

        if (!confirm(L('confirm_publish') + ' ' + target + '?')) return;
        ensureSpecsEnriched(carId).then(() => {
            ajax('publish_car', { car_id: carId, target }).then(res => {
                if (res && res.success) location.reload();
                else errorAlert(res);
            });
        });
    };

    // ---------- Publish queue indicator (sauto, server-side) ----------
    let _pubQueuePoll = null;
    function updatePublishQueueBadge(counts) {
        let badge = document.getElementById('publish-queue-badge');
        const pending = counts ? ((counts.pending || 0) + (counts.processing || 0)) : 0;
        const failed = counts ? (counts.failed || 0) : 0;

        if (!pending && !failed) {
            if (badge) badge.remove();
            if (_pubQueuePoll) { clearInterval(_pubQueuePoll); _pubQueuePoll = null; }
            return;
        }
        if (!badge) {
            badge = document.createElement('div');
            badge.id = 'publish-queue-badge';
            badge.className = 'publish-queue-badge';
            document.body.appendChild(badge);
        }
        let html = pending
            ? '<span class="pqb-spin"></span> ' + L('publishing_label', 'Se publică') + ': <strong>' + pending + '</strong>'
            : '';
        if (failed) {
            html += (pending ? ' · ' : '') +
                '<button type="button" class="pqb-failed" onclick="parsingShowFailures()">' +
                failed + ' ' + L('publish_failed_label', 'eșuate') + '</button>';
        }
        badge.innerHTML = html;

        if (!_pubQueuePoll) {
            _pubQueuePoll = setInterval(function () {
                ajax('publish_queue_status', {}).then(r => { if (r && r.success) updatePublishQueueBadge(r.queue); });
            }, 4000);
        }
    }

    document.addEventListener('DOMContentLoaded', function () {
        ajax('publish_queue_status', {}).then(r => { if (r && r.success) updatePublishQueueBadge(r.queue); });
    });

    // ---------- Failures panel (sauto queue) ----------
    window.parsingShowFailures = function () {
        ajax('publish_queue_failed', {}).then(res => {
            if (!res || !res.success) { errorAlert(res); return; }
            renderFailuresPanel(res.failed || []);
        });
    };

    function failureEditUrl(job) {
        const lang = (document.cookie.split('; ').find(c => c.startsWith('lang=')) || 'lang=ro').split('=')[1];
        const adminDir = window.ADMIN_DIR || 'adm';
        const base = '/' + lang + '/' + adminDir + '/ordercars/detail';
        return (job.target === 'sauto' || !job.car_ctlg_id)
            ? base + '?parsing_id=' + job.parsing_car_id
            : base + '?id=' + job.car_ctlg_id;
    }

    function renderFailuresPanel(failed) {
        let panel = document.getElementById('publish-failures-panel');
        if (panel) panel.remove();
        if (!failed.length) return;
        panel = document.createElement('div');
        panel.id = 'publish-failures-panel';
        panel.className = 'publish-failures-panel';
        const head = '<div class="pfp-head"><strong>' + L('failures_title', 'Publicări eșuate') +
            '</strong><button type="button" class="pfp-close" onclick="parsingCloseFailures()">✕</button></div>';
        const rows = failed.map(job => {
            const car = [job.brand, job.model, job.year].filter(Boolean).join(' ') || ('#' + job.parsing_car_id);
            const reason = (job.error || '').toString();
            return '<div class="pfp-row" data-job="' + job.id + '">' +
                '<div class="pfp-info"><div class="pfp-car">' + escapeHtml(car) + '</div>' +
                '<div class="pfp-reason" title="' + escapeHtml(reason) + '">' + escapeHtml(reason) + '</div></div>' +
                '<div class="pfp-actions">' +
                '<a class="pfp-btn pfp-edit" href="' + failureEditUrl(job) + '" target="_blank" rel="noopener" onclick="parsingDismissFailure(' + job.id + ')">' + L('action_edit', 'Editează') + '</a>' +
                '<button type="button" class="pfp-btn pfp-ignore" onclick="parsingDismissFailure(' + job.id + ')">' + L('action_ignore', 'Ignoră') + '</button>' +
                '</div></div>';
        }).join('');
        panel.innerHTML = head + '<div class="pfp-list">' + rows + '</div>';
        document.body.appendChild(panel);
    }

    window.parsingCloseFailures = function () {
        const panel = document.getElementById('publish-failures-panel');
        if (panel) panel.remove();
    };
    // Clicking "Editează" also clears the failed job from the queue (the operator
    // is taking over manually), so the badge count drops and it won't pile up.
    window.parsingDismissFailure = function (jobId) {
        ajax('publish_queue_dismiss', { job_id: jobId }).then(res => {
            removeFailureRow(jobId);
            if (res && res.queue) updatePublishQueueBadge(res.queue);
        });
    };
    function removeFailureRow(jobId) {
        const row = document.querySelector('.pfp-row[data-job="' + jobId + '"]');
        if (row) row.remove();
        const panel = document.getElementById('publish-failures-panel');
        if (panel && !panel.querySelector('.pfp-row')) panel.remove();
    }
    function escapeHtml(s) {
        return String(s == null ? '' : s)
            .replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;')
            .replace(/"/g, '&quot;').replace(/'/g, '&#39;');
    }

    // ── 999 publications today (modal) ──
    window.parsingShow999Stats = function () {
        ajax('publish_999_stats', {}).then(res => {
            if (!res || !res.success) { errorAlert(res); return; }
            render999StatsModal(res);
        });
    };
    window.parsingClose999Stats = function () {
        const m = document.getElementById('stats-999-modal');
        if (m) m.remove();
    };
    function render999StatsModal(data) {
        parsingClose999Stats();
        const head =
            '<div class="s999-row s999-head"><span class="s999-acc"></span>' +
            '<span class="s999-num" title="' + L('published_today', 'Publicate azi') + '">' + L('col_done', 'Publ.') + '</span>' +
            '<span class="s999-num" title="' + L('pending_today', 'În așteptare azi') + '">' + L('col_pending', 'Așteapt.') + '</span></div>';
        const rows = (data.rows || []).map(r =>
            '<div class="s999-row"><span class="s999-acc">' + escapeHtml(r.account) +
            '</span><span class="s999-num">' + (r.count || 0) + '</span>' +
            '<span class="s999-num s999-pending">' + (r.pending || 0) + '</span></div>'
        ).join('');
        const modal = document.createElement('div');
        modal.id = 'stats-999-modal';
        modal.className = 'parsing-modal';
        modal.style.display = 'flex';
        modal.innerHTML =
            '<div class="parsing-modal-content" style="max-width:480px;">' +
                '<div class="modal-header">' +
                    '<h2>' + escapeHtml(data.date || '') + '</h2>' +
                    '<button class="modal-close" onclick="parsingClose999Stats()">✕</button>' +
                '</div>' +
                '<div class="s999-list">' + head + rows +
                    '<div class="s999-row s999-total"><span class="s999-acc">' +
                    L('total_label', 'Total') + '</span><span class="s999-num">' + (data.total || 0) + '</span>' +
                    '<span class="s999-num s999-pending">' + (data.total_pending || 0) + '</span></div>' +
                '</div>' +
            '</div>';
        modal.addEventListener('click', function (e) { if (e.target === modal) parsingClose999Stats(); });
        document.body.appendChild(modal);
    }

    // ── Cross-post queue (client-side, real publishing via the form iframe) ──
    // 999 / Facebook / Telegram publish for real ONLY through the ordercars form
    // (autopublishXXX flags auto-fill + submit) — can't be done headless. So we
    // run a CLIENT-SIDE queue: each job opens the hidden form iframe and waits for
    // it to finish, then moves to the next. Selecting many cards no longer spawns
    // many iframes at once (server + browser freeze) — they go one at a time. The
    // page must stay open while the queue drains; a badge shows what's left.
    const _xpQueue = [];
    let _xpRunning = false;
    // Progress is counted in CARS, not platform jobs: "0/2" for two cars even if
    // each posts to several platforms. A car counts as done once all its jobs in
    // the run are finished.
    const _xpCarsTotal = new Set();   // distinct car ids in the current run
    const _xpCarsDone  = new Set();   // car ids whose every job is finished
    let _xpReloading = false;  // set when WE reload at the end (skip the unload warning)

    // Warn before leaving while a cross-post run is still going (it would stop
    // the remaining publishes). Browsers show their own generic confirm dialog.
    window.addEventListener('beforeunload', function (e) {
        if (!_xpReloading && (_xpRunning || _xpQueue.length)) { e.preventDefault(); e.returnValue = ''; }
    });

    function crossPostEnqueue(jobs) {
        jobs.forEach(j => {
            _xpQueue.push(j);
            _xpCarsTotal.add(j.carCtlgId);
            // Mark the card busy IMMEDIATELY (queued), not only when its turn comes,
            // so every card the operator clicks shows feedback right away — not just
            // the one currently publishing.
            const grid = document.querySelector('.publish-grid-2x2[data-ctlg="' + j.carCtlgId + '"]');
            if (grid) grid.classList.add('is-publishing');
        });
        updateCrossPostBadge();
        if (!_xpRunning) runCrossPostQueue();
    }

    function runCrossPostQueue() {
        const job = _xpQueue.shift();
        if (!job) {
            _xpRunning = false;
            _xpCarsTotal.clear(); _xpCarsDone.clear();
            updateCrossPostBadge();
            _xpReloading = true;  // our own reload — don't trigger the leave warning
            location.reload();    // show each button's real published state
            return;
        }
        _xpRunning = true;
        const grid = document.querySelector('.publish-grid-2x2[data-ctlg="' + job.carCtlgId + '"]');
        if (grid) grid.classList.add('is-publishing');  // already set at enqueue; safe
        crossPostFrame(job.target, job.carCtlgId).then(function (ok) {
            // Mark this car done once it has no remaining jobs in the queue.
            if (!_xpQueue.some(j => j.carCtlgId === job.carCtlgId)) {
                _xpCarsDone.add(job.carCtlgId);
            }
            // A job that didn't confirm success (timeout / error) is remembered so
            // the operator sees what failed after the reload (panel below).
            if (!ok) {
                const car = grid && grid.closest('.car-card');
                rememberCrossPostFail({
                    ctlg: job.carCtlgId,
                    target: job.target,
                    title: car ? (car.querySelector('h3') ? car.querySelector('h3').textContent.trim() : '') : '',
                    brand: car ? (car.getAttribute('data-brand') || '') : '',
                    model: car ? (car.getAttribute('data-model') || '') : ''
                });
            }
            updateCrossPostBadge();
            runCrossPostQueue();
        });
    }

    // Cross-post failures live only in the browser (no DB queue), so persist them
    // in sessionStorage to survive the end-of-run reload, then show a panel.
    const XPOST_FAIL_KEY = 'parsing_xpost_fails';
    function rememberCrossPostFail(f) {
        let list;
        try { list = JSON.parse(sessionStorage.getItem(XPOST_FAIL_KEY) || '[]'); } catch (e) { list = []; }
        list.push(f);
        try { sessionStorage.setItem(XPOST_FAIL_KEY, JSON.stringify(list)); } catch (e) {}
    }

    // Open the real publish form in a hidden iframe (autopublish flag) and resolve
    // when it finishes (redirects to /parsing/published) or after a timeout.
    function crossPostFrame(target, carCtlgId) {
        return new Promise(function (resolve) {
            if (!carCtlgId) { resolve(false); return; }
            const lang = (document.cookie.split('; ').find(c => c.startsWith('lang=')) || 'lang=ro').split('=')[1];
            const adminDir = window.ADMIN_DIR || 'adm';
            const flag = { '999': 'autopublish999', 'facebook': 'autopublishfb', 'telegram': 'autopublishtg' }[target];
            if (!flag) { resolve(false); return; }
            const url = '/' + lang + '/' + adminDir + '/ordercars/detail?id=' + carCtlgId + '&' + flag + '=1';

            const iframe = document.createElement('iframe');
            iframe.style.cssText = 'position:fixed;width:0;height:0;border:0;left:-9999px;top:-9999px;';
            let settled = false;
            const finish = ok => {
                if (settled) return; settled = true;
                clearTimeout(killTimer);
                try { iframe.remove(); } catch (e) {}
                resolve(ok);
            };
            iframe.addEventListener('load', function () {
                try {
                    if (iframe.contentWindow.location.pathname.indexOf('/parsing/published') !== -1) finish(true);
                } catch (e) { /* same-site; ignore */ }
            });
            // Generous cap — 999 form fill + API can be slow.
            const killTimer = setTimeout(() => finish(false), 90000);
            iframe.src = url;
            document.body.appendChild(iframe);
        });
    }

    // Single-platform cross-post → one job into the client-side queue.
    window.parsingCrossPost = function (target, carCtlgId) {
        if (!carCtlgId) { alert(L('not_on_sauto', 'Mașina nu e publicată pe sauto.md.')); return; }
        crossPostEnqueue([{ carCtlgId: carCtlgId, target: target }]);
    };

    // "Publish to all" → one job per pending platform into the queue.
    window.parsingCrossPostAll = function (carCtlgId, pending) {
        if (!carCtlgId || !Array.isArray(pending) || !pending.length) return;
        crossPostEnqueue(pending.map(t => ({ carCtlgId: carCtlgId, target: t })));
    };

    // Bottom-right progress bar with % + a "don't leave the page" warning, so the
    // operator knows how far the cross-post run is and when it's safe to leave.
    function updateCrossPostBadge() {
        let badge = document.getElementById('crosspost-queue-badge');
        const jobsLeft = _xpQueue.length + (_xpRunning ? 1 : 0);
        if (jobsLeft <= 0) { if (badge) badge.remove(); return; }
        if (!badge) {
            badge = document.createElement('div');
            badge.id = 'crosspost-queue-badge';
            badge.className = 'publish-queue-badge xpost-progress';
            document.body.appendChild(badge);
        }
        // Count in CARS, not platform jobs.
        const total = _xpCarsTotal.size;
        const done = _xpCarsDone.size;
        const pct = total > 0 ? Math.round((done / total) * 100) : 0;
        badge.innerHTML =
            '<div class="xpb-top"><span class="pqb-spin"></span>' +
                '<span class="xpb-label">' + L('publishing_label', 'Se publică') +
                ' ' + done + '/' + total + '</span>' +
                '<strong class="xpb-pct">' + pct + '%</strong></div>' +
            '<div class="xpb-bar"><div class="xpb-fill" style="width:' + pct + '%"></div></div>' +
            '<div class="xpb-warn">' + L('do_not_leave', 'Nu închide pagina până se termină') + '</div>';
    }

    // ---------- Cross-post failures panel (TG/FB/999) ----------
    // After the run reloads, show any failed cross-posts with the car, the
    // platform, and Retry / Edit / Ignore — mirroring the sauto failures panel.
    function platformLabelXp(t) {
        return { '999': '999.md', facebook: 'Facebook', telegram: 'Telegram' }[t] || t;
    }
    function showCrossPostFails() {
        let list;
        try { list = JSON.parse(sessionStorage.getItem(XPOST_FAIL_KEY) || '[]'); } catch (e) { list = []; }
        if (!list.length) return;

        let panel = document.getElementById('xpost-failures-panel');
        if (panel) panel.remove();
        panel = document.createElement('div');
        panel.id = 'xpost-failures-panel';
        panel.className = 'publish-failures-panel';
        const head = '<div class="pfp-head"><strong>' + L('failures_title', 'Publicări eșuate') +
            '</strong><button type="button" class="pfp-close" onclick="parsingCloseXpostFails()">✕</button></div>';
        const lang = (document.cookie.split('; ').find(c => c.startsWith('lang=')) || 'lang=ro').split('=')[1];
        const adminDir = window.ADMIN_DIR || 'adm';
        const rows = list.map((f, i) => {
            const car = (f.title || [f.brand, f.model].filter(Boolean).join(' ') || ('#' + f.ctlg));
            // Cross-post car is already on sauto → edit the live ad (?id=). The
            // operator fixes whatever's wrong, then publishes manually from there.
            const editUrl = '/' + lang + '/' + adminDir + '/ordercars/detail?id=' + f.ctlg;
            return '<div class="pfp-row" data-xp="' + i + '">' +
                '<div class="pfp-info"><div class="pfp-car">' + escapeHtml(car) +
                    ' <span class="pfp-platform">' + escapeHtml(platformLabelXp(f.target)) + '</span></div></div>' +
                '<div class="pfp-actions">' +
                '<a class="pfp-btn pfp-edit" href="' + editUrl + '" target="_blank" rel="noopener">' + L('action_edit', 'Editează') + '</a>' +
                '</div></div>';
        }).join('');
        panel.innerHTML = head + '<div class="pfp-list">' + rows + '</div>';
        document.body.appendChild(panel);
        // Failures are now shown — clear storage so they don't reappear on the
        // next reload. The operator edits via the button while the panel is open.
        try { sessionStorage.removeItem(XPOST_FAIL_KEY); } catch (e) {}
    }
    window.parsingCloseXpostFails = function () {
        const p = document.getElementById('xpost-failures-panel');
        if (p) p.remove();
    };
    // Show the panel on load (after the end-of-run reload).
    document.addEventListener('DOMContentLoaded', showCrossPostFails);

    window.parsingReject = function (carId) {
        ajax('reject_car', { car_id: carId }).then(res => {
            if (res && res.success) {
                const card = document.querySelector('[data-car-id="' + carId + '"]');
                if (card) card.remove();
            }
        });
    };

    window.parsingFavorite = function (carId) {
        ajax('favorite_car', { car_id: carId }).then(res => {
            if (res && res.success) {
                const card = document.querySelector('[data-car-id="' + carId + '"]');
                if (card) card.remove();
            }
        });
    };

    window.parsingUnfavorite = function (carId) {
        ajax('unfavorite_car', { car_id: carId }).then(res => {
            if (res && res.success) {
                const card = document.querySelector('[data-car-id="' + carId + '"]');
                if (card) card.remove();
            }
        });
    };

    // Edit: enrich missing specs via AI first, then redirect to sauto form.
    window.parsingEditCar = function (carId) {
        const lang = (document.cookie.split('; ').find(c => c.startsWith('lang=')) || 'lang=ro').split('=')[1];
        const adminDir = window.ADMIN_DIR || 'adm';
        const target = '/' + lang + '/' + adminDir + '/ordercars/detail?parsing_id=' + carId;

        // Run AI enrichment first so HP/drive_type land in DB before prefill loads.
        ensureSpecsEnriched(carId).then(() => {
            window.location.href = target;
        });
    };

    // Show the translated Encar inspection report inline, in the shared car modal
    // (same modal as Characteristics) — no popup window. Exactly the HTML that
    // renders on the public page, forced open, with the raw JSON foldable below.
    window.parsingTestReport = function (carId) {
        const modal = document.getElementById('parsing-car-modal');
        const body = document.getElementById('car-modal-body');
        const title = document.getElementById('car-modal-title');
        const saveBtn = document.getElementById('car-modal-save');
        if (!modal || !body) return;

        // Build a descriptive title from the card's data: "Raport BMW i8 2015 · 216 209 km · Hybrid".
        const card = document.querySelector('[data-car-id="' + carId + '"]');
        const reportWord = L('btn_report', 'Raport');
        let titleTxt = reportWord + ' Encar';
        if (card) {
            const d = card.dataset;
            const fuelLabel = {
                benzina: 'Benzină', diesel: 'Diesel', lpg: 'LPG', hybrid: 'Hybrid (Benzină)',
                diesel_hybrid: 'Hybrid (Diesel)', gasoline_lpg: 'Benzină+LPG',
                gasoline_cng: 'Benzină+CNG', electric: 'Electric', other: 'Altele',
            };
            const parts = [];
            const name = [d.brand, d.model].filter(Boolean).join(' ').trim();
            if (name) parts.push(name);
            if (d.year && d.year !== '0') parts.push(d.year);
            if (d.km && d.km !== '0') parts.push(Number(d.km).toLocaleString('ro-RO').replace(/,/g, ' ') + ' km');
            if (d.fuel) parts.push(fuelLabel[d.fuel] || d.fuel);
            if (parts.length) titleTxt = reportWord + ' ' + parts.join(' · ');
        }

        title.textContent = titleTxt;
        // Reuse the modal's save button as "Descarcă PDF" for the report.
        if (saveBtn) {
            const L = (k, fb) => (window.PARSING_LANG && window.PARSING_LANG[k]) || fb;
            saveBtn.style.display = 'none';
            saveBtn.classList.add('btn-pdf');
            saveBtn.textContent = L('btn_download_pdf', 'Descarcă PDF');
            saveBtn.onclick = function () { parsingReportToPdf(titleTxt); };
        }
        modal.style.display = 'flex';
        parsingRememberViewedCard(carId);
        // Report tables need more room than the standard car modal.
        modal.querySelector('.car-modal-content')?.classList.add('er-wide');
        document.body.style.overflow = 'hidden';

        // Render the report HTML into the modal (and reveal the PDF button).
        const show = function (htmlStr) {
            body.innerHTML = '<div class="er-modal-wrap">' + (htmlStr || '<em>(fără HTML)</em>') + '</div>';
            // Public layout emits two cards (Istoric + Dotări) — open both.
            body.querySelectorAll('.encar-report').forEach(function (c) { c.classList.add('open'); });
            if (saveBtn) saveBtn.style.display = '';
        };

        // Browser-side cache: once a car's report HTML is fetched, keep it in
        // memory. Re-opening the SAME car renders instantly with no server call.
        window._parsingReportCache = window._parsingReportCache || {};
        if (window._parsingReportCache[carId]) {
            show(window._parsingReportCache[carId]);
            return;
        }

        body.innerHTML = '<div class="er-loading"><span class="er-spinner"></span></div>';
        ajax('test_report', { car_id: carId }).then(res => {
            if (res && res.success) {
                window._parsingReportCache[carId] = res.html || '';
                show(res.html);
            } else {
                body.innerHTML = '<div class="err">' + L('report_load_error', 'Eroare la încărcarea raportului') + '</div>';
            }
        });
    };

    // OpenLane report — equipment + condition (damages). Same modal as Encar's
    // report, but fed by the openlane_report endpoint.
    window.parsingOpenlaneReport = function (carId) {
        const modal = document.getElementById('parsing-car-modal');
        const body = document.getElementById('car-modal-body');
        const title = document.getElementById('car-modal-title');
        const saveBtn = document.getElementById('car-modal-save');
        if (!modal || !body) return;

        // Title from the card's data.
        const eqLabel = (window.PARSING_LANG && window.PARSING_LANG.btn_equipment) || 'Dotări';
        const card = document.querySelector('[data-car-id="' + carId + '"]');
        let titleTxt = eqLabel + ' OpenLane';
        if (card) {
            const d = card.dataset;
            const name = [d.brand, d.model].filter(Boolean).join(' ').trim();
            const parts = [];
            if (name) parts.push(name);
            if (d.year && d.year !== '0') parts.push(d.year);
            if (parts.length) titleTxt = eqLabel + ' ' + parts.join(' · ');
        }
        title.textContent = titleTxt;
        if (saveBtn) {
            const L = (k, fb) => (window.PARSING_LANG && window.PARSING_LANG[k]) || fb;
            saveBtn.style.display = 'none';
            saveBtn.classList.add('btn-pdf');
            saveBtn.textContent = L('btn_download_pdf', 'Descarcă PDF');
            saveBtn.onclick = function () { parsingReportToPdf(titleTxt); };
        }
        modal.style.display = 'flex';
        parsingRememberViewedCard(carId);
        modal.querySelector('.car-modal-content')?.classList.add('er-wide');
        document.body.style.overflow = 'hidden';

        const show = function (htmlStr) {
            body.innerHTML = '<div class="er-modal-wrap">' + (htmlStr || '<em>(fără date)</em>') + '</div>';
            if (saveBtn) saveBtn.style.display = '';
        };

        window._parsingReportCache = window._parsingReportCache || {};
        if (window._parsingReportCache['ol_' + carId]) {
            show(window._parsingReportCache['ol_' + carId]);
            return;
        }

        body.innerHTML = '<div class="er-loading"><span class="er-spinner"></span></div>';
        ajax('openlane_report', { car_id: carId }).then(res => {
            if (res && res.success) {
                window._parsingReportCache['ol_' + carId] = res.html || '';
                show(res.html);
            } else {
                body.innerHTML = '<div class="err">' + ((res && res.error) || 'Eroare la raport') + '</div>';
            }
        });
    };

    // eCarsTrade equipment report — same "Dotări" modal as OpenLane, fed by the
    // ecarstrade_report endpoint (equipment list scraped from the detail page).
    window.parsingEcarstradeReport = function (carId) {
        const modal = document.getElementById('parsing-car-modal');
        const body = document.getElementById('car-modal-body');
        const title = document.getElementById('car-modal-title');
        const saveBtn = document.getElementById('car-modal-save');
        if (!modal || !body) return;

        const eqLabel = (window.PARSING_LANG && window.PARSING_LANG.btn_equipment) || 'Dotări';
        const card = document.querySelector('[data-car-id="' + carId + '"]');
        let titleTxt = eqLabel + ' eCarsTrade';
        if (card) {
            const d = card.dataset;
            const name = [d.brand, d.model].filter(Boolean).join(' ').trim();
            const parts = [];
            if (name) parts.push(name);
            if (d.year && d.year !== '0') parts.push(d.year);
            if (parts.length) titleTxt = eqLabel + ' ' + parts.join(' · ');
        }
        title.textContent = titleTxt;
        if (saveBtn) {
            const L = (k, fb) => (window.PARSING_LANG && window.PARSING_LANG[k]) || fb;
            saveBtn.style.display = 'none';
            saveBtn.classList.add('btn-pdf');
            saveBtn.textContent = L('btn_download_pdf', 'Descarcă PDF');
            saveBtn.onclick = function () { parsingReportToPdf(titleTxt); };
        }
        modal.style.display = 'flex';
        parsingRememberViewedCard(carId);
        modal.querySelector('.car-modal-content')?.classList.add('er-wide');
        document.body.style.overflow = 'hidden';

        const show = function (htmlStr) {
            body.innerHTML = '<div class="er-modal-wrap">' + (htmlStr || '<em>(fără date)</em>') + '</div>';
            if (saveBtn) saveBtn.style.display = '';
        };

        window._parsingReportCache = window._parsingReportCache || {};
        if (window._parsingReportCache['ec_' + carId]) {
            show(window._parsingReportCache['ec_' + carId]);
            return;
        }

        body.innerHTML = '<div class="er-loading"><span class="er-spinner"></span></div>';
        ajax('ecarstrade_report', { car_id: carId }).then(res => {
            if (res && res.success) {
                window._parsingReportCache['ec_' + carId] = res.html || '';
                show(res.html);
            } else {
                body.innerHTML = '<div class="err">' + ((res && res.error) || 'Eroare la raport') + '</div>';
            }
        });
    };

    // Generate and download a PDF of the report directly (no print dialog), using
    // html2pdf.js. We capture the report wrapper that is ALREADY visible inside the
    // open modal — capturing a live, painted element is far more reliable than an
    // offscreen clone (which often rasterises blank). We temporarily add a title
    // and a white background, render, then restore.
    window.parsingReportToPdf = function (titleTxt) {
        const wrap = document.querySelector('#car-modal-body .er-modal-wrap');
        if (!wrap) { alert(L('report_not_loaded', 'Raportul nu este încărcat încă.')); return; }
        if (typeof html2pdf === 'undefined') { alert(L('pdf_lib_error', 'Librăria PDF nu s-a încărcat. Reîncarcă pagina.')); return; }
        const saveBtn = document.getElementById('car-modal-save');
        const oldLabel = saveBtn ? saveBtn.textContent : '';
        if (saveBtn) { saveBtn.textContent = L('pdf_generating', 'Se generează…'); saveBtn.disabled = true; }

        // Temporary document title at the top of the captured area.
        const titleEl = document.createElement('h1');
        titleEl.textContent = titleTxt || (L('btn_report', 'Raport') + ' Encar');
        titleEl.style.cssText = 'font:700 18px Arial,Helvetica,sans-serif;color:#1f2430;margin:0 0 16px;padding:0 4px;';
        wrap.insertBefore(titleEl, wrap.firstChild);
        const prevBg = wrap.style.background;
        wrap.style.background = '#fff';

        // Remove the inspection-photos section from the PDF entirely (kept in the
        // on-screen modal — re-inserted at its original spot in cleanup).
        const photoSection = wrap.querySelector('.er-section-photos')
                          || wrap.querySelector('.er-photos')?.closest('.er-section');
        let photoParent = null, photoAnchor = null;
        if (photoSection) {
            photoParent = photoSection.parentNode;
            photoAnchor = photoSection.nextSibling;       // remember exact position
            photoParent.removeChild(photoSection);
        }

        const fileName = (titleTxt || (L('btn_report', 'Raport') + ' Encar')).replace(/[^\wÀ-ɏ .·-]+/g, '').trim().replace(/\s+/g, '_') + '.pdf';
        const opt = {
            margin:      [8, 8, 8, 8],
            filename:    fileName,
            image:       { type: 'jpeg', quality: 0.98 },
            html2canvas: { scale: 2, useCORS: true, backgroundColor: '#ffffff', scrollX: 0, scrollY: 0 },
            jsPDF:       { orientation: 'portrait', unit: 'mm', format: 'a4' },
            pagebreak:   { mode: ['css', 'avoid-all'] }
        };
        const cleanup = function () {
            titleEl.remove();
            wrap.style.background = prevBg;
            // Put the photos section back exactly where it was.
            if (photoSection && photoParent) photoParent.insertBefore(photoSection, photoAnchor);
            if (saveBtn) { saveBtn.textContent = oldLabel; saveBtn.disabled = false; }
        };
        html2pdf().set(opt).from(wrap).save().then(cleanup).catch(cleanup);
    };

    // Characteristics: open modal with all car details.
    window.parsingShowCharacteristics = function (carId) {
        const modal = document.getElementById('parsing-car-modal');
        const body = document.getElementById('car-modal-body');
        const title = document.getElementById('car-modal-title');
        const saveBtn = document.getElementById('car-modal-save');
        if (!modal || !body) return;

        title.textContent = L('modal_characteristics_title', 'Caracteristici');
        saveBtn.style.display = 'none';
        body.innerHTML = '<div class="er-loading"><span class="er-spinner"></span></div>';
        modal.style.display = 'flex';
        parsingRememberViewedCard(carId);
        // Standard width for characteristics (report widens it; reset here).
        modal.querySelector('.car-modal-content')?.classList.remove('er-wide');
        document.body.style.overflow = 'hidden';

        // Show the stored specs immediately (fast). Any missing AI-only fields
        // (HP / drive_type / seats) are filled in afterwards, below, without
        // blocking the modal from appearing.
        ajax('get_car_details', { car_id: carId }).then(res => {
            if (!(res && res.success && res.car)) {
                body.innerHTML = '<div class="err">' + (res && res.error ? res.error : 'Eroare') + '</div>';
                return;
            }
            const c = res.car;
            const L = (k, fb) => (window.PARSING_LANG && window.PARSING_LANG[k]) || fb;

            // Map sauto colour codes + Korean leftovers to lang keys.
            const colorKey = {
                // sauto codes
                'blk': 'color_black', 'wht': 'color_white', 'slv': 'color_silver',
                'gra': 'color_gray', 'red': 'color_red', 'blu': 'color_blue',
                'azr': 'color_blue', 'brn': 'color_brown', 'bge': 'color_beige',
                'gld': 'color_gold', 'ylw': 'color_yellow', 'orn': 'color_orange',
                'grn': 'color_green', 'd_grn': 'color_darkgreen', 'l_grn': 'color_lightgreen',
                'prp': 'color_purple', 'pnk': 'color_pink', 'vns': 'color_wine',
                // Korean fallbacks (legacy data)
                '흰색': 'color_white', '검정': 'color_black', '검정색': 'color_black',
                '은색': 'color_silver', '회색': 'color_gray', '쥐색': 'color_darkgray',
                '빨간색': 'color_red', '파란색': 'color_blue', '갈색': 'color_brown',
                '베이지': 'color_beige', '금색': 'color_gold', '노란색': 'color_yellow',
                '주황색': 'color_orange', '녹색': 'color_green',
                '진한녹색': 'color_darkgreen', '연두색': 'color_lightgreen',
                '보라색': 'color_purple', '분홍색': 'color_pink', '와인색': 'color_wine',
            };
            const colorTxt = c.color ? (colorKey[c.color.trim()] ? L(colorKey[c.color.trim()], c.color) : c.color) : '';

            const driveMap = { '4x4': '4x4', 'fwd': L('opt_fwd', 'Față'), 'rwd': L('opt_rwd', 'Spate') };
            // Translate code values to localized labels.
            const fuelMap = {
                'benzina': L('opt_gasoline', 'Benzină'),
                'diesel':  L('opt_diesel', 'Diesel'),
                'lpg':     'LPG',
                'hybrid':  L('opt_hybrid', 'Hybrid (Benzină)'),
                'hybrid_plugin': L('opt_plugin_hybrid', 'Plug-in Hybrid'),
                'diesel_hybrid': L('opt_diesel_hybrid', 'Hybrid (Diesel)'),
                'gasoline_lpg':  L('opt_gasoline_lpg', 'Benzină+LPG'),
                'gasoline_cng':  L('opt_gasoline_cng', 'Benzină+CNG'),
                'electric': L('opt_electric', 'Electric'),
                'other':    L('opt_other', 'Altele'),
            };
            const gearMap = {
                'automat':   L('opt_automatic', 'Automat'),
                'manual':    L('opt_manual', 'Manual'),
                'semi-auto': L('opt_semi_auto', 'Semi-auto'),
                'cvt':       L('opt_cvt', 'CVT'),
            };
            const bodyMap = {
                'sedan':       L('body_sedan', 'Sedan'),
                'suv':         'SUV',
                'hatchback':   L('body_hatchback', 'Hatchback'),
                'wagon':       L('body_wagon', 'Universal'),
                'coupe':       L('body_coupe', 'Coupe'),
                'minivan':     L('body_minivan', 'Minivan'),
                'pickup':      L('body_pickup', 'Pickup'),
                'van':         L('body_van', 'Furgon'),
                'convertible': L('body_convertible', 'Cabriolet'),
                'microbus':    L('body_microbus', 'Microbus'),
                'crossover':   L('body_crossover', 'Crossover'),
            };
            const fuelTxt = c.fuel_type ? (fuelMap[c.fuel_type] || c.fuel_type) : '';
            const gearTxt = c.gearbox   ? (gearMap[c.gearbox]   || c.gearbox)   : '';
            const bodyTxt = c.body_type ? (bodyMap[c.body_type] || c.body_type) : '';

            const rows = [
                [L('lbl_brand', 'Marca'),       c.brand],
                [L('lbl_model', 'Model'),       c.model],
                [L('lbl_year', 'An'),           c.year],
                [L('lbl_km', 'Km'),             c.km ? Number(c.km).toLocaleString() + ' km' : ''],
                [L('lbl_fuel', 'Combustibil'),  fuelTxt],
                [L('lbl_gearbox', 'Cutie'),     gearTxt],
                [L('lbl_engine', 'Vol. motor'), c.engine_volume ? c.engine_volume + ' cc' : ''],
                [L('lbl_power', 'Putere'),      c.power_hp ? c.power_hp + ' hp' : ''],
                [L('lbl_seats', 'Nr. locuri'),  c.seats || ''],
                [L('lbl_drive', 'Tracțiune'),   driveMap[c.drive_type] || c.drive_type || ''],
                [L('lbl_color', 'Culoare'),     colorTxt],
                [L('lbl_body', 'Caroserie'),    bodyTxt],
                [L('lbl_vin', 'VIN'),           c.vin || ''],
                [L('lbl_price', 'Preț'),        c.price_final_eur ? Number(c.price_final_eur).toLocaleString() + ' €' : ''],
            ];
            const renderRows = (rowsArr) => {
                body.innerHTML = '<div class="char-grid">' + rowsArr.map(r =>
                    '<div class="char-row"><span class="char-label">' + r[0] + '</span><span class="char-value">' + (r[1] || '—') + '</span></div>'
                ).join('') + '</div>';
            };
            renderRows(rows);

            // Rebuild + render all rows from a fresh car object (after enrichment
            // pulls VIN/colour/body/engine into the DB). Reuses the same maps.
            const buildRows = (cc) => [
                [L('lbl_brand', 'Marca'),       cc.brand],
                [L('lbl_model', 'Model'),       cc.model],
                [L('lbl_year', 'An'),           cc.year],
                [L('lbl_km', 'Km'),             cc.km ? Number(cc.km).toLocaleString() + ' km' : ''],
                [L('lbl_fuel', 'Combustibil'),  cc.fuel_type ? (fuelMap[cc.fuel_type] || cc.fuel_type) : ''],
                [L('lbl_gearbox', 'Cutie'),     cc.gearbox ? (gearMap[cc.gearbox] || cc.gearbox) : ''],
                [L('lbl_engine', 'Vol. motor'), cc.engine_volume ? cc.engine_volume + ' cc' : ''],
                [L('lbl_power', 'Putere'),      cc.power_hp ? cc.power_hp + ' hp' : ''],
                [L('lbl_seats', 'Nr. locuri'),  cc.seats || ''],
                [L('lbl_drive', 'Tracțiune'),   driveMap[cc.drive_type] || cc.drive_type || ''],
                [L('lbl_color', 'Culoare'),     cc.color ? (colorKey[String(cc.color).trim()] ? L(colorKey[String(cc.color).trim()], cc.color) : cc.color) : ''],
                [L('lbl_body', 'Caroserie'),    cc.body_type ? (bodyMap[cc.body_type] || cc.body_type) : ''],
                [L('lbl_vin', 'VIN'),           cc.vin || ''],
                [L('lbl_price', 'Preț'),        cc.price_final_eur ? Number(cc.price_final_eur).toLocaleString() + ' €' : ''],
            ];
            window._parsingRenderSpecRows = (cc) => renderRows(buildRows(cc));

            const reverifySources = ['ecarstrade', 'openlane'];
            const needsEnrich = !c.power_hp || !c.drive_type || !c.seats
                || !c.vin || !c.color || !c.body_type || !c.engine_volume || !c.fuel_type || !c.gearbox
                || reverifySources.includes(c.source);
            if (needsEnrich && carId) {
                ajax('ai_enrich_specs', { car_id: carId }).then(aiRes => {
                    if (!(aiRes && aiRes.success)) return;
                    
                    applyMdInputs(carId, aiRes);
                    // Fill the AI-only fields straight from the response.
                    rows.forEach((r, i) => {
                        if (r[0] === L('lbl_power', 'Putere') && aiRes.hp && !r[1]) {
                            rows[i][1] = aiRes.hp + ' hp';
                        }
                        if (r[0] === L('lbl_drive', 'Tracțiune') && aiRes.drive_type && !r[1]) {
                            rows[i][1] = driveMap[aiRes.drive_type] || aiRes.drive_type;
                        }
                        if (r[0] === L('lbl_seats', 'Nr. locuri') && aiRes.seats && !r[1]) {
                            rows[i][1] = aiRes.seats;
                        }
                    });
                    renderRows(rows);
                    // enrichOnePublic also pulled VIN/colour/body/engine into the DB.
                    // Re-fetch the row and re-render so those show without reopening.
                    ajax('get_car_details', { car_id: carId }).then(res2 => {
                        if (res2 && res2.success && res2.car && typeof window._parsingRenderSpecRows === 'function') {
                            window._parsingRenderSpecRows(res2.car);
                        }
                    });
                });
            }
        });
    };

    // Remember which car's modal (characteristics/report) we last opened, so AFTER
    // closing the modal that card stays marked as "seen" — easy to tell which cars
    // you've already reviewed when many are on screen.
    let _parsingLastViewedCard = null;
    function parsingRememberViewedCard(carId) {
        _parsingLastViewedCard = carId;
    }
    function parsingMarkViewedCard() {
        if (_parsingLastViewedCard == null) return;
        // Only the LAST viewed car stays marked — clear any previous mark first.
        document.querySelectorAll('.car-card.is-viewed-card')
            .forEach(el => el.classList.remove('is-viewed-card'));
        const card = document.querySelector('[data-car-id="' + _parsingLastViewedCard + '"]');
        if (card) card.classList.add('is-viewed-card');
        _parsingLastViewedCard = null;
    }

    window.parsingCloseCarModal = function () {
        const modal = document.getElementById('parsing-car-modal');
        if (modal) modal.style.display = 'none';
        document.body.style.overflow = '';
        parsingMarkViewedCard();   // mark the card as seen AFTER closing
    };

    // Close on backdrop click + Esc key.
    document.addEventListener('click', function (e) {
        const modal = document.getElementById('parsing-car-modal');
        if (!modal || modal.style.display === 'none') return;
        if (e.target === modal) parsingCloseCarModal();
    });
    document.addEventListener('keydown', function (e) {
        if (e.key !== 'Escape') return;
        const modal = document.getElementById('parsing-car-modal');
        if (modal && modal.style.display !== 'none') parsingCloseCarModal();
    });

    let galleryImages = [];
    let galleryIndex = 0;

    // Encar serves tiny thumbnails by default; the same URL accepts an "impolicy"
    // resize. The big gallery uses 1280px (sharp, full-quality viewing). Card
    // thumbnails stay small; only the gallery upgrades them.
    function galleryHiRes(url) {
        if (!url || typeof url !== 'string') return url;
        if (url.indexOf('encar.com') === -1) return url;   // only Encar CDN
        if (url.indexOf('impolicy') !== -1) return url;     // already sized
        const sep = url.indexOf('?') === -1 ? '?' : '&';
        return url + sep + 'impolicy=heightRate&cw=1280&rh=854&ch=854&cg=Center';
    }

    // Load a gallery image at hi-res: show the spinner, hide the old image, then
    // reveal the sharp image once it's downloaded (or keep it instant if already
    // cached/preloaded). A request token guards against fast clicking (only the
    // latest requested index is shown).
    let _galToken = 0;
    function loadGalleryImage(idx) {
        const modal = document.getElementById('parsing-gallery-modal');
        const img = document.getElementById('gallery-image');
        const loading = document.getElementById('gallery-loading');
        const current = document.getElementById('gallery-current');
        if (!img || !galleryImages.length) return;
        // Clear any pinch-zoom from the previous photo.
        if (window._galZoomReset) window._galZoomReset();
        const src = galleryHiRes(galleryImages[idx]);
        if (current) current.textContent = String(idx + 1);
        const token = ++_galToken;
        // 'is-loading' on the modal hides the nav arrows via CSS while spinning.
        const done = function () { if (modal) modal.classList.remove('is-loading'); };

        const pre = new Image();
        pre.src = src;   // assigning src first lets a cached image be 'complete' immediately
        if (pre.complete && pre.naturalWidth) {
            // already cached/preloaded — show instantly, no spinner, arrows stay
            img.src = src; img.style.display = 'block';
            if (loading) loading.style.display = 'none';
            done();
            return;
        }
        // Not cached yet — show the spinner and hide the arrows until it downloads.
        if (modal) modal.classList.add('is-loading');
        if (loading) { loading.textContent = ''; loading.style.display = 'flex'; }
        img.style.display = 'none';
        pre.onload = function () {
            if (token !== _galToken) return;   // a newer navigation won
            img.src = src; img.style.display = 'block';
            if (loading) loading.style.display = 'none';
            done();
        };
        pre.onerror = function () {
            if (token !== _galToken) return;
            // Fall back to the original (un-resized) URL if the hi-res 404s.
            img.src = galleryImages[idx]; img.style.display = 'block';
            if (loading) loading.style.display = 'none';
            done();
        };
    }

    window.parsingOpenGallery = function (carId) {
        const modal = document.getElementById('parsing-gallery-modal');
        const loading = document.getElementById('gallery-loading');
        const img = document.getElementById('gallery-image');
        const total = document.getElementById('gallery-total');
        const current = document.getElementById('gallery-current');

        // data-gallery has up to 20 images; data-images has up to 10 (slider).
        const card = document.querySelector('[data-car-id="' + carId + '"] .car-slider');
        let preview = [];
        if (card) {
            try { preview = JSON.parse(card.dataset.gallery || card.dataset.images || '[]'); } catch (e) {}
        }
        galleryImages = preview;
        galleryIndex = 0;
        modal.style.display = 'flex';
        document.body.style.overflow = 'hidden';   // lock background scroll
        if (preview.length) {
            total.textContent = preview.length;
            current.textContent = '1';
            // Show a spinner while the sharp hi-res image downloads, then reveal
            // it (no low-quality flash). loadGalleryImage handles spinner + swap.
            loadGalleryImage(0);
            // Preload next 2 images (hi-res) for snappy navigation.
            if (preview[1]) new Image().src = galleryHiRes(preview[1]);
            if (preview[2]) new Image().src = galleryHiRes(preview[2]);
        } else {
            loading.style.display = 'flex';
            img.style.display = 'none';
        }

        // Always fetch the full photo set from the source on gallery open — the
        // import only saves a few preview thumbs, so this pulls every photo (no
        // cap). It replaces the preview once the complete list arrives.
        ajax('fetch_all_photos', { car_id: carId }).then(res => {
            if (res && res.success && res.images && res.images.length > preview.length) {
                galleryImages = res.images;   // all photos, no 20 cap
                galleryIndex = 0;
                total.textContent = galleryImages.length;
                loadGalleryImage(0);
                // Warm the next few images so navigating feels instant.
                for (let i = 1; i <= 4; i++) {
                    if (galleryImages[i]) new Image().src = galleryHiRes(galleryImages[i]);
                }
            } else if (!preview.length) {
                loading.textContent = (res && res.error) ? res.error : L('no_photos', 'Nu sunt poze');
            }
        });
    };

    window.parsingCloseGallery = function () {
        if (window._galZoomReset) window._galZoomReset();   // clear zoom
        document.getElementById('parsing-gallery-modal').style.display = 'none';
        document.body.style.overflow = '';   // restore background scroll
    };

    // Close the big gallery by tapping/clicking outside the image (on the modal
    // backdrop or the image wrapper). This replaces the hidden ✕ on mobile.
    (function () {
        const modal = document.getElementById('parsing-gallery-modal');
        if (!modal) return;
        modal.addEventListener('click', function (e) {
            // Don't close while/just after zooming or panning the photo.
            if (window._galZoomed) return;
            // Ignore clicks on the image itself or any control (arrows, close, counter).
            if (e.target.closest('#gallery-image, .gallery-nav, .gallery-close, .gallery-counter')) return;
            parsingCloseGallery();
        });
    })();

    window.parsingGalleryNav = function (direction) {
        if (!galleryImages.length) return;
        galleryIndex = (galleryIndex + direction + galleryImages.length) % galleryImages.length;
        loadGalleryImage(galleryIndex);

        // Preload neighbors for instant navigation (hi-res).
        const prev = (galleryIndex - 1 + galleryImages.length) % galleryImages.length;
        const next = (galleryIndex + 1) % galleryImages.length;
        new Image().src = galleryHiRes(galleryImages[prev]);
        new Image().src = galleryHiRes(galleryImages[next]);
    };

    document.addEventListener('keydown', function (e) {
        const modal = document.getElementById('parsing-gallery-modal');
        if (!modal || modal.style.display === 'none') return;
        if (e.key === 'Escape') parsingCloseGallery();
        if (e.key === 'ArrowLeft') parsingGalleryNav(-1);
        if (e.key === 'ArrowRight') parsingGalleryNav(1);
    });

    // ---------- Desktop arrow nav (single <img>, swap src) ----------
    window.parsingSliderNav = function (btnOrSlider, direction) {
        const slider = btnOrSlider && btnOrSlider.classList && btnOrSlider.classList.contains('car-slider')
            ? btnOrSlider
            : (btnOrSlider && btnOrSlider.closest ? btnOrSlider.closest('.car-slider') : null);
        if (!slider) return;
        // If this slider was upgraded to a real track (mobile), drive that instead.
        if (slider._pms) { slider._pms.go(direction); return; }
        let images;
        try { images = JSON.parse(slider.dataset.images || '[]'); } catch (e) { return; }
        if (!images.length) return;
        let index = parseInt(slider.dataset.index || '0', 10);
        index = (index + direction + images.length) % images.length;
        slider.dataset.index = String(index);
        const img = slider.querySelector('.car-cover');
        const counter = slider.querySelector('.slider-current');
        if (counter) counter.textContent = String(index + 1);
        if (img) img.src = images[index];
        const next = (index + direction + images.length) % images.length;
        if (images[next]) { new Image().src = images[next]; }
    };

    // ---------- Mobile card slider: real track you drag with your finger ----------
    // Builds a horizontal track of all photos so the next image slides in under the
    // finger (like the public site), instead of swapping a single <img> src.
    function initParsingMobileSliders() {
        // Use the real touch capability, not screen width — so it also works in
        // phone landscape (>768px) and on tablets (incl. iPadOS, which reports a
        // desktop UA). Cap at 1366px so big desktop touchscreens keep arrows.
        const hasTouch = ('ontouchstart' in window) || (navigator.maxTouchPoints > 0);
        const touch = (hasTouch && window.innerWidth <= 1366)
            || window.matchMedia('(max-width: 768px)').matches
            || /Mobile|Android|iPhone|iPad/.test(navigator.userAgent);
        if (!touch) return;
        document.querySelectorAll('.car-slider:not([data-pms])').forEach(slider => {
            let images = [];
            try { images = JSON.parse(slider.dataset.images || '[]'); } catch (e) { return; }
            if (!Array.isArray(images) || images.length < 2) return;
            slider.setAttribute('data-pms', '1');
            slider._pms = new PMSlider(slider, images);
        });
    }

    function PMSlider(slider, images) {
        this.slider = slider;
        this.images = images;
        this.total = images.length;
        this.index = parseInt(slider.dataset.index || '0', 10) || 0;
        this._build();
        this._bind();
        this._render(false);
    }
    PMSlider.prototype._build = function () {
        const cover = this.slider.querySelector('.car-cover');
        if (cover) cover.style.display = 'none';   // hide the original single image
        const track = document.createElement('div');
        track.className = 'pms-track';
        for (let i = 0; i < this.total; i++) {
            const slide = document.createElement('div');
            slide.className = 'pms-slide';
            const img = document.createElement('img');
            if (i === 0 || i === this.index) img.src = this.images[i];
            else img.dataset.src = this.images[i];
            slide.appendChild(img);
            track.appendChild(slide);
        }
        this.slider.insertBefore(track, this.slider.firstChild);
        this.track = track;
        // Reposition the counter/indicator markup above the track via z-index (CSS).
    };
    PMSlider.prototype._loadAround = function () {
        for (let d = -1; d <= 1; d++) {
            const slide = this.track.children[this.index + d];
            if (!slide) continue;
            const img = slide.querySelector('img[data-src]');
            if (img) { img.src = img.dataset.src; img.removeAttribute('data-src'); }
        }
    };
    PMSlider.prototype._render = function (animate) {
        this.track.style.transition = animate ? 'transform 0.28s cubic-bezier(0.22,0.61,0.36,1)' : 'none';
        this.track.style.transform = 'translateX(' + (-this.index * 100) + '%)';
        const cnt = this.slider.querySelector('.slider-current');
        if (cnt) cnt.textContent = String(this.index + 1);
        this._loadAround();
    };
    PMSlider.prototype.go = function (dir) {
        const ni = this.index + dir;
        if (ni < 0 || ni > this.total - 1) return;
        this.index = ni;
        this.slider.dataset.index = String(this.index);
        this._render(true);
    };
    PMSlider.prototype._bind = function () {
        const self = this, track = this.track;
        let sx = 0, sy = 0, axis = null, dragging = false, w = 1, lastDx = 0, t0 = 0, pid = null, didSwipe = false;
        const LOCK = 6, RATIO = 0.12, FLICK = 0.3;
        let watchdog = null;
        const reset = () => { dragging = false; axis = null; pid = null; if (watchdog) { clearTimeout(watchdog); watchdog = null; } };
        const findT = (e) => { for (let i = 0; i < e.changedTouches.length; i++) if (e.changedTouches[i].identifier === pid) return e.changedTouches[i]; return null; };

        track.addEventListener('touchstart', function (e) {
            if (dragging) { reset(); self._render(true); }
            const t = e.changedTouches[0];
            pid = t.identifier; sx = t.clientX; sy = t.clientY; t0 = Date.now();
            w = track.offsetWidth || 1; dragging = true; axis = null; lastDx = 0; didSwipe = false;
            if (watchdog) clearTimeout(watchdog);
            watchdog = setTimeout(() => { if (dragging) { reset(); self._render(true); } }, 1200);
        }, { passive: true });

        track.addEventListener('touchmove', function (e) {
            if (!dragging) return;
            const t = findT(e) || e.touches[0]; if (!t) return;
            const dx = t.clientX - sx, dy = t.clientY - sy;
            if (axis === null) {
                if (Math.abs(dx) > LOCK || Math.abs(dy) > LOCK) axis = (Math.abs(dx) * 1.15 >= Math.abs(dy)) ? 'x' : 'y';
                else return;
            }
            if (axis === 'y') return;
            let edx = dx;
            if (self.index === 0 && dx > 0) edx = dx * 0.35;
            else if (self.index === self.total - 1 && dx < 0) edx = dx * 0.35;
            lastDx = edx;
            track.style.transition = 'none';
            track.style.transform = 'translateX(' + (-self.index * 100 + (edx / w) * 100) + '%)';
            if (e.cancelable) e.preventDefault();
        }, { passive: false });

        track.addEventListener('touchend', function (e) {
            if (!dragging) return;
            if (pid !== null && !findT(e) && e.touches.length) return;
            const wasX = axis === 'x';
            if (!wasX) { reset(); self._render(true); return; }
            const threshold = w * RATIO;
            const elapsed = Math.max(1, Date.now() - t0);
            const flick = (Math.abs(lastDx) / elapsed) >= FLICK && Math.abs(lastDx) > 10;
            if ((lastDx <= -threshold || (flick && lastDx < 0)) && self.index < self.total - 1) { self.index++; didSwipe = true; }
            else if ((lastDx >= threshold || (flick && lastDx > 0)) && self.index > 0) { self.index--; didSwipe = true; }
            self.slider.dataset.index = String(self.index);
            self._render(true);
            reset();
            if (didSwipe && e.cancelable) e.preventDefault();
        }, { passive: false });

        track.addEventListener('touchcancel', function () { if (dragging) { reset(); self._render(true); } }, { passive: true });

        // Tap (not swipe) opens the big gallery.
        track.addEventListener('click', function () {
            if (didSwipe) { didSwipe = false; return; }
            const card = self.slider.closest('[data-car-id]');
            const id = card ? parseInt(card.getAttribute('data-car-id'), 10) : null;
            if (id && window.parsingOpenGallery) window.parsingOpenGallery(id);
        });
    };

    // ---------- Big gallery swipe (drag the image with the finger, iOS-safe) ----------
    // The current photo follows the finger; on release it either snaps back or
    // slides out as the next/prev one comes in — the same feel as the card slider.
    (function () {
        const modal = document.getElementById('parsing-gallery-modal');
        if (!modal) return;
        const LOCK = 6, RATIO = 0.14, FLICK = 0.3;
        let sx = 0, sy = 0, axis = null, dragging = false, w = 1, lastDx = 0, t0 = 0, pid = null, didSwipe = false;
        let watchdog = null;

        const imgEl = () => document.getElementById('gallery-image');
        const reset = () => { dragging = false; axis = null; pid = null; if (watchdog) { clearTimeout(watchdog); watchdog = null; } };
        // Let the zoom module cancel an in-progress swipe (so a double-tap zoom
        // isn't wiped by this module's touchend transform reset).
        window._galSwipeCancel = reset;
        const findT = (e) => { for (let i = 0; i < e.changedTouches.length; i++) if (e.changedTouches[i].identifier === pid) return e.changedTouches[i]; return null; };

        modal.addEventListener('touchstart', function (e) {
            // Single-finger only, and not while the photo is pinch-zoomed (then the
            // gesture is a pan handled by the zoom module below).
            if (e.touches.length !== 1 || window._galZoomed || !e.target.closest('.gallery-img-wrap')) { reset(); return; }
            const t = e.changedTouches[0];
            pid = t.identifier; sx = t.clientX; sy = t.clientY; t0 = Date.now();
            w = (modal.querySelector('.gallery-img-wrap') || modal).offsetWidth || 1;
            dragging = true; axis = null; lastDx = 0; didSwipe = false;
            const img = imgEl(); if (img) img.style.transition = 'none';
            if (watchdog) clearTimeout(watchdog);
            watchdog = setTimeout(() => { if (dragging) { reset(); const i = imgEl(); if (i) { i.style.transition = 'transform 0.2s ease'; i.style.transform = ''; } } }, 1200);
        }, { passive: true });

        modal.addEventListener('touchmove', function (e) {
            if (!dragging) return;
            const t = findT(e) || e.touches[0]; if (!t) return;
            const dx = t.clientX - sx, dy = t.clientY - sy;
            if (axis === null) {
                if (Math.abs(dx) > LOCK || Math.abs(dy) > LOCK) axis = (Math.abs(dx) * 1.15 >= Math.abs(dy)) ? 'x' : 'y';
                else return;
            }
            if (axis === 'y') return;
            lastDx = dx;
            const img = imgEl();
            if (img) img.style.transform = 'translateX(' + dx + 'px)';
            if (e.cancelable) e.preventDefault();
        }, { passive: false });

        modal.addEventListener('touchend', function (e) {
            if (!dragging) return;
            if (pid !== null && !findT(e) && e.touches.length) return;
            const wasX = axis === 'x', dx = lastDx, wd = w;
            const elapsed = Math.max(1, Date.now() - t0);
            const flick = (Math.abs(dx) / elapsed) >= FLICK && Math.abs(dx) > 12;
            reset();
            const img = imgEl();
            if (!wasX) { if (img) { img.style.transition = 'transform 0.2s ease'; img.style.transform = ''; } return; }

            const committed = Math.abs(dx) > wd * RATIO || flick;
            if (!committed) {
                if (img) { img.style.transition = 'transform 0.2s ease'; img.style.transform = ''; }
                return;
            }
            const dir = dx < 0 ? 1 : -1;
            // Slide the current image fully out, then load the new one (which slides in).
            if (img) {
                img.style.transition = 'transform 0.18s ease';
                img.style.transform = 'translateX(' + (dir > 0 ? -wd : wd) + 'px)';
                setTimeout(function () {
                    img.style.transition = 'none';
                    img.style.transform = '';   // loadGalleryImage will set the new src
                    parsingGalleryNav(dir);
                }, 170);
            } else {
                parsingGalleryNav(dir);
            }
            if (e.cancelable) e.preventDefault();
        }, { passive: false });

        modal.addEventListener('touchcancel', function () {
            if (!dragging) return; reset();
            const img = imgEl(); if (img) { img.style.transition = 'transform 0.2s ease'; img.style.transform = ''; }
        }, { passive: true });
    })();

    // ---------- Big gallery pinch-zoom + pan (mobile) ----------
    // Two fingers zoom the photo; one finger pans while zoomed; double-tap toggles.
    // Works even with user-scalable=no (we do the scaling ourselves). While zoomed,
    // window._galZoomed is true so the swipe module above yields to panning.
    (function () {
        const modal = document.getElementById('parsing-gallery-modal');
        if (!modal) return;
        const imgEl = () => document.getElementById('gallery-image');

        let scale = 1, tx = 0, ty = 0;            // current transform
        let startDist = 0, startScale = 1;        // pinch baseline
        let panX = 0, panY = 0, startTx = 0, startTy = 0; // pan baseline
        let mode = null;                           // 'pinch' | 'pan' | null
        let lastTap = 0;

        const MAX = 4, MIN = 1;

        function apply(animate) {
            const img = imgEl(); if (!img) return;
            img.style.transition = animate ? 'transform 0.2s ease' : 'none';
            img.style.transform = `translate(${tx}px, ${ty}px) scale(${scale})`;
            window._galZoomed = scale > 1.01;
            // Grab cursor (desktop) when zoomed, so it's clear the photo can be dragged.
            img.style.cursor = window._galZoomed ? 'grab' : '';
        }
        function reset(animate) {
            scale = 1; tx = 0; ty = 0; window._galZoomed = false; apply(animate);
        }
        // Expose so loadGalleryImage / close can clear the zoom on photo change.
        window._galZoomReset = function () { reset(false); };

        const dist = (a, b) => Math.hypot(a.clientX - b.clientX, a.clientY - b.clientY);
        const mid  = (a, b) => ({ x: (a.clientX + b.clientX) / 2, y: (a.clientY + b.clientY) / 2 });

        modal.addEventListener('touchstart', function (e) {
            if (!e.target.closest('.gallery-img-wrap')) return;
            if (e.touches.length === 2) {
                mode = 'pinch';
                startDist = dist(e.touches[0], e.touches[1]);
                startScale = scale;
                if (e.cancelable) e.preventDefault();
            } else if (e.touches.length === 1) {
                // Double-tap handling first, so it works even while zoomed in.
                const now = Date.now();
                if (now - lastTap < 300) {
                    // Double-tap toggles: zoom IN when at normal size, back to normal
                    // when already zoomed. Cancel the swipe module's in-progress
                    // gesture first, otherwise its touchend wipes the zoom transform.
                    if (typeof window._galSwipeCancel === 'function') window._galSwipeCancel();
                    if (scale > 1.01) reset(true);
                    else { scale = 2.2; apply(true); }
                    lastTap = 0;
                    mode = null;   // cancel any pan from this tap
                    if (e.cancelable) e.preventDefault();
                    return;
                }
                lastTap = now;
                // Single finger while zoomed → pan the photo.
                if (scale > 1.01) {
                    mode = 'pan';
                    panX = e.touches[0].clientX; panY = e.touches[0].clientY;
                    startTx = tx; startTy = ty;
                }
            }
        }, { passive: false });

        modal.addEventListener('touchmove', function (e) {
            if (mode === 'pinch' && e.touches.length === 2) {
                const d = dist(e.touches[0], e.touches[1]);
                scale = Math.min(MAX, Math.max(MIN, startScale * (d / startDist)));
                apply(false);
                if (e.cancelable) e.preventDefault();
            } else if (mode === 'pan' && e.touches.length === 1) {
                tx = startTx + (e.touches[0].clientX - panX);
                ty = startTy + (e.touches[0].clientY - panY);
                apply(false);
                if (e.cancelable) e.preventDefault();
            }
        }, { passive: false });

        modal.addEventListener('touchend', function (e) {
            if (mode === 'pinch') {
                if (scale <= 1.01) reset(true);
                else clampPan();
            }
            if (e.touches.length === 0) mode = null;
            else if (e.touches.length === 1 && scale > 1.01) {
                // A finger lifted from a pinch — continue panning with the remaining one.
                mode = 'pan';
                panX = e.touches[0].clientX; panY = e.touches[0].clientY;
                startTx = tx; startTy = ty;
            }
        }, { passive: true });

        // Keep the zoomed image within reasonable bounds so it can't be lost off-screen.
        function clampPan() {
            const img = imgEl(); if (!img) return;
            const r = img.getBoundingClientRect();
            const overX = Math.max(0, (r.width - window.innerWidth) / 2 + 40);
            const overY = Math.max(0, (r.height - window.innerHeight) / 2 + 40);
            tx = Math.max(-overX, Math.min(overX, tx));
            ty = Math.max(-overY, Math.min(overY, ty));
            apply(true);
        }

        // ---------- Desktop: double-click toggle + mouse-wheel zoom ----------
        // Zoom toward a screen point (cx,cy) by changing scale from old→new, so the
        // pixel under the cursor stays put.
        function zoomToPoint(newScale, cx, cy) {
            newScale = Math.min(MAX, Math.max(MIN, newScale));
            const img = imgEl(); if (!img) return;
            const r = img.getBoundingClientRect();
            const ox = r.left + r.width / 2;   // image centre on screen
            const oy = r.top + r.height / 2;
            // Cursor offset from centre, in image-local (pre-scale) units.
            const lx = (cx - ox) / scale;
            const ly = (cy - oy) / scale;
            // Keep that local point under the cursor after the scale change.
            tx += -lx * (newScale - scale);
            ty += -ly * (newScale - scale);
            scale = newScale;
            if (scale <= 1.01) { reset(true); } else { apply(true); clampPan(); }
        }

        // Double-click: zoom IN at the cursor when normal, back to normal when zoomed.
        modal.addEventListener('dblclick', function (e) {
            if (!e.target.closest('.gallery-img-wrap')) return;
            e.preventDefault();
            if (typeof window._galSwipeCancel === 'function') window._galSwipeCancel();
            if (scale > 1.01) reset(true);
            else zoomToPoint(2.2, e.clientX, e.clientY);
        });

        // Mouse wheel: smooth zoom in/out toward the cursor.
        modal.addEventListener('wheel', function (e) {
            if (!e.target.closest('.gallery-img-wrap')) return;
            e.preventDefault();
            const factor = e.deltaY < 0 ? 1.15 : 1 / 1.15;   // up = in, down = out
            zoomToPoint(scale * factor, e.clientX, e.clientY);
        }, { passive: false });

        // Mouse drag to pan while zoomed in (see car details up close).
        let mDown = false, mStartX = 0, mStartY = 0, mStartTx = 0, mStartTy = 0;
        modal.addEventListener('mousedown', function (e) {
            if (scale <= 1.01 || !e.target.closest('.gallery-img-wrap')) return;
            e.preventDefault();
            mDown = true;
            mStartX = e.clientX; mStartY = e.clientY;
            mStartTx = tx; mStartTy = ty;
            const img = imgEl(); if (img) { img.style.transition = 'none'; img.style.cursor = 'grabbing'; }
        });
        window.addEventListener('mousemove', function (e) {
            if (!mDown) return;
            tx = mStartTx + (e.clientX - mStartX);
            ty = mStartTy + (e.clientY - mStartY);
            apply(false);
        });
        window.addEventListener('mouseup', function () {
            if (!mDown) return;
            mDown = false;
            const img = imgEl(); if (img) img.style.cursor = '';
            clampPan();
        });
    })();

    window.parsingRedownloadImages = function (carId) {
        const btn = document.querySelector('[data-car-id="' + carId + '"] .btn-redownload');
        if (btn) btn.textContent = '…';
        ajax('redownload_images', { car_id: carId }).then(res => {
            if (res && res.success && res.count > 0) {
                location.reload();
            } else {
                if (btn) btn.textContent = '↺';
                errorAlert(res && res.error ? res : { error: L('no_image_downloaded', 'Nicio imagine descărcată') });
            }
        });
    };

    window.parsingRemovePublished = function (carId) {
        ajax('remove_published', { car_id: carId }).then(res => {
            if (res && res.success) location.reload();
        });
    };

    // ---------- Direct link ----------
    window.parsingFetchByLink = function (e) {
        e.preventDefault();
        const url = e.target.url.value.trim();
        const result = document.getElementById('parsing-link-result');

        result.className = 'source-search-result';
        result.innerHTML = '';
        parsingShowOverlay();

        ajax('fetch_by_link', { url }).then(res => {
            parsingHideOverlay();
            if (res && res.success) {
                const adm = window.ADMIN_DIR || 'adm';

                // Car is already in the catalog — point the operator to it instead
                // of pretending we added a new one.
                if (res.result === 'duplicate') {
                    const ex = res.existing || {};
                    let href, linkLabel;
                    // #car-<id> lets the target catalog scroll to + highlight the card.
                    if (ex.car_ctlg_id) {
                        // Already published to sauto → it lives on the parsing
                        // "Published" page; jump there and highlight it (by parsing id).
                        href = '/' + adm + '/parsing/published#car-' + ex.id;
                        linkLabel = L('go_to_published', 'Vezi în deja publicate pe sauto.md');
                    } else if (ex.is_favorite) {
                        href = '/' + adm + '/parsing/favorites#car-' + ex.id;
                        linkLabel = L('go_to_favorites', 'Vezi la favorite');
                    } else {
                        href = '/' + adm + '/parsing/ctlg#car-' + ex.id;
                        linkLabel = L('see_proposed', 'Vezi catalogul');
                    }
                    const title = ex.title ? ' (' + ex.title + ')' : '';
                    result.className = 'source-search-result show warn';
                    result.innerHTML = '<span class="ssr-count"><strong>' +
                        L('link_already_exists', 'Mașina este deja în catalog') + '</strong>' + title + '</span>' +
                        '<a class="btn-see-catalog" href="' + href + '">' + linkLabel + '</a>';
                    e.target.reset();
                    return;
                }

                // Freshly added — link straight to the new car so #car-<id> scrolls
                // to it and rings it (it's otherwise lost among the price-sorted cars).
                const newId = res.existing && res.existing.id;
                const okHref = '/' + adm + '/parsing/ctlg' + (newId ? '#car-' + newId : '');
                result.className = 'source-search-result show ok';
                result.innerHTML = '<span class="ssr-count"><strong>' + L('link_result_ok') + '</strong></span>' +
                    '<a class="btn-see-catalog" href="' + okHref + '">' + L('see_proposed', 'Vezi catalogul') + '</a>';
                e.target.reset();
            } else {
                result.className = 'source-search-result show err';
                result.textContent = (res && res.error) ? res.error : L('link_result_error');
            }
        }).catch(function () { parsingHideOverlay(); });
    };

    // ---------- Settings ----------
    window.parsingSaveSettings = function (e) {
        e.preventDefault();
        const fd = new FormData(e.target);
        const data = {};
        fd.forEach((v, k) => { data[k] = v; });
        ['default_target_999', 'default_target_facebook', 'default_target_telegram', 'libretranslate_enabled']
            .forEach(k => { if (!(k in data)) data[k] = '0'; });
        ajax('save_settings', data).then(res => {
            if (res && res.success) alert(L('settings_saved'));
            else errorAlert(res);
        });
    };

    // Wipe the catalog (keeps published cars). Requires a 4-digit PIN — it's
    // destructive. The PIN is verified server-side too.
    window.parsingClearCatalog = function () {
        ajax('clear_catalog', {}).then(res => {
            if (res && res.success) {
                location.reload();
            } else {
                errorAlert(res);
            }
        });
    };

    // --- OpenLane cookie refresh (settings page) ---
    window.parsingOpenlaneSaveCookie = function () {
        const cookie = (document.getElementById('ol-cookie-input') || {}).value || '';
        const rvt = (document.getElementById('ol-rvt-input') || {}).value || '';
        if (!cookie.trim()) { alert(L('cookie_paste_first', 'Lipește cookie-ul întâi.')); return; }
        const body = new FormData();
        body.append('action', 'openlane_save_cookie');
        body.append('cookie', cookie.trim());
        body.append('rvt', rvt.trim());
        fetch('/ajax.php?tp=adm&pg=parsing', { method: 'POST', body, credentials: 'same-origin' })
            .then(r => r.json())
            .then(res => {
                if (res && res.success) {
                    const box = document.getElementById('ol-cookie-status');
                    if (box) { box.textContent = L('cookie_saved', '✓ Cookie salvat'); box.className = 'ol-cookie-status ok'; }
                    parsingOpenlaneCheckCookie(true);
                } else { alert((res && res.error) || L('save_error', 'Eroare la salvare')); }
            }).catch(() => alert(L('network_error', 'Eroare rețea')));
    };

    window.parsingOpenlaneCheckCookie = function (afterSave) {
        const box = document.getElementById('ol-cookie-status');
        if (box && !afterSave) { box.textContent = L('cookie_checking', 'Se verifică…'); box.className = 'ol-cookie-status'; }
        ajax('openlane_check_cookie', {}).then(res => {
            if (!box) return;
            const cannotTest = res && !res.logged_in && res.error && /de testat/i.test(res.error);
            if (cannotTest) {
                if (!afterSave) { box.textContent = L('cookie_saved', '✓ Cookie salvat'); box.className = 'ol-cookie-status ok'; }
                return;
            }
            if (res && res.success && res.logged_in) {
                box.textContent = L('cookie_valid_vin', '✓ Cookie valid — VIN: ') + (res.vin || '');
                box.className = 'ol-cookie-status ok';
            } else {
                box.textContent = L('cookie_expired_vin', '✗ Cookie expirat / VIN ascuns') + (res && res.error ? ' (' + res.error + ')' : '');
                box.className = 'ol-cookie-status bad';
            }
        }).catch(() => { if (box && !afterSave) { box.textContent = L('cookie_check_error', 'Eroare verificare'); box.className = 'ol-cookie-status bad'; } });
    };
    // --- eCarsTrade cookie refresh (settings page) ---
    window.parsingEcarstradeSaveCookie = function () {
        const cookie = (document.getElementById('ec-cookie-input') || {}).value || '';
        if (!cookie.trim()) { alert(L('cookie_paste_first', 'Lipește cookie-ul întâi.')); return; }
        const body = new FormData();
        body.append('action', 'ecarstrade_save_cookie');
        body.append('cookie', cookie.trim());
        fetch('/ajax.php?tp=adm&pg=parsing', { method: 'POST', body, credentials: 'same-origin' })
            .then(r => r.json())
            .then(res => {
                if (res && res.success) {
                    const box = document.getElementById('ec-cookie-status');
                    if (box) { box.textContent = L('cookie_saved', '✓ Cookie salvat'); box.className = 'ol-cookie-status ok'; }
                    // Try a VIN check too, but only let it OVERRIDE the "saved" note
                    // when it can actually test (there is an eCarsTrade car). A
                    // "nothing to test" result keeps the green "saved" confirmation.
                    parsingEcarstradeCheckCookie(true);
                } else { alert((res && res.error) || L('save_error', 'Eroare la salvare')); }
            }).catch(() => alert(L('network_error', 'Eroare rețea')));
    };

    window.parsingEcarstradeCheckCookie = function (afterSave) {
        const box = document.getElementById('ec-cookie-status');
        if (box && !afterSave) { box.textContent = L('cookie_checking', 'Se verifică…'); box.className = 'ol-cookie-status'; }
        ajax('ecarstrade_check_cookie', {}).then(res => {
            if (!box) return;
            // No car to test against: this isn't a cookie problem. After a save,
            // keep the green "saved" note instead of flashing a scary error.
            const cannotTest = res && !res.logged_in && res.error && /de testat/i.test(res.error);
            if (cannotTest) {
                if (!afterSave) { box.textContent = L('cookie_saved', '✓ Cookie salvat'); box.className = 'ol-cookie-status ok'; }
                return;
            }
            if (res && res.success && res.logged_in) {
                box.textContent = L('cookie_valid_vin', '✓ Cookie valid — VIN: ') + (res.vin || '');
                box.className = 'ol-cookie-status ok';
            } else {
                box.textContent = L('cookie_expired_vin', '✗ Cookie expirat / VIN ascuns') + (res && res.error ? ' (' + res.error + ')' : '');
                box.className = 'ol-cookie-status bad';
            }
        }).catch(() => { if (box && !afterSave) { box.textContent = L('cookie_check_error', 'Eroare verificare'); box.className = 'ol-cookie-status bad'; } });
    };

    // Auto-check on settings page load.
    document.addEventListener('DOMContentLoaded', function () {
        if (document.getElementById('ol-cookie-status')) parsingOpenlaneCheckCookie();
        if (document.getElementById('ec-cookie-status')) parsingEcarstradeCheckCookie();
    });

    // Filter page: show a "cookie expired" badge over the OpenLane / eCarsTrade
    // source panels when their session no longer returns a VIN, so the operator
    // knows to renew it in Settings. Silent (no status boxes here).
    function parsingCheckCookieWarn(source, action) {
        const warn = document.getElementById('cookie-warn-' + source);
        if (!warn) return;
        ajax(action, {}).then(res => {
            // "No car to test" is NOT an expired cookie — don't flag it. Only show
            // the badge when the check actually ran and the VIN came back hidden
            // (cookie expired). Mirrors the Settings page behaviour.
            const cannotTest = res && res.error && /de testat/i.test(res.error);
            const expired = res && res.success && res.logged_in === false && !cannotTest;
            warn.style.display = expired ? '' : 'none';
        }).catch(() => { warn.style.display = 'none'; });
    }
    document.addEventListener('DOMContentLoaded', function () {
        if (document.getElementById('cookie-warn-openlane'))   parsingCheckCookieWarn('openlane', 'openlane_check_cookie');
        if (document.getElementById('cookie-warn-ecarstrade')) parsingCheckCookieWarn('ecarstrade', 'ecarstrade_check_cookie');
    });

    window.parsingEditCountryPrices = function (countryCode) {
        alert('Editor: ' + countryCode);
    };

    // Save one pricing section (its own form): commission, delivery, eu_params
    // or kr_params. Each section has its own Save button; only that form's
    // fields are submitted, tagged with data-section.
    window.parsingSaveEu = function (e) {
        e.preventDefault();
        const form = e.target;
        const section = form.getAttribute('data-section') || '';
        const fd = new FormData(form);
        const data = { section: section };
        fd.forEach((v, k) => { data[k] = v; });
        // Unchecked checkboxes are absent from FormData — force enabled=0 for each
        // Europe (param_) and Korea (kr_) param that has an amount field.
        form.querySelectorAll('input[name$="_amount"]').forEach(inp => {
            const m = inp.name.match(/^(param|kr)_(\d+)_amount$/);
            if (m) {
                const flag = m[1] + '_' + m[2] + '_enabled';
                if (!(flag in data)) data[flag] = '0';
            }
        });
        // Tier rows queued for deletion (only inside this form).
        delete data['ctier_delete[]'];
        delete data['tier_delete[]'];
        const deletes = { ctier_delete: [], tier_delete: [] };
        form.querySelectorAll('input[data-delete]').forEach(inp => {
            const base = inp.name.replace('[]', '');
            if (deletes[base]) deletes[base].push(inp.value);
        });
        if (deletes.ctier_delete.length) data.ctier_delete = deletes.ctier_delete;
        if (deletes.tier_delete.length) data.tier_delete = deletes.tier_delete;

        const hasTierChanges = deletes.ctier_delete.length || deletes.tier_delete.length ||
            form.querySelector('input[name*="_new"]');
        ajax('save_eu_config', data).then(res => {
            if (res && res.success) {
                alert(L('eu_saved', 'Saved.'));
                // Reload only when rows were added/removed, so new rows pick up
                // their real DB ids. Plain edits don't need a reload.
                if (hasTierChanges) location.reload();
            } else {
                errorAlert(res);
            }
        });
    };

    // Track new-row indexes per table so each added row gets a unique name.
    let tierNewCounter = 0;

    // Add an empty tier row to a commission/delivery table. New rows use the
    // name pattern <prefix>_new<N>_<field> so the backend INSERTs them.
    window.parsingTierAdd = function (tableId) {
        const table = document.getElementById(tableId);
        if (!table) return;
        const prefix = table.getAttribute('data-prefix');
        const tbody = table.querySelector('tbody');
        const amountField = (prefix === 'ctier') ? 'commission' : (prefix === 'kmtier') ? 'markup' : 'delivery';
        const n = 'new' + (tierNewCounter++);
        const tr = document.createElement('tr');
        tr.setAttribute('data-row', '');
        tr.innerHTML =
            '<td><input type="number" step="1" min="0" class="int-only" name="' + prefix + '_' + n + '_price_from" value="0"></td>' +
            '<td><input type="number" step="1" min="0" class="int-only" name="' + prefix + '_' + n + '_price_to" value=""></td>' +
            '<td><input type="number" step="1" min="0" class="int-only" name="' + prefix + '_' + n + '_' + amountField + '" value="0"></td>' +
            '<td><button type="button" class="btn-icon" onclick="parsingTierRemove(this)">🗑</button></td>';
        tbody.appendChild(tr);
    };

    // Remove a tier row. If it maps to an existing DB row, queue its id for
    // deletion via a hidden <prefix>_delete[] field; new rows just drop out.
    window.parsingTierRemove = function (btn) {
        const tr = btn.closest('tr');
        const table = btn.closest('table');
        if (!tr || !table) return;
        const prefix = table.getAttribute('data-prefix');
        const firstInput = tr.querySelector('input[name]');
        const m = firstInput ? firstInput.name.match(new RegExp('^' + prefix + '_(\\d+)_')) : null;
        if (m) {
            const hidden = document.createElement('input');
            hidden.type = 'hidden';
            hidden.name = prefix + '_delete[]';
            hidden.value = m[1];
            hidden.setAttribute('data-delete', '');
            table.parentNode.insertBefore(hidden, table);
        }
        tr.remove();
    };

    document.addEventListener('keydown', function (e) {
        const el = e.target;
        if (!el.classList || !el.classList.contains('int-only')) return;
        if (['.', ',', 'e', 'E', '+', '-'].includes(e.key)) {
            e.preventDefault();
        }
    });

    document.addEventListener('input', function (e) {
        const el = e.target;
        if (!el.classList || !el.classList.contains('int-only')) return;
        if (/[.,]/.test(el.value)) {
            el.value = el.value.split(/[.,]/)[0].replace(/[^\d]/g, '');
        }
    });

    // ---------- Shared catalog filter (ctlg / published / favorites) ----------
    function initCatalogFilter() {
        const bar = document.getElementById('parsing-catalog-filter');
        if (!bar) return;

        // Toggle button (in source-subtabs / header) shows/hides the filter bar.
        const toggleBtn = document.getElementById('pcf-toggle');
        if (toggleBtn) {
            // Reflect the initial open state (bar is shown when a filter is active).
            toggleBtn.classList.toggle('active', bar.style.display !== 'none');
            toggleBtn.addEventListener('click', function () {
                const open = bar.style.display !== 'none';
                bar.style.display = open ? 'none' : '';
                toggleBtn.classList.toggle('active', !open);
            });
        }

        // Server-side filter: the bar is a <form method="get"> that reloads the
        // page with f_* params, so it searches the WHOLE DB (not just loaded cards).
        // Here we only keep the model dropdown in sync when the brand changes.
        const brandSel = document.getElementById('pcf-brand');
        const modelSel = document.getElementById('pcf-model');
        if (!brandSel || !modelSel) return;

        let modelsByBrand = {};
        try { modelsByBrand = JSON.parse(bar.getAttribute('data-models') || '{}'); } catch (e) {}

        const modelDefText = modelSel.options[0] ? modelSel.options[0].textContent : 'Model';

        brandSel.addEventListener('change', function () {
            const brand = brandSel.value;
            modelSel.innerHTML = '<option value="">' + modelDefText + '</option>';
            if (brand && modelsByBrand[brand]) {
                const models = modelsByBrand[brand];
                Object.keys(models).sort().forEach(function (m) {
                    const o = document.createElement('option');
                    o.value = m;
                    // data-models maps model -> car count; show it as "Model (N)".
                    const cnt = models[m];
                    o.textContent = (typeof cnt === 'number' && cnt > 0)
                        ? m + ' (' + cnt.toLocaleString('ro-RO') + ')'
                        : m;
                    modelSel.appendChild(o);
                });
                modelSel.disabled = false;
            } else {
                modelSel.disabled = true;
            }
        });
    }

    // ---------- Landed-cost ("MD: price") computation ----------
    // Mirrors /calculator/calc. Data (excise grid, settings, tiers, fixed
    // costs) comes from window.PARSING_PRICING, injected by the page.
    // Korea (encar) base for customs = car + sea freight (RoRo); Europe base =
    // car + delivery tier. Returns landed cost in EUR or null if not computable.

    function pricingData() {
        return window.PARSING_PRICING || null;
    }

    // Pick the tier whose [price_from, price_to] contains priceEur. price_to
    // null/empty = no upper limit. Returns the tier's `field` value or 0.
    function tierValue(tiers, priceEur, field) {
        if (!Array.isArray(tiers)) return 0;
        for (const tr of tiers) {
            const from = parseFloat(tr.price_from) || 0;
            const toRaw = tr.price_to;
            const to = (toRaw === null || toRaw === '' || toRaw === undefined) ? Infinity : parseFloat(toRaw);
            if (priceEur >= from && priceEur <= to) return parseFloat(tr[field]) || 0;
        }
        return 0;
    }

    function findExciseRate(rates, fuelType, capacity, age) {
        if (!Array.isArray(rates)) return 0;
        for (const r of rates) {
            if (r.fuel_type !== fuelType) continue;
            const capMin = parseInt(r.capacity_min, 10);
            const capMax = parseInt(r.capacity_max, 10);
            const ageMin = parseInt(r.age_min, 10);
            const ageMax = parseInt(r.age_max, 10);
            const capMatch = capacity >= capMin && (capMax === 0 || capacity <= capMax);
            const ageMatch = age >= ageMin && (ageMax === 0 || age <= ageMax);
            if (capMatch && ageMatch) return parseFloat(r.rate) || 0;
        }
        return 0;
    }

    // Customs cost in EUR for a given customs base (price + transport, in EUR).
    // Mirrors the /calc formula: excise (grid) + luxury excise + 0.4% fee.
    function customsEur(P, baseEur, priceEur, fuelType, capacity, year) {
        const rate = P.eur_rate || 19.5;       // MDL per EUR
        const valueMdl = baseEur * rate;
        const age = year ? (new Date().getFullYear() - year) : 0;

        // Excise (grid is MDL per cm3). Electric => 0.
        let exciseMdl = 0;
        const fuel = (fuelType || '').toLowerCase();
        if (fuel !== 'electric' && capacity > 0) {
            const discPlugin = P.hybrid_discount_plugin || 0;
            const discFull   = P.hybrid_discount_full || 0;
            if (fuel === 'diesel' || fuel === 'benzina' || fuel === 'gasoline' || fuel === 'petrol') {
                const baseFuel = (fuel === 'gasoline' || fuel === 'petrol') ? 'benzina' : fuel;
                exciseMdl = findExciseRate(P.excise_rates, baseFuel, capacity, age) * capacity;
            } else if (fuel === 'hybrid') {
                // Full hybrid on petrol → full discount.
                exciseMdl = findExciseRate(P.excise_rates, 'benzina', capacity, age) * capacity * (1 - discFull / 100);
            } else if (fuel === 'hybrid_plugin') {
                // Plug-in hybrid on petrol → plug-in discount.
                exciseMdl = findExciseRate(P.excise_rates, 'benzina', capacity, age) * capacity * (1 - discPlugin / 100);
            } else if (fuel === 'diesel_hybrid') {
                // Plug-in hybrid on diesel → diesel base, plug-in discount.
                exciseMdl = findExciseRate(P.excise_rates, 'diesel', capacity, age) * capacity * (1 - discPlugin / 100);
            }
        }

        // Luxury excise: 2%–10% of customs value over thresholds (MDL).
        let luxuryMdl = 0;
        const v = valueMdl;
        if (v >= 600000 && v <= 700000) luxuryMdl = v * 0.02;
        else if (v > 700000 && v <= 800000) luxuryMdl = v * 0.03;
        else if (v > 800000 && v <= 900000) luxuryMdl = v * 0.04;
        else if (v > 900000 && v <= 1000000) luxuryMdl = v * 0.05;
        else if (v > 1000000 && v <= 1200000) luxuryMdl = v * 0.06;
        else if (v > 1200000 && v <= 1400000) luxuryMdl = v * 0.07;
        else if (v > 1400000 && v <= 1600000) luxuryMdl = v * 0.08;
        else if (v > 1600000 && v <= 1800000) luxuryMdl = v * 0.09;
        else if (v > 1800000) luxuryMdl = v * 0.10;

        // Customs procedures fee: 0.4% of customs value, max 1800 EUR.
        let feeMdl = valueMdl * 0.004;
        const maxFeeMdl = 1800 * rate;
        if (feeMdl > maxFeeMdl) feeMdl = maxFeeMdl;

        return (exciseMdl + luxuryMdl + feeMdl) / rate; // back to EUR
    }

    // Sum enabled fixed params (EUR). 'percent' params are taken as % of priceEur.
    function fixedParamsEur(params, priceEur, skipKeys) {
        if (!Array.isArray(params)) return 0;
        let sum = 0;
        for (const p of params) {
            if (parseInt(p.enabled, 10) !== 1) continue;
            if (skipKeys && skipKeys.indexOf(p.param_key) !== -1) continue;
            const amt = parseFloat(p.amount_eur) || 0;
            if (p.value_type === 'percent') sum += priceEur * (amt / 100);
            else sum += amt;
        }
        return sum;
    }

    // Full landed cost in EUR. card data: { source, priceEur, fuel, capacity, year }.
    window.parsingCalcMd = function (d) {
        const P = pricingData();
        if (!P || !d || !d.priceEur || d.priceEur <= 0) return null;
        const isKorea = (d.source === 'encar');
        // Korea markup: add the price-band amount on top of the converted price.
        // The marked price is the new base for the whole MD breakdown (mirrors
        // parsing_md_breakdown_kr in PHP).
        let priceEur = d.priceEur;
        if (isKorea) priceEur += tierValue(P.kr_markup, priceEur, 'markup');

        const commission = tierValue(P.commission, priceEur, 'commission');

        if (isKorea) {
            // Sea freight (RoRo) is the transport that enters the customs base.
            let roro = 0;
            (P.kr_params || []).forEach(p => { if (p.param_key === 'sea_freight_roro') roro = parseFloat(p.amount_eur) || 0; });
            const base = priceEur + roro;
            const customs = customsEur(P, base, priceEur, d.fuel, d.capacity, d.year);
            // Add all enabled KR fixed costs EXCEPT RoRo (already in the base).
            const fixed = fixedParamsEur(P.kr_params, priceEur, ['sea_freight_roro']);
            return Math.round(priceEur + roro + customs + fixed + commission);
        }

        // Europe: base = car + delivery tier.
        const delivery = tierValue(P.eu_delivery, priceEur, 'delivery');
        const base = priceEur + delivery;
        const customs = customsEur(P, base, priceEur, d.fuel, d.capacity, d.year);
        const fixed = fixedParamsEur(P.eu_params, priceEur, null);
        return Math.round(priceEur + delivery + customs + fixed + commission);
    };

    function renderMdPrices() {
        if (!pricingData()) return;
        document.querySelectorAll('.car-card').forEach(card => {
            const out = card.querySelector('.car-price-md');
            if (!out) return;
            const priceEur = parseFloat(card.getAttribute('data-price-eur')) || 0;
            const fuel = card.getAttribute('data-fuel') || '';
            const source = card.getAttribute('data-source') || '';
            const ccExact = parseInt(card.getAttribute('data-capacity'), 10) || 0;       // engine_volume (DB)
            const ccTitle = parseInt(card.getAttribute('data-capacity-display'), 10) || 0; // parsed from title

            // Use the exact DB capacity, or the one parsed from the title when present
            // (e.g. "3.0L" → 3000) — the title cc, when it exists, is a good value.
            // Only when we have NEITHER (e.g. Encar/eCarsTrade titles like "T6" /
            // "30 TDI" that carry no litres, and engine_volume not enriched yet) do we
            // show "MD: …" and poll — computing with cc=0 drops the excise (the biggest
            // line) and gives a badly-low MD.
            const capacity = ccExact || ccTitle;

            if (priceEur > 0 && capacity <= 0 && fuel !== 'electric') {
                out.innerHTML = '<span class="car-price-md-label">MD:</span> …';
                out.style.display = '';
                card.setAttribute('data-md-pending', '1');
                return;
            }

            const md = window.parsingCalcMd({
                source: source,
                priceEur: priceEur,
                fuel: fuel,
                capacity: capacity,
                year: parseInt(card.getAttribute('data-year'), 10) || 0,
            });
            if (md && md > 0) {
                // "MD:" label in its own span so it can be styled separately
                // (black) from the amount (red).
                out.innerHTML = '<span class="car-price-md-label">MD:</span> ' +
                    md.toLocaleString('ro-MD') + ' €';
                out.style.display = '';
                card.removeAttribute('data-md-pending');
            } else {
                out.style.display = 'none';
            }
        });

        scheduleMdPendingPoll();
    }

    let _mdPollTimer = null;
    let _mdPollTries = 0;
    function scheduleMdPendingPoll() {
        if (_mdPollTimer) return;
        const pending = document.querySelectorAll('.car-card[data-md-pending="1"]');
        if (!pending.length) { _mdPollTries = 0; return; }
        if (_mdPollTries >= 12) return;
        // First pass fires almost immediately (no needless 5s wait); later passes
        // space out so we don't hammer the source with detail requests.
        const delay = _mdPollTries === 0 ? 200 : 3000;
        _mdPollTimer = setTimeout(() => {
            _mdPollTimer = null;
            _mdPollTries++;
            refreshPendingMd();
        }, delay);
    }

    function refreshPendingMd() {
        // Each enrich_one_md pulls a detail page, so cap how many we hit per cycle
        // to avoid hammering the source with many simultaneous detail requests.
        const pending = Array.from(document.querySelectorAll('.car-card[data-md-pending="1"]')).slice(0, 10);
        if (!pending.length) return;
        // enrich_one_md PULLS the detail page (real engine_volume from the official
        // spec) and returns it — so cc lands even if the import's background worker
        // hasn't run yet. No Groq, no cost.
        Promise.all(pending.map(card => {
            const carId = card.getAttribute('data-car-id');
            if (!carId) return Promise.resolve();
            return ajax('enrich_one_md', { car_id: carId }).then(res => {
                if (!(res && res.success)) return;
                const cap = parseInt(res.capacity, 10) || 0;
                if (cap > 0) {
                    card.setAttribute('data-capacity', cap);
                    if (res.fuel) card.setAttribute('data-fuel', res.fuel);
                    card.removeAttribute('data-md-pending');
                    // Also fill the "X.YL" text in car-meta, which was rendered empty
                    // by PHP when cc wasn't known yet (so it appears like the MD does).
                    const ccEl = card.querySelector('.car-meta-cc');
                    if (ccEl && !ccEl.textContent.trim()) {
                        ccEl.textContent = ' ' + (cap / 1000).toFixed(1) + 'L';
                    }
                }
            }).catch(() => {});
        })).then(() => {
            renderMdPrices();
        });
    }

    // OpenLane auction countdown: render "1д 11ч" / "11ч 30м" / "Завершено" on
    // each .ol-countdown from its data-end ISO date. Refreshes every 30s.
    function renderAuctionCountdowns() {
        const nodes = document.querySelectorAll('.ol-countdown');
        if (!nodes.length) return;
        const L = (k, fb) => (window.PARSING_LANG && window.PARSING_LANG[k]) || fb;
        const dl = L('cd_d', 'd'), hl = L('cd_h', 'h'), ml = L('cd_m', 'm');
        const now = Date.now();
        nodes.forEach(function (n) {
            // Skip the invisible spacer (cards without an auction): it only
            // reserves height so all cards line up — never touch it.
            if (n.classList.contains('ol-countdown-placeholder')) return;
            // Absolute epoch (ms), resolved server-side in Europe/Brussels — no
            // browser-timezone ambiguity.
            const end = parseInt(n.dataset.endTs || '0', 10);
            const out = n.querySelector('.ol-cd-time');
            if (!out) return;
            if (!end) { n.style.display = 'none'; return; }
            let diff = Math.floor((end - now) / 1000);
            if (diff <= 0) {
                n.classList.add('ended');
                out.textContent = L('cd_ended', 'Încheiat');
                return;
            }
            const d = Math.floor(diff / 86400); diff -= d * 86400;
            const h = Math.floor(diff / 3600); diff -= h * 3600;
            const m = Math.floor(diff / 60);
            // Always show days + hours + minutes. Numbers are bold/black, the unit
            // letters stay normal — wrap each number in <b class="cd-n">.
            const num = (v, label) => '<b class="cd-n">' + v + '</b>' + label;
            let s;
            if (d > 0)      s = num(d, dl) + ' ' + num(h, hl) + ' ' + num(m, ml);
            else if (h > 0) s = num(h, hl) + ' ' + num(m, ml);
            else            s = num(m, ml);
            out.innerHTML = s;
            n.classList.toggle('soon', d === 0 && h < 6); // <6h → highlight
        });
    }

    // Status badge (Published page) follows the source auction timer: still
    // running → "Activ" (green); expired → "Vândut" (red). DB status takes
    // priority: if already marked unavailable, stay "Vândut" regardless.
    function renderStatusTimers() {
        const L = (k, fb) => (window.PARSING_LANG && window.PARSING_LANG[k]) || fb;
        const now = Date.now();
        document.querySelectorAll('.badge.status-timer').forEach(function (b) {
            if (b.classList.contains('badge-off')) return; // already "Vândut" from DB
            const end = parseInt(b.dataset.endTs || '0', 10);
            if (!end) return;                               // no auction end → leave as is
            if (end - now <= 0) {
                b.textContent = L('status_unavailable', 'Vândut');
                b.classList.remove('badge-on');
                b.classList.add('badge-off');
            }
        });
    }

    // When we arrive with #car-<id> (from the "already in catalog" link), scroll to
    // that card and flash a highlight ring so the operator immediately sees it.
    function highlightLinkedCar() {
        const m = (location.hash || '').match(/^#car-(\d+)$/);
        if (!m) return;
        const id = m[1];
        const card = document.querySelector('.car-card[data-car-id="' + id + '"]')
                  || document.querySelector('.publish-grid-2x2[data-ctlg="' + id + '"]')
                  || document.querySelector('[data-id="' + id + '"]');
        if (!card) return;
        const target = card.closest('.car-card') || card;
        target.scrollIntoView({ behavior: 'smooth', block: 'center' });
        // Ring stays until the page is reloaded (no auto-remove).
        target.classList.add('car-card-linked');
    }

    // After a filter import, ring EVERY freshly imported card (found_at >= cutoff
    // saved before the reload) and scroll to the first one. One-shot: the cutoff is
    // cleared so a later manual reload doesn't keep ringing them.
    function highlightImportedCars() {
        let since;
        try { since = parseInt(sessionStorage.getItem('parsing_ring_since') || '0', 10); } catch (e) { since = 0; }
        if (!since) return;
        try { sessionStorage.removeItem('parsing_ring_since'); } catch (e) {}

        const cards = document.querySelectorAll('.car-card[data-found-at]');
        let first = null;
        cards.forEach(card => {
            if (parseInt(card.getAttribute('data-found-at') || '0', 10) >= since) {
                // Thinner ring for freshly imported cars (there are many of them);
                // the bold car-card-linked ring stays for the single "already exists" car.
                card.classList.add('car-card-imported');
                if (!first) first = card;
            }
        });
        if (first) first.scrollIntoView({ behavior: 'smooth', block: 'center' });
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', initCatalogFilter);
        document.addEventListener('DOMContentLoaded', renderMdPrices);
        document.addEventListener('DOMContentLoaded', renderAuctionCountdowns);
        document.addEventListener('DOMContentLoaded', renderStatusTimers);
        document.addEventListener('DOMContentLoaded', highlightImportedCars);
        document.addEventListener('DOMContentLoaded', highlightLinkedCar);
        document.addEventListener('DOMContentLoaded', initParsingMobileSliders);
    } else {
        initCatalogFilter();
        renderMdPrices();
        renderAuctionCountdowns();
        renderStatusTimers();
        highlightImportedCars();
        highlightLinkedCar();
        initParsingMobileSliders();
    }
    window.initParsingMobileSliders = initParsingMobileSliders;
    setInterval(renderAuctionCountdowns, 30000);
    setInterval(renderStatusTimers, 30000);
})();
