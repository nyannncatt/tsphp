<?php
require_once '../includes/auth_middleware.php';
checkAuth();
require_once '../config/database.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $user_id = $_POST['user_id'];
    $type = $_POST['type'];
    $severity = $_POST['severity'];
    $description = $_POST['description'];
    $reported_by = $_SESSION['user_id'];

    // Validate user is reporting for themselves
    if ($user_id != $reported_by) {
        $_SESSION['error'] = "Invalid user ID.";
        header("Location: " . $_SERVER['HTTP_REFERER']);
        exit();
    }

    // Get user details for logging
    $user_query = "SELECT u.username, u.role,
                  COALESCE(s.first_name, p.first_name) as first_name,
                  COALESCE(s.last_name, p.last_name) as last_name
                  FROM users u
                  LEFT JOIN students s ON u.id = s.user_id
                  LEFT JOIN parents p ON u.id = p.user_id
                  WHERE u.id = ?";
    $user_stmt = $conn->prepare($user_query);
    $user_stmt->bind_param("i", $user_id);
    $user_stmt->execute();
    $user = $user_stmt->get_result()->fetch_assoc();

    // Insert incident report
    $query = "INSERT INTO incidents (user_id, type, severity, description, status, created_at) 
              VALUES (?, ?, ?, ?, 'open', CURRENT_TIMESTAMP)";
    $stmt = $conn->prepare($query);
    $stmt->bind_param("isss", $user_id, $type, $severity, $description);
    
    if ($stmt->execute()) {
        // Log the action
        $action = "Reported incident: " . $user['first_name'] . ' ' . $user['last_name'] . ' (' . $user['username'] . ') - ' . ucfirst($type);
        $log_query = "INSERT INTO system_logs (user_id, action) VALUES (?, ?)";
        $log_stmt = $conn->prepare($log_query);
        $log_stmt->bind_param("is", $reported_by, $action);
        $log_stmt->execute();

        // Log user activity
        logAction("Reported security incident: " . ucfirst($type));

        $_SESSION['success'] = "Incident reported successfully. Our team will review it shortly.";
    } else {
        $_SESSION['error'] = "Error reporting incident. Please try again.";
    }

    // Redirect back to the dashboard
    header("Location: " . $_SERVER['HTTP_REFERER']);
    exit();
} else {
    // If someone tries to access this file directly
    header("Location: ../index.php");
    exit();
} 