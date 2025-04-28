/**
 * Projects related JavaScript for Equipment Tracking System
 */

// Document ready function
document.addEventListener('DOMContentLoaded', function() {
    // Initialize project forms
    initializeProjectForm();
    
    // Initialize resource form handling
    initializeResourceForm();
    
    // Initialize project search/filter functionality
    initializeProjectSearch();
});

/**
 * Initialize project form with validation and dynamic behavior
 */
function initializeProjectForm() {
    const form = document.querySelector('form[action*="project_detail.php"]');
    
    if (!form || !form.querySelector('input[name="action"][value^="add_project"], input[name="action"][value^="edit_project"]')) return;
    
    form.addEventListener('submit', function(event) {
        const titleInput = form.querySelector('input[name="title"]');
        const statusSelect = form.querySelector('select[name="status"]');
        
        let hasErrors = false;
        
        // Validate required fields
        if (!titleInput.value.trim()) {
            showFieldError(titleInput, 'Project title is required');
            hasErrors = true;
        } else {
            clearFieldError(titleInput);
        }
        
        if (!statusSelect.value) {
            showFieldError(statusSelect, 'Status is required');
            hasErrors = true;
        } else {
            clearFieldError(statusSelect);
        }
        
        // Validate date range
        const startDate = form.querySelector('input[name="start_date"]');
        const endDate = form.querySelector('input[name="end_date"]');
        
        if (startDate.value && endDate.value && new Date(startDate.value) > new Date(endDate.value)) {
            showFieldError(endDate, 'End date cannot be before start date');
            hasErrors = true;
        }
        
        if (hasErrors) {
            event.preventDefault();
        }
    });
}

/**
 * Initialize resource form handling
 */
function initializeResourceForm() {
    const resourceForm = document.querySelector('form[action*="project_detail.php"] input[name="action"][value="add_resource"]');
    
    if (!resourceForm) return;
    
    const form = resourceForm.closest('form');
    
    // Resource type change handler to show/hide relevant fields
    const resourceTypeSelect = form.querySelector('select[name="resource_type"]');
    const fileUploadField = form.querySelector('.form-group:has(input[name="resource_file"])');
    const externalUrlField = form.querySelector('.form-group:has(input[name="external_url"])');
    
    if (resourceTypeSelect && fileUploadField && externalUrlField) {
        resourceTypeSelect.addEventListener('change', function() {
            const selectedValue = this.value;
            
            if (selectedValue === 'git_repository') {
                // For git repositories, prioritize external URL
                fileUploadField.style.display = 'none';
                externalUrlField.style.display = 'block';
            } else if (selectedValue === 'report' || selectedValue === 'presentation' || selectedValue === 'image' || selectedValue === 'video') {
                // For files, prioritize file upload
                fileUploadField.style.display = 'block';
                externalUrlField.style.display = 'block';
            } else {
                // For other types, show both options
                fileUploadField.style.display = 'block';
                externalUrlField.style.display = 'block';
            }
        });
    }
    
    // Form validation
    form.addEventListener('submit', function(event) {
        const titleInput = form.querySelector('input[name="resource_title"]');
        const typeSelect = form.querySelector('select[name="resource_type"]');
        const fileInput = form.querySelector('input[name="resource_file"]');
        const urlInput = form.querySelector('input[name="external_url"]');
        
        let hasErrors = false;
        
        // Validate required fields
        if (!titleInput.value.trim()) {
            showFieldError(titleInput, 'Resource title is required');
            hasErrors = true;
        } else {
            clearFieldError(titleInput);
        }
        
        if (!typeSelect.value) {
            showFieldError(typeSelect, 'Resource type is required');
            hasErrors = true;
        } else {
            clearFieldError(typeSelect);
        }
        
        // Check if either file or URL is provided (except for 'other' type)
        if (typeSelect.value !== 'other' && typeSelect.value !== '') {
            if (!fileInput.files.length && !urlInput.value.trim()) {
                showFieldError(fileInput, 'Please provide either a file or external URL');
                hasErrors = true;
            } else {
                clearFieldError(fileInput);
            }
        }
        
        if (hasErrors) {
            event.preventDefault();
        }
    });
}

/**
 * Initialize project search and filter functionality
 */
function initializeProjectSearch() {
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
    errorElement.style.color = '#e74c3c';
    errorElement.style.fontSize = '0.8rem';
    errorElement.style.marginTop = '0.25rem';
    
    // Insert after the field
    field.parentNode.insertBefore(errorElement, field.nextSibling);
    
    // Add error class to field
    field.classList.add('error');
    field.style.borderColor = '#e74c3c';
}

/**
 * Clear error for a specific form field
 */
function clearFieldError(field) {
    // Remove error class from field
    field.classList.remove('error');
    field.style.borderColor = '';
    
    // Find and remove error message if exists
    const errorElement = field.parentNode.querySelector('.field-error');
    if (errorElement) {
        errorElement.parentNode.removeChild(errorElement);
    }
}

/**
 * Confirm before deleting project
 */
function confirmDelete(projectId) {
    if (confirm('Are you sure you want to delete this project? This action cannot be undone.')) {
        window.location.href = 'project_detail.php?id=' + projectId + '&action=delete';
    }
}

/**
 * Show add resource form
 */
function showAddResourceForm() {
    document.getElementById('add-resource-form').style.display = 'block';
}

/**
 * Hide add resource form
 */
function hideAddResourceForm() {
    document.getElementById('add-resource-form').style.display = 'none';
}