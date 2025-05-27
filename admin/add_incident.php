<?php
require_once '../includes/auth_middleware.php';
checkAuth();
checkRole(['admin']);
require_once '../config/database.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $type = $_POST['type'];
    $severity = $_POST['severity'];
    $description = $_POST['description'];
    $user_id = $_SESSION['user_id'];

    // Insert incident
    $query = "INSERT INTO incidents (user_id, type, description, severity, status) VALUES (?, ?, ?, ?, 'open')";
    $stmt = $conn->prepare($query);
    $stmt->bind_param("isss", $user_id, $type, $description, $severity);
    
    if ($stmt->execute()) {
        $incident_id = $conn->insert_id;
        
        // Log the action
        $action = "Reported new incident: " . $type;
        $log_query = "INSERT INTO system_logs (user_id, action, details) VALUES (?, ?, ?)";
        $log_stmt = $conn->prepare($log_query);
        $log_stmt->bind_param("iss", $user_id, $action, $description);
        $log_stmt->execute();

        // Log user activity
        logAction("Created new " . $severity . " severity incident: " . $type);

        $_SESSION['success'] = "Incident reported successfully.";
    } else {
        $_SESSION['error'] = "Error reporting incident.";
    }

    header("Location: dashboard.php");
    exit();
} 