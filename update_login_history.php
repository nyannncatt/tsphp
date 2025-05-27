<?php
require_once 'config/database.php';

try {
    // Add logout_time column
    $sql = "ALTER TABLE login_history ADD COLUMN logout_time DATETIME NULL AFTER login_time";
    if ($conn->query($sql)) {
        echo "Successfully added logout_time column to login_history table";
    } else {
        echo "Error adding column: " . $conn->error;
    }
} catch (Exception $e) {
    echo "Error: " . $e->getMessage();
}
?> 