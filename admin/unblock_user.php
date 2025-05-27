<?php
require_once '../includes/auth_middleware.php';
checkAuth();
checkRole(['admin']);
require_once '../config/database.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $user_id = $_POST['user_id'];

    // Remove the block
    $delete_query = "DELETE FROM user_blocks WHERE user_id = ?";
    $delete_stmt = $conn->prepare($delete_query);
    $delete_stmt->bind_param("i", $user_id);
    $delete_stmt->execute();

    // Log the action
    $log_query = "INSERT INTO system_logs (user_id, action) VALUES (?, 'Unblocked user ID: $user_id')";
    $log_stmt = $conn->prepare($log_query);
    $log_stmt->bind_param("i", $_SESSION['user_id']);
    $log_stmt->execute();

    header("Location: dashboard.php?success=user_unblocked");
    exit();
} else {
    header("Location: dashboard.php");
    exit();
} 