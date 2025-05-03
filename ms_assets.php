<?php
session_start();

// Check if user is logged in
if (!isset($_SESSION['username'])) {
    header("Location: ms_login.php");
    exit();
}

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

// Check if an AJAX request is made to add an asset
if (isset($_POST['add_asset'])) {
    // Capture form data
    $category = $_POST['category'];
    $purchase_date = $_POST['purchase_date'];
    $asset_description = $_POST['asset_description'];
    $value = $_POST['value'];
    $rental_rate = $_POST['rental_rate'];
    $tax = $_POST['tax'];
    $remarks = $_POST['remarks'];
    
    // Handling image upload
    if ($_FILES['asset_image']['name']) {
        $imageName = $_FILES['asset_image']['name'];
        $imageTmpName = $_FILES['asset_image']['tmp_name'];
        $uploadDirectory = "assets/images/";
        $imagePath = $uploadDirectory . basename($imageName);
        
        // Move the uploaded image
        move_uploaded_file($imageTmpName, $imagePath);
    } else {
        $imagePath = null; // Default to null if no image is uploaded
    }

    // Database connection
    $conn = new mysqli($servername, $username, $password, $dbname);
    if ($conn->connect_error) {
        die("Connection failed: " . $conn->connect_error);
    }

    // Insert new asset into the database
    $query = "INSERT INTO assets (category, purchase_date, asset_description, value, rental_rate, tax, remarks, asset_image) 
              VALUES (?, ?, ?, ?, ?, ?, ?, ?)";
    $stmt = $conn->prepare($query);
    $stmt->bind_param("ssssssss", $category, $purchase_date, $asset_description, $value, $rental_rate, $tax, $remarks, $imagePath);
    
    if ($stmt->execute()) {
        // Return the new asset in JSON format for AJAX
        $newAsset = [
            'asset_id' => $stmt->insert_id,
            'asset_description' => $asset_description,
            'category' => $category,
            'value' => number_format($value, 2),
            'asset_image' => $imagePath ? $imagePath : 'No Image'
        ];
        echo json_encode(['status' => 'success', 'asset' => $newAsset]);
    } else {
        echo json_encode(['status' => 'error', 'message' => 'Failed to add asset.']);
    }

    $conn->close();
    exit(); // End the script to prevent page reload
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
    <link href="css/ms_assets.css" rel="stylesheet">
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
            <a class="active" href="ms_assets.php"><button>Assets</button></a>
            <a href="ms_expenses.php"><button>Expenses</button></a>
            <a href="ms_workforce.php"><button>Workforce</button></a>
            <a href="ms_payroll.php"><button>Payroll</button></a>
            <a href="ms_vendors.php"><button>Vendors</button></a>
            <a href="ms_reports.php"><button>Reports</button></a>
        </div>
    </div>
    
    <div class="content-area">
        <!-- Header Section -->
        <header class="top-bar">
            <button class="hamburger" id="toggleSidebar">☰</button>
            <h2 class="page-title">ASSETS</h2>
            
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
            <button class="add-record-btn" id="addAssetBtn">ASSET <img src="icons/circle-plus.svg" alt="UserIcon" width="16"></button>

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
                <div class="asset-list-header">Assets</div> <!-- Header Bar inside asset list -->

                <?php
                $query = "SELECT * FROM assets ORDER BY asset_id ASC";
                $result = mysqli_query($conn, $query);

                if (mysqli_num_rows($result) > 0):
                    $counter = 1;
                    echo "<table class='table'>";
                    echo "<thead><tr><th>#</th><th>Description</th><th>Category</th><th>Value</th></tr></thead><tbody>";
                    while ($row = mysqli_fetch_assoc($result)):
                ?>
                    <tr onclick='showAssetDetails(<?= htmlspecialchars(json_encode($row)) ?>)'>
                        <td><?= $counter++ ?></td>
                        <td><?= htmlspecialchars($row['asset_description']) ?></td>
                        <td><?= htmlspecialchars($row['category']) ?></td>
                        <td>₱<?= number_format($row['value'], 2) ?></td>
                    </tr>
                <?php
                    endwhile;
                    echo "</tbody></table>";
                else:
                    echo "<p style='padding: 20px;'>There are no existing assets.</p>";
                endif;
                ?>
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

                    <div class="edit-button">
                        <button class="btn btn-primary">
                            Edit<img src="icons/edit.svg" width="20">
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- ADD ASSET MODAL -->
    <div id="addAssetModal" class="custom-modal-overlay">
        <div class="custom-modal">
            <div class="modal-header">
                <h5>ADD ASSET</h5>
            </div>
            <form method="POST" id="addAssetForm" enctype="multipart/form-data">
                <div class="modal-body">
                    
                    <div class="input-row">
                        <div class="form-group">
                            <label>Category</label>
                            <input type="text" name="category" required>
                        </div>
                        <div class="form-group">
                            <label>Date of Purchase</label>
                            <input type="date" name="purchase_date" value="<?= date('Y-m-d') ?>">
                        </div>
                    </div>

                    <div class="input-row">
                        <div class="form-group">
                            <label>Description</label>
                            <input type="text" name="asset_description" required>
                        </div>
                        <div class="form-group">
                            <label>Value</label>
                            <input type="number" name="value" placeholder="0.00" step="0.01" required>
                        </div>
                    </div>

                    <div class="input-row">
                        <div class="form-group">
                            <label>Rental Rate</label>
                            <input type="number" name="rental_rate" placeholder="0.00" step="0.01">
                        </div>
                        <div class="form-group">
                            <label>Tax</label>
                            <input type="number" name="tax" placeholder="0.00" step="0.01">
                        </div>
                    </div>

                    <div class="form-group full-width">
                        <label>Remarks</label>
                        <textarea name="remarks" rows="3" maxlength="500"></textarea>
                    </div>

                    <div class="form-group full-width">
                        <label>Upload Image</label>
                        <input type="file" name="asset_image" accept="image/*">
                    </div>
                </div>

                <div class="modal-footer">
                    <button type="submit" name="add_asset" class="btn-add">ADD</button>
                    <button type="button" class="btn-cancel" onclick="closeModal()">CANCEL</button>
                </div>
            </form>
        </div>
    </div>
    
    <script>
        //Sidebar Trigger (pullup or collapse sidebar)
        document.getElementById("toggleSidebar").addEventListener("click", function () {
            document.getElementById("sidebar").classList.toggle("collapsed");
        });

        //User Menu dropdown
        document.getElementById("userDropdownBtn").addEventListener("click", function (event) {
            event.stopPropagation(); // prevent body click from closing immediately
            const dropdown = document.getElementById("userDropdownMenu");
            dropdown.style.display = (dropdown.style.display === "block") ? "none" : "block";
        });

        // Close dropdown if clicking outside
        document.addEventListener("click", function () {
            document.getElementById("userDropdownMenu").style.display = "none";
        });

        function showAssetDetails(asset) {
            // Hide placeholder
            document.getElementById('assetPlaceholder').style.display = 'none';
            
            // Show asset info
            document.getElementById('assetInfo').style.display = 'block';

            // Set the header
            document.getElementById('assetHeader').textContent = asset.asset_description || '';

            // Fill in other details
            document.getElementById('assetCategory').textContent = asset.category || '';
            document.getElementById('assetValue').textContent = asset.value ? Number(asset.value).toFixed(2) : '0.00';
            document.getElementById('assetTax').textContent = asset.tax ? Number(asset.tax).toFixed(2) : '0.00';
            document.getElementById('assetRentalRate').textContent = asset.rental_rate ? Number(asset.rental_rate).toFixed(2) : '0.00';
            document.getElementById('assetPurchaseDate').textContent = asset.purchase_date || '';
            document.getElementById('assetRemarks').textContent = asset.remarks || '';
        }

        document.getElementById("addAssetBtn").addEventListener("click", function() {
            // Show the Add Asset modal when the button is clicked
            document.getElementById("addAssetModal").style.display = "flex";
        });

        // Function to close the modal
        function closeModal() {
            document.getElementById("addAssetModal").style.display = "none";
        }

        // Handle Add Asset form submission
        document.querySelector('form').addEventListener('submit', function(event) {
            event.preventDefault(); // Prevent the form from submitting normally
            
            const formData = new FormData(this); // Create a FormData object to handle form data
            
            // Send AJAX request
            fetch('ms_assets.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json()) // Parse JSON response
            .then(data => {
                if (data.status === 'success') {
                    // Append the new asset to the asset list
                    const asset = data.asset;
                    const assetRow = document.createElement('tr');
                    assetRow.innerHTML = `
                        <td>${document.querySelectorAll('.asset-list tbody tr').length + 1}</td>
                        <td>${asset.asset_description}</td>
                        <td>${asset.category}</td>
                        <td>₱${asset.value}</td>
                    `;
                    document.querySelector('.asset-list tbody').appendChild(assetRow);
                    
                    // Optionally, reset the form or close the modal
                    document.querySelector('form').reset();
                    closeModal(); // Close the modal after adding asset
                } else {
                    alert('Error: ' + data.message); // Display error if any
                }
            })
            .catch(error => {
                alert('Error: ' + error);
            });
        });
    </script>
</body>
</html>