<?php
include('validate_login.php');
require_once 'activity_logger.php';
    
$page_title = "REPORTS";

// Database connection
$host = 'localhost';
$username = "u188693564_adminsolar";
$password = "@Malayasolarenergies1";
$dbname = "u188693564_malayasol";
$conn = new mysqli($host, $username, $password, $dbname);

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

if (!isset($_SESSION['user_id'])) {
    header("Location: ms_login.php");
    exit();
}

$user_id = $_SESSION['user_id'];

// Debug: Log all POST data
error_log("POST Data: " . print_r($_POST, true));
error_log("REQUEST METHOD: " . $_SERVER['REQUEST_METHOD']);

// GET Test Export Handler (alternative method)
if (isset($_GET['test_export_get'])) {
    error_log("GET Test export handler triggered!");
    
    try {
        $filename = 'get_test_export_' . date('Y-m-d_H-i-s') . '.csv';
        error_log("Attempting to export file via GET: " . $filename);
        
        // Clear any output buffer
        if (ob_get_level()) {
            ob_end_clean();
        }
        
        header('Content-Type: text/csv');
        header('Content-Disposition: attachment; filename="' . $filename . '"');
        header('Pragma: no-cache');
        header('Expires: 0');
        header('Cache-Control: must-revalidate, post-check=0, pre-check=0');
        
        $output = fopen('php://output', 'w');
        
        // Simple test data
        fputcsv($output, ['GET TEST EXPORT REPORT']);
        fputcsv($output, ['Generated: ' . date('Y-m-d H:i:s')]);
        fputcsv($output, ['Method: GET']);
        fputcsv($output, ['Status: Working']);
        fputcsv($output, []);
        fputcsv($output, ['Column 1', 'Column 2', 'Column 3']);
        fputcsv($output, ['GET Test 1', 'GET Test 2', 'GET Test 3']);
        
        fclose($output);
        error_log("GET Export completed successfully");
        exit();
        
    } catch (Exception $e) {
        error_log("GET Export error: " . $e->getMessage());
        die("GET Export error: " . $e->getMessage());
    }
}

