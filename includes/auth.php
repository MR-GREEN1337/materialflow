<?php
// Start session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/../config/database.php';

/**
 * Authenticate user by student ID only (as per requirements)
 * 
 * @param string $student_id The student ID
 * @return bool Whether authentication was successful
 */
function login($student_id) {
    // Validate student ID
    if (empty($student_id)) {
        return false;
    }
    
    // Get user by student ID
    $sql = "SELECT * FROM users WHERE student_id = ?";
    $users = query($sql, [$student_id]);
    
    if (count($users) === 1) {
        // User found, set session
        $_SESSION['user_id'] = $users[0]['id'];
        $_SESSION['student_id'] = $users[0]['student_id'];
        $_SESSION['name'] = $users[0]['name'];
        $_SESSION['role'] = $users[0]['role'];
        $_SESSION['logged_in'] = true;
        
        // Update last login time (optional)
        $updateSql = "UPDATE users SET last_login = CURRENT_TIMESTAMP WHERE id = ?";
        query($updateSql, [$users[0]['id']]);
        
        return true;
    }
    
    return false;
}

/**
 * Register a new user (Student ID authentication only)
 */
function register_user($student_id, $name = null, $email = null) {
    // Check if student ID already exists
    $checkSql = "SELECT id FROM users WHERE student_id = ?";
    $existingUsers = query($checkSql, [$student_id]);
    
    if (count($existingUsers) > 0) {
        return ['success' => false, 'message' => 'Student ID already registered'];
    }
    
    // Insert new user (with default password hash since we're only using student ID)
    $insertSql = "INSERT INTO users (student_id, password_hash, name, email) VALUES (?, '', ?, ?)";
    $result = query($insertSql, [$student_id, $name, $email]);
    
    if ($result['affected_rows'] === 1) {
        return ['success' => true, 'user_id' => $result['insert_id']];
    } else {
        return ['success' => false, 'message' => 'Failed to create user'];
    }
}

/**
 * Check if user is logged in
 */
function is_logged_in() {
    return isset($_SESSION['logged_in']) && $_SESSION['logged_in'] === true;
}

/**
 * Check if the current user is an admin
 */
function is_admin() {
    return is_logged_in() && $_SESSION['role'] === 'admin';
}

/**
 * Logout the current user
 */
function logout() {
    // Unset all session variables
    $_SESSION = [];
    
    // If it's desired to kill the session, also delete the session cookie
    if (ini_get("session.use_cookies")) {
        $params = session_get_cookie_params();
        setcookie(session_name(), '', time() - 42000,
            $params["path"], $params["domain"],
            $params["secure"], $params["httponly"]
        );
    }
    
    // Finally, destroy the session
    session_destroy();
}

/**
 * Redirect if not logged in
 */
function require_login() {
    if (!is_logged_in()) {
        header('Location: /login.php');
        exit;
    }
}

/**
 * Redirect if not admin
 */
function require_admin() {
    require_login();
    
    if (!is_admin()) {
        header('Location: /index.php?error=unauthorized');
        exit;
    }
}