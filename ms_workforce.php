<!DOCTYPE html>
<html lang="en">
<head>
    <title>MalayaSol Employee Management</title>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Atkinson+Hyperlegible&display=swap" rel="stylesheet">
    <link rel="icon" href="images/Malaya_Logo.png" type="image/png">
    <link href="css/ms_sidebar.css" rel="stylesheet">
    <link href="css/ms_workforce.css" rel="stylesheet">
    <style>
        body {
            font-family: 'Atkinson Hyperlegible', sans-serif;
            margin: 0;
            padding: 0;
            color: black;
            background-color: #f5f5f5;
        }

        /* Fix header placement */
        .content-area {
            margin-left: 210px;
    flex: 1;
    display: flex;
    flex-direction: column;
    overflow-y: auto;
    height: 120vh;
    transition: margin-left 0.3s ease-in-out;
        }
        
        .container {
    max-width: 900px; /* or try 960px */
    background-color: #f4f6f8;
    padding: 20px;
    border-radius: 8px;
    box-shadow: 0 0 10px rgba(0,0,0,0.1);
    margin: 0 auto;
}
        h1 {
            color: black;
            margin-bottom: 20px;
            font-family: 'Atkinson Hyperlegible', sans-serif;

        }
        
        .controls {
            display: flex;
    justify-content: space-between;
    align-items: center;
    width: 85%;          /* Matches the table width */
    margin: 0 auto 20px; /* Centers it and adds spacing below */
    gap: 10px; ;
    font-family: 'Atkinson Hyperlegible', sans-serif;

        }
        
        .search-box {
            padding: 8px;
    border: 1px solid #ddd;
    border-radius: 4px;
    width: 250px;
        }
        .search-btn {
    padding: 8px 12px;
    background-color: #eee;
    border: 1px solid #ccc;
    border-radius: 4px;
    cursor: pointer;
}
.add-btn {
    background-color: #3498db;
    color: white;
    border: none;
    padding: 10px 18px;
    border-radius: 6px;
    font-weight: 500;
    box-shadow: 0 2px 4px rgba(0,0,0,0.1);
    transition: background-color 0.3s ease;
}

.add-btn:hover {
    background-color: #2980b9;
}
        
        table {
    width: 85%;
    margin: 0 auto; /* ✅ This centers the table */
    border-collapse: separate;
    border-spacing: 0 12px; /* spacing between rows */
    background-color: transparent;
    font-family: 'Atkinson Hyperlegible', sans-serif;
}
th {
    background-color: #ecf0f1;
    padding: 14px 16px;
    color: #2c3e50;
    font-weight: 600;
    text-align: left;
    border: none;
}

td {
    background-color: white;
    padding: 14px 16px;
    border: none;
    vertical-align: middle;
    box-shadow: 0 1px 3px rgba(0,0,0,0.06);
    border-radius: 6px;
}

/* Round only outer sides of rows */
td:first-child {
    border-top-left-radius: 6px;
    border-bottom-left-radius: 6px;
}

td:last-child {
    border-top-right-radius: 6px;
    border-bottom-right-radius: 6px;
}
thead th {
    background-color: #ecf0f1;
    color: #2c3e50;
    font-weight: 600;
    text-align: left;
    padding: 14px 16px;
    border: none;
    border-bottom: 2px solid #bdc3c7;
    border-radius: 6px;

}

tbody tr {
    background-color: #fff;
    border-radius: 8px;
    box-shadow: 0 1px 4px rgba(0, 0, 0, 0.05);
    transition: box-shadow 0.2s ease;
}

tbody tr:hover {
    box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
}

tbody td {
    padding: 14px 16px;
    border: none;
    vertical-align: middle;
}

tbody tr td:first-child {
    border-top-left-radius: 8px;
    border-bottom-left-radius: 8px;
}

tbody tr td:last-child {
    border-top-right-radius: 8px;
    border-bottom-right-radius: 8px;
}
        
        /* Status colors */
        .status-active {
            color: green;
            font-weight: bold;
        }
        
        .status-resigned {
            color: red;
            font-weight: bold;
        }
        
        .status-leave {
            color: orange;
            font-weight: bold;
        }
        
        .action-btn {
            margin-right: 5px;
            padding: 5px 10px;
            border: none;
            border-radius: 3px;
            cursor: pointer;
        }
        
        .edit-btn {
    background-color: #f39c12;
    color: white;
}

