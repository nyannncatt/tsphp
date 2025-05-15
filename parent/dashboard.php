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

// Get children information with detailed data
$children_query = "
    SELECT s.*, u.username, u.email
    FROM students s
    JOIN users u ON s.user_id = u.id
    JOIN student_parent sp ON s.id = sp.student_id
    WHERE sp.parent_id = ?
";
$stmt = $conn->prepare($children_query);
$stmt->bind_param("i", $parent['id']);
$stmt->execute();
$children = $stmt->get_result();

// Get recent announcements
$announcements = $conn->query("SELECT * FROM announcements ORDER BY created_at DESC LIMIT 5");
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Parent Dashboard - School Management System</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.8.1/font/bootstrap-icons.css" rel="stylesheet">
    <style>
        body {
            background-color: #f8f9fa;
            font-family: 'Segoe UI', system-ui, -apple-system, sans-serif;
        }
        .navbar {
            box-shadow: 0 2px 4px rgba(0,0,0,.08);
        }
        .dashboard-header {
            background: linear-gradient(135deg, #0d6efd 0%, #0099ff 100%);
            color: white;
            padding: 2rem 0;
            margin-bottom: 2rem;
            box-shadow: 0 4px 6px rgba(0,0,0,.05);
        }
        .card {
            border: none;
            border-radius: 10px;
            box-shadow: 0 4px 6px rgba(0,0,0,.04);
            transition: transform 0.2s, box-shadow 0.2s;
            margin-bottom: 1.5rem;
        }
        .card:hover {
            transform: translateY(-5px);
            box-shadow: 0 8px 15px rgba(0,0,0,.08);
        }
        .card-header {
            background: white;
            border-bottom: 1px solid rgba(0,0,0,.05);
            padding: 1.25rem;
            border-radius: 10px 10px 0 0 !important;
        }
        .card-body {
            padding: 1.5rem;
        }
        .stat-card {
            border-radius: 8px;
            padding: 1rem;
            height: 100%;
            min-height: 140px;
            display: flex;
            flex-direction: column;
            justify-content: center;
            align-items: center;
        }
        .stat-card h3 {
            font-size: 2.5rem;
            font-weight: bold;
            margin: 0.5rem 0;
        }
        .stat-card .card-title {
            font-size: 1rem;
            margin-bottom: 0;
        }
        .table {
            margin-bottom: 0;
        }
        .table th {
            border-top: none;
            font-weight: 600;
            color: #495057;
        }
        .badge {
            padding: 0.5rem 0.8rem;
            font-weight: 500;
        }
        .grade-badge {
            font-size: 0.9rem;
            min-width: 60px;
        }
        .announcement-card {
            background: #fff;
            border-radius: 8px;
            padding: 1rem;
            margin-bottom: 1rem;
            border-left: 4px solid #0d6efd;
        }
        .btn {
            border-radius: 6px;
            padding: 0.5rem 1rem;
            font-weight: 500;
        }
        .student-name {
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }
        .student-name .badge {
            font-size: 0.85rem;
        }
        .table-responsive {
            border-radius: 8px;
            background: white;
        }
        .section-title {
            color: #344767;
            font-size: 1.1rem;
            font-weight: 600;
            margin-bottom: 1.25rem;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }
    </style>
</head>
<body>
    <?php include '../includes/parent_navbar.php'; ?>

    <div class="dashboard-header">
        <div class="container">
            <h2 class="mb-0">
                <i class="bi bi-person-circle me-2"></i>
                Welcome, <?php echo htmlspecialchars($parent['first_name'] . ' ' . $parent['last_name']); ?>!
            </h2>
        </div>
    </div>

    <div class="container">
        <div class="row g-4">
            <!-- Children Details -->
            <div class="col-lg-8">
                <?php while ($child = $children->fetch_assoc()): 
                    // Get student's courses and grades
                    $grades_query = "
                        SELECT c.course_name, c.description, g.grade, g.grading_period, g.created_at
                        FROM courses c
                        JOIN student_courses sc ON c.id = sc.course_id
                        LEFT JOIN grades g ON (c.id = g.course_id AND g.student_id = ?)
                        WHERE sc.student_id = ?
                        ORDER BY c.course_name, g.created_at DESC
                    ";
                    $stmt = $conn->prepare($grades_query);
                    $stmt->bind_param("ii", $child['id'], $child['id']);
                    $stmt->execute();
                    $grades = $stmt->get_result();

                    // Get attendance records
                    $attendance_query = "
                        SELECT *
                        FROM attendance
                        WHERE student_id = ?
                        ORDER BY date DESC
                    ";
                    $stmt = $conn->prepare($attendance_query);
                    $stmt->bind_param("i", $child['id']);
                    $stmt->execute();
                    $attendance = $stmt->get_result();

                    // Calculate attendance statistics
                    $total_days = 0;
                    $present_days = 0;
                    $absent_days = 0;
                    $late_days = 0;

                    while ($record = $attendance->fetch_assoc()) {
                        $total_days++;
                        switch ($record['status']) {
                            case 'present':
                                $present_days++;
                                break;
                            case 'absent':
                                $absent_days++;
                                break;
                            case 'late':
                                $late_days++;
                                break;
                        }
                    }

                    $attendance_rate = $total_days > 0 ? round(($present_days + $late_days) * 100 / $total_days, 1) : 0;
                ?>
                    <div class="card">
                        <div class="card-header">
                            <div class="student-name">
                                <i class="bi bi-mortarboard-fill text-primary"></i>
                                <h4 class="mb-0">
                                    <?php echo htmlspecialchars($child['first_name'] . ' ' . $child['last_name']); ?>
                                    <span class="badge bg-secondary ms-2">Grade <?php echo $child['grade_level']; ?></span>
                                </h4>
                            </div>
                        </div>
                        <div class="card-body">
                            <!-- Attendance Statistics -->
                            <h5 class="section-title">
                                <i class="bi bi-calendar-check text-primary"></i>
                                Attendance Overview
                            </h5>
                            <div class="row g-3 mb-4">
                                <div class="col-md-3">
                                    <div class="card bg-success text-white stat-card">
                                        <h6 class="card-title">Present</h6>
                                        <h3><?php echo $present_days; ?></h3>
                                        <p class="mb-0">days</p>
                                    </div>
                                </div>
                                <div class="col-md-3">
                                    <div class="card bg-danger text-white stat-card">
                                        <h6 class="card-title">Absent</h6>
                                        <h3><?php echo $absent_days; ?></h3>
                                        <p class="mb-0">days</p>
                                    </div>
                                </div>
                                <div class="col-md-3">
                                    <div class="card bg-warning text-dark stat-card">
                                        <h6 class="card-title">Late</h6>
                                        <h3><?php echo $late_days; ?></h3>
                                        <p class="mb-0">days</p>
                                    </div>
                                </div>
                                <div class="col-md-3">
                                    <div class="card bg-info text-white stat-card">
                                        <h6 class="card-title">Attendance Rate</h6>
                                        <h3><?php echo $attendance_rate; ?>%</h3>
                                        <p class="mb-0">overall</p>
                                    </div>
                                </div>
                            </div>

                            <!-- Grades -->
                            <h5 class="section-title">
                                <i class="bi bi-book text-primary"></i>
                                Course Grades
                            </h5>
                            <div class="table-responsive">
                                <table class="table table-hover align-middle">
                                    <thead>
                                        <tr>
                                            <th>Course</th>
                                            <th>Description</th>
                                            <th>Grade</th>
                                            <th>Period</th>
                                            <th>Date</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php while ($grade = $grades->fetch_assoc()): ?>
                                            <tr>
                                                <td class="fw-medium"><?php echo htmlspecialchars($grade['course_name']); ?></td>
                                                <td>
                                                    <small class="text-muted">
                                                        <?php echo htmlspecialchars($grade['description'] ?? ''); ?>
                                                    </small>
                                                </td>
                                                <td>
                                                    <?php if ($grade['grade']): ?>
                                                        <span class="badge grade-badge bg-<?php 
                                                            echo $grade['grade'] >= 90 ? 'success' : 
                                                                ($grade['grade'] >= 80 ? 'primary' : 
                                                                ($grade['grade'] >= 70 ? 'warning' : 'danger')); 
                                                        ?>">
                                                            <?php echo number_format($grade['grade'], 2); ?>%
                                                        </span>
                                                    <?php else: ?>
                                                        <span class="badge grade-badge bg-secondary">No grade</span>
                                                    <?php endif; ?>
                                                </td>
                                                <td><?php echo htmlspecialchars($grade['grading_period'] ?? ''); ?></td>
                                                <td>
                                                    <small>
                                                        <?php 
                                                            echo $grade['created_at'] 
                                                                ? date('M d, Y', strtotime($grade['created_at']))
                                                                : '';
                                                        ?>
                                                    </small>
                                                </td>
                                            </tr>
                                        <?php endwhile; ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                <?php endwhile; ?>
            </div>

            <!-- Recent Announcements -->
            <div class="col-lg-4">
                <div class="card">
                    <div class="card-header">
                        <h5 class="mb-0">
                            <i class="bi bi-megaphone-fill text-primary me-2"></i>
                            Recent Announcements
                        </h5>
                    </div>
                    <div class="card-body">
                        <?php while ($announcement = $announcements->fetch_assoc()): ?>
                            <div class="announcement-card">
                                <h6 class="text-primary"><?php echo htmlspecialchars($announcement['title']); ?></h6>
                                <p class="small text-muted mb-2">
                                    <i class="bi bi-calendar3 me-1"></i>
                                    <?php echo date('M d, Y', strtotime($announcement['created_at'])); ?>
                                </p>
                                <p class="mb-0">
                                    <?php 
                                        $content = htmlspecialchars($announcement['content']);
                                        echo strlen($content) > 100 ? substr($content, 0, 100) . '...' : $content;
                                    ?>
                                </p>
                            </div>
                        <?php endwhile; ?>
                        <a href="announcements.php" class="btn btn-primary w-100">
                            <i class="bi bi-eye me-1"></i>
                            View All Announcements
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html> 