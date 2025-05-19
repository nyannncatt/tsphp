<?php
require_once '../includes/auth_middleware.php';
checkAuth();
checkRole(['student']);
require_once '../config/database.php';

// Get student information
$user_id = $_SESSION['user_id'];
$query = "SELECT s.*, u.email, u.username 
          FROM students s
          JOIN users u ON s.user_id = u.id
          WHERE s.user_id = ?";
$stmt = $conn->prepare($query);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$student = $stmt->get_result()->fetch_assoc();

// Get enrolled courses
$query = "SELECT c.* 
          FROM courses c
          JOIN student_courses sc ON c.id = sc.course_id
          WHERE sc.student_id = ?
          ORDER BY c.course_name";
$stmt = $conn->prepare($query);
$stmt->bind_param("i", $student['id']);
$stmt->execute();
$courses = $stmt->get_result();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Courses - School Management System</title>
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

        .breadcrumb-item a {
            color: var(--accent-purple);
            text-decoration: none;
        }
        
        .breadcrumb-item a:hover {
            color: #7c3aed;
        }
        
        .breadcrumb-item.active {
            color: var(--text-primary);
        }

        .card {
            background: #242639;
            border: 1px solid var(--card-border);
            border-radius: 1rem;
            box-shadow: 0 8px 20px rgba(0, 0, 0, 0.2);
            transition: transform 0.2s ease-in-out;
        }

        .card:hover {
            transform: translateY(-5px);
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

        .card-title {
            color: var(--text-primary);
            font-weight: 600;
            margin-bottom: 1rem;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }

        .card-text {
            color: var(--text-secondary);
        }

        .alert-info {
            background-color: #2f3245;
            border-color: #3f4259;
            color: var(--text-primary);
        }

        /* Course card specific styles */
        .row-cols-1 .card {
            height: 100%;
            display: flex;
            flex-direction: column;
        }

        .row-cols-1 .card .card-body {
            flex: 1;
            display: flex;
            flex-direction: column;
        }

        .row-cols-1 .card .card-title {
            font-size: 1.25rem;
            color: var(--accent-purple);
        }

        .row-cols-1 .card .card-text {
            flex: 1;
            font-size: 0.95rem;
            line-height: 1.5;
        }

        /* Add some visual flair to course cards */
        .row-cols-1 .card::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 3px;
            background: var(--accent-purple);
            border-radius: 1rem 1rem 0 0;
            opacity: 0;
            transition: opacity 0.2s ease-in-out;
        }

        .row-cols-1 .card:hover::before {
            opacity: 1;
        }
    </style>
</head>
<body>
    <?php include '../includes/student_navbar.php'; ?>

    <div class="container mt-4">
        <div class="row mb-4">
            <div class="col-md-12">
                <nav aria-label="breadcrumb">
                    <ol class="breadcrumb">
                        <li class="breadcrumb-item"><a href="dashboard.php">Dashboard</a></li>
                        <li class="breadcrumb-item active">My Courses</li>
                    </ol>
                </nav>
            </div>
        </div>

        <div class="row mb-4">
            <div class="col-md-12">
                <div class="card">
                    <div class="card-header">
                        <h4 class="mb-0">My Courses</h4>
                    </div>
                    <div class="card-body">
                        <?php if ($courses->num_rows == 0): ?>
                            <div class="alert alert-info">
                                You are not enrolled in any courses yet.
                            </div>
                        <?php else: ?>
                            <div class="row row-cols-1 row-cols-md-3 g-4">
                                <?php while ($course = $courses->fetch_assoc()): ?>
                                <div class="col">
                                    <div class="card h-100">
                                        <div class="card-body">
                                            <h5 class="card-title">
                                                <i class="bi bi-book text-purple me-2"></i>
                                                <?php echo htmlspecialchars($course['course_name']); ?>
                                            </h5>
                                            <?php if ($course['description']): ?>
                                                <p class="card-text">
                                                    <i class="bi bi-info-circle me-2"></i>
                                                    <?php echo htmlspecialchars($course['description']); ?>
                                                </p>
                                            <?php endif; ?>
                                        </div>
                                    </div>
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