<?php
    include('validate_login.php');
    require_once 'activity_logger.php';
    
    // Additional verification for settings access (belt and suspenders approach)
    $current_user_department = getCurrentUserDepartment();
    $current_user_role = getCurrentUserRole();
    
    if ($current_user_department !== 'IT Infrastructure & Cybersecurity Division' || 
        !in_array(strtolower($current_user_role), ['superadmin', 'admin'])) {
        
        // Log unauthorized access attempt
        logUserActivity('access_denied', 'ms_settings.php', 'Unauthorized settings access attempt');
        
        header("Location: access_denied.php");
        exit();
    }
    
    // Log successful settings access
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
            overflow: hidden;
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
            height: 100%;
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
            flex: 1;
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

        .backup-options {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 15px;
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

        .backup-card h4 {
            margin-bottom: 8px;
            color: #333;
            font-size: 14px;
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
                <!-- Backup Section -->
                <div class="backup-section">
                    <h3>SYSTEM BACKUP</h3>
                    <p>Create backups of your database and application files to ensure data safety.</p>
                    
                    <div class="backup-options">
                        <div class="backup-card">
                            <h4>Database Backup</h4>
                            <p>Export all database tables and data to a SQL file</p>
                            <button class="backup-btn" onclick="performBackup('database')">
                                <img src="icons/backup.svg" alt="Backup" width="12">
                                Backup Database
                            </button>
                        </div>
                        
                        <div class="backup-card">
                            <h4>Files Backup</h4>
                            <p>Create an archive of all PHP files, CSS, JS, and images</p>
                            <button class="backup-btn" onclick="performBackup('files')">
                                <img src="icons/backup.svg" alt="Backup" width="12">
                                Backup Files
                            </button>
                        </div>
                        
                        <div class="backup-card">
                            <h4>Full System Backup</h4>
                            <p>Complete backup including both database and files</p>
                            <button class="backup-btn" onclick="performBackup('full')">
                                <img src="icons/backup.svg" alt="Backup" width="12">
                                Full Backup
                            </button>
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
                // Store original content
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
                    credentials: 'same-origin' // Ensure session is included
                });
                
                // Check if response is ok
                if (!response.ok) {
                    throw new Error(`HTTP error! status: ${response.status}`);
                }
                
                const contentType = response.headers.get('content-type');
                if (!contentType || !contentType.includes('application/json')) {
                    const text = await response.text();
                    throw new Error('Server returned non-JSON response: ' + text);
                }
                
                const result = await response.json();
                
                // Hide progress
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
            
            // Keep status visible - don't hide it
            // The status will remain on screen until the next backup operation
        }

        // Prevent any form submissions on the page
        document.addEventListener('DOMContentLoaded', function() {
            // Prevent any forms from submitting
            const forms = document.querySelectorAll('form');
            forms.forEach(form => {
                form.addEventListener('submit', function(e) {
                    e.preventDefault();
                    return false;
                });
            });
            
            // Prevent page refresh on any button clicks
            document.addEventListener('click', function(e) {
                if (e.target.type === 'submit') {
                    e.preventDefault();
                    return false;
                }
            });
        });
    </script>
</body>
</html>