$(document).ready(function() {
    // Initialize sliders
    const sumaSlider = $("#suma-slider").ionRangeSlider({
        skin: "round",
        min: 2000,
        max: 50000,
        from: 25000,
        step: 500,
        prefix: "€",
        prettify_enabled: true,
        prettify_separator: ",",
        onStart: function(data) {
            $("#suma_creditului").val(data.from);
        },
        onChange: function(data) {
            $("#suma_creditului").val(data.from);
            calculatePayment();
        }
    }).data("ionRangeSlider");

    const termenSlider = $("#termen-slider").ionRangeSlider({
        skin: "round",
        min: 6,
        max: 60,
        from: 36,
        step: 1,
        postfix: " luni",
        prettify_enabled: true,
        onStart: function(data) {
            $("#termen_creditului").val(data.from);
        },
        onChange: function(data) {
            $("#termen_creditului").val(data.from);
            calculatePayment();
        }
    }).data("ionRangeSlider");

    // Handle input field changes
    $("#suma_creditului").on("input", function() {
        let val = parseInt($(this).val().replace(/[^0-9]/g, ''), 10);
        if (!isNaN(val)) {
            val = Math.max(2000, Math.min(50000, val));
            val = Math.round(val / 500) * 500;
            $(this).val(val);
            sumaSlider.update({ from: val });
            calculatePayment();
        }
    });

    $("#termen_creditului").on("input", function() {
        let val = parseInt($(this).val(), 10);
        if (!isNaN(val)) {
            val = Math.max(6, Math.min(60, val));
            $(this).val(val);
            termenSlider.update({ from: val });
            calculatePayment();
        }
    });

    function calculatePayment() {
        const suma = parseInt($("#suma_creditului").val(), 10) || 25000;
        const termen = parseInt($("#termen_creditului").val(), 10) || 36;
        
        // Interest rates (annual)
        const rateMin = 0.08; // 8%
        const rateMax = 0.15; // 15%
        
        // Convert to monthly rates
        const monthlyRateMin = rateMin / 12;
        const monthlyRateMax = rateMax / 12;
        
        // Calculate monthly payments using PMT formula
        const pmtMin = suma * (monthlyRateMin * Math.pow(1 + monthlyRateMin, termen)) / 
                      (Math.pow(1 + monthlyRateMin, termen) - 1);
        const pmtMax = suma * (monthlyRateMax * Math.pow(1 + monthlyRateMax, termen)) / 
                      (Math.pow(1 + monthlyRateMax, termen) - 1);
        
        const pmtAvg = (pmtMin + pmtMax) / 2;
        
        // Update display
        $("#rata-lunara").text("€" + Math.round(pmtAvg).toLocaleString());
        $("#rata-min").text("€" + Math.round(pmtMin).toLocaleString());
        $("#rata-max").text("€" + Math.round(pmtMax).toLocaleString());
    }

    // Initial calculation
    calculatePayment();

    // Smooth scrolling for anchor links
    $('a[href^="#"]').on('click', function(event) {
        var target = $(this.getAttribute('href'));
        if( target.length ) {
            event.preventDefault();
            $('html, body').stop().animate({
                scrollTop: target.offset().top - 100
            }, 1000);
        }
    });

    // Add animation on scroll
    function animateOnScroll() {
        $('.feature-card, .partner-card').each(function() {
            const elementTop = $(this).offset().top;
            const elementBottom = elementTop + $(this).outerHeight();
            const viewportTop = $(window).scrollTop();
            const viewportBottom = viewportTop + $(window).height();
            
            if (elementBottom > viewportTop && elementTop < viewportBottom) {
                $(this).addClass('animate-in');
            }
        });
    }

    $(window).on('scroll', animateOnScroll);
    animateOnScroll(); // Initial check

    // Form validation and submission helpers
    function validateCreditForm() {
        const suma = parseInt($("#suma_creditului").val(), 10);
        const termen = parseInt($("#termen_creditului").val(), 10);
        
        if (isNaN(suma) || suma < 2000 || suma > 50000) {
            alert('Suma creditului trebuie să fie între €2,000 și €50,000');
            return false;
        }
        
        if (isNaN(termen) || termen < 6 || termen > 60) {
            alert('Termenul creditului trebuie să fie între 6 și 60 luni');
            return false;
        }
        
        return true;
    }

    // Export functions for global access
    window.creditCalculator = {
        calculatePayment: calculatePayment,
        validateForm: validateCreditForm,
        updateSliders: function(suma, termen) {
            if (suma) {
                sumaSlider.update({ from: suma });
                $("#suma_creditului").val(suma);
            }
            if (termen) {
                termenSlider.update({ from: termen });
                $("#termen_creditului").val(termen);
            }
            calculatePayment();
        }
    };
});