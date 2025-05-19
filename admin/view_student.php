<?php
require_once '../includes/auth_middleware.php';
checkAuth();
checkRole(['admin']);
require_once '../config/database.php';

// Get student details
if (!isset($_GET['id'])) {
    header("Location: students.php");
    exit();
}

$student_id = (int)$_GET['id'];
$query = "SELECT s.*, u.username, u.email 
          FROM students s
          JOIN users u ON s.user_id = u.id
          WHERE s.id = ?";
$stmt = $conn->prepare($query);
$stmt->bind_param("i", $student_id);
$stmt->execute();
$student = $stmt->get_result()->fetch_assoc();

if (!$student) {
    header("Location: students.php");
    exit();
}

// Get student's grades
$query = "SELECT g.*, c.course_name 
          FROM grades g
          JOIN courses c ON g.course_id = c.id
          WHERE g.student_id = ?
          ORDER BY g.created_at DESC";
$stmt = $conn->prepare($query);
$stmt->bind_param("i", $student_id);
$stmt->execute();
$grades = $stmt->get_result();

// Get student's attendance
$query = "SELECT * FROM attendance 
          WHERE student_id = ?
          ORDER BY date DESC";
$stmt = $conn->prepare($query);
$stmt->bind_param("i", $student_id);
$stmt->execute();
$attendance = $stmt->get_result();
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
    <?php include '../includes/admin_navbar.php'; ?>

    <div class="container mt-4">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h2>Student Details</h2>
            <a href="students.php" class="btn btn-secondary">Back to Students</a>
        </div>

        <div class="row">
            <div class="col-md-6">
                <div class="card mb-4">
                    <div class="card-header">
                        <h5 class="mb-0">Personal Information</h5>
                    </div>
                    <div class="card-body">
                        <table class="table">
                            <tr>
                                <th>Name:</th>
                                <td><?php echo htmlspecialchars($student['first_name'] . ' ' . $student['last_name']); ?></td>
                            </tr>
                            <tr>
                                <th>Username:</th>
                                <td><?php echo htmlspecialchars($student['username']); ?></td>
                            </tr>
                            <tr>
                                <th>Email:</th>
                                <td><?php echo htmlspecialchars($student['email']); ?></td>
                            </tr>
                            <tr>
                                <th>Year Level:</th>
                                <td><?php echo htmlspecialchars($student['grade_level']); ?></td>
                            </tr>
                        </table>
                    </div>
                </div>
            </div>

            <div class="col-md-6">
                <div class="card mb-4">
                    <div class="card-header">
                        <h5 class="mb-0">Recent Grades</h5>
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table">
                                <thead>
                                    <tr>
                                        <th>Course</th>
                                        <th>Grade</th>
                                        <th>Date</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php while ($grade = $grades->fetch_assoc()): ?>
                                    <tr>
                                        <td><?php echo htmlspecialchars($grade['course_name']); ?></td>
                                        <td><?php echo htmlspecialchars($grade['grade']); ?></td>
                                        <td><?php echo date('M d, Y', strtotime($grade['created_at'])); ?></td>
                                    </tr>
                                    <?php endwhile; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>

                <div class="card">
                    <div class="card-header">
                        <h5 class="mb-0">Recent Attendance</h5>
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table">
                                <thead>
                                    <tr>
                                        <th>Date</th>
                                        <th>Status</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php while ($record = $attendance->fetch_assoc()): ?>
                                    <tr>
                                        <td><?php echo date('M d, Y', strtotime($record['date'])); ?></td>
                                        <td>
                                            <span class="badge bg-<?php 
                                                echo $record['status'] == 'present' ? 'success' : 
                                                    ($record['status'] == 'absent' ? 'danger' : 'warning'); 
                                            ?>">
                                                <?php echo ucfirst($record['status']); ?>
                                            </span>
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