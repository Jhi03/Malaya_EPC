<?php
    include('validate_login.php');
    require_once 'activity_logger.php';
    
    $page_title = "VENDORS";

    // DATABASE CONNECTION
    $servername = "localhost";  
    $username = "u188693564_adminsolar";           
    $password = "@Malayasolarenergies1";            
    $dbname = "u188693564_malayasol";     

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
            $vendor_unit_bldg_no = $_POST['vendor_unit_bldg_no'];
            $vendor_street = $_POST['vendor_street'];
            $vendor_city = $_POST['vendor_city'];
            $vendor_country = $_POST['vendor_country'];
            $vendor_remarks = $_POST['vendor_remarks'];

            if ($vendor_id) {
                // UPDATE vendor
                $stmt = $conn->prepare("UPDATE vendors SET vendor_name=?, vendor_type=?, contact_person=?, vendor_email=?, contact_no=?, telephone=?, vendor_unit_bldg_no=?, vendor_street=?, vendor_city=?, vendor_country=?, vendor_remarks=? WHERE vendor_id=?");
                $stmt->bind_param("sssssssssssi", $vendor_name, $vendor_type, $contact_person, $vendor_email, $contact_no, $telephone, $vendor_unit_bldg_no, $vendor_street, $vendor_city, $vendor_country, $vendor_remarks, $vendor_id);
                logUserActivity(
                    'edit', 
                    'ms_vendors.php', 
                    "Edit record: {$vendor_id}",
                    $vendor_id
                );
            } else {
                // INSERT vendor
                $stmt = $conn->prepare("INSERT INTO vendors (vendor_name, vendor_type, contact_person, vendor_email, contact_no, telephone, vendor_unit_bldg_no, vendor_street, vendor_city, vendor_country, vendor_remarks) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
                $stmt->bind_param("sssssssssss", $vendor_name, $vendor_type, $contact_person, $vendor_email, $contact_no, $telephone, $vendor_unit_bldg_no, $vendor_street, $vendor_city, $vendor_country, $vendor_remarks);
                logUserActivity(
                    'add', 
                    'ms_vendor.php', 
                    "Add record {$vendor_id}",
                    $vendor_id
                );
            }

            if ($stmt->execute()) {
                echo "<script>window.location = 'ms_vendors.php';</script>";
                exit();
            } else {
                echo "Error: " . $stmt->error;
            }
        }

        // Handle vendor deletion
        if (isset($_POST['delete_vendors'])) {
            $vendor_ids = explode(',', $_POST['vendor_ids']);
            if (!empty($vendor_ids)) {
                foreach ($vendor_ids as $id) {
                    $id = intval($id);
                    $stmt = $conn->prepare("DELETE FROM vendors WHERE vendor_id = ?");
                    logUserActivity(
                        'delete', 
                        'ms_vendors.php', 
                        "delete record: {$vendor_id}",
                        $vendor_id
                    );
                    $stmt->bind_param("i", $id);
                    $stmt->execute();
                }
                echo "<script>window.location = 'ms_vendors.php';</script>";
                exit();
            }
        }
    }

    // Use prepared statement for vendor query to allow for search functionality
    $query = "SELECT * FROM vendors WHERE 1=1";
    $params = [];
    $types = "";

    // Get search term from URL if exists
    $search_term = isset($_GET['search']) ? trim($_GET['search']) : '';

    // Additional PHP for database query with search functionality
    if (!empty($search_term)) {
        // Query with search term
        $query = "SELECT * FROM vendors WHERE 
                vendor_name LIKE ? OR 
                contact_person LIKE ? OR 
                vendor_type LIKE ?
                ORDER BY vendor_id ASC";
        
        $stmt = $conn->prepare($query);
        $search_param = "%" . $search_term . "%";
        $stmt->bind_param("sss", $search_param, $search_param, $search_param);
    } else {
        // Regular query without search
        $query = "SELECT * FROM vendors ORDER BY vendor_id ASC";
        $stmt = $conn->prepare($query);
    }
    $stmt->execute();
    $result = $stmt->get_result();
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
    <link href="css/ms_vendorsdesign.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>

