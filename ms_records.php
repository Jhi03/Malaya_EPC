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

    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_record'])) {
        $category = $_POST['category'];
        $record_date = $_POST['record_date'];
        $budget = $_POST['budget'];
        $actual = $_POST['actual'];
        $description = $_POST['description'];
        $remarks = $_POST['remarks'];
    
        $variance = $budget - $actual;
        $tax = 0;
        $creation_date = date('Y-m-d');
    
        $stmt = $conn->prepare("INSERT INTO project_expense 
            (project_id, category, description, budget, actual, variance, tax, remarks, record_date, creation_date) 
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
        $stmt->bind_param("sssiiissss", 
            $project_code, $category, $description, $budget, $actual, $variance, $tax, $remarks, $record_date, $creation_date);
    
        if ($stmt->execute()) {
            // Refresh to show the new record
            header("Location: ms_projects_record.php?projectCode=" . $project_code);
            exit();
        } else {
            echo "<script>alert('Error adding record: " . $stmt->error . "');</script>";
        }
        $stmt->close();
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
    
    <div class="content">
        <!-- Header Section -->
        <header class="top-bar">
            <button class="hamburger" id="toggleSidebar">☰</button>
            <h2 class="page-title">PROJECTS > Record</h2>
            <button class="user-icon">👤</button>
        </header>

        <!-- Project Summary -->
        <div class="project-summary">
            <p><strong>PROJECT:</strong> <?= $project['project_name'] ?></p>
            <p><strong>CODE:</strong> <?= $project['project_id'] ?></p>
            <p><strong>CLIENT:</strong> <?= $project['first_name'] ?> <?= $project['last_name'] ?></p>
            <p><strong>CREATION DATE:</strong> <?= date('m-d-Y', strtotime($project['creation_date'])) ?></p>
            <p><strong>DESCRIPTION:</strong> <?= $project['description'] ?></p>
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
                    <button class="sort-btn">⇅ Sort By</button>
                    <button class="filter-btn">⧉ Filter</button>
                </div>
            </div>

            <!-- Right group: View toggle -->
            <div class="view-toggle">
                <button class="toggle-btn active" id="view-records">RECORD</button>
                <button class="toggle-btn" id="view-analytics">ANALYTICS</button>
            </div>
        </div>

        <!-- ADD RECORD MODAL -->
        <div id="addRecordModal" class="modal" style="display:none;">
            <div class="modal-content">
                <h4>ADD RECORD</h4>
                <form method="POST" action="ms_projects_record.php?projectCode=<?= $project_code ?>">
                    <div class="form-row">
                        <label>Category</label>
                        <select name="category" required>
                            <option value="">-- SELECT --</option>
                            <option value="Category 1">Category 1</option>
                            <option value="Category 2">Category 2</option>
                        </select>

                        <label>Date</label>
                        <input type="date" name="record_date" value="<?= date('Y-m-d') ?>">
                    </div>

                    <div class="form-row">
                        <label>Budget</label>
                        <input type="number" name="budget" value="0" step="0.01">

                        <label>Amount</label>
                        <input type="number" name="actual" step="0.01" required>
                    </div>

                    <div class="form-row">
                        <label>Description</label>
                        <input type="text" name="description" required>
                    </div>

                    <div class="form-row">
                        <label>Remarks</label>
                        <textarea name="remarks" rows="2"></textarea>
                    </div>

                    <div class="form-actions">
                        <button type="submit" name="add_record" class="btn btn-success">ADD</button>
                        <button type="button" onclick="closeModal()" class="btn btn-secondary">CANCEL</button>
                    </div>
                </form>
            </div>
        </div>

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
                                <td><?= $row['description'] ?></td>
                                <td><?= number_format($row['budget'], 2) ?></td>
                                <td><?= number_format($row['actual'], 2) ?></td>
                                <td><?= number_format($row['variance'], 2) ?></td>
                                <td><?= $row['tax'] ?></td>
                                <td><?= $row['remarks'] ?></td>
                                <td><?= date("m-d-Y", strtotime($row['record_date'])) ?></td>
                                <td><a href="#"><i class="fa fa-pencil"></i></a></td>
                                <td><a href="#"><i class="fa fa-trash"></i></a></td>
                            </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr><td colspan="11" class="text-center">No records available for this project.</td></tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>

    <script>
        //Sidebar Trigger (pullup or collapse sidebar)
        document.getElementById("toggleSidebar").addEventListener("click", function() {
            document.getElementById("sidebar").classList.toggle("d-none");
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

        //Add Record Modal
        const modal = document.getElementById("addRecordModal");
        const openBtn = document.querySelector(".add-record-btn");

        openBtn.addEventListener("click", () => {
            modal.style.display = "block";
        });

        function closeModal() {
            modal.style.display = "none";
        }

        window.onclick = function(event) {
            if (event.target === modal) {
                modal.style.display = "none";
            }
        }
    </script>
</body>
</html>

<!--
NOTES: 
    04-13-25
    TO BE WORKED ON:
    - modify project summary layout to have them match the initial design
    - expand search bar later
    - add functionality to "add record" button [done]

    NOT YET FUNCTIONAL:
    - sort by and filter   
    - add record button

    04-13-25
    CHANGES:
    - added php, modal, and script for add record block

    TO BE WORKED ON:
    - fix add record form later
    - test adding and showing records in the page
-->