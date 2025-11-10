/**
 * Product Card Slider JavaScript
 * Handles image sliding functionality for product cards
 */

class ProductCardSlider {
    constructor(element) {
        this.slider = element;
        this.track = this.slider.querySelector('.product-card-slider__track');
        this.slides = this.slider.querySelectorAll('.product-card-slider__slide');
        this.prevBtn = this.slider.querySelector('.product-card-slider__nav--prev');
        this.nextBtn = this.slider.querySelector('.product-card-slider__nav--next');
        this.dots = this.slider.querySelectorAll('.product-card-slider__dot');
        this.counter = this.slider.querySelector('.product-card-slider__counter');
        
        this.currentIndex = 0;
        this.totalSlides = this.slides.length;
        
        // Only initialize if there are multiple slides
        if (this.totalSlides > 1) {
            this.init();
        } else {
            this.slider.classList.add('product-card-slider--single');
        }
    }
    
    init() {
        this.bindEvents();
        this.updateCounter();
        this.updateDots();
        
        // Add touch support for mobile
        this.addTouchSupport();
    }
    
    bindEvents() {
        // Navigation buttons
        if (this.prevBtn) {
            this.prevBtn.addEventListener('click', (e) => {
                e.preventDefault();
                e.stopPropagation();
                this.prevSlide();
            });
        }
        
        if (this.nextBtn) {
            this.nextBtn.addEventListener('click', (e) => {
                e.preventDefault();
                e.stopPropagation();
                this.nextSlide();
            });
        }
        
        // Dots navigation
        this.dots.forEach((dot, index) => {
            dot.addEventListener('click', (e) => {
                e.preventDefault();
                e.stopPropagation();
                this.goToSlide(index);
            });
        });
        
        // Prevent card link click when interacting with slider
        this.slider.addEventListener('click', (e) => {
            if (e.target.closest('.product-card-slider__nav') || 
                e.target.closest('.product-card-slider__dot')) {
                e.preventDefault();
                e.stopPropagation();
            }
        });
    }
    
    addTouchSupport() {
        let startX = 0;
        let startY = 0;
        let isDragging = false;
        
        this.track.addEventListener('touchstart', (e) => {
            startX = e.touches[0].clientX;
            startY = e.touches[0].clientY;
            isDragging = true;
        }, { passive: true });
        
        this.track.addEventListener('touchmove', (e) => {
            if (!isDragging) return;
            
            const currentX = e.touches[0].clientX;
            const currentY = e.touches[0].clientY;
            
            const diffX = Math.abs(startX - currentX);
            const diffY = Math.abs(startY - currentY);
            
            // If vertical movement is greater than horizontal, allow scrolling
            if (diffY > diffX) {
                return;
            }
            
            // Prevent default only for horizontal swipes
            if (diffX > 30) {
                e.preventDefault();
            }
        }, { passive: false });
        
        this.track.addEventListener('touchend', (e) => {
            if (!isDragging) return;
            isDragging = false;
            
            const currentX = e.changedTouches[0].clientX;
            const diffX = startX - currentX;
            const threshold = 30; // Minimum swipe distance
            
            // Only handle horizontal swipes
            const currentY = e.changedTouches[0].clientY;
            const diffY = Math.abs(startY - currentY);
            
            // If vertical movement is greater than horizontal, don't handle swipe
            if (diffY > Math.abs(diffX)) {
                return;
            }
            
            if (Math.abs(diffX) > threshold) {
                if (diffX > 0) {
                    this.nextSlide();
                } else {
                    this.prevSlide();
                }
            }
        });
    }
    
    prevSlide() {
        this.currentIndex = this.currentIndex > 0 ? this.currentIndex - 1 : this.totalSlides - 1;
        this.updateSlider();
    }
    
    nextSlide() {
        this.currentIndex = this.currentIndex < this.totalSlides - 1 ? this.currentIndex + 1 : 0;
        this.updateSlider();
    }
    
    goToSlide(index) {
        this.currentIndex = index;
        this.updateSlider();
    }
    
    updateSlider() {
        const translateX = -this.currentIndex * 100;
        this.track.style.transform = `translateX(${translateX}%)`;
        this.updateDots();
        this.updateLineIndicator();
    }
    
    updateLineIndicator() {
        const lineIndicator = this.slider.querySelector('.product-card-slider__line-indicator');
        if (lineIndicator) {
            const progress = ((this.currentIndex + 1) / this.totalSlides) * 100;
            lineIndicator.style.setProperty('--progress', `${progress}%`);
        }
    }
    
