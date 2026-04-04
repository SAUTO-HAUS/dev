(function () {
    'use strict';

    const cfg = window.CALC_CONFIG || {};
    let EUR_RATE = cfg.eurRate || 19.50;
const HYBRID_DISCOUNT_PLUGIN = cfg.hybridDiscountPlugin || 50;
    const HYBRID_DISCOUNT_FULL = cfg.hybridDiscountFull || 25;
    const EXCISE_RATES = cfg.exciseRates || [];

    // ── Fuel type buttons ──────────────────────────────────────────
    document.querySelectorAll('.calc-fuel-btn').forEach(function (btn) {
        btn.addEventListener('click', function () {
            document.querySelectorAll('.calc-fuel-btn').forEach(function (b) {
                b.classList.remove('active');
            });
            this.classList.add('active');

            const fuel = this.dataset.fuel;
            const hybridOptions = document.getElementById('calc-hybrid-options');
            const electricNotice = document.getElementById('calc-electric-notice');

            hybridOptions.classList.toggle('show', fuel === 'hybrid');
            electricNotice.classList.toggle('show', fuel === 'electric');
        });
    });

    // ── Hybrid type buttons ────────────────────────────────────────
    document.querySelectorAll('.calc-hybrid-type-btn').forEach(function (btn) {
        btn.addEventListener('click', function () {
            document.querySelectorAll('.calc-hybrid-type-btn').forEach(function (b) {
                b.classList.remove('active');
            });
            this.classList.add('active');
        });
    });

    // ── Calculate ─────────────────────────────────────────────────
    document.getElementById('calc-calculate-btn').addEventListener('click', function () {
        const vehicleType = document.getElementById('calc-vehicle-type').value;
        const year = parseInt(document.getElementById('calc-year').value);
        const capacity = parseInt(document.getElementById('calc-capacity').value) || 0;
        const priceEur = parseFloat(document.getElementById('calc-price-eur').value) || 0;
        const transportEur = parseFloat(document.getElementById('calc-transport-eur').value) || 0;
        const totalPriceEur = priceEur + transportEur;

        const activeFuelBtn = document.querySelector('.calc-fuel-btn.active');
        const fuelType = activeFuelBtn ? activeFuelBtn.dataset.fuel : 'benzina';

        const currentYear = new Date().getFullYear();
        const age = currentYear - year;

        // Excise
        let excise = 0;
        if (fuelType !== 'electric') {
            let baseFuel = fuelType;
            if (fuelType === 'hybrid') {
                const checkedRadio = document.querySelector('input[name="hybrid_fuel"]:checked');
                baseFuel = checkedRadio ? checkedRadio.value : 'benzina';
            }

            if (vehicleType === 'motocicleta') {
                const rate = findExciseRate('motocicleta', capacity, age);
                excise = rate * capacity;
            } else {
                const rate = findExciseRate(baseFuel, capacity, age);
                excise = rate * capacity;

                if (fuelType === 'hybrid') {
                    const activeHybridBtn = document.querySelector('.calc-hybrid-type-btn.active');
                    const hybridType = activeHybridBtn ? activeHybridBtn.dataset.hybrid : 'plugin';
                    if (hybridType === 'plugin') {
                        excise = excise * (1 - HYBRID_DISCOUNT_PLUGIN / 100);
                    } else if (hybridType === 'full') {
                        excise = excise * (1 - HYBRID_DISCOUNT_FULL / 100);
                    }
                }
            }
        }

        // Convert excise MDL → EUR
        const exciseEur = excise / EUR_RATE;

        // Luxury excise on customs value (MDL)
        const valueMdl = totalPriceEur * EUR_RATE;
        let luxuryExcise = 0;
        if (valueMdl >= 600000 && valueMdl <= 700000)      { luxuryExcise = valueMdl * 0.02; }
        else if (valueMdl > 700000 && valueMdl <= 800000)  { luxuryExcise = valueMdl * 0.03; }
        else if (valueMdl > 800000 && valueMdl <= 900000)  { luxuryExcise = valueMdl * 0.04; }
        else if (valueMdl > 900000 && valueMdl <= 1000000) { luxuryExcise = valueMdl * 0.05; }
        else if (valueMdl > 1000000 && valueMdl <= 1200000){ luxuryExcise = valueMdl * 0.06; }
        else if (valueMdl > 1200000 && valueMdl <= 1400000){ luxuryExcise = valueMdl * 0.07; }
        else if (valueMdl > 1400000 && valueMdl <= 1600000){ luxuryExcise = valueMdl * 0.08; }
        else if (valueMdl > 1600000 && valueMdl <= 1800000){ luxuryExcise = valueMdl * 0.09; }
        else if (valueMdl > 1800000)                        { luxuryExcise = valueMdl * 0.10; }
        const luxuryExciseEur = luxuryExcise / EUR_RATE;

        // Customs procedures fee (0.4% of customs value, max 1800 EUR)
        let customsFeeEur = totalPriceEur * 0.004;
        if (customsFeeEur > 1800) customsFeeEur = 1800;

        // Total customs costs in EUR
        const totalEur = exciseEur + luxuryExciseEur + customsFeeEur;

        // Grand total: vehicle price + transport + taxes (all in EUR)
        const vehicleTotalEur = totalPriceEur + totalEur;

        // ── Render results ──────────────────────────────────────────
        document.getElementById('res-year-display').textContent = year;
        document.getElementById('res-capacity-display').textContent = capacity > 0 ? capacity + ' cm³' : '—';
        document.getElementById('res-price-display').textContent = priceEur > 0 ? formatEur(priceEur) : '—';

        document.getElementById('res-excise').textContent = formatEur(exciseEur);

        const luxuryRow = document.getElementById('res-luxury-row');
        if (luxuryExcise > 0) {
            luxuryRow.style.display = 'flex';
            document.getElementById('res-luxury').textContent = formatEur(luxuryExciseEur);
        } else {
            luxuryRow.style.display = 'none';
        }

        document.getElementById('res-customs').textContent = formatEur(customsFeeEur);
        document.getElementById('res-total').textContent = formatEur(totalEur);
        document.getElementById('res-vehicle-total').textContent = formatEur(vehicleTotalEur);

        const resultsBox = document.getElementById('calc-results');
        resultsBox.style.display = 'block';
        resultsBox.scrollIntoView({ behavior: 'smooth', block: 'nearest' });
    });

    // ── Results toggle (collapse/expand) ──────────────────────────
    document.getElementById('calc-results-toggle').addEventListener('click', function () {
        const body = document.getElementById('calc-results-body');
        const chevron = this.querySelector('.calc-results-chevron');
        const isOpen = body.classList.toggle('collapsed');
        chevron.classList.toggle('rotated', isOpen);
    });

    // ── Helpers ───────────────────────────────────────────────────
    function findExciseRate(fuelType, capacity, age) {
        for (var i = 0; i < EXCISE_RATES.length; i++) {
            var r = EXCISE_RATES[i];
            if (r.fuel_type !== fuelType) continue;
            var capMin = parseInt(r.capacity_min);
            var capMax = parseInt(r.capacity_max);
            var ageMin = parseInt(r.age_min);
            var ageMax = parseInt(r.age_max);
            var capOk = capacity >= capMin && (capMax === 0 || capacity <= capMax);
            var ageOk = age >= ageMin && (ageMax === 0 || age <= ageMax);
            if (capOk && ageOk) return parseFloat(r.rate);
        }
        return 0;
    }

    function formatEur(amount) {
        return amount.toLocaleString('ro-MD', { minimumFractionDigits: 0, maximumFractionDigits: 0 }) + ' €';
    }

})();
