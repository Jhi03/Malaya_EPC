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
        <img src="icons/sidebar-icons/dashboard.svg" alt="Dashboard Icon" class="me-2" width="24"></i><span>Dashboard</span>
    </a>
    <a class="nav-link-item <?php echo (basename($_SERVER['PHP_SELF']) == 'ms_projects.php') ? 'active' : ''; ?>" href="ms_projects.php">
        <img src="icons/sidebar-icons/projects.svg" alt="Projects Icon" class="me-2" width="24"></i><span>Projects</span>
    </a>
    <a class="nav-link-item <?php echo (basename($_SERVER['PHP_SELF']) == 'ms_assets.php') ? 'active' : ''; ?>" href="ms_assets.php">
        <img src="icons/sidebar-icons/assets.svg" alt="Assets Icon" class="me-2" width="24"></i><span>Assets</span>
    </a>
    <a class="nav-link-item <?php echo (basename($_SERVER['PHP_SELF']) == 'ms_expenses.php') ? 'active' : ''; ?>" href="ms_expenses.php">
        <img src="icons/sidebar-icons/expense.svg" alt="Expense Icon" class="me-2" width="24"></i><span>Expenses</span>
    </a>
    <a class="nav-link-item <?php echo (basename($_SERVER['PHP_SELF']) == 'ms_workforce.php') ? 'active' : ''; ?>" href="ms_workforce.php">
        <img src="icons/sidebar-icons/workforce.svg" alt="Workforce Icon" class="me-2" width="24"></i><span>Workforce</span>
    </a>
    <a class="nav-link-item <?php echo (basename($_SERVER['PHP_SELF']) == 'ms_payroll.php') ? 'active' : ''; ?>" href="ms_payroll.php">
        <img src="icons/sidebar-icons/payroll.svg" alt="Payroll Icon" class="me-2" width="24"></i><span>Payroll</span>
    </a>
    <a class="nav-link-item <?php echo (basename($_SERVER['PHP_SELF']) == 'ms_vendors.php') ? 'active' : ''; ?>" href="ms_vendors.php">
        <img src="icons/sidebar-icons/vendors.svg" alt="Vendors Icon" class="me-2" width="24"></i><span>Vendors</span>
    </a>
    <a class="nav-link-item <?php echo (basename($_SERVER['PHP_SELF']) == 'ms_reports.php') ? 'active' : ''; ?>" href="ms_reports.php">
        <img src="icons/sidebar-icons/reports.svg" alt="Reports Icon" class="me-2" width="24"></i><span>Reports</span>
    </a>
</div>

<script src="js/sidebar.js"></script>
<script src="js/header.js"></script>