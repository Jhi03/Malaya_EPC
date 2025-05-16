<?php
session_start();

$error = "";
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $conn = new mysqli("localhost", "u188693564_adminsolar", "@Malayasolarenergies1", "u188693564_malayasol");

    // Check for DB connection error
    if ($conn->connect_error) {
        die("Database connection failed: " . $conn->connect_error);
    }

    $username = $_POST['username'];
    $password = $_POST['password'];

    // SQL query to check user credentials and employee status
    $stmt = $conn->prepare("
        SELECT u.*, e.status
        FROM users u
        LEFT JOIN employee e ON u.employee_id = e.employee_id
        WHERE u.username = ? AND u.password = ?
    ");
    $stmt->bind_param("ss", $username, $password);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result && $result->num_rows == 1) {
        $user = $result->fetch_assoc();

        // Check if employee status is active
        if ($user['status'] == 'active') {
            // User is active, proceed with login
            $_SESSION['user_id'] = $user['user_id'];
            $_SESSION['username'] = $user['username'];
            $_SESSION['role'] = $user['role'];

            // Redirect to dashboard
            header("Location: ms_dashboard.php");
            exit();
        } else {
            // Employee is not active
            $error = "Your account is not active. Please contact your administrator.";
        }
    } else {
        // Invalid username or password
        $error = "Invalid username or password.";
    }

    $stmt->close();
    $conn->close();
}
?>

<!DOCTYPE html>
<html lang="en">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>Login</title>
        <link rel="stylesheet" href="css/ms_login.css">
        <link href="https://fonts.googleapis.com/css2?family=Atkinson+Hyperlegible&display=swap" rel="stylesheet">
    </head>
    <body>
        <div class="container">
            <div class="left-panel">
                <div class="login-box">
                    <div class="login-top">
                        <img src="Malaya_Logo.png" class="logo" alt="Malaya Logo">
                        <hr>
                    </div>

                    <div class="login-middle">
                        <form action="ms_login.php" method="POST" id="login-form">
                            <input type="text" name="username" placeholder="username" required>
                            <input type="password" name="password" placeholder="password" required>
                            <div class="forgot-wrapper">
                                <a href="#" class="forgot">forgot password?</a>
                            </div>
                            <button type="submit">LOGIN</button>
                        </form>
                    </div>

                    <!-- Display error message if login fails -->
                    <?php if (!empty($error)): ?>
                        <div class="alert alert-danger text-center"><?= $error ?></div>
                    <?php endif; ?>
                </div>
            </div>
            <div class="right-panel">
                <div class="image-overlay">
                    <img src="login_background/login-solar-panel-1.png" alt="Solar Panels">
                    <div class="welcome-text">Welcome!</div>
                </div>
            </div>
        </div>
    </body>
</html>
