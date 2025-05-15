<?php
    include('validate_login.php');
    $page_title = "PROJECTS";

    // Database connection
    $host = 'localhost';
    $user = 'root';
    $password = '';
    $database = 'malayasol';
    $conn = new mysqli($host, $user, $password, $database);

    // Check connection
    if ($conn->connect_error) {
        die("Connection failed: " . $conn->connect_error);
    }

    // Ensure user is logged in
    if (!isset($_SESSION['user_id'])) {
        header("Location: ms_login.php");
        exit();
    }

    $user_id = $_SESSION['user_id'];

    // Get user name from joined users & employee table
    $created_by = "Unknown";
    if ($user_id) {
        $stmt = $conn->prepare("
            SELECT e.first_name, e.last_name 
            FROM users u
            LEFT JOIN employee e ON u.employee_id = e.employee_id 
            WHERE u.user_id = ?
        ");
        $stmt->bind_param("i", $user_id);
        $stmt->execute();
        $stmt->bind_result($first_name, $last_name);
        if ($stmt->fetch()) {
            $created_by = "$first_name $last_name";
        }
        $stmt->close();
    }

    // Get project_id from URL
    $project_id = isset($_GET['projectId']) ? intval($_GET['projectId']) : 0;

    $records = [];
    $project = null;

    if ($project_id > 0) {
        // Get project details
        $project_stmt = $conn->prepare("
            SELECT 
                p.project_id, p.project_name, p.project_code, p.first_name, p.last_name,
                p.company_name, p.description, p.creation_date, 
                p.created_by, p.edit_date, p.edited_by,
                p.email, p.contact
            FROM projects p
            WHERE p.project_id = ?
        ");
        $project_stmt->bind_param("i", $project_id);
        $project_stmt->execute();
        $project_result = $project_stmt->get_result();
        if ($project_result && $project_result->num_rows > 0) {
            $project = $project_result->fetch_assoc();
        }
        $project_stmt->close();

        // Get expense records for the project
        $stmt = $conn->prepare("SELECT * FROM project_expense WHERE project_id = ?");
        $stmt->bind_param("i", $project['project_id']);
        $stmt->execute();
        $result = $stmt->get_result();
        while ($row = $result->fetch_assoc()) {
            $records[] = $row;
        }
        $stmt->close();
    }

    // Fetch categories
    $categories = [];
    $cat_result = $conn->query("SELECT category_id, category_name FROM categories ORDER BY category_name");
    while ($row = $cat_result->fetch_assoc()) {
        $categories[] = $row;
    }

    // Fetch subcategories
    $subcategories = [];
    $subcat_result = $conn->query("SELECT subcategory_id, subcategory_name, category_name FROM subcategories ORDER BY subcategory_name");
    while ($row = $subcat_result->fetch_assoc()) {
        $subcategories[] = $row;
    }

    // Prepare data for analytics if needed
    $category_totals = [];
    $monthly_totals = [];

    foreach ($records as $record) {
        $cat = $record['category'];
        $category_totals[$cat] = ($category_totals[$cat] ?? 0) + $record['actual'];

        $month = date('F Y', strtotime($record['record_date']));
        $monthly_totals[$month] = ($monthly_totals[$month] ?? 0) + $record['actual'];
    }

    // Handle form submission
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['form_mode'])) {
        $edit_id = isset($_POST['edit_id']) ? intval($_POST['edit_id']) : 0;

        $category = $_POST['category'] ?? '';
        $subcategory = $_POST['subcategory'] ?? '';
        $record_date = $_POST['record_date'] ?? date('Y-m-d');
        $budget = floatval($_POST['budget'] ?? 0);
        $actual = floatval($_POST['actual'] ?? 0);
        $payee = $_POST['payee'] ?? '';
        $description = $_POST['description'] ?? '';
        $remarks = $_POST['remarks'] ?? '';
        $variance = $budget - $actual;
        $tax = round($actual * 0.12, 2); // Assuming 12% VAT

        if ($edit_id > 0) {
            // UPDATE EXISTING RECORD
            $stmt = $conn->prepare("UPDATE project_expense SET 
                category = ?, subcategory = ?, record_date = ?, budget = ?, actual = ?, payee = ?, description = ?, remarks = ?, 
                variance = ?, tax = ?, edited_by = ?, edit_date = NOW()
                WHERE record_id = ? AND project_id = ?");
            $stmt->bind_param(
                "sssddsssdsdii",
                $category, $subcategory, $record_date, $budget, $actual, $payee, $description, $remarks,
                $variance, $tax, $user_id, $edit_id, $project_id
            );
            $stmt->execute();
            $stmt->close();
        } else {
            // ADD NEW RECORD
            $stmt = $conn->prepare("INSERT INTO project_expense (
                project_id, category, subcategory, record_date, budget, actual, payee, description, remarks, 
                variance, tax, created_by, creation_date
            ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())");
            $stmt->bind_param(
                "isssddssssds",
                $project_id, $category, $subcategory, $record_date, $budget, $actual, $payee, $description, $remarks,
                $variance, $tax, $user_id
            );
            $stmt->execute();
            $stmt->close();
        }

        // Redirect to avoid form resubmission
        header("Location: ms_records.php?projectId=$project_id");
        exit();
    }

    // Delete record
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_record'])) {
        $id = $_POST['record_id'];
    
        $stmt = $conn->prepare("DELETE FROM project_expense WHERE record_id = ?");
        $stmt->bind_param("i", $id);
        $stmt->execute();
        $stmt->close();
    
        header("Location: ms_records.php?projectId=" . $project_id);
        exit();
    }    

    // Handle project deletion
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_project_id'])) {
        $delete_project_id = intval($_POST['delete_project_id']);

        // First, delete related records from project_expense
        $stmt = $conn->prepare("DELETE FROM project_expense WHERE project_id = ?");
        $stmt->bind_param("i", $delete_project_id);
        $stmt->execute();
        $stmt->close();

        // Then, delete the project itself
        $stmt = $conn->prepare("DELETE FROM projects WHERE project_id = ?");
        $stmt->bind_param("i", $delete_project_id);
        if ($stmt->execute()) {
            echo "success";
        } else {
            echo "Error deleting project: " . $stmt->error;
        }
        $stmt->close();

        // Stop further rendering
        exit();
    }

    $conn->close();
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
    <link href="css/ms_project_expense.css" rel="stylesheet">
    <link href="css/ms_sidebar.css" rel="stylesheet">
    <link href="css/ms_header.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
