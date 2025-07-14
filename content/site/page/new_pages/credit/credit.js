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
    
    // Tab switching functionality for credit categories with mobile accordion
    $(document).on('click', '.tab-button', function(e) {
        e.preventDefault();
        
        var targetCategory = $(this).attr('data-category');
        var isMobile = window.innerWidth <= 768;
        
        if (!targetCategory) {
            console.log('No data-category found');
            return;
        }
        
        if (isMobile) {
            // Mobile accordion behavior
            var clickedTab = $(this);
            var isCurrentlyActive = clickedTab.hasClass('active');
            
            if (isCurrentlyActive) {
                // Close the currently open accordion
                clickedTab.removeClass('active');
                clickedTab.next('.mobile-category-content').remove();
            } else {
                // Close any open accordion
                $('.tab-button').removeClass('active');
                $('.mobile-category-content').remove();
                
                // Open the clicked accordion
                clickedTab.addClass('active');
                
                // Clone the content and insert after clicked tab
                var contentToClone = $('#' + targetCategory + '-content').clone();
                contentToClone.removeClass('category-grid active');
                contentToClone.addClass('mobile-category-content');
                
                // Remove the duplicate title (keep only description and feature cards)
                contentToClone.find('.category-title').hide();
                
                contentToClone.css({
                    'display': 'block',
                    'padding': '20px',
                    'background-color': 'white',
                    'border-bottom': '1px solid #e0e0e0'
                });
                
                clickedTab.after(contentToClone);
            }
        } else {
            // Desktop tab behavior
            $('.tab-button').removeClass('active');
            $(this).addClass('active');
            $('.category-grid').removeClass('active');
            $('#' + targetCategory + '-content').addClass('active');
        }
        
        console.log('Tab switched to:', targetCategory);
    });
    
    // Initialize categories based on screen size
    function initializeCategories() {
        var isMobile = window.innerWidth <= 768;
        
        if (isMobile) {
            // Mobile: Close all accordions - no categories open initially
            $('.tab-button').removeClass('active');
            $('.category-grid').removeClass('active');
            $('.mobile-category-content').remove();
        } else {
            // Desktop: Show first tab as active
            $('.mobile-category-content').remove();
            $('.category-grid').removeClass('active');
            $('#personal-content').addClass('active');
            $('.tab-button').removeClass('active');
            $('.tab-button[data-category="personal"]').addClass('active');
        }
    }
    
    // Initialize on page load
    setTimeout(initializeCategories, 100);
    
    // Store initial window width to detect real resize
    var lastWindowWidth = window.innerWidth;
    var resizeTimeout;
    
    // Reinitialize on window resize with debounce and width check
    $(window).on('resize', function() {
        clearTimeout(resizeTimeout);
        resizeTimeout = setTimeout(function() {
            var currentWidth = window.innerWidth;
            // Only reinitialize if width actually changed (not just scroll)
            if (Math.abs(currentWidth - lastWindowWidth) > 50) {
                lastWindowWidth = currentWidth;
                initializeCategories();
            }
        }, 250);
    });
});

// Initial setup - removed showCategory call as function doesn't exist

// Function to show comments based on screen size
function showCommentPair(startIndex) {
    console.log('showCommentPair called with startIndex:', startIndex);
    
    // Check if mobile (same breakpoint as CSS)
    const isMobile = window.innerWidth <= 768;
    
    // Hide all comment cards first
    let commentCards = document.getElementsByClassName("comment-card");
    console.log('Found comment cards:', commentCards.length);
    
    for (let i = 0; i < commentCards.length; i++) {
        commentCards[i].classList.remove("active");
    }
    
    if (isMobile) {
        // Mobile: Show only one comment
        let commentIndex = startIndex;
        console.log('Mobile: Showing comment:', commentIndex);
        
        if (commentCards[commentIndex - 1]) {
            commentCards[commentIndex - 1].classList.add("active");
            console.log('Activated comment:', commentIndex);
        }
    } else {
        // Desktop: Show two consecutive comments
        let firstIndex = startIndex;
        let secondIndex = startIndex + 1;
        
        console.log('Desktop: Showing comments:', firstIndex, 'and', secondIndex);
        
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
    }
    
    // Update current index
    currentCommentIndex = startIndex;
}

