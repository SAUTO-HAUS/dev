document.addEventListener('DOMContentLoaded', function() {
    const sliderTrack = document.querySelector('.order-slider-track');
    const prevBtn = document.querySelector('.order-slider-prev');
    const nextBtn = document.querySelector('.order-slider-next');
    const cards = document.querySelectorAll('.order-slider-card');
    
    if (!sliderTrack || !prevBtn || !nextBtn || cards.length === 0) return;
    
    const cardsToShow = 4;
    const totalCards = cards.length;
    const firstClones = [];
    const lastClones = [];
    
    for (let i = 0; i < cardsToShow; i++) {
        const firstClone = cards[i].cloneNode(true);
        const lastClone = cards[totalCards - 1 - i].cloneNode(true);
        firstClones.push(firstClone);
        lastClones.unshift(lastClone);
    }
    
    lastClones.forEach(clone => sliderTrack.insertBefore(clone, sliderTrack.firstChild));
    firstClones.forEach(clone => sliderTrack.appendChild(clone));
    
    let currentIndex = cardsToShow;
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
        const cardWidth = getCardWidth();
        
        if (currentIndex >= totalCards + cardsToShow) {
            isTransitioning = true;
            currentIndex = cardsToShow;
            updateSlider(false);
            setTimeout(() => { isTransitioning = false; }, 50);
        }
        
        if (currentIndex < cardsToShow) {
            isTransitioning = true;
            currentIndex = totalCards + cardsToShow - 1;
            updateSlider(false);
            setTimeout(() => { isTransitioning = false; }, 50);
        }
    }
    
    sliderTrack.addEventListener('transitionend', handleTransitionEnd);
    
    prevBtn.addEventListener('click', function() {
        if (isTransitioning) return;
        currentIndex--;
        updateSlider(true);
    });
    
    nextBtn.addEventListener('click', function() {
        if (isTransitioning) return;
        currentIndex++;
        updateSlider(true);
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
