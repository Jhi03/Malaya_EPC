<?php
// Include the activity logger and access control
require_once 'activity_logger.php';
require_once 'access_control.php';

session_start();

// Check if user is logged in
if (!isset($_SESSION['username'])) {
    header("Location: ms_index.php");
    exit();
}

$conn = new mysqli("localhost", "u188693564_adminsolar", "@Malayasolarenergies1", "u188693564_malayasol");

// Check for DB connection error
if ($conn->connect_error) {
    die("Database connection failed: " . $conn->connect_error);
}

// Get the logged-in user's employee ID
$user_id = $_SESSION['user_id'];

// Query to check the user's employee status
$stmt = $conn->prepare("
    SELECT e.employment_status, u.account_status
    FROM users u
    LEFT JOIN employee e ON u.employee_id = e.employee_id
    WHERE u.user_id = ?
");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result && $result->num_rows == 1) {
    $user = $result->fetch_assoc();

    // Check if the employee is active and account is not disabled or locked
    if ($user['employment_status'] !== 'active' || $user['account_status'] !== 'active') {
        // Log the forced logout
        logUserActivity('logout', 'validate_login.php', 'Forced logout due to account status change');
        trackUserSession('logout');
        
        // If the user is not active, log them out and redirect to login
        session_unset();
        session_destroy();
        header("Location: ms_index.php");
        exit();
    }
} else {
    // Log the forced logout
    logUserActivity('logout', 'validate_login.php', 'Forced logout - user not found');
    trackUserSession('logout');
    
    // If the user is not found or any error occurs, log them out and redirect to login
    session_unset();
    session_destroy();
    header("Location: ms_index.php");
    exit();
}

// Check if user has access to current page
$current_page = basename($_SERVER['PHP_SELF']);
if (!canAccessPage($current_page, $user_id)) {
    // Log unauthorized access attempt
    logUserActivity('access_denied', $current_page, 'Unauthorized access attempt');
    
    // Redirect to access denied page
    header("Location: access_denied.php");
    exit();
}

// Log page access (optional - you might want to disable this for performance)
if (!isset($_SESSION['last_logged_page']) || $_SESSION['last_logged_page'] !== $current_page) {
    logUserActivity('access', $current_page, 'Page accessed');
    $_SESSION['last_logged_page'] = $current_page;
}

$stmt->close();
$conn->close();
?>