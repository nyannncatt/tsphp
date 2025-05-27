<?php
require_once '../includes/auth_middleware.php';
checkAuth();
checkRole(['admin']);
require_once '../config/database.php';

// Get counts for dashboard
$student_count = $conn->query("SELECT COUNT(*) as count FROM students")->fetch_assoc()['count'];
$parent_count = $conn->query("SELECT COUNT(*) as count FROM parents")->fetch_assoc()['count'];
$course_count = $conn->query("SELECT COUNT(*) as count FROM courses")->fetch_assoc()['count'];

// Get incident statistics
$stats_query = "SELECT 
    COUNT(*) as total,
    SUM(CASE WHEN status = 'open' THEN 1 ELSE 0 END) as open_count,
    SUM(CASE WHEN status = 'investigating' THEN 1 ELSE 0 END) as investigating_count,
    SUM(CASE WHEN status = 'resolved' THEN 1 ELSE 0 END) as resolved_count
FROM incidents";
$stats = $conn->query($stats_query)->fetch_assoc();

// Get recent incidents
$incidents_query = "SELECT i.*, u.email, 
                   COALESCE(s.first_name, p.first_name) as first_name,
                   COALESCE(s.last_name, p.last_name) as last_name
                   FROM incidents i 
                   JOIN users u ON i.user_id = u.id 
                   LEFT JOIN students s ON u.id = s.user_id
                   LEFT JOIN parents p ON u.id = p.user_id
                   ORDER BY i.created_at DESC 
                   LIMIT 5";
$recent_incidents = $conn->query($incidents_query);

// Get recent system logs
$logs_query = "SELECT l.*, u.email,
               COALESCE(s.first_name, p.first_name) as first_name,
               COALESCE(s.last_name, p.last_name) as last_name
               FROM system_logs l 
               JOIN users u ON l.user_id = u.id 
               LEFT JOIN students s ON u.id = s.user_id
               LEFT JOIN parents p ON u.id = p.user_id
               ORDER BY l.created_at DESC 
               LIMIT 10";
$recent_logs = $conn->query($logs_query);

// Get user activity logs
$activity_query = "SELECT a.*, u.email, u.role,
                  COALESCE(s.first_name, p.first_name) as first_name,
                  COALESCE(s.last_name, p.last_name) as last_name
                  FROM user_activity a
                  JOIN users u ON a.user_id = u.id
                  LEFT JOIN students s ON u.id = s.user_id
                  LEFT JOIN parents p ON u.id = p.user_id
                  WHERE u.role IN ('student', 'parent')
                  ORDER BY a.created_at DESC
                  LIMIT 10";
$user_activities = $conn->query($activity_query);

// Get system metrics for the last 7 days
$metrics_query = "
    SELECT 
        DATE(created_at) as date,
        COUNT(CASE WHEN action = 'login' THEN 1 END) as login_count,
        COUNT(CASE WHEN action = 'restricted_access' THEN 1 END) as restricted_count
    FROM user_activity 
    WHERE created_at >= DATE_SUB(CURRENT_DATE, INTERVAL 7 DAY)
    GROUP BY DATE(created_at)
    ORDER BY date";
$metrics_result = $conn->query($metrics_query);

// Get incident statistics
$incident_query = "
    SELECT 
        type,
        COUNT(*) as count
    FROM incidents 
    GROUP BY type";
$incident_result = $conn->query($incident_query);

// Get user activity by hour
$activity_query = "
    SELECT 
        HOUR(created_at) as hour,
        COUNT(DISTINCT user_id) as active_users
    FROM user_activity 
    WHERE created_at >= DATE_SUB(CURRENT_DATE, INTERVAL 7 DAY)
    GROUP BY HOUR(created_at)
    ORDER BY hour";
$activity_result = $conn->query($activity_query);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard - SchoolComSphere</title>
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

        .stat-card {
            background: var(--card-bg);
            border: 1px solid var(--card-border);
            border-radius: 0.75rem;
            padding: 1.5rem;
            text-align: center;
        }

        .stat-number {
            font-size: 2rem;
            font-weight: 600;
            color: var(--accent-purple);
        }

        .stat-label {
            color: var(--text-secondary);
            font-size: 0.875rem;
        }

        .table {
            color: var(--text-primary);
        }

        .table thead th {
            background: var(--card-bg);
            color: var(--header-text);
            border-bottom: 1px solid var(--card-border);
        }

        .table tbody td {
            border-color: var(--card-border);
        }

        .badge {
            padding: 0.5em 0.75em;
        }

        .modal-content {
            background: var(--card-bg);
            border: 1px solid var(--card-border);
            color: var(--text-primary);
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
        }

        .form-label {
            color: var(--text-secondary);
        }
    </style>
