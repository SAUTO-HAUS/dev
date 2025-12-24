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
