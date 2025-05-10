<?php
session_start();

// Check if user is logged in
if (!isset($_SESSION['username'])) {
    header("Location: ms_login.php");
    exit();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Atkinson+Hyperlegible&display=swap" rel="stylesheet">
    <link href="css/ms_dashboard.css" rel="stylesheet">
    <link href="css/ms_sidebar.css" rel="stylesheet">
    <link href="css/ms_header.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</head>
<body>
    <div class="sidebar" id="sidebar">
        <?php include 'sidebar.php'; ?>
    </div>
    
    <div class="content-area">
        <!-- Header Section -->
        <header class="top-bar">
            <button class="hamburger" id="toggleSidebar">☰</button>
            <h2 class="page-title">DASHBOARD</h2>
            
            <div class="user-dropdown">
                <button class="user-icon" id="userDropdownBtn">
                    <img src="icons/circle-user-round.svg" alt="UserIcon" width="30">
                </button>
                <div class="dropdown-menu" id="userDropdownMenu">
                    <a href="#" class="dropdown-item">Settings</a>
                    <a href="ms_logout.php" class="dropdown-item logout-btn">Logout</a>
                </div>
            </div>
        </header>

        <!-- Sections inside Content Pane -->
        <div class="sections">
            <div class="section">Section 1</div>
            <div class="section">Section 2</div>
            <div class="section">Section 3</div>
        </div>
    </div>

    <script src="js/sidebar.js"></script>
    <script src="js/header.js"></script>

    <script>
        //Sidebar Trigger (pullup or collapse sidebar)
        document.getElementById("toggleSidebar").addEventListener("click", function () {
            document.getElementById("sidebar").classList.toggle("collapsed");
        });

        //User Menu dropdown
        document.getElementById("userDropdownBtn").addEventListener("click", function (event) {
            event.stopPropagation(); // prevent body click from closing immediately
            const dropdown = document.getElementById("userDropdownMenu");
            dropdown.style.display = (dropdown.style.display === "block") ? "none" : "block";
        });

        // Close dropdown if clicking outside
        document.addEventListener("click", function () {
            document.getElementById("userDropdownMenu").style.display = "none";
        });
    </script>
</body>
</html>

<!-- 
NOTES:
    04-20-25
    CHANGES:
    - side bar: won't scroll, and animation added
    - topbar: contents will scroll under it 

    TO BE WORKED ON:
    - dashboard layout [not started]
    
    04-24-25
    CHANGES:
    - login page: login and session tracking added
    - user menu: added settings and logout button

    NO FUNCTION:
    - settings: from user menu
-->