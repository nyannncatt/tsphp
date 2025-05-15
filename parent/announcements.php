<?php
require_once '../includes/auth_middleware.php';
checkAuth();
checkRole(['parent']);
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
    <?php include '../includes/parent_navbar.php'; ?>

    <div class="container mt-4">
        <div class="row mb-4">
            <div class="col-md-12">
                <nav aria-label="breadcrumb">
                    <ol class="breadcrumb">
                        <li class="breadcrumb-item"><a href="dashboard.php">Dashboard</a></li>
                        <li class="breadcrumb-item active">Announcements</li>
                    </ol>
                </nav>
            </div>
        </div>

        <div class="row mb-4">
            <div class="col-md-12">
                <div class="card">
                    <div class="card-header">
                        <h4 class="mb-0">School Announcements</h4>
                    </div>
                    <div class="card-body">
                        <?php if ($announcements->num_rows == 0): ?>
                            <div class="alert alert-info">
                                No announcements available at this time.
                            </div>
                        <?php else: ?>
                            <div class="list-group">
                                <?php while ($announcement = $announcements->fetch_assoc()): ?>
                                    <div class="list-group-item mb-3">
                                        <div class="d-flex w-100 justify-content-between">
                                            <h5 class="mb-1"><?php echo htmlspecialchars($announcement['title']); ?></h5>
                                            <small class="text-muted">
                                                <?php echo date('M d, Y H:i', strtotime($announcement['created_at'])); ?>
                                            </small>
                                        </div>
                                        <p class="mb-1"><?php echo nl2br(htmlspecialchars($announcement['content'])); ?></p>
                                        <small class="text-muted">
                                            Posted by <?php echo htmlspecialchars($announcement['username']); ?>
                                        </small>
                                    </div>
                                <?php endwhile; ?>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html> 