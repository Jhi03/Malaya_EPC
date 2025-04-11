<!DOCTYPE html>
<!--NOTES: 
- Add new project: save projects form inputs in a database
- PHP: Filled out form will display "project card" in Projects page
- PHP: Selecting "Records" or "Anlytics" will take project id( ? | refer to database later ) to open a Records/Analytics Page 

- added href for CamSur "record" and "analytics" to create template for records and analytics page-->
<html lang="en">
    <?php
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

        // Form submission
        if ($_SERVER["REQUEST_METHOD"] === "POST") {
            // Collect and sanitize form data
            $project_name = trim($_POST['projectName']);
            $project_id = strtoupper(trim($_POST['projectCode']));
            $first_name = trim($_POST['clientFirstName']);
            $last_name = trim($_POST['clientLastName']);
            $company_name = trim($_POST['companyName']);
            $creation_date = date("Y-m-d");

            // Validate all fields
            if (empty($project_name) || empty($project_id) || empty($first_name) || empty($last_name) || empty($company_name)) {
                die("All fields are required.");
            }

            // Insert into projects table
            $stmt = $conn->prepare("INSERT INTO projects (project_id, project_name, first_name, last_name, company_name, budget, creation_date)
                                    VALUES (?, ?, ?, ?, ?, 0, ?)");
            $stmt->bind_param("ssssss", $project_id, $project_name, $first_name, $last_name, $company_name, $creation_date);

            if ($stmt->execute()) {
                // Create new table for project
                $table_name = "project_" . strtolower($project_id);
                $createTableSQL = "CREATE TABLE `$table_name` (
                    record_id INT(9) AUTO_INCREMENT PRIMARY KEY,
                    project_id VARCHAR(30),
                    category VARCHAR(30),
                    description VARCHAR(30),
                    budget INT(9),
                    actual INT(9),
                    variance INT(9),
                    tax INT(5),
                    remarks VARCHAR(50),
                    date DATE,
                    creation_date DATE NOT NULL,
                    edit_date DATE
                )";

                if ($conn->query($createTableSQL) === TRUE) {
                    echo "success";
                } else {
                    echo "Error creating project table: " . $conn->error;
                }
            } else {
                echo "Error adding project: " . $stmt->error;
            }

            $stmt->close();
            $conn->close();
        }
    ?>
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Malaya Sol Projects Layout</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Atkinson+Hyperlegible&display=swap" rel="stylesheet">
    <link href="ms_projects.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</head>
<body>
    <div class="sidebar" id="sidebar">
        <div class="logo">
            <img src="Malaya_Logo.png" alt="Logo"> Malaya Sol <br>Accounting System
        </div>
        <div class="nav-buttons">
            <a href="ms_dashboard.html"><button>Dashboard</button></a>
            <a class="active" href="ms_projects.html"><button>Projects <span>▼</span></button></a>
            <a href="ms_assets.html"><button>Assets</button></a>
            <a href="ms_expenses.html"><button>Expenses</button></a>
            <a href="ms_payroll.html"><button>Payroll <span>▼</span></button></a>
            <a href="ms_reports.html"><button>Reports</button></a>
        </div>
        <button class="create-btn">Create (+)</button>
    </div>
    
    <div class="content">
        <!-- Header Section -->
        <header class="top-bar">
            <button class="hamburger" id="toggleSidebar">☰</button>
            <h2 class="page-title">PROJECTS</h2>
            <button class="user-icon">👤</button>
        </header>

        <!-- Search and Filter Bar -->
        <div class="search-filter-bar">
            <div class="search-container">
                <input type="text" class="search-input" placeholder="SEARCH">
            </div>
            <div class="filter-options">
                <button class="sort-btn">⇅ Sort By</button>
                <button class="filter-btn">⧉ Filter</button>
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
            
            <!-- Project Card Example 1 -->
            <div class="project-card">
                <div class="project-info">
                    <h3 class="project-name">CAMSUR</h3>
                    <p class="project-code">CODE: CamsurMFS</p>
                </div>
                <div class="project-actions">
                    <a href="ms_records_template.html" class="btn-records">RECORDS</a>
                    <a href="ms_analytics_template.html" class="btn-analytics">📈</a>
                </div>
            </div>
            
            <!-- Project Card Example 2 -->
            <div class="project-card">
                <div class="project-info">
                    <h3 class="project-name">MONTENEGRO</h3>
                    <p class="project-code">CODE: MONTEG</p>
                </div>
                <div class="project-actions">
                    <button class="btn-records">RECORDS</button>
                    <button class="btn-analytics">📈</button>
                </div>
            </div>
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
                        <label for="description">Company</label>
                        <input type="text" name="Company" id="Company" placeholder="Company Name" required>
                    </div>

                    <div class="form-group">
                        <label for="description">Description</label>
                        <textarea name="description" id="description" placeholder="Project Description" required></textarea>
                    </div>

                    <div class="form-actions">
                        <button type="submit" class="btn-add">ADD</button>
                        <button type="button" class="btn-cancel" id="closeModal">CANCEL</button>
                    </div>
                </form>
            </div>
        </div>
    </div>


    <script>
        //Sidebar Trigger (pullup or collapse sidebar)
        document.getElementById("toggleSidebar").addEventListener("click", function() {
            document.getElementById("sidebar").classList.toggle("d-none");
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
                event.preventDefault(); // Prevent page refresh

                // Get form values
                const name = document.getElementById("projectName").value;
                const code = document.getElementById("projectCode").value;
                const firstName = document.getElementById("clientFirstName").value;
                const lastName = document.getElementById("clientLastName").value;
                const description = document.getElementById("description").value;

                // Create a new project card
                const projectCard = document.createElement("div");
                projectCard.classList.add("project-card");
                projectCard.innerHTML = `
                    <div class="project-info">
                        <h3 class="project-name">${name}</h3>
                        <p class="project-code">CODE: ${code}</p>
                        <p class="client-name">${firstName} ${lastName}</p>
                    </div>
                    <div class="project-actions">
                        <button class="btn-records">RECORDS</button>
                        <button class="btn-analytics">📈</button>
                    </div>
                `;

                // Add the new project card to the grid
                projectGrid.appendChild(projectCard);

                // Clear form inputs
                projectForm.reset();

                // Close the modal
                modal.style.display = "none";
            });
        });
    </script>
</body>
</html>