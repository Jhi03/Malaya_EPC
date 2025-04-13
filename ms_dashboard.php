<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Atkinson+Hyperlegible&display=swap" rel="stylesheet">
    <link href="ms_dashboard.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</head>
<body>
    <div class="sidebar" id="sidebar">
        <div class="logo">
            <img src="Malaya_Logo.png" alt="Logo"> Malaya Sol <br>Accounting System
        </div>
        <div class="nav-buttons">
            <a class="active" href="ms_dashboard.php"><button>Dashboard</button></a>
            <a href="ms_projects.php"><button>Projects <span>▼</span></button></a>
            <a href="ms_assets.html"><button>Assets</button></a>
            <a href="ms_expenses.html"><button>Expenses</button></a>
            <a href="ms_payroll.html"><button>Payroll <span>▼</span></button></a>
            <a href="ms_reports.html"><button>Reports</button></a>
        </div>
        <button class="create-btn">Create (+)</button>
    </div>    
    
    <div class="content">
        <!-- Header Section -->
        <header class="top-bar">
            <button class="hamburger" id="toggleSidebar">☰</button>
            <h2 class="page-title">DASHBOARD</h2>
            <button class="user-icon">👤</button>
        </header>

        <!-- Sections inside Content Pane -->
        <div class="sections">
            <div class="section">Section 1</div>
            <div class="section">Section 2</div>
            <div class="section">Section 3</div>
        </div>
    </div>

    <script>
        document.getElementById("toggleSidebar").addEventListener("click", function() {
            document.getElementById("sidebar").classList.toggle("d-none");
        });
    </script>
</body>
</html>
