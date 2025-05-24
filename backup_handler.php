<?php
session_start();

// Set proper headers
header('Content-Type: application/json');
header('Cache-Control: no-cache, must-revalidate');
header('Expires: Thu, 01 Jan 1970 00:00:00 GMT');

require_once 'backup_config.php';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Unauthorized access']);
    exit();
}

// Only process POST requests with action parameter
if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !isset($_POST['action'])) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Invalid request']);
    exit();
}

$action = $_POST['action'];

try {
    switch ($action) {
        case 'backup_database':
            $result = backupDatabase();
            break;
        case 'backup_files':
            $result = backupFiles();
            break;
        case 'backup_full':
            $db_result = backupDatabase();
            if (!$db_result['success']) {
                $result = $db_result;
                break;
            }
            
            $files_result = backupFiles();
            $result = [
                'success' => $db_result['success'] && $files_result['success'],
                'message' => $db_result['message'] . ' ' . $files_result['message'],
                'files' => array_merge($db_result['files'] ?? [], $files_result['files'] ?? [])
            ];
            break;
        default:
            $result = ['success' => false, 'message' => 'Invalid action'];
    }
    
    echo json_encode($result);
    
} catch (Exception $e) {
    error_log("Backup error: " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'Error: ' . $e->getMessage()]);
}

// Make sure we exit cleanly
exit();

function backupDatabase() {
    global $backup_config, $backup_dir;
    
    $timestamp = date('Y-m-d_H-i-s');
    $filename = "database_backup_{$timestamp}.sql";
    $filepath = $backup_dir . $filename;
    
    try {
        $conn = new mysqli(
            $backup_config['host'],
            $backup_config['username'],
            $backup_config['password'],
            $backup_config['database']
        );
        
        if ($conn->connect_error) {
            throw new Exception("Database connection failed: " . $conn->connect_error);
        }
        
        $sql_content = "-- Database Backup\n";
        $sql_content .= "-- Generated on: " . date('Y-m-d H:i:s') . "\n";
        $sql_content .= "-- Database: {$backup_config['database']}\n\n";
        
        // Get all tables
        $tables_result = $conn->query("SHOW TABLES");
        if (!$tables_result) {
            throw new Exception("Failed to get tables: " . $conn->error);
        }
        
        $tables = [];
        while ($row = $tables_result->fetch_array()) {
            $tables[] = $row[0];
        }
        
        if (empty($tables)) {
            throw new Exception("No tables found in database");
        }
        
        foreach ($tables as $table) {
            // Get table structure
            $sql_content .= "\n-- Table structure for `$table`\n";
            $sql_content .= "DROP TABLE IF EXISTS `$table`;\n";
            
            $create_result = $conn->query("SHOW CREATE TABLE `$table`");
            if (!$create_result) {
                throw new Exception("Failed to get table structure for $table: " . $conn->error);
            }
            
            $create_row = $create_result->fetch_array();
            $sql_content .= $create_row[1] . ";\n\n";
            
            // Get table data
            $sql_content .= "-- Dumping data for table `$table`\n";
            $data_result = $conn->query("SELECT * FROM `$table`");
            
            if ($data_result && $data_result->num_rows > 0) {
                while ($row = $data_result->fetch_assoc()) {
                    $sql_content .= "INSERT INTO `$table` VALUES (";
                    $values = [];
                    foreach ($row as $value) {
                        if ($value === null) {
                            $values[] = "NULL";
                        } else {
                            $values[] = "'" . $conn->real_escape_string($value) . "'";
                        }
                    }
                    $sql_content .= implode(', ', $values) . ");\n";
                }
            }
            $sql_content .= "\n";
        }
        
        $conn->close();
        
        // Write to file
        if (file_put_contents($filepath, $sql_content) === false) {
            throw new Exception("Failed to write database backup file");
        }
        
        return [
            'success' => true,
            'message' => 'Database backup created successfully.',
            'files' => [$filename],
            'size' => formatBytes(filesize($filepath))
        ];
        
    } catch (Exception $e) {
        return [
            'success' => false,
            'message' => 'Database backup failed: ' . $e->getMessage()
        ];
    }
}

function backupFiles() {
    global $backup_paths, $backup_dir;
    
    $timestamp = date('Y-m-d_H-i-s');
    $filename = "files_backup_{$timestamp}.zip";
    $filepath = $backup_dir . $filename;
    
    try {
        if (!class_exists('ZipArchive')) {
            throw new Exception("ZipArchive class not available");
        }
        
        $zip = new ZipArchive();
        $result = $zip->open($filepath, ZipArchive::CREATE);
        if ($result !== TRUE) {
            throw new Exception("Cannot create zip file. Error code: $result");
        }
        
        $root_path = realpath('./');
        $files_added = 0;
        
        // Add files and directories
        foreach ($backup_paths['files'] as $pattern) {
            if (is_dir($pattern)) {
                // Add directory recursively
                $files_added += addDirectoryToZip($zip, $pattern, $root_path);
            } else {
                // Add files by pattern
                $files = glob($pattern);
                foreach ($files as $file) {
                    if (is_file($file) && !shouldExclude($file)) {
                        $zip->addFile($file, $file);
                        $files_added++;
                    }
                }
            }
        }
        
        $zip->close();
        
        if ($files_added === 0) {
            if (file_exists($filepath)) {
                unlink($filepath);
            }
            throw new Exception("No files were added to backup");
        }
        
        return [
            'success' => true,
            'message' => "Files backup created successfully ($files_added files).",
            'files' => [$filename],
            'size' => formatBytes(filesize($filepath))
        ];
        
    } catch (Exception $e) {
        return [
            'success' => false,
            'message' => 'Files backup failed: ' . $e->getMessage()
        ];
    }
}

function addDirectoryToZip($zip, $dir, $root_path) {
    $files_added = 0;
    
    if (!is_dir($dir)) {
        return 0;
    }
    
    $iterator = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($dir));
    
    foreach ($iterator as $file) {
        if ($file->isFile() && !shouldExclude($file->getPathname())) {
            $relativePath = str_replace($root_path . DIRECTORY_SEPARATOR, '', $file->getPathname());
            $relativePath = str_replace('\\', '/', $relativePath);
            $zip->addFile($file->getPathname(), $relativePath);
            $files_added++;
        }
    }
    
    return $files_added;
}

function shouldExclude($filepath) {
    global $backup_paths;
    
    foreach ($backup_paths['exclude'] as $exclude_pattern) {
        if (strpos($filepath, $exclude_pattern) !== false) {
            return true;
        }
    }
    
    return false;
}

function formatBytes($size, $precision = 2) {
    if ($size <= 0) return '0 B';
    
    $units = array('B', 'KB', 'MB', 'GB', 'TB');
    $base = log($size, 1024);
    return round(pow(1024, $base - floor($base)), $precision) . ' ' . $units[floor($base)];
}
?>