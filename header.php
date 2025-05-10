
<?php 
// header.php - Contains only the header content
// Note: Main page already has session_start() so we don't need it here
?>

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">

    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Atkinson+Hyperlegible&display=swap" rel="stylesheet">
    <link href="css/ms_header.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
</head>
<!-- Header Section -->
<header class="top-bar">
    <button class="hamburger" id="toggleSidebar">☰</button>
    <h2 class="page-title"><?php echo isset($page_title) ? $page_title : 'PAGE TITLE'; ?></h2>
    
    <div class="user-dropdown">
        <button class="user-icon" id="userDropdownBtn">
            <img src="icons/circle-user-round.svg" alt="UserIcon" width="30">
        </button>
        <div class="dropdown-menu" id="userDropdownMenu">
            <!-- Display username from session -->
            <div class="user-info">
                <i class="fas fa-user-circle"></i>
                <span><?php echo isset($_SESSION['username']) ? $_SESSION['username'] : 'User'; ?></span>
            </div>
            <div class="dropdown-divider"></div>
            
            <a href="ms_settings.php" class="dropdown-item">
                <i class="fas fa-cog me-2"></i>Settings
            </a>
            <a href="ms_logout.php" class="dropdown-item logout-btn">
                <i class="fas fa-sign-out-alt me-2"></i>Logout
            </a>
        </div>
    </div>
</header>
<script>
    document.addEventListener('DOMContentLoaded', function() {
    const dropdownBtn = document.getElementById("userDropdownBtn");
    const dropdownMenu = document.getElementById("userDropdownMenu");
    
    if (dropdownBtn && dropdownMenu) {
        // Toggle dropdown when user icon is clicked
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
        });
    }
});

document.addEventListener('DOMContentLoaded', function() {
const dropdownBtn = document.getElementById("userDropdownBtn");
const dropdownMenu = document.getElementById("userDropdownMenu");

if (dropdownBtn && dropdownMenu) {
    // Toggle dropdown when user icon is clicked
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
    });
}
});

</script>
<script src="js/sidebar.js"></script>
    <script src="js/header.js"></script>