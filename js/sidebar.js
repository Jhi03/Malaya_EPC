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