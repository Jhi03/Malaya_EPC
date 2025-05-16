<?php
    include('validate_login.php');
    $page_title = "EXPENSES";

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

    //All actions will only update corporate expenses record
    $project_id = 1;

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
        $stmt = $conn->prepare("SELECT * FROM expense WHERE project_id = ?");
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
        $result = $conn->query("SELECT category_id, category_name FROM categories");
        while ($row = $result->fetch_assoc()) {
            $categories[] = $row;
        }

        // Fetch subcategories
        $subcategories = [];
        $result = $conn->query("SELECT subcategory_id, category_name, subcategory_name FROM subcategories");
        while ($row = $result->fetch_assoc()) {
            $subcategories[] = $row;
        }
    // Prepare data for analytics if needed
    $category_totals = [];
    $monthly_totals = [];

    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['save_expense'])) {
        error_log("POST request received");
        $form_mode = $_POST['form_mode'];
        $edit_id = isset($_POST['edit_id']) ? intval($_POST['edit_id']) : 0;
        
        $category = $_POST['category'] ?? '';
        $subcategory = $_POST['subcategory'] ?? '';
        $purchase_date = $_POST['purchase_date'] ?? date('Y-m-d');
        $budget = floatval($_POST['budget'] ?? 0);
        $payee = $_POST['payee'] ?? '';
        $record_description = $_POST['record_description'] ?? '';
        $remarks = $_POST['remarks'] ?? '';
        
        // Handle special fields
        $is_rental = isset($_POST['is_rental']) && $_POST['is_rental'] == 'on';
        $has_tax = isset($_POST['has_tax']) && $_POST['has_tax'] == 'on';
        $has_invoice = isset($_POST['has_invoice']) && $_POST['has_invoice'] == 'on';
        
        $expense = $is_rental ? 0 : floatval($_POST['expense'] ?? 0);
        $rental_rate = $is_rental ? floatval($_POST['rental_rate'] ?? 0) : 0;
        $tax = $has_tax ? floatval($_POST['tax'] ?? 0) : 0;
        $invoice_no = $has_invoice ? $_POST['invoice_no'] ?? '' : '';
        
        // Calculate variance
        $expense_amount = $is_rental ? $rental_rate : $expense;
        $variance = $budget - $expense_amount;
        
        if ($form_mode === 'edit' && $edit_id > 0) {
            // UPDATE EXISTING RECORD
            $stmt = $conn->prepare("UPDATE expense SET 
                category = ?, subcategory = ?, purchase_date = ?, budget = ?, expense = ?, 
                payee = ?, record_description = ?, remarks = ?, variance = ?, tax = ?, 
                rental_rate = ?, invoice_no = ?, edited_by = ?, edit_date = NOW()
                WHERE record_id = ?");
            $stmt->bind_param(
                "sssddsssddssii",
                $category, $subcategory, $purchase_date, $budget, $expense, 
                $payee, $record_description, $remarks, $variance, $tax,
                $rental_rate, $invoice_no, $user_id, $edit_id
            );
            $stmt->execute();
            $stmt->close();
        } else {
            error_log("Form submitted");

            // ADD NEW RECORD
            $stmt = $conn->prepare("INSERT INTO expense (
                project_id, user_id, category, subcategory, purchase_date, budget, expense, 
                payee, record_description, remarks, variance, tax, rental_rate, invoice_no, 
                created_by, creation_date
            ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())");
            $stmt->bind_param(
                "iisssddsssddsis",
                $project_id, $user_id, $category, $subcategory, $purchase_date, $budget, $expense,
                $payee, $record_description, $remarks, $variance, $tax, $rental_rate, $invoice_no,
                $user_id
            );
            // your insert logic here
            if ($insert_success) {
                error_log("Insert succeeded");
            } else {
                error_log("Insert failed: " . mysqli_error($conn));
            }

            $stmt->execute();
            $stmt->close();
        }
        // Redirect to avoid form resubmission
        header("Location: ms_expenses.php");
        exit();
     } else {
        error_log("No POST request");
    }

    // For existing delete record handling, ensure it works with expense table as well
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_record'])) {
        $id = $_POST['record_id'];

        $stmt = $conn->prepare("DELETE FROM expense WHERE record_id = ?");
        $stmt->bind_param("i", $id);
        $stmt->execute();
        $stmt->close();

        header("Location: ms_records.php?projectId=" . $project_id);
        exit();
    }

    // Add these lines to fetch expense records for the project
    $expense_records = [];
    if ($project_id > 0) {
        $expense_stmt = $conn->prepare("
            SELECT e.*, 
                CONCAT(emp.first_name, ' ', emp.last_name) as creator_name,
                CONCAT(emp2.first_name, ' ', emp2.last_name) as editor_name
            FROM expense e
            LEFT JOIN employee emp ON e.created_by = emp.employee_id
            LEFT JOIN employee emp2 ON e.edited_by = emp2.employee_id
            WHERE e.project_id = ?
            ORDER BY e.purchase_date DESC
        ");
        $expense_stmt->bind_param("i", $project_id);
        $expense_stmt->execute();
        $expense_result = $expense_stmt->get_result();
        while ($row = $expense_result->fetch_assoc()) {
            $expense_records[] = $row;
        }
        $expense_stmt->close();
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
            <!-- Add Records, Search, Filter, and Toggle Bar -->
            <div class="search-filter-bar">
                <!-- Left group: Add, Search, Filter -->
                <div class="left-controls">
                    <button onclick="openExpenseModal('add')" class="add-record-btn">ADD RECORD</button>

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
            <div class="table-responsive">
                <table class="table">
                    <thead>
                        <tr>
                            <th> </th>
                            <th>Category</th>
                            <th>Description</th>
                            <th>Payee</th>
                            <th>Budget</th>
                            <th>Expense</th>
                            <th>Variance</th>
                            <th>Tax</th>
                            <th>Remarks</th>
                            <th>Date</th>
                            <th> </th>                            
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (count($records) > 0): ?>
                            <?php foreach ($records as $i => $row): ?>
                                <tr>
                                    <td><?= $i + 1 ?></td>
                                    <td><?= htmlspecialchars($row['category']) ?></td>
                                    <td title="<?= htmlspecialchars($row['record_description']) ?>">
                                        <?= htmlspecialchars($row['record_description']) ?>
                                    </td>
                                    <td><?= htmlspecialchars($row['payee']) ?></td>
                                    <td>₱<?= number_format($row['budget'], 2) ?></td>
                                    <td>₱<?= number_format($row['expense'], 2) ?></td>
                                    <td>₱<?= number_format($row['variance'], 2) ?></td>
                                    <td>₱<?= number_format($row['tax'], 2) ?></td>
                                    <td title="<?= htmlspecialchars($row['remarks']) ?>">
                                        <?= htmlspecialchars($row['remarks']) ?>
                                    </td>
                                    <td><?= date("M d, Y", strtotime($row['purchase_date'])) ?></td>
                                    <td>
                                        <button class="btn btn-info btn-sm view-btn"
                                            data-id="<?= $row['record_id'] ?>"
                                            data-category="<?= htmlspecialchars($row['category']) ?>"
                                            data-subcategory="<?= htmlspecialchars($row['subcategory']) ?>"
                                            data-date="<?= $row['purchase_date'] ?>"
                                            data-budget="<?= $row['budget'] ?>"
                                            data-expense="<?= $row['expense'] ?>"
                                            data-payee="<?= htmlspecialchars($row['payee']) ?>"
                                            data-record_description="<?= htmlspecialchars($row['record_description']) ?>"
                                            data-remarks="<?= htmlspecialchars($row['remarks']) ?>"
                                            data-rental_rate="<?= $row['rental_rate'] ?>"
                                            data-tax="<?= $row['tax'] ?>"
                                            data-variance="<?= $row['variance'] ?>"
                                            data-invoice_no="<?= htmlspecialchars($row['invoice_no']) ?>">
                                            <img src="icons/eye.svg" width="16" alt="View">
                                        </button>
                                        <button type="button" class="btn btn-sm btn-primary edit-btn"
                                            data-id="<?= $row['record_id'] ?>"
                                            data-category="<?= htmlspecialchars($row['category']) ?>"
                                            data-subcategory="<?= htmlspecialchars($row['subcategory']) ?>"
                                            data-date="<?= $row['purchase_date'] ?>"
                                            data-budget="<?= $row['budget'] ?>"
                                            data-expense="<?= $row['expense'] ?>"
                                            data-payee="<?= htmlspecialchars($row['payee']) ?>"
                                            data-record_description="<?= htmlspecialchars($row['record_description']) ?>"
                                            data-remarks="<?= htmlspecialchars($row['remarks']) ?>"
                                            data-rental_rate="<?= $row['rental_rate'] ?>"
                                            data-tax="<?= $row['tax'] ?>"
                                            data-invoice_no="<?= htmlspecialchars($row['invoice_no']) ?>">
                                            <img src="icons/pencil.svg" width="16" alt="Edit">
                                        </button>
                                        <button type="button" class="btn btn-sm btn-danger" onclick="deleteExpense(<?= $row['record_id'] ?>)">
                                            <img src="icons/trash.svg" alt="Delete" width="18">
                                        </button>
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
                                <h6>Total Expense</h6>
                                <p class="fs-5 fw-bold text-black">₱<?= number_format(array_sum(array_column($records, 'expense')), 2) ?></p>
                            </div>
                            <div class="card shadow rounded-4 p-3 mb-3">
                                <h6>Total Variance</h6>
                                <p class="fs-5 fw-bold text-black">₱<?= number_format(array_sum(array_column($records, 'budget')) - array_sum(array_column($records, 'expense')), 2) ?></p>
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
                                        <h6 class="text-center">Weekly Budget vs Expense</h6>
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
    </div>

    <!-- ADD/EDIT RECORD MODAL -->
    <div class="modal fade" id="expenseModal" tabindex="-1" aria-labelledby="expenseModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <form id="expenseForm" method="post">
                    <div class="modal-header">
                        <h5 class="modal-title" id="expenseModalLabel">Add Expense</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
                        <input type="hidden" id="record_id" name="record_id">
                        <input type="hidden" id="form_mode" name="form_mode" value="add">
                        <input type="hidden" id="edit_id" name="edit_id" value="0">
                        
                        <div class="form-section">
                            <div class="form-section-title">Expense Information</div>
                            <div class="row g-3">
                                <div class="col-md-6">
                                    <label for="category" class="form-label">Category</label>
                                    <select id="category" name="category" class="form-select" required>
                                        <option value="">Select Category</option>
                                        <?php foreach ($categories as $cat): ?>
                                            <option value="<?php echo htmlspecialchars($cat['category_name']); ?>">
                                                <?php echo htmlspecialchars($cat['category_name']); ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                                <div class="col-md-6">
                                    <label for="subcategory" class="form-label">Subcategory</label>
                                    <select id="subcategory" name="subcategory" class="form-select" disabled required>
                                        <option value="">Select Subcategory</option>
                                    </select>
                                </div>
                                <div class="col-md-6">
                                    <label for="purchase_date" class="form-label">Date</label>
                                    <input type="date" class="form-control" id="purchase_date" name="purchase_date" required value="<?php echo date('Y-m-d'); ?>">
                                </div>
                                <div class="col-md-6">
                                    <label for="payee" class="form-label">Payee</label>
                                    <input type="text" class="form-control" id="payee" name="payee" required>
                                </div>
                                <div class="col-md-12">
                                    <label for="record_description" class="form-label">Description</label>
                                    <input type="text" class="form-control" id="record_description" name="record_description" required>
                                </div>
                            </div>
                        </div>
                        
                        <div class="form-section">
                            <div class="form-section-title">Budget and Expense</div>
                            <div class="row g-3">
                                <div class="col-md-6">
                                    <label for="budget" class="form-label">Budget</label>
                                    <input type="number" step="0.01" class="form-control calculation" id="budget" name="budget" value="0.00">
                                </div>
                                <div class="col-md-6">
                                    <div class="d-flex align-items-center mb-2">
                                        <input type="checkbox" id="is_rental" class="form-check-input me-2">
                                        <label for="is_rental" class="form-check-label">Is Rental?</label>
                                    </div>
                                    <div id="expense_input">
                                        <label for="expense" class="form-label">Expense</label>
                                        <input type="number" step="0.01" class="form-control calculation" id="expense" name="expense" required value="0.00">
                                    </div>
                                    <div id="rental_input" style="display: none;">
                                        <label for="rental_rate" class="form-label">Rental Rate</label>
                                        <input type="number" step="0.01" class="form-control calculation" id="rental_rate" name="rental_rate" value="0.00">
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <label for="variance" class="form-label">Variance</label>
                                    <input type="number" step="0.01" class="form-control" id="variance" name="variance" readonly>
                                </div>
                            </div>
                        </div>
                        
                        <div class="form-section">
                            <div class="form-section-title">Additional Information</div>
                            <div class="row g-3">
                                <div class="col-md-6">
                                    <div class="d-flex align-items-center mb-2">
                                        <input type="checkbox" id="has_tax" class="form-check-input me-2">
                                        <label for="has_tax" class="form-check-label">Include Tax?</label>
                                    </div>
                                    <div id="tax_input" style="display: none;">
                                        <label for="tax" class="form-label">Tax</label>
                                        <input type="number" step="0.01" class="form-control calculation" id="tax" name="tax" value="0.00">
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="d-flex align-items-center mb-2">
                                        <input type="checkbox" id="has_invoice" class="form-check-input me-2">
                                        <label for="has_invoice" class="form-check-label">Has Invoice?</label>
                                    </div>
                                    <div id="invoice_input" style="display: none;">
                                        <label for="invoice_no" class="form-label">Invoice No.</label>
                                        <input type="text" class="form-control" id="invoice_no" name="invoice_no">
                                    </div>
                                </div>
                                <div class="col-md-12">
                                    <label for="remarks" class="form-label">Remarks</label>
                                    <textarea class="form-control" id="remarks" name="remarks" rows="3"></textarea>
                                </div>
                            </div>
                        </div>
                        
                        <div id="expenseSummary" class="bg-light p-3 rounded">
                            <h6>Expense Summary</h6>
                            <div class="calculation-row">
                                <span>Budget Amount:</span>
                                <span id="summary_budget">₱0.00</span>
                            </div>
                            <div class="calculation-row" id="summary_expense_row">
                                <span>Expense Amount:</span>
                                <span id="summary_expense">₱0.00</span>
                            </div>
                            <div class="calculation-row" id="summary_rental_row" style="display: none;">
                                <span>Rental Rate:</span>
                                <span id="summary_rental">₱0.00</span>
                            </div>
                            <div class="calculation-row" id="summary_tax_row" style="display: none;">
                                <span>Tax Amount:</span>
                                <span id="summary_tax">₱0.00</span>
                            </div>
                            <div class="calculation-row total">
                                <span>Variance:</span>
                                <span id="summary_variance">₱0.00</span>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">CANCEL</button>
                        <button type="submit" name="save_expense" class="btn btn-primary">SAVE</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- View Expense Modal -->
    <div class="modal fade" id="viewExpenseModal" tabindex="-1" aria-labelledby="viewExpenseModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="viewExpenseModalLabel">Expense Details</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="row mb-4">
                        <div class="col-md-12">
                            <h6>Basic Information</h6>
                            <table class="table table-bordered">
                                <tr>
                                    <th class="viewRecord" style="width: 5%; text-align: left;">Category</th>
                                    <td id="view_category"></td>
                                </tr>
                                <tr>
                                    <th class="viewRecord" style="width: 5%; text-align: left;">Subcategory</th>
                                    <td id="view_subcategory"></td>
                                </tr>
                                <tr>
                                    <th class="viewRecord" style="width: 5%; text-align: left;">Purchase Date</th>
                                    <td id="view_purchase_date"></td>
                                </tr>
                                <tr>
                                    <th class="viewRecord" style="width: 5%; text-align: left;">Description</th>
                                    <td id="view_description"></td>
                                </tr>
                            </table>
                        </div>
                    </div>
                    
                    <div class="row mb-4">
                        <div class="col-md-6">
                            <h6>Financial Details</h6>
                            <table class="table table-bordered">
                                <tr>
                                    <th class="viewRecord" style="width: 5%; text-align: left;">Budget</th>
                                    <td id="view_budget"></td>
                                </tr>
                                <tr>
                                    <th class="viewRecord" style="width: 5%; text-align: left;">Expense</th>
                                    <td id="view_expense"></td>
                                </tr>
                                <tr>
                                    <th class="viewRecord" style="width: 5%; text-align: left;">Variance</th>
                                    <td id="view_variance"></td>
                                </tr>
                                <tr class="table-secondary">
                                    <th class="viewRecord" style="width: 5%; text-align: left;">Rental Rate</th>
                                    <td id="view_rental_rate"></td>
                                </tr>
                                <tr>
                                    <th class="viewRecord" style="width: 5%; text-align: left;">Tax</th>
                                    <td id="view_tax"></td>
                                </tr>
                            </table>
                        </div>

                        <div class="col-md-6">
                            <h6>Payment Information</h6>
                            <table class="table table-bordered">
                                <tr>
                                    <th class="viewRecord" style="width: 5%; text-align: left;">Payee</th>
                                    <td id="view_payee"></td>
                                </tr>
                                <tr>
                                    <th class="viewRecord" style="width: 5%; text-align: left;">Invoice No.</th>
                                    <td id="view_invoice_no"></td>
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
                    
                    <div class="row mt-4">
                        <div class="col-md-12">
                            <h6>Record Information</h6>
                            <table class="table table-bordered">
                                <tr>
                                    <th class="viewRecord" style="width: 5%; text-align: left;">Created By</th>
                                    <td id="view_created_by"></td>
                                </tr>
                                <tr>
                                    <th class="viewRecord" style="width: 5%; text-align: left;">Creation Date</th>
                                    <td id="view_creation_date"></td>
                                </tr>
                                <tr>
                                    <th class="viewRecord" style="width: 5%; text-align: left;">Last Edited By</th>
                                    <td id="view_edited_by"></td>
                                </tr>
                                <tr>
                                    <th class="viewRecord" style="width: 5%; text-align: left;">Last Edit Date</th>
                                    <td id="view_edit_date"></td>
                                </tr>
                            </table>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                </div>
            </div>
        </div>
    </div>
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>

    <script src="js/sidebar.js"></script>
    <script src="js/header.js"></script>
    
    <script>
            $(document).ready(function () {
                $(document).on('click', '.view-btn', function () {
                    const modal = new bootstrap.Modal(document.getElementById('viewExpenseModal'));

                    $('#view_category').text($(this).data('category') || 'N/A');
                    $('#view_subcategory').text($(this).data('subcategory') || 'N/A');
                    $('#view_purchase_date').text($(this).data('date') || 'N/A');
                    $('#view_description').text($(this).data('record_description') || 'N/A');
                    $('#view_budget').text('₱' + parseFloat($(this).data('budget') || 0).toFixed(2));
                    $('#view_expense').text('₱' + parseFloat($(this).data('expense') || 0).toFixed(2));
                    $('#view_variance').text('₱' + parseFloat($(this).data('variance') || 0).toFixed(2));
                    $('#view_rental_rate').text('₱' + parseFloat($(this).data('rental_rate') || 0).toFixed(2));
                    $('#view_tax').text('₱' + parseFloat($(this).data('tax') || 0).toFixed(2));
                    $('#view_payee').text($(this).data('payee') || 'N/A');
                    $('#view_invoice_no').text($(this).data('invoice_no') || 'N/A');
                    $('#view_remarks').text($(this).data('remarks') || 'No remarks');

                    $.post('get_expense_details.php', {
                    record_id: $(this).data('id')
                    }, function (response) {
                    if (response.success) {
                        $('#view_created_by').text(response.created_by_name || 'Unknown');
                        $('#view_creation_date').text(response.creation_date || 'N/A');
                        $('#view_edited_by').text(response.edited_by_name || 'N/A');
                        $('#view_edit_date').text(response.edit_date || 'N/A');
                    }
                    }, 'json');

                    modal.show();
                });
            });
        </script>
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
    </script>
    <script>
        document.querySelectorAll('.edit-btn').forEach(button => {
            button.addEventListener('click', function(e) {
                e.preventDefault();
                
                const recordData = {
                    id: this.getAttribute('data-id'),
                    category: this.getAttribute('data-category'),
                    subcategory: this.getAttribute('data-subcategory'),
                    date: this.getAttribute('data-date'),
                    budget: this.getAttribute('data-budget'),
                    expense: this.getAttribute('data-expense'),
                    payee: this.getAttribute('data-payee'),
                    record_description: this.getAttribute('data-record_description'),
                    remarks: this.getAttribute('data-remarks'),
                    rental_rate: this.getAttribute('data-rental_rate') || 0,
                    tax: this.getAttribute('data-tax') || 0,
                    invoice_no: this.getAttribute('data-invoice_no') || ''
                };

                // Call your existing modal open function in edit mode
                openExpenseModal('edit', recordData);
            });
        });

        document.addEventListener('DOMContentLoaded', function() {
            // Store all subcategories from PHP in a JavaScript variable
            const subcategories = <?php echo json_encode($subcategories); ?>;

            // Category dropdown change handler
            document.getElementById('category').addEventListener('change', function () {
                const categorySelect = document.getElementById('category');
                const subcategorySelect = document.getElementById('subcategory');
                const selectedCategoryName = categorySelect.value;

                // Clear previous options
                subcategorySelect.innerHTML = '<option value="">Select Subcategory</option>';

                // Filter subcategories by category_name
                const filteredSubcategories = subcategories.filter(item =>
                    item.category_name === selectedCategoryName
                );

                if (filteredSubcategories.length > 0) {
                    subcategorySelect.disabled = false;
                    subcategorySelect.setAttribute('required', 'required');

                    filteredSubcategories.forEach(subcategory => {
                        const option = document.createElement('option');
                        option.value = subcategory.subcategory_name;
                        option.textContent = subcategory.subcategory_name;
                        subcategorySelect.appendChild(option);
                    });
                } else {
                    // No subcategories — disable and make it not required
                    subcategorySelect.disabled = true;
                    subcategorySelect.removeAttribute('required');
                }
            });
            
            // Checkbox event listeners
            document.getElementById('is_rental').addEventListener('change', function() {
                const expenseInput = document.getElementById('expense_input');
                const rentalInput = document.getElementById('rental_input');
                const summaryExpenseRow = document.getElementById('summary_expense_row');
                const summaryRentalRow = document.getElementById('summary_rental_row');
                
                if (this.checked) {
                    expenseInput.style.display = 'none';
                    rentalInput.style.display = 'block';
                    document.getElementById('expense').value = '0.00';
                    summaryExpenseRow.style.display = 'none';
                    summaryRentalRow.style.display = 'flex';
                } else {
                    expenseInput.style.display = 'block';
                    rentalInput.style.display = 'none';
                    document.getElementById('rental_rate').value = '0.00';
                    summaryExpenseRow.style.display = 'flex';
                    summaryRentalRow.style.display = 'none';
                }
                updateCalculations();
            });
            
            document.getElementById('has_tax').addEventListener('change', function() {
                const taxInput = document.getElementById('tax_input');
                const summaryTaxRow = document.getElementById('summary_tax_row');
                
                if (this.checked) {
                    taxInput.style.display = 'block';
                    summaryTaxRow.style.display = 'flex';
                } else {
                    taxInput.style.display = 'none';
                    document.getElementById('tax').value = '0.00';
                    summaryTaxRow.style.display = 'none';
                }
                updateCalculations();
            });
            
            document.getElementById('has_invoice').addEventListener('change', function() {
                const invoiceInput = document.getElementById('invoice_input');
                
                if (this.checked) {
                    invoiceInput.style.display = 'block';
                } else {
                    invoiceInput.style.display = 'none';
                    document.getElementById('invoice_no').value = '';
                }
            });
            
            // Input calculation event listeners
            const calculationInputs = document.querySelectorAll('.calculation');
            calculationInputs.forEach(input => {
                input.addEventListener('input', updateCalculations);
            });
            
            // Function to update all calculations
            function updateCalculations() {
                const budget = parseFloat(document.getElementById('budget').value) || 0;
                const isRental = document.getElementById('is_rental').checked;
                const expense = isRental ? 
                    (parseFloat(document.getElementById('rental_rate').value) || 0) : 
                    (parseFloat(document.getElementById('expense').value) || 0);
                const hasTax = document.getElementById('has_tax').checked;
                const tax = hasTax ? (parseFloat(document.getElementById('tax').value) || 0) : 0;
                
                // Calculate variance
                const variance = budget - expense;
                document.getElementById('variance').value = variance.toFixed(2);
                
                // Update summary display
                document.getElementById('summary_budget').textContent = '₱' + budget.toFixed(2);
                
                if (isRental) {
                    document.getElementById('summary_rental').textContent = '₱' + expense.toFixed(2);
                } else {
                    document.getElementById('summary_expense').textContent = '₱' + expense.toFixed(2);
                }
                
                if (hasTax) {
                    document.getElementById('summary_tax').textContent = '₱' + tax.toFixed(2);
                }
                
                document.getElementById('summary_variance').textContent = '₱' + variance.toFixed(2);
            }
            
            // Function to open the expense modal (add or edit mode)
            window.openExpenseModal = function(mode, recordData = null) {
                const modal = document.getElementById('expenseModal');
                const modalTitle = document.getElementById('expenseModalLabel');
                const formMode = document.getElementById('form_mode');
                const editId = document.getElementById('edit_id');
                
                // Reset form
                document.getElementById('expenseForm').reset();
                
                // Reset all inputs to default state
                document.getElementById('expense_input').style.display = 'block';
                document.getElementById('rental_input').style.display = 'none';
                document.getElementById('tax_input').style.display = 'none';
                document.getElementById('invoice_input').style.display = 'none';
                document.getElementById('summary_expense_row').style.display = 'flex';
                document.getElementById('summary_rental_row').style.display = 'none';
                document.getElementById('summary_tax_row').style.display = 'none';
                
                if (mode === 'add') {
                    modalTitle.textContent = 'Add New Expense Record';
                    formMode.value = 'add';
                    editId.value = '0';
                    document.getElementById('purchase_date').value = new Date().toISOString().split('T')[0]; // Today's date
                } else if (mode === 'edit' && recordData) {
                    modalTitle.textContent = 'Edit Expense Record';
                    formMode.value = 'edit';
                    editId.value = recordData.id;
                    
                    // Fill the form with existing data
                    document.getElementById('category').value = recordData.category;
                    // Trigger category change to load subcategories
                    document.getElementById('category').dispatchEvent(new Event('change'));
                    
                    setTimeout(() => {
                        document.getElementById('subcategory').value = recordData.subcategory;
                    }, 100);
                    
                    document.getElementById('purchase_date').value = recordData.date;
                    document.getElementById('payee').value = recordData.payee;
                    
                    document.getElementById('record_description').value = recordData.record_description;
                    document.getElementById('budget').value = recordData.budget;
                    document.getElementById('remarks').value = recordData.remarks;
                    
                    // Handle special fields
                    if (recordData.rental_rate && recordData.rental_rate > 0) {
                        document.getElementById('is_rental').checked = true;
                        document.getElementById('rental_rate').value = recordData.rental_rate;
                        document.getElementById('expense_input').style.display = 'none';
                        document.getElementById('rental_input').style.display = 'block';
                        document.getElementById('summary_expense_row').style.display = 'none';
                        document.getElementById('summary_rental_row').style.display = 'flex';
                    } else {
                        document.getElementById('expense').value = recordData.expense;
                    }
                    
                    if (recordData.tax && recordData.tax > 0) {
                        document.getElementById('has_tax').checked = true;
                        document.getElementById('tax').value = recordData.tax;
                        document.getElementById('tax_input').style.display = 'block';
                        document.getElementById('summary_tax_row').style.display = 'flex';
                    }
                    
                    if (recordData.invoice_no) {
                        document.getElementById('has_invoice').checked = true;
                        document.getElementById('invoice_no').value = recordData.invoice_no;
                        document.getElementById('invoice_input').style.display = 'block';
                    }
                    
                    // Update calculations
                    updateCalculations();
                }
                
                // Show the modal
                const bsModal = new bootstrap.Modal(modal);
                bsModal.show();
            };
            
            // Handle form submission
            document.getElementById('expenseForm').addEventListener('submit', function(e) {
                
                // Form validation
                if (!this.checkValidity()) {
                    e.stopPropagation();
                    this.classList.add('was-validated');
                    return;
                }
                
                // Submit the form
                this.submit();
            });
        });

        // Function to edit expense record
        function editExpense(id, category, subcategory, date, budget, expense, payee, record_description, remarks, rental_rate, tax, invoice_no) {
            openExpenseModal('edit', {
                id: id,
                category: category,
                subcategory: subcategory,
                date: date,
                budget: budget,
                expense: expense,
                payee: payee,
                record_description: record_description,
                remarks: remarks,
                rental_rate: rental_rate,
                tax: tax,
                invoice_no: invoice_no
            });
        }

        // Function to delete expense record
        function deleteExpense(id) {
            if (confirm('Are you sure you want to delete this expense record?')) {
                const form = document.createElement('form');
                form.method = 'POST';
                form.style.display = 'none';
                
                const idInput = document.createElement('input');
                idInput.type = 'hidden';
                idInput.name = 'record_id';
                idInput.value = id;
                
                const deleteInput = document.createElement('input');
                deleteInput.type = 'hidden';
                deleteInput.name = 'delete_record';
                deleteInput.value = '1';
                
                form.appendChild(idInput);
                form.appendChild(deleteInput);
                document.body.appendChild(form);
                
                form.submit();
            }
        }
    </script>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const subcategories = <?php echo json_encode($subcategories); ?>;

            const categorySelect = document.getElementById('category');
            const subcategorySelect = document.getElementById('subcategory');

            categorySelect.addEventListener('change', function() {
                const selectedCategoryName = this.value;
                
                // Clear existing subcategory options
                subcategorySelect.innerHTML = '<option value="">Select Subcategory</option>';
                
                if (selectedCategoryName) {
                    // Enable subcategory dropdown
                    subcategorySelect.disabled = false;
                    
                    // Filter subcategories by category_name (string)
                    const filteredSubcategories = subcategories.filter(subcat => 
                        subcat.category_name === selectedCategoryName
                    );
                    
                    // Populate subcategory dropdown
                    filteredSubcategories.forEach(subcat => {
                        const option = document.createElement('option');
                        option.value = subcat.subcategory_name;
                        option.textContent = subcat.subcategory_name;
                        subcategorySelect.appendChild(option);
                    });
                } else {
                    // Disable subcategory dropdown if no category selected
                    subcategorySelect.disabled = true;
                }
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
        $weekly_expense_data = [];

        $temp_buckets = [];

        foreach ($records as $r) {
            $timestamp = strtotime($r['purchase_date']);
            $week_num = date('W', $timestamp);
            $year = date('o', $timestamp);

            $key = "$year-W$week_num";

            // Get start and end of the week (Mon-Sun)
            $start_of_week = date('M d', strtotime($year . "W" . $week_num));
            $end_of_week = date('M d', strtotime($year . "W" . $week_num . " +6 days"));

            $range = "$start_of_week - $end_of_week";
            $label = "Week $week_counter\n$range";

            if (!isset($temp_buckets[$key])) {
                $temp_buckets[$key] = ['budget' => 0, 'expense' => 0, 'label' => $label];
                $week_counter++;
            }

            $temp_buckets[$key]['budget'] += $r['budget'];
            $temp_buckets[$key]['expense'] += $r['expense'];
        }

        foreach ($temp_buckets as $entry) {
            $week_labels[] = $entry['label'];
            $weekly_budget_data[] = $entry['budget'];
            $weekly_expense_data[] = $entry['expense'];
        }
        ?>

        const weekLabels = <?= json_encode($week_labels) ?>;
        const weeklyBudgetData = <?= json_encode($weekly_budget_data) ?>;
        const weeklyExpenseData = <?= json_encode($weekly_expense_data) ?>;

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
                        label: 'Expense',
                        data: weeklyExpenseData,
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
            doc.text("Total Expense: ₱<?= number_format(array_sum(array_column($records, 'expense')), 2) ?>", 10, 40);
            doc.text("Variance: ₱<?= number_format(array_sum(array_column($records, 'budget')) - array_sum(array_column($records, 'expense')), 2) ?>", 10, 50);
            doc.text("Tax: ₱<?= number_format(array_sum(array_column($records, 'tax')), 2) ?>", 10, 60);
            doc.save("analytics-report.pdf");
        });
    </script>
</body>
</html>