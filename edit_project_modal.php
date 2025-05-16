<?php
// DB connection
$host = 'localhost';
$user = 'u188693564_adminsolar';
$password = '@Malayasolarenergies1';
$database = 'u188693564_malayasol';
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
        $rec_stmt = $conn->prepare("SELECT * FROM project_expense WHERE project_id = ?");
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
<link rel="stylesheet" href="styles.css" />
<style>
/* Basic styling for the edit panel and dropdown */
.edit-project-panel {
    position: fixed;
    top: 0; right: -400px;
    width: 400px; height: 100%;
    background: #f4f4f4;
    box-shadow: -2px 0 8px rgba(0,0,0,0.3);
    overflow-y: auto;
    transition: right 0.3s ease;
    z-index: 9999;
    padding: 20px;
}
.edit-project-panel.open {
    right: 0;
}
.panel-header h4 {
    margin: 0 0 20px 0;
}
.form-row {
    margin-bottom: 15px;
}
.form-row label {
    display: block;
    font-weight: bold;
}
.form-row input, .form-row textarea {
    width: 100%;
    padding: 6px 8px;
    box-sizing: border-box;
}
.panel-footer {
    margin-top: 20px;
    text-align: right;
}
.panel-footer button {
    margin-left: 10px;
    padding: 8px 12px;
}
.project-summary {
    border: 1px solid #ccc;
    padding: 15px;
    margin-bottom: 20px;
    position: relative;
    display: flex;
    justify-content: space-between;
}
.project-options {
    position: absolute;
    top: 10px; right: 10px;
}
.ellipsis-btn {
    background: none;
    border: none;
    cursor: pointer;
}
.dropdown-menu {
    position: absolute;
    top: 30px; right: 0;
    background: white;
    border: 1px solid #ccc;
    box-shadow: 0 2px 6px rgba(0,0,0,0.2);
    display: none;
    z-index: 10;
}
.dropdown-menu button {
    display: block;
    width: 100%;
    padding: 8px 15px;
    border: none;
    background: none;
    text-align: left;
    cursor: pointer;
}
.dropdown-menu button:hover {
    background-color: #eee;
}
</style>
</head>
<body>

<?php if ($project): ?>
<?php else: ?>
    <p class="text-danger text-center">Project not found.</p>
<?php endif; ?>

<!-- Slide-in Edit Project Panel -->
<div id="editProjectPanel" class="edit-project-panel" aria-hidden="true">
    <form method="post" action="" id="editProjectForm">
        <input type="hidden" name="project_id" value="<?= $project_id ?>">
        <div class="panel-header">
            <h4>Edit Project</h4>
        </div>
        <div class="panel-body">
            <div class="form-row">
                <label for="project_name">Project Name</label>
                <input type="text" id="project_name" name="project_name" value="<?= htmlspecialchars($project['project_name'] ?? '') ?>" required>
            </div>

            <div class="form-row">
                <label for="project_code">Project Code</label>
                <input type="text" id="project_code" name="project_code" value="<?= htmlspecialchars($project['project_code'] ?? '') ?>" required>
            </div>

            <div class="form-row">
                <label for="first_name">Client First Name</label>
                <input type="text" id="first_name" name="first_name" value="<?= htmlspecialchars($project['first_name'] ?? '') ?>" required>
            </div>

            <div class="form-row">
                <label for="last_name">Client Last Name</label>
                <input type="text" id="last_name" name="last_name" value="<?= htmlspecialchars($project['last_name'] ?? '') ?>" required>
            </div>

            <div class="form-row">
                <label for="contact">Contact</label>
                <input type="text" id="contact" name="contact" value="<?= htmlspecialchars($project['contact'] ?? '') ?>">
            </div>

            <div class="form-row">
                <label for="email">Email</label>
                <input type="email" id="email" name="email" value="<?= htmlspecialchars($project['email'] ?? '') ?>">
            </div>

            <div class="form-row">
                <label for="company_name">Company</label>
                <input type="text" id="company_name" name="company_name" value="<?= htmlspecialchars($project['company_name'] ?? '') ?>">
            </div>

            <div class="form-row">
                <label for="unit_building_no">Unit/Building No.</label>
                <input type="text" id="unit_building_no" name="unit_building_no" value="<?= htmlspecialchars($project['unit_building_no'] ?? '') ?>">
            </div>

            <div class="form-row">
                <label for="street">Street</label>
                <input type="text" id="street" name="street" value="<?= htmlspecialchars($project['Street'] ?? '') ?>">
            </div>

            <div class="form-row">
                <label for="barangay">Barangay</label>
                <input type="text" id="barangay" name="barangay" value="<?= htmlspecialchars($project['barangay'] ?? '') ?>">
            </div>

            <div class="form-row">
                <label for="city">City</label>
                <input type="text" id="city" name="city" value="<?= htmlspecialchars($project['city'] ?? '') ?>">
            </div>

            <div class="form-row">
                <label for="country">Country</label>
                <input type="text" id="country" name="country" value="<?= htmlspecialchars($project['country'] ?? '') ?>">
            </div>

            <div class="form-row">
                <label for="description">Description</label>
                <textarea id="description" name="description" rows="4"><?= htmlspecialchars($project['description'] ?? '') ?></textarea>
            </div>
        </div>
        <div class="panel-footer">
            <button type="button" onclick="closeEditPanel()">Cancel</button>
            <button type="submit" name="update_project" value="1">Update</button>
        </div>
    </form>
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

// Show edit panel
document.querySelector('.dropdown-edit')?.addEventListener('click', () => {
    openEditPanel();
    document.querySelectorAll('.dropdown-menu').forEach(menu => menu.style.display = 'none');
});

// Open edit panel
function openEditPanel() {
    document.getElementById('editProjectPanel').classList.add('open');
    document.getElementById('editProjectPanel').setAttribute('aria-hidden', 'false');
}

// Close edit panel
function closeEditPanel() {
    document.getElementById('editProjectPanel').classList.remove('open');
    document.getElementById('editProjectPanel').setAttribute('aria-hidden', 'true');
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

// Optional: close edit panel on ESC key
document.addEventListener('keydown', function(e) {
    if (e.key === 'Escape') {
        closeEditPanel();
    }
});
</script>

</body>
</html>

<?php
$conn->close();
?>