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

// Process search and filters
$status = isset($_GET['status']) ? $_GET['status'] : null;
$search = isset($_GET['search']) ? $_GET['search'] : null;

// Get equipment list
$equipment_list = get_all_equipment($status, $search);

// Count status totals for filters
$conn = connect_db();
$statusCountQuery = "SELECT 
    SUM(CASE WHEN status = 'available' THEN 1 ELSE 0 END) AS available,
    SUM(CASE WHEN status = 'in_use' THEN 1 ELSE 0 END) AS in_use,
    SUM(CASE WHEN status = 'broken' THEN 1 ELSE 0 END) AS broken,
    SUM(CASE WHEN status = 'lost' THEN 1 ELSE 0 END) AS lost,
    SUM(CASE WHEN status = 'deprecated' THEN 1 ELSE 0 END) AS deprecated,
    COUNT(*) AS total
FROM equipment";
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
    <title>Equipment List - Equipment Tracking System</title>
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
                        <rect x="3" y="3" width="18" height="18" rx="2" ry="2"></rect>
                        <line x1="3" y1="9" x2="21" y2="9"></line>
                        <line x1="9" y1="21" x2="9" y2="9"></line>
                    </svg>
                    Equipment List
                </h1>
                
                <div class="page-actions">
                    <?php if (is_admin()): ?>
                    <a href="equipment_detail.php?action=add" class="btn-primary">
                        <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <line x1="12" y1="5" x2="12" y2="19"></line>
                            <line x1="5" y1="12" x2="19" y2="12"></line>
                        </svg>
                        Add New Equipment
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
                <form method="get" action="equipment_list.php" class="search-form" style="display: flex; gap: 1rem; align-items: center; flex-wrap: wrap;">
                    <div class="search-input" style="position: relative; flex: 1;">
                        <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="position: absolute; left: 10px; top: 50%; transform: translateY(-50%); color: var(--muted-foreground);">
                            <circle cx="11" cy="11" r="8"></circle>
                            <line x1="21" y1="21" x2="16.65" y2="16.65"></line>
                        </svg>
                        <input type="text" name="search" placeholder="Search equipment..." value="<?php echo htmlspecialchars($search ?? ''); ?>" style="padding-left: 2.25rem;">
                    </div>
                    
                    <div class="filter-group">
                        <select name="status">
                            <option value="">All Status (<?php echo $statusCounts['total']; ?>)</option>
                            <option value="available" <?php echo $status === 'available' ? 'selected' : ''; ?>>
                                Available (<?php echo $statusCounts['available']; ?>)
                            </option>
                            <option value="in_use" <?php echo $status === 'in_use' ? 'selected' : ''; ?>>
                                In Use (<?php echo $statusCounts['in_use']; ?>)
                            </option>
                            <option value="broken" <?php echo $status === 'broken' ? 'selected' : ''; ?>>
                                Broken (<?php echo $statusCounts['broken']; ?>)
                            </option>
                            <option value="lost" <?php echo $status === 'lost' ? 'selected' : ''; ?>>
                                Lost (<?php echo $statusCounts['lost']; ?>)
                            </option>
                            <option value="deprecated" <?php echo $status === 'deprecated' ? 'selected' : ''; ?>>
                                Deprecated (<?php echo $statusCounts['deprecated']; ?>)
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
                        <a href="equipment_list.php" class="btn-secondary">
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
            
            <!-- Equipment table -->
            <div class="card">
                <div class="table-container">
                    <table>
                        <thead>
                            <tr>
                                <th>Equipment Name</th>
                                <th>Status</th>
                                <th>Location</th>
                                <th>Vendor</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($equipment_list)): ?>
                                <tr>
                                    <td colspan="5" style="text-align: center; padding: 2rem;">
                                        <svg xmlns="http://www.w3.org/2000/svg" width="32" height="32" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="margin: 0 auto 1rem; display: block; opacity: 0.5;">
                                            <path d="M2 9h20"></path>
                                            <path d="M6 20h12a4 4 0 0 0 4-4V9"></path>
                                            <path d="M8 5V3c0-1.1.9-2 2-2h4a2 2 0 0 1 2 2v2"></path>
                                            <path d="M12 12v4"></path>
                                        </svg>
                                        <p style="color: var(--muted-foreground);">No equipment found</p>
                                        <?php if (is_admin()): ?>
                                            <a href="equipment_detail.php?action=add" class="btn-primary" style="margin-top: 1rem; display: inline-flex;">
                                                <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="margin-right: 0.5rem;">
                                                    <line x1="12" y1="5" x2="12" y2="19"></line>
                                                    <line x1="5" y1="12" x2="19" y2="12"></line>
                                                </svg>
                                                Add First Equipment
                                            </a>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                            <?php else: ?>
                                <?php foreach ($equipment_list as $equipment): ?>
                                    <tr>
                                        <td>
                                            <a href="equipment_detail.php?id=<?php echo $equipment['id']; ?>" style="font-weight: 500; color: var(--foreground);">
                                                <?php echo htmlspecialchars($equipment['name']); ?>
                                            </a>
                                        </td>
                                        <td><?php echo get_equipment_status_label($equipment['status']); ?></td>
                                        <td><?php echo htmlspecialchars($equipment['storage_location'] ?? 'N/A'); ?></td>
                                        <td><?php echo htmlspecialchars($equipment['vendor'] ?? 'N/A'); ?></td>
                                        <td>
                                            <div style="display: flex; gap: 0.5rem;">
                                                <a href="equipment_detail.php?id=<?php echo $equipment['id']; ?>" class="btn-primary" style="padding: 0.5rem; display: inline-flex; align-items: center; justify-content: center;" title="View Details">
                                                    <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                                        <path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"></path>
                                                        <circle cx="12" cy="12" r="3"></circle>
                                                    </svg>
                                                </a>
                                                
                                                <?php if (is_admin()): ?>
                                                    <a href="equipment_detail.php?id=<?php echo $equipment['id']; ?>&action=edit" class="btn-secondary" style="padding: 0.5rem; display: inline-flex; align-items: center; justify-content: center;" title="Edit Equipment">
                                                        <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                                            <path d="M17 3a2.828 2.828 0 1 1 4 4L7.5 20.5 2 22l1.5-5.5L17 3z"></path>
                                                        </svg>
                                                    </a>
                                                    
                                                    <?php if ($equipment['status'] === 'available'): ?>
                                                        <a href="checkout_equipment.php?id=<?php echo $equipment['id']; ?>" class="btn-warning" style="padding: 0.5rem; display: inline-flex; align-items: center; justify-content: center;" title="Checkout Equipment">
                                                            <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                                                <path d="M16 3h5v5"></path>
                                                                <path d="M21 3 4 20"></path>
                                                                <path d="M21 16v5h-5"></path>
                                                                <path d="M3 8h5V3"></path>
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
    
    <script src="../assets/js/equipment.js"></script>
</body>
</html>