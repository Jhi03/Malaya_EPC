<?php
// Database update script for Malaya Solar Accounting System
// This script will:
// 1. Remove YubiKey fields from users table
// 2. Update all passwords to be hashed if they aren't already
// 3. Ensure proper structure for GoogleAuthenticator

// Database connection
$conn = new mysqli("localhost", "root", "", "malayasol");
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Set to display errors during development
ini_set('display_errors', 1);
error_reporting(E_ALL);

echo "<h1>Malaya Solar Database Update</h1>";

// STEP 1: Check if yubikey_id field exists before trying to remove it
$check_yubikey = $conn->query("SHOW COLUMNS FROM users LIKE 'yubikey_id'");
if ($check_yubikey->num_rows > 0) {
    // Remove yubikey_id field
    $sql = "ALTER TABLE users DROP COLUMN yubikey_id";
    if ($conn->query($sql)) {
        echo "<p>✅ Successfully removed yubikey_id field</p>";
    } else {
        echo "<p>❌ Error removing yubikey_id field: " . $conn->error . "</p>";
    }
} else {
    echo "<p>ℹ️ yubikey_id field does not exist, no need to remove</p>";
}

// STEP 2: Remove preferred_2fa if it references 'yubikey'
$check_preferred_2fa = $conn->query("SHOW COLUMNS FROM users LIKE 'preferred_2fa'");
if ($check_preferred_2fa->num_rows > 0) {
    // Change enum to only include 'authenticator'
    $sql = "ALTER TABLE users CHANGE preferred_2fa preferred_2fa ENUM('authenticator') DEFAULT NULL";
    if ($conn->query($sql)) {
        echo "<p>✅ Successfully updated preferred_2fa field</p>";
    } else {
        echo "<p>❌ Error updating preferred_2fa field: " . $conn->error . "</p>";
    }
} else {
    // Create preferred_2fa field if it doesn't exist
    $sql = "ALTER TABLE users ADD COLUMN preferred_2fa ENUM('authenticator') DEFAULT NULL";
    if ($conn->query($sql)) {
        echo "<p>✅ Successfully added preferred_2fa field</p>";
    } else {
        echo "<p>❌ Error adding preferred_2fa field: " . $conn->error . "</p>";
    }
}

// STEP 3: Add authenticator_secret field if it doesn't exist
$check_auth_secret = $conn->query("SHOW COLUMNS FROM users LIKE 'authenticator_secret'");
if ($check_auth_secret->num_rows == 0) {
    $sql = "ALTER TABLE users ADD COLUMN authenticator_secret VARCHAR(100) DEFAULT NULL";
    if ($conn->query($sql)) {
        echo "<p>✅ Successfully added authenticator_secret field</p>";
    } else {
        echo "<p>❌ Error adding authenticator_secret field: " . $conn->error . "</p>";
    }
} else {
    echo "<p>ℹ️ authenticator_secret field already exists</p>";
}

// STEP 4: Hash all plaintext passwords
$sql = "SELECT user_id, username, password FROM users";
$result = $conn->query($sql);

if ($result->num_rows > 0) {
    echo "<h2>Updating Passwords</h2>";
    echo "<table border='1'><tr><th>Username</th><th>Status</th></tr>";
    
    while ($row = $result->fetch_assoc()) {
        $user_id = $row["user_id"];
        $username = $row["username"];
        $password = $row["password"];
        
        // Skip if password is already hashed (longer than 40 chars is likely hashed)
        if (strlen($password) > 40 && substr($password, 0, 4) === '$2y$') {
            echo "<tr><td>$username</td><td>Already hashed</td></tr>";
            continue;
        }
        
        // Hash the password
        $hashed_password = password_hash($password, PASSWORD_DEFAULT);
        
        // Update the user record
        $update_sql = "UPDATE users SET password = ? WHERE user_id = ?";
        $stmt = $conn->prepare($update_sql);
        $stmt->bind_param("si", $hashed_password, $user_id);
        
        if ($stmt->execute()) {
            echo "<tr><td>$username</td><td>Updated to hashed password</td></tr>";
        } else {
            echo "<tr><td>$username</td><td>Error: " . $stmt->error . "</td></tr>";
        }
        
        $stmt->close();
    }
    
    echo "</table>";
} else {
    echo "<p>No users found in the database</p>";
}

// STEP 5: Ensure the right account_status field exists
$check_status = $conn->query("SHOW COLUMNS FROM users LIKE 'account_status'");
if ($check_status->num_rows == 0) {
    $sql = "ALTER TABLE users ADD COLUMN account_status ENUM('new','active','locked','disabled') NOT NULL DEFAULT 'active'";
    if ($conn->query($sql)) {
        echo "<p>✅ Successfully added account_status field</p>";
    } else {
        echo "<p>❌ Error adding account_status field: " . $conn->error . "</p>";
    }
} else {
    echo "<p>ℹ️ account_status field already exists</p>";
}

// STEP 6: Ensure the failed_attempts field exists
$check_attempts = $conn->query("SHOW COLUMNS FROM users LIKE 'failed_attempts'");
if ($check_attempts->num_rows == 0) {
    $sql = "ALTER TABLE users ADD COLUMN failed_attempts INT NOT NULL DEFAULT 0";
    if ($conn->query($sql)) {
        echo "<p>✅ Successfully added failed_attempts field</p>";
    } else {
        echo "<p>❌ Error adding failed_attempts field: " . $conn->error . "</p>";
    }
} else {
    echo "<p>ℹ️ failed_attempts field already exists</p>";
}

