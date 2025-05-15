<?php
require_once '../includes/auth_middleware.php';
checkAuth();
checkRole(['student']);
require_once '../config/database.php';

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
    <title>Announcements - School Management System</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>
    <?php include '../includes/student_navbar.php'; ?>

    <div class="container mt-4">
        <h2>Announcements</h2>

        <!-- Announcements List -->
        <div class="row">
            <?php while ($announcement = $announcements->fetch_assoc()): ?>
                <div class="col-md-12 mb-4">
                    <div class="card">
                        <div class="card-header">
                            <h4 class="mb-0"><?php echo htmlspecialchars($announcement['title']); ?></h4>
                        </div>
                        <div class="card-body">
                            <p class="text-muted">
                                Posted by <?php echo htmlspecialchars($announcement['username']); ?> 
                                on <?php echo date('M d, Y H:i', strtotime($announcement['created_at'])); ?>
                            </p>
                            <p class="card-text"><?php echo nl2br(htmlspecialchars($announcement['content'])); ?></p>
                        </div>
                    </div>
                </div>
            <?php endwhile; ?>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html> 