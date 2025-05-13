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

    // Access control
    $allowed_department = "Operations & Project Management Department";
    if ($role !== 'superadmin' && $employee_department !== $allowed_department) {
        echo "<h2>Access Denied</h2>";
        echo "<p>You do not have permission to view this page.</p>";
        exit;
    }

    // Fetch projects depending on role
    if ($role === 'superadmin') {
        $projectQuery = "SELECT * FROM projects ORDER BY creation_date DESC";
        $projectResult = $conn->query($projectQuery);
    } else {
        $projectQuery = "
            SELECT p.*
            FROM projects p
            INNER JOIN project_assignments pa ON p.project_id = pa.project_id
            WHERE pa.user_id = ?
            ORDER BY p.creation_date DESC
        ";
        $stmt = $conn->prepare($projectQuery);
        $stmt->bind_param("i", $user_id);
        $stmt->execute();
        $projectResult = $stmt->get_result();
        $stmt->close();
    }

    // ADD or EDIT modal handling
    if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST['projectName'])) {
        $mode = $_POST['mode'];
        $project_name = $_POST['projectName'];
        $project_id = strtoupper($_POST['projectId']);
        $first_name = $_POST['clientFirstName'];
        $last_name = $_POST['clientLastName'];
        $company_name = $_POST['companyName'];
        $description = $_POST['description'];
        $assignedMembers = $_POST['assignedMembers'] ?? [];

        if ($mode === "add") {
            $project_id = uniqid('PRJ_');
            $budget = 0;
            $creation_date = date("Y-m-d");

            $stmt = $conn->prepare("INSERT INTO projects (project_id, project_name, first_name, last_name, company_name, description, budget, creation_date)
                                    VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)");
            $stmt->bind_param("ssssssds", $project_id, $project_name, $first_name, $last_name, $company_name, $description, $budget, $creation_date);
        } else if ($mode === "edit" && isset($_POST['projectId'])) {
            $project_id = $_POST['projectId'];

            $stmt = $conn->prepare("UPDATE projects 
                                    SET project_name = ?, first_name = ?, last_name = ?, company_name = ?, description = ? 
                                    WHERE project_id = ?");
            $stmt->bind_param("ssssss", $project_name, $first_name, $last_name, $company_name, $description, $project_id);

            // Optionally update assigned members
            $conn->query("DELETE FROM project_assignments WHERE project_id = '$project_id'");
        }

        if ($stmt->execute()) {
            foreach ($assignedMembers as $emp_id) {
                $user_stmt = $conn->prepare("SELECT user_id FROM users WHERE employee_id = ?");
                $user_stmt->bind_param("s", $emp_id);
                $user_stmt->execute();
                $user_result = $user_stmt->get_result();

                if ($user_result && $user_result->num_rows === 1) {
                    $user_row = $user_result->fetch_assoc();
                    $assigned_user_id = $user_row['user_id'];

                    $assign_stmt = $conn->prepare("INSERT INTO project_assignments (project_id, user_id, assigned_date) VALUES (?, ?, NOW())");
                    $assign_stmt->bind_param("si", $project_id, $assigned_user_id);
                    $assign_stmt->execute();
                    $assign_stmt->close();
                }
                $user_stmt->close();
            }

            header("Location: ms_projects.php");
            exit;
        } else {
            echo "Error: " . $stmt->error;
            exit;
        }

        $stmt->close();
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
            <!-- Show "Add New Project" only for manager or superadmin -->
            <?php if ($role === 'manager' || $role === 'superadmin'): ?>
            <div class="project-card add-project" id="openAddProjectModal">
                <div id="addProjectBtn" class="add-project-content">
                    <div>
                        <img src="icons/circle-plus2.svg" alt="AddProjectIcon" width="50">
                    </div>
                    <div class="add-text">Add New Project</div>
                </div>
            </div>
            <?php endif; ?>

            <!-- Dynamically added project cards -->
            <?php while($row = $projectResult->fetch_assoc()): ?>
            <div class="project-card">
                <?php if ($role === 'manager' || $role === 'superadmin'): ?>
                    <div class="project-menu">
                        <img src="icons/ellipsis.svg" alt="Menu" class="ellipsis-icon" onclick="toggleDropdown(this)">
                        <div class="dropdown-menu">
                            <button class="dropdown-edit">Edit</button>
                            <button class="dropdown-delete">Delete</button>
                        </div>
                    </div>
                <?php endif; ?>

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
    <div id="projectModal" class="modal">
        <div class="modal-content" id="modalContent">
            <h2 class="modal-title" id="modalTitle">MODAL TITLE</h2>
            <form id="projectForm">
                <input type="hidden" name="projectId" id="projectId">
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
                        <label>&nbsp;</label>
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

                <div class="form-group">
                    <label for="assignedMembers">Assign:</label>
                    <select name="assignedMembers[]" id="assignedMembers" class="form-control" multiple required>
                        <?php
                        $empQuery = "SELECT e.employee_id, e.first_name, e.last_name 
                                    FROM employee e 
                                    INNER JOIN users u ON e.employee_id = u.employee_id 
                                    WHERE e.status = 'active'";
                        $empResult = $conn->query($empQuery);
                        while ($row = $empResult->fetch_assoc()) {
                            echo "<option value='" . $row['employee_id'] . "'>" . $row['first_name'] . " " . $row['last_name'] . "</option>";
                        }
                        ?>
                    </select>
                </div>

                <div class="modal-footer">
                    <button type="submit" id="modalSubmitBtn" class="btn-add">ADD</button>
                    <button type="button" class="btn-cancel" id="closeModal">CANCEL</button>
                </div>
            </form>
        </div>
    </div>

    <script src="js/sidebar.js"></script>
    <script src="js/header.js"></script>

    <script>
        const modal = document.getElementById("projectModal");
        const modalTitle = document.getElementById("modalTitle");
        const modalSubmitBtn = document.getElementById("modalSubmitBtn");
        const closeModal = document.getElementById("closeModal");

        function openAddProjectModal() {
            modalTitle.textContent = "NEW PROJECT";
            modalSubmitBtn.textContent = "ADD";
            modalSubmitBtn.className = "btn-add";
            document.getElementById("projectForm").reset();
            document.getElementById("projectId").value = "";
            modal.style.display = "flex";
        }

        function openEditProjectModal(projectData) {
            modalTitle.textContent = "EDIT PROJECT";
            modalSubmitBtn.textContent = "SAVE";
            modalSubmitBtn.className = "btn-edit";

            // Fill form with existing data
            document.getElementById("projectId").value = projectData.project_id;
            document.getElementById("projectName").value = projectData.project_name;
            document.getElementById("projectCode").value = projectData.project_code;
            document.getElementById("clientFirstName").value = projectData.first_name;
            document.getElementById("clientLastName").value = projectData.last_name;
            document.getElementById("companyName").value = projectData.company_name;
            document.getElementById("description").value = projectData.description;
            // Optional: Load assigned members if needed
            modal.style.display = "flex";
        }

        // Close modal
        closeModal.onclick = () => modal.style.display = "none";
        window.onclick = e => { if (e.target === modal) modal.style.display = "none"; }

        // Example hook for edit dropdown
        document.querySelectorAll(".dropdown-edit").forEach(btn => {
            btn.addEventListener("click", function () {
                const card = this.closest(".project-card");
                const data = {
                    project_id: card.dataset.projectId,
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

        //Project Card Dropdown
        function toggleDropdown(el) {
            const menu = el.nextElementSibling;
            menu.style.display = menu.style.display === 'block' ? 'none' : 'block';

            // Close other dropdowns
            document.querySelectorAll('.dropdown-menu').forEach(m => {
                if (m !== menu) m.style.display = 'none';
            });
        }

        // Optional: close on click outside
        document.addEventListener('click', function(e) {
            if (!e.target.closest('.project-menu')) {
                document.querySelectorAll('.dropdown-menu').forEach(m => m.style.display = 'none');
            }
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