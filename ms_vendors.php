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
            $vendor_unit_bldg_no = $_POST['vendor_unit_bldg_no'];
            $vendor_street = $_POST['vendor_street'];
            $vendor_city = $_POST['vendor_city'];
            $vendor_country = $_POST['vendor_country'];
            $vendor_remarks = $_POST['vendor_remarks'];

            if ($vendor_id) {
                // UPDATE vendor
                $stmt = $conn->prepare("UPDATE vendors SET vendor_name=?, vendor_type=?, contact_person=?, vendor_email=?, contact_no=?, telephone=?, vendor_unit_bldg_no=?, vendor_street=?, vendor_city=?, vendor_country=?, vendor_remarks=? WHERE vendor_id=?");
                $stmt->bind_param("sssssssssssi", $vendor_name, $vendor_type, $contact_person, $vendor_email, $contact_no, $telephone, $vendor_unit_bldg_no, $vendor_street, $vendor_city, $vendor_country, $vendor_remarks, $vendor_id);
            } else {
                // INSERT vendor
                $stmt = $conn->prepare("INSERT INTO vendors (vendor_name, vendor_type, contact_person, vendor_email, contact_no, telephone, vendor_unit_bldg_no, vendor_street, vendor_city, vendor_country, vendor_remarks) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
                $stmt->bind_param("sssssssssss", $vendor_name, $vendor_type, $contact_person, $vendor_email, $contact_no, $telephone, $vendor_unit_bldg_no, $vendor_street, $vendor_city, $vendor_country, $vendor_remarks);
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
                    $stmt->bind_param("i", $id);
                    $stmt->execute();
                    
                    // Log the deletion (for auditing)
                    $log_sql = "INSERT INTO user_activity_log (user_id, action, page, details) VALUES (?, 'DELETE', 'ms_vendors.php', ?)";
                    $log_stmt = $conn->prepare($log_sql);
                    $user_id = $_SESSION['user_id'];
                    $details = "Deleted vendor ID: $id";
                    $log_stmt->bind_param("is", $user_id, $details);
                    $log_stmt->execute();
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
    <style>
        /* Additional styling for form sections */
        .form-section {
            margin-bottom: 20px;
            padding: 15px;
            background-color: #f9f9f9;
            border-radius: 8px;
            box-shadow: 0 1px 3px rgba(0,0,0,0.05);
        }
        
        .form-section-title {
            font-weight: 600;
            margin-bottom: 15px;
            color: #333;
            border-bottom: 1px solid #ddd;
            padding-bottom: 5px;
        }
        
        .form-label {
            font-weight: 500;
            color: #555;
        }
        
        .form-control {
            border-radius: 6px;
            border: 1px solid #ddd;
            padding: 8px 12px;
            transition: all 0.2s ease;
        }
        
        .form-control:focus {
            border-color: #facc15;
            box-shadow: 0 0 0 0.2rem rgba(250, 204, 21, 0.25);
        }
        
        .modal-header {
            background-color: #f8f9fa;
            border-bottom: 1px solid #eaeaea;
        }
        
        .modal-footer {
            background-color: #f8f9fa;
            border-top: 1px solid #eaeaea;
        }
        
        .modal-title {
            font-weight: 600;
            color: #333;
        }
        
        .btn-primary {
            background: linear-gradient(135deg, #facc15, #eab308);
            border-color: #eab308;
        }
        
        .btn-primary:hover {
            background: linear-gradient(135deg, #eab308, #d97706);
            border-color: #d97706;
        }
        
        /* Table hover effect */
        .table tbody tr:hover {
            background-color: #f1f5f9;
        }
        
        /* New search-filter-bar styling based on the provided files */
        .search-filter-bar {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 16px 24px;
            background-color: #fff;
            border-radius: 16px;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.05);
            border: 1px solid #eee;
            margin: 20px 0;
        }

        .left-controls {
            display: flex;
            align-items: center;
            gap: 16px;
            flex-wrap: wrap;
            padding: 8px 0;
        }

        .add-record-btn {
            background: linear-gradient(135deg, #facc15, #eab308);
            color: #fff;
            border: none;
            padding: 10px 24px;
            border-radius: 9999px; /* Fully rounded pill */
            font-size: 14px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.2s ease;
            text-transform: uppercase;
            box-shadow: 0 4px 10px rgba(234, 179, 8, 0.3);
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .add-record-btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 15px rgba(234, 179, 8, 0.4);
        }

        /* Enhanced search input styling */
        .search-container {
            position: relative;
            max-width: 300px;
            width: 100%;
        }

        .search-input {
            width: 100%;
            padding: 10px 40px;
            border: 1px solid #e0e0e0;
            border-radius: 30px;
            font-size: 14px;
            background-color: #f9fafb;
            transition: all 0.2s ease-in-out;
            box-shadow: inset 0 1px 2px rgba(0, 0, 0, 0.03);
        }

        .search-input:focus {
            border-color: #facc15;
            background-color: #fff;
            outline: none;
            box-shadow: 0 0 0 3px rgba(250, 204, 21, 0.2);
        }

        .search-icon {
            position: absolute;
            left: 15px;
            top: 50%;
            transform: translateY(-50%);
            width: 16px;
            height: 16px;
            pointer-events: none;
            opacity: 0.6;
        }

        .clear-search-btn {
            position: absolute;
            right: 12px;
            top: 50%;
            transform: translateY(-50%);
            width: 20px;
            height: 20px;
            background: rgba(107, 114, 128, 0.2);
            color: #4b5563;
            border: none;
            border-radius: 50%;
            font-size: 16px;
            line-height: 1;
            cursor: pointer;
            display: flex;
            align-items: center;
            justify-content: center;
            transition: all 0.2s ease;
        }

        .clear-search-btn:hover {
            background: rgba(107, 114, 128, 0.3);
            color: #1f2937;
        }

        /* No results message styling */
        .no-results-message {
            display: none;
            padding: 30px;
            text-align: center;
            margin: 20px 0;
            background-color: #f9fafb;
            border-radius: 8px;
            color: #6b7280;
        }

        /* Search results counter */
        .results-counter {
            position: absolute;
            bottom: -22px;
            left: 15px;
            font-size: 12px;
            color: #6b7280;
        }

        /* Hide table rows that don't match search */
        .vendor-list tr.hidden-row {
            display: none;
        }

        /* Highlight matched text */
        .highlight {
            background-color: rgba(250, 204, 21, 0.3);
            padding: 1px 0;
            border-radius: 2px;
        }

        /* Only keep the SELECT button in right section */
        .select-items {
            display: flex;
            gap: 8px;
        }

        .select-btn {
            padding: 10px 20px;
            border-radius: 9999px;
            font-size: 14px;
            font-weight: 600;
            cursor: pointer;
            text-transform: uppercase;
            transition: all 0.25s ease-in-out;
            border: none;
            background: #f3f4f6;
            color: #374151;
            box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
        }

        .select-btn:hover {
            background: #e5e7eb;
            transform: translateY(-1px);
            box-shadow: 0 2px 5px rgba(0, 0, 0, 0.15);
        }

        .select-btn.active {
            background: linear-gradient(135deg, #facc15, #eab308);
            color: #fff;
            box-shadow: 0 6px 16px rgba(234, 179, 8, 0.3);
        }

        /* No results message */
        .no-results {
            text-align: center;
            padding: 30px;
            font-size: 16px;
            color: #6b7280;
            background-color: #f9fafb;
            border-radius: 8px;
            margin: 20px 0;
        }

        /* Delete selected button */
        .delete-selected-btn {
            position: fixed;
            bottom: 30px;
            right: 30px;
            width: 50px;
            height: 50px;
            border-radius: 50%;
            background: linear-gradient(135deg, #ef4444, #dc2626);
            color: white;
            display: flex;
            align-items: center;
            justify-content: center;
            border: none;
            box-shadow: 0 4px 12px rgba(220, 38, 38, 0.3);
            cursor: pointer;
            transition: all 0.2s ease;
            z-index: 100;
        }

        .delete-selected-btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 16px rgba(220, 38, 38, 0.4);
        }

        /* Enhanced Vendor Details Styling */
        .vendor-details {
            flex: 1;
            background: rgba(255, 255, 255, 0.8);
            margin: 0px 20px 0px 0px;
            padding: 25px;
            border-radius: 12px;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.05);
            border: 1px solid rgba(255, 255, 255, 0.3);
            /* Glassmorphism effect */
            background: rgba(255, 255, 255, 0.8);
            backdrop-filter: blur(12px) saturate(180%);
            -webkit-backdrop-filter: blur(12px) saturate(180%);
            position: relative;
            overflow: hidden;
        }

        .vendor-placeholder {
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            height: 100%;
            padding: 40px 20px;
            text-align: center;
        }

        .vendor-placeholder h4 {
            margin-bottom: 20px;
            color: #374151;
            font-weight: 600;
        }

        .vendor-placeholder p {
            color: #6b7280;
            max-width: 80%;
            margin: 0 auto 20px auto;
        }

        .vendor-placeholder .search-result-info {
            margin-top: 40px;
            padding: 15px;
            background: rgba(243, 244, 246, 0.7);
            border-radius: 8px;
            width: 80%;
        }

        .vendor-info {
            display: none;
        }

        .vendor-header {
            position: relative;
            padding-bottom: 20px;
            margin-bottom: 30px;
            border-bottom: 1px solid #e5e7eb;
            text-align: center;
        }

        .vendor-header h3 {
            margin: 0;
            font-weight: 600;
            color: #111827;
            padding: 10px 0;
        }

        .vendor-header .vendor-type-badge {
            display: inline-block;
            padding: 4px 12px;
            background-color: #dbeafe;
            color: #1e40af;
            font-size: 12px;
            font-weight: 500;
            border-radius: 9999px;
            margin-top: 8px;
        }

        .vendor-details-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 25px;
            margin-bottom: 30px;
        }

        .detail-section {
            background-color: #f9fafb;
            border-radius: 8px;
            padding: 20px;
            box-shadow: 0 1px 3px rgba(0, 0, 0, 0.05);
        }

        .detail-section h4 {
            font-size: 16px;
            font-weight: 600;
            color: #4b5563;
            margin-top: 0;
            margin-bottom: 15px;
            padding-bottom: 10px;
            border-bottom: 1px solid #e5e7eb;
        }

        .detail-item {
            display: flex;
            margin-bottom: 12px;
        }

        .detail-item:last-child {
            margin-bottom: 0;
        }

        .detail-label {
            width: 40%;
            color: #6b7280;
            font-weight: 500;
            font-size: 14px;
        }

        .detail-value {
            width: 60%;
            color: #111827;
            font-weight: 400;
            font-size: 14px;
            word-break: break-word;
        }

        .remarks-section {
            grid-column: span 2;
            background-color: #f9fafb;
            border-radius: 8px;
            padding: 20px;
            box-shadow: 0 1px 3px rgba(0, 0, 0, 0.05);
        }

        .remarks-content {
            background-color: white;
            padding: 15px;
            border-radius: 6px;
            border: 1px solid #e5e7eb;
            min-height: 80px;
            font-size: 14px;
            color: #4b5563;
        }

        .edit-button {
            position: absolute;
            bottom: 25px;
            right: 25px;
        }

        .edit-button button {
            background: linear-gradient(135deg, #facc15, #eab308);
            color: #fff;
            border: none;
            padding: 10px 24px;
            border-radius: 9999px;
            font-size: 14px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.2s ease;
            text-transform: uppercase;
            box-shadow: 0 4px 10px rgba(234, 179, 8, 0.3);
            display: inline-flex;
            align-items: center;
            gap: 8px;
        }

        .edit-button button:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 15px rgba(234, 179, 8, 0.4);
        }

        /* Responsive adjustments */
        @media (max-width: 768px) {
            .vendor-details-grid {
                grid-template-columns: 1fr;
            }
            
            .remarks-section {
                grid-column: 1;
            }
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

        <!-- Search Bar in search-filter-bar div -->
        <div class="search-filter-bar">
            <!-- Left group: Add, Search -->
            <div class="left-controls">
                <button class="add-record-btn" id="addVendorBtn">
                    ADD VENDOR <img src="icons/circle-plus.svg" alt="Add" width="16">
                </button>

                <div class="search-container">
                    <img src="icons/search.svg" alt="Search" class="search-icon">
                    <input type="text" id="search-input" class="search-input" placeholder="Search vendors..." autocomplete="off">
                    <button type="button" id="clearSearchBtn" class="clear-search-btn" style="display: none;">×</button>
                    <div class="results-counter" id="resultsCounter"></div>
                </div>
            </div>

            <!-- Right group: Select items -->
            <div class="select-items">
                <button class="select-btn" id="selectBtn">SELECT</button>
            </div>
        </div>

        <div class="vendor-content">
            <!-- Left Section: List of Vendors -->
            <div class="vendor-list">
                <?php if (mysqli_num_rows($result) > 0): ?>
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
                                            <input type="checkbox" class="row-checkbox" style="display: none;">
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

                        <button class="delete-selected-btn" style="display: none;">
                            <img src="icons/trash.svg" alt="TrashIcon" width="20">
                        </button>
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

                    <!-- Edit Button -->
                    <div class="edit-button" id="editVendorButton">
                        <button class="btn btn-primary" onclick="openEditVendorModal(currentVendor)">
                            Edit <img src="icons/edit.svg" width="16">
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

    <!-- ADD/EDIT VENDOR MODAL - Redesigned to match edit_project_modal.php -->
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
                
                // Update results counter
                if (searchTerm) {
                    resultsCounter.textContent = `${matchCount} result${matchCount !== 1 ? 's' : ''} found`;
                    resultsCounter.style.display = 'block';
                } else {
                    resultsCounter.style.display = 'none';
                }
                
                // Show/hide no results message
                noResultsMessage.style.display = (searchTerm && matchCount === 0) ? 'block' : 'none';
                
                // If there's at least one match and vendor details are showing placeholder,
                // you might want to select the first visible row
                if (matchCount > 0 && document.getElementById('vendorPlaceholder').style.display !== 'none') {
                    const firstVisibleRow = document.querySelector('.vendor-list tbody tr:not(.hidden-row)');
                    if (firstVisibleRow) {
                        // Optionally auto-select the first matching vendor
                        // Uncomment the next line to enable this behavior
                        // firstVisibleRow.click();
                    }
                }
            }
            
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