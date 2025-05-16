<?php
// DB connection
$host = 'localhost';
$user = 'root';
$password = '';
$database = 'malayasol';
$conn = new mysqli($host, $user, $password, $database);
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Check logged in user
if (!isset($_SESSION['user_id'])) {
    header("Location: ms_login.php");
    exit();
}
$user_id = $_SESSION['user_id'];

// Get project_id from URL or POST
$project_id = 0;
if (isset($_GET['projectId'])) {
    $project_id = intval($_GET['projectId']);
} elseif (isset($_POST['project_id'])) {
    $project_id = intval($_POST['project_id']);
}

// Handle POST updates or deletion
$response = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Delete project
    if (isset($_POST['delete_project_id'])) {
        $del_id = intval($_POST['delete_project_id']);
        $del_stmt = $conn->prepare("DELETE FROM projects WHERE project_id = ?");
        $del_stmt->bind_param("i", $del_id);
        if ($del_stmt->execute()) {
            echo "success";
        } else {
            echo "Failed to delete project.";
        }
        $del_stmt->close();
        exit;
    }

    // Update project
    if (isset($_POST['update_project']) && $project_id > 0) {
        // Sanitize and prepare update
        $project_name = $_POST['project_name'] ?? '';
        $project_code = $_POST['project_code'] ?? '';
        $first_name = $_POST['first_name'] ?? '';
        $last_name = $_POST['last_name'] ?? '';
        $contact = $_POST['contact'] ?? '';
        $email = $_POST['email'] ?? '';
        $company_name = $_POST['company_name'] ?? '';
        $unit_building_no = $_POST['unit_building_no'] ?? '';
        $street = $_POST['street'] ?? '';
        $barangay = $_POST['barangay'] ?? '';
        $city = $_POST['city'] ?? '';
        $country = $_POST['country'] ?? '';
        $description = $_POST['description'] ?? '';
        $edit_date = date('Y-m-d H:i:s');
        $edited_by = $user_id;

        $update_stmt = $conn->prepare("
            UPDATE projects SET 
                project_name = ?, project_code = ?, first_name = ?, last_name = ?, contact = ?, email = ?, company_name = ?,
                unit_building_no = ?, Street = ?, barangay = ?, city = ?, country = ?, description = ?, 
                edit_date = ?, edited_by = ?
            WHERE project_id = ?
        ");
        $update_stmt->bind_param(
            "ssssssssssssssii",
            $project_name, $project_code, $first_name, $last_name, $contact, $email, $company_name,
            $unit_building_no, $street, $barangay, $city, $country, $description,
            $edit_date, $edited_by,
            $project_id
        );
        if ($update_stmt->execute()) {
            $response = "Project updated successfully.";
        } else {
            $response = "Failed to update project.";
        }
        $update_stmt->close();
    }
}

// Fetch project and records
$project = null;
$records = [];

