<?php
    include('validate_login.php');
    require_once 'activity_logger.php';
    
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

            logUserActivity(
                'delete', 
                'ms_records.php', 
                "delete project"
            );
            
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

            logUserActivity(
                'edit', 
                'ms_records.php', 
                "edit project details"
            );

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

    include('search_filter_sort.php');

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

                logUserActivity(
                    'edit', 
                    'ms_records.php', 
                    "edit record"
                );
                
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
                    
                    logUserActivity(
                        'edit', 
                        'ms_records.php', 
                        "update loss record"
                    );
                    
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

                    logUserActivity(
                        'add', 
                        'ms_records.php', 
                        "add loss record to corporate"
                    );
                    
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

                    logUserActivity(
                        'edit', 
                        'ms_records.php', 
                        "update loss record"
                    );
                    
                    $remove_loss_id->bind_param("i", $edit_id);
                    $remove_loss_id->execute();
                    $remove_loss_id->close();
                    
                    // Delete any corporate records that reference this as a loss
                    $delete_corporate = $conn->prepare("
                        DELETE FROM expense 
                        WHERE project_id = 1 AND loss_id = ?
                    ");

                    logUserActivity(
                        'delete', 
                        'ms_records.php', 
                        "delete loss record from corporate"
                    );
                    
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

                        logUserActivity(
                            'delete', 
                            'ms_records.php', 
                            "delete loss record from corporate"
                        );
                        
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

                logUserActivity(
                    'add', 
                    'ms_records.php', 
                    "add expense record"
                );
                
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

                    logUserActivity(
                        'edit', 
                        'ms_records.php', 
                        "update loss record"
                    );
                                        
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

                    logUserActivity(
                        'add', 
                        'ms_records.php', 
                        "add loss record to corporate"
                    );
                    
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

                    logUserActivity(
                        'add', 
                        'ms_records.php', 
                        "add expense record to assets"
                    );
                    
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

                    logUserActivity(
                        'delete', 
                        'ms_records.php', 
                        "delete loss record from corporate"
                    );
                    
                $delete_loss_stmt->bind_param("i", $loss_record_id);
                $delete_loss_stmt->execute();
                $delete_loss_stmt->close();
            }
            $check_stmt->close();
            
            // Now delete the main record
            $stmt = $conn->prepare("DELETE FROM expense WHERE record_id = ?");
            
            logUserActivity(
                'delete', 
                'ms_records.php', 
                "delete expense record"
            );
                    
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
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <link href="css/ms_project_expense.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
</head>
<body>

    <script>
    // Define subcategories data directly from PHP
    window.subcategories = <?php echo json_encode($subcategories); ?>;

    // Define a simple function to handle category changes
    function handleCategoryChange() {
        const categorySelect = document.getElementById('category');
        const subcategorySelect = document.getElementById('subcategory');
        
        if (!categorySelect || !subcategorySelect) {
            console.error("Category or subcategory elements not found");
            return;
        }
        
        // Get selected category
        const selectedCategory = categorySelect.value;
        console.log("Category changed to:", selectedCategory);
        console.log("Available subcategories:", window.subcategories);
        
        // Clear existing options
        subcategorySelect.innerHTML = '<option value="">Select Subcategory</option>';
        
        if (!selectedCategory) {
            subcategorySelect.disabled = true;
            return;
        }
        
        // Filter subcategories for the selected category
        const filteredSubcategories = window.subcategories.filter(sub => 
            sub.category_name === selectedCategory
        );
        
        console.log("Filtered subcategories:", filteredSubcategories);
        
        if (filteredSubcategories && filteredSubcategories.length > 0) {
            // Enable subcategory select
            subcategorySelect.disabled = false;
            
            // Add filtered options
            filteredSubcategories.forEach(sub => {
                const option = document.createElement('option');
                option.value = sub.subcategory_name;
                option.textContent = sub.subcategory_name;
                subcategorySelect.appendChild(option);
            });
        } else {
            // If no subcategories found
            subcategorySelect.disabled = true;
        }
    }

    // Attach event handler when DOM is loaded
    document.addEventListener('DOMContentLoaded', function() {
        const categorySelect = document.getElementById('category');
        if (categorySelect) {
            categorySelect.addEventListener('change', handleCategoryChange);
            
            // Also handle edit mode by listening for modal show events
            const expenseModal = document.getElementById('expenseModal');
            if (expenseModal) {
                expenseModal.addEventListener('shown.bs.modal', function() {
                    // If category has a value, trigger change event
                    if (categorySelect.value) {
                        handleCategoryChange();
                    }
                });
            }
        }
        
        // Also attach handler for edit buttons
        document.addEventListener('click', function(e) {
            if (e.target.classList.contains('edit-btn') || e.target.closest('.edit-btn')) {
                setTimeout(function() {
                    if (categorySelect.value) {
                        handleCategoryChange();
                    }
                }, 300); // Delay to ensure modal is open and fields are populated
            }
        });
    });
    </script>

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
    
    <div class="sidebar" id="sidebar">
        <?php include 'sidebar.php'; ?>
    </div>
    
    <div class="content-area">
        <?php include 'header.php'; ?>

        <div class="content-body">
            <!-- Project Summary -->
            <?php if ($project && $project_id != 1): ?>
                <div class="project-summary position-relative">
                    <div class="project-options">
                        <button class="ellipsis-btn">
                            <img src="icons/ellipsis-vertical.svg" alt="Options">
                        </button>
                        <div class="dropdown-menu" style="display:none;">
                            <button class="dropdown-edit">Edit</button>
                            <button class="dropdown-delete" data-project-id="<?= $project['project_id'] ?>">Delete</button>
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
            <?php elseif ($project && $project_id == 1): ?>
            <?php else: ?>
                <p class="text-danger text-center">Project not found.</p>
            <?php endif; ?>

            <!-- Add Records, Search, Filter, and Toggle Bar -->
            <?php include('search_filter_sort_ui.php'); ?>

            <div class="records-table-container" id="records-view" style="display: block;">
                <!-- RECORDS VIEW -->
                <!-- Expense Records Table -->
                <div class="table-responsive">
                    <table class="table table-hover" id="expense-table">
                        <thead>
                            <tr>
                                <th> </th>
                                <th>Category</th>
                                <th>Description</th>
                                <th>Payee</th>
                                <th class="text-right">Budget</th>
                                <th class="text-right">Expense</th>
                                <th class="text-right">Variance</th>
                                <th class="text-right">Tax</th>
                                <th>Remarks</th>
                                <th class="text-center">Date</th>
                                <th class="text-center">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (count($records) > 0): ?>
                                <?php foreach ($records as $i => $row): ?>
                                    <tr>
                                        <td><?= $i + 1 ?></td>
                                        <td>
                                            <?php 
                                            // Display category and subcategory together
                                            echo htmlspecialchars($row['category']);
                                            if (!empty($row['subcategory'])) {
                                                echo ': ' . htmlspecialchars($row['subcategory']);
                                            }
                                            ?>
                                        </td>
                                        <td title="<?= htmlspecialchars($row['record_description']) ?>">
                                            <?= htmlspecialchars($row['record_description']) ?>
                                        </td>
                                        <td><?= htmlspecialchars($row['payee']) ?></td>
                                        <td class="text-right">₱<?= number_format($row['budget'], 2) ?></td>
                                        <td class="text-right">₱<?= number_format($row['expense'], 2) ?></td>
                                        <td class="text-right">₱<?= number_format($row['variance'], 2) ?></td>
                                        <td class="text-right">₱<?= number_format($row['tax'], 2) ?></td>
                                        <td title="<?= htmlspecialchars($row['remarks']) ?>">
                                            <?= htmlspecialchars($row['remarks']) ?>
                                        </td>
                                        <td class="text-center"><?= date("M d, Y", strtotime($row['purchase_date'])) ?></td>
                                        <td class="text-center">
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
                                                data-bill_to_client="<?= $row['bill_to_client'] ?? 'No' ?>"
                                                data-is_rental="<?= $row['is_rental'] ?? 'No' ?>"
                                                data-is_company_loss="<?= $row['is_company_loss'] ?? 'No' ?>">
                                                <img src="icons/eye.svg" width="16" alt="View">
                                            </button>
                                            <button type="button" id="edit-btn-<?= $row['record_id'] ?>" class="btn btn-sm btn-primary edit-btn"
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
                                                data-bill_to_client="<?= $row['bill_to_client'] ?? 'No' ?>"
                                                data-is_rental="<?= $row['is_rental'] ?? 'No' ?>"
                                                data-is_company_loss="<?= $row['is_company_loss'] ?? 'No' ?>">
                                                <img src="icons/pencil-white.svg" width="16" alt="Edit">
                                            </button>
                                            <button type="button" class="btn btn-sm btn-danger delete-btn">
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
                <?php include('analytics_view.php'); ?>
            </div>
        </div>
    </div>

