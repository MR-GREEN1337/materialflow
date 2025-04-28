<?php
// Start session
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Include authentication functions
require_once 'includes/auth.php';
require_once 'includes/functions.php';

// Require login for this page
require_login();

// Include database connection
require_once 'config/database.php';

// Get dashboard stats
$conn = connect_db();

// Count equipment items
$equipmentQuery = "SELECT 
    COUNT(*) AS total,
    SUM(CASE WHEN status = 'available' THEN 1 ELSE 0 END) AS available,
    SUM(CASE WHEN status = 'in_use' THEN 1 ELSE 0 END) AS in_use,
    SUM(CASE WHEN status = 'broken' THEN 1 ELSE 0 END) AS broken,
    SUM(CASE WHEN status = 'lost' THEN 1 ELSE 0 END) AS lost
FROM equipment";
$equipmentResult = $conn->query($equipmentQuery);
$equipment = $equipmentResult->fetch_assoc();

// Count projects
$projectsQuery = "SELECT 
    COUNT(*) AS total,
    SUM(CASE WHEN status = 'ongoing' THEN 1 ELSE 0 END) AS ongoing,
    SUM(CASE WHEN status = 'completed' THEN 1 ELSE 0 END) AS completed,
    SUM(CASE WHEN status = 'archived' THEN 1 ELSE 0 END) AS archived
FROM projects";
$projectsResult = $conn->query($projectsQuery);
$projects = $projectsResult->fetch_assoc();

// Get recent activities (equipment check-outs and returns)
$activitiesQuery = "SELECT pe.id, p.title AS project_title, e.name AS equipment_name, 
    pe.checkout_date, pe.return_date, pe.notes
FROM project_equipment pe
JOIN projects p ON pe.project_id = p.id
JOIN equipment e ON pe.equipment_id = e.id
ORDER BY 
    CASE WHEN pe.checkout_date IS NOT NULL THEN pe.checkout_date ELSE pe.return_date END DESC
LIMIT 10";
$activitiesResult = $conn->query($activitiesQuery);
$activities = [];
while ($row = $activitiesResult->fetch_assoc()) {
    $activities[] = $row;
}

// Close connection
$conn->close();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard - Equipment Tracking System</title>
    <link rel="stylesheet" href="assets/css/styles.css">
</head>
<body>
    <?php include 'includes/header.php'; ?>
    
    <main>
        <div class="container">
            <h1 class="page-title">Dashboard</h1>
            
            <div class="dashboard-stats">
                <div class="stat-card">
                    <h3>Total Equipment</h3>
                    <div class="stat-number"><?php echo $equipment['total']; ?></div>
                </div>
    </main>
    
    <?php include 'includes/footer.php'; ?>
    
    <script src="assets/js/main.js"></script>
</body>
</html>