</head>
<body>
    <div class="sidebar" id="sidebar">
        <?php include 'sidebar.php'; ?>
    </div>
    
    <div class="content-area">
        <?php include 'header.php'; ?>
    <div class="content-body">

        <!-- Search Bar in search-filter-bar div -->
        <div class="search-filter-bar">
            <!-- Left group: Add, Search -->
            <div class="left-controls">
                <button class="add-record-btn" id="addVendorBtn">
                    <img src="icons/plus.svg" alt="Add" width="16"> ADD
                </button>

                <div class="search-container">
                    <input type="text" id="search-input" class="search-input" placeholder="Search vendors..." autocomplete="off">
                    <button type="button" class="clear-search-btn" id="clearSearchBtn">&times;</button>
                </div>
            </div>
        </div>

        <div class="vendor-content">
            <!-- Left Section: List of Vendors -->
            <div class="vendor-list">
                <?php if (mysqli_num_rows($result) > 0): ?>
                    <!-- Add this header section -->
                    <div class="vendor-list-header">
                        <h4>Vendors</h4>
                        <span class="vendor-count"><?= mysqli_num_rows($result) ?> vendors</span>
                    </div>
                    
                    <div class="vendor-list-table-container">
                        <table class="table">
                            <thead>
                                <tr>
                                    <th></th>
                                    <th>Vendor Name</th>
                                    <th>Type</th>
                                    <th>POC</th>
                                    <th>Contact</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php 
                                $counter = 1;
                                while ($row = mysqli_fetch_assoc($result)): 
                                ?>
                                    <tr data-id="<?= $row['vendor_id'] ?>" data-json='<?= json_encode($row) ?>'>
                                        <td class="row-index-cell">
                                            <span class="row-number"><?= $counter++ ?></span>
                                            <input type="checkbox" class="row-checkbox">
                                        </td>
                                        <td><?= htmlspecialchars($row['vendor_name']) ?></td>
                                        <td><?= htmlspecialchars($row['vendor_type']) ?></td>
                                        <td><?= htmlspecialchars($row['contact_person']) ?></td>
                                        <td><?= htmlspecialchars($row['contact_no']) ?></td>
                                    </tr>
                                <?php endwhile; ?>
                            </tbody>
                        </table>

                        <!-- Add this div after your table for "no results" message -->
                        <div class="no-results-message" id="noResultsMessage">
                            <p>No vendors found matching your search.</p>
                        </div>
                    </div>
                    
                    <!-- Add this footer section -->
                    <div class="vendor-list-footer">
                        <div class="footer-right">  
                            <button class="delete-selected-btn">
                                <img src="icons/trash.svg" alt="TrashIcon" width="20">
                            </button>
                            <button class="select-btn" id="selectBtn">SELECT</button>
                        </div>
                    </div>
                <?php else: ?>
                    <?php if (!empty($search_term)): ?>
                        <div class="no-results">
                            <p>No vendors found matching "<strong><?= htmlspecialchars($search_term) ?></strong>"</p>
                            <a href="ms_vendors.php" class="btn btn-sm btn-outline-secondary mt-2">Clear Search</a>
                        </div>
                    <?php else: ?>
                        <div class="no-results">
                            <p>There are no existing vendors.</p>
                            <button class="btn btn-sm btn-primary mt-2" id="addVendorBtnEmpty">Add Your First Vendor</button>
                        </div>
                    <?php endif; ?>
                <?php endif; ?>
            </div>


            <!-- Right Section: Vendor Details -->
            <div class="vendor-details" id="vendorDetails">
                <!-- Placeholder shown when no vendor selected -->
                <div class="vendor-placeholder" id="vendorPlaceholder">
                    <h4>Select a Vendor</h4>
                    <p>Click on a vendor from the list to view details</p>
                </div>

                <!-- Vendor Info shown when a vendor is selected -->
                <div class="vendor-info" id="vendorInfo">
                    <!-- Vendor content wrapper -->
                    <div class="vendor-details-content">
                        <!-- Vendor Header -->
                        <div class="vendor-header">
                            <h3 id="vendorHeader"></h3>
                            <span class="vendor-type-badge" id="vendorTypeBadge"></span>
                        </div>

                        <!-- Vendor Details Grid -->
                        <div class="vendor-details-grid">
                            <!-- Contact Info Section -->
                            <div class="detail-section">
                                <h4>Contact Information</h4>
                                <div class="detail-item">
                                    <div class="detail-label">Contact Person:</div>
                                    <div class="detail-value" id="contactPerson"></div>
                                </div>
                                <div class="detail-item">
                                    <div class="detail-label">Contact No.:</div>
                                    <div class="detail-value" id="contactNo"></div>
                                </div>
                                <div class="detail-item">
                                    <div class="detail-label">Telephone:</div>
                                    <div class="detail-value" id="telephoneDisplay"></div>
                                </div>
                                <div class="detail-item">
                                    <div class="detail-label">Email:</div>
                                    <div class="detail-value" id="vendorEmail"></div>
                                </div>
                            </div>
                            
                            <!-- Address Section -->
                            <div class="detail-section">
                                <h4>Address Information</h4>
                                <div class="detail-item">
                                    <div class="detail-label">Building/Unit:</div>
                                    <div class="detail-value" id="vendorUnit"></div>
                                </div>
                                <div class="detail-item">
                                    <div class="detail-label">Street:</div>
                                    <div class="detail-value" id="vendorStreet"></div>
                                </div>
                                <div class="detail-item">
                                    <div class="detail-label">City:</div>
                                    <div class="detail-value" id="vendorCity"></div>
                                </div>
                                <div class="detail-item">
                                    <div class="detail-label">Country:</div>
                                    <div class="detail-value" id="vendorCountry"></div>
                                </div>
                            </div>
                            
                            <!-- Remarks Section -->
                            <div class="remarks-section">
                                <h4>Remarks</h4>
                                <div class="remarks-content" id="vendorRemarks"></div>
                            </div>
                        </div>
                    </div>

                    <!-- THIS IS THE CRITICAL PART - ADD THIS FOOTER -->
                        <div class="vendor-details-footer">
                            <button class="vendor-edit-btn" onclick="openEditVendorModal(currentVendor)">
                                Edit <img src="icons/edit.svg" width="16">
                            </button>
                        </div>
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
                <button type="button" class="close-button" onclick="closeDeleteModal()">&times;</button>
            </div>
            <div class="modal-body">
                <p id="deleteMessage"></p>
            </div>
            <div class="modal-footer">
                <button id="confirmDeleteBtn" class="btn-add">DELETE</button>
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
    <div class="modal fade" id="vendorModal" tabindex="-1" aria-labelledby="vendorModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="vendorModalLabel">Add Vendor</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                
                <form id="vendorForm" method="POST" action="ms_vendors.php">
                    <input type="hidden" name="vendor_id" id="vendor_id">
                    
                    <div class="modal-body">
                        <div class="form-section">
                            <div class="form-section-title">Vendor Information</div>
                            <div class="row g-3">
                                <div class="col-md-6">
                                    <label for="vendor_name" class="form-label">Vendor Name</label>
                                    <input type="text" class="form-control" id="vendor_name" name="vendor_name" required>
                                </div>
                                <div class="col-md-6">
                                    <label for="vendor_type" class="form-label">Vendor Type</label>
                                    <input type="text" class="form-control" id="vendor_type" name="vendor_type" required>
                                </div>
                            </div>
                        </div>
                        
                        <div class="form-section">
                            <div class="form-section-title">Contact Information</div>
                            <div class="row g-3">
                                <div class="col-md-6">
                                    <label for="contact_person" class="form-label">Contact Person</label>
                                    <input type="text" class="form-control" id="contact_person" name="contact_person" required>
                                </div>
                                <div class="col-md-6">
                                    <label for="contact_no" class="form-label">Contact Number</label>
                                    <div class="input-group">
                                        <span class="input-group-text">0</span>
                                        <input type="text" class="form-control" id="contact_no" name="contact_no" required maxlength="10" placeholder="9XX XXX XXXX">
                                    </div>
                                    <small class="text-muted">10 digits only (excluding the leading 0)</small>
                                </div>
                                <div class="col-md-6">
                                    <label for="telephone" class="form-label">Telephone (Optional)</label>
                                    <input type="text" class="form-control" id="telephone" name="telephone" placeholder="02 XXX XXXX">
                                </div>
                                <div class="col-md-6">
                                    <label for="vendor_email" class="form-label">Email Address</label>
                                    <input type="email" class="form-control" id="vendor_email" name="vendor_email" placeholder="email@example.com">
                                </div>
                            </div>
                        </div>
                        
                        <div class="form-section">
                            <div class="form-section-title">Address Information</div>
                            <div class="row g-3">
                                <div class="col-md-6">
                                    <label for="vendor_unit_bldg_no" class="form-label">Unit/Building No.</label>
                                    <input type="text" class="form-control" id="vendor_unit_bldg_no" name="vendor_unit_bldg_no" placeholder="Unit/Building Number">
                                </div>
                                <div class="col-md-6">
                                    <label for="vendor_street" class="form-label">Street</label>
                                    <input type="text" class="form-control" id="vendor_street" name="vendor_street" placeholder="Street">
                                </div>
                                <div class="col-md-6">
                                    <label for="vendor_city" class="form-label">City</label>
                                    <input type="text" class="form-control" id="vendor_city" name="vendor_city" placeholder="City">
                                </div>
                                <div class="col-md-6">
                                    <label for="vendor_country" class="form-label">Country</label>
                                    <input type="text" class="form-control" id="vendor_country" name="vendor_country" placeholder="Country">
                                </div>
                            </div>
                        </div>
                        
                        <div class="form-section">
                            <div class="form-section-title">Additional Information</div>
                            <div class="row g-3">
                                <div class="col-md-12">
                                    <label for="vendor_remarks" class="form-label">Remarks</label>
                                    <textarea class="form-control" id="vendor_remarks" name="vendor_remarks" rows="3" placeholder="Add any additional information about this vendor"></textarea>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-primary" id="submitVendorBtn">Save</button>
                    </div>
                </form>
            </div>
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

            // Header and type badge
            document.getElementById('vendorHeader').textContent = vendor.vendor_name || '';
            document.getElementById('vendorTypeBadge').textContent = vendor.vendor_type || '';
            
            // Contact information
            document.getElementById('contactPerson').textContent = vendor.contact_person || 'N/A';
            document.getElementById('contactNo').textContent = vendor.contact_no ? '0' + vendor.contact_no : 'N/A';
            document.getElementById('telephoneDisplay').textContent = vendor.telephone || 'N/A';
            document.getElementById('vendorEmail').textContent = vendor.vendor_email || 'N/A';
            
            // Address information
            document.getElementById('vendorUnit').textContent = vendor.vendor_unit_bldg_no || 'N/A';
            document.getElementById('vendorStreet').textContent = vendor.vendor_street || 'N/A';
            document.getElementById('vendorCity').textContent = vendor.vendor_city || 'N/A';
            document.getElementById('vendorCountry').textContent = vendor.vendor_country || 'N/A';
            
            // Remarks
            document.getElementById('vendorRemarks').textContent = vendor.vendor_remarks || 'No remarks available';
        }

        // Click event for vendor rows
        document.querySelectorAll('.vendor-list tbody tr').forEach(row => {
            row.addEventListener('click', function (e) {
                if (e.target && e.target.matches('input[type="checkbox"]')) return;

                // Remove active class from all rows
                document.querySelectorAll('.vendor-list tbody tr').forEach(r => r.classList.remove('active-row'));
                
                // Add active class to clicked row
                this.classList.add('active-row');

                const vendorData = JSON.parse(this.dataset.json);
                showVendorDetails(vendorData);
            });
        });

        // Add/Edit Vendor Modal Handler
        document.getElementById('addVendorBtn').addEventListener('click', function() {
            // Reset form
            document.getElementById('vendorForm').reset();
            document.getElementById('vendor_id').value = '';
            
            // Update modal title and button
            document.getElementById('vendorModalLabel').textContent = 'Add Vendor';
            document.getElementById('submitVendorBtn').textContent = 'Add';
            
            // Show modal
            const vendorModal = new bootstrap.Modal(document.getElementById('vendorModal'));
            vendorModal.show();
        });

        // If there's an "addVendorBtnEmpty" element for empty state
        const emptyStateAddBtn = document.getElementById('addVendorBtnEmpty');
        if (emptyStateAddBtn) {
            emptyStateAddBtn.addEventListener('click', function() {
                // Reset form
                document.getElementById('vendorForm').reset();
                document.getElementById('vendor_id').value = '';
                
                // Update modal title and button
                document.getElementById('vendorModalLabel').textContent = 'Add Vendor';
                document.getElementById('submitVendorBtn').textContent = 'Add';
                
                // Show modal
                const vendorModal = new bootstrap.Modal(document.getElementById('vendorModal'));
                vendorModal.show();
            });
        }

        // Open Edit Vendor Modal
        function openEditVendorModal(vendorData) {
            // Fill form with data
            document.getElementById('vendor_id').value = vendorData.vendor_id;
            document.getElementById('vendor_name').value = vendorData.vendor_name || '';
            document.getElementById('vendor_type').value = vendorData.vendor_type || '';
            document.getElementById('contact_person').value = vendorData.contact_person || '';
            document.getElementById('contact_no').value = vendorData.contact_no || '';
            document.getElementById('telephone').value = vendorData.telephone || '';
            document.getElementById('vendor_email').value = vendorData.vendor_email || '';
            document.getElementById('vendor_unit_bldg_no').value = vendorData.vendor_unit_bldg_no || '';
            document.getElementById('vendor_street').value = vendorData.vendor_street || '';
            document.getElementById('vendor_city').value = vendorData.vendor_city || '';
            document.getElementById('vendor_country').value = vendorData.vendor_country || '';
document.getElementById('vendor_remarks').value = vendorData.vendor_remarks || '';
            
            // Update modal title and button
            document.getElementById('vendorModalLabel').textContent = 'Edit Vendor';
            document.getElementById('submitVendorBtn').textContent = 'Update';
            
            // Show modal
            const vendorModal = new bootstrap.Modal(document.getElementById('vendorModal'));
            vendorModal.show();
        }

        // Contact number validation - only allow numbers and limit to 10 digits (excluding the leading 0)
        document.getElementById('contact_no').addEventListener('input', function() {
            this.value = this.value.replace(/[^0-9]/g, '').slice(0, 10);
        });

        // Telephone validation - only allow numbers
        document.getElementById('telephone').addEventListener('input', function() {
            this.value = this.value.replace(/[^0-9]/g, '');
        });

        // Deletion Select Mode
        let selectMode = false;
        const selectBtn = document.getElementById('selectBtn');
        const deleteBtn = document.querySelector('.delete-selected-btn');
        const rows = document.querySelectorAll('.vendor-list tbody tr');
        const deleteModal = document.getElementById('deleteModal');
        const deleteVendorIdsInput = document.getElementById('delete-vendor-ids');

        selectBtn.addEventListener('click', () => toggleSelectMode(!selectMode));

        function toggleSelectMode(enable) {
            selectMode = enable;
            selectBtn.textContent = enable ? 'CANCEL' : 'SELECT';
            selectBtn.classList.toggle('active', enable);
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

            // Add event listener for checkbox clicks
            if (enable) {
                document.querySelector('.vendor-list').addEventListener('click', checkboxClickHandler);
            } else {
                document.querySelector('.vendor-list').removeEventListener('click', checkboxClickHandler);
            }

            // ESC key exits select mode
            if (enable) {
                document.addEventListener('keydown', escKeyHandler);
            } else {
                document.removeEventListener('keydown', escKeyHandler);
            }
        }
        
        // Handler for checkbox clicks
        function checkboxClickHandler(e) {
            if (e.target.matches('.row-checkbox')) {
                // Prevent the row click handler from firing
                e.stopPropagation();
                
                const checkedCount = document.querySelectorAll('.vendor-list tbody .row-checkbox:checked').length;
                deleteBtn.style.display = checkedCount > 0 ? 'block' : 'none';
            }
        }
        
        // Handler for ESC key
        function escKeyHandler(e) {
            if (e.key === "Escape" && selectMode) {
                toggleSelectMode(false);
            }
        }

        // Open Delete Confirmation
        deleteBtn.addEventListener('click', () => {
            const selectedIds = Array.from(document.querySelectorAll('.vendor-list tbody .row-checkbox:checked'))
                .map(cb => cb.closest('tr').dataset.id);
            if (selectedIds.length > 0) {
                document.getElementById('deleteMessage').textContent = `Are you sure you want to delete ${selectedIds.length} selected vendor(s)?`;
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

        document.addEventListener('DOMContentLoaded', function() {
            const searchInput = document.getElementById('search-input');
            const clearBtn = document.getElementById('clearSearchBtn');
            const resultsCounter = document.getElementById('resultsCounter');
            const noResultsMessage = document.getElementById('noResultsMessage');
            const tableRows = document.querySelectorAll('.vendor-list tbody tr');
            
            // Function to filter table rows based on search input
            function filterVendors() {
                const searchTerm = searchInput.value.toLowerCase().trim();
                let matchCount = 0;
                
                // Show/hide clear button
                clearBtn.style.display = searchTerm ? 'flex' : 'none';
                
                // Process each table row
                tableRows.forEach(row => {
                    // Get vendor data from the row's data attributes
                    const vendorData = JSON.parse(row.dataset.json);
                    
                    // Fields to search in
                    const vendorName = (vendorData.vendor_name || '').toLowerCase();
                    const contactPerson = (vendorData.contact_person || '').toLowerCase();
                    const vendorType = (vendorData.vendor_type || '').toLowerCase();
                    
                    // Check if any field contains the search term
                    const isMatch = 
                        vendorName.includes(searchTerm) || 
                        contactPerson.includes(searchTerm) || 
                        vendorType.includes(searchTerm);
                    
                    // Show/hide the row
                    if (!searchTerm || isMatch) {
                        row.classList.remove('hidden-row');
                        matchCount++;
                    } else {
                        row.classList.add('hidden-row');
                    }
                });
                
                // Show/hide no results message
                if (noResultsMessage) {
                    noResultsMessage.style.display = (searchTerm && matchCount === 0) ? 'block' : 'none';
                }
            }
            
            // Event listeners
            searchInput.addEventListener('input', filterVendors);
            
            // Clear search button
            if (clearBtn) {
                clearBtn.addEventListener('click', function() {
                    searchInput.value = '';
                    filterVendors();
                    searchInput.focus();
                });
            }
            
            // Clear search on ESC key
            searchInput.addEventListener('keydown', function(e) {
                if (e.key === 'Escape') {
                    searchInput.value = '';
                    filterVendors();
                }
            });
            
            // Initialize on page load
            filterVendors();
            
            // Highlight matching text in the row
            function highlightText(row, searchTerm) {
                // First remove any existing highlights
                removeHighlights(row);
                
                // Get all text nodes in the row
                const textElements = row.querySelectorAll('td:not(.row-index-cell)');
                
                textElements.forEach(element => {
                    const originalText = element.textContent;
                    const lowerText = originalText.toLowerCase();
                    
                    // Skip if this cell doesn't contain the search term
                    if (!lowerText.includes(searchTerm)) return;
                    
                    // Create highlighted HTML
                    let html = '';
                    let lastIndex = 0;
                    let searchTermLower = searchTerm.toLowerCase();
                    
                    // Find all occurrences and mark them
                    let index = lowerText.indexOf(searchTermLower);
                    while (index >= 0) {
                        // Add text before match
                        html += originalText.substring(lastIndex, index);
                        
                        // Add highlighted match
                        html += `<span class="highlight">${originalText.substring(index, index + searchTerm.length)}</span>`;
                        
                        // Move to next potential match
                        lastIndex = index + searchTerm.length;
                        index = lowerText.indexOf(searchTermLower, lastIndex);
                    }
                    
                    // Add remaining text
                    html += originalText.substring(lastIndex);
                    
                    // Set the HTML content
                    element.innerHTML = html;
                });
            }
            
            // Remove highlights from a row
            function removeHighlights(row) {
                const textElements = row.querySelectorAll('td:not(.row-index-cell)');
                
                textElements.forEach(element => {
                    const hasHighlight = element.querySelector('.highlight');
                    if (hasHighlight) {
                        element.textContent = element.textContent;
                    }
                });
            }
            
            // Event listeners
            searchInput.addEventListener('input', filterVendors);
            
            // Clear search button
            clearBtn.addEventListener('click', function() {
                searchInput.value = '';
                filterVendors();
                searchInput.focus();
            });
            
            // Clear search on ESC key
            searchInput.addEventListener('keydown', function(e) {
                if (e.key === 'Escape') {
                    searchInput.value = '';
                    filterVendors();
                }
            });
            
            // Initialize on page load
            filterVendors();
        });
    </script>
</body>
</html>

<!--
NOTES: 
    05-22-25
    CHANGES:
    - Updated search bar to match the reference files
    - Removed analytics, record, sort by, and filter buttons
    - Enhanced search functionality to search by vendor_name, vendor_type, and contact_person
    - Added server-side search with URL parameters
    - Added search icon to search input
    - Improved select button styling and interaction
    - Fixed select mode functionality
    - Improved search input styling
    - Added "No results" message when search returns no vendors
    - Added clear search button when search is active
    - Improved responsive design for the search filter bar
    - Added input group for contact number with leading 0
    - Fixed search form submission behavior
    - Improved table hover styling
-->            