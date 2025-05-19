<?php
require_once '../includes/auth_middleware.php';
checkAuth();
checkRole(['admin']);
require_once '../config/database.php';

// Handle announcement actions
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    if (isset($_POST['action'])) {
        if ($_POST['action'] == 'create') {
            $title = $conn->real_escape_string($_POST['title']);
            $content = $conn->real_escape_string($_POST['content']);
            $user_id = getUserId();
            
            $sql = "INSERT INTO announcements (title, content, created_by) VALUES (?, ?, ?)";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("ssi", $title, $content, $user_id);
            
            if ($stmt->execute()) {
                $success = "Announcement created successfully!";
            } else {
                $error = "Error creating announcement";
            }
        }
        
        if ($_POST['action'] == 'delete' && isset($_POST['announcement_id'])) {
            $announcement_id = (int)$_POST['announcement_id'];
            $sql = "DELETE FROM announcements WHERE id = ?";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param("i", $announcement_id);
            
            if ($stmt->execute()) {
                $success = "Announcement deleted successfully!";
            } else {
                $error = "Error deleting announcement";
            }
        }
    }
}

// Fetch all announcements
$announcements = $conn->query("
    SELECT a.*, u.username 
    FROM announcements a 
    LEFT JOIN users u ON a.created_by = u.id 
    ORDER BY a.created_at DESC
");
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Announcements - School Management System</title>
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

        h4 {
            color: var(--header-text);
            font-weight: 600;
        }

        .card {
            background: var(--card-bg);
            border: 1px solid var(--card-border);
            border-radius: 0.75rem;
            box-shadow: 0 0.15rem 1.75rem rgba(0, 0, 0, 0.1);
            margin-bottom: 1.5rem;
        }

        .card-header {
            background: var(--card-bg);
            border-bottom: 1px solid var(--card-border);
            padding: 1.25rem;
            border-radius: 0.75rem 0.75rem 0 0 !important;
        }

        .card-header h5 {
            color: var(--header-text);
            font-weight: 600;
            margin: 0;
        }

        .card-body {
            padding: 1.5rem;
        }

        .alert {
            background: var(--card-bg);
            border: 1px solid var(--card-border);
            color: #000000;
            border-radius: 0.5rem;
        }

        .alert-success {
            border-left: 4px solid var(--success-color);
        }

        .alert-danger {
            border-left: 4px solid #ef4444;
        }

        .form-label {
            color: #000000;
            font-weight: 500;
        }

        .form-control {
            background: #ffffff;
            border: 1px solid var(--card-border);
            color: #000000;
            border-radius: 0.5rem;
        }

        .form-control:focus {
            background: #ffffff;
            border-color: var(--accent-purple);
            color: #000000;
            box-shadow: 0 0 0 0.25rem rgba(139, 92, 246, 0.25);
        }

        textarea.form-control {
            min-height: 120px;
            resize: vertical;
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

        .btn-danger {
            background: #ef4444;
            border: none;
            color: white;
        }

        .btn-danger:hover {
            background: #dc2626;
        }

        .border-bottom {
            border-color: var(--card-border) !important;
        }

        .text-muted {
            color: #666666 !important;
        }

        p {
            color: #000000;
            line-height: 1.6;
        }

        /* Custom announcement styles */
        .announcement-content {
            background: #ffffff;
            border-radius: 0.5rem;
            padding: 1rem;
            margin-top: 1rem;
            color: #000000;
        }

        .announcement-meta {
            display: flex;
            align-items: center;
            gap: 0.5rem;
            color: var(--text-secondary);
            font-size: 0.875rem;
        }

        .announcement-meta i {
            color: var(--accent-purple);
        }

        .btn-sm {
            padding: 0.25rem 0.75rem;
            font-size: 0.875rem;
        }
    </style>
</head>
<body>
    <?php include '../includes/admin_navbar.php'; ?>

    <div class="container mt-4">
        <h2><i class="bi bi-megaphone-fill me-2"></i>Manage Announcements</h2>
        
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

        <!-- Create Announcement Form -->
        <div class="card mb-4">
            <div class="card-header">
                <h5 class="mb-0"><i class="bi bi-plus-circle me-2"></i>Create New Announcement</h5>
            </div>
            <div class="card-body">
                <form method="POST" action="">
                    <input type="hidden" name="action" value="create">
                    <div class="mb-3">
                        <label for="title" class="form-label">Title</label>
                        <input type="text" class="form-control" id="title" name="title" required
                               placeholder="Enter announcement title">
                    </div>
                    <div class="mb-3">
                        <label for="content" class="form-label">Content</label>
                        <textarea class="form-control" id="content" name="content" rows="4" required
                                  placeholder="Enter announcement content"></textarea>
                    </div>
                    <button type="submit" class="btn btn-primary">
                        <i class="bi bi-plus-circle me-2"></i>Create Announcement
                    </button>
                </form>
            </div>
        </div>

        <!-- Announcements List -->
        <div class="card">
            <div class="card-header">
                <h5 class="mb-0"><i class="bi bi-list-ul me-2"></i>All Announcements</h5>
            </div>
            <div class="card-body">
                <?php while ($announcement = $announcements->fetch_assoc()): ?>
                    <div class="border-bottom mb-4 pb-3">
                        <div class="d-flex justify-content-between align-items-start">
                            <h4><?php echo htmlspecialchars($announcement['title']); ?></h4>
                            <form method="POST" action="" class="d-inline" onsubmit="return confirm('Are you sure you want to delete this announcement?');">
                                <input type="hidden" name="action" value="delete">
                                <input type="hidden" name="announcement_id" value="<?php echo $announcement['id']; ?>">
                                <button type="submit" class="btn btn-sm btn-danger">
                                    <i class="bi bi-trash me-1"></i>Delete
                                </button>
                            </form>
                        </div>
                        <div class="announcement-meta">
                            <i class="bi bi-person-circle"></i>
                            <?php echo htmlspecialchars($announcement['username']); ?>
                            <i class="bi bi-clock ms-2"></i>
                            <?php echo date('M d, Y H:i', strtotime($announcement['created_at'])); ?>
                        </div>
                        <div class="announcement-content">
                            <?php echo nl2br(htmlspecialchars($announcement['content'])); ?>
                        </div>
                    </div>
                <?php endwhile; ?>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html> 