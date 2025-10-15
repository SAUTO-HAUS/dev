document.addEventListener('DOMContentLoaded', function () {
    const observerOptions = {
        rootMargin: '50px',
        threshold: 0.1
    };

    const observer = new IntersectionObserver((entries) => {
        entries.forEach((entry) => {
            if (entry.isIntersecting) {
                entry.target.classList.add('is-visible');
                
                if (entry.target.classList.contains('sale-faq')) {
                    initializeFAQ(entry.target);
                }
                
                
                if (entry.target.classList.contains('sale-section')) {
                    observer.unobserve(entry.target);
                }
            }
        });
    }, observerOptions);

  
    function initializeFAQ(faqSection) {
        const firstTab = faqSection.querySelector('.sale-faq__tab');
        if (firstTab && !firstTab.hasAttribute('data-initialized')) {
            firstTab.setAttribute('aria-expanded', 'true');
            firstTab.setAttribute('data-initialized', 'true');
        }
    }

    const animatedElements = document.querySelectorAll('#sale-page .sale-section.sale-animated, #sale-page .sale-card, #sale-page .sale-step, #sale-page .sale-compare__card');
    animatedElements.forEach((element) => observer.observe(element));

    const tabs = document.querySelectorAll('#sale-page .sale-faq__tab');
    tabs.forEach((tab) => {
        tab.addEventListener('click', () => toggleTab(tab));
        tab.addEventListener('keydown', (event) => {
            if (event.key === 'Enter' || event.key === ' ') {
                event.preventDefault();
                toggleTab(tab);
            }
        });
    });

    function toggleTab(activeTab) {
        const isExpanded = activeTab.getAttribute('aria-expanded') === 'true';
        tabs.forEach((tab) => {
            if (tab === activeTab) {
                tab.setAttribute('aria-expanded', (!isExpanded).toString());
            } else {
                tab.setAttribute('aria-expanded', 'false');
            }
        });
    }

    const snapContainers = document.querySelectorAll('#sale-page [data-mobile-snap]');
    snapContainers.forEach((container) => {
        let isDown = false;
        let startX, scrollLeft;
        let startTime, startScrollLeft;

        const handleStart = (e) => {
            isDown = true;
            container.classList.add('is-dragging');
            startX = (e.pageX || e.touches[0].pageX) - container.offsetLeft;
            scrollLeft = container.scrollLeft;
            startTime = Date.now();
            startScrollLeft = container.scrollLeft;
            container.style.scrollBehavior = 'auto';
        };

        const handleEnd = (e) => {
            if (!isDown) return;
            isDown = false;
            container.classList.remove('is-dragging');
            
            const endTime = Date.now();
            const timeDiff = endTime - startTime;
            const scrollDiff = container.scrollLeft - startScrollLeft;
            
            if (timeDiff < 300 && Math.abs(scrollDiff) > 30) {
                const momentum = scrollDiff * 2;
                container.scrollLeft += momentum;
            }
            
            setTimeout(() => {
                container.style.scrollBehavior = 'smooth';
            }, 100);
        };

        const handleMove = (e) => {
            if (!isDown) return;
            e.preventDefault();
            const x = (e.pageX || e.touches[0].pageX) - container.offsetLeft;
            const walk = (x - startX) * 1.5; // Increase sensitivity
            container.scrollLeft = scrollLeft - walk;
        };

        container.addEventListener('mousedown', handleStart);
        container.addEventListener('touchstart', handleStart, { passive: true });
        container.addEventListener('mouseleave', handleEnd);
        container.addEventListener('mouseup', handleEnd);
        container.addEventListener('touchend', handleEnd);
        container.addEventListener('mousemove', handleMove);
        container.addEventListener('touchmove', handleMove, { passive: false });
        
        container.addEventListener('contextmenu', (e) => e.preventDefault());
    });

    const touchElements = document.querySelectorAll('#sale-page .sale-card, #sale-page .sale-step, #sale-page .sale-compare__card');
    touchElements.forEach((element) => {
        element.addEventListener('touchstart', () => {
            element.classList.add('is-touched');
        });
        element.addEventListener('touchend', () => {
            element.classList.remove('is-touched');
        });
    });
});
