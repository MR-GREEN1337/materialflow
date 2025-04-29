<?php
// Start session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Include auth functions
require_once 'auth.php';

// Get the base URL with the subfolder
function getBaseUrl() {
    $currentPath = $_SERVER['PHP_SELF'];
    $pathInfo = pathinfo($currentPath);
    $hostName = $_SERVER['HTTP_HOST'];
    $protocol = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https://' : 'http://';
    
    // Since logout.php is in the includes directory, we need to go up one level
    return $protocol . $hostName . dirname(dirname($currentPath));
}

// Call logout function
logout();

// Redirect to login page with proper path
$baseUrl = getBaseUrl();
header("Location: {$baseUrl}/login.php");
exit;