<?php
    // Start session if not already started
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }
    
    // Log the logout if possible (don't require the auth library)
    if (isset($_SESSION['user_id'])) {
        try {
            $conn = new mysqli("localhost", "root", "", "malayasol");
            if (!$conn->connect_error) {
                $user_id = $_SESSION['user_id'];
                $ip = $_SERVER['REMOTE_ADDR'];
                $log_sql = "INSERT INTO login_attempts (user_id, attempt_time, ip_address, success, notes) 
                           VALUES (?, NOW(), ?, 1, 'User logged out')";
                
                $log_stmt = $conn->prepare($log_sql);
                $log_stmt->bind_param("is", $user_id, $ip);
                $log_stmt->execute();
                $log_stmt->close();
                $conn->close();
            }
        } catch (Exception $e) {
            // Just continue with logout if logging fails
        }
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