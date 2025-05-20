<?php
// Enable error reporting for debugging
ini_set('display_errors', 1);
error_reporting(E_ALL);

// Start session
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Include authentication functions
require_once '../includes/auth.php';
require_once '../includes/functions.php';

// Require login for this page
require_login();

// Include database connection
require_once '../config/database.php';

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

// Get activities - first try to get equipment checkout/returns
$activities = [];

// Get equipment checkouts/returns
$checkoutQuery = "SELECT 
    pe.id, 
    p.id AS project_id, 
    p.title AS project_title, 
    e.id AS equipment_id, 
    e.name AS equipment_name, 
    pe.checkout_date, 
    pe.return_date, 
    pe.notes,
    'checkout_return' AS activity_type,
    CASE 
        WHEN pe.return_date IS NOT NULL THEN pe.return_date 
        ELSE pe.checkout_date 
    END AS activity_date
FROM project_equipment pe
JOIN projects p ON pe.project_id = p.id
JOIN equipment e ON pe.equipment_id = e.id";

// Get recently added equipment
$equipmentAddedQuery = "SELECT 
    id AS equipment_id, 
    name AS equipment_name, 
    created_at AS activity_date,
    'equipment_added' AS activity_type
FROM equipment";

// Get recently added projects
$projectAddedQuery = "SELECT 
    id AS project_id, 
    title AS project_title, 
    created_at AS activity_date,
    'project_added' AS activity_type
FROM projects";

// Combine all activities with UNION
$activitiesQuery = "
    SELECT * FROM (
        $checkoutQuery
        UNION ALL
        SELECT 
            NULL as id, 
            NULL as project_id, 
            NULL as project_title, 
            equipment_id, 
            equipment_name, 
            NULL as checkout_date, 
            NULL as return_date, 
            NULL as notes, 
            activity_type,
            activity_date 
        FROM ($equipmentAddedQuery) AS e
        UNION ALL
        SELECT 
            NULL as id, 
            project_id, 
            project_title, 
            NULL as equipment_id, 
            NULL as equipment_name, 
            NULL as checkout_date, 
            NULL as return_date, 
            NULL as notes, 
            activity_type,
            activity_date 
        FROM ($projectAddedQuery) AS p
    ) as combined_activities
    ORDER BY activity_date DESC
    LIMIT 10";

$activitiesResult = $conn->query($activitiesQuery);

// Check for SQL errors
if (!$activitiesResult) {
    // For debugging, show the SQL error
    $error = $conn->error;
    error_log("SQL Error in activities query: $error");
    
    // Fallback to just equipment checkout/returns
    $activitiesQuery = "SELECT pe.id, p.id AS project_id, p.title AS project_title, e.id AS equipment_id, 
        e.name AS equipment_name, pe.checkout_date, pe.return_date, pe.notes, 
        'checkout_return' AS activity_type,
        CASE 
            WHEN pe.return_date IS NOT NULL THEN pe.return_date 
            ELSE pe.checkout_date 
        END AS activity_date
    FROM project_equipment pe
    JOIN projects p ON pe.project_id = p.id
    JOIN equipment e ON pe.equipment_id = e.id
    ORDER BY activity_date DESC
    LIMIT 10";
    
    $activitiesResult = $conn->query($activitiesQuery);
}

// Fetch the activity data
if ($activitiesResult && $activitiesResult->num_rows > 0) {
    while ($row = $activitiesResult->fetch_assoc()) {
        $activities[] = $row;
    }
}

