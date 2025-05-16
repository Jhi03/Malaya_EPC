<?php
session_start();

// Check if user is logged in
if (!isset($_SESSION['username'])) {
    header("Location: ms_login.php");
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
    SELECT e.status
    FROM users u
    LEFT JOIN employee e ON u.employee_id = e.employee_id
    WHERE u.user_id = ?
");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result && $result->num_rows == 1) {
    $user = $result->fetch_assoc();

    // Check if the user is active
    if ($user['status'] !== 'active') {
        // If the user is not active, log them out and redirect to login
        session_unset(); // Unset session variables
        session_destroy(); // Destroy the session
        header("Location: ms_login.php");
        exit();
    }
} else {
    // If the user is not found or any error occurs, log them out and redirect to login
    session_unset();
    session_destroy();
    header("Location: ms_login.php");
    exit();
}

$stmt->close();
$conn->close();
?>
