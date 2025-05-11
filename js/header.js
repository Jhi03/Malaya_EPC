document.addEventListener("DOMContentLoaded", function () {
    // Sidebar toggle
    const toggleSidebarBtn = document.getElementById("toggleSidebar");
    const sidebar = document.getElementById("sidebar");
    if (toggleSidebarBtn && sidebar) {
        toggleSidebarBtn.addEventListener("click", function () {
            sidebar.classList.toggle("collapsed");
            const isCollapsed = sidebar.classList.contains("collapsed");
            localStorage.setItem("sidebarCollapsed", isCollapsed);
        });
        if (localStorage.getItem("sidebarCollapsed") === "true") {
            sidebar.classList.add("collapsed");
        }
    }

    // User dropdown toggle
    const dropdownBtn = document.getElementById("userDropdownBtn");
    const dropdownMenu = document.getElementById("userDropdownMenu");
    if (dropdownBtn && dropdownMenu) {
        dropdownBtn.addEventListener("click", function (event) {
            event.stopPropagation();
            dropdownMenu.style.display = (dropdownMenu.style.display === "block") ? "none" : "block";
        });
        dropdownMenu.addEventListener("click", function (event) {
            event.stopPropagation();
        });
        document.addEventListener("click", function () {
            dropdownMenu.style.display = "none";
        });
    }
});