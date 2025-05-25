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
        $status = $_POST['projectStatus'] ?? 'active'; // Add status field
        $creation_date = date("Y-m-d");

        $contact = isset($_POST['contact']) ? trim($_POST['contact']) : null;
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
                status = ?,
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

            $stmt->bind_param("sssssssissssssii", 
                $project_code, $project_name, $first_name, $last_name, $company_name, $description, $status,
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
                    <div class="dropdown sort-dropdown">
                        <button class="sort-btn" type="button" id="sortDropdown">
                            <img src="icons/arrow-down-up.svg" alt="SortIcon" width="16"> Sort By
                        </button>
                        <ul class="dropdown-menu sort-menu" aria-labelledby="sortDropdown">
                            <li><a class="dropdown-item sort-option" data-sort="name-a-z" href="#">Name (A to Z)</a></li>
                            <li><a class="dropdown-item sort-option" data-sort="name-z-a" href="#">Name (Z to A)</a></li>
                            <li><a class="dropdown-item sort-option" data-sort="oldest-newest" href="#">Oldest to Newest</a></li>
                            <li><a class="dropdown-item sort-option" data-sort="newest-oldest" href="#">Newest to Oldest</a></li>
                        </ul>
                    </div>
                </div>
                
                <!-- Status Toggle Buttons -->
                <div class="status-toggle">
                    <button class="status-toggle-btn active" data-status="active">Active</button>
                    <button class="status-toggle-btn" data-status="completed">Completed</button>
                    <button class="status-toggle-btn" data-status="archived">Archived</button>
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
                        data-edited-on="<?= htmlspecialchars($row['edit_date']) ?>"
                        data-status="<?= htmlspecialchars($row['status'] ?? 'active') ?>">
                        
                        <?php 
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
                            <div class="project-code-status">
                                <p class="project-code">CODE: <?= htmlspecialchars($row['project_code']) ?></p>
                                <span class="status-indicator status-<?= htmlspecialchars($row['status'] ?? 'active') ?>">
                                    <?= strtoupper($row['status'] ?? 'active') ?>
                                </span>
                            </div>
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

    <!-- Updated Add/Edit Project Modal -->
    <div class="modal fade" id="addProjectModal" tabindex="-1" aria-labelledby="projectModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <form id="projectForm" method="POST">
                    <input type="hidden" name="form_mode" id="formMode" value="add">
                    <input type="hidden" name="project_id" id="editProjectId">
                    
                    <div class="modal-header">
                        <h5 class="modal-title" id="projectModalLabel">Add New Project</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close">&times;</button>
                    </div>
                    
                    <div class="modal-body">
                        <div class="form-section">
                            <div class="form-section-title">Project Information</div>
                            <div class="row g-3">
                                <div class="col-md-6">
                                    <label for="projectName" class="form-label">Project Name</label>
                                    <input type="text" class="form-control" id="projectName" name="projectName" placeholder="Enter project name" required>
                                </div>
                                <div class="col-md-6">
                                    <label for="projectCode" class="form-label">Project Code</label>
                                    <input type="text" class="form-control" id="projectCode" name="projectCode" placeholder="Enter project code" required>
                                </div>
                            </div>
                        </div>

                        <div class="form-section" id="statusSection" style="display: none;">
                            <div class="form-section-title">Project Status</div>
                            <div class="row g-3">
                                <div class="col-md-12">
                                    <label for="projectStatus" class="form-label">Status</label>
                                    <select class="form-control" id="projectStatus" name="projectStatus">
                                        <option value="active">Active</option>
                                        <option value="completed">Completed</option>
                                        <option value="archived">Archived</option>
                                    </select>
                                </div>
                            </div>
                        </div>
                        
                        <div class="form-section">
                            <div class="form-section-title">Client Information</div>
                            <div class="row g-3">
                                <div class="col-md-6">
                                    <label for="clientFirstName" class="form-label">First Name</label>
                                    <input type="text" class="form-control" id="clientFirstName" name="clientFirstName" placeholder="Enter first name" required>
                                </div>
                                <div class="col-md-6">
                                    <label for="clientLastName" class="form-label">Last Name</label>
                                    <input type="text" class="form-control" id="clientLastName" name="clientLastName" placeholder="Enter last name" required>
                                </div>
                                <div class="col-md-6">
                                    <label for="contact" class="form-label">Contact</label>
                                    <input type="text" class="form-control" id="contact" name="contact" placeholder="Enter 11-digit contact number" maxlength="11" pattern="[0-9]{11}" required>
                                </div>
                                <div class="col-md-6">
                                    <label for="email" class="form-label">Email</label>
                                    <input type="email" class="form-control" id="email" name="email" placeholder="Enter email address" required>
                                </div>
                                <div class="col-md-12">
                                    <label for="companyName" class="form-label">Company</label>
                                    <input type="text" class="form-control" id="companyName" name="companyName" placeholder="Enter company name" required>
                                </div>
                            </div>
                        </div>
                        
                        <div class="form-section" id="addressSection" style="display: none;">
                            <div class="form-section-title">Address</div>
                            <div class="row g-3">
                                <div class="col-md-6">
                                    <label for="unit" class="form-label">Unit/Building No.</label>
                                    <input type="text" class="form-control" id="unit" name="unit" placeholder="Enter unit or building number">
                                </div>
                                <div class="col-md-6">
                                    <label for="street" class="form-label">Street</label>
                                    <input type="text" class="form-control" id="street" name="street" placeholder="Enter street address">
                                </div>
                                <div class="col-md-6">
                                    <label for="barangay" class="form-label">Barangay</label>
                                    <input type="text" class="form-control" id="barangay" name="barangay" placeholder="Enter barangay">
                                </div>
                                <div class="col-md-6">
                                    <label for="city" class="form-label">City</label>
                                    <input type="text" class="form-control" id="city" name="city" placeholder="Enter city">
                                </div>
                                <div class="col-md-12">
                                    <label for="country" class="form-label">Country</label>
                                    <input type="text" class="form-control" id="country" name="country" placeholder="Enter country">
                                </div>
                            </div>
                        </div>
                        
                        <div class="form-section">
                            <div class="form-section-title">Additional Information</div>
                            <div class="row g-3">
                                <div class="col-md-12">
                                    <label for="description" class="form-label">Description</label>
                                    <textarea class="form-control" id="description" name="description" rows="4" placeholder="Enter project description" required></textarea>
                                </div>
                            </div>
                        </div>
                        
                        <div id="recordMeta" class="form-section" style="display: none;">
                            <div class="form-section-title">Record Information</div>
                            <div class="row g-3">
                                <div class="col-md-6">
                                    <label class="form-label">Created By</label>
                                    <div class="form-control-plaintext" id="createdByMeta">Unknown</div>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label">Creation Date</label>
                                    <div class="form-control-plaintext" id="createdOnMeta">Unknown</div>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label">Last Edited By</label>
                                    <div class="form-control-plaintext" id="editedByMeta">Unknown</div>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label">Last Edit Date</label>
                                    <div class="form-control-plaintext" id="editedOnMeta">Unknown</div>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" id="submitButton" class="btn btn-primary">Add Project</button>
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

        // Initialize with active projects on page load
        document.addEventListener('DOMContentLoaded', function() {
            // Status filtering variables
            let currentStatusFilter = 'active';
            
            // Status toggle functionality
            document.querySelectorAll('.status-toggle-btn').forEach(btn => {
                btn.addEventListener('click', function() {
                    console.log('Status button clicked:', this.getAttribute('data-status'));
                    
                    // Remove active class from all buttons
                    document.querySelectorAll('.status-toggle-btn').forEach(b => b.classList.remove('active'));
                    
                    // Add active class to clicked button
                    this.classList.add('active');
                    
                    // Get selected status
                    currentStatusFilter = this.getAttribute('data-status');
                    
                    // Filter projects
                    filterProjectsByStatus(currentStatusFilter);
                });
            });

            // Filter projects by status
            function filterProjectsByStatus(status) {
                const projectCards = document.querySelectorAll('.project-card:not(.add-project)');
                
                projectCards.forEach(card => {
                    const cardStatus = card.getAttribute('data-status') || 'active';
                    
                    if (cardStatus === status) {
                        card.style.display = 'block';
                    } else {
                        card.style.display = 'none';
                    }
                });
            }

            // Initialize with active projects on page load
            filterProjectsByStatus('active');

            // Search functionality
            const searchInput = document.querySelector('.search-input');
            
            if (searchInput) {
                searchInput.addEventListener('input', function() {
                    const searchTerm = this.value.toLowerCase().trim();
                    const projectCards = document.querySelectorAll('.project-card:not(.add-project)');
                    
                    projectCards.forEach(card => {
                        const projectName = card.dataset.projectName ? card.dataset.projectName.toLowerCase() : '';
                        const projectCode = card.dataset.projectCode ? card.dataset.projectCode.toLowerCase() : '';
                        const companyName = card.dataset.companyName ? card.dataset.companyName.toLowerCase() : '';
                        
                        const matches = projectName.includes(searchTerm) || 
                                    projectCode.includes(searchTerm) || 
                                    companyName.includes(searchTerm);
                        
                        if (searchTerm === '' || matches) {
                            card.style.display = 'block';
                        } else {
                            card.style.display = 'none';
                        }
                    });
                });
            }

            // Sort functionality
            const sortBtn = document.getElementById('sortDropdown');
            const sortMenu = document.querySelector('.sort-menu');
            const sortOptions = document.querySelectorAll('.sort-option');
            const projectGrid = document.querySelector('.project-grid');
            
            // Toggle sort dropdown
            if (sortBtn && sortMenu) {
                sortBtn.addEventListener('click', function(e) {
                    e.preventDefault();
                    e.stopPropagation();
                    sortMenu.classList.toggle('show');
                });
            }
            
            // Close dropdown when clicking outside
            document.addEventListener('click', function(e) {
                if (!e.target.closest('.sort-dropdown')) {
                    sortMenu?.classList.remove('show');
                }
            });
            
            // Sort options click handlers
            sortOptions.forEach(option => {
                option.addEventListener('click', function(e) {
                    e.preventDefault();
                    
                    const sortType = this.getAttribute('data-sort');
                    sortProjects(sortType);
                    
                    // Close dropdown
                    sortMenu.classList.remove('show');
                    
                    // Update button state
                    sortBtn.classList.add('active');
                });
            });
            
            // Sort function
            function sortProjects(sortType) {
                const projectCards = Array.from(document.querySelectorAll('.project-card:not(.add-project)'));
                const addProjectCard = document.querySelector('.project-card.add-project');
                
                projectCards.sort((a, b) => {
                    switch (sortType) {
                        case 'name-a-z':
                            const nameA = a.dataset.projectName || '';
                            const nameB = b.dataset.projectName || '';
                            return nameA.localeCompare(nameB);
                            
                        case 'name-z-a':
                            const nameA2 = a.dataset.projectName || '';
                            const nameB2 = b.dataset.projectName || '';
                            return nameB2.localeCompare(nameA2);
                            
                        case 'oldest-newest':
                            const dateA = new Date(a.dataset.createdOn || '');
                            const dateB = new Date(b.dataset.createdOn || '');
                            return dateA - dateB;
                            
                        case 'newest-oldest':
                            const dateA2 = new Date(a.dataset.createdOn || '');
                            const dateB2 = new Date(b.dataset.createdOn || '');
                            return dateB2 - dateA2;
                            
                        default:
                            return 0;
                    }
                });
                
                // Clear the grid
                projectGrid.innerHTML = '';
                
                // Re-add the add project card first (if it exists)
                if (addProjectCard) {
                    projectGrid.appendChild(addProjectCard);
                }
                
                // Add sorted project cards
                projectCards.forEach(card => {
                    projectGrid.appendChild(card);
                });
            }

            // Contact number validation - digits only, max 11 characters
            const contactInput = document.getElementById('contact');
            if (contactInput) {
                contactInput.addEventListener('input', function(e) {
                    let value = e.target.value.replace(/\D/g, '');
                    if (value.length > 11) {
                        value = value.slice(0, 11);
                    }
                    e.target.value = value;
                });
                
                contactInput.addEventListener('keypress', function(e) {
                    if (!/\d/.test(e.key) && !['Backspace', 'Delete', 'Tab', 'Escape', 'Enter', 'Home', 'End', 'ArrowLeft', 'ArrowRight'].includes(e.key)) {
                        e.preventDefault();
                    }
                });
            }

            // Modal functionality - FIXED variable names to match your HTML
            const modal = document.getElementById("addProjectModal");
            const modalTitle = document.getElementById("projectModalLabel"); // FIXED: was "modalTitle"
            const form = document.getElementById("projectForm");
            const submitButton = document.getElementById("submitButton");
            const formMode = document.getElementById("formMode");

            // Add Project button handler
            const addProjectBtn = document.getElementById("addProjectBtn");
            if (addProjectBtn) {
                addProjectBtn.addEventListener("click", () => {
                    console.log("Add project button clicked"); // Debug log
                    openProjectModal('add');
                });
            } else {
                console.log("Add project button not found"); // Debug log
            }

            // Function to open modal in add or edit mode
            function openProjectModal(mode, projectData = null) {
                console.log("Opening modal in mode:", mode); // Debug log
                
                if (!modal) {
                    console.error("Modal not found!");
                    return;
                }
                
                const bsModal = new bootstrap.Modal(modal);
                
                if (mode === 'add') {
                    modalTitle.textContent = "Add New Project";
                    submitButton.textContent = "Add Project";
                    submitButton.className = "btn btn-primary";
                    formMode.value = "add";
                    form.reset();
                    
                    // Hide sections for add mode
                    const addressSection = document.getElementById("addressSection");
                    const statusSection = document.getElementById("statusSection");
                    const recordMeta = document.getElementById("recordMeta");
                    
                    if (addressSection) addressSection.style.display = "none";
                    if (statusSection) statusSection.style.display = "none";
                    if (recordMeta) recordMeta.style.display = "none";
                    
                } else if (mode === 'edit' && projectData) {
                    modalTitle.textContent = "Edit Project";
                    submitButton.textContent = "Update Project";
                    submitButton.className = "btn btn-warning";
                    formMode.value = "edit";
                    
                    // Fill form with project data
                    const editProjectId = document.getElementById("editProjectId");
                    const projectName = document.getElementById("projectName");
                    const projectCode = document.getElementById("projectCode");
                    const clientFirstName = document.getElementById("clientFirstName");
                    const clientLastName = document.getElementById("clientLastName");
                    const contact = document.getElementById("contact");
                    const email = document.getElementById("email");
                    const companyName = document.getElementById("companyName");
                    const description = document.getElementById("description");
                    
                    if (editProjectId) editProjectId.value = projectData.id;
                    if (projectName) projectName.value = projectData.name;
                    if (projectCode) projectCode.value = projectData.code;
                    if (clientFirstName) clientFirstName.value = projectData.firstName;
                    if (clientLastName) clientLastName.value = projectData.lastName;
                    if (contact) contact.value = projectData.contact;
                    if (email) email.value = projectData.email;
                    if (companyName) companyName.value = projectData.company;
                    if (description) description.value = projectData.description;
                    
                    // Show address section for edit mode
                    const addressSection = document.getElementById("addressSection");
                    if (addressSection) {
                        addressSection.style.display = "block";
                        const unit = document.getElementById("unit");
                        const street = document.getElementById("street");
                        const barangay = document.getElementById("barangay");
                        const city = document.getElementById("city");
                        const country = document.getElementById("country");
                        
                        if (unit) unit.value = projectData.unit || '';
                        if (street) street.value = projectData.street || '';
                        if (barangay) barangay.value = projectData.barangay || '';
                        if (city) city.value = projectData.city || '';
                        if (country) country.value = projectData.country || '';
                    }

                    // Show and set status section
                    const statusSection = document.getElementById("statusSection");
                    const projectStatus = document.getElementById("projectStatus");
                    if (statusSection && projectStatus) {
                        statusSection.style.display = "block";
                        projectStatus.value = projectData.status || 'active';
                    }
                    
                    // Show metadata section
                    const recordMeta = document.getElementById("recordMeta");
                    if (recordMeta) {
                        recordMeta.style.display = "block";
                        const createdByMeta = document.getElementById("createdByMeta");
                        const editedByMeta = document.getElementById("editedByMeta");
                        const createdOnMeta = document.getElementById("createdOnMeta");
                        const editedOnMeta = document.getElementById("editedOnMeta");
                        
                        if (createdByMeta) createdByMeta.textContent = projectData.createdBy || 'Unknown';
                        if (editedByMeta) editedByMeta.textContent = projectData.editedBy || 'Unknown';
                        if (createdOnMeta) createdOnMeta.textContent = formatDate(projectData.createdOn) || 'Unknown';
                        if (editedOnMeta) editedOnMeta.textContent = formatDate(projectData.editedOn) || 'Unknown';
                    }
                }
                
                bsModal.show();
            }

            // Edit button handler
            window.openEditModal = function(button) {
                console.log("Edit button clicked"); // Debug log
                
                const card = button.closest(".project-card");
                if (!card) {
                    console.error("Project card not found");
                    return;
                }
                
                const projectData = {
                    id: card.dataset.projectId,
                    name: card.dataset.projectName,
                    code: card.dataset.projectCode,
                    firstName: card.dataset.firstName,
                    lastName: card.dataset.lastName,
                    company: card.dataset.companyName,
                    description: card.dataset.description,
                    contact: card.dataset.contact,
                    email: card.dataset.email,
                    unit: card.dataset.unit,
                    street: card.dataset.street,
                    barangay: card.dataset.barangay,
                    city: card.dataset.city,
                    country: card.dataset.country,
                    status: card.dataset.status,
                    createdBy: card.dataset.createdBy,
                    editedBy: card.dataset.editedBy,
                    createdOn: card.dataset.createdOn,
                    editedOn: card.dataset.editedOn
                };
                
                console.log("Project data:", projectData); // Debug log
                openProjectModal('edit', projectData);
            };

            // Helper function to format dates
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

            // Form submission
            if (form) {
                form.addEventListener("submit", function (e) {
                    e.preventDefault();
                    console.log("Form submitted"); // Debug log
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
            }

            // Keep existing dropdown and delete functionality
            window.toggleDropdown = function(event, el) {
                event.stopPropagation();
                const menu = el.nextElementSibling;
                const isOpen = menu.style.display === 'block';

                // Close all other dropdowns first
                document.querySelectorAll('.project-menu .dropdown-menu').forEach(m => {
                    if (m !== menu) {
                        m.style.display = 'none';
                    }
                });

                // Toggle current dropdown
                if (!isOpen) {
                    menu.style.display = 'block';
                } else {
                    menu.style.display = 'none';
                }
            };

            // Close edit/delete dropdowns when clicking outside or pressing ESC
            document.addEventListener('click', function(e) {
                if (!e.target.closest('.project-menu')) {
                    document.querySelectorAll('.project-menu .dropdown-menu').forEach(menu => {
                        menu.style.display = 'none';
                    });
                }
            });

            document.addEventListener('keydown', function(e) {
                if (e.key === 'Escape') {
                    document.querySelectorAll('.project-menu .dropdown-menu').forEach(menu => {
                        menu.style.display = 'none';
                    });
                }
            });

            window.deleteProject = function(button) {
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
            };
        });
</script>
</body>
</html>