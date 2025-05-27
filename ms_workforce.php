    <?php
        include('validate_login.php');
        require_once 'activity_logger.php';
        
        $page_title = "WORKFORCE";

        // Database connection
        $host = 'localhost';
        $user = 'root';
        $password = '';
        $database = 'malayasol';

        $conn = new mysqli($host, $user, $password, $database);

        // Check connection
        if ($conn->connect_error) {
            die("Connection failed: " . $conn->connect_error);
        }

        // Initialize message variables
        $message = "";
        $message_type = "";

        // Define the allowed departments
        $allowed_departments = [
            "Executive & Strategy Office",
            "Finance & Digital Accounting Department",
            "IT Infrastructure & Cybersecurity Division",
            "System Development & Innovation Lab",
            "Cloud Engineering & Data Services Department",
            "Operations & Project Management Department",
            "Technical Support & IT Helpdesk",
            "HR & Digital Workforce Management",
            "Sales & Customer Engagement Hub",
            "Sustainability & Energy Analytics Division"
        ];
        
        // Define the allowed positions
        $allowed_positions = [
            "CEO",
            "CTO",
            "CFO",
            "Superadmin",
            "Admin",
            "User",
            "Manager",
            "Team Lead",
            "Senior Developer",
            "Junior Developer",
            "Accountant",
            "HR Specialist",
            "Project Manager",
            "System Administrator",
            "Network Engineer",
            "Security Analyst",
            "Database Administrator",
            "UI/UX Designer",
            "Quality Assurance",
            "Technical Support",
            "Sales Representative",
            "Office Administrator",
            "Intern"
        ];

        // ==================== EMPLOYEE DATA MANAGEMENT ====================
        
        // Handle form submissions for adding/editing employees
        if ($_SERVER["REQUEST_METHOD"] == "POST") {
            // ADD NEW EMPLOYEE
            if (isset($_POST['add_employee'])) {
                $employee_id = $_POST['employee_id'];
                $first_name = $_POST['first_name'];
                $middle_name = $_POST['middle_name'] ?? null;
                $last_name = $_POST['last_name'];
                $position = $_POST['position'];
                $department = $_POST['department'];
                $employment_status = $_POST['employment_status'];
                $contact = $_POST['contact'];
                $unit_no = $_POST['unit_no'] ?? null;
                $building = $_POST['building'] ?? null;
                $street = $_POST['street'];
                $barangay = $_POST['barangay'];
                $city = $_POST['city'];
                $country = $_POST['country'];
                
                // Start transaction
                $conn->begin_transaction();
                
                try {
                    // Check if employee already exists
                    $check_stmt = $conn->prepare("SELECT employee_id FROM employee WHERE employee_id = ?");
                    $check_stmt->bind_param("i", $employee_id);
                    $check_stmt->execute();
                    $check_result = $check_stmt->get_result();
                    
                    if ($check_result->num_rows > 0) {
                        throw new Exception("Employee ID already exists.");
                    }
                    
                    // Insert into employee table
                    $sql = "INSERT INTO employee (
                        employee_id, first_name, middle_name, last_name, position, department, employment_status, 
                        contact, unit_no, building, street, barangay, city, country
                    ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
                    logUserActivity(
                        'add', 
                        'ms_workforce.php', 
                        "add employee"
                    );
                    $stmt = $conn->prepare($sql);
                    $stmt->bind_param(
                        "issssssissssss",
                        $employee_id, $first_name, $middle_name, $last_name, $position, $department, $employment_status, 
                        $contact, $unit_no, $building, $street, $barangay, $city, $country
                    );
                    
                    if (!$stmt->execute()) {
                        throw new Exception("Error adding employee: " . $stmt->error);
                    }
                    
                    // If account creation is requested
                    if (isset($_POST['create_account']) && $_POST['create_account'] == 'yes') {
                        $username = $_POST['username'];
                        $email = $_POST['email'];
                        $password = $_POST['password'];
                        $role = $_POST['role'];
                        
                        // Hash the password
                        $hashed_password = password_hash($password, PASSWORD_DEFAULT);
                        
                        // Insert into users table
                        $user_sql = "INSERT INTO users (
                            employee_id, username, email, password, role, account_status
                        ) VALUES (?, ?, ?, ?, ?, 'new')";
                        logUserActivity(
                            'add', 
                            'ms_workforce.php', 
                            "add user"
                        );
                        
                        $user_stmt = $conn->prepare($user_sql);
                        $user_stmt->bind_param(
                            "issss",
                            $employee_id, $username, $email, $hashed_password, $role
                        );
                        
                        if (!$user_stmt->execute()) {
                            throw new Exception("Error creating user account: " . $user_stmt->error);
                        }
                        
                        $user_stmt->close();
                    }
                    
                    // Commit transaction
                    $conn->commit();
                    $message = "Employee added successfully!";
                    $message_type = "success";
                    
                } catch (Exception $e) {
                    // Roll back transaction on error
                    $conn->rollback();
                    $message = $e->getMessage();
                    $message_type = "error";
                }
            }
            
            // EDIT EMPLOYEE
            else if (isset($_POST['edit_employee'])) {
                $employee_id = $_POST['employee_id'];
                $first_name = $_POST['first_name'];
                $middle_name = $_POST['middle_name'] ?? null;
                $last_name = $_POST['last_name'];
                $position = $_POST['position'];
                $department = $_POST['department'];
                $employment_status = $_POST['employment_status'];
                $contact = $_POST['contact'];
                $unit_no = $_POST['unit_no'] ?? null;
                $building = $_POST['building'] ?? null;
                $street = $_POST['street'];
                $barangay = $_POST['barangay'];
                $city = $_POST['city'];
                $country = $_POST['country'];
                
                // Start transaction
                $conn->begin_transaction();
                
                try {
                    // Update employee record
                    $sql = "UPDATE employee SET 
                        first_name = ?, middle_name = ?, last_name = ?, position = ?, department = ?, 
                        employment_status = ?, contact = ?, unit_no = ?, building = ?, street = ?, 
                        barangay = ?, city = ?, country = ?
                        WHERE employee_id = ?";

                    logUserActivity(
                        'edit', 
                        'ms_workforce.php', 
                        "edit employee record"
                    );
                        
                    $stmt = $conn->prepare($sql);
                    $stmt->bind_param(
                        "sssssssssssssi",
                        $first_name, $middle_name, $last_name, $position, $department, 
                        $employment_status, $contact, $unit_no, $building, $street, 
                        $barangay, $city, $country, $employee_id
                    );
                    
                    if (!$stmt->execute()) {
                        throw new Exception("Error updating employee: " . $stmt->error);
                    }
                    
                    // Commit transaction
                    $conn->commit();
                    $message = "Employee updated successfully!";
                    $message_type = "success";
                    
                } catch (Exception $e) {
                    // Roll back transaction on error
                    $conn->rollback();
                    $message = $e->getMessage();
                    $message_type = "error";
                }
            }
            
            // MANAGE USER ACCOUNT
            else if (isset($_POST['manage_account'])) {
                $employee_id = $_POST['employee_id'];
                $action = $_POST['account_action'];
                $position = $_POST['position'] ?? '';
                
                // Include authentication helper
                require_once 'google_auth.php';
                $auth = new MalayaSolarAuth();
                
                // Start transaction
                $conn->begin_transaction();
                
                try {
                    if ($action === 'create') {
                        // Create new account
                        $username = $_POST['username']; // This should now be first_name.last_name
                        $email = $_POST['email']; // This should now be first_name.last_name@malayaenegies.com
                        $role = $_POST['role'];
                        
                        // Update the employee's position if it was changed
                        if (!empty($position)) {
                            $update_position_sql = "UPDATE employee SET position = ? WHERE employee_id = ?";
                            $update_position_stmt = $conn->prepare($update_position_sql);
                            $update_position_stmt->bind_param("si", $position, $employee_id);
                            $update_position_stmt->execute();
                            $update_position_stmt->close();
                        }
                        
                        // Check if user already exists
                        $check_stmt = $conn->prepare("SELECT user_id FROM users WHERE employee_id = ?");
                        $check_stmt->bind_param("i", $employee_id);
                        $check_stmt->execute();
                        $check_result = $check_stmt->get_result();
                        
                        if ($check_result->num_rows > 0) {
                            throw new Exception("User account already exists for this employee.");
                        }
                        
                        // Generate a random password
                        $password = $auth->generateRandomPassword(12);
                        $display_password = $password; // Store for display
                        
                        // Hash the password
                        $hashed_password = $auth->hashPassword($password);
                        
                        // Insert into users table with account_status = 'new'
                        $sql = "INSERT INTO users (
                            employee_id, username, email, password, role, account_status, reset_password
                        ) VALUES (?, ?, ?, ?, ?, 'new', 'no')";
                        
                        logUserActivity('add', 'ms_workforce.php', "add user");
                        
                        $stmt = $conn->prepare($sql);
                        $stmt->bind_param("issss", $employee_id, $username, $email, $hashed_password, $role);
                        
                        if (!$stmt->execute()) {
                            throw new Exception("Error creating user account: " . $stmt->error);
                        }
                        
                        $message = "User account created successfully! The initial password is: <strong>" . $display_password . "</strong>";
                    }
                    else if ($action === 'update') {
                        // Update existing account
                        $username = $_POST['username'];
                        $email = $_POST['email'];
                        $role = $_POST['role'];
                        $account_status = $_POST['account_status'];
                        
                        // Update the employee's position if it was changed
                        if (!empty($position)) {
                            $update_position_sql = "UPDATE employee SET position = ? WHERE employee_id = ?";
                            $update_position_stmt = $conn->prepare($update_position_sql);
                            $update_position_stmt->bind_param("si", $position, $employee_id);
                            $update_position_stmt->execute();
                            $update_position_stmt->close();
                        }
                        
                        // Update password only if provided
                        if (!empty($_POST['password'])) {
                            $password = $_POST['password'];
                            $hashed_password = $auth->hashPassword($password);
                            
                            $sql = "UPDATE users SET 
                                username = ?, email = ?, password = ?, role = ?, account_status = ?,
                                failed_attempts = 0
                                WHERE employee_id = ?";

                            logUserActivity(
                                'edit', 
                                'ms_workforce.php', 
                                "edit password"
                            );
                            
                            $stmt = $conn->prepare($sql);
                            $stmt->bind_param(
                                "sssssi",
                                $username, $email, $hashed_password, $role, $account_status, $employee_id
                            );
                        } else {
                            $sql = "UPDATE users SET 
                                username = ?, email = ?, role = ?, account_status = ?,
                                failed_attempts = 0
                                WHERE employee_id = ?";
                            
                            logUserActivity(
                                'edit', 
                                'ms_workforce.php', 
                                "edit user account"
                            );
                            
                            $stmt = $conn->prepare($sql);
                            $stmt->bind_param(
                                "ssssi",
                                $username, $email, $role, $account_status, $employee_id
                            );
                        }
                        
                        if (!$stmt->execute()) {
                            throw new Exception("Error updating user account: " . $stmt->error);
                        }
                        
                        // If 2FA reset is requested
                        if (isset($_POST['reset_2fa']) && $_POST['reset_2fa'] == 'yes') {
                            $reset_sql = "UPDATE users SET 
                                authenticator_secret = NULL, preferred_2fa = NULL
                                WHERE employee_id = ?";

                            logUserActivity(
                                'edit', 
                                'ms_workforce.php', 
                                "reset authentication"
                            );
                            
                            $reset_stmt = $conn->prepare($reset_sql);
                            $reset_stmt->bind_param("i", $employee_id);
                            
                            if (!$reset_stmt->execute()) {
                                throw new Exception("Error resetting 2FA: " . $reset_stmt->error);
                            }
                            
                            $reset_stmt->close();
                            $message = "User account updated successfully! 2FA has been reset.";
                        } else {
                            $message = "User account updated successfully!";
                        }
                    }
                    else if ($action === 'unlock') {
                        // Unlock account
                        $sql = "UPDATE users SET 
                            account_status = 'active', failed_attempts = 0
                            WHERE employee_id = ?";

                        logUserActivity(
                            'edit', 
                            'ms_workforce.php', 
                            "unlock account"
                        );
                        
                        $stmt = $conn->prepare($sql);
                        $stmt->bind_param("i", $employee_id);
                        
                        if (!$stmt->execute()) {
                            throw new Exception("Error unlocking account: " . $stmt->error);
                        }
                        
                        // Log the unlock action
                        $user_id_sql = "SELECT user_id FROM users WHERE employee_id = ?";
                        $user_id_stmt = $conn->prepare($user_id_sql);
                        $user_id_stmt->bind_param("i", $employee_id);
                        $user_id_stmt->execute();
                        $user_id_result = $user_id_stmt->get_result();
                        $user_row = $user_id_result->fetch_assoc();
                        $user_id = $user_row['user_id'];
                        
                        $log_sql = "INSERT INTO login_attempts (
                            user_id, attempt_time, ip_address, success, notes
                        ) VALUES (?, NOW(), ?, 0, 'Account unlocked')";
                        
                        $log_stmt = $conn->prepare($log_sql);
                        $ip = $_SERVER['REMOTE_ADDR'];
                        $log_stmt->bind_param("is", $user_id, $ip);
                        $log_stmt->execute();
                        
                        $message = "Account unlocked successfully!";
                    }
                    else if ($action === 'reset_password') {
                        // Set reset_password flag to 'yes'
                        $sql = "UPDATE users SET 
                            reset_password = 'yes', failed_attempts = 0
                            WHERE employee_id = ?";

                        logUserActivity('edit', 'ms_workforce.php', "reset password");
                        
                        $stmt = $conn->prepare($sql);
                        $stmt->bind_param("i", $employee_id);
                        
                        if (!$stmt->execute()) {
                            throw new Exception("Error resetting password: " . $stmt->error);
                        }
                        
                        $message = "Password reset initiated! User will be prompted to create a new password on next login.";
                    }
                    else if ($action === 'delete') {
                        // Delete user account
                        $sql = "DELETE FROM users WHERE employee_id = ?";
                        $stmt = $conn->prepare($sql);
                        $stmt->bind_param("i", $employee_id);
                        
                        if (!$stmt->execute()) {
                            throw new Exception("Error deleting user account: " . $stmt->error);
                        }
                        
                        $message = "User account deleted successfully!";
                    }
                    
                    // Commit transaction
                    $conn->commit();
                    $message_type = "success";
                    
                } catch (Exception $e) {
                    // Roll back transaction on error
                    $conn->rollback();
                    $message = $e->getMessage();
                    $message_type = "error";
                }
            }
        }

        // Handle employee deletion
        if (isset($_GET['delete'])) {
            $id_to_delete = $_GET['delete'];

            $conn->begin_transaction();

            try {
                // Get user_id if exists
                $check_stmt = $conn->prepare("SELECT user_id FROM users WHERE employee_id = ?");
                $check_stmt->bind_param("i", $id_to_delete);
                $check_stmt->execute();
                $check_result = $check_stmt->get_result();

                if ($check_result->num_rows > 0) {
                    $user = $check_result->fetch_assoc();
                    $user_id = $user['user_id'];

                    // 1. Delete from user_security_answers
                    $stmt = $conn->prepare("DELETE FROM user_security_answers WHERE user_id = ?");
                    $stmt->bind_param("i", $user_id);
                    $stmt->execute();

                    // 2. Delete from login_attempts
                    $stmt = $conn->prepare("DELETE FROM login_attempts WHERE user_id = ?");
                    $stmt->bind_param("i", $user_id);
                    if (!$stmt->execute()) {
                        throw new Exception("Error deleting login attempts: " . $stmt->error);
                    }

                    // 3. Delete from user_activity_log
                    $stmt = $conn->prepare("DELETE FROM user_activity_log WHERE user_id = ?");
                    $stmt->bind_param("i", $user_id);
                    if (!$stmt->execute()) {
                        throw new Exception("Error deleting activity logs: " . $stmt->error);
                    }

                    // 4. Delete from user_session_log
                    $stmt = $conn->prepare("DELETE FROM user_session_log WHERE user_id = ?");
                    $stmt->bind_param("i", $user_id);
                    if (!$stmt->execute()) {
                        throw new Exception("Error deleting session logs: " . $stmt->error);
                    }

                    // 5. Delete from users
                    $stmt = $conn->prepare("DELETE FROM users WHERE user_id = ?");
                    $stmt->bind_param("i", $user_id);
                    if (!$stmt->execute()) {
                        throw new Exception("Error deleting user account: " . $stmt->error);
                    }
                }

                // 6. Delete from employee
                $stmt = $conn->prepare("DELETE FROM employee WHERE employee_id = ?");
                $stmt->bind_param("i", $id_to_delete);
                if (!$stmt->execute()) {
                    throw new Exception("Error deleting employee: " . $stmt->error);
                }

                $conn->commit();
                $message = "Employee and all associated user data deleted successfully!";
                $message_type = "success";

            } catch (Exception $e) {
                $conn->rollback();
                $message = $e->getMessage();
                $message_type = "error";
            }
        }

        // ==================== DATA RETRIEVAL & PAGINATION ====================

        // Get search, filter and sort parameters
        $search = isset($_GET['search']) ? $_GET['search'] : '';
        $filter_department = isset($_GET['department']) ? $_GET['department'] : '';
        $filter_status = isset($_GET['status']) ? $_GET['status'] : '';
        $sort_by = isset($_GET['sort']) ? $_GET['sort'] : 'employee_id';
        $sort_order = isset($_GET['order']) ? $_GET['order'] : 'ASC';
        
        // Pagination setup
        $page = isset($_GET['page']) ? intval($_GET['page']) : 1;
        $records_per_page = 10;
        $offset = ($page - 1) * $records_per_page;
        
        // Build the base query
        $query = "SELECT e.*, u.user_id, u.username, u.account_status
                FROM employee e
                LEFT JOIN users u ON e.employee_id = u.employee_id
                WHERE 1=1";
        
        $count_query = "SELECT COUNT(*) as total FROM employee e WHERE 1=1";
        $params = [];
        $param_types = "";
        
        // Add search condition if provided
        if (!empty($search)) {
            $search_term = "%$search%";
            $query .= " AND (e.first_name LIKE ? OR e.last_name LIKE ? OR e.position LIKE ? OR e.department LIKE ?)";
            $count_query .= " AND (e.first_name LIKE ? OR e.last_name LIKE ? OR e.position LIKE ? OR e.department LIKE ?)";
            $params = array_merge($params, [$search_term, $search_term, $search_term, $search_term]);
            $param_types .= "ssss";
        }
        
        // Add filter conditions if provided
        if (!empty($filter_department)) {
            $query .= " AND e.department = ?";
            $count_query .= " AND e.department = ?";
            $params[] = $filter_department;
            $param_types .= "s";
        }
        
        if (!empty($filter_status)) {
            $query .= " AND e.employment_status = ?";
            $count_query .= " AND e.employment_status = ?";
            $params[] = $filter_status;
            $param_types .= "s";
        }
        
        // Add sorting
        $query .= " ORDER BY e.$sort_by $sort_order";
        
        // Add limit for pagination
        $query .= " LIMIT ?, ?";
        $params[] = $offset;
        $params[] = $records_per_page;
        $param_types .= "ii";
        
        // Prepare and execute the main query
        $stmt = $conn->prepare($query);
        if (!empty($params)) {
            $stmt->bind_param($param_types, ...$params);
        }
        $stmt->execute();
        $result = $stmt->get_result();
        
        // Prepare and execute the count query for pagination
        $count_stmt = $conn->prepare($count_query);
        if (!empty($params) && count($params) > 2) {
            // Remove the last two parameters (offset, limit) from the count query
            $count_params = array_slice($params, 0, -2);
            $count_param_types = substr($param_types, 0, -2);
            $count_stmt->bind_param($count_param_types, ...$count_params);
        }
        $count_stmt->execute();
        $count_result = $count_stmt->get_result();
        $count_row = $count_result->fetch_assoc();
        $total_records = $count_row['total'];
        $total_pages = ceil($total_records / $records_per_page);
    ?>

    <!DOCTYPE html>
    <html lang="en">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>Malaya Solar Energies Inc. - Workforce Management</title>
        <link rel="icon" href="images/Malaya_Logo.png" type="image/png">
        <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
        <link href="https://fonts.googleapis.com/css2?family+Hyperlegible&display=swap" rel="stylesheet">
        <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
        <link href="css/ms_workforcedesign.css" rel="stylesheet">
        <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    </head>
    <body>
        <div class="sidebar" id="sidebar">
            <?php include 'sidebar.php'; ?>
        </div>

        <div class="content-area">
            <?php include 'header.php'; ?>
            
            <div class="content-body">            
                <?php if (!empty($message)): ?>
                    <div class="alert-message alert-<?= $message_type === 'success' ? 'success' : 'error' ?>">
                        <i class="fas fa-<?= $message_type === 'success' ? 'check-circle' : 'exclamation-circle' ?> alert-icon"></i>
                        <p><?= $message ?></p>
                    </div>
                <?php endif; ?>
                
                <!-- Controls bar - Add, Search, Filters all in one row -->
                <div class="controls-container">
                    <div class="left-controls">
                        <button class="action-btn add-btn" data-bs-toggle="modal" data-bs-target="#addEmployeeModal">
                            <i class="fas fa-plus"></i> ADD
                        </button>
                        
                        <form method="GET" action="" class="search-container">
                            <i class="fas fa-search search-icon"></i>
                            <input type="text" name="search" class="search-input" placeholder="Search employees..." 
                                value="<?= htmlspecialchars($search) ?>">
                        </form>
                    </div>
                    
                    <form method="GET" action="" id="filter-form" class="d-flex align-items-center flex-wrap">
                        <!-- Preserve search term if exists -->
                        <?php if (!empty($search)): ?>
                            <input type="hidden" name="search" value="<?= htmlspecialchars($search) ?>">
                        <?php endif; ?>
                        
                        <div class="filter-group">
                            <label class="filter-label">Department:</label>
                            <select name="department" class="filter-select" onchange="this.form.submit()">
                                <option value="">All Departments</option>
                                <?php foreach ($allowed_departments as $dept): ?>
                                    <option value="<?= htmlspecialchars($dept) ?>" 
                                        <?= $filter_department === $dept ? 'selected' : '' ?>>
                                        <?= htmlspecialchars($dept) ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        
                        <div class="filter-group">
                            <label class="filter-label">Status:</label>
                            <select name="status" class="filter-select" onchange="this.form.submit()">
                                <option value="">All Status</option>
                                <option value="active" <?= $filter_status === 'active' ? 'selected' : '' ?>>Active</option>
                                <option value="on leave" <?= $filter_status === 'on leave' ? 'selected' : '' ?>>On Leave</option>
                                <option value="inactive" <?= $filter_status === 'inactive' ? 'selected' : '' ?>>Inactive</option>
                            </select>
                        </div>
                        
                        <div class="filter-group sort-group">
                            <label class="filter-label">Sort:</label>
                            <select name="sort" class="filter-select" onchange="this.form.submit()">
                                <option value="employee_id" <?= $sort_by === 'employee_id' ? 'selected' : '' ?>>ID</option>
                                <option value="last_name" <?= $sort_by === 'last_name' ? 'selected' : '' ?>>Name</option>
                                <option value="position" <?= $sort_by === 'position' ? 'selected' : '' ?>>Position</option>
                                <option value="department" <?= $sort_by === 'department' ? 'selected' : '' ?>>Department</option>
                                <option value="employment_status" <?= $sort_by === 'employment_status' ? 'selected' : '' ?>>Status</option>
                            </select>
                            
                            <select name="order" class="filter-select" onchange="this.form.submit()">
                                <option value="ASC" <?= $sort_order === 'ASC' ? 'selected' : '' ?>>Ascending</option>
                                <option value="DESC" <?= $sort_order === 'DESC' ? 'selected' : '' ?>>Descending</option>
                            </select>
                        </div>
                        
                        <?php if (!empty($filter_department) || !empty($filter_status) || $sort_by !== 'employee_id' || $sort_order !== 'ASC'): ?>
                            <div class="filter-group">
                                <a href="?<?= !empty($search) ? 'search=' . urlencode($search) : '' ?>" class="btn btn-sm btn-outline-secondary">
                                    <i class="fas fa-times"></i> Clear
                                </a>
                            </div>
                        <?php endif; ?>
                    </form>
                </div>
                
                <!-- Employee Table -->
                <div class="table-container">
                    <table>
                        <thead>
                            <tr>
                                <th width="5%">ID</th>
                                <th width="20%">Full Name</th>
                                <th width="20%">Department</th>
                                <th width="15%">Position</th>
                                <th width="10%">Status</th>
                                <th width="15%">Account</th>
                                <th width="15%">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if ($result->num_rows > 0): ?>
                                <?php while ($row = $result->fetch_assoc()): ?>
                                    <tr>
                                        <td><?= $row['employee_id'] ?></td>
                                        <td>
                                            <?= htmlspecialchars($row['first_name'] . ' ' . ($row['middle_name'] ? $row['middle_name'] . ' ' : '') . $row['last_name']) ?>
                                        </td>
                                        <td><?= htmlspecialchars($row['department']) ?></td>
                                        <td><?= htmlspecialchars($row['position']) ?></td>
                                        <td>
                                            <?php
                                            $status_class = 'status-active';
                                            if ($row['employment_status'] === 'inactive') {
                                            $status_class = 'status-inactive';
                                        } elseif ($row['employment_status'] === 'on leave') {
                                            $status_class = 'status-on-leave';
                                        }
                                        ?>
                                        <span class="status-pill <?= $status_class ?>">
                                            <?= ucfirst(htmlspecialchars($row['employment_status'])) ?>
                                        </span>
                                    </td>
                                    <td>
                                        <?php if (!empty($row['user_id'])): ?>
                                            <span class="status-pill <?= $row['account_status'] === 'active' ? 'status-active' : 'status-locked' ?>">
                                                <?= $row['username'] ?> (<?= ucfirst($row['account_status']) ?>)
                                            </span>
                                        <?php else: ?>
                                            <span class="text-muted">No account</span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <div class="action-buttons">
                                            <button class="btn btn-sm edit-btn" 
                                                    onclick="openEditModal(<?= htmlspecialchars(json_encode($row)) ?>)">
                                                <i class="fas fa-edit"></i>
                                            </button>
                                            
                                            <button class="btn btn-sm account-btn"
                                                    onclick="openAccountModal(<?= $row['employee_id'] ?>, '<?= htmlspecialchars($row['first_name']) ?>', '<?= htmlspecialchars($row['last_name']) ?>', <?= !empty($row['user_id']) ? 'true' : 'false' ?>, '<?= htmlspecialchars($row['username'] ?? '') ?>', '<?= htmlspecialchars($row['position']) ?>')">
                                                <i class="fas fa-user-shield"></i>
                                            </button>
                                            
                                            <a href="?delete=<?= $row['employee_id'] ?><?= !empty($search) ? '&search=' . urlencode($search) : '' ?><?= !empty($filter_department) ? '&department=' . urlencode($filter_department) : '' ?><?= !empty($filter_status) ? '&status=' . urlencode($filter_status) : '' ?>"
                                                class="btn btn-sm delete-btn"
                                                onclick="return confirm('Are you sure you want to delete this employee? This action cannot be undone.')">
                                                <i class="fas fa-trash"></i>
                                            </a>
                                        </div>
                                    </td>
                                </tr>
                            <?php endwhile; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="7">
                                    <div class="empty-state">
                                        <div class="empty-state-icon">
                                            <i class="fas fa-users-slash"></i>
                                        </div>
                                        <h4 class="empty-state-title">No employees found</h4>
                                        <p class="empty-state-message">
                                            <?php if (!empty($search) || !empty($filter_department) || !empty($filter_status)): ?>
                                                No employees match your search or filter criteria.
                                                <a href="ms_workforce.php">Clear all filters</a>
                                            <?php else: ?>
                                                There are no employees in the system yet.
                                                Click "Add Employee" to create one.
                                            <?php endif; ?>
                                        </p>
                                    </div>
                                </td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
            
            <!-- Pagination -->
            <?php if ($total_pages > 1): ?>
                <div class="pagination-container">
                    <nav aria-label="Page navigation">
                        <ul class="pagination">
                            <?php if ($page > 1): ?>
                                <li class="page-item">
                                    <a class="page-link" href="?page=<?= $page - 1 ?><?= !empty($search) ? '&search=' . urlencode($search) : '' ?><?= !empty($filter_department) ? '&department=' . urlencode($filter_department) : '' ?><?= !empty($filter_status) ? '&status=' . urlencode($filter_status) : '' ?><?= $sort_by !== 'employee_id' ? '&sort=' . urlencode($sort_by) : '' ?><?= $sort_order !== 'ASC' ? '&order=' . urlencode($sort_order) : '' ?>" aria-label="Previous">
                                        <span aria-hidden="true">&laquo;</span>
                                    </a>
                                </li>
                            <?php endif; ?>
                            
                            <?php
                            // Determine range of pages to show
                            $start_page = max(1, $page - 2);
                            $end_page = min($total_pages, $start_page + 4);
                            
                            if ($end_page - $start_page < 4) {
                                $start_page = max(1, $end_page - 4);
                            }
                            
                            for ($i = $start_page; $i <= $end_page; $i++):
                            ?>
                                <li class="page-item <?= $page == $i ? 'active' : '' ?>">
                                    <a class="page-link" href="?page=<?= $i ?><?= !empty($search) ? '&search=' . urlencode($search) : '' ?><?= !empty($filter_department) ? '&department=' . urlencode($filter_department) : '' ?><?= !empty($filter_status) ? '&status=' . urlencode($filter_status) : '' ?><?= $sort_by !== 'employee_id' ? '&sort=' . urlencode($sort_by) : '' ?><?= $sort_order !== 'ASC' ? '&order=' . urlencode($sort_order) : '' ?>">
                                        <?= $i ?>
                                    </a>
                                </li>
                            <?php endfor; ?>
                            
                            <?php if ($page < $total_pages): ?>
                                <li class="page-item">
                                    <a class="page-link" href="?page=<?= $page + 1 ?><?= !empty($search) ? '&search=' . urlencode($search) : '' ?><?= !empty($filter_department) ? '&department=' . urlencode($filter_department) : '' ?><?= !empty($filter_status) ? '&status=' . urlencode($filter_status) : '' ?><?= $sort_by !== 'employee_id' ? '&sort=' . urlencode($sort_by) : '' ?><?= $sort_order !== 'ASC' ? '&order=' . urlencode($sort_order) : '' ?>" aria-label="Next">
                                        <span aria-hidden="true">&raquo;</span>
                                    </a>
                                </li>
                            <?php endif; ?>
                        </ul>
                    </nav>
                </div>
            <?php endif; ?>
            
        </div>
    </div>
    
        <!-- Add Employee Modal with updated styling -->
        <div class="modal fade" id="addEmployeeModal" tabindex="-1" aria-labelledby="addEmployeeModalLabel" aria-hidden="true">
            <div class="modal-dialog modal-lg">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title" id="addEmployeeModalLabel">Add New Employee</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <form method="POST" action="">
                        <div class="modal-body">
                            <div class="form-section">
                                <div class="form-section-title">Employee Information</div>
                            <div class="row g-3">
                                <div class="col-md-4">
                                    <label for="employee_id" class="form-label">Employee ID</label>
                                    <input type="number" class="form-control" id="employee_id" name="employee_id" placeholder="Enter employee ID" required>
                                </div>
                                <div class="col-md-4">
                                    <label for="first_name" class="form-label">First Name</label>
                                    <input type="text" class="form-control" id="first_name" name="first_name" placeholder="Enter first name" required>
                                </div>
                                <div class="col-md-4">
                                    <label for="middle_name" class="form-label">Middle Name</label>
                                    <input type="text" class="form-control" id="middle_name" name="middle_name" placeholder="Enter middle name (optional)">
                                </div>
                                <div class="col-md-4">
                                    <label for="last_name" class="form-label">Last Name</label>
                                    <input type="text" class="form-control" id="last_name" name="last_name" placeholder="Enter last name" required>
                                </div>
                                <div class="col-md-4">
                                    <label for="position" class="form-label">Position</label>
                                    <select class="form-control" id="position" name="position" required>
                                        <option value="">Select Position</option>
                                        <?php foreach ($allowed_positions as $pos): ?>
                                            <option value="<?= htmlspecialchars($pos) ?>"><?= htmlspecialchars($pos) ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                                <div class="col-md-4">
                                    <label for="department" class="form-label">Department</label>
                                    <select class="form-control" id="department" name="department" required>
                                        <option value="">Select Department</option>
                                        <?php foreach ($allowed_departments as $dept): ?>
                                            <option value="<?= htmlspecialchars($dept) ?>"><?= htmlspecialchars($dept) ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                                <div class="col-md-6">
                                    <label for="contact" class="form-label">Contact Number</label>
                                    <input type="text" class="form-control" id="contact" name="contact" placeholder="Enter 11-digit contact number" maxlength="11" pattern="[0-9]{11}" required>
                                </div>
                                <div class="col-md-6">
                                    <label for="employment_status" class="form-label">Employment Status</label>
                                    <select class="form-control" id="employment_status" name="employment_status" required>
                                        <option value="active" selected>Active</option>
                                        <option value="on leave">On Leave</option>
                                        <option value="inactive">Inactive</option>
                                    </select>
                                </div>
                            </div>
                        </div>
                        
                        <div class="form-section">
                            <div class="form-section-title">Address Information</div>
                            <div class="row g-3">
                                <div class="col-md-6">
                                    <label for="unit_no" class="form-label">Unit No.</label>
                                    <input type="text" class="form-control" id="unit_no" name="unit_no" placeholder="Enter unit number (optional)">
                                </div>
                                <div class="col-md-6">
                                    <label for="building" class="form-label">Building</label>
                                    <input type="text" class="form-control" id="building" name="building" placeholder="Enter building name (optional)">
                                </div>
                                <div class="col-md-6">
                                    <label for="street" class="form-label">Street</label>
                                    <input type="text" class="form-control" id="street" name="street" placeholder="Enter street address" required>
                                </div>
                                <div class="col-md-6">
                                    <label for="barangay" class="form-label">Barangay</label>
                                    <input type="text" class="form-control" id="barangay" name="barangay" placeholder="Enter barangay" required>
                                </div>
                                <div class="col-md-6">
                                    <label for="city" class="form-label">City</label>
                                    <input type="text" class="form-control" id="city" name="city" placeholder="Enter city" required>
                                </div>
                                <div class="col-md-6">
                                    <label for="country" class="form-label">Country</label>
                                    <input type="text" class="form-control" id="country" name="country" placeholder="Enter country" required value="Philippines">
                                </div>
                            </div>
                        </div>
                        
                        <div class="form-section">
                            <div class="form-section-title">Account Information</div>
                            <div class="mb-3">
                                <div class="form-check form-switch">
                                    <input class="form-check-input" type="checkbox" id="create_account_switch" 
                                        onchange="toggleAccountFields(this.checked)">
                                    <label class="form-check-label" for="create_account_switch">
                                        Create user account for this employee
                                    </label>
                                    <input type="hidden" name="create_account" id="create_account_value" value="no">
                                </div>
                            </div>
                            
                            <div id="account_fields" style="display: none;">
                                <div class="row g-3">
                                    <div class="col-md-6">
                                        <label for="username" class="form-label">Username</label>
                                        <input type="text" class="form-control" id="username" name="username" placeholder="Auto-generated username" readonly>
                                    </div>
                                    <div class="col-md-6">
                                        <label for="email" class="form-label">Email</label>
                                        <input type="email" class="form-control" id="email" name="email" placeholder="Auto-generated email" readonly>
                                    </div>
                                    <div class="col-md-6">
                                        <label for="password" class="form-label">Password</label>
                                        <div class="input-group">
                                            <input type="text" class="form-control" id="password" name="password" placeholder="Auto-generated password" readonly>
                                            <button type="button" class="btn btn-outline-secondary" id="regenerate_password" onclick="generatePassword()">
                                                <i class="fas fa-sync"></i>
                                            </button>
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <label for="role_display" class="form-label">Account Role</label>
                                        <input type="text" class="form-control" id="role_display" placeholder="Based on position" readonly style="background-color: #f8f9fa;">
                                        <input type="hidden" id="role" name="role" value="user">
                                        <small class="text-muted">Role is automatically assigned based on position</small>
                                    </div>
                                </div>
                            </div>
                        </div>
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">CANCEL</button>
                            <button type="submit" name="add_employee" class="btn btn-primary">ADD</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    
    <!-- Edit Employee Modal -->
    <div class="modal fade" id="editEmployeeModal" tabindex="-1" aria-labelledby="editEmployeeModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="editEmployeeModalLabel">Edit Employee</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <form method="POST" action="">
                    <div class="modal-body">
                        <input type="hidden" id="edit_employee_id" name="employee_id">
                        
                        <div class="form-section">
                            <div class="form-section-title">Employee Information</div>
                            <div class="row g-3">
                                <div class="col-md-4">
                                    <label for="edit_first_name" class="form-label">First Name</label>
                                    <input type="text" class="form-control" id="edit_first_name" name="first_name" required>
                                </div>
                                <div class="col-md-4">
                                    <label for="edit_middle_name" class="form-label">Middle Name</label>
                                    <input type="text" class="form-control" id="edit_middle_name" name="middle_name">
                                </div>
                                <div class="col-md-4">
                                    <label for="edit_last_name" class="form-label">Last Name</label>
                                    <input type="text" class="form-control" id="edit_last_name" name="last_name" required>
                                </div>
                                <div class="col-md-4">
                                    <label for="edit_position" class="form-label">Position</label>
                                    <select class="form-control" id="edit_position" name="position" required>
                                        <option value="">Select Position</option>
                                        <?php foreach ($allowed_positions as $pos): ?>
                                            <option value="<?= htmlspecialchars($pos) ?>"><?= htmlspecialchars($pos) ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                                <div class="col-md-4">
                                    <label for="edit_department" class="form-label">Department</label>
                                    <select class="form-control" id="edit_department" name="department" required>
                                        <option value="">Select Department</option>
                                        <?php foreach ($allowed_departments as $dept): ?>
                                            <option value="<?= htmlspecialchars($dept) ?>"><?= htmlspecialchars($dept) ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                                <div class="col-md-4">
                                    <label for="edit_employment_status" class="form-label">Employment Status</label>
                                    <select class="form-control" id="edit_employment_status" name="employment_status" required>
                                        <option value="active">Active</option>
                                        <option value="on leave">On Leave</option>
                                        <option value="inactive">Inactive</option>
                                    </select>
                                </div>
                                <div class="col-md-12">
                                    <label for="edit_contact" class="form-label">Contact Number</label>
                                    <input type="text" class="form-control" id="edit_contact" name="contact" required>
                                </div>
                            </div>
                        </div>
                        
                        <div class="form-section">
                            <div class="form-section-title">Address Information</div>
                            <div class="row g-3">
                                <div class="col-md-6">
                                    <label for="edit_unit_no" class="form-label">Unit No.</label>
                                    <input type="text" class="form-control" id="edit_unit_no" name="unit_no">
                                </div>
                                <div class="col-md-6">
                                    <label for="edit_building" class="form-label">Building</label>
                                    <input type="text" class="form-control" id="edit_building" name="building">
                                </div>
                                <div class="col-md-6">
                                    <label for="edit_street" class="form-label">Street</label>
                                    <input type="text" class="form-control" id="edit_street" name="street" required>
                                </div>
                                <div class="col-md-6">
                                    <label for="edit_barangay" class="form-label">Barangay</label>
                                    <input type="text" class="form-control" id="edit_barangay" name="barangay" required>
                                </div>
                                <div class="col-md-6">
                                    <label for="edit_city" class="form-label">City</label>
                                    <input type="text" class="form-control" id="edit_city" name="city" required>
                                </div>
                                <div class="col-md-6">
                                    <label for="edit_country" class="form-label">Country</label>
                                    <input type="text" class="form-control" id="edit_country" name="country" required>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">CANCEL</button>
                        <button type="submit" name="edit_employee" class="btn btn-primary">Update</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    
    <!-- Account Management Modal -->
    <div class="modal fade" id="accountModal" tabindex="-1" aria-labelledby="accountModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="accountModalLabel">User Account Management</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <form method="POST" action="">
                    <div class="modal-body">
                        <input type="hidden" id="account_employee_id" name="employee_id">
                        <input type="hidden" id="account_action" name="account_action" value="create">
                        
                        <div id="account_existing_info" style="display: none;">
                            <div class="alert alert-info mb-4">
                                <i class="fas fa-info-circle me-2"></i>
                                <span id="account_status_message"></span>
                            </div>
                        </div>
                        
                        <div id="account_create_fields">
                            <div class="form-section">
                                <div class="form-section-title">Employee Position</div>
                                <div class="row g-3 mb-3">
                                    <div class="col-md-12">
                                        <label for="account_position" class="form-label">Position</label>
                                        <select class="form-control" id="account_position" name="position" required>
                                            <option value="">Select Position</option>
                                            <?php foreach ($allowed_positions as $pos): ?>
                                                <option value="<?= htmlspecialchars($pos) ?>"><?= htmlspecialchars($pos) ?></option>
                                            <?php endforeach; ?>
                                        </select>
                                        <small class="text-muted">Account role will be assigned based on selected position</small>
                                    </div>
                                </div>
                            </div>
                            
                            <div class="form-section">
                                <div class="form-section-title">Account Information</div>
                                <div class="row g-3">
                                    <div class="col-md-6">
                                        <label for="account_username" class="form-label">Username</label>
                                        <input type="text" class="form-control" id="account_username" name="username" required readonly>
                                    </div>
                                    <div class="col-md-6">
                                        <label for="account_email" class="form-label">Email</label>
                                        <input type="email" class="form-control" id="account_email" name="email" required readonly>
                                    </div>
                                    <div class="col-md-6">
                                        <label for="account_password" class="form-label">
                                            <span id="password_label">Password</span>
                                            <small id="password_note" class="text-muted d-none">
                                                (Leave blank to keep current password)
                                            </small>
                                        </label>
                                        <input type="password" class="form-control" id="account_password" name="password" placeholder="************" readonly>
                                    </div>
                                    <div class="col-md-6">
                                        <label for="account_role_display" class="form-label">Account Role</label>
                                        <input type="text" class="form-control" id="account_role_display" placeholder="Based on position" readonly style="background-color: #f8f9fa;">
                                        <input type="hidden" id="account_role" name="role" required>
                                    </div>
                                    <div class="col-md-6 d-none" id="account_status_field">
                                        <label for="account_status" class="form-label">Account Status</label>
                                        <select class="form-control" id="account_status" name="account_status">
                                            <option value="new">New</option>
                                            <option value="active">Active</option>
                                            <option value="locked">Locked</option>
                                            <option value="disabled">Disabled</option>
                                        </select>
                                    </div>
                                    <div class="col-md-6 d-none" id="auth_status_field">
                                        <label class="form-label">2FA Status</label>
                                        <div class="form-control" style="background-color: #f8f9fa;">
                                            <span id="auth_status_text">Not configured</span>
                                        </div>
                                    </div>
                                </div>
                                
                                <div class="mt-3 d-none" id="reset_2fa_field">
                                    <div class="form-check">
                                        <input class="form-check-input" type="checkbox" id="reset_2fa" name="reset_2fa" value="yes">
                                        <label class="form-check-label" for="reset_2fa">
                                            Reset Two-Factor Authentication
                                        </label>
                                    </div>
                                    <small class="text-muted">
                                        This will remove the user's 2FA settings, requiring them to set it up again on next login.
                                    </small>
                                </div>
                            </div>
                        </div>
                        
                        <div id="account_actions" class="mt-4 d-none">
                            <div class="d-flex justify-content-between flex-wrap gap-2">
                                <button type="button" id="unlock_account_btn" class="btn btn-success btn-sm" onclick="setAccountAction('unlock')">
                                    <i class="fas fa-unlock"></i> Unlock Account
                                </button>
                                
                                <button type="button" id="reset_password_btn" class="btn btn-warning btn-sm" onclick="confirmResetPassword()">
                                    <i class="fas fa-key"></i> Reset Password
                                </button>
                                
                                <button type="button" id="delete_account_btn" class="btn btn-danger btn-sm" onclick="confirmDeleteAccount()">
                                    <i class="fas fa-user-times"></i> Delete Account
                                </button>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">CANCEL</button>
                        <button type="submit" name="manage_account" id="account_submit_btn" class="btn btn-primary">
                            Create Account
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    
    <script src="js/sidebar.js"></script>
    <script src="js/header.js"></script>
    
    <script>
        // Enhanced role mapping function
        function getRoleFromPosition(position) {
            if (!position) return 'user';
            
            const position_lower = position.toLowerCase();
            
            // Superadmin roles
            if (position_lower.includes('ceo') || 
                position_lower.includes('superadmin') || 
                position_lower === 'ceo') {
                return 'superadmin';
            }
            
            // Admin roles
            if (position_lower.includes('cto') || 
                position_lower.includes('cfo') || 
                position_lower.includes('admin') || 
                position_lower === 'admin') {
                return 'admin';
            }
            
            // Manager roles
            if (position_lower.includes('manager') || 
                position_lower.includes('lead') || 
                position_lower === 'manager' || 
                position_lower === 'team lead') {
                return 'manager';
            }
            
            // Default to user for all other positions
            return 'user';
        }

        // Function to update role display in add modal
        function updateRoleDisplay() {
            const positionSelect = document.getElementById('position');
            const roleDisplay = document.getElementById('role_display');
            const roleHidden = document.getElementById('role');
            
            if (positionSelect && roleDisplay && roleHidden) {
                const selectedPosition = positionSelect.value;
                const assignedRole = getRoleFromPosition(selectedPosition);
                
                // Update display field
                if (selectedPosition) {
                    roleDisplay.value = assignedRole.charAt(0).toUpperCase() + assignedRole.slice(1);
                } else {
                    roleDisplay.value = '';
                }
                
                // Update hidden field
                roleHidden.value = assignedRole;
            }
        }

        // Function to update role display in account modal
        function updateAccountRoleDisplay() {
            const positionSelect = document.getElementById('account_position');
            const roleDisplay = document.getElementById('account_role_display');
            const roleHidden = document.getElementById('account_role');
            
            if (positionSelect && roleDisplay && roleHidden) {
                const selectedPosition = positionSelect.value;
                const assignedRole = getRoleFromPosition(selectedPosition);
                
                // Update display field
                if (selectedPosition) {
                    roleDisplay.value = assignedRole.charAt(0).toUpperCase() + assignedRole.slice(1);
                } else {
                    roleDisplay.value = '';
                }
                
                // Update hidden field
                roleHidden.value = assignedRole;
            }
        }

            // Toggle account fields in add employee modal
                function toggleAccountFields(checked) {
                    const accountFields = document.getElementById('account_fields');
                    const accountValue = document.getElementById('create_account_value');
                    
                    if (checked) {
                        accountFields.style.display = 'block';
                        accountValue.value = 'yes';
                        
                        // Auto-generate username and email
                        updateAccountInfo();
                        
                        // Auto-generate password
                        generatePassword();
                        
                        // Update role based on current position
                        updateRoleDisplay();
                    } else {
                        accountFields.style.display = 'none';
                        accountValue.value = 'no';
                        
                        // Clear fields
                        document.getElementById('username').value = '';
                        document.getElementById('email').value = '';
                        document.getElementById('password').value = '';
                        document.getElementById('role_display').value = '';
                        document.getElementById('role').value = 'user';
                    }
                }
                
                // Open the edit employee modal with data
                function openEditModal(employee) {
                    console.log('Opening edit modal with data:', employee);
                    
            try {
                // Set values in the form
                document.getElementById('edit_employee_id').value = employee.employee_id;
                document.getElementById('edit_first_name').value = employee.first_name || '';
                document.getElementById('edit_middle_name').value = employee.middle_name || '';
                document.getElementById('edit_last_name').value = employee.last_name || '';
                document.getElementById('edit_position').value = employee.position || '';
                document.getElementById('edit_department').value = employee.department || '';
                document.getElementById('edit_employment_status').value = employee.employment_status || '';
                document.getElementById('edit_contact').value = employee.contact || '';
                document.getElementById('edit_unit_no').value = employee.unit_no || '';
                document.getElementById('edit_building').value = employee.building || '';
                document.getElementById('edit_street').value = employee.street || '';
                document.getElementById('edit_barangay').value = employee.barangay || '';
                document.getElementById('edit_city').value = employee.city || '';
                document.getElementById('edit_country').value = employee.country || '';
                
                // Open the modal
                const modal = new bootstrap.Modal(document.getElementById('editEmployeeModal'));
                modal.show();
            } catch (error) {
                console.error('Error opening edit modal:', error);
                alert('Error opening edit modal. Please try again.');
            }
        }
        
        // Enhanced account modal function
        function openAccountModal(employeeId, firstName, lastName, hasAccount, username, currentPosition) {
            console.log('Opening account modal:', {employeeId, firstName, lastName, hasAccount, username, currentPosition});
            
            try {
                // Set employee ID
                document.getElementById('account_employee_id').value = employeeId;
                
                // Update modal title
                const modalTitle = document.getElementById('accountModalLabel');
                modalTitle.textContent = `Account Management - ${firstName} ${lastName}`;
                
                // Generate proper username and email format
                const generatedUsername = `${firstName.toLowerCase()}.${lastName.toLowerCase()}`;
                const generatedEmail = `${generatedUsername}@malayaenegies.com`;
                
                // Handle existing vs new account state
                const existingInfoDiv = document.getElementById('account_existing_info');
                const createFields = document.getElementById('account_create_fields');
                const accountActions = document.getElementById('account_actions');
                const statusMessage = document.getElementById('account_status_message');
                const passwordLabel = document.getElementById('password_label');
                const passwordNote = document.getElementById('password_note');
                const accountStatusField = document.getElementById('account_status_field');
                const authStatusField = document.getElementById('auth_status_field');
                const resetTwoFAField = document.getElementById('reset_2fa_field');
                const submitBtn = document.getElementById('account_submit_btn');
                const positionSelect = document.getElementById('account_position');
                
                // Reset form
                document.getElementById('account_action').value = hasAccount ? 'update' : 'create';
                
                // Always set the username and email fields to the generated format, even for existing accounts
                // This ensures consistency with our naming convention
                document.getElementById('account_username').value = generatedUsername;
                document.getElementById('account_email').value = generatedEmail;
                
                // Set current employee position if available
                if (currentPosition && positionSelect) {
                    positionSelect.value = currentPosition;
                    updateAccountRoleDisplay(); // Update role based on current position
                }
                
                if (hasAccount) {
                    // Existing account logic
                    existingInfoDiv.style.display = 'block';
                    statusMessage.textContent = `User account exists with username: ${username}`;
                    
                    // Show additional fields for existing accounts
                    accountStatusField.classList.remove('d-none');
                    authStatusField.classList.remove('d-none');
                    resetTwoFAField.classList.remove('d-none');
                    accountActions.classList.remove('d-none');
                    
                    // Update password field for existing accounts
                    passwordLabel.textContent = 'Password';
                    passwordNote.classList.remove('d-none');
                    document.getElementById('account_password').placeholder = '************';
                    
                    // Update submit button
                    submitBtn.textContent = 'Update Account';
                    
                    // Fetch account details via AJAX
                    fetchAccountDetails(employeeId);
                } else {
                    // New account logic
                    existingInfoDiv.style.display = 'none';
                    
                    // Hide additional fields for new accounts
                    accountStatusField.classList.add('d-none');
                    authStatusField.classList.add('d-none');
                    resetTwoFAField.classList.add('d-none');
                    accountActions.classList.add('d-none');
                    
                    // Update password field for new accounts
                    passwordLabel.textContent = 'Password (Auto-generated)';
                    passwordNote.classList.add('d-none');
                    document.getElementById('account_password').placeholder = 'Auto-generated password';
                    
                    // Update submit button
                    submitBtn.textContent = 'Create Account';
                }
                
                // Open the modal
                const modal = new bootstrap.Modal(document.getElementById('accountModal'));
                modal.show();
            } catch (error) {
                console.error('Error opening account modal:', error);
                alert('Error opening account modal. Please try again.');
            }
        }

        // Generate random alphanumeric password
        function generateRandomPassword(length = 8) {
            const chars = 'ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz0123456789';
            let result = '';
            for (let i = 0; i < length; i++) {
                result += chars.charAt(Math.floor(Math.random() * chars.length));
            }
            return result;
        }

        // Generate password function for the button
        function generatePassword() {
            const passwordField = document.getElementById('password');
            if (passwordField) {
                passwordField.value = generateRandomPassword(8);
            }
        }

        // Update username and email when name fields change
        function updateAccountInfo() {
            const firstName = document.getElementById('first_name').value.toLowerCase().trim();
            const lastName = document.getElementById('last_name').value.toLowerCase().trim();
            
            if (firstName && lastName) {
                const username = `${firstName}.${lastName}`;
                const email = `${username}@malayaenegies.com`;
                
                document.getElementById('username').value = username;
                document.getElementById('email').value = email;
            }
        }

        // Fetch account details via AJAX
        function fetchAccountDetails(employeeId) {
            // Make an AJAX call to fetch the real account details
            fetch('get_account_details.php?employee_id=' + employeeId)
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        // Fill in the form fields but preserve our formatting for username and email
                        // We don't overwrite username and email fields here to maintain our naming convention
                        
                        document.getElementById('account_role').value = data.role;
                        document.getElementById('account_role_display').value = data.role.charAt(0).toUpperCase() + data.role.slice(1);
                        document.getElementById('account_status').value = data.account_status;
                        
                        // Update the 2FA status text
                        const authStatusText = document.getElementById('auth_status_text');
                        if (data.authenticator_secret) {
                            authStatusText.textContent = 'Configured';
                            authStatusText.className = 'text-success';
                            document.getElementById('reset_2fa').disabled = false;
                        } else {
                            authStatusText.textContent = 'Not configured';
                            authStatusText.className = 'text-muted';
                            document.getElementById('reset_2fa').disabled = true;
                        }
                        
                        // Show/hide the unlock button based on account status
                        const unlockBtn = document.getElementById('unlock_account_btn');
                        if (data.account_status === 'locked') {
                            unlockBtn.style.display = 'block';
                        } else {
                            unlockBtn.style.display = 'none';
                        }
                    } else {
                        console.warn('Failed to fetch account details:', data.message);
                    }
                })
                .catch(error => {
                    console.error('Error fetching account details:', error);
                    
                    // Fallback values - but we keep the previously set username and email
                    document.getElementById('account_role').value = 'user';
                    document.getElementById('account_role_display').value = 'User';
                    document.getElementById('account_status').value = 'active';
                    document.getElementById('reset_2fa').checked = false;
                    
                    const authStatusText = document.getElementById('auth_status_text');
                    authStatusText.textContent = 'Unknown';
                    authStatusText.className = 'text-muted';
                    
                    document.getElementById('unlock_account_btn').style.display = 'block';
                });
        }
        
        // Confirm password reset
        function confirmResetPassword() {
            if (confirm('Are you sure you want to reset this user\'s password? The user will be prompted to create a new password on next login.')) {
                document.getElementById('account_action').value = 'reset_password';
                document.querySelector('#accountModal form').submit();
            }
        }

        // Set account action (unlock, delete)
        function setAccountAction(action) {
            document.getElementById('account_action').value = action;
            
            // Submit the form
            if (action === 'unlock') {
                if (confirm('Are you sure you want to unlock this account?')) {
                    document.querySelector('#accountModal form').submit();
                }
            }
        }
        
        // Confirm account deletion
        function confirmDeleteAccount() {
            if (confirm('Are you sure you want to delete this user account? This action cannot be undone.')) {
                document.getElementById('account_action').value = 'delete';
                document.querySelector('#accountModal form').submit();
            }
        }
        
        // Document ready functions
        document.addEventListener('DOMContentLoaded', function() {
            console.log('DOM Content Loaded');
            
            // Add event listener for position changes in add modal
            const positionSelect = document.getElementById('position');
            if (positionSelect) {
                positionSelect.addEventListener('change', function() {
                    updateRoleDisplay();
                    
                    // Also update account info if account creation is enabled
                    if (document.getElementById('create_account_switch') && document.getElementById('create_account_switch').checked) {
                        updateAccountInfo();
                    }
                });
            }
            
            // Add event listener for account modal position select
            const accountPositionSelect = document.getElementById('account_position');
            if (accountPositionSelect) {
                accountPositionSelect.addEventListener('change', function() {
                    updateAccountRoleDisplay();
                });
            }
            
            // Handle form submission for search
            const searchInput = document.querySelector('.search-input');
            if (searchInput) {
                searchInput.addEventListener('keypress', function(e) {
                    if (e.key === 'Enter') {
                        e.preventDefault();
                        this.form.submit();
                    }
                });
            }
            
            // Add event listeners for name fields in add modal
            const firstNameField = document.getElementById('first_name');
            const lastNameField = document.getElementById('last_name');
            
            if (firstNameField && lastNameField) {
                firstNameField.addEventListener('input', function() {
                    if (document.getElementById('create_account_switch') && document.getElementById('create_account_switch').checked) {
                        updateAccountInfo();
                    }
                });
                
                lastNameField.addEventListener('input', function() {
                    if (document.getElementById('create_account_switch') && document.getElementById('create_account_switch').checked) {
                        updateAccountInfo();
                    }
                });
            }
            
            // Contact number validation - only allow digits and limit to 11
            const contactField = document.getElementById('contact');
            const editContactField = document.getElementById('edit_contact');
            
            function setupContactValidation(field) {
                if (field) {
                    field.addEventListener('input', function(e) {
                        // Remove any non-digit characters
                        this.value = this.value.replace(/[^0-9]/g, '');
                        
                        // Limit to 11 digits
                        if (this.value.length > 11) {
                            this.value = this.value.slice(0, 11);
                        }
                    });
                    
                    field.addEventListener('keypress', function(e) {
                        // Only allow digits
                        if (!/[0-9]/.test(e.key) && !['Backspace', 'Delete', 'Tab', 'Enter'].includes(e.key)) {
                            e.preventDefault();
                        }
                    });
                }
            }
            
            setupContactValidation(contactField);
            setupContactValidation(editContactField);
            
            // Initialize tooltips
            const tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
            if (typeof bootstrap !== 'undefined' && bootstrap.Tooltip) {
                tooltipTriggerList.map(function (tooltipTriggerEl) {
                    return new bootstrap.Tooltip(tooltipTriggerEl);
                });
            }
            
            // Debug: Log when modals are opened
            const editModal = document.getElementById('editEmployeeModal');
            const accountModal = document.getElementById('accountModal');
            const addModal = document.getElementById('addEmployeeModal');
            
            if (editModal) {
                editModal.addEventListener('show.bs.modal', function () {
                    console.log('Edit modal is opening');
                });
                editModal.addEventListener('shown.bs.modal', function () {
                    console.log('Edit modal is fully shown');
                });
            }
            
            if (accountModal) {
                accountModal.addEventListener('show.bs.modal', function () {
                    console.log('Account modal is opening');
                });
                accountModal.addEventListener('shown.bs.modal', function () {
                    console.log('Account modal is fully shown');
                });
            }
            
            if (addModal) {
                addModal.addEventListener('show.bs.modal', function () {
                    console.log('Add modal is opening');
                });
                addModal.addEventListener('shown.bs.modal', function () {
                    console.log('Add modal is fully shown');
                });
            }
        });
    </script>
    </body>
    </html>