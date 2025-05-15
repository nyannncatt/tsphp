<?php
require_once '../includes/auth_middleware.php';
checkAuth();
checkRole(['admin']);
require_once '../config/database.php';

$success_message = '';
$error_message = '';

// Get course ID from URL
$course_id = isset($_GET['course_id']) ? intval($_GET['course_id']) : 0;

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

// Function to get available students
function getAvailableStudents($conn, $course_id) {
    $query = "SELECT s.*, u.email 
              FROM students s
              JOIN users u ON s.user_id = u.id
              WHERE s.id NOT IN (
                  SELECT student_id FROM student_courses WHERE course_id = ?
              )
              ORDER BY s.first_name, s.last_name";
    $stmt = $conn->prepare($query);
    $stmt->bind_param("i", $course_id);
    $stmt->execute();
    return $stmt->get_result();
}

// Get initial list of available students
$available_students = getAvailableStudents($conn, $course_id);

// Process student enrollment if form submitted
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['student_ids'])) {
    $success_count = 0;
    $error_count = 0;
    
    foreach ($_POST['student_ids'] as $student_id) {
        $student_id = intval($student_id);
        
        // Check if enrollment already exists
        $check_query = "SELECT 1 FROM student_courses WHERE student_id = ? AND course_id = ?";
        $stmt = $conn->prepare($check_query);
        $stmt->bind_param("ii", $student_id, $course_id);
        $stmt->execute();
        
        if ($stmt->get_result()->num_rows === 0) {
            // Enroll student
            $enroll_query = "INSERT INTO student_courses (student_id, course_id) VALUES (?, ?)";
            $stmt = $conn->prepare($enroll_query);
            $stmt->bind_param("ii", $student_id, $course_id);
            
            if ($stmt->execute()) {
                $success_count++;
            } else {
                $error_count++;
            }
        }
    }
    
    if ($success_count > 0) {
        $success_message = "$success_count student(s) enrolled successfully.";
    }
    if ($error_count > 0) {
        $error_message = "$error_count student(s) could not be enrolled.";
    }
    
    // Refresh available students list using the function
    $available_students = getAvailableStudents($conn, $course_id);
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Enroll Students - School Management System</title>
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
                        <li class="breadcrumb-item"><a href="view_course.php?id=<?php echo $course_id; ?>"><?php echo htmlspecialchars($course['course_name']); ?></a></li>
                        <li class="breadcrumb-item active">Enroll Students</li>
                    </ol>
                </nav>
            </div>
        </div>

        <div class="row">
            <div class="col-md-12">
                <div class="card">
                    <div class="card-header">
                        <h4 class="mb-0">Enroll Students in <?php echo htmlspecialchars($course['course_name']); ?></h4>
                    </div>
                    <div class="card-body">
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

                        <?php if ($available_students->num_rows > 0): ?>
                            <form method="POST" action="">
                                <div class="table-responsive">
                                    <table class="table table-hover">
                                        <thead>
                                            <tr>
                                                <th>
                                                    <div class="form-check">
                                                        <input class="form-check-input" type="checkbox" id="selectAll">
                                                    </div>
                                                </th>
                                                <th>Name</th>
                                                <th>Email</th>
                                                <th>Grade Level</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php while ($student = $available_students->fetch_assoc()): ?>
                                                <tr>
                                                    <td>
                                                        <div class="form-check">
                                                            <input class="form-check-input student-checkbox" type="checkbox" 
                                                                   name="student_ids[]" value="<?php echo $student['id']; ?>">
                                                        </div>
                                                    </td>
                                                    <td><?php echo htmlspecialchars($student['first_name'] . ' ' . $student['last_name']); ?></td>
                                                    <td><?php echo htmlspecialchars($student['email']); ?></td>
                                                    <td><?php echo htmlspecialchars($student['grade_level']); ?></td>
                                                </tr>
                                            <?php endwhile; ?>
                                        </tbody>
                                    </table>
                                </div>

                                <div class="mt-3">
                                    <button type="submit" class="btn btn-primary" id="enrollButton" disabled>
                                        <i class="bi bi-person-plus"></i> Enroll Selected Students
                                    </button>
                                    <a href="view_course.php?id=<?php echo $course_id; ?>" class="btn btn-secondary">
                                        <i class="bi bi-arrow-left"></i> Back to Course
                                    </a>
                                </div>
                            </form>
                        <?php else: ?>
                            <div class="alert alert-info">
                                No students available for enrollment.
                            </div>
                            <a href="view_course.php?id=<?php echo $course_id; ?>" class="btn btn-secondary">
                                <i class="bi bi-arrow-left"></i> Back to Course
                            </a>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Handle select all checkbox
        document.getElementById('selectAll').addEventListener('change', function() {
            const checkboxes = document.getElementsByClassName('student-checkbox');
            for (let checkbox of checkboxes) {
                checkbox.checked = this.checked;
            }
            updateEnrollButton();
        });

        // Handle individual checkboxes
        const studentCheckboxes = document.getElementsByClassName('student-checkbox');
        for (let checkbox of studentCheckboxes) {
            checkbox.addEventListener('change', updateEnrollButton);
        }

        // Update enroll button state
        function updateEnrollButton() {
            const checkboxes = document.getElementsByClassName('student-checkbox');
            const checkedCount = Array.from(checkboxes).filter(cb => cb.checked).length;
            document.getElementById('enrollButton').disabled = checkedCount === 0;
        }
    </script>
</body>
</html> 