<?php
session_start();

$error = "";
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $conn = new mysqli("localhost", "root", "", "malayasol");

    // Check for DB connection error
    if ($conn->connect_error) {
        die("Database connection failed: " . $conn->connect_error);
    }

    $username = $_POST['username'];
    $password = $_POST['password'];

    $stmt = $conn->prepare("SELECT * FROM users WHERE username = ? AND password = ?");
    $stmt->bind_param("ss", $username, $password);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result && $result->num_rows == 1) {
        $user = $result->fetch_assoc();

        $_SESSION['user_id'] = $user['user_id'];
        $_SESSION['username'] = $user['username'];
        $_SESSION['role'] = $user['role'];

        header("Location: ms_dashboard.php");
        exit();
    } else {
        $error = "Invalid username or password";
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
                        <!-- Add the form action here -->
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
