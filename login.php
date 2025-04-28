<?php
// Start session
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Include authentication functions
require_once 'includes/auth.php';

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
</head>
<body>
    <div class="login-container">
        <div class="login-form">
            <h1>Equipment Tracking System</h1>
            <h2>Login</h2>
            
            <?php if (!empty($error)): ?>
                <div class="error-message"><?php echo $error; ?></div>
            <?php endif; ?>
            
            <form method="post" action="login.php">
                <div class="form-group">
                    <label for="student_id">Student ID:</label>
                    <input type="text" id="student_id" name="student_id" required>
                </div>
                
                <div class="form-group">
                    <button type="submit" class="btn-primary">Login</button>
                </div>
            </form>
        </div>
    </div>
    
    <script src="assets/js/auth.js"></script>
</body>
</html>