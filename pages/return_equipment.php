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
$project_equipment_id = isset($_GET['id']) ? intval($_GET['id']) : null;
$project_equipment = null;
$equipment = null;
$project = null;
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

// Get project equipment record
if ($project_equipment_id) {
    // Connect to database
    $conn = connect_db();
    
    // Get project equipment record with related data
    $sql = "SELECT pe.*, e.name AS equipment_name, p.title AS project_title, p.id AS project_id 
            FROM project_equipment pe
            JOIN equipment e ON pe.equipment_id = e.id
            JOIN projects p ON pe.project_id = p.id
            WHERE pe.id = ?";
    
    $stmt = $conn->prepare($sql);
    $stmt->bind_param('i', $project_equipment_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows === 1) {
        $project_equipment = $result->fetch_assoc();
        
        // Check if already returned
        if ($project_equipment['return_date'] !== null) {
            $_SESSION['error_message'] = 'This equipment has already been returned.';
            header('Location: project_detail.php?id=' . $project_equipment['project_id']);
            exit;
        }
    } else {
        $_SESSION['error_message'] = 'Equipment checkout record not found.';
        header('Location: index.php');
        exit;
    }
    
    $conn->close();
} else {
    $_SESSION['error_message'] = 'Invalid request.';
    header('Location: index.php');
    exit;
}

// Process form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $return_date = isset($_POST['return_date']) ? trim($_POST['return_date']) : date('Y-m-d');
    $status_on_return = isset($_POST['status_on_return']) ? trim($_POST['status_on_return']) : null;
    $notes = isset($_POST['notes']) ? trim($_POST['notes']) : null;
    
    // Validate required fields
    if (!$return_date) {
        $errors[] = 'Return date is required.';
    }
    
    // If no errors, process the return
    if (empty($errors)) {
        $result = return_equipment($project_equipment_id, $return_date, $status_on_return, $notes);
        
        if ($result['success']) {
            $_SESSION['success_message'] = 'Equipment returned successfully.';
            header('Location: project_detail.php?id=' . $project_equipment['project_id']);
            exit;
        } else {
            $error_message = $result['message'] ?? 'Failed to return equipment.';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Return Equipment - Equipment Tracking System</title>
    <link rel="stylesheet" href="../assets/css/styles.css">
</head>
<body>
    <?php include '../includes/header.php'; ?>
    
    <main>
        <div class="container">
            <h1 class="page-title">Return Equipment</h1>
            
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
                <div class="return-info">
                    <h3>Return Information</h3>
                    <p><strong>Equipment:</strong> <?php echo htmlspecialchars($project_equipment['equipment_name']); ?></p>
                    <p><strong>Project:</strong> <?php echo htmlspecialchars($project_equipment['project_title']); ?></p>
                    <p><strong>Checkout Date:</strong> <?php echo format_date($project_equipment['checkout_date']); ?></p>
                    <?php if (!empty($project_equipment['notes'])): ?>
                        <p><strong>Checkout Notes:</strong> <?php echo nl2br(htmlspecialchars($project_equipment['notes'])); ?></p>
                    <?php endif; ?>
                </div>
                
                <form method="post" action="return_equipment.php?id=<?php echo $project_equipment_id; ?>">
                    <div class="form-group">
                        <label for="return_date">Return Date <span class="required">*</span></label>
                        <input type="date" id="return_date" name="return_date" value="<?php echo date('Y-m-d'); ?>" required>
                    </div>
                    
                    <div class="form-group">
                        <label for="status_on_return">Equipment Status on Return</label>
                        <select id="status_on_return" name="status_on_return">
                            <option value="">-- Select Status --</option>
                            <option value="Working">Working (Fully Functional)</option>
                            <option value="Minor Issues">Minor Issues (Still Usable)</option>
                            <option value="Needs Repair">Needs Repair</option>
                            <option value="Damaged">Damaged (Not Usable)</option>
                        </select>
                    </div>
                    
                    <div class="form-group">
                        <label for="notes">Notes</label>
                        <textarea id="notes" name="notes" rows="3" placeholder="Additional information about the return or equipment condition..."></textarea>
                    </div>
                    
                    <div class="form-actions">
                        <button type="submit" class="btn-primary">Return Equipment</button>
                        <a href="project_detail.php?id=<?php echo $project_equipment['project_id']; ?>" class="btn-secondary">Cancel</a>
                    </div>
                </form>
            </div>
        </div>
    </main>
    
    <?php include '../includes/footer.php'; ?>
    
    <script src="../assets/js/equipment.js"></script>
</body>
</html>