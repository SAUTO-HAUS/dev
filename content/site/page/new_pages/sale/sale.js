document.addEventListener('DOMContentLoaded', function () {
    var animated = document.querySelectorAll('.sale-animate');
    if ('IntersectionObserver' in window) {
        var observer = new IntersectionObserver(function (entries) {
            entries.forEach(function (entry) {
                if (entry.isIntersecting) {
                    entry.target.classList.add('is-visible');
                    observer.unobserve(entry.target);
                }
            });
        }, {
            threshold: 0.2,
            rootMargin: '0px 0px -10% 0px'
        });
        animated.forEach(function (element) {
            observer.observe(element);
        });
    } else {
        animated.forEach(function (element) {
            element.classList.add('is-visible');
        });
    }

    var scrollButtons = document.querySelectorAll('[data-sale-scroll]');
    scrollButtons.forEach(function (button) {
        button.addEventListener('click', function (event) {
            var selector = button.getAttribute('data-sale-scroll');
            if (!selector) {
                return;
            }
            var target = document.querySelector(selector);
            if (target) {
                event.preventDefault();
                if (typeof target.scrollIntoView === 'function') {
                    target.scrollIntoView({ behavior: 'smooth', block: 'start' });
                } else {
                    window.scrollTo({ top: target.offsetTop, behavior: 'smooth' });
                }
            }
        });
    });

    var formWrapper = document.querySelector('#sale-form');
    var formTriggers = document.querySelectorAll('[data-sale-form-trigger]');
    if (formWrapper && formTriggers.length) {
        formTriggers.forEach(function (trigger) {
            trigger.addEventListener('click', function (event) {
                event.preventDefault();
                if (formWrapper.hasAttribute('hidden')) {
                    formWrapper.removeAttribute('hidden');
                    formWrapper.setAttribute('aria-hidden', 'false');
                    formWrapper.classList.add('is-open');
                    formWrapper.classList.add('is-visible');
                }
                if (typeof formWrapper.scrollIntoView === 'function') {
                    formWrapper.scrollIntoView({ behavior: 'smooth', block: 'start' });
                } else {
                    window.scrollTo({ top: formWrapper.offsetTop, behavior: 'smooth' });
                }
            });
        });
    }

    var scrollables = document.querySelectorAll('[data-sale-scrollable]');
    scrollables.forEach(function (list) {
        list.setAttribute('tabindex', '0');
        var pointerActive = false;
        var startX = 0;
        var scrollLeft = 0;

        list.addEventListener('pointerdown', function (event) {
            pointerActive = true;
            startX = event.clientX;
            scrollLeft = list.scrollLeft;
            list.setPointerCapture(event.pointerId);
            list.classList.add('is-dragging');
        });

        list.addEventListener('pointermove', function (event) {
            if (!pointerActive) {
                return;
            }
            var dx = startX - event.clientX;
            list.scrollLeft = scrollLeft + dx;
        });

        var cancelPointer = function (event) {
            if (!pointerActive) {
                return;
            }
            pointerActive = false;
            if (typeof list.releasePointerCapture === 'function' && typeof list.hasPointerCapture === 'function' && list.hasPointerCapture(event.pointerId)) {
                list.releasePointerCapture(event.pointerId);
            }
            list.classList.remove('is-dragging');
        };

        list.addEventListener('pointerup', cancelPointer);
        list.addEventListener('pointercancel', cancelPointer);
        list.addEventListener('pointerleave', cancelPointer);

        list.addEventListener('keydown', function (event) {
            if (event.key === 'ArrowRight') {
                list.scrollBy({ left: 200, behavior: 'smooth' });
            } else if (event.key === 'ArrowLeft') {
                list.scrollBy({ left: -200, behavior: 'smooth' });
            }
        });
    });

    var faqItems = document.querySelectorAll('.sale-faq__item');
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
