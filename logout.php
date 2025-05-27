<?php
session_start();
require_once 'config/database.php';

// Debug information
error_log("Logout process started");

if (isset($_SESSION['user_id'])) {
    $user_id = $_SESSION['user_id'];
    error_log("User ID for logout: " . $user_id);

    // First, check if the user_activity table exists
    $check_table = "SHOW TABLES LIKE 'user_activity'";
    $table_exists = $conn->query($check_table);
    
    if ($table_exists->num_rows == 0) {
        // Create the table if it doesn't exist
        $create_table = "
        CREATE TABLE user_activity (
            id INT AUTO_INCREMENT PRIMARY KEY,
            user_id INT NOT NULL,
            action VARCHAR(50) NOT NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (user_id) REFERENCES users(id)
        )";
        $conn->query($create_table);
        error_log("Created user_activity table");
    }

    // Insert logout record
    $insert_query = "INSERT INTO user_activity (user_id, action) VALUES (?, 'logout')";
    $stmt = $conn->prepare($insert_query);
    
    if ($stmt) {
        $stmt->bind_param("i", $user_id);
        $success = $stmt->execute();
        
        if ($success) {
            error_log("Successfully recorded logout for user: " . $user_id);
            
            // Verify the insertion
            $verify_query = "SELECT * FROM user_activity WHERE user_id = ? AND action = 'logout' ORDER BY created_at DESC LIMIT 1";
            $verify_stmt = $conn->prepare($verify_query);
            $verify_stmt->bind_param("i", $user_id);
            $verify_stmt->execute();
            $result = $verify_stmt->get_result();
            
            if ($result->num_rows > 0) {
                $record = $result->fetch_assoc();
                error_log("Verified logout record: " . json_encode($record));
            } else {
                error_log("Failed to verify logout record");
            }
        } else {
            error_log("Failed to record logout: " . $stmt->error);
        }
    } else {
        error_log("Failed to prepare logout statement: " . $conn->error);
    }
} else {
    error_log("No user ID found in session");
}

// Clear all session variables
$_SESSION = array();

// Destroy the session
session_destroy();

// Redirect to login page
header("Location: login.php");
exit();
?> 