document.addEventListener('DOMContentLoaded', function() {
    const sliderTrack = document.querySelector('.order-slider-track');
    const prevBtn = document.querySelector('.order-slider-prev');
    const nextBtn = document.querySelector('.order-slider-next');
    const cards = document.querySelectorAll('.order-slider-card');
    
    if (!sliderTrack || !prevBtn || !nextBtn || cards.length === 0) return;
    
    const totalCards = cards.length;
    const clonedCards = [];
    cards.forEach(card => {
        const clone = card.cloneNode(true);
        clonedCards.push(clone);
        sliderTrack.appendChild(clone);
    });
    
    let currentIndex = 0;
    let isTransitioning = false;
    
    function getCardWidth() {
        return cards[0].offsetWidth;
    }
    
    function updateSlider(smooth = true) {
        const cardWidth = getCardWidth();
        const gap = 24;
        const offset = currentIndex * (cardWidth + gap);
        
        sliderTrack.style.transition = smooth ? 'transform 0.5s ease' : 'none';
        sliderTrack.style.transform = `translateX(-${offset}px)`;
    }
    
    function handleTransitionEnd() {
        if (currentIndex >= totalCards) {
            isTransitioning = true;
            currentIndex = 0;
            updateSlider(false);
            setTimeout(() => { isTransitioning = false; }, 50);
        }
        
        if (currentIndex < 0) {
            isTransitioning = true;
            currentIndex = totalCards - 1;
            updateSlider(false);
            setTimeout(() => { isTransitioning = false; }, 50);
        }
    }
    
    sliderTrack.addEventListener('transitionend', handleTransitionEnd);
    
    prevBtn.addEventListener('click', function() {
        if (isTransitioning) return;
        currentIndex--;
        if (currentIndex < 0) {
            currentIndex = totalCards - 1;
            updateSlider(false);
            setTimeout(() => {
                currentIndex--;
                updateSlider(true);
            }, 50);
        } else {
            updateSlider(true);
        }
    });
    
    nextBtn.addEventListener('click', function() {
        if (isTransitioning) return;
        currentIndex++;
        if (currentIndex >= totalCards) {
            currentIndex = 0;
            updateSlider(false);
            setTimeout(() => {
                currentIndex++;
                updateSlider(true);
            }, 50);
        } else {
            updateSlider(true);
        }
    });
    
    let resizeTimer;
    window.addEventListener('resize', function() {
        clearTimeout(resizeTimer);
        resizeTimer = setTimeout(function() {
            updateSlider(false);
        }, 250);
    });
    
    updateSlider(false);
});

// Process Steps - Change on Scroll
document.addEventListener('DOMContentLoaded', function() {
    const scrollContainer = document.querySelector('.order-process-scroll-container');
    const numberElement = document.querySelector('.order-process-number');
    const steps = document.querySelectorAll('.order-process-step');
    const previewPrev = document.querySelector('.order-process-preview-prev');
    const previewNext = document.querySelector('.order-process-preview-next');
    const previewPrevTitle = previewPrev ? previewPrev.querySelector('.order-process-preview-title') : null;
    const previewNextTitle = previewNext ? previewNext.querySelector('.order-process-preview-title') : null;
    
    if (!scrollContainer || !numberElement || steps.length === 0) return;
    
    let currentStep = 0;
    let isScrolling = false;
    
    function showStep(index) {
        steps.forEach((step, i) => {
            step.classList.toggle('active', i === index);
        });
        
        if (index > 0 && previewPrev && previewPrevTitle) {
            var prevTitle = steps[index - 1].querySelector('.order-process-step-title').textContent;
            previewPrevTitle.textContent = prevTitle;
            previewPrev.style.display = 'flex';
        } else if (previewPrev) {
            previewPrev.style.display = 'none';
        }
        
        if (index < steps.length - 1 && previewNext && previewNextTitle) {
            var nextTitle = steps[index + 1].querySelector('.order-process-step-title').textContent;
            previewNextTitle.textContent = nextTitle;
            previewNext.style.display = 'flex';
        } else if (previewNext) {
            previewNext.style.display = 'none';
        }
        
        numberElement.textContent = String(index + 1).padStart(2, '0');
    }
    
    showStep(0);
    
    scrollContainer.addEventListener('wheel', function(e) {
        e.preventDefault();
        e.stopPropagation();
        
        if (isScrolling) return;
        
        isScrolling = true;
        
        if (e.deltaY > 0 && currentStep < steps.length - 1) {
            currentStep++;
            showStep(currentStep);
        } else if (e.deltaY < 0 && currentStep > 0) {
            currentStep--;
            showStep(currentStep);
        }
        
        setTimeout(function() {
            isScrolling = false;
        }, 100);
    }, { passive: false, capture: true });
});

// Reviews Slider
document.addEventListener('DOMContentLoaded', function() {
    const slider = document.querySelector('.order-reviews-slider');
    const prevBtn = document.querySelector('.order-reviews-prev');
    const nextBtn = document.querySelector('.order-reviews-next');
    
    if (!slider || !prevBtn || !nextBtn) return;
    
    const cards = slider.querySelectorAll('.order-review-card');
    const totalCards = cards.length;
    const visibleCards = 4;
    let currentIndex = 0;
    
    function updateSlider() {
        const cardWidth = cards[0].offsetWidth;
        const gap = 24; 
        const offset = currentIndex * (cardWidth + gap);
        slider.style.transform = `translateX(-${offset}px)`;
        
        prevBtn.disabled = currentIndex === 0;
        nextBtn.disabled = currentIndex >= totalCards - visibleCards;
    }
    
    prevBtn.addEventListener('click', function() {
        if (currentIndex > 0) {
            currentIndex--;
            updateSlider();
        }
    });
    
    nextBtn.addEventListener('click', function() {
        if (currentIndex < totalCards - visibleCards) {
            currentIndex++;
            updateSlider();
        }
    });
    
    window.addEventListener('resize', updateSlider);
    updateSlider();
});
