<?php
// Start session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$baseUrl = getBaseUrl();
// Make sure the baseUrl doesn't end with a slash
if (substr($baseUrl, -1) === '/') {
    $baseUrl = substr($baseUrl, 0, -1);
}

// Determine current page for active state
$currentPage = basename($_SERVER['PHP_SELF']);
?>
<header>
    <div class="container header-container">
        <div class="logo">
            <a href="<?php echo $baseUrl; ?>/index.php">
                <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="display: inline-block; vertical-align: -0.25em; margin-right: 0.5rem;">
                    <path d="M21 16V8a2 2 0 0 0-1-1.73l-7-4a2 2 0 0 0-2 0l-7 4A2 2 0 0 0 3 8v8a2 2 0 0 0 1 1.73l7 4a2 2 0 0 0 2 0l7-4A2 2 0 0 0 21 16z"></path>
                    <polyline points="3.27 6.96 12 12.01 20.73 6.96"></polyline>
                    <line x1="12" y1="22.08" x2="12" y2="12"></line>
                </svg>
                Equipment Tracking
            </a>
        </div>
        
        <nav>
            <ul>
                <li>
                    <a href="<?php echo $baseUrl; ?>/index.php" class="<?php echo ($currentPage == 'index.php') ? 'active' : ''; ?>">
                        <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="margin-right: 0.25rem; vertical-align: -0.125em;">
                            <path d="M3 9l9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z"></path>
                            <polyline points="9 22 9 12 15 12 15 22"></polyline>
                        </svg>
                        Dashboard
                    </a>
                </li>
                <li>
                    <a href="<?php echo $baseUrl; ?>/pages/equipment_list.php" class="<?php echo (strpos($currentPage, 'equipment_') !== false) ? 'active' : ''; ?>">
                        <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="margin-right: 0.25rem; vertical-align: -0.125em;">
                            <rect x="3" y="3" width="18" height="18" rx="2" ry="2"></rect>
                            <line x1="3" y1="9" x2="21" y2="9"></line>
                            <line x1="9" y1="21" x2="9" y2="9"></line>
                        </svg>
                        Equipment
                    </a>
                </li>
                <li>
                    <a href="<?php echo $baseUrl; ?>/pages/project_list.php" class="<?php echo (strpos($currentPage, 'project_') !== false) ? 'active' : ''; ?>">
                        <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="margin-right: 0.25rem; vertical-align: -0.125em;">
                            <polygon points="12 2 2 7 12 12 22 7 12 2"></polygon>
                            <polyline points="2 17 12 22 22 17"></polyline>
                            <polyline points="2 12 12 17 22 12"></polyline>
                        </svg>
                        Projects
                    </a>
                </li>
                <?php if (isset($_SESSION['role']) && $_SESSION['role'] === 'admin'): ?>
                <li>
                    <a href="<?php echo $baseUrl; ?>/pages/users.php" class="<?php echo ($currentPage == 'users.php') ? 'active' : ''; ?>">
                        <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="margin-right: 0.25rem; vertical-align: -0.125em;">
                            <path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"></path>
                            <circle cx="12" cy="7" r="4"></circle>
                        </svg>
                        Users
                    </a>
                </li>
                <?php endif; ?>
                <li>
                    <a href="<?php echo $baseUrl; ?>/includes/logout.php">
                        <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="margin-right: 0.25rem; vertical-align: -0.125em;">
                            <path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"></path>
                            <polyline points="16 17 21 12 16 7"></polyline>
                            <line x1="21" y1="12" x2="9" y2="12"></line>
                        </svg>
                        Logout 
                        <span style="opacity: 0.75; margin-left: 0.25rem;">(<?php echo htmlspecialchars($_SESSION['student_id'] ?? 'User'); ?>)</span>
                    </a>
                </li>
            </ul>
        </nav>
    </div>
</header>