</head>
<body>
    <div class="sidebar" id="sidebar">
        <?php include 'sidebar.php'; ?>
    </div>
    
    <div class="content-area">
        <?php include 'header.php'; ?>

        <div class="content-body">
            <!-- Project Summary -->
            <?php if ($project): ?>
                <div class="project-summary position-relative">
                    <div class="project-options">
                        <button class="ellipsis-btn" onclick="toggleDropdown(this)">
                            <img src="icons/ellipsis-vertical.svg" alt="Options">
                        </button>
                        <div class="dropdown-menu" style="display:none;">
                            <button class="dropdown-edit">Edit</button>
                            <button class="dropdown-delete" onclick="deleteProject(<?= $project['project_id'] ?>)">Delete</button>
                        </div>
                    </div>
                    
                    <!-- Your existing project summary layout below -->
                    <div class="summary-left">
                        <div class="left-column">
                            <p><strong>PROJECT:</strong> <?= htmlspecialchars($project['project_name']) ?></p>
                            <p><strong>CODE:</strong> <?= htmlspecialchars($project['project_code']) ?></p>
                            <p><strong>CLIENT:</strong> <?= htmlspecialchars($project['first_name'] . ' ' . $project['last_name']) ?></p>
                        </div>
                        <div class="right-column">
                            <p><strong>COMPANY:</strong> <?= htmlspecialchars($project['company_name']) ?></p>
                            <p><strong>EMAIL:</strong> <?= htmlspecialchars($project['email']) ?></p>
                            <p><strong>CONTACT:</strong> <?= htmlspecialchars($project['contact']) ?></p>
                        </div>
                        </div>
                    <div class="summary-right">
                        <p><strong>CREATION DATE:</strong> <?= date('m-d-Y', strtotime($project['creation_date'])) ?></p>
                        <p><strong>DESCRIPTION:</strong> <?= htmlspecialchars($project['description']) ?></p>
                    </div>
                </div>
            <?php else: ?>
                <p class="text-danger text-center">Project not found.</p>
            <?php endif; ?>

            <!-- Add Records, Search, Filter, and Toggle Bar -->
            <div class="search-filter-bar">
                <!-- Left group: Add, Search, Filter -->
                <div class="left-controls">
                    <button onclick="openRecordModal('add')" class="add-record-btn">ADD RECORD</button>

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

                <!-- Right group: View toggle -->
                <div class="view-toggle">
                    <button class="toggle-btn active" id="view-records">RECORD</button>
                    <button class="toggle-btn" id="view-analytics">ANALYTICS</button>
                </div>
            </div>

            <!-- RECORDS VIEW -->
            <!-- Expense Records Table -->
            <div class="records-table-container">
                <table class="table">
                    <thead>
                        <tr>
                            <th> </th>
                            <th>Category</th>
                            <th>Description</th>
                            <th>Budget</th>
                            <th>Actual</th>
                            <th>Payee</th>
                            <th>Variance</th>
                            <th>Tax</th>
                            <th>Remarks</th>
                            <th>Date</th>
                            <th> </th>
                            <th> </th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (count($records) > 0): ?>
                            <?php foreach ($records as $i => $row): ?>
                                <tr>
                                    <td><?= $i + 1 ?></td>
                                    <td><?= htmlspecialchars($row['category']) ?></td>
                                    <td title="<?= htmlspecialchars($row['description']) ?>">
                                        <?= htmlspecialchars($row['description']) ?>
                                    </td>
                                    <td><?= number_format($row['budget'], 2) ?></td>
                                    <td><?= number_format($row['actual'], 2) ?></td>
                                    <td title="<?= htmlspecialchars($row['payee']) ?>">
                                        <?= htmlspecialchars($row['payee']) ?>
                                    </td>
                                    <td><?= number_format($row['variance'], 2) ?></td>
                                    <td><?= number_format($row['tax'], 2) ?></td>
                                    <td title="<?= htmlspecialchars($row['remarks']) ?>">
                                        <?= htmlspecialchars($row['remarks']) ?>
                                    </td>
                                    <td><?= date("m-d-Y", strtotime($row['record_date'])) ?></td>
                                    <td>
                                        <a href="#" class="edit-btn"
                                            data-id="<?= $row['record_id'] ?>"
                                            data-category="<?= htmlspecialchars($row['category']) ?>"
                                            data-subcategory="<?= htmlspecialchars($row['subcategory']) ?>"
                                            data-date="<?= $row['record_date'] ?>"
                                            data-budget="<?= $row['budget'] ?>"
                                            data-actual="<?= $row['actual'] ?>"
                                            data-payee="<?= htmlspecialchars($row['payee']) ?>"
                                            data-description="<?= htmlspecialchars($row['description']) ?>"
                                            data-remarks="<?= htmlspecialchars($row['remarks']) ?>"
                                            data-created_by="<?= htmlspecialchars($row['created_by']) ?>"
                                            data-creation_date="<?= $row['creation_date'] ?>"
                                            data-edited_by="<?= htmlspecialchars($row['edited_by']) ?>"
                                            data-edit_date="<?= $row['edit_date'] ?>">
                                            <img src="icons/edit.svg" width="18">
                                        </a>
                                    </td>
                                    <td>
                                        <a href="#" class="delete-btn" data-id="<?= htmlspecialchars($row['record_id']) ?>">
                                            <img src="icons/x-circle.svg" alt="Delete" width="18">
                                        </a>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr><td colspan="12" class="text-center">No records available for this project.</td></tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>

            <!--ANALYTICS VIEW -->
            
            <div class="analytics-view container py-4" style="display: none;">
                <div class="mb-4">
                    <div class="row">
                        <!-- Left: Summary (25%) -->
                        <div class="col-md-3">
                            <div class="card shadow rounded-4 p-3 mb-3">
                                <h6>Total Budget</h6>
                                <p class="fs-5 fw-bold text-black">₱<?= number_format(array_sum(array_column($records, 'budget')), 2) ?></p>
                            </div>
                            <div class="card shadow rounded-4 p-3 mb-3">
                                <h6>Total Actual</h6>
                                <p class="fs-5 fw-bold text-black">₱<?= number_format(array_sum(array_column($records, 'actual')), 2) ?></p>
                            </div>
                            <div class="card shadow rounded-4 p-3 mb-3">
                                <h6>Total Variance</h6>
                                <p class="fs-5 fw-bold text-black">₱<?= number_format(array_sum(array_column($records, 'budget')) - array_sum(array_column($records, 'actual')), 2) ?></p>
                            </div>
                            <div class="card shadow rounded-4 p-3">
                                <h6>Total Tax</h6>
                                <p class="fs-5 fw-bold text-black">₱<?= number_format(array_sum(array_column($records, 'tax')), 2) ?></p>
                            </div>
                        </div>

                        <!-- Right: Charts (65%) -->
                        <div class="col-md-9">
                            <div class="row g-4">
                                <div class="col-md-8">
                                    <div class="card shadow rounded-4 p-3 h-100">
                                        <h6 class="text-center">Weekly Budget vs Actual</h6>
                                        <canvas id="weeklyChart" height="200"></canvas>
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <div class="card shadow rounded-4 p-3 h-100">
                                        <h6 class="text-center">Category Breakdown</h6>
                                        <canvas id="doughnutChart"></canvas>
                                        <div class="mt-2 small text-center" id="categoryLegend"></div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="text-end mt-4">
                        <button id="downloadReport" class="btn btn-outline-secondary">📄 Download Report (PDF)</button>
                    </div>
                </div>
            </div>
            <?php include('edit_project_modal.php'); ?>
        </div>
    </div>

    <!-- ADD/EDIT RECORD MODAL -->
    <div id="recordModal" class="custom-modal-overlay" style="display:none;">
        <div class="custom-modal">
            <div class="modal-header">
                <h5 id="recordModalHeader">ADD RECORD</h5>
            </div>
            <form method="POST" action="ms_records.php?projectId=<?= $project_id ?>">
                <input type="hidden" name="edit_id" id="edit_id">
                <div class="modal-body">
                    <div class="input-row">
                        <div class="form-group">
                            <label for="category">Category</label>
                            <select class="form-control" id="category" name="category" required>
                                <option value="">-- Select Category --</option>
                                <?php foreach ($categories as $cat): ?>
                                    <option value="<?= htmlspecialchars($cat['category_name']) ?>" data-id="<?= htmlspecialchars($cat['category_id']) ?>">
                                        <?= htmlspecialchars($cat['category_name']) ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="form-group">
                            <label for="subcategory">Subcategory</label>
                            <select class="form-control" id="subcategory" name="subcategory" disabled>
                                <option value="">-- Select Subcategory --</option>
                                <?php foreach ($subcategories as $subcat): ?>
                                    <option value="<?= htmlspecialchars($subcat['subcategory_name']) ?>" 
                                            data-category="<?= htmlspecialchars($subcat['category_name']) ?>">
                                        <?= htmlspecialchars($subcat['subcategory_name']) ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>

                    <div class="form-group full-width">
                        <label>Description</label>
                        <input type="text" name="description" id="description" required> 
                    </div>

                    <div class="form-group full-width">
                        <div class="form-group">
                            <label>Date</label>
                            <input type="date" name="record_date" id="record_date" value="<?= date('Y-m-d') ?>">
                        </div>
                    </div>

                    <div class="input-row">
                        <div class="form-group">
                            <label>Budget</label>
                            <input type="number" name="budget" id="budget" value="0" step="0.01">
                        </div>
                        <div class="form-group">
                            <label>Amount</label>
                            <input type="number" name="actual" id="actual" step="0.01" required>
                        </div>
                    </div>

                    <div class="form-group full-width">
                        <label>Payee</label>
                        <input type="text" name="payee" id="payee" required>
                    </div>

                    <div class="form-group full-width">
                        <label>Remarks</label>
                        <textarea name="remarks" id="remarks" rows="3"></textarea>
                    </div>
                </div>

                <!-- Display metadata (added/edited info) -->
                <div id="recordMeta" class="record-meta" style="display: none;">
                    <div style="display: inline-flex; gap: 20px; width: 100%;">
                        <div class="meta-left">
                            <div>Added by: <strong id="createdBy"></strong></div>
                            <div>Edited by: <strong id="editedBy"></strong></div>
                        </div>
                        <div class="meta-right">
                            <div>Added on: <strong id="createdDate"></strong></div>
                            <div>Edited on: <strong id="editedDate"></strong></div>
                        </div>
                    </div>
                </div>

                <div class="modal-footer">
                    <button type="submit" name="form_mode" id="recordSubmitBtn" class="btn-add">ADD</button>
                    <button type="button" class="btn-cancel" onclick="closeModal()">CANCEL</button>
                </div>
            </form>
        </div>
    </div>

    <!-- Delete Confirmation Modal -->
    <div id="deleteConfirmModal" class="custom-modal-overlay" style="display:none;">
        <div class="custom-modal">
            <div class="modal-header">
                <h5>Delete Record?</h5>
            </div>
            <div class="modal-footer">
                <form method="POST" action="ms_records.php?projectId=<?= $project_id ?>">
                    <input type="hidden" name="record_id" id="delete_id">
                    <button type="submit" name="delete_record" class="btn-save-delete">YES</button>
                    <button type="button" class="btn-cancel" onclick="closeDeleteModal()">NO</button>
                </form>
            </div>
        </div>
    </div>

    <script src="js/sidebar.js"></script>
    <script src="js/header.js"></script>

    <script>//Project Summary Dropdown EDIT and DELETE
        // Toggle dropdown visibility
        function toggleDropdown(button) {
            // Find the dropdown-menu sibling of the clicked button
            const dropdown = button.nextElementSibling;
            if (!dropdown) return;
            
            // Toggle visibility
            dropdown.style.display = (dropdown.style.display === 'block') ? 'none' : 'block';
        }

        // Close dropdown if clicked outside
        window.addEventListener('click', function(e) {
            const dropdowns = document.querySelectorAll('.dropdown-menu');
            dropdowns.forEach(menu => {
                // If click is NOT inside menu or its button, hide it
                if (!menu.contains(e.target) && !menu.previousElementSibling.contains(e.target)) {
                    menu.style.display = 'none';
                }
            });
        });

        // Attach handlers for edit/delete buttons
        document.querySelectorAll('.dropdown-edit').forEach(button => {
            button.addEventListener('click', function () {
                // Show the slide-in edit panel
                document.getElementById('editProjectPanel').classList.add('open');
                // Optionally hide the dropdown
                this.parentElement.style.display = 'none';
            });
        });

        // Close panel function
        function closeEditPanel() {
            document.getElementById('editProjectPanel').classList.remove('open');
        }

        //DELETE Button
        function deleteProject(projectId) {
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
                        window.location.href = "ms_projects.php"; // redirect after deletion
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
    <script>
        //Records/Analytics View Toggles
        const btnRecords = document.getElementById('view-records');
        const btnAnalytics = document.getElementById('view-analytics');

        btnRecords.addEventListener('click', () => {
            btnRecords.classList.add('active');
            btnAnalytics.classList.remove('active');

            document.querySelector('.records-table-container').style.display = 'block';
            document.querySelector('.analytics-view').style.display = 'none';
        });

        btnAnalytics.addEventListener('click', () => {
            btnAnalytics.classList.add('active');
            btnRecords.classList.remove('active');

            document.querySelector('.records-table-container').style.display = 'none';
            document.querySelector('.analytics-view').style.display = 'block';
        });

        // Handle ?view=analytics parameter
        const urlParams = new URLSearchParams(window.location.search);
        const defaultView = urlParams.get('view');

        if (defaultView === 'analytics') {
            btnAnalytics.click(); // simulate button click
        }

        function openAddModal() {
            document.getElementById('recordModalHeader').textContent = "ADD RECORD";
            document.getElementById('recordSubmitBtn').textContent = "ADD";
            document.getElementById('recordMeta').style.display = 'none';

            // Reset form
            document.getElementById('edit_id').value = '';
            document.getElementById('category').value = '';
            document.getElementById('subcategory').value = '';
            document.getElementById('record_date').value = '<?= date('Y-m-d') ?>';
            document.getElementById('budget').value = '0';
            document.getElementById('actual').value = '';
            document.getElementById('payee').value = '';
            document.getElementById('description').value = '';
            document.getElementById('remarks').value = '';

            document.getElementById('recordModal').style.display = 'flex';
        }

        function openEditModal(btn) {
            document.getElementById('recordModalHeader').textContent = "EDIT RECORD";
            document.getElementById('recordSubmitBtn').textContent = "SAVE"; // Keep as ADD per requirement
            document.getElementById('recordMeta').style.display = 'flex';

            document.getElementById('edit_id').value = btn.dataset.id;
            document.getElementById('category').value = btn.dataset.category;
            document.getElementById('subcategory').value = btn.dataset.subcategory;
            document.getElementById('record_date').value = btn.dataset.date;
            document.getElementById('budget').value = btn.dataset.budget;
            document.getElementById('actual').value = btn.dataset.actual;
            document.getElementById('payee').value = btn.dataset.payee;
            document.getElementById('description').value = btn.dataset.description;
            document.getElementById('remarks').value = btn.dataset.remarks;

            document.getElementById('createdBy').textContent = btn.dataset.created_by || 'Unknown';
            document.getElementById('editedBy').textContent = btn.dataset.edited_by || '—';
            document.getElementById('createdDate').textContent = btn.dataset.creation_date || '—';
            document.getElementById('editedDate').textContent = btn.dataset.edit_date || '—';

            document.getElementById('recordModal').style.display = 'flex';
        }

        function closeModal() {
            document.getElementById('recordModal').style.display = 'none';
        }

        // Event delegation for edit buttons
        document.addEventListener('DOMContentLoaded', () => {
            document.querySelector('.add-record-btn').addEventListener('click', openAddModal);
            document.querySelectorAll('.edit-btn').forEach(btn => {
                btn.addEventListener('click', (e) => {
                    e.preventDefault();
                    openEditModal(btn);
                });
            });
        });

        // Close modal when clicking outside the modal box
        document.getElementById('recordModal').addEventListener('click', function(event) {
            const modalBox = document.querySelector('.custom-modal');
            if (!modalBox.contains(event.target)) {
                closeModal();
            }
        });

        // Close modal on ESC key press
        document.addEventListener('keydown', function(event) {
            if (event.key === "Escape") {
                closeModal();
            }
        });

        // Close modal function
        function closeModal() {
            document.getElementById('recordModal').style.display = 'none';
            document.getElementById('recordModalHeader').innerText = 'ADD RECORD';
            document.getElementById('recordSubmitBtn').innerText = 'ADD';
            document.getElementById('recordMeta').style.display = 'none';

            // Optional: Clear the form on close
            document.querySelector('#recordModal form').reset();
            document.getElementById('edit_id').value = '';
        }

        // DELETE
        const deleteModal = document.getElementById("deleteConfirmModal");
        
        document.querySelectorAll(".delete-btn").forEach(btn => {
            btn.addEventListener("click", e => {
                e.preventDefault();
                document.getElementById("delete_id").value = btn.dataset.id;
                deleteModal.style.display = "flex";
            });
        });

        function closeDeleteModal() {
            deleteModal.style.display = "none";
        }

        // ESC / Outside for Delete Modal
        window.addEventListener("click", function(e) {
            if (e.target === deleteModal) closeDeleteModal();
        });

        window.addEventListener("keydown", function(e) {
            if (e.key === "Escape" && deleteModal.style.display === "flex") closeDeleteModal();
        });
    </script>
    <script>
        document.addEventListener('DOMContentLoaded', function () {
            const categorySelect = document.getElementById('category');
            const subcategorySelect = document.getElementById('subcategory');

            // Store all subcategories initially
            const allSubOptions = Array.from(subcategorySelect.querySelectorAll('option[data-category]'));

            categorySelect.addEventListener('change', function () {
                const selectedCategory = categorySelect.value;

                // Reset and disable if no category selected
                if (!selectedCategory) {
                    subcategorySelect.disabled = true;
                    subcategorySelect.innerHTML = '<option value="">-- Select Subcategory --</option>';
                    return;
                }

                subcategorySelect.disabled = false;
                subcategorySelect.innerHTML = '<option value="">-- Select Subcategory --</option>';

                // Filter subcategories by category name
                allSubOptions.forEach(option => {
                    if (option.getAttribute('data-category') === selectedCategory) {
                        subcategorySelect.appendChild(option.cloneNode(true));
                    }
                });
            });
        });
    </script>
    <script>
        const weeklyCtx = document.getElementById('weeklyChart').getContext('2d');
        const doughnutCtx = document.getElementById('doughnutChart').getContext('2d');

        // Weekly Chart Data Grouping
        const weeklyBuckets = {};
        const weekRanges = {}; // To store date ranges for each week

        <?php
        $week_counter = 1;
        $week_labels = [];
        $weekly_budget_data = [];
        $weekly_actual_data = [];

        $temp_buckets = [];

        foreach ($records as $r) {
            $timestamp = strtotime($r['record_date']);
            $week_num = date('W', $timestamp);
            $year = date('o', $timestamp);

            $key = "$year-W$week_num";

            // Get start and end of the week (Mon-Sun)
            $start_of_week = date('M d', strtotime($year . "W" . $week_num));
            $end_of_week = date('M d', strtotime($year . "W" . $week_num . " +6 days"));

            $range = "$start_of_week - $end_of_week";
            $label = "Week $week_counter\n$range";

            if (!isset($temp_buckets[$key])) {
                $temp_buckets[$key] = ['budget' => 0, 'actual' => 0, 'label' => $label];
                $week_counter++;
            }

            $temp_buckets[$key]['budget'] += $r['budget'];
            $temp_buckets[$key]['actual'] += $r['actual'];
        }

        foreach ($temp_buckets as $entry) {
            $week_labels[] = $entry['label'];
            $weekly_budget_data[] = $entry['budget'];
            $weekly_actual_data[] = $entry['actual'];
        }
        ?>

        const weekLabels = <?= json_encode($week_labels) ?>;
        const weeklyBudgetData = <?= json_encode($weekly_budget_data) ?>;
        const weeklyActualData = <?= json_encode($weekly_actual_data) ?>;

        // Bar Chart
        new Chart(weeklyCtx, {
            type: 'bar',
            data: {
                labels: weekLabels,
                datasets: [
                    {
                        label: 'Budget',
                        data: weeklyBudgetData,
                        backgroundColor: 'rgba(54, 162, 235, 0.7)'
                    },
                    {
                        label: 'Actual',
                        data: weeklyActualData,
                        backgroundColor: 'rgba(255, 99, 132, 0.7)'
                    }
                ]
            },
            options: {
                responsive: true,
                scales: {
                    x: {
                        ticks: {
                            callback: function(value) {
                                return this.getLabelForValue(value).split('\n');
                            },
                            autoSkip: false,
                            maxRotation: 0,
                            minRotation: 0
                        }
                    },
                    y: {
                        beginAtZero: true,
                        ticks: {
                            callback: value => '₱' + value.toLocaleString()
                        }
                    }
                },
                plugins: {
                    tooltip: {
                        callbacks: {
                            label: ctx => `₱${ctx.raw.toLocaleString()}`
                        }
                    },
                    legend: {
                        display: true
                    }
                }
            }
        });

        // Doughnut Chart
        const categoryData = <?= json_encode($category_totals) ?>;
        const catLabels = Object.keys(categoryData);
        const catValues = Object.values(categoryData);
        const catTotal = catValues.reduce((a, b) => a + b, 0);
        const catColors = catLabels.map((_, i) => `hsl(${i * 360 / catLabels.length}, 70%, 60%)`);

        new Chart(doughnutCtx, {
            type: 'doughnut',
            data: {
                labels: catLabels,
                datasets: [{
                    data: catValues,
                    backgroundColor: catColors
                }]
            },
            options: {
                plugins: {
                    tooltip: {
                        callbacks: {
                            label: (ctx) => {
                                const amount = ctx.raw;
                                return `₱${amount.toLocaleString()}`;
                            }
                        }
                    }
                }
            }
        });

        // Category Legend with % breakdown
        const legendDiv = document.getElementById('categoryLegend');
        legendDiv.innerHTML = catLabels.map((label, i) => {
            const percent = ((catValues[i] / catTotal) * 100).toFixed(1);
            return `<div><span style="color:${catColors[i]}">●</span> ${label} - ${percent}%</div>`;
        }).join('');

        // PDF Export
        document.getElementById('downloadReport').addEventListener('click', () => {
            const { jsPDF } = window.jspdf;
            const doc = new jsPDF();
            doc.text("Project Analytics Report", 10, 10);
            doc.text("Project: <?= addslashes($project['project_name']) ?>", 10, 20);
            doc.text("Total Budget: ₱<?= number_format(array_sum(array_column($records, 'budget')), 2) ?>", 10, 30);
            doc.text("Total Actual: ₱<?= number_format(array_sum(array_column($records, 'actual')), 2) ?>", 10, 40);
            doc.text("Variance: ₱<?= number_format(array_sum(array_column($records, 'budget')) - array_sum(array_column($records, 'actual')), 2) ?>", 10, 50);
            doc.text("Tax: ₱<?= number_format(array_sum(array_column($records, 'tax')), 2) ?>", 10, 60);
            doc.save("analytics-report.pdf");
        });
    </script>
</body>
</html>

<!--
NOTES: 
    04-13-25
    TO BE WORKED ON:
    - modify project summary layout to have them match the initial design [done]
    - expand search bar later [done]
    - add functionality to "add record" button [done]

    NOT YET FUNCTIONAL:
    - add record button [done]

    04-14-25
    CHANGES:
    - added php, modal, and script for add record block

    TO BE WORKED ON:
    - fix add record form layout later [done]
    - test adding and showing records in the page [done]

    04-20-25
    CHANGES:
    - edit and delete button: added - tested and working
    - side bar: won't scroll, and animation added
    - topbar: contents will scroll under it
    - project summary: layout updated

    TO BE WORKED ON:
    - analytics view with existing data [done]
    
    04-21-25
    CHANGES:
    - analytics view: added

    TO BE WORKED ON:
    - sort by and filter 
    - analytics: tweak layout and data to be presented
    - add record: amount value is fixed to .00
        - input won't accept other decimal values e.g .98
    - download PDF button

    04-24-25
    CHANGES:
    - login page: login and session tracking added
    - user menu: added settings and logout button
    - search bar: width expanded

    NO FUNCTION:
    - settings: from user menu
-->