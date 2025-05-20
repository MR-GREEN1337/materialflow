// equipment-images.js - Updated for S3 integration

document.addEventListener('DOMContentLoaded', function() {
    // Image file input preview
    const imageInput = document.getElementById('equipment_image');
    const previewContainer = document.getElementById('image-preview-container');
    const captionContainer = document.getElementById('image-caption-container');
    
    if (imageInput) {
        imageInput.addEventListener('change', function() {
            // Clear previous preview
            previewContainer.innerHTML = '';
            
            // Check if a file was selected
            if (this.files && this.files[0]) {
                const file = this.files[0];
                
                // Check file type
                const validTypes = ['image/jpeg', 'image/png', 'image/gif'];
                if (!validTypes.includes(file.type)) {
                    previewContainer.innerHTML = '<div class="error-message">Invalid file type. Please select a JPG, PNG, or GIF image.</div>';
                    captionContainer.style.display = 'none';
                    return;
                }
                
                // Check file size (10MB max)
                if (file.size > 10 * 1024 * 1024) {
                    previewContainer.innerHTML = '<div class="error-message">File is too large. Maximum size is 10MB.</div>';
                    captionContainer.style.display = 'none';
                    return;
                }
                
                // Create and show preview
                const reader = new FileReader();
                reader.onload = function(e) {
                    previewContainer.innerHTML = `
                        <div class="image-preview-container">
                            <h4>Image Preview</h4>
                            <img src="${e.target.result}" class="image-preview" alt="Preview">
                        </div>
                    `;
                    
                    // Show caption input
                    captionContainer.style.display = 'block';
                };
                reader.readAsDataURL(file);
            } else {
                // Hide caption input if no file
                captionContainer.style.display = 'none';
            }
        });
    }
    
    // Lightbox functionality for image viewing
    const lightbox = document.getElementById('image-lightbox');
    const lightboxImg = document.getElementById('lightbox-img');
    const lightboxCaption = document.getElementById('lightbox-caption');
    const lightboxClose = document.getElementById('lightbox-close');
    
    // Open lightbox when clicking on a thumbnail
    const thumbnails = document.querySelectorAll('.equipment-image-thumbnail');
    thumbnails.forEach(thumbnail => {
        thumbnail.addEventListener('click', function() {
            lightboxImg.src = this.dataset.fullImg; // This will now be an S3 URL
            lightboxCaption.textContent = this.dataset.caption || '';
            lightbox.style.display = 'flex';
            document.body.style.overflow = 'hidden'; // Prevent scrolling when lightbox is open
        });
    });
    
    // Close lightbox
    if (lightboxClose) {
        lightboxClose.addEventListener('click', function() {
            lightbox.style.display = 'none';
            document.body.style.overflow = 'auto'; // Re-enable scrolling
        });
    }
    
    // Also close when clicking outside the image
    if (lightbox) {
        lightbox.addEventListener('click', function(e) {
            if (e.target === lightbox) {
                lightbox.style.display = 'none';
                document.body.style.overflow = 'auto';
            }
        });
    }
});

// Confirm delete image
function confirmDeleteImage(imageId, equipmentId) {
    if (confirm('Are you sure you want to delete this image? This will remove it from S3 storage and cannot be undone.')) {
        window.location.href = `equipment_detail.php?id=${equipmentId}&action=delete_image&image_id=${imageId}`;
    }
}

// Set image as primary
function setPrimaryImage(imageId, equipmentId) {
    window.location.href = `equipment_detail.php?id=${equipmentId}&action=set_primary_image&image_id=${imageId}`;
}