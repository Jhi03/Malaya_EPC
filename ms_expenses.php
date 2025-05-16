<?php
    include('validate_login.php');
    $page_title = "EXPENSES";

    // Database connection
    $host = 'localhost';
    $user = 'u188693564_adminsolar';
    $password = '';
    $database = 'u188693564_malayasol';
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
    $project_id = 1;

    $records = [];
    $project = null;

    if ($project_id > 0) {
        // Get project details
        $project_stmt = $conn->prepare("
            SELECT 
                p.project_id, p.project_name, p.project_code, p.first_name, p.last_name,
                p.company_name, p.description, p.creation_date, 
                p.created_by, p.edit_date, p.edited_by
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
                category = ?, record_date = ?, budget = ?, actual = ?, payee = ?, description = ?, remarks = ?, 
                variance = ?, tax = ?, edited_by = ?, edit_date = NOW()
                WHERE record_id = ? AND project_id = ?");
            $stmt->bind_param(
                "ssddsssdsdii",
                $category, $record_date, $budget, $actual, $payee, $description, $remarks,
                $variance, $tax, $user_id, $edit_id, $project_id
            );
            $stmt->execute();
            $stmt->close();
        } else {
            // ADD NEW RECORD
            $stmt = $conn->prepare("INSERT INTO project_expense (
                project_id, category, record_date, budget, actual, payee, description, remarks, 
                variance, tax, created_by, creation_date
            ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())");
            $stmt->bind_param(
                "issddssssds",
                $project_id, $category, $record_date, $budget, $actual, $payee, $description, $remarks,
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

    $conn->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>Malaya Solar Energies Inc.</title>
    <link rel="icon" href="images/Malaya_Logo.png" type="image/png">
        <link href="https://fonts.googleapis.com/css2?family=IBM+Plex+Sans:wght@300;400;500;700&display=swap" rel="stylesheet">

    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="css/ms_project_expense.css" rel="stylesheet">
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

        <div class="content-body">
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
                                        <button type="button" class="btn btn-sm btn-warning edit-btn"
                                            data-id="<?= $row['record_id'] ?>"
                                            data-category="<?= htmlspecialchars($row['category']) ?>"
                                            data-subcategory="<?= htmlspecialchars($row['subcategory']) ?>"
                                            data-date="<?= $row['purchase_date'] ?>"
                                            data-budget="<?= $row['budget'] ?>"
                                            data-expense="<?= $row['expense'] ?>"
                                            data-payee="<?= htmlspecialchars($row['payee']) ?>"
                                            data-description="<?= htmlspecialchars($row['description']) ?>"
                                            data-remarks="<?= htmlspecialchars($row['remarks']) ?>"
                                            data-rental_rate="<?= $row['rental_rate'] ?>"
                                            data-tax="<?= $row['tax'] ?>"
                                            data-invoice_no="<?= htmlspecialchars($row['invoice_no']) ?>">
                                            <img src="icons/edit.svg" width="18" alt="Edit">
                                        </button>
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
                                <label>Category</label>
                                <select name="category" id="category" required>
                                    <option value="">-- SELECT --</option>
                                    <option value="CAPEX: Materials">CAPEX: Materials</option>
                                    <option value="CAPEX: Labors">CAPEX: Labors</option>
                                    <option value="CAPEX: Purchase">CAPEX: Purchase</option>
                                    <option value="OPEX: Gas">OPEX: Gas</option>
                                    <option value="OPEX: Food">OPEX: Food</option>
                                    <option value="OPEX: Toll">OPEX: Toll</option>
                                    <option value="OPEX: Parking">OPEX: Parking</option>
                                    <option value="OPEX: Salary">OPEX: Salary</option>
                                </select>
                            </div>
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
                            <label>Description</label>
                            <input type="text" name="description" id="description" required> 
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