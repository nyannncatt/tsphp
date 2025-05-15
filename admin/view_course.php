<?php
require_once '../includes/auth_middleware.php';
checkAuth();
checkRole(['admin']);
require_once '../config/database.php';

// Get course ID from URL
$course_id = isset($_GET['id']) ? intval($_GET['id']) : 0;

// Get course details
$query = "SELECT * FROM courses WHERE id = ?";
$stmt = $conn->prepare($query);
$stmt->bind_param("i", $course_id);
$stmt->execute();
$course = $stmt->get_result()->fetch_assoc();

if (!$course) {
    header("Location: courses.php");
    exit();
}

// Get enrolled students
$query = "SELECT s.*, u.email 
          FROM students s
          JOIN users u ON s.user_id = u.id
          JOIN student_courses sc ON s.id = sc.student_id
          WHERE sc.course_id = ?
          ORDER BY s.first_name, s.last_name";
$stmt = $conn->prepare($query);
$stmt->bind_param("i", $course_id);
$stmt->execute();
$enrolled_students = $stmt->get_result();

// Process student enrollment/removal if form submitted
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['action']) && isset($_POST['student_id'])) {
        $student_id = intval($_POST['student_id']);
        
        if ($_POST['action'] === 'remove') {
            $query = "DELETE FROM student_courses WHERE student_id = ? AND course_id = ?";
            $stmt = $conn->prepare($query);
            $stmt->bind_param("ii", $student_id, $course_id);
            $stmt->execute();
        }
        
        // Redirect to refresh the page
        header("Location: view_course.php?id=" . $course_id);
        exit();
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>View Course - School Management System</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.8.1/font/bootstrap-icons.css" rel="stylesheet">
</head>
<body>
    <?php include '../includes/admin_navbar.php'; ?>

    <div class="container mt-4">
        <div class="row mb-4">
            <div class="col-md-12">
                <nav aria-label="breadcrumb">
                    <ol class="breadcrumb">
                        <li class="breadcrumb-item"><a href="courses.php">Courses</a></li>
                        <li class="breadcrumb-item active"><?php echo htmlspecialchars($course['course_name']); ?></li>
                    </ol>
                </nav>
            </div>
        </div>

        <div class="row">
            <div class="col-md-4">
                <div class="card mb-4">
                    <div class="card-header">
                        <h4 class="mb-0">Course Details</h4>
                    </div>
                    <div class="card-body">
                        <h5 class="card-title"><?php echo htmlspecialchars($course['course_name']); ?></h5>
                        <p class="card-text">
                            <?php echo $course['description'] ? htmlspecialchars($course['description']) : '<em>No description available</em>'; ?>
                        </p>
                        <p class="card-text">
                            <small class="text-muted">Created: <?php echo date('M d, Y', strtotime($course['created_at'])); ?></small>
                        </p>
                        <div class="d-grid gap-2">
                            <a href="enroll_students.php?course_id=<?php echo $course_id; ?>" class="btn btn-success">
                                <i class="bi bi-person-plus"></i> Enroll Students
                            </a>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-md-8">
                <div class="card">
                    <div class="card-header d-flex justify-content-between align-items-center">
                        <h4 class="mb-0">Enrolled Students</h4>
                        <span class="badge bg-primary"><?php echo $enrolled_students->num_rows; ?> Students</span>
                    </div>
                    <div class="card-body">
                        <?php if ($enrolled_students->num_rows > 0): ?>
                            <div class="table-responsive">
                                <table class="table table-hover">
                                    <thead>
                                        <tr>
                                            <th>Name</th>
                                            <th>Email</th>
                                            <th>Grade Level</th>
                                            <th>Actions</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php while ($student = $enrolled_students->fetch_assoc()): ?>
                                            <tr>
                                                <td><?php echo htmlspecialchars($student['first_name'] . ' ' . $student['last_name']); ?></td>
                                                <td><?php echo htmlspecialchars($student['email']); ?></td>
                                                <td><?php echo htmlspecialchars($student['grade_level']); ?></td>
                                                <td>
                                                    <form method="POST" action="" class="d-inline" 
                                                          onsubmit="return confirm('Are you sure you want to remove this student from the course?');">
                                                        <input type="hidden" name="action" value="remove">
                                                        <input type="hidden" name="student_id" value="<?php echo $student['id']; ?>">
                                                        <button type="submit" class="btn btn-danger btn-sm">
                                                            <i class="bi bi-person-x"></i> Remove
                                                        </button>
                                                    </form>
                                                </td>
                                            </tr>
                                        <?php endwhile; ?>
                                    </tbody>
                                </table>
                            </div>
                        <?php else: ?>
                            <div class="alert alert-info">
                                No students are currently enrolled in this course.
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