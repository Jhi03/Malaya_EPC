<?php
session_start();
require_once 'google_auth.php';

// Initialize auth class
$auth = new MalayaSolarAuth();

// Check if the library is available
$auth_lib_available = $auth->isLibraryAvailable();

// Initialize error variable
$error = '';
$show_2fa_form = false;
$show_2fa_setup = false;
$qr_code_url = '';
$auth_secret = '';
$temp_user_id = '';
$temp_username = '';

// Handle login form submission
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // If 2FA library is not available, show a message to administrators
    if (!$auth_lib_available && isset($_POST['auth_code']) || isset($_POST['setup_auth_code'])) {
        $error = "Two-factor authentication library is not available. Please contact the system administrator.";
    }
    // Handle 2FA code submission
    elseif (isset($_POST['auth_code'])) {
        // Your existing code...
    }
    // Handle 2FA code submission
    if (isset($_POST['auth_code'])) {
        // Get the stored user data from session
        $user_id = $_SESSION['temp_user_id'];
        $auth_secret = $_SESSION['temp_auth_secret'];
        $auth_code = $_POST['auth_code'];
        
        // Get user info
        $conn = new mysqli("localhost", "root", "", "malayasol");
        $stmt = $conn->prepare("SELECT username, preferred_2fa FROM users WHERE user_id = ?");
        $stmt->bind_param("i", $user_id);
        $stmt->execute();
        $stmt->bind_result($username, $preferred_2fa);
        $stmt->fetch();
        $stmt->close();
        
        // Verify the code
        if ($auth->verifyCode($auth_secret, $auth_code)) {
            // Successful 2FA
            // Update the session to indicate user is fully logged in
            $_SESSION['user_id'] = $user_id;
            $_SESSION['username'] = $username;
            
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
                           preferred_2fa = 'authenticator',
                           account_status = 'active'
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
            
            // Set the session to indicate user is fully logged in
            $_SESSION['user_id'] = $user_id;
            $_SESSION['username'] = $username;
            
            $conn->close();
            
            // Redirect to dashboard
            header("Location: ms_dashboard.php");
            exit();
        } else {
            $error = "Invalid authentication code. Please try again.";
            $show_2fa_setup = true;
            $qr_code_url = $auth->getQRCodeUrl($_SESSION['temp_username'], $auth_secret);
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
                $error = "Your account is locked. Please contact an administrator.";
                
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
if (isset($_SESSION['username']) && !isset($error) && !$show_2fa_form && !$show_2fa_setup) {
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
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <style>
        /* Additional styles for 2FA form */
        .auth-card {
            position: absolute;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%);
            width: 400px;
            background-color: white;
            border-radius: 20px;
            padding: 3rem;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.2);
            z-index: 10;
            text-align: center;
        }
        
        .auth-title {
            font-size: 1.75rem;
            color: #333;
            margin-bottom: 0.5rem;
        }
        
        .auth-subtitle {
            color: #666;
            font-size: 1rem;
            margin-bottom: 2rem;
        }
        
        .code-input {
            letter-spacing: 8px;
            font-size: 1.5rem;
            padding: 0.75rem;
            text-align: center;
            width: 100%;
            margin-bottom: 1.5rem;
        }
        
        .qr-code {
            display: block;
            margin: 0 auto 1.5rem auto;
            max-width: 200px;
            height: auto;
        }
        
        .qr-container {
            position: relative;
            margin: 0 auto 1.5rem auto;
            max-width: 200px;
        }

        .qr-fallback-info {
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background-color: rgba(255, 255, 255, 0.9);
            padding: 10px;
            font-size: 0.8rem;
            display: flex;
            flex-direction: column;
            justify-content: center;
            text-align: center;
            border-radius: 5px;
        }

        .otpauth-url {
            word-break: break-all;
            background: #f0f0f0;
            padding: 5px;
            border-radius: 3px;
            font-size: 0.7rem;
            color: #555;
            max-height: 60px;
            overflow-y: auto;
            margin-top: 5px;
        }

        .auth-instructions {
            margin-bottom: 2rem;
            text-align: left;
            font-size: 0.9rem;
            color: #555;
        }
        
        .auth-instructions ol {
            padding-left: 1.5rem;
        }
        
        .auth-instructions li {
            margin-bottom: 0.5rem;
        }
        
        .secret-key {
            font-family: monospace;
            background-color: #f8f9fa;
            padding: 0.5rem;
            border-radius: 4px;
            font-size: 1rem;
            letter-spacing: 2px;
            margin: 1rem 0;
            word-break: break-all;
        }
    </style>
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
                <div class="auth-header">
                    <h2 class="auth-title">Setup Two-Factor Authentication</h2>
                    <p class="auth-subtitle">Scan this QR code with your authenticator app</p>
                </div>
                
                <?php if (!empty($error)): ?>
                <div class="error-message">
                    <?= $error ?>
                </div>
                <?php endif; ?>
                
                <div class="qr-code-container">
                    <!-- Using the QR code URL from GoogleAuthenticator class -->
                    <img src="<?= $qr_code_url ?>" alt="QR Code" class="qr-code">
                </div>
                
                <p>Or enter this key manually:</p>
                <div class="secret-key"><?= $auth_secret ?></div>
                
                <form action="ms_index.php" method="POST" class="login-form">
                    <input type="text" name="setup_auth_code" class="code-input" 
                        placeholder="______" maxlength="6" inputmode="numeric" pattern="[0-9]*" 
                        autocomplete="one-time-code" required autofocus>
                    
                    <button type="submit" class="login-btn">Verify and Activate</button>
                </form>
                
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
        // Auto-focus code input fields when they appear
        document.addEventListener('DOMContentLoaded', function() {
            const codeInput = document.querySelector('.code-input');
            if (codeInput) {
                codeInput.focus();
            }
        });
    </script>
</body>
</html>