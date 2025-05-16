<?php
    include('validate_login.php');
    $page_title = "PAYROLL";

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
    
    // Process form submissions
    $success_message = "";
    $error_message = "";
    
    // Handle payroll entry deletion
    if (isset($_POST['delete_payroll'])) {
        $payroll_id = $_POST['payroll_id'];
        $delete_sql = "DELETE FROM payroll WHERE id = ?";
        $stmt = $conn->prepare($delete_sql);
        $stmt->bind_param("i", $payroll_id);
        
        if ($stmt->execute()) {
            $success_message = "Payroll entry deleted successfully.";
        } else {
            $error_message = "Error deleting payroll entry: " . $conn->error;
        }
        $stmt->close();
    }
    
    // Handle payroll entry addition or update
    if (isset($_POST['save_payroll'])) {
        $employee_id = $_POST['employee_id'];
        $period_start = $_POST['period_start'];
        $period_end = $_POST['period_end'];
        $gross_pay = $_POST['gross_pay'];
        $basic_pay = $_POST['basic_pay'];
        $overtime_pay = $_POST['overtime_pay'] ?? 0;
        $allowances = $_POST['allowances'] ?? 0;
        $bonus = $_POST['bonus'] ?? 0;
        $sss = $_POST['sss'];
        $philhealth = $_POST['philhealth'];
        $pagibig = $_POST['pagibig'];
        $loans = $_POST['loans'] ?? 0;
        $other_deductions = $_POST['other_deductions'] ?? 0;
        $total_deductions = $_POST['total_deductions'];
        $tax = $_POST['tax'];
        $net_pay = $_POST['net_pay'];
        $payment_method = $_POST['payment_method'];
        $remarks = $_POST['remarks'] ?? '';
        
        // Check if we're updating or adding
        if (isset($_POST['payroll_id']) && !empty($_POST['payroll_id'])) {
            $payroll_id = $_POST['payroll_id'];
            $sql = "UPDATE payroll SET 
                employee_id = ?, 
                period_start = ?, 
                period_end = ?, 
                gross_pay = ?, 
                basic_pay = ?, 
                overtime_pay = ?, 
                allowances = ?, 
                bonus = ?, 
                sss = ?, 
                philhealth = ?, 
                pagibig = ?, 
                loans = ?, 
                other_deductions = ?, 
                total_deductions = ?, 
                tax = ?, 
                net_pay = ?, 
                payment_method = ?, 
                remarks = ? 
                WHERE id = ?";
            
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("issdddddddddddddssi", 
                $employee_id, $period_start, $period_end, $gross_pay, $basic_pay, 
                $overtime_pay, $allowances, $bonus, $sss, $philhealth, $pagibig, 
                $loans, $other_deductions, $total_deductions, $tax, $net_pay, 
                $payment_method, $remarks, $payroll_id);
            
            if ($stmt->execute()) {
                $success_message = "Payroll entry updated successfully.";
            } else {
                $error_message = "Error updating payroll entry: " . $conn->error;
            }
        } else {
            // Add new payroll entry
            $sql = "INSERT INTO payroll (
                employee_id, period_start, period_end, gross_pay, basic_pay, 
                overtime_pay, allowances, bonus, sss, philhealth, pagibig, 
                loans, other_deductions, total_deductions, tax, net_pay, 
                payment_method, remarks
            ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
            
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("issddddddddddddss", 
                $employee_id, $period_start, $period_end, $gross_pay, $basic_pay, 
                $overtime_pay, $allowances, $bonus, $sss, $philhealth, $pagibig, 
                $loans, $other_deductions, $total_deductions, $tax, $net_pay, 
                $payment_method, $remarks);
            
            if ($stmt->execute()) {
                $success_message = "Payroll entry added successfully.";
            } else {
                $error_message = "Error adding payroll entry: " . $conn->error;
            }
        }
        $stmt->close();
    }
    
    // Fetch all employees for dropdown
    $employees_query = "SELECT e.employee_id, CONCAT(e.first_name, ' ', e.last_name) as employee_name 
                       FROM employee e 
                       WHERE e.status = 'active' 
                       ORDER BY e.last_name, e.first_name";
    $employees_result = $conn->query($employees_query);
    $employees = [];
    if ($employees_result && $employees_result->num_rows > 0) {
        while ($row = $employees_result->fetch_assoc()) {
            $employees[$row['employee_id']] = $row['employee_name'];
        }
    }
    
    // Set up filtering options
    $filter_employee = isset($_GET['filter_employee']) ? $_GET['filter_employee'] : '';
    $filter_period_start = isset($_GET['filter_period_start']) ? $_GET['filter_period_start'] : '';
    $filter_period_end = isset($_GET['filter_period_end']) ? $_GET['filter_period_end'] : '';
    $search = isset($_GET['search']) ? $_GET['search'] : '';
    
    // Construct the filter query
    $filter_query = " WHERE 1=1";
    $params = [];
    $param_types = "";
    
    if (!empty($filter_employee)) {
        $filter_query .= " AND p.employee_id = ?";
        $params[] = $filter_employee;
        $param_types .= "i";
    }
    
    if (!empty($filter_period_start)) {
        $filter_query .= " AND p.period_start >= ?";
        $params[] = $filter_period_start;
        $param_types .= "s";
    }
    
    if (!empty($filter_period_end)) {
        $filter_query .= " AND p.period_end <= ?";
        $params[] = $filter_period_end;
        $param_types .= "s";
    }
    
    if (!empty($search)) {
        $search_term = "%$search%";
        $filter_query .= " AND (e.first_name LIKE ? OR e.last_name LIKE ? OR p.payment_method LIKE ?)";
        $params[] = $search_term;
        $params[] = $search_term;
        $params[] = $search_term;
        $param_types .= "sss";
    }
    
    // Query to fetch payroll entries with employee names
    $sql = "SELECT p.*, CONCAT(e.first_name, ' ', e.last_name) as employee_name 
           FROM payroll p 
           JOIN employee e ON p.employee_id = e.employee_id
           $filter_query 
           ORDER BY p.period_end DESC";
    
    $stmt = $conn->prepare($sql);
    
    if (!empty($params)) {
        $stmt->bind_param($param_types, ...$params);
    }
    
    $stmt->execute();
    $result = $stmt->get_result();
    $payroll_entries = [];
    
    if ($result && $result->num_rows > 0) {
        while ($row = $result->fetch_assoc()) {
            $payroll_entries[] = $row;
        }
    }
    $stmt->close();
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
    <link href="css/ms_sidebar.css" rel="stylesheet">
    <link href="css/ms_header.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <style>
        .content-container {
            padding: 20px;
        }
        .card {
            margin-bottom: 20px;
            border-radius: 8px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }
        .card-header {
            background-color: #f8f9fa;
            padding: 15px;
            border-bottom: 1px solid #e7e7e7;
        }
        .form-group {
            margin-bottom: 15px;
        }
        .table-responsive {
            overflow-x: auto;
        }
        .table th {
            background-color: #f8f9fa;
        }
        .btn-action {
            margin-right: 5px;
        }
        .alert {
            margin-bottom: 20px;
        }
        .payroll-stats {
            display: flex;
            flex-wrap: wrap;
            gap: 15px;
            margin-bottom: 20px;
        }
        .stat-card {
            flex: 1;
            min-width: 200px;
            padding: 15px;
            border-radius: 8px;
            background-color: #fff;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }
        .stat-card h3 {
            margin: 0;
            font-size: 14px;
            color: #6c757d;
        }
        .stat-card p {
            margin: 10px 0 0;
            font-size: 24px;
            font-weight: bold;
        }
        .filter-form {
            padding: 15px;
            margin-bottom: 20px;
            background-color: #f8f9fa;
            border-radius: 8px;
        }
        .form-section {
            border: 1px solid #dee2e6;
            border-radius: 8px;
            padding: 15px;
            margin-bottom: 15px;
        }
        .form-section-title {
            font-weight: bold;
            margin-bottom: 10px;
            border-bottom: 1px solid #dee2e6;
            padding-bottom: 5px;
        }
        #calculationSummary {
            font-size: 16px;
            margin-top: 15px;
        }
        .calculation-row {
            display: flex;
            justify-content: space-between;
            margin-bottom: 5px;
        }
        .calculation-row.total {
            font-weight: bold;
            border-top: 1px solid #dee2e6;
            padding-top: 5px;
            margin-top: 5px;
        }
    </style>
