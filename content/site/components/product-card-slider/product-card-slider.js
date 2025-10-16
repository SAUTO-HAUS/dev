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
        let currentX = 0;
        let isDragging = false;
        
        this.track.addEventListener('touchstart', (e) => {
            startX = e.touches[0].clientX;
            isDragging = true;
        }, { passive: true });
        
        this.track.addEventListener('touchmove', (e) => {
            if (!isDragging) return;
            currentX = e.touches[0].clientX;
        }, { passive: true });
        
        this.track.addEventListener('touchend', (e) => {
            if (!isDragging) return;
            isDragging = false;
            
            const diffX = startX - currentX;
            const threshold = 50; // Minimum swipe distance
            
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
        this.updateCounter();
        this.updateDots();
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
    initMobileCardSliders();
});

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
        }, { passive: true });
        
        this.track.addEventListener('touchend', (e) => {
            const endX = e.changedTouches[0].clientX;
            const endTime = Date.now();
            const diffX = startX - endX;
            const diffTime = endTime - startTime;
            
            // Only trigger if it's a horizontal swipe with reasonable distance
            if (hasMoved && Math.abs(diffX) > 30 && diffTime < 500) {
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
    }
    
    updateCounter() {
        if (this.counter) {
            this.counter.textContent = `${this.currentIndex + 1}/${this.totalSlides}`;
        }
    }
}

// Export for manual initialization (e.g., after AJAX content load)
window.initProductCardSliders = initProductCardSliders;
