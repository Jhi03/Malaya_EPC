<?php
session_start();

// Check if user is logged in
if (!isset($_SESSION['username'])) {
    header("Location: ms_login.php");
    exit();
}

$page_title = "DASHBOARD";

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard</title>
    <link rel="icon" href="images/Malaya_Logo.png" type="image/png">
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
    <?php include 'header.php'; ?>
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
        document.addEventListener("DOMContentLoaded", function () {
            // Sidebar Toggle
            const toggleSidebarBtn = document.getElementById("toggleSidebar");
            const sidebar = document.getElementById("sidebar");

            if (toggleSidebarBtn && sidebar) {
                toggleSidebarBtn.addEventListener("click", function () {
                    sidebar.classList.toggle("collapsed");

                    // Optional: Save state
                    const isCollapsed = sidebar.classList.contains("collapsed");
                    localStorage.setItem("sidebarCollapsed", isCollapsed);
                });

                // Restore sidebar state
                const isCollapsed = localStorage.getItem("sidebarCollapsed") === "true";
                if (isCollapsed) {
                    sidebar.classList.add("collapsed");
                }
            }
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