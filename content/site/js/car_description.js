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
    var hiddenContent = button.previousElementSibling;
    var isVisible = hiddenContent.style.display !== 'none';
    
    if (isVisible) {
        hiddenContent.style.display = 'none';
        button.textContent = button.getAttribute('data-show');
    } else {
        hiddenContent.style.display = 'block';
        button.textContent = button.getAttribute('data-hide');
    }
}
