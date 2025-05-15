<?php
require_once '../includes/auth_middleware.php';
checkAuth();
checkRole(['admin']);
require_once '../config/database.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $sender_id = $_SESSION['user_id'];
    $recipient_id = isset($_POST['recipient_id']) ? intval($_POST['recipient_id']) : 0;
    $subject = trim($_POST['subject']);
    $message = trim($_POST['message']);
    
    // Validate input
    if (empty($recipient_id) || empty($subject) || empty($message)) {
        header("Location: messages.php?error=All fields are required");
        exit();
    }
    
    // Verify recipient exists and is a student or parent
    $query = "SELECT role FROM users WHERE id = ?";
    $stmt = $conn->prepare($query);
    $stmt->bind_param("i", $recipient_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $recipient = $result->fetch_assoc();
    
    if (!$recipient || !in_array($recipient['role'], ['student', 'parent'])) {
        header("Location: messages.php?error=Invalid recipient");
        exit();
    }
    
    try {
        // Insert the message
        $query = "INSERT INTO messages (sender_id, receiver_id, subject, message, created_at) 
                 VALUES (?, ?, ?, ?, NOW())";
        $stmt = $conn->prepare($query);
        $stmt->bind_param("iiss", $sender_id, $recipient_id, $subject, $message);
        $stmt->execute();
        
        header("Location: messages.php?success=1");
        exit();
        
    } catch (Exception $e) {
        header("Location: messages.php?error=" . urlencode($e->getMessage()));
        exit();
    }
} else {
    header("Location: messages.php");
    exit();
} 