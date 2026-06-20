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
        this.updateLineIndicator();

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
        if (!lineIndicator) return;

        let segments = lineIndicator.querySelectorAll('.product-card-slider__line-indicator__segment');
        if (segments.length !== this.totalSlides) {
            lineIndicator.innerHTML = '';
            for (let i = 0; i < this.totalSlides; i++) {
                const seg = document.createElement('div');
                seg.className = 'product-card-slider__line-indicator__segment';
                lineIndicator.appendChild(seg);
            }
            segments = lineIndicator.querySelectorAll('.product-card-slider__line-indicator__segment');
        }
        segments.forEach((seg, i) => {
            seg.classList.toggle('product-card-slider__line-indicator__segment--active', i === this.currentIndex);
        });
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
    initSliderLazyLoading();
});

// Lazy loading for slider images - loads images when card enters viewport or user interacts
function initSliderLazyLoading() {
    const sliders = document.querySelectorAll('.product-card-slider[data-lazy-load="pending"], .mobile-card-slider[data-lazy-load="pending"]');
    
    if ('IntersectionObserver' in window) {
        const sliderObserver = new IntersectionObserver((entries, observer) => {
            entries.forEach(entry => {
                if (entry.isIntersecting) {
                    const slider = entry.target;
                    loadSliderImages(slider);
                    slider.setAttribute('data-lazy-load', 'loaded');
                    sliderObserver.unobserve(slider);
                }
            });
        }, {
            rootMargin: '50px' // Start loading slightly before card enters viewport
        });
        
        sliders.forEach(slider => {
            sliderObserver.observe(slider);
            
            // Also load images on first interaction (touch/click)
            const loadOnInteraction = () => {
                if (slider.getAttribute('data-lazy-load') === 'pending') {
                    loadSliderImages(slider);
                    slider.setAttribute('data-lazy-load', 'loaded');
                }
            };
            
            slider.addEventListener('touchstart', loadOnInteraction, { once: true, passive: true });
            slider.addEventListener('mouseenter', loadOnInteraction, { once: true });
        });
    } else {
        // Fallback for older browsers - load all immediately
        sliders.forEach(slider => {
            loadSliderImages(slider);
            slider.setAttribute('data-lazy-load', 'loaded');
        });
    }
}

