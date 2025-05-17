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

    // Handle POST updates or deletion
    $response = '';
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        // Delete project
        if (isset($_POST['delete_project_id'])) {
            $del_id = intval($_POST['delete_project_id']);
            $del_stmt = $conn->prepare("DELETE FROM projects WHERE project_id = ?");
            $del_stmt->bind_param("i", $del_id);
            if ($del_stmt->execute()) {
                echo "success";
            } else {
                echo "Failed to delete project.";
            }
            $del_stmt->close();
            exit;
        }

        // Update project
        if (isset($_POST['update_project']) && $project_id > 0) {
            // Sanitize and prepare update
            $project_name = $_POST['project_name'] ?? '';
            $project_code = $_POST['project_code'] ?? '';
            $first_name = $_POST['first_name'] ?? '';
            $last_name = $_POST['last_name'] ?? '';
            $contact = $_POST['contact'] ?? '';
            $email = $_POST['email'] ?? '';
            $company_name = $_POST['company_name'] ?? '';
            $unit_building_no = $_POST['unit_building_no'] ?? '';
            $street = $_POST['street'] ?? '';
            $barangay = $_POST['barangay'] ?? '';
            $city = $_POST['city'] ?? '';
            $country = $_POST['country'] ?? '';
            $description = $_POST['description'] ?? '';
            $edit_date = date('Y-m-d H:i:s');
            $edited_by = $user_id;

            $update_stmt = $conn->prepare("
                UPDATE projects SET 
                    project_name = ?, project_code = ?, first_name = ?, last_name = ?, contact = ?, email = ?, company_name = ?,
                    unit_building_no = ?, street = ?, barangay = ?, city = ?, country = ?, description = ?, 
                    edit_date = ?, edited_by = ?
                WHERE project_id = ?
            ");
            $update_stmt->bind_param(
                "ssssssssssssssii",
                $project_name, $project_code, $first_name, $last_name, $contact, $email, $company_name,
                $unit_building_no, $street, $barangay, $city, $country, $description,
                $edit_date, $edited_by,
                $project_id
            );
            if ($update_stmt->execute()) {
                $response = "Project updated successfully.";
            } else {
                $response = "Failed to update project.";
            }
            $update_stmt->close();
        }
    }

    if ($project_id > 0) {
        // Get project details
        $project_stmt = $conn->prepare("
            SELECT 
                p.project_id, p.project_name, p.project_code, p.first_name, p.last_name,
                p.company_name, p.description, p.creation_date, 
                p.created_by, p.edit_date, p.edited_by,
                p.email, p.contact, p.unit_building_no, p.street, p.barangay, p.city, p.country
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
        if ($project) {
            $stmt = $conn->prepare("SELECT * FROM expense WHERE project_id = ?");
            $stmt->bind_param("i", $project['project_id']);
            $stmt->execute();
            $result = $stmt->get_result();
            while ($row = $result->fetch_assoc()) {
                $records[] = $row;
            }
            $stmt->close();
        }
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

    if ($_SERVER['REQUEST_METHOD'] === 'POST' && (isset($_POST['save_expense']) || isset($_POST['form_mode']))) {
        error_log("POST request received");
        $form_mode = $_POST['form_mode'];
        $edit_id = isset($_POST['edit_id']) ? intval($_POST['edit_id']) : 0;
        
        $category = $_POST['category'] ?? '';
        $subcategory = $_POST['subcategory'] ?? '';
        $purchase_date = $_POST['purchase_date'] ?? date('Y-m-d');
        $has_budget = isset($_POST['has_budget']) && $_POST['has_budget'] === 'on';
        $budget = $has_budget ? floatval($_POST['budget'] ?? 0) : 0;
        $payee = $_POST['payee'] ?? '';
        $record_description = $_POST['record_description'] ?? '';
        $remarks = $_POST['remarks'] ?? '';
        $bill_to_client = $_POST['bill_to_client'] ?? 'No';
        $is_rental = $_POST['is_rental'] ?? 'No';
        
        // Handle special fields
        $expense = $is_rental === 'Yes' ? 0 : floatval($_POST['expense'] ?? 0);
        $rental_rate = $is_rental === 'Yes' ? floatval($_POST['rental_rate'] ?? 0) : 0;
        $has_tax = isset($_POST['has_tax']) && $_POST['has_tax'] == 'on';
        $has_invoice = isset($_POST['has_invoice']) && $_POST['has_invoice'] == 'on';
        
        $tax = $has_tax ? floatval($_POST['tax'] ?? 0) : 0;
        $invoice_no = $has_invoice ? $_POST['invoice_no'] ?? '' : '';
        
        // Calculate variance
        $expense_amount = $is_rental === 'Yes' ? $rental_rate : $expense;
        $variance = $budget - $expense_amount;
        
        // Determine is_company_loss and bill_to_client based on scenarios
        $is_company_loss = 'No';
        
        // Scenario 1: Budget checked, expense <= budget
        if ($has_budget && $expense_amount <= $budget) {
            $is_company_loss = 'No';
            // bill_to_client already set by form
        }
        // Scenario 2: Budget NOT checked, bill_to_client NOT checked
        else if (!$has_budget && $bill_to_client === 'No') {
            $is_company_loss = 'Yes';
            // loss_id will be set after insert
        }
        // Scenario 3: Budget checked, expense > budget, bill_to_client NOT checked
        else if ($has_budget && $expense_amount > $budget && $bill_to_client === 'No') {
            $is_company_loss = 'Yes';
            // loss_id will be set after insert
        }
        // Scenario 4: Budget checked, expense > budget, bill_to_client checked
        else if ($has_budget && $expense_amount > $budget && $bill_to_client === 'Yes') {
            $is_company_loss = 'No';
            // loss_id remains NULL
        }
        
        // Begin transaction for data integrity
        $conn->begin_transaction();
        
        try {
            if ($form_mode === 'edit' && $edit_id > 0) {
                // First, get current record data to check for changes
                $get_current = $conn->prepare("
                    SELECT is_company_loss, loss_id FROM expense 
                    WHERE record_id = ?
                ");
                $get_current->bind_param("i", $edit_id);
                $get_current->execute();
                $result = $get_current->get_result();
                $current_record = $result->fetch_assoc();
                $get_current->close();
                
                $old_is_company_loss = $current_record['is_company_loss'] ?? 'No';
                $old_loss_id = $current_record['loss_id'];
                
                // UPDATE EXISTING RECORD
                $stmt = $conn->prepare("UPDATE expense SET 
                    category = ?, subcategory = ?, purchase_date = ?, budget = ?, expense = ?, 
                    payee = ?, record_description = ?, remarks = ?, variance = ?, tax = ?, 
                    rental_rate = ?, invoice_no = ?, bill_to_client = ?, is_rental = ?, 
                    is_company_loss = ?, edited_by = ?, edit_date = NOW()
                    WHERE record_id = ?");
                $stmt->bind_param(
                    "sssddsssdddssssii",
                    $category, $subcategory, $purchase_date, $budget, $expense, 
                    $payee, $record_description, $remarks, $variance, $tax,
                    $rental_rate, $invoice_no, $bill_to_client, $is_rental,
                    $is_company_loss, $user_id, $edit_id
                );
                $stmt->execute();
                $stmt->close();
                
                // If changing from not a company loss to a company loss
                if ($old_is_company_loss === 'No' && $is_company_loss === 'Yes') {
                    // Set the loss_id to point to itself
                    $update_loss_id = $conn->prepare("UPDATE expense SET loss_id = ? WHERE record_id = ?");
                    $update_loss_id->bind_param("ii", $edit_id, $edit_id);
                    $update_loss_id->execute();
                    $update_loss_id->close();
                    
                    // Add a duplicate record to Corporate
                    $corporate_project_id = 1;
                    $duplicate_stmt = $conn->prepare("INSERT INTO expense (
                        project_id, user_id, category, subcategory, purchase_date, budget, expense, 
                        payee, record_description, remarks, variance, tax, rental_rate, invoice_no, 
                        bill_to_client, is_rental, is_company_loss, loss_id, created_by, creation_date
                    ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())");
                    $duplicate_stmt->bind_param(
                        "iisssddsssddsssssii",
                        $corporate_project_id, $user_id, $category, $subcategory, $purchase_date, $budget, $expense,
                        $payee, $record_description, $remarks, $variance, $tax, $rental_rate, $invoice_no,
                        $bill_to_client, $is_rental, $is_company_loss, $edit_id, $user_id
                    );
                    $duplicate_stmt->execute();
                    $duplicate_stmt->close();
                } 
                // If changing from a company loss to not a company loss
                else if ($old_is_company_loss === 'Yes' && $is_company_loss === 'No') {
                    // Remove the loss_id
                    $remove_loss_id = $conn->prepare("UPDATE expense SET loss_id = NULL WHERE record_id = ?");
                    $remove_loss_id->bind_param("i", $edit_id);
                    $remove_loss_id->execute();
                    $remove_loss_id->close();
                    
                    // Delete any corporate records that reference this as a loss
                    $delete_corporate = $conn->prepare("
                        DELETE FROM expense 
                        WHERE project_id = 1 AND loss_id = ?
                    ");
                    $delete_corporate->bind_param("i", $edit_id);
                    $delete_corporate->execute();
                    $delete_corporate->close();
                }
                
                // If bill_to_client changed from Yes to No, delete the corporate record
                if ($bill_to_client === 'No') {
                    // First, check if there's a corporate record (that's not a loss record)
                    $check_stmt = $conn->prepare("
                        SELECT record_id FROM expense 
                        WHERE project_id = 1 AND 
                            category = ? AND 
                            subcategory = ? AND 
                            purchase_date = ? AND 
                            record_description = ? AND
                            (loss_id IS NULL OR loss_id != ?)
                    ");
                    $check_stmt->bind_param(
                        "ssssi",
                        $category, $subcategory, $purchase_date, $record_description, $edit_id
                    );
                    $check_stmt->execute();
                    $check_result = $check_stmt->get_result();
                    
                    if ($check_result->num_rows > 0) {
                        // Delete the corporate record
                        $delete_stmt = $conn->prepare("
                            DELETE FROM expense 
                            WHERE project_id = 1 AND 
                                category = ? AND 
                                subcategory = ? AND 
                                purchase_date = ? AND 
                                record_description = ? AND
                                (loss_id IS NULL OR loss_id != ?)
                        ");
                        $delete_stmt->bind_param(
                            "ssssi",
                            $category, $subcategory, $purchase_date, $record_description, $edit_id
                        );
                        $delete_stmt->execute();
                        $delete_stmt->close();
                    }
                    $check_stmt->close();
                }
            } else {
                // ADD NEW RECORD to the selected project
                $stmt = $conn->prepare("INSERT INTO expense (
                    project_id, user_id, category, subcategory, purchase_date, budget, expense, 
                    payee, record_description, remarks, variance, tax, rental_rate, invoice_no, 
                    bill_to_client, is_rental, is_company_loss, created_by, creation_date
                ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())");
                $stmt->bind_param(
                    "iisssddsssddsssssi",
                    $project_id, $user_id, $category, $subcategory, $purchase_date, $budget, $expense,
                    $payee, $record_description, $remarks, $variance, $tax, $rental_rate, $invoice_no,
                    $bill_to_client, $is_rental, $is_company_loss, $user_id
                );
                $stmt->execute();
                $record_id = $conn->insert_id; // Get the ID of the newly inserted record
                $stmt->close();
                
                // If this is a company loss, update the loss_id to point to itself
                if ($is_company_loss === 'Yes') {
                    $update_stmt = $conn->prepare("UPDATE expense SET loss_id = ? WHERE record_id = ?");
                    $update_stmt->bind_param("ii", $record_id, $record_id);
                    $update_stmt->execute();
                    $update_stmt->close();
                }
                
                // If is_company_loss is 'Yes', add a record to Corporate (project_id=1)
                if ($is_company_loss === 'Yes') {
                    $corporate_project_id = 1; // The corporate project ID
                    
                    $stmt = $conn->prepare("INSERT INTO expense (
                        project_id, user_id, category, subcategory, purchase_date, budget, expense, 
                        payee, record_description, remarks, variance, tax, rental_rate, invoice_no, 
                        bill_to_client, is_rental, is_company_loss, loss_id, created_by, creation_date
                    ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())");
                    $stmt->bind_param(
                        "iisssddsssddsssssii",
                        $corporate_project_id, $user_id, $category, $subcategory, $purchase_date, $budget, $expense,
                        $payee, $record_description, $remarks, $variance, $tax, $rental_rate, $invoice_no,
                        $bill_to_client, $is_rental, $is_company_loss, $record_id, $user_id
                    );
                    $stmt->execute();
                    $stmt->close();
                }
                
                // If category is ASSET, also add a record to the assets table
                if ($category === 'ASSET') {
                    $asset_stmt = $conn->prepare("INSERT INTO assets (
                        record_id, asset_description, asset_img, created_by, creation_date
                    ) VALUES (?, ?, NULL, ?, NOW())");
                    $asset_stmt->bind_param(
                        "isi",
                        $record_id,
                        $record_description, // Use record_description as asset_name
                        $user_id
                    );
                    $asset_stmt->execute();
                    $asset_stmt->close();
                }
            }
            
            // If everything is successful, commit the transaction
            $conn->commit();
            error_log("Transaction committed successfully");
            
        } catch (Exception $e) {
            // An error occurred, rollback the transaction
            $conn->rollback();
            error_log("Transaction failed: " . $e->getMessage());
        }
        
        // Redirect to avoid form resubmission
        header("Location: ms_records.php?projectId=$project_id");
        exit();
    } else {
        error_log("No POST request - Method is: " . $_SERVER['REQUEST_METHOD']);
    }

    // For existing delete record handling, ensure it works with expense table as well
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_record'])) {
        $id = $_POST['record_id'];
        
        // Start a transaction
        $conn->begin_transaction();
        
        try {
            // First, check if the record has a matching loss_id somewhere
            $check_stmt = $conn->prepare("
                SELECT record_id FROM expense 
                WHERE loss_id = ? AND record_id != ?
            ");
            $check_stmt->bind_param("ii", $id, $id);
            $check_stmt->execute();
            $check_result = $check_stmt->get_result();
            
            // If there are records with matching loss_id, delete them too
            while ($row = $check_result->fetch_assoc()) {
                $loss_record_id = $row['record_id'];
                $delete_loss_stmt = $conn->prepare("DELETE FROM expense WHERE record_id = ?");
                $delete_loss_stmt->bind_param("i", $loss_record_id);
                $delete_loss_stmt->execute();
                $delete_loss_stmt->close();
            }
            $check_stmt->close();
            
            // Now delete the main record
            $stmt = $conn->prepare("DELETE FROM expense WHERE record_id = ?");
            $stmt->bind_param("i", $id);
            $stmt->execute();
            $stmt->close();
            
            $conn->commit();
        } catch (Exception $e) {
            $conn->rollback();
            error_log("Delete transaction failed: " . $e->getMessage());
        }
        
        header("Location: ms_records.php?projectId=" . $project_id);
        exit();
    }

    // fetch records from expense table with category and subcategory information
    $expense_records = [];
    $total_budget = 0;
    $total_expense = 0;
    $total_variance = 0;
    $total_tax = 0;
    
    if ($project_id > 0) {
        $expense_stmt = $conn->prepare("
            SELECT e.*, 
                c.category_name as category,
                s.subcategory_name as subcategory,
                CONCAT(emp.first_name, ' ', emp.last_name) as creator_name,
                CONCAT(emp2.first_name, ' ', emp2.last_name) as editor_name
            FROM expense e
            LEFT JOIN categories c ON e.category = c.category_name
            LEFT JOIN subcategories s ON e.subcategory = s.subcategory_name
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
            // Calculate totals for analytics summary
            $total_budget += floatval($row['budget'] ?? 0);
            $total_expense += floatval($row['expense'] ?? 0) + floatval($row['rental_rate'] ?? 0);
            $total_variance += floatval($row['variance'] ?? 0);
            $total_tax += floatval($row['tax'] ?? 0);
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
            <!-- Project Summary -->
            <?php if ($project): ?>
                <div class="project-summary position-relative">
                    <div class="project-options">
                        <button class="ellipsis-btn" onclick="toggleDropdown(this)">
                            <img src="icons/ellipsis-vertical.svg" alt="Options">
                        </button>
                        <div class="dropdown-menu" style="display:none;">
                            <button class="dropdown-edit" onclick="openEditModal()">Edit</button>
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
                    <button class="toggle-btn active" id="view-records-btn">RECORD</button>
                    <button class="toggle-btn" id="view-analytics-btn">ANALYTICS</button>
                </div>
            </div>

            <div class="records-table-container" id="records-view" style="display: block;">
                <!-- RECORDS VIEW -->
                <!-- Expense Records Table -->
                <div class="table-responsive">
                    <table class="table table-hover">
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
                                                data-invoice_no="<?= htmlspecialchars($row['invoice_no']) ?>"
                                                data-bill_to_client="<?= $row['bill_to_client'] ?>"
                                                data-is_rental="<?= $row['is_rental'] ?>"
                                                data-is_company_loss="<?= $row['is_company_loss'] ?>">
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
                                                data-invoice_no="<?= htmlspecialchars($row['invoice_no']) ?>"
                                                data-bill_to_client="<?= $row['bill_to_client'] ?>"
                                                data-is_rental="<?= $row['is_rental'] ?>"
                                                data-is_company_loss="<?= $row['is_company_loss'] ?>">
                                                <img src="icons/pencil-white.svg" width="16" alt="Edit">
                                            </button>
                                            <button type="button" class="btn btn-sm btn-danger delete-btn" onclick="deleteExpense(<?= $row['record_id'] ?>)">
                                                <img src="icons/trash.svg" alt="Delete" width="16">
                                            </button>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <tr><td colspan="11" class="text-center">No records available for this project.</td></tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>

            <!--ANALYTICS VIEW -->
            <div class="analytics-view container py-4" id="analytics-view" style="display: none;">
                <!-- Time period selection -->
                <div class="time-selector mb-4">
                    <div class="d-flex justify-content-end">
                        <div class="btn-group" role="group">
                            <button type="button" class="btn btn-sm btn-outline-primary" data-period="daily">Daily</button>
                            <button type="button" class="btn btn-sm btn-outline-primary active" data-period="weekly">Weekly</button>
                            <button type="button" class="btn btn-sm btn-outline-primary" data-period="monthly">Monthly</button>
                            <button type="button" class="btn btn-sm btn-outline-primary" data-period="quarterly">Quarterly</button>
                            <button type="button" class="btn btn-sm btn-outline-primary" data-period="biannual">6 Months</button>
                            <button type="button" class="btn btn-sm btn-outline-primary" data-period="annual">12 Months</button>
                        </div>
                    </div>
                </div>

                <div class="row">
                    <!-- Left: Summary (25%) -->
                    <div class="col-md-3">
                        <div class="card shadow rounded-4 p-3 mb-3">
                            <h6>Total Budget</h6>
                            <p class="fs-5 fw-bold text-black" id="total-budget">₱<?= number_format($total_budget, 2) ?></p>
                        </div>
                        <div class="card shadow rounded-4 p-3 mb-3">
                            <h6>Total Expense</h6>
                            <p class="fs-5 fw-bold text-black" id="total-expense">₱<?= number_format($total_expense, 2) ?></p>
                        </div>
                        <div class="card shadow rounded-4 p-3 mb-3">
                            <h6>Total Variance</h6>
                            <p class="fs-5 fw-bold text-black" id="total-variance">₱<?= number_format($total_variance, 2) ?></p>
                        </div>
                        <div class="card shadow rounded-4 p-3 mb-3">
                            <h6>Total Tax</h6>
                            <p class="fs-5 fw-bold text-black" id="total-tax">₱<?= number_format($total_tax, 2) ?></p>
                        </div>
                        
                        <!-- Breakdown selection -->
                        <div class="breakdown-selector mt-4">
                            <h6 class="mb-2">Breakdown By:</h6>
                            <div class="btn-group d-flex" role="group">
                                <button type="button" class="btn btn-sm btn-outline-secondary active" data-type="category">Category</button>
                                <button type="button" class="btn btn-sm btn-outline-secondary" data-type="subcategory">Subcategory</button>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Right: Charts (75%) -->
                    <div class="col-md-9">
                        <div class="row g-4">
                            <!-- Budget vs Expense Over Time -->
                            <div class="col-md-12">
                                <div class="card shadow rounded-4 p-3 mb-3">
                                    <h6 class="text-center">Budget vs Expense Over Time</h6>
                                    <div style="height: 300px;">
                                        <canvas id="timeChart"></canvas>
                                    </div>
                                </div>
                            </div>
                            
                            <!-- Category Breakdown -->
                            <div class="col-md-12">
                                <div class="card shadow rounded-4 p-3">
                                    <h6 class="text-center">Expense by Category</h6>
                                    <div style="height: 300px;">
                                        <canvas id="categoryChart"></canvas>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <?php include('edit_project_modal.php'); ?>

    <!-- ADD/EDIT RECORD MODAL -->
    <div class="modal fade" id="expenseModal" tabindex="-1" aria-labelledby="expenseModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <form id="expenseForm" method="post" action="">
                    <div class="modal-header">
                        <h5 class="modal-title" id="expenseModalLabel">Add Expense</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
                        <input type="hidden" id="record_id" name="record_id">
                        <input type="hidden" id="form_mode" name="form_mode" value="add">
                        <input type="hidden" id="edit_id" name="edit_id" value="0">
                        <input type="hidden" id="has_budget_field" name="has_budget" value="off">
                        <input type="hidden" id="bill_to_client" name="bill_to_client" value="No">
                        <input type="hidden" id="is_rental_field" name="is_rental" value="No">
                        
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
                                    <select id="subcategory" name="subcategory" class="form-select" disabled>
                                        <option value="">Select Subcategory</option>
                                    </select>
                                </div>
                                <div class="col-md-6">
                                    <label for="purchase_date" class="form-label">Date</label>
                                    <input type="date" class="form-control" id="purchase_date" name="purchase_date" required value="<?php echo date('Y-m-d'); ?>">
                                </div>
                                <div class="col-md-6">
                                    <label for="payee" class="form-label">Payee</label>
                                    <input type="text" class="form-control focus-clear" id="payee" name="payee" placeholder="Enter Payee" required>
                                </div>
                                <div class="col-md-12">
                                    <label for="record_description" class="form-label">Description</label>
                                    <input type="text" class="form-control focus-clear" id="record_description" name="record_description" placeholder="Enter Description" required>
                                </div>
                            </div>
                        </div>
                        
                        <div class="form-section">
                            <div class="form-section-title">Budget and Expense</div>
                            <div class="row g-3">
                                <div class="col-md-6">
                                    <div class="d-flex align-items-center mb-2">
                                        <input type="checkbox" id="is_rental" class="form-check-input me-2">
                                        <label for="is_rental" class="form-check-label">Rental</label>
                                    </div>
                                    <div id="expense_input">
                                        <label for="expense" class="form-label">Expense</label>
                                        <input type="number" step="0.01" class="form-control calculation focus-clear" id="expense" name="expense" placeholder="0.00" required>
                                    </div>
                                    <div id="rental_input" style="display: none;">
                                        <label for="rental_rate" class="form-label">Rental Rate</label>
                                        <input type="number" step="0.01" class="form-control calculation focus-clear" id="rental_rate" name="rental_rate" placeholder="0.00">
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="d-flex align-items-center mb-2">
                                        <input type="checkbox" id="has_budget" class="form-check-input me-2">
                                        <label for="has_budget" class="form-check-label">Budget</label>
                                    </div>
                                    <label for="budget" class="form-label">Budget Amount</label>
                                    <input type="number" step="0.01" class="form-control calculation focus-clear" id="budget" name="budget" placeholder="0.00" disabled>
                                </div>
                                <div class="col-md-6 bill-to-client-wrapper" <?php echo ($project_id == 1) ? 'style="display:none;"' : ''; ?>>
                                    <div class="d-flex align-items-center mb-2">
                                        <input type="checkbox" id="bill_to_client_checkbox" class="form-check-input me-2">
                                        <label for="bill_to_client_checkbox" class="form-check-label">Bill to Client</label>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <label for="variance" class="form-label">Variance</label>
                                    <input type="number" step="0.01" class="form-control" id="variance" name="variance" readonly placeholder="0.00">
                                </div>
                            </div>
                        </div>
                        
                        <div class="form-section">
                            <div class="form-section-title">Additional Information</div>
                            <div class="row g-3">
                                <div class="col-md-6">
                                    <div class="d-flex align-items-center mb-2">
                                        <input type="checkbox" id="has_tax" class="form-check-input me-2">
                                        <label for="has_tax" class="form-check-label">Tax included</label>
                                    </div>
                                    <div id="tax_input">
                                        <div class="input-group">
                                            <label for="tax" class="form-label w-100">Tax</label>
                                            <input type="number" step="0.01" class="form-control calculation focus-clear" id="tax" name="tax" placeholder="0.00" disabled>
                                            <button type="button" id="tax_edit_btn" class="btn btn-outline-secondary">
                                                <img src="icons/pencil-black.svg" alt="Edit" id="tax_edit_icon" width="16">
                                            </button>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="d-flex align-items-center mb-2">
                                        <input type="checkbox" id="has_invoice" class="form-check-input me-2">
                                        <label for="has_invoice" class="form-check-label">Has Invoice?</label>
                                    </div>
                                    <div id="invoice_input">
                                        <label for="invoice_no" class="form-label">Invoice No.</label>
                                        <input type="text" class="form-control focus-clear" id="invoice_no" name="invoice_no" placeholder="Enter Invoice Number" disabled>
                                    </div>
                                </div>
                                <div class="col-md-12">
                                    <label for="remarks" class="form-label">Remarks</label>
                                    <textarea class="form-control focus-clear" id="remarks" name="remarks" rows="3" placeholder="Enter Remarks"></textarea>
                                </div>
                            </div>
                        </div>
                        
                        <div id="expenseSummary" class="bg-light p-3 rounded mt-3">
                            <h6>Expense Summary</h6>
                            <div class="calculation-row" id="summary_budget_row" style="display: none;">
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
                            <div class="calculation-row" id="summary_bill_row" style="display: none;">
                                <span>Bill to Client:</span>
                                <span id="summary_bill">No</span>
                            </div>
                            <div class="calculation-row" id="summary_loss_row" style="display: none;">
                                <span>Company Loss:</span>
                                <span id="summary_loss">No</span>
                            </div>
                            <div class="calculation-row total" id="summary_variance_row" style="display: none;">
                                <span>Variance:</span>
                                <span id="summary_variance">₱0.00</span>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">CANCEL</button>
                        <button type="submit" name="save_expense" value="1" class="btn btn-primary">SAVE</button>
                    </div>
                    <input type="hidden" name="save_expense" value="1">
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
                            <h6>Transaction Details</h6>
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
                                    <th class="viewRecord" style="width: 5%; text-align: left;">Rental</th>
                                    <td id="view_is_rental"></td>
                                </tr>
                                <tr>
                                    <th class="viewRecord" style="width: 5%; text-align: left;">Rental Rate</th>
                                    <td id="view_rental_rate"></td>
                                </tr>
                                <tr>
                                    <th class="viewRecord" style="width: 5%; text-align: left;">Variance</th>
                                    <td id="view_variance"></td>
                                </tr>
                                <tr>
                                    <th class="viewRecord" style="width: 5%; text-align: left;">Tax</th>
                                    <td id="view_tax"></td>
                                </tr>
                                <tr>
                                    <th class="viewRecord" style="width: 5%; text-align: left;">Bill to Client</th>
                                    <td id="view_bill_to_client"></td>
                                </tr>
                                <tr>
                                    <th class="viewRecord" style="width: 5%; text-align: left;">Company Loss</th>
                                    <td id="view_is_company_loss"></td>
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
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.2.3/dist/js/bootstrap.bundle.min.js"></script>

    <script src="js/sidebar.js"></script>
    <script src="js/header.js"></script>

    <script>
        document.addEventListener('DOMContentLoaded', function () {
            const recordsBtn = document.getElementById('view-records-btn');
            const analyticsBtn = document.getElementById('view-analytics-btn');
            const recordsView = document.getElementById('records-table-container');
            const analyticsView = document.getElementById('analytics-view');

            if (recordsBtn && analyticsBtn && recordsView && analyticsView) {
                recordsBtn.addEventListener('click', () => {
                    recordsView.style.display = 'block';
                    analyticsView.style.display = 'none';
                    recordsBtn.classList.add('active');
                    analyticsBtn.classList.remove('active');
                });

                analyticsBtn.addEventListener('click', () => {
                    recordsView.style.display = 'none';
                    analyticsView.style.display = 'block';
                    analyticsBtn.classList.add('active');
                    recordsBtn.classList.remove('active');
                });
            } else {
                console.warn('Toggle elements not found in DOM');
            }
        });
    </script>

    <script> //viewExpenseModal
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
                
                // Add these new fields
                $('#view_bill_to_client').text($(this).data('bill_to_client') || 'No');
                $('#view_is_rental').text($(this).data('is_rental') || 'No');
                $('#view_is_company_loss').text($(this).data('is_company_loss') || 'No');

                // Hide bill_to_client row if not Yes
                if ($(this).data('bill_to_client') === 'Yes') {
                    $('#view_bill_to_client').closest('tr').show();
                    $('#view_bill_to_client').closest('tr').addClass('table-success');
                } else {
                    $('#view_bill_to_client').closest('tr').hide();
                }
                
                // Hide company_loss row if not Yes
                if ($(this).data('is_company_loss') === 'Yes') {
                    $('#view_is_company_loss').closest('tr').show();
                    $('#view_is_company_loss').closest('tr').addClass('table-danger');
                } else {
                    $('#view_is_company_loss').closest('tr').hide();
                }
                
                // Show/highlight rental row based on is_rental value
                if ($(this).data('is_rental') === 'Yes') {
                    $('#view_rental_rate').closest('tr').addClass('table-primary');
                } else {
                    $('#view_rental_rate').closest('tr').removeClass('table-primary');
                }

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
        // Toggle dropdown menu
        function toggleDropdown(button) {
            const menu = button.nextElementSibling;
            if (menu.style.display === 'block') {
                menu.style.display = 'none';
            } else {
                // Close any other dropdowns open on the page first
                document.querySelectorAll('.dropdown-menu').forEach(m => m.style.display = 'none');
                menu.style.display = 'block';
            }
        }

        // Close dropdown if clicked outside
        document.addEventListener('click', function(event) {
            if (!event.target.closest('.project-options')) {
                document.querySelectorAll('.dropdown-menu').forEach(menu => menu.style.display = 'none');
            }
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

        // Delete project with confirmation & AJAX
        function deleteProject(projectId) {
            if (!confirm('Are you sure you want to delete this project?')) {
                return;
            }

            const xhr = new XMLHttpRequest();
            xhr.open('POST', window.location.href, true);
            xhr.setRequestHeader('Content-Type', 'application/x-www-form-urlencoded');

            xhr.onload = function() {
                if (xhr.status === 200) {
                    if (xhr.responseText.trim() === 'success') {
                        alert('Project deleted successfully.');
                        window.location.href = 'ms_projects.php'; // Redirect to projects list page
                    } else {
                        alert('Failed to delete project: ' + xhr.responseText);
                    }
                } else {
                    alert('Request failed. Returned status of ' + xhr.status);
                }
            };

            xhr.send('delete_project_id=' + encodeURIComponent(projectId));
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
                    invoice_no: this.getAttribute('data-invoice_no') || '',
                    is_rental: this.getAttribute('data-is_rental') || 'No',
                    bill_to_client: this.getAttribute('data-bill_to_client') || 'No',
                    is_company_loss: this.getAttribute('data-is_company_loss') || 'No'
                };
                // Call your existing modal open function in edit mode
                openExpenseModal('edit', recordData);
            });
        });

        document.addEventListener('DOMContentLoaded', function() {

            // Add at the beginning of your DOMContentLoaded handler
            window.onerror = function(message, source, lineno, colno, error) {
                console.error("JavaScript error:", message, "at", source, ":", lineno);
                return false;
            };

            // Handle focus-clear class for placeholders
            document.querySelectorAll('.focus-clear').forEach(input => {
                const placeholder = input.placeholder;
                
                input.addEventListener('focus', function() {
                    this.placeholder = '';
                });
                
                input.addEventListener('blur', function() {
                    if (this.value === '') {
                        this.placeholder = placeholder;
                    }
                });
            });
            
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
                const expenseInputContainer = document.getElementById('expense_input');
                const rentalInputContainer = document.getElementById('rental_input');
                const summaryExpenseRow = document.getElementById('summary_expense_row');
                const summaryRentalRow = document.getElementById('summary_rental_row');
                const rentalRateInput = document.getElementById('rental_rate');
                const expenseInput = document.getElementById('expense');
                const isRentalField = document.getElementById('is_rental_field');
                
                if (this.checked) {
                    expenseInputContainer.style.display = 'none';
                    rentalInputContainer.style.display = 'block';
                    expenseInput.value = '';
                    expenseInput.required = false;
                    expenseInput.placeholder = '0.00';
                    rentalRateInput.required = true;
                    summaryExpenseRow.style.display = 'none';
                    summaryRentalRow.style.display = 'flex';
                    isRentalField.value = 'Yes';
                } else {
                    expenseInputContainer.style.display = 'block';
                    rentalInputContainer.style.display = 'none';
                    rentalRateInput.value = '';
                    rentalRateInput.required = false;
                    rentalRateInput.placeholder = '0.00';
                    expenseInput.required = true;
                    summaryExpenseRow.style.display = 'flex';
                    summaryRentalRow.style.display = 'none';
                    isRentalField.value = 'No';
                }
                updateCalculations();
            });

            // Add a function to update company loss status based on scenarios
            function updateCompanyLossStatus() {
                const hasBudget = document.getElementById('has_budget').checked;
                const isBillToClient = document.getElementById('bill_to_client_checkbox').checked;
                const expenseAmount = document.getElementById('is_rental').checked 
                    ? parseFloat(document.getElementById('rental_rate').value || 0) 
                    : parseFloat(document.getElementById('expense').value || 0);
                const budgetAmount = parseFloat(document.getElementById('budget').value || 0);
                
                // For logging/debugging
                console.log("Updating company loss status:");
                console.log("Has budget:", hasBudget);
                console.log("Bill to client:", isBillToClient);
                console.log("Expense amount:", expenseAmount);
                console.log("Budget amount:", budgetAmount);
                
                let isCompanyLoss = false;
                
                // Scenario 1: Budget checked, expense <= budget
                if (hasBudget && expenseAmount <= budgetAmount) {
                    isCompanyLoss = false;
                }
                // Scenario 2: Budget NOT checked, bill_to_client NOT checked
                else if (!hasBudget && !isBillToClient) {
                    isCompanyLoss = true;
                }
                // Scenario 3: Budget checked, expense > budget, bill_to_client NOT checked
                else if (hasBudget && expenseAmount > budgetAmount && !isBillToClient) {
                    isCompanyLoss = true;
                }
                // Scenario 4: Budget checked, expense > budget, bill_to_client checked
                else if (hasBudget && expenseAmount > budgetAmount && isBillToClient) {
                    isCompanyLoss = false;
                }
                
                console.log("Is company loss:", isCompanyLoss);
                
                // Add a hidden field for is_company_loss if it doesn't exist
                let isCompanyLossField = document.getElementById('is_company_loss');
                if (!isCompanyLossField) {
                    isCompanyLossField = document.createElement('input');
                    isCompanyLossField.type = 'hidden';
                    isCompanyLossField.id = 'is_company_loss';
                    isCompanyLossField.name = 'is_company_loss';
                    document.getElementById('expenseForm').appendChild(isCompanyLossField);
                }
                
                // Update the value
                isCompanyLossField.value = isCompanyLoss ? 'Yes' : 'No';
                
                // Add a row to the expense summary
                const summaryLossRow = document.getElementById('summary_loss_row');
                if (summaryLossRow) {
                    summaryLossRow.style.display = isCompanyLoss ? 'flex' : 'none';
                    document.getElementById('summary_loss').textContent = isCompanyLoss ? 'Yes' : 'No';
                }
            }

            // Add event listeners to expense, rental, and budget inputs to monitor for changes
            document.querySelectorAll('.calculation').forEach(input => {
                input.addEventListener('input', function() {
                    if (this.id === 'expense' || this.id === 'rental_rate' || this.id === 'budget') {
                        calculateTax();
                        checkExpenseBudgetDifference(); // Check expense vs budget whenever these values change
                        updateCompanyLossStatus();
                    }
                    updateCalculations();
                });
            });

            // Update the checkbox event handlers
            document.getElementById('has_budget').addEventListener('change', function() {
                const budgetInput = document.getElementById('budget');
                const summaryBudgetRow = document.getElementById('summary_budget_row');
                const summaryVarianceRow = document.getElementById('summary_variance_row');
                const billToClientCheckbox = document.getElementById('bill_to_client_checkbox');
                const hasBudgetField = document.getElementById('has_budget_field');
                
                if (this.checked) {
                    // Budget is checked
                    budgetInput.disabled = false;
                    budgetInput.required = true;
                    summaryBudgetRow.style.display = 'flex';
                    summaryVarianceRow.style.display = 'flex';
                    hasBudgetField.value = 'on';
                    
                    // When budget is first checked, disable Bill to Client 
                    // (we'll re-enable it if expense > budget later)
                    billToClientCheckbox.disabled = true;
                    billToClientCheckbox.checked = false;
                    document.getElementById('bill_to_client').value = 'No';
                    document.getElementById('summary_bill').textContent = 'No';
                    document.getElementById('summary_bill_row').style.display = 'none';
                    
                    // Check existing values to see if expense > budget already
                    checkExpenseBudgetDifference();
                } else {
                    // Budget is unchecked
                    budgetInput.disabled = true;
                    budgetInput.required = false;
                    budgetInput.value = '';
                    budgetInput.placeholder = '0.00';
                    summaryBudgetRow.style.display = 'none';
                    summaryVarianceRow.style.display = 'none';
                    hasBudgetField.value = 'off';
                    
                    // Enable Bill to Client when Budget is unchecked
                    billToClientCheckbox.disabled = false;
                }
                
                updateCalculations();
                updateCompanyLossStatus();
            });

            // Function to check if expense > budget and update the UI accordingly
            function checkExpenseBudgetDifference() {
                const hasBudget = document.getElementById('has_budget').checked;
                if (!hasBudget) return; // Only proceed if budget is enabled
                
                const expenseAmount = document.getElementById('is_rental').checked 
                    ? parseFloat(document.getElementById('rental_rate').value || 0) 
                    : parseFloat(document.getElementById('expense').value || 0);
                const budgetAmount = parseFloat(document.getElementById('budget').value || 0);
                const billToClientCheckbox = document.getElementById('bill_to_client_checkbox');
                
                if (expenseAmount > budgetAmount) {
                    // Expense exceeds budget - enable the Bill to Client checkbox
                    billToClientCheckbox.disabled = false;
                } else {
                    // Expense is within budget - disable the Bill to Client checkbox
                    billToClientCheckbox.disabled = true;
                    billToClientCheckbox.checked = false;
                    document.getElementById('bill_to_client').value = 'No';
                    document.getElementById('summary_bill').textContent = 'No';
                    document.getElementById('summary_bill_row').style.display = 'none';
                }
            }

            
            // Bill to Client checkbox
            document.getElementById('bill_to_client_checkbox').addEventListener('change', function() {
                const summaryBillRow = document.getElementById('summary_bill_row');
                const billToClientField = document.getElementById('bill_to_client');
                
                if (this.checked) {
                    billToClientField.value = 'Yes';
                    document.getElementById('summary_bill').textContent = 'Yes';
                    summaryBillRow.style.display = 'flex';
                } else {
                    billToClientField.value = 'No';
                    document.getElementById('summary_bill').textContent = 'No';
                    summaryBillRow.style.display = 'none';
                }
                
                updateCompanyLossStatus();
            });
            
            // Tax checkbox
            document.getElementById('has_tax').addEventListener('change', function() {
                const taxInput = document.getElementById('tax');
                const summaryTaxRow = document.getElementById('summary_tax_row');
                const taxEditBtn = document.getElementById('tax_edit_btn');
                
                if (this.checked) {
                    taxInput.disabled = true;
                    taxEditBtn.style.display = 'block';
                    summaryTaxRow.style.display = 'flex';
                    
                    // Calculate tax as 12% of expense
                    calculateTax();
                } else {
                    taxInput.disabled = true;
                    taxInput.value = '';
                    taxInput.placeholder = '0.00';
                    taxEditBtn.style.display = 'none';
                    document.getElementById('tax_edit_icon').src = 'icons/pencil-black.svg';
                    summaryTaxRow.style.display = 'none';
                }
                updateCalculations();
            });
            
            // Tax edit button
            document.getElementById('tax_edit_btn').addEventListener('click', function() {
                const taxInput = document.getElementById('tax');
                const taxEditIcon = document.getElementById('tax_edit_icon');
                
                if (taxInput.disabled) {
                    // Enable editing
                    taxInput.disabled = false;
                    taxEditIcon.src = 'icons/pencil-white.svg';
                    this.classList.remove('btn-outline-secondary');
                    this.classList.add('btn-primary');
                } else {
                    // Disable editing
                    taxInput.disabled = true;
                    taxEditIcon.src = 'icons/pencil-black.svg';
                    this.classList.remove('btn-primary');
                    this.classList.add('btn-outline-secondary');
                    
                    // Always recalculate tax as 12% of expense when toggling back to disabled
                    calculateTax();
                }
            });
            
            // Invoice checkbox
            document.getElementById('has_invoice').addEventListener('change', function() {
                const invoiceInput = document.getElementById('invoice_no');
                
                if (this.checked) {
                    invoiceInput.disabled = false;
                    invoiceInput.required = true;
                } else {
                    invoiceInput.disabled = true;
                    invoiceInput.required = false;
                    invoiceInput.value = '';
                    invoiceInput.placeholder = 'Enter Invoice Number';
                }
            });
            
            // Calculate tax as 12% of expense
            function calculateTax() {
                if (!document.getElementById('has_tax').checked) return;
                
                const isRental = document.getElementById('is_rental').checked;
                const expense = isRental ? 
                    (parseFloat(document.getElementById('rental_rate').value) || 0) : 
                    (parseFloat(document.getElementById('expense').value) || 0);
                
                const taxAmount = expense * 0.12;
                document.getElementById('tax').value = taxAmount.toFixed(2);
                document.getElementById('summary_tax').textContent = '₱' + taxAmount.toFixed(2);
            }
            
            // Input calculation event listeners
            const calculationInputs = document.querySelectorAll('.calculation');
            calculationInputs.forEach(input => {
                input.addEventListener('input', function() {
                    if (this.id === 'expense' || this.id === 'rental_rate') {
                        calculateTax();
                    }
                    updateCalculations();
                });
            });
            
            // Function to update all calculations
            function updateCalculations() {
                const hasBudget = document.getElementById('has_budget').checked;
                const budget = hasBudget ? (parseFloat(document.getElementById('budget').value) || 0) : 0;
                
                const isRental = document.getElementById('is_rental').checked;
                const expense = isRental ? 
                    (parseFloat(document.getElementById('rental_rate').value) || 0) : 
                    (parseFloat(document.getElementById('expense').value) || 0);
                
                const hasTax = document.getElementById('has_tax').checked;
                const tax = hasTax ? (parseFloat(document.getElementById('tax').value) || 0) : 0;
                
                // Calculate variance only if budget is enabled
                if (hasBudget) {
                    const variance = budget - expense;
                    document.getElementById('variance').value = variance.toFixed(2);
                    document.getElementById('summary_variance').textContent = '₱' + variance.toFixed(2);
                } else {
                    document.getElementById('variance').value = '';
                    document.getElementById('variance').placeholder = '0.00';
                }
                
                // Update summary display
                if (hasBudget) {
                    document.getElementById('summary_budget').textContent = '₱' + budget.toFixed(2);
                }
                
                if (isRental) {
                    document.getElementById('summary_rental').textContent = '₱' + expense.toFixed(2);
                } else {
                    document.getElementById('summary_expense').textContent = '₱' + expense.toFixed(2);
                }
                
                if (hasTax) {
                    document.getElementById('summary_tax').textContent = '₱' + tax.toFixed(2);
                }
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
                document.getElementById('tax_input').style.display = 'block';
                document.getElementById('invoice_input').style.display = 'block';
                document.getElementById('tax').disabled = true;
                document.getElementById('invoice_no').disabled = true;
                document.getElementById('budget').disabled = true;
                document.getElementById('bill_to_client_checkbox').disabled = false;
                document.getElementById('tax_edit_btn').style.display = 'none';
                document.getElementById('tax_edit_icon').src = 'icons/pencil-black.svg';
                document.getElementById('tax_edit_btn').classList.remove('btn-primary');
                document.getElementById('tax_edit_btn').classList.add('btn-outline-secondary');
                
                // Reset summary display
                document.getElementById('summary_expense_row').style.display = 'flex';
                document.getElementById('summary_rental_row').style.display = 'none';
                document.getElementById('summary_tax_row').style.display = 'none';
                document.getElementById('summary_budget_row').style.display = 'none';
                document.getElementById('summary_variance_row').style.display = 'none';
                document.getElementById('summary_bill_row').style.display = 'none';
                
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
                    document.getElementById('remarks').value = recordData.remarks;
                    
                    // Check if is_rental is "Yes"
                    if (recordData.is_rental === 'Yes' || 
                        (recordData.rental_rate && parseFloat(recordData.rental_rate) > 0)) {
                        document.getElementById('is_rental').checked = true;
                        document.getElementById('is_rental_field').value = 'Yes';
                        document.getElementById('rental_rate').value = recordData.rental_rate;
                        document.getElementById('expense_input').style.display = 'none';
                        document.getElementById('rental_input').style.display = 'block';
                        document.getElementById('summary_expense_row').style.display = 'none';
                        document.getElementById('summary_rental_row').style.display = 'flex';
                        document.getElementById('rental_rate').required = true;
                        document.getElementById('expense').required = false;
                    } else {
                        document.getElementById('expense').value = recordData.expense;
                        document.getElementById('is_rental_field').value = 'No';
                    }
                    
                    // Check if budget exists and is not zero
                    if (recordData.budget && parseFloat(recordData.budget) > 0) {
                        document.getElementById('has_budget').checked = true;
                        document.getElementById('has_budget_field').value = 'on';
                        document.getElementById('budget').disabled = false;
                        document.getElementById('budget').required = true;
                        document.getElementById('budget').value = recordData.budget;
                        document.getElementById('summary_budget_row').style.display = 'flex';
                        document.getElementById('summary_variance_row').style.display = 'flex';
                        
                        // Only disable Bill to Client if budget >= expense/rental_rate
                        const expenseAmount = parseFloat(recordData.is_rental === 'Yes' ? recordData.rental_rate : recordData.expense) || 0;
                        const budgetAmount = parseFloat(recordData.budget) || 0;
                        
                        if (expenseAmount <= budgetAmount) {
                            document.getElementById('bill_to_client_checkbox').disabled = true;
                        } else {
                            document.getElementById('bill_to_client_checkbox').disabled = false;
                        }
                    }
                    
                    // Check if bill_to_client is "Yes"
                    if (recordData.bill_to_client === 'Yes') {
                        document.getElementById('bill_to_client_checkbox').checked = true;
                        document.getElementById('bill_to_client').value = 'Yes';
                        document.getElementById('summary_bill').textContent = 'Yes';
                        document.getElementById('summary_bill_row').style.display = 'flex';
                    }
                    
                    // Check if tax exists and is not zero
                    if (recordData.tax && parseFloat(recordData.tax) > 0) {
                        document.getElementById('has_tax').checked = true;
                        document.getElementById('tax').value = recordData.tax;
                        document.getElementById('tax_edit_btn').style.display = 'block';
                        document.getElementById('summary_tax_row').style.display = 'flex';
                    }
                    
                    // Check if invoice exists
                    if (recordData.invoice_no && recordData.invoice_no.trim() !== '') {
                        document.getElementById('has_invoice').checked = true;
                        document.getElementById('invoice_no').disabled = false;
                        document.getElementById('invoice_no').required = true;
                        document.getElementById('invoice_no').value = recordData.invoice_no;
                    }
                    
                    // Update calculations
                    updateCalculations();
                    
                    // Update company loss status if needed
                    if (typeof updateCompanyLossStatus === 'function') {
                        updateCompanyLossStatus();
                    }
                }
                
                // Show the modal
                const bsModal = new bootstrap.Modal(modal);
                bsModal.show();
            };
            
            // Handle form submission
            document.getElementById('expenseForm').addEventListener('submit', function(e) {
                // Log for debugging
                console.log("Form submission started");
                
                // Always preventDefault first to handle our custom validation
                e.preventDefault();
                
                // Form validation
                if (!this.checkValidity()) {
                    console.log("Form validation failed");
                    this.classList.add('was-validated');
                    return false;
                }
                
                // Check if tax is greater than expense
                const hasTax = document.getElementById('has_tax').checked;
                
                if (hasTax) {
                    const isRental = document.getElementById('is_rental').checked;
                    const expense = isRental ? 
                        (parseFloat(document.getElementById('rental_rate').value) || 0) : 
                        (parseFloat(document.getElementById('expense').value) || 0);
                    const tax = parseFloat(document.getElementById('tax').value) || 0;
                    
                    if (tax > expense) {
                        console.log("Tax validation failed: tax > expense");
                        alert("Tax cannot be greater than expense.");
                        return false;
                    }
                }
                
                // Log for debugging - right before submission
                console.log("Validation passed, submitting form");
                console.log("Is rental: " + document.getElementById('is_rental_field').value);
                console.log("Expense value: " + document.getElementById('expense').value);
                console.log("Rental rate value: " + document.getElementById('rental_rate').value);
                
                // If validation passes, manually submit the form
                this.submit();
            });
        });
        // Function to edit expense record
        function editExpense(id, category, subcategory, date, budget, expense, payee, record_description, remarks, rental_rate, tax, invoice_no, bill_to_client) {
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
                invoice_no: invoice_no,
                bill_to_client: bill_to_client
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
</body>
</html>
