<?php
session_start();

// Check if user is logged in
if (!isset($_SESSION['username'])) {
    header("Location: ms_login.php");
    exit();
}

// Get the logged-in user's details
$user_id = $_SESSION['user_id'];
$username = $_SESSION['username'];
$role = $_SESSION['role'];
$employee_id = $_SESSION['employee_id'];

// Check employee status from database
$conn = new mysqli("localhost", "root", "", "malayasol");

// Check for DB connection error
if ($conn->connect_error) {
    die("Database connection failed: " . $conn->connect_error);
}

// Query to check the user's employee status
$stmt = $conn->prepare("
    SELECT e.employment_status
    FROM employee e
    WHERE e.employee_id = ?
");
$stmt->bind_param("i", $employee_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result && $result->num_rows == 1) {
    $user = $result->fetch_assoc();

    // Check if the user is active
    if ($user['employment_status'] !== 'active') {
        // If the user is not active, log them out and redirect to login
        session_unset(); // Unset session variables
        session_destroy(); // Destroy the session
        header("Location: ms_login.php?error=account_inactive");
        exit();
    }
} else {
    // If the user is not found or any error occurs, log them out and redirect to login
    session_unset();
    session_destroy();
    header("Location: ms_login.php?error=account_not_found");
    exit();
}

$stmt->close();
$conn->close();
?>