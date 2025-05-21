<?php
    include('validate_login.php');
    $page_title = "REPORTS";
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
    
    // Process form submissions
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        // Export Project Expense Report
        if (isset($_POST['export_project'])) {
            $project_id = $_POST['project_id'];
            
            // Get project details
            $project_query = "SELECT project_code, project_name FROM projects WHERE project_id = ?";
            $stmt = $conn->prepare($project_query);
            $stmt->bind_param("i", $project_id);
            $stmt->execute();
            $project_result = $stmt->get_result();
            $project = $project_result->fetch_assoc();
            $stmt->close();
            
            // Get project expenses
            $expense_query = "SELECT category, subcategory, description, budget, actual, payee, variance, tax, remarks 
                             FROM project_expense 
                             WHERE project_id = ? 
                             ORDER BY record_date DESC";
            $stmt = $conn->prepare($expense_query);
            $stmt->bind_param("i", $project_id);
            $stmt->execute();
            $expense_result = $stmt->get_result();
            $stmt->close();
            
            // Generate CSV file
            $filename = $project['project_code'] . '_' . date('Y-m-d') . '.csv';
            
            header('Content-Type: text/csv');
            header('Content-Disposition: attachment; filename="' . $filename . '"');
            
            $output = fopen('php://output', 'w');
            
            // Add header row
            fputcsv($output, array(
                'PROJECT: ' . $project['project_name'] . ' (' . $project['project_code'] . ')',
                'EXPORT DATE: ' . date('Y-m-d')
            ));
            fputcsv($output, array()); // Empty row for spacing
            
            // Add column headers
            fputcsv($output, array(
                'CATEGORY', 
                'SUBCATEGORY', 
                'DESCRIPTION', 
                'BUDGET', 
                'ACTUAL', 
                'PAYEE', 
                'VARIANCE', 
                'WITH TAX Y/N',
                'REMARKS'
            ));
            
            // Add data rows
            $total_budget = 0;
            $total_actual = 0;
            $total_variance = 0;
            $total_tax = 0;
            
            while ($row = $expense_result->fetch_assoc()) {
                // Calculate tax status
                $tax_status = ($row['tax'] > 0) ? 'Y' : 'N';
                
                fputcsv($output, array(
                    $row['category'],
                    $row['subcategory'] ?? 'N/A',
                    $row['description'],
                    $row['budget'],
                    $row['actual'],
                    $row['payee'],
                    $row['variance'],
                    $tax_status,
                    $row['remarks']
                ));
                
                // Add to totals
                $total_budget += $row['budget'];
                $total_actual += $row['actual'];
                $total_variance += $row['variance'];
                $total_tax += $row['tax'];
            }
            
            // Add footer with totals
            fputcsv($output, array()); // Empty row for spacing
            fputcsv($output, array(
                'TOTALS',
                '',
                '',
                $total_budget,
                $total_actual,
                '',
                $total_variance,
                '',
                ''
            ));
            
            fputcsv($output, array(
                'TOTAL TAX',
                '',
                '',
                '',
                $total_tax,
                '',
                '',
                '',
                ''
            ));
            
            fclose($output);
            exit();
        }
        
        // Export Payroll Report
        else if (isset($_POST['export_payroll'])) {
            $employee_id = $_POST['employee_id'];
            $start_date = $_POST['start_date'] ?? '';
            $end_date = $_POST['end_date'] ?? '';
            
            // Get employee details
            $employee_query = "SELECT first_name, last_name FROM employees WHERE id = ?";
            $stmt = $conn->prepare($employee_query);
            $stmt->bind_param("i", $employee_id);
            $stmt->execute();
            $employee_result = $stmt->get_result();
            $employee = $employee_result->fetch_assoc();
            $stmt->close();
            
            if (!$employee) {
                echo "Employee not found";
                exit();
            }
            
            // Build payroll query with date range filter if provided
            $payroll_query = "SELECT period_start, period_end, gross_pay, basic_pay, overtime_pay, 
                            allowances, bonus, sss, philhealth, pagibig, loans, 
                            other_deductions, total_deductions, tax, net_pay, 
                            payment_method, remarks, date_generated 
                            FROM payroll WHERE employee_id = ?";
            
            $params = array($employee_id);
            $types = "i";
            
            if (!empty($start_date) && !empty($end_date)) {
                $payroll_query .= " AND period_start >= ? AND period_end <= ?";
                $params[] = $start_date;
                $params[] = $end_date;
                $types .= "ss";
            }
            
            $payroll_query .= " ORDER BY period_start DESC";
            
            $stmt = $conn->prepare($payroll_query);
            $stmt->bind_param($types, ...$params);
            $stmt->execute();
            $payroll_result = $stmt->get_result();
            $stmt->close();
            
            // Generate CSV file
            $filename = $employee['last_name'] . $employee['first_name'] . '_' . date('Y-m-d') . '.csv';
            
            header('Content-Type: text/csv');
            header('Content-Disposition: attachment; filename="' . $filename . '"');
            
            $output = fopen('php://output', 'w');
            
            // Add header row
            fputcsv($output, array(
                'EMPLOYEE: ' . $employee['first_name'] . ' ' . $employee['last_name'],
                'EXPORT DATE: ' . date('Y-m-d')
            ));
            
            if (!empty($start_date) && !empty($end_date)) {
                fputcsv($output, array(
                    'PERIOD: ' . $start_date . ' to ' . $end_date
                ));
            }
            
            fputcsv($output, array()); // Empty row for spacing
            
            // Add column headers
            fputcsv($output, array(
                'PERIOD START',
                'PERIOD END',
                'GROSS PAY',
                'BASIC PAY',
                'OVERTIME PAY',
                'ALLOWANCES',
                'BONUS',
                'SSS',
                'PHILHEALTH',
                'PAGIBIG',
                'LOANS',
                'OTHER DEDUCTIONS',
                'TOTAL DEDUCTIONS',
                'TAX',
                'NET PAY',
                'PAYMENT METHOD',
                'REMARKS',
                'DATE GENERATED'
            ));
            
            // Add data rows
            $total_gross = 0;
            $total_basic = 0;
            $total_overtime = 0;
            $total_allowances = 0;
            $total_bonus = 0;
            $total_sss = 0;
            $total_philhealth = 0;
            $total_pagibig = 0;
            $total_loans = 0;
            $total_other_deductions = 0;
            $total_deductions = 0;
            $total_tax = 0;
            $total_net = 0;
            
            while ($row = $payroll_result->fetch_assoc()) {
                fputcsv($output, array(
                    $row['period_start'],
                    $row['period_end'],
                    $row['gross_pay'],
                    $row['basic_pay'],
                    $row['overtime_pay'],
                    $row['allowances'],
                    $row['bonus'],
                    $row['sss'],
                    $row['philhealth'],
                    $row['pagibig'],
                    $row['loans'],
                    $row['other_deductions'],
                    $row['total_deductions'],
                    $row['tax'],
                    $row['net_pay'],
                    $row['payment_method'],
                    $row['remarks'],
                    $row['date_generated']
                ));
                
                // Add to totals
                $total_gross += $row['gross_pay'];
                $total_basic += $row['basic_pay'];
                $total_overtime += $row['overtime_pay'];
                $total_allowances += $row['allowances'];
                $total_bonus += $row['bonus'];
                $total_sss += $row['sss'];
                $total_philhealth += $row['philhealth'];
                $total_pagibig += $row['pagibig'];
                $total_loans += $row['loans'];
                $total_other_deductions += $row['other_deductions'];
                $total_deductions += $row['total_deductions'];
                $total_tax += $row['tax'];
                $total_net += $row['net_pay'];
            }
            
            // Add footer with totals
            fputcsv($output, array()); // Empty row for spacing
            fputcsv($output, array(
                'TOTALS',
                '',
                $total_gross,
                $total_basic,
                $total_overtime,
                $total_allowances,
                $total_bonus,
                $total_sss,
                $total_philhealth,
                $total_pagibig,
                $total_loans,
                $total_other_deductions,
                $total_deductions,
                $total_tax,
                $total_net,
                '',
                '',
                ''
            ));
            
            fclose($output);
            exit();
        }
    }
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
    <link href="css/ms_reports.css" rel="stylesheet">
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
            <div class="container">
                <h2 class="text-center mb-4">Export Reports</h2>
                
                <div class="row">
                    <!-- Project Expenses Export Panel -->
                    <div class="col-md-6 mb-4">
                        <div class="card">
                            <div class="card-header bg-primary text-white">
                                <h5 class="mb-0">Project Expense Reports</h5>
                            </div>
                            <div class="card-body">
                                <form method="post" action="">
                                    <div class="mb-3">
                                        <label for="project_id" class="form-label">Select Project</label>
                                        <select class="form-select" id="project_id" name="project_id" required>
                                            <option value="">-- Select Project --</option>
                                            <?php
                                            $project_list_query = "SELECT project_id, project_code, project_name FROM projects ORDER BY project_name";
                                            $project_list_result = $conn->query($project_list_query);
                                            
                                            while ($project = $project_list_result->fetch_assoc()) {
                                                echo '<option value="' . $project['project_id'] . '">' . $project['project_name'] . ' (' . $project['project_code'] . ')</option>';
                                            }
                                            ?>
                                        </select>
                                    </div>
                                    
                                    <div class="d-grid">
                                        <button type="submit" name="export_project" class="btn btn-primary">
                                            <i class="bi bi-file-earmark-excel"></i> Export Project Expenses
                                        </button>
                                    </div>
                                </form>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Payroll Export Panel -->
                    <div class="col-md-6 mb-4">
                        <div class="card">
                            <div class="card-header bg-success text-white">
                                <h5 class="mb-0">Payroll Reports</h5>
                            </div>
                            <div class="card-body">
                                <form method="post" action="">
                                    <div class="mb-3">
                                        <label for="employee_id" class="form-label">Select Employee</label>
                                        <select class="form-select" id="employee_id" name="employee_id" required>
                                            <option value="">-- Select Employee --</option>
                                            <?php
                                            // You might need to adjust this query based on your actual employees table structure
                                            $employee_list_query = "SELECT id, first_name, last_name FROM employees ORDER BY last_name, first_name";
                                            $employee_list_result = $conn->query($employee_list_query);
                                            
                                            if ($employee_list_result) {
                                                while ($employee = $employee_list_result->fetch_assoc()) {
                                                    echo '<option value="' . $employee['id'] . '">' . $employee['last_name'] . ', ' . $employee['first_name'] . '</option>';
                                                }
                                            }
                                            ?>
                                        </select>
                                    </div>
                                    
                                    <div class="row mb-3">
                                        <div class="col-md-6">
                                            <label for="start_date" class="form-label">Start Date</label>
                                            <input type="date" class="form-control" id="start_date" name="start_date">
                                        </div>
                                        <div class="col-md-6">
                                            <label for="end_date" class="form-label">End Date</label>
                                            <input type="date" class="form-control" id="end_date" name="end_date">
                                        </div>
                                    </div>
                                    
                                    <div class="d-grid">
                                        <button type="submit" name="export_payroll" class="btn btn-success">
                                            <i class="bi bi-file-earmark-excel"></i> Export Payroll Records
                                        </button>
                                    </div>
                                </form>
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- Reports Guide Section -->
                <div class="row mt-4">
                    <div class="col-12">
                        <div class="card">
                            <div class="card-header bg-info text-white">
                                <h5 class="mb-0">Reports Guide</h5>
                            </div>
                            <div class="card-body">
                                <div class="row">
                                    <div class="col-md-6">
                                        <h6>Project Expense Reports</h6>
                                        <ul>
                                            <li>Select a project to export all expense records associated with it</li>
                                            <li>The report includes categories, descriptions, budgets, actual expenses, and more</li>
                                            <li>Each report includes subtotals for all numeric fields</li>
                                            <li>Filename format: PROJECT_CODE_YYYY-MM-DD.csv</li>
                                        </ul>
                                    </div>
                                    <div class="col-md-6">
                                        <h6>Payroll Reports</h6>
                                        <ul>
                                            <li>Select an employee to export their payroll records</li>
                                            <li>Optionally select a date range to filter payroll records</li>
                                            <li>Reports include all payroll details such as gross pay, deductions, and net pay</li>
                                            <li>Each report includes subtotals for all numeric fields</li>
                                            <li>Filename format: LASTNAME_FIRSTNAME_YYYY-MM-DD.csv</li>
                                        </ul>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <script>
        // Validate date range selection
        document.addEventListener('DOMContentLoaded', function() {
            const payrollForm = document.querySelector('form[name="export_payroll"]');
            const startDateInput = document.getElementById('start_date');
            const endDateInput = document.getElementById('end_date');
            
            if (payrollForm) {
                payrollForm.addEventListener('submit', function(e) {
                    // If one date is filled, both should be filled
                    if ((startDateInput.value && !endDateInput.value) || (!startDateInput.value && endDateInput.value)) {
                        e.preventDefault();
                        alert('Please select both start and end dates, or leave both empty for all records');
                    }
                    
                    // End date should be after start date
                    if (startDateInput.value && endDateInput.value) {
                        const startDate = new Date(startDateInput.value);
                        const endDate = new Date(endDateInput.value);
                        
                        if (endDate < startDate) {
                            e.preventDefault();
                            alert('End date must be after start date');
                        }
                    }
                });
            }
        });
    </script>
</body>
</html>