    updateCounter() {
        if (this.counter) {
            this.counter.textContent = `${this.currentIndex + 1}/${this.totalSlides}`;
        }
    }
    
    updateDots() {
        this.dots.forEach((dot, index) => {
            dot.classList.toggle('product-card-slider__dot--active', index === this.currentIndex);
        });
    }
}

// Auto-initialize sliders when DOM is ready
document.addEventListener('DOMContentLoaded', function() {
    initProductCardSliders();
    
    // Only initialize mobile sliders on mobile devices
    if (window.innerWidth <= 768 || /Mobile|Android|iPhone|iPad/.test(navigator.userAgent)) {
        initMobileCardSliders();
    }
    
    initLazyLoading();
    initOfferTimers();
});

// Timer countdown functionality for offer timers
function initOfferTimers() {
    function updateTimers() {
        const timerDisplays = document.querySelectorAll('.timer-display');
        timerDisplays.forEach(function(display) {
            const endTime = parseInt(display.getAttribute('data-end-time'));
            const now = Math.floor(Date.now() / 1000);
            const remaining = endTime - now;
            
            if (remaining > 0) {
                const days = Math.floor(remaining / 86400);
                const hours = Math.floor((remaining % 86400) / 3600);
                const minutes = Math.floor((remaining % 3600) / 60);
                display.textContent = String(days).padStart(2, '0') + ':' + 
                                     String(hours).padStart(2, '0') + ':' + 
                                     String(minutes).padStart(2, '0');
            } else {
                // Timer expired - reload page to show "Oferta a expirat"
                location.reload();
            }
        });
    }
    
    // Update timers every minute
    if (document.querySelectorAll('.timer-display').length > 0) {
        setInterval(updateTimers, 60000);
    }
}

// Lazy loading for mobile slider images
function initLazyLoading() {
    const lazyImages = document.querySelectorAll('img.lazy-load');
    
    if ('IntersectionObserver' in window) {
        const imageObserver = new IntersectionObserver((entries, observer) => {
            entries.forEach(entry => {
                if (entry.isIntersecting) {
                    const img = entry.target;
                    img.src = img.dataset.src;
                    img.classList.remove('lazy-load');
                    imageObserver.unobserve(img);
                }
            });
        });
        
        lazyImages.forEach(img => imageObserver.observe(img));
    } else {
        // Fallback for older browsers
        lazyImages.forEach(img => {
            img.src = img.dataset.src;
            img.classList.remove('lazy-load');
        });
    }
}

// Function to initialize all product card sliders
function initProductCardSliders() {
    const sliders = document.querySelectorAll('.product-card-slider');
    sliders.forEach(slider => {
        if (!slider.hasAttribute('data-slider-initialized')) {
            new ProductCardSlider(slider);
            slider.setAttribute('data-slider-initialized', 'true');
        }
    });
}

// Function to initialize mobile card sliders
function initMobileCardSliders() {
    const sliders = document.querySelectorAll('.mobile-card-slider');
    sliders.forEach(slider => {
        if (!slider.hasAttribute('data-slider-initialized')) {
            new MobileCardSlider(slider);
            slider.setAttribute('data-slider-initialized', 'true');
        }
    });
}

/**
 * Mobile Card Slider Class
 * Simplified version for mobile devices
 */
class MobileCardSlider {
    constructor(element) {
        this.slider = element;
        this.track = this.slider.querySelector('.mobile-card-slider__track');
        this.slides = this.slider.querySelectorAll('.mobile-card-slider__slide');
        this.prevBtn = this.slider.querySelector('.mobile-card-slider__nav--prev');
        this.nextBtn = this.slider.querySelector('.mobile-card-slider__nav--next');
        this.counter = this.slider.querySelector('.mobile-card-slider__counter');
        
        this.currentIndex = 0;
        this.totalSlides = this.slides.length;
        
        // Only initialize if there are multiple slides
        if (this.totalSlides > 1) {
            this.init();
        } else {
            this.slider.classList.add('mobile-card-slider--single');
        }
    }
    
    init() {
        this.bindEvents();
        this.updateCounter();
        
        // Add touch support
        this.addSimpleTouchSupport();
    }
    
    bindEvents() {
        // No navigation buttons - slider is display only
        // Just show multiple images with counter
    }
    
