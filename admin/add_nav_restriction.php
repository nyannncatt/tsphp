<?php
require_once '../includes/auth_middleware.php';
checkAuth();
checkRole(['admin']);
require_once '../config/database.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $user_id = $_POST['user_id'];
    $restricted_pages = isset($_POST['restricted_pages']) ? implode(',', $_POST['restricted_pages']) : '';

    // Check if restriction already exists
    $check_query = "SELECT id FROM nav_restrictions WHERE user_id = ?";
    $check_stmt = $conn->prepare($check_query);
    $check_stmt->bind_param("i", $user_id);
    $check_stmt->execute();
    $result = $check_stmt->get_result();

    if ($result->num_rows > 0) {
        // Update existing restriction
        $update_query = "UPDATE nav_restrictions SET restricted_pages = ? WHERE user_id = ?";
        $update_stmt = $conn->prepare($update_query);
        $update_stmt->bind_param("si", $restricted_pages, $user_id);
        $update_stmt->execute();
    } else {
        // Insert new restriction
        $insert_query = "INSERT INTO nav_restrictions (user_id, restricted_pages) VALUES (?, ?)";
        $insert_stmt = $conn->prepare($insert_query);
        $insert_stmt->bind_param("is", $user_id, $restricted_pages);
        $insert_stmt->execute();
    }

    // Log the action
    $log_query = "INSERT INTO system_logs (user_id, action) VALUES (?, 'Added/Updated navigation restrictions')";
    $log_stmt = $conn->prepare($log_query);
    $log_stmt->bind_param("i", $_SESSION['user_id']);
    $log_stmt->execute();

    header("Location: dashboard.php?success=restriction_added");
    exit();
} else {
    header("Location: dashboard.php");
    exit();
} 