<?php
// Start session
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Include required files
require_once '../includes/auth.php';
require_once '../includes/functions.php';
require_once '../includes/equipment.php';

// Require login for this page
require_login();

// Get action and ID
$action = isset($_GET['action']) ? $_GET['action'] : 'view';
$equipment_id = isset($_GET['id']) ? intval($_GET['id']) : null;
$image_id = isset($_GET['image_id']) ? intval($_GET['image_id']) : null;

// Check permissions for edit/add
if (($action === 'edit' || $action === 'add' || $action === 'delete_image' || $action === 'set_primary_image') && !is_admin()) {
    $_SESSION['error_message'] = 'You do not have permission to perform this action.';
    header('Location: equipment_list.php');
    exit;
}

// Initialize variables
$equipment = null;
$projects_using = [];
$equipment_images = [];
$errors = [];
$success_message = '';
$error_message = '';

// Check if we have a session message
if (isset($_SESSION['success_message'])) {
    $success_message = $_SESSION['success_message'];
    unset($_SESSION['success_message']);
}

if (isset($_SESSION['error_message'])) {
    $error_message = $_SESSION['error_message'];
    unset($_SESSION['error_message']);
}

// Load equipment data if ID is provided
if ($equipment_id && $action !== 'add') {
    $equipment = get_equipment_by_id($equipment_id);
    
    if (!$equipment) {
        $_SESSION['error_message'] = 'Equipment not found.';
        header('Location: equipment_list.php');
        exit;
    }
    
    // Get projects using this equipment
    $projects_using = get_projects_using_equipment($equipment_id);
    
    // Get equipment images
    $equipment_images = get_equipment_images($equipment_id);
}

// Handle image-specific actions
if ($action === 'delete_image' && $equipment_id && $image_id && is_admin()) {
    $result = delete_equipment_image($image_id);
    
    if ($result['success']) {
        $_SESSION['success_message'] = 'Image deleted successfully.';
    } else {
        $_SESSION['error_message'] = $result['message'] ?? 'Failed to delete image.';
    }
    
    header('Location: equipment_detail.php?id=' . $equipment_id);
    exit;
}

if ($action === 'set_primary_image' && $equipment_id && $image_id && is_admin()) {
    $result = set_primary_equipment_image($image_id, $equipment_id);
    
    if ($result['success']) {
        $_SESSION['success_message'] = 'Primary image set successfully.';
    } else {
        $_SESSION['error_message'] = $result['message'] ?? 'Failed to set primary image.';
    }
    
    header('Location: equipment_detail.php?id=' . $equipment_id);
    exit;
}

// Process form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Handle image upload
    if (isset($_POST['upload_image']) && $equipment_id) {
        if (isset($_FILES['equipment_image']) && $_FILES['equipment_image']['size'] > 0) {
            $caption = isset($_POST['image_caption']) ? trim($_POST['image_caption']) : '';
            $is_primary = isset($_POST['is_primary']) ? 1 : 0;
            
            $result = add_equipment_image($equipment_id, $_FILES['equipment_image'], $caption, $is_primary);
            
            if ($result['success']) {
                $_SESSION['success_message'] = 'Image uploaded successfully.';
                header('Location: equipment_detail.php?id=' . $equipment_id);
                exit;
            } else {
                $error_message = $result['message'] ?? 'Failed to upload image.';
            }
        } else {
            $error_message = 'Please select an image to upload.';
        }
    }
    
    // Validate form data for equipment
    elseif (isset($_POST['action']) && ($_POST['action'] === 'add_equipment' || $_POST['action'] === 'edit_equipment')) {
        $name = trim($_POST['name'] ?? '');
        $description = trim($_POST['description'] ?? '');
        $purchase_date = trim($_POST['purchase_date'] ?? '');
        $purchase_price = trim($_POST['purchase_price'] ?? '');
        $vendor = trim($_POST['vendor'] ?? '');
        $vendor_url = trim($_POST['vendor_url'] ?? '');
        $documentation_url = trim($_POST['documentation_url'] ?? '');
        $technical_specs = trim($_POST['technical_specs'] ?? '');
        $status = trim($_POST['status'] ?? '');
        $storage_location = trim($_POST['storage_location'] ?? '');
        $additional_notes = trim($_POST['additional_notes'] ?? '');
        
        // Validate required fields
        if (empty($name)) {
            $errors[] = 'Equipment name is required.';
        }
        
        if (empty($status)) {
            $errors[] = 'Status is required.';
        }
        
        // If no errors, process the form
        if (empty($errors)) {
            $data = [
                'name' => $name,
                'description' => $description,
                'purchase_date' => $purchase_date ?: null,
                'purchase_price' => $purchase_price ?: null,
                'vendor' => $vendor,
                'vendor_url' => $vendor_url,
                'documentation_url' => $documentation_url,
                'technical_specs' => $technical_specs,
                'status' => $status,
                'storage_location' => $storage_location,
                'additional_notes' => $additional_notes
            ];
            
            if ($_POST['action'] === 'add_equipment') {
                // Add new equipment
                $result = add_equipment($data);
                
                if ($result['success']) {
                    $_SESSION['success_message'] = 'Equipment added successfully.';
                    header('Location: equipment_detail.php?id=' . $result['equipment_id']);
                    exit;
                } else {
                    $error_message = $result['message'] ?? 'Failed to add equipment.';
                }
            } elseif ($_POST['action'] === 'edit_equipment' && $equipment_id) {
                // Update equipment
                $result = update_equipment($equipment_id, $data);
                
                if ($result['success']) {
                    $_SESSION['success_message'] = 'Equipment updated successfully.';
                    header('Location: equipment_detail.php?id=' . $equipment_id);
                    exit;
                } else {
                    $error_message = $result['message'] ?? 'Failed to update equipment.';
                }
            }
        }
    }
}

