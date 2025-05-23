<?php
// Include the activity logger
require_once 'activity_logger.php';

// Start session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Log the logout activity
if (isset($_SESSION['user_id'])) {
    logUserActivity('logout', 'ms_logout.php', 'User initiated logout');
    trackUserSession('logout');
}

// Destroy the session
$_SESSION = array();
if (isset($_COOKIE[session_name()])) {
    setcookie(session_name(), '', time()-42000, '/');
}
session_destroy();

// Redirect to login page
header("Location: ms_index.php");
exit();
?>