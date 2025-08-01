$(document).ready(function () {
    $(".owl-carousel").owlCarousel({
        center: true,
        items: 1,
        loop: true,
        nav: true,
        margin: 42,
        responsive: {
            900: {
                items: 2
            },
            1200: {
                items: 3
            }
        }
    });


});
document.addEventListener('DOMContentLoaded', function () {

    const accordions = document.querySelectorAll('.trade-in__faq-item');

    accordions.forEach(accordion => {
        const title = accordion.querySelector('.trade-in__faq-title');
        const description = accordion.querySelector('.trade-in__faq-description');
        title.addEventListener('click', () => {
            if (accordion.classList.contains('active')) {
                accordion.classList.remove('active');
            } else {
                accordions.forEach(accordion => {
                    accordion.classList.remove('active');
                });
                accordion.classList.add('active');
            }
        });
    });
});