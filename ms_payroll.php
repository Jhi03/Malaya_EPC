<?php
session_start();
$page_title = "PAYROLL MANAGEMENT";

// Check if user is logged in
if (!isset($_SESSION['username'])) {
    header("Location: ms_login.php");
    exit();
}

$conn = new mysqli("localhost", "root", "", "malayasol");

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Create payroll table if it doesn't exist
$conn->query("DROP TABLE IF EXISTS payroll");
$conn->query("CREATE TABLE IF NOT EXISTS payroll (
    id INT AUTO_INCREMENT PRIMARY KEY,
    employee_id INT NOT NULL,
    period_start DATE NOT NULL,
    period_end DATE NOT NULL,
    gross_pay DECIMAL(10,2) NOT NULL,
    basic_pay DECIMAL(10,2) NOT NULL,
    overtime_pay DECIMAL(10,2) DEFAULT 0,
    allowances DECIMAL(10,2) DEFAULT 0,
    bonus DECIMAL(10,2) DEFAULT 0,
    sss DECIMAL(10,2) NOT NULL,
    philhealth DECIMAL(10,2) NOT NULL,
    pagibig DECIMAL(10,2) NOT NULL,
    loans DECIMAL(10,2) DEFAULT 0,
    other_deductions DECIMAL(10,2) DEFAULT 0,
    total_deductions DECIMAL(10,2) NOT NULL,
    tax DECIMAL(10,2) NOT NULL,
    net_pay DECIMAL(10,2) NOT NULL,
    payment_method VARCHAR(50) DEFAULT 'Bank Transfer',
    remarks TEXT,
    date_generated TIMESTAMP DEFAULT CURRENT_TIMESTAMP
)");

