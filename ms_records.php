<?php
    $host = 'localhost';
    $user = 'root';
    $password = '';
    $database = 'malayasol';
    $conn = new mysqli($host, $user, $password, $database);

    if ($conn->connect_error) {
        die("Connection failed: " . $conn->connect_error);
    }

    $project_code = isset($_GET['projectCode']) ? $_GET['projectCode'] : '';

    $records = [];
    $project = null;

    if ($project_code !== '') {
        // Get project info
        $project_result = $conn->query("SELECT * FROM projects WHERE project_id = '$project_code'");
        if ($project_result && $project_result->num_rows > 0) {
            $project = $project_result->fetch_assoc();
        }

        // Get project_expenses records
        $stmt = $conn->prepare("SELECT * FROM project_expense WHERE project_id = ?");
        $stmt->bind_param("s", $project_code);
        $stmt->execute();
        $result = $stmt->get_result();
        while ($row = $result->fetch_assoc()) {
            $records[] = $row;
        }
        $stmt->close();
    }

    //Analytics Chart
    $category_totals = [];
    $monthly_totals = [];

    foreach ($records as $record) {
        // Category totals
        $cat = $record['category'];
        $category_totals[$cat] = ($category_totals[$cat] ?? 0) + $record['actual'];

        // Monthly totals
        $month = date('F Y', strtotime($record['record_date']));
        $monthly_totals[$month] = ($monthly_totals[$month] ?? 0) + $record['actual'];
    }

    //Add record
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_record'])) {
        $category = $_POST['category'];
        $record_date = $_POST['record_date'];
        $budget = $_POST['budget'];
        $actual = $_POST['actual'];
        $payee = $_POST['payee'];
        $description = $_POST['description'];
        $remarks = $_POST['remarks'];
    
        $variance = $budget - $actual;
        $tax = 0;
        $creation_date = date('Y-m-d');
    
        $stmt = $conn->prepare("INSERT INTO project_expense 
            (project_id, category, description, budget, actual, payee, variance, tax, remarks, record_date, creation_date) 
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
        $stmt->bind_param("sssiisissss", 
            $project_code, $category, $description, $budget, $actual, $payee, $variance, $tax, $remarks, $record_date, $creation_date);
    
        if ($stmt->execute()) {
            // Refresh to show the new record
            header("Location: ms_records.php?projectCode=" . $project_code);
            exit();
        } else {
            echo "<script>alert('Error adding record: " . $stmt->error . "');</script>";
        }
        $stmt->close();
    }

    // Update or Delete record
    if (isset($_POST['save_edit'])) {
        $edit_id = $_POST['edit_id'];
        $category = $_POST['category'];
        $record_date = $_POST['record_date'];
        $budget = $_POST['budget'];
        $actual = $_POST['actual'];
        $payee = $_POST['payee'];
        $description = $_POST['description'];
        $remarks = $_POST['remarks'];
    
        // Calculate variance (budget - actual)
        $variance = $budget - $actual;
    
        $stmt = $conn->prepare("UPDATE project_expense SET 
            category = ?, record_date = ?, budget = ?, actual = ?, variance = ?, tax = ?, payee = ?, description = ?, remarks = ?
            WHERE record_id = ?");
    
        $stmt->bind_param("ssddddsssi", $category, $record_date, $budget, $actual, $variance, $tax, $payee, $description, $remarks, $edit_id);
    
        if ($stmt->execute()) {
            echo "<script>window.location.href = 'ms_records.php?projectCode=$project_code';</script>";
            exit();
        } else {
            echo "Error updating record: " . $conn->error;
        }
    }

    // Delete record
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_record'])) {
        $id = $_POST['record_id'];
    
        $stmt = $conn->prepare("DELETE FROM project_expense WHERE record_id = ?");
        $stmt->bind_param("i", $id);
        $stmt->execute();
        $stmt->close();
    
        header("Location: ms_records.php?projectCode=" . $project_code);
        exit();
    }    

    $conn->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Malaya Sol Projects Layout</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Atkinson+Hyperlegible&display=swap" rel="stylesheet">
    <link href="ms_project_expense.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    
    <!-- External JavaScript Libraries -->
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf/2.5.1/jspdf.umd.min.js"></script>
</head>
<body>
    <div class="sidebar" id="sidebar">
        <div class="logo">
            <img src="Malaya_Logo.png" alt="Logo"> Malaya Sol <br>Accounting System
        </div>
        <div class="nav-buttons">
            <a href="ms_dashboard.php"><button>Dashboard</button></a>
            <a class="active" href="ms_projects.php"><button>Projects <span>▼</span></button></a>
            <a href="ms_assets.html"><button>Assets</button></a>
            <a href="ms_expenses.html"><button>Expenses</button></a>
            <a href="ms_payroll.html"><button>Payroll <span>▼</span></button></a>
            <a href="ms_reports.html"><button>Reports</button></a>
        </div>
        <button class="create-btn">Create (+)</button>
    </div>
    
    <div class="content-area">
        <!-- Header Section -->
        <header class="top-bar">
            <button class="hamburger" id="toggleSidebar">
                <img src="icons/menu.svg" alt="SortIcon" width="25">
            </button>
            <h2 class="page-title">PROJECTS > Record</h2>
            <button class="user-icon">
                <img src="icons/circle-user-round.svg" alt="UserIcon" width="30">
            </button>
        </header>

        <!-- Project Summary -->
        <div class="project-summary">
            <div class="summary-left">
                <p><strong>PROJECT:</strong> <?= $project['project_name'] ?></p>
                <p><strong>CODE:</strong> <?= $project['project_id'] ?></p>
                <p><strong>CLIENT:</strong> <?= $project['first_name'] ?> <?= $project['last_name'] ?></p>
            </div>
            <div class="summary-right">
                <p><strong>CREATION DATE:</strong> <?= date('m-d-Y', strtotime($project['creation_date'])) ?></p>
                <p><strong>DESCRIPTION:</strong> <?= $project['description'] ?></p>
            </div>
        </div>

        <!-- Add Records, Search, Filter, and Toggle Bar -->
        <div class="search-filter-bar">
            <!-- Left group: Add, Search, Filter -->
            <div class="left-controls">
                <button class="add-record-btn">ADD RECORD</button>

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
        <!-- Table of records -->
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
                                <td><?= $row['category'] ?></td>
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
                                    <a href="#"  class="edit-btn"
                                        data-id="<?= $row['record_id'] ?>"
                                        data-category="<?= $row['category'] ?>"
                                        data-date="<?= $row['record_date'] ?>"
                                        data-budget="<?= $row['budget'] ?>"
                                        data-actual="<?= $row['actual'] ?>"
                                        data-payee="<?= $row['payee'] ?>"
                                        data-description="<?= $row['description'] ?>"
                                        data-remarks="<?= $row['remarks'] ?>">
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
                <h2 class="text-center mb-3">Project Overview</h2>
                <div class="row text-center">
                    <div class="col-md-3">
                        <div class="card shadow rounded-4 p-3">
                            <h6>Total Budget</h6>
                            <p class="fs-5 fw-bold text-primary">₱<?= number_format(array_sum(array_column($records, 'budget')), 2) ?></p>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="card shadow rounded-4 p-3">
                            <h6>Total Actual</h6>
                            <p class="fs-5 fw-bold text-success">₱<?= number_format(array_sum(array_column($records, 'actual')), 2) ?></p>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="card shadow rounded-4 p-3">
                            <h6>Total Variance</h6>
                            <p class="fs-5 fw-bold text-warning">₱<?= number_format(array_sum(array_column($records, 'budget')) - array_sum(array_column($records, 'actual')), 2) ?></p>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="card shadow rounded-4 p-3">
                            <h6>Total Tax</h6>
                            <p class="fs-5 fw-bold text-danger">₱<?= number_format(array_sum(array_column($records, 'tax')), 2) ?></p>
                        </div>
                    </div>
                </div>
            </div>

            <div class="row g-4">
                <div class="col-md-6">
                    <div class="card shadow rounded-4 p-3">
                        <h6 class="text-center">Actual by Category</h6>
                        <canvas id="categoryChart"></canvas>
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="card shadow rounded-4 p-3">
                        <h6 class="text-center">Monthly Actual Spending</h6>
                        <canvas id="monthlyChart"></canvas>
                    </div>
                </div>
            </div>

            <div class="row mt-4">
                <div class="col-md-6">
                    <div class="card shadow rounded-4 p-3">
                        <h6 class="text-center">Expense Distribution</h6>
                        <canvas id="pieChart"></canvas>
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="card shadow rounded-4 p-3">
                        <h6 class="text-center">Variance Over Time</h6>
                        <canvas id="varianceChart"></canvas>
                    </div>
                </div>
            </div>

            <div class="text-end mt-4">
                <button id="downloadReport" class="btn btn-outline-secondary">📄 Download Report (PDF)</button>
            </div>
        </div>
    </div>

    <!-- ADD RECORD MODAL -->
    <div id="addRecordModal" class="custom-modal-overlay">
        <div class="custom-modal">
            <div class="modal-header">
                <h5>ADD RECORD</h5>
            </div>
            <form method="POST" action="ms_records.php?projectCode=<?= $project_code ?>">
                <div class="modal-body">
                    <div class="input-row">
                        <div class="form-group">
                            <label>Category</label>
                            <select name="category" required>
                                <option value="">-- SELECT --</option>
                                <option value="OPEX">OPEX</option>
                                <option value="CAPEX">CAPEX</option>
                            </select>
                        </div>
                        <div class="form-group">
                            <label>Date</label>
                            <input type="date" name="record_date" value="<?= date('Y-m-d') ?>">
                        </div>
                    </div>

                    <div class="input-row">
                        <div class="form-group">
                            <label>Budget</label>
                            <input type="number" name="budget" value="0" step="0.01">
                        </div>
                        <div class="form-group">
                            <label>Amount</label>
                            <input type="number" name="actual" step="0.01" required>
                        </div>
                    </div>

                    <div class="form-group full-width">
                        <label>Payee</label>
                        <input type="text" name="payee" required>
                    </div>

                    <div class="form-group full-width">
                        <label>Description</label>
                        <input type="text" name="description" required> 
                    </div>

                    <div class="form-group full-width">
                        <label>Remarks</label>
                        <textarea name="remarks" rows="3"></textarea>
                    </div>
                </div>

                <div class="modal-footer">
                    <button type="submit" name="add_record" class="btn-add">ADD</button>
                    <button type="button" class="btn-cancel" onclick="closeModal()">CANCEL</button>
                </div>
            </form>
        </div>
    </div>
    
    <!-- Edit Modal -->
    <div id="editRecordModal" class="custom-modal-overlay" style="display:none;">
        <div class="custom-modal">
            <div class="modal-header">
                <h5>EDIT RECORD</h5>
            </div>
            <form method="POST" action="ms_records.php?projectCode=<?= $project_code ?>">
                <input type="hidden" name="edit_id" id="edit_id">
                <div class="modal-body">
                    <div class="input-row">
                        <div class="form-group">
                            <label>Category</label>
                            <select name="category" required>
                                <option value="">-- SELECT --</option>
                                <option value="OPEX">OPEX</option>
                                <option value="CAPEX">CAPEX</option>
                            </select>
                        </div>
                        <div class="form-group">
                            <label>Date</label>
                            <input type="date" name="record_date">
                        </div>
                    </div>

                    <div class="input-row">
                        <div class="form-group">
                            <label>Budget</label>
                            <input type="number" name="budget" step="0.01">
                        </div>
                        <div class="form-group">
                            <label>Amount</label>
                            <input type="number" name="actual" step="0.01" required>
                        </div>
                    </div>

                    <div class="form-group full-width">
                        <label>Payee</label>
                        <input type="text" name="payee" required>
                    </div>

                    <div class="form-group full-width">
                        <label>Description</label>
                        <input type="text" name="description" required>
                    </div>

                    <div class="form-group full-width">
                        <label>Remarks</label>
                        <textarea name="remarks" rows="3"></textarea>
                    </div>
                </div>

                <div class="modal-footer">
                    <button type="submit" name="save_edit" class="btn-edit-delete">SAVE</button>
                    <button type="button" class="btn-cancel" onclick="closeEditModal()">CANCEL</button>
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
                <form method="POST" action="ms_records.php?projectCode=<?= $project_code ?>">
                    <input type="hidden" name="record_id" id="delete_id">
                    <button type="submit" name="delete_record" class="btn-edit-delete">YES</button>
                    <button type="button" class="btn-cancel" onclick="closeDeleteModal()">NO</button>
                </form>
            </div>
        </div>
    </div>

    <script>
        //Sidebar Trigger (pullup or collapse sidebar)
        document.getElementById("toggleSidebar").addEventListener("click", function () {
            document.getElementById("sidebar").classList.toggle("collapsed");
        });

        //Toggles
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

        //ADD Record Modal
        const modal = document.getElementById("addRecordModal");
        const openBtn = document.querySelector(".add-record-btn");

        openBtn.addEventListener("click", () => {
            modal.style.display = "flex";
        });

        function closeModal() {
            modal.style.display = "none";
        }

        //Closing methods aside from Cancel Button
            //  Close on outside click
            window.onclick = function(event) {
                if (event.target === modal) {
                    closeModal();
                }
            }

            //  Close modal on Escape key
            window.addEventListener("keydown", function(event) {
                if (event.key === "Escape" && modal.style.display === "flex") {
                    modal.style.display = "none";
                }
            });

        //EDIT and DELETE Modals
        const editModal = document.getElementById("editRecordModal"); // you'll define this below
        const deleteModal = document.getElementById("deleteConfirmModal");

        // DELETE
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

        // EDIT
        document.querySelectorAll(".edit-btn").forEach(btn => {
            btn.addEventListener("click", e => {
                e.preventDefault();
                document.getElementById("edit_id").value = btn.dataset.id;
                document.querySelector("#editRecordModal select[name='category']").value = btn.dataset.category;
                document.querySelector("#editRecordModal input[name='record_date']").value = btn.dataset.date;
                document.querySelector("#editRecordModal input[name='budget']").value = btn.dataset.budget;
                document.querySelector("#editRecordModal input[name='actual']").value = btn.dataset.actual;
                document.querySelector("#editRecordModal input[name='payee']").value = btn.dataset.payee;
                document.querySelector("#editRecordModal input[name='description']").value = btn.dataset.description;
                document.querySelector("#editRecordModal textarea[name='remarks']").value = btn.dataset.remarks;

                editModal.style.display = "flex";
            });
        });

        function closeEditModal() {
            editModal.style.display = "none";
        }
    </script>

    <script> //ANALYTICS VIEW
        const categoryChartCtx = document.getElementById('categoryChart').getContext('2d');
        const monthlyChartCtx = document.getElementById('monthlyChart').getContext('2d');
        
        const categoryData = <?= json_encode($category_totals) ?>;
        const monthlyData = <?= json_encode($monthly_totals) ?>;

        const pieLabels = Object.keys(categoryData);
        const pieValues = Object.values(categoryData);

        const categoryChart = new Chart(categoryChartCtx, {
            type: 'bar',
            data: {
                labels: <?= json_encode(array_keys($category_totals)) ?>,
                datasets: [{
                    label: 'Actual by Category',
                    data: <?= json_encode(array_values($category_totals)) ?>,
                    backgroundColor: 'rgba(75, 192, 192, 0.7)',
                    borderColor: 'rgba(75, 192, 192, 1)',
                    borderWidth: 1
                }]
            }
        });

        const monthlyChart = new Chart(monthlyChartCtx, {
            type: 'line',
            data: {
                labels: <?= json_encode(array_keys($monthly_totals)) ?>,
                datasets: [{
                    label: 'Monthly Actual Spending',
                    data: <?= json_encode(array_values($monthly_totals)) ?>,
                    fill: false,
                    borderColor: 'rgba(153, 102, 255, 1)',
                    tension: 0.3
                }]
            }
        });

        const pieChart = new Chart(document.getElementById('pieChart'), {
            type: 'pie',
            data: {
                labels: pieLabels,
                datasets: [{
                    label: 'Category Distribution',
                    data: pieValues,
                    backgroundColor: pieLabels.map(() => `hsl(${Math.random()*360}, 70%, 70%)`)
                }]
            }
        });

        const varianceLabels = <?= json_encode(array_column($records, 'record_date')) ?>;
        const varianceData = <?= json_encode(array_map(fn($r) => $r['budget'] - $r['actual'], $records)) ?>;

        const varianceChart = new Chart(document.getElementById('varianceChart'), {
            type: 'line',
            data: {
                labels: varianceLabels,
                datasets: [{
                    label: 'Variance Over Time',
                    data: varianceData,
                    borderColor: 'rgba(255, 159, 64, 1)',
                    tension: 0.3
                }]
            }
        });

        // PDF Report Export
        document.getElementById('downloadReport').addEventListener('click', () => {
            const { jsPDF } = window.jspdf;
            const doc = new jsPDF();
            doc.text("Project Analytics Report", 10, 10);
            doc.text("Project: <?= addslashes($project['project_name']) ?>", 10, 20);
            doc.text("Total Budget: ₱<?= number_format(array_sum(array_column($records, 'budget')), 2) ?>", 10, 30);
            doc.text("Total Actual: ₱<?= number_format(array_sum(array_column($records, 'actual')), 2) ?>", 10, 40);
            doc.text("Total Variance: ₱<?= number_format(array_sum(array_column($records, 'budget')) - array_sum(array_column($records, 'actual')), 2) ?>", 10, 50);
            doc.text("Total Tax: ₱<?= number_format(array_sum(array_column($records, 'tax')), 2) ?>", 10, 60);
            doc.save("analytics_report_<?= $project_code ?>.pdf");
        });
    </script>
</body>
</html>

<!--
NOTES: 
    04-13-25
    TO BE WORKED ON:
    - modify project summary layout to have them match the initial design [done]
    - expand search bar later [in-progress]
    - add functionality to "add record" button [done]

    NOT YET FUNCTIONAL:
    - sort by and filter   
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
    - analytics: tweak layout and data to be presented
    - add record: amount value is fixed to .00
        - input won't accept other decimal values e.g .98
    - download PDF button
-->