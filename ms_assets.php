<?php
    include('validate_login.php');
    $page_title = "ASSETS";

    // DATABASE CONNECTION
    $conn = new mysqli("localhost", "root", "", "malayasol");
    if ($conn->connect_error) {
        die("Connection failed: " . $conn->connect_error);
    }

    // PROJECT DROPDOWN OPTIONS
    $projectOptions = '';
    $projectQuery = "SELECT project_id, project_name FROM projects ORDER BY project_name";
    $projectResult = $conn->query($projectQuery);
    if ($projectResult->num_rows > 0) {
        while ($proj = $projectResult->fetch_assoc()) {
            $projectOptions .= '<option value="' . $proj['project_id'] . '">' . htmlspecialchars($proj['project_name']) . '</option>';
        }
    }

    // EMPLOYEE DROPDOWN OPTIONS (for assigned_to)
    $employeeOptions = '';
    $employeeQuery = "SELECT employee_id, CONCAT(first_name, ' ', last_name) as full_name FROM employee WHERE employment_status = 'active' ORDER BY first_name";
    $employeeResult = $conn->query($employeeQuery);
    if ($employeeResult->num_rows > 0) {
        while ($emp = $employeeResult->fetch_assoc()) {
            $employeeOptions .= '<option value="' . htmlspecialchars($emp['full_name']) . '">' . htmlspecialchars($emp['full_name']) . '</option>';
        }
    }

    // ADD / EDIT / DELETE HANDLER
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        if (isset($_POST['delete_assets'])) {
            if (!empty($_POST['asset_ids'])) {
                $asset_ids = explode(',', $_POST['asset_ids']);
                foreach ($asset_ids as $asset_id) {
                    $stmt = $conn->prepare("DELETE FROM assets WHERE asset_id = ?");
                    $stmt->bind_param("i", $asset_id);
                    $stmt->execute();
                }
                echo "<script>window.location = 'ms_assets.php';</script>";
                exit();
            }
        } else {
            $asset_id = $_POST['asset_id'] ?? '';
            $asset_description = $_POST['asset_description'] ?? '';
            $location = $_POST['location'] ?? '';
            $assigned_to = $_POST['assigned_to'] ?? '';
            $serial_number = $_POST['serial_number'] ?? '';
            $warranty_expiry = $_POST['warranty_expiry'] ?? null;
            $user_id = $_SESSION['user_id'];

            // Handle image upload
            $imagePath = null;
            if (!empty($_FILES['asset_img']['name'])) {
                $targetDir = "uploads/assets/";
                if (!is_dir($targetDir)) {
                    mkdir($targetDir, 0777, true);
                }
                $imageExtension = pathinfo($_FILES["asset_img"]["name"], PATHINFO_EXTENSION);
                $imageName = uniqid() . '.' . $imageExtension;
                $imagePath = $targetDir . $imageName;
                move_uploaded_file($_FILES["asset_img"]["tmp_name"], $imagePath);
            }

            if ($asset_id) {
                // UPDATE
                $updateQuery = "UPDATE assets SET 
                    asset_description = ?, 
                    location = ?, 
                    assigned_to = ?, 
                    serial_number = ?, 
                    warranty_expiry = ?, 
                    edit_date = NOW(), 
                    edited_by = ?";
                
                $params = [$asset_description, $location, $assigned_to, $serial_number, $warranty_expiry, $user_id];
                $types = "sssssi";
                
                if ($imagePath) {
                    $updateQuery .= ", asset_img = ?";
                    $params[] = $imagePath;
                    $types .= "s";
                }
                
                $updateQuery .= " WHERE asset_id = ?";
                $params[] = $asset_id;
                $types .= "i";
                
                $stmt = $conn->prepare($updateQuery);
                $stmt->bind_param($types, ...$params);
            } else {
                // INSERT
                $stmt = $conn->prepare("INSERT INTO assets (asset_description, asset_img, location, assigned_to, serial_number, warranty_expiry, created_by) VALUES (?, ?, ?, ?, ?, ?, ?)");
                $stmt->bind_param("ssssssi", $asset_description, $imagePath, $location, $assigned_to, $serial_number, $warranty_expiry, $user_id);
            }

            if ($stmt->execute()) {
                echo "<script>window.location = 'ms_assets.php';</script>";
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
    <title>Assets - Malaya Solar Energies Inc.</title>
    <link rel="icon" href="images/Malaya_Logo.png" type="image/png">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Atkinson+Hyperlegible&display=swap" rel="stylesheet">
    <link href="css/ms_sidebar.css" rel="stylesheet">
    <link href="css/ms_header.css" rel="stylesheet">
    <link href="css/ms_assets.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</head>
<body>
    <div class="sidebar" id="sidebar">
        <?php include 'sidebar.php'; ?>
    </div>
    
    <div class="content-area">
        <?php include 'header.php'; ?>
   
        <div class="content-body">
            <!-- Search, Filter, and Control Bar -->
            <div class="search-filter-bar">
                <div class="left-controls">
                    <button class="add-record-btn" id="addAssetBtn">
                        <img src="icons/plus.svg" alt="Add" width="16"> ASSET
                    </button>

                    <div class="search-container">
                        <input type="text" class="search-input" placeholder="Search assets..." id="searchInput">
                    </div>

                    <div class="filter-options">
                        <div class="dropdown-container">
                            <button class="sort-btn" id="sortBtn">
                                <img src="icons/arrow-down-up.svg" alt="Sort" width="16"> Sort By
                            </button>
                            <div class="dropdown-menu-custom" id="sortDropdown">
                                <button class="dropdown-item-custom" data-sort="description-asc"> A to Z</button>
                                <button class="dropdown-item-custom" data-sort="description-desc"> Z to A</button>
                                <button class="dropdown-item-custom" data-sort="date-newest"> Newest First</button>
                                <button class="dropdown-item-custom" data-sort="date-oldest"> Oldest First</button>
                                <button class="dropdown-item-custom" data-sort="value-high"> Highest Value</button>
                                <button class="dropdown-item-custom" data-sort="value-low"> Lowest Value</button>
                            </div>
                        </div>
                        
                        <div class="dropdown-container">
                            <button class="filter-btn" id="filterBtn"> Filter </button>
                            <div class="dropdown-menu-custom" id="filterDropdown">
                                <button class="dropdown-item-custom active" data-filter="all"> All Assets</button>
                                <button class="dropdown-item-custom" data-filter="tracked"> Tracked Only</button>
                                <button class="dropdown-item-custom" data-filter="untracked"> Untracked Only</button>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="select-items">
                    <button class="select-btn" id="selectBtn">SELECT</button>
                </div>
            </div>

            <div class="asset-content">
                <!-- Left Section: List of Assets -->
                <div class="asset-list">
                    <div class="asset-list-header">
                        <h4>ASSETS</h4>
                        <span class="asset-count" id="assetCount">0 items</span>
                    </div>

                    <?php
                    // Updated query with JOIN to get expense value
                    $query = "
                        SELECT 
                            a.*,
                            CASE 
                                WHEN e.is_rental = 'Yes' THEN e.rental_rate
                                ELSE e.expense
                            END as asset_value,
                            e.purchase_date as expense_date,
                            p.project_name
                        FROM assets a
                        LEFT JOIN expense e ON a.record_id = e.record_id
                        LEFT JOIN projects p ON e.project_id = p.project_id
                        ORDER BY a.creation_date DESC
                    ";
                    $result = $conn->query($query);
                    if ($result->num_rows > 0):
                        $counter = 1;
                    ?>
                    <div class="asset-list-table-wrapper">
                        <table class="asset-list-table">
                            <thead>
                                <tr>
                                    <th style="width: 5%;"></th>
                                    <th style="width: 25%;">Description</th>
                                    <th style="width: 25%; text-align: center;">Serial No.</th>
                                    <th style="width: 25%; text-align: center;">Value</th>
                                    <th style="width: 20%; text-align: center;">Status</th>
                                </tr>
                            </thead>
                        </table>

                        <div class="asset-list-table-body-container">
                            <table class="asset-list-table">
                                <tbody id="assetTableBody">
                                    <?php while ($row = $result->fetch_assoc()): 
                                        $isUntracked = is_null($row['record_id']);
                                        $assetValue = $row['asset_value'] ? '₱' . number_format($row['asset_value'], 2) : 'N/A';
                                    ?>
                                        <tr data-id="<?= $row['asset_id'] ?>" data-json='<?= json_encode($row) ?>' class="asset-row">
                                            <td class="row-index-cell">
                                                <span class="row-number"><?= $counter++ ?></span>
                                                <input type="checkbox" class="row-checkbox" style="display: none;" style="width: 25%;">
                                            </td>
                                            <td class="asset-description" style="width: 25%;">
                                                <div class="description-main"><?= htmlspecialchars($row['asset_description']) ?></div>
                                                <?php if ($row['assigned_to']): ?>
                                                <div class="description-sub">Assigned to: <?= htmlspecialchars($row['assigned_to']) ?></div>
                                                <?php endif; ?>
                                            </td>
                                            <td style="width: 25%; text-align: center;"><?= htmlspecialchars($row['serial_number'] ?: 'N/A') ?></td>
                                            <td class="asset-value" style="width: 25%; text-align: right;"><?= $assetValue ?></td>
                                            <td class="asset-status" style="width: 20%; text-align: center;">
                                                <?php if ($isUntracked): ?>
                                                    <span class="status-badge untracked">Untracked</span>
                                                <?php else: ?>
                                                    <span class="status-badge tracked">Tracked</span>
                                                <?php endif; ?>
                                            </td>
                                        </tr>
                                    <?php endwhile; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>

                    <!-- Floating delete button -->
                    <button class="delete-selected-btn" id="deleteBtn" style="display: none;">
                        <img src="icons/trash.svg" alt="Delete" width="20">
                    </button>

                    <?php else: ?>
                        <div class="no-assets">
                            <img src="icons/box.svg" alt="No Assets" width="48">
                            <p>No assets found</p>
                            <button class="add-first-asset-btn" onclick="document.getElementById('addAssetBtn').click()">
                                Add Your First Asset
                            </button>
                        </div>
                    <?php endif; ?>
                </div>

                <!-- Right Section: Asset Details -->
                <div class="asset-details" id="assetDetails">
                    <div id="assetPlaceholder" class="asset-placeholder">
                        <h4>Select an Asset</h4>
                        <p>Choose an asset from the list to view its details</p>
                    </div>

                    <div id="assetInfo" style="display: none;">
                        <div class="asset-header">
                            <h4 id="assetTitle"></h4>
                            <div class="asset-actions">
                                <button class="btn-edit" id="editAssetBtn">
                                    <img src="icons/pencil-white.svg" width="16"> Edit
                                </button>
                            </div>
                        </div>

                        <div class="asset-image-container" id="assetImageContainer">
                            <div class="no-image">
                                <p>No Image Available</p>
                            </div>
                        </div>

                        <div class="asset-info-grid">
                            <div class="info-item">
                                <label>Serial Number</label>
                                <span id="detailSerial">N/A</span>
                            </div>
                            <div class="info-item">
                                <label>Location</label>
                                <span id="detailLocation">N/A</span>
                            </div>
                            <div class="info-item">
                                <label>Assigned To</label>
                                <span id="detailAssigned">N/A</span>
                            </div>
                            <div class="info-item">
                                <label>Value</label>
                                <span id="detailValue">N/A</span>
                            </div>
                            <div class="info-item">
                                <label>Warranty Expiry</label>
                                <span id="detailWarranty">N/A</span>
                            </div>
                            <div class="info-item">
                                <label>Project</label>
                                <span id="detailProject">N/A</span>
                            </div>
                        </div>

                        <div class="untracked-notice" id="untrackedNotice" style="display: none;">
                            <div class="notice-icon">⚠️</div>
                            <div class="notice-content">
                                <strong>Untracked Expense</strong>
                                <p>This item is an untracked expense. Please add if expense is incurred.</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Delete Confirmation Modal -->
    <div class="modal fade" id="deleteModal" tabindex="-1" aria-labelledby="deleteModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="deleteModalLabel">Confirm Deletion</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <p id="deleteMessage">Are you sure you want to delete the selected asset(s)?</p>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="button" class="btn btn-danger" id="confirmDeleteBtn">Delete</button>
                </div>
            </div>
        </div>
    </div>

    <!-- ADD/EDIT ASSET MODAL -->
    <div class="modal fade" id="assetModal" tabindex="-1" aria-labelledby="assetModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <form id="assetForm" method="POST" enctype="multipart/form-data">
                    <input type="hidden" name="asset_id" id="asset_id">
                    
                    <div class="modal-header">
                        <h5 class="modal-title" id="modalTitle">Add Asset</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    
                    <div class="modal-body">
                        <div class="form-section">
                            <div class="form-section-title">Asset Information</div>
                            <div class="row g-3">
                                <div class="col-md-8">
                                    <label for="asset_description" class="form-label">Description *</label>
                                    <input type="text" class="form-control" id="asset_description" name="asset_description" required>
                                </div>
                                <div class="col-md-4">
                                    <label for="serial_number" class="form-label">Serial Number</label>
                                    <input type="text" class="form-control" id="serial_number" name="serial_number">
                                </div>
                            </div>
                        </div>
                        
                        <div class="form-section">
                            <div class="form-section-title">Location & Assignment</div>
                            <div class="row g-3">
                                <div class="col-md-6">
                                    <label for="location" class="form-label">Location</label>
                                    <input type="text" class="form-control" id="location" name="location">
                                </div>
                                <div class="col-md-6">
                                    <label for="assigned_to" class="form-label">Assigned To</label>
                                    <select class="form-select" id="assigned_to" name="assigned_to">
                                        <option value="">-- Select Employee --</option>
                                        <?= $employeeOptions ?>
                                    </select>
                                </div>
                            </div>
                        </div>
                        
                        <div class="form-section">
                            <div class="form-section-title">Additional Information</div>
                            <div class="row g-3">
                                <div class="col-md-6">
                                    <label for="warranty_expiry" class="form-label">Warranty Expiry</label>
                                    <input type="date" class="form-control" id="warranty_expiry" name="warranty_expiry">
                                </div>
                                <div class="col-md-6">
                                    <label for="asset_img" class="form-label">Asset Image</label>
                                    <input type="file" class="form-control" id="asset_img" name="asset_img" accept="image/*">
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-primary" id="submitBtn">Add Asset</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Hidden form for deletion -->
    <form id="deleteForm" method="POST" style="display: none;">
        <input type="hidden" name="delete_assets" value="1">
        <input type="hidden" name="asset_ids" id="deleteAssetIds">
    </form>

    <script src="js/sidebar.js"></script>
    <script src="js/header.js"></script>
    <script src="js/ms_assets.js"></script>
</body>
</html>