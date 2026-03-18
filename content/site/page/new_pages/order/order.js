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
