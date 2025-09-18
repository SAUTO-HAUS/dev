document.addEventListener('DOMContentLoaded', function () {
    var root = document.getElementById('sale-page');
    if (!root) {
        return;
    }

    var prefersReducedMotion = false;
    try {
        prefersReducedMotion = window.matchMedia && window.matchMedia('(prefers-reduced-motion: reduce)').matches;
    } catch (error) {
        prefersReducedMotion = false;
    }

    var animatedNodes = root.querySelectorAll('.sale-animate');
    if (!prefersReducedMotion && 'IntersectionObserver' in window) {
        var observer = new IntersectionObserver(function (entries, obs) {
            entries.forEach(function (entry) {
                if (entry.isIntersecting) {
                    entry.target.classList.add('is-visible');
                    obs.unobserve(entry.target);
                }
            });
        }, { threshold: 0.18, rootMargin: '0px 0px -10% 0px' });

        animatedNodes.forEach(function (node) {
            observer.observe(node);
        });
    } else {
        animatedNodes.forEach(function (node) {
            node.classList.add('is-visible');
        });
    }

    var formSection = root.querySelector('#sale-form');
    if (formSection) {
        var formTriggers = root.querySelectorAll('[data-sale-open]');
        var revealForm = function () {
            if (formSection.hasAttribute('hidden')) {
                formSection.removeAttribute('hidden');
            }
            formSection.setAttribute('aria-hidden', 'false');
            formSection.classList.add('is-visible');
            if (typeof formSection.scrollIntoView === 'function') {
                formSection.scrollIntoView({ behavior: prefersReducedMotion ? 'auto' : 'smooth', block: 'start' });
            }
        };

        formTriggers.forEach(function (trigger) {
            trigger.addEventListener('click', function (event) {
                event.preventDefault();
                revealForm();
            });
            trigger.addEventListener('keydown', function (event) {
                if (event.key === 'Enter' || event.key === ' ') {
                    event.preventDefault();
                    revealForm();
                }
            });
        });
    }

    var scrollContainers = root.querySelectorAll('[data-sale-scroll]');
    scrollContainers.forEach(function (container) {
        container.setAttribute('tabindex', '0');
        var isPointerActive = false;
        var pointerStartX = 0;
        var scrollStart = 0;

        container.addEventListener('pointerdown', function (event) {
            if (event.pointerType === 'mouse' || event.pointerType === 'touch' || event.pointerType === 'pen') {
                isPointerActive = true;
                pointerStartX = event.clientX;
                scrollStart = container.scrollLeft;
                container.classList.add('is-dragging');
                if (typeof container.setPointerCapture === 'function') {
                    container.setPointerCapture(event.pointerId);
                }
            }
        });

        container.addEventListener('pointermove', function (event) {
            if (!isPointerActive) {
                return;
            }
            var deltaX = pointerStartX - event.clientX;
            container.scrollLeft = scrollStart + deltaX;
        });

        var releasePointer = function (event) {
            if (!isPointerActive) {
                return;
            }
            isPointerActive = false;
            container.classList.remove('is-dragging');
            if (typeof container.releasePointerCapture === 'function' && container.hasPointerCapture(event.pointerId)) {
                container.releasePointerCapture(event.pointerId);
            }
        };

        container.addEventListener('pointerup', releasePointer);
        container.addEventListener('pointercancel', releasePointer);
        container.addEventListener('pointerleave', releasePointer);

        container.addEventListener('keydown', function (event) {
            if (event.key === 'ArrowRight') {
                container.scrollBy({ left: 240, behavior: prefersReducedMotion ? 'auto' : 'smooth' });
            } else if (event.key === 'ArrowLeft') {
                container.scrollBy({ left: -240, behavior: prefersReducedMotion ? 'auto' : 'smooth' });
            }
        });
    });

    var faqItems = root.querySelectorAll('.sale-faq__item');
    faqItems.forEach(function (item) {
        var question = item.querySelector('.sale-faq__question');
        var answer = item.querySelector('.sale-faq__answer');
        if (!question || !answer) {
            return;
        }

        question.addEventListener('click', function () {
            var expanded = question.getAttribute('aria-expanded') === 'true';
            question.setAttribute('aria-expanded', String(!expanded));
            if (expanded) {
                answer.hidden = true;
                item.classList.remove('is-open');
            } else {
                answer.hidden = false;
                item.classList.add('is-open');
            }
        });

        question.addEventListener('keydown', function (event) {
            if (event.key === 'Enter' || event.key === ' ') {
                event.preventDefault();
                question.click();
            }
        });
    });
});
