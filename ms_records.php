<?php
    session_start();
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

    // Check if user is logged in
    if (!isset($_SESSION['user_id'])) {
        header("Location: ms_login.php");
        exit();
    }
    
    // Fetch logged-in user info
    $user_id = $_SESSION['user_id'];
    $user_query = $conn->prepare("SELECT first_name, last_name FROM users WHERE user_id = ?");
    $user_query->bind_param("s", $user_id);
    $user_query->execute();
    $user_result = $user_query->get_result();
    $user = $user_result->fetch_assoc();
    $created_by = $user['first_name'] . ' ' . $user['last_name'];
    $user_query->close();       

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

    // Add record
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
            (project_id, category, description, budget, actual, payee, variance, tax, remarks, record_date, creation_date, created_by) 
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");

        $stmt->bind_param("sssddsddssss", 
            $project_code, $category, $description, $budget, $actual, $payee, $variance, $tax, $remarks, $record_date, $creation_date, $created_by);

        if ($stmt->execute()) {
            header("Location: ms_records.php?projectCode=" . $project_code);
            exit();
        } else {
            echo "<script>alert('Error adding record: " . $stmt->error . "');</script>";
        }
        $stmt->close();
    }

    // Edit record
    if (isset($_POST['save_edit'])) {
        $edit_id = $_POST['edit_id'];
        $category = $_POST['category'];
        $record_date = $_POST['record_date'];
        $budget = $_POST['budget'];
        $actual = $_POST['actual'];
        $payee = $_POST['payee'];
        $description = $_POST['description'];
        $remarks = $_POST['remarks'];

        $variance = $budget - $actual;
        $tax = 0;
        $edit_date = date('Y-m-d');
        $edited_by = $created_by;

        $stmt = $conn->prepare("UPDATE project_expense SET 
            category = ?, record_date = ?, budget = ?, actual = ?, variance = ?, tax = ?, payee = ?, description = ?, remarks = ?, edit_date = ?, edited_by = ?
            WHERE record_id = ?");

        $stmt->bind_param("ssddddsssssi", $category, $record_date, $budget, $actual, $variance, $tax, $payee, $description, $remarks, $edit_date, $edited_by, $edit_id);

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
                                        data-remarks="<?= $row['remarks'] ?>"
                                        data-created_by="<?= $row['created_by'] ?>"
                                        data-creation_date="<?= $row['creation_date'] ?>"
                                        data-edited_by="<?= $row['edited_by'] ?>"
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
    </div>

    <!-- ADD/EDIT RECORD MODAL -->
    <div id="recordModal" class="custom-modal-overlay" style="display:none;">
        <div class="custom-modal">
            <div class="modal-header">
                <h5 id="recordModalHeader">ADD RECORD</h5>
            </div>
            <form method="POST" action="ms_records.php?projectCode=<?= $project_code ?>">
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

    <!-- Delete Confirmation Modal -->
    <div id="deleteConfirmModal" class="custom-modal-overlay" style="display:none;">
        <div class="custom-modal">
            <div class="modal-header">
                <h5>Delete Record?</h5>
            </div>
            <div class="modal-footer">
                <form method="POST" action="ms_records.php?projectCode=<?= $project_code ?>">
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
        document.addEventListener("DOMContentLoaded", function () {
            // Sidebar Toggle
            const toggleSidebarBtn = document.getElementById("toggleSidebar");
            const sidebar = document.getElementById("sidebar");

            if (toggleSidebarBtn && sidebar) {
                toggleSidebarBtn.addEventListener("click", function () {
                    sidebar.classList.toggle("collapsed");

                    // Optional: Save state
                    const isCollapsed = sidebar.classList.contains("collapsed");
                    localStorage.setItem("sidebarCollapsed", isCollapsed);
                });

                // Restore sidebar state
                const isCollapsed = localStorage.getItem("sidebarCollapsed") === "true";
                if (isCollapsed) {
                    sidebar.classList.add("collapsed");
                }
            }
        });
        
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

        //ADD and EDIT Record Modal
        function openRecordModal(mode, data = {}) {
            const modal = document.getElementById('recordModal');
            const header = document.getElementById('recordModalHeader');
            const submitBtn = document.getElementById('recordSubmitBtn');
            const editIdField = document.getElementById('edit_id');

            // Reset all form fields
            document.querySelector('#recordModal form').reset();

            // Set form based on mode
            if (mode === 'add') {
                header.textContent = 'ADD RECORD';
                submitBtn.textContent = 'ADD';
                submitBtn.className = 'btn-add';
                submitBtn.name = 'add_record';
                editIdField.value = '';

                document.getElementById('recordMeta').style.display = 'none';
            } else if (mode === 'edit') {
                header.textContent = 'EDIT RECORD';
                submitBtn.textContent = 'SAVE';
                submitBtn.className = 'btn-save-delete';
                submitBtn.name = 'save_edit';
                editIdField.value = data.id;

                document.getElementById('category').value = data.category;
                document.getElementById('record_date').value = data.date;
                document.getElementById('budget').value = data.budget;
                document.getElementById('actual').value = data.actual;
                document.getElementById('payee').value = data.payee;
                document.getElementById('description').value = data.description;
                document.getElementById('remarks').value = data.remarks;

                // Fill metadata
                document.getElementById('recordMeta').style.display = 'block';
                document.getElementById('createdBy').textContent = data.created_by || 'N/A';
                document.getElementById('createdDate').textContent = data.creation_date ? formatDate(data.creation_date) : 'N/A';
                document.getElementById('editedBy').textContent = data.edited_by || '—';
                document.getElementById('editedDate').textContent = data.edit_date ? formatDate(data.edit_date) : '—';
            }

            modal.style.display = 'flex';
        }

        function formatDate(dateStr) {
            const date = new Date(dateStr);
            return (date.getMonth()+1).toString().padStart(2, '0') + '/' + date.getDate().toString().padStart(2, '0') + '/' + date.getFullYear();
        }

        function closeModal() {
            document.getElementById('recordModal').style.display = 'none';
        }

        // Trigger edit modal
        document.querySelectorAll('.edit-btn').forEach(btn => {
            btn.addEventListener('click', function(e) {
                e.preventDefault();
                const data = {
                    id: this.dataset.id,
                    category: this.dataset.category,
                    date: this.dataset.date,
                    budget: this.dataset.budget,
                    actual: this.dataset.actual,
                    payee: this.dataset.payee,
                    description: this.dataset.description,
                    remarks: this.dataset.remarks,
                    created_by: this.dataset.created_by,
                    creation_date: this.dataset.creation_date,
                    edited_by: this.dataset.edited_by,
                    edit_date: this.dataset.edit_date
                };
                openRecordModal('edit', data);
            });
        });

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
                    closeModal();
                }
            });

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