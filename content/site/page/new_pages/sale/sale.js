document.addEventListener('DOMContentLoaded', function () {
    const animatedSections = document.querySelectorAll('#sale-page .sale-section.sale-animated');
    const accentElements = document.querySelectorAll('#sale-page .sale-card, #sale-page .sale-step, #sale-page .sale-compare__card');

    const sectionObserver = new IntersectionObserver((entries) => {
        entries.forEach((entry) => {
            if (entry.isIntersecting) {
                entry.target.classList.add('is-visible');
                sectionObserver.unobserve(entry.target);
            }
        });
    }, { threshold: 0.2 });

    const accentObserver = new IntersectionObserver((entries) => {
        entries.forEach((entry) => {
            if (entry.isIntersecting) {
                entry.target.classList.add('is-visible');
            } else {
                entry.target.classList.remove('is-visible');
            }
        });
    }, { threshold: 0.4 });

    animatedSections.forEach((section) => sectionObserver.observe(section));
    accentElements.forEach((element) => accentObserver.observe(element));

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
});