if ($project_id > 0) {
    $stmt = $conn->prepare("
        SELECT 
            project_id, project_name, project_code, first_name, last_name, company_name, description, 
            creation_date, created_by, edit_date, edited_by, email, contact,
            unit_building_no, Street, barangay, city, country
        FROM projects WHERE project_id = ?
    ");
    $stmt->bind_param("i", $project_id);
    $stmt->execute();
    $result = $stmt->get_result();
    if ($result && $result->num_rows > 0) {
        $project = $result->fetch_assoc();
    }
    $stmt->close();

    if ($project) {
        $rec_stmt = $conn->prepare("SELECT * FROM expense WHERE project_id = ?");
        $rec_stmt->bind_param("i", $project_id);
        $rec_stmt->execute();
        $rec_result = $rec_stmt->get_result();
        while ($row = $rec_result->fetch_assoc()) {
            $records[] = $row;
        }
        $rec_stmt->close();
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8" />
<title><?= htmlspecialchars($page_title) ?></title>
<link rel="stylesheet" href="ms_payroll.css" />
<link rel="stylesheet" href="styles.css" />
</head>
<body>

<?php if ($project): ?>
<!-- Project content would go here -->
<?php else: ?>
    <p class="text-danger text-center">Project not found.</p>
<?php endif; ?>

<!-- Edit Project Modal - Styled to match ms_payroll modals -->
<div class="modal fade" id="editProjectModal" tabindex="-1" aria-labelledby="editProjectModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <form id="editProjectForm" method="post">
                <input type="hidden" name="project_id" value="<?= $project_id ?>">
                
                <div class="modal-header">
                    <h5 class="modal-title" id="editProjectModalLabel">Edit Project</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                
                <div class="modal-body">
                    <div class="form-section">
                        <div class="form-section-title">Project Information</div>
                        <div class="row g-3">
                            <div class="col-md-6">
                                <label for="project_name" class="form-label">Project Name</label>
                                <input type="text" class="form-control" id="project_name" name="project_name" 
                                       value="<?= htmlspecialchars($project['project_name'] ?? '') ?>" required>
                            </div>
                            <div class="col-md-6">
                                <label for="project_code" class="form-label">Project Code</label>
                                <input type="text" class="form-control" id="project_code" name="project_code" 
                                       value="<?= htmlspecialchars($project['project_code'] ?? '') ?>" required>
                            </div>
                        </div>
                    </div>
                    
                    <div class="form-section">
                        <div class="form-section-title">Client Information</div>
                        <div class="row g-3">
                            <div class="col-md-6">
                                <label for="first_name" class="form-label">First Name</label>
                                <input type="text" class="form-control" id="first_name" name="first_name" 
                                       value="<?= htmlspecialchars($project['first_name'] ?? '') ?>" required>
                            </div>
                            <div class="col-md-6">
                                <label for="last_name" class="form-label">Last Name</label>
                                <input type="text" class="form-control" id="last_name" name="last_name" 
                                       value="<?= htmlspecialchars($project['last_name'] ?? '') ?>" required>
                            </div>
                            <div class="col-md-6">
                                <label for="contact" class="form-label">Contact</label>
                                <input type="text" class="form-control" id="contact" name="contact" 
                                       value="<?= htmlspecialchars($project['contact'] ?? '') ?>">
                            </div>
                            <div class="col-md-6">
                                <label for="email" class="form-label">Email</label>
                                <input type="email" class="form-control" id="email" name="email" 
                                       value="<?= htmlspecialchars($project['email'] ?? '') ?>">
                            </div>
                            <div class="col-md-12">
                                <label for="company_name" class="form-label">Company</label>
                                <input type="text" class="form-control" id="company_name" name="company_name" 
                                       value="<?= htmlspecialchars($project['company_name'] ?? '') ?>">
                            </div>
                        </div>
                    </div>
                    
                    <div class="form-section">
                        <div class="form-section-title">Address</div>
                        <div class="row g-3">
                            <div class="col-md-6">
                                <label for="unit_building_no" class="form-label">Unit/Building No.</label>
                                <input type="text" class="form-control" id="unit_building_no" name="unit_building_no" 
                                       value="<?= htmlspecialchars($project['unit_building_no'] ?? '') ?>">
                            </div>
                            <div class="col-md-6">
                                <label for="street" class="form-label">Street</label>
                                <input type="text" class="form-control" id="street" name="street" 
                                       value="<?= htmlspecialchars($project['Street'] ?? '') ?>">
                            </div>
                            <div class="col-md-6">
                                <label for="barangay" class="form-label">Barangay</label>
                                <input type="text" class="form-control" id="barangay" name="barangay" 
                                       value="<?= htmlspecialchars($project['barangay'] ?? '') ?>">
                            </div>
                            <div class="col-md-6">
                                <label for="city" class="form-label">City</label>
                                <input type="text" class="form-control" id="city" name="city" 
                                       value="<?= htmlspecialchars($project['city'] ?? '') ?>">
                            </div>
                            <div class="col-md-12">
                                <label for="country" class="form-label">Country</label>
                                <input type="text" class="form-control" id="country" name="country" 
                                       value="<?= htmlspecialchars($project['country'] ?? '') ?>">
                            </div>
                        </div>
                    </div>
                    
                    <div class="form-section">
                        <div class="form-section-title">Additional Information</div>
                        <div class="row g-3">
                            <div class="col-md-12">
                                <label for="description" class="form-label">Description</label>
                                <textarea class="form-control" id="description" name="description" rows="4"><?= htmlspecialchars($project['description'] ?? '') ?></textarea>
                            </div>
                        </div>
                    </div>
                </div>
                
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" name="update_project" value="1" class="btn btn-primary">Update</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Project options dropdown -->
<div class="project-options">
    <button class="ellipsis-btn" onclick="toggleDropdown(this)">
        <img src="assets/icons/three-dots.svg" alt="Options">
    </button>
    <div class="dropdown-menu">
        <button class="dropdown-edit" onclick="openEditModal()">Edit Project</button>
        <button class="dropdown-delete" onclick="deleteProject(<?= $project_id ?>)">Delete Project</button>
    </div>
</div>

<script>
// Toggle dropdown menu
function toggleDropdown(button) {
    const menu = button.nextElementSibling;
    if (menu.style.display === 'block') {
        menu.style.display = 'none';
    } else {
        // Close any other dropdowns open on the page first
        document.querySelectorAll('.dropdown-menu').forEach(m => m.style.display = 'none');
        menu.style.display = 'block';
    }
}

// Close dropdown if clicked outside
document.addEventListener('click', function(event) {
    if (!event.target.closest('.project-options')) {
        document.querySelectorAll('.dropdown-menu').forEach(menu => menu.style.display = 'none');
    }
});

// Show edit modal
function openEditModal() {
    const modal = new bootstrap.Modal(document.getElementById('editProjectModal'));
    modal.show();
    document.querySelectorAll('.dropdown-menu').forEach(menu => menu.style.display = 'none');
}

// Delete project with confirmation & AJAX
function deleteProject(projectId) {
    if (!confirm('Are you sure you want to delete this project?')) {
        return;
    }

    const xhr = new XMLHttpRequest();
    xhr.open('POST', 'ms_records.php', true);
    xhr.setRequestHeader('Content-Type', 'application/x-www-form-urlencoded');

    xhr.onload = function() {
        if (xhr.status === 200) {
            if (xhr.responseText.trim() === 'success') {
                alert('Project deleted successfully.');
                window.location.href = 'ms_projects.php'; // Redirect to projects list page
            } else {
                alert('Failed to delete project: ' + xhr.responseText);
            }
        } else {
            alert('Request failed. Returned status of ' + xhr.status);
        }
    };

    xhr.send('delete_project_id=' + encodeURIComponent(projectId));
}
</script>

</body>
</html>

<?php
$conn->close();
?>