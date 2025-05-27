<?php
require_once '../includes/auth_middleware.php';
checkAuth();
checkRole(['parent']);
require_once '../config/database.php';

// Verify and create user_activity table if it doesn't exist
$create_table_query = "
CREATE TABLE IF NOT EXISTS user_activity (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    action VARCHAR(50) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id)
)";
$conn->query($create_table_query);

$user_id = getUserId();

// Get parent information
$parent_query = "SELECT p.*, u.email, u.username 
                FROM parents p 
                JOIN users u ON p.user_id = u.id 
                WHERE p.user_id = ?";
$stmt = $conn->prepare($parent_query);
$stmt->bind_param("i", $_SESSION['user_id']);
$stmt->execute();
$parent = $stmt->get_result()->fetch_assoc();

// Get children information with detailed data
$children_query = "
    SELECT s.*, u.username, u.email
    FROM students s
    JOIN users u ON s.user_id = u.id
    JOIN student_parent sp ON s.id = sp.student_id
    WHERE sp.parent_id = ?
";
$stmt = $conn->prepare($children_query);
$stmt->bind_param("i", $parent['id']);
$stmt->execute();
$children = $stmt->get_result();

// Get login/logout history
$history_query = "SELECT * FROM user_activity 
                 WHERE user_id = ? 
                 AND action IN ('login', 'logout')
                 ORDER BY created_at DESC 
                 LIMIT 10";
$stmt = $conn->prepare($history_query);
$stmt->bind_param("i", $_SESSION['user_id']);
$stmt->execute();
$login_history = $stmt->get_result();

// Get recent incidents
$incidents_query = "SELECT * FROM incidents 
                   WHERE user_id = ? 
                   ORDER BY created_at DESC 
                   LIMIT 5";
$stmt = $conn->prepare($incidents_query);
$stmt->bind_param("i", $_SESSION['user_id']);
$stmt->execute();
$recent_incidents = $stmt->get_result();

// Get recent announcements
$announcements = $conn->query("SELECT * FROM announcements ORDER BY created_at DESC LIMIT 5");
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Parent Dashboard - SchoolComSphere System</title>
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
        }

        body {
            background-color: var(--dark-bg);
            color: var(--text-primary);
            font-family: 'Inter', system-ui, -apple-system, sans-serif;
        }

        .dashboard-header {
            background: #242639;
            color: var(--text-primary);
            padding: 1.5rem 0;
            margin-bottom: 2rem;
            box-shadow: 0 4px 6px rgba(0,0,0,.2);
            border: 1px solid var(--card-border);
            position: relative;
            margin-top: 0;
            border-top: none;
        }

        .dashboard-header::before {
            display: none;
        }

        .dashboard-header h2 {
            color: var(--text-primary);
        }

        .dashboard-header i {
            color: var(--accent-purple);
        }

        .card {
            background: #242639;
            border: 1px solid var(--card-border);
            border-radius: 1rem;
            box-shadow: 0 8px 20px rgba(0,0,0,0.2);
            transition: transform 0.2s ease-in-out;
            margin-bottom: 1.5rem;
        }

        .card:hover {
            transform: translateY(-5px);
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
            padding: 1.5rem;
            color: var(--text-primary);
        }

        .section-title {
            color: var(--text-primary);
            font-size: 1.1rem;
            font-weight: 600;
            margin-bottom: 1.25rem;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }

        .section-title i {
            color: var(--accent-purple);
        }

        /* Table styles */
        .table {
            color: var(--text-primary);
            margin-bottom: 0;
        }

        .table > :not(caption) > * > * {
            background-color: #242639;
            border-bottom-color: var(--card-border);
            color: var(--text-primary);
        }

        .table tbody tr:hover {
            background-color: var(--hover-bg) !important;
        }

        .table th {
            border-top: none;
            font-weight: 600;
            color: var(--accent-purple);
        }

        .text-muted {
            color: var(--text-secondary) !important;
        }

        /* Student name section */
        .student-name {
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }

        .student-name i {
            color: var(--accent-purple);
        }

        .student-name h4 {
            color: var(--text-primary);
            margin: 0;
        }

        /* Stat cards */
        .stat-card {
            border-radius: 1rem;
            padding: 1.5rem;
            height: 100%;
            min-height: 140px;
            display: flex;
            flex-direction: column;
            justify-content: center;
            align-items: center;
            transition: transform 0.2s ease-in-out;
        }

        .stat-card:hover {
            transform: translateY(-5px);
        }

        .stat-card h3 {
            font-size: 2.5rem;
            font-weight: bold;
            margin: 0.5rem 0;
        }

        .stat-card .card-title {
            font-size: 1rem;
            margin-bottom: 0;
        }

        /* Announcement cards */
                    .announcement-card {
            background: var(--card-bg);
            border-radius: 0.5rem;
            padding: 1rem;
            margin-bottom: 1rem;
            border-left: 4px solid var(--accent-purple);
            transition: transform 0.2s ease-in-out;
        }

        .announcement-card:hover {
            transform: translateY(-2px);
            background: var(--hover-bg);
        }

        .announcement-card h6 {
            color: var(--accent-purple);
            margin-bottom: 0.5rem;
            font-weight: 600;
        }

        .announcement-card p {
            color: var(--text-primary) !important;
            margin-bottom: 0;
        }
        
        .announcement-card .announcement-content {
            color: #000000 !important;
            font-size: 0.95rem;
            line-height: 1.5;
            background: rgba(255, 255, 255, 0.9);
            padding: 8px;
            border-radius: 4px;
        }

        .announcement-card .text-muted {
            color: var(--accent-purple) !important;
            opacity: 0.8;
        }

        .announcement-card .bi {
            color: var(--accent-purple);
        }

        /* Badges */
        .badge {
            padding: 0.5rem 0.8rem;
            font-weight: 500;
        }

        .badge.bg-secondary {
            background-color: var(--hover-bg) !important;
            color: var(--text-primary);
        }

        .grade-badge {
            font-size: 0.9rem;
            min-width: 60px;
        }

        /* Buttons */
        .btn-primary {
            background-color: var(--accent-purple);
            border-color: var(--accent-purple);
            padding: 0.5rem 1rem;
            font-weight: 500;
            border-radius: 0.5rem;
        }

        .btn-primary:hover {
            background-color: #7c3aed;
            border-color: #7c3aed;
        }

        /* Keep original colors for status cards */
        .card.bg-success, .card.bg-danger, .card.bg-warning, .card.bg-info {
            border: none;
        }

        .table-responsive {
            border-radius: 1rem;
            background: #242639;
            border: 1px solid var(--card-border);
        }
    </style>