// If still no activities, try a simpler approach - get last created equipment and projects
if (empty($activities)) {
    // Get most recent equipment
    $recentEquipmentQuery = "SELECT 
        id AS equipment_id, 
        name AS equipment_name, 
        created_at AS activity_date,
        'equipment_added' AS activity_type 
    FROM equipment 
    ORDER BY created_at DESC 
    LIMIT 5";
    
    $recentEquipmentResult = $conn->query($recentEquipmentQuery);
    if ($recentEquipmentResult && $recentEquipmentResult->num_rows > 0) {
        while ($row = $recentEquipmentResult->fetch_assoc()) {
            $activities[] = $row;
        }
    }
    
    // Get most recent projects
    $recentProjectsQuery = "SELECT 
        id AS project_id, 
        title AS project_title, 
        created_at AS activity_date,
        'project_added' AS activity_type 
    FROM projects 
    ORDER BY created_at DESC 
    LIMIT 5";
    
    $recentProjectsResult = $conn->query($recentProjectsQuery);
    if ($recentProjectsResult && $recentProjectsResult->num_rows > 0) {
        while ($row = $recentProjectsResult->fetch_assoc()) {
            $activities[] = $row;
        }
    }
    
    // Sort by date
    usort($activities, function($a, $b) {
        return strtotime($b['activity_date']) - strtotime($a['activity_date']);
    });
    
    // Limit to 10
    $activities = array_slice($activities, 0, 10);
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
    <link rel="stylesheet" href="../assets/css/styles.css">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
</head>
<body>
    <?php include '../includes/header.php'; ?>
    
    <main>
        <div class="container">
            <div class="page-header">
                <h1 class="page-title">Dashboard</h1>
                
                <div class="page-actions">
                    <?php if (is_admin()): ?>
                    <a href="equipment_detail.php?action=add" class="btn-primary">
                        <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <line x1="12" y1="5" x2="12" y2="19"></line>
                            <line x1="5" y1="12" x2="19" y2="12"></line>
                        </svg>
                        Add Equipment
                    </a>
                    <a href="project_detail.php?action=add" class="btn-secondary">
                        <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <line x1="12" y1="5" x2="12" y2="19"></line>
                            <line x1="5" y1="12" x2="19" y2="12"></line>
                        </svg>
                        Add Project
                    </a>
                    <?php endif; ?>
                </div>
            </div>
            
            <div class="dashboard-stats">
                <div class="stat-card">
                    <h3>Total Equipment</h3>
                    <div class="stat-number"><?php echo $equipment['total'] ?? 0; ?></div>
                </div>
                
                <div class="stat-card">
                    <h3>Available Equipment</h3>
                    <div class="stat-number"><?php echo $equipment['available'] ?? 0; ?></div>
                </div>
                
                <div class="stat-card">
                    <h3>Equipment In Use</h3>
                    <div class="stat-number"><?php echo $equipment['in_use'] ?? 0; ?></div>
                </div>
                
                <div class="stat-card">
                    <h3>Total Projects</h3>
                    <div class="stat-number"><?php echo $projects['total'] ?? 0; ?></div>
                </div>
            </div>
            
            <div class="card">
                <div class="section-header">
                    <h2 class="card-title">
                        <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="margin-right: 0.5rem; vertical-align: -0.125em;">
                            <polyline points="22 12 18 12 15 21 9 3 6 12 2 12"></polyline>
                        </svg>
                        Recent Activities
                    </h2>
                </div>
                
                <?php if (empty($activities)): ?>
                    <div style="padding: 1.5rem 0; text-align: center; color: var(--muted-foreground);">
                        <svg xmlns="http://www.w3.org/2000/svg" width="36" height="36" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="margin: 0 auto 1rem; opacity: 0.5;">
                            <circle cx="12" cy="12" r="10"></circle>
                            <line x1="12" y1="8" x2="12" y2="12"></line>
                            <line x1="12" y1="16" x2="12" y2="16"></line>
                        </svg>
                        <p>No recent activities. Start by adding equipment or projects.</p>
                    </div>
                <?php else: ?>
                    <div class="table-container">
                        <table>
                            <thead>
                                <tr>
                                    <th>Activity</th>
                                    <th>Item</th>
                                    <th>Date</th>
                                    <th>Details</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($activities as $activity): ?>
                                    <tr>
                                        <td>
                                            <?php if (isset($activity['activity_type']) && $activity['activity_type'] == 'equipment_added'): ?>
                                                <span class="badge status-available">Equipment Added</span>
                                            <?php elseif (isset($activity['activity_type']) && $activity['activity_type'] == 'project_added'): ?>
                                                <span class="badge status-in-use">Project Created</span>
                                            <?php elseif (isset($activity['return_date']) && $activity['return_date']): ?>
                                                <span class="badge status-available">Equipment Returned</span>
                                            <?php else: ?>
                                                <span class="badge status-in-use">Equipment Checked Out</span>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <?php if (isset($activity['equipment_name']) && $activity['equipment_name']): ?>
                                                <a href="equipment_detail.php?id=<?php echo $activity['equipment_id']; ?>">
                                                    <?php echo htmlspecialchars($activity['equipment_name']); ?>
                                                </a>
                                            <?php elseif (isset($activity['project_title']) && $activity['project_title']): ?>
                                                <a href="project_detail.php?id=<?php echo $activity['project_id']; ?>">
                                                    <?php echo htmlspecialchars($activity['project_title']); ?>
                                                </a>
                                            <?php else: ?>
                                                -
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <?php 
                                            if (isset($activity['activity_date'])) {
                                                echo format_date($activity['activity_date']);
                                            } elseif (isset($activity['return_date']) && $activity['return_date']) {
                                                echo format_date($activity['return_date']);
                                            } elseif (isset($activity['checkout_date'])) {
                                                echo format_date($activity['checkout_date']);
                                            } else {
                                                echo "Unknown date";
                                            }
                                            ?>
                                        </td>
                                        <td>
                                            <?php if (isset($activity['activity_type']) && $activity['activity_type'] == 'equipment_added'): ?>
                                                <a href="equipment_detail.php?id=<?php echo $activity['equipment_id']; ?>" class="btn-secondary" style="padding: 0.25rem 0.5rem; font-size: 0.75rem;">View Equipment</a>
                                            <?php elseif (isset($activity['activity_type']) && $activity['activity_type'] == 'project_added'): ?>
                                                <a href="project_detail.php?id=<?php echo $activity['project_id']; ?>" class="btn-secondary" style="padding: 0.25rem 0.5rem; font-size: 0.75rem;">View Project</a>
                                            <?php elseif (isset($activity['id']) && isset($activity['return_date']) && $activity['return_date']): ?>
                                                <a href="project_detail.php?id=<?php echo $activity['project_id']; ?>" class="btn-secondary" style="padding: 0.25rem 0.5rem; font-size: 0.75rem;">View Details</a>
                                            <?php elseif (isset($activity['id'])): ?>
                                                <?php if (is_admin()): ?>
                                                    <a href="return_equipment.php?id=<?php echo $activity['id']; ?>" class="btn-warning" style="padding: 0.25rem 0.5rem; font-size: 0.75rem;">Return</a>
                                                <?php else: ?>
                                                    <a href="project_detail.php?id=<?php echo $activity['project_id']; ?>" class="btn-secondary" style="padding: 0.25rem 0.5rem; font-size: 0.75rem;">View Details</a>
                                                <?php endif; ?>
                                            <?php endif; ?>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php endif; ?>
            </div>
            
            <div class="card">
                <div class="section-header">
                    <h2 class="card-title">
                        <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="margin-right: 0.5rem; vertical-align: -0.125em;">
                            <path d="M21 16V8a2 2 0 0 0-1-1.73l-7-4a2 2 0 0 0-2 0l-7 4A2 2 0 0 0 3 8v8a2 2 0 0 0 1 1.73l7 4a2 2 0 0 0 2 0l7-4A2 2 0 0 0 21 16z"></path>
                            <polyline points="3.27 6.96 12 12.01 20.73 6.96"></polyline>
                            <line x1="12" y1="22.08" x2="12" y2="12"></line>
                        </svg>
                        Quick Links
                    </h2>
                </div>
                
                <div style="display: grid; grid-template-columns: repeat(auto-fill, minmax(200px, 1fr)); gap: 1rem;">
                    <a href="equipment_list.php" class="card" style="margin-bottom: 0; display: flex; flex-direction: column; align-items: center; justify-content: center; padding: 1.5rem; text-align: center; text-decoration: none; color: var(--foreground);">
                        <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="margin-bottom: 0.75rem;">
                            <rect x="3" y="3" width="18" height="18" rx="2" ry="2"></rect>
                            <line x1="3" y1="9" x2="21" y2="9"></line>
                            <line x1="9" y1="21" x2="9" y2="9"></line>
                        </svg>
                        <span style="font-weight: 500;">View All Equipment</span>
                    </a>
                    
                    <a href="project_list.php" class="card" style="margin-bottom: 0; display: flex; flex-direction: column; align-items: center; justify-content: center; padding: 1.5rem; text-align: center; text-decoration: none; color: var(--foreground);">
                        <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="margin-bottom: 0.75rem;">
                            <polygon points="12 2 2 7 12 12 22 7 12 2"></polygon>
                            <polyline points="2 17 12 22 22 17"></polyline>
                            <polyline points="2 12 12 17 22 12"></polyline>
                        </svg>
                        <span style="font-weight: 500;">View All Projects</span>
                    </a>
                    
                    <?php if (is_admin()): ?>
                    <a href="checkout_equipment.php" class="card" style="margin-bottom: 0; display: flex; flex-direction: column; align-items: center; justify-content: center; padding: 1.5rem; text-align: center; text-decoration: none; color: var(--foreground);">
                        <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="margin-bottom: 0.75rem;">
                            <path d="M21 10V8a2 2 0 0 0-1-1.73l-7-4a2 2 0 0 0-2 0l-7 4A2 2 0 0 0 3 8v8a2 2 0 0 0 1 1.73l7 4a2 2 0 0 0 2 0l2-1.14"></path>
                            <path d="M16.5 9.4L7.55 4.24"></path>
                            <polyline points="3.29 7 12 12 20.71 7"></polyline>
                            <line x1="12" y1="22" x2="12" y2="12"></line>
                            <circle cx="18.5" cy="15.5" r="2.5"></circle>
                            <path d="M20.27 17.27L22 19"></path>
                        </svg>
                        <span style="font-weight: 500;">Checkout Equipment</span>
                    </a>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </main>
    
    <?php include '../includes/footer.php'; ?>
    
    <script src="../assets/js/main.js"></script>
</body>
</html>