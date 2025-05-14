<?php
    include('validate_login.php');
    $page_title = "PROJECTS";

    // Database connection
    $host = 'localhost';
    $user = 'root';
    $password = '';
    $database = 'malayasol';
    $conn = new mysqli($host, $user, $password, $database);
    if ($conn->connect_error) {
        die("Connection failed: " . $conn->connect_error);
    }

    $user_id = $_SESSION['user_id'];
    $role = $_SESSION['role'] ?? '';

    // Fetch employee department (if linked)
    $employee_department = null;
    $emp_stmt = $conn->prepare("
        SELECT e.department 
        FROM users u 
        LEFT JOIN employee e ON u.employee_id = e.employee_id 
        WHERE u.user_id = ?
    ");
    $emp_stmt->bind_param("i", $user_id);
    $emp_stmt->execute();
    $emp_stmt->bind_result($employee_department);
    $emp_stmt->fetch();
    $emp_stmt->close();

    // Access control allowed IF superadmin or the right dept
    $allowed_department = "Operations & Project Management Department";
    if ($role !== 'superadmin' && $employee_department !== $allowed_department) {
        echo "<h2>Access Denied</h2>";
        echo "<p>You do not have permission to view this page.</p>";
        exit;
    }

    // Fetch projects depending on role
    if ($role === 'superadmin') {
        $projectQuery = "SELECT * FROM projects ORDER BY creation_date ASC";
        $projectResult = $conn->query($projectQuery);
    } else {
        $projectQuery = "
            SELECT p.*
            FROM projects p
            INNER JOIN project_assignments pa ON p.project_code = pa.project_code
            WHERE pa.user_id = ?
            ORDER BY p.creation_date DESC
        ";
        $stmt = $conn->prepare($projectQuery);
        $stmt->bind_param("i", $user_id);
        $stmt->execute();
        $projectResult = $stmt->get_result();
        $stmt->close();
    }

    // No form submission
    if ($_SERVER["REQUEST_METHOD"] !== "POST") {
        $query = "SELECT * FROM projects";
        $result = $conn->query($query);
    }

    // Form submission (Insert)
    if ($_SERVER["REQUEST_METHOD"] === "POST" && !isset($_POST['delete_project_code'])) {
        $mode = $_POST['form_mode'] ?? 'add';

        $project_name = trim($_POST['projectName']);
        $project_code = strtoupper(trim($_POST['projectCode']));
        $first_name = trim($_POST['clientFirstName']);
        $last_name = trim($_POST['clientLastName']);
        $company_name = trim($_POST['companyName']);
        $description = trim($_POST['description']);
        $creation_date = date("Y-m-d");

        if (empty($project_name) || empty($project_code) || empty($first_name) || empty($last_name) || empty($company_name)) {
            die("All fields are required.");
        }

        if ($mode === 'edit') {
            // UPDATE existing project
            $stmt = $conn->prepare("UPDATE projects SET project_name=?, first_name=?, last_name=?, company_name=?, description=? WHERE project_code=?");
            $stmt->bind_param("ssssss", $project_name, $first_name, $last_name, $company_name, $description, $project_code);
        } else {
            // INSERT new project
            $stmt = $conn->prepare("INSERT INTO projects (project_code, project_name, first_name, last_name, company_name, description, budget, creation_date)
                                    VALUES (?, ?, ?, ?, ?, ?, 0, ?)");
            $stmt->bind_param("sssssss", $project_code, $project_name, $first_name, $last_name, $company_name, $description, $creation_date);
        }

        if ($stmt->execute()) {
            echo "success";
        } else {
            echo "Error: " . $stmt->error;
        }

        $stmt->close();
        exit;
    }

    // DELETE Project
    if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST['delete_project_code'])) {
        // Sanitize the delete_project_code
        $project_code = strtoupper(trim($_POST['delete_project_code']));  // Sanitize input

        // Ensure project_code is not empty before deleting
        if (empty($project_code)) {
            die("Project code is required.");
        }

        // Prepare and execute delete query
        $stmt = $conn->prepare("DELETE FROM projects WHERE project_code = ?");
        $stmt->bind_param("s", $project_code);

        if ($stmt->execute()) {
            echo "success"; // Inform frontend of successful deletion
        } else {
            echo "Error: " . $stmt->error; // Provide error details if delete fails
        }

        $stmt->close();
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
            <?php if ($role === 'manager' || $role === 'superadmin'): ?>
                <div id="addProjectBtn" class="project-card add-project">
                    <div class="add-project-content">
                        <img src="icons/circle-plus.svg" alt="AddProjectIcon" width="50">
                        <div class="add-text">Add New Project</div>
                    </div>
                </div>
            <?php endif; ?>

            <?php while($row = $projectResult->fetch_assoc()): ?>
                <div class="project-card"
                    data-project-code="<?= htmlspecialchars($row['project_code']) ?>"
                    data-project-name="<?= htmlspecialchars($row['project_name']) ?>"
                    data-first-name="<?= htmlspecialchars($row['first_name']) ?>"
                    data-last-name="<?= htmlspecialchars($row['last_name']) ?>"
                    data-company-name="<?= htmlspecialchars($row['company_name']) ?>"
                    data-description="<?= htmlspecialchars($row['description']) ?>">
                    <?php if ($role === 'manager' || $role === 'superadmin'): ?>
                        <div class="project-menu">
                            <img src="icons/ellipsis.svg" alt="Menu" class="ellipsis-icon" onclick="toggleDropdown(event, this)">
                            <div class="dropdown-menu">
                                <button class="dropdown-edit" onclick="openEditModal(this)">Edit</button>
                                <button class="dropdown-delete" onclick="deleteProject('<?= htmlspecialchars($row['project_code']) ?>')">Delete</button>
                            </div>
                        </div>
                    <?php endif; ?>

                    <div class="project-info">
                        <h3 class="project-name"><?= htmlspecialchars($row['project_name']) ?></h3>
                        <p class="project-code">CODE: <?= htmlspecialchars($row['project_code']) ?></p>
                    </div>
                    <div class="project-actions">
                        <a href="ms_records.php?projectCode=<?= urlencode($row['project_code']) ?>" class="btn-records">RECORDS</a>
                        <a href="ms_records.php?projectCode=<?= urlencode($row['project_code']) ?>&view=analytics" class="btn-analytics">
                            <img src="icons/chart-no-axes-column.svg" alt="AnalyticsIcon" width="16">
                        </a>
                    </div>
                </div>
            <?php endwhile; ?>
        </div>

        <!-- Pop-up Modal (Add/Edit Project) -->
        <div id="addProjectModal" class="modal">
            <div class="modal-content">
                <h2 class="modal-title" id="modalTitle">NEW PROJECT</h2>
                <form id="projectForm" method="POST">
                    <input type="hidden" name="form_mode" id="formMode" value="add">

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
                        <button type="submit" id="submitButton" class="btn-add" style="background-color: #38b6ff;">ADD</button>
                        <button type="button" class="btn-cancel" id="closeModal">CANCEL</button>
                    </div>
                </form>
            </div>
        </div>

    <script src="js/sidebar.js"></script>
    <script src="js/header.js"></script>

    <script>
        //Add Project Form
        const addProjectBtn = document.getElementById("addProjectBtn");
        const modal = document.getElementById("addProjectModal");
        const modalTitle = document.getElementById("modalTitle");
        const form = document.getElementById("projectForm");
        const submitButton = document.getElementById("submitButton");
        const formMode = document.getElementById("formMode");

        const fields = {
            name: document.getElementById("projectName"),
            code: document.getElementById("projectCode"),
            first: document.getElementById("clientFirstName"),
            last: document.getElementById("clientLastName"),
            company: document.getElementById("companyName"),
            description: document.getElementById("description")
        };

        // Open modal in "Add Project" mode
        addProjectBtn.addEventListener("click", () => {
            modalTitle.textContent = "NEW PROJECT";
            submitButton.textContent = "ADD";
            submitButton.style.backgroundColor = "#38b6ff";
            formMode.value = "add";

            form.reset();
            modal.style.display = "block";
        });

        // Edit button handler (delegated inside dropdown-edit)
        document.querySelectorAll(".dropdown-edit").forEach(button => {
            button.addEventListener("click", (e) => {
                const card = e.target.closest(".project-card");

                // Extract data
                fields.name.value = card.dataset.projectName || "";
                fields.code.value = card.dataset.projectCode || "";
                fields.first.value = card.dataset.firstName || "";
                fields.last.value = card.dataset.lastName || "";
                fields.company.value = card.dataset.companyName || "";
                fields.description.value = card.dataset.description || "";

                modalTitle.textContent = "EDIT PROJECT";
                submitButton.textContent = "SAVE";
                submitButton.style.backgroundColor = "#ff5757";
                formMode.value = "edit";

                modal.style.display = "block";
            });
        });

        // Close modal logic
        document.getElementById("closeModal").addEventListener("click", () => {
            modal.style.display = "none";
            form.reset();
        });

        // Optional: Close modal on outside click or ESC
        window.addEventListener("click", (event) => {
            if (event.target === modal) {
                modal.style.display = "none";
                form.reset();
            }
        });
        window.addEventListener("keydown", (event) => {
            if (event.key === "Escape") {
                modal.style.display = "none";
                form.reset();
            }
        });

        form.addEventListener("submit", function (e) {
            e.preventDefault();
            const formData = new FormData(form);

            fetch("", {
                method: "POST",
                body: formData,
            })
            .then((res) => res.text())
            .then((data) => {
                if (data.trim() === "success") {
                    location.reload();
                } else {
                    alert("Error: " + data);
                }
            });
        });

        // Example hook for edit dropdown
        document.querySelectorAll(".dropdown-edit").forEach(btn => {
            btn.addEventListener("click", function () {
                const card = this.closest(".project-card");
                const data = {
                    project_code: card.dataset.projectCode,
                    project_name: card.dataset.projectName,
                    project_code: card.dataset.projectCode,
                    first_name: card.dataset.firstName,
                    last_name: card.dataset.lastName,
                    company_name: card.dataset.companyName,
                    description: card.dataset.description
                };
                openEditProjectModal(data);
            });
        });

        // Hook for the ADD button
        document.getElementById("addProjectBtn").addEventListener("click", openAddProjectModal);

        //Project Card Dropdown [EDIT and DELETE]
       function toggleDropdown(event, el) {
            event.stopPropagation(); // Prevent outside click handler
            const menu = el.nextElementSibling;
            const isOpen = menu.style.display === 'block';

            // Close all open dropdowns
            document.querySelectorAll('.dropdown-menu').forEach(m => m.style.display = 'none');

            // Toggle current one
            if (!isOpen) {
                menu.style.display = 'block';
            }
            
            // Close dropdown when clicking outside
            document.addEventListener('click', function(e) {
                if (!e.target.closest('.project-menu')) {
                    document.querySelectorAll('.dropdown-menu').forEach(m => m.style.display = 'none');
                }
            });

            // Close dropdown on Escape key
            document.addEventListener('keydown', function(e) {
                if (e.key === 'Escape') {
                    document.querySelectorAll('.dropdown-menu').forEach(m => m.style.display = 'none');
                }
            });
        }

        //DELETION
        function deleteProject(projectCode) {
        if (confirm("Are you sure you want to delete this project?")) {
            const formData = new FormData();
            formData.append('delete_project_code', projectCode);

            fetch('', {
                method: 'POST',
                body: formData
            })
            .then(res => res.text())
            .then(data => {
                if (data.trim() === "success") {
                    // Remove the project card from the page
                    const projectCard = document.querySelector(`.project-card[data-project-code="${projectCode}"]`);
                    if (projectCard) {
                        projectCard.remove();
                    }
                    alert("Project deleted successfully!");
                } else {
                    alert("Error deleting project: " + data);
                }
            });
        }
    }
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