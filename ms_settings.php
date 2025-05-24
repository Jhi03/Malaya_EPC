<?php
    include('validate_login.php');
    require_once 'activity_logger.php';
    
    // Additional verification for settings access
    $current_user_department = getCurrentUserDepartment();
    $current_user_role = getCurrentUserRole();
    
    if ($current_user_department !== 'IT Infrastructure & Cybersecurity Division' || 
        !in_array(strtolower($current_user_role), ['superadmin', 'admin'])) {
        
        logUserActivity('access_denied', 'ms_settings.php', 'Unauthorized settings access attempt');
        header("Location: access_denied.php");
        exit();
    }
    
    logUserActivity('access', 'ms_settings.php', "Settings accessed by {$current_user_role} from {$current_user_department}");
    
    $page_title = "System Settings - Malaya Solar Energies Inc.";
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $page_title ?></title>
    <link rel="icon" href="images/Malaya_Logo.png" type="image/png">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Atkinson+Hyperlegible&display=swap" rel="stylesheet">
    <link href="css/ms_dashboard.css" rel="stylesheet">
    <link href="css/ms_sidebar.css" rel="stylesheet">
    <link href="css/ms_header.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <style>
        body {
            font-family: 'Atkinson Hyperlegible', sans-serif;
            margin: 0;
            display: flex;
            height: 100vh;
            overflow: hidden;
        }
        .content-body {
            padding: 20px 40px 20px 40px;
            flex: 1;
            overflow-y: auto;
            display: flex;
            flex-direction: column;
            width: 100%;
            margin-left: auto;
            margin-right: auto;
            background: rgba(243, 243, 243, 0.8);
            background-image: repeating-linear-gradient(
                45deg,
                rgba(255, 255, 255, 0.700) 0px,
                rgba(255, 255, 255, 0.500) 1px,
                transparent 1px,
                transparent 20px
            );
            backdrop-filter: blur(12px) saturate(180%);
            -webkit-backdrop-filter: blur(12px) saturate(180%);
        }
        
        .settings-container {
            display: flex;
            flex-direction: column;
            gap: 20px;
        }
        
        .access-info {
            background: white;
            border-radius: 8px;
            padding: 20px;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
        }
        
        .access-info h6 {
            color: #333;
            margin-bottom: 10px;
            font-size: 12px;
            font-weight: 600;
        }
        
        .access-info p {
            margin-bottom: 5px;
            font-size: 12px;
            color: #666;
        }
        
        .backup-section {
            background: white;
            border-radius: 8px;
            padding: 20px;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
        }

        .backup-section h3 {
            margin-bottom: 10px;
            color: #333;
            font-weight: 600;
            font-size: 16px;
        }

        .backup-section p {
            color: #666;
            font-size: 12px;
            margin-bottom: 20px;
        }

        .backup-tabs {
            margin-bottom: 20px;
        }

        .backup-tabs .nav-link {
            font-size: 12px;
            padding: 8px 16px;
            color: #666;
            border: 1px solid #ddd;
            background: #f8f9fa;
        }

        .backup-tabs .nav-link.active {
            background: #007bff;
            color: white;
            border-color: #007bff;
        }

        .backup-options {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 15px;
            margin-top: 20px;
            margin-bottom: 20px;
        }

        .backup-card {
            border: 1px solid #ddd;
            border-radius: 6px;
            padding: 15px;
            text-align: center;
            transition: border-color 0.3s ease;
        }

        .backup-card:hover {
            border-color: #007bff;
        }

        .backup-card h5 {
            margin-bottom: 8px;
            color: #333;
            font-size: 12px;
            font-weight: 600;
        }

        .backup-card p {
            color: #666;
            font-size: 11px;
            margin-bottom: 12px;
            line-height: 1.3;
        }

        .backup-btn {
            background: #f8f9fa;
            border: 1px solid #dee2e6;
            color: #495057;
            padding: 6px 12px;
            border-radius: 4px;
            font-size: 11px;
            cursor: pointer;
            transition: all 0.3s ease;
            display: inline-flex;
            align-items: center;
            gap: 6px;
        }

        .backup-btn:hover {
            background: #e9ecef;
            border-color: #adb5bd;
        }

        .backup-btn:disabled {
            opacity: 0.6;
            cursor: not-allowed;
        }

        .backup-status {
            padding: 12px;
            border-radius: 4px;
            display: none;
            font-size: 11px;
        }

        .backup-status.success {
            background: #d4edda;
            border: 1px solid #c3e6cb;
            color: #155724;
        }

        .backup-status.error {
            background: #f8d7da;
            border: 1px solid #f5c6cb;
            color: #721c24;
        }

        .backup-status.info {
            background: #d1ecf1;
            border: 1px solid #bee5eb;
            color: #0c5460;
        }

        .backup-status.warning {
            background: #fff3cd;
            border: 1px solid #ffeaa7;
            color: #856404;
        }

        .backup-progress {
            margin-top: 8px;
        }

        .backup-files-list {
            margin-top: 10px;
        }

        .backup-files-list ul {
            list-style: none;
            padding: 0;
            margin: 0;
        }

        .backup-files-list li {
            padding: 4px 0;
            border-bottom: 1px solid #eee;
            display: flex;
            justify-content: space-between;
            align-items: center;
            font-size: 11px;
        }

        .backup-files-list li:last-child {
            border-bottom: none;
        }

        .download-link {
            color: #007bff;
            text-decoration: none;
            font-size: 10px;
        }

        .download-link:hover {
            text-decoration: underline;
        }

        .spinner {
            display: inline-block;
            width: 12px;
            height: 12px;
            border: 2px solid #f3f3f3;
            border-top: 2px solid #007bff;
            border-radius: 50%;
            animation: spin 1s linear infinite;
        }

        @keyframes spin {
            0% { transform: rotate(0deg); }
            100% { transform: rotate(360deg); }
        }

        /* Restore specific styles */
        .restore-table {
            font-size: 11px;
        }

        .restore-table th {
            background: #f8f9fa;
            font-weight: 600;
            padding: 8px;
        }

        .restore-table td {
            padding: 6px 8px;
            vertical-align: middle;
        }

        .btn-restore {
            background: #28a745;
            border: 1px solid #28a745;
            color: white;
            padding: 4px 8px;
            border-radius: 3px;
            font-size: 10px;
            cursor: pointer;
            margin-right: 4px;
        }

        .btn-restore:hover {
            background: #218838;
        }

        .btn-delete {
            background: #dc3545;
            border: 1px solid #dc3545;
            color: white;
            padding: 4px 8px;
            border-radius: 3px;
            font-size: 10px;
            cursor: pointer;
        }

        .btn-delete:hover {
            background: #c82333;
        }

        .type-badge {
            padding: 2px 6px;
            border-radius: 3px;
            font-size: 9px;
            font-weight: 600;
        }

        .type-database {
            background: #007bff;
            color: white;
        }

        .type-files {
            background: #28a745;
            color: white;
        }

        .refresh-btn {
            background: #6c757d;
            border: 1px solid #6c757d;
            color: white;
            padding: 6px 12px;
            border-radius: 4px;
            font-size: 11px;
            cursor: pointer;
            margin-top: 20px;
            margin-bottom: 15px;
        }

        .refresh-btn:hover {
            background: #5a6268;
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
            <div class="settings-container">
                <!-- Access Info -->
                <div class="access-info">
                    <h6>Administrator Access</h6>
                    <p><strong>Role:</strong> <?= htmlspecialchars($current_user_role) ?></p>
                    <p><strong>Department:</strong> <?= htmlspecialchars($current_user_department) ?></p>
                </div>
                
                <!-- Backup Section -->
                <div class="backup-section">
                    <h3>System Backup & Restore</h3>
                    <p>Create backups of your database and application files, or restore from existing backups.</p>
                    
                    <!-- Tabs -->
                    <ul class="nav nav-tabs backup-tabs" id="backupTabs" role="tablist">
                        <li class="nav-item" role="presentation">
                            <button class="nav-link active" id="backup-tab" data-bs-toggle="tab" data-bs-target="#backup-panel" type="button" role="tab">
                                Create Backup
                            </button>
                        </li>
                        <li class="nav-item" role="presentation">
                            <button class="nav-link" id="restore-tab" data-bs-toggle="tab" data-bs-target="#restore-panel" type="button" role="tab">
                                Restore from Backup
                            </button>
                        </li>
                    </ul>
                    
                    <!-- Tab Content -->
                    <div class="tab-content" id="backupTabContent">
                        <!-- Backup Panel -->
                        <div class="tab-pane fade show active" id="backup-panel" role="tabpanel">
                            <div class="backup-options">
                                <div class="backup-card">
                                    <h5>Database Backup</h5>
                                    <p>Export all database tables and data to a SQL file</p>
                                    <button class="backup-btn" onclick="performBackup('database')">
                                        <img src="icons/backup.svg" alt="Backup" width="12">
                                        Backup Database
                                    </button>
                                </div>
                                
                                <div class="backup-card">
                                    <h5>Files Backup</h5>
                                    <p>Create an archive of all PHP files, CSS, JS, and images</p>
                                    <button class="backup-btn" onclick="performBackup('files')">
                                        <img src="icons/backup.svg" alt="Backup" width="12">
                                        Backup Files
                                    </button>
                                </div>
                                
                                <div class="backup-card">
                                    <h5>Full System Backup</h5>
                                    <p>Complete backup including both database and files</p>
                                    <button class="backup-btn" onclick="performBackup('full')">
                                        <img src="icons/backup.svg" alt="Backup" width="12">
                                        Full Backup
                                    </button>
                                </div>
                            </div>
                        </div>
                        
                        <!-- Restore Panel -->
                        <div class="tab-pane fade" id="restore-panel" role="tabpanel">
                            <button class="refresh-btn" onclick="loadBackupList()">
                                <img src="icons/refresh.svg" alt="Refresh" width="12">
                                Refresh List
                            </button>
                            
                            <div id="backup-list-container">
                                <p>Loading backup files...</p>
                            </div>
                        </div>
                    </div>
                    
                    <div class="backup-status" id="backup-status">
                        <div class="backup-message" id="backup-message"></div>
                        <div class="backup-progress" id="backup-progress" style="display: none;">
                            <div class="progress">
                                <div class="progress-bar progress-bar-striped progress-bar-animated" 
                                     role="progressbar" style="width: 100%"></div>
                            </div>
                        </div>
                        <div class="backup-files-list" id="backup-files" style="display: none;">
                            <strong>Generated Files:</strong>
                            <ul id="backup-files-ul"></ul>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="js/sidebar.js"></script>
    <script src="js/header.js"></script>
    
    <script>
        // Load backup list when restore tab is shown
        document.getElementById('restore-tab').addEventListener('shown.bs.tab', function () {
            loadBackupList();
        });

        // Load backup list on page load if restore tab is active
        document.addEventListener('DOMContentLoaded', function() {
            // Auto-load backup list when page loads
            loadBackupList();
        });

        async function loadBackupList() {
            const container = document.getElementById('backup-list-container');
            container.innerHTML = '<p>Loading backup files...</p>';
            
            try {
                const formData = new FormData();
                formData.append('action', 'list_backups');
                
                const response = await fetch('backup_handler.php', {
                    method: 'POST',
                    body: formData
                });
                
                const result = await response.json();
                
                if (result.success) {
                    if (result.files.length === 0) {
                        container.innerHTML = '<p>No backup files found.</p>';
                    } else {
                        let html = `
                            <table class="table table-striped restore-table">
                                <thead>
                                    <tr>
                                        <th>File Name</th>
                                        <th>Type</th>
                                        <th>Size</th>
                                        <th>Date</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                        `;
                        
                        result.files.forEach(file => {
                            html += `
                                <tr>
                                    <td>${file.name}</td>
                                    <td><span class="type-badge type-${file.type}">${file.type.toUpperCase()}</span></td>
                                    <td>${file.size}</td>
                                    <td>${file.date}</td>
                                    <td>
                                        ${file.type === 'database' ? 
                                            `<button class="btn-restore" onclick="restoreBackup('${file.name}')">Restore</button>` : 
                                            '<span style="font-size: 10px; color: #666; padding: 0px 4px 0px 4px; margin-right: 7px;">Files only</span>'
                                        }
                                        <button class="btn-delete" style="margin: 4px;" onclick="deleteBackup('${file.name}')">Delete</button>
                                        <a style="padding:0px 4px 0px 4px;" href="backups/${file.name}" class="download-link" download>Download</a>
                                    </td>
                                </tr>
                            `;
                        });
                        
                        html += '</tbody></table>';
                        container.innerHTML = html;
                    }
                } else {
                    container.innerHTML = `<p class="text-danger">Error loading backup files: ${result.message}</p>`;
                }
            } catch (error) {
                container.innerHTML = `<p class="text-danger">Error: ${error.message}</p>`;
            }
        }

        async function restoreBackup(filename) {
            if (!confirm(`Are you sure you want to restore the database from "${filename}"?\n\nThis will overwrite all current data. This action cannot be undone.`)) {
                return;
            }
            
            const statusDiv = document.getElementById('backup-status');
            const messageDiv = document.getElementById('backup-message');
            const progressDiv = document.getElementById('backup-progress');
            
            // Show status
            statusDiv.className = 'backup-status info';
            statusDiv.style.display = 'block';
            messageDiv.textContent = 'Starting database restore...';
            progressDiv.style.display = 'block';
            
            try {
                const formData = new FormData();
                formData.append('action', 'restore_database');
                formData.append('filename', filename);
                
                const response = await fetch('backup_handler.php', {
                    method: 'POST',
                    body: formData
                });
                
                const result = await response.json();
                
                progressDiv.style.display = 'none';
                
                if (result.success) {
                    statusDiv.className = 'backup-status success';
                    messageDiv.textContent = result.message;
                } else {
                    statusDiv.className = 'backup-status error';
                    messageDiv.textContent = result.message;
                }
            } catch (error) {
                progressDiv.style.display = 'none';
                statusDiv.className = 'backup-status error';
                messageDiv.textContent = 'Restore failed: ' + error.message;
            }
        }

        async function deleteBackup(filename) {
            if (!confirm(`Are you sure you want to delete "${filename}"?\n\nThis action cannot be undone.`)) {
                return;
            }
            
            try {
                const formData = new FormData();
                formData.append('action', 'delete_backup');
                formData.append('filename', filename);
                
                const response = await fetch('backup_handler.php', {
                    method: 'POST',
                    body: formData
                });
                
                const result = await response.json();
                
                if (result.success) {
                    // Refresh the backup list
                    loadBackupList();
                    
                    // Show success message
                    const statusDiv = document.getElementById('backup-status');
                    const messageDiv = document.getElementById('backup-message');
                    statusDiv.className = 'backup-status success';
                    statusDiv.style.display = 'block';
                    messageDiv.textContent = result.message;
                } else {
                    alert('Delete failed: ' + result.message);
                }
            } catch (error) {
                alert('Error: ' + error.message);
            }
        }

        async function performBackup(type) {
            const statusDiv = document.getElementById('backup-status');
            const messageDiv = document.getElementById('backup-message');
            const progressDiv = document.getElementById('backup-progress');
            const filesDiv = document.getElementById('backup-files');
            const filesUl = document.getElementById('backup-files-ul');
            
            // Hide any previous status
            statusDiv.style.display = 'none';
            
            // Disable all backup buttons
            const buttons = document.querySelectorAll('.backup-btn');
            const originalButtonContents = [];
            
            buttons.forEach((btn, index) => {
                originalButtonContents[index] = btn.innerHTML;
                btn.disabled = true;
                const spinner = document.createElement('span');
                spinner.className = 'spinner';
                btn.innerHTML = '';
                btn.appendChild(spinner);
                btn.appendChild(document.createTextNode(' Processing...'));
            });
            
            // Show status and progress
            statusDiv.className = 'backup-status info';
            statusDiv.style.display = 'block';
            messageDiv.textContent = `Starting ${type} backup...`;
            progressDiv.style.display = 'block';
            filesDiv.style.display = 'none';
            
            try {
                const formData = new FormData();
                formData.append('action', 'backup_' + type);
                
                const response = await fetch('backup_handler.php', {
                    method: 'POST',
                    body: formData,
                    credentials: 'same-origin'
                });
                
                if (!response.ok) {
                    throw new Error(`HTTP error! status: ${response.status}`);
                }
                
                const contentType = response.headers.get('content-type');
                if (!contentType || !contentType.includes('application/json')) {
                    const text = await response.text();
                    throw new Error('Server returned non-JSON response: ' + text);
                }
                
                const result = await response.json();
                
                progressDiv.style.display = 'none';
                
                if (result.success) {
                    statusDiv.className = 'backup-status success';
                    messageDiv.textContent = result.message;
                    
                    if (result.files && result.files.length > 0) {
                        filesDiv.style.display = 'block';
                        filesUl.innerHTML = '';
                        
                        result.files.forEach(file => {
                            const li = document.createElement('li');
                            li.innerHTML = `
                                <span>${file}</span>
                                <a href="backups/${file}" class="download-link" download>Download</a>
                            `;
                            filesUl.appendChild(li);
                        });
                    }
                    
                    // Refresh backup list if restore tab is visible
                    const restoreTab = document.getElementById('restore-panel');
                    if (restoreTab.classList.contains('active')) {
                        setTimeout(() => loadBackupList(), 1000);
                    }
                } else {
                    statusDiv.className = 'backup-status error';
                    messageDiv.textContent = result.message || 'Backup failed';
                }
                
            } catch (error) {
                console.error('Backup error:', error);
                progressDiv.style.display = 'none';
                statusDiv.className = 'backup-status error';
                messageDiv.textContent = 'Error: ' + error.message;
            }
            
            // Re-enable buttons and restore original content
            buttons.forEach((btn, index) => {
                btn.disabled = false;
                btn.innerHTML = originalButtonContents[index];
            });
        }
    </script>
</body>
</html>