// Function to show next comments (1 on mobile, 2 on desktop)
function nextComment() {
    console.log('nextComment() called, currentCommentIndex:', currentCommentIndex);
    
    // Check if mobile (same breakpoint as CSS)
    const isMobile = window.innerWidth <= 768;
    const step = isMobile ? 1 : 2;
    
    // Calculate next starting index
    let nextIndex = currentCommentIndex + step;
    if (nextIndex > totalComments) {
        nextIndex = 1; // Wrap around to beginning
    }
    
    console.log('Next will start at:', nextIndex, '(step:', step + ')');
    showCommentPair(nextIndex);
}

// Function to show previous comments (1 on mobile, 2 on desktop)
function previousComment() {
    console.log('previousComment() called, currentCommentIndex:', currentCommentIndex);
    
    // Check if mobile (same breakpoint as CSS)
    const isMobile = window.innerWidth <= 768;
    const step = isMobile ? 1 : 2;
    
    // Calculate previous starting index
    let prevIndex = currentCommentIndex - step;
    if (prevIndex < 1) {
        // Wrap around to the end
        prevIndex = isMobile ? totalComments : totalComments - 1;
    }
    
    console.log('Previous will start at:', prevIndex, '(step:', step + ')');
    showCommentPair(prevIndex);
}

// Initialize partners slider with infinite scroll
$(document).ready(function() {
    // Initialize partners slider position on mobile
    if (window.innerWidth <= 768) {
        setTimeout(function() {
            const partnersGrid = document.querySelector('.partners-grid');
            const originalCards = document.querySelectorAll('.partner-card');
            
            if (partnersGrid && originalCards.length > 0) {
                console.log('Found partners grid and', originalCards.length, 'original partner cards');
                
                // Create infinite scroll by duplicating cards
                const cardArray = Array.from(originalCards);
                
                // Clone cards and add them before (in reverse order) and after original cards
                const clonedBefore = cardArray.slice().reverse().map(card => {
                    const clone = card.cloneNode(true);
                    clone.classList.add('clone-before');
                    return clone;
                });
                
                const clonedAfter = cardArray.map(card => {
                    const clone = card.cloneNode(true);
                    clone.classList.add('clone-after');
                    return clone;
                });
                
                // Insert cloned cards
                clonedBefore.forEach(clone => {
                    partnersGrid.insertBefore(clone, partnersGrid.firstChild);
                });
                
                clonedAfter.forEach(clone => {
                    partnersGrid.appendChild(clone);
                });
                
                // Position to start at original first card (after the cloned-before cards)
                const firstOriginalCard = partnersGrid.querySelector('.partner-card:not(.clone-before):not(.clone-after)');
                if (firstOriginalCard) {
                    firstOriginalCard.scrollIntoView({ inline: 'start', behavior: 'auto' });
                }
                
                console.log('Infinite scroll setup complete with', partnersGrid.children.length, 'total cards');
                
                // Add infinite scroll listener
                partnersGrid.addEventListener('scroll', function() {
                    const scrollLeft = partnersGrid.scrollLeft;
                    const scrollWidth = partnersGrid.scrollWidth;
                    const clientWidth = partnersGrid.clientWidth;
                    const cardWidth = 272; // 260px + 12px gap
                    const originalCardsWidth = cardWidth * originalCards.length;
                    
                    // If scrolled to far right (past original cards), jump to beginning
                    if (scrollLeft >= originalCardsWidth + (cardWidth * originalCards.length)) {
                        partnersGrid.scrollLeft = originalCardsWidth;
                    }
                    
                    // If scrolled to far left (before original cards), jump to end
                    if (scrollLeft <= 0) {
                        partnersGrid.scrollLeft = originalCardsWidth;
                    }
                });
                
            } else {
                console.log('Partners grid or cards not found');
            }
        }, 500); // Wait for page to fully load
    }
});

// Initialize comments slider when page loads
$(document).ready(function() {
    // Show first pair of comments by default
    showCommentPair(1);
});