<?php
require_once '../includes/auth_middleware.php';
checkAuth();
checkRole(['admin']);
require_once '../config/database.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $incident_id = $_POST['incident_id'];
    $status = $_POST['status'];

    // Update the incident status
    $update_query = "UPDATE incidents SET status = ? WHERE id = ?";
    $stmt = $conn->prepare($update_query);
    $stmt->bind_param("si", $status, $incident_id);
    $stmt->execute();

    // Log the action
    $log_query = "INSERT INTO system_logs (user_id, action) VALUES (?, 'Updated incident status')";
    $log_stmt = $conn->prepare($log_query);
    $log_stmt->bind_param("i", $_SESSION['user_id']);
    $log_stmt->execute();

    // Get updated counts
    $stats_query = "SELECT 
        COUNT(*) as total,
        SUM(CASE WHEN status = 'open' THEN 1 ELSE 0 END) as open_count,
        SUM(CASE WHEN status = 'investigating' THEN 1 ELSE 0 END) as investigating_count,
        SUM(CASE WHEN status = 'resolved' THEN 1 ELSE 0 END) as resolved_count
    FROM incidents";
    $stats = $conn->query($stats_query)->fetch_assoc();

    // Return JSON response
    header('Content-Type: application/json');
    echo json_encode([
        'success' => true,
        'counts' => [
            'open' => (int)$stats['open_count'],
            'investigating' => (int)$stats['investigating_count'],
            'resolved' => (int)$stats['resolved_count']
        ]
    ]);
} else {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'Invalid request method']);
} 