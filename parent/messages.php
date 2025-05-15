<?php
require_once '../includes/auth_middleware.php';
checkAuth();
checkRole(['parent']);
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

// Get parent information
$parent_query = "SELECT id FROM parents WHERE user_id = ?";
$stmt = $conn->prepare($parent_query);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$parent = $stmt->get_result()->fetch_assoc();

// Get admin users
$admin_query = "SELECT u.id, u.username, 'admin' as role FROM users u WHERE u.role = 'admin'";
$admin_result = $conn->query($admin_query);
while ($admin = $admin_result->fetch_assoc()) {
    $found = false;
    foreach ($allowed_recipients as $recipient) {
        if ($recipient['id'] == $admin['id']) {
            $found = true;
            break;
        }
    }
    if (!$found) {
        $allowed_recipients[] = [
            'id' => $admin['id'],
            'username' => $admin['username'],
            'role' => 'admin',
            'full_name' => 'Administrator'
        ];
    }
}

// Get children's information to allow messaging them
$children_query = "
    SELECT u.id, u.username, 'student' as role,
           CONCAT(s.first_name, ' ', s.last_name) as full_name
    FROM users u
    JOIN students s ON u.id = s.user_id
    JOIN student_parent sp ON s.id = sp.student_id
    WHERE sp.parent_id = ?
";
$stmt = $conn->prepare($children_query);
$stmt->bind_param("i", $parent['id']);
$stmt->execute();
$children = $stmt->get_result();

// Add children to allowed recipients
while ($child = $children->fetch_assoc()) {
    $found = false;
    foreach ($allowed_recipients as $recipient) {
        if ($recipient['id'] == $child['id']) {
            $found = true;
            break;
        }
    }
    if (!$found) {
        $allowed_recipients[] = [
            'id' => $child['id'],
            'username' => $child['username'],
            'role' => 'student',
            'full_name' => $child['full_name'] . ' (Your Child)'
        ];
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Messages - School Management System</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>
    <?php include '../includes/parent_navbar.php'; ?>

    <div class="container mt-4">
        <div class="row mb-4">
            <div class="col-md-12">
                <nav aria-label="breadcrumb">
                    <ol class="breadcrumb">
                        <li class="breadcrumb-item"><a href="dashboard.php">Dashboard</a></li>
                        <li class="breadcrumb-item active">Messages</li>
                    </ol>
                </nav>
            </div>
        </div>

        <?php if (isset($success)): ?>
            <div class="alert alert-success"><?php echo $success; ?></div>
        <?php endif; ?>
        
        <?php if (isset($error)): ?>
            <div class="alert alert-danger"><?php echo $error; ?></div>
        <?php endif; ?>

        <!-- New Message Form -->
        <div class="card mb-4">
            <div class="card-header">
                <h5 class="mb-0">Send New Message</h5>
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
                    <button type="submit" class="btn btn-primary">Send Message</button>
                </form>
            </div>
        </div>

        <!-- Messages Tabs -->
        <div class="card">
            <div class="card-header">
                <ul class="nav nav-tabs card-header-tabs" role="tablist">
                    <li class="nav-item">
                        <button class="nav-link active" data-bs-toggle="tab" data-bs-target="#inbox" type="button">
                            Inbox
                            <?php if (getUnreadMessageCount($conn, $user_id) > 0): ?>
                                <span class="badge bg-danger">
                                    <?php echo getUnreadMessageCount($conn, $user_id); ?>
                                </span>
                            <?php endif; ?>
                        </button>
                    </li>
                    <li class="nav-item">
                        <button class="nav-link" data-bs-toggle="tab" data-bs-target="#sent" type="button">
                            Sent
                        </button>
                    </li>
                </ul>
            </div>
            <div class="card-body">
                <div class="tab-content">
                    <!-- Inbox -->
                    <div class="tab-pane fade show active" id="inbox">
                        <div class="list-group">
                            <?php if ($inbox_messages->num_rows == 0): ?>
                                <div class="text-muted">No messages in your inbox.</div>
                            <?php else: ?>
                                <?php while ($message = $inbox_messages->fetch_assoc()): ?>
                                    <div class="list-group-item list-group-item-action <?php echo !$message['is_read'] ? 'bg-light' : ''; ?>">
                                        <div class="d-flex w-100 justify-content-between">
                                            <h5 class="mb-1"><?php echo htmlspecialchars($message['subject']); ?></h5>
                                            <small><?php echo date('M d, Y H:i', strtotime($message['created_at'])); ?></small>
                                        </div>
                                        <p class="mb-1"><?php echo nl2br(htmlspecialchars($message['message'])); ?></p>
                                        <small>From: <?php echo getSenderName($message); ?></small>
                                    </div>
                                <?php endwhile; ?>
                            <?php endif; ?>
                        </div>
                    </div>

                    <!-- Sent Messages -->
                    <div class="tab-pane fade" id="sent">
                        <div class="list-group">
                            <?php if ($sent_messages->num_rows == 0): ?>
                                <div class="text-muted">No sent messages.</div>
                            <?php else: ?>
                                <?php while ($message = $sent_messages->fetch_assoc()): ?>
                                    <div class="list-group-item list-group-item-action">
                                        <div class="d-flex w-100 justify-content-between">
                                            <h5 class="mb-1"><?php echo htmlspecialchars($message['subject']); ?></h5>
                                            <small><?php echo date('M d, Y H:i', strtotime($message['created_at'])); ?></small>
                                        </div>
                                        <p class="mb-1"><?php echo nl2br(htmlspecialchars($message['message'])); ?></p>
                                        <small>To: <?php echo getReceiverName($message); ?></small>
                                    </div>
                                <?php endwhile; ?>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html> 