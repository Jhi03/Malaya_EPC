<?php
session_start();

// Check if user is logged in
if (!isset($_SESSION['username'])) {
    header("Location: ms_login.php");
    exit();
}

$conn = new mysqli("localhost", "root", "", "malayasol");
if ($conn->connect_error) die("Connection failed: " . $conn->connect_error);

// Handle Add/Edit/Delete
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['delete_vendors']) && isset($_POST['vendor_ids'])) {
        $vendor_ids = explode(',', $_POST['vendor_ids']);
        foreach ($vendor_ids as $vendor_id) {
            $stmt = $conn->prepare("DELETE FROM vendors WHERE vendor_id = ?");
            $stmt->bind_param("i", $vendor_id);
            $stmt->execute();
        }
        echo "<script>window.location = 'ms_vendors.php';</script>";
        exit();
    } else {
        $vendor_id = $_POST['vendor_id'];
        $vendor_name = $_POST['vendor_name'];
        $vendor_type = $_POST['vendor_type'];
        $contact_person = $_POST['contact_person'];
        $vendor_email = $_POST['vendor_email'] ?: null;
        $contact_no = $_POST['contact_no'];
        $telephone = $_POST['telephone'] ?: null;
        $vendor_address = $_POST['vendor_address'];

        if ($vendor_id) {
            $stmt = $conn->prepare("UPDATE vendors SET vendor_name=?, vendor_type=?, contact_person=?, vendor_email=?, contact_no=?, telephone=?, vendor_address=? WHERE vendor_id=?");
            $stmt->bind_param("ssssissi", $vendor_name, $vendor_type, $contact_person, $vendor_email, $contact_no, $telephone, $vendor_address, $vendor_id);
        } else {
            $stmt = $conn->prepare("INSERT INTO vendors (vendor_name, vendor_type, contact_person, vendor_email, contact_no, telephone, vendor_address) VALUES (?, ?, ?, ?, ?, ?, ?)");
            $stmt->bind_param("ssssiss", $vendor_name, $vendor_type, $contact_person, $vendor_email, $contact_no, $telephone, $vendor_address);
        }

        if ($stmt->execute()) {
            echo "<script>window.location = 'ms_vendors.php';</script>";
            exit();
        } else {
            echo "Error: " . $stmt->error;
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Atkinson+Hyperlegible&display=swap" rel="stylesheet">
    <link href="css/ms_vendors.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</head>
<body>
    <div class="sidebar" id="sidebar">
        <div class="logo">
            <img src="Malaya_Logo.png" alt="Logo"> Malaya Sol <br>Accounting System
        </div>
        <div class="nav-buttons">
            <a href="ms_dashboard.php"><button>Dashboard</button></a>
            <a href="ms_projects.php"><button>Projects</button></a>
            <a href="ms_assets.php"><button>Assets</button></a>
            <a href="ms_expenses.php"><button>Expenses</button></a>
            <a href="ms_workforce.php"><button>Workforce</button></a>
            <a href="ms_payroll.php"><button>Payroll</button></a>
            <a class="active" href="ms_vendors.php"><button>Vendors</button></a>
            <a href="ms_reports.php"><button>Reports</button></a>
        </div>
    </div>
    
    <div class="content-area">
        <!-- Header Section -->
        <header class="top-bar">
            <button class="hamburger" id="toggleSidebar">☰</button>
            <h2 class="page-title">VENDORS</h2>
            
            <div class="user-dropdown">
                <button class="user-icon" id="userDropdownBtn">
                    <img src="icons/circle-user-round.svg" alt="UserIcon" width="30">
                </button>
                <div class="dropdown-menu" id="userDropdownMenu">
                    <a href="#" class="dropdown-item">Settings</a>
                    <a href="ms_logout.php" class="dropdown-item logout-btn">Logout</a>
                </div>
            </div>
        </header>

        <!-- Add Records, Search, Filter, and Toggle Bar -->
        <div class="search-filter-bar">
            <!-- Left group: Add, Search, Filter -->
            <div class="left-controls">
            <button class="add-record-btn" id="addVendorBtn">ADD <img src="icons/circle-plus.svg" alt="UserIcon" width="16"></button>

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

            <!-- Right group: Select items -->
            <div class="select-items">
                <button class="select-btn">SELECT</button>
            </div>
        </div>

        <div class="vendor-content">
            <!-- Left Section: List of Vendors -->
            <div class="vendor-list">
                <div class="vendor-list-header">Vendors</div> <!-- Header Bar inside vendor list -->

                <?php
                $query = "SELECT * FROM vendors ORDER BY vendor_id ASC";
                $result = mysqli_query($conn, $query);

                if (mysqli_num_rows($result) > 0):
                    $counter = 1;
                ?>
                    <div class="vendor-list-table-container">
                        <table class="table">
                            <tbody>
                                <?php while ($row = mysqli_fetch_assoc($result)): ?>
                                    <tr data-id="<?= $row['vendor_id'] ?>" data-json='<?= json_encode($row) ?>'>
                                        <td class="row-index-cell">
                                            <span class="row-number"><?= $counter++ ?></span>
                                            <input type="checkbox" class="row-checkbox" style="display: none;">
                                        </td>
                                        <td><?= htmlspecialchars($row['vendor_name']) ?></td>
                                        <td><?= htmlspecialchars($row['vendor_type']) ?></td>
                                        <td>0<?= htmlspecialchars($row['contact_no']) ?></td>
                                    </tr>
                                <?php endwhile; ?>
                                </tbody>
                        </table>
                        <button class="delete-selected-btn" style="display: none;">
                            <img src="icons/trash.svg" alt="TrashIcon" width="20">
                        </button>
                    </div>
                <?php else: ?>
                    <p style='padding: 20px;'>There are no existing vendors.</p>
                <?php endif; ?>
            </div>

            <!-- Right Section: Vendor Details -->
            <div class="vendor-details" id="vendorDetails">
                <div id="vendorPlaceholder">
                    <h4>Select an Vendor</h4>
                </div>

                <div id="vendorInfo" style="display: none;">
                    <h4 id="vendorHeader"></h4> <!-- Dynamic vendor_description header -->

                    <div class="vendor-details-info">
                        <p><strong>Type:</strong> <span id="vendorType"></span></p>
                        <div class="vendor-cost-tax">
                            <p><strong>Contact Person:</strong><span id="vendorPerson"></span></p>
                            <p><strong>Company Email:</strong><span id="vendorEmail"></span></p>
                        </div>
                        <div class="vendor-rental-date">
                            <p><strong>Contact No.:</strong><span id="vendorContact"></span></p>
                            <p><strong>Telephone No.:</strong> <span id="vendorTelephone"></span></p>
                        </div>
                        <p><strong>Address:</strong> <span id="vendorAddress"></span></p>
                    </div>

                    <!-- Edit Button -->
                    <div class="edit-button" id="editButton" style="display: none;">
                        <button class="btn btn-primary" onclick="openEditVendorModal()">
                            Edit <img src="icons/edit.svg" width="20">
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Deletion Modal -->
    <div id="deleteModal" class="custom-modal-overlay">
        <div class="custom-modal">
            <div class="modal-header">
                <h5>Confirm Deletion</h5>
            </div>
            <div class="modal-body">
                <p id="deleteMessage"></p>
            </div>
            <div class="modal-footer">
                <button id="confirmDeleteBtn" class="btn-add">YES</button>
                <button class="btn-cancel" onclick="closeDeleteModal()">Cancel</button>
            </div>
        </div>
    </div>

    <!-- Hidden form for deleting vendors -->
    <form id="deleteVendorsForm" method="POST" action="ms_vendors.php" style="display:none;">
        <input type="hidden" name="delete_vendors" value="1">
        <input type="hidden" id="delete-vendor-ids" name="vendor_ids" value="">
    </form>

    <!-- ADD/EDIT VENDOR MODAL -->
    <div id="addVendorModal" class="custom-modal-overlay">
        <div class="custom-modal">
            <div class="modal-header">
                <h5 id="modalTitle">ADD VENDOR</h5>
            </div>
            <form id="vendorForm" method="POST" enctype="multipart/form-data">
            <input type="hidden" name="vendor_id" id="vendor_id">
                <div class="modal-body">
                    <div class="input-row">
                        <div class="form-group">
                            <label>Vendor Name</label>
                            <input name="name" id="name" required></input>
                        </div>
                        <div class="form-group">
                            <label>Vendor Type</label>
                            <input name="type" id="type" required></input>
                        </div>
                    </div>

                    <div class="input-row">
                        <div class="form-group">
                            <label>Contact Person</label>
                            <input name="contact_person" id="contact_person" required></input>
                        </div>
                        <div class="form-group">
                            <label>Contact No.</label>
                            <input type="number" name="contact_no" id="contact_no" required></input>
                        </div>
                    </div>

                    <div class="form-group full-width">
                        <label>Telephone No.</label>
                        <textarea name="telephone_no" id="telephone_no"></input>
                    </div>

                    <div class="form-group full-width">
                        <label>Address:</label>
                        <input name="address" id="address"></input>
                    </div>
                </div>

                <div class="modal-footer">
                    <button type="submit" class="btn-add" id="submitVendorBtn">ADD</button>
                    <button type="button" class="btn-cancel" onclick="closeModal()">CANCEL</button>
                </div>
            </form>
        </div>
    </div>
    
    <script>
        // Sidebar Toggle
        document.getElementById("toggleSidebar").addEventListener("click", function () {
            document.getElementById("sidebar").classList.toggle("collapsed");
        });

        // User Dropdown
        document.getElementById("userDropdownBtn").addEventListener("click", function (event) {
            event.stopPropagation();
            const dropdown = document.getElementById("userDropdownMenu");
            dropdown.style.display = (dropdown.style.display === "block") ? "none" : "block";
        });

        document.addEventListener("click", function () {
            document.getElementById("userDropdownMenu").style.display = "none";
        });

        // Store the currently selected vendor
        let currentVendor = null;

        function showVendorDetails(vendor) {
            currentVendor = vendor;

            document.getElementById('vendorPlaceholder').style.display = 'none';
            document.getElementById('vendorInfo').style.display = 'block';

            document.getElementById('vendorHeader').textContent = vendor.vendor_name || '';
            document.getElementById('vendorType').textContent = vendor.vendor_type || '';
            document.getElementById('vendorPerson').textContent = vendor.contact_person || '';
            document.getElementById('vendorEmail').textContent = vendor.vendor_email || '';
            document.getElementById('vendorContact').textContent = vendor.contact_no ? '0' + vendor.contact_no : '';
            document.getElementById('vendorTelephone').textContent = vendor.telephone ? '0' + vendor.telephone : '';
            document.getElementById('vendorAddress').textContent = vendor.vendor_address || '';

            document.getElementById('editButton').style.display = 'block';
        }

        document.querySelectorAll('.vendor-list tbody tr').forEach(row => {
            row.addEventListener('click', function (e) {
                if (e.target.matches('input[type="checkbox"]')) return;
                const vendorData = JSON.parse(this.dataset.json);
                showVendorDetails(vendorData);
            });
        });

        //ADD or EDIT Modals
        const modal = document.getElementById('addVendorModal');
        const modalTitle = document.getElementById('modalTitle');
        const submitBtn = document.getElementById('submitVendorBtn');
        const form = document.getElementById('vendorForm');

        // Open Add Modal
        document.getElementById('addVendorBtn').addEventListener('click', () => {
            modal.style.display = 'flex';
            modalTitle.textContent = 'ADD VENDOR';
            submitBtn.textContent = 'ADD';
            form.reset();
            document.getElementById('vendor_id').value = '';
        });

        // Open Edit Modal
        function openEditVendorModal() {
            if (!currentVendor) return;
            
            modal.style.display = 'flex';
            modalTitle.textContent = 'EDIT VENDOR';
            submitBtn.textContent = 'SAVE';

            document.getElementById('vendor_id').value = currentVendor.vendor_id;
            document.getElementById('vendor_name').value = currentVendor.vendor_name;
            document.getElementById('vendor_type').value = currentVendor.vendor_type;
            document.getElementById('contact_person').value = currentVendor.contact_person;
            document.getElementById('vendor_email').value = currentVendor.vendor_email;
            document.getElementById('contact_no').value = currentVendor.contact_no;
            document.getElementById('telephone').value = currentVendor.telephone;
            document.getElementById('vendor_address').value = currentVendor.vendor_address;
        }

        // Close Modal
        function closeModal() {
            modal.style.display = 'none';
        }

        // Close on outside click
        window.addEventListener('click', function (e) {
            if (e.target === modal) {
                closeModal();
            }
        });

        // DELETION Function
        let selectMode = false;
        const selectBtn = document.querySelector('.select-btn');
        const deleteBtn = document.querySelector('.delete-selected-btn');
        const rows = document.querySelectorAll('.vendor-list tbody tr');
        const deleteModal = document.getElementById('deleteModal');
        const deleteVendorIdsInput = document.getElementById('delete-vendor-ids');

        // Toggle Select Mode
        function toggleSelectMode(enable) {
            selectMode = enable;
            selectBtn.textContent = enable ? 'CANCEL' : 'SELECT';
            deleteBtn.style.display = 'none';

            rows.forEach(row => {
                const checkbox = row.querySelector('.row-checkbox');
                const number = row.querySelector('.row-number');

                if (enable) {
                    checkbox.style.display = 'inline-block';
                    number.style.display = 'none';
                } else {
                    checkbox.checked = false;
                    checkbox.style.display = 'none';
                    number.style.display = 'inline-block';
                }
            });

            // Bind click event to show vendor details only when select mode is not enabled
            rows.forEach(row => {
                row.onclick = enable
                    ? e => e.stopPropagation() // Prevent vendor details display in select mode
                    : () => showVendorDetails(JSON.parse(row.dataset.json)); // Display vendor details
            });
        }

        // Toggle button
        selectBtn.addEventListener('click', () => toggleSelectMode(!selectMode));

        // ESC key support
        document.addEventListener('keydown', e => {
            if (e.key === 'Escape' && selectMode) {
                toggleSelectMode(false);
            }
        });

        // Checkbox change
        rows.forEach(row => {
            row.onclick = enable
                ? e => e.stopPropagation() // Prevent vendor details display in select mode
                : e => {
                    // Prevent opening vendor details if the click is on a modal-triggering element (e.g., edit/delete buttons)
                    const target = e.target;
                    if (
                        target.closest('.edit-btn') || 
                        target.closest('.delete-btn') || 
                        target.closest('.row-checkbox')
                    ) {
                        return; // Do nothing if clicking on buttons or checkbox
                    }

                    showVendorDetails(JSON.parse(row.dataset.json));
                };
        });

        // Delete action
        deleteBtn.addEventListener('click', () => {
            const selected = [...document.querySelectorAll('.row-checkbox')]
                .filter(cb => cb.checked)
                .map(cb => cb.closest('tr').dataset.id);

            if (selected.length === 0) return;

            const confirmMsg = selected.length === 1
                ? 'Are you sure you want to delete this record?'
                : 'Are you sure you want to delete these records?';

            // Update message in the modal
            document.getElementById('deleteMessage').textContent = confirmMsg;

            // Pass selected IDs to the hidden input
            deleteVendorIdsInput.value = selected.join(',');

            // Show the modal
            deleteModal.style.display = 'block';
        });

        // Confirm deletion and submit the form
        document.getElementById('confirmDeleteBtn').addEventListener('click', function() {
            // Trigger form submission
            document.getElementById('deleteVendorsForm').submit();
        });

        // Close Modal
        function closeDeleteModal() {
            deleteModal.style.display = 'none';
        }

        // Close modal if user clicks outside
        window.onclick = function(event) {
            if (event.target == deleteModal) {
                closeDeleteModal();
            }
        };
    </script>
</body>
</html>