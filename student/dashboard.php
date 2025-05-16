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

// Get recent grades
$query = "SELECT g.*, c.course_name 
          FROM grades g
          JOIN courses c ON g.course_id = c.id
          WHERE g.student_id = ?
          ORDER BY g.created_at DESC LIMIT 5";
$stmt = $conn->prepare($query);
$stmt->bind_param("i", $student['id']);
$stmt->execute();
$recent_grades = $stmt->get_result();

// Get recent attendance
$query = "SELECT * FROM attendance 
          WHERE student_id = ?
          ORDER BY date DESC LIMIT 5";
$stmt = $conn->prepare($query);
$stmt->bind_param("i", $student['id']);
$stmt->execute();
$recent_attendance = $stmt->get_result();

// Get course count
$query = "SELECT COUNT(*) as count FROM student_courses WHERE student_id = ?";
$stmt = $conn->prepare($query);
$stmt->bind_param("i", $student['id']);
$stmt->execute();
$course_count = $stmt->get_result()->fetch_assoc()['count'];
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Student Dashboard - SchoolComSphere</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css" rel="stylesheet">
    <style>
        :root {
            --primary-color: #4e73df;
            --secondary-color: #858796;
            --success-color: #1cc88a;
            --info-color: #36b9cc;
            --warning-color: #f6c23e;
            --danger-color: #e74a3b;
        }
        
        body {
            background-color: #f8f9fc;
            font-family: 'Nunito', -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, "Helvetica Neue", Arial, sans-serif;
        }
        
        .dashboard-header {
            background: linear-gradient(135deg, var(--primary-color), #224abe);
            color: white;
            padding: 2rem 0;
            margin-bottom: 2rem;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }

        .card {
            border: none;
            border-radius: 0.75rem;
            box-shadow: 0 0.15rem 1.75rem rgba(0, 0, 0, 0.1);
            transition: transform 0.2s ease-in-out;
        }

        .card:hover {
            transform: translateY(-5px);
        }

        .card-header {
            background: white;
            border-bottom: 1px solid rgba(0,0,0,0.05);
            padding: 1.25rem;
            border-radius: 0.75rem 0.75rem 0 0 !important;
        }

        .card-header h5 {
            color: var(--primary-color);
            font-weight: 600;
            margin: 0;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }

        .list-group-item {
            border: none;
            border-bottom: 1px solid rgba(0,0,0,0.05);
            padding: 1rem;
        }

        .list-group-item:last-child {
            border-bottom: none;
        }

        .stat-card {
            background: white;
            padding: 1rem;
            border-radius: 0.75rem;
            margin-bottom: 1rem;
        }

        .stat-icon {
            width: 3rem;
            height: 3rem;
            display: flex;
            align-items: center;
            justify-content: center;
            border-radius: 0.5rem;
            font-size: 1.5rem;
        }

        .badge {
            padding: 0.5em 0.75em;
            font-weight: 600;
        }

        .progress {
            height: 0.5rem;
            border-radius: 0.25rem;
        }

        .welcome-message {
            font-size: 2rem;
            font-weight: 700;
            margin-bottom: 0.5rem;
        }

        .profile-info {
            color: rgba(255,255,255,0.9);
            font-size: 1.1rem;
        }

        .quick-stats {
            background: white;
            border-radius: 0.75rem;
            padding: 1rem;
            margin-bottom: 1.5rem;
        }
    </style>
</head>
<body>
    <?php include '../includes/student_navbar.php'; ?>

    <div class="dashboard-header">
        <div class="container">
            <div class="row align-items-center">
                <div class="col-md-8">
                    <h1 class="welcome-message">Welcome back, <?php echo htmlspecialchars($student['first_name']); ?>! 👋</h1>
                    <p class="profile-info mb-0">
                        <i class="bi bi-mortarboard-fill me-2"></i>Year Level: <?php echo htmlspecialchars($student['grade_level']); ?> |
                        <i class="bi bi-envelope-fill me-2"></i><?php echo htmlspecialchars($student['email']); ?>
                    </p>
                </div>
            </div>
        </div>
    </div>

    <div class="container">
        <!-- Quick Stats Row -->
        <div class="row mb-4">
            <div class="col-md-12">
                <div class="quick-stats">
                    <div class="d-flex align-items-center">
                        <div class="stat-icon bg-primary bg-opacity-10 text-primary me-3">
                            <i class="bi bi-book"></i>
                        </div>
                        <div>
                            <h6 class="mb-0">Enrolled Courses</h6>
                            <h3 class="mb-0"><?php echo $course_count; ?></h3>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="row">
            <div class="col-md-4">
                <div class="card mb-4">
                    <div class="card-header">
                        <h5><i class="bi bi-person-badge me-2"></i>Personal Information</h5>
                    </div>
                    <div class="card-body">
                        <div class="d-flex align-items-center mb-4">
                            <div class="bg-primary bg-opacity-10 rounded-circle p-3 me-3">
                                <i class="bi bi-person-circle text-primary" style="font-size: 2rem;"></i>
                            </div>
                            <div>
                                <h6 class="mb-1"><?php echo htmlspecialchars($student['first_name'] . ' ' . $student['last_name']); ?></h6>
                                <p class="mb-0 text-muted">Student ID: #<?php echo htmlspecialchars($student['id']); ?></p>
                            </div>
                        </div>
                        <div class="mb-3">
                            <label class="text-muted mb-1">Year Level</label>
                            <p class="mb-2 fw-bold"><?php echo htmlspecialchars($student['grade_level']); ?></p>
                            <div class="progress">
                                <div class="progress-bar" role="progressbar" style="width: 75%" aria-valuenow="75" aria-valuemin="0" aria-valuemax="100"></div>
                            </div>
                        </div>
                        <div class="mb-3">
                            <label class="text-muted mb-1">Email Address</label>
                            <p class="mb-0 fw-bold"><?php echo htmlspecialchars($student['email']); ?></p>
                        </div>
                        <div>
                            <label class="text-muted mb-1">Username</label>
                            <p class="mb-0 fw-bold"><?php echo htmlspecialchars($student['username']); ?></p>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-md-4">
                <div class="card mb-4">
                    <div class="card-header">
                        <h5><i class="bi bi-graph-up me-2"></i>Recent Grades</h5>
                    </div>
                    <div class="card-body p-0">
                        <div class="list-group list-group-flush">
                            <?php while ($grade = $recent_grades->fetch_assoc()): ?>
                            <div class="list-group-item">
                                <div class="d-flex justify-content-between align-items-center">
                                    <div>
                                        <h6 class="mb-1"><?php echo htmlspecialchars($grade['course_name']); ?></h6>
                                        <small class="text-muted">
                                            <i class="bi bi-calendar3 me-1"></i>
                                            <?php echo date('M d, Y', strtotime($grade['created_at'])); ?>
                                        </small>
                                    </div>
                                    <span class="badge bg-<?php 
                                        echo $grade['grade'] >= 90 ? 'success' : 
                                            ($grade['grade'] >= 80 ? 'primary' : 
                                                ($grade['grade'] >= 70 ? 'warning' : 'danger')); 
                                    ?>">
                                        <?php echo htmlspecialchars($grade['grade']); ?>%
                                    </span>
                                </div>
                            </div>
                            <?php endwhile; ?>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-md-4">
                <div class="card mb-4">
                    <div class="card-header">
                        <h5><i class="bi bi-calendar-check me-2"></i>Recent Attendance</h5>
                    </div>
                    <div class="card-body p-0">
                        <div class="list-group list-group-flush">
                            <?php while ($record = $recent_attendance->fetch_assoc()): ?>
                            <div class="list-group-item">
                                <div class="d-flex justify-content-between align-items-center">
                                    <div>
                                        <h6 class="mb-1">
                                            <i class="bi bi-calendar3 me-2"></i>
                                            <?php echo date('M d, Y', strtotime($record['date'])); ?>
                                        </h6>
                                        <span class="badge bg-<?php 
                                            echo $record['status'] == 'present' ? 'success' : 
                                                ($record['status'] == 'absent' ? 'danger' : 'warning'); 
                                        ?>">
                                            <i class="bi bi-<?php 
                                                echo $record['status'] == 'present' ? 'check-circle' : 
                                                    ($record['status'] == 'absent' ? 'x-circle' : 'exclamation-circle'); 
                                            ?> me-1"></i>
                                            <?php echo ucfirst($record['status']); ?>
                                        </span>
                                    </div>
                                </div>
                            </div>
                            <?php endwhile; ?>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Add smooth scrolling
        document.querySelectorAll('a[href^="#"]').forEach(anchor => {
            anchor.addEventListener('click', function (e) {
                e.preventDefault();
                document.querySelector(this.getAttribute('href')).scrollIntoView({
                    behavior: 'smooth'
                });
            });
        });
    </script>
</body>
</html> 