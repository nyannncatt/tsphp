<?php
function sendMessage($conn, $sender_id, $receiver_id, $subject, $message) {
    $sql = "INSERT INTO messages (sender_id, receiver_id, subject, message) VALUES (?, ?, ?, ?)";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("iiss", $sender_id, $receiver_id, $subject, $message);
    return $stmt->execute();
}

function getInboxMessages($conn, $user_id) {
    $sql = "SELECT m.*, 
            CONCAT(s.first_name, ' ', s.last_name) as sender_student_name,
            CONCAT(p.first_name, ' ', p.last_name) as sender_parent_name,
            u.username as sender_username,
            u.role as sender_role
            FROM messages m 
            LEFT JOIN users u ON m.sender_id = u.id
            LEFT JOIN students s ON u.id = s.user_id
            LEFT JOIN parents p ON u.id = p.user_id
            WHERE m.receiver_id = ?
            ORDER BY m.created_at DESC";
    
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    return $stmt->get_result();
}

function getSentMessages($conn, $user_id) {
    $sql = "SELECT m.*, 
            CONCAT(s.first_name, ' ', s.last_name) as receiver_student_name,
            CONCAT(p.first_name, ' ', p.last_name) as receiver_parent_name,
            u.username as receiver_username,
            u.role as receiver_role
            FROM messages m 
            LEFT JOIN users u ON m.receiver_id = u.id
            LEFT JOIN students s ON u.id = s.user_id
            LEFT JOIN parents p ON u.id = p.user_id
            WHERE m.sender_id = ?
            ORDER BY m.created_at DESC";
    
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    return $stmt->get_result();
}

function markMessageAsRead($conn, $message_id, $user_id) {
    $sql = "UPDATE messages SET is_read = 1 WHERE id = ? AND receiver_id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("ii", $message_id, $user_id);
    return $stmt->execute();
}

function getUnreadMessageCount($conn, $user_id) {
    $sql = "SELECT COUNT(*) as count FROM messages WHERE receiver_id = ? AND is_read = 0";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $result = $stmt->get_result();
    return $result->fetch_assoc()['count'];
}

function getSenderName($message) {
    if ($message['sender_role'] == 'admin') {
        return 'Admin: ' . $message['sender_username'];
    } elseif ($message['sender_role'] == 'student') {
        return 'Student: ' . $message['sender_student_name'];
    } elseif ($message['sender_role'] == 'parent') {
        return 'Parent: ' . $message['sender_parent_name'];
    }
    return $message['sender_username'];
}

function getReceiverName($message) {
    if ($message['receiver_role'] == 'admin') {
        return 'Admin: ' . $message['receiver_username'];
    } elseif ($message['receiver_role'] == 'student') {
        return 'Student: ' . $message['receiver_student_name'];
    } elseif ($message['receiver_role'] == 'parent') {
        return 'Parent: ' . $message['receiver_parent_name'];
    }
    return $message['receiver_username'];
}

function getAllowedRecipients($conn, $user_role, $user_id) {
    $recipients = [];
    
    switch ($user_role) {
        case 'admin':
            // Admin can message everyone
            $sql = "SELECT u.id, u.username, u.role, 
                    CONCAT(COALESCE(s.first_name, p.first_name), ' ', COALESCE(s.last_name, p.last_name)) as full_name
                    FROM users u
                    LEFT JOIN students s ON u.id = s.user_id
                    LEFT JOIN parents p ON u.id = p.user_id
                    WHERE u.id != ?";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("i", $user_id);
            break;
            
        case 'student':
            // Students can message admin and their parents
            $sql = "SELECT u.id, u.username, u.role,
                    CONCAT(p.first_name, ' ', p.last_name) as full_name
                    FROM users u
                    LEFT JOIN parents p ON u.id = p.user_id
                    WHERE u.role = 'admin' 
                    OR (u.role = 'parent' AND p.id IN 
                        (SELECT parent_id FROM student_parent WHERE student_id = 
                            (SELECT id FROM students WHERE user_id = ?)))";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("i", $user_id);
            break;
            
        case 'parent':
            // Parents can message admin and their children
            $sql = "SELECT u.id, u.username, u.role,
                    CONCAT(s.first_name, ' ', s.last_name) as full_name
                    FROM users u
                    LEFT JOIN students s ON u.id = s.user_id
                    WHERE u.role = 'admin'
                    OR (u.role = 'student' AND s.id IN 
                        (SELECT student_id FROM student_parent WHERE parent_id = 
                            (SELECT id FROM parents WHERE user_id = ?)))";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("i", $user_id);
            break;
    }
    
    $stmt->execute();
    $result = $stmt->get_result();
    
    while ($row = $result->fetch_assoc()) {
        $recipients[] = $row;
    }
    
    return $recipients;
}
?> 