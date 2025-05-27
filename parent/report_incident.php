<?php
require_once '../includes/auth_middleware.php';
checkAuth();
checkRole(['parent']);
require_once '../config/database.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $type = $_POST['type'];
    $severity = $_POST['severity'];
    $description = $_POST['description'];
    $user_id = $_SESSION['user_id'];

    // Insert the incident
    $insert_query = "INSERT INTO incidents (user_id, type, severity, description, status) 
                    VALUES (?, ?, ?, ?, 'open')";
    $stmt = $conn->prepare($insert_query);
    $stmt->bind_param("isss", $user_id, $type, $severity, $description);
    
    if ($stmt->execute()) {
        // Log the action
        $log_query = "INSERT INTO system_logs (user_id, action) VALUES (?, 'Reported incident: $type')";
        $log_stmt = $conn->prepare($log_query);
        $log_stmt->bind_param("i", $user_id);
        $log_stmt->execute();

        header("Location: dashboard.php?success=incident_reported");
        exit();
    } else {
        header("Location: dashboard.php?error=incident_report_failed");
        exit();
    }
} else {
    header("Location: dashboard.php");
    exit();
} 