// Process form submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    error_log("=== POST METHOD DETECTED ===");
    error_log("Available POST keys: " . implode(', ', array_keys($_POST)));
    
    // Test Export - with debugging
    if (isset($_POST['test_export'])) {
        error_log("Test export handler triggered!");
        
        // Check if headers have been sent
        if (headers_sent()) {
            error_log("Headers already sent - cannot export");
            die("Headers already sent - cannot export file");
        }
        
        try {
            $filename = 'test_export_' . date('Y-m-d_H-i-s') . '.csv';
            error_log("Attempting to export file: " . $filename);
            
            // Clear any output buffer
            if (ob_get_level()) {
                ob_end_clean();
            }
            
            header('Content-Type: text/csv');
            header('Content-Disposition: attachment; filename="' . $filename . '"');
            header('Pragma: no-cache');
            header('Expires: 0');
            header('Cache-Control: must-revalidate, post-check=0, pre-check=0');
            
            $output = fopen('php://output', 'w');
            
            if (!$output) {
                error_log("Failed to open php://output");
                die("Failed to create output stream");
            }
            
            // Simple test data
            fputcsv($output, ['TEST EXPORT REPORT']);
            fputcsv($output, ['Generated: ' . date('Y-m-d H:i:s')]);
            fputcsv($output, ['Status: Working']);
            fputcsv($output, ['User Agent: ' . $_SERVER['HTTP_USER_AGENT']]);
            fputcsv($output, []);
            fputcsv($output, ['Column 1', 'Column 2', 'Column 3']);
            fputcsv($output, ['Test Data 1', 'Test Data 2', 'Test Data 3']);
            fputcsv($output, ['Sample Row 1', 'Sample Row 2', 'Sample Row 3']);
            
            fclose($output);

            logUserActivity(
                'export', 
                'ms_reports.php', 
                "export report"
            );
            
            error_log("Export completed successfully");
            exit();
            
        } catch (Exception $e) {
            error_log("Export error: " . $e->getMessage());
            die("Export error: " . $e->getMessage());
        }
    }
    
    if (isset($_POST['export_project_expenses'])) {
        error_log("=== PROJECT EXPORT HANDLER TRIGGERED ===");
        
        $project_id = intval($_POST['project_id']);
        $start_date = $_POST['start_date'] ?? '';
        $end_date = $_POST['end_date'] ?? '';
        
        error_log("Project ID: " . $project_id);
        error_log("Date range: " . $start_date . " to " . $end_date);
        
        // Validate project exists
        if ($project_id <= 0) {
            die("Please select a valid project.");
        }
        
        try {
            // Get project details
            $project_query = "SELECT project_code, project_name, first_name, last_name, company_name FROM projects WHERE project_id = ?";
            $stmt = $conn->prepare($project_query);
            
            if (!$stmt) {
                error_log("Database error: " . $conn->error);
                die("Database error occurred.");
            }
            
            $stmt->bind_param("i", $project_id);
            $stmt->execute();
            $project_result = $stmt->get_result();
            
            if ($project_result->num_rows === 0) {
                die("Project not found.");
            }
            
            $project = $project_result->fetch_assoc();
            $stmt->close();
            
            error_log("Project found: " . $project['project_name']);
            
            // Build expense query
            $expense_query = "SELECT e.*, 
                            CONCAT(emp.first_name, ' ', emp.last_name) as creator_name
                            FROM expense e
                            LEFT JOIN users u ON e.created_by = u.user_id
                            LEFT JOIN employee emp ON u.employee_id = emp.employee_id
                            WHERE e.project_id = ?";
            
            $params = array($project_id);
            $types = "i";
            
            if (!empty($start_date) && !empty($end_date)) {
                $expense_query .= " AND e.purchase_date >= ? AND e.purchase_date <= ?";
                $params[] = $start_date;
                $params[] = $end_date;
                $types .= "ss";
            }
            
            $expense_query .= " ORDER BY e.purchase_date DESC";
            
            $stmt = $conn->prepare($expense_query);
            if (!$stmt) {
                error_log("Expense query error: " . $conn->error);
                die("Database query error.");
            }
            
            $stmt->bind_param($types, ...$params);
            $stmt->execute();
            $expense_result = $stmt->get_result();
            
            error_log("Found " . $expense_result->num_rows . " expense records");
            
            // Generate safe filename
            $project_name_safe = preg_replace('/[^a-zA-Z0-9_-]/', '_', $project['project_code']);
            $date_suffix = !empty($start_date) && !empty($end_date) ? "_{$start_date}_to_{$end_date}" : "";
            $filename = "{$project_name_safe}_Expenses{$date_suffix}_" . date('Y-m-d') . '.csv';
            
            error_log("Generating file: " . $filename);
            
            // Clear any output buffer
            if (ob_get_level()) {
                ob_end_clean();
            }
            
            header('Content-Type: text/csv; charset=utf-8');
            header('Content-Disposition: attachment; filename="' . $filename . '"');
            header('Pragma: no-cache');
            header('Expires: 0');
            header('Cache-Control: must-revalidate, post-check=0, pre-check=0');
            
            $output = fopen('php://output', 'w');
            
            if (!$output) {
                error_log("Failed to open php://output");
                die("Failed to create output stream");
            }
            
            // Add UTF-8 BOM for Excel compatibility
            fwrite($output, "\xEF\xBB\xBF");
            
            // Add header information
            fputcsv($output, ['PROJECT EXPENSE REPORT']);
            fputcsv($output, ['Project: ' . $project['project_name']]);
            fputcsv($output, ['Code: ' . $project['project_code']]);
            fputcsv($output, ['Client: ' . $project['first_name'] . ' ' . $project['last_name']]);
            fputcsv($output, ['Company: ' . $project['company_name']]);
            if (!empty($start_date) && !empty($end_date)) {
                fputcsv($output, ['Period: ' . $start_date . ' to ' . $end_date]);
            }
            fputcsv($output, ['Generated: ' . date('Y-m-d H:i:s T')]);
            fputcsv($output, ['Generated By: ' . ($_SESSION['username'] ?? 'Unknown')]);
            fputcsv($output, []); // Empty row

            // Column headers
            fputcsv($output, [
                'Record ID', 'Category', 'Subcategory', 'Description', 'Purchase Date',
                'Budget (₱)', 'Expense (₱)', 'Rental Rate (₱)', 'Variance (₱)', 'Tax (₱)', 
                'Invoice No', 'Payee', 'Is Rental', 'Bill to Client', 'Is Company Loss', 
                'Remarks', 'Created By', 'Creation Date'
            ]);
            
            // Data rows and totals calculation
            $total_budget = 0;
            $total_expense = 0;
            $total_rental = 0;
            $total_variance = 0;
            $total_tax = 0;
            $record_count = 0;
            
            while ($row = $expense_result->fetch_assoc()) {
                fputcsv($output, [
                    $row['record_id'],
                    $row['category'] ?? '',
                    $row['subcategory'] ?? '',
                    $row['record_description'] ?? '',
                    $row['purchase_date'] ?? '',
                    number_format($row['budget'] ?? 0, 2),
                    number_format($row['expense'] ?? 0, 2),
                    number_format($row['rental_rate'] ?? 0, 2),
                    number_format($row['variance'] ?? 0, 2),
                    number_format($row['tax'] ?? 0, 2),
                    $row['invoice_no'] ?? '',
                    $row['payee'] ?? '',
                    $row['is_rental'] ?? 'No',
                    $row['bill_to_client'] ?? 'No',
                    $row['is_company_loss'] ?? 'No',
                    $row['remarks'] ?? '',
                    $row['creator_name'] ?? 'Unknown',
                    $row['creation_date'] ?? ''
                ]);
                
                $total_budget += floatval($row['budget'] ?? 0);
                $total_expense += floatval($row['expense'] ?? 0);
                $total_rental += floatval($row['rental_rate'] ?? 0);
                $total_variance += floatval($row['variance'] ?? 0);
                $total_tax += floatval($row['tax'] ?? 0);
                $record_count++;
            }
            
            // Summary
            fputcsv($output, []);
            fputcsv($output, ['=== SUMMARY ===']);
            fputcsv($output, ['Total Records', $record_count]);
            fputcsv($output, ['Total Budget', number_format($total_budget, 2)]);
            fputcsv($output, ['Total Expense', number_format($total_expense, 2)]);
            fputcsv($output, ['Total Rental', number_format($total_rental, 2)]);
            fputcsv($output, ['Total Combined Expense', number_format($total_expense + $total_rental, 2)]);
            fputcsv($output, ['Total Variance', number_format($total_variance, 2)]);
            fputcsv($output, ['Total Tax', number_format($total_tax, 2)]);
            
            if ($total_budget > 0) {
                $utilization = (($total_expense + $total_rental) / $total_budget) * 100;
                fputcsv($output, ['Budget Utilization', number_format($utilization, 2) . '%']);
            }
            
            fclose($output);
            $stmt->close();
            
            logUserActivity(
                'export', 
                'ms_reports.php', 
                "export report"
            );
            
            error_log("=== PROJECT EXPORT COMPLETED SUCCESSFULLY ===");
            exit();
            
        } catch (Exception $e) {
            error_log("Project export error: " . $e->getMessage());
            die("Export error: " . $e->getMessage());
        }
    }

    // Corporate Report (project_id = 1)
    elseif (isset($_POST['export_corporate'])) {
        $corp_start_date = $_POST['corp_start_date'] ?? '';
        $corp_end_date = $_POST['corp_end_date'] ?? '';

        $corporate_query = "SELECT e.*, 
                            CONCAT(emp.first_name, ' ', emp.last_name) as creator_name,
                            p.project_name as original_project,
                            p.project_code as original_project_code
                            FROM expense e
                            LEFT JOIN users u ON e.created_by = u.user_id
                            LEFT JOIN employee emp ON u.employee_id = emp.employee_id
                            LEFT JOIN expense e2 ON e.loss_id = e2.record_id
                            LEFT JOIN projects p ON e2.project_id = p.project_id
                            WHERE e.project_id = 1";

        $params = [];
        $types = "";

        if (!empty($corp_start_date) && !empty($corp_end_date)) {
            $corporate_query .= " AND e.purchase_date >= ? AND e.purchase_date <= ?";
            $params[] = $corp_start_date;
            $params[] = $corp_end_date;
            $types .= "ss";
        }

        $corporate_query .= " ORDER BY e.purchase_date DESC";

        $stmt = $conn->prepare($corporate_query);
        if (!$stmt) {
            die("Database error: " . $conn->error);
        }
        
        if (!empty($params)) {
            $stmt->bind_param($types, ...$params);
        }
        $stmt->execute();
        $corporate_result = $stmt->get_result();

        $date_suffix = !empty($corp_start_date) && !empty($corp_end_date) ? "_{$corp_start_date}_to_{$corp_end_date}" : "";
        $filename = "Corporate_Report{$date_suffix}_" . date('Y-m-d') . '.csv';

        // Clear any output buffer
        if (ob_get_level()) {
            ob_end_clean();
        }

        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment; filename="' . $filename . '"');
        header('Pragma: no-cache');
        header('Expires: 0');
        header('Cache-Control: must-revalidate, post-check=0, pre-check=0');

        $output = fopen('php://output', 'w');

        // Add UTF-8 BOM for Excel compatibility
        fwrite($output, "\xEF\xBB\xBF");

        fputcsv($output, ['CORPORATE EXPENSE REPORT']);
        fputcsv($output, ['Company: Malaya Solar Energies Inc.']);
        if (!empty($corp_start_date) && !empty($corp_end_date)) {
            fputcsv($output, ['Period: ' . $corp_start_date . ' to ' . $corp_end_date]);
        }
        fputcsv($output, ['Generated: ' . date('Y-m-d H:i:s T')]);
        fputcsv($output, ['Generated By: ' . ($_SESSION['username'] ?? 'Unknown')]);
        fputcsv($output, []);

        fputcsv($output, [
            'Record ID', 'Category', 'Subcategory', 'Description', 'Purchase Date',
            'Expense Amount (₱)', 'Rental Rate (₱)', 'Tax (₱)', 'Payee', 'Is Company Loss',
            'Original Project', 'Original Project Code', 'Loss Reference ID', 'Created By', 'Remarks'
        ]);

        $total_corporate_expense = 0;
        $total_corporate_rental = 0;
        $total_corporate_tax = 0;
        $company_loss_count = 0;
        $billed_to_client_count = 0;

        while ($row = $corporate_result->fetch_assoc()) {
            fputcsv($output, [
                $row['record_id'], 
                $row['category'] ?? '', 
                $row['subcategory'] ?? '',
                $row['record_description'] ?? '', 
                $row['purchase_date'] ?? '',
                number_format($row['expense'] ?? 0, 2), 
                number_format($row['rental_rate'] ?? 0, 2), 
                number_format($row['tax'] ?? 0, 2),
                $row['payee'] ?? '', 
                $row['is_company_loss'] ?? 'No', 
                $row['original_project'] ?? 'Unknown',
                $row['original_project_code'] ?? '',
                $row['loss_id'] ?? '', 
                $row['creator_name'] ?? 'Unknown', 
                $row['remarks'] ?? ''
            ]);

            $total_corporate_expense += floatval($row['expense'] ?? 0);
            $total_corporate_rental += floatval($row['rental_rate'] ?? 0);
            $total_corporate_tax += floatval($row['tax'] ?? 0);

            if (($row['is_company_loss'] ?? 'No') === 'Yes') {
                $company_loss_count++;
            }
            if (($row['bill_to_client'] ?? 'No') === 'Yes') {
                $billed_to_client_count++;
            }
        }

        fputcsv($output, []);
        fputcsv($output, ['=== CORPORATE SUMMARY ===']);
        fputcsv($output, ['Total Corporate Expenses', number_format($total_corporate_expense, 2)]);
        fputcsv($output, ['Total Rental Costs', number_format($total_corporate_rental, 2)]);
        fputcsv($output, ['Total Tax', number_format($total_corporate_tax, 2)]);
        fputcsv($output, ['Company Loss Items', $company_loss_count]);
        fputcsv($output, ['Billed to Client Items', $billed_to_client_count]);
        fputcsv($output, ['Total Corporate Impact', number_format($total_corporate_expense + $total_corporate_rental, 2)]);

        fclose($output);
        
        logUserActivity(
            'export', 
            'ms_reports.php', 
            "export report"
        );
        
        $stmt->close();
        exit();
    }
    
    // Payroll Report
    elseif (isset($_POST['export_payroll'])) {
        $employee_id = intval($_POST['employee_id']);
        $start_date = $_POST['payroll_start_date'] ?? '';
        $end_date = $_POST['payroll_end_date'] ?? '';
        
        if ($employee_id <= 0) {
            die("Invalid employee selected.");
        }
        
        // Get employee details
        $employee_query = "SELECT first_name, last_name, position, department, employment_status FROM employee WHERE employee_id = ?";
        $stmt = $conn->prepare($employee_query);
        if (!$stmt) {
            die("Database error: " . $conn->error);
        }
        
        $stmt->bind_param("i", $employee_id);
        $stmt->execute();
        $employee_result = $stmt->get_result();
        
        if ($employee_result->num_rows === 0) {
            die("Employee not found.");
        }
        
        $employee = $employee_result->fetch_assoc();
        $stmt->close();
        
        // Build payroll query
        $payroll_query = "SELECT * FROM payroll WHERE employee_id = ?";
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
        if (!$stmt) {
            die("Database error: " . $conn->error);
        }
        
        $stmt->bind_param($types, ...$params);
        $stmt->execute();
        $payroll_result = $stmt->get_result();
        
        $employee_name_safe = preg_replace('/[^a-zA-Z0-9_-]/', '_', $employee['last_name'] . '_' . $employee['first_name']);
        $date_suffix = !empty($start_date) && !empty($end_date) ? "_{$start_date}_to_{$end_date}" : "";
        $filename = "{$employee_name_safe}_Payroll{$date_suffix}_" . date('Y-m-d') . '.csv';
        
        // Clear any output buffer
        if (ob_get_level()) {
            ob_end_clean();
        }
        
        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment; filename="' . $filename . '"');
        header('Pragma: no-cache');
        header('Expires: 0');
        header('Cache-Control: must-revalidate, post-check=0, pre-check=0');
        
        $output = fopen('php://output', 'w');
        
        // Add UTF-8 BOM for Excel compatibility
        fwrite($output, "\xEF\xBB\xBF");
        
        // Header
        fputcsv($output, ['PAYROLL REPORT']);
        fputcsv($output, ['Employee: ' . $employee['first_name'] . ' ' . $employee['last_name']]);
        fputcsv($output, ['Position: ' . $employee['position']]);
        fputcsv($output, ['Department: ' . $employee['department']]);
        fputcsv($output, ['Employment Status: ' . $employee['employment_status']]);
        if (!empty($start_date) && !empty($end_date)) {
            fputcsv($output, ['Period: ' . $start_date . ' to ' . $end_date]);
        }
        fputcsv($output, ['Generated: ' . date('Y-m-d H:i:s T')]);
        fputcsv($output, ['Generated By: ' . ($_SESSION['username'] ?? 'Unknown')]);
        fputcsv($output, []);
        
        // Column headers
        fputcsv($output, [
            'Payroll ID', 'Period Start', 'Period End', 'Gross Pay (₱)', 'Basic Pay (₱)',
            'Overtime Pay (₱)', 'Allowances (₱)', 'Bonus (₱)', 'SSS (₱)', 'PhilHealth (₱)', 'Pag-IBIG (₱)',
            'Loans (₱)', 'Other Deductions (₱)', 'Total Deductions (₱)', 'Tax (₱)', 'Net Pay (₱)',
            'Payment Method', 'Remarks', 'Date Generated'
        ]);
        
        // Data and totals
        $totals = array_fill_keys([
            'gross_pay', 'basic_pay', 'overtime_pay', 'allowances', 'bonus',
            'sss', 'philhealth', 'pagibig', 'loans', 'other_deductions',
            'total_deductions', 'tax', 'net_pay'
        ], 0);
        
        $record_count = 0;
        
        while ($row = $payroll_result->fetch_assoc()) {
            fputcsv($output, [
                $row['payroll_id'], 
                $row['period_start'], 
                $row['period_end'],
                number_format($row['gross_pay'], 2), 
                number_format($row['basic_pay'], 2), 
                number_format($row['overtime_pay'], 2),
                number_format($row['allowances'], 2), 
                number_format($row['bonus'], 2), 
                number_format($row['sss'], 2), 
                number_format($row['philhealth'], 2),
                number_format($row['pagibig'], 2), 
                number_format($row['loans'], 2), 
                number_format($row['other_deductions'], 2),
                number_format($row['total_deductions'], 2), 
                number_format($row['tax'], 2), 
                number_format($row['net_pay'], 2),
                $row['payment_method'] ?? 'Bank Transfer', 
                $row['remarks'] ?? '', 
                $row['date_generated']
            ]);
            
            foreach ($totals as $key => $value) {
                $totals[$key] += floatval($row[$key] ?? 0);
            }
            $record_count++;
        }
        
        // Summary
        fputcsv($output, []);
        fputcsv($output, ['=== SUMMARY ===']);
        fputcsv($output, ['Total Pay Periods', $record_count]);
        foreach ($totals as $key => $value) {
            $display_key = ucwords(str_replace('_', ' ', $key));
            fputcsv($output, [$display_key, number_format($value, 2)]);
        }
        
        // Calculate averages
        if ($record_count > 0) {
            fputcsv($output, []);
            fputcsv($output, ['=== AVERAGES ===']);
            foreach ($totals as $key => $value) {
                $average = $value / $record_count;
                $display_key = 'Average ' . ucwords(str_replace('_', ' ', $key));
                fputcsv($output, [$display_key, number_format($average, 2)]);
            }
        }
        
        fclose($output);
        
        logUserActivity(
            'export', 
            'ms_reports.php', 
            "export report"
        );
        
        $stmt->close();
        exit();
    }
    
    // User Activity Report
    elseif (isset($_POST['export_user_activity'])) {
        $activity_start_date = $_POST['activity_start_date'] ?? '';
        $activity_end_date = $_POST['activity_end_date'] ?? '';
        $selected_user = $_POST['selected_user'] ?? '';
        
        $activity_query = "SELECT ual.*, u.username, e.first_name, e.last_name
                          FROM user_activity_log ual
                          LEFT JOIN users u ON ual.user_id = u.user_id
                          LEFT JOIN employee e ON u.employee_id = e.employee_id
                          WHERE 1=1";
        
        $params = array();
        $types = "";
        
        if (!empty($activity_start_date) && !empty($activity_end_date)) {
            $activity_query .= " AND DATE(ual.timestamp) >= ? AND DATE(ual.timestamp) <= ?";
            $params[] = $activity_start_date;
            $params[] = $activity_end_date;
            $types .= "ss";
        }
        
        if (!empty($selected_user)) {
            $activity_query .= " AND ual.user_id = ?";
            $params[] = $selected_user;
            $types .= "i";
        }
        
        $activity_query .= " ORDER BY ual.timestamp DESC";
        
        $stmt = $conn->prepare($activity_query);
        if (!empty($params)) {
            $stmt->bind_param($types, ...$params);
        }
        $stmt->execute();
        $activity_result = $stmt->get_result();
        
        $filename = 'User_Activity_Report_' . date('Y-m-d') . '.csv';
        
        // Clear any output buffer
        if (ob_get_level()) {
            ob_end_clean();
        }
        
        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment; filename="' . $filename . '"');
        header('Pragma: no-cache');
        header('Expires: 0');
        
        $output = fopen('php://output', 'w');
        
        // Add UTF-8 BOM for Excel compatibility
        fwrite($output, "\xEF\xBB\xBF");
        
        fputcsv($output, ['USER ACTIVITY REPORT']);
        if (!empty($activity_start_date) && !empty($activity_end_date)) {
            fputcsv($output, ['Period: ' . $activity_start_date . ' to ' . $activity_end_date]);
        }
        fputcsv($output, ['Generated: ' . date('Y-m-d H:i:s T')]);
        fputcsv($output, ['Generated By: ' . ($_SESSION['username'] ?? 'Unknown')]);
        fputcsv($output, []);
        
        fputcsv($output, [
            'Log ID', 'User ID', 'Username', 'Employee Name', 'Action',
            'Page', 'Details', 'Timestamp'
        ]);
        
        $record_count = 0;
        while ($row = $activity_result->fetch_assoc()) {
            fputcsv($output, [
                $row['log_id'], $row['user_id'], $row['username'],
                $row['first_name'] . ' ' . $row['last_name'],
                $row['action'], $row['page'], $row['details'], $row['timestamp']
            ]);
            $record_count++;
        }
        
        fputcsv($output, []);
        fputcsv($output, ['=== SUMMARY ===']);
        fputcsv($output, ['Total Activities', $record_count]);
        
        fclose($output);
        
        logUserActivity(
            'export', 
            'ms_reports.php', 
            "export report"
        );
        
        $stmt->close();
        exit();
    }
    
    // Login Sessions Report
    elseif (isset($_POST['export_login_sessions'])) {
        $login_start_date = $_POST['login_start_date'] ?? '';
        $login_end_date = $_POST['login_end_date'] ?? '';
        $selected_user_login = $_POST['selected_user_login'] ?? '';
        
        $login_query = "SELECT la.*, u.username, e.first_name, e.last_name
                       FROM login_attempts la
                       LEFT JOIN users u ON la.user_id = u.user_id
                       LEFT JOIN employee e ON u.employee_id = e.employee_id
                       WHERE 1=1";
        
        $params = array();
        $types = "";
        
        if (!empty($login_start_date) && !empty($login_end_date)) {
            $login_query .= " AND DATE(la.attempt_time) >= ? AND DATE(la.attempt_time) <= ?";
            $params[] = $login_start_date;
            $params[] = $login_end_date;
            $types .= "ss";
        }
        
        if (!empty($selected_user_login)) {
            $login_query .= " AND la.user_id = ?";
            $params[] = $selected_user_login;
            $types .= "i";
        }
        
        $login_query .= " ORDER BY la.attempt_time DESC";
        
        $stmt = $conn->prepare($login_query);
        if (!empty($params)) {
            $stmt->bind_param($types, ...$params);
        }
        $stmt->execute();
        $login_result = $stmt->get_result();
        
        $filename = 'Login_Sessions_Report_' . date('Y-m-d') . '.csv';
        
        // Clear any output buffer
        if (ob_get_level()) {
            ob_end_clean();
        }
        
        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment; filename="' . $filename . '"');
        header('Pragma: no-cache');
        header('Expires: 0');
        
        $output = fopen('php://output', 'w');
        
        // Add UTF-8 BOM for Excel compatibility
        fwrite($output, "\xEF\xBB\xBF");
        
        fputcsv($output, ['LOGIN SESSIONS REPORT']);
        if (!empty($login_start_date) && !empty($login_end_date)) {
            fputcsv($output, ['Period: ' . $login_start_date . ' to ' . $login_end_date]);
        }
        fputcsv($output, ['Generated: ' . date('Y-m-d H:i:s T')]);
        fputcsv($output, ['Generated By: ' . ($_SESSION['username'] ?? 'Unknown')]);
        fputcsv($output, []);
        
        fputcsv($output, [
            'Attempt ID', 'User ID', 'Username', 'Employee Name', 'Attempt Time',
            'IP Address', 'Success', 'Notes'
        ]);
        
        $successful_logins = 0;
        $failed_logins = 0;
        $total_attempts = 0;
        
        while ($row = $login_result->fetch_assoc()) {
            fputcsv($output, [
                $row['id'], $row['user_id'], $row['username'],
                $row['first_name'] . ' ' . $row['last_name'],
                $row['attempt_time'], $row['ip_address'],
                $row['success'] ? 'Yes' : 'No', $row['notes']
            ]);
            
            if ($row['success']) {
                $successful_logins++;
            } else {
                $failed_logins++;
            }
            $total_attempts++;
        }
        
        fputcsv($output, []);
        fputcsv($output, ['=== SUMMARY ===']);
        fputcsv($output, ['Total Attempts', $total_attempts]);
        fputcsv($output, ['Successful Logins', $successful_logins]);
        fputcsv($output, ['Failed Logins', $failed_logins]);
        fputcsv($output, ['Success Rate', $total_attempts > 0 ? round(($successful_logins / $total_attempts) * 100, 2) . '%' : '0%']);
        
        fclose($output);
        
        logUserActivity(
            'export', 
            'ms_reports.php', 
            "export report"
        );
        
        $stmt->close();
        exit();
    }
    
    // All Projects Summary Report
    elseif (isset($_POST['export_all_projects'])) {
        $projects_query = "SELECT p.*, 
                          COUNT(e.record_id) as total_records,
                          COALESCE(SUM(e.budget), 0) as total_budget,
                          COALESCE(SUM(e.expense + COALESCE(e.rental_rate, 0)), 0) as total_expense,
                          COALESCE(SUM(e.variance), 0) as total_variance,
                          COALESCE(SUM(e.tax), 0) as total_tax,
                          CONCAT(creator.first_name, ' ', creator.last_name) as created_by_name
                          FROM projects p
                          LEFT JOIN expense e ON p.project_id = e.project_id
                          LEFT JOIN users u ON p.created_by = u.user_id
                          LEFT JOIN employee creator ON u.employee_id = creator.employee_id
                          GROUP BY p.project_id
                          ORDER BY p.project_name";
        
        $projects_result = $conn->query($projects_query);
        if (!$projects_result) {
            die("Database error: " . $conn->error);
        }
        
        $filename = 'All_Projects_Summary_' . date('Y-m-d') . '.csv';
        
        // Clear any output buffer
        if (ob_get_level()) {
            ob_end_clean();
        }
        
        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment; filename="' . $filename . '"');
        header('Pragma: no-cache');
        header('Expires: 0');
        header('Cache-Control: must-revalidate, post-check=0, pre-check=0');
        
        $output = fopen('php://output', 'w');
        
        // Add UTF-8 BOM for Excel compatibility
        fwrite($output, "\xEF\xBB\xBF");
        
        fputcsv($output, ['ALL PROJECTS SUMMARY REPORT']);
        fputcsv($output, ['Generated: ' . date('Y-m-d H:i:s T')]);
        fputcsv($output, ['Generated By: ' . ($_SESSION['username'] ?? 'Unknown')]);
        fputcsv($output, []);
        
        fputcsv($output, [
            'Project ID', 'Project Code', 'Project Name', 'Client Name', 'Company',
            'Contact', 'Email', 'Description', 'Total Records', 'Total Budget (₱)',
            'Total Expense (₱)', 'Total Variance (₱)', 'Total Tax (₱)', 'Budget Utilization (%)',
            'Project Status', 'Created By', 'Creation Date'
        ]);
        
        $grand_total_budget = 0;
        $grand_total_expense = 0;
        $grand_total_variance = 0;
        $grand_total_tax = 0;
        $total_projects = 0;
        $total_records = 0;
        
        while ($row = $projects_result->fetch_assoc()) {
            $budget_utilization = $row['total_budget'] > 0 ? 
                number_format(($row['total_expense'] / $row['total_budget']) * 100, 2) : 'N/A';
            
            $project_status = 'Active';
            if ($row['project_id'] == 1) {
                $project_status = 'Corporate';
            } elseif ($row['total_records'] == 0) {
                $project_status = 'No Activity';
            } elseif ($row['total_variance'] < 0) {
                $project_status = 'Over Budget';
            }
            
            fputcsv($output, [
                $row['project_id'], 
                $row['project_code'], 
                $row['project_name'],
                $row['first_name'] . ' ' . $row['last_name'], 
                $row['company_name'],
                $row['contact'], 
                $row['email'], 
                $row['description'],
                $row['total_records'], 
                number_format($row['total_budget'], 2), 
                number_format($row['total_expense'], 2),
                number_format($row['total_variance'], 2), 
                number_format($row['total_tax'], 2),
                $budget_utilization,
                $project_status,
                $row['created_by_name'] ?? 'Unknown',
                $row['creation_date']
            ]);
            
            $grand_total_budget += $row['total_budget'];
            $grand_total_expense += $row['total_expense'];
            $grand_total_variance += $row['total_variance'];
            $grand_total_tax += $row['total_tax'];
            $total_records += $row['total_records'];
            $total_projects++;
        }
        
        fputcsv($output, []);
        fputcsv($output, ['=== GRAND TOTALS ===']);
        fputcsv($output, ['Total Projects', $total_projects]);
        fputcsv($output, ['Total Records Across All Projects', $total_records]);
        fputcsv($output, ['Grand Total Budget', number_format($grand_total_budget, 2)]);
        fputcsv($output, ['Grand Total Expense', number_format($grand_total_expense, 2)]);
        fputcsv($output, ['Grand Total Variance', number_format($grand_total_variance, 2)]);
        fputcsv($output, ['Grand Total Tax', number_format($grand_total_tax, 2)]);
        fputcsv($output, ['Overall Budget Utilization', $grand_total_budget > 0 ? 
            number_format(($grand_total_expense / $grand_total_budget) * 100, 2) . '%' : 'N/A']);
        
        fclose($output);
        
        logUserActivity(
            'export', 
            'ms_reports.php', 
            "export report"
        );
        
        exit();
    }
    
    // All Employees Summary Report
    elseif (isset($_POST['export_all_employees'])) {
        $employees_query = "SELECT e.*, 
                           COUNT(p.payroll_id) as total_payrolls,
                           COALESCE(SUM(p.gross_pay), 0) as total_gross,
                           COALESCE(SUM(p.net_pay), 0) as total_net,
                           COALESCE(SUM(p.total_deductions), 0) as total_deductions,
                           COALESCE(u.username, 'No Account') as username,
                           COALESCE(u.account_status, 'No Account') as account_status,
                           COALESCE(u.role, 'No Role') as user_role,
                           MAX(p.period_end) as latest_payroll_date
                           FROM employee e
                           LEFT JOIN payroll p ON e.employee_id = p.employee_id
                           LEFT JOIN users u ON e.employee_id = u.employee_id
                           GROUP BY e.employee_id
                           ORDER BY e.last_name, e.first_name";
        
        $employees_result = $conn->query($employees_query);
        if (!$employees_result) {
            die("Database error: " . $conn->error);
        }
        
        $filename = 'All_Employees_Summary_' . date('Y-m-d') . '.csv';
        
        // Clear any output buffer
        if (ob_get_level()) {
            ob_end_clean();
        }
        
        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment; filename="' . $filename . '"');
        header('Pragma: no-cache');
        header('Expires: 0');
        header('Cache-Control: must-revalidate, post-check=0, pre-check=0');
        
        $output = fopen('php://output', 'w');
        
        // Add UTF-8 BOM for Excel compatibility
        fwrite($output, "\xEF\xBB\xBF");
        
        fputcsv($output, ['ALL EMPLOYEES SUMMARY REPORT']);
        fputcsv($output, ['Generated: ' . date('Y-m-d H:i:s T')]);
        fputcsv($output, ['Generated By: ' . ($_SESSION['username'] ?? 'Unknown')]);
        fputcsv($output, []);
        
        fputcsv($output, [
            'Employee ID', 'First Name', 'Last Name', 'Position', 'Department',
            'Employment Status', 'Contact', 'Address', 'Username', 'Account Status', 'User Role',
            'Total Payrolls', 'Total Gross Pay (₱)', 'Total Net Pay (₱)', 'Total Deductions (₱)',
            'Average Gross Pay (₱)', 'Latest Payroll Date'
        ]);
        
        $total_employees = 0;
        $grand_total_gross = 0;
        $grand_total_net = 0;
        $grand_total_deductions = 0;
        $employees_with_payroll = 0;
        $employees_with_accounts = 0;
        
        while ($row = $employees_result->fetch_assoc()) {
            $address = trim(
                ($row['unit_no'] ?? '') . ' ' . 
                ($row['building'] ?? '') . ' ' . 
                ($row['street'] ?? '') . ', ' . 
                ($row['barangay'] ?? '') . ', ' . 
                ($row['city'] ?? '') . ', ' . 
                ($row['country'] ?? '')
            );
            $address = preg_replace('/^[,\s]+|[,\s]+$/', '', $address); // Clean up commas and spaces
            
            $average_gross = $row['total_payrolls'] > 0 ? $row['total_gross'] / $row['total_payrolls'] : 0;
            
            fputcsv($output, [
                $row['employee_id'], 
                $row['first_name'], 
                $row['last_name'],
                $row['position'], 
                $row['department'], 
                $row['employment_status'],
                $row['contact'], 
                $address, 
                $row['username'], 
                $row['account_status'],
                $row['user_role'],
                $row['total_payrolls'], 
                number_format($row['total_gross'], 2), 
                number_format($row['total_net'], 2), 
                number_format($row['total_deductions'], 2),
                number_format($average_gross, 2),
                $row['latest_payroll_date'] ?? 'No Payroll'
            ]);
            
            $grand_total_gross += $row['total_gross'];
            $grand_total_net += $row['total_net'];
            $grand_total_deductions += $row['total_deductions'];
            $total_employees++;
            
            if ($row['total_payrolls'] > 0) {
                $employees_with_payroll++;
            }
            
            if ($row['username'] !== 'No Account') {
                $employees_with_accounts++;
            }
        }
        
        fputcsv($output, []);
        fputcsv($output, ['=== SUMMARY ===']);
        fputcsv($output, ['Total Employees', $total_employees]);
        fputcsv($output, ['Employees with User Accounts', $employees_with_accounts]);
        fputcsv($output, ['Employees with Payroll Records', $employees_with_payroll]);
        fputcsv($output, ['Grand Total Gross Pay', number_format($grand_total_gross, 2)]);
        fputcsv($output, ['Grand Total Net Pay', number_format($grand_total_net, 2)]);
        fputcsv($output, ['Grand Total Deductions', number_format($grand_total_deductions, 2)]);
        fputcsv($output, ['Average Gross Pay per Employee', $total_employees > 0 ? 
            number_format($grand_total_gross / $total_employees, 2) : '0.00']);
        
        fclose($output);
        
        logUserActivity(
            'export', 
            'ms_reports.php', 
            "export report"
        );
        
        exit();
    }
    
    // Vendors Summary Report
    elseif (isset($_POST['export_vendor_summary'])) {
        $vendors_query = "SELECT * FROM vendors ORDER BY vendor_name";
        $vendors_result = $conn->query($vendors_query);
        
        if (!$vendors_result) {
            die("Database error: " . $conn->error);
        }

        $filename = 'Vendors_Summary_' . date('Y-m-d') . '.csv';

        // Clear any output buffer
        if (ob_get_level()) {
            ob_end_clean();
        }

        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment; filename="' . $filename . '"');
        header('Pragma: no-cache');
        header('Expires: 0');
        header('Cache-Control: must-revalidate, post-check=0, pre-check=0');

        $output = fopen('php://output', 'w');

        // Add UTF-8 BOM for Excel compatibility
        fwrite($output, "\xEF\xBB\xBF");

        fputcsv($output, ['VENDORS SUMMARY REPORT']);
        fputcsv($output, ['Generated: ' . date('Y-m-d H:i:s T')]);
        fputcsv($output, ['Generated By: ' . ($_SESSION['username'] ?? 'Unknown')]);
        fputcsv($output, []);

        fputcsv($output, [
            'Vendor ID', 'Vendor Name', 'Vendor Type', 'Contact Person',
            'Email', 'Contact Number', 'Telephone', 'Complete Address', 'Remarks'
        ]);

        $total_vendors = 0;
        $vendor_types = [];
        $countries = [];

        while ($row = $vendors_result->fetch_assoc()) {
            $address = trim(
                ($row['vendor_unit_bldg_no'] ?? '') . ' ' . 
                ($row['vendor_street'] ?? '') . ', ' . 
                ($row['vendor_city'] ?? '') . ', ' . 
                ($row['vendor_country'] ?? '')
            );
            $address = preg_replace('/^[,\s]+|[,\s]+$/', '', $address); // Clean up commas and spaces
            
            fputcsv($output, [
                $row['vendor_id'], 
                $row['vendor_name'], 
                $row['vendor_type'],
                $row['contact_person'], 
                $row['vendor_email'] ?? '', 
                $row['contact_no'],
                $row['telephone'] ?? '', 
                $address, 
                $row['vendor_remarks'] ?? ''
            ]);
            
            // Count vendor types
            $vendor_type = $row['vendor_type'];
            if (!isset($vendor_types[$vendor_type])) {
                $vendor_types[$vendor_type] = 0;
            }
            $vendor_types[$vendor_type]++;
            
            // Count countries
            $country = $row['vendor_country'] ?? 'Unknown';
            if (!isset($countries[$country])) {
                $countries[$country] = 0;
            }
            $countries[$country]++;
            
            $total_vendors++;
        }
        
        fputcsv($output, []);
        fputcsv($output, ['=== SUMMARY ===']);
        fputcsv($output, ['Total Vendors', $total_vendors]);
        fputcsv($output, []);
        fputcsv($output, ['VENDOR TYPES BREAKDOWN']);
        fputcsv($output, ['Type', 'Count', 'Percentage']);
        foreach ($vendor_types as $type => $count) {
            $percentage = $total_vendors > 0 ? number_format(($count / $total_vendors) * 100, 1) : '0.0';
            fputcsv($output, [$type, $count, $percentage . '%']);
        }
        
        fputcsv($output, []);
        fputcsv($output, ['COUNTRIES BREAKDOWN']);
        fputcsv($output, ['Country', 'Count', 'Percentage']);
        foreach ($countries as $country => $count) {
            $percentage = $total_vendors > 0 ? number_format(($count / $total_vendors) * 100, 1) : '0.0';
            fputcsv($output, [$country, $count, $percentage . '%']);
        }

        fclose($output);
        
        logUserActivity(
            'export', 
            'ms_reports.php', 
            "export report"
        );
        
        exit();
    }
    
    // Assets Summary Report
    elseif (isset($_POST['export_assets_summary'])) {
        $assets_query = "SELECT a.*, e.category, e.purchase_date, e.expense, e.rental_rate,
                        e.is_rental, e.payee, e.invoice_no,
                        emp.first_name, emp.last_name, p.project_name, p.project_code
                        FROM assets a
                        LEFT JOIN expense e ON a.record_id = e.record_id
                        LEFT JOIN users u ON a.created_by = u.user_id
                        LEFT JOIN employee emp ON u.employee_id = emp.employee_id
                        LEFT JOIN projects p ON e.project_id = p.project_id
                        ORDER BY a.creation_date DESC";
        
        $assets_result = $conn->query($assets_query);
        if (!$assets_result) {
            die("Database error: " . $conn->error);
        }
        
        $filename = 'Assets_Summary_' . date('Y-m-d') . '.csv';
        
        // Clear any output buffer
        if (ob_get_level()) {
            ob_end_clean();
        }
        
        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment; filename="' . $filename . '"');
        header('Pragma: no-cache');
        header('Expires: 0');
        header('Cache-Control: must-revalidate, post-check=0, pre-check=0');
        
        $output = fopen('php://output', 'w');
        
        // Add UTF-8 BOM for Excel compatibility
        fwrite($output, "\xEF\xBB\xBF");
        
        fputcsv($output, ['ASSETS SUMMARY REPORT']);
        fputcsv($output, ['Generated: ' . date('Y-m-d H:i:s T')]);
        fputcsv($output, ['Generated By: ' . ($_SESSION['username'] ?? 'Unknown')]);
        fputcsv($output, []);
        
        fputcsv($output, [
            'Asset ID', 'Record ID', 'Asset Description', 'Category', 'Project',
            'Project Code', 'Purchase Date', 'Cost/Rental Rate (₱)', 'Is Rental', 
            'Payee', 'Invoice No', 'Location', 'Assigned To', 'Serial Number', 
            'Warranty Expiry', 'Created By', 'Creation Date'
        ]);
        
        $total_assets = 0;
        $total_asset_value = 0;
        $categories_summary = [];
        $rental_assets = 0;
        $purchased_assets = 0;
        
        while ($row = $assets_result->fetch_assoc()) {
            $cost = $row['expense'] > 0 ? $row['expense'] : $row['rental_rate'];
            $is_rental = ($row['is_rental'] ?? 'No') === 'Yes' ? 'Yes' : 'No';
            
            if ($is_rental === 'Yes') {
                $rental_assets++;
            } else {
                $purchased_assets++;
            }
            
            fputcsv($output, [
                $row['asset_id'], 
                $row['record_id'], 
                $row['asset_description'],
                $row['category'], 
                $row['project_name'], 
                $row['project_code'],
                $row['purchase_date'],
                number_format($cost, 2),
                $is_rental,
                $row['payee'] ?? '',
                $row['invoice_no'] ?? '',
                $row['location'] ?? '', 
                $row['assigned_to'] ?? '', 
                $row['serial_number'] ?? '',
                $row['warranty_expiry'] ?? '', 
                ($row['first_name'] ?? '') . ' ' . ($row['last_name'] ?? ''),
                $row['creation_date']
            ]);
            
            $total_asset_value += $cost;
            $total_assets++;
            
            // Category summary
            $category = $row['category'] ?? 'Unknown';
            if (!isset($categories_summary[$category])) {
                $categories_summary[$category] = ['count' => 0, 'value' => 0];
            }
            $categories_summary[$category]['count']++;
            $categories_summary[$category]['value'] += $cost;
        }
        
        fputcsv($output, []);
        fputcsv($output, ['=== SUMMARY ===']);
        fputcsv($output, ['Total Assets', $total_assets]);
        fputcsv($output, ['Purchased Assets', $purchased_assets]);
        fputcsv($output, ['Rental Assets', $rental_assets]);
        fputcsv($output, ['Total Asset Value', number_format($total_asset_value, 2)]);
        fputcsv($output, []);
        fputcsv($output, ['CATEGORY BREAKDOWN']);
        fputcsv($output, ['Category', 'Count', 'Total Value (₱)', 'Percentage']);
        foreach ($categories_summary as $category => $data) {
            $percentage = $total_assets > 0 ? number_format(($data['count'] / $total_assets) * 100, 1) : '0.0';
            fputcsv($output, [$category, $data['count'], number_format($data['value'], 2), $percentage . '%']);
        }
        
        fclose($output);
        
        logUserActivity(
            'export', 
            'ms_reports.php', 
            "export report"
        );
        
        exit();
    }
}

