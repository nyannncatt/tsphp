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

        /* Custom date input styling */
        input[type="date"] {
            position: relative;
            padding-right: 2.5rem;
        }

        input[type="date"]::-webkit-calendar-picker-indicator {
            background: url("data:image/svg+xml,%3csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 16 16'%3e%3cpath fill='none' stroke='%23000000' stroke-linecap='round' stroke-linejoin='round' stroke-width='2' d='M4 8h8M8 4v8'/%3e%3c/svg%3e") no-repeat center/16px 16px;
            cursor: pointer;
            position: absolute;
            right: 0.75rem;
            filter: invert(0);
        }

        .badge {
            padding: 0.5rem 0.75rem;
            font-weight: 500;
        }

        .badge.bg-success {
            background-color: var(--success-color) !important;
        }

        .badge.bg-danger {
            background-color: #ef4444 !important;
        }

        .badge.bg-warning {
            background-color: #f59e0b !important;
            color: #000000;
        }

        .badge.bg-info {
            background-color: var(--accent-purple) !important;
        }

        .badge.bg-primary {
            background-color: var(--accent-purple) !important;
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