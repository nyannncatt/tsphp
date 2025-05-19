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

// Get all attendance records for the student
$query = "SELECT a.* 
          FROM attendance a
          WHERE a.student_id = ?
          ORDER BY a.date DESC";
$stmt = $conn->prepare($query);
$stmt->bind_param("i", $student['id']);
$stmt->execute();
$attendance_records = $stmt->get_result();

// Calculate attendance statistics
$total_days = 0;
$present_days = 0;
$absent_days = 0;
$late_days = 0;
$records_array = [];

while ($record = $attendance_records->fetch_assoc()) {
    $total_days++;
    switch ($record['status']) {
        case 'present':
            $present_days++;
            break;
        case 'absent':
            $absent_days++;
            break;
        case 'late':
            $late_days++;
            break;
    }
    $records_array[] = $record;
}

$attendance_rate = $total_days > 0 ? round(($present_days + $late_days) * 100 / $total_days, 1) : 0;
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Attendance - School Management System</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
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
        }
        
        body {
            background-color: var(--dark-bg);
            color: var(--text-primary);
            font-family: 'Inter', sans-serif;
            min-height: 100vh;
        }

        .breadcrumb-item a {
            color: var(--accent-purple);
            text-decoration: none;
        }
        
        .breadcrumb-item a:hover {
            color: #7c3aed;
        }
        
        .breadcrumb-item.active {
            color: var(--text-primary);
        }

        .card {
            background: #242639;
            border: 1px solid var(--card-border);
            border-radius: 1rem;
            box-shadow: 0 8px 20px rgba(0, 0, 0, 0.2);
        }

        .card-header {
            background: #242639;
            border-bottom: 1px solid var(--card-border);
            padding: 1.25rem;
            border-radius: 1rem 1rem 0 0 !important;
            color: var(--text-primary);
        }

        .card-body {
            background: #242639;
            border-radius: 0 0 1rem 1rem;
            color: var(--text-primary);
        }

        .table {
            color: var(--text-primary);
        }

        .table > :not(caption) > * > * {
            background-color: #242639;
            border-bottom-color: var(--card-border);
            color: var(--text-primary);
        }

        .table tbody tr:hover {
            background-color: var(--hover-bg) !important;
        }

        .table thead th {
            border-bottom: 2px solid var(--card-border);
            color: var(--accent-purple);
        }

        .alert-info {
            background-color: #2f3245;
            border-color: #3f4259;
            color: var(--text-primary);
        }

        /* Style adjustments for stat cards */
        .stat-card {
            background: #242639;
            border: 1px solid var(--card-border);
            transition: transform 0.2s ease-in-out;
        }

        .stat-card:hover {
            transform: translateY(-5px);
        }

        .stat-card .card-body {
            padding: 1.5rem;
        }

        /* Override Bootstrap badge styles */
        .badge.bg-primary {
            background-color: var(--accent-purple) !important;
        }

        /* Custom styles for attendance stat cards */
        .card.bg-success, .card.bg-danger, .card.bg-warning, .card.bg-info {
            border: none;
            transition: transform 0.2s ease-in-out;
            overflow: hidden;
            border-radius: 1rem;
        }

        .card.bg-success .card-body {
            background-color: #10b981 !important;
            border-radius: 1rem;
            padding: 1.5rem;
        }

        .card.bg-danger .card-body {
            background-color: #ef4444 !important;
            border-radius: 1rem;
            padding: 1.5rem;
        }

        .card.bg-warning .card-body {
            background-color: #f59e0b !important;
            border-radius: 1rem;
            padding: 1.5rem;
        }

        .card.bg-info .card-body {
            background-color: #3b82f6 !important;
            border-radius: 1rem;
            padding: 1.5rem;
        }

        .card.bg-success:hover, .card.bg-danger:hover, .card.bg-warning:hover, .card.bg-info:hover {
            transform: translateY(-5px);
        }

        /* Additional styles for stat card content */
        .card-body h5.card-title {
            font-size: 1rem;
            font-weight: 500;
            margin-bottom: 0.75rem;
        }

        .card-body h2 {
            font-size: 2.5rem;
            font-weight: 700;
            margin-bottom: 0.5rem;
        }

        .card-body p.mb-0 {
            font-size: 0.875rem;
            opacity: 0.9;
        }

        .text-muted {
            color: var(--text-secondary) !important;
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
                        <li class="breadcrumb-item active">My Attendance</li>
                    </ol>
                </nav>
            </div>
        </div>

        <div class="row mb-4">
            <div class="col-md-12">
                <div class="card">
                    <div class="card-header">
                        <div class="d-flex justify-content-between align-items-center">
                            <h4 class="mb-0">My Attendance</h4>
                            <span class="badge bg-primary fs-5">Attendance Rate: <?php echo $attendance_rate; ?>%</span>
                        </div>
                    </div>
                    <div class="card-body">
                        <!-- Attendance Statistics -->
                        <div class="row mb-4">
                            <div class="col-md-3">
                                <div class="card bg-success text-white">
                                    <div class="card-body text-center">
                                        <h5 class="card-title">Present</h5>
                                        <h2><?php echo $present_days; ?></h2>
                                        <p class="mb-0">days</p>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="card bg-danger text-white">
                                    <div class="card-body text-center">
                                        <h5 class="card-title">Absent</h5>
                                        <h2><?php echo $absent_days; ?></h2>
                                        <p class="mb-0">days</p>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="card bg-warning text-dark">
                                    <div class="card-body text-center">
                                        <h5 class="card-title">Late</h5>
                                        <h2><?php echo $late_days; ?></h2>
                                        <p class="mb-0">days</p>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="card bg-info text-white">
                                    <div class="card-body text-center">
                                        <h5 class="card-title">Total Days</h5>
                                        <h2><?php echo $total_days; ?></h2>
                                        <p class="mb-0">days</p>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Attendance Records Table -->
                        <?php if (empty($records_array)): ?>
                            <div class="alert alert-info">
                                No attendance records available yet.
                            </div>
                        <?php else: ?>
                            <div class="table-responsive">
                                <table class="table table-hover">
                                    <thead>
                                        <tr>
                                            <th>Date</th>
                                            <th>Status</th>
                                            <th>Remarks</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($records_array as $record): ?>
                                        <tr>
                                            <td><?php echo date('M d, Y', strtotime($record['date'])); ?></td>
                                            <td>
                                                <span class="badge bg-<?php 
                                                    echo $record['status'] == 'present' ? 'success' : 
                                                        ($record['status'] == 'absent' ? 'danger' : 'warning'); 
                                                ?>">
                                                    <?php echo ucfirst($record['status']); ?>
                                                </span>
                                            </td>
                                            <td><?php echo htmlspecialchars($record['remarks'] ?? ''); ?></td>
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