// Helper function to load all images in a slider
function loadSliderImages(slider) {
    const images = slider.querySelectorAll('img[data-src]');
    images.forEach(img => {
        if (img.dataset.src) {
            img.src = img.dataset.src;
            img.removeAttribute('data-src');
        }
    });
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
        this.updateLineIndicator();

        // Add touch support
        this.addSimpleTouchSupport();
    }
    
    bindEvents() {
        // No navigation buttons - slider is display only
        // Just show multiple images with counter
    }
    
    addSimpleTouchSupport() {
        // Per-touch state. Tracked by identifier so a second finger / a fresh
        // gesture can't inherit stale values — the main cause of "the 2nd swipe
        // doesn't work" on iOS Safari, where touchend sometimes fires without a
        // clean reset.
        let startX = 0;
        let startY = 0;
        let trackWidth = 0;
        let isDragging = false;
        let axisLocked = null; // null | 'x' | 'y'
        let lastDeltaX = 0;
        let didSwipe = false;
        let pointerId = null;
        let startTime = 0;
        const AXIS_LOCK_PX = 6;       // a touch counts as a gesture past this
        const SWIPE_RATIO = 0.10;
        const FLICK_VELOCITY = 0.3;

        let watchdog = null;

        const reset = () => {
            isDragging = false;
            axisLocked = null;
            pointerId = null;
            if (watchdog) { clearTimeout(watchdog); watchdog = null; }
            this.track.classList.remove('is-dragging');
        };

        const onTouchStart = (e) => {
            if (isDragging) { reset(); this.updateSlider(); }
            const t = e.changedTouches[0];
            pointerId = t.identifier;
            startX = t.clientX;
            startY = t.clientY;
            startTime = Date.now();
            trackWidth = this.track.offsetWidth || 1;
            isDragging = true;
            axisLocked = null;
            lastDeltaX = 0;
            didSwipe = false;
            if (watchdog) clearTimeout(watchdog);
            watchdog = setTimeout(() => {
                if (isDragging) { reset(); this.updateSlider(); }
            }, 1200);
        };

        // Find this gesture's touch by identifier (ignore other fingers).
        const findTouch = (e) => {
            const list = e.changedTouches;
            for (let i = 0; i < list.length; i++) {
                if (list[i].identifier === pointerId) return list[i];
            }
            return null;
        };

        const onTouchMove = (e) => {
            if (!isDragging) return;
            const t = findTouch(e) || e.touches[0];
            if (!t) return;
            const dx = t.clientX - startX;
            const dy = t.clientY - startY;

            if (axisLocked === null) {
                if (Math.abs(dx) > AXIS_LOCK_PX || Math.abs(dy) > AXIS_LOCK_PX) {
                    // Lock to the dominant axis. Bias slightly toward horizontal
                    // so a near-diagonal swipe still flips the photo on iOS.
                    axisLocked = (Math.abs(dx) * 1.15 >= Math.abs(dy)) ? 'x' : 'y';
                    if (axisLocked === 'x') this.track.classList.add('is-dragging');
                } else {
                    return;
                }
            }

            if (axisLocked === 'y') return; // let the page scroll vertically

            let effectiveDx = dx;
            if (this.currentIndex === 0 && dx > 0) effectiveDx = dx * 0.35;
            else if (this.currentIndex === this.totalSlides - 1 && dx < 0) effectiveDx = dx * 0.35;

            lastDeltaX = effectiveDx;
            const basePct = -this.currentIndex * 100;
            const dragPct = (effectiveDx / trackWidth) * 100;
            this.track.style.transform = `translateX(${basePct + dragPct}%)`;

            // Owning the horizontal pan → stop the page from also scrolling.
            if (e.cancelable) e.preventDefault();
        };

        const onTouchEnd = (e) => {
            if (!isDragging) return;
            // Only react to the finger that started this gesture.
            if (pointerId !== null && !findTouch(e) && e.touches.length) return;

            const wasX = axisLocked === 'x';
            this.track.classList.remove('is-dragging');

            if (!wasX) { reset(); return; }

            const threshold = trackWidth * SWIPE_RATIO;
            const elapsed = Math.max(1, Date.now() - startTime);
            const velocity = Math.abs(lastDeltaX) / elapsed; // px/ms
            const isFlick = velocity >= FLICK_VELOCITY && Math.abs(lastDeltaX) > 10;

            if ((lastDeltaX <= -threshold || (isFlick && lastDeltaX < 0)) && this.currentIndex < this.totalSlides - 1) {
                this.currentIndex++;
                didSwipe = true;
            } else if ((lastDeltaX >= threshold || (isFlick && lastDeltaX > 0)) && this.currentIndex > 0) {
                this.currentIndex--;
                didSwipe = true;
            }
            // Always snap back to a whole slide (even if under threshold) so the
            // track never gets stuck mid-drag — the other iOS "stuck" symptom.
            this.updateSlider();
            reset();

            if (didSwipe && e.cancelable) e.preventDefault();
        };

        const onClickCapture = (e) => {
            if (didSwipe) {
                e.preventDefault();
                e.stopPropagation();
                didSwipe = false;
            }
        };

        this.slider.addEventListener('touchstart', onTouchStart, { passive: true });
        this.slider.addEventListener('touchmove', onTouchMove, { passive: false });
        this.slider.addEventListener('touchend', onTouchEnd, { passive: false });
        this.slider.addEventListener('touchcancel', () => {
            if (!isDragging) return;
            reset();
            this.updateSlider();
        }, { passive: true });
        this.slider.addEventListener('click', onClickCapture, true);
    }

    prevSlide() {
        if (this.currentIndex > 0) {
            this.currentIndex--;
            this.updateSlider();
        }
    }

    nextSlide() {
        if (this.currentIndex < this.totalSlides - 1) {
            this.currentIndex++;
            this.updateSlider();
        }
    }
    
    updateSlider() {
        const translateX = -this.currentIndex * 100;
        this.track.style.transform = `translateX(${translateX}%)`;
        this.updateCounter();
        this.updateLineIndicator();
    }
    
    updateLineIndicator() {
        const lineIndicator = this.slider.querySelector('.mobile-card-slider__line-indicator');
        if (!lineIndicator) return;

        let segments = lineIndicator.querySelectorAll('.mobile-card-slider__line-indicator__segment');
        if (segments.length !== this.totalSlides) {
            lineIndicator.innerHTML = '';
            for (let i = 0; i < this.totalSlides; i++) {
                const seg = document.createElement('div');
                seg.className = 'mobile-card-slider__line-indicator__segment';
                lineIndicator.appendChild(seg);
            }
            segments = lineIndicator.querySelectorAll('.mobile-card-slider__line-indicator__segment');
        }
        segments.forEach((seg, i) => {
            seg.classList.toggle('mobile-card-slider__line-indicator__segment--active', i === this.currentIndex);
        });
    }

    updateCounter() {
        if (this.counter) {
            this.counter.textContent = `${this.currentIndex + 1}/${this.totalSlides}`;
        }
    }
}

// Export for manual initialization (e.g., after AJAX content load)
window.initProductCardSliders = initProductCardSliders;
window.initSliderLazyLoading = initSliderLazyLoading;
