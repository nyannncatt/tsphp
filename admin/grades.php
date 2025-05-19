<?php
require_once '../includes/auth_middleware.php';
checkAuth();
checkRole(['admin']);
require_once '../config/database.php';

$success_message = '';
$error_message = '';

// Process grade submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $student_id = intval($_POST['student_id']);
    $course_id = intval($_POST['course_id']);
    $grade = trim($_POST['grade']);

    if (empty($grade)) {
        $error_message = "Grade is required.";
    } else {
        $query = "INSERT INTO grades (student_id, course_id, grade) VALUES (?, ?, ?)";
        $stmt = $conn->prepare($query);
        $stmt->bind_param("iis", $student_id, $course_id, $grade);
        
        if ($stmt->execute()) {
            $success_message = "Grade added successfully.";
        } else {
            $error_message = "Error adding grade: " . $conn->error;
        }
    }
}

// Get all courses
$query = "SELECT * FROM courses ORDER BY course_name";
$courses = $conn->query($query);

// Get all students with their latest grades
$query = "SELECT s.id, s.first_name, s.last_name, s.grade_level, 
          c.id as course_id, c.course_name,
          (SELECT g.grade 
           FROM grades g 
           WHERE g.student_id = s.id 
           AND g.course_id = c.id 
           ORDER BY g.created_at DESC 
           LIMIT 1) as current_grade
          FROM students s
          CROSS JOIN courses c
          ORDER BY s.first_name, s.last_name, c.course_name";
$grades = $conn->query($query);

// Organize grades by student
$students_grades = [];
while ($row = $grades->fetch_assoc()) {
    $student_id = $row['id'];
    if (!isset($students_grades[$student_id])) {
        $students_grades[$student_id] = [
            'name' => $row['first_name'] . ' ' . $row['last_name'],
            'grade_level' => $row['grade_level'],
            'courses' => []
        ];
    }
    $students_grades[$student_id]['courses'][] = [
        'course_id' => $row['course_id'],
        'course_name' => $row['course_name'],
        'grade' => $row['current_grade']
    ];
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Grades Management - School Management System</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.8.1/font/bootstrap-icons.css" rel="stylesheet">
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
            margin-bottom: 1.5rem;
        }

        .card-header {
            background: var(--card-bg);
            border-bottom: 1px solid var(--card-border);
            padding: 1.25rem;
            border-radius: 0.75rem 0.75rem 0 0 !important;
        }

        .card-header h5 {
            color: var(--header-text);
            font-weight: 600;
            margin: 0;
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
            color: #000000;
        }

        .table-hover tbody tr:hover {
            background-color: var(--hover-bg);
        }

        .btn-primary {
            background: var(--accent-purple);
            border: none;
            color: white;
        }

        .btn-primary:hover {
            background: var(--secondary-color);
        }

        .alert {
            background: var(--card-bg);
            border: 1px solid var(--card-border);
            color: #000000;
        }

        .alert-success {
            border-left: 4px solid var(--success-color);
        }

        .alert-danger {
            border-left: 4px solid #ef4444;
        }

        .form-label {
            color: #000000;
            font-weight: 500;
        }

        .form-control, .form-select {
            background: #ffffff;
            border: 1px solid var(--card-border);
            color: #000000;
        }

        .form-control:focus, .form-select:focus {
            background: #ffffff;
            border-color: var(--accent-purple);
            color: #000000;
            box-shadow: 0 0 0 0.25rem rgba(139, 92, 246, 0.25);
        }

        /* Remove arrows from number inputs */
        input[type="number"]::-webkit-inner-spin-button,
        input[type="number"]::-webkit-outer-spin-button {
            -webkit-appearance: none;
            margin: 0;
        }

        input[type="number"] {
            -moz-appearance: textfield;
        }

        /* Custom select arrow */
        .form-select {
            background: #ffffff url("data:image/svg+xml,%3csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 16 16'%3e%3cpath fill='none' stroke='%23000000' stroke-linecap='round' stroke-linejoin='round' stroke-width='2' d='m2 5 6 6 6-6'/%3e%3c/svg%3e") no-repeat right 0.75rem center/16px 12px;
            cursor: pointer;
            padding-right: 2.5rem;
        }

        .form-select:focus {
            background: #ffffff url("data:image/svg+xml,%3csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 16 16'%3e%3cpath fill='none' stroke='%238b5cf6' stroke-linecap='round' stroke-linejoin='round' stroke-width='2' d='m2 5 6 6 6-6'/%3e%3c/svg%3e") no-repeat right 0.75rem center/16px 12px;
        }

        .form-select option {
            background-color: #ffffff;
            color: #000000;
            padding: 0.5rem;
        }

        .text-muted {
            color: #666666 !important;
        }

        .d-grid .btn {
            padding: 0.75rem;
            font-weight: 500;
        }
    </style>
