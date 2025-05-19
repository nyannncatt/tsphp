<?php
require_once '../includes/auth_middleware.php';
checkAuth();
checkRole(['student']);
require_once '../config/database.php';
require_once '../includes/messages.php';

$user_id = getUserId();
$user_role = getUserRole();

// Handle message sending
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['action'])) {
    if ($_POST['action'] == 'send') {
        $receiver_id = (int)$_POST['receiver_id'];
        $subject = $conn->real_escape_string($_POST['subject']);
        $message = $conn->real_escape_string($_POST['message']);
        
        if (sendMessage($conn, $user_id, $receiver_id, $subject, $message)) {
            $success = "Message sent successfully!";
        } else {
            $error = "Error sending message";
        }
    }
}

// Get messages
$inbox_messages = getInboxMessages($conn, $user_id);
$sent_messages = getSentMessages($conn, $user_id);
$allowed_recipients = getAllowedRecipients($conn, $user_role, $user_id);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Messages - School Management System</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css" rel="stylesheet">
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
        }
        
        body {
            background-color: var(--dark-bg);
            color: var(--text-primary);
            font-family: 'Inter', sans-serif;
            min-height: 100vh;
        }

        h2 {
            color: var(--text-primary);
            margin-bottom: 1.5rem;
        }

        .card {
            background: #242639;
            border: 1px solid var(--card-border);
            border-radius: 1rem;
            box-shadow: 0 8px 20px rgba(0, 0, 0, 0.2);
        }

        .card-header {
            background: #242639;
            border-bottom: 1px solid var(--card-border);
            padding: 1.25rem;
            border-radius: 1rem 1rem 0 0 !important;
            color: var(--text-primary);
        }

        .card-body {
            background: #242639;
            border-radius: 0 0 1rem 1rem;
            color: var(--text-primary);
        }

        /* Form styles */
        .form-label {
            color: var(--text-primary);
        }

        .form-control, .form-select {
            background-color: var(--dark-bg);
            border: 1px solid var(--card-border);
            color: var(--text-primary);
        }

        .form-control:focus, .form-select:focus {
            background-color: var(--dark-bg);
            border-color: var(--accent-purple);
            color: var(--text-primary);
            box-shadow: 0 0 0 0.25rem rgba(139, 92, 246, 0.25);
        }

        .form-select option {
            background-color: var(--dark-bg);
            color: var(--text-primary);
        }

        /* Button styles */
        .btn-primary {
            background-color: var(--accent-purple);
            border-color: var(--accent-purple);
        }

        .btn-primary:hover {
            background-color: #7c3aed;
            border-color: #7c3aed;
        }

        /* Tabs styling */
        .nav-tabs {
            border-bottom: 1px solid var(--card-border);
        }

        .nav-tabs .nav-link {
            color: var(--text-secondary);
            border: none;
            padding: 1rem 1.5rem;
            margin-right: 0.5rem;
            border-radius: 0.5rem 0.5rem 0 0;
        }

        .nav-tabs .nav-link:hover {
            color: var(--text-primary);
            border: none;
            background-color: var(--hover-bg);
        }

        .nav-tabs .nav-link.active {
            color: var(--accent-purple);
            background-color: #242639;
            border: 1px solid var(--card-border);
            border-bottom: none;
        }

        /* Message list styling */
        .list-group-item {
            background-color: #242639;
            border: 1px solid var(--card-border);
            margin-bottom: 0.5rem;
            border-radius: 0.5rem;
            color: var(--text-primary);
            transition: all 0.2s ease-in-out;
            padding: 1rem;
        }

        .list-group-item:hover {
            transform: translateY(-2px);
            background-color: var(--hover-bg);
            border-color: var(--accent-purple);
        }

        .list-group-item.bg-light {
            background-color: #2f3245 !important;
            border-left: 4px solid var(--accent-purple);
        }

        .list-group-item h5 {
            color: var(--accent-purple);
            font-weight: 600;
            font-size: 1.1rem;
            margin: 0;
        }

        .list-group-item small {
            color: var(--text-secondary);
            font-size: 0.85rem;
            display: flex;
            align-items: center;
        }

        .list-group-item p {
            color: var(--text-primary);
            margin: 0.75rem 0;
            font-size: 0.95rem;
            line-height: 1.5;
        }

        .list-group-item .bi {
            color: var(--accent-purple);
        }

        .list-group-item .message-meta {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-top: 0.5rem;
            padding-top: 0.5rem;
            border-top: 1px solid var(--card-border);
        }

        .list-group-item .message-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 0.5rem;
        }

        .list-group-item .message-content {
            background-color: rgba(47, 50, 69, 0.5);
            padding: 0.75rem;
            border-radius: 0.5rem;
            margin: 0.5rem 0;
        }

        /* Empty state styling */
        .empty-state {
            text-align: center;
            padding: 2rem;
            color: var(--text-secondary);
        }

        .empty-state i {
            font-size: 3rem;
            color: var(--accent-purple);
            margin-bottom: 1rem;
        }

        .empty-state p {
            margin: 0;
            font-size: 1.1rem;
        }

        /* Alert styling */
        .alert-success {
            background-color: rgba(16, 185, 129, 0.2);
            border-color: var(--success-color);
            color: var(--text-primary);
        }

        .alert-danger {
            background-color: rgba(239, 68, 68, 0.2);
            border-color: #ef4444;
            color: var(--text-primary);
        }
    </style>
