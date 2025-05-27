<?php
session_start();
require_once '../config/database.php';

if (isset($_SESSION['user_id'])) {
    try {
        // First check if logout_time column exists
        $check_column = "SHOW COLUMNS FROM login_history LIKE 'logout_time'";
        $result = $conn->query($check_column);
        
        if ($result->num_rows == 0) {
            // Column doesn't exist, add it
            $alter_table = "ALTER TABLE login_history ADD COLUMN logout_time DATETIME NULL AFTER login_time";
            $conn->query($alter_table);
        }

        // Now update the most recent successful login record with logout time
        $update_query = "UPDATE login_history 
                        SET logout_time = CURRENT_TIMESTAMP 
                        WHERE user_id = ? 
                        AND status = 'success' 
                        AND (logout_time IS NULL OR logout_time = '') 
                        ORDER BY login_time DESC 
                        LIMIT 1";
        $stmt = $conn->prepare($update_query);
        $stmt->bind_param("i", $_SESSION['user_id']);
        $stmt->execute();
    } catch (Exception $e) {
        // Log the error but continue with logout
        error_log("Logout error: " . $e->getMessage());
    }
}

// Destroy the session
session_destroy();

// Redirect to login page
header("Location: login.php");
exit();
?> 