</head>
<body>
    <?php include '../includes/admin_navbar.php'; ?>

    <div class="container mt-4">
        <div class="row mb-4">
            <div class="col-md-12">
                <h2>Grades Management</h2>
            </div>
        </div>

        <?php if ($success_message): ?>
            <div class="alert alert-success">
                <?php echo htmlspecialchars($success_message); ?>
            </div>
        <?php endif; ?>

        <?php if ($error_message): ?>
            <div class="alert alert-danger">
                <?php echo htmlspecialchars($error_message); ?>
            </div>
        <?php endif; ?>

        <div class="row mb-4">
            <div class="col-md-4">
                <div class="card">
                    <div class="card-header">
                        <h5 class="mb-0">Add New Grade</h5>
                    </div>
                    <div class="card-body">
                        <form method="POST" action="">
                            <div class="mb-3">
                                <label for="student_id" class="form-label">Student</label>
                                <select class="form-select" id="student_id" name="student_id" required>
                                    <option value="">Select Student</option>
                                    <?php foreach ($students_grades as $id => $student): ?>
                                        <option value="<?php echo $id; ?>">
                                            <?php echo htmlspecialchars($student['name']); ?> 
                                            (Grade <?php echo htmlspecialchars($student['grade_level']); ?>)
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>

                            <div class="mb-3">
                                <label for="course_id" class="form-label">Course</label>
                                <select class="form-select" id="course_id" name="course_id" required>
                                    <option value="">Select Course</option>
                                    <?php while ($course = $courses->fetch_assoc()): ?>
                                        <option value="<?php echo $course['id']; ?>">
                                            <?php echo htmlspecialchars($course['course_name']); ?>
                                        </option>
                                    <?php endwhile; ?>
                                </select>
                            </div>

                            <div class="mb-3">
                                <label for="grade" class="form-label">Grade</label>
                                <input type="text" class="form-control" id="grade" name="grade" required
                                       placeholder="Enter grade (e.g., A, B+, 95)">
                            </div>

                            <div class="d-grid">
                                <button type="submit" class="btn btn-primary">Add Grade</button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>

            <div class="col-md-8">
                <div class="card">
                    <div class="card-header">
                        <h5 class="mb-0">Current Grades</h5>
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-hover">
                                <thead>
                                    <tr>
                                        <th>Student</th>
                                        <th>Year Level</th>
                                        <th>Course</th>
                                        <th>Current Grade</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($students_grades as $student_id => $student): ?>
                                        <?php foreach ($student['courses'] as $course): ?>
                                            <tr>
                                                <td><?php echo htmlspecialchars($student['name']); ?></td>
                                                <td><?php echo htmlspecialchars($student['grade_level']); ?></td>
                                                <td><?php echo htmlspecialchars($course['course_name']); ?></td>
                                                <td>
                                                    <?php if ($course['grade']): ?>
                                                        <?php echo htmlspecialchars($course['grade']); ?>
                                                    <?php else: ?>
                                                        <span class="text-muted">Not graded</span>
                                                    <?php endif; ?>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    <?php endforeach; ?>
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