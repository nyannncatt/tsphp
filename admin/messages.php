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
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.8.1/font/bootstrap-icons.css" rel="stylesheet">
    <style>
        :root {
            --dark-bg: #1a1b2e;
            --card-bg: #242639;
            --accent-purple: #8b5cf6;
            --text-primary: #ffffff;
            --text-secondary: #9ca3af;
            --success-color: #10b981;
            --card-border: #2f3245;
            --hover-bg: #2f3245;
            --stat-text: #10b981;
            --header-text: #8b5cf6;
            --primary-color: #8b5cf6;
            --secondary-color: #a78bfa;
            --navbar-bg-start: #e9d5ff;
            --navbar-bg-end: #d8b4fe;
        }

        body {
            background-color: var(--dark-bg);
            color: var(--text-primary);
            font-family: 'Inter', sans-serif;
        }

        h2 {
            color: var(--header-text);
            font-weight: 600;
            margin-bottom: 1.5rem;
        }

        .card {
            background: var(--card-bg);
            border: 1px solid var(--card-border);
            border-radius: 0.75rem;
            box-shadow: 0 0.15rem 1.75rem rgba(0, 0, 0, 0.1);
        }

        .card-header {
            background: var(--card-bg);
            border-bottom: 1px solid var(--card-border);
            padding: 1rem;
        }

        .nav-tabs {
            border-bottom: none;
        }

        .nav-tabs .nav-link {
            color: var(--text-secondary);
            border: none;
            padding: 0.5rem 1rem;
            margin-right: 0.5rem;
            border-radius: 0.5rem;
        }

        .nav-tabs .nav-link:hover {
            color: var(--accent-purple);
            background: var(--hover-bg);
            border: none;
        }

        .nav-tabs .nav-link.active {
            color: var(--text-primary);
            background: var(--accent-purple);
            border: none;
        }

        .table {
            color: var(--text-primary);
        }

        .table > :not(caption) > * > * {
            background-color: var(--card-bg);
            border-bottom-color: var(--card-border);
        }

        .table tbody tr:hover {
            background-color: var(--hover-bg) !important;
            color: var(--text-primary);
        }

        .message-unread {
            font-weight: bold;
            color: var(--accent-purple);
        }

        .btn-primary {
            background: var(--accent-purple);
            border: none;
            color: white;
            padding: 0.5rem 1.5rem;
            border-radius: 0.5rem;
            font-weight: 500;
        }

        .btn-primary:hover {
            background: var(--secondary-color);
        }

        .btn-info {
            background: var(--accent-purple);
            border: none;
            color: white;
        }

        .btn-info:hover {
            background: var(--secondary-color);
            color: white;
        }

        .btn-secondary {
            background: var(--card-border);
            border: none;
            color: var(--text-primary);
        }

        .btn-secondary:hover {
            background: var(--hover-bg);
            color: var(--text-primary);
        }

        .alert {
            background: var(--card-bg);
            border: 1px solid var(--card-border);
            color: var(--text-primary);
            border-radius: 0.5rem;
        }

        .alert-success {
            border-left: 4px solid var(--success-color);
        }

        .alert-danger {
            border-left: 4px solid #ef4444;
        }

        .modal-content {
            background: var(--card-bg);
            border: 1px solid var(--card-border);
        }

        .modal-header {
            border-bottom: 1px solid var(--card-border);
        }

        .modal-header .btn-close {
            filter: invert(1) grayscale(100%) brightness(200%);
        }

        .modal-footer {
            border-top: 1px solid var(--card-border);
        }

        .modal-title {
            color: var(--header-text);
        }

        .form-label {
            color: var(--text-primary);
            font-weight: 500;
        }

        .form-control, .form-select {
            background: #ffffff;
            border: 1px solid var(--card-border);
            color: #000000;
            border-radius: 0.5rem;
        }

        .form-control:focus, .form-select:focus {
            background: #ffffff;
            border-color: var(--accent-purple);
            color: #000000;
            box-shadow: 0 0 0 0.25rem rgba(139, 92, 246, 0.25);
        }

        optgroup {
            background: #ffffff;
            color: #000000;
        }

        option {
            background: #ffffff;
            color: #000000;
        }

        .badge.bg-success {
            background-color: var(--success-color) !important;
        }

        .badge.bg-warning {
            background-color: #f59e0b !important;
        }

        .table-responsive {
            border-radius: 0.5rem;
        }
    </style>
