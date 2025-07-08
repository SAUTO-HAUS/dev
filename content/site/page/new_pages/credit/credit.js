// Comments slider functionality - Show 2 comments at a time
let currentCommentIndex = 1;
const totalComments = 8;
const commentsPerView = 2;

// Initialize sliders when page is ready
$(document).ready(function() {
    console.log('Initializing credit calculator sliders...');
    // Format money values with spaces between thousands
    function formatMoney(value) {
        return value.toString().replace(/\B(?=(\d{3})+(?!\d))/g, " ");
    }
    
    // Variables for sliders - exact copy from cars.php
    let updateRateTimeout;
    
    // Input field references
    var $input_suma_creditului = $("#view_suma_creditului");
    
    console.log('Found suma creditului span:', $input_suma_creditului.length);
    console.log('Found suma creditului input:', $("#suma-creditului").length);
    
    // Amount slider - exact copy from cars.php
    const sliderSuma = $("#suma-creditului").ionRangeSlider({
        skin: "round",
        min: 2000,
        max: 50000,
        from: 25000,
        step: 500,
        onStart: function(data) {
            console.log('Slider suma onStart:', data.from);
            $input_suma_creditului.html("<span style='font-size: 1.2em; font-weight: bold;'>" + formatMoney(data.from) + "</span> €");
        },
        onChange: function (data) {
            console.log('Slider suma onChange:', data.from);

            $input_suma_creditului.html("<span style='font-size: 1.2em; font-weight: bold;'>" + formatMoney(data.from) + "</span> €");
            console.log('Updated suma text to:', formatMoney(data.from) + " €");
            clearTimeout(updateRateTimeout);
            updateRateTimeout = setTimeout(updateRate, 100);
        }
    }).data("ionRangeSlider");
    
    var $input_termen_creditului = $("#view_termen_creditului");
    
    console.log('Found termen creditului span:', $input_termen_creditului.length);
    console.log('Found termen creditului input:', $("#termen-creditului").length);
    
    // Term slider - adapted for span elements
    const sliderTermen = $("#termen-creditului").ionRangeSlider({
        skin: "round",
        min: 6,
        max: 60,
        from: 30,
        step: 1,
        onStart: function(data) {
            console.log('Slider termen onStart:', data.from);
            // Get the translated months text from the data attribute
            var monthsText = $input_termen_creditului.data('months') || 'luni';
            // Update the span text with larger number and normal text
            $input_termen_creditului.html("<span style='font-size: 1.2em; font-weight: bold;'>" + data.from + "</span> " + monthsText);
        },
        onChange: function (data) {
            console.log('Slider termen onChange:', data.from);
            // Get the translated months text from the data attribute
            var monthsText = $input_termen_creditului.data('months') || 'luni';
            // Update the span text when the slider moves with larger number and normal text
            $input_termen_creditului.html("<span style='font-size: 1.2em; font-weight: bold;'>" + data.from + "</span> " + monthsText);
            console.log('Updated termen text to:', data.from + " " + monthsText);
            clearTimeout(updateRateTimeout);
            updateRateTimeout = setTimeout(updateRate, 100);
        }
    }).data("ionRangeSlider");
    
    // Update Rate function - adaptat pentru a citi din slider-uri direct
    function updateRate() {
        // Obținem valorile direct din slider-uri
        const suma = sliderSuma.result.from;
        const termen = sliderTermen.result.from;
        
        console.log('updateRate called with:', suma, termen);
        
        // Call the main calculation function
        calculatePayment();
    }
    
    // Payment calculation function
    function calculatePayment() {
        // Get values from sliders directly
        const loanAmount = sliderSuma.result.from;
        const loanTerm = sliderTermen.result.from;
        
        console.log('Calculating payment for:', loanAmount, 'EUR over', loanTerm, 'months');
        
        if (isNaN(loanAmount) || isNaN(loanTerm) || loanAmount <= 0 || loanTerm <= 0) {
            console.log('Invalid input values');
            return;
        }
    
    // Minimum interest rate: 9.2%
    const minRate = 0.092;
    const minMonthlyRate = minRate / 12;
    
    // Maximum interest rate: 24%
    const maxRate = 0.24;
    const maxMonthlyRate = maxRate / 12;
    
    // Loan formula: PMT = P * r * (1+r)^n / ((1+r)^n - 1)
    const minPayment = (loanAmount * minMonthlyRate * Math.pow(1 + minMonthlyRate, loanTerm)) / 
                     (Math.pow(1 + minMonthlyRate, loanTerm) - 1);
    
    const maxPayment = (loanAmount * maxMonthlyRate * Math.pow(1 + maxMonthlyRate, loanTerm)) / 
                     (Math.pow(1 + maxMonthlyRate, loanTerm) - 1);
    
    console.log('Calculated payments:', Math.floor(minPayment), 'to', Math.floor(maxPayment));
    
        // Get translation words from page (set by PHP)
        var fromText = $("#payment-display").attr('data-from') || 'от';
        var toText = $("#payment-display").attr('data-to') || 'до';
        
        // Display results in localized format with smaller "from" and "to" text
        $("#payment-display").html("<span style='font-size: 0.85em;'>" + fromText + "</span> " + Math.floor(minPayment) + " <span style='font-size: 0.85em;'>" + toText + "</span> " + Math.floor(maxPayment) + " €");
    }
    
    // Calculate initial payments on page load
    calculatePayment();
    
    // Tab switching functionality for credit categories - Simple approach
    $(document).on('click', '.tab-button', function(e) {
        e.preventDefault();
        
        // Get the target category from data attribute
        var targetCategory = $(this).attr('data-category');
        
        if (!targetCategory) {
            console.log('No data-category found');
            return;
        }
        
        // Remove active class from all tabs
        $('.tab-button').removeClass('active');
        
        // Add active class to clicked tab
        $(this).addClass('active');
        
        // Hide all category grids
        $('.category-grid').removeClass('active');
        
        // Show the target category grid
        $('#' + targetCategory + '-content').addClass('active');
        
        console.log('Tab switched to:', targetCategory);
    });
    
    // Make sure only first tab is active on page load
    setTimeout(function() {
        $('.category-grid').removeClass('active');
        $('#personal-content').addClass('active');
        $('.tab-button').removeClass('active');
        $('.tab-button[data-category="personal"]').addClass('active');
    }, 100);
});

