 // Sidebar Toggle Script
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
});