// STEP 7: Ensure the last_login field exists
$check_last_login = $conn->query("SHOW COLUMNS FROM users LIKE 'last_login'");
if ($check_last_login->num_rows == 0) {
    $sql = "ALTER TABLE users ADD COLUMN last_login DATETIME DEFAULT NULL";
    if ($conn->query($sql)) {
        echo "<p>✅ Successfully added last_login field</p>";
    } else {
        echo "<p>❌ Error adding last_login field: " . $conn->error . "</p>";
    }
} else {
    echo "<p>ℹ️ last_login field already exists</p>";
}

// STEP 8: Update 'status' field in employee table to 'employment_status' for better clarity
$check_employee_status = $conn->query("SHOW COLUMNS FROM employee LIKE 'status'");
if ($check_employee_status->num_rows > 0) {
    // Check if employment_status already exists
    $check_employment_status = $conn->query("SHOW COLUMNS FROM employee LIKE 'employment_status'");
    if ($check_employment_status->num_rows == 0) {
        $sql = "ALTER TABLE employee CHANGE status employment_status VARCHAR(20) NOT NULL DEFAULT 'active'";
        if ($conn->query($sql)) {
            echo "<p>✅ Successfully renamed 'status' to 'employment_status' in employee table</p>";
        } else {
            echo "<p>❌ Error renaming 'status' to 'employment_status' field: " . $conn->error . "</p>";
        }
    } else {
        echo "<p>⚠️ Both 'status' and 'employment_status' fields exist in employee table. Please manually review.</p>";
    }
} else {
    echo "<p>ℹ️ 'status' field doesn't exist in employee table, might already be renamed</p>";
}

// STEP 9: Make sure we have a login_attempts table to track logins
$check_login_attempts = $conn->query("SHOW TABLES LIKE 'login_attempts'");
if ($check_login_attempts->num_rows == 0) {
    $sql = "CREATE TABLE login_attempts (
        id INT AUTO_INCREMENT PRIMARY KEY,
        user_id INT NOT NULL,
        attempt_time DATETIME NOT NULL,
        ip_address VARCHAR(45) NOT NULL,
        success BOOLEAN NOT NULL DEFAULT 0,
        notes VARCHAR(255),
        FOREIGN KEY (user_id) REFERENCES users(user_id)
    )";
    if ($conn->query($sql)) {
        echo "<p>✅ Successfully created login_attempts table</p>";
    } else {
        echo "<p>❌ Error creating login_attempts table: " . $conn->error . "</p>";
    }
} else {
    echo "<p>ℹ️ login_attempts table already exists</p>";
}

// STEP 10: Make sure we have security_questions table
$check_security_questions = $conn->query("SHOW TABLES LIKE 'security_questions'");
if ($check_security_questions->num_rows == 0) {
    $sql = "CREATE TABLE security_questions (
        question_id INT AUTO_INCREMENT PRIMARY KEY,
        question_text VARCHAR(255) NOT NULL
    )";
    if ($conn->query($sql)) {
        echo "<p>✅ Successfully created security_questions table</p>";
        
        // Insert some default questions
        $questions = [
            "What was your childhood nickname?",
            "What was the name of your elementary school?",
            "What was the make of your first car?",
            "What is your favorite movie?",
            "What is your mother's maiden name?",
            "What street did you grow up on?"
        ];
        
        $insert_stmt = $conn->prepare("INSERT INTO security_questions (question_text) VALUES (?)");
        foreach ($questions as $question) {
            $insert_stmt->bind_param("s", $question);
            $insert_stmt->execute();
        }
        $insert_stmt->close();
        
        echo "<p>✅ Added default security questions</p>";
    } else {
        echo "<p>❌ Error creating security_questions table: " . $conn->error . "</p>";
    }
} else {
    echo "<p>ℹ️ security_questions table already exists</p>";
}

// STEP 11: Make sure we have user_security_answers table
$check_security_answers = $conn->query("SHOW TABLES LIKE 'user_security_answers'");
if ($check_security_answers->num_rows == 0) {
    $sql = "CREATE TABLE user_security_answers (
        answer_id INT AUTO_INCREMENT PRIMARY KEY,
        user_id INT NOT NULL,
        question_id INT NOT NULL,
        answer_hash VARCHAR(255) NOT NULL,
        FOREIGN KEY (user_id) REFERENCES users(user_id),
        FOREIGN KEY (question_id) REFERENCES security_questions(question_id)
    )";
    if ($conn->query($sql)) {
        echo "<p>✅ Successfully created user_security_answers table</p>";
    } else {
        echo "<p>❌ Error creating user_security_answers table: " . $conn->error . "</p>";
    }
} else {
    echo "<p>ℹ️ user_security_answers table already exists</p>";
}

// STEP 12: Make sure we have security columns in users table
$columns_to_check = ['security_question1', 'security_answer1', 'security_question2', 'security_answer2'];
foreach ($columns_to_check as $column) {
    $check_column = $conn->query("SHOW COLUMNS FROM users LIKE '$column'");
    if ($check_column->num_rows == 0) {
        $sql = "ALTER TABLE users ADD COLUMN $column VARCHAR(255) DEFAULT NULL";
        if ($conn->query($sql)) {
            echo "<p>✅ Successfully added $column field</p>";
        } else {
            echo "<p>❌ Error adding $column field: " . $conn->error . "</p>";
        }
    } else {
        echo "<p>ℹ️ $column field already exists</p>";
    }
}

// Close connection
$conn->close();

echo "<h2>Database Update Complete!</h2>";
echo "<p>Your database has been updated successfully. You can now use Google Authenticator for 2FA.</p>";
echo "<p><a href='ms_login.php'>Go to Login Page</a></p>";
?>