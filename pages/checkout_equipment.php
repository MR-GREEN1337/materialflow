<?php
// Start session
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Include required files
require_once '../includes/auth.php';
require_once '../includes/functions.php';
require_once '../includes/equipment.php';
require_once '../includes/projects.php';

// Require admin for this page
require_admin();

// Initialize variables
$equipment_id = isset($_GET['id']) ? intval($_GET['id']) : null;
$project_id = isset($_GET['project_id']) ? intval($_GET['project_id']) : null;
$equipment = null;
$project = null;
$available_equipment = [];
$ongoing_projects = [];
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

// Get data based on what was provided
if ($equipment_id) {
    // Get specific equipment
    $equipment = get_equipment_by_id($equipment_id);
    
    if (!$equipment) {
        $_SESSION['error_message'] = 'Equipment not found.';
        header('Location: equipment_list.php');
        exit;
    }
    
    // Check if equipment is available
    if ($equipment['status'] !== 'available') {
        $_SESSION['error_message'] = 'This equipment is not available for checkout.';
        header('Location: equipment_detail.php?id=' . $equipment_id);
        exit;
    }
    
    // Get ongoing projects
    $ongoing_projects = get_all_projects('ongoing');
} elseif ($project_id) {
    // Get specific project
    $project = get_project_by_id($project_id);
    
    if (!$project) {
        $_SESSION['error_message'] = 'Project not found.';
        header('Location: project_list.php');
        exit;
    }
    
    // Check if project is ongoing
    if ($project['status'] !== 'ongoing') {
        $_SESSION['error_message'] = 'Only ongoing projects can checkout equipment.';
        header('Location: project_detail.php?id=' . $project_id);
        exit;
    }
    
    // Get available equipment
    $available_equipment = get_all_equipment('available');
} else {
    // No specific equipment or project, get all available equipment and ongoing projects
    $available_equipment = get_all_equipment('available');
    $ongoing_projects = get_all_projects('ongoing');
}

// Process form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $checkout_equipment_id = isset($_POST['equipment_id']) ? intval($_POST['equipment_id']) : null;
    $checkout_project_id = isset($_POST['project_id']) ? intval($_POST['project_id']) : null;
    $checkout_date = isset($_POST['checkout_date']) ? trim($_POST['checkout_date']) : date('Y-m-d');
    $notes = isset($_POST['notes']) ? trim($_POST['notes']) : null;
    
    // Validate required fields
    if (!$checkout_equipment_id) {
        $errors[] = 'Please select equipment to checkout.';
    }
    
    if (!$checkout_project_id) {
        $errors[] = 'Please select a project for checkout.';
    }
    
    if (!$checkout_date) {
        $errors[] = 'Checkout date is required.';
    }
    
    // If no errors, process the checkout
    if (empty($errors)) {
        $result = checkout_equipment($checkout_project_id, $checkout_equipment_id, $checkout_date, $notes);
        
        if ($result['success']) {
            $_SESSION['success_message'] = 'Equipment checked out successfully.';
            header('Location: project_detail.php?id=' . $checkout_project_id);
            exit;
        } else {
            $error_message = $result['message'] ?? 'Failed to checkout equipment.';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Checkout Equipment - Equipment Tracking System</title>
    <link rel="stylesheet" href="../assets/css/styles.css">
</head>
<body>
    <?php include '../includes/header.php'; ?>
    
    <main>
        <div class="container">
            <h1 class="page-title">Checkout Equipment</h1>
            
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
            
            <div class="card">
                <form method="post" action="checkout_equipment.php<?php echo $project_id ? '?project_id=' . $project_id : ($equipment_id ? '?id=' . $equipment_id : ''); ?>">
                    <div class="form-group">
                        <label for="equipment_id">Equipment <span class="required">*</span></label>
                        <select id="equipment_id" name="equipment_id" required <?php echo $equipment_id ? 'disabled' : ''; ?>>
                            <option value="">-- Select Equipment --</option>
                            <?php if ($equipment_id && $equipment): ?>
                                <option value="<?php echo $equipment['id']; ?>" selected><?php echo htmlspecialchars($equipment['name']); ?></option>
                                <input type="hidden" name="equipment_id" value="<?php echo $equipment['id']; ?>">
                            <?php else: ?>
                                <?php foreach ($available_equipment as $equip): ?>
                                    <option value="<?php echo $equip['id']; ?>"><?php echo htmlspecialchars($equip['name']); ?></option>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </select>
                    </div>
                    
                    <div class="form-group">
                        <label for="project_id">Project <span class="required">*</span></label>
                        <select id="project_id" name="project_id" required <?php echo $project_id ? 'disabled' : ''; ?>>
                            <option value="">-- Select Project --</option>
                            <?php if ($project_id && $project): ?>
                                <option value="<?php echo $project['id']; ?>" selected><?php echo htmlspecialchars($project['title']); ?></option>
                                <input type="hidden" name="project_id" value="<?php echo $project['id']; ?>">
                            <?php else: ?>
                                <?php foreach ($ongoing_projects as $proj): ?>
                                    <option value="<?php echo $proj['id']; ?>"><?php echo htmlspecialchars($proj['title']); ?></option>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </select>
                    </div>
                    
                    <div class="form-group">
                        <label for="checkout_date">Checkout Date <span class="required">*</span></label>
                        <input type="date" id="checkout_date" name="checkout_date" value="<?php echo date('Y-m-d'); ?>" required>
                    </div>
                    
                    <div class="form-group">
                        <label for="notes">Notes</label>
                        <textarea id="notes" name="notes" rows="3" placeholder="Additional information about checkout..."></textarea>
                    </div>
                    
                    <div class="form-actions">
                        <button type="submit" class="btn-primary">Checkout Equipment</button>
                        <a href="<?php echo $project_id ? 'project_detail.php?id=' . $project_id : ($equipment_id ? 'equipment_detail.php?id=' . $equipment_id : 'equipment_list.php'); ?>" class="btn-secondary">Cancel</a>
                    </div>
                </form>
            </div>
        </div>
    </main>
    
    <?php include '../includes/footer.php'; ?>
    
    <script src="../assets/js/equipment.js"></script>
</body>
</html>