</head>
<body>
    <div class="sidebar" id="sidebar">
        <?php include 'sidebar.php'; ?>
    </div>
    
    <div class="content-area">
        <?php include 'header.php'; ?>
        
        <div class="content-container">
            <?php if(!empty($success_message)): ?>
                <div class="alert alert-success"><?php echo $success_message; ?></div>
            <?php endif; ?>
            
            <?php if(!empty($error_message)): ?>
                <div class="alert alert-danger"><?php echo $error_message; ?></div>
            <?php endif; ?>
            
            <div class="d-flex justify-content-between align-items-center mb-4">
                <h1>Payroll Management</h1>
                <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#payrollModal">
                    <i class="fas fa-plus-circle me-2"></i>Add New Payroll Entry
                </button>
            </div>
            
            <!-- Payroll Statistics Section -->
            <div class="payroll-stats">
                <div class="stat-card">
                    <h3>Total Payroll Records</h3>
                    <p><?php echo count($payroll_entries); ?></p>
                </div>
                <div class="stat-card">
                    <h3>Total Employees on Payroll</h3>
                    <p><?php echo count($employees); ?></p>
                </div>
                <?php if(!empty($payroll_entries)): 
                    $total_net_pay = array_sum(array_column($payroll_entries, 'net_pay'));
                    $total_tax = array_sum(array_column($payroll_entries, 'tax'));
                    $total_deductions = array_sum(array_column($payroll_entries, 'total_deductions'));
                ?>
                <div class="stat-card">
                    <h3>Total Net Pay</h3>
                    <p>₱<?php echo number_format($total_net_pay, 2); ?></p>
                </div>
                <div class="stat-card">
                    <h3>Total Tax</h3>
                    <p>₱<?php echo number_format($total_tax, 2); ?></p>
                </div>
                <?php endif; ?>
            </div>
            
            <!-- Filter Form -->
            <div class="card mb-4">
                <div class="card-header">
                    <h5 class="mb-0">Filter Payroll Records</h5>
                </div>
                <div class="card-body">
                    <form method="GET" class="row g-3">
                        <div class="col-md-3">
                            <label for="filter_employee" class="form-label">Employee</label>
                            <select class="form-select" id="filter_employee" name="filter_employee">
                                <option value="">All Employees</option>
                                <?php foreach($employees as $id => $name): ?>
                                    <option value="<?php echo $id; ?>" <?php echo ($filter_employee == $id) ? 'selected' : ''; ?>>
                                        <?php echo $name; ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-3">
                            <label for="filter_period_start" class="form-label">Period Start</label>
                            <input type="date" class="form-control" id="filter_period_start" name="filter_period_start" value="<?php echo $filter_period_start; ?>">
                        </div>
                        <div class="col-md-3">
                            <label for="filter_period_end" class="form-label">Period End</label>
                            <input type="date" class="form-control" id="filter_period_end" name="filter_period_end" value="<?php echo $filter_period_end; ?>">
                        </div>
                        <div class="col-md-3">
                            <label for="search" class="form-label">Search</label>
                            <input type="text" class="form-control" id="search" name="search" placeholder="Search..." value="<?php echo $search; ?>">
                        </div>
                        <div class="col-12">
                            <button type="submit" class="btn btn-primary me-2">Apply Filters</button>
                            <a href="ms_payroll.php" class="btn btn-outline-secondary">Clear Filters</a>
                        </div>
                    </form>
                </div>
            </div>
            
            <!-- Payroll Records Table -->
            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0">Payroll Records</h5>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-hover table-striped">
                            <thead>
                                <tr>
                                    <th>ID</th>
                                    <th>Employee</th>
                                    <th>Period</th>
                                    <th>Gross Pay</th>
                                    <th>Deductions</th>
                                    <th>Tax</th>
                                    <th>Net Pay</th>
                                    <th>Payment Method</th>
                                    <th>Date Generated</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if(empty($payroll_entries)): ?>
                                    <tr>
                                        <td colspan="10" class="text-center">No payroll records found.</td>
                                    </tr>
                                <?php else: ?>
                                    <?php foreach($payroll_entries as $entry): ?>
                                        <tr>
                                            <td><?php echo $entry['id']; ?></td>
                                            <td><?php echo $entry['employee_name']; ?></td>
                                            <td><?php echo date('M d, Y', strtotime($entry['period_start'])) . ' - ' . date('M d, Y', strtotime($entry['period_end'])); ?></td>
                                            <td>₱<?php echo number_format($entry['gross_pay'], 2); ?></td>
                                            <td>₱<?php echo number_format($entry['total_deductions'], 2); ?></td>
                                            <td>₱<?php echo number_format($entry['tax'], 2); ?></td>
                                            <td>₱<?php echo number_format($entry['net_pay'], 2); ?></td>
                                            <td><?php echo $entry['payment_method']; ?></td>
                                            <td><?php echo date('M d, Y', strtotime($entry['date_generated'])); ?></td>
                                            <td>
                                                <button class="btn btn-sm btn-info btn-action view-payroll" 
                                                        data-id="<?php echo $entry['id']; ?>"
                                                        data-employee="<?php echo $entry['employee_id']; ?>"
                                                        data-period-start="<?php echo $entry['period_start']; ?>"
                                                        data-period-end="<?php echo $entry['period_end']; ?>"
                                                        data-gross="<?php echo $entry['gross_pay']; ?>"
                                                        data-basic="<?php echo $entry['basic_pay']; ?>"
                                                        data-overtime="<?php echo $entry['overtime_pay']; ?>"
                                                        data-allowances="<?php echo $entry['allowances']; ?>"
                                                        data-bonus="<?php echo $entry['bonus']; ?>"
                                                        data-sss="<?php echo $entry['sss']; ?>"
                                                        data-philhealth="<?php echo $entry['philhealth']; ?>"
                                                        data-pagibig="<?php echo $entry['pagibig']; ?>"
                                                        data-loans="<?php echo $entry['loans']; ?>"
                                                        data-other-deductions="<?php echo $entry['other_deductions']; ?>"
                                                        data-total-deductions="<?php echo $entry['total_deductions']; ?>"
                                                        data-tax="<?php echo $entry['tax']; ?>"
                                                        data-net="<?php echo $entry['net_pay']; ?>"
                                                        data-payment="<?php echo $entry['payment_method']; ?>"
                                                        data-remarks="<?php echo $entry['remarks']; ?>">
                                                    <i class="fas fa-eye"></i>
                                                </button>
                                                <button class="btn btn-sm btn-warning btn-action edit-payroll"
                                                        data-id="<?php echo $entry['id']; ?>"
                                                        data-employee="<?php echo $entry['employee_id']; ?>"
                                                        data-period-start="<?php echo $entry['period_start']; ?>"
                                                        data-period-end="<?php echo $entry['period_end']; ?>"
                                                        data-gross="<?php echo $entry['gross_pay']; ?>"
                                                        data-basic="<?php echo $entry['basic_pay']; ?>"
                                                        data-overtime="<?php echo $entry['overtime_pay']; ?>"
                                                        data-allowances="<?php echo $entry['allowances']; ?>"
                                                        data-bonus="<?php echo $entry['bonus']; ?>"
                                                        data-sss="<?php echo $entry['sss']; ?>"
                                                        data-philhealth="<?php echo $entry['philhealth']; ?>"
                                                        data-pagibig="<?php echo $entry['pagibig']; ?>"
                                                        data-loans="<?php echo $entry['loans']; ?>"
                                                        data-other-deductions="<?php echo $entry['other_deductions']; ?>"
                                                        data-total-deductions="<?php echo $entry['total_deductions']; ?>"
                                                        data-tax="<?php echo $entry['tax']; ?>"
                                                        data-net="<?php echo $entry['net_pay']; ?>"
                                                        data-payment="<?php echo $entry['payment_method']; ?>"
                                                        data-remarks="<?php echo $entry['remarks']; ?>">
                                                    <i class="fas fa-edit"></i>
                                                </button>
                                                <button class="btn btn-sm btn-danger btn-action delete-payroll" data-id="<?php echo $entry['id']; ?>">
                                                    <i class="fas fa-trash"></i>
                                                </button>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Payroll Modal (Add/Edit) -->
    <div class="modal fade" id="payrollModal" tabindex="-1" aria-labelledby="payrollModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <form id="payrollForm" method="post">
                    <div class="modal-header">
                        <h5 class="modal-title" id="payrollModalLabel">Add New Payroll Entry</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
                        <input type="hidden" id="payroll_id" name="payroll_id">
                        
                        <div class="form-section">
                            <div class="form-section-title">Employee Information</div>
                            <div class="row g-3">
                                <div class="col-md-12">
                                    <label for="employee_id" class="form-label">Employee</label>
                                    <select class="form-select" id="employee_id" name="employee_id" required>
                                        <option value="">Select Employee</option>
                                        <?php foreach($employees as $id => $name): ?>
                                            <option value="<?php echo $id; ?>"><?php echo $name; ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                                <div class="col-md-6">
                                    <label for="period_start" class="form-label">Period Start</label>
                                    <input type="date" class="form-control" id="period_start" name="period_start" required>
                                </div>
                                <div class="col-md-6">
                                    <label for="period_end" class="form-label">Period End</label>
                                    <input type="date" class="form-control" id="period_end" name="period_end" required>
                                </div>
                            </div>
                        </div>
                        
                        <div class="form-section">
                            <div class="form-section-title">Earnings</div>
                            <div class="row g-3">
                                <div class="col-md-6">
                                    <label for="basic_pay" class="form-label">Basic Pay</label>
                                    <input type="number" step="0.01" class="form-control calculation" id="basic_pay" name="basic_pay" required>
                                </div>
                                <div class="col-md-6">
                                    <label for="overtime_pay" class="form-label">Overtime Pay</label>
                                    <input type="number" step="0.01" class="form-control calculation" id="overtime_pay" name="overtime_pay" value="0.00">
                                </div>
                                <div class="col-md-6">
                                    <label for="allowances" class="form-label">Allowances</label>
                                    <input type="number" step="0.01" class="form-control calculation" id="allowances" name="allowances" value="0.00">
                                </div>
                                <div class="col-md-6">
                                    <label for="bonus" class="form-label">Bonus</label>
                                    <input type="number" step="0.01" class="form-control calculation" id="bonus" name="bonus" value="0.00">
                                </div>
                                <div class="col-md-6">
                                    <label for="gross_pay" class="form-label">Gross Pay</label>
                                    <input type="number" step="0.01" class="form-control" id="gross_pay" name="gross_pay" readonly>
                                </div>
                            </div>
                        </div>
                        
                        <div class="form-section">
                            <div class="form-section-title">Deductions</div>
                            <div class="row g-3">
                                <div class="col-md-4">
                                    <label for="sss" class="form-label">SSS</label>
                                    <input type="number" step="0.01" class="form-control calculation" id="sss" name="sss" required>
                                </div>
                                <div class="col-md-4">
                                    <label for="philhealth" class="form-label">PhilHealth</label>
                                    <input type="number" step="0.01" class="form-control calculation" id="philhealth" name="philhealth" required>
                                </div>
                                <div class="col-md-4">
                                    <label for="pagibig" class="form-label">Pag-IBIG</label>
                                    <input type="number" step="0.01" class="form-control calculation" id="pagibig" name="pagibig" required>
                                </div>
                                <div class="col-md-6">
                                    <label for="loans" class="form-label">Loans</label>
                                    <input type="number" step="0.01" class="form-control calculation" id="loans" name="loans" value="0.00">
                                </div>
                                <div class="col-md-6">
                                    <label for="other_deductions" class="form-label">Other Deductions</label>
                                    <input type="number" step="0.01" class="form-control calculation" id="other_deductions" name="other_deductions" value="0.00">
                                </div>
                                <div class="col-md-6">
                                    <label for="total_deductions" class="form-label">Total Deductions</label>
                                    <input type="number" step="0.01" class="form-control" id="total_deductions" name="total_deductions" readonly>
                                </div>
                                <div class="col-md-6">
                                    <label for="tax" class="form-label">Tax</label>
                                    <input type="number" step="0.01" class="form-control calculation" id="tax" name="tax" required>
                                </div>
                            </div>
                        </div>
                        
                        <div class="form-section">
                            <div class="form-section-title">Payment Information</div>
                            <div class="row g-3">
                                <div class="col-md-6">
                                    <label for="net_pay" class="form-label">Net Pay</label>
                                    <input type="number" step="0.01" class="form-control" id="net_pay" name="net_pay" readonly>
                                </div>
                                <div class="col-md-6">
                                    <label for="payment_method" class="form-label">Payment Method</label>
                                    <select class="form-select" id="payment_method" name="payment_method" required>
                                        <option value="Bank Transfer">Bank Transfer</option>
                                        <option value="Check">Check</option>
                                        <option value="Cash">Cash</option>
                                    </select>
                                </div>
