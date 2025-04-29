<?php
ini_set('display_errors', 1);
error_reporting(E_ALL);

// Start session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Include auth functions
require_once __DIR__ . '/auth.php';

// Call logout function
logout();

// Redirect simply
header("Location: ../login.php");
exit;
