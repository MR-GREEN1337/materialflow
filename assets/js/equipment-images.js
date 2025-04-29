/**
 * Equipment images related JavaScript for Equipment Tracking System
 */

// Document ready function
document.addEventListener('DOMContentLoaded', function() {
    // Initialize image preview
    initImagePreview();
    
    // Initialize image gallery
    initImageGallery();
});

/**
 * Initialize image preview before upload
 */
function initImagePreview() {
    const imageInput = document.getElementById('equipment_image');
    const previewContainer = document.getElementById('image-preview-container');
    
    if (!imageInput || !previewContainer) return;
    
    imageInput.addEventListener('change', function() {
        // Clear previous preview
        previewContainer.innerHTML = '';
        
        if (this.files && this.files.length > 0) {
            const file = this.files[0];
            
            // Check if file is an image
            if (!file.type.match('image.*')) {
                previewContainer.innerHTML = '<p class="error-message">Please select an image file.</p>';
                return;
            }
            
            // Create preview
            const img = document.createElement('img');
            img.classList.add('image-preview');
            img.file = file;
            
            previewContainer.appendChild(img);
            
            const reader = new FileReader();
            reader.onload = (function(aImg) { 
                return function(e) { 
                    aImg.src = e.target.result; 
                }; 
            })(img);
            
            reader.readAsDataURL(file);
            
            // Show caption field after image is selected
            const captionContainer = document.getElementById('image-caption-container');
            if (captionContainer) {
                captionContainer.style.display = 'block';
            }
        }
    });
}

/**
 * Initialize image gallery with lightbox effect
 */
function initImageGallery() {
    const galleryImages = document.querySelectorAll('.equipment-image-thumbnail');
    const lightbox = document.getElementById('image-lightbox');
    const lightboxImg = document.getElementById('lightbox-img');
    const lightboxCaption = document.getElementById('lightbox-caption');
    const lightboxClose = document.getElementById('lightbox-close');
    
    if (!galleryImages.length || !lightbox || !lightboxImg) return;
    
    // Open lightbox when clicking on thumbnail
    galleryImages.forEach(img => {
        img.addEventListener('click', function() {
            const fullSrc = this.getAttribute('data-full-img');
            const caption = this.getAttribute('data-caption');
            
            lightboxImg.src = fullSrc;
            if (lightboxCaption) {
                lightboxCaption.textContent = caption || '';
            }
            
            lightbox.style.display = 'flex';
            document.body.style.overflow = 'hidden'; // Prevent scrolling when lightbox is open
        });
    });
    
    // Close lightbox when clicking on close button
    if (lightboxClose) {
        lightboxClose.addEventListener('click', function() {
            closeLightbox();
        });
    }
    
    // Close lightbox when clicking outside image
    lightbox.addEventListener('click', function(e) {
        if (e.target === lightbox) {
            closeLightbox();
        }
    });
    
    // Close lightbox when pressing Escape key
    document.addEventListener('keydown', function(e) {
        if (e.key === 'Escape' && lightbox.style.display === 'flex') {
            closeLightbox();
        }
    });
}

/**
 * Close the lightbox
 */
function closeLightbox() {
    const lightbox = document.getElementById('image-lightbox');
    if (lightbox) {
        lightbox.style.display = 'none';
        document.body.style.overflow = 'auto'; // Re-enable scrolling
    }
}

/**
 * Confirm before deleting image
 */
function confirmDeleteImage(imageId, equipmentId) {
    if (confirm('Are you sure you want to delete this image?')) {
        window.location.href = `equipment_detail.php?id=${equipmentId}&action=delete_image&image_id=${imageId}`;
    }
}

/**
 * Set image as primary
 */
function setPrimaryImage(imageId, equipmentId) {
    window.location.href = `equipment_detail.php?id=${equipmentId}&action=set_primary_image&image_id=${imageId}`;
}