// Get data for dropdowns
$projects_query = "SELECT project_id, project_code, project_name FROM projects ORDER BY project_name";
$projects_result = $conn->query($projects_query);

$employees_query = "SELECT employee_id, first_name, last_name, position FROM employee ORDER BY last_name, first_name";
$employees_result = $conn->query($employees_query);

$users_query = "SELECT u.user_id, u.username, e.first_name, e.last_name FROM users u 
                LEFT JOIN employee e ON u.employee_id = e.employee_id 
                ORDER BY e.last_name, e.first_name";
$users_result = $conn->query($users_query);
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
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <style>
        /* Reports Page CSS Styles */
        body {
            font-family: 'Atkinson Hyperlegible', sans-serif;
            margin: 0;
            display: flex;
            height: 100vh;
            overflow: hidden;
            font-size: 12px;
        }

        .content-body {
            padding: 20px 40px;
            flex: 1;
            overflow-y: auto;
            background: rgba(243, 243, 243, 0.8);
            background-image: repeating-linear-gradient(
                45deg,
                rgba(255, 255, 255, 0.700) 0px,
                rgba(255, 255, 255, 0.500) 1px,
                transparent 1px,
                transparent 20px
            );
            backdrop-filter: blur(12px) saturate(180%);
            -webkit-backdrop-filter: blur(12px) saturate(180%);
        }

        /* Quick Date Toggle Buttons */
        .quick-dates-container {
            margin-bottom: 20px;
            padding: 15px;
            background: #f3f4f6;
            border-radius: 8px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }

        .quick-date-btn {
            background-color: #f3f4f6;
            border: 1px solid #d1d5db;
            color: #374151;
            padding: 6px 12px;
            margin: 2px;
            border-radius: 6px;
            font-size: 12px;
            font-family: 'Atkinson Hyperlegible', sans-serif;
            cursor: pointer;
            transition: all 0.2s ease;
            box-shadow: 0 1px 2px rgba(0,0,0,0.1);
        }

        .quick-date-btn:hover {
            background-color: #e5e7eb;
            box-shadow: 0 2px 4px rgba(0,0,0,0.15);
        }

        .quick-date-btn.active {
            background-color: #333;
            color: white;
            border-color: #333;
        }

        /* Report Cards */
        .report-card {
            transition: transform 0.2s ease, box-shadow 0.2s ease;
            border: none;
            border-radius: 12px;
            overflow: hidden;
        }

        .report-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 8px 25px rgba(0,0,0,0.15);
        }

        .card-header {
            background-color: #333;
            color: white;
            border: none;
            padding: 1.25rem;
        }

        .card-title {
            margin: 0;
            font-weight: 600;
            font-size: 14px;
        }

        /* Buttons */
        .btn-download {
            background-color: #333;
            border: none;
            color: white;
            padding: 0.75rem 1.5rem;
            border-radius: 8px;
            font-weight: 500;
            transition: all 0.3s ease;
            font-size: 12px;
        }

        .btn-download:hover {
            background-color: #555;
            transform: translateY(-2px);
            box-shadow: 0 4px 15px rgba(51, 51, 51, 0.4);
            color: white;
        }

        /* Form Elements */
        .form-label {
            font-weight: 500;
            color: #495057;
            margin-bottom: 0.5rem;
            font-size: 12px;
        }

        .form-control, .form-select {
            border-radius: 8px;
            border: 1px solid #dee2e6;
            padding: 0.75rem;
            transition: border-color 0.15s ease-in-out, box-shadow 0.15s ease-in-out;
            font-size: 12px;
        }

        .form-control:focus, .form-select:focus {
            border-color: #333;
            box-shadow: 0 0 0 0.2rem rgba(51, 51, 51, 0.25);
        }

        /* Page Title */
        .page-title {
            color: #333;
            font-weight: 700;
            margin-bottom: 2rem;
            font-size: 24px;
        }

        /* Additional Button Styles */
        .btn-outline-primary, .btn-outline-success, .btn-outline-info, .btn-outline-warning {
            font-size: 12px;
        }

        /* Text Styles */
        .text-muted {
            font-size: 11px;
        }

        .alert {
            font-size: 12px;
        }

        .card-body p {
            font-size: 12px;
        }
    </style>