// Process delete equipment
if ($action === 'delete' && $equipment_id && is_admin()) {
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['confirm_delete'])) {
        $result = delete_equipment($equipment_id);
        
        if ($result['success']) {
            $_SESSION['success_message'] = 'Equipment deleted successfully.';
            header('Location: equipment_list.php');
            exit;
        } else {
            $error_message = $result['message'] ?? 'Failed to delete equipment.';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $action === 'add' ? 'Add New Equipment' : ($action === 'edit' ? 'Edit Equipment' : 'Equipment Details'); ?> - Equipment Tracking System</title>
    <link rel="stylesheet" href="../assets/css/styles.css">
    <style>
        /* Additional styles for equipment images */
        .equipment-images {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(150px, 1fr));
            gap: 1rem;
            margin-top: 1rem;
        }
        
        .equipment-image-card {
            border: 1px solid var(--border);
            border-radius: var(--radius);
            overflow: hidden;
            position: relative;
        }
        
        .equipment-image-thumbnail {
            width: 100%;
            height: 150px;
            object-fit: cover;
            cursor: pointer;
            transition: transform 0.2s;
        }
        
        .equipment-image-thumbnail:hover {
            transform: scale(1.05);
        }
        
        .primary-badge {
            position: absolute;
            top: 0.5rem;
            right: 0.5rem;
            background-color: var(--primary);
            color: var(--primary-foreground);
            padding: 0.25rem 0.5rem;
            border-radius: 9999px;
            font-size: 0.625rem;
            font-weight: 500;
        }
        
        .image-actions {
            display: flex;
            justify-content: space-between;
            padding: 0.5rem;
            background-color: var(--card);
        }
        
        .image-caption {
            padding: 0.5rem;
            font-size: 0.875rem;
            color: var(--muted-foreground);
            text-align: center;
            background-color: var(--muted);
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }
        
        .image-preview-container {
            margin-top: 1rem;
            text-align: center;
        }
        
        .image-preview {
            max-width: 100%;
            max-height: 300px;
            border-radius: var(--radius);
            border: 1px solid var(--border);
        }
        
        /* Lightbox styles */
        #image-lightbox {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background-color: rgba(0, 0, 0, 0.9);
            z-index: 1000;
            justify-content: center;
            align-items: center;
            flex-direction: column;
        }
        
        #lightbox-img {
            max-width: 90%;
            max-height: 80vh;
            border-radius: var(--radius);
        }
        
        #lightbox-caption {
            color: white;
            margin-top: 1rem;
            padding: 0.5rem 1rem;
            background-color: rgba(0, 0, 0, 0.7);
            border-radius: var(--radius);
        }
        
        #lightbox-close {
            position: absolute;
            top: 1rem;
            right: 1rem;
            color: white;
            font-size: 2rem;
            cursor: pointer;
            background: none;
            border: none;
        }
    </style>
