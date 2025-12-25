/**
 * Toggle accordion open/close
 * @param {HTMLElement} header - The accordion header element
 */
function toggleAccordion(header) {
    var content = header.nextElementSibling;
    var isActive = header.classList.contains('active');
    
    if (isActive) {
        header.classList.remove('active');
        content.classList.remove('active');
    } else {
        header.classList.add('active');
        content.classList.add('active');
    }
}

/**
 * Toggle full description visibility on desktop
 * @param {HTMLElement} button - The button element
 */
function toggleFullDescription(button) {
    var block = button.closest('.car-description-block');
    var isExpanded = block.classList.contains('expanded');
    
    if (isExpanded) {
        block.classList.remove('expanded');
        button.textContent = button.getAttribute('data-show');
    } else {
        block.classList.add('expanded');
        button.textContent = button.getAttribute('data-hide');
    }
}
