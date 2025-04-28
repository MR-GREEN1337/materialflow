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

// Check permissions for edit/add
if (($action === 'edit' || $action === 'add') && !is_admin()) {
    $_SESSION['error_message'] = 'You do not have permission to perform this action.';
    header('Location: equipment_list.php');
    exit;
}

// Initialize variables
$equipment = null;
$projects_using = [];
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
}

// Process form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Validate form data
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
        
        if ($action === 'add') {
            // Add new equipment
            $result = add_equipment($data);
            
            if ($result['success']) {
                $_SESSION['success_message'] = 'Equipment added successfully.';
                header('Location: equipment_detail.php?id=' . $result['equipment_id']);
                exit;
            } else {
                $error_message = $result['message'] ?? 'Failed to add equipment.';
            }
        } elseif ($action === 'edit' && $equipment_id) {
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
            <?php else: ?>
                <!-- View mode -->
                <div class="card">
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
    
    <script src="../assets/js/equipment.js"></script>
</body>
</html>