</head>
<body>
    <?php include '../includes/student_navbar.php'; ?>

    <div class="container mt-4">
        <h2><i class="bi bi-envelope me-2"></i>Messages</h2>
        
        <?php if (isset($success)): ?>
            <div class="alert alert-success">
                <i class="bi bi-check-circle me-2"></i>
                <?php echo $success; ?>
            </div>
        <?php endif; ?>
        
        <?php if (isset($error)): ?>
            <div class="alert alert-danger">
                <i class="bi bi-exclamation-circle me-2"></i>
                <?php echo $error; ?>
            </div>
        <?php endif; ?>

        <!-- New Message Form -->
        <div class="card mb-4">
            <div class="card-header">
                <h5 class="mb-0"><i class="bi bi-pencil-square me-2"></i>Send New Message</h5>
            </div>
            <div class="card-body">
                <form method="POST" action="">
                    <input type="hidden" name="action" value="send">
                    <div class="mb-3">
                        <label for="receiver_id" class="form-label">To</label>
                        <select class="form-select" id="receiver_id" name="receiver_id" required>
                            <option value="">Select recipient</option>
                            <?php foreach ($allowed_recipients as $recipient): ?>
                                <option value="<?php echo $recipient['id']; ?>">
                                    <?php echo htmlspecialchars($recipient['role'] . ': ' . ($recipient['full_name'] ?? $recipient['username'])); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label for="subject" class="form-label">Subject</label>
                        <input type="text" class="form-control" id="subject" name="subject" required>
                    </div>
                    <div class="mb-3">
                        <label for="message" class="form-label">Message</label>
                        <textarea class="form-control" id="message" name="message" rows="4" required></textarea>
                    </div>
                    <button type="submit" class="btn btn-primary">
                        <i class="bi bi-send me-2"></i>Send Message
                    </button>
                </form>
            </div>
        </div>

        <!-- Messages Tabs -->
        <ul class="nav nav-tabs mb-3" id="messagesTab" role="tablist">
            <li class="nav-item" role="presentation">
                <button class="nav-link active" id="inbox-tab" data-bs-toggle="tab" data-bs-target="#inbox" type="button">
                    <i class="bi bi-inbox me-2"></i>Inbox
                </button>
            </li>
            <li class="nav-item" role="presentation">
                <button class="nav-link" id="sent-tab" data-bs-toggle="tab" data-bs-target="#sent" type="button">
                    <i class="bi bi-send me-2"></i>Sent
                </button>
            </li>
        </ul>

        <div class="tab-content" id="messagesTabContent">
            <!-- Inbox -->
            <div class="tab-pane fade show active" id="inbox">
                <div class="list-group">
                    <?php if ($inbox_messages->num_rows == 0): ?>
                        <div class="empty-state">
                            <i class="bi bi-inbox"></i>
                            <p>Your inbox is empty</p>
                        </div>
                    <?php else: ?>
                        <?php while ($message = $inbox_messages->fetch_assoc()): ?>
                            <div class="list-group-item list-group-item-action <?php echo !$message['is_read'] ? 'bg-light' : ''; ?>">
                                <div class="message-header">
                                    <h5 class="mb-0">
                                        <?php if (!$message['is_read']): ?>
                                            <i class="bi bi-envelope-fill me-2"></i>
                                        <?php else: ?>
                                            <i class="bi bi-envelope-open me-2"></i>
                                        <?php endif; ?>
                                        <?php echo htmlspecialchars($message['subject']); ?>
                                    </h5>
                                    <small><i class="bi bi-clock me-1"></i><?php echo date('M d, Y H:i', strtotime($message['created_at'])); ?></small>
                                </div>
                                <div class="message-content">
                                    <p class="mb-0"><?php echo htmlspecialchars($message['message']); ?></p>
                                </div>
                                <div class="message-meta">
                                    <small><i class="bi bi-person me-1"></i>From: <?php echo getSenderName($message); ?></small>
                                    <?php if (!$message['is_read']): ?>
                                        <span class="badge bg-accent-purple">New</span>
                                    <?php endif; ?>
                                </div>
                            </div>
                        <?php endwhile; ?>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Sent Messages -->
            <div class="tab-pane fade" id="sent">
                <div class="list-group">
                    <?php if ($sent_messages->num_rows == 0): ?>
                        <div class="empty-state">
                            <i class="bi bi-send"></i>
                            <p>No sent messages</p>
                        </div>
                    <?php else: ?>
                        <?php while ($message = $sent_messages->fetch_assoc()): ?>
                            <div class="list-group-item list-group-item-action">
                                <div class="message-header">
                                    <h5 class="mb-0">
                                        <i class="bi bi-send me-2"></i>
                                        <?php echo htmlspecialchars($message['subject']); ?>
                                    </h5>
                                    <small><i class="bi bi-clock me-1"></i><?php echo date('M d, Y H:i', strtotime($message['created_at'])); ?></small>
                                </div>
                                <div class="message-content">
                                    <p class="mb-0"><?php echo htmlspecialchars($message['message']); ?></p>
                                </div>
                                <div class="message-meta">
                                    <small><i class="bi bi-person me-1"></i>To: <?php echo getReceiverName($message); ?></small>
                                </div>
                            </div>
                        <?php endwhile; ?>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html> 