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
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
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
            --stat-text: #10b981;
            --header-text: #8b5cf6;
            --primary-color: #8b5cf6;
            --secondary-color: #a78bfa;
            --navbar-bg-start: #e9d5ff;
            --navbar-bg-end: #d8b4fe;
        }

        body {
            background-color: var(--dark-bg);
            color: var(--text-primary);
            font-family: 'Inter', sans-serif;
        }

        .card {
            background: var(--card-bg);
            border: 1px solid var(--card-border);
            border-radius: 0.75rem;
            box-shadow: 0 0.15rem 1.75rem rgba(0, 0, 0, 0.1);
        }

        .card-header {
            background: var(--card-bg);
            border-bottom: 1px solid var(--card-border);
            color: #000000;
        }

        .card-body {
            padding: 1.5rem;
        }

        .table {
            color: var(--text-primary);
            margin-bottom: 0;
        }

        .table thead th {
            background: var(--card-bg);
            color: #000000;
            border-bottom: 1px solid var(--card-border);
            padding: 1rem;
            font-weight: 500;
        }

        .table tbody td {
            border-color: var(--card-border);
            padding: 1rem;
            vertical-align: middle;
        }

        .table-striped > tbody > tr:nth-of-type(odd) {
            background-color: var(--hover-bg);
        }

        .table-striped > tbody > tr:nth-of-type(even) {
            background-color: var(--card-bg);
        }

        .breadcrumb {
            background-color: transparent;
        }

        .breadcrumb-item a {
            color: var(--accent-purple);
            text-decoration: none;
        }

        .breadcrumb-item.active {
            color: var(--text-secondary);
        }

        .btn-success {
            background: var(--success-color);
            border: none;
            color: white;
        }

        .btn-success:hover {
            background: #0ea271;
            color: white;
        }

        .btn-danger {
            background: #ef4444;
            border: none;
            color: white;
        }

        .btn-danger:hover {
            background: #dc2626;
            color: white;
        }

        .alert-info {
            background-color: var(--hover-bg);
            border-color: var(--card-border);
            color: var(--text-primary);
        }

        .text-muted {
            color: var(--text-secondary) !important;
        }

        .modal-content {
            background: var(--card-bg);
            border: 1px solid var(--card-border);
            color: #000000;
        }

        .modal-header {
            border-bottom: 1px solid var(--card-border);
        }

        .modal-title {
            color: #000000;
        }

        .modal-footer {
            border-top: 1px solid var(--card-border);
        }

        .form-label {
            color: #000000 !important;
            font-weight: 500;
        }

        .form-control {
            background: var(--hover-bg);
            border: 1px solid var(--card-border);
            color: #000000;
        }

        .form-control:focus {
            background: var(--hover-bg);
            border-color: var(--accent-purple);
            color: #000000;
            box-shadow: 0 0 0 0.25rem rgba(139, 92, 246, 0.25);
        }

        .btn-close {
            filter: none;
            background-color: #ffffff;
            color: #000000;
        }

        .btn-secondary {
            background: #ffffff;
            border: 1px solid var(--card-border);
            color: #000000;
            font-weight: 500;
        }

        .btn-secondary:hover {
            background: #f0f0f0;
            border-color: var(--card-border);
            color: #000000;
        }
    </style>
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
                                            <th>Year Level</th>
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