.delete-btn {
    background-color: #e74c3c;
    color: white;
}
        /* Modal styles */
        .modal {
            display: none;
            position: fixed;
            z-index: 1000;
            left: 0;
            top: 0;
            width: 100%;
            height: 100%;
            overflow: auto;
            background-color: rgba(0,0,0,0.4);
        }
        
        .modal-content {
            background-color: white;
            margin: 10% auto;
            padding: 20px;
            border-radius: 5px;
            max-width: 500px;
            box-shadow: 0 5px 8px rgba(0,0,0,0.2);
        }
        
        .close {
            color: #aaa;
            float: right;
            font-size: 28px;
            font-weight: bold;
            cursor: pointer;
        }
        
        .close:hover {
            color: black;
        }
        
        .form-group {
            margin-bottom: 15px;
        }
        
        .form-group label {
            display: block;
            margin-bottom: 5px;
        }
        
        .form-group input, .form-group select {
            width: 100%;
            padding: 8px;
            border: 1px solid #ddd;
            border-radius: 4px;
            box-sizing: border-box;
        }
        
        .submit-btn {
            background-color: #2ecc71;
            color: white;
            border: none;
            padding: 10px 15px;
            border-radius: 4px;
            cursor: pointer;
            width: 100%;
        }
        
        .pagination {
            margin-top: 20px;
            text-align: center;
        }
        
        .pagination a {
            color: black;
            padding: 8px 16px;
            text-decoration: none;
            transition: background-color .3s;
            border: 1px solid #ddd;
            margin: 0 4px;
        }
        
        .pagination a.active {
            background-color: #3498db;
            color: white;
            border: 1px solid #3498db;
        }
        
        .pagination a:hover:not(.active) {
            background-color: #ddd;
        }
        
        /* Message Modal Styles */
        #messageModal {
            display: none;
            position: fixed;
            z-index: 1500;
            left: 0;
            top: 0;
            width: 100%;
            height: 100%;
            background-color: rgba(0,0,0,0.5);
        }

        #messageModal .modal-content {
            position: absolute;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%);
            background-color: #fff;
            padding: 20px;
            border-radius: 5px;
            box-shadow: 0 0 20px rgba(0,0,0,0.2);
            max-width: 400px;
            text-align: center;
        }

        .close-modal {
            position: absolute;
            right: 10px;
            top: 5px;
            font-size: 24px;
            font-weight: bold;
            cursor: pointer;
        }

        .message-success {
            color: #28a745;
            padding: 15px;
            border-left: 4px solid #28a745;
            background-color: rgba(40, 167, 69, 0.1);
        }

        .message-error {
            color: #dc3545;
            padding: 15px;
            border-left: 4px solid #dc3545;
            background-color: rgba(220, 53, 69, 0.1);
        }

        .btn-modal {
            display: inline-block;
            margin-top: 15px;
            padding: 8px 20px;
            background-color: #007bff;
            color: white;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            font-weight: bold;
        }

        .btn-modal:hover {
            background-color: #0069d9;
        }
    </style>
</head>
<body>

