<?php
require_once '../includes/auth_middleware.php';
checkAuth();
checkRole(['parent']);
require_once '../config/database.php';

$user_id = getUserId();

// Get parent information
$parent_query = "SELECT p.* FROM parents p WHERE p.user_id = ?";
$stmt = $conn->prepare($parent_query);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$parent = $stmt->get_result()->fetch_assoc();

// Get children information with summary data
$children_query = "
    SELECT 
        s.*, 
        u.username, 
        u.email,
        (SELECT COUNT(*) FROM attendance a WHERE a.student_id = s.id AND status = 'present') as present_days,
        (SELECT COUNT(*) FROM attendance a WHERE a.student_id = s.id AND status = 'absent') as absent_days,
        (SELECT COUNT(*) FROM attendance a WHERE a.student_id = s.id AND status = 'late') as late_days,
        (SELECT AVG(g.grade) FROM grades g WHERE g.student_id = s.id) as average_grade,
        (SELECT COUNT(*) FROM student_courses sc WHERE sc.student_id = s.id) as course_count
    FROM students s
    JOIN users u ON s.user_id = u.id
    JOIN student_parent sp ON s.id = sp.student_id
    WHERE sp.parent_id = ?
    ORDER BY s.grade_level, s.last_name, s.first_name
";
$stmt = $conn->prepare($children_query);
$stmt->bind_param("i", $parent['id']);
$stmt->execute();
$children = $stmt->get_result();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Student - SchoolComSphere System</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.8.1/font/bootstrap-icons.css" rel="stylesheet">
    <style>
        body {
            background-color: #1a1b2e;
            color: #ffffff;
        }
        .breadcrumb-item a {
            color: #8b5cf6;
            text-decoration: none;
        }
        .breadcrumb-item a:hover {
            color: #7c3aed;
        }
        .breadcrumb-item.active {
            color: #ffffff;
        }
        .card {
            background-color: #242639;
            border: 1px solid #2f3245;
        }
        .card-header {
            background-color: #2f3245;
            border-bottom: 1px solid #2f3245;
            color: #ffffff;
        }
        .text-muted {
            color: #a0aec0 !important;
        }
        .alert-info {
            background-color: #2f3245;
            border-color: #3f4259;
            color: #ffffff;
        }

        .student-stats {
            background-color: #242639;
            border: 1px solid #2f3245;
            border-radius: 0.5rem;
            padding: 1rem;
        }

        .student-stats strong {
            color: #ffffff;
            font-size: 1.2rem;
        }

        .student-email {
            background-color: #242639;
            padding: 0.5rem;
            border-radius: 0.5rem;
            border: 1px solid #2f3245;
            color: #ffffff;
        }
        
        .student-email div {
            color: #ffffff;
        }

        .attendance-box {
            border-radius: 0.5rem;
            padding: 1rem;
            text-align: center;
            border: 1px solid #2f3245;
            background-color: #242639;
        }

        .attendance-box.present {
            background-color: #242639;
            border-color: #2f3245;
        }

        .attendance-box.late {
            background-color: #242639;
            border-color: #2f3245;
        }

        .attendance-box.absent {
            background-color: #242639;
            border-color: #2f3245;
        }

        .attendance-box strong {
            font-size: 1.5rem;
            display: block;
            margin-top: 0.5rem;
            color: #ffffff;
        }

        .badge {
            padding: 0.5rem 0.8rem;
            font-weight: 500;
        }

        .text-purple {
            color: #8b5cf6 !important;
        }

        .btn-primary {
            background-color: #8b5cf6;
            border-color: #8b5cf6;
            padding: 0.5rem 1rem;
            font-weight: 500;
            border-radius: 0.5rem;
        }

        .btn-primary:hover {
            background-color: #7c3aed;
            border-color: #7c3aed;
        }
    </style>