<!-- Include the new event delegation script -->
<script src="js/project_buttons.js"></script>

<?php if ($project_id != 1): ?>
    <?php include('edit_project_modal.php'); ?>
<?php endif; ?>

<script src="js/sidebar.js"></script>
<script src="js/header.js"></script>

<script>
// jQuery-based view modal script (keep this since it works)
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
        
        $('#view_bill_to_client').text($(this).data('bill_to_client') || 'No');
        $('#view_is_rental').text($(this).data('is_rental') || 'No');
        $('#view_is_company_loss').text($(this).data('is_company_loss') || 'No');

        // Hide/show and style rows based on data
        if ($(this).data('bill_to_client') === 'Yes') {
            $('#view_bill_to_client').closest('tr').show().addClass('table-success');
        } else {
            $('#view_bill_to_client').closest('tr').hide();
        }
        
        if ($(this).data('is_company_loss') === 'Yes') {
            $('#view_is_company_loss').closest('tr').show().addClass('table-danger');
        } else {
            $('#view_is_company_loss').closest('tr').hide();
        }
        
        if ($(this).data('is_rental') === 'Yes') {
            $('#view_rental_rate').closest('tr').addClass('table-primary');
        } else {
            $('#view_rental_rate').closest('tr').removeClass('table-primary');
        }

        // Get additional details via AJAX
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
</body>
</html>