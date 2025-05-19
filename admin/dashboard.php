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
    <title>Admin Dashboard - SchoolComSphere</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.8.1/font/bootstrap-icons.css" rel="stylesheet">
    <style>
        .stat-card {
            border: none;
            border-radius: 15px;
            transition: transform 0.3s ease, box-shadow 0.3s ease;
        }
        
        .stat-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 10px 20px rgba(0,0,0,0.1);
        }

        .stat-card .card-body {
            padding: 2rem;
        }

        .stat-card .icon {
            font-size: 2.5rem;
            margin-bottom: 1rem;
        }

        .stat-card .card-title {
            font-size: 1.1rem;
            font-weight: 600;
            margin-bottom: 0.5rem;
        }

        .stat-card .card-text {
            font-size: 2.5rem;
            font-weight: 700;
            margin: 0;
        }

        .announcement-card {
            border-radius: 15px;
            box-shadow: 0 4px 6px rgba(0,0,0,0.05);
            margin-bottom: 1.5rem;
        }

        .announcement-card .card-header {
            background: linear-gradient(135deg, #1e3c72, #2a5298);
            color: white;
            border-radius: 15px 15px 0 0;
            padding: 1rem 1.5rem;
        }

        .announcement-item {
            padding: 1.5rem;
            border-bottom: 1px solid #eee;
            transition: background-color 0.2s ease;
        }

        .announcement-item:last-child {
            border-bottom: none;
        }

        .announcement-item:hover {
            background-color: #f8f9fa;
        }

        .btn-add-announcement {
            background: rgba(255,255,255,0.2);
            border: 1px solid rgba(255,255,255,0.3);
            color: white;
            transition: all 0.2s ease;
        }

        .btn-add-announcement:hover {
            background: rgba(255,255,255,0.3);
            color: white;
        }

        .dashboard-title {
            color: #1e3c72;
            font-weight: 600;
            margin-bottom: 2rem;
        }
    </style>
</head>
<body>
    <?php include '../includes/admin_navbar.php'; ?>

    <div class="container mt-4">
        <h2 class="dashboard-title">Admin Dashboard</h2>
        <div class="row mt-4">
            <div class="col-md-4">
                <div class="card stat-card text-white bg-primary mb-3">
                    <div class="card-body">
                        <div class="icon">
                            <i class="bi bi-mortarboard-fill"></i>
                        </div>
                        <h5 class="card-title">Total Students</h5>
                        <p class="card-text"><?php echo $student_count; ?></p>
                    </div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="card stat-card text-white bg-success mb-3">
                    <div class="card-body">
                        <div class="icon">
                            <i class="bi bi-people-fill"></i>
                        </div>
                        <h5 class="card-title">Total Parents</h5>
                        <p class="card-text"><?php echo $parent_count; ?></p>
                    </div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="card stat-card text-white bg-info mb-3">
                    <div class="card-body">
                        <div class="icon">
                            <i class="bi bi-book-fill"></i>
                        </div>
                        <h5 class="card-title">Total Courses</h5>
                        <p class="card-text"><?php echo $course_count; ?></p>
                    </div>
                </div>
            </div>
        </div>

        <!-- Recent Announcements -->
        <div class="row mt-4">
            <div class="col-md-12">
                <div class="card announcement-card">
                    <div class="card-header d-flex justify-content-between align-items-center">
                        <h5 class="mb-0">Recent Announcements</h5>
                        <a href="announcements.php" class="btn btn-add-announcement">
                            <i class="bi bi-plus-lg me-2"></i>Add New
                        </a>
                    </div>
                    <div class="card-body p-0">
                        <?php
                        $announcements = $conn->query("SELECT * FROM announcements ORDER BY created_at DESC LIMIT 5");
                        while ($announcement = $announcements->fetch_assoc()):
                        ?>
                        <div class="announcement-item">
                            <h5 class="text-primary"><?php echo htmlspecialchars($announcement['title']); ?></h5>
                            <p class="mb-2"><?php echo htmlspecialchars($announcement['content']); ?></p>
                            <div class="d-flex align-items-center text-muted">
                                <i class="bi bi-clock me-2"></i>
                                <small>Posted on: <?php echo date('M d, Y', strtotime($announcement['created_at'])); ?></small>
                            </div>
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