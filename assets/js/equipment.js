/**
 * Equipment related JavaScript for Equipment Tracking System
 */

// Document ready function
document.addEventListener('DOMContentLoaded', function() {
    // Initialize equipment forms
    initializeEquipmentForm();
    
    // Initialize equipment search/filter functionality
    initializeEquipmentSearch();
});

/**
 * Initialize equipment form with validation and dynamic behavior
 */
function initializeEquipmentForm() {
    const form = document.querySelector('form[action*="equipment_detail.php"]');
    
    if (!form) return;
    
    form.addEventListener('submit', function(event) {
        const nameInput = form.querySelector('input[name="name"]');
        const statusSelect = form.querySelector('select[name="status"]');
        
        let hasErrors = false;
        
        // Validate required fields
        if (!nameInput.value.trim()) {
            showFieldError(nameInput, 'Equipment name is required');
            hasErrors = true;
        } else {
            clearFieldError(nameInput);
        }
        
        if (!statusSelect.value) {
            showFieldError(statusSelect, 'Status is required');
            hasErrors = true;
        } else {
            clearFieldError(statusSelect);
        }
        
        if (hasErrors) {
            event.preventDefault();
        }
    });
}

/**
 * Initialize equipment search and filter functionality
 */
function initializeEquipmentSearch() {
    const searchForm = document.querySelector('.search-form');
    
    if (!searchForm) return;
    
    // Add keypress event for search input to submit on enter
    const searchInput = searchForm.querySelector('input[name="search"]');
    if (searchInput) {
        searchInput.addEventListener('keypress', function(event) {
            if (event.key === 'Enter') {
                searchForm.submit();
            }
        });
    }
    
    // Add change event for status filter to auto-submit
    const statusFilter = searchForm.querySelector('select[name="status"]');
    if (statusFilter) {
        statusFilter.addEventListener('change', function() {
            searchForm.submit();
        });
    }
}

/**
 * Show error for a specific form field
 */
function showFieldError(field, message) {
    // Clear previous error
    clearFieldError(field);
    
    // Create error message element
    const errorElement = document.createElement('div');
    errorElement.className = 'field-error';
    errorElement.textContent = message;
    
    // Insert after the field
    field.parentNode.insertBefore(errorElement, field.nextSibling);
    
    // Add error class to field
    field.classList.add('error');
}

/**
 * Clear error for a specific form field
 */
function clearFieldError(field) {
    // Remove error class from field
    field.classList.remove('error');
    
    // Find and remove error message if exists
    const errorElement = field.parentNode.querySelector('.field-error');
    if (errorElement) {
        errorElement.parentNode.removeChild(errorElement);
    }
}