    addSimpleTouchSupport() {
        let startX = 0;
        let startY = 0;
        let startTime = 0;
        let hasMoved = false;
        
        this.track.addEventListener('touchstart', (e) => {
            startX = e.touches[0].clientX;
            startY = e.touches[0].clientY;
            startTime = Date.now();
            hasMoved = false;
        }, { passive: true });
        
        this.track.addEventListener('touchmove', (e) => {
            if (!hasMoved) {
                const currentX = e.touches[0].clientX;
                const currentY = e.touches[0].clientY;
                const diffX = Math.abs(startX - currentX);
                const diffY = Math.abs(startY - currentY);
                
                // If vertical movement is dominant, allow scroll
                if (diffY > diffX && diffY > 10) {
                    hasMoved = true;
                    return; // Allow vertical scroll
                }
                
                // If horizontal movement is significant, mark as moved
                if (diffX > 10) {
                    hasMoved = true;
                }
            }
            
            // Allow vertical scrolling by not preventing default
        }, { passive: true });
        
        this.track.addEventListener('touchend', (e) => {
            const endX = e.changedTouches[0].clientX;
            const endY = e.changedTouches[0].clientY;
            const endTime = Date.now();
            const diffX = startX - endX;
            const diffY = Math.abs(startY - endY);
            const diffTime = endTime - startTime;
            
            // Only trigger if it's a horizontal swipe with reasonable distance and time
            // and if vertical movement is not dominant
            if (hasMoved && Math.abs(diffX) > 30 && diffTime < 500 && diffY < Math.abs(diffX)) {
                if (diffX > 0) {
                    this.nextSlide();
                } else {
                    this.prevSlide();
                }
                // Prevent card click after swipe
                e.preventDefault();
                e.stopPropagation();
            }
        }, { passive: false });
    }
    
    addTouchSupport() {
        let startX = 0;
        let startY = 0;
        let currentX = 0;
        let currentY = 0;
        let isDragging = false;
        let isHorizontalSwipe = false;
        
        this.track.addEventListener('touchstart', (e) => {
            startX = e.touches[0].clientX;
            startY = e.touches[0].clientY;
            isDragging = true;
            isHorizontalSwipe = false;
        }, { passive: true });
        
        this.track.addEventListener('touchmove', (e) => {
            if (!isDragging) return;
            
            currentX = e.touches[0].clientX;
            currentY = e.touches[0].clientY;
            
            const diffX = Math.abs(startX - currentX);
            const diffY = Math.abs(startY - currentY);
            
            // Only consider horizontal swipe if it's VERY clearly horizontal
            if (diffX > 30 && diffX > diffY * 2) {
                // Very clear horizontal swipe
                isHorizontalSwipe = true;
            } else if (diffY > 10) {
                // Any vertical movement - stop slider interaction completely
                isDragging = false;
                isHorizontalSwipe = false;
                return;
            }
            
            // NEVER prevent default - always allow scroll
        }, { passive: true });
        
        this.track.addEventListener('touchend', (e) => {
            if (!isDragging) return;
            isDragging = false;
            
            if (isHorizontalSwipe) {
                const diffX = startX - currentX;
                const threshold = 30; // Reduced threshold for better responsiveness
                
                if (Math.abs(diffX) > threshold) {
                    if (diffX > 0) {
                        this.nextSlide();
                    } else {
                        this.prevSlide();
                    }
                    // Prevent card click after swipe
                    e.preventDefault();
                    e.stopPropagation();
                }
            }
            
            isHorizontalSwipe = false;
        });
    }
    
    prevSlide() {
        this.currentIndex = this.currentIndex > 0 ? this.currentIndex - 1 : this.totalSlides - 1;
        this.updateSlider();
    }
    
    nextSlide() {
        this.currentIndex = this.currentIndex < this.totalSlides - 1 ? this.currentIndex + 1 : 0;
        this.updateSlider();
    }
    
    updateSlider() {
        const translateX = -this.currentIndex * 100;
        this.track.style.transform = `translateX(${translateX}%)`;
        this.updateCounter();
        this.updateLineIndicator();
    }
    
    updateLineIndicator() {
        const lineIndicator = this.slider.querySelector('.mobile-card-slider__line-indicator');
        if (lineIndicator) {
            const progress = ((this.currentIndex + 1) / this.totalSlides) * 100;
            lineIndicator.style.setProperty('--progress', `${progress}%`);
        }
    }
    
    updateCounter() {
        if (this.counter) {
            this.counter.textContent = `${this.currentIndex + 1}/${this.totalSlides}`;
        }
    }
}

// Export for manual initialization (e.g., after AJAX content load)
window.initProductCardSliders = initProductCardSliders;
