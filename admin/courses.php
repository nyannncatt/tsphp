<?php
require_once '../includes/auth_middleware.php';
checkAuth();
checkRole(['admin']);
require_once '../config/database.php';

// Get all courses
$query = "SELECT * FROM courses ORDER BY course_name";
$courses = $conn->query($query);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Courses - SchoolComSphere</title>
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

        h2 {
            color: var(--header-text);
            font-weight: 600;
        }

        .card {
            background: var(--card-bg);
            border: 1px solid var(--card-border);
            border-radius: 0.75rem;
            box-shadow: 0 0.15rem 1.75rem rgba(0, 0, 0, 0.1);
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
            color: var(--header-text);
            border-bottom: 1px solid var(--card-border);
            padding: 1rem;
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

        .btn-primary {
            background: var(--accent-purple);
            border: none;
            color: white;
        }

        .btn-primary:hover {
            background: var(--secondary-color);
        }

        .btn-info {
            background: var(--accent-purple);
            border: none;
            color: white;
        }

        .btn-info:hover {
            background: var(--secondary-color);
            color: white;
        }

        .btn-secondary {
            background: var(--hover-bg);
            border: 1px solid var(--card-border);
            color: var(--text-primary);
        }

        .btn-secondary:hover {
            background: var(--card-border);
            border-color: var(--card-border);
            color: var(--text-primary);
        }

        .modal-content {
            background: var(--card-bg);
            border: 1px solid var(--card-border);
            color: var(--text-primary);
        }

        .modal-header {
            border-bottom: 1px solid var(--card-border);
        }

        .modal-footer {
            border-top: 1px solid var(--card-border);
        }

        .form-label {
            color: var(--text-secondary);
        }

        .form-control {
            background: var(--hover-bg);
            border: 1px solid var(--card-border);
            color: var(--text-primary);
        }

        .form-control:focus {
            background: var(--hover-bg);
            border-color: var(--accent-purple);
            color: var(--text-primary);
            box-shadow: 0 0 0 0.25rem rgba(139, 92, 246, 0.25);
        }

        .btn-close {
            filter: invert(1) grayscale(100%) brightness(200%);
        }
    </style>
</head>
<body>
    <?php include '../includes/admin_navbar.php'; ?>

    <div class="container mt-4">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h2>Manage Courses</h2>
            <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addCourseModal">
                <i class="bi bi-plus-circle me-2"></i>Add New Course
            </button>
        </div>

        <div class="card shadow-sm">
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-striped">
                        <thead>
                            <tr>
                                <th>Course Name</th>
                                <th>Description</th>
                                <th>Created At</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php while ($course = $courses->fetch_assoc()): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($course['course_name']); ?></td>
                                <td><?php echo htmlspecialchars($course['description']); ?></td>
                                <td><?php echo date('M d, Y', strtotime($course['created_at'])); ?></td>
                                <td>
                                    <a href="view_course.php?id=<?php echo $course['id']; ?>" class="btn btn-sm btn-info">
                                        <i class="bi bi-eye me-1"></i>View
                                    </a>
                                </td>
                            </tr>
                            <?php endwhile; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <!-- Add Course Modal -->
    <div class="modal fade" id="addCourseModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Add New Course</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <form action="add_course.php" method="POST">
                    <div class="modal-body">
                        <div class="mb-3">
                            <label for="course_name" class="form-label">Course Name</label>
                            <input type="text" class="form-control" id="course_name" name="course_name" required>
                        </div>
                        <div class="mb-3">
                            <label for="description" class="form-label">Description</label>
                            <textarea class="form-control" id="description" name="description" rows="3"></textarea>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                        <button type="submit" class="btn btn-primary">
                            <i class="bi bi-plus-circle me-1"></i>Add Course
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html> 