<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    
    <!-- CSS Libraries -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Atkinson+Hyperlegible&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
</head>
<style>
.top-bar {
    position: sticky;
    top: 0;
    background-color: #333;
    z-index: 900;
    padding: 8px 17px; /* Reduced from 10px 20px */
    display: flex;
    align-items: center;
    justify-content: space-between;
    border-bottom: 2px solid #e7b92c;
}

.hamburger {
    background: none;
    border: none;
    font-size: 20px; /* Reduced from 24px */
    cursor: pointer;
    transition: transform 0.2s ease;
}

.hamburger:hover {
    transform: scale(1.1);
}

.page-title {
    font-size: 15px; /* Reduced from 18px */
    font-weight: bold;
    margin: 0;
    color: #FFF;
    letter-spacing: 0.5px;
}

/* User Menu */
.user-icon {
    background: none;
    border: none;
    cursor: pointer;
    padding: 0;
    display: flex;
    align-items: center;
    justify-content: center;
    transition: transform 0.2s ease;
    color: #FFF;
}

.user-icon:hover {
    transform: scale(1.1);
}

.user-icon img {
    width: 25px;  /* Reduced from 30px */
    height: 25px;
    color: #FFF;
    filter: brightness(0) invert(1);
}

.user-dropdown {
    position: relative;
    display: inline-block;
}

.user-info {
    padding: 7px 13px; /* Reduced from 8px 15px */
    background-color: #f8f9fa;
    font-weight: 500;
    color: #333;
    display: flex;
    align-items: center;
    gap: 4px; /* Reduced from 5px */
}

.user-info i {
    color: #ffc107;
    font-size: 15px; /* Reduced from 18px */
}

/* Dropdown container */
.dropdown-menu {
    display: none;
    position: absolute;
    right: 0;
    background-color: #ffffff;
    border-radius: 8px; /* Reduced from 10px */
    box-shadow: 0 5px 17px rgba(0, 0, 0, 0.12); /* Reduced shadow */
    min-width: 130px; /* Reduced from 150px */
    z-index: 100;
    margin-top: 4px; /* Reduced from 5px */
    overflow: hidden;
    border: 1px solid #ddd;
    font-family: 'Segoe UI', sans-serif;
    font-size: 0.75rem; /* Reduced from 0.85rem */
    text-align: center;
    padding: 3px 0;
}

/* User info at the top */
.user-info {
    padding: 8px; /* Reduced from 10px */
    background-color: #ffffff;
    font-weight: 600;
    color: #333;
    display: flex;
    flex-direction: column;
    align-items: center;
    gap: 2px;
    font-size: 0.75rem; /* Reduced from 0.85rem */
}

.user-info i {
    color: #e7b92c;
    font-size: 15px; /* Reduced from 18px */
}

/* Menu items */
.dropdown-item {
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 5px; /* Reduced from 6px */
    padding: 7px 10px; /* Reduced from 8px 12px */
    color: #333;
    text-decoration: none;
    transition: background-color 0.2s ease, color 0.2s ease;
    font-size: 0.75rem; /* Reduced from 0.85rem */
}

/* Hover state */
.dropdown-item:hover {
    background-color: #fdf7e3;
    color: #000;
}

/* Logout section */
.logout-btn {
    color: #d63031;
    font-weight: 600;
    margin-top: 3px; /* Reduced from 4px */
}

.logout-btn:hover {
    background-color: #ffe5e5;
    color: #b71c1c;
}

/* Responsive adjustments */
@media (max-width: 768px) {
    .page-title {
        font-size: 14px; /* Reduced from 16px */
    }
}
</style>
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