// Initial setup - removed showCategory call as function doesn't exist

// Function to show a pair of comments starting from a specific index
function showCommentPair(startIndex) {
    console.log('showCommentPair called with startIndex:', startIndex);
    
    // Hide all comment cards first
    let commentCards = document.getElementsByClassName("comment-card");
    console.log('Found comment cards:', commentCards.length);
    
    for (let i = 0; i < commentCards.length; i++) {
        commentCards[i].classList.remove("active");
    }
    
    // Show two consecutive comments
    let firstIndex = startIndex;
    let secondIndex = startIndex + 1;
    
    console.log('Showing comments:', firstIndex, 'and', secondIndex);
    
    // Show the first comment
    if (commentCards[firstIndex - 1]) {
        commentCards[firstIndex - 1].classList.add("active");
        console.log('Activated comment:', firstIndex);
    }
    
    // Show the second comment
    if (commentCards[secondIndex - 1]) {
        commentCards[secondIndex - 1].classList.add("active");
        console.log('Activated comment:', secondIndex);
    }
    
    // Update current index
    currentCommentIndex = startIndex;
}

// Function to show next pair of comments
function nextComment() {
    console.log('nextComment() called, currentCommentIndex:', currentCommentIndex);
    
    // Calculate next pair starting index (move by 2)
    let nextIndex = currentCommentIndex + 2;
    if (nextIndex > totalComments) {
        nextIndex = 1; // Wrap around to beginning
    }
    
    console.log('Next pair will start at:', nextIndex);
    showCommentPair(nextIndex);
}

// Function to show previous pair of comments
function previousComment() {
    console.log('previousComment() called, currentCommentIndex:', currentCommentIndex);
    
    // Calculate previous pair starting index (move by 2)
    let prevIndex = currentCommentIndex - 2;
    if (prevIndex < 1) {
        // Wrap around to the last pair
        prevIndex = totalComments - 1; // Start at 7 to show 7,8
    }
    
    console.log('Previous pair will start at:', prevIndex);
    showCommentPair(prevIndex);
}

// Initialize comments slider when page loads
$(document).ready(function() {
    // Show first pair of comments by default
    showCommentPair(1);
});