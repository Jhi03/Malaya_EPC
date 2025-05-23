<?php
/**
 * Activity Logger - Enhanced tracking system for user actions
 * This function logs user activities across all pages
 */

function logUserActivity($action, $page, $details = null, $record_id = null) {
    // Ensure session is started
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }
    
    // Check if user is logged in
    if (!isset($_SESSION['user_id'])) {
        return false;
    }
    
    try {
        $conn = new mysqli("localhost", "root", "", "malayasol");
        
        if ($conn->connect_error) {
            error_log("Activity Logger DB Error: " . $conn->connect_error);
            return false;
        }
        
        $user_id = $_SESSION['user_id'];
        $ip_address = $_SERVER['REMOTE_ADDR'] ?? 'Unknown';
        $user_agent = $_SERVER['HTTP_USER_AGENT'] ?? 'Unknown';
        
        // Prepare the activity log entry
        $stmt = $conn->prepare("
            INSERT INTO user_activity_log 
            (user_id, action, page, details, record_id, ip_address, user_agent, timestamp) 
            VALUES (?, ?, ?, ?, ?, ?, ?, NOW())
        ");
        
        $stmt->bind_param("isssiss", 
            $user_id, 
            $action, 
            $page, 
            $details, 
            $record_id, 
            $ip_address, 
            $user_agent
        );
        
        $success = $stmt->execute();
        
        if (!$success) {
            error_log("Activity Logger SQL Error: " . $stmt->error);
        }
        
        $stmt->close();
        $conn->close();
        
        return $success;
        
    } catch (Exception $e) {
        error_log("Activity Logger Exception: " . $e->getMessage());
        return false;
    }
}

/**
 * Enhanced session tracking with automatic logout detection
 */
function trackUserSession($action = 'login') {
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }
    
    if (!isset($_SESSION['user_id'])) {
        return false;
    }
    
    try {
        $conn = new mysqli("localhost", "root", "", "malayasol");
        
        if ($conn->connect_error) {
            return false;
        }
        
        $user_id = $_SESSION['user_id'];
        $ip_address = $_SERVER['REMOTE_ADDR'] ?? 'Unknown';
        $user_agent = $_SERVER['HTTP_USER_AGENT'] ?? 'Unknown';
        
        if ($action === 'login') {
            // Insert new session
            $stmt = $conn->prepare("
                INSERT INTO user_session_log 
                (user_id, login_time, ip_address, user_agent, status) 
                VALUES (?, NOW(), ?, ?, 'active')
            ");
            $stmt->bind_param("iss", $user_id, $ip_address, $user_agent);
            
        } elseif ($action === 'logout') {
            // Update existing session with logout time
            $stmt = $conn->prepare("
                UPDATE user_session_log 
                SET logout_time = NOW(), status = 'closed' 
                WHERE user_id = ? AND status = 'active' AND logout_time IS NULL
                ORDER BY login_time DESC 
                LIMIT 1
            ");
            $stmt->bind_param("i", $user_id);
        }
        
        $success = $stmt->execute();
        $stmt->close();
        $conn->close();
        
        return $success;
        
    } catch (Exception $e) {
        error_log("Session Tracker Exception: " . $e->getMessage());
        return false;
    }
}

/**
 * Get user's recent activities for display
 */
function getUserRecentActivities($user_id = null, $limit = 50) {
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }
    
    $target_user_id = $user_id ?? $_SESSION['user_id'] ?? null;
    
    if (!$target_user_id) {
        return [];
    }
    
    try {
        $conn = new mysqli("localhost", "root", "", "malayasol");
        
        if ($conn->connect_error) {
            return [];
        }
        
        $stmt = $conn->prepare("
            SELECT 
                ual.action,
                ual.page,
                ual.details,
                ual.timestamp,
                ual.ip_address,
                CONCAT(e.first_name, ' ', e.last_name) as user_name
            FROM user_activity_log ual
            LEFT JOIN users u ON ual.user_id = u.user_id
            LEFT JOIN employee e ON u.employee_id = e.employee_id
            WHERE ual.user_id = ?
            ORDER BY ual.timestamp DESC
            LIMIT ?
        ");
        
        $stmt->bind_param("ii", $target_user_id, $limit);
        $stmt->execute();
        $result = $stmt->get_result();
        
        $activities = [];
        while ($row = $result->fetch_assoc()) {
            $activities[] = $row;
        }
        
        $stmt->close();
        $conn->close();
        
        return $activities;
        
    } catch (Exception $e) {
        error_log("Get Activities Exception: " . $e->getMessage());
        return [];
    }
}
?>