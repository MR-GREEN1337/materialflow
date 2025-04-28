/**
 * Main JavaScript file for Equipment Tracking System
 */

// Document ready function
document.addEventListener('DOMContentLoaded', function() {
    // Initialize any common components
    initializeFlashMessages();
    
    // Add navigation active class based on current page
    highlightCurrentNavItem();
});

/**
 * Initialize flash messages to auto-hide after a few seconds
 */
function initializeFlashMessages() {
    const flashMessages = document.querySelectorAll('.success-message, .error-message');
    
    flashMessages.forEach(message => {
        setTimeout(() => {
            message.style.opacity = '0';
            setTimeout(() => {
                message.style.display = 'none';
            }, 500);
        }, 5000);
    });
}

/**
 * Highlight the current navigation item based on URL
 */
function highlightCurrentNavItem() {
    const currentPath = window.location.pathname;
    const navLinks = document.querySelectorAll('nav ul li a');
    
    navLinks.forEach(link => {
        const linkPath = link.getAttribute('href');
        
        if (currentPath === linkPath || 
            (currentPath.includes('/pages/') && linkPath.includes(currentPath.split('/').pop().split('_')[0]))) {
            link.classList.add('active');
        }
    });
}