<?php
$page_title = "WORKFORCE";

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
    
    // Create personnel table if it doesn't exist
    $create_table_sql = "CREATE TABLE IF NOT EXISTS personnel (
        employee_id INT PRIMARY KEY,
        full_name VARCHAR(100),
        department VARCHAR(100),
        position VARCHAR(50),
        salary DECIMAL(10,2),
        status VARCHAR(20)
    )";
    
    if (!$conn->query($create_table_sql)) {
        die("Error creating table: " . $conn->error);
    }
    
    // Initialize message variables
    $message = "";
    $message_type = "";
    
    // Handle form submissions for adding/editing employees
    if ($_SERVER["REQUEST_METHOD"] == "POST") {
        if (isset($_POST['add_employee'])) {
            $employee_id = $_POST['employee_id'];
            $full_name = $_POST['full_name'];
            $department = $_POST['department'];
            $position = $_POST['position'];
            $salary = $_POST['salary'];
            $status = $_POST['status'];
            
            $sql = "INSERT INTO personnel (employee_id, full_name, department, position, salary, status) 
                    VALUES (?, ?, ?, ?, ?, ?)";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("isssds", $employee_id, $full_name, $department, $position, $salary, $status);
            
            if ($stmt->execute()) {
                $message = "Employee added successfully!";
                $message_type = "success";
            } else {
                $message = "Error: " . $stmt->error;
                $message_type = "error";
            }
            $stmt->close();
        }
        
        if (isset($_POST['edit_employee'])) {
            $employee_id = $_POST['employee_id'];
            $full_name = $_POST['full_name'];
            $department = $_POST['department'];
            $position = $_POST['position'];
            $salary = $_POST['salary'];
            $status = $_POST['status'];
            
            $sql = "UPDATE personnel SET full_name=?, department=?, position=?, salary=?, status=? WHERE employee_id=?";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("sssdsi", $full_name, $department, $position, $salary, $status, $employee_id);
            
            if ($stmt->execute()) {
                $message = "Employee updated successfully!";
                $message_type = "success";
            } else {
                $message = "Error: " . $stmt->error;
                $message_type = "error";
            }
            $stmt->close();
        }
    }
    
    // Handle delete request
    if (isset($_GET['delete'])) {
        $id_to_delete = $_GET['delete'];
        
        $sql = "DELETE FROM personnel WHERE employee_id = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("i", $id_to_delete);
        
        if ($stmt->execute()) {
            $message = "Employee deleted successfully!";
            $message_type = "success";
        } else {
            $message = "Error deleting record: " . $stmt->error;
            $message_type = "error";
        }
        $stmt->close();
    }
    
    // Pagination
    $records_per_page = 10;
    if (isset($_GET['page'])) {
        $page = $_GET['page'];
    } else {
        $page = 1;
    }
    $start_from = ($page-1) * $records_per_page;
    
    // Search functionality
    $search = "";
    if (isset($_GET['search'])) {
        $search = $_GET['search'];
        $sql = "SELECT * FROM personnel WHERE 
                full_name LIKE ? OR 
                department LIKE ? OR 
                position LIKE ? OR 
                status LIKE ? 
                ORDER BY employee_id LIMIT ?, ?";
        $stmt = $conn->prepare($sql);
        $search_param = "%$search%";
        $stmt->bind_param("ssssii", $search_param, $search_param, $search_param, $search_param, $start_from, $records_per_page);
    } else {
        $sql = "SELECT * FROM personnel ORDER BY employee_id LIMIT ?, ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("ii", $start_from, $records_per_page);
    }
    
    $stmt->execute();
    $result = $stmt->get_result();
    
    // Define the allowed departments
    $allowed_departments = [
        "Executive & Strategy Office",
        "Finance & Digital Accounting Department",
        "IT Infrastructure & Cybersecurity Division",
        "System Development & Innovation Lab",
        "Cloud Engineering & Data Services Department",
        "Operations & Project Management Department",
        "Technical Support & IT Helpdesk",
        "HR & Digital Workforce Management",
        "Sales & Customer Engagement Hub",
        "Sustainability & Energy Analytics Division"
    ];
    ?>

   
    <div class="sidebar" id="sidebar">
        <?php include 'sidebar.php'; ?>
    </div>
    
    <div class="content-area">
        <!-- Header Section - Fixed placement -->
        <header>
            <?php include 'header.php'; ?>
        </header>
        
        <div style="padding-top: 50px;"><!-- Space for fixed header -->
        <h1 style="color: black; margin-left: 80px;  margin-bottom: 20px ;">Employee Management</h1>
            
            <div class="controls">
                <form method="GET" action="">
                    <input type="text" name="search" class="search-box" placeholder="Search employees..." value="<?php echo htmlspecialchars($search); ?>">
                    <button type="submit" class="action-btn">Search</button>
                    <?php if(!empty($search)): ?>
                        <a href="<?php echo $_SERVER['PHP_SELF']; ?>" class="action-btn">Clear</a>
                    <?php endif; ?>
                </form>
                <button class="add-btn" onclick="document.getElementById('addModal').style.display='block'">Add New Employee</button>
            </div>
            
            <table>
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Full Name</th>
                        <th>Department</th>
                        <th>Position</th>
                        <th>Salary</th>
                        <th>Status</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php
                    if ($result->num_rows > 0) {
                        while($row = $result->fetch_assoc()) {
                            $status_class = '';
                            if ($row['status'] == 'Active') {
                                $status_class = 'status-active';
                            } else if ($row['status'] == 'Resigned') {
                                $status_class = 'status-resigned';
                            } else if ($row['status'] == 'On Leave') {
                                $status_class = 'status-leave';
                            }
                            
                            echo "<tr>";
                            echo "<td>" . $row['employee_id'] . "</td>";
                            echo "<td>" . htmlspecialchars($row['full_name']) . "</td>";
                            echo "<td>" . htmlspecialchars($row['department']) . "</td>";
                            echo "<td>" . htmlspecialchars($row['position']) . "</td>";
                            echo "<td>₱" . number_format($row['salary'], 2) . "</td>";
                            echo "<td class='" . $status_class . "'>" . htmlspecialchars($row['status']) . "</td>";
                            echo "<td>
                                    <button class='action-btn edit-btn' onclick='openEditModal(" . $row['employee_id'] . ", \"" . addslashes($row['full_name']) . "\", \"" . addslashes($row['department']) . "\", \"" . addslashes($row['position']) . "\", " . $row['salary'] . ", \"" . addslashes($row['status']) . "\")'>Edit</button>
                                    <a href='" . $_SERVER['PHP_SELF'] . "?delete=" . $row['employee_id'] . "' class='action-btn delete-btn' onclick='return confirm(\"Are you sure you want to delete this employee?\")'>Delete</a>
                                  </td>";
                            echo "</tr>";
                        }
                    } else {
                        echo "<tr><td colspan='7' style='text-align: center;'>No employees found</td></tr>";
                    }
                    ?>
                    </tbody>
                </table>
            </div>
            
            <?php
            // Pagination links
            $sql = "SELECT COUNT(*) AS total FROM personnel";
            if (!empty($search)) {
                $sql = "SELECT COUNT(*) AS total FROM personnel WHERE 
                        full_name LIKE ? OR 
                        department LIKE ? OR 
                        position LIKE ? OR 
                        status LIKE ?";
                $count_stmt = $conn->prepare($sql);
                $search_param = "%$search%";
                $count_stmt->bind_param("ssss", $search_param, $search_param, $search_param, $search_param);
            } else {
                $count_stmt = $conn->prepare($sql);
            }
            
            $count_stmt->execute();
            $count_result = $count_stmt->get_result();
            $row = $count_result->fetch_assoc();
            $total_records = $row['total'];
            $total_pages = ceil($total_records / $records_per_page);
            
            echo "<div class='pagination'>";
            if($total_pages > 1) {
                if($page > 1) {
                    echo "<a href='" . $_SERVER['PHP_SELF'] . "?page=" . ($page-1);
                    if(!empty($search)) echo "&search=" . urlencode($search);
                    echo "'>&laquo; Previous</a>";
                }
                
                for($i = 1; $i <= $total_pages; $i++) {
                    echo "<a href='" . $_SERVER['PHP_SELF'] . "?page=" . $i;
                    if(!empty($search)) echo "&search=" . urlencode($search);
                    echo "' " . ($page == $i ? "class='active'" : "") . ">" . $i . "</a>";
                }
                
                if($page < $total_pages) {
                    echo "<a href='" . $_SERVER['PHP_SELF'] . "?page=" . ($page+1);
                    if(!empty($search)) echo "&search=" . urlencode($search);
                    echo "'>Next &raquo;</a>";
                }
            }
            echo "</div>";
            
            $stmt->close();
            $count_stmt->close();
            $conn->close();
            ?>
            </div>
        </div>
    </div>
    
    <!-- Add Employee Modal -->
    <div id="addModal" class="modal">
        <div class="modal-content">
            <span class="close" onclick="document.getElementById('addModal').style.display='none'">&times;</span>
            <h2>Add New Employee</h2>
            <form method="POST" action="">
                <div class="form-group">
                    <label for="employee_id">Employee ID:</label>
                    <input type="number" id="employee_id" name="employee_id" required>
                </div>
                <div class="form-group">
                    <label for="full_name">Full Name:</label>
                    <input type="text" id="full_name" name="full_name" required>
                </div>
                <div class="form-group">
                    <label for="department">Department:</label>
                    <select id="department" name="department" required>
                        <?php foreach($allowed_departments as $dept): ?>
                            <option value="<?php echo htmlspecialchars($dept); ?>"><?php echo htmlspecialchars($dept); ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="form-group">
                    <label for="position">Position:</label>
                    <input type="text" id="position" name="position" required>
                </div>
                <div class="form-group">
                    <label for="salary">Salary:</label>
                    <input type="number" id="salary" name="salary" step="0.01" required>
                </div>
                <div class="form-group">
                    <label for="status">Status:</label>
                    <select id="status" name="status" required>
                        <option value="Active">Active</option>
                        <option value="On Leave">On Leave</option>
                        <option value="Resigned">Resigned</option>
                    </select>
                </div>
                <button type="submit" name="add_employee" class="submit-btn">Add Employee</button>
            </form>
        </div>
    </div>
    
    <!-- Edit Employee Modal -->
    <div id="editModal" class="modal">
        <div class="modal-content">
            <span class="close" onclick="document.getElementById('editModal').style.display='none'">&times;</span>
            <h2>Edit Employee</h2>
            <form method="POST" action="">
                <input type="hidden" id="edit_employee_id" name="employee_id">
                <div class="form-group">
                    <label for="edit_full_name">Full Name:</label>
                    <input type="text" id="edit_full_name" name="full_name" required>
                </div>
                <div class="form-group">
                    <label for="edit_department">Department:</label>
                    <select id="edit_department" name="department" required>
                        <?php foreach($allowed_departments as $dept): ?>
                            <option value="<?php echo htmlspecialchars($dept); ?>"><?php echo htmlspecialchars($dept); ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="form-group">
                    <label for="edit_position">Position:</label>
                    <input type="text" id="edit_position" name="position" required>
                </div>
                <div class="form-group">
                    <label for="edit_salary">Salary:</label>
                    <input type="number" id="edit_salary" name="salary" step="0.01" required>
                </div>
                <div class="form-group">
                    <label for="edit_status">Status:</label>
                    <select id="edit_status" name="status" required>
                        <option value="Active">Active</option>
                        <option value="On Leave">On Leave</option>
                        <option value="Resigned">Resigned</option>
                    </select>
                </div>
                <button type="submit" name="edit_employee" class="submit-btn">Update Employee</button>
            </form>
        </div>
    </div>
    
    <!-- Message Modal -->
    <div id="messageModal" class="modal">
        <div class="modal-content">
            <span class="close-modal" onclick="closeModal()">&times;</span>
            <div id="modalMessage" class=""></div>
            <button class="btn-modal" onclick="closeModal()">OK</button>
        </div>
    </div>
    
    <script>
        // Function to open the edit modal with pre-filled data
        function openEditModal(id, name, department, position, salary, status) {
            document.getElementById('edit_employee_id').value = id;
            document.getElementById('edit_full_name').value = name;
            document.getElementById('edit_department').value = department;
            document.getElementById('edit_position').value = position;
            document.getElementById('edit_salary').value = salary;
            document.getElementById('edit_status').value = status;
            document.getElementById('editModal').style.display = 'block';
        }
        
        // Close the modals when clicking outside of them
        window.onclick = function(event) {
            if (event.target == document.getElementById('addModal')) {
                document.getElementById('addModal').style.display = 'none';
            }
            if (event.target == document.getElementById('editModal')) {
                document.getElementById('editModal').style.display = 'none';
            }
            if (event.target == document.getElementById('messageModal')) {
                document.getElementById('messageModal').style.display = 'none';
            }
        }
        
        // Function to close the message modal
        function closeModal() {
            document.getElementById('messageModal').style.display = "none";
        }
        
        // Set selected department in edit modal
        function setSelectedDepartment() {
            const departmentSelect = document.getElementById('edit_department');
            const departmentValue = departmentSelect.getAttribute('data-value');
            if (departmentValue) {
                for (let i = 0; i < departmentSelect.options.length; i++) {
                    if (departmentSelect.options[i].value === departmentValue) {
                        departmentSelect.options[i].selected = true;
                        break;
                    }
                }
            }
        }
    </script>
    
    <?php
    // Display message in modal if there is one
    if (!empty($message)) {
        $modalClass = ($message_type == "success") ? "message-success" : "message-error";
        echo "
        <script>
            document.addEventListener('DOMContentLoaded', function() {
                document.getElementById('modalMessage').innerHTML = '" . addslashes($message) . "';
                document.getElementById('modalMessage').className = '" . $modalClass . "';
                document.getElementById('messageModal').style.display = 'block';
            });
        </script>";
    }
    ?>
    
    <script src="js/sidebar.js"></script>
    <script src="js/header.js"></script>
    <script src="js/ms_employees.js"></script>

</body>
</html>