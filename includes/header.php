<?php
// Start session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
?>
<header>
    <div class="container header-container">
        <div class="logo">
            <a href="/index.php">Equipment Tracking System</a>
        </div>
        
        <nav>
            <ul>
                <li><a href="/index.php">Dashboard</a></li>
                <li><a href="/pages/equipment_list.php">Equipment</a></li>
                <li><a href="/pages/project_list.php">Projects</a></li>
                <?php if (isset($_SESSION['role']) && $_SESSION['role'] === 'admin'): ?>
                <li><a href="/pages/users.php">Users</a></li>
                <?php endif; ?>
                <li><a href="/includes/logout.php">Logout (<?php echo htmlspecialchars($_SESSION['student_id'] ?? 'User'); ?>)</a></li>
            </ul>
        </nav>
    </div>
</header>