<?php
/**
 * Access Control Functions
 * Handles role and department-based permissions
 */

// Page access permissions based on departments and roles
function getPagePermissions() {
    return [
        'ms_dashboard.php' => [
            'Executive & Strategy Office' => ['CEO'],
            'IT Infrastructure & Cybersecurity Division' => ['superadmin', 'admin'],
            'Operations & Project Management Department' => ['manager', 'office administrator'],
            'Finance & Digital Accounting Department' => ['cfo']
        ],
        'ms_projects.php' => [
            'Executive & Strategy Office' => ['CEO'],
            'IT Infrastructure & Cybersecurity Division' => ['superadmin', 'admin'],
            'Operations & Project Management Department' => ['manager', 'office administrator', 'user']
        ],
        'ms_assets.php' => [
            'Executive & Strategy Office' => ['CEO'],
            'IT Infrastructure & Cybersecurity Division' => ['superadmin', 'admin'],
            'Operations & Project Management Department' => ['manager', 'user']
        ],
        'ms_records.php' => [ // Expenses page
            'Executive & Strategy Office' => ['CEO'],
            'IT Infrastructure & Cybersecurity Division' => ['superadmin', 'admin'],
            'Operations & Project Management Department' => ['manager', 'user'],
            'Finance & Digital Accounting Department' => ['cfo', 'accountant']
        ],
        'ms_workforce.php' => [
            'Executive & Strategy Office' => ['CEO'],
            'IT Infrastructure & Cybersecurity Division' => ['superadmin', 'admin'],
            'Operations & Project Management Department' => ['manager', 'office administrator']
        ],
        'ms_payroll.php' => [
            'Executive & Strategy Office' => ['CEO'],
            'IT Infrastructure & Cybersecurity Division' => ['superadmin', 'admin'],
            'Finance & Digital Accounting Department' => ['cfo', 'accountant']
        ],
        'ms_vendors.php' => [
            'Executive & Strategy Office' => ['CEO'],
            'IT Infrastructure & Cybersecurity Division' => ['superadmin', 'admin'],
            'Operations & Project Management Department' => ['manager', 'user']
        ],
        'ms_reports.php' => [
            'Executive & Strategy Office' => ['CEO'],
            'IT Infrastructure & Cybersecurity Division' => ['superadmin', 'admin'],
            'Operations & Project Management Department' => ['manager', 'office administrator'],
            'Finance & Digital Accounting Department' => ['cfo', 'accountant']
        ]
    ];
}

/**
 * Get user's department and role information
 */
function getUserAccessInfo($user_id) {
    $conn = new mysqli("localhost", "root", "", "malayasol");
    
    if ($conn->connect_error) {
        return false;
    }
    
    $stmt = $conn->prepare("
        SELECT u.role, e.department, e.position 
        FROM users u 
        LEFT JOIN employee e ON u.employee_id = e.employee_id 
        WHERE u.user_id = ?
    ");
    
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows === 1) {
        $user_info = $result->fetch_assoc();
        $stmt->close();
        $conn->close();
        return $user_info;
    }
    
    $stmt->close();
    $conn->close();
    return false;
}

/**
 * Check if user can access a specific page
 */
function canAccessPage($page_name, $user_id = null) {
    // Use session user_id if not provided
    if ($user_id === null && isset($_SESSION['user_id'])) {
        $user_id = $_SESSION['user_id'];
    }
    
    if (!$user_id) {
        return false;
    }
    
    // Get user access info
    $user_info = getUserAccessInfo($user_id);
    if (!$user_info) {
        return false;
    }
    
    $user_role = strtolower($user_info['role']);
    $user_department = $user_info['department'];
    
    // Get page permissions
    $permissions = getPagePermissions();
    
    // Check if page exists in permissions
    if (!isset($permissions[$page_name])) {
        return false;
    }
    
    $page_permissions = $permissions[$page_name];
    
    // Check if user's department has access to this page
    if (!isset($page_permissions[$user_department])) {
        return false;
    }
    
    // Check if user's role is allowed in their department for this page
    $allowed_roles = array_map('strtolower', $page_permissions[$user_department]);
    
    return in_array($user_role, $allowed_roles);
}

/**
 * Check access and redirect if unauthorized
 */
function validatePageAccess($page_name = null) {
    // Include login validation first
    if (!isset($_SESSION['user_id'])) {
        header("Location: ms_index.php");
        exit();
    }
    
    // If no specific page provided, use current page
    if ($page_name === null) {
        $page_name = basename($_SERVER['PHP_SELF']);
    }
    
    // Check if user can access this page
    if (!canAccessPage($page_name)) {
        // Log unauthorized access attempt
        if (function_exists('logUserActivity')) {
            logUserActivity('access_denied', $page_name, 'Unauthorized access attempt');
        }
        
        // Redirect to access denied page or dashboard
        header("Location: access_denied.php");
        exit();
    }
}

/**
 * Get list of pages user can access (for sidebar)
 */
function getUserAccessiblePages($user_id = null) {
    if ($user_id === null && isset($_SESSION['user_id'])) {
        $user_id = $_SESSION['user_id'];
    }
    
    if (!$user_id) {
        return [];
    }
    
    $accessible_pages = [];
    $permissions = getPagePermissions();
    
    foreach ($permissions as $page => $page_perms) {
        if (canAccessPage($page, $user_id)) {
            $accessible_pages[] = $page;
        }
    }
    
    return $accessible_pages;
}

/**
 * Check if user has specific role
 */
function hasRole($required_role, $user_id = null) {
    if ($user_id === null && isset($_SESSION['user_id'])) {
        $user_id = $_SESSION['user_id'];
    }
    
    if (!$user_id) {
        return false;
    }
    
    $user_info = getUserAccessInfo($user_id);
    if (!$user_info) {
        return false;
    }
    
    return strtolower($user_info['role']) === strtolower($required_role);
}

/**
 * Check if user is in specific department
 */
function inDepartment($required_department, $user_id = null) {
    if ($user_id === null && isset($_SESSION['user_id'])) {
        $user_id = $_SESSION['user_id'];
    }
    
    if (!$user_id) {
        return false;
    }
    
    $user_info = getUserAccessInfo($user_id);
    if (!$user_info) {
        return false;
    }
    
    return $user_info['department'] === $required_department;
}

/**
 * Get user's current role
 */
function getCurrentUserRole() {
    if (!isset($_SESSION['user_id'])) {
        return null;
    }
    
    $user_info = getUserAccessInfo($_SESSION['user_id']);
    return $user_info ? $user_info['role'] : null;
}

/**
 * Get user's current department
 */
function getCurrentUserDepartment() {
    if (!isset($_SESSION['user_id'])) {
        return null;
    }
    
    $user_info = getUserAccessInfo($_SESSION['user_id']);
    return $user_info ? $user_info['department'] : null;
}

/**
 * Initialize access control (call this at the start of protected pages)
 */
function initAccessControl() {
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }
    
    // Validate user is logged in and has access to current page
    validatePageAccess();
}
?>