</head>
<body>
    <?php include '../includes/header.php'; ?>
    
    <main>
        <div class="container">
            <div class="page-header">
                <h1 class="page-title">
                    <?php if ($action === 'add'): ?>
                        Add New Equipment
                    <?php elseif ($action === 'edit'): ?>
                        Edit Equipment: <?php echo htmlspecialchars($equipment['name']); ?>
                    <?php elseif ($action === 'delete'): ?>
                        Delete Equipment: <?php echo htmlspecialchars($equipment['name']); ?>
                    <?php else: ?>
                        Equipment Details: <?php echo htmlspecialchars($equipment['name']); ?>
                    <?php endif; ?>
                </h1>
                
                <?php if ($action === 'view' && $equipment_id): ?>
                    <div class="page-actions">
                        <?php if (is_admin()): ?>
                            <a href="equipment_detail.php?id=<?php echo $equipment_id; ?>&action=edit" class="btn-secondary">Edit Equipment</a>
                            <a href="equipment_detail.php?id=<?php echo $equipment_id; ?>&action=delete" class="btn-danger">Delete Equipment</a>
                        <?php endif; ?>
                        <a href="equipment_list.php" class="btn-primary">Back to List</a>
                    </div>
                <?php endif; ?>
            </div>
            
            <?php if (!empty($error_message)): ?>
                <div class="error-message"><?php echo $error_message; ?></div>
            <?php endif; ?>
            
            <?php if (!empty($success_message)): ?>
                <div class="success-message"><?php echo $success_message; ?></div>
            <?php endif; ?>
            
            <?php if (!empty($errors)): ?>
                <div class="error-message">
                    <ul>
                        <?php foreach ($errors as $error): ?>
                            <li><?php echo $error; ?></li>
                        <?php endforeach; ?>
                    </ul>
                </div>
            <?php endif; ?>
            
            <?php if ($action === 'delete' && $equipment_id): ?>
                <!-- Delete confirmation -->
                <div class="card">
                    <h2 class="card-title">Confirm Deletion</h2>
                    <p>Are you sure you want to delete this equipment? This action cannot be undone.</p>
                    
                    <?php if (!empty($projects_using)): ?>
                        <div class="error-message">
                            <p>This equipment has been used in projects. Deleting it will remove the equipment from these projects.</p>
                        </div>
                    <?php endif; ?>
                    
                    <form method="post" action="equipment_detail.php?id=<?php echo $equipment_id; ?>&action=delete">
                        <input type="hidden" name="confirm_delete" value="1">
                        <div class="form-actions">
                            <button type="submit" class="btn-danger">Confirm Delete</button>
                            <a href="equipment_detail.php?id=<?php echo $equipment_id; ?>" class="btn-secondary">Cancel</a>
                        </div>
                    </form>
                </div>
            <?php elseif ($action === 'add' || $action === 'edit'): ?>
                <!-- Add/Edit form -->
                <div class="card">
                    <form method="post" action="<?php echo $action === 'add' ? 'equipment_detail.php?action=add' : 'equipment_detail.php?id=' . $equipment_id . '&action=edit'; ?>">
                        <input type="hidden" name="action" value="<?php echo $action === 'add' ? 'add_equipment' : 'edit_equipment'; ?>">
                        
                        <div class="form-group">
                            <label for="name">Equipment Name <span class="required">*</span></label>
                            <input type="text" id="name" name="name" required value="<?php echo htmlspecialchars($equipment['name'] ?? ''); ?>">
                        </div>
                        
                        <div class="form-group">
                            <label for="description">Description</label>
                            <textarea id="description" name="description"><?php echo htmlspecialchars($equipment['description'] ?? ''); ?></textarea>
                        </div>
                        
                        <div class="form-row">
                            <div class="form-group">
                                <label for="purchase_date">Purchase Date</label>
                                <input type="date" id="purchase_date" name="purchase_date" value="<?php echo htmlspecialchars($equipment['purchase_date'] ?? ''); ?>">
                            </div>
                            
                            <div class="form-group">
                                <label for="purchase_price">Purchase Price</label>
                                <input type="number" id="purchase_price" name="purchase_price" step="0.01" value="<?php echo htmlspecialchars($equipment['purchase_price'] ?? ''); ?>">
                            </div>
                        </div>
                        
                        <div class="form-group">
                            <label for="vendor">Vendor/Supplier</label>
                            <input type="text" id="vendor" name="vendor" value="<?php echo htmlspecialchars($equipment['vendor'] ?? ''); ?>">
                        </div>
                        
                        <div class="form-group">
                            <label for="vendor_url">Vendor Website URL</label>
                            <input type="url" id="vendor_url" name="vendor_url" value="<?php echo htmlspecialchars($equipment['vendor_url'] ?? ''); ?>">
                        </div>
                        
                        <div class="form-group">
                            <label for="documentation_url">Documentation URL</label>
                            <input type="url" id="documentation_url" name="documentation_url" value="<?php echo htmlspecialchars($equipment['documentation_url'] ?? ''); ?>">
                        </div>
                        
                        <div class="form-group">
                            <label for="technical_specs">Technical Specifications</label>
                            <textarea id="technical_specs" name="technical_specs"><?php echo htmlspecialchars($equipment['technical_specs'] ?? ''); ?></textarea>
                        </div>
                        
                        <div class="form-group">
                            <label for="status">Status <span class="required">*</span></label>
                            <select id="status" name="status" required>
                                <option value="">-- Select Status --</option>
                                <option value="available" <?php echo isset($equipment['status']) && $equipment['status'] === 'available' ? 'selected' : ''; ?>>Available</option>
                                <option value="in_use" <?php echo isset($equipment['status']) && $equipment['status'] === 'in_use' ? 'selected' : ''; ?>>In Use</option>
                                <option value="broken" <?php echo isset($equipment['status']) && $equipment['status'] === 'broken' ? 'selected' : ''; ?>>Broken</option>
                                <option value="lost" <?php echo isset($equipment['status']) && $equipment['status'] === 'lost' ? 'selected' : ''; ?>>Lost</option>
                                <option value="deprecated" <?php echo isset($equipment['status']) && $equipment['status'] === 'deprecated' ? 'selected' : ''; ?>>Deprecated</option>
                            </select>
                        </div>
                        
                        <div class="form-group">
                            <label for="storage_location">Storage Location</label>
                            <input type="text" id="storage_location" name="storage_location" value="<?php echo htmlspecialchars($equipment['storage_location'] ?? ''); ?>">
                        </div>
                        
                        <div class="form-group">
                            <label for="additional_notes">Additional Notes</label>
                            <textarea id="additional_notes" name="additional_notes"><?php echo htmlspecialchars($equipment['additional_notes'] ?? ''); ?></textarea>
                        </div>
                        
                        <div class="form-actions">
                            <button type="submit" class="btn-primary"><?php echo $action === 'add' ? 'Add Equipment' : 'Update Equipment'; ?></button>
                            <a href="<?php echo $action === 'add' ? 'equipment_list.php' : 'equipment_detail.php?id=' . $equipment_id; ?>" class="btn-secondary">Cancel</a>
                        </div>
                    </form>
                </div>
                
                <?php if ($action === 'edit' && $equipment_id): ?>
                <!-- Image upload form (edit mode only) -->
                <div class="card">
                    <h2 class="card-title">Equipment Images</h2>
                    
                    <form method="post" action="equipment_detail.php?id=<?php echo $equipment_id; ?>" enctype="multipart/form-data">
                        <div class="form-group">
                            <label for="equipment_image">Upload Image</label>
                            <input type="file" id="equipment_image" name="equipment_image" accept="image/*">
                            <small>Accepted formats: JPG, PNG, GIF. Max file size: 5MB</small>
                        </div>
                        
                        <div id="image-preview-container"></div>
                        
                        <div id="image-caption-container" class="form-group" style="display: none;">
                            <label for="image_caption">Image Caption (optional)</label>
                            <input type="text" id="image_caption" name="image_caption" placeholder="Enter a caption for this image">
                        </div>
                        
                        <div class="form-group">
                            <label>
                                <input type="checkbox" name="is_primary" id="is_primary" value="1">
                                Set as primary image
                            </label>
                        </div>
                        
                        <div class="form-actions">
                            <button type="submit" name="upload_image" class="btn-primary">
                                <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="margin-right: 0.5rem;">
                                    <path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"></path>
                                    <polyline points="17 8 12 3 7 8"></polyline>
                                    <line x1="12" y1="3" x2="12" y2="15"></line>
                                </svg>
                                Upload Image
                            </button>
                        </div>
                    </form>
                    
                    <!-- Display existing images -->
                    <?php if (!empty($equipment_images)): ?>
                        <h3 style="margin-top: 1.5rem; margin-bottom: 0.75rem;">Existing Images</h3>
                        <div class="equipment-images">
                            <?php foreach ($equipment_images as $image): ?>
                                <div class="equipment-image-card">
                                    <?php if ($image['is_primary']): ?>
                                        <div class="primary-badge">Primary</div>
                                    <?php endif; ?>
                                    <img 
                                        src="<?php echo htmlspecialchars($image['file_path']); ?>" 
                                        alt="<?php echo htmlspecialchars($image['caption'] ?: 'Equipment image'); ?>"
                                        class="equipment-image-thumbnail"
                                        data-full-img="<?php echo htmlspecialchars($image['file_path']); ?>"
                                        data-caption="<?php echo htmlspecialchars($image['caption']); ?>"
                                    >
                                    <?php if (!empty($image['caption'])): ?>
                                        <div class="image-caption"><?php echo htmlspecialchars($image['caption']); ?></div>
                                    <?php endif; ?>
                                    <div class="image-actions">
                                        <?php if (!$image['is_primary']): ?>
                                            <button type="button" class="btn-secondary" style="padding: 0.25rem 0.5rem; font-size: 0.75rem;" onclick="setPrimaryImage(<?php echo $image['id']; ?>, <?php echo $equipment_id; ?>)">
                                                Set Primary
                                            </button>
                                        <?php else: ?>
                                            <span></span> <!-- Empty span for layout -->
                                        <?php endif; ?>
                                        <button type="button" class="btn-danger" style="padding: 0.25rem 0.5rem; font-size: 0.75rem;" onclick="confirmDeleteImage(<?php echo $image['id']; ?>, <?php echo $equipment_id; ?>)">
                                            Delete
                                        </button>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php else: ?>
                        <p style="margin-top: 1rem; color: var(--muted-foreground);">No images have been uploaded for this equipment yet.</p>
                    <?php endif; ?>
                </div>
                <?php endif; ?>
                <?php else: ?>
                <!-- View mode -->
                <div class="card">
                    <!-- Equipment Images Gallery (if any) -->
                    <?php if (!empty($equipment_images)): ?>
                        <div class="detail-section">
                            <h3>Equipment Images</h3>
                            <div class="equipment-images">
                                <?php foreach ($equipment_images as $image): ?>
                                    <div class="equipment-image-card">
                                        <?php if ($image['is_primary']): ?>
                                            <div class="primary-badge">Primary</div>
                                        <?php endif; ?>
                                        <img 
                                            src="<?php echo htmlspecialchars($image['file_path']); ?>" 
                                            alt="<?php echo htmlspecialchars($image['caption'] ?: 'Equipment image'); ?>"
                                            class="equipment-image-thumbnail"
                                            data-full-img="<?php echo htmlspecialchars($image['file_path']); ?>"
                                            data-caption="<?php echo htmlspecialchars($image['caption']); ?>"
                                        >
                                        <?php if (!empty($image['caption'])): ?>
                                            <div class="image-caption"><?php echo htmlspecialchars($image['caption']); ?></div>
                                        <?php endif; ?>
                                        <?php if (is_admin()): ?>
                                            <div class="image-actions">
                                                <?php if (!$image['is_primary']): ?>
                                                    <button type="button" class="btn-secondary" style="padding: 0.25rem 0.5rem; font-size: 0.75rem;" onclick="setPrimaryImage(<?php echo $image['id']; ?>, <?php echo $equipment_id; ?>)">
                                                        Set Primary
                                                    </button>
                                                <?php else: ?>
                                                    <span></span> <!-- Empty span for layout -->
                                                <?php endif; ?>
                                                <button type="button" class="btn-danger" style="padding: 0.25rem 0.5rem; font-size: 0.75rem;" onclick="confirmDeleteImage(<?php echo $image['id']; ?>, <?php echo $equipment_id; ?>)">
                                                    Delete
                                                </button>
                                            </div>
                                        <?php endif; ?>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        </div>
                    <?php endif; ?>

                    <div class="detail-section">
                        <h3>Equipment Information</h3>
                        
                        <div class="detail-row">
                            <div class="detail-label">Status:</div>
                            <div class="detail-value"><?php echo get_equipment_status_label($equipment['status']); ?></div>
                        </div>
                        
                        <div class="detail-row">
                            <div class="detail-label">Description:</div>
                            <div class="detail-value"><?php echo nl2br(htmlspecialchars($equipment['description'] ?? 'N/A')); ?></div>
                        </div>
                        
                        <div class="detail-row">
                            <div class="detail-label">Purchase Date:</div>
                            <div class="detail-value"><?php echo $equipment['purchase_date'] ? format_date($equipment['purchase_date']) : 'N/A'; ?></div>
                        </div>
                        
                        <div class="detail-row">
                            <div class="detail-label">Purchase Price:</div>
                            <div class="detail-value"><?php echo $equipment['purchase_price'] ? '€' . number_format($equipment['purchase_price'], 2) : 'N/A'; ?></div>
                        </div>
                        
                        <div class="detail-row">
                            <div class="detail-label">Vendor/Supplier:</div>
                            <div class="detail-value"><?php echo htmlspecialchars($equipment['vendor'] ?? 'N/A'); ?></div>
                        </div>
                        
                        <?php if (!empty($equipment['vendor_url'])): ?>
                        <div class="detail-row">
                            <div class="detail-label">Vendor Website:</div>
                            <div class="detail-value"><a href="<?php echo htmlspecialchars($equipment['vendor_url']); ?>" target="_blank"><?php echo htmlspecialchars($equipment['vendor_url']); ?></a></div>
                        </div>
                        <?php endif; ?>
                        
                        <?php if (!empty($equipment['documentation_url'])): ?>
                        <div class="detail-row">
                            <div class="detail-label">Documentation:</div>
                            <div class="detail-value"><a href="<?php echo htmlspecialchars($equipment['documentation_url']); ?>" target="_blank"><?php echo htmlspecialchars($equipment['documentation_url']); ?></a></div>
                        </div>
                        <?php endif; ?>
                        
                        <div class="detail-row">
                            <div class="detail-label">Storage Location:</div>
                            <div class="detail-value"><?php echo htmlspecialchars($equipment['storage_location'] ?? 'N/A'); ?></div>
                        </div>
                        
                        <?php if (!empty($equipment['technical_specs'])): ?>
                        <div class="detail-row">
                            <div class="detail-label">Technical Specifications:</div>
                            <div class="detail-value"><?php echo nl2br(htmlspecialchars($equipment['technical_specs'])); ?></div>
                        </div>
                        <?php endif; ?>
                        
                        <?php if (!empty($equipment['additional_notes'])): ?>
                        <div class="detail-row">
                            <div class="detail-label">Additional Notes:</div>
                            <div class="detail-value"><?php echo nl2br(htmlspecialchars($equipment['additional_notes'])); ?></div>
                        </div>
                        <?php endif; ?>
                    </div>
                </div>
                
                <!-- Usage history -->
                <div class="card">
                    <h2 class="card-title">Usage History</h2>
                    
                    <?php if (empty($projects_using)): ?>
                        <p>This equipment has not been used in any projects yet.</p>
                    <?php else: ?>
                        <div class="table-container">
                            <table>
                                <thead>
                                    <tr>
                                        <th>Project</th>
                                        <th>Status</th>
                                        <th>Checkout Date</th>
                                        <th>Return Date</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($projects_using as $project): ?>
                                        <tr>
                                            <td><a href="project_detail.php?id=<?php echo $project['id']; ?>"><?php echo htmlspecialchars($project['title']); ?></a></td>
                                            <td><?php echo get_project_status_label($project['status']); ?></td>
                                            <td><?php echo format_date($project['checkout_date']); ?></td>
                                            <td><?php echo $project['return_date'] ? format_date($project['return_date']) : 'Not returned'; ?></td>
                                            <td>
                                                <a href="project_detail.php?id=<?php echo $project['id']; ?>" class="btn-primary">View Project</a>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php endif; ?>
                </div>
            <?php endif; ?>
        </div>
    </main>
    
    <?php include '../includes/footer.php'; ?>
    
    <!-- Lightbox for full-size image viewing -->
    <div id="image-lightbox">
        <button id="lightbox-close">&times;</button>
        <img id="lightbox-img" src="" alt="Equipment image full view">
        <div id="lightbox-caption"></div>
    </div>

    <script src="../assets/js/equipment.js"></script>
    <script src="../assets/js/equipment-images.js"></script>
</body>
</html>