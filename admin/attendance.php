<?php
require_once '../includes/auth_middleware.php';
checkAuth();
checkRole(['admin']);
require_once '../config/database.php';

$success_message = '';
$error_message = '';

// Process attendance submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $student_id = intval($_POST['student_id']);
    $date = $_POST['date'];
    $status = $_POST['status'];

    if (empty($date) || empty($status)) {
        $error_message = "Date and status are required.";
    } else {
        // Check if attendance already exists for this student on this date
        $check_query = "SELECT id FROM attendance WHERE student_id = ? AND date = ?";
        $stmt = $conn->prepare($check_query);
        $stmt->bind_param("is", $student_id, $date);
        $stmt->execute();
        
        if ($stmt->get_result()->num_rows > 0) {
            // Update existing attendance
            $query = "UPDATE attendance SET status = ? WHERE student_id = ? AND date = ?";
            $stmt = $conn->prepare($query);
            $stmt->bind_param("sis", $status, $student_id, $date);
        } else {
            // Insert new attendance
            $query = "INSERT INTO attendance (student_id, date, status) VALUES (?, ?, ?)";
            $stmt = $conn->prepare($query);
            $stmt->bind_param("iss", $student_id, $date, $status);
        }
        
        if ($stmt->execute()) {
            $success_message = "Attendance recorded successfully.";
        } else {
            $error_message = "Error recording attendance: " . $conn->error;
        }
    }
}

// Get all students
$query = "SELECT s.*, u.email 
          FROM students s
          JOIN users u ON s.user_id = u.id
          ORDER BY s.first_name, s.last_name";
$students = $conn->query($query);

// Get recent attendance records
$query = "SELECT a.*, s.first_name, s.last_name, s.grade_level 
          FROM attendance a
          JOIN students s ON a.student_id = s.id
          ORDER BY a.date DESC, s.first_name, s.last_name
          LIMIT 50";
$recent_attendance = $conn->query($query);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Attendance Management - School Management System</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.8.1/font/bootstrap-icons.css" rel="stylesheet">
</head>
<body>
    <?php include '../includes/admin_navbar.php'; ?>

    <div class="container mt-4">
        <div class="row mb-4">
            <div class="col-md-12">
                <h2>Attendance Management</h2>
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
                        <h5 class="mb-0">Record Attendance</h5>
                    </div>
                    <div class="card-body">
                        <form method="POST" action="">
                            <div class="mb-3">
                                <label for="student_id" class="form-label">Student</label>
                                <select class="form-select" id="student_id" name="student_id" required>
                                    <option value="">Select Student</option>
                                    <?php while ($student = $students->fetch_assoc()): ?>
                                        <option value="<?php echo $student['id']; ?>">
                                            <?php echo htmlspecialchars($student['first_name'] . ' ' . $student['last_name']); ?>
                                            (Year level: <?php echo htmlspecialchars($student['grade_level']); ?>)
                                        </option>
                                    <?php endwhile; ?>
                                </select>
                            </div>

                            <div class="mb-3">
                                <label for="date" class="form-label">Date</label>
                                <input type="date" class="form-control" id="date" name="date" 
                                       value="<?php echo date('Y-m-d'); ?>" required>
                            </div>

                            <div class="mb-3">
                                <label for="status" class="form-label">Status</label>
                                <select class="form-select" id="status" name="status" required>
                                    <option value="">Select Status</option>
                                    <option value="present">Present</option>
                                    <option value="absent">Absent</option>
                                    <option value="late">Late</option>
                                    <option value="excused">Excused</option>
                                </select>
                            </div>

                            <div class="d-grid">
                                <button type="submit" class="btn btn-primary">Record Attendance</button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>

            <div class="col-md-8">
                <div class="card">
                    <div class="card-header d-flex justify-content-between align-items-center">
                        <h5 class="mb-0">Recent Attendance Records</h5>
                        <span class="badge bg-primary"><?php echo $recent_attendance->num_rows; ?> Records</span>
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-hover">
                                <thead>
                                    <tr>
                                        <th>Date</th>
                                        <th>Student</th>
                                        <th>Year Level</th>
                                        <th>Status</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php while ($record = $recent_attendance->fetch_assoc()): ?>
                                        <tr>
                                            <td><?php echo date('M d, Y', strtotime($record['date'])); ?></td>
                                            <td><?php echo htmlspecialchars($record['first_name'] . ' ' . $record['last_name']); ?></td>
                                            <td><?php echo htmlspecialchars($record['grade_level']); ?></td>
                                            <td>
                                                <span class="badge bg-<?php 
                                                    echo $record['status'] == 'present' ? 'success' : 
                                                        ($record['status'] == 'absent' ? 'danger' : 
                                                        ($record['status'] == 'late' ? 'warning' : 'info')); 
                                                ?>">
                                                    <?php echo ucfirst(htmlspecialchars($record['status'])); ?>
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
    <script>
        // Set minimum date to prevent future attendance
        document.getElementById('date').max = new Date().toISOString().split('T')[0];
    </script>
</body>
</html> 