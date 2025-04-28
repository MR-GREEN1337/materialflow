<?php
// Start session
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Include required files
require_once '../includes/auth.php';
require_once '../includes/functions.php';
require_once '../includes/projects.php';

// Require login for this page
require_login();

// Process search and filters
$status = isset($_GET['status']) ? $_GET['status'] : null;
$search = isset($_GET['search']) ? $_GET['search'] : null;

// Get project list
$project_list = get_all_projects($status, $search);

// Count status totals for filters
$conn = connect_db();
$statusCountQuery = "SELECT 
    SUM(CASE WHEN status = 'ongoing' THEN 1 ELSE 0 END) AS ongoing,
    SUM(CASE WHEN status = 'completed' THEN 1 ELSE 0 END) AS completed,
    SUM(CASE WHEN status = 'archived' THEN 1 ELSE 0 END) AS archived,
    COUNT(*) AS total
FROM projects";
$statusCountResult = $conn->query($statusCountQuery);
$statusCounts = $statusCountResult->fetch_assoc();
$conn->close();

// Check for flash messages
$success_message = '';
$error_message = '';

if (isset($_SESSION['success_message'])) {
    $success_message = $_SESSION['success_message'];
    unset($_SESSION['success_message']);
}

if (isset($_SESSION['error_message'])) {
    $error_message = $_SESSION['error_message'];
    unset($_SESSION['error_message']);
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Project List - Equipment Tracking System</title>
    <link rel="stylesheet" href="../assets/css/styles.css">
</head>
<body>
    <?php include '../includes/header.php'; ?>
    
    <main>
        <div class="container">
            <h1 class="page-title">Projects List</h1>
            
            <?php if (!empty($success_message)): ?>
                <div class="success-message"><?php echo $success_message; ?></div>
            <?php endif; ?>
            
            <?php if (!empty($error_message)): ?>
                <div class="error-message"><?php echo $error_message; ?></div>
            <?php endif; ?>
            
            <!-- Search and filters -->
            <div class="search-filters">
                <form method="get" action="project_list.php" class="search-form">
                    <div class="search-input">
                        <input type="text" name="search" placeholder="Search projects..." value="<?php echo htmlspecialchars($search ?? ''); ?>">
                    </div>
        </div>
    </main>
    
    <?php include '../includes/footer.php'; ?>
    
    <script src="../assets/js/projects.js"></script>
</body>
</html>