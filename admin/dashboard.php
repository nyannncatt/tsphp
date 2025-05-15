<?php
require_once '../includes/auth_middleware.php';
checkAuth();
checkRole(['admin']);
require_once '../config/database.php';

// Get counts for dashboard
$student_count = $conn->query("SELECT COUNT(*) as count FROM students")->fetch_assoc()['count'];
$parent_count = $conn->query("SELECT COUNT(*) as count FROM parents")->fetch_assoc()['count'];
$course_count = $conn->query("SELECT COUNT(*) as count FROM courses")->fetch_assoc()['count'];
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Teacher Dashboard - SchoolComSphere</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.8.1/font/bootstrap-icons.css" rel="stylesheet">
</head>
<body>
    <?php include '../includes/admin_navbar.php'; ?>

    <div class="container mt-4">
        <h2>Admin Dashboard</h2>
        <div class="row mt-4">
            <div class="col-md-4">
                <div class="card text-white bg-primary mb-3">
                    <div class="card-body">
                        <h5 class="card-title">Total Students</h5>
                        <p class="card-text display-4"><?php echo $student_count; ?></p>
                    </div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="card text-white bg-success mb-3">
                    <div class="card-body">
                        <h5 class="card-title">Total Parents</h5>
                        <p class="card-text display-4"><?php echo $parent_count; ?></p>
                    </div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="card text-white bg-info mb-3">
                    <div class="card-body">
                        <h5 class="card-title">Total Courses</h5>
                        <p class="card-text display-4"><?php echo $course_count; ?></p>
                    </div>
                </div>
            </div>
        </div>

        <!-- Recent Announcements -->
        <div class="row mt-4">
            <div class="col-md-12">
                <div class="card">
                    <div class="card-header">
                        Recent Announcements
                        <a href="announcements.php" class="btn btn-primary btn-sm float-end">Add New</a>
                    </div>
                    <div class="card-body">
                        <?php
                        $announcements = $conn->query("SELECT * FROM announcements ORDER BY created_at DESC LIMIT 5");
                        while ($announcement = $announcements->fetch_assoc()):
                        ?>
                        <div class="mb-3">
                            <h5><?php echo htmlspecialchars($announcement['title']); ?></h5>
                            <p><?php echo htmlspecialchars($announcement['content']); ?></p>
                            <small class="text-muted">Posted on: <?php echo date('M d, Y', strtotime($announcement['created_at'])); ?></small>
                        </div>
                        <?php endwhile; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html> 