<?php
session_start();
require_once 'google_auth.php';
require_once 'activity_logger.php'; 

// Initialize auth class
$auth = new MalayaSolarAuth();

// Check if the library is available
$auth_lib_available = $auth->isLibraryAvailable();

// Initialize state variables
$error = '';
$show_2fa_form = false;
$show_2fa_setup = false;
$show_security_question_1 = false;
$show_security_question_2 = false;
$show_security_recovery = false;
$show_password_update = false;
$qr_code_url = '';
$auth_secret = '';
$temp_user_id = '';
$temp_username = '';

// Handle form submissions
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // If 2FA library is not available, show a message to administrators
    if (!$auth_lib_available && (isset($_POST['auth_code']) || isset($_POST['setup_auth_code']))) {
        $error = "Two-factor authentication library is not available. Please contact the system administrator.";
    }
    // Handle password update submission
    elseif (isset($_POST['update_password'])) {
        $user_id = $_SESSION['temp_user_id'];
        $new_password = $_POST['new_password'];
        $confirm_password = $_POST['confirm_password'];
        
        // Validate password requirements
        if ($new_password !== $confirm_password) {
            $error = "Passwords do not match.";
            $show_password_update = true;
        } elseif (strlen($new_password) < 12) {
            $error = "Password must be at least 12 characters long.";
            $show_password_update = true;
        } elseif (!preg_match('/[A-Z]/', $new_password)) {
            $error = "Password must contain at least one uppercase letter.";
            $show_password_update = true;
        } elseif (!preg_match('/[a-z]/', $new_password)) {
            $error = "Password must contain at least one lowercase letter.";
            $show_password_update = true;
        } elseif (!preg_match('/[0-9]/', $new_password)) {
            $error = "Password must contain at least one digit.";
            $show_password_update = true;
        } elseif (!preg_match('/[^A-Za-z0-9]/', $new_password)) {
            $error = "Password must contain at least one special character.";
            $show_password_update = true;
        } else {
            // Password is valid, update it
            $conn = new mysqli("localhost", "root", "", "malayasol");
            
            $hashed_password = $auth->hashPassword($new_password);
            
            $update_sql = "UPDATE users SET 
                password = ?, 
                reset_password = 'no', 
                account_status = 'active',
                failed_attempts = 0
                WHERE user_id = ?";
            
            $stmt = $conn->prepare($update_sql);
            $stmt->bind_param("si", $hashed_password, $user_id);
            
            if ($stmt->execute()) {
                // Get user info for session
                $user_stmt = $conn->prepare("SELECT username, role, employee_id FROM users WHERE user_id = ?");
                $user_stmt->bind_param("i", $user_id);
                $user_stmt->execute();
                $user_result = $user_stmt->get_result();
                $user_data = $user_result->fetch_assoc();
                $user_stmt->close();
                
                // Set session variables
                $_SESSION['user_id'] = $user_id;
                $_SESSION['username'] = $user_data['username'];
                $_SESSION['role'] = $user_data['role'];
                $_SESSION['employee_id'] = $user_data['employee_id'];
                
                // Clear temp session data
                unset($_SESSION['temp_user_id']);
                
                // Log the password update
                $ip = $_SERVER['REMOTE_ADDR'];
                $log_sql = "INSERT INTO login_attempts (user_id, attempt_time, ip_address, success, notes) 
                           VALUES (?, NOW(), ?, 1, 'Password updated successfully')";
                
                $log_stmt = $conn->prepare($log_sql);
                $log_stmt->bind_param("is", $user_id, $ip);
                $log_stmt->execute();
                $log_stmt->close();
                
                logUserActivity('login', 'ms_index.php', 'Password updated on first login');
                trackUserSession('login');
                
                // Redirect to dashboard
                header("Location: ms_dashboard.php");
                exit();
            } else {
                $error = "Failed to update password. Please try again.";
                $show_password_update = true;
            }
            
            $stmt->close();
            $conn->close();
        }
    }
    // Handle 2FA code submission
    elseif (isset($_POST['auth_code'])) {
        // Get the stored user data from session
        $user_id = $_SESSION['temp_user_id'];
        $auth_secret = $_SESSION['temp_auth_secret'];
        $auth_code = $_POST['auth_code'];
        
        // Get user info
        $conn = new mysqli("localhost", "root", "", "malayasol");
        $stmt = $conn->prepare("SELECT username, role, preferred_2fa FROM users WHERE user_id = ?");
        $stmt->bind_param("i", $user_id);
        $stmt->execute();
        $stmt->bind_result($username, $role, $preferred_2fa);
        $stmt->fetch();
        $stmt->close();
        
        // Verify the code
        if ($auth->verifyCode($auth_secret, $auth_code)) {
            // Successful 2FA
            // Update the session to indicate user is fully logged in
            $_SESSION['user_id'] = $user_id;
            $_SESSION['username'] = $username;
            $_SESSION['role'] = $role;
            
            // Update last login time
            $update_stmt = $conn->prepare("UPDATE users SET last_login = NOW() WHERE user_id = ?");
            $update_stmt->bind_param("i", $user_id);
            $update_stmt->execute();
            $update_stmt->close();
            
            // Record the login in login_attempts table
            $ip = $_SERVER['REMOTE_ADDR'];
            $log_sql = "INSERT INTO login_attempts (user_id, attempt_time, ip_address, success, notes) 
                       VALUES (?, NOW(), ?, 1, '2FA completed: authenticator')";
            
            $log_stmt = $conn->prepare($log_sql);
            $log_stmt->bind_param("is", $user_id, $ip);
            $log_stmt->execute();
            $log_stmt->close();
            
            //log user activity and session
            logUserActivity('login', 'ms_index.php', '2FA authentication successful');
            trackUserSession('login');

            // Redirect to dashboard
            header("Location: ms_dashboard.php");
            exit();
        } else {
            $error = "Invalid authentication code. Please try again.";
            $show_2fa_form = true;
        }
        
        $conn->close();
    }
    // Handle 2FA setup submission
    elseif (isset($_POST['setup_auth_code'])) {
        // Get the stored user data from session
        $user_id = $_SESSION['temp_user_id'];
        $auth_secret = $_SESSION['temp_auth_secret'];
        $auth_code = $_POST['setup_auth_code'];
        
        // Verify the code to ensure the setup was correct
        if ($auth->verifyCode($auth_secret, $auth_code)) {
            // Update the user record with the new secret and 2FA preference
            $conn = new mysqli("localhost", "root", "", "malayasol");
            
            $update_sql = "UPDATE users SET 
                           authenticator_secret = ?, 
                           preferred_2fa = 'authenticator'
                           WHERE user_id = ?";
            
            $update_stmt = $conn->prepare($update_sql);
            $update_stmt->bind_param("si", $auth_secret, $user_id);
            $update_stmt->execute();
            $update_stmt->close();
            
            // Get username
            $stmt = $conn->prepare("SELECT username FROM users WHERE user_id = ?");
            $stmt->bind_param("i", $user_id);
            $stmt->execute();
            $stmt->bind_result($username);
            $stmt->fetch();
            $stmt->close();
            
            // Log successful setup
            $ip = $_SERVER['REMOTE_ADDR'];
            $log_sql = "INSERT INTO login_attempts (user_id, attempt_time, ip_address, success, notes) 
                       VALUES (?, NOW(), ?, 1, '2FA setup completed')";
            
            $log_stmt = $conn->prepare($log_sql);
            $log_stmt->bind_param("is", $user_id, $ip);
            $log_stmt->execute();
            $log_stmt->close();
            
            // Store temp username for security questions
            $_SESSION['temp_username'] = $username;
            
            // Show first security question form
            $show_security_question_1 = true;
            $show_2fa_setup = false;
            
            $conn->close();
        } else {
            $error = "Invalid authentication code. Please try again.";
            $show_2fa_setup = true;
            $qr_code_url = $auth->getQRCodeUrl($_SESSION['temp_username'], $auth_secret);
        }
    }
    // Handle first security question setup
    elseif (isset($_POST['setup_security_question_1'])) {
        $user_id = $_SESSION['temp_user_id'];
        $question1_id = $_POST['security_question_1'];
        $answer1 = $_POST['security_answer_1'];
        
        // Validate inputs
        if (empty($question1_id) || empty($answer1)) {
            $error = "Please select a question and provide an answer.";
            $show_security_question_1 = true;
        } else {
            // Hash the answer for security
            $answer1_hash = password_hash($answer1, PASSWORD_DEFAULT);
            
            $conn = new mysqli("localhost", "root", "", "malayasol");
            
            $conn->begin_transaction();
            
            try {
                // Update user record with first security question
                $stmt1 = $conn->prepare("UPDATE users SET security_question_1 = ? WHERE user_id = ?");
                $stmt1->bind_param("ii", $question1_id, $user_id);
                $stmt1->execute();
                $stmt1->close();
                
                // Insert first question answer
                $stmt2 = $conn->prepare("INSERT INTO user_security_answers (user_id, question_id, answer_hash) VALUES (?, ?, ?)");
                $stmt2->bind_param("iis", $user_id, $question1_id, $answer1_hash);
                $stmt2->execute();
                $stmt2->close();
                
                $conn->commit();
                
                // Store the selected question in session to exclude from next step
                $_SESSION['selected_question_1'] = $question1_id;
                
                // Show second security question form
                $show_security_question_2 = true;
                $show_security_question_1 = false;
                
            } catch (Exception $e) {
                $conn->rollback();
                $error = "An error occurred during setup. Please try again.";
                $show_security_question_1 = true;
            }
            
            $conn->close();
        }
    }
    // Handle second security question setup
    elseif (isset($_POST['setup_security_question_2'])) {
        $user_id = $_SESSION['temp_user_id'];
        $question2_id = $_POST['security_question_2'];
        $answer2 = $_POST['security_answer_2'];
        
        // Validate inputs
        if (empty($question2_id) || empty($answer2)) {
            $error = "Please select a question and provide an answer.";
            $show_security_question_2 = true;
        } elseif ($question2_id == $_SESSION['selected_question_1']) {
            $error = "Please select a different security question.";
            $show_security_question_2 = true;
        } else {
            // Hash the answer for security
            $answer2_hash = password_hash($answer2, PASSWORD_DEFAULT);
            
            $conn = new mysqli("localhost", "root", "", "malayasol");
            
            $conn->begin_transaction();
            
            try {
                // Update user record with second security question
                $stmt1 = $conn->prepare("UPDATE users SET security_question_2 = ? WHERE user_id = ?");
                $stmt1->bind_param("ii", $question2_id, $user_id);
                $stmt1->execute();
                $stmt1->close();
                
                // Insert second question answer
                $stmt2 = $conn->prepare("INSERT INTO user_security_answers (user_id, question_id, answer_hash) VALUES (?, ?, ?)");
                $stmt2->bind_param("iis", $user_id, $question2_id, $answer2_hash);
                $stmt2->execute();
                $stmt2->close();
                
                // Update user account status to active
                $stmt3 = $conn->prepare("UPDATE users SET account_status = 'active' WHERE user_id = ?");
                $stmt3->bind_param("i", $user_id);
                $stmt3->execute();
                $stmt3->close();
                
                $conn->commit();
                
                // Set session variables
                $_SESSION['user_id'] = $user_id;
                $_SESSION['username'] = $_SESSION['temp_username'];
                
                // Clear temporary session data
                unset($_SESSION['temp_user_id']);
                unset($_SESSION['temp_auth_secret']);
                unset($_SESSION['temp_username']);
                unset($_SESSION['selected_question_1']);
                
                // Log the completion
                logUserActivity('login', 'ms_index.php', 'Account setup completed');
                trackUserSession('login');

                // Redirect to dashboard
                header("Location: ms_dashboard.php");
                exit();
                
            } catch (Exception $e) {
                $conn->rollback();
                $error = "An error occurred during setup. Please try again.";
                $show_security_question_2 = true;
            }
            
            $conn->close();
        }
    }
    // Handle security recovery questions
    elseif (isset($_POST['security_recovery'])) {
        $user_id = $_POST['user_id'];
        $question_id = $_POST['question_id'];
        $answer = $_POST['security_answer'];
        
        if (empty($answer)) {
            $error = "Please provide an answer to the security question.";
            $show_security_recovery = true;
        } else {
            $conn = new mysqli("localhost", "root", "", "malayasol");
            
            // Get the stored answer hash
            $stmt = $conn->prepare("SELECT answer_hash FROM user_security_answers WHERE user_id = ? AND question_id = ?");
            $stmt->bind_param("ii", $user_id, $question_id);
            $stmt->execute();
            $stmt->bind_result($stored_hash);
            $has_result = $stmt->fetch();
            $stmt->close();
            
            if ($has_result && password_verify($answer, $stored_hash)) {
                // Answer is correct, unlock the account
                $update = $conn->prepare("UPDATE users SET account_status = 'active', failed_attempts = 0 WHERE user_id = ?");
                $update->bind_param("i", $user_id);
                $update->execute();
                $update->close();
                
                // Get user's 2FA secret
                $stmt = $conn->prepare("SELECT authenticator_secret, username FROM users WHERE user_id = ?");
                $stmt->bind_param("i", $user_id);
                $stmt->execute();
                $stmt->bind_result($auth_secret, $username);
                $stmt->fetch();
                $stmt->close();
                
                // Store temp info for 2FA verification
                $_SESSION['temp_user_id'] = $user_id;
                $_SESSION['temp_auth_secret'] = $auth_secret;
                
                // Log the recovery attempt
                $ip = $_SERVER['REMOTE_ADDR'];
                $log_sql = "INSERT INTO login_attempts (user_id, attempt_time, ip_address, success, notes) 
                           VALUES (?, NOW(), ?, 1, 'Account unlocked via security question')";
                
                $log_stmt = $conn->prepare($log_sql);
                $log_stmt->bind_param("is", $user_id, $ip);
                $log_stmt->execute();
                $log_stmt->close();
                
                // Show 2FA form
                $show_2fa_form = true;
                $show_security_recovery = false;
                
            } else {
                $error = "Incorrect answer. Please try again or contact an administrator.";
                $show_security_recovery = true;
            }
            
            $conn->close();
        }
    }
    // Handle initial login
    else {
        $username = $_POST['username'];
        $password = $_POST['password'];

        // Database connection
        $conn = new mysqli("localhost", "root", "", "malayasol");

        if ($conn->connect_error) {
            die("Database connection failed: " . $conn->connect_error);
        }

        // Fetch user record by username
        $stmt = $conn->prepare("SELECT u.user_id, u.username, u.password, u.role, u.account_status, 
                                u.employee_id, e.employment_status, u.authenticator_secret, u.preferred_2fa, 
                                u.failed_attempts, u.security_question_1, u.security_question_2, u.reset_password
                                FROM users u 
                                LEFT JOIN employee e ON u.employee_id = e.employee_id 
                                WHERE u.username = ?");
        $stmt->bind_param("s", $username);
        $stmt->execute();
        $result = $stmt->get_result();

        // If user exists
        if ($result->num_rows === 1) {
            $user = $result->fetch_assoc();

            // Check if account is locked
            if ($user['account_status'] === 'locked') {
                // Get security questions for this user
                $q_stmt = $conn->prepare("SELECT q.question_id, q.question_text 
                                         FROM user_security_answers sa
                                         JOIN security_questions q ON sa.question_id = q.question_id
                                         WHERE sa.user_id = ?
                                         LIMIT 1");
                $q_stmt->bind_param("i", $user['user_id']);
                $q_stmt->execute();
                $q_result = $q_stmt->get_result();
                $q_stmt->close();
                
                if ($q_result->num_rows > 0) {
                    // Show security question recovery
                    $security_question = $q_result->fetch_assoc();
                    $_SESSION['recovery_question'] = $security_question;
                    $_SESSION['recovery_user_id'] = $user['user_id'];
                    $show_security_recovery = true;
                } else {
                    $error = "Your account is locked. Please contact an administrator.";
                }
                
                // Log failed attempt due to locked account
                $ip = $_SERVER['REMOTE_ADDR'];
                $log_sql = "INSERT INTO login_attempts (user_id, attempt_time, ip_address, success, notes) 
                           VALUES (?, NOW(), ?, 0, 'Account locked')";
                
                $log_stmt = $conn->prepare($log_sql);
                $log_stmt->bind_param("is", $user['user_id'], $ip);
                $log_stmt->execute();
                $log_stmt->close();
            }
            // Check if account is disabled
            elseif ($user['account_status'] === 'disabled') {
                $error = "Your account has been disabled. Please contact an administrator.";
                
                // Log failed attempt due to disabled account
                $ip = $_SERVER['REMOTE_ADDR'];
                $log_sql = "INSERT INTO login_attempts (user_id, attempt_time, ip_address, success, notes) 
                           VALUES (?, NOW(), ?, 0, 'Account disabled')";
                
                $log_stmt = $conn->prepare($log_sql);
                $log_stmt->bind_param("is", $user['user_id'], $ip);
                $log_stmt->execute();
                $log_stmt->close();
            }
            // Check if employee is inactive
            elseif ($user['employment_status'] !== 'active') {
                $error = "Your employment status is not active. Please contact HR.";
                
                // Log failed attempt due to employment status
                $ip = $_SERVER['REMOTE_ADDR'];
                $log_sql = "INSERT INTO login_attempts (user_id, attempt_time, ip_address, success, notes) 
                           VALUES (?, NOW(), ?, 0, 'Employment status: {$user['employment_status']}')";
                
                $log_stmt = $conn->prepare($log_sql);
                $log_stmt->bind_param("is", $user['user_id'], $ip);
                $log_stmt->execute();
                $log_stmt->close();
            }
            // Verify password
            elseif ($auth->verifyPassword($password, $user['password']) || $password === $user['password']) {
                // Reset failed attempts on successful password
                $reset_stmt = $conn->prepare("UPDATE users SET failed_attempts = 0 WHERE user_id = ?");
                $reset_stmt->bind_param("i", $user['user_id']);
                $reset_stmt->execute();
                $reset_stmt->close();
                
                // Check if user needs to update password
                if ($user['account_status'] === 'new' || $user['reset_password'] === 'yes') {
                    // Store user ID temporarily for password update
                    $_SESSION['temp_user_id'] = $user['user_id'];
                    $show_password_update = true;
                    $temp_user_id = $user['user_id'];
                }
                else {
                    // Check if user setup is complete
                    $setup_complete = !empty($user['authenticator_secret']) && 
                                     !empty($user['security_question_1']) && 
                                     !empty($user['security_question_2']) && 
                                     $user['account_status'] === 'active';
                    
                    if ($setup_complete) {
                        // User has completed setup, proceed with 2FA
                        $_SESSION['temp_user_id'] = $user['user_id'];
                        $_SESSION['temp_auth_secret'] = $user['authenticator_secret'];
                        
                        // Show the 2FA form
                        $show_2fa_form = true;
                        $temp_user_id = $user['user_id'];
                    }
                    // Super admin bypasses setup for demonstration
                    elseif ($user['role'] === 'superadmin') {
                        // Set session variables
                        $_SESSION['user_id'] = $user['user_id'];
                        $_SESSION['username'] = $user['username'];
                        $_SESSION['role'] = $user['role'];
                        $_SESSION['employee_id'] = $user['employee_id'];

                        // Record the login in login_attempts table
                        $ip = $_SERVER['REMOTE_ADDR'];
                        $log_sql = "INSERT INTO login_attempts (user_id, attempt_time, ip_address, success, notes) 
                                   VALUES (?, NOW(), ?, 1, 'Superadmin login (2FA bypassed)')";
                        
                        $log_stmt = $conn->prepare($log_sql);
                        $log_stmt->bind_param("is", $user['user_id'], $ip);
                        $log_stmt->execute();
                        $log_stmt->close();

                        logUserActivity('login', 'ms_index.php', 'Superadmin login (2FA bypassed)');
                        trackUserSession('login');

                        // Redirect to dashboard
                        header("Location: ms_dashboard.php");
                        exit();
                    }
                    // User needs to complete setup - start with 2FA setup
                    else {
                        // Generate a new secret for the user
                        $new_secret = $auth->createSecret();
                        
                        // Store in session for verification
                        $_SESSION['temp_user_id'] = $user['user_id'];
                        $_SESSION['temp_auth_secret'] = $new_secret;
                        $_SESSION['temp_username'] = $user['username'];
                        
                        // Prepare the QR code URL
                        $qr_code_url = $auth->getQRCodeUrl($user['username'], $new_secret);
                        
                        // Show the 2FA setup form
                        $show_2fa_setup = true;
                        $auth_secret = $new_secret;
                        $temp_username = $user['username'];
                    }
                }
            } else {
                $error = "Invalid username or password.";
                
                // Update failed attempts counter and potentially lock account
                $failed_attempts = $user['failed_attempts'] + 1;
                $status_update = "";
                $lock_note = "Failed password";
                
                if ($failed_attempts >= 3 && $user['account_status'] === 'active') {
                    $status_update = ", account_status = 'locked'";
                    $lock_note = "Account locked - too many failed attempts";
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
}

// If already logged in, redirect to dashboard
if (isset($_SESSION['username']) && !isset($error) && !$show_2fa_form && !$show_2fa_setup && !$show_security_question_1 && !$show_security_question_2 && !$show_security_recovery && !$show_password_update) {
    header("Location: ms_dashboard.php");
    exit();
}

// Load security questions if needed
$security_questions = [];
if ($show_security_question_1 || $show_security_question_2 || $show_security_recovery) {
    $conn = new mysqli("localhost", "root", "", "malayasol");
    $query = "SELECT question_id, question_text FROM security_questions ORDER BY question_id";
    $result = $conn->query($query);
    
    while ($row = $result->fetch_assoc()) {
        $security_questions[$row['question_id']] = $row['question_text'];
    }
    
    $conn->close();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Malaya Solar Technologies Inc.</title>
    <link rel="icon" href="images/Malaya_Logo.png" type="image/png">
    <link href="css/index.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Atkinson+Hyperlegible:wght@400;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <style>
        /* Additional styles for password update form */
        .password-update-form {
            margin-bottom: 1.5rem;
        }

        .password-requirements {
            background-color: var(--form-bg);
            border-radius: 8px;
            padding: 1rem;
            margin-top: 0.5rem;
            font-size: 0.85rem;
        }

        .password-requirements h4 {
            margin-bottom: 0.75rem;
            color: var(--text-dark);
            font-size: 0.9rem;
        }

        .password-requirements ul {
            margin: 0;
            padding-left: 1.25rem;
            list-style-type: none;
        }

        .password-requirements li {
            margin-bottom: 0.4rem;
            position: relative;
            color: var(--text-muted);
        }

        .password-requirements li:before {
            content: "✗";
            position: absolute;
            left: -1.25rem;
            color: var(--error-color);
            font-weight: bold;
        }

        .password-requirements li.valid {
            color: var(--success-color);
        }

        .password-requirements li.valid:before {
            content: "✓";
            color: var(--success-color);
        }

        .password-strength {
            margin-top: 0.5rem;
        }

        .strength-bar {
            width: 100%;
            height: 6px;
            background-color: #e9ecef;
            border-radius: 3px;
            overflow: hidden;
            margin-bottom: 0.5rem;
        }

        .strength-progress {
            height: 100%;
            transition: width 0.3s ease, background-color 0.3s ease;
            border-radius: 3px;
        }

        .strength-text {
            font-size: 0.8rem;
            font-weight: 600;
        }

        .strength-weak {
            background-color: var(--error-color);
            color: var(--error-color);
        }

        .strength-medium {
            background-color: #ffc107;
            color: #ffc107;
        }

        .strength-strong {
            background-color: var(--success-color);
            color: var(--success-color);
        }

        .form-group.password-group {
            position: relative;
        }

        .password-toggle {
            position: absolute;
            right: 1rem;
            top: 50%;
            transform: translateY(-50%);
            background: none;
            border: none;
            color: var(--text-muted);
            cursor: pointer;
            font-size: 1rem;
            padding: 0.25rem;
        }

        .password-toggle:hover {
            color: var(--text-dark);
        }

        .match-indicator {
            font-size: 0.8rem;
            margin-top: 0.25rem;
            font-weight: 500;
        }

        .match-success {
            color: var(--success-color);
        }

        .match-error {
            color: var(--error-color);
        }

        /* Responsive adjustments for password form */
        @media (max-width: 768px) {
            .password-requirements {
                font-size: 0.8rem;
                padding: 0.75rem;
            }
            
            .password-requirements li {
                margin-bottom: 0.3rem;
            }
        }
    </style>
</head>
<body>
    <div class="login-container">
        <!-- Brand Panel (Left) -->
        <div class="brand-panel">
            <div class="brand-content">
                <img src="images/Malaya_Logo.png" alt="Malaya Solar Energies Logo" class="brand-logo">
                <h1 class="brand-title">Malaya Solar Technologies</h1>
                <p class="brand-description">Access the system to manage your solar projects efficiently and track financial performance.</p>
            </div>
        </div>
        
        <!-- Image Panel (Right) -->
        <div class="image-panel">
            <img src="login_background/login-solar-panel-1.png" alt="Solar Panels" class="background-image">
            <div class="image-overlay"></div>
        </div>
        
        <?php if ($show_password_update): ?>
            <!-- Password Update Card -->
            <div class="auth-card">
                <div class="auth-header">
                    <h2 class="auth-title">Update Your Password</h2>
                    <p class="auth-subtitle">Please create a new secure password to continue</p>
                </div>
                
                <?php if (!empty($error)): ?>
                <div class="error-message">
                    <?= $error ?>
                </div>
                <?php endif; ?>
                
                <form action="ms_index.php" method="POST" class="password-update-form">
                    <input type="hidden" name="update_password" value="1">
                    
                    <div class="form-group password-group">
                        <label for="new_password">New Password:</label>
                        <input type="password" id="new_password" name="new_password" class="form-input" required>
                        <button type="button" class="password-toggle" onclick="togglePassword('new_password', this)">
                            <i class="fas fa-eye"></i>
                        </button>
                    </div>
                    
                    <div class="password-requirements">
                        <h4>Password Requirements:</h4>
                        <ul id="password-checklist">
                            <li id="length-check">At least 12 characters long</li>
                            <li id="uppercase-check">At least one uppercase letter (A-Z)</li>
                            <li id="lowercase-check">At least one lowercase letter (a-z)</li>
                            <li id="digit-check">At least one digit (0-9)</li>
                            <li id="special-check">At least one special character (!@#$%^&*)</li>
                        </ul>
                        
                        <div class="password-strength">
                            <div class="strength-bar">
                                <div class="strength-progress" id="strength-progress"></div>
                            </div>
                            <div class="strength-text" id="strength-text">Password strength: Weak</div>
                        </div>
                    </div>
                    
                    <div class="form-group password-group">
                        <label for="confirm_password">Confirm Password:</label>
                        <input type="password" id="confirm_password" name="confirm_password" class="form-input" required>
                        <button type="button" class="password-toggle" onclick="togglePassword('confirm_password', this)">
                            <i class="fas fa-eye"></i>
                        </button>
                        <div class="match-indicator" id="match-indicator"></div>
                    </div>
                    
                    <button type="submit" class="login-btn" id="update-btn" disabled>Update Password</button>
                </form>
                
                <div class="login-footer">
                    &copy; 2025 Malaya Solar Energies Inc. All rights reserved.
                </div>
            </div>

        <?php elseif ($show_2fa_form): ?>
            <!-- 2FA Authentication Card -->
            <div class="auth-card">
                <div class="auth-header">
                    <h2 class="auth-title">Two-Factor Authentication</h2>
                    <p class="auth-subtitle">Enter the code from your authenticator app</p>
                </div>
                
                <?php if (!empty($error)): ?>
                <div class="error-message">
                    <?= $error ?>
                </div>
                <?php endif; ?>
                
                <form action="ms_index.php" method="POST" class="login-form">
                    <input type="text" name="auth_code" class="code-input" 
                           placeholder="______" maxlength="6" inputmode="numeric" pattern="[0-9]*" 
                           autocomplete="one-time-code" required autofocus>
                    
                    <button type="submit" class="login-btn">Verify</button>
                </form>
                
                <div class="login-footer">
                    &copy; 2025 Malaya Solar Energies Inc. All rights reserved.
                </div>
            </div>
        
        <?php elseif ($show_2fa_setup): ?>
            <!-- 2FA Setup Card -->
            <div class="auth-card">
                
                <?php if (!empty($error)): ?>
                <div class="error-message">
                    <?= $error ?>
                </div>
                <?php endif; ?>
                
                <div class="setup-container">
                    <div class="setup-content">
                        <div class="setup-left">                            
                            <form action="ms_index.php" method="POST" class="login-form">
                                <div class="form-group verification-group">
                                    <div class="auth-header">
                                        <h2 class="auth-title">Setup Two-Factor Authentication</h2>
                                        <p class="auth-subtitle">Enhance your account security</p>
                                    </div>

                                    <label for="verification-code">Verification Code:</label>
                                    <input type="text" id="verification-code" name="setup_auth_code" class="code-input" 
                                        placeholder="______" maxlength="6" inputmode="numeric" pattern="[0-9]*" 
                                        autocomplete="one-time-code" required autofocus>
                                </div>
                                
                                <button type="submit" class="login-btn">Verify & Continue</button>
                            </form>
                        </div>
                        
                        <div class="setup-right">
                            <div class="qr-code-wrapper">
                                <img src="<?= $qr_code_url ?>" alt="QR Code" class="qr-code">
                            </div>
                            
                            <div class="manual-entry">
                                <p>If you can't scan the QR code, enter this key manually:</p>
                                <div class="secret-key"><?= $auth_secret ?></div>
                            </div>
                        </div>
                    </div>
                </div>
                
                <div class="login-footer">
                    &copy; 2025 Malaya Solar Energies Inc. All rights reserved.
                </div>
            </div>

        <?php elseif ($show_security_question_1): ?>
            <!-- First Security Question Setup Card -->
            <div class="auth-card">
                <div class="auth-header">
                    <h2 class="auth-title">Setup Security Questions</h2>
                    <p class="auth-subtitle">Step 1 of 2: Choose your first security question</p>
                </div>
                
                <?php if (!empty($error)): ?>
                <div class="error-message">
                    <?= $error ?>
                </div>
                <?php endif; ?>
                
                <form action="ms_index.php" method="POST" class="security-questions-form">
                    <input type="hidden" name="setup_security_question_1" value="1">
                    
                    <div class="form-section">
                        <div class="form-group">
                            <label for="security_question_1">Choose your first security question:</label>
                            <select id="security_question_1" name="security_question_1" class="form-select" required>
                                <option value="">Select a question...</option>
                                <?php foreach ($security_questions as $id => $text): ?>
                                    <option value="<?= $id ?>"><?= htmlspecialchars($text) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        
                        <div class="form-group">
                            <label for="security_answer_1">Your answer:</label>
                            <input type="text" id="security_answer_1" name="security_answer_1" class="form-input" required>
                        </div>
                    </div>
                    
                    <button type="submit" class="login-btn">Continue to Next Question</button>
                </form>
                
                <div class="login-footer">
                    &copy; 2025 Malaya Solar Energies Inc. All rights reserved.
                </div>
            </div>

        <?php elseif ($show_security_question_2): ?>
            <!-- Second Security Question Setup Card -->
            <div class="auth-card">
                <div class="auth-header">
                    <h2 class="auth-title">Setup Security Questions</h2>
                    <p class="auth-subtitle">Step 2 of 2: Choose your second security question</p>
                </div>
                
                <?php if (!empty($error)): ?>
                <div class="error-message">
                    <?= $error ?>
                </div>
                <?php endif; ?>
                
                <form action="ms_index.php" method="POST" class="security-questions-form">
                    <input type="hidden" name="setup_security_question_2" value="1">
                    
                    <div class="form-section">
                        <div class="form-group">
                            <label for="security_question_2">Choose your second security question:</label>
                            <select id="security_question_2" name="security_question_2" class="form-select" required>
                                <option value="">Select a question...</option>
                                <?php foreach ($security_questions as $id => $text): ?>
                                    <?php if ($id != $_SESSION['selected_question_1']): ?>
                                        <option value="<?= $id ?>"><?= htmlspecialchars($text) ?></option>
                                    <?php endif; ?>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        
                        <div class="form-group">
                            <label for="security_answer_2">Your answer:</label>
                            <input type="text" id="security_answer_2" name="security_answer_2" class="form-input" required>
                        </div>
                    </div>
                    
                    <button type="submit" class="login-btn">Complete Setup</button>
                </form>
                
                <div class="login-footer">
                    &copy; 2025 Malaya Solar Energies Inc. All rights reserved.
                </div>
            </div>
                
        <?php elseif ($show_security_recovery): ?>
            <!-- Security Recovery Card -->
            <div class="auth-card">
                <div class="auth-header">
                    <h2 class="auth-title">Account Recovery</h2>
                    <p class="auth-subtitle">Answer your security question to unlock your account</p>
                </div>
                
                <?php if (!empty($error)): ?>
                <div class="error-message">
                    <?= $error ?>
                </div>
                <?php endif; ?>
                
                <form action="ms_index.php" method="POST" class="security-recovery-form">
                    <input type="hidden" name="security_recovery" value="1">
                    <input type="hidden" name="user_id" value="<?= $_SESSION['recovery_user_id'] ?>">
                    <input type="hidden" name="question_id" value="<?= $_SESSION['recovery_question']['question_id'] ?>">
                    
                    <div class="form-group">
                        <label for="security_question">Security Question:</label>
                        <div class="question-text"><?= $_SESSION['recovery_question']['question_text'] ?></div>
                    </div>
                    
                    <div class="form-group">
                        <label for="security_answer">Your answer:</label>
                        <input type="text" id="security_answer" name="security_answer" class="form-input" required autofocus>
                    </div>
                    
                    <button type="submit" class="login-btn">Submit</button>
                </form>
                
                <div class="login-options">
                    <a href="ms_index.php" class="back-to-login">Back to Login</a>
                    <span>|</span>
                    <a href="#" class="contact-admin">Contact Administrator</a>
                </div>
                
                <div class="login-footer">
                    &copy; 2025 Malaya Solar Energies Inc. All rights reserved.
                </div>
            </div>

        <?php else: ?>
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
                                        
                    <button type="submit" class="login-btn">Sign In</button>
                </form>
                
                <div class="login-footer">
                    &copy; 2025 Malaya Solar Energies Inc. All rights reserved.
                </div>
            </div>
        <?php endif; ?>
    </div>
    
    <script>
        // Password visibility toggle function
        function togglePassword(inputId, button) {
            const input = document.getElementById(inputId);
            const icon = button.querySelector('i');
            
            if (input.type === 'password') {
                input.type = 'text';
                icon.classList.remove('fa-eye');
                icon.classList.add('fa-eye-slash');
            } else {
                input.type = 'password';
                icon.classList.remove('fa-eye-slash');
                icon.classList.add('fa-eye');
            }
        }

        // Password validation and strength checking
        document.addEventListener('DOMContentLoaded', function() {
            const newPasswordInput = document.getElementById('new_password');
            const confirmPasswordInput = document.getElementById('confirm_password');
            const updateBtn = document.getElementById('update-btn');
            const matchIndicator = document.getElementById('match-indicator');
            const strengthProgress = document.getElementById('strength-progress');
            const strengthText = document.getElementById('strength-text');

            if (newPasswordInput) {
                // Password requirements checklist
                const checks = {
                    length: { element: document.getElementById('length-check'), regex: /.{12,}/ },
                    uppercase: { element: document.getElementById('uppercase-check'), regex: /[A-Z]/ },
                    lowercase: { element: document.getElementById('lowercase-check'), regex: /[a-z]/ },
                    digit: { element: document.getElementById('digit-check'), regex: /[0-9]/ },
                    special: { element: document.getElementById('special-check'), regex: /[^A-Za-z0-9]/ }
                };

                function validatePassword() {
                    const password = newPasswordInput.value;
                    const confirmPassword = confirmPasswordInput.value;
                    let validCount = 0;

                    // Check each requirement
                    for (const [key, check] of Object.entries(checks)) {
                        if (check.regex.test(password)) {
                            check.element.classList.add('valid');
                            validCount++;
                        } else {
                            check.element.classList.remove('valid');
                        }
                    }

                    // Update strength indicator
                    updateStrengthIndicator(validCount);

                    // Check password match
                    updateMatchIndicator(password, confirmPassword);

                    // Enable/disable submit button
                    const allValid = validCount === 5;
                    const passwordsMatch = password === confirmPassword && password.length > 0;
                    updateBtn.disabled = !(allValid && passwordsMatch);
                }

                function updateStrengthIndicator(validCount) {
                    const percentage = (validCount / 5) * 100;
                    strengthProgress.style.width = percentage + '%';

                    if (validCount <= 2) {
                        strengthProgress.className = 'strength-progress strength-weak';
                        strengthText.innerHTML = 'Password strength: <span class="strength-weak">Weak</span>';
                    } else if (validCount <= 4) {
                        strengthProgress.className = 'strength-progress strength-medium';
                        strengthText.innerHTML = 'Password strength: <span class="strength-medium">Medium</span>';
                    } else {
                        strengthProgress.className = 'strength-progress strength-strong';
                        strengthText.innerHTML = 'Password strength: <span class="strength-strong">Strong</span>';
                    }
                }

                function updateMatchIndicator(password, confirmPassword) {
                    if (confirmPassword.length === 0) {
                        matchIndicator.textContent = '';
                        matchIndicator.className = 'match-indicator';
                    } else if (password === confirmPassword) {
                        matchIndicator.textContent = '✓ Passwords match';
                        matchIndicator.className = 'match-indicator match-success';
                    } else {
                        matchIndicator.textContent = '✗ Passwords do not match';
                        matchIndicator.className = 'match-indicator match-error';
                    }
                }

                // Add event listeners
                newPasswordInput.addEventListener('input', validatePassword);
                confirmPasswordInput.addEventListener('input', validatePassword);

                // Initial validation
                validatePassword();
            }

            // Enhanced JavaScript for all forms
            // Auto-focus functionality
            const codeInput = document.querySelector('.code-input');
            if (codeInput) {
                codeInput.focus();
                
                // Format code input as user types
                codeInput.addEventListener('input', function(e) {
                    // Remove any non-numeric characters
                    this.value = this.value.replace(/[^0-9]/g, '');
                    
                    // Limit to 6 digits
                    if (this.value.length > 6) {
                        this.value = this.value.slice(0, 6);
                    }
                });
            }
            
            // Focus first required input if no code input exists
            const firstInput = document.querySelector('input[required]:not([type="hidden"])');
            if (firstInput && !codeInput && firstInput.id !== 'new_password') {
                firstInput.focus();
            }
            
            // Enhanced form validation for security questions
            const securityForm = document.querySelector('.security-questions-form');
            if (securityForm) {
                securityForm.addEventListener('submit', function(e) {
                    const questionSelect = securityForm.querySelector('select[required]');
                    const answerInput = securityForm.querySelector('input[type="text"][required]');
                    
                    if (questionSelect && !questionSelect.value) {
                        e.preventDefault();
                        questionSelect.focus();
                        return false;
                    }
                    
                    if (answerInput && !answerInput.value.trim()) {
                        e.preventDefault();
                        answerInput.focus();
                        return false;
                    }
                    
                    if (answerInput && answerInput.value.trim().length < 2) {
                        e.preventDefault();
                        answerInput.focus();
                        return false;
                    }
                });
            }
        });

        // Prevent form double submission
        document.addEventListener('submit', function(e) {
            const submitBtn = e.target.querySelector('button[type="submit"]');
            if (submitBtn && !submitBtn.disabled) {
                submitBtn.disabled = true;
                const originalText = submitBtn.textContent;
                submitBtn.textContent = 'Please wait...';
                
                // Re-enable after 5 seconds in case of network issues
                setTimeout(() => {
                    submitBtn.disabled = false;
                    submitBtn.textContent = originalText;
                }, 5000);
            }
        });

        // Enhanced accessibility
        document.addEventListener('keydown', function(e) {
            // Allow Enter key to submit forms
            if (e.key === 'Enter' && e.target.matches('input, select')) {
                const form = e.target.closest('form');
                if (form) {
                    const submitBtn = form.querySelector('button[type="submit"]');
                    if (submitBtn && !submitBtn.disabled) {
                        submitBtn.click();
                    }
                }
            }
            
            // Allow Escape key to clear current field
            if (e.key === 'Escape' && e.target.matches('input[type="text"], input[type="password"]')) {
                e.target.value = '';
                e.target.focus();
                // Trigger validation if it's the password field
                if (e.target.id === 'new_password' || e.target.id === 'confirm_password') {
                    e.target.dispatchEvent(new Event('input'));
                }
            }
        });
    </script>
</body>
</html>