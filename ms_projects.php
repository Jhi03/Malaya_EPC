<?php
    // Include validation and access control
    include('validate_login.php'); // This now includes access_control.php
    require_once 'activity_logger.php';
    
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

    $projectQuery = "
        SELECT 
            p.*,
            CONCAT(creator.first_name, ' ', creator.last_name) AS created_by_name,
            CONCAT(editor.first_name, ' ', editor.last_name) AS edited_by_name
        FROM projects p
        LEFT JOIN users creator_user ON p.created_by = creator_user.user_id
        LEFT JOIN employee creator ON creator_user.employee_id = creator.employee_id
        LEFT JOIN users editor_user ON p.edited_by = editor_user.user_id
        LEFT JOIN employee editor ON editor_user.employee_id = editor.employee_id
        WHERE p.project_id != 1
        ORDER BY p.creation_date ASC
    ";
    
    $projectResult = $conn->query($projectQuery);

    // Get user info for logging and display purposes
    $user_info = getUserAccessInfo($user_id);
    $current_user_role = $user_info['role'] ?? 'unknown';
    $current_user_department = $user_info['department'] ?? 'unknown';

    // Handle the form submission for INSERT or UPDATE
    if ($_SERVER["REQUEST_METHOD"] === "POST" && !isset($_POST['delete_project_id'])) {
        $mode = $_POST['form_mode'] ?? 'add';
        $project_id = isset($_POST['project_id']) ? intval($_POST['project_id']) : 0;
        $project_name = trim($_POST['projectName']);
        $project_code = strtoupper(trim($_POST['projectCode']));
        $first_name = trim($_POST['clientFirstName']);
        $last_name = trim($_POST['clientLastName']);
        $company_name = trim($_POST['companyName']);
        $description = trim($_POST['description']);
        $creation_date = date("Y-m-d");

        $contact = isset($_POST['contact']) ? intval($_POST['contact']) : null;
        $email = isset($_POST['email']) ? trim($_POST['email']) : null;

        $unit = $_POST['unit'] ?? null;
        $street = $_POST['street'] ?? null;
        $barangay = $_POST['barangay'] ?? null;
        $city = $_POST['city'] ?? null;
        $country = $_POST['country'] ?? null;

        if (empty($project_name) || empty($project_code) || empty($first_name) || empty($last_name) || empty($contact) || empty($email) || empty($company_name)) {
            die("All fields are required.");
        }

        if ($mode === 'edit') {
            // UPDATE existing project
            $stmt = $conn->prepare("UPDATE projects SET 
                project_code = ?, 
                project_name = ?, 
                first_name = ?, 
                last_name = ?, 
                company_name = ?, 
                description = ?, 
                contact = ?, 
                email = ?, 
                unit_building_no = ?, 
                street = ?, 
                barangay = ?, 
                city = ?, 
                country = ?, 
                edit_date = NOW(), 
                edited_by = ? 
                WHERE project_id = ?");

            logUserActivity(
                'edit', 
                'ms_project.php', 
                "edit project record"
            );

            $stmt->bind_param("sssssssssssssii", 
                $project_code, $project_name, $first_name, $last_name, $company_name, $description,
                $contact, $email, $unit, $street, $barangay, $city, $country, $user_id, $project_id);

        } else {
            $stmt = $conn->prepare("INSERT INTO projects 
                (project_code, project_name, first_name, last_name, company_name, description, contact, email, budget, creation_date, created_by)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, 0, ?, ?)");

            logUserActivity(
                'add', 
                'ms_project.php', 
                "add new project"
            );

            $stmt->bind_param("ssssssissi", 
                $project_code, $project_name, $first_name, $last_name, $company_name, $description,
                $contact, $email, $creation_date, $user_id);

        }

        if ($stmt->execute()) {
            echo "success";
        } else {
            echo "Error: " . $stmt->error;
        }

        $stmt->close();
        exit;
    }

    // DELETE project
    if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST['delete_project_id'])) {
        $project_id = intval($_POST['delete_project_id']);

        if ($project_id <= 0) {
            die("Valid project ID is required.");
        }

        $stmt = $conn->prepare("DELETE FROM projects WHERE project_id = ?");

        logUserActivity(
            'delete', 
            'ms_project.php', 
            "delete project record"
        );

        $stmt->bind_param("i", $project_id);

        if ($stmt->execute()) {
            echo "success";
        } else {
            echo "Error: " . $stmt->error;
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
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</head>
<body>
    <div class="sidebar" id="sidebar">
        <?php include 'sidebar.php'; ?>
    </div>
    
    <div class="content-area">
        <?php include 'header.php'; ?>

        <div class="content-body">
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
                <?php 
                // Check if user can add projects (exclude 'user' role)
                $can_add_projects = !hasRole('user');
                if ($can_add_projects): 
                ?>
                <div id="addProjectBtn" class="project-card add-project">
                    <div class="add-project-content">
                        <img src="icons/circle-plus.svg" alt="AddProjectIcon" width="50">
                        <div class="add-text">Add New Project</div>
                    </div>
                </div>
                <?php endif; ?>

                <!-- Dynamically added project cards -->
                <?php while($row = $projectResult->fetch_assoc()): ?>
                    <div class="project-card" 
                        data-project-id="<?= $row['project_id'] ?>" 
                        data-project-code="<?= htmlspecialchars($row['project_code']) ?>"
                        data-project-name="<?= htmlspecialchars($row['project_name']) ?>"
                        data-first-name="<?= htmlspecialchars($row['first_name']) ?>"
                        data-last-name="<?= htmlspecialchars($row['last_name']) ?>"
                        data-company-name="<?= htmlspecialchars($row['company_name']) ?>"
                        data-description="<?= htmlspecialchars($row['description']) ?>"
                        data-contact="<?= htmlspecialchars($row['contact']) ?>"
                        data-email="<?= htmlspecialchars($row['email']) ?>"
                        data-unit="<?= htmlspecialchars($row['unit_building_no']) ?>"
                        data-street="<?= htmlspecialchars($row['street']) ?>"
                        data-barangay="<?= htmlspecialchars($row['barangay']) ?>"
                        data-city="<?= htmlspecialchars($row['city']) ?>"
                        data-country="<?= htmlspecialchars($row['country']) ?>"
                        data-created-by="<?= htmlspecialchars($row['created_by_name'] ?? 'Unknown') ?>"
                        data-edited-by="<?= htmlspecialchars($row['edited_by_name'] ?? 'Unknown') ?>"
                        data-created-on="<?= htmlspecialchars($row['creation_date']) ?>"
                        data-edited-on="<?= htmlspecialchars($row['edit_date']) ?>">
                        
                        <?php 
                        // Check if user can edit/delete projects (exclude 'user' role)
                        $can_edit_projects = !hasRole('user');
                        if ($can_edit_projects): 
                        ?>
                            <div class="project-menu">
                                <img src="icons/ellipsis.svg" alt="Menu" class="ellipsis-icon" onclick="toggleDropdown(event, this)">
                                <div class="dropdown-menu">
                                    <button class="dropdown-edit" onclick="openEditModal(this)">Edit</button>
                                    <button class="dropdown-delete" onclick="deleteProject(this)">Delete</button>
                                </div>
                            </div>
                        <?php endif; ?>

                        <div class="project-info">
                            <h3 class="project-name"><?= htmlspecialchars($row['project_name']) ?></h3>
                            <p class="project-code">CODE: <?= htmlspecialchars($row['project_code']) ?></p>
                        </div>
                        <div class="project-actions">
                            <a href="ms_records.php?projectId=<?= urlencode($row['project_id']) ?>" class="btn-records">RECORDS</a>
                            <a href="ms_records.php?projectId=<?= urlencode($row['project_id']) ?>&view=analytics" class="btn-analytics">
                                <img src="icons/chart-no-axes-column.svg" alt="AnalyticsIcon" width="16">
                            </a>
                        </div>
                    </div>
                <?php endwhile; ?>
            </div>
        </div>
    </div>

    <!-- Pop-up Modal -->
    <div id="addProjectModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h4 class="modal-title" id="modalTitle">NEW PROJECT</h4>
            </div>
            <div class="modal-body">
                <form id="projectForm" method="POST">
                    <input type="hidden" name="form_mode" id="formMode" value="add">
                    <input type="hidden" name="project_id" id="editProjectId">

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
                    <!-- Contact and Email (visible in add mode) -->
                    <div id="contactEmailGroup" class="form-row">
                        <div class="form-group">
                            <label for="contact">Contact</label>
                            <input type="text" name="contact" id="contact" placeholder="Contact Number">
                        </div>
                        <div class="form-group">
                            <label for="email">Email Address</label>
                            <input type="email" name="email" id="email" placeholder="Email Address">
                        </div>
                    </div>

                    <div class="form-group">
                        <label for="companyName">Company</label>
                        <input type="text" name="companyName" id="companyName" placeholder="Company Name" required>
                    </div>
                    <!-- Address Fields (visible in edit mode) -->
                    <div id="addressGroup" class="form-row" style="display: none;">
                        <div class="form-group">
                            <label for="unit">Unit/Building No.</label>
                            <input type="text" name="unit" id="unit" placeholder="Unit or Building No.">
                        </div>
                        <div class="form-group">
                            <label for="street">Street</label>
                            <input type="text" name="street" id="street" placeholder="Street">
                        </div>
                    </div>

                    <div id="locationGroup" class="form-row" style="display: none;">
                        <div class="form-group">
                            <label for="barangay">Barangay</label>
                            <input type="text" name="barangay" id="barangay" placeholder="Barangay">
                        </div>
                        <div class="form-group">
                            <label for="city">City</label>
                            <input type="text" name="city" id="city" placeholder="City">
                        </div>
                        <div class="form-group">
                            <label for="country">Country</label>
                            <input type="text" name="country" id="country" placeholder="Country">
                        </div>
                    </div>

                    <div class="form-group">
                        <label for="description">Description</label>
                        <textarea name="description" id="description" placeholder="Project Description" required></textarea>
                    </div>
                    
                    <div id="recordMeta" class="record-meta" style="display: none;">
                        <div style="display: inline-flex; gap: 20px; width: 100%;">
                            <div class="meta-left">
                                <div>Added by: <strong id="createdByMeta">Unknown</strong></div>
                                <div>Edited by: <strong id="editedByMeta">Unknown</strong></div>
                            </div>
                            <div class="meta-right">
                                <div>Added on: <strong id="createdOnMeta">Unknown</strong></div>
                                <div>Edited on: <strong id="editedOnMeta">Unknown</strong></div>
                            </div>
                        </div>
                    </div>

                    <div class="modal-footer">
                        <button type="submit" id="submitButton" class="btn-add" style="background-color: #38b6ff;">ADD</button>
                        <button type="button" class="btn-cancel" id="closeModal">CANCEL</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script src="js/sidebar.js"></script>
    <script src="js/header.js"></script>

    <script>
        // Dynamic modal: form handling
        const modal = document.getElementById("addProjectModal");
        const modalTitle = document.getElementById("modalTitle");
        const form = document.getElementById("projectForm");
        const submitButton = document.getElementById("submitButton");
        const formMode = document.getElementById("formMode");

        const contactEmailGroup = document.getElementById("contactEmailGroup");
        const addressGroup = document.getElementById("addressGroup");
        const locationGroup = document.getElementById("locationGroup");
        const recordMeta = document.getElementById("recordMeta");

        const fields = {
            name: document.getElementById("projectName"),
            code: document.getElementById("projectCode"),
            first: document.getElementById("clientFirstName"),
            last: document.getElementById("clientLastName"),
            contact: document.getElementById("contact"),
            email: document.getElementById("email"),
            company: document.getElementById("companyName"),
            description: document.getElementById("description")
        };

        // For the "Add Project" button - keep this as-is
        const addProjectBtn = document.getElementById("addProjectBtn");
        addProjectBtn.addEventListener("click", () => {
            const modal = document.getElementById("addProjectModal");
            const modalTitle = document.getElementById("modalTitle");
            const form = document.getElementById("projectForm");
            const submitButton = document.getElementById("submitButton");
            const formMode = document.getElementById("formMode");
            
            modalTitle.textContent = "NEW PROJECT";
            submitButton.textContent = "ADD";
            submitButton.style.backgroundColor = "#38b6ff";
            formMode.value = "add";

            form.reset();
            document.getElementById("contactEmailGroup").style.display = "flex";
            document.getElementById("addressGroup").style.display = "none";
            document.getElementById("locationGroup").style.display = "none";
            document.getElementById("recordMeta").style.display = "none";

            modal.style.display = "flex";
        });
        
        // Edit button handler - use this instead of the existing edit handler
        function openEditModal(button) {
            const card = button.closest(".project-card");
            const modal = document.getElementById("addProjectModal");
            const modalTitle = document.getElementById("modalTitle");
            const submitButton = document.getElementById("submitButton");
            const formMode = document.getElementById("formMode");
            
            // Fill in basic project info
            document.getElementById("projectName").value = card.dataset.projectName || "";
            document.getElementById("projectCode").value = card.dataset.projectCode || "";
            document.getElementById("clientFirstName").value = card.dataset.firstName || "";
            document.getElementById("clientLastName").value = card.dataset.lastName || "";
            document.getElementById("companyName").value = card.dataset.companyName || "";
            document.getElementById("description").value = card.dataset.description || "";
            document.getElementById("contact").value = card.dataset.contact || "";
            document.getElementById("email").value = card.dataset.email || "";
            document.getElementById("editProjectId").value = card.dataset.projectId || "";

            // Configure modal appearance
            modalTitle.textContent = "EDIT PROJECT";
            submitButton.textContent = "SAVE";
            submitButton.style.backgroundColor = "#ff5757";
            formMode.value = "edit";

            // Show additional sections
            document.getElementById("addressGroup").style.display = "flex";
            document.getElementById("locationGroup").style.display = "flex";
            document.getElementById("recordMeta").style.display = "flex";

            // Optional address fields
            document.getElementById("unit").value = card.dataset.unit || "";
            document.getElementById("street").value = card.dataset.street || "";
            document.getElementById("barangay").value = card.dataset.barangay || "";
            document.getElementById("city").value = card.dataset.city || "";
            document.getElementById("country").value = card.dataset.country || "";

            // Update metadata fields
            document.getElementById("createdByMeta").textContent = card.dataset.createdBy || "Unknown";
            document.getElementById("editedByMeta").textContent = card.dataset.editedBy || "Unknown";
            document.getElementById("createdOnMeta").textContent = formatDate(card.dataset.createdOn) || "Unknown";
            document.getElementById("editedOnMeta").textContent = formatDate(card.dataset.editedOn) || "Unknown";

            // Display the modal
            modal.style.display = "flex";
        }

        // Helper function to format dates nicely
        function formatDate(dateString) {
            if (!dateString) return "";
            const date = new Date(dateString);
            if (isNaN(date.getTime())) return dateString;
            
            return date.toLocaleString('en-US', {
                year: 'numeric',
                month: 'short',
                day: 'numeric',
                hour: '2-digit',
                minute: '2-digit'
            });
        }

        // Close modal logic
        document.getElementById("closeModal").addEventListener("click", () => {
            modal.style.display = "none";
            form.reset();
        });

        // Close modal on outside click or ESC
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

        // Submit form via POST
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
        function deleteProject(button) {
            const card = button.closest(".project-card");
            const projectId = card.dataset.projectId;

            if (!projectId) {
                alert("Project ID not found.");
                return;
            }

            if (confirm("Are you sure you want to delete this project?")) {
                const formData = new FormData();
                formData.append("delete_project_id", projectId);

                fetch("", {
                    method: "POST",
                    body: formData
                })
                .then(response => response.text())
                .then(data => {
                    if (data.trim() === "success") {
                        location.reload();
                    } else {
                        alert("Error: " + data);
                    }
                })
                .catch(error => {
                    console.error("Delete error:", error);
                    alert("An error occurred.");
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