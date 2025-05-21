<style>
body {
    font-family: 'Atkinson Hyperlegible', sans-serif;
    margin: 0;
    display: flex;
    height: 100vh;
    overflow: hidden;
}

/* Sidebar styles */
.sidebar {
    width: 168px; /* 210 * 0.8 = 168 */
    background-color: #333;
    color: white;
    height: 100vh;
    padding: 12px; /* 15 * 0.8 = 12 */
    display: flex;
    flex-direction: column;
    justify-content: flex-start;
    position: fixed;
    top: 0;
    left: 0;
    z-index: 1000;
    transform: translateX(0);
    transition: transform 0.3s ease-in-out, width 0.3s ease-in-out;
}

.sidebar.collapsed {
    width: 56px; /* 70 * 0.8 = 56 */
    transform: translateX(0);
    padding: 12px 4px; /* 15 * 0.8 = 12, 5 * 0.8 = 4 */
}

/* Content area shifts accordingly */
.content-area {
    margin-left: 168px; /* Match new sidebar width */
    flex: 1;
    display: flex;
    flex-direction: column;
    overflow-y: auto;
    height: 100vh;
    transition: margin-left 0.3s ease-in-out;
}

.sidebar.collapsed ~ .content-area {
    margin-left: 56px; /* Match collapsed sidebar width */
}

.logo {
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 12px; /* 12 * 0.8 = 9.6 → 10 */
    margin-bottom: 16px; /* 20 * 0.8 = 16 */
    transition: all 0.3s ease;
}

.logo span {
    font-weight: normal;
}

.logo img {
    width: 32px; /* 40 * 0.8 = 32 */
    margin-right: 8px; /* 10 * 0.8 = 8 */
    transition: all 0.3s ease;
}

.sidebar.collapsed .logo {
    justify-content: center;
}

.sidebar.collapsed .logo span {
    display: none;
}

.sidebar.collapsed .logo img {
    margin-right: 0;
}

/* Navigation styles */
.nav-buttons {
    display: flex;
    flex-direction: column;
    padding: 8px 0; /* 10 * 0.8 = 8 */
}

.nav-link-item {
    display: flex;
    align-items: center;
    padding: 10px 13px; /* 12 * 0.8 = 9.6 ≈ 10, 16 * 0.8 = 12.8 ≈ 13 */
    font-size: 13px; /* 16 * 0.8 = 12.8 ≈ 13 */
    color: white !important;
    text-decoration: none;
    border-radius: 6px; /* 8 * 0.8 = 6.4 ≈ 6 */
    transition: background-color 0.3s, padding 0.3s;
    gap: 8px; /* 10 * 0.8 = 8 */
    border-left: 3px solid transparent; /* 4 * 0.8 = 3.2 ≈ 3 */
}

.nav-link-item i {
    color: white !important;
    font-size: 14px; /* 18 * 0.8 = 14.4 ≈ 14 */
    min-width: 16px; /* 20 * 0.8 = 16 */
    text-align: center;
    transition: margin 0.3s ease;
}

.sidebar.collapsed .nav-link-item {
    padding: 10px 0;
    justify-content: center;
}

.sidebar.collapsed .nav-link-item span {
    display: none;
}

.sidebar.collapsed .nav-link-item i {
    margin: 0;
}

.nav-link-item:hover {
    background-color: rgba(255, 255, 255, 0.1);
}

.nav-link-item.active {
    background-color: rgba(255, 255, 255, 0.15);
    border-left: 3px solid #ffc107; /* updated from 4 to 3 */
}

.sidebar.collapsed .nav-link-item.active {
    border-left: none;
    border-right: 3px solid #ffc107; /* updated from 4 to 3 */
}
</style>
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
    <a class="nav-link-item <?php echo (basename($_SERVER['PHP_SELF']) == 'ms_expenses.php' || (basename($_SERVER['PHP_SELF']) == 'ms_records.php' && isset($_GET['projectId']) && $_GET['projectId'] == 1)) ? 'active' : ''; ?>" href="ms_records.php?projectId=1">
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