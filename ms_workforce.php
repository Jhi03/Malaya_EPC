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

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Malaya Solar Energies Inc.</title>
    <link rel="icon" href="images/Malaya_Logo.png" type="image/png">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Atkinson+Hyperlegible&display=swap" rel="stylesheet">
    <link href="css/ms_workforce.css" rel="stylesheet">
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
</body>
</html>