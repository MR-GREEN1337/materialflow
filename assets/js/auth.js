/**
 * Authentication related JavaScript for Equipment Tracking System
 */

// Document ready function
document.addEventListener('DOMContentLoaded', function() {
    // Initialize login form
    const loginForm = document.querySelector('form[action="login.php"]');
    
    if (loginForm) {
        initializeLoginForm(loginForm);
    }
});

/**
 * Initialize login form with validation
 */
function initializeLoginForm(form) {
    form.addEventListener('submit', function(event) {
        const studentIdInput = form.querySelector('input[name="student_id"]');
        
        if (!studentIdInput.value.trim()) {
            event.preventDefault();
            showError('Please enter your student ID');
            studentIdInput.focus();
        }
    });
}

/**
 * Show error message
 */
function showError(message) {
    // Check if error container already exists
    let errorContainer = document.querySelector('.error-message');
    
    if (!errorContainer) {
        // Create error container
        errorContainer = document.createElement('div');
        errorContainer.className = 'error-message';
        
        // Insert it before the form
        const form = document.querySelector('form');
        form.parentNode.insertBefore(errorContainer, form);
    }
    
    // Set error message
    errorContainer.textContent = message;
}