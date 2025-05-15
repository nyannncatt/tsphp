<?php
require_once 'config/database.php';

if ($conn) {
    echo "Database connection successful!";
    
    // Test query
    $result = $conn->query("SHOW TABLES");
    
    if ($result) {
        echo "<br><br>Available tables:<br>";
        while ($row = $result->fetch_array()) {
            echo "- " . $row[0] . "<br>";
        }
    }
} else {
    echo "Connection failed!";
}
?> 