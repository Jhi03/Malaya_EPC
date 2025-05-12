<?php
    include('validate_login.php');
    $page_title = "VENDORS";

    // DATABASE CONNECTION
    $servername = "localhost";  
    $username = "root";           
    $password = "";            
    $dbname = "malayasol";     

    // Create connection
    $conn = new mysqli($servername, $username, $password, $dbname);

    // Check connection
    if ($conn->connect_error) {
        die("Connection failed: " . $conn->connect_error);
    }

    // Handle Add/Edit/Delete
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        if (isset($_POST['vendor_name'])) {
            $vendor_id = $_POST['vendor_id'];
            $vendor_name = $_POST['vendor_name'];
            $vendor_type = $_POST['vendor_type'];
            $contact_person = $_POST['contact_person'];
            $vendor_email = $_POST['vendor_email'];
            $contact_no = $_POST['contact_no'];
            $telephone = $_POST['telephone'];
            $vendor_address = $_POST['vendor_address'];

            if ($vendor_id) {
                // UPDATE vendor
                $stmt = $conn->prepare("UPDATE vendors SET vendor_name=?, vendor_type=?, contact_person=?, vendor_email=?, contact_no=?, telephone=?, vendor_address=? WHERE vendor_id=?");
                $stmt->bind_param("ssssiisi", $vendor_name, $vendor_type, $contact_person, $vendor_email, $contact_no, $telephone, $vendor_address, $vendor_id);
            } else {
                // INSERT vendor
                $stmt = $conn->prepare("INSERT INTO vendors (vendor_name, vendor_type, contact_person, vendor_email, contact_no, telephone, vendor_address) VALUES (?, ?, ?, ?, ?, ?, ?)");
                $stmt->bind_param("ssssiis", $vendor_name, $vendor_type, $contact_person, $vendor_email, $contact_no, $telephone, $vendor_address);
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
    <title>Malaya Solar Energies Inc.</title>
    <link rel="icon" href="images/Malaya_Logo.png" type="image/png">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Atkinson+Hyperlegible&display=swap" rel="stylesheet">
    <link href="css/ms_vendors.css" rel="stylesheet">
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
                <div class="vendor-list-header">Vendors</div>

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
                                        <td><?= htmlspecialchars($row['contact_no']) ?></td>
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
            <div class="vendor-details" id="vendorDetails" style="position: relative;">
                <div id="vendorPlaceholder">
                    <h4 style="text-align: center;">Select a Vendor</h4>
                </div>

                <div id="vendorInfo" style="display: none;">
                    <!-- Vendor Header -->
                    <h4 id="vendorHeader" style="text-align: center; margin: 10px;"></h4>
                    <hr style="width: 95%; margin: 0 auto 40px auto; border: 1px solid #ccc;">

                    <!-- Vendor Details List -->
                    <div class="vendor-details-info">
                        <p><strong>Vendor Type:</strong> <span id="vendorType"></span></p>
                        <p><strong>Contact Person:</strong> <span id="contactPerson"></span></p>
                        <p><strong>Contact No.:</strong> <span id="contactNo"></span></p>
                        <p><strong>Telephone No.:</strong> <span id="telephone"></span></p>
                        <p><strong>Email Address:</strong> <span id="vendorEmail"></span></p>
                        <p><strong>Address:</strong> <span id="vendorAddress"></span></p>
                    </div>

                    <!-- Edit Button (bottom-right corner) -->
                    <div class="edit-button" id="editVendorButton" style="position: absolute; bottom: 20px; right: 20px;">
                        <button class="btn btn-primary" onclick="openEditVendorModal(currentVendor)">
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
                <h5 id="vendorModalTitle">ADD VENDOR</h5>
            </div>
            <form id="vendorForm" method="POST" action="ms_vendors.php">
                <input type="hidden" name="vendor_id" id="vendor_id">
                <div class="modal-body">

                    <div class="input-row">
                        <div class="form-group">
                            <label>Vendor Name</label>
                            <input type="text" name="vendor_name" id="vendor_name" required>
                        </div>
                        <div class="form-group">
                            <label>Vendor Type</label>
                            <input type="text" name="vendor_type" id="vendor_type" required>
                        </div>
                    </div>

                    <div class="input-row">
                        <div class="form-group">
                            <label>Contact Person</label>
                            <input type="text" name="contact_person" id="contact_person" required>
                        </div>
                        <div class="form-group">
                            <label>Contact No.</label>
                            <input type="number" name="contact_no" id="contact_no" required>
                        </div>
                    </div>

                    <div class="form-group full-width">
                        <label>Email</label>
                        <input type="email" name="vendor_email" id="vendor_email">
                    </div>

                    <div class="form-group full-width">
                        <label>Telephone</label>
                        <input type="number" name="telephone" id="telephone">
                    </div>

                    <div class="form-group full-width">
                        <label>Address</label>
                        <textarea name="vendor_address" id="vendor_address" rows="3" maxlength="200" required></textarea>
                    </div>

                </div>
                <div class="modal-footer">
                    <button type="submit" class="btn-add" id="submitVendorBtn">ADD</button>
                    <button type="button" class="btn-cancel" onclick="closeVendorModal()">CANCEL</button>
                </div>
            </form>
        </div>
    </div>

    <script src="js/sidebar.js"></script>
    <script src="js/header.js"></script>

    <script>
        // Store currently selected vendor
        let currentVendor = null;

        // Show Vendor Details
        function showVendorDetails(vendor) {
            currentVendor = vendor;

            document.getElementById('vendorPlaceholder').style.display = 'none';
            document.getElementById('vendorInfo').style.display = 'block';

            document.getElementById('vendorHeader').textContent = vendor.vendor_name || '';
            document.getElementById('vendorType').textContent = vendor.vendor_type || '';
            document.getElementById('contactPerson').textContent = vendor.contact_person || '';
            document.getElementById('contactNo').textContent = vendor.contact_no || '';
            document.getElementById('telephone').textContent = vendor.telephone || '';
            document.getElementById('vendorEmail').textContent = vendor.vendor_email || '';
            document.getElementById('vendorAddress').textContent = vendor.vendor_address || '';

            document.getElementById('editVendorButton').style.display = 'block';
        }

        // Click event for vendor rows
        document.querySelectorAll('.vendor-list tbody tr').forEach(row => {
            row.addEventListener('click', function (e) {
                if (e.target && e.target.matches('input[type="checkbox"]')) return;

                const vendorData = JSON.parse(this.dataset.json);
                showVendorDetails(vendorData);
            });
        });

        // Vendor modal elements
        const vendorModal = document.getElementById('addVendorModal');
        const vendorForm = document.getElementById('vendorForm');
        const vendorModalTitle = document.getElementById('vendorModalTitle');
        const submitVendorBtn = document.getElementById('submitVendorBtn');

        // Open Add Vendor Modal
        document.getElementById('addVendorBtn').addEventListener('click', () => {
            vendorModal.style.display = 'flex';
            vendorModalTitle.textContent = 'ADD VENDOR';
            submitVendorBtn.textContent = 'ADD';
            vendorForm.reset();
            document.getElementById('vendor_id').value = '';
        });

        // Open Edit Vendor Modal
        function openEditVendorModal(vendorData) {
            vendorModal.style.display = 'flex';
            vendorModalTitle.textContent = 'EDIT VENDOR';
            submitVendorBtn.textContent = 'SAVE';

            document.getElementById('vendor_id').value = vendorData.vendor_id;
            document.getElementById('vendor_name').value = vendorData.vendor_name;
            document.getElementById('vendor_type').value = vendorData.vendor_type;
            document.getElementById('contact_person').value = vendorData.contact_person;
            document.getElementById('vendor_email').value = vendorData.vendor_email || '';
            document.getElementById('contact_no').value = vendorData.contact_no;
            document.getElementById('telephone').value = vendorData.telephone || '';
            document.getElementById('vendor_address').value = vendorData.vendor_address;
        }

        // Close Vendor Modal
        function closeVendorModal() {
            vendorModal.style.display = 'none';
        }

        // Close on outside click
        window.addEventListener('click', function (e) {
            if (e.target === vendorModal) {
                closeVendorModal();
            }
        });

        // Deletion Select Mode
        let selectMode = false;
        const selectBtn = document.querySelector('.select-btn');
        const deleteBtn = document.querySelector('.delete-selected-btn');
        const rows = document.querySelectorAll('.vendor-list tbody tr');
        const deleteModal = document.getElementById('deleteModal');
        const deleteVendorIdsInput = document.getElementById('delete-vendor-ids');

        selectBtn.addEventListener('click', () => toggleSelectMode(!selectMode));

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

                row.addEventListener('click', function (e) {
                    if (selectMode && e.target.matches('input[type="checkbox"]')) {
                        const checkedCount = document.querySelectorAll('.vendor-list tbody .row-checkbox:checked').length;
                        deleteBtn.style.display = checkedCount > 0 ? 'inline-block' : 'none';
                    }
                });
            });

            // ESC key exits select mode
            document.addEventListener('keydown', function escHandler(e) {
                if (e.key === "Escape" && selectMode) {
                    toggleSelectMode(false);
                    document.removeEventListener('keydown', escHandler);
                }
            });
        }

        // Open Delete Confirmation
        deleteBtn.addEventListener('click', () => {
            const selectedIds = Array.from(document.querySelectorAll('.vendor-list tbody .row-checkbox:checked'))
                .map(cb => cb.closest('tr').dataset.id);
            if (selectedIds.length > 0) {
                document.getElementById('deleteMessage').textContent = `Delete ${selectedIds.length} selected vendor(s)?`;
                deleteVendorIdsInput.value = selectedIds.join(',');
                deleteModal.style.display = 'flex';
            }
        });

        // Confirm Delete
        document.getElementById('confirmDeleteBtn').addEventListener('click', () => {
            document.getElementById('deleteVendorsForm').submit();
        });

        // Close Delete Modal
        function closeDeleteModal() {
            deleteModal.style.display = 'none';
        }
    </script>
</body>
</html>

<!--
NOTES: 
    05-05-25
    CHANGES:
    - add vendor: working
    - select: working
    - delete: working
    - edit: working

    TO BE WORKED ON:
    - vendor details: N/A for telephone if null
    - vendor details: concat 0 at beginning of contact no.
    - vendor list: concat 0 at beginning of contact no.
    - form modal: limit contact number entry to 11 digits
-->