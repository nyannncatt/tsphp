<?php
require_once '../includes/auth_middleware.php';
checkAuth();
checkRole(['admin']);
require_once '../config/database.php';

$success_message = '';
$error_message = '';

// Get student ID from URL
$student_id = isset($_GET['id']) ? intval($_GET['id']) : 0;

// Get student details
$query = "SELECT s.*, u.email, u.username 
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

// Process form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $first_name = trim($_POST['first_name']);
    $last_name = trim($_POST['last_name']);
    $grade_level = intval($_POST['grade_level']);
    $email = trim($_POST['email']);

    if (empty($first_name) || empty($last_name) || empty($grade_level)) {
        $error_message = "First name, last name, and year level are required.";
    } else {
        // Start transaction
        $conn->begin_transaction();
        try {
            // Update student information
            $query = "UPDATE students 
                     SET first_name = ?, last_name = ?, grade_level = ?
                     WHERE id = ?";
            $stmt = $conn->prepare($query);
            $stmt->bind_param("ssii", $first_name, $last_name, $grade_level, $student_id);
            $stmt->execute();

            // Update email in users table
            $query = "UPDATE users SET email = ? WHERE id = ?";
            $stmt = $conn->prepare($query);
            $stmt->bind_param("si", $email, $student['user_id']);
            $stmt->execute();

            $conn->commit();
            $success_message = "Student information updated successfully.";

            // Refresh student data
            $query = "SELECT s.*, u.email, u.username 
                     FROM students s
                     JOIN users u ON s.user_id = u.id
                     WHERE s.id = ?";
            $stmt = $conn->prepare($query);
            $stmt->bind_param("i", $student_id);
            $stmt->execute();
            $student = $stmt->get_result()->fetch_assoc();

        } catch (Exception $e) {
            $conn->rollback();
            $error_message = "Error updating student information: " . $e->getMessage();
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Student - School Management System</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>
    <?php include '../includes/admin_navbar.php'; ?>

    <div class="container mt-4">
        <div class="row mb-4">
            <div class="col-md-12">
                <nav aria-label="breadcrumb">
                    <ol class="breadcrumb">
                        <li class="breadcrumb-item"><a href="students.php">Students</a></li>
                        <li class="breadcrumb-item active">Edit Student</li>
                    </ol>
                </nav>
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

        <div class="row">
            <div class="col-md-8 offset-md-2">
                <div class="card">
                    <div class="card-header">
                        <h4 class="mb-0">Edit Student Information</h4>
                    </div>
                    <div class="card-body">
                        <form method="POST" action="">
                            <div class="row mb-3">
                                <div class="col-md-6">
                                    <label for="first_name" class="form-label">First Name *</label>
                                    <input type="text" class="form-control" id="first_name" name="first_name" 
                                           value="<?php echo htmlspecialchars($student['first_name']); ?>" required>
                                </div>
                                <div class="col-md-6">
                                    <label for="last_name" class="form-label">Last Name *</label>
                                    <input type="text" class="form-control" id="last_name" name="last_name" 
                                           value="<?php echo htmlspecialchars($student['last_name']); ?>" required>
                                </div>
                            </div>

                            <div class="mb-3">
                                <label class="form-label">Username</label>
                                <input type="text" class="form-control" value="<?php echo htmlspecialchars($student['username']); ?>" readonly>
                                <small class="text-muted">Username cannot be changed</small>
                            </div>

                            <div class="mb-3">
                                <label for="email" class="form-label">Email *</label>
                                <input type="email" class="form-control" id="email" name="email" 
                                       value="<?php echo htmlspecialchars($student['email']); ?>" required>
                            </div>

                            <div class="mb-3">
                                <label for="grade_level" class="form-label">Year Level *</label>
                                <select class="form-select" id="grade_level" name="grade_level" required>
                                    <?php for ($i = 1; $i <= 12; $i++): ?>
                                        <option value="<?php echo $i; ?>" 
                                            <?php echo $student['grade_level'] == $i ? 'selected' : ''; ?>>
                                            Year <?php echo $i; ?>
                                        </option>
                                    <?php endfor; ?>
                                </select>
                            </div>

                            <div class="d-grid gap-2">
                                <button type="submit" class="btn btn-primary">Update Student Information</button>
                                <a href="students.php" class="btn btn-secondary">Back to Students List</a>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html> 