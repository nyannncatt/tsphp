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