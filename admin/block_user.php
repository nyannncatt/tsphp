<?php
require_once '../includes/auth_middleware.php';
checkAuth();
checkRole(['admin']);
require_once '../config/database.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $user_id = $_POST['user_id'];
    $block_date = $_POST['block_date'];
    $block_hour = $_POST['block_hour'];
    $block_minute = $_POST['block_minute'];
    $reason = $_POST['reason'];
    $blocked_by = $_SESSION['user_id'];

    // Combine date and time
    $block_until = $block_date . ' ' . $block_hour . ':' . $block_minute . ':00';

    // Insert block record
    $query = "INSERT INTO user_blocks (user_id, blocked_by, block_until, reason) VALUES (?, ?, ?, ?)";
    $stmt = $conn->prepare($query);
    $stmt->bind_param("iiss", $user_id, $blocked_by, $block_until, $reason);
    
    if ($stmt->execute()) {
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

        // Log the action with user's full name
        $action = "Blocked user: " . $user['first_name'] . ' ' . $user['last_name'] . ' (' . $user['username'] . ') until ' . date('M d, Y h:i A', strtotime($block_until));
        $log_query = "INSERT INTO system_logs (user_id, action) VALUES (?, ?)";
        $log_stmt = $conn->prepare($log_query);
        $log_stmt->bind_param("is", $blocked_by, $action);
        $log_stmt->execute();

        // Log user activity with full name
        logAction("Blocked user: " . $user['first_name'] . ' ' . $user['last_name'] . ' (' . $user['username'] . ') until ' . date('M d, Y h:i A', strtotime($block_until)));

        $_SESSION['success'] = "User blocked successfully.";
    } else {
        $_SESSION['error'] = "Error blocking user.";
    }

    header("Location: dashboard.php");
    exit();
} 