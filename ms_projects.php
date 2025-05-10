<?php
    session_start();

    // Check if user is logged in
    if (!isset($_SESSION['username'])) {
        header("Location: ms_login.php");
        exit();
    }

    $page_title = "PROJECTS";

    // Database connection
    $host = 'localhost';
    $user = 'root';
    $password = ''; // Change as needed
    $database = 'malayasol';
    $conn = new mysqli($host, $user, $password, $database);

    // Check connection
    if ($conn->connect_error) {
        die("Connection failed: " . $conn->connect_error);
    }

    // No form submission
    if ($_SERVER["REQUEST_METHOD"] !== "POST") {
        $query = "SELECT * FROM projects";
        $result = $conn->query($query);
    }

    // Form submission
    if ($_SERVER["REQUEST_METHOD"] === "POST") {
        // Collect and sanitize form data
        $project_name = trim($_POST['projectName']);
        $project_id = strtoupper(trim($_POST['projectCode']));
        $first_name = trim($_POST['clientFirstName']);
        $last_name = trim($_POST['clientLastName']);
        $company_name = trim($_POST['companyName']);
        $description = ($_POST['description']);
        $creation_date = date("Y-m-d");
    
        // Validate all fields
        if (empty($project_name) || empty($project_id) || empty($first_name) || empty($last_name) || empty($company_name)) {
            die("All fields are required.");
        }
    
        // Insert into projects table
        $stmt = $conn->prepare("INSERT INTO projects (project_id, project_name, first_name, last_name, company_name, description, budget, creation_date)
                                VALUES (?, ?, ?, ?, ?, ?, 0, ?)");
        $stmt->bind_param("sssssss", $project_id, $project_name, $first_name, $last_name, $company_name, $description, $creation_date);
    
        //runs the INSERT
        if ($stmt->execute()) {
            echo "success"; // Let JS know everything went well
        } else {
            echo "Error: " . $stmt->error; // Echo detailed error
        }
    
        $stmt->close();
        $conn->close();
        exit;
    }    
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Malaya Solar Energies Inc.</title>
    <link rel="icon" href="images/Malaya_Logo.png" type="image/png">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Atkinson+Hyperlegible&display=swap" rel="stylesheet">
    <link href="css/ms_projects.css" rel="stylesheet">
    <link href="css/ms_sidebar.css" rel="stylesheet">
    <link href="css/ms_header.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</head>
<body>
    <div class="sidebar" id="sidebar">
        <?php include 'sidebar.php'; ?>
    </div>
    
    <div class="content-area">
        <?php include 'header.php'; ?>

        <!-- Search and Filter Bar -->
        <div class="search-filter-bar">
            <div class="search-container">
                <input type="text" class="search-input" placeholder="SEARCH">
            </div>
            <div class="filter-options">
                <button class="sort-btn">
                    <img src="icons/arrow-down-up.svg" alt="SortIcon" width="16"> Sort By
                </button>                    
                <button class="filter-btn">
                    <img src="icons/filter.svg" alt="FilterIcon" width="16"> Filter
                </button>
            </div>
        </div>

        <!-- Project Cards Grid -->
        <div class="project-grid">
            <!-- Add New Project Card -->
            <div class="project-card add-project">
                <div class="add-project-content">
                    <div class="add-icon">+</div>
                    <div class="add-text">Add New Project</div>
                </div>
            </div>
            
            <!-- Dynamically added project cards -->
            <?php while($row = $result->fetch_assoc()): ?>
                <div class="project-card">
                    <div class="project-info">
                        <h3 class="project-name"><?= htmlspecialchars($row['project_name']) ?></h3>
                        <p class="project-code">CODE: <?= htmlspecialchars($row['project_id']) ?></p>
                    </div>
                    <div class="project-actions">
                    <a href="ms_records.php?projectCode=<?= urlencode($row['project_id']) ?>" class="btn-records">RECORDS</a>
                    <a href="ms_records.php?projectCode=<?= urlencode($row['project_id']) ?>&view=analytics" class="btn-analytics">
                        <img src="icons/chart-no-axes-column.svg" alt="AnalyticsIcon" width="16">
                    </a>
                    </div>
                </div>
            <?php endwhile; ?>
        </div>
    </div>

    <!-- Pop-up Modal -->
        <div id="addProjectModal" class="modal">
            <div class="modal-content">
                <h2 class="modal-title">NEW PROJECT</h2>
                <form id="projectForm">
                    <div class="form-row">
                        <div class="form-group">
                            <label for="projectName">Project Name</label>
                            <input type="text" name="projectName" id="projectName" placeholder="Project Name" required>
                        </div>
                        <div class="form-group">
                            <label for="projectCode">Project Code</label>
                            <input type="text" name="projectCode" id="projectCode" placeholder="Project Code" required>
                        </div>
                    </div>

                    <div class="form-row">
                        <div class="form-group">
                            <label for="clientFirstName">Client Name</label>
                            <input type="text" name="clientFirstName" id="clientFirstName" placeholder="First Name" required>
                        </div>
                        <div class="form-group">
                            <input type="text" name="clientLastName" id="clientLastName" placeholder="Last Name" required>
                        </div>
                    </div>

                    <div class="form-group">
                        <label for="companyName">Company</label>
                        <input type="text" name="companyName" id="companyName" placeholder="Company Name" required>
                    </div>

                    <div class="form-group">
                        <label for="description">Description</label>
                        <textarea name="description" id="description" placeholder="Project Description" required></textarea>
                    </div>

                    <div class="modal-footer">
                        <button type="submit" class="btn-add">ADD</button>
                        <button type="button" class="btn-cancel" id="closeModal">CANCEL</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script src="js/sidebar.js"></script>
    <script src="js/header.js"></script>

    <script>
        document.addEventListener("DOMContentLoaded", function () {
            // Sidebar Toggle
            const toggleSidebarBtn = document.getElementById("toggleSidebar");
            const sidebar = document.getElementById("sidebar");

            if (toggleSidebarBtn && sidebar) {
                toggleSidebarBtn.addEventListener("click", function () {
                    sidebar.classList.toggle("collapsed");

                    // Optional: Save state
                    const isCollapsed = sidebar.classList.contains("collapsed");
                    localStorage.setItem("sidebarCollapsed", isCollapsed);
                });

                // Restore sidebar state
                const isCollapsed = localStorage.getItem("sidebarCollapsed") === "true";
                if (isCollapsed) {
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

        //Add Project Form
        document.addEventListener("DOMContentLoaded", function () {
            const modal = document.getElementById("addProjectModal");
            const addProjectBtn = document.querySelector(".add-project");
            const closeModalBtn = document.getElementById("closeModal");
            const projectForm = document.getElementById("projectForm");
            const projectGrid = document.querySelector(".project-grid");

            // Open modal
            addProjectBtn.addEventListener("click", () => {
                modal.style.display = "flex";
            });

            // Close modal
            closeModalBtn.addEventListener("click", () => {
                modal.style.display = "none";
            });

            // Handle form submission
            projectForm.addEventListener("submit", function (event) {
                event.preventDefault();

                const formData = new FormData(projectForm);

                fetch('', {
                    method: 'POST',
                    body: formData
                })
                .then(res => res.text())
                .then(data => {
                    if (data.trim() === "success") {
                        const name = formData.get("projectName");
                        const code = formData.get("projectCode").toUpperCase();

                        const projectCard = document.createElement("div");
                        projectCard.classList.add("project-card");
                        projectCard.innerHTML = `
                            <div class="project-info">
                                <h3 class="project-name">${name}</h3>
                                <p class="project-code">CODE: ${code}</p>
                            </div>
                            <div class="project-actions">
                                <a href="ms_records.php?projectCode=${code}" class="btn-records">RECORDS</a>
                                <a href="ms_records.php?projectCode=${code}&view=analytics" class="btn-analytics">
                                    <img src="icons/chart-no-axes-column.svg" alt="AnalyticsIcon" width="16">
                                </a>
                            </div>
                        `;

                        projectGrid.appendChild(projectCard);
                        projectForm.reset();
                        modal.style.display = "none";
                    } else {
                        alert(data); // Show error message
                    }
                });
            });

            //Closing methods aside from Cancel Button
                //  Close on outside click
                window.addEventListener("click", function(event) {
                    if (event.target === modal) {
                        modal.style.display = "none";
                    }
                });

                //  Close modal on Escape key
                window.addEventListener("keydown", function(event) {
                    if (event.key === "Escape" && modal.style.display === "flex") {
                        modal.style.display = "none";
                    }
                });
        });
    </script>
</body>
</html>

<!-- 
NOTES: 
    04-05-25
    - Add new project: save projects form inputs in a database [done]
    - PHP: Filled out form will display "project card" in Projects page [done]
    - PHP: Selecting "Records" or "Anlytics" will take project id( ? | refer to database later ) to open a Records/Analytics Page [done]

    - added href for CamSur "record" and "analytics" to create template for records and analytics page [removed]

    04-13-25
    CHANGES:
    - Added PHP connection and commands to add form input from "Add New Project"
    - project id, project name, client name, company, description are added taken as input
    - backend adds budget as zero (0) by default and creation date for documentation
    - form inputs are "trimmed" for white space
    -   consider removing trim for project name ; only project code is important to be trimmed to avoid query issues (filtering) later
    - form will not continue with creation if fields are not filled out 
    - project cards are updated to dynamically display existing projects

    04-20-25
    CHANGES:
    - Added script for the modal to close when thru: button, outside click or "esc" key 
    - side bar: won't scroll, and animation added
    - topbar: contents will scroll under it

    TO BE WORKED ON:
    - project card: analytics button not yet working [done]
        - redirect user to analytics view when analytics button is selected
    - analytics button: update its icon to svg file [done]

    NOT YET FUNCTIONAL:
    - search bar, sort by and filter   
    - analytics button in project card
    - user profile button [done]

    04-24-25
    CHANGES:
    - login page: login and session tracking added
    - user menu: added settings and logout button

    TO BE WORKED ON:
    - project card: add meatball for edit and deletion of project

    NO FUNCTION:
    - settings: from user menu
-->