</head>
<body>
    <?php include '../includes/admin_navbar.php'; ?>

    <div class="container mt-4">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h2><i class="bi bi-chat-dots-fill me-2"></i>Messages</h2>
            <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#composeMessageModal">
                <i class="bi bi-envelope-plus me-2"></i>Compose Message
            </button>
        </div>

        <?php if (isset($_GET['success'])): ?>
            <div class="alert alert-success">
                <i class="bi bi-check-circle me-2"></i>Message sent successfully.
            </div>
        <?php endif; ?>

        <?php if (isset($_GET['error'])): ?>
            <div class="alert alert-danger">
                <i class="bi bi-exclamation-circle me-2"></i><?php echo htmlspecialchars($_GET['error']); ?>
            </div>
        <?php endif; ?>

        <div class="card">
            <div class="card-header">
                <ul class="nav nav-tabs card-header-tabs">
                    <li class="nav-item">
                        <a class="nav-link active" data-bs-toggle="tab" href="#inbox">
                            <i class="bi bi-inbox me-2"></i>Inbox
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" data-bs-toggle="tab" href="#sent">
                            <i class="bi bi-send me-2"></i>Sent
                        </a>
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
                                        <th><i class="bi bi-person me-2"></i>From</th>
                                        <th><i class="bi bi-envelope me-2"></i>Subject</th>
                                        <th><i class="bi bi-calendar me-2"></i>Date</th>
                                        <th><i class="bi bi-check-circle me-2"></i>Status</th>
                                        <th><i class="bi bi-gear me-2"></i>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php 
                                    $messages->data_seek(0);
                                    while ($message = $messages->fetch_assoc()): 
                                        if ($message['receiver_id'] == $user_id):
                                    ?>
                                    <tr class="<?php echo !$message['is_read'] ? 'message-unread' : ''; ?>">
                                        <td><i class="bi bi-person-circle me-2"></i><?php echo htmlspecialchars($message['sender_username']); ?></td>
                                        <td><?php echo htmlspecialchars($message['subject']); ?></td>
                                        <td><i class="bi bi-clock me-2"></i><?php echo date('M d, Y H:i', strtotime($message['created_at'])); ?></td>
                                        <td>
                                            <span class="badge bg-<?php echo $message['is_read'] ? 'success' : 'warning'; ?>">
                                                <i class="bi bi-<?php echo $message['is_read'] ? 'check-circle' : 'exclamation-circle'; ?> me-1"></i>
                                                <?php echo $message['is_read'] ? 'Read' : 'Unread'; ?>
                                            </span>
                                        </td>
                                        <td>
                                            <a href="view_message.php?id=<?php echo $message['id']; ?>" class="btn btn-sm btn-info">
                                                <i class="bi bi-eye me-1"></i>View
                                            </a>
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
                                        <th><i class="bi bi-person me-2"></i>To</th>
                                        <th><i class="bi bi-envelope me-2"></i>Subject</th>
                                        <th><i class="bi bi-calendar me-2"></i>Date</th>
                                        <th><i class="bi bi-check-circle me-2"></i>Status</th>
                                        <th><i class="bi bi-gear me-2"></i>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php 
                                    $messages->data_seek(0);
                                    while ($message = $messages->fetch_assoc()): 
                                        if ($message['sender_id'] == $user_id):
                                    ?>
                                    <tr>
                                        <td><i class="bi bi-person-circle me-2"></i><?php echo htmlspecialchars($message['receiver_username']); ?></td>
                                        <td><?php echo htmlspecialchars($message['subject']); ?></td>
                                        <td><i class="bi bi-clock me-2"></i><?php echo date('M d, Y H:i', strtotime($message['created_at'])); ?></td>
                                        <td>
                                            <span class="badge bg-<?php echo $message['is_read'] ? 'success' : 'warning'; ?>">
                                                <i class="bi bi-<?php echo $message['is_read'] ? 'check-circle' : 'exclamation-circle'; ?> me-1"></i>
                                                <?php echo $message['is_read'] ? 'Read' : 'Unread'; ?>
                                            </span>
                                        </td>
                                        <td>
                                            <a href="view_message.php?id=<?php echo $message['id']; ?>" class="btn btn-sm btn-info">
                                                <i class="bi bi-eye me-1"></i>View
                                            </a>
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
                    <h5 class="modal-title"><i class="bi bi-envelope-plus me-2"></i>Compose Message</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <form action="send_message.php" method="POST">
                    <div class="modal-body">
                        <div class="mb-3">
                            <label for="recipient" class="form-label"><i class="bi bi-person me-2"></i>To *</label>
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
                            <label for="subject" class="form-label"><i class="bi bi-tag me-2"></i>Subject *</label>
                            <input type="text" class="form-control" id="subject" name="subject" required>
                        </div>
                        <div class="mb-3">
                            <label for="message" class="form-label"><i class="bi bi-chat-text me-2"></i>Message *</label>
                            <textarea class="form-control" id="message" name="message" rows="5" required></textarea>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                            <i class="bi bi-x-circle me-2"></i>Cancel
                        </button>
                        <button type="submit" class="btn btn-primary">
                            <i class="bi bi-send me-2"></i>Send Message
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html> 