</head>
<body>
    <?php include '../includes/parent_navbar.php'; ?>

    <div class="container mt-4">
        <div class="row mb-4">
            <div class="col-md-12">
                <nav aria-label="breadcrumb">
                    <ol class="breadcrumb">
                        <li class="breadcrumb-item"><a href="dashboard.php">Dashboard</a></li>
                        <li class="breadcrumb-item active">My Student</li>
                    </ol>
                </nav>
            </div>
        </div>

        <div class="row mb-4">
            <div class="col-md-12">
                <div class="card">
                    <div class="card-header">
                        <h4 class="mb-0">
                            <i class="bi bi-mortarboard-fill me-2 text-purple"></i>
                            My Student
                        </h4>
                    </div>
                    <div class="card-body">
                        <?php if ($children->num_rows == 0): ?>
                            <div class="alert alert-info">
                                <i class="bi bi-info-circle me-2"></i>
                                No children records found.
                            </div>
                        <?php else: ?>
                            <div class="row row-cols-1 row-cols-md-2 g-4">
                                <?php while ($child = $children->fetch_assoc()): ?>
                                    <div class="col">
                                        <div class="card h-100">
                                            <div class="card-header">
                                                <h5 class="mb-0 d-flex align-items-center">
                                                    <i class="bi bi-person-badge me-2 text-purple"></i>
                                                    <?php echo htmlspecialchars($child['first_name'] . ' ' . $child['last_name']); ?>
                                                    <span class="badge ms-2" style="background-color: var(--accent-purple);">Year level: <?php echo $child['grade_level']; ?></span>
                                                </h5>
                                            </div>
                                            <div class="card-body">
                                                <!-- Basic Info -->
                                                <div class="mb-3 student-email">
                                                    <small class="text-muted">
                                                        <i class="bi bi-envelope me-1"></i>
                                                        Student Email:
                                                    </small>
                                                    <div class="mt-1"><?php echo htmlspecialchars($child['email']); ?></div>
                                                </div>

                                                <!-- Academic Summary -->
                                                <div class="mb-3">
                                                    <h6 class="d-flex align-items-center">
                                                        <i class="bi bi-book me-2 text-purple"></i>
                                                        Academic Overview
                                                    </h6>
                                                    <div class="row g-2">
                                                        <div class="col-6">
                                                            <div class="student-stats">
                                                                <small class="text-muted d-block">Average Grade</small>
                                                                <span class="badge bg-<?php 
                                                    echo $child['average_grade'] >= 90 ? 'success' : 
                                                        ($child['average_grade'] >= 80 ? 'primary' : 
                                                        ($child['average_grade'] >= 70 ? 'warning' : 'danger')); 
                                                ?>">
                                                                    <?php echo $child['average_grade'] ? number_format($child['average_grade'], 1) . '%' : 'N/A'; ?>
                                                                </span>
                                                            </div>
                                                        </div>
                                                        <div class="col-6">
                                                            <div class="student-stats">
                                                                <small class="text-muted d-block">Enrolled Courses</small>
                                                                <strong><?php echo $child['course_count']; ?></strong>
                                                            </div>
                                                        </div>
                                                    </div>
                                                </div>

                                                <!-- Attendance Summary -->
                                                <div class="mb-3">
                                                    <h6 class="d-flex align-items-center">
                                                        <i class="bi bi-calendar-check me-2 text-purple"></i>
                                                        Attendance Summary
                                                    </h6>
                                                    <div class="row g-2">
                                                        <div class="col-4">
                                                            <div class="attendance-box present">
                                                                <small class="text-muted">Present</small>
                                                                <strong><?php echo $child['present_days']; ?></strong>
                                                            </div>
                                                        </div>
                                                        <div class="col-4">
                                                            <div class="attendance-box late">
                                                                <small class="text-muted">Late</small>
                                                                <strong><?php echo $child['late_days']; ?></strong>
                                                            </div>
                                                        </div>
                                                        <div class="col-4">
                                                            <div class="attendance-box absent">
                                                                <small class="text-muted">Absent</small>
                                                                <strong><?php echo $child['absent_days']; ?></strong>
                                                            </div>
                                                        </div>
                                                    </div>
                                                </div>

                                                <!-- Action Buttons -->
                                                <div class="d-grid gap-2">
                                                    <a href="student_details.php?id=<?php echo $child['id']; ?>" 
                                                       class="btn btn-primary">
                                                        <i class="bi bi-eye me-2"></i>
                                                        View Full Details
                                                    </a>
                                                </div>
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