// Process payroll generation
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST["generate_payroll"])) {
    $employee_id = $_POST["employee_id"];
    $start = $_POST["period_start"];
    $end = $_POST["period_end"];

    // Get basic employee information
    $emp = $conn->query("SELECT salary FROM personnel WHERE employee_id = $employee_id")->fetch_assoc();
    $basic_pay = $emp['salary'];
    
    // Calculate additional earnings
    $overtime_hours = isset($_POST["overtime_hours"]) ? floatval($_POST["overtime_hours"]) : 0;
    $overtime_rate = isset($_POST["overtime_rate"]) ? floatval($_POST["overtime_rate"]) : 1.5;
    $overtime_pay = ($basic_pay / 22 / 8) * $overtime_hours * $overtime_rate;
    
    $allowances = isset($_POST["allowances"]) ? floatval($_POST["allowances"]) : 0;
    $bonus = isset($_POST["bonus"]) ? floatval($_POST["bonus"]) : 0;
    
    // Calculate gross pay
    $gross_pay = $basic_pay + $overtime_pay + $allowances + $bonus;
    
    // Calculate deductions
    $sss = min($gross_pay * 0.045, 1125); // 4.5% SSS with cap at 25,000 salary
    $philhealth = min($gross_pay * 0.035, 875); // 3.5% PhilHealth with cap at 25,000
    $pagibig = min($gross_pay * 0.02, 100); // 2% Pag-IBIG up to max of 100
    
    $loans = isset($_POST["loans"]) ? floatval($_POST["loans"]) : 0;
    $other_deductions = isset($_POST["other_deductions"]) ? floatval($_POST["other_deductions"]) : 0;
    
    $total_deductions = $sss + $philhealth + $pagibig + $loans + $other_deductions;
    
    // Calculate tax (improved progressive taxation)
    $taxable_income = $gross_pay - $sss - $philhealth - $pagibig;
    $tax = calculateTax($taxable_income);
    
    // Calculate net pay
    $net_pay = $gross_pay - $total_deductions - $tax;
    
    $payment_method = $_POST["payment_method"];
    $remarks = $_POST["remarks"];

    // Insert into database
    $stmt = $conn->prepare("INSERT INTO payroll (
        employee_id, period_start, period_end, gross_pay, basic_pay, overtime_pay, 
        allowances, bonus, sss, philhealth, pagibig, loans, other_deductions, 
        total_deductions, tax, net_pay, payment_method, remarks
    ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
    
    $stmt->bind_param(
        "issddddddddddddsss", 
        $employee_id, $start, $end, $gross_pay, $basic_pay, $overtime_pay, 
        $allowances, $bonus, $sss, $philhealth, $pagibig, $loans, $other_deductions, 
        $total_deductions, $tax, $net_pay, $payment_method, $remarks
    );
    
    if ($stmt->execute()) {
        echo "<p class='highlight'>✅ Payroll generated successfully!</p>";
    } else {
        echo "<p style='color:red'>Error: " . $stmt->error . "</p>";
    }
    $stmt->close();
}

// Function to calculate tax using progressive tax brackets
function calculateTax($taxable_income) {
    // Monthly tax calculation (simplified Philippine tax structure)
    if ($taxable_income <= 20833) { // 250,000 annually
        return 0;
    } elseif ($taxable_income <= 33332) { // 400,000 annually
        return ($taxable_income - 20833) * 0.15;
    } elseif ($taxable_income <= 66666) { // 800,000 annually
        return 1875 + ($taxable_income - 33332) * 0.20;
    } elseif ($taxable_income <= 166666) { // 2,000,000 annually
        return 8541.8 + ($taxable_income - 66666) * 0.25;
    } elseif ($taxable_income <= 666666) { // 8,000,000 annually
        return 33541.8 + ($taxable_income - 166666) * 0.30;
    } else {
        return 183541.8 + ($taxable_income - 666666) * 0.35;
    }
}

if (isset($_GET['generate_department_report'])) {
    $department = $_GET['report_department'];
    $from_date = $_GET['report_date_from'];
    $to_date = $_GET['report_date_to'];
    
    // Build the department report query
    if ($department == 'all') {
        $dept_query = "SELECT pr.department,
            COUNT(DISTINCT p.employee_id) as employee_count,
            SUM(p.gross_pay) as total_gross,
            SUM(p.total_deductions) as total_deductions,
            SUM(p.tax) as total_tax,
            SUM(p.net_pay) as total_net
        FROM payroll p 
        JOIN personnel pr ON p.employee_id = pr.employee_id
        WHERE p.date_generated BETWEEN ? AND ?
        GROUP BY pr.department
        ORDER BY pr.department";
        
        $stmt = $conn->prepare($dept_query);
        $stmt->bind_param("ss", $from_date, $to_date);
    } else {
        $dept_query = "SELECT pr.department,
            COUNT(DISTINCT p.employee_id) as employee_count,
            SUM(p.gross_pay) as total_gross,
            SUM(p.total_deductions) as total_deductions,
            SUM(p.tax) as total_tax,
            SUM(p.net_pay) as total_net
        FROM payroll p 
        JOIN personnel pr ON p.employee_id = pr.employee_id
        WHERE p.date_generated BETWEEN ? AND ? AND pr.department = ?
        GROUP BY pr.department";
        
        $stmt = $conn->prepare($dept_query);
        $stmt->bind_param("sss", $from_date, $to_date, $department);
    }
    
    $stmt->execute();
    $dept_result = $stmt->get_result();
    
    echo "<div class='summary-box'>";
    echo "<h4>Department Report (" . date('M d, Y', strtotime($from_date)) . " to " . date('M d, Y', strtotime($to_date)) . ")</h4>";
    
    if ($dept_result->num_rows > 0) {
        while ($dept_row = $dept_result->fetch_assoc()) {
            echo $dept_row['department'] . "<br>";
            echo "Employee Count: " . $dept_row['employee_count'] . "<br>";
            echo "Total Gross Pay: ₱" . number_format($dept_row['total_gross'], 2) . "<br>";
            echo "Total Deductions: ₱" . number_format($dept_row['total_deductions'], 2) . "<br>";
            echo "Total Tax: ₱" . number_format($dept_row['total_tax'], 2) . "<br>";
            echo "Total Net Pay: ₱" . number_format($dept_row['total_net'], 2) . "<br>";
            echo "<br>";
        }
    } else {
        echo "No data found for the selected period.<br>";
    }
    echo "</div>"; // Close the summary-box div
} // Close the if statement
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
    <link href="css/ms_payroll.css" rel="stylesheet">
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

        <div class="payroll-form">
            <div class="tabs">
                <div class="tab active" onclick="openTab('generate')">Generate Payroll</div>
                <div class="tab" onclick="openTab('view')">View Payroll Records</div>
                <div class="tab" onclick="openTab('reports')">Reports</div>
            </div>

            <div id="generate" class="tab-content active">
                <form method="POST">
                    <div class="search-container">
                        <input type="text" id="employee_search" placeholder="Search employees by name, ID, or department..." onkeyup="searchEmployees()">
                        <button type="button" onclick="searchEmployees()">Search</button>
                    </div>

                    <label>Select Employee:</label>
                    <select name="employee_id" id="employee_select" required>
                        <option value="">-- Select Employee --</option>
                        <?php
                        $employees = $conn->query("SELECT * FROM personnel WHERE status = 'Active' ORDER BY full_name");
                        while($row = $employees->fetch_assoc()) {
                            echo "<option value='{$row['employee_id']}' data-name='{$row['full_name']}' data-department='{$row['department']}'>{$row['full_name']} ({$row['department']})</option>";
                        }
                        ?>
                    </select>

                    <div class="form-row">
                        <div class="form-col">
                            <label>Period Start:</label>
                            <input type="date" name="period_start" required>
                        </div>
                        <div class="form-col">
                            <label>Period End:</label>
                            <input type="date" name="period_end" required>
                        </div>
                    </div>

                    <h3>Earnings</h3>
                    <div class="form-row">
                        <div class="form-col">
                            <label>Overtime Hours:</label>
                            <input type="number" name="overtime_hours" step="0.01" min="0" value="0">
                        </div>
                        <div class="form-col">
                            <label>Overtime Rate:</label>
                            <select name="overtime_rate">
                                <option value="1.25">Regular (1.25x)</option>
                                <option value="1.5" selected>Regular OT (1.5x)</option>
                                <option value="1.3">Rest Day (1.3x)</option>
                                <option value="2">Holiday (2x)</option>
                            </select>
                        </div>
                    </div>

                    <div class="form-row">
                        <div class="form-col">
                            <label>Allowances:</label>
                            <input type="number" name="allowances" step="0.01" min="0" value="0">
                        </div>
                        <div class="form-col">
                            <label>Bonus:</label>
                            <input type="number" name="bonus" step="0.01" min="0" value="0">
                        </div>
                    </div>

                    <h3>Deductions</h3>
                    <div class="form-row">
                        <div class="form-col">
                            <label>Loans/Advances:</label>
                            <input type="number" name="loans" step="0.01" min="0" value="0">
                        </div>
                        <div class="form-col">
                            <label>Other Deductions:</label>
                            <input type="number" name="other_deductions" step="0.01" min="0" value="0">
                        </div>
                    </div>

                    <h3>Payment Details</h3>
                    <div class="form-row">
                        <div class="form-col">
                            <label>Payment Method:</label>
                            <select name="payment_method">
                                <option value="Bank Transfer">Bank Transfer</option>
                                <option value="Cash">Cash</option>
                                <option value="Check">Check</option>
                                <option value="E-wallet">E-wallet</option>
                            </select>
                        </div>
                        <div class="form-col">
                            <label>Remarks:</label>
                            <input type="text" name="remarks" placeholder="Any additional notes...">
                        </div>
                    </div>

                    <input type="submit" name="generate_payroll" value="Generate Payroll">
                </form>
            </div>

            <div id="view" class="tab-content">
                <div class="filter-container">
                    <h3>Filter Payroll Records</h3>
                    <form method="GET">
                        <div class="form-row">
                            <div class="form-col">
                                <label>Employee Name:</label>
                                <input type="text" name="filter_name" placeholder="Search by name..." 
                                    value="<?php echo isset($_GET['filter_name']) ? htmlspecialchars($_GET['filter_name']) : ''; ?>">
                            </div>
                            <div class="form-col">
                                <label>Department:</label>
                                <select name="filter_department">
                                    <option value="">All Departments</option>
                                    <?php
                                    $departments = $conn->query("SELECT DISTINCT department FROM personnel WHERE department != '' ORDER BY department");
                                    while($dept = $departments->fetch_assoc()) {
                                        $selected = (isset($_GET['filter_department']) && $_GET['filter_department'] == $dept['department']) ? 'selected' : '';
                                        echo "<option value='{$dept['department']}' $selected>{$dept['department']}</option>";
                                    }
                                    ?>
                                </select>
                            </div>
                        </div>
                        <div class="form-row">
                            <div class="form-col">
                                <label>Date From:</label>
                                <input type="date" name="filter_date_from"
                                    value="<?php echo isset($_GET['filter_date_from']) ? $_GET['filter_date_from'] : ''; ?>">
                            </div>
                            <div class="form-col">
                                <label>Date To:</label>
                                <input type="date" name="filter_date_to"
                                    value="<?php echo isset($_GET['filter_date_to']) ? $_GET['filter_date_to'] : ''; ?>">
                            </div>
                        </div>
                        <input type="submit" value="Apply Filters">
                    </form>
                </div>

                <table>
                    <tr>
                        <th>Name</th>
                        <th>Department</th>
                        <th>Period</th>
                        <th>Gross Pay</th>
                        <th>Deductions</th>
                        <th>Tax</th>
                        <th>Net Pay</th>
                        <th>Date Generated</th>
                        <th>Actions</th>
                    </tr>
                    <?php
                    // Build the query with filters
                    $query = "SELECT p.*, pr.full_name, pr.department FROM payroll p 
                            JOIN personnel pr ON p.employee_id = pr.employee_id WHERE 1=1";
                    
                    $params = [];
                    $types = "";
                    
                    if (isset($_GET['filter_name']) && !empty($_GET['filter_name'])) {
                        $query .= " AND pr.full_name LIKE ?";
                        $name_param = '%' . $_GET['filter_name'] . '%';
                        $params[] = $name_param;
                        $types .= "s";
                    }
                    
                    if (isset($_GET['filter_department']) && !empty($_GET['filter_department'])) {
                        $query .= " AND pr.department = ?";
                        $params[] = $_GET['filter_department'];
                        $types .= "s";
                    }
                    
                    if (isset($_GET['filter_date_from']) && !empty($_GET['filter_date_from'])) {
                        $query .= " AND p.date_generated >= ?";
                        $params[] = $_GET['filter_date_from'] . ' 00:00:00';
                        $types .= "s";
                    }
                    
                    if (isset($_GET['filter_date_to']) && !empty($_GET['filter_date_to'])) {
                        $query .= " AND p.date_generated <= ?";
                        $params[] = $_GET['filter_date_to'] . ' 23:59:59';
                        $types .= "s";
                    }
                    
                    $query .= " ORDER BY p.date_generated DESC";
                    
                    // Pagination
                    $records_per_page = 10;
                    $page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
                    $offset = ($page - 1) * $records_per_page;
                    
                    $query .= " LIMIT $offset, $records_per_page";
                    
                    // Prepare and execute the query
                    $stmt = $conn->prepare($query);
                    if (!empty($params)) {
                        $stmt->bind_param($types, ...$params);
                    }
                    $stmt->execute();
                    $result = $stmt->get_result();
                    
                    // Count total records for pagination
                    $count_query = "SELECT COUNT(*) as total FROM payroll p 
                                JOIN personnel pr ON p.employee_id = pr.employee_id WHERE 1=1";
                    
                    if (isset($_GET['filter_name']) && !empty($_GET['filter_name'])) {
                        $count_query .= " AND pr.full_name LIKE ?";
                    }
                    
                    if (isset($_GET['filter_department']) && !empty($_GET['filter_department'])) {
                        $count_query .= " AND pr.department = ?";
                    }
                    
                    if (isset($_GET['filter_date_from']) && !empty($_GET['filter_date_from'])) {
                        $count_query .= " AND p.date_generated >= ?";
                    }
                    
                    if (isset($_GET['filter_date_to']) && !empty($_GET['filter_date_to'])) {
                        $count_query .= " AND p.date_generated <= ?";
                    }
                    
                    $count_stmt = $conn->prepare($count_query);
                    if (!empty($params)) {
                        $count_stmt->bind_param($types, ...$params);
                    }
                    $count_stmt->execute();
                    $count_result = $count_stmt->get_result();
                    $count_row = $count_result->fetch_assoc();
                    $total_records = $count_row['total'];
                    $total_pages = ceil($total_records / $records_per_page);
                    
                    while ($row = $result->fetch_assoc()) {
                        echo "<tr>
                            <td>{$row['full_name']}</td>
                            <td>{$row['department']}</td>
                            <td>{$row['period_start']} to {$row['period_end']}</td>
                            <td>₱" . number_format($row['gross_pay'], 2) . "</td>
                            <td>₱" . number_format($row['total_deductions'], 2) . "</td>
                            <td>₱" . number_format($row['tax'], 2) . "</td>
                            <td><strong>₱" . number_format($row['net_pay'], 2) . "</strong></td>
                            <td>{$row['date_generated']}</td>
                            <td>
                                <a href='#' class='btn' onclick='viewPayslip({$row['id']})'>View</a>
                            </td>
                        </tr>";
                    }
                    
                    if ($result->num_rows == 0) {
                        echo "<tr><td colspan='9' style='text-align:center'>No records found</td></tr>";
                    }
                    ?>
                </table>
                
                <!-- Pagination links -->
                <?php if ($total_pages > 1): ?>
                <div class="pagination">
                    <?php
                    $filter_params = '';
                    if (isset($_GET['filter_name'])) $filter_params .= "&filter_name=" . urlencode($_GET['filter_name']);
                    if (isset($_GET['filter_department'])) $filter_params .= "&filter_department=" . urlencode($_GET['filter_department']);
                    if (isset($_GET['filter_date_from'])) $filter_params .= "&filter_date_from=" . urlencode($_GET['filter_date_from']);
                    if (isset($_GET['filter_date_to'])) $filter_params .= "&filter_date_to=" . urlencode($_GET['filter_date_to']);
                    
                    // Previous page link
                    if ($page > 1) {
                        echo "<a href='?page=" . ($page - 1) . $filter_params . "'>Previous</a>";
                    }
                    
                    // Page numbers
                    $start_page = max(1, $page - 2);
                    $end_page = min($total_pages, $page + 2);
                    
                    for ($i = $start_page; $i <= $end_page; $i++) {
                        $active = $i == $page ? 'active' : '';
                        echo "<a class='$active' href='?page=$i$filter_params'>$i</a>";
                    }
                    
                    // Next page link
                    if ($page < $total_pages) {
                        echo "<a href='?page=" . ($page + 1) . $filter_params . "'>Next</a>";
                    }
                    ?>
                </div>
                <?php endif; ?>
            </div>

            <div id="reports" class="tab-content">
                <h3>Payroll Reports</h3>
                
                <div class="form-row">
                    <div class="form-col">
                        <h4>Monthly Summary</h4>
                        <form method="GET" action="#reports">
                            <div class="form-row">
                                <div class="form-col">
                                    <label>Month:</label>
                                    <select name="report_month" required>
                                        <?php
                                        for ($i = 1; $i <= 12; $i++) {
                                            $month = date('F', mktime(0, 0, 0, $i, 1));
                                            $selected = (isset($_GET['report_month']) && $_GET['report_month'] == $i) ? 'selected' : '';
                                            echo "<option value='$i' $selected>$month</option>";
                                        }
                                        ?>
                                    </select>
                                </div>
                                <div class="form-col">
                                    <label>Year:</label>
                                    <select name="report_year" required>
                                        <?php
                                        $current_year = date('Y');
                                        for ($i = $current_year - 2; $i <= $current_year + 1; $i++) {
                                            $selected = (isset($_GET['report_year']) && $_GET['report_year'] == $i) ? 'selected' : '';
                                            echo "<option value='$i' $selected>$i</option>";
                                        }
                                        ?>
                                    </select>
                                </div>
                            </div>
                            <input type="submit" name="generate_monthly_report" value="Generate Report">
                        </form>
                        
                        <?php
                        if (isset($_GET['generate_monthly_report'])) {
                            $month = $_GET['report_month'];
                            $year = $_GET['report_year'];
                            
                            $start_date = "$year-$month-01";
                            $end_date = date('Y-m-t', strtotime($start_date));
                            
                            $report_query = "SELECT 
                                COUNT(DISTINCT employee_id) as employee_count,
                                SUM(gross_pay) as total_gross,
                                SUM(basic_pay) as total_basic,
                                SUM(overtime_pay) as total_overtime,
                                SUM(allowances) as total_allowances,
                                SUM(bonus) as total_bonus,
                                SUM(sss) as total_sss,
                                SUM(philhealth) as total_philhealth,
                                SUM(pagibig) as total_pagibig,
                                SUM(loans) as total_loans,
                                SUM(other_deductions) as total_other_deductions,
                                SUM(tax) as total_tax,
                                SUM(net_pay) as total_net
                            FROM payroll 
                            WHERE date_generated BETWEEN ? AND ?";
                            
                            $stmt = $conn->prepare($report_query);
                            $stmt->bind_param("ss", $start_date, $end_date);
                            $stmt->execute();
                            $report_result = $stmt->get_result();
                            $report = $report_result->fetch_assoc();
                            
                            echo "<div class='summary-box'>";
                            echo "<h4>Monthly Summary for " . date('F Y', strtotime($start_date)) . "</h4>";
                            echo "<p>Total Employees: {$report['employee_count']}</p>";
                            echo "<p>Total Gross Pay: ₱" . number_format($report['total_gross'], 2) . "</p>";
                            echo "<p>Total Basic Pay: ₱" . number_format($report['total_basic'], 2) . "</p>";
                            echo "<p>Total Overtime Pay: ₱" . number_format($report['total_overtime'], 2) . "</p>";
                            echo "<p>Total Allowances: ₱" . number_format($report['total_allowances'], 2) . "</p>";
                            echo "<p>Total Bonus: ₱" . number_format($report['total_bonus'], 2) . "</p>";
                            echo "<p>Total SSS Contributions: ₱" . number_format($report['total_sss'], 2) . "</p>";
                            echo "<p>Total PhilHealth Contributions: ₱" . number_format($report['total_philhealth'], 2) . "</p>";
                            echo "<p>Total Pag-IBIG Contributions: ₱" . number_format($report['total_pagibig'], 2) . "</p>";
                            echo "<p>Total Loans/Advances: ₱" . number_format($report['total_loans'], 2) . "</p>";
                            echo "<p>Total Other Deductions: ₱" . number_format($report['total_other_deductions'], 2) . "</p>";
                            echo "<p>Total Tax Withheld: ₱" . number_format($report['total_tax'], 2) . "</p>";
                            echo "<p><strong>Total Net Pay: ₱" . number_format($report['total_net'], 2) . "</strong></p>";
                            echo "</div>";
                        }
                        ?>
                    </div>
                    
                    <div class="form-col">
                        <h4>Department Summary</h4>
                        <form method="GET" action="#reports">
                            <div class="form-row">
                                <div class="form-col">
                                    <label>Department:</label>
                                    <select name="report_department" required>
                                        <option value="all">All Departments</option>
                                        <?php
                                        $departments = $conn->query("SELECT DISTINCT department FROM personnel WHERE department != '' ORDER BY department");
                                        while($dept = $departments->fetch_assoc()) {
                                            $selected = (isset($_GET['report_department']) && $_GET['report_department'] == $dept['department']) ? 'selected' : '';
                                            echo "<option value='{$dept['department']}' $selected>{$dept['department']}</option>";
                                        }
                                        ?>
                                    </select>
                                </div>
                            </div>
                            <div class="form-row">
                                <div class="form-col">
                                    <label>From:</label>
                                    <input type="date" name="report_date_from" required 
                                        value="<?php echo isset($_GET['report_date_from']) ? $_GET['report_date_from'] : ''; ?>">
                                </div>
                                <div class="form-col">
                                    <label>To:</label>
                                    <input type="date" name="report_date_to" required
                                        value="<?php echo isset($_GET['report_date_to']) ? $_GET['report_date_to'] : ''; ?>">
                                </div>
                            </div>
                            <input type="submit" name="generate_department_report" value="Generate Report">
                        </form>?>
                    </div>
                </div>
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

        function openTab(tabName) {
            // Hide all tab content
            var tabContents = document.getElementsByClassName("tab-content");
            for (var i = 0; i < tabContents.length; i++) {
                tabContents[i].classList.remove("active");
            }
            
            // Remove active class from all tabs
            var tabs = document.getElementsByClassName("tab");
            for (var i = 0; i < tabs.length; i++) {
                tabs[i].classList.remove("active");
            }
            
            // Show the specific tab content
            document.getElementById(tabName).classList.add("active");
            
            // Add active class to the clicked tab
            var activeTab = document.querySelector(".tab[onclick=\"openTab('" + tabName + "')\"]");
            if (activeTab) {
                activeTab.classList.add("active");
            }
        }

        // Function for employee search
        function searchEmployees() {
            var input = document.getElementById("employee_search").value.toLowerCase();
            var select = document.getElementById("employee_select");
            var options = select.getElementsByTagName("option");
            
            for (var i = 0; i < options.length; i++) {
                var option = options[i];
                var name = option.getAttribute("data-name") || "";
                var department = option.getAttribute("data-department") || "";
                var text = (name + " " + department).toLowerCase();
                
                if (text.indexOf(input) > -1 || option.value === "") {
                    option.style.display = "";
                } else {
                    option.style.display = "none";
                }
            }
        }

        // Function to view payslip (placeholder)
        function viewPayslip(id) {
            alert("Viewing payslip ID: " + id);
            // You can replace this with your actual implementation
            // For example, open a modal or navigate to a payslip detail page
        }
    </script>
</body>
</html>