<div class="col-md-12">
                                    <label for="remarks" class="form-label">Remarks</label>
                                    <textarea class="form-control" id="remarks" name="remarks" rows="3"></textarea>
                                </div>
                            </div>
                        </div>
                        
                        <div id="calculationSummary" class="bg-light p-3 rounded">
                            <h6>Payroll Summary</h6>
                            <div class="calculation-row">
                                <span>Basic Pay:</span>
                                <span id="summary_basic">₱0.00</span>
                            </div>
                            <div class="calculation-row">
                                <span>Overtime Pay:</span>
                                <span id="summary_overtime">₱0.00</span>
                            </div>
                            <div class="calculation-row">
                                <span>Allowances:</span>
                                <span id="summary_allowances">₱0.00</span>
                            </div>
                            <div class="calculation-row">
                                <span>Bonus:</span>
                                <span id="summary_bonus">₱0.00</span>
                            </div>
                            <div class="calculation-row total">
                                <span>Gross Pay:</span>
                                <span id="summary_gross">₱0.00</span>
                            </div>
                            
                            <div class="calculation-row mt-3">
                                <span>SSS:</span>
                                <span id="summary_sss">₱0.00</span>
                            </div>
                            <div class="calculation-row">
                                <span>PhilHealth:</span>
                                <span id="summary_philhealth">₱0.00</span>
                            </div>
                            <div class="calculation-row">
                                <span>Pag-IBIG:</span>
                                <span id="summary_pagibig">₱0.00</span>
                            </div>
                            <div class="calculation-row">
                                <span>Loans:</span>
                                <span id="summary_loans">₱0.00</span>
                            </div>
                            <div class="calculation-row">
                                <span>Other Deductions:</span>
                                <span id="summary_other_deductions">₱0.00</span>
                            </div>
                            <div class="calculation-row">
                                <span>Tax:</span>
                                <span id="summary_tax">₱0.00</span>
                            </div>
                            <div class="calculation-row total">
                                <span>Total Deductions:</span>
                                <span id="summary_deductions">₱0.00</span>
                            </div>
                            
                            <div class="calculation-row total mt-3">
                                <span>NET PAY:</span>
                                <span id="summary_net">₱0.00</span>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" name="save_payroll" class="btn btn-primary">Save Payroll Entry</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    
    <!-- View Payroll Modal -->
    <div class="modal fade" id="viewPayrollModal" tabindex="-1" aria-labelledby="viewPayrollModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="viewPayrollModalLabel">Payroll Details</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="row mb-4">
                        <div class="col-md-12">
                            <h6>Employee Information</h6>
                            <table class="table table-bordered">
                                <tr>
                                    <th width="30%">Employee</th>
                                    <td id="view_employee_name"></td>
                                </tr>
                                <tr>
                                    <th>Payroll Period</th>
                                    <td id="view_period"></td>
                                </tr>
                                <tr>
                                    <th>Payment Method</th>
                                    <td id="view_payment_method"></td>
                                </tr>
                            </table>
                        </div>
                    </div>
                    
                    <div class="row mb-4">
                        <div class="col-md-6">
                            <h6>Earnings</h6>
                            <table class="table table-bordered">
                                <tr>
                                    <th>Basic Pay</th>
                                    <td id="view_basic_pay"></td>
                                </tr>
                                <tr>
                                    <th>Overtime Pay</th>
                                    <td id="view_overtime_pay"></td>
                                </tr>
                                <tr>
                                    <th>Allowances</th>
                                    <td id="view_allowances"></td>
                                </tr>
                                <tr>
                                    <th>Bonus</th>
                                    <td id="view_bonus"></td>
                                </tr>
                                <tr class="table-secondary">
                                    <th>Gross Pay</th>
                                    <td id="view_gross_pay"></td>
                                </tr>
                            </table>
                        </div>
                        <div class="col-md-6">
                            <h6>Deductions</h6>
                            <table class="table table-bordered">
                                <tr>
                                    <th>SSS</th>
                                    <td id="view_sss"></td>
                                </tr>
                                <tr>
                                    <th>PhilHealth</th>
                                    <td id="view_philhealth"></td>
                                </tr>
                                <tr>
                                    <th>Pag-IBIG</th>
                                    <td id="view_pagibig"></td>
                                </tr>
                                <tr>
                                    <th>Loans</th>
                                    <td id="view_loans"></td>
                                </tr>
                                <tr>
                                    <th>Other Deductions</th>
                                    <td id="view_other_deductions"></td>
                                </tr>
                                <tr>
                                    <th>Tax</th>
                                    <td id="view_tax"></td>
                                </tr>
                                <tr class="table-secondary">
                                    <th>Total Deductions</th>
                                    <td id="view_total_deductions"></td>
                                </tr>
                            </table>
                        </div>
                    </div>
                    
                    <div class="row">
                        <div class="col-md-12">
                            <table class="table table-bordered">
                                <tr class="table-primary">
                                    <th width="30%">NET PAY</th>
                                    <td id="view_net_pay"></td>
                                </tr>
                            </table>
                        </div>
                    </div>
                    
                    <div class="row mt-3">
                        <div class="col-md-12">
                            <h6>Remarks</h6>
                            <div class="p-3 bg-light rounded" id="view_remarks"></div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                    <button type="button" class="btn btn-primary" id="printPayrollSlip">Print Payslip</button>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Delete Confirmation Modal -->
    <div class="modal fade" id="deleteModal" tabindex="-1" aria-labelledby="deleteModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="deleteModalLabel">Confirm Delete</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    Are you sure you want to delete this payroll entry? This action cannot be undone.
                </div>
                <div class="modal-footer">
                    <form method="post">
                        <input type="hidden" id="delete_payroll_id" name="payroll_id">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" name="delete_payroll" class="btn btn-danger">Delete</button>
                    </form>
                </div>
            </div>
        </div>
    </div>
    
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Toggle sidebar
            const sidebar = document.getElementById('sidebar');
            const toggleBtn = document.getElementById('toggleSidebar');
            
            if (toggleBtn) {
                toggleBtn.addEventListener('click', function() {
                    sidebar.classList.toggle('collapsed');
                });
            }
            
            // Toggle user dropdown menu
            const userDropdownBtn = document.getElementById('userDropdownBtn');
            const userDropdownMenu = document.getElementById('userDropdownMenu');
            
            if (userDropdownBtn && userDropdownMenu) {
                userDropdownBtn.addEventListener('click', function(e) {
                    e.stopPropagation();
                    userDropdownMenu.style.display = userDropdownMenu.style.display === 'block' ? 'none' : 'block';
                });
                
                // Close dropdown when clicking outside
                document.addEventListener('click', function() {
                    userDropdownMenu.style.display = 'none';
                });
            }
            
            // Payroll form calculations for automatic updates
            const calculationInputs = document.querySelectorAll('.calculation');
            
            calculationInputs.forEach(input => {
                input.addEventListener('input', calculatePayroll);
            });
            
            function calculatePayroll() {
                // Get input values
                const basicPay = parseFloat(document.getElementById('basic_pay').value) || 0;
                const overtimePay = parseFloat(document.getElementById('overtime_pay').value) || 0;
                const allowances = parseFloat(document.getElementById('allowances').value) || 0;
                const bonus = parseFloat(document.getElementById('bonus').value) || 0;
                
                // Calculate gross pay
                const grossPay = basicPay + overtimePay + allowances + bonus;
                document.getElementById('gross_pay').value = grossPay.toFixed(2);
                
                // Get deduction values
                const sss = parseFloat(document.getElementById('sss').value) || 0;
                const philhealth = parseFloat(document.getElementById('philhealth').value) || 0;
                const pagibig = parseFloat(document.getElementById('pagibig').value) || 0;
                const loans = parseFloat(document.getElementById('loans').value) || 0;
                const otherDeductions = parseFloat(document.getElementById('other_deductions').value) || 0;
                const tax = parseFloat(document.getElementById('tax').value) || 0;
                
                // Calculate total deductions
                const totalDeductions = sss + philhealth + pagibig + loans + otherDeductions;
                document.getElementById('total_deductions').value = totalDeductions.toFixed(2);
                
                // Calculate net pay
                const netPay = grossPay - totalDeductions - tax;
                document.getElementById('net_pay').value = netPay.toFixed(2);
                
                // Update summary
                document.getElementById('summary_basic').textContent = '₱' + basicPay.toFixed(2);
                document.getElementById('summary_overtime').textContent = '₱' + overtimePay.toFixed(2);
                document.getElementById('summary_allowances').textContent = '₱' + allowances.toFixed(2);
                document.getElementById('summary_bonus').textContent = '₱' + bonus.toFixed(2);
                document.getElementById('summary_gross').textContent = '₱' + grossPay.toFixed(2);
                
                document.getElementById('summary_sss').textContent = '₱' + sss.toFixed(2);
                document.getElementById('summary_philhealth').textContent = '₱' + philhealth.toFixed(2);
                document.getElementById('summary_pagibig').textContent = '₱' + pagibig.toFixed(2);
                document.getElementById('summary_loans').textContent = '₱' + loans.toFixed(2);
                document.getElementById('summary_other_deductions').textContent = '₱' + otherDeductions.toFixed(2);
                document.getElementById('summary_tax').textContent = '₱' + tax.toFixed(2);
                document.getElementById('summary_deductions').textContent = '₱' + (totalDeductions + tax).toFixed(2);
                
                document.getElementById('summary_net').textContent = '₱' + netPay.toFixed(2);
            }
            
            // Format date as MMM DD, YYYY
            function formatDate(dateString) {
                const date = new Date(dateString);
                const options = { year: 'numeric', month: 'short', day: 'numeric' };
                return date.toLocaleDateString('en-US', options);
            }
            
            // Format currency as ₱ with 2 decimal places
            function formatCurrency(amount) {
                return '₱' + parseFloat(amount).toFixed(2);
            }
            
            // Handle View Payroll button clicks
            const viewButtons = document.querySelectorAll('.view-payroll');
            viewButtons.forEach(button => {
                button.addEventListener('click', function() {
                    // Get employee name
                    const employeeId = this.getAttribute('data-employee');
                    const employeeSelect = document.getElementById('filter_employee');
                    let employeeName = '';
                    
                    for (let i = 0; i < employeeSelect.options.length; i++) {
                        if (employeeSelect.options[i].value == employeeId) {
                            employeeName = employeeSelect.options[i].text;
                            break;
                        }
                    }
                    
                    // Set modal values
                    document.getElementById('view_employee_name').textContent = employeeName;
                    document.getElementById('view_period').textContent = 
                        formatDate(this.getAttribute('data-period-start')) + ' to ' + 
                        formatDate(this.getAttribute('data-period-end'));
                    document.getElementById('view_payment_method').textContent = this.getAttribute('data-payment');
                    
                    document.getElementById('view_basic_pay').textContent = formatCurrency(this.getAttribute('data-basic'));
                    document.getElementById('view_overtime_pay').textContent = formatCurrency(this.getAttribute('data-overtime'));
                    document.getElementById('view_allowances').textContent = formatCurrency(this.getAttribute('data-allowances'));
                    document.getElementById('view_bonus').textContent = formatCurrency(this.getAttribute('data-bonus'));
                    document.getElementById('view_gross_pay').textContent = formatCurrency(this.getAttribute('data-gross'));
                    
                    document.getElementById('view_sss').textContent = formatCurrency(this.getAttribute('data-sss'));
                    document.getElementById('view_philhealth').textContent = formatCurrency(this.getAttribute('data-philhealth'));
                    document.getElementById('view_pagibig').textContent = formatCurrency(this.getAttribute('data-pagibig'));
                    document.getElementById('view_loans').textContent = formatCurrency(this.getAttribute('data-loans'));
                    document.getElementById('view_other_deductions').textContent = formatCurrency(this.getAttribute('data-other-deductions'));
                    document.getElementById('view_tax').textContent = formatCurrency(this.getAttribute('data-tax'));
                    document.getElementById('view_total_deductions').textContent = formatCurrency(this.getAttribute('data-total-deductions'));
                    
                    document.getElementById('view_net_pay').textContent = formatCurrency(this.getAttribute('data-net'));
                    document.getElementById('view_remarks').textContent = this.getAttribute('data-remarks') || 'No remarks';
                    
                    // Show the modal
                    const viewModal = new bootstrap.Modal(document.getElementById('viewPayrollModal'));
                    viewModal.show();
                });
            });
            
            // Handle Edit Payroll button clicks
            const editButtons = document.querySelectorAll('.edit-payroll');
            editButtons.forEach(button => {
                button.addEventListener('click', function() {
                    // Set form values for editing
                    document.getElementById('payrollModalLabel').textContent = 'Edit Payroll Entry';
                    document.getElementById('payroll_id').value = this.getAttribute('data-id');
                    document.getElementById('employee_id').value = this.getAttribute('data-employee');
                    document.getElementById('period_start').value = this.getAttribute('data-period-start');
                    document.getElementById('period_end').value = this.getAttribute('data-period-end');
                    document.getElementById('basic_pay').value = this.getAttribute('data-basic');
                    document.getElementById('overtime_pay').value = this.getAttribute('data-overtime');
                    document.getElementById('allowances').value = this.getAttribute('data-allowances');
                    document.getElementById('bonus').value = this.getAttribute('data-bonus');
                    document.getElementById('sss').value = this.getAttribute('data-sss');
                    document.getElementById('philhealth').value = this.getAttribute('data-philhealth');
                    document.getElementById('pagibig').value = this.getAttribute('data-pagibig');
                    document.getElementById('loans').value = this.getAttribute('data-loans');
                    document.getElementById('other_deductions').value = this.getAttribute('data-other-deductions');
                    document.getElementById('tax').value = this.getAttribute('data-tax');
                    document.getElementById('payment_method').value = this.getAttribute('data-payment');
                    document.getElementById('remarks').value = this.getAttribute('data-remarks');
                    
                    // Trigger calculations
                    calculatePayroll();
                    
                    // Show the modal
                    const payrollModal = new bootstrap.Modal(document.getElementById('payrollModal'));
                    payrollModal.show();
                });
            });
            
            // Handle Delete Payroll button clicks
            const deleteButtons = document.querySelectorAll('.delete-payroll');
            deleteButtons.forEach(button => {
                button.addEventListener('click', function() {
                    document.getElementById('delete_payroll_id').value = this.getAttribute('data-id');
                    
                    // Show the confirmation modal
                    const deleteModal = new bootstrap.Modal(document.getElementById('deleteModal'));
                    deleteModal.show();
                });
            });
            
            // Handle Add New Payroll button click - reset form
            document.querySelector('[data-bs-target="#payrollModal"]').addEventListener('click', function() {
                document.getElementById('payrollModalLabel').textContent = 'Add New Payroll Entry';
                document.getElementById('payrollForm').reset();
                document.getElementById('payroll_id').value = '';
                
                // Reset summary values
                const summaryElements = document.querySelectorAll('[id^="summary_"]');
                summaryElements.forEach(element => {
                    element.textContent = '₱0.00';
                });
            });
            
            // Print payslip functionality
            document.getElementById('printPayrollSlip').addEventListener('click', function() {
                const printWindow = window.open('', '_blank');
                
                const employeeName = document.getElementById('view_employee_name').textContent;
                const payrollPeriod = document.getElementById('view_period').textContent;
                const grossPay = document.getElementById('view_gross_pay').textContent;
                const totalDeductions = document.getElementById('view_total_deductions').textContent;
                const netPay = document.getElementById('view_net_pay').textContent;
                
                let printContent = `
                <!DOCTYPE html>
                <html>
                <head>
                    <title>Payslip - ${employeeName}</title>
                    <style>
                        body { font-family: Arial, sans-serif; margin: 0; padding: 20px; }
                        .header { text-align: center; margin-bottom: 20px; }
                        .company-name { font-size: 22px; font-weight: bold; }
                        .payslip-title { font-size: 18px; margin: 10px 0; }
                        .info-section { margin-bottom: 20px; }
                        .info-row { display: flex; margin-bottom: 5px; }
                        .info-label { width: 200px; font-weight: bold; }
                        table { width: 100%; border-collapse: collapse; margin-bottom: 20px; }
                        th, td { border: 1px solid #ddd; padding: 8px; text-align: left; }
                        th { background-color: #f2f2f2; }
                        .summary { font-weight: bold; }
                        .footer { margin-top: 50px; text-align: center; font-size: 12px; }
                        @media print {
                            body { padding: 0; margin: 0; }
                            button { display: none; }
                        }
                    </style>
                </head>
                <body>
                    <div class="header">
                        <div class="company-name">MALAYA SOLAR ENERGIES INC.</div>
                        <div class="payslip-title">EMPLOYEE PAYSLIP</div>
                    </div>
                    
                    <div class="info-section">
                        <div class="info-row">
                            <div class="info-label">Employee Name:</div>
                            <div>${employeeName}</div>
                        </div>
                        <div class="info-row">
                            <div class="info-label">Pay Period:</div>
                            <div>${payrollPeriod}</div>
                        </div>
                        <div class="info-row">
                            <div class="info-label">Payment Method:</div>
                            <div>${document.getElementById('view_payment_method').textContent}</div>
                        </div>
                    </div>
                    
                    <div class="row">
                        <div class="col">
                            <h4>Earnings</h4>
                            <table>
                                <tr>
                                    <th>Description</th>
                                    <th>Amount</th>
                                </tr>
                                <tr>
                                    <td>Basic Pay</td>
                                    <td>${document.getElementById('view_basic_pay').textContent}</td>
                                </tr>
                                <tr>
                                    <td>Overtime Pay</td>
                                    <td>${document.getElementById('view_overtime_pay').textContent}</td>
                                </tr>
                                <tr>
                                    <td>Allowances</td>
                                    <td>${document.getElementById('view_allowances').textContent}</td>
                                </tr>
                                <tr>
                                    <td>Bonus</td>
                                    <td>${document.getElementById('view_bonus').textContent}</td>
                                </tr>
                                <tr class="summary">
                                    <td>Gross Pay</td>
                                    <td>${grossPay}</td>
                                </tr>
                            </table>
                        </div>
                        
                        <div class="col">
                            <h4>Deductions</h4>
                            <table>
                                <tr>
                                    <th>Description</th>
                                    <th>Amount</th>
                                </tr>
                                <tr>
                                    <td>SSS</td>
                                    <td>${document.getElementById('view_sss').textContent}</td>
                                </tr>
                                <tr>
                                    <td>PhilHealth</td>
                                    <td>${document.getElementById('view_philhealth').textContent}</td>
                                </tr>
                                <tr>
                                    <td>Pag-IBIG</td>
                                    <td>${document.getElementById('view_pagibig').textContent}</td>
                                </tr>
                                <tr>
                                    <td>Loans</td>
                                    <td>${document.getElementById('view_loans').textContent}</td>
                                </tr>
                                <tr>
                                    <td>Other Deductions</td>
                                    <td>${document.getElementById('view_other_deductions').textContent}</td>
                                </tr>
                                <tr>
                                    <td>Tax</td>
                                    <td>${document.getElementById('view_tax').textContent}</td>
                                </tr>
                                <tr class="summary">
                                    <td>Total Deductions</td>
                                    <td>${totalDeductions}</td>
                                </tr>
                            </table>
                        </div>
                    </div>
                    
                    <table>
                        <tr class="summary">
                            <th>NET PAY</th>
                            <th>${netPay}</th>
                        </tr>
                    </table>
                    
                    <div class="info-section">
                        <h4>Remarks</h4>
                        <div style="border: 1px solid #ddd; padding: 10px;">
                            ${document.getElementById('view_remarks').textContent}
                        </div>
                    </div>
                    
                    <div class="footer">
                        <p>This is a computer-generated document. No signature required.</p>
                        <p>Malaya Solar Energies Inc. &copy; ${new Date().getFullYear()}</p>
                    </div>
                    
                    <div style="text-align: center; margin-top: 20px;">
                        <button onclick="window.print()">Print Payslip</button>
                    </div>
                </body>
                </html>
                `;
                
                printWindow.document.open();
                printWindow.document.write(printContent);
                printWindow.document.close();
                
                setTimeout(function() {
                    printWindow.focus();
                }, 500);
            });
        });
    </script>
</body>
</html>