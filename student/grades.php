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

// Get all grades for the student
$query = "SELECT g.*, c.course_name, c.description
          FROM grades g
          JOIN courses c ON g.course_id = c.id
          WHERE g.student_id = ?
          ORDER BY g.created_at DESC";
$stmt = $conn->prepare($query);
$stmt->bind_param("i", $student['id']);
$stmt->execute();
$grades = $stmt->get_result();

// Calculate GPA
$total_grade = 0;
$grade_count = 0;
$grades_array = [];
while ($grade = $grades->fetch_assoc()) {
    $total_grade += $grade['grade'];
    $grade_count++;
    $grades_array[] = $grade;
}
$gpa = $grade_count > 0 ? round($total_grade / $grade_count, 2) : 0;
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Grades - School Management System</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
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
        .table {
            color: #ffffff;
        }
        .table > :not(caption) > * > * {
            background-color: #242639;
            border-bottom-color: #2f3245;
        }
        .table tbody tr:hover {
            background-color: #2f3245 !important;
        }
        .text-muted {
            color: #a0aec0 !important;
        }
        .alert-info {
            background-color: #2f3245;
            border-color: #3f4259;
            color: #ffffff;
        }
        .table thead th {
            border-bottom: 2px solid #2f3245;
            color: #8b5cf6;
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
                        <li class="breadcrumb-item active">My Grades</li>
                    </ol>
                </nav>
            </div>
        </div>

        <div class="row mb-4">
            <div class="col-md-12">
                <div class="card">
                    <div class="card-header">
                        <div class="d-flex justify-content-between align-items-center">
                            <h4 class="mb-0">My Grades</h4>
                            <span class="badge bg-primary fs-5">GPA: <?php echo $gpa; ?></span>
                        </div>
                    </div>
                    <div class="card-body">
                        <?php if (empty($grades_array)): ?>
                            <div class="alert alert-info">
                                No grades available yet.
                            </div>
                        <?php else: ?>
                            <div class="table-responsive">
                                <table class="table table-hover">
                                    <thead>
                                        <tr>
                                            <th>Course</th>
                                            <th>Grade</th>
                                            <th>Date</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($grades_array as $grade): ?>
                                        <tr>
                                            <td>
                                                <strong><?php echo htmlspecialchars($grade['course_name']); ?></strong>
                                                <?php if ($grade['description']): ?>
                                                    <br>
                                                    <small class="text-muted"><?php echo htmlspecialchars($grade['description']); ?></small>
                                                <?php endif; ?>
                                            </td>
                                            <td>
                                                <span class="badge bg-<?php 
                                                    echo $grade['grade'] >= 90 ? 'success' : 
                                                        ($grade['grade'] >= 80 ? 'primary' : 
                                                        ($grade['grade'] >= 70 ? 'warning' : 'danger')); 
                                                ?>">
                                                    <?php echo htmlspecialchars($grade['grade']); ?>
                                                </span>
                                            </td>
                                            <td><?php echo date('M d, Y', strtotime($grade['created_at'])); ?></td>
                                        </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
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