</head>
<body>
    <?php include '../includes/parent_navbar.php'; ?>
    <div class="dashboard-header">
        <div class="container">
            <h2 class="mb-0">
                <i class="bi bi-person-circle me-2"></i>
                Welcome, <?php echo htmlspecialchars($parent['first_name'] . ' ' . $parent['last_name']); ?>!
            </h2>
        </div>
    </div>

    <div class="container">
        <div class="row g-4">
            <!-- Children Details -->
            <div class="col-lg-8">
                <?php while ($child = $children->fetch_assoc()): 
                    // Get student's courses and grades
                    $grades_query = "
                        SELECT c.course_name, c.description, g.grade, g.grading_period, g.created_at
                        FROM courses c
                        JOIN student_courses sc ON c.id = sc.course_id
                        LEFT JOIN grades g ON (c.id = g.course_id AND g.student_id = ?)
                        WHERE sc.student_id = ?
                        ORDER BY c.course_name, g.created_at DESC
                    ";
                    $stmt = $conn->prepare($grades_query);
                    $stmt->bind_param("ii", $child['id'], $child['id']);
                    $stmt->execute();
                    $grades = $stmt->get_result();

                    // Get attendance records
                    $attendance_query = "
                        SELECT *
                        FROM attendance
                        WHERE student_id = ?
                        ORDER BY date DESC
                    ";
                    $stmt = $conn->prepare($attendance_query);
                    $stmt->bind_param("i", $child['id']);
                    $stmt->execute();
                    $attendance = $stmt->get_result();

                    // Calculate attendance statistics
                    $total_days = 0;
                    $present_days = 0;
                    $absent_days = 0;
                    $late_days = 0;

                    while ($record = $attendance->fetch_assoc()) {
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
                    }

                    $attendance_rate = $total_days > 0 ? round(($present_days + $late_days) * 100 / $total_days, 1) : 0;
                ?>
                    <div class="card">
                        <div class="card-header">
                            <div class="student-name">
                                <i class="bi bi-mortarboard-fill text-primary"></i>
                                <h4 class="mb-0">
                                    <?php echo htmlspecialchars($child['first_name'] . ' ' . $child['last_name']); ?>
                                    <span class="badge ms-2" style="background-color: var(--accent-purple);">Year level: <?php echo $child['grade_level']; ?></span>
                                </h4>
                            </div>
                        </div>
                        <div class="card-body">
                            <!-- Attendance Statistics -->
                            <h5 class="section-title">
                                <i class="bi bi-calendar-check text-primary"></i>
                                Attendance Overview
                            </h5>
                            <div class="row g-3 mb-4">
                                <div class="col-md-3">
                                    <div class="card bg-success text-white stat-card">
                                        <h6 class="card-title">Present</h6>
                                        <h3><?php echo $present_days; ?></h3>
                                        <p class="mb-0">days</p>
                                    </div>
                                </div>
                                <div class="col-md-3">
                                    <div class="card bg-danger text-white stat-card">
                                        <h6 class="card-title">Absent</h6>
                                        <h3><?php echo $absent_days; ?></h3>
                                        <p class="mb-0">days</p>
                                    </div>
                                </div>
                                <div class="col-md-3">
                                    <div class="card bg-warning text-dark stat-card">
                                        <h6 class="card-title">Late</h6>
                                        <h3><?php echo $late_days; ?></h3>
                                        <p class="mb-0">days</p>
                                    </div>
                                </div>
                                <div class="col-md-3">
                                    <div class="card bg-info text-white stat-card">
                                        <h6 class="card-title">Attendance Rate</h6>
                                        <h3><?php echo $attendance_rate; ?>%</h3>
                                        <p class="mb-0">overall</p>
                                    </div>
                                </div>
                            </div>

                            <!-- Grades -->
                            <h5 class="section-title">
                                <i class="bi bi-book text-primary"></i>
                                Course Grades
                            </h5>
                            <div class="table-responsive">
                                <table class="table table-hover align-middle">
                                    <thead>
                                        <tr>
                                            <th>Course</th>
                                            <th>Description</th>
                                            <th>Grade</th>
                                            <th>Period</th>
                                            <th>Date</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php while ($grade = $grades->fetch_assoc()): ?>
                                            <tr>
                                                <td class="fw-medium"><?php echo htmlspecialchars($grade['course_name']); ?></td>
                                                <td>
                                                    <small class="text-muted">
                                                        <?php echo htmlspecialchars($grade['description'] ?? ''); ?>
                                                    </small>
                                                </td>
                                                <td>
                                                    <?php if ($grade['grade']): ?>
                                                        <span class="badge grade-badge bg-<?php 
                                                            echo $grade['grade'] >= 90 ? 'success' : 
                                                                ($grade['grade'] >= 80 ? 'primary' : 
                                                                ($grade['grade'] >= 70 ? 'warning' : 'danger')); 
                                                        ?>">
                                                            <?php echo number_format($grade['grade'], 2); ?>%
                                                        </span>
                                                    <?php else: ?>
                                                        <span class="badge grade-badge bg-secondary">No grade</span>
                                                    <?php endif; ?>
                                                </td>
                                                <td><?php echo htmlspecialchars($grade['grading_period'] ?? ''); ?></td>
                                                <td>
                                                    <small>
                                                        <?php 
                                                            echo $grade['created_at'] 
                                                                ? date('M d, Y', strtotime($grade['created_at']))
                                                                : '';
                                                        ?>
                                                    </small>
                                                </td>
                                            </tr>
                                        <?php endwhile; ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                <?php endwhile; ?>
            </div>

            <!-- Recent Announcements -->
            <div class="col-lg-4">
                <div class="card">
                    <div class="card-header">
                        <h5 class="mb-0">
                            <i class="bi bi-megaphone-fill text-primary me-2"></i>
                            Recent Announcements
                        </h5>
                    </div>
                    <div class="card-body">
                        <?php while ($announcement = $announcements->fetch_assoc()): ?>
                            <div class="announcement-card">
                                <h6><?php echo htmlspecialchars($announcement['title']); ?></h6>
                                <p class="small text-muted mb-2">
                                    <i class="bi bi-calendar3 me-1"></i>
                                    <?php echo date('M d, Y', strtotime($announcement['created_at'])); ?>
                                </p>
                                <p class="mb-0 announcement-content">
                                    <?php 
                                        $content = htmlspecialchars($announcement['content']);
                                        echo strlen($content) > 100 ? substr($content, 0, 100) . '...' : $content;
                                    ?>
                                </p>
                            </div>
                        <?php endwhile; ?>
                        <a href="announcements.php" class="btn btn-primary w-100">
                            <i class="bi bi-eye me-1"></i>
                            View All Announcements
                        </a>
                    </div>
                </div>
            </div>

            <!-- Login History -->
            <div class="col-md-6 mb-4">
                <div class="card">
                    <div class="card-header">
                        <h5 class="mb-0"><i class="bi bi-box-arrow-in-right me-2"></i>Login History</h5>
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table">
                                <thead>
                                    <tr>
                                        <th>Date & Time</th>
                                        <th>Duration</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php 
                                    // Get login history with session duration
                                    $login_query = "
                                        WITH RankedEvents AS (
                                            SELECT 
                                                action,
                                                created_at,
                                                ROW_NUMBER() OVER (ORDER BY created_at DESC) as rn
                                            FROM user_activity 
                                            WHERE user_id = ? 
                                            AND action = 'login'
                                        )
                                        SELECT 
                                            r1.created_at as action_time,
                                            r2.created_at as next_action_time,
                                            TIMESTAMPDIFF(MINUTE, r1.created_at, r2.created_at) as duration
                                        FROM RankedEvents r1
                                        LEFT JOIN RankedEvents r2 ON r2.rn = r1.rn + 1
                                        ORDER BY r1.created_at DESC
                                        LIMIT 5";
                                    $stmt = $conn->prepare($login_query);
                                    $stmt->bind_param("i", $_SESSION['user_id']);
                                    $stmt->execute();
                                    $login_history = $stmt->get_result();
                                    
                                    while ($history = $login_history->fetch_assoc()): 
                                        $duration = '';
                                        if ($history['duration'] !== null) {
                                            $hours = floor($history['duration'] / 60);
                                            $minutes = $history['duration'] % 60;
                                            if ($hours > 0) {
                                                $duration = sprintf("%dh %dm", $hours, $minutes);
                                            } else {
                                                $duration = sprintf("%dm", $minutes);
                                            }
                                        }
                                    ?>
                                    <tr>
                                        <td><?php echo date('M d, Y h:i A', strtotime($history['action_time'])); ?></td>
                                        <td>
                                            <?php echo $duration ? $duration : '<span class="text-muted">Active</span>'; ?>
                                        </td>
                                    </tr>
                                    <?php endwhile; ?>
                                    <?php if ($login_history->num_rows === 0): ?>
                                    <tr>
                                        <td colspan="2" class="text-center text-muted">No login history available</td>
                                    </tr>
                                    <?php endif; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Recent Incidents -->
            <div class="col-md-6 mb-4">
                <div class="card">
                    <div class="card-header d-flex justify-content-between align-items-center">
                        <h5 class="mb-0">Recent Incidents</h5>
                        <button class="btn btn-primary btn-sm" data-bs-toggle="modal" data-bs-target="#newIncidentModal">
                            <i class="bi bi-plus-circle me-1"></i>Report Incident
                        </button>
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table">
                                <thead>
                                    <tr>
                                        <th>Type</th>
                                        <th>Severity</th>
                                        <th>Status</th>
                                        <th>Reported</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php while ($incident = $recent_incidents->fetch_assoc()): ?>
                                    <tr>
                                        <td><?php echo htmlspecialchars($incident['type']); ?></td>
                                        <td>
                                            <span class="badge bg-<?php 
                                                echo match($incident['severity']) {
                                                    'low' => 'success',
                                                    'medium' => 'warning',
                                                    'high' => 'danger',
                                                    'critical' => 'dark',
                                                    default => 'secondary'
                                                };
                                            ?>">
                                                <?php echo ucfirst($incident['severity']); ?>
                                            </span>
                                        </td>
                                        <td>
                                            <span class="badge bg-<?php 
                                                echo match($incident['status']) {
                                                    'open' => 'warning',
                                                    'investigating' => 'info',
                                                    'resolved' => 'success',
                                                    default => 'secondary'
                                                };
                                            ?>">
                                                <?php echo ucfirst($incident['status']); ?>
                                            </span>
                                        </td>
                                        <td><?php echo date('M d, Y', strtotime($incident['created_at'])); ?></td>
                                    </tr>
                                    <?php endwhile; ?>
                                    <?php if ($recent_incidents->num_rows === 0): ?>
                                    <tr>
                                        <td colspan="4" class="text-center text-muted">No incidents reported yet</td>
                                    </tr>
                                    <?php endif; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- New Incident Modal -->
    <div class="modal fade" id="newIncidentModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title text-black">Report New Incident</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <form action="report_incident.php" method="POST">
                    <div class="modal-body">
                        <div class="mb-3">
                            <label for="type" class="form-label text-black">Incident Type</label>
                            <select class="form-select text-black" id="type" name="type" required>
                                <option value="">Select Type</option>
                                <option value="technical">Technical Issue</option>
                                <option value="security">Security Concern</option>
                                <option value="behavior">Behavioral Issue</option>
                                <option value="other">Other</option>
                            </select>
                        </div>
                        <div class="mb-3">
                            <label for="severity" class="form-label text-black">Severity</label>
                            <select class="form-select text-black" id="severity" name="severity" required>
                                <option value="">Select Severity</option>
                                <option value="low">Low</option>
                                <option value="medium">Medium</option>
                                <option value="high">High</option>
                                <option value="critical">Critical</option>
                            </select>
                        </div>
                        <div class="mb-3">
                            <label for="description" class="form-label text-black">Description</label>
                            <textarea class="form-control text-black" id="description" name="description" rows="3" required></textarea>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-primary">Submit Incident</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html> 