<?php
session_start();
require_once 'google_auth.php';

// Initialize auth class
$auth = new MalayaSolarAuth();

// Check if the library is available
$auth_lib_available = $auth->isLibraryAvailable();

// Initialize state variables
$error = '';
$show_2fa_form = false;
$show_2fa_setup = false;
$show_security_questions = false;
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
            
            // Show security questions form
            $show_security_questions = true;
            $show_2fa_setup = false;
            
            $conn->close();
        } else {
            $error = "Invalid authentication code. Please try again.";
            $show_2fa_setup = true;
            $qr_code_url = $auth->getQRCodeUrl($_SESSION['temp_username'], $auth_secret);
        }
    }
    // Handle security questions setup
    elseif (isset($_POST['setup_security_questions'])) {
        $user_id = $_SESSION['temp_user_id'];
        $question1_id = $_POST['security_question1'];
        $answer1 = $_POST['security_answer1'];
        $question2_id = $_POST['security_question2'];
        $answer2 = $_POST['security_answer2'];
        
        // Validate inputs
        if (empty($question1_id) || empty($answer1) || empty($question2_id) || empty($answer2)) {
            $error = "Please select two questions and provide answers.";
            $show_security_questions = true;
        } elseif ($question1_id == $question2_id) {
            $error = "Please select two different security questions.";
            $show_security_questions = true;
        } else {
            // Hash the answers for security
            $answer1_hash = password_hash($answer1, PASSWORD_DEFAULT);
            $answer2_hash = password_hash($answer2, PASSWORD_DEFAULT);
            
            $conn = new mysqli("localhost", "root", "", "malayasol");
            
            // Insert security question answers
            $conn->begin_transaction();
            
            try {
                // Insert first question answer
                $stmt1 = $conn->prepare("INSERT INTO user_security_answers (user_id, question_id, answer_hash) VALUES (?, ?, ?)");
                $stmt1->bind_param("iis", $user_id, $question1_id, $answer1_hash);
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
                
                // Redirect to dashboard
                header("Location: ms_dashboard.php");
                exit();
                
            } catch (Exception $e) {
                $conn->rollback();
                $error = "An error occurred during setup. Please try again.";
                $show_security_questions = true;
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
                                u.failed_attempts
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
                
                // Check if 2FA is needed
                if (!empty($user['authenticator_secret']) && $user['preferred_2fa'] === 'authenticator') {
                    // Store minimal info in session for 2FA check
                    $_SESSION['temp_user_id'] = $user['user_id'];
                    $_SESSION['temp_auth_secret'] = $user['authenticator_secret'];
                    
                    // Show the 2FA form
                    $show_2fa_form = true;
                    $temp_user_id = $user['user_id'];
                }
                // Check if this is a new account that needs 2FA setup
                elseif ($user['account_status'] === 'new') {
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
                // Super admin bypasses 2FA for demonstration
                elseif ($user['role'] === 'superadmin') {
                    // Set session variables
                    $_SESSION['user_id'] = $user['user_id'];
                    $_SESSION['username'] = $user['username'];
                    $_SESSION['role'] = $user['role'];
                    $_SESSION['employee_id'] = $user['employee_id'];

                    // Record the login in login_attempts table
                    $ip = $_SERVER['REMOTE_ADDR'];
                    $log_sql = "INSERT INTO login_attempts (user_id, attempt_time, ip_address, success, notes) 
                               VALUES (?, NOW(), ?, 1, 'Admin login (2FA skipped)')";
                    
                    $log_stmt = $conn->prepare($log_sql);
                    $log_stmt->bind_param("is", $user['user_id'], $ip);
                    $log_stmt->execute();
                    $log_stmt->close();

                    // Redirect to dashboard
                    header("Location: ms_dashboard.php");
                    exit();
                }
                // No 2FA configured yet - Show setup screen
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
if (isset($_SESSION['username']) && !isset($error) && !$show_2fa_form && !$show_2fa_setup && !$show_security_questions && !$show_security_recovery) {
    header("Location: ms_dashboard.php");
    exit();
}

// Load security questions if needed
$security_questions = [];
if ($show_security_questions || $show_security_recovery) {
    $conn = new mysqli("localhost", "root", "", "malayasol");
    $query = "SELECT question_id, question_text FROM security_questions";
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
    <title>Malaya Solar Energies Inc.</title>
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
                <h1 class="brand-title">Malaya Solar Energies</h1>
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
                                
                                <button type="submit" class="login-btn">Verify</button>
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

            <?php elseif ($show_security_questions): ?>
                <!-- Security Questions Setup Card -->
                <div class="auth-card">
                    <div class="auth-header">
                        <h2 class="auth-title">Set Up Security Questions</h2>
                        <p class="auth-subtitle">Choose and answer 2 security questions for account recovery</p>
                    </div>
                    
                    <?php if (!empty($error)): ?>
                    <div class="error-message">
                        <?= $error ?>
                    </div>
                    <?php endif; ?>
                    
                    <form action="ms_index.php" method="POST" class="security-questions-form">
                        <input type="hidden" name="setup_security_questions" value="1">
                        
                        <div class="form-section">
                            <h3>Question 1</h3>
                            <div class="form-group">
                                <label for="security_question1">Choose a security question:</label>
                                <select id="security_question1" name="security_question1" class="form-select" required>
                                    <option value="">Select a question...</option>
                                    <option value="1">What was the name of your first pet?</option>
                                    <option value="2">What is your mother's maiden name?</option>
                                    <option value="3">What was the name of your childhood crush?</option>
                                    <option value="4">In what city were you born?</option>
                                    <option value="5">What is the name of your favorite childhood teacher?</option>
                                    <option value="6">What was the name of your first school?</option>
                                    <option value="7">What is your favorite book?</option>
                                </select>
                            </div>
                            
                            <div class="form-group">
                                <label for="security_answer1">Your answer:</label>
                                <input type="text" id="security_answer1" name="security_answer1" class="form-input" required>
                            </div>
                        </div>
                        
                        <div class="form-section">
                            <h3>Question 2</h3>
                            <div class="form-group">
                                <label for="security_question2">Choose a security question:</label>
                                <select id="security_question2" name="security_question2" class="form-select" required>
                                    <option value="">Select a question...</option>
                                    <option value="1">What was the name of your first pet?</option>
                                    <option value="2">What is your mother's maiden name?</option>
                                    <option value="3">What was the name of your childhood crush?</option>
                                    <option value="4">In what city were you born?</option>
                                    <option value="5">What is the name of your favorite childhood teacher?</option>
                                    <option value="6">What was the name of your first school?</option>
                                    <option value="7">What is your favorite book?</option>
                                </select>
                            </div>
                            
                            <div class="form-group">
                                <label for="security_answer2">Your answer:</label>
                                <input type="text" id="security_answer2" name="security_answer2" class="form-input" required>
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
        // Prevent selecting the same security question twice
        document.addEventListener('DOMContentLoaded', function() {
            const question1 = document.getElementById('security_question1');
            const question2 = document.getElementById('security_question2');
            
            if (question1 && question2) {
                question1.addEventListener('change', function() {
                    const selectedValue = this.value;
                    
                    // Enable all options in question2
                    Array.from(question2.options).forEach(option => {
                        option.disabled = false;
                    });
                    
                    // Disable the option that matches the selected value in question1
                    if (selectedValue) {
                        const optionToDisable = question2.querySelector(`option[value="${selectedValue}"]`);
                        if (optionToDisable) {
                            optionToDisable.disabled = true;
                        }
                        
                        // If question2 has the same value as question1, reset it
                        if (question2.value === selectedValue) {
                            question2.value = '';
                        }
                    }
                });
                
                question2.addEventListener('change', function() {
                    const selectedValue = this.value;
                    
                    // Enable all options in question1
                    Array.from(question1.options).forEach(option => {
                        option.disabled = false;
                    });
                    
                    // Disable the option that matches the selected value in question2
                    if (selectedValue) {
                        const optionToDisable = question1.querySelector(`option[value="${selectedValue}"]`);
                        if (optionToDisable) {
                            optionToDisable.disabled = true;
                        }
                        
                        // If question1 has the same value as question2, reset it
                        if (question1.value === selectedValue) {
                            question1.value = '';
                        }
                    }
                });
            }
            
            // Auto-focus code input fields when they appear
            const codeInput = document.querySelector('.code-input');
            if (codeInput) {
                codeInput.focus();
            }
        });
    </script>
</body>
</html>