<?php
session_start();

// If user is not logged in, redirect to login
if (!isset($_SESSION['user_id'])) {
    header("Location: ms_index.php");
    exit();
}

// Include access control to get user info
require_once 'access_control.php';

$user_role = getCurrentUserRole();
$user_department = getCurrentUserDepartment();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Access Denied - Malaya Solar Energies Inc.</title>
    <link rel="icon" href="images/Malaya_Logo.png" type="image/png">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Atkinson+Hyperlegible&display=swap" rel="stylesheet">
    <style>
        body {
            font-family: 'Atkinson Hyperlegible', sans-serif;
            background-color: #f8f9fa;
            height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        .access-denied-container {
            max-width: 500px;
            text-align: center;
            background: white;
            padding: 3rem;
            border-radius: 10px;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.1);
        }
        .access-denied-icon {
            font-size: 4rem;
            color: #dc3545;
            margin-bottom: 1rem;
        }
        .access-denied-title {
            color: #dc3545;
            font-size: 2rem;
            font-weight: 600;
            margin-bottom: 1rem;
        }
        .access-denied-message {
            color: #6c757d;
            margin-bottom: 2rem;
            line-height: 1.6;
        }
        .user-info {
            background-color: #f8f9fa;
            padding: 1rem;
            border-radius: 5px;
            margin-bottom: 2rem;
            font-size: 0.9rem;
        }
        .btn-primary {
            background-color: #f6d757;
            border-color: #f6d757;
            color: #1a1a1a;
            font-weight: 600;
        }
        .btn-primary:hover {
            background-color: #eac843;
            border-color: #eac843;
            color: #1a1a1a;
        }
        .btn-outline-secondary {
            color: #6c757d;
            border-color: #6c757d;
        }
        .btn-outline-secondary:hover {
            background-color: #6c757d;
            border-color: #6c757d;
        }
    </style>
</head>
<body>
    <div class="access-denied-container">
        <div class="access-denied-icon">
            🚫
        </div>
        
        <h1 class="access-denied-title">Access Denied</h1>
        
        <p class="access-denied-message">
            You don't have permission to access this page. This restriction is based on your role and department within the organization.
        </p>
        
        <div class="user-info">
            <strong>Your Access Level:</strong><br>
            Role: <?= htmlspecialchars($user_role) ?><br>
            Department: <?= htmlspecialchars($user_department) ?>
        </div>
        
        <div class="d-grid gap-2">
            <button onclick="goBack()" class="btn btn-primary">
                Go Back
            </button>
            <a href="ms_dashboard.php" class="btn btn-outline-secondary">
                Return to Dashboard
            </a>
        </div>
        
        <p class="mt-3 text-muted small">
            If you believe this is an error, please contact your system administrator.
        </p>
    </div>

    <script>
        function goBack() {
            // Check if there's a previous page in history
            if (window.history.length > 1) {
                window.history.back();
            } else {
                // If no history, go to dashboard
                window.location.href = 'ms_dashboard.php';
            }
        }
    </script>
</body>
</html>