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
    <title>Student Dashboard - School Management System</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.8.1/font/bootstrap-icons.css" rel="stylesheet">
</head>
<body>
    <?php include '../includes/student_navbar.php'; ?>

    <div class="container mt-4">
        <div class="row mb-4">
            <div class="col-md-12">
                <h2>Welcome, <?php echo htmlspecialchars($student['first_name']); ?>!</h2>
            </div>
        </div>

        <div class="row">
            <div class="col-md-4">
                <div class="card mb-4">
                    <div class="card-header">
                        <h5 class="mb-0">Personal Information</h5>
                    </div>
                    <div class="card-body">
                        <p><strong>Name:</strong> <?php echo htmlspecialchars($student['first_name'] . ' ' . $student['last_name']); ?></p>
                        <p><strong>Year Level:</strong> <?php echo htmlspecialchars($student['grade_level']); ?></p>
                        <p><strong>Email:</strong> <?php echo htmlspecialchars($student['email']); ?></p>
                        <p><strong>Enrolled Courses:</strong> <?php echo $course_count; ?></p>
                    </div>
                </div>
            </div>

            <div class="col-md-4">
                <div class="card mb-4">
                    <div class="card-header">
                        <h5 class="mb-0">Recent Grades</h5>
                    </div>
                    <div class="card-body">
                        <div class="list-group list-group-flush">
                            <?php while ($grade = $recent_grades->fetch_assoc()): ?>
                            <div class="list-group-item">
                                <h6 class="mb-1"><?php echo htmlspecialchars($grade['course_name']); ?></h6>
                                <p class="mb-1">Grade: <?php echo htmlspecialchars($grade['grade']); ?></p>
                                <small class="text-muted">Date: <?php echo date('M d, Y', strtotime($grade['created_at'])); ?></small>
                            </div>
                            <?php endwhile; ?>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-md-4">
                <div class="card mb-4">
                    <div class="card-header">
                        <h5 class="mb-0">Recent Attendance</h5>
                    </div>
                    <div class="card-body">
                        <div class="list-group list-group-flush">
                            <?php while ($record = $recent_attendance->fetch_assoc()): ?>
                            <div class="list-group-item">
                                <div class="d-flex justify-content-between align-items-center">
                                    <div>
                                        <h6 class="mb-1"><?php echo date('M d, Y', strtotime($record['date'])); ?></h6>
                                        <span class="badge bg-<?php 
                                            echo $record['status'] == 'present' ? 'success' : 
                                                ($record['status'] == 'absent' ? 'danger' : 'warning'); 
                                        ?>">
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

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html> 