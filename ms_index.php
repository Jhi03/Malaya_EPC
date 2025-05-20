<?php
session_start();

// Initialize error variable
$error = '';

// Handle login form submission
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $username = $_POST['username'];
    $password = $_POST['password'];

    // Database connection
    $conn = new mysqli("localhost", "root", "", "malayasol");

    if ($conn->connect_error) {
        die("Database connection failed: " . $conn->connect_error);
    }

    // Fetch user record by username
    $stmt = $conn->prepare("SELECT u.user_id, u.username, u.password, u.role, u.account_status, u.employee_id, e.employment_status 
                            FROM users u 
                            LEFT JOIN employee e ON u.employee_id = e.employee_id 
                            WHERE u.username = ?");
    $stmt->bind_param("s", $username);
    $stmt->execute();
    $result = $stmt->get_result();

    // If user exists
    if ($result->num_rows === 1) {
        $user = $result->fetch_assoc();

        // For plain-text passwords or if you need to support both hashed and unhashed
        if (password_verify($password, $user['password']) || $password === $user['password']) {
            // Check account and employment status
            if ($user['account_status'] === 'active' && $user['employment_status'] === 'active') {
                // Set session variables
                $_SESSION['user_id'] = $user['user_id'];
                $_SESSION['username'] = $user['username'];
                $_SESSION['role'] = $user['role'];
                $_SESSION['employee_id'] = $user['employee_id'];

                // Record the login in login_attempts table
                $ip = $_SERVER['REMOTE_ADDR'];
                $log_sql = "INSERT INTO login_attempts (user_id, attempt_time, ip_address, success, notes) 
                           VALUES (?, NOW(), ?, 1, 'Login successful')";
                
                $log_stmt = $conn->prepare($log_sql);
                $log_stmt->bind_param("is", $user['user_id'], $ip);
                $log_stmt->execute();
                $log_stmt->close();

                // Redirect to dashboard
                header("Location: ms_dashboard.php");
                exit();
            } else {
                $error = "Your account is not active. Please contact an administrator.";
                
                // Log failed attempt
                $ip = $_SERVER['REMOTE_ADDR'];
                $log_sql = "INSERT INTO login_attempts (user_id, attempt_time, ip_address, success, notes) 
                           VALUES (?, NOW(), ?, 0, 'Account inactive')";
                
                $log_stmt = $conn->prepare($log_sql);
                $log_stmt->bind_param("is", $user['user_id'], $ip);
                $log_stmt->execute();
                $log_stmt->close();
            }
        } else {
            $error = "Invalid username or password.";
            
            // Update failed attempts counter and potentially lock account
            $failed_attempts = $user['failed_attempts'] + 1;
            $status_update = "";
            
            if ($failed_attempts >= 3 && $user['account_status'] === 'active') {
                $status_update = ", account_status = 'locked'";
                
                // Log account locking
                $ip = $_SERVER['REMOTE_ADDR'];
                $lock_note = "Account locked - too many failed attempts";
            } else {
                $lock_note = "Failed password";
            }
            
            // Update the failed attempts counter
            $update_sql = "UPDATE users SET failed_attempts = $failed_attempts $status_update WHERE user_id = ?";
            $update_stmt = $conn->prepare($update_sql);
            $update_stmt->bind_param("i", $user['user_id']);
            $update_stmt->execute();
            $update_stmt->close();
            
            // Log the failed attempt
            $ip = $_SERVER['REMOTE_ADDR'];
            $log_sql = "INSERT INTO login_attempts (user_id, attempt_time, ip_address, success, notes) 
                       VALUES (?, NOW(), ?, 0, ?)";
            
            $log_stmt = $conn->prepare($log_sql);
            $log_stmt->bind_param("iss", $user['user_id'], $ip, $lock_note);
            $log_stmt->execute();
            $log_stmt->close();
        }
    } else {
        $error = "Invalid username or password.";
    }

    $stmt->close();
    $conn->close();
}

// If already logged in, redirect to dashboard
if (isset($_SESSION['username']) && !isset($error)) {
    header("Location: ms_dashboard.php");
    exit();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Malaya Solar Energies Inc.</title>
    <link rel="icon" href="images/Malaya_Logo.png" type="image/png">
    <link href="css/index.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Atkinson+Hyperlegible:wght@400;700&display=swap" rel="stylesheet">
</head>
<body>
    <div class="login-container">
        <!-- Brand Panel (Left) -->
        <div class="brand-panel">
            <div class="brand-content">
                <img src="images/Malaya_Logo.png" alt="Malaya Solar Energies Logo" class="brand-logo">
                <h1 class="brand-title">Malaya Solar Energies</h1>
                <p class="brand-description">Access the system to manage your solar projects efficiently and track financial performance.</p>
            </div>
        </div>
        
        <!-- Image Panel (Right) -->
        <div class="image-panel">
            <img src="login_background/login-solar-panel-1.png" alt="Solar Panels" class="background-image">
            <div class="image-overlay"></div>
        </div>
        
        <!-- Login Card (Centered) -->
        <div class="login-card">
            <div class="login-header">
                <h2 class="login-title">Welcome Back</h2>
                <p class="login-subtitle">Please sign in to continue</p>
            </div>
            
            <?php if (!empty($error)): ?>
            <div class="error-message">
                <?= $error ?>
            </div>
            <?php endif; ?>
            
            <form action="ms_index.php" method="POST" class="login-form">
                <div class="form-group">
                    <svg class="input-icon" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"></path>
                        <circle cx="12" cy="7" r="4"></circle>
                    </svg>
                    <input type="text" name="username" class="form-input" placeholder="Username" required>
                </div>
                
                <div class="form-group">
                    <svg class="input-icon" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <rect x="3" y="11" width="18" height="11" rx="2" ry="2"></rect>
                        <path d="M7 11V7a5 5 0 0 1 10 0v4"></path>
                    </svg>
                    <input type="password" name="password" class="form-input" placeholder="Password" required>
                </div>
                
                <a href="#" class="forgot-password">Forgot password?</a>
                
                <button type="submit" class="login-btn">Sign In</button>
            </form>
            
            <div class="login-footer">
                &copy; 2025 Malaya Solar Energies Inc. All rights reserved.
            </div>
        </div>
    </div>
</body>
</html>