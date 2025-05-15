<?php
require_once '../includes/auth_middleware.php';
checkAuth();
checkRole(['admin']);
require_once '../config/database.php';

$message_id = isset($_GET['id']) ? intval($_GET['id']) : 0;
$user_id = $_SESSION['user_id'];

// Get message details
$query = "SELECT m.*, 
          sender.username as sender_username, sender.role as sender_role,
          receiver.username as receiver_username, receiver.role as receiver_role,
          CASE 
            WHEN sender.role = 'student' THEN s1.first_name
            WHEN sender.role = 'parent' THEN p1.first_name
            ELSE 'Admin'
          END as sender_first_name,
          CASE 
            WHEN sender.role = 'student' THEN s1.last_name
            WHEN sender.role = 'parent' THEN p1.last_name
            ELSE ''
          END as sender_last_name,
          CASE 
            WHEN receiver.role = 'student' THEN s2.first_name
            WHEN receiver.role = 'parent' THEN p2.first_name
            ELSE 'Admin'
          END as receiver_first_name,
          CASE 
            WHEN receiver.role = 'student' THEN s2.last_name
            WHEN receiver.role = 'parent' THEN p2.last_name
            ELSE ''
          END as receiver_last_name
          FROM messages m
          JOIN users sender ON m.sender_id = sender.id
          JOIN users receiver ON m.receiver_id = receiver.id
          LEFT JOIN students s1 ON sender.id = s1.user_id
          LEFT JOIN parents p1 ON sender.id = p1.user_id
          LEFT JOIN students s2 ON receiver.id = s2.user_id
          LEFT JOIN parents p2 ON receiver.id = p2.user_id
          WHERE m.id = ? AND (m.sender_id = ? OR m.receiver_id = ?)";
$stmt = $conn->prepare($query);
$stmt->bind_param("iii", $message_id, $user_id, $user_id);
$stmt->execute();
$message = $stmt->get_result()->fetch_assoc();

if (!$message) {
    header("Location: messages.php");
    exit();
}

// Mark message as read if current user is the receiver
if ($message['receiver_id'] == $user_id && !$message['is_read']) {
    $query = "UPDATE messages SET is_read = 1 WHERE id = ?";
    $stmt = $conn->prepare($query);
    $stmt->bind_param("i", $message_id);
    $stmt->execute();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>View Message - School Management System</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>
    <?php include '../includes/admin_navbar.php'; ?>

    <div class="container mt-4">
        <div class="row mb-4">
            <div class="col-md-12">
                <nav aria-label="breadcrumb">
                    <ol class="breadcrumb">
                        <li class="breadcrumb-item"><a href="messages.php">Messages</a></li>
                        <li class="breadcrumb-item active">View Message</li>
                    </ol>
                </nav>
            </div>
        </div>

        <div class="card">
            <div class="card-header">
                <h4 class="mb-0"><?php echo htmlspecialchars($message['subject']); ?></h4>
            </div>
            <div class="card-body">
                <div class="row mb-3">
                    <div class="col-md-6">
                        <p><strong>From:</strong> 
                            <?php 
                            echo htmlspecialchars($message['sender_first_name'] . ' ' . $message['sender_last_name']);
                            echo ' (' . htmlspecialchars($message['sender_username']) . ')';
                            ?>
                        </p>
                    </div>
                    <div class="col-md-6">
                        <p><strong>To:</strong> 
                            <?php 
                            echo htmlspecialchars($message['receiver_first_name'] . ' ' . $message['receiver_last_name']);
                            echo ' (' . htmlspecialchars($message['receiver_username']) . ')';
                            ?>
                        </p>
                    </div>
                </div>
                <div class="row mb-3">
                    <div class="col-md-6">
                        <p><strong>Date:</strong> <?php echo date('M d, Y H:i', strtotime($message['created_at'])); ?></p>
                    </div>
                    <div class="col-md-6">
                        <p><strong>Status:</strong> 
                            <span class="badge bg-<?php echo $message['is_read'] ? 'success' : 'warning'; ?>">
                                <?php echo $message['is_read'] ? 'Read' : 'Unread'; ?>
                            </span>
                        </p>
                    </div>
                </div>
                <hr>
                <div class="message-content">
                    <?php echo nl2br(htmlspecialchars($message['message'])); ?>
                </div>
            </div>
            <div class="card-footer">
                <a href="messages.php" class="btn btn-secondary">Back to Messages</a>
                <?php if ($message['receiver_id'] == $user_id): ?>
                <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#replyModal">
                    Reply
                </button>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- Reply Modal -->
    <?php if ($message['receiver_id'] == $user_id): ?>
    <div class="modal fade" id="replyModal" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Reply to Message</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <form action="send_message.php" method="POST">
                    <div class="modal-body">
                        <input type="hidden" name="recipient_id" value="<?php echo $message['sender_id']; ?>">
                        <div class="mb-3">
                            <label for="subject" class="form-label">Subject *</label>
                            <input type="text" class="form-control" id="subject" name="subject" 
                                   value="Re: <?php echo htmlspecialchars($message['subject']); ?>" required>
                        </div>
                        <div class="mb-3">
                            <label for="message" class="form-label">Message *</label>
                            <textarea class="form-control" id="message" name="message" rows="5" required></textarea>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-primary">Send Reply</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    <?php endif; ?>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html> 