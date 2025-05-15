<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    
    <!-- CSS Libraries -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Atkinson+Hyperlegible&display=swap" rel="stylesheet">
    <link href="css/ms_header.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
</head>

<!-- Header Section -->
<header class="top-bar">
    <button class="hamburger" id="toggleSidebar">
        <img src="icons/hamburger.svg" alt="Toggle Sidebar">
    </button>    
        <div class="user-dropdown">
        <button class="user-icon" id="userDropdownBtn">
            <img src="icons/header-icons/circle-user-round.svg" alt="User Icon" width="30">
        </button>
        <div class="dropdown-menu" id="userDropdownMenu">
            <div class="user-info d-flex align-items-center">
                <img src="icons/header-icons/circle-user-round.svg" alt="User Icon" width="20">
                <span><?php echo isset($_SESSION['username']) ? $_SESSION['username'] : 'User'; ?></span>
            </div>
            <div class="dropdown-divider"></div>
            <a href="ms_settings.php" class="dropdown-item d-flex align-items-center">
                <img src="icons/header-icons/settings.svg" alt="Settings Icon" width="18" class="me-2">
                Settings
            </a>
            <a href="ms_logout.php" class="dropdown-item logout-btn d-flex align-items-center">
                <img src="icons/header-icons/logout.svg" alt="Logout Icon" width="18" class="me-2">
                Logout
            </a>
        </div>
    </div>

</header>
<script src="js/sidebar.js"></script>
<script src="js/header.js"></script>