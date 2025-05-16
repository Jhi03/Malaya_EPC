<?php
session_start();
// Database connection
$host = 'localhost';
$user = 'root';
$password = '';
$database = 'malayasol';
$conn = new mysqli($host, $user, $password, $database);

// Check connection
if ($conn->connect_error) {
    die(json_encode(['success' => false, 'message' => 'Connection failed: ' . $conn->connect_error]));
}

if (isset($_POST['record_id'])) {
    $record_id = intval($_POST['record_id']);
    
    $stmt = $conn->prepare("
        SELECT 
            e.created_by, 
            e.creation_date, 
            e.edited_by, 
            e.edit_date
        FROM expense e
        WHERE e.record_id = ?
    ");
    
    $stmt->bind_param("i", $record_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($row = $result->fetch_assoc()) {
        // Get creator name
        $created_by_name = "Unknown";
        if ($row['created_by']) {
            $creator_stmt = $conn->prepare("
                SELECT e.first_name, e.last_name 
                FROM users u
                LEFT JOIN employee e ON u.employee_id = e.employee_id 
                WHERE u.user_id = ?
            ");
            $creator_stmt->bind_param("i", $row['created_by']);
            $creator_stmt->execute();
            $creator_stmt->bind_result($first_name, $last_name);
            if ($creator_stmt->fetch()) {
                $created_by_name = "$first_name $last_name";
            }
            $creator_stmt->close();
        }
        
        // Get editor name
        $edited_by_name = "N/A";
        if ($row['edited_by']) {
            $editor_stmt = $conn->prepare("
                SELECT e.first_name, e.last_name 
                FROM users u
                LEFT JOIN employee e ON u.employee_id = e.employee_id 
                WHERE u.user_id = ?
            ");
            $editor_stmt->bind_param("i", $row['edited_by']);
            $editor_stmt->execute();
            $editor_stmt->bind_result($first_name, $last_name);
            if ($editor_stmt->fetch()) {
                $edited_by_name = "$first_name $last_name";
            }
            $editor_stmt->close();
        }
        
        echo json_encode([
            'success' => true,
            'created_by_name' => $created_by_name,
            'creation_date' => $row['creation_date'],
            'edited_by_name' => $edited_by_name,
            'edit_date' => $row['edit_date']
        ]);
    } else {
        echo json_encode(['success' => false, 'message' => 'Record not found']);
    }
    
    $stmt->close();
} else {
    echo json_encode(['success' => false, 'message' => 'Record ID not provided']);
}

$conn->close();
?>