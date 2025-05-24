<?php
// Note: This is a utility script to be run once to update all existing passwords.
// Remove or secure this file after running it.

// Include authentication helper
require_once 'google_auth.php';
$auth = new MalayaSolarAuth();

// Database connection
$conn = new mysqli("localhost", "u188693564_adminsolar", "@Malayasolarenergies1", "u188693564_malayasol");

if ($conn->connect_error) {
    die("Database connection failed: " . $conn->connect_error);
}

echo "Starting password hash update...<br>";

// Get all users with un-hashed passwords
$stmt = $conn->prepare("SELECT user_id, username, password FROM users");
$stmt->execute();
$result = $stmt->get_result();
$updated = 0;

while ($user = $result->fetch_assoc()) {
    // Check if password is already hashed
    if (strpos($user['password'], '$2y$') === 0) {
        echo "Password for {$user['username']} is already hashed. Skipping.<br>";
        continue;
    }
    
    // Hash the password
    $hashed_password = $auth->hashPassword($user['password']);
    
    // Update the user record
    $update_stmt = $conn->prepare("UPDATE users SET password = ? WHERE user_id = ?");
    $update_stmt->bind_param("si", $hashed_password, $user['user_id']);
    
    if ($update_stmt->execute()) {
        echo "Updated password for {$user['username']} successfully.<br>";
        $updated++;
    } else {
        echo "Failed to update password for {$user['username']}: " . $update_stmt->error . "<br>";
    }
    
    $update_stmt->close();
}

echo "<br>Password update complete. Updated $updated user passwords.<br>";

$stmt->close();
$conn->close();
?>