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
$qr_code_url = '';
$auth_secret = '';
$temp_user_id = '';
$temp_username = '';

// Handle login form submission
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // If 2FA library is not available, show a message to administrators
    if (!$auth_lib_available && (isset($_POST['auth_code']) || isset($_POST['setup_auth_code']))) {
        $error = "Two-factor authentication library is not available. Please contact the system administrator.";
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
                                u.failed_attempts, u.security_question_1, u.security_question_2
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
if (isset($_SESSION['username']) && !isset($error) && !$show_2fa_form && !$show_2fa_setup && !$show_security_question_1 && !$show_security_question_2 && !$show_security_recovery) {
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
        
        <?php if ($show_2fa_form): ?>
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
                    
                    <a href="#" class="forgot-password">Forgot password?</a>
                    
                    <button type="submit" class="login-btn">Sign In</button>
                </form>
                
                <div class="login-footer">
                    &copy; 2025 Malaya Solar Energies Inc. All rights reserved.
                </div>
            </div>
        <?php endif; ?>
    </div>
    
    <script>
        // Enhanced JavaScript for security questions setup
        document.addEventListener('DOMContentLoaded', function() {
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
            if (firstInput && !codeInput) {
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
                        showValidationError(questionSelect, 'Please select a security question');
                        return false;
                    }
                    
                    if (answerInput && !answerInput.value.trim()) {
                        e.preventDefault();
                        answerInput.focus();
                        showValidationError(answerInput, 'Please provide an answer');
                        return false;
                    }
                    
                    if (answerInput && answerInput.value.trim().length < 2) {
                        e.preventDefault();
                        answerInput.focus();
                        showValidationError(answerInput, 'Answer must be at least 2 characters long');
                        return false;
                    }
                });
            }
            
            // Real-time validation feedback
            const requiredInputs = document.querySelectorAll('input[required], select[required]');
            requiredInputs.forEach(input => {
                input.addEventListener('blur', function() {
                    validateField(this);
                });
                
                input.addEventListener('input', function() {
                    clearValidationError(this);
                });
            });
            
            // Security question selection validation
            const questionSelects = document.querySelectorAll('select[name^="security_question"]');
            questionSelects.forEach(select => {
                select.addEventListener('change', function() {
                    validateField(this);
                });
            });
        });

        // Prevent form double submission
        document.addEventListener('submit', function(e) {
            const submitBtn = e.target.querySelector('button[type="submit"]');
            if (submitBtn && !submitBtn.disabled) {
                submitBtn.disabled = true;
                submitBtn.textContent = submitBtn.textContent.includes('Continue') ? 'Processing...' : 'Please wait...';
                
                // Re-enable after 3 seconds in case of network issues
                setTimeout(() => {
                    submitBtn.disabled = false;
                    submitBtn.textContent = submitBtn.textContent.replace('Processing...', 'Continue to Next Question');
                    submitBtn.textContent = submitBtn.textContent.replace('Please wait...', 'Complete Setup');
                }, 3000);
            }
        });

        // Auto-resize text inputs based on content
        document.addEventListener('input', function(e) {
            if (e.target.matches('input[type="text"]')) {
                // Auto-expand input if content is long
                const minWidth = 200;
                const maxWidth = 400;
                const charWidth = 8;
                const newWidth = Math.min(maxWidth, Math.max(minWidth, e.target.value.length * charWidth + 20));
                e.target.style.width = newWidth + 'px';
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
            if (e.key === 'Escape' && e.target.matches('input[type="text"]')) {
                e.target.value = '';
                e.target.focus();
            }
        });
    </script>
</body>
</html>