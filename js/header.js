
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
