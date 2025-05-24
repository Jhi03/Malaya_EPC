<?php
session_start();
include('validate_login.php');

// Ensure the user is logged in and has admin rights
$allowed_roles = ['admin', 'superadmin'];
if (!in_array($_SESSION['role'], $allowed_roles)) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

// Get the employee ID from the request
$employee_id = isset($_GET['employee_id']) ? intval($_GET['employee_id']) : 0;

if ($employee_id <= 0) {
    echo json_encode(['success' => false, 'message' => 'Invalid employee ID']);
    exit;
}

// Database connection
$conn = new mysqli("localhost", "u188693564_adminsolar", "@Malayasolarenergies1", "u188693564_malayasol");

if ($conn->connect_error) {
    echo json_encode(['success' => false, 'message' => 'Database connection failed']);
    exit;
}

// Fetch user account details
$stmt = $conn->prepare("
    SELECT 
        u.username, 
        u.email, 
        u.role, 
        u.account_status, 
        u.failed_attempts,
        u.authenticator_secret,
        u.preferred_2fa,
        u.last_login,
        e.first_name,
        e.last_name
    FROM users u
    JOIN employee e ON u.employee_id = e.employee_id
    WHERE u.employee_id = ?
");

$stmt->bind_param("i", $employee_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 1) {
    $user = $result->fetch_assoc();
    
    // Mask sensitive data
    $user['has_authenticator'] = !empty($user['authenticator_secret']);
    unset($user['authenticator_secret']); // Don't send the actual secret
    
    echo json_encode([
        'success' => true,
        'username' => $user['username'],
        'email' => $user['email'],
        'role' => $user['role'],
        'account_status' => $user['account_status'],
        'failed_attempts' => $user['failed_attempts'],
        'authenticator_secret' => $user['has_authenticator'],
        'preferred_2fa' => $user['preferred_2fa'],
        'last_login' => $user['last_login'],
        'full_name' => $user['first_name'] . ' ' . $user['last_name']
    ]);
} else {
    echo json_encode(['success' => false, 'message' => 'User not found']);
}

$stmt->close();
$conn->close();
?>