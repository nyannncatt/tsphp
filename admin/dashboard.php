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
        }

        body {
            background-color: var(--dark-bg);
            color: var(--text-primary);
            font-family: 'Inter', sans-serif;
            min-height: 100vh;
        }

        .dashboard-title {
            color: var(--text-primary);
            font-weight: 600;
            margin-bottom: 2rem;
            font-size: 1.5rem;
        }

        .stat-card {
            background: var(--card-bg);
            border: 1px solid var(--card-border);
            border-radius: 1rem;
            padding: 1.5rem;
            height: 100%;
            transition: all 0.3s ease;
        }

        .stat-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 10px 20px rgba(0,0,0,0.2);
        }

        .stat-card .icon {
            font-size: 2rem;
            margin-bottom: 1rem;
            color: var(--accent-purple);
        }

        .stat-card .card-title {
            color: var(--text-secondary);
            font-size: 0.875rem;
            margin-bottom: 0.5rem;
        }

        .stat-card .card-text {
            color: var(--stat-text);
            font-size: 2rem;
            font-weight: 600;
            margin: 0;
        }

        .stat-value {
            color: var(--stat-text);
            font-size: 1.5rem;
            font-weight: 600;
        }

        .announcement-card {
            background: var(--card-bg);
            border: 1px solid var(--card-border);
            border-radius: 1rem;
            margin-bottom: 1.5rem;
        }

        .announcement-card .card-header {
            background: var(--card-bg);
            border-bottom: 1px solid var(--card-border);
            padding: 1.25rem;
            border-radius: 1rem 1rem 0 0;
        }

        .announcement-card .card-header h5 {
            color: var(--header-text);
            margin: 0;
            font-size: 1.1rem;
            font-weight: 600;
            letter-spacing: 0.5px;
        }

        .btn-add-announcement {
            background: var(--accent-purple);
            color: white;
            border: none;
            padding: 0.5rem 1rem;
            border-radius: 0.5rem;
            font-size: 0.875rem;
            transition: all 0.2s ease;
        }

        .btn-add-announcement:hover {
            background: #7c4ef3;
            color: white;
        }

        .announcement-item {
            padding: 1.25rem;
            border-bottom: 1px solid var(--card-border);
            transition: background-color 0.2s ease;
        }

        .announcement-item:last-child {
            border-bottom: none;
        }

        .announcement-item:hover {
            background-color: var(--hover-bg);
        }

        .announcement-item h5 {
            color: var(--stat-text);
            font-size: 1rem;
            margin-bottom: 0.5rem;
        }

        .announcement-item p {
            color: var(--text-secondary);
            font-size: 0.875rem;
            margin-bottom: 0.5rem;
        }

        .announcement-item .text-muted {
            color: var(--text-secondary) !important;
            font-size: 0.75rem;
        }

        .search-bar {
            background: var(--card-bg);
            border: 1px solid var(--card-border);
            border-radius: 0.5rem;
            padding: 0.5rem 1rem;
            color: var(--text-primary);
            margin-bottom: 2rem;
        }

        .search-bar input {
            background: transparent;
            border: none;
            color: var(--text-primary);
            width: 100%;
            outline: none;
        }

        .search-bar input::placeholder {
            color: var(--text-secondary);
        }

        .top-stats {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 1.5rem;
            margin-bottom: 2rem;
        }

        @media (max-width: 768px) {
            .top-stats {
                grid-template-columns: 1fr;
            }
        }
    </style>
</head>
<body>
    <?php include '../includes/admin_navbar.php'; ?>

    <div class="container py-4">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h2 class="dashboard-title mb-0">Admin Dashboard</h2>
            <div class="search-bar d-flex align-items-center">
                <i class="bi bi-search me-2 text-secondary"></i>
                <input type="text" placeholder="Search for anything..." />
            </div>
                    </div>

        <div class="top-stats">
            <div class="stat-card">
                <div class="icon">
                    <i class="bi bi-mortarboard-fill"></i>
                </div>
                <h5 class="card-title">Total Students</h5>
                <p class="card-text"><?php echo $student_count; ?></p>
            </div>
            <div class="stat-card">
                <div class="icon">
                    <i class="bi bi-people-fill"></i>
                </div>
                        <h5 class="card-title">Total Parents</h5>
                <p class="card-text"><?php echo $parent_count; ?></p>
                    </div>
            <div class="stat-card">
                <div class="icon">
                    <i class="bi bi-book-fill"></i>
                </div>
                <h5 class="card-title">Total Courses</h5>
                <p class="card-text"><?php echo $course_count; ?></p>
            </div>
            <div class="stat-card">
                <div class="icon">
                    <i class="bi bi-graph-up"></i>
                </div>
                <h5 class="card-title">System Growth</h5>
                <p class="stat-value">+24.5%</p>
            </div>
        </div>

        <!-- Recent Announcements -->
        <div class="announcement-card">
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
                            <h5><?php echo htmlspecialchars($announcement['title']); ?></h5>
                            <p><?php echo htmlspecialchars($announcement['content']); ?></p>
                    <div class="d-flex align-items-center text-muted">
                        <i class="bi bi-clock me-2"></i>
                        <small>Posted on: <?php echo date('M d, Y', strtotime($announcement['created_at'])); ?></small>
                    </div>
                </div>
                <?php endwhile; ?>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Add smooth hover effects
        document.querySelectorAll('.stat-card').forEach(card => {
            card.addEventListener('mouseover', function() {
                this.style.transform = 'translateY(-5px)';
            });
            card.addEventListener('mouseout', function() {
                this.style.transform = 'translateY(0)';
            });
        });
    </script>
</body>
</html> 