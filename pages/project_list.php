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
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
</head>
<body>
    <?php include '../includes/header.php'; ?>
    
    <main>
        <div class="container">
            <div class="page-header">
                <h1 class="page-title">
                    <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="margin-right: 0.5rem; vertical-align: -0.125em;">
                        <polygon points="12 2 2 7 12 12 22 7 12 2"></polygon>
                        <polyline points="2 17 12 22 22 17"></polyline>
                        <polyline points="2 12 12 17 22 12"></polyline>
                    </svg>
                    Projects List
                </h1>
                
                <div class="page-actions">
                    <?php if (is_admin()): ?>
                    <a href="project_detail.php?action=add" class="btn-primary">
                        <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <line x1="12" y1="5" x2="12" y2="19"></line>
                            <line x1="5" y1="12" x2="19" y2="12"></line>
                        </svg>
                        Add New Project
                    </a>
                    <?php endif; ?>
                </div>
            </div>
            
            <?php if (!empty($success_message)): ?>
                <div class="success-message">
                    <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="margin-right: 0.5rem; vertical-align: -0.125em;">
                        <path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"></path>
                        <polyline points="22 4 12 14.01 9 11.01"></polyline>
                    </svg>
                    <?php echo $success_message; ?>
                </div>
            <?php endif; ?>
            
            <?php if (!empty($error_message)): ?>
                <div class="error-message">
                    <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="margin-right: 0.5rem; vertical-align: -0.125em;">
                        <circle cx="12" cy="12" r="10"></circle>
                        <line x1="12" y1="8" x2="12" y2="12"></line>
                        <line x1="12" y1="16" x2="12.01" y2="16"></line>
                    </svg>
                    <?php echo $error_message; ?>
                </div>
            <?php endif; ?>
            
            <!-- Search and filters -->
            <div class="search-filters">
                <form method="get" action="project_list.php" class="search-form" style="display: flex; gap: 1rem; align-items: center; flex-wrap: wrap;">
                    <div class="search-input" style="position: relative; flex: 1;">
                        <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="position: absolute; left: 10px; top: 50%; transform: translateY(-50%); color: var(--muted-foreground);">
                            <circle cx="11" cy="11" r="8"></circle>
                            <line x1="21" y1="21" x2="16.65" y2="16.65"></line>
                        </svg>
                        <input type="text" name="search" placeholder="Search projects..." value="<?php echo htmlspecialchars($search ?? ''); ?>" style="padding-left: 2.25rem;">
                    </div>
                    
                    <div class="filter-group">
                        <select name="status">
                            <option value="">All Status (<?php echo $statusCounts['total']; ?>)</option>
                            <option value="ongoing" <?php echo $status === 'ongoing' ? 'selected' : ''; ?>>
                                Ongoing (<?php echo $statusCounts['ongoing']; ?>)
                            </option>
                            <option value="completed" <?php echo $status === 'completed' ? 'selected' : ''; ?>>
                                Completed (<?php echo $statusCounts['completed']; ?>)
                            </option>
                            <option value="archived" <?php echo $status === 'archived' ? 'selected' : ''; ?>>
                                Archived (<?php echo $statusCounts['archived']; ?>)
                            </option>
                        </select>
                    </div>
                    
                    <button type="submit" class="btn-primary">
                        <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="margin-right: 0.25rem;">
                            <polygon points="22 3 2 3 10 12.46 10 19 14 21 14 12.46 22 3"></polygon>
                        </svg>
                        Filter
                    </button>
                    
                    <?php if ($search || $status): ?>
                        <a href="project_list.php" class="btn-secondary">
                            <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="margin-right: 0.25rem;">
                                <circle cx="12" cy="12" r="10"></circle>
                                <line x1="15" y1="9" x2="9" y2="15"></line>
                                <line x1="9" y1="9" x2="15" y2="15"></line>
                            </svg>
                            Clear Filters
                        </a>
                    <?php endif; ?>
                </form>
            </div>
            
            <!-- Projects table -->
            <div class="card">
                <div class="table-container">
                    <table>
                        <thead>
                            <tr>
                                <th>Title</th>
                                <th>Status</th>
                                <th>Course</th>
                                <th>Dates</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($project_list)): ?>
                                <tr>
                                    <td colspan="5" style="text-align: center; padding: 2rem;">
                                        <svg xmlns="http://www.w3.org/2000/svg" width="32" height="32" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="margin: 0 auto 1rem; display: block; opacity: 0.5;">
                                            <rect x="2" y="6" width="20" height="8" rx="1"></rect>
                                            <path d="M6 14v3a1 1 0 0 0 1 1h10a1 1 0 0 0 1-1v-3"></path>
                                            <path d="M4 6v-1a1 1 0 0 1 1-1h14a1 1 0 0 1 1 1v1"></path>
                                        </svg>
                                        <p style="color: var(--muted-foreground);">No projects found</p>
                                        <?php if (is_admin()): ?>
                                            <a href="project_detail.php?action=add" class="btn-primary" style="margin-top: 1rem; display: inline-flex;">
                                                <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="margin-right: 0.5rem;">
                                                    <line x1="12" y1="5" x2="12" y2="19"></line>
                                                    <line x1="5" y1="12" x2="19" y2="12"></line>
                                                </svg>
                                                Create a New Project
                                            </a>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                            <?php else: ?>
                                <?php foreach ($project_list as $project): ?>
                                    <tr>
                                        <td>
                                            <a href="project_detail.php?id=<?php echo $project['id']; ?>" style="font-weight: 500; color: var(--foreground);">
                                                <?php echo htmlspecialchars($project['title']); ?>
                                            </a>
                                        </td>
                                        <td><?php echo get_project_status_label($project['status']); ?></td>
                                        <td><?php echo htmlspecialchars($project['course_name'] ?? 'N/A'); ?></td>
                                        <td>
                                            <?php if ($project['start_date']): ?>
                                                <?php echo format_date($project['start_date']); ?>
                                                <?php if ($project['end_date']): ?>
                                                    to <?php echo format_date($project['end_date']); ?>
                                                <?php endif; ?>
                                            <?php else: ?>
                                                N/A
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <div style="display: flex; gap: 0.5rem;">
                                                <a href="project_detail.php?id=<?php echo $project['id']; ?>" class="btn-primary" style="padding: 0.5rem; display: inline-flex; align-items: center; justify-content: center;" title="View Details">
                                                    <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                                        <path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"></path>
                                                        <circle cx="12" cy="12" r="3"></circle>
                                                    </svg>
                                                </a>
                                                
                                                <?php if (is_admin()): ?>
                                                    <a href="project_detail.php?id=<?php echo $project['id']; ?>&action=edit" class="btn-secondary" style="padding: 0.5rem; display: inline-flex; align-items: center; justify-content: center;" title="Edit Project">
                                                        <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                                            <path d="M17 3a2.828 2.828 0 1 1 4 4L7.5 20.5 2 22l1.5-5.5L17 3z"></path>
                                                        </svg>
                                                    </a>
                                                    <?php if ($project['status'] === 'ongoing'): ?>
                                                        <a href="checkout_equipment.php?project_id=<?php echo $project['id']; ?>" class="btn-warning" style="padding: 0.5rem; display: inline-flex; align-items: center; justify-content: center;" title="Checkout Equipment">
                                                            <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                                                <path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"></path>
                                                                <path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"></path>
                                                            </svg>
                                                        </a>
                                                    <?php endif; ?>
                                                <?php endif; ?>
                                            </div>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </main>
    
    <?php include '../includes/footer.php'; ?>
    
    <script src="../assets/js/projects.js"></script>
</body>
</html>