</head>
<body>
    <div class="sidebar" id="sidebar">
        <?php include 'sidebar.php'; ?>
    </div>
    
    <div class="content-area">
        <?php include 'header.php'; ?>

        <div class="content-body">            
            <!-- Global Quick Dates Section -->
            <div class="quick-dates-container">
                <div class="d-flex align-items-center justify-content-between">
                    <div>
                        <h6 class="mb-2" style="font-size: 12px; font-weight: 600;">Quick Date Selection</h6>
                        <div class="quick-date-buttons">
                            <button type="button" class="quick-date-btn active" data-days="0">Today</button>
                            <button type="button" class="quick-date-btn" data-days="7">7 Days</button>
                            <button type="button" class="quick-date-btn" data-days="30">30 Days</button>
                            <button type="button" class="quick-date-btn" data-months="3">3 Months</button>
                            <button type="button" class="quick-date-btn" data-months="6">6 Months</button>
                            <button type="button" class="quick-date-btn" data-years="1">1 Year</button>
                        </div>
                    </div>
                    <div>
                        <small class="text-muted">Selected range will apply to all exports unless specific dates are chosen</small>
                    </div>
                </div>
            </div>
            
            <!-- Row 1: Project Reports -->
            <div class="row mb-4">
                <!-- Individual Project Expense Reports -->
                <div class="col-md-6 mb-4">
                    <div class="card report-card h-100">
                        <div class="card-header">
                            <h5 class="card-title">Project Expense Reports</h5>
                        </div>
                        <div class="card-body">
                            <form method="post" action="">
                                <input type="hidden" name="export_project_expenses" value="1">
                                
                                <div class="mb-3">
                                    <label for="project_id" class="form-label">Select Project</label>
                                    <select class="form-select" id="project_id" name="project_id" required>
                                        <option value="">-- Choose Project --</option>
                                        <?php
                                        if ($projects_result) {
                                            $projects_result->data_seek(0);
                                            while ($project = $projects_result->fetch_assoc()) {
                                                echo '<option value="' . $project['project_id'] . '">' . 
                                                    htmlspecialchars($project['project_name']) . ' (' . 
                                                    htmlspecialchars($project['project_code']) . ')</option>';
                                            }
                                        }
                                        ?>
                                    </select>
                                </div>
                                
                                <div class="row mb-3">
                                    <div class="col-md-6">
                                        <label for="start_date" class="form-label">Start Date (Optional)</label>
                                        <input type="date" class="form-control date-override" id="start_date" name="start_date">
                                    </div>
                                    <div class="col-md-6">
                                        <label for="end_date" class="form-label">End Date (Optional)</label>
                                        <input type="date" class="form-control date-override" id="end_date" name="end_date">
                                    </div>
                                </div>
                                
                                <div class="d-grid">
                                    <button type="submit" class="btn btn-download">
                                        Export Project Report
                                    </button>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>

                <!-- Corporate Export Form -->
                <div class="col-md-6 mb-4">
                    <div class="card report-card h-100">
                        <div class="card-header">
                            <h5 class="card-title">Corporate Expense Report</h5>
                        </div>
                        <div class="card-body">
                            <form method="post" action="">
                                <input type="hidden" name="export_corporate" value="1">
                                
                                <div class="mb-3">
                                    <p class="text-muted">
                                        This report shows all company losses and corporate expenses
                                    </p>
                                </div>
                                
                                <div class="row mb-3">
                                    <div class="col-md-6">
                                        <label for="corp_start_date" class="form-label">Start Date</label>
                                        <input type="date" class="form-control date-override" id="corp_start_date" name="corp_start_date">
                                    </div>
                                    <div class="col-md-6">
                                        <label for="corp_end_date" class="form-label">End Date</label>
                                        <input type="date" class="form-control date-override" id="corp_end_date" name="corp_end_date">
                                    </div>
                                </div>
                                
                                <div class="d-grid">
                                    <button type="submit" class="btn btn-download">
                                        Export Corporate Report
                                    </button>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Row 2: HR Reports -->
            <div class="row mb-4">
                <!-- Payroll Export Form -->
                <div class="col-md-6 mb-4">
                    <div class="card report-card h-100">
                        <div class="card-header">
                            <h5 class="card-title">Payroll Reports</h5>
                        </div>
                        <div class="card-body">
                            <form method="post" action="">
                                <input type="hidden" name="export_payroll" value="1">
                                
                                <div class="mb-3">
                                    <label for="employee_id" class="form-label">Select Employee</label>
                                    <select class="form-select" id="employee_id" name="employee_id" required>
                                        <option value="">-- Choose Employee --</option>
                                        <?php
                                        if ($employees_result) {
                                            $employees_result->data_seek(0);
                                            while ($employee = $employees_result->fetch_assoc()) {
                                                echo '<option value="' . $employee['employee_id'] . '">' . 
                                                    htmlspecialchars($employee['last_name']) . ', ' . 
                                                    htmlspecialchars($employee['first_name']) . ' (' . 
                                                    htmlspecialchars($employee['position']) . ')</option>';
                                            }
                                        }
                                        ?>
                                    </select>
                                </div>
                                
                                <div class="row mb-3">
                                    <div class="col-md-6">
                                        <label for="payroll_start_date" class="form-label">Start Date</label>
                                        <input type="date" class="form-control date-override" id="payroll_start_date" name="payroll_start_date">
                                    </div>
                                    <div class="col-md-6">
                                        <label for="payroll_end_date" class="form-label">End Date</label>
                                        <input type="date" class="form-control date-override" id="payroll_end_date" name="payroll_end_date">
                                    </div>
                                </div>
                                
                                <div class="d-grid">
                                    <button type="submit" class="btn btn-download">
                                        Export Payroll Report
                                    </button>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
                
                <!-- User Activity Export Form -->
                <div class="col-md-6 mb-4">
                    <div class="card report-card h-100">
                        <div class="card-header">
                            <h5 class="card-title">User Activity Reports</h5>
                        </div>
                        <div class="card-body">
                            <form method="post" action="">
                                <input type="hidden" name="export_user_activity" value="1">
                                
                                <div class="mb-3">
                                    <label for="selected_user" class="form-label">Select User (Optional)</label>
                                    <select class="form-select" id="selected_user" name="selected_user">
                                        <option value="">-- All Users --</option>
                                        <?php
                                        if ($users_result) {
                                            $users_result->data_seek(0);
                                            while ($user = $users_result->fetch_assoc()) {
                                                echo '<option value="' . $user['user_id'] . '">' . 
                                                    htmlspecialchars($user['username']) . ' (' . 
                                                    htmlspecialchars($user['first_name']) . ' ' . 
                                                    htmlspecialchars($user['last_name']) . ')</option>';
                                            }
                                        }
                                        ?>
                                    </select>
                                </div>
                                
                                <div class="row mb-3">
                                    <div class="col-md-6">
                                        <label for="activity_start_date" class="form-label">Start Date</label>
                                        <input type="date" class="form-control date-override" id="activity_start_date" name="activity_start_date">
                                    </div>
                                    <div class="col-md-6">
                                        <label for="activity_end_date" class="form-label">End Date</label>
                                        <input type="date" class="form-control date-override" id="activity_end_date" name="activity_end_date">
                                    </div>
                                </div>
                                
                                <div class="d-grid">
                                    <button type="submit" class="btn btn-download">
                                        Export Activity Report
                                    </button>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Row 3: Security Reports -->
            <div class="row mb-4">
                <!-- Login Sessions Export Form -->
                <div class="col-md-6 mb-4">
                    <div class="card report-card h-100">
                        <div class="card-header">
                            <h5 class="card-title">Login Sessions Report</h5>
                        </div>
                        <div class="card-body">
                            <form method="post" action="">
                                <input type="hidden" name="export_login_sessions" value="1">
                                
                                <div class="mb-3">
                                    <label for="selected_user_login" class="form-label">Select User (Optional)</label>
                                    <select class="form-select" id="selected_user_login" name="selected_user_login">
                                        <option value="">-- All Users --</option>
                                        <?php
                                        if ($users_result) {
                                            $users_result->data_seek(0);
                                            while ($user = $users_result->fetch_assoc()) {
                                                echo '<option value="' . $user['user_id'] . '">' . 
                                                    htmlspecialchars($user['username']) . ' (' . 
                                                    htmlspecialchars($user['first_name']) . ' ' . 
                                                    htmlspecialchars($user['last_name']) . ')</option>';
                                            }
                                        }
                                        ?>
                                    </select>
                                </div>
                                
                                <div class="row mb-3">
                                    <div class="col-md-6">
                                        <label for="login_start_date" class="form-label">Start Date</label>
                                        <input type="date" class="form-control date-override" id="login_start_date" name="login_start_date">
                                    </div>
                                    <div class="col-md-6">
                                        <label for="login_end_date" class="form-label">End Date</label>
                                        <input type="date" class="form-control date-override" id="login_end_date" name="login_end_date">
                                    </div>
                                </div>
                                
                                <div class="d-grid">
                                    <button type="submit" class="btn btn-download">
                                        Export Login Report
                                    </button>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
                
                <!-- Quick Reports Panel -->
                <div class="col-md-6 mb-4">
                    <div class="card report-card h-100">
                        <div class="card-header">
                            <h5 class="card-title">Quick Reports</h5>
                        </div>
                        <div class="card-body">
                            <div class="d-grid gap-2">
                                <form method="post" action="" class="mb-2">
                                    <input type="hidden" name="export_all_projects" value="1">
                                    <button type="submit" class="btn btn-outline-primary w-100">
                                        All Projects Summary
                                    </button>
                                </form>
                                
                                <form method="post" action="" class="mb-2">
                                    <input type="hidden" name="export_all_employees" value="1">
                                    <button type="submit" class="btn btn-outline-success w-100">
                                        All Employees Summary
                                    </button>
                                </form>
                                
                                <form method="post" action="" class="mb-2">
                                    <input type="hidden" name="export_vendor_summary" value="1">
                                    <button type="submit" class="btn btn-outline-info w-100">
                                        Vendors Summary
                                    </button>
                                </form>
                                
                                <form method="post" action="">
                                    <input type="hidden" name="export_assets_summary" value="1">
                                    <button type="submit" class="btn btn-outline-warning w-100">
                                        Assets Summary
                                    </button>
                                </form>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="js/sidebar.js"></script>
    <script src="js/header.js"></script>
    
    <script>
        // Global Quick Dates Functionality
        document.addEventListener('DOMContentLoaded', function() {
            console.log('Global quick dates loaded');
            
            // Current selected date range (default to today)
            let currentDateRange = {
                type: 'days',
                value: 0, // today
                startDate: null,
                endDate: null
            };
            
            // Initialize with today as default
            updateDateRange(0, 'days');
            
            // Quick date button handlers
            document.querySelectorAll('.quick-date-btn').forEach(button => {
                button.addEventListener('click', function() {
                    // Remove active class from all buttons
                    document.querySelectorAll('.quick-date-btn').forEach(btn => {
                        btn.classList.remove('active');
                    });
                    
                    // Add active class to clicked button
                    this.classList.add('active');
                    
                    // Get the date range
                    if (this.hasAttribute('data-days')) {
                        const days = parseInt(this.getAttribute('data-days'));
                        updateDateRange(days, 'days');
                    } else if (this.hasAttribute('data-months')) {
                        const months = parseInt(this.getAttribute('data-months'));
                        updateDateRange(months, 'months');
                    } else if (this.hasAttribute('data-years')) {
                        const years = parseInt(this.getAttribute('data-years'));
                        updateDateRange(years, 'years');
                    }
                });
            });
            
            // Function to update the current date range
            function updateDateRange(value, type) {
                const today = new Date();
                let startDate, endDate;
                
                if (type === 'days' && value === 0) {
                    // Today only
                    startDate = new Date(today);
                    endDate = new Date(today);
                } else if (type === 'days') {
                    // Last X days
                    endDate = new Date(today);
                    startDate = new Date(today);
                    startDate.setDate(today.getDate() - value);
                } else if (type === 'months') {
                    // Last X months
                    endDate = new Date(today);
                    startDate = new Date(today);
                    startDate.setMonth(today.getMonth() - value);
                } else if (type === 'years') {
                    // Last X years
                    endDate = new Date(today);
                    startDate = new Date(today);
                    startDate.setFullYear(today.getFullYear() - value);
                }
                
                currentDateRange = {
                    type: type,
                    value: value,
                    startDate: startDate,
                    endDate: endDate
                };
                
                console.log('Date range updated:', currentDateRange);
            }
            
            // Function to format date as YYYY-MM-DD
            function formatDate(date) {
                return date.toISOString().split('T')[0];
            }
            
            // Monitor manual date changes
            document.querySelectorAll('.date-override').forEach(input => {
                input.addEventListener('change', function() {
                    // If user manually sets dates, don't override with quick dates
                    console.log('Manual date change detected');
                });
            });
            
            // Intercept form submissions to apply quick dates if no manual dates are set
            document.querySelectorAll('form').forEach(form => {
                form.addEventListener('submit', function(e) {
                    // Find all date inputs in this form that are empty
                    const dateInputs = form.querySelectorAll('input[type="date"]');
                    let hasManualDates = false;
                    
                    // Check if any date inputs have values
                    dateInputs.forEach(input => {
                        if (input.value.trim() !== '') {
                            hasManualDates = true;
                        }
                    });
                    
                    // If no manual dates are set, apply the current quick date range
                    if (!hasManualDates && currentDateRange.startDate && currentDateRange.endDate) {
                        dateInputs.forEach(input => {
                            if (input.name && input.name.includes('start')) {
                                input.value = formatDate(currentDateRange.startDate);
                            } else if (input.name && input.name.includes('end')) {
                                input.value = formatDate(currentDateRange.endDate);
                            }
                        });
                        
                        console.log('Applied quick date range to form:', {
                            start: formatDate(currentDateRange.startDate),
                            end: formatDate(currentDateRange.endDate)
                        });
                    }
                });
            });
            
            // Form submission handling for exports
            document.querySelectorAll('form[method="post"]').forEach(form => {
                const submitButton = form.querySelector('button[type="submit"]');
                const isExportForm = form.querySelector('input[name*="export"]') !== null;
                
                if (isExportForm && submitButton) {
                    form.addEventListener('submit', function(e) {
                        // Validate required fields
                        const requiredFields = form.querySelectorAll('[required]');
                        let hasEmptyRequired = false;
                        
                        requiredFields.forEach(field => {
                            if (!field.value.trim()) {
                                hasEmptyRequired = true;
                                field.style.borderColor = 'red';
                                field.style.boxShadow = '0 0 0 0.2rem rgba(220, 53, 69, 0.25)';
                            } else {
                                field.style.borderColor = '';
                                field.style.boxShadow = '';
                            }
                        });
                        
                        if (hasEmptyRequired) {
                            e.preventDefault();
                            alert('Please fill in all required fields.');
                            return false;
                        }
                        
                        // Show loading state
                        const originalText = submitButton.innerHTML;
                        submitButton.innerHTML = 'Generating...';
                        submitButton.disabled = true;
                        
                        // Reset button after delay (in case of error)
                        setTimeout(() => {
                            submitButton.innerHTML = originalText;
                            submitButton.disabled = false;
                        }, 1000);
                        
                        return true;
                    });
                }
            });
            
            console.log('Global quick dates functionality initialized');
        });        
    </script>
</body>
</html>

<?php
$conn->close();
?>