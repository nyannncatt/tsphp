<?php
require_once '../includes/auth_middleware.php';
checkAuth();
checkRole(['admin']);
require_once '../config/database.php';

// Get all messages for the current user
$user_id = $_SESSION['user_id'];
$query = "SELECT m.*, 
          sender.username as sender_username, sender.role as sender_role,
          receiver.username as receiver_username, receiver.role as receiver_role
          FROM messages m
          JOIN users sender ON m.sender_id = sender.id
          JOIN users receiver ON m.receiver_id = receiver.id
          WHERE m.sender_id = ? OR m.receiver_id = ?
          ORDER BY m.created_at DESC";
$stmt = $conn->prepare($query);
$stmt->bind_param("ii", $user_id, $user_id);
$stmt->execute();
$messages = $stmt->get_result();

// Get all students and parents for the compose message form
$query = "SELECT u.id, u.username, u.role, 
          CASE 
            WHEN u.role = 'student' THEN s.first_name
            WHEN u.role = 'parent' THEN p.first_name
          END as first_name,
          CASE 
            WHEN u.role = 'student' THEN s.last_name
            WHEN u.role = 'parent' THEN p.last_name
          END as last_name
          FROM users u
          LEFT JOIN students s ON u.id = s.user_id
          LEFT JOIN parents p ON u.id = p.user_id
          WHERE u.role IN ('student', 'parent')
          ORDER BY u.role, last_name, first_name";
$recipients = $conn->query($query);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Messages - School Management System</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        .message-unread { font-weight: bold; }
        .tab-content { min-height: 300px; }
    </style>
</head>
<body>
    <?php include '../includes/admin_navbar.php'; ?>

    <div class="container mt-4">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h2>Messages</h2>
            <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#composeMessageModal">
                <i class="bi bi-envelope-plus"></i> Compose Message
            </button>
        </div>

        <?php if (isset($_GET['success'])): ?>
            <div class="alert alert-success">
                Message sent successfully.
            </div>
        <?php endif; ?>

        <?php if (isset($_GET['error'])): ?>
            <div class="alert alert-danger">
                <?php echo htmlspecialchars($_GET['error']); ?>
            </div>
        <?php endif; ?>

        <div class="card">
            <div class="card-header">
                <ul class="nav nav-tabs card-header-tabs">
                    <li class="nav-item">
                        <a class="nav-link active" data-bs-toggle="tab" href="#inbox">Inbox</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" data-bs-toggle="tab" href="#sent">Sent</a>
                    </li>
                </ul>
            </div>
            <div class="card-body">
                <div class="tab-content">
                    <div class="tab-pane fade show active" id="inbox">
                        <div class="table-responsive">
                            <table class="table table-hover">
                                <thead>
                                    <tr>
                                        <th>From</th>
                                        <th>Subject</th>
                                        <th>Date</th>
                                        <th>Status</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php 
                                    $messages->data_seek(0);
                                    while ($message = $messages->fetch_assoc()): 
                                        if ($message['receiver_id'] == $user_id):
                                    ?>
                                    <tr class="<?php echo !$message['is_read'] ? 'message-unread' : ''; ?>">
                                        <td><?php echo htmlspecialchars($message['sender_username']); ?></td>
                                        <td><?php echo htmlspecialchars($message['subject']); ?></td>
                                        <td><?php echo date('M d, Y H:i', strtotime($message['created_at'])); ?></td>
                                        <td>
                                            <span class="badge bg-<?php echo $message['is_read'] ? 'success' : 'warning'; ?>">
                                                <?php echo $message['is_read'] ? 'Read' : 'Unread'; ?>
                                            </span>
                                        </td>
                                        <td>
                                            <a href="view_message.php?id=<?php echo $message['id']; ?>" class="btn btn-sm btn-info">View</a>
                                        </td>
                                    </tr>
                                    <?php 
                                        endif;
                                    endwhile; 
                                    ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                    <div class="tab-pane fade" id="sent">
                        <div class="table-responsive">
                            <table class="table table-hover">
                                <thead>
                                    <tr>
                                        <th>To</th>
                                        <th>Subject</th>
                                        <th>Date</th>
                                        <th>Status</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php 
                                    $messages->data_seek(0);
                                    while ($message = $messages->fetch_assoc()): 
                                        if ($message['sender_id'] == $user_id):
                                    ?>
                                    <tr>
                                        <td><?php echo htmlspecialchars($message['receiver_username']); ?></td>
                                        <td><?php echo htmlspecialchars($message['subject']); ?></td>
                                        <td><?php echo date('M d, Y H:i', strtotime($message['created_at'])); ?></td>
                                        <td>
                                            <span class="badge bg-<?php echo $message['is_read'] ? 'success' : 'warning'; ?>">
                                                <?php echo $message['is_read'] ? 'Read' : 'Unread'; ?>
                                            </span>
                                        </td>
                                        <td>
                                            <a href="view_message.php?id=<?php echo $message['id']; ?>" class="btn btn-sm btn-info">View</a>
                                        </td>
                                    </tr>
                                    <?php 
                                        endif;
                                    endwhile; 
                                    ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Compose Message Modal -->
    <div class="modal fade" id="composeMessageModal" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Compose Message</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <form action="send_message.php" method="POST">
                    <div class="modal-body">
                        <div class="mb-3">
                            <label for="recipient" class="form-label">To *</label>
                            <select class="form-select" id="recipient" name="recipient_id" required>
                                <option value="">Select recipient...</option>
                                <optgroup label="Students">
                                    <?php 
                                    $recipients->data_seek(0);
                                    while ($recipient = $recipients->fetch_assoc()): 
                                        if ($recipient['role'] == 'student'):
                                    ?>
                                    <option value="<?php echo $recipient['id']; ?>">
                                        <?php echo htmlspecialchars($recipient['first_name'] . ' ' . $recipient['last_name'] . ' (' . $recipient['username'] . ')'); ?>
                                    </option>
                                    <?php 
                                        endif;
                                    endwhile; 
                                    ?>
                                </optgroup>
                                <optgroup label="Parents">
                                    <?php 
                                    $recipients->data_seek(0);
                                    while ($recipient = $recipients->fetch_assoc()): 
                                        if ($recipient['role'] == 'parent'):
                                    ?>
                                    <option value="<?php echo $recipient['id']; ?>">
                                        <?php echo htmlspecialchars($recipient['first_name'] . ' ' . $recipient['last_name'] . ' (' . $recipient['username'] . ')'); ?>
                                    </option>
                                    <?php 
                                        endif;
                                    endwhile; 
                                    ?>
                                </optgroup>
                            </select>
                        </div>
                        <div class="mb-3">
                            <label for="subject" class="form-label">Subject *</label>
                            <input type="text" class="form-control" id="subject" name="subject" required>
                        </div>
                        <div class="mb-3">
                            <label for="message" class="form-label">Message *</label>
                            <textarea class="form-control" id="message" name="message" rows="5" required></textarea>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-primary">Send Message</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html> 