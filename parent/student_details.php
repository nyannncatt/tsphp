<?php
require_once '../includes/auth_middleware.php';
checkAuth();
checkRole(['parent']);
require_once '../config/database.php';

$user_id = getUserId();
$student_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

// Verify that this parent has access to this student
$access_check = "
    SELECT s.* 
    FROM students s
    JOIN student_parent sp ON s.id = sp.student_id
    JOIN parents p ON sp.parent_id = p.id
    WHERE p.user_id = ? AND s.id = ?
";
$stmt = $conn->prepare($access_check);
$stmt->bind_param("ii", $user_id, $student_id);
$stmt->execute();
$student = $stmt->get_result()->fetch_assoc();

if (!$student) {
    header("Location: dashboard.php");
    exit();
}

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
$stmt->bind_param("ii", $student_id, $student_id);
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
$stmt->bind_param("i", $student_id);
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

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Student Details - School Management System</title>
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
                        <li class="breadcrumb-item active">Student Details</li>
                    </ol>
                </nav>
            </div>
        </div>

        <div class="row mb-4">
            <div class="col-md-12">
                <div class="card">
                    <div class="card-header">
                        <h4 class="mb-0">
                            <?php echo htmlspecialchars($student['first_name'] . ' ' . $student['last_name']); ?>
                            <span class="badge bg-secondary">Grade <?php echo $student['grade_level']; ?></span>
                        </h4>
                    </div>
                    <div class="card-body">
                        <!-- Attendance Statistics -->
                        <h5 class="mb-3">Attendance Overview</h5>
                        <div class="row mb-4">
                            <div class="col-md-3">
                                <div class="card bg-success text-white">
                                    <div class="card-body text-center">
                                        <h6 class="card-title">Present</h6>
                                        <h3><?php echo $present_days; ?></h3>
                                        <p class="mb-0">days</p>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="card bg-danger text-white">
                                    <div class="card-body text-center">
                                        <h6 class="card-title">Absent</h6>
                                        <h3><?php echo $absent_days; ?></h3>
                                        <p class="mb-0">days</p>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="card bg-warning text-dark">
                                    <div class="card-body text-center">
                                        <h6 class="card-title">Late</h6>
                                        <h3><?php echo $late_days; ?></h3>
                                        <p class="mb-0">days</p>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="card bg-info text-white">
                                    <div class="card-body text-center">
                                        <h6 class="card-title">Attendance Rate</h6>
                                        <h3><?php echo $attendance_rate; ?>%</h3>
                                        <p class="mb-0">overall</p>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Grades -->
                        <h5 class="mb-3">Course Grades</h5>
                        <div class="table-responsive">
                            <table class="table table-hover">
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
                                            <td><?php echo htmlspecialchars($grade['course_name']); ?></td>
                                            <td>
                                                <small class="text-muted">
                                                    <?php echo htmlspecialchars($grade['description'] ?? ''); ?>
                                                </small>
                                            </td>
                                            <td>
                                                <?php if ($grade['grade']): ?>
                                                    <span class="badge bg-<?php 
                                                        echo $grade['grade'] >= 90 ? 'success' : 
                                                            ($grade['grade'] >= 80 ? 'primary' : 
                                                            ($grade['grade'] >= 70 ? 'warning' : 'danger')); 
                                                    ?>">
                                                        <?php echo number_format($grade['grade'], 2); ?>%
                                                    </span>
                                                <?php else: ?>
                                                    <span class="badge bg-secondary">No grade</span>
                                                <?php endif; ?>
                                            </td>
                                            <td><?php echo htmlspecialchars($grade['grading_period'] ?? ''); ?></td>
                                            <td>
                                                <?php 
                                                    echo $grade['created_at'] 
                                                        ? date('M d, Y', strtotime($grade['created_at']))
                                                        : '';
                                                ?>
                                            </td>
                                        </tr>
                                    <?php endwhile; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html> 