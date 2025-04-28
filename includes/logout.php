<?php
// Start session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Include auth functions
require_once 'auth.php';

// Call logout function
logout();

// Redirect to login page
header('Location: /login.php');
exit;