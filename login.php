<?php
// Start session
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Include authentication functions with correct path
require_once __DIR__ . '/includes/auth.php';

// Debug: Show errors during development
ini_set('display_errors', 1);
error_reporting(E_ALL);

// Check if already logged in
if (is_logged_in()) {
    header('Location: index.php');
    exit;
}

// Check for login submission
$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $student_id = trim($_POST['student_id'] ?? '');
    
    if (empty($student_id)) {
        $error = 'Please enter your student ID';
    } else {
        if (login($student_id)) {
            // Redirect to dashboard on successful login
            header('Location: index.php');
            exit;
        } else {
            $error = 'Invalid student ID';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - Equipment Tracking System</title>
    <link rel="stylesheet" href="assets/css/styles.css">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
</head>
<body>
    <div class="login-container">
        <div class="login-form">
            <div style="text-align: center; margin-bottom: 2rem;">
                <svg xmlns="http://www.w3.org/2000/svg" width="48" height="48" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="feather feather-box" style="margin: 0 auto 1rem;">
                    <path d="M21 16V8a2 2 0 0 0-1-1.73l-7-4a2 2 0 0 0-2 0l-7 4A2 2 0 0 0 3 8v8a2 2 0 0 0 1 1.73l7 4a2 2 0 0 0 2 0l7-4A2 2 0 0 0 21 16z"></path>
                    <polyline points="3.27 6.96 12 12.01 20.73 6.96"></polyline>
                    <line x1="12" y1="22.08" x2="12" y2="12"></line>
                </svg>
                <h1>Equipment Tracking System</h1>
                <h2>Sign in to access the dashboard</h2>
            </div>
            
            <?php if (!empty($error)): ?>
                <div class="error-message">
                    <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="margin-right: 0.5rem;">
                        <circle cx="12" cy="12" r="10"></circle>
                        <line x1="12" y1="8" x2="12" y2="12"></line>
                        <line x1="12" y1="16" x2="12.01" y2="16"></line>
                    </svg>
                    <?php echo $error; ?>
                </div>
            <?php endif; ?>
            
            <form method="post" action="login.php">
                <div class="form-group">
                    <label for="student_id">Student ID</label>
                    <input type="text" id="student_id" name="student_id" placeholder="Enter your student ID" required>
                </div>
                
                <div class="form-group" style="margin-bottom: 0;">
                    <button type="submit" class="btn-primary" style="width: 100%;">
                        Sign In
                        <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <path d="M9 18l6-6-6-6"></path>
                        </svg>
                    </button>
                </div>
            </form>
            
            <div style="margin-top: 1.5rem; text-align: center; font-size: 0.875rem; color: var(--muted-foreground);">
                <p>Default admin access: student ID "admin"</p>
            </div>
        </div>
    </div>
    
    <script src="assets/js/auth.js"></script>
</body>
</html>