<?php
    include('validate_login.php');
    $page_title = "ASSETS";

    // DATABASE CONNECTION
<<<<<<< HEAD
    $servername = "localhost";  
    $username = "u188693564_adminsolar";           
    $password = "@Malayasolarenergies1";            
    $dbname = "u188693564_malayasol";     

    // Create connection
    $conn = new mysqli($servername, $username, $password, $dbname);

    // Check connection
=======
    $conn = new mysqli("localhost", "root", "", "malayasol");
>>>>>>> 53a8b739bac1fa34eb692d96a52277e24a81f2ec
    if ($conn->connect_error) {
        die("Connection failed: " . $conn->connect_error);
    }

    // CATEGORY DROPDOWN
    $categoryOptions = '';
    $categoryQuery = "SELECT project_name FROM projects";
    $categoryResult = $conn->query($categoryQuery);
    if ($categoryResult->num_rows > 0) {
        while ($cat = $categoryResult->fetch_assoc()) {
            $categoryOptions .= '<option value="' . htmlspecialchars($cat['project_name']) . '">' . htmlspecialchars($cat['project_name']) . '</option>';
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
            $asset_id = $_POST['asset_id'];
            $category = $_POST['category'];
            $description = $_POST['asset_description'];
            $value = $_POST['value'];
            $purchase_date = $_POST['purchase_date'] ?: date('Y-m-d');
            $rental_rate = $_POST['rental_rate'] ?: 0.00;
            $tax = $_POST['tax'] ?: 0.00;
            $remarks = $_POST['remarks'];

            $imagePath = null;
            if (!empty($_FILES['asset_image']['name'])) {
                $targetDir = "uploads/";
                if (!is_dir($targetDir)) mkdir($targetDir);
                $imagePath = $targetDir . basename($_FILES["asset_image"]["name"]);
                move_uploaded_file($_FILES["asset_image"]["tmp_name"], $imagePath);
            }

            if ($asset_id) {
                $stmt = $conn->prepare("UPDATE assets SET category=?, asset_description=?, value=?, purchase_date=?, rental_rate=?, tax=?, remarks=?, asset_image=? WHERE asset_id=?");
                $stmt->bind_param("ssdsddssi", $category, $description, $value, $purchase_date, $rental_rate, $tax, $remarks, $imagePath, $asset_id);
            } else {
                $stmt = $conn->prepare("INSERT INTO assets (category, asset_description, value, purchase_date, rental_rate, tax, remarks, asset_image) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
                $stmt->bind_param("ssdsddss", $category, $description, $value, $purchase_date, $rental_rate, $tax, $remarks, $imagePath);
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
    <title>Malaya Solar Energies Inc.</title>
    <link rel="icon" href="images/Malaya_Logo.png" type="image/png">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Atkinson+Hyperlegible&display=swap" rel="stylesheet">
    <link href="css/ms_assetsdesign.css" rel="stylesheet">
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
   
        <div class="content-body">
        <!-- Add Records, Search, Filter, and Toggle Bar -->
        <div class="search-filter-bar">
            <!-- Left group: Add, Search, Filter -->
            <div class="left-controls">
            <button class="add-record-btn" id="addAssetBtn">ASSET <img src="icons/plus.svg" alt="UserIcon" width="16"></button>

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

        <div class="asset-content">
            <!-- Left Section: List of Assets -->
            <div class="asset-list">
                <div class="asset-list-header">Assets</div>

                <?php
                $query = "SELECT * FROM assets ORDER BY asset_id ASC";
                $result = $conn->query($query);
                if ($result->num_rows > 0):
                    $counter = 1;
                ?>
                <div class="asset-list-table-wrapper">
                    <table class="asset-list-table">
                        <thead>
                            <tr>
                                <th style="width: 5%;"></th>
                                <th style="width: 45%;">Description</th>
                                <th style="width: 25%;">Category</th>
                                <th style="width: 25%;">Value</th>
                            </tr>
                        </thead>
                    </table>

                    <div class="asset-list-table-body-container">
                        <table class="asset-list-table">
                            <tbody>
                                <?php while ($row = $result->fetch_assoc()): ?>
                                    <tr data-id="<?= $row['asset_id'] ?>" data-json='<?= json_encode($row) ?>'>
                                        <td class="row-index-cell">
                                            <span class="row-number"><?= $counter++ ?></span>
                                            <input type="checkbox" class="row-checkbox" style="display: none;">
                                        </td>
                                        <td><?= htmlspecialchars($row['asset_description']) ?></td>
                                        <td><?= htmlspecialchars($row['category']) ?></td>
                                        <td>₱<?= number_format($row['value'], 2) ?></td>
                                    </tr>
                                <?php endwhile; ?>
                            </tbody>
                        </table>
                    </div>
                </div>

                <!-- Floating delete button (moved here) -->
                <button class="delete-selected-btn">
                    <img src="icons/trash.svg" alt="TrashIcon" width="20">
                </button>

                <?php else: ?>
                    <p style='padding: 20px;'>There are no existing assets.</p>
                <?php endif; ?>
            </div>

            <!-- Right Section: Asset Details -->
            <div class="asset-details" id="assetDetails">
                <div id="assetPlaceholder">
                    <h4>Select an Asset</h4>
                </div>

                <div id="assetInfo" style="display: none;">
                    <h4 id="assetHeader"></h4> <!-- Dynamic asset_description header -->

                    <div id="assetImageContainer">No Image.</div>

                    <div class="asset-details-info">
                        <p><strong>Category:</strong> <span id="assetCategory"></span></p>
                        <div class="asset-cost-tax">
                            <p><strong>Cost/Value:</strong> ₱<span id="assetValue"></span></p>
                            <p><strong>Tax:</strong> ₱<span id="assetTax"></span></p>
                        </div>
                        <div class="asset-rental-date">
                            <p><strong>Rental Rate:</strong> ₱<span id="assetRentalRate"></span></p>
                            <p><strong>Date of Purchase:</strong> <span id="assetPurchaseDate"></span></p>
                        </div>
                        <p><strong>Remarks:</strong> <span id="assetRemarks"></span></p>
                    </div>

                    <!-- Edit Button -->
                    <div class="edit-button" id="editButton" style="display: none;">
                        <button onclick="openEditAssetModal()">
                            Edit <img src="icons/pencil.svg" width="14">
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Deletion Modal -->
    <div id="deleteModal" class="modal" style="display:none;">
        <div class="modal-content">
            <p id="deleteMessage">Are you sure you want to delete?</p>
            <form id="deleteAssetsForm" method="POST">
                <input type="hidden" name="asset_ids" id="delete-asset-ids">
                <input type="hidden" name="delete_assets" value="1">
                <button type="button" id="confirmDeleteBtn">Yes, Delete</button>
                <button type="button" onclick="document.getElementById('deleteModal').style.display='none'">Cancel</button>
            </form>
        </div>
    </div>

    <!-- Hidden form for deleting assets -->
    <form id="deleteAssetsForm" method="POST" action="ms_assets.php" style="display:none;">
        <input type="hidden" name="delete_assets" value="1">
        <input type="hidden" id="delete-asset-ids" name="asset_ids" value="">
    </form>

    <!-- ADD/EDIT ASSET MODAL -->
    <div id="addAssetModal" class="custom-modal-overlay">
        <div class="custom-modal">
            <div class="modal-header">
                <h5 id="modalTitle">ADD ASSET</h5>
            </div>
            <form id="assetForm" method="POST" enctype="multipart/form-data">
            <input type="hidden" name="asset_id" id="asset_id">
                <div class="modal-body">
                    <div class="input-row">
                        <div class="form-group">
                            <label>Category</label>
                            <select name="category" id="category" required>
                                <option value="">-- Select Project --</option>
                                <?= $categoryOptions ?>
                            </select>
                        </div>
                        <div class="form-group">
                            <label>Date of Purchase</label>
                            <input type="date" name="purchase_date" id="purchase_date">
                        </div>
                    </div>

                    <div class="input-row">
                        <div class="form-group">
                            <label>Description</label>
                            <input type="text" name="asset_description" id="asset_description" required>
                        </div>
                        <div class="form-group">
                            <label>Value</label>
                            <input type="number" name="value" id="value" placeholder="0.00" step="0.01" required>
                        </div>
                    </div>

                    <div class="input-row">
                        <div class="form-group">
                            <label>Rental Rate</label>
                            <input type="number" name="rental_rate" id="rental_rate" placeholder="0.00" step="0.01">
                        </div>
                        <div class="form-group">
                            <label>Tax</label>
                            <input type="number" name="tax" id="tax" placeholder="0.00" step="0.01">
                        </div>
                    </div>

                    <div class="form-group full-width">
                        <label>Remarks</label>
                        <textarea name="remarks" id="remarks" rows="3" maxlength="500"></textarea>
                    </div>

                    <div class="form-group full-width">
                        <label>Upload Image</label>
                        <input type="file" name="asset_image" id="asset_image" accept="image/*">
                    </div>
                </div>

                <div class="modal-footer">
                    <button type="submit" class="btn-add" id="submitAssetBtn">ADD</button>
                    <button type="button" class="btn-cancel" onclick="closeModal()">CANCEL</button>
                </div>
            </form>
        </div>
    </div>

    <script src="js/sidebar.js"></script>
    <script src="js/header.js"></script>

    <script>
        // Store the currently selected asset
        let currentAsset = null;

        // Show Asset Details
        function showAssetDetails(asset) {
            currentAsset = asset;

            document.getElementById('assetPlaceholder').style.display = 'none';
            document.getElementById('assetInfo').style.display = 'block';

            document.getElementById('assetHeader').textContent = asset.asset_description || '';
            document.getElementById('assetCategory').textContent = asset.category || '';
            document.getElementById('assetValue').textContent = asset.value ? Number(asset.value).toFixed(2) : '0.00';
            document.getElementById('assetTax').textContent = asset.tax ? Number(asset.tax).toFixed(2) : '0.00';
            document.getElementById('assetRentalRate').textContent = asset.rental_rate ? Number(asset.rental_rate).toFixed(2) : '0.00';
            document.getElementById('assetPurchaseDate').textContent = asset.purchase_date || '';
            document.getElementById('assetRemarks').textContent = asset.remarks || '';

            const imageContainer = document.getElementById("assetImageContainer");
            if (asset.asset_image && asset.asset_image !== "No Image") {
                imageContainer.innerHTML = `<img src="${asset.asset_image}" alt="Asset Image" style="max-width: 100%; max-height: 200px;">`;
            } else {
                imageContainer.textContent = "No Image.";
            }

            document.getElementById('editButton').style.display = 'block';
        }

        document.querySelectorAll('.asset-list tbody tr').forEach(row => {
            row.addEventListener('click', function (e) {
                // If the click target is a checkbox, ignore asset detail display
                if (e.target && e.target.matches('input[type="checkbox"]')) {
                    return;
                }

                const assetData = JSON.parse(this.dataset.json);
                showAssetDetails(assetData);
            });
        });

        //ADD or EDIT Modals
        const modal = document.getElementById('addAssetModal');
        const modalTitle = document.getElementById('modalTitle');
        const submitBtn = document.getElementById('submitAssetBtn');
        const form = document.getElementById('assetForm');

        // Open Add Modal
        document.getElementById('addAssetBtn').addEventListener('click', () => {
            modal.style.display = 'flex';
            modalTitle.textContent = 'ADD ASSET';
            submitBtn.textContent = 'ADD';
            form.reset();
            document.getElementById('asset_id').value = '';
        });

        // Open Edit Modal
        function openEditAssetModal() {
            if (!currentAsset) return;

            modal.style.display = 'flex';
            modalTitle.textContent = 'EDIT ASSET';
            submitBtn.textContent = 'SAVE';

            // Fill form with currentAsset
            document.getElementById('asset_id').value = currentAsset.asset_id;
            document.getElementById('category').value = currentAsset.category;
            document.getElementById('purchase_date').value = currentAsset.purchase_date;
            document.getElementById('asset_description').value = currentAsset.asset_description;
            document.getElementById('value').value = currentAsset.value;
            document.getElementById('rental_rate').value = currentAsset.rental_rate;
            document.getElementById('tax').value = currentAsset.tax;
            document.getElementById('remarks').value = currentAsset.remarks;
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
        const rows = document.querySelectorAll('.asset-list tbody tr');
        const deleteModal = document.getElementById('deleteModal');
        const deleteAssetIdsInput = document.getElementById('delete-asset-ids');

        function updateDeleteBtnVisibility() {
            const anyChecked = document.querySelectorAll('.row-checkbox:checked').length > 0;
            deleteBtn.style.display = anyChecked ? 'block' : 'none';
        }

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
                    checkbox.addEventListener('change', updateDeleteBtnVisibility);
                } else {
                    checkbox.checked = false;
                    checkbox.style.display = 'none';
                    number.style.display = 'inline-block';
                    checkbox.removeEventListener('change', updateDeleteBtnVisibility);
                }
            });

            rows.forEach(row => {
                if (enable) {
                    row.onclick = e => {
                        if (e.target.classList.contains('row-checkbox')) return;
                        const checkbox = row.querySelector('.row-checkbox');
                        checkbox.checked = !checkbox.checked;
                        updateDeleteBtnVisibility();
                    };
                } else {
                    row.onclick = () => showAssetDetails(JSON.parse(row.dataset.json));
                }
            });
        }

        document.addEventListener('keydown', e => {
            if (e.key === 'Escape' && selectMode) toggleSelectMode(false);
        });

        if (selectBtn) {
            selectBtn.addEventListener('click', () => toggleSelectMode(!selectMode));
        }

        deleteBtn.addEventListener('click', () => {
            const selected = [...document.querySelectorAll('.row-checkbox')]
                .filter(cb => cb.checked)
                .map(cb => cb.closest('tr').dataset.id);

            if (selected.length === 0) return;

            const confirmMsg = selected.length === 1
                ? 'Are you sure you want to delete this record?'
                : 'Are you sure you want to delete these records?';

            document.getElementById('deleteMessage').textContent = confirmMsg;
            deleteAssetIdsInput.value = selected.join(',');
            deleteModal.style.display = 'block';
        });

        document.getElementById('confirmDeleteBtn').addEventListener('click', () => {
            document.getElementById('deleteAssetsForm').submit();
        });

        window.onclick = function(event) {
            if (event.target == deleteModal) {
                deleteModal.style.display = 'none';
            }
        };
    </script>
</body>
</html>

<!--
NOTES: 
    05-04-25
    CHANGES:
    - add asset: working
    - select: working
    - delete: working
    - edit: working

    TO BE WORKED ON:
    - add and edit functions: taxes 

-->