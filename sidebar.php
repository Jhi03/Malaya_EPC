<?php
// sidebar.php - Includes session check if needed
// Note: Main page already has session_start() so we don't need it here
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">

    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Atkinson+Hyperlegible&display=swap" rel="stylesheet">
    <link href="css/ms_sidebar.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
</head>
<!-- Remove duplicate HTML, head elements -->
<?php
// sidebar.php - Only the sidebar content
?>
<div class="logo">
    <img src="Malaya_Logo.png" alt="Logo"> 
    <span>Malaya Solar<br>Accounting System</span>
</div>
<div class="nav-buttons d-flex flex-column gap-2">
    <a class="nav-link-item <?php echo (basename($_SERVER['PHP_SELF']) == 'ms_dashboard.php') ? 'active' : ''; ?>" href="ms_dashboard.php">
        <i class="fas fa-home me-2"></i><span>Dashboard</span>
    </a>
    <a class="nav-link-item <?php echo (basename($_SERVER['PHP_SELF']) == 'ms_projects.php') ? 'active' : ''; ?>" href="ms_projects.php">
        <i class="fas fa-chart-bar me-2"></i><span>Projects</span>
    </a>
    <a class="nav-link-item <?php echo (basename($_SERVER['PHP_SELF']) == 'ms_assets.php') ? 'active' : ''; ?>" href="ms_assets.php">
        <i class="fas fa-boxes me-2"></i><span>Assets</span>
    </a>
    <a class="nav-link-item <?php echo (basename($_SERVER['PHP_SELF']) == 'ms_expenses.php') ? 'active' : ''; ?>" href="ms_expenses.php">
        <i class="fas fa-receipt me-2"></i><span>Expenses</span>
    </a>
    <a class="nav-link-item <?php echo (basename($_SERVER['PHP_SELF']) == 'ms_workforce.php') ? 'active' : ''; ?>" href="ms_workforce.php">
        <i class="fas fa-users me-2"></i><span>Workforce</span>
    </a>
    <a class="nav-link-item <?php echo (basename($_SERVER['PHP_SELF']) == 'ms_payroll.php') ? 'active' : ''; ?>" href="ms_payroll.php">
        <i class="fas fa-wallet me-2"></i><span>Payroll</span>
    </a>
    <a class="nav-link-item <?php echo (basename($_SERVER['PHP_SELF']) == 'ms_vendors.php') ? 'active' : ''; ?>" href="ms_vendors.php">
        <i class="fas fa-store me-2"></i><span>Vendors</span>
    </a>
    <a class="nav-link-item <?php echo (basename($_SERVER['PHP_SELF']) == 'ms_reports.php') ? 'active' : ''; ?>" href="ms_reports.php">
        <i class="fas fa-file-alt me-2"></i><span>Reports</span>
    </a>
</div>
<script src="js/sidebar.js"></script>
    <script src="js/header.js"></script>
    <script> // Sidebar Toggle Script
 document.getElementById("toggleSidebar").addEventListener("click", function () {
    document.getElementById("sidebar").classList.toggle("collapsed");
    
    // Optional: Save sidebar state to localStorage
    const isSidebarCollapsed = document.getElementById("sidebar").classList.contains("collapsed");
    localStorage.setItem("sidebarCollapsed", isSidebarCollapsed);
});

// Optional: Restore sidebar state on page load
document.addEventListener("DOMContentLoaded", function() {
    const isSidebarCollapsed = localStorage.getItem("sidebarCollapsed") === "true";
    if (isSidebarCollapsed) {
        document.getElementById("sidebar").classList.add("collapsed");
    }
});

// User Menu dropdown
const dropdownBtn = document.getElementById("userDropdownBtn");
const dropdownMenu = document.getElementById("userDropdownMenu");

dropdownBtn.addEventListener("click", function (event) {
    event.stopPropagation(); // Prevent immediate close
    dropdownMenu.style.display = (dropdownMenu.style.display === "block") ? "none" : "block";
});

// Prevent clicks inside the dropdown from closing it
dropdownMenu.addEventListener("click", function (event) {
    event.stopPropagation();
});

// Close dropdown if clicking outside
document.addEventListener("click", function () {
    dropdownMenu.style.display = "none";
});</script>