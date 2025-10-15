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


    const touchElements = document.querySelectorAll('#sale-page .sale-card, #sale-page .sale-step, #sale-page .sale-compare__card');
    touchElements.forEach((element) => {
        element.addEventListener('touchstart', () => {
            element.classList.add('is-touched');
        });
        element.addEventListener('touchend', () => {
            element.classList.remove('is-touched');
        });
    });

    if (window.innerWidth <= 768) {
        const stepsContainer = document.querySelector('.sale-steps');
        const saleHow = document.querySelector('.sale-how');
        
        if (stepsContainer && saleHow) {
            let hasInteracted = false;
            
            const hideHint = () => {
                if (!hasInteracted) {
                    hasInteracted = true;
                    saleHow.style.setProperty('--hint-opacity', '0');
                }
            };
            
            stepsContainer.addEventListener('scroll', hideHint);
            stepsContainer.addEventListener('touchstart', hideHint);
        }
    }
});
