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
</head>
<body>
    <?php include '../includes/admin_navbar.php'; ?>

    <div class="container mt-4">
        <h2>Manage Announcements</h2>
        
        <?php if (isset($success)): ?>
            <div class="alert alert-success"><?php echo $success; ?></div>
        <?php endif; ?>
        
        <?php if (isset($error)): ?>
            <div class="alert alert-danger"><?php echo $error; ?></div>
        <?php endif; ?>

        <!-- Create Announcement Form -->
        <div class="card mb-4">
            <div class="card-header">
                <h5 class="mb-0">Create New Announcement</h5>
            </div>
            <div class="card-body">
                <form method="POST" action="">
                    <input type="hidden" name="action" value="create">
                    <div class="mb-3">
                        <label for="title" class="form-label">Title</label>
                        <input type="text" class="form-control" id="title" name="title" required>
                    </div>
                    <div class="mb-3">
                        <label for="content" class="form-label">Content</label>
                        <textarea class="form-control" id="content" name="content" rows="4" required></textarea>
                    </div>
                    <button type="submit" class="btn btn-primary">Create Announcement</button>
                </form>
            </div>
        </div>

        <!-- Announcements List -->
        <div class="card">
            <div class="card-header">
                <h5 class="mb-0">All Announcements</h5>
            </div>
            <div class="card-body">
                <?php while ($announcement = $announcements->fetch_assoc()): ?>
                    <div class="border-bottom mb-4 pb-3">
                        <div class="d-flex justify-content-between align-items-start">
                            <h4><?php echo htmlspecialchars($announcement['title']); ?></h4>
                            <form method="POST" action="" class="d-inline" onsubmit="return confirm('Are you sure you want to delete this announcement?');">
                                <input type="hidden" name="action" value="delete">
                                <input type="hidden" name="announcement_id" value="<?php echo $announcement['id']; ?>">
                                <button type="submit" class="btn btn-sm btn-danger">Delete</button>
                            </form>
                        </div>
                        <p class="text-muted">
                            Posted by <?php echo htmlspecialchars($announcement['username']); ?> 
                            on <?php echo date('M d, Y H:i', strtotime($announcement['created_at'])); ?>
                        </p>
                        <p><?php echo nl2br(htmlspecialchars($announcement['content'])); ?></p>
                    </div>
                <?php endwhile; ?>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html> 