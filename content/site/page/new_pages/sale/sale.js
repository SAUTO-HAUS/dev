document.addEventListener('DOMContentLoaded', function () {
    var root = document.getElementById('sale-root');
    if (!root) {
        return;
    }

    var animatedNodes = root.querySelectorAll('.sale-animate');
    var prefersReducedMotion = false;
    try {
        prefersReducedMotion = window.matchMedia && window.matchMedia('(prefers-reduced-motion: reduce)').matches;
    } catch (error) {
        prefersReducedMotion = false;
    }

    if (!prefersReducedMotion && 'IntersectionObserver' in window) {
        var observer = new IntersectionObserver(function (entries) {
            entries.forEach(function (entry) {
                if (entry.isIntersecting) {
                    entry.target.classList.add('is-visible');
                    observer.unobserve(entry.target);
                }
            });
        }, {
            threshold: 0.18,
            rootMargin: '0px 0px -10% 0px'
        });

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
        var formTriggers = root.querySelectorAll('[data-sale-form-trigger]');
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

    var scrollableBlocks = root.querySelectorAll('[data-sale-scrollable]');
    scrollableBlocks.forEach(function (block) {
        block.setAttribute('tabindex', '0');
        block.style.scrollBehavior = 'smooth';
        var isPointerActive = false;
        var pointerStartX = 0;
        var storedScrollLeft = 0;

        block.addEventListener('pointerdown', function (event) {
            if (event.pointerType === 'mouse' || event.pointerType === 'touch' || event.pointerType === 'pen') {
                isPointerActive = true;
                pointerStartX = event.clientX;
                storedScrollLeft = block.scrollLeft;
                block.classList.add('is-dragging');
                block.setPointerCapture(event.pointerId);
                block.style.cursor = 'grabbing';
            }
        });

        block.addEventListener('pointermove', function (event) {
            if (!isPointerActive) {
                return;
            }
            var deltaX = pointerStartX - event.clientX;
            block.scrollLeft = storedScrollLeft + deltaX;
        });

        var releasePointer = function (event) {
            if (!isPointerActive) {
                return;
            }
            isPointerActive = false;
            block.classList.remove('is-dragging');
            block.style.cursor = '';
            if (typeof block.releasePointerCapture === 'function' && block.hasPointerCapture(event.pointerId)) {
                block.releasePointerCapture(event.pointerId);
            }
        };

        block.addEventListener('pointerup', releasePointer);
        block.addEventListener('pointercancel', releasePointer);
        block.addEventListener('pointerleave', releasePointer);

        block.addEventListener('keydown', function (event) {
            if (event.key === 'ArrowRight') {
                block.scrollBy({ left: 220, behavior: prefersReducedMotion ? 'auto' : 'smooth' });
            } else if (event.key === 'ArrowLeft') {
                block.scrollBy({ left: -220, behavior: prefersReducedMotion ? 'auto' : 'smooth' });
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
    });
});
