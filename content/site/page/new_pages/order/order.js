document.addEventListener('DOMContentLoaded', function() {
    const sliderTrack = document.querySelector('.order-slider-track');
    const prevBtn = document.querySelector('.order-slider-prev');
    const nextBtn = document.querySelector('.order-slider-next');
    const cards = document.querySelectorAll('.order-slider-card');
    
    if (!sliderTrack || !prevBtn || !nextBtn || cards.length === 0) return;
    
    let currentIndex = 0;
    const cardsToShow = 4;
    const totalCards = cards.length;
    const maxIndex = Math.max(0, totalCards - cardsToShow);
    
    function updateSlider() {
        const cardWidth = cards[0].offsetWidth;
        const gap = 24; 
        const offset = currentIndex * (cardWidth + gap);
        sliderTrack.style.transform = `translateX(-${offset}px)`;
        
        prevBtn.disabled = currentIndex === 0;
        nextBtn.disabled = currentIndex >= maxIndex;
        
        prevBtn.style.opacity = currentIndex === 0 ? '0.5' : '1';
        nextBtn.style.opacity = currentIndex >= maxIndex ? '0.5' : '1';
    }
    
    prevBtn.addEventListener('click', function() {
        if (currentIndex > 0) {
            currentIndex--;
            updateSlider();
        }
    });
    
    nextBtn.addEventListener('click', function() {
        if (currentIndex < maxIndex) {
            currentIndex++;
            updateSlider();
        }
    });
    
    let resizeTimer;
    window.addEventListener('resize', function() {
        clearTimeout(resizeTimer);
        resizeTimer = setTimeout(function() {
            updateSlider();
        }, 250);
    });
    
    updateSlider();
});