</head>
<body>
    <?php include '../includes/admin_navbar.php'; ?>

    <div class="container mt-4">
        <div class="row mb-4">
            <div class="col-md-12">
                <h2><i class="bi bi-speedometer2 me-2"></i>Dashboard Overview</h2>
            </div>
        </div>

        <!-- System Statistics -->
        <div class="row mb-4">
            <div class="col-md-3">
                <div class="stat-card">
                    <div class="stat-number"><?php echo $student_count; ?></div>
                    <div class="stat-label">Total Students</div>
                </div>
            </div>
            <div class="col-md-3">
            <div class="stat-card">
                    <div class="stat-number"><?php echo $parent_count; ?></div>
                    <div class="stat-label">Total Parents</div>
                </div>
            </div>
            <div class="col-md-3">
            <div class="stat-card">
                    <div class="stat-number"><?php echo $course_count; ?></div>
                    <div class="stat-label">Total Courses</div>
                </div>
            </div>
            <div class="col-md-3">
            <div class="stat-card">
                    <div class="stat-number"><?php echo $stats['total']; ?></div>
                    <div class="stat-label">Total Incidents</div>
                </div>
            </div>
        </div>

        <!-- Incident Management Section -->
        <div class="row mb-4">
            <div class="col-md-12">
                <div class="card">
            <div class="card-header d-flex justify-content-between align-items-center">
                        <h5 class="mb-0">Incident Management</h5>
                        <button class="btn btn-primary btn-sm" data-bs-toggle="modal" data-bs-target="#newIncidentModal">
                            <i class="bi bi-plus-circle me-1"></i>Report New Incident
                        </button>
                    </div>
                    <div class="card-body">
                        <div class="row mb-4">
                            <div class="col-md-4">
                                <div class="stat-card">
                                    <div class="stat-number text-warning"><?php echo $stats['open_count']; ?></div>
                                    <div class="stat-label">Open Incidents</div>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="stat-card">
                                    <div class="stat-number text-info"><?php echo $stats['investigating_count']; ?></div>
                                    <div class="stat-label">Under Investigation</div>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="stat-card">
                                    <div class="stat-number text-success"><?php echo $stats['resolved_count']; ?></div>
                                    <div class="stat-label">Resolved</div>
                                </div>
                            </div>
                        </div>
                        <div class="table-responsive">
                            <table class="table">
                                <thead>
                                    <tr>
                                        <th>Type</th>
                                        <th>Severity</th>
                                        <th>Status</th>
                                        <th>Reported By</th>
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
                                            <form method="POST" action="update_incident.php" class="d-inline incident-status-form">
                                                <input type="hidden" name="incident_id" value="<?php echo $incident['id']; ?>">
                                                <select name="status" class="form-select form-select-sm d-inline-block w-auto incident-status-select" onchange="updateIncidentStatus(this)">
                                                    <option value="open" <?php echo $incident['status'] === 'open' ? 'selected' : ''; ?>>Open</option>
                                                    <option value="investigating" <?php echo $incident['status'] === 'investigating' ? 'selected' : ''; ?>>Investigating</option>
                                                    <option value="resolved" <?php echo $incident['status'] === 'resolved' ? 'selected' : ''; ?>>Resolved</option>
                                                </select>
                                            </form>
                                        </td>
                                        <td><?php echo htmlspecialchars($incident['first_name'] . ' ' . $incident['last_name']); ?></td>
                                    </tr>
                                    <?php endwhile; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Move Incident Status Overview to the bottom -->
        <div class="row mb-4">
            <div class="col-md-12">
                <div class="card">
                    <div class="card-header">
                        <h5 class="mb-0"><i class="bi bi-pie-chart me-2"></i>Incident Status Overview</h5>
                    </div>
                    <div class="card-body">
                        <div class="row">
                            <div class="col-md-8 mx-auto">
                                <canvas id="incidentStatusChart"></canvas>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- User Management Section -->
        <div class="row">
            <!-- User Activity Monitoring -->
            <div class="col-md-6 mb-4">
                <div class="card">
                    <div class="card-header">
                        <h5 class="mb-0"><i class="bi bi-activity me-2"></i>User Activity Monitoring</h5>
                    </div>
                    <div class="card-body">
                        <div class="table-responsive" style="height: 300px; overflow-y: auto;">
                            <table class="table table-hover">
                                <thead>
                                    <tr>
                                        <th style="width: 25%">User</th>
                                        <th style="width: 45%">Navigation</th>
                                        <th style="width: 30%">Time</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php 
                                    $activity_query = "
                                        SELECT ua.*, u.username, u.role,
                                        COALESCE(s.first_name, p.first_name) as first_name,
                                        COALESCE(s.last_name, p.last_name) as last_name
                                        FROM user_activity ua
                                        JOIN users u ON ua.user_id = u.id
                                        LEFT JOIN students s ON u.id = s.user_id
                                        LEFT JOIN parents p ON u.id = p.user_id
                                        WHERE u.role IN ('student', 'parent')
                                        AND (ua.action = 'page_view' OR ua.action = 'login' OR ua.action = 'logout')
                                        ORDER BY ua.created_at DESC
                                        LIMIT 10";
                                    $activity_result = $conn->query($activity_query);
                                    while ($activity = $activity_result->fetch_assoc()): 
                                    ?>
                                    <tr>
                                        <td>
                                            <div class="d-flex align-items-center">
                                                <span class="badge bg-<?php echo $activity['role'] == 'student' ? 'primary' : 'success'; ?> me-2">
                                                    <?php echo ucfirst($activity['role']); ?>
                                                </span>
                                                <span class="text-truncate" style="max-width: 120px;" title="<?php echo htmlspecialchars($activity['first_name'] . ' ' . $activity['last_name']); ?>">
                                                    <?php echo htmlspecialchars($activity['first_name'] . ' ' . $activity['last_name']); ?>
                                                </span>
                                            </div>
                                        </td>
                                        <td>
                                            <?php if ($activity['action'] == 'page_view'): ?>
                                                <div class="d-flex align-items-center">
                                                    <i class="bi bi-arrow-right-circle me-2 text-info"></i>
                                                    <span class="text-truncate" style="max-width: 200px;" title="<?php echo htmlspecialchars($activity['page']); ?>">
                                                        <?php echo htmlspecialchars($activity['page']); ?>
                                                    </span>
                                                </div>
                                            <?php else: ?>
                                                <span class="badge bg-<?php 
                                                    echo $activity['action'] == 'login' ? 'success' : 'warning'; 
                                                ?>">
                                                    <i class="bi bi-<?php echo $activity['action'] == 'login' ? 'box-arrow-in-right' : 'box-arrow-right'; ?> me-1"></i>
                                                    <?php echo ucfirst($activity['action']); ?>
                                                </span>
                                            <?php endif; ?>
                                        </td>
                                        <td class="text-nowrap">
                                            <?php echo date('M d, H:i', strtotime($activity['created_at'])); ?>
                                        </td>
                                    </tr>
                                    <?php endwhile; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>

            <!-- System Activity Log -->
            <div class="col-md-6 mb-4">
                <div class="card">
                    <div class="card-header">
                        <h5 class="mb-0"><i class="bi bi-gear me-2"></i>System Activity Log</h5>
                    </div>
                    <div class="card-body">
                        <div class="table-responsive" style="height: 300px; overflow-y: auto;">
                            <table class="table table-hover">
                                <thead>
                                    <tr>
                                        <th style="width: 50%">Action</th>
                                        <th style="width: 50%">Time</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php 
                                    $system_query = "
                                        SELECT * FROM system_logs 
                                        ORDER BY created_at DESC 
                                        LIMIT 10";
                                    $system_result = $conn->query($system_query);
                                    while ($log = $system_result->fetch_assoc()): 
                                    ?>
                                    <tr>
                                        <td>
                                            <span class="badge bg-<?php 
                                                echo $log['action'] == 'login' ? 'success' : 
                                                    ($log['action'] == 'logout' ? 'warning' : 
                                                    ($log['action'] == 'restricted_access' ? 'danger' : 'info')); 
                                            ?>">
                                                <?php echo htmlspecialchars($log['action']); ?>
                                            </span>
                                        </td>
                                        <td class="text-nowrap">
                                            <?php echo date('M d, H:i', strtotime($log['created_at'])); ?>
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

        <!-- User Activity Logs Card -->
        <div class="col-md-12 mb-4">
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h5 class="mb-0"><i class="bi bi-shield-lock me-2"></i>Navigation Restrictions</h5>
                    <button class="btn btn-primary btn-sm" data-bs-toggle="modal" data-bs-target="#restrictNavModal">
                        <i class="bi bi-plus-circle me-1"></i>Add Restriction
                    </button>
                </div>
                <div class="card-body">
                    <div class="row">
                        <!-- Student Restrictions -->
                        <div class="col-md-6 mb-4">
                            <div class="card">
                                <div class="card-header">
                                    <h6 class="mb-0"><i class="bi bi-mortarboard me-2"></i>Student Restrictions</h6>
                                </div>
                                <div class="card-body">
                                    <div class="table-responsive">
                                        <table class="table">
                                            <thead>
                                                <tr>
                                                    <th>Student</th>
                                                    <th>Restricted Pages</th>
                                                    <th>Actions</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                <?php
                                                $student_restrictions_query = "SELECT nr.*, u.username, 
                                                                             s.first_name, s.last_name
                                                                             FROM nav_restrictions nr
                                                                             JOIN users u ON nr.user_id = u.id
                                                                             JOIN students s ON u.id = s.user_id
                                                                             WHERE u.role = 'student'
                                                                             ORDER BY s.first_name, s.last_name";
                                                $student_restrictions = $conn->query($student_restrictions_query);
                                                while ($restriction = $student_restrictions->fetch_assoc()):
                                                ?>
                                                <tr>
                                                    <td>
                                                        <div class="d-flex align-items-center">
                                                            <div class="avatar-circle bg-primary bg-opacity-10 text-primary me-2">
                                                                <i class="bi bi-person"></i>
                                                            </div>
                                                            <div>
                                                                <div class="fw-bold"><?php echo htmlspecialchars($restriction['first_name'] . ' ' . $restriction['last_name']); ?></div>
                                                                <small class="text-muted"><?php echo htmlspecialchars($restriction['username']); ?></small>
                                                            </div>
                                                        </div>
                                                    </td>
                                                    <td>
                                                        <?php
                                                        $restricted_pages = explode(',', $restriction['restricted_pages']);
                                                        foreach ($restricted_pages as $page):
                                                        ?>
                                                        <span class="badge bg-danger me-1"><?php echo htmlspecialchars($page); ?></span>
                                                        <?php endforeach; ?>
                                                    </td>
                                                    <td>
                                                        <button class="btn btn-sm btn-warning" 
                                                                data-bs-toggle="modal" 
                                                                data-bs-target="#editRestrictionModal"
                                                                data-user-id="<?php echo $restriction['user_id']; ?>"
                                                                data-user-name="<?php echo htmlspecialchars($restriction['first_name'] . ' ' . $restriction['last_name']); ?>"
                                                                data-restricted-pages="<?php echo htmlspecialchars($restriction['restricted_pages']); ?>">
                                                            <i class="bi bi-pencil"></i>
                                                        </button>
                                                        <button class="btn btn-sm btn-danger" 
                                                                data-bs-toggle="modal" 
                                                                data-bs-target="#removeRestrictionModal"
                                                                data-user-id="<?php echo $restriction['user_id']; ?>"
                                                                data-user-name="<?php echo htmlspecialchars($restriction['first_name'] . ' ' . $restriction['last_name']); ?>">
                                                            <i class="bi bi-trash"></i>
                                                        </button>
                                                    </td>
                                                </tr>
                                                <?php endwhile; ?>
                                            </tbody>
                                        </table>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Parent Restrictions -->
                        <div class="col-md-6 mb-4">
                            <div class="card">
                                <div class="card-header">
                                    <h6 class="mb-0"><i class="bi bi-people me-2"></i>Parent Restrictions</h6>
                                </div>
                                <div class="card-body">
                                    <div class="table-responsive">
                                        <table class="table">
                                            <thead>
                                                <tr>
                                                    <th>Parent</th>
                                                    <th>Restricted Pages</th>
                                                    <th>Actions</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                <?php
                                                $parent_restrictions_query = "SELECT nr.*, u.username, 
                                                                           p.first_name, p.last_name
                                                                           FROM nav_restrictions nr
                                                                           JOIN users u ON nr.user_id = u.id
                                                                           JOIN parents p ON u.id = p.user_id
                                                                           WHERE u.role = 'parent'
                                                                           ORDER BY p.first_name, p.last_name";
                                                $parent_restrictions = $conn->query($parent_restrictions_query);
                                                while ($restriction = $parent_restrictions->fetch_assoc()):
                                                ?>
                                                <tr>
                                                    <td>
                                                        <div class="d-flex align-items-center">
                                                            <div class="avatar-circle bg-success bg-opacity-10 text-success me-2">
                                                                <i class="bi bi-person"></i>
                                                            </div>
                                                            <div>
                                                                <div class="fw-bold"><?php echo htmlspecialchars($restriction['first_name'] . ' ' . $restriction['last_name']); ?></div>
                                                                <small class="text-muted"><?php echo htmlspecialchars($restriction['username']); ?></small>
                                                            </div>
                                                        </div>
                                                    </td>
                                                    <td>
                <?php
                                                        $restricted_pages = explode(',', $restriction['restricted_pages']);
                                                        foreach ($restricted_pages as $page):
                                                        ?>
                                                        <span class="badge bg-danger me-1"><?php echo htmlspecialchars($page); ?></span>
                                                        <?php endforeach; ?>
                                                    </td>
                                                    <td>
                                                        <button class="btn btn-sm btn-warning" 
                                                                data-bs-toggle="modal" 
                                                                data-bs-target="#editRestrictionModal"
                                                                data-user-id="<?php echo $restriction['user_id']; ?>"
                                                                data-user-name="<?php echo htmlspecialchars($restriction['first_name'] . ' ' . $restriction['last_name']); ?>"
                                                                data-restricted-pages="<?php echo htmlspecialchars($restriction['restricted_pages']); ?>">
                                                            <i class="bi bi-pencil"></i>
                                                        </button>
                                                        <button class="btn btn-sm btn-danger" 
                                                                data-bs-toggle="modal" 
                                                                data-bs-target="#removeRestrictionModal"
                                                                data-user-id="<?php echo $restriction['user_id']; ?>"
                                                                data-user-name="<?php echo htmlspecialchars($restriction['first_name'] . ' ' . $restriction['last_name']); ?>">
                                                            <i class="bi bi-trash"></i>
                                                        </button>
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
            </div>
        </div>

        <!-- System Analytics Section -->
        <div class="row mb-4">
            <div class="col-12">
                <div class="card">
                    <div class="card-header">
                        <h5 class="mb-0"><i class="bi bi-graph-up me-2"></i>System Analytics</h5>
                    </div>
                    <div class="card-body">
                        <div class="row">
                            <!-- Daily Login Statistics -->
                            <div class="col-md-6 mb-4">
                                <div class="card">
                                    <div class="card-header">
                                        <h6 class="mb-0">Daily Login Statistics</h6>
                                    </div>
                                    <div class="card-body">
                                        <canvas id="loginChart"></canvas>
                                    </div>
                                </div>
                            </div>

                            <!-- Incident Reports Distribution -->
                            <div class="col-md-6 mb-4">
                                <div class="card">
                                    <div class="card-header">
                                        <h6 class="mb-0">Incident Reports Distribution</h6>
                                    </div>
                                    <div class="card-body">
                                        <canvas id="incidentChart"></canvas>
                                    </div>
                                </div>
                            </div>

                            <!-- Restricted Access Attempts -->
                            <div class="col-md-6 mb-4">
                                <div class="card">
                                    <div class="card-header">
                                        <h6 class="mb-0">Restricted Access Attempts</h6>
                                    </div>
                                    <div class="card-body">
                                        <canvas id="restrictedChart"></canvas>
                                    </div>
                                </div>
                            </div>

                            <!-- User Activity by Time -->
                            <div class="col-md-6 mb-4">
                                <div class="card">
                                    <div class="card-header">
                                        <h6 class="mb-0">User Activity by Time</h6>
                                    </div>
                                    <div class="card-body">
                                        <canvas id="activityChart"></canvas>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Add Restriction Modal -->
        <div class="modal fade" id="restrictNavModal" tabindex="-1">
            <div class="modal-dialog">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title text-black">Add Navigation Restriction</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>
                    <form action="add_nav_restriction.php" method="POST">
                        <div class="modal-body">
                            <div class="mb-3">
                                <label for="user_id" class="form-label text-black">Select User</label>
                                <select class="form-select text-black" id="user_id" name="user_id" required>
                                    <option value="">Select User</option>
                                    <?php
                                    $users_query = "SELECT u.id, u.email, u.role,
                                                  COALESCE(s.first_name, p.first_name) as first_name,
                                                  COALESCE(s.last_name, p.last_name) as last_name
                                                  FROM users u
                                                  LEFT JOIN students s ON u.id = s.user_id
                                                  LEFT JOIN parents p ON u.id = p.user_id
                                                  WHERE u.role IN ('student', 'parent')";
                                    $users = $conn->query($users_query);
                                    while ($user = $users->fetch_assoc()):
                                    ?>
                                    <option value="<?php echo $user['id']; ?>">
                                        <?php echo htmlspecialchars($user['first_name'] . ' ' . $user['last_name'] . ' (' . $user['role'] . ')'); ?>
                                    </option>
                <?php endwhile; ?>
                                </select>
                            </div>
                            <div class="mb-3">
                                <label class="form-label text-black">Restrict Pages</label>
                                <div class="row">
                                    <div class="col-md-6">
                                        <div class="form-check">
                                            <input class="form-check-input" type="checkbox" name="restricted_pages[]" value="dashboard" id="nav_dashboard">
                                            <label class="form-check-label text-black" for="nav_dashboard">Dashboard</label>
                                        </div>
                                        <div class="form-check">
                                            <input class="form-check-input" type="checkbox" name="restricted_pages[]" value="courses" id="nav_courses">
                                            <label class="form-check-label text-black" for="nav_courses">Courses</label>
                                        </div>
                                        <div class="form-check">
                                            <input class="form-check-input" type="checkbox" name="restricted_pages[]" value="grades" id="nav_grades">
                                            <label class="form-check-label text-black" for="nav_grades">Grades</label>
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="form-check">
                                            <input class="form-check-input" type="checkbox" name="restricted_pages[]" value="attendance" id="nav_attendance">
                                            <label class="form-check-label text-black" for="nav_attendance">Attendance</label>
                                        </div>
                                        <div class="form-check">
                                            <input class="form-check-input" type="checkbox" name="restricted_pages[]" value="profile" id="nav_profile">
                                            <label class="form-check-label text-black" for="nav_profile">Profile</label>
                                        </div>
                                        <div class="form-check">
                                            <input class="form-check-input" type="checkbox" name="restricted_pages[]" value="messages" id="nav_messages">
                                            <label class="form-check-label text-black" for="nav_messages">Messages</label>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                            <button type="submit" class="btn btn-primary">Add Restriction</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        <!-- Edit Restriction Modal -->
        <div class="modal fade" id="editRestrictionModal" tabindex="-1">
            <div class="modal-dialog">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title text-black">Edit Navigation Restriction</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>
                    <form action="edit_nav_restriction.php" method="POST">
                        <div class="modal-body">
                            <input type="hidden" name="user_id" id="edit_user_id">
                            <p class="text-black">Editing restrictions for: <strong id="edit_user_name"></strong></p>
                            <div class="mb-3">
                                <label class="form-label text-black">Restrict Pages</label>
                                <div class="row">
                                    <div class="col-md-6">
                                        <div class="form-check">
                                            <input class="form-check-input" type="checkbox" name="restricted_pages[]" value="dashboard" id="edit_nav_dashboard">
                                            <label class="form-check-label text-black" for="edit_nav_dashboard">Dashboard</label>
                                        </div>
                                        <div class="form-check">
                                            <input class="form-check-input" type="checkbox" name="restricted_pages[]" value="courses" id="edit_nav_courses">
                                            <label class="form-check-label text-black" for="edit_nav_courses">Courses</label>
                                        </div>
                                        <div class="form-check">
                                            <input class="form-check-input" type="checkbox" name="restricted_pages[]" value="grades" id="edit_nav_grades">
                                            <label class="form-check-label text-black" for="edit_nav_grades">Grades</label>
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="form-check">
                                            <input class="form-check-input" type="checkbox" name="restricted_pages[]" value="attendance" id="edit_nav_attendance">
                                            <label class="form-check-label text-black" for="edit_nav_attendance">Attendance</label>
                                        </div>
                                        <div class="form-check">
                                            <input class="form-check-input" type="checkbox" name="restricted_pages[]" value="profile" id="edit_nav_profile">
                                            <label class="form-check-label text-black" for="edit_nav_profile">Profile</label>
                                        </div>
                                        <div class="form-check">
                                            <input class="form-check-input" type="checkbox" name="restricted_pages[]" value="messages" id="edit_nav_messages">
                                            <label class="form-check-label text-black" for="edit_nav_messages">Messages</label>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                            <button type="submit" class="btn btn-primary">Save Changes</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        <!-- Remove Restriction Modal -->
        <div class="modal fade" id="removeRestrictionModal" tabindex="-1">
            <div class="modal-dialog">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title text-black">Remove Restriction</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>
                    <form action="remove_nav_restriction.php" method="POST">
                        <div class="modal-body">
                            <input type="hidden" name="user_id" id="remove_user_id">
                            <p class="text-black">Are you sure you want to remove all navigation restrictions for <strong id="remove_user_name"></strong>?</p>
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                            <button type="submit" class="btn btn-danger">Remove Restrictions</button>
                        </div>
                    </form>
            </div>
        </div>
    </div>

    <script>
            // Handle edit restriction modal
            document.addEventListener('DOMContentLoaded', function() {
                const editModal = document.getElementById('editRestrictionModal');
                if (editModal) {
                    editModal.addEventListener('show.bs.modal', function(event) {
                        const button = event.relatedTarget;
                        const userId = button.getAttribute('data-user-id');
                        const userName = button.getAttribute('data-user-name');
                        const restrictedPages = button.getAttribute('data-restricted-pages').split(',');
                        
                        document.getElementById('edit_user_id').value = userId;
                        document.getElementById('edit_user_name').textContent = userName;
                        
                        // Reset all checkboxes
                        document.querySelectorAll('#editRestrictionModal input[type="checkbox"]').forEach(checkbox => {
                            checkbox.checked = false;
                        });
                        
                        // Check the restricted pages
                        restrictedPages.forEach(page => {
                            const checkbox = document.getElementById('edit_nav_' + page);
                            if (checkbox) checkbox.checked = true;
                        });
                    });
                }

                // Handle remove restriction modal
                const removeModal = document.getElementById('removeRestrictionModal');
                if (removeModal) {
                    removeModal.addEventListener('show.bs.modal', function(event) {
                        const button = event.relatedTarget;
                        const userId = button.getAttribute('data-user-id');
                        const userName = button.getAttribute('data-user-name');
                        
                        document.getElementById('remove_user_id').value = userId;
                        document.getElementById('remove_user_name').textContent = userName;
                    });
                }
        });
    </script>
    </div>

    <!-- Block User Modal -->
    <div class="modal fade" id="blockUserModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title text-black">Block User</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <form action="block_user.php" method="POST">
                    <div class="modal-body">
                        <div class="mb-3">
                            <label for="user_id" class="form-label text-black">Select User</label>
                            <select class="form-select text-black" id="user_id" name="user_id" required>
                                <option value="">Select User</option>
                                <?php
                                $users_query = "SELECT u.id, u.email, u.role,
                                              COALESCE(s.first_name, p.first_name) as first_name,
                                              COALESCE(s.last_name, p.last_name) as last_name
                                              FROM users u
                                              LEFT JOIN students s ON u.id = s.user_id
                                              LEFT JOIN parents p ON u.id = p.user_id
                                              WHERE u.role != 'admin'
                                              AND u.id NOT IN (SELECT user_id FROM user_blocks WHERE block_until > CURRENT_TIMESTAMP)
                                              ORDER BY u.role, first_name, last_name";
                                $users = $conn->query($users_query);
                                while ($user = $users->fetch_assoc()):
                                ?>
                                <option value="<?php echo $user['id']; ?>">
                                    <?php echo htmlspecialchars($user['first_name'] . ' ' . $user['last_name'] . ' (' . $user['role'] . ')'); ?>
                                </option>
                                <?php endwhile; ?>
                            </select>
                        </div>
                        <div class="mb-3">
                            <label for="block_until" class="form-label text-black">Block Until</label>
                            <div class="row g-2">
                                <div class="col-md-6">
                                    <input type="date" class="form-control text-black" id="block_date" name="block_date" required>
                                </div>
                                <div class="col-md-3">
                                    <select class="form-select text-black" id="block_hour" name="block_hour" required>
                                        <?php for($i = 0; $i < 24; $i++): ?>
                                            <option value="<?php echo str_pad($i, 2, '0', STR_PAD_LEFT); ?>">
                                                <?php echo str_pad($i, 2, '0', STR_PAD_LEFT); ?>
                                            </option>
                                        <?php endfor; ?>
                                    </select>
                                </div>
                                <div class="col-md-3">
                                    <select class="form-select text-black" id="block_minute" name="block_minute" required>
                                        <?php for($i = 0; $i < 60; $i += 5): ?>
                                            <option value="<?php echo str_pad($i, 2, '0', STR_PAD_LEFT); ?>">
                                                <?php echo str_pad($i, 2, '0', STR_PAD_LEFT); ?>
                                            </option>
                                        <?php endfor; ?>
                                    </select>
                                </div>
                            </div>
                        </div>
                        <div class="mb-3">
                            <label for="reason" class="form-label text-black">Reason</label>
                            <textarea class="form-control text-black" id="reason" name="reason" rows="3" required></textarea>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-danger">Block User</button>
                    </div>
                </form>
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
                <form action="add_incident.php" method="POST">
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

    <!-- Add Chart.js -->
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

    <!-- Initialize Charts -->
    <script>
    document.addEventListener('DOMContentLoaded', function() {
        // Fetch data for charts
        fetch('get_system_metrics.php')
            .then(response => response.json())
            .then(data => {
                // Login Statistics Chart
                new Chart(document.getElementById('loginChart'), {
                    type: 'bar',
                    data: {
                        labels: data.login_stats.labels,
                        datasets: [
                            {
                                label: 'Student Logins',
                                data: data.login_stats.student_values,
                                backgroundColor: '#8b5cf6',
                                borderColor: '#8b5cf6',
                                borderWidth: 1
                            },
                            {
                                label: 'Parent Logins',
                                data: data.login_stats.parent_values,
                                backgroundColor: '#10b981',
                                borderColor: '#10b981',
                                borderWidth: 1
                            }
                        ]
                    },
                    options: {
                        responsive: true,
                        plugins: {
                            legend: {
                                position: 'top'
                            }
                        },
                        scales: {
                            y: {
                                beginAtZero: true,
                                ticks: {
                                    stepSize: 1
                                }
                            }
                        }
                    }
                });

                // Incident Reports Chart
                new Chart(document.getElementById('incidentChart'), {
                    type: 'bar',
                    data: {
                        labels: data.incident_stats.labels,
                        datasets: [
                            {
                                label: 'Student Incidents',
                                data: data.incident_stats.student_values,
                                backgroundColor: '#8b5cf6'
                            },
                            {
                                label: 'Parent Incidents',
                                data: data.incident_stats.parent_values,
                                backgroundColor: '#10b981'
                            }
                        ]
                    },
                    options: {
                        responsive: true,
                        plugins: {
                            legend: {
                                position: 'top'
                            }
                        },
                        scales: {
                            y: {
                                beginAtZero: true
                            }
                        }
                    }
                });

                // Restricted Access Chart
                new Chart(document.getElementById('restrictedChart'), {
                    type: 'bar',
                    data: {
                        labels: data.restricted_stats.labels,
                        datasets: [
                            {
                                label: 'Student Attempts',
                                data: data.restricted_stats.student_values,
                                backgroundColor: '#8b5cf6'
                            },
                            {
                                label: 'Parent Attempts',
                                data: data.restricted_stats.parent_values,
                                backgroundColor: '#10b981'
                            }
                        ]
                    },
                    options: {
                        responsive: true,
                        plugins: {
                            legend: {
                                position: 'top'
                            }
                        },
                        scales: {
                            y: {
                                beginAtZero: true
                            }
                        }
                    }
                });

                // User Activity Chart
                new Chart(document.getElementById('activityChart'), {
                    type: 'bar',
                    data: {
                        labels: data.activity_stats.labels,
                        datasets: [
                            {
                                label: 'Active Students',
                                data: data.activity_stats.student_values,
                                backgroundColor: '#8b5cf6',
                                borderColor: '#8b5cf6',
                                borderWidth: 1
                            },
                            {
                                label: 'Active Parents',
                                data: data.activity_stats.parent_values,
                                backgroundColor: '#10b981',
                                borderColor: '#10b981',
                                borderWidth: 1
                            }
                        ]
                    },
                    options: {
                        responsive: true,
                        plugins: {
                            legend: {
                                position: 'top'
                            },
                            title: {
                                display: true,
                                text: 'Active Users by Hour'
                            }
                        },
                        scales: {
                            y: {
                                beginAtZero: true,
                                ticks: {
                                    stepSize: 1
                                },
                                title: {
                                    display: true,
                                    text: 'Number of Active Users'
                                }
                            },
                            x: {
                                title: {
                                    display: true,
                                    text: 'Hour of Day'
                                }
                            }
                        }
                    }
                });

                // Incident Status Chart
                new Chart(document.getElementById('incidentStatusChart'), {
                    type: 'doughnut',
                    data: {
                        labels: ['Open', 'Investigating', 'Resolved'],
                        datasets: [{
                            data: [
                                <?php echo $stats['open_count']; ?>,
                                <?php echo $stats['investigating_count']; ?>,
                                <?php echo $stats['resolved_count']; ?>
                            ],
                            backgroundColor: [
                                '#f59e0b', // warning color for open
                                '#3b82f6', // info color for investigating
                                '#10b981'  // success color for resolved
                            ],
                            borderWidth: 1
                        }]
                    },
                    options: {
                        responsive: true,
                        maintainAspectRatio: false,
                        plugins: {
                            legend: {
                                position: 'bottom',
                                labels: {
                                    padding: 20,
                                    font: {
                                        size: 14
                                    }
                                }
                            },
                            title: {
                                display: true,
                                text: 'Incident Status Distribution',
                                font: {
                                    size: 16,
                                    weight: 'bold'
                                },
                                padding: {
                                    bottom: 20
                                }
                            }
                        }
                    }
                });
            });
        });
    </script>

    <script>
    // Function to update incident status and refresh charts
    function updateIncidentStatus(selectElement) {
        const form = selectElement.closest('form');
        const formData = new FormData(form);

        fetch('update_incident.php', {
            method: 'POST',
            body: formData
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                // Show a success message
                alert('Status updated successfully');
                // Refresh the page
                window.location.reload();
            } else {
                alert('Failed to update status. Please try again.');
            }
        })
        .catch(error => {
            console.error('Error:', error);
            alert('An error occurred while updating the status. Please try again.');
        });
    }
    </script>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html> 